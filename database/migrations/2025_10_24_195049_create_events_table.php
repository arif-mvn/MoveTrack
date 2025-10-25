<?php

use App\Enums\EventVisibilityTypeEnum;
use App\Models\Event;
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
        Schema::create((new Event())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained((new Shipment())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('leg_id')
                ->nullable()
                ->constrained((new Leg())->getTable())
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignId('source_id')
                ->nullable()
                ->constrained((new Source())->getTable())
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('type', 40)->index(); // canonical event type
            $table->dateTime('occurred_at')->index();
            $table->string('source', 50); //['internal','courier']

            $table->string('status_code', 40)->nullable();
            $table->text('raw_text')->nullable();
            $table->json('location')->nullable();
            $table->json('actor')->nullable();
            $table->json('evidence')->nullable();
            $table->string('visibility', 50)->default(EventVisibilityTypeEnum::PUBLIC);
            $table->boolean('authoritative')->default(true);
            $table->timestamps();

            $table->index(['shipment_id','occurred_at'], 'events_shipment_time_idx');
            $table->index(['leg_id','occurred_at'], 'events_leg_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Event())->getTable());
    }
};
