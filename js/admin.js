/**
 * WooCommerce One Click Checkout by GetMogul
 * https://getmogul.com
 */
jQuery( document ).ready( function( $ ) {
  $enabled = $( '#gm_wocc_enabled' );
  $enabled.on( 'change', function () {
    if ( $enabled.prop( 'checked' ) ) {
      $( '#gm_wocc_button_cart' ).closest( 'tr' ).show();
      $( '#gm_wocc_button_checkout' ).closest( 'tr' ).show();
    } else {
      $( '#gm_wocc_button_cart' ).closest( 'tr' ).hide();
      $( '#gm_wocc_button_checkout' ).closest( 'tr' ).hide();
    }
  } );
  $enabled.change();
  $( document.body ).on( 'gm-wocc-url-builder-modal', function () {
    $( '#plugin-information-footer button' ).on( 'click', tb_remove );
    var $product_template_clone = $( '#gm-wocc-product-template' );
    var $product_template = $product_template_clone.clone().removeClass( 'hidden' ).removeAttr( 'id' );
    $product_template_clone.remove();
    var $product_table_body = $( '#gm-wocc-product-table tbody' );
    $( '#gm-wocc-product-add' ).on( 'click', function ( e ) {
      e.preventDefault();
      $product_table_body.append( $product_template.clone() );
      $( document.body ).trigger( 'wc-enhanced-select-init' );
    } );
    var first_product = true;
    function updateURL() {
      var destination_url = $( '#gm-wocc-destination-url' ).val();
      var params_list = [];
      var $products = $( '#gm-wocc-import-form :input[name="gm-wocc-products[]"]' );
      var $quantities = $( '#gm-wocc-import-form :input[name="gm-wocc-quantities[]"]' );
      var products_elements = [];
      $products.each( function ( index, el ) {
        var val = $( el ).val();
        if ( val ) {
          products_elements.push( $( el ).val() + '|' + $quantities.eq( index ).val() );
        }
      } );
      if ( products_elements.length > 0 ) {
        params_list.push( 'gm-wocc-products=' + products_elements.join( ',' ) );
      }

      var $coupons = $( '#gm-wocc-import-form :input[name="gm-wocc-coupons[]"]' );
      var coupons_elements = [];
      var url_format = false;
      $coupons.each( function ( index, el ) {
        var val = $( el ).val();
        if ( val ) {
          if ( val.indexOf( ',' ) !== -1 ) {
            url_format = true;
          }
          coupons_elements.push( val );
        }
      } );
      if ( coupons_elements.length > 0 ) {
        if ( url_format ) {
          params_list.push( $coupons.serialize() );
        } else {
          params_list.push( 'gm-wocc-coupons=' + coupons_elements.join( ',' ) );
        }
      }

      var current_cart = $( '#gm-wocc-import-form :input[name="gm-wocc-current-cart"]' ).val();
      if ( current_cart ) {
        params_list.push( 'gm-wocc-current-cart=' + current_cart );
      }

      var shipping_method = $( '#gm-wocc-import-form :input[name="gm-wocc-shipping-method"]' ).val();
      if ( shipping_method ) {
        params_list.push( 'gm-wocc-shipping-method=' + shipping_method );
      }

      var $order_received = $( '#gm-wocc-import-form :input[name="gm-wocc-redirect-order-received"]' );
      if ( $order_received.prop( 'checked' ) ) {
        params_list.push( 'gm-wocc-redirect-order-received=' + $order_received.val() );
      }

      var params = params_list.join( '&' );
      var sep = '?';
      var url = '';
      if ( destination_url ) {
        if ( destination_url.indexOf( '?' ) > -1 ) {
          sep = '&';
        }
      } else {
        destination_url = '';
      }
      var url = destination_url + sep + params;
      $( '#gm-wocc-url' ).val( url );
    }
    $( '#gm-wocc-import-form' ).on( 'click', 'a[href="#gm-wocc-product-delete"]', function ( e ) {
      e.preventDefault();
      $( this ).closest( 'tr' ).remove();
      updateURL();
    } );
    $( '#gm-wocc-import-form' ).on( 'change', 'select, input', updateURL );
    $( '#gm-wocc-url-copy' ).on( 'click', function ( e ) {
      var text = $( '#gm-wocc-url' ).val();
      var node = document.createElement('pre');
      node.textContent = text;
      document.body.appendChild(node);
      var selection = window.getSelection();
      var range = document.createRange();
      range.selectNodeContents( node );
      selection.removeAllRanges()
      selection.addRange(range);
      try {
        var successful = document.execCommand('copy');
      } catch(err) {
        var successful = false;
      }
      if ( ! successful ) {
        alert( gm_wocc_admin.unable_to_copy_message );
      }
      document.body.removeChild(node);
    } );
    var $order_received_row = $( '#gm-wocc-redirect-order-received-row' );
    $( '#gm-wocc-redirect-order-received' ).on( 'change', function () {
      if ( $( this ).prop( 'checked' ) ) {
        $order_received_row.addClass( 'hidden' );
      } else {
        $order_received_row.removeClass( 'hidden' );
      }
    } );
    $( document.body ).trigger( 'init_tooltips' );
    $( document.body ).trigger( 'wc-enhanced-select-init' );
  } );
} );