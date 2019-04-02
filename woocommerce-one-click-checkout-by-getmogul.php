<?php
/*
* Plugin Name: WooCommerce One Click Checkout by GetMogul
* Description: Build URLs that create and pay an order with one click using the customers's previous orders information. You can also add a 1-Click Checkout button to the cart and order pages.
* Version: 1.2.0
* Author: GetMogul
* Author URI: https://getmogul.com
* WC requires at least: 2.6
* WC tested up to: 3.5
* Text Domain: woocommerce-one-click-checkout-by-getmogul
* Domain Path: languages
* Plugin URI: https://getmogul.com
*/

// URL Structure:
// ?gm-wocc-products[]=12&gm-wocc-quantities[]=1&gm-wocc-coupons[]=test coupon&gm-wocc-current-cart=restore&gm-wocc-shipping-method=free_shipping:1

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}
if ( ! function_exists( 'WooCommerce_One_Click_Checkout_By_GetMogul' ) ) :
/**
 * WooCommerce One Click Checkout by GetMogul main plugin class.
 */
class WooCommerce_One_Click_Checkout_By_GetMogul {
  /** @var string The plugin name */
  public $name = 'WooCommerce One Click Checkout by GetMogul';
  /** @var string The plugin version */
  public $version = '1.2.0';
  /** @var string The minimum WooCommerce version required */
  public $wc_requires_at_least = '2.6';
  /** @var string The plugin URL */
  public $plugin_url = null;
  /** @var string The plugin path */
  public $plugin_path = null;
  /** @var string The template path */
  public $template_path = null;
  /** @var string The admin views path */
  public $admin_views_path = null;
  /** @var Woocommerce_One_Click_Checkout_By_GetMogul The single instance of the class. */
	protected static $_instance = null;

  /**
	 * Main Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 * @return WooCommerce_One_Click_Checkout_By_GetMogul Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

  /**
	 * Woocommerce_One_Click_Checkout_By_GetMogul constructor.
	 */
  public function __construct() {

    $this->plugin_file = __FILE__;
    $this->plugin_url = untrailingslashit( plugins_url( '/', $this->plugin_file ) );
    $this->plugin_path  = untrailingslashit( plugin_dir_path( $this->plugin_file ) );
    $this->template_path = $this->plugin_path . '/templates/';
    $this->admin_views_path  = $this->plugin_path . '/includes/admin/views/';

    add_action( 'init', array( $this, 'init' ) );

  }

  /**
	 * Initialize in hook.
	 */
  public function init() {
    if ( ! defined( 'WC_VERSION' ) || version_compare( WC_VERSION, $this->wc_requires_at_least, '<' ) ) {
      add_action( 'admin_notices', array( $this, 'woocommerce_inactive_notice' ) );
      add_action( 'network_admin_notices', array( $this, 'woocommerce_inactive_notice' ) );
      return;
    }

    /** 
     *  Shipping Integration 
     */
    include('includes/frontend/shipping-detail.php');  
    
    add_shortcode( 'one_click_button', array( $this, 'add_button' ));
    //add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'shortcode_btn_display' ), 15 );
    add_action( 'wp_footer', array( $this, 'modal_address' ) );
    add_action('wp_ajax_yith_wocc_add_address', array($this, 'shipping_ajax_handler'));
    add_action('wp_ajax_nopriv_yith_wocc_add_address', array($this, 'shipping_ajax_handler'));
    add_action( 'wp_enqueue_scripts', array( $this, 'one_click_enqueue_scripts' ), 15 );

    $this->id = 'gm_wocc';
    $this->param_products = str_replace( '_', '-', $this->id ) . '-products';
    $this->param_quantities = str_replace( '_', '-', $this->id ) . '-quantities';
    $this->param_current_cart = str_replace( '_', '-', $this->id ) . '-current-cart';
    $this->param_shipping = str_replace( '_', '-', $this->id ) . '-shipping-method';
    $this->param_coupons = str_replace( '_', '-', $this->id ) . '-coupons';
    $this->param_redirect_order_received = str_replace( '_', '-', $this->id ) . '-redirect-order-received';

    $this->enabled = get_option( 'gm_wocc_enabled', 'yes' );
    $this->enabled_button_product = get_option( 'gm_wocc_button_product', 'yes' );
    $this->enabled_button_cart = get_option( 'gm_wocc_button_cart', 'yes' );
    $this->enabled_button_checkout = get_option( 'gm_wocc_button_checkout', 'yes' );
    $this->gm_wocc_successful_payment_statuses = get_option( 'gm_wocc_successful_payment_statuses', 'yes' );

    if ( $this->enabled == 'yes' ) {
      $this->successful_payment_statuses = array_map( function( $status ) { return 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status; }, $this->gm_wocc_successful_payment_statuses );
      add_action( 'template_redirect', array( $this, 'parse_checkout_url' ) );
      add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ) );

      if ( $this->enabled_button_product == 'yes'  ) {;

        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_button_product' ) );


          add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'deep_stupid' ), 10, 0 );

          add_action( 'woocommerce_before_variations_form', array( $this, 'deep_stupid_variable' ), 10, 0 );


      }

      if ( $this->enabled_button_cart == 'yes' ) {
        add_action( 'woocommerce_proceed_to_checkout', array( $this, 'add_button' ) );
      }

      if ( $this->enabled_button_checkout == 'yes' ) {
        add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'add_button' ), 0 );
      }
    }

    if ( $this->enabled_button_checkout == 'yes' || $this->enabled_button_cart == 'yes' || $this->enabled_button_product == 'yes' ) {
      wp_enqueue_style( 'gm-wocc-one-click-checkout-button', $this->plugin_url . '/css/one-click-checkout-button.css', array(), $this->version );
      wp_enqueue_script( 'gm-wocc-one-click-checkout-button', $this->plugin_url . '/js/one-click-checkout-button.js', array( 'wc-checkout' ), $this->version, true );
    }

    add_filter( 'admin_init', array( $this, 'admin_init' ) );
    add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), array( $this, 'plugin_action_links' ) );
  }
  
    
  /**
   *  Enqueue scripts
   */
  public function one_click_enqueue_scripts(){
   
    $this->enabled_button_cart = get_option( 'gm_wocc_button_cart', 'yes' );
    $this->enabled_button_checkout = get_option( 'gm_wocc_button_checkout', 'yes' );  
    if ( $this->enabled_button_checkout == 'yes' || $this->enabled_button_cart == 'yes' ) {
      
        wp_enqueue_style( 'gm-wocc-one-click-checkout-button', $this->plugin_url . '/css/one-click-checkout-button.css', array(), $this->version );
        wp_enqueue_script( 'gm-wocc-one-click-checkout-button-js', $this->plugin_url . '/js/one-click-checkout-button.js',$this->version, true );
        wp_enqueue_script( 'gm-wocc-one-click-shipping-method', $this->plugin_url . '/js/one-click-shipping-method.js', $this->version, true );
    }

    wp_enqueue_script( 'gm-wocc-one-click-select2', $this->plugin_url . '/js/select2.min.js');
    $assets_path = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
    wp_enqueue_style( 'select2', $assets_path . '/css/select2.css' );
    
    wp_enqueue_style( 'nanoscroller-plugin', $this->plugin_url . '/css/perfect-scrollbar.css' );
    wp_enqueue_script( 'nanoscroller-plugin', $this->plugin_url . '/js/perfect-scrollbar.min.js', array('jquery'), $this->version, true );
    
  }

  
  public function modal_address(){

        // get form html
        $WooCommerce_One_Click_Checkout_Shipping = new WooCommerce_One_Click_Checkout_Shipping();
        $WooCommerce_One_Click_Checkout_Shipping->get_address_form_html();
        
  }
  
   public function shipping_ajax_handler() {
        
        $WooCommerce_One_Click_Checkout_Shipping = new WooCommerce_One_Click_Checkout_Shipping();
        return  $WooCommerce_One_Click_Checkout_Shipping->add_custom_shipping_address();
   }
  
  /**
   * If the user can checkout with one click.
   *
   * @return boolean User can checkout with one click
	 */
  public function can_checkout() {
    $user = wp_get_current_user();
    if ( $user->ID ) {
      $user_orders = wc_get_orders( array(
        'customer_id' => $user->ID,
      ) );
      if ( ! empty( $user_orders ) ) {
        foreach ( $user_orders as $user_order ) {
          if ( ! empty( $user_order->get_payment_method() ) && in_array( $user_order->get_status(), $this->successful_payment_statuses ) ) {
            return true;
          }
        }
      }
    }
    return false;
  }

  public function shortcode_btn_display(){
      echo do_shortcode('[one_click_button]');
  }
  /**
   * Add button if possible.
	 */
  public function add_button() {
    if ( $this->can_checkout() ) {
       wc_get_template( 'cart/one-click-checkout-button.php', array(
        'redirect_url' => add_query_arg( array(
          'gm-wocc-current-cart' => 'checkout',
          'gm-wocc-redirect-order-received' => 'yes'
        ), wc_get_checkout_url() ),
      ), '', $this->template_path );
    }
  }

  /**
   * Add button to product page if possible.
	 */
  public function add_button_product() {
    global $product;  
    echo '<div class="one-click-container">';
?>
        <div class="gm-wocc-checkout-button-wrapper gm-wocc-checkout-button-wrapper-product">
          <p class="gm-wocc-checkout-separator">- <?php esc_html_e( 'Or', 'woocommerce-one-click-checkout-by-getmogul' ); ?> -</p>
          <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
          <button type="submit" name="gm-wocc-add-to-cart" class="single_add_to_cart_button button alt gm-wocc-checkout-button"><?php esc_html_e( '1-Click checkout', 'woocommerce-one-click-checkout-by-getmogul' ); ?></button>
        </div>
        <div class="gm-wocc-checkout-button-clear"></div>
    <?php 

//     $WooCommerce_One_Click_Checkout_Shipping = new WooCommerce_One_Click_Checkout_Shipping();
//     echo $WooCommerce_One_Click_Checkout_Shipping->add_shipping_details();
     echo '</div>';
  }
    public function deep_stupid(){
        global $product;
        if($product instanceof WC_Product_Simple){


            $WooCommerce_One_Click_Checkout_Shipping = new WooCommerce_One_Click_Checkout_Shipping();
            echo $WooCommerce_One_Click_Checkout_Shipping->add_shipping_details();
        }
    }

    public function deep_stupid_variable(){
        global $product;
        if($product instanceof WC_Product_Variable){


            $WooCommerce_One_Click_Checkout_Shipping = new WooCommerce_One_Click_Checkout_Shipping();
            echo $WooCommerce_One_Click_Checkout_Shipping->add_shipping_details();
        }
    }
  /**
   * Action when the 1-Click checkout is from the product page.
	 */
  public function add_to_cart_redirect( $url ) {
    if ( isset( $_REQUEST['gm-wocc-add-to-cart'] ) ) {
      $url = add_query_arg( array(
        'gm-wocc-current-cart' => 'checkout',
        'gm-wocc-redirect-order-received' => 'yes',
        'gm-wocc-shipping-type' => $_REQUEST['_one_click_select_address']
      ), wc_get_checkout_url() );
    }
    return $url;
  }

  /**
	 * Init admin.
	 */
  public function admin_init() {
    add_thickbox();
    add_filter( 'woocommerce_payment_gateways_settings', function ( $settings ) {
      $updated_settings = array();
      foreach ( $settings as $section ) {
        $updated_settings[] = $section;
        if ( isset( $section['id'] ) && 'payment_gateways_options' == $section['id'] && isset( $section['type'] ) && 'sectionend' == $section['type'] ) {
          $updated_settings[] = array(
            'id' => 'gm_wooc_options',
            'title' => __( 'One Click Checkout', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'title',
          );
          $updated_settings[] = array(
            'id' => 'gm_wocc_enabled',
            'title' => __( 'One Click Checkout', 'woocommerce-one-click-checkout-by-getmogul' ),
            'desc' => __( 'Enable One Click Checkout', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'checkbox',
            'desc_tip' =>  sprintf( __( 'Enables the One Click Checkout. To create One Click Checkout URLs you can use %sthis generator%s.', 'woocommerce-one-click-checkout-by-getmogul' ), '<a href="' . admin_url('admin-ajax.php') . '?action=gm_wocc_url_builder_modal&width=850&height=550' . '" class="thickbox" title="' . __( 'One Click Checkout URL Generator', 'woocommerce-one-click-checkout-by-getmogul' ) . '">', '</a>' ),
            'default' => 'yes',
          );
          $updated_settings[] = array(
            'id' => 'gm_wocc_button_product',
            'title' => __( 'One Click Checkout Buttons', 'woocommerce-one-click-checkout-by-getmogul' ),
            'desc' => __( 'Product', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'checkbox',
            'desc_tip' => __( 'Adds a One Click Checkout button to the product page if possible.', 'woocommerce-one-click-checkout-by-getmogul' ),
            'default' => 'yes',
          );
          $updated_settings[] = array(
            'id' => 'gm_wocc_button_cart',
            'desc' => __( 'Cart', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'checkbox',
            'desc_tip' => __( 'Adds a One Click Checkout button to the cart if possible.', 'woocommerce-one-click-checkout-by-getmogul' ),
            'default' => 'yes',
          );
          $updated_settings[] = array(
            'id' => 'gm_wocc_button_checkout',
            'desc' => __( 'Checkout', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'checkbox',
            'desc_tip' => __( 'Adds a One Click Checkout button on top of the checkout page if possible.', 'woocommerce-one-click-checkout-by-getmogul' ),
            'default' => 'yes',
          );
          $updated_settings[] = array(
            'id' => 'gm_wocc_successful_payment_statuses',
            'title' => __( 'One Click Checkout Successful Payment Statuses', 'woocommerce-one-click-checkout-by-getmogul' ),
            'type' => 'multiselect',
            'class' => 'wc-enhanced-select',
            'desc' => __( 'These order statuses will be considered as having successful payment information.', 'woocommerce-one-click-checkout-by-getmogul' ),
            'options' => wc_get_order_statuses(),
            'default' => array(
              'wc-processing',
              'wc-completed',
              'wc-refunded',
            ),
          );
          $updated_settings[] = array(
            'id' => 'gm_wooc_options',
            'type' => 'sectionend',
          );
        }
      }
      return $updated_settings;
    } );
    wp_enqueue_script( 'gm-wocc-admin', $this->plugin_url . '/js/admin.js', array( 'jquery' ), $this->version, true );
    wp_localize_script( 'gm-wocc-admin', 'gm_wocc_admin', array( 'unable_to_copy_message' => __( 'Your browser doesn\'t support the copy to clipboard functionality. You need to copy the URL manually.', 'woocommerce-one-click-checkout-by-getmogul' ) ) );
    wp_enqueue_style( 'gm-wocc-admin', $this->plugin_url . '/css/admin.css', array(), $this->version );
    add_action( 'wp_ajax_gm_wocc_url_builder_modal', array( $this, 'gm_wocc_url_builder_modal') );
  }

  /**
	 * URL builder HTML for modal.
	 */
	public function gm_wocc_url_builder_modal() {
		include $this->admin_views_path . 'html-url-builder-modal.php';
		wp_die();
	}

  /**
	 * Parse checkout URL.
	 */
  public function parse_checkout_url() {
    if ( ! empty( $_GET[$this->param_products] ) || ( empty( $_GET[$this->param_products] ) && ! empty( $_GET[$this->param_current_cart] ) && $_GET[$this->param_current_cart] != 'discard' ) ) {
      if ( ! is_user_logged_in() ) {
        wc_add_notice( __( 'Please login before continuing', 'notice', 'woocommerce-one-click-checkout-by-getmogul' ) );
        wp_redirect( wc_get_page_permalink( 'myaccount' ) );
        exit;
      }

      $redirect_url = null;
      if ( ! isset( $_GET[$this->param_redirect_order_received] ) ) {
        $redirect_url = $this->get_current_url();
      }


      if ( $redirect_url && isset( $_GET[$this->param_products] ) ) {
        $redirect_url = remove_query_arg( $this->param_products, $redirect_url );
      }

      if ( $redirect_url && isset( $_GET[$this->param_quantities] ) ) {
        $redirect_url = remove_query_arg( $this->param_quantities, $redirect_url );
      }
      
      $user = wp_get_current_user();
      if ( $user->ID ) {
        $user_orders = wc_get_orders( array(
          'customer_id' => $user->ID,
        ) );
        if ( ! empty( $user_orders ) ) {
          foreach ( $user_orders as $user_order ) {
            $payment_method = $user_order->get_payment_method();
            if ( ! empty( $payment_method ) ) {
              $last_order = wc_get_order( $user_order->get_id() );
              break;
            }
          }
        }
      }

      $current_cart = null;
      if ( isset( $_GET[$this->param_current_cart] ) ) {
        $current_cart = $_GET[$this->param_current_cart];
        if ( $redirect_url ) {
          $redirect_url = remove_query_arg( $this->param_current_cart, $redirect_url );
        }
      }
      
      $shipping_selected = null;
      $shipping_address_selected = array();
      if( isset( $_GET['gm-wocc-shipping-type'])){
            $shipping_selected = $_GET['gm-wocc-shipping-type'];
            $shipping_address_selected = $this->get_shipping_address( $shipping_selected );
        if ( $redirect_url ) {
          $redirect_url = remove_query_arg( 'gm-wocc-shipping-type', $redirect_url );
        }
      }
      
      // Default is to restore the cart
      if ( ! $current_cart || $current_cart == 'restore' ) {
        $saved_cart = WC()->session->get( 'cart' );
      }
      if ( $current_cart != 'checkout' ) {
        WC()->cart->empty_cart();
      }


      if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
				define( 'WOOCOMMERCE_CHECKOUT', true );
			}

      $order = wc_create_order( array(
        'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
        'customer_id'   => $user->ID,
      ));
      $order->set_order_key( 'wc_' . apply_filters( 'woocommerce_generate_order_key', uniqid( 'order_' ) ) );

      $order->set_billing_first_name( $shipping_address_selected['first_name'] );
      $order->set_billing_last_name( $shipping_address_selected['last_name'] );
      $order->set_billing_company( $shipping_address_selected['company'] );
      $order->set_billing_address_1( $shipping_address_selected['address_1'] );
      $order->set_billing_address_2( $shipping_address_selected['address_2'] );
      $order->set_billing_city( $shipping_address_selected['city'] );
      $order->set_billing_state( $shipping_address_selected['state'] );
      $order->set_billing_postcode( $shipping_address_selected['postcode'] );
      $order->set_billing_country( $shipping_address_selected['country'] );
      $order->set_billing_email( $this->get_customer_property( 'billing', 'email', $last_order, $user ) );
      $order->set_billing_phone( $this->get_customer_property( 'billing', 'phone', $last_order, $user ) );

      $order->set_shipping_first_name( $shipping_address_selected['first_name'] );
      $order->set_shipping_last_name(  $shipping_address_selected['last_name'] );
      $order->set_shipping_company( $shipping_address_selected['company'] );
      $order->set_shipping_address_1( $shipping_address_selected['address_1'] );
      $order->set_shipping_address_2( $shipping_address_selected['address_2'] );
      $order->set_shipping_city( $shipping_address_selected['city'] );
      $order->set_shipping_state( $shipping_address_selected['state'] );
      $order->set_shipping_postcode( $shipping_address_selected['postcode'] );
      $order->set_shipping_country( $shipping_address_selected['country'] );

      $order->set_created_via( 'one-click-checkout' );
      $order->set_currency( get_woocommerce_currency() );
      $order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
      $order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
      $order->set_customer_user_agent( wc_get_user_agent() );

      WC()->shipping->reset_shipping();

			$cart_items = array();

      global $wp_filter;
      unset( $wp_filter['woocommerce_add_to_cart'] );
      unset( $wp_filter['woocommerce_cart_updated'] );
      $cart_item_defaults = array(
        'product_id' => 0,
        'quantity' => 1,
        'variation_id' => 0,
        'variation' => array(),
      );

      if ( is_array( $_GET[$this->param_products] ) ) {
        $products = $_GET[$this->param_products];
        $quantities = $_GET[$this->param_quantities];
      } else {
        $products = array();
        $quantities = array();
        $products_list = $this->get_list_separated_by( ',', $_GET[$this->param_products] );
        foreach ( $products_list as $index => $product_item ) {
          $product_item_list = $this->get_list_separated_by( '|', $product_item );
          $products[$index] = $product_item_list[0];
          if ( isset( $product_item_list[1] ) ) {
            $quantities[$index] = $product_item_list[1];
          }
        }
      }


      foreach ( $products as $index => $id ) {
        $product = wc_get_product( $id );
        if ( ! empty( $product ) ) {
          $cart_item = array();
          $product_type = $product->get_type();
          if ( $product_type == 'variation' ) {
            $cart_item['product_id'] = $product->get_parent_id();
            $cart_item['variation_id'] = $product->get_id();
            $cart_item['variation'] = $product->get_variation_attributes();
          } else if ( $product_type == 'variable' ) {
            if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
              $default_atts = $product->get_default_attributes();
            } else {
              $default_atts = $product->get_variation_default_attributes();
            }
            if ( ! empty( $default_atts ) ) {
              foreach( $default_atts as $key => $value ) {
                if( strpos( $key, 'attribute_' ) === 0 ) {
                    continue;
                }
                unset( $default_atts[ $key ] );
                $default_atts[ sprintf( 'attribute_%s', $key ) ] = $value;
              }
              if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
                $data_store = WC_Data_Store::load( 'product' );
                $variation_id = $data_store->find_matching_product_variation( $product, $default_atts );
              } else {
                $variation_id = $product->get_matching_variation( $default_atts );
              }
              $cart_item['product_id'] = $product->get_id();
              $cart_item['variation_id'] = $variation_id;
              $cart_item['variation'] = $default_atts;
            } else {
              $children_ids = $product->get_children();
              if ( ! empty( $children_ids ) ) {
                $variation = wc_get_product( $children_ids[0] );
                $cart_item['product_id'] = $variation->get_parent_id();
                $cart_item['variation_id'] = $variation->get_id();
                $cart_item['variation'] = $variation->get_variation_attributes();
              }
            }
          } else {
            $cart_item['product_id'] = $product->get_id();
          }
          if ( isset( $quantities[$index] ) && $quantities[$index] >= 0 ) {
            $cart_item['quantity'] = $quantities[$index];
          }
          $cart_item = array_merge( $cart_item_defaults, $cart_item );
          WC()->cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'] );
        }
      }

      WC()->cart->calculate_totals();

      if ( ! empty( $_GET[$this->param_coupons] ) ) {
        if ( $redirect_url ) {
          $redirect_url = remove_query_arg( $this->param_coupons, $redirect_url );
        }
        add_filter( 'woocommerce_coupon_message', '__return_empty_string' );
        remove_action( 'woocommerce_applied_coupon', array( WC()->cart, 'calculate_totals' ), 20, 0 );
        if ( is_array( $_GET[$this->param_coupons] ) ) {
          $coupons = $_GET[$this->param_coupons];
        } else {
          $coupons = $this->get_list_separated_by( ',', $_GET[$this->param_coupons] );
        }
        foreach ( $coupons as $coupon ) {
          WC()->cart->add_discount( $coupon );
        }
        remove_filter( 'woocommerce_coupon_message', '__return_empty_string' );
        add_action( 'woocommerce_applied_coupon', array( WC()->cart, 'calculate_totals' ), 20, 0 );
        WC()->cart->calculate_totals();
      }

      // Add shipping
      if ( WC()->cart->needs_shipping() ) {
        if ( ! empty( $_GET[$this->param_shipping] ) ) {
          if ( $redirect_url ) {
            $redirect_url = remove_query_arg( $this->param_shipping, $redirect_url );
          }
          $chosen_shipping_methods = array();
          foreach ( WC()->shipping->get_packages() as $i => $package ) {
            $chosen_shipping_methods[ $i ] = $_GET[$this->param_shipping];
          }
          WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
        } else if ( ! in_array( WC()->customer->get_shipping_country(), array_keys( WC()->countries->get_shipping_countries() ) ) || $this->shipping_available() ) {
          wc_add_notice( __( 'No shipping method available', 'error', 'woocommerce-one-click-checkout-by-getmogul' ) );
          return;
        }
      }

      WC()->cart->calculate_totals();

      $order->set_shipping_total( WC()->cart->shipping_total );
      $order->set_discount_total( WC()->cart->get_cart_discount_total() );
      $order->set_discount_tax( WC()->cart->get_cart_discount_tax_total() );
      $order->set_cart_tax( WC()->cart->tax_total );
      $order->set_shipping_tax( WC()->cart->shipping_tax_total );
      $order->set_total( WC()->cart->total );

      WC()->checkout->create_order_line_items( $order, WC()->cart );
      WC()->checkout->create_order_fee_lines( $order, WC()->cart );

      WC()->checkout->create_order_shipping_lines( $order, WC()->session->get( 'chosen_shipping_methods' ), WC()->shipping->get_packages() );
      WC()->checkout->create_order_tax_lines( $order, WC()->cart );
      WC()->checkout->create_order_coupon_lines( $order, WC()->cart );

      $payment_gateway = null;

      if ( ! $order->needs_payment() ) {
        $order->payment_complete();
      } else {
        if ( ! empty( $last_order ) ) {
          $order->set_payment_method( $payment_method );
          $order->set_payment_method_title( $last_order->get_payment_method_title() );
          $payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
          if ( ! empty( $payment_gateways[$payment_method] ) ) {
            $payment_gateway = $payment_gateways[$payment_method];
          }
        }
      }

      $order_id = $order->save();

      do_action( 'woocommerce_new_order', $order_id );

      if ( $payment_gateway ) {
        WC()->session->set( 'order_awaiting_payment', $order_id );

        if ( $payment_method == 'stripe' && defined( 'WC_STRIPE_VERSION' ) && version_compare( WC_STRIPE_VERSION, '4.0', '>=' ) ) {
          $source = $payment_gateway->prepare_order_source( $last_order );
          $_POST['stripe_source'] = $source->source;
        }
        $result = $payment_gateway->process_payment( $order_id );
      } else if ( $order->needs_payment() ) {
        $message = sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', esc_url( $order->get_checkout_payment_url() ), esc_html__( 'View order', 'woocommerce-one-click-checkout-by-getmogul' ), esc_html__( 'Your order has been received and it is now waiting for payment', 'woocommerce-one-click-checkout-by-getmogul' ) );
        wc_add_notice( __( $message, 'notice', 'woocommerce-one-click-checkout-by-getmogul' ) );
      }

      WC()->cart->empty_cart();

      if ( ! $current_cart || $current_cart == 'restore' ) {
        WC()->session->set( 'cart', $saved_cart );
      }

      if ( $redirect_url ) {
        wp_redirect( $redirect_url );
      } else {
        wp_redirect( $order->get_checkout_order_received_url() );
      }
			exit;
    }
  }

  private function get_shipping_address( $shipping_type ){
      
       $fields = array();
            
            $this->_user_id = get_current_user_id();
            // standard types
            //$types = array('billing', 'shipping');
            if (empty($shipping_type) || $shipping_type == '') {
                $shipping_type = 'shipping';
            }

            if ($shipping_type == 'billing' || $shipping_type == 'shipping') {

                $types = array($shipping_type);

                $address = array();

                foreach ($types as $type) {

                    $fields = array(
                        'first_name' => get_user_meta($this->_user_id, $type . '_first_name', true),
                        'last_name' => get_user_meta($this->_user_id, $type . '_last_name', true),
                        'company' => get_user_meta($this->_user_id, $type . '_company', true),
                        'address_1' => get_user_meta($this->_user_id, $type . '_address_1', true),
                        'address_2' => get_user_meta($this->_user_id, $type . '_address_2', true),
                        'city' => get_user_meta($this->_user_id, $type . '_city', true),
                        'state' => get_user_meta($this->_user_id, $type . '_state', true),
                        'postcode' => get_user_meta($this->_user_id, $type . '_postcode', true),
                        'country' => get_user_meta($this->_user_id, $type . '_country', true)
                    );

                    return $fields;
                }
            } else {

                $custom_address_arr = maybe_unserialize(get_user_meta($this->_user_id, 'yith-wocc-user-custom-address', true));
                if ((count($custom_address_arr) > 0) && (array_key_exists($shipping_type, $custom_address_arr))) {
                    
                        $fields = array(
                            'first_name' => $custom_address_arr[$shipping_type]['first_name'],
                            'last_name' => $custom_address_arr[$shipping_type]['last_name'],
                            'company' => $custom_address_arr[$shipping_type]['company'],
                            'address_1' => $custom_address_arr[$shipping_type]['address_1'],
                            'address_2' => $custom_address_arr[$shipping_type]['address_2'],
                            'city' => $custom_address_arr[$shipping_type]['city'],
                            'state' => $custom_address_arr[$shipping_type]['state'],
                            'postcode' => $custom_address_arr[$shipping_type]['postcode'],
                            'country' => $custom_address_arr[$shipping_type]['country']
                        );
                        
                }
            }
            //print_r($fields);
            return $fields;
  }
  
  /**
	 * Get customer property from order or user.
	 *
	 * @param string Type
	 * @param int Property
	 * @param WC_Order Order
	 * @param WP_User User
	 */
	private function get_customer_property( $type, $property, $order = null, $user = null ) {
		$value = null;
		if ( ! empty( $order ) ) {
			if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
				$value = $order->{'get_' . $type . '_' . $property}();
			} else {
				$value = $order->{$type . '_' . $property};
			}
    }
    if ( empty( $value ) && ! empty( $user ) ) {
			$value = $user->{$type . '_' . $property};
		}
		return $value;
	}

  /**
	 * Get current URL.
	 *
	 * @return string Current URL
	 */
	private function get_current_url() {
		if ( is_multisite() ) {
			$parts = parse_url( home_url() );
			// WordPress core does not support port, user or password in a multisite site URL so this should be sufficient.
			$current_uri = "{$parts['scheme']}://{$parts['host']}" . add_query_arg( NULL, NULL );
		} else {
			$current_url = home_url( add_query_arg( NULL, NULL ) );
		}
		return $current_url;
	}

  /**
    * Check if shipping is available
    *
    * @return boolean Shipping is available
    */
  public function shipping_available(){
    $packages = WC()->cart->get_shipping_packages();
    $available = false;
    foreach( $packages as $package ) {
      $shipping_zone = wc_get_shipping_zone( $package );
      $shipping_methods = $shipping_zone->get_shipping_methods( true );
      if( ! empty( $shipping_methods ) ) {
        continue;
      }
      $available = true;
      break;
    }

    return $available;
  }

  /**
   * Get all shipping zones and methods
   *
   * @return array Shipping zones and methods
   */
  public function get_shipping_zones_methods() {
    $result = array();
    $default_zone = new WC_Shipping_Zone(0);
    $result[0]['zone_name'] = $default_zone->get_zone_name();
    $result[0]['zone_id'] = 0;
    foreach( $default_zone->get_shipping_methods( true ) as $shipping_method ) {
      $method = array();
      $method['title'] = $shipping_method->get_method_title();
      $method['method_id'] = $shipping_method->id;
      $method['instance_id'] = $shipping_method->get_instance_id();
      $result[0]['shipping_methods'][$method['instance_id']] = $method;
    }
    foreach( WC_Shipping_Zones::get_zones() as $zone ) {
      $result[$zone['zone_id']]['zone_name'] = $zone['zone_name'];
      $result[$zone['zone_id']]['zone_id'] = $zone['zone_id'];
      foreach( $zone['shipping_methods'] as $shipping_method ) {
        $method = array();
        $method['title'] = $shipping_method->get_method_title();
        $method['method_id'] = $shipping_method->id;
        $method['instance_id'] = $shipping_method->get_instance_id();
        $result[$zone['zone_id']]['shipping_methods'][$method['instance_id']] = $method;
      }
    }
    return $result;
  }

  /**
   * Get all coupon codes
   *
   * @return array Coupon codes
   */
  public function get_coupon_codes() {
    $args = array(
      'posts_per_page'   => -1,
      'orderby'          => 'title',
      'order'            => 'asc',
      'post_type'        => 'shop_coupon',
      'post_status'      => 'publish',
    );

    $post_coupons = get_posts( $args );
    $codes = array();
    foreach( $post_coupons as $post_coupon ) {
      $codes[] = $post_coupon->post_title;
    }
    return $codes;
  }

  /**
	 * Get the trimmed elements from a list separated by a separator.
   *
   * @param string Separator
   * @param string Comma separated list
   * @param array List elements
	 */
  public function get_list_separated_by( $separator, $separated_list ) {
    $list = explode( $separator, $separated_list );
    $list = array_map( function ( $value ) {
      return trim( $value );
    }, $list );
    return $list;
  }

  /**
	 * Called when WooCommerce is inactive to display an inactive notice.
	 */
	public function woocommerce_inactive_notice() {
		if ( current_user_can( 'activate_plugins' ) ) {
			if ( ! defined( 'WC_VERSION' ) ) {
				?>
				<div id="message" class="error">
					<p><?php printf( __( '%s requires WooCommerce to be activated to work', 'woocommerce-one-click-checkout-by-getmogul' ), $this->name ); ?></p>
				</div>
				<?php
			} elseif ( version_compare( WC_VERSION, $this->wc_requires_at_least, '<' ) ) {
				?>
				<div id="message" class="error">
					<p><?php printf( __( '%s requires WooCommerce version %s or higher to work. You are running %s', 'woocommerce-one-click-checkout-by-getmogul' ), $this->name, $this->wc_requires_at_least, WC_VERSION ); ?></p>
				</div>
				<?php
			}
		}
	}

  /**
	 * Add Settings link to plugin action links.
	 *
	 * @param array Links
	 * @return array Links
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'woocommerce-one-click-checkout-by-getmogul' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}
}
endif;
WooCommerce_One_Click_Checkout_By_GetMogul::instance();