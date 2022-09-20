<?php

namespace Cardpay\Core\Config;

class ApiAccessMode extends UnlimintBaseOption
{
    protected const OPTIONS = [
        'pp'=>'Payment page',
        'gateway'=>'Gateway',
    ];
}