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
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
            self::$instance->options = get_option( 'sch_plugin_options', [] );
        }
        return self::$instance;
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
    public function is_widget_active( $key ) {
        return ! empty( $this->options[ 'widget_' . $key ] );
    }
    public function get_widgets() {
        return $this->widgets;
    }
}
