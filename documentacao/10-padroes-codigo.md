# 10. Padrões de Código e Boas Práticas

## 10.1 Visão Geral

Este documento define os padrões de código e boas práticas para o desenvolvimento e manutenção do Sistema de Agendamento de Postagens AW7. Seguir estes padrões garante que o código seja consistente, legível e mais fácil de manter, independentemente de quem esteja trabalhando nele.

## 10.2 Estrutura de Arquivos e Diretórios

### 10.2.1 Organização de Diretórios

O sistema segue a seguinte estrutura de diretórios:

```
/Postagens/
├── admin/                 # Scripts administrativos
├── ajax/                  # Endpoints para requisições AJAX
├── arquivos/              # Arquivos de mídia organizados por cliente
├── assets/                # Recursos estáticos
│   ├── css/               # Folhas de estilo
│   ├── js/                # Scripts JavaScript
│   ├── img/               # Imagens do sistema
│   └── vendor/            # Bibliotecas de terceiros
├── config/                # Arquivos de configuração
├── documentacao/          # Documentação do sistema
├── includes/              # Componentes reutilizáveis
├── uploads/               # Diretório temporário para uploads
└── vendor/                # Bibliotecas de terceiros (se aplicável)
```

### 10.2.2 Nomenclatura de Arquivos

- **Arquivos PHP**: Nomes em minúsculas com underscores para separar palavras
  - Exemplo: `cliente_adicionar.php`, `postagens_agendadas.php`

- **Arquivos CSS**: Nomes em minúsculas com hífens para separar palavras
  - Exemplo: `main-style.css`, `dashboard-layout.css`

- **Arquivos JavaScript**: Nomes em camelCase ou com hífens
  - Exemplo: `formValidation.js`, `post-scheduler.js`

- **Arquivos de Imagem**: Nomes descritivos em minúsculas com hífens
  - Exemplo: `logo-header.png`, `icon-upload.svg`

## 10.3 Padrões de PHP

### 10.3.1 Estilo de Codificação

- **Indentação**: 4 espaços (não usar tabs)
- **Comprimento de Linha**: Máximo de 120 caracteres
- **Chaves**: Abertura na mesma linha da declaração, fechamento em linha separada
- **Espaçamento**: Um espaço após palavras-chave e antes/depois de operadores

```php
// Correto
if ($condition) {
    // código
} else {
    // código
}

// Incorreto
if($condition){
    // código
}
else {
    // código
}
```

### 10.3.2 Convenções de Nomenclatura

- **Variáveis**: camelCase, começando com letra minúscula
  - Exemplo: `$userName`, `$clienteId`, `$dataPostagem`

- **Constantes**: Todas maiúsculas com underscores
  - Exemplo: `MAX_FILE_SIZE`, `UPLOAD_DIR`, `APP_NAME`

- **Funções**: camelCase, começando com letra minúscula
  - Exemplo: `getUploadPath()`, `registrarLog()`, `convertToUTC()`

- **Classes**: PascalCase, começando com letra maiúscula
  - Exemplo: `Database`, `FileUploader`, `PostManager`

- **Métodos de Classes**: camelCase, começando com letra minúscula
  - Exemplo: `connect()`, `getUserById()`, `savePost()`

- **Nomes de Tabelas**: Minúsculas, no singular
  - Exemplo: `usuario`, `cliente`, `postagem`

- **Colunas de Banco de Dados**: snake_case, minúsculas
  - Exemplo: `nome_cliente`, `data_criacao`, `post_id_unique`

### 10.3.3 Estrutura de Arquivos PHP

Cada arquivo PHP deve seguir esta estrutura:

```php
<?php
/**
 * Nome do arquivo
 * Descrição breve do propósito do arquivo
 *
 * @author Nome do Autor
 * @version 1.0
 */

// Includes e requires
require_once 'config/config.php';
require_once 'config/db.php';

// Declaração de constantes específicas do arquivo (se necessário)
define('CONSTANTE_LOCAL', 'valor');

// Funções e lógica principal do arquivo
function minhaFuncao() {
    // código
}

// Código principal
// ...

// Inclusão do footer (se for uma página)
require_once 'includes/footer.php';
```

### 10.3.4 Comentários

- **Cabeçalho de Arquivo**: Descrição do propósito do arquivo
- **Cabeçalho de Função**: Descrição, parâmetros e valor de retorno
- **Comentários em Linha**: Para explicar lógica complexa ou não óbvia
- **Blocos de Código**: Comentários para separar seções lógicas

```php
/**
 * Gera um nome de arquivo único no formato solicitado
 * 
 * @param string $cliente_nome Nome do cliente para o slug
 * @param string $extensao Extensão do arquivo (sem ponto)
 * @return string Nome de arquivo único no formato [cliente_slug]_[timestamp].[extensao]
 */
function generateUniqueFilename($cliente_nome, $extensao) {
    // Remover caracteres especiais e converter para minúsculas
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    
    // Gerar timestamp único
    $timestamp = date('mdYHis') . substr(microtime(), 2, 3);
    
    // Retornar nome formatado
    return $cliente_slug . '_' . $timestamp . '.' . $extensao;
}
```

### 10.3.5 Tratamento de Erros

- Usar blocos try-catch para operações que podem falhar
- Registrar erros em log com mensagens descritivas
- Exibir mensagens amigáveis para o usuário

```php
try {
    // Operação que pode falhar
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
} catch (PDOException $e) {
    // Registrar erro detalhado
    error_log("Database Error: " . $e->getMessage() . " in " . __FILE__ . " on line " . __LINE__);
    
    // Mensagem amigável para o usuário
    setFlashMessage('danger', 'Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.');
    
    // Redirecionar ou retornar
    redirect('index.php');
}
```

### 10.3.6 Segurança

- Sempre usar prepared statements para consultas SQL
- Sanitizar todas as entradas de usuário
- Escapar saídas para prevenir XSS
- Validar dados antes de processá-los

```php
// Sanitização de entrada
$input = sanitize($_POST['input']);

// Consulta segura com prepared statement
$stmt = $conn->prepare("SELECT * FROM usuario WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();

// Escape de saída
echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
```

## 10.4 Padrões de JavaScript

### 10.4.1 Estilo de Codificação

- **Indentação**: 2 espaços
- **Ponto e Vírgula**: Obrigatório ao final de cada instrução
- **Aspas**: Preferencialmente aspas simples para strings
- **Comprimento de Linha**: Máximo de 100 caracteres

```javascript
// Correto
function validateForm() {
  const userName = document.getElementById('userName').value;
  if (userName === '') {
    showError('O nome de usuário é obrigatório');
    return false;
  }
  return true;
}
```

### 10.4.2 Convenções de Nomenclatura

- **Variáveis e Funções**: camelCase
  - Exemplo: `userName`, `validateForm()`, `showError()`

- **Constantes**: Todas maiúsculas com underscores
  - Exemplo: `MAX_ATTEMPTS`, `API_URL`

- **Classes**: PascalCase
  - Exemplo: `FormValidator`, `FileUploader`

- **IDs de Elementos HTML**: camelCase ou kebab-case
  - Exemplo: `userName` ou `user-name`

- **Classes CSS**: kebab-case
  - Exemplo: `form-container`, `error-message`

### 10.4.3 Organização de Código JavaScript

- Agrupar código relacionado em arquivos separados
- Usar namespaces ou módulos para evitar poluição do escopo global
- Comentar funções e blocos de código complexos
- Evitar código inline em HTML

```javascript
// Namespace para funcionalidades de postagem
const PostManager = {
  // Configurações
  config: {
    maxFileSize: 1024 * 1024 * 50, // 50MB
    allowedTypes: ['image/jpeg', 'image/png', 'video/mp4']
  },
  
  // Inicialização
  init: function() {
    this.bindEvents();
    this.setupValidation();
  },
  
  // Vinculação de eventos
  bindEvents: function() {
    $('#uploadForm').on('submit', this.handleSubmit.bind(this));
    $('#fileInput').on('change', this.validateFile.bind(this));
  },
  
  // Métodos específicos
  validateFile: function(event) {
    // Código de validação
  },
  
  handleSubmit: function(event) {
    // Código de submissão
  }
};

// Inicializar quando o documento estiver pronto
$(document).ready(function() {
  PostManager.init();
});
```

### 10.4.4 jQuery e Bibliotecas

- Usar `$` como prefixo para variáveis que armazenam objetos jQuery
- Encadear métodos jQuery quando apropriado
- Armazenar seletores frequentemente usados em variáveis
- Usar versões minificadas em produção

```javascript
// Armazenar seletores em variáveis
const $form = $('#uploadForm');
const $fileInput = $('#fileInput');
const $submitButton = $('#submitButton');

// Encadeamento de métodos
$form.addClass('loading')
     .find('.error-message')
     .hide();

// Delegação de eventos
$form.on('click', '.remove-file', function(event) {
  $(this).closest('.file-item').remove();
});
```

## 10.5 Padrões de CSS

### 10.5.1 Estilo de Codificação

- **Indentação**: 2 espaços
- **Formatação**: Uma propriedade por linha
- **Organização**: Agrupar propriedades relacionadas
- **Comentários**: Usar para separar seções

```css
/* Correto */
.button {
  display: inline-block;
  padding: 10px 15px;
  background-color: #6CBD45;
  color: white;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.button:hover {
  background-color: #C62E60;
}
```

### 10.5.2 Organização de Arquivos CSS

- Separar CSS em arquivos lógicos (reset, layout, componentes, etc.)
- Usar comentários para dividir seções dentro de arquivos
- Manter uma ordem consistente de propriedades

```css
/* Reset e estilos base */
@import 'reset.css';
@import 'typography.css';

/* Layout e estrutura */
@import 'layout.css';
@import 'grid.css';

/* Componentes */
@import 'buttons.css';
@import 'forms.css';
@import 'tables.css';

/* Páginas específicas */
@import 'dashboard.css';
@import 'post-scheduler.css';
```

### 10.5.3 Nomenclatura de Classes

- Usar kebab-case para nomes de classes
- Seguir uma metodologia como BEM (Block, Element, Modifier)
- Evitar seletores muito específicos ou profundos

```css
/* Exemplo usando BEM */
.post-card { /* Bloco */
  margin-bottom: 20px;
}

.post-card__title { /* Elemento */
  font-size: 18px;
  font-weight: bold;
}

.post-card__image { /* Elemento */
  width: 100%;
}

.post-card--featured { /* Modificador */
  border: 2px solid #6CBD45;
}
```

### 10.5.4 Responsividade

- Usar media queries para diferentes tamanhos de tela
- Adotar uma abordagem mobile-first quando possível
- Definir breakpoints consistentes em todo o projeto

```css
/* Base (mobile) */
.container {
  padding: 10px;
}

/* Tablet */
@media (min-width: 768px) {
  .container {
    padding: 20px;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .container {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
  }
}
```

## 10.6 Padrões de Banco de Dados

### 10.6.1 Nomenclatura

- **Tabelas**: Minúsculas, no singular
  - Exemplo: `usuario`, `cliente`, `postagem`

- **Colunas**: snake_case, minúsculas
  - Exemplo: `nome_cliente`, `data_criacao`, `post_id_unique`

- **Chaves Primárias**: Preferencialmente `id`
  - Exemplo: `usuario.id`, `cliente.id`

- **Chaves Estrangeiras**: Nome da tabela referenciada no singular + `_id`
  - Exemplo: `cliente_id`, `usuario_id`

- **Índices**: Prefixo `idx_` seguido do nome da(s) coluna(s)
  - Exemplo: `idx_nome_cliente`, `idx_data_postagem`

- **Chaves Únicas**: Prefixo `uk_` seguido do nome da(s) coluna(s)
  - Exemplo: `uk_email`, `uk_post_id_unique`

### 10.6.2 Estrutura de Tabelas

- Sempre incluir uma chave primária auto-incremento
- Incluir colunas de auditoria (`data_criacao`, `data_atualizacao`)
- Usar tipos de dados apropriados e eficientes
- Definir charset e collation consistentes (preferencialmente utf8mb4)

```sql
CREATE TABLE cliente (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome_cliente VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    telefone VARCHAR(20) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    data_criacao DATETIME NOT NULL,
    data_atualizacao DATETIME NULL,
    usuario_id INT(11) NULL,
    PRIMARY KEY (id),
    KEY idx_nome_cliente (nome_cliente),
    KEY idx_ativo (ativo),
    KEY fk_cliente_usuario (usuario_id),
    CONSTRAINT fk_cliente_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 10.6.3 Consultas SQL

- Usar aliases para tabelas em JOINs
- Indentar e formatar consultas para legibilidade
- Usar comentários para explicar consultas complexas
- Sempre usar prepared statements no código PHP

```sql
-- Exemplo de consulta formatada
SELECT 
    p.id, 
    p.data_postagem, 
    p.tipo_postagem, 
    p.formato, 
    c.nome_cliente, 
    u.nome as usuario_nome
FROM 
    postagem p
    JOIN cliente c ON p.cliente_id = c.id
    JOIN usuario u ON p.usuario_id = u.id
WHERE 
    p.data_postagem >= CURRENT_DATE()
    AND p.status = 'agendado'
ORDER BY 
    p.data_postagem ASC
LIMIT 20;
```

## 10.7 Controle de Versão (Git)

### 10.7.1 Estrutura de Branches

- **main/master**: Branch principal, sempre estável
- **develop**: Branch de desenvolvimento
- **feature/[nome]**: Branches para novas funcionalidades
- **bugfix/[nome]**: Branches para correções de bugs
- **release/[versão]**: Branches para preparação de releases

### 10.7.2 Commits

- Mensagens claras e descritivas no imperativo
- Prefixos para indicar o tipo de alteração
- Referenciar números de issue quando aplicável

```
feat: Adiciona sistema de agendamento recorrente
fix: Corrige erro na validação de arquivos de vídeo
docs: Atualiza documentação de webhooks
style: Formata código de acordo com padrões
refactor: Simplifica função de upload de arquivos
test: Adiciona testes para módulo de autenticação
chore: Atualiza dependências
```

### 10.7.3 Pull Requests

- Título claro descrevendo a alteração
- Descrição detalhada do que foi feito e por quê
- Referenciar issues relacionadas
- Solicitar revisão de pelo menos um outro desenvolvedor
- Garantir que todos os testes passam antes de mesclar

### 10.7.4 Versionamento

Seguir Versionamento Semântico (SemVer):

- **MAJOR.MINOR.PATCH** (ex: 1.2.3)
- **MAJOR**: Mudanças incompatíveis com versões anteriores
- **MINOR**: Adições de funcionalidades compatíveis
- **PATCH**: Correções de bugs compatíveis

## 10.8 Documentação de Código

### 10.8.1 Documentação de Funções e Métodos

Usar formato PHPDoc para documentar funções:

```php
/**
 * Converte uma data brasileira para formato UTC
 * 
 * @param string $data Data no formato d/m/Y ou data e hora combinados
 * @param string $hora Hora no formato H:i (opcional)
 * @return string Data em formato UTC (ISO 8601)
 * @throws InvalidArgumentException Se a data for inválida
 */
function convertToUTC($data, $hora = null) {
    // código
}
```

### 10.8.2 Documentação de Classes

```php
/**
 * Classe Database
 * 
 * Gerencia conexões e operações com o banco de dados
 * 
 * @package AW7\Database
 * @author Equipe AW7
 * @version 1.0
 */
class Database {
    // propriedades e métodos
}
```

### 10.8.3 Documentação de Constantes

```php
/**
 * Tamanho máximo de arquivo permitido para upload (1GB)
 * @var int
 */
define('MAX_FILE_SIZE', 1073741824);
```

## 10.9 Testes

### 10.9.1 Tipos de Testes

- **Testes Unitários**: Testar funções e métodos isoladamente
- **Testes de Integração**: Testar interações entre componentes
- **Testes Funcionais**: Testar fluxos completos do usuário

### 10.9.2 Estrutura de Testes

- Organizar testes em diretório separado (`/tests`)
- Nomear arquivos de teste com sufixo `Test`
- Agrupar testes relacionados em classes de teste

```php
/**
 * Testes para funções de upload de arquivos
 */
class UploadTest extends TestCase {
    /**
     * Testa a geração de nomes de arquivo únicos
     */
    public function testGenerateUniqueFilename() {
        $filename = generateUniqueFilename('Cliente Teste', 'jpg');
        
        // Verificar formato esperado
        $this->assertMatchesRegularExpression('/^clienteteste_\d+\.jpg$/', $filename);
    }
    
    /**
     * Testa a validação de tipos de arquivo
     */
    public function testValidateFileType() {
        // código de teste
    }
}
```

## 10.10 Revisão de Código

### 10.10.1 Checklist de Revisão

Antes de submeter código para revisão, verifique:

1. **Funcionalidade**: O código faz o que deveria fazer?
2. **Segurança**: O código está livre de vulnerabilidades?
3. **Performance**: O código é eficiente?
4. **Legibilidade**: O código é fácil de entender?
5. **Manutenibilidade**: O código será fácil de manter?
6. **Padrões**: O código segue os padrões definidos?
7. **Testes**: Existem testes adequados?
8. **Documentação**: O código está bem documentado?

### 10.10.2 Processo de Revisão

1. O autor submete um pull request
2. Pelo menos um revisor examina o código
3. O revisor fornece feedback construtivo
4. O autor faz as alterações necessárias
5. O revisor aprova as alterações
6. O código é mesclado na branch de destino

## 10.11 Recursos e Referências

- [PHP-FIG PSR Standards](https://www.php-fig.org/psr/)
- [Google JavaScript Style Guide](https://google.github.io/styleguide/jsguide.html)
- [Airbnb CSS/Sass Styleguide](https://github.com/airbnb/css)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [BEM Methodology](http://getbem.com/)
- [PHPDoc Reference](https://docs.phpdoc.org/guide/references/phpdoc/index.html)

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
