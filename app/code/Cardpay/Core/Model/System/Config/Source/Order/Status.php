<?php

namespace Cardpay\Core\Model\System\Config\Source\Order;

/**
 * Overrides array to avoid showing certain statuses as an option
 */
class Status extends \Magento\Sales\Model\Config\Source\Order\Status
{
    /**
     * @var string[]
     */
    protected $_stateStatuses;
}