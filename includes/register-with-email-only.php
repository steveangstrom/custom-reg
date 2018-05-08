<?php
remove_filter('authenticate', 'wp_authenticate_username_password', 20);

function email_login_auth( $user, $username, $password ) {
	
	if ( is_email( $username ) ) {
		$user_by_email = get_user_by( 'email', $username );
		
		if ( $user_by_email instanceof WP_User ) {
			$user = null;
			$username = $user_by_email->user_login;
		}
		return wp_authenticate_username_password( $user, $username, $password );
	}
	
	// attempt to do the plain old usename login as a fallback
	$user_by_usename = get_user_by('login',$username);
	if($user_by_usename ){
			if ( $user_by_usename instanceof WP_User ) {
				$user = null;
				$username = $user_by_usename->user_login;
			}
		return wp_authenticate_username_password( $user, $username, $password );
	}
	
	
}
add_filter( 'authenticate', 'email_login_auth', 20, 3 );



function change_login_label() {
    add_filter( 'gettext', 'password_change', 20, 3 );
    function password_change( $translated_text, $text, $domain )
    {
        if ($text === 'Username')
        {
            $translated_text = 'Enter registered E-mail (or user name)';
        }
        return $translated_text;
    }
}
add_action( 'login_head', 'change_login_label' );
	
?>