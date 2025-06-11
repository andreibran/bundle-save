<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BS_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'woocommerce_before_add_to_cart_button', [ $this, 'display_offer_box' ], 20 );
    }

    public function display_offer_box() {
        global $product;
        if ( ! is_product() || ! $product ) return;

        $campaign = $this->get_applicable_campaign($product);

        if ( $campaign ) {
            $tiers = get_post_meta($campaign->ID, '_bs_tiers', true);

            // --- CORREÇÃO APLICADA AQUI ---
            // Filtra o array para remover quaisquer tiers que possam estar vazios ou mal configurados.
            $filtered_tiers = is_array($tiers) ? array_filter($tiers, function($tier) {
                return !empty($tier['qty']) && !empty($tier['title']);
            }) : [];

            // Se após a filtragem não sobrar nenhum tier válido, não mostra a caixa.
            if (empty($filtered_tiers)) {
                return;
            }
            // --- FIM DA CORREÇÃO ---

            $campaign_data = [
                'id' => $campaign->ID,
                'tiers' => $filtered_tiers // Usa a lista de tiers já filtrada
            ];
            
            wc_get_template(
                'offer-box.php',
                [ 'campaign' => $campaign_data, 'product' => $product ],
                '',
                BS_PLUGIN_DIR . 'templates/'
            );
        }
    }
    
    private function get_applicable_campaign($product) {
        $product_id = $product->get_id();
        $category_ids = $product->get_category_ids();

        $args = [
            'post_type' => 'bs_campaign',
            'post_status' => 'publish',
            'posts_per_page' => -1, // Pega todas para checar
            'meta_query' => [
                'relation' => 'AND',
                ['key' => '_bs_status', 'value' => 'active', 'compare' => '='],
            ]
        ];

        $query = new WP_Query($args);
        $found_campaign = null; // Variável para armazenar a campanha encontrada

        if ($query->have_posts()) {
            while($query->have_posts()) {
                $query->the_post();
                $apply_to = get_post_meta(get_the_ID(), '_bs_apply_to', true);
                $is_match = false;

                if ($apply_to === 'all') {
                    $is_match = true;
                } elseif ($apply_to === 'products') {
                    $product_ids_meta = get_post_meta(get_the_ID(), '_bs_product_ids', true) ?: [];
                    if (in_array($product_id, $product_ids_meta)) {
                        $is_match = true;
                    }
                } elseif ($apply_to === 'categories') {
                    $category_ids_meta = get_post_meta(get_the_ID(), '_bs_category_ids', true) ?: [];
                    if (!empty(array_intersect($category_ids, $category_ids_meta))) {
                        $is_match = true;
                    }
                }
                
                if ($is_match) {
                    $found_campaign = get_post();
                    break; // Encontrou a primeira campanha aplicável, pode parar o loop
                }
            }
        }
        
        wp_reset_postdata(); 

        return $found_campaign; // Retorna a campanha encontrada (ou null)
    }

    public function enqueue_assets() {
        if ( ! is_product() ) return;
        
        wp_enqueue_style('bs-frontend-style', BS_PLUGIN_URL . 'assets/css/frontend.css', [], BS_PLUGIN_VERSION);
        wp_enqueue_script('bs-frontend-script', BS_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], BS_PLUGIN_VERSION, true);

        $settings = get_option('bs_global_settings', ['primary_color' => '#e92d3b']);
        $primary_color = $settings['primary_color'];
        $custom_css = ":root { --bs-primary-color: " . esc_attr($primary_color) . "; }";
        wp_add_inline_style('bs-frontend-style', $custom_css);
    }
}