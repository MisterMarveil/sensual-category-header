<?php

class SCH_Header_Shortcode {
    private static $instance;
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        wp_register_style('sensual-header-style', plugins_url('assets/css/sch-styles.css'));
        wp_enqueue_style('sensual-header-style');

        add_shortcode( 'category_header', [ $this, 'render_header' ] );
        add_action( 'sch_render_widgets', [ $this, 'render_all_widgets' ] );
    }

    public function render_header( $atts ) {

        $cat = self::$instance->get_category_info();
        if ($cat === false || !$cat ) {
            return '';
        }
        
        $raw = SCH_IA::get_category_description( $cat->term_id, $cat->name );        
        if(empty($raw)){
            $html = "<strong>Oops! empty html</strong>";
            $css = "";
        }else{
            $html = self::$instance->extractDescHtml($raw);
            $css = self::$instance->extractDescCss($raw);
        }

        ob_start();
        include SCH_PATH . 'templates/header-block.php';
        return ob_get_clean();
    }

    private function extractDescHtml($raw){    
        if ( preg_match( '/---START HTML---(.*?)---END HTML---/s', $raw, $html_match ) ) {
            return trim( $html_match[1] );
        } else {
            return '<!-- HTML not found in AI response -->';
        }    
    }
    
    private function extractDescCss($raw){            
        if ( preg_match( '/---START CSS---(.*?)---END CSS---/s', $raw, $css_match ) ) {
            return trim( $css_match[1] );
        } else {
            return '/* CSS not found in AI response */';
        }
    }

    public function render_all_widgets( $cat ) {
        $admin   = SCH_Admin::instance();
        $widgets = $admin->get_widgets();
        foreach ( $widgets as $key => $label ) {
            if ( $admin->is_widget_active( $key ) ) {
                do_action( 'sch_widget_' . $key, $cat );
            }
        }
    }

    public function get_category_info($category_id = null) {
        // Si aucun ID n'est fourni, utiliser la catégorie courante
        if ($category_id === null) {
            if(isset($_GET['wpf_filter_cat_list_0s']) && !empty($_GET['wpf_filter_cat_list_0s'])){
                $slug = $_GET['wpf_filter_cat_list_0s'];

                $category = get_term_by('slug', $slug, 'product_cat');
    
                if (!$category || is_wp_error($category)) {
                    return false;
                }else{
                    return $category;
                }
                
            }else if (is_product_category()) {
                $category = get_queried_object();
                return $category;
            }

            return false;
            /*if (is_product_category()) {
                $category = get_queried_object();
                return $category;
            } else {
                return false;
            }*/
        } else {
            // Récupérer la catégorie par son ID
            $category = get_term($category_id, 'product_cat');
            if (!$category || is_wp_error($category)) {
                return false;
            }
            return $category;
        }
    }
    
}