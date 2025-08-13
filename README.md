# Salon Booking WordPress Plugin

A comprehensive booking system for salons and beauty businesses with appointment management, staff scheduling, and payment processing.

## Features

### 🗓️ Booking Management
- **Real-time calendar** with FullCalendar integration
- **Service selection** with duration and pricing
- **Staff assignment** with availability management
- **Time slot booking** with conflict prevention
- **Customer information** collection and management

### 💳 Payment Processing
- **Stripe integration** for secure payments
- **Multiple payment methods** (cards, digital wallets)
- **Booking deposits** and full payments
- **Payment confirmation** and receipts

### 📧 Communication
- **Email notifications** for bookings, confirmations, and reminders
- **Customizable templates** for different notification types
- **Admin notifications** for new bookings
- **Customer confirmations** with booking details

### 👥 Staff Management
- **Staff profiles** with services and availability
- **Working hours** configuration
- **Service assignments** per staff member
- **Availability calendar** management

### 🛠️ Admin Dashboard
- **Booking overview** with calendar view
- **Service management** (CRUD operations)
- **Staff management** with scheduling
- **Settings configuration** for payments and notifications
- **Reports and analytics** (planned)

## Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- SSL certificate (for payment processing)

### Manual Installation
1. Download the plugin files
2. Upload to `/wp-content/plugins/salon-booking-plugin/`
3. Activate the plugin through WordPress admin
4. Configure settings in **Salon Booking** menu

### WordPress.org Installation
*Coming soon - plugin will be submitted to WordPress repository*

## Configuration

### Initial Setup
1. **Services**: Add your salon services with pricing and duration
2. **Staff**: Create staff profiles and assign services
3. **Payment**: Configure Stripe API keys for payment processing
4. **Email**: Set up SMTP settings for notifications
5. **Booking Page**: Create a page with `[salon_booking_form]` shortcode

### Shortcodes
- `[salon_booking_form]` - Complete booking form with calendar
- `[salon_services]` - Display services list
- `[salon_staff]` - Display staff members

### Payment Setup
1. Create a Stripe account at https://stripe.com
2. Get your API keys from Stripe dashboard
3. Add keys in **Salon Booking → Settings → Payments**
4. Test with Stripe test mode before going live

## Development

### Project Structure
```
salon-booking-plugin/
├── admin/                 # Admin interface
├── includes/             # Core classes
├── public/               # Frontend functionality
├── salon-booking-plugin.php  # Main plugin file
├── update-system.php     # Auto-update system
└── version-manager.php   # Version management
```

### Key Classes
- `Salon_Booking_Plugin` - Main plugin class
- `Salon_Booking_Admin` - Admin interface
- `Salon_Booking_Public` - Frontend functionality
- `Salon_Booking_Database` - Database operations
- `Salon_Booking_Payment_Handler` - Payment processing
- `Salon_Booking_Email_Handler` - Email notifications

### Database Tables
- `wp_salon_services` - Service definitions
- `wp_salon_staff` - Staff members and availability
- `wp_salon_bookings` - Booking records
- `wp_salon_payments` - Payment transactions

## API Endpoints

### AJAX Endpoints
- `salon_booking_get_services` - Fetch available services
- `salon_booking_get_staff` - Fetch staff members
- `salon_booking_get_availability` - Check time slot availability
- `salon_booking_create_booking` - Create new booking
- `salon_booking_process_payment` - Process payment

### REST API
*Planned for future versions*

## Security

### Implemented Security Measures
- **Input validation** and sanitization
- **SQL injection prevention** with prepared statements
- **XSS protection** with proper output escaping
- **CSRF protection** with WordPress nonces
- **User capability checks** for admin functions
- **Secure payment processing** with Stripe

### Security Best Practices
- Always use HTTPS for payment processing
- Keep WordPress and plugins updated
- Use strong passwords for admin accounts
- Regular security audits recommended

## Updates

### Automatic Updates
The plugin includes an automatic update system that:
- Checks for new releases on GitHub
- Shows updates in WordPress admin
- Allows one-click updates
- Maintains version history

### Manual Updates
1. Download latest release from GitHub
2. Deactivate current plugin
3. Replace plugin files
4. Reactivate plugin

## Support

### Documentation
- [Production Roadmap](PRODUCTION-ROADMAP.md)
- [Deployment Guide](DEPLOYMENT-GUIDE.md)
- [Changelog](CHANGELOG.md)

### Getting Help
- **Issues**: [GitHub Issues](https://github.com/John-the-Wise/Salon-Booking-WP-Plugin/issues)
- **Discussions**: [GitHub Discussions](https://github.com/John-the-Wise/Salon-Booking-WP-Plugin/discussions)
- **Email**: support@yourwebsite.com

### Contributing
Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.

## Roadmap

### Current Status: Development/Testing Phase
The plugin is functional but requires completion of production roadmap items before live deployment.

### Upcoming Features
- SMS notifications
- Multi-location support
- Advanced reporting
- Customer portal
- Mobile app integration
- Third-party calendar sync

### Production Timeline
- **Phase 1**: Security audit and testing (2-4 weeks)
- **Phase 2**: Beta testing and optimization (2-3 weeks)
- **Phase 3**: WordPress.org submission and public release

---

**⚠️ Important**: This plugin is currently in development/testing phase. Complete the [Production Roadmap](PRODUCTION-ROADMAP.md) before deploying to live sites, especially for payment processing functionality.

**Made with ❤️ for the beauty industry**