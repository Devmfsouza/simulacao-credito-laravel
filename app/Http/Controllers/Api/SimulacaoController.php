<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Simulacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class SimulacaoController extends Controller
{
    private $baseUrl = 'https://dev.gosat.org/api/v1/simulacao';

    /**
     * Consulta ofertas de crédito para um CPF
     */
    public function consultarOfertas(Request $request)
    {
        try {
            Log::info('=== INÍCIO CONSULTA OFERTAS ===');

            // Validação do CPF
            $validator = Validator::make($request->all(), [
                'cpf' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF é obrigatório',
                    'errors' => $validator->errors()
                ], 400);
            }

            $cpf = $this->limparCpf($request->cpf);

            // Validar CPFs permitidos
            $cpfsPermitidos = ['11111111111', '12312312312', '22222222222'];
            if (!in_array($cpf, $cpfsPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'CPF não disponível para consulta. Use: 111.111.111-11, 123.123.123-12 ou 222.222.222-22'
                ], 400);
            }

            // 1. Consultar instituições disponíveis
            $instituicoes = $this->consultarInstituicoes($cpf);

            if (!$instituicoes || empty($instituicoes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma instituição disponível para este CPF'
                ], 500);
            }

            // 2. Simular cada modalidade de cada instituição
            $ofertasDetalhadas = [];
            foreach ($instituicoes as $instituicao) {
                if (isset($instituicao['modalidades']) && is_array($instituicao['modalidades'])) {
                    foreach ($instituicao['modalidades'] as $modalidade) {
                        Log::info("Simulando: Instituição {$instituicao['id']}, Modalidade {$modalidade['cod']}");

                        $simulacao = $this->simularOferta($cpf, $instituicao['id'], $modalidade['cod']);
                        if ($simulacao) {
                            $simulacao['instituicaoFinanceira'] = $instituicao['nome'];
                            $simulacao['modalidadeCredito'] = $modalidade['nome'];
                            $simulacao['instituicao_id'] = $instituicao['id'];
                            $simulacao['codModalidade'] = $modalidade['cod'];
                            $ofertasDetalhadas[] = $simulacao;
                            Log::info("Simulação OK para {$instituicao['nome']} - {$modalidade['nome']}");
                        } else {
                            Log::warning("Simulação falhou para {$instituicao['nome']} - {$modalidade['nome']}");
                        }
                    }
                }
            }

            if (empty($ofertasDetalhadas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma oferta disponível para este CPF'
                ], 404);
            }

            Log::info('Total de ofertas simuladas: ' . count($ofertasDetalhadas));

            // 3. Processar e ordenar ofertas (máximo 3)
            $ofertasProcessadas = $this->processarOfertas($ofertasDetalhadas);

            // 4. Salvar no banco
            $simulacao = Simulacao::create([
                'cpf' => $request->cpf,
                'ofertas_originais' => $ofertasDetalhadas,
                'ofertas_processadas' => $ofertasProcessadas,
                'data_consulta' => now()
            ]);

            Log::info('Simulação salva no banco com ID: ' . $simulacao->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'cpf' => $request->cpf,
                    'ofertas' => $ofertasProcessadas,
                    'total_ofertas' => count($ofertasProcessadas),
                    'data_consulta' => $simulacao->data_consulta->format('d/m/Y H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            Log::error('ERRO GERAL: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta instituições disponíveis na API externa
     */
    private function consultarInstituicoes($cpf)
    {
        try {
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ])
                ->post($this->baseUrl . '/credito', [
                    'cpf' => $cpf
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['instituicoes'] ?? null;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Erro ao consultar instituições: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Simula uma oferta específica
     */
    private function simularOferta($cpf, $instituicaoId, $codModalidade)
    {
        try {
            Log::info("=== SIMULANDO OFERTA ===");
            Log::info("CPF: {$cpf}");
            Log::info("Instituição ID: {$instituicaoId}");
            Log::info("Modalidade: {$codModalidade}");
            Log::info("URL: " . $this->baseUrl . '/oferta');

            $payload = [
                'cpf' => $cpf,
                'instituicao_id' => $instituicaoId,
                'codModalidade' => $codModalidade
            ];

            Log::info("Payload: " . json_encode($payload));

            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false,
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ])
                ->post($this->baseUrl . '/oferta', $payload);

            Log::info("Status da resposta: " . $response->status());
            Log::info("Body da resposta: " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                Log::info("Dados da simulação: " . json_encode($data));
                return $data;
            } else {
                Log::error("Resposta não successful. Status: " . $response->status());
                Log::error("Body: " . $response->body());
                return null;
            }
        } catch (Exception $e) {
            Log::error('Erro ao simular oferta: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Processa e ordena as ofertas da mais vantajosa para menos vantajosa
     */
    private function processarOfertas($ofertas)
    {
        try {
            Log::info("=== PROCESSANDO OFERTAS ===");
            Log::info("Total de ofertas recebidas: " . count($ofertas));

            $ofertasProcessadas = [];

            foreach ($ofertas as $index => $oferta) {
                try {
                    Log::info("Processando oferta {$index}: " . json_encode($oferta));

                    // CORRIGIDO: Usar nomes corretos dos campos (minúsculo)
                    $camposObrigatorios = ['valorMin', 'valorMax', 'QntParcelaMin', 'QntParcelaMax', 'jurosMes', 'instituicaoFinanceira', 'modalidadeCredito'];
                    foreach ($camposObrigatorios as $campo) {
                        if (!isset($oferta[$campo])) {
                            Log::error("Campo obrigatório não encontrado: {$campo}");
                            continue 2; // Pular para próxima oferta
                        }
                    }

                    // CORRIGIDO: Usar nomes corretos dos campos
                    $valorSolicitado = ($oferta['valorMin'] + $oferta['valorMax']) / 2;
                    $qntParcelas = round(($oferta['QntParcelaMin'] + $oferta['QntParcelaMax']) / 2);
                    $jurosMensal = $oferta['jurosMes'];
                    $taxaJuros = $jurosMensal * 100;

                    // Calcular valor total usando juros compostos
                    $valorAPagar = $valorSolicitado * pow(1 + $jurosMensal, $qntParcelas);

                    $ofertaProcessada = [
                        'instituicaoFinanceira' => $oferta['instituicaoFinanceira'],
                        'modalidadeCredito' => $oferta['modalidadeCredito'],
                        'valorAPagar' => round($valorAPagar, 2),
                        'valorSolicitado' => round($valorSolicitado, 2),
                        'taxaJuros' => round($taxaJuros, 4),
                        'qntParcelas' => $qntParcelas,
                        'valorParcela' => round($valorAPagar / $qntParcelas, 2),
                        'custoTotal' => round($valorAPagar - $valorSolicitado, 2),
                        'score_vantagem' => $this->calcularScoreVantagem($jurosMensal, $qntParcelas, $valorAPagar, $valorSolicitado)
                    ];

                    $ofertasProcessadas[] = $ofertaProcessada;
                    Log::info("Oferta {$index} processada com sucesso");
                } catch (Exception $e) {
                    Log::error("Erro ao processar oferta {$index}: " . $e->getMessage());
                    continue;
                }
            }

            Log::info("Total de ofertas processadas: " . count($ofertasProcessadas));

            // Ordenar por score de vantagem (menor é melhor)
            usort($ofertasProcessadas, function ($a, $b) {
                return $a['score_vantagem'] <=> $b['score_vantagem'];
            });

            // Retornar apenas as 3 melhores ofertas
            $resultado = array_slice($ofertasProcessadas, 0, 3);
            Log::info("Ofertas finais: " . json_encode($resultado));

            return $resultado;
        } catch (Exception $e) {
            Log::error('Erro geral no processamento: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula um score para determinar a vantagem da oferta
     * Considera: taxa de juros mensal, número de parcelas e custo total
     */
    private function calcularScoreVantagem($jurosMensal, $parcelas, $valorAPagar, $valorSolicitado)
    {
        // Peso maior para taxa de juros, menor para parcelas
        $scoreTaxa = $jurosMensal * 1000; // Amplificar para comparação
        $scoreParcelas = ($parcelas / 12) * 0.2; // Normalizado por ano
        $scoreCusto = (($valorAPagar - $valorSolicitado) / $valorSolicitado) * 0.3;

        return $scoreTaxa + $scoreParcelas + $scoreCusto;
    }

    /**
     * Remove formatação do CPF
     */
    private function limparCpf($cpf)
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Lista histórico de simulações
     */
    public function historico()
    {
        try {
            $simulacoes = Simulacao::orderBy('created_at', 'desc')
                ->take(50)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $simulacoes
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao buscar histórico: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico'
            ], 500);
        }
    }
}
