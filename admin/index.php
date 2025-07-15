<?php
/**
 * Dashboard Administrativo - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login_admin.php?message=Por favor, faça login para acessar o painel administrativo.&type=warning');
    exit;
}

// Verificar se é administrador
if (!isAdmin($currentUser['id'])) {
    header('Location: ../login.php?message=Acesso negado. Apenas administradores podem acessar esta área.&type=error');
    exit;
}

// Buscar estatísticas do dashboard
try {
    $pdo = getConnection();
    
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1");
    $total_usuarios = $stmt->fetchColumn();
    
    // Total de produtos
    $stmt = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1");
    $total_produtos = $stmt->fetchColumn();
    
    // Total de lojas
    $stmt = $pdo->query("SELECT COUNT(*) FROM lojas WHERE ativo = 1");
    $total_lojas = $stmt->fetchColumn();
    
    // Total de categorias
    $stmt = $pdo->query("SELECT COUNT(*) FROM categorias WHERE ativo = 1");
    $total_categorias = $stmt->fetchColumn();
    
    // Pedidos do mês atual
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM pedidos 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $pedidos_mes = $stmt->fetchColumn();
    
    // Vendas do mês atual
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(total), 0) FROM pedidos 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
        AND status != 'cancelado'
    ");
    $vendas_mes = $stmt->fetchColumn();
    
    // Pedidos pendentes
    $stmt = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'pendente'");
    $pedidos_pendentes = $stmt->fetchColumn();
    
    // Últimos pedidos
    $stmt = $pdo->query("
        SELECT p.*, u.nome as usuario_nome, u.email as usuario_email
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $ultimos_pedidos = $stmt->fetchAll();
    
    // Produtos mais vendidos
    $stmt = $pdo->query("
        SELECT pr.nome, pr.imagem, SUM(ip.quantidade) as total_vendido
        FROM produtos pr
        INNER JOIN itens_pedido ip ON pr.id = ip.produto_id
        INNER JOIN pedidos p ON ip.pedido_id = p.id
        WHERE p.status != 'cancelado'
        GROUP BY pr.id
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $produtos_mais_vendidos = $stmt->fetchAll();
    
    // Atividades recentes
    $stmt = $pdo->query("
        SELECT al.*, u.nome as usuario_nome
        FROM activity_logs al
        LEFT JOIN usuarios u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 10
    ");
    $atividades_recentes = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logSystemError('Database error in admin dashboard: ' . $e->getMessage());
    $total_usuarios = $total_produtos = $total_lojas = $total_categorias = 0;
    $pedidos_mes = $vendas_mes = $pedidos_pendentes = 0;
    $ultimos_pedidos = $produtos_mais_vendidos = $atividades_recentes = [];
}

// Log do acesso ao dashboard
logUserActivity($currentUser['id'], 'admin_dashboard_access', 'Accessed admin dashboard');
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MozEntregas Admin</title>
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

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            z-index: 1000;
            transition: var(--transition);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-logo {
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sidebar-user {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .nav-item:hover,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }

        .nav-item i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--dark-color);
        }

        /* Content */
        .content {
            padding: 2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .stat-icon.users {
            background: linear-gradient(135deg, var(--info-color), #138496);
        }

        .stat-icon.products {
            background: linear-gradient(135deg, var(--success-color), #1e7e34);
        }

        .stat-icon.stores {
            background: linear-gradient(135deg, var(--warning-color), #d39e00);
        }

        .stat-icon.orders {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        .stat-icon.sales {
            background: linear-gradient(135deg, var(--secondary-color), #e83e8c);
        }

        .stat-icon.pending {
            background: linear-gradient(135deg, var(--danger-color), #bd2130);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--dark-color);
        }

        .stat-info p {
            color: #666;
            font-weight: 500;
        }

        /* Dashboard Sections */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .dashboard-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Orders List */
        .order-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .order-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .order-info h4 {
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .order-info p {
            font-size: 0.9rem;
            color: #666;
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pendente {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .status-confirmado {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        .status-entregue {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        /* Products List */
        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-info h4 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .product-sales {
            font-size: 0.8rem;
            color: var(--success-color);
            font-weight: 600;
        }

        /* Activity Log */
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--light-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: var(--primary-color);
        }

        .activity-info {
            flex: 1;
        }

        .activity-info h5 {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #666;
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

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                padding: 1rem;
            }

            .content {
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-utensils"></i>
                MozEntregas Admin
            </div>
            <div class="sidebar-user">
                <i class="fas fa-user-shield"></i>
                <?= htmlspecialchars($currentUser['nome']) ?>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="produtos.php" class="nav-item">
                <i class="fas fa-box"></i>
                Produtos
            </a>
            <a href="lojas.php" class="nav-item">
                <i class="fas fa-store"></i>
                Lojas
            </a>
            <a href="categorias.php" class="nav-item">
                <i class="fas fa-tags"></i>
                Categorias
            </a>
            <a href="pedidos.php" class="nav-item">
                <i class="fas fa-shopping-bag"></i>
                Pedidos
            </a>
            <a href="usuarios.php" class="nav-item">
                <i class="fas fa-users"></i>
                Usuários
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="header">
            <h1 class="header-title">Dashboard</h1>
            <div class="header-actions">
                <div class="user-info">
                    <i class="fas fa-clock"></i>
                    <?= date('d/m/Y H:i') ?>
                </div>
                <a href="../auth/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </header>

        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php
            $message = $_GET['message'] ?? '';
            $type = $_GET['type'] ?? '';
            if ($message):
            ?>
                <div class="alert alert-<?= $type ?> show">
                    <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i> 
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_usuarios) ?></h3>
                        <p>Usuários Ativos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_produtos) ?></h3>
                        <p>Produtos</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stores">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($total_lojas) ?></h3>
                        <p>Lojas Ativas</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($pedidos_mes) ?></h3>
                        <p>Pedidos este Mês</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon sales">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>MT <?= number_format($vendas_mes, 2, ',', '.') ?></h3>
                        <p>Vendas este Mês</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= number_format($pedidos_pendentes) ?></h3>
                        <p>Pedidos Pendentes</p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Recent Orders -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-shopping-bag"></i> Últimos Pedidos
                        </h2>
                        <a href="pedidos.php" class="btn btn-primary">Ver Todos</a>
                    </div>
                    
                    <?php if (empty($ultimos_pedidos)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">
                            <i class="fas fa-inbox"></i><br>
                            Nenhum pedido encontrado
                        </p>
                    <?php else: ?>
                        <?php foreach ($ultimos_pedidos as $pedido): ?>
                            <div class="order-item">
                                <div class="order-info">
                                    <h4>Pedido #<?= $pedido['id'] ?></h4>
                                    <p>
                                        <?= htmlspecialchars($pedido['usuario_nome'] ?: 'Cliente Convidado') ?> - 
                                        MT <?= number_format($pedido['total'], 2, ',', '.') ?>
                                    </p>
                                    <p><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></p>
                                </div>
                                <span class="order-status status-<?= $pedido['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $pedido['status'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Top Products -->
                    <div class="dashboard-section" style="margin-bottom: 2rem;">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-star"></i> Mais Vendidos
                            </h2>
                        </div>
                        
                        <?php if (empty($produtos_mais_vendidos)): ?>
                            <p style="text-align: center; color: #666; padding: 1rem;">
                                <i class="fas fa-chart-bar"></i><br>
                                Nenhum dado disponível
                            </p>
                        <?php else: ?>
                            <?php foreach ($produtos_mais_vendidos as $produto): ?>
                                <div class="product-item">
                                    <img src="<?= $produto['imagem'] ? '../uploads/' . $produto['imagem'] : 'https://via.placeholder.com/50x50?text=Produto' ?>" 
                                         alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                         class="product-image">
                                    <div class="product-info">
                                        <h4><?= htmlspecialchars($produto['nome']) ?></h4>
                                        <div class="product-sales"><?= $produto['total_vendido'] ?> vendidos</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h2 class="section-title">
                                <i class="fas fa-history"></i> Atividade Recente
                            </h2>
                        </div>
                        
                        <?php if (empty($atividades_recentes)): ?>
                            <p style="text-align: center; color: #666; padding: 1rem;">
                                <i class="fas fa-list"></i><br>
                                Nenhuma atividade recente
                            </p>
                        <?php else: ?>
                            <?php foreach ($atividades_recentes as $atividade): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-<?= 
                                            strpos($atividade['action'], 'login') !== false ? 'sign-in-alt' : 
                                            (strpos($atividade['action'], 'product') !== false ? 'box' : 
                                            (strpos($atividade['action'], 'order') !== false ? 'shopping-bag' : 'cog'))
                                        ?>"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h5><?= htmlspecialchars($atividade['usuario_nome'] ?: 'Sistema') ?></h5>
                                        <p><?= htmlspecialchars($atividade['description']) ?></p>
                                        <div class="activity-time">
                                            <?= date('d/m/Y H:i', strtotime($atividade['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Auto-refresh stats every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>

