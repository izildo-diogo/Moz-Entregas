<?php
/**
 * Script de logout para MozEntregas
 * 
 * @author MozEntregas Team
 * @version 2.0
 */

require_once '../config_moz.php';

// Verificar se usuário está logado
if (!isUserLoggedIn()) {
    header('Location: ../login.php?message=Você já está desconectado.&type=info');
    exit;
}

// Fazer logout
logoutUser();

// Redirecionar para página de login com mensagem de sucesso
header('Location: ../login.php?message=Logout realizado com sucesso!&type=success');
exit;
?>

