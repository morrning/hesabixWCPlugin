<?php

include_once(plugin_dir_path(__DIR__) . 'admin/services/HesabixLogService.php');

/**
 * @class      Ssbhesabix_Api
 * @version    2.0.93
 * @since      1.0.0
 * @package    ssbhesabix
 * @subpackage ssbhesabix/api
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 */

class Ssbhesabix_Api
{
//================================================================================================
    public function apiRequest($method, $data = array())
    {
        if ($method == null) return false;

        $endpoint = 'https://hesabix.ir/' . $method;

        $apiAddress = get_option('ssbhesabix_api_address', 0);

        if($apiAddress == 1) $endpoint = 'https://next.hesabix.ir/' . $method;

        $body = array_merge(array(
            'API-KEY' => get_option('ssbhesabix_account_api'),
        ), $data);

        //Debug mode
        if (get_option('ssbhesabix_debug_mode')) {
            HesabixLogService::log(array("Debug Mode - Data: " . print_r($data, true)));
        }

        $options = array(
            'body' => wp_json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'API-KEY' => get_option('ssbhesabix_account_api'),
                'activeBid' => get_option('ssbhesabix_account_bid'),
                'activeYear' => get_option('ssbhesabix_account_year'),
            ),
            'timeout' => 60,
            'redirection' => 5,
            'blocking' => true,
            'httpversion' => '1.0',
            'sslverify' => false,
            'data_format' => 'body',
        );

        //HesabixLogService::writeLogObj($options);
        $wp_remote_post = wp_remote_post($endpoint, $options);
        $result = json_decode(wp_remote_retrieve_body($wp_remote_post));
        //Debug mode
        if (get_option('ssbhesabix_debug_mode')) {
            HesabixLogService::log(array("Debug Mode - Result: " . print_r($result, true)));
        }

        //fix API limit request - Maximum request per minutes is 60 times,
        sleep(1);

        if ($result == null) {
            return 'No response from Hesabix';
        } else {
            return $result;
        }
        return false;
    }
//================================================================================================
    //Contact functions
    public function contactGet($code)
    {
        $method = 'contact/get';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetById($idList)
    {
        $method = 'contact/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetContacts($queryInfo)
    {
        $method = 'contact/getcontacts';
        $data = array(
            'queryInfo' => $queryInfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactSave($contact)
    {
        $method = 'contact/save';
        $data = array(
            'contact' => $contact,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactDelete($code)
    {
        $method = 'contact/delete';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function contactGetByPhoneOrEmail($phone, $email) {
        $method = 'contact/findByPhoneOrEmail';
        $data = array(
            'mobile' => $phone,
            'email' => $email,
            'phone' => $phone,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Items functions
    public function itemGet($code)
    {
        $method = 'item/get';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetByBarcode($barcode)
    {
        $method = 'item/getByBarcode';
        $data = array(
            'barcode' => $barcode,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetById($idList)
    {
        $method = 'item/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetItems($queryInfo = null)
    {
        $method = 'hooks/item/getitems';
        $data = array(
            'queryInfo' => $queryInfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemSave($item)
    {
        $method = 'item/save';
        $data = array(
            'item' => $item,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemBatchSave($items)
    {
        $method = 'item/batchsave';
        $data = array(
            'items' => $items,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemDelete($code)
    {
        $method = 'item/delete';
        $data = array(
            'code' => $code,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemGetQuantity($warehouseCode, $codes)
    {
        $method = 'item/GetQuantity';
        $data = array(
            'warehouseCode' => $warehouseCode,
            'codes' => $codes,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Invoice functions
    public function invoiceGet($number, $type = 0)
    {
        $method = 'invoice/get';
        $data = array(
            'number' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetById($id)
    {
        $method = 'invoice/getById';
        $data = array(
            'id' => $id,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetByIdList($idList)
    {
        $method = 'invoice/getById';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetInvoices($queryinfo, $type = 0)
    {
        $method = 'invoice/getinvoices';
        $data = array(
            'type' => $type,
            'queryInfo' => $queryinfo,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceSave($invoice, $GUID='')
    {
        $method = 'invoice/save';
        $data = array(
            'invoice' => $invoice,
        );

        if($GUID != '') $data['requestUniqueId'] = $GUID;

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceDelete($number, $type = 0)
    {
        $method = 'invoice/delete';
        $data = array(
            'code' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceSavePayment($number, $financialData, $accountPath, $date, $amount, $transactionNumber = null, $description = null, $transactionFee = 0)
    {
        if(get_option('ssbhesabix_invoice_transaction_fee') && get_option('ssbhesabix_invoice_transaction_fee') > 0) {
            $transactionFeeOption = get_option('ssbhesabix_invoice_transaction_fee');

            $func = new Ssbhesabix_Admin_Functions();
            $transactionFeeOption = $func->convertPersianDigitsToEnglish($transactionFeeOption);

            if($transactionFeeOption<100 && $transactionFeeOption>0) $transactionFeeOption /= 100;
            $transactionFee = $amount * $transactionFeeOption;
            if($transactionFee < 1) $transactionFee = 0;
        }

        $method = 'invoice/savepayment';
        $data = array(
            'number' => (int)$number,
            'date' => $date,
            'amount' => $amount,
            'transactionNumber' => $transactionNumber,
            'description' => $description,
            'transactionFee' => $transactionFee,
        );

        $data = array_merge($data, $financialData);
        if($accountPath != []) $data = array_merge($data, $accountPath);

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function invoiceGetOnlineInvoiceURL($number, $type = 0)
    {
        $method = 'invoice/getonlineinvoiceurl';
        $data = array(
            'number' => $number,
            'type' => $type,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function itemUpdateOpeningQuantity($items)
    {
        $method = 'item/UpdateOpeningQuantity';
        $data = array(
            'items' => $items,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function saveWarehouseReceipt($receipt) {
        $method = 'invoice/SaveWarehouseReceipt';
        $data = array(
            'deleteOldReceipts' => true,
            'receipt' => $receipt,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function warehouseReceiptGetByIdList($idList)
    {
        $method = 'invoice/getWarehouseReceipt';
        $data = array(
            'idList' => $idList,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    //Settings functions
    public function settingSetChangeHook($url, $hookPassword)
    {
        $method = 'hooks/setting/SetChangeHook';
        $data = array(
            'url' => $url,
            'hookPassword' => $hookPassword,
        );

        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function settingGetChanges($start = 0)
    {
        $method = 'hooks/setting/GetChanges';
        $data = array(
            'start' => $start,
        );
        return $this->apiRequest($method, $data);
    }
//================================================================================================
    public function settingGetAccounts()
    {
        $method = 'hooks/setting/GetAccounts';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetBanks()
    {
        $method = 'hooks/setting/getBanks';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetCashes()
    {
        $method = 'setting/GetCashes';
        return $this->apiRequest($method);
    }

//================================================================================================
	public function settingGetSalesmen()
	{
		$method = 'setting/getSalesmen';
		return $this->apiRequest($method);
	}
//================================================================================================
	public function settingGetCurrency()
    {
        $method = 'hooks/setting/getCurrency';

        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetFiscalYear()
    {
        $method = 'setting/GetFiscalYear';

        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetWarehouses()
    {
        $method = 'setting/GetWarehouses';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function fixClearTags()
    {
        $method = 'fix/clearTag';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingGetSubscriptionInfo()
    {
        $method = 'hooks/setting/getBusinessInfo';
        return $this->apiRequest($method);
    }
//================================================================================================
    public function settingExportProdects($data)
    {
        $method = 'hooks/commodity/import';
        return $this->apiRequest($method,$data);
    }

//================================================================================================
    public function personsImport($data)
    {
        $method = 'hooks/person/import';
        return $this->apiRequest($method,$data);
    }
}