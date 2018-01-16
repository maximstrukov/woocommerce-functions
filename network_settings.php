<?php 

/*------------------------------------*/
/* create network settings menu */
/*------------------------------------*/

class Network_Settings {
	
    private $updated;
    /**
      * This method will be used to register
      * our custom settings admin page
      */
 
    public function init() {
        // register page
        add_action('network_admin_menu', array($this, 'setupTabs'));
        // update settings
        add_action('network_admin_menu', array($this, 'update'));
    }
 
    /**
      * This method will be used to register
      * our custom settings admin page
      */
 
    public function setupTabs() {
		
		add_menu_page('Coaching Site', 'Coaching Site', 'manage_options', 'site-purpose', array($this, 'screen'));
 
        return $this;
    }
 
    /**
      * This method will parse the contents of
      * our custom settings age
      */
 
    public function screen() {
		global $wpdb;

		$sites = get_sites();
		$network_admin_settings = get_site_option('network_admin_settings');

		$roles_option = $wpdb->get_var("SELECT option_value FROM wp_".$network_admin_settings['store']."_options WHERE option_name='wp_".$network_admin_settings['store']."_user_roles'");
		$user_roles = unserialize($roles_option);
		
		if (count($sites) > 2) {
		?>
			<h2>Set Sites Purposes</h2>

			<?php if (isset($_POST['submit'])) : ?>

				<?php if ( $this->updated ) : ?>
					<div class="updated notice is-dismissible">
						<p><?php echo 'Settings updated successfully!'; ?></p>
					</div>
				<?php else : ?>
					<div class="error notice is-dismissible">
						<p><?php echo 'Error!<br/>All selected sites should be different!<br/>All membership plans should be assigned!'; ?></p>
					</div>
				<?php endif; ?>

			<?php endif; ?>

			<form method="post" action="">

				<table class="form-table">
					<tr valign="top">
					<th scope="row">Store</th>
					<td>
						<select name="store">
							<?php foreach ($sites as $site): ?>
							<option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($network_admin_settings['store'], $site->blog_id); ?>><?php echo esc_attr(get_blog_details($site->blog_id)->blogname); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Assigned roles</th>
					<td>
						<select name="store_roles[]" multiple="multiple" size="<?php echo count($user_roles) ?>">
							<?php foreach ($user_roles as $role => $values): ?>
							<option value="<?php echo esc_attr($role); ?>" <?php selected(@in_array($role, $network_admin_settings['assign_roles'])); ?>><?php echo esc_attr($values['name']); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>
					
					<tr><td colspan=2 class="div_line"><hr align="left" width="45%"></td></tr>

					<tr valign="top" class="plan_row">
					<th scope="row">Membership</th>
					<td>
						<select name="membership" class="select_plan" id="membership">
							<?php foreach ($sites as $site): ?>
							<option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($network_admin_settings['membership'], $site->blog_id); ?>><?php echo esc_attr(get_blog_details($site->blog_id)->blogname); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>
					<tr valign="top">
					<th scope="row">Membership plan for this site</th>
					<td>
						<select name="membership_plan" id="membership_plan" _id="<?php echo $network_admin_settings['membership_plan'] ?>"></select><span>Memberhip plans are not available for this site</span>
					</td>
					</tr>
					<tr valign="top" id="memberhip_length_row">
					<th scope="row">The length of this plan</th>
					<td>
						<select name="membership_plan_length" id="membership_plan_length">
							<option value="3 months" <?php @selected($network_admin_settings['membership_plan_length'], "3 months"); ?>>3 months</option>
							<option value="6 months" <?php @selected($network_admin_settings['membership_plan_length'], "6 months"); ?>>6 months</option>
							<option value="1 year" <?php @selected($network_admin_settings['membership_plan_length'], "1 year"); ?>>1 year</option>
						</select>
					</td>
					</tr>
					
					<tr><td colspan=2 class="div_line"><hr align="left" width="45%"></td></tr>

					<tr valign="top" class="plan_row">
					<th scope="row">Coaching</th>
					<td>
						<select name="coaching" class="select_plan">
							<?php foreach ($sites as $site): ?>
							<option value="<?php echo esc_attr($site->blog_id); ?>" <?php selected($network_admin_settings['coaching'], $site->blog_id); ?>><?php echo esc_attr(get_blog_details($site->blog_id)->blogname); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>
					<tr valign="top" class="plan_row">
					<th scope="row">Membership plan for this site</th>
					<td>
						<select name="coaching_plan" id="coaching_plan" _id="<?php echo $network_admin_settings['coaching_plan'] ?>"></select><span>Memberhip plans are not available for this site</span>
					</td>
					</tr>
					<tr valign="top" id="coaching_length_row">
					<th scope="row">The length of this plan</th>
					<td>
						<select name="coaching_plan_length" id="coaching_plan_length">
							<option value="3 months" <?php @selected($network_admin_settings['coaching_plan_length'], "3 months"); ?>>3 months</option>
							<option value="6 months" <?php @selected($network_admin_settings['coaching_plan_length'], "6 months"); ?>>6 months</option>
							<option value="1 year" <?php @selected($network_admin_settings['coaching_plan_length'], "1 year"); ?>>1 year</option>
						</select>
					</td>
					</tr>
					<tr><td colspan=2 class="div_line"><hr align="left" width="45%"></td></tr>

				</table>

				<?php wp_nonce_field('site_purpose_nonce', 'site_purpose_nonce'); ?>
				<?php submit_button(); ?>

			</form>
			<hr align="left" width="45%">
		<?php
		} else {
		?>
			<h2>You should have 3 sites installed to set the purposes</h2>
		<?php
		}

		$products = $wpdb->get_results('SELECT ID, post_title FROM wp_'.$network_admin_settings['store'].'_posts WHERE post_type = "product" AND post_status="publish"');
		if (count($products) > 0) {
		?>
			<h2 class="sub_header">Customizing email on processing order</h2>

			<form method="post">

				<table class="form-table">

					<tr valign="top" class="plan_row">
					<th scope="row">Select Product</th>
					<td>
						<select name="product_for_custom_email">
							<?php foreach ($products as $product): ?>
							<option value="<?php echo esc_attr($product->ID); ?>" <?php selected($network_admin_settings['product_for_custom_email'], $product->ID); ?>><?php echo esc_attr($product->post_title); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>
					<tr valign="top">
					<th scope="row">Text to include in email content</th>
					<td>
						<textarea name="text_for_custom_email" rows="3"><?php echo stripslashes_deep($network_admin_settings['text_for_custom_email']); ?></textarea>
					</td>
					</tr>

				</table>

				<?php wp_nonce_field('email_options_nonce', 'email_options_nonce'); ?>
				<?php submit_button(); ?>

			</form>
			<hr align="left" width="45%">

			<h2 class="sub_header">The Tote product on the Store</h2>

			<form method="post">

				<table class="form-table">

					<tr valign="top" class="plan_row">
					<th scope="row">Select Product</th>
					<td>
						<select name="product_for_order">
							<?php foreach ($products as $product): ?>
							<option value="<?php echo esc_attr($product->ID); ?>" <?php selected($network_admin_settings['product_for_order'], $product->ID); ?>><?php echo esc_attr($product->post_title); ?></option>
							<?php endforeach;?>
						</select>
					</td>
					</tr>

				</table>

				<?php wp_nonce_field('order_product_nonce', 'order_product_nonce'); ?>
				<?php submit_button(); ?>

			</form>
			<hr align="left" width="45%">
		<?php
		} else {
		?>
			<h2 class="sub_header">You should have the products to be added on the Store site</h2>
		<?php
		}

		if (is_null($network_admin_settings['coaching_tests'])) echo "None";
		$tests = $wpdb->get_results('SELECT ID, post_title FROM wp_'.$network_admin_settings['coaching'].'_posts WHERE post_type = "test" AND post_status="publish"');
		if (count($tests) > 0) {
		?>
			<h2 class="sub_header">The Tests on the Coaching site that should be passed<br>to have all corresponding actions on the Store site done</h2>

			<form method="post">

				<table class="form-table coaching-tests">
					<?php foreach ($tests as $test): ?>
					<tr valign="middle">
						<td><input type="checkbox" name="coaching_tests[]" value="<?php echo $test->ID ?>" <?php if ($network_admin_settings['coaching_tests'] && in_array($test->ID, $network_admin_settings['coaching_tests'])): ?> checked="checked" <?php endif; ?>></td>
						<td><?php echo esc_attr($test->post_title) ?></td>
					</tr>
					<?php endforeach; ?>
				</table>
				<br>
				<?php wp_nonce_field('coaching_tests_nonce', 'coaching_tests_nonce'); ?>
				<?php submit_button(); ?>

			</form>
			<hr align="left" width="45%">
		<?php
		} else {
		?>
			<h2 class="sub_header">You should have at least 1 test on the Coaching site</h2>
		<?php
		}

    }
 
    /**
      * Check for POST (form submission)
      * Verifies nonce first then calls
      * updateSettings method to update.
      */
 
    public function update() {
        if ( isset($_POST['submit']) ) {
             
            // verify authentication (nonce)
            if ( !isset( $_POST['site_purpose_nonce'] ) && !isset( $_POST['email_options_nonce'] ) 
				&& !isset( $_POST['order_product_nonce'] ) && !isset( $_POST['coaching_tests_nonce'] ))
                return;
 
            // verify authentication (nonce)
            if ( @!wp_verify_nonce($_POST['site_purpose_nonce'], 'site_purpose_nonce') 
				&& @!wp_verify_nonce($_POST['email_options_nonce'], 'email_options_nonce') 
				&& @!wp_verify_nonce($_POST['order_product_nonce'], 'order_product_nonce') 
				&& @!wp_verify_nonce($_POST['coaching_tests_nonce'], 'coaching_tests_nonce'))
                return;
 
            return $this->updateSettings();
        }
    }
 
    /**
      * Updates settings
      */
 
    public function updateSettings() {
        $settings = array();
 
		if (isset($_POST['site_purpose_nonce'])) {
 
			$settings['store'] = sanitize_text_field($_POST['store']);
			$settings['assign_roles'] = $_POST['store_roles'];
			$settings['membership'] = sanitize_text_field($_POST['membership']);
			$settings['coaching'] = sanitize_text_field($_POST['coaching']);
			$settings['membership_plan'] = sanitize_text_field($_POST['membership_plan']);
			$settings['coaching_plan'] = sanitize_text_field($_POST['coaching_plan']);
			$settings['membership_plan_length'] = sanitize_text_field($_POST['membership_plan_length']);
			$settings['coaching_plan_length'] = sanitize_text_field($_POST['coaching_plan_length']);
			$settings['product_for_custom_email'] = $this->getSettings('product_for_custom_email');
			$settings['text_for_custom_email'] = $this->getSettings('text_for_custom_email');
			$settings['product_for_order'] = $this->getSettings('product_for_order');
			$settings['coaching_tests'] = $this->getSettings('coaching_tests');
				
			if ($settings['store'] == $settings['membership'] 
			 || $settings['membership'] == $settings['coaching'] 
			 || $settings['store'] == $settings['coaching'] 
			 || !$settings['membership_plan'] || !$settings['coaching_plan']) {
				$this->updated = false;
			 } else {
				// update new settings
				update_site_option('network_admin_settings', $settings);
				$this->updated = true;
			}
		
		} elseif (isset($_POST['email_options_nonce'])) {
			$settings['store'] = $this->getSettings('store');
			$settings['assign_roles'] = $this->getSettings('assign_roles');			
			$settings['membership'] = $this->getSettings('membership');
			$settings['coaching'] = $this->getSettings('coaching');
			$settings['membership_plan'] = $this->getSettings('membership_plan');
			$settings['coaching_plan'] = $this->getSettings('coaching_plan');
			$settings['membership_plan_length'] = $this->getSettings('membership_plan_length');
			$settings['coaching_plan_length'] = $this->getSettings('coaching_plan_length');
			$settings['product_for_custom_email'] = sanitize_text_field($_POST['product_for_custom_email']);
			$settings['text_for_custom_email'] = $_POST['text_for_custom_email'];
			$settings['product_for_order'] = $this->getSettings('product_for_order');
			$settings['coaching_tests'] = $this->getSettings('coaching_tests');

			

			// update new settings
			update_site_option('network_admin_settings', $settings);
			$this->updated = true;

		} elseif (isset($_POST["order_product_nonce"])) {
			$settings['store'] = $this->getSettings('store');
			$settings['assign_roles'] = $this->getSettings('assign_roles');
			$settings['membership'] = $this->getSettings('membership');
			$settings['coaching'] = $this->getSettings('coaching');
			$settings['membership_plan'] = $this->getSettings('membership_plan');
			$settings['coaching_plan'] = $this->getSettings('coaching_plan');
			$settings['membership_plan_length'] = $this->getSettings('membership_plan_length');
			$settings['coaching_plan_length'] = $this->getSettings('coaching_plan_length');
			$settings['product_for_custom_email'] = $this->getSettings('product_for_custom_email');
			$settings['text_for_custom_email'] = $this->getSettings('text_for_custom_email');
			$settings['product_for_order'] = $_POST['product_for_order'];
			$settings['coaching_tests'] = $this->getSettings('coaching_tests');
			// update new settings
			update_site_option('network_admin_settings', $settings);
			$this->updated = true;
		} elseif (isset($_POST["coaching_tests_nonce"])) {
			$settings['store'] = $this->getSettings('store');
			$settings['assign_roles'] = $this->getSettings('assign_roles');
			$settings['membership'] = $this->getSettings('membership');
			$settings['coaching'] = $this->getSettings('coaching');
			$settings['membership_plan'] = $this->getSettings('membership_plan');
			$settings['coaching_plan'] = $this->getSettings('coaching_plan');
			$settings['membership_plan_length'] = $this->getSettings('membership_plan_length');
			$settings['coaching_plan_length'] = $this->getSettings('coaching_plan_length');
			$settings['product_for_custom_email'] = $this->getSettings('product_for_custom_email');
			$settings['text_for_custom_email'] = $this->getSettings('text_for_custom_email');
			$settings['product_for_order'] = $this->getSettings('product_for_order');
			$settings['coaching_tests'] = $_POST['coaching_tests'];
			// update new settings
			update_site_option('network_admin_settings', $settings);
			$this->updated = true;
		}
       
    }
 
    /**
      * Updates settings
      *
      * @param $setting string optional setting name
      */
 
 
    public function getSettings($setting='') {
        global $network_admin_settings;
 
        if ( isset($network_admin_settings) ) {
            if ( $setting ) {
                return isset($network_admin_settings[$setting]) ? $network_admin_settings[$setting] : null;
            }
            return $network_admin_settings;
        }
 
        $network_admin_settings = wp_parse_args(get_site_option('network_admin_settings'), array(
			'store' => null,
			'assign_roles' => null,
            'membership' => null,
            'coaching' => null,
			'membership_plan' => null,
			'coaching_plan' => null,
			'membership_plan_length' => null,
			'coaching_plan_length' => null,
			'product_for_custom_email' => null,
			'text_for_custom_email' => null,
			'product_for_order' => null,
			'coaching_tests' => null
        ));

        if ( $setting ) {
            return isset($network_admin_settings[$setting]) ? $network_admin_settings[$setting] : null;
        }
        return $network_admin_settings;
    }
	
}

function network_admin_ajax_handler() {

	if( ! isset( $_POST['site_id'] ) ){
		echo json_encode( array() );
		wp_die();
	}

	switch_to_blog($_POST['site_id']);
	
	$args = array(
	  'post_type' => 'wc_membership_plan',
	  'post_status' => 'publish',
	  'orderby' => 'ID',
	  'order' => 'ASC'
	);
	$query = new WP_Query( $args );
	$rows = $query->get_posts();
	
	restore_current_blog();
	
	echo json_encode($rows);
	
	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_get_plans', 'network_admin_ajax_handler' );
 
$NetworkSettings = new Network_Settings;
$NetworkSettings->init();