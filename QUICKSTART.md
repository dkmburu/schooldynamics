# SchoolDynamics SIMS - Quick Start Guide

## ⚡ 5-Minute Setup

### 1. Update Hosts File (As Administrator)

Edit: `C:\Windows\System32\drivers\etc\hosts`

Add these lines:
```
127.0.0.1 admin.schooldynamics.local
127.0.0.1 demo.schooldynamics.local
```

### 2. Restart Apache

In WAMP: Click **"Restart All Services"**

### 3. Test Access

Open your browser and visit:

**Main Admin:**
```
http://admin.schooldynamics.local
```

**Demo School:**
```
http://demo.schooldynamics.local
```

## 🔑 Default Login Credentials

### Main Admin Portal
- URL: http://admin.schooldynamics.local
- Username: `admin`
- Password: `admin123`

### Demo School Portal
- URL: http://demo.schooldynamics.local
- Username: `admin`
- Password: `admin123`

## ✅ What's Already Set Up

✅ Router database (`sims_router`) created
✅ Demo tenant database (`sims_demo`) created
✅ 19 tables in tenant database
✅ Default roles configured (Admin, Teacher, Bursar, etc.)
✅ Authentication system working
✅ RBAC permissions system active
✅ Professional Tabler UI integrated
✅ Audit logging enabled

## 🎯 What You Can Do Now

### In Main Admin Portal:
- View all tenants
- See system metrics
- Manage admin users
- View audit logs

### In Demo School Portal:
- View dashboard with statistics
- See sample classes (Grade 1-6)
- Check your assigned roles
- Navigate through modules (Students, Academics, Finance, etc.)

## 📋 Database Details

### Router Database
- Name: `sims_router`
- Purpose: Maps subdomains to tenant DBs
- Tables: 4

### Demo Tenant Database
- Name: `sims_demo`
- Purpose: School data storage
- Tables: 19
- Sample Data: Yes (6 classes)

## 🔍 What's Included

### Frontend (UI)
- ✅ Login pages (Admin & Tenant) with Tabler design
- ✅ Dashboard layouts with sidebars
- ✅ Responsive design (mobile-friendly)
- ✅ Flash messages
- ✅ Role-based navigation

### Backend
- ✅ MVC architecture
- ✅ Router with dynamic routes
- ✅ Database abstraction with PDO
- ✅ Authentication with session management
- ✅ RBAC middleware
- ✅ Audit logging
- ✅ Helper functions (50+ utilities)

### Security
- ✅ CSRF protection
- ✅ Password hashing (Bcrypt)
- ✅ Failed login tracking & lockout
- ✅ Encrypted tenant DB passwords
- ✅ Input validation
- ✅ SQL injection prevention

## 🚀 Next Steps

1. **Test Login** - Log into both portals
2. **Explore UI** - Click through menus and pages
3. **Add More Schools** - Create additional tenants
4. **Build Features** - Start with Student Management or Finance

## 📚 Full Documentation

See `README.md` for:
- Complete setup instructions
- Architecture details
- Development guide
- API reference
- Troubleshooting

## 🆘 Quick Troubleshooting

**Can't access admin.schooldynamics.local?**
1. Check hosts file has the entries
2. Restart Apache
3. Clear browser cache

**Login doesn't work?**
- Check URL (correct subdomain?)
- Use: admin / admin123
- Check browser console for errors

**Database errors?**
```bash
cd C:\wamp64_3.3.7\www\schooldynamics
php bootstrap/verify_database.php
```

**Reset everything:**
```bash
# Reinstall router DB
php bootstrap/install_router_db.php

# Recreate demo tenant
php bootstrap/provision_demo_tenant.php
```

## 🎨 UI Preview

### Main Admin Portal
- Clean, professional dashboard
- Tenant management interface
- Metrics and statistics
- System-wide audit logs

### School Portal
- School-branded interface
- Role-based sidebar menu
- Module-specific dashboards
- Student, Finance, Academic modules

## 💡 Tips

1. **Testing Multiple Schools**
   - Create another tenant with different subdomain
   - Each school is completely isolated
   - Same codebase, different data

2. **Customization**
   - Views are in `app/Views/`
   - Layouts in `app/Views/layouts/`
   - CSS via Tabler CDN (easily customizable)

3. **Development**
   - Enable debug mode: `APP_DEBUG=true` in `.env`
   - Check logs: `storage/logs/app_*.log`
   - Use `dd($var)` for debugging

## 🔐 Security Reminder

⚠️ **BEFORE PRODUCTION:**
- Change `SECURE_KEY` in `.env`
- Change all default passwords
- Set `APP_DEBUG=false`
- Enable HTTPS
- Review `.gitignore` (`.env` should NOT be committed)

## 📞 Need Help?

1. Check `README.md` for detailed documentation
2. Review `SETUP_PROGRESS.md` for what's implemented
3. Check error logs in `storage/logs/`
4. Review the spec: `schooldynamics_intro.md`

---

**Ready to start?** Visit http://admin.schooldynamics.local and log in with `admin` / `admin123`

🎉 **Welcome to SchoolDynamics!**
