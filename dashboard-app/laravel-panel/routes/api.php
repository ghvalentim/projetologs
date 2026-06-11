<?php

use App\Jobs\ProcessarAlertaCritico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/syslog/notify', function (Request $request) {
    // Aqui você pode processar os dados recebidos do Go Listener
    $logData = $request->all();
    
    ProcessarAlertaCritico::dispatch($logData);
    
    // Retornar uma resposta de sucesso
    return response()->json(['message' => 'Alerta colocado na fila com sucesso!'], 200);
});
