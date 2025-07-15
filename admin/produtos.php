<?php
/**
 * Gestão de Produtos - MozEntregas Admin
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
        
        if ($action === 'add_product') {
            // Adicionar produto
            $nome = sanitize($_POST['nome']);
            $descricao = sanitize($_POST['descricao']);
            $preco = (float)$_POST['preco'];
            $categoria_id = (int)$_POST['categoria_id'];
            $loja_id = (int)$_POST['loja_id'];
            
            // Validações
            if (empty($nome) || empty($descricao) || $preco <= 0 || $categoria_id <= 0 || $loja_id <= 0) {
                throw new Exception('Todos os campos são obrigatórios e devem ser válidos.');
            }
            
            $pdo = getConnection();
            
            // Upload de imagem
            $imagem = null;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $imagem = uploadProductImage($_FILES['imagem']);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO produtos (nome, descricao, preco, categoria_id, loja_id, imagem, ativo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$nome, $descricao, $preco, $categoria_id, $loja_id, $imagem]);
            
            logUserActivity($currentUser['id'], 'product_created', 'Created new product: ' . $nome);
            $message = 'Produto adicionado com sucesso!';
            $messageType = 'success';
            
        } elseif ($action === 'edit_product') {
            // Editar produto
            $id = (int)$_POST['id'];
            $nome = sanitize($_POST['nome']);
            $descricao = sanitize($_POST['descricao']);
            $preco = (float)$_POST['preco'];
            $categoria_id = (int)$_POST['categoria_id'];
            $loja_id = (int)$_POST['loja_id'];
            
            if ($id <= 0 || empty($nome) || empty($descricao) || $preco <= 0) {
                throw new Exception('Dados inválidos.');
            }
            
            $pdo = getConnection();
            
            // Upload de nova imagem se fornecida
            $imagem = null;
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $imagem = uploadProductImage($_FILES['imagem']);
                
                $stmt = $pdo->prepare("
                    UPDATE produtos 
                    SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, loja_id = ?, imagem = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $descricao, $preco, $categoria_id, $loja_id, $imagem, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE produtos 
                    SET nome = ?, descricao = ?, preco = ?, categoria_id = ?, loja_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $descricao, $preco, $categoria_id, $loja_id, $id]);
            }
            
            logUserActivity($currentUser['id'], 'product_updated', 'Updated product: ' . $nome);
            $message = 'Produto atualizado com sucesso!';
            $messageType = 'success';
            
        } elseif ($action === 'toggle_status') {
            // Ativar/desativar produto
            $id = (int)$_POST['id'];
            
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("UPDATE produtos SET ativo = NOT ativo, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            
            logUserActivity($currentUser['id'], 'product_status_toggled', 'Toggled product status for ID: ' . $id);
            $message = 'Status do produto alterado com sucesso!';
            $messageType = 'success';
            
        } elseif ($action === 'delete_product') {
            // Excluir produto
            $id = (int)$_POST['id'];
            
            if ($id <= 0) {
                throw new Exception('ID inválido.');
            }
            
            $pdo = getConnection();
            $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmt->execute([$id]);
            
            logUserActivity($currentUser['id'], 'product_deleted', 'Deleted product ID: ' . $id);
            $message = 'Produto excluído com sucesso!';
            $messageType = 'success';
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        logSystemError('Product management error: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    }
}

// Buscar produtos
try {
    $pdo = getConnection();
    
    // Filtros
    $search = $_GET['search'] ?? '';
    $categoria_filter = $_GET['categoria'] ?? '';
    $loja_filter = $_GET['loja'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "p.nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria_filter)) {
        $where_conditions[] = "p.categoria_id = ?";
        $params[] = $categoria_filter;
    }
    
    if (!empty($loja_filter)) {
        $where_conditions[] = "p.loja_id = ?";
        $params[] = $loja_filter;
    }
    
    if ($status_filter !== '') {
        $where_conditions[] = "p.ativo = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome as categoria_nome, l.nome as loja_nome
        FROM produtos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        INNER JOIN lojas l ON p.loja_id = l.id
        $where_clause
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();
    
    // Buscar categorias para filtros
    $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 ORDER BY nome");
    $categorias = $stmt->fetchAll();
    
    // Buscar lojas para filtros
    $stmt = $pdo->query("SELECT * FROM lojas WHERE ativo = 1 ORDER BY nome");
    $lojas = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logSystemError('Database error in products page: ' . $e->getMessage());
    $produtos = [];
    $categorias = [];
    $lojas = [];
}

/**
 * Upload de imagem de produto
 */
function uploadProductImage($file) {
    $uploadDir = '../uploads/';
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Validar tipo de arquivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de arquivo não permitido. Use JPEG, PNG, GIF ou WebP.');
    }
    
    // Validar tamanho (máximo 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Arquivo muito grande. Máximo 5MB.');
    }
    
    // Gerar nome único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'produto_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Mover arquivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erro ao fazer upload da imagem.');
    }
    
    return $filename;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Produtos - MozEntregas Admin</title>
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

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
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

        .btn-success:hover {
            background: #218838;
        }

        .btn-warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        /* Products Grid */
        .products-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
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

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-info {
            max-width: 200px;
        }

        .product-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .product-description {
            font-size: 0.9rem;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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

        .badge-danger {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
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
            z-index: 2000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
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

        .modal-close:hover {
            color: var(--danger-color);
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

            .table-container {
                font-size: 0.9rem;
            }

            .modal-content {
                padding: 1rem;
                width: 95%;
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
            <a href="produtos.php" class="nav-item active">
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
            <h1 class="header-title">Gestão de Produtos</h1>
            <div class="header-actions">
                <button onclick="openAddModal()" class="btn btn-success">
                    <i class="fas fa-plus"></i> Novo Produto
                </button>
                <a href="../auth/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
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
                                   placeholder="Nome do produto..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Categoria</label>
                            <select name="categoria" class="form-control">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id'] ?>" <?= $categoria_filter == $categoria['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Loja</label>
                            <select name="loja" class="form-control">
                                <option value="">Todas as lojas</option>
                                <?php foreach ($lojas as $loja): ?>
                                    <option value="<?= $loja['id'] ?>" <?= $loja_filter == $loja['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loja['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">Todos</option>
                                <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Ativo</option>
                                <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Products Table -->
            <div class="products-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-box"></i> Produtos (<?= count($produtos) ?>)
                    </h2>
                </div>
                
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Imagem</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Loja</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td>
                                        <img src="<?= $produto['imagem'] ? '../uploads/' . $produto['imagem'] : 'https://via.placeholder.com/60x60?text=Produto' ?>" 
                                             alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                             class="product-image">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                                            <div class="product-description"><?= htmlspecialchars($produto['descricao']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($produto['categoria_nome']) ?></td>
                                    <td><?= htmlspecialchars($produto['loja_nome']) ?></td>
                                    <td>MT <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-<?= $produto['ativo'] ? 'success' : 'danger' ?>">
                                            <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="editProduct(<?= htmlspecialchars(json_encode($produto)) ?>)" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleStatus(<?= $produto['id'] ?>)" 
                                                class="btn btn-warning btn-sm">
                                            <i class="fas fa-toggle-<?= $produto['ativo'] ? 'on' : 'off' ?>"></i>
                                        </button>
                                        <button onclick="deleteProduct(<?= $produto['id'] ?>, '<?= htmlspecialchars($produto['nome']) ?>')" 
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
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

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Novo Produto</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="action" id="formAction" value="add_product">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-group">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" name="nome" id="productName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea name="descricao" id="productDescription" class="form-control" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preço (MT)</label>
                    <input type="number" name="preco" id="productPrice" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="productCategory" class="form-control" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Loja</label>
                    <select name="loja_id" id="productStore" class="form-control" required>
                        <option value="">Selecione uma loja</option>
                        <?php foreach ($lojas as $loja): ?>
                            <option value="<?= $loja['id'] ?>"><?= htmlspecialchars($loja['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Imagem</label>
                    <input type="file" name="imagem" id="productImage" class="form-control" accept="image/*">
                    <small style="color: #666;">Formatos aceitos: JPEG, PNG, GIF, WebP. Máximo 5MB.</small>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hidden Forms -->
    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="id" id="toggleId">
    </form>

    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="action" value="delete_product">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Novo Produto';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').classList.add('show');
        }

        function editProduct(product) {
            document.getElementById('modalTitle').textContent = 'Editar Produto';
            document.getElementById('formAction').value = 'edit_product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.nome;
            document.getElementById('productDescription').value = product.descricao;
            document.getElementById('productPrice').value = product.preco;
            document.getElementById('productCategory').value = product.categoria_id;
            document.getElementById('productStore').value = product.loja_id;
            document.getElementById('productModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        function toggleStatus(id) {
            if (confirm('Tem certeza que deseja alterar o status deste produto?')) {
                document.getElementById('toggleId').value = id;
                document.getElementById('toggleForm').submit();
            }
        }

        function deleteProduct(id, name) {
            if (confirm(`Tem certeza que deseja excluir o produto "${name}"? Esta ação não pode ser desfeita.`)) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
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
    </script>
</body>
</html>

