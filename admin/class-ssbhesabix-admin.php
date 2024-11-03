<?php

include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabixLogService.php');
include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabixWpFaService.php');

/**
 * The admin-specific functionality of the plugin.
 *
 * @class      Ssbhesabix_Admin
 * @version    2.1.1
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/admin
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */
class Ssbhesabix_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;
//=========================================================================================================================
    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @since    1.0.0
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load_dependencies();
    }

    /**
     * Check DB ver on plugin update and do necessary actions
     *
     * @since    1.0.7
     */
//=========================================================================================================================
    public function ssbhesabix_update_db_check()
    {
        $current_db_ver = get_site_option('ssbhesabix_db_version');
        if ($current_db_ver === false || $current_db_ver < 1.1) {
            global $wpdb;
            $table_name = $wpdb->prefix . "ssbhesabix";

            $sql = "ALTER TABLE $table_name
                    ADD `id_ps_attribute` INT(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `id_ps`;";

            if (!$wpdb->query($sql)) {
                HesabixLogService::log(array("Cannot alter table $table_name. Current DB Version: $current_db_ver"));
            } else {
                update_option('ssbhesabix_db_version', 1.1);
                HesabixLogService::log(array("Alter table $table_name. Current DB Version: $current_db_ver"));
            }
        }
    }
//=========================================================================================================================
    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ssbhesabix_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ssbhesabix_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        if( isset($_GET['page']) && str_contains($_GET['page'], "hesabix") ){
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/ssbhesabix-admin.css?v=1', array(), $this->version, 'all');
            wp_enqueue_style('bootstrap_css', plugin_dir_url(__FILE__) . 'css/bootstrap.css', array(), $this->version, 'all');
        }
    }
//=========================================================================================================================
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Ssbhesabix_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Ssbhesabix_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/ssbhesabix-admin.js', array('jquery'), $this->version, false);
        if( isset($_GET['page']) && str_contains($_GET['page'], "hesabix") )
            wp_enqueue_script('bootstrap_js', plugin_dir_url(__FILE__) . 'js/bootstrap.bundle.min.js', array('jquery'), $this->version, false);
    }
//=========================================================================================================================
    private function load_dependencies()
    {
        /**
         * The class responsible for defining all actions that occur in the Dashboard
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabix-admin-display.php';

        /**
         * The class responsible for defining function for display Html element
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabix-html-output.php';

        /**
         * The class responsible for defining function for display general setting tab
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabix-admin-setting.php';

        /**
         * The class responsible for defining function for admin area
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/ssbhesabix-admin-functions.php';
    }

    /**
     * WC missing notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabix_missing_notice()
    {
        echo '<div class="error"><p>' . sprintf(__('Hesabix Plugin requires the %s to work!', 'ssbhesabix'), '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">' . __('WooCommerce', 'ssbhesabix') . '</a>') . '</p></div>';
    }

    /**
     * Hesabix Plugin Live mode notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabix_live_mode_notice()
    {
        echo '<div class="error"><p>' . __('Hesabix Plugin need to connect to Hesabix Accounting, Please check the API credential!', 'ssbhesabix') . '</p></div>';
    }
//=========================================================================================================================
    public function ssbhesabix_business_expired_notice()
    {
        echo '<div class="error"><p>' . __('Cannot connect to Hesabix. Business expired.', 'ssbhesabix') . '</p></div>';
    }

    /**
     * Missing hesabix default currency notice for the admin area.
     *
     * @since    1.0.0
     */
//=========================================================================================================================
    public function ssbhesabix_currency_notice()
    {
        echo '<div class="error"><p>' . __('Hesabix Plugin cannot works! because WooCommerce currency in not match with Hesabix.', 'ssbhesabix') . '</p></div>';
    }
//=========================================================================================================================
    public function ssbhesabix_general_notices() {
        if (!empty( $_REQUEST['submit_selected_orders_invoice_in_hesabix'])) {
            if(!empty($_REQUEST['error_msg']) && $_REQUEST['error_msg'] == "select_max_10_items") {
                printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                    __('Error: Select maximum 10 orders. Due to some limitations in Hesabix API, sending too many requests in one minute is not possible.', 'ssbhesabix'));
            } else {
                $success_count = intval( $_REQUEST['success_count'] );
                printf( '<div class="notice notice-success is-dismissible"><p>%s %d</p></div>', __('Selected orders invoices have been saved. Number of saved invoices: ', 'ssbhesabix'), $success_count);
            }
        }
    }

//=========================================================================================================================
    /*
     * Action - Ajax 'export products' from Hesabix/Export tab
     * @since	1.0.0
     */
    public function adminExportProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->exportProducts($batch, $totalBatch, $total, $updateCount);

            if ($result['error']) {
                if ($updateCount === -1) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productExportResult=false&error=-1');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productExportResult=false');
                }
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productExportResult=true&processed=' . $result['updateCount']);
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    public function adminImportProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->importProducts($batch, $totalBatch, $total, $updateCount);
            $import_count = $result['updateCount'];

            if ($result['error']) {
                if ($import_count === -1) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productImportResult=false&error=-1');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productImportResult=false');
                }
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productImportResult=true&processed=' . $import_count);
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'export products Opening Quantity' from Hesabix/Export tab
     * @since	1.0.6
     */
    public function adminExportProductsOpeningQuantityCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->exportOpeningQuantity($batch, $totalBatch, $total);
            if ($result['error']) {
                if ($result['errorType'] == 'shareholderError') {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productOpeningQuantityExportResult=false&shareholderError=true');
                } else if ($result['errorType'] == 'noProduct') {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productOpeningQuantityExportResult=false&noProduct=true');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productOpeningQuantityExportResult=false');
                }
            } else {
                if ($result["done"] == true)
                    update_option('ssbhesabix_use_export_product_opening_quantity', true);
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&productOpeningQuantityExportResult=true');
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'export customers' from Hesabix/Export tab
     * @since	1.0.0
     */
    public function adminExportCustomersCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->exportCustomers($batch, $totalBatch, $total, $updateCount);

            if ($result["error"]) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&customerExportResult=false');
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=export&customerExportResult=true&processed=' . $result["updateCount"]);
            }
            echo json_encode($result);

            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Changes' from Hesabix/Sync tab
     * @since	1.0.0
     */
    public function adminSyncChangesCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            include(plugin_dir_path(__DIR__) . 'includes/class-ssbhesabix-webhook.php');
            new Ssbhesabix_Webhook();

            $redirect_url = admin_url('admin.php?page=ssbhesabix-option&tab=sync&changesSyncResult=true');
            echo $redirect_url;

            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Products' from Hesabix/Sync tab
     * @since	1.0.0
     */
    public function adminSyncProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->syncProducts($batch, $totalBatch, $total);
            if ($result['error']) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&productSyncResult=false');
                echo json_encode($result);
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&productSyncResult=true');
                echo json_encode($result);
            }
            die();
        }
    }
//=========================================================================================================================
    /*
     * Action - Ajax 'Sync Orders from Hesabix/Sync tab
     * @since	1.0.0
     */
    public function adminSyncOrdersCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);
            $updateCount = wc_clean($_POST['updateCount']);
            $from_date = wc_clean($_POST['date']);
            $end_date = wc_clean($_POST['endDate']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->syncOrders($from_date, $end_date, $batch, $totalBatch, $total, $updateCount);

            if (!$result['error'])
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&orderSyncResult=true&processed=' . $result["updateCount"]);
            else {
                switch ($result['error']) {
                    case 'fiscalYearError':
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&orderSyncResult=false&fiscal=true');
                        break;
                    case 'inputDateError':
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&orderSyncResult=false');
                        break;
                    default:
                        $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&orderSyncResult=true&processed=' . $updateCount);
                }
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    /*
    * Action - Ajax 'Update Products' from Hesabix/Sync tab
    * @since	1.0.0
    */
    public function adminUpdateProductsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $batch = wc_clean($_POST['batch']);
            $totalBatch = wc_clean($_POST['totalBatch']);
            $total = wc_clean($_POST['total']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->updateProductsInHesabixBasedOnStore($batch, $totalBatch, $total);

            if ($result['error']) {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&$productUpdateResult=false');
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&$productUpdateResult=true');
            }
            echo json_encode($result);
            die();
        }
    }

//=========================================================================================================================
    public function adminUpdateProductsWithFilterCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $offset = wc_clean($_POST['offset']);
            $rpp = wc_clean($_POST['rpp']);
            if(abs($rpp-$offset) <= 200) {
                $func = new Ssbhesabix_Admin_Functions();
                $result = $func->updateProductsInHesabixBasedOnStoreWithFilter($offset, $rpp);

                if ($result['error']) {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&$productUpdateWithFilterResult=false');
                } else {
                    $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&$productUpdateWithFilterResult=true');
                }
                echo json_encode($result);
                die();
            } else {
                $result["redirectUrl"] = admin_url('admin.php?page=ssbhesabix-option&tab=sync&$productUpdateWithFilterResult=false');
                echo json_encode($result);
                die();
            }
        }
    }
//==========================================================================================================================
    public function adminSubmitInvoiceCallback()
    {
        HesabixLogService::writeLogStr('Submit Invoice Manually');

        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $orderId = wc_clean($_POST['orderId']);

            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->setOrder($orderId);
            if ($result)
                $func->setOrderPayment($orderId);

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    public function adminSyncProductsManuallyCallback()
    {
        HesabixLogService::writeLogStr('Sync Products Manually');

        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $page = wc_clean($_POST["page"]);
            $rpp = wc_clean($_POST["rpp"]);
            if (!$page) $page = 1;
            if (!$rpp) $rpp = 10;

            if (isset($_POST["data"])) {
                $data = wc_clean($_POST['data']);
                $data = str_replace('\\', '', $data);
                $data = json_decode($data, true);
            } else {
                $errors = true;
            }

            $func = new Ssbhesabix_Admin_Functions();
            $res = $func->syncProductsManually($data);
            if ($res["result"] == true) {
                $redirect_url = admin_url("admin.php?page=hesabix-sync-products-manually&p=$page&rpp=$rpp&result=true");
            } else {
                $data = implode(",", $res["data"]);
                $redirect_url = admin_url("admin.php?page=hesabix-sync-products-manually&p=$page&rpp=$rpp&result=false&data=$data");
            }
            echo $redirect_url;

            die();
        }
    }
//=========================================================================================================================
    public function adminClearPluginDataCallback()
    {

        HesabixLogService::writeLogStr('Clear Plugin Data');
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            //Call API
            $hesabixApi = new Ssbhesabix_Api();
            $result = $hesabixApi->fixClearTags();
            if (!$result->Success) {

                HesabixLogService::log(array("ssbhesabix - Cannot clear tags. Error Message: " . (string)$result->ErrorMessage . ". Error Code: " . (string)$result->ErrorCode));
            }

            global $wpdb;
            $options = $wpdb->get_results("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '%ssbhesabix%'");
            foreach ($options as $option) {
                delete_option($option->option_name);
            }

            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ssbhesabix");

            die();
        }
    }
//=========================================================================================================================
    public function adminInstallPluginDataCallback()
    {

        HesabixLogService::writeLogStr('Install Plugin Data');
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            // create table and settings
            require_once plugin_dir_path(__DIR__) . 'includes/class-ssbhesabix-activator.php';
            Ssbhesabix_Activator::activate();

            die();
        }
    }
//=========================================================================================================================
    public function admin_product_add_column( $columns ) {
        $hesabixArray = array("hesabix_code" => "کد در حسابیکس");
        $columns = $hesabixArray + $columns;
        return $columns;
    }
//=========================================================================================================================
    public function admin_product_export_rows($rows, $products) {
        $rowsArray = explode("\n", $rows);
        $exportRows = [];

        $reflection = new ReflectionClass($products);
        $property = $reflection->getProperty('row_data');
        $property->setAccessible(true);
        $productsArray = $property->getValue($products);
        $matchingArray = [];

        if (!empty($productsArray)) {
            foreach ($productsArray as $product) {
                if (is_array($product) && isset($product['id'])) {
                    $wpFaService = new HesabixWpFaService();

                    if ($product["type"] == "variation") {
                        if(array_key_exists('parent_id', $product)) {
                            $parentId = $product['parent_id'];
                            $productParentId = explode(':', $parentId)[1];
                            $wpFa = $wpFaService->getWpFaSearch($productParentId, $product['id'], '', "product");
                        }
                    } elseif ($product["type"] == "simple" || $product["type"] == "variable") {
                        $wpFa = $wpFaService->getWpFaSearch($product['id'], 0, '', "product");
                    }

                    if (is_array($wpFa)) {
                        foreach ($wpFa as $item) {
                            if ($item->idWpAttribute != 0) {
                                $matchingArray[$item->idWpAttribute] = $item->idHesabix;
                            } else {
                                $matchingArray[$item->idWp] = $item->idHesabix;
                            }
                        }
                    }
                }
            }
        }

        foreach ($rowsArray as $row) {
            if (empty(trim($row))) {
                continue;
            }
            $columns = str_getcsv($row);
            $inserted = false;

            if (isset($columns[1])) {
                foreach ($matchingArray as $wpId => $hesabixId) {
                    if ($columns[1] == $wpId && !$inserted) {
                        $columns[0] = $hesabixId;
                        $inserted = true;
                        break;
                    }
                }
            }

            if (!$inserted) {
                $columns[0] = "کد ندارد";
            }

            $exportRows[] = implode(",", $columns);
        }

        return implode("\n", $exportRows);
    }
//=========================================================================================================================
    public function ssbhesabix_init_internal()
    {
        add_rewrite_rule('ssbhesabix-webhook.php$', 'index.php?ssbhesabix_webhook=1', 'top');
        //$this->checkForSyncChanges();
    }
//=========================================================================================================================
    private function checkForSyncChanges()
    {
        $syncChangesLastDate = get_option('ssbhesabix_sync_changes_last_date');
        if (!isset($syncChangesLastDate) || $syncChangesLastDate == false) {
            add_option('ssbhesabix_sync_changes_last_date', new DateTime());
            $syncChangesLastDate = new DateTime();
        }

        $nowDateTime = new DateTime();
        $diff = $nowDateTime->diff($syncChangesLastDate);

        if ($diff->i >= 4) {
            HesabixLogService::writeLogStr('Sync Changes Automatically');
            update_option('ssbhesabix_sync_changes_last_date', new DateTime());
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-ssbhesabix-webhook.php';
            new Ssbhesabix_Webhook();
        }
    }
//=========================================================================================================================
    public function ssbhesabix_query_vars($query_vars)
    {
        $query_vars[] = 'ssbhesabix_webhook';
        return $query_vars;
    }
//=========================================================================================================================
    public function custom_hesabix_column_order_list($columns)
    {
        $reordered_columns = array();

        foreach ($columns as $key => $column) {
            $reordered_columns[$key] = $column;
            if ($key == 'order_status') {
                // Inserting after "Status" column
                $reordered_columns['hesabix-column-invoice-number'] = __('Invoice in Hesabix', 'ssbhesabix');
                $reordered_columns['hesabix-column-submit-invoice'] = __('Submit Invoice', 'ssbhesabix');
            }
        }
        return $reordered_columns;
    }
//=========================================================================================================================
    public function custom_orders_list_column_content($column, $post_id)
    {

	    global $wpdb;

        if (get_option('woocommerce_custom_orders_table_enabled') == 'yes') {
            switch ($column) {
                case 'hesabix-column-invoice-number':
                    $product_id = $post_id->ID; // Extract product ID from the object
    //                $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps` = $post_id AND `obj_type` = 'order'");
                    $table_name = $wpdb->prefix . 'ssbhesabix';
                    $row = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT id_hesabix FROM $table_name WHERE id_ps = %d AND obj_type = 'order'",
                            $product_id
                        )
                    );

                    if (!empty($row)) {
                        echo '<mark class="order-status"><span>' . $row->id_hesabix . '</span></mark>';
                    } else {
                        echo '<small></small>';
                    }
                    break;

                case 'hesabix-column-submit-invoice':
                    // Use the product ID for the data attribute value
                    $product_id = $post_id->ID;
                    echo '<a role="button" class="button btn-submit-invoice" ';
                    echo 'data-order-id="' . $product_id . '">';
                    echo __('Submit Invoice', 'ssbhesabix');
                    echo '</a>';
                    break;
            }
        } else {
            switch ($column) {
                case 'hesabix-column-invoice-number' :
                    // Get custom post meta data
                    $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps` = $post_id AND `obj_type` = 'order'");

                    //$my_var_one = get_post_meta( $post_id, '_the_meta_key1', true );
                    if (!empty($row))
                        echo '<mark class="order-status"><span>' . $row->id_hesabix . '</span></mark>';
                    else
                        echo '<small></small>';
                    break;

                case 'hesabix-column-submit-invoice' :
                    echo '<a role="button" class="button btn-submit-invoice" ';
                    echo "data-order-id='$post_id'>";
                    echo __('Submit Invoice', 'ssbhesabix');
                    echo '</a>';
                    break;
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabix_parse_request(&$wp)
    {
        if (array_key_exists('ssbhesabix_webhook', $wp->query_vars)) {
            include(plugin_dir_path(__DIR__) . 'includes/ssbhesabix-webhook.php');
            exit();
        }
    }
//=========================================================================================================================
    public function custom_orders_list_bulk_action($actions) {
        $actions['submit_invoice_in_hesabix'] = __('Submit Invoice in Hesabix', 'ssbhesabix');
        return $actions;
    }
//=========================================================================================================================
    public function custom_orders_list_bulk_action_run($redirect_to, $action, $post_ids) {
        if ( $action !== 'submit_invoice_in_hesabix' )
            return $redirect_to; // Exit


        HesabixLogService::writeLogStr("Submit selected orders invoice");

        if(count($post_ids) > 10)
            return $redirect_to = add_query_arg( array(
                'submit_selected_orders_invoice_in_hesabix' => '1',
                'error_msg' => 'select_max_10_items'
            ), $redirect_to );

        $success_count = 0;
        $func = new Ssbhesabix_Admin_Functions();
        foreach ($post_ids as $orderId) {
            $result = $func->setOrder($orderId);
            if ($result) {
                $success_count++;
                $func->setOrderPayment($orderId);
            }
        }

        return $redirect_to = add_query_arg( array(
            'submit_selected_orders_invoice_in_hesabix' => '1',
            'success_count' => $success_count,
            'error_msg' => '0'
        ), $redirect_to );
    }
//=========================================================================================================================
    //Hooks
    //Contact
    public function ssbhesabix_hook_edit_user(WP_User $user)
    {
        $wpFaService = new HesabixWpFaService();
        $code = isset($user) ? $wpFaService->getCustomerCodeByWpId($user->ID) : '';
        ?>
        <hr>
        <table class="form-table">
            <tr>
                <th><label for="user_hesabix_code"
                           class="text-info"><?php echo __('Contact Code in Hesabix', 'ssbhesabix'); ?></label>
                </th>
                <td>
                    <input
                            type="text"
                            value="<?php echo $code; ?>"
                            name="user_hesabix_code"
                            id="user_hesabix_code"
                            class="regular-text"
                    ><br/>
                    <div class="description mt-2">
                        <?php echo __("The contact code of this user in Hesabix, if you want to map this user "
                            . "to a contact in Hesabix, enter the Contact code.", 'ssbhesabix'); ?>
                    </div>
                </td>
            </tr>
        </table>
        <hr>
        <?php
    }
//=========================================================================================================================
    public function ssbhesabix_hook_user_register($id_customer)
    {

        $user_hesabix_code = $_REQUEST['user_hesabix_code'];

        if (isset($user_hesabix_code) && $user_hesabix_code !== "") {
            $wpFaService = new HesabixWpFaService();
            $wpFaOld = $wpFaService->getWpFaByHesabixId('customer', $user_hesabix_code);
            $wpFa = $wpFaService->getWpFa('customer', $id_customer);

            if (!$wpFaOld || !$wpFa || $wpFaOld->id !== $wpFa->id) {
                if ($wpFaOld)
                    $wpFaService->delete($wpFaOld);

                if ($wpFa) {
                    $wpFa->idHesabix = $user_hesabix_code;
                    $wpFaService->update($wpFa);
                } else {
                    $wpFa = new WpFa();
                    $wpFa->objType = 'customer';
                    $wpFa->idWp = $id_customer;
                    $wpFa->idHesabix = intval($user_hesabix_code);
                    $wpFaService->save($wpFa);
                }
            }
        }

        $function = new Ssbhesabix_Admin_Functions();

        if(get_option('ssbhesabix_contact_automatically_save_in_hesabix') == 'yes')
            $function->setContact($id_customer);
    }
//=========================================================================================================================
    public function ssbhesabix_hook_delete_user($id_customer)
    {
        $wpFaService = new HesabixWpFaService();
        $id_obj = $wpFaService->getWpFaId('customer', $id_customer);
        if ($id_obj != false) {
            global $wpdb;
            $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id` = $id_obj AND `obj_type` = 'customer'");

            if (is_object($row)) {
                $hesabixApi = new Ssbhesabix_Api();
                $hesabixApi->contactDelete($row->id_hesabix);
            }

            global $wpdb;
            $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id_ps' => $id_customer));


            HesabixLogService::log(array("Customer deleted. Customer ID: $id_customer"));
        }
    }
//=========================================================================================================================
    //Invoice
    public function ssbhesabix_hook_order_status_change($id_order, $from, $to)
    {
        HesabixLogService::writeLogStr("Order Status Hook");
        $function = new Ssbhesabix_Admin_Functions();

        foreach (get_option('ssbhesabix_invoice_status') as $status) {

            HesabixLogService::writeLogStr("status: $status");

            if ($status == $to) {
                $orderResult = $function->setOrder($id_order);
                if ($orderResult) {
                    // set payment
                    foreach (get_option('ssbhesabix_payment_status') as $statusPayment) {
                        if ($statusPayment == $to)
                            $function->setOrderPayment($id_order);
                    }
                }
            }
        }

        $values = get_option('ssbhesabix_invoice_return_status');
        if(is_array($values) || is_object($values)) {
            foreach ($values as $status) {
                if ($status == $to)
                    $function->setOrder($id_order, 2, $function->getInvoiceCodeByOrderId($id_order));
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabix_hook_new_order($id_order, $order)
    {
        HesabixLogService::writeLogStr("New Order Hook");
        $function = new Ssbhesabix_Admin_Functions();
        $orderStatus = wc_get_order($id_order)->get_status();
        $orderItems = $order->get_items();

        foreach (get_option('ssbhesabix_invoice_status') as $status) {

            HesabixLogService::writeLogStr("status: $status");

            if ($status == $orderStatus) {
                $orderResult = $function->setOrder($id_order, 0, null, $orderItems);
                if ($orderResult) {
                    // set payment
                    foreach (get_option('ssbhesabix_payment_status') as $statusPayment) {
                        if ($statusPayment == $orderStatus)
                            $function->setOrderPayment($id_order);
                    }
                }
            }
        }

        HesabixLogService::log(array($orderStatus));

        $values = get_option('ssbhesabix_invoice_return_status');
        if(is_array($values) || is_object($values)) {
            foreach ($values as $status) {
                if ($status == $orderStatus)
                    $function->setOrder($id_order, 2, $function->getInvoiceCodeByOrderId($id_order), $orderItems);
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabix_hook_payment_confirmation($id_order, $from, $to)
    {
        foreach (get_option('ssbhesabix_payment_status') as $status) {
            if ($status == $to) {
                $function = new Ssbhesabix_Admin_Functions();
                $function->setOrderPayment($id_order);
            }
        }
    }

    //Item
    private $call_time = 1;
//=========================================================================================================================
    public function ssbhesabix_hook_new_product($id_product)
    {
//        if (get_option("ssbhesabix_inside_product_edit", 0) === 1)
//            return;

        if ($this->call_time === 1) {
            $this->call_time++;
            return;
        } else {
            $this->call_time = 1;
        }

        if (get_option("ssbhesabix_do_not_submit_product_automatically", "no") === "yes") return;
        $function = new Ssbhesabix_Admin_Functions();
        $function->setItems(array($id_product));
    }
//=========================================================================================================================
    public function ssbhesabix_hook_save_product_variation($id_attribute)
    {

        HesabixLogService::writeLogStr("ssbhesabix_hook_save_product_variation");

        if (get_option("ssbhesabix_do_not_submit_product_automatically", "no") === "yes" || get_option("ssbhesabix_do_not_submit_product_automatically", "no") == "1") {
            //change hesabix item code
            $variable_field_id = "ssbhesabix_hesabix_item_code_" . $id_attribute;
            $code = $_POST[$variable_field_id];
            $id_product = $_POST['product_id'];

            if ($code === "")
                return;

            if (isset($code)) {
                global $wpdb;
                $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_hesabix` = " . $code . " AND `obj_type` = 'product'");

                if (is_object($row)) {
                    if ($row->id_ps == $id_product && $row->id_ps_attribute == $id_attribute) {
                        return false;
                    }

                    echo '<div class="error"><p>' . __('The new Item code already used for another Item', 'ssbhesabix') . '</p></div>';

                    HesabixLogService::log(array("The new Item code already used for another Item. Product ID: $id_product"));
                } else {
                    $row2 = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps` = $id_product AND `obj_type` = 'product' AND `id_ps_attribute` = $id_attribute");

                    if (is_object($row2)) {
                        $wpdb->update($wpdb->prefix . 'ssbhesabix', array(
                            'id_hesabix' => (int)$code,
                        ), array(
                            'id_ps' => $id_product,
                            'id_ps_attribute' => $id_attribute,
                            'obj_type' => 'product',
                        ));
                    } else if ((int)$code !== 0) {
                        $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                            'id_hesabix' => (int)$code,
                            'id_ps' => (int)$id_product,
                            'id_ps_attribute' => $id_attribute,
                            'obj_type' => 'product',
                        ));
                    }
                }
            }

            //add attribute if not exists
            $func = new Ssbhesabix_Admin_Functions();
            $wpFaService = new HesabixWpFaService();
            $code = $wpFaService->getProductCodeByWpId($id_product, $id_attribute);
            if ($code == null) {
                $func->setItems(array($id_product));
            }
        }
    }
//=========================================================================================================================
    //ToDo: check why base product is not deleted
    public function ssbhesabix_hook_delete_product($id_product)
    {

        HesabixLogService::writeLogStr("Product Delete Hook");

        $func = new Ssbhesabix_Admin_Functions();
        $wpFaService = new HesabixWpFaService();

        $hesabixApi = new Ssbhesabix_Api();
        global $wpdb;

        $variations = $func->getProductVariations($id_product);
        if ($variations != false) {
            foreach ($variations as $variation) {
                $id_attribute = $variation->get_id();
                $code = $wpFaService->getProductCodeByWpId($id_product, $id_attribute);
                if ($code != false) {
                    $hesabixApi->itemDelete($code);
                    $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => $code, 'obj_type' => 'product'));

                    HesabixLogService::log(array("Product variation deleted. Product ID: $id_product-$id_attribute"));
                }
            }
        }

        $code = $wpFaService->getProductCodeByWpId($id_product);

        if ($code != false) {
            $hesabixApi->itemDelete($code);
            $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => $code, 'obj_type' => 'product'));

            HesabixLogService::log(array("Product deleted. Product ID: $id_product"));
        }
    }
//=========================================================================================================================
    public function ssbhesabix_hook_delete_product_variation($id_attribute)
    {
//        $func = new Ssbhesabix_Admin_Functions();

        $hesabixApi = new Ssbhesabix_Api();
        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps_attribute` = $id_attribute AND `obj_type` = 'product'");

        if (is_object($row)) {
            $hesabixApi->itemDelete($row->id_hesabix);

            $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id' => $row->id));

            HesabixLogService::log(array("Product variation deleted. Product ID: $row->id_ps-$id_attribute"));
        }
    }
//=========================================================================================================================
    public function ssbhesabix_hook_product_options_general_product_data()
    {
        $wpFaService = new HesabixWpFaService();
        $value = isset($_GET['post']) ? $wpFaService->getProductCodeByWpId($_GET['post']) : '';
        $args = array(
            'id' => 'ssbhesabix_hesabix_item_code_0',
            'label' => __('Hesabix base item code', 'ssbhesabix'),
            'desc_tip' => true,
            'description' => __('The base Item code of this product in Hesabix, if you want to map this product to another item in Hesabix, enter the new Item code.', 'ssbhesabix'),
            'value' => $value,
            'type' => 'number',
        );
        woocommerce_wp_text_input($args);
    }
//=========================================================================================================================
    public function ssbhesabix_hook_process_product_meta($post_id)
    {
        $itemCode = isset($_POST['ssbhesabix_hesabix_item_code_0']) ? $_POST['ssbhesabix_hesabix_item_code_0'] : '';

        if ($itemCode === "")
            return;

        if (isset($itemCode)) {
            global $wpdb;
            $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_hesabix` = " . $itemCode . " AND `obj_type` = 'product'");

            if (is_object($row)) {
                //ToDo: show error to customer in BO
                echo '<div class="error"><p>' . __('The new Item code already used for another Item', 'ssbhesabix') . '</p></div>';

                HesabixLogService::log(array("The new Item code already used for another Item. Product ID: $post_id"));
            } else {
                $row2 = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps` = $post_id AND `obj_type` = 'product' AND `id_ps_attribute` = 0");
                if (is_object($row2)) {
                    $wpdb->update($wpdb->prefix . 'ssbhesabix', array(
                        'id_hesabix' => (int)$itemCode,
                    ), array(
                        'id_ps' => $post_id,
                        'id_ps_attribute' => 0,
                        'obj_type' => 'product',
                    ));
                } else if ((int)$itemCode !== 0) {
                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                        'id_hesabix' => (int)$itemCode,
                        'id_ps' => (int)$post_id,
                        'id_ps_attribute' => 0,
                        'obj_type' => 'product',
                    ));
                }
            }
        }
    }
//=========================================================================================================================
    public function ssbhesabix_hook_product_after_variable_attributes($loop, $variation_data, $variation)
    {
        $wpFaService = new HesabixWpFaService();
        $value = isset($_POST['product_id']) ? $wpFaService->getProductCodeByWpId($_POST['product_id'], $variation->ID) : '';
        $args = array(
            'id' => 'ssbhesabix_hesabix_item_code_' . $variation->ID,
            'label' => __('Hesabix variable item code', 'ssbhesabix'),
            'desc_tip' => true,
            'description' => __('The variable Item code of this product variable in Hesabix, if you want to map this product to another item in Hesabix, enter the new Item code.', 'ssbhesabix'),
            'value' => $value,
        );
        woocommerce_wp_text_input($args);
    }
//=========================================================================================================================
    /*
    * Action - Ajax 'clean log file' from Hesabix/Log tab
    * @since	1.0.0
    */
    public function adminCleanLogFileCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $func = new Ssbhesabix_Admin_Functions();
            $result = $func->cleanLogFile();

            if ($result) {
                $redirect_url = admin_url('admin.php?page=ssbhesabix-option&tab=log&cleanLogResult=true');
            } else {
                $redirect_url = admin_url('admin.php?page=ssbhesabix-option&tab=log&cleanLogResult=false');
            }
            echo $redirect_url;

            die();
        }
    }
//=========================================================================================================================
    // custom data tab in edit product page in admin panel
    function add_hesabix_product_data_tab($product_data_tabs)
    {
        $product_data_tabs['hesabix'] = array(
            'label' => __('Hesabix', 'ssbhesabix'),
            'target' => 'panel_product_data_hesabix',
        );
        return $product_data_tabs;
    }
//=========================================================================================================================
    function add_hesabix_product_data_fields()
    {
        global $woocommerce, $post, $product;

        $funcs = new Ssbhesabix_Admin_Functions();
        $items = array();
        $id_product = $post->ID;
//        $product = new WC_Product($id_product);
        $product = wc_get_product($id_product);

        if ($product->get_status() === "auto-draft") {
            ?>
            <div id="panel_product_data_hesabix" class="panel woocommerce_options_panel"
                 data-product-id="<?php echo $id_product ?>">
                هنوز محصول ذخیره نشده است.
                <br>
                پس از ذخیره محصول، در این قسمت می توانید ارتباط محصول و متغیرهای آن با حسابیکس
                را مدیریت کنید.
            </div>
            <?php
            return;
        }
        global $items;
        $items[] = ssbhesabixItemService::mapProduct($product, $id_product, false);
        $items[0]["Quantity"] = $product->get_stock_quantity();
        $items[0]["Id"] = $id_product;
        $i = 1;

        $variations = $funcs->getProductVariations($id_product);
        if ($variations) {
            foreach ($variations as $variation) {
                $items[] = ssbhesabixItemService::mapProductVariation($product, $variation, $id_product, false);
                $items[$i]["Quantity"] = $variation->get_stock_quantity();
                $items[$i]["Id"] = $variation->get_id();
                $i++;
            }
        }

        ?>
        <div id="panel_product_data_hesabix" class="panel woocommerce_options_panel"
             data-product-id="<?php echo $id_product ?>">
            <table class="table table-striped">
                <tr class="small fw-bold">
                    <td>نام کالا</td>
                    <td>کد در حسابیکس</td>
                    <td>ذخیره کد</td>
                    <td>حذف ارتباط</td>
                    <td>بروزرسانی قیمت و موجودی</td>
                    <td>قیمت</td>
                    <td>موجودی</td>
                </tr>
                <?php
                foreach ($items as $item) {
                    ?>
                    <tr>
                        <td><?php echo $item["Name"]; ?></td>
                        <td><input type="text" value="<?php echo $item["Code"]; ?>"
                                   id="hesabix-item-<?php echo $item["Id"]; ?>" style="width: 75px;"
                                   class="hesabix-item-code" data-id="<?php echo $item["Id"]; ?>"></td>
                        <td><input type="button" value="ذخیره" data-id="<?php echo $item["Id"]; ?>"
                                   class="button hesabix-item-save"></td>
                        <td><input type="button" value="حذف ارتباط" data-id="<?php echo $item["Id"]; ?>"
                                   class="button hesabix-item-delete-link"></td>
                        <td><input type="button" value="بروزرسانی" data-id="<?php echo $item["Id"]; ?>"
                                   class="button button-primary hesabix-item-update"></td>
                        <td id="hesabix-item-price-<?php echo $item["Id"] ?>"><?php echo Ssbhesabix_Admin_Functions::getPriceInWooCommerceDefaultCurrency($item["SellPrice"]); ?></td>
                        <td id="hesabix-item-quantity-<?php echo $item["Id"] ?>"><?php echo $item["Quantity"]; ?></td>
                    </tr>
                    <?php
                }
                ?>
            </table>

            <input type="button" value="ذخیره همه" id="hesabix-item-save-all" class="button">
            <input type="button" value="حذف ارتباط همه" id="hesabix-item-delete-link-all" class="button">
            <input type="button" value="بروزرسانی همه" id="hesabix-item-update-all" class="button button-primary">

        </div>
        <?php
    }
//=========================================================================================================================
    function admin_products_hesabixId_column( $columns ){
        echo '<style>
        #hesabixID {
            width: 5vw;
            color: #2271b1;
        }
        </style>';
        return array_slice($columns, 0, 3, true) + array('hesabixID' => 'کد حسابیکس') + array_slice($columns, 3, count($columns) - 3, true);
    }
//======
    function admin_products_hesabixId_column_content( $column ){
        $funcs = new Ssbhesabix_Admin_Functions();
        $items = array();
        $id_product = get_the_ID();
//        $product = new WC_Product($id_product);
        $product = wc_get_product($id_product);

        $items[] = ssbhesabixItemService::mapProduct($product, $id_product, false);
        $i = 1;

        $variations = $funcs->getProductVariations($id_product);
        if ($variations) {
            foreach ($variations as $variation) {
                $items[] = ssbhesabixItemService::mapProductVariation($product, $variation, $id_product, false);
                $i++;
            }
        }

        echo '<div>';
        foreach ($items as $item) {
            if ( $column == 'hesabixID' ) {
                $hesabixId = $item["Code"];
                echo "<span class='button button-secondary'>" . $hesabixId . " " . "</span>";
            }
        }
        echo '</div>';
    }
//=========================================================================================================================
    function adminChangeProductCodeCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $productId = (int)wc_clean($_POST['productId']);
            $attributeId = (int)wc_clean($_POST['attributeId']);
            if ($productId == $attributeId) $attributeId = 0;
            $code = (int)wc_clean($_POST['code']);
            $result = array();

            if (!$code) {
                $result["error"] = true;
                $result["message"] = "کد کالا وارد نشده است.";
                echo json_encode($result);
                die();
                return;
            }

            $wpFaService = new HesabixWpFaService();
            $wpFa = $wpFaService->getWpFaByHesabixId('product', $code);
            if ($wpFa) {
                $result["error"] = true;
                $result["message"] = "این کد به کالای دیگری متصل است. \n" . $wpFa->idWp . " - " . $wpFa->idWpAttribute;
                echo json_encode($result);
                die();
                return;
            }

            $api = new Ssbhesabix_Api();
            $response = $api->itemGet($code);
            if (!$response->Success) {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابیکس پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }

            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {
                $wpFa->idHesabix = $code;
                $wpFaService->update($wpFa);
            } else {
                $wpFa = new WpFa();
                $wpFa->idHesabix = $code;
                $wpFa->idWp = $productId;
                $wpFa->idWpAttribute = $attributeId;
                $wpFa->objType = 'product';
                $wpFaService->save($wpFa);
            }
            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminDeleteProductLinkCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $productId = wc_clean($_POST['productId']);
            $attributeId = wc_clean($_POST['attributeId']);
            if ($productId == $attributeId) $attributeId = 0;
            $result = array();

            $wpFaService = new HesabixWpFaService();
            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {
                $wpFaService->delete($wpFa);
                HesabixLogService::writeLogStr("حذف ارتباط کالا. کد کالا: " . $productId . " - ". "کد متغیر:". $attributeId);
            }

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminUpdateProductCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            if (get_option('ssbhesabix_item_update_price', 'no') == 'no' &&
                get_option('ssbhesabix_item_update_quantity', 'no') == 'no') {
                $result["error"] = true;
                $result["message"] = "خطا: در تنظیمات افزونه، گزینه های بروزرسانی قیمت و موجودی محصول بر اساس حسابیکس فعال نیستند.";
                echo json_encode($result);
                die();
            }

            $productId = wc_clean($_POST['productId']);
            $attributeId = wc_clean($_POST['attributeId']);

            if (get_option('ssbhesabix_item_update_quantity', 'no') == 'yes')
                update_post_meta($attributeId, '_manage_stock', 'yes');

            if ($productId == $attributeId) $attributeId = 0;
            $result = array();

            $wpFaService = new HesabixWpFaService();
            $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
            if ($wpFa) {

                $api = new Ssbhesabix_Api();
                $warehouse = get_option('ssbhesabix_item_update_quantity_based_on', "-1");
                if ($warehouse == "-1")
                    $response = $api->itemGet($wpFa->idHesabix);
                else {
                    $response = $api->itemGetQuantity($warehouse, array($wpFa->idHesabix));
                }

                if ($response->Success) {
                    $item = $warehouse == "-1" ? $response->Result : $response->Result[0];
                    $newProps = Ssbhesabix_Admin_Functions::setItemChanges($item);
                    $result["error"] = false;
                    $result["newPrice"] = $newProps["newPrice"];
                    $result["newQuantity"] = $newProps["newQuantity"];
                } else {
                    $result["error"] = true;
                    $result["message"] = "کالا در حسابیکس پیدا نشد.";
                }
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminChangeProductsCodeCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {
            $wpFaService = new HesabixWpFaService();

            $productId = (int)wc_clean($_POST['productId']);
            $itemsData = wc_clean($_POST['itemsData'], true);
            $result = array();
            $codes = [];

            foreach ($itemsData as $itemData) {
                $attributeId = (int)$itemData["attributeId"];
                $code = (int)$itemData["code"];
                if ($productId == $attributeId) $attributeId = 0;
                $codes[] = str_pad($code, 6, "0", STR_PAD_LEFT);

                if (!$code) {
                    $result["error"] = true;
                    $result["message"] = "کد کالا وارد نشده است.";
                    echo json_encode($result);
                    die();
                    return;
                }

                $wpFa = $wpFaService->getWpFaByHesabixId('product', $code);
                $wpFa2 = $wpFaService->getWpFa('product', $productId, $attributeId);
                if ($wpFa && $wpFa2 && $wpFa->id != $wpFa2->id) {
                    $result["error"] = true;
                    $result["message"] = "این کد ($code) به کالای دیگری متصل است. \n" . $wpFa->idWp . " - " . $wpFa->idWpAttribute;
                    echo json_encode($result);
                    die();
                    return;
                }
            }

            $api = new Ssbhesabix_Api();
            $response = $api->itemGetItemsByCodes(array('values' => $codes));
            if ($response->Success) {
                $items = $response->result;
                foreach ($codes as $code) {
                    $found = false;
                    foreach ($items as $item) {
                        if ($item->code == $code)
                            $found = true;
                    }
                    if (!$found) {
                        $result["error"] = true;
                        $result["message"] = "کالایی با کد $code در حسابیکس پیدا نشد.";
                        echo json_encode($result);
                        die();
                        return;
                    }
                }
            } else {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابیکس پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }


            foreach ($itemsData as $itemData) {
                $attributeId = (int)$itemData["attributeId"];
                $code = (int)$itemData["code"];
                if ($productId == $attributeId) $attributeId = 0;

                $wpFa = $wpFaService->getWpFa('product', $productId, $attributeId);
                if ($wpFa) {
                    $wpFa->idHesabix = $code;
                    $wpFaService->update($wpFa);
                } else {
                    $wpFa = new WpFa();
                    $wpFa->idHesabix = $code;
                    $wpFa->idWp = $productId;
                    $wpFa->idWpAttribute = $attributeId;
                    $wpFa->objType = 'product';
                    $wpFaService->save($wpFa);
                }
            }

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminDeleteProductsLinkCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            $productId = wc_clean($_POST['productId']);
            $result = array();

            $wpFaService = new HesabixWpFaService();
            $wpFaService->deleteAll($productId);
            HesabixLogService::writeLogStr("حذف ارتباط کالاها. کد کالا: " . $productId);

            $result["error"] = false;
            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function adminUpdateProductAndVariationsCallback()
    {
        if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

            if (get_option('ssbhesabix_item_update_price', 'no') == 'no' &&
                get_option('ssbhesabix_item_update_quantity', 'no') == 'no') {
                $result["error"] = true;
                $result["message"] = "خطا: در تنظیمات افزونه، گزینه های بروزرسانی قیمت و موجودی محصول بر اساس حسابیکس فعال نیستند.";
                echo json_encode($result);
                die();
            }

            //Call API
            $api = new Ssbhesabix_Api();
            $wpFaService = new HesabixWpFaService();

            $productId = wc_clean($_POST['productId']);
            $productAndCombinations = $wpFaService->getProductAndCombinations($productId);
            $result = array();
            if (count($productAndCombinations) == 0) {
                $result["error"] = true;
                $result["message"] = "هیچ ارتباطی پیدا نشد.";
                echo json_encode($result);
                die();
            }
            $codes = [];
            $ssbhesabix_item_update_quantity = get_option('ssbhesabix_item_update_quantity', 'no');
            foreach ($productAndCombinations as $p) {
                $codes[] = str_pad($p->idHesabix, 6, "0", STR_PAD_LEFT);

                if ($ssbhesabix_item_update_quantity == 'yes')
                    update_post_meta($p->idWpAttribute == 0 ? $p->idWp : $p->idWpAttribute, '_manage_stock', 'yes');
            }

            $warehouse = get_option('ssbhesabix_item_update_quantity_based_on', "-1");
            if ($warehouse == "-1")
                $response = $api->itemGetItemsByCodes($codes);
            else {
                $response = $api->itemGetQuantity($warehouse, $codes);
            }

            if ($response->Success) {
                $items = $warehouse == "-1" ? $response->Result->List : $response->Result;
                $newData = [];
                $result["error"] = false;
                foreach ($items as $item) {
                    $newProps = Ssbhesabix_Admin_Functions::setItemChanges($item);
                    $wpFa = $wpFaService->getWpFaByHesabixId('product', $item->Code);
                    $newData[] = array("newPrice" => $newProps["newPrice"],
                        "newQuantity" => $newProps["newQuantity"],
                        "attributeId" => $wpFa->idWpAttribute > 0 ? $wpFa->idWpAttribute : $wpFa->idWp);
                }
                $result["newData"] = $newData;
            } else {
                $result["error"] = true;
                $result["message"] = "کالایی با کد وارد شده در حسابیکس پیدا نشد.";
                echo json_encode($result);
                die();
                return;
            }

            echo json_encode($result);
            die();
        }
    }
//=========================================================================================================================
    function add_additional_fields_to_checkout( $fields ) {
        $NationalCode_isActive = get_option('ssbhesabix_contact_NationalCode_checkbox_hesabix');
        $EconomicCode_isActive = get_option('ssbhesabix_contact_EconomicCode_checkbox_hesabix');
        $RegistrationNumber_isActive = get_option('ssbhesabix_contact_RegistrationNumber_checkbox_hesabix');
        $Website_isActive = get_option('ssbhesabix_contact_Website_checkbox_hesabix');

	    $NationalCode_isRequired = get_option('ssbhesabix_contact_NationalCode_isRequired_hesabix');
	    $EconomicCode_isRequired = get_option('ssbhesabix_contact_EconomicCode_isRequired_hesabix');
	    $RegistrationNumber_isRequired = get_option('ssbhesabix_contact_RegistrationNumber_isRequired_hesabix');
	    $Website_isRequired = get_option('ssbhesabix_contact_Website_isRequired_hesabix');

        //NationalCode
	    if($NationalCode_isActive == 'yes'){
		    $fields['billing']['billing_hesabix_nationalcode'] = array(
               'label'     => __('National code', 'ssbhesabix'),
               'placeholder'   => __('please enter your National code', 'ssbhesabix'),
               'priority' => 30,
               'required'  => (bool) $NationalCode_isRequired,
               'clear'     => true,
               'maxlength' => 10,
            );
        }
        //Economic code
	    if($EconomicCode_isActive == 'yes'){
            $fields['billing']['billing_hesabix_economiccode'] = array(
               'label'     => __('Economic code', 'ssbhesabix'),
               'placeholder'   => __('please enter your Economic code', 'ssbhesabix'),
               'priority' => 31,
               'required'  => (bool) $EconomicCode_isRequired,
               'clear'     => true
               );
	    }
        //Registration Number
	    if($RegistrationNumber_isActive == 'yes'){
		    $fields['billing']['billing_hesabix_registerationnumber'] = array(
               'label'     => __('Registration number', 'ssbhesabix'),
               'placeholder'   => __('please enter your Registration number', 'ssbhesabix'),
               'priority' => 32,
               'required'  => (bool) $RegistrationNumber_isRequired,
               'clear'     => true
               );
	    }
        //Website
	    if($Website_isActive == 'yes'){
		    $fields['billing']['billing_hesabix_website'] = array(
               'type' => 'url',
               'label'     => __('Website', 'ssbhesabix'),
               'placeholder'   => __('please enter your Website address', 'ssbhesabix'),
               'priority' => 33,
               'required'  => (bool) $Website_isRequired,
               'clear'     => true,
             );
	    }
        if(isset($_POST['billing_hesabix_nationalcode']) || isset($_POST['billing_hesabix_website'])) {
            $func = new Ssbhesabix_Admin_Functions();
            $NationalCode = $_POST['billing_hesabix_nationalcode'];
            $Website = $_POST['billing_hesabix_website'];
            if($NationalCode_isRequired) {
                $func->checkNationalCode($NationalCode);
            }

            if($Website_isRequired) {
                $func->checkWebsite($Website);
            }
        }
	        return $fields;
    }
//=========================================================================================================================
    function show_additional_fields_in_order_detail($order) {
        //this function is used to show codes and website in woocommerce orders detail
        $orderId = $order->get_id();
	    $NationalCode = '_billing_hesabix_nationalcode';
        $EconomicCode = '_billing_hesabix_economiccode';
	    $RegistrationNumber = '_billing_hesabix_registerationnumber';
	    $Website = '_billing_hesabix_website';

	    $NationalCode_isActive = get_option('ssbhesabix_contact_NationalCode_checkbox_hesabix');
	    $EconomicCode_isActive = get_option('ssbhesabix_contact_EconomicCode_checkbox_hesabix');
	    $RegistrationNumber_isActive = get_option('ssbhesabix_contact_RegistrationNumber_checkbox_hesabix');
	    $Website_isActive = get_option('ssbhesabix_contact_Website_checkbox_hesabix');

	    if($NationalCode_isActive == 'yes') {
		    echo '<p><strong>' . __('National code', 'ssbhesabix')  . ': </strong> ' .'<br>'. '<strong>' . get_post_meta( $orderId, $NationalCode, true ) . '</strong></p>';
        }

	    if($EconomicCode_isActive == 'yes')
		    echo '<p><strong>' . __('Economic code', 'ssbhesabix')  . ': </strong> ' .'<br>'. '<strong>' . get_post_meta( $orderId, $EconomicCode, true ) . '</strong></p>';

	    if($RegistrationNumber_isActive == 'yes')
		    echo '<p><strong>' . __('Registration number', 'ssbhesabix')  . ': </strong> ' .'<br>'. '<strong>' . get_post_meta( $orderId, $RegistrationNumber, true ) . '</strong></p>';

	    if($Website_isActive == 'yes')
		    echo '<p><strong>' . __('Website', 'ssbhesabix')  . ': </strong> ' .'<br>'. '<a target="_blank" href="https://'.get_post_meta( $orderId, $Website, true ) .'">' . get_post_meta( $orderId, $Website, true ) . '</a></p>';
    }
//=========================================================================================================================
}
