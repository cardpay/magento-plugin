<?php

namespace Cardpay\Core\Lib;

use Cardpay\Core\Helper\Data;
use function curl_close;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use const CURLINFO_HTTP_CODE;
use Exception;
use const false;
use function json_decode;
use const true;

class RestClient
{
    private const CONTENT_TYPE = 'application/json';
    private const USER_AGENT = 'UnlimintPlugin/1.0.5/Magento';

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
    private static function getConnection($uri, $method, $contentType, $extraParams = [])
    {
        if (!extension_loaded('curl')) {
            throw new Exception('cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.');
        }

        $connection = curl_init($uri);

        curl_setopt($connection, CURLOPT_USERAGENT, self::USER_AGENT);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connection, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connection, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $headers = ['Accept: ' . self::CONTENT_TYPE, 'Content-Type: ' . $contentType];

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
    private static function setData($connect, $data, $contentType)
    {
        if ((string)$contentType === self::CONTENT_TYPE) {
            if (is_string($data)) {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if (function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error !== JSON_ERROR_NONE) {
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
    protected static function exec($method, $url, $data, $contentType, $extraParams)
    {
        if (empty($url) || strncmp($url, 'http', 4) !== 0) {
            return [];
        }

        $connection = self::getConnection($url, $method, $contentType, $extraParams);
        if (!empty($data)) {
            self::setData($connection, $data, $contentType);
        }

        $apiResult = curl_exec($connection);
        $apiHttpCode = curl_getinfo($connection, CURLINFO_HTTP_CODE);
        curl_close($connection);

        if ($apiResult === false) {
            throw new Exception(curl_error($connection));
        }

        $response = [
            'status' => $apiHttpCode,
            'response' => json_decode($apiResult, true)
        ];

        if ((int)$response['status'] >= 400 && self::$checkLoop === 0) {
            self::logErrorResponse($response, $data, $url);
        }

        self::$checkLoop = 0;

        return $response;
    }

    /**
     * @param $url
     * @param $method
     * @param $contentType
     * @param $extraParams
     * @param $data
     * @return array
     * @throws Exception
     */

    /**
     * @param array $response
     * @param $data
     * @param $url
     */
    private static function logErrorResponse(array $response, $data, $url)
    {
        try {
            self::$checkLoop = 1;
            $payloads = null;
            $endpoint = null;
            $errors = [];

            // add data
            if (!empty($data)) {
                $payloads = json_encode(self::maskCardData($data));
            }

            // add uri
            if (!empty($url)) {
                $endpoint = $url;
            }

            $errors[] = [
                'endpoint' => $endpoint,
                'message' => self::getErrorMessage($response),
                'payloads' => $payloads
            ];

            self::logError($response['status'], $errors);
        } catch (Exception $e) {
            error_log('error to call API LOGS' . $e);
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
                } elseif (is_array($responseCause)) {
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
    public static function get($uri, $content_type = self::CONTENT_TYPE, $extra_params = [])
    {
        return self::exec('GET', $uri, null, $content_type, $extra_params);
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
    public static function post($url, $data, $content_type = self::CONTENT_TYPE, $extra_params = [])
    {
        return self::exec('POST', $url, $data, $content_type, $extra_params);
    }

    public static function patch($url, $data, $content_type = self::CONTENT_TYPE, $extra_params = [])
    {
        return self::exec('PATCH', $url, $data, $content_type, $extra_params);
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
    public static function put($uri, $data, $content_type = self::CONTENT_TYPE, $extra_params = [])
    {
        return self::exec('PUT', $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array $extra_params
     *
     * @return array
     * @throws Exception
     */
    public static function delete($uri, $content_type = self::CONTENT_TYPE, $extra_params = [])
    {
        return self::exec('DELETE', $uri, null, $content_type, $extra_params);
    }

    /**
     * Error implementation tracking
     */
    public static $module_version = "";
    public static $url_store = "";
    public static $email_admin = "";
    public static $country_initial = "";
    public static $sponsor_id = "";
    public static $checkLoop = 0;

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

        $data = [
            'code' => $code,
            'errors' => $errors,
            'module' => self::PLATFORM_ID,
            'module_version' => self::$module_version,
            'url_store' => self::$url_store,
            'country_initial' => self::$country_initial,
            'server_version' => $server_version,
            'code_lang' => 'PHP ' . $php_version
        ];

        self::$_cpHelper->log('sendErrorLog', 'cardpay-restclient', $data);
    }
}
