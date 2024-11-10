<?php
/*
Plugin Name: Site Plugin & Theme Downloader with Elementor Templates
Description: A plugin to download and install general site plugins, Hello Elementor theme, and set up an Elementor header and footer.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define general site plugins and theme to download
$site_plugins = [
    'elementor',
    'wordpress-seo',     // Yoast SEO
    'contact-form-7',
    'jetpack',
];

$site_theme = 'hello-elementor'; // Theme slug for Hello Elementor

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

// Add admin menu and page
function site_downloader_menu() {
    add_menu_page(
        'Site Plugin & Theme Downloader',
        'Site Setup',
        'manage_options',
        'site-plugin-downloader',
        'site_downloader_page'
    );
}
add_action('admin_menu', 'site_downloader_menu');

// Display the admin page and trigger download and setup
function site_downloader_page() {
    global $site_plugins, $site_theme;

    echo '<div class="wrap"><h1>Site Plugins & Theme with Elementor Templates</h1>';
    echo '<form method="post">';
    echo '<input type="submit" name="download_site" class="button button-primary" value="Download Site Plugins, Theme, & Create Templates">';
    echo '</form></div>';

    if (isset($_POST['download_site'])) {
        download_site_plugins_and_theme($site_plugins, $site_theme);
        create_elementor_templates();
        echo '<div class="updated"><p>Site plugins, theme, and Elementor templates downloaded and installed.</p></div>';
    }
}
