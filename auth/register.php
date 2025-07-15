<?php
/**
 * Script de registro de usuários para MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Definir cabeçalhos para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendErrorResponse('Método não permitido.', 405);
}

try {
    // Obter dados JSON da requisição
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Dados JSON inválidos.');
    }
    
    // Validar dados de entrada
    $validationRules = [
        'nome' => [
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'custom' => function($value) {
                $trimmed = trim($value);
                if (count(explode(' ', $trimmed)) < 2) {
                    return 'Digite o nome completo.';
                }
                return true;
            }
        ],
        'email' => [
            'required' => true,
            'email' => true,
            'max_length' => 150
        ],
        'telefone' => [
            'required' => true,
            'mozambican_phone' => true
        ],
        'password' => [
            'required' => true,
            'min_length' => 8,
            'custom' => function($value) {
                if (!preg_match('/(?=.*[a-z])/', $value)) {
                    return 'A senha deve conter pelo menos uma letra minúscula.';
                }
                if (!preg_match('/(?=.*[A-Z])/', $value)) {
                    return 'A senha deve conter pelo menos uma letra maiúscula.';
                }
                if (!preg_match('/(?=.*\d)/', $value)) {
                    return 'A senha deve conter pelo menos um número.';
                }
                return true;
            }
        ],
        'confirm_password' => [
            'required' => true,
            'custom' => function($value) use ($data) {
                if ($value !== $data['password']) {
                    return 'As senhas não coincidem.';
                }
                return true;
            }
        ],
        'terms_accepted' => [
            'required' => true,
            'custom' => function($value) {
                if (!$value) {
                    return 'Você deve aceitar os termos de uso.';
                }
                return true;
            }
        ]
    ];
    
    $errors = validateInput($data, $validationRules);
    
    if (!empty($errors)) {
        sendErrorResponse('Dados inválidos.', 400, $errors);
    }
    
    $nome = sanitize(trim($data['nome']));
    $email = sanitize(trim($data['email']));
    $telefone = sanitize(trim($data['telefone']));
    $password = $data['password'];
    
    // Conectar à base de dados
    $pdo = getConnection();
    
    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        sendErrorResponse('Este email já está registrado. Tente fazer login ou use outro email.');
    }
    
    // Verificar se o telefone já existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE telefone = ?");
    $stmt->execute([$telefone]);
    
    if ($stmt->fetch()) {
        sendErrorResponse('Este telefone já está registrado. Tente fazer login ou use outro telefone.');
    }
    
    // Gerar hash da senha
    $passwordHash = hashPassword($password);
    
    // Gerar token de verificação de email (opcional)
    $verificationToken = generateSecureToken(32);
    
    // Inserir novo usuário na base de dados
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (nome, email, telefone, senha, token_verificacao, ativo, email_verificado, created_at)
        VALUES (?, ?, ?, ?, ?, 1, 0, NOW())
    ");
    
    $stmt->execute([
        $nome,
        $email,
        $telefone,
        $passwordHash,
        $verificationToken
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Log do registro bem-sucedido
    logUserActivity($userId, 'user_registered', 'New user registered successfully', [
        'email' => $email,
        'telefone' => $telefone,
        'nome' => $nome
    ]);
    
    // TODO: Enviar email de verificação (implementar depois)
    // sendVerificationEmail($email, $nome, $verificationToken);
    
    // Fazer login automático após registro (opcional)
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $nome;
    $_SESSION['user_email'] = $email;
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Log do login automático
    logUserActivity($userId, 'auto_login_after_register', 'User automatically logged in after registration');
    
    // Resposta de sucesso
    sendSuccessResponse('Conta criada com sucesso! Bem-vindo ao MozEntregas!', [
        'user' => [
            'id' => $userId,
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone
        ],
        'redirect' => 'index.php'
    ]);
    
} catch (PDOException $e) {
    // Verificar se é erro de duplicação (email ou telefone)
    if ($e->getCode() == 23000) { // Integrity constraint violation
        if (strpos($e->getMessage(), 'email') !== false) {
            sendErrorResponse('Este email já está registrado.');
        } elseif (strpos($e->getMessage(), 'telefone') !== false) {
            sendErrorResponse('Este telefone já está registrado.');
        } else {
            sendErrorResponse('Dados duplicados. Verifique email e telefone.');
        }
    } else {
        // Log do erro do sistema
        logSystemError('Database error in registration: ' . $e->getMessage(), [
            'file' => __FILE__,
            'line' => __LINE__,
            'email' => $email ?? 'unknown',
            'telefone' => $telefone ?? 'unknown'
        ]);
        
        sendErrorResponse('Erro interno do servidor. Tente novamente mais tarde.', 500);
    }
    
} catch (Exception $e) {
    // Log do erro geral
    logSystemError('General error in registration: ' . $e->getMessage(), [
        'file' => __FILE__,
        'line' => __LINE__,
        'email' => $email ?? 'unknown',
        'telefone' => $telefone ?? 'unknown'
    ]);
    
    sendErrorResponse('Erro inesperado. Tente novamente mais tarde.', 500);
}
?>

