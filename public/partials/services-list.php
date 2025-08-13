<?php
/**
 * Services list shortcode template
 *
 * This file is used to display the services list via shortcode
 *
 * @package SalonBooking
 * @subpackage SalonBooking/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get shortcode attributes
$category = $atts['category'] ?? '';
$limit = intval($atts['limit'] ?? -1);
$show_price = $atts['show_price'] === 'true';
$show_duration = $atts['show_duration'] === 'true';

// Get services
$service_manager = new Salon_Booking_Service_Manager();

// Get active services only
$services = $service_manager->get_services(true);

// Apply category filter if specified
if (!empty($category)) {
    $services = array_filter($services, function($service) use ($category) {
        return $service->category === $category;
    });
}

// Apply limit if specified
if ($limit > 0) {
    $services = array_slice($services, 0, $limit);
}

if (empty($services)) {
    echo '<p class="salon-booking-no-services">' . __('No services available at the moment.', 'salon-booking-plugin') . '</p>';
    return;
}

$currency_symbol = get_option('salon_booking_currency_symbol', 'R');
?>

<div class="salon-booking-services-list">
    <?php if (!empty($category)): ?>
        <h3 class="salon-booking-services-category-title"><?php echo esc_html($category); ?></h3>
    <?php endif; ?>
    
    <div class="salon-booking-services-grid">
        <?php foreach ($services as $service): ?>
            <div class="salon-booking-service-card" data-service-id="<?php echo esc_attr($service->id); ?>">
                <div class="salon-booking-service-header">
                    <h4 class="salon-booking-service-name"><?php echo esc_html($service->name); ?></h4>
                    <?php if (!empty($service->category)): ?>
                        <span class="salon-booking-service-category"><?php echo esc_html($service->category); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($service->description)): ?>
                    <div class="salon-booking-service-description">
                        <p><?php echo esc_html($service->description); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="salon-booking-service-details">
                    <?php if ($show_duration): ?>
                        <div class="salon-booking-service-duration">
                            <span class="salon-booking-service-label"><?php _e('Duration:', 'salon-booking-plugin'); ?></span>
                            <span class="salon-booking-service-value"><?php echo esc_html($service->duration); ?> <?php _e('minutes', 'salon-booking-plugin'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($show_price): ?>
                        <div class="salon-booking-service-price">
                            <span class="salon-booking-service-label"><?php _e('Price:', 'salon-booking-plugin'); ?></span>
                            <span class="salon-booking-service-value"><?php echo esc_html($currency_symbol . number_format($service->price, 2)); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="salon-booking-service-actions">
                    <button type="button" class="salon-booking-book-service-btn" data-service-id="<?php echo esc_attr($service->id); ?>">
                        <?php _e('Book Now', 'salon-booking-plugin'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.salon-booking-services-list {
    margin: 20px 0;
}

.salon-booking-services-category-title {
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #e1e1e1;
    padding-bottom: 10px;
}

.salon-booking-services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.salon-booking-service-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.salon-booking-service-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.salon-booking-service-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.salon-booking-service-name {
    margin: 0;
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
}

.salon-booking-service-category {
    background: #f8f9fa;
    color: #6c757d;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.salon-booking-service-description {
    margin-bottom: 15px;
}

.salon-booking-service-description p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

.salon-booking-service-details {
    margin-bottom: 20px;
}

.salon-booking-service-duration,
.salon-booking-service-price {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.salon-booking-service-label {
    font-weight: 500;
    color: #333;
}

.salon-booking-service-value {
    color: #666;
}

.salon-booking-service-price .salon-booking-service-value {
    font-weight: 600;
    color: #28a745;
    font-size: 1.1em;
}

.salon-booking-service-actions {
    text-align: center;
}

.salon-booking-book-service-btn {
    background: #007cba;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
    font-weight: 500;
    transition: background-color 0.3s ease;
    width: 100%;
}

.salon-booking-book-service-btn:hover {
    background: #005a87;
}

.salon-booking-no-services {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .salon-booking-services-grid {
        grid-template-columns: 1fr;
    }
    
    .salon-booking-service-header {
        flex-direction: column;
        gap: 10px;
    }
}
</style>