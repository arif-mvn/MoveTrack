<?php

use App\Models\Shipment;
use App\Models\ShipmentSource;
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
        Schema::create((new ShipmentSource())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained((new Shipment())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('source_id')
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->dateTime('last_synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['shipment_id','source_id'], 'shipment_sources_unique');
            $table->index(['source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new ShipmentSource())->getTable());
    }
};
