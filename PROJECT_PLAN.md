# UR Beautiful Salon Booking Plugin - Project Plan

## Overview
A comprehensive WordPress booking system for UR Beautiful Beauty Salon to replace the current WhatsApp "Book Now" link with a full-featured booking platform.

## Core Requirements
1. Client-facing booking form with interactive calendar
2. Service selection system
3. Staff management (scalable for future additions)
4. Stripe payment integration for upfront fees
5. Admin dashboard with calendar view
6. Email notification system
7. Integration with Astra theme and Elementor

## File Structure

```
salon-booking-plugin/
├── salon-booking-plugin.php              # Main plugin file
├── includes/
│   ├── class-salon-booking-plugin.php    # Main plugin class
│   ├── class-activator.php               # Plugin activation
│   ├── class-deactivator.php             # Plugin deactivation
│   ├── class-database.php                # Database operations
│   ├── class-booking-manager.php         # Booking logic
│   ├── class-staff-manager.php           # Staff management
│   ├── class-service-manager.php         # Service management
│   ├── class-payment-handler.php         # Stripe integration
│   ├── class-email-notifications.php     # Email system
│   └── class-calendar-helper.php         # Calendar utilities
├── admin/
│   ├── class-admin.php                   # Admin dashboard
│   ├── partials/
│   │   ├── admin-dashboard.php           # Main admin page
│   │   ├── bookings-calendar.php         # Calendar view
│   │   ├── staff-management.php          # Staff CRUD
│   │   ├── service-management.php        # Service CRUD
│   │   └── settings.php                  # Plugin settings
│   └── css/
│       └── admin-styles.css              # Admin styling
├── public/
│   ├── class-public.php                  # Frontend functionality
│   ├── partials/
│   │   ├── booking-form.php              # Main booking form
│   │   ├── calendar-widget.php           # Interactive calendar
│   │   ├── service-selector.php          # Service selection
│   │   ├── payment-form.php              # Payment interface
│   │   └── booking-confirmation.php      # Success page
│   ├── css/
│   │   └── public-styles.css             # Frontend styling
│   └── js/
│       ├── booking-calendar.js           # Calendar functionality
│       ├── booking-form.js               # Form validation
│       └── payment-handler.js            # Payment processing
├── assets/
│   ├── css/
│   │   └── shared-styles.css             # Shared styles
│   ├── js/
│   │   └── shared-scripts.js             # Shared JavaScript
│   └── images/
│       └── icons/                        # Plugin icons
└── languages/
    └── salon-booking-plugin.pot           # Translation template
```

## Database Schema

### 1. Bookings Table (`wp_salon_bookings`)
```sql
CREATE TABLE wp_salon_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    client_email VARCHAR(100) NOT NULL,
    client_phone VARCHAR(20),
    service_id INT NOT NULL,
    staff_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    duration INT NOT NULL, -- in minutes
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_intent_id VARCHAR(255), -- Stripe payment intent ID
    total_amount DECIMAL(10,2) NOT NULL,
    upfront_fee DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES wp_salon_services(id),
    FOREIGN KEY (staff_id) REFERENCES wp_salon_staff(id)
);
```

### 2. Services Table (`wp_salon_services`)
```sql
CREATE TABLE wp_salon_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT NOT NULL, -- in minutes
    price DECIMAL(10,2) NOT NULL,
    upfront_fee DECIMAL(10,2) NOT NULL,
    category VARCHAR(50), -- Nails, Waxing, Massages, Threading, Brows & Lashes, Facials
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Staff Table (`wp_salon_staff`)
```sql
CREATE TABLE wp_salon_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    specialties TEXT, -- JSON array of service categories
    working_hours TEXT, -- JSON object with schedule
    is_active BOOLEAN DEFAULT TRUE,
    is_owner BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 4. Staff Availability Table (`wp_salon_staff_availability`)
```sql
CREATE TABLE wp_salon_staff_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    reason VARCHAR(255), -- for unavailable slots
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES wp_salon_staff(id),
    UNIQUE KEY unique_staff_date_time (staff_id, date, start_time)
);
```

### 5. Email Templates Table (`wp_salon_email_templates`)
```sql
CREATE TABLE wp_salon_email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(50) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables TEXT, -- JSON array of available variables
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Implementation Phases

### Phase 1: Core Infrastructure
1. Set up main plugin structure
2. Create database tables and activation hooks
3. Implement basic admin dashboard
4. Create service management system

### Phase 2: Staff Management
1. Staff CRUD operations
2. Availability management
3. Working hours configuration

### Phase 3: Booking System
1. Frontend booking form
2. Interactive calendar
3. Time slot availability checking
4. Basic booking creation

### Phase 4: Payment Integration
1. Stripe API integration
2. Payment form implementation
3. Payment status tracking
4. Webhook handling

### Phase 5: Notifications
1. Email template system
2. Automated notifications
3. Confirmation emails
4. Reminder system

### Phase 6: Admin Features
1. Booking calendar view
2. Booking management
3. Reporting and analytics
4. Settings configuration

### Phase 7: Frontend Integration
1. Astra theme compatibility
2. Elementor widget creation
3. Responsive design implementation
4. UR Beautiful brand styling

## Design Considerations

### Styling Reference
Based on the UR Beautiful website (https://urbeautiful.co.za/), the design should incorporate:
- Elegant, modern aesthetic
- Warm color palette (golds, creams, soft pinks)
- Clean typography
- Professional imagery
- Mobile-responsive design
- Smooth animations and transitions

### Security Features
- Input sanitization and validation
- CSRF protection
- SQL injection prevention
- Secure payment processing
- Data encryption for sensitive information

### Performance Optimization
- Efficient database queries
- Caching for availability checks
- Minified CSS/JS assets
- Lazy loading for calendar data
- AJAX for dynamic interactions

## Integration Points

### WordPress Integration
- Custom post types for bookings
- WordPress user roles and capabilities
- WordPress hooks and filters
- WordPress REST API endpoints

### Third-party Integrations
- Stripe Payment Gateway
- WordPress email system
- Calendar libraries (FullCalendar.js)
- Form validation libraries

## Testing Strategy

### Unit Testing
- Database operations
- Booking logic
- Payment processing
- Email notifications

### Integration Testing
- Frontend-backend communication
- Payment gateway integration
- Email delivery
- Calendar functionality

### User Acceptance Testing
- Booking flow testing
- Admin dashboard usability
- Mobile responsiveness
- Cross-browser compatibility

## Deployment Checklist

1. Database migration scripts
2. Default data seeding (services, staff)
3. Email template setup
4. Stripe configuration
5. WordPress permissions setup
6. SSL certificate verification
7. Backup procedures
8. Monitoring setup

## Future Enhancements

1. SMS notifications
2. Online rescheduling
3. Loyalty program integration
4. Multi-location support
5. Advanced reporting
6. Mobile app integration
7. Social media integration
8. Review and rating system

This comprehensive plan provides a solid foundation for building a production-ready booking system that meets all requirements while being scalable for future growth.