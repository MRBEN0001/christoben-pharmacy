<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSectionTable extends Migration
{
    public function up()
    {
        Schema::create('section', function (Blueprint $table) {
            $table->increments('id_section');
            $table->string('nama_section')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('section');
    }
}
