<?php
/**
 * @package Pulsestorm_Launcher
 * @version 1.0
 */
/*
Plugin Name: Pulse Storm Launcher
Plugin URI: http://pulsestorm.net/wordpress/pulsestorm_launcher/
Description: A plugin for "one touch" Wordpress Admin navigation!
Author: Alan Storm
Version: 1.0
Author URI: http://alanstorm.com/
*/

class Pulsestorm_Launcher_Plugin
{
    const DEFAULT_TRIGGER_KEY='m';
    public function __construct()
    {
    }
    
    protected function getGlobalMenuData()
    {
        global $menu;
        return $menu;
    }
    
    protected function getGlobalSubMenuData()
    {
        global $submenu;
        return $submenu;
    }
    
    protected function getParentMenuLabelBySlug($slug)
    {
        $menus = $this->getGlobalMenuData();
        $menu = array_filter($menus, function($menu) use ($slug){
            return ($menu[2] === $slug);
        });                
        $menu = array_shift($menu);
        if(!$menu)
        {
            return 'Unknown Parent';
        }
        return $menu[0];    
    }
    protected function normalizeSubmenu($submenu)
    {
        $all = [];
        foreach($submenu as $key=>$menus)
        {
            $menus = array_map(function($array) use ($key){
                $array[0] = $this->getParentMenuLabelBySlug($key) . ' &raquo; ' . $array[0];
                return $array;
            }, $menus);
            $all = array_merge($all, $menus);
        }
        return $all;
    }
    
    protected function cleanLabel($label)
    {
        $label = preg_replace('%<.+>%','',$label);
        return trim($label);
    }
    
    protected function getUrlFromSlug($slug)
    {
        if(strpos($slug,'.php'))
        {
            return admin_url($slug);
        }        
        $url = menu_page_url($slug,false);
        return $url;    
    }
    
    protected function getSlugFromMenuItem($item)
    {
        return $item[2];            
    }
    
    public function getQuicksearchData()
    {
        $menu    = $this->getGlobalMenuData();
        $submenu = $this->getGlobalSubMenuData();    
        $submenu = $this->normalizeSubmenu($submenu);
        
        $all = array_merge($menu, $submenu);
        $quickSearch = [];
        foreach($all as $item)
        {            
            $label  = $this->cleanLabel($item[0]);
            if(!trim($label)) { continue; }            
            $slug   = $this->getSlugFromMenuItem($item);
            $url    = $this->getUrlFromSlug($slug);
            $terms  = implode(' ', [$slug,$label]);            
            $quickSearch = $this->addMenuToQuickSearchMenus(
                $url, $label, $terms, $quickSearch
            );    
        }
        $quickSearch = apply_filters('pulsestorm_launcher_menus', $quickSearch);
        return $quickSearch;
    }
    
    protected function addMenuToQuickSearchMenus($url, $label, $terms, $quickSearch)
    {
        $quickSearch[$url] = (object) [
            'terms'=>$terms,
            'label'=>$label,
        ];
        return $quickSearch;
    }
    
    protected function renderJsonAndThickbox()
    {
        add_action( 'in_admin_footer', function(){   
            add_thickbox();    
            include(__DIR__ . '/includes/thickbox-div.php');
            include(__DIR__ . '/includes/menu-json.php');
            include(__DIR__ . '/includes/settings-json.php');
        });    
    }
    
    protected function renderFrontendLinksAndScripts()
    {
        add_action('admin_enqueue_scripts', function(){
            wp_enqueue_style('admin-styles', plugins_url() . '/pulsestorm_launcher/css/styles.css');        
            wp_enqueue_script('admin', plugins_url() . '/pulsestorm_launcher/js/pulsestorm_launcher_wordpress.js');        
        });    
    }
    
    protected function getNormalizedSettingInformation()
    {
        return array_map(function($settingObject){                
            $item = [];
            //use reflection to grab id
            $r = new ReflectionClass($settingObject);
            foreach(['id','label'] as $propName)
            {
                $prop = $r->getProperty($propName);                                
                $prop->setAccessible(true);
                $item[$propName] = $prop->getValue($settingObject);                
            }
            
            $item['settings'] = $settingObject->get_settings();
            return $item;
        }, WC_Admin_Settings::get_settings_pages());    
    }
    
    protected function addKeyToArrayIfExistsInOtherArray($key, $terms, $item)
    {
        if(isset($item[$key]))
        {
            $terms[] = $item[$key];
        }    
        return $terms;
    }
    
    protected function addToTermsFromSettingInfo($terms, $settingInfo)
    {
        foreach($settingInfo as $info)
        {
            if(!is_array($info)) { continue; }
            if(count($info) === 0) { continue; }
            foreach($info as $item)
            {
                $terms = $this->addKeyToArrayIfExistsInOtherArray('title', $terms, $item);
                $terms = $this->addKeyToArrayIfExistsInOtherArray('desc', $terms, $item);                                            
                if(isset($item['options']))
                {
                    $terms[] = implode(' ', array_keys($item['options']));
                    $terms[] = implode(' ', array_values($item['options']));
                }                                                           
            }
        }    
        return $terms;
    }
    
    protected function getTermsFromLabelAndSettingInfo($label, $settingInfo)
    {
        $terms = [];
        $terms[] = 'WooCommerce Settings';
        $terms[] = $label;
        $terms = $this->addToTermsFromSettingInfo($terms, $settingInfo);
        return implode(' ', $terms);
    }
    
    public function setupWoocommerceTabsFilterCallback($menus)
    {            
        if(!class_exists('WC_Admin_Settings')) { return $menus; }        
                
        $settings = $this->getNormalizedSettingInformation();
        foreach($settings as $settingInfo)
        {        
            $label = 'WooCommerce &raquo; Settings &raquo; ' . $settingInfo['label'];
            // var_dump($label);
            // $tmp = print_r($settingInfo, true);
            // file_put_contents('/tmp/test.log',"$tmp\n",FILE_APPEND);
            // exit;
            $url   = admin_url( 
                'admin.php?page=wc-settings&tab=' . $settingInfo['id'] );
            $terms = $this->getTermsFromLabelAndSettingInfo(
                $label, $settingInfo);
            
            $menus = $this->addMenuToQuickSearchMenus(
                $url, 
                $label, 
                $terms, //temp make terms label //$terms, 
                $menus
            );       
        }
        return $menus;
            
    }
    
    protected function setupWoocommerceTabsFilter()
    {        
        add_filter('pulsestorm_launcher_menus', [$this, 'setupWoocommerceTabsFilterCallback']);
    }
    
    protected function renderAdminBarLink()
    {
        add_action( 'admin_bar_menu', function($wp_admin_bar){
            if(!is_admin()){return;}
            $wp_admin_bar->add_node([
                'id'=>'pulsestorm_launcher_link',
                'title'=>'Pulse Storm Launcher', 
            ]);             
        },9999);    
    }
    
    protected function setupSettingsPage()
    {
        add_action( 'admin_menu', function(){
            add_options_page( 'Pulse Storm Launcher', 'Pulse Storm Launcher', 
                'read', 'pulsestorm_launcher_options', function(){
                    include(__DIR__ . '/includes/settings.php');
                });
        
        });
        
        add_action( 'admin_init', function(){
            register_setting( 'pulsestorm_launcher-group', 'pulsestorm_launcher_trigger_key' );
        });        	                
    }
    
    public function outputJsonWithScriptTag($var_name, $data)
    {
        echo '<script type="text/javascript">';
        echo $var_name . '=' . 
            json_encode(
                $data, 
                JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS) . ';';
        echo '</script>';                
    }
    
    protected function getSearchTermsFromRequest()
    {
        return $_REQUEST['terms'];
    }
    
    protected function setupAjaxPostWPeCSearchProducts()
    {
        add_filter('pulsestorm_launcher_ajax_menus', function($links){
            $terms = $this->getSearchTermsFromRequest();
            $posts = get_posts([
                's' => $terms,
                'post_per_page'=>'10',
                'paged'=>'1',
                'post_type'=>'wpsc-product'
                ]);                
                
            foreach($posts as $post)
            {
                $links[] = $this->generateEditPostLink(
                    $post->ID, 
                    'Product &raquo; ' . $post->post_title . 
                    ' (' . $post->post_name . ') '
                );
            }        
            return $links;
        });
                
        return ['links'=>$links];       
    }
    
    protected function setupAjaxPostSearchHook()
    {
        add_filter('pulsestorm_launcher_ajax_menus', function($links){
            $terms = $this->getSearchTermsFromRequest();
            $posts = get_posts([
                's' => $terms,
                'post_per_page'=>'10',
                'paged'=>'1']);                
                
            foreach($posts as $post)
            {
                $links[] = $this->generateEditPostLink(
                    $post->ID, 'Post &raquo; ' . $post->post_title);
            }        
            return $links;
        });
                
        return ['links'=>$links];   
    }
    
    protected function generateEditWooOrderLink($id, $label)
    {
        return $this->generateEditPostLink($id, $label);    
    }
    
    protected function generateEditPostLink($id, $label)
    {
        return [
            'href'  => admin_url('post.php?action=edit') . '&post=' . (integer) $id,
            'label' => $label
        ];    
    }
    
    protected function getRequestObjectForWooProductApiCall()
    {
        $request = new WP_REST_Request;
        $request['s']               = 'hello';
        $request['orderby']         = 'name'; 
        $request['page']            = '1';
        $request['per_page']        = '10';
        return $request;    
    }
    
    protected function getRequestObjectForWooOrderApiCall()
    {
        $request = new WP_REST_Request;
        return $request;
    }
    
    protected function setupAjaxPostWooSearchOrders()
    {
        add_filter('pulsestorm_launcher_ajax_menus', function($links){            
            if(!class_exists('WC_REST_Orders_Controller')) 
            { 
                return $links; 
            }
            $request            = $this->getRequestObjectForWooOrderApiCall();
            $request['search']  = $this->getSearchTermsFromRequest();
            $controller         = new WC_REST_Orders_Controller;
            $result             = $controller->get_items($request);
            foreach($result->get_data() as $item)
            {
                $links[] = $this->generateEditWooOrderLink(
                    $item['id'],'Order &raquo; #' . $item['id']);
            }
            return $links;
        });     
    }
    
    protected function setupAjaxPostWooSearchProducts()
    {
        add_filter('pulsestorm_launcher_ajax_menus', function($links){            
            if(!class_exists('WC_REST_Products_Controller')) 
            { 
                return $links; 
            }
            $request            = $this->getRequestObjectForWooProductApiCall();
            $request['search']  = $this->getSearchTermsFromRequest();
            $controller         = new WC_REST_Products_Controller;
            $result             = $controller->get_items($request);
            foreach($result->get_data() as $item)
            {
                $links[] = $this->generateEditPostLink(
                    $item['id'],'Product &raquo; ' . $item['name'] . ' (' . $item['slug'] . ')');                
            }
            return $links;
        });    
    }
        
    protected function setupAjaxEndpoint()
    {
        add_action( 'wp_ajax_pulsestorm_launcher_search', function(){
            $links = [];
            $links = apply_filters('pulsestorm_launcher_ajax_menus', $links);                
            $data = ['links'=>$links]; 
            wp_send_json($data);
            // return 'bar';            
            wp_die();
        });
    }
    
    static public function activate()
    {        
        $version = '5.6.0';
        if(!version_compare($version,phpversion(),'<='))
        {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die(__(
                sprintf("The Pulsestorm Launcher requires PHP %s or greater.",$version)
                ));
        }
    }
    
    public function init()
    {
        $this->setupWoocommerceTabsFilter();
        $this->renderFrontendLinksAndScripts();
        $this->renderJsonAndThickbox();           
        $this->renderAdminBarLink();
        $this->setupSettingsPage();
                
        $this->setupAjaxPostSearchHook();        
        $this->setupAjaxPostWooSearchProducts();
        $this->setupAjaxPostWooSearchOrders();
        $this->setupAjaxPostWPeCSearchProducts();
        
        $this->setupAjaxEndpoint();
    }
    
}

register_activation_hook(__FILE__, ['Pulsestorm_Launcher_Plugin','activate']);
$pulsestorm_launcher_plugin = new Pulsestorm_Launcher_Plugin;
$pulsestorm_launcher_plugin->init();