<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSnapTokenAndPaymentMethodToTransactionsTable extends Migration
{
    public function up()
    {   
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('snap_token')->nullable()->after('payment_url');
            $table->index(['user_id', 'status']);
            $table->index('snap_token');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['snap_token', 'payment_method']);
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['snap_token']);
        });
    }
}