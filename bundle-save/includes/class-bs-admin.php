<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BS_Admin {

    public function __construct() {
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_bs_campaign', [ $this, 'save_meta_data' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'bs_campaign_settings',
            __( 'Níveis de Desconto da Campanha', 'bundle-save' ),
            [ $this, 'render_tiers_metabox' ],
            'bs_campaign', 'normal', 'high'
        );
        add_meta_box(
            'bs_campaign_conditions',
            __( 'Condições e Status', 'bundle-save' ),
            [ $this, 'render_conditions_metabox' ],
            'bs_campaign', 'side', 'high'
        );
    }

    public function render_conditions_metabox($post) {
        wp_nonce_field( 'bs_save_campaign_meta', 'bs_meta_nonce' );

        $status     = get_post_meta($post->ID, '_bs_status', true) ?: 'active';
        $apply_to   = get_post_meta($post->ID, '_bs_apply_to', true) ?: 'all';
        $product_ids = get_post_meta($post->ID, '_bs_product_ids', true) ?: [];
        $category_ids = get_post_meta($post->ID, '_bs_category_ids', true) ?: [];
        ?>
        <div class="bs-metabox-content">
            <p>
                <label for="bs_status"><strong><?php esc_html_e('Status da Campanha', 'bundle-save'); ?></strong></label><br>
                <select name="bs_status" id="bs_status" style="width:100%;">
                    <option value="active" <?php selected($status, 'active'); ?>><?php esc_html_e('Ativa', 'bundle-save'); ?></option>
                    <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inativa', 'bundle-save'); ?></option>
                </select>
            </p>
            <hr>
            <p>
                <label for="bs_apply_to"><strong><?php esc_html_e('Aplicar a:', 'bundle-save'); ?></strong></label><br>
                <select name="bs_apply_to" id="bs_apply_to" style="width:100%;">
                   <option value="all" <?php selected($apply_to, 'all'); ?>><?php esc_html_e('Todos os Produtos', 'bundle-save'); ?></option>
                   <option value="products" <?php selected($apply_to, 'products'); ?>><?php esc_html_e('Produtos Específicos', 'bundle-save'); ?></option>
                   <option value="categories" <?php selected($apply_to, 'categories'); ?>><?php esc_html_e('Categorias Específicas', 'bundle-save'); ?></option>
                </select>
            </p>
            <div id="bs_products_setting" class="bs-conditional-field">
                 <select class="wc-product-search" multiple="multiple" style="width: 100%;" name="bs_product_ids[]" data-placeholder="<?php esc_attr_e( 'Pesquisar produtos…', 'bundle-save' ); ?>">
                    <?php foreach ( $product_ids as $product_id ) {
                        $product = wc_get_product( $product_id );
                        if ( is_object( $product ) ) echo '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . wp_strip_all_tags( $product->get_formatted_name() ) . '</option>';
                    } ?>
                </select>
            </div>
            <div id="bs_categories_setting" class="bs-conditional-field">
                 <select class="wc-category-search" multiple="multiple" style="width: 100%;" name="bs_category_ids[]" data-placeholder="<?php esc_attr_e( 'Pesquisar categorias…', 'bundle-save' ); ?>">
                    <?php foreach ( $category_ids as $category_id ) {
                        $term = get_term_by( 'id', $category_id, 'product_cat' );
                        if ( $term && ! is_wp_error( $term ) ) echo '<option value="' . esc_attr( $category_id ) . '" selected="selected">' . esc_html( $term->name ) . '</option>';
                    } ?>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_tiers_metabox($post) {
        $tiers = get_post_meta($post->ID, '_bs_tiers', true);
        if (empty($tiers)) {
            $tiers = BS_Activator::get_default_options()['tiers'];
        }
        ?>
        <div class="bs-metabox-content">
            <p><?php esc_html_e('Configure os níveis de desconto por quantidade. Arraste as linhas para reordenar.', 'bundle-save'); ?></p>
            
            <table class="wp-list-table widefat striped" id="bs-tiers-table">
                <thead>
                    <tr>
                        <th class="sort-handle"></th>
                        <th class="column-qty"><?php esc_html_e( 'Quantidade', 'bundle-save' ); ?></th>
                        <th class="column-title"><?php esc_html_e( 'Título', 'bundle-save' ); ?></th>
                        <th class="column-discount-type"><?php esc_html_e( 'Tipo Desconto', 'bundle-save' ); ?></th>
                        <th class="column-discount-value"><?php esc_html_e( 'Valor Desconto', 'bundle-save' ); ?></th>
                        <th class="column-badge"><?php esc_html_e( 'Badge', 'bundle-save' ); ?></th>
                        <th class="column-label"><?php esc_html_e( 'Label Extra', 'bundle-save' ); ?></th>
                        <th class="actions-column"><?php esc_html_e( 'Ações', 'bundle-save' ); ?></th>
                    </tr>
                </thead>
                <tbody id="bs_tiers_list">
                    <?php foreach($tiers as $i => $tier): ?>
                        <tr class="bs-tier-row">
                            <td class="sort-handle"><span class="dashicons dashicons-menu"></span></td>
                            <td class="column-qty"><input type="number" min="1" class="small-text" name="bs_tiers[<?php echo $i; ?>][qty]" value="<?php echo esc_attr($tier['qty']); ?>" /></td>
                            <td class="column-title"><input type="text" name="bs_tiers[<?php echo $i; ?>][title]" value="<?php echo esc_attr($tier['title']); ?>" /></td>
                            <td class="column-discount-type">
                                <select name="bs_tiers[<?php echo $i; ?>][discount_type]">
                                    <option value="percentage" <?php selected($tier['discount_type'], 'percentage'); ?>><?php esc_html_e('Porcentagem (%)', 'bundle-save'); ?></option>
                                    <option value="fixed" <?php selected($tier['discount_type'], 'fixed'); ?>><?php esc_html_e('Fixo por Item ($)', 'bundle-save'); ?></option>
                                </select>
                            </td>
                            <td class="column-discount-value">
                                <input type="text" class="wc_input_price" name="bs_tiers[<?php echo $i; ?>][discount_value]" value="<?php echo esc_attr($tier['discount_value']); ?>" />
                            </td>
                            <td class="column-badge"><input type="text" name="bs_tiers[<?php echo $i; ?>][badge]" value="<?php echo esc_attr($tier['badge']); ?>"/></td>
                            <td class="column-label"><input type="text" name="bs_tiers[<?php echo $i; ?>][label]" value="<?php echo esc_attr($tier['label'] ?? ''); ?>"/></td>
                            <td class="actions-column"><a href="#" class="bs-remove-tier button-link-delete"><?php esc_html_e('Remover', 'bundle-save'); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p>
                <button type="button" class="button button-secondary" id="add_bs_tier"><span class="dashicons dashicons-plus"></span> <?php esc_html_e('Adicionar Nível', 'bundle-save'); ?></button>
            </p>
        </div>

        <table style="display:none;">
            <tr id="bs-tier-template">
                <td class="sort-handle"><span class="dashicons dashicons-menu"></span></td>
                <td class="column-qty"><input type="number" min="1" class="small-text" name="bs_tiers[__INDEX__][qty]" value="1" /></td>
                <td class="column-title"><input type="text" name="bs_tiers[__INDEX__][title]" value="" /></td>
                <td class="column-discount-type">
                    <select name="bs_tiers[__INDEX__][discount_type]">
                        <option value="percentage"><?php esc_html_e('Porcentagem (%)', 'bundle-save'); ?></option>
                        <option value="fixed"><?php esc_html_e('Fixo por Item ($)', 'bundle-save'); ?></option>
                    </select>
                </td>
                <td class="column-discount-value">
                    <input type="text" class="wc_input_price" name="bs_tiers[__INDEX__][discount_value]" value="0" />
                </td>
                <td class="column-badge"><input type="text" name="bs_tiers[__INDEX__][badge]" value="" /></td>
                <td class="column-label"><input type="text" name="bs_tiers[__INDEX__][label]" value="" /></td>
                <td class="actions-column"><a href="#" class="bs-remove-tier button-link-delete"><?php esc_html_e('Remover', 'bundle-save'); ?></a></td>
            </tr>
        </table>
        <?php
    }

    public function save_meta_data($post_id) {
        if (!isset($_POST['bs_meta_nonce']) || !wp_verify_nonce($_POST['bs_meta_nonce'], 'bs_save_campaign_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta($post_id, '_bs_status', sanitize_text_field($_POST['bs_status'] ?? 'inactive'));
        update_post_meta($post_id, '_bs_apply_to', sanitize_text_field($_POST['bs_apply_to'] ?? 'all'));
        update_post_meta($post_id, '_bs_product_ids', isset($_POST['bs_product_ids']) ? array_map('intval', $_POST['bs_product_ids']) : []);
        update_post_meta($post_id, '_bs_category_ids', isset($_POST['bs_category_ids']) ? array_map('intval', $_POST['bs_category_ids']) : []);

        $sanitized_tiers = [];
        if (isset($_POST['bs_tiers'])) {
            foreach ($_POST['bs_tiers'] as $tier_data) {
                // --- CORREÇÃO APLICADA AQUI ---
                // Agora a verificação principal é se o TÍTULO está vazio.
                if (empty(trim($tier_data['title']))) {
                    continue;
                }

                $sanitized_tiers[] = [
                    'qty'            => absint($tier_data['qty']),
                    'discount_type'  => sanitize_text_field($tier_data['discount_type']),
                    'discount_value' => wc_format_decimal($tier_data['discount_value']),
                    'title'          => sanitize_text_field($tier_data['title']),
                    'badge'          => sanitize_text_field($tier_data['badge']),
                    'label'          => sanitize_text_field($tier_data['label']),
                ];
            }
        }
        update_post_meta($post_id, '_bs_tiers', $sanitized_tiers);
    }

    public function enqueue_admin_assets($hook) {
        global $post;
        if (($hook != 'post.php' && $hook != 'post-new.php') || !isset($post->post_type) || $post->post_type != 'bs_campaign') {
            return;
        }

        wp_enqueue_style('bs-admin-styles', BS_PLUGIN_URL . 'assets/css/admin.css', [], BS_PLUGIN_VERSION);
        wp_enqueue_script('bs-admin-scripts', BS_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], BS_PLUGIN_VERSION, true);

        wp_localize_script('bs-admin-scripts', 'bs_admin_params', [
            'i18n' => [
                'remove_tier_confirm' => __('Tem certeza que deseja remover este nível?', 'bundle-save'),
            ]
        ]);
        
        wp_enqueue_script('select2');
        wp_enqueue_style('select2');
    }
}