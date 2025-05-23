<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simulacao extends Model
{
    use HasFactory;

    protected $table = 'simulacoes';

    protected $fillable = [
        'cpf',
        'ofertas_originais',
        'ofertas_processadas',
        'data_consulta'
    ];

    protected $casts = [
        'ofertas_originais' => 'array',
        'ofertas_processadas' => 'array',
        'data_consulta' => 'datetime'
    ];
}
