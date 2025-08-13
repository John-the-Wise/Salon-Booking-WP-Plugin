<?php
/**
 * Plugin Update System
 * Handles automatic updates for the Salon Booking Plugin
 */

class Salon_Booking_Updater {
    
    private $plugin_slug;
    private $version;
    private $plugin_path;
    private $plugin_file;
    private $github_username;
    private $github_repo;
    
    public function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = SALON_BOOKING_VERSION;
        $this->plugin_path = plugin_dir_path($plugin_file);
        $this->github_username = 'John-the-Wise'; // Updated to your GitHub username
        $this->github_repo = 'Salon-Booking-WP-Plugin'; // Updated to your repository name
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->get_plugin_info_url(),
                'package' => $this->get_download_url()
            );
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub releases
     */
    private function get_remote_version() {
        $request = wp_remote_get("https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest");
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (isset($data['tag_name'])) {
                return ltrim($data['tag_name'], 'v'); // Remove 'v' prefix if present
            }
        }
        
        return $this->version; // Return current version if unable to fetch
    }
    
    /**
     * Get plugin information for the update popup
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        return (object) array(
            'name' => 'Salon Booking Plugin',
            'slug' => dirname($this->plugin_slug),
            'version' => $remote_version,
            'author' => 'Your Name',
            'homepage' => 'https://github.com/' . $this->github_username . '/' . $this->github_repo,
            'short_description' => 'Professional salon booking and appointment management system.',
            'sections' => array(
                'description' => 'A comprehensive booking system for salons and beauty businesses.',
                'changelog' => $this->get_changelog()
            ),
            'download_link' => $this->get_download_url(),
            'requires' => '5.0',
            'tested' => '6.4',
            'requires_php' => '7.4'
        );
    }
    
    /**
     * Get download URL for the latest version
     */
    private function get_download_url() {
        return "https://github.com/{$this->github_username}/{$this->github_repo}/archive/refs/heads/main.zip";
    }
    
    /**
     * Get plugin info URL
     */
    private function get_plugin_info_url() {
        return "https://github.com/{$this->github_username}/{$this->github_repo}";
    }
    
    /**
     * Get changelog from GitHub releases
     */
    private function get_changelog() {
        $request = wp_remote_get("https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases");
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $releases = json_decode($body, true);
            
            $changelog = '';
            foreach (array_slice($releases, 0, 5) as $release) { // Show last 5 releases
                $changelog .= '<h4>Version ' . ltrim($release['tag_name'], 'v') . '</h4>';
                $changelog .= '<p>' . wp_kses_post($release['body']) . '</p>';
            }
            
            return $changelog;
        }
        
        return '<p>No changelog available.</p>';
    }
}

// Initialize the updater
if (is_admin()) {
    new Salon_Booking_Updater(__FILE__);
}

/**
 * Manual update check function
 */
function salon_booking_check_for_updates() {
    delete_site_transient('update_plugins');
    wp_update_plugins();
}

/**
 * Add update check to admin menu
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'salon-booking',
        'Check for Updates',
        'Updates',
        'manage_options',
        'salon-booking-updates',
        'salon_booking_updates_page'
    );
});

/**
 * Updates page
 */
function salon_booking_updates_page() {
    if (isset($_POST['check_updates'])) {
        salon_booking_check_for_updates();
        echo '<div class="notice notice-success"><p>Update check completed!</p></div>';
    }
    
    $current_version = SALON_BOOKING_VERSION;
    $plugin_data = get_plugin_data(SALON_BOOKING_PLUGIN_FILE);
    
    ?>
    <div class="wrap">
        <h1>Plugin Updates</h1>
        
        <div class="card">
            <h2>Current Version Information</h2>
            <table class="form-table">
                <tr>
                    <th>Plugin Name</th>
                    <td><?php echo esc_html($plugin_data['Name']); ?></td>
                </tr>
                <tr>
                    <th>Current Version</th>
                    <td><?php echo esc_html($current_version); ?></td>
                </tr>
                <tr>
                    <th>Author</th>
                    <td><?php echo esc_html($plugin_data['Author']); ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><?php echo esc_html($plugin_data['Description']); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="card">
            <h2>Update Management</h2>
            <p>Check for the latest version of the Salon Booking Plugin.</p>
            
            <form method="post">
                <?php wp_nonce_field('salon_booking_updates'); ?>
                <p>
                    <input type="submit" name="check_updates" class="button button-primary" value="Check for Updates">
                </p>
            </form>
            
            <h3>Automatic Updates</h3>
            <p>The plugin will automatically check for updates from the configured repository. Make sure to:</p>
            <ul>
                <li>✅ Keep regular backups of your site</li>
                <li>✅ Test updates on a staging environment first</li>
                <li>✅ Review changelog before updating</li>
            </ul>
        </div>
        
        <div class="card">
            <h2>Version History</h2>
            <h4>Version 1.0.0 (Current)</h4>
            <ul>
                <li>✅ Initial release</li>
                <li>✅ Service management</li>
                <li>✅ Staff management</li>
                <li>✅ Booking system</li>
                <li>✅ Calendar integration</li>
                <li>✅ Payment processing (Stripe)</li>
                <li>✅ Email notifications</li>
                <li>✅ Admin dashboard</li>
            </ul>
            
            <h4>Planned Updates</h4>
            <ul>
                <li>🔄 Enhanced calendar features</li>
                <li>🔄 SMS notifications</li>
                <li>🔄 Multi-location support</li>
                <li>🔄 Advanced reporting</li>
                <li>🔄 Customer portal</li>
                <li>🔄 Mobile app integration</li>
            </ul>
        </div>
    </div>
    <?php
}
?>