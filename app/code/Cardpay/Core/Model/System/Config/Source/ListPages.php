<?php

namespace Cardpay\Core\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Installments
 *
 * @package Cardpay\Core\Model\System\Config\Source
 */
class ListPages implements ArrayInterface
{
    /**
     * Return available installments array
     * @return array
     */
    public function toOptionArray()
    {
        $pages = [];
        $pages[] = ['value' => "product.info.calculator", 'label' => __("Product Detail Page")];
        $pages[] = ['value' => "checkout.cart.calculator", 'label' => __("Cart page")];

        //force order by key
        ksort($pages);

        return $pages;
    }
}