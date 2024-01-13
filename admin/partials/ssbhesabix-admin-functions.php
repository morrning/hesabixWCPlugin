<?php
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabixItemService.php');
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabixCustomerService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabixLogService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabixWpFaService.php');

/**
 * @class      Ssbhesabix_Admin_Functions
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/admin/functions
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 * @author     Babak Alizadeh <alizadeh.babak@gmail.com>
 */
class Ssbhesabix_Admin_Functions
{
    public static function isDateInFiscalYear($date)
    {
        $hesabixApi = new Ssbhesabix_Api();
        $fiscalYear = $hesabixApi->settingGetFiscalYear();

        if (is_object($fiscalYear)) {

            if ($fiscalYear->Success) {
                $fiscalYearStartTimeStamp = strtotime($fiscalYear->Result->StartDate);
                $fiscalYearEndTimeStamp = strtotime($fiscalYear->Result->EndDate);
                $dateTimeStamp = strtotime($date);

                if ($dateTimeStamp >= $fiscalYearStartTimeStamp && $dateTimeStamp <= $fiscalYearEndTimeStamp) {
                    return 1;
                } else {
                    return 0;
                }
            } else {
                HesabixLogService::log(array("Cannot get FiscalDate. Error Code: $fiscalYear->ErrroCode. Error Message: $fiscalYear->ErrorMessage"));
                return false;
            }
        }
        HesabixLogService::log(array("Cannot connect to Hesabix for get FiscalDate."));
        return false;
    }
//====================================================================================================================
    public function getProductVariations($id_product)
    {
        if (!isset($id_product)) {
            return false;
        }
        $product = wc_get_product($id_product);

        if (is_bool($product)) return false;
        if ($product->is_type('variable')) {
            $children = $product->get_children($args = '', $output = OBJECT);
            $variations = array();
            foreach ($children as $value) {
                $product_variatons = new WC_Product_Variation($value);
                if ($product_variatons->exists()) {
                    $variations[] = $product_variatons;
                }
            }
            return $variations;
        }
        return false;
    }
//========================================================================================================
    public function setItems($id_product_array)
    {
        if (!isset($id_product_array) || $id_product_array[0] == null) return false;
        if (is_array($id_product_array) && empty($id_product_array)) return true;

        $items = array();
        foreach ($id_product_array as $id_product) {
            $product = new WC_Product($id_product);
            if ($product->get_status() === "draft") continue;

            $items[] = ssbhesabixItemService::mapProduct($product, $id_product, false);

            $variations = $this->getProductVariations($id_product);
            if ($variations)
                foreach ($variations as $variation)
                    $items[] = ssbhesabixItemService::mapProductVariation($product, $variation, $id_product, false);
        }

        if (count($items) === 0) return false;
        if (!$this->saveItems($items)) return false;
        return true;
    }
//====================================================================================================================
    public function saveItems($items)
    {
        $hesabix = new Ssbhesabix_Api();
        $wpFaService = new HesabixWpFaService();

        $response = $hesabix->itemBatchSave($items);
        if ($response->Success) {
            foreach ($response->Result as $item)
                $wpFaService->saveProduct($item);
            return true;
        } else {
            HesabixLogService::log(array("Cannot add/update Hesabix items. Error Code: " . (string)$response->ErrorCode . ". Error Message: $response->ErrorMessage."));
            return false;
        }
    }
//====================================================================================================================
    public function getContactCodeByCustomerId($id_customer)
    {
        if (!isset($id_customer)) {
            return false;
        }

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabix` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $id_customer AND `obj_type` = 'customer'");

        if (is_object($row)) {
            return $row->id_hesabix;
        } else {
            return null;
        }
    }
//====================================================================================================================
    public function setContact($id_customer, $type = 'first', $id_order = '')
    {
        if (!isset($id_customer)) return false;

        $code = $this->getContactCodeByCustomerId($id_customer);

        $hesabixCustomer = ssbhesabixCustomerService::mapCustomer($code, $id_customer, $type, $id_order);

        $hesabix = new Ssbhesabix_Api();
        $response = $hesabix->contactSave($hesabixCustomer);

        if ($response->Success) {
            $wpFaService = new HesabixWpFaService();
            $wpFaService->saveCustomer($response->Result);
            return $response->Result->Code;
        } else {
            HesabixLogService::log(array("Cannot add/update customer. Error Code: " . (string)$response->ErrroCode . ". Error Message: " . (string)$response->ErrorMessage . ". Customer ID: $id_customer"));
            return false;
        }
    }
//====================================================================================================================
    public function setGuestCustomer($id_order)
    {
        if (!isset($id_order)) return false;

        //$order = new WC_Order($id_order);
        $order = wc_get_order($id_order);

        $contactCode = $this->getContactCodeByPhoneOrEmail($order->get_billing_phone(), $order->get_billing_email());

        $hesabixCustomer = ssbhesabixCustomerService::mapGuestCustomer($contactCode, $id_order);

        $hesabix = new Ssbhesabix_Api();
        $response = $hesabix->contactSave($hesabixCustomer);

        if ($response->Success) {
            $wpFaService = new HesabixWpFaService();
            $wpFaService->saveCustomer($response->Result);
            return (int)$response->Result->Code;
        } else {
            HesabixLogService::log(array("Cannot add/update contact. Error Code: " . (string)$response->ErrroCode . ". Error Message: " . (string)$response->ErrorMessage . ". Customer ID: Guest Customer"));
            return false;
        }
    }
//====================================================================================================================
    public function getContactCodeByPhoneOrEmail($phone, $email)
    {
        if (!$email && !$phone) return null;

        $hesabix = new Ssbhesabix_Api();
        $response = $hesabix->contactGetByPhoneOrEmail($phone, $email);

        if (is_object($response)) {
            if ($response->Success && $response->Result->TotalCount > 0) {
                $contact_obj = $response->Result->List;

                if (!$contact_obj[0]->Code || $contact_obj[0]->Code == '0' || $contact_obj[0]->Code == '000000') return null;

                foreach ($contact_obj as $contact) {
                    if (($contact->phone == $phone || $contact->mobile = $phone) && $contact->email == $email)
                        return (int)$contact->Code;
                }
                foreach ($contact_obj as $contact) {
                    if ($phone && $contact->phone == $phone || $contact->mobile = $phone)
                        return (int)$contact->Code;
                }
                foreach ($contact_obj as $contact) {
                    if ($email && $contact->email == $email)
                        return (int)$contact->Code;
                }
                return null;
            }
        } else {
            HesabixLogService::log(array("Cannot get Contact list. Error Message: (string)$response->ErrorMessage. Error Code: (string)$response->ErrorCode."));
        }

        return null;
    }
//====================================================================================================================
    //Invoice
    public function setOrder($id_order, $orderType = 0, $reference = null)
    {
        if (!isset($id_order)) {
            return false;
        }

        $wpFaService = new HesabixWpFaService();

        $number = $this->getInvoiceNumberByOrderId($id_order);
        if (!$number) {
            $number = null;
            if ($orderType == 2) //return if saleInvoice not set before
            {
                return false;
            }
        }

//        $order = new WC_Order($id_order);
        $order = wc_get_order($id_order);

        $dokanOption = get_option("ssbhesabix_invoice_dokan", 0);

        if ($dokanOption && is_plugin_active("dokan-lite/dokan.php")) {
            $orderCreated = $order->get_created_via();
            if ($dokanOption == 1 && $orderCreated !== 'checkout')
                return false;
            else if ($dokanOption == 2 && $orderCreated === 'checkout')
                return false;
        }

        $id_customer = $order->get_customer_id();
        if ($id_customer !== 0) {

            $contactCode = $this->setContact($id_customer, 'first', $id_order);

            if ($contactCode == null) {
                if (!$contactCode) {
                    return false;
                }
            }
            HesabixLogService::writeLogStr("order ID " . $id_order);
            if (get_option('ssbhesabix_contact_address_status') == 2) {
                $this->setContact($id_customer, 'billing', $id_order);
            } elseif (get_option('ssbhesabix_contact_address_status') == 3) {
                $this->setContact($id_customer, 'shipping', $id_order);
            }
        } else {
            $contactCode = $this->setGuestCustomer($id_order);
            if (!$contactCode) {
                return false;
            }
        }

        global $notDefinedProductID;
        $notDefinedItems = array();
        $products = $order->get_items();
        foreach ($products as $product) {
            if ($product['product_id'] == 0) continue;
            $itemCode = $wpFaService->getProductCodeByWpId($product['product_id'], $product['variation_id']);
            if ($itemCode == null) {
                $notDefinedItems[] = $product['product_id'];
            }
        }

        if (!empty($notDefinedItems)) {
            if (!$this->setItems($notDefinedItems)) {
                HesabixLogService::writeLogStr("Cannot add/update Invoice. Failed to set products. Order ID: $id_order");
                return false;
            }
        }

        $invoiceItems = array();
        $i = 0;
        $failed = false;
        foreach ($products as $key => $product) {
            $itemCode = $wpFaService->getProductCodeByWpId($product['product_id'], $product['variation_id']);

            if ($itemCode == null) {
                $pId = $product['product_id'];
                $vId = $product['variation_id'];
                HesabixLogService::writeLogStr("Item not found. productId: $pId, variationId: $vId, Order ID: $id_order");

                $failed = true;
                break;
            }

//            $wcProduct = new WC_Product($product['product_id']);

            if($product['variation_id']) {
                $wcProduct = wc_get_product($product['variation_id']);
            } else {
                $wcProduct = wc_get_product($product['product_id']);
            }

            global $discount, $price;
            if( $wcProduct->is_on_sale() && get_option('ssbhesabix_set_special_sale_as_discount') === 'yes' ) {
                $price = $this->getPriceInHesabixDefaultCurrency($wcProduct->get_regular_price());
                $discount = $this->getPriceInHesabixDefaultCurrency($wcProduct->get_regular_price() - $wcProduct->get_sale_price());
                $discount *= $product['quantity'];
            } else {
                $price = $this->getPriceInHesabixDefaultCurrency($product['subtotal'] / $product['quantity']);
                $discount = $this->getPriceInHesabixDefaultCurrency($product['subtotal'] - $product['total']);
            }

            $item = array(
                'RowNumber' => $i,
                'ItemCode' => $itemCode,
                'Description' => Ssbhesabix_Validation::invoiceItemDescriptionValidation($product['name']),
                'Quantity' => (int)$product['quantity'],
                'UnitPrice' => (float)$price,
                'Discount' => (float)$discount,
                'Tax' => (float)$this->getPriceInHesabixDefaultCurrency($product['total_tax']),
            );

            $invoiceItems[] = $item;
            $i++;
        }

        if ($failed) {
            HesabixLogService::writeLogStr("Cannot add/update Invoice. Item code is NULL. Check your invoice products and relations with Hesabix. Order ID: $id_order");
            return false;
        }

        if (empty($invoiceItems)) {
            HesabixLogService::log(array("Cannot add/update Invoice. At least one item required."));
            return false;
        }

        $date_obj = $order->get_date_created();
        switch ($orderType) {
            case 0:
                $date = $date_obj->date('Y-m-d H:i:s');
                break;
            case 2:
                $date = date('Y-m-d H:i:s');
                break;
            default:
                $date = $date_obj->date('Y-m-d H:i:s');
        }

        if ($reference === null)
            $reference = $id_order;

        $order_shipping_method = "";
        foreach ($order->get_items('shipping') as $item)
            $order_shipping_method = $item->get_name();

        $note = $order->customer_note;
        if ($order_shipping_method)
            $note .= "\n" . __('Shipping method', 'ssbhesabix') . ": " . $order_shipping_method;

        global $freightOption, $freightItemCode;
        $freightOption = get_option("ssbhesabix_invoice_freight");

        if($freightOption == 1) {
            $freightItemCode = get_option('ssbhesabix_invoice_freight_code');
            if(!isset($freightItemCode) || !$freightItemCode) HesabixLogService::writeLogStr("کد هزینه حمل و نقل تعریف نشده است" . "\n" . "Freight service code is not set");

            $freightItemCode = $this->convertPersianDigitsToEnglish($freightItemCode);

            if($this->getPriceInHesabixDefaultCurrency($order->get_shipping_total()) != 0) {
                $invoiceItem = array(
                    'RowNumber' => $i,
                    'ItemCode' => $freightItemCode,
                    'Description' => 'هزینه حمل و نقل',
                    'Quantity' => 1,
                    'UnitPrice' => (float) $this->getPriceInHesabixDefaultCurrency($order->get_shipping_total()),
                    'Discount' => 0,
                    'Tax' => (float) $this->getPriceInHesabixDefaultCurrency($order->get_shipping_tax())
                );
                $invoiceItems[] = $invoiceItem;
            }
        }

        $data = array(
            'Number' => $number,
            'InvoiceType' => $orderType,
            'ContactCode' => $contactCode,
            'Date' => $date,
            'DueDate' => $date,
            'Reference' => $reference,
            'Status' => 2,
            'Tag' => json_encode(array('id_order' => $id_order)),
            'InvoiceItems' => $invoiceItems,
            'Note' => $note,
            'Freight' => ''
        );

        if($freightOption == 0) {
            $freight = $this->getPriceInHesabixDefaultCurrency($order->get_shipping_total() + $order->get_shipping_tax());
            $data['Freight'] = $freight;
        }

        $invoice_draft_save = get_option('ssbhesabix_invoice_draft_save_in_hesabix', 'no');
        if ($invoice_draft_save != 'no')
            $data['Status'] = 0;

        $invoice_project = get_option('ssbhesabix_invoice_project', -1);
        $invoice_salesman = get_option('ssbhesabix_invoice_salesman', -1);
        $invoice_salesman_percentage = get_option('ssbhesabix_invoice_salesman_percentage', 0);
        if ($invoice_project != -1) $data['Project'] = $invoice_project;
        if ($invoice_salesman != -1) $data['SalesmanCode'] = $invoice_salesman;
        if($invoice_salesman_percentage) if($invoice_salesman_percentage != 0) $data['SalesmanPercent'] = $this->convertPersianDigitsToEnglish($invoice_salesman_percentage);

        $GUID = $this->getGUID($id_order);
        $hesabix = new Ssbhesabix_Api();
        $response = $hesabix->invoiceSave($data, $GUID);
//        $response = $hesabix->invoiceSave($data, '');

        if ($response->Success) {
            global $wpdb;

            switch ($orderType) {
                case 0:
                    $obj_type = 'order';
                    break;
                case 2:
                    $obj_type = 'returnOrder';
                    break;
            }

            if ($number === null) {
                $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                    'id_hesabix' => (int)$response->Result->Number,
                    'obj_type' => $obj_type,
                    'id_ps' => $id_order,
                ));
                HesabixLogService::log(array("Invoice successfully added. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
            } else {
                $wpFaId = $wpFaService->getWpFaId($obj_type, $id_order);

                $wpdb->update($wpdb->prefix . 'ssbhesabix', array(
                    'id_hesabix' => (int)$response->Result->Number,
                    'obj_type' => $obj_type,
                    'id_ps' => $id_order,
                ), array('id' => $wpFaId));
                HesabixLogService::log(array("Invoice successfully updated. Invoice number: " . (string)$response->Result->Number . ". Order ID: $id_order"));
            }

            $warehouse = get_option('ssbhesabix_item_update_quantity_based_on', "-1");
            if ($warehouse != "-1" && $orderType === 0)
                $this->setWarehouseReceipt($invoiceItems, (int)$response->Result->Number, $warehouse, $date, $invoice_project);

            return true;
        } else {
            foreach ($invoiceItems as $item) {
                HesabixLogService::log(array("Cannot add/update Invoice. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . ". Order ID: $id_order" . "\n"
              . "Hesabix Id:" . $item['ItemCode']
            ));
            }
            return false;
        }
    }
//========================================================================================================================
    public function setWarehouseReceipt($items, $invoiceNumber, $warehouseCode, $date, $project)
    {
        $invoiceOption = get_option('ssbhesabix_invoice_freight');
        if($invoiceOption == 1) {
            $invoiceFreightCode = get_option('ssbhesabix_invoice_freight_code');
            for ($i = 0 ; $i < count($items) ; $i++) {
                if($items[$i]["ItemCode"] == $invoiceFreightCode) {
                    unset($items[$i]);
                }
            }
        }

        $data = array(
            'WarehouseCode' => $warehouseCode,
            'InvoiceNumber' => $invoiceNumber,
            'InvoiceType' => 0,
            'Date' => $date,
            'Items' => $items
        );

        if ($project != -1)
            $data['Project'] = $project;

        $hesabix = new Ssbhesabix_Api();
        $response = $hesabix->saveWarehouseReceipt($data);

        if ($response->Success)
            HesabixLogService::log(array("Warehouse receipt successfully saved/updated. warehouse receipt number: " . (string)$response->Result->Number . ". Invoice number: $invoiceNumber"));
        else
            HesabixLogService::log(array("Cannot save/update Warehouse receipt. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . ". Invoice number: $invoiceNumber"));
    }
//========================================================================================================================
    public static function getPriceInHesabixDefaultCurrency($price)
    {
        if (!isset($price)) return false;

        $woocommerce_currency = get_woocommerce_currency();
        $hesabix_currency = get_option('ssbhesabix_hesabix_default_currency');

        if (!is_numeric($price))
            $price = intval($price);

        if ($hesabix_currency == 'IRR' && $woocommerce_currency == 'IRT')
            $price *= 10;

        if ($hesabix_currency == 'IRT' && $woocommerce_currency == 'IRR')
            $price /= 10;

        return $price;
    }
//========================================================================================================================
    public static function getPriceInWooCommerceDefaultCurrency($price)
    {
        if (!isset($price)) return false;

        $woocommerce_currency = get_woocommerce_currency();
        $hesabix_currency = get_option('ssbhesabix_hesabix_default_currency');

        if (!is_numeric($price))
            $price = intval($price);

        if ($hesabix_currency == 'IRR' && $woocommerce_currency == 'IRT')
            $price /= 10;

        if ($hesabix_currency == 'IRT' && $woocommerce_currency == 'IRR')
            $price *= 10;

        return $price;
    }
//========================================================================================================================
    public function setOrderPayment($id_order)
    {
        if (!isset($id_order)) {
            return false;
        }

        $hesabix = new Ssbhesabix_Api();
        $number = $this->getInvoiceCodeByOrderId($id_order);
        if (!$number) {
            return false;
        }

        //$order = new WC_Order($id_order);
        $order = wc_get_order($id_order);

        if ($order->get_total() <= 0) {
            return true;
        }
        $bank_code = $this->getBankCodeByPaymentMethod($order->get_payment_method());

        if ($bank_code == -1) {
            return true;
        } elseif ($bank_code != false) {
            $transaction_id = $order->get_transaction_id();
            //transaction id cannot be null or empty
            if ($transaction_id == '') {
                $transaction_id = '-';
            }

            $payTempValue = substr($bank_code, 0, 4);
            global $financialData;
            if(get_option('ssbhesabix_payment_option') == 'no') {
                switch($payTempValue) {
                    case 'bank':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('bankCode' => $payTempValue);break;
                    case 'cash':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('cashCode' => $payTempValue);break;
                }
            } elseif (get_option('ssbhesabix_payment_option') == 'yes') {
                $defaultBankCode = $this->convertPersianDigitsToEnglish(get_option('ssbhesabix_default_payment_method_code'));
                $financialData = array('bankCode' => $defaultBankCode);
            }

            $date_obj = $order->get_date_paid();
            if ($date_obj == null) {
                $date_obj = $order->get_date_modified();
            }

            global $accountPath;

            if(get_option("ssbhesabix_cash_in_transit") == "1" || get_option("ssbhesabix_cash_in_transit") == "yes") {
                $func = new Ssbhesabix_Admin_Functions();
                $cashInTransitFullPath = $func->getCashInTransitFullPath();
                if(!$cashInTransitFullPath) {
                    HesabixLogService::writeLogStr("Cash in Transit is not Defined in Hesabix ---- وجوه در راه در حسابیکس یافت نشد");
                    return false;
                } else {
                    $accountPath = array("accountPath" => $cashInTransitFullPath);
                }
            }

            $response = $hesabix->invoiceGet($number);
            if ($response->Success) {
                if ($response->Result->Paid > 0) {
                    // payment submited before
                } else {
                    $response = $hesabix->invoiceSavePayment($number, $financialData, $accountPath, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabixDefaultCurrency($order->get_total()), $transaction_id);

                    if ($response->Success) {
                        HesabixLogService::log(array("Hesabix invoice payment added. Order ID: $id_order"));
                        return true;
                    } else {
                        HesabixLogService::log(array("Cannot add Hesabix Invoice payment. Order ID: $id_order. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                        return false;
                    }
                }
                return true;
            } else {
                HesabixLogService::log(array("Error while trying to get invoice. Invoice Number: $number. Error Code: " . (string)$response->ErrorCode . ". Error Message: " . (string)$response->ErrorMessage . "."));
                return false;
            }
        } else {
            HesabixLogService::log(array("Cannot add Hesabix Invoice payment - Bank Code not defined. Order ID: $id_order"));
            return false;
        }
    }
//========================================================================================================================
    public function getCashInTransitFullPath() {
        $api = new Ssbhesabix_Api();
        $accounts = $api->settingGetAccounts();
        foreach ($accounts->Result as $account) {
            if($account->Name == "وجوه در راه") {
                return $account->FullPath;
            }
        }
        return false;
    }
//========================================================================================================================
    public function getInvoiceNumberByOrderId($id_order)
    {
        if (!isset($id_order)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabix` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $id_order AND `obj_type` = 'order'");

        if (is_object($row)) {
            return $row->id_hesabix;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function getBankCodeByPaymentMethod($payment_method)
    {
        $code = get_option('ssbhesabix_payment_method_' . $payment_method);

        if (isset($code))
            return $code;
        else
            return false;
    }
//========================================================================================================================
    public function getInvoiceCodeByOrderId($id_order)
    {
        if (!isset($id_order)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id_hesabix` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $id_order AND `obj_type` = 'order'");

        if (is_object($row)) {
            return $row->id_hesabix;
        } else {
            return false;
        }
    }
//========================================================================================================================
    public function exportProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabixLogService::writeLogStr("Exporting Products");
        try {
            $wpFaService = new HesabixWpFaService();
            $extraSettingRPP = get_option("ssbhesabix_set_rpp_for_export_products");
            $rpp=500;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }
            $result = array();
            $result["error"] = false;
            global $wpdb;

            if ($batch == 1) {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`                                                                
                                    WHERE post_type = 'product' AND post_status IN('publish','private')");
                $totalBatch = ceil($total / $rpp);
            }

            $offset = ($batch - 1) * $rpp;
            $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`                                                                
                                    WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

            $items = array();

            foreach ($products as $item) {
                $id_product = $item->ID;
                $product = new WC_Product($id_product);

                $id_obj = $wpFaService->getWpFaId('product', $id_product, 0);

                if (!$id_obj) {
                    $hesabixItem = ssbhesabixItemService::mapProduct($product, $id_product);
                    array_push($items, $hesabixItem);
                    $updateCount++;
                }

                $variations = $this->getProductVariations($id_product);
                if ($variations) {
                    foreach ($variations as $variation) {
                        $id_attribute = $variation->get_id();
                        $id_obj = $wpFaService->getWpFaId('product', $id_product, $id_attribute);

                        if (!$id_obj) {
                            $hesabixItem = ssbhesabixItemService::mapProductVariation($product, $variation, $id_product);
                            array_push($items, $hesabixItem);
                            $updateCount++;
                        }
                    }
                }
            }

            if (!empty($items)) {
                $count = 0;
                $hesabix = new Ssbhesabix_Api();
                $response = $hesabix->itemBatchSave($items);
                if ($response->Success) {
                    foreach ($response->Result as $item) {
                        $json = json_decode($item->Tag);

                        global $wpdb;
                        $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                            'id_hesabix' => (int)$item->Code,
                            'obj_type' => 'product',
                            'id_ps' => (int)$json->id_product,
                            'id_ps_attribute' => (int)$json->id_attribute,
                        ));
                        HesabixLogService::log(array("Item successfully added. Item Code: " . (string)$item->Code . ". Product ID: $json->id_product - $json->id_attribute"));
                    }
                    $count += count($response->Result);
                } else {
                    HesabixLogService::log(array("Cannot add bulk item. Error Message: " . (string)$response->ErrorMessage . ". Error Code: " . (string)$response->ErrorCode . "."));
                }
                sleep(2);
            }

            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            $result["updateCount"] = $updateCount;
            return $result;
        } catch(Error $error) {
            HesabixLogService::writeLogStr("Error in export products: " . $error->getMessage());
        }
    }
//========================================================================================================================
    public function importProducts($batch, $totalBatch, $total, $updateCount)
    {
        HesabixLogService::writeLogStr("Import Products");
        try {
            $wpFaService = new HesabixWpFaService();
            $rpp=500;
            $result = array();
            $result["error"] = false;
            global $wpdb;
            $hesabix = new Ssbhesabix_Api();
            $filters = array(array("Property" => "ItemType", "Operator" => "=", "Value" => 0));

            if ($batch == 1) {
                $total = 0;
                $response = $hesabix->itemGetItems(array('Take' => 1, 'Filters' => $filters));
                if ($response->Success) {
                    $total = $response->Result->FilteredCount;
                    $totalBatch = ceil($total / $rpp);
                } else {
                    HesabixLogService::log(array("Error while trying to get products for import. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                    $result["error"] = true;
                    return $result;
                };
            }

            $id_product_array = array();
            $offset = ($batch - 1) * $rpp;

            $response = $hesabix->itemGetItems(array('Skip' => $offset, 'Take' => $rpp, 'SortBy' => 'Id', 'Filters' => $filters));
            if ($response->Success) {
                $items = $response->Result->List;
                $from = $response->Result->From;
                $to = $response->Result->To;

                foreach ($items as $item) {
                    $wpFa = $wpFaService->getWpFaByHesabixId('product', $item->Code);
                    if ($wpFa) continue;

                    $clearedName = preg_replace("/\s+|\/|\\\|\(|\)/", '-', trim($item->Name));
                    $clearedName = preg_replace("/\-+/", '-', $clearedName);
                    $clearedName = trim($clearedName, '-');
                    $clearedName = preg_replace(["/۰/", "/۱/", "/۲/", "/۳/", "/۴/", "/۵/", "/۶/", "/۷/", "/۸/", "/۹/"],
                        ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"], $clearedName);

                    // add product to database
                    $wpdb->insert($wpdb->prefix . 'posts', array(
                        'post_author' => get_current_user_id(),
                        'post_date' => date("Y-m-d H:i:s"),
                        'post_date_gmt' => date("Y-m-d H:i:s"),
                        'post_content' => '',
                        'post_title' => $item->Name,
                        'post_excerpt' => '',
                        'post_status' => 'private',
                        'comment_status' => 'open',
                        'ping_status' => 'closed',
                        'post_password' => '',
                        'post_name' => $clearedName,
                        'to_ping' => '',
                        'pinged' => '',
                        'post_modified' => date("Y-m-d H:i:s"),
                        'post_modified_gmt' => date("Y-m-d H:i:s"),
                        'post_content_filtered' => '',
                        'post_parent' => 0,
                        'guid' => get_site_url() . '/product/' . $clearedName . '/',
                        'menu_order' => 0,
                        'post_type' => 'product',
                        'post_mime_type' => '',
                        'comment_count' => 0,
                    ));

                    $postId = $wpdb->insert_id;
                    $id_product_array[] = $postId;
                    $price = self::getPriceInWooCommerceDefaultCurrency($item->SellPrice);

                    // add product link to hesabix
                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                        'obj_type' => 'product',
                        'id_hesabix' => (int)$item->Code,
                        'id_ps' => $postId,
                        'id_ps_attribute' => 0,
                    ));

                    update_post_meta($postId, '_manage_stock', 'yes');
                    update_post_meta($postId, '_sku', $item->Barcode);
                    update_post_meta($postId, '_regular_price', $price);
                    update_post_meta($postId, '_price', $price);
                    update_post_meta($postId, '_stock', $item->Stock);

                    $new_stock_status = ($item->Stock > 0) ? "instock" : "outofstock";
                    wc_update_product_stock_status($postId, $new_stock_status);
                    $updateCount++;
                }

            } else {
                HesabixLogService::log(array("Error while trying to get products for import. Error Message: (string)$response->ErrorMessage. Error Code: (string)$response->ErrorCode."));
                $result["error"] = true;
                return $result;
            }
            sleep(2);

            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            $result["updateCount"] = $updateCount;
            return $result;
        } catch(Error $error) {
            HesabixLogService::writeLogStr("Error in importing products" . $error->getMessage());
        }
    }

//========================================================================================================================
    public function exportCustomers($batch, $totalBatch, $total, $updateCount)
    {
        HesabixLogService::writeLogStr("Export Customers");
        $wpFaService = new HesabixWpFaService();

        $result = array();
        $result["error"] = false;
        $rpp = 500;
        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "users`");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $customers = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "users` ORDER BY ID ASC LIMIT $offset,$rpp");

        $items = array();
        foreach ($customers as $item) {
            $id_customer = $item->ID;
            $id_obj = $wpFaService->getWpFaId('customer', $id_customer);
            if (!$id_obj) {
                $hesabixCustomer = ssbhesabixCustomerService::mapCustomer(null, $id_customer);
                array_push($items, $hesabixCustomer);
                $updateCount++;
            }
        }

        if (!empty($items)) {
            $hesabix = new Ssbhesabix_Api();
            $response = $hesabix->contactBatchSave($items);
            if ($response->Success) {
                foreach ($response->Result as $item) {
                    $json = json_decode($item->Tag);

                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                        'id_hesabix' => (int)$item->Code,
                        'obj_type' => 'customer',
                        'id_ps' => (int)$json->id_customer,
                    ));

                    HesabixLogService::log(array("Contact successfully added. Contact Code: " . $item->Code . ". Customer ID: " . (int)$json->id_customer));
                }
            } else {
                HesabixLogService::log(array("Cannot add bulk contacts. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
            }
        }

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;

        return $result;
    }
//========================================================================================================================
    public function syncOrders($from_date, $batch, $totalBatch, $total, $updateCount)
    {

        HesabixLogService::writeLogStr("Sync Orders");
        $wpFaService = new HesabixWpFaService();

        $result = array();
        $result["error"] = false;
        $rpp = 10;
        global $wpdb;

        if (!isset($from_date) || empty($from_date)) {
            $result['error'] = 'inputDateError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($from_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'
                                ORDER BY ID ASC LIMIT $offset,$rpp");
        HesabixLogService::writeLogStr("Orders count: " . count($orders));

        $statusesToSubmitInvoice = get_option('ssbhesabix_invoice_status');
        $statusesToSubmitInvoice = implode(',', $statusesToSubmitInvoice);
        $statusesToSubmitReturnInvoice = get_option('ssbhesabix_invoice_return_status');
        $statusesToSubmitReturnInvoice = implode(',', $statusesToSubmitReturnInvoice);
        $statusesToSubmitPayment = get_option('ssbhesabix_payment_status');
        $statusesToSubmitPayment = implode(',', $statusesToSubmitPayment);

        $id_orders = array();
        foreach ($orders as $order) {
            //$order = new WC_Order($order->ID);
            $order = wc_get_order($order->ID);

            $id_order = $order->get_id();
            $id_obj = $wpFaService->getWpFaId('order', $id_order);
            $current_status = $order->get_status();

            if (!$id_obj) {
                if (strpos($statusesToSubmitInvoice, $current_status) !== false) {
                    if ($this->setOrder($id_order)) {
                        array_push($id_orders, $id_order);
                        $updateCount++;

                        if (strpos($statusesToSubmitPayment, $current_status) !== false)
                            $this->setOrderPayment($id_order);

                        // set return invoice
                        if (strpos($statusesToSubmitReturnInvoice, $current_status) !== false) {
                            $this->setOrder($id_order, 2, $this->getInvoiceCodeByOrderId($id_order));
                        }
                    }
                }
            }

        }

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        $result["updateCount"] = $updateCount;
        return $result;
    }
//========================================================================================================================
    public function syncProducts($batch, $totalBatch, $total)
    {
        try {
            HesabixLogService::writeLogStr("Sync products price and quantity from hesabix to store: part $batch");
            $result = array();
            $result["error"] = false;
            $extraSettingRPP = get_option("ssbhesabix_set_rpp_for_sync_products_into_woocommerce");

            $rpp=200;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

            $hesabix = new Ssbhesabix_Api();
            $filters = array(array("Property" => "ItemType", "Operator" => "=", "Value" => 0));

            if ($batch == 1) {
                $response = $hesabix->itemGetItems(array('Take' => 1, 'Filters' => $filters));
                if ($response->Success) {
                    $total = $response->Result->FilteredCount;
                    $totalBatch = ceil($total / $rpp);
                } else {
                    HesabixLogService::log(array("Error while trying to get products for sync. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                    $result["error"] = true;
                    return $result;
                }
            }

            $offset = ($batch - 1) * $rpp;
            $response = $hesabix->itemGetItems(array('Skip' => $offset, 'Take' => $rpp, 'SortBy' => 'Id', 'Filters' => $filters));

            $warehouse = get_option('ssbhesabix_item_update_quantity_based_on', "-1");

            if ($warehouse != "-1") {
                $products = $response->Result->List;
                $codes = [];
                foreach ($products as $product)
                    $codes[] = $product->Code;
                $response = $hesabix->itemGetQuantity($warehouse, $codes);
            }

            if ($response->Success) {
                $products = $warehouse == "-1" ? $response->Result->List : $response->Result;
                foreach ($products as $product) {
                    self::setItemChanges($product);
                }
            } else {
                HesabixLogService::log(array("Error while trying to get products for sync. Error Message: $response->ErrorMessage. Error Code: $response->ErrorCode."));
                $result["error"] = true;
                return $result;
            }

            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            return $result;
        } catch (Error $error) {
            HesabixLogService::writeLogStr("Error in sync products: " . $error->getMessage());
        }
    }
//========================================================================================================================
    public function syncProductsManually($data)
    {
        HesabixLogService::writeLogStr('Sync Products Manually');

        $hesabix_item_codes = array();
        foreach ($data as $d) {
            if ($d["hesabix_id"]) {
                $hesabix_item_codes[] = str_pad($d["hesabix_id"], 6, "0", STR_PAD_LEFT);
            }
        }

        $hesabix = new Ssbhesabix_Api();

        $filters = array(array("Property" => "Code", "Operator" => "in", "Value" => $hesabix_item_codes));
        $response = $hesabix->itemGetItems(array('Take' => 100, 'Filters' => $filters));

        if ($response->Success) {
            $products = $response->Result->List;
            $products_codes = array();
            foreach ($products as $product)
                $products_codes[] = $product->Code;
            $diff = array_diff($hesabix_item_codes, $products_codes);
            if (is_array($diff) && count($diff) > 0) {
                return array("result" => false, "data" => $diff);
            }
        }

        $id_product_array = array();
        global $wpdb;

        foreach ($data as $d) {
            $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps_attribute` = " . $d["id"] . " AND `obj_type` = 'product'");

            if (!is_object($row)) {
                $row = $wpdb->get_row("SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id_ps` = " . $d["id"] . " AND `obj_type` = 'product'");
            }
            if (is_object($row)) {
                if (!$d["hesabix_id"])
                    $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id' => $row->id));
                else
                    $wpdb->update($wpdb->prefix . 'ssbhesabix', array('id_hesabix' => $d["hesabix_id"]), array('id' => $row->id));
            } else {
                if (!$d["hesabix_id"])
                    continue;
                if ($d["parent_id"])
                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array('obj_type' => 'product', 'id_hesabix' => $d["hesabix_id"], 'id_ps' => $d["parent_id"], 'id_ps_attribute' => $d["id"]));
                else
                    $wpdb->insert($wpdb->prefix . 'ssbhesabix', array('obj_type' => 'product', 'id_hesabix' => $d["hesabix_id"], 'id_ps' => $d["id"], 'id_ps_attribute' => '0'));
            }

            if ($d["hesabix_id"]) {
                if ($d["parent_id"]) {
                    if (!in_array($d["parent_id"], $id_product_array))
                        $id_product_array[] = $d["parent_id"];
                } else {
                    if (!in_array($d["id"], $id_product_array))
                        $id_product_array[] = $d["id"];
                }
            }
        }

        $this->setItems($id_product_array);
        return array("result" => true, "data" => null);
    }
//========================================================================================================================
    public function updateProductsInHesabixBasedOnStore($batch, $totalBatch, $total)
    {
        HesabixLogService::writeLogStr("Update Products In Hesabix Based On Store");
        $result = array();
        $result["error"] = false;
        $extraSettingRPP = get_option('ssbhesabix_set_rpp_for_sync_products_into_hesabix');

        $rpp=500;
        if($extraSettingRPP) {
            if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                $rpp=$extraSettingRPP;
            }
        }

        global $wpdb;

        if ($batch == 1) {
            $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private')");
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
        $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`                                                                
                                WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

        $products_id_array = array();
        foreach ($products as $product)
            $products_id_array[] = $product->ID;
        $this->setItems($products_id_array);
        sleep(2);

        $result["batch"] = $batch;
        $result["totalBatch"] = $totalBatch;
        $result["total"] = $total;
        return $result;
    }
//========================================================================================================================
    public static function updateProductsInHesabixBasedOnStoreWithFilter($offset=0, $rpp=0)
    {
        HesabixLogService::writeLogStr("Update Products With Filter In Hesabix Based On Store");
        $result = array();
        $result["error"] = false;

        global $wpdb;
        if($offset != 0 && $rpp != 0) {
            if(abs($rpp - $offset) <= 200) {
                if($rpp > $offset) {
                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`                                                                
                                            WHERE ID BETWEEN $offset AND $rpp AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabix_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                } else {
                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`                                                                
                                            WHERE ID BETWEEN $rpp AND $offset AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabix_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                }
            } else {
                $result['error'] = true;
                echo '<script>alert("بازه ID نباید بیشتر از 200 عدد باشد")</script>';
            }
        } else {
            echo '<script>alert("کد کالای معتبر وارد نمایید")</script>';
        }

        return $result;
    }
//========================================================================================================================
    public function cleanLogFile()
    {
        HesabixLogService::clearLog();
        return true;
    }
//========================================================================================================================
    public static function setItemChanges($item)
    {
        if (!is_object($item)) return false;

        if ($item->Quantity || !$item->Stock)
            $item->Stock = $item->Quantity;

        $wpFaService = new HesabixWpFaService();
        global $wpdb;

        $wpFa = $wpFaService->getWpFaByHesabixId('product', $item->Code);
        if (!$wpFa) return false;

        $id_product = $wpFa->idWp;
        $id_attribute = $wpFa->idWpAttribute;

        if ($id_product == 0) {
            HesabixLogService::log(array("Item with code: $item->Code is not defined in Online store"));
            return false;
        }

        $found = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` WHERE ID = $id_product");

        if (!$found) {
            HesabixLogService::writeLogStr("product not found in woocommerce.code: $item->Code, product id: $id_product, variation id: $id_attribute");
            return false;
        }

        $product = wc_get_product($id_product);
        $variation = $id_attribute != 0 ? wc_get_product($id_attribute) : null;


//        $product = new WC_Product($id_product);
//        $variation = $id_attribute != 0 ? new WC_Product($id_attribute) : null;

        $result = array();
        $result["newPrice"] = null;
        $result["newQuantity"] = null;

        $p = $variation ? $variation : $product;

        if (get_option('ssbhesabix_item_update_price') == 'yes')
            $result = self::setItemNewPrice($p, $item, $id_attribute, $id_product, $result);

        if (get_option('ssbhesabix_item_update_quantity') == 'yes')
            $result = self::setItemNewQuantity($p, $item, $id_product, $id_attribute, $result);

        return $result;
    }
//========================================================================================================================
    private static function setItemNewPrice($product, $item, $id_attribute, $id_product, array $result)
    {
        try {
            $option_sale_price = get_option('ssbhesabix_item_update_sale_price', 0);
            $woocommerce_currency = get_woocommerce_currency();
            $hesabix_currency = get_option('ssbhesabix_hesabix_default_currency');

            $old_price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();
            $old_price = Ssbhesabix_Admin_Functions::getPriceInHesabixDefaultCurrency($old_price);

            $post_id = $id_attribute && $id_attribute > 0 ? $id_attribute : $id_product;

            if ($item->SellPrice != $old_price) {
                $new_price = Ssbhesabix_Admin_Functions::getPriceInWooCommerceDefaultCurrency($item->SellPrice);
                update_post_meta($post_id, '_regular_price', $new_price);
                update_post_meta($post_id, '_price', $new_price);


                $sale_price = $product->get_sale_price();
                if ($sale_price && is_numeric($sale_price)) {
                    $sale_price = Ssbhesabix_Admin_Functions::getPriceInHesabixDefaultCurrency($sale_price);
                    if (+$option_sale_price === 1) {
                        update_post_meta($post_id, '_sale_price', null);
                    } elseif (+$option_sale_price === 2) {
                        update_post_meta($post_id, '_sale_price', round(($sale_price * $new_price) / $old_price));
                        update_post_meta($post_id, '_price', round(($sale_price * $new_price) / $old_price));
                    } else {
                        if($woocommerce_currency == 'IRT' && $hesabix_currency == 'IRR') update_post_meta($post_id, '_price', ($sale_price/10));
                        elseif($woocommerce_currency == 'IRR' && $hesabix_currency == 'IRT') update_post_meta($post_id, '_price', ($sale_price*10));
                        elseif($woocommerce_currency == 'IRR' && $hesabix_currency == 'IRR') update_post_meta($post_id, '_price', $sale_price);
                        elseif($woocommerce_currency == 'IRT' && $hesabix_currency == 'IRT') update_post_meta($post_id, '_price', $sale_price);
                    }
                }

                HesabixLogService::log(array("product ID $id_product-$id_attribute Price changed. Old Price: $old_price. New Price: $new_price"));
                $result["newPrice"] = $new_price;
            }

            return $result;
        } catch (Error $error) {
            HesabixLogService::writeLogStr("Error in Set Item New Price -> $error");
        }
    }
//========================================================================================================================
    private static function setItemNewQuantity($product, $item, $id_product, $id_attribute, array $result)
    {
        try {
            $old_quantity = $product->get_stock_quantity();
            if ($item->Stock != $old_quantity) {
                $new_quantity = $item->Stock;
                if (!$new_quantity) $new_quantity = 0;

                $new_stock_status = ($new_quantity > 0) ? "instock" : "outofstock";

                $post_id = ($id_attribute && $id_attribute > 0) ? $id_attribute : $id_product;

                update_post_meta($post_id, '_stock', $new_quantity);
                wc_update_product_stock_status($post_id, $new_stock_status);

                HesabixLogService::log(array("product ID $id_product-$id_attribute quantity changed. Old quantity: $old_quantity. New quantity: $new_quantity"));
                $result["newQuantity"] = $new_quantity;
            }

            return $result;
        } catch (Error $error) {
            HesabixLogService::writeLogStr("Error in Set Item New Price -> $error");
        }
    }
//=========================================================================================================================
    function CheckNationalCode($NationalCode): void
    {
        $identicalDigits = ['1111111111', '2222222222', '3333333333', '4444444444', '5555555555', '6666666666', '7777777777', '8888888888', '9999999999'];

        if(strlen($NationalCode) === 10) {
            $summation = 0;
            $j = 10;
            for($i = 0 ; $i < 9 ; $i++) {
                $digit = substr($NationalCode, $i, 1);
                $temp = $digit * $j;
                $j -= 1;
                $summation += $temp;
            }
            $controlDigit = substr($NationalCode, 9, 1);
            $retrieve = $summation % 11;

            if(in_array($NationalCode, $identicalDigits) === false) {
                if($retrieve < 2) {
                    if($controlDigit != $retrieve) {
                        wc_add_notice(__('please enter a valid national code', 'ssbhesabix'), 'error');
                    }
                } else {
                    if($controlDigit != (11 - $retrieve)) {
                        wc_add_notice(__('please enter a valid national code', 'ssbhesabix'), 'error');
                    }
                }
            }
        } else {
            wc_add_notice(__('please enter a valid national code', 'ssbhesabix'), 'error');
        }
    }
//=========================================================================================================================
    function CheckWebsite($Website): void
    {
        if (filter_var($Website, FILTER_VALIDATE_URL)) {
            //
        } else {
            wc_add_notice(__('please enter a valid Website URL', 'ssbhesabix'), 'error');
        }
    }
//=========================================================================================================================
    public static function enableDebugMode(): void {
        update_option('ssbhesabix_debug_mode', 1);
    }

    public static function disableDebugMode(): void {
        update_option('ssbhesabix_debug_mode', 0);
    }
//=========================================================================================================================
    public function convertPersianDigitsToEnglish($inputString) : int {
        $newNumbers = range(0, 9);
        $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
        $arabicDecimal  = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
        $arabic  = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

        $string =  str_replace($persianDecimal, $newNumbers, $inputString);
        $string =  str_replace($arabicDecimal, $newNumbers, $string);
        $string =  str_replace($persian, $newNumbers, $string);

        return str_replace($arabic, $newNumbers, $string);
    }
//=========================================================================================================================
    function generateGUID() : string {
        $characters = '0123456789ABCDEF';
        $guid = '';

        for ($i = 0; $i < 32; $i++) {
            $guid .= $characters[mt_rand(0, 15)];
            if ($i == 7 || $i == 11 || $i == 15 || $i == 19) {
                $guid .= '-';
            }
        }

        return $guid;
    }
//=========================================================================================================================
    public function getGUID($id_order): string {
        $option = get_option($id_order);

        if ($option === false || $option == 0) {
            $GUID = $this->generateGUID();
            $expirationDateTime = new DateTime('now', new DateTimeZone('UTC'));
            add_option($id_order, $expirationDateTime->format('Y-m-d H:i:s') . $GUID);
        } else {
            $expirationDateTime = new DateTime(substr($option, 0, 19), new DateTimeZone('UTC'));
            $currentDateTime = new DateTime('now', new DateTimeZone('UTC'));

            $diff = $currentDateTime->diff($expirationDateTime);

            if ($diff->days < 1) {
                // GUID is still valid, continue processing
            } else {
                // GUID expired, reset the option to allow saving a new invoice
                $GUID = $this->generateGUID();
                $expirationDateTime = new DateTime('now', new DateTimeZone('UTC'));
                update_option($id_order, $expirationDateTime->format('Y-m-d H:i:s') . $GUID);
            }
        }

        return substr(get_option($id_order), 20);
    }
//=========================================================================================================================
}