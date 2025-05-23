@extends('layouts.app')

@section('title', 'Simulação de Crédito - Encontre as Melhores Ofertas')

@section('content')
<!-- Header -->
<div class="header-section">
    <h1 class="mb-3">
        <i class="fas fa-credit-card me-3"></i>
        Simulação de Crédito
    </h1>
    <p class="lead mb-0">
        Encontre as melhores ofertas de crédito personalizadas para você
    </p>
</div>

<!-- Formulário de Consulta -->
<div class="form-section">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form id="simulacaoForm">
                <div class="mb-4">
                    <label for="cpf" class="form-label h5">
                        <i class="fas fa-user me-2"></i>
                        Digite seu CPF
                    </label>
                    <input type="text"
                        class="form-control cpf-input"
                        id="cpf"
                        name="cpf"
                        placeholder="000.000.000-00"
                        maxlength="14"
                        required>
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>
                        CPFs disponíveis: 111.111.111-11, 123.123.123-12, 222.222.222-22
                    </div>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>
                        Consultar Ofertas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Loading -->
    <div class="loading text-center mt-4" id="loading">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Carregando...</span>
        </div>
        <p class="mt-2">Buscando as melhores ofertas para você...</p>
    </div>
</div>

<!-- Resultados -->
<div class="results-section" id="results" style="display: none;">
    <hr class="my-4">

    <!-- Informações da Consulta -->
    <div class="row mb-4" id="consultaInfo">
        <div class="col-md-4">
            <div class="stats-card">
                <h5 class="text-primary"><i class="fas fa-user"></i></h5>
                <h6>CPF Consultado</h6>
                <p id="cpfConsultado" class="mb-0"></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h5 class="text-success"><i class="fas fa-chart-line"></i></h5>
                <h6>Ofertas Encontradas</h6>
                <p id="totalOfertas" class="mb-0"></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <h5 class="text-info"><i class="fas fa-clock"></i></h5>
                <h6>Data da Consulta</h6>
                <p id="dataConsulta" class="mb-0"></p>
            </div>
        </div>
    </div>

    <!-- Lista de Ofertas -->
    <div id="ofertas"></div>

    <!-- Gráfico Comparativo -->
    <div class="mt-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar me-2"></i>Comparativo de Ofertas</h5>
            </div>
            <div class="card-body">
                <canvas id="chartOfertas" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Botão Nova Consulta -->
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary" onclick="novaConsulta()">
            <i class="fas fa-redo me-2"></i>
            Nova Consulta
        </button>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Máscara do CPF
        $('#cpf').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            $(this).val(value);
        });

        // CSRF Token para requisições AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Submit do formulário
        $('#simulacaoForm').on('submit', function(e) {
            e.preventDefault();

            const cpf = $('#cpf').val().trim();

            if (!cpf) {
                alert('Por favor, digite um CPF válido.');
                return;
            }

            consultarOfertas(cpf);
        });
    });

    function consultarOfertas(cpf) {
        // Mostrar loading
        $('#loading').addClass('show');
        $('#results').hide();

        // Fazer requisição para a API
        $.ajax({
            url: '/api/simulacao/consultar',
            method: 'POST',
            data: {
                cpf: cpf
            },
            success: function(response) {
                $('#loading').removeClass('show');

                if (response.success) {
                    exibirResultados(response.data);
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr) {
                $('#loading').removeClass('show');

                let errorMessage = 'Erro ao consultar ofertas.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                alert(errorMessage);
            }
        });
    }

    function exibirResultados(data) {
        // Informações da consulta
        $('#cpfConsultado').text(data.cpf);
        $('#totalOfertas').text(data.total_ofertas);
        $('#dataConsulta').text(data.data_consulta);

        // Gerar cards das ofertas
        let ofertasHtml = '';
        data.ofertas.forEach(function(oferta, index) {
            const isMelhor = index === 0;
            const cardClass = isMelhor ? 'offer-card best-offer' : 'offer-card';

            ofertasHtml += `
            <div class="${cardClass}">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="text-primary">${oferta.instituicaoFinanceira}</h5>
                        <p class="text-muted mb-2">${oferta.modalidadeCredito}</p>
                        
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Valor Solicitado</small>
                                <h6 class="text-success">R$ ${formatarMoeda(oferta.valorSolicitado)}</h6>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Valor a Pagar</small>
                                <h6 class="text-danger">R$ ${formatarMoeda(oferta.valorAPagar)}</h6>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="row">
                            <div class="col-4">
                                <small class="text-muted">Taxa de Juros</small>
                                <h6>${oferta.taxaJuros}%</h6>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Parcelas</small>
                                <h6>${oferta.qntParcelas}x</h6>
                            </div>
                            <div class="col-4">
                                <small class="text-muted">Valor da Parcela</small>
                                <h6>R$ ${formatarMoeda(oferta.valorParcela)}</h6>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small class="text-muted">Custo Total dos Juros</small>
                            <h6 class="text-warning">R$ ${formatarMoeda(oferta.custoTotal)}</h6>
                        </div>
                    </div>
                </div>
            </div>
        `;
        });

        $('#ofertas').html(ofertasHtml);

        // Gerar gráfico
        gerarGrafico(data.ofertas);

        // Mostrar resultados
        $('#results').show();
    }

    function gerarGrafico(ofertas) {
        console.log('Gerando gráfico para:', ofertas);

        const ctx = document.getElementById('chartOfertas').getContext('2d');

        // Destruir gráfico anterior se existir (CORRIGIDO)
        if (window.chartOfertas && typeof window.chartOfertas.destroy === 'function') {
            window.chartOfertas.destroy();
        }

        const labels = ofertas.map(o => o.instituicaoFinanceira);
        const taxasJuros = ofertas.map(o => parseFloat(o.taxaJuros));
        const custosTotal = ofertas.map(o => parseFloat(o.custoTotal));

        console.log('Labels:', labels);
        console.log('Taxas:', taxasJuros);
        console.log('Custos:', custosTotal);

        window.chartOfertas = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Taxa de Juros (%)',
                        data: taxasJuros,
                        backgroundColor: 'rgba(37, 99, 235, 0.6)',
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Custo Total (R$)',
                        data: custosTotal,
                        backgroundColor: 'rgba(234, 179, 8, 0.6)',
                        borderColor: 'rgba(234, 179, 8, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Taxa de Juros (%)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Custo Total (R$)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    title: {
                        display: true,
                        text: 'Comparacao: Taxa de Juros vs Custo Total'
                    }
                }
            }
        });

        console.log('Gráfico criado com sucesso');
    }

    function formatarMoeda(valor) {
        // Converter para número primeiro
        const numero = parseFloat(valor);
        if (isNaN(numero)) return '0,00';

        return numero.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function novaConsulta() {
        $('#cpf').val('');
        $('#results').hide();
        $('#cpf').focus();
    }
</script>
@endsection