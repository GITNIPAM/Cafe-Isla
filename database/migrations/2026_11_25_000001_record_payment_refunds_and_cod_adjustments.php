<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('refunded_amount', 10, 2)->default(0);
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->decimal('cod_adjustment', 10, 2)->default(0);
        });

        DB::table('payments')->where('status', 'refunded')->whereNotNull('paid_at')
            ->update(['refunded_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('return_requests', function (Blueprint $table) {
            $table->dropColumn('cod_adjustment');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('refunded_amount');
        });
    }
};
