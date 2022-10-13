<?php

namespace Cardpay\Core\Lib;

use Cardpay\Core\Exceptions\UnlimintBaseException;
use Exception;
use Magento\Framework\Exception\LocalizedException;

class Api
{
    private const AUTH_HEADER_PREFIX = 'Authorization: Bearer ';
    private const CONTENT_TYPE = 'application/json';

    /**
     * @var mixed
     */
    private $host;

    /**
     * @var mixed
     */
    private $terminalCode;

    /**
     * @var mixed
     */
    private $terminalPassword;

    /**
     * @var mixed
     */
    public $ll_access_token;

    /**
     * @var
     */
    private $accessData;

    /**
     * @var null
     */
    private $_platform;

    /**
     * @var null
     */
    private $_type;

    protected $_cpHelper;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $numberOfArguments = func_num_args();

        if ($numberOfArguments > 2 || $numberOfArguments < 1) {
            throw new UnlimintBaseException('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if ($numberOfArguments === 1) {
            $this->ll_access_token = func_get_arg(0);
        }

        if ($numberOfArguments === 2) {
            $this->terminalCode = func_get_arg(0);
            $this->terminalPassword = func_get_arg(1);
        }
    }

    /**
     * @throws Exception
     */
    public function getAccessToken()
    {
        if (isset ($this->ll_access_token) && !is_null($this->ll_access_token)) {
            return $this->ll_access_token;
        }

        $apiAuthParams = $this->buildQuery([
            'terminal_code' => $this->terminalCode,
            'password' => $this->terminalPassword,
            'grant_type' => 'password'
        ]);

        $authResponse = RestClient::post(
            $this->getHost() . '/api/auth/token',
            $apiAuthParams,
            'application/x-www-form-urlencoded'
        );

        if (!isset($authResponse['status'])) {
            throw new LocalizedException(__('Invalid auth response'));
        }

        if ((int)$authResponse['status'] !== 200) {
            $message = '';
            if (isset($authResponse['response']['message'])) {
                $message = $authResponse['response']['message'];
            }
            throw new UnlimintBaseException($message, $authResponse['status']);
        }

        $this->accessData = $authResponse['response'];

        $this->ll_access_token = $this->accessData['access_token'];

        return $this->ll_access_token;
    }

    /**
     * @throws Exception
     */
    public function refund($data)
    {
        return $this->post('/api/refunds', $data);
    }

    /**
     * @throws Exception
     */
    public function createParams($preference)
    {
        $access_token = $this->getAccessToken();

        $extra_params = [
            'platform: ' . $this->_platform, 'so;',
            'type: ' . $this->_type,
            self::AUTH_HEADER_PREFIX . $access_token
        ];

        return RestClient::post(
            $this->getHost() . '/checkout/preferences',
            $preference,
            self::CONTENT_TYPE,
            $extra_params
        );
    }

    /**
     * @throws Exception
     */
    public function get($uri, $params = null, $authenticate = true)
    {
        $params = is_array($params) ? $params : [];

        $access_token = null;
        if ($authenticate !== false) {
            $access_token = $this->getAccessToken();
        }

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, '?') === false) ? '?' : '&';
            $uri .= $this->buildQuery($params);
        }

        return RestClient::get($uri, null, [self::AUTH_HEADER_PREFIX . $access_token]);
    }

    /**
     * @throws Exception
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

    /**
     * @throws Exception
     */
    public function put($uri, $data, $params = null)
    {
        return RestClient::put(
            $this->getUriWithParams($uri, $params),
            $data,
            $this->buildExtraParams()
        );
    }

    /**
     * @throws Exception
     */
    public function delete($uri, $params = null)
    {
        return RestClient::delete(
            $this->getUriWithParams($uri, $params),
            null,
            $this->buildExtraParams()
        );
    }

    private function buildUrl($uri, $urlParams = null)
    {
        $url = $this->getHost() . $uri;

        $urlParams = is_array($urlParams) ? $urlParams : [];

        if (count($urlParams) > 0) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= $this->buildQuery($urlParams);
        }

        return $url;
    }

    private function buildExtraParams()
    {
        return [self::AUTH_HEADER_PREFIX . $this->getAccessToken()];
    }

    private function getUriWithParams($uri, $params = null)
    {
        $params = is_array($params) ? $params : [];

        $uri = $this->getHost() . $uri;

        if (count($params) > 0) {
            $uri .= (strpos($uri, '?') === false) ? '?' : '&';
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
        if (function_exists('http_build_query')) {
            return http_build_query($params);
        }

        $elements = [];
        foreach ($params as $name => $value) {
            $elements[] = "$name=" . urlencode($value);
        }

        return implode('&', $elements);
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
        $this->terminalCode = $terminalCode;
    }

    /**
     * @param null $terminalPassword
     */
    public function setTerminalPassword($terminalPassword)
    {
        $this->terminalPassword = $terminalPassword;
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

    public function setHelperData($cpHelper)
    {
        $this->_cpHelper = $cpHelper;
    }

    public function getHost()
    {
        return $this->host;
    }
}