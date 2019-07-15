<?php

/*---------------------------------------------------------*/
/*---- Get an array of SKUs in the WP database ------------*/
/*---------------------------------------------------------*/
function get_all_woocommerce_skus() {

	global $wpdb;

	$sku_array = array();

	$table_name = $wpdb->prefix . "postmeta";
	$sql = "SELECT meta_value FROM " . $table_name . " WHERE meta_key = '_sku'";
	$rows = $wpdb->get_results($sql, ARRAY_A);

	foreach($rows as $row) {
		$sku = $row['meta_value'];
		if ( !is_null($sku) && !empty($sku) ) {
			array_push( $sku_array, $sku );
		}
	}

	return $sku_array;
}

/*---------------------------------------------------------*/
/*-- Get the parent post of a Product using post_id -------*/
/*---------------------------------------------------------*/
function get_post_parent( $post_id = NULL ) {

	global $wpdb;

	$table_name = $wpdb->prefix . "posts";
	$sql = "SELECT post_parent FROM " . $table_name . " WHERE ID = '$post_id'";
	$rows = $wpdb->get_results($sql, ARRAY_A);

	foreach($rows as $row){
		$post_parent = $row['post_parent'];
		return $post_parent;
	}
}

/*---------------------------------------------------------*/
/*-- Get meta_value of a Product using post_id ------------*/
/*---------------------------------------------------------*/
function fetch_post_meta( $post_id = NULL, $key ) {

	global $wpdb;

	$table_name = $wpdb->prefix . "postmeta";
	$sql = "SELECT meta_value FROM " . $table_name . " WHERE post_id = '$post_id' AND meta_key = '$key'";
	$rows = $wpdb->get_results($sql, ARRAY_A);

	foreach($rows as $row){
		$meta_value = $row['meta_value'];
		return $meta_value;
	}
}

/*----------------------------------------------------------------*/
/*-- Update the postmeta table -----------------------------------*/
/*----------------------------------------------------------------*/
function rewrite_post_meta( $post_id, $key, $value ) {

	global $wpdb;

	$tablename = $wpdb->prefix . 'postmeta';

	$update = $wpdb->update(
		// $table - The name of the table to update.
		$tablename,
		// $data - Data to update (in column => value pairs).
		array(
			'meta_value' => $value
			),
		// $where - A named array of WHERE clauses (in column => value pairs).
		array(
			'post_id' => $post_id,
			'meta_key' => $key
			)
		);

	if ($update === FALSE) {
		return false;
	} else {
		return true;
	}
}

/*----------------------------------------------------------------*/
/*-- Reset all _stock_status to be outofstock --------------------*/
/*----------------------------------------------------------------*/
function reset_all_stock_status( $key, $value ) {

	global $wpdb;

	$tablename = $wpdb->prefix . 'postmeta';

	$update = $wpdb->update(
		// $table - The name of the table to update.
		$tablename,
		// $data - Data to update (in column => value pairs).
		array(
			'meta_value' => $value
			),
		// $where - A named array of WHERE clauses (in column => value pairs).
		array(
			'meta_key' => $key
			)
		);

	if ($update === FALSE) {
		return false;
	} else {
		return true;
	}
}

/*----------------------------------------------------------------*/
/*-- Write new data to postmeta table ----------------------------*/
/*----------------------------------------------------------------*/
function save_wc_stock($sku, $stock) {

	// Get the Post ID from the SKU
	$post_id = get_post_id_from_sku( $sku );
	// Get the current Stock Count for this product
	$current_stock = fetch_post_meta( $post_id, '_stock' );
	// Get the current Stock Status for this product (for comparison)
	$old_stock_status = fetch_post_meta( $post_id, '_stock_status' );

	// Find the parent post for this product
	$parent_post_id = get_post_parent( $post_id );

	// Determine the string to use for the new _stock_status
	if ($stock > 0) {
		// If $stock is greater than 0, use 'instock'
		$stock_status = 'instock';
		rewrite_post_meta( $parent_post_id, '_stock_status', 'instock');
	} else {
		// If $stock is 0 or less, use 'outofstock'
		$stock_status = 'outofstock';
	}
	
	// Compare the newly-provided stock count to the value currently in the database
	if ( $stock != $current_stock ) {
		// If the newly-provided stock count is not the same as the value currently in the database, continue
		// Update stock count with the latest figures
		$update_stock = rewrite_post_meta( $post_id, '_stock', $stock );
		// Check the new stock count has been saved
		if ( !$update_stock ) {
			// If new stock status has not been saved, store an error message in notice array
			wisync_admin_notice__error( $sku.': stock count failed to update!' );
		} else {
			// If new stock status has been saved successfully, store a success message in notice array
			wisync_admin_notice__success( $sku.': stock count updated to '.$stock );
		}
	}



	// Compare the existing _stock_status to the new one
	if ( $stock_status != $old_stock_status ) {
		// If the _stock_status is not identical to the old _stock_status, update it
		$update_status = rewrite_post_meta( $post_id, '_stock_status', $stock_status );
		// Check the new stock status has been saved
		if ( !$update_status ) {
			// If new stock status has not been saved, store an error message in notice array
			wisync_admin_notice__error( $sku.': stock status failed to update!' );
		} else {
			// If new stock status has been saved successfully, store a success message in notice array
			wisync_admin_notice__success( $sku.': stock status updated to '.$stock_status );
		}
	}

}