<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('dashboard_notifications') && !Schema::hasTable('notifications')) {
            Schema::rename('dashboard_notifications', 'notifications');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications') && !Schema::hasTable('dashboard_notifications')) {
            Schema::rename('notifications', 'dashboard_notifications');
        }
    }
};
