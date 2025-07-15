<?php
/**
 * Página de perfil do usuário - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

// Verificar se usuário está logado
$currentUser = getCurrentUser();
if (!$currentUser) {
    header('Location: login.php?message=Você precisa fazer login para acessar seu perfil.&type=error');
    exit;
}

$message = '';
$messageType = '';

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    try {
        // Validar token CSRF
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido.');
        }
        
        // Validar dados
        $errors = validateInput($_POST, [
            'nome' => ['required' => true, 'min_length' => 2, 'max_length' => 100],
            'telefone' => ['required' => true, 'mozambican_phone' => true],
            'endereco' => ['max_length' => 255]
        ]);
        
        if (!empty($errors)) {
            throw new Exception('Dados inválidos: ' . implode(', ', $errors));
        }
        
        $nome = sanitize($_POST['nome']);
        $telefone = sanitize($_POST['telefone']);
        $endereco = sanitize($_POST['endereco']);
        
        // Atualizar perfil
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            UPDATE usuarios 
            SET nome = ?, telefone = ?, endereco = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$nome, $telefone, $endereco, $currentUser['id']]);
        
        // Log da atividade
        logUserActivity($currentUser['id'], 'profile_updated', 'User updated profile information');
        
        $message = 'Perfil atualizado com sucesso!';
        $messageType = 'success';
        
        // Atualizar dados do usuário na sessão
        $currentUser['nome'] = $nome;
        $currentUser['telefone'] = $telefone;
        $currentUser['endereco'] = $endereco;
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        logSystemError('Profile update error: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    }
}

// Processar mudança de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    try {
        // Validar token CSRF
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Token de segurança inválido.');
        }
        
        $senhaAtual = $_POST['senha_atual'] ?? '';
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        
        // Validações
        if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
            throw new Exception('Todos os campos de senha são obrigatórios.');
        }
        
        if ($novaSenha !== $confirmarSenha) {
            throw new Exception('A nova senha e a confirmação não coincidem.');
        }
        
        if (strlen($novaSenha) < 6) {
            throw new Exception('A nova senha deve ter pelo menos 6 caracteres.');
        }
        
        // Verificar senha atual
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->execute([$currentUser['id']]);
        $user = $stmt->fetch();
        
        if (!$user || !verifyPassword($senhaAtual, $user['senha'])) {
            throw new Exception('Senha atual incorreta.');
        }
        
        // Atualizar senha
        $novoHash = hashPassword($novaSenha);
        $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$novoHash, $currentUser['id']]);
        
        // Log da atividade
        logUserActivity($currentUser['id'], 'password_changed', 'User changed password');
        
        $message = 'Senha alterada com sucesso!';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
        logSystemError('Password change error: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    }
}

// Buscar histórico de pedidos
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT p.*, COUNT(ip.id) as total_itens
        FROM pedidos p
        LEFT JOIN itens_pedido ip ON p.id = ip.pedido_id
        WHERE p.usuario_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentUser['id']]);
    $pedidos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logSystemError('Error loading user orders: ' . $e->getMessage(), ['user_id' => $currentUser['id']]);
    $pedidos = [];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - MozEntregas</title>
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

        .profile-header {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            color: white;
        }

        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-email {
            color: #666;
            font-size: 1rem;
        }

        /* Tabs */
        .tabs {
            display: flex;
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            background: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
            color: var(--dark-color);
        }

        .tab.active {
            background: var(--primary-color);
            color: white;
        }

        .tab:hover:not(.active) {
            background: var(--light-color);
        }

        /* Tab Content */
        .tab-content {
            display: none;
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .tab-content.active {
            display: block;
        }

        /* Forms */
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

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
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

        /* Orders */
        .orders-list {
            display: grid;
            gap: 1rem;
        }

        .order-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            transition: var(--transition);
        }

        .order-card:hover {
            box-shadow: var(--shadow);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .order-id {
            font-weight: 700;
            color: var(--primary-color);
        }

        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .status-entregue {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelado {
            background: #f8d7da;
            color: #721c24;
        }

        .order-details {
            color: #666;
            font-size: 0.9rem;
        }

        .order-total {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
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

            .profile-header {
                padding: 1.5rem;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-content {
                padding: 1.5rem;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
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
                <a href="pedidos_moz.php" class="nav-link">
                    <i class="fas fa-list"></i> Pedidos
                </a>
                <a href="auth/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            <h1 class="profile-name"><?= htmlspecialchars($currentUser['nome']) ?></h1>
            <p class="profile-email"><?= htmlspecialchars($currentUser['email']) ?></p>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('profile')">
                <i class="fas fa-user"></i> Perfil
            </button>
            <button class="tab" onclick="showTab('password')">
                <i class="fas fa-lock"></i> Senha
            </button>
            <button class="tab" onclick="showTab('orders')">
                <i class="fas fa-shopping-bag"></i> Pedidos
            </button>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> show">
                <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i> 
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content active">
            <h2>Informações do Perfil</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label class="form-label">Nome Completo</label>
                    <input type="text" 
                           name="nome" 
                           class="form-control" 
                           value="<?= htmlspecialchars($currentUser['nome']) ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" 
                           class="form-control" 
                           value="<?= htmlspecialchars($currentUser['email']) ?>" 
                           disabled>
                    <small style="color: #666;">O email não pode ser alterado.</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="tel" 
                           name="telefone" 
                           class="form-control" 
                           value="<?= htmlspecialchars($currentUser['telefone']) ?>" 
                           placeholder="+258841234567"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Endereço</label>
                    <textarea name="endereco" 
                              class="form-control" 
                              rows="3" 
                              placeholder="Seu endereço completo"><?= htmlspecialchars($currentUser['endereco'] ?? '') ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </form>
        </div>

        <!-- Password Tab -->
        <div id="password-tab" class="tab-content">
            <h2>Alterar Senha</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label class="form-label">Senha Atual</label>
                    <input type="password" 
                           name="senha_atual" 
                           class="form-control" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nova Senha</label>
                    <input type="password" 
                           name="nova_senha" 
                           class="form-control" 
                           minlength="6"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar Nova Senha</label>
                    <input type="password" 
                           name="confirmar_senha" 
                           class="form-control" 
                           minlength="6"
                           required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Alterar Senha
                </button>
            </form>
        </div>

        <!-- Orders Tab -->
        <div id="orders-tab" class="tab-content">
            <h2>Meus Pedidos</h2>
            
            <?php if (empty($pedidos)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Nenhum pedido encontrado</h3>
                    <p>Você ainda não fez nenhum pedido.</p>
                    <a href="index_moz.php" class="btn btn-primary">
                        <i class="fas fa-utensils"></i> Fazer Primeiro Pedido
                    </a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($pedidos as $pedido): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Pedido #<?= $pedido['id'] ?></div>
                                    <div class="order-details">
                                        <?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?> • 
                                        <?= $pedido['total_itens'] ?> item(s)
                                    </div>
                                </div>
                                <div>
                                    <div class="order-status status-<?= $pedido['status'] ?>">
                                        <?= ucfirst($pedido['status']) ?>
                                    </div>
                                    <div class="order-total">
                                        MT <?= number_format($pedido['total'], 2, ',', '.') ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-details">
                                <strong>Endereço:</strong> <?= htmlspecialchars($pedido['endereco_entrega']) ?>
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <a href="pedido_detalhes.php?id=<?= $pedido['id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </a>
                                <?php if ($pedido['status'] === 'entregue'): ?>
                                    <a href="api/generate_receipt_pdf.php?pedido_id=<?= $pedido['id'] ?>" 
                                       class="btn btn-primary" 
                                       target="_blank">
                                        <i class="fas fa-download"></i> Baixar Recibo
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }

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

