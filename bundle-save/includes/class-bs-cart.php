<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BS_Cart {

    public function __construct() {
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_offer_data_to_cart_item' ], 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'apply_offer_discount' ], 20, 1 );
    }

    public function add_offer_data_to_cart_item( $cart_item_data, $product_id ) {
        if ( isset( $_POST['bs_selected_tier'] ) && isset( $_POST['bs_campaign_id'] ) ) {
            $cart_item_data['bs_offer_tier'] = absint( $_POST['bs_selected_tier'] );
            $cart_item_data['bs_campaign_id'] = absint( $_POST['bs_campaign_id'] );
        }
        return $cart_item_data;
    }

    public function apply_offer_discount( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['bs_campaign_id'] ) ) {
                $campaign_id = $cart_item['bs_campaign_id'];
                $tiers = get_post_meta($campaign_id, '_bs_tiers', true);
                if ( empty($tiers) ) continue;

                $product_qty = $cart_item['quantity'];
                
                $applicable_tier = null;
                usort($tiers, function($a, $b) { return $b['qty'] <=> $a['qty']; });

                foreach($tiers as $tier) {
                    if ($product_qty >= $tier['qty']) {
                        $applicable_tier = $tier;
                        break;
                    }
                }
                
                if ( $applicable_tier ) {
                    $base_price = (float) $cart_item['data']->get_regular_price();
                    $discount_type = $applicable_tier['discount_type'];
                    $discount_value = (float) $applicable_tier['discount_value'];

                    $new_price = $base_price;
                    if ($discount_type === 'percentage') {
                        $new_price = $base_price * (1 - ($discount_value / 100));
                    } elseif ($discount_type === 'fixed') {
                        $new_price = $base_price - $discount_value;
                    }

                    $cart_item['data']->set_price( max(0, $new_price) );
                }
            }
        }
    }
}