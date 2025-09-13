# Wedding Invitation Platform - Wevitation Clone

A comprehensive digital wedding invitation platform built with PHP, MySQL, Tailwind CSS, and Flowbite components. This platform allows users to create beautiful, responsive wedding invitations with modern features.

## 🌟 Features

### Core Features
- **User Registration & Authentication** - Secure user accounts with login/logout
- **Multi-tier Subscription Plans** - Free, Premium, and Business plans
- **Beautiful Wedding Invitations** - Multiple themes and customization options
- **Guest Management** - Add and manage wedding guest lists
- **RSVP System** - Guests can confirm attendance online
- **Guest Messages** - Collect wishes and congratulations from guests
- **Digital Gifts** - Accept digital envelopes/gifts via bank transfer or e-wallet
- **Photo & Video Gallery** - Showcase wedding photos and videos
- **Live Streaming Integration** - Share live stream links for remote guests
- **Background Music** - Add romantic background music to invitations
- **Mobile Responsive** - Perfect on all devices
- **SEO Optimized** - Social media sharing ready

### Technical Features
- **Clean URLs** - SEO-friendly invitation links
- **File Upload System** - Secure image and audio uploads
- **Theme System** - Easy theme customization and management
- **Admin Dashboard** - Comprehensive user management
- **Security Headers** - XSS protection, clickjacking prevention
- **Caching & Compression** - Optimized performance

## 🛠️ Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Composer (optional, for additional packages)

### Step 1: Download Files
Clone or download all the files to your web server directory.

### Step 2: Database Setup
1. Create a new MySQL database named `wedding_invitation_platform`
2. Import the database schema by running the SQL commands in `wedding_database.sql`
3. The database will be automatically created with all necessary tables

### Step 3: Configuration
1. Open `config.php` and update the database credentials:
```php
define('DB_HOST', 'your_host');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'wedding_invitation_platform');
```

2. Update the site URL:
```php
define('SITE_URL', 'http://your-domain.com');
```

### Step 4: Directory Permissions
Create and set proper permissions for upload directories:
```bash
mkdir uploads
mkdir uploads/covers
mkdir uploads/gallery
mkdir uploads/music
mkdir uploads/qrcodes
chmod 755 uploads/
chmod 755 uploads/covers/
chmod 755 uploads/gallery/
chmod 755 uploads/music/
chmod 755 uploads/qrcodes/
```

### Step 5: Sample Data (Optional)
To populate the database with sample data including a demo user:
1. Run `php sample_data.php` from command line, or
2. Access `your-domain.com/sample_data.php` in browser (then delete this file)

### Step 6: Security
1. After installation, delete or secure the `sample_data.php` file
2. Update the `.htaccess` file rules as needed
3. Consider enabling HTTPS in production

## 🎯 Usage

### Demo Account
After running sample data insertion:
- **Username:** demo
- **Password:** demo123
- **Sample Invitation:** `/invitation/john-jane-demo`

### Creating Your First Invitation
1. Register a new account or login
2. Go to Dashboard
3. Click "Buat Undangan Baru" (Create New Invitation)
4. Fill in all required details
5. Choose a theme
6. Upload cover image and background music (optional)
7. Save and share your invitation URL

### Managing Guests
1. From Dashboard, go to "Kelola Tamu" (Manage Guests)
2. Add guests manually or import from CSV
3. Send invitation links via WhatsApp or email
4. Track RSVP responses

## 📁 File Structure

```
wedding-platform/
├── config.php              # Database configuration
├── index.php               # Landing page
├── register.php            # User registration
├── login.php               # User login
├── logout.php              # Logout functionality
├── dashboard.php           # User dashboard
├── create-invitation.php   # Create new invitation
├── invitation.php          # Invitation display page
├── 404.php                 # 404 error page
├── .htaccess               # URL rewriting & security
├── sample_data.php         # Sample data insertion
├── uploads/                # File uploads directory
│   ├── covers/            # Invitation cover images
│   ├── gallery/           # Photo/video gallery
│   ├── music/             # Background music files
│   └── qrcodes/           # QR codes for digital gifts
└── assets/                 # Static assets
    ├── themes/            # Invitation themes
    └── images/            # Default images
```

## 🎨 Customization

### Adding New Themes
1. Create CSS file in `/assets/themes/your-theme/`
2. Add preview image
3. Insert theme record in `themes` table:
```sql
INSERT INTO themes (name, preview_image, css_file, is_premium) 
VALUES ('Your Theme', '/assets/themes/your-theme/preview.jpg', 'your-theme.css', 0);
```

### Customizing Colors
The platform uses Tailwind CSS with custom color scheme:
- Primary: Pink (`#ec4899`)
- Secondary: Purple (`#8b5cf6`)
- Success: Green (`#10b981`)
- Warning: Yellow (`#f59e0b`)

## 📱 Subscription Plans

### Free Plan
- 1 invitation
- Active for 1 month
- 50 guest limit
- 10 photos in gallery
- Basic themes only

### Premium Plan
- 1 invitation  
- Active forever
- Unlimited guests
- Unlimited gallery
- All premium themes
- Priority support

### Business Plan
- 2 invitations
- Active forever
- All premium features
- White label option
- Priority support

## 🔧 Advanced Configuration

### Email Settings
Add SMTP configuration in `config.php`:
```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USER', 'your-email@domain.com');
define('SMTP_PASS', 'your-smtp-password');
```

### File Upload Limits
Adjust in `config.php`:
```php
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
```

### Social Login
Integrate OAuth providers by adding API keys in `config.php`.

## 🚀 Performance Optimization

### Production Setup
1. Enable HTTPS redirect in `.htaccess`
2. Set up proper caching headers
3. Optimize database queries
4. Use CDN for static assets
5. Enable Gzip compression

### Database Optimization
- Add indexes for frequently queried fields
- Regular database cleanup
- Consider database connection pooling

## 🛡️ Security Considerations

### Implemented Security
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- File upload validation and restrictions
- Session security with proper timeout
- Password hashing with PHP's password_hash()

### Additional Recommendations
- Regular security updates
- Backup strategy implementation
- Rate limiting for login attempts
- Two-factor authentication (future enhancement)

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials in `config.php`
- Ensure MySQL service is running
- Verify database exists and user has proper permissions

**File Upload Issues**
- Check directory permissions (755 recommended)
- Verify PHP upload settings in php.ini
- Ensure file size within limits

**URL Rewriting Not Working**
- Verify Apache mod_rewrite is enabled
- Check `.htaccess` file permissions
- Confirm AllowOverride is enabled in Apache config

**Theme Not Loading**
- Verify theme files exist in correct directory
- Check file permissions
- Clear browser cache

## 📈 Future Enhancements

### Planned Features
- Mobile app for guests
- QR code guest check-in
- Advanced analytics and reporting
- Multi-language support
- Integration with wedding vendors
- Advanced theme customization
- Email invitation system
- Payment gateway integration

### Contributing
This is a demonstration project. For production use, consider:
- Adding comprehensive error handling
- Implementing unit tests
- Adding API endpoints
- Database migration system
- Advanced logging system

## 📄 License

This project is created for educational and demonstration purposes. Please ensure proper licensing for production use.

## 🤝 Support

For support and questions:
- Check the troubleshooting section
- Review the code comments
- Create detailed issue reports with:
  - PHP version
  - MySQL version  
  - Error messages
  - Steps to reproduce

## 🎉 Credits

Built with:
- PHP & MySQL for backend
- Tailwind CSS for styling
- Flowbite for UI components
- Font Awesome for icons
- Google Fonts for typography

---

**Note:** This is a comprehensive wedding invitation platform similar to Wevitation. Ensure you have proper hosting, security measures, and legal compliance before deploying to production.