# Installation & Setup Guide

## System Requirements

- **Web Server**: Apache 2.4+ with mod_rewrite enabled
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB 10.2+)
- **Browser**: Modern browser (Chrome, Firefox, Safari, Edge)
- **Internet Connection**: Required for CDN resources (Bootstrap, jQuery, Font Awesome)

## Installation Steps

### Step 1: Extract Project Files

1. Download the project files
2. Extract to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\fishing_boat_catch_earnings_management_system\
   ```
   
   Or for Linux:
   ```
   /var/www/html/fishing_boat_catch_earnings_management_system/
   ```

### Step 2: Create Database

1. Open XAMPP Control Panel and ensure MySQL is running (start it if not)
2. Open your browser and go to: `http://localhost/phpmyadmin`
3. Log in (default username: `root`, password: empty)
4. Click on the **"Database"** tab at the top
5. Under "Create database", enter:
   - **Database name**: `fishing_management`
   - **Charset**: Select `utf8mb4_general_ci`
6. Click **"Create"** button

### Step 3: Import Database Schema

1. In phpMyAdmin, click on the newly created `fishing_management` database
2. Click on the **"Import"** tab
3. Click on the **"Choose File"** button
4. Navigate to your project folder and select **`database.sql`**
5. Scroll down and click **"Go"** button
6. Wait for the import to complete (should show success message)

### Step 4: Configure Database Connection (Optional)

If your MySQL credentials are different from default:

1. Open file: `config/config.php`
2. Update these lines:
   ```php
   define('DB_HOST', 'localhost');    // Your MySQL host
   define('DB_USER', 'root');         // Your MySQL username
   define('DB_PASS', '');             // Your MySQL password
   define('DB_NAME', 'fishing_management'); // Database name
   ```
3. Save the file

### Step 5: Set Folder Permissions

**For Windows (usually not needed):**
- Right-click folder → Properties
- Security tab → Edit → Give Full Control

**For Linux/Mac:**
```bash
chmod 755 /var/www/html/fishing_boat_catch_earnings_management_system/
chmod 644 /var/www/html/fishing_boat_catch_earnings_management_system/*.php
```

### Step 6: Start the Application

1. Open your web browser
2. Go to: `http://localhost/fishing_boat_catch_earnings_management_system/`
3. You will be redirected to the login page

## 🔐 First Login

### Admin Account
```
Username: admin
Password: password
```

### Staff Account
```
Username: staff
Password: password
```

> **⚠️ Important**: Change these passwords immediately after first login!

### Change Password (for future use)

**Current system note**: This version uses simple password comparison. To properly hash passwords:

1. In `login.php`, uncomment the password_verify line:
   ```php
   // if ($password === 'password' || password_verify($password, $user['password'])) {
   if (password_verify($password, $user['password'])) {
   ```

2. To update existing passwords with hashing:
   ```sql
   UPDATE users SET password = '$2y$10$YourHashedPasswordHere' WHERE username = 'admin';
   ```

3. Or use PHP to generate the hash:
   ```php
   echo password_hash('your_new_password', PASSWORD_BCRYPT);
   ```

## 📋 Initial Setup Checklist

After successful login:

- [ ] Login with admin credentials
- [ ] Go to Master Data > Boats and add at least one boat
- [ ] Go to Master Data > Fishermen and add at least 3-5 fishermen
- [ ] Go to Master Data > Fish Types and add fish varieties
- [ ] (Optional) Go to Master Data > Users and create staff accounts
- [ ] Test Staff module by logging in as staff user
- [ ] Create a boat trip and enter catch data
- [ ] View reports to verify everything works

## 🧪 Testing the Application

### Admin Panel Test
1. Login as admin
2. Navigate to Dashboard - should show statistics
3. Go to Boats menu - should show list of boats
4. Try adding a new boat
5. Try editing and deleting boats

### Staff Panel Test
1. Login as staff
2. Go to Daily Operations > Create New Trip
3. Create a trip for a boat
4. Assign fishermen to the trip
5. Record catch entries
6. Add expenses
7. View reports

### Report Testing
1. From Admin or Staff, go to Reports
2. Select various date ranges
3. Test filters
4. Try printing reports

## 🔧 Troubleshooting

### "Connection failed" Error
**Solution:**
- Ensure MySQL is running
- Check credentials in `config/config.php`
- Verify database `fishing_management` exists
- Check MySQL user permissions

### "Table doesn't exist" Error
**Solution:**
- Reimport the `database.sql` file
- Make sure you imported into the correct database
- Check for import errors in phpMyAdmin

### "Blank white page" or "500 Error"
**Solution:**
- Check PHP error logs
- Ensure PHP 7.4+ is installed
- Check file permissions
- Enable error reporting in `config.php`:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```

### "Session not working"
**Solution:**
- Check if sessions folder has write permissions
- Session path: `C:\xampp\tmp\` (Windows) or `/tmp/` (Linux)
- Restart Apache/XAMPP

### "CSS not loading" or "Page looks broken"
**Solution:**
- Check browser console for errors
- Verify Bootstrap CDN is accessible (internet required)
- Clear browser cache (Ctrl+F5)
- Check file paths in `includes/header.php`

## 🚀 Performance Tips

1. **Database Optimization**:
   ```sql
   -- Add indexes for frequently queried columns
   CREATE INDEX idx_trip_date ON boat_trips(trip_date);
   CREATE INDEX idx_boat_date ON boat_trips(boat_id, trip_date);
   ```

2. **Backup Database**:
   - Regular backups via phpMyAdmin
   - Export as SQL file monthly
   - Store in secure location

3. **Clear Old Data**:
   - Archive old records to separate database
   - Keep current year data active
   - Delete temporary files

## 📊 Database Backup & Restore

### Create Backup
1. Open phpMyAdmin
2. Select `fishing_management` database
3. Click **"Export"** tab
4. Select **"SQL"** format
5. Click **"Go"** to download backup file
6. Store the file safely

### Restore from Backup
1. Open phpMyAdmin
2. Select or create target database
3. Click **"Import"** tab
4. Choose your backup SQL file
5. Click **"Go"**

## 🔐 Security Recommendations

1. **Change Default Passwords**: Update admin and staff passwords immediately
2. **Use HTTPS**: Implement SSL certificate (HTTPS) in production
3. **Strong Passwords**: Use minimum 12 characters, mix of upper/lower/numbers/symbols
4. **Regular Updates**: Keep PHP, MySQL, and browser updated
5. **Database User**: Create separate MySQL user with limited privileges
6. **Backup**: Regular automated backups of database
7. **Access Control**: Use firewall rules to restrict access

## 🌐 Deployment to Production

1. **Get a domain** and hosting with PHP 7.4+ and MySQL support
2. **Use HTTPS** certificate (Let's Encrypt is free)
3. **Update config.php** with production database credentials
4. **Change database passwords** to strong ones
5. **Enable PHP opcode cache** (OpCache) for performance
6. **Set proper file permissions**: 
   ```bash
   chmod 755 directories
   chmod 644 files
   ```
7. **Disable error display** in production:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   ini_set('log_errors', 1);
   ```
8. **Setup automated backups**
9. **Monitor server logs** regularly

## 📞 Support & Help

- Check README.md for feature documentation
- Review code comments in PHP files
- Check database structure in database.sql
- Look at error logs in `/logs/` folder (if exists)

## 🎓 Learning Resources

- **PHP**: https://www.php.net/manual/
- **MySQL**: https://dev.mysql.com/doc/
- **Bootstrap**: https://getbootstrap.com/docs/5.3/
- **jQuery**: https://api.jquery.com/

---

**Version**: 1.0  
**Last Updated**: December 2025  
**Status**: Ready for Production
