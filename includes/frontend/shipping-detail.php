<?php

/**
 * Add shipping dropdown
 * @param type $product
 * @return type
 */
Class WooCommerce_One_Click_Checkout_Shipping extends WooCommerce_One_Click_Checkout_By_GetMogul {

    public $_user_id;

    public function __construct() {
        
        $this->add_shipping_details();
        $this->_user_id = get_current_user_id();
    }

    function add_shipping_details() {
        global $product;
        if (is_null($product) || !$product || !$product->needs_shipping()) {
            return;
        }
        return $this->shipping_address_select_html();
    }

    /**
     * 
     */
    function shipping_address_select_html($product = null) {

        $address = $this->get_formatted_address();
        $html = '';
        // set default from option
        is_null($selected) && $selected = get_option('yith-wocc-default-shipping-addr', '');

        if (!empty($address)) {
            ob_start();
            ?>
            <div class="one-click-select-address-container">
                <br>
                <p for="_yith_wocc_select_address">
                    <br>
                    <?php _e('Ship To: ', 'yith-woocommerce-one-click-checkout'); ?></label>
                    <select class="one-click-select-address" name="_one_click_select_address">
                        <option value=""></option>
                        <?php foreach ($address as $key => $value) : ?>
                            <option value="<?php echo $key ?>" <?php selected($selected, $key) ?>><?php echo $value ?></option>
                        <?php endforeach; ?>
                        <?php if (!wp_is_mobile()) : ?>
                            <option value="add-new"><?php echo __('Add new shipping address', 'yith-woocommerce-one-click-checkout') ?></option>
                        <?php endif; ?>
                    </select>
                
            </div>
            <?php
            $html = ob_get_clean();
        }
        return $html;
        //return apply_filters('yith_wocc_address_select_html', $html, $address);
    }

    /**
     * Get billing and shipping info address
     *
     * @access public
     * @since 1.0.0
     * @return array
     * @author Francesco Licandro
     */
    public function get_formatted_address() {

        $this->_user_id = get_current_user_id();
        // standard types
        $types = array('billing', 'shipping');
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

            // remove empty
            $fields = array_filter($fields);

            if (!empty($fields)) {
                $formatted = WC()->countries->get_formatted_address($fields);
                $address[$type] = esc_html(preg_replace('#<br\s*/?>#i', ', ', $formatted));
            }
        }

        // add custom also
        $custom = $this->get_formatted_custom_address();
        if (!empty($custom))
            $address = array_merge($address, $custom);

        return apply_filters('yith_wocc_get_formatted_address', $address);
    }

    /**
     * Get custom address info
     *
     * @access public
     * @since 1.0.0
     * @param bool $inline
     * @return array
     * @author Francesco Licandro
     */
    public function get_formatted_custom_address($inline = true) {

        // custom types
        $custom_address = maybe_unserialize( get_user_meta( $this->_user_id, 'yith-wocc-user-custom-address', true ));
        $address = array();

        if (!$custom_address) {
            return $address;
        }


        foreach ($custom_address as $key => $value) {

            // remove empty
            $fields = array_filter($value);

            if (!empty($fields)) {
                $formatted = WC()->countries->get_formatted_address($fields);
                $address[$key] = $inline ? esc_html(preg_replace('#<br\s*/?>#i', ', ', $formatted)) : $formatted;
            }
        }

        return $address;
    }

    /**
     * Get add/edit form address html
     *
     * @since 1.0.0
     * @param boolean|array $address
     * @param string $action
     * @return mixed
     * @author Francesco Licandro
     */
    public function get_address_form_html() {
        
        if (!$address) {

            $address = WC()->countries->get_address_fields('', 'shipping_');

            foreach ($address as $key => $field) {

                switch ($key) {
                    case 'shipping_country' :
                        $value = WC()->countries->get_base_country();
                        break;
                    case 'shipping_state' :
                        $value = WC()->countries->get_base_state();
                        break;
                    default :
                        $value = '';
                        break;
                }

                $address[$key]['value'] = $value;
            }
        }

        // set template args
        $args = array(
            'address' => $address,
            'action_form' => $this->_action_edit_address,
            'action' => $action
        );

        $this->print_address_html_form($args);
    }

    public function print_address_html_form( $args ) {  

        if ($args['action'] == '') {
            $args['action'] = 'add';
        }
        ?>
        <div id="one-click-modal-overlay"></div>
        <div id="one-click-modal">
            <div class="one-click-modal-content">
                <a href="#" class="one-click-wacp-close fa fa-close"></a>
                <div class="woocommerce">
                    <form method="post">
                         <?php
                        $user_info = wp_get_current_user();
                        $title = ( $args['action'] == 'add' ) ? __('Add address', 'yith-woocommerce-one-click-checkout') : __('Edit address', 'yith-woocommerce-one-click-checkout');
                        echo '<h3>' . $title . '</h3>';

                        foreach ($args['address'] as $key => $field) :
                            if($field['label'] == 'First name'){
                                 woocommerce_form_field($key, $field, !empty($_POST[$key]) ? wc_clean($_POST[$key]) : $user_info->first_name  );
                            } else if ( $field['label'] == 'Last name' ) {
                                 woocommerce_form_field($key, $field, !empty($_POST[$key]) ? wc_clean($_POST[$key]) :  $user_info->last_name );
                            } else {
                                  woocommerce_form_field($key, $field, !empty($_POST[$key]) ? wc_clean($_POST[$key]) : $field['value'] );
                            }
                           
                        endforeach;
                        ?>
                        <p>
                            <?php if (isset($_GET['edit'])) : ?>
                                <input type="hidden" name="address_edit" value="<?php echo $_GET['edit'] ?>">
                            <?php endif; ?>
                            <input type="submit" class="button" name="save_address" value="<?php echo esc_attr(__('Save Address', 'yith-woocommerce-one-click-checkout')); ?>" />
                            <?php wp_nonce_field($action_form); ?>
                            <input type="hidden" name="_action_form" value="<?php echo $action_form ?>"/>
                        </p>
                </div>
            </div>
        </div>
        </form>
        <?php
    }

    public function add_custom_shipping_address(  ) {

        $result = $this->save_shipping_address();
        $html = false;
        //print_r($result);
        // get result html
        ob_start();

        // if same errors occurred get notice
        if (!$result) {
            wc_print_notices();
        } else {
            echo $this->shipping_address_select_html($result);
        }

        $html = ob_get_clean();

        wp_send_json(array(
            'error' => !$result,
            'key' => $result,
            'html' => $html
        ));

    }
    
    public function save_shipping_address(){
        
            $ajax = true;

            $this->_user_id = get_current_user_id();
            
            $address = WC()->countries->get_address_fields(esc_attr($_REQUEST['shipping_country']), 'shipping_');
            $to_save = array();
            
            foreach ($address as $key => $field) {

                if (!isset($field['type'])) {
                    $field['type'] = 'text';
                }

                // Get Value
                switch ($field['type']) {
                    case "checkbox" :
                        $_REQUEST[$key] = isset($_REQUEST[$key]) ? 1 : 0;
                        break;
                    default :
                        $_REQUEST[$key] = isset($_REQUEST[$key]) ? wc_clean($_REQUEST[$key]) : '';
                        break;
                }

                // Hook to allow modification of value
                $_REQUEST[$key] = apply_filters('woocommerce_process_myaccount_field_' . $key, $_REQUEST[$key]);

                // Validation: Required fields
                if (!empty($field['required']) && empty($_REQUEST[$key])) {
                    wc_add_notice($field['label'] . ' ' . __('is a required field.', 'yith-woocommerce-one-click-checkout'), 'error');
                }

                if (!empty($_POST[$key])) {

                    // Validation rules
                    if (!empty($field['validate']) && is_array($field['validate'])) {
                        foreach ($field['validate'] as $rule) {
                            switch ($rule) {
                                case 'postcode' :
                                    $_REQUEST[$key] = strtoupper(str_replace(' ', '', $_REQUEST[$key]));

                                    if (!WC_Validation::is_postcode($_REQUEST[$key], $_REQUEST['shipping_country'])) {
                                        wc_add_notice(__('Please enter a valid postcode/ZIP.', 'yith-woocommerce-one-click-checkout'), 'error');
                                    } else {
                                        $_REQUEST[$key] = wc_format_postcode($_REQUEST[$key], $_REQUEST['shipping_country']);
                                    }
                                    break;
                            }
                        }
                    }
                }

                // populate save array address
                $my_key = str_replace('shipping_', '', $key);
                $to_save[$my_key] = $_REQUEST[$key];
            }
            
            if (wc_notice_count('error') == 0) {

                $saved_address = maybe_unserialize( get_user_meta( $this->_user_id, 'yith-wocc-user-custom-address', true ) );
                $key = null;

                if (is_array($saved_address)) {
                    end($saved_address);         // move the internal pointer to the end of the array
                    $key = key($saved_address);
                } else {
                    $saved_address = array();
                }
                $key = !is_null($key) ? intval(str_replace('custom_', '', $key)) : 0;

                // check if is edit
                if (isset($_REQUEST['address_edit'])) {
                    $edited = $_REQUEST['address_edit'];
                    $saved_address[$edited] = $to_save;
                } else {
                    // add new address
                    $saved_address['custom_' . ++$key] = $to_save;
                }
                
                
                if (update_user_meta( $this->_user_id, 'yith-wocc-user-custom-address', $saved_address) && $ajax) {
                    return array_search($to_save, $saved_address);
                }


                // else add message and redirect
                $message = isset($_REQUEST['address_edit']) ? __('Address changed successfully.', 'yith-woocommerce-one-click-checkout') : __('Address added successfully.', 'yith-woocommerce-one-click-checkout');

                wc_add_notice($message, 'success');

                $redirect_url = wc_get_endpoint_url('one-click');
                wp_safe_redirect($redirect_url);
                exit;
            }

            return false;
    }

}
?>