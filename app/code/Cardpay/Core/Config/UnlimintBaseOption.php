<?php

namespace Cardpay\Core\Config;

use Magento\Checkout\Model\Session;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Option\ArrayInterface;
use Magento\Store\Model\StoreManagerInterface;

class UnlimintBaseOption implements ArrayInterface
{
    protected const OPTIONS = [];

    private $storeConfig;

    /**
     * @var CurrencyFactory
     */
    private $currencyCode;

    /**
     * Options getter
     *
     * @return array
     */
    public function __construct(
        StoreManagerInterface $storeConfig,
        CurrencyFactory       $currencyFactory
    )
    {
        $this->storeConfig = $storeConfig;
        $this->currencyCode = $currencyFactory->create();
    }

    public function toOptionArray()
    {
        $result = [];
        foreach (static::OPTIONS as $value => $label) {
            if ($value == 'currency') {
                $this->getCurrencySymbol();
            }

            if (is_array($label)) {
                $tmp = [];
                foreach ($label as $_ => $subLabel) {
                    $tmp[] = ['value' => '', 'label' => __($subLabel)];
                }
                $result[] = ['label' => $value, 'value' => $tmp];

            } else {
                $result[] = ['value' => $value, 'label' => __($label)];
            }
        }
        return $result;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array_values(static::OPTIONS);
    }

    public function getCurrencySymbol()
    {
        $currentCurrency = $this->storeConfig->getStore()->getCurrentCurrencyCode();
        $currency = $this->currencyCode->load($currentCurrency);
        $getCurrencySymbols = [
            'currency' => $currency->getCurrencySymbol(),
        ];

        $getCurrencySymbol = '{';
        foreach ($getCurrencySymbols as $key => $value) {
            $getCurrencySymbol .= "\"$key\":\"$value\"";
            if (array_key_last($getCurrencySymbols) != $key) {
                $getCurrencySymbol .= ',';
            }
        }
        $getCurrencySymbol .= '}';

        echo "
			<script type='text/javascript'>
			if (typeof GET_CURRENCY_SYMBOL === 'undefined') {
                var GET_CURRENCY_SYMBOL = $getCurrencySymbol;
            }
			</script>
		";
    }
}
