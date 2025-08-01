<?php

class SCH_Uninstall {
    public static function cleanup() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sensual_category_descriptions" );
        delete_option( 'sch_plugin_options' );
    }
}