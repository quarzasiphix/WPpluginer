<?php
/*
Plugin Name: Dynamic Site Plugin & Theme Downloader with Elementor Templates
Description: A plugin to dynamically download and install site plugins, Hello Elementor theme, set up an Elementor header and footer, and create a Home page.
Version: 1.4
Author: Your Name
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define the correct URL for the plugin list from GitHub
define('PLUGIN_LIST_URL', 'https://raw.githubusercontent.com/quarzasiphix/WPpluginer/refs/heads/main/standard-site/plugins.txt');

$site_theme = 'hello-elementor'; // Theme slug for Hello Elementor

// Function to fetch plugin slugs from the GitHub source
if (!function_exists('fetch_plugin_slugs')) {
    function fetch_plugin_slugs() {
        $response = wp_remote_get(PLUGIN_LIST_URL);
        if (is_wp_error($response)) {
            return []; // Return an empty array if the request fails
        }

        $plugin_slugs = array_filter(array_map('trim', explode("\n", wp_remote_retrieve_body($response))));
        return $plugin_slugs;
    }
}

// Function to delete default WordPress plugins "Akismet" and "Hello Dolly"
function delete_default_plugins() {
    // Define the default plugins to delete by their directory names
    $default_plugins = ['akismet', 'hello'];
    
    // Check if each plugin exists, then delete if found
    foreach ($default_plugins as $plugin_slug) {
        $plugin_path = $plugin_slug . '/' . $plugin_slug . '.php';
        if (file_exists(WP_PLUGIN_DIR . '/' . $plugin_path)) {
            delete_plugins([$plugin_path]);
        }
    }
}

// Function to download and install plugins and theme
function download_site_plugins_and_theme($plugins, $theme_slug) {
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/theme-install.php';

    // Initialize the WordPress filesystem API
    WP_Filesystem();

    $upgrader = new Plugin_Upgrader();
    $theme_upgrader = new Theme_Upgrader();

    // Install plugins
    foreach ($plugins as $slug) {
        $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['sections' => false]]);
        if (!is_wp_error($api)) {
            $upgrader->install($api->download_link);
        }
    }

    // Install and activate theme
    $theme_api = themes_api('theme_information', ['slug' => $theme_slug]);
    if (!is_wp_error($theme_api)) {
        $theme_upgrader->install($theme_api->download_link);
        switch_theme($theme_slug);
    }
}

// Function to create header and footer templates in Elementor
function create_elementor_templates() {
    if (class_exists('Elementor\Plugin')) {
        $header_template = [
            'post_title' => 'Header',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'elementor_library',
            'meta_input' => [
                '_elementor_template_type' => 'header',
                '_elementor_conditions' => [
                    [
                        'type' => 'include',
                        'sub_id' => 'all',
                        'sub_name' => 'Entire Site',
                    ],
                ],
            ],
        ];

        $footer_template = [
            'post_title' => 'Footer',
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'elementor_library',
            'meta_input' => [
                '_elementor_template_type' => 'footer',
                '_elementor_conditions' => [
                    [
                        'type' => 'include',
                        'sub_id' => 'all',
                        'sub_name' => 'Entire Site',
                    ],
                ],
            ],
        ];

        // Insert header and footer templates into Elementor library
        wp_insert_post($header_template);
        wp_insert_post($footer_template);
    }
}

// Function to create a blank Home page and set it as the default homepage
function create_and_set_home_page() {
    // Check if a page with the title "Home" already exists
    $home_page = get_page_by_title('Home');
    
    // If the page doesn't exist, create it
    if (!$home_page) {
        // Set up the page data with empty content for Elementor
        $page_data = [
            'post_title'   => 'Home',
            'post_content' => '', // Blank content for Elementor
            'post_status'  => 'publish',
            'post_type'    => 'page'
        ];

        // Insert the page into the database
        $home_page_id = wp_insert_post($page_data);
    } else {
        // If the page exists, get its ID
        $home_page_id = $home_page->ID;
    }

    // Set the created page as the static homepage
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_page_id);
}

// Add admin menu and page
function site_downloader_menu() {
    add_menu_page(
        'Dynamic Site Plugin & Theme Downloader',
        'Dynamic Site Setup',
        'manage_options',
        'dynamic-site-plugin-downloader',
        'site_downloader_page'
    );
}
add_action('admin_menu', 'site_downloader_menu');

// Display the admin page and trigger download and setup
function site_downloader_page() {
    global $site_theme;

    // Fetch the plugin slugs from the GitHub source
    $site_plugins = fetch_plugin_slugs();

    // Display plugins list based on fetched slugs
    echo '<h1>Standard site setup</h1>';
    echo '<form method="post">';
    echo '<input type="submit" name="download_site" class="button button-primary" value="Download Site Plugins, Theme, & Create Templates">';
    echo '</form><br><br>';

    // Trigger plugin and theme download and setup
    if (isset($_POST['download_site'])) {
        delete_default_plugins(); // Delete "Akismet" and "Hello Dolly" plugins
        download_site_plugins_and_theme($site_plugins, $site_theme);
        create_elementor_templates();
        create_and_set_home_page(); // Call the function to create and set the Home page
        echo '<div class="updated"><p>Site plugins, theme, Elementor templates, and Home page setup completed.</p></div>';
    }

    // Get installed plugins and display those that match the fetched slugs
    $all_plugins = get_plugins();
    echo '<h2>Selected Installed Plugins Matching Fetched List</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Plugin</th><th>Version</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

    foreach ($all_plugins as $plugin_file => $plugin_data) {
        // Get the plugin slug based on the file path
        $plugin_slug = dirname($plugin_file);

        // Check if the slug matches the list of fetched plugins
        if (in_array($plugin_slug, $site_plugins)) {
            $is_active = is_plugin_active($plugin_file);
            echo '<tr>';
            echo '<td>' . esc_html($plugin_data['Name']) . '</td>';
            echo '<td>' . esc_html($plugin_data['Version']) . '</td>';
            echo '<td>' . ($is_active ? 'Active' : 'Inactive') . '</td>';
            echo '<td>';
            if ($is_active) {
                echo '<a href="?page=dynamic-site-plugin-downloader&action=deactivate&plugin=' . urlencode($plugin_file) . '" class="button">Deactivate</a> ';
            } else {
                echo '<a href="?page=dynamic-site-plugin-downloader&action=activate&plugin=' . urlencode($plugin_file) . '" class="button">Activate</a> ';
            }
            echo '<a href="?page=dynamic-site-plugin-downloader&action=delete&plugin=' . urlencode($plugin_file) . '" class="button" onclick="return confirm(\'Are you sure you want to delete this plugin?\')">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody></table>';

    // Footer
    echo '<p style="margin-top: 20px; text-align: center;">Made by WebTover, Version 0.1</p>';
}
?>
