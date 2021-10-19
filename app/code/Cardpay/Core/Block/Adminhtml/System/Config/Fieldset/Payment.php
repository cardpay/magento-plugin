<?php

namespace Cardpay\Core\Block\Adminhtml\System\Config\Fieldset;

use Cardpay\Core\Helper\ConfigData;
use Magento\Backend\Block\Context;
use Magento\Backend\Block\Store\Switcher;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Config\Model\ResourceModel\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;
use Magento\Store\Model\ScopeInterface;

/**
 * Config form FieldSet renderer
 */
class Payment extends Fieldset
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     *
     * @var Config
     */
    protected $configResource;

    /**
     *
     * @var Switcher
     */
    protected $switcher;

    /**
     * @param Context $context
     * @param Session $authSession
     * @param Js $jsHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $configResource
     * @param Switcher $switcher
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Session              $authSession,
        Js                   $jsHelper,
        ScopeConfigInterface $scopeConfig,
        Config               $configResource,
        Switcher             $switcher,
        array                $data = []
    )
    {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->scopeConfig = $scopeConfig;
        $this->configResource = $configResource;
        $this->switcher = $switcher;
    }

    /**
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        //get id element
        $id = $element->getId();

        //check is bank transfer
        if (strpos($id, 'custom_checkout_bank_transfer') !== false) {

            //get country (Site id for Cardpay)
            $siteId = strtoupper($this->scopeConfig->getValue(ConfigData::PATH_SITE_ID, ScopeInterface::SCOPE_STORE));

            //hide payment method if not Chile or Colombia
            if ($siteId != "MLC" && $siteId != "MCO") {

                //get is active
                $statusPaymentMethod = $this->scopeConfig->isSetFlag(ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE, ScopeInterface::SCOPE_STORE);

                //check is active for disable
                if ($statusPaymentMethod) {
                    $path = ConfigData::PATH_CUSTOM_BANK_TRANSFER_ACTIVE;
                    $value = 0;

                    if ($this->switcher->getWebsiteId() == 0) {
                        $this->configResource->saveConfig($path, $value, 'default', 0);
                    } else {
                        $this->configResource->saveConfig($path, $value, 'websites', $this->switcher->getWebsiteId());
                    }
                }

                return "";
            }
        }

        return parent::render($element);
    }
}
