<?php

require_once '../config_moz.php';



// Verificar se usuário está logado e é admin
$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['id'])) {
    header('Location: ../login.php?message=Acesso negado. Apenas administradores podem acessar esta área.&type=error');
    exit;
}


$action = $_GET['action'] ?? 'list';
$loja_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Processar ações
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $nome = sanitize($_POST['nome'] ?? '');
            $localizacao = sanitize($_POST['localizacao'] ?? '');
            $descricao = sanitize($_POST['descricao'] ?? '');
            $telefone = sanitize($_POST['telefone'] ?? '');
            $horario = sanitize($_POST['horario_funcionamento'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            $errors = [];
            if (empty($nome)) $errors[] = 'Nome é obrigatório';
            if (empty($localizacao)) $errors[] = 'Localização é obrigatória';
            
            // Upload de imagem
            $imagem_nome = '';
            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $imagem_nome = uploadImage($_FILES['imagem'], '../uploads/');
                if (!$imagem_nome) {
                    $errors[] = 'Erro no upload da imagem';
                }
            }
            
            if (empty($errors)) {
                if ($action === 'add') {
                    $sql = "INSERT INTO lojas (nome, localizacao, descricao, telefone, horario_funcionamento, ativo";
                    $params = [$nome, $localizacao, $descricao, $telefone, $horario, $ativo];
                    
                    if ($imagem_nome) {
                        $sql .= ", imagem";
                        $params[] = $imagem_nome;
                    }
                    
                    $sql .= ") VALUES (" . str_repeat('?,', count($params) - 1) . "?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $success = 'Loja adicionada com sucesso!';
                } else {
                    $sql = "UPDATE lojas SET nome = ?, localizacao = ?, descricao = ?, telefone = ?, horario_funcionamento = ?, ativo = ?";
                    $params = [$nome, $localizacao, $descricao, $telefone, $horario, $ativo];
                    
                    if ($imagem_nome) {
                        $sql .= ", imagem = ?";
                        $params[] = $imagem_nome;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $loja_id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $success = 'Loja atualizada com sucesso!';
                }
                
                // Redirecionar para evitar resubmissão
                header("Location: lojas.php?success=" . urlencode($success));
                exit;
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
    
    // Deletar loja
    if ($action === 'delete' && $loja_id > 0) {
        $stmt = $pdo->prepare("UPDATE lojas SET ativo = 0 WHERE id = ?");
        $stmt->execute([$loja_id]);
        
        header("Location: lojas.php?success=" . urlencode('Loja desativada com sucesso!'));
        exit;
    }
    
    // Buscar dados para edição
    $loja_edit = null;
    if ($action === 'edit' && $loja_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM lojas WHERE id = ?");
        $stmt->execute([$loja_id]);
        $loja_edit = $stmt->fetch();
        
        if (!$loja_edit) {
            header('Location: lojas.php');
            exit;
        }
    }
    
    // Listar lojas
    $stmt = $pdo->query("
        SELECT l.*, 
               (SELECT COUNT(*) FROM produtos WHERE loja_id = l.id AND ativo = 1) as total_produtos
        FROM lojas l 
        ORDER BY l.ativo DESC, l.nome
    ");
    $lojas = $stmt->fetchAll();
    
    // Verificar mensagem de sucesso
    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }
    
} catch(PDOException $e) {
    $error = "Erro no banco de dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Lojas - Admin FoodDelivery</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header {
            background: #343a40;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .admin-menu {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .admin-menu a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .admin-menu a:hover,
        .admin-menu a.active {
            background: rgba(255,255,255,0.1);
        }
        
        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .form-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-group-full {
            grid-column: 1 / -1;
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                text-align: center;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Header Admin -->
    <header class="admin-header">
        <div class="container">
            <nav class="admin-nav">
                <div class="admin-logo">
                    <i class="fas fa-shield-alt"></i> Admin FoodDelivery
                </div>
                
                <div class="admin-menu">
                    <a href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="categorias.php">
                        <i class="fas fa-th-large"></i> Categorias
                    </a>
                    <a href="lojas.php" class="active">
                        <i class="fas fa-store"></i> Lojas
                    </a>
                    <a href="produtos.php">
                        <i class="fas fa-utensils"></i> Produtos
                    </a>
                    <a href="pedidos.php">
                        <i class="fas fa-shopping-bag"></i> Pedidos
                    </a>
                </div>
                
                <div class="admin-user">
                    <span>
                        <i class="fas fa-user"></i> 
                        <?= htmlspecialchars((string)$_SESSION['admin_nome'] ?? $_SESSION['admin_login']) ?>
                    </span>
                    <a href="logout.php" style="color: #dc3545;">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main style="padding: 0 0 2rem;">
        <div class="container">
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- Lista de Lojas -->
                <div class="page-header">
                    <h1><i class="fas fa-store"></i> Gerenciar Lojas</h1>
                    <a href="lojas.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Loja
                    </a>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Localização</th>
                                <th>Telefone</th>
                                <th>Produtos</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lojas)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 2rem; color: #666;">
                                        Nenhuma loja encontrada.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($lojas as $loja): ?>
                                    <tr>
                                        <td><?= $loja['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($loja['nome']) ?></strong>
                                            <?php if ($loja['imagem']): ?>
                                                <br><small style="color: #666;"><i class="fas fa-image"></i> Com imagem</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars((string)$loja['localizacao']) ?></td>
                                        <td><?= htmlspecialchars((string)$loja['telefone']) ?></td>
                                        <td>
                                            <span class="badge" style="background: #667eea; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                                <?= $loja['total_produtos'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $loja['ativo'] ? 'status-ativo' : 'status-inativo' ?>">
                                                <?= $loja['ativo'] ? 'Ativo' : 'Inativo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="lojas.php?action=edit&id=<?= $loja['id'] ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($loja['ativo']): ?>
                                                    <a href="lojas.php?action=delete&id=<?= $loja['id'] ?>" 
                                                       class="btn btn-danger btn-sm"
                                                       onclick="return confirm('Deseja desativar esta loja?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <!-- Formulário de Adicionar/Editar -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-store"></i> 
                        <?= $action === 'add' ? 'Nova Loja' : 'Editar Loja' ?>
                    </h1>
                    <a href="lojas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="nome">Nome da Loja *</label>
                                <input type="text" id="nome" name="nome" class="form-input" 
                                       value="<?= htmlspecialchars($loja_edit['nome'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" class="form-input" 
                                       value="<?= htmlspecialchars($loja_edit['telefone'] ?? '') ?>"
                                       placeholder="(11) 99999-9999">
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label class="form-label" for="localizacao">Localização *</label>
                                <input type="text" id="localizacao" name="localizacao" class="form-input" 
                                       value="<?= htmlspecialchars($loja_edit['localizacao'] ?? '') ?>" 
                                       placeholder="Endereço completo" required>
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label class="form-label" for="descricao">Descrição</label>
                                <textarea id="descricao" name="descricao" class="form-textarea" 
                                          placeholder="Descrição da loja..."><?= htmlspecialchars($loja_edit['descricao'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="horario_funcionamento">Horário de Funcionamento</label>
                                <input type="text" id="horario_funcionamento" name="horario_funcionamento" class="form-input" 
                                       value="<?= htmlspecialchars($loja_edit['horario_funcionamento'] ?? '') ?>"
                                       placeholder="Ex: 18:00 - 23:00">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="imagem">Imagem da Loja</label>
                                <input type="file" id="imagem" name="imagem" class="form-input" 
                                       accept="image/*" onchange="previewImage(this)">
                                <?php if ($loja_edit && $loja_edit['imagem']): ?>
                                    <img src="../uploads/<?= $loja_edit['imagem'] ?>" 
                                         alt="Imagem atual" class="image-preview" id="imagePreview">
                                <?php else: ?>
                                    <img id="imagePreview" class="image-preview" style="display: none;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="ativo" 
                                           <?= (!$loja_edit || $loja_edit['ativo']) ? 'checked' : '' ?>>
                                    <span>Loja ativa</span>
                                </label>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; text-align: center;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> 
                                <?= $action === 'add' ? 'Adicionar Loja' : 'Salvar Alterações' ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>

