<?php
/*
Plugin Name: Multi-Step Form2
Description: A plugin to create a multi-step form with validation and save data to a custom post type.
Version: 1.0
Author: raman verma
*/

defined('ABSPATH') or die('No script kiddies please!');

require plugin_dir_path(__FILE__) . 'step-form.php';

register_activation_hook(__FILE__, 'my_plugin_create_table');
my_plugin_create_table();
function my_plugin_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_custom2_table'; // Adjust the table name as needed

    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(255) NOT NULL,
        identifier text NOT NULL,
        `first_name` varchar(255) NOT NULL,
        `last_name` varchar(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


register_deactivation_hook(__FILE__, 'my_plugin_remove_table');

function my_plugin_remove_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'my_custom2_table';
    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
}
