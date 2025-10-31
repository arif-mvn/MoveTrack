<?php

namespace App\Enums\Source;

use App\Base\BaseEnum;

class SourceFieldsEnum extends BaseEnum
{
    const ID                    = 'id';
    const CODE                  = 'code';
    const NAME                  = 'name';
    const TYPE                  = 'type';
    const CONFIG                = 'config';
    const CREDENTIALS_ENCRYPTED = 'credentials_encrypted';
    const WEBHOOK_SECRET        = 'webhook_secret';
    const ENABLED               = 'enabled';
    const CREATED_AT            = 'created_at';
    const UPDATED_AT            = 'updated_at';
    const DELETED_AT            = 'deleted_at';
}
