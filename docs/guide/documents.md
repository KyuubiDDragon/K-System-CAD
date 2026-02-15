# Documents

Document management system for organizing rich-text documents in categories with drag-and-drop sorting and area-based permissions.

## Overview

Features:
- Rich-text document creation with formatting toolbar
- Category-based organization
- Drag-and-drop sorting (categories and documents)
- Document areas with role-based permissions
- Grid and list view modes
- Basic search by title
- Multi-tenant support

**Required Permission:** `READ_DOCUMENT` (view), `WRITE_DOCUMENT` (create/edit), `DELETE_DOCUMENT` (delete)

## Accessing Documents

**Route:** `/document`

**How to Access:**
- Desktop: Double-click **Documents** icon
- Menu: Start Menu → Documents
- Or navigate directly to `/document`

**Area-Based Access:**
- Different document areas may have different routes
- Access controlled by area permissions (read, write, delete)
- Authority-specific document isolation

## Interface Layout

### Header
- Title with document icon
- Search field (search by title)
- View mode toggle (Grid/List)

### Categories Section
- Expandable category cards
- Document count per category
- Drag-and-drop to reorder categories
- Click to expand/collapse documents

### Documents Section
- Document cards showing:
  - Title
  - Creator name
  - Created date
  - Last updated date
- Hover to see edit/delete actions
- Click document to view/edit

### Action Buttons
- **Create Category** - Add new category (if you have write permission)
- **Sort Categories** - Drag-and-drop interface
- **Sort Documents** - Drag-and-drop interface within category

## Creating Documents

### New Document

1. Click **Create Document** button (+ icon)
2. Document Editor modal opens
3. Fill in fields:
   - **Title** (required): Document name
   - **Category** (required): Select from dropdown
   - **Content**: Rich-text content with formatting toolbar
   - **Notes** (optional): Internal notes about the document
4. Click **Save**
5. Document appears in selected category

**Rich Text Editor Features:**
- Bold, italic, underline
- Headings (H1, H2, H3)
- Bullet lists and numbered lists
- Tables
- Links
- Images
- Text alignment
- Code blocks

**Note:** Documents are rich-text content only, not file uploads. To attach files, use the File Manager.

## Creating Categories

### New Category

1. Click **Create Category** button (folder+ icon)
2. Dialog opens
3. Enter category name
4. Click **Create**
5. Category appears in list

**Category Features:**
- Unlimited categories per document area
- Drag-and-drop sorting to reorder
- Rename by editing category
- Delete entire category (warning: deletes all documents inside)

## Viewing and Editing Documents

### View Document

**Method 1: Click Document Card**
1. Find document in category
2. Click document card
3. Document Editor opens in view/edit mode
4. Edit fields if you have write permission
5. Click **Save** to update

**Method 2: Direct Access**
- If document area has specific ID route
- Documents load automatically for that area

### Edit Document

1. Click document to open editor
2. Modify fields:
   - Title
   - Category (can move to different category)
   - Content (rich-text editor)
   - Notes
3. Click **Save**
4. Changes applied immediately

**Permissions Required:**
- Must have `WRITE_DOCUMENT` permission
- Area-level write permission required

## Deleting Documents

### Delete Document

1. Hover over document card
2. Click **Delete** button (trash icon)
3. Confirm deletion in dialog
4. Document soft-deleted (archived)

**Note:** Deletion is permanent from user perspective. Only administrators can recover from database.

**Permission Required:** `DELETE_DOCUMENT` + area-level delete permission

## Deleting Categories

### Delete Category

1. Find category
2. Click **Delete** button (trash icon on category card)
3. **Warning:** Deleting a category deletes ALL documents inside
4. Confirm deletion
5. Category and all documents removed

**Permission Required:** `DELETE_DOCUMENT`

## Sorting

### Sort Categories

**Drag-and-Drop:**
1. Click **Sort Categories** button
2. Drag-and-drop interface appears
3. Drag categories to desired order
4. Click **Save Order**
5. Category order updates

**Or:**
- Drag category cards directly in main view
- Order saves automatically via API

### Sort Documents

**Drag-and-Drop Within Category:**
1. Click **Sort Documents** button
2. Select category to sort
3. Drag documents within that category
4. Click **Save Order**
5. Document order updates

**Note:** Documents can only be sorted within their category. To move to different category, edit the document and change category.

## Search Documents

### Search Functionality

**Search Bar:**
- Located in header (top right)
- Type document title to search
- Case-insensitive matching
- Real-time filtering

**What's Searched:**
- Document titles only
- Does not search document content or notes

**Search Behavior:**
- Filters documents across all categories
- Shows only documents matching search term
- Clear search to see all documents again

**Limitations:**
- No advanced search (by creator, date, category)
- No content search
- No saved searches

## View Modes

### Grid View (Default)

**Card Layout:**
- Larger document cards
- Shows title, creator, dates
- Category grouping
- Hover overlay with actions

**Best For:**
- Visual browsing
- Quick identification by title
- Seeing metadata at a glance

### List View

**Table Layout:**
- Compact rows
- Document title + creator + dates in columns
- More documents visible per screen
- Action buttons inline

**Best For:**
- Many documents
- Quick scanning by name
- Finding specific documents in long lists

**Toggle View:**
- Click view mode toggle button in header
- Switch between Grid and List
- Preference saves to user settings

## Document Areas

Document areas allow creating separate document repositories with independent permissions.

### What Are Document Areas?

**Concept:**
- Separate document spaces within your authority
- Each area has its own categories and documents
- Independent permission settings per area
- Examples: HR Documents, Operations Manual, Training Materials

**Area Properties:**
- Name (e.g., "HR Documents")
- Icon (material design icon code)
- Description
- Route/identifier for accessing
- Authority-specific

### Accessing Different Areas

**Method 1: Direct Route**
- Each area may have its own route
- Navigate to area-specific document view

**Method 2: Area Selector**
- If implemented, switch between areas via dropdown

**Method 3: Desktop Icons**
- Separate desktop icons for each document area

## Permission System

### Area-Level Permissions

**Three Permission Types:**

**1. Read Permission** (`READ_DOCUMENT`)
- View document areas list
- Browse categories and documents
- Read document content
- Cannot create or edit

**2. Write Permission** (`WRITE_DOCUMENT`)
- All READ permissions
- Create new documents
- Edit existing documents
- Create and rename categories
- Sort documents and categories

**3. Delete Permission** (`DELETE_DOCUMENT`)
- Delete documents
- Delete categories (with all documents inside)

**Permission Configuration:**
- Set per document area
- Role-based assignment
- Configured by administrators in DocumentAreasView

### How Permissions Work

**Area Access:**
- User must have role assigned to area
- Permission levels determine available actions
- No create/edit/delete buttons shown without permission

**Permission Inheritance:**
- Area permissions apply to all documents within
- No per-document permissions (all documents inherit area permissions)

## Multi-Tenant Security

**Authority Isolation:**
- All documents scoped to `authority_id`
- Users only see documents from their organization
- Cross-organization access prevented
- Document areas specific to each authority

## Document Data Model

**Document Fields (Database):**
- `id` - Unique document ID
- `category_id` - Category assignment
- `title` - Document title
- `content` - Rich-text HTML content
- `notes` - Internal notes
- `sort_order` - Sort position within category
- `creator` - User ID who created document
- `is_deleted` - Soft delete flag
- `authority_id` - Organization/tenant ID
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp
- `updated_at_user` - User who last updated

**Category Fields:**
- `id` - Category ID
- `name` - Category name
- `parent_id` - Parent category (hierarchical, if used)
- `sort_order` - Sort position
- `area_id` - Document area assignment
- `authority_id` - Organization ID

**Document Area Fields:**
- `id` - Area ID
- `name` - Area name
- `icon` - Material design icon
- `description` - Area description
- `authority_id` - Organization ID

## Admin: Document Areas

Administrators can create and manage document areas via the admin interface.

### Create Document Area

**Access:** Admin → Documents → Document Areas

1. Click **Create Area** button
2. Fill in details:
   - Name (required)
   - Icon (material design icon code, e.g., `mdi-folder`)
   - Description
3. Click **Create**
4. New area appears in list

### Configure Area Permissions

1. Find document area in admin list
2. Click **Permissions** button
3. Select roles to grant access
4. Set permission level for each role:
   - Read only
   - Read + Write
   - Read + Write + Delete
5. Click **Save Permissions**
6. Permissions applied immediately

**Permission Assignment:**
- Multiple roles can have different permission levels
- Users with assigned roles gain area access
- No role = no access to that area

### Edit Document Area

1. Click **Edit** button on area
2. Modify name, icon, or description
3. Click **Save**
4. Changes applied

### Delete Document Area

**Warning:** Deleting an area deletes ALL categories and documents within it.

1. Click **Delete** button
2. Confirm deletion
3. Area, categories, and documents removed (soft-deleted)

## Limitations

**What's NOT Available:**

### Document Features
- ❌ Version control/history
- ❌ Document comparison
- ❌ File attachments (use File Manager separately)
- ❌ Document sharing (internal users or external links)
- ❌ Document templates
- ❌ Document status (Draft/Published/Archived)
- ❌ Document approval workflows
- ❌ Comments/discussions on documents
- ❌ Document tags/labels
- ❌ Document expiry/effective dates
- ❌ Document numbering system
- ❌ Export to PDF/Word

### Organization Features
- ❌ Folder hierarchy (only flat categories per area)
- ❌ Move documents between areas
- ❌ Copy documents
- ❌ Bulk operations (multi-select, bulk edit/delete)
- ❌ Document linking/cross-references

### Search Features
- ❌ Content search (only title search)
- ❌ Advanced search (by date, creator, category)
- ❌ Saved searches
- ❌ Search filters

### Collaboration Features
- ❌ Real-time collaboration
- ❌ Document locking (edit protection)
- ❌ Document subscriptions/notifications
- ❌ Activity feeds
- ❌ User mentions

### Analytics Features
- ❌ Document view counts
- ❌ Popular documents
- ❌ Storage usage statistics
- ❌ Activity reports

### Advanced Features
- ❌ Custom metadata fields
- ❌ Area export/import configuration
- ❌ Global/cross-authority documents
- ❌ Document retention policies
- ❌ Audit trail (only basic timestamps)

## Tips & Best Practices

**Organizing Documents:**
- Create clear, descriptive category names
- Use consistent naming conventions for documents
- Keep category count manageable (< 20 per area)
- Use document notes for internal context

**Creating Content:**
- Write clear, concise document titles
- Use headings in content for structure
- Keep documents focused (one topic per document)
- Use rich-text formatting for readability

**Managing Areas:**
- Create separate areas for different departments
- Set appropriate permissions per role
- Use descriptive area names and icons
- Regularly review and clean up outdated documents

**Performance:**
- Avoid extremely long documents (split into multiple)
- Use simple formatting (avoid excessive images in content)
- Keep category structures flat
- Regular cleanup of unused documents

## Troubleshooting

### Can't Create Document

**Problem:** Create button missing or disabled

**Solutions:**
1. Verify you have `WRITE_DOCUMENT` permission
2. Check area-level write permission
3. Verify document area is accessible
4. Refresh page
5. Check browser console for errors

### Can't Edit Document

**Problem:** Save button doesn't work

**Solutions:**
1. Check you have write permission for this area
2. Verify title and category are filled
3. Check internet connection
4. Look for error messages in toast notifications
5. Try refreshing and editing again

### Documents Not Loading

**Problem:** Categories empty or documents missing

**Solutions:**
1. Verify you have read permission for the area
2. Check area ID in route is correct
3. Clear search filter (if active)
4. Refresh page
5. Check browser console for API errors

### Search Not Working

**Problem:** Search doesn't filter documents

**Solutions:**
1. Check spelling of document title
2. Try partial title
3. Clear search and try again
4. Refresh page to reload documents
5. Verify documents exist in database

### Sorting Not Saving

**Problem:** Drag-and-drop order doesn't persist

**Solutions:**
1. Check you have write permission
2. Verify you clicked "Save Order" button
3. Check internet connection during save
4. Look for error messages
5. Try manual sort via sort dialog

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [File Manager](/guide/file-manager) - File storage and management
- [Reports](/guide/reports) - Report creation and management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
