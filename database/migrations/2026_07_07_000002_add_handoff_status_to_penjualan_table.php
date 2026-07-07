<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHandoffStatusToPenjualanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            // null = normal sale; 'pending_provisions' = sent by a picker (e.g. Pharmacy)
            // awaiting a Provisions cashier; 'received' = opened by Provisions.
            $table->string('handoff_status')->nullable()->after('id_user');
            $table->unsignedInteger('handoff_from_section')->nullable()->after('handoff_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('penjualan', function (Blueprint $table) {
            $table->dropColumn(['handoff_status', 'handoff_from_section']);
        });
    }
}
