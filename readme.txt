=== WooCommerce Payment Gateway for Saferpay ===
Contributors: smkb
Tags: woocommerce, wordpress ecommerce, payment gateway
Requires at least: 3.5
Tested up to: 3.5
Stable tag: 0.4.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WooCommerce Payment Gateway for Saferpay. Adds a Saferpay payment method to the WooCommerce eCommerce plugin. 

== Description ==

This plugin adds a Saferpay payment method to your WooCommerce eCommerce store. Saferpay is a payment solution provided by SIX Payment Services. Please note that this plugin is __not__ provided by SIX Payment Services/Saferpay and it is safe to assume that they will __not__ support it in any way. You will need to have a Saferpay account to use this plugin in a commerical setting. However, you can use the test account to get a feel of the functionality. 


= Currently supported payment methods =

* __MasterCard__
* __Visa__ 
* __American Express__
* __Direct payment__ 

Supported languages are English and German. And to come to an end - Saferpay is a registered trademark of the SIX Group.

= Recommendations =
Technically, this plugin does not require an installed and activated SSL certificate on your server. Best practice in e-Commerce dictates that any time you are dealing with private customer information such as billing details, the transfer of such data should be encrpyted using a secure protocol (HTTPS/SSL).


== Installation ==

= Minimum Requirements =

* WordPress 3.5 or greater
* WooCommerce 2.0.0 or greater
* PHP version 5.2.4 or greater
* PHP cURL extension 
* Saferpay account
* Activated certificate hosting for your Saferpay account


= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t even need to leave your web browser. To do an automatic install of WooCommerce Payment Gateway for Saferpay, log in to your WordPress admin panel, navigate to the Plugins menu and click Add New.

In the search field type “WooCommerce Payment Gateway for Saferpay” and click Search Plugins. Once you’ve found the plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now. After clicking that link you will be asked if you’re sure you want to install the plugin. Click yes and WordPress will automatically complete the installation.

= Manual installation =

The manual installation method involves downloading our eCommerce plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

== Frequently Asked Questions ==

In preparation


== Screenshots ==

1. Settings panel in WooCommerce.
2. Payment method on the checkout page


== Changelog ==

= 0.4.6 - 03/09/2013 =
* Added: Configuration option - Prefix for order id on Saferpay
* Added: Configuration option - Order description during Saferpay checkout

= 0.4.5 - 03/09/2013 =
* Adjusted version/stable tag to be reflected properly

= 0.4.4 - 29/08/2013 =
* Submission to WordPress.org
* German translation added

= 0.4.3 - 10/08/2013 =
* Added localisation support

= 0.4.2 - 03/08/2013 =
* Added direct payment

= 0.4.1 - 29/07/2013 =
* Added alerts to admin

= 0.4.0 - 19/07/2013 =
* Added error handling

= 0.3 - 14/07/2013 =
* Changed payment notification from SUCCESSLINK to NOTIFY_URL

= 0.2 - 06/07/2013 =
* First fully functional version

= 0.1 - 26/06/2013 =
* Initial prototype


