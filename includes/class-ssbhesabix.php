<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @class      Ssbhesabix
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/includes
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */

class Ssbhesabix
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Ssbhesabix_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;
//==========================================================================================================
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('SSBHESABIX_VERSION')) {
            $this->version = SSBHESABIX_VERSION;
        } else {
            $this->version = '2.0.93';
        }
        $this->plugin_name = 'ssbhesabix';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
    }
//==========================================================================================================
    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Ssbhesabix_Loader. Orchestrates the hooks of the plugin.
     * - Ssbhesabix_i18n. Defines internationalization functionality.
     * - Ssbhesabix_Admin. Defines all hooks for the admin area.
     * - Ssbhesabix_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabix-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabix-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-ssbhesabix-admin.php';

        /**
         * The class responsible for defining all Hesabix API methods
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabix-api.php';

        $this->loader = new Ssbhesabix_Loader();

        /**
         * The class responsible for defining all Hesabix data Validations
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabix-validation.php';

        $this->loader = new Ssbhesabix_Loader();

    }
//=====================================================================================
    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new Ssbhesabix_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
//=====================================================================================
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new Ssbhesabix_Admin($this->get_plugin_name(), $this->get_version());

        //Related to check DB ver on plugin update
        $this->loader->add_action('plugins_loaded', $plugin_admin, 'ssbhesabix_update_db_check');

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        //Related to webhook set
        $this->loader->add_filter('query_vars', $plugin_admin, 'ssbhesabix_query_vars');
        $this->loader->add_action('parse_request', $plugin_admin, 'ssbhesabix_parse_request');

        $this->loader->add_action('wp_ajax_nopriv_handle_webhook_request', $plugin_admin, 'handle_webhook_request');
        $this->loader->add_action('wp_ajax_handle_webhook_request', $plugin_admin, 'handle_webhook_request');

        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

            $this->loader->add_action('init', $plugin_admin, 'ssbhesabix_init_internal');

            //Check plugin live mode
            if (get_option('ssbhesabix_live_mode')) {
                if (get_option('ssbhesabix_hesabix_default_currency') === 0) {
                    $this->loader->add_action('admin_notices', $plugin_admin, 'ssbhesabix_currency_notice');
                }

                // these lines add hesabix id to the all products list page and make it sortable as well
                ///////////////////////////////////////////////////////////////////////////////////////////////////////
                if(get_option('ssbhesabix_show_product_code_in_products_page') === 'yes') {
                    $this->loader->add_filter( 'manage_edit-product_columns', $plugin_admin,'admin_products_hesabixId_column', 12 );
                    $this->loader->add_action( 'manage_product_posts_custom_column', $plugin_admin, 'admin_products_hesabixId_column_content', 10, 2 );
                    $this->loader->add_filter( 'manage_edit-product_sortable_columns', $plugin_admin,'admin_products_hesabixId_column');
                }

                $this->loader->add_action('custom_product_tabs', $plugin_admin, 'ssbhesabix_general_notices');

                // add filter and action for woocommerce order list
                $this->loader->add_filter('manage_edit-shop_order_columns', $plugin_admin, 'custom_hesabix_column_order_list', 20);
                $this->loader->add_action('manage_shop_order_posts_custom_column', $plugin_admin, 'custom_orders_list_column_content', 20, 2);
                $this->loader->add_filter('bulk_actions-edit-shop_order', $plugin_admin, 'custom_orders_list_bulk_action', 20, 1);
                $this->loader->add_filter('handle_bulk_actions-edit-shop_order', $plugin_admin, 'custom_orders_list_bulk_action_run', 10, 3);
	            // check add fields to checkout page by hesabix plugin
				if(get_option('ssbhesabix_contact_add_additional_checkout_fields_hesabix') == 1)
					$this->loader->add_filter('woocommerce_checkout_fields', $plugin_admin, 'add_additional_fields_to_checkout', 10, 3);

				// show checkout additional fields in order detail
	            if(get_option('ssbhesabix_contact_add_additional_checkout_fields_hesabix') == 1)
	                $this->loader->add_action('woocommerce_admin_order_data_after_billing_address', $plugin_admin, 'show_additional_fields_in_order_detail', 10, 3);

                //Runs when a new order added.
                $this->loader->add_action('woocommerce_order_status_changed', $plugin_admin, 'ssbhesabix_hook_order_status_change', 10, 3);

                //Runs when an order paid.
//                $this->loader->add_action('woocommerce_payment_complete', $plugin_admin, 'ssbhesabix_hook_payment_confirmation', 10, 1);
//                $this->loader->add_filter('woocommerce_payment_complete_order_status', $plugin_admin, 'ssbhesabix_hook_payment_confirmation', 10, 1);
//                $this->loader->add_filter('woocommerce_order_status_completed', $plugin_admin, 'ssbhesabix_hook_payment_confirmation', 10, 1);
                $this->loader->add_filter('woocommerce_order_status_changed', $plugin_admin, 'ssbhesabix_hook_payment_confirmation', 11, 3);

                //Runs when a user's profile is first created.
                $this->loader->add_action('edit_user_profile', $plugin_admin, 'ssbhesabix_hook_edit_user');

                $this->loader->add_action('user_register', $plugin_admin, 'ssbhesabix_hook_user_register');
//                $this->loader->add_action('woocommerce_new_customer', $plugin_admin, 'ssbhesabix_hook_user_register');
//                $this->loader->add_action('woocommerce_created_customer', $plugin_admin, 'ssbhesabix_hook_user_register');
                //Runs when a user updates personal options from the admin screen.
                $this->loader->add_action('personal_options_update', $plugin_admin, 'ssbhesabix_hook_user_register');
                //Runs when a user's profile is updated.
                $this->loader->add_action('profile_update', $plugin_admin, 'ssbhesabix_hook_user_register');
                //Runs when a user is deleted.
                $this->loader->add_action('delete_user', $plugin_admin, 'ssbhesabix_hook_delete_user');

                //Runs when a product is added.
//                $this->loader->add_action('woocommerce_new_product', $plugin_admin, 'ssbhesabix_hook_new_product');
//                $this->loader->add_action('woocommerce_new_product_variation', $plugin_admin, 'ssbhesabix_hook_new_product_variation', 10, 2);
                //Runs when a product is updated.
                $this->loader->add_action('woocommerce_update_product', $plugin_admin, 'ssbhesabix_hook_new_product');
//                $this->loader->add_action('woocommerce_update_product_variation', $plugin_admin, 'ssbhesabix_hook_new_product');
                //Runs when a product is deleted.
                $this->loader->add_action('before_delete_post', $plugin_admin, 'ssbhesabix_hook_delete_product');
                //$this->loader->add_action('woocommerce_delete_product_variation', $plugin_admin, 'ssbhesabix_hook_delete_product_variation');

                //Display Hesabix item code in Product data section
                $this->loader->add_action('woocommerce_product_options_general_product_data', $plugin_admin, 'ssbhesabix_hook_product_options_general_product_data');
                $this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'ssbhesabix_hook_process_product_meta');
                //Display Hesabix item code in Product variable attribute section
                $this->loader->add_action('woocommerce_product_after_variable_attributes', $plugin_admin, 'ssbhesabix_hook_product_after_variable_attributes', 10, 3);
                $this->loader->add_action('woocommerce_save_product_variation', $plugin_admin, 'ssbhesabix_hook_save_product_variation', 10, 3);

                $this->loader->add_filter('woocommerce_product_data_tabs', $plugin_admin, 'add_hesabix_product_data_tab');
                $this->loader->add_action('woocommerce_product_data_panels', $plugin_admin, 'add_hesabix_product_data_fields');

            } elseif (!get_option('ssbhesabix_live_mode')) {
                if (get_option('ssbhesabix_business_expired'))
                    $this->loader->add_action('admin_notices', $plugin_admin, 'ssbhesabix_business_expired_notice');
                else
                    $this->loader->add_action('admin_notices', $plugin_admin, 'ssbhesabix_live_mode_notice');
            }

            /*
             * Action - Ajax 'Export Tabs' from Hesabix/Export
             * @since	1.0.0
             */
            $this->loader->add_filter('wp_ajax_adminExportProducts', $plugin_admin, 'adminExportProductsCallback');
            $this->loader->add_filter('wp_ajax_adminImportProducts', $plugin_admin, 'adminImportProductsCallback');
            $this->loader->add_filter('wp_ajax_adminExportProductsOpeningQuantity', $plugin_admin, 'adminExportProductsOpeningQuantityCallback');
            $this->loader->add_filter('wp_ajax_adminExportCustomers', $plugin_admin, 'adminExportCustomersCallback');

            /*
             * Action - Ajax 'Sync Tabs' from Hesabix/Sync
             * @since	1.0.0
             */
            $this->loader->add_filter('wp_ajax_adminSyncChanges', $plugin_admin, 'adminSyncChangesCallback');
            $this->loader->add_filter('wp_ajax_adminSyncProducts', $plugin_admin, 'adminSyncProductsCallback');
            $this->loader->add_filter('wp_ajax_adminSyncOrders', $plugin_admin, 'adminSyncOrdersCallback');
            $this->loader->add_filter('wp_ajax_adminUpdateProducts', $plugin_admin, 'adminUpdateProductsCallback');
            $this->loader->add_filter('wp_ajax_adminUpdateProductsWithFilter', $plugin_admin, 'adminUpdateProductsWithFilterCallback');
            $this->loader->add_filter('wp_ajax_adminSubmitInvoice', $plugin_admin, 'adminSubmitInvoiceCallback');

            /*
             * Action - Ajax 'Log Tab' from Hesabix/Log
             * @since	1.0.0
             */
            $this->loader->add_filter('wp_ajax_adminCleanLogFile', $plugin_admin, 'adminCleanLogFileCallback');

            $this->loader->add_filter('wp_ajax_adminSyncProductsManually', $plugin_admin, 'adminSyncProductsManuallyCallback', 10, 4);
            $this->loader->add_filter('wp_ajax_adminClearPluginData', $plugin_admin, 'adminClearPluginDataCallback', 10, 4);
            $this->loader->add_filter('wp_ajax_adminInstallPluginData', $plugin_admin, 'adminInstallPluginDataCallback', 10, 4);

            $this->loader->add_filter('wp_ajax_adminChangeProductCode', $plugin_admin, 'adminChangeProductCodeCallback');
            $this->loader->add_filter('wp_ajax_adminDeleteProductLink', $plugin_admin, 'adminDeleteProductLinkCallback');
            $this->loader->add_filter('wp_ajax_adminUpdateProduct', $plugin_admin, 'adminUpdateProductCallback');
            $this->loader->add_filter('wp_ajax_adminChangeProductsCode', $plugin_admin, 'adminChangeProductsCodeCallback');
            $this->loader->add_filter('wp_ajax_adminDeleteProductsLink', $plugin_admin, 'adminDeleteProductsLinkCallback');
            $this->loader->add_filter('wp_ajax_adminUpdateProductAndVariations', $plugin_admin, 'adminUpdateProductAndVariationsCallback');

        } else {
            $this->loader->add_action('admin_notices', $plugin_admin, 'ssbhesabix_missing_notice');
        }
    }
//=====================================================================================
    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {$this->loader->run();}
//=====================================================================================
    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name() {return $this->plugin_name;}
//=====================================================================================
    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Ssbhesabix_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader() {return $this->loader;}
//=====================================================================================
    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version() {return $this->version;}
//=====================================================================================
}
