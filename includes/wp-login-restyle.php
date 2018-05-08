<?php


// this file handles the restyling and the locking down of the various admin and wp-admin login pages. Some cant be hidden.

function pher_custom_login() {
echo '<link rel="stylesheet" type="text/css" href="' . plugin_dir_url( __FILE__ )  . '../css/reg-and-log.css" />';
}
add_action('login_head', 'pher_custom_login');

/*****  Importantly set the register URL */

add_filter( 'register_url', 'custom_register_url' );
function custom_register_url( $register_url ){
	return home_url( '/register/' );
}

// Redirect Registration Page attempts to prevent bot registry. 10/04/2017
function my_registration_page_redirect(){
	global $pagenow;
	if ( ( strtolower($pagenow) == 'wp-login.php') && ( strtolower( $_GET['action']) == 'register' ) ) {
		wp_redirect( home_url('/register/'));
	}
}

add_filter( 'init', 'my_registration_page_redirect' );


//add_filter( 'login_url', 'custom_login_url' ); // removed because it hides wp-admin from EVERYONE !
/*
function custom_login_url( $register_url ){
	return home_url( '/sign-in/' );
}
*/


add_filter( 'lostpassword_url', 'custom_lostpass_url' );
function custom_lostpass_url( $lostpassword_url ) {
    return home_url( '/lost-password/' );
}


function pher_login_logo_url() {
return get_bloginfo( 'url' );
}
add_filter( 'login_headerurl', 'pher_login_logo_url' );


function login_checked_remember_me() {
	add_filter( 'login_footer', 'rememberme_checked' );
}
add_action( 'init', 'login_checked_remember_me' );

function rememberme_checked() {
	echo "<script>document.getElementById('rememberme').checked = true;</script>";
}

?>