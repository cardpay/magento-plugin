<?php

namespace Cardpay\Core\Model\System\Config\Source;

use Cardpay\Core\Helper\Data;
use Cardpay\Core\Lib\RestClient;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class Category
 *
 * @package Cardpay\Core\Model\System\Config\Source
 */
class Category implements ArrayInterface
{
    /**
     * @var Data
     */
    protected $coreHelper;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $coreHelper
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Data $coreHelper)
    {
        $this->scopeConfig = $scopeConfig;
        $this->coreHelper = $coreHelper;
    }

    /**
     * Return key sorted shop item categories
     * @return array
     */
    public function toOptionArray()
    {
        try {
            $terminalPassword = $this->coreHelper->getTerminalPassword();
            $response = RestClient::get("/item_categories", null, ["Authorization: Bearer " . $terminalPassword]);
        } catch (\Exception $e) {
            $this->coreHelper->log("Category:: An error occurred at the time of obtaining the categories: " . $e);
            return [];
        }

        $response = $response['response'];

        $cat = [];
        $count = 0;
        foreach ($response as $v) {
            //force category others first
            if ($v['id'] == "others") {
                $cat[0] = ['value' => $v['id'], 'label' => __($v['description'])];
            } else {
                $count++;
                $cat[$count] = ['value' => $v['id'], 'label' => __($v['description'])];
            }
        }

        //force order by key
        ksort($cat);

        $this->coreHelper->log("Category:: Displayed", 'cardpay', $cat);
        return $cat;
    }
}
