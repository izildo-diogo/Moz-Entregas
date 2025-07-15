<?php
/**
 * API para adicionar produtos ao carrinho
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
    if (empty($data['produto_id'])) {
        sendErrorResponse('ID do produto é obrigatório.');
    }
    
    $produtoId = (int)$data['produto_id'];
    $quantidade = isset($data['quantidade']) ? (int)$data['quantidade'] : 1;
    
    // Validar quantidade
    if ($quantidade <= 0) {
        sendErrorResponse('Quantidade deve ser maior que zero.');
    }
    
    if ($quantidade > 99) {
        sendErrorResponse('Quantidade máxima é 99 unidades.');
    }
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se produto existe e está ativo
    $stmt = $pdo->prepare("
        SELECT p.*, l.nome as loja_nome 
        FROM produtos p 
        INNER JOIN lojas l ON p.loja_id = l.id 
        WHERE p.id = ? AND p.ativo = 1 AND l.ativo = 1
    ");
    $stmt->execute([$produtoId]);
    $produto = $stmt->fetch();
    
    if (!$produto) {
        sendErrorResponse('Produto não encontrado ou não disponível.');
    }
    
    // Obter identificador do usuário/sessão
    $currentUser = getCurrentUser();
    $userId = $currentUser ? $currentUser['id'] : null;
    $sessionId = $currentUser ? null : session_id();
    
    // Verificar se produto já está no carrinho
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT id, quantidade 
            FROM carrinho 
            WHERE usuario_id = ? AND produto_id = ?
        ");
        $stmt->execute([$userId, $produtoId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT id, quantidade 
            FROM carrinho 
            WHERE session_id = ? AND produto_id = ?
        ");
        $stmt->execute([$sessionId, $produtoId]);
    }
    
    $carrinhoItem = $stmt->fetch();
    
    if ($carrinhoItem) {
        // Atualizar quantidade existente
        $novaQuantidade = $carrinhoItem['quantidade'] + $quantidade;
        
        if ($novaQuantidade > 99) {
            sendErrorResponse('Quantidade total não pode exceder 99 unidades.');
        }
        
        $stmt = $pdo->prepare("
            UPDATE carrinho 
            SET quantidade = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$novaQuantidade, $carrinhoItem['id']]);
        
        $action = 'cart_item_updated';
        $description = 'Quantidade atualizada no carrinho';
        
    } else {
        // Adicionar novo item ao carrinho
        $stmt = $pdo->prepare("
            INSERT INTO carrinho (usuario_id, session_id, produto_id, quantidade, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $sessionId, $produtoId, $quantidade]);
        
        $action = 'cart_item_added';
        $description = 'Produto adicionado ao carrinho';
    }
    
    // Incrementar contador de cliques do produto
    $stmt = $pdo->prepare("UPDATE produtos SET cliques = cliques + 1 WHERE id = ?");
    $stmt->execute([$produtoId]);
    
    // Contar total de itens no carrinho
    if ($userId) {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE session_id = ?");
        $stmt->execute([$sessionId]);
    }
    
    $cartCount = $stmt->fetchColumn() ?: 0;
    
    // Log da atividade
    logUserActivity($userId, $action, $description, [
        'produto_id' => $produtoId,
        'produto_nome' => $produto['nome'],
        'quantidade' => $quantidade,
        'preco' => $produto['preco']
    ]);
    
    // Resposta de sucesso
    sendSuccessResponse('Produto adicionado ao carrinho com sucesso!', [
        'produto_id' => $produtoId,
        'produto_nome' => $produto['nome'],
        'quantidade' => $quantidade,
        'cart_count' => $cartCount
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in add_to_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'produto_id' => $produtoId ?? null,
        'user_id' => $userId ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in add_to_cart: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'produto_id' => $produtoId ?? null,
        'user_id' => $userId ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

