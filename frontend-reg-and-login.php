<?php
/*
Plugin Name: Front End Registration and Login
Plugin URI: 
Description: Provides  StaffCollege front end registration and login forms
Version: 1.02
Author: 
Author URI: 
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

#####################################################################################################


include_once('includes/admin-auth-domains.php' );
include_once('includes/user-emails.php' );
include_once('includes/admin-auth-subscribers.php' );// go do the subscriber menu thingy
include_once('includes/wp-login-restyle.php');

include_once('includes/register-with-email-only.php' );
include_once('includes/forgot-password.php');


function hide_plainview_log() {
    remove_menu_page( 'plainview_activity_monitor');
}
add_action( 'admin_menu', 'hide_plainview_log', 999 );


// register our form css -----------------------------------------
function pher_register_css() {
	wp_register_style('pher-form-css', plugin_dir_url( __FILE__ ) . '/css/reg-and-log.css');
}
add_action('init', 'pher_register_css');



// load our form css -----------------------------------------
function pher_print_css() {
	global $pher_load_css;
	// this variable is set to TRUE if the short code is used on a page/post
	if ( ! $pher_load_css )
		return; // this means that neither short code is present, so we get out of here
	wp_print_styles('pher-form-css');
}
add_action('wp_footer', 'pher_print_css');



// user registration login form -----------------------------------------
function pher_registration_form() {

 
	// only show the registration form to non-logged-in members
	if(!is_user_logged_in()) {
		global $pher_load_css;
		$pher_load_css = true;// set this to true so the CSS is loaded
		$registration_enabled = get_option('users_can_register');	// check to make sure user registration is enabled
		
		if($registration_enabled) {// only show the registration form if allowed
			$output = pher_registration_form_fields();
		} else {
			$output = __('User registration is not enabled');
		}
		return $output;
	} else {
		//
		return 'or <a href="'.wp_logout_url( home_url() ).'">Sign-out</a>';
	}
 
}
add_shortcode('register_form', 'pher_registration_form');




// user login form -----------------------------------------
function pher_login_form() {
 
	if(!is_user_logged_in()) {
 
		global $pher_load_css;
 
		// set this to true so the CSS is loaded
		$pher_load_css = true;
 
		$output = pher_login_form_fields();
	} else {
		// could show some logged in user info here
		// $output = 'user info here';
		 global $current_user; 
		// get_currentuserinfo();

		return '<h2>You are currently logged in as '.$current_user->user_login.'</h2> <p>Would you like to go to <a href="'.esc_url( home_url( '/learning-portal/' )).'">Your Learning Portal</a></p>';
		
		
	}

	return $output;
}
add_shortcode('login_form', 'pher_login_form');


// registration form fields-----------------------------------------
function pher_registration_form_fields() {
 	ob_start(); ?>	
	<form id="pher_registration_form" class="pher_form" action="" method="POST">
        <h3 class="pher_header"><?php _e('Register New Account'); ?></h3>
        		<?php pher_show_error_messages(); ?>
			<fieldset>

				<p>
					<label for="pher_user_email"><?php _e('Email'); ?></label>
					<input name="pher_user_email" id="pher_user_email" class="required" type="email"  value="<?php echo isset($_POST['pher_user_email'])? $_POST['pher_user_email'] : ""?>"  />
				</p>
				<p>
					<label for="pher_user_first"><?php _e('First Name'); ?></label>
					<input name="pher_user_first" id="pher_user_first" type="text"  value="<?php echo isset($_POST['pher_user_first'])? $_POST['pher_user_first'] : ""?>" />
				</p>
				<p>
					<label for="pher_user_last"><?php _e('Last Name'); ?></label>
					<input name="pher_user_last" id="pher_user_last" type="text"   value="<?php echo isset($_POST['pher_user_last'])? $_POST['pher_user_last'] : ""?>"  />
				</p>
                <p>
					<label for="pher_user_jobtitle"><?php _e('Job Title'); ?></label>
					<input name="pher_user_jobtitle" id="pher_user_jobtitle" type="text"   value="<?php echo isset($_POST['pher_user_jobtitle'])? $_POST['pher_user_jobtitle'] : ""?>"  />
				</p>
				<p>
					<label for="password"><?php _e('Create Password'); ?></label>
					<input name="pher_user_pass" id="password" class="required" type="password"   value="<?php echo isset($_POST['pher_user_pass'])? $_POST['pher_user_pass'] : ""?>"     />
				</p>
                
				<p>
					<label for="pher_terms"><?php _e('I agree to the Terms of Service'); ?></label>
					<input name="pher_terms" id="pher_terms" type="checkbox" value="Yes">
				</p>
				
				
				<p><label></label>
					<input type="hidden" name="pher_register_nonce" value="<?php echo wp_create_nonce('pher-register-nonce'); ?>"/>
                    <button type="submit" class="qbutton"  >Register Your Account</button>
				</p>
			</fieldset>
		</form>
        
	<?php
	
	return ob_get_clean();

	}
	
	
function pher_activate_user() {
	$slug = get_post_field( 'post_name', get_post());
    if ( $slug =='sign-in' ) {
		
		//echo('now testing');
		
        $applicant_id = isset($_GET["user"])?$_GET["user"]:''; // the incoming user id
		$applicant_key = isset( $_GET["key"])?$_GET["key"]:''; // the incoming user key

		
        if ( $applicant_id ) {
            // get user meta activation hash field
            $code = get_user_meta( $applicant_id, 'has_to_be_activated', true );
			
			//echo ('<br>secret code = '.$code);
			
            if ( $code == filter_input( INPUT_GET, 'key' ) ) {	
				echo('<div class="pher_notice success"><h3>Congratulations - you activated your membership</h3>
				<p>Now sign in with your email address and password below to access your learning portal</p></div>');
				
                delete_user_meta( $applicant_id, 'has_to_be_activated' ); // remove the key
			    update_user_meta( $applicant_id, 'membership_status','activated' ); // set the flag
				
            }else{
				echo('<div class="pher_notice info"><h3>Account is already Activated</h3>
				<p>Sign in with your email address and password below to access your learning portal</p></div>');
				
				}
        }
    }
}
	
	
// login form fields-----------------------------------------
function pher_login_form_fields() {
 pher_activate_user();// check see if there's anything there
 

	ob_start(); 

	$getaction =isset($_REQUEST['password'])?$_REQUEST['password'] :null;
	if ($getaction =='changed'){	
	?>
	<div class="pher_notice success"><h3>Success - you updated your password</h3><p>Now sign in with your email address and new password to access your learning portal</p></div>
	<?php }	
	
	
	$reseterror =isset($_REQUEST['login'])?$_REQUEST['login'] :null;
	if ($reseterror =='invalidkey'){
		echo '<div class="pher_notice error"><h3>Error - reset not complete</h3><p>It seems you have already used that token to reset your password.  The email links can only be used once for security reasons. Would you like to <a href="'.home_url( 'lost-password').'">reset your password again?</a> Or try logging in below with your most recently known password.</p></div>	';
	}
	 ?>	

 
		<form id="pher_login_form"  class="pher_form"action="" method="post">
        <h2 class="pher_header"><?php _e('Sign In'); ?></h2>
         
		<?php
		// show any error messages after form submission
		pher_show_error_messages(); ?>
			<fieldset>
				<p>
					<label for="pher_user_Login">Email address <span class="redtext">*</span></label>
					<input name="pher_user_login" id="pher_user_login" class="required" type="text"/>
				</p>
				<p>
					<label for="pher_user_pass">Password</label>
					<input name="pher_user_pass" id="pher_user_pass" class="required" type="password"/>
				</p>
				<p>
					<input type="hidden" name="pher_login_nonce" value="<?php echo wp_create_nonce('pher-login-nonce'); ?>"/>
					<div class="padlikealabel"> </div>
                   <button type="submit" class="qbutton"  >Sign in to your account</a>
				</p>
			</fieldset>
            <div class="padlikealabel"> </div>
            <?php 	echo '<div class="forgotreset"><a href="'.wp_lostpassword_url().'" title="If you have forgotten your membership details then click here">Forgotten password?</a></div>'; ?>
		</form>
        <p><span class="redtext">*</span> Your registered local authority email address</p>
	<?php

	return ob_get_clean();
}

 

// --------------------------------- LOG IN  - member in after submitting login form-----------------------------------------

function pher_login_member() {
 
	if(isset($_POST['pher_user_login']) && isset($_POST['pher_login_nonce']) && wp_verify_nonce($_POST['pher_login_nonce'], 'pher-login-nonce')) {
 
	// this returns the user ID and other info from the user name
	
		$user_login=sanitize_user($_POST['pher_user_login']);
		
		$user_pass=$_POST['pher_user_pass'];
		
		
		
		/******* LOGIN BY EMAIL ADDRESS AS UERNAME, or just usernam if thats what it is ********/
		if ( is_email( $user_login ) ) {
				$user = get_user_by( 'email', $user_login );
		}else{
				$user = get_user_by('login',$user_login);//returns WP_User object on success, false on failure.
			}

		if(!$user) {
			// if the user name doesn't exist  (get_user_by returns false )
			pher_errors()->add('invalid_login', __('Sorry, that doesn\'t match our records')); //should not be telling people about invalid usernames 
			return;
		}
 
		if(!isset($user_pass) || $user_pass == '') {
			// if no password was entered
			pher_errors()->add('empty_password', __('Please enter a password'));
			return;
		}
		
 		if ( $user && wp_check_password( $user_pass, $user->data->user_pass, $user->ID) ){
			   //echo "That's it";
			}else{
				 pher_errors()->add('bad_password', __('Please enter the correct credentials'));
				 return;
			 }


		// check if their organisation is still allowed.  Some expire!
		$org_auth = get_organisation_auth($user_login);
		// go and see if the org is still authorised
		if ($org_auth  == false){
			
				do_action('pher_orgfail',$user->ID, 'BAD' );// steves ORG FAIL HOOK
				
				pher_errors()->add('not_authorised', __('Your organisation no longer has the required access permission. Please contact your network administrator or hello@staffcollege.uk if you require access.'));	
			}


		// Account not activated ?
		$code = get_user_meta( $user->ID, 'has_to_be_activated', true );

		if (strlen($code)>0 ){
			
			//OK, so they failed to get the email or click the link, so has an ADMIN done it instead?
			$activationstatus = get_user_meta( $user->ID, 'membership_status', true );
			if($activationstatus =='activated'){
				delete_user_meta( $applicant_id, 'has_to_be_activated' ); // remove the key because ADMIN allowed them in.
			}else{
				// nah, they just arent activated at all.
				pher_errors()->add('not_activated', __('You have not yet activated your account. Check your registered email account for an activation notice from The Staff College and click the ACTIVATE button in that email.'));
			}
			
			
		};
	
		
		
		$errors = pher_errors()->get_error_messages();
		if(empty($errors)) {
			wp_set_auth_cookie($user->ID);

			$creds=array('user_login' => $user_login,'user_pass' => $user_pass );
			
           	$user_verify= do_action('wp_signon',$creds );
			
			do_action('pher_signon',$user->ID, 'OK' );// steves new hook
			
			//exit;
			
			
			if ( !is_wp_error($user_verify) ){ 
				wp_set_current_user($user->ID, $user_login);
				wp_redirect(home_url('/learning-portal/'));
				exit;
			}
		}
	}
}
#add_action('init', 'pher_login_member');
add_action( 'after_setup_theme', 'pher_login_member' );



add_action('pher_signon','pher_signon_log_trigger',10,2);

function pher_signon_log_trigger($user_id , $status='TEST'){
	return 'a returned var';
}

add_action('pher_orgfail','pher_orgfail_log_trigger',10,2);

function pher_orgfail_log_trigger($user_id , $status='TEST'){
	return 'a returned var';
}




#########################################################


function pher_add_new_member() {
	
  	if (isset($_POST['pher_register_nonce']) && wp_verify_nonce($_POST['pher_register_nonce'], 'pher-register-nonce')) {
		/*$user_login		= sanitize_user($_POST["pher_user_login"]);*/	
		
		$user_login		= sanitize_email( $_POST["pher_user_email"]); // yes really. we are mapping new username to the email address
		$user_email		= sanitize_email( $_POST["pher_user_email"]);
		
		
		$user_first 	= sanitize_user($_POST["pher_user_first"]);
		$user_last	 	= sanitize_user($_POST["pher_user_last"]);
		$user_pass		=  sanitize_user($_POST["pher_user_pass"]);
		
		$user_job_title = sanitize_text_field($_POST["pher_user_jobtitle"]);
		
		
		if(!is_email($user_email)) {
			//invalid email    //Does not grok i18n domains. Not RFC compliant. 
			pher_errors()->add('email_invalid', __('Invalid email'));
		}
		if(email_exists($user_email)) {
			//Email address already registered
			pher_errors()->add('email_used', __('Sorry, that email is already in our system. If you would like to recover the password for it then use the "forgotten my password" link'));
		}
		
		 // Check terms of service is agreed to
        if($_POST['pher_terms'] != "Yes"){
            pher_errors()->add('Terms', __('You must agree to Terms of Service')); 
        }
		if($user_pass == '') {
			// password  empty
			pher_errors()->add('password_empty', __('Please enter a password'));
		}
	

 ####  test user email address to see if it is in  table  of authorized email domains

 
	$explodedEmail = explode('@', $user_email);
    $user_domain = array_pop($explodedEmail);
 
 
		global $wpdb;					
		$approved_domains =array();
		$tablename=$wpdb->prefix.'authorised_orgs';

		
		$results = $wpdb->get_results('SELECT domain FROM '.$tablename.' WHERE authstatus = "allowed" AND domain = "'.$user_domain.'"' ) ; 
		if (!$results){
			//pher_errors()->add('not_authorised', __('Your organisation does not have the required access permission. Please contact your Network Admin. or hello@staffcollege.org if you you require access '));	 
			pher_errors()->add('not_authorised', __('Your organisation does not have the required access permission. Please contact <a href="mailto:hello@thestaffcollege.uk">hello@thestaffcollege.uk</a> if you require access.'));	 
		}
	
		//give feedback to user on success -on redirect??

		$errors = pher_errors()->get_error_messages();
		
		/***********  REGISTER A NEW USER **********************/
 
		// only create the user in if there are no errors	
		if(empty($errors)) {
 	
 
		$new_user_data=array(
					'user_login'		=> $user_login,
					'user_pass'	 		=> $user_pass,
					'user_email'		=> $user_email,
					'first_name'		=> $user_first,
					'last_name'			=> $user_last,
					'user_registered'	=> date('Y-m-d H:i:s'),
					'role'				=> 'subscriber',
				);
			$new_user_id = wp_insert_user($new_user_data);
			 // we also add an activation meta, this is done in the email file user-emails.php
			
			//On success
		if ( ! is_wp_error( $new_user_id ) ) {
				// Email login credentials to user. new user registration notification is also sent to admin email.
				
				$org_nicename = get_organisation_nicename($user_email);
		
				update_user_meta( $new_user_id, 'organisation',$org_nicename );// on SUCCESS add the @organisation to which they belong to their user-meta. we need this later. Yep its a sting. - other solutions welcomed. 
				update_user_meta( $new_user_id, 'Job Title',$user_job_title );// on SUCCESS add the @organisation to which they belong to their user-meta. we need this later. Yep its a sting. - other solutions welcomed. 
		
				// this sends an email to admin and user with whatever we require to be in there.
				
				wp_new_user_notification($new_user_id,null,'both'); // the function which determines this formatting is in /includes / user-emails.php
		
			#do_action('user_register',$new_user_id);// steves test of triggering this hook.
			
				wp_redirect(home_url('/activate-membership/')); // this is all we need.
				exit; 
		
				}else {pher_errors()->add('registration error', __('There was an error registering  user account'));} // end of 'no errors' clause
		
		}
	}
}
add_action('init', 'pher_add_new_member');




function get_organisation_auth($user_login){  /***** a re-usable function for checking the status of the organisation */
	
	if ( is_email( $user_login ) ) {
		
		$explodedEmail = explode('@', $user_login);
  	 	$user_domain = array_pop($explodedEmail);

		global $wpdb;					
		$approved_domains =array();
		$tablename=$wpdb->prefix.'authorised_orgs';

		
		$results = $wpdb->get_results('SELECT domain FROM '.$tablename.' WHERE authstatus = "allowed" AND domain = "'.$user_domain.'"' ) ; 
		if (!$results){
			return false; // the org is no longer approved
			}
			return true; // the org is still in the list.
			
		}else {
			return false;// edge case for not emails sneaking through the gaps somehow.
	}
	
}



function get_organisation_nicename($user_email){
	if ( is_email( $user_email ) ) {
		global $wpdb;		
		$explodedEmail = explode('@', $user_email);
		$user_domain = array_pop($explodedEmail);
		$tablename=$wpdb->prefix.'authorised_orgs';
		$results = $wpdb->get_results('SELECT title FROM '.$tablename.' WHERE authstatus = "allowed" AND domain = "'.$user_domain.'"' ) ; 

		if (isset ($results[0]->title)){return $results[0]->title;}
		}
}






 
/*  PROTECT the admin from subscribers --- and hide the admin bar from subscribers */

add_action('wp_logout','go_home');
function go_home(){
  wp_redirect( home_url() );
  exit();
}

function pher_restrict_dashboard_to_admin() {
    if ( ! current_user_can( 'manage_options' )  && $_SERVER['PHP_SELF'] != '/wp-admin/admin-ajax.php' ) {
        wp_redirect(home_url('/learning-portal/'));
    }
}
add_action( 'admin_init', 'pher_restrict_dashboard_to_admin', 1 );


function remove_admin_bar() {
	if (!current_user_can('administrator') && !is_admin()) {
	  show_admin_bar(false);
	}
}
add_action('after_setup_theme', 'remove_admin_bar');



// used for tracking error messages-----------------------------------------
function pher_errors(){
    static $wp_error; // Will hold global variable safely
    return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}

// displays error messages from form submissions-----------------------------------------
function pher_show_error_messages() {
	if($codes = pher_errors()->get_error_codes()) {
		echo '<div class="pher_notice error">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = pher_errors()->get_error_message($code);
		        echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		    }
		echo '</div>';
	}	
}

/******* Store timestamp of a user's last login as user meta ********/

function user_last_login( $user_login, $user ){
    update_user_meta( $user->ID, '_last_login', time() );
}
add_action( 'wp_login', 'user_last_login', 10, 2 );

/******** the staff college logo for use in all mails to avoid the weird server which blocks any images !*/

function get_logo_encoded(){
	/*
$logo= '<img width="200" height="100" title="The Staff College Logo"  alt="The Staff College Logo" src="data:image/png;base64,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ERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERGRUTSwd6LP5ZcuYF487xwCm3v7W6ve7/f2t3JB87nP5/JLR75FHwLbwOre/tZh2HI8yzsCdvf2txY8n1WACjBrl7drl3cQsowVYAWYAlrA9t7+1npI+pxVzzRTdv5FYAY4sHlYt9uoEbKMzb39rWU7v0vzifn39rcOfXn1OrDLWA/JV+i2C9h/zoltaaetABs2Pasxy0madmd1b39rPUneYvZT2PF2fHwEHHuRx7BfwmNhEbMf3XJbdltse5YTtN7jYz5k3aHHWNR5kiT/MeeWVwuzfTbT5CXBvr+AOVcX9va3WlFpB/J7+1sHdnts2O0Ruf3stGt22tTneFwe5/JLd2P2Sd53zLvzxnuMH59fSfdpQLq8ywg9J5J4bNIJ+8gdTLPAylx+ibATMILbIW45FftZ2uUwl1/asPMf2LTNYHbQLJAPmL6CObhadp5ZYG0uv9Ta29/aDUif07LzTwHuBGjZdc7S2ambdLbRouczgJadv4E5UPzzz8/llxZ8J4U3LfM2rYfekzol/3xBQbbi+R22T04cB8DiXH7pXETanVbI9yfylnA/ZZXmGI46FtYweffnozGXXzoVfD3rnaGzjZf9K0xwjJ2ap4dcXqbsOjfm8kvze/tbJd90cXmJ2/dTmO3kP2a837c8N4EueLi0RV0z3LS7dppU53iCPG7adVQwN43Oom++Yyn3aeA52otzYuABZG9/6zijc/mlfcxGS3vhb/mWc4TZGKnM5ZfmMTvRXyIJiuLOIsDe/tY57/S+HXAifT6upLO6d/JuuYK5GzjEHgxz+aUZzB2fN68rmIuMf/4VzMHhPyiP02IPwu/YPGQKIBH5cumYp3OQz87llypBwSogT+5i6j0Worbjie8D8pZkP2WS8hgOzIPdTiuYi1TJc/ftLhQrc/mlXW96feu92643aPtEHmNJ85mR/9xsYG4OFr2lqgR5idv3YC7MawRvA++NF3b5uy6QxRwLi3b9J64JKc5xIDKP23SClCvVzGDOa3cN8Eu8TyPS1fU5MfAA4nNAcJErMc/F3h/9k3AR/8TJH7NBW5g7/QuYHbeb8qJUAQ79xcYUJYKw+dfthdgfQIIEVnv0iLvTKgF32/8j82bTXiHbzYSfy1u3+ymprMfw8bHnvWDYqohlzLZzd8FBpiKW3e0x1kurmHy4u/8gUXmJsg1UbKD1L9tfQnDHwwbmIh13js/a4LdLb46d4zzafbxp0z5rq+HceRO2j3qxT7s+J4YmgNgL/zzhJ0iUeV895AHxF80gMwDeetQE1ulUc80DzOWXtoFlz4XAnz489cYzZMuzN81h87c4fTGbtScNnu8yX0gC6n8X3EFo76IWMfXeB/YkWUl4lxN0IY7ajhCdtyT7Kao+O1bCYzgsD6HH3t7+Vmsuv4SbxrM+l9cZ+xN2zCc+xrrJfxL2OABfkEiQl7h9D+Zu3lWTtXzTnvNNW8JU/VYwF+4DzLEbVAXrpnWBL+05niSPm3TaMZfteg4izpNu9qk7RxOdE1EGHkB8mUvabuHPnGuvgE79YlhRNna5nruAWHZDl2xVg6sCcw1zbv1B9aPetKeubks4v2tY83L1vU6py7upqDYQt54Ze/LMeD6PW2dQ2qO2I0TkLeF+gtP5qRAj5TEclofQY28uv+T2r397eNMW1EbipDnGUuc/DXtTAafP4bi8xO17dyfvSrobMdMeAOfstl3EVB9eIKCd0zOtq1ZygSTpOe6E5tHeJLQw1Xu7mOM/6jjqZp8e2HUmPSdCDTyA0MncAaYo6U4SV7w6riv17ED/xefAVw/ogkjaALKN2YBrgL8NZCqgWHxcZWYvVNvAtr2geO8Wo+pHtzF35SsBdZm7IXdESeZfIfggdL1SXDtD1uoCILYNxJ0w/pLE4lx+aSYsb560++9C4+qZQ/OWcD+dyo/dD3HCjuEgYXlw7TRrc/klfxvImp3GX7edsxdA14snTOJjLGP+08ialyRtIO5CvOpZTyBP3luYziizhFQ9+rbTJrCZ8hx3aYvLo2tM3/D8HybzPvVMm+iciDLwABKx0V2RrmEj8iHhVS7eqovAIOP5/nj5/ju9vf2tbVuEW7SNoe5OYJ5OFzm/DcwddgtbV2o/9047G7D+lq2vXKdz4Vj0LGPWLsPfW8XPdfUNmn+XkIPQtjO49UYGqqBtF/Hd4d7+1qo9kKcwxWHv9It0qg5WA5bj0t7idACJ2o5xeUuynzJJcmFLsIzdufzSOuZOeN8e82D27RTmjvVUqc1eMNcxF5MTDdMe3R5jkWLOLbfPXC+sGfv9qXTG5CXRvrfLWfdUJwaldwZ7kfZdW4JuEP3Teu/8U6cvJo+uMX2K8MZzJ/E+DTtH6cE5cVHSCc+aPekXMBdBV2w8wFRL+DPofQ4i7KSo+H4Co6ztlbHsmWcWc8H016E65zA706VhCtMQ6j1wvOnzptMVI90yZnzLiD2x7fwLQWnA05snxLKdNrK4H5D2mYjvFj2fn+oebPfdAaerRtz8UzYv/u7HELEdE+QtyX4aKHtSlzDbx1WTtDD7Mao6Y93Os2FLLP7ldnWMJRB1fLjtfVw1EhNww/KSZt+D2f+Bx76rksJcJ1wV1jYBNRZ22rxn2tTneNI82v3klhnX0STNPg07R4f+nBARERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERERGZJP8PJ7vw2Jyb1hwAAAAASUVORK5CYII=" />';
	*/
	
	$logo='<h2 style="color:#57488F; text-transform:uppercase;">The Staff College</h2>';
	
	return $logo;
	
	
}
