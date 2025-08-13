# Content Security Policy (CSP) Fix for Stripe Integration

## Issue Description

When using the Salon Booking Plugin with Stripe payment integration, you may encounter a Content Security Policy (CSP) error in the browser console:

```
Refused to load the script 'https://gc.kis.v2.scr.kaspersky-labs.com/...' because it violates the following Content Security Policy directive: "script-src https://m.stripe.network 'sha256-...' 'sha256-...'"
```

This error occurs because:
1. Stripe's JavaScript SDK loads additional resources from `m.stripe.network` and other Stripe domains
2. Some antivirus software (like Kaspersky) may inject scripts that conflict with strict CSP policies
3. The existing CSP policy is too restrictive for Stripe's requirements

## Solution Implemented

We've implemented a comprehensive CSP fix that:

### 1. Automatic CSP Headers
- Automatically adds appropriate CSP headers when Stripe functionality is needed
- Only applies to pages that use the booking system (booking page, shortcode pages, admin pages)
- Uses WordPress `send_headers` action for better compatibility

### 2. Allowed Domains
The CSP policy now allows these Stripe domains:
- `https://js.stripe.com` - Main Stripe JavaScript SDK
- `https://m.stripe.network` - Stripe's additional resources
- `https://*.stripe.network` - All Stripe network subdomains
- `https://api.stripe.com` - Stripe API endpoints
- `https://hooks.stripe.com` - Stripe webhooks

### 3. Meta Tag Fallback
- Also adds CSP via meta tags in the HTML head for additional compatibility
- Includes debugging comments to help identify when CSP headers are added

### 4. Customization Support
- Provides a filter `salon_booking_csp_policy` to allow customization of the CSP policy
- Checks for existing CSP headers to avoid conflicts

## Usage

The fix is automatically applied when:
- Viewing the designated booking page
- Viewing any page with the `[salon_booking]` shortcode
- Accessing admin pages related to the booking plugin

## Customization

Developers can customize the CSP policy using the provided filter:

```php
add_filter('salon_booking_csp_policy', function($policy) {
    // Add additional domains or modify the policy
    return $policy . '; font-src https://fonts.googleapis.com';
});
```

## Troubleshooting

### If CSP errors persist:
1. Check browser console for specific blocked resources
2. Verify that the booking page is properly configured
3. Look for the debug comment in page source: `<!-- Salon Booking Plugin: CSP headers added for Stripe compatibility -->`
4. Check if another plugin or theme is setting conflicting CSP headers

### For antivirus conflicts:
- The CSP now includes common antivirus domains (Kaspersky, Avast, AVG, Norton, McAfee)
- If you see errors from other antivirus software, you can add their domains using the filter
- Example: `add_filter('salon_booking_csp_policy', function($policy) { return str_replace('script-src', 'script-src https://your-antivirus-domain.com', $policy); });`

## Technical Details

### Files Modified:
- `public/class-public.php` - Added meta tag CSP headers and local FullCalendar CSS
- `admin/class-admin.php` - Updated to use local FullCalendar CSS
- `salon-booking-plugin.php` - Added HTTP header CSP support with CDN domains
- `public/css/vendor/fullcalendar.min.css` - Local FullCalendar CSS fallback

### Functions Added:
- `salon_booking_add_csp_headers()` - Main CSP header function
- `salon_booking_needs_stripe()` - Detects when Stripe is needed
- `salon_booking_has_existing_csp()` - Checks for existing CSP headers
- `add_stripe_csp_headers()` - Meta tag CSP implementation
- `is_booking_page()` - Identifies booking-related pages

### Issues Resolved:
- ✅ Stripe CSP blocking errors eliminated
- ✅ Kaspersky and other antivirus script conflicts resolved
- ✅ FullCalendar CORS/ORB blocking issues fixed with local CSS fallback
- ✅ JavaScript AJAX object loading improved
- ✅ CSP inline script violations resolved with proper Kaspersky WebSocket support
- ✅ FullCalendar CSS loading from unpkg.com CDN now allowed in CSP
- ✅ salon_booking_ajax object now always available (moved localization outside conditional loading)

### Console Errors Fixed:
- ✅ `Refused to execute inline script because it violates CSP directive` - Added Kaspersky WebSocket domains
- ✅ `Refused to load stylesheet from unpkg.com` - Added unpkg.com to CSP style-src directive
- ✅ `salon_booking_ajax object not loaded` - Moved script localization to always execute

### Known Non-Critical Issues:
- WordPress `@wordpress/interactivity` module error - This is a WordPress core issue unrelated to the plugin
- Browser `net::ERR_ABORTED` errors - These are browser/network related and don't affect functionality

This fix ensures that Stripe payment processing and FullCalendar display work correctly while maintaining security through appropriate Content Security Policy headers.