# KMC Robotics Club Website - Comprehensive User Manual

## Table of Contents
1. [Introduction](#introduction)
2. [Installation & Configuration](#installation--configuration)
3. [Getting Started](#getting-started)
4. [Public Interface](#public-interface)
5. [User Registration & Authentication](#user-registration--authentication)
6. [Member Portal](#member-portal)
7. [Administrator Portal](#administrator-portal)
8. [Features & Functionality](#features--functionality)
9. [Troubleshooting](#troubleshooting)
10. [Security & Best Practices](#security--best-practices)
11. [Frequently Asked Questions](#frequently-asked-questions)

---

## Introduction

### About KMC Robotics Club Website
The **Kathmandu Model College Robotics Club (KMC RC)** website is a comprehensive platform designed to serve as the digital headquarters for the club. It provides:

- **Public Information Portal**: Showcasing club activities, events, and achievements
- **Member Management System**: Registration, profiles, and member directories
- **Event Management**: Creating, promoting, and tracking event registrations
- **Gallery System**: Photo management and display of club activities
- **Communication Hub**: Internal messaging and announcements
- **Administrative Tools**: Complete backend management system

### System Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: Version 7.4 or higher (PHP 8.x recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge - latest versions)
- **Storage**: Minimum 500MB for application and uploads

### Key Features
✨ Responsive Design (Mobile, Tablet, Desktop)  
✨ Dark Mode Theme with Sci-Fi Aesthetic  
✨ User Role Management (Admin, Member, Guest)  
✨ Event Registration & Attendance Tracking  
✨ Gallery Management with Image Upload  
✨ Internal Messaging System  
✨ Secure Authentication & Password Recovery  
✨ Profile Management with Social Media Links  

---

## Installation & Configuration

### Step 1: Initial Setup

#### 1.1 Download & Deploy
1. Download the complete project files
2. Extract to your web server directory:
   - **XAMPP**: `C:\xampp\htdocs\KMC_Robotics_Club`
   - **WAMP**: `C:\wamp64\www\KMC_Robotics_Club`
   - **Linux**: `/var/www/html/KMC_Robotics_Club`

#### 1.2 File Permissions (Linux/Mac)
```bash
# Set proper permissions
chmod -R 755 KMC_Robotics_Club/
chmod -R 777 KMC_Robotics_Club/uploads/
chmod -R 755 KMC_Robotics_Club/config/
```

### Step 2: Database Installation

#### 2.1 Using the Installation Wizard (Recommended)
1. Open your browser and navigate to: `http://localhost/KMC_Robotics_Club/install.php`
2. **Step 1 - Prerequisites Check**: Review system requirements
3. **Step 2 - Database Configuration**:
   - Database Host: `localhost` (or your MySQL server)
   - Database Name: `kmc_robotics_club` (or custom name)
   - Database Username: `root` (default) or your MySQL user
   - Database Password: Your MySQL password (blank for default XAMPP)
   - Click "Test Connection" to verify
4. **Step 3 - Admin Account Creation**:
   - Full Name: Your name
   - Email: Your admin email
   - Password: Strong password (min. 8 characters)
   - Confirm Password
5. Click "Install" and wait for completion
6. **Important**: Delete `install.php` file after successful installation

#### 2.2 Manual Database Setup
If the wizard fails, manually create the database:
```sql
-- Create database
CREATE DATABASE kmc_robotics_club CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import schema
mysql -u root -p kmc_robotics_club < database/schema.sql
```

### Step 3: Configuration

#### 3.1 Configuration File
The installer creates `config/config.php`. Manual configuration:

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'kmc_robotics_club');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost/KMC_Robotics_Club');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
```

#### 3.2 Default Administrator Credentials
After installation, use these credentials to login:
- **Email**: `admin@kmcrc.edu.np`
- **Password**: `Admin@123`

**⚠️ IMPORTANT**: Change the default password immediately after first login!

---

## Getting Started

### Accessing the Website

#### Public Access
- Homepage: `http://localhost/KMC_Robotics_Club/`
- No login required to view public pages

#### Member Login
- Login Page: `http://localhost/KMC_Robotics_Club/auth/login.php`
- Use your registered email and password

#### Admin Access
- Same login page as members
- System recognizes admin role automatically
- Redirects to: `http://localhost/KMC_Robotics_Club/admin/dashboard.php`

---

## Public Interface

### 4.1 Homepage (`index.php`)

**Features**:
- Hero section with club tagline and call-to-action
- Quick statistics (Members, Events, Projects, Awards)
- Featured upcoming events (next 3 events)
- Latest gallery items (6 featured photos)
- Executive team showcase
- Newsletter subscription form

**Navigation**:
- Top navigation bar with links to all major sections
- "Join Us" button for new member registration
- "Login" button for existing members

### 4.2 About Page (`pages/about.php`)

**Content Sections**:
- **Our Story**: Club history and establishment
- **Mission & Vision**: Core values and goals
- **What We Do**: Areas of focus (Robotics, Programming, Competitions)
- **Achievements**: Awards and recognitions
- **Core Values**: Innovation, Collaboration, Learning, Excellence

**Interactive Elements**:
- Animated statistics counters
- Photo gallery integration
- Contact information

### 4.3 Events Page (`pages/events.php`)

**Viewing Events**:
- All published events displayed in grid layout
- Each card shows:
  - Event banner image
  - Title and short description
  - Date, time, and location
  - Event category badge
  - Registration status (if logged in)

**Filtering Options**:
- **By Period**: 
  - Upcoming Events (default)
  - Past Events
  - All Events
- **By Category**:
  - Workshops
  - Competitions
  - Seminars
  - Hackathons
  - Meetups
  - Other

**Search Functionality**:
- Search by event name
- Real-time filtering as you type

**Event Details**:
- Click "View Details" to see full information:
  - Complete description
  - Detailed schedule
  - Venue information
  - Registration deadline
  - Maximum participants
  - Registered attendees count
  - "Register" button (for logged-in members)

### 4.4 Gallery Page (`pages/gallery.php`)

**Gallery Display**:
- Masonry/Grid layout of approved photos
- Responsive image loading
- Lightbox view on click

**Filtering**:
- All Categories
- Projects
- Events
- Workshops
- Competitions
- Team Photos
- Other

**Image Information**:
- Photo title
- Caption/Description
- Category badge
- Upload date

### 4.5 Team Page (`pages/team.php`)

**Team Organization**:
- **Executive Board**: President, Vice President, Secretary
- **Technical Team**: Technical leads and specialists
- **Creative Team**: Design and content creators
- **Advisory Board**: Faculty advisors and mentors

**Member Cards Display**:
- Profile photo
- Name and role
- Bio/Description
- Social media links (LinkedIn, GitHub)
- Email contact (if public)

### 4.6 Join Us Page (`pages/join.php`)

**Membership Information**:
- Benefits of joining
- Membership requirements
- Application process
- Fee structure (if any)

**Quick Registration**:
- Link to registration form
- Contact information for queries

---

## User Registration & Authentication

### 5.1 Creating a New Account (`auth/register.php`)

**Step-by-Step Registration**:

1. Navigate to: `http://localhost/KMC_Robotics_Club/auth/register.php`
2. Fill out the registration form:

**Required Fields**:
- Full Name
- Email Address (must be unique)
- Password (minimum 8 characters)
- Confirm Password
- Phone Number
- Student ID (for KMC students)
- Department/Faculty
- Year of Study

**Optional Fields**:
- Profile Picture (upload)
- Bio/Introduction
- Skills (comma-separated)
- LinkedIn Profile URL
- GitHub Profile URL

3. Review Terms and Conditions
4. Click "Register"
5. **Account Status**: 
   - May be "Pending" and require admin approval
   - Or automatically "Active" based on settings

**Email Verification**:
- Check your email for verification link (if enabled)
- Click the link to verify your account

### 5.2 Logging In (`auth/login.php`)

**Login Process**:
1. Navigate to: `http://localhost/KMC_Robotics_Club/auth/login.php`
2. Enter your email address
3. Enter your password
4. (Optional) Check "Remember Me" for extended session
5. Click "Login"

**After Login**:
- **Members**: Redirected to Member Dashboard
- **Admins**: Redirected to Admin Dashboard

**Security Features**:
- Password encryption (bcrypt)
- Session management
- CSRF protection
- Failed login attempt tracking

### 5.3 Password Recovery (`auth/forgot-password.php`)

**Forgot Password**:
1. Click "Forgot Password?" on login page
2. Enter your registered email address
3. Click "Send Reset Link"
4. Check your email for password reset instructions
5. Click the reset link (valid for 1 hour)
6. Enter new password
7. Confirm new password
8. Click "Reset Password"

### 5.4 Logging Out

**To Logout**:
- Click your profile menu (top right)
- Select "Logout"
- Or navigate to: `auth/logout.php`
- Clears session and redirects to homepage

---

## Member Portal

### 6.1 Member Dashboard (`member/dashboard.php`)

**Dashboard Overview**:

**Quick Statistics**:
- Events Attended
- Events Registered
- Unread Messages
- Gallery Contributions

**Sections**:

#### Upcoming Events
- List of events you've registered for
- Event details and countdown
- Quick access to event pages
- Registration status badges

#### Recent Announcements
- Club news and updates
- System notifications
- Event reminders

#### Quick Actions
- Register for Events
- Update Profile
- Send Message
- View Gallery

**Navigation Menu**:
- Dashboard (Home)
- My Events
- Messages
- Profile
- Logout

### 6.2 Event Management (`member/events.php`)

**My Events Page**:

**Registered Events**:
- All events you've signed up for
- Categorized by status:
  - Upcoming (not yet happened)
  - Ongoing (happening now)
  - Completed (past events)
  - Cancelled

**Event Registration**:
1. Browse available events
2. Click "Register" on event card
3. Confirm registration
4. Receive confirmation notification

**Event Actions**:
- **View Details**: Full event information
- **Cancel Registration**: Unregister from event (before deadline)
- **Download Certificate**: For completed events (if available)

**Registration Status**:
- ✅ Registered
- ⏳ Attended (marked by admin)
- ❌ Cancelled

### 6.3 Messages (`member/messages.php`)

**Messaging System**:

#### Inbox
- All messages received
- Unread messages highlighted
- Sender name and profile picture
- Message preview
- Timestamp

**Composing Messages**:
1. Click "Compose New Message"
2. Select recipient:
   - Search by name
   - Select from active members list
   - Select admin/executives
3. Enter subject
4. Write message (supports basic formatting)
5. Click "Send"

**Message Actions**:
- **Read**: Open and view message
- **Reply**: Send response to sender
- **Delete**: Remove message from inbox
- **Archive**: Move to archive folder

**Message Features**:
- Thread view for conversations
- Unread count badge
- Read receipts
- Message search

#### Sent Messages
- All messages you've sent
- Delivery status
- Reply tracking

### 6.4 Profile Management (`member/profile.php`)

**Profile Sections**:

#### Personal Information
- Full Name
- Email Address (cannot be changed)
- Phone Number
- Student ID
- Department
- Year of Study

**Edit Fields**:
1. Click "Edit Profile"
2. Update desired fields
3. Click "Save Changes"

#### Profile Picture
- Current photo display
- **Upload New Photo**:
  1. Click "Change Photo"
  2. Select image file (JPG, PNG, GIF)
  3. Maximum size: 5MB
  4. Automatic resize and crop
  5. Click "Upload"

#### Bio & Skills
- **Bio**: Write about yourself (500 characters max)
- **Skills**: List your technical skills
  - Programming languages
  - Tools and frameworks
  - Robotics expertise
  - Other relevant skills

#### Social Media Links
- LinkedIn Profile URL
- GitHub Profile URL
- Portfolio Website (optional)

#### Account Security
**Change Password**:
1. Click "Change Password"
2. Enter current password
3. Enter new password (min. 8 chars)
4. Confirm new password
5. Click "Update Password"

**Password Requirements**:
- Minimum 8 characters
- At least one uppercase letter
- At least one lowercase letter
- At least one number
- At least one special character (recommended)

#### Activity Log
- Recent login history
- Profile changes
- Event registrations
- Gallery uploads

---

## Administrator Portal

### 7.1 Admin Dashboard (`admin/dashboard.php`)

**Dashboard Overview**:

**Key Statistics**:
- Total Members (Active/Pending/Inactive)
- Total Events (Upcoming/Ongoing/Completed)
- Gallery Items (Approved/Pending)
- Unread Messages
- New Registrations (Last 30 days)
- Event Attendance Rate

**Quick Actions**:
- Create New Event
- Approve Pending Users
- Upload Gallery Photos
- View Messages
- Manage Team Members

**Recent Activity Feed**:
- New member registrations
- Event registrations
- Gallery uploads
- System activities

**Upcoming Events Overview**:
- Next 5 events
- Registration counts
- Quick access to manage

**Charts & Analytics**:
- Member growth chart
- Event attendance trends
- Gallery upload statistics
- User engagement metrics

### 7.2 User Management (`admin/users.php`)

**User List**:
- Comprehensive table of all users
- Columns:
  - ID
  - Profile Picture
  - Name
  - Email
  - Role (Admin/Member)
  - Status (Active/Pending/Inactive)
  - Registration Date
  - Last Login
  - Actions

**Filtering Options**:
- By Role: All, Admins, Members
- By Status: All, Active, Pending, Inactive
- Search by name or email

**User Actions**:

#### Viewing User Details
1. Click user's name or "View" button
2. View complete profile:
   - Personal information
   - Registered events
   - Gallery contributions
   - Activity history

#### Approving Pending Users
1. Filter by Status: Pending
2. Review user information
3. Click "Approve" to activate account
4. Or click "Reject" to deny registration

#### Editing User Information
1. Click "Edit" on user row
2. Modify fields:
   - Name
   - Email
   - Phone
   - Department
   - Year of Study
3. Click "Save Changes"

#### Changing User Role
1. Click "Edit" on user row
2. Select Role dropdown:
   - Member
   - Admin
3. Click "Save"
4. **Warning**: Grants/revokes admin privileges

#### Deactivating/Activating Users
- **Deactivate**: Suspends user account (cannot login)
- **Activate**: Restores user access
- **Delete**: Permanently removes user (⚠️ irreversible)

#### Bulk Actions
1. Select multiple users (checkboxes)
2. Choose bulk action:
   - Activate Selected
   - Deactivate Selected
   - Delete Selected
   - Export to CSV
3. Click "Apply"

### 7.3 Event Management (`admin/events.php`)

**Events Overview**:
- All events in system
- Filter by status: All, Upcoming, Ongoing, Completed, Cancelled
- Search by title or description

**Creating a New Event**:

1. Click "Create New Event" button
2. Fill out event form:

**Basic Information**:
- Event Title (required)
- Short Description (200 chars)
- Full Description (rich text editor)
- Event Category:
  - Workshop
  - Competition
  - Seminar
  - Hackathon
  - Meetup
  - Other

**Schedule Details**:
- Event Date (calendar picker)
- Start Time
- End Time
- Registration Deadline

**Venue Information**:
- Location/Venue Name
- Address
- Room/Hall Number
- Capacity (Max Participants)

**Media**:
- Event Banner/Poster (upload)
- Image formats: JPG, PNG, GIF, WebP
- Recommended size: 1200x600px

**Settings**:
- Status: Upcoming, Ongoing, Completed, Cancelled
- Featured Event (checkbox) - appears on homepage
- Registration Required (checkbox)
- Allow Wait List (checkbox)

3. Click "Save Event" or "Save as Draft"

**Editing Events**:
1. Click "Edit" on event row
2. Modify any fields
3. Click "Update Event"

**Managing Event Registrations**:
1. Click "Registrations" on event row
2. View list of registered members:
   - Member name
   - Registration date
   - Attendance status
3. **Mark Attendance**:
   - Check "Attended" for members who participated
   - Bulk mark attendance
   - Export attendee list
4. **Send Notifications**:
   - Send reminders to registered members
   - Send event updates
   - Send cancellation notice

**Deleting Events**:
- Click "Delete" (⚠️ Warning appears)
- Confirm deletion
- Related registrations are also removed

### 7.4 Team Management (`admin/team.php`)

**Team Member List**:
- All team members displayed on public Team page
- Organized by category and position order

**Adding Team Member**:

1. Click "Add Team Member"
2. **Two Options**:
   - Link to existing user account
   - Create standalone team profile

**Form Fields**:
- Name (required)
- Role/Position (required)
  - President
  - Vice President
  - Secretary
  - Treasurer
  - Technical Lead
  - Creative Head
  - etc.
- Category (required):
  - Executive
  - Technical
  - Creative
  - Advisory
- Bio (500 chars)
- Photo Upload
- Email (optional)
- LinkedIn URL
- GitHub URL
- Year Joined
- Position Order (for sorting)
- Active Status (checkbox)

3. Click "Add Member"

**Editing Team Members**:
1. Click "Edit" on member row
2. Update information
3. Click "Save Changes"

**Reordering Team Members**:
- Drag and drop members to reorder
- Or use Position Order field (numeric)
- Lower numbers appear first

**Removing Team Members**:
- Click "Remove" to take off public page
- Or click "Deactivate" to temporarily hide

### 7.5 Gallery Management (`admin/gallery.php`)

**Gallery Overview**:
- All uploaded photos
- Filter by status: All, Approved, Pending, Rejected
- Filter by category
- Grid/List view toggle

**Uploading Photos**:

1. Click "Upload Photos"
2. **Single Upload**:
   - Select one image file
   - Fill out details
   - Click "Upload"
3. **Bulk Upload**:
   - Select multiple images
   - Apply same category/event to all
   - Or customize each individually
   - Click "Upload All"

**Photo Details Form**:
- Title (required)
- Description/Caption
- Category:
  - Projects
  - Events
  - Workshops
  - Competitions
  - Team
  - Other
- Link to Event (if applicable)
- Featured Photo (checkbox) - appears on homepage
- Tags (comma-separated)

**Approving/Rejecting Photos**:
- Review pending photos
- Click "Approve" to publish
- Click "Reject" to decline
- Add rejection reason (notifies uploader)

**Editing Gallery Items**:
1. Click "Edit" on photo
2. Update title, description, category
3. Change featured status
4. Click "Save"

**Deleting Photos**:
- Click "Delete" (⚠️ permanent action)
- Confirm deletion
- Photo file is removed from server

**Bulk Actions**:
- Select multiple photos
- Approve Selected
- Reject Selected
- Delete Selected
- Change Category
- Mark as Featured

### 7.6 Messages (`admin/messages.php`)

**Message Management**:

**Message Filters**:
- All Messages
- Inbox (messages to admin)
- Unread Messages
- Contact Form Submissions (from guests)
- Archived Messages
- Sent Messages

**Viewing Messages**:
1. Click message row to open
2. View full message content
3. See sender information
4. View message thread/history

**Replying to Messages**:
1. Open message
2. Click "Reply" button
3. Write your response
4. Click "Send Reply"
5. Original message is marked as "Replied"

**Message Actions**:
- **Mark as Read**: Change status to read
- **Mark as Unread**: Return to unread state
- **Archive**: Move to archived messages
- **Delete**: Permanently remove
- **Forward**: Send to another admin/member

**Contact Form Messages**:
- Messages from website contact form
- Sent by non-members (guests)
- Contains:
  - Sender name
  - Sender email
  - Subject
  - Message
  - Submission timestamp

**Bulk Actions**:
- Select multiple messages
- Mark as Read/Unread
- Archive Selected
- Delete Selected

### 7.7 Settings (`admin/settings.php`)

**Site Configuration**:

#### General Settings
- Site Name
- Site Email (contact)
- Site Phone
- Site Address
- Tagline/Slogan

#### Registration Settings
- Enable/Disable New Registrations
- Auto-Approve Members (vs. manual approval)
- Email Verification Required
- Registration Fields (required/optional)

#### Event Settings
- Events Per Page (pagination)
- Auto-Archive Past Events
- Registration Deadline Default (days before event)
- Max Participants Default

#### Gallery Settings
- Gallery Items Per Page
- Auto-Approve Uploads (by members)
- Allowed File Types
- Maximum File Size (MB)
- Image Quality (compression)

#### Email Notifications
- Enable/Disable Email Notifications
- SMTP Configuration:
  - Host
  - Port
  - Username
  - Password
  - Encryption (SSL/TLS)
- Email Templates:
  - Welcome Email
  - Password Reset
  - Event Registration Confirmation
  - Event Reminders

#### Security Settings
- Session Timeout (minutes)
- Password Requirements
- Failed Login Attempts Limit
- Enable Two-Factor Authentication
- IP Whitelist for Admin Access

#### Maintenance Mode
- Enable Maintenance Mode
- Maintenance Message
- Allowed IPs (during maintenance)

**Saving Settings**:
1. Modify desired settings
2. Click "Save Changes"
3. Settings apply immediately

---

## Features & Functionality

### 8.1 Event Registration System

**For Members**:
- Browse available events
- Register with one click
- View registration confirmation
- Receive email confirmation
- Cancel registration (before deadline)
- View registered events in dashboard

**For Admins**:
- Create events with full details
- Set registration deadlines
- Set participant limits
- View registration list
- Mark attendance
- Export attendee data
- Send bulk notifications

**Registration Process**:
1. Member views event details
2. Clicks "Register" button
3. Confirms registration
4. System checks:
   - Registration deadline not passed
   - Capacity not reached
   - Member not already registered
5. Registration recorded in database
6. Confirmation email sent
7. Event appears in member's dashboard

**Attendance Tracking**:
- Admin marks members as "Attended" after event
- Attendance statistics tracked
- Certificate generation (if enabled)

### 8.2 Gallery System

**Photo Upload Workflow**:
1. User uploads photo
2. Photo stored in `uploads/gallery/`
3. Thumbnail automatically generated
4. Status set to "Pending" (if approval required)
5. Admin reviews and approves
6. Photo published to public gallery
7. Appears in filtered views

**Gallery Features**:
- Lightbox viewing
- Category filtering
- Search functionality
- View counts
- Download options
- Social sharing
- Comments (optional feature)

### 8.3 Messaging System

**Internal Communication**:
- Member-to-Member messaging
- Member-to-Admin messaging
- Admin broadcasts
- System notifications

**Message Types**:
- Direct Messages
- Replies (threaded conversations)
- System Notifications:
  - Event reminders
  - Registration confirmations
  - Account status changes
  - New announcements

**Message Features**:
- Read/Unread status
- Reply tracking
- Message search
- Archive functionality
- Bulk operations

### 8.4 Profile System

**Member Profiles**:
- Public profile page
- Private information (visible only to admins)
- Social media integration
- Activity history
- Skills showcase

**Profile Visibility**:
- **Public**: Name, photo, bio, social links
- **Private**: Email, phone, student ID, address
- **Members Only**: Full profile details
- **Admin Only**: Complete information + activity logs

### 8.5 Search & Filter

**Site-Wide Search**:
- Search events by title, description, category
- Search members by name, department, skills
- Search gallery by title, description, tags

**Advanced Filters**:
- Events: By date range, category, status
- Gallery: By category, event, date range
- Members: By department, year, role

### 8.6 Responsive Design

**Mobile Optimization**:
- Touch-friendly navigation
- Optimized layouts for small screens
- Fast loading times
- Mobile-first approach

**Breakpoints**:
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

### 8.7 Security Features

**User Authentication**:
- Bcrypt password hashing
- Secure session management
- CSRF token protection
- XSS prevention
- SQL injection protection

**Access Control**:
- Role-based permissions
- Route protection
- Admin-only areas
- Member-only features

**Data Protection**:
- Input validation
- Output sanitization
- Secure file uploads
- Database prepared statements

---

## Troubleshooting

### 9.1 Common Issues

#### Cannot Access Website
**Problem**: Blank page or "Cannot connect" error  
**Solutions**:
1. Check web server is running (Apache/Nginx)
2. Verify correct URL: `http://localhost/KMC_Robotics_Club/`
3. Check PHP is installed and enabled
4. Review server error logs

#### Database Connection Error
**Problem**: "Database connection failed" message  
**Solutions**:
1. Verify MySQL/MariaDB is running
2. Check credentials in `config/config.php`
3. Ensure database exists
4. Test connection manually:
```bash
mysql -u root -p
USE kmc_robotics_club;
```

#### Cannot Login
**Problem**: "Invalid credentials" or error after login  
**Solutions**:
1. Verify email and password are correct
2. Check account status (Active/Pending/Inactive)
3. Reset password using "Forgot Password"
4. Clear browser cache and cookies
5. Check if email is verified (if required)

#### File Upload Errors
**Problem**: "Upload failed" or image not appearing  
**Solutions**:
1. Check `uploads/` directory exists and is writable
2. Verify file size is within limit (default: 5MB)
3. Ensure file type is allowed (JPG, PNG, GIF, WebP)
4. Check PHP `upload_max_filesize` and `post_max_size` settings
5. Review server error logs

#### 404 Errors on Links
**Problem**: "Page not found" errors  
**Solutions**:
1. Check `.htaccess` file exists (if using Apache)
2. Enable `mod_rewrite` in Apache:
```bash
a2enmod rewrite
systemctl restart apache2
```
3. Verify file paths are correct
4. Clear browser cache

#### Slow Page Loading
**Problem**: Pages take long time to load  
**Solutions**:
1. Optimize database with indexes
2. Enable PHP OPcache
3. Compress images before upload
4. Enable browser caching
5. Check server resources (CPU, RAM)

#### Email Not Sending
**Problem**: Emails not received (registration, password reset)  
**Solutions**:
1. Check SMTP configuration in settings
2. Verify firewall allows SMTP traffic
3. Test with external SMTP (Gmail, SendGrid)
4. Check spam/junk folders
5. Review PHP `mail()` function configuration

### 9.2 Error Messages

#### "Unknown column 'status' in 'where clause'"
**Cause**: Database schema mismatch  
**Fix**: This error has been fixed in the codebase. If still occurring, re-run the database schema.

#### "Uncaught PDOException: SQLSTATE[42S22]"
**Cause**: Column doesn't exist in database table  
**Fix**: 
1. Review database schema
2. Run: `mysql -u root -p kmc_robotics_club < database/schema.sql`
3. Check for recent code updates

#### "Session expired or invalid"
**Cause**: Session timeout or CSRF token mismatch  
**Fix**:
1. Refresh the page
2. Login again
3. Check `session.gc_maxlifetime` in php.ini

### 9.3 Getting Help

**Support Resources**:
1. Check documentation in `docs/` folder
2. Review README.md file
3. Contact technical lead
4. Submit issue with details:
   - Error message (exact text)
   - Steps to reproduce
   - Browser and version
   - Screenshot (if applicable)

---

## Security & Best Practices

### 10.1 Security Recommendations

**For Administrators**:

1. **Change Default Credentials**:
   - Change default admin password immediately
   - Use strong, unique passwords
   - Enable two-factor authentication

2. **Regular Updates**:
   - Keep PHP updated
   - Update database software
   - Monitor security advisories

3. **Backup Strategy**:
   - Daily database backups
   - Weekly file system backups
   - Store backups off-server
   - Test restoration process

4. **File Permissions**:
   - Config files: 644 (read-only)
   - Upload directories: 755
   - Prevent script execution in uploads:
```apache
# In uploads/.htaccess
<FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
    deny from all
</FilesMatch>
```

5. **SSL/HTTPS**:
   - Use HTTPS in production
   - Obtain SSL certificate (Let's Encrypt)
   - Force HTTPS redirect

6. **Monitor Activity**:
   - Review activity logs regularly
   - Check for suspicious login attempts
   - Monitor file uploads
   - Track admin actions

**For Members**:

1. **Password Security**:
   - Use strong, unique password
   - Don't share password
   - Change password periodically
   - Use password manager

2. **Account Security**:
   - Logout when using shared computers
   - Don't share account
   - Report suspicious activity
   - Keep contact info updated

3. **Safe Browsing**:
   - Don't click suspicious links
   - Verify you're on correct website
   - Be cautious with downloads
   - Report phishing attempts

### 10.2 Data Privacy

**User Data Protection**:
- Personal information encrypted
- Secure data transmission
- Limited data collection
- Privacy policy compliance
- Right to data deletion

**What Data is Collected**:
- Registration information (name, email, etc.)
- Login activity (timestamps, IP addresses)
- Event registrations
- Gallery uploads
- Messages (content and metadata)

**Data Retention**:
- Active accounts: Indefinite
- Inactive accounts: Review after 2 years
- Event data: Archived after 1 year
- Messages: User-controlled deletion
- Activity logs: 6 months

### 10.3 Best Practices

**For Optimal Performance**:

1. **Image Optimization**:
   - Compress images before upload
   - Use appropriate dimensions
   - Consider WebP format
   - Limit file sizes

2. **Database Maintenance**:
   - Regular OPTIMIZE TABLE commands
   - Archive old data
   - Monitor database size
   - Index frequently queried columns

3. **Caching**:
   - Enable browser caching
   - Use PHP OPcache
   - Cache database queries
   - Consider CDN for static assets

4. **Code Quality**:
   - Follow coding standards
   - Comment complex logic
   - Use version control
   - Test changes thoroughly

---

## Frequently Asked Questions

### General Questions

**Q: Who can access the website?**  
A: The homepage and public pages are accessible to everyone. Registration and login are required for member features. Admin access requires admin role.

**Q: Is the website mobile-friendly?**  
A: Yes, the entire website is fully responsive and works on smartphones, tablets, and desktops.

**Q: How do I join the club?**  
A: Navigate to the "Join Us" page or register directly at `/auth/register.php`. Fill out the registration form and wait for admin approval (if required).

### Registration & Login

**Q: I didn't receive a verification email**  
A: Check your spam/junk folder. If still not received, contact admin to manually verify your account or resend the email.

**Q: My account is pending approval. How long does it take?**  
A: Approval typically happens within 24-48 hours. Admins are notified of new registrations automatically.

**Q: I forgot my password. What should I do?**  
A: Use the "Forgot Password" link on the login page. Enter your email to receive reset instructions.

**Q: Can I change my email address?**  
A: Currently, email addresses cannot be changed by users. Contact an administrator if you need to update your email.

### Events

**Q: How do I register for an event?**  
A: Login to your account, view the event details, and click the "Register" button. You'll receive a confirmation.

**Q: Can I cancel my event registration?**  
A: Yes, you can cancel before the registration deadline by visiting "My Events" and clicking "Cancel Registration".

**Q: Will I receive a certificate for attending events?**  
A: Certificates are issued for certain events. Check event details or contact organizers.

**Q: How do I know if an event is full?**  
A: The event page shows the number of registered participants. If full, the "Register" button is disabled.

### Gallery

**Q: Can I upload photos to the gallery?**  
A: Yes, logged-in members can upload photos. They may require admin approval before appearing publicly.

**Q: What image formats are accepted?**  
A: JPG, JPEG, PNG, GIF, and WebP formats are supported. Maximum file size is 5MB.

**Q: Can I delete my uploaded photos?**  
A: You can request deletion by contacting an admin, or admins can remove photos from the gallery management page.

### Messages

**Q: How do I contact other members?**  
A: Use the internal messaging system. Go to Messages > Compose New Message, select the recipient, and send.

**Q: Are my messages private?**  
A: Messages are private between sender and recipient. Admins have access to all messages for moderation purposes.

**Q: Can I send messages to multiple people at once?**  
A: Currently, messages are one-to-one. For announcements to all members, contact an admin.

### Profile

**Q: How do I update my profile?**  
A: Login and navigate to Profile page. Click "Edit Profile", make changes, and save.

**Q: Who can see my profile information?**  
A: Your public profile (name, photo, bio, social links) is visible to all logged-in members. Email and phone are private.

**Q: Can I delete my account?**  
A: Contact an administrator to request account deletion. This action is permanent.

### Technical Issues

**Q: The website isn't loading properly**  
A: Try clearing your browser cache and cookies. Ensure you're using a modern browser (Chrome, Firefox, Safari, Edge).

**Q: I'm getting an error message**  
A: Take a screenshot of the error and contact technical support with details about what you were doing when the error occurred.

**Q: Images aren't displaying**  
A: Check your internet connection. If the problem persists, it may be a server issue. Report to admin.

### For Administrators

**Q: How do I approve new members?**  
A: Go to Admin > Users, filter by "Pending", review the applicant, and click "Approve" or "Reject".

**Q: Can I export member data?**  
A: Yes, use the "Export to CSV" feature in User Management to download member lists.

**Q: How do I send notifications to all members?**  
A: Create an announcement or use the bulk messaging feature in the admin panel.

**Q: Can I customize the website appearance?**  
A: Yes, modify the CSS files in the `css/` directory. For major changes, edit the relevant HTML/PHP files.

**Q: How do I backup the database?**  
A: Use phpMyAdmin or command line:
```bash
mysqldump -u root -p kmc_robotics_club > backup_$(date +%Y%m%d).sql
```

---

## Contact & Support

### Technical Support
- **Email**: admin@kmcrc.edu.np
- **Website**: [Internal Support Page]
- **Office Hours**: Monday-Friday, 10 AM - 4 PM

### Report Issues
Please include:
- Detailed description of the problem
- Steps to reproduce
- Error messages (screenshots)
- Your browser and version
- Date and time of occurrence

### Feature Requests
Submit feature requests through:
- Admin messages
- Email to technical team
- Club meetings

---

## Appendix

### A. Keyboard Shortcuts
- `Ctrl/Cmd + K`: Quick search
- `Ctrl/Cmd + /`: Show help
- `Escape`: Close modals/dialogs

### B. File Upload Specifications
- **Profile Pictures**: Max 2MB, JPG/PNG, min 200x200px
- **Event Banners**: Max 5MB, JPG/PNG/WebP, recommended 1200x600px
- **Gallery Photos**: Max 5MB, JPG/PNG/GIF/WebP

### C. Browser Compatibility
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ⚠️ Internet Explorer: Not supported

### D. API Endpoints (For Developers)
- `/api/events.php`: Event data
- `/api/gallery.php`: Gallery items
- `/api/users.php`: User information (restricted)
- `/api/team.php`: Team member data
- `/api/messages.php`: Messaging functions (authenticated)

### E. Database Tables Reference
- `users`: User accounts
- `events`: Event information
- `event_registrations`: Event sign-ups
- `gallery`: Photo gallery
- `messages`: Internal messaging
- `team_members`: Team directory
- `notifications`: System notifications
- `settings`: Site configuration
- `activity_logs`: User activity tracking

---

## Document Version

- **Version**: 1.0
- **Last Updated**: January 8, 2026
- **Author**: KMC Robotics Club Technical Team
- **For**: KMC Robotics Club Website v2.1

---

**End of User Manual**

For the most up-to-date information, visit the club's internal documentation or contact the technical team.
