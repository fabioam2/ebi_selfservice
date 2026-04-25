<?php
/**
 * Painel Administrativo - EBI Self-Service
 *
 * Sistema completo de administração com:
 * - Gerenciamento de usuários (CRUD)
 * - Gerenciamento de instâncias (seleção múltipla, ações em lote)
 * - Configurações do sistema
 * - Documentação
 *
 * @version 3.0
 * @author EBI Team
 */

session_start();

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Carregar .env se existir (cria automaticamente a partir de .env.example se faltar)
$envFileBoot = __DIR__ . '/../.env';
$envExampleBoot = __DIR__ . '/../.env.example';
if (!file_exists($envFileBoot) && file_exists($envExampleBoot)) {
    if (@copy($envExampleBoot, $envFileBoot)) {
        @chmod($envFileBoot, 0600);
    }
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists($envFileBoot)) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
}

// Carregar configuração de caminhos dinâmicos
require_once __DIR__ . '/inc/paths.php';

// Carregar dependências
require_once __DIR__ . '/criar_instancia.php';
require_once __DIR__ . '/inc/user_manager.php';

// Senha de administrador - hash bcrypt (gere com: php -r "echo password_hash('SuaSenha', PASSWORD_BCRYPT, ['cost'=>12]);")
// Senha padrão de fábrica: Senha123!  — TROQUE em produção via .env (ADMIN_PASSWORD_HASH).
$adminPasswordHash = $_ENV['ADMIN_PASSWORD_HASH'] ?? '$2y$12$BPPI8U9mvBmGP/kI0pH/n.PUkkn/cB/9qrOaePiKcVy.vitwF7VsW';
define('SENHA_ADMIN_HASH', $adminPasswordHash);

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

/**
 * Gera token CSRF
 */
function admin_csrf_token() {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

/**
 * Gera campo hidden de CSRF
 */
function admin_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(admin_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Valida token CSRF
 */
function admin_csrf_validate() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals(admin_csrf_token(), $token)) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
        return false;
    }
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

/**
 * Atualiza uma variável no arquivo .env de forma segura
 *
 * @param string $envFile Caminho do arquivo .env
 * @param string $key Nome da variável
 * @param string $value Novo valor (será automaticamente envolvido em aspas simples)
 * @return bool True se atualizado com sucesso, false caso contrário
 */
function updateEnvVariable($envFile, $key, $value) {
    if (!file_exists($envFile)) {
        return false;
    }

    // Valida nome da chave (evita injeção de variáveis arbitrárias)
    if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key)) {
        return false;
    }

    // Formato .env: preferir aspas SIMPLES (não interpretam $, \, etc — valor literal).
    // Em aspas simples não há mecanismo de escape para a propria aspa simples,
    // então se o valor contiver ' fazemos fallback para aspas duplas com escape padrão.
    $valorBruto = str_replace(["\r\n", "\r", "\n"], ' ', (string)$value);
    if (strpos($valorBruto, "'") === false) {
        // Caminho normal: aspas simples, valor literal sem nenhum escape.
        $linhaNova = $key . "='" . $valorBruto . "'";
    } else {
        // Fallback: aspas duplas com escape de \ e "
        $valorEscapado = str_replace(['\\', '"'], ['\\\\', '\\"'], $valorBruto);
        $linhaNova = $key . '="' . $valorEscapado . '"';
    }

    // Ler todas as linhas
    $lines = file($envFile, FILE_IGNORE_NEW_LINES);
    $updated = false;

    // Procurar e substituir a linha
    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }

        if (strpos($line, $key . '=') === 0) {
            $lines[$index] = $linhaNova;
            $updated = true;
            break;
        }
    }

    if (!$updated) {
        $lines[] = $linhaNova;
    }

    $content = implode("\n", $lines) . "\n";
    $ok = file_put_contents($envFile, $content, LOCK_EX) !== false;
    if ($ok) { @chmod($envFile, 0600); }
    return $ok;
}

/**
 * Exibe mensagem de alerta
 */
function exibirAlerta($mensagem, $tipo = 'info') {
    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show">
                ' . htmlspecialchars($mensagem) . '
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>';
}

// ============================================================================
// PROCESSAR LOGIN
// ============================================================================

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (admin_csrf_validate() && password_verify($_POST['senha_admin'] ?? '', SENHA_ADMIN_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin_logado'] = true;
        $_SESSION['admin_login_time'] = time();
        header("Location: admin.php");
        exit;
    } else {
        $erro_login = "Senha incorreta!";
    }
}

// ============================================================================
// PROCESSAR LOGOUT
// ============================================================================

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// ============================================================================
// VERIFICAR SE ESTÁ LOGADO
// ============================================================================

if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Admin - Self-Service</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <style>
            body {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                font-family: 'Inter', sans-serif;
            }
            .login-box {
                background: white;
                padding: 40px;
                border-radius: 15px;
                box-shadow: 0 15px 50px rgba(0,0,0,0.3);
                max-width: 400px;
                width: 100%;
            }
            .login-box h2 {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            .btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
        </style>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="login-box">
            <h2 class="text-center mb-4">🔐 Admin Login</h2>
            <?php if (isset($erro_login)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro_login); ?></div>
            <?php endif; ?>
            <form method="post" action="admin.php">
                <?php echo admin_csrf_field(); ?>
                <div class="form-group">
                    <label><i class="fas fa-lock mr-2"></i>Senha de Administrador</label>
                    <input type="password" name="senha_admin" class="form-control" required autofocus>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt mr-2"></i>Entrar
                </button>
            </form>
            <div class="text-center mt-3">
                <small class="text-muted">EBI Self-Service v3.0</small>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================================
// PROCESSAR AÇÕES DO SISTEMA
// ============================================================================

$mensagem = '';
$tipo_mensagem = '';
$page = $_GET['page'] ?? 'dashboard';

// --- AÇÕES DE INSTÂNCIAS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Remover instância única
    if (isset($_POST['remover_instancia'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
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
        }
    }

    // Remover instâncias em lote
    if (isset($_POST['remover_instancias_lote'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $user_ids = $_POST['instance_ids'] ?? [];
            if (is_array($user_ids) && count($user_ids) > 0) {
                $removidas = 0;
                $erros = 0;

                foreach ($user_ids as $user_id) {
                    $resultado = removerInstancia($user_id);
                    if ($resultado['sucesso']) {
                        $removidas++;
                    } else {
                        $erros++;
                    }
                }

                $mensagem = "{$removidas} instância(s) removida(s) com sucesso";
                if ($erros > 0) {
                    $mensagem .= " ({$erros} erro(s))";
                    $tipo_mensagem = "warning";
                } else {
                    $tipo_mensagem = "success";
                }
            } else {
                $mensagem = "Nenhuma instância selecionada";
                $tipo_mensagem = "warning";
            }
        }
    }

    // Redefinir senha de uma instância (senha informada pelo admin)
    if (isset($_POST['redefinir_senha_instancia'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $user_id = $_POST['user_id'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirma = $_POST['confirma_senha'] ?? '';

            if ($novaSenha !== $confirma) {
                $mensagem = "As senhas não coincidem.";
                $tipo_mensagem = "danger";
            } else {
                $resultado = redefinirSenhaInstancia($user_id, $novaSenha);
                if ($resultado['sucesso']) {
                    $mensagem = "Senha da instância redefinida com sucesso.";
                    $tipo_mensagem = "success";
                } else {
                    $mensagem = "Erro ao redefinir senha: " . $resultado['erro'];
                    $tipo_mensagem = "danger";
                }
            }
        }
    }

    // Enviar nova senha por email (reset + email com senha temporária)
    if (isset($_POST['reset_senha_email'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $user_id = $_POST['user_id'] ?? '';
            if (!validarUserId($user_id)) {
                $mensagem = "ID de usuário inválido.";
                $tipo_mensagem = "danger";
            } else {
                $info = obterInfoInstancia($user_id);
                $emailDestino = $info['EMAIL'] ?? '';
                $nomeDestino = $info['NOME'] ?? 'Usuário';

                if (!$emailDestino || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
                    $mensagem = "Instância sem e-mail válido cadastrado.";
                    $tipo_mensagem = "danger";
                } else {
                    require_once __DIR__ . '/inc/email_manager.php';

                    $senhaTemp = gerarSenhaTemporaria(12);
                    $resetRes = redefinirSenhaInstancia($user_id, $senhaTemp);

                    if (!$resetRes['sucesso']) {
                        $mensagem = "Erro ao redefinir: " . $resetRes['erro'];
                        $tipo_mensagem = "danger";
                    } else {
                        // Monta link da instância
                        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                                 . '://' . $_SERVER['HTTP_HOST'];
                        $rootPath = dirname(dirname($_SERVER['PHP_SELF']));
                        $pathPrefix = ($rootPath === '/') ? '' : $rootPath;
                        $linkInstancia = $baseUrl . $pathPrefix . '/ebi/i/' . $user_id . '/index.php';

                        $mail = enviarEmailResetSenha($emailDestino, $nomeDestino, $linkInstancia, $senhaTemp);

                        // Log
                        $logFile = DATA_PATH . '/admin_actions.log';
                        $linha = date('Y-m-d H:i:s') . " | RESET_SENHA_EMAIL | $user_id | $emailDestino | " . ($mail['sucesso'] ? 'enviado' : 'falha: ' . ($mail['erro'] ?? '?')) . "\n";
                        @file_put_contents($logFile, $linha, FILE_APPEND | LOCK_EX);

                        if ($mail['sucesso']) {
                            $mensagem = "Nova senha enviada por email para $emailDestino.";
                            $tipo_mensagem = "success";
                        } else {
                            $mensagem = "Senha redefinida, mas falha ao enviar email (" . ($mail['erro'] ?? 'desconhecido') . "). Nova senha temporária: " . $senhaTemp;
                            $tipo_mensagem = "warning";
                        }
                    }
                }
            }
        }
    }

    // --- AÇÕES DE USUÁRIOS ---

    // Criar usuário
    if (isset($_POST['criar_usuario'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $resultado = createUser([
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'permissions' => $_POST['permissions'] ?? [],
                'notes' => $_POST['notes'] ?? ''
            ]);

            $mensagem = $resultado['message'];
            $tipo_mensagem = $resultado['success'] ? 'success' : 'danger';
        }
    }

    // Editar usuário
    if (isset($_POST['editar_usuario'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $resultado = updateUser($_POST['user_id'], [
                'username' => $_POST['username'] ?? '',
                'email' => $_POST['email'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'role' => $_POST['role'] ?? 'user',
                'permissions' => $_POST['permissions'] ?? [],
                'notes' => $_POST['notes'] ?? ''
            ]);

            $mensagem = $resultado['message'];
            $tipo_mensagem = $resultado['success'] ? 'success' : 'danger';
        }
    }

    // Bloquear/Desbloquear usuário
    if (isset($_POST['toggle_user_status'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $userId = $_POST['user_id'] ?? '';
            $block = $_POST['action'] === 'block';

            $resultado = toggleUserStatus($userId, $block);
            $mensagem = $resultado['message'];
            $tipo_mensagem = $resultado['success'] ? 'success' : 'danger';
        }
    }

    // Apagar usuário
    if (isset($_POST['apagar_usuario'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $userId = $_POST['user_id'] ?? '';
            $resultado = deleteUser($userId);

            $mensagem = $resultado['message'];
            $tipo_mensagem = $resultado['success'] ? 'success' : 'danger';
        }
    }

    // --- AÇÕES DE CONFIGURAÇÕES ---

    // Alterar senha do admin
    if (isset($_POST['alterar_senha'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $senhaNova = $_POST['senha_nova'] ?? '';
            $senhaConfirmar = $_POST['senha_confirmar'] ?? '';

            if (!password_verify($senhaAtual, SENHA_ADMIN_HASH)) {
                $mensagem = "Senha atual incorreta";
                $tipo_mensagem = "danger";
            } elseif ($senhaNova !== $senhaConfirmar) {
                $mensagem = "As senhas não coincidem";
                $tipo_mensagem = "danger";
            } elseif (strlen($senhaNova) < 8) {
                $mensagem = "A senha deve ter pelo menos 8 caracteres";
                $tipo_mensagem = "danger";
            } else {
                $novoHash = password_hash($senhaNova, PASSWORD_BCRYPT);

                // Atualizar no .env de forma segura
                $envFile = __DIR__ . '/../.env';
                if (file_exists($envFile)) {
                    // Usar função segura que lida corretamente com caracteres especiais
                    if (updateEnvVariable($envFile, 'ADMIN_PASSWORD_HASH', $novoHash)) {
                        // Verificar se foi salvo corretamente
                        $envContent = file_get_contents($envFile);
                        if (strpos($envContent, $novoHash) !== false) {
                            $mensagem = "Senha alterada com sucesso! Faça login novamente.";
                            $tipo_mensagem = "success";

                            // Log da alteração
                            $logFile = DATA_PATH . '/admin_actions.log';
                            $logEntry = date('Y-m-d H:i:s') . " | SENHA_ALTERADA | Admin alterou a senha\n";
                            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

                            // Fazer logout
                            session_destroy();
                            header("Refresh: 3; url=admin.php");
                        } else {
                            $mensagem = "Erro: Senha não foi salva corretamente no .env. Tente novamente.";
                            $tipo_mensagem = "danger";
                        }
                    } else {
                        $mensagem = "Erro ao salvar senha no arquivo .env. Verifique as permissões.";
                        $tipo_mensagem = "danger";
                    }
                } else {
                    $mensagem = "Arquivo .env não encontrado. Crie-o a partir do .env.example";
                    $tipo_mensagem = "danger";
                }
            }
        }
    }

    // Criar .env a partir do .env.example
    if (isset($_POST['criar_env_do_example'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $envFile = __DIR__ . '/../.env';
            $exampleFile = __DIR__ . '/../.env.example';
            if (file_exists($envFile)) {
                $mensagem = "O arquivo .env já existe. Operação cancelada para não sobrescrever.";
                $tipo_mensagem = "warning";
            } elseif (!file_exists($exampleFile)) {
                $mensagem = "Arquivo .env.example não encontrado.";
                $tipo_mensagem = "danger";
            } elseif (@copy($exampleFile, $envFile)) {
                @chmod($envFile, 0600);
                $logFile = DATA_PATH . '/admin_actions.log';
                @file_put_contents($logFile, date('Y-m-d H:i:s') . " | ENV_CRIADO | copiado de .env.example\n", FILE_APPEND | LOCK_EX);
                $mensagem = "Arquivo .env criado com sucesso (cópia do .env.example, permissões 0600).";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Falha ao copiar .env.example para .env. Verifique permissões de escrita.";
                $tipo_mensagem = "danger";
            }
        }
    }

    // Instalar dependências do composer (PHPMailer, Dotenv, Monolog)
    if (isset($_POST['instalar_dependencias'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $projectRoot = realpath(__DIR__ . '/..');
            if (!$projectRoot || !is_dir($projectRoot) || !file_exists($projectRoot . '/composer.json')) {
                $mensagem = "composer.json não encontrado na raiz do projeto.";
                $tipo_mensagem = "danger";
            } elseif (!function_exists('proc_open')) {
                $mensagem = "A função proc_open está desabilitada no PHP (verifique disable_functions). Execute composer manualmente pelo SSH.";
                $tipo_mensagem = "danger";
            } else {
                // Descobrir binário disponível
                $comandos = [
                    'composer install --no-dev --optimize-autoloader --no-interaction 2>&1',
                    'php composer.phar install --no-dev --optimize-autoloader --no-interaction 2>&1',
                ];
                $saida = '';
                $status = -1;
                foreach ($comandos as $cmd) {
                    $descriptors = [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w'],
                    ];
                    $env = array_merge($_ENV, ['COMPOSER_ALLOW_SUPERUSER' => '1', 'HOME' => $projectRoot]);
                    $proc = @proc_open($cmd, $descriptors, $pipes, $projectRoot, $env);
                    if (!is_resource($proc)) continue;
                    fclose($pipes[0]);
                    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
                    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
                    $status = proc_close($proc);
                    $saida = trim($stdout . "\n" . $stderr);
                    if ($status === 0) break;
                    // Se erro foi "command not found" tenta o próximo
                    if (stripos($saida, 'not found') === false && stripos($saida, 'command not found') === false) break;
                }

                $logFile = DATA_PATH . '/admin_actions.log';
                @file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " | COMPOSER_INSTALL | status=$status\n",
                    FILE_APPEND | LOCK_EX
                );

                $_SESSION['composer_output'] = $saida;
                if ($status === 0) {
                    $mensagem = "Dependências instaladas com sucesso. PHPMailer disponível em vendor/.";
                    $tipo_mensagem = "success";
                } else {
                    $mensagem = "Falha ao executar composer (status $status). Veja a saída abaixo ou execute manualmente via SSH.";
                    $tipo_mensagem = "danger";
                }
            }
        }
    }

    // Desbloquear rate limiting (apagar arquivos ratelimit_*.json)
    if (isset($_POST['desbloquear_rate_limit'])) {
        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $modo = $_POST['desbloquear_modo'] ?? 'todos'; // 'todos' | 'meu_ip'
            $rateDir = __DIR__ . '/data/';
            $arquivos = glob($rateDir . 'ratelimit_*.json') ?: [];
            $removidos = 0;

            if ($modo === 'meu_ip') {
                require_once __DIR__ . '/inc/rate_limit.php';
                $meuIp = getClientIP();
                $ipSafe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $meuIp);
                $alvo = $rateDir . 'ratelimit_' . $ipSafe . '.json';
                if (file_exists($alvo) && @unlink($alvo)) {
                    $removidos = 1;
                }
                $descricao = "IP $meuIp";
            } else {
                foreach ($arquivos as $f) {
                    if (@unlink($f)) $removidos++;
                }
                $descricao = "todos os IPs";
            }

            $logFile = DATA_PATH . '/admin_actions.log';
            @file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " | RATE_LIMIT_DESBLOQUEADO | $descricao | removidos=$removidos\n",
                FILE_APPEND | LOCK_EX
            );

            $mensagem = "Rate limit desbloqueado ($descricao). Arquivos removidos: $removidos.";
            $tipo_mensagem = $removidos > 0 ? "success" : "info";
        }
    }

    // Atualizar configurações
    if (isset($_POST['atualizar_config'])) {        if (!admin_csrf_validate()) {
            $mensagem = "Requisição inválida (token de segurança).";
            $tipo_mensagem = "danger";
        } else {
            $envFile = __DIR__ . '/../.env';
            if (file_exists($envFile)) {
                // Atualizar valores usando função segura
                $configs = [
                    'RATE_LIMIT_ENABLED' => $_POST['rate_limit_enabled'] ?? 'false',
                    'RATE_LIMIT_MAX_REQUESTS' => $_POST['rate_limit_max_requests'] ?? '60',
                    'RATE_LIMIT_TIME_WINDOW' => $_POST['rate_limit_time_window'] ?? '60',
                    'ALLOW_MULTIPLE_INSTANCES' => $_POST['allow_multiple_instances'] ?? 'false',
                    'CLEANUP_INACTIVE_HOURS' => $_POST['cleanup_inactive_hours'] ?? '6',
                    'LOG_LEVEL' => $_POST['log_level'] ?? 'warning',
                    'DEBUG_MODE' => $_POST['debug_mode'] ?? 'false',
                    'EMAIL_ENABLED' => $_POST['email_enabled'] ?? 'false',
                    'SMTP_HOST' => $_POST['smtp_host'] ?? 'smtp.hostinger.com',
                    'SMTP_PORT' => $_POST['smtp_port'] ?? '465',
                    'SMTP_USER' => $_POST['smtp_user'] ?? 'no-replay@ebi.ccbcampinas.org.br',
                    'SMTP_SECURE' => $_POST['smtp_secure'] ?? 'ssl',
                    'EMAIL_FROM' => $_POST['email_from'] ?? 'no-replay@ebi.ccbcampinas.org.br',
                    'EMAIL_FROM_NAME' => $_POST['email_from_name'] ?? 'EBI Self-Service'
                ];

                // Só atualizar senha SMTP se foi fornecida
                if (!empty($_POST['smtp_password'])) {
                    $configs['SMTP_PASSWORD'] = $_POST['smtp_password'];
                }

                $success = true;
                foreach ($configs as $key => $value) {
                    if (!updateEnvVariable($envFile, $key, $value)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $mensagem = "Configurações atualizadas com sucesso!";
                    $tipo_mensagem = "success";

                    // Log da alteração
                    $logFile = DATA_PATH . '/admin_actions.log';
                    $logEntry = date('Y-m-d H:i:s') . " | CONFIG_ATUALIZADA | Configurações do sistema atualizadas\n";
                    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
                } else {
                    $mensagem = "Erro ao salvar configurações";
                    $tipo_mensagem = "danger";
                }
            } else {
                $mensagem = "Arquivo .env não encontrado";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// ============================================================================
// OBTER DADOS PARA EXIBIÇÃO
// ============================================================================

// Estatísticas de instâncias
$instancias = listarTodasInstancias();
$totalInstancias = count($instancias);
$instanciasHoje = 0;

foreach ($instancias as $inst) {
    if (isset($inst['DATA_CRIACAO'])) {
        $dataCriacao = date('Y-m-d', strtotime($inst['DATA_CRIACAO']));
        if ($dataCriacao === date('Y-m-d')) {
            $instanciasHoje++;
        }
    }
}

// Estatísticas de usuários
$userStats = getUserStats();
$usuarios = listUsers();

// Carregar configurações do .env
$envFile = __DIR__ . '/../.env';
$configAtual = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $configAtual[trim($key)] = trim($value, "'\"");
        }
    }
}

// ============================================================================
// PÁGINA DE DOCUMENTAÇÃO
// ============================================================================

if ($page === 'docs') {
    $docDir = __DIR__ . '/documentacao/';
    $docSelecionado = $_GET['doc'] ?? '';

    // Listar documentos
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

    // Ler documento selecionado
    $conteudoDoc = '';
    $nomeDoc = '';
    if ($docSelecionado && in_array($docSelecionado, $documentos)) {
        $caminhoDoc = $docDir . $docSelecionado;
        if (file_exists($caminhoDoc)) {
            $conteudoDoc = file_get_contents($caminhoDoc);
            $nomeDoc = $docSelecionado;

            if (pathinfo($docSelecionado, PATHINFO_EXTENSION) === 'md') {
                $conteudoDoc = processarMarkdownSimples($conteudoDoc);
            } else {
                $conteudoDoc = '<pre>' . htmlspecialchars($conteudoDoc) . '</pre>';
            }
        }
    }

    include __DIR__ . '/inc/admin_docs.php';
    exit;
}

/**
 * Processa Markdown simples para HTML
 */
function processarMarkdownSimples($texto) {
    // Headers
    $texto = preg_replace('/^#### (.*?)$/m', '<h4>$1</h4>', $texto);
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

    // Blockquotes
    $texto = preg_replace('/^> (.*)$/m', '<blockquote>$1</blockquote>', $texto);

    // Horizontal rule
    $texto = preg_replace('/^---$/m', '<hr>', $texto);

    // Tables
    $texto = preg_replace_callback('/(\|[^\n]+\|\n)+/', function($matches) {
        $lines = explode("\n", trim($matches[0]));
        $html = '<table class="table table-bordered">';

        foreach ($lines as $i => $line) {
            if (strpos($line, '|---') !== false) continue;

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

    // Cleanup
    $texto = preg_replace('/<p><\/p>/', '', $texto);
    $texto = preg_replace('/<p>(<h[1-6]>)/', '$1', $texto);
    $texto = preg_replace('/(<\/h[1-6]>)<\/p>/', '$1', $texto);
    $texto = preg_replace('/<p>(<ul>)/', '$1', $texto);
    $texto = preg_replace('/(<\/ul>)<\/p>/', '$1', $texto);
    $texto = preg_replace('/<p>(<table)/', '$1', $texto);
    $texto = preg_replace('/(<\/table>)<\/p>/', '$1', $texto);
    $texto = preg_replace('/<p>(<pre>)/', '$1', $texto);
    $texto = preg_replace('/(<\/pre>)<\/p>/', '$1', $texto);

    return $texto;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - Self-Service v3.0</title>

    <!-- CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sidebar {
            background: white;
            min-height: calc(100vh - 76px);
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
        }

        .sidebar .nav-link {
            color: #333;
            padding: 15px 20px;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover {
            background-color: #f8f9fa;
            border-left-color: #667eea;
        }

        .sidebar .nav-link.active {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .stats-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            border: none;
            transition: transform 0.2s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .stats-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card.success { background: linear-gradient(135deg, #56CCF2 0%, #2F80ED 100%); color: white; }
        .stats-card.warning { background: linear-gradient(135deg, #F2994A 0%, #F2C94C 100%); color: white; }
        .stats-card.info { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .stats-card.danger { background: linear-gradient(135deg, #EB3349 0%, #F45C43 100%); color: white; }

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

        .table-custom thead th {
            border: none;
            font-weight: 600;
        }

        .table-custom tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }

        .btn-action {
            padding: 5px 15px;
            font-size: 0.875rem;
            border-radius: 5px;
            margin: 0 2px;
            transition: all 0.2s;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }

        .search-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .content-header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .content-header h2 {
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: 600;
        }

        .badge-active { background-color: #28a745; color: white; }
        .badge-blocked { background-color: #dc3545; color: white; }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .checkbox-lg {
            transform: scale(1.3);
            margin-right: 10px;
        }

        .action-bar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            display: none;
        }

        .action-bar.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="?">
                <i class="fas fa-cogs"></i> Painel Administrativo - Self-Service v3.0
            </a>
            <div>
                <span class="navbar-text text-white mr-3">
                    <i class="fas fa-user"></i> Admin
                </span>
                <a href="?logout=1" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">

            <!-- Sidebar -->
            <nav class="col-md-2 sidebar">
                <div class="nav flex-column nav-pills" role="tablist">
                    <a class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>" href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a class="nav-link <?php echo $page === 'instances' ? 'active' : ''; ?>" href="?page=instances">
                        <i class="fas fa-server"></i> Instâncias
                    </a>
                    <a class="nav-link <?php echo $page === 'users' ? 'active' : ''; ?>" href="?page=users">
                        <i class="fas fa-users"></i> Usuários
                    </a>
                    <a class="nav-link <?php echo $page === 'settings' ? 'active' : ''; ?>" href="?page=settings">
                        <i class="fas fa-cog"></i> Configurações
                    </a>
                    <a class="nav-link <?php echo $page === 'docs' ? 'active' : ''; ?>" href="?page=docs">
                        <i class="fas fa-book"></i> Documentação
                    </a>
                    <hr>
                    <a class="nav-link" href="selfservice.php" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Cadastro
                    </a>
                    <a class="nav-link" href="instal.html" target="_blank">
                        <i class="fas fa-life-ring"></i> Página de Instalação
                    </a>
                    <a class="nav-link" href="diag_email.php" target="_blank">
                        <i class="fas fa-stethoscope"></i> Diagnóstico de Email
                    </a>
                </div>
            </nav>

            <!-- Conteúdo Principal -->
            <main class="col-md-10 ml-sm-auto px-4 py-4">

                <?php if ($mensagem): ?>
                    <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($mensagem); ?>
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>

                <?php
                // Incluir conteúdo da página selecionada
                switch ($page) {
                    case 'dashboard':
                        include __DIR__ . '/inc/admin_dashboard.php';
                        break;
                    case 'instances':
                        include __DIR__ . '/inc/admin_instances.php';
                        break;
                    case 'users':
                        include __DIR__ . '/inc/admin_users.php';
                        break;
                    case 'settings':
                        include __DIR__ . '/inc/admin_settings.php';
                        break;
                    default:
                        echo '<div class="alert alert-warning">Página não encontrada</div>';
                }
                ?>

            </main>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

    <script>
        // Busca na tabela
        function setupTableSearch(inputId, tableId) {
            $('#' + inputId).on('keyup', function() {
                const value = $(this).val().toLowerCase();
                $('#' + tableId + ' tbody tr').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        }

        // Copiar link
        function copiarLink(link) {
            const fullLink = window.location.origin + window.location.pathname.replace('admin.php', '') + link;

            if (navigator.clipboard) {
                navigator.clipboard.writeText(fullLink).then(() => {
                    alert('✅ Link copiado: ' + fullLink);
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
                if (confirm('ATENÇÃO: Todos os dados serão perdidos!\n\nConfirma a remoção?')) {
                    $('#userIdRemover').val(userId);
                    $('#formRemover').submit();
                }
            }
        }

        // Seleção múltipla de checkboxes
        function setupBulkActions() {
            $('#selectAll').on('change', function() {
                $('.instance-checkbox').prop('checked', this.checked);
                updateActionBar();
            });

            $('.instance-checkbox').on('change', function() {
                updateActionBar();

                // Atualizar "Selecionar todos"
                const total = $('.instance-checkbox').length;
                const checked = $('.instance-checkbox:checked').length;
                $('#selectAll').prop('checked', total === checked);
            });
        }

        // Atualizar barra de ações
        function updateActionBar() {
            const checked = $('.instance-checkbox:checked').length;
            if (checked > 0) {
                $('#actionBar').addClass('show');
                $('#selectedCount').text(checked);
            } else {
                $('#actionBar').removeClass('show');
            }
        }

        // Remover selecionados
        function removerSelecionados() {
            const checked = $('.instance-checkbox:checked').length;

            if (checked === 0) {
                alert('Nenhuma instância selecionada');
                return;
            }

            if (confirm(`Tem certeza que deseja remover ${checked} instância(s)?\n\nEsta ação não pode ser desfeita!`)) {
                if (confirm('ATENÇÃO: Todos os dados serão perdidos!\n\nConfirma a remoção?')) {
                    $('#formRemoverLote').submit();
                }
            }
        }

        $(document).ready(function() {
            setupBulkActions();
        });
    </script>

</body>
</html>
