<?php

/**
 * @class      Ssbhesabix_Admin_Display
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/admin/display
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Babak Alizadeh <alizadeh.babak@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */

class Ssbhesabix_Admin_Display
{
    /**
     * Ssbhesabix_Admin_Display constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array(__CLASS__, 'hesabix_add_menu'));
    }

    /**
     * Hook in methods
     * @since    1.0.0
     * @access   static
     */

    /**
     * @since    1.0.0
     * @access   public
     */

    static function hesabix_add_menu()
    {
        $iconUrl = plugins_url('/hesabix-accounting/admin/img/menu-icon.png');
        add_menu_page("حسابیکس", "حسابیکس", "manage_options", "ssbhesabix-option", array(__CLASS__, 'hesabix_plugin_page'), $iconUrl, null);
        add_submenu_page("ssbhesabix-option", "تنظیمات حسابیکس", "تنظیمات حسابیکس", "manage_options", 'ssbhesabix-option', array(__CLASS__, 'hesabix_plugin_page'));
    }

    function hesabix_plugin_sync_products_manually()
    {
        $page = $_GET["p"];
        $rpp = $_GET["rpp"];
        if (isset($_GET['data'])) {
            $data = $_GET["data"];
            $codesNotFoundInHesabix = explode(",", $data);
        }
        //set default values to page and rpp
        if (!$page) $page = 1;
        if (!$rpp) $rpp = 10;

        $result = self::getProductsAndRelations($page, $rpp);
        $pageCount = ceil($result["totalCount"] / $rpp);
        $i = ($page - 1) * $rpp;
        $rpp_options = [10, 15, 20, 30, 50];

        $showTips = true;
        if (!isset($_COOKIE['syncProductsManuallyHelp'])) {
            setcookie('syncProductsManuallyHelp', 'ture');
        } else {
            $showTips = false;
        }

        self::hesabix_plugin_header();
        ?>
        <div class="hesabix-f">
            <p class="p mt-4">
            <h5 class="h5 hesabix-tab-page-title">
                همسان سازی دستی کالاهای فروشگاه با حسابیکس
                <span class="badge bg-warning text-dark hand <?= $showTips ? 'd-none' : 'd-inline-block' ?>"
                      id="show-tips-btn">مشاهده نکات مهم</span>
            </h5>

            <div class="alert alert-danger alert-dismissible fade show <?= isset($codesNotFoundInHesabix) ? 'd-block' : 'd-none' ?>"
                 role="alert">
                <strong>خطا</strong><br> کدهای زیر در حسابیکس پیدا نشد:
                <br>
                <?php foreach ($codesNotFoundInHesabix as $code): ?>
                    <span class="badge bg-secondary"><?= $code ?></span>&nbsp;
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div id="tips-alert"
                 class="alert alert-warning alert-dismissible fade show <?= $showTips ? 'd-block' : 'd-none' ?>"
                 role="alert">
                <strong>توجه!</strong>
                <ul style="list-style-type:square">
                    <li>تغییرات هر صفحه را ذخیره کنید و سپس به صفحه بعد بروید.</li>
                    <li>کد حسابیکس همان کد 4 رقمی (کد حسابداری کالا) است.</li>
                    <li>از وجود تعریف کالا در حسابیکس اطمینان حاصل کنید.</li>
                    <li>این صفحه برای زمانی است که شما از قبل یک کالا را هم در فروشگاه و هم در حسابیکس
                        تعریف کرده اید اما اتصالی بین آنها وجود ندارد.
                        به کمک این صفحه می توانید این اتصال را بصورت دستی برقرار کنید.
                    </li>
                    <li>
                        برای راحتی کار، این جدول بر اساس نام محصول مرتب سازی شده است،
                        بنابراین در حسابیکس نیز لیست کالاها را بر اساس نام مرتب سازی کرده و از روی آن شروع به وارد کردن
                        کدهای
                        متناظر در این جدول نمایید.
                    </li>
                </ul>
                <button type="button" class="btn-close" id="hide-tips-btn"></button>
            </div>

            </p>
            <form id="ssbhesabix_sync_products_manually" autocomplete="off"
                  action="<?php echo admin_url('admin.php?page=hesabix-sync-products-manually&p=1'); ?>"
                  method="post">

                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">ID</th>
                        <th scope="col">نام کالا</th>
                        <th scope="col">شناسه محصول</th>
                        <th scope="col">کد حسابیکس</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result["data"] as $p):
                        $i++; ?>
                        <tr class="<?= $p->id_hesabix ? 'table-success' : 'table-danger'; ?>">
                            <th scope="row"><?= $i; ?></th>
                            <td><?= $p->ID; ?></td>
                            <td><?= $p->post_title; ?></td>
                            <td><?= $p->sku; ?></td>
                            <td>
                                <input type="text" class="form-control code-input" id="<?= $p->ID; ?>"
                                       data-parent-id="<?= $p->post_parent; ?>" value="<?= $p->id_hesabix; ?>"
                                       style="width: 100px">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <label><?= $result["totalCount"] ?> رکورد </label> |
                <label><?= $pageCount ?> صفحه </label> |
                <label>صفحه جاری: </label>
                <input id="pageNumber" class="form-control form-control-sm d-inline" type="text" value="<?= $page ?>"
                       style="width: 80px">
                <a id="goToPage" class="btn btn-outline-secondary btn-sm" data-rpp="<?= $rpp ?>"
                   href="javascript:void(0)">برو</a>

                <div class="dropdown d-inline">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                            id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <?= $rpp . ' ' ?>ردیف در هر صفحه
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <?php foreach ($rpp_options as $option): ?>
                            <li><a class="dropdown-item"
                                   href="?page=hesabix-sync-products-manually&p=<?= $page ?>&rpp=<?= $option ?>"><?= $option ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <a class="btn btn-outline-secondary btn-sm <?= $page == 1 ? 'disabled' : '' ?>"
                   href="?page=hesabix-sync-products-manually&p=<?= $page - 1 ?>&rpp=<?= $rpp ?>">< صفحه قبل</a>
                <a class="btn btn-outline-secondary btn-sm <?= $page == $pageCount ? 'disabled' : '' ?>"
                   href="?page=hesabix-sync-products-manually&p=<?= $page + 1 ?>&rpp=<?= $rpp ?>">صفحه بعد ></a>

                <div class="mt-3">
                    <button class="btn btn-success" id="ssbhesabix_sync_products_manually-submit"
                            name="ssbhesabix_sync_products_manually-submit"><?php echo __('Save changes', 'ssbhesabix'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }
//========================================================================================================================================
    function hesabix_plugin_repeated_products()
    {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT id_hesabix FROM " . $wpdb->prefix . "ssbhesabix WHERE obj_type = 'product' GROUP BY id_hesabix HAVING COUNT(id_hesabix) > 1;");
        $ids = array();

        foreach ($rows as $row)
            $ids[] = $row->id_hesabix;

        $idsStr = implode(',', $ids);
        $rows = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "ssbhesabix WHERE obj_type = 'product' AND id_hesabix IN ($idsStr) ORDER BY id_hesabix");
        $i = 0;

        self::hesabix_plugin_header();
        ?>
        <div class="hesabix-f mt-4">
            <h5 class="h5 hesabix-tab-page-title">
                کد محصولات تکراری
            </h5>
            <table class="table">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">کد حسابیکس</th>
                    <th scope="col">شناسه محصول</th>
                    <th scope="col">شناسه متغیر</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $p):
                    $i++; ?>
                    <tr>
                        <th scope="row"><?= $i; ?></th>
                        <td><?= $p->id_hesabix; ?></td>
                        <td><?= $p->id_ps; ?></td>
                        <td><?= $p->id_ps_attribute; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
//========================================================================================================================================
    function hesabix_plugin_tools() {
        self::hesabix_plugin_header();
        ?>
        <div class="hesabix-f mt-4">
            <h5 class="h5 hesabix-tab-page-title">
                ابزارهای افزونه حسابیکس
            </h5>

            <a href="javascript:void(0);" class="btn btn-danger mt-2" id="hesabix-clear-plugin-data" >حذف دیتای افزونه</a>
            <br>
            <a href="javascript:void(0);" class="btn btn-success mt-2" id="hesabix-install-plugin-data">نصب دیتای افزونه</a>
        </div>
        <?php
    }
//========================================================================================================================================
    public static function getProductsAndRelations($page, $rpp)
    {
        $offset = ($page - 1) * $rpp;

        global $wpdb;
        $rows = $wpdb->get_results("SELECT post.ID,post.post_title,post.post_parent,post_excerpt,wc.sku FROM `" . $wpdb->prefix . "posts` as post
                                LEFT OUTER JOIN `" . $wpdb->prefix . "wc_product_meta_lookup` as wc
                                ON post.id =  wc.product_id
                                WHERE post.post_type IN('product','product_variation') AND post.post_status IN('publish','private')
                                ORDER BY post.post_title ASC LIMIT $offset,$rpp");

        $totalCount = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` as post
                                LEFT OUTER JOIN `" . $wpdb->prefix . "wc_product_meta_lookup` as wc
                                ON post.id =  wc.product_id
                                WHERE post.post_type IN('product','product_variation') AND post.post_status IN('publish','private')");

        $links = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix`
                                WHERE obj_type ='product'");

        foreach ($rows as $r) {
            if ($r->post_excerpt)
                $r->post_title = $r->post_title . ' [' . $r->post_excerpt . ']';
        }

        foreach ($links as $link) {
            foreach ($rows as $r) {
                if ($r->ID == $link->id_ps && $link->id_ps_attribute == 0) {
                    $r->id_hesabix = $link->id_hesabix;
                } else if ($r->ID == $link->id_ps_attribute) {
                    $r->id_hesabix = $link->id_hesabix;
                }
            }
        }

        return array("data" => $rows, "totalCount" => $totalCount);
    }
//========================================================================================================================================
    /**
     * @since    1.0.0
     * @access   public
     */
    public static function hesabix_plugin_page()
    {
        $iconsArray = ['home', 'cog', 'box-open', 'users', 'file-invoice-dollar', 'money-check-alt', 'file-export', 'sync-alt', 'file-alt', 'cog'];
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            $setting_tabs = apply_filters('ssbhesabix_setting_tab', array(
                'home' => __('Home', 'ssbhesabix'),
                'api' => __('API', 'ssbhesabix'),
                'catalog' => __('Catalog', 'ssbhesabix'),
                'customers' => __('Customers', 'ssbhesabix'),
                'invoice' => __('Invoice', 'ssbhesabix'),
                'payment' => __('Payment Methods', 'ssbhesabix'),
                'export' => __('Import and export data', 'ssbhesabix'),
                'sync' => __('Sync', 'ssbhesabix'),
                'log' => __('Log', 'ssbhesabix'),
                'extra' => __('Extra Settings', 'ssbhesabix')
            ));
            $current_tab = (isset($_GET['tab'])) ? wc_clean($_GET['tab']) : 'home';
            self::hesabix_plugin_header();
            ?>
            <h2 class="nav-tab-wrapper mt-2">
                <?php
                $i = 0;
                foreach ($setting_tabs as $name => $label) {
                    $iconUrl = plugins_url("/hesabix-accounting/admin/img/icons/$iconsArray[$i].svg");
                    $i++;
                    echo '<a href="' . admin_url('admin.php?page=ssbhesabix-option&tab=' . $name) . '" class="nav-tab ' . ($current_tab == $name ? 'nav-tab-active' : '') . '">' . "<svg width='16' height='16' class='hesabix-tab-icon'><image href='$iconUrl' width='16' height='16' /></svg>" . $label . '</a>';
                }
                ?>
            </h2>
            <?php
            foreach ($setting_tabs as $setting_tabkey => $setting_tabvalue) {
                switch ($setting_tabkey) {
                    case $current_tab:
                        do_action('ssbhesabix_' . $setting_tabkey . '_setting_save_field');
                        do_action('ssbhesabix_' . $setting_tabkey . '_setting');
                        break;
                }
            }
        } else {
            echo '<div class="wrap">' . __('Hesabix Plugin requires the WooCommerce to work!, Please install/activate woocommerce and try again', 'ssbhesabix') . '</div>';
        }
    }
//========================================================================================================================================
    public static function hesabix_plugin_header()
    {
        $logoUrl = plugins_url('/hesabix-accounting/admin/img/hesabix-logo.fa.png');
        ?>
        <div class="hesabix-header">
            <div class="row">
                <div class="col-auto">
                    <img src="<?php echo $logoUrl ?>" alt="حسابیکس">
                </div>
                <div class="col"></div>
                <div class="col-auto">
                    <a class="btn btn-sm btn-success" href="https://my.hesabix.ir" target="_blank">ورود به
                        حسابیکس</a>
                    <a class="btn btn-sm btn-warning"
                       href="https://hesabix.ir/help/topics/%D8%A7%D9%81%D8%B2%D9%88%D9%86%D9%87/%D9%88%D9%88%DA%A9%D8%A7%D9%85%D8%B1%D8%B3"
                       target="_blank">راهنمای افزونه</a>
                </div>
            </div>
        </div>
        <?php
    }
//========================================================================================================================================
}

new Ssbhesabix_Admin_Display();
