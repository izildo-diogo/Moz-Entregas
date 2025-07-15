<?php
/**
 * Arquivo de configuração principal do MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

// Configurações de erro para desenvolvimento (remover em produção)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurações de sessão seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Mudar para 1 em HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações da base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'moz_entregas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configurações do sistema
define('SITE_NAME', 'MozEntregas');
define('SITE_URL', 'http://localhost/mozentregas');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Configurações de email (configurar conforme necessário)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@mozentregas.com');
define('SMTP_PASS', 'sua_senha_aqui');

/**
 * Função para obter conexão com a base de dados
 * 
 * @return PDO Conexão PDO
 * @throws PDOException Se não conseguir conectar
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new PDOException("Erro de conexão com a base de dados.");
        }
    }
    
    return $pdo;
}

/**
 * Função para sanitizar dados de entrada
 * 
 * @param string $data Dados a serem sanitizados
 * @return string Dados sanitizados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Função para validar email
 * 
 * @param string $email Email a ser validado
 * @return bool True se válido
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Função para validar telefone moçambicano
 * 
 * @param string $phone Telefone a ser validado
 * @return bool True se válido
 */
function isValidMozambicanPhone($phone) {
    // Formato: +258XXXXXXXXX (9 dígitos após +258)
    $pattern = '/^\+258[0-9]{9}$/';
    return preg_match($pattern, $phone);
}

/**
 * Função para gerar hash de senha
 * 
 * @param string $password Senha em texto plano
 * @return string Hash da senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Função para verificar senha
 * 
 * @param string $password Senha em texto plano
 * @param string $hash Hash armazenado
 * @return bool True se a senha estiver correta
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Função para gerar token seguro
 * 
 * @param int $length Comprimento do token
 * @return string Token gerado
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Função para gerar token CSRF
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateSecureToken(32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Função para validar token CSRF
 * 
 * @param string $token Token a ser validado
 * @return bool True se válido
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Função para obter usuário atual
 * 
 * @return array|null Dados do usuário ou null se não logado
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT id, nome, email, telefone, endereco, is_admin, ativo
            FROM usuarios 
            WHERE id = ? AND ativo = 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        logSystemError('Error getting current user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Função para verificar se o usuário está logado
 * 
 * @return bool True se logado
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

/**
 * Função para verificar se o usuário é administrador
 * 
 * @param int|null $userId ID do usuário (opcional, usa o atual se não fornecido)
 * @return bool True se for administrador
 */
function isAdmin($userId = null) {
    if ($userId === null) {
        $user = getCurrentUser();
        return $user && (bool)$user['is_admin'];
    }
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT is_admin FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result && (bool)$result['is_admin'];
    } catch (PDOException $e) {
        logSystemError('Error checking admin status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Função para validar entrada de dados
 * 
 * @param array $data Dados a serem validados
 * @param array $rules Regras de validação
 * @return array Erros encontrados
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';
        
        // Campo obrigatório
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = $rule['message'] ?? "Campo {$field} é obrigatório.";
            continue;
        }
        
        // Se campo não é obrigatório e está vazio, pular outras validações
        if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
            continue;
        }
        
        // Validação de email
        if (isset($rule['email']) && $rule['email'] && !isValidEmail($value)) {
            $errors[$field] = $rule['message'] ?? "Email inválido.";
        }
        
        // Validação de comprimento mínimo
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = $rule['message'] ?? "Campo deve ter pelo menos {$rule['min_length']} caracteres.";
        }
        
        // Validação de comprimento máximo
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = $rule['message'] ?? "Campo deve ter no máximo {$rule['max_length']} caracteres.";
        }
        
        // Validação de telefone moçambicano
        if (isset($rule['mozambican_phone']) && $rule['mozambican_phone'] && !isValidMozambicanPhone($value)) {
            $errors[$field] = $rule['message'] ?? "Telefone deve estar no formato +258XXXXXXXXX.";
        }
    }
    
    return $errors;
}

/**
 * Função para enviar resposta JSON de sucesso
 * 
 * @param string $message Mensagem de sucesso
 * @param array $data Dados adicionais (opcional)
 */
function sendSuccessResponse($message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Função para enviar resposta JSON de erro
 * 
 * @param string $message Mensagem de erro
 * @param int $httpCode Código HTTP (opcional)
 * @param array $errors Erros específicos (opcional)
 */
function sendErrorResponse($message, $httpCode = 400, $errors = []) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ]);
    exit;
}

/**
 * Função para registrar log de atividade do usuário
 * 
 * @param int|null $userId ID do usuário (null para usuários não logados)
 * @param string $action Ação realizada
 * @param string $description Descrição da ação
 * @param array $additionalData Dados adicionais (opcional)
 */
function logUserActivity($userId, $action, $description, $additionalData = []) {
    try {
        $pdo = getConnection();
        
        // Verificar se a tabela existe, se não, criar
        $stmt = $pdo->query("SHOW TABLES LIKE 'activity_logs'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE activity_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    action VARCHAR(100) NOT NULL,
                    description TEXT,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    additional_data JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_action (action),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, additional_data)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            json_encode($additionalData)
        ]);
    } catch (PDOException $e) {
        // Log silencioso para não interromper o fluxo
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

/**
 * Função para registrar log do sistema
 * 
 * @param string $level Nível do log (info, warning, error, critical)
 * @param string $message Mensagem do log
 * @param array $context Contexto adicional (opcional)
 */
function logSystemEvent($level, $message, $context = []) {
    try {
        $pdo = getConnection();
        
        // Verificar se a tabela existe, se não, criar
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_logs'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
                    message TEXT NOT NULL,
                    context JSON,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level (level),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO system_logs (level, message, context)
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $level,
            $message,
            json_encode($context)
        ]);
    } catch (PDOException $e) {
        // Log em arquivo como fallback
        error_log("System log failed: " . $e->getMessage());
    }
}

/**
 * Função para registrar erro do sistema
 * 
 * @param string $message Mensagem de erro
 * @param array $context Contexto adicional (opcional)
 */
function logSystemError($message, $context = []) {
    logSystemEvent('error', $message, $context);
}

/**
 * Função para formatar moeda moçambicana
 * 
 * @param float $value Valor a ser formatado
 * @return string Valor formatado
 */
function formatCurrency($value) {
    return 'MT ' . number_format($value, 2, ',', '.');
}

/**
 * Função para formatar data
 * 
 * @param string $date Data a ser formatada
 * @param string $format Formato desejado
 * @return string Data formatada
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Função para redirecionar
 * 
 * @param string $url URL de destino
 * @param string $message Mensagem (opcional)
 * @param string $type Tipo da mensagem (opcional)
 */
function redirect($url, $message = '', $type = 'info') {
    if (!empty($message)) {
        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . 'message=' . urlencode($message) . '&type=' . urlencode($type);
    }
    
    header("Location: $url");
    exit;
}

/**
 * Função para limpar sessão
 */
function clearSession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Regenerar token CSRF a cada 30 minutos para segurança
if (!isset($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > 1800) {
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token_time'] = time();
}

// Verificar se a sessão expirou (2 horas de inatividade)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
    clearSession();
    session_start();
}

$_SESSION['last_activity'] = time();
?>

