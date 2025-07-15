<?php
/**
 * API para atualizar quantidade de itens no carrinho
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
    if (empty($data['item_id']) || empty($data['quantity'])) {
        sendErrorResponse('ID do item e quantidade são obrigatórios.');
    }
    
    $itemId = (int)$data['item_id'];
    $quantity = (int)$data['quantity'];
    
    // Validar quantidade
    if ($quantity < 1 || $quantity > 10) {
        sendErrorResponse('Quantidade deve estar entre 1 e 10.');
    }
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se usuário está logado
    $currentUser = getCurrentUser();
    
    if ($currentUser) {
        // Atualizar carrinho do usuário logado
        $stmt = $pdo->prepare("
            UPDATE carrinho 
            SET quantidade = ?, updated_at = NOW()
            WHERE id = ? AND usuario_id = ?
        ");
        $result = $stmt->execute([$quantity, $itemId, $currentUser['id']]);
    } else {
        // Atualizar carrinho da sessão
        $session_id = session_id();
        $stmt = $pdo->prepare("
            UPDATE carrinho 
            SET quantidade = ?, updated_at = NOW()
            WHERE id = ? AND session_id = ?
        ");
        $result = $stmt->execute([$quantity, $itemId, $session_id]);
    }
    
    if ($stmt->rowCount() === 0) {
        sendErrorResponse('Item não encontrado no carrinho.');
    }
    
    // Log da atividade
    if ($currentUser) {
        logUserActivity($currentUser['id'], 'update_cart_quantity', 'Updated cart item quantity', [
            'item_id' => $itemId,
            'new_quantity' => $quantity
        ]);
    }
    
    // Resposta de sucesso
    sendSuccessResponse('Quantidade atualizada com sucesso!', [
        'item_id' => $itemId,
        'quantity' => $quantity
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in update_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'item_id' => $itemId ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in update_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'item_id' => $itemId ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

