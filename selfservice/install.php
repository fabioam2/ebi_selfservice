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

// PASSO 2: Criar Diretórios
echo "<div class='step'>";
echo "<h3>📁 Passo 2: Criando Estrutura de Diretórios</h3>";

$diretorios = [
    'data',
    'instances',
    'backups',
    'template'
];

foreach ($diretorios as $dir) {
    $caminho = __DIR__ . '/' . $dir;
    if (!file_exists($caminho)) {
        if (mkdir($caminho, 0755, true)) {
            echo "<p class='success'>✅ Diretório /$dir criado</p>";
        } else {
            echo "<p class='error'>❌ Erro ao criar /$dir</p>";
            $erros[] = "Não foi possível criar $dir";
        }
    } else {
        echo "<p class='warning'>⚠️ Diretório /$dir já existe</p>";
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

foreach (['data', 'instances', 'backups'] as $dir) {
    file_put_contents(__DIR__ . '/' . $dir . '/.htaccess', $htaccessContent);
    echo "<p class='success'>✅ .htaccess criado em /$dir</p>";
}

echo "</div>";

// PASSO 3: Verificar Template
echo "<div class='step'>";
echo "<h3>📄 Passo 3: Verificando Template</h3>";

$templateFile = __DIR__ . '/template/ebi.txt';
if (!file_exists($templateFile)) {
    echo "<p class='warning'>⚠️ Arquivo template/ebi.txt não encontrado</p>";
    echo "<p>Por favor, copie seu arquivo do sistema para <code>template/ebi.txt</code></p>";
    $avisos[] = "Template não configurado";
} else {
    echo "<p class='success'>✅ Template encontrado (" . number_format(filesize($templateFile) / 1024, 2) . " KB)</p>";
}

echo "</div>";

// PASSO 4: Testar Permissões
echo "<div class='step'>";
echo "<h3>🔐 Passo 4: Testando Permissões de Escrita</h3>";

$testarDiretorios = ['data', 'instances', 'backups'];
foreach ($testarDiretorios as $dir) {
    $testFile = __DIR__ . '/' . $dir . '/.test_write';
    if (file_put_contents($testFile, 'test') !== false) {
        unlink($testFile);
        echo "<p class='success'>✅ Permissão de escrita OK em /$dir</p>";
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

echo "<div class='alert alert-warning'>";
echo "<h5>⚠️ IMPORTANTE - Senha de Administrador</h5>";
echo "<p>Sua senha temporária de admin foi gerada:</p>";
echo "<pre>$senhaAdmin</pre>";
echo "<p><strong>COPIE ESTA SENHA AGORA!</strong> Ela será necessária para acessar o painel administrativo.</p>";
echo "<p>Você pode alterá-la editando o arquivo <code>admin.php</code></p>";
echo "</div>";

// Atualizar admin.php com a nova senha
$adminFile = __DIR__ . '/admin.php';
if (file_exists($adminFile)) {
    $adminContent = file_get_contents($adminFile);
    $adminContent = str_replace("define('SENHA_ADMIN', 'Admin@2024!');", "define('SENHA_ADMIN', '$senhaAdmin');", $adminContent);
    file_put_contents($adminFile, $adminContent);
    echo "<p class='success'>✅ Senha de admin configurada</p>";
}

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
    $infoInstalacao .= "Senha Admin: $senhaAdmin\n";
    file_put_contents(__DIR__ . '/.instalado', $infoInstalacao);
    
    echo "<div class='alert alert-success'>";
    echo "<h2>✅ Instalação Concluída com Sucesso!</h2>";
    echo "<h4>Próximos Passos:</h4>";
    echo "<ol>";
    echo "<li><strong>COPIE A SENHA DE ADMIN</strong> mostrada acima</li>";
    echo "<li>Copie seu arquivo do sistema para <code>template/ebi.txt</code> (se ainda não fez)</li>";
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
