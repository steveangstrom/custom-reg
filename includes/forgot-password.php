<?php
/******** FORGOT PASSWORD - MAILS ****************/


function pher_forgotpassword_form() {
	//  shortcode on the page lost-password , displays either the "Enter your mail and ask for a reset"  form or the "you are back from the mail now enter your password form"
	
	$postaction =isset($_REQUEST['action'])?$_REQUEST['action'] :null;

	if ($postaction == 'rp'){
			$output = pher_resetpass_form(); // "you are back with a reset token, so enter your new password"
		}else{
			$output = pher_forgot_form(); // "enter your email address and we'll send you an RESET token
		}
		
	return $output;
}
add_shortcode('forgotpass_form', 'pher_forgotpassword_form');


function pher_show_reset_success(){
# check the post var is set for a request, there are no errors.
	if (isset ($_POST['request']) && (!pher_errors()->get_error_codes()) && ($_POST['request'] =='newpassword')){
		echo('<div class="pher_notice success"><h3>We have sent you an email</h3>
				<p>An email has been sent to '.$_POST['user_login'].', when it arrives you will be able to reset your password with the link it contains.</p></div>');
				return true;
		}

}


function pher_forgot_form(){
		global $pher_load_css;
		$pher_load_css = true;// set this to true so the CSS is loaded
		// show any error messages after form submission
		pher_show_error_messages(); 
		$issuccess = pher_show_reset_success();
		
		if($issuccess){return;}

		
	 	ob_start(); ?>
        
        <form id="lostpasswordform" class="pher_form" action="<?php echo wp_lostpassword_url(); ?>" method="post">
        <p>
           <label for="pher_user_email"><?php _e('Your Email'); ?></label>
            <input type="text" name="user_login" id="user_login">
        </p>
 
        <p>
           <!-- <input type="submit" name="submit" class="lostpassword-button"  value="<?php _e( 'Reset Password', 'personalize-login' ); ?>"/>-->
          <div class="padlikealabel"> </div>  <button type="submit" class="qbutton" >Reset Password</button>
        </p>
         <input type="hidden" name="request" value="newpassword">
    </form>
            
        <?php
	return ob_get_clean();
	
}


function pher_resetpass_form(){
		global $pher_load_css;
		$pher_load_css = true;// set this to true so the CSS is loaded
		// show any error messages after form submission
		pher_show_error_messages(); 
		pher_show_reset_success();
		
	/* 	if ( is_user_logged_in() ) {
       	 return __( 'You are already signed in.', 'personalize-login' );
    	} else {*/
			
        if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
            $attributes['login'] = $_REQUEST['login'];
            $attributes['key'] = $_REQUEST['key'];
 
          ?>
        <form name="resetpassform" id="resetpassform" class="pher_form" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="post" autocomplete="off">
        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attributes['login'] ); ?>" autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attributes['key'] ); ?>" />
         
        <?php if ( isset($attributes['errors']) && count( $attributes['errors'] ) > 0 ) : ?>
            <?php foreach ( $attributes['errors'] as $error ) : ?>
                <p>
                    <?php echo $error; ?>
                </p>
            <?php endforeach; ?>
        <?php endif; ?>
        <h2>Changing your password</h2>
  <p>Now type your new password in the box below</p>
        <p>
            <label for="pass1"><?php _e( 'New password', 'personalize-login' ) ?></label>
            <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
        </p>

         
          <div class="padlikealabel"> </div>  <small class="description"><?php echo wp_get_password_hint(); ?></small>
         
        <p class="resetpass-submit">
             <div class="padlikealabel"> </div>  <button type="submit" class="qbutton"  name="submit" id="resetpass-button"  class="qbutton" >Save your new password</button>
        </p>
    </form>
          
          <?php
        } else {
            return __( 'Invalid password reset link.', 'personalize-login' );
        }
    //}
}


add_action( 'after_setup_theme', 'do_password_lost' );


function do_password_lost(){
	// this function intercepts the POST from the custom "forgot password" form also in this file.
	// it also checks the posted variables when they have entered a new password
	
	
	 if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {


		$postaction =isset($_REQUEST['action'])?$_REQUEST['action'] :null;
		
		if($postaction == 'resetpass'){
			
			
			}

		
		$request=isset($_REQUEST['request'])?$_REQUEST['request'] :null;
		

		if ( $request !='newpassword'){return;}

		$user_login = sanitize_email( $_POST["user_login"]); 

		if(!is_email($user_login)) {
			#echo 'not an email';
			pher_errors()->add('email_invalid', __('Invalid email address.'));
			return;
		}
		
		
		if( email_exists( $user_login )) {
			#echo 'this dude exists';
		}else{
			#echo 'we do not have that mail';
			pher_errors()->add('email_invalid', __('We do not have a record of your account at that email address'));
			return;
			}
			
		 $user_data = get_user_by( 'email',  $user_login );

		 if ( !$user_data ) {
			pher_errors()->add('email_invalid', __('We do not have a record of your account at that email address'));
			return;
   		 }
		 
		 
		  // Redefining user_login ensures we return the right case in the email.
    $user_login = $user_data->user_login;
    $user_email = $user_data->user_login;
    $key = get_password_reset_key( $user_data );
 

/********* MAIL TIME ***************/
  	 add_filter( 'wp_mail_content_type', 'wpmail_content_type' );
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	$reseturl= home_url("/lost-password/?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');
	
	$buttoncss=' display: inline-block; width: auto; height: 39px; line-height: 39px; margin: 0; padding: 0 23px; border: 2px solid #0067b1; color:#0067b1; text-decoration:none; font-weight:bold;';
		
		
	//$logoUrl = plugin_dir_url( __FILE__ ).'staff_college_logo.png';
	$message ='<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">';
	//$message .= '<img src="'.$logoUrl.'" alt="The Staff College Logo" height="125" width="250">'; // put the logo in
	$message .= get_logo_encoded();
	$message .= '<h2 style="font-family:Montserrat, Arial, Helvetica; text-transform: capitalize; color:#0067b1;">Password Reset request for '. $user_data->display_name . '</h2>';


	$message .= "To reset your password please follow the link below.  If you didn't request a password reset, please contact the team if you have any concerns regarding the security of your account.</p> <p>Thank you,<br>The Staff College Team<br>hello@thestaffcollege.uk </p>";

	$message.= '<a href ="'.$reseturl.'" style="'.$buttoncss.'" >CREATE A NEW PASSWORD</a> ';
	 $message .= '<p><small>or copy and paste this link into your browser : '.$reseturl;
  $message .='</small></p></div>';// message ends 

	
	$email=$user_data->user_email;
	
	wp_mail($email, sprintf(__('[%s] You requested a password reset'), $blogname), $message);
	
	// if we want an admin to know about this then uncomment and set a message for them.
	//@wp_mail(get_option('admin_email'), sprintf(__('[%s] Forgot Password'), $blogname), $adminmessage);
	
	remove_filter ( 'wp_mail_content_type', 'wpmail_content_type' );

	return;
	 }
	
}

if ( ! function_exists( 'wp_password_change_notification' ) ) {
	
	function wp_password_change_notification( $user ) {
		// send a copy of password change notification to the admin
		// but check to see if it's the admin whose password we're changing, and skip this
		if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
			
			add_filter( 'wp_mail_content_type', 'wpmail_content_type' );
			//$logoUrl = plugin_dir_url( __FILE__ ).'staff_college_logo.png';
			$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			
			 /******************* admin mail **********************************/
			$adminmessage ='<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">';
			//$adminmessage .= '<img src="'.$logoUrl.'" alt="The Staff College Logo" height="125" width="250">'; // put the logo in
			$adminmessage .= get_logo_encoded();
			$adminmessage .= '<p>The Member '.$user->display_name.' has reset their password. No further action is necessary</p>';
			$adminmessage .= '</div>';// message ends 
			wp_mail(get_option('admin_email'), sprintf(__('[%s] A Member reset their password'), $blogname), $adminmessage);
			
			/******************* now the usermail **********************************/
			
			$email = $user->user_email;
			$message ='<div style="font-family:Montserrat, Arial, Helvetica, sans-serif; font-size:16px; color:#333333; padding:10px 30px 10px 30px;">';
			//$message .= '<img src="'.$logoUrl.'" alt="The Staff College Logo" height="125" width="250">'; // put the logo in
			$message .= get_logo_encoded();
			$message .= '<h2 style="font-family:Montserrat, Arial, Helvetica; text-transform: capitalize; color:#0067b1;">The Staff College Learning Portal</h2>';
			$message .= "<p>Dear ".$user->display_name.",Your learning portal password has now been changed.<br>If you did not change your password, please contact us via 0115 7484124.<br> Many thanks,<br>The Staff College Team<br>hello@thestaffcollege.uk</p> ";
			$adminmessage .= '</div>';// message ends 
			wp_mail($email, sprintf(__('[%s] Confirmation - your password is now changed'), $blogname), $message);
			remove_filter ( 'wp_mail_content_type', 'wpmail_content_type' );
			return;
		}
	}

}

add_action( 'login_form_resetpass', 'do_password_reset'  );
function do_password_reset() {
	// this one ought to take command of the actual reseting.
	
    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $rp_key = $_REQUEST['rp_key'];
        $rp_login = $_REQUEST['rp_login'];
 
        $user = check_password_reset_key( $rp_key, $rp_login );
 
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
				pher_errors()->add('expired_pw_reset_key', __('Expired Key'));
                wp_redirect( home_url( 'sign-in?login=expiredkey' ) );
            } else {
				pher_errors()->add('invalid_pw_reset_key', __('Invalid Key'));
                wp_redirect( home_url( 'sign-in?login=invalidkey' ) );
            }
            exit;
        }
 
        if ( isset( $_POST['pass1'] ) ) {

            if ( empty( $_POST['pass1'] ) ) {
                // Password is empty
                $redirect_url = home_url( 'member-password-reset' );
 
                $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
 
                wp_redirect( $redirect_url );
                exit;
            }
 
            // Parameter checks OK, reset password
            reset_password( $user, $_POST['pass1'] );
            wp_redirect( home_url( 'sign-in?password=changed' ) );
        } else {
            echo "Invalid request.";
        }
 
        exit;
    }
}



add_action( 'the_post', 'pher_notification', 10, 3 );
//do_action( 'pher_notification', 'testttt');

function pher_notification( $post, $query, $message='' ) {
   echo $message;
}


if(!function_exists('pher_errors')){
// used for tracking error messages-----------------------------------------
		function pher_errors(){
			static $wp_error; // Will hold global variable safely
			return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
		}
	}
?>