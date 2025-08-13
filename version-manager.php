<?php
/**
 * Version Manager for Salon Booking Plugin
 * Helps manage version updates and releases
 */

class Salon_Booking_Version_Manager {
    
    private $plugin_file;
    private $plugin_dir;
    private $current_version;
    
    public function __construct() {
        $this->plugin_file = SALON_BOOKING_PLUGIN_FILE;
        $this->plugin_dir = SALON_BOOKING_PLUGIN_DIR;
        $this->current_version = SALON_BOOKING_VERSION;
        
        // Add admin menu for version management
        add_action('admin_menu', array($this, 'add_version_menu'));
        
        // Add AJAX handlers
        add_action('wp_ajax_salon_booking_check_version', array($this, 'ajax_check_version'));
        add_action('wp_ajax_salon_booking_prepare_release', array($this, 'ajax_prepare_release'));
    }
    
    /**
     * Add version management menu
     */
    public function add_version_menu() {
        add_submenu_page(
            'salon-booking',
            'Version Manager',
            'Version Manager',
            'manage_options',
            'salon-booking-version',
            array($this, 'version_manager_page')
        );
    }
    
    /**
     * Version manager admin page
     */
    public function version_manager_page() {
        $plugin_data = get_plugin_data($this->plugin_file);
        $git_info = $this->get_git_info();
        $changelog = $this->get_changelog();
        
        ?>
        <div class="wrap">
            <h1>Version Manager</h1>
            
            <div class="card">
                <h2>Current Version Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Plugin Version</th>
                        <td><?php echo esc_html($this->current_version); ?></td>
                    </tr>
                    <tr>
                        <th>WordPress Version</th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th>PHP Version</th>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <th>Last Modified</th>
                        <td><?php echo esc_html(date('Y-m-d H:i:s', filemtime($this->plugin_file))); ?></td>
                    </tr>
                </table>
            </div>
            
            <?php if ($git_info): ?>
            <div class="card">
                <h2>Git Information</h2>
                <table class="form-table">
                    <tr>
                        <th>Current Branch</th>
                        <td><?php echo esc_html($git_info['branch']); ?></td>
                    </tr>
                    <tr>
                        <th>Last Commit</th>
                        <td><?php echo esc_html($git_info['commit']); ?></td>
                    </tr>
                    <tr>
                        <th>Commit Message</th>
                        <td><?php echo esc_html($git_info['message']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo esc_html($git_info['status']); ?></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Version Management</h2>
                
                <h3>Release Preparation</h3>
                <p>Prepare a new version release with automated checks and updates.</p>
                
                <form id="version-release-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="new-version">New Version</label></th>
                            <td>
                                <input type="text" id="new-version" name="new_version" 
                                       value="<?php echo esc_attr($this->suggest_next_version()); ?>" 
                                       pattern="\d+\.\d+\.\d+" required>
                                <p class="description">Format: X.Y.Z (e.g., 1.0.1)</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="release-type">Release Type</label></th>
                            <td>
                                <select id="release-type" name="release_type">
                                    <option value="patch">Patch (Bug fixes)</option>
                                    <option value="minor">Minor (New features)</option>
                                    <option value="major">Major (Breaking changes)</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="changelog-entry">Changelog Entry</label></th>
                            <td>
                                <textarea id="changelog-entry" name="changelog_entry" rows="5" cols="50" required>
- Bug fixes and improvements
- Enhanced security
- Performance optimizations</textarea>
                                <p class="description">Describe what's new in this version</p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3>Pre-Release Checks</h3>
                    <div id="pre-release-checks">
                        <p><button type="button" id="run-checks" class="button">Run Pre-Release Checks</button></p>
                        <div id="check-results" style="display: none;"></div>
                    </div>
                    
                    <p>
                        <button type="submit" id="prepare-release" class="button button-primary" disabled>
                            Prepare Release
                        </button>
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2>Changelog</h2>
                <div id="changelog-content">
                    <?php echo $this->format_changelog($changelog); ?>
                </div>
            </div>
            
            <div class="card">
                <h2>Development Status</h2>
                <div class="development-status">
                    <?php echo $this->get_development_status(); ?>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Run pre-release checks
            $('#run-checks').on('click', function() {
                var button = $(this);
                var results = $('#check-results');
                
                button.prop('disabled', true).text('Running checks...');
                results.show().html('<p>Running pre-release checks...</p>');
                
                $.post(ajaxurl, {
                    action: 'salon_booking_check_version',
                    nonce: '<?php echo wp_create_nonce('salon_booking_version'); ?>'
                }, function(response) {
                    if (response.success) {
                        results.html(response.data.html);
                        if (response.data.all_passed) {
                            $('#prepare-release').prop('disabled', false);
                        }
                    } else {
                        results.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                    }
                    button.prop('disabled', false).text('Run Pre-Release Checks');
                });
            });
            
            // Prepare release
            $('#version-release-form').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var button = $('#prepare-release');
                var spinner = $('.spinner');
                
                button.prop('disabled', true);
                spinner.addClass('is-active');
                
                $.post(ajaxurl, {
                    action: 'salon_booking_prepare_release',
                    nonce: '<?php echo wp_create_nonce('salon_booking_version'); ?>',
                    new_version: $('#new-version').val(),
                    release_type: $('#release-type').val(),
                    changelog_entry: $('#changelog-entry').val()
                }, function(response) {
                    if (response.success) {
                        alert('Release prepared successfully! Check the results below.');
                        location.reload();
                    } else {
                        alert('Error preparing release: ' + response.data);
                    }
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                });
            });
        });
        </script>
        
        <style>
        .development-status .status-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .status-item .dashicons {
            margin-right: 10px;
        }
        .status-passed { color: #46b450; }
        .status-failed { color: #dc3232; }
        .status-warning { color: #ffb900; }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for version checks
     */
    public function ajax_check_version() {
        check_ajax_referer('salon_booking_version', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $checks = $this->run_pre_release_checks();
        $all_passed = !in_array(false, array_column($checks, 'passed'));
        
        $html = '<div class="pre-release-results">';
        foreach ($checks as $check) {
            $status_class = $check['passed'] ? 'status-passed' : 'status-failed';
            $icon = $check['passed'] ? 'yes-alt' : 'dismiss';
            
            $html .= '<div class="status-item ' . $status_class . '">';
            $html .= '<span class="dashicons dashicons-' . $icon . '"></span>';
            $html .= '<strong>' . esc_html($check['name']) . '</strong>: ' . esc_html($check['message']);
            $html .= '</div>';
        }
        $html .= '</div>';
        
        wp_send_json_success(array(
            'html' => $html,
            'all_passed' => $all_passed
        ));
    }
    
    /**
     * AJAX handler for preparing release
     */
    public function ajax_prepare_release() {
        check_ajax_referer('salon_booking_version', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $new_version = sanitize_text_field($_POST['new_version']);
        $release_type = sanitize_text_field($_POST['release_type']);
        $changelog_entry = sanitize_textarea_field($_POST['changelog_entry']);
        
        // Validate version format
        if (!preg_match('/^\d+\.\d+\.\d+$/', $new_version)) {
            wp_send_json_error('Invalid version format');
        }
        
        // Update version in files
        $result = $this->update_version_in_files($new_version);
        
        if ($result) {
            // Update changelog
            $this->update_changelog($new_version, $changelog_entry, $release_type);
            
            wp_send_json_success('Version updated successfully');
        } else {
            wp_send_json_error('Failed to update version in files');
        }
    }
    
    /**
     * Run pre-release checks
     */
    private function run_pre_release_checks() {
        $checks = array();
        
        // Check for PHP errors
        $checks[] = array(
            'name' => 'PHP Syntax Check',
            'passed' => $this->check_php_syntax(),
            'message' => $this->check_php_syntax() ? 'No PHP syntax errors found' : 'PHP syntax errors detected'
        );
        
        // Check for required files
        $checks[] = array(
            'name' => 'Required Files',
            'passed' => $this->check_required_files(),
            'message' => $this->check_required_files() ? 'All required files present' : 'Missing required files'
        );
        
        // Check database tables
        $checks[] = array(
            'name' => 'Database Tables',
            'passed' => $this->check_database_tables(),
            'message' => $this->check_database_tables() ? 'Database tables exist' : 'Database tables missing'
        );
        
        // Check for test files
        $checks[] = array(
            'name' => 'Test Files Cleanup',
            'passed' => $this->check_no_test_files(),
            'message' => $this->check_no_test_files() ? 'No test files found' : 'Test files should be removed'
        );
        
        return $checks;
    }
    
    /**
     * Check PHP syntax
     */
    private function check_php_syntax() {
        $files = $this->get_php_files();
        foreach ($files as $file) {
            $output = array();
            $return_var = 0;
            exec("php -l \"$file\" 2>&1", $output, $return_var);
            if ($return_var !== 0) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check required files exist
     */
    private function check_required_files() {
        $required_files = array(
            'salon-booking-plugin.php',
            'includes/class-salon-booking.php',
            'includes/class-activator.php',
            'includes/class-deactivator.php',
            'admin/class-admin.php',
            'public/class-public.php'
        );
        
        foreach ($required_files as $file) {
            if (!file_exists($this->plugin_dir . $file)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check database tables exist
     */
    private function check_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'salon_services',
            $wpdb->prefix . 'salon_staff',
            $wpdb->prefix . 'salon_bookings'
        );
        
        foreach ($tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if ($result !== $table) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Check no test files exist
     */
    private function check_no_test_files() {
        $test_files = array(
            'test-wordpress-booking.php',
            'simple-test.php',
            'test-booking-page.php',
            'manual-test.php',
            'simple-ajax-test.php',
            'client-demo.php'
        );
        
        foreach ($test_files as $file) {
            if (file_exists($this->plugin_dir . $file)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get all PHP files in plugin
     */
    private function get_php_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->plugin_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Suggest next version number
     */
    private function suggest_next_version() {
        $parts = explode('.', $this->current_version);
        $parts[2] = (int)$parts[2] + 1; // Increment patch version
        return implode('.', $parts);
    }
    
    /**
     * Update version in plugin files
     */
    private function update_version_in_files($new_version) {
        // Update main plugin file
        $main_file = file_get_contents($this->plugin_file);
        $main_file = preg_replace('/Version: [\d\.]+/', 'Version: ' . $new_version, $main_file);
        $main_file = preg_replace('/define\(\s*[\'"]SALON_BOOKING_VERSION[\'"]\s*,\s*[\'"][\d\.]+[\'"]\s*\)/', 
                                 "define('SALON_BOOKING_VERSION', '$new_version')", $main_file);
        
        return file_put_contents($this->plugin_file, $main_file) !== false;
    }
    
    /**
     * Update changelog
     */
    private function update_changelog($version, $entry, $type) {
        $changelog_file = $this->plugin_dir . 'CHANGELOG.md';
        $date = date('Y-m-d');
        
        $new_entry = "## [$version] - $date\n\n### " . ucfirst($type) . "\n$entry\n\n";
        
        if (file_exists($changelog_file)) {
            $content = file_get_contents($changelog_file);
            $content = str_replace('# Changelog\n\n', "# Changelog\n\n$new_entry", $content);
        } else {
            $content = "# Changelog\n\n$new_entry";
        }
        
        file_put_contents($changelog_file, $content);
    }
    
    /**
     * Get git information
     */
    private function get_git_info() {
        if (!is_dir($this->plugin_dir . '.git')) {
            return false;
        }
        
        $info = array();
        
        // Get current branch
        $branch = exec('cd ' . escapeshellarg($this->plugin_dir) . ' && git branch --show-current 2>/dev/null');
        $info['branch'] = $branch ?: 'unknown';
        
        // Get last commit
        $commit = exec('cd ' . escapeshellarg($this->plugin_dir) . ' && git log -1 --format="%H" 2>/dev/null');
        $info['commit'] = substr($commit ?: 'unknown', 0, 8);
        
        // Get commit message
        $message = exec('cd ' . escapeshellarg($this->plugin_dir) . ' && git log -1 --format="%s" 2>/dev/null');
        $info['message'] = $message ?: 'unknown';
        
        // Get status
        $status = exec('cd ' . escapeshellarg($this->plugin_dir) . ' && git status --porcelain 2>/dev/null');
        $info['status'] = empty($status) ? 'Clean' : 'Modified files';
        
        return $info;
    }
    
    /**
     * Get changelog content
     */
    private function get_changelog() {
        $changelog_file = $this->plugin_dir . 'CHANGELOG.md';
        if (file_exists($changelog_file)) {
            return file_get_contents($changelog_file);
        }
        return "# Changelog\n\nNo changelog available.";
    }
    
    /**
     * Format changelog for display
     */
    private function format_changelog($content) {
        // Convert markdown to HTML (basic)
        $content = esc_html($content);
        $content = preg_replace('/^# (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h4>$1</h4>', $content);
        $content = preg_replace('/^### (.+)$/m', '<h5>$1</h5>', $content);
        $content = preg_replace('/^- (.+)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);
        $content = nl2br($content);
        
        return $content;
    }
    
    /**
     * Get development status
     */
    private function get_development_status() {
        $status_items = array(
            array(
                'name' => 'Core Functionality',
                'status' => 'passed',
                'message' => 'Booking system, payments, notifications implemented'
            ),
            array(
                'name' => 'Security Review',
                'status' => 'warning',
                'message' => 'Requires comprehensive security audit'
            ),
            array(
                'name' => 'Performance Testing',
                'status' => 'warning',
                'message' => 'Needs load testing and optimization'
            ),
            array(
                'name' => 'Documentation',
                'status' => 'warning',
                'message' => 'User manual and API docs needed'
            ),
            array(
                'name' => 'Production Ready',
                'status' => 'failed',
                'message' => 'Complete PRODUCTION-ROADMAP.md items first'
            )
        );
        
        $html = '';
        foreach ($status_items as $item) {
            $icon = $item['status'] === 'passed' ? 'yes-alt' : 
                   ($item['status'] === 'warning' ? 'warning' : 'dismiss');
            
            $html .= '<div class="status-item status-' . $item['status'] . '">';
            $html .= '<span class="dashicons dashicons-' . $icon . '"></span>';
            $html .= '<strong>' . esc_html($item['name']) . '</strong>: ' . esc_html($item['message']);
            $html .= '</div>';
        }
        
        return $html;
    }
}

// Initialize version manager in admin
if (is_admin()) {
    new Salon_Booking_Version_Manager();
}
?>