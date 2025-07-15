<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar - MozEntregas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .register-header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .register-form {
            padding: 40px 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input.error {
            border-color: #dc3545;
            background: #fff5f5;
        }

        .form-input.success {
            border-color: #28a745;
            background: #f8fff8;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            color: #28a745;
            font-size: 0.85rem;
            margin-top: 5px;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .register-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-loading {
            display: none;
        }

        .btn-loading.show {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .form-links {
            text-align: center;
            margin-top: 25px;
        }

        .form-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .form-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 10px;
        }

        .terms-checkbox input[type="checkbox"] {
            margin-top: 3px;
            transform: scale(1.2);
            flex-shrink: 0;
        }

        .terms-checkbox label {
            font-size: 0.9rem;
            color: #6c757d;
            cursor: pointer;
            line-height: 1.4;
        }

        .terms-checkbox a {
            color: #667eea;
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        .password-strength {
            margin-top: 8px;
            font-size: 0.85rem;
        }

        .strength-bar {
            height: 4px;
            background: #e1e5e9;
            border-radius: 2px;
            margin: 5px 0;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .strength-weak .strength-fill {
            width: 33%;
            background: #dc3545;
        }

        .strength-medium .strength-fill {
            width: 66%;
            background: #ffc107;
        }

        .strength-strong .strength-fill {
            width: 100%;
            background: #28a745;
        }

        .phone-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .register-container {
                margin: 10px;
                border-radius: 15px;
            }

            .register-header {
                padding: 25px 15px;
            }

            .register-header h1 {
                font-size: 1.7rem;
            }

            .register-form {
                padding: 30px 20px;
            }

            .form-input {
                padding: 12px 15px;
            }

            .register-btn {
                padding: 12px;
                font-size: 1rem;
            }
        }

        /* Animações de validação */
        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .pulse {
            animation: pulse 0.5s ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-utensils"></i> MozEntregas</h1>
            <p>Crie a sua conta</p>
        </div>

        <form class="register-form" id="registerForm" novalidate>
            <div class="alert alert-error" id="alertError"></div>
            <div class="alert alert-success" id="alertSuccess"></div>

            <div class="form-group">
                <label for="nome" class="form-label">
                    <i class="fas fa-user"></i> Nome Completo
                </label>
                <div style="position: relative;">
                    <input type="text" id="nome" name="nome" class="form-input" 
                           placeholder="Digite o seu nome completo" required autocomplete="name">
                    <i class="input-icon fas fa-user"></i>
                </div>
                <div class="error-message" id="nomeError"></div>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i> Email
                </label>
                <div style="position: relative;">
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Digite o seu email" required autocomplete="email">
                    <i class="input-icon fas fa-envelope"></i>
                </div>
                <div class="error-message" id="emailError"></div>
            </div>

            <div class="form-group">
                <label for="telefone" class="form-label">
                    <i class="fas fa-phone"></i> Telefone
                </label>
                <div style="position: relative;">
                    <input type="tel" id="telefone" name="telefone" class="form-input" 
                           placeholder="+258XXXXXXXXX" required autocomplete="tel">
                    <i class="input-icon fas fa-phone"></i>
                </div>
                <div class="phone-hint">Formato: +258 seguido de 9 dígitos</div>
                <div class="error-message" id="telefoneError"></div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i> Senha
                </label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Digite uma senha segura" required autocomplete="new-password">
                    <i class="password-toggle fas fa-eye" id="passwordToggle"></i>
                </div>
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <span class="strength-text"></span>
                </div>
                <div class="error-message" id="passwordError"></div>
            </div>

            <div class="form-group">
                <label for="confirmPassword" class="form-label">
                    <i class="fas fa-lock"></i> Confirmar Senha
                </label>
                <div style="position: relative;">
                    <input type="password" id="confirmPassword" name="confirm_password" class="form-input" 
                           placeholder="Digite a senha novamente" required autocomplete="new-password">
                    <i class="password-toggle fas fa-eye" id="confirmPasswordToggle"></i>
                </div>
                <div class="error-message" id="confirmPasswordError"></div>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="termsAccepted" name="terms_accepted" required>
                <label for="termsAccepted">
                    Aceito os <a href="termos_uso.php" target="_blank">Termos de Uso</a> 
                    e a <a href="politica_privacidade.php" target="_blank">Política de Privacidade</a>
                </label>
            </div>
            <div class="error-message" id="termsError"></div>

            <button type="submit" class="register-btn" id="registerBtn">
                <span class="btn-loading" id="btnLoading"></span>
                <span id="btnText">Criar Conta</span>
            </button>

            <div class="form-links">
                <a href="login.php">Já tem conta? Faça login</a>
            </div>
        </form>
    </div>

    <script>
        // Elementos do DOM
        const registerForm = document.getElementById('registerForm');
        const nomeInput = document.getElementById('nome');
        const emailInput = document.getElementById('email');
        const telefoneInput = document.getElementById('telefone');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const termsCheckbox = document.getElementById('termsAccepted');
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const registerBtn = document.getElementById('registerBtn');
        const btnLoading = document.getElementById('btnLoading');
        const btnText = document.getElementById('btnText');
        const alertError = document.getElementById('alertError');
        const alertSuccess = document.getElementById('alertSuccess');
        const passwordStrength = document.getElementById('passwordStrength');

        // Toggle de visibilidade da senha
        passwordToggle.addEventListener('click', function() {
            togglePasswordVisibility(passwordInput, passwordToggle);
        });

        confirmPasswordToggle.addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordInput, confirmPasswordToggle);
        });

        function togglePasswordVisibility(input, toggle) {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            if (type === 'password') {
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            } else {
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            }
        }

        // Validação em tempo real
        nomeInput.addEventListener('input', function() {
            validateNome(this.value);
        });

        emailInput.addEventListener('input', function() {
            validateEmail(this.value);
        });

        telefoneInput.addEventListener('input', function() {
            validateTelefone(this.value);
        });

        passwordInput.addEventListener('input', function() {
            validatePassword(this.value);
            updatePasswordStrength(this.value);
            if (confirmPasswordInput.value) {
                validateConfirmPassword(confirmPasswordInput.value);
            }
        });

        confirmPasswordInput.addEventListener('input', function() {
            validateConfirmPassword(this.value);
        });

        termsCheckbox.addEventListener('change', function() {
            validateTerms(this.checked);
        });

        // Função para validar nome
        function validateNome(nome) {
            const errorElement = document.getElementById('nomeError');
            
            if (!nome.trim()) {
                showFieldError(nomeInput, errorElement, 'O nome é obrigatório.');
                return false;
            }
            
            if (nome.trim().length < 2) {
                showFieldError(nomeInput, errorElement, 'O nome deve ter pelo menos 2 caracteres.');
                return false;
            }
            
            if (nome.trim().split(' ').length < 2) {
                showFieldError(nomeInput, errorElement, 'Digite o nome completo.');
                return false;
            }
            
            showFieldSuccess(nomeInput, errorElement);
            return true;
        }

        // Função para validar email
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const errorElement = document.getElementById('emailError');
            
            if (!email) {
                showFieldError(emailInput, errorElement, 'O email é obrigatório.');
                return false;
            }
            
            if (!emailRegex.test(email)) {
                showFieldError(emailInput, errorElement, 'Digite um email válido.');
                return false;
            }
            
            showFieldSuccess(emailInput, errorElement);
            return true;
        }

        // Função para validar telefone moçambicano
        function validateTelefone(telefone) {
            const phoneRegex = /^\+258[0-9]{9}$/;
            const errorElement = document.getElementById('telefoneError');
            
            if (!telefone) {
                showFieldError(telefoneInput, errorElement, 'O telefone é obrigatório.');
                return false;
            }
            
            if (!phoneRegex.test(telefone)) {
                showFieldError(telefoneInput, errorElement, 'Digite um telefone moçambicano válido (+258XXXXXXXXX).');
                return false;
            }
            
            showFieldSuccess(telefoneInput, errorElement);
            return true;
        }

        // Função para validar senha
        function validatePassword(password) {
            const errorElement = document.getElementById('passwordError');
            
            if (!password) {
                showFieldError(passwordInput, errorElement, 'A senha é obrigatória.');
                return false;
            }
            
            if (password.length < 8) {
                showFieldError(passwordInput, errorElement, 'A senha deve ter pelo menos 8 caracteres.');
                return false;
            }
            
            if (!/(?=.*[a-z])/.test(password)) {
                showFieldError(passwordInput, errorElement, 'A senha deve conter pelo menos uma letra minúscula.');
                return false;
            }
            
            if (!/(?=.*[A-Z])/.test(password)) {
                showFieldError(passwordInput, errorElement, 'A senha deve conter pelo menos uma letra maiúscula.');
                return false;
            }
            
            if (!/(?=.*\d)/.test(password)) {
                showFieldError(passwordInput, errorElement, 'A senha deve conter pelo menos um número.');
                return false;
            }
            
            showFieldSuccess(passwordInput, errorElement);
            return true;
        }

        // Função para validar confirmação de senha
        function validateConfirmPassword(confirmPassword) {
            const errorElement = document.getElementById('confirmPasswordError');
            
            if (!confirmPassword) {
                showFieldError(confirmPasswordInput, errorElement, 'A confirmação de senha é obrigatória.');
                return false;
            }
            
            if (confirmPassword !== passwordInput.value) {
                showFieldError(confirmPasswordInput, errorElement, 'As senhas não coincidem.');
                return false;
            }
            
            showFieldSuccess(confirmPasswordInput, errorElement);
            return true;
        }

        // Função para validar termos
        function validateTerms(accepted) {
            const errorElement = document.getElementById('termsError');
            
            if (!accepted) {
                showFieldError(termsCheckbox, errorElement, 'Você deve aceitar os termos de uso.');
                return false;
            }
            
            errorElement.classList.remove('show');
            return true;
        }

        // Função para atualizar força da senha
        function updatePasswordStrength(password) {
            const strengthBar = passwordStrength.querySelector('.strength-bar');
            const strengthText = passwordStrength.querySelector('.strength-text');
            
            let score = 0;
            let feedback = '';
            
            if (password.length >= 8) score++;
            if (/(?=.*[a-z])/.test(password)) score++;
            if (/(?=.*[A-Z])/.test(password)) score++;
            if (/(?=.*\d)/.test(password)) score++;
            if (/(?=.*[!@#$%^&*])/.test(password)) score++;
            
            strengthBar.className = 'strength-bar';
            
            if (score < 3) {
                strengthBar.classList.add('strength-weak');
                feedback = 'Senha fraca';
            } else if (score < 4) {
                strengthBar.classList.add('strength-medium');
                feedback = 'Senha média';
            } else {
                strengthBar.classList.add('strength-strong');
                feedback = 'Senha forte';
            }
            
            strengthText.textContent = feedback;
        }

        // Função para mostrar erro no campo
        function showFieldError(input, errorElement, message) {
            input.classList.remove('success');
            input.classList.add('error');
            errorElement.textContent = message;
            errorElement.classList.add('show');
            input.classList.add('shake');
            setTimeout(() => input.classList.remove('shake'), 500);
        }

        // Função para mostrar sucesso no campo
        function showFieldSuccess(input, errorElement) {
            input.classList.remove('error');
            input.classList.add('success');
            errorElement.classList.remove('show');
            input.classList.add('pulse');
            setTimeout(() => input.classList.remove('pulse'), 500);
        }

        // Função para mostrar alerta
        function showAlert(type, message) {
            const alert = type === 'error' ? alertError : alertSuccess;
            const otherAlert = type === 'error' ? alertSuccess : alertError;
            
            otherAlert.classList.remove('show');
            alert.textContent = message;
            alert.classList.add('show');
            
            // Auto-hide após 5 segundos
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        // Função para definir estado de carregamento
        function setLoadingState(loading) {
            if (loading) {
                registerBtn.disabled = true;
                btnLoading.classList.add('show');
                btnText.textContent = 'Criando conta...';
            } else {
                registerBtn.disabled = false;
                btnLoading.classList.remove('show');
                btnText.textContent = 'Criar Conta';
            }
        }

        // Submissão do formulário
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const nome = nomeInput.value.trim();
            const email = emailInput.value.trim();
            const telefone = telefoneInput.value.trim();
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const termsAccepted = termsCheckbox.checked;
            
            // Validar todos os campos
            const nomeValid = validateNome(nome);
            const emailValid = validateEmail(email);
            const telefoneValid = validateTelefone(telefone);
            const passwordValid = validatePassword(password);
            const confirmPasswordValid = validateConfirmPassword(confirmPassword);
            const termsValid = validateTerms(termsAccepted);
            
            if (!nomeValid || !emailValid || !telefoneValid || !passwordValid || !confirmPasswordValid || !termsValid) {
                showAlert('error', 'Por favor, corrija os erros nos campos.');
                return;
            }
            
            // Definir estado de carregamento
            setLoadingState(true);
            
            try {
                // Fazer requisição para o servidor
                const response = await fetch('auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        nome: nome,
                        email: email,
                        telefone: telefone,
                        password: password,
                        confirm_password: confirmPassword,
                        terms_accepted: termsAccepted
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('success', 'Conta criada com sucesso! Redirecionando...');
                    
                    // Redirecionar após 2 segundos
                    setTimeout(() => {
                        window.location.href = data.redirect || 'login.php?message=Conta criada com sucesso! Faça login para continuar.&type=success';
                    }, 2000);
                } else {
                    showAlert('error', data.message || 'Erro ao criar conta. Tente novamente.');
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                showAlert('error', 'Erro de conexão. Verifique sua internet e tente novamente.');
            } finally {
                setLoadingState(false);
            }
        });

        // Focar no primeiro campo ao carregar a página
        window.addEventListener('load', function() {
            nomeInput.focus();
        });
    </script>
</body>
</html>

