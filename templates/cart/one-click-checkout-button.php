<?php
/**
 * One Click Checkout Button
 *
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
?>
<div class="gm-wocc-checkout-button-wrapper">
  <a href="<?php echo esc_url( $redirect_url ); ?>" class="gm-wocc-checkout-button checkout-button button alt wc-forward">
    <?php esc_html_e( '1-Click checkout', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
  </a>
  <p class="gm-wocc-checkout-separator">- <?php esc_html_e( 'Or', 'woocommerce-one-click-checkout-by-getmogul' ); ?> -</p>
</div>