# Workflow: Creating a Report

This guide walks you through the complete process of creating a report in K-Systems from start to finish.

---

## Prerequisites

**Required Permissions:**
- `READ_REPORT` - To access the Reports module
- `WRITE_REPORT` - To create new reports

**What You'll Need:**
- Information about the incident/event to report
- Any supporting documents or photos (optional)
- Knowledge of which report category to use

**Time Required:** 5-15 minutes (depending on complexity)

---

## Step-by-Step Process

### Step 1: Navigate to Reports Module

![Navigate to Reports](../../public/images/workflows/workflow-report-step1.png)

1. **Open K-Systems** and log in
2. **In the sidebar**, find and click **"Reports"**
   - Or use search: Press `Ctrl+K` and type "Reports"
3. You'll see the **Reports list view**

---

### Step 2: Start Creating New Report

![Click New Report](../../public/images/workflows/workflow-report-step2.png)

1. **Click the "+ New Report"** button
   - Usually in top-right corner or as a floating action button
2. The **report creation form** will open

---

### Step 3: Select Report Category

![Select Category](../../public/images/workflows/workflow-report-step3.png)

1. **Choose a report category** from the dropdown:
   - **Incident Report** - For emergency incidents
   - **Training Report** - For training sessions
   - **Inspection Report** - For equipment/building inspections
   - **Maintenance Report** - For maintenance activities
   - **Other categories** - As configured by your organization

2. **Category determines:**
   - Which fields appear in the form
   - Required vs optional information
   - Who can view the report
   - Report numbering/coding

::: tip Choosing the Right Category
If unsure which category to use, ask a supervisor or check your organization's reporting guidelines. Using the correct category ensures proper processing and archiving.
:::

---

### Step 4: Fill in Report Details

![Fill in Form](../../public/images/workflows/workflow-report-step4.png)

#### A. Basic Information

**Required fields (marked with *):**

1. **Report Title***
   - Brief, descriptive title (e.g., "House Fire - Main Street 123")
   - Keep it concise but informative

2. **Date and Time***
   - When did the event occur?
   - Use date picker and time selector
   - Be as accurate as possible

3. **Location***
   - Where did the event take place?
   - Include:
     - Street address
     - Building/floor if applicable
     - GPS coordinates (if available)

4. **Description***
   - Detailed account of what happened
   - Use the rich text editor to format text:
     - **Bold** for important points
     - *Italic* for emphasis
     - Bullet lists for sequences
     - Numbered lists for procedures

#### B. People Involved

5. **Reporter** (usually auto-filled with your name)

6. **Assigned Employees**
   - Select team members who were involved
   - Click "+ Add Employee" to add more
   - You can remove with the X button

7. **Companies/Departments**
   - Select which companies/departments responded
   - May have multiple selections

#### C. Additional Details

8. **Priority/Severity** (if available)
   - Low, Medium, High, Critical
   - Based on incident severity

9. **Status**
   - **Open** - Report in progress
   - **In Progress** - Being worked on
   - **Completed** - Finalized
   - **Closed** - Archived

10. **Report Code** (if applicable)
    - May auto-generate based on category
    - Or select from predefined codes

#### D. Custom Fields

11. **Authority-Specific Fields**
    - Your organization may have custom fields
    - Examples: "Weather conditions", "Equipment used", "Damage estimate"
    - Fill in as applicable

---

### Step 5: Add Attachments (Optional)

![Add Attachments](../../public/images/report-attachments.png)

1. **Click "Attach Files"** or "Add Attachment"

2. **Select files** from your computer:
   - **Photos** - Incident scene, damage, equipment
   - **Documents** - Related paperwork, sketches
   - **Videos** - If supported and relevant

3. **Supported formats:**
   - Images: JPG, PNG, GIF
   - Documents: PDF, DOC, DOCX, XLS, XLSX
   - Maximum size: Usually 64MB per file

4. **Add descriptions** to attachments:
   - Click attachment to add caption
   - Helps others understand what the file shows

::: warning File Size Limits
If your files are too large, compress them first or upload to a document management system and link them instead.
:::

---

### Step 6: Set Sharing/Permissions (If Available)

![Set Permissions](../../public/images/report-permissions.png)

1. **Share with other authorities** (optional):
   - If enabled, you can share report with partner organizations
   - Select which authorities should see this report

2. **Set visibility:**
   - Private (only assigned users)
   - Department (entire department)
   - Organization (whole organization)

---

### Step 7: Review Your Report

**Before saving, check:**

- ✅ All required fields filled in (marked with *)
- ✅ Title is clear and descriptive
- ✅ Date/time are accurate
- ✅ Description is complete and detailed
- ✅ Correct employees assigned
- ✅ Relevant attachments included
- ✅ Status is appropriate
- ✅ No typos or errors

::: tip Use Draft Status
If you're not ready to finalize, save as "Draft" or "Open" status. You can edit it later.
:::

---

### Step 8: Save the Report

![Save Report](../../public/images/workflows/workflow-report-step8.png)

1. **Click "Save"** or **"Submit"** button
   - Button may say different things based on configuration
   - Usually at bottom or top-right of form

2. **Wait for confirmation:**
   - You should see a success message: "Report created successfully"
   - If you see an error, check for validation messages and fix issues

3. **Note the report ID/number:**
   - System assigns a unique identifier
   - Use this for reference

---

### Step 9: View Your Report

![View Report](../../public/images/report-detail.png)

1. After saving, you'll typically see the **report detail view**

2. **Verify everything looks correct:**
   - All information displayed properly
   - Attachments visible and openable
   - Assigned employees shown

3. **If changes needed:**
   - Click **"Edit"** button
   - Make corrections
   - Save again

---

## Post-Creation Actions

### Export to PDF

![Export PDF](../../public/images/report-export-pdf.png)

1. **Click "Export to PDF"** or **"Download PDF"**
2. PDF generates with all report information
3. **Save** to your computer or **Print**
4. **Use for:**
   - Hard copy filing
   - Email to external parties
   - Archive purposes

### Share the Report

1. **Copy report link:**
   - Click "Share" or copy URL
   - Send to colleagues who need to review

2. **Add to related documents:**
   - Link report in document management system
   - Cross-reference with other reports

### Update Report Status

As work progresses:

1. **Open the report**
2. **Click "Edit"**
3. **Change status:**
   - Open → In Progress → Completed → Closed
4. **Add notes** about what changed
5. **Save**

### Add Follow-Up Information

1. **Edit the report** to add:
   - Investigation findings
   - Resolution details
   - Lessons learned
   - Corrective actions taken

2. **Attach additional files** as they become available

---

## Common Scenarios

### Scenario 1: Emergency Incident Report

**Steps:**
1. Create report **immediately** or as soon as safe
2. Use **Incident Report** category
3. Set **priority** based on severity
4. Include:
   - Exact time of alarm
   - Response time
   - Units deployed
   - Actions taken
   - Outcome
5. Attach photos of scene, equipment, damage
6. Assign all responding personnel
7. Save as "In Progress" initially
8. Update with final information later

### Scenario 2: Training Session Report

**Steps:**
1. Create report **after training**
2. Use **Training Report** category
3. Include:
   - Training topic/type
   - Duration
   - Location
   - Instructor(s)
   - Attendees
   - Skills practiced
   - Equipment used
4. Attach:
   - Training materials
   - Photos of exercises
   - Certificates (if issued)
5. Save as "Completed"

### Scenario 3: Multi-Day Incident

**Steps:**
1. Create initial report on **Day 1**
2. Save as **"In Progress"**
3. Each day:
   - **Edit report**
   - Add updates to description
   - Upload new photos
   - Update status
4. When incident concludes:
   - Add final summary
   - Change status to **"Completed"**
   - Export PDF for records

---

## Tips & Best Practices

### Writing Effective Reports

**Be Specific:**
- ✅ "House fire at 123 Main St, fully involved on arrival, 2 engines responded"
- ❌ "Fire somewhere, we went there"

**Use Clear Language:**
- Avoid jargon unless necessary
- Write for someone unfamiliar with the incident
- Use complete sentences

**Include Timeline:**
- Alarm received: 14:32
- First unit on scene: 14:38
- Fire under control: 15:15
- Units cleared: 16:00

**Document Actions:**
- What was done
- Who did it
- Why it was done
- Outcome

### Organizing Information

**Use Structure:**
```
1. Incident Overview
   - What happened
   - When and where

2. Response
   - Units deployed
   - Personnel assigned

3. Actions Taken
   - Initial assessment
   - Tactical decisions
   - Operations performed

4. Outcome
   - Results
   - Condition after incident

5. Lessons Learned / Notes
   - What went well
   - What could improve
```

### Attachment Best Practices

**Photos:**
- Take multiple angles
- Include wide shots and close-ups
- Ensure photos are clear and well-lit
- Add captions explaining what's shown

**Documents:**
- Use clear file names: "Fire_Main_St_FloorPlan.pdf"
- Organize by type (photos, diagrams, forms)
- Keep file sizes reasonable

---

## Troubleshooting

### Problem: Can't Create Report

**Possible causes:**
- Missing `WRITE_REPORT` permission
- Report feature not enabled for your account

**Solution:** Contact administrator for access.

### Problem: Required Fields Not Clear

**Solution:**
- Look for red asterisks (*) next to field names
- Error messages will highlight missing fields when you try to save

### Problem: Can't Upload Attachments

**Possible causes:**
- File too large (>64MB)
- Unsupported file type
- Network connection issue

**Solutions:**
- Compress large files
- Convert to supported format
- Check internet connection
- Try again

### Problem: Report Not Saving

**Check:**
- All required fields filled?
- Validation errors shown?
- Internet connection stable?

**Try:**
- Fix any errors
- Refresh page
- Try saving again

### Problem: Can't Find My Report

**Solutions:**
- Check filters (may be hiding your report)
- Use search function
- Check if it was saved (check email confirmation if enabled)
- Contact administrator

---

## Related Guides

- **[Reports Module Guide](/guide/reports)** - Detailed feature documentation
- **[Documents Guide](/guide/documents)** - Managing attachments
- **[Employee Management](/guide/employee-management)** - Assigning personnel

---

## Quick Reference

**Creating a Report:**
1. Reports → + New Report
2. Select category
3. Fill required fields
4. Add details and attachments
5. Review
6. Save

**Required Permissions:**
- READ_REPORT (to access)
- WRITE_REPORT (to create)

**Key Fields:**
- Title*
- Date/Time*
- Location*
- Description*
- Status

**After Creation:**
- Export to PDF
- Share with colleagues
- Update status as needed
- Add follow-up information

---

**Need Help?** See the [FAQ](/guide/faq) or [Troubleshooting Guide](/guide/troubleshooting).

**Last Updated:** 2025-10-27
