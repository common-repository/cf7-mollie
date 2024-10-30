<?php
require __DIR__ . '/initialize.php';
//Set database version
global $cf7_mollie_db_version;
$cf7_mollie_db_version = '1.0';

//On Activation
function cf7_mollie_install() {
	global $wpdb;
	global $cf7_mollie_db_version;

	$table_name = $wpdb->prefix . 'cf7_mollie';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		paymentid tinytext NOT NULL,
		orderid tinytext NOT NULL,
		customerid tinytext NOT NULL,
		name tinytext NOT NULL,
		email tinytext NOT NULL,
		amount tinytext NOT NULL,
		description tinytext NOT NULL,
		status tinytext NOT NULL,
		frequency tinytext NOT NULL,
		times tinytext NOT NULL,
		startdate date DEFAULT '0000-00-00' NOT NULL,
		subscriptionid tinytext NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	update_option( 'cf7_mollie_db_version', $cf7_mollie_db_version);
}

function cf7_mollie_import_payments() {
	global $mollie;
	cf7_mollie_setapikey();
	try{
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_mollie';
		$count = 0;
		$payments = $mollie->payments->page();
		do {
			foreach($payments as $payment) {
				//Check if payment id is already in the database
				$query = "SELECT * FROM {$table_name} WHERE paymentid = '{$payment->id}'";
				$result = json_decode(json_encode($wpdb->get_results($query)), True);
				//Only import if not yet in database
				if (empty($result)){
					$count++;
					$wpdb->insert( 
						$table_name, 
						array( 
							'time' => current_time( 'mysql' ), 
							'paymentid' => "{$payment->id}", 
							'orderid' => "{$payment->metadata->order_id}", 
							'customerid' => "", 
							'name' => "{$payment->details->consumerName}", 
							'amount' => "{$payment->amount->value}",
							'description' => "{$payment->description}",
							'status' => "{$payment->status}",
							'subscriptionid' => "{$payment->subscriptionId}"
						)
					);
				}
			}
			if ($payments->hasNext()){
				$payments = $payments->next();
			}
		} while ($payments->hasNext());
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "API call failed: " . htmlspecialchars($e->getMessage());
	}
	return $count;
}

function cf7_mollie_import_subscriptions() {
	global $mollie;
	cf7_mollie_setapikey();
	
	$count = 0;
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';

	try{
		$subscriptions = $mollie->subscriptions->page();
		do {
			foreach($subscriptions as $subscription) {
				//Check if payment id is already in the database
				$query = "SELECT * FROM {$table_name} WHERE subscriptionid = '{$subscription->id}'";
				$result = json_decode(json_encode($wpdb->get_results($query)), True);
				
				//Retrieve customer information
				$customer_id = str_replace("https://api.mollie.com/v2/customers/","",$subscription->_links-> customer->href);
				$customer = $mollie->customers->get($customer_id);
				$customer_name = $customer -> name;
				$customer_email = $customer -> email;
				$payments = $customer->payments();
				$first_payment_status = $payments[0]->status;
		
				//Only import if not yet in database
				if (empty($result) and $subscription->status != "canceled"){
					$count++;
					$wpdb->insert( 
						$table_name, 
						array( 
							'time' => current_time( 'mysql' ), 
							'orderid' => "{$subscription->metadata->order_id}", 
							'customerid' => "{$subscription->customerId}",  
							'amount' => "{$subscription->amount->value}",
							'description' => "{$subscription->description}",
							'startdate' => "{$subscription->startDate}",
							'frequency' => "{$subscription->interval}",
							'subscriptionid' => "{$subscription->id}",
							'name' => "{$customer_name}",
							'email' => "{$customer_email}",
							'status' => "{$first_payment_status}",
						)
					);
				}
			}
			if ($subscriptions->hasNext()){
				$subscriptions = $subscriptions->next();
			}
		} while ($subscriptions->hasNext());
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "API call failed: " . htmlspecialchars($e->getMessage());
	}
	return $count;
}

//On deactivation
function cf7_mollie_remove() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	$sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    delete_option("cf7_mollie_db_version");
}