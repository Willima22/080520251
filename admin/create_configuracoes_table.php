<?php
/**
 * Script para criar a tabela de configurações do sistema
 */

// Incluir arquivos necessários
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isAdmin()) {
    die("Acesso negado. Você precisa ser um administrador para executar este script.");
}

// Criar conexão com o banco de dados
$database = new Database();
$conn = $database->connect();

try {
    // Verificar se a tabela já existe
    $stmt = $conn->query("SHOW TABLES LIKE 'configuracoes'");
    if ($stmt->rowCount() > 0) {
        echo "<p>A tabela de configurações já existe.</p>";
    } else {
        // Criar a tabela de configurações
        $sql = "CREATE TABLE configuracoes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            chave VARCHAR(50) NOT NULL,
            valor TEXT,
            descricao VARCHAR(255),
            tipo VARCHAR(20) DEFAULT 'texto',
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            usuario_id INT(11),
            PRIMARY KEY (id),
            UNIQUE KEY (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($sql);
        
        // Inserir configurações padrão
        $configuracoes_padrao = [
            ['tempo_limite_sessao', '5', 'Tempo limite da sessão em minutos', 'numero'],
            ['permitir_multiplos_logins', '0', 'Permitir múltiplos logins simultâneos', 'boolean'],
            ['nome_sistema', 'AW7 Postagens', 'Nome do sistema', 'texto'],
            ['nome_empresa', 'AW7 Comunicação e Marketing', 'Nome da empresa', 'texto'],
            ['logo_path', 'assets/img/logo.png', 'Caminho para o logo do sistema', 'texto'],
            ['tipo_padrao', 'editor', 'Tipo padrão de usuário', 'texto'],
            ['alterar_senha_primeiro_login', '1', 'Alterar senha no primeiro login', 'boolean'],
            ['autenticacao_dois_fatores', '0', 'Autenticação de dois fatores', 'boolean'],
            ['periodicidade_backup', 'semanal', 'Periodicidade de backup', 'texto']
        ];
        
        $sql_insert = "INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        
        foreach ($configuracoes_padrao as $config) {
            $stmt->execute($config);
        }
        
        echo "<p>Tabela de configurações criada com sucesso e configurações padrão inseridas!</p>";
    }
    
    echo "<p><a href='../configuracoes.php' class='btn btn-primary'>Voltar para Configurações</a></p>";
} catch (PDOException $e) {
    echo "<p>Erro ao criar tabela de configurações: " . $e->getMessage() . "</p>";
}
?>
