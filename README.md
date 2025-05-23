# 🏦 API Simulação de Crédito

Sistema completo para consulta e simulação de ofertas de crédito, desenvolvido em Laravel com interface web moderna e responsiva.

## 📋 Sobre o Projeto

Este projeto foi desenvolvido como teste prático para vaga de Desenvolvedor Senior. O sistema consulta ofertas de crédito de múltiplas instituições financeiras, processa os dados, ordena as ofertas da mais vantajosa para a menos vantajosa e apresenta os resultados através de uma interface web com gráficos interativos.

## ✨ Funcionalidades

- 🔍 **Consulta de Ofertas**: Busca ofertas de crédito em múltiplas instituições
- 📊 **Processamento Inteligente**: Calcula e ordena ofertas por vantagem ao cliente
- 💾 **Persistência de Dados**: Salva todas as simulações no banco de dados
- 📈 **Relatórios Gráficos**: Interface web com gráficos interativos
- 🎯 **API RESTful**: Endpoints bem documentados e testáveis
- 📱 **Interface Responsiva**: Design moderno que funciona em qualquer dispositivo

## 🛠️ Tecnologias Utilizadas

- **Backend**: Laravel 10.x, PHP 8.0+
- **Banco de Dados**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Gráficos**: Chart.js
- **HTTP Client**: Guzzle
- **Outras**: jQuery, Font Awesome

## 📦 Instalação e Configuração

### Pré-requisitos

- PHP 8.0 ou superior
- Composer
- MySQL
- Servidor web (Apache/Nginx) ou WAMP/XAMPP

### Passo a Passo

1. **Clone o repositório**
   ```bash
   git clone [seu-repositorio]
   cd simulacao-credito
   ```

2. **Instale as dependências**
   ```bash
   composer install
   ```

3. **Configure o ambiente**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure o banco de dados**
   
   Edite o arquivo `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=simulacao_credito
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Crie o banco de dados**
   - Acesse phpMyAdmin ou MySQL
   - Crie um banco chamado `simulacao_credito`

6. **Execute as migrações**
   ```bash
   php artisan migrate
   ```

7. **Inicie o servidor**
   ```bash
   php artisan serve
   ```

8. **Acesse a aplicação**
   - Interface Web: http://127.0.0.1:8000
   - Documentação da API: http://127.0.0.1:8000/api-docs.html

## 🚀 Como Usar

### Interface Web

1. Acesse http://127.0.0.1:8000
2. Digite um dos CPFs de teste:
   - `111.111.111-11`
   - `123.123.123-12`
   - `222.222.222-22`
3. Clique em "Consultar Ofertas"
4. Visualize os resultados com gráficos comparativos

### API Endpoints

#### Consultar Ofertas
```http
POST /api/simulacao/consultar
Content-Type: application/json

{
  "cpf": "111.111.111-11"
}
```

#### Histórico de Simulações
```http
GET /api/simulacao/historico
```

#### Status da API
```http
GET /api/status
```

## 📊 Estrutura do Projeto

```
simulacao-credito/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── SimulacaoController.php    # Controller principal da API
│   └── Models/
│       └── Simulacao.php              # Model para persistência
├── database/
│   └── migrations/                    # Migrações do banco
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php              # Layout principal
│   └── simulacao.blade.php            # Interface principal
├── routes/
│   ├── api.php                        # Rotas da API
│   └── web.php                        # Rotas web
└── public/
    └── api-docs.html                  # Documentação da API
```

## 🔧 Funcionalidades Técnicas

### Algoritmo de Ordenação

O sistema utiliza um algoritmo proprietário para determinar a melhor oferta considerando:

- **Taxa de Juros** (peso 60%): Menor taxa é melhor
- **Número de Parcelas** (peso 20%): Menos parcelas é melhor
- **Custo Total** (peso 20%): Menor custo é melhor

### Cálculos Financeiros

- **Valor Médio**: Média entre valor mínimo e máximo
- **Juros Compostos**: Cálculo preciso do valor final
- **Custo Total**: Diferença entre valor a pagar e valor solicitado

### Tratamento de Erros

- Validação de CPF
- Timeout de requisições (30s)
- Logs detalhados para debug
- Tratamento de falhas de SSL
- Fallback para diferentes respostas da API

## 🧪 Testes

### CPFs de Teste Disponíveis

| CPF | Instituições | Modalidades |
|-----|-------------|-------------|
| 111.111.111-11 | 2 | 3 |
| 123.123.123-12 | 2 | 3 |
| 222.222.222-22 | 2 | 3 |

### Testando a API

Você pode testar usando:

1. **Interface Web**: http://127.0.0.1:8000
2. **Postman/Insomnia**: Importe as rotas da documentação
3. **cURL**:
   ```bash
   curl -X POST http://127.0.0.1:8000/api/simulacao/consultar \
        -H "Content-Type: application/json" \
        -d '{"cpf": "111.111.111-11"}'
   ```

## 📚 Documentação

- **API Docs**: http://127.0.0.1:8000/api-docs.html
- **Logs**: http://127.0.0.1:8000/logs (desenvolvimento)

## 🐛 Troubleshooting

### Problemas Comuns

1. **Erro SSL**: O sistema já está configurado para ignorar problemas de SSL
2. **Tabela não existe**: Execute `php artisan migrate`
3. **Permissões**: Verifique permissões da pasta `storage/`
4. **Composer**: Se houver problemas, use `composer install --no-dev`

### Logs

Para debug, verifique:
- `storage/logs/laravel.log`
- Console do navegador (F12)
- Logs da aplicação: http://127.0.0.1:8000/logs

## 🎨 Capturas de Tela

### Interface Principal
![Interface moderna com formulário de consulta]

### Resultados
![Cards das ofertas ordenadas com destaque para a melhor oferta]

### Gráficos
![Gráfico comparativo de taxa de juros vs custo total]

## 🔒 Segurança

- Validação de entrada
- Sanitização de CPF
- CSRF Protection
- Timeout de requisições
- Rate limiting na API

## 📈 Performance

- Cache de consultas
- Timeout otimizado (30s)
- Processamento assíncrono
- Logs estruturados
- Banco de dados indexado

## 🚀 Próximos Passos

- [ ] Deploy em servidor de produção
- [ ] Implementar cache Redis
- [ ] Adicionar testes automatizados
- [ ] Implementar autenticação JWT
- [ ] Adicionar mais instituições financeiras

## 👨‍💻 Desenvolvedor

**[Seu Nome]**
- GitHub: [seu-github]
- LinkedIn: [seu-linkedin]
- Email: [seu-email]

## 📄 Licença

Este projeto foi desenvolvido como teste prático e está disponível para avaliação.

---

⭐ **Obrigado pela oportunidade de demonstrar minhas habilidades!**