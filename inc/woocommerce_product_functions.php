<?php

/*---------------------------------------------------------*/
/*-- The Loop we'll pass the $map['create'] through -------*/
/*---------------------------------------------------------*/
function create_product_loop( $login, $style, $woocommerce_skus ) {

	// First, extract API login details to use in the query URL
	$domain = $login['domain'];
	$username = $login['username'];
	$password = $login['pass'];

	// Query the API again, specifying the family of product (using the $style value)
	$ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&style='.$style, $username, $password);
	// Decode the response
	$family = json_decode( $ilevel_response, true );

	// Make an array of all products returned by this query (we could have several, as we have retrieved a whole 'Style' of product)
	$products_array = build_products_array_from_ilevel_data( $family['response']['Stock'] );
	// Count how many items are in this array (to determine whether it's a Simple or a Variable product)
	$variation_count = sizeof( $products_array );
	// Set a variable that declares the product_type as simple (we'll change this if there is more than one product in the array)
	$product_type = 'simple';
	// If there's more than 1 product in the array, set product_type to 'variable'
	if ( $variation_count > 1 ) {
		$product_type = 'variable';
	}

	// We need to assemble an array of colour and size attributes to add to the parent product
	$colours_array = array();
	$sizes_array = array();

	$colour_increment = 0;
	foreach ( $products_array as $product ) {
		$colours_array[$colour_increment] = $product['colour'];
		// Check whether this Colour exists as an Atribute Term in WooCommerce
		$term_colour_exists = check_for_attribute_term( $product['colour'] );
		// If it doesn't yet exist, create it
		if ( !$term_colour_exists ) {
				create_product_attribute_term( $product['colour'], 'pa_colour' );
		}
		$colour_increment++;
	}
	$size_increment = 0;
	foreach ( $products_array as $product ) {
		$sizes_array[$size_increment] = $product['size'];
		// Check whether this Size exists as an Atribute Term in WooCommerce
		$term_size_exists = check_for_attribute_term( $product['size'] );
		// If it doesn't yet exist, create it
		if ( !$term_size_exists ) {
				create_product_attribute_term( $product['size'], 'pa_size' );
		}
		$size_increment++;
	}

	// Set a counter so we can determine the first variation
	$i = 0;

	// First, check whether the main product already exists
	if ( !in_array( $products_array[$i]['parent'], $woocommerce_skus ) ) {
		// If it doesn't exist in WooCommerce, create it
		$post_parent = create_woocommerce_product( $products_array[$i], $product_type, $colours_array, $sizes_array );
	} else {
		$post_parent = get_post_id_from_sku( $products_array[$i]['parent'] );
	}

	if ( $variation_count > 1 ) {

		while ( $i < $variation_count ) {

			// For each item in the $products_array
			foreach ( $products_array as $variation ) {

				// Declare a variable to store our success/error notice
				// $notice = NULL;

				// Check whether the parent product exists
				if ( $post_parent ) {
					// Check whether the Variation already exists
					if ( !in_array( $variation['skusize'], $woocommerce_skus ) ) {
						create_woocommerce_product_variation( $i, $products_array[$i], $post_parent );
					}
				}

				// Increment the counter
				$i++;

			}

		}

	}
}



/*---------------------------------------------------------*/
/*-- The Loop we'll pass the $map['create'] through -------*/
/*---------------------------------------------------------*/
function update_product_loop( $login, $style ) {

	// First, extract API login details to use in the query URL
	$domain = $login['domain'];
	$username = $login['username'];
	$password = $login['pass'];

	// Query the API again, specifying the family of product (using the $style value)
	$ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&style='.$style, $username, $password);
	// Decode the response
	$family = json_decode( $ilevel_response, true );

	// Make an array of all products returned by this query (we could have several, as we have retrieved a whole 'Style' of product)
	$products_array = build_products_array_from_ilevel_data( $family['response']['Stock'] );
	// Count how many items are in this array (to determine whether it's a Simple or a Variable product)
	$variation_count = sizeof( $products_array );
	// Set a variable that declares the product_type as simple (we'll change this if there is more than one product in the array)
	$product_type = 'simple';
	// If there's more than 1 product in the array, set product_type to 'variable'
	if ( $variation_count > 1 ) {
		$product_type = 'variable';
	}

	// We need to assemble an array of colour and size attributes to add to the parent product
	$colours_array = array();
	$sizes_array = array();

	$colour_increment = 0;
	foreach ( $products_array as $product ) {
		$colours_array[$colour_increment] = $product['colour'];
		$colour_increment++;
	}
	$size_increment = 0;
	foreach ( $products_array as $product ) {
		$sizes_array[$size_increment] = $product['size'];
		$size_increment++;
	}

	// Set a counter so we can determine the first variation
	$i = 0;

	// First, get the post_id for the main/parent Product
	$post_id = get_post_id_from_sku( $product['parent'] );

	$post_parent = update_woocommerce_product( $post_id, $products_array[$i], $product_type, $colours_array, $sizes_array, $post_id );

	if ( $variation_count > 1 ) {

		while ( $i < $variation_count ) {

			// For each item in the $products_array
			foreach ( $products_array as $variation ) {

				// Get an up-to-date array of all SKUs in WooCommerce
				$woocommerce_skus = get_all_woocommerce_skus();

				// Check whether the Variation already exists
				if ( !in_array( $variation['skusize'], $woocommerce_skus ) ) {
					// If it doesn't exist, create it
					create_woocommerce_product_variation( $i, $products_array[$i], $post_parent );
				} else {
					// Get the post_id for this variation
					$variation_id = get_post_id_from_sku( $variation['skusize'] );
					// If it already exists, update it
					update_woocommerce_product_variation( $variation_id, $i, $products_array[$i], $post_parent );
				}

				// Increment the counter
				$i++;

			}

		}

	}
}


/*---------------------------------------------------------*/
/*---- Get post_id of a Product based on SKU provided -----*/
/*---------------------------------------------------------*/
function get_post_id_from_sku( $sku = NULL ) {

	global $wpdb;

	$table_name = $wpdb->prefix . "postmeta";
	$sql = "SELECT post_id FROM " . $table_name . " WHERE meta_value = '$sku'";
	$rows = $wpdb->get_results($sql, ARRAY_A);

	foreach($rows as $row){
		$post_id = $row['post_id'];
		return $post_id;
	}

}



/*----------------------------------------------------------------*/
/*------- Create a new WooCommerce Product -----------------------*/
/*----------------------------------------------------------------*/
function create_woocommerce_product( $product, $product_type, $colours_array, $sizes_array ) {

		$sku = $product['parent'];
		$article = $product['name'];
		$description = $product['description'];
		$product_group = $product['product_group'];
		$price = $product['price'];
		$sale_price = $product['sale_price'];
		$stock = $product['stock'];
		// Set _stock_status according to value of $stock
		if ( $stock > 0 ) {
			$in_stock = 'instock';			
		} else {
			$in_stock = 'outofstock';
		}

		$post = array(
		    'post_author' => 'i.LEVEL',
		    'post_content' => $description,
		    'post_status' => 'draft',
		    'post_title' => $article,
		    'post_parent' => NULL,
		    'post_type' => 'product',
		);

		//Create post
		$post_id = wp_insert_post( $post );

		if($post_id){

			wp_set_object_terms( $post_id, $product_group, 'product_cat' );
			wp_set_object_terms( $post_id, $product_type, 'product_type');
			wp_set_object_terms( $post_id, $colours_array, 'pa_colour' );
			wp_set_object_terms( $post_id, $sizes_array, 'pa_size' );

			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', $in_stock );
			update_post_meta( $post_id, '_stock', $stock );
			// update_post_meta( $post_id, 'total_sales', '0');
			update_post_meta( $post_id, '_downloadable', 'no');
			update_post_meta( $post_id, '_virtual', 'no');
			update_post_meta( $post_id, '_regular_price', $price );
			update_post_meta( $post_id, '_sale_price', $sale_price );
			// update_post_meta( $post_id, '_purchase_note', "" );
			// update_post_meta( $post_id, '_featured', "no" );
			// update_post_meta( $post_id, '_weight', "" );
			// update_post_meta( $post_id, '_length', "" );
			// update_post_meta( $post_id, '_width', "" );
			// update_post_meta( $post_id, '_height', "" );
			update_post_meta( $post_id, '_sku', $sku);
			// update_post_meta( $post_id, '_sale_price_dates_from', "" );
			// update_post_meta( $post_id, '_sale_price_dates_to', "" );
			update_post_meta( $post_id, '_price', $price );
			// update_post_meta( $post_id, '_sold_individually', "" );
			update_post_meta( $post_id, '_manage_stock', "yes" );
			update_post_meta( $post_id, '_backorders', "no" );

			// Now let's set all the attributes if the product is Variable
			if ( $product_type == 'variable' ) {

				foreach ( $sizes_array as $size ) {

					$size_attributes = array(
						                'name'=>'pa_size',
						                'value'=>'',
						                'is_visible' => '1',
						                'is_variation' => '1',
						                'is_taxonomy' => '1'
						);
				}

				foreach ( $colours_array as $colour ) {

					$colour_attributes = array(
						                'name'=>'pa_colour',
						                'value'=>'',
						                'is_visible' => '1',
						                'is_variation' => '1',
						                'is_taxonomy' => '1'
						);
				}

				$product_attributes = array( 
					'pa_colour'=> $size_attributes,
					'pa_size'=> $colour_attributes
					);
				update_post_meta( $post_id, '_product_attributes', $product_attributes);

			}


			wisync_admin_notice__success( $article . ' created (Post #' . $post_id . ').' );

			return $post_id;

		} else {

			wisync_admin_notice__error( 'Failed to create ' . $article . '!' );
			return false;

		}

}


/*------------------------------------------------------------------*/
/*------- Create a new WooCommerce Product Variation ---------------*/
/*------------------------------------------------------------------*/
function create_woocommerce_product_variation( $i, $product, $post_parent ) {

		$sku = $product['skusize'];
		$article = $product['name'];
		$product_group = $product['product_group'];
		$price = $product['price'];
		$sale_price = $product['sale_price'];
		$post_type = 'product';
		$colour = str_replace(array(' ','/'), '-', strtolower($product['colour']));
		$size = str_replace(array(' ','/'), '-', strtolower($product['size']));
		$stock = $product['stock'];
		// Set _stock_status according to value of $stock
		if ( $stock > 0 ) {
			$in_stock = 'instock';			
		} else {
			$in_stock = 'outofstock';
		}

		$post = array(
		    'post_author' => 'i.LEVEL',
		    'post_status' => 'publish',
		    'post_title' => 'Variation #' . $i . ' of ' . $article,
		    'post_parent' => $post_parent,
		    'post_type' => 'product_variation',
		);

		//Create post
		$post_id = wp_insert_post( $post );

		if($post_id){

			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', $in_stock );
			update_post_meta( $post_id, '_stock', $stock );
			update_post_meta( $post_id, '_downloadable', 'no');
			update_post_meta( $post_id, '_virtual', 'no');
			update_post_meta( $post_id, '_price', $price );
			update_post_meta( $post_id, '_regular_price', $price );
			update_post_meta( $post_id, '_sale_price', $sale_price );
			// update_post_meta( $post_id, '_purchase_note', "" );
			// update_post_meta( $post_id, '_featured', "no" );
			// update_post_meta( $post_id, '_weight', "" );
			// update_post_meta( $post_id, '_length', "" );
			// update_post_meta( $post_id, '_width', "" );
			// update_post_meta( $post_id, '_height', "" );
			update_post_meta( $post_id, '_sku', $sku);
			update_post_meta( $post_id, 'attribute_pa_size', $size);
			update_post_meta( $post_id, 'attribute_pa_colour', $colour);
			// update_post_meta( $post_id, '_sale_price_dates_from', "" );
			// update_post_meta( $post_id, '_sale_price_dates_to', "" );
			// update_post_meta( $post_id, '_sold_individually', "" );
			update_post_meta( $post_id, '_manage_stock', "yes" );
			update_post_meta( $post_id, '_backorders', "no" );

			wisync_admin_notice__success( ucfirst($colour) . '/' . ucfirst($size) . ' Variation of ' . $article . ' created (Post #' . $post_id . ').' );
			return $post_id;

		} else {

			wisync_admin_notice__error( 'Failed to create ' . ucfirst($colour) . '/' . ucfirst($size) . ' Variation of ' . $article . '!' );
			return false;

		}
}


/*----------------------------------------------------------------*/
/*------- Update an existing WooCommerce Product -----------------*/
/*----------------------------------------------------------------*/
function update_woocommerce_product( $post_id, $product, $product_type, $colours_array, $sizes_array, $post_parent ) {

		$sku = $product['parent'];
		$article = $product['name'];
		$description = $product['description'];
		$product_group = $product['product_group'];
		$price = $product['price'];
		$sale_price = $product['sale_price'];
		$stock = $product['stock'];
		// Set _stock_status according to value of $stock
		if ( $stock > 0 ) {
			$in_stock = 'instock';			
		} else {
			$in_stock = 'outofstock';
		}

		$post = array(
			'ID' => $post_id,
		    'post_author' => 'i.LEVEL',
		    'post_content' => $description,
// 		    'post_status' => 'publish',
		    'post_title' => $article,
		    'post_parent' => $post_parent,
		    'post_type' => 'product',
		);

		//Update post
		$post_updated = wp_update_post( $post );

		if($post_updated){

			wp_set_object_terms( $post_id, $product_group, 'product_cat' );
			wp_set_object_terms( $post_id, $product_type, 'product_type');
			wp_set_object_terms( $post_id, $colours_array, 'pa_colour' );
			wp_set_object_terms( $post_id, $sizes_array, 'pa_size' );

			update_post_meta( $post_id, '_visibility', 'visible' );
			update_post_meta( $post_id, '_stock_status', $in_stock );
			update_post_meta( $post_id, '_stock', $stock );
			// update_post_meta( $post_id, 'total_sales', '0');
			update_post_meta( $post_id, '_downloadable', 'no');
			update_post_meta( $post_id, '_virtual', 'no');
			update_post_meta( $post_id, '_regular_price', $price );
			update_post_meta( $post_id, '_sale_price', $sale_price );
			// update_post_meta( $post_id, '_purchase_note', "" );
			// update_post_meta( $post_id, '_featured', "no" );
			// update_post_meta( $post_id, '_weight', "" );
			// update_post_meta( $post_id, '_length', "" );
			// update_post_meta( $post_id, '_width', "" );
			// update_post_meta( $post_id, '_height', "" );
			update_post_meta( $post_id, '_sku', $sku);
			// update_post_meta( $post_id, '_sale_price_dates_from', "" );
			// update_post_meta( $post_id, '_sale_price_dates_to', "" );
			update_post_meta( $post_id, '_price', $price );
			// update_post_meta( $post_id, '_sold_individually', "" );
			update_post_meta( $post_id, '_manage_stock', "yes" );
			update_post_meta( $post_id, '_backorders', "no" );

			// Now let's set all the attributes if the product is Variable
			if ( $product_type == 'variable' ) {

				foreach ( $sizes_array as $size ) {

					$size_attributes = array(
						                'name'=>'pa_size',
						                'value'=>'',
						                'is_visible' => '1',
						                'is_variation' => '1',
						                'is_taxonomy' => '1'
						);
				}

				foreach ( $colours_array as $colour ) {

					$colour_attributes = array(
						                'name'=>'pa_colour',
						                'value'=>'',
						                'is_visible' => '1',
						                'is_variation' => '1',
						                'is_taxonomy' => '1'
						);
				}

				$product_attributes = array( 
					'pa_colour'=> $size_attributes,
					'pa_size'=> $colour_attributes
					);
				update_post_meta( $post_id, '_product_attributes', $product_attributes);

			}


			wisync_admin_notice__info( $article . ' updated (Post #' . $post_id . ').' );

			return $post_updated;

		} else {

			wisync_admin_notice__error( 'Failed to update ' . $article . '!' );

			return false;

		}

}


/*------------------------------------------------------------------*/
/*------- Update an existing WooCommerce Product Variation ---------*/
/*------------------------------------------------------------------*/
function update_woocommerce_product_variation( $variation_id, $i, $product, $post_parent ) {

		$sku = $product['skusize'];
		$article = $product['name'];
		$product_group = $product['product_group'];
		$price = $product['price'];
		$sale_price = $product['sale_price'];
		$post_type = 'product';
		$colour = str_replace(array(' ','/'), '-', strtolower($product['colour']));
		$size = str_replace(array(' ','/'), '-', strtolower($product['size']));
		$stock = $product['stock'];
		// Set _stock_status according to value of $stock
		if ( $stock > 0 ) {
			$in_stock = 'instock';			
		} else {
			$in_stock = 'outofstock';
		}

		$post = array(
			'ID' => $variation_id,
		    'post_author' => 'i.LEVEL',
		    'post_status' => 'publish',
		    'post_title' => 'Variation #' . $i . ' of ' . $article,
		    'post_parent' => $post_parent,
		    'post_type' => 'product_variation',
		);

		//Create post
		$variation_updated = wp_update_post( $post );

		if($variation_updated){

			update_post_meta( $variation_id, '_visibility', 'visible' );
			update_post_meta( $variation_id, '_stock_status', $in_stock );
			update_post_meta( $variation_id, '_stock', $stock );
			update_post_meta( $variation_id, '_downloadable', 'no');
			update_post_meta( $variation_id, '_virtual', 'no');
			update_post_meta( $variation_id, '_price', $price );
			update_post_meta( $variation_id, '_regular_price', $price );
			update_post_meta( $variation_id, '_sale_price', $sale_price );
			// update_post_meta( $variation_id, '_purchase_note', "" );
			// update_post_meta( $variation_id, '_featured', "no" );
			// update_post_meta( $variation_id, '_weight', "" );
			// update_post_meta( $variation_id, '_length', "" );
			// update_post_meta( $variation_id, '_width', "" );
			// update_post_meta( $variation_id, '_height', "" );
			update_post_meta( $variation_id, '_sku', $sku);
			update_post_meta( $variation_id, 'attribute_pa_size', $size);
			update_post_meta( $variation_id, 'attribute_pa_colour', $colour);
			// update_post_meta( $variation_id, '_sale_price_dates_from', "" );
			// update_post_meta( $variation_id, '_sale_price_dates_to', "" );
			// update_post_meta( $variation_id, '_sold_individually', "" );
			update_post_meta( $variation_id, '_manage_stock', "yes" );
			update_post_meta( $variation_id, '_backorders', "no" );

			wisync_admin_notice__info( ucfirst($colour) . '/' . ucfirst($size) . ' Variation of ' . $article . ' updated (Post #' . $variation_id . ').' );
			return $variation_id;

		} else {

			wisync_admin_notice__error( 'Failed to update ' . ucfirst($colour) . '/' . ucfirst($size) . ' Variation of ' . $article . '!' );
			return false;

		}
}


/*------------------------------------------------------------------*/
/*------- Update Stock Counts for all Products and Variations ------*/
/*------------------------------------------------------------------*/
function inventory_sync( $login, $skusize, $woocommerce_skus ) {

	// First, extract API login details to use in the query URL
	$domain = $login['domain'];
	$username = $login['username'];
	$password = $login['pass'];

	// Query the API again, specifying the exact variation of product (using the $skusize value)
	$ilevel_response = query_ilevel_api($domain.'/api/stock?stocktype=retail&skusize='.$skusize, $username, $password);

	$stock = json_decode($ilevel_response, true);

	$available = $stock['response']['Stock'][0]['Available'];
	
	save_wc_stock( $skusize, $available );

}