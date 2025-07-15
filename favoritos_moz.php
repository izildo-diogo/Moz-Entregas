<?php
/**
 * Página de favoritos - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php?message=Você precisa fazer login para acessar seus favoritos.&type=error');
    exit;
}

$message = '';
$messageType = '';

// Processar remoção de favorito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    try {
        // Validar token CSRF
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido.');
        }
        
        $produtoId = (int)$_POST['produto_id'];
        
        if ($produtoId <= 0) {
            throw new Exception('ID do produto inválido.');
        }
        
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND produto_id = ?");
        $stmt->execute([$currentUser['id'], $produtoId]);
        
        // Log da atividade
        logUserActivity($currentUser['id'], 'favorite_removed', 'Product removed from favorites', [
            'produto_id' => $produtoId
        ]);
        
        $message = 'Produto removido dos favoritos!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        logSystemError('Remove favorite error: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    }
}

// Buscar produtos favoritos
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT p.*, l.nome as loja_nome, c.nome as categoria_nome, f.created_at as favorito_em
        FROM favoritos f
        INNER JOIN produtos p ON f.produto_id = p.id
        INNER JOIN lojas l ON p.loja_id = l.id
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE f.usuario_id = ? AND p.ativo = 1 AND l.ativo = 1
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $favoritos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logSystemError('Error loading favorites: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    $favoritos = [];
    $message = 'Erro ao carregar favoritos.';
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Favoritos - MozEntregas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #764ba2;
            --secondary-color: #f093fb;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #e1e5e9;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: var(--light-color);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow-lg);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .page-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 2rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: var(--light-color);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .product-store {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .product-category {
            color: var(--primary-color);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            flex: 1;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Favorite Badge */
        .favorite-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--danger-color);
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            font-size: 1rem;
        }

        .favorite-date {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.7rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .modal-text {
            margin-bottom: 2rem;
            color: #666;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1rem;
            }

            .product-info {
                padding: 1rem;
            }

            .product-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index_moz.php" class="logo">
                <i class="fas fa-utensils"></i>
                MozEntregas
            </a>
            <nav class="nav-links">
                <a href="index_moz.php" class="nav-link">
                    <i class="fas fa-home"></i> Início
                </a>
                <a href="carrinho_moz.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Carrinho
                </a>
                <a href="perfil_moz.php" class="nav-link">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-heart"></i> Meus Favoritos</h1>
            <p>Produtos que você marcou como favoritos</p>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> show">
                <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i> 
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <?php if (empty($favoritos)): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>Nenhum favorito encontrado</h3>
                <p>Você ainda não adicionou nenhum produto aos seus favoritos.</p>
                <a href="index_moz.php" class="btn btn-primary">
                    <i class="fas fa-utensils"></i> Explorar Produtos
                </a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($favoritos as $produto): ?>
                    <div class="product-card">
                        <div class="favorite-date">
                            <?= date('d/m/Y', strtotime($produto['favorito_em'])) ?>
                        </div>
                        
                        <div class="favorite-badge">
                            <i class="fas fa-heart"></i>
                        </div>
                        
                        <img src="<?= $produto['imagem'] ? 'uploads/' . $produto['imagem'] : 'https://via.placeholder.com/300x200?text=Produto' ?>" 
                             alt="<?= htmlspecialchars($produto['nome']) ?>" 
                             class="product-image">
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($produto['nome']) ?></h3>
                            <p class="product-store">
                                <i class="fas fa-store"></i> <?= htmlspecialchars($produto['loja_nome']) ?>
                            </p>
                            <p class="product-category">
                                <i class="fas fa-tag"></i> <?= htmlspecialchars($produto['categoria_nome']) ?>
                            </p>
                            <div class="product-price">
                                MT <?= number_format($produto['preco'], 2, ',', '.') ?>
                            </div>
                            
                            <div class="product-actions">
                                <button onclick="addToCart(<?= $produto['id'] ?>)" class="btn btn-primary">
                                    <i class="fas fa-cart-plus"></i> Adicionar
                                </button>
                                <button onclick="removeFavorite(<?= $produto['id'] ?>, '<?= htmlspecialchars($produto['nome']) ?>')" 
                                        class="btn btn-danger">
                                    <i class="fas fa-heart-broken"></i> Remover
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Remove Favorite Modal -->
    <div id="removeFavoriteModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Remover dos Favoritos</h3>
            <p class="modal-text">Tem certeza que deseja remover este produto dos seus favoritos?</p>
            <div class="modal-actions">
                <button onclick="closeModal()" class="btn btn-outline">Cancelar</button>
                <button onclick="confirmRemoveFavorite()" class="btn btn-danger">Remover</button>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Remove Favorite -->
    <form id="removeFavoriteForm" method="POST" action="" style="display: none;">
        <input type="hidden" name="action" value="remove_favorite">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="produto_id" id="removeProdutoId">
    </form>

    <script>
        let currentProductId = null;

        // Add to cart function
        async function addToCart(productId) {
            try {
                const response = await fetch('api/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        produto_id: productId,
                        quantidade: 1
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    showAlert('Produto adicionado ao carrinho!', 'success');
                    
                    // Update cart count if element exists
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.data.cart_count) {
                        cartCount.textContent = data.data.cart_count;
                    }
                } else {
                    showAlert('Erro: ' + (data.message || 'Erro desconhecido'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showAlert('Erro de conexão. Tente novamente.', 'error');
            }
        }

        // Remove favorite function
        function removeFavorite(productId, productName) {
            currentProductId = productId;
            document.querySelector('#removeFavoriteModal .modal-text').textContent = 
                `Tem certeza que deseja remover "${productName}" dos seus favoritos?`;
            document.getElementById('removeFavoriteModal').classList.add('show');
        }

        // Confirm remove favorite
        function confirmRemoveFavorite() {
            if (currentProductId) {
                document.getElementById('removeProdutoId').value = currentProductId;
                document.getElementById('removeFavoriteForm').submit();
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('removeFavoriteModal').classList.remove('show');
            currentProductId = null;
        }

        // Show alert function
        function showAlert(message, type) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> 
                ${message}
            `;
            
            // Insert after page title
            const pageTitle = document.querySelector('.page-title');
            pageTitle.insertAdjacentElement('afterend', alert);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Close modal when clicking outside
        document.getElementById('removeFavoriteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>

