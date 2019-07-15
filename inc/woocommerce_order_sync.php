<?php

/*---------------------------------------------------------*/
/*---- Build an array of Items that have been ordered -----*/
/*---------------------------------------------------------*/
function build_array_of_ordered_items( $item ) {

	// Declare an array to populate with item information
	$item_array = array();

	// Get the SKU for the item, using the variation_id
	$sku = get_post_meta( $item['variation_id'], '_sku', true );
	// Get the quantity of the item sold from the item object
	$quantity = $item['quantity'];
	// Get the name of the item sold from the item object
	$name = $item['name'];
	// Get the total cost of the item sold from the item object
	$total = $item['total'];

	// Add these variables to the item_array
	$item_array['SKUSize'] = $sku;
	// $item_array['Article'] = $name;
	$item_array['Quantity'] = $quantity;
	$item_array['Item_Price'] = floatval($total / $quantity);
	$item_array['Line_Total'] = floatval($total);

	// Return the result of the array
	return $item_array;

}


/*---------------------------------------------------------*/
/*---- POST Order Information to the i.LEVEL API ----------*/
/*---------------------------------------------------------*/
function post_order( $login, $json ) {

	// Get url from database
	$domain = $login['domain'];
	// Get username from database
	$username = $login['username'];
	// Get password from database
	$password = $login['pass'];

	// Query the i.LEVEL API for Stock information
	$order = post_to_ilevel( $domain.'/api/retailorder', $username, $password, $json );

	// Check if the response has any stock information
	if ( $order ) {

		return true;

	}

}


/*---------------------------------------------------------*/
/*---- POST to the i.LEVEL API ----------------------------*/
/*---------------------------------------------------------*/
function post_to_ilevel($url, $user, $password, $body) {

	// Set the arguments for the query
	$args = array(
		'method' => 'POST',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $user . ':' . $password )
			),
		'httpversion' => '1.0',
        'sslverify' => true,
        'body' => $body
		);

	// Make the query, using the URL and arguments
	$response = wp_remote_get( $url, $args );

	// If the query returns an error, exit early
	if( is_wp_error( $response ) ) {
		return false;
	}

	// Retrieve the body from the returned data
	$response_body = wp_remote_retrieve_body( $response );

	// Decode the response_body so we can access its values
	// $json = json_decode($response_body, true);

	// Log the body in the error log
	// error_log('response_status: '.$json['response_status']);
	// error_log('type: '.gettype($response_body));

	wp_mail('michael@tyrell.mobi', 'i.LEVEL RetailOrder Response', $response_body);

	// Return the response body
	return $body;

}

/*----------------------------------------------------------------*/
/*-- Send WooCommerce order data to i.LEVEL API ------------------*/
/*----------------------------------------------------------------*/

// Just record the order and item data to debug log for now
function wisync_order_complete( $order_id, $status ) {

	global $wpdb;
	// Set the table name we're using
	$table_name = $wpdb->prefix . 'wisync';

	// Get the order using the order_id
	$order = new WC_Order( $order_id );

	// Get the order data out of the object as an associative array
	$order_data = $order->get_data();

	// Make an array to store the order data we need for i.LEVEL
	$order_array = array();

	// Set the Customer_id (Customer id)
	$order_array['Customer_id'] = $order->get_customer_id();
	// Set the Warehouse_id (Warehouse id indicates the warehouse from which the stock for the order should be taken. Default 0 for main warehouse. Use multiplewarehouse resource (if active on your system) for details of available warehouses.)
	$order_array['Warehouse_id'] = 0;
	// Set the Order_Ref (Order reference can be passed in. If passed it will be used with the Order_Source to check uniqueness)
	$order_array['Order_Ref'] = $order_data['order_key'];
	// Set the Order_source (Order source)
	// $order_array['Order_Source'] = get_site_url();
	$order_array['Order_Source'] = 'WooCommerce';
	// Set the Order_Date (Creation date of the order)
	// $order_array['Order_Date'] = $order->get_date_completed()->format('Y-m-d H:i:s');
	$order_array['Order_Date'] = $order->get_date_completed();
	// Set the Marketplace_Status (Web/marketplace order status)
	$order_array['Marketplace_Status'] = $order->get_status();
	// Set the Name (Delivery customer name)
	$order_array['Name'] = $order->get_shipping_first_name() .' '. $order->get_shipping_last_name();
	// Set the Address1 (Delivery address line 1)
	$order_array['Address1'] = $order->get_shipping_address_1();
	// Set the Address2 (Delivery address line 2)
	$order_array['Address2'] = $order->get_shipping_address_2();
	// Set the Town (Delivery address line 3)
	$order_array['Town'] = $order->get_shipping_city();
	// Set the County (Delivery address line 4)
	$order_array['County'] = $order->get_shipping_state();
	// Set the Postcode (Delivery postcode)
	$order_array['Postcode'] = $order->get_shipping_postcode();
	// Set the Gross (Total order value including Taxes and delivery)
	$order_array['Gross'] = floatval( $order->get_subtotal() );
	// $order_array['Gross'] = floatval( $order->get_total() - $order->get_shipping_total() );
	// Set the VAT (Total VAT value for the order)
	$order_array['VAT'] = floatval( $order->get_total_tax() );
	// Set the VAT_Code (VAT code)
	$order_array['VAT_Code'] = $order->get_taxes();
	// Set the currency (Order currency code)
	$order_array['Currency'] = $order->get_currency();
	// Set the Country (Delivery address country code)
	$order_array['Country_Code'] = $order->get_shipping_country();

	/*---------------------*/
	/*-- Handle Customer --*/
	/*---------------------*/

	// Get the customer's billing details in the order
	$order_array['RetailCustomer']['Forename'] = $order->get_billing_first_name();
	$order_array['RetailCustomer']['Surname'] = $order->get_billing_last_name();
	$order_array['RetailCustomer']['Company_Name'] = $order->get_billing_company();
	$order_array['RetailCustomer']['Address1'] = $order->get_billing_address_1();
	$order_array['RetailCustomer']['Address2'] = $order->get_billing_address_2();
	$order_array['RetailCustomer']['Town'] = $order->get_billing_city();
	$order_array['RetailCustomer']['County'] = $order->get_billing_state();
	$order_array['RetailCustomer']['Country_Code'] = $order->get_billing_country();
	$order_array['RetailCustomer']['Postcode'] = $order->get_billing_postcode();
	$order_array['RetailCustomer']['Telephone'] = $order->get_billing_phone();
	$order_array['RetailCustomer']['Email'] = $order->get_billing_email();
	$order_array['RetailCustomer']['Comments'] = $order->get_customer_note();
	$order_array['RetailCustomer']['Accounts_Ref'] = $order->get_transaction_id();

	/*------------------*/
	/*-- Handle Items --*/
	/*------------------*/

	// Make an array to store order items
	$items_array = array();

	// Get the items in the order
	$items = $order->get_items();

	foreach ( $items as $item ) {

		// Pass the item to be converted into a JSON object
		$item_json = build_array_of_ordered_items( $item );

		// Push the json object to the items_array
		array_push( $items_array, $item_json);

	}

	// Add the items array to the order array
	$order_array['Items'] = $items_array;

	// JSON encode the order_array
	$order_json = json_encode($order_array);

	// Query the database to get API login details
	$login = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1", ARRAY_A );

	$order_upload = post_order( $login, $order_json );

	if ( $order_upload ) {

		wisync_admin_notice__success( 'Order recorded at i.LEVEL' );

	}

}
