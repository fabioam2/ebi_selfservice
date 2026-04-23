<?php
/**
 * Script de Instalação Automática
 * Self-Service - Sistema de Cadastro de Crianças
 * 
 * Execute este arquivo UMA VEZ para configurar o sistema
 * Depois delete este arquivo por segurança!
 */

// Verificar se já foi instalado
if (file_exists(__DIR__ . '/.instalado')) {
    die("⚠️ Sistema já foi instalado! Delete o arquivo .instalado para reinstalar.");
}

$erros = [];
$avisos = [];
$sucessos = [];

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalação - Self-Service</title>
    <link rel='stylesheet' href='https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        h1 { color: #667eea; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #667eea; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class='container'>";

echo "<h1>🚀 Instalação do Self-Service</h1>";
echo "<p>Configurando o sistema automaticamente...</p><hr>";

// PASSO 1: Verificar requisitos
echo "<div class='step'>";
echo "<h3>📋 Passo 1: Verificando Requisitos</h3>";

// PHP Version
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    $sucessos[] = "✅ PHP $phpVersion (OK)";
} else {
    $erros[] = "❌ PHP $phpVersion (Requer 7.4+)";
}

// Extensões
$extensoes = ['curl', 'gd', 'mbstring', 'xml'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        $sucessos[] = "✅ Extensão $ext instalada";
    } else {
        $avisos[] = "⚠️ Extensão $ext não encontrada (recomendada)";
    }
}

foreach ($sucessos as $msg) echo "<p class='success'>$msg</p>";
foreach ($avisos as $msg) echo "<p class='warning'>$msg</p>";
foreach ($erros as $msg) echo "<p class='error'>$msg</p>";
echo "</div>";

if (!empty($erros)) {
    echo "<div class='alert alert-danger'><strong>Instalação interrompida!</strong> Corrija os erros acima.</div>";
    echo "</div></body></html>";
    exit;
}

// Load paths configuration
require_once __DIR__ . '/inc/paths.php';

// PASSO 2: Criar Diretórios
echo "<div class='step'>";
echo "<h3>📁 Passo 2: Criando Estrutura de Diretórios</h3>";

$diretorios = [
    'data' => __DIR__ . '/data',
    'ebi/i' => INSTANCE_BASE_PATH,
    'ebi/template' => TEMPLATE_PATH,
    'backups' => __DIR__ . '/backups'
];

foreach ($diretorios as $nome => $caminho) {
    if (!file_exists($caminho)) {
        if (mkdir($caminho, 0755, true)) {
            echo "<p class='success'>✅ Diretório $nome criado</p>";
        } else {
            echo "<p class='error'>❌ Erro ao criar $nome</p>";
            $erros[] = "Não foi possível criar $nome";
        }
    } else {
        echo "<p class='warning'>⚠️ Diretório $nome já existe</p>";
    }
}

// Criar .htaccess para proteção
$htaccessContent = "# Proteção de Diretórios
Options -Indexes

<FilesMatch \"\\.(txt|log|ini|bak)$\">
    Order allow,deny
    Deny from all
</FilesMatch>
";

foreach ([__DIR__ . '/data' => 'data', INSTANCE_BASE_PATH => 'ebi/i', __DIR__ . '/backups' => 'backups'] as $path => $name) {
    if (file_exists($path)) {
        file_put_contents($path . '/.htaccess', $htaccessContent);
        echo "<p class='success'>✅ .htaccess criado em $name</p>";
    }
}

echo "</div>";

// PASSO 3: Verificar Template (estrutura refatorada)
echo "<div class='step'>";
echo "<h3>📄 Passo 3: Verificando Template</h3>";

$templateBase = __DIR__ . '/template/';
$arquivosTemplate = [
    'index.php',
    'config.ini',
    'inc/bootstrap.php',
    'inc/auth.php',
    'inc/funcoes.php',
    'inc/actions.php',
    'views/login.php',
    'views/main.php',
];
$templateOk = true;
foreach ($arquivosTemplate as $arq) {
    if (!file_exists($templateBase . $arq)) {
        echo "<p class='warning'>⚠️ Arquivo template/$arq não encontrado</p>";
        $templateOk = false;
    }
}
if ($templateOk) {
    echo "<p class='success'>✅ Template refatorado completo (index.php + inc/ + views/)</p>";
} else {
    $avisos[] = "Template incompleto - certifique-se de que template/ contém a estrutura refatorada";
}

echo "</div>";

// PASSO 4: Testar Permissões
echo "<div class='step'>";
echo "<h3>🔐 Passo 4: Testando Permissões de Escrita</h3>";

$testarDiretorios = [__DIR__ . '/data' => 'data', INSTANCE_BASE_PATH => 'ebi/i', __DIR__ . '/backups' => 'backups'];
foreach ($testarDiretorios as $path => $name) {
    $testFile = $path . '/.test_write';
    if (file_put_contents($testFile, 'test') !== false) {
        unlink($testFile);
        echo "<p class='success'>✅ Permissão de escrita OK em $name</p>";
    } else {
        echo "<p class='error'>❌ Sem permissão de escrita em /$dir</p>";
        $erros[] = "Permissão negada em $dir";
    }
}

echo "</div>";

// PASSO 5: Criar arquivos iniciais
echo "<div class='step'>";
echo "<h3>⚙️ Passo 5: Criando Arquivos Iniciais</h3>";

// Criar arquivos de log vazios
$arquivosLog = [
    'data/selfservice_users.txt',
    'data/instancias_criadas.log',
    'data/instancias_removidas.log',
    'data/erros.log'
];

foreach ($arquivosLog as $arquivo) {
    $caminho = __DIR__ . '/' . $arquivo;
    if (!file_exists($caminho)) {
        $header = "# " . basename($arquivo) . " - Criado em " . date('Y-m-d H:i:s') . "\n";
        file_put_contents($caminho, $header);
        echo "<p class='success'>✅ Criado $arquivo</p>";
    } else {
        echo "<p class='warning'>⚠️ $arquivo já existe</p>";
    }
}

echo "</div>";

// PASSO 6: Configurações de Segurança
echo "<div class='step'>";
echo "<h3>🔒 Passo 6: Configurações de Segurança</h3>";

$senhaAdmin = bin2hex(random_bytes(8)); // Gerar senha aleatória
$senhaAdminHash = password_hash($senhaAdmin, PASSWORD_BCRYPT, ['cost' => 12]);

echo "<div class='alert alert-warning'>";
echo "<h5>⚠️ IMPORTANTE - Senha de Administrador</h5>";
echo "<p>Sua senha temporária de admin foi gerada:</p>";
echo "<pre>$senhaAdmin</pre>";
echo "<p><strong>COPIE ESTA SENHA AGORA!</strong> Ela será necessária para acessar o painel administrativo.</p>";
echo "<p>Você pode alterá-la editando o arquivo <code>admin.php</code></p>";
echo "</div>";

// Atualizar .env com o hash da nova senha (admin.php lê $_ENV['ADMIN_PASSWORD_HASH'])
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    @touch($envFile);
    @chmod($envFile, 0600);
}
$envLinhas = @file($envFile, FILE_IGNORE_NEW_LINES) ?: [];
$encontrou = false;
foreach ($envLinhas as $i => $ln) {
    if (strpos($ln, 'ADMIN_PASSWORD_HASH=') === 0) {
        $envLinhas[$i] = 'ADMIN_PASSWORD_HASH="' . $senhaAdminHash . '"';
        $encontrou = true;
        break;
    }
}
if (!$encontrou) {
    $envLinhas[] = 'ADMIN_PASSWORD_HASH="' . $senhaAdminHash . '"';
}
file_put_contents($envFile, implode("\n", $envLinhas) . "\n", LOCK_EX);
@chmod($envFile, 0600);
echo "<p class='success'>✅ Senha de admin configurada no .env (hash bcrypt)</p>";

echo "</div>";

// PASSO 7: Verificações Finais
echo "<div class='step'>";
echo "<h3>✅ Passo 7: Verificações Finais</h3>";

$arquivosNecessarios = [
    'selfservice.php' => 'Página de cadastro',
    'admin.php' => 'Painel administrativo',
    'criar_instancia.php' => 'Script de criação de instâncias'
];

$tudoOk = true;
foreach ($arquivosNecessarios as $arquivo => $descricao) {
    if (file_exists(__DIR__ . '/' . $arquivo)) {
        echo "<p class='success'>✅ $descricao ($arquivo) encontrado</p>";
    } else {
        echo "<p class='error'>❌ $descricao ($arquivo) NÃO encontrado</p>";
        $tudoOk = false;
    }
}

echo "</div>";

// RESULTADO FINAL
echo "<hr>";
if ($tudoOk && empty($erros)) {
    // Criar arquivo de marcação de instalação
    $infoInstalacao = "Sistema instalado em: " . date('Y-m-d H:i:s') . "\n";
    $infoInstalacao .= "PHP Version: $phpVersion\n";
    $infoInstalacao .= "Senha Admin (texto): $senhaAdmin\n";
    $infoInstalacao .= "Senha Admin (hash) : $senhaAdminHash\n";
    file_put_contents(__DIR__ . '/.instalado', $infoInstalacao);
    @chmod(__DIR__ . '/.instalado', 0600);
    
    echo "<div class='alert alert-success'>";
    echo "<h2>✅ Instalação Concluída com Sucesso!</h2>";
    echo "<h4>Próximos Passos:</h4>";
    echo "<ol>";
    echo "<li><strong>COPIE A SENHA DE ADMIN</strong> mostrada acima</li>";
    echo "<li>Verifique se a pasta <code>template/</code> contém a estrutura refatorada (index.php, inc/, views/, config.ini)</li>";
    echo "<li>Delete este arquivo (install.php) por segurança</li>";
    echo "<li>Acesse <a href='admin.php'>admin.php</a> com a senha gerada</li>";
    echo "<li>Configure seu sistema</li>";
    echo "<li>Compartilhe <a href='selfservice.php'>selfservice.php</a> com os usuários</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='alert alert-info'>";
    echo "<h4>🔗 Links Úteis:</h4>";
    echo "<ul>";
    echo "<li><a href='selfservice.php' target='_blank'>Página de Cadastro</a></li>";
    echo "<li><a href='admin.php' target='_blank'>Painel Administrativo</a></li>";
    echo "<li><a href='README.md' target='_blank'>Documentação Completa</a></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='alert alert-warning'>";
    echo "<h4>⚠️ Segurança:</h4>";
    echo "<ul>";
    echo "<li>Delete o arquivo <strong>install.php</strong> agora!</li>";
    echo "<li>Altere a senha de admin em <code>admin.php</code> se necessário</li>";
    echo "<li>Use HTTPS em produção</li>";
    echo "<li>Faça backups regulares</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<div class='alert alert-danger'>";
    echo "<h2>❌ Instalação Incompleta</h2>";
    echo "<p>Foram encontrados problemas durante a instalação:</p>";
    echo "<ul>";
    foreach ($erros as $erro) {
        echo "<li>$erro</li>";
    }
    echo "</ul>";
    echo "<p>Corrija os problemas e execute este script novamente.</p>";
    echo "</div>";
}

echo "</div></body></html>";
?>
