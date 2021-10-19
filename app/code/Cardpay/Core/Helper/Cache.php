<?php

namespace Cardpay\Core\Helper;

use Magento\Framework\View\Element\Context;

/**
 * Class Cache
 * @package Cardpay\Core\Helper
 */
class Cache
{
    const PREFIX_KEY = 'MP_';

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cacheManager;

    /**
     * Cache constructor.
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->_cacheManager = $context->getCache();
    }

    /**
     * @param $key
     * @return string
     */
    public function getFromCache($key)
    {
        return $this->_cacheManager->load(self::PREFIX_KEY . $key);
    }

    /**
     * @param $key
     * @param $value
     * @param array $tags
     * @param int $lifetime (600 = 10 minutes)
     */
    public function saveCache($key, $value, $tags = [], $lifetime = 600)
    {
        $this->_cacheManager->save($value, self::PREFIX_KEY . $key, [], $lifetime);
    }

    /**
     * @param $key
     */
    public function removeFromCache($key)
    {
        $this->_cacheManager->remove(self::PREFIX_KEY . $key);
    }
}