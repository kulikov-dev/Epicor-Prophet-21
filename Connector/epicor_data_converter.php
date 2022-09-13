<?php

namespace kulikov_dev\connectors\epicor;

/**
 * Class epicor_data_converter required to process data from your CMS data object to Epicor and vice versa.
 * (!) Need to adapt each CMS arrays for specific data model
 * @package kulikov_dev\connectors\epicor
 */
class epicor_data_converter
{
    /** Convert CMS order array to Epicor order array
     * @param array $cms_order_info CMS order array
     * @param array $cms_account_info CMS account array
     * @return array Epicor order info
     */
    public static function convert_order_to_epicor($cms_order_info, $cms_account_info)
    {
        $epicor_result = [];
        if (!is_null_or_empty_string($cms_order_info["EPICOR_ID"])) {
            $epicor_result["OrderNo"] = $cms_order_info["EPICOR_ID"];
        }

        $epicor_result["ShipToName"] = $cms_order_info["FULL_SHIP_TO"];

        $addresses = str_split($cms_order_info["SHIPADDRESS"], 50);
        $epicor_result["ShipToAddress1"] = isset($addresses[0]) ? $addresses[0] : "";
        $epicor_result["ShipToAddress2"] = isset($addresses[1]) ? $addresses[1] : "";
        $epicor_result["ShipToAddress3"] = isset($addresses[2]) ? $addresses[2] : "";

        $epicor_result["ShipToCity"] = $cms_order_info["SHIPCITY"] ?: "";
        $epicor_result["OeHdrShip2State"] = $cms_order_info["SHIPSTATE"] ?: "";
        $epicor_result["ZipCode"] = $cms_order_info["SHIPZIPCODE"] ?: "";
        $epicor_result["ShipToCountry"] = $cms_order_info["SHIPCOUNTRY"] ?: "";
        $epicor_result["ShipToPhone"] = $cms_order_info["SHIPPHONE"] ?: "";
        $epicor_result["ShipToMailAddress"] = $cms_order_info["SHIPEMAIL"] ?: "";

        $epicor_result["Taker"] = "Web";
        $epicor_result["Terms"] = "1";
        $epicor_result["PickTicketType"] = "PT";
        $epicor_result["CompanyId"] = $cms_order_info["COMPANY_ID"];
        $epicor_result["Approved"] = "false";

        $epicor_result["ContactId"] = $cms_account_info["EPICOR_CONTACT_ID"];
        $epicor_result["CustomerId"] = $cms_account_info["EPICOR_CUSTOMER_ID"];
        $epicor_result["ShipToId"] = $cms_account_info["EPICOR_CUSTOMER_ID"];

        return $epicor_result;
    }

    /** Convert CMS line items array to Epicor line items array
     * @param array $cms_line_items CMS line items
     * @return array Epicor line items
     */
    public static function convert_order_lineitems_to_epicor($cms_line_items)
    {
        $result = [];
        foreach ($cms_line_items as $line_item) {
            if (empty($line_item["EPICOR_SKU"])) {
                continue;           // Don't add or create product if we can't match it with epicor.
            }

            array_push($result, [
                "ItemId" => $line_item["EPICOR_SKU"],
                "UnitQuantity" => $line_item["QUANTITY"],
                "ExtendedDesc" => $line_item["SHORT_DESC"] ?: "",
                "UnitPrice" => $line_item["ITEM_PRICE"],
                "ManualPriceOveride" => "Y",
                "ExtendedPrice" => $line_item["ORIGINAL_PRICE"]
            ]);
        }

        return $result;
    }

    /** Convert Epicor product info to CMS product array
     * @param object $epicor_product_info Epicor product info
     * @param bool $update_specifications Flag, if need to update all product info, otherwise set only Epicor identifiers to update
     * @return array CMS product info
     */
    public static function convert_product_to_cms($epicor_product_info, $update_specifications = true)
    {
        $cms_result = [];
        $cms_result["EPICOR_ID"] = -1;
        if (!is_null_or_empty_string(@$epicor_product_info->inv_mast_uid)) {
            $cms_result["EPICOR_ID"] = $epicor_product_info->inv_mast_uid;
        } elseif (!is_null_or_empty_string(@$epicor_product_info->InvMastUid)) {
            $cms_result["EPICOR_ID"] = $epicor_product_info->InvMastUid;
        }

        $cms_result["EPICOR_SKU"] = '';
        if (!is_null_or_empty_string(@$epicor_product_info->item_id)) {
            $cms_result["EPICOR_SKU"] = $epicor_product_info->item_id;
        } elseif (!is_null_or_empty_string(@$epicor_product_info->ItemId)) {
            $cms_result["EPICOR_SKU"] = $epicor_product_info->ItemId;
        }

        if ($update_specifications) {
            $cms_result["WEIGHT"] = $epicor_product_info->Weight;
            $cms_result["PRICE"] = $epicor_product_info->Price1;

            $cms_result["NAME"] = 'N/A';
            if (!is_null_or_empty_string(@$epicor_product_info->ItemDesc)) {
                $cms_result["NAME"] = $epicor_product_info->ItemDesc;
            } elseif (!is_null_or_empty_string(@$epicor_product_info->item_desc)) {
                $cms_result["NAME"] = $epicor_product_info->item_desc;
            }

            $cms_result["TYPE"] = 'PROD';
            $cms_result["LONG_DESC"] = $epicor_product_info->ExtendedDesc;
            $cms_result["LENGTH"] = empty($epicor_product_info->Length) ? 0 : $epicor_product_info->Length;
            $cms_result["WIDTH"] = empty($epicor_product_info->Width) ? 0 : $epicor_product_info->Width;
            $cms_result["HEIGHT"] = empty($epicor_product_info->Height) ? 0 : $epicor_product_info->Height;
        }

        return $cms_result;
    }

    /** Convert CMS customer array to Epicor contact array
     * @param array $cms_customer_info CMS customer info
     * @return array Epicor contact info
     */
    public static function convert_customer_to_epicor_contact($cms_customer_info)
    {
        return [
            "FirstName" => $cms_customer_info["FNAME"],
            "LastName" => $cms_customer_info["LNAME"],
            "Title" => $cms_customer_info["THE_TITLE"],
            "DirectPhone" => $cms_customer_info["PHONE"],
            "FaxExt" => $cms_customer_info["FAX"],
            "Cellular" => is_null_or_empty_string($cms_customer_info["PHONE"]) ? $cms_customer_info["CELLPHONE"] : $cms_customer_info["PHONE"],
            "HomeAddress1" => $cms_customer_info["ADDR1"],
            "HomeAddress2" => $cms_customer_info["ADDR2"],
            "Url" => $cms_customer_info["URL"],
            "EmailAddress" => $cms_customer_info["EMAIL"],
        ];
    }

    /** Convert CMS customer array to Epicor address array
     * @param array $cms_customer_info CMS customer info
     * @return array Epicor address info
     */
    public static function convert_customer_to_epicor_address($cms_customer_info)
    {
        // In Epicor we have two tables for contact and for it's address. They linked and have duplicated data
        return [
            "MailAddress1" => $cms_customer_info["ADDR1"],
            "MailAddress2" => $cms_customer_info["ADDR2"],
            "MailCity" => $cms_customer_info["CITY"],
            "MailState" => $cms_customer_info["STATE"],
            "MailPostalCode" => $cms_customer_info["ZIPCODE"],
            "MailCountry" => $cms_customer_info["COUNTRY"],
            "CentralPhoneNumber" => empty($cms_customer_info["PHONE"]) ? $cms_customer_info["CELLPHONE"] : $cms_customer_info["PHONE"],
            "EmailAddress" => $cms_customer_info["EMAIL"],
            "Url" => $cms_customer_info["URL"],
            "Name" => isset($cms_customer_info["COMPANY"]) && $cms_customer_info["COMPANY"] <> "" ? $cms_customer_info["COMPANY"] : "Unknown",
        ];
    }

    /** Convert CMS customer array to Epicor customer array
     * @param array $cms_customer_info CMS customer info
     * @param object $contact_id Epicor contact identifier
     * @param object $address_id Epicor contact address identifier
     * @return array Epicor customer info
     */
    public static function convert_customer_to_epicor_customer($cms_customer_info, $contact_id, $address_id)
    {
        $epicor_result = epicor_api::create_empty_customer();
        $epicor_result["CompanyId"] = $cms_customer_info["COMPANY_ID"];
        $epicor_result["CustomerName"] = $cms_customer_info["FULL_NAME"];
        $epicor_result["SalesrepId"] = "5826";

        $epicor_result["TermsId"] = "8";       // Default terms = credit card
        $epicor_result["Taxable"] = "Y";

        $epicor_result["CustomerContacts"]["list"][0]["CompanyId"] = $cms_customer_info["COMPANY_ID"];
        $epicor_result["CustomerContacts"]["list"][0]["ContactId"] = $contact_id;

        $epicor_result["CustomerSalesreps"]["list"][0]["SalesrepId"] = "5826";
        $epicor_result["CustomerSalesreps"]["list"][0]["PrimarySalesrep"] = "Y";
        $epicor_result["CustomerSalesreps"]["list"][0]["CompanyId"] = $cms_customer_info["COMPANY_ID"];
        $epicor_result["CustomerSalesreps"]["list"][0]["CommissionPercentage"] = "100";

        $epicor_result["CustomerAddress"]["CorpAddressId"] = $address_id;
        $epicor_result["CustomerAddress"]["MailAddress1"] = $cms_customer_info["ADDR1"];
        $epicor_result["CustomerAddress"]["MailAddress2"] = $cms_customer_info["ADDR2"];
        $epicor_result["CustomerAddress"]["MailAddress3"] = "";
        $epicor_result["CustomerAddress"]["MailCity"] = $cms_customer_info["CITY"];
        $epicor_result["CustomerAddress"]["MailState"] = $cms_customer_info["STATE"];
        $epicor_result["CustomerAddress"]["MailPostalCode"] = $cms_customer_info["ZIPCODE"];
        $epicor_result["CustomerAddress"]["MailCountry"] = $cms_customer_info["COUNTRY"];
        $epicor_result["CustomerAddress"]["CentralPhoneNumber"] = is_null_or_empty_string($cms_customer_info["PHONE"]) ? $cms_customer_info["CELLPHONE"] : $cms_customer_info["PHONE"];
        $epicor_result["CustomerAddress"]["CentralFaxNumber"] = "";
        $epicor_result["CustomerAddress"]["Alternative1099Name"] = "";
        $epicor_result["CustomerAddress"]["PhysAddress1"] = $cms_customer_info["ADDR1"];
        $epicor_result["CustomerAddress"]["PhysAddress2"] = $cms_customer_info["ADDR2"];
        $epicor_result["CustomerAddress"]["PhysAddress3"] = "";
        $epicor_result["CustomerAddress"]["PhysCity"] = $cms_customer_info["CITY"];
        $epicor_result["CustomerAddress"]["PhysState"] = $cms_customer_info["STATE"];
        $epicor_result["CustomerAddress"]["PhysPostalCode"] = $cms_customer_info["ZIPCODE"];
        $epicor_result["CustomerAddress"]["PhysCountry"] = $cms_customer_info["COUNTRY"];
        $epicor_result["CustomerAddress"]["Incorporated"] = "";
        $epicor_result["CustomerAddress"]["EmailAddress"] = $cms_customer_info["EMAIL"];
        $epicor_result["CustomerAddress"]["Url"] = "";
        $epicor_result["CustomerAddress"]["Name"] = $cms_customer_info["FULL_NAME"];

        $epicor_result["CustomerShipTos"]["list"][0]["CompanyId"] = $cms_customer_info["COMPANY_ID"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToName"] = $cms_customer_info["FULL_NAME"];
        $epicor_result["CustomerShipTos"]["list"][0]["DefaultBranch"] = "00";
        $epicor_result["CustomerShipTos"]["list"][0]["TaxGroupId"] = "OUT STATE";
        $epicor_result["CustomerShipTos"]["list"][0]["TermsId"] = "8";
        $epicor_result["CustomerShipTos"]["list"][0]["InvoiceType"] = "IN";
        $epicor_result["CustomerShipTos"]["list"][0]["DefaultSourceLocationId"] = "100";
        $epicor_result["CustomerShipTos"]["list"][0]["PackingBasis"] = "Partial";
        $epicor_result["CustomerShipTos"]["list"][0]["PickTicketType"] = "PT";
        $epicor_result["CustomerShipTos"]["list"][0]["AcceptPartialOrders"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["ExcludeCanceldFromPickTix"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["ExcludeCanceldFromPackList"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["PrintPricesOnPackinglist"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["PrintPackinglistInShipping"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["UseCustomerUPSHandlingCharge"] = "Y";
        $epicor_result["CustomerShipTos"]["list"][0]["UpsHandlingCharge"] = "0.0";
        $epicor_result["CustomerShipTos"]["list"][0]["IncludeNonAllocOnPickTix"] = "1278";
        $epicor_result["CustomerShipTos"]["list"][0]["IncludeNonAllocOnPackList"] = "1278";
        $epicor_result["CustomerShipTos"]["list"][0]["ThirdPartyBillingFlag"] = "S";
        $epicor_result["CustomerShipTos"]["list"][0]["SendOutsideUseDocs"] = "N";
        $epicor_result["CustomerShipTos"]["list"][0]["SendOutsideUsePrint"] = "N";
        $epicor_result["CustomerShipTos"]["list"][0]["SendOutsideUseFax"] = "N";
        $epicor_result["CustomerShipTos"]["list"][0]["HandlingCharge"] = "N";
        $epicor_result["CustomerShipTos"]["list"][0]["SalesrepId"] = "5826";
        $epicor_result["CustomerShipTos"]["list"][0]["DefaultCarrierId"] = "10002";

        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["CorpAddressId"] = $address_id;
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailAddress1"] = $cms_customer_info["ADDR1"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailAddress2"] = $cms_customer_info["ADDR2"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailAddress3"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailCity"] = $cms_customer_info["CITY"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailState"] = $cms_customer_info["STATE"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailPostalCode"] = $cms_customer_info["ZIPCODE"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["MailCountry"] = $cms_customer_info["COUNTRY"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["CentralPhoneNumber"] = is_null_or_empty_string($cms_customer_info["PHONE"]) ? $cms_customer_info["CELLPHONE"] : $cms_customer_info["PHONE"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["CentralFaxNumber"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["Alternative1099Name"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysAddress1"] = $cms_customer_info["ADDR1"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysAddress2"] = $cms_customer_info["ADDR2"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysAddress3"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysCity"] = $cms_customer_info["CITY"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysState"] = $cms_customer_info["STATE"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysPostalCode"] = $cms_customer_info["ZIPCODE"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["PhysCountry"] = $cms_customer_info["COUNTRY"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["Incorporated"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["EmailAddress"] = $cms_customer_info["EMAIL"];
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["Url"] = "";
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["AddressId"] = $address_id;
        $epicor_result["CustomerShipTos"]["list"][0]["ShipToAddress"]["Name"] = $cms_customer_info["FULL_NAME"];

        return $epicor_result;
    }

    /** Convert Epicor customer info to CMS customer array
     * @param object $epicor_customer_info Customer info from Epicor
     * @return array CMS customer info
     */
    public static function convert_customer_to_cms($epicor_customer_info)
    {
        $cms_result = [];
        $cms_result["EPICOR_CONTACT_ID"] = $epicor_customer_info->id;
        $cms_result["EPICOR_ADDRESS_ID"] = $epicor_customer_info->address_id;
        $cms_result["FNAME"] = $epicor_customer_info->first_name;
        $cms_result["LNAME"] = $epicor_customer_info->last_name;
        $cms_result["THE_TITLE"] = $epicor_customer_info->title ?: "";
        $cms_result["PHONE"] = $epicor_customer_info->direct_phone ?: "";
        $cms_result["FAX"] = $epicor_customer_info->fax_ext ?: "";
        $cms_result["CELLPHONE"] = $epicor_customer_info->cellular ?: "";
        $cms_result["ADDR1"] = $epicor_customer_info->home_address1 ?: "";
        $cms_result["ADDR2"] = $epicor_customer_info->home_address2 ?: "";
        $cms_result["URL"] = $epicor_customer_info->url ?: "";
        $cms_result["EMAIL"] = $epicor_customer_info->email_address ?: "";

        $cms_result["EPICOR_CUSTOMER_ID"] = '';
        $record = epicor_api::get_link_by_contact($cms_result["EPICOR_CONTACT_ID"]);
        if (!empty($record)) {
            $cms_result["EPICOR_CUSTOMER_ID"] = $record->value[0]->link_id;
        } else {
            die('Contact #' . $cms_result["EPICOR_CONTACT_ID"] . ', email: ' . $cms_result["EMAIL"] . ' doesnt link with any customer.');
        }

        $address_record = epicor_api::get_address($epicor_customer_info->address_id);
        if (!empty($address_record)) {
            $cms_result["CITY"] = $address_record->MailCity;
            $cms_result["STATE"] = $address_record->MailState;
            $cms_result["ZIPCODE"] = $address_record->MailPostalCode;
            $cms_result["COUNTRY"] = $address_record->MailCountry;
            $cms_result["COMPANY"] = $address_record->Name;
            if (empty($cms_result["ADDR1"])) {
                $cms_result["ADDR1"] = $address_record->MailAddress1;
            }

            if (empty($cms_result["ADDR2"])) {
                $cms_result["ADDR2"] = $address_record->MailAddress2;
            }

            if (empty($cms_result["PHONE"])) {
                $cms_result["PHONE"] = $address_record->CentralPhoneNumber;
            }
        }

        if (empty($cms_result["COUNTRY"])) {
            $cms_result["COUNTRY"] = "USA";
        }

        return $cms_result;
    }
}
