<?php
/**
 * Página do carrinho de compras MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();

try {
    $pdo = getConnection();
    
    // Buscar itens do carrinho
    $cartItems = [];
    $cartTotal = 0;
    
    if ($currentUser) {
        // Carrinho do usuário logado
        $stmt = $pdo->prepare("
            SELECT c.*, p.nome, p.preco, p.descricao, p.imagem, 
                   l.nome as loja_nome, l.id as loja_id
            FROM carrinho c
            INNER JOIN produtos p ON c.produto_id = p.id
            INNER JOIN lojas l ON p.loja_id = l.id
            WHERE c.usuario_id = ? AND p.ativo = 1 AND l.ativo = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$currentUser['id']]);
    } else {
        // Carrinho da sessão
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT c.*, p.nome, p.preco, p.descricao, p.imagem, 
                   l.nome as loja_nome, l.id as loja_id
            FROM carrinho c
            INNER JOIN produtos p ON c.produto_id = p.id
            INNER JOIN lojas l ON p.loja_id = l.id
            WHERE c.session_id = ? AND p.ativo = 1 AND l.ativo = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$session_id]);
    }
    
    $cartItems = $stmt->fetchAll();
    
    // Calcular total
    foreach ($cartItems as $item) {
        $cartTotal += $item['preco'] * $item['quantidade'];
    }
    
    // Agrupar por loja
    $itemsByStore = [];
    foreach ($cartItems as $item) {
        $storeId = $item['loja_id'];
        if (!isset($itemsByStore[$storeId])) {
            $itemsByStore[$storeId] = [
                'loja_nome' => $item['loja_nome'],
                'items' => []
            ];
        }
        $itemsByStore[$storeId]['items'][] = $item;
    }
    
} catch(PDOException $e) {
    logSystemError('Database error in cart: ' . $e->getMessage());
    $error = "Erro ao carregar carrinho. Tente novamente mais tarde.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrinho - MozEntregas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #667eea;
            --primary-dark: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            box-shadow: var(--shadow);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .back-button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            min-height: calc(100vh - 200px);
        }

        /* Cart Container */
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        .cart-items {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .cart-header {
            background: var(--light-color);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .cart-header h2 {
            font-size: 1.3rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Store Section */
        .store-section {
            border-bottom: 1px solid var(--border-color);
        }

        .store-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Cart Item */
        .cart-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
            flex-shrink: 0;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }

        .item-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .item-price {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }

        .quantity-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--border-color);
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.25rem;
            font-weight: 600;
        }

        .remove-btn {
            background: none;
            border: none;
            color: var(--danger-color);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .remove-btn:hover {
            background: rgba(220, 53, 69, 0.1);
        }

        /* Cart Summary */
        .cart-summary {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .summary-row:not(:last-child) {
            border-bottom: 1px solid var(--border-color);
        }

        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 2px solid var(--border-color);
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 1rem;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .checkout-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .empty-cart h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .empty-cart p {
            color: #666;
            margin-bottom: 2rem;
        }

        .continue-shopping {
            background: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .continue-shopping:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                margin-bottom: 80px;
            }

            .cart-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .cart-summary {
                position: static;
                order: -1;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .item-image {
                width: 100%;
                height: 150px;
            }

            .quantity-controls {
                justify-content: center;
                width: 100%;
            }
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
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #666;
            font-size: 0.7rem;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .nav-item:hover,
        .nav-item.active {
            color: var(--primary-color);
        }

        .nav-item i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        @media (min-width: 769px) {
            .bottom-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="index_moz.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
            <h1 class="header-title">
                <i class="fas fa-shopping-cart"></i> Carrinho
            </h1>
            <div></div> <!-- Spacer for flexbox -->
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <?php if (isset($error)): ?>
            <div class="alert alert-error show">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div id="alertContainer"></div>

        <?php if (empty($cartItems)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Seu carrinho está vazio</h3>
                <p>Adicione produtos dos nossos restaurantes para começar a fazer seu pedido.</p>
                <a href="index_moz.php" class="continue-shopping">
                    <i class="fas fa-utensils"></i> Explorar Restaurantes
                </a>
            </div>
        <?php else: ?>
            <!-- Cart with Items -->
            <div class="cart-container">
                <!-- Cart Items -->
                <div class="cart-items">
                    <div class="cart-header">
                        <h2>
                            <i class="fas fa-list"></i> 
                            Seus Itens (<?= count($cartItems) ?> <?= count($cartItems) == 1 ? 'item' : 'itens' ?>)
                        </h2>
                    </div>

                    <?php foreach ($itemsByStore as $storeId => $storeData): ?>
                        <div class="store-section">
                            <div class="store-header">
                                <i class="fas fa-store"></i>
                                <?= htmlspecialchars($storeData['loja_nome']) ?>
                            </div>

                            <?php foreach ($storeData['items'] as $item): ?>
                                <div class="cart-item" data-item-id="<?= $item['id'] ?>">
                                    <img src="<?= $item['imagem'] ? 'uploads/' . $item['imagem'] : 'https://via.placeholder.com/80x80?text=Produto' ?>" 
                                         alt="<?= htmlspecialchars($item['nome']) ?>" 
                                         class="item-image">
                                    
                                    <div class="item-info">
                                        <h3 class="item-name"><?= htmlspecialchars($item['nome']) ?></h3>
                                        <p class="item-description"><?= htmlspecialchars($item['descricao']) ?></p>
                                        <div class="item-price">MT <?= number_format($item['preco'], 2, ',', '.') ?></div>
                                        
                                        <div class="quantity-controls">
                                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" 
                                                   class="quantity-input" 
                                                   value="<?= $item['quantidade'] ?>" 
                                                   min="1" 
                                                   max="10"
                                                   onchange="updateQuantity(<?= $item['id'] ?>, 0, this.value)">
                                            <button class="quantity-btn" onclick="updateQuantity(<?= $item['id'] ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button class="remove-btn" onclick="removeFromCart(<?= $item['id'] ?>)" title="Remover item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="cart-summary">
                    <h3 class="summary-title">Resumo do Pedido</h3>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">MT <?= number_format($cartTotal, 2, ',', '.') ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Taxa de entrega:</span>
                        <span id="delivery-fee">MT 50,00</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="total">MT <?= number_format($cartTotal + 50, 2, ',', '.') ?></span>
                    </div>

                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        <i class="fas fa-credit-card"></i> Finalizar Pedido
                    </button>

                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="index_moz.php" style="color: var(--primary-color); text-decoration: none;">
                            <i class="fas fa-plus"></i> Adicionar mais itens
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="bottom-nav">
        <a href="index_moz.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="categorias_moz.php" class="nav-item">
            <i class="fas fa-th-large"></i>
            <span>Categorias</span>
        </a>
        <a href="carrinho_moz.php" class="nav-item active">
            <i class="fas fa-shopping-cart"></i>
            <span>Carrinho</span>
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
        // Show alert function
        function showAlert(type, message) {
            const alertContainer = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> ${message}`;
            
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Update quantity
        async function updateQuantity(itemId, change, newValue = null) {
            const quantityInput = document.querySelector(`[data-item-id="${itemId}"] .quantity-input`);
            let quantity = newValue !== null ? parseInt(newValue) : parseInt(quantityInput.value) + change;
            
            if (quantity < 1) quantity = 1;
            if (quantity > 10) quantity = 10;
            
            try {
                const response = await fetch('api/update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        quantity: quantity
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    quantityInput.value = quantity;
                    updateCartTotals();
                    showAlert('success', 'Quantidade atualizada!');
                } else {
                    showAlert('error', data.message || 'Erro ao atualizar quantidade.');
                }
            } catch (error) {
                console.error('Error updating quantity:', error);
                showAlert('error', 'Erro de conexão. Tente novamente.');
            }
        }

        // Remove from cart
        async function removeFromCart(itemId) {
            if (!confirm('Tem certeza que deseja remover este item do carrinho?')) {
                return;
            }
            
            try {
                const response = await fetch('api/remove_from_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
                    itemElement.remove();
                    updateCartTotals();
                    showAlert('success', 'Item removido do carrinho!');
                    
                    // Check if cart is empty
                    const remainingItems = document.querySelectorAll('.cart-item');
                    if (remainingItems.length === 0) {
                        location.reload();
                    }
                } else {
                    showAlert('error', data.message || 'Erro ao remover item.');
                }
            } catch (error) {
                console.error('Error removing item:', error);
                showAlert('error', 'Erro de conexão. Tente novamente.');
            }
        }

        // Update cart totals
        function updateCartTotals() {
            let subtotal = 0;
            const cartItems = document.querySelectorAll('.cart-item');
            
            cartItems.forEach(item => {
                const priceText = item.querySelector('.item-price').textContent;
                const price = parseFloat(priceText.replace('MT ', '').replace(',', '.'));
                const quantity = parseInt(item.querySelector('.quantity-input').value);
                subtotal += price * quantity;
            });
            
            const deliveryFee = 50;
            const total = subtotal + deliveryFee;
            
            document.getElementById('subtotal').textContent = `MT ${subtotal.toFixed(2).replace('.', ',')}`;
            document.getElementById('total').textContent = `MT ${total.toFixed(2).replace('.', ',')}`;
        }

        // Proceed to checkout
        function proceedToCheckout() {
            <?php if (!$currentUser): ?>
                if (confirm('Você precisa fazer login para finalizar o pedido. Deseja fazer login agora?')) {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                }
            <?php else: ?>
                window.location.href = 'checkout_moz.php';
            <?php endif; ?>
        }

        // Log activity
        <?php if ($currentUser): ?>
        fetch('api/log_activity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'view_cart',
                description: 'Viewed shopping cart',
                additional_data: {
                    items_count: <?= count($cartItems) ?>,
                    total_value: <?= $cartTotal ?>
                }
            })
        }).catch(console.error);
        <?php endif; ?>
    </script>
</body>
</html>

