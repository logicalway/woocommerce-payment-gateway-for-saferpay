<?php

/*****************************************************************
* Preflight check - helper methods
*/ 
function fnwc_gateway_saferpay_error()
{
	$title =  __( "The WooCommerce Payment Gateway for Saferpay is activated but your system does not meet the requirements:", 'fnwc-woocommerce' );
	
	return '<div id="message" class="error"><br/>' . $title . '<br/><ul><li><strong>%s</strong></li></ul><br/></div>';
	 
}

// curl extension not loaded
function fnwc_gateway_saferpay_error_curl()
{
	 echo sprintf(fnwc_gateway_saferpay_error(),  __( "PHP extention curl is missing", 'fnwc-woocommerce' ));
}

// WooCommerce not installed
function fnwc_gateway_saferpay_error_wc()
{
	 echo sprintf(fnwc_gateway_saferpay_error(),  __(  "WooCommerce is not installed", 'fnwc-woocommerce' ));
}

// WooCommerce version not correct
function fnwc_gateway_saferpay_error_wc_version()
{
	 echo sprintf(fnwc_gateway_saferpay_error(),  __(  "WooCommerce version 2.0 or higher is not installed", 'fnwc-woocommerce' ));
}	
	


?>