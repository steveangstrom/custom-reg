<?php
/**
 * redefine new user notification function
 */
 // NOTE TO FUTURE DEVS - this server apparently has issues sending mails with CID attachments, hence my  fix of encoding the logo.
  
 // http://stackoverflow.com/questions/15646187/display-inline-image-attachments-with-wp-mail
 // this is partially correct, but will not send user an email because it will be detected as spam.
 //http://www.webtipblog.com/change-wordpress-user-registration-welcome-email/

 // whereas THIS is from the WP-core and will work 
 
 if ( !function_exists('wp_new_user_notification') ) :

function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
	if ( $deprecated !== null ) {
		_deprecated_argument( __FUNCTION__, '4.3.1' );
	}
	global $wpdb, $wp_hasher,  $phpmailer;
	
    add_filter( 'wp_mail_content_type', 'wpmail_content_type' );
	
	$user = get_userdata( $user_id );

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	
   /* $filename = plugin_dir_url( __FILE__ ).'staff_college_logo.png';//phpmailer will load this file
	$cid = 'logo'; //will map it to this CID
	$name = 'staff_college_logo.png'; //this will be the file name for the attachment
	$encodingtouse = 'base64';
	$filemime = 'image/jpeg';
	add_action( 'phpmailer_init', function(&$phpmailer)use($filename,$cid,$name,$encodingtouse,$filemime){
		$phpmailer->SMTPKeepAlive = true;
		$phpmailer->AddEmbeddedImage($filename, $cid, $name,$encodingtouse,$filemime);
	});
		*/
	/******************************/
	// DEBUG
	//$debugmail = print_r($user, true);

	
	/*--------- ADMIN MAIL  --------------------------------------------------*/

	$message ='<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">';

	//$message .= '<img src="cid:logo" alt="The Staff College Logo" height="125" width="250">'; // put the logo in

	$message .= get_logo_encoded();
	$message .= '<h2 style="font-family:Montserrat, Arial, Helvetica; text-transform: capitalize; color:#0067b1;">New user registration on '. $blogname . '</h2>';
	//$message .= '<p>DEBUG : cid = '.cid:logo;
	$message .= '<p>Name :  '.$user->display_name . '</p>';
	$message .= '<p>Email: '. $user->user_email . '</p>';
	$message .='</div>';// message ends 
	
	//$testmess= '<img src="http://i.imgur.com/pyj8gW4.png" alt="The Staff College Logo" height="125" width="250"> ';
	//$testmess= '<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">user display name  =  '.$user->display_name. ' | user email =  '.$user->user_email . ' and the logo url is - '.$logoUrl.'</div>';
	$headers = array('Content-Type: text/html; charset=UTF-8');
	wp_mail(get_option('admin_email'), 'New User Registration on '. $blogname, $message,$headers );

	//wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);
		
	/***************** END OF ADMIN MAIL SECTION ------------------------*/


	// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notifcation.
	if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
		return;
	}

	// Generate something random for a password reset key. /// IN OUR CASE WE USE IT FOR A HASH
	$key = wp_generate_password( 20, false );
	do_action( 'retrieve_password_key', $user->user_login, $key );

	// Now insert the key, hashed, into the DB.
	if ( empty( $wp_hasher ) ) {
		require_once ABSPATH . WPINC . '/class-phpass.php';
		$wp_hasher = new PasswordHash( 8, true );
	}
	$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
	$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );
	
	$message=''; //restart the var for the usermail
	$message .='<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">';	
	//$message .= '<img src="'.$logoUrl.'" alt="The Staff College Logo" height="125" width="250">'; // put the logo in
	$message .= get_logo_encoded();
	$message .= '<h2 style="font-family:Montserrat, Arial, Helvetica; text-transform: capitalize; color:#0067b1;">Welcome '. $user->display_name . '</h2>';
	/*$message.= '<p>Dear '.$user->display_name.' Thank you for registering for access to the unique Staff College learning portal, an online resource designed specifically to support users in growing their personal and organisational strategic intelligence.
For access to the exclusive resources on the Staff College website, please click the following link to complete your registration:</p>';*/

//$message.= '<p>Thank you for registering for access to the unique Staff College learning portal, an online resource designed specifically to support users in growing their personal and organisational strategic intelligence. For access to the exclusive resources on the Staff College website, please click the following link to complete your registration:</p>';


$message.= '<p>Thank you for registering on the unique Staff College Learning Portal, a resource designed to support DCSs and their senior teams in growing their personal and organisational intelligence.</p><p>The Portal will become the College’s prime means of sharing innovative national and international practice through occasional virtual master class style conversations with leading thinkers, blogs, social media feeds, regular digital newsletters, significant document synopses, additional think pieces and policy thinking from across the public and private sector.</p><p>Through its participation in a number of international networks and forums the College will also use the Learning Portal to offer occasional digital common rooms where DCSs can join peers from other countries in discussing and thinking about the challenging and wicked issues facing them and, through this, collectively learn from each other thus creating the potential for a learning community of practice which extends well beyond the UK.</p><p>As a registered user you will be kept up to date with any new online content via a regular newsletter.</p><p>For access to the exclusive resources on the Staff College website, please click the following link to complete your registration:</p>';
	
	
	$css=' display: inline-block; width: auto; height: 39px; line-height: 39px; margin: 0; padding: 0 23px; border: 2px solid #0067b1; color:#0067b1; text-decoration:none; font-weight:bold;';

/*------ TOKEN STUFF ------*/

	add_user_meta( $user_id, 'membership_status','pending-activation'); // set the status of the user to pending.
	add_user_meta( $user_id, 'has_to_be_activated', $key, true );
	
	$activation_link = add_query_arg( array( 'key' => $key, 'user' => $user_id ),home_url('/sign-in/'));
	$message.= '<a href ="'.$activation_link.'" style="'.$css.'" >ACTIVATE YOUR ACCOUNT</a> ';
	$message .='</div>';// message ends 

	wp_mail($user->user_email, sprintf(__('[%s] Activate your account'), $blogname), $message);
	
/**---------------*****/	
	
	// remove html content type
        remove_filter ( 'wp_mail_content_type', 'wpmail_content_type' );
}
endif;




function wpmail_content_type() {
    return 'text/html';
}





if ( !function_exists('wp_nonce_tick') ) :
	/**
	 * Get the time-dependent variable for nonce creation.
	 *
	 * A nonce has a lifespan of two ticks. Nonces in their second tick may be
	 * updated, e.g. by autosave.
	 *
	 * @since 2.5.0
	 *
	 * @return float Float value rounded up to the next highest integer.
	 */
	function wp_nonce_tick() {
		/**
		 * Filter the lifespan of nonces in seconds.
		 *
		 * @since 2.5.0
		 *
		 * @param int $lifespan Lifespan of nonces in seconds. Default 86,400 seconds, or one day.
		 */
		$nonce_life = apply_filters( 'nonce_life', DAY_IN_SECONDS );
	
		return ceil(time() / ( $nonce_life / 2 ));
	}
endif;

add_filter( 'wp_mail_from_name', 'my_mail_from_name' );

function my_mail_from_name( $name ){
    return "The Staff College";
}



?>