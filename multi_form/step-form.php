<?php
class MultiStepFormPlugin
{
    public function __construct()
    {
        // $this->export_data_to_csv();
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('multi_step_form2', array($this, 'render_form_shortcode'));
        add_action('wp_ajax_submit_form', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_submit_form', array($this, 'handle_form_submission'));
        add_action('admin_menu',  array($this, 'my_cool_plugin_create_menu'));
        add_action('admin_post_export_csv', [$this, 'handle_export']);
        add_action('admin_post_nopriv_export_csv', [$this, 'handle_export']);
    }

    public function register_custom_post_type()
    {
        register_post_type('form_submission', array(
            'labels' => array(
                'name' => __('Form Submissions'),
                'singular_name' => __('Form Submission')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
        ));
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('multi_step_form_script', plugins_url('/js/multi-step-form.js', __FILE__), array('jquery'), null, true);
        wp_enqueue_style('multi_step_form_style', plugins_url('/css/multi-step-form.css', __FILE__));
        wp_localize_script('multi_step_form_script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function my_cool_plugin_create_menu()
    {
        //create new top-level menu     
        add_menu_page('My Plugin Settings', 'Plugin Settings', 'manage_options', 'my_plugin', [$this, 'my_plugin_settings_page'], plugins_url('/img/one.png', __FILE__), 10);
        add_submenu_page('my_plugin', 'CSV File Uploader', 'CSV File Uploader', 'manage_options', 'csv_file_uploader', [$this, 'csv_file_uploader_page']);
        add_submenu_page('my_plugin', 'Export', 'Export', 'manage_options', 'export_csv_filedata', [$this, 'export_data_to_csv']);
    }
    //custom setting page    
    public function my_plugin_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings if form is submitted
        if (isset($_POST['submit'])) {
            update_option('custom_setting_1', sanitize_text_field($_POST['custom_setting_1']));
            update_option('custom_setting_2', sanitize_text_field($_POST['custom_setting_2']));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        // Retrieve existing settings
        $custom_setting_1 = get_option('custom_setting_1', '');
        $custom_setting_2 = get_option('custom_setting_2', '');

?>
        <div class="wrap">
            <h1>Custom Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('save_custom_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Site Title</th>
                        <td><input type="text" name="custom_setting_1" value="<?php echo esc_attr($custom_setting_1); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Tagline</th>
                        <td><input type="text" name="custom_setting_2" value="<?php echo esc_attr($custom_setting_2); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php
    }
    //import data from csv to database
    public function csv_file_uploader_page()
    {
    ?>
        <div class="wrap">
            <h1>Upload CSV File</h1>
            <form method="post" enctype="multipart/form-data" action="">
                <input type="file" name="csv_file" accept=".csv" required>
                <?php submit_button('Upload CSV'); ?>
            </form>
        </div>
    <?php

        // Handle CSV upload
        $this->handle_csv_upload();
    }
    public function handle_csv_upload()
    {
        if (isset($_FILES['csv_file']) && !empty($_FILES['csv_file']['tmp_name'])) {
            // Check if the file is a CSV
            $file_type = wp_check_filetype(basename($_FILES['csv_file']['name']));
            if ($file_type['ext'] !== 'csv') {
                echo '<div class="notice notice-error"><p>Invalid file type. Please upload a CSV file.</p></div>';
                return;
            }

            // Move the uploaded file to a temporary location
            $csv_file = $_FILES['csv_file']['tmp_name'];
            // return $csv_file;

            // Process the CSV data
            $this->process_csv_data($csv_file);
        }
    }
    public function process_csv_data($csv_file)
    {
        global $wpdb;

        if (($handle = fopen($csv_file, 'r')) !== FALSE) {
            // Get the first row, which contains the column headers
            $headers = fgetcsv($handle, 1000, ',');

            // $data_array = [];
            // Loop through the rows and process the data
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $row = [];
                foreach ($headers as $index => $header_key_value) {
                    $row[$header_key_value] = isset($data[$index]) ? $data[$index] : "";
                }
                // $data_array[] = $row;
                $wpdb->insert($wpdb->prefix . "my_custom2_table", $row);
            }

            fclose($handle); // Close the file
            echo 'Data imported successfully!';
        } else {
            echo 'Please upload a CSV file.';
        }
        // echo '<pre>';
        // print_r( $data_array);
        // die;
    }
    //export data from database to csv
    public function export_data_to_csv()
    {
        global $wpdb;

        // Define the table name
        $table_name =  $wpdb->prefix . 'my_custom2_table'; // Replace with your table name

        // Fetch the data
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

        if (empty($results)) {
            wp_die('No data found in the table.');
        }

        // Set the headers to force download
        if (isset($_POST['export_csv'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=data-export.csv');

            // Open output stream
            $output = fopen('php://output', 'w');

            // Output the column 
            fputcsv($output, array_keys($results[0]));

            // Output the data rows
            foreach ($results as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit();
        }
    ?>
        <div class="wrap">
            <h2>Export Data to CSV</h2>
            <form method="post" action="">
                <input type="submit" name="export_csv" value="Export Data to CSV" class="button button-primary"/>
            </form>
        </div>
    <?php
        // Handle the export if the button is clicked
        $this->handle_export();
    }
    public function handle_export()
    {
        if (isset($_POST['export_csv'])) {
            $this->export_data_to_csv();
        }
    }

    public function render_form_shortcode()
    {
        ob_start();
    ?>
        <form id="multi-step-form">
            <div class="step step-1">
                <h2> Personal Information</h2>
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
                <span class="error-message" id="first_name_error"></span><br>

                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
                <span class="error-message" id="last_name_error"></span><br>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
                <span class="error-message" id="email_error"></span><br>

                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
                <span class="error-message" id="address_error"></span><br>

                <label for="country">Country</label>
                <input type="text" id="country" name="country" required>
                <span class="error-message" id="country_error"></span><br>

                <label for="state">State</label>
                <input type="text" id="state" name="state" required>
                <span class="error-message" id="state_error"></span><br>

                <label for="city">City</label>
                <input type="text" id="city" name="city" required>
                <span class="error-message" id="city_error"></span><br>

                <br><br>
                <button type="button" class="next">Next</button>
            </div>

            <div class="step step-2">
                <h2>Company Info</h2>
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" required>
                <span class="error-message" id="company_name_error"></span><br>

                <div id="company_address_container">
                    <div class="company_address">
                        <label for="company_address">Company Address</label>
                        <input type="text" name="company_address[]" required>
                        <span class="error-message" id="company_address_error"></span><br>

                        <br><br>
                        <button type="button" class="add_address">Add Address</button>
                    </div>
                </div>
                <br>
                <button type="button" class="next">Next</button>
                <button type="button" class="prev">Previous</button>
            </div>

            <div class="step step-3">
                <h2> Card Info</h2>
                <label for="card_number">Card Number</label>
                <input type="text" id="card_number" name="card_number" required>
                <span class="error-message" id="card_number_error"></span><br>

                <label for="expiry_date">Expiry Date</label>
                <input type="text" id="expiry_date" name="expiry_date" required>
                <span class="error-message" id="expiry_date_error"></span><br>

                <label for="cvv">CVV</label>
                <input type="text" id="cvv" name="cvv" required>
                <span class="error-message" id="cvv_error"></span><br>
                <br>
                <button type="submit">Submit</button>
                <button type="button" class="prev">Previous</button>
                <br><br>
                <div id="message"></div>
                <div id="loader" class="loder-one" style="display: none;"><img src="<?php echo plugins_url('/img/Reload.gif', __FILE__) ?>" alt="Loading..." width="100" height="100"></div>
            </div>
        </form>
<?php
        return ob_get_clean();
    }

    public function handle_form_submission()
    {
        $response = array('status' => 'error', 'message' => 'Form submission failed.');

        if (isset($_POST['first_name']) && isset($_POST['last_name'])) {
            $post_id = wp_insert_post(array(
                'post_title' => sanitize_text_field($_POST['first_name']) . ' ' . sanitize_text_field($_POST['last_name']),
                'post_type' => 'form_submission',
                'post_content' => json_encode(array(
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'email' => sanitize_email($_POST['email']),
                    'address' => sanitize_text_field($_POST['address']),
                    'country' => sanitize_text_field($_POST['country']),
                    'state' => sanitize_text_field($_POST['state']),
                    'city' => sanitize_text_field($_POST['city']),
                    'company_name' => sanitize_text_field($_POST['company_name']),
                    'company_address' => array_map('sanitize_text_field', $_POST['company_address']),
                    'card_number' => sanitize_text_field($_POST['card_number']),
                    'expiry_date' => sanitize_text_field($_POST['expiry_date']),
                    'cvv' => sanitize_text_field($_POST['cvv']),
                )),
                'post_status' => 'publish',

            ));
            if ($post_id) {
                update_post_meta($post_id, 'first_name', sanitize_text_field($_POST['first_name']));
                update_post_meta($post_id, 'last_name', sanitize_text_field($_POST['last_name']));
                update_post_meta($post_id, 'email', sanitize_email($_POST['email']));
                update_post_meta($post_id, 'address', sanitize_text_field($_POST['address']));
                update_post_meta($post_id, 'country', sanitize_text_field($_POST['country']));
                update_post_meta($post_id, 'state', sanitize_text_field($_POST['state']));
                update_post_meta($post_id, 'city', sanitize_text_field($_POST['city']));
                update_post_meta($post_id, 'company_name', sanitize_text_field($_POST['company_name']));
                update_post_meta($post_id, 'company_address', array_map('sanitize_text_field', $_POST['company_address']));
                update_post_meta($post_id, 'card_number', sanitize_text_field($_POST['card_number']));
                update_post_meta($post_id, 'expiry_date', sanitize_text_field($_POST['expiry_date']));
                update_post_meta($post_id, 'cvv', sanitize_text_field($_POST['cvv']));

                $response = array('status' => 'success', 'message' => 'Form submitted successfully.');
            }
        }

        echo json_encode($response);
        wp_die();
    }
}

new MultiStepFormPlugin();
