# Todo API

The Todo API provides comprehensive endpoints for managing todo lists, individual tasks, checkboxes, and user permissions for collaborative task management.

## Base URL

```
/backend/todo/
```

## Authentication

All endpoints require a valid JWT token passed as a cookie. The token must contain valid `userId`, `authority`, and `authority_id` claims.

### Required Permissions

- **READ_TODO**: View todo lists and tasks
- **WRITE_TODO**: Create and edit todo lists, tasks, and permissions
- **DELETE_TODO**: Delete todo lists, tasks, and checkboxes

## Endpoints

### Get Todo Lists

**GET** `/backend/todo/?action=getTodoLists`

Retrieves all todo lists the authenticated user has access to.

**Required Permission:** `READ_TODO`

**Response (200):**
```json
{
  "todoLists": [
    {
      "id": 1,
      "name": "Project Alpha Tasks",
      "owner_id": 10,
      "owner_name": "John Manager",
      "created_at": "2025-01-01 10:00:00",
      "updated_at": "2025-01-15 14:30:00",
      "todos_count": 12,
      "completed_count": 8,
      "permissions": ["read", "write"]
    },
    {
      "id": 2,
      "name": "Personal Tasks",
      "owner_id": 42,
      "owner_name": "Jane Doe",
      "created_at": "2025-01-10 09:00:00",
      "updated_at": "2025-01-20 11:15:00",
      "todos_count": 5,
      "completed_count": 2,
      "permissions": ["read", "write", "delete"]
    }
  ]
}
```

### Get Todos

**GET** `/backend/todo/?action=getTodos&list_id={id}`

Retrieves all todos in a specific list.

**Required Permission:** `READ_TODO`

**Query Parameters:**
- `list_id` (required): ID of the todo list

**Response (200):**
```json
{
  "todos": [
    {
      "id": 1,
      "list_id": 1,
      "title": "Complete API documentation",
      "description": "Write comprehensive API docs for all endpoints",
      "assigned_user_id": 42,
      "assigned_user_name": "Jane Doe",
      "importance": "high",
      "due_date": "2025-02-01",
      "completed": false,
      "completed_at": null,
      "created_at": "2025-01-15 10:00:00",
      "updated_at": "2025-01-15 10:00:00",
      "checkboxes": [
        {
          "id": 1,
          "text": "Review existing endpoints",
          "checked": true,
          "order": 1
        },
        {
          "id": 2,
          "text": "Write new endpoint docs",
          "checked": false,
          "order": 2
        }
      ]
    }
  ]
}
```

### Get Single Todo

**GET** `/backend/todo/?action=getTodo&id={id}`

Retrieves details of a specific todo.

**Required Permission:** `READ_TODO`

**Query Parameters:**
- `id` (required): ID of the todo

**Response (200):**
```json
{
  "todo": {
    "id": 1,
    "list_id": 1,
    "list_name": "Project Alpha Tasks",
    "title": "Complete API documentation",
    "description": "Write comprehensive API docs for all endpoints",
    "assigned_user_id": 42,
    "assigned_user_name": "Jane Doe",
    "importance": "high",
    "due_date": "2025-02-01",
    "completed": false,
    "completed_at": null,
    "created_at": "2025-01-15 10:00:00",
    "updated_at": "2025-01-15 10:00:00",
    "checkboxes": [
      {
        "id": 1,
        "text": "Review existing endpoints",
        "checked": true,
        "order": 1
      }
    ]
  }
}
```

### Get Todos Today

**GET** `/backend/todo/?action=getTodosToday`

Retrieves all todos due today for the authenticated user.

**Required Permission:** `READ_TODO`

**Response (200):**
```json
{
  "todos": [
    {
      "id": 5,
      "list_id": 2,
      "list_name": "Personal Tasks",
      "title": "Submit expense report",
      "assigned_user_id": 42,
      "importance": "medium",
      "due_date": "2025-01-20",
      "completed": false
    }
  ]
}
```

### Get Todos Next 7 Days

**GET** `/backend/todo/?action=getTodos7Days`

Retrieves all todos due in the next 7 days for the authenticated user.

**Required Permission:** `READ_TODO`

**Response (200):**
```json
{
  "todos": [
    {
      "id": 1,
      "list_id": 1,
      "list_name": "Project Alpha Tasks",
      "title": "Complete API documentation",
      "assigned_user_id": 42,
      "importance": "high",
      "due_date": "2025-01-25",
      "completed": false
    }
  ]
}
```

### Create Todo List

**POST** `/backend/todo/?action=createTodoList`

Creates a new todo list.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "name": "New Project Tasks"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo list created successfully",
  "list_id": 3
}
```

### Update Todo List Name

**POST** `/backend/todo/?action=updateTodoListName`

Updates the name of a todo list.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 3,
  "name": "Updated Project Name"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo list name updated successfully"
}
```

### Delete Todo List

**POST** `/backend/todo/?action=deleteTodoList`

Deletes a todo list and all its todos.

**Required Permission:** `DELETE_TODO`

**Request Body:**
```json
{
  "id": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo list deleted successfully"
}
```

### Create Todo

**POST** `/backend/todo/?action=createTodo`

Creates a new todo in a list.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "list_id": 1,
  "title": "New task",
  "description": "Task description",
  "assigned_user_id": 42,
  "importance": "medium",
  "due_date": "2025-02-15"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo created successfully",
  "todo_id": 10
}
```

### Update Todo

**POST** `/backend/todo/?action=updateTodo`

Updates a todo with all fields.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "title": "Updated task title",
  "description": "Updated description",
  "assigned_user_id": 43,
  "importance": "high",
  "due_date": "2025-02-20",
  "completed": false
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo updated successfully"
}
```

### Update Todo Title

**POST** `/backend/todo/?action=updateTodoTitle`

Updates only the title of a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "title": "New title"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo title updated successfully"
}
```

### Update Todo Description

**POST** `/backend/todo/?action=updateTodoDescription`

Updates only the description of a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "description": "New detailed description"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo description updated successfully"
}
```

### Update Todo Completion Status

**POST** `/backend/todo/?action=updateTodoCompletionStatus`

Toggles the completion status of a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "completed": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo completion status updated successfully"
}
```

### Update Todo Importance

**POST** `/backend/todo/?action=updateTodoImportance`

Updates the importance level of a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "importance": "critical"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo importance updated successfully"
}
```

### Update Todo Due Date

**POST** `/backend/todo/?action=updateTodoDueDate`

Updates the due date of a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "due_date": "2025-03-01"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo due date updated successfully"
}
```

### Update Todo Assigned User

**POST** `/backend/todo/?action=updateTodoAssignedUser`

Assigns a todo to a different user.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 10,
  "assigned_user_id": 45
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo assigned user updated successfully"
}
```

### Delete Todo

**POST** `/backend/todo/?action=deleteTodo`

Deletes a todo and all its checkboxes.

**Required Permission:** `DELETE_TODO`

**Request Body:**
```json
{
  "id": 10
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Todo deleted successfully"
}
```

### Create Todo Checkbox

**POST** `/backend/todo/?action=createTodoCheckbox`

Adds a checkbox item to a todo.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "todo_id": 1,
  "text": "New checkbox item",
  "order": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Checkbox created successfully",
  "checkbox_id": 5
}
```

### Update Checkbox

**POST** `/backend/todo/?action=updateCheckbox`

Updates a checkbox (text and/or checked status).

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 5,
  "text": "Updated checkbox text",
  "checked": true
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Checkbox updated successfully"
}
```

### Update Todo Checkbox Text

**POST** `/backend/todo/?action=updateTodoCheckboxText`

Updates only the text of a checkbox.

**Required Permission:** `WRITE_TODO`

**Request Body:**
```json
{
  "id": 5,
  "text": "New text"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Checkbox text updated successfully"
}
```

### Delete Checkbox

**POST** `/backend/todo/?action=deleteCheckbox`

Deletes a checkbox from a todo.

**Required Permission:** `DELETE_TODO`

**Request Body:**
```json
{
  "id": 5
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Checkbox deleted successfully"
}
```

### Get Permissions

**GET** `/backend/todo/?action=getPermissions&list_id={id}`

Retrieves user permissions for a todo list.

**Required Permission:** `READ_TODO`

**Query Parameters:**
- `list_id` (required): ID of the todo list

**Response (200):**
```json
{
  "permissions": [
    {
      "user_id": 42,
      "user_name": "Jane Doe",
      "can_read": true,
      "can_write": true,
      "can_delete": false
    }
  ]
}
```

### Update Permissions

**POST** `/backend/todo/?action=updatePermissions`

Updates user permissions for a todo list.

**Required Permission:** `WRITE_TODO` (list owner)

**Request Body:**
```json
{
  "list_id": 1,
  "permissions": [
    {
      "user_id": 42,
      "can_read": true,
      "can_write": true,
      "can_delete": true
    },
    {
      "user_id": 43,
      "can_read": true,
      "can_write": false,
      "can_delete": false
    }
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permissions updated successfully"
}
```

### Get Users with Todo Permissions

**GET** `/backend/todo/?action=getUsersWithTodoPermissions`

Retrieves all users who can be assigned to todos.

**Required Permission:** `READ_TODO`

**Response (200):**
```json
{
  "users": [
    {
      "id": 42,
      "first_name": "Jane",
      "last_name": "Doe",
      "email": "jane.doe@example.com",
      "department": "Development"
    }
  ]
}
```

## Importance Levels

The `importance` field supports:
- `low`: Low priority tasks
- `medium`: Normal priority tasks
- `high`: High priority tasks
- `critical`: Urgent, critical priority tasks

## Permission Model

Todo lists support granular permissions:
- **can_read**: View the list and its todos
- **can_write**: Create and edit todos in the list
- **can_delete**: Delete todos from the list

List owners automatically have all permissions.

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Missing required parameters or invalid data |
| 403 | Forbidden - Insufficient permissions or invalid authority |
| 404 | Not found - Todo list or todo does not exist |
| 405 | Method not allowed - Wrong HTTP method used |
| 500 | Internal server error - Database or system error |

## Multi-Tenant Isolation

All todo operations are automatically scoped to the authenticated user's authority. Users can only access todo lists within their authority.

## Examples

### JavaScript Example

```javascript
// Get all todo lists
async function getTodoLists() {
  const response = await fetch('/backend/todo/?action=getTodoLists', {
    method: 'GET',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data.todoLists;
}

// Create new todo
async function createTodo(listId, todoData) {
  const response = await fetch('/backend/todo/?action=createTodo', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      list_id: listId,
      ...todoData
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to create todo');
  }

  return data;
}

// Toggle todo completion
async function toggleTodoCompletion(todoId, completed) {
  const response = await fetch('/backend/todo/?action=updateTodoCompletionStatus', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      id: todoId,
      completed: completed
    })
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to update todo');
  }

  return data;
}
```

### cURL Examples

```bash
# Get todo lists
curl -X GET \
  'http://localhost:8080/backend/todo/?action=getTodoLists' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create todo
curl -X POST \
  'http://localhost:8080/backend/todo/?action=createTodo' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "list_id": 1,
    "title": "New task",
    "description": "Task details",
    "importance": "high",
    "due_date": "2025-02-01"
  }'

# Mark todo as completed
curl -X POST \
  'http://localhost:8080/backend/todo/?action=updateTodoCompletionStatus' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "id": 10,
    "completed": true
  }'
```

## Best Practices

1. **List Organization**: Create separate lists for different projects or categories
2. **Clear Titles**: Use descriptive, actionable titles
3. **Due Dates**: Set realistic deadlines
4. **Importance Levels**: Use appropriately to prioritize work
5. **Checkboxes**: Break complex tasks into smaller steps
6. **Assignments**: Assign tasks to appropriate team members
7. **Permissions**: Share lists with relevant team members
8. **Regular Updates**: Keep completion status current

## Database Tables

The Todo API interacts with the following tables:

- `todo_lists`: Todo list containers
- `todos`: Individual tasks
- `todo_checkboxes`: Checkbox items within todos
- `todo_permissions`: User permissions for lists
- `users`: User information for assignments

All tables include `authority_id` for multi-tenant data isolation.
