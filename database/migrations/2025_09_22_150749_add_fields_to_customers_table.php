<?php

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
        Schema::table('customers', function (Blueprint $table) {
               if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'company_reg_no_new')) {
                $table->string('company_reg_no_new')->nullable();
            }
            if (!Schema::hasColumn('customers', 'company_reg_no_old')) {
                $table->string('company_reg_no_old')->nullable();
            }
            if (!Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable();
            }
            if (!Schema::hasColumn('customers', 'billing_phone')) {
                $table->string('billing_phone')->nullable();
            }
            if (!Schema::hasColumn('customers', 'remark')) {
                $table->text('remark')->nullable();
            }
            if (!Schema::hasColumn('customers', 'consignor_currency')) {
                $table->string('consignor_currency')->nullable();
            }
            if (!Schema::hasColumn('customers', 'consignee_currency')) {
                $table->string('consignee_currency')->nullable();
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable();
            }
            if (!Schema::hasColumn('customers', 'post_code')) {
                $table->string('post_code')->nullable();
            }
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable();
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('customers', 'tin')) {
                $table->string('tin')->nullable();
            }
            if (!Schema::hasColumn('customers', 'service_tax_no')) {
                $table->string('service_tax_no')->nullable();
            }
            if (!Schema::hasColumn('customers', 'contact_person')) {
                $table->string('contact_person')->nullable();
            }
            if (!Schema::hasColumn('customers', 'term')) {
                $table->string('term')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
               $columns = [
                'account_number','type','billing_address','phone','email',
                'company_reg_no_new','company_reg_no_old','website','billing_phone',
                'remark','consignor_currency','consignee_currency','city','post_code',
                'state','country','tin','service_tax_no','contact_person','term'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
