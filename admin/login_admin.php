<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - MozEntregas</title>
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

        .login-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }

        .login-header {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .login-logo {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .login-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .login-form {
            padding: 2rem;
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
            border-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
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
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-danger:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .login-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .login-links a {
            color: var(--danger-color);
            text-decoration: none;
            font-weight: 500;
        }

        .login-links a:hover {
            text-decoration: underline;
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .loading {
            display: none;
        }

        .loading.show {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .credentials-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                margin: 0;
                border-radius: 0;
            }

            .login-header {
                padding: 1.5rem;
            }

            .login-form {
                padding: 1.5rem;
            }

            .login-logo {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-shield-alt"></i>
                MozEntregas
            </div>
            <div class="login-subtitle">Painel Administrativo</div>
            <div class="admin-badge">
                <i class="fas fa-crown"></i> ADMIN
            </div>
        </div>

        <div class="login-form">
            <!-- Credentials Info -->
            <div class="credentials-info">
                <i class="fas fa-info-circle"></i>
                <strong>Credenciais Padrão:</strong><br>
                Email: admin@mozentregas.com<br>
                Senha: password
            </div>

            <!-- Security Notice -->
            <div class="security-notice">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Área Restrita:</strong> Apenas administradores autorizados podem acessar esta área.
            </div>

            <!-- Alerts -->
            <?php
            $message = $_GET['message'] ?? '';
            $type = $_GET['type'] ?? '';
            if ($message):
            ?>
                <div class="alert alert-<?= $type ?> show">
                    <i class="fas fa-<?= $type === 'error' ? 'exclamation-circle' : ($type === 'warning' ? 'exclamation-triangle' : 'check-circle') ?>"></i> 
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form id="adminLoginForm" method="POST" action="../auth/login.php">
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-user-shield"></i> Email do Administrador
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="admin@mozentregas.com"
                           value="admin@mozentregas.com"
                           required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-key"></i> Senha Administrativa
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="password"
                           required>
                </div>

                <button type="submit" class="btn btn-danger" id="loginBtn">
                    <i class="fas fa-spinner loading" id="loadingIcon"></i>
                    <i class="fas fa-sign-in-alt" id="loginIcon"></i>
                    <span id="loginText">Acessar Painel</span>
                </button>
            </form>

            <div class="login-links">
                <p>
                    <a href="../index_moz.php">
                        <i class="fas fa-home"></i> Voltar ao site
                    </a>
                </p>
                <p style="margin-top: 1rem;">
                    <a href="../login.php">
                        <i class="fas fa-user"></i> Login de usuário
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Validação do formulário
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            // Validações básicas
            if (!email || !password) {
                e.preventDefault();
                showAlert('Por favor, preencha todos os campos.', 'error');
                return;
            }
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                showAlert('Por favor, insira um email válido.', 'error');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showAlert('A senha deve ter pelo menos 6 caracteres.', 'error');
                return;
            }
            
            // Mostrar loading
            showLoading(true);
        });

        // Função para validar email
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Função para mostrar loading
        function showLoading(show) {
            const loginBtn = document.getElementById('loginBtn');
            const loadingIcon = document.getElementById('loadingIcon');
            const loginIcon = document.getElementById('loginIcon');
            const loginText = document.getElementById('loginText');
            
            if (show) {
                loginBtn.disabled = true;
                loadingIcon.classList.add('show');
                loginIcon.style.display = 'none';
                loginText.textContent = 'Verificando...';
            } else {
                loginBtn.disabled = false;
                loadingIcon.classList.remove('show');
                loginIcon.style.display = 'inline-block';
                loginText.textContent = 'Acessar Painel';
            }
        }

        // Função para mostrar alertas
        function showAlert(message, type) {
            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());
            
            // Criar novo alerta
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} show`;
            alert.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i> 
                ${message}
            `;
            
            // Inserir antes do formulário
            const form = document.getElementById('adminLoginForm');
            form.parentNode.insertBefore(alert, form);
            
            // Auto-remover após 5 segundos
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }

        // Auto-hide alerts existentes
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert.show');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Focar no campo senha ao carregar (email já está preenchido)
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('password').focus();
        });
    </script>
</body>
</html>

