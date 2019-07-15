<?php

require_once "inc/woocommerce_queries.php";
require_once "inc/ilevel_connection.php";
require_once "inc/general_functions.php";
require_once "inc/woocommerce_product_functions.php";
require_once "inc/taxonomy_functions.php";

/*--------------------------------------------------------------------*/
/*-- The Sync Function -----------------------------------------------*/
/*-- (to be triggered upon a Manual Sync or at hourly intervals) -----*/
/*--------------------------------------------------------------------*/

function sync() {

	// Get an array of SKUs that already exist in WooCommerce
	$woocommerce_skus = get_all_woocommerce_skus();

	// Get i.LEVEL API details from WP database
	$login = get_ilevel_details();
	// Get each fragment of the login details from the response
	$domain = $login['domain'];
	$username = $login['username'];
	$password = $login['pass'];
	$process_option = $login['process_option'];

	// Query the i.LEVEL API for Stock information
	// for now we'll just count how many products there are and establish what variations/attributes we need
	/*----------------------------------------------------------------------------------------------------------------------------*/
	// THIS URL NEEDS THE STYLE VALUE REMOVING (THIS IS ONLY THERE FOR DEVELOPMENT PURPOSES TO RESTRICT TO A SINGLE PRODUCT FAMILY)
	/*----------------------------------------------------------------------------------------------------------------------------*/
	//This is the query for a VARIABLE PRODUCT WITH MULTIPLE SIZE AND COLOUR ATTRIBUTES
	// $ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&pagesize=1000&style=W18SS203', $username, $password);
	//This is the query for a SIMPLE PRODUCT
	// $ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&pagesize=1000&style=W18AW704', $username, $password);
	//This is the query for ALL PRODUCTS
	$ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&pagesize=1000', $username, $password);
	// Decode the returned response from i.LEVEL
	$retail = json_decode($ilevel_response, true);
	// Get the number of total records contained within the response
	$total_records = $retail['response']['RecordsFound'];
	// Get the current page of data
	$current_page = $retail['response']['Page'];
	// Get the number of total pages of data we need to return in total
	$total_pages = $retail['response']['PageCount'];

	/*----------------------------------------------------------------------------------------------------------------------------*/
	// Let's deal with all the attributes we're going to need for Products first
	/*----------------------------------------------------------------------------------------------------------------------------*/

	// First check that 'Colour' and 'Size' are set as product attributes in WooCommerce
	$colour_exists = check_for_product_attribute( 'colour' );
	$size_exists = check_for_product_attribute( 'size' );

	// If either of these product attributes do not exist, create them
	if ( !$colour_exists ) {
		$colour_created = create_product_attribute( 'Colour' );
	}
	if ( !$size_exists ) {
		$size_created = create_product_attribute( 'Size' );
	}

	/*----------------------------------------------------------------------------------------------------------------------------*/
	// Let's map out which items are being update and which are being created
	/*----------------------------------------------------------------------------------------------------------------------------*/

	// Set an increment counter to 0, so we can iterate through Products in the i.LEVEL response data
	$i = 0;
	// Declare a map array so we can divide i.LEVEL Products into 'Create' or 'Update'
	$map = array();
	$map['create'] = array();
	$map['update'] = array();
	$map['inventory'] = array();

	// Now for each of the products in the i.LEVEL API, we need to:
	// 1. check if there are variations or not (to determine if it's a Simple or Variable Product)
	// 2. check if it already exists in WooCommerce (if yes, update it, otherwise create it)
	// 3. get their Size and Colour values and check they exist as Attribute Terms in WooCommerce
	// 4. 

	while ( $i < $total_records ) {

		// First, let's find out if this is a Simple or Variable Product
		$sku_count = check_if_product_has_variations( $retail, $retail['response']['Stock'][$i] );

		// If the $sku_count is greater than 1, it is a Variable Product, otherwise it's a Simple Product
		if ( $sku_count > 1 ) {
			$product_type = 'Variable';
		} elseif ( $sku_count == 1 ) {
			$product_type = 'Simple';
		}

		// Set the SKUSize and Style values to variables
		$sku = $retail['response']['Stock'][$i]['SKU'];
		$skusize = $retail['response']['Stock'][$i]['SKUSize'];
		$style = $retail['response']['Stock'][$i]['Style'];

		// Check whether this item's Style exists in the array of WooCommerce SKUs
		if ( in_array( $style, $woocommerce_skus ) ) {

			// If it exists, check whether it has already been added to the Update map
			if ( !in_array( $style, $map['update'] ) ) {
				// If not, add the Product Family reference to the update map
				$map['update'][$i] = $style;
			}

		} else {

			// If it doesn't exist, check whether it has already been added to the Create map
			if ( !in_array( $style, $map['create']) ) {
				// If not, add the Product Family reference to the Create map
				$map['create'][$i] = $style;
			}

		}

		// Add all SKUSize values to the inventory map
		$map['inventory'][$i] = $skusize;

		// // Set the Colour and Size values to variables
		// $term_colour = $retail['response']['Stock'][$i]['Colour'];
		// $term_size = $retail['response']['Stock'][$i]['Size'];
		// // Check whether these values exist in WooCommerce
		// $term_colour_exists = check_for_attribute_term( $term_colour );
		// $term_size_exists = check_for_attribute_term( $term_size );
		// // If they don't exist, create them
		// if ( !$term_colour_exists ) {
		// 	// if ( check_for_product_attribute( 'colour' ) ) {
		// 		create_product_attribute_term( $term_colour, 'pa_colour' );
		// 	// }
		// }
		// if ( !$term_size_exists ) {
		// 	// if ( check_for_product_attribute( 'size' ) ) {
		// 		create_product_attribute_term( $term_size, 'pa_size' );
		// 	// }
		// }
		
		$i++;
	}

	if ( $process_option == 'product-creation' ) {

		/*----------------------------------------------------------------------------------------------------------------------------*/
		// Let's Create Non-existing Items in WooCommerce
		/*----------------------------------------------------------------------------------------------------------------------------*/
		if( array_key_exists( 'create', $map ) ) {
			foreach ( $map['create'] as $style ) {
				create_product_loop( $login, $style, $woocommerce_skus );
			}
		}

		/*----------------------------------------------------------------------------------------------------------------------------*/
		// Let's Update Existing Items in WooCommerce
		/*----------------------------------------------------------------------------------------------------------------------------*/
		if( array_key_exists( 'update', $map ) ) {
			foreach ( $map['update'] as $style ) {
				update_product_loop( $login, $style );
			}
		}

	}

	if ( $process_option == 'inventory-sync' ) {
		reset_all_stock_status( '_stock_status', 'outofstock');
		foreach ( $map['inventory'] as $skusize ) {
			inventory_sync( $login, $skusize, $woocommerce_skus );
		}
	}

}