<?php

namespace kulikov-dev\connectors\epicor;

use Generator;

/**
 * Class epicor_api top-level API for Epicor
 * @package kulikov-dev\connectors\epicor
 * (!) Epicor doesn't support standard Odata string functions like toupper, tolower :(
 */
class epicor_api
{
    /**
     * @var epicor_connector Connector to Epicor
     */
    private static $connector;

    /** Get an Epicor order information by an identifier
     * @param string $epicor_order_id Epicor order unique identifier
     * @return object Epicor order information
     */
    public static function get_order($epicor_order_id)
    {
        if (is_null_or_empty_string($epicor_order_id)) {
            return null;
        }

        self::check_connection();
        $order_record = self::$connector->find_table_records('api/sales/orders/' . $epicor_order_id . '?extendedproperties=*', null);
        if (!empty($order_record)) {
            return $order_record[0];
        }

        return null;
    }

    /** Upload information about a product to Epicor
     * @param array $epicor_order Product information in Epicor format
     * @return object Created product information
     */
    public static function set_order($epicor_order)
    {
        self::check_connection();

        $search_url = 'api/sales/orders/';
        $records = self::$connector->upload_record($search_url, $epicor_order);
        return $records[0];
    }

    /** Get array fields for a new customer
     * @return array Customer fields
     */
    public static function create_empty_customer()
    {
        self::check_connection();

        $search_url = 'api/entity/customers/new';
        return self::$connector->find_table_records($search_url, null);
    }

    /** Get all contacts from Epicor
     * @return Generator Contacts yield array
     */
    public static function get_all_contacts()
    {
        self::check_connection();

        $indexer = self::$connector->get_all_items('data/erp/views/v1/p21_view_contacts');
        foreach ($indexer as $value) {
            yield $value;
        }
    }

    /** Get Epicor link information between customer and contact by contact identifier
     * @param string $contact_id Epicor contact unique identifier
     * @return object Link info
     */
    public static function get_link_by_contact($contact_id)
    {
        self::check_connection();

        $search_url = 'data/erp/views/v1/p21_view_contacts_x_links?$filter=' . rawurlencode('trim(id) eq \'' . $contact_id . '\'');
        $records = self::$connector->find_table_records($search_url, null);
        if (!empty($records) && !empty($records[0]->value)) {
            return $records[0];
        }

        return null;
    }

    /** Get Epicor link information between customer and contact by customer identifier
     * @param string $customer_id Epicor customer unique identifier
     * @return object Link information
     */
    public static function get_link_by_customer($customer_id)
    {
        self::check_connection();

        $search_url = 'data/erp/views/v1/p21_view_contacts_x_links?$filter=' . rawurlencode('trim(link_id) eq \'' . $customer_id . '\'');
        $records = self::$connector->find_table_records($search_url, null);
        if (!empty($records) && !empty($records[0]->value)) {
            return $records[0];
        }

        return null;
    }

    /** Get a customer information from Epicor matched by email
     * @param string $email Email
     * @return object|null Epicor customer array
     */
    public static function get_customer_by_email($email)
    {
        return self::get_entity_by_email($email, 'customers');
    }

    /** Get a contact information from Epicor matched by email
     * @param string $email Email
     * @return object|null Epicor customer array
     */
    public static function get_contact_by_email($email)
    {
        return self::get_entity_by_email($email, 'contacts');
    }

    /** Get address information by an identifier
     * @param string $address_id Epicor address unique identifier
     * @return object Address information
     */
    public static function get_address($address_id)
    {
        self::check_connection();

        $address_record = self::$connector->find_table_records('api/entity/addresses/?$query=' . rawurlencode('id eq \'' . $address_id . '\''), null);
        if (!empty($address_record)) {
            return $address_record[0];
        }

        return null;
    }

    /** Get all products from Epicor
     * @return Generator Products yield array
     */
    public static function get_all_products()
    {
        self::check_connection();

        $indexer = self::$connector->get_all_items('data/erp/views/v1/p21_view_inv_mast');
        foreach ($indexer as $value) {
            yield $value;
        }
    }

    /** Get product information by SKU
     * @param string $sku Product SKU
     * @return object Product information
     */
    public static function get_product($sku)
    {
        self::check_connection();

        $search_url = 'api/inventory/parts/' . strval($sku);
        $records = self::$connector->find_table_records($search_url, null);
        if (empty($records)) {
            return null;
        }

        return $records[0];
    }

    /**
     * Check connection to Epicor and initialize it if necessary
     */
    private static function check_connection()
    {
        if (self::$connector == null || !self::$connector->is_opened()) {
            self::initialize_epicor_connection();
        }
    }

    /**
     * Initialize connection to Epicor
     */
    private static function initialize_epicor_connection()
    {
        self::$connector = new epicor_connector();
        self::$connector->open_connection();

        if (self::$connector == null || !self::$connector->is_opened()) {
            die('Initialize Epicor connection before work with it.');
        }
    }

    /** Get account entity from Epicor matched by email
     * @param string $email Email
     * @param string $entity_type Epicor account entity type (customer or contact)
     * @return object|null Epicor account entity
     */
    private static function get_entity_by_email($email, $entity_type)
    {
        self::initialize_epicor_connection();

        $email = trim($email);
        $search_url = 'api/entity/' . $entity_type . '/?$query=' . rawurlencode('email_address eq \'' . $email . '\' or email_address eq \'' . strtolower($email) . '\'');
        $records = self::$connector->find_table_records($search_url, null);
        if (!empty($records)) {
            return $records[0];
        }

        return null;
    }
}