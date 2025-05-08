# 2. Arquitetura e Estrutura

## 2.1 Arquitetura do Sistema

O Sistema de Agendamento de Postagens AW7 segue uma arquitetura tradicional de aplicação web PHP, organizada em camadas para facilitar a manutenção e escalabilidade.

### 2.1.1 Camada de Apresentação

A camada de apresentação é composta por arquivos PHP que geram a interface do usuário, utilizando HTML, CSS e JavaScript. Esta camada é responsável por:

- Renderizar formulários e interfaces
- Validar entradas do usuário no lado do cliente
- Realizar requisições AJAX para atualização dinâmica de conteúdo
- Fornecer feedback visual ao usuário

Os principais componentes desta camada são:

- **Arquivos de Template**: `includes/header.php`, `includes/footer.php`
- **Páginas da Interface**: Arquivos PHP na raiz do projeto
- **Recursos Estáticos**: Arquivos na pasta `assets/`

### 2.1.2 Camada de Lógica de Negócio

A camada de lógica de negócio contém as regras e processos do sistema. Esta camada é responsável por:

- Processar requisições do usuário
- Aplicar regras de negócio
- Validar dados no lado do servidor
- Coordenar operações entre diferentes componentes

Os principais componentes desta camada são:

- **Funções Utilitárias**: Definidas em `includes/functions.php`
- **Funções de Autenticação**: Definidas em `includes/auth.php`
- **Funções de Log**: Definidas em `includes/log_functions.php`
- **Configurações do Sistema**: Definidas em `config/config.php`

### 2.1.3 Camada de Acesso a Dados

A camada de acesso a dados é responsável pela comunicação com o banco de dados e sistema de arquivos. Esta camada é responsável por:

- Estabelecer conexões com o banco de dados
- Executar consultas SQL
- Gerenciar transações
- Manipular arquivos no sistema de arquivos

Os principais componentes desta camada são:

- **Classe Database**: Definida em `config/db.php`
- **Funções de Upload**: Definidas em `config/config.php`

### 2.1.4 Camada de Integração

A camada de integração é responsável pela comunicação com sistemas externos. Esta camada é responsável por:

- Enviar webhooks para sistemas externos
- Receber e processar requisições de APIs
- Gerenciar autenticação com serviços externos

Os principais componentes desta camada são:

- **Funções de Webhook**: Implementadas em várias partes do sistema
- **Endpoints AJAX**: Arquivos na pasta `ajax/`

## 2.2 Padrões de Design

O sistema utiliza vários padrões de design para organizar seu código e funcionalidades:

### 2.2.1 Singleton

O padrão Singleton é utilizado na classe `Database` para garantir que apenas uma instância da conexão com o banco de dados seja criada durante a execução da aplicação.

```php
class Database {
    private static $instance = null;
    private $conn;
    
    // Construtor privado
    private function __construct() {
        // Configuração da conexão
    }
    
    // Método para obter a instância
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Método para conectar ao banco
    public function connect() {
        // Lógica de conexão
        return $this->conn;
    }
}
```

### 2.2.2 Factory Method

O sistema utiliza um padrão similar ao Factory Method para criar diferentes tipos de logs baseados no contexto:

```php
// Função factory para criar diferentes tipos de logs
function createLog($tipo, $acao, $detalhes, $modulo) {
    switch ($tipo) {
        case 'sessao':
            return registrarLogSessao($acao, $detalhes, $modulo);
        case 'usuario':
            return registrarLogUsuario($acao, $detalhes, $modulo);
        default:
            return registrarLog($acao, $detalhes, $modulo);
    }
}
```

### 2.2.3 MVC (Model-View-Controller)

Embora não siga estritamente o padrão MVC, o sistema organiza seu código de forma similar:

- **Model**: Funções e classes que interagem com o banco de dados
- **View**: Arquivos PHP que geram a interface do usuário
- **Controller**: Lógica de processamento em cada arquivo PHP principal

## 2.3 Fluxo de Execução

O fluxo típico de execução no sistema segue estas etapas:

1. **Inicialização**:
   - Carregamento de configurações (`config/config.php`)
   - Estabelecimento de conexão com o banco de dados (`config/db.php`)
   - Verificação de autenticação (`includes/auth.php`)

2. **Processamento de Requisição**:
   - Validação de entradas do usuário
   - Execução de operações no banco de dados
   - Manipulação de arquivos (se necessário)
   - Registro de logs

3. **Geração de Resposta**:
   - Carregamento do cabeçalho (`includes/header.php`)
   - Geração do conteúdo específico da página
   - Carregamento do rodapé (`includes/footer.php`)

4. **Finalização**:
   - Fechamento de conexões
   - Limpeza de recursos

## 2.4 Gerenciamento de Sessões

O sistema utiliza sessões PHP para gerenciar o estado do usuário entre requisições:

- **Inicialização de Sessão**: Realizada em `config/config.php`
- **Armazenamento de Dados**: Informações do usuário, mensagens flash, dados temporários
- **Timeout de Sessão**: Configurável nas configurações do sistema
- **Destruição de Sessão**: Realizada no logout ou após inatividade

```php
// Exemplo de gerenciamento de sessão
session_start();

// Verificar inatividade
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    redirect('login.php?reason=inactivity');
}

// Atualizar timestamp de última atividade
$_SESSION['last_activity'] = time();
```

## 2.5 Gerenciamento de Erros

O sistema implementa um mecanismo de gerenciamento de erros em várias camadas:

### 2.5.1 Erros PHP

- **Configuração**: Definida em `config/config.php`
- **Exibição**: Controlada por `ini_set('display_errors', 1)` (habilitada em desenvolvimento, desabilitada em produção)
- **Logging**: Erros são registrados via `error_log()`

### 2.5.2 Erros de Aplicação

- **Mensagens Flash**: Utilizadas para comunicar erros ao usuário
- **Logs de Sistema**: Erros são registrados na tabela `historico`
- **Try-Catch**: Blocos try-catch são utilizados para capturar exceções em operações críticas

```php
// Exemplo de tratamento de erro
try {
    // Operação que pode falhar
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    // Registrar erro
    error_log("Erro no banco de dados: " . $e->getMessage());
    
    // Informar usuário
    setFlashMessage('danger', 'Ocorreu um erro ao processar sua solicitação.');
    
    // Registrar no histórico
    registrarLog('Erro', $e->getMessage(), 'database');
}
```

## 2.6 Sistema de Arquivos

O sistema organiza os arquivos de mídia de forma estruturada:

### 2.6.1 Diretório `arquivos/`

Os arquivos de mídia são organizados por cliente e tipo:

```
/arquivos/
├── cliente1/
│   ├── imagem/
│   │   ├── cliente1_20250506123045.jpg
│   │   └── cliente1_20250506123456.png
│   └── video/
│       └── cliente1_20250506124512.mp4
├── cliente2/
│   ├── imagem/
│   └── video/
└── ...
```

### 2.6.2 Diretório `uploads/`

Utilizado para armazenar uploads temporários antes de serem processados e movidos para o diretório final.

### 2.6.3 Nomenclatura de Arquivos

Os arquivos seguem um padrão de nomenclatura para garantir unicidade:

```
[nome_cliente]_[timestamp].[extensao]
```

Exemplo: `cliente1_20250506123045.jpg`

## 2.7 Integrações Externas

O sistema se integra com sistemas externos através de webhooks:

### 2.7.1 Envio de Webhooks

- **Formato**: JSON via POST
- **Autenticação**: Token no cabeçalho
- **Retry**: Tentativas automáticas em caso de falha
- **Logging**: Registro de todas as tentativas

### 2.7.2 Recebimento de Webhooks

- **Endpoints**: Não implementado na versão atual
- **Validação**: Verificação de token
- **Processamento**: Ações baseadas no tipo de evento

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
