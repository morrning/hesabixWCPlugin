<?php
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabixItemService.php');
include_once(plugin_dir_path(__DIR__) . 'services/ssbhesabixCustomerService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabixLogService.php');
include_once(plugin_dir_path(__DIR__) . 'services/HesabixWpFaService.php');

/**
 * @class      Ssbhesabix_Admin_Functions
 * @version    2.1.1
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/admin/functions
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
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
        //$row = $wpdb->get_row("SELECT `id_hesabix` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $id_customer AND `obj_type` = 'customer'");

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT `id_hesabix` FROM {$wpdb->prefix}ssbhesabix 
                WHERE `id_ps` = %d AND `obj_type` = 'customer'",
                $id_customer
            )
        );

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


        $transaction_id = $order->get_transaction_id();
        //transaction id cannot be null or empty
        if ($transaction_id == '') {
            $transaction_id = '-';
        }

        global $financialData;
        if(get_option('ssbhesabix_payment_option') == 'no') {
            $bank_code = $this->getBankCodeByPaymentMethod($order->get_payment_method());
            if ($bank_code != false) {
                $payTempValue = substr($bank_code, 0, 4);

                switch($payTempValue) {
                    case 'bank':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('bankCode' => $payTempValue);break;
                    case 'cash':
                        $payTempValue = substr($bank_code, 4);
                        $financialData = array('cashCode' => $payTempValue);break;
                }
            } else {
                HesabixLogService::log(array("Cannot add Hesabix Invoice payment - Bank Code not defined. Order ID: $id_order"));
                return false;
            }
        } elseif (get_option('ssbhesabix_payment_option') == 'yes') {
            $defaultBankCode = $this->convertPersianDigitsToEnglish(get_option('ssbhesabix_default_payment_method_code'));
            if($defaultBankCode != false) {
                $financialData = array('bankCode' => $defaultBankCode);
            } else {
                HesabixLogService::writeLogStr("Default Bank Code is not Defined");
                return false;
            }
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
                $paymentMethod = $order->get_payment_method();
                $transactionFee = 0;
                if(isset($paymentMethod)) {
                    if(get_option("ssbhesabix_payment_transaction_fee_$paymentMethod") > 0) $transactionFee = $this->formatTransactionFee(get_option("ssbhesabix_payment_transaction_fee_$paymentMethod"), $this->getPriceInHesabixDefaultCurrency($order->get_total()));
                    else $transactionFee = $this->formatTransactionFee(get_option("ssbhesabix_invoice_transaction_fee"), $this->getPriceInHesabixDefaultCurrency($order->get_total()));
                }

                if(isset($transactionFee) && $transactionFee != null) $response = $hesabix->invoiceSavePayment($number, $financialData, $accountPath, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabixDefaultCurrency($order->get_total()), $transaction_id,'', $transactionFee);
                else $response = $hesabix->invoiceSavePayment($number, $financialData, $accountPath, $date_obj->date('Y-m-d H:i:s'), $this->getPriceInHesabixDefaultCurrency($order->get_total()), $transaction_id,'', 0);

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
//                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`
//                                    WHERE post_type = 'product' AND post_status IN('publish','private')");

	            $total = $wpdb->get_var(
		            $wpdb->prepare(
			            "SELECT COUNT(*) FROM {$wpdb->posts}
						        WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
		            )
	            );

                $totalBatch = ceil($total / $rpp);
            }

            $offset = ($batch - 1) * $rpp;
//            $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
//                                    WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

	        $products = $wpdb->get_results(
		        $wpdb->prepare(
			        "SELECT ID FROM {$wpdb->posts}
				        WHERE post_type = 'product' AND post_status IN ('publish', 'private')
				        ORDER BY ID ASC
				        LIMIT %d, %d",
				        $offset,
				        $rpp
		        )
	        );

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
            $extraSettingRPP = get_option("ssbhesabix_set_rpp_for_import_products");

            $rpp=100;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

            $result = array();
            $result["error"] = false;
            global $wpdb;
            $hesabix = new Ssbhesabix_Api();
            $filters = array(array("Property" => "khadamat", "Operator" => "=", "Value" => 0));

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
	                $wpdb->insert($wpdb->posts, array(
		                'post_author'           => get_current_user_id(),
		                'post_date'             => current_time('mysql'),
		                'post_date_gmt'         => current_time('mysql', 1),
		                'post_content'          => '',
		                'post_title'            => $item->Name,
		                'post_excerpt'          => '',
		                'post_status'           => 'private',
		                'comment_status'        => 'open',
		                'ping_status'           => 'closed',
		                'post_password'         => '',
		                'post_name'             => $clearedName,
		                'to_ping'               => '',
		                'pinged'                => '',
		                'post_modified'         => current_time('mysql'),
		                'post_modified_gmt'     => current_time('mysql', 1),
		                'post_content_filtered' => '',
		                'post_parent'           => 0,
		                'guid'                  => home_url('/product/' . $clearedName . '/'),
		                'menu_order'            => 0,
		                'post_type'             => 'product',
		                'post_mime_type'        => '',
		                'comment_count'         => 0,
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
    public function exportOpeningQuantity($batch, $totalBatch, $total)
    {
        try {
            $wpFaService = new HesabixWpFaService();

            $result = array();
            $result["error"] = false;
            $extraSettingRPP = get_option("ssbhesabix_set_rpp_for_export_opening_products");

            $rpp=500;
            if($extraSettingRPP) {
                if($extraSettingRPP != '-1' && $extraSettingRPP != '0') {
                    $rpp=$extraSettingRPP;
                }
            }

            global $wpdb;

	        if ($batch == 1) {
		        $total = $wpdb->get_var(
			        $wpdb->prepare(
				        "SELECT COUNT(*) FROM {$wpdb->posts}
            					WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
			        )
		        );
		        $totalBatch = ceil($total / $rpp);
	        }

            $offset = ($batch - 1) * $rpp;

	        $products = $wpdb->get_results(
		        $wpdb->prepare(
			        "SELECT ID FROM {$wpdb->posts}
					        WHERE post_type = 'product' AND post_status IN ('publish', 'private')
					        ORDER BY ID ASC
					        LIMIT %d, %d",
			        $offset,
			        $rpp
		        )
	        );

            $items = array();

            foreach ($products as $item) {
                $variations = $this->getProductVariations($item->ID);
                if (!$variations) {
                    $id_obj = $wpFaService->getWpFaId('product', $item->ID, 0);

                    if ($id_obj != false) {
                        $product = new WC_Product($item->ID);
                        $quantity = $product->get_stock_quantity();
                        $price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();

                        $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");

                        if (is_object($product) && is_object($row) && $quantity > 0 && $price > 0) {
                            array_push($items, array(
                                'Code' => $row->id_hesabix,
                                'Quantity' => $quantity,
                                'UnitPrice' => $this->getPriceInHesabixDefaultCurrency($price),
                            ));
                        }
                    }
                } else {
                    foreach ($variations as $variation) {
                        $id_attribute = $variation->get_id();
                        $id_obj = $wpFaService->getWpFaId('product', $item->ID, $id_attribute);
                        if ($id_obj != false) {
                            $quantity = $variation->get_stock_quantity();
                            $price = $variation->get_regular_price() ? $variation->get_regular_price() : $variation->get_price();

                            $row = $wpdb->get_row("SELECT `id_hesabix` FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `id` = " . $id_obj . " AND `obj_type` = 'product'");

                            if (is_object($variation) && is_object($row) && $quantity > 0 && $price > 0) {
                                array_push($items, array(
                                    'Code' => $row->id_hesabix,
                                    'Quantity' => $quantity,
                                    'UnitPrice' => $this->getPriceInHesabixDefaultCurrency($price),
                                ));
                            }
                        }
                    }
                }
            }

            if (!empty($items)) {
                $hesabix = new Ssbhesabix_Api();
                $response = $hesabix->itemUpdateOpeningQuantity($items);
                if ($response->Success) {
                    // continue batch loop
                } else {
                    HesabixLogService::log(array("ssbhesabix - Cannot set Opening quantity. Error Code: ' . $response->ErrorCode . '. Error Message: ' . $response->ErrorMessage"));
                    $result['error'] = true;
                    if ($response->ErrorCode = 199 && $response->ErrorMessage == 'No-Shareholders-Exist') {
                        $result['errorType'] = 'shareholderError';
                        return $result;
                    }
                    return $result;
                }
            }
            sleep(2);
            $result["batch"] = $batch;
            $result["totalBatch"] = $totalBatch;
            $result["total"] = $total;
            $result["done"] = $batch == $totalBatch;
            return $result;
        } catch(Error $error) {
            HesabixLogService::writeLogStr("Error in Exporting Opening Quantity" . $error->getMessage());
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
    public function syncOrders($from_date, $end_date, $batch, $totalBatch, $total, $updateCount)
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

        if (!isset($end_date) || empty($end_date)) {
            $result['error'] = 'inputDateError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($from_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if (!$this->isDateInFiscalYear($end_date)) {
            $result['error'] = 'fiscalYearError';
            return $result;
        }

        if ($batch == 1) {
            if (get_option('woocommerce_custom_orders_table_enabled') === 'yes') {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "wc_orders`
                                   WHERE type = 'shop_order' AND date_created_gmt >= '" . $from_date . "' AND date_created_gmt <= '". $end_date ."'");
            } else {
                $total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts`
                                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "' AND post_date <= '". $end_date ."'");
            }
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;

        if (get_option('woocommerce_custom_orders_table_enabled') === 'yes') {
          $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "wc_orders`
            WHERE type = 'shop_order' AND date_created_gmt >= '" . $from_date . "'
            AND date_created_gmt <= '". $end_date ."'
            ORDER BY ID ASC LIMIT $offset,$rpp");
        } else {
            $orders = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
                WHERE post_type = 'shop_order' AND post_date >= '" . $from_date . "'
                AND post_date <= '". $end_date ."'
                ORDER BY ID ASC LIMIT $offset,$rpp");
        }

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
            } else {
                if (strpos($statusesToSubmitPayment, $current_status) !== false)
                    $this->setOrderPayment($id_order);
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
            $filters = array(array("Property" => "khadamat", "Operator" => "=", "Value" => 0));

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
            //$total = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` WHERE post_type = 'product' AND post_status IN('publish','private')");

            $total = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts}
                    WHERE post_type = 'product' AND post_status IN ('publish', 'private')"
                )
            );
            $totalBatch = ceil($total / $rpp);
        }

        $offset = ($batch - 1) * $rpp;
//        $products = $wpdb->get_results("SELECT ID FROM `" . $wpdb->prefix . "posts`
//                                WHERE post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC LIMIT $offset,$rpp");

        $products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                WHERE post_type = 'product' AND post_status IN ('publish', 'private')
                ORDER BY ID ASC
                LIMIT %d, %d",
                $offset,
                $rpp
            )
        );

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
//                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`
//                                            WHERE ID BETWEEN $offset AND $rpp AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->posts}
                            WHERE ID BETWEEN %d AND %d
                            AND post_type = 'product'
                            AND post_status IN ('publish', 'private')
                            ORDER BY ID ASC",
                            $offset,
                            $rpp
                        )
                    );

                    $products_id_array = array();
                    foreach ($products as $product)
                        $products_id_array[] = $product->ID;
                    $response = (new Ssbhesabix_Admin_Functions)->setItems($products_id_array);
                    if(!$response) $result['error'] = true;
                } else {
//                    $products = $wpdb->get_results("SELECT * FROM `" . $wpdb->prefix . "posts`
//                                            WHERE ID BETWEEN $rpp AND $offset AND post_type = 'product' AND post_status IN('publish','private') ORDER BY 'ID' ASC");

                    $products = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->posts}
                            WHERE ID BETWEEN %d AND %d
                            AND post_type = 'product'
                            AND post_status IN ('publish', 'private')
                            ORDER BY ID ASC",
                            $rpp,
                            $offset
                        )
                    );

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

        //$found = $wpdb->get_var("SELECT COUNT(*) FROM `" . $wpdb->prefix . "posts` WHERE ID = $id_product");

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                WHERE ID = %d",
                $id_product
            )
        );


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

//                update_post_meta($post_id, '_stock', $new_quantity);
//                wc_update_product_stock_status($post_id, $new_stock_status);

                $product = wc_get_product( $post_id );
                if ( $product ) {

                    $product->set_stock_quantity( $new_quantity );
                    $product->set_stock_status( $new_stock_status );
                    $product->save();
                    HesabixLogService::log(array("product ID $id_product-$id_attribute quantity changed. Old quantity: $old_quantity. New quantity: $new_quantity"));
                    $result["newQuantity"] = $new_quantity;
                }
            }

            return $result;
        } catch (Error $error) {
            HesabixLogService::writeLogStr("Error in Set Item New Quantity -> $error");
        }
    }
//=========================================================================================================================
    public static function syncLastChangeID(): bool {
        try {
            HesabixLogService::writeLogStr("Sync Last Change ID");
            $hesabixApi = new Ssbhesabix_Api();
            $lastChange = $hesabixApi->getLastChangeId();

            if ($lastChange && isset($lastChange->LastId)) {
                update_option('ssbhesabix_last_log_check_id', $lastChange->LastId - 1);
                return true;
            }
        } catch (Exception $error) {
            HesabixLogService::writeLogStr("Error in syncing last change id -> " . $error->getMessage());
        }

        return false;
    }
//=========================================================================================================================
    public static function SaveProductManuallyToHesabix($woocommerceCode, $attributeId, $hesabixCode): bool {
        //check no record exist in hesabix
        $isProductExistInHesabix = self::CheckExistenceOfTheProductInHesabix($hesabixCode);
        if(!$isProductExistInHesabix) {
            $isProductValidInWoocommerce = self::CheckValidityOfTheProductInWoocommerce($woocommerceCode, $attributeId, $hesabixCode);
            if($isProductValidInWoocommerce) {
                //get product
                $product = wc_get_product($woocommerceCode);
                if($attributeId != 0) $variation = wc_get_product($attributeId);

                if($attributeId == 0) {
                    $hesabixItem = ssbhesabixItemService::mapProduct($product, $woocommerceCode);
                } else {
                    $hesabixItem = ssbhesabixItemService::mapProductVariation($product, $variation, $woocommerceCode);
                }

                //save product to hesabix and make a new link
                $api = new Ssbhesabix_Api();
                $hesabixItem["Code"] = $hesabixCode;
                $response = $api->itemSave($hesabixItem);
                if($response->Success) {
                    if($attributeId == 0) $productCode = $woocommerceCode; else $productCode = $attributeId;
                    HesabixLogService::log(array("Item successfully added to Hesabix. Hesabix code: " . $hesabixCode . " - Product code: " . $productCode));

                    $wpFaService = new HesabixWpFaService();
                    $wpFa = $wpFaService->getWpFa('product', $woocommerceCode, $attributeId);
                    if (!$wpFa) {
                        $wpFa = new WpFa();
                        $wpFa->idHesabix = $hesabixCode;
                        $wpFa->idWp = $woocommerceCode;
                        $wpFa->idWpAttribute = $attributeId;
                        $wpFa->objType = 'product';
                        $wpFaService->save($wpFa);
                        HesabixLogService::log(array("Item successfully added. Hesabix code: " . (string)$hesabixCode . ". Product ID: $woocommerceCode - $attributeId"));
                        return true;
                    }
                } else {
                    HesabixLogService::log(array("Error in saving product to hesabix. Hesabix given code: " . $hesabixCode));
                    return false;
                }
            }
        }

        return false;
    }
//=========================================================================================================================
    public static function CheckExistenceOfTheProductInHesabix($hesabixCode): bool {
        $api = new Ssbhesabix_Api();
        $response = $api->itemGet($hesabixCode);
        if($response->Success) {
            HesabixLogService::writeLogStr("کالا با کد(" .  $hesabixCode . ") در حسابیکس موجود است.");
            return true;
        } else if($response->ErrorCode == "112") {
            return false;
        } else {
            HesabixLogService::writeLogStr("Error in getting the existence of the product");
            return true;
        }
    }
//=========================================================================================================================
    public static function CheckValidityOfTheProductInWoocommerce($woocommerceCode, $attributeId, $hesabixCode): bool {
        //check not exist in link table
        $wpFaService = new HesabixWpFaService();
        $code = $wpFaService->getProductCodeByWpId($woocommerceCode, $attributeId);
        if ($code) {
            HesabixLogService::writeLogStr("این کد حسابیکسی وارد شده به کالای دیگری متصل است." . $code . " - " . $woocommerceCode . " - " . $attributeId);
            return false;
        }

        //check woocommerce code exists
        global $wpdb;

        if($attributeId != 0) $productId = $attributeId;
        else $productId = $woocommerceCode;

        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                WHERE ID = %d",
                $productId
            )
        );

        if($found) {
            //product is valid
            return true;
        } else {
            HesabixLogService::writeLogStr("product not found in woocommerce. Given product code: " . $woocommerceCode . "-" . $attributeId );
            return false;
        }
    }
//=========================================================================================================================
    function checkNationalCode($NationalCode): void
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
    public function checkNationalCodeWithPhone($nationalCode, $billingPhone): bool {
        $api = new Ssbhesabix_Api();

        $formattedPhoneNumber = $this->convertPersianPhoneDigitsToEnglish($billingPhone);
        $formattedPhoneNumber = $this->formatPhoneNumber($formattedPhoneNumber);

        $response = $api->checkMobileAndNationalCode($nationalCode, $formattedPhoneNumber);
        if($response->Success) {
            if($response->Result->Status == 1) {
                return $response->Result->Data->Matched;
            } else {
                return false;
            }
        } else {
            HesabixLogService::writeLogStr('Error Occurred in Checking Mobile and NationalCode. ErrorCode: ' . $response->ErrorCode . " - ErrorMessage: " . $response->ErrorMessage);
            return false;
        }
    }
//=========================================================================================================================
    function checkWebsite($Website): void
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
    function formatPhoneNumber($phoneNumber) {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (substr($phoneNumber, 0, 2) == '98') {
            $phoneNumber = substr($phoneNumber, 2);
        }

        if (substr($phoneNumber, 0, 1) == '9' && strlen($phoneNumber) == 10) {
            $phoneNumber = '0' . $phoneNumber;
        }

        if (strlen($phoneNumber) == 10 && substr($phoneNumber, 0, 1) == '9') {
            $phoneNumber = '0' . $phoneNumber;
        }

        return $phoneNumber;
    }
//=========================================================================================================================
    public function convertPersianPhoneDigitsToEnglish($inputString) : string {
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
    public function formatTransactionFee($transactionFee, $amount) {
        if($transactionFee && $transactionFee > 0) {
            $func = new Ssbhesabix_Admin_Functions();
            $transactionFee = $func->convertPersianDigitsToEnglish($transactionFee);

            if($transactionFee<100 && $transactionFee>0) $transactionFee /= 100;
            $transactionFee *= $amount;
            if($transactionFee < 1) $transactionFee = 0;
        }
        return $transactionFee;
    }
//=========================================================================================================================
    public function convertCityCodeToName($cityCode) {
        $citiesArray = [
            1 => [
                'title' => 'تهران',
                'cities' => [
                    1 => 'تهران',
                    331 => 'اسلام شهر',
                    1813 => 'ری',
                    3341 => 'لواسان',
                    3351 => 'شهریار',
                    3371 => 'ورامین',
                    3381 => 'پیشوا',
                    3391 => 'پاکدشت',
                    3751 => 'قدس',
                    3761 => 'رباطکریم',
                    3971 => 'دماوند',
                    3981 => 'فیروزکوه',
                    16531 => 'جاجرود (خسروآباد)',
                    16551 => 'بومهن',
                    16571 => 'شهرصنعتی خرمدشت',
                    16581 => 'پردیس',
                    18131 => 'باقر شهر',
                    18141 => 'جعفرابادباقراف',
                    18151 => 'مرقدامام ره',
                    18161 => 'کهریزک',
                    18171 => 'طورقوزاباد',
                    18181 => 'قاسم ابادشوراباد',
                    18191 => 'قمصر',
                    18331 => 'حسن آباد',
                    18341 => 'شمس اباد',
                    18351 => 'ابراهیم اباد',
                    18361 => 'چرمشهر',
                    18371 => 'قلعه محمدعلی خان',
                    18381 => 'فرودگاه امام خمینی',
                    18391 => 'وهن اباد',
                    18441 => 'قلعه نوخالصه',
                    18451 => 'گل تپه کبیر',
                    18461 => 'محمودابادپیرزاده',
                    18471 => 'فرون اباد',
                    18631 => 'خاورشهر',
                    18641 => 'اسلام اباد',
                    18651 => 'لپه زنگ',
                    18661 => 'قیامدشت',
                    18686 => 'قرچک',
                    18791 => 'قوچ حصار',
                    18986 => 'خلازیر',
                    19338 => 'تجریش',
                    31130 => 'نصیرشهر',
                    31133 => 'شهرک صنعتی نصیرشهر',
                    31136 => 'شهرک قلعه میر',
                    31641 => 'صفادشت',
                    31686 => 'اندیشه',
                    31691 => 'ملارد',
                    31694 => 'گرمدره',
                    33131 => 'احمدابادمستوفی',
                    33141 => 'فیروزبهرام',
                    33151 => 'گلدسته',
                    33171 => 'صالح آباد',
                    33186 => 'شاطره',
                    33191 => 'چهاردانگه',
                    33361 => 'سعیدآباد',
                    33451 => 'فشم',
                    33461 => 'لواسان بزرگ',
                    33541 => 'باغستان',
                    33560 => 'صباشهر',
                    33561 => 'شاهدشهر',
                    33571 => 'فردوسیه',
                    33581 => 'وحیدیه',
                    33591 => 'لم اباد',
                    33711 => 'قلعه سین',
                    33741 => 'عسگرابادعباسی',
                    33751 => 'دهماسین',
                    33761 => 'باغخواص',
                    33771 => 'ایجدان',
                    33781 => 'ابباریک',
                    33831 => 'جواد آباد',
                    33841 => 'خاوه',
                    33861 => 'جلیل اباد',
                    33871 => 'کریم اباد',
                    33881 => 'قلعه خواجه',
                    33930 => 'شهرک عباس آباد',
                    33931 => 'داوداباد',
                    33941 => 'شریف آباد',
                    33971 => 'پارچین',
                    33981 => 'حصارامیر',
                    33991 => 'خاتون اباد',
                    37551 => 'نصیرآباد',
                    37571 => 'گلستان',
                    37581 => 'کلمه',
                    37611 => 'پرند',
                    37614 => 'شهر صنعتی پرند',
                    37631 => 'سلطان اباد',
                    37650 => 'حصارک پایین',
                    37651 => 'نسیم شهر',
                    37652 => 'حصارک بالا',
                    37653 => 'سبزدشت',
                    37656 => 'احمدآبادجانسپار',
                    37661 => 'اسماعیل آباد',
                    39720 => 'جابان',
                    39731 => 'رودهن',
                    39741 => 'آبعلی',
                    39751 => 'کیلان',
                    39761 => 'آبسرد',
                    39771 => 'سربندان',
                    39780 => 'مهرآباد',
                    39781 => 'مشا',
                    39791 => 'مرا',
                    39811 => 'هرانده',
                    39831 => 'درده',
                    39841 => 'حصاربن',
                    39851 => 'ارجمند',
                    39861 => 'امیریه',
                ],
            ],
            2 => [
                'title' => 'گيلان',
                'cities' => [
                    41 => 'رشت',
                    431 => 'بندرانزلی',
                    441 => 'لاهیجان',
                    4331 => 'ابکنار',
                    4341 => 'خمام',
                    4351 => 'فومن',
                    4361 => 'صومعه سرا',
                    4371 => 'هشتپر',
                    4381 => 'ماسال',
                    4391 => 'آستارا',
                    4431 => 'سیاهکل',
                    4441 => 'آستانه اشرفیه',
                    4451 => 'منجیل',
                    4461 => 'رودبار',
                    4471 => 'لنگرود',
                    4481 => 'رودسر',
                    4491 => 'کلاچای',
                    43331 => 'کپورچال',
                    43341 => 'جیرهنده',
                    43351 => 'لیچارکی حسن رود',
                    43361 => 'سنگر',
                    43381 => 'سراوان',
                    43391 => 'خشکبیجار',
                    43431 => 'لشت نشاء',
                    43451 => 'خواچکین',
                    43461 => 'کوچصفهان',
                    43471 => 'بلسبنه',
                    43481 => 'چاپارخانه',
                    43491 => 'جیرکویه',
                    43513 => 'ماکلوان',
                    43531 => 'لولمان',
                    43541 => 'شفت',
                    43551 => 'ملاسرا',
                    43561 => 'چوبر',
                    43571 => 'ماسوله',
                    43581 => 'گشت',
                    43591 => 'احمد سر گوراب',
                    43631 => 'مرجقل',
                    43641 => 'گوراب زرمیخ',
                    43651 => 'طاهرگوراب',
                    43661 => 'ضیابر',
                    43671 => 'مرکیه',
                    43681 => 'هنده خاله',
                    43691 => 'نوخاله اکبری',
                    43741 => 'شیله وشت',
                    43751 => 'جوکندان بزرگ',
                    43761 => 'لیسار',
                    43771 => 'بازارخطبه سرا',
                    43780 => 'چوبر',
                    43781 => 'حویق',
                    43791 => 'پلاسی',
                    43811 => 'بازار جمعه',
                    43841 => 'رضوانشهر',
                    43861 => 'پره سر',
                    43871 => 'پلنگ پاره',
                    43891 => 'اسالم',
                    43931 => 'شیخ محله',
                    43941 => 'ویرمونی',
                    43951 => 'سیبلی',
                    43961 => 'لوندویل',
                    43971 => 'مشند',
                    43981 => 'کوته کومه',
                    43991 => 'حیران',
                    44141 => 'رودبنه',
                    44331 => 'پایین محله پاشاکی',
                    44341 => 'گرماور',
                    44351 => 'لیش',
                    44361 => 'بارکوسرا',
                    44371 => 'شیرین نسا',
                    44381 => 'خرارود',
                    44391 => 'دیلمان',
                    44431 => 'لسکوکلایه',
                    44441 => 'کیسم',
                    44451 => 'شیرکوه چهارده',
                    44461 => 'دهشال',
                    44471 => 'کیاشهر',
                    44481 => 'دستک',
                    44491 => 'پرگاپشت مهدی خانی',
                    44531 => 'لوشان',
                    44541 => 'بیورزین',
                    44551 => 'جیرنده',
                    44561 => 'بره سر',
                    44581 => 'ویشان',
                    44591 => 'کلیشم',
                    44631 => 'علی اباد',
                    44641 => 'رستم آباد',
                    44651 => 'توتکابن',
                    44661 => 'کلشتر',
                    44681 => 'اسکولک',
                    44691 => 'کوکنه',
                    44731 => 'سلوش',
                    44741 => 'چاف وچمخاله',
                    44751 => 'شلمان',
                    44761 => 'کومله',
                    44771 => 'دیوشل',
                    44781 => 'پروش پایین',
                    44791 => 'اطاقور',
                    44841 => 'حسن سرا',
                    44851 => 'طول لات',
                    44861 => 'رانکوه',
                    44871 => 'چابکسر',
                    44881 => 'جنگ سرا',
                    44891 => 'واجارگاه',
                    44931 => 'رحیم آباد',
                    44941 => 'بلترک',
                    44951 => 'املش',
                    44971 => 'کجید',
                    44981 => 'گرمابدشت',
                    44991 => 'شوییل',
                    44992 => 'پونل',
                ],
            ],
            3 => [
                'title' => 'آذربايجان شرقي',
                'cities' => [
                    51 => 'تبریز',
                    531 => 'میانه',
                    541 => 'مرند',
                    551 => 'مراغه',
                    5331 => 'شهرجدیدسهند',
                    5351 => 'اسکو',
                    5361 => 'سردرود',
                    5371 => 'آذر شهر',
                    5381 => 'شبستر',
                    5391 => 'هریس',
                    5431 => 'هادیشهر',
                    5441 => 'جلفا',
                    5451 => 'اهر',
                    5461 => 'کلیبر',
                    5471 => 'سراب',
                    5491 => 'بستان آباد',
                    5541 => 'عجب شیر',
                    5551 => 'بناب',
                    5561 => 'ملکان',
                    5571 => 'هشترود',
                    5581 => 'قره آغاج',
                    5586 => 'اغچه ریش',
                    53331 => 'ترک',
                    53351 => 'ترکمانچای',
                    53361 => 'خاتون اباد',
                    53371 => 'شیخدراباد',
                    53381 => 'قره بلاغ',
                    53391 => 'آقکند',
                    53431 => 'اچاچی',
                    53441 => 'گوندوغدی',
                    53451 => 'پورسخلو',
                    53461 => 'کنگاور',
                    53481 => 'قویوجاق',
                    53491 => 'ارموداق',
                    53531 => 'کهنمو',
                    53541 => 'اربط',
                    53551 => 'خسرو شهر',
                    53561 => 'لاهیجان',
                    53571 => 'خاص اباد (خاصبان)',
                    53581 => 'ایلخچی',
                    53591 => 'سرای (سرای ده)',
                    53631 => 'کجوار',
                    53641 => 'خلجان',
                    53651 => '(ینگی اسپران (سفیدان جد',
                    53661 => 'باسمنج',
                    53671 => '(شادبادمشایخ (پینه شلوا',
                    53681 => 'کندرود',
                    53691 => 'مایان سفلی',
                    53731 => 'تیمورلو',
                    53740 => 'خراجو',
                    53741 => 'قدمگاه (بادام یار)',
                    53751 => 'ممقان',
                    53761 => 'گوگان',
                    53771 => 'شیرامین',
                    53791 => 'هفت چشمه',
                    53811 => 'وایقان',
                    53831 => 'امند',
                    53840 => 'کوزه کنان',
                    53841 => 'خامنه',
                    53851 => 'سیس',
                    53861 => 'صوفیان',
                    53871 => 'شند آباد',
                    53881 => 'تسوج',
                    53891 => 'شرفخانه',
                    53941 => 'مینق',
                    53950 => 'کلوانق',
                    53951 => 'بخشایش',
                    53961 => 'سرند',
                    53971 => 'زرنق',
                    53981 => 'بیلوردی',
                    53991 => 'خواجه',
                    54331 => 'گلین قیه',
                    54341 => 'هرزندجدید (چای هرزند)',
                    54351 => 'بناب جدید',
                    54361 => 'زنوز',
                    54371 => 'دولت اباد',
                    54381 => 'یکان کهریز',
                    54391 => 'یامچی',
                    54431 => 'شجاع',
                    54441 => 'داران',
                    54451 => 'سیه رود',
                    54461 => 'نوجه مهر',
                    54471 => 'کشکسرای',
                    54481 => 'خاروانا',
                    54491 => 'هوراند',
                    54531 => 'چول قشلاقی',
                    54541 => 'ورگهان',
                    54551 => 'افیل',
                    54561 => 'اذغان (ازغان)',
                    54571 => 'سیه کلان',
                    54581 => 'ورزقان',
                    54591 => 'اق براز',
                    54631 => 'مولان',
                    54641 => 'خمارلو',
                    54651 => 'عاشقلو',
                    54661 => 'اسکلو (اسگلو)',
                    54671 => 'آبش احمد',
                    54681 => 'یوزبند',
                    54682 => 'شهرک صنعتی کاغذکنان',
                    54685 => 'کندوان',
                    54686 => 'تیل',
                    54691 => 'لاریجان',
                    54731 => 'اسبفروشان',
                    54741 => 'ابرغان',
                    54750 => 'دوزدوزان',
                    54751 => 'شربیان',
                    54761 => 'مهربان',
                    54771 => 'رازلیق',
                    54781 => 'اغمیون',
                    54791 => 'اردها',
                    54931 => 'قره چای حاج علی',
                    54941 => 'قره بابا',
                    54951 => 'سعیداباد',
                    54961 => 'الانق',
                    54971 => 'کردکندی',
                    54981 => 'تیکمه داش',
                    54991 => 'قره چمن',
                    55330 => 'ورجوی',
                    55341 => 'گل تپه',
                    55351 => 'خداجو',
                    55361 => 'داش اتان',
                    55371 => 'داش بلاغ بازار',
                    55381 => 'صومعه',
                    55391 => 'علویان',
                    55431 => 'شیراز',
                    55441 => 'خضرلو',
                    55451 => 'ینگجه',
                    55461 => 'مهماندار',
                    55471 => 'خانیان',
                    55481 => 'دانالو',
                    55491 => 'رحمانلو',
                    55531 => 'زاوشت',
                    55541 => 'القو',
                    55551 => 'روشت بزرگ',
                    55561 => 'خوشه مهر (خواجه امیر)',
                    55571 => 'زوارق',
                    55581 => '(خانه برق قدیم (شورخانه ب',
                    55631 => 'لکلر',
                    55641 => 'بایقوت',
                    55651 => 'اروق',
                    55661 => 'اق منار',
                    55671 => 'لیلان',
                    55681 => 'طوراغای (طوراغایی)',
                    55731 => 'اوشندل',
                    55741 => 'علی ابادعلیا',
                    55751 => 'ذوالبین',
                    55761 => 'نظر کهریزی',
                    55771 => 'اتش بیگ',
                    55781 => 'سلوک',
                    55791 => 'نصیرابادسفلی',
                    55831 => 'ارسگنای سفلی',
                    55841 => '(سلطان اباد (س انمکزار',
                    55851 => 'قلعه حسین اباد',
                    55871 => 'ذاکرکندی',
                    55881 => 'قوچ احمد',
                    55891 => 'اغ زیارت',
                ],
            ],
            4 => [
                'title' => 'خوزستان',
                'cities' => [
                    61 => 'اهواز',
                    631 => 'آبادان',
                    641 => 'خرمشهر',
                    6331 => 'اروندکنار',
                    6341 => 'ملاثانی',
                    6351 => 'بندرماهشهر',
                    6361 => 'بهبهان',
                    6371 => 'آغاجاری',
                    6381 => 'رامهرمز',
                    6391 => 'ایذه',
                    6431 => 'شادگان',
                    6441 => 'سوسنگرد',
                    6451 => 'شوشتر',
                    6461 => 'دزفول',
                    6471 => 'شوش',
                    6481 => 'اندیمشک',
                    6491 => 'مسجدسلیمان',
                    61431 => 'الهائی',
                    61481 => 'شیبان',
                    61491 => 'ویس',
                    63331 => 'فیاضی',
                    63341 => 'تنگ یک',
                    63351 => 'چوئبده',
                    63361 => 'نهرسلیم',
                    63381 => 'نهرابطر',
                    63431 => 'عین دو',
                    63441 => 'حمیدیه',
                    63451 => 'ام الطمیر (سیدیوسف)',
                    63461 => 'کوت عبدالله',
                    63471 => 'قلعه چنعان',
                    63481 => 'کریت برومی',
                    63491 => 'غیزانیه بزرگ',
                    63531 => 'چم کلگه',
                    63541 => 'چمران',
                    63561 => 'بندرامام خمینی',
                    63571 => 'صالح شهر',
                    63581 => 'اسیاب',
                    63591 => 'هندیجان',
                    63640 => 'تشان',
                    63641 => 'گروه پدافندهوایی بهبها',
                    63651 => 'شاه غالب ده ابراهیم',
                    63661 => 'کردستان بزرگ',
                    63671 => 'منصوریه',
                    63681 => 'سردشت',
                    63731 => 'امیدیه',
                    63751 => 'میانکوه',
                    63771 => 'زهره',
                    63831 => 'رودزرد',
                    63851 => 'نفت سفید',
                    63861 => 'مشراگه',
                    63871 => 'رامشیر',
                    63881 => 'جایزان',
                    63891 => 'دره تونم نمی',
                    63931 => 'میداود',
                    63941 => 'صیدون',
                    63951 => 'باغ ملک',
                    63961 => 'قلعه تل',
                    63971 => 'چنارستان',
                    63981 => 'پشت پیان',
                    63991 => 'دهدز',
                    64330 => 'خنافره',
                    64331 => 'عبودی',
                    64341 => 'دارخوین',
                    64351 => 'درویشی',
                    64361 => 'بوزی سیف',
                    64371 => 'مینوشهر',
                    64381 => 'حفاری شرقی',
                    64431 => 'بروایه یوسف',
                    64440 => 'کوت سیدنعیم',
                    64441 => 'ابوحمیظه',
                    64451 => 'هویزه',
                    64461 => 'یزدنو',
                    64471 => 'رفیع',
                    64481 => 'بستان',
                    64491 => 'سیدعباس',
                    64510 => 'سرداران',
                    64511 => 'شرافت',
                    64531 => 'گوریه',
                    64541 => 'جنت مکان',
                    64551 => 'گتوند',
                    64560 => 'ترکالکی',
                    64561 => 'سماله',
                    64571 => 'شهرک نورمحمدی',
                    64581 => 'گاومیش اباد',
                    64591 => 'عرب حسن',
                    64631 => 'صفی آباد',
                    64640 => 'چغامیش',
                    64641 => 'حمزه',
                    64650 => 'شمس آباد',
                    64651 => 'امام',
                    64652 => 'سیاه منصور',
                    64661 => 'میانرود',
                    64681 => 'چلون',
                    64691 => 'سالند',
                    64730 => 'حر',
                    64731 => 'شاوور',
                    64741 => 'مزرعه یک',
                    64751 => 'خسرجی راضی حمد',
                    64761 => 'الوان',
                    64771 => 'علمه تیمورابوذرغفاری',
                    64781 => 'شهرک بهرام',
                    64791 => 'فتح المبین',
                    64830 => 'آزادی',
                    64831 => 'شهرک انصار',
                    64841 => 'خواجوی',
                    64850 => 'بیدروبه',
                    64851 => 'حسینیه',
                    64861 => 'کلگه دره دو',
                    64871 => 'تله زنگ پایین',
                    64881 => 'چم گلک',
                    64931 => 'روستای عنبر',
                    64941 => 'لالی',
                    64951 => 'دره بوری',
                    64961 => 'هفتگل',
                    64971 => 'کوشکک',
                    64980 => 'آبژدان',
                    64981 => 'قلعه خواجه',
                    64991 => 'گلگیر',
                ],
            ],
            5 => [
                'title' => 'فارس',
                'cities' => [
                    71 => 'شیراز',
                    731 => 'کازرون',
                    741 => 'جهرم',
                    7331 => 'قائمیه',
                    7341 => 'زرقان',
                    7351 => 'نور آباد',
                    7361 => 'اردکان',
                    7371 => 'مرودشت',
                    7381 => 'اقلید',
                    7391 => 'آباده',
                    7431 => 'لار',
                    7441 => 'گراش',
                    7451 => 'استهبان',
                    7461 => 'فسا',
                    7471 => 'فیروز آباد',
                    7481 => 'داراب',
                    7491 => 'نی ریز',
                    71431 => 'بندامیر',
                    71451 => 'خیرابادتوللی',
                    71461 => 'داریان',
                    71491 => 'کم جان',
                    71551 => 'شوریجه',
                    71561 => 'مهارلو',
                    71571 => 'کوهنجان',
                    71581 => 'سلطان آباد',
                    71591 => 'تفیهان',
                    71641 => 'طسوج',
                    71651 => 'اکبراباد',
                    71661 => 'مظفری',
                    71671 => 'کوشک بیدک',
                    71681 => 'فتح اباد',
                    71691 => 'ده شیب',
                    71741 => 'خانه زنیان',
                    71781 => 'پاسگاه چنارراهدار',
                    71881 => 'موردراز',
                    71991 => 'شهرجدیدصدرا',
                    73131 => 'کلاتون',
                    73141 => 'کلانی',
                    73151 => 'کمارج مرکزی',
                    73161 => 'مهبودی علیا',
                    73171 => 'وراوی',
                    73311 => 'حکیم باشی نصف میان (بالا)',
                    73331 => 'کنار تخته',
                    73341 => 'خشت',
                    73351 => 'انارستان',
                    73361 => 'نودان',
                    73371 => 'مهرنجان',
                    73381 => 'جره',
                    73391 => 'بالاده',
                    73411 => 'لپوئی',
                    73431 => 'کامفیروز',
                    73441 => 'خرامه',
                    73451 => 'سروستان',
                    73461 => 'کوار',
                    73471 => 'رامجرد',
                    73491 => 'گویم',
                    73511 => 'خومه زار',
                    73531 => 'بابامنیر',
                    73541 => 'اهنگری',
                    73551 => 'پرین',
                    73560 => 'کوپن',
                    73561 => 'حسین ابادرستم',
                    73571 => 'مصیری',
                    73591 => 'میشان سفلی',
                    73611 => 'بهرغان',
                    73631 => 'بیضا',
                    73641 => 'هماشهر',
                    73651 => 'کمهر',
                    73661 => 'راشک علیا',
                    73671 => 'هرایجان',
                    73681 => 'بانش',
                    73711 => 'کوشک',
                    73731 => 'خانیمن',
                    73741 => 'سعادت شهر',
                    73751 => 'قادرآباد',
                    73761 => 'ارسنجان',
                    73771 => 'سیدان',
                    73791 => 'کوشکک',
                    73810 => 'مزایجان',
                    73811 => 'خنجشت',
                    73831 => 'امامزاده اسماعیل',
                    73840 => 'مادرسلیمان',
                    73841 => 'حسن آباد',
                    73851 => 'اسپاس',
                    73861 => 'سده',
                    73881 => 'دژکرد',
                    73891 => 'شهرمیان',
                    73911 => 'بهمن',
                    73931 => 'صغاد',
                    73940 => 'حسامی',
                    73941 => 'بوانات',
                    73942 => 'کره ای',
                    73951 => 'صفاشهر',
                    73981 => 'سورمق',
                    73991 => 'ایزدخواست',
                    74110 => 'دوزه',
                    74161 => 'بندبست',
                    74171 => 'باب انار',
                    74311 => 'فیشور',
                    74331 => 'اوز',
                    74341 => 'لامرد',
                    74351 => 'جویم',
                    74361 => 'بنارویه',
                    74370 => 'خور',
                    74371 => 'لطیفی',
                    74380 => 'عمادده',
                    74381 => 'بیرم',
                    74390 => 'اهل',
                    74391 => 'اشکنان',
                    74410 => 'اسیر',
                    74411 => 'کهنه',
                    74414 => 'خوزی',
                    74431 => 'خنج',
                    74441 => 'علامرودشت',
                    74450 => 'گله دار',
                    74451 => 'مهر',
                    74461 => 'رونیز',
                    74471 => 'بنوان',
                    74481 => 'ایج',
                    74491 => 'درب قلعه',
                    74541 => 'خاوران',
                    74551 => 'قطب آباد',
                    74561 => 'دنیان',
                    74571 => 'سروو',
                    74581 => 'مانیان',
                    74591 => 'به جان',
                    74611 => 'کوشک قاضی',
                    74641 => 'نوبندگان',
                    74650 => 'قره بلاغ',
                    74651 => 'ششده',
                    74661 => 'قاسم ابادسفلی',
                    74671 => 'زاهدشهر',
                    74681 => 'میانده',
                    74691 => 'صحرارود',
                    74711 => 'بایگان',
                    74714 => 'امام شهر',
                    74731 => 'مبارک آباد',
                    74741 => 'میمند',
                    74751 => 'افزر',
                    74760 => 'قیر',
                    74761 => 'کارزین',
                    74771 => 'فراشبند',
                    74780 => 'نوجین',
                    74781 => 'دهرم',
                    74791 => 'جوکان',
                    74811 => 'مادوان',
                    74814 => 'دبیران',
                    74831 => 'ماه سالاری',
                    74841 => 'رستاق',
                    74850 => 'شهرپیر',
                    74861 => 'حاجی آباد',
                    74871 => 'فدامی',
                    74880 => 'دوبرجی',
                    74881 => 'چمن مروارید',
                    74891 => 'جنت شهر',
                    74911 => 'لای حنا',
                    74931 => 'آباده طشک',
                    74941 => 'قطاربنه',
                    74971 => 'مشکان',
                    74981 => 'قطرویه',
                    74991 => 'هرگان',
                ],
            ],
            6 => [
                'title' => 'اصفهان',
                'cities' => [
                    81 => 'اصفهان',
                    831 => 'شاهین شهر',
                    841 => 'خمینی شهر',
                    851 => 'نجف آباد',
                    861 => 'شهرضا',
                    871 => 'کاشان',
                    8161 => 'منطقه صنعتی محموداباد',
                    8331 => 'مورچه خورت',
                    8341 => 'دولت آباد',
                    8351 => 'میمه',
                    8361 => 'خور',
                    8371 => 'کوهپایه',
                    8381 => 'اردستان',
                    8391 => 'نائین',
                    8431 => 'درچه پیاز',
                    8441 => 'زواره',
                    8451 => 'فلاورجان',
                    8461 => 'قهدریجان',
                    8471 => 'زرین شهر',
                    8481 => 'مبارکه',
                    8491 => 'فولادشهر',
                    8531 => 'تیران',
                    8541 => 'دهق',
                    8551 => 'علویجه',
                    8561 => 'داران',
                    8571 => 'چادگان',
                    8591 => 'فریدونشهر',
                    8641 => 'دهاقان',
                    8651 => 'اسفرجان',
                    8661 => 'سمیرم',
                    8671 => 'حنا',
                    8681 => 'مهرگرد',
                    8731 => 'جوشقان استرک',
                    8741 => 'آران و بیدگل',
                    8751 => 'قمصر',
                    8761 => 'نطنز',
                    8771 => 'گلپایگان',
                    8781 => 'گوگد',
                    8791 => 'خوانسار',
                    81351 => 'تودشک',
                    81391 => 'سگزی',
                    81431 => 'بهارستان',
                    81561 => 'خوراسگان',
                    81594 => 'گورت',
                    81671 => 'دستجا',
                    81681 => 'زیار',
                    81751 => 'نصرآباد',
                    81789 => 'ابریشم',
                    81799 => 'اصفهان (سپاهان شهر)',
                    83341 => 'پادگان اموزشی امام ص',
                    83351 => 'پالایشگاه اصفهان',
                    83361 => 'کلهرود',
                    83371 => 'گرگاب',
                    83431 => 'دستگرد',
                    83441 => 'گز برخوار',
                    83451 => 'خورزوق',
                    83461 => 'حبیب آباد',
                    83531 => 'موته',
                    83541 => 'وزوان',
                    83551 => 'لای بید',
                    83561 => 'رباطاقاکمال',
                    83581 => 'خسرواباد',
                    83591 => 'کمشچه',
                    83631 => 'جندق',
                    83641 => 'فرخی',
                    83651 => 'مزیک',
                    83661 => 'مهرجان',
                    83671 => 'بیاضه',
                    83681 => 'چوپانان',
                    83691 => 'بلان',
                    83731 => 'محمدآباد',
                    83741 => 'هرند',
                    83751 => 'ورزنه',
                    83761 => 'قهجاورستان',
                    83771 => 'نیک آباد',
                    83781 => 'اژیه',
                    83791 => 'حسن اباد',
                    83831 => 'کچومثقال',
                    83841 => 'ظفرقند',
                    83851 => 'نهوج',
                    83861 => 'نیسیان',
                    83871 => 'ومکان',
                    83881 => 'همسار',
                    83891 => 'فسخود',
                    83931 => 'فوداز',
                    83941 => 'اشکستان',
                    83951 => 'کجان',
                    83961 => 'نیستانک',
                    83971 => 'انارک',
                    83991 => 'بافران',
                    84331 => 'تیرانچی',
                    84341 => 'کوشک',
                    84371 => 'قلعه امیریه',
                    84431 => 'مهاباد',
                    84441 => 'درقه',
                    84451 => 'شهراب',
                    84461 => 'تورزن',
                    84471 => 'کریم اباد',
                    84481 => 'تلک اباد',
                    84491 => 'موغار',
                    84531 => 'خوانسارک',
                    84541 => 'پیربکران',
                    84561 => 'کلیشادوسودرجان',
                    84581 => 'کرسگان',
                    84591 => 'بهاران شهر',
                    84631 => 'سهروفیروزان',
                    84651 => 'ایمانشهر',
                    84671 => 'زازران',
                    84681 => 'شرودان',
                    84691 => 'جوجیل',
                    84731 => 'ورنامخواست',
                    84741 => 'سده لنجان',
                    84751 => 'چرمهین',
                    84761 => 'باغ بهادران',
                    84771 => 'نوگوران',
                    84781 => 'چمگردان',
                    84791 => 'کرچگان',
                    84831 => 'دیزیچه',
                    84841 => 'زیباشهر',
                    84851 => 'باغ ملک',
                    84861 => 'دهسرخ',
                    84871 => 'پلی اکریل',
                    84881 => 'فولادمبارکه',
                    84891 => 'کرکوند',
                    84931 => 'زاینده رود',
                    84951 => 'چم نور',
                    84961 => 'کچوییه',
                    84971 => 'اشیان',
                    84981 => 'طالخونچه',
                    84991 => 'تاسیسات سدنکواباد',
                    85331 => 'رضوانشهر',
                    85341 => 'ورپشت',
                    85351 => 'عسگران',
                    85371 => 'عزیزاباد',
                    85381 => 'میراباد',
                    85391 => 'حاجی اباد',
                    85441 => 'خیراباد',
                    85451 => 'اشن',
                    85471 => 'خونداب',
                    85531 => 'حسین اباد',
                    85631 => 'غرغن',
                    85641 => 'دامنه',
                    85651 => 'بوئین و میاندشت',
                    85661 => 'زرنه',
                    85671 => 'بلطاق',
                    85681 => 'کرچ',
                    85691 => 'قره بلطاق',
                    85711 => 'افوس',
                    85731 => 'سازمان عمران زاینده رود',
                    85741 => 'مشهدکاوه',
                    85751 => 'اسکندری',
                    85761 => 'رزوه',
                    85771 => 'نهرخلج',
                    85781 => 'چاه غلامرضارحیمی',
                    85791 => 'اورگان',
                    85831 => 'گلدشت',
                    85851 => 'جوزدان',
                    85861 => 'کهریزسنگ',
                    85931 => 'نهضت اباد',
                    85941 => 'قلعه سرخ',
                    85961 => 'اسلام ابادموگویی',
                    85971 => 'مصیر',
                    85991 => 'برف انبار',
                    86331 => 'قمشلو',
                    86341 => 'پوده',
                    86351 => 'مهیار',
                    86361 => 'پرزان',
                    86371 => 'منوچهراباد',
                    86391 => 'شهرک صنایع شیمیایی ر',
                    86431 => 'همگین',
                    86451 => 'گلشن',
                    86461 => 'کهرویه',
                    86471 => 'قصرچم',
                    86531 => 'امین اباد',
                    86541 => 'مقصودبیک',
                    86551 => 'سولار',
                    86561 => 'منظریه',
                    86631 => 'گرموک',
                    86651 => 'هست',
                    86671 => 'ونک',
                    86751 => 'کهنگان',
                    86771 => 'کمه',
                    86781 => 'مورک',
                    86791 => 'چهارراه',
                    86831 => 'ده نسا سفلی',
                    86841 => 'اغداش',
                    86851 => 'چشمه رحمان',
                    86861 => 'ورق',
                    86881 => 'سعادت اباد',
                    86891 => 'فتح اباد',
                    87181 => 'نیاسر',
                    87331 => 'سن سن',
                    87351 => 'ده زیره',
                    87361 => 'رحق',
                    87371 => 'اب شیرین',
                    87381 => 'نشلج',
                    87391 => 'مشکات',
                    87431 => 'سفیدشهر',
                    87441 => 'مزرعه صدر',
                    87461 => 'نوش آباد',
                    87481 => 'ابوزیدآباد',
                    87491 => 'کاغذی',
                    87541 => 'قهرود',
                    87551 => 'جوشقان و کامو',
                    87561 => 'برزک',
                    87571 => 'اسحق اباد',
                    87581 => 'وادقان',
                    87591 => 'اذان',
                    87631 => 'طرق رود',
                    87641 => 'اریسمان',
                    87651 => 'ابیانه',
                    87661 => 'بادرود',
                    87671 => 'خالدآ باد',
                    87681 => 'اوره',
                    87731 => 'ملازجان',
                    87741 => 'سعیداباد',
                    87751 => 'مرغ',
                    87771 => 'قرغن',
                    87781 => 'کوچری',
                    87831 => 'کلوچان',
                    87841 => 'گلشهر',
                    87861 => 'زرنجان',
                    87871 => 'وانشان',
                    87881 => 'تیکن',
                    87931 => 'سنگ سفید',
                    87941 => 'رحمت اباد',
                    87951 => 'خم پیچ',
                    87961 => 'مهراباد',
                    87971 => 'تیدجان',
                    87981 => 'خشکرود',
                    87991 => 'ویست',
                ],
            ],
            7 => [
                'title' => 'خراسان رضوي',
                'cities' => [
                    91 => 'مشهد',
                    931 => 'نیشابور',
                    951 => 'تربت حیدریه',
                    961 => 'سبزوار',
                    9331 => 'فیروزه',
                    9341 => 'درود',
                    9351 => 'طرقبه',
                    9361 => 'چناران',
                    9371 => 'کلات',
                    9381 => 'سرخس',
                    9391 => 'فریمان',
                    9471 => 'قوچان',
                    9491 => 'درگز',
                    9531 => 'فیض آباد',
                    9541 => 'رشتخوار',
                    9551 => 'کدکن',
                    9561 => 'خواف',
                    9571 => 'تربت جام',
                    9581 => 'صالح آباد',
                    9591 => 'تایباد',
                    9631 => 'داورزن',
                    9641 => 'جغتای',
                    9651 => 'ششتمد',
                    9671 => 'کاشمر',
                    9681 => 'بردسکن',
                    9691 => 'گناباد',
                    91671 => 'رضویه',
                    93161 => 'همت آباد',
                    93331 => 'شوراب',
                    93341 => 'گلبوی پایین',
                    93351 => 'مبارکه',
                    93361 => 'چکنه',
                    93371 => 'برزنون',
                    93381 => 'فدیشه',
                    93391 => 'بار',
                    93431 => 'میراباد',
                    93441 => 'فرخک',
                    93451 => 'خرو',
                    93461 => 'قدمگاه',
                    93471 => 'اسحق اباد',
                    93481 => 'خوجان',
                    93491 => 'عشق آباد',
                    93541 => 'ملک آباد',
                    93551 => 'کورده',
                    93561 => 'شاندیز',
                    93571 => 'طوس سفلی',
                    93581 => 'قرقی سفلی (شهیدکاوه)',
                    93591 => 'کنه بیست',
                    93631 => 'رادکان',
                    93641 => 'سیداباد',
                    93651 => 'گلبهار',
                    93661 => 'سلوگرد',
                    93671 => 'ارداک',
                    93681 => 'بقمج',
                    93691 => 'گلمکان',
                    93741 => 'میامی',
                    93751 => 'چاهک',
                    93761 => 'شهرزو',
                    93771 => 'گوش',
                    93781 => 'نریمانی سفلی',
                    93791 => 'تقی اباد',
                    93831 => 'کچولی',
                    93841 => 'شیرتپه',
                    93851 => 'پس کمر',
                    93861 => 'مزدآوند',
                    93871 => 'بزنگان',
                    93881 => 'گنبدلی',
                    93891 => 'کندک لی',
                    93931 => 'کته شمشیرسفلی',
                ],
            ],
            8 => [
                'title' => 'قزوين',
                'cities' => [
                    341 => 'قزوین',
                    3431 => 'الوند',
                    3441 => 'آبیک',
                    3451 => 'بوئین زهرا',
                    3461 => 'آوج',
                    3481 => 'تاکستان',
                    3491 => 'محمدیه',
                    34131 => 'محمودآبادنمونه',
                    34151 => 'بیدستان',
                    34161 => 'شریفیه',
                    34171 => 'اقبالیه',
                    34313 => 'نصرت آباد',
                    34331 => 'الولک',
                    34341 => 'کاکوهستان',
                    34351 => 'فلار',
                    34381 => 'مینودشت',
                    34391 => 'زوارک',
                    34431 => 'صمغ اباد',
                    34441 => 'ناصراباد',
                    34461 => 'رشتقون',
                    34471 => 'قشلاق',
                    34481 => 'خاکعلی',
                    34491 => 'شهرک صنعتی لیا (قدیم)',
                    34531 => 'سگز آباد',
                    34541 => 'عصمت اباد',
                    34551 => 'خرم اباد',
                    34561 => 'اسفرورین',
                    34571 => 'شال',
                    34581 => 'دانسفهان',
                    34631 => 'کلنجین',
                    34641 => 'آبگرم',
                    34651 => 'استبلخ',
                    34671 => 'ارداق',
                    34681 => 'نیارج',
                    34691 => 'حصارولیعصر',
                    34731 => 'ماهین',
                    34741 => 'سیردان',
                    34761 => 'سیاهپوش',
                    34781 => 'نیارک',
                    34791 => 'اقابابا',
                    34811 => 'نرجه',
                    34831 => 'خرمدشت',
                    34851 => 'ضیاءآباد',
                    34871 => 'حسین اباد',
                    34891 => 'رحیم اباد',
                    34913 => 'مهرگان',
                    34931 => 'معلم کلایه',
                    34941 => 'یحیی اباد',
                    34951 => 'نیکویه',
                    34961 => 'رازمیان',
                    34971 => 'کوهین',
                ],
            ],
            9 => [
                'title' => 'سمنان',
                'cities' => [
                    351 => 'سمنان',
                    361 => 'شاهرود',
                    3531 => 'علا',
                    3541 => 'ابخوری',
                    3551 => 'سرخه',
                    3561 => 'مهدیشهر',
                    3571 => 'شهمیرزاد',
                    3581 => 'گرمسار',
                    3591 => 'ایوانکی',
                    3631 => 'میامی',
                    3641 => 'بسطام',
                    3651 => 'مجن',
                    3661 => 'بیارجمند',
                    3671 => 'دامغان',
                    3681 => 'امیریه',
                    35331 => 'خیراباد',
                    35341 => 'ایستگاه میان دره',
                    35381 => 'اهوان',
                    35431 => 'جام',
                    35441 => 'دوزهیر',
                    35451 => 'معدن نمک',
                    35531 => 'نظامی',
                    35541 => 'اسداباد',
                    35551 => 'لاسجرد',
                    35561 => 'سیداباد',
                    35571 => 'عبدالله ابادپایین',
                    35581 => 'بیابانک',
                    35591 => 'مومن اباد',
                    35631 => 'درجزین',
                    35641 => 'دربند',
                    35651 => 'گل رودبار',
                    35661 => 'ابگرم',
                    35671 => 'افتر',
                    35731 => 'فولادمحله',
                    35741 => 'ده صوفیان',
                    35751 => 'هیکو',
                    35761 => 'چاشم',
                    35831 => 'کردوان',
                    35841 => 'مندولک',
                    35851 => 'داوراباد',
                    35861 => 'آرادان',
                    35881 => 'بن کوه',
                    35891 => 'کهن آباد',
                    35931 => 'حسین ابادکوروس',
                    35941 => 'کرک',
                    35951 => 'گلستانک',
                    35961 => 'لجران',
                    36331 => 'جودانه',
                    36341 => 'ابراهیم اباد',
                    36351 => 'بکران',
                    36361 => 'کرداباد',
                    36371 => 'نردین',
                    36381 => 'سوداغلان',
                    36391 => 'فرومد',
                    36431 => 'ابرسیج',
                    36441 => 'میغان',
                    36451 => 'قلعه نوخرقان',
                    36461 => 'چهلدخترپادگان',
                    36471 => 'کلاته خیج',
                    36531 => 'نگارمن',
                    36541 => 'دهملا',
                    36551 => 'رویان',
                    36561 => 'بدشت',
                    36571 => 'سطوه',
                    36581 => 'طرود',
                    36591 => 'مغان',
                    36631 => 'گیور',
                    36641 => 'دستجرد',
                    36651 => 'مسیح اباد',
                    36661 => 'احمداباد',
                    36671 => 'زمان اباد',
                    36681 => 'سلمرود',
                    36731 => 'جزن',
                    36741 => 'برم',
                    36751 => 'محمداباد',
                    36761 => 'معصوم اباد',
                    36771 => 'فرات',
                    36781 => 'علیان',
                    36791 => 'عمروان',
                    36831 => 'قوشه',
                    36841 => 'دروار',
                    36851 => 'استانه',
                    36861 => 'دیباج',
                    36871 => 'طرزه',
                    36881 => 'مهماندوست',
                    36891 => 'کلاته ملا',
                    36931 => 'قدرت اباد',
                ],
            ],
            10 => [
                'title' => 'قم',
                'cities' => [
                    371 => 'قم',
                    3731 => 'قنوات',
                    3741 => 'دستجرد',
                    37331 => 'امیرابادگنجی',
                    37341 => 'قمرود',
                    37351 => 'کهک',
                    37361 => 'قلعه چم',
                    37431 => 'قاهان',
                    37441 => 'جعفریه',
                    37451 => 'جنداب',
                    37461 => 'سلفچگان',
                ],
            ],
            11 => [
                'title' => 'مركزي',
                'cities' => [
                    381 => 'اراک',
                    391 => 'ساوه',
                    3771 => 'پرندک',
                    3781 => 'محلات',
                    3791 => 'دلیجان',
                    3831 => 'کرهرود',
                    3841 => 'خنداب',
                    3851 => 'کمیجان',
                    3861 => 'شازند',
                    3871 => 'آستانه',
                    3881 => 'خمین',
                    3891 => 'رباطمراد',
                    3931 => 'غرق آباد',
                    3941 => 'مامونیه',
                    3951 => 'تفرش',
                    3961 => 'آشتیان',
                    3991 => 'شهرجدیدمهاجران',
                    37731 => 'سلطان اباد',
                    37741 => 'اصفهانک',
                    37751 => 'حسین اباد',
                    37761 => 'خشکرود',
                    37771 => 'حکیم اباد',
                    37781 => 'یحیی اباد',
                    37791 => 'صدراباد',
                    37841 => 'نیمور',
                    37851 => 'نخجیروان',
                    37861 => 'باقراباد',
                    37871 => 'بزیجان',
                    37881 => 'عیسی اباد',
                    37891 => 'خورهه',
                    37961 => 'نراق',
                    38341 => 'ساروق',
                    38351 => 'داودآباد',
                    38361 => 'کارچان',
                    38451 => 'جاورسیان',
                    38461 => 'ادشته',
                    38471 => 'استوه',
                    38481 => 'سنجان',
                    38491 => 'اناج',
                    38531 => 'وفس',
                    38541 => 'خسروبیگ',
                    38551 => 'میلاجرد',
                    38561 => 'سمقاور',
                    38571 => 'هزاوه',
                    38631 => 'قدمگاه',
                    38641 => 'هفته',
                    38651 => 'لنجرود',
                    38661 => 'توره',
                    38671 => 'کزاز',
                    38681 => 'کتیران بالا',
                    38691 => 'نهرمیان',
                    38731 => 'سرسختی بالا',
                    38741 => 'لوزدرعلیا',
                    38761 => 'هندودر',
                    38771 => 'تواندشت علیا',
                    38781 => 'مالمیر',
                    38791 => 'چهارچریک',
                    38841 => 'چهارچشمه',
                    38851 => 'لکان',
                    38861 => 'قورچی باشی',
                    38871 => 'ورچه',
                    38881 => 'فرفهان',
                    38891 => 'امامزاده ورچه',
                    38931 => 'رباطکفسان',
                    38941 => 'ریحان علیا',
                    38951 => 'جزنق',
                    38961 => 'خوراوند',
                    38971 => 'میشیجان علیا',
                    38981 => 'گلدشت',
                    38991 => 'دهنو',
                    39331 => 'نوبران',
                    39351 => 'یل اباد',
                    39361 => 'رازقان',
                    39371 => 'الویر',
                    39381 => 'دوزج',
                    39391 => 'علیشار',
                    39431 => 'بالقلو',
                    39441 => 'زاویه',
                    39451 => 'چمران',
                    39461 => 'قاقان',
                    39471 => 'سامان',
                    39481 => 'دخان',
                    39491 => 'مراغه',
                    39531 => 'فرمهین',
                    39541 => 'شهراب',
                    39551 => 'زاغر',
                    39561 => 'کهک',
                    39571 => 'فشک',
                    39581 => 'اهنگران',
                    39631 => 'مزرعه نو',
                    39641 => 'صالح اباد',
                    39651 => 'سیاوشان',
                    39661 => 'اهو',
                ],
            ],
            12 => [
                'title' => 'زنجان',
                'cities' => [
                    451 => 'زنجان',
                    4531 => 'زرین آباد',
                    4541 => 'ماهنشان',
                    4551 => 'سلطانیه',
                    4561 => 'ابهر',
                    4571 => 'خرمدره',
                    4581 => 'قیدار',
                    4591 => 'آب بر',
                    45331 => 'همایون',
                    45341 => 'بوغداکندی',
                    45351 => 'اژدهاتو',
                    45371 => 'اسفجین',
                    45381 => 'ارمغانخانه',
                    45391 => 'قبله بلاغی',
                    45431 => 'پری',
                    45441 => 'اندابادعلیا',
                    45451 => 'قره گل',
                    45461 => 'نیک پی',
                    45471 => 'دندی',
                    45481 => 'سونتو',
                    45491 => 'قلتوق',
                    45531 => 'گوزلدره',
                    45551 => 'سنبل اباد',
                    45641 => 'درسجین',
                    45651 => 'دولت اباد',
                    45661 => 'کینه ورس',
                    45731 => 'هیدج',
                    45741 => 'صائین قلعه',
                    45781 => 'اقبلاغ سفلی',
                    45791 => 'سهرورد',
                    45831 => 'کرسف',
                    45841 => 'سجاس',
                    45851 => 'محموداباد',
                    45861 => 'باش قشلاق',
                    45871 => 'گرماب',
                    45881 => 'زرین رود',
                    45891 => 'کهلا',
                    45931 => 'گیلوان',
                    45941 => 'دستجرده',
                    45951 => 'سعیداباد',
                    45961 => 'چورزق',
                    45971 => 'حلب',
                    45981 => 'درام',
                ],
            ],
            13 => [
                'title' => 'مازندران',
                'cities' => [
                    461 => 'آمل',
                    471 => 'بابل',
                    481 => 'ساری',
                    4631 => 'محمودآباد',
                    4641 => 'نور',
                    4651 => 'نوشهر',
                    4661 => 'چالوس',
                    4671 => 'سلمانشهر',
                    4681 => 'تنکابن',
                    4691 => 'رامسر',
                    4731 => 'امیرکلا',
                    4741 => 'بابلسر',
                    4751 => 'فریدونکنار',
                    4761 => 'قائم شهر',
                    4771 => 'جویبار',
                    4781 => 'زیر آب',
                    4791 => 'پل سفید',
                    4831 => 'کیاسر',
                    4841 => 'نکا',
                    4851 => 'بهشهر',
                    4861 => 'گلوگاه',
                    46181 => 'دابودشت',
                    46331 => 'معلم کلا',
                    46341 => 'سرخرود',
                    46351 => 'وسطی کلا',
                    46361 => 'رینه',
                    46371 => 'سوا',
                    46381 => 'باییجان',
                    46391 => 'گزنک',
                    46411 => 'ایزدشهر',
                    46431 => 'چمستان',
                    46441 => 'بنفشه ده',
                    46451 => 'رییس کلا',
                    46461 => 'اوز',
                    46471 => 'بلده',
                    46481 => 'تاکر',
                    46491 => 'گلندرود',
                    46531 => 'چلندر',
                    46541 => 'صلاح الدین کلا',
                    46551 => 'نارنج بن',
                    46561 => 'رویان',
                    46571 => 'کجور',
                    46581 => 'پول',
                    46591 => 'لشکنار',
                    46631 => 'هیچرود',
                    46641 => 'مرزن آباد',
                    46651 => 'کردیچال',
                    46661 => 'کلاردشت',
                    46671 => 'کلنو',
                    46681 => 'دلیر',
                    46691 => 'سیاه بیشه',
                    46731 => 'کلارآباد',
                    46741 => 'عباس آباد',
                    46751 => 'سرلنگا',
                    46761 => 'کترا',
                    46771 => 'گلعلی اباد',
                    46781 => 'میان کوه سادات',
                    46791 => 'مران سه هزار',
                    46831 => 'نشتارود',
                    46841 => 'قلعه گردن',
                    46851 => 'خرم آباد',
                    46861 => 'شیرود',
                    46871 => 'سلیمان اباد',
                    46881 => 'کشکو',
                    46891 => 'لاک تراشان',
                    46931 => 'سادات محله',
                    46941 => 'کتالم وسادات شهر',
                    46961 => 'اغوزکتی',
                    46971 => 'جواهرده',
                    46981 => 'جنت رودبار',
                    46991 => 'تمل',
                    47331 => 'خوشرودپی',
                    47341 => 'اهنگرکلا',
                    47351 => 'گاوانکلا',
                    47381 => 'شورکش',
                    47391 => 'اینج دان',
                    47431 => 'عرب خیل',
                    47441 => 'بهنمیر',
                    47451 => 'کاسگرمحله',
                    47461 => 'کله بست',
                    47471 => 'بیشه سر',
                    47491 => 'گتاب',
                    47541 => 'درازکش',
                    47551 => 'گردرودبار',
                    47561 => 'مرزی کلا',
                    47571 => 'شهیداباد',
                    47581 => 'زرگرمحله',
                    47631 => 'بالاجنیدلاک پل',
                    47641 => 'خطیرکلا',
                    47651 => 'حاجی کلاصنم',
                    47661 => 'واسکس',
                    47681 => 'ریکنده',
                    47691 => 'ارطه',
                    47731 => 'کیاکلا',
                    47741 => 'بالادسته رکن کنار',
                    47751 => 'بیزکی',
                    47761 => 'کوهی خیل',
                    47781 => 'سنگتاب',
                    47791 => 'رکابدارکلا',
                    47831 => 'شیرکلا',
                    47841 => 'آلاشت',
                    47851 => 'لفور (لفورک)',
                    47861 => 'اتو',
                    47871 => 'شیرگاه',
                    47881 => 'پالند',
                    47891 => 'چرات',
                    47931 => 'ده میان',
                    47941 => 'خشک دره',
                    47951 => 'امافت',
                    47961 => 'بالادواب',
                    47971 => 'ورسک',
                    47981 => 'کتی لته',
                    48331 => 'اروست',
                    48341 => 'فریم',
                    48351 => 'سنگده',
                    48361 => 'قادیکلا',
                    48371 => 'تاکام',
                    48390 => 'پایین هولار',
                    48391 => 'بالاهولار',
                    48431 => 'اسبوکلا',
                    48441 => 'سورک',
                    48451 => 'اسلام اباد',
                    48461 => 'شهرک صنعتی گهرباران',
                    48471 => 'فرح اباد (خزراباد)',
                    48481 => 'دارابکلا',
                    48491 => 'ماچک پشت',
                    48531 => 'خورشید (امامیه)',
                    48541 => 'زاغمرز',
                    48551 => 'چلمردی',
                    48561 => 'رستم کلا',
                    48571 => 'پایین زرندین',
                    48591 => 'بادابسر',
                    48631 => 'تیرتاش',
                    48641 => 'خلیل شهر',
                    48661 => 'دامداری حسن ابوطالبی',
                    48671 => 'بیشه بنه',
                    48681 => 'سفیدچاه',
                    48691 => 'دامداری حاج عزیزمجریان',
                    48841 => 'میان دره',
                    48872 => 'بندپی',
                ],
            ],
            14 => [
                'title' => 'گلستان',
                'cities' => [
                    491 => 'گرگان',
                    4871 => 'بندر گز',
                    4881 => 'کردکوی',
                    4891 => 'بندرترکمن',
                    4931 => 'آق قلا',
                    4941 => 'علی آباد',
                    4951 => 'رامیان',
                    4961 => 'آزاد شهر',
                    4971 => 'گنبد کاووس',
                    4981 => 'مینو دشت',
                    4991 => 'کلاله',
                    48731 => 'نوکنده',
                    48733 => 'مراوه تپه',
                    48961 => 'گمیش تپه',
                    48971 => 'سیمین شهر',
                    49351 => 'جلین',
                    49361 => 'سرخنکلاته',
                    49371 => 'تقی اباد',
                    49391 => 'انبار آلوم',
                    49431 => 'فاضل آباد',
                    49471 => 'حاجیکلاته',
                    49531 => 'خان ببین',
                    49541 => 'دلند',
                    49631 => 'نگین شهر',
                    49641 => 'نوده خاندوز',
                    49680 => 'تاتارعلیا',
                    49751 => 'اینچه برون',
                    49791 => 'کرند',
                    49831 => 'گالیکش',
                    49981 => 'عزیزاباد',
                ],
            ],
            15 => [
                'title' => 'اردبيل',
                'cities' => [
                    561 => 'اردبیل',
                    5631 => 'نمین',
                    5641 => 'نیر',
                    5651 => 'گرمی',
                    5661 => 'مشگین شهر',
                    5671 => 'بیله سوار',
                    5681 => 'خلخال',
                    5691 => 'پارس آباد',
                    56331 => 'آبی بیگلو',
                    56341 => 'ننه کران',
                    56351 => 'عنبران',
                    56361 => 'گرده',
                    56371 => 'ثمرین',
                    56381 => 'اردیموسی',
                    56391 => 'سرعین',
                    56431 => 'کورائیم',
                    56441 => 'اسلام آباد',
                    56451 => 'مهماندوست علیا',
                    56461 => 'هیر',
                    56471 => 'بقراباد',
                    56481 => 'بودالالو',
                    56491 => 'اراللوی بزرگ',
                    56531 => 'دیزج',
                    56541 => 'حمزه خانلو',
                    56551 => 'زهرا',
                    56561 => 'انی علیا',
                    56571 => 'قاسم کندی',
                    56581 => 'تازه کندانگوت',
                    56591 => 'قره اغاج پایین',
                    56631 => 'پریخان',
                    56641 => 'قصابه',
                    56651 => 'فخرآباد',
                    56653 => 'لاهرود',
                    56661 => 'رضی',
                    56671 => 'قوشه سفلی',
                    56681 => 'مرادلو',
                    56691 => 'گنجوبه',
                    56731 => 'گوگ تپه',
                    56741 => 'انجیرلو',
                    56751 => 'جعفر آباد',
                    56761 => 'قشلاق اغداش کلام',
                    56771 => 'خورخورسفلی',
                    56781 => 'شورگل',
                    56791 => 'نظرعلی بلاغی',
                    56831 => 'لنبر',
                    56841 => 'فیروزاباد',
                    56851 => 'گیوی',
                    56861 => 'خلفلو',
                    56871 => 'هشتجین',
                    56881 => 'برندق',
                    56891 => 'کلور',
                    56931 => 'تازه کندجدید',
                    56941 => 'گوشلو',
                    56961 => 'اق قباق علیا',
                    56971 => 'شهرک غفاری',
                    56981 => 'اصلاندوز',
                    56991 => 'بران علیا',
                ],
            ],
            16 => [
                'title' => 'آذربايجان غربي',
                'cities' => [
                    571 => 'ارومیه',
                    573 => 'سیلوه',
                    581 => 'خوی',
                    591 => 'مهاباد',
                    5751 => 'قوشچی',
                    5761 => 'نقده',
                    5771 => 'اشنویه',
                    5781 => 'پیرانشهر',
                    5791 => 'جلدیان',
                    5831 => 'ایواوغلی',
                    5837 => 'دیزج دیز',
                    5841 => 'فیرورق',
                    5861 => 'ماکو',
                    5881 => 'سلماس',
                    5891 => 'تازه شهر',
                    5931 => 'گوگ تپه',
                    5951 => 'بوکان',
                    5961 => 'سردشت',
                    5971 => 'میاندوآب',
                    5981 => 'شاهیندژ',
                    5991 => 'تکاب',
                    57331 => 'باراندوز',
                    57341 => 'دیزج دول',
                    57351 => 'میاوق',
                    57361 => 'ایبلو',
                    57371 => 'دستجرد',
                    57381 => 'نوشین',
                    57391 => 'طلاتپه',
                    57411 => 'سیلوانه',
                    57431 => 'راژان',
                    57441 => 'هاشم اباد',
                    57451 => 'دیزج',
                    57461 => 'زیوه',
                    57471 => 'تویی',
                    57481 => 'موانا',
                    57531 => 'قره باغ',
                    57541 => 'بهله',
                    57551 => 'امام کندی',
                    57561 => 'نازلو',
                    57571 => 'سرو',
                    57581 => 'کانسپی',
                    57591 => 'ممکان',
                    57641 => 'حسنلو',
                    57651 => 'کهریزعجم',
                    57661 => 'محمدیار',
                    57671 => 'شیخ احمد',
                    57681 => 'بیگم قلعه',
                    57691 => 'راهدانه',
                    57731 => 'شاهوانه',
                    57741 => 'نالوس',
                    57751 => 'ده شمس بزرگ',
                    57761 => 'گلاز',
                    57771 => 'لولکان',
                    57781 => 'سیاوان',
                    57831 => 'کله کین',
                    57841 => 'شین اباد',
                    57851 => 'چیانه',
                    57861 => 'بیکوس',
                    57871 => 'هنگ اباد',
                    57941 => 'گردکشانه',
                    57951 => 'پسوه',
                    57961 => 'ریگ اباد',
                    57971 => 'احمدغریب',
                    58331 => 'سیه باز',
                    58341 => 'بیله وار',
                    58361 => 'ولدیان',
                    58381 => 'قوروق',
                    58391 => 'هندوان',
                    58431 => 'بدلان',
                    58441 => 'بلسورسفلی',
                    58450 => 'زرآباد',
                    58471 => 'استران',
                    58481 => 'قطور',
                    58516 => 'قره ضیاءالدین',
                    58531 => 'شیرین بلاغ',
                    58541 => 'مراکان',
                    58551 => 'چورس',
                    58561 => 'قورول علیا',
                    58571 => 'بسطام',
                    58631 => 'قره تپه',
                    58641 => 'ریحانلوی علیا',
                    58651 => 'زاویه سفلی',
                    58661 => 'آواجیق',
                    58671 => 'بازرگان',
                    58681 => 'قم قشلاق',
                    58691 => 'یولاگلدی',
                    58716 => 'سیه چشمه',
                    58731 => 'قرنقو',
                    58751 => 'شوط',
                    58761 => 'مرگنلر',
                    58771 => 'پلدشت',
                    58781 => 'نازک علیا',
                    58791 => 'حسن کندی',
                    58831 => 'وردان',
                    58861 => 'قره قشلاق',
                    58871 => 'تمر',
                    58881 => 'ابگرم',
                    58891 => 'سرنق',
                    58931 => 'چهریق علیا',
                    58941 => 'داراب',
                    58951 => 'دلزی',
                    58961 => 'اغ برزه',
                    58971 => 'سنجی',
                    59341 => 'خاتون باغ',
                    59351 => 'حاجی حسن',
                    59361 => 'سوگلی تپه',
                    59371 => 'گلیجه',
                    59381 => 'حاجی کند',
                    59431 => 'باغچه',
                    59441 => 'خورخوره',
                    59450 => 'خلیفان',
                    59451 => 'کاولان علیا',
                    59461 => 'سیاقول علیا',
                    59471 => 'اگریقاش',
                    59481 => 'اوزون دره علیا',
                    59531 => 'یکشوه',
                    59541 => 'جوانمرد',
                    59551 => 'اختتر',
                    59561 => 'سیمینه',
                    59571 => 'رحیم خان',
                    59581 => 'گل تپه قورمیش',
                    59631 => 'شلماش',
                    59641 => 'اسلام اباد',
                    59651 => 'بیوران سفلی',
                    59671 => 'میرآباد',
                    59681 => 'زمزیران',
                    59691 => 'ربط',
                    59730 => 'کشاورز',
                    59731 => 'اقبال',
                    59741 => 'ملاشهاب الدین',
                    59751 => 'للکلو',
                    59761 => 'بگتاش',
                    59771 => 'چهار برج',
                    59781 => 'گوگ تپه خالصه',
                    59791 => 'تک اغاج',
                    59831 => 'هاچاسو',
                    59841 => 'هولاسو',
                    59851 => 'قوزلوی افشار',
                    59861 => 'محمودآباد',
                    59871 => 'الی چین',
                    59881 => 'حیدرباغی',
                    59891 => 'حمزه قاسم',
                    59931 => 'اوغول بیگ',
                    59941 => 'دورباش',
                    59951 => 'اقابیگ',
                    59961 => 'احمدابادسفلی',
                    59981 => 'باروق',
                ],
            ],
            17 => [
                'title' => 'همدان',
                'cities' => [
                    651 => 'همدان',
                    6531 => 'بهار',
                    6541 => 'اسدآباد',
                    6551 => 'کبودرآهنگ',
                    6561 => 'فامنین',
                    6571 => 'ملایر',
                    6581 => 'تویسرکان',
                    6591 => 'نهاوند',
                    65141 => 'مریانج',
                    65181 => 'جورقان',
                    65331 => 'لالجین',
                    65341 => 'دیناراباد',
                    65351 => 'همه کسی',
                    65361 => 'صالح آباد',
                    65371 => 'پرلوک',
                    65381 => 'حسین ابادبهارعاشوری',
                    65391 => 'مهاجران',
                    65431 => 'ویرایی',
                    65441 => 'جنت اباد',
                    65451 => 'موسی اباد',
                    65461 => 'چنارسفلی',
                    65471 => 'چنارعلیا',
                    65481 => 'آجین',
                    65491 => 'طویلان سفلی',
                    65531 => 'کوریجان',
                    65541 => 'کوهین',
                    65551 => 'قهوردسفلی',
                    65561 => 'اکنلو',
                    65571 => 'شیرین سو',
                    65581 => 'گل تپه',
                    65591 => 'داق داق اباد',
                    65631 => 'قهاوند',
                    65641 => 'تجرک',
                    65651 => 'کوزره',
                    65661 => 'چانگرین',
                    65671 => 'دمق',
                    65681 => 'رزن',
                    65691 => 'قروه درجزین',
                    65731 => 'ازناو',
                    65741 => 'جوزان',
                    65751 => 'زنگنه',
                    65761 => 'سامن',
                    65771 => 'اورزمان',
                    65781 => 'جوکار',
                    65791 => 'اسلام اباد',
                    65831 => 'جعفریه (قلعه جعفربیک)',
                    65841 => 'سرکان',
                    65851 => 'میانده',
                    65861 => 'فرسفج',
                    65871 => 'ولاشجرد',
                    65881 => 'اشتران',
                    65891 => 'باباپیر',
                    65931 => 'جهان اباد',
                    65941 => 'باباقاسم',
                    65951 => 'بابارستم',
                    65960 => 'برزول',
                    65961 => 'گیان',
                    65971 => 'دهفول',
                    65981 => 'فیروزان',
                    65991 => 'شهرک صنعتی بوعلی',
                    65992 => 'پایگاه نوژه',
                    65993 => 'علیصدر',
                    65995 => 'ازندریان',
                    65998 => 'گنبد',
                    66000 => 'پادگان قهرمان',
                ],
            ],
            18 => [
                'title' => 'كردستان',
                'cities' => [
                    661 => 'سنندج',
                    6631 => 'کامیاران',
                    6641 => 'دیواندره',
                    6651 => 'بیجار',
                    6661 => 'قروه',
                    6671 => 'مریوان',
                    6681 => 'سقز',
                    6691 => 'بانه',
                    66171 => 'شویشه',
                    66331 => 'شاهینی',
                    66341 => 'طای',
                    66351 => 'گازرخانی',
                    66361 => 'نشورسفلی',
                    66371 => 'شیروانه',
                    66381 => 'خامسان',
                    66391 => 'موچش',
                    66431 => 'شریف اباد',
                    66441 => 'کوله',
                    66451 => 'هزارکانیان',
                    66461 => 'زرینه',
                    66471 => 'گورباباعلی',
                    66481 => 'گاوشله',
                    66491 => 'خرکه',
                    66531 => 'یاسوکند',
                    66541 => 'توپ اغاج',
                    66551 => 'اق بلاغ طغامین',
                    66561 => 'بابارشانی',
                    66571 => 'خسرواباد',
                    66591 => 'جعفراباد',
                    66631 => 'دلبران',
                    66641 => 'دزج',
                    66651 => 'کانی گنجی',
                    66661 => 'بلبان آباد',
                    66671 => 'دهگلان',
                    66681 => 'قوریچای',
                    66691 => 'سریش آباد',
                    66711 => 'کانی دینار',
                    66731 => 'نی',
                    66741 => 'برده رشه',
                    66751 => 'چناره',
                    66761 => 'پیرخضران',
                    66771 => 'بیساران',
                    66781 => 'سروآباد',
                    66791 => 'اورامان تخت',
                    66831 => 'سرا',
                    66841 => 'گل تپه',
                    66851 => 'تیلکو',
                    66861 => 'صاحب',
                    66871 => 'خورخوره',
                    66881 => 'کسنزان',
                    66891 => 'میرده',
                    66931 => 'ننور',
                    66941 => 'بوئین سفلی',
                    66951 => 'آرمرده',
                    66961 => 'بوالحسن',
                    66971 => 'کانی سور',
                    66981 => 'کوخان',
                    66991 => 'شوی',
                ],
            ],
            19 => [
                'title' => 'كرمانشاه',
                'cities' => [
                    671 => 'کرمانشاه',
                    6731 => 'هرسین',
                    6741 => 'کنگاور',
                    6751 => 'سنقر',
                    6761 => 'اسلام آبادغرب',
                    6771 => 'سرپل ذهاب',
                    6781 => 'قصرشیرین',
                    6791 => 'پاوه',
                    67131 => 'رباط',
                    67331 => 'هفت اشیان',
                    67341 => 'هلشی',
                    67351 => 'دوردشت',
                    67361 => 'سنقراباد',
                    67371 => 'بیستون',
                    67381 => 'جعفراباد',
                    67391 => 'مرزبانی',
                    67431 => 'فش',
                    67441 => 'فرامان',
                    67451 => 'سلطان اباد',
                    67461 => 'صحنه',
                    67471 => 'قزوینه',
                    67481 => 'دهلقین',
                    67491 => 'درکه',
                    67531 => 'باوله',
                    67541 => 'گردکانه علیا',
                    67551 => 'اگاه علیا',
                    67561 => 'سطر',
                    67571 => 'کیوه نان',
                    67580 => 'میان راهان',
                    67581 => 'کرکسار',
                    67591 => 'کندوله',
                    67631 => 'زاوله علیا',
                    67641 => 'حمیل',
                    67651 => 'ریجاب',
                    67661 => 'کرندغرب',
                    67671 => 'گهواره',
                    67681 => 'کوزران',
                    67691 => 'قلعه شیان',
                    67731 => 'حسن اباد',
                    67741 => 'سراب ذهاب',
                    67751 => 'ترک ویس',
                    67761 => 'ازگله',
                    67771 => 'تازه آباد',
                    67781 => 'نساردیره',
                    67791 => 'سرمست',
                    67831 => 'تپه رش',
                    67841 => 'خسروی',
                    67861 => 'سومار',
                    67871 => 'گیلانغرب',
                    67891 => 'قیلان',
                    67911 => 'شاهو',
                    67931 => 'باینگان',
                    67940 => 'بانوره',
                    67941 => 'نوسود',
                    67951 => 'نودشه',
                    67961 => 'روانسر',
                    67971 => 'دولت اباد',
                    67981 => 'جوانرود',
                    67991 => 'میراباد',
                ],
            ],
            20 => [
                'title' => 'لرستان',
                'cities' => [
                    681 => 'خرم آباد',
                    691 => 'بروجرد',
                    6831 => 'نورآباد',
                    6841 => 'کوهدشت',
                    6851 => 'پلدختر',
                    6861 => 'الیگودرز',
                    6871 => 'ازنا',
                    6881 => 'دورود',
                    6891 => 'الشتر',
                    68141 => 'ماسور',
                    68181 => 'بیرانوند',
                    68331 => 'برخوردار',
                    68341 => 'فرهاداباد',
                    68351 => 'دم باغ',
                    68361 => 'کهریزوروشت',
                    68371 => 'چشمه کیزاب علیا',
                    68381 => 'هفت چشمه',
                    68391 => 'تقی اباد',
                    68431 => 'خوشناموند',
                    68441 => 'اشتره گل گل',
                    68451 => 'چقابل',
                    68461 => 'سوری',
                    68471 => 'کونانی',
                    68481 => 'گراب',
                    68491 => 'درب گنبد',
                    68531 => 'پاعلم (پل تنگ)',
                    68541 => 'واشیان نصیرتپه',
                    68551 => 'چمشک زیرتنگ',
                    68561 => 'افرینه',
                    68571 => 'معمولان',
                    68580 => 'ویسیان',
                    68581 => 'میان تاگان',
                    68591 => 'پل شوراب پایین',
                    68631 => 'شاهپوراباد',
                    68641 => 'چمن سلطان',
                    68651 => 'کیزاندره',
                    68661 => 'قلعه بزنوید',
                    68671 => 'شول آباد',
                    68681 => 'حیه',
                    68691 => 'مرگ سر',
                    68731 => 'مومن آباد',
                    68741 => 'رازان',
                    68751 => 'سیاه گوشی (پل هرو)',
                    68761 => 'زاغه',
                    68771 => 'سرابدوره',
                    68781 => 'چاه ذوالفقار',
                    68791 => 'چم پلک',
                    68831 => 'ژان',
                    68841 => 'کاغه',
                    68851 => 'چالانچولان',
                    68861 => 'سپید دشت',
                    68871 => 'چم سنگر',
                    68881 => 'ایستگاه تنگ هفت',
                    68891 => 'مکینه حکومتی',
                    68931 => 'سراب سیاهپوش',
                    68951 => 'ده رحم',
                    68961 => 'فیروز آباد',
                    68971 => 'اشترینان',
                    68981 => 'بندیزه',
                    68991 => 'دره گرگ',
                ],
            ],
            21 => [
                'title' => 'بوشهر',
                'cities' => [
                    751 => 'بوشهر',
                    7531 => 'بندرگناوه',
                    7541 => 'خورموج',
                    7551 => 'اهرم',
                    7561 => 'برازجان',
                    75111 => 'نخل تقی',
                    75331 => 'بندر ریگ',
                    75341 => 'چهارروستایی',
                    75351 => 'شول',
                    75361 => 'بندر دیلم',
                    75371 => 'امام حسن',
                    75381 => 'چغادک',
                    75390 => 'سیراف',
                    75391 => 'عسلویه',
                    75431 => 'بادوله',
                    75441 => 'شنبه',
                    75451 => 'کاکی',
                    75461 => 'خارک',
                    75471 => 'دلوار',
                    75481 => 'بنه گز',
                    75491 => 'اباد',
                    75531 => 'بردخون',
                    75540 => 'بردستان',
                    75541 => 'بندردیر',
                    75551 => 'آبدان',
                    75560 => 'انارستان',
                    75561 => 'ریز',
                    75570 => 'بنک',
                    75571 => 'بندرکنگان',
                    75581 => 'جم',
                    75591 => 'ابگرمک',
                    75631 => 'دالکی',
                    75641 => 'شبانکاره',
                    75651 => 'آبپخش',
                    75661 => 'سعدآباد',
                    75671 => 'وحدتیه',
                    75681 => 'تنگ ارم',
                    75691 => 'کلمه',
                ],
            ],
            22 => [
                'title' => 'كرمان',
                'cities' => [
                    761 => 'کرمان',
                    771 => 'رفسنجان',
                    781 => 'سیرجان',
                    7631 => 'ماهان',
                    7641 => 'گلباف',
                    7651 => 'راور',
                    7661 => 'بم',
                    7671 => 'بروات',
                    7681 => 'راین',
                    7691 => 'محمدآباد',
                    7731 => 'سرچشمه',
                    7741 => 'انار',
                    7751 => 'شهربابک',
                    7761 => 'زرند',
                    7771 => 'کیانشهر',
                    7781 => 'کوهبنان',
                    7791 => 'چترود',
                    7831 => 'پاریز',
                    7841 => 'بردسیر',
                    7851 => 'بافت',
                    7861 => 'جیرفت',
                    7871 => 'عنبرآباد',
                    7881 => 'کهنوج',
                    7891 => 'منوجان',
                    76331 => 'ده بالا',
                    76361 => 'جوپار',
                    76371 => 'باغین',
                    76381 => 'اختیارآباد',
                    76391 => 'زنگی آباد',
                    76431 => 'جوشان',
                    76451 => 'اندوهجرد',
                    76461 => 'شهداد',
                    76471 => 'کشیت',
                    76541 => 'فیض اباد',
                    76641 => 'دریجان',
                    76731 => 'نرماشیر',
                    76741 => 'فهرج',
                    76771 => 'برج معاز',
                    76791 => 'نظام شهر',
                    76831 => 'خانه خاتون',
                    76841 => 'ابارق',
                    76861 => 'گروه',
                    76871 => 'گزک',
                    76891 => 'محی آباد',
                    76941 => 'تهرود',
                    76951 => 'میرابادارجمند',
                    77331 => 'داوران',
                    77341 => 'خنامان',
                    77351 => 'کبوترخان',
                    77361 => 'هرمزاباد',
                    77371 => 'کشکوئیه',
                    77381 => 'گلشن',
                    77391 => 'صفائیه',
                    77431 => 'امین شهر',
                    77461 => 'بهرمان',
                    77471 => 'جوادیه الهیه نوق',
                    77511 => 'خاتون آباد',
                    77541 => 'محمدابادبرفه',
                    77551 => 'خورسند',
                    77561 => 'خبر',
                    77571 => 'کمسرخ',
                    77581 => 'جوزم',
                    77591 => 'دهج',
                    77631 => 'دشت خاک',
                    77651 => 'حتکن',
                    77661 => 'ریحان',
                    77671 => 'جرجافک',
                    77691 => 'یزدان شهر',
                    77731 => 'شعبجره',
                    77751 => 'سیریز',
                    77761 => 'خانوک',
                    77861 => 'جور',
                    77931 => 'هوتک',
                    77951 => 'کاظم آباد',
                    77961 => 'هجدک',
                    77971 => 'حرجند',
                    78151 => 'نجف شهر',
                    78331 => 'بلورد',
                    78341 => 'ملک اباد',
                    78361 => 'عماداباد',
                    78371 => 'زیدآباد',
                    78380 => 'هماشهر',
                    78431 => 'نگار',
                    78441 => 'گلزار',
                    78451 => 'لاله زار',
                    78461 => 'قلعه عسکر',
                    78471 => 'مومن اباد',
                    78481 => 'چناربرین',
                    78491 => 'کمال اباد',
                    78541 => 'امیراباد',
                    78551 => 'بزنجان',
                    78561 => 'رابر',
                    78571 => 'پتکان',
                    78591 => 'ارزوئیه',
                    78631 => 'جبالبارز',
                    78661 => 'درب بهشت',
                    78691 => 'رضی ابادبالا',
                    78731 => 'میجان علیا',
                    78761 => 'مردهک',
                    78771 => 'دوساری',
                    78781 => 'حسین ابادجدید',
                    78791 => 'بلوک',
                    78831 => 'رودبار',
                    78841 => 'قلعه گنج',
                    78851 => 'نودژ',
                    78871 => 'فاریاب',
                    78941 => 'سرخ قلعه',
                    78971 => 'خیراباد',
                ],
            ],
            23 => [
                'title' => 'هرمزگان',
                'cities' => [
                    791 => 'بندرعباس',
                    7931 => 'خمیر',
                    7941 => 'کیش',
                    7951 => 'قشم',
                    7961 => 'بستک',
                    7971 => 'بندرلنگه',
                    7981 => 'میناب',
                    7991 => 'دهبارز',
                    79331 => 'پشته ایسین',
                    79341 => 'پل شرقی',
                    79351 => 'فین',
                    79361 => 'سیاهو',
                    79370 => 'سرگز',
                    79371 => 'فارغان',
                    79381 => 'باغات',
                    79391 => 'حاجی آباد',
                    79431 => 'ابگرم خورگو',
                    79441 => 'قلعه قاضی',
                    79450 => 'تخت',
                    79451 => 'حسن لنگی پایین',
                    79460 => 'گروک',
                    79461 => 'سیریک',
                    79471 => 'گونمردی',
                    79491 => 'گوهرت',
                    79531 => 'درگهان',
                    79541 => 'سوزا',
                    79551 => 'هرمز',
                    79561 => 'جزیره لارک شهری',
                    79571 => 'هنگام جدید',
                    79581 => 'جزیره سیری',
                    79591 => 'ابوموسی',
                    79611 => 'جناح',
                    79631 => 'پدل',
                    79641 => 'کنگ',
                    79651 => 'دژگان',
                    79661 => 'رویدر',
                    79671 => 'دهنگ',
                    79691 => 'کمشک',
                    79711 => 'کوشکنار',
                    79731 => 'گزیر',
                    79741 => 'بندرمغویه',
                    79751 => 'چارک',
                    79761 => 'دشتی',
                    79771 => 'پارسیان',
                    79781 => 'جزیره لاوان',
                    79791 => 'بندرجاسک',
                    79831 => 'بندر',
                    79841 => 'سندرک',
                    79851 => 'درپهن',
                    79861 => 'کلورجکدان',
                    79871 => 'گوهران',
                    79881 => 'سردشت',
                    79911 => 'بیکاه',
                    79931 => 'جغین',
                    79941 => 'زیارت علی',
                    79951 => 'ماشنگی',
                    79961 => 'گوربند',
                    79971 => 'تیاب',
                    79981 => 'بندزرک',
                    79991 => 'هشتبندی',
                ],
            ],
            24 => [
                'title' => 'چهارمحال و بختياري',
                'cities' => [
                    881 => 'شهر کرد',
                    8831 => 'فرخ شهر',
                    8834 => 'دزک',
                    8841 => 'هفشجان',
                    8844 => 'هارونی',
                    8851 => 'سامان',
                    8861 => 'فارسان',
                    8871 => 'بروجن',
                    8881 => 'اردل',
                    8891 => 'لردگان',
                    88139 => 'کیان',
                    88331 => 'طاقانک',
                    88351 => 'خراجی',
                    88361 => 'دستناء',
                    88371 => 'شلمزار',
                    88381 => 'گهرو',
                    88431 => 'سورشجان',
                    88451 => 'مرغملک',
                    88461 => 'سودجان',
                    88561 => 'نافچ',
                    88571 => 'وردنجان',
                    88581 => 'بن',
                    88591 => 'پردنجان',
                    88631 => 'باباحیدر',
                    88651 => 'چلگرد',
                    88661 => 'شهریاری',
                    88671 => 'جونقان',
                    88731 => 'نقنه',
                    88741 => 'فرادنبه',
                    88751 => 'سفید دشت',
                    88761 => 'بلداجی',
                    88771 => 'اورگان',
                    88781 => 'گندمان',
                    88791 => 'امام قیس',
                    88831 => 'ناغان',
                    88841 => 'گل سفید',
                    88861 => 'چوله دان',
                    88881 => 'دشتک',
                    88941 => 'آلونی',
                    88951 => 'مال خلیفه',
                    88961 => 'چمن بید',
                    88971 => 'سردشت',
                    88991 => 'منج',
                ],
            ],
            25 => [
                'title' => 'يزد',
                'cities' => [
                    891 => 'یزد',
                    8931 => 'ابرکوه',
                    8951 => 'اردکان',
                    8961 => 'میبد',
                    8971 => 'بافق',
                    8981 => 'مهریز',
                    8991 => 'تفت',
                    89331 => 'فراغه',
                    89351 => 'مهردشت',
                    89361 => 'اسفنداباد',
                    89416 => 'اشکذر',
                    89418 => 'زارچ',
                    89431 => 'شاهدیه',
                    89441 => 'فهرج',
                    89451 => 'خضر آباد',
                    89481 => 'ندوشن',
                    89491 => 'حمیدیا',
                    89531 => 'احمد آباد',
                    89551 => 'عقدا',
                    89571 => 'انارستان',
                    89581 => 'زرین',
                    89631 => 'بفروئیه',
                    89731 => 'اسفیج',
                    89751 => 'مبارکه',
                    89761 => 'بهاباد',
                    89771 => 'کوشک',
                    89781 => 'بنستان',
                    89831 => 'تنگ چنار (چنار)',
                    89851 => 'ارنان',
                    89861 => 'بهادران',
                    89871 => 'مروست',
                    89881 => 'هرات',
                    89891 => 'فتح اباد',
                    89931 => 'ناحیه صنعتی پیشکوه',
                    89941 => 'نصراباد',
                    89951 => 'علی اباد',
                    89961 => 'نیر',
                    89981 => 'ناحیه صنعتی گاریزات',
                    89991 => 'دهشیر',
                ],
            ],
            26 => [
                'title' => 'سيستان و بلوچستان',
                'cities' => [
                    981 => 'زاهدان',
                    991 => 'ایرانشهر',
                    9831 => 'نصرت آباد',
                    9841 => 'میرجاوه',
                    9861 => 'زابل',
                    9871 => 'زهک',
                    9875 => 'خواجه احمد',
                    9891 => 'خاش',
                    9931 => 'سرباز',
                    9941 => 'بمپور',
                    9951 => 'سراوان',
                    9961 => 'سوران',
                    9971 => 'چابهار',
                    9981 => 'کنارک',
                    9991 => 'نیکشهر',
                ],
            ],
            27 => [
                'title' => 'ايلام',
                'cities' => [
                    6931 => 'ایلام',
                    6941 => 'ایوان',
                    6951 => 'سرآبله',
                    6961 => 'دره شهر',
                    6971 => 'آبدانان',
                    6981 => 'دهلران',
                    6991 => 'مهران',
                    69331 => 'چنارباشی',
                    69341 => 'بیشه دراز',
                    69351 => 'چشمه کبود',
                    69361 => 'چوار',
                    69371 => 'بانویزه',
                    69381 => 'چمن سیدمحمد',
                    69391 => 'هفت چشمه',
                    69441 => 'شورابه ملک',
                    69451 => 'کلان',
                    69471 => 'زرنه',
                    69511 => 'شباب',
                    69531 => 'توحید',
                    69541 => 'بلاوه تره سفلی',
                    69551 => 'لومار',
                    69561 => 'آسمان آباد',
                    69571 => 'سراب کارزان',
                    69581 => 'شهرک سرتنگ',
                    69591 => 'علی اباد',
                    69631 => 'ماژین',
                    69641 => 'ارمو',
                    69661 => 'چشمه شیرین',
                    69671 => 'بدره',
                    69681 => 'شهرک ولیعصر',
                    69731 => 'گنداب',
                    69741 => 'ژیور',
                    69751 => 'سراب باغ',
                    69761 => 'مورموری',
                    69771 => 'سیاه گل',
                    69781 => 'اب انار',
                    69831 => 'چم هندی',
                    69841 => 'موسیان',
                    69851 => 'گولاب',
                    69861 => 'میمه',
                    69871 => 'پهله',
                    69881 => 'عین خوش',
                    69891 => 'دشت عباس',
                    69931 => 'شهرک اسلامیه',
                    69951 => 'صالح آباد',
                    69970 => 'دلگشا',
                    69971 => 'ارکواز',
                    69972 => 'مهر',
                    69981 => 'دول کبودخوشادول',
                    69991 => 'پاریاب',
                ],
            ],
            28 => [
                'title' => 'كهگيلويه و بويراحمد',
                'cities' => [
                    7571 => 'دهدشت',
                    7581 => 'دوگنبدان',
                    7591 => 'یاسوج',
                    75731 => 'سوق',
                    75741 => 'لنده',
                    75751 => 'لیکک',
                    75761 => 'چرام',
                    75771 => 'دیشموک',
                    75781 => 'قلعه رییسی',
                    75791 => 'قلعه دختر',
                    75831 => 'باباکلان',
                    75841 => 'مظفراباد',
                    75851 => 'دیل',
                    75861 => 'شاه بهرام',
                    75871 => 'چاه تلخاب علیا',
                    75881 => 'باشت',
                    75891 => 'سربیشه',
                    75911 => 'مادوان',
                    75941 => 'چیتاب',
                    75951 => 'گراب سفلی',
                    75961 => 'مارگون',
                    75971 => 'میمند',
                    75981 => 'پاتاوه',
                    75991 => 'سی سخت',
                ],
            ],
            29 => [
                'title' => 'خراسان شمالي',
                'cities' => [
                    941 => 'بجنورد',
                    9431 => 'گرمه',
                    9441 => 'جاجرم',
                    9451 => 'آشخانه',
                    9461 => 'شیروان',
                    9481 => 'فاروج',
                    9661 => 'اسفراین',
                ],
            ],
            30 => [
                'title' => 'خراسان جنوبي',
                'cities' => [
                    971 => 'بیرجند',
                    9741 => 'سربیشه',
                    9751 => 'نهبندان',
                    9761 => 'قاین',
                    9771 => 'فردوس',
                    9781 => 'بشرویه',
                    9791 => 'طبس',
                ],
            ],
            31 => [
                'title' => 'البرز',
                'cities' => [
                    31 => 'کرج',
                    3331 => 'نظرآباد',
                    3361 => 'هشتگرد',
                    31541 => 'ادران',
                    31551 => 'آسارا',
                    31638 => 'گرمدره',
                    31656 => 'فردیس',
                    31776 => 'مشکین دشت',
                    31778 => 'محمدشهر',
                    31836 => 'کرج (مهرشهر)',
                    31849 => 'ماهدشت',
                    31871 => 'اشتهارد',
                    31991 => 'کمالشهر',
                    33351 => 'تنکمان',
                    33611 => 'گلسار',
                    33618 => 'شهر جدید هشتگرد',
                    33651 => 'کوهسار',
                    33661 => 'چهارباغ',
                    33691 => 'طالقان',
                ],
            ],
        ];
        foreach($citiesArray as $province) {
            foreach ($province['cities'] as $code => $cityName) {
                if ($code == $cityCode) {
                    return $cityName;
                }
            }
        }
        return false;
    }
//=========================================================================================================================
}