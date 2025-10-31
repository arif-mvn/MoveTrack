<?php

namespace App\Models;

use App\Enums\Database\TableNameEnum;
use App\Enums\ValueTypEnum;
use App\Enums\WebhookDelivery\WebhookDeliveryFieldsEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $table = TableNameEnum::WEBHOOK_DELIVERIES;
    protected $guarded = [];

    protected $casts = [
        WebhookDeliveryFieldsEnum::REQUEST_BODY => ValueTypEnum::ARRAY_VALUE,
        WebhookDeliveryFieldsEnum::RECEIVED_AT  => ValueTypEnum::DATETIME,
        WebhookDeliveryFieldsEnum::PROCESSED_AT => ValueTypEnum::DATETIME,
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, WebhookDeliveryFieldsEnum::SOURCE_ID);
    }
}
