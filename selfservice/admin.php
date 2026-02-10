<?php
session_start();

// Senha de administrador (ALTERE ESTA SENHA!)
define('SENHA_ADMIN', 'Admin@2024!');

// Processar login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if ($_POST['senha_admin'] === SENHA_ADMIN) {
        $_SESSION['admin_logado'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $erro_login = "Senha incorreta!";
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    $_SESSION['admin_logado'] = false;
    header("Location: " . $_SERVER['PHP_SELF']);
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
            <form method="post">
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
            <a href="?logout=1" class="btn btn-light btn-sm">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
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
    <form method="post" id="formRemover" style="display: none;">
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
