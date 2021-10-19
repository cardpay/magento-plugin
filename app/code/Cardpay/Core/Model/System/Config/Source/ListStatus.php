<?php

namespace Cardpay\Core\Model\System\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Installments
 *
 * @package Cardpay\Core\Model\System\Config\Source
 */
class ListStatus implements ArrayInterface
{

    /**
     * Return available installments array
     * @return array
     */
    public function toOptionArray()
    {
        $status = [];
        $status[] = ['value' => "do_not_send", 'label' => __("Do not send")];
        $status[] = ['value' => "pending", 'label' => __("pending")];
        $status[] = ['value' => "approved", 'label' => __("approved")];
        $status[] = ['value' => "authorized", 'label' => __("authorized")];
        $status[] = ['value' => "in_process", 'label' => __("in_process")];
        $status[] = ['value' => "in_mediation", 'label' => __("in_mediation")];
        $status[] = ['value' => "rejected", 'label' => __("rejected")];
        $status[] = ['value' => "cancelled", 'label' => __("cancelled")];
        $status[] = ['value' => "refunded", 'label' => __("refunded")];
        $status[] = ['value' => "charged_back", 'label' => __("charged_back")];

        //force order by key
        ksort($status);

        return $status;
    }
}