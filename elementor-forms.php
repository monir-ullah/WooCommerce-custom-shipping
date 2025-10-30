<?php
/*
Plugin Name: Elementor Form Data Viewer
Description: Save Elementor form submissions into a custom database table and display them in a custom admin panel.
Version: 1.0
Author: Monir Ullah
*/

if (!defined('ABSPATH'))
    exit;

// -----------------------------
// 1️⃣ Create custom table on plugin activation
// -----------------------------
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elementor_form_data';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        form_name varchar(255) NOT NULL,
        name varchar(255) DEFAULT '',
        email varchar(255) DEFAULT '',
        message text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// -----------------------------
// 2️⃣ Capture Elementor Form submission
// -----------------------------
add_action('elementor_pro/forms/new_record', function ($record, $handler) {
    if ('form' !== $record->get_form_settings('form_type')) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'elementor_form_data';

    $form_name = $record->get_form_settings('form_name');
    $fields = [];
    foreach ($record->get('fields') as $id => $field) {
        $fields[$id] = $field['value'];
    }

    // Save basic data (you can add more fields as needed)
    $wpdb->insert($table, [
        'form_name' => $form_name,
        'name' => $fields['name'] ?? '',
        'email' => $fields['email'] ?? '',
        'message' => $fields['message'] ?? '',
    ]);
}, 10, 2);

// -----------------------------
// 3️⃣ Add admin menu page to view data
// -----------------------------
add_action('admin_menu', function () {
    add_menu_page(
        'Form Data',
        'Form Data',
        'manage_options',
        'elementor-form-data-viewer',
        'efdv_render_admin_page',
        'dashicons-feedback',
        26
    );
});

// -----------------------------
// 4️⃣ Render admin page with table view
// -----------------------------
function efdv_render_admin_page()
{
    global $wpdb;
    $table = $wpdb->prefix . 'elementor_form_data';
    $results = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

    echo '<div class="wrap"><h1>Elementor Form Submissions</h1>';

    if (empty($results)) {
        echo '<p>No form data found yet.</p></div>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Form Name</th><th>Name</th><th>Email</th><th>Message</th><th>Date</th></tr></thead><tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->id) . '</td>';
        echo '<td>' . esc_html($row->form_name) . '</td>';
        echo '<td>' . esc_html($row->name) . '</td>';
        echo '<td>' . esc_html($row->email) . '</td>';
        echo '<td>' . esc_html($row->message) . '</td>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}
