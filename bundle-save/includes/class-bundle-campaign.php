<?php
namespace BundleSave;

if (!defined('ABSPATH')) exit;

class Bundle_Campaign {
    private $post_type = 'bundle_campaign';
    private $meta_prefix = '_bundle_';

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_' . $this->post_type, [$this, 'save_campaign']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // AJAX handlers for product search
        add_action('wp_ajax_bundle_search_products', [$this, 'ajax_search_products']);
    }

    public function register_post_type() {
        register_post_type($this->post_type, [
            'labels' => [
                'name' => __('Bundle Campaigns', 'bundle-save'),
                'singular_name' => __('Bundle Campaign', 'bundle-save'),
                'add_new' => __('Add Campaign', 'bundle-save'),
                'add_new_item' => __('Add New Campaign', 'bundle-save'),
                'edit_item' => __('Edit Campaign', 'bundle-save'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'bundle-save',
            'supports' => ['title'],
            'capability_type' => 'post',
            'hierarchical' => false,
            'menu_position' => 58,
            'register_meta_box_cb' => [$this, 'add_meta_boxes']
        ]);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'bundle-campaign-basics',
            __('Campaign Settings', 'bundle-save'),
            [$this, 'render_basics_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'bundle-campaign-targeting',
            __('Display Settings', 'bundle-save'),
            [$this, 'render_targeting_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'bundle-campaign-offers',
            __('Bundle Offers', 'bundle-save'),
            [$this, 'render_offers_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'bundle-campaign-style',
            __('Appearance', 'bundle-save'),
            [$this, 'render_style_meta_box'],
            $this->post_type,
            'normal',
            'high'
        );

        add_meta_box(
            'bundle-campaign-preview',
            __('Live Preview', 'bundle-save'),
            [$this, 'render_preview_meta_box'],
            $this->post_type,
            'side',
            'high'
        );
    }

    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        if (!$screen || $this->post_type !== $screen->post_type) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_style(
            'bundle-campaign-admin',
            BUNDLE_SAVE_PLUGIN_URL . 'assets/admin/bundle-campaign.css',
            ['wp-color-picker'],
            BUNDLE_SAVE_VERSION
        );

        wp_enqueue_script(
            'bundle-campaign-admin',
            BUNDLE_SAVE_PLUGIN_URL . 'assets/admin/bundle-campaign.js',
            ['jquery', 'wp-color-picker', 'wp-util'],
            BUNDLE_SAVE_VERSION,
            true
        );

        wp_localize_script('bundle-campaign-admin', 'bundleCampaign', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bundle_campaign_nonce'),
            'i18n' => [
                'searchPlaceholder' => __('Search products...', 'bundle-save'),
                'noResults' => __('No products found', 'bundle-save'),
                'addOffer' => __('Add Offer', 'bundle-save'),
                'removeOffer' => __('Remove', 'bundle-save'),
            ]
        ]);
    }

    public function render_basics_meta_box($post) {
        wp_nonce_field('bundle_campaign_save', 'bundle_campaign_nonce');
        
        $priority = get_post_meta($post->ID, $this->meta_prefix . 'priority', true) ?: 10;
        $status = get_post_meta($post->ID, $this->meta_prefix . 'status', true) ?: 'draft';
        ?>
        <div class="bundle-campaign-basics">
            <div class="field-row">
                <label for="bundle_priority">
                    <?php _e('Priority', 'bundle-save'); ?>
                    <span class="description"><?php _e('Higher numbers show first', 'bundle-save'); ?></span>
                </label>
                <input type="number" 
                       id="bundle_priority" 
                       name="bundle_priority" 
                       value="<?php echo esc_attr($priority); ?>" 
                       min="1" 
                       step="1" 
                       class="small-text">
            </div>

            <div class="field-row">
                <label for="bundle_status">
                    <?php _e('Status', 'bundle-save'); ?>
                </label>
                <select id="bundle_status" name="bundle_status">
                    <option value="draft" <?php selected($status, 'draft'); ?>>
                        <?php _e('Draft', 'bundle-save'); ?>
                    </option>
                    <option value="active" <?php selected($status, 'active'); ?>>
                        <?php _e('Active', 'bundle-save'); ?>
                    </option>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_targeting_meta_box($post) {
        $targets = get_post_meta($post->ID, $this->meta_prefix . 'targets', true) ?: [];
        $target_type = $targets['type'] ?? 'products';
        $target_ids = $targets['ids'] ?? [];
        ?>
        <div class="bundle-campaign-targeting">
            <fieldset>
                <legend><?php _e('Where to Display', 'bundle-save'); ?></legend>
                
                <div class="target-type-selector">
                    <label>
                        <input type="radio" 
                               name="bundle_target_type" 
                               value="products" 
                               <?php checked($target_type, 'products'); ?>>
                        <?php _e('Select Products', 'bundle-save'); ?>
                    </label>
                    
                    <label>
                        <input type="radio" 
                               name="bundle_target_type" 
                               value="categories" 
                               <?php checked($target_type, 'categories'); ?>>
                        <?php _e('Product Categories', 'bundle-save'); ?>
                    </label>
                    
                    <label>
                        <input type="radio" 
                               name="bundle_target_type" 
                               value="all" 
                               <?php checked($target_type, 'all'); ?>>
                        <?php _e('All Products', 'bundle-save'); ?>
                    </label>
                </div>

                <div class="target-selector" data-type="<?php echo esc_attr($target_type); ?>">
                    <div class="products-selector" <?php echo $target_type !== 'products' ? 'style="display:none"' : ''; ?>>
                        <select class="bundle-product-search" 
                                name="bundle_target_products[]" 
                                multiple="multiple" 
                                style="width: 100%;"
                                aria-label="<?php esc_attr_e('Search and select products', 'bundle-save'); ?>">
                            <?php
                            if (!empty($target_ids) && $target_type === 'products') {
                                foreach ($target_ids as $product_id) {
                                    $product = wc_get_product($product_id);
                                    if ($product) {
                                        printf(
                                            '<option value="%d" selected="selected">%s</option>',
                                            esc_attr($product_id),
                                            esc_html($product->get_name())
                                        );
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="categories-selector" <?php echo $target_type !== 'categories' ? 'style="display:none"' : ''; ?>>
                        <?php
                        $categories = get_terms([
                            'taxonomy' => 'product_cat',
                            'hide_empty' => false,
                        ]);
                        
                        if (!empty($categories) && !is_wp_error($categories)) {
                            echo '<div class="categories-list">';
                            foreach ($categories as $category) {
                                printf(
                                    '<label><input type="checkbox" name="bundle_target_categories[]" value="%d" %s> %s</label>',
                                    esc_attr($category->term_id),
                                    checked(in_array($category->term_id, $target_ids), true, false),
                                    esc_html($category->name)
                                );
                            }
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>
            </fieldset>
        </div>
        <?php
    }

    public function render_offers_meta_box($post) {
        $offers = get_post_meta($post->ID, $this->meta_prefix . 'offers', true) ?: [
            ['qty' => 1, 'discount_type' => 'percentage', 'discount_value' => 0, 'badge' => '', 'note' => ''],
            ['qty' => 2, 'discount_type' => 'percentage', 'discount_value' => 20, 'badge' => __('Most Popular', 'bundle-save'), 'note' => __('+ Free Shipping', 'bundle-save')],
            ['qty' => 3, 'discount_type' => 'percentage', 'discount_value' => 30, 'badge' => __('Best Value', 'bundle-save'), 'note' => __('+ Free Shipping', 'bundle-save')]
        ];
        ?>
        <div class="bundle-campaign-offers">
            <table class="widefat bundle-offers-table">
                <thead>
                    <tr>
                        <th><?php _e('Quantity', 'bundle-save'); ?></th>
                        <th><?php _e('Discount Type', 'bundle-save'); ?></th>
                        <th><?php _e('Discount Value', 'bundle-save'); ?></th>
                        <th><?php _e('Badge Text', 'bundle-save'); ?></th>
                        <th><?php _e('Note', 'bundle-save'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $index => $offer) : ?>
                    <tr class="bundle-offer-row">
                        <td>
                            <input type="number" 
                                   name="bundle_offers[<?php echo $index; ?>][qty]" 
                                   value="<?php echo esc_attr($offer['qty']); ?>"
                                   min="1" 
                                   max="99"
                                   class="small-text"
                                   required>
                        </td>
                        <td>
                            <select name="bundle_offers[<?php echo $index; ?>][discount_type]">
                                <option value="percentage" <?php selected($offer['discount_type'], 'percentage'); ?>>
                                    <?php _e('Percentage (%)', 'bundle-save'); ?>
                                </option>
                                <option value="fixed" <?php selected($offer['discount_type'], 'fixed'); ?>>
                                    <?php _e('Fixed Amount', 'bundle-save'); ?>
                                </option>
                            </select>
                        </td>
                        <td>
                            <input type="number" 
                                   name="bundle_offers[<?php echo $index; ?>][discount_value]" 
                                   value="<?php echo esc_attr($offer['discount_value']); ?>"
                                   step="0.01"
                                   min="0"
                                   class="small-text"
                                   required>
                        </td>
                        <td>
                            <input type="text" 
                                   name="bundle_offers[<?php echo $index; ?>][badge]" 
                                   value="<?php echo esc_attr($offer['badge']); ?>"
                                   class="regular-text">
                        </td>
                        <td>
                            <input type="text" 
                                   name="bundle_offers[<?php echo $index; ?>][note]" 
                                   value="<?php echo esc_attr($offer['note']); ?>"
                                   class="regular-text">
                        </td>
                        <td>
                            <?php if ($index > 0) : ?>
                            <button type="button" class="button remove-offer">
                                <?php _e('Remove', 'bundle-save'); ?>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($offers) < 3) : ?>
            <button type="button" class="button add-offer">
                <?php _e('Add Offer', 'bundle-save'); ?>
            </button>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_style_meta_box($post) {
        $style = get_post_meta($post->ID, $this->meta_prefix . 'style', true) ?: [];
        $primary_color = $style['primary_color'] ?? '#e92d3b';
        $font_family = $style['font_family'] ?? '';
        $border_radius = $style['border_radius'] ?? '10';
        $layout = $style['layout'] ?? 'vertical';
        ?>
        <div class="bundle-campaign-style">
            <div class="field-row">
                <label for="bundle_style_primary_color">
                    <?php _e('Primary Color', 'bundle-save'); ?>
                </label>
                <input type="text" 
                       id="bundle_style_primary_color" 
                       name="bundle_style[primary_color]" 
                       value="<?php echo esc_attr($primary_color); ?>" 
                       class="bundle-color-picker">
            </div>

            <div class="field-row">
                <label for="bundle_style_font_family">
                    <?php _e('Font Family', 'bundle-save'); ?>
                </label>
                <select id="bundle_style_font_family" name="bundle_style[font_family]">
                    <option value="" <?php selected($font_family, ''); ?>>
                        <?php _e('Default', 'bundle-save'); ?>
                    </option>
                    <option value="system-ui" <?php selected($font_family, 'system-ui'); ?>>
                        <?php _e('System UI', 'bundle-save'); ?>
                    </option>
                    <option value="Helvetica" <?php selected($font_family, 'Helvetica'); ?>>
                        Helvetica
                    </option>
                    <option value="Arial" <?php selected($font_family, 'Arial'); ?>>
                        Arial
                    </option>
                </select>
            </div>

            <div class="field-row">
                <label for="bundle_style_border_radius">
                    <?php _e('Border Radius', 'bundle-save'); ?>
                </label>
                <input type="number" 
                       id="bundle_style_border_radius" 
                       name="bundle_style[border_radius]" 
                       value="<?php echo esc_attr($border_radius); ?>"
                       min="0"
                       max="50"
                       class="small-text">
                <span class="unit">px</span>
            </div>

            <div class="field-row">
                <label for="bundle_style_layout">
                    <?php _e('Layout', 'bundle-save'); ?>
                </label>
                <select id="bundle_style_layout" name="bundle_style[layout]">
                    <option value="vertical" <?php selected($layout, 'vertical'); ?>>
                        <?php _e('Vertical', 'bundle-save'); ?>
                    </option>
                    <option value="horizontal" <?php selected($layout, 'horizontal'); ?>>
                        <?php _e('Horizontal', 'bundle-save'); ?>
                    </option>
                </select>
            </div>
        </div>
        <?php
    }

    public function render_preview_meta_box($post) {
        ?>
        <div class="bundle-campaign-preview">
            <iframe id="bundle-preview-frame" 
                    src="<?php echo esc_url(admin_url('admin-ajax.php?action=bundle_preview&post_id=' . $post->ID)); ?>"
                    style="width: 100%; height: 400px; border: 1px solid #ddd;">
            </iframe>
        </div>
        <?php
    }

    public function ajax_search_products() {
        check_ajax_referer('bundle_campaign_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_die(-1);
        }

        $term = sanitize_text_field($_GET['term']);
        
        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            's' => $term,
        ];

        $query = new \WP_Query($args);
        $products = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    $products[] = [
                        'id' => $product->get_id(),
                        'text' => $product->get_name(),
                        'price' => $product->get_price(),
                    ];
                }
            }
        }

        wp_reset_postdata();
        wp_send_json($products);
    }

    public function save_campaign($post_id) {
        if (!isset($_POST['bundle_campaign_nonce']) || 
            !wp_verify_nonce($_POST['bundle_campaign_nonce'], 'bundle_campaign_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Basic Settings
        update_post_meta($post_id, $this->meta_prefix . 'priority', 
            absint($_POST['bundle_priority'] ?? 10));
        
        update_post_meta($post_id, $this->meta_prefix . 'status',
            sanitize_key($_POST['bundle_status'] ?? 'draft'));

        // Targeting
        $target_type = sanitize_key($_POST['bundle_target_type'] ?? 'products');
        $target_ids = [];
        
        if ($target_type === 'products' && !empty($_POST['bundle_target_products'])) {
            $target_ids = array_map('absint', $_POST['bundle_target_products']);
        } elseif ($target_type === 'categories' && !empty($_POST['bundle_target_categories'])) {
            $target_ids = array_map('absint', $_POST['bundle_target_categories']);
        }

        update_post_meta($post_id, $this->meta_prefix . 'targets', [
            'type' => $target_type,
            'ids' => $target_ids
        ]);

        // Offers
        $offers = [];
        if (!empty($_POST['bundle_offers']) && is_array($_POST['bundle_offers'])) {
            foreach ($_POST['bundle_offers'] as $offer) {
                $offers[] = [
                    'qty' => absint($offer['qty']),
                    'discount_type' => sanitize_key($offer['discount_type']),
                    'discount_value' => floatval($offer['discount_value']),
                    'badge' => sanitize_text_field($offer['badge']),
                    'note' => sanitize_text_field($offer['note'])
                ];
            }
        }
        update_post_meta($post_id, $this->meta_prefix . 'offers', $offers);

        // Style
        $style = [
            'primary_color' => sanitize_hex_color($_POST['bundle_style']['primary_color'] ?? '#e92d3b'),
            'font_family' => sanitize_text_field($_POST['bundle_style']['font_family'] ?? ''),
            'border_radius' => absint($_POST['bundle_style']['border_radius'] ?? 10),
            'layout' => sanitize_key($_POST['bundle_style']['layout'] ?? 'vertical')
        ];
        update_post_meta($post_id, $this->meta_prefix . 'style', $style);
    }
}