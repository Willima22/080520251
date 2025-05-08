# 8. Manutenção e Suporte

## 8.1 Visão Geral de Manutenção

O Sistema de Agendamento de Postagens AW7 requer manutenção regular para garantir seu funcionamento adequado, segurança e desempenho. Esta seção documenta os procedimentos de manutenção, resolução de problemas comuns, e informações de suporte para administradores e usuários do sistema.

## 8.2 Manutenção Regular

### 8.2.1 Backup do Banco de Dados

O backup regular do banco de dados é essencial para prevenir perda de dados:

1. **Backup Manual**
   - Acesso: Dashboard > Backup do Banco de Dados (apenas administradores)
   - Arquivo: `admin/backup_database.php`
   - Processo: Exportação da estrutura e dados em formato SQL
   - Resultado: Download de arquivo .sql compactado

2. **Backup Automático**
   - Configuração: Página de Configurações > Seção Backup
   - Opções: Diário, Semanal, Mensal
   - Implementação: Tarefa cron executando `admin/backup_database.php`
   - Armazenamento: Diretório configurável ou envio por email

3. **Recomendações**
   - Frequência: No mínimo semanal
   - Armazenamento: Manter em local seguro, separado do servidor
   - Retenção: Manter pelo menos os últimos 3 backups
   - Teste: Verificar periodicamente a integridade dos backups

### 8.2.2 Limpeza de Arquivos Temporários

O sistema gera arquivos temporários que devem ser limpos periodicamente:

1. **Diretório `uploads/`**
   - Conteúdo: Arquivos temporários de upload
   - Limpeza: Arquivos com mais de 24 horas podem ser removidos
   - Comando: `find /caminho/para/uploads -type f -mtime +1 -delete`

2. **Arquivos de Log**
   - Localização: Logs do servidor web e PHP
   - Rotação: Configurar rotação de logs no servidor
   - Retenção: Manter logs por pelo menos 30 dias

### 8.2.3 Verificação de Integridade

Verificações periódicas para garantir a integridade do sistema:

1. **Verificação de Tabelas**
   - Ferramenta: phpMyAdmin ou linha de comando MySQL
   - Comando: `CHECK TABLE nome_tabela`
   - Frequência: Mensal

2. **Verificação de Arquivos**
   - Verificar permissões de arquivos e diretórios
   - Verificar integridade de arquivos críticos do sistema
   - Comando: `find /caminho/para/sistema -type f -name "*.php" -exec md5sum {} \; > checksums.txt`

3. **Monitoramento de Espaço em Disco**
   - Verificar regularmente o espaço disponível
   - Alerta quando o uso ultrapassar 80%
   - Comando: `df -h`

## 8.3 Atualizações do Sistema

### 8.3.1 Procedimento de Atualização

Ao atualizar o sistema para uma nova versão, siga estes passos:

1. **Preparação**
   - Realizar backup completo do banco de dados
   - Realizar backup completo dos arquivos do sistema
   - Notificar usuários sobre o período de manutenção

2. **Atualização**
   - Substituir arquivos do sistema pelos novos
   - Manter arquivos de configuração personalizados
   - Executar scripts de atualização do banco de dados

3. **Verificação**
   - Verificar logs de erro
   - Testar funcionalidades principais
   - Verificar compatibilidade com plugins/integrações

4. **Finalização**
   - Limpar cache do navegador e do servidor
   - Notificar usuários sobre a conclusão da atualização
   - Documentar alterações realizadas

### 8.3.2 Scripts de Atualização

O sistema inclui scripts para facilitar atualizações:

1. **`admin/update_database.php`**
   - Verifica a versão atual do banco de dados
   - Aplica migrações necessárias
   - Registra alterações no log

2. **`admin/check_system.php`**
   - Verifica requisitos do sistema
   - Identifica problemas de configuração
   - Sugere correções

## 8.4 Resolução de Problemas Comuns

### 8.4.1 Problemas de Login

**Sintoma: Usuário não consegue fazer login**

Possíveis causas e soluções:

1. **Credenciais Incorretas**
   - Verificar se o usuário está usando o nome de usuário e senha corretos
   - Verificar se o Caps Lock está ativado
   - Solução: Redefinir a senha do usuário

2. **Conta Desativada**
   - Verificar o status do usuário na tabela `usuarios`
   - Solução: Ativar a conta definindo `ativo = 1`

3. **Problemas de Sessão**
   - Verificar configurações de sessão do PHP
   - Verificar permissões do diretório de sessões
   - Solução: Limpar cookies e sessões do navegador

4. **Banco de Dados Inacessível**
   - Verificar conexão com o banco de dados
   - Verificar logs de erro do PHP
   - Solução: Corrigir configurações de conexão em `config/db.php`

### 8.4.2 Problemas de Upload de Arquivos

**Sintoma: Falha ao fazer upload de arquivos**

Possíveis causas e soluções:

1. **Permissões de Diretório**
   - Verificar se os diretórios `uploads/` e `arquivos/` têm permissões de escrita
   - Solução: Definir permissões 755 para diretórios e 644 para arquivos

2. **Limites de PHP**
   - Verificar configurações `upload_max_filesize` e `post_max_size` no php.ini
   - Solução: Aumentar limites conforme necessário

3. **Diretórios Inexistentes**
   - Verificar se os diretórios de destino existem
   - Solução: Criar diretórios com permissões adequadas
   - Ferramenta: `admin/fix_upload_system.php`

4. **Tipo de Arquivo Não Permitido**
   - Verificar se o tipo de arquivo está na lista de permitidos
   - Solução: Adicionar o tipo à lista ou converter o arquivo para um formato suportado

### 8.4.3 Problemas de Webhook

**Sintoma: Webhooks não estão sendo enviados**

Possíveis causas e soluções:

1. **URL Incorreta**
   - Verificar se a URL do webhook está correta
   - Verificar se a URL é acessível a partir do servidor
   - Solução: Corrigir a URL nas configurações

2. **Problemas de Rede**
   - Verificar conectividade do servidor
   - Verificar se há firewalls bloqueando conexões de saída
   - Solução: Configurar regras de firewall adequadas

3. **Timeout**
   - Verificar se o endpoint do webhook está respondendo em tempo hábil
   - Solução: Aumentar o timeout da requisição ou otimizar o endpoint

4. **Erros de Resposta**
   - Verificar logs para códigos de erro HTTP
   - Solução: Corrigir problemas no endpoint ou ajustar o formato dos dados

### 8.4.4 Problemas de Desempenho

**Sintoma: Sistema lento ou instável**

Possíveis causas e soluções:

1. **Banco de Dados Sobrecarregado**
   - Verificar consultas lentas nos logs do MySQL
   - Solução: Otimizar consultas, adicionar índices, limpar dados antigos

2. **Arquivos de Mídia Grandes**
   - Verificar tamanho dos arquivos no diretório `arquivos/`
   - Solução: Implementar compressão de imagens, limitar tamanho máximo

3. **Recursos do Servidor Insuficientes**
   - Verificar uso de CPU, memória e disco
   - Solução: Aumentar recursos do servidor ou otimizar código

4. **Cache Desativado**
   - Verificar configurações de cache
   - Solução: Implementar cache de consultas e páginas

## 8.5 Ferramentas de Diagnóstico

O sistema inclui ferramentas para diagnosticar e resolver problemas:

### 8.5.1 Scripts Administrativos

1. **`admin/check_system.php`**
   - Verifica requisitos do sistema
   - Testa conexão com o banco de dados
   - Verifica permissões de diretórios
   - Verifica configurações do PHP

2. **`admin/fix_database.php`**
   - Verifica a estrutura do banco de dados
   - Corrige problemas comuns
   - Cria tabelas ausentes

3. **`admin/fix_upload_system.php`**
   - Verifica diretórios de upload
   - Cria diretórios ausentes
   - Ajusta permissões

### 8.5.2 Logs do Sistema

1. **Logs de Aplicação**
   - Localização: Tabela `historico` no banco de dados
   - Interface: `logs.php` (acesso de administrador)
   - Informações: Ações de usuários, erros, eventos do sistema

2. **Logs do PHP**
   - Localização: Configurada em php.ini (`error_log`)
   - Conteúdo: Erros de PHP, avisos, notices
   - Acesso: Via sistema de arquivos do servidor

3. **Logs do Servidor Web**
   - Localização: Diretório de logs do Apache/Nginx
   - Conteúdo: Requisições, erros, acessos
   - Acesso: Via sistema de arquivos do servidor

## 8.6 Otimização de Desempenho

### 8.6.1 Otimização do Banco de Dados

1. **Índices**
   - Verificar e otimizar índices para consultas frequentes
   - Comando: `ANALYZE TABLE nome_tabela`
   - Frequência: Trimestral

2. **Limpeza de Dados**
   - Remover registros antigos e desnecessários
   - Tabelas críticas: `historico` (logs antigos)
   - Script: `admin/clean_old_logs.php`

3. **Otimização de Tabelas**
   - Desfragmentar tabelas para melhorar desempenho
   - Comando: `OPTIMIZE TABLE nome_tabela`
   - Frequência: Mensal

### 8.6.2 Otimização de Arquivos

1. **Compressão de Imagens**
   - Implementar compressão automática de imagens
   - Ferramentas: GD ou ImageMagick
   - Configuração: Qualidade e dimensões máximas

2. **Limpeza de Arquivos Temporários**
   - Remover arquivos de upload não utilizados
   - Script: `admin/clean_temp_files.php`
   - Frequência: Diária (via cron)

3. **Organização de Arquivos**
   - Manter estrutura de diretórios organizada
   - Limitar número de arquivos por diretório
   - Implementar subdivisão por data/mês

## 8.7 Suporte ao Usuário

### 8.7.1 Documentação para Usuários

O sistema inclui documentação para diferentes perfis de usuário:

1. **Manual do Administrador**
   - Instalação e configuração
   - Manutenção e backup
   - Gerenciamento de usuários
   - Resolução de problemas

2. **Manual do Editor**
   - Agendamento de postagens
   - Gerenciamento de clientes
   - Uso de recursos de mídia
   - Fluxos de trabalho

3. **Guias Rápidos**
   - Procedimentos comuns passo a passo
   - Dicas e melhores práticas
   - Respostas para perguntas frequentes

### 8.7.2 Treinamento

Recomendações para treinamento de usuários:

1. **Sessões Iniciais**
   - Visão geral do sistema
   - Funcionalidades principais
   - Fluxos de trabalho básicos

2. **Treinamento Avançado**
   - Recursos avançados
   - Otimização de fluxos de trabalho
   - Resolução de problemas comuns

3. **Atualizações**
   - Treinamento para novas funcionalidades
   - Revisão de procedimentos
   - Feedback dos usuários

### 8.7.3 Canais de Suporte

Canais recomendados para suporte ao usuário:

1. **Email de Suporte**
   - Para questões não urgentes
   - Documentação de problemas
   - Acompanhamento de casos

2. **Sistema de Tickets**
   - Rastreamento de problemas
   - Histórico de interações
   - Priorização de casos

3. **Suporte Telefônico**
   - Para questões urgentes
   - Orientação em tempo real
   - Resolução rápida de problemas críticos

## 8.8 Plano de Recuperação de Desastres

### 8.8.1 Cenários de Desastre

Preparação para diferentes cenários de falha:

1. **Falha de Hardware**
   - Servidor físico ou virtual inoperante
   - Plano: Restauração em hardware alternativo

2. **Corrupção de Dados**
   - Banco de dados ou arquivos corrompidos
   - Plano: Restauração a partir do último backup válido

3. **Comprometimento de Segurança**
   - Acesso não autorizado ou malware
   - Plano: Isolamento, limpeza e restauração

4. **Perda de Conectividade**
   - Problemas de rede ou provedor
   - Plano: Redundância de conexão ou servidor alternativo

### 8.8.2 Procedimento de Recuperação

Passos para recuperação em caso de desastre:

1. **Avaliação**
   - Identificar a natureza e extensão do problema
   - Determinar o impacto nos dados e serviços
   - Selecionar a estratégia de recuperação apropriada

2. **Contenção**
   - Prevenir danos adicionais
   - Isolar componentes afetados
   - Notificar usuários sobre a indisponibilidade

3. **Recuperação**
   - Restaurar banco de dados a partir do backup
   - Restaurar arquivos do sistema
   - Verificar integridade e consistência

4. **Verificação**
   - Testar funcionalidades críticas
   - Verificar integridade dos dados
   - Confirmar acesso e permissões

5. **Retorno à Operação**
   - Reativar serviços para usuários
   - Monitorar desempenho e estabilidade
   - Documentar o incidente e as ações tomadas

### 8.8.3 Testes de Recuperação

Recomendações para testes periódicos:

1. **Simulação de Recuperação**
   - Frequência: Trimestral
   - Processo: Restaurar backup em ambiente de teste
   - Verificação: Funcionalidade e integridade dos dados

2. **Documentação**
   - Manter procedimentos atualizados
   - Registrar resultados dos testes
   - Atualizar plano com base nas lições aprendidas

## 8.9 Contatos e Recursos

### 8.9.1 Contatos de Suporte

- **Suporte Técnico AW7**
  - Email: suporte@aw7.com.br
  - Telefone: (XX) XXXX-XXXX
  - Horário: Segunda a Sexta, 8h às 18h

- **Emergências**
  - Telefone: (XX) XXXX-XXXX
  - Disponibilidade: 24/7 para problemas críticos

### 8.9.2 Recursos Adicionais

- **Documentação Online**
  - URL: https://docs.aw7.com.br/sistema-postagens
  - Atualizações regulares e artigos de suporte

- **Fórum de Usuários**
  - URL: https://forum.aw7.com.br
  - Comunidade para troca de experiências e dicas

- **Base de Conhecimento**
  - Artigos sobre problemas comuns
  - Tutoriais e guias passo a passo
  - Perguntas frequentes (FAQ)

---

© 2025 AW7 Comunicação e Marketing. Todos os direitos reservados.
