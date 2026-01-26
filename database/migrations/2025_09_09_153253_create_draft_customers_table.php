<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('draft_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['Consignor', 'Consignee']);
            $table->string('account_number')->unique();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_customers');
    }
};
