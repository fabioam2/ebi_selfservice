<?php
session_start();

// Senha de administrador - hash bcrypt (use password_hash('SuaSenha', PASSWORD_DEFAULT) para gerar)
// Senha padrão: Admin@2024!
define('SENHA_ADMIN_HASH', '$2y$12$zS/zF79Sc2tVmkIppd72xew8.36YCIxFQm1t/dONXx4.1LiH4i/MO');

// --- CSRF ---
function admin_csrf_token() {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function admin_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function admin_csrf_validate() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals(admin_csrf_token(), $token)) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        return false;
    }
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

// Processar login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (admin_csrf_validate() && password_verify($_POST['senha_admin'] ?? '', SENHA_ADMIN_HASH)) {
        $_SESSION['admin_logado'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $erro_login = "Senha incorreta!";
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    $_SESSION['admin_logado'] = false;
    header("Location: admin.php");
    exit;
}

// Verificar se está logado
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin - Self-Service</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .login-box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 400px; width: 100%; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2 class="text-center mb-4">🔐 Admin Login</h2>
            <?php if (isset($erro_login)): ?>
                <div class="alert alert-danger"><?php echo $erro_login; ?></div>
            <?php endif; ?>
            <form method="post" action="admin.php">
                <?php echo admin_csrf_field(); ?>
                <div class="form-group">
                    <label>Senha de Administrador</label>
                    <input type="password" name="senha_admin" class="form-control" required autofocus>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-block">Entrar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Carregar funções
require_once 'criar_instancia.php';

// Processar ações
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['remover_instancia'])) {
    if (!admin_csrf_validate()) {
        $mensagem = "Requisição inválida (token de segurança). Tente novamente.";
        $tipo_mensagem = "danger";
    } else {
    $user_id = $_POST['user_id'] ?? '';
    if ($user_id) {
        $resultado = removerInstancia($user_id);
        if ($resultado['sucesso']) {
            $mensagem = "Instância removida com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao remover instância: " . $resultado['erro'];
            $tipo_mensagem = "danger";
        }
    }
    } // end CSRF valid
}

// Verificar se está acessando página de documentação
if (isset($_GET['page']) && $_GET['page'] === 'docs') {
    exibirPaginaDocumentacao();
    exit;
}

// Obter todas as instâncias
$instancias = listarTodasInstancias();

// Obter estatísticas
$totalInstancias = count($instancias);
$totalUsuarios = 0;
$instanciasHoje = 0;

if (file_exists(__DIR__ . '/data/selfservice_users.txt')) {
    $usuarios = file(__DIR__ . '/data/selfservice_users.txt', FILE_IGNORE_NEW_LINES);
    $totalUsuarios = count($usuarios);
}

foreach ($instancias as $inst) {
    if (isset($inst['DATA_CRIACAO'])) {
        $dataCriacao = date('Y-m-d', strtotime($inst['DATA_CRIACAO']));
        if ($dataCriacao === date('Y-m-d')) {
            $instanciasHoje++;
        }
    }
}

/**
 * Exibe a página de documentação
 */
function exibirPaginaDocumentacao() {
    $docDir = __DIR__ . '/documentacao/';
    $docSelecionado = $_GET['doc'] ?? '';

    // Listar todos os documentos
    $documentos = [];
    if (is_dir($docDir)) {
        $arquivos = scandir($docDir);
        foreach ($arquivos as $arquivo) {
            if (in_array(pathinfo($arquivo, PATHINFO_EXTENSION), ['md', 'txt'])) {
                $documentos[] = $arquivo;
            }
        }
        sort($documentos);
    }

    // Ler conteúdo do documento selecionado
    $conteudoDoc = '';
    $nomeDoc = '';
    if ($docSelecionado && in_array($docSelecionado, $documentos)) {
        $caminhoDoc = $docDir . $docSelecionado;
        if (file_exists($caminhoDoc)) {
            $conteudoDoc = file_get_contents($caminhoDoc);
            $nomeDoc = $docSelecionado;

            // Processar Markdown simples para HTML
            if (pathinfo($docSelecionado, PATHINFO_EXTENSION) === 'md') {
                $conteudoDoc = processarMarkdownSimples($conteudoDoc);
            } else {
                $conteudoDoc = '<pre>' . htmlspecialchars($conteudoDoc) . '</pre>';
            }
        }
    }

    // Renderizar página
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Documentação - Self-Service</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
        <style>
            body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
            .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
            .sidebar { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; max-height: calc(100vh - 100px); overflow-y: auto; }
            .doc-content { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; min-height: calc(100vh - 100px); overflow-y: auto; }
            .doc-item { padding: 10px 15px; margin: 5px 0; border-radius: 5px; cursor: pointer; transition: all 0.2s; }
            .doc-item:hover { background-color: #f0f0f0; }
            .doc-item.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .doc-item i { margin-right: 10px; }
            h1, h2, h3, h4 { margin-top: 1.5rem; margin-bottom: 1rem; }
            h1 { border-bottom: 2px solid #667eea; padding-bottom: 10px; }
            h2 { border-bottom: 1px solid #ddd; padding-bottom: 8px; }
            code { background-color: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
            pre { background-color: #f6f8fa; border: 1px solid #ddd; border-radius: 5px; padding: 15px; overflow-x: auto; }
            pre code { background: none; padding: 0; }
            table { width: 100%; margin: 20px 0; border-collapse: collapse; }
            table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            table th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            table tr:nth-child(even) { background-color: #f9f9f9; }
            .alert-box { padding: 15px; border-radius: 5px; margin: 15px 0; }
            .alert-box.info { background-color: #d1ecf1; border-left: 4px solid #0c5460; }
            .alert-box.warning { background-color: #fff3cd; border-left: 4px solid #856404; }
            .alert-box.success { background-color: #d4edda; border-left: 4px solid #155724; }
            blockquote { border-left: 4px solid #667eea; padding-left: 15px; margin: 20px 0; color: #666; font-style: italic; }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <!-- Navbar -->
        <nav class="navbar navbar-dark navbar-custom">
            <div class="container-fluid">
                <a class="navbar-brand" href="?">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <span class="navbar-text text-white">
                    <i class="fas fa-book"></i> Documentação Self-Service
                </span>
            </div>
        </nav>

        <div class="container-fluid mt-4">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3">
                    <div class="sidebar">
                        <h5 class="mb-3"><i class="fas fa-list"></i> Documentos</h5>
                        <?php if (empty($documentos)): ?>
                            <p class="text-muted">Nenhum documento encontrado.</p>
                        <?php else: ?>
                            <?php foreach ($documentos as $doc): ?>
                                <a href="?page=docs&doc=<?php echo urlencode($doc); ?>" class="doc-item d-block text-decoration-none <?php echo $doc === $nomeDoc ? 'active' : 'text-dark'; ?>">
                                    <?php
                                    $ext = pathinfo($doc, PATHINFO_EXTENSION);
                                    $icon = $ext === 'md' ? 'fa-file-alt' : 'fa-file-code';
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars(str_replace(['_', '.md', '.txt'], [' ', '', ''], $doc)); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Content -->
                <div class="col-md-9">
                    <div class="doc-content">
                        <?php if ($conteudoDoc): ?>
                            <?php echo $conteudoDoc; ?>
                        <?php else: ?>
                            <div class="text-center text-muted" style="padding: 100px 0;">
                                <i class="fas fa-book-open" style="font-size: 4rem; margin-bottom: 20px;"></i>
                                <h3>Selecione um documento</h3>
                                <p>Escolha um documento da lista ao lado para visualizar seu conteúdo.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
        <script>hljs.highlightAll();</script>
    </body>
    </html>
    <?php
}

/**
 * Processa Markdown simples para HTML
 */
function processarMarkdownSimples($texto) {
    // Headers
    $texto = preg_replace('/^### (.*?)$/m', '<h3>$1</h3>', $texto);
    $texto = preg_replace('/^## (.*?)$/m', '<h2>$1</h2>', $texto);
    $texto = preg_replace('/^# (.*?)$/m', '<h1>$1</h1>', $texto);

    // Bold e Italic
    $texto = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $texto);
    $texto = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $texto);
    $texto = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $texto);

    // Code blocks
    $texto = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) {
        $lang = $matches[1] ?? '';
        $code = htmlspecialchars($matches[2]);
        return "<pre><code class='language-$lang'>$code</code></pre>";
    }, $texto);

    // Inline code
    $texto = preg_replace('/`([^`]+)`/', '<code>$1</code>', $texto);

    // Links
    $texto = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $texto);

    // Lists
    $texto = preg_replace('/^\- (.*)$/m', '<li>$1</li>', $texto);
    $texto = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $texto);

    // Numbered lists
    $texto = preg_replace('/^\d+\. (.*)$/m', '<li>$1</li>', $texto);

    // Blockquotes
    $texto = preg_replace('/^> (.*)$/m', '<blockquote>$1</blockquote>', $texto);

    // Horizontal rule
    $texto = preg_replace('/^---$/m', '<hr>', $texto);

    // Tables (basic)
    $texto = preg_replace_callback('/(\|[^\n]+\|\n)+/', function($matches) {
        $lines = explode("\n", trim($matches[0]));
        $html = '<table class="table table-bordered">';

        foreach ($lines as $i => $line) {
            if (strpos($line, '|---') !== false) continue; // Skip separator

            $cells = array_map('trim', explode('|', trim($line, '|')));
            $tag = $i === 0 ? 'th' : 'td';
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= "<$tag>" . trim($cell) . "</$tag>";
            }
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }, $texto);

    // Paragraphs
    $texto = preg_replace('/\n\n/', '</p><p>', $texto);
    $texto = '<p>' . $texto . '</p>';

    // Clean up
    $texto = preg_replace('/<p><\/p>/', '', $texto);
    $texto = preg_replace('/<p>(<h[1-6]>)/','$1', $texto);
    $texto = preg_replace('/(<\/h[1-6]>)<\/p>/','$1', $texto);
    $texto = preg_replace('/<p>(<ul>)/','$1', $texto);
    $texto = preg_replace('/(<\/ul>)<\/p>/','$1', $texto);
    $texto = preg_replace('/<p>(<table)/','$1', $texto);
    $texto = preg_replace('/(<\/table>)<\/p>/','$1', $texto);
    $texto = preg_replace('/<p>(<pre>)/','$1', $texto);
    $texto = preg_replace('/(<\/pre>)<\/p>/','$1', $texto);
    $texto = preg_replace('/<p>(<hr>)/','$1', $texto);
    $texto = preg_replace('/(<hr>)<\/p>/','$1', $texto);

    return $texto;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Self-Service</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        
        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border: none;
        }
        
        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stats-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card.success { background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%); color: white; }
        .stats-card.warning { background: linear-gradient(135deg, #F2994A 0%, #F2C94C 100%); color: white; }
        
        .table-custom {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-action {
            padding: 5px 15px;
            font-size: 0.875rem;
            border-radius: 5px;
            margin: 0 2px;
        }
        
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-cogs"></i> Painel Administrativo - Self-Service
            </a>
            <div>
                <a href="?page=docs" class="btn btn-info btn-sm mr-2">
                    <i class="fas fa-book"></i> Documentação
                </a>
                <a href="?logout=1" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Conteúdo -->
    <div class="container-fluid mt-4">
        
        <?php if ($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($mensagem); ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <!-- Cards de Estatísticas -->
        <div class="row">
            <div class="col-md-4">
                <div class="card stats-card primary">
                    <div class="text-center">
                        <i class="fas fa-server icon"></i>
                        <h3 class="mb-0"><?php echo $totalInstancias; ?></h3>
                        <p class="mb-0">Instâncias Criadas</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stats-card success">
                    <div class="text-center">
                        <i class="fas fa-users icon"></i>
                        <h3 class="mb-0"><?php echo $totalUsuarios; ?></h3>
                        <p class="mb-0">Usuários Cadastrados</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stats-card warning">
                    <div class="text-center">
                        <i class="fas fa-calendar-day icon"></i>
                        <h3 class="mb-0"><?php echo $instanciasHoje; ?></h3>
                        <p class="mb-0">Criadas Hoje</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Busca e Filtros -->
        <div class="search-box">
            <div class="row">
                <div class="col-md-6">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nome, email, cidade ou comum...">
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="selfservice.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Acessar Página de Cadastro
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Tabela de Instâncias -->
        <div class="table-custom">
            <table class="table table-hover mb-0" id="tabelaInstancias">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Cidade</th>
                        <th>Comum</th>
                        <th>Data Criação</th>
                        <th>User ID</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($instancias)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Nenhuma instância criada ainda</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($instancias as $inst): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($inst['NOME'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($inst['EMAIL'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($inst['CIDADE'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($inst['COMUM'] ?? 'N/A'); ?></td>
                                <td><?php echo isset($inst['DATA_CRIACAO']) ? date('d/m/Y H:i', strtotime($inst['DATA_CRIACAO'])) : 'N/A'; ?></td>
                                <td><small><code><?php echo htmlspecialchars($inst['user_id'] ?? 'N/A'); ?></code></small></td>
                                <td class="text-center">
                                    <?php
                                    $link = 'instances/' . ($inst['user_id'] ?? '') . '/public_html/ebi/index.php';
                                    ?>
                                    <a href="<?php echo $link; ?>" target="_blank" class="btn btn-sm btn-info btn-action" title="Acessar Sistema">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <button class="btn btn-sm btn-primary btn-action" onclick="copiarLink('<?php echo $link; ?>')" title="Copiar Link">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btn-action" onclick="confirmarRemocao('<?php echo htmlspecialchars($inst['user_id'] ?? ''); ?>', '<?php echo htmlspecialchars($inst['NOME'] ?? 'este usuário'); ?>')" title="Remover">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-center mt-4 mb-4">
            <p class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total de <?php echo $totalInstancias; ?> instância(s) | 
                Última atualização: <?php echo date('d/m/Y H:i:s'); ?>
            </p>
        </div>
    </div>
    
    <!-- Form oculto para remoção -->
    <form method="post" action="admin.php" id="formRemover" style="display: none;">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="user_id" id="userIdRemover">
        <input type="hidden" name="remover_instancia" value="1">
    </form>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Busca na tabela
        $('#searchInput').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#tabelaInstancias tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Copiar link
        function copiarLink(link) {
            const fullLink = window.location.origin + window.location.pathname.replace('admin.php', '') + link;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(fullLink).then(() => {
                    alert('Link copiado: ' + fullLink);
                }).catch(() => {
                    prompt('Copie o link:', fullLink);
                });
            } else {
                prompt('Copie o link:', fullLink);
            }
        }
        
        // Confirmar remoção
        function confirmarRemocao(userId, nome) {
            if (confirm('Tem certeza que deseja remover a instância de "' + nome + '"?\n\nEsta ação não pode ser desfeita!')) {
                if (confirm('ATENÇÃO: Todos os dados desta instância serão perdidos!\n\nConfirma a remoção?')) {
                    $('#userIdRemover').val(userId);
                    $('#formRemover').submit();
                }
            }
        }
        
        // Auto-refresh a cada 30 segundos (opcional)
        // setTimeout(function(){ location.reload(); }, 30000);
    </script>
</body>
</html>
