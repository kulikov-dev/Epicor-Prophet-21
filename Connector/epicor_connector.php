<?php

namespace kulikov_dev\connectors\epicor;

use Exception;
use Generator;

/**
 * Class epicor_connector low-level API for Epicor
 * @package kulikov-dev\connectors\epicor
 */
class epicor_connector
{
    /**
     * @var int Limit of get all records query
     */
    private static $limit = 500;

    /**
     * @var string Authentication info
     * Appears after open connection
     */
    private $basic_auth;

    /**
     * @var string Entry point
     */
    private $entry_point;

    /**
     * @return bool Check if connected to Epicor
     */
    public function is_opened()
    {
        return !empty($this->basic_auth);
    }

    /**
     * Open connection to Epicor
     * Response result: {"response":{"token":"f7b67d1df17b9f439f7646e6ceaac3ae6931fba9f9c5e8a6b8ac"},"messages":[{"code":"0","message":"OK"}]}
     */
    public function open_connection()
    {
        // TODO: Load username, password and entry point (https://p21.yourdomain.com:3443) from your config.
        $user_name = "";
        $password = "";
        $this->entry_point = "";

        if (empty($user_name) || empty($this->entry_point)) {
            print('Initialize credentials and API entry point first.');
            return;
        }

        try {
            $header = [
                'Content-Type: application/json',
                'Accept: application/json',
                'username: ' . $user_name,
                'password: ' . $password,
            ];

            $api_url = $this->entry_point . '/api/security/token/';

            $respond_json = $this->send_request($api_url, $header, "{ }");
            if (!isset($respond_json) || $respond_json == null) {
                throw new Exception("Wrong response");
            }

            $response = json_decode($respond_json);
            if (empty($response) || !is_object($response) || !isset($response->AccessToken)) {
                throw new Exception("Wrong response");
            }

            $this->basic_auth = $response->AccessToken;
        } catch (Exception $exception) {
            print('Fatal Error during Epicor connection: ' . $exception->getMessage());
        }
    }

    /** Get table records based on query
     * @param string $search_url . Base URL
     * @param array $query . Query info to get records
     * @return array Array of table records
     */
    public function find_table_records($search_url, $query)
    {
        if (empty($this->basic_auth)) {
            print('Open Epicor connection before trying to find records.');
            return [];
        }

        try {
            $header = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->basic_auth
            ];
            $api_url = $this->entry_point . '/' . $search_url;

            $json_query = $query == null ? null : json_encode($query);
            $respond_json = $this->send_request($api_url, $header, $json_query);
            if (!isset($respond_json) || $respond_json == null) {
                return [];
            }

            $response = json_decode($respond_json);

            if (is_array($response)) {
                return $response;       // in case if epicor has few records.
            }

            if (empty($response) || !is_object($response)) {
                return [];
            }

            if (isset($response->ErrorType) && !empty($response->ErrorType)) {
                if ($response->ErrorType == 'P21.Common.Exceptions.NotFoundException') {
                    return [];
                }

                throw new Exception($response->ErrorMessage);
            }

            return [$response];
        } catch (Exception $exception) {
            print('Fatal Error during finding Epicor content: ' . $exception->getMessage());
        }

        return [];
    }

    /** Create or update record in Epicor database
     * @param string $search_url . Base URL
     * @param array $query . Query info of uploading records
     * @return array Epicor record array
     */
    public function upload_record($search_url, $query)
    {
        if (empty($this->basic_auth)) {
            print('Open Epicor connection before trying to find records.');
            return [];
        }

        try {
            $json_query = $query == null ? null : json_encode($query);
            $header = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->basic_auth,
                'Content-Length: ' . strlen($json_query)
            ];
            $api_url = $this->entry_point . '/' . $search_url;
            $respond_json = $this->send_request($api_url, $header, $json_query);

            if (!isset($respond_json) || $respond_json == null) {
                return [];
            }

            $response = json_decode($respond_json);

            if (empty($response) || !is_object($response)) {
                return [];
            }

            if (is_array($response)) {
                return $response;
            }

            return [$response];
        } catch (Exception $exception) {
            print('Fatal Error during uploading record to Epicor database: ' . $exception->getMessage());
        }

        return [];
    }

    /** Get rows count
     * @param $search_url . Base URL for table where we need to get rows
     * @return int|mixed . Rows count
     */
    public function get_rows_count($search_url)
    {
        if (empty($this->basic_auth)) {
            print('Open Epicor connection before trying to find records.');
            return 0;
        }

        try {
            $header = [
                'Authorization: Bearer ' . $this->basic_auth
            ];

            $api_url = $this->entry_point . '/' . $search_url . '/$count';

            $respond_json = $this->send_request($api_url, $header, null);
            if (!isset($respond_json) || $respond_json == null) {
                return 0;
            }

            return json_decode($respond_json);
        } catch (Exception $exception) {
            print('Fatal Error during counting Epicor content: ' . $exception->getMessage());
        }

        return 0;
    }

    /** Yield return all items from Epicor table
     * @param string $url path to table
     * @return Generator Array of items
     */
    public function get_all_items($url)
    {
        $total_count = self::get_rows_count($url);
        $counter = 0;
        $limit = epicor_connector::$limit;
        while ($counter < $total_count) {
            $api_url = $url . '?$skip=' . $counter . '&$top=' . $limit;
            $records = self::find_table_records($api_url, null);
            echo($counter . ' ');
            if ($records == null || empty($records)) {
                yield null;
            }

            $counter += $limit;
            $limit = $counter + $limit >= $total_count ? $total_count - $counter : $limit;
            yield $records;
        }
    }

    /** Send request to the website
     * @param string $url . Url
     * @param array $header_params . Header params
     * @param string $post_fields . Request data
     * @return mixed|string. Request result
     */
    private function send_request($url, $header_params, $post_fields = '')
    {
        $url_command = curl_init();
        try {
            curl_setopt($url_command, CURLOPT_URL, $url);
            curl_setopt($url_command, CURLOPT_HTTPHEADER, $header_params);
            curl_setopt($url_command, CURLOPT_SSL_VERIFYPEER, false);
            if (isset($post_fields) && $post_fields != '') {
                curl_setopt($url_command, CURLOPT_POSTFIELDS, $post_fields);
            }

            curl_setopt($url_command, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($url_command);
            curl_close($url_command);
            return $result;
        } catch (Exception $exception) {
            curl_close($url_command);
            return '';
        }
    }
}
