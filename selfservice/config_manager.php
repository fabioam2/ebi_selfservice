<?php
/**
 * Config Manager — DESABILITADO
 *
 * Este arquivo antigo escrevia config.ini concatenando strings (`"$key = '$value'"`),
 * permitindo injeção no INI. Foi desativado em favor do painel admin.php, que usa
 * hash bcrypt e escape seguro em updateEnvVariable().
 *
 * Para editar configurações: acesse admin.php (requer senha de administrador).
 */

http_response_code(410); // Gone
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Config Manager desativado</title>
<style>body{font-family:sans-serif;max-width:600px;margin:40px auto;padding:20px;background:#f8f9fa}h1{color:#dc3545}</style>
</head>
<body>
<h1>Este módulo foi desativado</h1>
<p>O antigo <code>config_manager.php</code> continha uma vulnerabilidade de injeção no arquivo INI
e não está mais disponível.</p>
<p>Use o <a href="admin.php">painel administrativo</a> para alterar configurações.</p>
</body>
</html>

