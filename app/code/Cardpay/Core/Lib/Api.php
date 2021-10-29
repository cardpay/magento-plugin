<?php

namespace Cardpay\Core\Lib;

use Exception;

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
    private $_platform;

    /**
     * @var null
     */
    private $_type;

    protected $_cpHelper;

    public function __construct()
    {
        $i = func_num_args();

        if ($i > 2 || $i < 1) {
            throw new Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if ($i === 1) {
            $this->ll_access_token = func_get_arg(0);
        }

        if ($i === 2) {
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
    public function getAccessToken()
    {
        if (isset ($this->ll_access_token) && !is_null($this->ll_access_token)) {
            return $this->ll_access_token;
        }

        $app_client_values = $this->buildQuery([
            'terminal_code' => $this->terminal_code,
            'password' => $this->terminal_password,
            'grant_type' => 'password'
        ]);

        $authResponse = RestClient::post($this->getHost() . "/api/auth/token", $app_client_values, "application/x-www-form-urlencoded");
        if ((int)$authResponse['status'] !== 200) {
            $message = '';
            if (isset($authResponse['response']['message'])) {
                $message = $authResponse['response']['message'];
            }
            throw new Exception($message, $authResponse['status']);
        }

        $this->access_data = $authResponse['response'];

        $this->ll_access_token = $this->access_data['access_token'];

        return $this->ll_access_token;
    }

    public function performRefund($data)
    {
        return $this->post("/api/refunds", $data);
    }

    public function createParams($preference)
    {
        $access_token = $this->getAccessToken();

        $extra_params = [
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            self::AUTH_HEADER_PREFIX . $access_token
        ];

        return RestClient::post($this->getHost() . "/checkout/preferences", $preference, self::CONTENT_TYPE, $extra_params);
    }

    public function checkDiscountCampaigns($transaction_amount, $payer_email, $coupon_code)
    {
        $accessToken = $this->getAccessToken();
        $url = $this->getHost() . "/discount_campaigns?transaction_amount=$transaction_amount&payer_email=$payer_email&coupon_code=$coupon_code";

        return RestClient::get($url, null, [self::AUTH_HEADER_PREFIX . $accessToken]);
    }

    public function get($uri, $params = null, $authenticate = true)
    {
        $params = is_array($params) ? $params : [];

        $access_token = null;
        if ($authenticate !== false) {
            $access_token = $this->getAccessToken();
        }

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->buildQuery($params);
        }

        return RestClient::get($uri, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

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

        $urlParams = is_array($urlParams) ? $urlParams : [];

        if (count($urlParams) > 0) {
            $url .= (strpos($url, "?") === false) ? "?" : "&";
            $url .= $this->buildQuery($urlParams);
        }

        return $url;
    }

    private function buildExtraParams()
    {
        return [self::AUTH_HEADER_PREFIX . $this->getAccessToken()];
    }

    public function put($uri, $data, $params = null)
    {
        return RestClient::put(
            $this->getUriWithParams($uri, $params),
            $data,
            $this->buildExtraParams()
        );
    }

    public function delete($uri, $params = null)
    {
        return RestClient::delete(
            $this->getUriWithParams($uri, $params),
            null,
            $this->buildExtraParams()
        );
    }

    private function getUriWithParams($uri, $params = null) {
        $params = is_array($params) ? $params : [];

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->buildQuery($params);
        }

        return $uri;
    }

    /**
     * @param $params
     *
     * @return string
     */
    private function buildQuery($params)
    {
        if (function_exists("http_build_query")) {
            return http_build_query($params);
        }

        $elements = [];
        foreach ($params as $name => $value) {
            $elements[] = "$name=" . urlencode($value);
        }

        return implode("&", $elements);
    }

    /**
     * @param null $host
     */
    public function setHost($host)
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
    public function setPlatform($platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @param null $type
     */
    public function setType($type)
    {
        $this->_type = $type;
    }
}