<?php

namespace App\Enums;

use App\Base\BaseEnum;

class WebhookDeliveryFieldsEnum extends BaseEnum
{
    const ID           = 'id';
    const SOURCE_ID    = 'source_id'; // FK
    const SIGNATURE    = 'signature';
    const REQUEST_BODY = 'request_body';
    const STATUS       = 'status';
    const ERROR        = 'error';
    const RECEIVED_AT  = 'received_at';
    const PROCESSED_AT = 'processed_at';
    const CREATED_AT   = 'created_at';
    const UPDATED_AT   = 'updated_at';
}
