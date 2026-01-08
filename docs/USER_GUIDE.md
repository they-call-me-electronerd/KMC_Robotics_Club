# KMC Robotics Club Website - User Guide

## Introduction
Welcome to the implementation guide and user manual for the **Kathmandu Model College Robotics Club (KMC RC)** website. This platform acts as the digital headquarters for the club, facilitating member management, event coordination, and showcasing the club's achievements.

This guide covers installation, public features, member features, and administrative tools.

---

## 1. Installation & Setup

### Prerequisites
- A web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or MariaDB

### First-Time Setup
1. **Deploy Files**: Copy all project files to your web server's public directory (e.g., `htdocs` or `www`).
2. **Launch Installer**: Open your web browser and navigate to `http://your-domain.com/install.php`.
   - **Step 1**: Review the prerequisites.
   - **Step 2**: Enter your Database credentials (Host, Database Name, Username, Password).
   - **Step 3**: Create the first Admin account.
3. **Completion**: The script will create the `config/config.php` file and populate the database tables.
4. **Cleanup**: **Important!** Delete `install.php` after successful installation for security.

---

## 2. Public Interface
The public-facing website is designed for prospective members and the general public.

- **Home (`index.html`)**: Landing page with hero section, club overview, and latest updates.
- **About (`pages/about.php`)**: History, mission, and vision of the club.
- **Events (`pages/events.php`)**:
  - Lists upcoming and past events.
  - Allows filtering by category (Workshop, Competition, Seminar).
  - Users can click "View Details" to see the full agenda.
- **Gallery (`pages/gallery.php`)**: Photo grid of club activities.
- **Team (`pages/team.php`)**: Showcases current executives and members.
- **Join Us (`pages/join.php`)**: Information on how to become a member.

---

## 3. Member Portal
Members can access exclusive features by logging in.

### Getting Access
- **Registration**: New users can sign up at `/auth/register.php`. Accounts may require admin approval based on settings.
- **Login**: Existing users access the portal via `/auth/login.php`.

### Member Features
1. **Dashboard (`member/dashboard.php`)**:
   - Overview of registered events.
   - Announcements and club news.
2. **Event Registration**:
   - Members can RSVP for specific events directly from the dashboard or events page.
   - Track attendance status.
3. **Profile Management (`member/profile.php`)**:
   - Update personal information (Bio, Skills, Contact info).
   - Link social media profiles (LinkedIn, GitHub).
   - Change password.

---

## 4. Admin Portal
Accessible only to users with the 'admin' role. Contains tools to manage the entire platform.

**Access**: Log in and navigate to `/admin/dashboard.php`.

### Dashboard Overview
- **Quick Stats**: Total members, upcoming events, pending requests.
- **Recent Activity Log**.

### Management Modules

#### **Events Management** (`admin/events.php`)
- **Add Event**: Create new events with details (Title, Date, Time, Location, Image).
- **Edit/Delete**: Modify existing event details.
- **Registrations**: View who has signed up for an event and mark their attendance.

#### **User Management** (`admin/users.php`)
- **View Users**: List all registered members.
- **Role Assignment**: Promote members to Admins.
- **Status Control**: Activate/Deactivate accounts or approve pending registrations.

#### **Team Management** (`admin/team.php`)
- **Organize Team**: Add members to the public "Team" page.
- **Roles**: Assign specific roles (e.g., President, Technical Lead) and categories (Executive, Technical).

#### **Gallery Management** (`admin/gallery.php`)
- **Upload**: Add photos to the public gallery.
- **Organize**: Caption and categorize images.

#### **Messages** (`admin/messages.php`)
- View inquiries submitted through the contact form.

#### **Settings** (`admin/settings.php`)
- General site configuration (Site Name, Contact Email).
- maintenance mode toggles.

---

## 5. Security Best Practices
- **File Permissions**: Ensure `config/config.php` is read-only.
- **Uploads Folder**: The `uploads/` directory must be writable by the web server but should prevent script execution if possible.
- **Regular Updates**: Keep the PHP and MySQL server updated.

## 6. Troubleshooting

| Issue | Solution |
|-------|----------|
| **Database Connection Error** | Check `config/config.php` credentials. Ensure MySQL server is running. |
| **"Access Denied"** | Verify you are logged not as a regular member but with an Admin account. |
| **Images Not Loading** | Check permissions of the `uploads/` folder. Ensure paths are relative/correct. |
| **404 Errors** | Ensure `.htaccess` is configured correctly if utilizing URL rewriting (optional). |

---

*For technical support, please contact the current Technical Lead or the website maintainer.*
