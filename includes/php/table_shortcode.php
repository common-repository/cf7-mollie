<?php
// Exit if accessed directly
if(!defined( 'ABSPATH' ) ) {
	exit;
}

function cf7_mollie_payment_table_func($atts){
	//Process actions
	if(!empty($_GET['customerid'])){
		//Show payments
		echo cf7_mollie_show_payments();
		die();
	}elseif (!empty($_POST['cf7_mollie_action']) or !empty($_GET["confirm"])) {
		$action = sanitize_text_field($_POST['cf7_mollie_action']);
		if (!empty($_POST['cf7_mollie_action'])){
			$id = sanitize_text_field($_POST['cf7_mollie_rowid']);
		}else{
			$id = !empty($_GET["payment"]) ? $_GET["payment"] : "1";
			$id = $id[0];
		}
		
		//Delete payment
		if ($action == __("Delete payment", 'cf7-mollie-translation') or !empty($_GET["confirm"])){
			echo cf7_mollie_delete_recurring_payment([$id]);
			die();
		//Edit payment
		}else{
			echo cf7_mollie_edit_recurring_payment($id);
			die();
		}
	}else{
		//Process payment update then continue
		if (!empty($_POST['cf7_mollie_amount'])) {
			cf7mollie_process_payment_update();
		}
		
		//Build the payments table
		global $wpdb;
		$table_name = $wpdb->prefix . 'cf7_mollie';
		global $mollie;
		cf7_mollie_setapikey();
		$headerForForm = null;
		$table_id = null;
		$table_class = null;
		$table_style = null;
		$html = "";
		
		$arrInfo = 	shortcode_atts(
			array('hide' => '','columns' => '', 'paymenttype' => '', 'count' => 'false', 'search' => '',	'header' => '', 'style' => '', 'max_entries' => '', 'status' => ''),
			$atts
		);
				
		$hide = $arrInfo['hide'];
		if(!empty($hide)){
			$hide = explode(",",$hide);
			$hide = array_map('trim', $hide);
		}
		$column_names = $arrInfo['columns'];
		if(!empty($column_names)){
			$column_names = explode(",",$column_names);
			$column_names = array_map('trim', $column_names);
		}
		$paymenttype = $arrInfo['paymenttype'];
		$search = $arrInfo['search'];
		$table_id = $arrInfo['id'];
		$table_class = $arrInfo['class'];
		$headerForForm = $arrInfo['header'];
		$table_style = $arrInfo['style'];
		$posts_per_page = $arrInfo['max_entries'];
		$status = $arrInfo['status'];

		//Replace an optional exclamation mark with "not "
		if ($search[0]=="!"){
			$search = "not ".ltrim($search,"!");
		}
		
		$search = str_replace('"', '\\&quot;', $search);
		$columns = array("ID","Time","PaymentID","OrderID","CustomerID","Name","Email","Amount","Description","Status","Frequency","Times","StartDate","SubscriptionID");
		if(!empty($hide)){
			$columns = array_values(array_diff($columns, $hide));
		}
		
		//columns querry
		$columns_query = "";
		foreach ($columns as $column){
			$columns_query .= "`".$column."`,";
		}
		$columns_query = substr($columns_query, 0, -1);
		
		if ($columns_query == ""){$columns_query = "*";}
		
		/* -- Preparing your query -- */
		$query = "SELECT {$columns_query} FROM {$table_name}";
		if ($paymenttype == "subscription"){
			$query .= " WHERE subscriptionid <> ''";
		}elseif($paymenttype == "onetime"){
			$query .= " WHERE subscriptionid = ''";
		}
		if ($status != ""){
			$query .= " and (status='{$status}' or status='')";
		}
		
		if ($search != ""){
			$query .= " and {$search}";
		}
		
		$items = json_decode(json_encode($wpdb->get_results($query)), True);
		$count_query = str_replace($columns_query,"SUM(amount)",$query);
		$count = json_decode(json_encode($wpdb->get_results($count_query)), True);
		$count = round($count[0]['SUM(amount)'],2);
							
		//Get all fields related information
		// If title passed in attribute use that otherwise use form title
		//$html .= '<div id="cf7-mollie-payments-table">';
		$html .= !empty($headerForForm) ? '<h2 id="cf7-mollie-payments-table-title">'.esc_html($headerForForm).'</h2>' : '<h2>'.esc_html(__("Payments Table", 'cf7-mollie-translation')).'</h2>';
		$html .= '<table class="cf7-mollie-payments-table" id="cf7-mollie-payments-table">';
		if (!empty($items)){
			$html .= '<thead class="cf7-mollie-payments-head"><tr>';
			
			//Define table header section here
			if (!empty($column_names)){
				foreach ($column_names as $column){
					$html .= '<th>'.$column.'</th>';
				}
			}else{
				foreach ($columns as $column){
					$html .= '<th>'.__($column, 'cf7-mollie-translation').'</th>';
				}
				if (!in_array("Actions",$hide)){
					$html .= '<th>'.__("Action", 'cf7-mollie-translation').'</th>';
				}
			}
			$html .= '</tr>
					</thead>
					<tbody class="cf7-mollie-payments-body">';

			$i = 0;
			foreach ($items as $item) {
				$i++;
				$html .= '<tr class="cf7-mollie-payments-row-'.$i.'">';
				foreach ($item as $field) {
					//Add show payments button
					if (substr( $field, 0, 4 ) === "cst_"){
						global $wp;
						$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
						$html .= '<td>'.sprintf('%1$s <div style="color:silver">%2$s</div>',
										/*$1%s*/ $field,
										/*$2%s*/ sprintf('<a href="%s&customerid=%s">%s</a>',$current_url,$field,__("Show payments", 'cf7-mollie-translation'))
									).'</td>';
					}else{
						if ($field == ""){
							$field = "X";
						}
						$html .= '<td>'.__($field, 'cf7-mollie-translation').'</td>';
					}
				}
				if (!in_array("Actions",$hide)){
				//Add edit and delete buttons on each row
					$html .= '<td>
								<form action="" method="post">
									<input type="submit" name="cf7_mollie_action" value="'.__("Delete payment", 'cf7-mollie-translation').'"/>';
					
					if ($item['Frequency'] != ""){
						$html .= '<input type="submit" name="cf7_mollie_action" value="'.__("Edit payment", 'cf7-mollie-translation').'"/>';
					}
						$html .= '<input type="hidden" name="cf7_mollie_rowid" value="'.$item['ID'].'"/>
								</form>
							</td>';
				}
				$html .='</tr>';
			}
			$html .='</tbody>';
			
			if ($arrInfo['count']=="true"){
				$count_key = array_search("Amount", $columns);
				$html .= '<tfoot  class="cf7-mollie-payments-foot">
							<tr class="cf7-mollie-payments-row-final">
								<td>'.__("Total", 'cf7-mollie-translation').'</td>';
				for ($i = 2; $i <= $count_key; $i++) {
					$html .= '<td> </td>';
				}
				$html .= '<td>'.$count.'</td>';
				
				for ($i = 1; $i <= count($columns)-$count_key; $i++) {
					$html .= '<td> </td>';
				}
				$html .= '</tr>
							</tfoot>';
			}
			
			$html .='</table>';
				//</div>';
		}else{
			$html .= '<tr>
						<td>
							'.__("No records found.", 'cf7-mollie-translation').'
						</td>
					</tr>
				</table>';
		}
		return $html;
	}		
}
add_shortcode( 'paymentstable', 'cf7_mollie_payment_table_func' );