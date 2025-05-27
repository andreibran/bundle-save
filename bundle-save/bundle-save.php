<?php
/**
 * Plugin Name: Bundle & Save
 * Description: Ofertas dinâmicas “Compre 1/2/3” em páginas de produto WooCommerce.
 * Version:     1.1.0
 * Author:      Você
 * Text Domain: bundle-save
 * Domain Path: /languages
 * WC requires at least: 3.0
 * WC tested up to: 8.8
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Define plugin constants
define( 'BUNDLE_SAVE_VERSION', '1.1.0' );
define( 'BUNDLE_SAVE_PLUGIN_FILE', __FILE__ );
define( 'BUNDLE_SAVE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BUNDLE_SAVE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

class Bundle_Save_Plugin {

    /** Option key */
    private $opt_key = 'bundle_save_options';
    public $default_options; // Tornar público para acesso no hook de ativação

    public function __construct() {
        $this->default_options = [
            'primary_color' => '#e92d3b',
            'css_extra'     => '',
            'bundles'       => [
                [
                    'enabled'   => true,
                    'qty'       => 1,
                    'discount_type' => 'percentage',
                    'discount_value' => 0,
                    'title'     => __( 'Buy 1', 'bundle-save' ),
                    'note'      => __( '%unit_price% / per item', 'bundle-save' ),
                    'badge'     => '',
                    'label'     => '',
                ],
                [
                    'enabled'   => true,
                    'qty'       => 2,
                    'discount_type' => 'percentage',
                    'discount_value' => 20,
                    'title'     => __( 'Buy 2', 'bundle-save' ),
                    'note'      => __( '%unit_price% / per item', 'bundle-save' ),
                    'badge'     => __( 'Most Popular', 'bundle-save' ),
                    'label'     => __( '+ Free Shipping', 'bundle-save' ),
                ],
                [
                    'enabled'   => true,
                    'qty'       => 3,
                    'discount_type' => 'percentage',
                    'discount_value' => 30,
                    'title'     => __( 'Buy 3', 'bundle-save' ),
                    'note'      => __( '%unit_price% / per item', 'bundle-save' ),
                    'badge'     => __( 'Best Value', 'bundle-save' ),
                    'label'     => __( '+ Free Shipping', 'bundle-save' ),
                ],
            ]
        ];

        add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
        add_action( 'init', [ $this, 'register_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_menu', [ $this, 'settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings'] );

        // Check if WooCommerce is active
        if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', [ $this, 'woocommerce_not_active_notice' ] );
            return;
        }
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'bundle-save', false, dirname( plugin_basename( BUNDLE_SAVE_PLUGIN_FILE ) ) . '/languages/' );
    }

    public function is_woocommerce_active() {
        return class_exists( 'WooCommerce' );
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'Bundle & Save plugin requires WooCommerce to be activated.', 'bundle-save' ); ?></p>
        </div>
        <?php
    }

    /*--------------------------------------  FRONT-END  --------------------------------------*/

    public function register_shortcode() {
        add_shortcode( 'bundle_save', [ $this, 'render_bundle' ] );
    }

    public function enqueue_assets() {
        if ( ! function_exists('is_product') || ! is_product() ) return;

        wp_enqueue_style(
            'bundle-save',
            BUNDLE_SAVE_PLUGIN_URL . 'assets/bundle-save.css',
            [],
            BUNDLE_SAVE_VERSION
        );

        wp_enqueue_script(
            'bundle-save',
            BUNDLE_SAVE_PLUGIN_URL . 'assets/bundle-save.js',
            [ 'jquery' ],
            BUNDLE_SAVE_VERSION,
            true
        );

        $options = get_option( $this->opt_key, $this->default_options );
        wp_localize_script( 'bundle-save', 'BundleSaveOpt', [
            'primary' => $options['primary_color'] ?? $this->default_options['primary_color'],
        ] );
    }

    /*------------------------------------------------------------------
     * FRONT-END  –  gera os cartões
     *-----------------------------------------------------------------*/
    public function render_bundle() {

        if ( ! function_exists( 'is_product' ) || ! is_product() || ! $this->is_woocommerce_active() ) {
            return '';
        }

        /* ——— produto atual ——— */
        global $product;
        if ( ! $product instanceof WC_Product ) {
            $product = wc_get_product( get_the_ID() );
            // Verificação mais robusta se o produto é válido
            if ( ! $product instanceof WC_Product ) {
                return '';
            }
        }

        /* ——— preço base (considera preço promocional se disponível) ——— */
        $base_price = floatval( $product->get_price() );
        if ( ! $base_price && $product->is_type('variable')) {
            // Para produtos variáveis, pode ser necessário um tratamento diferente
            // ou obter o preço de uma variação padrão, se aplicável.
        }
        // Se ainda assim não houver preço base (e não for explicitamente zero), retorna vazio.
        if ( ! $base_price && $base_price !== 0.0 ) {
             return '';
        }


        $opt  = get_option( $this->opt_key, $this->default_options );
        $rows = $opt['bundles'] ?? $this->default_options['bundles'];

        ob_start(); ?>
        <div id="bundle-save" class="bundle-grid">

        <?php
        $first_one_rendered = true; // Flag para marcar o primeiro item habilitado como ativo/checked
        foreach ( $rows as $i => $cfg ) :
            if ( empty( $cfg['enabled'] ) ) continue;

            /* cálculos */
            $qty  = max( 1, intval( $cfg['qty'] ?? 1 ) );
            $disc_value = floatval( $cfg['discount_value'] ?? 0 );
            $disc_type  = $cfg['discount_type'] ?? 'percentage';

            $unit_price = $base_price;
            if ( $disc_type === 'fixed' ) {
                $unit_price = max( 0, $base_price - $disc_value );
            } else { // 'percentage' or default
                $unit_price = max( 0, $base_price * ( 1 - $disc_value / 100 ) );
            }
            $unit_price  = round( $unit_price, wc_get_price_decimals() );
            $total = $unit_price * $qty;

            /* textos */
            $title = $cfg['title'] ?? sprintf( __( 'Buy %d', 'bundle-save' ), $qty );
            $label = $cfg['label'] ?? '';
            $badge = $cfg['badge'] ?? '';

            $note  = $cfg['note'] ?? __( '%unit_price% / per item', 'bundle-save' );
            $note  = str_replace( '%unit_price%', wc_price( $unit_price ), $note );

            // Melhoria na exibição do valor do desconto na nota
            $discount_display_val = '';
            if ($disc_value > 0) {
                if ( $disc_type === 'fixed' ) {
                    $discount_display_val = wc_price( $disc_value );
                } else {
                    $discount_display_val = $disc_value . '%';
                }
            }
            $note  = str_replace( '%discount_value%', $discount_display_val, $note );


            $is_this_one_active = false;
            if ($first_one_rendered) {
                $is_this_one_active = true;
                $first_one_rendered = false;
            }
            ?>
            <label class="bundle-card<?php echo $is_this_one_active ? ' is-active' : ''; ?>"
                   data-qty="<?php echo esc_attr( $qty ); ?>"
                   data-price="<?php echo esc_attr( $total ); ?>"
                   <?php if ( $badge ) printf( 'data-badge="%s"', esc_attr( $badge ) ); ?>>

                <input type="radio"
                       name="bundle_option_qty"
                       value="<?php echo esc_attr( $qty ); ?>"
                       <?php if ( $is_this_one_active ) echo 'checked="checked"'; ?> >

                <div class="bundle-info">
                    <strong><?php echo wp_kses_post( $title . ( $label ? ' <small>'.esc_html( $label ).'</small>' : '' ) ); ?></strong>
                    <em><?php echo wp_kses_post( $note ); ?></em>
                </div>

                <span class="total"><?php echo wc_price( $total ); ?></span>
            </label>
        <?php endforeach; ?>

        </div>
        <?php
        /* CSS extra */
        if ( ! empty( $opt['css_extra'] ) ) {
            printf( '<style id="bundle-save-extra">%s</style>', wp_strip_all_tags( $opt['css_extra'] ) );
        }
        return ob_get_clean();
    }

    /*--------------------------------------  ADMIN  --------------------------------------*/

    public function settings_page() {
        add_options_page(
            __( 'Bundle & Save Settings', 'bundle-save' ),
            __( 'Bundle & Save', 'bundle-save' ),
            'manage_options',
            'bundle-save',
            [ $this, 'settings_html' ]
        );
    }

    public function register_settings() {
        register_setting( $this->opt_key . '_group', $this->opt_key, [ $this, 'sanitize_options' ] );

        // General Settings Section
        add_settings_section(
            'bundle_save_general',
            __( 'General Settings', 'bundle-save' ),
            null,
            $this->opt_key
        );

        add_settings_field(
            'primary_color',
            __( 'Primary Color', 'bundle-save' ),
            [ $this, 'field_color_picker' ],
            $this->opt_key,
            'bundle_save_general',
            [
                'id' => 'primary_color',
                'label_for' => 'primary_color',
                'description' => __( 'Main color used for active elements and prices.', 'bundle-save' )
            ]
        );

        add_settings_field(
            'css_extra',
            __( 'Custom CSS', 'bundle-save' ),
            [ $this, 'field_textarea' ],
            $this->opt_key,
            'bundle_save_general',
            [
                'id' => 'css_extra',
                'label_for' => 'css_extra',
                'description' => __( 'Add any custom CSS styles here.', 'bundle-save' )
            ]
        );

        // Bundle Configuration Sections (Loop for 3 bundles)
        for ( $i = 0; $i < 3; $i++ ) {
            add_settings_section(
                "bundle_save_offer_{$i}_section",
                sprintf( __( 'Offer Tier %d', 'bundle-save' ), $i + 1 ),
                null, // Callback for section description (optional)
                $this->opt_key
            );

            add_settings_field( "bundles_{$i}_enabled", __( 'Enable Tier', 'bundle-save' ), [ $this,'field_checkbox' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"enabled", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_enabled_field" ] );
            add_settings_field( "bundles_{$i}_qty", __( 'Quantity', 'bundle-save' ), [ $this,'field_number' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"qty", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_qty_field", 'min' => 1, 'step' => 1, 'default' => $this->default_options['bundles'][$i]['qty'] ] );
            add_settings_field( "bundles_{$i}_discount_type", __( 'Discount Type', 'bundle-save' ), [ $this,'field_select' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"discount_type", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_discount_type_field", 'options' => ['percentage' => __( 'Percentage (%)', 'bundle-save'), 'fixed' => __( 'Fixed Amount', 'bundle-save')], 'default' => $this->default_options['bundles'][$i]['discount_type'] ] );
            add_settings_field( "bundles_{$i}_discount_value", __( 'Discount Value', 'bundle-save' ), [ $this,'field_number' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"discount_value", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_discount_value_field", 'step' => 'any', 'default' => $this->default_options['bundles'][$i]['discount_value'], 'description' => __('Enter value without % or currency symbol.', 'bundle-save') ] );
            add_settings_field( "bundles_{$i}_title", __( 'Title', 'bundle-save' ), [ $this,'field_text' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"title", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_title_field", 'default' => $this->default_options['bundles'][$i]['title'] ] );
            add_settings_field( "bundles_{$i}_note", __( 'Note', 'bundle-save' ), [ $this,'field_text' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"note", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_note_field", 'default' => $this->default_options['bundles'][$i]['note'], 'description' => __( 'Use %unit_price% for unit price, %discount_value% for discount.', 'bundle-save' ) ] );
            add_settings_field( "bundles_{$i}_badge", __( 'Badge Text', 'bundle-save' ), [ $this,'field_text' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"badge", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_badge_field", 'default' => $this->default_options['bundles'][$i]['badge'] ] );
            add_settings_field( "bundles_{$i}_label", __( 'Extra Label', 'bundle-save' ), [ $this,'field_text' ], $this->opt_key, "bundle_save_offer_{$i}_section", ['id'=>"label", 'group' => 'bundles', 'index' => $i, 'label_for' => "bundles_{$i}_label_field", 'default' => $this->default_options['bundles'][$i]['label'], 'description' => __( 'e.g., "+ Free Shipping". Will appear after the title.', 'bundle-save') ] );
        }
    }

    public function sanitize_options( $input ) {
        // Comece com as opções salvas existentes. Se não houver, use um array vazio.
        $current_options = get_option( $this->opt_key, [] );
        // Faça um merge profundo com as opções padrão para garantir que todas as chaves estejam presentes
        // e tenham um valor base, dando prioridade aos valores de $current_options.
        $sanitized_options = array_replace_recursive( $this->default_options, $current_options );
    
        // Sanitiza opções gerais se estiverem presentes no input
        if ( isset( $input['primary_color'] ) ) {
            $sanitized_options['primary_color'] = sanitize_hex_color( $input['primary_color'] );
            // Fallback para o padrão se a sanitização falhar ou resultar em valor inválido
            if ( ! $sanitized_options['primary_color'] ) {
                $sanitized_options['primary_color'] = $this->default_options['primary_color'];
            }
        }
    
        if ( isset( $input['css_extra'] ) ) {
            // wp_strip_all_tags é bom, mas para CSS, talvez queira permitir algumas tags <style> ou validar mais a fundo.
            // Para simplicidade e segurança, wp_strip_all_tags é usado conforme o original.
            // Se precisar de CSS mais complexo, considere wp_kses_post ou similar com contexto apropriado.
            $sanitized_options['css_extra'] = wp_strip_all_tags( $input['css_extra'] );
        }
        // Se não estiverem no input, eles mantêm seus valores de $sanitized_options (que é o merge de current/default)
    
        // Sanitiza os bundles
        if ( isset( $input['bundles'] ) && is_array( $input['bundles'] ) ) {
            $submitted_bundles_input = $input['bundles'];
            $processed_bundles = [];
    
            for ( $i = 0; $i < 3; $i++ ) { // Assumindo no máximo 3 bundles conforme estrutura original
                // Comece com os dados do bundle existentes (ou padrão, se não existir) para este índice
                // $sanitized_options já contém os dados atuais/padrão devido ao array_replace_recursive
                $current_bundle_data = $sanitized_options['bundles'][$i] ?? $this->default_options['bundles'][$i];
                // Garante que é um array
                if (!is_array($current_bundle_data)) {
                    $current_bundle_data = $this->default_options['bundles'][$i];
                }
    
                if ( isset( $submitted_bundles_input[$i] ) && is_array($submitted_bundles_input[$i]) ) {
                    // Dados para este índice de bundle FORAM enviados. Processe-os.
                    $single_bundle_input = $submitted_bundles_input[$i];
    
                    // Checkbox 'enabled': se $single_bundle_input para este tier existir,
                    // a ausência de 'enabled' significa que foi desmarcado.
                    $current_bundle_data['enabled'] = isset( $single_bundle_input['enabled'] ) ? true : false;
    
                    // Outros campos: atualize se a chave existir no input para este bundle
                    if ( array_key_exists( 'qty', $single_bundle_input ) ) {
                        $current_bundle_data['qty'] = absint( $single_bundle_input['qty'] );
                        if ($current_bundle_data['qty'] <= 0) $current_bundle_data['qty'] = 1; // Garante qty mínima de 1
                    }
    
                    if ( array_key_exists( 'discount_type', $single_bundle_input ) ) {
                        $dt = sanitize_text_field( $single_bundle_input['discount_type'] );
                        $current_bundle_data['discount_type'] = in_array($dt, ['percentage', 'fixed']) ? $dt : $this->default_options['bundles'][$i]['discount_type'];
                    }
    
                    if ( array_key_exists( 'discount_value', $single_bundle_input ) ) {
                        $dv = floatval( str_replace(',', '.', $single_bundle_input['discount_value'] ) ); // Aceita vírgula como decimal
                        $current_bundle_data['discount_value'] = max(0, $dv); // Sem desconto negativo
                    }
    
                    if ( array_key_exists( 'title', $single_bundle_input ) ) {
                        $current_bundle_data['title'] = sanitize_text_field( $single_bundle_input['title'] );
                    }
                    if ( array_key_exists( 'note', $single_bundle_input ) ) {
                        $current_bundle_data['note'] = sanitize_text_field( $single_bundle_input['note'] );
                    }
                    if ( array_key_exists( 'badge', $single_bundle_input ) ) {
                        $current_bundle_data['badge'] = sanitize_text_field( $single_bundle_input['badge'] );
                    }
                    if ( array_key_exists( 'label', $single_bundle_input ) ) {
                        $current_bundle_data['label'] = sanitize_text_field( $single_bundle_input['label'] );
                    }
                    $processed_bundles[$i] = $current_bundle_data;
    
                } else {
                    // Nenhum dado enviado para este índice de bundle ($i). Mantenha os dados existentes.
                    $processed_bundles[$i] = $current_bundle_data; // Já contém os dados existentes ou padrão
                }
            }
            $sanitized_options['bundles'] = $processed_bundles;
        }
        // Se a chave 'bundles' estiver totalmente ausente do $input, $sanitized_options['bundles'] retém seu valor
        // do $sanitized_options inicializado com array_replace_recursive.
    
        return $sanitized_options;
    }


    /* Field Callbacks */
    private function get_option_value($id, $group = null, $index = null, $default = '') {
        $options = get_option($this->opt_key, $this->default_options);
        // Garante que default_options seja um array para evitar erros com ??
        $defaults_to_use = is_array($this->default_options) ? $this->default_options : [];

        if ($group && isset($options[$group][$index][$id])) {
            return $options[$group][$index][$id];
        } elseif (!$group && isset($options[$id])) {
            return $options[$id];
        }
        
        // Fallback para os valores padrão se não encontrados
        if ($group && isset($defaults_to_use[$group][$index][$id])) {
            return $defaults_to_use[$group][$index][$id];
        } elseif (!$group && isset($defaults_to_use[$id])) {
            return $defaults_to_use[$id];
        }
        
        return $default;
    }

    public function field_text( $args ){
        $id = esc_attr( $args['id'] );
        $group = $args['group'] ?? null;
        $index = $args['index'] ?? null;
        // Use o default do argumento se fornecido, caso contrário string vazia
        $default_value = $args['default'] ?? '';
        $value = $this->get_option_value($id, $group, $index, $default_value);

        $name_attr = $group ? sprintf('%s[%s][%s][%s]', esc_attr($this->opt_key), $group, $index, $id) : sprintf('%s[%s]', esc_attr($this->opt_key), $id);
        $field_id_attr = $group ? sprintf('%s_%s_%s_%s_field', esc_attr($this->opt_key), $group, $index, $id) : ($args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id));


        printf( '<input type="text" name="%s" id="%s" value="%s" class="regular-text">',
            $name_attr,
            $field_id_attr,
            esc_attr( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function field_number( $args ){
        $id = esc_attr( $args['id'] );
        $group = $args['group'] ?? null;
        $index = $args['index'] ?? null;
        $default_value = $args['default'] ?? 0;
        $value = $this->get_option_value($id, $group, $index, $default_value);

        $name_attr = $group ? sprintf('%s[%s][%s][%s]', esc_attr($this->opt_key), $group, $index, $id) : sprintf('%s[%s]', esc_attr($this->opt_key), $id);
        $field_id_attr = $group ? sprintf('%s_%s_%s_%s_field', esc_attr($this->opt_key), $group, $index, $id) : ($args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id));
        $min = isset($args['min']) ? 'min="' . esc_attr($args['min']) . '"' : '';
        $step = isset($args['step']) ? 'step="' . esc_attr($args['step']) . '"' : '1'; // Default step 1

        printf( '<input type="number" name="%s" id="%s" value="%s" class="small-text" %s %s>',
            $name_attr,
            $field_id_attr,
            esc_attr( $value ),
            $min,
            $step
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function field_checkbox( $args ){
        $id = esc_attr( $args['id'] );
        $group = $args['group'] ?? null;
        $index = $args['index'] ?? null;
        $default_value = $args['default'] ?? false;
        $value = $this->get_option_value($id, $group, $index, $default_value);

        $name_attr = $group ? sprintf('%s[%s][%s][%s]', esc_attr($this->opt_key), $group, $index, $id) : sprintf('%s[%s]', esc_attr($this->opt_key), $id);
        $field_id_attr = $group ? sprintf('%s_%s_%s_%s_field', esc_attr($this->opt_key), $group, $index, $id) : ($args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id));

        printf( '<input type="checkbox" name="%s" id="%s" value="1" %s>',
            $name_attr,
            $field_id_attr,
            checked( 1, $value, false )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<label for="%s"> <span class="description">%s</span></label>', $field_id_attr, esc_html( $args['description'] ) );
        }
    }

    public function field_select( $args ) {
        $id = esc_attr( $args['id'] );
        $group = $args['group'] ?? null;
        $index = $args['index'] ?? null;
        $default_value = $args['default'] ?? '';
        $current_value = $this->get_option_value($id, $group, $index, $default_value);

        $name_attr = $group ? sprintf('%s[%s][%s][%s]', esc_attr($this->opt_key), $group, $index, $id) : sprintf('%s[%s]', esc_attr($this->opt_key), $id);
        $field_id_attr = $group ? sprintf('%s_%s_%s_%s_field', esc_attr($this->opt_key), $group, $index, $id) : ($args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id));
        $options = $args['options'] ?? [];

        echo "<select name='{$name_attr}' id='{$field_id_attr}'>";
        foreach ( $options as $value_opt => $label_opt ) { // Renomeado para evitar conflito com $value
            printf( '<option value="%s"%s>%s</option>',
                esc_attr( $value_opt ),
                selected( $current_value, $value_opt, false ),
                esc_html( $label_opt )
            );
        }
        echo "</select>";
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

    public function field_textarea( $args ){
        $id = esc_attr( $args['id'] );
        $default_value = $args['default'] ?? '';
        $value = $this->get_option_value($id, null, null, $default_value);

        $name_attr = sprintf('%s[%s]', esc_attr($this->opt_key), $id);
        $field_id_attr = $args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id);

        printf( '<textarea name="%s" id="%s" rows="6" cols="60" class="large-text code">%s</textarea>',
            $name_attr,
            $field_id_attr,
            esc_textarea( $value )
        );
        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }

   /* ---------- campo: Color Picker ---------- */
    public function field_color_picker( $args ) {
        $id    = esc_attr( $args['id'] );
        $default_value = $args['default'] ?? '#e92d3b';
        $value = $this->get_option_value( $id, null, null, $default_value );

        $name  = sprintf( '%s[%s]', esc_attr( $this->opt_key ), $id );
        $field_id_attr = $args['label_for'] ?? sprintf('%s_%s_field', esc_attr($this->opt_key), $id);

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_add_inline_script(
            'wp-color-picker',
            // Garante que o seletor de ID seja devidamente escapado para JS, se necessário, mas geralmente '#' + ID é seguro.
            sprintf( "jQuery(function($){ $('#%s').wpColorPicker(); });", $field_id_attr)
        );

        printf(
            '<input type="text" name="%s" id="%s" value="%s" class="bundle-save-color-picker" data-default-color="%s">',
            $name,
            $field_id_attr,
            esc_attr( $value ),
            esc_attr( $default_value ) // data-default-color também usa o default do argumento
        );

        if ( ! empty( $args['description'] ) ) {
            printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
        }
    }


    public function settings_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap bundle-save-settings-wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <p>
                <?php esc_html_e( 'Configure the bundle offers that will appear on your WooCommerce product pages when you use the shortcode', 'bundle-save' ); ?>
                <code>[bundle_save]</code>.
            </p>
            <form method="post" action="options.php">
                <?php
                    settings_fields( $this->opt_key . '_group' );
                    do_settings_sections( $this->opt_key );
                    submit_button( __( 'Save Settings', 'bundle-save' ) );
                ?>
            </form>
        </div>
        <style>
            .bundle-save-settings-wrap .form-table th { padding-left: 20px; }
            .bundle-save-settings-wrap .form-table tr:not(:first-child) > th { border-top: 1px dashed #ccc; padding-top: 20px !important; }
            .bundle-save-settings-wrap h2 { margin-top: 30px; padding-bottom: 5px; border-bottom: 1px solid #ccc; }
        </style>
        <?php
    }
}

// Instantiate the plugin class
function bundle_save_plugin_init() {
    new Bundle_Save_Plugin();
}
add_action( 'plugins_loaded', 'bundle_save_plugin_init' );

// Activation hook for default options
function bundle_save_activate() {
    if ( ! class_exists( 'Bundle_Save_Plugin' ) ) {
        // Normalmente não é necessário, pois o plugin já estaria carregado para o hook de ativação disparar.
        // Mas como uma medida de segurança extra.
        // require_once BUNDLE_SAVE_PLUGIN_FILE;
    }

    // Verifica se as opções já existem
    if ( false === get_option( 'bundle_save_options' ) && class_exists( 'Bundle_Save_Plugin' ) ) {
        // Para acessar default_options de forma segura, idealmente seria estático ou por um getter.
        // Mantendo a estrutura próxima, instanciamos temporariamente.
        $plugin_instance_for_defaults = new Bundle_Save_Plugin();
        // A propriedade default_options foi tornada pública para este acesso.
        $default_opts = $plugin_instance_for_defaults->default_options;
        if (is_array($default_opts)) { // Garante que temos um array para salvar
            update_option( 'bundle_save_options', $default_opts );
        }
    }
}
register_activation_hook( BUNDLE_SAVE_PLUGIN_FILE, 'bundle_save_activate' );