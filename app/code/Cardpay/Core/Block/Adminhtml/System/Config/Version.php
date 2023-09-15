<?php

namespace Cardpay\Core\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

class Version extends Field
{
    const MODULE_NAME = 'Cardpay_Core';

    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'Cardpay_Core::system/config/module_version.phtml';

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param  Context     $context
     * @param  YotpoConfig $yotpoConfig
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->moduleList = $moduleList;
        parent::__construct($context, $data);
    }

    /**
     * Remove scope label
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return element html
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Generate collect button html
     *
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleList->getOne(self::MODULE_NAME)['setup_version'];
    }
}
