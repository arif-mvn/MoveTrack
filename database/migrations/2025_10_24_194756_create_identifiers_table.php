<?php

use App\Enums\SourceCodeEnum;
use App\Enums\SourceTypeEnum;
use App\Models\Identifier;
use App\Models\Shipment;
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
        Schema::create((new Identifier())->getTable(), function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained((new Shipment())->getTable())
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('scope');
            $table->string('carrier_code', 20)->default(SourceCodeEnum::MOVEON);
            $table->string('value', 191);
            $table->timestamps();

            $table->index(['shipment_id']);
            $table->index(['value']);
            $table->index(['carrier_code','value']);

            $table->unique(['scope','carrier_code','value'], 'identifiers_scope_carrier_value_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new Identifier())->getTable());
    }
};
