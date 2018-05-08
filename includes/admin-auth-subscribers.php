<?php
/*
* admin-auth-subscribers.php  23-06-16
* admin list table showing subscribers from authorised organisations
* http://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/
*/
if ( ! defined( 'ABSPATH' ) ) { exit;} // Exit if accessed directly.
if(!class_exists('WP_List_Table')){require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );}
/*********************  get WP table class , should probably fork this and include it in the plugin for safety ******************/
################################

/*  extends the WP users list table so we can filter it and only show the subscribers */
class Pheriche_Subscriber_List_Table extends WP_List_Table { 
    function __construct(){
        global $status, $page;       
        //Set parent defaults
        parent::__construct( array(
          	'singular' => 'user',
			'plural'   => 'users',
            'ajax'      => false       
        ) );  
    }
	public function column_default($item, $column_name) {
		if(isset($item[$column_name])){
			return $item[$column_name];
			}
		
	}
 
  /*
   example, how to render specific column
    method name must be like this: "column_[column_name]"
  render column with actions, * when you hover row "Edit | Delete" links showed
  */
    function column_user_login($item){
		$delete_nonce = wp_create_nonce( 'pher_delete_subs' );
		//Build row actions   
        $actions = array(
		  'edit'      => sprintf('<a href="user-edit.php?user_id=%s">Edit</a>',absint( $item['ID']) ), 
          'delete'    => sprintf('<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>',esc_attr($_REQUEST['page']),'delete',absint( $item['ID'] ), $delete_nonce)
        );
        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span> %3$s',
            /*$1%s*/ $item['user_login'],
            /*$2%s*/ $item['ID'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //singular label 
            /*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
        );
    }
 
    function get_columns(){
        $columns = array(
			'cb'       => '<input type="checkbox" />',
			'user_login' => __( 'Username' ),
			'display_name'     => __( 'Name' ),
			'user_email'    => __( 'Email' ),
			'organisation'    => __( 'Organisation' ),  
		);
        return $columns;
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'display_name'     => array('username',false),     //true means it's already sorted
            'user_email'    => array('email',false),
			'organisation'    =>array('organisation',false),
        );
        return $sortable_columns;
    }
 

    function get_bulk_actions() {
        $actions = array(
            'bulk-delete'    => 'Delete'	//action=>label
        );
        return $actions;
    }
	
 
	/*process (bulk) actions eg delete,edit
     * it can be outside of class
     * it can not use wp_redirect coz there is output already
	*/
	    function process_bulk_action() {
         global $wpdb;
		 $tablename=$wpdb->prefix.'users'; 
		//Detect when an action is being triggered...
	   #bulk-delete  
		if( 'bulk-delete'===$this->current_action() ) {
				$nonce=isset($_REQUEST['_wpnonce'])? $_REQUEST['_wpnonce'] :null; 
				$referer=isset($_REQUEST['_wp_http_referer'])? $_REQUEST['_wp_http_referer'] :null; 
				$users= is_array($_REQUEST['user'])? $_REQUEST['user'] :null; 
				if(current_user_can( 'edit_pages')){
				  foreach($users as $v){$wpdb->delete( $tablename, array( 'ID' => $v ) );}
				 }	
			}
		/*	#single row delete	*/		 
		if( 'delete'===$this->current_action() ) {
		 
			$id=isset($_REQUEST['id']) ? $_REQUEST['id'] : null;
			$nonce=isset($_REQUEST['_wpnonce'])? $_REQUEST['_wpnonce'] :null; 
			if ((current_user_can( 'edit_pages') )&&(wp_verify_nonce( $nonce, 'pher_delete_subs' ))){
			$wpdb->delete( $tablename, array( 'ID' => $id ) );
			}
        }
    }
	
	
	

    /** ************************************************************************
     *  prepare your data for display.  
     *  query the database, sort and filter the data, and get it ready to be displayed. 
     **************************************************************************/
    function prepare_items() {
        global $wpdb;			//$role,$usersearch,  ?

		$users_per_page = 10;
		$paged = $this->get_pagenum();
		$usersearch = isset( $_REQUEST['s'] ) ? sanitize_text_field($_REQUEST['s'])   : '';
		$this->process_bulk_action();

		/********** get the table  data  QUERY WP_User_Query  ********/
		 //OR use wrapper function   get_users( $args )
		// the below args are set up for a search, although I've not added such a box yet.

		$args = array(
				'number' => $users_per_page,
				'offset' => ( $paged-1 ) * $users_per_page,
				'role' => 'Subscriber',
				'search' => $usersearch,
				'fields' => 'all_with_meta',
				'orderby'=>'ID',
				'order'=>'asc',
				'count_total'=>true,
			);
	
		if ( '' !== $args['search'] ){	$args['search'] = '*' . $args['search'] . '*';} 
		
		$orderbyArr=array('username','email','organisation');
		$args['orderby']=isset( $_REQUEST['orderby'] ) && in_array($_REQUEST['orderby'],$orderbyArr)?$_REQUEST['orderby']:'';
		$orderArr=array('asc','desc');
		$args['order'] = isset($_REQUEST['order']) && in_array($_REQUEST['order'],$orderArr)?$_REQUEST['order']:'';
		
		

		$wp_user_search = new WP_User_Query( $args );
  		$row =$wp_user_search->get_results(); 
	 
        # >>>> Pagination
		foreach($row as $var){
			$user_array_row = get_object_vars($var->data); // convert each row  to an array
			//   The WP_user_query does not returning meta key organisation,get it from the user_meta table  by ID and push it into the array/user object .
			 $user_ID=$user_array_row['ID'];
			$org = get_user_meta( $user_ID,'organisation','true' );
				if(!empty($org )){$user_array_row['organisation']=$org;}
			 #echo('<pre>');print_r($user_array_row);echo('</pre>');// spew yer guts.
			$items[]=$user_array_row; 	//push it into the main array. . array of user  objects.	
			
		}

		$columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $items;

		$this->set_pagination_args( array(
			'total_items' => $wp_user_search->get_total(),
			'per_page' => $users_per_page,
		) );
    }
}//end CLASS Pheriche_Subscriber_List_Table

 

###############################################################################

function pher_add_subscriber_menu(){
    add_menu_page('Subscribers', 'Subscribers', 'activate_plugins', 'pher_list_subs', 'pher_render_subs_list_page','dashicons-businessman'); 
} add_action('admin_menu', 'pher_add_subscriber_menu');


function pher_render_subs_list_page(){
    if(is_admin()){
    //Create an instance of our package class...
	$subsListTable = new Pheriche_Subscriber_List_Table();
   //
   
   //Fetch, prepare, sort, and filter our data...
   
   
   
    $subsListTable->prepare_items();
   ###########################
	   $message = '';
		if ('delete' === $subsListTable->current_action()) {
			$message = '<div class="updated below-h2" id="message"><p>Subscriber deleted </p></div>';
		}
		if ('bulk-delete' === $subsListTable->current_action()) {
			$message = '<div class="updated below-h2" id="message"><p> Subscribers Deleted </p></div>';
		}
    ?>
    <div class="wrap">
		<div id="icon-users" class="icon32"><br/></div>
			<h2>Authorised Subscribers</h2>
			 
			<?php echo $message; ?>
			<div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;"> </div>
			<form id="subs-filter" method="get">
			<?php $subsListTable->search_box('search', 'search_subs'); ?>
				
					<!-- For plugins, we also need to ensure that the form posts back to our current page -->
					<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
					<?php 
					$subsListTable->display();//render  list table  
					?>
			</form>  
    </div>
    <?php
   }//is admin	
}  #end pher_render_subs_list_page
 

/************************************************************/


/**/
add_action( 'show_user_profile', 'pher_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'pher_extra_user_profile_fields' );
add_action( 'personal_options_update', 'pher_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'pher_save_extra_user_profile_fields' );
 
function pher_save_extra_user_profile_fields( $user_id ){
 if ( !current_user_can( 'edit_user', $user_id ) ) { return false; }
 
 	 echo($_POST['membership_status']);
	 update_user_meta( $user_id, 'membership_status', $_POST['membership_status'] );
	
 }

function pher_extra_user_profile_fields( $user ){ ?>
 <h3>Account Activation</h3>
    <table class="form-table">
    <tr>
    <th><label for="membership_status">Activation Status</label></th>
    <td>
    <?php  $activationstatus = get_user_meta( $user->ID, 'membership_status', true );?>
    
   <!-- <input type="text" id="membership_status" name="membership_status" size="20" value="<?php echo $activationstatus; ?>">-->
    
    <select  name="membership_status">
    <option value="pending-activation" <?php if ($activationstatus=='pending-activation'){ echo ' selected ';} ?> >pending-activation</option>
	<option value="activated" <?php if ($activationstatus=='activated'){echo ' selected  ';} ?> >activated</option>
    </select></td></tr>
    <span class="description"> <?php echo get_user_meta( $user->ID, 'has_to_be_activated', true );?></span>
    </td>
    </tr>
    </table>
    
<?php }
?>