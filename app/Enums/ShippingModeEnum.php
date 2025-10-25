<?php

namespace App\Enums;

use App\Base\BaseEnum;

class ShippingModeEnum extends BaseEnum
{
    const CARGO         = 'cargo';
    const INTERNATIONAL = 'international';
    const P2P           = 'p2p';
}
