<?php

namespace Cardpay\Core\Config;

use Magento\Framework\Option\ArrayInterface;

class UnlimintBaseOption implements ArrayInterface
{
    protected const OPTIONS = [];

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        foreach (static::OPTIONS as $value => $label) {
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
}
