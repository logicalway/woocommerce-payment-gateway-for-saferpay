<?php
/*
	Plugin Name: WooCommerce Payment Gateway for Saferpay
	Plugin URI: http://www.fern.ch/wordpress-plugins/woocommerce-saferpay
	Description: Adds a Saferpay payment method to your WooCommerce installation.
	Author: Stefan Keller (FERN media solutions GmbH)
	Author URI: http://www.fern.ch/team/

	Version: 0.4.4

	License: GNU General Public License v2.0 (or later)
	License URI: http://www.opensource.org/licenses/gpl-license.php
*/




/*  Copyright 2013  FERN media solutions GmbH (email : http://http://www.fern.ch/kontakt)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define('FNWC_GATEWAY_SAFERPAY_DIR', dirname( __FILE__ ));


function fnwc_gateway_saferpay_plugins_loaded() {
	
	global $woocommerce;
	
	/* Load plugin language files */
	load_plugin_textdomain( 'fnwc-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'  ); 
	
	
	/*****************************************************************
	* Preflight check
	*****************************************************************/ 
	
	// Load utility
	require_once("fn-utilities.php");
	
	// Is curl installed?
	if (!function_exists('curl_version'))
	{
		add_action('admin_notices', 'fnwc_gateway_saferpay_error_curl'); 
        return;
	}
	// Is WooCommerce installed and the Gateway class present?
	if (!class_exists('WC_Payment_Gateway'))
	{
		add_action('admin_notices', 'fnwc_gateway_saferpay_error_wc'); 
        return;
	}
	
	if(!version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ))
	{
		add_action('admin_notices', 'fnwc_gateway_saferpay_error_wc_version'); 
        return;		
	}

	/*****************************************************************
	* Take off
	*****************************************************************/

	// Load the class
	require_once("class-fnwc-gateway-saferpay.php");

	// Add Saferpay as WooCommerce gateway (WooCommerce takes care of the rest)
	add_filter('woocommerce_payment_gateways', 'fnwc_gateway_saferpay_addme');

	// Short codes
	add_shortcode('saferpay-checkout', 'sc_saferpay_checkout');

		
}

 function sc_saferpay_checkout()
 {
	$result = "";
	
	if (!empty($_GET))
	{
		if($_GET['o'])
			$result .= "<p>". fn_log_for_order($_GET['o']) . "</p>"; 
			
	}
	
	return $result; 
 }
	 
/**
* Add our Payment class to the woocommerce gateway
*
* @return array of methods
*/ 
function fnwc_gateway_saferpay_addme( $methods ) {
	
	$methods[] = 'FNWC_Saferpay_Gateway'; /* Class name */
	return $methods;
	
}


/**
* Setup and Init:
*
* Launch the Saferpay Gateway when active plugins and pluggable functions are loaded
*/
add_action('plugins_loaded', 'fnwc_gateway_saferpay_plugins_loaded'); // Fires before init


/* ********************************************************* */
// Activation/Deactivation
register_activation_hook(__FILE__, 'fnwc_gateway_activation');
register_deactivation_hook(__FILE__, 'fnwc_gateway_deactivation');

function fnwc_gateway_activation()
{
	update_option( 'saferpay_page_id', 0);		
}

function fnwc_gateway_deactivation()
{
	$pid = get_option( 'saferpay_page_id', 0);
	
	if($pid  > 0)
	{
		@wp_delete_post($pid, true);
	}
	
	delete_option('saferpay_page_id');	
}

?>