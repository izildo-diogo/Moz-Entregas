<?php
/**
 * API para registrar atividades do usuário
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Método não permitido.', 405);
}

try {
    // Obter dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Dados JSON inválidos.');
    }
    
    // Validar dados obrigatórios
    if (empty($data['action'])) {
        sendErrorResponse('Ação é obrigatória.');
    }
    
    $action = sanitize($data['action']);
    $description = isset($data['description']) ? sanitize($data['description']) : '';
    $additionalData = isset($data['additional_data']) ? $data['additional_data'] : [];
    
    // Obter ID do usuário se logado
    $userId = isUserLoggedIn() ? $_SESSION['user_id'] : null;
    
    // Registrar atividade
    logUserActivity($userId, $action, $description, $additionalData);
    
    // Resposta de sucesso
    sendSuccessResponse('Atividade registrada com sucesso.');
    
} catch (Exception $e) {
    // Log do erro
    logSystemError('Error in log_activity API: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor.', 500);
}
?>

