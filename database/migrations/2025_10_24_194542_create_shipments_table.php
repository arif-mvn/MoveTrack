<?php

use App\Models\Shipment;
use App\Models\Source;
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
        Schema::create((new Shipment())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->string('mode', 50)->index();
            $table->string('current_status', 40)->index();
            $table->foreignId('status_source_id')
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->dateTime('delivered_at')->nullable()->index();
            $table->dateTime('delivered_at_courier')->nullable();
            $table->boolean('status_discrepancy')->default(false)->index();
            $table->dateTime('last_event_at')->index();
            $table->dateTime('last_synced_at')->nullable()->index();
            $table->json('summary_timestamps')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Shipment())->getTable());
    }
};
