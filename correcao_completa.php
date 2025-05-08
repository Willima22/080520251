<?php
/**
 * Script de correção completa para o sistema de agendamento de postagens
 * Este script corrige todos os problemas identificados no sistema
 */

// Habilitar exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Incluir arquivos de configuração
require_once 'config/config.php';
require_once 'config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Função para executar consultas SQL com segurança
function executarSQL($conn, $sql, $descricao) {
    try {
        $stmt = $conn->prepare($sql);
        $resultado = $stmt->execute();
        echo "<p style='color:green'>✓ $descricao: Sucesso</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ $descricao: Erro - " . $e->getMessage() . "</p>";
        return false;
    }
}

// Função para corrigir arquivo PHP
function corrigirArquivo($caminho, $buscar, $substituir) {
    if (file_exists($caminho)) {
        $conteudo = file_get_contents($caminho);
        $conteudo_novo = str_replace($buscar, $substituir, $conteudo);
        
        if ($conteudo !== $conteudo_novo) {
            if (file_put_contents($caminho, $conteudo_novo)) {
                echo "<p style='color:green'>✓ Arquivo $caminho corrigido com sucesso</p>";
                return true;
            } else {
                echo "<p style='color:red'>✗ Erro ao escrever no arquivo $caminho</p>";
                return false;
            }
        } else {
            echo "<p style='color:blue'>ℹ Nenhuma alteração necessária no arquivo $caminho</p>";
            return true;
        }
    } else {
        echo "<p style='color:red'>✗ Arquivo $caminho não encontrado</p>";
        return false;
    }
}

// Iniciar HTML
echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Correção Completa do Sistema</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
        }
        h1, h2, h3 {
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .info {
            color: blue;
        }
        pre {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Correção Completa do Sistema</h1>";

// Verificar se o usuário confirmou a correção
if (!isset($_GET['confirmar'])) {
    echo "
        <p>Este script irá corrigir todos os problemas identificados no sistema.</p>
        <p><strong>Atenção:</strong> Recomendamos fazer um backup do banco de dados e dos arquivos antes de prosseguir.</p>
        <p>As seguintes correções serão aplicadas:</p>
        <ol>
            <li>Correção da coluna 'nome' para 'nome_cliente' em todos os arquivos</li>
            <li>Correção da coluna 'status' para 'webhook_status' em dashboard.php e postagens_agendadas.php</li>
            <li>Correção da coluna 'created_at' para 'data_criacao' em relatorios.php</li>
            <li>Correção da variável \$pdo para \$conn em perfil.php</li>
            <li>Correção de referências a 'nome' em clientes_visualizar.php</li>
            <li>Verificação da tabela 'historico' para resolver problema de chave estrangeira</li>
        </ol>
        <a href='?confirmar=1' class='btn'>Iniciar Correção</a>
    ";
} else {
    // Executar correções
    echo "<h2>Executando correções...</h2>";
    
    // 1. Corrigir problema de nome_cliente em todos os arquivos
    echo "<h3>1. Corrigindo referências a 'nome' para 'nome_cliente'</h3>";
    
    // Correções para index.php
    corrigirArquivo('index.php', 
        'SELECT id, nome FROM clientes ORDER BY nome ASC', 
        'SELECT id, nome_cliente as nome FROM clientes ORDER BY nome_cliente ASC');
    
    // Correções para clientes_visualizar.php
    corrigirArquivo('clientes_visualizar.php', 
        'SELECT * FROM clientes ORDER BY nome ASC', 
        'SELECT * FROM clientes ORDER BY nome_cliente ASC');
    
    corrigirArquivo('clientes_visualizar.php', 
        'htmlspecialchars($client[\'nome\'])', 
        'htmlspecialchars($client[\'nome_cliente\'])');
    
    // Correções para confirmar_postagem.php
    corrigirArquivo('confirmar_postagem.php', 
        'htmlspecialchars($client[\'nome\'])', 
        'htmlspecialchars($client[\'nome_cliente\'])');
    
    // Correções para dashboard.php
    corrigirArquivo('dashboard.php', 
        'c.nome as cliente_nome', 
        'c.nome_cliente as cliente_nome');
    
    // Correções para postagens_agendadas.php
    corrigirArquivo('postagens_agendadas.php', 
        'c.nome as cliente_nome', 
        'c.nome_cliente as cliente_nome');
    
    // Correções para visualizar_postagem.php
    corrigirArquivo('visualizar_postagem.php', 
        'c.nome as cliente_nome', 
        'c.nome_cliente as cliente_nome');
    
    // 2. Corrigir problema de status para webhook_status
    echo "<h3>2. Corrigindo referências a 'status' para 'webhook_status'</h3>";
    
    // Correções para dashboard.php
    corrigirArquivo('dashboard.php', 
        'SELECT status, COUNT(*) as total FROM postagens GROUP BY status', 
        'SELECT webhook_status, COUNT(*) as total FROM postagens GROUP BY webhook_status');
    
    corrigirArquivo('dashboard.php', 
        'switch ($status[\'status\']) {', 
        'switch ($status[\'webhook_status\']) {');
    
    // Correções para postagens_agendadas.php
    corrigirArquivo('postagens_agendadas.php', 
        'p.status', 
        'p.webhook_status as status');
    
    corrigirArquivo('postagens_agendadas.php', 
        'SET status = \'Cancelado\'', 
        'SET webhook_status = \'Cancelado\'');
    
    corrigirArquivo('postagens_agendadas.php', 
        'data-status="<?= $postagem[\'status\'] ?>"', 
        'data-status="<?= $postagem[\'status\'] ?>"');
    
    corrigirArquivo('postagens_agendadas.php', 
        'if ($postagem[\'status\'] == \'Agendado\')', 
        'if ($postagem[\'status\'] == \'Agendado\')');
    
    corrigirArquivo('postagens_agendadas.php', 
        'elseif ($postagem[\'status\'] == \'Publicado\')', 
        'elseif ($postagem[\'status\'] == \'Publicado\')');
    
    corrigirArquivo('postagens_agendadas.php', 
        'elseif ($postagem[\'status\'] == \'Cancelado\')', 
        'elseif ($postagem[\'status\'] == \'Cancelado\')');
    
    corrigirArquivo('postagens_agendadas.php', 
        'elseif ($postagem[\'status\'] == \'Falha\')', 
        'elseif ($postagem[\'status\'] == \'Falha\')');
    
    // 3. Corrigir problema de created_at para data_criacao
    echo "<h3>3. Corrigindo referências a 'created_at' para 'data_criacao'</h3>";
    
    // Correções para relatorios.php
    corrigirArquivo('relatorios.php', 
        'WHERE DATE(created_at) BETWEEN :start_date AND :end_date', 
        'WHERE DATE(data_criacao) BETWEEN :start_date AND :end_date');
    
    corrigirArquivo('relatorios.php', 
        'WHERE DATE(created_at) = :date', 
        'WHERE DATE(data_criacao) = :date');
    
    corrigirArquivo('relatorios.php', 
        'GROUP BY DATE(created_at)', 
        'GROUP BY DATE(data_criacao)');
    
    corrigirArquivo('relatorios.php', 
        'ORDER BY DATE(created_at)', 
        'ORDER BY DATE(data_criacao)');
    
    // Correções para dashboard.php
    corrigirArquivo('dashboard.php', 
        'WHERE DATE(created_at) = :date', 
        'WHERE DATE(data_criacao) = :date');
    
    corrigirArquivo('dashboard.php', 
        'ORDER BY p.created_at DESC', 
        'ORDER BY p.data_criacao DESC');
    
    // Correções para postagens_agendadas.php
    corrigirArquivo('postagens_agendadas.php', 
        'date(\'d/m/Y H:i\', strtotime($postagem[\'created_at\']))', 
        'date(\'d/m/Y H:i\', strtotime($postagem[\'data_criacao\']))');
    
    // 4. Corrigir variável $pdo para $conn
    echo "<h3>4. Corrigindo variável \$pdo para \$conn</h3>";
    
    // Correções para perfil.php
    corrigirArquivo('perfil.php', 
        '$pdo->prepare', 
        '$conn->prepare');
    
    corrigirArquivo('perfil.php', 
        'require_once \'config/config.php\';
require_once \'includes/header.php\';', 
        'require_once \'config/config.php\';
require_once \'config/db.php\';
require_once \'includes/header.php\';

// Get database connection
$database = new Database();
$conn = $database->connect();');
    
    // 5. Verificar tabela historico
    echo "<h3>5. Verificando tabela 'historico'</h3>";
    
    try {
        $query = "SHOW TABLES LIKE 'historico'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll();
        
        if (count($result) > 0) {
            echo "<p style='color:green'>✓ Tabela 'historico' existe</p>";
            
            // Verificar estrutura da tabela
            $query = "DESCRIBE historico";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p>Estrutura da tabela 'historico':</p>";
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
            
            // Verificar restrição de chave estrangeira
            $query = "SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                      WHERE REFERENCED_TABLE_NAME = 'usuarios' 
                      AND TABLE_NAME = 'historico'";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($constraints) > 0) {
                echo "<p style='color:blue'>ℹ Restrição de chave estrangeira encontrada entre 'historico' e 'usuarios'</p>";
                echo "<p>Para resolver o problema de exclusão de usuários, você tem duas opções:</p>";
                echo "<ol>";
                echo "<li>Adicionar ON DELETE CASCADE à chave estrangeira (isso excluirá automaticamente registros relacionados)</li>";
                echo "<li>Excluir manualmente os registros relacionados antes de excluir o usuário</li>";
                echo "</ol>";
                
                echo "<p>Código SQL para adicionar ON DELETE CASCADE:</p>";
                echo "<pre>";
                echo "ALTER TABLE historico DROP FOREIGN KEY historico_ibfk_1;\n";
                echo "ALTER TABLE historico ADD CONSTRAINT historico_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;\n";
                echo "</pre>";
                
                echo "<p>Deseja adicionar ON DELETE CASCADE à chave estrangeira?</p>";
                echo "<a href='?confirmar=1&fix_fk=1' class='btn'>Sim, adicionar ON DELETE CASCADE</a> ";
                echo "<a href='?confirmar=1' class='btn' style='background:#f44336'>Não, deixar como está</a>";
                
                // Se o usuário confirmou a correção da chave estrangeira
                if (isset($_GET['fix_fk']) && $_GET['fix_fk'] == 1) {
                    try {
                        // Adicionar ON DELETE CASCADE à chave estrangeira
                        $conn->exec("ALTER TABLE historico DROP FOREIGN KEY historico_ibfk_1");
                        $conn->exec("ALTER TABLE historico ADD CONSTRAINT historico_ibfk_1 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE");
                        echo "<p style='color:green'>✓ ON DELETE CASCADE adicionado com sucesso à chave estrangeira</p>";
                    } catch (PDOException $e) {
                        echo "<p style='color:red'>✗ Erro ao modificar a chave estrangeira: " . $e->getMessage() . "</p>";
                    }
                }
            } else {
                echo "<p style='color:blue'>ℹ Nenhuma restrição de chave estrangeira encontrada entre 'historico' e 'usuarios'</p>";
            }
        } else {
            echo "<p style='color:blue'>ℹ Tabela 'historico' não existe</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>✗ Erro ao verificar a tabela 'historico': " . $e->getMessage() . "</p>";
    }
    
    // Conclusão
    echo "<h2>Correção concluída</h2>";
    echo "<p>As correções foram aplicadas. Agora você pode acessar o sistema novamente.</p>";
    echo "<a href='index.php' class='btn'>Acessar o Sistema</a>";
}

// Finalizar HTML
echo "
    </div>
</body>
</html>";
?>
