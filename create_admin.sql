-- Script para criar usuário administrador padrão e corrigir estrutura da base de dados
-- Execute este script após importar a base de dados

-- Verificar e adicionar coluna is_admin se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'usuarios' 
     AND COLUMN_NAME = 'is_admin') = 0,
    'ALTER TABLE usuarios ADD COLUMN is_admin BOOLEAN DEFAULT FALSE AFTER email_verificado',
    'SELECT "Coluna is_admin já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna updated_at se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'usuarios' 
     AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Coluna updated_at já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna cor na tabela categorias se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'categorias' 
     AND COLUMN_NAME = 'cor') = 0,
    'ALTER TABLE categorias ADD COLUMN cor VARCHAR(7) DEFAULT "#667eea" AFTER icone',
    'SELECT "Coluna cor já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna updated_at na tabela categorias se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'categorias' 
     AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE categorias ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Coluna updated_at já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna email na tabela lojas se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'lojas' 
     AND COLUMN_NAME = 'email') = 0,
    'ALTER TABLE lojas ADD COLUMN email VARCHAR(150) AFTER telefone',
    'SELECT "Coluna email já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar e adicionar coluna updated_at na tabela lojas se não existir
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'lojas' 
     AND COLUMN_NAME = 'updated_at') = 0,
    'ALTER TABLE lojas ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    'SELECT "Coluna updated_at já existe" as status'
));

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Criar tabela activity_logs se não existir
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Criar tabela system_logs se não existir
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuário administrador padrão
INSERT INTO usuarios (nome, email, telefone, endereco, senha, ativo, email_verificado, is_admin, created_at, updated_at)
VALUES (
    'Administrador MozEntregas',
    'admin@mozentregas.com',
    '+258840000000',
    'Maputo, Moçambique',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: password
    1,
    1,
    1,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    is_admin = 1,
    ativo = 1,
    updated_at = NOW();

-- Inserir algumas categorias padrão
INSERT INTO categorias (nome, descricao, cor, ativo, created_at, updated_at) VALUES
('Comida Rápida', 'Hambúrgueres, pizzas, sanduíches', '#FF6B6B', 1, NOW(), NOW()),
('Comida Tradicional', 'Pratos tradicionais moçambicanos', '#4ECDC4', 1, NOW(), NOW()),
('Bebidas', 'Refrigerantes, sucos, águas', '#45B7D1', 1, NOW(), NOW()),
('Sobremesas', 'Doces, bolos, gelados', '#96CEB4', 1, NOW(), NOW()),
('Saudável', 'Saladas, pratos light', '#FFEAA7', 1, NOW(), NOW()),
('Frutos do Mar', 'Peixes, camarões, lagosta', '#74B9FF', 1, NOW(), NOW()),
('Carnes', 'Frango, carne bovina, suína', '#E17055', 1, NOW(), NOW()),
('Vegetariano', 'Pratos sem carne', '#00B894', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    cor = VALUES(cor),
    ativo = 1,
    updated_at = NOW();

-- Inserir algumas lojas padrão
INSERT INTO lojas (nome, descricao, endereco, telefone, email, ativo, created_at, updated_at) VALUES
('Restaurante Central', 'Comida tradicional moçambicana', 'Av. Julius Nyerere, 1234, Maputo', '+258843000001', 'central@mozentregas.com', 1, NOW(), NOW()),
('Pizza Express', 'Pizzas artesanais e massas', 'Av. 24 de Julho, 567, Maputo', '+258843000002', 'pizza@mozentregas.com', 1, NOW(), NOW()),
('Burger House', 'Hambúrgueres gourmet', 'Av. Marginal, 890, Maputo', '+258843000003', 'burger@mozentregas.com', 1, NOW(), NOW()),
('Sabores do Mar', 'Especialidades em frutos do mar', 'Rua da Praia, 123, Maputo', '+258843000004', 'mar@mozentregas.com', 1, NOW(), NOW()),
('Doce Vida', 'Sobremesas e doces tradicionais', 'Av. Eduardo Mondlane, 456, Maputo', '+258843000005', 'doces@mozentregas.com', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    endereco = VALUES(endereco),
    telefone = VALUES(telefone),
    email = VALUES(email),
    ativo = 1,
    updated_at = NOW();

-- Inserir alguns produtos de exemplo
INSERT INTO produtos (nome, descricao, preco, loja_id, categoria_id, ativo, created_at, updated_at) VALUES
('Matapa com Camarão', 'Prato tradicional moçambicano com folhas de mandioca e camarão', 350.00, 1, 2, 1, NOW(), NOW()),
('Pizza Margherita', 'Pizza clássica com molho de tomate, mozzarella e manjericão', 280.00, 2, 1, 1, NOW(), NOW()),
('Hambúrguer Clássico', 'Hambúrguer com carne bovina, alface, tomate e queijo', 220.00, 3, 1, 1, NOW(), NOW()),
('Lagosta Grelhada', 'Lagosta fresca grelhada com molho especial', 850.00, 4, 6, 1, NOW(), NOW()),
('Pudim de Leite', 'Sobremesa tradicional cremosa', 120.00, 5, 4, 1, NOW(), NOW()),
('Coca-Cola 500ml', 'Refrigerante gelado', 45.00, 1, 3, 1, NOW(), NOW()),
('Salada Caesar', 'Salada fresca com frango grelhado', 180.00, 1, 5, 1, NOW(), NOW()),
('Frango à Zambeziana', 'Frango temperado com especiarias locais', 320.00, 1, 7, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    descricao = VALUES(descricao),
    preco = VALUES(preco),
    ativo = 1,
    updated_at = NOW();

-- Criar usuário de teste
INSERT INTO usuarios (nome, email, telefone, endereco, senha, ativo, email_verificado, is_admin, created_at, updated_at)
VALUES (
    'Cliente Teste',
    'cliente@teste.com',
    '+258841234567',
    'Rua das Flores, 123, Maputo',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: password
    1,
    1,
    0,
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE
    nome = VALUES(nome),
    ativo = 1,
    updated_at = NOW();

-- Log da criação
INSERT INTO activity_logs (user_id, action, description, created_at)
VALUES (1, 'database_setup', 'Base de dados configurada com dados iniciais', NOW());

-- Mensagem de sucesso
SELECT 'Base de dados configurada com sucesso!' as status,
       'Admin: admin@mozentregas.com / password' as credenciais,
       'Cliente teste: cliente@teste.com / password' as teste;

