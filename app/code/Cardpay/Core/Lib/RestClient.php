<?php

namespace Cardpay\Core\Lib;

use Cardpay\Core\Helper\Data;
use Exception;

class RestClient
{
    private const CONTENT_TYPE = 'application/json';
    private const USER_AGENT = 'UnlimintPlugin/1.0.3/Magento';

    /**
     * @var Data
     */
    protected static $_cpHelper;

    /**
     * Platform Id
     */
    private const PLATFORM_ID = 'Magento2';

    /**
     * @param       $uri
     * @param       $method
     * @param       $contentType
     * @param array $extraParams
     *
     * @return resource
     * @throws Exception
     */
    private static function getConnection($uri, $method, $contentType, $extraParams = array())
    {
        if (!extension_loaded("curl")) {
            throw new Exception("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }

        $connection = curl_init($uri);

        curl_setopt($connection, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connection, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $headers = array("Accept: " . self::CONTENT_TYPE, "Content-Type: " . $contentType);

        if (count($extraParams) > 0) {
            $headers = array_merge($headers, $extraParams);
        }

        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        return $connection;
    }

    /**
     * @param $connect
     * @param $data
     * @param $contentType
     *
     * @throws Exception
     */
    private static function set_data(&$connect, $data, $contentType)
    {
        if ($contentType == self::CONTENT_TYPE) {
            if (is_string($data)) {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if (function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new Exception("JSON Error [$json_error] - Data: $data");
                }
            }
        }

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param $method
     * @param $url
     * @param $data
     * @param $contentType
     * @param $extraParams
     *
     * @return array
     * @throws Exception
     */
    private static function exec($method, $url, $data, $contentType, $extraParams)
    {
        $connect = self::getConnection($url, $method, $contentType, $extraParams);
        if ($data) {
            self::set_data($connect, $data, $contentType);
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_result === FALSE) {
            throw new Exception(curl_error($connect));
        }

        $response = array(
            'status' => $api_http_code,
            'response' => json_decode($api_result, true)
        );

        if ($response != null && $response['status'] >= 400 && self::$check_loop == 0) {
            self::logErrorResponse($response, $data, $url);
        }

        self::$check_loop = 0;
        curl_close($connect);

        return $response;
    }

    /**
     * @param array $response
     * @param $data
     * @param $url
     */
    private static function logErrorResponse(array $response, $data, $url)
    {
        try {
            self::$check_loop = 1;
            $payloads = null;
            $endpoint = null;
            $errors = array();

            //add data
            if (isset($data) && $data != null) {
                $payloads = json_encode(self::maskCardData($data));
            }

            //add uri
            if (isset($url) && $url != null) {
                $endpoint = $url;
            }

            $errors[] = array(
                "endpoint" => $endpoint,
                "message" => self::getErrorMessage($response),
                "payloads" => $payloads
            );

            self::logError($response['status'], $errors);
        } catch (Exception $e) {
            error_log("error to call API LOGS" . $e);
        }
    }

    private static function getErrorMessage($response)
    {
        $message = null;

        if (isset($response['response'])) {
            $responseSection = $response['response'];
            if (isset($responseSection['message'])) {
                $message = $responseSection['message'];
            }

            if (isset($responseSection['cause'])) {
                $responseCause = $responseSection['cause'];
                if (isset($responseCause['code'], $responseCause['description'])) {
                    $message .= ' - ' . $responseCause['code'] . ': ' . $responseCause['description'];
                } else if (is_array($responseCause)) {
                    foreach ($responseCause as $cause) {
                        $message .= ' - ' . $cause['code'] . ': ' . $cause['description'];
                    }
                }
            }
        }

        return $message;
    }

    private static function maskCardData($data)
    {
        if (isset($data['card_account']['card']['pan'])) {
            $data['card_account']['card']['pan'] = self::$_cpHelper->maskSensitiveInfo($data['card_account']['card']['pan']);
        }

        if (isset($data['card_account']['card']['security_code'])) {
            $data['card_account']['card']['security_code'] = '...';
        }

        return $data;
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function get($uri, $content_type = self::CONTENT_TYPE, $extra_params = array())
    {
        return self::exec("GET", $uri, null, $content_type, $extra_params);
    }

    /**
     * @param        $url
     * @param        $data
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function post($url, $data, $content_type = self::CONTENT_TYPE, $extra_params = array())
    {
        return self::exec("POST", $url, $data, $content_type, $extra_params);
    }

    public static function patch($url, $data, $content_type = self::CONTENT_TYPE, $extra_params = array())
    {
        return self::exec("PATCH", $url, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param        $data
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function put($uri, $data, $content_type = self::CONTENT_TYPE, $extra_params = array())
    {
        return self::exec("PUT", $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function delete($uri, $content_type = self::CONTENT_TYPE, $extra_params = array())
    {
        return self::exec("DELETE", $uri, null, $content_type, $extra_params);
    }

    /**
     * Error implementation tracking
     */
    static $module_version = "";
    static $url_store = "";
    static $email_admin = "";
    static $country_initial = "";
    static $sponsor_id = "";
    static $check_loop = 0;

    public static function setHelperData($cpHelper)
    {
        self::$_cpHelper = $cpHelper;
    }

    public static function setSponsorID($sponsor_id)
    {
        self::$sponsor_id = $sponsor_id;
    }

    public static function setModuleVersion($module_version)
    {
        self::$module_version = $module_version;
    }

    public static function setUrlStore($url_store)
    {
        self::$url_store = $url_store;
    }

    public static function setEmailAdmin($email_admin)
    {
        self::$email_admin = $email_admin;
    }

    public static function setCountryInitial($country_initial)
    {
        self::$country_initial = $country_initial;
    }

    public static function logError($code, $errors)
    {
        $server_version = php_uname();
        $php_version = PHP_VERSION;

        $data = array(
            "code" => $code,
            "errors" => $errors,
            "module" => self::PLATFORM_ID,
            "module_version" => self::$module_version,
            "url_store" => self::$url_store,
            "country_initial" => self::$country_initial,
            "server_version" => $server_version,
            "code_lang" => "PHP " . $php_version
        );

        self::$_cpHelper->log('sendErrorLog', 'cardpay-restclient', $data);
    }
}