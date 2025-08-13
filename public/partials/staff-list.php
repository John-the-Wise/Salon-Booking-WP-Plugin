<?php
/**
 * Staff list shortcode template
 *
 * This file is used to display the staff list via shortcode
 *
 * @package SalonBooking
 * @subpackage SalonBooking/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get shortcode attributes
$show_specialties = $atts['show_specialties'] === 'true';
$show_contact = $atts['show_contact'] === 'true';

// Get staff
$staff_manager = new Salon_Booking_Staff_Manager();
$staff_members = $staff_manager->get_staff(true);

if (empty($staff_members)) {
    echo '<p class="salon-booking-no-staff">' . __('No staff members available at the moment.', 'salon-booking-plugin') . '</p>';
    return;
}
?>

<div class="salon-booking-staff-list">
    <div class="salon-booking-staff-grid">
        <?php foreach ($staff_members as $staff): ?>
            <div class="salon-booking-staff-card" data-staff-id="<?php echo esc_attr($staff->id); ?>">
                <div class="salon-booking-staff-header">
                    <h4 class="salon-booking-staff-name"><?php echo esc_html($staff->name); ?></h4>
                    <?php if ($staff->is_owner): ?>
                        <span class="salon-booking-staff-badge salon-booking-owner-badge"><?php _e('Owner', 'salon-booking-plugin'); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_specialties && !empty($staff->specialties)): ?>
                    <div class="salon-booking-staff-specialties">
                        <h5 class="salon-booking-specialties-title"><?php _e('Specialties:', 'salon-booking-plugin'); ?></h5>
                        <div class="salon-booking-specialties-list">
                            <?php 
                            $specialties = is_array($staff->specialties) ? $staff->specialties : json_decode($staff->specialties, true);
                            if (is_array($specialties)):
                                foreach ($specialties as $specialty): ?>
                                    <span class="salon-booking-specialty-tag"><?php echo esc_html($specialty); ?></span>
                                <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_contact): ?>
                    <div class="salon-booking-staff-contact">
                        <?php if (!empty($staff->email)): ?>
                            <div class="salon-booking-contact-item">
                                <span class="salon-booking-contact-label"><?php _e('Email:', 'salon-booking-plugin'); ?></span>
                                <a href="mailto:<?php echo esc_attr($staff->email); ?>" class="salon-booking-contact-value">
                                    <?php echo esc_html($staff->email); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($staff->phone)): ?>
                            <div class="salon-booking-contact-item">
                                <span class="salon-booking-contact-label"><?php _e('Phone:', 'salon-booking-plugin'); ?></span>
                                <a href="tel:<?php echo esc_attr($staff->phone); ?>" class="salon-booking-contact-value">
                                    <?php echo esc_html($staff->phone); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="salon-booking-staff-actions">
                    <button type="button" class="salon-booking-book-staff-btn" data-staff-id="<?php echo esc_attr($staff->id); ?>">
                        <?php _e('Book with', 'salon-booking-plugin'); ?> <?php echo esc_html($staff->name); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.salon-booking-staff-list {
    margin: 20px 0;
}

.salon-booking-staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.salon-booking-staff-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.3s ease;
}

.salon-booking-staff-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.salon-booking-staff-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.salon-booking-staff-name {
    margin: 0;
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
}

.salon-booking-staff-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.salon-booking-owner-badge {
    background: #ffd700;
    color: #333;
}

.salon-booking-staff-specialties {
    margin-bottom: 15px;
}

.salon-booking-specialties-title {
    margin: 0 0 8px 0;
    font-size: 1em;
    font-weight: 500;
    color: #333;
}

.salon-booking-specialties-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.salon-booking-specialty-tag {
    background: #e9ecef;
    color: #495057;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 500;
}

.salon-booking-staff-contact {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.salon-booking-contact-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.salon-booking-contact-item:last-child {
    margin-bottom: 0;
}

.salon-booking-contact-label {
    font-weight: 500;
    color: #333;
}

.salon-booking-contact-value {
    color: #007cba;
    text-decoration: none;
}

.salon-booking-contact-value:hover {
    text-decoration: underline;
}

.salon-booking-staff-actions {
    text-align: center;
}

.salon-booking-book-staff-btn {
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

.salon-booking-book-staff-btn:hover {
    background: #005a87;
}

.salon-booking-no-staff {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .salon-booking-staff-grid {
        grid-template-columns: 1fr;
    }
    
    .salon-booking-staff-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .salon-booking-contact-item {
        flex-direction: column;
        gap: 4px;
    }
}
</style>