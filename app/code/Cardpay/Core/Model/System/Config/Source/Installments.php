<?php

namespace Cardpay\Core\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Installments
 *
 * @package Cardpay\Core\Model\System\Config\Source
 */
class Installments implements ArrayInterface
{

    /**
     * Return available installments array
     * @return array
     */
    public function toOptionArray()
    {
        $installment = [];

        $installment[] = ["value" => 1, "label" => "1"];
        $installment[] = ["value" => 2, "label" => "2"];
        $installment[] = ["value" => 3, "label" => "3"];
        $installment[] = ["value" => 4, "label" => "4"];
        $installment[] = ["value" => 5, "label" => "5"];
        $installment[] = ["value" => 6, "label" => "6"];
        $installment[] = ["value" => 9, "label" => "9"];
        $installment[] = ["value" => 10, "label" => "10"];
        $installment[] = ["value" => 12, "label" => "12"];
        $installment[] = ["value" => 15, "label" => "15"];
        $installment[] = ["value" => 24, "label" => "24"];

        return $installment;
    }
}
