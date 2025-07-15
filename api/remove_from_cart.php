<?php
/**
 * API para remover itens do carrinho
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
    if (empty($data['item_id'])) {
        sendErrorResponse('ID do item é obrigatório.');
    }
    
    $itemId = (int)$data['item_id'];
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se usuário está logado
    $currentUser = getCurrentUser();
    
    // Buscar informações do item antes de remover (para log)
    if ($currentUser) {
        $stmt = $pdo->prepare("
            SELECT c.*, p.nome as produto_nome
            FROM carrinho c
            INNER JOIN produtos p ON c.produto_id = p.id
            WHERE c.id = ? AND c.usuario_id = ?
        ");
        $stmt->execute([$itemId, $currentUser['id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT c.*, p.nome as produto_nome
            FROM carrinho c
            INNER JOIN produtos p ON c.produto_id = p.id
            WHERE c.id = ? AND c.session_id = ?
        ");
        $stmt->execute([$itemId, $session_id]);
    }
    
    $cartItem = $stmt->fetch();
    
    if (!$cartItem) {
        sendErrorResponse('Item não encontrado no carrinho.');
    }
    
    // Remover item do carrinho
    if ($currentUser) {
        $stmt = $pdo->prepare("DELETE FROM carrinho WHERE id = ? AND usuario_id = ?");
        $result = $stmt->execute([$itemId, $currentUser['id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("DELETE FROM carrinho WHERE id = ? AND session_id = ?");
        $result = $stmt->execute([$itemId, $session_id]);
    }
    
    if ($stmt->rowCount() === 0) {
        sendErrorResponse('Erro ao remover item do carrinho.');
    }
    
    // Log da atividade
    if ($currentUser) {
        logUserActivity($currentUser['id'], 'remove_from_cart', 'Removed item from cart', [
            'item_id' => $itemId,
            'produto_nome' => $cartItem['produto_nome'],
            'quantidade' => $cartItem['quantidade']
        ]);
    }
    
    // Contar itens restantes no carrinho
    if ($currentUser) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM carrinho WHERE usuario_id = ?");
        $stmt->execute([$currentUser['id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM carrinho WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    
    $remainingItems = $stmt->fetchColumn();
    
    // Resposta de sucesso
    sendSuccessResponse('Item removido do carrinho com sucesso!', [
        'item_id' => $itemId,
        'remaining_items' => $remainingItems
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in remove_from_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'item_id' => $itemId ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in remove_from_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'item_id' => $itemId ?? null,
        'user_id' => $_SESSION['user_id'] ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

