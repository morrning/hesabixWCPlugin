<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @class      Ssbhesabix_Activator
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/includes
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */
class Ssbhesabix_Activator {
    public static $ssbhesabix_db_version = '1.1';

    /**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
//===============================================================================================================
	public static function activate() {
        add_option('ssbhesabix_webhook_password', bin2hex(openssl_random_pseudo_bytes(16)));
        add_option('ssbhesabix_last_log_check_id', 0);
        add_option('ssbhesabix_live_mode', 0);
        add_option('ssbhesabix_debug_mode', 0);
        add_option('ssbhesabix_contact_address_status', 1);
        add_option('ssbhesabix_contact_node_family', 'مشتریان فروشگاه آن‌لاین');
        add_option('ssbhesabix_contact_automaatic_save_node_family', 'yes');
        add_option('ssbhesabix_contact_automatically_save_in_hesabix', 'yes');
        add_option('ssbhesabix_activation_date', date("Y-m-d"));
        add_option('ssbhesabix_use_export_product_opening_quantity', false);
        add_option('ssbhesabix_business_expired', 0);
        add_option('ssbhesabix_do_not_submit_product_automatically', "no");
        add_option('ssbhesabix_do_not_update_product_price_in_hesabix', "no");
        add_option('ssbhesabix_contact_add_additional_checkout_fields_hesabix', 1);

        self::ssbhesabix_create_database_table();
	}
//===============================================================================================================
    public static function ssbhesabix_create_database_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "ssbhesabix";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE $table_name (
                id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                obj_type varchar(32) NOT NULL,
                id_hesabix int(11) UNSIGNED NOT NULL,
                id_ps int(11) UNSIGNED NOT NULL,
                id_ps_attribute int(11) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
            ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      	dbDelta($sql);

        update_option('ssbhesabix_db_version', self::$ssbhesabix_db_version);
    }
//===============================================================================================================
}
