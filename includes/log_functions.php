<?php
/**
 * Funções para registro de logs/histórico no sistema
 */

/**
 * Registra uma ação no histórico do sistema
 * 
 * @param string $acao Descrição da ação realizada
 * @param string $detalhes Detalhes adicionais (opcional)
 * @param string $modulo Nome do módulo onde a ação foi realizada (opcional)
 * @return bool Retorna true se o registro foi criado com sucesso, false caso contrário
 */
if (!function_exists('registrarLog')) {
    function registrarLog($acao, $detalhes = null, $modulo = null) {
        try {
            // Obter conexão com o banco de dados
            $database = new Database();
            $conn = $database->connect();
            
            // Obter dados do usuário
            $usuario_id = $_SESSION['user_id'] ?? null;
            $usuario_nome = $_SESSION['user_name'] ?? 'Sistema';
            
            // Obter IP do usuário
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            
            // Data e hora atual
            $data_hora = date('Y-m-d H:i:s');
            
            // Inserir registro no histórico
            $sql = "INSERT INTO historico (usuario_id, usuario_nome, acao, detalhes, modulo, ip, data_hora) 
                    VALUES (:usuario_id, :usuario_nome, :acao, :detalhes, :modulo, :ip, :data_hora)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':usuario_id', $usuario_id);
            $stmt->bindParam(':usuario_nome', $usuario_nome);
            $stmt->bindParam(':acao', $acao);
            $stmt->bindParam(':detalhes', $detalhes);
            $stmt->bindParam(':modulo', $modulo);
            $stmt->bindParam(':ip', $ip);
            $stmt->bindParam(':data_hora', $data_hora);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            // Em caso de erro, apenas retorna false
            // Não exibe o erro para não interromper o fluxo da aplicação
            return false;
        }
    }
}

/**
 * Registra uma ação de sessão no histórico
 * 
 * @param string $acao Descrição da ação realizada (login, logout, etc)
 * @param string $detalhes Detalhes adicionais (opcional)
 * @param string $modulo Nome do módulo onde a ação foi realizada (opcional)
 * @return bool Retorna true se o registro foi criado com sucesso, false caso contrário
 */
if (!function_exists('registrarLogSessao')) {
    function registrarLogSessao($acao, $detalhes = null, $modulo = null) {
        // Obter ID do usuário da sessão
        $usuario_id = $_SESSION['user_id'] ?? null;
        $usuario_nome = $_SESSION['user_name'] ?? 'Sistema';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        return registrarLog($acao, $detalhes, $modulo);
    }
}
?>
