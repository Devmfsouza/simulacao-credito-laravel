<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('simulacoes', function (Blueprint $table) {
            $table->id();
            $table->string('cpf');
            $table->json('ofertas_originais');
            $table->json('ofertas_processadas');
            $table->timestamp('data_consulta');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('simulacoes');
    }
};
