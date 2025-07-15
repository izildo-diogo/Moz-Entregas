# 🔐 CREDENCIAIS DE ADMINISTRADOR - MozEntregas

## 👤 **ACESSO ADMINISTRATIVO**

### **Credenciais Padrão:**
- **Email:** `admin@mozentregas.com`
- **Senha:** `password`

### **URLs de Acesso:**
- **Login Admin:** `admin/login_admin.php`
- **Dashboard:** `admin/index.php`
- **Login Usuário:** `login.php`

---

## 🚀 **INSTRUÇÕES DE PRIMEIRO ACESSO**

### **1. Configurar Base de Dados:**
1. Importe o arquivo `database_moz_entregas.sql`
2. Execute o script `create_admin.sql` para criar o administrador padrão
3. Configure as credenciais da base de dados no `config_moz.php`

### **2. Primeiro Login:**
1. Acesse `admin/login_admin.php`
2. Use as credenciais padrão acima
3. **IMPORTANTE:** Altere a senha imediatamente após o primeiro login

### **3. Configurações Iniciais:**
1. Acesse o painel administrativo
2. Configure as categorias de produtos
3. Adicione lojas parceiras
4. Configure produtos iniciais
5. Teste todas as funcionalidades

---

## ⚠️ **SEGURANÇA IMPORTANTE**

### **Alterar Senha Padrão:**
```sql
UPDATE usuarios 
SET senha = '$2y$10$[NOVA_SENHA_HASH]' 
WHERE email = 'admin@mozentregas.com';
```

### **Criar Novos Administradores:**
```sql
INSERT INTO usuarios (nome, email, telefone, endereco, senha, ativo, email_verificado, is_admin, created_at, updated_at)
VALUES (
    'Nome do Admin',
    'novo@admin.com',
    '+258840000000',
    'Endereço',
    '$2y$10$[SENHA_HASH]',
    1,
    1,
    1,
    NOW(),
    NOW()
);
```

---

## 🔧 **FUNCIONALIDADES ADMINISTRATIVAS**

### **Dashboard:**
- Estatísticas em tempo real
- Últimos pedidos
- Produtos mais vendidos
- Atividades recentes

### **Gestão de Produtos:**
- CRUD completo
- Upload de imagens
- Ativação/desativação
- Filtros e pesquisa

### **Gestão de Pedidos:**
- Visualização detalhada
- Atualização de status
- Sistema de notas
- Filtros avançados

### **Gestão de Lojas:**
- Cadastro de parceiros
- Informações de contato
- Ativação/desativação

### **Gestão de Usuários:**
- Lista de clientes
- Informações detalhadas
- Controle de acesso

---

## 📱 **ACESSO MOBILE**

O painel administrativo é totalmente responsivo e pode ser acessado via:
- Desktop
- Tablet
- Smartphone

---

## 🆘 **SUPORTE TÉCNICO**

### **Problemas Comuns:**

**1. Não consegue fazer login:**
- Verifique se executou o script `create_admin.sql`
- Confirme as credenciais
- Verifique a configuração da base de dados

**2. Erro de base de dados:**
- Verifique as credenciais no `config_moz.php`
- Confirme se a base de dados foi criada
- Verifique se o MySQL está rodando

**3. Páginas em branco:**
- Ative a exibição de erros PHP
- Verifique os logs do servidor
- Confirme as permissões de arquivos

### **Logs do Sistema:**
- Atividades: Tabela `activity_logs`
- Erros: Tabela `system_logs`
- Pedidos: Tabela `pedidos`

---

## 📞 **CONTATO**

Para suporte técnico ou dúvidas:
- Consulte a documentação em `INSTALACAO.md`
- Verifique as correções em `CORRECOES_IMPLEMENTADAS.md`
- Analise os logs do sistema

---

**MozEntregas v2.0 - Sistema de E-commerce Completo**
*Desenvolvido com foco em segurança e funcionalidade*

