# Company Management

Company management system for tracking companies and clients with fire protection inspection monitoring.

## Overview

Features:
- Basic company CRUD operations
- Company type classification
- Parent company relationships
- Fire protection inspection tracking
- Search and filtering
- Authority-specific data isolation

**Required Permission:** `READ_COMPANY` (view), `WRITE_COMPANY` (create/edit), `DELETE_COMPANY` (delete)

## Accessing Companies

**Route:** `/company`

**How to Access:**
- Desktop: Double-click **Companies** icon
- Menu: Start Menu → Companies
- Or navigate directly to `/company`

## Interface Layout

### Company List Table

**Columns:**
- Company Name
- Street Address
- City
- Postal Code
- Phone
- Email
- Last Fire Protection Inspection
- Fire Protection Inspection Valid Until
- Extinguisher Count
- Actions (Edit, Delete)

**Toolbar:**
- Search field (filters by name, address, city, postal code, phone, email)
- **New Company** button

**Pagination:**
- Built-in table pagination
- Client-side filtering

## Creating Companies

### New Company

1. Click **New Company** button
2. Form dialog opens
3. Fill in fields:

**Basic Information:**
- **Company Name*** (required)
- **Company Type**: Customer, Supplier, Partner, Other
- **Parent Company**: Select from existing companies (creates hierarchy)

**Contact Information:**
- Street Address
- Postal Code
- City
- Country
- Phone
- Email
- Website

**Business Details:**
- Tax ID Number
- Company Registration Number
- **Contract Available** (checkbox)

**Fire Protection Inspection:**
- Last Fire Protection Inspection (date)
- Fire Protection Inspection Valid Until (date)
- Extinguisher Count (number)

4. Click **Save**
5. Company appears in list

## Editing Companies

### Edit Company

1. Click **Edit** icon on company row
2. Form dialog opens with current data
3. Modify any fields
4. Click **Save**
5. Changes applied immediately

**Authority Validation:**
- Backend ensures company belongs to user's authority
- Prevents cross-tenant modifications

## Deleting Companies

### Delete Company

1. Click **Delete** icon on company row
2. Confirm deletion in dialog
3. Company permanently deleted

**Warning:** Deletion is permanent. May fail if company has dependent records in other modules.

**Permission Required:** `DELETE_COMPANY`

## Fire Protection Inspection Tracking

The system includes specific fields for fire safety compliance:

**Fields:**
1. **Last Fire Protection Inspection** - Date of last inspection
2. **Fire Protection Inspection Valid Until** - Expiration date
3. **Extinguisher Count** - Number of fire extinguishers at location

**Use Case:**
- Track compliance with fire safety regulations
- Monitor inspection expiration dates
- Maintain equipment counts for auditing

**Display:**
- All three fields appear as columns in company table
- Visible at a glance for compliance monitoring

## Company Hierarchies

**Parent Company Feature:**
- Companies can be linked to a parent company
- Creates hierarchical relationship (subsidiaries)
- Parent company selector shows searchable dropdown
- Useful for managing corporate structures

**Limitations:**
- No visual hierarchy tree view
- No cascading operations
- No reporting on hierarchies

## Search and Filter

### Search Functionality

**Search Bar:**
- Located in toolbar
- Real-time client-side filtering
- Case-insensitive matching

**Searchable Fields:**
- Company name
- Street address
- City
- Postal code
- Phone
- Email

**Behavior:**
- Filters as you type
- Shows matching companies
- Clear search to see all

## Data Model

**Company Record Fields:**
- `id` - Unique identifier
- `authority_id` - Multi-tenant isolation
- `name` - Company name (required)
- `company_type` - Type: customer, supplier, partner, other
- `parent_company_id` - Parent company reference
- `street`, `postal_code`, `city`, `country` - Address
- `phone`, `email`, `website` - Contact info
- `tax_id` - Tax identification
- `registration_number` - Company registration
- `contract_available` - Contract status flag
- `last_fire_protection_inspection` - Inspection date
- `fire_protection_inspection_valid_until` - Expiry date
- `extinguisher_count` - Equipment count
- `created_at`, `updated_at` - Timestamps

## Multi-Tenant Security

**Authority Isolation:**
- All companies scoped to `authority_id`
- Users only see companies in their organization
- Cross-organization access prevented
- Parent company dropdown filtered by authority

## Limitations

**What's NOT Available:**

### Contact Management
- ❌ Dedicated contacts/persons per company
- ❌ Multiple contact persons
- ❌ Contact roles or departments
- ❌ Phone/email stored at company level only (no contact-specific)

### Document Management
- ❌ Document uploads or attachments
- ❌ Contract storage
- ❌ Certificate uploads
- ❌ Fire protection certificates
- ❌ Document version control

### Notes & History
- ❌ Notes or comments section
- ❌ Interaction history logging
- ❌ Activity timeline
- ❌ Communication tracking

### Advanced Features
- ❌ Advanced company relationships (beyond parent company)
- ❌ Tags or custom fields
- ❌ Import/export functionality
- ❌ Bulk operations
- ❌ Advanced filtering (by type, inspection status, etc.)
- ❌ Reporting or analytics
- ❌ Company status workflow (active/inactive/archived)
- ❌ Duplicate detection
- ❌ Automatic inspection expiration alerts

### Integration Features
- ❌ Link to invoices from other modules
- ❌ Link to documents
- ❌ Project associations
- ❌ Employee assignments to companies

### Search Limitations
- ❌ Client-side search only (doesn't scale well)
- ❌ No saved searches
- ❌ No sorting by fire protection dates
- ❌ No server-side pagination

**Current Reality:**
This is a **basic company directory** with fire protection inspection tracking. It is not a comprehensive CRM or company management system.

## Use Cases

**Company Directory:**
- Maintain list of customers, suppliers, partners
- Store basic contact information
- Track company types

**Fire Safety Compliance:**
- Monitor fire protection inspections
- Track inspection expiration dates
- Maintain extinguisher counts
- Ensure regulatory compliance

**Simple Hierarchy:**
- Track parent/subsidiary relationships
- Basic corporate structure

## Tips & Best Practices

**Data Entry:**
1. Always fill company name (required)
2. Set company type for organization
3. Use parent company for subsidiaries
4. Keep fire protection dates current

**Fire Protection Compliance:**
1. Set "Valid Until" date when recording inspections
2. Update extinguisher count during inspections
3. Regularly review companies with approaching expiration
4. **Note:** No automatic alerts - requires manual review

**Searching:**
1. Use specific terms for faster results
2. Search works across multiple fields
3. Clear search to view full list

**Hierarchy Management:**
1. Set parent company when creating subsidiaries
2. Parent dropdown only shows companies in same authority
3. No visual tree - track manually

## Troubleshooting

### Can't Create Company

**Problem:** New Company button doesn't work

**Solutions:**
1. Verify `WRITE_COMPANY` permission
2. Check company name is filled (required)
3. Validate email format if provided
4. Check browser console for errors

### Can't Edit Company

**Problem:** Edit not saving

**Solutions:**
1. Check `WRITE_COMPANY` permission
2. Verify company name not empty
3. Check internet connection
4. Ensure company belongs to your authority

### Can't Delete Company

**Problem:** Delete fails

**Solutions:**
1. Check `DELETE_COMPANY` permission
2. Company may have dependent records in other modules
3. Verify company belongs to your authority
4. Check for error message

### Search Not Working

**Problem:** No results

**Solutions:**
1. Check search term matches name, street, city, postal code, phone, or email
2. Search is case-insensitive but must match substring
3. Verify companies exist
4. Clear search and try again

### Fire Protection Dates Not Saving

**Problem:** Dates don't persist

**Solutions:**
1. Check dates are valid format
2. Verify browser console for errors
3. Check backend accepts date fields
4. Ensure proper date picker selection

## Related Documentation

- [Getting Started](/guide/getting-started) - K-Systems basics
- [Person Files](/guide/person-files) - Person contacts
- [Invoices](/guide/invoices) - Invoice management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
