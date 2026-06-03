# EasyPass - Bus Pass Management System

A comprehensive, dynamic bus pass management system built with PHP, MySQL, HTML, CSS, and JavaScript. This system provides complete CRUD functionality, email notifications, payment processing, and a modern, responsive user interface.

## Features

### 🔐 Security Features
- **Secure Authentication**: Password hashing, session management, and rate limiting
- **Input Validation**: Comprehensive validation and sanitization
- **SQL Injection Protection**: Prepared statements throughout
- **Session Security**: IP and user agent validation
- **Activity Logging**: Complete audit trail of user actions

### 🎫 Pass Management
- **Dynamic Pass Creation**: Multiple pass types (Daily, Monthly, Six Month, Annual)
- **Real-time Status**: Active, expired, cancelled, suspended states
- **QR Code Generation**: Unique QR codes for each pass
- **Auto-renewal Options**: Configurable automatic renewal

### 💳 Payment System
- **Multiple Payment Methods**: Credit/Debit cards, UPI, Net Banking, Mobile Wallets
- **Mock Payment Gateway**: Simulated payment processing with success rates
- **Payment History**: Complete transaction tracking
- **Receipt Generation**: Automated payment confirmations

### 📧 Notification System
- **Email Notifications**: Pass expiry reminders, payment confirmations
- **SMS/WhatsApp Ready**: Framework for SMS and WhatsApp integration
- **Automated Cron Jobs**: Daily notification processing
- **Customizable Preferences**: User-controlled notification settings

### 📊 Dashboard & Analytics
- **Real-time Statistics**: Trip counts, spending, active passes
- **Dynamic Data**: Live updates without page refresh
- **Trip Management**: Record and track bus journeys
- **Pass Display**: Digital pass with QR code

### 🎨 Modern UI/UX
- **Responsive Design**: Mobile-first approach
- **Clean Interface**: Modern, professional design
- **Intuitive Navigation**: Easy-to-use interface
- **Accessibility**: Screen reader friendly

## Installation

### Prerequisites
- XAMPP (Apache, MySQL, PHP 7.4+)
- Web browser
- Text editor (optional)

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   # If using Git
   git clone <repository-url>
   
   # Or download and extract the ZIP file
   ```

2. **Setup XAMPP**
   - Start Apache and MySQL services in XAMPP Control Panel
   - Open phpMyAdmin (http://localhost/phpmyadmin)

3. **Database Setup**
   - Create a new database named `bus_pass_db`
   - The system will automatically create all required tables on first run

4. **File Placement**
   - Place the project folder in `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - Ensure the folder is named `minor project` or rename it to your preference

5. **Access the Application**
   - Open your browser and navigate to `http://localhost/minor project/`
   - The system will automatically set up the database schema

## Database Schema

The system includes the following tables:

### Core Tables
- **users**: User accounts with enhanced security fields
- **pass_types**: Available pass types and pricing
- **user_passes**: User's purchased passes
- **routes**: Bus routes and stops
- **trips**: Trip history and tracking
- **payments**: Payment transactions
- **notifications**: System notifications

### Security Tables
- **login_attempts**: Rate limiting and security tracking
- **user_activity_log**: Complete audit trail

### System Tables
- **system_settings**: Application configuration

## Configuration

### Email Settings
Update email configuration in `includes/email_notifications.php`:
```php
// Configure SMTP settings for production
$headers = [
    'From: noreply@easypass.com',
    'Reply-To: support@easypass.com',
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/html; charset=UTF-8'
];
```

### Cron Job Setup
Set up automated notifications by adding to crontab:
```bash
# Daily at 9 AM
0 9 * * * /usr/bin/php /path/to/project/cron/notification_cron.php
```

### Security Configuration
- Update session settings in `auth_check.php`
- Configure rate limiting in `login.php`
- Set up proper file permissions

## File Structure

```
minor project/
├── auth_check.php              # Authentication and security
├── db_connect.php              # Database connection and schema
├── dashboard.php               # Main dashboard
├── login.php                   # User login
├── signup.php                  # User registration
├── logout.php                  # Secure logout
├── passes.php                  # Pass management
├── payment.php                 # Payment processing
├── trips.php                   # Trip management
├── settings.php                # User settings
├── includes/
│   ├── functions.php           # Utility functions
│   └── email_notifications.php # Email system
├── cron/
│   └── notification_cron.php  # Automated notifications
├── styles/
│   ├── dashboard.css          # Dashboard styles
│   ├── login.css              # Login/signup styles
│   └── styles.css             # Main styles
└── README.md                  # This file
```

## Usage Guide

### For Users

1. **Registration**
   - Visit the homepage
   - Click "Sign Up" to create an account
   - Complete the registration form with valid information

2. **Login**
   - Use your username/email and password
   - System includes rate limiting for security

3. **Purchase Pass**
   - Navigate to "Pass Management"
   - Choose your preferred pass type
   - Complete payment process

4. **View Dashboard**
   - See your active passes
   - View trip history
   - Check payment records

5. **Record Trips**
   - Use "Trip Management" to record journeys
   - Track your travel patterns
   - View spending statistics

### For Administrators

1. **Database Management**
   - Use phpMyAdmin to manage data
   - Monitor user activity through logs
   - Update system settings

2. **Notification Management**
   - Configure email settings
   - Set up cron jobs for automation
   - Monitor notification delivery

## Security Features

### Authentication
- Secure password hashing with PHP's `password_hash()`
- Session regeneration on login
- IP address and user agent validation
- Rate limiting for login attempts

### Data Protection
- Prepared statements for all database queries
- Input sanitization and validation
- XSS protection with `htmlspecialchars()`
- SQL injection prevention

### Session Management
- Secure session configuration
- Session timeout handling
- Automatic logout on security violations
- Activity logging for audit trails

## API Endpoints

### Authentication
- `POST /login.php` - User login
- `POST /signup.php` - User registration
- `GET /logout.php` - User logout

### Pass Management
- `GET /passes.php` - View and purchase passes
- `POST /passes.php` - Create new pass

### Payment Processing
- `POST /payment.php` - Process payments
- `GET /payment.php` - Payment form

### Trip Management
- `GET /trips.php` - View trip history
- `POST /trips.php` - Record new trip

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL is running in XAMPP
   - Check database name in `db_connect.php`
   - Verify MySQL credentials

2. **Email Notifications Not Working**
   - Check PHP mail configuration
   - Verify SMTP settings
   - Test with a simple mail() function

3. **Session Issues**
   - Check PHP session configuration
   - Ensure proper file permissions
   - Clear browser cookies

4. **Cron Job Not Running**
   - Verify cron job syntax
   - Check file paths
   - Ensure PHP executable path is correct

### Debug Mode
Enable debug mode by adding to the top of PHP files:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions:
- Email: support@easypass.com
- Documentation: Check this README
- Issues: Create an issue in the repository

## Changelog

### Version 1.0.0
- Initial release
- Complete CRUD functionality
- Email notification system
- Payment processing
- Security implementation
- Responsive design

## Future Enhancements

- [ ] Mobile app integration
- [ ] Real-time GPS tracking
- [ ] Advanced analytics
- [ ] Multi-language support
- [ ] API for third-party integration
- [ ] Advanced reporting features

---

**EasyPass** - Making public transportation easier and more accessible for everyone.