# File Manager

File browsing and storage system for organizing files in folders with upload, download, and basic file management.

## Overview

Features:
- Folder navigation with breadcrumb
- File upload (multi-file support)
- Folder creation (nested folders)
- File download
- File search by name
- Grid and list view modes
- Image preview
- File deletion

**Required Permission:** `READ_FILEMANAGER` (view), `WRITE_FILEMANAGER` (upload/create), `DELETE_FILEMANAGER` (delete)

## Accessing File Manager

**Route:** `/filemanager`

**How to Access:**
- Desktop: Double-click **File Manager** icon
- Menu: Start Menu → Files → File Manager
- Or navigate directly to `/filemanager`

## Interface Layout

### Header
- Title with folder icon
- Search field (top right)

### Action Bar
- **Back button** - Return to parent folder (when inside a folder)
- **Breadcrumb navigation** - Root → Folder1 → Folder2... (click segments to navigate)
- **Create Folder button** - Create new subfolder (if you have `canEdit` permission)
- **Upload File Input** - Select files to upload
- **Upload button** - Upload selected files (if you have `canEdit` permission)

### Content Area

**Folders Section:**
- Grid of folder cards
- Folder icon and name
- Click folder to navigate into it
- Hover to see actions

**Files Section:**
- View mode toggle (Grid/List)
- **Grid View**: Card-based layout with previews
- **List View**: Compact table-like rows

### File Cards (Grid View)
- File type icon or image preview
- File name
- File size (formatted as B, KB, MB, GB)
- File type badge with color coding
- Hover overlay with action buttons:
  - Download (opens in new tab)
  - Copy Link (copy URL to clipboard)
  - Delete (if you have `canDelete` permission)

## Creating Folders

### New Folder

1. Click **Create Folder** button (folder+ icon)
2. Dialog opens
3. Enter folder name
4. Click **Create**
5. New folder appears in current location

**Nested Folders:**
- Navigate into a folder
- Create new folder
- Creates subfolder with parent-child relationship

**Note**: Folders cannot be renamed or deleted from the UI after creation.

## Uploading Files

### Upload Files

1. Click file input field or **Upload** button
2. File picker dialog opens
3. Select one or more files
4. Click **Open**
5. Files upload to current folder
6. Toast notification confirms upload

**Multi-File Upload:**
- Select multiple files in file picker (Ctrl+Click or Shift+Click)
- All selected files upload to current location

### Supported File Types

**Allowed by Frontend:**
- Images: jpg, jpeg, png, gif, bmp, svg
- Documents: pdf, doc, docx, xls, xlsx, ppt, pptx
- Design: psd (Photoshop)

**Additional Types (Backend):**
- Text: txt
- Archives: zip, rar
- Videos: mp4, mov, avi, wmv

**File Size Limit:** 50MB per file (backend limit)

**Upload Location:**
- Files upload to currently open folder
- If at root level, files go to root folder

## File Operations

### Download File

**Method 1:**
- Hover over file card
- Click **Download** button (download icon)
- File opens in new browser tab
- Use browser's download option to save

**Method 2:**
- Right-click file name
- Select "Save link as..." from browser menu

### Copy Link

1. Hover over file card
2. Click **Copy Link** button (link icon)
3. File URL copied to clipboard
4. Paste URL to share directly

**URL Format:**
```
https://your-domain.com/uploads/{authority}/filemanager/{filename}
```

### Delete File

1. Hover over file card
2. Click **Delete** button (trash icon)
3. Confirm deletion in dialog
4. File soft-deleted (archived)

**Note**: Deletion is permanent from user perspective. Only administrators can recover deleted files from database.

**Permission Required:** `DELETE_FILEMANAGER`

## Navigation

### Folder Navigation

**Enter Folder:**
- Click folder card anywhere
- Opens folder, shows its contents

**Go Back:**
- Click **Back** button (arrow icon)
- Returns to parent folder
- Disabled at root level

**Breadcrumb Navigation:**
- Click any segment in breadcrumb path
- Jumps directly to that level
- Example: Root → Documents → Reports
  - Click "Documents" to go to Documents folder
  - Click "Root" to return to top level

**Current Folder Display:**
- Breadcrumb shows full path
- Current folder name highlighted
- Helps orient where you are in structure

## Search Files

### Search Functionality

**Search Bar:**
- Located in header (top right)
- Type filename to search
- Case-insensitive matching
- Real-time filtering (searches as you type)

**What's Searched:**
- File names
- Folder names

**Search Behavior:**
- Filters both files and folders
- Shows only items matching search term
- Clear search to see all items again

**Note**: Search only works by name. Cannot search by file type, size, date, or content.

## View Modes

### Grid View (Default)

**Card Layout:**
- Larger file/folder cards
- Image previews for photos
- Icon-based preview for other files
- File type badges
- Hover overlay with actions

**Best For:**
- Browsing images
- Visual file identification
- Fewer files per screen

### List View

**Compact Layout:**
- Table-like rows
- File icon + name + size
- Action buttons inline
- More files visible per screen

**Best For:**
- Many files
- Quick scanning by name
- Finding files in long lists

**Toggle View:**
- Click view mode toggle button in files section
- Switch between Grid and List
- Setting persists during session

## File Previews

### Image Preview

**Supported Formats:**
- JPG, JPEG, PNG, GIF, BMP

**Preview Display:**
- Thumbnail shown in grid view
- Click Download to view full size in new tab

**Note**: No in-app image viewer. Full preview requires downloading or opening in new tab.

### File Type Icons

**Icon-Based Preview (Non-Images):**
- **PDF** - Red badge with PDF icon
- **Word** (doc, docx) - Blue badge with Word icon
- **Excel** (xls, xlsx) - Green badge with Excel icon
- **PowerPoint** (ppt, pptx) - PowerPoint icon
- **Images** (jpg, png, etc.) - Purple badge with image icon
- **Text** (txt) - Document icon
- **Archives** (zip, rar, 7z) - Zip icon
- **Unknown** - Generic file icon

**Color Coding:**
- Red = PDF documents
- Blue = Word documents
- Green = Excel spreadsheets
- Purple = Image files

## Permission System

### View Permission

**Permission:** `READ_FILEMANAGER`

Can:
- Browse folders
- View file list
- Search files
- See file details (name, size, type)

Cannot:
- Upload files
- Create folders
- Delete files

### Upload/Create Permission

**Permission:** `WRITE_FILEMANAGER`

Can:
- All READ permissions
- Upload files
- Create folders

Cannot:
- Delete files

### Delete Permission

**Permission:** `DELETE_FILEMANAGER`

Can:
- Delete files (soft delete)

**Note**: Only file owner or users with this permission can delete files.

### Permission Props

The component supports permission control via props:
- `canEdit` - Upload and create folders
- `canDelete` - Delete files
- `canCreate` - Create folders
- `allPermissions` - Bypass all restrictions (admin)

## Multi-Tenant Security

**Authority Isolation:**
- All files scoped to `authority_id`
- Users only see files from their organization
- Files stored in authority-specific folder: `/uploads/{authority}/filemanager/`
- Cross-organization access prevented

## File Storage

**Storage Location:**
- Server path: `../../uploads/{authority}/filemanager/`
- Public URL: `https://your-domain.com/uploads/{authority}/filemanager/`

**File Organization:**
- Flat file storage (all files in same directory)
- Folder structure maintained in database
- Files retain original names

**File Size:**
- Individual files: Max 50MB
- No total storage quota enforced

## Tips & Best Practices

**Organizing Files:**
- Create folders for different categories (Reports, Images, Documents)
- Use descriptive folder names
- Navigate using breadcrumbs for quick access
- Use search to find files by name

**Uploading Files:**
- Upload multiple files at once for efficiency
- Check file size before upload (50MB limit)
- Upload to correct folder before clicking upload
- Use supported file types for best compatibility

**Managing Files:**
- Use Copy Link to share files directly
- Delete unnecessary files to save space
- Create subfolders to organize within folders
- Use grid view for images, list view for documents

## Limitations

**What's NOT Available:**
- File sharing with users
- Version control/history
- File/folder renaming (after creation)
- Folder deletion
- File/folder moving
- Drag-and-drop upload
- Bulk operations (multi-select)
- File tags or categories
- Storage usage display
- Trash/recovery system
- File metadata display (upload date, uploader)
- Sorting options (by name, date, size)
- In-app file preview/viewer
- Context menu (right-click)
- Keyboard shortcuts

**Backend Support (Not in UI):**
- Folder deletion (API exists, not exposed)
- Folder renaming (API exists, not exposed)
- File moving (API exists, not exposed)
- Folder moving (API exists, not exposed)

## Troubleshooting

### Upload Fails

**Problem**: File won't upload

**Solutions:**
1. Check file size (max 50MB)
2. Verify file type is supported
3. Check you have `WRITE_FILEMANAGER` permission
4. Ensure file input shows selected files
5. Try smaller files first
6. Check internet connection

### Can't Create Folder

**Problem**: Create folder button disabled or fails

**Solutions:**
1. Verify you have `WRITE_FILEMANAGER` permission
2. Check feature enabled for your authority
3. Try different folder name
4. Refresh page

### File Not Downloading

**Problem**: Download button doesn't work

**Solutions:**
1. Check browser allows popups from this site
2. Try right-click → "Save link as"
3. Check file still exists (not deleted)
4. Verify internet connection
5. Try different browser

### Can't Delete File

**Problem**: Delete button missing or fails

**Solutions:**
1. Verify you have `DELETE_FILEMANAGER` permission
2. Check you're the file owner
3. Refresh page
4. Contact administrator

### Search Not Working

**Problem**: Search doesn't find files

**Solutions:**
1. Check spelling of filename
2. Try partial filename
3. Search is case-insensitive, try different casing
4. Clear search and browse manually
5. Refresh page to reload file list

## Related Documentation

- [Getting Started](/guide/getting-started) - Learn the basics of K-Systems
- [Documents](/guide/documents) - Document management system
- [Person Files](/guide/person-files) - Person-specific files

---

**Last Updated:** 2025-10-01
**Version:** 2.0.0 (Corrected to match actual implementation)
