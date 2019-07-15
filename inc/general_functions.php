<?php

/*---------------------------------------------------------*/
/*------- Check whether i.LEVEL product has variations ----*/
/*---------------------------------------------------------*/
function check_if_product_has_variations( $array, $product ) {
	// Set a counter to 0 so we can increment it when we find a matching SKU
	$sku_count = 0;

	// Loop through the array of all stock items, looking for this exact SKU
	foreach ( $array['response']['Stock'] as $key => $value ) {

		// If we find this SKU, increment the counter
		if ( $value['SKU'] == $product['SKU'] ) {
			$sku_count++;
		}

	}

	return $sku_count;
}


/*-------------------------------------------------------------------*/
/*-- Build an array of WooCommerce Products from i.LEVEL response ---*/
/*-------------------------------------------------------------------*/
function build_products_array_from_ilevel_data( $stock ) {

	// Declare an array to save the values
	$products = array();
	// Declare an array to save the attributes
	// $colours_array = array();
	// $sizes_array = array();

	// Loop through all the various stock items, adding sizes and colours to respective arrays if they're not already in there
	// foreach ( $stock as $item ) {

	// 	if ( !in_array( $item['Colour'], $colours_array ) ) {
	// 		$colours_array[] = $item['Colour'];
	// 	}
	// 	if ( !in_array( $item['Size'], $sizes_array ) ) {
	// 		$sizes_array[$item['Size_Sort']] = $item['Size'];
	// 	}

	// }

	// Check if the attributes we need already exist, and if not, create them
	// if ( !check_for_product_attribute( 'Size' ) ) {
	// 	create_product_attribute( 'Size' );
	// 	}
	// if ( !check_for_product_attribute( 'Colour' ) ) {
	// 	create_product_attribute( 'Colour' );
	// }

	// Check if the attribute terms we need already exist, and if not, create them
	// foreach ( $colours_array as $colour ) {
	// 	if ( !check_for_attribute_term( $colour ) ) {
	// 		// Create it
	// 		wp_insert_term( $colour, 'pa_colour' );
	// 	}
	// }
	// foreach ( $sizes_array as $size ) {
	// 	if ( !check_for_attribute_term( $size ) ) {
	// 		// Create it
	// 		wp_insert_term( $size, 'pa_size' );
	// 	}
	// }

	// Add the colour and size arrays to the attribute array
	// $attributes = array( 'Colours'=>$colours_array, 'Sizes'=>$sizes_array );


	// print_r($attributes);

	$i = 0;
	foreach ( $stock as $item ) {

		$product = array();


		// Set the Family
		$product['parent'] = $item['Style'];
		// Set the SKU
		$product['sku'] = $item['SKU'];
		// Set the SKUSize
		$product['skusize'] = $item['SKUSize'];
		// Set the Name
		$product['name'] = $item['Article'];
		// Set the Brand Name
		$product['description'] = $item['Web_Description'];
		// Set the Product Group
		$product['product_group'] = $item['Product_Group'];
		// Set the Standard Price
		$product['price'] = $item['Retail_Price_Own_Currency'];
		// Set the Sale Price
// 		$product['sale_price'] = $item['Sell_Price1'];
		$product['sale_price'] = $item['Retail_Price_Own_Currency'];
		// Set the Colour
		$product['colour'] = $item['Colour'];
		// Set the Size
		$product['size'] = $item['Size'];
		// Set the Stock Count
		$product['stock'] = $item['Available'];

		$products[$i] = $product;

		$i++;

	}

	// Return the completed array
	return $products;
}


/*-----------------------------------------------------------------------*/
/*-- Build a JSON object from the i.LEVEL API response object -----------*/
/*-----------------------------------------------------------------------*/
function build_inventory_json($array) {

	// Decode the JSON object we've passed in
	$contents = json_decode($array, true);
	// Create a new array to contain the re-assembled data
	$query_array = array();

	// Loop through the stock array
	foreach ($contents as $array) {

		// Loop through each 'Stock' item within the array
		foreach ($array['Stock'] as $stock_array) {

			// Where the key is 'SKUSize', set its value to the 'key' variable
			$key = $stock_array['SKUSize'];

			// Where the key is 'Available', set its value to the 'value' variable
			$value = $stock_array['Available'];

			// Add both the 'key' and 'value' to the new array
			$query_array[$key] = $value;

		}

	}

	// Check that the new array contains data
	if ( !empty($query_array) ) {

		// Return the array of re-assembled data ('SKUSize':'Availability','SKUSize':'Availability','SKUSize':'Availability')
		return json_encode($query_array);

	} else {

		return false;

	}

}