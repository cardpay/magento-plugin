<?php

namespace Cardpay\Core\Config;

class ApiAccessMode extends UnlimitBaseOption
{
    protected const OPTIONS = [
        'pp' => 'Payment page',
        'gateway' => 'Gateway',
    ];
}
