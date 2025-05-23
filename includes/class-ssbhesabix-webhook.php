<?php

include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabixLogService.php');
include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabixWpFaService.php');

/*
 * @package    ssbhesabix
 * @subpackage ssbhesabix/includes
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 * @author     Babak Alizadeh <alizadeh.babak@gmail.com>
 */

class Ssbhesabix_Webhook
{
    public $invoicesObjectId = array();
    public $invoiceItemsCode = array();
    public $itemsObjectId = array();
    public $contactsObjectId = array();
    public $warehouseReceiptsObjectId = array();

    public function __construct()
    {
        //hesabixLogService::writeLogStr("Calling Webhook");
        $wpFaService = new HesabixWpFaService();

        $hesabixApi = new Ssbhesabix_Api();

        $lastChange = get_option('ssbhesabix_last_log_check_id');
        $changes = $hesabixApi->settingGetChanges($lastChange + 1);

        if ($changes->Success) {
            update_option('ssbhesabix_business_expired', 0);

            foreach ($changes->Result as $item) {
                if (!$item->API) {
                    switch ($item->ObjectType) {
                        case 'Invoice':
                            if ($item->Action == 123) {
                                $wpFa1 = $wpFaService->getWpFaByHesabixId('order', $item->Extra2);
                                if($wpFa1) {
                                    $wpFaService->delete($wpFa1);
                                    HesabixLogService::writeLogStr("The invoice link with the order deleted. Invoice number: " . $item->Extra2 . ", Order id: " . $wpFa1->idWp);
                                }
                            }
                            $this->invoicesObjectId[] = $item->ObjectId;
                            foreach (explode(',', $item->Extra) as $invoiceItem) {
                                if ($invoiceItem != '') {
                                    $this->invoiceItemsCode[] = $invoiceItem;
                                }
                            }
                            break;
                        case 'WarehouseReceipt':
                            if ($item->Action == 261) {
                                global $wpdb;
                                $hesabixApi = new Ssbhesabix_Api();
                                $receipt = $hesabixApi->getWarehouseReceipt($item->ObjectId);
//                                HesabixLogService::writeLogObj($receipt->Result->Items);
                                foreach ($receipt->Result->Items as $receiptItem) {
                                    $wpFa = $wpFaService->getWpFaByHesabixId('product', $receiptItem->ItemCode);
                                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                                        'id_hesabix' => (int)$item->ObjectId,
                                        'obj_type' => "receiptItems",
                                        'id_ps' => $wpFa->idWp,
                                        'id_ps_attribute' => $wpFa->idWpAttribute
                                    ));
                                }

                                if (get_option('ssbhesabix_item_update_quantity', 'no') == 'no') {
                                    HesabixLogService::writeLogStr("Sync Products Quantity is Off");
                                }
                            }
                            if ($item->Action == 263) {
                                global $wpdb;
                                $rows = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_hesabix` = $item->ObjectId");
                                $receiptID = $item->ObjectId;
                                foreach ($rows as $row) {
                                    $post_id = ($row->id_ps_attribute && $row->id_ps_attribute > 0) ? $row->id_ps_attribute : $row->id_ps;
                                    //$product = wc_get_product( $post_id );

                                    if (get_option('ssbhesabix_item_update_quantity', 'no') == 'no') {
                                        HesabixLogService::writeLogStr("Sync Products Quantity is Off");
                                    }

                                    $productId = $row->id_ps;
                                    $attributeId = $row->id_ps_attribute;

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
                                        } else {
                                            HesabixLogService::writeLogStr("Product is not defined in Hesabix");
                                        }
                                    }
                                }

                                $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => $receiptID));
                                break;
                            }
                            $this->warehouseReceiptsObjectId[] = $item->ObjectId;
                            break;
                        case 'Product':
                            if ($item->Action == 53) {
                                $wpFa = $wpFaService->getWpFaByHesabixId('product', $item->Extra);
                                if ($wpFa) {
                                    global $wpdb;
                                    $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id' => $wpFa->id));
                                }
                                break;
                            }

                            $this->itemsObjectId[] = $item->ObjectId;
                            break;
                        case 'Contact':
                            if ($item->Action == 33) {
                                $id_obj = $wpFaService->getWpFaIdByHesabixId('customer', $item->Extra);
                                global $wpdb;
                                $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id' => $id_obj));
                                break;
                            }

                            $this->contactsObjectId[] = $item->ObjectId;
                            break;
                    }
                }
            }

            $this->invoiceItemsCode = array_unique($this->invoiceItemsCode);
            $this->contactsObjectId = array_unique($this->contactsObjectId);
            $this->itemsObjectId = array_unique($this->itemsObjectId);
            $this->invoicesObjectId = array_unique($this->invoicesObjectId);

            $this->setChanges();

            $lastChange = end($changes->Result);
            if (is_object($lastChange))
                update_option('ssbhesabix_last_log_check_id', $lastChange->Id);
            else if ($changes->LastId)
                update_option('ssbhesabix_last_log_check_id', $changes->LastId);

        } else {
            HesabixLogService::log(array("ssbhesabix - Cannot check last changes. Error Message: " . (string)$changes->ErrorMessage . ". Error Code: " . (string)$changes->ErrorCode));
            if ($changes->ErrorCode == 108) {
                update_option('ssbhesabix_business_expired', 1);
                add_action('admin_notices', array(__CLASS__, 'ssbhesabix_business_expired_notice'));
            }
            return false;
        }

        return true;
    }
//=================================================================================================================================
    public function ssbhesabix_business_expired_notice()
    {
        echo '<div class="error"><p>' . __('Cannot connect to Hesabix. Business expired.', 'ssbhesabix') . '</p></div>';
    }
//=================================================================================================================================
    public function setChanges()
    {
        //Items
        $items = array();

        if (!empty($this->warehouseReceiptsObjectId)) {
            $receipts = $this->getObjectsByIdList($this->warehouseReceiptsObjectId, 'WarehouseReceipt');
            if ($receipts != false) {
                foreach ($receipts as $receipt) {
                    foreach ($receipt->Items as $item)
                        array_push($this->invoiceItemsCode, $item->ItemCode);
                }
            }
        }

        if (!empty($this->itemsObjectId)) {
            $objects = $this->getObjectsByIdList($this->itemsObjectId, 'item');
            if ($objects != false) {
                foreach ($objects as $object) {
                    array_push($items, $object);
                }
            }
        }

        if (!empty($this->invoiceItemsCode)) {
            $objects = $this->getObjectsByCodeList($this->invoiceItemsCode);

            if ($objects != false) {
                foreach ($objects as $object) {
                    array_push($items, $object);
                }
            }
        }

        if (!empty($items)) {
            update_option("ssbhesabix_inside_product_edit", 1);
            try {
                foreach ($items as $item) {
                    Ssbhesabix_Admin_Functions::setItemChanges($item);
                }
            } catch (Exception $e) {
            } finally {
                update_option("ssbhesabix_inside_product_edit", 0);
            }
        }

        return true;
    }
//=================================================================================================================================
    public function setInvoiceChanges($invoice)
    {
        if (!is_object($invoice)) return false;

        $wpFaService = new HesabixWpFaService();

        $number = $invoice->Number;
        $json = json_decode($invoice->Tag);
        if (is_object($json)) {
            $id_order = $json->id_order;
        } else {
            $id_order = 0;
        }

        if ($invoice->InvoiceType == 0) {
            if ($id_order == 0) {
                HesabixLogService::log(array("This invoice is not defined in OnlineStore. Invoice Number: " . $number));
            } else {
                //check if order exist in wooCommerce
                $id_obj = $wpFaService->getWpFaId('order', $id_order);
                if ($id_obj != false) {
                    global $wpdb;
                    $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id` = $id_obj");
                    if (is_object($row) && $row->id_hesabix != $number) {
                        $id_hesabix_old = $row->id_hesabix;
                        //ToDo: number must be int in hesabix
                        $wpdb->update($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => $number), array('id' => $id_obj));
                        HesabixLogService::log(array("Invoice Number changed. Old Number: $id_hesabix_old. New ID: $number"));
                    }
                }
            }
        }
    }
//=================================================================================================================================
    public function setContactChanges($contact)
    {
        if (!is_object($contact)) return false;

        $code = $contact->Code;

        $json = json_decode($contact->Tag);
        if (is_object($json)) {
            $id_customer = $json->id_customer;
        } else {
            $id_customer = 0;
        }

        if ($id_customer == 0) {
            HesabixLogService::log(array("This Customer is not define in OnlineStore. Customer code: $code"));
            return false;
        }

        $wpFaService = new HesabixWpFaService();
        $id_obj = $wpFaService->getWpFaId('customer', $id_customer);

        if ($id_obj != false) {
            global $wpdb;
            $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id` = $id_obj");

            if (is_object($row) && $row->id_hesabix != $code) {
                $id_hesabix_old = $row->id_hesabix;
                $wpdb->update($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => (int)$code), array('id' => $id_obj));

                HesabixLogService::log(array("Contact Code changed. Old ID: $id_hesabix_old. New ID: $code"));
            }
        }

        return true;
    }
//=================================================================================================================================
    public function getObjectsByIdList($idList, $type)
    {
        $hesabixApi = new Ssbhesabix_Api();
        $warehouseCode = get_option('ssbhesabix_item_update_quantity_based_on');
        switch ($type) {
            case 'item':
                if($warehouseCode == '-1') {
                    $result = $hesabixApi->itemGetById($idList);
                } else {
                    $items = $hesabixApi->itemGetById($idList);
                    $codeList = [];
                    foreach ($items->Result as $item) {
                        array_push($codeList, $item->Code);
                    }
                    $result = $hesabixApi->itemGetQuantity($warehouseCode, $codeList);
                }
                break;
            case 'contact':
                $result = $hesabixApi->contactGetById($idList);
                break;
            case 'invoice':
                $result = $hesabixApi->invoiceGetInvoices(array("Filters" => array("Property" => "Id", "Operator" => "in", "Value" => $idList)));
                break;
            case 'WarehouseReceipt':
                $result = $hesabixApi->warehouseReceiptGetByIdList($idList);
                break;
            default:
                return false;
        }

        if (is_object($result) && $result->Success) {
            return $result->Result;
        }

        return false;
    }
//=================================================================================================================================
    public function getObjectsByCodeList($codeList)
    {
        $hesabixApi = new Ssbhesabix_Api();

        $warehouse = get_option('ssbhesabix_item_update_quantity_based_on', "-1");
        if ($warehouse == "-1")
            $result = $hesabixApi->itemGetItemsByCodes($codeList);
        else {
            $result = $hesabixApi->itemGetQuantity($warehouse, $codeList);
        }

        //$result = $hesabixApi->itemGetItems($queryInfo);

        if (is_object($result) && $result->Success) {
            return $warehouse == "-1" ? $result->Result->List : $result->Result;
        }

        return false;
    }
}
