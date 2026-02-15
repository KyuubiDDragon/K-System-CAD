# Workflow: Employee Onboarding

Complete process for adding a new employee to K-Systems.

---

## Prerequisites

**Required Permissions:**
- `WRITE_EMPLOYEE` - Create employee records
- `ADMIN_READ_USERS` - Create user account (admin only)
- `WRITE_TRAINING` - Assign initial training (if applicable)

**What You'll Need:**
- Employee personal information
- Department/company assignment
- Rank/position
- Contact details
- Initial training requirements

**Time Required:** 15-30 minutes

---

## Complete Onboarding Process

### Step 1: Create User Account

**This step requires administrator permissions.**

![Create User Account](../../public/images/admin/admin-user-create.png)

1. Navigate to **Admin > User Management**
2. Click **"+ New User"**
3. Enter credentials:
   - **Username** - Unique username (e.g., firstname.lastname)
   - **Password** - Temporary password (user will change on first login)
   - **Email** - Work email address
4. Assign **initial role** (e.g., "Member" or "Employee")
5. Enable required **features**:
   - employee, calendar, documents, training, etc.
6. Click **"Create"**

**Note the username and temporary password** to provide to new employee.

### Step 2: Create Employee Record

![Create Employee](../../public/images/employee/employee-create.png)

1. Navigate to **Employees**
2. Click **"+ New Employee"**
3. Fill in basic information:
   - **First Name** and **Last Name**
   - **Employee Number** (if used)
   - **Date of Birth**
   - **Date of Hire**

4. Assign organizational structure:
   - **Company/Department**
   - **Rank/Position**
   - **Primary Station** (if applicable)

5. Contact information:
   - **Phone Number**
   - **Email Address**
   - **Emergency Contact**

6. Additional details:
   - **Address**
   - **ID/Personnel Number**
   - **Blood Type** (if relevant)

7. Upload **photo** (optional but recommended)

8. Click **"Save"**

### Step 3: Configure Permissions

**For administrators:**

![Assign Permissions](../../public/images/admin/admin-user-permissions.png)

1. Return to **Admin > User Management**
2. Find newly created user
3. Click **"Edit Permissions"** or **"Assign Roles"**
4. Add permissions based on position:
   - READ_EMPLOYEE (view employees)
   - READ_CALENDAR (view calendar)
   - READ_DOCUMENT (access documents)
   - WRITE_REPORT (create reports) - if needed
5. Assign to additional **roles** if needed
6. Save changes

### Step 4: Assign Initial Training

If training management is used:

![Assign Training](../../public/images/training/training-assign.png)

1. Navigate to **Training**
2. Click **"Assign Training"** or **"+ New Assignment"**
3. Select **new employee**
4. Select required training courses:
   - Orientation/Onboarding
   - Safety Training
   - Equipment Training
   - Position-specific training
5. Set **due dates**
6. Add **notes** (e.g., "New hire - complete within 30 days")
7. Save assignments

### Step 5: Add to Calendar/Schedule

If using scheduling:

1. Navigate to **Calendar**
2. Add employee to **calendar groups** (shifts, teams)
3. Create initial **schedule entries**
4. Set **availability** preferences

### Step 6: Grant Document Access

1. Navigate to **Documents > Admin** (or relevant area)
2. Create or update permissions for:
   - Employee Handbook
   - Safety Procedures
   - Department SOPs
3. Ensure new employee has **Read** access to essential documents

### Step 7: Communication Setup

**Email/Messages:**
1. Add to **distribution lists** (All Staff, Department, etc.)
2. Send **welcome message** with:
   - Login instructions
   - Temporary password
   - Important links
   - First-day information

**Example Welcome Message:**
```
Welcome to [Organization]!

Your K-Systems account has been created:
- Username: firstname.lastname
- Temporary Password: [provided separately]
- URL: https://your-k-systems-url.com

Please log in and change your password immediately.

Assigned Training (due within 30 days):
- Orientation
- Safety Procedures
- Equipment Familiarization

Questions? Contact [Supervisor Name] or IT Support.

Welcome to the team!
```

### Step 8: Physical/Equipment Setup

**Outside K-Systems (but part of onboarding):**
- Uniform/PPE issuance
- Equipment assignment (radio, gear, etc.)
- Building access (keys, cards)
- Locker assignment
- Parking permit

**Document in K-Systems:**
- Create **equipment assignment record**
- Note in employee file
- Add to **asset tracking** (if available)

---

## Post-Onboarding Tasks

### Week 1 Follow-Up

**Check:**
- ✅ Employee logged in successfully
- ✅ Password changed
- ✅ Can access required modules
- ✅ Training started
- ✅ No access issues reported

### Month 1 Follow-Up

**Review:**
- Training progress
- System usage (are they using features?)
- Permission adequacy (need more access?)
- Feedback on system

**Update as needed:**
- Adjust permissions
- Assign additional training
- Update employee record (photo, certifications, etc.)

---

## Quick Onboarding Checklist

**Pre-Onboarding:**
- [ ] Collect employee information
- [ ] Determine role and permissions needed
- [ ] Identify required training

**Account Creation:**
- [ ] Create user account (username, password, email)
- [ ] Assign initial role
- [ ] Enable required features

**Employee Profile:**
- [ ] Create employee record
- [ ] Assign to department/company
- [ ] Add contact information
- [ ] Upload photo

**Permissions:**
- [ ] Configure user permissions
- [ ] Assign to appropriate roles
- [ ] Test access to modules

**Training:**
- [ ] Assign orientation/onboarding training
- [ ] Assign position-specific training
- [ ] Set due dates

**Communication:**
- [ ] Send welcome message with login info
- [ ] Add to distribution lists
- [ ] Provide system documentation

**Documentation Access:**
- [ ] Grant access to employee handbook
- [ ] Ensure access to relevant policies
- [ ] Share training materials

**Follow-Up:**
- [ ] Confirm successful first login
- [ ] Check training progress after 1 week
- [ ] Review access and permissions after 1 month

---

## Common Issues

**Problem: Employee Can't Log In**
- Verify username is correct (no typos)
- Check password was provided correctly
- Ensure account is active
- Check authority is correct

**Problem: Missing Permissions**
- Review assigned roles
- Check feature flags enabled
- Add specific permissions as needed
- May need to log out and back in to refresh

**Problem: Can't See Modules**
- Features not enabled for user
- Insufficient permissions
- Role doesn't include module access

---

## Tips for Smooth Onboarding

**Prepare in Advance:**
- Create account 1-2 days before start date
- Have training materials ready
- Prepare welcome message template

**Use Consistent Naming:**
- Username format: firstname.lastname
- Email format: matches username
- Employee numbers if applicable

**Document Everything:**
- Keep checklist for each new hire
- Note any deviations or issues
- Track time to complete onboarding

**Provide Resources:**
- Link to user documentation
- List of key contacts
- FAQ for new users
- Quick start guide

---

## Related Guides

- **[Employee Management](/guide/employee-management)** - Managing employee records
- **[User Management](/guide/admin/users)** - Creating user accounts
- **[Permissions Guide](/guide/admin/permissions)** - Understanding permissions
- **[Training Management](/guide/training)** - Assigning training

---

## Quick Reference

**Onboarding Steps:**
1. Create user account (Admin)
2. Create employee record
3. Configure permissions
4. Assign initial training
5. Grant document access
6. Send welcome message
7. Follow up after 1 week

**Required Permissions:**
- WRITE_EMPLOYEE (employee record)
- ADMIN_READ_USERS (user account - admin only)

---

**Last Updated:** 2025-10-27
