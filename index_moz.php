<?php
/**
 * Página principal da loja MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();

// Busca e filtros
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoria_id = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$loja_id = isset($_GET['loja']) ? (int)$_GET['loja'] : 0;

try {
    $pdo = getConnection();
    
    // Buscar categorias para o filtro
    $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
    $categorias = $stmt->fetchAll();
    
    // Query base para produtos
    $whereConditions = ['p.ativo = 1', 'l.ativo = 1'];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(p.nome LIKE :search OR p.descricao LIKE :search OR l.nome LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    if ($categoria_id > 0) {
        $whereConditions[] = "p.categoria_id = :categoria_id";
        $params[':categoria_id'] = $categoria_id;
    }
    
    if ($loja_id > 0) {
        $whereConditions[] = "p.loja_id = :loja_id";
        $params[':loja_id'] = $loja_id;
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Buscar produtos
    $sql = "SELECT p.*, l.nome as loja_nome, c.nome as categoria_nome, c.icone as categoria_icone
            FROM produtos p 
            INNER JOIN lojas l ON p.loja_id = l.id 
            INNER JOIN categorias c ON p.categoria_id = c.id
            $whereClause
            ORDER BY p.cliques DESC, p.vendas DESC, p.created_at DESC
            LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
    
    // Buscar lojas populares
    $stmt = $pdo->query("
        SELECT * FROM lojas 
        WHERE ativo = 1 
        ORDER BY cliques DESC, vendas DESC 
        LIMIT 8
    ");
    $lojas_populares = $stmt->fetchAll();
    
    // Contar itens no carrinho
    $cart_count = 0;
    if ($currentUser) {
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE usuario_id = ?");
        $stmt->execute([$currentUser['id']]);
        $cart_count = $stmt->fetchColumn() ?: 0;
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("SELECT SUM(quantidade) as total FROM carrinho WHERE session_id = ?");
        $stmt->execute([$session_id]);
        $cart_count = $stmt->fetchColumn() ?: 0;
    }
    
} catch(PDOException $e) {
    logSystemError('Database error in index: ' . $e->getMessage());
    $error = "Erro ao carregar dados. Tente novamente mais tarde.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MozEntregas - Delivery de Comida em Moçambique</title>
    <meta name="description" content="Peça comida online em Maputo com o MozEntregas. Delivery rápido dos melhores restaurantes e supermercados.">
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
            padding-bottom: 80px; /* Space for bottom nav */
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo:hover {
            opacity: 0.9;
        }

        /* Search Container */
        .search-container {
            flex: 1;
            max-width: 500px;
            position: relative;
        }

        .search-form {
            display: flex;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: none;
            outline: none;
            font-size: 1rem;
        }

        .search-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .search-btn:hover {
            background: var(--primary-dark);
        }

        /* Mobile Search Toggle */
        .search-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-btn {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .user-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 6px;
            box-shadow: var(--shadow-lg);
            min-width: 200px;
            z-index: 1001;
            display: none;
            margin-top: 0.5rem;
        }

        .user-dropdown.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 0.75rem 1rem;
            color: var(--dark-color);
            text-decoration: none;
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--light-color);
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Alert */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* Section Title */
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Categories */
        .categories-section {
            margin-bottom: 3rem;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }

        .category-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--dark-color);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .category-card:hover,
        .category-card.active {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .category-icon {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .category-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Stores */
        .stores-section {
            margin-bottom: 3rem;
        }

        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .store-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
        }

        .store-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .store-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .store-info {
            padding: 1rem;
        }

        .store-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .store-location {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .store-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .store-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: #999;
        }

        /* Products */
        .products-section {
            margin-bottom: 3rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .product-category {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-info {
            padding: 1rem;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 0.5rem;
        }

        .product-store {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .add-to-cart-btn {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .add-to-cart-btn:hover {
            background: var(--primary-dark);
        }

        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ccc;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-around;
            padding: 0.5rem 0;
            z-index: 1000;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 0.8rem;
            padding: 0.5rem;
            position: relative;
            transition: var(--transition);
        }

        .nav-item.active,
        .nav-item:hover {
            color: var(--primary-color);
        }

        .nav-item i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .search-container {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--primary-color);
                padding: 1rem;
            }

            .search-container.show {
                display: block;
            }

            .search-toggle {
                display: block;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }

            .stores-grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 1rem;
            }
        }

        /* Loading */
        .loading {
            display: none;
        }

        .loading.show {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

            <!-- Search (Desktop) -->
            <div class="search-container" id="searchContainer">
                <form class="search-form" method="GET" action="index_moz.php">
                    <input type="text" 
                           name="search" 
                           class="search-input" 
                           placeholder="Buscar restaurantes, pratos..."
                           value="<?= htmlspecialchars($search) ?>">
                    <?php if ($categoria_id): ?>
                        <input type="hidden" name="categoria" value="<?= $categoria_id ?>">
                    <?php endif; ?>
                    <?php if ($loja_id): ?>
                        <input type="hidden" name="loja" value="<?= $loja_id ?>">
                    <?php endif; ?>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <!-- Search Toggle (Mobile) -->
            <button class="search-toggle" onclick="toggleMobileSearch()">
                <i class="fas fa-search"></i>
            </button>

            <!-- User Menu -->
            <div class="user-menu">
                <?php if ($currentUser): ?>
                    <button class="user-btn" onclick="toggleUserDropdown()">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($currentUser['nome']) ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="perfil_moz.php" class="dropdown-item">
                            <i class="fas fa-user"></i> Meu Perfil
                        </a>
                        <a href="pedidos_moz.php" class="dropdown-item">
                            <i class="fas fa-shopping-bag"></i> Meus Pedidos
                        </a>
                        <a href="favoritos_moz.php" class="dropdown-item">
                            <i class="fas fa-heart"></i> Favoritos
                        </a>
                        <a href="auth/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="user-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Entrar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-error show">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Categories Filter -->
        <section class="categories-section">
            <h2 class="section-title">
                <i class="fas fa-filter"></i> Categorias
            </h2>
            <div class="categories-grid">
                <a href="index_moz.php" class="category-card <?= !$categoria_id ? 'active' : '' ?>">
                    <div class="category-icon"><i class="fas fa-th-large"></i></div>
                    <div class="category-name">Todas</div>
                </a>
                <?php foreach ($categorias as $categoria): ?>
                    <a href="index_moz.php?categoria=<?= $categoria['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="category-card <?= $categoria_id == $categoria['id'] ? 'active' : '' ?>">
                        <div class="category-icon">
                            <i class="<?= $categoria['icone'] ?: 'fas fa-utensils' ?>"></i>
                        </div>
                        <div class="category-name"><?= htmlspecialchars($categoria['nome']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <?php if (!$search && !$categoria_id && !$loja_id): ?>
            <!-- Popular Stores -->
            <section class="stores-section">
                <h2 class="section-title">
                    <i class="fas fa-star"></i> Restaurantes Populares
                </h2>
                <div class="stores-grid">
                    <?php foreach ($lojas_populares as $loja): ?>
                        <div class="store-card" onclick="viewStore(<?= $loja['id'] ?>)">
                            <img src="<?= $loja['imagem'] ? 'uploads/' . $loja['imagem'] : 'https://via.placeholder.com/300x150?text=Restaurante' ?>" 
                                 alt="<?= htmlspecialchars($loja['nome']) ?>" 
                                 class="store-image">
                            <div class="store-info">
                                <h3 class="store-name"><?= htmlspecialchars($loja['nome']) ?></h3>
                                <p class="store-location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= htmlspecialchars($loja['endereco']) ?>
                                </p>
                                <p class="store-description"><?= htmlspecialchars($loja['descricao']) ?></p>
                                <div class="store-stats">
                                    <span><i class="fas fa-eye"></i> <?= $loja['cliques'] ?> visualizações</span>
                                    <span><i class="fas fa-shopping-bag"></i> <?= $loja['vendas'] ?> vendas</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Products -->
        <section class="products-section">
            <h2 class="section-title">
                <i class="fas fa-utensils"></i> 
                <?php if ($search): ?>
                    Resultados para "<?= htmlspecialchars($search) ?>"
                <?php elseif ($categoria_id): ?>
                    <?php 
                    $categoria_nome = '';
                    foreach ($categorias as $cat) {
                        if ($cat['id'] == $categoria_id) {
                            $categoria_nome = $cat['nome'];
                            break;
                        }
                    }
                    echo htmlspecialchars($categoria_nome);
                    ?>
                <?php elseif ($loja_id): ?>
                    Produtos da Loja
                <?php else: ?>
                    Pratos Populares
                <?php endif; ?>
            </h2>
            
            <?php if (empty($produtos)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Nenhum produto encontrado</h3>
                    <p>Tente buscar por outros termos ou explore nossas categorias.</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <div class="product-card">
                            <img src="<?= $produto['imagem'] ? 'uploads/' . $produto['imagem'] : 'https://via.placeholder.com/250x180?text=Produto' ?>" 
                                 alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                 class="product-image">
                            
                            <div class="product-category">
                                <i class="<?= $produto['categoria_icone'] ?: 'fas fa-utensils' ?>"></i>
                                <?= htmlspecialchars($produto['categoria_nome']) ?>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($produto['nome']) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($produto['descricao']) ?></p>
                                <div class="product-price">MT <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                                <div class="product-store">
                                    <i class="fas fa-store"></i> <?= htmlspecialchars($produto['loja_nome']) ?>
                                </div>
                                <button class="add-to-cart-btn" onclick="addToCart(<?= $produto['id'] ?>)">
                                    <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index_moz.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="categorias_moz.php" class="nav-item">
            <i class="fas fa-th-large"></i>
            <span>Categorias</span>
        </a>
        <a href="carrinho_moz.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrinho</span>
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge" id="cartBadge"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>
        <a href="pedidos_moz.php" class="nav-item">
            <i class="fas fa-shopping-bag"></i>
            <span>Pedidos</span>
        </a>
        <a href="<?= $currentUser ? 'perfil_moz.php' : 'login.php' ?>" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </nav>

    <script>
        // Toggle mobile search
        function toggleMobileSearch() {
            const searchContainer = document.getElementById('searchContainer');
            searchContainer.classList.toggle('show');
            
            if (searchContainer.classList.contains('show')) {
                searchContainer.querySelector('.search-input').focus();
            }
        }

        // Toggle user dropdown
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                const dropdown = document.getElementById('userDropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // View store products
        function viewStore(lojaId) {
            window.location.href = `index_moz.php?loja=${lojaId}`;
        }

        // Add to cart function
        function addToCart(productId) {
            const button = event.target;
            const originalText = button.innerHTML;
            
            // Show loading
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner loading show"></i> Adicionando...';
            
            fetch('api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    produto_id: productId,
                    quantidade: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart badge
                    updateCartBadge(data.data.cart_count);
                    
                    // Show success feedback
                    button.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                    button.style.background = 'var(--success-color)';
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        button.disabled = false;
                        button.innerHTML = originalText;
                        button.style.background = '';
                    }, 2000);
                    
                    // Show success message
                    showAlert(data.message, 'success');
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Reset button
                button.disabled = false;
                button.innerHTML = originalText;
                
                // Show error message
                showAlert(error.message || 'Erro ao adicionar produto ao carrinho.', 'error');
            });
        }

        // Update cart badge
        function updateCartBadge(count) {
            const badge = document.getElementById('cartBadge');
            const cartLink = document.querySelector('.nav-item[href="carrinho_moz.php"]');
            
            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-badge';
                    newBadge.id = 'cartBadge';
                    newBadge.textContent = count;
                    cartLink.appendChild(newBadge);
                }
            } else {
                if (badge) {
                    badge.remove();
                }
            }
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
            
            // Insert at top of main content
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alert, mainContent.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Log user activity
        <?php if ($currentUser): ?>
        fetch('api/log_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'page_view',
                description: 'Viewed main page',
                additional_data: {
                    search: '<?= addslashes($search) ?>',
                    categoria_id: <?= $categoria_id ?: 'null' ?>,
                    loja_id: <?= $loja_id ?: 'null' ?>
                }
            })
        }).catch(console.error);
        <?php endif; ?>
    </script>
</body>
</html>

