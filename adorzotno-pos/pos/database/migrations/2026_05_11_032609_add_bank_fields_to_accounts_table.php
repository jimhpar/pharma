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
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('account_code');
            $table->string('account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_branch', 150)->nullable()->after('account_number');
            $table->string('routing_number', 50)->nullable()->after('bank_branch');
            $table->text('notes')->nullable()->after('routing_number');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_number', 'bank_branch', 'routing_number', 'notes']);
        });
    }
};
