/**
 * WooCommerce One Click Checkout by GetMogul
 * https://getmogul.com
 */
jQuery( document ).ready( function( $ ) {
  var url = $( '.gm-wocc-checkout-button' ).attr( 'href' );
  if ( url ) {
    var sep = '?';
    if ( url.indexOf( '?' ) > -1 ) {
      sep = '&';
    }
    function update_button() {
      var shipping_method = $( '#shipping_method input.shipping_method:checked').val();
      $( '.gm-wocc-checkout-button' ).attr( 'href', url + ( shipping_method !== undefined ? sep + 'gm-wocc-shipping-method=' + shipping_method : '' ) );
    }
    update_button();
    $( document.body ).on( 'updated_checkout', update_button );
    $( document.body ).on( 'updated_shipping_method', update_button );
  }
} );