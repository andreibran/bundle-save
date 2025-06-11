<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$base_price = (float) $product->get_price();
?>
<div id="bs-offer-box" class="bs-offer-grid">
    <input type="hidden" name="bs_selected_tier" value="0" />
    <input type="hidden" name="bs_campaign_id" value="<?php echo esc_attr( $campaign['id'] ); ?>" />
    
    <?php foreach ( $campaign['tiers'] as $index => $tier ) : 
        $unit_price = $base_price;
        if ($tier['discount_type'] === 'percentage') {
            $unit_price = $base_price * (1 - ($tier['discount_value'] / 100));
        } else {
            $unit_price = $base_price - $tier['discount_value'];
        }
    ?>
    <label class="bs-offer-card" 
           data-tier-index="<?php echo esc_attr( $index ); ?>"
           data-qty="<?php echo esc_attr( $tier['qty'] ); ?>"
           <?php if ( ! empty( $tier['badge'] ) ) echo 'data-badge="' . esc_attr( $tier['badge'] ) . '"'; ?>>
        
        <input type="radio" name="bs_offer_option" value="<?php echo esc_attr( $index ); ?>">
        
        <div class="bs-offer-info">
            <strong>
                <?php echo esc_html( $tier['title'] ); ?>
                <?php if ( ! empty( $tier['label'] ) ) : ?>
                    <span class="bs-offer-label"><?php echo esc_html( $tier['label'] ); ?></span>
                <?php endif; ?>
            </strong>
            <em><?php echo wp_kses_post( wc_price($unit_price) ); ?> / por item</em>
        </div>

        <span class="bs-offer-total-price"><?php echo wp_kses_post( wc_price( $unit_price * $tier['qty'] ) ); ?></span>
    </label>
    <?php endforeach; ?>
</div>