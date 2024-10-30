<?php
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

require __DIR__ . '/initialize.php';
global $wpdb;
$table_name = $wpdb->prefix . 'cf7_mollie';

//$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET paymentid='%s' WHERE orderid='1571221517'","blbla"));

/************************** CREATE A PACKAGE CLASS *****************************
 *******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * To display this example on a page, you will first need to instantiate the class,
 * then call $yourInstance->prepare_items() to handle any data manipulation, then
 * finally call $yourInstance->display() to render the table to the page.
 * 
 * Our theme for this list table is going to be payments.
 */
class CF7_Mollie_Payment_List_Table extends WP_List_Table {
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'payment',     //singular name of the listed records
            'plural'    => 'payments',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) ); 
    }
	
	//Add extra buttons to the table header
	function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//Add rows dropdown
			?>
			<div class="alignleft actions bulkactions">
			<label>Rows:</label>
			</div>
			<div class="alignleft actions bulkactions">
				<select id="visible_rows" onchange="redirect_rows()" class="visible_rows">
					<option value="5"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 5){
							echo 'selected="selected"';
						}
					?>
					>5</option>
					<option value="10"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 10){
							echo 'selected="selected"';
						}
					?>
					>10</option>
					<option value="20"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 20){
							echo 'selected="selected"';
						}
					?>
					>20</option>
					<option value="30" 
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 30){
							echo 'selected="selected"';
						}
					?>
					>30</option>
					<option value="40"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 40){
							echo 'selected="selected"';
						}
					?>
					>40</option>
					<option value="50"
					<?php 
						if (empty($_GET["rows"]) or $_GET["rows"] == 50){
							echo 'selected="selected"';
						}
					?>
					>50</option>
					<option value="100"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 100){
							echo 'selected="selected"';
						}
					?>
					>100</option>
					<option value="200"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 200){
							echo 'selected="selected"';
						}
					?>
					>200</option>
					<option value="400"
					<?php 
						if (!empty($_GET["rows"]) and $_GET["rows"] == 400){
							echo 'selected="selected"';
						}
					?>
					>400</option>
				</select>
				<?php
					$this->search_box('Search', 'search');
				?>
			</div>
			<?php
			echo "
				<script type=\"text/javascript\">
					function redirect_rows() {
						var visible_rows = document.getElementById('visible_rows');
						visible_rows = visible_rows.options[visible_rows.selectedIndex].value;
						
						var url = window.location.href;    
						if (url.indexOf('?') > -1){
						   url += '&rows='+visible_rows;
						}else{
						   url += '?rows='+visible_rows;
						}
						window.location.href = url;
					}
				</script>
			";
		}
	}

	//Default output
    function column_default($item, $column_name){
        switch($column_name){
			case 'time':
            case 'orderid':
            case 'name':
			case 'email':
			case 'amount':
			case 'description':
			case 'status':
			case 'frequency':
			case 'startdate':
			case 'subscriptionid':
				return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    //output for the payment column
    function column_paymentid($item){
        //Build row actions
		//Only subscriptions are editable, it makes no sense to edit payments already made.
		if ($item['subscriptionid'] != ""){
			$actions = array(
				'edit'      => sprintf('<a href="?page=%s&action=%s&payment=%s">'.__('Edit', 'cf7-mollie-translation').'</a>',$_REQUEST['page'],'edit',$item['id']),
				'delete'    => sprintf('<a href="?page=%s&action=%s&payment[]=%s">'.__('Delete', 'cf7-mollie-translation').'</a>',$_REQUEST['page'],'delete',$item['id']),
			);
		}
        
        //Return the paymentid contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['paymentid'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }
	
	//output for the customerid column
    function column_customerid($item){
        //Build row actions
		//Only subscriptions are editable, it makes no sense to edit payments already made.
		if ($item['subscriptionid'] != ""){
			$actions = array(
				'show'      => sprintf('<a href="?page=%s&customerid=%s">%s</a>',$_REQUEST['page'],$item['customerid'],__('Show payments', 'cf7-mollie-translation')),
			);
		}
        
        //Return the paymentid contents
		if ($item['subscriptionid'] != ""){
			return sprintf('%1$s <span style="color:silver">%2$s</span>',
				/*$1%s*/ $item['customerid'],
				/*$2%s*/ $this->row_actions($actions)
			);
		}else{
			return $item['customerid'];
		}
		
    }

    //Settings for the Checkbox column
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("payment")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }


    //Define the visible columns
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'time'     => __('Time', 'cf7-mollie-translation'),
            'paymentid'     => __('Payment ID', 'cf7-mollie-translation'),
			'customerid'     => __('Customer ID', 'cf7-mollie-translation'),
            'orderid'    => __('Order ID', 'cf7-mollie-translation'),
            'name'  => __('Name', 'cf7-mollie-translation'),
			'email'    => __('E-mail', 'cf7-mollie-translation'),
			'amount'    => __('Amount', 'cf7-mollie-translation'),
			'description'    => __('Description', 'cf7-mollie-translation'),
			'status'    => __('Status', 'cf7-mollie-translation'),
			'frequency'    => __('Frequency', 'cf7-mollie-translation'),
			'startdate'    => __('Start date', 'cf7-mollie-translation'),
			'subscriptionid'    => __('Subscription ID', 'cf7-mollie-translation')
        );
        return $columns;
    }


    //Set which columns are sortable
    function get_sortable_columns() {
        $sortable_columns = array(
			'time'     => array('time',false), 
            'paymentid'     => array('paymentid',false),     //true means it's already sorted
            'orderid'    => array('orderid',false),
			'customerid'    => array('customerid',false),
            'name'  => array('name',false),
			'email'  => array('email',false),
			'amount'  => array('amount',false),
			'description'  => array('description',false),
			'status'  => array('status',false),
			'frequency'  => array('frequency',false),
			'startdate'  => array('startdate',false),
			'subscriptionid'  => array('subscriptionid',false)
        );
        return $sortable_columns;
    }


    //Define available bulkactions
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }


    //Functions to handle bulkactions
    function process_bulk_action() {
		global $mollie;
		cf7_mollie_setapikey();
		global $wpdb;
		global $table_name;
		
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
			echo cf7_mollie_delete_recurring_payment();
			wp_die();
        }elseif( 'edit'===$this->current_action() ) {
			echo cf7_mollie_edit_recurring_payment();
			wp_die();			
        }  
    }


    //Prepare the data to show
    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
		global $table_name;
		
		$search = ( isset( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : "";	     
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
		
		/* -- Preparing your query -- */
	    $query = "SELECT * FROM {$table_name}";
		
		//Include an optional search
		if(!empty($search)){
			$query .= " WHERE (paymentid LIKE '%{$search}%' OR orderid LIKE '%{$search}%' OR customerid LIKE '%{$search}%'  OR name LIKE '%{$search}%' OR email LIKE '%{$search}%' OR amount LIKE '%{$search}%' OR description LIKE '%{$search}%' OR status LIKE '%{$search}%' OR frequency LIKE '%{$search}%' OR startdate LIKE '%{$search}%' OR subscriptionid LIKE '%{$search}%')";
		}
		
		/* -- Ordering parameters -- */
	    //Parameters that are going to be used to order the result
	    $orderby = !empty($_GET["orderby"]) ? esc_sql($_GET["orderby"]) : 'ASC';
	    $order = !empty($_GET["order"]) ? esc_sql($_GET["order"]) : '';
	    if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }
		/* -- Pagination parameters -- */
        //Number of elements in your table?
        $totalitems = $wpdb->query($query); //return the total number of affected rows
        //How many to display per page?
        $perpage = !empty($_GET["rows"]) ? $_GET["rows"] : 50;
		if ($perpage == "all"){$perpage = $totalitems;}
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? esc_sql($_GET["paged"]) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
		if(!empty($paged) && !empty($perpage)){
			$offset=($paged-1)*$perpage;
	    	$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage,
		) );
		//The pagination links are automatically built according to those parameters
		
		/* — Register the Columns — */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		/* -- Fetch the items -- */
		$this->items = json_decode(json_encode($wpdb->get_results($query)), True);
	}
}

//Add admin menu
add_action('admin_menu', 'cf7mollie_menu_pages');
function cf7mollie_menu_pages(){
    add_submenu_page( 'wpcf7', "Mollie", "Mollie", "administrator", 'wpcf7-mollie-config', 'cf7mollie_menu_output'  );
}

function cf7mollie_menu_output(){
	$count = 0;
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	if (!empty($_POST['import_button'])) {
		$import = sanitize_text_field($_POST['import_button']);
		if ($import==__("Import All Payments", 'cf7-mollie-translation')){
			$import="payment";
			$count = cf7_mollie_import_payments();//in database.php
		}elseif($import==__("Import Subscriptions", 'cf7-mollie-translation')){
			$import="subscription";
			$count = cf7_mollie_import_subscriptions();//in database.php
		}		
		
		if ($count > 0){
			if ($count > 1){
				$import .= "s";
			}
			$text = sprintf(_n("%s %s is imported succesfully","%s %s are imported succesfully", $count, 'cf7-mollie-translation'),$count,$import);
			$color = "green";
		}elseif($count == 0){
			$text = sprintf(__("No %s are imported.", 'cf7-mollie-translation'),$import);
			$color = "red";
		}
		cf7mollie_allert_message($text,$color);
	}elseif (!empty($_POST['bankname'])) {
		cf7mollie_process_mandate();
	}elseif (!empty($_POST['cf7_mollie_amount'])) {
		cf7mollie_process_payment_update();
	}
	
	//Determine active tab
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'payments_made';
	
	//Define tabs
    ?>
	<h2 class="nav-tab-wrapper">
		<a href="?page=wpcf7-mollie-config&tab=payments_made" class="nav-tab  <?php echo $active_tab == 'payments_made' ? 'nav-tab-active' : ''; ?>"><?php _e("Payments made", 'cf7-mollie-translation')?></a>
		<a href="?page=wpcf7-mollie-config&tab=mollie_api" class="nav-tab <?php echo $active_tab == 'mollie_api' ? 'nav-tab-active' : ''; ?>"><?php _e("Mollie Settings", 'cf7-mollie-translation')?></a>
		<a href="?page=wpcf7-mollie-config&tab=mandate" class="nav-tab <?php echo $active_tab == 'mandate' ? 'nav-tab-active' : ''; ?>"><?php _e("Add payment mandate", 'cf7-mollie-translation')?></a>
	</h2>
    <?php
	
	//Set contents of the tabs
	if( $active_tab == 'payments_made' ) {
		if(empty($_GET['customerid'])){
			cf7mollie_payments_tab();
		}else{
			echo cf7_mollie_show_payments();
		}
	} elseif ( $active_tab == 'mollie_api' ) {
		cf7mollie_api_key_tab();
	}else{
		cf7_mollie_mandate_tab();
	}
}

function cf7mollie_payments_tab(){
	//Create an instance of our package class...
	$ListTable = new CF7_Mollie_Payment_List_Table();
	//Fetch, prepare, sort, and filter our data...
	$ListTable->prepare_items();
	?>
	<div class="wrap">	
		<div id="icon-users" class="icon32"><br/></div>
		<h2><?php _e("Payments Table", 'cf7-mollie-translation')?></h2>
		
		<div>
			<font size="3">
				<?php _e("Below you can find all payments done.", 'cf7-mollie-translation')?><br>
				<?php _e("All payments with a Subscription ID can be edited, that will edit the actual subscription on the Mollie side.", 'cf7-mollie-translation')?><br>
				<?php _e("When deleting a subscription the subscription will be cancelled at the Mollie side.", 'cf7-mollie-translation')?>
				<br><br><br>
			</font>
		</div>
		
		<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
		<form id="payments-filter" method="get">
			<!-- For plugins, we also need to ensure that the form posts back to our current page -->
			<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			<!-- Now we can render the completed list table -->
			<?php $ListTable->display() ?>
		</form>
		
	</div>
	<?php
}

function cf7mollie_api_key_tab(){
	if (get_option("CF7_mollie_global_key") and (get_option("CF7_mollie_global_key")) != ""){
		$buttontext = __("Reset Api Key", 'cf7-mollie-translation');
	}else{
		$buttontext = __("Set Api Key", 'cf7-mollie-translation');
	}
	
	?>
	<div class="wrap">
		<h2>
			<?php _e("Mollie Settings", 'cf7-mollie-translation')?>
		</h2>
	</div>
	
	<?php if(isset($_POST['apikey'])): 
		$apikey=$_POST['apikey'];
		if (strpos($apikey, 'test_')==0 or strpos($apikey, 'live_')==0):
			$text = __("Your Mollie Api Key is saved.", 'cf7-mollie-translation');
			$color = "green";
			update_option("CF7_mollie_global_key",$apikey);
			$buttontext = __("Reset Api Key", 'cf7-mollie-translation');
			//Remove the dabase
			cf7_mollie_remove();
			//Recreate the database
			cf7_mollie_install();
		else:
			$text = __("Please enter a valid api key!", 'cf7-mollie-translation');
			$color = "red";
		endif;
		
		cf7mollie_allert_message($text,$color);
		endif; ?>

		<div>
		<form name="cf7_name" id="cf7_name" action="admin.php?page=wpcf7-mollie-config&tab=mollie_api" method="post">
			<label for="apikey" class="cf7_mollie_api_api_label"><?php _e("Mollie Api Key:", 'cf7-mollie-translation')?></label>
			<input type="text" name="apikey" maxlength="50" value='<?php echo get_option("CF7_mollie_global_key"); ?>' style="height:2em; width:25em;">
			<input type="submit" name="formSubmit" value='<?php echo $buttontext; ?>' class="button">
		</form>
		<br>
		<?php _e("Press the button to import all payments from Mollie", 'cf7-mollie-translation')?>
		<form name="cf7_mollie_Import_all_payments" id="cf7_mollie_Import_payments" action="admin.php?page=wpcf7-mollie-config" method="post">
			<input type="submit" name="import_button" value="<?php _e("Import All Payments", 'cf7-mollie-translation')?>" class="button">
		</form>
		<br>
		<?php _e("Press the button to import all subscriptions from Mollie", 'cf7-mollie-translation')?>
		<form name="cf7_mollie_Import_subscriptions" id="cf7_mollie_Import_subscriptions" action="admin.php?page=wpcf7-mollie-config" method="post">
			<input type="submit" name="import_button" value="<?php _e("Import Subscriptions", 'cf7-mollie-translation')?>" class="button">
		</form>
	</div>
	<?php
}

function cf7mollie_allert_message($text, $color){
	?>
	<div class="wrap select-specific">
		<div style="color:<?php echo $color; ?>;" class="cf7_mollie_api_alert">
			 <span style="color:<?php echo $color; ?>;" class="cf7_mollie_api_closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
			 <?php echo $text;?>
		</div>
	</div>
	<?php
}

function cf7_mollie_mandate_tab(){
	$html = '
	<div class="wrap">
		<div id="icon-users" class="icon32"><br/></div>
		<h2>'.__("Add a payment mandate.", 'cf7-mollie-translation').'</h2>'.
		__("Fill in the form below to:", 'cf7-mollie-translation').'<br>
		* '.__("Create a customer", 'cf7-mollie-translation').'<br>
		* '.__("Create a payment mandate for that customer", 'cf7-mollie-translation').'<br>
		* '.__("Create a subscription for that customer (optional)", 'cf7-mollie-translation').' <br><br>
		<form action="admin.php?page=wpcf7-mollie-config&tab=payments_made" method="post">
			'.__("Display Name:", 'cf7-mollie-translation').'<br>
			<input type="text" name="name" class="cf7_mollie_name"><br>
			'.__("Bank account name:", 'cf7-mollie-translation').'<br>
			<input type="text" name="bankname" class="cf7_mollie_bankname"><br>
			'.__("E-mail:", 'cf7-mollie-translation').'<br>
			<input type="text" name="email" class="cf7_mollie_email"><br>
			'.__("Bank account IBAN:", 'cf7-mollie-translation').'<br>
			<input type="text" name="iban" class="cf7_mollie_iban"><br>
			'.__("Signature date", 'cf7-mollie-translation').'<br>
			<input type="date" name="signaturedate" id="signaturedate" class="cf7_mollie_signaturedate" max="'.date('Y-m-d').'"><br>
			<br>
			'.__("Fill in the fields below only if you want to add a subscription as well.", 'cf7-mollie-translation').'<br>
			'.__("If you add an existing order ID you can change an oneoff payment to a subscription.", 'cf7-mollie-translation').'<br><br>
			'.__("Amount:", 'cf7-mollie-translation').'<br>
			<input type="text" name="amount" class="cf7_mollie_amount"><br>
			'.__("Description:", 'cf7-mollie-translation').'<br>
			<input type="text" name="description" class="cf7_mollie_description"><br>
			'.__("Interval:", 'cf7-mollie-translation').'<br>
			<input type="number" name="interval" class="cf7_mollie_interval" value="1">
			<select name="interval_type" class="cf7_mollie_interval_type">
			  <option value="days">'.__("Days", 'cf7-mollie-translation').'</option>
			  <option value="weeks">'.__("Weeks", 'cf7-mollie-translation').'</option>
			  <option value="months" selected>'.__("Months", 'cf7-mollie-translation').'</option>
			</select>
			<br>
			'.__("Times:", 'cf7-mollie-translation').'<br>
			<input type="number" name="times" class="cf7_mollie_times"><br>
			'.__("Start date", 'cf7-mollie-translation').'<br>
			<input type="date" name="startdate" id="startdate" class="cf7_mollie_startdate"   min="'.date('Y-m-d').'"><br>
			'.__("OrderID (Optional):", 'cf7-mollie-translation').'<br>
			<input type="text" name="order_id" class="cf7_mollie_order_id"><br>
			'.__("Webhook URL:", 'cf7-mollie-translation').'<br>
			'.home_url().'/<input type="text" name="webhook" class="cf7_mollie_webhook"><br>
			<br>
			<input type="submit" value="'.__("Submit", 'cf7-mollie-translation').'" class="button">
		</form> 
	</div>';
	
	echo $html;
	
	wp_die();			
}

function cf7mollie_process_payment_update(){
	require __DIR__ . '/initialize.php';
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	cf7_mollie_setapikey();
    global $mollie;
	
	$row_id = sanitize_text_field($_POST['row_id']);
	$name = sanitize_text_field($_POST['cf7_mollie_name']);
	$email = sanitize_email($_POST['cf7_mollie_email']);
	$amount = sanitize_text_field($_POST['cf7_mollie_amount']);
	$times = sanitize_text_field($_POST['cf7_mollie_times']);
	$startdate = sanitize_text_field($_POST['cf7_mollie_startdate']);
	$description = sanitize_text_field($_POST['cf7_mollie_description']);
	$interval = sanitize_text_field($_POST['cf7_mollie_interval']);
	$interval_type = sanitize_text_field($_POST['cf7_mollie_interval_type']);
	
	echo "test";
	echo $interval;
	echo $interval_type;
	echo "test";
	
	try{
		//Update Mollie
		$query = "SELECT * FROM {$table_name} WHERE id = '{$row_id}'";
		$result = json_decode(json_encode($wpdb->get_results($query)), True);
		$customer = $mollie->customers->get($result[0]['customerid']);
		$customer->name = "{$name}";
		$customer->email = "{$email}";
		$customer->update();
		$subscription = $customer->getSubscription($result[0]['subscriptionid']);
		$subscription->amount = (object) [
			  "currency" => "EUR",
			  "value" => "{$amount}",
		];
		$subscription->times = "{$times}";
		$subscription->interval = "{$interval} {$interval_type}";
		
		if ($startdate >= date('Y-m-d') and $startdate != ""){
			$subscription->startDate = "{$startdate}";
		}else{
			$startdate = $subscription->startDate;
			$subscription->startDate = null;
		}
		$subscription->description = "{$description}";
		$updatedSubscription = $subscription->update();
		
		//Update Database
		$wpdb->query($wpdb->prepare("UPDATE {$table_name} SET amount='%s', name='{$name}', email='{$email}', times='{$times}', startdate= '{$startdate}', description='{$description}' WHERE orderid='{$result[0]['orderid']}'",$amount));
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "Admin menu 3: API call failed: " . htmlspecialchars($e->getMessage());
	}
}

function cf7mollie_process_mandate(){
	require __DIR__ . '/initialize.php';
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	cf7_mollie_setapikey();
    global $mollie;
	
	try{
		$name = sanitize_text_field($_POST['name']);
		$bankname = sanitize_text_field($_POST['bankname']);
		$email = sanitize_email($_POST['email']);
		$iban = sanitize_text_field($_POST['iban']);
		$amount = str_replace(',', '.', $_POST['amount']);
		$amount = number_format($amount,2,".","");
		$amount = sanitize_text_field($amount);
		$times = sanitize_text_field($_POST['times']);
		$interval = sanitize_text_field($_POST['interval']);
		$interval_type = sanitize_text_field($_POST['interval_type']);
		$startdate = sanitize_text_field($_POST['startdate']);
		if ($startdate == null or $startdate == "0000-00-00"){
			$startdate = date('Y-m-d');
		}
		$signaturedate = sanitize_text_field($_POST['signaturedate']);
		$description = sanitize_text_field($_POST['description']);
		$order_id =! empty($_POST["order_id"]) ? $_POST["order_id"] : time();
		$webhook = esc_url_raw($_POST['webhook']);
		
		//Create Mollie customer
		$customer = $mollie->customers->create([
		  "name" => "{$name}",
		  "email" => "{$email}",
		]);
		$customerid = $customer->id;
	
		//Create Mollie mandate
		$mandate = $mollie->customers->get($customer->id)->createMandate([
		   "method" => \Mollie\Api\Types\MandateMethod::DIRECTDEBIT,
		   "consumerName" => "{$bankname}",
		   "consumerAccount" => "{$iban}",
		   "signatureDate" => "{$signaturedate}",
		]);
		
		if ($amount != ""){
			//Create Mollie subscription
			$customer->createSubscription([
			   "amount" => [
					 "currency" => "EUR",
					 "value" => "{$amount}",
			   ],
			   "interval" => "{$interval} {$interval_type}",
			   "startDate" => "{$startdate}",
			   "metadata" => [
					"order_id" => "{$order_id}",
				],
			   "description" => "{$description}",
			   "webhookUrl" => "{$webhook}",
			]);
			
			$subscriptions = $customer->subscriptions();
			$subscriptionId = $subscriptions[0]->id;
			$subscription = $customer->getSubscription($subscriptionId);
			if ($times != ""){
				$subscription->times = "{$times}";
			}
		}
				
		//Update Database
		$result = $wpdb->query($wpdb->prepare("UPDATE {$table_name} SET amount='%s', name='{$name}', email='{$email}', times='{$times}', startdate='{$startdate}', description='{$description}' WHERE orderid='{$order_id}'",$amount));

		if ($result==0){
			$wpdb->insert( 
				$table_name,
				array( 
					'time' => current_time( 'mysql' ), 
					'orderid' => "{$order_id}",
					'amount' => "{$amount}",
					'description' => "{$description}",
					'customerid' => "{$customerid}",
					'name' => "{$name}",
					'email' => "{$email}",
					'frequency'=>"{$interval} {$interval_type}",
					'times' => "{$times}",
					'subscriptionid' => "{$subscriptionId}",
					'startdate' => "{$startdate}"
				)
			);
		}
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "Admin menu 4: API call failed: " . htmlspecialchars($e->getMessage());
	}
}

function cf7_mollie_show_payments(){
	require __DIR__ . '/initialize.php';
	global $wpdb;
	$table_name = $wpdb->prefix . 'cf7_mollie';
	cf7_mollie_setapikey();
    global $mollie;
	
	$customer = sanitize_text_field($_GET['customerid']);
	
	try{
		$payments = $mollie->customers->get("{$customer}")->payments();
		$name = $mollie->customers->get("{$customer}")->name;
		global $wp;
		$current_url = add_query_arg( $wp->query_vars, home_url( $wp->request ) );
		//echo '<pre>'; print_r($payments); echo '</pre>';
		
		$html .= '<div id="cf7-mollie-client-payments-overview-wrapper">
					<h4>'.__("Payments for customer", 'cf7-mollie-translation')." ".$name.'</h4>
					<table class="cf7-mollie-client-payments-overview-table">
						<thead class="cf7-mollie-payments-head">
							<tr>
								<th>'.__("Date", 'cf7-mollie-translation').'</th>
								<th>'.__("Amount", 'cf7-mollie-translation').'</th>
								<th>'.__("Status", 'cf7-mollie-translation').'</th>
							</tr>
						</thead>
					<tbody class="cf7-mollie-payments-body">';
		
		$i = 0;
		foreach ($payments as $payment) {
			//echo '<pre>'; print_r($payment); echo '</pre>';
			$i++;
			$html .= '<tr class="cf7-mollie-payments-row-'.$i.'">
						<td>'.date('d-m-Y',strtotime(substr($payment->createdAt, 0, 10))).'</td>
						<td>'.$payment->amount->value.'</td>
						<td>'.__($payment->status, 'cf7-mollie-translation').'</td>
					</tr>';
		}
		
		$html .= '</tbody>
			</table>
			<br>
			<a href="'.str_replace ("customerid=".$_GET['customerid'],"",$current_url).'">'.__( 'Back to main page', 'cf7-mollie-translation').'</a>
		</div>';
					
		return $html;		
	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "Admin menu 5: API call failed: " . htmlspecialchars($e->getMessage());
	}
	
}

function cf7_mollie_edit_recurring_payment($DatabaseID=""){
	global $mollie;
	cf7_mollie_setapikey();
	global $wpdb;
	global $table_name;
		
	//Retrieve information from database
	if ($DatabaseID == ""){
		$DatabaseID = !empty($_GET["payment"]) ? $_GET["payment"] : "1";
	}
	$query = "SELECT * FROM {$table_name} WHERE `id` =".$DatabaseID;
	$result = json_decode(json_encode($wpdb->get_results($query)), True);
	//Retrieve information from Mollie
	try{
		$customer = $mollie->customers->get("{$result[0]['customerid']}");
		$name = $customer->name;
		$email = $customer->email;
		$subscription = $customer->getSubscription("{$result[0]['subscriptionid']}");
		$amount = $subscription->amount->value;
		$frequency_string = $subscription->interval;
		$frequency = explode (" ",$frequency_string)[0];
		$frequency_type = explode (" ",$frequency_string)[1];

	} catch (\Mollie\Api\Exceptions\ApiException $e) {
		echo "Admin menu 2: API call failed: " . htmlspecialchars($e->getMessage());
	}
	
	global $wp;			
	$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
	
	if ($_GET['page'] == "wpcf7-mollie-config"){
		$current_url .= "/wp-admin/admin.php?page=wpcf7-mollie-config";
	}else{
		$current_url = "/test/?page_id=930";
	}
	
	$html = '
	<div class="cf7_mollie_update_payment">
		<div id="icon-users" class="icon32"><br/></div>
		<h2>'.__("Edit values", 'cf7-mollie-translation').'</h2>'.
		__("The values below are retrieved from Mollie.", 'cf7-mollie-translation').'<br>'.
		__("All changed values will also be changed at the side of Mollie.", 'cf7-mollie-translation').'<br><br>
		<form action="'.$current_url.'" method="post">
			<input type="hidden" name="row_id" class="row_id" value="'.$DatabaseID.'">'.
			__("Name", 'cf7-mollie-translation').':<br>
			<input type="text" name="cf7_mollie_name" class="cf7_mollie_name" value="'.$name .'"><br>'.
			__("E-mail", 'cf7-mollie-translation').':<br>
			<input type="text" name="cf7_mollie_email" class="cf7_mollie_email" value="'. $email .'"><br>'.
			__("Amount", 'cf7-mollie-translation').':<br>
			<input type="text" name="cf7_mollie_amount" class="cf7_mollie_amount" value="'. $amount .'"><br>'.
			__("Description", 'cf7-mollie-translation').':<br>
			<input type="text" name="cf7_mollie_description" class="cf7_mollie_description" value="'. $subscription->description  .'"><br>'.
			__("Times", 'cf7-mollie-translation').':<br>
			<input type="number" name="cf7_mollie_times" class="cf7_mollie_times" value="'.$subscription->times  .'"><br>'.
			__("Frequency", 'cf7-mollie-translation').':<br>
			<input type="number" name="cf7_mollie_interval" class="cf7_mollie_interval" value="'.$frequency.'">
			<select name="cf7_mollie_interval_type" class="cf7_mollie_interval_type">';
	
	foreach(["months","weeks","days"] as $value){
		if ($value == $frequency_type){
			$html .= '<option value="'.$value.'" selected="selected">'.__($value, 'cf7-mollie-translation').'</option>';
		}else{
			$html .= '<option value="'.$value.'">'.__($value, 'cf7-mollie-translation').'</option>';
		}
	}
	$html .= '</select><br>';
			
			
	if ($subscription->startDate >= date('Y-m-d')){
		$html .= __("Start date", 'cf7-mollie-translation').'<br>
			<input type="date" name="cf7_mollie_startdate" id="startdate" class="cf7_mollie_startdate" value="'. $subscription->startDate .'"   min="'. date('Y-m-d').'">';
	}
	
	$html .= '<br><br><br>
			<input type="submit" value="'.__("Submit", 'cf7-mollie-translation').'" class="button">
		</form> 
	</div>';
	
	return $html;
}

function cf7_mollie_delete_recurring_payment($ID=""){
	global $mollie;
	cf7_mollie_setapikey();
	global $wpdb;
	global $table_name;
	
	$DatabaseID = $ID;
	
	if ($DatabaseID == ""){
		$DatabaseID = !empty($_GET["payment"]) ? $_GET["payment"] : "1";
	}
	$confirm = !empty($_GET["confirm"]) ? $_GET["confirm"] : "";
	$confirm = sanitize_text_field($confirm);
	
	$subscription_count = 0;
	foreach ($DatabaseID as $ID){
		//Retrieve information from database	
		$query = "SELECT * FROM {$table_name} WHERE `id` =".$ID;
		$result = json_decode(json_encode($wpdb->get_results($query)), True);
	
		if ($result[0]['subscriptionid'] != ""){
			$subscription_count++;
		}else{
			$wpdb->delete( $table_name, array( 'id' => $ID ));
		}
		
		if ($result[0]['subscriptionid'] != ""){					
			if ($confirm=="true"){
				try{
					$customer = $mollie->customers->get("{$result[0]['customerid']}");
					$subscription = $customer->cancelSubscription("{$result[0]['subscriptionid']}");
				} catch (\Mollie\Api\Exceptions\ApiException $e) {
					echo "Admin menu 1: API call failed: " . htmlspecialchars($e->getMessage());
				}
			}
			if ($confirm!=""){
				$wpdb->delete( $table_name, array( 'id' => $ID ));
			}
		}
	}
	
	if ($subscription_count != 0 and $confirm==""){
		if ($ID==""){
			$parameter = "confirm=";
		}else{
			$parameter = "payment[0]={$ID}&confirm=";
		}
		?>
		<div class="cf7_mollie_delete_payment">
			<div id="icon-users" class="icon32"><br/></div>
			<h2><?php __("Confirm subscription cancellation", 'cf7-mollie-translation') ?></h2>
			<br>
			<font size="3">
				<?php
				if (count ($DatabaseID)==1){
					echo sprintf(__("Are you sure to cancel the subscription with id '%s'?", 'cf7-mollie-translation'),$result[0]['subscriptionid']); 
					?><br><br>
				<?php }else{
					echo sprintf(__("Are you sure to cancel all %s subscriptions?", 'cf7-mollie-translation'),$subscription_count); ?> <br><br>
				<?php } ?>
				<button id="yes" onclick="get_url('<?php echo $parameter; ?>true')" class="button"> 
					<?php _e("Yes", 'cf7-mollie-translation') ?> 
				</button>
				<button id="no" onclick="get_url('<?php echo $parameter; ?>false')" class="button">
					<?php _e("No", 'cf7-mollie-translation') ?> 
				</button>
			</font>
		</div>
		<script>
			function get_url(a) {
				_url = location.href;
				_url += ((_url.split('?').length==2) ? '&':'?') + a;
				location.href = _url;
			}
		</script>

		<?php
	}else{
		if ($confirm=="true"){
			_e("Items deleted scuccesfully from mollie and local database", 'cf7-mollie-translation');
		}else{
			_e("Items deleted scuccesfully from local database only.", 'cf7-mollie-translation');
		}
		
		global $wp;			
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
		header("Location: ".$current_url);

		//Redirect to main screen after 3 seconds
		echo "
			<script type=\"text/javascript\">
			setTimeout(function(){
				window.location = window.location.href.split('?')[0]+'?page=wpcf7-mollie-config';
			 }, 3000);
			</script>
		";
	}
	return $html;
}