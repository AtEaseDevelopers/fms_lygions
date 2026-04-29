<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dashboard_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->foreignId('consignment_id')
                ->nullable()
                ->constrained('consignments')
                ->nullOnDelete();
            $table->foreignId('triggered_by_consignment_id')
                ->nullable()
                ->constrained('consignments')
                ->nullOnDelete();
            $table->string('truck_number')->nullable();
            $table->date('affected_date')->nullable();
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_notifications');
    }
};
