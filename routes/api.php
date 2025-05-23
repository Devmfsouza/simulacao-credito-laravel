<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SimulacaoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('api')->group(function () {
    // Rota principal para consultar ofertas de crédito
    Route::post('/simulacao/consultar', [SimulacaoController::class, 'consultarOfertas']);

    // Rota para histórico de simulações
    Route::get('/simulacao/historico', [SimulacaoController::class, 'historico']);

    // Rota de status da API
    Route::get('/status', function () {
        return response()->json([
            'success' => true,
            'message' => 'API Simulação de Crédito funcionando',
            'version' => '1.0.0',
            'timestamp' => now()->format('Y-m-d H:i:s')
        ]);
    });
});
