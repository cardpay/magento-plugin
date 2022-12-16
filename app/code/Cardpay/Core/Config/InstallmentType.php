<?php

namespace Cardpay\Core\Config;

class InstallmentType extends UnlimintBaseOption
{
    public const INSTALLMENT_TYPE_IF = 'IF';
    public const INSTALLMENT_TYPE_MF = 'MF_HOLD';

    protected const OPTIONS = [
        self::INSTALLMENT_TYPE_IF => 'Issuer financed',
        self::INSTALLMENT_TYPE_MF => 'Merchant financed'
    ];
}
