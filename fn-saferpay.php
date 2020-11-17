<?php

/* Saferpay URLs */
define('FN_SAFERPAY_CREATEINITURL', 'https://www.saferpay.com/hosting/createpayinit.asp');
define('FN_SAFERPAY_VERIFYPAYCONFIRM', 'https://www.saferpay.com/hosting/verifypayconfirm.asp');
define('FN_SAFERPAY_PAYCOMPLETEV2', 'https://www.saferpay.com/hosting/paycompletev2.asp');

/* Saferpay test account */
define('FN_SAFERPAY_TESTACCOUNT_ID', '99867-94913159');
define('FN_SAFERPAY_TESTACCOUNT_UN', 'e99867001');
define('FN_SAFERPAY_TESTACCOUNT_PW', 'XAjc3Kna');


/***********************************************************************************
* Saferpay HTTPS Interface
***********************************************************************************/ 
	
/**
 * Get the Payment URL from Saferpay (init)
 *
 * @return string (URL)
 */		
function fn_get_createpayiniturl($gateway, $order, $backlink, $faillink)
{
	
	$description = str_replace("#_ORDERID", $order->id, $gateway->order_description);
	$orderid = $gateway->order_id_prefix . $order->id;
		
	$variables = array (
	
		
		 'ACCOUNTID' => $gateway->saferpay_accountid,
		 'AMOUNT' => round($order->get_total(),2) * 100, // Saferpay wants to have cent-values
		 'CURRENCY' => get_woocommerce_currency(),
		 'DESCRIPTION' => urlencode($description),
		 'ORDERID' => $orderid,
		 /* 'VTCONFIG' => '', */		 
		 'SUCCESSLINK' => $gateway->successlink,
		 'FAILLINK' => $faillink,  
		 'BACKLINK' => $backlink,
		 'NOTIFYURL' => $gateway->notify_url,
		 /* 'AUTOCLOSE' => '0', */ // We do not autoclose at the moment
		 'CCNAME' => 'yes',
		 'DELIVERY' => 'no',
		 'SHOWLANGUAGES' => 'yes',
		 'PAYMENTMETHODS' => $gateway->get_payment_methods(),		 
		 /* 'USERNOTIFY' => trim($gateway->receiver_email) */
		 
	);	
	
	return add_query_arg($variables, FN_SAFERPAY_CREATEINITURL);
}



/**
 * Verify Saferpay payment 
 *
 * @return string - returns the content of the response from Saferpay
 */	
function fn_verify_saferpay_payment($gateway, $input)
{
	
	$variables = array (
	
		 'ACCOUNTID' => $gateway->get_option('accountid'),
		 'SIGNATURE' => $input['SIGNATURE'],
		 'DATA' => urlencode($input['DATA'])
	);	
	

	// Build the URL for the verification process
	$url = add_query_arg($variables, FN_SAFERPAY_VERIFYPAYCONFIRM);
	
	// Return the content of the response
	return fn_get_urlcontent($url);
	
}



/**
 * Complete Saferpay payment 
 *
 * @return string - returns the content of the response from Saferpay
 */	
function fn_complete_saferpay_payment($gateway, $id)
{
	// 
	$account = $gateway->get_option('accountid');
	
	$variables = array (
		 'ACCOUNTID' => $account,
		 'ID' => $id
	);	
	
	// Add the password for the test account
	if($account == FN_SAFERPAY_TESTACCOUNT_ID)
		$variables['spPassword'] = FN_SAFERPAY_TESTACCOUNT_PW;

	// Build the URL for the verification process
	$url = add_query_arg($variables, FN_SAFERPAY_PAYCOMPLETEV2);
	
	// Return the content of the response
	return fn_get_urlcontent($url);
		
}

/***********************************************************************************
* WooCommerce functions
***********************************************************************************/ 

/**
 * Confirm and Verify the Saferpay payment 
 * this methos is called by Saferpay using the notify_url of the gateway. The process
 * runs in the background and we can't inform the user directly if an error occurs.
 *
 * @return string - returns the state of the payment
 */		
function fn_pay_validate_and_complete($gateway, $posted)
{
	global $woocommerce;
	
	if (!empty($posted) && fn_pay_confirm_is_valid($gateway, $posted)) 
	{
		
		// At this point we know that the payment is reserved and valid
		$data = stripcslashes($posted['DATA']);
		$xml = simplexml_load_string($data);
		
		if($xml)
		{
			$id = fn_simplexml_attribute($xml, 'ID');
			$amount = fn_simplexml_attribute($xml, 'AMOUNT');
			
			// Get the order from the server
			$orderid_with_prefix = fn_simplexml_attribute($xml, 'ORDERID');
			
			// Get the order id without prefix
			$orderid = str_replace($gateway->order_id_prefix, '', $orderid_with_prefix);
			
			$gateway->alert_admin($orderid .  " -- " . $gateway->saferpay_accountid);
			
			$order = new WC_Order($orderid);
			
			// Validate Status
			if($order->status == 'completed' || $order->status == 'processing')
			{
				// Add a note 
				$order->add_order_note( __( 'Aborting, order already processing or completed', 'fnwc-woocommerce' ) );
				
				return "ERROR: " . __('Order already processing or completed', 'fnwc-woocommerce');
			}
			
			// Validate Amount
			$_amount = round($order->get_total(),2) * 100;
			
			if($_amount != $amount)
			{
				// Update the post meta
				update_post_meta($order->id, 'Payment alert', __('Payment amount mismatch', 'fnwc-woocommerce'));
				
				return "ERROR: " . __('Payment amount mismatch', 'fnwc-woocommerce');
			}
			
			// Update Order data
			update_post_meta($order->id, 'Payment type', "Saferpay");
			update_post_meta($order->id, 'Transaction ID', $id);
			
			/*
			* Complete the payment
			*/
			$completion = fn_complete_saferpay_payment($gateway, $id);
			// Check completion
			
			
			
			
			$order->add_order_note( __( 'Saferpay payment completed', 'fnwc-woocommerce' ) );
			$order->payment_complete(); /* Smart method - recognizes download vs delivery */
			
			// Empty cart (silently and gracefully)
			@$woocommerce->cart->empty_cart();
						
			return "OK";

		}
		
		return "ERROR: " . __('Payment not completed', 'fnwc-woocommerce');
	}
	else
	{

		return "ERROR: " . __('Payment could not be validated', 'fnwc-woocommerce');
	}

}



/**
 * Validate Saferpay payment 
 *
 * @return boolean
 */	
function fn_pay_confirm_is_valid($gateway, $posted)
{
	$verfication = "";
	$status = true;
	
	try
	{
		// Ask Saferpay to confirm the payment
		$verfication = fn_verify_saferpay_payment($gateway, $posted);
	}
	catch(Exception $e)
	{
		$msg = __('Could not connect to the payment provider', 'fnwc-woocommerce' );
			
		// This is serious, we inform the administrator
		$gateway->alert_admin($msg . "\r\nOrder ID: ". $order->id);
		$status = false;	
	}
	
	// Saferpay does not confirm the payment and returns an error
	if ($status && stripos($verfication, "ERROR") !== false) {
		
		// This is serious, we inform the administrator
		$gateway->alert_admin($verfication);
		return false;	
	}

	return $status;	
}



/**
 * Check if order is complete after payment (notify url), used by the successlink 
 *
 * @return string - returns the url the redirect page
 */	
function fn_pay_confirm_complete($gateway)
{
	
	global $woocommerce;
	
	// Check if order is payed for and display the thank you page.
	if (!empty($_GET))
	{
		parse_str($_SERVER['QUERY_STRING'], $output);
		
		$xml = simplexml_load_string($output['DATA']);
		
		if($xml)
		{
			$id = fn_simplexml_attribute($xml, 'ID');
			$amount = fn_simplexml_attribute($xml, 'AMOUNT');
			
						// Get the order from the server
			$orderid_with_prefix = fn_simplexml_attribute($xml, 'ORDERID');
			
			// Get the order id without prefix
			$orderid = str_replace($gateway->order_id_prefix, '', $orderid_with_prefix);
			
			
			
			if($orderid != "")
			{
				$order = new WC_Order($orderid);	
				
				if($order->status == 'completed' || $order->status == 'processing')
				{
					// Display the Thank you page
					/* $pid = woocommerce_get_page_id('thanks'); */
					/* return get_permalink($pid);	*/	
					return $gateway->get_return_url( $order );



				}
			}
			
		}
		
		
	
	}
	
	// Payment could not be verfied
	$pid = get_option( 'saferpay_page_id', 0);
	return get_permalink($pid) . "?o=" . $order->id;	

			
}


/**
 * Log errors from Saferpay 
 *
 * @return string - returns the content of the response from Saferpay
 */	
function fn_log($message, $orderid = 0, $clean = false)
{

	$file =  plugin_dir_path( __FILE__ ) . "errors/" . $orderid . ".log";

	if($clean && $orderid != 0)
	{
		unlink($file);
	}
	
	error_log($message, 3, $file);
			
}

/**
 * Get content of log for order
 *
 * @return string - returns the content of the response from Saferpay
 */	
function fn_log_for_order($orderid)
{
	$file =  plugin_dir_path( __FILE__ ) . "errors/" . $orderid . ".log";
	
	if(file_exists($file))
	{
		return file_get_contents($file);
	}
	
	return "";
}


/*********************************** Helpers  ***********************************/

/**
 * Get a simplexml attribute (helper function)
 *
 * @access public
 * @return string
 */		
function fn_simplexml_attribute($xml, $name)
{	
	foreach($xml->Attributes() as $key=>$val)
    {
    	if($key == $name)
        	return (string) $val;
    }
	
	return "";
}	


/**
 * Get the content of the provided URL
 *
 * @access public
 * @return string
 */		
function fn_get_urlcontent($url) {
		
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
	
	$result = curl_exec($ch);
	$errorString = curl_error($ch);
	$errorNumber = curl_errno($ch);
	
	curl_close($ch);
	
	if ($errorNumber != 0) {
		if (!empty($errorString)) {
			
			throw new Exception($errorString);
			
		} else {
			throw new Exception('CURL download failed');
		}
	}
	
	return $result;
}




?>
