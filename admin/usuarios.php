<?php
require_once '../config_moz.php';

// Verificar se usuário está logado e é admin
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['id'])) {
    header('Location: ../login.php?message=Acesso negado. Apenas administradores podem acessar esta área.&type=error');
    exit;
}

$pdo = getConnection();

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_status':
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Status do usuário alterado com sucesso!";
            break;
            
        case 'delete_user':
            // Verificar se usuário tem pedidos
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE usuario_id = ?");
            $stmt->execute([$user_id]);
            $has_orders = $stmt->fetchColumn() > 0;
            
            if ($has_orders) {
                // Anonimizar dados em vez de deletar
                $stmt = $pdo->prepare("
                    UPDATE usuarios SET 
                        nome = 'Usuário Removido',
                        email = CONCAT('deleted_', id, '@removed.com'),
                        telefone = 'N/A',
                        endereco = 'Endereço removido',
                        ativo = 0,
                        deleted_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$user_id]);
                $message = "Usuário anonimizado com sucesso (tinha pedidos)!";
            } else {
                // Deletar completamente
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id]);
                $message = "Usuário deletado permanentemente!";
            }
            break;
            
        case 'reset_password':
            $new_password = password_hash('123456', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$new_password, $user_id]);
            $message = "Senha resetada para '123456'!";
            break;
    }
}

// Buscar usuários
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(nome LIKE ? OR email LIKE ? OR telefone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== '') {
    $where_conditions[] = "ativo = ?";
    $params[] = intval($status_filter);
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Contar total
$count_sql = "SELECT COUNT(*) FROM usuarios $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Buscar usuários
$sql = "
    SELECT u.*, 
           COUNT(p.id) as total_pedidos,
           COALESCE(SUM(p.total), 0) as total_gasto,
           MAX(p.created_at) as ultimo_pedido,
           COUNT(CASE WHEN p.status = 'entregue' THEN 1 END) as pedidos_entregues
    FROM usuarios u
    LEFT JOIN pedidos p ON u.id = p.usuario_id
    $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Estatísticas gerais
$stats_sql = "
    SELECT 
        COUNT(*) as total_usuarios,
        COUNT(CASE WHEN ativo = 1 THEN 1 END) as usuarios_ativos,
        COUNT(CASE WHEN ativo = 0 THEN 1 END) as usuarios_inativos,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as novos_usuarios_mes
    FROM usuarios
";
$stmt = $pdo->query($stats_sql);
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: var(--white);
            box-shadow: var(--shadow-md);
            padding: 2rem 0;
        }
        
        .admin-main {
            flex: 1;
            padding: 2rem;
            background: var(--gray-50);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-red);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
        }
        
        .filters-section {
            background: var(--white);
            padding: 1.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .filters-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .users-table {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .table-header {
            background: var(--gray-100);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        
        th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-details h4 {
            margin: 0 0 0.25rem 0;
            color: var(--gray-800);
        }
        
        .user-details p {
            margin: 0;
            color: var(--gray-600);
            font-size: 0.8rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-active {
            background: var(--light-green);
            color: var(--primary-green);
        }
        
        .status-inactive {
            background: var(--light-red);
            color: var(--primary-red);
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem;
            font-size: 0.7rem;
            min-width: auto;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            text-decoration: none;
            color: var(--gray-700);
        }
        
        .pagination .current {
            background: var(--primary-red);
            color: var(--white);
            border-color: var(--primary-red);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 2rem;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
            }
            
            .filters-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-content {
                font-size: 0.8rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-nav">
                <h2 style="padding: 0 1.5rem; margin-bottom: 2rem; color: var(--primary-red);">
                    <i class="fas fa-utensils"></i> Admin Panel
                </h2>
                <a href="index.php" class="nav-link">
                    <i class="fas fa-dashboard"></i> Dashboard
                </a>
                <a href="usuarios.php" class="nav-link active">
                    <i class="fas fa-users"></i> Usuários
                </a>
                <a href="lojas.php" class="nav-link">
                    <i class="fas fa-store"></i> Lojas
                </a>
                <a href="categorias.php" class="nav-link">
                    <i class="fas fa-tags"></i> Categorias
                </a>
                <a href="pedidos.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> Pedidos
                </a>
                <a href="motoristas.php" class="nav-link">
                    <i class="fas fa-motorcycle"></i> Motoristas
                </a>
                <a href="entregas.php" class="nav-link">
                    <i class="fas fa-truck"></i> Entregas
                </a>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                <?php if (isset($message)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
            </div>
            
            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_usuarios'] ?></div>
                    <div class="stat-label">Total de Usuários</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['usuarios_ativos'] ?></div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['usuarios_inativos'] ?></div>
                    <div class="stat-label">Usuários Inativos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['novos_usuarios_mes'] ?></div>
                    <div class="stat-label">Novos este Mês</div>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="form-group">
                        <label class="form-label">Buscar</label>
                        <input type="text" name="search" class="form-input" 
                               placeholder="Nome, email ou telefone..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Ativos</option>
                            <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tabela de Usuários -->
            <div class="users-table">
                <div class="table-header">
                    <h3>Lista de Usuários (<?= $total_users ?> encontrados)</h3>
                </div>
                <div class="table-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Contato</th>
                                <th>Estatísticas</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?= htmlspecialchars($usuario['nome']) ?></h4>
                                            <p>ID: <?= $usuario['id'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($usuario['email']) ?></strong><br>
                                        <small><?= htmlspecialchars($usuario['telefone']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?= $usuario['total_pedidos'] ?></strong> pedidos<br>
                                        <small>R$ <?= number_format($usuario['total_gasto'], 2, ',', '.') ?> gastos</small><br>
                                        <small><?= $usuario['pedidos_entregues'] ?> entregues</small>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $usuario['ativo'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div>
                                        <?= date('d/m/Y', strtotime($usuario['created_at'])) ?><br>
                                        <?php if ($usuario['ultimo_pedido']): ?>
                                            <small>Último: <?= date('d/m/Y', strtotime($usuario['ultimo_pedido'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-outline btn-sm" onclick="viewUserDetails(<?= $usuario['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="viewUserActivity(<?= $usuario['id'] ?>)">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $usuario['id'] ?>">
                                            <button type="submit" class="btn <?= $usuario['ativo'] ? 'btn-warning' : 'btn-success' ?> btn-sm">
                                                <i class="fas fa-<?= $usuario['ativo'] ? 'pause' : 'play' ?>"></i>
                                            </button>
                                        </form>
                                        <button class="btn btn-primary btn-sm" onclick="resetPassword(<?= $usuario['id'] ?>)">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nome']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Paginação -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Modal de Detalhes do Usuário -->
    <div id="userDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Usuário</h3>
                <button class="close" onclick="closeModal('userDetailsModal')">&times;</button>
            </div>
            <div id="userDetailsContent">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Modal de Atividades -->
    <div id="userActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Atividades do Usuário</h3>
                <button class="close" onclick="closeModal('userActivityModal')">&times;</button>
            </div>
            <div id="userActivityContent">
                <!-- Conteúdo carregado via AJAX -->
            </div>
        </div>
    </div>
    
    <script>
        function viewUserDetails(userId) {
            fetch(`../php/get_user_details.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userDetailsContent').innerHTML = data.html;
                        document.getElementById('userDetailsModal').style.display = 'block';
                    } else {
                        alert('Erro ao carregar detalhes do usuário');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar detalhes do usuário');
                });
        }
        
        function viewUserActivity(userId) {
            fetch(`../php/get_user_activity.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('userActivityContent').innerHTML = data.html;
                        document.getElementById('userActivityModal').style.display = 'block';
                    } else {
                        alert('Erro ao carregar atividades do usuário');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao carregar atividades do usuário');
                });
        }
        
        function resetPassword(userId) {
            if (confirm('Deseja resetar a senha deste usuário para "123456"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function deleteUser(userId, userName) {
            if (confirm(`Deseja realmente deletar o usuário "${userName}"?\n\nSe o usuário tiver pedidos, os dados serão anonimizados. Caso contrário, será deletado permanentemente.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

