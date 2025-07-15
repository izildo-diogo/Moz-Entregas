<?php
/**
 * Página de recuperação de senha - MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once 'config_moz.php';

$message = '';
$messageType = '';
$step = 'request'; // request, verify, reset

// Verificar se há token na URL
$token = isset($_GET['token']) ? sanitize($_GET['token']) : '';
if ($token) {
    $step = 'reset';
}

// Processar solicitação de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'request_reset') {
        try {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido.');
            }
            
            $email = sanitize($_POST['email']);
            
            if (!isValidEmail($email)) {
                throw new Exception('Email inválido.');
            }
            
            $pdo = getConnection();
            
            // Verificar se email existe
            $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = ? AND ativo = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Por segurança, não revelar se o email existe ou não
                $message = 'Se o email estiver cadastrado, você receberá instruções para redefinir sua senha.';
                $messageType = 'success';
            } else {
                // Gerar token de recuperação
                $resetToken = generateSecureToken(32);
                $expiresAt = date('Y-m-d H:i:s', time() + (TOKEN_EXPIRY_HOURS * 3600));
                
                // Salvar token na base de dados
                $stmt = $pdo->prepare("
                    INSERT INTO password_reset_tokens (usuario_id, token, expires_at)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    token = VALUES(token), 
                    expires_at = VALUES(expires_at),
                    used = 0
                ");
                $stmt->execute([$user['id'], $resetToken, $expiresAt]);
                
                // Enviar email (simulação)
                $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/recuperar_senha.php?token=' . $resetToken;
                $emailSent = sendPasswordResetEmail($email, $user['nome'], $resetLink);
                
                if ($emailSent) {
                    // Log da atividade
                    logUserActivity($user['id'], 'password_reset_requested', 'Password reset requested');
                    
                    $message = 'Instruções para redefinir sua senha foram enviadas para seu email.';
                    $messageType = 'success';
                } else {
                    throw new Exception('Erro ao enviar email. Tente novamente mais tarde.');
                }
            }
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
            logSystemError('Password reset request error: ' . $e->getMessage());
        }
    }
    
    elseif ($_POST['action'] === 'reset_password') {
        try {
            // Validar token CSRF
            if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido.');
            }
            
            $token = sanitize($_POST['token']);
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';
            
            // Validações
            if (empty($token) || empty($novaSenha) || empty($confirmarSenha)) {
                throw new Exception('Todos os campos são obrigatórios.');
            }
            
            if ($novaSenha !== $confirmarSenha) {
                throw new Exception('As senhas não coincidem.');
            }
            
            if (strlen($novaSenha) < 6) {
                throw new Exception('A senha deve ter pelo menos 6 caracteres.');
            }
            
            $pdo = getConnection();
            
            // Verificar token
            $stmt = $pdo->prepare("
                SELECT prt.*, u.id as user_id, u.email, u.nome
                FROM password_reset_tokens prt
                INNER JOIN usuarios u ON prt.usuario_id = u.id
                WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = 0 AND u.ativo = 1
            ");
            $stmt->execute([$token]);
            $resetData = $stmt->fetch();
            
            if (!$resetData) {
                throw new Exception('Token inválido ou expirado.');
            }
            
            // Atualizar senha
            $novoHash = hashPassword($novaSenha);
            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$novoHash, $resetData['user_id']]);
            
            // Marcar token como usado
            $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
            $stmt->execute([$resetData['id']]);
            
            // Log da atividade
            logUserActivity($resetData['user_id'], 'password_reset_completed', 'Password reset completed');
            
            $message = 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
            $messageType = 'success';
            $step = 'completed';
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
            logSystemError('Password reset error: ' . $e->getMessage());
        }
    }
}

// Verificar token se estiver na URL
if ($step === 'reset' && $token) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT prt.*, u.email
            FROM password_reset_tokens prt
            INNER JOIN usuarios u ON prt.usuario_id = u.id
            WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used = 0 AND u.ativo = 1
        ");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();
        
        if (!$resetData) {
            $message = 'Token inválido ou expirado. Solicite uma nova recuperação de senha.';
            $messageType = 'error';
            $step = 'request';
        }
        
    } catch (PDOException $e) {
        logSystemError('Token verification error: ' . $e->getMessage());
        $message = 'Erro ao verificar token. Tente novamente.';
        $messageType = 'error';
        $step = 'request';
    }
}

/**
 * Enviar email de recuperação de senha (simulação)
 */
function sendPasswordResetEmail($email, $nome, $resetLink) {
    // Em produção, usar PHPMailer, SendGrid, etc.
    // Por agora, apenas simular o envio
    
    $subject = 'Recuperação de Senha - MozEntregas';
    $body = "
    Olá {$nome},
    
    Você solicitou a recuperação de sua senha no MozEntregas.
    
    Clique no link abaixo para redefinir sua senha:
    {$resetLink}
    
    Este link é válido por " . TOKEN_EXPIRY_HOURS . " horas.
    
    Se você não solicitou esta recuperação, ignore este email.
    
    Atenciosamente,
    Equipe MozEntregas
    ";
    
    // Log do envio (para demonstração)
    logSystemError('Password reset email sent (simulation)', [
        'to' => $email,
        'subject' => $subject,
        'reset_link' => $resetLink
    ]);
    
    // Simular sucesso
    return true;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - MozEntregas</title>
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
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .content {
            padding: 2rem;
        }

        .step-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
            text-align: center;
        }

        .step-description {
            color: #666;
            text-align: center;
            margin-bottom: 2rem;
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
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

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

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .links {
            text-align: center;
            margin-top: 1rem;
        }

        .link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 1rem 0;
            color: #666;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
            z-index: 1;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            z-index: 2;
        }

        .password-requirements {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .password-requirements h4 {
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .password-requirements li {
            margin-bottom: 0.25rem;
            color: #666;
        }

        .success-icon {
            text-align: center;
            margin-bottom: 1rem;
        }

        .success-icon i {
            font-size: 3rem;
            color: var(--success-color);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                margin: 0.5rem;
            }

            .header {
                padding: 1.5rem;
            }

            .content {
                padding: 1.5rem;
            }

            .logo {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                MozEntregas
            </div>
            <div class="subtitle">Delivery de Comida em Moçambique</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Alerts -->
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> show">
                    <i class="fas fa-<?= $messageType === 'error' ? 'exclamation-circle' : ($messageType === 'success' ? 'check-circle' : 'info-circle') ?>"></i> 
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'request'): ?>
                <!-- Request Reset Step -->
                <h2 class="step-title">Recuperar Senha</h2>
                <p class="step-description">
                    Digite seu email para receber instruções de recuperação de senha.
                </p>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="request_reset">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="seu@email.com"
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Enviar Instruções
                    </button>
                </form>

                <div class="divider">
                    <span>ou</span>
                </div>

                <div class="links">
                    <a href="login.php" class="link">
                        <i class="fas fa-arrow-left"></i> Voltar ao Login
                    </a>
                </div>

            <?php elseif ($step === 'reset'): ?>
                <!-- Reset Password Step -->
                <h2 class="step-title">Nova Senha</h2>
                <p class="step-description">
                    Digite sua nova senha abaixo.
                </p>

                <div class="password-requirements">
                    <h4>Requisitos da senha:</h4>
                    <ul>
                        <li>Mínimo de 6 caracteres</li>
                        <li>Recomendado: use letras, números e símbolos</li>
                        <li>Evite senhas muito simples</li>
                    </ul>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    
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
                        <i class="fas fa-key"></i> Redefinir Senha
                    </button>
                </form>

            <?php elseif ($step === 'completed'): ?>
                <!-- Completed Step -->
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2 class="step-title">Senha Redefinida!</h2>
                <p class="step-description">
                    Sua senha foi redefinida com sucesso. Agora você pode fazer login com sua nova senha.
                </p>

                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Fazer Login
                </a>

                <div class="links">
                    <a href="index_moz.php" class="link">
                        <i class="fas fa-home"></i> Voltar ao Início
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Validação de confirmação de senha
        document.addEventListener('DOMContentLoaded', function() {
            const novaSenhaInput = document.querySelector('input[name="nova_senha"]');
            const confirmarSenhaInput = document.querySelector('input[name="confirmar_senha"]');
            
            if (novaSenhaInput && confirmarSenhaInput) {
                function validatePasswords() {
                    if (confirmarSenhaInput.value && novaSenhaInput.value !== confirmarSenhaInput.value) {
                        confirmarSenhaInput.setCustomValidity('As senhas não coincidem');
                    } else {
                        confirmarSenhaInput.setCustomValidity('');
                    }
                }
                
                novaSenhaInput.addEventListener('input', validatePasswords);
                confirmarSenhaInput.addEventListener('input', validatePasswords);
            }
        });

        // Auto-hide alerts after 10 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 10000);
    </script>
</body>
</html>

