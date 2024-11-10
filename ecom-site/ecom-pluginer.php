<?php
/*
Plugin Name: eCommerce Plugin & Theme Downloader
Description: A plugin to download and install eCommerce-related plugins and themes.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define the eCommerce plugins and themes to download
$ecommerce_plugins = [
    'woocommerce',
    'contact-form-7',
    'woocommerce-admin',
    // Add other eCommerce-related plugin slugs here
];

$ecommerce_theme = 'storefront'; // Default WooCommerce theme

// Function to download and install plugins and theme
function download_ecommerce_plugins_and_theme($plugins, $theme_slug) {
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

    // Install theme
    $theme_api = themes_api('theme_information', ['slug' => $theme_slug]);
    if (!is_wp_error($theme_api)) {
        $theme_upgrader->install($theme_api->download_link);
    }
}

// Add admin menu and page
function ecommerce_downloader_menu() {
    add_menu_page(
        'eCommerce Plugin & Theme Downloader',
        'eCommerce Setup',
        'manage_options',
        'ecommerce-plugin-downloader',
        'ecommerce_downloader_page'
    );
}
add_action('admin_menu', 'ecommerce_downloader_menu');

function ecommerce_downloader_page() {
    global $ecommerce_plugins, $ecommerce_theme;

    echo '<div class="wrap"><h1>eCommerce Plugins & Theme</h1>';
    echo '<form method="post">';
    echo '<input type="submit" name="download_ecommerce" class="button button-primary" value="Download eCommerce Plugins & Theme">';
    echo '</form></div>';

    if (isset($_POST['download_ecommerce'])) {
        download_ecommerce_plugins_and_theme($ecommerce_plugins, $ecommerce_theme);
        echo '<div class="updated"><p>eCommerce plugins and theme downloaded and installed.</p></div>';
    }
}
