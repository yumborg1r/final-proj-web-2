# GymFit Pro - Gym Membership Management System

A comprehensive PHP-based gym membership management system with role-based access control, subscription management, workout plan tracking, and attendance monitoring.

## Features

### For Users (Members)
- User registration and authentication
- Subscription management with auto-expiration
- Manual subscription renewal
- View available workout plans
- Track personal attendance history
- Profile management with photo upload

### For Staff
- Update and manage workout plans
- Mark member attendance via checkboxes
- View attendance reports
- Manage member information

### For Administrators
- Approve/reject subscription requests
- Manage all users (CRUD operations)
- Manage workout plans
- View comprehensive reports
- System-wide settings and controls

## Technical Features
- **Authentication System**: Secure login with role-based access control
- **Database Management**: MySQL with proper relationships and constraints
- **File Upload**: Profile photo upload with validation
- **Responsive Design**: Dark orange-gold theme with modern UI/UX
- **Data Validation**: Email and username uniqueness validation
- **Auto-increment IDs**: Database primary keys with auto-increment
- **CRUD Operations**: Complete Create, Read, Update, Delete functionality
- **Table Joins**: Complex queries with multiple table relationships

## Installation Instructions

### Prerequisites
- XAMPP (Apache, MySQL, PHP)
- Web browser

### Setup Steps

1. **Extract the Project**
   - Extract the zip file to your XAMPP htdocs directory
   - Example: `C:\xampp\htdocs\gymfitpro\`

2. **Database Setup**
   - Start XAMPP and ensure Apache and MySQL are running
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - This will create the `gym_membership` database with all required tables

3. **Configuration**
   - The database configuration is already set in `includes/Database.php`
   - Default settings:
     - Host: localhost
     - Database: gym_membership
     - Username: root
     - Password: (empty)

4. **File Permissions**
   - Ensure the `uploads/` directory has write permissions
   - Create the directory if it doesn't exist

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/gymfitpro/`
   - You should see the GymFit Pro homepage

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: password
- **Access**: Full system administration

### Demo Accounts
- **Staff**: staff / password
- **User**: user / password

*Note: You can create additional accounts through the registration system or admin panel.*

## Project Structure

```
gymfitpro/
├── admin/                  # Admin panel pages
│   ├── dashboard.php
│   ├── users.php
│   ├── subscriptions.php
│   └── ...
├── staff/                  # Staff panel pages
│   ├── dashboard.php
│   ├── workout-plans.php
│   └── ...
├── user/                   # User panel pages
│   ├── dashboard.php
│   ├── subscriptions.php
│   └── ...
├── includes/               # Core PHP classes
│   ├── Database.php
│   ├── auth.php
│   ├── Subscription.php
│   └── WorkoutPlan.php
├── assets/                 # CSS and JS files
│   └── css/
│       └── style.css
├── database/               # Database schema
│   └── schema.sql
├── uploads/               # File uploads directory
├── index.php              # Homepage
├── login.php              # Login page
├── register.php           # Registration page
└── logout.php             # Logout handler
```

## Database Schema

The system includes the following main tables:
- **users**: User accounts with roles (admin, staff, user)
- **subscription_plans**: Available subscription packages
- **user_subscriptions**: User subscription records
- **workout_plans**: Exercise and workout programs
- **user_attendance**: Member attendance tracking
- **payments**: Payment records

## Key Features Explained

### Authentication & Authorization
- Secure password hashing using PHP's `password_hash()`
- Session management with role-based access control
- Protection against unauthorized access

### Subscription Management
- Automatic expiration checking
- Manual renewal capability
- Admin approval workflow
- Payment status tracking

### Attendance System
- Checkbox-based attendance marking
- Time tracking (check-in/check-out)
- Attendance statistics and reports

### File Upload
- Profile photo upload with validation
- File type and size restrictions
- Secure file storage

### Responsive Design
- Dark orange-gold color scheme
- Mobile-friendly responsive layout
- Modern UI components with Bootstrap 5

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `includes/Database.php`
   - Verify database exists and tables are created

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Ensure directory exists
   - Verify PHP file upload settings

3. **Login Problems**
   - Use default credentials provided above
   - Check if user account is active
   - Verify session configuration

4. **Permission Errors**
   - Ensure proper file permissions on directories
   - Check XAMPP configuration
   - Verify PHP error reporting

### Support
For technical support or questions about the system, please refer to the code comments or contact the development team.

## License
This project is created for educational and demonstration purposes.

---

**GymFit Pro** - Transform Your Fitness Journey! 💪
