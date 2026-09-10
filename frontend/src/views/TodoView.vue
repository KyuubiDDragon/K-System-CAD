<script setup lang="ts">
import { ref, computed, onMounted, reactive, watch, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { apiClientAuth } from '@/api'; // Use configured Axios instance
import { useAuthStore } from '@/stores/auth'; // Import Pinia Auth Store if needed for user context
import { useToast } from 'vue-toastification'; // Import toast
import { useI18n } from 'vue-i18n';
// src/types/Todo.ts

export interface TodoList {
    id: number;
    name: string;
    parent_list_id: number | null;
    sort_order?: number;
}

export interface Todo {
    id: number;
    list_id: number;
    title: string;
    description: string | null;
    completed: boolean;
    importance: 'low' | 'medium' | 'high';
    due_date: string | null; // Format: YYYY-MM-DD
    assigned_users: number[];
    checkboxes: TodoCheckbox[];
}

export interface TodoCheckbox {
    id: number;
    todo_id: number;
    title: string;
    completed: boolean;
}

export interface UserWithPermission {
    id: number;
    username: string;
    permission_level?: string;
}
import draggable from 'vuedraggable'; // Use vuedraggable

// Define props for desktop window mode
interface Props {
  meta?: Record<string, any>
  canEdit?: boolean
  canDelete?: boolean
  canCreate?: boolean
  allPermissions?: boolean
  id?: number | string
}

const props = withDefaults(defineProps<Props>(), {
  meta: () => ({}),
  canEdit: false,
  canDelete: false,
  canCreate: false,
  allPermissions: false,
  id: undefined
});

// Router
const route = useRoute();

// --- Store (if needed, e.g., for current user ID) ---
// const authStore = useAuthStore();
// const currentUserId = computed(() => authStore.user?.id);

// --- Component State ---
const todoLists = ref<TodoList[]>([]);
const todos = ref<Todo[]>([]); // Todos for the selected list
const usersWithPermissions = ref<UserWithPermission[]>([]); // Users who can be assigned
const loadingLists = ref(false);
const loadingTodos = ref(false);
const loadingTodoDetails = ref(false); // Separate indicator for loading details like users/checkboxes?
const savingList = ref(false);
const savingSubList = ref(false);
const savingTodo = ref(false);
const savingTodoDetails = ref(false); // Indicator for updates like importance, due date, assignment, description
const savingCheckbox = ref(false);
const deletingList = ref(false);
const deletingTodo = ref(false);
const deletingCheckbox = ref(false);

// --- Selection State ---
const selectedList = ref<TodoList | null>(null);
const selectedTodo = ref<Todo | null>(null);

// --- Dialog States ---
const showCreateListDialog = ref(false);
const deleteSubListDialog = reactive({ show: false, subList: null as TodoList | null });
const deleteTodoDialog = reactive({ show: false, todo: null as Todo | null });
const deleteCheckboxDialog = reactive({ show: false, checkbox: null as TodoCheckbox | null });

// --- Form & Input State ---
const createListFormRef = ref<any>(null);
const isCreateListFormValid = ref(false);
const newListName = ref('');
const newSubListNames = reactive<{ [key: number]: string }>({}); // Store new sublist names per parent ID
const newTodoTitle = ref('');
const newTodoCheckboxTitle = ref('');
const showCompletedTodos = ref(false); // Filter toggle for todo list
const showCompletedTodoCheckboxes = ref(false); // Filter toggle for checkbox list

// --- Inline Editing State ---
const editingField = ref<{ type: string; id: number }>({ type: '', id: -1 });
const editedValue = ref('');
const editFieldRef = ref<any>(null); // Ref for the inline text field

// --- Validation Rules ---
/**
 * Pflichtfeld-Regel.
 *
 * Der Entwurf verlangt, dass eine Meldung die Ursache benennt: "Diese
 * Dienstnummer ist bereits vergeben" statt "Ungueltige Eingabe". Fuer ein
 * fehlendes Pflichtfeld heisst das, den Namen des Feldes zu nennen - er steht
 * ohnehin als Beschriftung daneben.
 */
const requiredRule = (feld?: string) => (value: any) =>
    (value !== null && value !== undefined && String(value).trim() !== '') ||
    (feld ? `${feld} fehlt.` : 'Dieses Feld muss ausgefüllt werden.');

// --- Snackbar ---
const toast = useToast();
const { t } = useI18n();

function showSnackbar(message: string, color: 'success' | 'error' | 'info' | 'warning' = 'info') {
    if (color === 'success') toast.success(message);
    else if (color === 'error') toast.error(message);
    else if (color === 'warning') toast.warning(message);
    else toast.info(message);
}

// --- Computed Properties ---
const topLevelTodoLists = computed(() => todoLists.value.filter(list => !list.parent_list_id));
const getSubLists = (parentId: number) =>
    todoLists.value.filter(list => list.parent_list_id === parentId);

const unfinishedTodos = computed(() => {
    const filtered = showCompletedTodos.value
        ? todos.value
        : todos.value.filter(todo => !todo.completed);
    // Sort: Uncompleted first, then by due date (soonest first, nulls last), then importance (high first)
    return filtered.sort((a, b) => {
        if (a.completed !== b.completed) return a.completed ? 1 : -1; // Uncompleted first
        const dateA = a.due_date ? new Date(a.due_date).getTime() : Infinity;
        const dateB = b.due_date ? new Date(b.due_date).getTime() : Infinity;
        if (dateA !== dateB) return dateA - dateB; // Sort by due date (soonest first)
        const importanceOrder = { high: 1, medium: 2, low: 3 };
        return (importanceOrder[a.importance] ?? 9) - (importanceOrder[b.importance] ?? 9); // Sort by importance
    });
});

const unfinishedTodoCheckboxes = computed(() => {
    const checkboxes = selectedTodo.value?.checkboxes || [];
    if (showCompletedTodoCheckboxes.value) {
        return checkboxes; // Show all
    } else {
        return checkboxes.filter(checkbox => !checkbox.completed); // Show only uncompleted
    }
});

const isActiveList = (list: TodoList) => selectedList.value?.id === list.id;
const isActiveTodo = (todo: Todo) => selectedTodo.value?.id === todo.id;

const importanceOptions = [
    { text: 'Niedrig', value: 'low', icon: 'mdi-flag-outline', color: 'green' },
    { text: 'Mittel', value: 'medium', icon: 'mdi-flag', color: 'orange' },
    { text: 'Hoch', value: 'high', icon: 'mdi-flag-variant', color: 'red' },
];

// --- Data Fetching ---
const fetchTodoLists = async () => {
    loadingLists.value = true;
    try {
        const response = await apiClientAuth.get<TodoList[]>('/todo/?action=getTodoLists');
        // Sort lists by parent_id (nulls first) then name/sort_order
        todoLists.value = (response.data || []).sort((a, b) => {
            if (a.parent_list_id === null && b.parent_list_id !== null) return -1;
            if (a.parent_list_id !== null && b.parent_list_id === null) return 1;
            // Add sort_order if available
            // if(a.sort_order !== b.sort_order) return (a.sort_order ?? Infinity) - (b.sort_order ?? Infinity);
            return a.name.localeCompare(b.name);
        });
    } catch (error: any) {
        console.error('Error fetching todo lists:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Listen.', 'error');
        todoLists.value = [];
    } finally {
        loadingLists.value = false;
    }
};

const fetchTodos = async (listId: number | null) => {
    if (!listId) {
        todos.value = [];
        return;
    }
    loadingTodos.value = true;
    try {
        const response = await apiClientAuth.get<Todo[]>(`/todo/?action=getTodos&list_id=${listId}`);
        todos.value = (response.data || []).map((todo: any) => ({
            ...todo,
            completed: !!todo.completed, // Ensure boolean
            assigned_users: todo.assigned_users
                ? todo.assigned_users.map(Number).filter((id: number) => !isNaN(id))
                : [], // Ensure array of numbers
            checkboxes: (todo.checkboxes || []).map((checkbox: any) => ({
                ...checkbox,
                completed: !!checkbox.completed, // Ensure boolean
            })),
            importance: todo.importance || 'medium', // Default importance
            // Ensure due_date is in 'YYYY-MM-DD' format for date input, handle null
            due_date: todo.due_date ? todo.due_date.split(' ')[0] : null,
        }));
    } catch (error: any) {
        console.error('Error fetching todos:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Laden der Todos.', 'error');
        todos.value = [];
    } finally {
        loadingTodos.value = false;
    }
};

const fetchUsers = async (listId: number | null) => {
    if (!listId) {
        usersWithPermissions.value = [];
        return;
    }
    try {
        const response = await apiClientAuth.get<UserWithPermission[]>(
            `/todo/?action=getUsersWithTodoPermissions&list_id=${listId}`
        );
        usersWithPermissions.value = response.data || [];
    } catch (error: any) {
        console.error('Error fetching users with permissions:', error);
        // No snackbar here? Or a less prominent one?
        // showSnackbar(error.response?.data?.error || "Fehler beim Laden der Benutzer.", "warning");
        usersWithPermissions.value = [];
    }
};

// --- Methods ---

// List/Sub-List Management
const selectList = (list: TodoList) => {
    selectedList.value = list;
    selectedTodo.value = null; // Deselect todo when list changes
    fetchTodos(list.id);
    fetchUsers(list.id);
};

const createList = async () => {
    if (!isCreateListFormValid.value || !newListName.value.trim()) return;
    savingList.value = true;
    try {
        await apiClientAuth.post('/todo/?action=createTodoList', { name: newListName.value });
        await fetchTodoLists();
        showCreateListDialog.value = false;
        newListName.value = ''; // Reset
        showSnackbar('Ordner erfolgreich erstellt.', 'success');
    } catch (error: any) {
        console.error('Error creating list:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Erstellen des Ordners.', 'error');
    } finally {
        savingList.value = false;
    }
};

const createSubList = async (parentListId: number) => {
    const subListName = newSubListNames[parentListId]?.trim();
    if (!subListName) return;
    savingSubList.value = true; // Use general saving indicator?
    try {
        await apiClientAuth.post('/todo/?action=createTodoList', {
            name: subListName,
            parent_list_id: parentListId,
        });
        newSubListNames[parentListId] = ''; // Reset specific input
        await fetchTodoLists();
        showSnackbar('Liste erfolgreich hinzugefügt.', 'success');
    } catch (error: any) {
        console.error('Error creating sub-list:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Hinzufügen der Liste.', 'error');
    } finally {
        savingSubList.value = false;
    }
};

const showDeleteSubListDialog = (subList: TodoList) => {
    deleteSubListDialog.subList = subList;
    deleteSubListDialog.show = true;
};

const closeDeleteSublistDialog = () => {
    deleteSubListDialog.show = false;
    deleteSubListDialog.subList = null;
};

const deleteSubList = async () => {
    if (!deleteSubListDialog.subList) return;
    deletingList.value = true;
    try {
        await apiClientAuth.post('/todo/?action=deleteTodoList', {
            list_id: deleteSubListDialog.subList.id,
        });
        // If the deleted list was selected, clear selection and todos
        if (selectedList.value?.id === deleteSubListDialog.subList.id) {
            selectedList.value = null;
            selectedTodo.value = null;
            todos.value = [];
        }
        closeDeleteSublistDialog();
        await fetchTodoLists(); // Refresh list structure
        showSnackbar('Liste erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting list:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen der Liste.', 'error');
    } finally {
        deletingList.value = false;
    }
};

// Todo Management
const selectTodo = (todo: Todo) => {
    selectedTodo.value = todo;
};

const createTodo = async () => {
    if (!newTodoTitle.value.trim() || !selectedList.value) return;
    savingTodo.value = true;
    try {
        await apiClientAuth.post('/todo/?action=createTodo', {
            title: newTodoTitle.value,
            list_id: selectedList.value.id,
        });
        newTodoTitle.value = '';
        await fetchTodos(selectedList.value.id); // Refresh todos for current list
        showSnackbar('Todo erfolgreich hinzugefügt.', 'success');
    } catch (error: any) {
        console.error('Error creating todo:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Hinzufügen des Todos.', 'error');
    } finally {
        savingTodo.value = false;
    }
};

const updateTodoCompletionStatus = async (todo: Todo, completed: boolean) => {
    // Optimistic update
    todo.completed = completed;
    try {
        await apiClientAuth.post('/todo/?action=updateTodoCompletionStatus', {
            id: todo.id,
            completed: completed,
        });
        // Re-fetch or rely on optimistic update + sorting
        // await fetchTodos(selectedList.value?.id ?? null);
    } catch (error: any) {
        console.error('Error updating todo completion:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren des Todo-Status.',
            'error'
        );
        // Revert optimistic update on failure
        todo.completed = !completed;
    }
};

const openDeleteTodoDialog = (todo: Todo) => {
    deleteTodoDialog.todo = todo;
    deleteTodoDialog.show = true;
};

const closeDeleteTodoDialog = () => {
    deleteTodoDialog.show = false;
    deleteTodoDialog.todo = null;
};

const confirmDeleteTodo = async () => {
    if (!deleteTodoDialog.todo) return;
    deletingTodo.value = true;
    try {
        await apiClientAuth.post('/todo/?action=deleteTodo', { id: deleteTodoDialog.todo.id }); // Assuming endpoint name
        // If the deleted todo was selected, clear selection
        if (selectedTodo.value?.id === deleteTodoDialog.todo.id) {
            selectedTodo.value = null;
        }
        await fetchTodos(selectedList.value?.id ?? null); // Refresh todos
        closeDeleteTodoDialog();
        showSnackbar('Todo erfolgreich gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting todo:', error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Löschen des Todos.', 'error');
    } finally {
        deletingTodo.value = false;
    }
};

// Todo Detail Updates
const updateImportance = async () => {
    if (!selectedTodo.value) return;
    savingTodoDetails.value = true;
    try {
        await apiClientAuth.post('/todo/?action=updateTodoImportance', {
            id: selectedTodo.value.id,
            importance: selectedTodo.value.importance,
        });
        // Maybe refetch todos if importance affects sorting significantly
        // await fetchTodos(selectedList.value?.id ?? null);
    } catch (error: any) {
        console.error('Error updating importance:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Wichtigkeit.',
            'error'
        );
        // Consider reverting local change on error
    } finally {
        savingTodoDetails.value = false;
    }
};

const updateDueDate = async () => {
    if (!selectedTodo.value) return;
    savingTodoDetails.value = true;
    try {
        // Ensure date is sent in YYYY-MM-DD format or null
        const dateToSend = selectedTodo.value.due_date || null;
        await apiClientAuth.post('/todo/?action=updateTodoDueDate', {
            id: selectedTodo.value.id,
            due_date: dateToSend,
        });
        // Refetch if sorting by due date is critical
        // await fetchTodos(selectedList.value?.id ?? null);
    } catch (error: any) {
        console.error('Error updating due date:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern des Fälligkeitsdatums.',
            'error'
        );
        // Consider reverting local change on error
    } finally {
        savingTodoDetails.value = false;
    }
};

const updateAssignedUser = async () => {
    if (!selectedTodo.value) return;
    savingTodoDetails.value = true;
    try {
        // Ensure assigned_users is an array of numbers
        const userIds = (selectedTodo.value.assigned_users || [])
            .map(Number)
            .filter(id => !isNaN(id));
        await apiClientAuth.post('/todo/?action=updateTodoAssignedUser', {
            id: selectedTodo.value.id,
            assigned_users: userIds,
        });
    } catch (error: any) {
        console.error('Error updating assigned users:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Zuweisung.',
            'error'
        );
        // Consider refetching the todo or list to revert changes
    } finally {
        savingTodoDetails.value = false;
    }
};

const updateDescription = async () => {
    if (!selectedTodo.value) return;
    savingTodoDetails.value = true;
    try {
        await apiClientAuth.post('/todo/?action=updateTodoDescription', {
            id: selectedTodo.value.id,
            description: selectedTodo.value.description,
        });
    } catch (error: any) {
        console.error('Error updating description:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Speichern der Beschreibung.',
            'error'
        );
        // Consider reverting local change on error
    } finally {
        savingTodoDetails.value = false;
    }
};

// Checkbox Management
const createTodoCheckbox = async () => {
    if (!selectedTodo.value || !newTodoCheckboxTitle.value.trim()) return;
    savingCheckbox.value = true;
    try {
        const response = await apiClientAuth.post<{ checkbox_id: number }>(
            '/todo/?action=createTodoCheckbox',
            {
                todo_id: selectedTodo.value.id, // Send todo_id instead of id? Check API
                title: newTodoCheckboxTitle.value, // Send title instead of name? Check API
            }
        );
        const newCheckbox: TodoCheckbox = {
            id: response.data.checkbox_id, // Adjust based on actual response
            todo_id: selectedTodo.value.id,
            title: newTodoCheckboxTitle.value,
            completed: false,
        };
        if (!selectedTodo.value.checkboxes) {
            selectedTodo.value.checkboxes = [];
        }
        selectedTodo.value.checkboxes.push(newCheckbox);
        newTodoCheckboxTitle.value = ''; // Reset input
        showSnackbar('Teilaufgabe hinzugefügt.', 'success');
        // No need to refetch all todos, just update local state
    } catch (error: any) {
        console.error('Error creating checkbox:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Erstellen der Teilaufgabe.',
            'error'
        );
    } finally {
        savingCheckbox.value = false;
    }
};

const updateCheckbox = async (checkbox: TodoCheckbox, completed: boolean) => {
    // Optimistic update
    checkbox.completed = completed;
    try {
        await apiClientAuth.post('/todo/?action=updateCheckbox', {
            id: checkbox.id,
            completed: completed,
        });
        // No refetch needed if optimistic update is enough
    } catch (error: any) {
        console.error('Error updating checkbox:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Aktualisieren der Teilaufgabe.',
            'error'
        );
        // Revert optimistic update
        checkbox.completed = !completed;
    }
};

const openDeleteCheckboxDialog = (checkbox: TodoCheckbox) => {
    deleteCheckboxDialog.checkbox = checkbox;
    deleteCheckboxDialog.show = true;
};

const closeDeleteCheckboxDialog = () => {
    deleteCheckboxDialog.show = false;
    deleteCheckboxDialog.checkbox = null;
};

const confirmDeleteCheckbox = async () => {
    if (!deleteCheckboxDialog.checkbox || !selectedTodo.value) return;
    deletingCheckbox.value = true;
    const checkboxIdToDelete = deleteCheckboxDialog.checkbox.id;
    try {
        await apiClientAuth.post('/todo/?action=deleteCheckbox', { id: checkboxIdToDelete }); // Assuming endpoint name
        if (selectedTodo.value.checkboxes) {
            selectedTodo.value.checkboxes = selectedTodo.value.checkboxes.filter(
                cb => cb.id !== checkboxIdToDelete
            );
        }
        closeDeleteCheckboxDialog();
        showSnackbar('Teilaufgabe gelöscht.', 'success');
    } catch (error: any) {
        console.error('Error deleting checkbox:', error);
        showSnackbar(
            error.response?.data?.error || 'Fehler beim Löschen der Teilaufgabe.',
            'error'
        );
    } finally {
        deletingCheckbox.value = false;
    }
};

// Inline Editing Logic
const startEditingField = async (fieldType: string, entity: TodoList | Todo | TodoCheckbox) => {
    if (!entity || !('id' in entity)) return; // Basic check

    cancelEditingField(); // Cancel any previous edit

    editingField.value = { type: fieldType, id: entity.id };

    switch (fieldType) {
        case 'listName':
            editedValue.value = (entity as TodoList).name;
            break;
        case 'todoTitle':
            editedValue.value = (entity as Todo).title;
            break;
        case 'todoCheckboxEdit':
            editedValue.value = (entity as TodoCheckbox).title;
            break;
        // Add other cases if needed
        default:
            cancelEditingField();
            return;
    }

    // Focus the input field
    await nextTick();
    editFieldRef.value?.focus();
};

const cancelEditingField = () => {
    editingField.value = { type: '', id: -1 };
    editedValue.value = '';
};

// Specific update functions called from inline edit blur/enter
const updateListName = () => updateField();
const updateTodoTitle = () => updateField();
const updateCheckboxText = (checkbox: TodoCheckbox) => updateField(); // Pass checkbox if needed by API

async function updateField() {
    const { type, id } = editingField.value;
    const value = editedValue.value.trim();

    // Get original value to check if change occurred
    let originalValue = '';
    let entityToUpdate: TodoList | Todo | TodoCheckbox | undefined;
    if (type === 'listName') {
        entityToUpdate = todoLists.value.find(l => l.id === id);
        originalValue = entityToUpdate?.name ?? '';
    } else if (type === 'todoTitle') {
        entityToUpdate = todos.value.find(t => t.id === id);
        originalValue = entityToUpdate?.title ?? '';
    } else if (type === 'todoCheckboxEdit' && selectedTodo.value?.checkboxes) {
        entityToUpdate = selectedTodo.value.checkboxes.find(c => c.id === id);
        originalValue = entityToUpdate?.title ?? '';
    }

    // If no change, empty value, or entity not found, cancel
    if (!entityToUpdate || originalValue === value || !value) {
        cancelEditingField();
        return;
    }

    // API call based on type
    let endpoint = '';
    const payload: any = { id: id };
    let propertyName = '';

    switch (type) {
        case 'listName':
            endpoint = 'updateTodoListName';
            payload.name = value;
            propertyName = 'name';
            break;
        case 'todoTitle':
            endpoint = 'updateTodoTitle';
            payload.title = value;
            propertyName = 'title';
            break;
        case 'todoCheckboxEdit':
            endpoint = 'updateTodoCheckboxText';
            payload.title = value;
            propertyName = 'title';
            break;
        default:
            cancelEditingField();
            return;
    }

    // Optimistic update
    (entityToUpdate as any)[propertyName] = value;
    const currentEditingId = id; // Store id before cancelling
    cancelEditingField(); // Close input

    try {
        await apiClientAuth.post(`/todo/?action=${endpoint}`, payload);
        showSnackbar('Änderung gespeichert.', 'success');
        // Optional: Refetch if necessary, e.g., if sorting depends on the updated field
        // await fetchTodoLists(); or await fetchTodos(selectedList.value?.id);
    } catch (error: any) {
        console.error(`Error updating ${type}:`, error);
        showSnackbar(error.response?.data?.error || 'Fehler beim Speichern der Änderung.', 'error');
        // Revert optimistic update
        if (type === 'listName') {
            const list = todoLists.value.find(l => l.id === currentEditingId);
            if (list) list.name = originalValue;
        } else if (type === 'todoTitle') {
            const todo = todos.value.find(t => t.id === currentEditingId);
            if (todo) todo.title = originalValue;
        } else if (type === 'todoCheckboxEdit' && selectedTodo.value?.checkboxes) {
            const checkbox = selectedTodo.value.checkboxes.find(c => c.id === currentEditingId);
            if (checkbox) checkbox.title = originalValue;
        }
    }
}

// --- Utility Functions ---
const formatDateToDDMMYYYY = (dateString?: string | null): string | null => {
    if (!dateString) return null;
    try {
        const date = new Date(dateString.split(' ')[0]); // Handle date part only
        if (isNaN(date.getTime())) return null;
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}.${month}.${year}`;
    } catch {
        return null;
    }
};

const getDueDateStyle = (dueDate?: string | null) => {
    if (!dueDate) return {};
    try {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todoDueDate = new Date(dueDate);
        todoDueDate.setHours(0, 0, 0, 0);

        if (todoDueDate.getTime() === today.getTime()) {
            return { borderLeft: '4px solid orange' }; // Due today
        } else if (todoDueDate < today) {
            return { borderLeft: '4px solid red' }; // Overdue
        }
    } catch {
        return {}; // Invalid date
    }
    return {}; // Not due or overdue
};

const getImportanceColor = (importance: 'low' | 'medium' | 'high' | undefined): string => {
    switch (importance) {
        case 'low':
            return 'green';
        case 'medium':
            return 'orange';
        case 'high':
            return 'red';
        default:
            return 'grey'; // Default color
    }
};

// --- Lifecycle Hooks ---
onMounted(async () => {
    await fetchTodoLists();
    
    // Check for ID from route query or props
    const todoId = route.query.id || props.id || props.meta?.id;
    
    if (todoId) {
        // Find the todo with the specified ID across all lists
        for (const list of todoLists.value) {
            selectedList.value = list;
            await fetchTodos(list.id);
            
            const todo = todos.value.find(t => t.id === Number(todoId));
            if (todo) {
                // Select the todo and ensure its details are displayed
                selectTodo(todo);
                break;
            }
        }
    }
});
</script>

<template>
    <div class="todo-container">
        <v-container fluid class="pa-4">
            <!-- Drei-Spalten Layout -->
            <v-row>
                <!-- Linke Spalte: Ordner und Listen -->
                <v-col cols="12" md="4" lg="3">
                    <v-card class="list-card" elevation="3">
                        <v-toolbar density="compact" class="card-toolbar">
                            <v-toolbar-title class="text-subtitle-1">
                                <v-icon icon="mdi-folder-multiple" size="18" class="mr-2"></v-icon>
                                Meine Listen
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <v-tooltip text="Neuer Ordner" location="top">
                                <template v-slot:activator="{ props }">
                                    <v-btn
                                        icon="mdi-folder-plus-outline"
                                        variant="text"
                                        size="small"
                                        @click="showCreateListDialog = true"
                                        v-bind="props"
                                    ></v-btn>
                                </template>
                            </v-tooltip>
                        </v-toolbar>

                        <v-card-text class="pa-0 list-scroll-area">
                            <v-list
                                class="lists-container"
                                density="compact"
                                nav
                                bg-color="transparent"
                            >
                                <!-- Ladeindikator -->
                                <div v-if="loadingLists" class="text-center pa-4">
                                    <v-progress-circular
                                        indeterminate
                                        color="primary"
                                        size="24"
                                    ></v-progress-circular>
                                </div>

                                <!-- Leerer Zustand -->
                                <template v-else-if="topLevelTodoLists.length === 0">
                                    <div class="empty-list-message">
                                        <v-icon
                                            icon="mdi-folder-outline"
                                            size="36"
                                            color="grey-darken-1"
                                            class="mb-2"
                                        ></v-icon>
                                        <span>{{ t('todoView.noFolders') }}</span>
                                        <v-btn
                                            prepend-icon="mdi-folder-plus-outline"
                                            variant="tonal"
                                            color="primary"
                                            size="small"
                                            class="mt-4"
                                            @click="showCreateListDialog = true"
                                        >
                                            {{ t('todoView.createFolder') }}
                                        </v-btn>
                                    </div>
                                </template>

                                <!-- Ordnerliste -->
                                <v-list-group
                                    v-for="list in topLevelTodoLists"
                                    :key="list.id"
                                    :value="list.id"
                                    class="folder-group"
                                >
                                    <template #activator="{ props }">
                                        <v-list-item
                                            v-bind="props"
                                            :title="list.name"
                                            class="top-level-list"
                                            prepend-icon="mdi-folder"
                                        >
                                        </v-list-item>
                                    </template>

                                    <v-list-item
                                        v-for="subList in getSubLists(list.id)"
                                        :key="subList.id"
                                        @click="selectList(subList)"
                                        :active="isActiveList(subList)"
                                        color="primary"
                                        link
                                        class="sub-list-item"
                                        density="comfortable"
                                        prepend-icon="mdi-note-text-outline"
                                    >
                                        <v-list-item-title>{{ subList.name }}</v-list-item-title>
                                        <template v-slot:append>
                                            <v-tooltip text="Liste löschen" location="top">
                                                <template v-slot:activator="{ props }">
                                                    <v-btn
                                                        icon="mdi-delete-outline"
                                                        size="x-small"
                                                        variant="text"
                                                        @click.stop="
                                                            showDeleteSubListDialog(subList)
                                                        "
                                                        v-bind="props"
                                                        color="grey"
                                                    ></v-btn>
                                                </template>
                                            </v-tooltip>
                                        </template>
                                    </v-list-item>

                                    <v-list-item class="pa-0 ma-0">
                                        <v-text-field
                                            v-model="newSubListNames[list.id]"
                                            label="Neue Liste hinzufügen..."
                                            @keyup.enter="createSubList(list.id)"
                                            prepend-inner-icon="mdi-plus"
                                            variant="solo-filled"
                                            density="compact"
                                            flat
                                            hide-details
                                            class="ma-2"
                                            
                                        ></v-text-field>
                                    </v-list-item>
                                </v-list-group>
                            </v-list>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Mittlere Spalte: To-Do Übersicht -->
                <v-col cols="12" md="4" lg="4">
                    <v-card
                        class="list-card"
                        :disabled="!selectedList"
                        :loading="loadingTodos"
                        elevation="3"
                    >
                        <v-toolbar density="compact" class="card-toolbar">
                            <v-toolbar-title class="text-subtitle-1">
                                <template
                                    v-if="
                                        editingField.type !== 'listName' ||
                                        editingField.id !== selectedList?.id
                                    "
                                >
                                    <span
                                        @dblclick="
                                            selectedList
                                                ? startEditingField('listName', selectedList)
                                                : null
                                        "
                                        :style="selectedList ? 'cursor: pointer;' : ''"
                                    >
                                        <v-icon
                                            :icon="
                                                selectedList
                                                    ? 'mdi-note-text'
                                                    : 'mdi-note-off-outline'
                                            "
                                            size="18"
                                            class="mr-2"
                                        ></v-icon>
                                        {{
                                            selectedList
                                                ? selectedList.name
                                                : t('todoView.noListSelected')
                                        }}
                                        <v-tooltip
                                            v-if="selectedList"
                                            text="Doppelklick zum Bearbeiten"
                                            location="top"
                                        >
                                            <template v-slot:activator="{ props }">
                                                <v-icon
                                                    v-bind="props"
                                                    size="x-small"
                                                    class="ml-1"
                                                    color="grey"
                                                    >mdi-pencil-outline</v-icon
                                                >
                                            </template>
                                        </v-tooltip>
                                    </span>
                                </template>
                                <template v-else>
                                    <v-text-field
                                        v-model="editedValue"
                                        density="compact"
                                        variant="solo-filled"
                                        single-line
                                        autofocus
                                        flat
                                        hide-details
                                        ref="editFieldRef"
                                        @keydown.enter="updateListName"
                                        @keydown.esc="cancelEditingField"
                                        @blur="updateListName"
                                        class="ma-0 pa-0 inline-edit-field"
                                        
                                    ></v-text-field>
                                </template>
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <div v-if="selectedList" class="d-flex align-center">
                                <span class="text-caption mr-2">Fertige anzeigen</span>
                                <v-switch
                                    v-model="showCompletedTodos"
                                    color="primary"
                                    density="compact"
                                    hide-details
                                    class="mr-1"
                                ></v-switch>
                            </div>
                        </v-toolbar>

                        <v-card-text class="pa-0 list-scroll-area">
                            <v-list class="todos-container" lines="two" bg-color="transparent">
                                <!-- Kein Listenauswahl -->
                                <div v-if="!selectedList" class="empty-state">
                                    <v-icon
                                        icon="mdi-format-list-checks"
                                        size="48"
                                        color="grey-darken-1"
                                        class="mb-4"
                                    ></v-icon>
                                    <span>Wählen Sie links eine Liste aus</span>
                                </div>

                                <!-- Ladeindikator -->
                                <div v-else-if="loadingTodos" class="empty-state">
                                    <v-progress-circular
                                        indeterminate
                                        color="primary"
                                        size="32"
                                    ></v-progress-circular>
                                </div>

                                <!-- Leerer Zustand -->
                                <div
                                    v-else-if="unfinishedTodos.length === 0 && !showCompletedTodos"
                                    class="empty-state"
                                >
                                    <v-icon
                                        icon="mdi-check-all"
                                        size="48"
                                        color="grey-darken-1"
                                        class="mb-4"
                                    ></v-icon>
                                    <span>{{ t('todoView.noOpenTodos') }}</span>
                                </div>
                                <div
                                    v-else-if="unfinishedTodos.length === 0 && showCompletedTodos"
                                    class="empty-state"
                                >
                                    <v-icon
                                        icon="mdi-note-off-outline"
                                        size="48"
                                        color="grey-darken-1"
                                        class="mb-4"
                                    ></v-icon>
                                    <span>{{ t('todoView.noTodos') }}</span>
                                </div>

                                <!-- To-Do Liste -->
                                <template v-for="todo in unfinishedTodos" :key="todo.id">
                                    <v-list-item
                                        @click="selectTodo(todo)"
                                        :active="isActiveTodo(todo)"
                                        :class="['todo-item', { 'completed-todo': todo.completed }]"
                                        :style="getDueDateStyle(todo.due_date)"
                                        color="primary"
                                    >
                                        <template v-slot:prepend>
                                            <v-checkbox-btn
                                                v-model="todo.completed"
                                                @update:modelValue="
                                                    value => updateTodoCompletionStatus(todo, value)
                                                "
                                                :color="getImportanceColor(todo.importance)"
                                                class="mr-n2"
                                                hide-details
                                            ></v-checkbox-btn>
                                        </template>

                                        <v-list-item-title
                                            :class="{
                                                'text-decoration-line-through text-grey':
                                                    todo.completed,
                                            }"
                                        >
                                            {{ todo.title }}
                                        </v-list-item-title>

                                        <v-list-item-subtitle
                                            v-if="todo.checkboxes && todo.checkboxes.length > 0"
                                            class="mt-1"
                                        >
                                            <div class="d-flex align-center">
                                                <v-icon
                                                    icon="mdi-format-list-checks"
                                                    size="14"
                                                    class="mr-1"
                                                ></v-icon>
                                                {{
                                                    todo.checkboxes.filter(c => c.completed).length
                                                }}
                                                / {{ todo.checkboxes.length }} erledigt
                                            </div>
                                        </v-list-item-subtitle>
                                        <v-list-item-subtitle v-if="todo.due_date" class="mt-1">
                                            <div class="d-flex align-center">
                                                <v-icon
                                                    icon="mdi-calendar"
                                                    size="14"
                                                    class="mr-1"
                                                ></v-icon>
                                                {{ formatDateToDDMMYYYY(todo.due_date) }}
                                            </div>
                                        </v-list-item-subtitle>

                                        <template v-slot:append>
                                            <v-icon :color="getImportanceColor(todo.importance)"
                                                >mdi-flag</v-icon
                                            >
                                        </template>
                                    </v-list-item>
                                    <v-divider v-if="!todo.completed"></v-divider>
                                </template>

                                <!-- Neues To-Do hinzufügen -->
                                <v-list-item v-if="selectedList" class="pa-0 ma-0">
                                    <v-text-field
                                        v-model="newTodoTitle"
                                        label="Neues Todo hinzufügen..."
                                        @keyup.enter="createTodo"
                                        prepend-inner-icon="mdi-plus"
                                        variant="solo-filled"
                                        density="compact"
                                        flat
                                        hide-details
                                        class="ma-2"
                                        
                                    ></v-text-field>
                                </v-list-item>
                            </v-list>
                        </v-card-text>
                    </v-card>
                </v-col>

                <!-- Rechte Spalte: To-Do Details -->
                <v-col cols="12" md="4" lg="5">
                    <v-card
                        class="list-card"
                        :disabled="!selectedTodo"
                        :loading="loadingTodoDetails || savingTodoDetails"
                        elevation="3"
                    >
                        <v-toolbar density="compact" class="card-toolbar">
                            <v-toolbar-title class="text-subtitle-1">
                                <template
                                    v-if="
                                        editingField.type !== 'todoTitle' ||
                                        editingField.id !== selectedTodo?.id
                                    "
                                >
                                    <span
                                        @dblclick="
                                            selectedTodo
                                                ? startEditingField('todoTitle', selectedTodo)
                                                : null
                                        "
                                        :style="selectedTodo ? 'cursor: pointer;' : ''"
                                    >
                                        <v-icon
                                            :icon="
                                                selectedTodo
                                                    ? 'mdi-clipboard-text'
                                                    : 'mdi-clipboard-off-outline'
                                            "
                                            size="18"
                                            class="mr-2"
                                        ></v-icon>
                                        {{
                                            selectedTodo
                                                ? selectedTodo.title
                                                : 'Kein Todo ausgewählt'
                                        }}
                                        <v-tooltip
                                            v-if="selectedTodo"
                                            text="Doppelklick zum Bearbeiten"
                                            location="top"
                                        >
                                            <template v-slot:activator="{ props }">
                                                <v-icon
                                                    v-bind="props"
                                                    size="x-small"
                                                    class="ml-1"
                                                    color="grey"
                                                    >mdi-pencil-outline</v-icon
                                                >
                                            </template>
                                        </v-tooltip>
                                    </span>
                                </template>
                                <template v-else>
                                    <v-text-field
                                        v-model="editedValue"
                                        density="compact"
                                        variant="solo-filled"
                                        single-line
                                        autofocus
                                        flat
                                        hide-details
                                        ref="editFieldRef"
                                        @keydown.enter="updateTodoTitle"
                                        @keydown.esc="cancelEditingField"
                                        @blur="updateTodoTitle"
                                        class="ma-0 pa-0 inline-edit-field"
                                        
                                    ></v-text-field>
                                </template>
                            </v-toolbar-title>
                            <v-spacer></v-spacer>
                            <div v-if="selectedTodo" class="d-flex align-center">
                                <span class="text-caption mr-2">Fertige Teilaufgaben</span>
                                <v-switch
                                    v-model="showCompletedTodoCheckboxes"
                                    color="primary"
                                    density="compact"
                                    hide-details
                                    class="mr-1"
                                ></v-switch>
                                <v-tooltip text="Todo löschen" location="bottom">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon="mdi-delete-outline"
                                            variant="text"
                                            size="small"
                                            @click="openDeleteTodoDialog(selectedTodo!)"
                                            v-bind="props"
                                            color="error"
                                        ></v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </v-toolbar>

                        <v-card-text v-if="selectedTodo" class="todo-details-container pa-4">
                            <v-row dense>
                                <v-col cols="12" md="6">
                                    <v-select
                                        v-model="selectedTodo.importance"
                                        :items="importanceOptions"
                                        item-title="text"
                                        item-value="value"
                                        label="Wichtigkeit"
                                        @update:modelValue="updateImportance"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        color="primary"
                                    >
                                        <template v-slot:item="{ props, item }">
                                            <v-list-item
                                                v-bind="props"
                                                :prepend-icon="item.raw.icon"
                                                :color="item.raw.color"
                                            ></v-list-item>
                                        </template>
                                        <template v-slot:selection="{ item }">
                                            <v-icon :color="item.raw.color" left class="mr-2">{{
                                                item.raw.icon
                                            }}</v-icon>
                                            {{ item.title }}
                                        </template>
                                    </v-select>
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field
                                        v-model="selectedTodo.due_date"
                                        label="Erledigen bis"
                                        type="date"
                                        @change="updateDueDate"
                                        variant="outlined"
                                        density="comfortable"
                                        
                                        color="primary"
                                    ></v-text-field>
                                </v-col>
                            </v-row>

                            <v-select
                                v-model="selectedTodo.assigned_users"
                                :items="usersWithPermissions"
                                item-title="username"
                                item-value="id"
                                label="Zugewiesen an"
                                chips
                                multiple
                                closable-chips
                                @update:modelValue="updateAssignedUser"
                                variant="outlined"
                                density="comfortable"
                                class="mt-3"
                                
                                color="primary"
                            ></v-select>

                            <v-textarea
                                label="Notizen / Beschreibung"
                                v-model="selectedTodo.description"
                                @change="updateDescription"
                                variant="outlined"
                                density="comfortable"
                                rows="4"
                                auto-grow
                                class="mt-3"
                                
                                color="primary"
                            ></v-textarea>

                            <v-divider class="my-4"></v-divider>

                            <div class="subtasks-section">
                                <div class="d-flex align-center mb-3">
                                    <v-icon
                                        icon="mdi-format-list-checks"
                                        size="20"
                                        class="mr-2"
                                    ></v-icon>
                                    <div class="text-subtitle-2">Teilaufgaben</div>
                                </div>

                                <div class="subtasks-list">
                                    <v-list
                                        density="compact"
                                        class="pa-0 subtasks-container"
                                        bg-color="transparent"
                                    >
                                        <!-- Leerer Zustand -->
                                        <div
                                            v-if="unfinishedTodoCheckboxes.length === 0"
                                            class="text-center text-grey pa-3"
                                        >
                                            {{ t('todoView.noSubtasks') }}
                                        </div>

                                        <v-list-item
                                            v-for="checkbox in unfinishedTodoCheckboxes"
                                            :key="checkbox.id"
                                            class="pl-0 pr-0 checkbox-item"
                                            :class="{
                                                'text-decoration-line-through text-grey':
                                                    checkbox.completed,
                                            }"
                                        >
                                            <template v-slot:prepend>
                                                <v-checkbox-btn
                                                    v-model="checkbox.completed"
                                                    @update:modelValue="
                                                        value => updateCheckbox(checkbox, value)
                                                    "
                                                    hide-details
                                                    color="primary"
                                                ></v-checkbox-btn>
                                            </template>

                                            <v-list-item-title>
                                                <template
                                                    v-if="
                                                        editingField.type !== 'todoCheckboxEdit' ||
                                                        editingField.id !== checkbox.id
                                                    "
                                                >
                                                    <span
                                                        @dblclick="
                                                            startEditingField(
                                                                'todoCheckboxEdit',
                                                                checkbox
                                                            )
                                                        "
                                                        :style="'cursor: pointer;'"
                                                    >
                                                        {{ checkbox.title }}
                                                        <v-tooltip
                                                            text="Doppelklick zum Bearbeiten"
                                                            location="top"
                                                        >
                                                            <template v-slot:activator="{ props }">
                                                                <v-icon
                                                                    v-bind="props"
                                                                    size="x-small"
                                                                    class="ml-1"
                                                                    color="grey"
                                                                    >mdi-pencil-outline</v-icon
                                                                >
                                                            </template>
                                                        </v-tooltip>
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    <v-text-field
                                                        v-model="editedValue"
                                                        density="compact"
                                                        variant="underlined"
                                                        single-line
                                                        autofocus
                                                        hide-details
                                                        ref="editFieldRef"
                                                        @keydown.enter="
                                                            updateCheckboxText(checkbox)
                                                        "
                                                        @keydown.esc="cancelEditingField"
                                                        @blur="updateCheckboxText(checkbox)"
                                                        class="ma-0 pa-0 d-inline-block"
                                                        bg-color="grey-darken-4"
                                                        color="primary"
                                                    ></v-text-field>
                                                </template>
                                            </v-list-item-title>

                                            <template v-slot:append>
                                                <v-tooltip
                                                    text="Teilaufgabe löschen"
                                                    location="top"
                                                >
                                                    <template v-slot:activator="{ props }">
                                                        <v-btn
                                                            icon="mdi-delete-outline"
                                                            size="x-small"
                                                            variant="text"
                                                            @click.stop="
                                                                openDeleteCheckboxDialog(checkbox)
                                                            "
                                                            v-bind="props"
                                                            color="grey"
                                                        ></v-btn>
                                                    </template>
                                                </v-tooltip>
                                            </template>
                                        </v-list-item>
                                    </v-list>
                                </div>

                                <v-text-field
                                    v-model="newTodoCheckboxTitle"
                                    label="Neue Teilaufgabe"
                                    prepend-inner-icon="mdi-plus"
                                    variant="outlined"
                                    density="comfortable"
                                    @keydown.enter="createTodoCheckbox"
                                    hide-details
                                    class="mt-3"
                                    
                                    color="primary"
                                ></v-text-field>
                            </div>
                        </v-card-text>

                        <v-card-text v-else class="empty-state">
                            <v-icon
                                icon="mdi-clipboard-outline"
                                size="48"
                                color="grey-darken-1"
                                class="mb-4"
                            ></v-icon>
                            <span
                                >Wählen Sie ein Todo aus, um Details anzuzeigen oder zu
                                bearbeiten</span
                            >
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <!-- Dialoge -->

            <!-- Neue Liste Dialog -->
            <v-dialog v-model="showCreateListDialog" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-folder-plus" class="mr-2"></v-icon>
                        Neuen Ordner hinzufügen
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <v-form ref="createListFormRef" v-model="isCreateListFormValid">
                            <v-text-field
                                v-model="newListName"
                                label="Ordnername"
                                required
                                :rules="[requiredRule('Ordnername')]"
                                variant="outlined"
                                density="comfortable"
                                
                                color="primary"
                            ></v-text-field>
                        </v-form>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="showCreateListDialog = false"
                            >Abbrechen</v-btn
                        >
                        <v-btn
                            color="primary"
                            variant="elevated"
                            @click="createList"
                            :disabled="!isCreateListFormValid"
                            :loading="savingList"
                        >
                            Erstellen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Liste löschen Dialog -->
            <v-dialog v-model="deleteSubListDialog.show" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Liste löschen
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-body-1 mb-2">
                            Möchten Sie die folgende Liste wirklich löschen?
                        </p>
                        <p class="text-body-2 font-weight-bold">
                            {{ deleteSubListDialog.subList?.name }}
                        </p>
                        <p class="text-caption text-grey-darken-1 mt-4">
                            Alle darin enthaltenen Todos werden ebenfalls gelöscht. Diese Aktion
                            kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteSublistDialog">Abbrechen</v-btn>
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="deleteSubList"
                            :loading="deletingList"
                        >
                            Löschen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Todo löschen Dialog -->
            <v-dialog v-model="deleteTodoDialog.show" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Todo löschen
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-body-1 mb-2">
                            Möchten Sie das folgende Todo wirklich löschen?
                        </p>
                        <p class="text-body-2 font-weight-bold">
                            {{ deleteTodoDialog.todo?.title }}
                        </p>
                        <p class="text-caption text-grey-darken-1 mt-4">
                            Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteTodoDialog">Abbrechen</v-btn>
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDeleteTodo"
                            :loading="deletingTodo"
                        >
                            Löschen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>

            <!-- Teilaufgabe löschen Dialog -->
            <v-dialog v-model="deleteCheckboxDialog.show" max-width="500" persistent>
                <v-card class="dialog-card">
                    <v-card-title class="dialog-title">
                        <v-icon icon="mdi-delete-alert" class="mr-2" color="error"></v-icon>
                        Teilaufgabe löschen
                    </v-card-title>
                    <v-card-text class="pa-4">
                        <p class="text-body-1 mb-2">
                            Möchten Sie die folgende Teilaufgabe wirklich löschen?
                        </p>
                        <p class="text-body-2 font-weight-bold">
                            {{ deleteCheckboxDialog.checkbox?.title }}
                        </p>
                        <p class="text-caption text-grey-darken-1 mt-4">
                            Diese Aktion kann nicht rückgängig gemacht werden.
                        </p>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4">
                        <v-spacer></v-spacer>
                        <v-btn variant="text" @click="closeDeleteCheckboxDialog">Abbrechen</v-btn>
                        <v-btn
                            color="error"
                            variant="elevated"
                            @click="confirmDeleteCheckbox"
                            :loading="deletingCheckbox"
                        >
                            Löschen
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </div>
</template>

<style scoped>
.todo-container {
    min-height: 90vh;
    background-color: var(--k-canvas);
    background-image:
        radial-gradient(circle at 20% 30%, rgba(30, 64, 175, 0.05) 0%, transparent 25%),
        radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.05) 0%, transparent 35%);
    position: relative;
}

.list-card {
    position: relative;
    background: rgba(15, 23, 42, 0.6) !important;
    border: 1px solid var(--k-line);
    backdrop-filter: blur(10px);
    height: calc(90vh - 100px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.card-toolbar {
    background: linear-gradient(90deg, rgba(30, 58, 138, 0.2), rgba(30, 64, 175, 0.1)) !important;
    border-bottom: 1px solid var(--k-line);
}

.list-scroll-area {
    flex-grow: 1;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, 0.5) transparent;
}

.list-scroll-area::-webkit-scrollbar {
    width: 4px;
}

.list-scroll-area::-webkit-scrollbar-thumb {
    background-color: rgba(59, 130, 246, 0.5);
    border-radius: 2px;
}

.list-scroll-area::-webkit-scrollbar-track {
    background: transparent;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: var(--k-ink-muted);
    font-size: 14px;
    height: 100%;
}

.empty-list-message {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 32px 16px;
    color: var(--k-ink-muted);
    text-align: center;
}

.folder-group {
    background: rgba(30, 41, 59, 0.3);
    border-radius: 4px;
    margin: 4px 0;
    border: 1px solid var(--k-line);
}

.top-level-list {
    border-bottom: 1px solid var(--k-line);
}

.sub-list-item {
    margin: 2px 0;
    transition: background-color 0.2s ease;
    border-radius: 4px;
}

.sub-list-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
}

.todo-item {
    position: relative;
    transition: all 0.2s ease;
    margin: 2px 0;
    border-radius: 4px;
}

.todo-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
    transform: translateX(4px);
}

.completed-todo {
    opacity: 0.7;
}

.todo-details-container {
    padding: 16px;
    overflow-y: auto;
}

.subtasks-section {
    background: rgba(15, 23, 42, 0.3);
    border-radius: 8px;
    padding: 16px;
    border: 1px solid var(--k-line);
}

.subtasks-list {
    max-height: 200px;
    overflow-y: auto;
    margin-bottom: 16px;
    scrollbar-width: thin;
    scrollbar-color: rgba(59, 130, 246, 0.5) transparent;
}

.subtasks-list::-webkit-scrollbar {
    width: 4px;
}

.subtasks-list::-webkit-scrollbar-thumb {
    background-color: rgba(59, 130, 246, 0.5);
    border-radius: 2px;
}

.subtasks-list::-webkit-scrollbar-track {
    background: transparent;
}

.checkbox-item {
    transition: background-color 0.2s ease;
    border-radius: 4px;
}

.checkbox-item:hover {
    background: rgba(30, 41, 59, 0.6) !important;
}

.inline-edit-field .v-field__input {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
    min-height: auto !important;
    font-size: inherit;
}

.inline-edit-field .v-input__details {
    display: none;
}

/* Dialog styling */
.dialog-card {
    background-color: var(--k-canvas) !important;
    border: 1px solid var(--k-line);
}

.dialog-title {
    background: linear-gradient(90deg, #1e3a8a, var(--k-accent-hover));
    color: var(--k-ink);
    padding: 16px;
}

/* Animation effects */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.todo-item,
.folder-group,
.sub-list-item,
.checkbox-item {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
