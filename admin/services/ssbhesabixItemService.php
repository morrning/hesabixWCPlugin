<?php

class ssbhesabixItemService
{
    public static function mapProduct($product, $id, $new = true) {
        $wpFaService = new HesabixWpFaService();

        $categories = $product->get_category_ids();
        $code = $new ? null : $wpFaService->getProductCodeByWpId($id) ;
        $price = $product->get_regular_price() ? $product->get_regular_price() : $product->get_price();

        $hesabixItem = array(
            'Code' => $code,
            'Name' => Ssbhesabix_Validation::itemNameValidation($product->get_title()),
            'PurchasesTitle' => Ssbhesabix_Validation::itemNameValidation($product->get_title()),
            'SalesTitle' => Ssbhesabix_Validation::itemNameValidation($product->get_title()),
            'ItemType' => $product->is_virtual() == 1 ? 1 : 0,
            'Tag' => json_encode(array('id_product' => $id, 'id_attribute' => 0))
        );

        if(!$code || get_option("ssbhesabix_do_not_update_product_price_in_hesabix", "no") === "no")
            $hesabixItem["SellPrice"] = self::getPriceInHesabixDefaultCurrency($price);
        if(get_option("ssbhesabix_do_not_update_product_barcode_in_hesabix", "no") === "no")
            $hesabixItem["Barcode"] = Ssbhesabix_Validation::itemBarcodeValidation($product->get_sku());
		if(get_option("ssbhesabix_do_not_update_product_category_in_hesabix", "no") === "no")
            if($categories) $hesabixItem["NodeFamily"] = self::getCategoryPath($categories[0]);
        if(get_option("ssbhesabix_do_not_update_product_product_code_in_hesabix", "no") === "no")
            $hesabixItem["ProductCode"] = $id;

		return $hesabixItem;
    }
//===========================================================================================================
    public static function mapProductVariation($product, $variation, $id_product, $new = true) {
        $wpFaService = new HesabixWpFaService();

        $id_attribute = $variation->get_id();
        $categories = $product->get_category_ids();
        $code = $new ? null : $wpFaService->getProductCodeByWpId($id_product, $id_attribute);

        $productName = $product->get_title();
        $variationName = $variation->get_attribute_summary();
        $fullName = Ssbhesabix_Validation::itemNameValidation($productName . ' - ' . $variationName);
        $price = $variation->get_regular_price() ? $variation->get_regular_price() : $variation->get_price();

        $hesabixItem = array(
            'Code' => $code,
            'Name' => $fullName,
            'PurchasesTitle' => $fullName,
            'SalesTitle' => $fullName,
            'ItemType' => $variation->is_virtual() == 1 ? 1 : 0,
            'Tag' => json_encode(array(
                'id_product' => $id_product,
                'id_attribute' => $id_attribute
            )),
        );

        if(!$code || get_option("ssbhesabix_do_not_update_product_price_in_hesabix", "no") === "no")    $hesabixItem["SellPrice"] = self::getPriceInHesabixDefaultCurrency($price);
        if(get_option("ssbhesabix_do_not_update_product_barcode_in_hesabix", "no") === "no")            $hesabixItem["Barcode"] = Ssbhesabix_Validation::itemBarcodeValidation($variation->get_sku());
		if(get_option("ssbhesabix_do_not_update_product_category_in_hesabix", "no") === "no")           $hesabixItem["NodeFamily"] = self::getCategoryPath($categories[0]);
        if(get_option("ssbhesabix_do_not_update_product_product_code_in_hesabix", "no") === "no")       $hesabixItem["ProductCode"] = $id_attribute;

        return $hesabixItem;
    }
//===========================================================================================================
    public static function getPriceInHesabixDefaultCurrency($price)
    {
        if (!isset($price)) return false;

        $woocommerce_currency = get_woocommerce_currency();
        $hesabix_currency = get_option('ssbhesabix_hesabix_default_currency');

        if (!is_numeric($price)) $price = intval($price);

        if ($hesabix_currency == 'IRR' && $woocommerce_currency == 'IRT') $price *= 10;

        if ($hesabix_currency == 'IRT' && $woocommerce_currency == 'IRR') $price /= 10;

        return $price;
    }
//===========================================================================================================
    public static function getCategoryPath($id_category)
    {
        if (!isset($id_category)) return '';

        $path = get_term_parents_list($id_category, 'product_cat', array(
            'format' => 'name',
            'separator' => ':',
            'link' => false,
            'inclusive' => true,
        ));

        return substr('products: ' . $path, 0, -1);
    }
//===========================================================================================================
}