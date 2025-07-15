<?php
/**
 * API para obter contagem de itens no carrinho
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Obter identificador do usuário/sessão
    $currentUser = getCurrentUser();
    $userId = $currentUser ? $currentUser['id'] : null;
    $sessionId = $currentUser ? null : session_id();
    
    // Contar itens no carrinho
    if ($userId) {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    $cartCount = $stmt->fetchColumn() ?: 0;
    
    // Resposta de sucesso
    sendSuccessResponse('Contagem do carrinho obtida com sucesso.', [
        'cart_count' => (int)$cartCount
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in get_cart_count: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'user_id' => $userId ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in get_cart_count: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'user_id' => $userId ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

