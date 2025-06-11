<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BS_Activator {
    
    public static function activate() {
        if ( ! get_option( 'bs_campaigns' ) ) {
            update_option( 'bs_campaigns', self::get_default_options() );
        }
    }

    public static function get_default_options() {
        return [
            'enabled' => 1,
            'primary_color' => '#e92d3b',
            'apply_to' => 'all',
            'product_ids' => [],
            'category_ids' => [],
            'tiers' => [
                [
                    'qty' => 1,
                    'discount_type' => 'percentage',
                    'discount_value' => 0,
                    'title' => 'Compre 1',
                    'badge' => '',
                    'label' => '' // NOVO CAMPO
                ],
                [
                    'qty' => 2,
                    'discount_type' => 'percentage',
                    'discount_value' => 10,
                    'title' => 'Compre 2 e Ganhe 10% OFF',
                    'badge' => 'Popular',
                    'label' => '+ Frete Grátis' // NOVO CAMPO
                ],
                [
                    'qty' => 3,
                    'discount_type' => 'percentage',
                    'discount_value' => 15,
                    'title' => 'Compre 3 e Ganhe 15% OFF',
                    'badge' => 'Melhor Valor',
                    'label' => '+ Frete Grátis' // NOVO CAMPO
                ]
            ]
        ];
    }
}