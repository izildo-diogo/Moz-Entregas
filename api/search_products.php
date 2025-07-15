<?php
/**
 * API de pesquisa de produtos - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Obter parâmetros de pesquisa
    $query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
    $categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
    $loja = isset($_GET['loja']) ? (int)$_GET['loja'] : 0;
    $min_preco = isset($_GET['min_preco']) ? (float)$_GET['min_preco'] : 0;
    $max_preco = isset($_GET['max_preco']) ? (float)$_GET['max_preco'] : 0;
    $ordenar = isset($_GET['ordenar']) ? sanitize($_GET['ordenar']) : 'relevancia';
    $limite = isset($_GET['limite']) ? min((int)$_GET['limite'], 50) : 20;
    $pagina = isset($_GET['pagina']) ? max((int)$_GET['pagina'], 1) : 1;
    
    $offset = ($pagina - 1) * $limite;
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Construir query de pesquisa
    $where_conditions = ['p.ativo = 1', 'l.ativo = 1'];
    $params = [];
    
    // Filtro por texto
    if (!empty($query)) {
        $where_conditions[] = "(p.nome LIKE ? OR p.descricao LIKE ? OR l.nome LIKE ?)";
        $search_term = "%$query%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    // Filtro por categoria
    if ($categoria > 0) {
        $where_conditions[] = "p.categoria_id = ?";
        $params[] = $categoria;
    }
    
    // Filtro por loja
    if ($loja > 0) {
        $where_conditions[] = "p.loja_id = ?";
        $params[] = $loja;
    }
    
    // Filtro por preço mínimo
    if ($min_preco > 0) {
        $where_conditions[] = "p.preco >= ?";
        $params[] = $min_preco;
    }
    
    // Filtro por preço máximo
    if ($max_preco > 0) {
        $where_conditions[] = "p.preco <= ?";
        $params[] = $max_preco;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Definir ordenação
    $order_clause = 'ORDER BY ';
    switch ($ordenar) {
        case 'preco_asc':
            $order_clause .= 'p.preco ASC';
            break;
        case 'preco_desc':
            $order_clause .= 'p.preco DESC';
            break;
        case 'nome':
            $order_clause .= 'p.nome ASC';
            break;
        case 'mais_vendidos':
            $order_clause .= 'p.cliques DESC, p.created_at DESC';
            break;
        case 'mais_recentes':
            $order_clause .= 'p.created_at DESC';
            break;
        default: // relevancia
            if (!empty($query)) {
                $order_clause .= "
                    CASE 
                        WHEN p.nome LIKE ? THEN 1
                        WHEN p.descricao LIKE ? THEN 2
                        WHEN l.nome LIKE ? THEN 3
                        ELSE 4
                    END ASC, p.cliques DESC
                ";
                // Adicionar parâmetros para ordenação por relevância
                array_unshift($params, "%$query%", "%$query%", "%$query%");
            } else {
                $order_clause .= 'p.cliques DESC, p.created_at DESC';
            }
            break;
    }
    
    // Contar total de resultados
    $count_query = "
        SELECT COUNT(*) as total
        FROM produtos p
        INNER JOIN lojas l ON p.loja_id = l.id
        INNER JOIN categorias c ON p.categoria_id = c.id
        $where_clause
    ";
    
    $count_params = $params;
    if ($ordenar === 'relevancia' && !empty($query)) {
        // Remover os parâmetros de ordenação para a contagem
        $count_params = array_slice($params, 3);
    }
    
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($count_params);
    $total_resultados = $stmt->fetchColumn();
    
    // Buscar produtos
    $search_query = "
        SELECT p.*, l.nome as loja_nome, l.endereco as loja_endereco, 
               c.nome as categoria_nome, c.cor as categoria_cor
        FROM produtos p
        INNER JOIN lojas l ON p.loja_id = l.id
        INNER JOIN categorias c ON p.categoria_id = c.id
        $where_clause
        $order_clause
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limite;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($search_query);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
    
    // Processar resultados
    $produtos_formatados = [];
    foreach ($produtos as $produto) {
        $produtos_formatados[] = [
            'id' => (int)$produto['id'],
            'nome' => $produto['nome'],
            'descricao' => $produto['descricao'],
            'preco' => (float)$produto['preco'],
            'preco_formatado' => 'MT ' . number_format($produto['preco'], 2, ',', '.'),
            'imagem' => $produto['imagem'] ? '/uploads/' . $produto['imagem'] : null,
            'categoria' => [
                'id' => (int)$produto['categoria_id'],
                'nome' => $produto['categoria_nome'],
                'cor' => $produto['categoria_cor']
            ],
            'loja' => [
                'id' => (int)$produto['loja_id'],
                'nome' => $produto['loja_nome'],
                'endereco' => $produto['loja_endereco']
            ],
            'cliques' => (int)$produto['cliques'],
            'created_at' => $produto['created_at']
        ];
    }
    
    // Buscar categorias disponíveis para filtros
    $stmt = $pdo->query("
        SELECT c.*, COUNT(p.id) as total_produtos
        FROM categorias c
        INNER JOIN produtos p ON c.id = p.categoria_id
        WHERE c.ativo = 1 AND p.ativo = 1
        GROUP BY c.id
        ORDER BY c.nome
    ");
    $categorias = $stmt->fetchAll();
    
    // Buscar lojas disponíveis para filtros
    $stmt = $pdo->query("
        SELECT l.*, COUNT(p.id) as total_produtos
        FROM lojas l
        INNER JOIN produtos p ON l.id = p.loja_id
        WHERE l.ativo = 1 AND p.ativo = 1
        GROUP BY l.id
        ORDER BY l.nome
    ");
    $lojas = $stmt->fetchAll();
    
    // Calcular paginação
    $total_paginas = ceil($total_resultados / $limite);
    $tem_proxima = $pagina < $total_paginas;
    $tem_anterior = $pagina > 1;
    
    // Log da pesquisa
    $currentUser = getCurrentUser();
    if ($currentUser) {
        logUserActivity($currentUser['id'], 'product_search', 'Searched for products', [
            'query' => $query,
            'categoria' => $categoria,
            'loja' => $loja,
            'total_resultados' => $total_resultados
        ]);
    }
    
    // Resposta de sucesso
    sendSuccessResponse('Pesquisa realizada com sucesso.', [
        'produtos' => $produtos_formatados,
        'paginacao' => [
            'pagina_atual' => $pagina,
            'total_paginas' => $total_paginas,
            'total_resultados' => $total_resultados,
            'limite' => $limite,
            'tem_proxima' => $tem_proxima,
            'tem_anterior' => $tem_anterior
        ],
        'filtros' => [
            'categorias' => $categorias,
            'lojas' => $lojas,
            'query' => $query,
            'categoria_selecionada' => $categoria,
            'loja_selecionada' => $loja,
            'min_preco' => $min_preco,
            'max_preco' => $max_preco,
            'ordenacao' => $ordenar
        ],
        'opcoes_ordenacao' => [
            'relevancia' => 'Relevância',
            'mais_recentes' => 'Mais Recentes',
            'mais_vendidos' => 'Mais Vendidos',
            'nome' => 'Nome A-Z',
            'preco_asc' => 'Menor Preço',
            'preco_desc' => 'Maior Preço'
        ]
    ]);
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in product search: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'query' => $query ?? null,
        'categoria' => $categoria ?? null,
        'loja' => $loja ?? null
    ]);
    
    sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in product search: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'query' => $query ?? null,
        'categoria' => $categoria ?? null,
        'loja' => $loja ?? null
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

