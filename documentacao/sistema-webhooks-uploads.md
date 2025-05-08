# Sistema de Webhooks e Upload de Arquivos

## Índice
1. [Sistema de Webhooks](#sistema-de-webhooks)
   - [Visão Geral](#visão-geral-webhooks)
   - [Configuração](#configuração-webhooks)
   - [Fluxo de Funcionamento](#fluxo-de-funcionamento-webhooks)
   - [Formato dos Dados](#formato-dos-dados)
   - [Tratamento de Erros](#tratamento-de-erros-webhooks)
   - [Segurança](#segurança-webhooks)
   - [Logs e Monitoramento](#logs-e-monitoramento-webhooks)
   - [Troubleshooting](#troubleshooting-webhooks)

2. [Sistema de Upload de Arquivos](#sistema-de-upload-de-arquivos)
   - [Visão Geral](#visão-geral-uploads)
   - [Estrutura de Diretórios](#estrutura-de-diretórios)
   - [Configuração](#configuração-uploads)
   - [Fluxo de Funcionamento](#fluxo-de-funcionamento-uploads)
   - [Nomenclatura de Arquivos](#nomenclatura-de-arquivos)
   - [Tipos de Arquivos Suportados](#tipos-de-arquivos-suportados)
   - [Limites e Restrições](#limites-e-restrições)
   - [Tratamento de Erros](#tratamento-de-erros-uploads)
   - [Segurança](#segurança-uploads)
   - [Troubleshooting](#troubleshooting-uploads)

---

## Sistema de Webhooks

<a id="visão-geral-webhooks"></a>
### Visão Geral

O sistema de webhooks permite a integração em tempo real entre o sistema de agendamento de postagens e sistemas externos, como ferramentas de automação. Quando uma postagem é agendada, o sistema envia automaticamente uma notificação via webhook para um endpoint pré-configurado, contendo todas as informações relevantes sobre a postagem.

Este mecanismo permite que ações automatizadas sejam executadas em resposta ao agendamento de uma nova postagem, como a criação de tarefas em sistemas de gerenciamento de projetos, notificações em ferramentas de comunicação, ou o próprio agendamento da publicação em plataformas de mídia social.

<a id="configuração-webhooks"></a>
### Configuração

#### Configurações Globais

O webhook é configurado globalmente através da constante `WEBHOOK_URL` no arquivo `config/config.php`:

```php
define('WEBHOOK_URL', 'https://automacao2.aw7agencia.com.br/webhook/agendarpostagem');
```

Esta URL é o endpoint que receberá as notificações de novas postagens agendadas.

#### Configuração por Cliente

O sistema também suporta webhooks específicos por cliente, que podem ser configurados na tabela `webhooks` do banco de dados:

| Campo | Descrição |
|-------|-----------|
| id | Identificador único do webhook |
| cliente_id | ID do cliente associado ao webhook |
| url | URL do endpoint do webhook |
| ativo | Status do webhook (1 = ativo, 0 = inativo) |
| data_criacao | Data de criação do registro |
| ultima_atualizacao | Data da última atualização do registro |

<a id="fluxo-de-funcionamento-webhooks"></a>
### Fluxo de Funcionamento

1. **Agendamento da Postagem**: Quando um usuário agenda uma nova postagem através do formulário em `index.php`, os dados são processados e salvos no banco de dados.

2. **Confirmação da Postagem**: Após o usuário confirmar os detalhes da postagem em `confirmar_postagem.php`, o sistema:
   - Insere os dados na tabela `postagens`
   - Prepara os dados para o webhook
   - Envia a notificação via webhook

3. **Envio do Webhook**: O sistema utiliza a função `enviarWebhook()` para enviar os dados via HTTP POST para o endpoint configurado:

```php
function enviarWebhook($dados) {
    $url = WEBHOOK_URL;
    
    // Configurar a requisição cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dados));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Source: AW7-Postagens'
    ]);
    
    // Executar a requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Registrar resultado
    error_log("Webhook enviado para {$url}. Código: {$httpCode}. Resposta: {$response}");
    
    // Retornar resultado
    return [
        'success' => ($httpCode >= 200 && $httpCode < 300),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}
```

4. **Atualização do Status**: O sistema atualiza o campo `webhook_status` na tabela `postagens` com o resultado do envio:
   - `1` = Enviado com sucesso
   - `0` = Falha no envio

5. **Retry Mechanism**: Em caso de falha, o sistema pode tentar reenviar o webhook através de um job agendado (se configurado).

<a id="formato-dos-dados"></a>
### Formato dos Dados

Os dados enviados via webhook seguem o formato JSON abaixo:

```json
{
  "post_id": "123",
  "post_id_unique": "clientenome_05062025142530123",
  "cliente": {
    "id": "45",
    "nome": "Nome do Cliente",
    "instagram": "@cliente_instagram",
    "id_instagram": "12345678"
  },
  "tipo_postagem": "Feed",
  "formato": "Imagem Única",
  "data_postagem": "2025-05-06T14:25:30-03:00",
  "legenda": "Texto da legenda da postagem #hashtag",
  "arquivos": [
    {
      "name": "clientenome_05062025142530123.jpg",
      "url": "https://postar.agenciaraff.com.br/arquivos/clientenome/imagem/clientenome_05062025142530123.jpg",
      "tipo": "imagem"
    }
  ],
  "usuario": {
    "id": "3",
    "nome": "Nome do Usuário"
  },
  "data_criacao": "2025-05-06T14:20:15-03:00"
}
```

<a id="tratamento-de-erros-webhooks"></a>
### Tratamento de Erros

O sistema implementa os seguintes mecanismos para tratamento de erros:

1. **Validação de Resposta HTTP**: O sistema verifica o código de status HTTP retornado pelo endpoint. Códigos na faixa 200-299 são considerados sucesso, enquanto outros códigos são tratados como erro.

2. **Timeout**: A requisição tem um timeout configurado para evitar bloqueios no sistema em caso de endpoints lentos.

3. **Registro de Erros**: Todos os erros são registrados no log do sistema para posterior análise.

4. **Notificação Visual**: Em caso de falha no envio do webhook, o sistema exibe uma mensagem de alerta para o usuário, mas ainda confirma o agendamento da postagem.

<a id="segurança-webhooks"></a>
### Segurança

O sistema implementa as seguintes medidas de segurança para os webhooks:

1. **HTTPS**: Todas as comunicações são realizadas via HTTPS para garantir a criptografia dos dados.

2. **Headers de Identificação**: O sistema inclui o header `X-Source: AW7-Postagens` para que o receptor possa validar a origem da requisição.

3. **Validação de Dados**: Todos os dados são sanitizados antes de serem enviados via webhook.

4. **Acesso Restrito**: A configuração de webhooks é restrita a usuários administradores.

<a id="logs-e-monitoramento-webhooks"></a>
### Logs e Monitoramento

O sistema mantém logs detalhados de todas as operações relacionadas a webhooks:

1. **Logs de Sistema**: Todas as operações de webhook são registradas no log do sistema através da função `error_log()`.

2. **Tabela de Logs**: As operações também são registradas na tabela `logs` do banco de dados, incluindo:
   - Usuário que realizou a ação
   - Tipo de ação (envio de webhook)
   - Detalhes da operação
   - Resultado (sucesso/falha)
   - Data e hora

3. **Dashboard Administrativo**: Administradores podem visualizar o status de envio de webhooks no dashboard administrativo.

<a id="troubleshooting-webhooks"></a>
### Troubleshooting

#### Problemas Comuns e Soluções

1. **Webhook não está sendo enviado**
   - Verifique se a constante `WEBHOOK_URL` está corretamente configurada
   - Confirme se o servidor tem acesso à internet
   - Verifique se a extensão cURL está habilitada no PHP

2. **Endpoint retorna erro**
   - Verifique se o endpoint está online e acessível
   - Confirme se o formato dos dados está correto
   - Verifique os logs do sistema para detalhes do erro

3. **Dados incorretos no webhook**
   - Verifique a função `enviarWebhook()` para garantir que todos os dados estão sendo incluídos
   - Confirme se as URLs dos arquivos estão corretas
   - Verifique se a constante `FILES_BASE_URL` está configurada corretamente

---

## Sistema de Upload de Arquivos

<a id="visão-geral-uploads"></a>
### Visão Geral

O sistema de upload de arquivos permite que os usuários façam upload de imagens e vídeos para serem incluídos nas postagens agendadas. O sistema suporta uploads únicos (imagem ou vídeo) e múltiplos (carrossel), gerencia automaticamente a criação de diretórios, gera nomes de arquivos únicos e armazena os arquivos em uma estrutura organizada.

<a id="estrutura-de-diretórios"></a>
### Estrutura de Diretórios

O sistema organiza os arquivos na seguinte estrutura:

```
www.meusite.com.br/
├── arquivos/
│   ├── [nome_do_cliente]/
│   │   ├── imagem/
│   │   │   ├── [nome_do_cliente]_[timestamp].jpg
│   │   │   └── ...
│   │   └── video/
│   │       ├── [nome_do_cliente]_[timestamp].mp4
│   │       └── ...
│   └── ...
└── uploads/
    └── [arquivos temporários]
```

- **arquivos/**: Diretório principal para armazenamento permanente de arquivos
  - **[nome_do_cliente]/**: Subdiretório para cada cliente (nome sem espaços e em minúsculo)
    - **imagem/**: Subdiretório para imagens
    - **video/**: Subdiretório para vídeos

- **uploads/**: Diretório para uploads temporários antes de serem processados

<a id="configuração-uploads"></a>
### Configuração

O sistema de upload é configurado no arquivo `config/config.php` através das seguintes constantes:

```php
// Diretórios de upload
define('UPLOAD_BASE_DIR', $_SERVER['DOCUMENT_ROOT'] . '/arquivos/');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');

// Tamanho máximo de arquivo
define('MAX_FILE_SIZE', 1073741824); // 1GB em bytes

// Tipos de arquivos permitidos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/mov', 'video/avi']);

// URL base para acesso aos arquivos
define('FILES_BASE_URL', 'https://postar.agenciaraff.com.br');
```

<a id="fluxo-de-funcionamento-uploads"></a>
### Fluxo de Funcionamento

1. **Seleção de Arquivos**: O usuário seleciona arquivos para upload através do formulário em `index.php`.

2. **Validação Inicial**: O sistema verifica:
   - Se arquivos foram selecionados
   - Se o número de arquivos está dentro do limite (máximo 20 para carrossel)
   - Se o tamanho total dos arquivos está dentro do limite (1GB)

3. **Processamento de Arquivos**: Para cada arquivo:
   - Determina o tipo (imagem ou vídeo)
   - Obtém o caminho de upload específico para o cliente e tipo de arquivo
   - Gera um nome de arquivo único
   - Move o arquivo para o diretório correto
   - Define permissões adequadas (0644)

4. **Armazenamento de Metadados**: O sistema armazena os seguintes metadados para cada arquivo:
   - Nome do arquivo
   - Caminho completo no servidor
   - URL pública completa

5. **Confirmação**: Os metadados dos arquivos são armazenados na sessão para serem utilizados na página de confirmação.

6. **Persistência**: Após a confirmação, os dados dos arquivos são salvos no banco de dados junto com os detalhes da postagem.

<a id="nomenclatura-de-arquivos"></a>
### Nomenclatura de Arquivos

Os arquivos são nomeados seguindo o padrão:

```
[nome_do_cliente]_[timestamp].[extensao]
```

Onde:
- **nome_do_cliente**: Nome do cliente sem espaços e em minúsculo
- **timestamp**: Data e hora no formato MMDDYYYYHHMMSSMMM (mês, dia, ano, hora, minuto, segundo, milissegundo)
- **extensao**: Extensão original do arquivo

Exemplo:
```
clientenome_05062025142530123.jpg
```

A função responsável por gerar esses nomes é:

```php
function generateUniqueFilename($cliente_nome, $extensao) {
    $cliente_slug = preg_replace('/[^a-z0-9]/', '', strtolower($cliente_nome));
    $timestamp = date('mdYHis') . substr(microtime(), 2, 3);
    return $cliente_slug . '_' . $timestamp . '.' . $extensao;
}
```

<a id="tipos-de-arquivos-suportados"></a>
### Tipos de Arquivos Suportados

#### Imagens
- JPEG/JPG (`image/jpeg`)
- PNG (`image/png`)
- GIF (`image/gif`)

#### Vídeos
- MP4 (`video/mp4`)
- MOV (`video/mov`)
- AVI (`video/avi`)

<a id="limites-e-restrições"></a>
### Limites e Restrições

1. **Tamanho Máximo Total**: 1GB para todos os arquivos combinados
2. **Número Máximo de Arquivos**: 
   - 1 para uploads únicos (imagem ou vídeo)
   - 20 para carrossel
3. **Tipos de Arquivos**: Apenas os tipos listados acima são permitidos

<a id="tratamento-de-erros-uploads"></a>
### Tratamento de Erros

O sistema implementa os seguintes mecanismos para tratamento de erros de upload:

1. **Validação de Tipo**: Verifica se o tipo MIME do arquivo está na lista de tipos permitidos.

2. **Validação de Tamanho**: Verifica se o tamanho do arquivo está dentro do limite.

3. **Criação de Diretórios**: Tenta criar diretórios automaticamente se não existirem.

4. **Verificação de Permissões**: Verifica se o sistema tem permissões para escrever nos diretórios.

5. **Mensagens de Erro**: Exibe mensagens específicas para cada tipo de erro.

6. **Logs Detalhados**: Registra informações detalhadas no log do sistema para facilitar a depuração.

<a id="segurança-uploads"></a>
### Segurança

O sistema implementa as seguintes medidas de segurança para uploads:

1. **Validação de Tipo MIME**: Verifica o tipo MIME real do arquivo, não apenas a extensão.

2. **Sanitização de Nomes**: Remove caracteres especiais e espaços dos nomes de arquivos.

3. **Diretórios Separados**: Mantém os arquivos em diretórios separados por cliente e tipo.

4. **Permissões Restritas**: Define permissões adequadas para os arquivos (0644) e diretórios (0755).

5. **Validação de Usuário**: Apenas usuários autenticados podem fazer upload de arquivos.

<a id="troubleshooting-uploads"></a>
### Troubleshooting

#### Ferramenta de Diagnóstico

O sistema inclui uma ferramenta de diagnóstico em `admin/diagnostico_upload.php` que permite:

- Verificar as configurações do servidor
- Testar a existência e permissões dos diretórios
- Testar a criação de diretórios
- Verificar o acesso web aos arquivos
- Realizar um upload de teste
- Receber recomendações para resolver problemas

#### Problemas Comuns e Soluções

1. **Falha ao fazer upload de arquivos**
   - Verifique se os diretórios de upload existem e têm permissões corretas (755)
   - Confirme se o tamanho do arquivo está dentro dos limites configurados no PHP (`upload_max_filesize`, `post_max_size`)
   - Verifique se o tipo de arquivo é permitido

2. **Arquivos não estão sendo salvos no local correto**
   - Verifique se `UPLOAD_BASE_DIR` está configurado corretamente para usar `$_SERVER['DOCUMENT_ROOT']`
   - Confirme se os diretórios específicos do cliente estão sendo criados corretamente
   - Verifique os logs para identificar o caminho exato onde o sistema está tentando salvar os arquivos

3. **URLs incorretas nos webhooks**
   - Verifique se `FILES_BASE_URL` está configurado corretamente
   - Confirme se a função `getUploadPath()` está gerando URLs corretas
   - Verifique se o formato da URL está seguindo o padrão esperado pelo sistema receptor

4. **Permissões negadas**
   - Verifique se o usuário do servidor web (www-data, apache, etc.) tem permissões para escrever nos diretórios
   - Confirme se os diretórios têm permissões 755 e os arquivos 644
   - Em ambientes compartilhados (como CPanel), verifique se as permissões de grupo estão configuradas corretamente

5. **Diretórios não estão sendo criados automaticamente**
   - Verifique se a função `mkdir()` está sendo chamada com o parâmetro `recursive` como `true`
   - Confirme se o diretório pai tem permissões adequadas
   - Verifique os logs para identificar erros específicos

#### Logs e Depuração

O sistema registra informações detalhadas sobre o processo de upload no log do PHP:

```php
error_log("Processando upload: Cliente ID: {$cliente_id}, Nome: {$cliente_nome}, Tipo: {$tipo_arquivo}");
error_log("Caminho de upload: " . print_r($upload_path, true));
error_log("Tentando mover arquivo para: {$targetPath}");
```

Para visualizar esses logs:
- Em ambiente de desenvolvimento: Verifique o arquivo de log do PHP (geralmente em `/var/log/apache2/error.log` ou similar)
- Em CPanel: Acesse a seção "Logs de Erro" no painel de controle

---

## Integração entre Webhooks e Uploads

O sistema de webhooks e o sistema de upload de arquivos trabalham em conjunto para garantir que as postagens agendadas incluam as URLs corretas dos arquivos enviados. Esta integração ocorre da seguinte forma:

1. O usuário faz upload de arquivos através do formulário
2. O sistema processa os uploads e gera URLs públicas para cada arquivo
3. Essas URLs são incluídas nos dados enviados via webhook
4. O sistema receptor do webhook pode acessar os arquivos diretamente através dessas URLs

Esta integração permite que o sistema receptor (como uma ferramenta de automação) tenha acesso imediato aos arquivos associados à postagem, sem necessidade de downloads ou transferências adicionais.

---

## Conclusão

Os sistemas de webhooks e upload de arquivos são componentes críticos do sistema de agendamento de postagens, permitindo a integração com sistemas externos e o gerenciamento eficiente de mídia. A correta configuração e manutenção desses sistemas é essencial para o funcionamento adequado da plataforma como um todo.

Para garantir o funcionamento adequado, é importante:

1. Manter as constantes de configuração atualizadas
2. Verificar regularmente os logs do sistema
3. Garantir que os diretórios tenham permissões adequadas
4. Monitorar o espaço em disco disponível
5. Realizar backups regulares dos arquivos e do banco de dados

Em caso de problemas, utilize a ferramenta de diagnóstico e consulte os logs do sistema para identificar e resolver as questões específicas.
