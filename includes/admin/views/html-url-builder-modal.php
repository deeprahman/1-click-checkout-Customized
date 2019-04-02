<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<form id="gm-wocc-import-form">
  <table class="form-table">
    <tbody>
      <tr>
        <th>
          <?php _e( 'Products to checkout', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'If you leave products empty and select the Checkout option for the Current Cart, it will check out whatever is in the cart when the URL is visited (if something). If you select a variable product without specifying a variation, it will add the default variation, or if not set, the first variation.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <table id="gm-wocc-product-table" class="widefat">
            <thead>
              <tr>
                <th><?php _e( 'Product', 'woocommerce-one-click-checkout-by-getmogul' ); ?></th>
                <th><?php _e( 'Quantity', 'woocommerce-one-click-checkout-by-getmogul' ); ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr id="gm-wocc-product-template" class="gm-wocc-product hidden">
                <td>
                  <select class="wc-product-search" name="gm-wocc-products[]" data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce-one-click-checkout-by-getmogul' ); ?>" data-action="woocommerce_json_search_products_and_variations"></select>
                </td>
                <td>
                  <input type="number" min="0" name="gm-wocc-quantities[]" value="1" />
                </td>
                <td>
                  <a href="#gm-wocc-product-delete" class="button"><?php _e( 'Delete', 'woocommerce-one-click-checkout-by-getmogul' ); ?></a>
                </td>
              </tr>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="3">
                  <a id="gm-wocc-product-add" href="#gm-wocc-product-add" class="button"><?php _e( 'Add Product', 'woocommerce-one-click-checkout-by-getmogul' ); ?></a>
                </th>
              </tr>
            </tfoot>
          </table>

        </td>
      </tr>
      <tr>
        <th>
          <?php _e( 'Coupons', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'Select the coupons to apply to this order.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <select class="wc-enhanced-select" multiple name="gm-wocc-coupons[]">
            <!-- <option value=""><?php _e( 'None', 'woocommerce-one-click-checkout-by-getmogul' ); ?></option> -->
            <?php
            foreach ( $this->get_coupon_codes() as $code ) :
              ?>
              <option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $code ); ?></option>
              <?php
            endforeach;
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <th>
          <?php _e( 'Current Cart', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'If the customer has already something in the cart, you can specify what to do with it: Restore (checkout the products above and restore the current cart as it was), Checkout (checkout the products above and the products in the cart), Discard (checkout only the products above and discard the current cart).', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <select class="wc-enhanced-select" name="gm-wocc-current-cart">
            <option value=""><?php _e( 'Restore', 'woocommerce-one-click-checkout-by-getmogul' ); ?></option>
            <option value="checkout"><?php _e( 'Checkout', 'woocommerce-one-click-checkout-by-getmogul' ); ?></option>
            <option value="discard" ><?php _e( 'Discard', 'woocommerce-one-click-checkout-by-getmogul' ); ?></option>
          </select>
        </td>
      </tr>
      <tr>
        <th>
          <?php _e( 'Shipping Method', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'If you select a shipping method different than the default for the order, you need to make sure the shipping method will be available for the selected products and the customer region.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <select class="wc-enhanced-select" name="gm-wocc-shipping-method">
            <option value=""><?php _e( 'Default shipping method for the order', 'woocommerce-one-click-checkout-by-getmogul' ); ?></option>
            <?php
            foreach ( $this->get_shipping_zones_methods() as $shipping_zone ) :
              $zone_name = $shipping_zone['zone_name'];
              foreach ( $shipping_zone['shipping_methods'] as $shipping_method ) :
                $method_title = $shipping_method['title'];
                $method_id = $shipping_method['method_id'];
                $instance_id = $shipping_method['instance_id'];
                ?>
                <option value="<?php echo $method_id . ':' . $instance_id ?>"><?php echo $zone_name . ': ' . $method_title; ?></option>
                <?php
              endforeach;
            endforeach;
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <th>
          <?php _e( 'Redirect to Order Received page', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'The customer will go to the standard Order Received page after the checkout.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <input type="checkbox" id="gm-wocc-redirect-order-received" name="gm-wocc-redirect-order-received" value="yes" checked />
        </td>
      </tr>
      <tr id="gm-wocc-redirect-order-received-row" class="hidden">
        <th>
          <?php _e( 'Redirect URL', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'This is where the customer will go after the checkout.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <input type="text" id="gm-wocc-destination-url" value="<?php echo esc_url( get_site_url() ); ?>" />
        </td>
      </tr>
      <tr>
        <th>
          <?php _e( 'One Click Checkout URL', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
          <?php echo wc_help_tip( __( 'The final URL that you can copy.', 'woocommerce-one-click-checkout-by-getmogul' ) ); ?>
        </th>
        <td>
          <input type="text" id="gm-wocc-url" readonly />
          <button type="button" id="gm-wocc-url-copy" class="button button-secondary"><?php _e( 'Copy to Clipboard', 'woocommerce-one-click-checkout-by-getmogul' ) ?></button>
        </td>
      </tr>
    </tfoot>
  </table>
  <div id="plugin-information-footer">
    <button type="button" class="button button-secondary right">
      <?php _e( 'Close', 'woocommerce-one-click-checkout-by-getmogul' ); ?>
    </button>
  </div>
</form>
<script>jQuery( document.body ).trigger( 'gm-wocc-url-builder-modal' );</script>
<br>
<br>
<br>
<br>