<?php
/**
 * Gestão de Pedidos - MozEntregas Admin
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Verificar se usuário está logado e é admin
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['id'])) {
    header('Location: ../login.php?message=Acesso negado. Apenas administradores podem acessar esta área.&type=error');
    exit;
}

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar token CSRF
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido.');
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            // Atualizar status do pedido
            $pedido_id = (int)$_POST['pedido_id'];
            $novo_status = sanitize($_POST['status']);
            
            $status_validos = ['pendente', 'confirmado', 'preparando', 'saiu_entrega', 'entregue', 'cancelado'];
            
            if ($pedido_id <= 0 || !in_array($novo_status, $status_validos)) {
                throw new Exception('Dados inválidos.');
            }
            
            $pdo = getConnection();
            
            // Atualizar status
            $stmt = $pdo->prepare("UPDATE pedidos SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$novo_status, $pedido_id]);
            
            // Registrar no histórico
            $stmt = $pdo->prepare("
                INSERT INTO pedido_status_history (pedido_id, status, changed_by, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$pedido_id, $novo_status, $currentUser['id']]);
            
            logUserActivity($currentUser['id'], 'order_status_updated', "Updated order #$pedido_id status to $novo_status");
            $message = 'Status do pedido atualizado com sucesso!';
            $messageType = 'success';
            
        } elseif ($action === 'add_note') {
            // Adicionar nota ao pedido
            $pedido_id = (int)$_POST['pedido_id'];
            $nota = sanitize($_POST['nota']);
            
            if ($pedido_id <= 0 || empty($nota)) {
                throw new Exception('Dados inválidos.');
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO pedido_notas (pedido_id, admin_id, nota, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$pedido_id, $currentUser['id'], $nota]);
            
            logUserActivity($currentUser['id'], 'order_note_added', "Added note to order #$pedido_id");
            $message = 'Nota adicionada com sucesso!';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        logSystemError('Order management error: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    }
}

// Buscar pedidos
try {
    $pdo = getConnection();
    
    // Filtros
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $data_inicio = $_GET['data_inicio'] ?? '';
    $data_fim = $_GET['data_fim'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(p.id LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "p.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($data_inicio)) {
        $where_conditions[] = "DATE(p.created_at) >= ?";
        $params[] = $data_inicio;
    }
    
    if (!empty($data_fim)) {
        $where_conditions[] = "DATE(p.created_at) <= ?";
        $params[] = $data_fim;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.nome as usuario_nome, u.email as usuario_email, u.telefone as usuario_telefone,
               COUNT(ip.id) as total_itens
        FROM pedidos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
        $where_clause
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logSystemError('Database error in orders page: ' . $e->getMessage());
    $pedidos = [];
}

// Buscar detalhes de um pedido específico se solicitado
$pedido_detalhes = null;
$itens_pedido = [];
$notas_pedido = [];

if (isset($_GET['detalhes']) && is_numeric($_GET['detalhes'])) {
    $pedido_id = (int)$_GET['detalhes'];
    
    try {
        // Buscar dados do pedido
        $stmt = $pdo->prepare("
            SELECT p.*, u.nome as usuario_nome, u.email as usuario_email, u.telefone as usuario_telefone
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$pedido_id]);
        $pedido_detalhes = $stmt->fetch();
        
        if ($pedido_detalhes) {
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
            $stmt->execute([$pedido_id]);
            $itens_pedido = $stmt->fetchAll();
            
            // Buscar notas do pedido
            $stmt = $pdo->prepare("
                SELECT pn.*, u.nome as admin_nome
                FROM pedido_notas pn
                INNER JOIN usuarios u ON pn.admin_id = u.id
                WHERE pn.pedido_id = ?
                ORDER BY pn.created_at DESC
            ");
            $stmt->execute([$pedido_id]);
            $notas_pedido = $stmt->fetchAll();
        }
        
    } catch (PDOException $e) {
        logSystemError('Error loading order details: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pedidos - MozEntregas Admin</title>
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

        /* Content */
        .content {
            padding: 2rem;
        }

        /* Filters */
        .filters {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
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
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Orders Section */
        .orders-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.1);
            color: var(--info-color);
        }

        /* Order Details Modal */
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
            z-index: 2000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--dark-color);
        }

        .items-list {
            margin-bottom: 2rem;
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

        .notes-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .note-item {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .note-author {
            font-weight: 600;
            color: var(--primary-color);
        }

        .note-date {
            font-size: 0.8rem;
            color: #666;
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

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                padding: 1rem;
                width: 95%;
            }

            .order-info-grid {
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
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item">
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
            <a href="pedidos.php" class="nav-item active">
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
            <h1 class="header-title">Gestão de Pedidos</h1>
            <a href="../auth/logout.php" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </header>

        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> show">
                    <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i> 
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label class="form-label">Buscar</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="ID, cliente, email..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos os status</option>
                                <option value="pendente" <?= $status_filter === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                <option value="confirmado" <?= $status_filter === 'confirmado' ? 'selected' : '' ?>>Confirmado</option>
                                <option value="preparando" <?= $status_filter === 'preparando' ? 'selected' : '' ?>>Preparando</option>
                                <option value="saiu_entrega" <?= $status_filter === 'saiu_entrega' ? 'selected' : '' ?>>Saiu para Entrega</option>
                                <option value="entregue" <?= $status_filter === 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                <option value="cancelado" <?= $status_filter === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Data Início</label>
                            <input type="date" 
                                   name="data_inicio" 
                                   class="form-control"
                                   value="<?= htmlspecialchars($data_inicio) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Data Fim</label>
                            <input type="date" 
                                   name="data_fim" 
                                   class="form-control"
                                   value="<?= htmlspecialchars($data_fim) ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="orders-section">
                <h2 class="section-title">
                    <i class="fas fa-shopping-bag"></i> Pedidos (<?= count($pedidos) ?>)
                </h2>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Itens</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td>#<?= $pedido['id'] ?></td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($pedido['usuario_nome'] ?: 'Cliente Convidado') ?></strong>
                                            <?php if ($pedido['usuario_email']): ?>
                                                <br><small><?= htmlspecialchars($pedido['usuario_email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?= $pedido['total_itens'] ?> item(s)</td>
                                    <td>MT <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $pedido['status'] === 'entregue' ? 'success' : 
                                            ($pedido['status'] === 'cancelado' ? 'danger' : 
                                            ($pedido['status'] === 'pendente' ? 'warning' : 'info')) 
                                        ?>">
                                            <?= ucfirst(str_replace('_', ' ', $pedido['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></td>
                                    <td>
                                        <button onclick="viewOrder(<?= $pedido['id'] ?>)" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="updateStatus(<?= $pedido['id'] ?>, '<?= $pedido['status'] ?>')" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="orderModalTitle">Detalhes do Pedido</h3>
                <button class="modal-close" onclick="closeOrderModal()">&times;</button>
            </div>
            <div id="orderModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Atualizar Status</h3>
                <button class="modal-close" onclick="closeStatusModal()">&times;</button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="pedido_id" id="statusPedidoId">
                
                <div class="form-group">
                    <label class="form-label">Novo Status</label>
                    <select name="status" id="statusSelect" class="form-control" required>
                        <option value="pendente">Pendente</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="preparando">Preparando</option>
                        <option value="saiu_entrega">Saiu para Entrega</option>
                        <option value="entregue">Entregue</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Atualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // View order details
        function viewOrder(orderId) {
            window.location.href = `pedidos.php?detalhes=${orderId}`;
        }

        // Update status modal
        function updateStatus(orderId, currentStatus) {
            document.getElementById('statusPedidoId').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.add('show');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.remove('show');
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('show');
        }

        // Close modals when clicking outside
        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Show order details if requested
        <?php if ($pedido_detalhes): ?>
            document.getElementById('orderModalTitle').textContent = 'Pedido #<?= $pedido_detalhes['id'] ?>';
            document.getElementById('orderModalContent').innerHTML = `
                <div class="order-info-grid">
                    <div class="info-card">
                        <div class="info-label">Cliente</div>
                        <div class="info-value"><?= htmlspecialchars($pedido_detalhes['usuario_nome'] ?: 'Cliente Convidado') ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($pedido_detalhes['usuario_email'] ?: 'N/A') ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Telefone</div>
                        <div class="info-value"><?= htmlspecialchars($pedido_detalhes['usuario_telefone'] ?: 'N/A') ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge badge-<?= 
                                $pedido_detalhes['status'] === 'entregue' ? 'success' : 
                                ($pedido_detalhes['status'] === 'cancelado' ? 'danger' : 
                                ($pedido_detalhes['status'] === 'pendente' ? 'warning' : 'info')) 
                            ?>">
                                <?= ucfirst(str_replace('_', ' ', $pedido_detalhes['status'])) ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Data do Pedido</div>
                        <div class="info-value"><?= date('d/m/Y H:i', strtotime($pedido_detalhes['created_at'])) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Total</div>
                        <div class="info-value">MT <?= number_format($pedido_detalhes['total'], 2, ',', '.') ?></div>
                    </div>
                </div>

                <div class="info-card" style="margin-bottom: 2rem;">
                    <div class="info-label">Endereço de Entrega</div>
                    <div class="info-value"><?= htmlspecialchars($pedido_detalhes['endereco_entrega']) ?></div>
                </div>

                <h4 style="margin-bottom: 1rem; color: var(--primary-color);">
                    <i class="fas fa-list"></i> Itens do Pedido
                </h4>
                <div class="items-list">
                    <?php foreach ($itens_pedido as $item): ?>
                        <div class="item-card">
                            <img src="<?= $item['produto_imagem'] ? '../uploads/' . $item['produto_imagem'] : 'https://via.placeholder.com/60x60?text=Produto' ?>" 
                                 alt="<?= htmlspecialchars($item['produto_nome']) ?>" 
                                 class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?= htmlspecialchars($item['produto_nome']) ?></div>
                                <div class="item-store"><?= htmlspecialchars($item['loja_nome']) ?></div>
                            </div>
                            <div style="text-align: right;">
                                <div><?= $item['quantidade'] ?>x MT <?= number_format($item['preco'], 2, ',', '.') ?></div>
                                <div style="font-weight: 600;">MT <?= number_format($item['preco'] * $item['quantidade'], 2, ',', '.') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="text-align: right; padding: 1rem; background: var(--light-color); border-radius: 8px; margin-bottom: 2rem;">
                    <div>Subtotal: MT <?= number_format($pedido_detalhes['total'] - $pedido_detalhes['taxa_entrega'], 2, ',', '.') ?></div>
                    <div>Taxa de Entrega: MT <?= number_format($pedido_detalhes['taxa_entrega'], 2, ',', '.') ?></div>
                    <?php if ($pedido_detalhes['desconto'] > 0): ?>
                        <div>Desconto: -MT <?= number_format($pedido_detalhes['desconto'], 2, ',', '.') ?></div>
                    <?php endif; ?>
                    <div style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                        Total: MT <?= number_format($pedido_detalhes['total'], 2, ',', '.') ?>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button onclick="updateStatus(<?= $pedido_detalhes['id'] ?>, '<?= $pedido_detalhes['status'] ?>')" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Atualizar Status
                    </button>
                    <button onclick="addNote(<?= $pedido_detalhes['id'] ?>)" class="btn btn-primary">
                        <i class="fas fa-comment"></i> Adicionar Nota
                    </button>
                </div>
            `;
            document.getElementById('orderModal').classList.add('show');
        <?php endif; ?>

        function addNote(orderId) {
            const nota = prompt('Digite a nota para este pedido:');
            if (nota && nota.trim()) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_note">
                    <input type="hidden" name="pedido_id" value="${orderId}">
                    <input type="hidden" name="nota" value="${nota.trim()}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

