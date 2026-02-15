# Workflow: Document Management

Managing documents in K-Systems from upload to organization and sharing.

---

## Prerequisites

**Required Permissions:**
- `READ_DOCUMENT` - Access documents
- `WRITE_DOCUMENT` - Create/edit documents in allowed areas

**Time Required:** 5-10 minutes per document

---

## Step-by-Step Process

### Step 1: Navigate to Documents

![Navigate to Documents](../../public/images/workflows/workflow-document-step1.png)

1. Open sidebar
2. Click **"Documents"**
3. Document management interface loads

### Step 2: Select Document Area

![Select Document Area](../../public/images/workflows/workflow-document-step2.png)

**Available Areas:**
- **Training** - Training materials
- **Department** - Department-specific documents
- **Administration** - Administrative documents
- **Authority Exchange** - Cross-organization documents
- **Custom Areas** - Organization-specific areas

**Choose the appropriate area** for your document based on its purpose and audience.

### Step 3: Create or Upload Document

![Upload or Create](../../public/images/workflows/workflow-document-step3.png)

**Option A: Upload Existing File**
1. Click **"Upload"** or **"+ New Document"**
2. Click **"Choose File"** or drag-and-drop
3. Select file from computer
4. Wait for upload to complete

**Option B: Create New Document**
1. Click **"+ New Document"** or **"Create"**
2. Select **"New Document"**
3. Rich text editor opens
4. Write content using formatting tools

**Supported File Types:**
- PDF, DOC, DOCX
- XLS, XLSX
- TXT, RTF
- Images: JPG, PNG

### Step 4: Set Document Properties

![Set Properties](../../public/images/document-properties.png)

**Required Information:**
- **Title** - Clear, descriptive name
- **Description** - Brief summary of contents (optional but recommended)

**Optional Metadata:**
- **Tags** - Keywords for searching
- **Category** - Subcategory within area
- **Version** - Version number if applicable
- **Effective Date** - When document becomes valid
- **Expiration Date** - When document should be reviewed/replaced

### Step 5: Configure Permissions

![Set Permissions](../../public/images/workflows/workflow-document-step4.png)

**Document Access Control:**

**Visibility Levels:**
- **Private** - Only you can see
- **Department** - Your department only
- **Organization** - Entire organization
- **Public** - All authorities (if in Exchange area)

**Fine-Grained Permissions:**
Set specific permissions for roles or users:
- **Read** - Can view document
- **Write** - Can edit document
- **Delete** - Can delete document

**Example:**
- Administrators: Read, Write, Delete
- Department Members: Read, Write
- General Staff: Read only

### Step 6: Review and Save

**Before saving, verify:**
- ✅ Correct document area
- ✅ Clear title
- ✅ Appropriate permissions set
- ✅ Tags added for searchability
- ✅ No errors in upload/content

**Save Options:**
- **Save** - Document is immediately visible (based on permissions)
- **Save as Draft** - Save for later completion (if available)

---

## Post-Upload Actions

### Organizing Documents

**Create Folder Structure:** (if supported)
1. Right-click in document area
2. Select "New Folder" or "New Category"
3. Name the folder
4. Drag documents into folders

**Tagging Documents:**
1. Open document
2. Click "Edit" or "Properties"
3. Add relevant tags (e.g., "policy", "training-2024", "equipment")
4. Save

### Sharing Documents

**Internal Sharing:**
- Send link to colleagues via messages
- Reference document ID in reports
- Add to relevant project folders

**External Sharing:** (if permission allows)
- Generate public link with expiration
- Download and email
- Export to PDF for printing

### Version Control

**Updating Documents:**
1. Open existing document
2. Click **"Edit"** or **"Upload New Version"**
3. Make changes or upload new file
4. System tracks version history
5. Save with version notes

**Viewing History:**
- Click "Version History" or "Revisions"
- See who changed what and when
- Restore previous version if needed

---

## Common Scenarios

### Scenario 1: Uploading Training Materials

**Workflow:**
1. Navigate to **Training** document area
2. Create folder: "CPR Training 2024"
3. Upload documents:
   - Training slides (PDF)
   - Handouts (PDF)
   - Certificates template (DOCX)
4. Set permissions: All employees Read access
5. Tag documents: "training", "cpr", "2024"
6. Save

**Share with trainees:**
- Send document area link
- Or individual document links

### Scenario 2: Policy Document

**Workflow:**
1. Navigate to **Administration** area
2. Create new document titled "Social Media Policy"
3. Write or paste policy text
4. Format with headings and lists
5. Set permissions:
   - All staff: Read
   - Administrators: Read, Write, Delete
6. Add tags: "policy", "social-media", "hr"
7. Set effective date: Start of policy
8. Save and notify staff via announcements

### Scenario 3: Cross-Department Collaboration

**Workflow:**
1. Create document in **Department** area
2. Share with specific users from other departments
3. Grant Write permissions to collaborators
4. Set up notifications for changes
5. Team members edit document
6. Review changes in version history
7. When finalized, change permissions to Read-only

---

## Best Practices

### Naming Conventions

**Clear Titles:**
- ✅ "Fire Safety Inspection Checklist 2024"
- ✅ "Equipment Maintenance Log - Engine 1"
- ❌ "Document1" or "New Doc"

**Use Dates:**
- Include year or month for time-sensitive documents
- Format: YYYY-MM-DD or "Month Year"

**Be Descriptive:**
- Title should explain what the document is
- Anyone should understand without opening it

### Organization Tips

**Folder Structure:**
```
Training/
  ├── CPR/
  ├── HAZMAT/
  ├── Equipment Operation/
  └── Safety Protocols/

Administration/
  ├── Policies/
  ├── Procedures/
  ├── Forms/
  └── Meeting Minutes/
```

**Tagging Strategy:**
- Use consistent tags across organization
- Create tag categories: Type, Year, Topic, Department
- Example tags: "policy", "2024", "training", "fire-safety"

### Permission Management

**Principle of Least Privilege:**
- Give minimum necessary access
- Most staff need Read-only for policies
- Reserve Write access for document owners
- Limit Delete to administrators

**Review Regularly:**
- Quarterly review who has access
- Remove permissions when staff changes roles
- Update permissions for new hires

---

## Troubleshooting

**Problem: Upload Fails**
- File too large (check limit, usually 64MB)
- Unsupported file type
- Network issue
- Insufficient storage space

**Problem: Can't Find Document**
- Check filters (may be hiding document)
- Search by title or tags
- Verify you're in correct document area
- Check if document was actually saved

**Problem: Permission Denied**
- You don't have access to that document area
- Document owner restricted access
- Contact administrator for access

**Problem: Can't Edit Document**
- Document locked by another user
- You have Read-only permission
- Document archived or finalized
- Request Write permission from owner

---

## Tips for Efficiency

**Batch Upload:**
- Upload multiple files at once
- Select all files in file dialog
- Set common properties for all

**Use Templates:**
- Create document templates for common types
- Copy template and fill in specific details
- Saves time and ensures consistency

**Search Effectively:**
- Use specific keywords
- Combine multiple search terms
- Use filters (date, area, tags)
- Sort by relevance or date

**Set Reminders:**
- For documents with expiration dates
- Review annually for policy documents
- Update training materials seasonally

---

## Related Guides

- **[Documents Module Guide](/guide/documents)** - Full feature documentation
- **[Search Guide](/guide/search)** - Advanced search techniques
- **[Permissions Overview](/guide/admin/permissions)** - Understanding permissions

---

## Quick Reference

**Upload Document:**
1. Documents → Select Area
2. Click Upload/New Document
3. Choose file or create new
4. Set title and properties
5. Configure permissions
6. Save

**Organize:**
- Create folders/categories
- Add tags
- Move to appropriate area

**Share:**
- Set read permissions
- Send document link
- Or download and email

**Required Permissions:**
- READ_DOCUMENT (access)
- WRITE_DOCUMENT (upload/edit)

---

**Last Updated:** 2025-10-27
