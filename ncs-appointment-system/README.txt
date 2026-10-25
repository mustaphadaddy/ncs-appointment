================================================================================
                    NCS ONLINE APPOINTMENT APPLICATION SYSTEM
                    Nigeria Customs Service
                    Version: 1.0.0
================================================================================

TABLE OF CONTENTS
-----------------
1. PROJECT OVERVIEW
2. SYSTEM REQUIREMENTS
3. PROJECT STRUCTURE
4. INSTALLATION GUIDE
5. DATABASE SETUP
6. DEFAULT LOGIN CREDENTIALS
7. USER ROLES & PERMISSIONS
8. FEATURES LIST
9. API ENDPOINTS
10. TROUBLESHOOTING
11. SUPPORT & CONTACT
12. LICENSE

================================================================================
1. PROJECT OVERVIEW
================================================================================

The Online Appointment Application System for the Nigeria Customs Service (NCS) 
is a web-based platform designed to streamline the process of booking and 
managing appointments between members of the public and Customs offices.

Key Features:
- Online appointment booking
- Role-based access control (Admin, Officer, Applicant)
- Document upload and management
- Appointment tracking
- Printable appointment slips
- Audit logging
- Email/SMS notifications
- Dark/Light mode support

================================================================================
2. SYSTEM REQUIREMENTS
================================================================================

Server Requirements:
-------------------
- PHP 7.4 or higher (PHP 8.x recommended)
- MySQL 5.7 or higher / MariaDB 10.4 or higher
- Apache 2.4 or higher / Nginx
- XAMPP/WAMP/LAMP/MAMP stack recommended

PHP Extensions Required:
-----------------------
- mysqli
- json
- session
- fileinfo (for file uploads)
- gd (for image processing)
- mbstring
- openssl

Browser Requirements:
--------------------
- Google Chrome (latest)
- Mozilla Firefox (latest)
- Microsoft Edge (latest)
- Safari (latest)
- Any modern mobile browser

================================================================================
3. PROJECT STRUCTURE
================================================================================

ncs-appointment-system/
├── index.php                          # Home page
├── login.php                          # Login page
├── register.php                       # Registration page
├── track-appointment.php              # Public appointment tracking
├── logout.php                         # Logout handler
├── apply-appointment.php              # Apply redirect (if not logged in)
│
├── includes/
│   ├── config.php                     # Database & system configuration
│   ├── header.php                     # HTML head section
│   ├── topnav.php                     # Navigation bar
│   └── footer.php                     # Footer section
│
├── admin/
│   ├── dashboard.php                  # Admin dashboard
│   ├── manage-users.php               # User management
│   ├── manage-offices.php             # Office management
│   ├── manage-appointments.php        # Appointment management
│   ├── audit-logs.php                 # Audit logs viewer
│   ├── profile.php                    # Admin profile
│   ├── system-settings.php            # System settings
│   └── view-appointment.php           # View appointment details
│
├── officer/
│   ├── dashboard.php                  # Officer dashboard
│   ├── view-appointments.php          # View all appointments
│   ├── pending.php                    # Pending appointments
│   ├── approved.php                   # Approved appointments
│   ├── rejected.php                   # Rejected appointments
│   ├── view-applicant.php             # View applicant details
│   ├── view-appointment.php           # View appointment details
│   └── profile.php                    # Officer profile
│
├── applicant/
│   ├── dashboard.php                  # Applicant dashboard
│   ├── apply.php                      # Book appointment
│   ├── my-appointments.php            # View appointments
│   ├── view-appointment.php           # View appointment details
│   ├── upload-document.php            # Upload documents
│   ├── print-slip.php                 # Print appointment slip
│   └── profile.php                    # Applicant profile
│
├── api/
│   ├── auth-api.php                   # Authentication API
│   ├── users-api.php                  # User management API
│   ├── appointments-api.php           # Appointment management API
│   ├── offices-api.php                # Office management API
│   ├── documents-api.php              # Document management API
│   └── settings-api.php               # System settings API
│
├── assets/
│   ├── css/
│   │   └── ncs-style.css              # Main stylesheet
│   ├── js/
│   │   └── ncs-main.js                # Main JavaScript
│   └── img/
│       └── ncs-favicon.png            # Favicon
│
├── uploads/
│   └── documents/                     # Uploaded documents
│
├── database/
│   └── schema.sql                     # Database schema
│
└── docs/
    └── README.txt                     # This file

================================================================================
4. INSTALLATION GUIDE
================================================================================

Step 1: Download & Extract
-------------------------
1. Download the project zip file
2. Extract to your web server directory:
   - XAMPP: C:\xampp\htdocs\ncs-appointment-system\
   - WAMP: C:\wamp\www\ncs-appointment-system\
   - LAMP: /var/www/html/ncs-appointment-system/

Step 2: Configure Database
-------------------------
1. Open phpMyAdmin or your preferred MySQL client
2. Create a new database: ncs_appointment_db
3. Import the database schema:
   - Open database/schema.sql
   - Run the SQL script in phpMyAdmin
   - OR use command line: mysql -u root -p ncs_appointment_db < database/schema.sql

Step 3: Configure Application
----------------------------
1. Open includes/config.php
2. Update database credentials:
   $host = 'localhost';
   $username = 'root';
   $password = '';
   $dbname = 'ncs_appointment_db';

Step 4: Set Permissions
-----------------------
1. Ensure the uploads/documents/ directory is writable:
   - Windows: Right-click > Properties > Security > Full Control
   - Linux: chmod -R 755 uploads/

Step 5: Access the Application
-----------------------------
1. Open your browser and navigate to:
   http://localhost/ncs-appointment-system/
2. Login with default credentials (see Section 6)
3. Start using the system!

================================================================================
5. DATABASE SETUP
================================================================================

Creating the Database:
---------------------
CREATE DATABASE ncs_appointment_db;
USE ncs_appointment_db;

Import Schema:
-------------
mysql -u root -p ncs_appointment_db < database/schema.sql

Or via phpMyAdmin:
1. Select the ncs_appointment_db database
2. Go to Import tab
3. Choose schema.sql file
4. Click Go

Sample Data:
-----------
The schema includes sample data for:
- Admin user (admin@customs.gov.ng / Admin@123)
- Officer users
- Applicant users
- Office locations
- Time slots
- System settings

================================================================================
6. DEFAULT LOGIN CREDENTIALS
================================================================================

ADMIN USER:
-----------
Email:    admin@customs.gov.ng
Password: Admin@123
Role:     Administrator

OFFICER USERS:
-------------
Email:    james.okonkwo@customs.gov.ng
Password: Admin@123
Role:     Officer

Email:    fatima.bello@customs.gov.ng
Password: Admin@123
Role:     Officer

Email:    chidi.eze@customs.gov.ng
Password: Admin@123
Role:     Officer

Email:    amara.nwachukwu@customs.gov.ng
Password: Admin@123
Role:     Officer

APPLICANT USERS:
---------------
Email:    adeola.ogunleye@gmail.com
Password: Admin@123
Role:     Applicant

Email:    ngozi.eze@yahoo.com
Password: Admin@123
Role:     Applicant

Email:    tunde.bakare@gmail.com
Password: Admin@123
Role:     Applicant

Email:    chioma.okonkwo@outlook.com
Password: Admin@123
Role:     Applicant

================================================================================
7. USER ROLES & PERMISSIONS
================================================================================

ADMINISTRATOR
-------------
- Full system access
- Manage users (add, edit, delete, toggle status)
- Manage offices (add, edit, delete, toggle status)
- Manage all appointments (approve, reject, delete)
- View audit logs
- System settings configuration
- View all statistics

OFFICER
-------
- View all appointments
- Approve/reject pending appointments
- View applicant details
- Profile management
- Dashboard statistics

APPLICANT
---------
- Book appointments
- View own appointments
- Upload documents
- Print appointment slips
- Track appointment status
- Profile management

PUBLIC (Not Logged In)
----------------------
- Register new account
- Login
- Track appointment status (with appointment number)

================================================================================
8. FEATURES LIST
================================================================================

Authentication & Security:
-------------------------
- Secure user registration and login
- Password hashing (bcrypt)
- Session management
- Role-based access control
- Login attempt limiting
- Remember me functionality
- Password reset (coming soon)

Appointment Management:
----------------------
- Book appointments with date/time selection
- View all appointments with status
- Approve/Reject appointments (Officer/Admin)
- Cancel appointments (Applicant)
- Print appointment slips
- Appointment tracking (public)

Office Management:
-----------------
- Add/Edit/Delete offices
- Office types: Headquarters, Zonal, Area Command, Port, Border
- Set operating hours
- Configure time slots
- Active/Inactive toggle

User Management:
---------------
- Add/Edit/Delete users
- Assign roles (Admin/Officer/Applicant)
- Activate/Deactivate accounts
- Search and filter users
- View user details

Document Management:
------------------
- Upload documents (PDF, JPG, PNG)
- Document types: ID, Passport, NIN, etc.
- Link documents to appointments
- Document verification (Officer)
- Document preview and deletion

Audit Logs:
----------
- Track all system activities
- User actions logging
- Appointment status changes
- Login/Logout tracking
- Search and filter logs
- Export functionality

Reporting:
---------
- Dashboard statistics
- Appointment trends charts
- User statistics
- Office statistics
- Status distribution

System Settings:
---------------
- Site name configuration
- Timezone settings
- Appointment limits
- Notification settings
- Security settings
- Maintenance mode

UI/UX Features:
-------------
- Dark/Light mode toggle
- Responsive design
- AOS animations
- Toast notifications (iziToast)
- SweetAlert confirmations
- DataTables for tables
- Chart.js for statistics
- Mobile-friendly navigation

================================================================================
9. API ENDPOINTS
================================================================================

Authentication API (auth-api.php):
--------------------------------
- login      - User login
- register   - User registration
- check_email - Check email availability

Users API (users-api.php):
-------------------------
- add_user           - Add new user (Admin)
- update_user        - Update user (Admin)
- toggle_user_status - Toggle user active status (Admin)
- delete_user        - Delete user (Admin)
- update_profile     - Update own profile
- change_password    - Change password
- logout_all         - Logout from all devices

Appointments API (appointments-api.php):
--------------------------------------
- approve_appointment  - Approve appointment (Officer/Admin)
- reject_appointment   - Reject appointment (Officer/Admin)
- delete_appointment   - Delete appointment (Admin)
- cancel_appointment   - Cancel appointment (Applicant)
- book_appointment     - Book new appointment (Applicant)
- get_appointment_details - Get appointment details

Offices API (offices-api.php):
----------------------------
- add_office        - Add new office (Admin)
- update_office     - Update office (Admin)
- toggle_office_status - Toggle office status (Admin)
- delete_office     - Delete office (Admin)

Documents API (documents-api.php):
--------------------------------
- upload_document   - Upload document (Applicant)
- delete_document   - Delete document (Applicant/Admin)

Settings API (settings-api.php):
------------------------------
- save_settings     - Save system settings (Admin)
- reset_settings    - Reset to defaults (Admin)

================================================================================
10. TROUBLESHOOTING
================================================================================

Common Issues & Solutions:

1. Database Connection Error
   -------------------------
   - Check database credentials in includes/config.php
   - Ensure MySQL service is running
   - Verify database name is correct
   - Check if user has proper privileges

2. 404 Page Not Found
   -------------------
   - Ensure mod_rewrite is enabled (Apache)
   - Check file permissions
   - Verify BASE_URL in includes/config.php

3. File Upload Error
   ------------------
   - Ensure uploads/documents/ directory exists
   - Set proper permissions (755 or 777)
   - Check PHP upload_max_filesize in php.ini
   - Check post_max_size in php.ini

4. Session Issues
   ---------------
   - Clear browser cache and cookies
   - Check session.save_path in php.ini
   - Ensure session_start() is called

5. Dark Mode Not Working
   ----------------------
   - Clear browser cache
   - Check localStorage for theme preference
   - Verify CSS variables are set

6. Email Notifications
   --------------------
   - Configure SMTP settings (if implemented)
   - Check email server connectivity
   - Verify email addresses are valid

7. PHP Errors
   -----------
   - Enable error reporting in php.ini:
     error_reporting = E_ALL
     display_errors = On
   - Check Apache/Nginx error logs

================================================================================
11. SUPPORT & CONTACT
================================================================================

For support, please contact:

Nigeria Customs Service
Website: https://www.customs.gov.ng
Email: support@customs.gov.ng
Phone: +234 800 287 8677

Technical Support:
-----------------
Email: tech-support@customs.gov.ng

Bug Reports:
-----------
Email: bugs@customs.gov.ng
Please include:
- Error message
- Steps to reproduce
- Browser/OS information
- Screenshots (if applicable)

Feature Requests:
----------------
Email: features@customs.gov.ng

================================================================================
12. LICENSE
================================================================================

This software is proprietary to the Nigeria Customs Service (NCS).

Copyright (c) 2026 Nigeria Customs Service. All rights reserved.

Unauthorized copying, modification, distribution, or use of this software,
via any medium, is strictly prohibited without prior written permission from
the Nigeria Customs Service.

================================================================================
13. ACKNOWLEDGMENTS
================================================================================

This system was built using the following open-source libraries:

- Bootstrap 5.3 - CSS Framework
- jQuery 3.7 - JavaScript Library
- Chart.js 4.4 - Charts
- DataTables 1.13 - Tables
- SweetAlert2 11.10 - Alerts
- iziToast 1.4 - Notifications
- AOS 2.3 - Animations
- Font Awesome / Bootstrap Icons - Icons
- Google Fonts (Inter) - Typography

================================================================================
14. VERSION HISTORY
================================================================================

Version 1.0.0 (July 2026)
------------------------
- Initial release
- Full system functionality
- All modules complete
- Dark/Light mode
- Mobile responsive
- Documentation included

================================================================================
15. FUTURE IMPROVEMENTS
================================================================================

Planned features for future releases:

- SMS notifications
- Email notifications
- Mobile application
- API for third-party integration
- Multi-language support
- Advanced reporting
- Appointment reminders
- Online payment integration
- Live chat support
- Document verification with OCR

================================================================================

END OF README

Nigeria Customs Service - Service with Integrity
================================================================================