<?php
/**
 * API para ativar/desativar produtos
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Método não permitido.', 405);
}

// Verificar se usuário está logado e é admin
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['id'])) {
    sendErrorResponse('Acesso negado. Apenas administradores podem realizar esta ação.', 403);
}

try {
    // Obter dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Dados JSON inválidos.');
    }
    
    // Validar dados obrigatórios
    if (empty($data['product_id']) || !isset($data['status'])) {
        sendErrorResponse('ID do produto e status são obrigatórios.');
    }
    
    $productId = (int)$data['product_id'];
    $status = (int)$data['status'];
    
    // Validar status
    if ($status !== 0 && $status !== 1) {
        sendErrorResponse('Status deve ser 0 (inativo) ou 1 (ativo).');
    }
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se produto existe
    $stmt = $pdo->prepare("SELECT id, nome FROM produtos WHERE id = ?");
    $stmt->execute([$productId]);
    $produto = $stmt->fetch();
    
    if (!$produto) {
        sendErrorResponse('Produto não encontrado.');
    }
    
    // Atualizar status do produto
    $stmt = $pdo->prepare("UPDATE produtos SET ativo = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $productId]);
    
    if (!$result) {
        sendErrorResponse('Erro ao atualizar status do produto.');
    }
    
    // Log da atividade
    $action = $status ? 'product_activated' : 'product_deactivated';
    $description = $status ? 'Produto ativado' : 'Produto desativado';
    
    logUserActivity($currentUser['id'], $action, $description, [
        'product_id' => $productId,
        'product_name' => $produto['nome'],
        'new_status' => $status
    ]);
    
    // Resposta de sucesso
    sendSuccessResponse($status ? 'Produto ativado com sucesso!' : 'Produto desativado com sucesso!', [
        'product_id' => $productId,
        'status' => $status
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in toggle_product_status: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'product_id' => $productId ?? null,
        'admin_id' => $currentUser['id'] ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in toggle_product_status: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'product_id' => $productId ?? null,
        'admin_id' => $currentUser['id'] ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

