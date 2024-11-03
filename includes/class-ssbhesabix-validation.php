<?php

/*
 * @class      Ssbhesabix_Validation
 * @version    2.1.1
 * @since      1.1.5
 * @package    ssbhesabix
 * @subpackage ssbhesabix/includes
 * @author     Saeed Sattar Beglou <saeed.sb@gmail.com>
 * @author     HamidReza Gharahzadeh <hamidprime@gmail.com>
 * @author     Sepehr Najafi <sepehrn249@gmail.com>
 * @author     Babak Alizadeh <alizadeh.babak@gmail.com>
 */

class Ssbhesabix_Validation
{
    public static function itemCodeValidation($code)
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        $code = self::formatFarsiNumbers($code);
        return mb_substr($code, 0, 5);
    }
//=============================================================================================
    public static function itemNameValidation($name)
    {
        $name = self::formatFarsiNumbers($name);
        return self::remove_emoji($name, 199);
    }
//=============================================================================================
    public static function formatFarsiNumbers($str) {
        $farsiNumbers = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
        $englishNumbers = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];
        for ($i = 0; $i < 10; $i++)
            $str = str_replace($farsiNumbers[$i], $englishNumbers[$i], $str);
        return $str;
    }
//=============================================================================================
    public static function itemBarcodeValidation($barcode)
    {
        $barcode = self::formatFarsiNumbers($barcode);
        return mb_substr($barcode, 0, 999);
    }
//=============================================================================================
    public static function itemCategoryValidation($category) {return $category;}
//=============================================================================================
    public static function itemDescriptionValidation($description) {return mb_substr($description, 0, 199);}
//=============================================================================================
    public static function itemMainUnitValidation($mainUnit) {return mb_substr($mainUnit, 0, 29);}
//=============================================================================================
    public static function itemSubUnitValidation($subUnit) {return mb_substr($subUnit, 0, 29);}
//=============================================================================================
    public static function itemConversionFactorValidation($conversionFactor)
    {
        if ($conversionFactor < 0) {
            return 0;
        } else {
            return $conversionFactor;
        }
    }
//=============================================================================================
    public static function itemSalesTaxValidation($salesTax)
    {
        if ($salesTax >= 0 && $salesTax <= 100) {
            return $salesTax;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function itemSalesInfoValidation($salesInfo) {return mb_substr($salesInfo, 0, 99);}
//=============================================================================================
    public static function itemPurchaseCostValidation($purchaseCost)
    {
        if ($purchaseCost >= 0) {
            return $purchaseCost;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function itemPurchaseInfoValidation($purchaseInfo) {return mb_substr($purchaseInfo, 0, 99);}
//=============================================================================================
    public static function itemTagValidation($tag) {return mb_substr($tag, 0, 254);}
//=============================================================================================
    public static function contactCodeValidation($code)
    {
        $code = preg_replace('/[^0-9]/', '', $code);
        return mb_substr($code, 0, 5);
    }
//=============================================================================================
    public static function contactDisplayNameValidation($displayName) {return mb_substr($displayName, 0, 99);}
//=============================================================================================
    public static function contactCompanyValidation($company) {return mb_substr($company, 0, 99);}
//=============================================================================================
    public static function contactTitleValidation($title) {return mb_substr($title, 0, 49);}
//=============================================================================================
    public static function contactFirstNameValidation($firstName) {return mb_substr($firstName, 0, 49);}
//=============================================================================================
    public static function contactLastNameValidation($lastName) {return mb_substr($lastName, 0, 49);}
//=============================================================================================
    public static function contactAddressValidation($address) {return mb_substr($address, 0, 149);}
//=============================================================================================
    public static function contactCountryValidation($country) {return mb_substr($country, 0, 49);}
//=============================================================================================
    public static function contactStateValidation($state) {
        if ( is_numeric( $state ) ) {
            if(is_plugin_active("persian-woocommerce-shipping/woocommerce-shipping.php")) {
                $state = PWS()::get_state( $state );
            }
        }
        return mb_substr($state, 0, 49);
    }
//=============================================================================================
    public static function contactCityValidation($city) {
        if ( is_numeric( $city ) ) {
            if(is_plugin_active("persian-woocommerce-shipping/woocommerce-shipping.php")) {
                $city = PWS()::get_city($city);
            }
        }
        return mb_substr($city, 0, 49);
    }
//=============================================================================================
    public static function contactPostalCodeValidation($postalCode)
    {
        $postalCode = preg_replace('/[^0-9]/', '', $postalCode);
        $postalCode = self::formatFarsiNumbers($postalCode);
        return mb_substr($postalCode, 0, 10);
    }
//=============================================================================================
    public static function contactPhoneValidation($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $phone = self::formatFarsiNumbers($phone);
        return mb_substr($phone, 0, 14);
    }
//=============================================================================================
    public static function contactMobileValidation($mobile)
    {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        return mb_substr($mobile, 0, 14);
    }
//=============================================================================================
    public static function contactFaxValidation($fax)
    {
        $fax = preg_replace('/[^0-9]/', '', $fax);
        return mb_substr($fax, 0, 14);
    }
//=============================================================================================
    public static function contactEmailValidation($email)
    {
        $isValid = true;
        $atIndex = strrpos($email, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($email, $atIndex + 1);
            $local = substr($email, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if
            (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                str_replace("\\\\", "", $local))) {
                // character not valid in local part unless
                // local part is quoted
                if (!preg_match('/^"(\\\\"|[^"])+"$/',
                    str_replace("\\\\", "", $local))) {
                    $isValid = false;
                }
            }
        }

        if ($isValid) {
            return $email;
        } else {
            return null;
        }
    }
//=============================================================================================
    public static function contactWebsiteValidation($website) {return mb_substr($website, 0, 119);}
//=============================================================================================
    public static function contactNoteValidation($note) {return mb_substr($note, 0, 499);}
//=============================================================================================
    public static function contactCategoryValidation($category) {return $category;}
//=============================================================================================
    public static function contactTagValidation($tag) {return mb_substr($tag, 0, 254);}
//=============================================================================================
    public static function invoiceFinancialYearValidation($financialYear) {return $financialYear;}
//=============================================================================================
    public static function invoiceCurrencyRateValidation($currencyRate)
    {
        if ($currencyRate > 0) {
            return $currencyRate;
        } else {
            return 1;
        }
    }
//=============================================================================================
    public static function invoiceNumberValidation($number) {return mb_substr($number, 0, 49);}
//=============================================================================================
    public static function invoiceContactTitleValidation($contactTitle) {return mb_substr($contactTitle, 0, 199);}
//=============================================================================================
    public static function invoiceDueDateValidation($dueDate) {return $dueDate;}
//=============================================================================================
    public static function invoiceNoteValidation($note) {return mb_substr($note, 0, 499);}
//=============================================================================================
    public static function invoiceReferenceValidation($reference) {return mb_substr($reference, 0, 49);}
//=============================================================================================
    public static function invoiceTagValidation($tag) {return mb_substr($tag, 0, 254);}
//=============================================================================================
    public function invoiceItemsValidation($items) {return $items;}
//=============================================================================================
    public static function invoiceItemDescriptionValidation($description) {
        return self::remove_emoji($description, 249);
    }
//=============================================================================================
    public static function invoiceItemQuantityValidation($quantity)
    {
        if ($quantity > 0) {
            return $quantity;
        } else {
            return 1;
        }
    }
//=============================================================================================
    public static function invoiceItemUnitValidation($unit) {return mb_substr($unit, 0, 29);}
//=============================================================================================
    public static function invoiceItemUnitPriceValidation($unitPrice)
    {
        if ($unitPrice >= 0) {
            return $unitPrice;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function invoiceItemAmountValidation($amount)
    {
        if ($amount >= 0) {
            return $amount;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function invoiceItemDiscountValidation($discount)
    {
        if ($discount >= 0) {
            return $discount;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function invoiceItemTaxValidation($tax)
    {
        if ($tax >= 0) {
            return $tax;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function invoiceItemTotalAmountValidation($totalAmount)
    {
        if ($totalAmount >= 0) {
            return $totalAmount;
        } else {
            return 0;
        }
    }
//=============================================================================================
    public static function remove_emoji($string, $length = 200) : string
    {
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);

        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);

        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);

        $regex_misc = '/[\x{2600}-\x{26FF}\x{1F7E9}-\x{1F7EF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        $truncated_string = mb_substr($clear_string, 0, $length);

        return $truncated_string;
    }
//=============================================================================================
}
