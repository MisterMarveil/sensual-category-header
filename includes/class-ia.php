<?php

class SCH_IA {
    public static function get_category_description( $cat_id, $cat_name ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT description_html FROM {$table} WHERE category_id=%d", $cat_id ) );
        if ( $row ) {
            return $row->description_html;
        }
        $opts     = get_option( 'sch_plugin_options', [] );
        $provider = $opts['ia_provider'] ?? 'chatgpt';
        $key      = $opts['api_key'] ?? '';
        $prompt   = sprintf(
            "Tu es un expert en marketing sensuel. Rédige une description HTML engageante et élégante pour une boutique de lingerie fine/sexshop. Utilise un ton séduisant et poétique. La catégorie est : \"%s\". Évoque des sensations, les formes, le désir, et les occasions idéales pour ces produits, sans jamais être vulgaire.",
            esc_html( $cat_name )
        );
        $res  = self::call_ia( $provider, $key, $prompt );
        $desc = wp_kses_post( $res );
        $wpdb->insert( $table, [ 'category_id' => $cat_id, 'description_html' => $desc ], [ '%d', '%s' ] );
        return $desc;
    }
    private static function call_ia( $provider, $key, $prompt ) {
        $url = ( 'deepseek' === $provider )
            ? 'https://api.deepseek.ai/generate'
            : 'https://api.openai.com/v1/chat/completions';
        $body = ( 'deepseek' === $provider )
            ? wp_json_encode( [ 'prompt' => $prompt, 'model' => 'ds-sensual' ] )
            : wp_json_encode( [ 'model' => 'gpt-4', 'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ] ] );
        $response = wp_safe_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => $body,
            'timeout' => 20,
        ] );
        if ( is_wp_error( $response ) ) {
            return '<p>' . esc_html__( 'Erreur de génération IA.', 'sensual-category-header' ) . '</p>';
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 'deepseek' === $provider ) {
            return $data['text'] ?? '';
        }
        return $data['choices'][0]['message']['content'] ?? '';
    }
}