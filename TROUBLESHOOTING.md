# Troubleshooting: Internal Server Error

## Quick Diagnosis

### Step 1: Test if PHP Works
Visit: **http://demo.schooldynamics.local/test.php**

If you see "PHP is working!", continue to Step 2.
If you get an error, PHP is not working - check WAMP Apache/PHP configuration.

### Step 2: Check What the Error Says
The test.php page will show:
- PHP version
- If mod_rewrite is loaded
- If .htaccess exists
- File paths

### Step 3: Common Issues & Solutions

## Issue 1: mod_rewrite Not Enabled

**Symptoms:** Internal Server Error on any URL except test.php

**Solution:**

1. Open WAMP menu
2. Click on **Apache** → **Apache Modules**
3. Make sure **rewrite_module** is checked ✓
4. Restart Apache

**Or manually edit:**
File: `C:\wamp64_3.3.7\bin\apache\apache2.4.62\conf\httpd.conf`

Find this line (around line 140-180):
```apache
#LoadModule rewrite_module modules/mod_rewrite.so
```

Remove the `#` to make it:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

Save and restart Apache.

## Issue 2: Options -Indexes Not Allowed

**Symptoms:** Internal Server Error with message about "Options"

**Solution:**

Your simplified .htaccess is now:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

If this still fails, comment out the RewriteRule line to test.

## Issue 3: PHP Errors

**Symptoms:** You can access test.php but get errors on /

**Solution:**

Check PHP error log:
- WAMP: `C:\wamp64_3.3.7\logs\php_error.log`
- Apache: `C:\wamp64_3.3.7\bin\apache\apache2.4.62\logs\error.log`

Or enable display errors temporarily by adding to `public/.htaccess`:
```apache
php_flag display_errors on
php_value error_reporting E_ALL
```

## Issue 4: File Permissions (Windows)

**Symptoms:** Cannot read files

**Solution:**

Make sure the `www` folder has read permissions:
1. Right-click on `C:\wamp64_3.3.7\www\schooldynamics`
2. Properties → Security
3. Make sure your user has "Read" and "Read & Execute"

## Issue 5: Virtual Host Not Working

**Symptoms:** Gets redirected to localhost or WAMP homepage

**Solution:**

Check your `httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName schooldynamics.local
    ServerAlias *.schooldynamics.local
    DocumentRoot "c:/wamp64_3.3.7/www/schooldynamics/public"
    <Directory "c:/wamp64_3.3.7/www/schooldynamics/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

**Important:** Make sure `httpd-vhosts.conf` is included in main `httpd.conf`:

In `httpd.conf`, find and uncomment (remove #):
```apache
Include conf/extra/httpd-vhosts.conf
```

## Issue 6: Hosts File

**Your hosts file should have:**
```
127.0.0.1 admin.schooldynamics.local
127.0.0.1 demo.schooldynamics.local
```

**Note:** You don't need the `::1` IPv6 entries unless you're using IPv6.

## Testing Steps

### Test 1: Direct PHP Test
```
http://demo.schooldynamics.local/test.php
```
**Expected:** Shows PHP info and file checks

### Test 2: Test Index Directly
```
http://demo.schooldynamics.local/index.php
```
**Expected:** Should show something (error or page)

### Test 3: Test Root URL
```
http://demo.schooldynamics.local/
```
**Expected:** Should redirect through .htaccess to index.php

## Manual Test Without .htaccess

Temporarily rename `.htaccess`:
```bash
cd c:/wamp64_3.3.7/www/schooldynamics/public
move .htaccess .htaccess.disabled
```

Then visit:
```
http://demo.schooldynamics.local/index.php
```

If this works, the issue is with `.htaccess` or mod_rewrite.

## Debug Mode

To see actual PHP errors, edit `.env`:
```ini
APP_DEBUG=true
APP_ENV=local
```

## Check Apache Configuration

Run this command:
```bash
c:\wamp64_3.3.7\bin\apache\apache2.4.62\bin\httpd.exe -t
```

**Expected:** "Syntax OK"

If you get errors, fix them before proceeding.

## Still Not Working?

Try this ultra-simple test file:

Create `public/simple.php`:
```php
<?php
echo "This is working!";
?>
```

Visit: `http://demo.schooldynamics.local/simple.php`

If this doesn't work, WAMP/Apache is not properly configured for this domain.

## WAMP-Specific Fix

### Make Sure WAMP is Online

1. Click WAMP icon in system tray
2. Should be green, not orange/red
3. Click "Put Online" if it's offline

### Allow .htaccess in WAMP

1. WAMP Menu → Apache → `httpd.conf`
2. Search for your Directory directive
3. Make sure it has `AllowOverride All`

### Check Apache Version

Some modules changed names in Apache 2.4+. Make sure your VirtualHost uses:
```apache
Require all granted    # NOT "Order allow,deny / Allow from all"
```

## Last Resort

If nothing works, let's bypass .htaccess entirely:

Edit `public/index.php` to add at the top:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "PHP is executing...<br>";
// rest of file
?>
```

Then visit directly: `http://demo.schooldynamics.local/index.php`

This will show you any PHP errors.

## Get Help

After trying these steps, report:
1. What you see at `/test.php`
2. What you see at `/index.php`
3. Any error messages from Apache logs
4. WAMP version and PHP version
