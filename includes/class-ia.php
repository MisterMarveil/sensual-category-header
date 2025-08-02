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
            "Rédige une description HTML  en français. La catégorie a décrire est : \"%s\". Évoque des sensations, les formes, le désir, et les occasions idéales pour ces produits, sans jamais être vulgaire. Prière de clairement délimité la partie html de la description fournie par ---START HTML--- et ---END HTML--- ensuite le css proposée par ---START CSS--- et ---END CSS---",            
            esc_html( $cat_name )
        );

        if($provider != 'chatgpt'){
            $res = self::deepseek_r1_request($prompt, $key);
        }else{
            $res  = self::call_ia( $provider, $key, $prompt );
        }

        if (is_wp_error($res)) {
            // Handle error
            $res = 'DeepSeek API Error: ' . $res->get_error_message();
        }
        
        $desc = wp_kses_post( $res );
        $wpdb->insert( $table, [ 'category_id' => $cat_id, 'description_html' => $desc ], [ '%d', '%s' ] );
        return $desc;
    }

    /**
     * Send a prompt to DeepSeek R1 model and get the response
     * 
     * @param string $prompt The input prompt to send to DeepSeek
     * @param array $options Optional parameters for the API request
     * @return string|WP_Error The response from DeepSeek or WP_Error on failure
     */
    private static function deepseek_r1_request($prompt, $api_key, $options = array()) {
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('DeepSeek API key is not set.', 'sensual-category-header'));
        }

        // Set default options        
        $defaults = array(
            'max_tokens' => 1048,
            'temperature' => 1,
            'stream' => false,
        );
        $options = wp_parse_args($options, $defaults);
        
        // Prepare the API endpoint
        $api_url = 'https://api.deepseek.com/v1/chat/completions';
        
        // Prepare the request body
        $request_body = array(
            'model' => 'deepseek-reasoner',
            'messages' => array(
                array(
                    "role" => "system",
                    "content" => "Vous êtes le/la copywriter attitré·e de « JardinSucre », une boutique en ligne haut de gamme spécialisée dans la lingerie fine, les sextoys et les accessoires de bien-être intime. À chaque catégorie de produits que nous fournissons, vous concevez un texte de présentation marketing captivant, placé en tête de page pour introduire l’univers et l’atmosphère de la sélection. Votre style se veut toujours séducteur, poétique et inclusif, teinté de sensualité subtile. Mettez en avant le confort, la confiance en soi et l’émancipation, tout en restant élégant·e et pudique : privilégiez les euphémismes raffinés (par exemple « accessoires de plaisir »), sans tomber dans la vulgarité. Soulignez la qualité, l’ajustement parfait et la discrétion de nos livraisons. Vos réponses doivent rester concises, engageantes et parfaitement adaptées à un en-tête de page produit, à une fiche descriptive ou à un post sur les réseaux sociaux."
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'stream' => $options['stream'],
        );
        
        // Prepare the request arguments
        $args = array(
            'body' => json_encode($request_body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 220, // 30 seconds timeout
        );
        
        // Make the POST request
        $response = wp_remote_post($api_url, $args);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code != 200) {
            return new WP_Error('api_error', sprintf(__('API request failed with status code %d', 'your-plugin-textdomain'), $response_code), $response_body);
        }
        
        // Decode the JSON response
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Failed to decode API response', 'your-plugin-textdomain'));
        }
        
        // Extract and return the content
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
        
        return new WP_Error('no_content', __('No content received from API', 'your-plugin-textdomain'));
    }

    private static function call_ia( $provider, $key, $prompt ) {
        $url = ( 'deepseek' === $provider )
            ? 'https://api.deepseek.com/v1/chat/completions'
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
            return '<p>' . esc_html__( 'Erreur de génération IA. Details: '.$response->get_error_message(), 'sensual-category-header' ) . '</p>';
        }
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( 'deepseek' === $provider ) {
            return $data['text'] ?? '';
        }
        return $data['choices'][0]['message']['content'] ?? '';
    }
}