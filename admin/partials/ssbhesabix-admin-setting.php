<?php

include_once( plugin_dir_path( __DIR__ ) . 'services/HesabixLogService.php' );
error_reporting(0);
/**
 * @class      Ssbhesabix_Setting
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/admin/setting
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */
class Ssbhesabix_Setting {

	/**
	 * Hook in methods
	 * @since    1.0.0
	 * @access   static
	 */
//==========================================================================================================================
    public static function init() {
		add_action( 'ssbhesabix_home_setting', array( __CLASS__, 'ssbhesabix_home_setting' ) );

		add_action( 'ssbhesabix_catalog_setting', array( __CLASS__, 'ssbhesabix_catalog_setting' ) );
		add_action( 'ssbhesabix_catalog_setting_save_field', array(
			__CLASS__,
			'ssbhesabix_catalog_setting_save_field'
		) );

		add_action( 'ssbhesabix_customers_setting', array( __CLASS__, 'ssbhesabix_customers_setting' ) );
		add_action( 'ssbhesabix_customers_setting_save_field', array(
			__CLASS__,
			'ssbhesabix_customers_setting_save_field'
		) );

		add_action( 'ssbhesabix_invoice_setting', array( __CLASS__, 'ssbhesabix_invoice_setting' ) );
		add_action( 'ssbhesabix_invoice_setting_save_field', array(
			__CLASS__,
			'ssbhesabix_invoice_setting_save_field'
		) );

		add_action( 'ssbhesabix_payment_setting', array( __CLASS__, 'ssbhesabix_payment_setting' ) );
		add_action( 'ssbhesabix_payment_setting_save_field', array(
			__CLASS__,
			'ssbhesabix_payment_setting_save_field'
		) );

		add_action( 'ssbhesabix_api_setting', array( __CLASS__, 'ssbhesabix_api_setting' ) );
		add_action( 'ssbhesabix_api_setting_save_field', array( __CLASS__, 'ssbhesabix_api_setting_save_field' ) );

		add_action( 'ssbhesabix_export_setting', array( __CLASS__, 'ssbhesabix_export_setting' ) );

		add_action( 'ssbhesabix_sync_setting', array( __CLASS__, 'ssbhesabix_sync_setting' ) );

		add_action( 'ssbhesabix_log_setting', array( __CLASS__, 'ssbhesabix_log_setting' ) );

		add_action( 'ssbhesabix_extra_setting', array( __CLASS__, 'ssbhesabix_extra_setting' ) );
        add_action( 'ssbhesabix_extra_setting_save_field', array(
            __CLASS__,
            'ssbhesabix_extra_setting_save_field'
        ) );
    }
//==========================================================================================================================
	public static function ssbhesabix_home_setting() {
		?>
        <h3 class="h3 hesabix-tab-page-title mt-4"><?php esc_attr_e( 'Hesabix Accounting', 'ssbhesabix' ); ?></h3>
        <p class="p mt-4 hesabix-p hesabix-f-12 ms-3"
           style="text-align: justify"><?php esc_attr_e( 'This module helps connect your (online) store to Hesabix online accounting software. By using this module, saving products, contacts, and orders in your store will also save them automatically in your Hesabix account. Besides that, just after a client pays a bill, the receipt document will be stored in Hesabix as well. Of course, you have to register your account in Hesabix first. To do so, visit Hesabix at the link here hesabix.ir and sign up for free. After you signed up and entered your account, choose your business, then in the settings menu/API, you can find the API keys for the business and import them to the plugin’s settings. Now your module is ready to use.', 'ssbhesabix' ); ?></p>
        <p class="p hesabix-p hesabix-f-12"><?php esc_attr_e( 'For more information and a full guide to how to use Hesabix and WooCommerce Plugin, visit Hesabix’s website and go to the “Guides and Tutorials” menu.', 'ssbhesabix' ); ?></p>

        <div class="alert alert-danger hesabix-f mt-4">
            <strong>هشدارها</strong>
            <br>
            <ul class="mt-2">
                <li> *
                    افزونه حسابیکس از کد کالاها و مشتریان و از شماره فاکتور جهت شناسایی آنها استفاده می کند،
                    بنابراین پس از ثبت کالاها و مشتریان در حسابیکس کد آنها را در حسابیکس تغییر ندهید، و همچنین پس از ثبت
                    فاکتور،
                    شماره فاکتور را در حسابیکس نباید تغییر دهید.
                </li>
                <li>
                    * با حذف افزونه از وردپرس، جدول ارتباط بین افزونه و حسابیکس نیز از دیتابیس وردپرس حذف می شود
                    و کلیه ارتباطات از بین می رود.
                </li>
            </ul>
        </div>

		<?php
	}
//==============================================================================================
    public static function ssbhesabix_extra_setting_fields() {
        $fields[] = array(
            'desc' => __('Enable or Disable Debug Mode', 'ssbhesabix'),
            'id'    => 'ssbhesabix_debug_mode_checkbox',
            'default' => 'no',
            'type'  => 'checkbox',
        );

        return $fields;
    }
//==============================================================================================
    public static function ssbhesabix_extra_setting() {
        ?>
        <div class="alert alert-warning hesabix-f">
            <ul class="mt-2">
                <li>
                    این صفحه برای تنظیمات پیشرفته افزونه می باشد
                </li>
            </ul>
        </div>

        <h3><?php echo __( 'Extra Settings', 'ssbhesabix' ); ?></h3>

        <?php
            $ssbhesabf_setting_fields = self::ssbhesabix_extra_setting_fields();
            $Html_output = new Ssbhesabix_Html_output();
        ?>
        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
            <?php
                global $plugin_version;
                if (defined('SSBHESABIX_VERSION')) {
                    $plugin_version = constant('SSBHESABIX_VERSION');
                }
                $server_php_version  = phpversion();
                $plugin_php_version = '8.1';

                echo
                    '<table style="width: 98%;" class="table table-stripped">
                        <thead>
                            <tr style="direction: ltr;">
                                <th>Plugin Version</th>
                                <th>Server PHP Version</th>
                                <th>Plugin PHP Version Tested Up To</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="direction: ltr;">
                                <td>' . $plugin_version . '</td>
                                <td>' . $server_php_version . '</td>                                
                                <td>' . $plugin_php_version . '</td>                                
                            </tr>
                        </tbody>
                    '

                    . '</table>';
            ?>
            <div class="d-flex flex-column">
                <?php $Html_output->init( $ssbhesabf_setting_fields ); ?>
            </div>
            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
        </form>
        <?php
        if(get_option('ssbhesabix_debug_mode_checkbox') == 'yes' || get_option('ssbhesabix_debug_mode_checkbox') == '1') {
            Ssbhesabix_Admin_Functions::enableDebugMode();
        } elseif(get_option('ssbhesabix_debug_mode_checkbox') == 'no' || get_option('ssbhesabix_debug_mode_checkbox') == '0') {
            Ssbhesabix_Admin_Functions::disableDebugMode();
        }

        if(isset($_POST["ssbhesabix_integration"])) {
            if(isset($_POST['ssbhesabix_set_rpp_for_sync_products_into_hesabix'])) update_option('ssbhesabix_set_rpp_for_sync_products_into_hesabix', $_POST['ssbhesabix_set_rpp_for_sync_products_into_hesabix']);
            if(isset($_POST['ssbhesabix_set_rpp_for_sync_products_into_woocommerce'])) update_option('ssbhesabix_set_rpp_for_sync_products_into_woocommerce', $_POST['ssbhesabix_set_rpp_for_sync_products_into_woocommerce']);
            if(isset($_POST['ssbhesabix_set_rpp_for_import_products'])) update_option('ssbhesabix_set_rpp_for_import_products', $_POST['ssbhesabix_set_rpp_for_import_products']);
            if(isset($_POST['ssbhesabix_set_rpp_for_export_products'])) update_option('ssbhesabix_set_rpp_for_export_products', $_POST['ssbhesabix_set_rpp_for_export_products']);
            if(isset($_POST['ssbhesabix_set_rpp_for_export_opening_products'])) update_option('ssbhesabix_set_rpp_for_export_opening_products', $_POST['ssbhesabix_set_rpp_for_export_opening_products']);
            header('refresh:0');
        }
    }
//==============================================================================================
    public static function ssbhesabix_extra_setting_save_field() {
        $ssbhesabf_setting_fields = self::ssbhesabix_extra_setting_fields();
        $Html_output              = new Ssbhesabix_Html_output();
        $Html_output->save_fields( $ssbhesabf_setting_fields );
    }
//==============================================================================================
	public static function ssbhesabix_catalog_setting_fields() {
		$warehouses = Ssbhesabix_Setting::ssbhesabix_get_warehouses();

		$fields[] = array(
			'title' => __( 'Catalog Settings', 'ssbhesabix' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'catalog_options'
		);

		$fields[] = array(
			'title'   => __( 'Update Price', 'ssbhesabix' ),
			'desc'    => __( 'Update Price after change in Hesabix', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_item_update_price',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => __( 'Update Quantity', 'ssbhesabix' ),
			'desc'    => __( 'Update Quantity after change in Hesabix', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_item_update_quantity',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => __( "Update product's quantity based on", 'ssbhesabix' ),
			'id'      => 'ssbhesabix_item_update_quantity_based_on',
			'type'    => 'select',
			'options' => $warehouses,
		);

		$fields[] = array(
			'title'   => "",
			'desc'    => __( 'Do not submit product in Hesabix automatically by saving product in woocommerce', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_do_not_submit_product_automatically',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => "",
			'desc'    => __( 'Do not update product price in Hesabix by editing product in woocommerce', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_do_not_update_product_price_in_hesabix',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => "",
			'desc'    => __( 'Do not update product barcode in Hesabix by saving product in woocommerce', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_do_not_update_product_barcode_in_hesabix',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => "",
			'desc'    => __( 'Do not update product category in Hesabix by saving product in woocommerce', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_do_not_update_product_category_in_hesabix',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$fields[] = array(
			'title'   => "",
			'desc'    => __( 'Do not update product code in Hesabix by saving product in woocommerce', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_do_not_update_product_product_code_in_hesabix',
			'default' => 'no',
			'type'    => 'checkbox'
		);

        $fields[] = array(
			'title'   => "",
			'desc'    => __( 'Show Hesabix ID in Products Page', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_show_product_code_in_products_page',
			'default' => 'no',
			'type'    => 'checkbox'
		);

        $fields[] = array(
			'title'   => "",
			'desc'    => __( 'Set Special Sale as Discount in invoice', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_set_special_sale_as_discount',
			'default' => 'no',
			'type'    => 'checkbox'
		);

		$options_to_update_sale_price    = array();
		$options_to_update_sale_price[0] = __( "The Sale price does not change", 'ssbhesabix' );
		$options_to_update_sale_price[1] = __( "The Sale price gets removed", 'ssbhesabix' );
		$options_to_update_sale_price[2] = __( "The sale price get changes in proportion to the regular price", 'ssbhesabix' );

		$fields[] = array(
			'title'   => __( "Update sale price", 'ssbhesabix' ),
			'id'      => 'ssbhesabix_item_update_sale_price',
			'type'    => 'select',
			'options' => $options_to_update_sale_price,
		);

		$fields[] = array( 'type' => 'sectionend', 'id' => 'catalog_options' );

		return $fields;
	}
//====================================================================================================
	public static function ssbhesabix_catalog_setting() {
		$ssbhesabf_setting_fields = self::ssbhesabix_catalog_setting_fields();
		$Html_output = new Ssbhesabix_Html_output();
		?>
        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
			<?php $Html_output->init( $ssbhesabf_setting_fields ); ?>
            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_catalog_setting_save_field() {
		$ssbhesabf_setting_fields = self::ssbhesabix_catalog_setting_fields();
		$Html_output = new Ssbhesabix_Html_output();
		$Html_output->save_fields( $ssbhesabf_setting_fields );
	}
//=============================================================================================
	public static function ssbhesabix_customers_setting_fields() {

		$fields[] = array(
			'title' => __( 'Customers Settings', 'ssbhesabix' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'customer_options'
		);

		$fields[] = array(
			'title'   => __( 'Update Customer Address', 'ssbhesabix' ),
			'desc'    => __( 'Choose when update Customer address in Hesabix.', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_contact_address_status',
			'type'    => 'select',
			'options' => array(
				'1' => __( 'Use first customer address', 'ssbhesabix' ),
				'2' => __( 'update address with Invoice address', 'ssbhesabix' ),
				'3' => __( 'update address with Delivery address', 'ssbhesabix' )
			),
		);

		$fields[] = array(
			'title'   => __( 'Customer\'s Group', 'ssbhesabix' ),
			'desc'    => __( 'Enter a Customer\'s Group in Hesabix', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_contact_node_family',
			'type'    => 'text',
			'default' => 'مشتریان فروشگاه آنلاین'
		);

		$fields[] = array(
			'title'   => __( 'Save Customer\'s group', 'ssbhesabix' ),
			'desc'    => __( 'Automatically save Customer\'s group in hesabix', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_contact_automatic_save_node_family',
			'default' => 'yes',
			'type'    => 'checkbox'
		);
		$fields[] = array(
			'title'   => __( 'Customer\'s detail auto save and update', 'ssbhesabix' ),
			'desc'    => __( 'Save and update Customer\'s detail automatically in hesabix', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_contact_automatically_save_in_hesabix',
			'type'    => 'checkbox',
			'default' => 'yes'
		);

		$fields[] = array( 'type' => 'sectionend', 'id' => 'customer_options' );

		return $fields;
	}
//=============================================================================================
	public static function ssbhesabix_customers_setting() {

		$ssbhesabf_setting_fields   = self::ssbhesabix_customers_setting_fields();

		$add_fields                 = get_option( 'ssbhesabix_contact_add_additional_checkout_fields_hesabix', 1 );
		$nationalCodeCheck          = get_option( 'ssbhesabix_contact_NationalCode_checkbox_hesabix' ) == 'yes';
		$economicCodeCheck          = get_option( 'ssbhesabix_contact_EconomicCode_checkbox_hesabix' ) == 'yes';
		$registrationNumberCheck    = get_option( 'ssbhesabix_contact_RegistrationNumber_checkbox_hesabix') == 'yes';
		$websiteCheck               = get_option( 'ssbhesabix_contact_Website_checkbox_hesabix') == 'yes';

		$nationalCodeRequired          = get_option( 'ssbhesabix_contact_NationalCode_isRequired_hesabix' ) == 'yes';
		$economicCodeRequired          = get_option( 'ssbhesabix_contact_EconomicCode_isRequired_hesabix' ) == 'yes';
		$registrationNumberRequired    = get_option( 'ssbhesabix_contact_RegistrationNumber_isRequired_hesabix') == 'yes';
		$websiteRequired               = get_option( 'ssbhesabix_contact_Website_isRequired_hesabix') == 'yes';

		$nationalCodeMetaName       = get_option( 'ssbhesabix_contact_NationalCode_text_hesabix', null ) ;
		$economicCodeMetaName       = get_option( 'ssbhesabix_contact_EconomicCode_text_hesabix', null ) ;
		$registrationNumberMetaName = get_option( 'ssbhesabix_contact_RegistrationNumber_text_hesabix', null );
		$websiteMetaName            = get_option( 'ssbhesabix_contact_Website_text_hesabix', null ) ;

		$Html_output = new Ssbhesabix_Html_output();
		?>
        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
			<?php $Html_output->init( $ssbhesabf_setting_fields ); ?>

            <div class="row my-3">
                <div class="col-1 ml-4">
                    <label class="hesabix-p mt-2"
                           style="font-weight: bold"><?php echo __( 'Add additional fields to checkout page', 'ssbhesabix' ) ?></label>
                </div>
                <div class="col-4 mx-5">
                    <div class="form-check py-2">
                        <input type="radio" name="addFieldsRadio"
                               id="flexRadioDefault1" value="1"  <?php echo $add_fields == '1' ? 'checked' : '' ?>>
                        <label for="flexRadioDefault1" class="hesabix-p">
	                        <?php echo __( 'Customer add field to checkout by hesabix', 'ssbhesabix' ) ?>
                        </label>
                    </div>

                    <div class="form-check">
                        <input type="radio" name="addFieldsRadio"
                               id="flexRadioDefault2" value="2"  <?php echo $add_fields == '2' ?  'checked' : ''?>>
                        <label for="flexRadioDefault2" class="hesabix-p">
	                        <?php echo __( 'Customer add field to checkout by postmeta', 'ssbhesabix' ) ?>
                        </label>
                    </div>
                </div>

            </div>
            <div class="container ">
                <div class="row mx-3">

                    <table class="table table-light mt-4 ">
                        <thead>
                            <tr>
                                <th class="col-1  hesabix-p"><?php echo __( 'Show', 'ssbhesabix' ) ?></th>
                                <th class="col-1  hesabix-p"><?php echo __( 'Required', 'ssbhesabix' ) ?></th>
                                <th class="col-1  hesabix-p"><?php echo __( 'Title', 'ssbhesabix' ) ?></th>
                                <th class="col-4  hesabix-p" ><?php echo __( 'Meta code in Postmeta', 'ssbhesabix' ) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><input type="checkbox" name="nationalCodeCheck" id="nationalCodeCheck"
                                           <?php echo $nationalCodeCheck ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><input type="checkbox" name="nationalCodeRequired" id="nationalCodeRequired"
			                            <?php echo $nationalCodeRequired ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><span class="hesabix-p"><?php echo __( 'National code', 'ssbhesabix' ) ?></span></td>
                                <td><input type="text" name="nationalCode" id="nationalCode"
                                           value="<?php echo $nationalCodeMetaName ?>" class="contact_text_input form-control"></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" name="economicCodeCheck" id="economicCodeCheck"
                                           <?php echo $economicCodeCheck ? 'checked' : '' ?> class="form-control" value="yes"></td>
                                <td><input type="checkbox" name="economicCodeRequired" id="economicCodeRequired"
			                            <?php echo $economicCodeRequired ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><span class="hesabix-p"><?php echo __( 'Economic code', 'ssbhesabix' ) ?></span></td>
                                <td><input type="text" name="economicCode" id="economicCode"
                                           value="<?php echo $economicCodeMetaName ?>" class="contact_text_input form-control"></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" name="registrationNumberCheck" id="registrationNumberCheck"
                                           <?php echo $registrationNumberCheck ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><input type="checkbox" name="registrationNumberRequired" id="registrationNumberRequired"
			                            <?php echo $registrationNumberRequired ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><span class="hesabix-p"><?php echo __( 'Registration number', 'ssbhesabix' ) ?></span></td>
                                <td><input type="text" name="registrationNumber" id="registrationNumber"
                                           value="<?php echo $registrationNumberMetaName ?>" class="contact_text_input form-control"></td>
                            </tr>
                            <tr>
                                <td><input type="checkbox" name="websiteCheck" id="websiteCheck"
                                           <?php echo $websiteCheck ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><input type="checkbox" name="websiteRequired" id="websiteRequired"
			                            <?php echo $websiteRequired ? 'checked' : '' ?> class="form-control"  value="yes"></td>
                                <td><span><?php echo __( 'Website', 'ssbhesabix' ) ?></span></td>
                                <td><input type="text" name="website" id="website" value="<?php echo $websiteMetaName ?>"
                                           class="contact_text_input form-control"></td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            </div>

            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_customers_setting_save_field() {
		$ssbhesabf_setting_fields = self::ssbhesabix_customers_setting_fields();

		if ($_POST) {

            HesabixLogService::writeLogStr( "customer settings save" );

            $add_fields = wc_clean( $_POST['addFieldsRadio'] );;

            $nationalCodeCheck          = wc_clean( $_POST['nationalCodeCheck'] );
            $economicCodeCheck          = wc_clean( $_POST['economicCodeCheck'] );
            $registrationNumberCheck    = wc_clean( $_POST['registrationNumberCheck'] );
            $websiteCheck               = wc_clean( $_POST['websiteCheck'] );

            $nationalCodeRequired          = wc_clean( $_POST['nationalCodeRequired'] );
            $economicCodeRequired          = wc_clean( $_POST['economicCodeRequired'] );
            $registrationNumberRequired    = wc_clean( $_POST['registrationNumberRequired'] );
            $websiteRequired               = wc_clean( $_POST['websiteRequired'] );

            if(isset($_POST['nationalCode']) || isset($_POST['economicCode']) || isset($_POST['registrationNumber']) || isset($_POST['website'])) {
                $nationalCode          = wc_clean( $_POST['nationalCode'] );
                $economicCode          = wc_clean( $_POST['economicCode'] );
                $registrationNumber    = wc_clean( $_POST['registrationNumber'] );
                $website               = wc_clean( $_POST['website'] );
            }

            update_option( 'ssbhesabix_contact_add_additional_checkout_fields_hesabix', $add_fields );

            update_option( 'ssbhesabix_contact_NationalCode_checkbox_hesabix', $nationalCodeCheck );
            update_option( 'ssbhesabix_contact_EconomicCode_checkbox_hesabix', $economicCodeCheck );
            update_option( 'ssbhesabix_contact_RegistrationNumber_checkbox_hesabix', $registrationNumberCheck );
            update_option( 'ssbhesabix_contact_Website_checkbox_hesabix', $websiteCheck );

            update_option( 'ssbhesabix_contact_NationalCode_isRequired_hesabix', $nationalCodeRequired );
            update_option( 'ssbhesabix_contact_EconomicCode_isRequired_hesabix', $economicCodeRequired );
            update_option( 'ssbhesabix_contact_RegistrationNumber_isRequired_hesabix', $registrationNumberRequired );
            update_option( 'ssbhesabix_contact_Website_isRequired_hesabix', $websiteRequired );

            if(isset($nationalCode) || isset($economicCode) || isset($registrationNumber) || isset($website)) {
                update_option('ssbhesabix_contact_NationalCode_text_hesabix', $nationalCode);
                update_option('ssbhesabix_contact_EconomicCode_text_hesabix', $economicCode);
                update_option('ssbhesabix_contact_RegistrationNumber_text_hesabix', $registrationNumber);
                update_option('ssbhesabix_contact_Website_text_hesabix', $website);
            }
        }

		$Html_output = new Ssbhesabix_Html_output();
		$Html_output->save_fields( $ssbhesabf_setting_fields );
		// ....
	}

//=============================================================================================
	public static function ssbhesabix_invoice_setting_fields() {
		$projects = Ssbhesabix_Setting::ssbhesabix_get_projects();
		$salesmen = Ssbhesabix_Setting::ssbhesabix_get_salesmen();

		$fields[] = array(
			'title' => __( 'Invoice Settings', 'ssbhesabix' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'invoice_options'
		);

		$fields[] = array(
			'title'   => __( 'Add invoice in which status', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_invoice_status',
			'type'    => 'multiselect',
			'options' => array(
				'pending'        => __( 'Pending payment', 'ssbhesabix' ),
				'processing'     => __( 'Processing', 'ssbhesabix' ),
				'on-hold'        => __( 'On hold', 'ssbhesabix' ),
				'completed'      => __( 'Completed', 'ssbhesabix' ),
				'cancelled'      => __( 'Cancelled', 'ssbhesabix' ),
				'refunded'       => __( 'Refunded', 'ssbhesabix' ),
				'failed'         => __( 'Failed', 'ssbhesabix' ),
				'checkout-draft' => __( 'Draft', 'ssbhesabix' ),
			),
		);

		$fields[] = array(
			'title'   => __( 'Return sale invoice status', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_invoice_return_status',
			'type'    => 'multiselect',
			'options' => array(
				'pending'        => __( 'Pending payment', 'ssbhesabix' ),
				'processing'     => __( 'Processing', 'ssbhesabix' ),
				'on-hold'        => __( 'On hold', 'ssbhesabix' ),
				'completed'      => __( 'Completed', 'ssbhesabix' ),
				'cancelled'      => __( 'Cancelled', 'ssbhesabix' ),
				'refunded'       => __( 'Refunded', 'ssbhesabix' ),
				'failed'         => __( 'Failed', 'ssbhesabix' ),
				'checkout-draft' => __( 'Draft', 'ssbhesabix' ),
			),
		);

		$fields[] = array(
			'title'   => __( "Invoice's Project", 'ssbhesabix' ),
			'id'      => 'ssbhesabix_invoice_project',
			'type'    => 'select',
			'options' => $projects,
		);

		$fields[] = array(
			'title'   => __( "Invoice's Salesman", 'ssbhesabix' ),
			'id'      => 'ssbhesabix_invoice_salesman',
			'type'    => 'select',
			'options' => $salesmen,
		);

        $fields[] = array(
            'title'   => __( "Invoice Salesman Percentage", 'ssbhesabix' ),
            'id'      => 'ssbhesabix_invoice_salesman_percentage',
            'type'    => 'text',
            'placeholder' => __("Invoice Salesman Percentage", 'ssbhesabix'),
        );

        $fields[] = array(
            'title' => '',
            'desc' => __('Save invoice in draft mode in Hesabix', 'ssbhesabix'),
            'id' => 'ssbhesabix_invoice_draft_save_in_hesabix',
            'type' => 'checkbox',
            'default' => 'no'
        );

        $fields[] = array(
            'title' => __('Save Freight', 'ssbhesabix'),
            'id' => 'ssbhesabix_invoice_freight',
            'type' => 'radio',
            'options' => [
                0 => __("Save as Freight", 'ssbhesabix'),
                1 => __("Save as a Service", 'ssbhesabix')
            ],
        );

        $fields[] = array(
            'title' => __('Service Code For Freight', 'ssbhesabix'),
            'id' => 'ssbhesabix_invoice_freight_code',
            'type' => 'text',
            'placeholder' => __('Enter Freight Code', 'ssbhesabix'),
        );

        if(is_plugin_active( 'dokan-lite/dokan.php' )){
            $fields[] = array(
                'title'   => __( "Submit invoice base on Dokan orders", 'ssbhesabix' ),
                'id'      => 'ssbhesabix_invoice_dokan',
                'type'    => 'radio',
                'options' => [0 => __( "Inactive", 'ssbhesabix' ),
                    1 => __( "Submit parent order", 'ssbhesabix' ),
                    2 =>  __( "Submit children orders", 'ssbhesabix' )],
                'default' => 0
            );
        }

		$fields[] = array('type' => 'sectionend', 'id' => 'invoice_options');

		return $fields;
	}
//=============================================================================================
	public static function ssbhesabix_invoice_setting() {
		$ssbhesabf_setting_fields = self::ssbhesabix_invoice_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		?>
        <style>
            #ssbhesabix_invoice_freight_code, #ssbhesabix_invoice_salesman_percentage {
                min-width: 250px;
            }
            #ssbhesabix_invoice_transaction_fee {
                width: fit-content;
            }
        </style>
        <div class="alert alert-warning hesabix-f">
            <strong>توجه</strong><br>
            در اینجا تعیین کنید که فاکتور سفارش در چه مرحله ای در حسابیکس ثبت شود.
            و چه زمان برای یک سفارش فاکتور برگشت از فروش ثبت شود.
            <br>
            در صورت انتخاب ذخیره هزینه حمل و نقل به عنوان یک خدمت، ابتدا باید یک خدمت در حسابیکس تعریف کنید و کد مربوط به آن را در فیلد کد خدمت حمل و نقل  وارد و ذخیره نمایید.
            <br>
            فیلد "ذخیره هزینه به عنوان خدمت" برای سامانه مودیان مالیاتی می باشد.
            <br>
            توجه کنید که مقدار این فیلد به درستی وارد شده باشد تا در ثبت فاکتور مشکلی ایجاد نشود.
        </div>
        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
			<?php $Html_output->init( $ssbhesabf_setting_fields ); ?>
            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
            <?php
                if(get_option('ssbhesabix_invoice_freight') == 1 && !(get_option('ssbhesabix_invoice_freight_code'))) {
                    HesabixLogService::writeLogStr("Invoice Freight Service Code is not Defined in Hesabix ---- کد خدمت حمل و نقل تعریف نشده است");
                    echo '<script>alert("کد خدمت حمل و نقل تعریف نشده است")</script>';
                }
            ?>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_invoice_setting_save_field() {
		$ssbhesabf_setting_fields = self::ssbhesabix_invoice_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		$Html_output->save_fields( $ssbhesabf_setting_fields );
	}
//=============================================================================================
	public static function ssbhesabix_payment_setting_fields() {
		$banks = Ssbhesabix_Setting::ssbhesabix_get_banks();
		$cashes = Ssbhesabix_Setting::ssbhesabix_get_cashes();
        $payInputValue = array_merge($banks,$cashes);

		$payment_gateways           = new WC_Payment_Gateways;
		$available_payment_gateways = $payment_gateways->get_available_payment_gateways();

		$fields[] = array(
			'title' => __( 'Payment methods Settings', 'ssbhesabix' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'payment_options'
		);

		$fields[] = array(
			'title'   => __( 'Add payment in which status', 'ssbhesabix' ),
			'id'      => 'ssbhesabix_payment_status',
			'type'    => 'multiselect',
			'options' => array(
				'pending'        => __( 'Pending payment', 'ssbhesabix' ),
				'processing'     => __( 'Processing', 'ssbhesabix' ),
				'on-hold'        => __( 'On hold', 'ssbhesabix' ),
				'completed'      => __( 'Completed', 'ssbhesabix' ),
				'cancelled'      => __( 'Cancelled', 'ssbhesabix' ),
				'refunded'       => __( 'Refunded', 'ssbhesabix' ),
				'failed'         => __( 'Failed', 'ssbhesabix' ),
				'checkout-draft' => __( 'Draft', 'ssbhesabix' ),
			),
		);


        foreach ( $available_payment_gateways as $gateway ) {
            $fields[] = array(
                'title'   => $gateway->title,
                'id'      => 'ssbhesabix_payment_method_' . $gateway->id,
                'type'    => 'select',
                'options' => $payInputValue
            );
        }

        $fields[] = array(
            'title' => __('Default Payment Gateway By Using this Option, all Invoices Will Have this Payment Gateway as Their Payment Gateway', 'ssbhesabix'),
            'id' => 'ssbhesabix_payment_option',
            'type' => 'radio',
            'options' => [
                'yes' => __("Save Default Bank as the Payment Gateway", "ssbhesabix"),
                'no' => __("Save Other Payment Methods as the Payment Gateway", "ssbhesabix"),
            ],
            'default' => 'no'
        );

        $fields[] = array(
            'title'   => __( "Invoice Transaction Fee Percentage", 'ssbhesabix' ),
            'id'      => 'ssbhesabix_invoice_transaction_fee',
            'type'    => 'text',
            'placeholder' => __("Invoice Transaction Fee Percentage", 'ssbhesabix'),
            'default' => '0'
        );

        $fields[] = array(
            'title'   => __( "Submit Cash in Transit", 'ssbhesabix' ),
            'id'      => 'ssbhesabix_cash_in_transit',
            'desc' => __( "Submit Invoice Receipt Cash in Transit", 'ssbhesabix' ),
            'type'    => 'checkbox',
            'default' => 'no'
        );

        $fields[] = array(
          'title' => __('Default Bank Code', 'ssbhesabix'),
          'id' => 'ssbhesabix_default_payment_method_code',
          'type' => 'text',
          'placeholder' => __('Enter Bank Code', 'ssbhesabix')
        );

        $fields[] = array(
          'title' => __('Default Bank Name', 'ssbhesabix'),
          'id' => 'ssbhesabix_default_payment_method_name',
          'type' => 'text',
          'placeholder' => __('Enter Bank Name', 'ssbhesabix')
        );

		$fields[] = array( 'type' => 'sectionend', 'id' => 'payment_options' );

		return $fields;
	}
//=============================================================================================
	public static function ssbhesabix_payment_setting() {
		$ssbhesabf_setting_fields = self::ssbhesabix_payment_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		?>
        <div class="alert alert-warning hesabix-f">
            <strong>توجه</strong><br>
            در اینجا تعیین کنید که رسید دریافت وجه فاکتور در چه وضعیتی ثبت شود
            و در هر روش پرداخت، رسید در چه بانکی و یا صندوقی ثبت شود.
            <br>
            بانک پیش فرض، جهت کاربرانی می باشد که به هر دلیلی روش های پرداخت وکامرس در اینجا نمایش داده نمی شود. در این صورت با انتخاب بانک و ثبت کد آن، تمامی دریافت ها در آن بانک ثبت خواهد شد
        </div>
        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
			<?php $Html_output->init( $ssbhesabf_setting_fields ); ?>
            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
            <?php
            if(get_option('ssbhesabix_payment_option') == 'yes') {
                if(!(get_option('ssbhesabix_default_payment_method_code'))) echo '<script>alert("کد بانک پیش فرض تعریف نشده است")</script>';
            }

            if(get_option("ssbhesabix_cash_in_transit") == "yes" || get_option("ssbhesabix_cash_in_transit") == "1") {
                $func = new Ssbhesabix_Admin_Functions();
                $cashInTransitFullPath = $func->getCashInTransitFullPath();
                if(!$cashInTransitFullPath) {
                    HesabixLogService::writeLogStr("Cash in Transit is not Defined in Hesabix ---- وجوه در راه در حسابیکس یافت نشد");
                    echo '
                        <script>
                            alert("وجوه در راه در حسابیکس یافت نشد");
                        </script>';
                }
            }
            ?>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_payment_setting_save_field() {
		$ssbhesabf_setting_fields = self::ssbhesabix_payment_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		$Html_output->save_fields( $ssbhesabf_setting_fields );
	}
//=============================================================================================
    public static function ssbhesabix_api_setting_fields() {
		$fields[] = array(
			'title' => __( 'API Settings', 'ssbhesabix' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'api_options'
		);

		$fields[] = array(
			'title' => __( 'API Key', 'ssbhesabix' ),
			'desc'  => __( 'Find API key in Setting->Financial Settings->API Menu', 'ssbhesabix' ),
			'id'    => 'ssbhesabix_account_api',
			'type'  => 'text',
		);

        $fields[] = array(
            'title' => __( 'API Address', 'ssbhesabix' ),
            'id'    => 'ssbhesabix_api_address',
            'type'  => 'select',
            'options' => array(
                "0" => "hesabix.ir",
                "1" => "next.hesabix.ir"
            )
        );

		$fields[] = array( 'type' => 'sectionend', 'id' => 'api_options' );

		return $fields;
	}
//=============================================================================================
	public static function ssbhesabix_api_setting() {
		$businessInfo   = self::getSubscriptionInfo();
		$isBusinessInfo = false;
		if ( $businessInfo["expireDate"] != '' && $businessInfo["expireDate"] != null ) {
			$isBusinessInfo = true;
			$expireDate     = strtotime( $businessInfo["expireDate"] );
			$expireDateStr  = date( "Y/m/d", $expireDate );
		}

		$ssbhesabf_setting_fields = self::ssbhesabix_api_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		?>
        <div class="alert alert-warning hesabix-f">
            <strong>توجه</strong><br>
            <ul class="mx-4" style="list-style-type:square">
                <li>
                    برای اتصال به API حسابیکس و فعال شدن این افزونه باید در اینجا
                    کلید API و توکن ورود به کسب و کار خود را وارد کنید.
                </li>
                <li>
                    برای پیدا کردن توکن ورود و کلید API، در حسابیکس به قسمت تنظیمات، تنظیمات API مراجعه کنید.
                </li>
                <li>
                    اگر می خواهید کسب و کار دیگری را به افزونه متصل کنید، ابتدا باید یک بار افزونه را
                    حذف و مجدد نصب کنید تا جدول ارتباطات کسب و کار قبلی با افزونه حذف گردد.
                </li>
            </ul>
        </div>
        <div class="card hesabix-card hesabix-f <?php echo $isBusinessInfo ? '' : 'd-none' ?>">
            <strong>اطلاعات کسب و کار</strong>
            <div class="row mt-2">
                <div class="col">نام کسب و کار:</div>
                <div class="col text-info fw-bold"><?php echo $businessInfo["businessName"] ?></div>
                <div class="col">طرح:</div>
                <div class="col text-info fw-bold"><?php echo $businessInfo["plan"] ?></div>
            </div>
            <div class="row mt-2">
                <div class="col">اعتبار سند:</div>
                <div class="col text-info fw-bold"><?php echo $businessInfo["credit"] ?></div>
                <div class="col">تاریخ انقضا:</div>
                <div class="col text-info fw-bold"><?php echo $expireDateStr ?></div>
            </div>
        </div>

        <div class="alert alert-danger hesabix-f mt-2" id="changeBusinessWarning">
            <strong>هشدار</strong><br>
            برای اتصال یک کسب و کار دیگر به افزونه، ابتدا باید یک بار افزونه را حذف و مجدد
            نصب کنید تا جدول ارتباطات افزونه با کسب و کار قبل حذف گردد.
        </div>

        <form id="ssbhesabix_form" enctype="multipart/form-data" action="" method="post">
			<?php $Html_output->init( $ssbhesabf_setting_fields ); ?>
            <p class="submit hesabix-p">
                <input type="submit" name="ssbhesabix_integration" class="button-primary"
                       value="<?php esc_attr_e( 'Save changes', 'ssbhesabix' ); ?>"/>
            </p>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_api_setting_save_field() {
		$ssbhesabf_setting_fields = self::ssbhesabix_api_setting_fields();
		$Html_output              = new Ssbhesabix_Html_output();
		$Html_output->save_fields( $ssbhesabf_setting_fields );

		Ssbhesabix_Setting::ssbhesabix_set_webhook();
	}
//=============================================================================================
	public static function ssbhesabix_export_setting() {
		// Export - Bulk product export offers
		$productExportResult = ( isset( $_GET['productExportResult'] ) ) ? wc_clean( $_GET['productExportResult'] ) : null;
		$productImportResult = ( isset( $_GET['productImportResult'] ) ) ? wc_clean( $_GET['productImportResult'] ) : null;
		$error               = ( isset( $_GET['error'] ) ) ? wc_clean( $_GET['error'] ) : null;

		if ( ! is_null( $productExportResult ) && $productExportResult === 'true' ) {
			$processed = ( isset( $_GET['processed'] ) ) ? wc_clean( $_GET['processed'] ) : null;
			if ( $processed == 0 ) {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'No products were exported, All products were exported or there are no product', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . sprintf( __( 'Export products completed. %s products added/updated.', 'ssbhesabix' ), $processed );
				echo '</div>';
			}
		} elseif ( $productExportResult === 'false' ) {
			if ( ! is_null( $error ) && $error === '-1' ) {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'Export products fail. Hesabix has already contained products.', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'Export products fail. Please check the log file.', 'ssbhesabix' );
				echo '</div>';
			}
		}

		if ( ! is_null( $productImportResult ) && $productImportResult === 'true' ) {
			$processed = ( isset( $_GET['processed'] ) ) ? wc_clean( $_GET['processed'] ) : null;
			if ( $processed == 0 ) {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'No products were imported, All products were imported or there are no product', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . sprintf( __( 'Import products completed. %s products added/updated.', 'ssbhesabix' ), $processed );
				echo '</div>';
			}
		} elseif ( $productImportResult === 'false' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Import products fail. Please check the log file.', 'ssbhesabix' );
			echo '</div>';
		}

		// Export - Product opening quantity export offers
		$productOpeningQuantityExportResult = ( isset( $_GET['productOpeningQuantityExportResult'] ) ) ? wc_clean( $_GET['productOpeningQuantityExportResult'] ) : null;
		if ( ! is_null( $productOpeningQuantityExportResult ) && $productOpeningQuantityExportResult === 'true' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Export product opening quantity completed.', 'ssbhesabix' );
			echo '</div>';
		} elseif ( ! is_null( $productOpeningQuantityExportResult ) && $productOpeningQuantityExportResult === 'false' ) {
			$shareholderError = ( isset( $_GET['shareholderError'] ) ) ? wc_clean( $_GET['shareholderError'] ) : null;
			$noProduct        = ( isset( $_GET['noProduct'] ) ) ? wc_clean( $_GET['noProduct'] ) : null;
			if ( $shareholderError == 'true' ) {
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'Export product opening quantity fail. No Shareholder exists, Please define Shareholder in Hesabix', 'ssbhesabix' );
				echo '</div>';
			} elseif ( $noProduct == 'true' ) {
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'No product available for Export product opening quantity.', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'Export product opening quantity fail. Please check the log file.', 'ssbhesabix' );
				echo '</div>';
			}
		}

		// Export - Bulk customer export offers
		$customerExportResult = ( isset( $_GET['customerExportResult'] ) ) ? wc_clean( $_GET['customerExportResult'] ) : null;

		if ( ! is_null( $customerExportResult ) && $customerExportResult === 'true' ) {
			$processed = ( isset( $_GET['processed'] ) ) ? wc_clean( $_GET['processed'] ) : null;
			if ( $processed == 0 ) {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'No customers were exported, All customers were exported or there are no customer', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . sprintf( __( 'Export customers completed. %s customers added.', 'ssbhesabix' ), $processed );
				echo '</div>';
			}
		} elseif ( ! is_null( $customerExportResult ) && $customerExportResult === 'false' ) {
			if ( ! is_null( $error ) && $error === '-1' ) {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'Export customers fail. Hesabix has already contained customers.', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="updated">';
				echo '<p class="hesabix-p">' . __( 'Export customers fail. Please check the log file.', 'ssbhesabix' );
				echo '</div>';
			}
		}

		?>
        <div class="notice notice-info">
            <p class="hesabix-p"><?php echo __( 'Export can take several minutes.', 'ssbhesabix' ) ?></p>
        </div>
        <br>
        <form class="card hesabix-card" id="ssbhesabix_export_products" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=export' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-export-product-submit"></label>
                    <div>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-export-product-submit"
                                name="ssbhesabix-export-product-submit"><?php echo __( 'Export Products', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Export and add all online store products to Hesabix', 'ssbhesabix' ); ?></p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="exportProductsProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="exportProductsProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات محصولات لینک نشده از فروشگاه وارد حسابیکس می شوند.</li>
                        <li>با انجام این عملیات موجودی محصولات وارد حسابیکس نمی شود و برای وارد کردن موجودی محصولات
                            فروشگاه
                            در حسابیکس، باید از گزینه استخراج موجودی اول دوره استفاده کنید.
                        </li>
                    </ul>
                </div>
            </div>
        </form>

        <form class="card hesabix-card hesabix-f" id="ssbhesabix_export_customers" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=export' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-export-customer-submit"></label>
                    <div>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-export-customer-submit"
                                name="ssbhesabix-export-customer-submit"><?php echo __( 'Export Customers', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Export and add all online store customers to Hesabix.', 'ssbhesabix' ); ?></p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="exportCustomersProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="exportCustomersProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات مشتریان لینک نشده از فروشگاه وارد حسابیکس می شوند.</li>
                        <li>
                            اگر یک مشتری بیش از یک بار وارد حسابیکس شده است می توانید از گزینه ادغام تراکنش ها در حسابیکس
                            استفاده کنید.
                        </li>
                    </ul>
                </div>
            </div>
        </form>

        <form class="card hesabix-card hesabix-f" id="ssbhesabix_import_products" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=export' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-import-product-submit"></label>
                    <div>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-import-product-submit"
                                name="ssbhesabix-import-product-submit"><?php echo __( 'Import Products', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2">
					<?php echo __( 'Import and add all products from Hesabix to online store', 'ssbhesabix' ); ?>
                </p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="importProductsProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="importProductsProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات محصولات لینک نشده از حسابیکس وارد فروشگاه می شوند.</li>
                        <li>اگر محصولات از قبل هم در فروشگاه تعریف شده اند و هم در حسابیکس و به هم لینک نشده اند باید از
                            گزینه
                            همسان سازی دستی محصولات استفاده کنید.
                        </li>
                        <li>محصولات در وضعیت خصوصی وارد فروشگاه می شوند و سپس هر زمان مایل بودید می توانید وضعیت را به
                            منتشر شده تغییر دهید.
                        </li>
                        <li>تمامی محصولات بعنوان محصول ساده (و نه متغیر) وارد فروشگاه می شوند.</li>
                    </ul>
                </div>
            </div>
        </form>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_sync_setting() {
		$result               = self::getProductsCount();
		$storeProductsCount   = $result["storeProductsCount"];
		$hesabixProductsCount = $result["hesabixProductsCount"];
		$linkedProductsCount  = $result["linkedProductsCount"];

		// Sync - Bulk changes sync offers
		$changesSyncResult = ( isset( $_GET['changesSyncResult'] ) ) ? wc_clean( $_GET['changesSyncResult'] ) : false;
		if ( ! is_null( $changesSyncResult ) && $changesSyncResult == 'true' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Sync completed, All hesabix changes synced successfully.', 'ssbhesabix' );
			echo '</div>';
		}

		// Sync - Bulk product sync offers
		$productSyncResult = ( isset( $_GET['productSyncResult'] ) ) ? wc_clean( $_GET['productSyncResult'] ) : null;
		if ( ! is_null( $productSyncResult ) && $productSyncResult == 'true' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Sync completed, All products price/quantity synced successfully.', 'ssbhesabix' );
			echo '</div>';
		} elseif ( ! is_null( $productSyncResult ) && ! $productSyncResult == 'false' ) {
			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Sync products fail. Please check the log file.', 'ssbhesabix' );
			echo '</div>';
		}

		// Sync - Bulk invoice sync offers
		$orderSyncResult = ( isset( $_GET['orderSyncResult'] ) ) ? wc_clean( $_GET['orderSyncResult'] ) : null;

		if ( ! is_null( $orderSyncResult ) && $orderSyncResult === 'true' ) {
			$processed = ( isset( $_GET['processed'] ) ) ? wc_clean( $_GET['processed'] ) : null;
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . sprintf( __( 'Order sync completed. %s order added.', 'ssbhesabix' ), $processed );
			echo '</div>';
		} elseif ( ! is_null( $orderSyncResult ) && $orderSyncResult === 'false' ) {
			$fiscal = ( isset( $_GET['fiscal'] ) ) ? wc_clean( $_GET['fiscal'] ) : false;

			if ( $fiscal === 'true' ) {
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'The date entered is not within the fiscal year.', 'ssbhesabix' );
				echo '</div>';
			} else {
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'Cannot sync orders. Please enter valid Date format.', 'ssbhesabix' );
				echo '</div>';
			}
		}

		// Sync - Bulk product update
		$productUpdateResult = ( isset( $_GET['$productUpdateResult'] ) ) ? wc_clean( $_GET['$productUpdateResult'] ) : null;
		if ( ! is_null( $productUpdateResult ) && $productUpdateResult == 'true' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Update completed successfully.', 'ssbhesabix' );
			echo '</div>';
		} elseif ( ! is_null( $productUpdateResult ) && ! $productUpdateResult == 'false' ) {
			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Update failed. Please check the log file.', 'ssbhesabix' );
			echo '</div>';
		}

        // Sync - Bulk product with filter update in Hesabix
        $productUpdateWithFilterResult = ( isset( $_GET['$productUpdateWithFilterResult'] ) ) ? wc_clean( $_GET['$productUpdateWithFilterResult'] ) : null;
        if ( ! is_null( $productUpdateWithFilterResult ) && $productUpdateWithFilterResult == 'true' ) {
            echo '<div class="updated">';
            echo '<p class="hesabix-p">' . __( 'Update completed successfully.', 'ssbhesabix' );
            echo '</div>';
        } elseif ( ! is_null( $productUpdateWithFilterResult ) && ! $productUpdateWithFilterResult == 'false' ) {
            echo '<div class="error">';
            echo '<p class="hesabix-p">' . __( 'Update failed. Please check the log file.', 'ssbhesabix' );
            echo '</div>';
        }
		?>

        <div class="notice notice-info mt-3">
            <p class="hesabix-p"><?php echo __( 'Number of products in store:', 'ssbhesabix' ) . ' <b>' . $storeProductsCount . '</b>' ?></p>
            <p class="hesabix-p"><?php echo __( 'Number of products in hesabix:', 'ssbhesabix' ) . ' <b>' . $hesabixProductsCount . '</b>' ?></p>
            <p class="hesabix-p"><?php echo __( 'Number of linked products:', 'ssbhesabix' ) . ' <b>' . $linkedProductsCount . '</b>' ?></p>
        </div>

        <div class="notice notice-info">
            <p class="hesabix-p"><?php echo __( 'Sync can take several minutes.', 'ssbhesabix' ) ?></p>
        </div>

        <br>
        <form class="card hesabix-card hesabix-f d-none" id="ssbhesabix_sync_changes" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=sync' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-sync-changes-submit"></label>
                    <div>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-sync-changes-submit"
                                name="ssbhesabix-sync-changes-submit"><?php echo esc_attr_e( 'Sync Changes', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Sync all Hesabix changes with Online Store.', 'ssbhesabix' ); ?></p>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات کالاها، مشتریان و سفارشاتی که تا کنون در حسابیکس ثبت نشده اند در حسابیکس
                            ثبت می شوند.
                        </li>
                        <li>توجه کنید که بصورت نرمال با فعالسازی افزونه و تکمیل تنظیمات API
                            این همسان سازی بصورت خودکار انجام می شود و این گزینه صرفاْ برای مواقعی است که به دلایل فنی
                            مثل قطع اتصال فروشگاه با حسابیکس و یا خطا و باگ این همسان سازی صورت نگرفته است.
                        </li>
                    </ul>
                </div>
            </div>
        </form>

        <form class="card hesabix-card hesabix-f" id="ssbhesabix_sync_products" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=sync' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-sync-products-submit"></label>
                    <div>
                        <?php
                            if(get_option('ssbhesabix_item_update_price') == 'no' && get_option('ssbhesabix_item_update_quantity') == 'no') { ?>
                                <button disabled class="button button-primary hesabix-f" id="ssbhesabix-sync-products-submit"
                                        name="ssbhesabix-sync-products-submit"><?php echo __( 'Sync Products Quantity and Price', 'ssbhesabix' ); ?></button>
                           <?php } else {
                        ?>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-sync-products-submit"
                                name="ssbhesabix-sync-products-submit"><?php echo __( 'Sync Products Quantity and Price', 'ssbhesabix' ); ?></button>
                        <?php } ?>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Sync quantity and price of products in hesabix with online store.', 'ssbhesabix' ); ?></p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="syncProductsProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="syncProductsProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات موجودی و قیمت محصولات در فروشگاه، بر اساس قیمت و موجودی آنها در حسابیکس
                            تنظیم می شود.
                        </li>
                        <li>این عملیات بر اساس تنظیمات صورت گرفته در تب محصولات انجام می شود.</li>
                    </ul>
                </div>
            </div>
        </form>

        <form class="card hesabix-card hesabix-f" id="ssbhesabix_sync_orders" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=sync' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-sync-orders-submit"></label>
                    <div>
                        <input type="date" id="ssbhesabix_sync_order_date" name="ssbhesabix_sync_order_date" value=""
                               class="datepicker"/>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-sync-orders-submit"
                                name="ssbhesabix-sync-orders-submit"><?php echo __( 'Sync Orders', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Sync/Add orders in online store with hesabix from above date.', 'ssbhesabix' ); ?></p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="syncOrdersProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="syncOrdersProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات سفارشات فروشگاه که در حسابیکس ثبت نشده اند از تاریخ انتخاب شده بررسی و در
                            حسابیکس ثبت می شوند.
                        </li>
                        <li>توجه کنید که بصورت نرمال با فعالسازی افزونه و تکمیل تنظیمات API
                            این همسان سازی بصورت خودکار انجام می شود و این گزینه صرفاْ برای مواقعی است که به دلایل فنی
                            مثل قطع اتصال فروشگاه با حسابیکس و یا خطا و باگ این همسان سازی صورت نگرفته است.
                        </li>
                    </ul>
                </div>
            </div>
        </form>

        <form class="card hesabix-card hesabix-f" id="ssbhesabix_update_products" autocomplete="off"
              action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=sync' ); ?>"
              method="post">
            <div>
                <div>
                    <label for="ssbhesabix-update-products-submit"></label>
                    <div>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-update-products-submit"
                                name="ssbhesabix-update-products-submit"><?php echo __( 'Update Products in Hesabix based on store', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Update products in hesabix based on products definition in store.', 'ssbhesabix' ); ?></p>
                <div class="progress mt-1 mb-2" style="height: 5px; max-width: 400px; border: 1px solid silver"
                     id="updateProductsProgress">
                    <div class="progress-bar progress-bar-striped bg-success" id="updateProductsProgressBar"
                         role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات ویژگی محصولات مثل نام و قیمت در حسابیکس، بر اساس فروشگاه بروزرسانی می
                            شود.
                        </li>
                        <li>در این عملیات موجودی کالا در حسابیکس تغییری نمی کند و بروز رسانی نمی شود.</li>
                    </ul>
                </div>
            </div>
        </form>

        <form
            class="card hesabix-card hesabix-f" name="ssbhesabix_update_products_with_filter" id="ssbhesabix_update_products_with_filter" autocomplete="off" method="post"
            action="<?php echo admin_url( 'admin.php?page=ssbhesabix-option&tab=sync' ); ?>"
        >
            <div>
                <div>
                    <label for="ssbhesabix-update-products-with-filter-submit"></label>
                    <div>
                        <input style="min-width: 250px;" type="text" id="ssbhesabix-update-products-offset" name="ssbhesabix-update-products-offset" placeholder="<?php echo __('Start ID', 'ssbhesabix'); ?>" />
                        <br><br>
                        <input style="min-width: 250px;" type="text" id="ssbhesabix-update-products-rpp" name="ssbhesabix-update-products-rpp" placeholder="<?php echo __('End ID', 'ssbhesabix'); ?>"  />
                        <br><br>
                        <button class="button button-primary hesabix-f" id="ssbhesabix-update-products-with-filter-submit"
                                name="ssbhesabix-update-products-with-filter-submit"><?php echo __( 'Update Products in Hesabix based on store with filter', 'ssbhesabix' ); ?></button>
                    </div>
                </div>
                <p class="hesabix-p mt-2"><?php echo __( 'Update products in hesabix based on products definition in store.', 'ssbhesabix' ); ?></p>
                <div class="p-2 hesabix-f">
                    <label class="fw-bold mb-2">نکات مهم:</label>
                    <ul>
                        <li>با انجام این عملیات ویژگی محصولات مثل نام و قیمت در حسابیکس، بر اساس فروشگاه در بازه ID مشخص شده بروزرسانی می
                            شود.
                        </li>
                        <li>در این عملیات موجودی کالا در حسابیکس تغییری نمی کند و بروز رسانی نمی شود.</li>
                        <li>بازه ID نباید بیشتر از 200 عدد باشد.</li>
                    </ul>
                </div>
            </div>
        </form>

		<?php
	}
//=============================================================================================
	public static function getProductsCount() {
		$storeProductsCount   = self::getProductCountsInStore();
		$hesabixProductsCount = self::getProductCountsInHesabix();
		$linkedProductsCount  = self::getLinkedProductsCount();

		return array(
			"storeProductsCount"   => $storeProductsCount,
			"hesabixProductsCount" => $hesabixProductsCount,
			"linkedProductsCount"  => $linkedProductsCount
		);
	}
//=============================================================================================
	public static function getProductCountsInHesabix() {
		$hesabix = new Ssbhesabix_Api();

		$filters = array( array( "Property" => "ItemType", "Operator" => "=", "Value" => 0 ) );

		$response = $hesabix->itemGetItems( array( 'Take' => 1, 'Filters' => $filters ) );
		if ( $response->Success ) {
			return $response->Result->FilteredCount;
		} else {
			return 0;
		}
	}
//=============================================================================================
	public static function getLinkedProductsCount() {
		global $wpdb;

		return $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `obj_type` = 'product'" );
	}
//=============================================================================================
	public static function getProductCountsInStore() {
		global $wpdb;

		return $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` WHERE `post_type` IN ('product','product_variation') AND `post_status` IN ('publish', 'private', 'draft')  " );
	}
//=============================================================================================
	public static function getSubscriptionInfo() {
		$businessName = '';
		$hesabix  = new Ssbhesabix_Api();
		$response = $hesabix->settingGetSubscriptionInfo();
		if ( $response->Success ) {
			$businessName = $response->Result->name;
		}
		return array(
			"businessName" => $businessName,
		);
	}
//=============================================================================================
	public static function ssbhesabix_set_webhook() {
		$url = get_site_url() . '/index.php?ssbhesabix_webhook=1&token=' . substr( wp_hash( AUTH_KEY . 'ssbhesabix/webhook' ), 0, 10 );

		$hookPassword = get_option( 'ssbhesabix_webhook_password' );

		$ssbhesabix_api = new Ssbhesabix_Api();
		$response       = $ssbhesabix_api->settingSetChangeHook( $url, $hookPassword );
    
		if ( is_object( $response ) ) {
			if ( $response->Success ) {
				update_option( 'ssbhesabix_live_mode', 1 );
				update_option( 'ssbhesabix_account_bid', $response->bid );
                update_option( 'ssbhesabix_account_year', $response->year );
				//set the last log ID if is not set
				$lastChanges = get_option( 'ssbhesabix_last_log_check_id' );
				if ( ! $lastChanges ) {
					$lastChanges = 0;
				}
				$changes = $ssbhesabix_api->settingGetChanges( $lastChanges );
				if ( $changes->Success ) {
					if ( get_option( 'ssbhesabix_last_log_check_id' ) == 0 ) {
						$lastChange = end( $changes->Result );
						update_option( 'ssbhesabix_last_log_check_id', $lastChange->Id );
					}
				} else {
					echo '<div class="error">';
					echo '<p class="hesabix-p">' . __( 'Cannot check the last change ID. Error Message: ', 'ssbhesabix' ) . $changes->ErrorMessage . '</p>';
					echo '</div>';

					HesabixLogService::log( array("Cannot check the last change ID. Error Message: $changes->ErrorMessage. Error Code: $changes->ErrorCode") );
				}


				//check if date in fiscalYear
				if ( Ssbhesabix_Admin_Functions::isDateInFiscalYear( date( 'Y-m-d H:i:s' ) ) === 0 ) {
					echo '<div class="error">';
					echo '<p class="hesabix-p">' . __( 'The fiscal year has passed or not arrived. Please check the fiscal year settings in Hesabix.', 'ssbhesabix' ) . '</p>';
					echo '</div>';

					update_option( 'ssbhesabix_live_mode', 0 );
				}

				//check the Hesabix default currency
				$default_currency = $ssbhesabix_api->settingGetCurrency();
				if ( $default_currency->Success ) {
					$woocommerce_currency = get_woocommerce_currency();
					$hesabix_currency     = $default_currency->Result->moneyName;
					if ( $hesabix_currency == $woocommerce_currency || ( $hesabix_currency == 'IRR' && $woocommerce_currency == 'IRT' ) || ( $hesabix_currency == 'IRT' && $woocommerce_currency == 'IRR' ) ) {
						update_option( 'ssbhesabix_hesabix_default_currency', $hesabix_currency );
					} else {
						update_option( 'ssbhesabix_hesabix_default_currency', 0 );
						update_option( 'ssbhesabix_live_mode', 0 );

						echo '<div class="error">';
						echo '<p class="hesabix-p">' . __( 'Hesabix and WooCommerce default currency must be same.', 'ssbhesabix' );
						echo '</div>';
					}
				} else {
					echo '<div class="error">';
					echo '<p class="hesabix-p">' . __( 'Cannot check the Hesabix default currency. Error Message: ', 'ssbhesabix' ) . $default_currency->ErrorMessage . '</p>';
					echo '</div>';

					HesabixLogService::log( array( "Cannot check the Hesabix default currency. Error Message: $default_currency->ErrorMessage. Error Code: $default_currency->ErrorCode" ) );
				}

				if ( get_option( 'ssbhesabix_live_mode' ) ) {
					echo '<div class="updated">';
					echo '<p class="hesabix-p">' . __( 'API Setting updated. Test Successfully', 'ssbhesabix' ) . '</p>';
					echo '</div>';
				}
			} else {
				update_option( 'ssbhesabix_live_mode', 0 );
				echo '<div class="error">';
				echo '<p class="hesabix-p">' . __( 'Cannot set Hesabix webHook. Error Message:', 'ssbhesabix' ) . $response->ErrorMessage . '</p>';
				echo '</div>';
				HesabixLogService::log( array("Cannot set Hesabix webHook. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode") );
			}
		} else {
			update_option( 'ssbhesabix_live_mode', 0 );
			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Cannot connect to Hesabix servers. Please check your Internet connection', 'ssbhesabix' ) . '</p>';
			echo '</div>';

			HesabixLogService::log( array("Cannot connect to hesabix servers. Check your internet connection" ) );
		}

		return $response;
	}
//=============================================================================================
	public static function ssbhesabix_get_banks() {
		$ssbhesabix_api = new Ssbhesabix_Api();
		$banks          = $ssbhesabix_api->settingGetBanks();

		if ( is_object( $banks ) && $banks->Success ) {
			$available_banks        = array();
			$available_banks[ - 1 ] = __( 'Choose', 'ssbhesabix' );
			foreach ( $banks->Result as $bank ) {
				if ( $bank->Currency == get_woocommerce_currency() || ( get_woocommerce_currency() == 'IRT' && $bank->Currency == 'IRR' ) || ( get_woocommerce_currency() == 'IRR' && $bank->Currency == 'IRT' ) ) {
					$available_banks[ 'bank'.$bank->Code ] = $bank->Name . ' - ' . $bank->Branch . ' - ' . $bank->AccountNumber;
                }
			}

			if ( empty( $available_banks ) ) {
				$available_banks[0] = __( 'Define at least one bank in Hesabix', 'ssbhesabix' );
			}

			return $available_banks;
		} else {
			update_option( 'ssbhesabix_live_mode', 0 );

			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Cannot get Banks detail.', 'ssbhesabix' ) . '</p>';
			echo '</div>';

			HesabixLogService::log( array("Cannot get banking information. Error Code: $banks->ErrorCode. Error Message: $banks->ErrorMessage." ) );

			return array( '0' => __( 'Cannot get Banks detail.', 'ssbhesabix' ) );
		}
	}
//=============================================================================================
	public static function ssbhesabix_get_cashes() {
		$ssbhesabix_api = new Ssbhesabix_Api();
		$cashes          = $ssbhesabix_api->settingGetCashes();

		if ( is_object( $cashes ) && $cashes->Success ) {
            $available_cashes        = array();
            foreach ( $cashes->Result as $cash ) {
				if ( $cash->Currency == get_woocommerce_currency() || ( get_woocommerce_currency() == 'IRT' && $cash->Currency == 'IRR' ) || ( get_woocommerce_currency() == 'IRR' && $cash->Currency == 'IRT' ) ) {
					$available_cashes[ 'cash'.$cash->Code ] = $cash->Name;
				}
			}
			return $available_cashes;
		}
	}

//=============================================================================================
	public static function ssbhesabix_get_salesmen() {
		$ssbhesabix_api = new Ssbhesabix_Api();
		$salesmen       = $ssbhesabix_api->settingGetSalesmen();

		if ( is_object( $salesmen ) && $salesmen->Success ) {
			$available_salesmen        = array();
			$available_salesmen[ - 1 ] = __( 'Choose', 'ssbhesabix' );
			foreach ( $salesmen->Result as $salesman ) {
				if ( $salesman->Active ) {
					$available_salesmen[ $salesman->Code ] = $salesman->Name;
				}
			}

			return $available_salesmen;
		} else {
			update_option( 'ssbhesabix_live_mode', 0 );
			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Cannot get Salesmen detail.', 'ssbhesabix' ) . '</p>';
			echo '</div>';
			HesabixLogService::log( array("Cannot get salesmen information. Error Code: $salesmen->ErrorCode Error Message: .$salesmen->ErrorMessage.") );

			return array( '0' => __( 'Cannot get salesmen detail.', 'ssbhesabix' ) );
		}
	}
//=============================================================================================
	public static function ssbhesabix_log_setting() {
		$cleanLogResult = ( isset( $_GET['cleanLogResult'] ) ) ? wc_clean( $_GET['cleanLogResult'] ) : null;

		if ( ! is_null( $cleanLogResult ) && $cleanLogResult === 'true' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'The log file was cleared.', 'ssbhesabix' ) . '</p>';
			echo '</div>';
		} elseif ( $cleanLogResult === 'false' ) {
			echo '<div class="updated">';
			echo '<p class="hesabix-p">' . __( 'Log file not found.', 'ssbhesabix' ) . '</p>';
			echo '</div>';
		}

		self::ssbhesabix_tab_log_html();
	}
//=============================================================================================
	public static function ssbhesabix_tab_log_html() {
        ?>
        <div style="padding-left: 20px">
            <div class="alert alert-warning hesabix-f">
                توجه فرمایید با زدن دکمه پاک کردن کل لاگ ها، تمامی فایل های لاگ ذخیره شده پاک می شوند.
                <br>
                در صورت نیاز به پاک کردن فایل لاگ جاری می توانید از دکمه پاک کردن لاگ جاری، زمانی که فایل لاگ مدنظر انتخاب شده است، استفاده کنید.
                <br>
                فهرست تاریخچه لاگ ها، لاگ های موجود در سیستم در بازه 10 روز گذشته را نمایش می دهد.
            </div>
            <h3 class="hesabix-tab-page-title"><?php echo __( 'Events and bugs log', 'ssbhesabix' ) ?></h3>
            <div style="display:flex;align-items: center;">
                <div style="display: inline-block;">
                    <label for="ssbhesabix-clean-log-files"></label>
                    <form method="post">
                        <div>
                            <div>
                                <button name="deleteLogFiles" class="button button-primary hesabix-f" style="cursor: pointer; margin: 0.4rem 0;"><?php echo __("Delete All Log Files", "ssbhesabix"); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <label for="ssbhesabix-log-download-submit"></label>
                    <div>
                        <a class="button button-secondary hesabix-f" target="_blank"
                           href="<?php if(isset($_POST["changeLogFile"])) echo WP_CONTENT_URL . '/ssbhesabix-' . $_POST["changeLogFile"] . '.txt'; else echo WP_CONTENT_URL . '/ssbhesabix-' . date("20y-m-d") . '.txt'; ?>">
                            <?php echo __( 'Download log file', 'ssbhesabix' ); ?>
                        </a>
                    </div>
                </div>
                <div style="display: inline-block; margin-right: 10px;">
                    <form method="post">
                        <label for="ssbhesabix-log-clean-submit"></label>
                        <div>
                            <div>
                                <input name="currentLogFileDate" type="hidden" value="<?php if(isset($_POST["changeLogFile"])) echo $_POST["changeLogFile"]; else echo $_POST["ssbhesabix_find_log_date"]; ?>">
                                <button class="button button-primary hesabix-f" id="ssbhesabix-log-clean-submit"
                                        name="ssbhesabix-log-clean-submit"> <?php echo __( 'Clean current log', 'ssbhesabix' ); ?></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <br>
            <hr>
            <div style="display:flex;align-items: center;">
                <div style="display: inline-block;">
                    <form method="post">
                        <label for="ssbhesabix-find-log-submit"></label>
                        <div>
                            <input type="date" id="ssbhesabix_find_log_date" name="ssbhesabix_find_log_date" value=""
                                   class="datepicker"/>
                            <button class="button button-primary hesabix-f" id="ssbhesabix-find-log-submit"
                                    name="ssbhesabix-find-log-submit"><?php echo __( 'Find Log File', 'ssbhesabix' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            <br>
            <hr>
            <div style="display:flex;align-items: center;">
                <div style="display: inline-block;">
                    <form method="post">
                        <label for="ssbhesabix-delete-logs-between-two-dates"></label>
                        <div>
                            <input type="date" id="ssbhesabix_delete_log_date_from" name="ssbhesabix_delete_log_date_from" value=""
                                   class="datepicker"/>
                            <input type="date" id="ssbhesabix_delete_log_date_to" name="ssbhesabix_delete_log_date_to" value=""
                                   class="datepicker"/>
                            <button class="button button-primary hesabix-f" id="ssbhesabix-delete-logs-between-two-dates"
                                    name="ssbhesabix-delete-logs-between-two-dates"><?php echo __( 'Delete Logs Between These Tow Dates', 'ssbhesabix' ); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            <br>
			<?php
			if ( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt' ) &&
			     ( filesize( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt' ) / 1000 ) > 1000 ) {

				$fileSizeInMb = ( ( filesize( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt' ) / 1000 ) / 1000 );
				$fileSizeInMb = round( $fileSizeInMb, 2 );


				$str = __( 'The log file size is large, clean log file.', 'ssbhesabix' );

				echo '<div class="notice notice-warning">' .
				     '<p class="hesabix-p">' . $str . ' (' . $fileSizeInMb . 'MB)' . '</p>'
				     . '</div>';

			} else if ( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt' ) ) {

                $URL = WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt';
                $logFileContent = HesabixLogService::readLog($URL);
            }

                echo '<div id="logFileContainer" style="display: flex; justify-content: space-between; flex-direction: column;">'.
                    '<div style="direction: ltr;display: flex; flex-direction: column; align-items: center;">
                        <h3>' . __("Log History", "ssbhesabix") . '</h3>
                        <form method="post"">
                            <ul>';
                            for($i = 0 ; $i < 10 ; $i++) {
                                if( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d", strtotime(-$i."day")) . '.txt' ) ) {
                                    echo '<li class="button button-secondary" id="'.date("20y-m-d", strtotime(-$i."day")).'" style="cursor: pointer; margin: 0.4rem;"><input style="background: transparent;border: none; color: #2271B1" name="changeLogFile" type="submit" value="'. date("20y-m-d", strtotime(-$i."day")) .'" /></li>';
                                }
                            }
                            echo '
                            </ul>          
                        </form>
                    </div>';
				echo '<textarea id="textarea" rows="35" style="width: 100%; box-sizing: border-box; direction: ltr; margin-left: 10px; background-color: whitesmoke">' . $logFileContent . '</textarea>';
                echo '</div>';
//---------------------------------------
                if(isset($_POST["changeLogFile"])) {
                    echo
                    '<script>
                        document.getElementById("logFileContainer").innerHTML = "";
                    </script>';

                    $URL = WP_CONTENT_DIR . '/ssbhesabix-' . $_POST["changeLogFile"] . '.txt';
                    $logFileContent = HesabixLogService::readLog($URL);

                    echo '<div id="logFileContainer" style="display: flex; justify-content: space-between; flex-direction: column;">'.
                        '<div style="direction: ltr;display: flex; flex-direction: column; align-items: center;">
                        <h3>' . __("Log History", "ssbhesabix") . '</h3>
                        <form method="post">
                            <ul>';
                    for($i = 0 ; $i < 10 ; $i++) {
                        if( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d", strtotime(-$i."day")) . '.txt' ) ) {
                            echo '<li class="button button-secondary" id="'.date("20y-m-d", strtotime(-$i."day")).'" style="cursor: pointer; margin: 0.4rem;"><input style="background: transparent;border: none; color: #2271B1" name="changeLogFile" type="submit" value="'. date("20y-m-d", strtotime(-$i."day")) .'" /></li>';
                        }
                    }
                    echo '
                            </ul>          
                        </form>
                    </div>';
                    echo '<textarea id="textarea" rows="35" style="width: 100%; box-sizing: border-box; direction: ltr; margin-left: 10px; background-color: whitesmoke">' . $logFileContent . '</textarea>';
                    echo '</div>';
                }
//---------------------------------------
                if(isset($_POST["deleteLogFiles"])) {
                    $prefix = WP_CONTENT_DIR . '/ssbhesabix-';

                    $files = glob($prefix . '*');
                    if ($files) {
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                if (unlink($file)) {
                                    header("refresh:0");
                                } else {
                                    HesabixLogService::writeLogStr("Unable to delete the file");
                                }
                            }
                        }
                    } else {
                        HesabixLogService::writeLogStr("No files found");
                    }
                }
//---------------------------------------
                if(isset($_POST["ssbhesabix-log-clean-submit"])) {
                    if($_POST["currentLogFileDate"]) {
                        $file = WP_CONTENT_DIR . '/ssbhesabix-' . $_POST["currentLogFileDate"] . '.txt';
                    } else {
                        $file = WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d") . '.txt';
                    }
                    if (is_file($file)) {
                        if (unlink($file)) {
                            HesabixLogService::writeLogStr("Selected Log File Deleted");
                            header("refresh:0");
                        } else {
                            HesabixLogService::writeLogStr("Unable to delete the file");
                        }
                    }
                }
//---------------------------------------
                if(isset($_POST["ssbhesabix-delete-logs-between-two-dates"])) {
                    $startDate = $_POST["ssbhesabix_delete_log_date_from"];
                    $endDate = $_POST["ssbhesabix_delete_log_date_to"];

                    $directory = WP_CONTENT_DIR . '/ssbhesabix-';
                    $files = glob($directory . '*');
                    if($files) {
                        foreach ($files as $file) {
                            if(is_file($file)) {
                                $fileDate = substr($file, strlen($directory), 10);
                                $dateObj = DateTime::createFromFormat('Y-m-d', $fileDate);
                                $startObj = DateTime::createFromFormat('Y-m-d', $startDate);
                                $endObj = DateTime::createFromFormat('Y-m-d', $endDate);

                                if ($dateObj >= $startObj && $dateObj <= $endObj) {
                                     HesabixLogService::writeLogStr("Log Files deleted");
                                     unlink($file);
                                }
                            }
                        }
                    }
                    header("refresh:0");
                }
//---------------------------------------
                if(isset($_POST["ssbhesabix-find-log-submit"])) {
                    echo
                    '<script>
                        document.getElementById("logFileContainer").innerHTML = "";
                    </script>';

                    $URL = WP_CONTENT_DIR . '/ssbhesabix-' . $_POST["ssbhesabix_find_log_date"] . '.txt';
                    if ( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . $_POST["ssbhesabix_find_log_date"] . '.txt' ) &&
                        ( filesize( WP_CONTENT_DIR . '/ssbhesabix-' . $_POST["ssbhesabix_find_log_date"] . '.txt' ) / 1000 ) < 1000 ) {
                            $logFileContent = HesabixLogService::readLog($URL);
                    }


                    echo '<div id="logFileContainer" style="display: flex; justify-content: space-between; flex-direction: column;">'.
                                '<div style="direction: ltr;display: flex; flex-direction: column; align-items: center;">
                                <h3>' . __("Log History", "ssbhesabix") . '</h3>
                                <form method="post">
                                    <ul>';
                            for($i = 0 ; $i < 10 ; $i++) {
                                if( file_exists( WP_CONTENT_DIR . '/ssbhesabix-' . date("20y-m-d", strtotime(-$i."day")) . '.txt' ) ) {
                                    echo '<li class="button button-secondary" id="'.date("20y-m-d", strtotime(-$i."day")).'" style="cursor: pointer; margin: 0 0.4rem;"><input style="background: transparent;border: none; color: #2271B1" name="changeLogFile" type="submit" value="'. date("20y-m-d", strtotime(-$i."day")) .'" /></li>';
                                }
                            }
                            echo '
                                    </ul>          
                                </form>
                            </div>';
                            echo '<textarea id="textarea" rows="35" style="width: 100%; box-sizing: border-box; direction: ltr; margin-left: 10px; background-color: whitesmoke">' . $logFileContent . '</textarea>';
                            echo '</div>';
                }
			?>
        </div>
		<?php
	}
//=============================================================================================
	public static function ssbhesabix_get_warehouses() {
		$ssbhesabix_api = new Ssbhesabix_Api();
		$warehouses     = $ssbhesabix_api->settingGetWarehouses();

		if ( is_object( $warehouses ) && $warehouses->ErrorCode == 199 ) {
			$available_warehouses        = array();
			$available_warehouses[ - 1 ] = __( 'Accounting quantity (Total inventory)', 'ssbhesabix' );

			return $available_warehouses;
		}

		if ( is_object( $warehouses ) && $warehouses->Success ) {
			$available_warehouses        = array();
			$available_warehouses[ - 1 ] = __( 'Accounting quantity (Total inventory)', 'ssbhesabix' );
			foreach ( $warehouses->Result as $warehouse ) {
				$available_warehouses[ $warehouse->Code ] = $warehouse->Name;
			}

			return $available_warehouses;
		} else {
			update_option( 'ssbhesabix_live_mode', 0 );
			echo '<div class="error">';
			echo '<p class="hesabix-p">' . __( 'Cannot get warehouses.', 'ssbhesabix' ) . '</p>';
			echo '</div>';
			HesabixLogService::log( array("Cannot get warehouses. Error Code: $warehouses->ErrorCode. Error Message: .$warehouses->ErrorMessage.") );

			return array( '0' => __( 'Cannot get warehouses.', 'ssbhesabix' ) );
		}
	}

}

Ssbhesabix_Setting::init();
