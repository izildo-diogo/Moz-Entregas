# 🚀 Guia de Instalação - MozEntregas

## 📋 Pré-requisitos do Sistema

### Requisitos Mínimos
- **PHP**: 8.0 ou superior
- **MySQL**: 8.0 ou superior  
- **Servidor Web**: Apache 2.4+ ou Nginx 1.18+
- **Memória RAM**: 512MB mínimo (2GB recomendado)
- **Espaço em Disco**: 1GB mínimo (5GB recomendado)

### Extensões PHP Necessárias
```bash
# Verificar extensões instaladas
php -m | grep -E "(pdo|mysql|mbstring|openssl|curl|gd|zip)"

# Instalar extensões no Ubuntu/Debian
sudo apt-get install php8.0-pdo php8.0-mysql php8.0-mbstring php8.0-openssl php8.0-curl php8.0-gd php8.0-zip

# Instalar extensões no CentOS/RHEL
sudo yum install php80-php-pdo php80-php-mysqlnd php80-php-mbstring php80-php-openssl
```

## 🔧 Instalação Passo a Passo

### 1. Preparação do Ambiente

#### 1.1 Atualizar Sistema
```bash
# Ubuntu/Debian
sudo apt update && sudo apt upgrade -y

# CentOS/RHEL
sudo yum update -y
```

#### 1.2 Instalar LAMP Stack
```bash
# Ubuntu/Debian
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-cli php8.0-common php8.0-mbstring php8.0-xml php8.0-curl php8.0-gd php8.0-zip -y

# CentOS/RHEL
sudo yum install httpd mysql-server php80 php80-php-mysql php80-php-cli php80-php-common php80-php-mbstring -y
```

#### 1.3 Iniciar Serviços
```bash
# Ubuntu/Debian
sudo systemctl start apache2
sudo systemctl start mysql
sudo systemctl enable apache2
sudo systemctl enable mysql

# CentOS/RHEL
sudo systemctl start httpd
sudo systemctl start mysqld
sudo systemctl enable httpd
sudo systemctl enable mysqld
```

### 2. Configuração do MySQL

#### 2.1 Configuração Inicial de Segurança
```bash
sudo mysql_secure_installation
```

#### 2.2 Criar Base de Dados e Usuário
```sql
-- Conectar ao MySQL como root
mysql -u root -p

-- Criar base de dados
CREATE DATABASE mozentregas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário específico
CREATE USER 'mozentregas_user'@'localhost' IDENTIFIED BY 'SuaSenhaSegura123!';

-- Conceder permissões
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER ON mozentregas.* TO 'mozentregas_user'@'localhost';

-- Aplicar mudanças
FLUSH PRIVILEGES;

-- Sair
EXIT;
```

### 3. Instalação do MozEntregas

#### 3.1 Download e Extração
```bash
# Navegar para o diretório web
cd /var/www/html

# Extrair arquivos (assumindo que você tem o arquivo ZIP)
sudo unzip MozEntregas.zip

# Renomear diretório se necessário
sudo mv ecommerce-food mozentregas

# Definir permissões
sudo chown -R www-data:www-data /var/www/html/mozentregas
sudo chmod -R 755 /var/www/html/mozentregas
sudo chmod -R 777 /var/www/html/mozentregas/uploads
```

#### 3.2 Importar Schema da Base de Dados
```bash
# Navegar para o diretório do projeto
cd /var/www/html/mozentregas

# Importar schema
mysql -u mozentregas_user -p mozentregas < database_moz_entregas.sql
```

#### 3.3 Configurar Credenciais
```bash
# Editar arquivo de configuração
sudo nano config_moz.php
```

Atualizar as seguintes linhas:
```php
// Configurações da Base de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'mozentregas');
define('DB_USER', 'mozentregas_user');
define('DB_PASS', 'SuaSenhaSegura123!');
define('DB_CHARSET', 'utf8mb4');

// URL Base do Sistema
define('BASE_URL', 'http://seu-dominio.com');

// Configurações de Email (configurar depois)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu-email@gmail.com');
define('SMTP_PASS', 'sua-senha-app');
```

### 4. Configuração do Apache

#### 4.1 Criar Virtual Host
```bash
sudo nano /etc/apache2/sites-available/mozentregas.conf
```

Adicionar configuração:
```apache
<VirtualHost *:80>
    ServerName mozentregas.local
    ServerAlias www.mozentregas.local
    DocumentRoot /var/www/html/mozentregas
    DirectoryIndex index_moz.php
    
    <Directory /var/www/html/mozentregas>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/mozentregas_error.log
    CustomLog ${APACHE_LOG_DIR}/mozentregas_access.log combined
    
    # Segurança
    ServerTokens Prod
    ServerSignature Off
</VirtualHost>
```

#### 4.2 Ativar Site e Módulos
```bash
# Ativar módulos necessários
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Ativar site
sudo a2ensite mozentregas.conf

# Desativar site padrão (opcional)
sudo a2dissite 000-default.conf

# Reiniciar Apache
sudo systemctl restart apache2
```

#### 4.3 Configurar .htaccess
```bash
# Criar arquivo .htaccess
sudo nano /var/www/html/mozentregas/.htaccess
```

Adicionar conteúdo:
```apache
# Proteção contra acesso direto a arquivos sensíveis
<Files "config_moz.php">
    Order allow,deny
    Deny from all
</Files>

<Files "database_moz_entregas.sql">
    Order allow,deny
    Deny from all
</Files>

# Redirecionamento HTTPS (quando SSL estiver configurado)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Proteção contra hotlinking de imagens
RewriteEngine On
RewriteCond %{HTTP_REFERER} !^$
RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?mozentregas\.local [NC]
RewriteRule \.(jpg|jpeg|png|gif)$ - [NC,F,L]

# Cabeçalhos de segurança
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
```

### 5. Configuração do PHP

#### 5.1 Otimizar php.ini
```bash
sudo nano /etc/php/8.0/apache2/php.ini
```

Configurações recomendadas:
```ini
; Configurações básicas
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
post_max_size = 20M
upload_max_filesize = 10M

; Configurações de sessão
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; Configurações de segurança
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

; Configurações de erro (produção)
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Timezone
date.timezone = "Africa/Maputo"
```

#### 5.2 Reiniciar Apache
```bash
sudo systemctl restart apache2
```

### 6. Criar Usuário Administrador

#### 6.1 Gerar Hash da Senha
```php
<?php
// Criar arquivo temporário para gerar hash
echo password_hash('SuaSenhaAdmin123!', PASSWORD_DEFAULT);
?>
```

#### 6.2 Inserir Administrador na Base de Dados
```sql
mysql -u mozentregas_user -p mozentregas

INSERT INTO usuarios (nome, email, telefone, senha, tipo, ativo, email_verificado, created_at) 
VALUES (
    'Administrador Principal', 
    'admin@mozentregas.com', 
    '+258841234567', 
    '$2y$10$hash_gerado_acima', 
    'admin', 
    1, 
    1, 
    NOW()
);
```

### 7. Configuração de SSL (Recomendado)

#### 7.1 Instalar Certbot
```bash
# Ubuntu/Debian
sudo apt install certbot python3-certbot-apache -y

# CentOS/RHEL
sudo yum install certbot python3-certbot-apache -y
```

#### 7.2 Obter Certificado SSL
```bash
sudo certbot --apache -d mozentregas.com -d www.mozentregas.com
```

### 8. Configuração de Backup Automático

#### 8.1 Criar Script de Backup
```bash
sudo nano /usr/local/bin/backup_mozentregas.sh
```

Conteúdo do script:
```bash
#!/bin/bash

# Configurações
BACKUP_DIR="/var/backups/mozentregas"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="mozentregas"
DB_USER="mozentregas_user"
DB_PASS="SuaSenhaSegura123!"
WEB_DIR="/var/www/html/mozentregas"

# Criar diretório de backup
mkdir -p $BACKUP_DIR

# Backup da base de dados
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Backup dos arquivos
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz $WEB_DIR/uploads

# Remover backups antigos (manter apenas 7 dias)
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup concluído: $DATE"
```

#### 8.2 Tornar Executável e Agendar
```bash
sudo chmod +x /usr/local/bin/backup_mozentregas.sh

# Adicionar ao crontab
sudo crontab -e

# Adicionar linha para backup diário às 2:00 AM
0 2 * * * /usr/local/bin/backup_mozentregas.sh >> /var/log/backup_mozentregas.log 2>&1
```

### 9. Configuração de Monitoramento

#### 9.1 Configurar Logs
```bash
# Criar diretório de logs
sudo mkdir -p /var/log/mozentregas
sudo chown www-data:www-data /var/log/mozentregas

# Configurar rotação de logs
sudo nano /etc/logrotate.d/mozentregas
```

Conteúdo:
```
/var/log/mozentregas/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

### 10. Testes de Instalação

#### 10.1 Verificar Conectividade
```bash
# Testar conexão com base de dados
mysql -u mozentregas_user -p mozentregas -e "SELECT COUNT(*) FROM usuarios;"

# Verificar permissões de arquivos
ls -la /var/www/html/mozentregas/uploads/

# Testar Apache
sudo apache2ctl configtest
```

#### 10.2 Acessar Sistema
1. Abrir navegador e acessar: `http://seu-dominio.com`
2. Testar registro de novo usuário
3. Fazer login como administrador: `admin@mozentregas.com`
4. Acessar dashboard: `http://seu-dominio.com/admin/`

### 11. Configurações de Produção

#### 11.1 Configurações de Segurança Adicionais
```bash
# Configurar firewall
sudo ufw enable
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443

# Configurar fail2ban
sudo apt install fail2ban -y
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

#### 11.2 Otimizações de Performance
```bash
# Ativar compressão no Apache
sudo a2enmod deflate
sudo systemctl restart apache2

# Configurar cache de opcodes PHP
sudo apt install php8.0-opcache -y
```

## 🔧 Resolução de Problemas

### Problema: Erro 500 Internal Server Error
```bash
# Verificar logs de erro
sudo tail -f /var/log/apache2/mozentregas_error.log
sudo tail -f /var/log/php/error.log
```

### Problema: Uploads não funcionam
```bash
# Verificar permissões
sudo chmod 777 /var/www/html/mozentregas/uploads
sudo chown www-data:www-data /var/www/html/mozentregas/uploads
```

### Problema: Base de dados não conecta
```bash
# Verificar status do MySQL
sudo systemctl status mysql

# Testar conexão
mysql -u mozentregas_user -p
```

## 📞 Suporte

Se encontrar problemas durante a instalação:

1. Verificar logs de erro
2. Consultar documentação oficial do PHP/MySQL/Apache
3. Entrar em contato: contato@mozentregas.com

---

**Instalação concluída com sucesso! 🎉**

Agora você pode começar a usar o MozEntregas para gerenciar seu negócio de delivery.

