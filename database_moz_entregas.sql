-- Criação do banco de dados MozEntregas
-- Versão 2.0 - Corrigida e Otimizada

CREATE DATABASE IF NOT EXISTS moz_entregas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE moz_entregas;

-- Tabela de usuários (CORRIGIDA - com is_admin)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telefone VARCHAR(20) UNIQUE,
    senha VARCHAR(255) NOT NULL,
    endereco TEXT,
    data_nascimento DATE,
    ativo BOOLEAN DEFAULT TRUE,
    email_verificado BOOLEAN DEFAULT FALSE,
    is_admin BOOLEAN DEFAULT FALSE, -- ADICIONADO: Campo para identificar administradores
    token_verificacao VARCHAR(100),
    token_recuperacao VARCHAR(100),
    expiracao_token_recuperacao DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_email (email),
    INDEX idx_telefone (telefone),
    INDEX idx_ativo (ativo),
    INDEX idx_is_admin (is_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    imagem VARCHAR(255),
    icone VARCHAR(50),
    cor VARCHAR(7) DEFAULT '#667eea', -- Cor em hexadecimal
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lojas/supermercados
CREATE TABLE IF NOT EXISTS lojas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL UNIQUE,
    descricao TEXT,
    endereco VARCHAR(255),
    telefone VARCHAR(20),
    email VARCHAR(150),
    imagem VARCHAR(255),
    horario_funcionamento VARCHAR(100),
    cliques INT DEFAULT 0,
    vendas INT DEFAULT 0,
    avaliacao_media DECIMAL(3,2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_nome (nome),
    INDEX idx_ativo (ativo),
    INDEX idx_avaliacao (avaliacao_media)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL,
    preco_promocional DECIMAL(10,2) NULL,
    imagem VARCHAR(255),
    loja_id INT NOT NULL,
    categoria_id INT NOT NULL,
    cliques INT DEFAULT 0,
    vendas INT DEFAULT 0,
    estoque INT DEFAULT 0,
    avaliacao_media DECIMAL(3,2) DEFAULT 0.00,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (loja_id) REFERENCES lojas(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_nome (nome),
    INDEX idx_loja (loja_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_preco (preco),
    INDEX idx_ativo (ativo),
    INDEX idx_avaliacao (avaliacao_media)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de carrinho
CREATE TABLE IF NOT EXISTS carrinho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    session_id VARCHAR(255) NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id),
    INDEX idx_produto (produto_id),
    UNIQUE KEY unique_cart_item (usuario_id, session_id, produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    session_id VARCHAR(255) NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM(
        'pendente', 'confirmado', 'preparando', 'em_transito', 
        'entregue', 'cancelado', 'reembolsado'
    ) DEFAULT 'pendente',
    nome_cliente VARCHAR(150) NOT NULL,
    telefone_cliente VARCHAR(20) NOT NULL,
    email_cliente VARCHAR(150),
    endereco_entrega TEXT NOT NULL,
    observacoes TEXT,
    forma_pagamento VARCHAR(50) NOT NULL,
    taxa_entrega DECIMAL(10,2) DEFAULT 0.00,
    desconto DECIMAL(10,2) DEFAULT 0.00,
    avaliado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    -- Índices para performance
    INDEX idx_usuario (usuario_id),
    INDEX idx_session (session_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    
    -- Chaves estrangeiras
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_pedido (pedido_id),
    INDEX idx_produto (produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de avaliações
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    produto_id INT NULL,
    loja_id INT NULL,
    pedido_id INT NULL,
    nota INT NOT NULL CHECK (nota >= 1 AND nota <= 5),
    comentario TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (loja_id) REFERENCES lojas(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_usuario (usuario_id),
    INDEX idx_produto (produto_id),
    INDEX idx_loja (loja_id),
    INDEX idx_nota (nota),
    UNIQUE KEY unique_avaliacao (usuario_id, produto_id, loja_id, pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de favoritos
CREATE TABLE IF NOT EXISTS favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    produto_id INT NULL,
    loja_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    FOREIGN KEY (loja_id) REFERENCES lojas(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_usuario (usuario_id),
    INDEX idx_produto (produto_id),
    INDEX idx_loja (loja_id),
    UNIQUE KEY unique_favorito (usuario_id, produto_id, loja_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    lida BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_usuario (usuario_id),
    INDEX idx_lida (lida),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de motoristas
CREATE TABLE IF NOT EXISTS motoristas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) UNIQUE,
    veiculo_tipo ENUM('moto', 'carro', 'bicicleta') NOT NULL,
    veiculo_placa VARCHAR(20),
    foto VARCHAR(255),
    status ENUM('disponivel', 'ocupado', 'offline') DEFAULT 'offline',
    avaliacao_media DECIMAL(3,2) DEFAULT 0.00,
    total_entregas INT DEFAULT 0,
    localizacao_lat DECIMAL(10,8),
    localizacao_lng DECIMAL(11,8),
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_telefone (telefone),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de entregas/rastreamento
CREATE TABLE IF NOT EXISTS entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL UNIQUE,
    motorista_id INT NULL,
    status ENUM(
        'aguardando_motorista', 'motorista_designado', 'coletado', 
        'em_transito', 'entregue', 'cancelado'
    ) DEFAULT 'aguardando_motorista',
    tempo_estimado INT,
    distancia_km DECIMAL(5,2),
    taxa_entrega DECIMAL(10,2),
    endereco_coleta TEXT,
    endereco_entrega TEXT,
    coordenadas_coleta_lat DECIMAL(10,8),
    coordenadas_coleta_lng DECIMAL(11,8),
    coordenadas_entrega_lat DECIMAL(10,8),
    coordenadas_entrega_lng DECIMAL(11,8),
    coordenadas_atual_lat DECIMAL(10,8),
    coordenadas_atual_lng DECIMAL(11,8),
    observacoes TEXT,
    data_coleta TIMESTAMP NULL,
    data_entrega TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE SET NULL,
    
    -- Índices para performance
    INDEX idx_pedido (pedido_id),
    INDEX idx_motorista (motorista_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs de atividade
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    additional_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    -- Índices para performance
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_level (level),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cupons de desconto
CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(200),
    tipo ENUM('percentual', 'valor_fixo') NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    valor_minimo_pedido DECIMAL(10,2) DEFAULT 0.00,
    limite_uso INT DEFAULT NULL,
    usado INT DEFAULT 0,
    data_inicio DATE,
    data_fim DATE,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para performance
    INDEX idx_codigo (codigo),
    INDEX idx_ativo (ativo),
    INDEX idx_data_fim (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de uso de cupons
CREATE TABLE IF NOT EXISTS cupons_uso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cupom_id INT NOT NULL,
    usuario_id INT NULL,
    pedido_id INT NOT NULL,
    valor_desconto DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    
    -- Índices para performance
    INDEX idx_cupom (cupom_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers para atualizar contadores automaticamente

-- Trigger para atualizar vendas do produto
DELIMITER $$
CREATE TRIGGER update_produto_vendas 
AFTER INSERT ON itens_pedido 
FOR EACH ROW 
BEGIN
    UPDATE produtos 
    SET vendas = vendas + NEW.quantidade 
    WHERE id = NEW.produto_id;
END$$
DELIMITER ;

-- Trigger para atualizar vendas da loja
DELIMITER $$
CREATE TRIGGER update_loja_vendas 
AFTER INSERT ON itens_pedido 
FOR EACH ROW 
BEGIN
    UPDATE lojas l
    INNER JOIN produtos p ON l.id = p.loja_id
    SET l.vendas = l.vendas + NEW.quantidade 
    WHERE p.id = NEW.produto_id;
END$$
DELIMITER ;

-- Trigger para atualizar avaliação média do produto
DELIMITER $$
CREATE TRIGGER update_produto_avaliacao 
AFTER INSERT ON avaliacoes 
FOR EACH ROW 
BEGIN
    IF NEW.produto_id IS NOT NULL THEN
        UPDATE produtos 
        SET avaliacao_media = (
            SELECT AVG(nota) 
            FROM avaliacoes 
            WHERE produto_id = NEW.produto_id
        ) 
        WHERE id = NEW.produto_id;
    END IF;
END$$
DELIMITER ;

-- Trigger para atualizar avaliação média da loja
DELIMITER $$
CREATE TRIGGER update_loja_avaliacao 
AFTER INSERT ON avaliacoes 
FOR EACH ROW 
BEGIN
    IF NEW.loja_id IS NOT NULL THEN
        UPDATE lojas 
        SET avaliacao_media = (
            SELECT AVG(nota) 
            FROM avaliacoes 
            WHERE loja_id = NEW.loja_id
        ) 
        WHERE id = NEW.loja_id;
    END IF;
END$$
DELIMITER ;

