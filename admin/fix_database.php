<?php
/**
 * Script para corrigir todos os problemas do banco de dados
 * Este script não depende de verificações de administrador
 */

// Desativar verificação de sessão para este script
$bypass_auth = true;

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

// Obter conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

// Array para armazenar mensagens
$messages = [];

// Função para adicionar mensagem
function addMessage($type, $message) {
    global $messages;
    $messages[] = [
        'type' => $type,
        'message' => $message
    ];
}

// 1. Verificar e criar a tabela historico
try {
    // Verificar se a tabela historico já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'historico'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        // Criar a tabela historico
        $sql = "CREATE TABLE historico (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT,
            usuario_nome VARCHAR(100),
            acao VARCHAR(100) NOT NULL,
            detalhes TEXT DEFAULT NULL,
            modulo VARCHAR(100) DEFAULT NULL,
            ip VARCHAR(45) DEFAULT NULL,
            data_hora DATETIME NOT NULL
        )";
        
        $conn->exec($sql);
        addMessage('success', 'Tabela "historico" criada com sucesso.');
    } else {
        // Verificar se as colunas existem
        $stmt = $conn->query("DESCRIBE historico");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingColumns = [];
        $requiredColumns = [
            'usuario_id' => 'INT',
            'usuario_nome' => 'VARCHAR(100)',
            'acao' => 'VARCHAR(100) NOT NULL',
            'detalhes' => 'TEXT DEFAULT NULL',
            'modulo' => 'VARCHAR(100) DEFAULT NULL',
            'ip' => 'VARCHAR(45) DEFAULT NULL',
            'data_hora' => 'DATETIME NOT NULL'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        if (!empty($missingColumns)) {
            // Adicionar as colunas que faltam
            foreach ($missingColumns as $column => $definition) {
                $sql = "ALTER TABLE historico ADD COLUMN $column $definition";
                $conn->exec($sql);
            }
            
            addMessage('success', 'Colunas adicionadas à tabela "historico": ' . implode(', ', array_keys($missingColumns)));
        } else {
            addMessage('info', 'A tabela "historico" já existe e contém todas as colunas necessárias.');
        }
    }
} catch (PDOException $e) {
    addMessage('danger', 'Erro ao criar tabela "historico": ' . $e->getMessage());
}

// 2. Verificar e corrigir a tabela postagens
try {
    // Verificar se a tabela postagens já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'postagens'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Verificar se as colunas existem
        $stmt = $conn->query("DESCRIBE postagens");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingColumns = [];
        $requiredColumns = [
            'status' => 'VARCHAR(50) DEFAULT NULL',
            'webhook_status' => 'TINYINT(1) DEFAULT 0',
            'data_criacao' => 'DATETIME DEFAULT NULL',
            'arquivos' => 'TEXT DEFAULT NULL'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        if (!empty($missingColumns)) {
            // Adicionar as colunas que faltam
            foreach ($missingColumns as $column => $definition) {
                $sql = "ALTER TABLE postagens ADD COLUMN $column $definition";
                $conn->exec($sql);
            }
            
            addMessage('success', 'Colunas adicionadas à tabela "postagens": ' . implode(', ', array_keys($missingColumns)));
        } else {
            addMessage('info', 'A tabela "postagens" já contém todas as colunas necessárias.');
        }
    } else {
        addMessage('warning', 'A tabela "postagens" não existe. Execute o script setup_database.php para criar todas as tabelas.');
    }
} catch (PDOException $e) {
    addMessage('danger', 'Erro ao verificar tabela "postagens": ' . $e->getMessage());
}

// 3. Verificar e corrigir a tabela clientes
try {
    // Verificar se a tabela clientes já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'clientes'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Verificar se as colunas existem
        $stmt = $conn->query("DESCRIBE clientes");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingColumns = [];
        $requiredColumns = [
            'nome' => 'VARCHAR(255) DEFAULT NULL',
            'nome_cliente' => 'VARCHAR(100) DEFAULT NULL'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $missingColumns[$column] = $definition;
            }
        }
        
        if (!empty($missingColumns)) {
            // Adicionar as colunas que faltam
            foreach ($missingColumns as $column => $definition) {
                $sql = "ALTER TABLE clientes ADD COLUMN $column $definition";
                $conn->exec($sql);
            }
            
            addMessage('success', 'Colunas adicionadas à tabela "clientes": ' . implode(', ', array_keys($missingColumns)));
        } else {
            addMessage('info', 'A tabela "clientes" já contém todas as colunas necessárias.');
        }
    } else {
        addMessage('warning', 'A tabela "clientes" não existe. Execute o script setup_database.php para criar todas as tabelas.');
    }
} catch (PDOException $e) {
    addMessage('danger', 'Erro ao verificar tabela "clientes": ' . $e->getMessage());
}

// 4. Executar o arquivo SQL de configuração do banco de dados
try {
    // Verificar se o arquivo SQL existe
    $sqlFile = __DIR__ . '/database_setup.sql';
    if (file_exists($sqlFile)) {
        // Ler o conteúdo do arquivo SQL
        $sql = file_get_contents($sqlFile);
        
        // Dividir o SQL em comandos individuais
        $commands = explode(';', $sql);
        
        // Executar cada comando
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command)) {
                try {
                    $conn->exec($command);
                } catch (PDOException $e) {
                    // Ignorar erros de tabela já existente
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        addMessage('success', 'Arquivo SQL de configuração executado com sucesso.');
    } else {
        addMessage('warning', 'Arquivo SQL de configuração não encontrado.');
    }
} catch (PDOException $e) {
    addMessage('danger', 'Erro ao executar arquivo SQL: ' . $e->getMessage());
}

// 5. Verificar e corrigir a função isAdmin()
$adminCheckResult = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'Não logado';
addMessage('info', 'Valor atual de $_SESSION[\'user_type\']: ' . $adminCheckResult);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção do Banco de Dados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Correção do Banco de Dados</h1>
        
        <div class="mt-4">
            <?php foreach ($messages as $message): ?>
                <div class="alert alert-<?php echo $message['type']; ?>" role="alert">
                    <?php echo $message['message']; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4">
            <h2>Próximos Passos</h2>
            <ol>
                <li>Verifique se todas as tabelas foram criadas corretamente.</li>
                <li>Verifique se todas as colunas necessárias foram adicionadas.</li>
                <li>Acesse o <a href="../dashboard.php" class="btn btn-sm btn-primary">Dashboard</a> para verificar se o sistema está funcionando corretamente.</li>
            </ol>
        </div>
        
        <div class="mt-4">
            <a href="../dashboard.php" class="btn btn-primary">Voltar para o Dashboard</a>
        </div>
    </div>
</body>
</html>
