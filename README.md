### Connector to the [Epicor Prophet 21](https://www.epicor.com/en-us/industry-productivity-solutions/distribution/platforms/prophet-21/) for PHP 5.5 >=
 
The connector allows to communicate with the Epicor Prophet 21 through the REST API, realizing operations of obtaining and updating data. Using the connector it's easy to synchronize your CMS with the Prophet 21.

There are three classes here:

<b>epicor_connector</b> - low-level API. Allow to send queries and get a response. Before work it's necessary to setup credentials:
``` php
     /**
     * Open connection to Epicor
     */
    public function open_connection()
    {
        // TODO: Load username, password and entry point (https://p21.yourdomain.com:3443) from your config.
        $user_name = "";
        $password = "";
        $this->entry_point = ""; 
```

<b>epicor_api</b> - top-level API:
  * Get list of all views;
``` php
  public static function get_all_views()
```
  * Get list of all products;
``` php
  public static function get_all_products()
```
  * Get a specific product by SKU, update a product in the Prophet 21 with a data from CMS;
``` php
  public static function get_product($sku)
```
  * Get list of all orders;
``` php
  public static function get_all_orders()
```
  * Get a specific order and its line-items by an identifier, update an order in the Prophet 21 with a data from CMS;
``` php
  public static function get_order($epicor_order_id);
  public static function get_order_with_line_items($epicor_order_id);
  public static function set_order($epicor_order);
```
  * Get list of all contacts;
``` php
  public static function get_all_contacts()
```
  * Get a customer/contact by e-mail, update a customer in the Prophet 21 with data from CMS;
``` php
 public static function get_customer_by_email($email);
 public static function get_contact_by_email($email);
 public static function set_customer($cms_customer);
```
  * Get data by client addresses by an identifier.
``` php
 public static function get_address($address_id)
```

<b>epicor_data_converter</b> - used to convert data from your CMS data object to the Prophet 21 and vice versa.

#### Small tooltip about views:
  * p21_view_customer: view to process customers;
  * p21_view_contacts: view to process customers contacts;
  * p21_view_inv_mast: view to process products;
  * p21_view_oe_hdr: view to process orders;
  * p21_view_oe_line: view to process line items in orders (products, quantities).
