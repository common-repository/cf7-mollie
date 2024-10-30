<?php

//Shortcode so retrieve payment result
function cf7_mollie_payment_result_func( $atts, $content = null ){
    $a = shortcode_atts( array(
        'success' => 'Payment was successful, thank you.',
        'fail' => 'Payment failed, please try again.',
    ), $atts );

	$content = cf7_mollie_find_shortcode($content);

    $order_id=!empty($_GET["order_id"]) ? $_GET["order_id"] : null;
    $order_id = sanitize_text_field($order_id);
	
    if (empty($_GET["order_id"])) {
        return $content;
    }else{
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_mollie';
		$query = "SELECT * FROM {$table_name} WHERE `orderid` = '{$order_id}'";
		$result = json_decode(json_encode($wpdb->get_results($query)), True);
		$status = $result[0]['status'];
		
        if ($status == "paid"){
            $result = $a['success'];
			$result = cf7_mollie_find_shortcode($result);
			
            return $result;
        }else{
            $result = $a['fail'];
			$result = cf7_mollie_find_shortcode($result);
			
            return $result.$content;
        }
    }
}
add_shortcode( 'payment_result', 'cf7_mollie_payment_result_func' );

function cf7_mollie_find_shortcode($content){
	$content = str_replace('$l','[',$content);
	$content = str_replace('$r',']',$content);
	//Handle shortcodes
	//Find the shortcodes
	$offset = 0;
	$shortcode_positions = array();
	$shortcodes = array();
	while (($pos = strpos($content, '[', $offset)) !== FALSE) {
		$offset   = $pos + 1;
		$shortcode_positions[] = [$pos,strpos($content, ']', $offset)];
	}
	
	//Save the shortcode
    foreach ($shortcode_positions as $shortcode_position) {
		$shortcode_lenght = intval($shortcode_position[1])-intval($shortcode_position[0]) + 1;
        $shortcode=substr($content,intval($shortcode_position[0]),$shortcode_lenght);
		$shortcodes[] = $shortcode;
    }
	
	//Process the shortcode
	foreach ($shortcodes as $shortcode) {
		//Execute the shortcode
		$content = str_replace($shortcode,do_shortcode( $shortcode  ),$content);
	}
	
	return $content;
}