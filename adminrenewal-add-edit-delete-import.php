<?php

// Register function to be called when administration pages init takes place
add_action( 'admin_init', 'wpamms_adminrenewal_init' );

// Register functions to be called when renewals are saved
function wpamms_adminrenewal_init() {
	add_action('admin_post_save_wpamms_renewal',
		'wpamms_process_renewal');

	add_action('admin_post_delete_amms_renewal',
		'wpamms_delete_renewal');
}

// Function to be called when new renewals are created or existing renewals
// are saved
function wpamms_process_renewal() {
    $options = get_option( 'wpamms_options' );

	// Check if user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present for security
	check_admin_referer( 'wpamms_renewal_add_edit' );

	global $wpdb;

	// Place all user submitted values in an array
	$member_data = array();
	$member_data['id'] = ( isset( $_POST['id'] ) ? sanitize_text_field($_POST['id']) : '' );
	$member_data['name'] = ( isset( $_POST['name'] ) ? sanitize_text_field($_POST['name']) : '' );
	$member_data['emailaddress'] = ( isset( $_POST['emailaddress'] ) ? sanitize_email($_POST['emailaddress']) : '' );
        if(is_email($member_data['emailaddress']) == false)
            $member_data['emailaddress'] = '';
        else
            $member_data['emailaddress'] = is_email($member_data['emailaddress']);
	$member_data['paymentreceipt'] = ( isset( $_POST['paymentreceipt'] ) ? sanitize_text_field($_POST['paymentreceipt']) : '' );
	$member_data['renewaldate'] = ( isset( $_POST['renewaldate'] ) ? sanitize_text_field($_POST['renewaldate']) : '' );
	$member_data['confirmed'] = ( isset( $_POST['confirmed'] ) ? sanitize_text_field($_POST['confirmed']) : '' );
            
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        if( file_exists($_FILES['filePaymentReceiptToUpload']['tmp_name']) && is_uploaded_file($_FILES['filePaymentReceiptToUpload']['tmp_name'])) {

            $uploadedfile = $_FILES['filePaymentReceiptToUpload'];
            $uploadedfile['name'] = date("Y-m-d-H-i-s") . '_' . $uploadedfile['name'];
            $upload_overrides = array( 'test_form' => false );
            $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );
            if ( $movefile && !isset( $movefile['error'] ) ) {
                //echo "File is valid, and was successfully uploaded.\n";
                //var_dump( $movefile);
                $member_data['paymentreceipt'] = $movefile['url'];
            } else {
                wp_die("Upload PROOF OF PAYMENT Failed! " );
            }
            
        }
        
        
	// Call the wpdb insert or update method based on value
	// of hidden member_id field
	if ( isset( $_POST['id'] ) && $_POST['id'] == 'new' ) {
                $member_data['id'] = ''; 
		$wpdb->insert($wpdb->get_blog_prefix() . 'amms_renewal', $member_data );
        } elseif ( isset( $_POST['id'] ) && is_numeric( $_POST['id'] ) ) {
		$wpdb->update( $wpdb->get_blog_prefix() . 'amms_renewal', $member_data, array( 'id' => intval($_POST['id']) ) );
        }
        
        if ($options['send_email_notification']) {
            $multiple_recipients = array(
                sanitize_email($member_data['emailaddress']),
                sanitize_email($options['membership_admin_email'])
            );
            $subj = "[Notification]  Your Renewal at ".esc_html($options['short_tittle'])." Membership (id=".esc_html($member_data['memberid']).') has been updated';

            $body = "Your Membership Renewal Data : \r\n\r\n";
            $body .= "name = " . esc_html($member_data['name']) . "\r\n";
            $body .= "renewal date = " . esc_html($member_data['renewaldate'])   . "\r\n";

            if($member_data['confirmed'] == 1)
                $body .= "renewal status = CONFIRMED \r\n\r\n ";
            else 
                $body .= "renewal status = FAILED / REJECTED \r\n\r\n ";

            $body .= "In Case Any Incorrect Data, Please Report to Membership Help Desk at = ". sanitize_email($options['membership_admin_email']) . "\r\n\r\n";
            wp_mail( $multiple_recipients, $subj, $body ); 
        }
        
	// Redirect the page to the admin form
	wp_redirect( add_query_arg( 'page', 'wpamms-renewal', admin_url( 'admin.php' ) ) );
	exit;
}

// Function to be called when deleting renewals
function wpamms_delete_renewal() {

	// Check that user has proper security level
	if ( !current_user_can( 'manage_options' ) )
		wp_die( 'Not allowed' );

	// Check if nonce field is present
	check_admin_referer( 'amms_renewal_deletion' );

	if ( !empty( $_POST['renewals_id'] ) ) {
		// Retrieve array of renewals IDs to be deleted
		$renewals_to_delete = $_POST['renewals_id'];

		global $wpdb;

		foreach ( $renewals_to_delete as $renewal_to_delete ) {
			$query = 'DELETE from ' . $wpdb->get_blog_prefix() . 'amms_renewal ';
			$query .= 'WHERE id = ' . intval( sanitize_text_field($renewal_to_delete) );
			$wpdb->query( $query );
		}
	}

	// Redirect the page to the admin page
	wp_redirect( add_query_arg( 'page', 'wpamms-renewal', admin_url( 'admin.php' ) ) );
	exit;
}