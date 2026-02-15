# Fire Protection Certificate Generator

Simple certificate generator for creating fire protection compliance certificates with A4 preview and image export.

## Overview

Features:
- Generate fire protection certificates
- Three certificate types (Company, Authority, Event)
- A4 page preview with header/footer graphics
- Form-based data entry
- HTML to image conversion (html2canvas)
- Image upload to server
- Shareable link generation
- HTML tag and direct link copying
- Last 5 certificates table
- Email template generation
- Signature image support
- Authority-specific isolation

**Required Permission:** `READ_FIREPROTECTION` (view), `WRITE_FIREPROTECTION` (generate)

## Accessing Fire Protection

**Desktop:** Double-click **Fire Protection** icon
**Menu:** Start Menu → Operations → Fire Protection
**Route:** `/fireprotection`

## Interface Layout

### Left Column - Certificate Form

**Form Fields:**

**Type Selection:**
- Dropdown with 3 options:
  - **Unternehmen** (Company) - Default
  - **Behörde** (Authority)
  - **Event**
- Changes label and validation logic

**Issued On:**
- Date field
- Defaults to today
- Format: YYYY-MM-DD (input), DD.MM.YYYY (display)
- Used for validity calculations

**Performed From:**
- Text field
- Default: "Fire Department"
- Who conducted the inspection

**Creator:**
- Text field (required)
- Auto-filled from `authStore.user.username`
- Person creating certificate

**Office:**
- Text field (required)
- Default: "Fire Protection Bureau"
- Issuing office/department

**Signature URL:**
- Text field (optional)
- Auto-filled from `authStore.user.signature`
- URL to signature image
- Shows placeholder if not provided

**Object Name:**
- Text field (required)
- Name of company/authority/event
- Used in certificate title and filename

**Location:**
- Text field (required)
- Address or location of object

**Report Number:**
- Text field (required)
- Certificate/report reference number
- Format: Free text

**Event-Specific Fields** (only if Type = "Event"):
- **Valid From**: Date field (required)
- **Valid To**: Date field (required)
- Both dates shown in certificate

**Action Button:**
- **Generate & Upload** button
- Primary color, block width
- Icon: file-image-plus-outline
- Disabled if form invalid
- Loading state during generation

**Generated Link Section** (shows after generation):
- File name display
- HTML tag text field with copy button
- Direct link text field with copy button
- **Download Image** button
- **Copy Mail Template** button

### Last Certificates Table

**Table Columns:**
- **File Name** - Certificate filename
- **Link** - Open button (external link)

**Features:**
- Shows last 5 uploaded certificates
- Compact density
- 5 items per page
- Loading state
- Empty state with icon
- Opens links in new tab

### Right Column - Certificate Preview

**A4 Page Preview:**
- 210mm x 297mm dimensions
- Beige background (#fff9ed)
- PT Serif font family
- Live preview of certificate

**Preview Sections:**

**Header:**
- "Los Santos Fire Department" title (35px)
- Header image (pdf_top.png)
- Subtitle: "Ausgestellt durch das Los Santos Fire Department" (11px)

**Watermark:**
- Logo image (logo.png)
- Centered, full-page overlay
- 8% opacity
- 500px width
- Non-interactive

**Content Table:**
- Two-column layout (label: value)
- Bold font weight
- 16px font size
- Fields:
  - Ausgestellt am (Issued on)
  - Durchgeführt von (Performed from)
  - Objekt/Event (Object name)
  - Standort (Location)
  - Gutachten-Nr (Report number)
  - Gültig bis / Gültig von-bis (Validity)

**Main Text:**
- Centered alignment
- Pre-defined compliance statements:
  - "Brandschutzmaßnahmen wurden vorgenommen und bestätigt."
  - "Keine Mängel festgestellt."
  - **"Der Brandschutz ist gewährleistet."** (bold, highlighted)
- Validity text (11px, fine print)
- Update requirements text (11px, fine print)

**Signature Section:**
- "Mit freundlichen Grüßen"
- Creator name (bold)
- Office name
- Signature image (54px height) or placeholder

**Footer:**
- Footer image (pdf_bottom.png)
- "Brandschutzdienststelle des LSFD" (15px)

## Certificate Types

### Company Certificate (Unternehmen)

**Validity:**
- Calculated: Issued date + 12 months
- Display: "Gültig bis DD.MM.YYYY"

**Update Text:**
- "Dieses Gutachten ist bei baulichen Veränderungen oder Veränderungen an der Nutzung zu aktualisieren."

**Use Case:**
- Regular business inspections
- Annual renewals
- Standard validity period

### Authority Certificate (Behörde)

**Validity:**
- Same as Company (Issued + 12 months)
- Display: "Gültig bis DD.MM.YYYY"

**Update Text:**
- Same as Company

**Use Case:**
- Government building inspections
- Public facilities
- Authority-owned properties

### Event Certificate (Event)

**Validity:**
- User-specified date range
- Display: "Gültig von DD.MM.YYYY bis DD.MM.YYYY"
- Both dates required

**Update Text:**
- "Dieses Gutachten ist nur für den genannten Zeitraum gültig."

**Use Case:**
- Temporary events
- Festivals/concerts
- Short-term installations
- Time-limited operations

## Certificate Generation Process

### Step-by-Step

1. **Fill Form**
   - Enter all required fields
   - Select certificate type
   - Add signature URL if needed
   - Form validates in real-time

2. **Click Generate & Upload**
   - Form validation check
   - Loading state activates

3. **Image Generation**
   - html2canvas captures A4 preview element
   - Scale: 2x for quality
   - Format: PNG, 100% quality
   - CORS enabled for signature image

4. **Upload to Server**
   - FormData with image blob
   - Additional fields: type, year, dates, all form data
   - Filename: `ObjectName_DDMMYYYY_timestamp.png`
   - Spaces replaced with underscores

5. **Server Response**
   - Returns shareable link
   - Returns filename
   - Status: success/error

6. **Display Results**
   - Shareable link section shows
   - HTML tag generated
   - Direct link available
   - Last certificates table refreshes

### Filename Format

```
ObjectName_DDMMYYYY_timestamp.png

Example:
Muster_GmbH_02102025_1696248372645.png
```

## Using Generated Certificate

### HTML Tag

**Generated Format:**
```html
<img alt="" src="https://domain.com/path/certificate.png" style="height:849px; width:600px" />
```

**Copy to Clipboard:**
- Click copy icon in HTML tag field
- Use in websites, emails, documents
- Maintains aspect ratio

### Direct Link

**Format:**
```
https://domain.com/uploads/fireprotection/YYYY/certificate.png
```

**Use Cases:**
- Share via email
- Embed in documents
- Reference in reports
- Archive/documentation

### Download Image

**Process:**
1. Click **Download Image** button
2. Fetches image from shareable link
3. Creates blob URL
4. Triggers browser download
5. Filename from server response

**Downloaded File:**
- PNG format
- Full resolution (A4 at scale 2)
- Ready for printing/sharing

### Email Template

**Generated Template:**
```
Subject: Brandschutzbestätigung für [Object Name]

Body:
Sehr geehrte Damen und Herren,

anbei übersende ich Ihnen die Brandschutzbestätigung für [Object Name].

Link zur Bestätigung: [Shareable Link]

Mit freundlichen Grüßen
[Creator Name]
[Office]
```

**Copy to Clipboard:**
- Click **Copy Mail Template** button
- Opens default email client with pre-filled content
- Uses `mailto:` protocol

## Data Model

**Form Data:**
```typescript
{
  issuedOn: string;           // YYYY-MM-DD
  performedFrom: string;      // "Fire Department"
  objectFireProtection: string; // Object name (required)
  location: string;           // Address (required)
  creator: string;            // Creator name (required)
  office: string;             // Office name (required)
  signatureUrl: string;       // Signature image URL
  reportNumber: string;       // Report number (required)
  selectedType: 'Unternehmen' | 'Behörde' | 'Event';
  validFrom?: string;         // Only for Event
  validTo?: string;           // Only for Event
}
```

**Uploaded File:**
```typescript
interface UploadedFile {
  name: string; // Filename
  link: string; // Full URL to certificate
}
```

## API Integration

**Base Path:** `/fireprotection/`

**Actions:**

**Get Last Files:**
```
POST /fireprotection/?action=getLastFiles
Response: { data: UploadedFile[] }
```

**Submit Document:**
```
POST /fireprotection/?action=submitDocument
Content-Type: multipart/form-data
Payload:
  - image: Blob (PNG file)
  - isEvent: string ('true'/'false')
  - year: string (YYYY)
  - issuedOn: string (YYYY-MM-DD)
  - performedFrom: string
  - objectFireProtection: string
  - location: string
  - creator: string
  - office: string
  - reportNumber: string
  - selectedType: string
  - validFrom: string (optional, if Event)
  - validTo: string (optional, if Event)

Response: {
  status: 'success',
  fileName: string,
  shareableLink: string,
  message?: string
}
```

**Error Handling:**
- Toast notifications for errors
- Console logging for debugging
- Loading states during operations

## Multi-Tenant Security

**Authority Isolation:**
- Uploaded files scoped to `authority_id`
- File storage in authority-specific folders
- Users only see certificates from their organization
- Cross-organization access prevented

## Styling & Theme

**Dark Theme Form:**
- Dark cards with elevation
- Outlined text fields
- Compact density
- Primary color accents

**A4 Preview:**
- Beige background (#fff9ed)
- Black text for print compatibility
- PT Serif font (professional appearance)
- Shadow and border for depth
- Responsive scaling on mobile

**Responsive:**
- Left column: 4 columns (form)
- Right column: 8 columns (preview)
- Mobile: Stacked layout
- Preview scales to 100% width on small screens

## Limitations

**What's NOT Available:**

### Inspection Management
- ❌ Fire safety inspections
- ❌ Inspection scheduling
- ❌ Inspector assignments
- ❌ Inspection checklists
- ❌ Inspection history
- ❌ Due/overdue tracking
- ❌ Follow-up scheduling
- ❌ Re-inspection management

### Property Database
- ❌ Property management
- ❌ Building information
- ❌ Occupancy tracking
- ❌ Construction type
- ❌ Square footage
- ❌ Number of stories
- ❌ Owner information
- ❌ Tenant information

### Fire Safety Systems
- ❌ Sprinkler system tracking
- ❌ Alarm system tracking
- ❌ Standpipe system
- ❌ Fire extinguisher locations
- ❌ Equipment inventory
- ❌ System testing logs
- ❌ Maintenance records

### Violation Tracking
- ❌ Violation codes
- ❌ Citation management
- ❌ Compliance tracking
- ❌ Court date tracking
- ❌ Enforcement workflow
- ❌ Violation history
- ❌ Fine tracking

### Code Compliance
- ❌ Building code enforcement
- ❌ Compliance verification
- ❌ Hazard identification
- ❌ Pre-incident planning
- ❌ Code violation database

### Reporting & Analytics
- ❌ Inspection reports
- ❌ Compliance statistics
- ❌ Violation trends
- ❌ Inspector performance
- ❌ Property compliance rate
- ❌ Dashboard metrics
- ❌ Export functionality

### Advanced Features
- ❌ Calendar view for inspections
- ❌ Map view for properties
- ❌ Inspector view/assignments
- ❌ Custom certificate templates
- ❌ Multiple certificate designs
- ❌ PDF generation (only PNG)
- ❌ Batch certificate generation
- ❌ Certificate revocation
- ❌ Certificate verification system

### UI Limitations
- ❌ No certificate editing after generation
- ❌ No certificate deletion from UI
- ❌ No search/filter for old certificates
- ❌ Only last 5 certificates shown
- ❌ No pagination for certificate history
- ❌ No certificate preview before generation
- ❌ No custom validity periods (except Event type)
- ❌ Fixed certificate text (no customization)

**Current Reality:**
This is a **simple certificate generator** that creates standardized fire protection compliance certificates with a professional layout. It is not a comprehensive fire inspection, code enforcement, or property management system.

## Use Cases

**Certificate Generation:**
- Issue fire protection compliance certificates
- Document completed inspections
- Provide proof of compliance
- Annual renewals for businesses

**Event Certifications:**
- Temporary event approvals
- Short-term compliance certificates
- Time-limited authorizations

**Documentation:**
- Archive compliance certificates
- Share certificates with stakeholders
- Email certificates to businesses
- Embed certificates in reports

## Best Practices

**Creating Certificates:**
1. Fill all required fields completely
2. Use consistent report numbering scheme
3. Add signature URL from profile for professionalism
4. Review preview before generating
5. Select correct certificate type

**Object Names:**
1. Use full business/event names
2. Be consistent with naming
3. Avoid special characters (replaced with underscores in filename)
4. Use proper capitalization

**Report Numbers:**
1. Establish numbering convention
2. Use sequential numbers
3. Include year or date in number
4. Keep records of issued numbers

**Event Certificates:**
1. Set realistic date ranges
2. Ensure dates cover entire event period
3. Issue close to event start date
4. Specify exact validity period

## Troubleshooting

### Can't Generate Certificate

**Problem:** Generate button disabled or doesn't work

**Solutions:**
1. Check all required fields filled
2. For Event type, ensure validFrom and validTo filled
3. Check form validation messages
4. Refresh page and try again

### Image Generation Fails

**Problem:** Error during image generation

**Solutions:**
1. Check signature URL is valid and accessible
2. Ensure browser supports html2canvas
3. Check browser console for CORS errors
4. Try without signature URL
5. Check internet connection for loading signature

### Upload Fails

**Problem:** Image generates but upload fails

**Solutions:**
1. Check internet connection
2. Verify backend is running
3. Check browser console for API errors
4. Check file size isn't too large
5. Verify you have `WRITE_FIREPROTECTION` permission

### Signature Not Showing

**Problem:** Signature image doesn't appear

**Solutions:**
1. Check signature URL is correct
2. Verify image is publicly accessible
3. Check for CORS issues
4. Try re-entering URL
5. Update signature in profile settings

### Last Certificates Not Loading

**Problem:** Certificate table empty or not loading

**Solutions:**
1. Check you have `READ_FIREPROTECTION` permission
2. Verify backend is running
3. Check browser console for API errors
4. Refresh page
5. Verify certificates exist for your authority

### Download Fails

**Problem:** Download button doesn't work

**Solutions:**
1. Check shareable link is valid
2. Verify internet connection
3. Check browser allows downloads
4. Try opening link directly first
5. Check file isn't blocked by firewall

## Related Documentation

- [📄 Documents](/guide/documents) - Document management
- [🏢 Companies](/guide/companies) - Company fire protection tracking
- [⚙️ Profile Settings](/guide/profile-settings) - Signature setup

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
