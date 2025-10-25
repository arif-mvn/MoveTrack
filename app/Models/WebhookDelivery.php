<?php

namespace App\Models;

use App\Enums\WebhookDeliveryFieldsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $table = 'webhook_deliveries';
    protected $guarded = [];

    protected $casts = [
        WebhookDeliveryFieldsEnum::REQUEST_BODY => 'array',
        WebhookDeliveryFieldsEnum::RECEIVED_AT  => 'datetime',
        WebhookDeliveryFieldsEnum::PROCESSED_AT => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, WebhookDeliveryFieldsEnum::SOURCE_ID);
    }
}
