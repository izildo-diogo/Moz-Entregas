<?php
require_once '../php/config.php';

// Destruir sessão
session_destroy();

// Redirecionar para login
header('Location: login.php');
exit;
?>

