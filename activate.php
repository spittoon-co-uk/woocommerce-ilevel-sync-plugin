<?php
global $db_version;
$db_version = '1.0';

function wisync_update_db_check()
{
	global $db_version;

	if (get_site_option('db_version') != $db_version) {
		wisync_install();
	}
}

function wisync_install() {

	global $wpdb;
	global $db_version;

	// Set the name of the new database table
	$table_name = $wpdb->prefix . 'wisync';

	// The database character collate.
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					domain text NOT NULL,
					userkey text NOT NULL,
					username text NOT NULL,
					pass text NOT NULL,
					process_option text,
					PRIMARY KEY  (id)
				) $charset_collate;";

	$first_row = "INSERT IGNORE INTO $table_name (id, domain, userkey, username, pass, process_option) VALUES ('1', 'default', 'default', 'default', 'default', 'default')";

	// Require the WordPress upgrade file containing the 'dbDelta' function
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	// Run the SQL query using dbDelta
	dbDelta( $sql );
	dbDelta( $first_row );

	add_option( 'db_version', $db_version );

}