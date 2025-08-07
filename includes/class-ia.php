<?php

class SCH_IA {
    public static function get_category_description( $cat_id, $cat_name ) {
        global $wpdb;
        $table = $wpdb->prefix . 'sensual_category_descriptions';
        $row   = $wpdb->get_row( $wpdb->prepare( "SELECT description_html FROM {$table} WHERE category_id=%d", $cat_id ) );
        if ( $row ) {
            return $row->description_html;
        }

         // Récupération des produits représentatifs de la catégorie
        $product_names = self::get_category_sample_products($cat_id, 30);
        $products_list = !empty($product_names) 
            ? 'basée sur ces produits représentatifs : (' . implode(', ', $product_names) . ')'
            : '';

        $opts     = get_option( 'sch_plugin_options', [] );
        $provider = $opts['ia_provider'] ?? 'chatgpt';
        $key      = $opts['api_key'] ?? '';
        
        $prompt   = sprintf(
            "Rédige une description HTML engageante en français pour la catégorie \"%s\"  %s\n\n.",            
            esc_html( $cat_name ), $products_list
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
     * Récupère un échantillon de produits de la catégorie
     */
    private static function get_category_sample_products($category_id, $limit = 10) {
        $products = wc_get_products([
            'category' => [$category_id],
            'limit' => $limit,
            'orderby' => 'rand',
            'return' => 'ids'
        ]);
        
        $product_names = [];
        foreach ($products as $product_id) {
            $product = wc_get_product($product_id);
            if ($product) {
                $product_names[] = $product->get_name();
            }
        }
        
        return $product_names;
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
                    "content" => "Tu es un rédacteur expert en e-commerce spécialisé dans la lingerie fine et les articles intimes. Tu écris pour Jardin Sucre, une boutique en ligne haut de gamme qui privilégie l'élégance, la sensualité raffinée et la discrétion. \n TON & STYLE : - Élégant et séducteur sans être vulgaire \n - Professionnel avec subtilité sensuelle \n - Inclusif et respectueux \n - Discret et confidentiel \nSTRUCTURE ATTENDUE : \n - 2-3 paragraphes captivants (200-350 mots) \n - HTML sémantique avec balises appropriées.. prendre soin de délimiter le html avec les mots clés ---START HTML--- et ---END HTML---; et le css avec ---START CSS--- et ---END CSS--- \n - 1-2 mots-clés naturellement intégrés \n - Appel à l'action subtil en conclusion \n CONTRAINTES : \n - Structure HTML compatible avec ce CSS: [.sensual-header-container{background:linear-gradient(to right,#1a1a1a,#333);color:#fff;padding:30px;margin-bottom:40px;border-radius:8px;position:relative;overflow:hidden}.sensual-description{font-family:'Playfair Display',serif;font-size:1.2rem;line-height:1.8;margin-bottom:25px;text-shadow:0 1px 2px rgba(0,0,0,0.5)}.sensual-description em{font-style:italic;color:#ff6b9c}.marketing-cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px;margin:30px 0}.marketing-card{position:relative;border-radius:8px;overflow:hidden;height:250px;transition:transform 0.3s ease}.marketing-card:hover{transform:translateY(-5px)}.card-image{height:100%;background-size:cover;background-position:center;transition:all 0.4s ease}.marketing-card:hover .card-image{transform:scale(1.05)}.marketing-card h3{position:absolute;bottom:0;left:0;right:0;margin:0;padding:15px;background:rgba(0,0,0,0.7);text-align:center;font-weight:600}.card-hover-content{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(106,27,154,0.85);display:flex;flex-direction:column;justify-content:center;align-items:center;opacity:0;transition:opacity 0.3s ease;color:white;text-align:center;padding:20px}.marketing-card:hover .card-hover-content{opacity:1}.quick-filters-container{background:rgba(255,255,255,0.1);padding:20px;border-radius:8px;margin-top:25px}.filters-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}.filter-group h4{margin-top:0;border-bottom:1px solid rgba(255,255,255,0.2);padding-bottom:8px;font-size:1.1rem}.filter-options{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}.filter-options label{background:rgba(255,255,255,0.1);padding:8px 15px;border-radius:20px;cursor:pointer;transition:all 0.2s ease}.filter-options label:hover{background:rgba(255,255,255,0.2)}.color-option{width:30px;height:30px;border-radius:50%;position:relative}.color-option input{position:absolute;opacity:0}.editor-pick-container{margin-top:30px;border-top:1px solid rgba(255,255,255,0.1);padding-top:25px}.editor-pick-content{display:flex;gap:30px;align-items:center}.editor-pick-image{width:40%;height:300px;background-size:cover;background-position:center;border-radius:8px;flex-shrink:0}.editor-pick-details{flex-grow:1}.editor-pick-button{display:inline-block;background:#e91e63;color:white;padding:12px 25px;border-radius:30px;text-decoration:none;font-weight:600;margin-top:15px;transition:all 0.3s ease}.editor-pick-button:hover{background:#c2185b;transform:translateY(-2px)}] \n- Balises autorisées : div, p, em, strong, span \n-Inclure 1-2 emojis pertinents \n- Thèmes à aborder : confort, sensualité, qualité des matériaux, particularités de la catégorie \n -Format : <div class='sensual-description'>[CONTENU]</div> \n introduire un titre h1 en début sous une forme proche de '<h1>Collection <em>CATEGORY NAME</em></h1>' ou une variante  \n- Pas de références explicites à la sexualité \n - Éviter les clichés marketing \n - Privilégier l'émotion et l'expérience \n - Respecter les bonnes pratiques SEO"
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