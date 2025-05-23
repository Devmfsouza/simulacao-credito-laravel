<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('simulacao');
})->name('home');

Route::get('/documentacao', function () {
    return view('documentacao');
})->name('documentacao');

// Rota de debug para testar a API externa
Route::get('/debug-api/{cpf}', function ($cpf) {
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ])
            ->post('https://dev.gosat.org/api/v1/simulacao/credito', [
                'cpf' => $cpf
            ]);

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'raw_body' => $response->body(),
            'json_data' => $response->json(),
            'has_instituicoes' => isset($response->json()['instituicoes']),
            'instituicoes_count' => isset($response->json()['instituicoes']) ? count($response->json()['instituicoes']) : 0
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Rota de teste simples da API
Route::get('/teste-api', function () {
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ])
            ->post('https://dev.gosat.org/api/v1/simulacao/credito', [
                'cpf' => '11111111111'
            ]);

        return response()->json([
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->body(),
            'json' => $response->json()
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
});

// Rota para listar logs
Route::get('/logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        return response('<pre>' . htmlspecialchars($logs) . '</pre>');
    }
    return 'Arquivo de log não encontrado';
});

// Rota para limpar logs
Route::get('/clear-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
        return 'Logs limpos com sucesso!';
    }
    return 'Arquivo de log não encontrado';
});


// Rota para testar diretamente o controller
Route::get('/test-controller/{cpf}', function ($cpf) {
    try {
        $controller = new \App\Http\Controllers\Api\SimulacaoController();

        $request = new \Illuminate\Http\Request();
        $request->merge(['cpf' => $cpf]);

        return $controller->consultarOfertas($request);
    } catch (Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});
