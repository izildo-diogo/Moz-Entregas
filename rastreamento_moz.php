<?php
/**
 * Página de rastreamento de pedidos - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

$pedido = null;
$error = '';
$pedidoId = isset($_GET['pedido']) ? (int)$_GET['pedido'] : 0;

if ($pedidoId > 0) {
    try {
        $pdo = getConnection();
        
        // Buscar dados do pedido
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome as usuario_nome, u.email as usuario_email
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch();
        
        if ($pedido) {
            // Buscar itens do pedido
            $stmt = $pdo->prepare("
                SELECT ip.*, pr.nome as produto_nome, pr.imagem as produto_imagem,
                       l.nome as loja_nome
                FROM itens_pedido ip
                INNER JOIN produtos pr ON ip.produto_id = pr.id
                INNER JOIN lojas l ON pr.loja_id = l.id
                WHERE ip.pedido_id = ?
                ORDER BY l.nome, pr.nome
            ");
            $stmt->execute([$pedidoId]);
            $itens = $stmt->fetchAll();
            
            // Buscar histórico de status
            $stmt = $pdo->prepare("
                SELECT * FROM pedido_status_history 
                WHERE pedido_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt->execute([$pedidoId]);
            $statusHistory = $stmt->fetchAll();
        } else {
            $error = 'Pedido não encontrado.';
        }
        
    } catch (PDOException $e) {
        logSystemError('Error loading order tracking: ' . $e->getMessage());
        $error = 'Erro ao carregar dados do pedido.';
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastreamento de Pedido - MozEntregas</title>
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
            max-width: 1000px;
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

        /* Search Form */
        .search-form {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            max-width: 300px;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Order Info */
        .order-info {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .order-id {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmado {
            background: #d4edda;
            color: #155724;
        }

        .status-preparando {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-saiu_entrega {
            background: #cce5ff;
            color: #004085;
        }

        .status-entregue {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelado {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            padding: 1rem;
            background: var(--light-color);
            border-radius: 8px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-size: 1rem;
            color: var(--dark-color);
        }

        /* Status Timeline */
        .status-timeline {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .timeline-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 0.75rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            padding-left: 2rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -0.5rem;
            top: 0.25rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background: var(--border-color);
            border: 3px solid white;
            box-shadow: var(--shadow);
        }

        .timeline-item.active::before {
            background: var(--success-color);
        }

        .timeline-item.current::before {
            background: var(--primary-color);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(102, 126, 234, 0); }
            100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
        }

        .timeline-content {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
        }

        .timeline-status {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            color: #666;
            font-size: 0.9rem;
        }

        /* Order Items */
        .order-items {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .items-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .item-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .item-store {
            color: #666;
            font-size: 0.9rem;
        }

        .item-quantity {
            font-weight: 600;
            color: var(--primary-color);
        }

        .item-price {
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Total */
        .order-total {
            text-align: right;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .total-amount {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Error */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 2rem;
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

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .item-card {
                flex-direction: column;
                text-align: center;
            }

            .timeline {
                padding-left: 1.5rem;
            }

            .timeline-item {
                padding-left: 1.5rem;
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
                <?php if (isUserLoggedIn()): ?>
                    <a href="perfil_moz.php" class="nav-link">
                        <i class="fas fa-user"></i> Perfil
                    </a>
                    <a href="auth/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-search"></i> Rastreamento de Pedido</h1>
            <p>Acompanhe o status do seu pedido em tempo real</p>
        </div>

        <!-- Search Form -->
        <?php if (!$pedido && !$error): ?>
            <div class="search-form">
                <form method="GET" action="">
                    <div class="form-group">
                        <label class="form-label">Número do Pedido</label>
                        <input type="number" 
                               name="pedido" 
                               class="form-control" 
                               placeholder="Digite o número do pedido"
                               value="<?= $pedidoId ?>"
                               required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Rastrear Pedido
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <div class="search-form">
                <a href="rastreamento_moz.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tentar Novamente
                </a>
            </div>
        <?php endif; ?>

        <!-- Order Information -->
        <?php if ($pedido): ?>
            <!-- Order Header -->
            <div class="order-info">
                <div class="order-header">
                    <div class="order-id">Pedido #<?= $pedido['id'] ?></div>
                    <div class="order-status status-<?= $pedido['status'] ?>">
                        <?= ucfirst(str_replace('_', ' ', $pedido['status'])) ?>
                    </div>
                </div>

                <div class="order-details">
                    <div class="detail-item">
                        <div class="detail-label">Data do Pedido</div>
                        <div class="detail-value"><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Cliente</div>
                        <div class="detail-value"><?= htmlspecialchars($pedido['usuario_nome'] ?: 'Cliente Convidado') ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Endereço de Entrega</div>
                        <div class="detail-value"><?= htmlspecialchars($pedido['endereco_entrega']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Método de Pagamento</div>
                        <div class="detail-value"><?= ucfirst($pedido['metodo_pagamento']) ?></div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-label">Total</div>
                        <div class="detail-value total-amount">MT <?= number_format($pedido['total'], 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="status-timeline">
                <h2 class="timeline-title"><i class="fas fa-clock"></i> Histórico do Pedido</h2>
                
                <div class="timeline">
                    <?php
                    $statusOrder = ['pendente', 'confirmado', 'preparando', 'saiu_entrega', 'entregue'];
                    $currentStatusIndex = array_search($pedido['status'], $statusOrder);
                    
                    $statusLabels = [
                        'pendente' => 'Pedido Recebido',
                        'confirmado' => 'Pedido Confirmado',
                        'preparando' => 'Preparando Pedido',
                        'saiu_entrega' => 'Saiu para Entrega',
                        'entregue' => 'Pedido Entregue'
                    ];
                    
                    foreach ($statusOrder as $index => $status):
                        $isActive = $index <= $currentStatusIndex;
                        $isCurrent = $index === $currentStatusIndex;
                        $class = $isActive ? 'active' : '';
                        $class .= $isCurrent ? ' current' : '';
                    ?>
                        <div class="timeline-item <?= $class ?>">
                            <div class="timeline-content">
                                <div class="timeline-status"><?= $statusLabels[$status] ?></div>
                                <?php if ($isActive): ?>
                                    <div class="timeline-time">
                                        <?php if ($isCurrent): ?>
                                            Status atual
                                        <?php else: ?>
                                            Concluído
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($pedido['status'] === 'cancelado'): ?>
                        <div class="timeline-item active current">
                            <div class="timeline-content">
                                <div class="timeline-status" style="color: var(--danger-color);">Pedido Cancelado</div>
                                <div class="timeline-time">Status atual</div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Items -->
            <div class="order-items">
                <h2 class="items-title"><i class="fas fa-list"></i> Itens do Pedido</h2>
                
                <?php foreach ($itens as $item): ?>
                    <div class="item-card">
                        <img src="<?= $item['produto_imagem'] ? 'uploads/' . $item['produto_imagem'] : 'https://via.placeholder.com/60x60?text=Produto' ?>" 
                             alt="<?= htmlspecialchars($item['produto_nome']) ?>" 
                             class="item-image">
                        
                        <div class="item-details">
                            <div class="item-name"><?= htmlspecialchars($item['produto_nome']) ?></div>
                            <div class="item-store"><?= htmlspecialchars($item['loja_nome']) ?></div>
                        </div>
                        
                        <div class="item-quantity"><?= $item['quantidade'] ?>x</div>
                        
                        <div class="item-price">
                            MT <?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-total">
                    <div>Subtotal: MT <?= number_format($pedido['total'] - $pedido['taxa_entrega'], 2, ',', '.') ?></div>
                    <div>Taxa de Entrega: MT <?= number_format($pedido['taxa_entrega'], 2, ',', '.') ?></div>
                    <?php if ($pedido['desconto'] > 0): ?>
                        <div>Desconto: -MT <?= number_format($pedido['desconto'], 2, ',', '.') ?></div>
                    <?php endif; ?>
                    <div class="total-amount">Total: MT <?= number_format($pedido['total'], 2, ',', '.') ?></div>
                </div>
            </div>

            <!-- Actions -->
            <div style="text-align: center; margin-top: 2rem;">
                <a href="rastreamento_moz.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Rastrear Outro Pedido
                </a>
                
                <?php if ($pedido['status'] === 'entregue'): ?>
                    <a href="api/generate_receipt_pdf.php?pedido_id=<?= $pedido['id'] ?>" 
                       class="btn btn-primary" 
                       target="_blank">
                        <i class="fas fa-download"></i> Baixar Recibo
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh page every 30 seconds if order is not delivered or cancelled
        <?php if ($pedido && !in_array($pedido['status'], ['entregue', 'cancelado'])): ?>
            setTimeout(() => {
                location.reload();
            }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>

