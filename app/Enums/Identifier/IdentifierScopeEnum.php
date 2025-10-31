<?php

namespace App\Enums\Identifier;

use App\Base\BaseEnum;

class IdentifierScopeEnum extends BaseEnum
{
    const INTERNAL          = 'internal';
    const COURIER           = 'courier';
    const LAST_MILE         = 'last_mile';
    const INVOICE           = 'invoice';
    const CUSTOMER_ORDER    = 'customer_order';
}
