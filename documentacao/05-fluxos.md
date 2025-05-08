# 5. Fluxos de Trabalho

## 5.1 Visão Geral dos Fluxos

O Sistema de Agendamento de Postagens AW7 implementa diversos fluxos de trabalho para gerenciar o ciclo de vida completo das postagens em redes sociais, desde o agendamento inicial até a publicação e monitoramento. Esta seção documenta os principais fluxos de trabalho do sistema, suas etapas, atores envolvidos e pontos de decisão.

## 5.2 Fluxo de Agendamento de Postagem

Este é o fluxo principal do sistema, que permite aos usuários agendar novas postagens para os clientes.

### 5.2.1 Diagrama do Fluxo

```
┌─────────┐     ┌─────────────┐     ┌───────────┐     ┌──────────┐     ┌─────────┐
│  Início ├────►│ Formulário  ├────►│ Upload de ├────►│Confirmação├────►│ Webhook │
│         │     │ de Postagem │     │ Arquivos  │     │           │     │         │
└─────────┘     └─────────────┘     └───────────┘     └──────────┘     └─────────┘
```

### 5.2.2 Etapas Detalhadas

1. **Acesso ao Formulário**
   - Ator: Usuário (Administrador ou Editor)
   - Arquivo: `index.php`
   - Descrição: O usuário acessa a página de agendamento de postagens, que exibe um formulário para preenchimento dos dados.

2. **Preenchimento do Formulário**
   - Ator: Usuário
   - Campos Obrigatórios:
     - Cliente (selecionado de uma lista)
     - Tipo de Postagem (Instagram, Facebook, etc.)
     - Formato (Imagem Única, Vídeo, Carrossel)
     - Data e Hora de Publicação
   - Campos Opcionais:
     - Legenda
     - Hashtags

3. **Upload de Arquivos**
   - Ator: Usuário
   - Validações:
     - Tipo de arquivo (imagem ou vídeo)
     - Tamanho máximo
     - Quantidade (para carrosséis)
   - Processamento:
     - Geração de nome único
     - Armazenamento no diretório apropriado
     - Criação de URLs para acesso

4. **Confirmação da Postagem**
   - Ator: Usuário
   - Arquivo: `confirmar_postagem.php`
   - Descrição: Exibe um resumo dos dados da postagem para confirmação
   - Ações:
     - Confirmar e finalizar
     - Voltar e editar

5. **Finalização do Agendamento**
   - Ator: Sistema
   - Processamento:
     - Conversão de data/hora para UTC
     - Inserção no banco de dados
     - Geração de ID único para a postagem
     - Registro no histórico

6. **Envio de Webhook**
   - Ator: Sistema
   - Processamento:
     - Preparação dos dados em formato JSON
     - Envio para a URL configurada
     - Verificação da resposta
     - Atualização do status no banco de dados

### 5.2.3 Pontos de Decisão

- **Validação de Campos**: Se campos obrigatórios não forem preenchidos, exibir mensagens de erro e impedir o prosseguimento.
- **Validação de Arquivos**: Se os arquivos não atenderem aos requisitos, exibir mensagens de erro e solicitar novos uploads.
- **Confirmação**: Se o usuário não confirmar, retornar ao formulário mantendo os dados preenchidos.
- **Webhook**: Se o envio falhar, registrar o erro e permitir reenvio posterior.

## 5.3 Fluxo de Gerenciamento de Clientes

Este fluxo permite aos usuários gerenciar os clientes no sistema.

### 5.3.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────┐     ┌──────────┐
│ Listar  ├────►│ Adicionar/ ├────►│ Salvar ├────►│ Histórico │
│ Clientes│     │ Editar     │     │        │     │          │
└─────────┘     └────────────┘     └────────┘     └──────────┘
     │                                                 ▲
     │                                                 │
     │               ┌────────┐                        │
     └──────────────►│ Excluir├────────────────────────┘
                     └────────┘
```

### 5.3.2 Etapas Detalhadas

1. **Listar Clientes**
   - Ator: Usuário
   - Arquivo: `clientes_visualizar.php`
   - Descrição: Exibe a lista de todos os clientes cadastrados
   - Funcionalidades:
     - Filtrar por nome ou status
     - Ordenar por diferentes campos
     - Acessar detalhes de cada cliente

2. **Adicionar/Editar Cliente**
   - Ator: Usuário
   - Arquivos: `cliente_adicionar.php`, `cliente_editar.php`
   - Campos:
     - Nome do Cliente
     - Email
     - Telefone
     - Instagram
     - Status (Ativo/Inativo)

3. **Salvar Cliente**
   - Ator: Sistema
   - Processamento:
     - Validação dos dados
     - Inserção/atualização no banco de dados
     - Registro no histórico

4. **Visualizar Histórico do Cliente**
   - Ator: Usuário
   - Descrição: Exibe o histórico de postagens do cliente
   - Funcionalidades:
     - Filtrar por período
     - Visualizar detalhes de cada postagem

5. **Excluir Cliente**
   - Ator: Usuário (apenas Administrador)
   - Processamento:
     - Confirmação da exclusão
     - Exclusão em cascata das postagens relacionadas
     - Registro no histórico

### 5.3.3 Pontos de Decisão

- **Permissão**: Apenas administradores podem excluir clientes.
- **Validação**: Se o nome do cliente já existir, exibir mensagem de erro.
- **Exclusão**: Confirmar antes de excluir, alertando sobre a exclusão em cascata das postagens.

## 5.4 Fluxo de Gerenciamento de Usuários

Este fluxo permite aos administradores gerenciar os usuários do sistema.

### 5.4.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────┐
│ Listar  ├────►│ Adicionar/ ├────►│ Salvar │
│ Usuários│     │ Editar     │     │        │
└─────────┘     └────────────┘     └────────┘
     │
     │
     │               ┌────────┐
     └──────────────►│ Excluir│
                     └────────┘
```

### 5.4.2 Etapas Detalhadas

1. **Listar Usuários**
   - Ator: Administrador
   - Arquivo: `usuarios.php`
   - Descrição: Exibe a lista de todos os usuários cadastrados
   - Funcionalidades:
     - Filtrar por nome ou tipo
     - Ordenar por diferentes campos
     - Acessar detalhes de cada usuário

2. **Adicionar/Editar Usuário**
   - Ator: Administrador
   - Arquivos: `usuario_adicionar.php`, `usuario_editar.php`
   - Campos:
     - Nome
     - Nome de Usuário
     - Email
     - Senha
     - Tipo (Administrador/Editor)
     - Status (Ativo/Inativo)

3. **Salvar Usuário**
   - Ator: Sistema
   - Processamento:
     - Validação dos dados
     - Criptografia da senha
     - Inserção/atualização no banco de dados
     - Registro no histórico

4. **Excluir Usuário**
   - Ator: Administrador
   - Processamento:
     - Confirmação da exclusão
     - Atualização das referências em outras tabelas
     - Registro no histórico

### 5.4.3 Pontos de Decisão

- **Permissão**: Apenas administradores podem gerenciar usuários.
- **Validação**: Se o nome de usuário ou email já existirem, exibir mensagem de erro.
- **Exclusão**: Não permitir que um administrador exclua a si mesmo.
- **Último Administrador**: Não permitir a alteração de tipo ou exclusão do último administrador do sistema.

## 5.5 Fluxo de Postagens Agendadas

Este fluxo permite aos usuários visualizar e gerenciar as postagens já agendadas.

### 5.5.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────┐     ┌──────────┐
│ Listar  ├────►│ Visualizar ├────►│ Editar ├────►│ Atualizar│
│ Postagens│    │ Detalhes   │     │        │     │          │
└─────────┘     └────────────┘     └────────┘     └──────────┘
     │                │
     │                │
     │                ▼
     │           ┌────────┐
     └──────────►│ Excluir│
                 └────────┘
```

### 5.5.2 Etapas Detalhadas

1. **Listar Postagens**
   - Ator: Usuário
   - Arquivo: `postagens_agendadas.php`
   - Descrição: Exibe a lista de todas as postagens agendadas
   - Funcionalidades:
     - Filtrar por cliente, período ou status
     - Ordenar por diferentes campos
     - Acessar detalhes de cada postagem

2. **Visualizar Detalhes**
   - Ator: Usuário
   - Descrição: Exibe os detalhes completos de uma postagem
   - Informações:
     - Cliente
     - Tipo e formato
     - Data e hora
     - Legenda
     - Arquivos (com pré-visualização)
     - Status do webhook

3. **Editar Postagem**
   - Ator: Usuário
   - Arquivo: `editar_postagem.php`
   - Campos Editáveis:
     - Tipo de Postagem
     - Formato
     - Data e Hora
     - Legenda
     - Arquivos

4. **Atualizar Postagem**
   - Ator: Sistema
   - Processamento:
     - Validação dos dados
     - Atualização no banco de dados
     - Reenvio de webhook
     - Registro no histórico

5. **Excluir Postagem**
   - Ator: Usuário
   - Processamento:
     - Confirmação da exclusão
     - Remoção do banco de dados
     - Registro no histórico

### 5.5.3 Pontos de Decisão

- **Edição**: Não permitir a edição de postagens já publicadas.
- **Reenvio de Webhook**: Se a data ou arquivos forem alterados, reenviar o webhook.
- **Exclusão**: Confirmar antes de excluir uma postagem.

## 5.6 Fluxo de Configurações do Sistema

Este fluxo permite aos administradores configurar diversos aspectos do sistema.

### 5.6.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────┐
│ Acessar ├────►│ Editar     ├────►│ Salvar │
│ Config. │     │ Configurações    │        │
└─────────┘     └────────────┘     └────────┘
```

### 5.6.2 Etapas Detalhadas

1. **Acessar Configurações**
   - Ator: Administrador
   - Arquivo: `configuracoes.php`
   - Descrição: Exibe as configurações atuais do sistema

2. **Editar Configurações**
   - Ator: Administrador
   - Seções:
     - Informações Gerais (nome do sistema, empresa, logo)
     - Configurações de Upload (tamanhos, tipos permitidos)
     - Configurações de Webhook (URL, chave de API)
     - Configurações de Sessão (tempo limite, múltiplos logins)
     - Configurações de Backup (periodicidade)

3. **Salvar Configurações**
   - Ator: Sistema
   - Processamento:
     - Validação dos dados
     - Atualização no banco de dados
     - Registro no histórico

### 5.6.3 Pontos de Decisão

- **Permissão**: Apenas administradores podem acessar e editar configurações.
- **Validação**: Verificar se os valores estão dentro dos limites aceitáveis.
- **Impacto**: Alertar sobre possíveis impactos de alterações em configurações críticas.

## 5.7 Fluxo de Backup do Banco de Dados

Este fluxo permite realizar e gerenciar backups do banco de dados.

### 5.7.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────┐
│ Iniciar ├────►│ Gerar      ├────►│ Download│
│ Backup  │     │ Arquivo SQL│     │         │
└─────────┘     └────────────┘     └────────┘
```

### 5.7.2 Etapas Detalhadas

1. **Iniciar Backup**
   - Ator: Administrador
   - Arquivo: `admin/backup_database.php`
   - Descrição: Inicia o processo de backup do banco de dados

2. **Gerar Arquivo SQL**
   - Ator: Sistema
   - Processamento:
     - Conexão com o banco de dados
     - Exportação da estrutura e dados
     - Compactação do arquivo
     - Armazenamento temporário

3. **Download do Backup**
   - Ator: Administrador
   - Descrição: Realiza o download do arquivo de backup gerado

### 5.7.3 Pontos de Decisão

- **Permissão**: Apenas administradores podem realizar backups.
- **Tamanho do Banco**: Para bancos muito grandes, alertar sobre possível demora.
- **Armazenamento**: Verificar espaço disponível antes de iniciar o backup.

## 5.8 Fluxo de Agendamento Recorrente

Este fluxo permite configurar postagens que se repetem automaticamente com uma frequência definida.

### 5.8.1 Diagrama do Fluxo

```
┌─────────┐     ┌────────────┐     ┌────────────┐     ┌────────────┐
│ Configurar ├─►│ Definir    ├────►│ Salvar     ├────►│ Geração    │
│ Recorrência │ │ Frequência │     │ Agendamento│     │ Automática │
└─────────┘     └────────────┘     └────────────┘     └────────────┘
```

### 5.8.2 Etapas Detalhadas

1. **Configurar Recorrência**
   - Ator: Usuário
   - Arquivo: `index.php` (com opção de recorrência)
   - Descrição: Ativa a opção de agendamento recorrente no formulário

2. **Definir Frequência**
   - Ator: Usuário
   - Opções:
     - Diária
     - Semanal (com seleção de dia da semana)
     - Mensal (com seleção de dia do mês)
   - Campos Adicionais:
     - Hora de publicação
     - Data de início
     - Data de término (opcional)

3. **Salvar Agendamento Recorrente**
   - Ator: Sistema
   - Processamento:
     - Validação dos dados
     - Inserção na tabela `agendamentos_recorrentes`
     - Cálculo da próxima execução
     - Registro no histórico

4. **Geração Automática de Postagens**
   - Ator: Sistema (via cron job)
   - Arquivo: `admin/process_recurrent_posts.php`
   - Processamento:
     - Verificação de agendamentos pendentes
     - Criação de novas postagens
     - Envio de webhooks
     - Atualização da data da próxima execução

### 5.8.3 Pontos de Decisão

- **Validação**: Verificar se a frequência e horários são válidos.
- **Conflitos**: Verificar se já existem postagens agendadas para os mesmos horários.
- **Limite**: Definir um limite máximo de recorrências para evitar sobrecarga.

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
