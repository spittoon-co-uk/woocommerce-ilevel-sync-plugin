<?php

/*---------------------------------------------------------*/
/*------- Get i.LEVEL connecton details from WP Database --*/
/*---------------------------------------------------------*/
function get_ilevel_details() {

	// Get the global WP database connection
	global $wpdb;

	// Set the table name we're using
	$table_name = $wpdb->prefix . 'wisync';

	// Get the row of data from the specified WP table
	$login = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1", ARRAY_A );

	// Return the resulting row
	return $login;

}



/*---------------------------------------------------------*/
/*---- Query i.LEVEL API for Stock information ------------*/
/*---------------------------------------------------------*/
function query_ilevel_api($url, $user, $password) {

	// Set the arguments for the query
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $user . ':' . $password )
			),
		'httpversion' => '1.0',
        'sslverify' => true,
        'body' => null
		);

	// Make the query, using the URL and arguments
	$response = wp_remote_get( $url, $args );

	// If the query returns an error, exit early
	if( is_wp_error( $response ) ) {
		return false;
	}

	// Retrieve the body from the returned data
	$body = wp_remote_retrieve_body( $response );

	// Return the body
	return $body;

}