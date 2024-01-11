<?php

include_once('HesabixLogService.php');

class WpFa
{
    public $id;
    public $objType;
    public $idHesabix;
    public $idWp;
    public $idWpAttribute;

    public function __construct() {}

    public static function newWpFa($id, $type, $idHesabix, $idWp, $idWpAttribute): WpFa
    {
        $instance = new self();
        $instance->id = $id;
        $instance->objType = $type;
        $instance->idHesabix = $idHesabix;
        $instance->idWp = $idWp;
        $instance->idWpAttribute = $idWpAttribute;
        return $instance;
    }
}

class HesabixWpFaService
{
    public function __construct() {}

    public function getWpFa($objType, $idWp, $idWpAttribute = 0)
    {
        if (!isset($objType) || !isset($idWp)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $idWp AND `id_ps_attribute` = $idWpAttribute AND `obj_type` = '$objType'");

        if (isset($row)) return $this->mapWpFa($row);

        return null;
    }
//=========================================================================================================
    public function getWpFaByHesabixId($objType, $hesabixId)
    {
        if (!isset($objType) || !isset($hesabixId)) return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_hesabix` = $hesabixId AND `obj_type` = '$objType'");

        if (isset($row))
            return $this->mapWpFa($row);
        return null;
    }
//=========================================================================================================
    public function getWpFaId($objType, $idWp, $idWpAttribute = 0)
    {
        if (!isset($objType) || !isset($idWp))
            return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_ps` = $idWp AND `id_ps_attribute` = $idWpAttribute AND `obj_type` = '$objType'");

        if (is_object($row))
            return (int)$row->id;
        else
            return false;
    }
//=========================================================================================================
    public function getWpFaIdByHesabixId($objType, $hesabixId)
    {
        if (!isset($objType) || !isset($hesabixId))
            return false;

        global $wpdb;
        $row = $wpdb->get_row("SELECT `id` FROM " . $wpdb->prefix . "ssbhesabix WHERE `id_hesabix` = $hesabixId AND `obj_type` = '$objType'");

        if (isset($row))
            return (int)$row->id;
        return null;
    }
//=========================================================================================================
    public function getProductCodeByWpId($id_product, $id_attribute = 0)
    {
        $obj = $this->getWpFa('product', $id_product, $id_attribute);

        if ($obj != null) return $obj->idHesabix;

        return null;
    }
//=========================================================================================================
    public function getCustomerCodeByWpId($id_customer)
    {
        $obj = $this->getWpFa('customer', $id_customer);

        if ($obj != null) return $obj->idHesabix;

        return null;
    }
//=========================================================================================================
    public function getInvoiceCodeByWpId($id_order)
    {
        $obj = $this->getWpFa('order', $id_order);

        if ($obj != null) return $obj->idHesabix;

        return null;
    }
//=========================================================================================================
    public function getProductAndCombinations($idWp)
    {
        global $wpdb;

        $sql = "SELECT * FROM `" . $wpdb->prefix . "ssbhesabix` WHERE `obj_type` = 'product' AND `id_ps` = '$idWp'";
        $result = $wpdb->get_results($sql);

        $wpFaObjects = array();
        if (isset($result) && is_array($result) && count($result) > 0) {
            foreach ($result as $item)
                $wpFaObjects[] = $this->mapWpFa($item);
            return $wpFaObjects;
        }
        return null;
    }
//=========================================================================================================
    public function mapWpFa($sqlObj): WpFa
    {
        $wpFa = new WpFa();

        $wpFa->id = $sqlObj->id;
        $wpFa->idHesabix = $sqlObj->id_hesabix;
        $wpFa->idWp = $sqlObj->id_ps;
        $wpFa->idWpAttribute = $sqlObj->id_ps_attribute;
        $wpFa->objType = $sqlObj->obj_type;

        return $wpFa;
    }
//=========================================================================================================
    public function saveProduct($item): bool
    {
        $json = json_decode($item->Tag);
        $wpFaService = new HesabixWpFaService();
        $wpFa = $wpFaService->getWpFaByHesabixId('product', $item->Code);

        if (!$wpFa) {
            $wpFa = WpFa::newWpFa(0, 'product', (int)$item->Code, (int)$json->id_product, (int)$json->id_attribute);
            $wpFaService->save($wpFa);
            HesabixLogService::log(array("Item successfully added. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute"));
        } else {
            $wpFa->idHesabix = (int)$item->Code;
            $wpFaService->update($wpFa);
            HesabixLogService::log(array("Item successfully updated. Item code: " . (string)$item->Code . ". Product ID: $json->id_product-$json->id_attribute"));
        }
        return true;
    }
//=========================================================================================================
    public function saveCustomer($customer): bool
    {
        $json = json_decode($customer->Tag);
        if ((int)$json->id_customer == 0) return true;

        $id = $this->getWpFaId('customer', (int)$json->id_customer);
        global $wpdb;

        if (!$id) {
            $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
                'id_hesabix' => (int)$customer->Code,
                'obj_type' => 'customer',
                'id_ps' => (int)$json->id_customer
            ));
            HesabixLogService::writeLogStr("Customer successfully added. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        } else {
            $wpdb->update($wpdb->prefix . 'ssbhesabix', array(
                'id_hesabix' => (int)$customer->Code,
                'obj_type' => 'customer',
                'id_ps' => (int)$json->id_customer,
            ), array('id' => $id));
            HesabixLogService::writeLogStr("Customer successfully updated. Customer code: " . (string)$customer->Code . ". Customer ID: $json->id_customer");
        }
        return true;
    }
//=========================================================================================================
    public function saveInvoice($invoice, $orderType)
    {
        $json = json_decode($invoice->Tag);
        $id = $this->getPsFaId('order', (int)$json->id_order);

        $invoiceNumber = (int)$invoice->Number;
        $objType = $orderType == 0 ? 'order' : 'returnOrder';

        if (!$id) {
            Db::getInstance()->insert('ps_hesabix', array(
                'id_hesabix' => $invoiceNumber,
                'obj_type' => $objType,
                'id_ps' => (int)$json->id_order,
            ));
            if ($objType == 'order')
                LogService::writeLogStr("Invoice successfully added. invoice number: " . (string)$invoice->Number . ", order id: " . $json->id_order);
            else
                LogService::writeLogStr("Return Invoice successfully added. Customer code: " . (string)$invoice->Number . ", order id: " . $json->id_order);
        } else {
            Db::getInstance()->update('ps_hesabix', array(
                'id_hesabix' => $invoiceNumber,
                'obj_type' => $objType,
                'id_ps' => (int)$json->id_order,
            ), array('id' => $id), 0, true, true);
            //check if it is order or return order
            if ($objType == 'order')
                LogService::writeLogStr("Invoice successfully updated. invoice number: " . (string)$invoice->Number . ", order id: " . $json->id_order);
            else
                LogService::writeLogStr("Return Invoice successfully updated. Customer code: " . (string)$invoice->Number . ", order id: " . $json->id_order);
        }

        return true;
    }
//=========================================================================================================
    public function save(WpFa $wpFa)
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'ssbhesabix', array(
            'id_hesabix' => $wpFa->idHesabix,
            'obj_type' => $wpFa->objType,
            'id_ps' => (int)$wpFa->idWp,
            'id_ps_attribute' => (int)$wpFa->idWpAttribute,
        ));
    }
//=========================================================================================================
    public function update(WpFa $wpFa)
    {
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'ssbhesabix', array(
            'id_hesabix' => $wpFa->idHesabix,
            'obj_type' => $wpFa->objType,
            'id_ps' => (int)$wpFa->idWp,
            'id_ps_attribute' => (int)$wpFa->idWpAttribute,
        ), array('id' => $wpFa->id));
    }
//=========================================================================================================
    public function delete(WpFa $wpFa)
    {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id' => $wpFa->id));
    }
//=========================================================================================================
    public function deleteAll($productId)
    {
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'ssbhesabix', array('id_ps' => $productId));
    }
//=========================================================================================================
}