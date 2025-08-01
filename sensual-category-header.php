<?php
/**
 * Plugin Name: Sensual Category Header
 * Description: Injects a dynamic, sensual HTML header and filter widgets on WooCommerce category pages via shortcode.
 * Version: 1.2.0
 * Author: Merveil EWONI (BRIDGE IT Solutions <merveil@bridge-cm.com>)
 * Text Domain: sensual-category-header
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Paths
if ( ! defined( 'SCH_PATH' ) ) define( 'SCH_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'SCH_URL' ) )  define( 'SCH_URL',  plugin_dir_url( __FILE__ ) );

// Autoload
spl_autoload_register( function( $class ) {
    if ( 0 === strpos( $class, 'SCH_' ) ) {
        $file = SCH_PATH . 'includes/class-' . strtolower( str_replace( 'SCH_', '', $class ) ) . '.php';
        if ( file_exists( $file ) ) require_once $file;
    }
});

// Hooks
register_activation_hook( __FILE__, [ 'SCH_Init', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SCH_Uninstall', 'cleanup' ] );
add_action( 'plugins_loaded', [ 'SCH_Init', 'instance' ] );