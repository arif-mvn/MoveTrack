<?php

namespace App\Enums;

use App\Base\BaseEnum;

class IdentifierFieldsEnum extends BaseEnum
{
    const ID           = 'id';
    const SHIPMENT_ID  = 'shipment_id'; // FK
    const SCOPE        = 'scope';
    const CARRIER_CODE = 'carrier_code';
    const VALUE        = 'value';
    const CREATED_AT   = 'created_at';
    const UPDATED_AT   = 'updated_at';
}
