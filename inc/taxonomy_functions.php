<?php

/*-----------------------------------------------------------------*/
/*-- Check an Attribute exists in WooCommerce ---------------------*/
/*-----------------------------------------------------------------*/
function check_for_product_attribute( $attribute ) {

	global $wpdb;

	$taxonomy_table_name = $wpdb->prefix . "term_taxonomy";

	$taxonomy_sql = "SELECT * FROM " . $taxonomy_table_name . " WHERE taxonomy = 'pa_$attribute'";
	$taxonomy_table_rows = $wpdb->get_results($taxonomy_sql, ARRAY_A);

	if ( $taxonomy_table_rows ) {
		return true;
	} else {
		return false;
	}

}


/*---------------------------------------------------------*/
/*-- Create a WooCommerce Product Attribute ---------------*/
/*---------------------------------------------------------*/
function create_product_attribute( $label_name ) {
    global $wpdb;

    $slug = sanitize_title( $label_name );

    if ( strlen( $slug ) >= 28 ) {
        return new WP_Error( 'invalid_product_attribute_slug_too_long', sprintf( __( 'Name "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
    } elseif ( wc_check_if_attribute_name_is_reserved( $slug ) ) {
        return new WP_Error( 'invalid_product_attribute_slug_reserved_name', sprintf( __( 'Name "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), $slug ), array( 'status' => 400 ) );
    } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $label_name ) ) ) {
        return new WP_Error( 'invalid_product_attribute_slug_already_exists', sprintf( __( 'Name "%s" is already in use. Change it, please.', 'woocommerce' ), $label_name ), array( 'status' => 400 ) );
    }

    $data = array(
        'attribute_label'   => $label_name,
        'attribute_name'    => $slug,
        'attribute_type'    => 'select',
        'attribute_orderby' => 'menu_order',
        'attribute_public'  => 0, // Enable archives ==> true (or 1)
    );

    $results = $wpdb->insert( "{$wpdb->prefix}woocommerce_attribute_taxonomies", $data );

    if ( is_wp_error( $results ) ) {
        return new WP_Error( 'cannot_create_attribute', $results->get_error_message(), array( 'status' => 400 ) );
    } else {
		wisync_admin_notice__success( 'Created ' . $label_name . ' as Product Attribute.' );
	}

    $id = $wpdb->insert_id;

    do_action('woocommerce_attribute_added', $id, $data);

    wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );

    delete_transient('wc_attribute_taxonomies');
}


/*-------------------------------------------------------------------*/
/*-- Check for existing WooCommerce Product Attribute Term ----------*/
/*-------------------------------------------------------------------*/
function check_for_attribute_term( $term_name ) {
	global $wpdb;

	$terms_table = $wpdb->prefix . "terms";
	$terms_sql = "SELECT * FROM " . $terms_table . " WHERE name = '$term_name'";

	if ( $wpdb->get_results($terms_sql, ARRAY_A) ) {
		$response = true;
	} else {
		$response = false;
	}

	return $response;
}


/*-------------------------------------------------------------------*/
/*-- Create WooCommerce Product Attribute Term ----------------------*/
/*-------------------------------------------------------------------*/
function create_product_attribute_term( $term_name, $attribute_name ) {
	if ( wp_insert_term( $term_name, $attribute_name ) ) {
		wisync_admin_notice__success( $term_name . ' added to Product Attributes.' );
	} else {
		wisync_admin_notice__error( 'Failed to add ' . $term_name . ' to Product Attributes.' );
	}
}