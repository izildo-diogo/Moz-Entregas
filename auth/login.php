<?php
/**
 * Script de autenticação de login para MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../login.php', 'Método não permitido.', 'error');
}

try {
    // Obter dados do formulário
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']) ? true : false;
    
    // Validações básicas
    if (empty($email) || empty($password)) {
        throw new Exception('Email e senha são obrigatórios.');
    }
    
    if (!isValidEmail($email)) {
        throw new Exception('Email inválido.');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Senha deve ter pelo menos 6 caracteres.');
    }
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Buscar usuário pelo email
    $stmt = $pdo->prepare("
        SELECT id, nome, email, telefone, endereco, senha, ativo, email_verificado, is_admin
        FROM usuarios 
        WHERE email = ? AND ativo = 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Log da tentativa de login falhada
        logUserActivity(null, 'login_failed', 'Failed login attempt for email: ' . $email, [
            'email' => $email,
            'reason' => 'user_not_found',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        throw new Exception('Email ou senha incorretos.');
    }
    
    // Verificar senha
    if (!verifyPassword($password, $user['senha'])) {
        // Log da tentativa de login falhada
        logUserActivity($user['id'], 'login_failed', 'Failed login attempt - wrong password', [
            'email' => $email,
            'reason' => 'wrong_password',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        throw new Exception('Email ou senha incorretos.');
    }
    
    // Login bem-sucedido - criar sessão
    session_regenerate_id(true); // Regenerar ID da sessão por segurança
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_telefone'] = $user['telefone'];
    $_SESSION['user_endereco'] = $user['endereco'];
    $_SESSION['is_admin'] = (bool)$user['is_admin'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Configurar cookie de "lembrar-me" se solicitado
    if ($rememberMe) {
        $cookieToken = generateSecureToken(32);
        setcookie('remember_token', $cookieToken, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 dias
    }
    
    // Log do login bem-sucedido
    logUserActivity($user['id'], 'login_success', 'User logged in successfully', [
        'email' => $email,
        'remember_me' => $rememberMe,
        'is_admin' => $user['is_admin'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Redirecionar baseado no tipo de usuário
    if ($user['is_admin']) {
        redirect('../admin/index.php', 'Login realizado com sucesso! Bem-vindo ao painel administrativo.', 'success');
    } else {
        redirect('../index_moz.php', 'Login realizado com sucesso! Bem-vindo ao MozEntregas.', 'success');
    }
    
} catch (Exception $e) {
    // Log do erro
    logSystemError('Login error: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'email' => $email ?? 'unknown',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Redirecionar com erro
    redirect('../login.php', $e->getMessage(), 'error');
    
} catch (PDOException $e) {
    // Log do erro do sistema
    logSystemError('Database error in login: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'email' => $email ?? 'unknown'
    ]);
    
    redirect('../login.php', 'Erro interno do servidor. Tente novamente mais tarde.', 'error');
}
?>

