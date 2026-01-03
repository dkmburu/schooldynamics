# SchoolDynamics SIMS - Setup Progress

## ✅ Completed Steps

### 1. Project Structure ✓
- Created MVC directory structure
- Set up folders: app/, config/, bootstrap/, public/, storage/
- Sub-folders for Controllers, Services, Repositories, Models, Views, Middlewares, Jobs, Helpers

### 2. Environment Configuration ✓
- Created `.env` file with database credentials
- Created `.env.example` template
- Built `config/env.php` - Environment loader with variable substitution
- Created `.gitignore` for security
- Configured timezone, security keys, file paths

### 3. Router Database ✓
- Created `sims_router` database
- Tables created:
  - `tenants` - Store tenant credentials and settings
  - `main_admin_users` - Super admin access
  - `tenant_metrics` - Track tenant usage
  - `router_audit_logs` - Audit trail for admin actions
- Default admin user: **admin / admin123** (⚠️ CHANGE IN PRODUCTION!)
- Database encryption for tenant passwords using AES-256-CBC

### 4. Core Framework ✓
- **Database Class** (`config/database.php`):
  - Router DB connection
  - Tenant resolution by subdomain
  - Dynamic tenant DB connection
  - Password encryption/decryption
  - Transaction support

- **Request Lifecycle** (`bootstrap/app.php`):
  - Router class with GET/POST/ANY methods
  - Dynamic routing with parameters (`:id` style)
  - Request class for handling HTTP requests
  - Response class (JSON, redirect, views)
  - Middleware interface
  - Error handling and logging

- **Helper Functions** (`app/Helpers/functions.php`):
  - HTML escaping, CSRF protection
  - Flash messages, old input
  - Date/money formatting
  - Authentication helpers
  - Permission checks

- **Front Controller** (`public/index.php`):
  - Subdomain extraction
  - Tenant resolution
  - Main admin vs tenant routing
  - Maintenance mode handling

- **Routing**:
  - `bootstrap/routes_admin.php` - Main admin routes
  - `bootstrap/routes_tenant.php` - Tenant routes
  - URL rewriting via `.htaccess`

## 📋 Next Steps

### 5. Database Abstraction Layer (Repository Pattern)
- [ ] Create base Repository class
- [ ] Create base Model class
- [ ] Build query builder
- [ ] Implement tenant DB migrations system

### 6. RBAC System
- [ ] Create tenant DB schema (roles, permissions, user_roles tables)
- [ ] Build Permission Gate middleware
- [ ] Create RBAC helper functions
- [ ] Seed default roles (Admin, Bursar, Teacher, etc.)

### 7. Authentication System
- [ ] AdminAuthController for main admin
- [ ] AuthController for tenant users
- [ ] Session management
- [ ] Password reset functionality
- [ ] Failed login tracking

### 8. Tabler UI Integration
- [ ] Download Tabler dist files
- [ ] Create base layout files
- [ ] Admin portal layout
- [ ] Tenant portal layout
- [ ] Login pages with Tabler styling

### 9. Tenant Provisioning UI
- [ ] List tenants page
- [ ] Create tenant form
- [ ] Edit tenant page
- [ ] Toggle status/maintenance
- [ ] Auto-create tenant database

### 10. Audit Logging System
- [ ] Create audit_logs table (tenant DB)
- [ ] Audit middleware
- [ ] Log all mutations
- [ ] View audit logs UI

## 🗄️ Database Credentials

**Router DB:**
- Host: localhost
- Database: sims_router
- User: root
- Password: wkid2019

**Main Admin:**
- URL: http://admin.schooldynamics.local
- Username: admin
- Password: admin123

## 🌐 Access URLs

**Main Admin Portal:**
```
http://admin.schooldynamics.local
```

**Tenant Access:**
```
http://schoolname.schooldynamics.local
http://school2.schooldynamics.local
```

## 📁 Key Files

```
/public/index.php           - Front controller
/bootstrap/app.php          - Core framework
/bootstrap/routes_admin.php - Main admin routes
/bootstrap/routes_tenant.php- Tenant routes
/config/env.php            - Environment loader
/config/database.php       - Database connections
/app/Helpers/functions.php - Global helpers
/.env                      - Environment config (DO NOT COMMIT!)
```

## 🔧 Installation Scripts

```bash
# Install/Reset Router DB
php bootstrap/install_router_db.php

# Reset specific tables
php bootstrap/reset_router_tables.php
```

## ⚡ Quick Test

To test the setup:
1. Ensure WAMP is running
2. Configure virtual hosts in `httpd-vhosts.conf`:
   ```apache
   <VirtualHost *:80>
       ServerName schooldynamics.local
       ServerAlias *.schooldynamics.local
       DocumentRoot "c:/wamp64_3.3.7/www/schooldynamics/public"
       <Directory "c:/wamp64_3.3.7/www/schooldynamics/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Add to `C:\Windows\System32\drivers\etc\hosts`:
   ```
   127.0.0.1 admin.schooldynamics.local
   127.0.0.1 demo.schooldynamics.local
   ```
4. Restart Apache
5. Visit: http://admin.schooldynamics.local

## 🔐 Security Notes

- SECURE_KEY is set in `.env` - change in production
- Default admin password must be changed
- All tenant DB passwords are encrypted with AES-256
- CSRF protection implemented
- SQL injection prevention via PDO prepared statements
- XSS protection via output escaping

## 📝 Development Guidelines

1. **Controllers**: Handle HTTP requests, call services
2. **Services**: Business logic layer
3. **Repositories**: Database access layer
4. **Models**: Data entities
5. **Views**: Presentation layer (Tabler UI)
6. **Middlewares**: Cross-cutting concerns (auth, RBAC, audit)

## 🎯 Current Status

**Architecture**: ✅ Complete
**Database**: ✅ Router DB ready
**Routing**: ✅ Complete
**Authentication**: 🟡 Pending
**RBAC**: 🟡 Pending
**UI**: 🟡 Pending
**Tenant Provisioning**: 🟡 Pending

---

**Last Updated**: 2025-11-03 14:00 EAT
**Version**: 0.1.0-alpha
