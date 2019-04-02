<?php
/**
 * One Click Checkout Button Product
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

global $product;

?>
<div class="gm-wocc-checkout-button-wrapper gm-wocc-checkout-button-wrapper-product">
  <p class="gm-wocc-checkout-separator">- <?php esc_html_e( 'Or', 'woocommerce-one-click-checkout-by-getmogul' ); ?> -</p>
  <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
  <button type="submit" name="gm-wocc-add-to-cart" class="single_add_to_cart_button button alt gm-wocc-checkout-button"><?php esc_html_e( '1-Click checkout', 'woocommerce-one-click-checkout-by-getmogul' ); ?></button>
</div>
<div class="gm-wocc-checkout-button-clear"></div>