<?php

class SCH_Admin {
    private static $instance;
    private $options;
    private $widgets;
    private function __construct() {
        $this->widgets = [
            'market_cards'    => esc_html__( 'Market Cards (Amazon style)', 'sensual-category-header' ),
            'product_type'    => esc_html__( 'Filtre par type de produit', 'sensual-category-header' ),
            'ambiance'        => esc_html__( 'Filtre par ambiance', 'sensual-category-header' ),
            'color'           => esc_html__( 'Filtre par couleur dominante', 'sensual-category-header' ),
            'size'            => esc_html__( 'Filtre par taille', 'sensual-category-header' ),
            'recommendations' => esc_html__( 'Propositions personnalisées', 'sensual-category-header' ),
            'best_sellers'    => esc_html__( 'Best-sellers', 'sensual-category-header' ),
            'new_arrivals'    => esc_html__( 'Nouveautés', 'sensual-category-header' ),
            'testimonials'    => esc_html__( 'Témoignages sélectionnés', 'sensual-category-header' ),
            'teaser'          => esc_html__( 'Mini teaser vidéo/image', 'sensual-category-header' ),
            'countdown'       => esc_html__( 'Effet countdown', 'sensual-category-header' ),
        ];
        add_action( 'admin_menu', [ $this, 'add_menu' ] );
        add_action('admin_menu', [$this, 'add_management_menu']);
        add_action( 'admin_init', [ $this, 'register_settings' ] );        
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('wp_ajax_sch_search_categories', [$this, 'ajax_search_categories']);
        add_action('wp_ajax_sch_get_description', [$this, 'ajax_get_description']);
        add_action('wp_ajax_sch_save_description', [$this, 'ajax_save_description']);
        add_action('wp_ajax_sch_delete_description', [$this, 'ajax_delete_description']);
    }
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
            self::$instance->options = get_option( 'sch_plugin_options', [] );
        }
        return self::$instance;
    }

    public function add_management_menu() {
        add_submenu_page(
            'sch-settings',
            esc_html__('Gestion des Descriptions', 'sensual-category-header'),
            esc_html__('Gérer Descriptions', 'sensual-category-header'),
            'manage_options',
            'sch-manage-descriptions',
            [$this, 'render_management_page']
        );
    }

    public function render_management_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Gestion des Descriptions de Catégories', 'sensual-category-header'); ?></h1>
            
            <div id="sch-description-management">
                <div class="search-section">
                    <label for="category-search">Rechercher une catégorie :</label>
                    <input type="text" id="category-search" placeholder="Commencez à taper...">
                    <div id="search-results"></div>
                </div>
                
                <div id="editor-section" style="display:none;">
                    <input type="hidden" id="selected-category-id">
                    <h2 id="selected-category-name"></h2>
                    <textarea id="description-editor" rows="15" style="width:100%;"></textarea>
                    <div class="editor-actions">
                        <button id="save-description" class="button button-primary">Enregistrer</button>
                        <button id="delete-description" class="button button-danger">Supprimer</button>
                        <span id="action-feedback"></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_admin_assets($hook) {
    if ($hook === 'sensual-category-header_page_sch-manage-descriptions') {
        wp_enqueue_style(
            'sch-admin-styles',
            plugins_url('../assets/css/sch-admin-styles.css', __FILE__)
        );
        
        wp_enqueue_script(
            'sch-manage-descriptions',
            plugins_url('../assets/js/admin-manage-descriptions.js', __FILE__),
            ['jquery'],
            '1.0',
            true
        );
        
        wp_localize_script('sch-manage-descriptions', 'sch_admin_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sch_manage_nonce')
        ]);
    }
}
    public function add_menu() {
        add_menu_page(
            esc_html__( 'Sensual Header', 'sensual-category-header' ),
            esc_html__( 'Sensual Header', 'sensual-category-header' ),
            'manage_options',
            'sch-settings',
            [ $this, 'render_settings_page' ],
            'dashicons-heart',
            60
        );
    }
    public function register_settings() {
        register_setting(
            'sch_options_group',
            'sch_plugin_options',
            [ 'sanitize_callback' => [ $this, 'sanitize_options' ] ]
        );
        add_settings_section( 'sch_main_section', esc_html__( 'Paramètres généraux', 'sensual-category-header' ), null, 'sch-settings' );
        add_settings_field( 'ia_provider', esc_html__( 'IA Provider', 'sensual-category-header' ), [ $this, 'field_ia_provider' ], 'sch-settings', 'sch_main_section' );
        add_settings_field( 'api_key',     esc_html__( 'API Key', 'sensual-category-header' ),      [ $this, 'field_api_key' ],     'sch-settings', 'sch_main_section' );
        add_settings_section( 'sch_widgets_section', esc_html__( 'Widgets d\'entête activables', 'sensual-category-header' ), null, 'sch-settings' );
        foreach ( $this->widgets as $key => $label ) {
            add_settings_field(
                'widget_' . $key,
                $label,
                function() use ( $key ) {
                    $checked = ! empty( $this->options[ 'widget_' . $key ] );
                    printf(
                        '<input type="checkbox" id="widget_%1$s" name="sch_plugin_options[widget_%1$s]" value="1" %2$s />',
                        esc_attr( $key ), checked( true, $checked, false )
                    );
                },
                'sch-settings',
                'sch_widgets_section'
            );
        }
    }
    public function sanitize_options( $input ) {
        $valid = [];
        $valid['ia_provider'] = in_array( $input['ia_provider'] ?? '', [ 'chatgpt', 'deepseek' ], true ) ? $input['ia_provider'] : 'chatgpt';
        $valid['api_key']     = sanitize_text_field( $input['api_key'] ?? '' );
        foreach ( array_keys( $this->widgets ) as $key ) {
            $valid['widget_' . $key] = ! empty( $input['widget_' . $key] ) ? 1 : 0;
        }
        return $valid;
    }
    public function field_ia_provider() {
        $val = $this->options['ia_provider'] ?? 'chatgpt';
        printf(
            '<select name="sch_plugin_options[ia_provider]"> <option value="chatgpt" %1$s>ChatGPT</option> <option value="deepseek" %2$s>DeepSeek</option> </select>',
            selected( $val, 'chatgpt', false ), selected( $val, 'deepseek', false )
        );
    }
    public function field_api_key() {
        printf(
            '<input type="text" name="sch_plugin_options[api_key]" value="%1$s" size="50" />',
            esc_attr( $this->options['api_key'] ?? '' )
        );
    }
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Sensual Category Header Settings', 'sensual-category-header' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'sch_options_group' );
                do_settings_sections( 'sch-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // AJAX handlers
    public function ajax_search_categories() {
        check_ajax_referer('sch_manage_nonce', 'security');
        
        global $wpdb;
        $search = sanitize_text_field($_GET['term'] ?? '');
        $results = [];
        
        if (!empty($search)) {
            $terms = get_terms([
                'taxonomy'   => 'product_cat',
                'name__like' => $search,
                'number'     => 10,
                'hide_empty' => false,
            ]);
            
            foreach ($terms as $term) {
                $results[] = [
                    'id'   => $term->term_id,
                    'text' => $term->name . " (ID: {$term->term_id})"
                ];
            }
        }
        
        wp_send_json(['results' => $results]);
    }

    public function ajax_get_description() {
        check_ajax_referer('sch_manage_nonce', 'security');
        
        global $wpdb;
        $category_id = intval($_POST['category_id']);
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        
        $description = $wpdb->get_var($wpdb->prepare(
            "SELECT description_html FROM $table WHERE category_id = %d",
            $category_id
        ));
        
        wp_send_json_success([
            'description' => $description ?: '',
            'category_name' => get_term($category_id)->name
        ]);
    }

    public function ajax_save_description() {
        check_ajax_referer('sch_manage_nonce', 'security');
        
        global $wpdb;
        $category_id = intval($_POST['category_id']);
        $description = wp_kses_post($_POST['description']);
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        
        $result = $wpdb->replace($table, [
            'category_id'     => $category_id,
            'description_html' => $description
        ], ['%d', '%s']);
        
        wp_send_json_success([
            'message' => $result ? 'Description sauvegardée!' : 'Erreur de sauvegarde'
        ]);
    }

    public function ajax_delete_description() {
        check_ajax_referer('sch_manage_nonce', 'security');
        
        global $wpdb;
        $category_id = intval($_POST['category_id']);
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        
        $result = $wpdb->delete($table, ['category_id' => $category_id], ['%d']);
        
        wp_send_json_success([
            'message' => $result ? 'Description supprimée!' : 'Erreur de suppression'
        ]);
    }

    public function is_widget_active( $key ) {
        return ! empty( $this->options[ 'widget_' . $key ] );
    }
    public function get_widgets() {
        return $this->widgets;
    }
}
