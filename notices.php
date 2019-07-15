<?php

/*---------------------------------------------------------*/
/*---- Display success/failure messages in admin pages ----*/
/*---------------------------------------------------------*/
function wisync_admin_notice__success( $message ) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e( $message, 'sample-text-domain' ); ?></p>
    </div>
    <?php
}
// add_action( 'admin_notices', 'wisync_admin_notice__success' );

function wisync_admin_notice__error( $message ) {
	$class = 'notice notice-error';
	$message = __( $message, 'sample-text-domain' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
// add_action( 'admin_notices', 'wisync_admin_notice__error' );

function wisync_admin_notice__info( $message ) {
	$class = 'notice notice-info is-dismissible';
	$message = __( $message, 'sample-text-domain' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}
// add_action( 'admin_notices', 'wisync_admin_notice__info' );