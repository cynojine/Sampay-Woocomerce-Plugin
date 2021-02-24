<?php
/*
	Plugin Name: sampay Payment Gateway
	Plugin URI: https://github.com/cynojine/sampay-woocommerce 
	Description: sampay Woocommerce Payment Gateway allows you to accept payment on your Woocommerce store via Visa Cards,MTN MOoMo.
	Version: 1.0.0
	Author: kazashim Kuzasuwat
	Author URI: http://samafricaonline.com/
	WC requires at least: 3.3
    WC tested up to: 3.4
	License:           GPL-2.0+
 	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'sampay_add_gateway_class' );

function sampay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_sampay'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'sampay_init_gateway_class' );

function sampay_init_gateway_class() {
 
	class WC_sampay extends WC_Payment_Gateway {
 		/**
		  * Class constructor, more about it in Step 3
		  */
		public function __construct() {
			$this->id = "sampay_gateway"; // global ID
			$this->has_fields = false;
			$this->method_title = "sampay Payment Gateway"; // Show Title
			$this->method_description = 'sampay Payment Gateway allows you to receive Mastercard, MTN MoMo and Visa Card Payments via your Woocommerce Powered Site.';// Show Description
			$this->icon = apply_filters('woocommerce_sampay_icon', plugins_url( 'assets/images/logo.png' , __FILE__ ) );// Icon link
			$this->notify_url = WC()->api_request_url( 'WC_sampay' );
			
			
			// Method with all the options fields
			$this->init_form_fields();

			// load variable setting
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );
			$this->appkey = $this->get_option('appkey');
			$this->authkey = $this->get_option('authkey');
			$this->$currency = $this->get_option('currency');
			if ($this->get_option('sandbox') == 'yes') {
				$this->posturl = 'https://samafricaonline.com/sam_pay/public/merchantcheckout';
				$this->geturl =  'https://samafricaonline.com/sam_pay/public/paymentconfirmation';
			} else {
				$this->posturl = 'https://samafricaonline.com/sam_pay/public/merchantcheckout';
				$this->geturl =  'https://samafricaonline.com/sam_pay/public/paymentconfirmation';
			}
			
			

			// This action hook saves the settings
			add_action('woocommerce_receipt_' . $this->id, array( $this, 'receipt_page'), 10, 1);
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			// You can also register a webhook here
			add_action( 'woocommerce_api_'.strtolower(get_class($this)) , array(&$this, 'get_sampay_response' ));
 		 }
		 
		  
		/**
	 	* Plugin options, we deal with it in Step 3 too
		*/
		public function init_form_fields(){

			$this->form_fields = array(
				'enabled' => array(
					'title'		=> 'Enable / Disable',
					'label'		=> 'Enable sampay',
					'type'		=> 'checkbox',
					'default'	=> 'no',
				),
				'title' => array(
					'title'		=> 'Title',
					'type'		=> 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'	=> 'sampay',
					'desc_tip'  => true,
				),
				'description' => array(
					'title'		=> 'Description',
					'type'		=> 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'	=> 'Payment Methods Accepted: MasterCard, VisaCard, Mtn MOMO. Sampay Assured',
					'desc_tip'    => true,
					'css'		=> 'max-width:450px;',
				),
				'appkey' => array(
					'title' => 'sampay App Key',
					'type' => 'text',
					'description' => 'Enter Your sampay Merchant ID, this can be gotten on your account page when you login on sampay',
					'desc_tip'    => true,
				),
				'authkey' => array(
						'title' => 'Auth Key', 
						'type' => 'text',
						'description' => 'long string of Alphanumeric characters sent to you by your account officer',
						'desc_tip'    => true,
					),
				'currency' => array(
						'title' => 'Store Currency',
						'type' => 'select',
						'description' => 'Currency you wish to accept payment in. Make sure this tallys with set currency of woocommerce and bank',
						'desc_tip'    => true,
						'options' => array(
							"566" => "ZMW"), 
						),
				'sandbox' => array(
						'title' => 'Sandbox',
						'label'		=> 'Enable Test Mode',
						'type' => 'checkbox',
						'description' => 'Enable/Disable Test mode',
						'desc_tip'    => true,
						'default'	=> 'yes',
					)
			);

		}

		public function receipt_page($order_id) {
		 if(isset($_GET['token'])){
		 $this->sampay_process_onreturn();
		 }
			echo '<p><span style="background-color: blue;color: #f4f4f4;padding: 5px;">Please review your order, then click on "Pay via sampay" to be redirected to the Payment Gateway</span></p>';
			echo $this->generate_sampay_button($order_id);
			
		}

		/**
		 * Generate the sampay Payment button link
	    **/
	    
	    
	    
	    
public function sampay_process_onreturn(){
    	global $woocommerce;
        //process order here
        //check order status on sampay
        $url = "https://samafricaonline.com/sam_pay/public/wpcheckstatus";
    
        $tok = $_GET['token'];
        $data = array('token' => $tok);
        
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'httpversion' => '1.0',
            'timeout' => 45,
            'sslverify' => false,
            'headers' => array('Content-Type'=>'application/json; charset=utf-8'),
            'body' => json_encode($data))
            );
        if ( is_wp_error( $response ) ) {
        //$error_message = $response->get_error_message();
    
        }
        else{
        if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
               //echo "response from page not 200";
            //	var_dump( $response);
        }
        else{
        if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
        $response_body = wp_remote_retrieve_body( $response );
        $apiresponse = json_decode($response_body);
        
        //fetch all need variables from response and loacl
        $status = $apiresponse->status;
        $status = strtolower($status);
        $orderid = $apiresponse->orderid;
        $order_id = trim($orderid);
        $tranxid = "n/a";
        
    
    	$order = wc_get_order($order_id);
    
        
        //check order status 
        $ostat = $order->get_status();
        
        //process if still pending
        if($ostat=='pending'){
            if($status=='canceled'){
            #payment cancelled
				$respond_desc = "Transaction Cancelled.";
				$message_resp = "Transaction was cancelled by Sampay User.";
				$message_type = "error";
				$order->add_order_note('sampay payment failed:<br> ' . $respond_desc, true);
				$order->update_status('cancelled');
				wc_add_notice($order, $message_type);
				wp_redirect($order->get_cancel_order_url());
            }
            elseif($status=='paid'){
                $respond_desc = "Transaction Successful";
				$message_resp = "Thank You!. <br><strong>Reason</strong>: " . $respond_desc;
				$message_type = "success";
				$order->payment_complete();
				$order->update_status('completed');
				wc_reduce_stock_levels( $order->get_id() );
				$order->add_order_note( 'Hey, your order is paid! Thank you! <br>Sampay Transaction Reference: ' . $tranxid, true );
				wc_add_notice( $message_resp, $message_type );
				// Empty cart
				$woocommerce->cart->empty_cart();
				// Redirect to the thank you page
				wp_redirect($this->get_return_url( $order ));
            }
            else{}
        }
        
        
        }
        }
        }
}
	   
	   
	   
	   
		public function generate_sampay_button($order_id) {
			global $woocommerce;
			// we need it to get any order details
			$order = wc_get_order( $order_id );
			$appkey = $this->appkey;
			$authkey = $this->authkey;
			$orderID = $order->get_id() . '_' . date("ymds");
			$ordername = $order->get_id() . '_' . date("ymds");
			$orderdetails = $order->get_id() . '_' . date("ymds");
			$ordertotal = $order->get_total();
			$ordertotal *=1;
			$currency = $this->$currency;
			$sampay_tranx_noti_url = $this->notify_url;
		
			

			//Perform hash
			$my_hash = $appkey . $authkey . $orderID . $ordername . $orderdetails . $ordertotal . $currency . $sampay_tranx_noti_url;
			$sampay_hash = hash('sha512', $my_hash);

			/*
			* Array with parameters for API interaction
			*/
		
	foreach ( $order->get_items() as $item_id => $item ) {
   $product_id = $item->get_product_id();
   $variation_id = $item->get_variation_id();
   $product = $item->get_product();
   $name = $item->get_name();
   $quantity = $item->get_quantity();
   $subtotal = $item->get_subtotal();
   $total = $item->get_total();
   $tax = $item->get_subtotal_tax();
   $taxclass = $item->get_tax_class();
   $taxstat = $item->get_tax_status();
   $allmeta = $item->get_meta_data();
   $somemeta = $item->get_meta( '_whatever', true );
   $type = $item->get_type();
}
;
	$purchasedetails="Purchase";
			
			$data = array(
				'AppKey'      => $this->appkey, // API Key Merchant / 
				'AuthKey'  => $this->authkey, 
				'OrderID'  => $order->get_id(),
				'OrderName' => $order->$product,
				'OrderTotal'    => $order->order_total ?? $order->total,
				'OrderDetails' => $purchasedetails,
				'Currency' => $order->get_currency(),
				'WPRTURL' => $order->get_order_key()
			);

			$sampay_args_array = array();
            foreach ($data as $key => $value) {
                $sampay_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
            }
			return '<form action="' . $this->posturl . '" method="POST" id="sampay_payment_form">' . implode('', $sampay_args_array) . '
			<input type="submit" class="button-alt" id="submit_sampay_payment_form" value="' . __('Pay via sampay', 'sampay') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'sampay') . '</a>
            <script type="text/javascript">
                function processsampayJSPayment(){
                jQuery("body").block(
                        {
                            message: "<img src=\"' . plugins_url('assets/images/ajax-loader.gif', __FILE__) . '\" alt=\"redirecting...\" style=\"float:left; margin-right: 10px;\" />' . __('Thank you for your order. We are now redirecting you to Payment Gateway to make payment.', 'sampay') . '",
                                overlayCSS:
                        {
                            background: "#fff",
                                opacity: 0.6
                    },
                    css: {
                        padding:        20,
                            textAlign:      "center",
                            color:          "#555",
                            border:         "3px solid #aaa",
                            backgroundColor:"#fff",
                            cursor:         "wait",
                            lineHeight:"32px"
                    }
                    });
                    jQuery("#sampay_payment_form").submit();
                    }
                    jQuery("#submit_sampay_payment_form").click(function (e) {
                        e.preventDefault();
                        processsampayJSPayment();
                    });
            </script>
			</form>';
		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
			global $woocommerce;

			$order = wc_get_order($order_id);
			return array(
				'result' => 'success',
				'redirect'	=> $order->get_checkout_payment_url( true )
			);

		}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function get_sampay_response() {
		    global $woocommerce;
			//Get reponse
			$filepamil = 'logPam.txt';
			if(! empty( $_POST )){
			    //echo '<pre>'; print_r($_POST); echo '</pre>';
			    $posted = wp_unslash($_POST);
			    $appkey = $this->appkey;
			    $authkey = $this->authkey;
    			
            	$tranxid = $posted['sampay_tranx_id'];
    			$sampay_echo_data = $posted['sampay_echo_data'];
    			$data = explode(";", $sampay_echo_data);
    			$wc_order_arr = explode("_", $data[0]);
    			$wc_order_id = trim($wc_order_arr[0]);
    			$resp_order = wc_get_order($wc_order_id);
    			$total_amount = $resp_order->get_total();
    			$total_amt =$total_amount * 100;
    			$reff = base64_encode($total_amount . "| Sampay_TranxId" . $tranxid);
    			$resp_order->add_order_note('Reff: ' . $reff);
				if ($posted['sampay_tranx_status_code'] === '200') {
					#payment cancelled
					$respond_desc = $posted['sampay_tranx_status_msg'];
					$message_resp = "Your transaction failed. <br><strong>Reason</strong>: " . $respond_desc . "<br><strong>Transaction Reference<strong>:" . $tranxid . '<br>You may restart your payment below.';
					$message_type = "error";
					$resp_order->add_order_note('sampay payment failed:<br> ' . $respond_desc . '<br>Transaction Reference: ' . $tranxid, true);
					$resp_order->update_status('cancelled');
					wc_add_notice($message_resp, $message_type);
					wp_redirect($resp_order->get_cancel_order_url());
				}else{
					//Payment params successfully posted
					//confirm hash
				// 	if (strtolower($posted['sampay_verification_hash']) == strtolower($sampay_hash)){
					$hash_req = hash('sha512', $appkey.$authkey.$token.$tranxid);
					$get_url = $this->geturl;
					$params ='appkey='.$appkey.'authkey='.$authkey.'token='.$token.'&amount='.$total_amt.'&tranxid='.$tranxid.'&hash='.$hash_req;
					$my_url = $get_url. '?' .$params;
					$gt_json_response = wp_remote_get($my_url);
					    
					if( !is_wp_error( $gt_json_response ) ) {
						$data = json_decode( $gt_json_response['body'], true );
						if( $data['ResponseCode'] == '00'){
							#payment successful
							// we received the payment
							$respond_desc = $data['ResponseDescription'];
							$message_resp = "Thank You!. <br><strong>Reason</strong>: " . $respond_desc . "<br><strong>Transaction Reference</strong>:" . $tranxid;
							$message_type = "success";
							$resp_order->payment_complete();
							$resp_order->update_status('completed');
							wc_reduce_stock_levels( $resp_order->get_id() );
							$resp_order->add_order_note( 'Hey, your order is paid! Thank you! <br>sampay Transaction Reference: ' . $tranxid, true );
							wc_add_notice( $message_resp, $message_type );
							// Empty cart
							$woocommerce->cart->empty_cart();
							// Redirect to the thank you page
							wp_redirect($this->get_return_url( $resp_order ));

						}else{
							// something went horribly wrong
							$respond_desc = $data['ResponseDescription'];
							$message_resp = "Your Transaction was unsuccessful.<br>Reason: ". $respond_desc . "<br><strong>Transaction Reference</strong>:" . $tranxid ."<br><strong>Please Try Again!</strong>";
							$message_type = "error";
							$resp_order->add_order_note('sampay payment failed:<br> ' . $respond_desc . '<br>Transaction Reference: ' . $tranxid, true);
							$resp_order->update_status('cancelled');
							wc_add_notice( $message_resp, $message_type );
							wp_redirect($resp_order->get_cancel_order_url());
						}
					} else {
						wc_add_notice(  'Connection error.', 'error' );
					}
				}
			}else {
				exit();
			die();
			}
		}
		
		public function getostat($order_id){
         	global $woocommerce;
         		$order = wc_get_order($order_id);   
                $status = $order->get_status();
                return $status;
         }
	
        
		
	}
}

//add_action( 'plugins_loaded', 'sampay_alt_process' );
	


