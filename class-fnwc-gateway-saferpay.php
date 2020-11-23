<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Helper functions
require_once("fn-saferpay.php");

/**
 * Safer Payment Gateway
 *
 * Provides a Safer Payment Gateway.
 *
 * @class 		FNWC_Saferpay_Gateway
 * @extends		WC_Payment_Gateway
 * @version		1.0.1
 * @package		-
 * @author 		Stefan Keller
 */

class FNWC_Saferpay_Gateway extends WC_Payment_Gateway {

	

	public function __construct() {

		global $woocommerce;
		

		// 1. Required settings
		// 	see http://docs.woothemes.com/document/payment-gateway-api/
		$this->id = 'saferpay'; 
		$this->classname = 'FNWC_Saferpay_Gateway';
		$this->icon = plugins_url( '/assets/images/credit_cards.png' , __FILE__ ) ;
		
		$this->method_title = 'Saferpay';
		$this->method_description = __('WooCommerce Payment Gateway for Saferpay', 'fnwc-woocommerce');
		$this->has_fields = false; 
		
		// The notify URL for the Saferpay response (currently no https)
		$this->notify_url   = home_url('/wc-api/fnwc_saferpay_gateway');
		$this->successlink  = home_url('/wc-api/fnwc_saferpay_gateway');
		
		
			
		// 2. Init the settings and the form fields		
		$this->init_form_fields();
		$this->init_settings();
		
										
		// 3. Get/Set the options
		$this->title = $this->get_option( 'title' );
		$this->saferpay_accountid =  $this->get_option( 'accountid' );
		$this->order_description =  $this->get_option( 'order_description' );
		$this->order_id_prefix = $this->get_option( 'order_id_prefix' );

		if ( $this->enabled && is_admin() ) {
			$this->install_saferpay_page();
		}
		
		// 4. Add the actions
		if (is_admin())
		{
			 // Update options
			 add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		
		// 
		/* 
		* Note: WooCommerce Callback API
		* We are now using the notify URL -> we can use the WC-API
		*/
		add_action('woocommerce_api_' . 'fnwc_saferpay_gateway', array($this, 'saferpay_notify'));  /* Sanitized lower key classname */
		


	 }


	 
	/**
	 * install_saferpay_page function.
	 *
	 * @access public
	 */
	public function install_saferpay_page() {
		
		$saferpay_page_id = get_option( 'saferpay_page_id', 0);

		if ($saferpay_page_id == 0) {
			
			$pid = woocommerce_get_page_id('checkout');
				
			$page = array(
				'post_title' 		=> 'Saferpay',
				'post_name' 		=> 'saferpay',
				'post_parent' 		=> $pid,
				'post_status' 		=> 'publish',
				'post_type' 		=> 'page',
				'comment_status' 	=> 'closed',
				'ping_status' 		=> 'closed',
				'post_content' 		=> "[saferpay-checkout]",
			);
						
			$id = wp_insert_post( $page );
			update_option( 'saferpay_page_id', $id);
			
			
		}
	}
	 
	/**
	 * Get the currently activated payment methods
	 *
	 * @access public
	 * @return void
	 */	 
	 function get_payment_methods()
	 {
		 $result = "";
		 $delimiter = "";
		 
		 if($this->get_option( 'payment_method_mastercard' ) == 'yes')
		 {
			 $result .=  $delimiter . "1" ;
			 $delimiter = ",";
		 }
		 
		 if($this->get_option( 'payment_method_visa' ) == 'yes')
		 {
			 $result .=  $delimiter . "2" ;
			 $delimiter = ",";
		 }
		 
		 
		 if($this->get_option( 'payment_method_american_express' ) == 'yes')
		 {
			 $result .=  $delimiter . "3" ;
			 $delimiter = ",";
		 }
		 
		 if($this->get_option( 'payment_method_sofort_ueberweisung' ) == 'yes')
		 {
			 $result .=  $delimiter . "15" ;
			 $delimiter = ",";
		 }
		 

		 return $result;
	 }
	 
	/**
	 * Set the admin options panel
	 *
	 * @access public
	 * @return void
	 */
  	public function admin_options() {
		
		echo '<h3>' . $this->method_title . '</h3>';
        echo '<p>' . $this->method_description . '</p>';
		
		// Display additional information here...
		
		echo '<table class="form-table">';
			$this->generate_settings_html();
		echo '</table>';
        
	}
	
	/**
	 * Initialise Gateway Settings Form Fields
	 *
	 * @access public
	 * @return void
	 */
	function init_form_fields() {
	
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable/Disable', 'fnwc-woocommerce' ),
				'type' => 'checkbox',
				'label' => __( 'Enable Saferpay Payment', 'fnwc-woocommerce' ),
				'default' => 'yes'
			),
			'accountid' => array(
				'title' => __( 'Saferpay Account ID', 'fnwc-woocommerce' ),
				'type' => 'text',
				'description' => __( 'Your Saferpay account ID. The ID of the Saferpay Test Account is 99867-94913159.', 'fnwc-woocommerce' ),
				'default' => __( '99867-94913159', 'fnwc-woocommerce' ),
				'desc_tip'      => true,
			),
			'title' => array(
				'title' => __( 'Title', 'fnwc-woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'fnwc-woocommerce' ),
				'default' => __( 'Saferpay Payment', 'fnwc-woocommerce' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Customer Message', 'fnwc-woocommerce' ),
				'type' => 'textarea',
				'default' => ''
			),
			'order_id_prefix' => array(
				'title' => __( 'Invoice prefix', 'fnwc-woocommerce' ),
				'type' => 'text',
				'description' => __( 'Please enter a prefix for your invoice numbers. If you use your Saferpay account for multiple stores ensure this prefix is unqiue to identify the order correctly.', 'fnwc-woocommerce' ),
				'default' => __( 'WC-', 'fnwc-woocommerce' ),
				'desc_tip'      => true,
			),
			'order_description' => array(
				'title' => __( 'Invoice description', 'fnwc-woocommerce' ),
				'type' => 'text',
				'description' => __( 'This is the description which the user sees during the Saferpay payment process. Use the placeholder #_ORDERID to get the Id of the current order.', 'fnwc-woocommerce' ),
				'default' => __( 'Your order # #_ORDERID', 'fnwc-woocommerce' ),
				'desc_tip'      => true,
			),

			/*  Payment methods */
			'payment_methods' => array(
				'title' => __( 'Payment methods', 'fnwc-woocommerce' ),
				'type' => 'title',
				'description' => '',
			),			
			'payment_method_mastercard' => array(
				'title' => __( 'Enable/Disable', 'fnwc-woocommerce' ),
				'type' => 'checkbox',
				'label' => __('MasterCard', 'fnwc-woocommerce' ),
				'default' => 'yes'
			),
			'payment_method_visa' => array(
				'title' => __( 'Enable/Disable', 'fnwc-woocommerce' ),
				'type' => 'checkbox',
				'label' => __('Visa', 'fnwc-woocommerce' ),
				'default' => 'yes'
			),			
			'payment_method_american_express' => array(
				'title' => __( 'Enable/Disable', 'fnwc-woocommerce' ),
				'type' => 'checkbox',
				'label' => __('American Express', 'fnwc-woocommerce' ),
				'default' => 'yes'
			),
			'payment_method_sofort_ueberweisung' => array(
				'title' => __( 'Enable/Disable', 'fnwc-woocommerce' ),
				'type' => 'checkbox',
				'label' => __('Direct payment', 'fnwc-woocommerce' ),
				'default' => 'no'
			),				
		);
		
	}

	/**
	 * Process the payment on checkout
	 *
	 * @access public
	 * @return void
	 */	
	function process_payment( $order_id ) {
		
		global $woocommerce;
		
		// Get the order
        $order = wc_get_order($order_id);
		$status = 'success';
		
		/************************************************************
		* It seems that we don't have to take care of clearing the cart
		* or update the state of the order.
		*
		* see: WC_Gateway_Paypal in the WooCommerce /classes/gateways/paypal
		* folder. The work is done in the return functionality
		*/
		
		
		/************************************************************
		* What are we doing here? 
		* - Build the url to get the payment URL from Saferpay
		* - We check the result for validity
		* - and redirect the user to the payment URL
		*
		*/
		
		// Get the URL of the checkout page (in case the user wants to return)
        $checkouturl = wc_get_checkout_url();
		$faillink = home_url('/wc-api/fnwc_saferpay_gateway');
		
		// Create the so called Init Pay URL for Saferpay
		$createpayiniturl = fn_get_createpayiniturl($this, $order,  $checkouturl, $faillink);
		$payurl = "";
		
		try
		{
			// The Init Pay URL returns the Pay URL
			$payurl = fn_get_urlcontent($createpayiniturl);
		}
		catch(Exception $e)
		{
			$msg = __('Could not connect to the payment provider', 'fnwc-woocommerce' );
			
			// This is serious, we inform the administrator
			$this->alert_admin($msg . "\r\nOrder ID: ". $order->id);
			
			// Add an error and set the status
			$woocommerce->add_error($msg);
			$status = 'failed';
		}
		
		// Simple verification of the Pay URL
		if (stripos($payurl, "ERROR") !== false) {
			
			// This is serious, we inform the administrator
			$this->alert_admin($payurl . "\r\nOrder ID: ". $order->id);
			
			// Add an error and set the status
			$woocommerce->add_error($payurl);
			$status = 'failed';
			
			// Return redirect
			return array(
				'result' => $status,
				'redirect' => ''
			);
			
		}
		
		
		
		// Return redirect
		return array(
			'result' => $status,
			'redirect' => $payurl
		);
	
	}


	/**
	 * Gets the notification from Saferpay and processes the result
	 *
	 * @access public
	 * @return void
	 */	 
	 function saferpay_notify()
	 {
		// If 
		if (!empty($_GET))
		{
			$url = fn_pay_confirm_complete($this);
			wp_redirect($url); 
			exit;
		}
		 
		$response = fn_pay_validate_and_complete($this, $_POST);
		
		if($response != "OK")
		{
			$gateway->alert_admin($response);	
		}
		
	 }

	/**
	 * Alert the administrator
	 *
	 * @access public
	 * @return void
	 */	 	 
	function alert_admin($message)
	{
		$to = get_option('admin_email');
		
		$subject = 'Alert: WooCommerce Payment Gateway for Saferpay';
		wp_mail($to, $subject, $message );
	}
	
				
}














?>