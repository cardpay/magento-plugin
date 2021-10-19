<?php

namespace Cardpay\Core\Lib;

use Exception;

/**
 * Unlimint Integration Library
 * Access Unlimint for payments integration
 */
class Api
{
    const VERSION = "0.3.3";
    const AUTH_HEADER_PREFIX = 'Authorization: Bearer ';
    const CONTENT_TYPE = 'application/json';

    /**
     * @var mixed
     */
    private $host;

    /**
     * @var mixed
     */
    private $terminal_code;

    /**
     * @var mixed
     */
    private $terminal_password;

    /**
     * @var mixed
     */
    public $ll_access_token;

    /**
     * @var
     */
    private $access_data;

    /**
     * @var null
     */
    private $_platform = null;

    /**
     * @var null
     */
    private $_so = null;

    /**
     * @var null
     */
    private $_type = null;

    protected $_cpHelper;

    /**
     * \Cardpay\Core\Lib\Api constructor
     */
    public function __construct()
    {
        $i = func_num_args();

        if ($i > 2 || $i < 1) {
            throw new Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if ($i == 1) {
            $this->ll_access_token = func_get_arg(0);
        }

        if ($i == 2) {
            $this->terminal_code = func_get_arg(0);
            $this->terminal_password = func_get_arg(1);
        }
    }

    public function setHelperData($cpHelper)
    {
        $this->_cpHelper = $cpHelper;
    }

    /**
     * Get Access Token for API use
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get Access Token for API use
     */
    public function get_access_token()
    {
        if (isset ($this->ll_access_token) && !is_null($this->ll_access_token)) {
            return $this->ll_access_token;
        }

        $app_client_values = $this->build_query(array(
            'terminal_code' => $this->terminal_code,
            'password' => $this->terminal_password,
            'grant_type' => 'password'
        ));

        $authResponse = RestClient::post($this->getHost() . "/api/auth/token", $app_client_values, "application/x-www-form-urlencoded");

        if ($authResponse["status"] != 200) {
            throw new Exception ($authResponse['response']['message'], $authResponse['status']);
        }

        $this->access_data = $authResponse['response'];

        $this->ll_access_token = $this->access_data['access_token'];

        return $this->ll_access_token;
    }

    /**
     * Refund accredited payment
     *
     * @param int $id
     * @return array(json)
     */
    public function performRefund($data)
    {
        return $this->post("/api/refunds", $data);
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     */
    public function createParams($preference)
    {
        $access_token = $this->get_access_token();

        $extra_params = array(
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            self::AUTH_HEADER_PREFIX . $access_token
        );

        return RestClient::post($this->getHost() . "/checkout/preferences", $preference, self::CONTENT_TYPE, $extra_params);
    }

    public function check_discount_campaigns($transaction_amount, $payer_email, $coupon_code)
    {
        $access_token = $this->get_access_token();
        $url = $this->getHost() . "/discount_campaigns?transaction_amount=$transaction_amount&payer_email=$payer_email&coupon_code=$coupon_code";

        return RestClient::get($url, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

    /* Generic resource call methods */

    /**
     * Generic resource get
     * @param uri
     * @param params
     * @param authenticate = true
     */
    public function get($uri, $params = null, $authenticate = true)
    {
        $params = is_array($params) ? $params : array();

        $access_token = null;
        if ($authenticate !== false) {
            $access_token = $this->get_access_token();
        }

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::get($uri, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

    /**
     * Generic resource post
     * @param uri
     * @param data
     * @param params
     */
    public function post($uri, $data, $urlParams = null)
    {
        $url = $this->buildUrl($uri, $urlParams);
        $extraParams = $this->buildExtraParams();

        return RestClient::post($url, $data, self::CONTENT_TYPE, $extraParams);
    }

    public function patch($uri, $data, $urlParams = null)
    {
        $url = $this->buildUrl($uri, $urlParams);
        $extraParams = $this->buildExtraParams();

        return RestClient::patch($url, $data, self::CONTENT_TYPE, $extraParams);
    }

    private function buildUrl($uri, $urlParams = null)
    {
        $url = $this->getHost() . $uri;

        $urlParams = is_array($urlParams) ? $urlParams : array();

        if (count($urlParams) > 0) {
            $url .= (strpos($url, "?") === false) ? "?" : "&";
            $url .= $this->build_query($urlParams);
        }

        return $url;
    }

    private function buildExtraParams()
    {
        return array(self::AUTH_HEADER_PREFIX . $this->get_access_token());
    }

    /**
     * Generic resource put
     * @param uri
     * @param data
     * @param params
     */
    public function put($uri, $data, $params = null)
    {
        $params = is_array($params) ? $params : array();

        $access_token = $this->get_access_token();

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::put($uri, $data, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

    /**
     * Generic resource delete
     * @param uri
     * @param data
     * @param params
     */
    public function delete($uri, $params = null)
    {
        $params = is_array($params) ? $params : array();

        $access_token = $this->get_access_token();

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        return RestClient::delete($uri, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

    /**
     * @param $params
     *
     * @return string
     */
    private function build_query($params)
    {
        if (function_exists("http_build_query")) {
            return http_build_query($params);
        } else {
            $elements = [];
            foreach ($params as $name => $value) {
                $elements[] = "$name=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

    /**
     * @param null $host
     */
    public function set_host($host)
    {
        $this->host = $host;
    }

    /**
     * @param null $terminalCode
     */
    public function setTerminalCode($terminalCode)
    {
        $this->terminal_code = $terminalCode;
    }

    /**
     * @param null $terminalPassword
     */
    public function setTerminalPassword($terminalPassword)
    {
        $this->terminal_password = $terminalPassword;
    }

    /**
     * @param null $platform
     */
    public function set_platform($platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @param null $so
     */
    public function set_so($so = '')
    {
        $this->_so = $so;
    }

    /**
     * @param null $type
     */
    public function set_type($type)
    {
        $this->_type = $type;
    }
}