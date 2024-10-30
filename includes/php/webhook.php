<?php
require_once __DIR__ . '/initialize.php';

function cf7_mollie_payment_status(){
	global $wpdb;
	cf7_mollie_setapikey();
    global $mollie;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	
	if (isset($_POST['id'])) {
		$paymentid = sanitize_text_field($_POST["id"]);
		try{
			$payment = $mollie->payments->get($paymentid);
			$payment_status = $payment->status;
			$order_id = $payment->metadata->order_id;
			$amount = $payment->amount->value;
			$description = $payment->description;
			$subscriptionId  = $payment -> subscriptionId;
			$consumer_name = $payment->details->consumerName;
		} catch (\Mollie\Api\Exceptions\ApiException $e) {
			echo "API call failed: " . htmlspecialchars($e->getMessage());
			$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET subscriptionid='%s' WHERE orderid='{$order_id}'",$e->getMessage()));
		}
		
		//Check if the subscription already exists
		if ($subscriptionId != ""){
			$query = "SELECT * FROM {$table_name} WHERE `subscriptionid` ='".$subscriptionId."'";
			$result = json_decode(json_encode($wpdb->get_results($query)), True);
			if (count($result)> 0){
				return;
			}
		}
		
		if ($order_id != ""){
			cf7_mollie_subscribe($order_id, $amount);
			$query = "SELECT * FROM {$table_name} WHERE orderid = '{$order_id}'";
			$result = json_decode(json_encode($wpdb->get_results($query)), true);
		
			if ($result[0]['name']==""){
				$name = $consumer_name;
			}else{
				$name = $result[0]['name'];
			}
		}else{
			$result = null;
		}
		
		//Only import if not yet in database
		if (empty($result)){
			$wpdb->insert(
				$table_name,
				array(
					'time' => current_time( 'mysql' ),
					'paymentid' => "{$paymentid}",
					'orderid' => "{$order_id}",
					'amount' => "{$amount}",
					'description' => "{$description}",
					'status' => "{$payment_status}",
					'name' => "{$name}",
					'subscriptionid' => "{$subscriptionId}"
				)
			);
		}else{
			//Update existing entry
			$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET paymentid='%s', status='{$payment_status}',name='{$name}' WHERE orderid='{$order_id}'",$paymentid));
			
			if ($payment->resource == "subscription"){
				$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET subscriptionid='%s' WHERE orderid='{$order_id}'",$subscriptionId));
			}
		}
	}else{
		$wpdb->insert(
				$table_name,
				array(
					'time' => current_time( 'mysql' ),
					'paymentid' => "",
					'orderid' => "",
					'amount' => "",
					'description' => "no post id is given post array is ".implode("", $_POST),
					'status' => "",
					'name' => "",
					'subscriptionid' => ""
				)
			);
	}
}

function cf7_mollie_subscribe($order_id, $amount){
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	cf7_mollie_setapikey();
    global $mollie;
	
	try{
		/*
		 * Create a subscription based on the first payment
		 */
		$query = "SELECT * FROM {$table_name} WHERE `orderid` =".$order_id;
		$result = json_decode(json_encode($wpdb->get_results($query)), true);
		
		if ($result[0]['subscriptionid']=="recurring"){
			$customer_id = $result[0]['customerid'];

			$wpcf7 = WPCF7_ContactForm::get_current();
    		$formid = $wpcf7->id();

			$protocol = isset($_SERVER['HTTPS']) && strcasecmp('off', $_SERVER['HTTPS']) !== 0 ? "https" : "http";
    		$hostname = $_SERVER['HTTP_HOST'];
    		$path = dirname(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF']);

			$webhookUrl	= "{$protocol}://{$hostname}{$path}/webhook.php";
			error_log($result);
			error_log($webhookUrl);
			if ($amount == 0){
				$amount = $result[0]['amount'];
			}

			$frequency = $result[0]['frequency'];
			if ($frequency == null){
				$frequency =1;
			}
			$frequency = "$frequency month";
			$description = $result[0]['description'];
			$startdate = $result[0]['startdate'];
			if ($startdate == null or $startdate == "0000-00-00"){
				$now = date('Y-m-d');
				$startdate = date("Y-m-d", strtotime("$now +$frequency"));
			}

			$customer = $mollie->customers->get($customer_id);
			
			$customer->createSubscription([
			   "amount" => [
					 "currency" => "EUR",
					 "value" => "{$amount}",
			   ],
			   "interval" => "{$frequency}",
			   "startDate" => "{$startdate}",
			   "metadata" => [
					"order_id" => $order_id,
					"formid" => $formid,
				],
			   "description" => "{$description}",
			   "webhookUrl" => "{$webhookUrl}",
			]);
			
			//Store the subscriptionid in the database
			$customer = $mollie->customers->get($customer_id);
			$subscriptions = $customer->subscriptions();
			$subscriptionId = $subscriptions[0]->id;
			$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET subscriptionid='%s',startdate='{$startdate}',frequency='{$frequency}'  WHERE orderid='{$order_id}'",$subscriptionId));
		}
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "API call failed: " . htmlspecialchars($e->getMessage());
		$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET subscriptionid='%s' WHERE orderid='{$order_id}'",$e->getMessage()));
	}
}