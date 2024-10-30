<?php
/**
* Plugin Name: Listings for Buildium
* Description: This plugin gets your Buildium property listings and display them in an interactive way instead of iframe and gives you styling freedom with SEO benefits.
* Version: 0.1.2
* Author: Listings for Buildium
* Author URI: https://listingsforbuildium.com/
* License: GPL+2
* Text Domain: listings-for-buildium
* Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

add_action( 'init', 'bldm_init_plugin', 1 );
if (!function_exists('bldm_init_plugin')) {
	function bldm_init_plugin(){
		global $bldm_plugin_url;
		global $bldm_listings_url;
		$bldm_plugin_url = plugin_dir_url( __FILE__ );
		$bldm_listings_url = get_option('bldm_url');
		
		add_action( 'wp_enqueue_scripts', 'bldm_styles_scripts' );
		
		// Including main functions
		if(!class_exists ('simple_html_dom')){
			require(plugin_dir_path( __FILE__ ) . 'inc/simple_html_dom.php');
		}
		include(plugin_dir_path( __FILE__ ) . 'inc/single-listing.php');
		include(plugin_dir_path( __FILE__ ) . 'inc/listings.php');
		
		// Shortcodes
		add_shortcode('bldm_listings', 'bldm_display_all_listings');
	}
}

if (!function_exists('bldm_styles_scripts')) {
	function bldm_styles_scripts(){
		wp_enqueue_style(
			'bldm-style',
			plugin_dir_url( __FILE__ ) . 'css/style.css'
		);
		wp_enqueue_style(
			'bldm-pp-gall-style',
			plugin_dir_url( __FILE__ ) . 'css/gallery.css'
		);
		wp_enqueue_script(
			'bldm-pp-script',
			plugins_url('js/main.js',__FILE__ ),
			array('jquery')
		);
	}
}

// Plugin Configuration Page
if(is_admin()){
	add_action('admin_menu', 'bldm_admin_config');
	if (!function_exists('bldm_admin_config')) {
		function bldm_admin_config() {
			add_options_page('Listings for Buildium', 'Listings for Buildium', 'manage_options', 'bldm', 'bldm_config_callback'); 
		}
	}
	if (!function_exists('bldm_config_callback')) {
		function bldm_config_callback(){
			if (!current_user_can('manage_options')){
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}
			
			if($_POST){
				if(isset($_POST['bldm_config_submit'])){
					if(isset($_POST['bldm_config_url'])){
						$bldm_url = sanitize_text_field($_POST['bldm_config_url']);
						$bldm_url_updated = update_option('bldm_url', $bldm_url);
					}
					
					// Saved message
					if($bldm_url_updated){
						echo '<div class="notice notice-success is-dismissible"><p>Settings Saved!</p></div>';
					}
				}
			}
			?>

			<div class="wrap">
				<div id="bldm_settings">
					<form method='POST' action="">
						<br>
						<h1>Listings for Buildium Settings <span style="font-size: 15px;background: lightgrey;padding: 4px 10px;">shortcode - [bldm_listings]</span></h1>
						<table class="form-table">
							<tr>
								<th>
									<?php $bldm_listing_url = get_option('bldm_url'); ?>
									<label for="bldm_config_url">Buildium URL to fetch listings: </label>
								</th>
								<td>
									<input type="text" name="bldm_config_url" id="bldm_config_url" style="min-width: 350px;" placeholder="For Example - https://example.managebuilding.com" value="<?php echo $bldm_listing_url; ?>">
								</td>
							</tr>
						</table>
						
						<p class="submit">
							<input type="submit" name="bldm_config_submit" id="bldm_config_submit" class="button-primary" value="Save"/>
						</p>
					</form>
				</div>
			</div>
		<?php
		}
	}
}
