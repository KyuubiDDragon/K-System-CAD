# Aufgaben API

Die Aufgaben API bietet umfassende Endpunkte zur Verwaltung von Aufgabenlisten, einzelnen Aufgaben, Checkboxen und Benutzerberechtigungen für kollaboratives Aufgabenmanagement.

## Base URL

```
/backend/todo/
```

## Authentication

Alle Endpunkte erfordern einen gültigen JWT-Token, der als Cookie übergeben wird. Der Token muss gültige `userId`, `authority` und `authority_id` Claims enthalten.

### Erforderliche Berechtigungen

- **READ_TODO**: Aufgabenlisten und Aufgaben anzeigen
- **WRITE_TODO**: Aufgabenlisten, Aufgaben und Berechtigungen erstellen und bearbeiten
- **DELETE_TODO**: Aufgabenlisten, Aufgaben und Checkboxen löschen

## Endpoints

### Get Todo Lists

**GET** `/backend/todo/?action=getTodoLists`

Ruft alle Aufgabenlisten ab, auf die der authentifizierte Benutzer Zugriff hat.

**Erforderliche Berechtigung:** `READ_TODO`

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

Ruft alle Aufgaben in einer bestimmten Liste ab.

**Erforderliche Berechtigung:** `READ_TODO`

**Query Parameters:**
- `list_id` (erforderlich): ID der Aufgabenliste

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

Ruft Details einer bestimmten Aufgabe ab.

**Erforderliche Berechtigung:** `READ_TODO`

**Query Parameters:**
- `id` (erforderlich): ID der Aufgabe

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

Ruft alle heute fälligen Aufgaben für den authentifizierten Benutzer ab.

**Erforderliche Berechtigung:** `READ_TODO`

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

Ruft alle Aufgaben ab, die in den nächsten 7 Tagen für den authentifizierten Benutzer fällig sind.

**Erforderliche Berechtigung:** `READ_TODO`

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

Erstellt eine neue Aufgabenliste.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert den Namen einer Aufgabenliste.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Löscht eine Aufgabenliste und alle ihre Aufgaben.

**Erforderliche Berechtigung:** `DELETE_TODO`

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

Erstellt eine neue Aufgabe in einer Liste.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert eine Aufgabe mit allen Feldern.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert nur den Titel einer Aufgabe.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert nur die Beschreibung einer Aufgabe.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Ändert den Abschlussstatus einer Aufgabe.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert die Wichtigkeitsstufe einer Aufgabe.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert das Fälligkeitsdatum einer Aufgabe.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Weist eine Aufgabe einem anderen Benutzer zu.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Löscht eine Aufgabe und alle ihre Checkboxen.

**Erforderliche Berechtigung:** `DELETE_TODO`

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

Fügt einer Aufgabe ein Checkbox-Element hinzu.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert eine Checkbox (Text und/oder aktivierter Status).

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Aktualisiert nur den Text einer Checkbox.

**Erforderliche Berechtigung:** `WRITE_TODO`

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

Löscht eine Checkbox aus einer Aufgabe.

**Erforderliche Berechtigung:** `DELETE_TODO`

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

Ruft Benutzerberechtigungen für eine Aufgabenliste ab.

**Erforderliche Berechtigung:** `READ_TODO`

**Query Parameters:**
- `list_id` (erforderlich): ID der Aufgabenliste

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

Aktualisiert Benutzerberechtigungen für eine Aufgabenliste.

**Erforderliche Berechtigung:** `WRITE_TODO` (Listenbesitzer)

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

Ruft alle Benutzer ab, die Aufgaben zugewiesen werden können.

**Erforderliche Berechtigung:** `READ_TODO`

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

Das Feld `importance` unterstützt:
- `low`: Aufgaben mit niedriger Priorität
- `medium`: Aufgaben mit normaler Priorität
- `high`: Aufgaben mit hoher Priorität
- `critical`: Dringende Aufgaben mit kritischer Priorität

## Permission Model

Aufgabenlisten unterstützen granulare Berechtigungen:
- **can_read**: Liste und ihre Aufgaben anzeigen
- **can_write**: Aufgaben in der Liste erstellen und bearbeiten
- **can_delete**: Aufgaben aus der Liste löschen

Listenbesitzer haben automatisch alle Berechtigungen.

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Fehlende erforderliche Parameter oder ungültige Daten |
| 403 | Forbidden - Unzureichende Berechtigungen oder ungültige Behörde |
| 404 | Not found - Aufgabenliste oder Aufgabe existiert nicht |
| 405 | Method not allowed - Falsche HTTP-Methode verwendet |
| 500 | Internal server error - Datenbank- oder Systemfehler |

## Multi-Tenant Isolation

Alle Aufgabenvorgänge sind automatisch auf die Behörde des authentifizierten Benutzers beschränkt. Benutzer können nur auf Aufgabenlisten innerhalb ihrer Behörde zugreifen.

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

1. **List Organization**: Erstellen Sie separate Listen für verschiedene Projekte oder Kategorien
2. **Clear Titles**: Verwenden Sie beschreibende, handlungsorientierte Titel
3. **Due Dates**: Setzen Sie realistische Fristen
4. **Importance Levels**: Verwenden Sie angemessen zur Priorisierung der Arbeit
5. **Checkboxes**: Unterteilen Sie komplexe Aufgaben in kleinere Schritte
6. **Assignments**: Weisen Sie Aufgaben den entsprechenden Teammitgliedern zu
7. **Permissions**: Teilen Sie Listen mit relevanten Teammitgliedern
8. **Regular Updates**: Halten Sie den Abschlussstatus aktuell

## Database Tables

Die Aufgaben API interagiert mit folgenden Tabellen:

- `todo_lists`: Aufgabenlisten-Container
- `todos`: Einzelne Aufgaben
- `todo_checkboxes`: Checkbox-Elemente innerhalb von Aufgaben
- `todo_permissions`: Benutzerberechtigungen für Listen
- `users`: Benutzerinformationen für Zuweisungen

Alle Tabellen enthalten `authority_id` für Multi-Tenant-Datenisolation.
