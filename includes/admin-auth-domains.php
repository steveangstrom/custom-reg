<?php
/*
authorised domains plugin admin pages

allows management of a list of authorised organisations and  email domains which are able to register member accounts

 
 http://www.sitepoint.com/using-wp_list_table-to-create-wordpress-admin-tables/
 http://codex.wordpress.org/Class_Reference/wpdb
 http://www.paulund.co.uk/creating-custom-tables-wordpress-plugin-activation
 http://stackoverflow.com/questions/9278772/extending-wp-list-table-handling-checkbox-options-in-plugin-administration
 http://www.kvcodes.com/2014/05/wp_list_table-bulk-actions-example/
 http://glennmessersmith.com/pages/custom_columns.html	
 https://www.smashingmagazine.com/2011/11/native-admin-tables-wordpress/
 http://web.archive.org/web/20120819070722/http://mac-blog.org.ua/942
 http://www.prelovac.com/vladimir/improving-security-in-wordpress-plugins-using-nonces/
 http://www.ceus-now.com/bulk-action-hook-for-admin-pages-which-uses-wp-list-table/
 */
 
if ( ! defined( 'ABSPATH' ) ) { exit;} // Exit if accessed directly.

/*********************  get WP table class , should probably fork this and include it in the plugin for safety ******************/

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/************************** CREATE A PACKAGE CLASS *****************************/

class Pheriche_List_Table extends WP_List_Table {
    
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'organisation',     //singular name of the listed records
            'plural'    => 'organisations',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'authstatus':
            case 'domain':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

  
    function column_title($item){
	
	// create a nonce
	$delete_nonce = wp_create_nonce( 'pher_delete_org' );
	$edit_nonce = wp_create_nonce( 'pher_edit_org' );
   

        //Build row actions   
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Edit</a>','org_edit','edit',absint( $item['id'] ), $edit_nonce),
            'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>',esc_attr($_REQUEST['page']),'delete',absint( $item['id'] ), $delete_nonce)
        );
	   
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span> %3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //singular label 
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }
 
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'title'    => 'Organisation',
            'authstatus'  => 'Access Status',
			'domain'  => 'Email Domain'
        );
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'title'     => array('title',false),     //true means it's already sorted
            'authstatus'    => array('authstatus',false),
            'domain'  => array('domain',false)
        );
        return $sortable_columns;
    }

    function get_bulk_actions() {
        $actions = array(
            'bulk-delete'    => 'Delete'	
        );
        return $actions;
    }


     
    function process_bulk_action() {
         global $wpdb;
		 $tablename=$wpdb->prefix.'authorised_orgs'; 
		//Detect when an action is being triggered...
	   #bulk-delete
		if( 'bulk-delete'===$this->current_action() ) {
			$nonce=isset($_REQUEST['_wpnonce'])? $_REQUEST['_wpnonce'] :null; 
			$referer=isset($_REQUEST['_wp_http_referer'])? $_REQUEST['_wp_http_referer'] :null; 
			$organisations= is_array($_REQUEST['organisation'])? $_REQUEST['organisation'] :null; 
			  
		 if(current_user_can( 'edit_pages')){
			  foreach($organisations as $v){$wpdb->delete( $tablename, array( 'id' => $v ) );}
			 }	
		}

		#single row delete			 
		if( 'delete'===$this->current_action() ) {
			$id=isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
			$nonce=isset($_REQUEST['_wpnonce'])? $_REQUEST['_wpnonce'] :null; 
			if ((current_user_can( 'edit_pages') )&&(wp_verify_nonce( $nonce, 'pher_delete_org' ))){
			//
			$wpdb->delete( $tablename, array( 'id' => $id ) );
			}
		####wp_die('row deleter-'.print_r($_REQUEST) );//DEBUG
        }
		
		//see http://web.archive.org/web/20120819070722/http://mac-blog.org.ua/942
		
		if( 'edit'===$this->current_action() ) {
		
		# wp_die('edit-'.print_r($_REQUEST) );
        }
    }

 


    /** ************************************************************************
     *  prepare your data for display.  
     *  query the database, sort and filter the data, and 
     * get it ready to be displayed.  
     **************************************************************************/
    function prepare_items() {
        global $wpdb;

        $per_page = 10;	//records per page
        $current_page = $this->get_pagenum(); 
        $columns = $this->get_columns();
        $hidden = array();//not hiding any columns 
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
		
		
		$paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
		 
		 $offset =      $paged* $per_page;
        /********** get the table  data  QUERY ********/

		$table_name = $wpdb->prefix . 'authorised_orgs';
		$data = $wpdb->get_results( $wpdb->prepare('SELECT SQL_CALC_FOUND_ROWS  * FROM '.$table_name.' ORDER BY '.$orderby.' '.$order.' LIMIT %d OFFSET %d', $per_page, $offset  ), ARRAY_A );  
		
		$total_count = $wpdb->get_var( "SELECT FOUND_ROWS();");

		 $this->set_pagination_args( array(
  		 'total_items' => $total_count,                  //WE have to calculate the total number of items
   		 'per_page'    => $per_page                     //WE have to determine how many items to show on a page
 		 ) );
        $this->items = $data;/* add our *sorted* data to the items property, where it can be used by the rest of the class.*/

    }


}
//END class Pheriche_List_Table

###############################
 
/*******  THIS FUNCTION makes our magical database tables on our PLUGIN activation **********
during dev we may find it doesnt update DB tables schema, but in normal use it ought to be fine.
NOTE - if you want it to update the DB schema - update the $pher_db_version (below), WP will magically update it.

 http://www.paulund.co.uk/creating-custom-tables-wordpress-plugin-activation
 */
 
 
 /*authstatus : allowed,disallowed   */ 
 
 
global $pher_db_version;
$pher_db_version = '1.03';

function pher_install() {
	global $wpdb;
	global $pher_db_version;
	$table_name = $wpdb->prefix . 'authorised_orgs';
	$charset_collate = $wpdb->get_charset_collate();

 
	
	$sql = "CREATE TABLE $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		title varchar (255) NOT NULL,
		authstatus   ENUM( 'allowed','disallowed' )  DEFAULT 'allowed' NOT NULL ,  
		domain varchar(255) DEFAULT '' NOT NULL,
		UNIQUE KEY id (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	add_option( 'pher_db_version', $pher_db_version );
}
register_activation_hook( __FILE__, 'pher_install' );



function pher_install_data() {
	global $wpdb;
	
	$welcome_name = 'Mr. WordPress';
	$welcome_text = 'Congratulations, you just completed the installation!';
	
	$table_name = $wpdb->prefix . 'authorised_orgs';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'name' => $welcome_name, 
			'text' => $welcome_text, 
		) 
	);
}

register_activation_hook( __FILE__, 'pher_install_data' );
 


function myplugin_update_db_check() {
    global $pher_db_version;
    if ( get_site_option( 'pher_db_version' ) != $pher_db_version ) {
        pher_install();
    }
}
add_action( 'plugins_loaded', 'myplugin_update_db_check' );



/** ************************ REGISTER THE TEST PAGE ****************************
 *******************************************************************************
 *   define admin page.add a top-level menu item to the admin menus. define  sub-pages
 */
function pher_add_menu_items(){
    add_menu_page('Authorised Orgs List Table', 'Authorised Orgs', 'activate_plugins', 'pher_list_orgs', 'pher_render_list_page','dashicons-businessman');
	add_submenu_page( 'pher_list_orgs','Add new Organisation', 'Add Organisation', 'activate_plugins', 'pher_add_list_org', 'pher_add_new_org' );	
	add_submenu_page( null, 'Edit','edit','manage_options','org_edit',  'pher_org_edit_page_handler');  //this page is hidden on admin menu 	
	 
} add_action('admin_menu', 'pher_add_menu_items');


#add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
#add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' )

/** *************************** RENDER the ADMIN LISTING PAGE ********************************
 *******************************************************************************
 * This function renders the admin page and the example list table. Although it's
 * possible to call prepare_items() and display() from the constructor, there
 * are often times where you may need to include logic here between those steps,
 * so we've instead called those methods explicitly. It keeps things flexible, and
 * it's the way the list tables are used in the WordPress core.
 */
function pher_render_list_page(){
    if(is_admin()){
    //Create an instance of our package class...
    $orgsListTable = new Pheriche_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $orgsListTable->prepare_items();
    $message = '';
    if ('delete' === $orgsListTable->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>Authorized Organisation deleted </p></div>';
    }
	if ('bulk-delete' === $orgsListTable->current_action()) {
        $message = '<div id="message"><p> Authorized Organisations Deleted </p></div>';
    }
    ?>
    <div class="wrap">
        
        <div id="icon-users" class="icon32"><br/></div>
        <h2>Authorised Companies</h2>
		<?php echo $message; ?>
        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
             <p>    <a class="add-new-h2" href="admin.php?page=pher_add_list_org">Add New</a></p>
        </div>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="orgs-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
			
			 
            <!-- Now we can render the completed list table -->
            <?php $orgsListTable->display() ?>
        </form>
        
    </div>
    <?php
		}//is admin	
	}


###################

##i did not use custom metaboxes  or use the custom list class in a clever way I just built some html forms and procedural php logic  
 
function pher_add_new_org () {

	$title =isset($_POST["title"])?$_POST["title"]:null;
	$authstatus =isset($_POST["authstatus"])?$_POST["authstatus"]:null; 
	$domain =isset($_POST["domain"])?$_POST["domain"]:null;
	  # wp_verify_nonce( $_POST['add_new_org'], 'addorg' ) 

	$message='';

	if ( isset( $_POST['add_new_org']  ) && check_admin_referer('addorg', 'add_new_org') ){ 
	
	$explodedEmail = explode('@', $domain);
	

	if ($explodedEmail[1]){
		//echo 'these idiots entered someones actual mail address rather than a domain';
		$domain = $explodedEmail[1];
	}


	if (isset($domain) && !is_email( 'intentional@'.$domain )){
		$message= '<div class="error notice"><p>The email address is empty or malformed</p></div>'; 
	
		}else{
				global $wpdb;
				$tablename=$wpdb->prefix.'authorised_orgs';
				$wpdb->insert($tablename,array('title' => $title,'authstatus' => $authstatus,'domain'=>$domain),  array('%s','%s','%s')  	);
				$message.='<p>New Organisation added. <br> <a href="admin.php?page=pher_list_orgs"> &laquo; Organisation Access List</a></p>';
		}
	}

	?>

    <link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pher-custom-reg-and-login/css/reg-and-log.css" rel="stylesheet" />
    <div class="wrap">
    <h2>Add New organisation</h2>
    <?php if (isset($message)&&(!empty($message))): ?>
        <div class="updated"><p><?php echo $message;?></p></div>
        <h2>Successfully added: Add another organisation?</h2>
        
    <?php 
	
	$title='';#blank em if its a success
	$domain='';#blank em if its a success
	endif;?>
    
    
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
    
    <table class='wp-list-table '>
    <tr><th>Organisation</th><td><input type="text" name="title" value ="<?php echo $title; ?>" /> Example : London School of Economics</td></tr>
    <tr><th>Access Status</th><td><select  name="authstatus"><option value="allowed">allowed</option>
    <option value="disallowed">disallowed</option></select> </td></tr>
    <tr><th>Email Domain</th><td><input type="text" name="domain" value ="<?php echo $domain; ?>"/>Example : lse.ac.uk</td></tr>
    
    </table>
    <?php  wp_nonce_field('addorg', 'add_new_org'); ?>
    
    <input type='submit' name="insert" value='Save' class='button'>
    </form>
    
    <a href="admin.php?page=pher_list_orgs">&laquo; Return to the list of organisations</a>
    </div>
<?php
}

####################################################################################
function pher_org_edit_page_handler()
	{
	 global $wpdb;
	 $tablename=$wpdb->prefix.'authorised_orgs';
	 $message = '';

 
 if(isset($_POST['orgupdate'])){
 //do database update if theres a post var
 #echo'post!<pre>';print_r($_REQUEST);echo'</pre>';
  if(wp_verify_nonce($_REQUEST['_wpnonce'],'pher_edit_org' ) &&  ( current_user_can( 'manage_options' ) )) {
		$id=isset($_GET['id']) ? $_GET['id'] : null;
		$title=isset($_POST['title']) ? $_POST['title'] : null;
		$authstatus=isset($_POST['authstatus']) ? $_POST['authstatus'] : null;
		$domain=isset($_POST['domain']) ? $_POST['domain'] : null;
	#$wpdb->show_errors = true ;
    #$wpdb->suppress_errors = false;
 
	  $updated=$wpdb->update( $tablename, array( 'title' => $title,'authstatus'=>$authstatus,	 'domain' => $domain), array( 'id' => $id ), array('%s'), array( '%d' )  );	
 	//echo($wpdb->last_query); //debug
	
	
	if ( false === $updated ) {
		$message= '<div class="error notice"><p>There was an error updating database</p></div>'; 
		} else {
		$message='<div class="updated notice"><p>Item was successfully updated</p></div>';
		} 
	
	 // http://wordpress.stackexchange.com/questions/16382/showing-errors-with-wpdb-update
	 ?>
<div class="wrap">
<h2>Edit organisation access</h2>
	<?php
 echo ($message.' &nbsp; <a href="'.admin_url( '?page=pher_list_orgs' ) .'">Back to Organisation access list</a>');  
 ?>
 
 </div>
		<?php	
 }
 }else{  //print  form  
		 #echo'GET!';print_r($_REQUEST);}
		$id=isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
		$title=isset($_REQUEST['title']) ? $_REQUEST['title'] : null;
		$authstatus=isset($_REQUEST['authstatus']) ? $_REQUEST['authstatus'] : null;
		$domain=isset($_REQUEST['authstatus']) ? $_REQUEST['authstatus'] : null;
        if( is_numeric($id)){
			$result = $wpdb->get_row($wpdb->prepare(  "SELECT * FROM $tablename WHERE  id = %d",$id ));
 
			# print_r($result->authstatus);
			 
			$title=$result->title;
		 	$domain=$result->domain;
			$authstatus=$result->authstatus;
			
			
			
			?>
			<link type="text/css" href="<?php echo WP_PLUGIN_URL; ?>/pher-custom-reg-and-login/css/reg-and-log.css" rel="stylesheet" />
			<div class="wrap">
			<h2>Edit organisation access</h2>
			<?php if (isset($message)&&(!empty($message))): ?><div class="updated"><p><?php echo $message;?></p></div><?php endif;?>
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
			<table class='wp-list-table '>
			<tr><th>Organisation</th><td><input type="text" name="title" value="<?php echo $title ; ?>"/></td></tr>
			<tr><th>Access Status</th><td><select  name="authstatus">
			<option value="allowed" <?php if ($authstatus=='allowed'){ echo ' selected ';} ?> >allowed</option>
			<option value="disallowed" <?php if ($authstatus=='disallowed'){echo ' selected  ';} ?> >disallowed</option></select></td></tr>
			<tr><th>Email Domain</th><td><input type="text" name="domain" value="<?php  echo $domain; ?>"/></td></tr>
	
			</table>
			<?php  wp_nonce_field('editorg', 'edit_org'); ?>
	
			<input type='submit' name="orgupdate" value='Save' class='button'> <p>or <a href="admin.php?page=pher_list_orgs" title ="return to the list of organisations">Cancel</a> and return to the list of organisations</p>
			</form>
			</div> 
	
			<?php
		 }
 

	}//end func edit_page_handler

 }
?>