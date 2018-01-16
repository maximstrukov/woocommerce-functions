<?php
/**
 * Plugin Name: Coaching Site Custom Actions (THM)
 * Description: It commits the actions for particular user on one of the sites when some other actions are done by them on the other site.
 * Version: 1.4.1
 * Author: Max Strukov ( Miller Media )
 * Author URI: www.millermedia.io
 */

// For the time being, this plugin uses functions that were deprecated below WP 4.6
if ( version_compare( $wp_version, '4.6', '<' ) ) {
	add_action( 'admin_notices', create_function( '', "echo '<div class=\"error\"><p>".__('The Coaching Site Custom Actions plugin requires WordPress 4.6 or higher.', 'plugin-name') ."</p></div>';" ) );
	return;
}

 
// assigning user to membership on Coaching site after payment is complete on Store site
function action_woocommerce_payment_complete($order_id) {
	$payment_method = get_post_meta( $order_id, '_payment_method', true );
	if ($payment_method != 'cheque') assign_on_payment($order_id);
};

function action_woocommerce_order_status_completed($order_id) {
	$payment_method = get_post_meta( $order_id, '_payment_method', true );
	if ($payment_method == 'cheque') assign_on_payment($order_id);
}

function assign_on_payment($order_id) {

	//checking if the user is on the Store site
	$current_site = get_current_blog_id();
	$network_admin_settings = get_site_option('network_admin_settings');
	$store_site = $network_admin_settings['store'];
	$coaching_plan = $network_admin_settings['coaching_plan'];

	$selected_product = false;
	
	$product_for_custom_email = $network_admin_settings['product_for_custom_email'];
	$order = new WC_Order($order_id);
	$items = $order->get_items();
	foreach ( $items as $item ) {
		if ($item['product_id'] == $product_for_custom_email) {
			$selected_product = true;
			break;
		}
	}
	if ($current_site == $store_site && $coaching_plan && $selected_product) {
		
		//$user_id = get_current_user_id();
		$user_id = $order->get_user_id();
		//switching to the Coaching site
		switch_to_blog($network_admin_settings['coaching']);

		if (function_exists( 'wc_memberships' ) ) {
			
			//adding user to the Coaching site
			add_user_to_blog($network_admin_settings['coaching'], $user_id, 'customer');
			$args = array(
				'plan_id'	=> $coaching_plan,
				'user_id'	=> $user_id,
			);
			//adding user to particular membership
			$res = wc_memberships_create_user_membership( $args );
			
		} else {
			echo "Can't load the wc_memberships";
		}
		restore_current_blog();
		
	}
}

// after test is finished and successful on Coaching site
function action_after_quiz_submit($result) {

	global $test, $wpdb;
	
	$data =	$test->get_result_data($result);
	if ($data['ranking']) {
	
		//checking if the user is on the Coaching site
		$current_site = get_current_blog_id();
		$network_admin_settings = get_site_option('network_admin_settings');
		$coaching_site = $network_admin_settings['coaching'];
		$membership_plan = $network_admin_settings['membership_plan'];
		
		if ($current_site == $coaching_site) {
			$user_id = get_current_user_id();
			
			//checking if this user has passed all necessary tests before
			$coaching_tests = $network_admin_settings['coaching_tests'];
			$passed = false;
			if (!is_null($coaching_tests) && in_array($test->id, $coaching_tests)) {
				if (count($coaching_tests) == 1) $passed = true;
				else {
					$prev_tests = $wpdb->get_results("SELECT test_id, percent FROM ".$wpdb->prefix."quizmaker_results WHERE user_id=".$user_id." AND test_id != ".$test->id." AND test_id IN (".implode(',',$coaching_tests).")");
					foreach ($prev_tests as $prev_test) {
						$test_meta = get_post_meta($prev_test->test_id);
						$test_settings = unserialize($test_meta["_test_settings"][0]);
						$certificate = false;
						foreach ($test_settings["ranking"] as $ranking) {
							if ($prev_test->percent >= $ranking["min"]) {
								$certificate = true;
								break;
							}
						}
						if ($certificate) {
							$passed = true;
							break;
						}
					}
				}
			}
			if ($passed) {
				// assigning user to membership plan on Membership site
				if ($membership_plan) {
					switch_to_blog($network_admin_settings['membership']);
					//adding user to the Membership site
					add_user_to_blog($network_admin_settings['membership'], $user_id, 'customer');
					if (function_exists( 'wc_memberships' ) ) {
						
						$exists = wc_memberships_is_user_member($user_id, $membership_plan);
						if ($exists) {
							//updating the existing membership
							$membership = $wpdb->get_row("SELECT ID, post_status FROM ".$wpdb->prefix."posts WHERE post_parent=".$membership_plan." AND post_author=".$user_id);
							if ($membership->post_status != 'wcm-active') {
								$wpdb->update($wpdb->prefix."posts", array("post_status"=>"wcm-active"), array("ID"=>$membership->ID));
								$start_date = date("Y-m-d H:i:s");
								$end_date = date("Y-m-d H:i:s", strtotime("+".$network_admin_settings['membership_plan_length']));
								update_post_meta($membership->ID, "_start_date", $start_date);
								update_post_meta($membership->ID, "_end_date", $end_date);
							}
						} else {
							$args = array(
								'plan_id'	=> $membership_plan,
								'user_id'	=> $user_id,
							);
							//adding user to particular membership
							$res = wc_memberships_create_user_membership( $args );
							$end_date = date("Y-m-d H:i:s", strtotime("+".$network_admin_settings['membership_plan_length']));
							update_post_meta($res->id, "_end_date", $end_date);
						}
					}
					restore_current_blog();
				}
				
				// assigning the user to the Store site
				switch_to_blog($network_admin_settings['store']);
				add_user_to_blog($network_admin_settings['store'], $user_id, 'customer');
				
				// and adding the chosen roles to him
				$user = new WP_User($user_id);
				foreach ($network_admin_settings['assign_roles'] as $role):
					$user->add_role($role);
				endforeach;
				
				
				$tote_product_ordered = get_user_meta($user_id, "tote_product_ordered", true);
				if (!$tote_product_ordered) {
				
					//creating the order for this user on the Store site
					$product = wc_get_product($network_admin_settings["product_for_order"]);
					$order = wc_create_order();
					$order->add_product( $product , 1 );
					$order->update_status('processing');
					
					// assign the order to the current user
					update_post_meta($order->get_order_number(), '_customer_user', $user_id );
					// getting user shipping address and assigning it to the order
					$order->set_shipping_first_name(get_user_meta( $user_id, 'shipping_first_name', true ));
					$order->set_shipping_last_name(get_user_meta( $user_id, 'shipping_last_name', true ));
					$order->set_shipping_company(get_user_meta( $user_id, 'shipping_company', true ));
					$order->set_shipping_address_1(get_user_meta( $user_id, 'shipping_address_1', true ));
					$order->set_shipping_address_2(get_user_meta( $user_id, 'shipping_address_2', true ));
					$order->set_shipping_city(get_user_meta( $user_id, 'shipping_city', true ));
					$order->set_shipping_state(get_user_meta( $user_id, 'shipping_state', true ));
					$order->set_shipping_country(get_user_meta( $user_id, 'shipping_country', true ));
					$order->set_shipping_postcode(get_user_meta( $user_id, 'shipping_postcode', true ));
					$order->calculate_totals();
					
					update_user_meta($user_id, "tote_product_ordered", 1);
				}
				restore_current_blog();
			}
		}
	}
	
}

function payment_complete_enqueue_script() {
	wp_enqueue_script( 'network_admin_custom_script', plugins_url( 'js/custom.js', __FILE__ ) );
	wp_enqueue_style( 'network_admin_custom_script', plugins_url('css/custom.css', __FILE__) );
}

/**
 * @param $email_heading
 * @param WC_Email $email
 * @return bool
 */
function custom_email_header($email_heading, $email = null) {
	if ( !$email || !is_object($email) )
		return false;

	if ( !($email->object instanceof WC_Order) ) {
		return false;
	}
		
	$network_admin_settings = get_site_option('network_admin_settings');
	$product = $network_admin_settings['product_for_custom_email'];
	$text = $network_admin_settings['text_for_custom_email'];
	$order = $email->object;
	$order_status = $order->get_status();
	$template_html = $email->template_html;
	if ($product && $text && in_array($order_status, array('on-hold', 'processing', 'completed')) && !stristr($template_html, 'admin')) {
		$proper_product = false;
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ($item['product_id'] == $product) {
				$proper_product = true;
				break;
			}
		}
		if ($proper_product) echo stripslashes_deep($text);
	}
}



add_action( 'woocommerce_payment_complete', 'action_woocommerce_payment_complete', 10, 1 );

add_action( 'woocommerce_order_status_completed', 'action_woocommerce_order_status_completed', 10, 1 );

add_action('admin_enqueue_scripts', 'payment_complete_enqueue_script');

add_action('quizmaker_after_submit_result', 'action_after_quiz_submit', 20, 1);

add_action('woocommerce_email_header', 'custom_email_header', 50, 2);

// Safe retrieving of the customer_completed_order main text to avoid errors
add_filter('pre_option_ec_woocommerce_customer_completed_order_main_text', function($value) {
	global $wpdb;
	$option = 'ec_woocommerce_customer_completed_order_main_text';
	$value = wp_cache_get( $option, 'options' );
	if ( false === $value ) {
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) );

		// Has to be get_row instead of get_var because of funkiness with 0, false, null values
		if ( is_object( $row ) ) {
			$value = $row->option_value;
			$site_name = get_option('blogname');
			$value = str_replace('[ec_site_name]', $site_name, $value);
			wp_cache_add( $option, $value, 'options' );
			
		} else { // option does not exist, so we must cache its non-existence
			if ( ! is_array( $notoptions ) ) {
				 $notoptions = array();
			}
			$notoptions[$option] = true;
			wp_cache_set( 'notoptions', $notoptions, 'options' );

			// This filter is documented in wp-includes/option.php
			return apply_filters( 'default_option_' . $option, $default, $option );
		}
	}
	return $value;
});

//Sending 'Order complete' email to customer only if the 'Coaching certification' product is included in the order
add_filter('woocommerce_email_enabled_customer_completed_order', function($value, $order) {
	$network_admin_settings = get_site_option('network_admin_settings');
	$product = $network_admin_settings['product_for_custom_email'];
	if ($product && $value) {
		$proper_product = false;
		$items = $order->get_items();
		foreach ( $items as $item ) {
			if ($item['product_id'] == $product) {
				$proper_product = true;
				break;
			}
		}
		if (!$proper_product) $value = false;
	}
	return $value;
}, 10, 2);

include('network_settings.php');