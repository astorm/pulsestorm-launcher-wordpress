<?php
/**
 * Plugin Name: Pulse Storm Launcher
 * Plugin URI: http://pulsestorm.net/wordpress/pulsestorm_launcher/
 * Description: A plugin for "one touch" Wordpress Admin navigation!
 * Author: Alan Storm
 * Version: 1.0
 * Author URI: http://alanstorm.com/
 *
 * @package Pulsestorm_Launcher
 * @version 1.0
 */

/**
 * Plugin class
 */
class Pulsestorm_Launcher_Plugin {

	const DEFAULT_TRIGGER_KEY = 'm';

	/**
	 * Blank constructor out of old school DI habits
	 */
	public function __construct() {
	}

	/**
	 * Method for accessing global array without introducing global function
	 */
	protected function get_global_menu_data() {
		global $menu;
		return $menu;
	}

	/**
	 * Method for accessing global array without introducing global function
	 */
	protected function get_global_sub_menu_data() {
		global $submenu;
		return $submenu;
	}

	/**
	 * Returns menu label for passed in slug
	 *
	 * @param mixed $slug The Slug.
	 */
	protected function get_parent_menu_label_by_slug( $slug ) {
		$local_menus = $this->get_global_menu_data();
		$local_menu = array_filter($local_menus, function( $local_menu ) use ( $slug ) {
			return ($local_menu[2] === $slug);
		});

		$local_menu = array_shift( $local_menu );
		if ( ! $local_menu ) {
			return 'Unknown Parent';
		}
		return $local_menu[0];
	}

	/**
	 * Change sub-menu test to match what the launcher JS wants
	 *
	 * @param mixed $submenu The Submenu.
	 */
	protected function normalize_submenu( $submenu ) {
		$all = [];
		foreach ( $submenu as $key => $menus ) {
			$menus = array_map(function( $array ) use ( $key ) {
				$array[0] = $this->get_parent_menu_label_by_slug( $key ) . ' &raquo; ' . $array[0];
				return $array;
			}, $menus);
			$all = array_merge( $all, $menus );
		}
		return $all;
	}

	/**
	 * Cleans tags and counts from labels (Foo <span>1</span>)
	 *
	 * @param mixed $label The Label.
	 */
	protected function clean_label( $label ) {
		$label = preg_replace( '%<.+>%','',$label );
		return trim( $label );
	}

	/**
	 * Generates a URL from a slug, depending on slug type
	 *
	 * @param mixed $slug The Slug.
	 */
	protected function get_url_from_slug( $slug ) {
		if ( strpos( $slug,'.php' ) ) {
			return admin_url( $slug );
		}

		$url = menu_page_url( $slug,false );
		return $url;
	}

	/**
	 * Method for accessing global array without introducing global function
	 *
	 * @param mixed $item The Menu Item.
	 */
	protected function get_slug_from_menu_item( $item ) {
		return $item[2];
	}

	/**
	 * Grabs menus from system for including directly in page js
	 */
	public function get_quicksearch_data() {
		$local_menu	 = $this->get_global_menu_data();
		$local_submenu = $this->get_global_sub_menu_data();

		$local_submenu = $this->normalize_submenu( $local_submenu );

		$all = array_merge( $local_menu, $local_submenu );
		$quick_search = [];
		foreach ( $all as $item ) {

			$label	= $this->clean_label( $item[0] );
			if ( ! trim( $label ) ) { continue; }

			$slug	= $this->get_slug_from_menu_item( $item );
			$url	= $this->get_url_from_slug( $slug );
			$terms	= implode( ' ', [ $slug, $label ] );

			$quick_search = $this->add_menu_to_quick_search_menus(
				$url, $label, $terms, $quick_search
			);

		}
		$quick_search = apply_filters( 'pulsestorm_launcher_menus', $quick_search );
		return $quick_search;
	}

	/**
	 * Adds a new menu item to the quick search (in page) results
	 *
	 * @param mixed $url The URL/slug.
	 * @param mixed $label The Label.
	 * @param mixed $terms String (or stringable) for search terms.
	 * @param mixed $quick_search The current array we're adding to.
	 */
	protected function add_menu_to_quick_search_menus( $url, $label, $terms, $quick_search ) {
		$quick_search[ $url ] = (object) [
			'terms' => $terms,
			'label' => $label,
		];
		return $quick_search;
	}

	/**
	 * Called from init(), renders HTML and JS for launcher
	 */
	protected function render_json_and_thickbox() {
		add_action( 'in_admin_footer', function(){
			add_thickbox();
			include( __DIR__ . '/includes/thickbox-div.php' );
			include( __DIR__ . '/includes/menu-json.php' );
			include( __DIR__ . '/includes/settings-json.php' );
		});

	}

	/**
	 * Add JS <script/>s
	 */
	protected function render_frontend_links_and_scripts() {
		add_action('admin_enqueue_scripts', function(){
			wp_enqueue_style( 'admin-styles', plugins_url() . '/pulsestorm_launcher/css/styles.css' );
			wp_enqueue_script( 'admin', plugins_url() . '/pulsestorm_launcher/js/pulsestorm_launcher_wordpress.js' );
		});
	}

	/**
	 * Grabs settings info from Woo Commerce for settings links
	 */
	protected function get_normalized_setting_information() {
		return array_map(function( $setting_object ) {
			$item = [];
			// use reflection to grab id.
			$r = new ReflectionClass( $setting_object );
			foreach ( [ 'id', 'label' ] as $prop_name ) {
				$prop = $r->getProperty( $prop_name );
				$prop->setAccessible( true );
				$item[ $prop_name ] = $prop->getValue( $setting_object );

			}
			$item['settings'] = $setting_object->get_settings();
			return $item;
		}, WC_Admin_Settings::get_settings_pages());

	}

	/**
	 * Adds the key to an array if it exists in the other array
	 *
	 * @param mixed $key The Key.
	 * @param mixed $terms Array 1.
	 * @param mixed $item Array 2.
	 */
	protected function add_key_to_array_if_exists_in_other_array( $key, $terms, $item ) {
		if ( isset( $item[ $key ] ) ) {
			$terms[] = $item[ $key ];
		}
		return $terms;
	}

	/**
	 * Adds to the terms from the setting information
	 *
	 * @param mixed $terms The Terms.
	 * @param mixed $setting_info The Settings Information.
	 */
	protected function add_to_terms_from_setting_info( $terms, $setting_info ) {
		foreach ( $setting_info as $info ) {
			if ( ! is_array( $info ) ) { continue; }
			if ( count( $info ) === 0 ) { continue; }
			foreach ( $info as $item ) {
				$terms = $this->add_key_to_array_if_exists_in_other_array( 'title', $terms, $item );
				$terms = $this->add_key_to_array_if_exists_in_other_array( 'desc', $terms, $item );
				if ( isset( $item['options'] ) ) {
					$terms[] = implode( ' ', array_keys( $item['options'] ) );
					$terms[] = implode( ' ', array_values( $item['options'] ) );
				}
			}
		}
		return $terms;
	}

	/**
	 * Gets terms from a lable and settings information
	 *
	 * @param mixed $label The Label.
	 * @param mixed $setting_info The settings information.
	 */
	protected function get_terms_from_label_and_setting_info( $label, $setting_info ) {
		$terms = [];
		$terms[] = 'WooCommerce Settings';
		$terms[] = $label;
		$terms = $this->add_to_terms_from_setting_info( $terms, $setting_info );
		return implode( ' ', $terms );
	}

	/**
	 * Filter callback to add Woo Commerce settings pages to quick results
	 *
	 * @param mixed $menus The Menus.
	 */
	public function setup_woocommerce_tabs_filter_callback( $menus ) {
		if ( ! class_exists( 'WC_Admin_Settings' ) ) { return $menus; }
		$settings = $this->get_normalized_setting_information();
		foreach ( $settings as $setting_info ) {
			$label = 'WooCommerce &raquo; Settings &raquo; ' . $setting_info['label'];
			$url   = admin_url(
			'admin.php?page=wc-settings&tab=' . $setting_info['id'] );
			$terms = $this->get_terms_from_label_and_setting_info(
			$label, $setting_info);
			$menus = $this->add_menu_to_quick_search_menus(
				$url,
				$label,
				$terms, // temp make terms label //$terms.
				$menus
			);

		}
		return $menus;

	}

	/**
	 * Add callback to add Woo Commerce settings tab to quick search results
	 */
	protected function setup_woocommerce_tabs_filter() {
		add_filter( 'pulsestorm_launcher_menus', [ $this, 'setup_woocommerce_tabs_filter_callback' ] );
	}

	/**
	 * Adds clickable link to admin bar
	 */
	protected function render_admin_bar_link() {
		add_action( 'admin_bar_menu', function( $wp_admin_bar ) {
			if ( ! is_admin() ) {return;}
			$wp_admin_bar->add_node([
				'id' => 'pulsestorm_launcher_link',
				'title' => 'Pulse Storm Launcher',
			]);

		},9999);
	}

	/**
	 * Sets up plugin options page
	 */
	protected function setup_settings_page() {
		add_action( 'admin_menu', function(){
			add_options_page(
			    'Pulse Storm Launcher',
			    'Pulse Storm Launcher',
				'read',
				'pulsestorm_launcher_options',
				function(){
					include( __DIR__ . '/includes/settings.php' );
			    }
			);
		});

		add_action( 'admin_init', function(){
			register_setting( 'pulsestorm_launcher-group', 'pulsestorm_launcher_trigger_key' );
		});
	}

	/**
	 * Helper function to output cheaply namespaced JSON var to page
	 *
	 * @param string $var_name The name of the JS variable.
	 * @param mixed  $data The data to output.
	 */
	public function output_json_with_script_tag( $var_name, $data ) {
		echo '<script type="text/javascript">';
		echo esc_js( $var_name ) . '=' .
			wp_json_encode(
				$data,
			JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . ';';
		echo '</script>';

	}

	/**
	 * Grabs search terms from request
	 *
	 * Abstracted because we're used to working with a request object
	 */
	protected function get_search_terms_from_request() {
	    $terms = '';
	    if ( isset( $_REQUEST['terms'] ) ) { // Input var okay.
		    $terms = sanitize_text_field( wp_unslash( $_REQUEST['terms'] ) ); // Input var okay.
	    }
		return $terms;
	}

	/**
	 * Search WP eCommerce products for ajax results
	 */
	protected function setup_ajax_post_w_pe_c_search_products() {
		add_filter('pulsestorm_launcher_ajax_menus', function( $links ) {
			$terms = $this->get_search_terms_from_request();
			$posts = get_posts([
				's' => $terms,
				'post_per_page' => '10',
				'paged' => '1',
				'post_type' => 'wpsc-product',
			]);

			foreach ( $posts as $post ) {
				$links[] = $this->generate_edit_post_link(
					$post->ID,
					'Product &raquo; ' . $post->post_title .
					' (' . $post->post_name . ') '
				);
			}
			return $links;
		});
		return [ 'links' => $links ];
	}

	/**
	 * Searches posts for ajax results
	 */
	protected function setup_ajax_post_search_hook() {
		add_filter('pulsestorm_launcher_ajax_menus', function( $links ) {
			$terms = $this->get_search_terms_from_request();
			$posts = get_posts([
				's' => $terms,
				'post_per_page' => '10',
				'paged' => '1',
			]);
			foreach ( $posts as $post ) {
				$links[] = $this->generate_edit_post_link(
				$post->ID, 'Post &raquo; ' . $post->post_title);
			}
			return $links;
		});
		return [ 'links' => $links ];
	}

	/**
	 * Generates a link to Woo Commerce order
	 *
	 * @param mixed $id The Id.
	 * @param mixed $label The Label.
	 */
	protected function generate_edit_woo_order_link( $id, $label ) {
		return $this->generate_edit_post_link( $id, $label );
	}

	/**
	 * Generates a link to an edit post page
	 *
	 * @param mixed $id The Id.
	 * @param mixed $label The Label.
	 */
	protected function generate_edit_post_link( $id, $label ) {
		return [
			'href'	=> admin_url( 'post.php?action=edit' ) . '&post=' . (integer) $id,
			'label' => $label,
		];

	}

	/**
	 * Gets base request object for call to Woo Restful API controller
	 */
	protected function get_request_object_for_woo_product_api_call() {
		$request = new WP_REST_Request;
		$request['s']				= 'hello';
		$request['orderby']			= 'name';

		$request['page']			= '1';
		$request['per_page']		= '10';
		return $request;
	}

	/**
	 * Gets base request object for call to WP eCommerce Restful API controller
	 */
	protected function get_request_object_for_woo_order_api_call() {
		$request = new WP_REST_Request;
		return $request;
	}

	/**
	 * Ajax search results for Woo orders
	 */
	protected function setup_ajax_post_woo_search_orders() {
		add_filter('pulsestorm_launcher_ajax_menus', function( $links ) {
			if ( ! class_exists( 'WC_REST_Orders_Controller' ) ) {
				return $links;
			}
			$request			= $this->get_request_object_for_woo_order_api_call();
			$request['search']	= $this->get_search_terms_from_request();
			$controller			= new WC_REST_Orders_Controller;
			$result				= $controller->get_items( $request );
			foreach ( $result->get_data() as $item ) {
				$links[] = $this->generate_edit_woo_order_link(
				$item['id'],'Order &raquo; #' . $item['id']);
			}
			return $links;
		});

	}

	/**
	 * Ajax search results for Woo Products
	 */
	protected function setup_ajax_post_woo_search_products() {
		add_filter('pulsestorm_launcher_ajax_menus', function( $links ) {
			if ( ! class_exists( 'WC_REST_Products_Controller' ) ) {
				return $links;
			}
			$request			= $this->get_request_object_for_woo_product_api_call();
			$request['search']	= $this->get_search_terms_from_request();
			$controller			= new WC_REST_Products_Controller;
			$result				= $controller->get_items( $request );
			foreach ( $result->get_data() as $item ) {
				$links[] = $this->generate_edit_post_link(
				$item['id'],'Product &raquo; ' . $item['name'] . ' (' . $item['slug'] . ')');

			}
			return $links;
		});

	}

	/**
	 * Handles ajax endpoint action
	 */
	protected function setup_ajax_endpoint() {
		add_action( 'wp_ajax_pulsestorm_launcher_search', function(){
			$links = [];
			$links = apply_filters( 'pulsestorm_launcher_ajax_menus', $links );
			$data = [ 'links' => $links ];
			wp_send_json( $data );
			wp_die();
		});
	}

	/**
	 * Static activation function where PHP version is checked
	 */
	static public function activate() {
		$version = '5.6.0';
		if ( ! version_compare( $version,phpversion(),'<=' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			$text = 'The Pulsestorm Launcher requires PHP 5.6.0 or greater.';
			wp_die( esc_html( $text ) );
		}
	}

	/**
	 * Main entry method!
	 */
	public function init() {
		$this->setup_woocommerce_tabs_filter();
		$this->render_frontend_links_and_scripts();
		$this->render_json_and_thickbox();

		$this->render_admin_bar_link();
		$this->setup_settings_page();

		$this->setup_ajax_post_search_hook();

		$this->setup_ajax_post_woo_search_products();
		$this->setup_ajax_post_woo_search_orders();
		$this->setup_ajax_post_w_pe_c_search_products();

		$this->setup_ajax_endpoint();
	}

}

register_activation_hook( __FILE__, [ 'Pulsestorm_Launcher_Plugin', 'activate' ] );
$pulsestorm_launcher_plugin = new Pulsestorm_Launcher_Plugin;
$pulsestorm_launcher_plugin->init();
