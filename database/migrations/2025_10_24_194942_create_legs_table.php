<?php

use App\Models\Leg;
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
        Schema::create((new Leg())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained((new Shipment())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('type', 50)->index();
            $table->foreignId('source_id')
                ->nullable()
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('carrier_name', 80)->nullable();
            $table->json('route')->nullable();
            $table->dateTime('start_at')->nullable()->index();
            $table->dateTime('end_at')->nullable()->index();
            $table->timestamps();

            $table->index(['shipment_id','source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Leg())->getTable());
    }
};
