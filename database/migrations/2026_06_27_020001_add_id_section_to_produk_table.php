<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdSectionToProdukTable extends Migration
{
    public function up()
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->unsignedInteger('id_section')->nullable()->after('id_kategori');

            $table->foreign('id_section')
                ->references('id_section')
                ->on('section')
                ->onUpdate('restrict')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['id_section']);
            $table->dropColumn('id_section');
        });
    }
}
