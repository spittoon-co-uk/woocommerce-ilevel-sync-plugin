<?php

function wisync_settings() {
	if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }
	?>
	<div>
		<form method="post">

		<?php

		global $wpdb;

		// Set the table name we're using
		$table_name = $wpdb->prefix . 'wisync';

		// If we have posted data, continue
		if ( !empty($_POST) ) {

			// Declare an array to contain the posted values, setting the ID to 1
			$row = array(
				'id' => 1
				);

			// Check that the nonce is set and valid. If it is, update the database using form submissions
			if ( isset( $_POST['update_login'] ) && wp_verify_nonce( $_POST['update_login'], 'update_api_login' ) ) {

				if (isset($_POST['domain'])) {
			        $domain = $_POST['domain'];
			        $row['domain'] = $_POST['domain'];
			    }
			    if (isset($_POST['userkey'])) {
			        $userkey = $_POST['userkey'];
			        $row['userkey'] = $_POST['userkey'];
			    }
			    if (isset($_POST['username'])) {
			        $username = $_POST['username'];
			        $row['username'] = $_POST['username'];
			    }
			    if (isset($_POST['password'])) {
			        $password = $_POST['password'];
			        $row['pass'] = $_POST['password'];
			    }
			    // Insert the default values into the table
			    $update = $wpdb->update( $table_name, $row, array( 'id' => 1 ) );
			    // If update was successful, display a success notice
			    if ($update) {
				    wisync_admin_notice__success( 'i.LEVEL API details saved.' );
				}

			// If the nonce was set but invalid, display an error message
			} elseif ( isset( $_POST['update_login'] ) && !wp_verify_nonce( $_POST['update_login'], 'update_api_login' ) ) {

				wisync_admin_notice__error( 'Sorry, your login nonce is unverified' );

			}

			// Check that the nonce is set and valid. If it is, update the database using form submissions
			if ( isset( $_POST['wisync_settings'] ) && wp_verify_nonce( $_POST['wisync_settings'], 'wisync_settings' ) ) {

				if (isset($_POST['process-option'])) {
			        $process_option = $_POST['process-option'];
			        $row['process_option'] = $_POST['process-option'];
			    } else {
			    	$row['process_option'] = 0;
			    }

			    // Insert the default values into the table
			    $update = $wpdb->update( $table_name, $row, array( 'id' => 1 ) );
			    // If update was successful, display a success notice
			    if ($update) {
				    wisync_admin_notice__success( 'Sync Settings saved.' );
				}

			// If the nonce was set but invalid, display an error message
			} elseif ( isset( $_POST['wisync_settings'] ) && !wp_verify_nonce( $_POST['wisync_settings'], 'wisync_settings' ) ) {

				wisync_admin_notice__error( 'Sorry, your wisync_settings nonce is unverified' );

			}

			// Check that the sync nonce is set and valid. If it is, sync with i.LEVEL
			if ( isset( $_POST['manual_sync'] ) && wp_verify_nonce( $_POST['manual_sync'], 'manual_sync' ) ) {

				$login = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1", ARRAY_A );

				$domain = $login['domain'];
				$username = $login['username'];
				$password = $login['pass'];

				$process_option = $login['process_option'];

				// Run the sync
				sync();


			// If the nonce was set but invalid, display an error message
			} elseif ( isset( $_POST['manual_sync'] ) && !wp_verify_nonce( $_POST['manual_sync'], 'manual_sync' ) ) {

				wisync_admin_notice__error( 'Sorry, your sync nonce is unverified' );
			
			}

		}

	    $login = $wpdb->get_row( "SELECT * FROM $table_name WHERE id = 1", ARRAY_A );

		$domain = $login['domain'];
		$username = $login['username'];
		$password = $login['pass'];
		if ( $login['process_option'] == 'inventory-sync' ) {
			$inventory_sync = 'checked';
		} else {
			$inventory_sync = NULL;
		}
		if ( $login['process_option'] == 'product-creation' ) {
			$product_creation = 'checked';
		} else {
			$product_creation = NULL;
		}

		// Check that WooCommerce is activated, if not, display an error message
		if ( !class_exists( 'woocommerce' ) ) {
			wisync_admin_notice__error( 'WooCommerce / i.LEVEL Sync requires WooCommerce' );
		}

	    ?>

		<h2>
			WooCommerce / i.LEVEL Sync
		</h2>
		<p>This plugin functions using the SKUs recorded in WooCommerce.
		<br>
		Any Products that do not have a SKU will not be synced.
		<br>
		Once you have set your API login details and selected a Sync Option, a sync will run every 24 hours. You can also prompt a sync whenever you need to using the Manual Sync button.
		<br>
		Orders made from WooCommerce will also be sent to the i.LEVEL "RetailOrder" API.
		</p>

		<?php
		if (wp_next_scheduled( 'wisync_daily' )) {

			?>
			<p class="description">Your next Automatic Sync is scheduled for <b><?php echo date('m/d/Y H:i:s', wp_next_scheduled( 'wisync_daily' ) ) ?></b></p>
			<?php
		}
		?>
		<hr>
		    <fieldset>
				<h3>i.LEVEL API</h3>
				<p>Please provide your i.LEVEL login details to connect your WooCommerce installation.</p>
				<table class="form-table">
					<?php wp_nonce_field( 'update_api_login', 'update_login' ) ?>
					<tr valign="top">
						<th scope="row">
							<label for="domain">Domain</label>
						</th>
						<td>
							<input type="text" id="domain" name="domain" value="<?php echo $domain; ?>" class="regular-text" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="username">Username</label>
						</th>
						<td>
							<input type="text" id="username" name="username" value="<?php echo $username; ?>" class="regular-text" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="password">Password</label>
						</th>
						<td>
							<input type="password" id="password" name="password" value="<?php echo $password; ?>" class="regular-text" />
						</td>
					</tr>
				</table>
			<?php submit_button( 'Save' ); ?>
			</fieldset>
		</form>
		<hr>
		<form method="post">
			<fieldset>
				<h3>Sync Options</h3>
				<p>Please choose whether to use the Inventory Sync Only or the full Product Sync process. Each process has different minimum requirements in order to function correctly, so please read the instructions carefully.
				</p>
				<h4>Inventory Sync Only</h4>
				<p>This option is best if you want to tailor your WooCommerce Products' groupings to be different from how they appear in i.LEVEL, and manage them manually.<br>
					For this to function correctly, all you need is for any Product or Variation you want to be included in the sync to have a SKU that matches the <b><i>"SKUSize"</i></b> value from the i.LEVEL API.<br>
					Any Products/Variations with a SKU that does not match i.LEVEL will not be included in the sync.<br>
					If you have your SKUs set up correctly for Full Sync, this will continue to function if you switch to Inventory Sync Only.
				</p>
				<h4>Full Sync</h4>
				<p>This option is best for full automation that reflects the i.LEVEL data <i>exactly</i>.<br>
					It is important to ensure that all existing top-level Products have a SKU that matches the <b><i>"Style"</i></b> value from the i.LEVEL API.<br>
					All Variations should also have a SKU that matches the <b><i>"SKUSize"</i></b> value from the i.LEVEL API.<br>
					Any Products or Variations that do not have the correct SKU will be created anew.<br>
					Whenever a top-level Product is newly created during a sync, it is stored as a Draft, allowing the opportunity to add images before publishing the Product to the front end of your website.
				</p>
				<table class="form-table">
					<?php wp_nonce_field( 'wisync_settings', 'wisync_settings' ) ?>
					<tr valign="top">
						<th scope="row">
							<label for="inventory-sync">Inventory Sync Only</label>
						</th>
						<td>
							<input type="radio" id="inventory-sync" name="process-option" value="inventory-sync" <?php echo $inventory_sync; ?> >
						</td>
						<td>
							<p class="description">
								Syncs only the stock levels, and only where the Product or Variation SKU has been set to match that within i.LEVEL.
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="product-creation">Full Sync</label>
						</th>
						<td>
							<input type="radio" id="product-creation" name="process-option" value="product-creation" <?php echo $product_creation; ?>>
						</td>
						<td>
							<p class="description">
								Full synchronisation of Products, including creating new Products/Variations and Size/Colour Attributes, as well as updating descriptions, categories, names, prices and stock levels.
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save' ); ?>
			</fieldset>
		</form>
		<hr>
		<form method="post">
			<?php wp_nonce_field( 'manual_sync', 'manual_sync' ) ?>
			<fieldset>
				<h3>Manual Sync</h3>
				<p>If you need to trigger a sync manually, just click the button below.</p>
				<?php submit_button( 'Sync Now' ); ?>
			</fieldset>
		</form>
	</div>
	<?php
}