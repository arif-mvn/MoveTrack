<?php

use App\Models\Source;
use App\Models\WebhookDelivery;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create((new WebhookDelivery())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('signature', 191)->nullable();
            $table->json('request_body');
            $table->string('status', 50); //\App\Enums\WebhookDeliveryStatusEnum::all()
            $table->text('error')->nullable();
            $table->dateTime('received_at')->index();
            $table->dateTime('processed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new WebhookDelivery())->getTable());
    }
};
