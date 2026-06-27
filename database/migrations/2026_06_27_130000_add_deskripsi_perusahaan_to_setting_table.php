<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeskripsiPerusahaanToSettingTable extends Migration
{
    public function up()
    {
        Schema::table('setting', function (Blueprint $table) {
            $table->text('deskripsi_perusahaan')->nullable()->after('nama_perusahaan');
        });
    }

    public function down()
    {
        Schema::table('setting', function (Blueprint $table) {
            $table->dropColumn('deskripsi_perusahaan');
        });
    }
}
