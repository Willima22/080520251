# 6. API e Integrações

## 6.1 Visão Geral das Integrações

O Sistema de Agendamento de Postagens AW7 foi projetado para se integrar com sistemas externos através de webhooks, permitindo a comunicação automática de eventos e dados. Esta seção documenta as APIs e integrações disponíveis no sistema, incluindo formatos de dados, endpoints, autenticação e exemplos de uso.

## 6.2 Sistema de Webhooks

### 6.2.1 Conceito e Funcionamento

O sistema utiliza webhooks para notificar sistemas externos sobre eventos importantes, como o agendamento de uma nova postagem. Um webhook é essencialmente uma requisição HTTP POST enviada para uma URL pré-configurada, contendo dados em formato JSON sobre o evento ocorrido.

### 6.2.2 Configuração de Webhooks

Os webhooks são configurados através da página de configurações do sistema:

**Arquivo:** `configuracoes.php`

**Campos de Configuração:**
- **URL do Webhook**: Endereço para onde as notificações serão enviadas
- **Chave de API**: Token de autenticação para validar as requisições
- **Eventos Habilitados**: Tipos de eventos que disparam o envio de webhooks

**Armazenamento:**
As configurações são armazenadas na tabela `configuracoes` do banco de dados:

```sql
SELECT valor FROM configuracoes WHERE chave = 'webhook_url';
SELECT valor FROM configuracoes WHERE chave = 'webhook_api_key';
```

## 6.3 Eventos e Payloads

### 6.3.1 Evento: Nova Postagem Agendada

Este evento é disparado quando uma nova postagem é agendada no sistema.

**Payload:**
```json
{
  "post_id": 123,
  "client_id": 45,
  "client_name": "Nome do Cliente",
  "post_type": "Instagram",
  "format": "Imagem Única",
  "scheduled_date": "2025-05-10T15:00:00Z",
  "caption": "Texto da postagem #hashtag",
  "files": [
    "https://exemplo.com/arquivos/cliente/imagem/cliente_20250506123045.jpg"
  ],
  "scheduled_date_brazil": "10/05/2025",
  "scheduled_time_brazil": "12:00"
}
```

**Campos:**
- `post_id`: ID único da postagem no sistema
- `client_id`: ID do cliente no sistema
- `client_name`: Nome do cliente
- `post_type`: Tipo de postagem (Instagram, Facebook, etc.)
- `format`: Formato da postagem (Imagem Única, Vídeo, Carrossel)
- `scheduled_date`: Data e hora agendada em formato UTC (ISO 8601)
- `caption`: Texto/legenda da postagem
- `files`: Array com URLs dos arquivos de mídia
- `scheduled_date_brazil`: Data formatada no padrão brasileiro (dd/mm/yyyy)
- `scheduled_time_brazil`: Hora formatada no padrão brasileiro (hh:mm)

### 6.3.2 Evento: Postagem Atualizada

Este evento é disparado quando uma postagem existente é atualizada.

**Payload:**
```json
{
  "post_id": 123,
  "client_id": 45,
  "client_name": "Nome do Cliente",
  "post_type": "Instagram",
  "format": "Imagem Única",
  "scheduled_date": "2025-05-10T16:00:00Z",
  "caption": "Texto atualizado da postagem #hashtag",
  "files": [
    "https://exemplo.com/arquivos/cliente/imagem/cliente_20250506123045.jpg"
  ],
  "scheduled_date_brazil": "10/05/2025",
  "scheduled_time_brazil": "13:00",
  "update_reason": "Horário alterado"
}
```

**Campos Adicionais:**
- `update_reason`: Motivo da atualização (opcional)

### 6.3.3 Evento: Postagem Excluída

Este evento é disparado quando uma postagem é excluída do sistema.

**Payload:**
```json
{
  "post_id": 123,
  "client_id": 45,
  "client_name": "Nome do Cliente",
  "delete_reason": "Cancelado pelo cliente"
}
```

**Campos:**
- `post_id`: ID único da postagem no sistema
- `client_id`: ID do cliente no sistema
- `client_name`: Nome do cliente
- `delete_reason`: Motivo da exclusão (opcional)

## 6.4 Implementação do Envio de Webhooks

### 6.4.1 Função de Envio

O sistema utiliza a função `sendWebhook()` para enviar notificações para sistemas externos:

```php
function sendWebhook($postId, $postData, $dateTimeUTC) {
    // Obter URL do webhook das configurações
    $webhookUrl = WEBHOOK_URL;
    
    // Preparar dados
    $webhookData = [
        'post_id' => $postId,
        'client_id' => $postData['cliente_id'],
        'client_name' => $postData['cliente_nome'],
        'post_type' => $postData['tipo_postagem'],
        'format' => $postData['formato'],
        'scheduled_date' => $dateTimeUTC,
        'caption' => $postData['legenda'],
        'files' => array_map(function($file) {
            return $file['url'];
        }, $postData['arquivos']),
        'scheduled_date_brazil' => date('d/m/Y', strtotime($postData['data_postagem'])),
        'scheduled_time_brazil' => $postData['hora_postagem']
    ];
    
    // Configurar cURL
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . WEBHOOK_API_KEY
    ]);
    
    // Enviar requisição
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Verificar resposta
    $success = ($httpCode >= 200 && $httpCode < 300);
    
    // Registrar tentativa
    registrarLog(
        $success ? 'Webhook enviado' : 'Erro ao enviar webhook',
        json_encode([
            'post_id' => $postId,
            'response_code' => $httpCode,
            'response' => $response
        ]),
        'webhook'
    );
    
    return $success;
}
```

### 6.4.2 Pontos de Chamada

A função `sendWebhook()` é chamada nos seguintes pontos do sistema:

1. **Após Agendamento de Nova Postagem**
   - Arquivo: `confirmar_postagem.php`
   - Contexto: Após a inserção da postagem no banco de dados

2. **Após Atualização de Postagem**
   - Arquivo: `editar_postagem.php`
   - Contexto: Após a atualização da postagem no banco de dados

3. **Reenvio Manual**
   - Arquivo: `postagens_agendadas.php`
   - Contexto: Quando o usuário solicita o reenvio do webhook

### 6.4.3 Tratamento de Falhas

O sistema implementa as seguintes estratégias para lidar com falhas no envio de webhooks:

1. **Registro de Tentativas**
   - Todas as tentativas de envio são registradas na tabela `historico`
   - Detalhes como código de resposta e corpo da resposta são armazenados

2. **Status de Webhook**
   - A tabela `postagens` mantém uma coluna `webhook_status` que indica se o webhook foi enviado com sucesso
   - Valores: 0 (não enviado/falha), 1 (enviado com sucesso)

3. **Reenvio Manual**
   - A interface permite que usuários reenviem webhooks que falharam
   - Botão "Reenviar Webhook" disponível na página de postagens agendadas

## 6.5 Segurança e Autenticação

### 6.5.1 Autenticação de Webhooks

O sistema utiliza um token de API para autenticar as requisições enviadas:

1. **Cabeçalho HTTP**
   - O token é enviado no cabeçalho `X-API-Key`
   - Exemplo: `X-API-Key: seu_token_secreto`

2. **Configuração do Token**
   - O token é configurado na página de configurações do sistema
   - Armazenado na tabela `configuracoes` com a chave `webhook_api_key`

### 6.5.2 Boas Práticas de Segurança

O sistema implementa as seguintes práticas de segurança para webhooks:

1. **HTTPS**
   - Recomenda-se que a URL do webhook utilize HTTPS para criptografar os dados em trânsito

2. **Validação de Resposta**
   - O sistema verifica se a resposta do endpoint está dentro da faixa de sucesso (2xx)
   - Respostas fora dessa faixa são tratadas como falhas

3. **Timeout**
   - A requisição tem um timeout configurado para evitar bloqueios
   - Padrão: 10 segundos

4. **Limitação de Tentativas**
   - O sistema limita o número de tentativas automáticas de reenvio
   - Tentativas adicionais devem ser manuais

## 6.6 Recebimento de Webhooks (Futuro)

Embora a versão atual do sistema não implemente o recebimento de webhooks de sistemas externos, esta funcionalidade está planejada para futuras versões.

### 6.6.1 Endpoints Planejados

1. **Atualização de Status de Postagem**
   - URL: `/api/webhook/update_status.php`
   - Método: POST
   - Payload:
     ```json
     {
       "post_id": 123,
       "status": "publicado",
       "published_at": "2025-05-10T15:05:23Z",
       "external_post_id": "instagram_post_123456"
     }
     ```

2. **Notificação de Erro**
   - URL: `/api/webhook/notify_error.php`
   - Método: POST
   - Payload:
     ```json
     {
       "post_id": 123,
       "error_code": "media_upload_failed",
       "error_message": "Falha ao fazer upload da mídia para o Instagram",
       "timestamp": "2025-05-10T15:01:12Z"
     }
     ```

### 6.6.2 Autenticação Planejada

O sistema planeja implementar os seguintes métodos de autenticação para webhooks recebidos:

1. **Token de API**
   - Verificação do cabeçalho `X-API-Key`
   - Comparação com token configurado no sistema

2. **Assinatura HMAC**
   - Verificação da assinatura no cabeçalho `X-Signature`
   - Cálculo de HMAC-SHA256 do corpo da requisição usando uma chave secreta compartilhada

## 6.7 Integração com Redes Sociais (Futuro)

O sistema planeja implementar integrações diretas com APIs de redes sociais em versões futuras.

### 6.7.1 Instagram Graph API

**Funcionalidades Planejadas:**
- Publicação direta de conteúdo
- Agendamento nativo
- Obtenção de métricas de engajamento

### 6.7.2 Facebook Marketing API

**Funcionalidades Planejadas:**
- Publicação em páginas
- Agendamento de posts
- Gerenciamento de anúncios

### 6.7.3 Twitter API

**Funcionalidades Planejadas:**
- Publicação de tweets
- Agendamento
- Monitoramento de menções

## 6.8 Exemplos de Integração

### 6.8.1 Exemplo de Recebimento de Webhook (PHP)

```php
<?php
// Arquivo que recebe webhooks do sistema AW7
// Salvar como webhook_receiver.php em seu sistema

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

// Verificar cabeçalho de autenticação
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$expectedKey = 'seu_token_secreto';

if ($apiKey !== $expectedKey) {
    http_response_code(401);
    exit('Não autorizado');
}

// Obter corpo da requisição
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

if (!$data) {
    http_response_code(400);
    exit('Dados inválidos');
}

// Processar os dados recebidos
$postId = $data['post_id'] ?? null;
$clientName = $data['client_name'] ?? 'Cliente desconhecido';
$scheduledDate = $data['scheduled_date'] ?? null;

// Registrar em log
file_put_contents(
    'webhook_log.txt',
    date('Y-m-d H:i:s') . " - Recebido webhook para postagem $postId do cliente $clientName\n",
    FILE_APPEND
);

// Processar os dados conforme necessário
// ...

// Responder com sucesso
http_response_code(200);
echo json_encode(['status' => 'success', 'message' => 'Webhook processado com sucesso']);
```

### 6.8.2 Exemplo de Recebimento de Webhook (Node.js)

```javascript
// Exemplo de recebimento de webhook em Node.js com Express
const express = require('express');
const bodyParser = require('body-parser');
const app = express();

// Configurar middleware
app.use(bodyParser.json());

// Rota para receber webhooks
app.post('/webhook/aw7', (req, res) => {
    // Verificar autenticação
    const apiKey = req.headers['x-api-key'];
    const expectedKey = 'seu_token_secreto';
    
    if (apiKey !== expectedKey) {
        return res.status(401).json({ error: 'Não autorizado' });
    }
    
    // Processar dados
    const { post_id, client_name, scheduled_date, files } = req.body;
    
    console.log(`Webhook recebido: Postagem ${post_id} para ${client_name}`);
    console.log(`Arquivos: ${files.join(', ')}`);
    
    // Processar conforme necessário
    // ...
    
    // Responder com sucesso
    res.status(200).json({ status: 'success', message: 'Webhook processado com sucesso' });
});

// Iniciar servidor
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Servidor rodando na porta ${PORT}`);
});
```

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
