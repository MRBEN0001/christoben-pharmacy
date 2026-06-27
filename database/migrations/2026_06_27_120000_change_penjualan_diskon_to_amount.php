<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePenjualanDiskonToAmount extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE penjualan MODIFY diskon INT UNSIGNED NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE penjualan_detail MODIFY diskon INT UNSIGNED NOT NULL DEFAULT 0');
    }

    public function down()
    {
        DB::statement('ALTER TABLE penjualan MODIFY diskon TINYINT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE penjualan_detail MODIFY diskon TINYINT NOT NULL DEFAULT 0');
    }
}
