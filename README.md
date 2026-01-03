# SchoolDynamics SIMS - Multi-Tenant School Management System

## 🎯 Overview

SchoolDynamics is a production-ready, multi-tenant School Information Management System built with PHP and MySQL. It features:

- ✅ Single codebase serving multiple schools
- ✅ Router database for subdomain-based tenant resolution
- ✅ Per-tenant database isolation
- ✅ Role-Based Access Control (RBAC)
- ✅ Professional Tabler UI
- ✅ Comprehensive audit logging
- ✅ Encrypted tenant credentials
- ✅ MVC architecture with clean separation of concerns

## 📋 Requirements

- PHP 7.4+ (8.x recommended)
- MySQL 8.0+
- Apache with mod_rewrite enabled
- WAMP/LAMP/XAMPP or similar

## 🚀 Installation

### Step 1: Clone/Setup Project

The project is already set up in:
```
C:\wamp64_3.3.7\www\schooldynamics\
```

### Step 2: Configure Apache Virtual Host

Edit your `httpd-vhosts.conf` (usually in `C:\wamp64_3.3.7\bin\apache\apache2.x.x\conf\extra\`):

```apache
<VirtualHost *:80>
    ServerName schooldynamics.local
    ServerAlias *.schooldynamics.local
    DocumentRoot "C:/wamp64_3.3.7/www/schooldynamics/public"

    <Directory "C:/wamp64_3.3.7/www/schooldynamics/public">
        AllowOverride All
        Require all granted
        Options Indexes FollowSymLinks
    </Directory>

    ErrorLog "logs/schooldynamics-error.log"
    CustomLog "logs/schooldynamics-access.log" common
</VirtualHost>
```

### Step 3: Configure Hosts File

Edit `C:\Windows\System32\drivers\etc\hosts` (Run Notepad as Administrator):

```
127.0.0.1 admin.schooldynamics.local
127.0.0.1 demo.schooldynamics.local
```

### Step 4: Restart Apache

In WAMP, click "Restart All Services"

### Step 5: Install Router Database

```bash
cd C:\wamp64_3.3.7\www\schooldynamics
php bootstrap/install_router_db.php
```

**Expected Output:**
```
✓ Database 'sims_router' created
✓ Table 'tenants' created
✓ Table 'main_admin_users' created
✓ Default main admin user created
  Username: admin
  Password: admin123
```

### Step 6: Provision Demo Tenant

```bash
php bootstrap/provision_demo_tenant.php
```

**Expected Output:**
```
✓ Database 'sims_demo' created
✓ 19 tables created
✓ Demo data seeded
✓ Tenant registered

Access Details:
  URL: http://demo.schooldynamics.local
  Username: admin
  Password: admin123
```

## 🌐 Access URLs

### Main Admin Portal
- **URL:** http://admin.schooldynamics.local
- **Username:** admin
- **Password:** admin123
- **Purpose:** Manage all tenants, create schools, view metrics

### Demo Tenant (School)
- **URL:** http://demo.schooldynamics.local
- **Username:** admin
- **Password:** admin123
- **Purpose:** School management portal

## 📁 Directory Structure

```
schooldynamics/
├── app/
│   ├── Controllers/         # Request handlers
│   │   ├── AdminAuthController.php      # Main admin auth
│   │   ├── AdminDashboardController.php # Main admin dashboard
│   │   ├── AuthController.php           # Tenant auth
│   │   └── DashboardController.php      # Tenant dashboard
│   ├── Models/              # Data models (to be added)
│   ├── Services/            # Business logic (to be added)
│   ├── Repositories/        # Database access (to be added)
│   ├── Views/              # Presentation layer
│   │   ├── layouts/
│   │   │   ├── admin.php   # Main admin layout
│   │   │   └── tenant.php  # Tenant layout
│   │   ├── admin/
│   │   │   ├── login.php   # Main admin login
│   │   │   └── dashboard.php
│   │   └── tenant/
│   │       ├── login.php   # Tenant login
│   │       └── dashboard.php
│   ├── Middlewares/         # Cross-cutting concerns
│   │   ├── AuthMiddleware.php
│   │   └── PermissionMiddleware.php
│   ├── Jobs/                # Background jobs
│   └── Helpers/
│       └── functions.php    # Global helper functions
├── bootstrap/
│   ├── app.php              # Application bootstrap
│   ├── routes_admin.php     # Main admin routes
│   ├── routes_tenant.php    # Tenant routes
│   ├── install_router_db.php         # Router DB installer
│   ├── provision_demo_tenant.php     # Demo tenant creator
│   └── create_tenant_schema.php      # Tenant schema creator
├── config/
│   ├── env.php              # Environment loader
│   └── database.php         # Database connections
├── public/
│   ├── index.php            # Front controller
│   ├── .htaccess            # URL rewriting
│   └── assets/              # Static files
├── storage/
│   ├── logs/                # Application logs
│   ├── uploads/             # User uploads
│   ├── cache/               # Cache files
│   └── sessions/            # Session files
├── .env                     # Environment config (DO NOT COMMIT!)
├── .env.example             # Environment template
└── .gitignore               # Git ignore rules
```

## 🗄️ Database Architecture

### Router Database (`sims_router`)

Central database that maps subdomains to tenant databases:

- **tenants** - School credentials and configuration
- **main_admin_users** - Super admin accounts
- **tenant_metrics** - Usage tracking per school
- **router_audit_logs** - Audit trail for admin actions

### Tenant Databases (`sims_*`)

Each school has its own isolated database with:

- **System Tables:** modules, submodules, roles, permissions, settings
- **User Management:** users, user_roles, audit_logs
- **Academic:** students, guardians, classes, streams, enrollments
- **Calendar:** academic_years, terms
- **RBAC:** Comprehensive role-based access control

## 🔐 Security Features

### Encryption
- Tenant database passwords encrypted with AES-256-CBC
- SECURE_KEY stored in `.env` (never commit!)
- Bcrypt password hashing for all user accounts

### CSRF Protection
- CSRF tokens on all forms
- Token validation middleware
- Session-based token storage

### Authentication
- Failed login attempt tracking
- Account lockout after 5 failed attempts (configurable)
- IP logging for all authentication events
- Last login tracking

### Authorization (RBAC)
- Module and submodule-level permissions
- Action-based access: view, write, approve, export, delete
- Role inheritance and multiple roles per user
- Permission gates in controllers and views

### Audit Logging
- Every mutation logged with:
  - User ID, action, entity type/ID
  - Before/after snapshots (JSON)
  - IP address, user agent, timestamp
  - Success/failure status

## 🎨 UI Framework - Tabler

We use **Tabler** (https://tabler.io) - a premium Bootstrap-based UI kit:

- Modern, clean design
- Responsive and mobile-friendly
- Built on Bootstrap 5
- Tabler Icons included
- Optimized for admin dashboards

**CDN Links (already integrated):**
- CSS: `https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css`
- JS: `https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js`
- Icons: `https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css`

## 🔧 Configuration

### Environment Variables (`.env`)

Key settings in `.env`:

```ini
# Security
SECURE_KEY=your-secure-key-here    # CHANGE IN PRODUCTION!
FAILED_LOGIN_ATTEMPTS=5
LOCKOUT_DURATION=900              # 15 minutes

# Database
ROUTER_DB_HOST=localhost
ROUTER_DB_NAME=sims_router
ROUTER_DB_USER=root
ROUTER_DB_PASS=your-password

# Application
APP_ENV=local                     # local, production
APP_DEBUG=true                    # false in production
APP_TIMEZONE=Africa/Nairobi

# File Storage
UPLOAD_MAX_SIZE=10485760          # 10MB
ALLOWED_FILE_TYPES=pdf,doc,docx,xls,xlsx,jpg,jpeg,png

# Audit
AUDIT_ENABLED=true
```

## 👥 Default Roles

Each tenant comes with pre-configured roles:

1. **ADMIN** - System Administrator
   - Full system access
   - User management, RBAC configuration

2. **HEAD_TEACHER** - Head Teacher/Principal
   - School-wide oversight
   - Academic and operational management

3. **TEACHER** - Teacher
   - Class and subject management
   - Attendance and assessment

4. **BURSAR** - Bursar/Accountant
   - Finance and fees management
   - Invoice and receipt processing

5. **CLERK** - Clerk/Data Entry
   - Basic data entry operations
   - Student registration

## 🛠️ Development

### Creating a New Tenant

#### Method 1: Via Script

```bash
# Create custom tenant
php bootstrap/provision_demo_tenant.php
```

Then edit the script to change:
- `$subdomain` - e.g., 'hillside'
- `$schoolName` - e.g., 'Hillside Academy'
- `$dbName` - e.g., 'sims_hillside'

#### Method 2: Via UI (Coming Soon)
Use the Main Admin Portal → Tenants → Add New Tenant

### Adding New Routes

**Main Admin Routes** (`bootstrap/routes_admin.php`):
```php
Router::get('/my-route', 'MyController@myMethod');
Router::post('/my-route', 'MyController@store');
```

**Tenant Routes** (`bootstrap/routes_tenant.php`):
```php
Router::get('/my-route', 'MyController@myMethod');
```

### Creating Controllers

```php
// app/Controllers/MyController.php
<?php
class MyController
{
    public function index()
    {
        // Check authentication
        if (!isAuthenticated()) {
            Response::redirect('/login');
        }

        // Check permission
        if (!hasPermission('Module.Submodule.view')) {
            Gate::deny('You need view permission');
        }

        // Get data
        $pdo = Database::getTenantConnection();
        // ... query database

        // Return view
        Response::view('my-view', ['data' => $data]);
    }
}
```

### Creating Views

```php
// app/Views/my-module/my-view.php
<?php
$contentView = __DIR__ . '/_my_content.php';
$pageTitle = "My Page";
require __DIR__ . '/../layouts/tenant.php';
?>
```

Then create `_my_content.php` with your HTML.

### Helper Functions

Available globally:

```php
// Authentication
isAuthenticated()           // Check if user logged in
authUserId()               // Get current user ID
authUser()                 // Get current user data
hasPermission('perm')      // Check permission
Gate::hasRole('ADMIN')     // Check role

// CSRF
csrfField()                // Generate CSRF field
csrfToken()                // Get CSRF token
verifyCsrfToken($token)    // Verify token

// Flash Messages
flash('key', 'message')    // Set flash message
flash('key')               // Get & clear flash message

// Formatting
formatDate($date)          // Format date
formatDateTime($datetime)  // Format datetime
formatMoney($amount)       // Format currency

// Responses
Response::json($data)      // JSON response
Response::redirect($url)   // Redirect
Response::back()           // Go back
Response::view($view, $data)  // Render view

// Requests
Request::all()             // All input
Request::get('key')        // Get input
Request::ip()              // Get IP
Request::isAjax()          // Check AJAX

// Output
e($value)                  // Escape HTML
dd($var)                   // Dump & die
```

## 📊 Database Queries

### Using PDO (Direct)

```php
$pdo = Database::getTenantConnection();

// Select
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

// Insert
$stmt = $pdo->prepare("INSERT INTO students (first_name, last_name) VALUES (:first, :last)");
$stmt->execute(['first' => 'John', 'last' => 'Doe']);
$newId = $pdo->lastInsertId();

// Update
$stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
$stmt->execute(['status' => 'active', 'id' => $userId]);

// Transactions
Database::beginTransaction();
try {
    // ... multiple queries
    Database::commit();
} catch (Exception $e) {
    Database::rollback();
    throw $e;
}
```

## 🚨 Troubleshooting

### Issue: Cannot access admin.schooldynamics.local

**Solution:**
1. Check hosts file: `127.0.0.1 admin.schooldynamics.local`
2. Check virtual host configuration
3. Restart Apache
4. Try `http://localhost/schooldynamics/public/` directly

### Issue: Database not found

**Solution:**
```bash
php bootstrap/verify_database.php
```

If missing, reinstall:
```bash
php bootstrap/install_router_db.php
```

### Issue: Login fails with "Invalid credentials"

**Solution:**
- Main Admin: Username `admin`, Password `admin123`
- Demo Tenant: Username `admin`, Password `admin123`
- Check you're on the correct subdomain

### Issue: 500 Internal Server Error

**Solution:**
1. Check `storage/logs/app_*.log`
2. Ensure `.env` file exists
3. Check database credentials in `.env`
4. Enable debug mode: `APP_DEBUG=true` in `.env`

### Issue: Permission denied errors

**Solution:**
```bash
# Give write permissions to storage folder
chmod -R 755 storage/
```

## 📝 Next Steps

Ready-to-implement features:

1. **Tenant Management UI**
   - Create, edit, delete tenants
   - Toggle status and maintenance mode
   - View tenant metrics

2. **Student Management**
   - Full CRUD operations
   - Bulk import from CSV
   - Photo uploads
   - Guardian management

3. **Finance Module**
   - Fee tariffs configuration
   - Invoice generation (batch)
   - Receipt posting
   - Student statements

4. **Academic Management**
   - Timetable builder
   - Attendance tracking
   - Assessment/grades entry

5. **Communication**
   - SMS integration (Africa's Talking)
   - Email templates
   - Bulk messaging

## 📞 Support

For issues or questions:
- Check `storage/logs/` for error logs
- Review `SETUP_PROGRESS.md` for setup details
- Consult the spec document: `schooldynamics_intro.md`

## 🔒 Production Checklist

Before deploying to production:

- [ ] Change `SECURE_KEY` in `.env`
- [ ] Change all default passwords
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Enable HTTPS/SSL
- [ ] Configure proper file permissions
- [ ] Set up automated backups
- [ ] Configure email/SMS providers
- [ ] Review and restrict database user privileges
- [ ] Set up monitoring and logging
- [ ] Configure firewall rules

## 📄 License

Proprietary - SchoolDynamics SIMS © 2025

---

**Version:** 1.0.0
**Last Updated:** 2025-11-03
**PHP Version:** 7.4+
**Database:** MySQL 8.0+
