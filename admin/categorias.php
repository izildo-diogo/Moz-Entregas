<?php
require_once '../config_moz.php';

// Verificar se está logado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$categoria_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Processar ações
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $nome = sanitize($_POST['nome'] ?? '');
            $descricao = sanitize($_POST['descricao'] ?? '');
            $icone = sanitize($_POST['icone'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            
            $errors = [];
            if (empty($nome)) $errors[] = 'Nome é obrigatório';
            
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
                    $sql = "INSERT INTO categorias (nome, descricao, icone, ativo";
                    $params = [$nome, $descricao, $icone, $ativo];
                    
                    if ($imagem_nome) {
                        $sql .= ", imagem";
                        $params[] = $imagem_nome;
                    }
                    
                    $sql .= ") VALUES (" . str_repeat('?,', count($params) - 1) . "?)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $success = 'Categoria adicionada com sucesso!';
                } else {
                    $sql = "UPDATE categorias SET nome = ?, descricao = ?, icone = ?, ativo = ?";
                    $params = [$nome, $descricao, $icone, $ativo];
                    
                    if ($imagem_nome) {
                        $sql .= ", imagem = ?";
                        $params[] = $imagem_nome;
                    }
                    
                    $sql .= " WHERE id = ?";
                    $params[] = $categoria_id;
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $success = 'Categoria atualizada com sucesso!';
                }
                
                // Redirecionar para evitar resubmissão
                header("Location: categorias.php?success=" . urlencode($success));
                exit;
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
    
    // Deletar categoria
    if ($action === 'delete' && $categoria_id > 0) {
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = 0 WHERE id = ?");
        $stmt->execute([$categoria_id]);
        
        header("Location: categorias.php?success=" . urlencode('Categoria desativada com sucesso!'));
        exit;
    }
    
    // Buscar dados para edição
    $categoria_edit = null;
    if ($action === 'edit' && $categoria_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = ?");
        $stmt->execute([$categoria_id]);
        $categoria_edit = $stmt->fetch();
        
        if (!$categoria_edit) {
            header('Location: categorias.php');
            exit;
        }
    }
    
    // Listar categorias
    $stmt = $pdo->query("
        SELECT c.*, 
               (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id AND ativo = 1) as total_produtos
        FROM categorias c 
        ORDER BY c.ativo DESC, c.nome
    ");
    $categorias = $stmt->fetchAll();
    
    // Verificar mensagem de sucesso
    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }
    
} catch(PDOException $e) {
    $error = "Erro no banco de dados: " . $e->getMessage();
}

// Lista de ícones disponíveis
$icones_disponiveis = [
    'fas fa-pizza-slice' => 'Pizza',
    'fas fa-hamburger' => 'Hambúrguer',
    'fas fa-fish' => 'Peixe/Japonesa',
    'fas fa-cheese' => 'Queijo/Italiana',
    'fas fa-drumstick-bite' => 'Frango/Brasileira',
    'fas fa-ice-cream' => 'Sorvete/Sobremesas',
    'fas fa-glass-cheers' => 'Bebidas',
    'fas fa-cookie-bite' => 'Lanches',
    'fas fa-utensils' => 'Utensílios',
    'fas fa-coffee' => 'Café',
    'fas fa-wine-glass' => 'Vinho',
    'fas fa-birthday-cake' => 'Bolo',
    'fas fa-apple-alt' => 'Frutas',
    'fas fa-carrot' => 'Vegetais',
    'fas fa-bread-slice' => 'Pães',
    'fas fa-hotdog' => 'Hot Dog'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Categorias - Admin FoodDelivery</title>
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
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .category-admin-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        
        .category-admin-card.inactive {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        .category-icon-large {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .category-admin-card h3 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .category-admin-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .category-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.8rem;
            color: #999;
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .icon-selector {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 4px;
        }
        
        .icon-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .icon-option:hover,
        .icon-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .icon-option i {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
        
        .icon-option span {
            font-size: 0.7rem;
            text-align: center;
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
            
            .categories-grid {
                grid-template-columns: 1fr;
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
                    <a href="categorias.php" class="active">
                        <i class="fas fa-th-large"></i> Categorias
                    </a>
                    <a href="lojas.php">
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
                        <?= htmlspecialchars($_SESSION['admin_nome'] ?? $_SESSION['admin_login']) ?>
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
                <!-- Lista de Categorias -->
                <div class="page-header">
                    <h1><i class="fas fa-th-large"></i> Gerenciar Categorias</h1>
                    <a href="categorias.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Categoria
                    </a>
                </div>

                <div class="categories-grid">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="category-admin-card <?= !$categoria['ativo'] ? 'inactive' : '' ?>">
                            <?php if ($categoria['imagem']): ?>
                                <img src="../uploads/<?= $categoria['imagem'] ?>" 
                                     alt="<?= htmlspecialchars($categoria['nome']) ?>"
                                     style="width: 80px; height: 80px; object-fit: cover; border-radius: 50%; margin-bottom: 1rem;">
                            <?php else: ?>
                                <div class="category-icon-large">
                                    <i class="<?= $categoria['icone'] ?: 'fas fa-utensils' ?>"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h3><?= htmlspecialchars($categoria['nome']) ?></h3>
                            <p><?= htmlspecialchars($categoria['descricao']) ?></p>
                            
                            <div class="category-stats">
                                <span><?= $categoria['total_produtos'] ?> produtos</span>
                                <span><?= $categoria['ativo'] ? 'Ativa' : 'Inativa' ?></span>
                            </div>
                            
                            <div class="category-actions">
                                <a href="categorias.php?action=edit&id=<?= $categoria['id'] ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($categoria['ativo']): ?>
                                    <a href="categorias.php?action=delete&id=<?= $categoria['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Deseja desativar esta categoria?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Formulário de Adicionar/Editar -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-th-large"></i> 
                        <?= $action === 'add' ? 'Nova Categoria' : 'Editar Categoria' ?>
                    </h1>
                    <a href="categorias.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="form-container">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="nome">Nome da Categoria *</label>
                                <input type="text" id="nome" name="nome" class="form-input" 
                                       value="<?= htmlspecialchars($categoria_edit['nome'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="icone">Ícone</label>
                                <input type="text" id="icone" name="icone" class="form-input" 
                                       value="<?= htmlspecialchars($categoria_edit['icone'] ?? '') ?>"
                                       placeholder="Ex: fas fa-pizza-slice" readonly>
                                
                                <div class="icon-selector">
                                    <?php foreach ($icones_disponiveis as $classe => $nome): ?>
                                        <div class="icon-option" onclick="selectIcon('<?= $classe ?>')">
                                            <i class="<?= $classe ?>"></i>
                                            <span><?= $nome ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="form-group form-group-full">
                                <label class="form-label" for="descricao">Descrição</label>
                                <textarea id="descricao" name="descricao" class="form-textarea" 
                                          placeholder="Descrição da categoria..."><?= htmlspecialchars($categoria_edit['descricao'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="imagem">Imagem da Categoria</label>
                                <input type="file" id="imagem" name="imagem" class="form-input" 
                                       accept="image/*" onchange="previewImage(this)">
                                <small style="color: #666;">Opcional - Se não enviar imagem, será usado o ícone selecionado</small>
                                <?php if ($categoria_edit && $categoria_edit['imagem']): ?>
                                    <img src="../uploads/<?= $categoria_edit['imagem'] ?>" 
                                         alt="Imagem atual" class="image-preview" id="imagePreview">
                                <?php else: ?>
                                    <img id="imagePreview" class="image-preview" style="display: none;">
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="ativo" 
                                           <?= (!$categoria_edit || $categoria_edit['ativo']) ? 'checked' : '' ?>>
                                    <span>Categoria ativa</span>
                                </label>
                            </div>
                        </div>
                        
                        <div style="margin-top: 2rem; text-align: center;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> 
                                <?= $action === 'add' ? 'Adicionar Categoria' : 'Salvar Alterações' ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function selectIcon(iconClass) {
            // Remover seleção anterior
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('selected'));
            
            // Adicionar seleção atual
            event.currentTarget.classList.add('selected');
            
            // Atualizar campo de input
            document.getElementById('icone').value = iconClass;
        }
        
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
        
        // Marcar ícone selecionado ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            const iconeAtual = document.getElementById('icone').value;
            if (iconeAtual) {
                const iconOption = document.querySelector(`[onclick="selectIcon('${iconeAtual}')"]`);
                if (iconOption) {
                    iconOption.classList.add('selected');
                }
            }
        });
    </script>
</body>
</html>

