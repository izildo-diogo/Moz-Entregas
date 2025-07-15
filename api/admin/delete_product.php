<?php
/**
 * API para excluir produtos
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
    if (empty($data['product_id'])) {
        sendErrorResponse('ID do produto é obrigatório.');
    }
    
    $productId = (int)$data['product_id'];
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se produto existe
    $stmt = $pdo->prepare("SELECT id, nome, imagem FROM produtos WHERE id = ?");
    $stmt->execute([$productId]);
    $produto = $stmt->fetch();
    
    if (!$produto) {
        sendErrorResponse('Produto não encontrado.');
    }
    
    // Verificar se produto tem pedidos associados
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM itens_pedido WHERE produto_id = ?");
    $stmt->execute([$productId]);
    $pedidosCount = $stmt->fetchColumn();
    
    if ($pedidosCount > 0) {
        // Se tem pedidos, apenas desativar em vez de excluir
        $stmt = $pdo->prepare("UPDATE produtos SET ativo = 0, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$productId]);
        
        // Log da atividade
        logUserActivity($currentUser['id'], 'product_deactivated_instead_deleted', 'Produto desativado em vez de excluído (tem pedidos associados)', [
            'product_id' => $productId,
            'product_name' => $produto['nome'],
            'orders_count' => $pedidosCount
        ]);
        
        sendSuccessResponse('Produto desativado com sucesso! (Não foi possível excluir pois há pedidos associados)', [
            'product_id' => $productId,
            'action' => 'deactivated'
        ]);
        
        return;
    }
    
    // Iniciar transação
    $pdo->beginTransaction();
    
    try {
        // Remover do carrinho
        $stmt = $pdo->prepare("DELETE FROM carrinho WHERE produto_id = ?");
        $stmt->execute([$productId]);
        
        // Excluir produto
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$productId]);
        
        // Remover imagem se existir
        if ($produto['imagem'] && file_exists('../../uploads/' . $produto['imagem'])) {
            unlink('../../uploads/' . $produto['imagem']);
        }
        
        // Confirmar transação
        $pdo->commit();
        
        // Log da atividade
        logUserActivity($currentUser['id'], 'product_deleted', 'Produto excluído permanentemente', [
            'product_id' => $productId,
            'product_name' => $produto['nome'],
            'image_removed' => !empty($produto['imagem'])
        ]);
        
        // Resposta de sucesso
        sendSuccessResponse('Produto excluído com sucesso!', [
            'product_id' => $productId,
            'action' => 'deleted'
        ]);
        
    } catch (Exception $e) {
        // Reverter transação em caso de erro
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in delete_product: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'product_id' => $productId ?? null,
        'admin_id' => $currentUser['id'] ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in delete_product: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'product_id' => $productId ?? null,
        'admin_id' => $currentUser['id'] ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

