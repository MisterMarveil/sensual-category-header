<?php 

class SCH_Init {
    private static $instance;
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
            self::$instance->init_hooks();
        }
        return self::$instance;
    }
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT UNSIGNED NOT NULL,
            description_html LONGTEXT NOT NULL,
            PRIMARY KEY(id),
            UNIQUE KEY cat_idx(category_id)
        ) {$charset};";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
    private function init_hooks() {
        add_action( 'init', [ $this, 'load_textdomain' ] );
        if ( is_admin() ) {
            SCH_Admin::instance();
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        }
        SCH_Header_Shortcode::instance();
    }
    public function load_textdomain() {
        load_plugin_textdomain( 'sensual-category-header', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }
    public function enqueue_assets() {
        if ( is_product_category() ) {
            wp_enqueue_style( 'sch-styles', SCH_URL . 'assets/css/sch-styles.css', [], '1.2.0' );
            wp_enqueue_script( 'sch-scripts', SCH_URL . 'assets/js/sch-scripts.js', [ 'jquery' ], '1.2.0', true );
        }
    }
}