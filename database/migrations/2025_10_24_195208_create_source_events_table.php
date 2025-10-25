<?php

use App\Models\Event;
use App\Models\Shipment;
use App\Models\Source;
use App\Models\SourceEvent;
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
        Schema::create((new SourceEvent())->getTable(), function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->nullable()
                ->constrained((new Shipment())->getTable())
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('event_id')
                ->nullable()
                ->constrained((new Event())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('source_id')
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('source_event_id', 120)->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->string('payload_hash', 64)->unique(); // idempotency
            $table->json('payload');
            $table->dateTime('received_at');
            $table->timestamps();

            $table->index(['source_id','source_event_id'], 'src_ev_code_id_idx');
            $table->index(['shipment_id','occurred_at'], 'src_ev_shipment_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new SourceEvent())->getTable());
    }
};
