# Person Files

Person file management for storing contact information, personal data, and identification details.

## Overview

Person Files allows you to maintain records of individuals with contact information, identification, and personal details.

**Features:**
- Comprehensive person information
- Search across multiple fields
- CRUD operations
- Wanted status flag
- Authority-specific data isolation

**Required Permission:** `READ_PERSON_FILE` (view), `WRITE_PERSON_FILE` (create/edit), `DELETE_PERSON_FILE` (delete)

## Access

**Desktop:** Double-click **Person Files** icon
**Menu:** Start Menu → Files → Person Files
**Route:** `/person`

## Person List

### Table View

**Table Columns:**
- **Name**: Full name (clickable to view details)
- **Phone Number**: Contact phone
- **Email**: Email address
- **Actions**: View, Edit, Delete buttons

**Search:**
- Real-time search as you type
- Searches across: name, phone, email, firstname, lastname, address, ID card

**Action Button:**
- **+ Add Person**: Create new person file

## Creating Person Files

### Add New Person

1. Click **+ Add Person** button
2. Authority-specific dialog opens
3. Fill person information (fields vary by authority)
4. Click **Save**
5. Person added to list

**Common Fields:**
- **Name**: Full name or last name
- **First Name**: Given name
- **Last Name**: Family name
- **Gender**: M/F/Other
- **Title**: Mr., Ms., Dr., etc.
- **Birthplace**: Place of birth
- **Birthday**: Date of birth
- **Phone Number**: Contact phone
- **Address**: Full address
- **ID Card**: Identification card number
- **Bank Account**: Bank account details
- **Email**: Email address
- **Entry**: Entry/registration notes
- **Licenses**: License information
- **Wanted**: Flag as wanted (checkbox)
- **Text**: Additional notes/description

**Note:** Exact fields depend on authority-specific configuration.

## Viewing Person Files

### View Person Details

1. Click on person **name** in list
2. View dialog opens
3. Shows all person information
4. Close dialog when done

**View Dialog:**
- Displays all filled fields
- Read-only view
- Authority-specific layout

## Editing Person Files

### Edit Person

1. Find person in list
2. Click **Edit** button (pencil icon)
3. Edit dialog opens with current data
4. Modify any fields
5. Click **Save**
6. Changes applied immediately

**All fields editable** except system fields (ID, created_at, etc.).

## Deleting Person Files

### Delete Person

1. Find person in list
2. Click **Delete** button (trash icon)
3. Confirm deletion
4. Person soft-deleted (is_deleted flag set)

**Required Permission:** `DELETE_PERSON_FILE`

**Note:** Deleted persons are hidden but remain in database.

## Search and Filter

### Search Persons

**Search Behavior:**
- Search bar at top of list
- Real-time filtering as you type
- Case-insensitive matching
- Searches multiple fields simultaneously:
  - Name
  - First name
  - Last name
  - Phone number
  - Email
  - Address
  - ID card number

**Search Tips:**
- Type partial name for broader results
- Phone number search finds exact matches
- Email search is case-insensitive
- Clear search to show all persons

## Wanted Status

### Mark as Wanted

When creating or editing a person:
1. Check **Wanted** checkbox
2. Save person
3. Wanted flag stored

**Use Case:** Flag individuals for attention or tracking.

## Data Fields

### Standard Fields

**Personal Information:**
- `id` - Unique identifier
- `name` - Full name or last name
- `firstname` - First/given name
- `lastname` - Last/family name
- `fullname` - Computed full name
- `gender` - Gender
- `title` - Name title/prefix
- `birthplace` - Place of birth
- `birthday` - Date of birth (YYYY-MM-DD)

**Contact Information:**
- `phonenumber` - Phone number
- `address` - Full address
- `mail` - Email address

**Identification:**
- `idcard` - ID card number
- `bankaccount` - Bank account details
- `licenses` - License information

**Additional:**
- `entry` - Entry/registration notes
- `text` - Additional notes
- `wanted` - Wanted status flag (boolean)

**System Fields:**
- `authority_id` - Organization ID
- `is_deleted` - Soft delete flag
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Custom Fields:**
- System preserves any additional fields from database
- Custom fields maintained but may not display in UI

## Desktop Window Support

**Window Properties:**
- Can open person file in desktop window
- Deep linking: `/person?id={id}` opens specific person
- Window title shows person name
- Close window returns to desktop

## Multi-Tenant Security

**Authority Isolation:**
- All person files filtered by `authority_id`
- Users only see persons from their organization
- No cross-organization access
- Automatic filtering in all queries

## API Reference

### Endpoints

```http
GET /personfile/?action=getPersons
Returns: Array of person files for current authority

POST /personfile/?action=deletePerson
Body: { id }
Returns: Success message
```

**Note:** Add/Edit operations handled by authority-specific components.

## TypeScript Interface

```typescript
interface PersonFile {
  id: number;
  name: string;
  fullname: string;
  firstname: string;
  lastname: string;
  gender: string;
  title: string;
  birthplace: string;
  birthday: string;
  phonenumber: string;
  address: string;
  idcard: string;
  bankaccount: string;
  mail: string;
  entry: string;
  licenses: string;
  wanted: boolean;
  text: string;
  is_deleted: boolean;
  authority_id: number;
  [key: string]: any;  // Supports custom fields
}
```

## Best Practices

**Do:**
✅ Fill in all available fields for complete records
✅ Use consistent name formatting
✅ Verify email addresses
✅ Keep phone numbers up to date
✅ Use search to find persons quickly
✅ Update wanted status as needed
✅ Use address field for full address

**Don't:**
❌ Create duplicate entries
❌ Leave name fields empty
❌ Use inconsistent date formats
❌ Delete persons unnecessarily (they're archived, not removed)
❌ Share sensitive ID card information

## Troubleshooting

### Can't Create Person

**Solutions:**
1. Verify you have `WRITE_PERSON_FILE` permission
2. Fill all required fields (name at minimum)
3. Check browser console for errors
4. Refresh page and try again

### Person Not Showing in List

**Solutions:**
1. Clear search filter
2. Check person wasn't deleted
3. Refresh page (F5)
4. Verify same authority selected

### Search Not Finding Person

**Solutions:**
1. Try different search terms
2. Search by phone or email instead
3. Use partial name
4. Clear and retype search
5. Verify person exists in database

### Edit Not Saving

**Solutions:**
1. Check `WRITE_PERSON_FILE` permission
2. Verify all required fields filled
3. Check internet connection
4. Look for error messages
5. Try refreshing and editing again

## Limitations

The following features mentioned in documentation **do not exist** in the current codebase:

❌ **Not Available:**
- Inline editing in table
- Advanced filtering (by wanted status, date range, etc.)
- Bulk operations (select multiple, bulk delete/edit)
- Import from CSV/Excel
- Export to Excel/PDF
- Photo/avatar upload
- Document attachments
- Activity timeline/history log
- Notes tab
- Medical information section
- Emergency contacts
- Multiple phone/email addresses
- Address validation
- Duplicate detection
- Merge persons functionality
- Field-level permissions
- Audit trail
- GDPR features (data export request, right to deletion)
- File number system (auto-generated)
- Status workflow (Active/Inactive/Archived/Deceased)
- Age auto-calculation
- Demographics reporting
- Custom field configuration in UI

---

## Next Steps

- [Vehicle Files](/guide/vehicle-files) - Manage vehicle records
- [Apartment Files](/guide/apartment-files) - Manage apartment records
- [Companies](/guide/companies) - Company contacts
- [Employee Management](/guide/employee-management) - Employee records
