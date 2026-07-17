<?php
session_start();

// Headers de segurança
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// Carregar .env se existir
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\Dotenv') && file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
    }
}

// Rate Limiting (proteção contra abuso)
require_once __DIR__ . '/inc/rate_limit.php';

// Verificar rate limit antes de processar qualquer requisição
$clientIP = getClientIP();
$maxRequests = (int)($_ENV['RATE_LIMIT_MAX_REQUESTS'] ?? 60); // Ler do .env
$timeWindow = (int)($_ENV['RATE_LIMIT_TIME_WINDOW'] ?? 60); // Ler do .env

if (!checkRateLimit($clientIP, $maxRequests, $timeWindow)) {
    $status = getRateLimitStatus($clientIP, $maxRequests, $timeWindow);
    showRateLimitError($status['reset_in']);
    // showRateLimitError() faz exit, código não continua
}

// Load paths configuration
require_once __DIR__ . '/inc/paths.php';
require_once __DIR__ . '/inc/db_manager.php';
require_once __DIR__ . '/inc/email_manager.php';

// Cria diretório de instâncias se necessário
if (!file_exists(INSTANCE_BASE_PATH)) {
    mkdir(INSTANCE_BASE_PATH, 0755, true);
}

// --- CSRF ---
function ss_csrf_token() {
    if (empty($_SESSION['ss_csrf_token'])) {
        $_SESSION['ss_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['ss_csrf_token'];
}

function ss_csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(ss_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function ss_csrf_validate() {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals(ss_csrf_token(), $token)) {
        $_SESSION['ss_csrf_token'] = bin2hex(random_bytes(32));
        return false;
    }
    $_SESSION['ss_csrf_token'] = bin2hex(random_bytes(32));
    return true;
}

$mensagem = '';
$tipo_mensagem = '';

// ============================================================================
// PROCESSAR AÇÃO DE RECUPERAR CONTAS POR EMAIL
// ============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['recuperar_conta_email'])) {
    if (!ss_csrf_validate()) {
        $tipo_mensagem = 'danger';
        $mensagem = 'Requisição inválida (token de segurança). Tente novamente.';
    } else {
        $emailRecuperacao = trim((string)($_POST['email_recuperacao'] ?? ''));

        if ($emailRecuperacao === '') {
            $tipo_mensagem = 'danger';
            $mensagem = 'Informe o e-mail no campo acima para recuperar a conta.';
        } elseif (!filter_var($emailRecuperacao, FILTER_VALIDATE_EMAIL)) {
            $tipo_mensagem = 'danger';
            $mensagem = 'Informe um e-mail válido para recuperar a conta.';
        } else {
            $usuarios = db_listar_usuarios_por_email($emailRecuperacao);

            if (empty($usuarios)) {
                $tipo_mensagem = 'info';
                $mensagem = 'Nenhuma conta foi encontrada para este e-mail.';
            } else {
                $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                         . '://' . $_SERVER['HTTP_HOST'];
                $rootPath = dirname(dirname($_SERVER['PHP_SELF']));
                $pathPrefix = ($rootPath === '/' || $rootPath === '\\') ? '' : $rootPath;

                $contas = [];
                foreach ($usuarios as $u) {
                    $contas[] = [
                        'nome' => (string)($u['nome'] ?? ''),
                        'cidade' => (string)($u['cidade'] ?? ''),
                        'comum' => (string)($u['comum'] ?? ''),
                        'link' => $baseUrl . $pathPrefix . '/ebi/i/' . (string)$u['user_id'] . '/index.php',
                    ];
                }

                $nomeDestinatario = (string)($usuarios[0]['nome'] ?? 'Usuário');
                $envio = enviarEmailRecuperacaoContas($emailRecuperacao, $nomeDestinatario, $contas);

                if (!empty($envio['sucesso'])) {
                    $tipo_mensagem = 'success';
                    $mensagem = 'Enviamos os links das contas para o e-mail informado.';
                } else {
                    $tipo_mensagem = 'danger';
                    $mensagem = 'Não foi possível enviar o e-mail de recuperação: ' . htmlspecialchars((string)($envio['erro'] ?? 'erro desconhecido'));
                }
            }
        }
    }
}

// ============================================================================
// PROCESSAR AÇÃO DE APAGAR INSTÂNCIA EXISTENTE
// ============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apagar_instancia'])) {
    if (!ss_csrf_validate()) {
        $tipo_mensagem = 'danger';
        $mensagem = 'Requisição inválida (token de segurança). Tente novamente.';
    } else {
        $user_id_existente = $_SESSION['user_id_existente'] ?? '';
        $senha_admin_digitada = $_POST['senha_admin'] ?? '';

        if (empty($user_id_existente)) {
            $tipo_mensagem = 'danger';
            $mensagem = 'Sessão expirada. Tente novamente.';
        } else {
            require_once 'criar_instancia.php';

            // Obter senha admin do config.ini da instância
            $configFile = INSTANCE_BASE_PATH . '/' . $user_id_existente . '/config.ini';
            // Compat com instâncias antigas
            if (!file_exists($configFile)) {
                $legado = INSTANCE_BASE_PATH . '/' . $user_id_existente . '/config/config.ini';
                if (file_exists($legado)) {
                    $configFile = $legado;
                }
            }

            if (!file_exists($configFile)) {
                $tipo_mensagem = 'danger';
                $mensagem = 'Configuração da instância não encontrada.';
            } else {
                $config = parse_ini_file($configFile, true);
                $hashAdmin = (string)($config['SEGURANCA']['SENHA_ADMIN_HASH'] ?? '');
                $plainLegado = (string)($config['SEGURANCA']['SENHA_ADMIN_REAL'] ?? '');

                $senhaOk = false;
                if ($hashAdmin !== '' && preg_match('/^\$2[aby]\$/', $hashAdmin)) {
                    $senhaOk = password_verify($senha_admin_digitada, $hashAdmin);
                } elseif ($plainLegado !== '') {
                    $senhaOk = hash_equals($plainLegado, $senha_admin_digitada);
                }

                if ($senhaOk) {
                    // Senha correta, pode apagar
                    $resultado = removerInstancia($user_id_existente);

                    if ($resultado['sucesso']) {
                        $tipo_mensagem = 'success';
                        $mensagem = 'Instância removida com sucesso! Você pode criar uma nova conta agora.';
                        unset($_SESSION['user_id_existente']);
                        unset($_SESSION['instancia_existente']);
                    } else {
                        $tipo_mensagem = 'danger';
                        $mensagem = 'Erro ao remover instância: ' . $resultado['erro'];
                    }
                } else {
                    $tipo_mensagem = 'danger';
                    $mensagem = 'Senha de administrador incorreta!';
                }
            }
        }
    }
}

// ============================================================================
// PROCESSAR AÇÃO DE CRIAR NOVA INSTÂNCIA (ignorar existente)
// ============================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['criar_nova_instancia'])) {
    if (!ss_csrf_validate()) {
        $tipo_mensagem = 'danger';
        $mensagem = 'Requisição inválida (token de segurança). Tente novamente.';
    } else {
        // Usar dados da sessão
        $nome = $_SESSION['dados_cadastro']['nome'] ?? '';
        $email = $_SESSION['dados_cadastro']['email'] ?? '';
        $cidade = $_SESSION['dados_cadastro']['cidade'] ?? '';
        $comum = $_SESSION['dados_cadastro']['comum'] ?? '';
        $senha = $_SESSION['dados_cadastro']['senha'] ?? '';

        if (empty($nome) || empty($email) || empty($senha)) {
            $tipo_mensagem = 'danger';
            $mensagem = 'Dados do cadastro não encontrados. Por favor, preencha o formulário novamente.';
            unset($_SESSION['instancia_existente']);
            unset($_SESSION['user_id_existente']);
        } else {
            // Gerar novo ID único
            $user_id = uniqid('user_', true);
            $hash_senha = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

            // Registrar usuário no banco central
            db_inserir_usuario($user_id, $email, $nome, $cidade, $comum, $hash_senha);

            // Criar instância do sistema para o usuário
            require_once 'criar_instancia.php';
            $resultado = criarInstanciaUsuario($user_id, $nome, $email, $cidade, $comum, $senha);

            if ($resultado['sucesso']) {
                // Tentar enviar email com dados de acesso
                require_once 'inc/email_manager.php';
                $resultadoEmail = enviarEmailAcesso($email, $nome, $resultado['link'], $cidade, $comum);

                // Guardar resultado do email na sessão (apenas para informação, não bloqueia)
                if ($resultadoEmail['sucesso']) {
                    $_SESSION['email_enviado'] = true;
                } else {
                    $_SESSION['email_enviado'] = false;
                    $_SESSION['email_erro'] = $resultadoEmail['erro'] ?? 'Erro desconhecido';
                }

                $_SESSION['cadastro_sucesso'] = true;
                $_SESSION['link_sistema'] = $resultado['link'];
                $_SESSION['user_id'] = $user_id;
                unset($_SESSION['instancia_existente']);
                unset($_SESSION['user_id_existente']);
                unset($_SESSION['dados_cadastro']);
                header("Location: selfservice.php");
                exit;
            } else {
                $tipo_mensagem = 'danger';
                $mensagem = "Erro ao criar sua instância: " . $resultado['erro'];
            }
        }
    }
}

// Processar formulário de cadastro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cadastrar'])) {
    if (!ss_csrf_validate()) {
        $tipo_mensagem = 'danger';
        $mensagem = 'Requisição inválida (token de segurança). Tente novamente.';
    } else {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $comum = trim($_POST['comum'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = "Nome é obrigatório";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros[] = "Email válido é obrigatório";
    }
    
    if (empty($cidade)) {
        $erros[] = "Cidade é obrigatória";
    }
    
    if (empty($comum)) {
        $erros[] = "Comum é obrigatório";
    }
    
    if (empty($senha) || strlen($senha) < 6) {
        $erros[] = "Senha deve ter no mínimo 6 caracteres";
    }
    
    if ($senha !== $confirmar_senha) {
        $erros[] = "As senhas não coincidem";
    }
    
    // Verificar se já existe instância com esse email
    $instanciaExistente = null;
    $user_id_existente = null;

    if (empty($erros)) {
        $usuarioExistente = db_buscar_usuario_por_email($email);
        if ($usuarioExistente !== null) {
            $user_id_existente = $usuarioExistente['user_id'];
            require_once 'criar_instancia.php';
            if (verificarInstanciaExiste($user_id_existente)) {
                $instanciaExistente = obterInfoInstancia($user_id_existente);
            }
        }
    }

    if (empty($erros) && $instanciaExistente !== null) {
        // Já existe instância! Guardar dados e mostrar tela de confirmação
        $_SESSION['instancia_existente'] = $instanciaExistente;
        $_SESSION['user_id_existente'] = $user_id_existente;
        $_SESSION['dados_cadastro'] = [
            'nome' => $nome,
            'email' => $email,
            'cidade' => $cidade,
            'comum' => $comum,
            'senha' => $senha
        ];

        // Gerar link da instância existente
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
                   . "://" . $_SERVER['HTTP_HOST'];

        // Obter o caminho raiz do projeto (suba dois níveis a partir de PHP_SELF)
        // Exemplo: /dev2/selfservice/selfservice.php -> /dev2
        $rootPath = dirname(dirname($_SERVER['PHP_SELF']));

        // Evitar duplo slash se o sistema estiver na raiz
        $pathPrefix = ($rootPath === '/') ? '' : $rootPath;

        // Construir o link usando o caminho raiz dinâmico
        $_SESSION['link_instancia_existente'] = $baseUrl . $pathPrefix . '/ebi/i/' . $user_id_existente . '/index.php';

        header("Location: selfservice.php");
        exit;
    }

    if (empty($erros)) {
        // Gerar ID único para o usuário
        $user_id = uniqid('user_', true);
        $hash_senha = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        // Registrar usuário no banco central
        db_inserir_usuario($user_id, $email, $nome, $cidade, $comum, $hash_senha);

        // Criar instância do sistema para o usuário
        require_once 'criar_instancia.php';
        $resultado = criarInstanciaUsuario($user_id, $nome, $email, $cidade, $comum, $senha);

        if ($resultado['sucesso']) {
            // Tentar enviar email com dados de acesso
            require_once 'inc/email_manager.php';
            $resultadoEmail = enviarEmailAcesso($email, $nome, $resultado['link'], $cidade, $comum);

            // Guardar resultado do email na sessão (apenas para informação, não bloqueia)
            if ($resultadoEmail['sucesso']) {
                $_SESSION['email_enviado'] = true;
            } else {
                $_SESSION['email_enviado'] = false;
                $_SESSION['email_erro'] = $resultadoEmail['erro'] ?? 'Erro desconhecido';
            }

            $_SESSION['cadastro_sucesso'] = true;
            $_SESSION['link_sistema'] = $resultado['link'];
            $_SESSION['user_id'] = $user_id;
            header("Location: selfservice.php");
            exit;
        } else {
            $tipo_mensagem = 'danger';
            $mensagem = "Erro ao criar sua instância: " . $resultado['erro'];
        }
    } else {
        $tipo_mensagem = 'danger';
        $mensagem = implode('<br>', $erros);
    }
    } // end CSRF valid
}

// Verificar se acabou de cadastrar
$mostrar_sucesso = false;
$link_sistema = '';
$email_enviado = false;
$email_erro = '';
if (isset($_SESSION['cadastro_sucesso'])) {
    $mostrar_sucesso = true;
    $link_sistema = $_SESSION['link_sistema'] ?? '';
    $email_enviado = $_SESSION['email_enviado'] ?? false;
    $email_erro = $_SESSION['email_erro'] ?? '';
    unset($_SESSION['cadastro_sucesso']);
    unset($_SESSION['link_sistema']);
    unset($_SESSION['email_enviado']);
    unset($_SESSION['email_erro']);
}

// Verificar se encontrou instância existente
$mostrar_instancia_existente = false;
$instancia_info = null;
$link_instancia_existente = '';
if (isset($_SESSION['instancia_existente'])) {
    $mostrar_instancia_existente = true;
    $instancia_info = $_SESSION['instancia_existente'];
    $link_instancia_existente = $_SESSION['link_instancia_existente'] ?? '';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Service - Sistema de Cadastro de Crianças</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-1: #0f766e;
            --bg-2: #0b4f8a;
            --bg-3: #f59e0b;
            --surface: #ffffff;
            --surface-border: rgba(15, 23, 42, 0.08);
            --text-main: #10273b;
            --text-soft: #4b647c;
            --brand: #0e7490;
            --brand-strong: #0b5f76;
            --brand-soft: rgba(14, 116, 144, 0.14);
            --success-bg: #dff8ea;
            --success-border: #1f9d61;
            --warning-bg: #fff4dc;
            --warning-border: #e8a100;
            --danger: #b91c1c;
        }

        body {
            background: radial-gradient(circle at 8% 8%, rgba(245, 158, 11, 0.32) 0%, rgba(245, 158, 11, 0) 35%),
                        radial-gradient(circle at 94% 16%, rgba(20, 184, 166, 0.38) 0%, rgba(20, 184, 166, 0) 40%),
                        linear-gradient(130deg, var(--bg-1) 0%, var(--bg-2) 58%, #083358 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-family: 'Manrope', sans-serif;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 999px;
            z-index: 0;
            filter: blur(0.5px);
            animation: floatGlow 11s ease-in-out infinite alternate;
            pointer-events: none;
        }

        body::before {
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.42), rgba(245, 158, 11, 0));
            left: -40px;
            top: 28%;
        }

        body::after {
            width: 270px;
            height: 270px;
            background: radial-gradient(circle, rgba(45, 212, 191, 0.35), rgba(45, 212, 191, 0));
            right: -70px;
            bottom: 6%;
            animation-delay: 1.4s;
        }
        
        .selfservice-container {
            position: relative;
            z-index: 1;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.97));
            padding: 40px;
            border-radius: 22px;
            border: 1px solid var(--surface-border);
            box-shadow: 0 18px 48px rgba(1, 27, 49, 0.33);
            width: 100%;
            max-width: 600px;
            animation: slideIn 0.55s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-18px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes floatGlow {
            from {
                transform: translateY(0px);
            }
            to {
                transform: translateY(-14px);
            }
        }
        
        .selfservice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .selfservice-header h1 {
            color: var(--text-main);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        
        .selfservice-header p {
            color: var(--text-soft);
            font-size: 0.95rem;
        }
        
        .icon-header {
            font-size: 3.15rem;
            color: var(--brand);
            margin-bottom: 14px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #173146;
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 11px;
            border: 1px solid #ced9e4;
            padding: 12px 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            background: #fbfdff;
        }
        
        .form-control:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 0.19rem var(--brand-soft);
            transform: translateY(-1px);
            background: #fff;
        }
        
        .btn-cadastrar {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
            border: none;
            border-radius: 11px;
            padding: 12px 30px;
            font-weight: 700;
            color: white;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }
        
        .btn-cadastrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(14, 116, 144, 0.4);
            filter: brightness(1.03);
        }

        .btn-recuperar-conta {
            background: #fff;
            color: var(--brand-strong);
            border: 1px solid rgba(14, 116, 144, 0.35);
            border-radius: 11px;
            padding: 11px 22px;
            font-weight: 700;
            width: 100%;
            margin-top: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }

        .btn-recuperar-conta:hover {
            background: #f2fbff;
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(14, 116, 144, 0.15);
        }
        
        .sucesso-box {
            background: linear-gradient(180deg, #f3fff8 0%, var(--success-bg) 100%);
            border: 1px solid var(--success-border);
            border-radius: 14px;
            padding: 30px;
            text-align: center;
            animation: pulse 0.5s ease-out;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.95); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .sucesso-box i {
            font-size: 4rem;
            color: var(--success-border);
            margin-bottom: 20px;
        }
        
        .sucesso-box h2 {
            color: #14532d;
            margin-bottom: 15px;
        }
        
        .link-sistema {
            background-color: #fff;
            border: 1px solid var(--success-border);
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.8);
        }
        
        .link-sistema a {
            color: var(--brand-strong);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .link-warning {
            border-color: var(--warning-border);
            background: linear-gradient(180deg, #fffdf7, #fff8e7);
        }
        
        .btn-acessar {
            background: linear-gradient(135deg, #1f9d61 0%, #17784a 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 11px;
            font-weight: 700;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-acessar:hover {
            transform: translateY(-2px);
            color: #fff;
            box-shadow: 0 7px 16px rgba(31, 157, 97, 0.35);
        }
        
        .info-box {
            background: #eff9ff;
            border-left: 4px solid var(--brand);
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 9px;
            color: #20455f;
        }
        
        .info-box i {
            color: var(--brand);
            margin-right: 10px;
        }

        .instance-found-box {
            background: var(--warning-bg);
            border-left-color: var(--warning-border);
        }

        .instance-found-box i {
            color: #d28d00;
        }

        .card.border-danger {
            border-width: 1px !important;
            border-color: rgba(185, 28, 28, 0.35) !important;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(185, 28, 28, 0.08);
        }

        .card.border-danger .card-header {
            border-radius: 11px 11px 0 0;
            background: linear-gradient(135deg, #ef4444, #b91c1c);
        }

        .btn-copy,
        .btn-open-instance,
        .btn-create-instance {
            border: none;
            border-radius: 10px;
            font-weight: 700;
            transition: transform 0.2s ease, box-shadow 0.2s ease, filter 0.2s ease;
        }

        .btn-open-instance {
            background: linear-gradient(135deg, #1f9d61 0%, #17784a 100%);
        }

        .btn-open-instance:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(23, 120, 74, 0.35);
            color: #fff;
        }

        .btn-copy {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: #fff;
        }

        .btn-copy:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 14px rgba(71, 85, 105, 0.35);
            color: #fff;
        }

        .btn-create-instance {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
            color: #fff;
        }

        .btn-create-instance:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 16px rgba(14, 116, 144, 0.35);
            color: #fff;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #5f7387;
        }

        .quick-links {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 18px auto 28px;
            padding: 0 16px;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-link-btn {
            border: 1px solid rgba(255, 255, 255, 0.58);
            color: #fff;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(5px);
            font-weight: 700;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .quick-link-btn:hover {
            color: #fff;
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.18);
        }

        .test-links {
            position: relative;
            z-index: 1;
            max-width: 720px;
            margin: 0 auto 24px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .test-links .test-links-label {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.65);
        }

        .test-links-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .test-link-btn {
            border: 1px dashed rgba(255, 255, 255, 0.45);
            color: rgba(255, 255, 255, 0.85);
            border-radius: 999px;
            background: transparent;
            font-weight: 600;
            font-size: 0.82rem;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .test-link-btn:hover {
            color: #fff;
            transform: translateY(-1px);
            background: rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            body {
                padding: 14px;
            }

            .selfservice-container {
                padding: 26px 20px;
                border-radius: 18px;
            }

            .selfservice-header h1 {
                font-size: 1.7rem;
            }

            .icon-header {
                font-size: 2.6rem;
            }

            .link-sistema a {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="selfservice-container">
        <?php if ($mostrar_instancia_existente): ?>
            <!-- Tela de Instância Existente -->
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atenção!</strong> Já existe uma instância cadastrada com este email.
            </div>

            <div class="info-box instance-found-box mb-4">
                <i class="fas fa-info-circle"></i>
                <strong>Instância encontrada:</strong><br>
                <small>
                    Nome: <?php echo htmlspecialchars($instancia_info['NOME'] ?? 'N/A'); ?><br>
                    Cidade: <?php echo htmlspecialchars($instancia_info['CIDADE'] ?? 'N/A'); ?><br>
                    Comum: <?php echo htmlspecialchars($instancia_info['COMUM'] ?? 'N/A'); ?><br>
                    Criada em: <?php echo htmlspecialchars($instancia_info['DATA_CRIACAO'] ?? 'N/A'); ?>
                </small>
            </div>

            <div class="link-sistema link-warning mb-3">
                <strong>Link da sua instância:</strong><br>
                <a href="<?php echo htmlspecialchars($link_instancia_existente); ?>" target="_blank" id="linkInstanciaExistente">
                    <?php echo htmlspecialchars($link_instancia_existente); ?>
                </a>
            </div>

            <button class="btn btn-open-instance btn-block mb-2" onclick="window.open('<?php echo htmlspecialchars($link_instancia_existente); ?>', '_blank')">
                <i class="fas fa-external-link-alt"></i> Acessar Minha Instância
            </button>

            <button class="btn btn-copy btn-block mb-3" onclick="copiarLinkExistente()">
                <i class="fas fa-copy"></i> Copiar Link
            </button>

            <hr class="my-4">

            <h5 class="text-center mb-3">O que você deseja fazer?</h5>

            <!-- Opção 1: Criar Nova Instância -->
            <form method="post" action="selfservice.php" class="mb-3">
                <?php echo ss_csrf_field(); ?>
                <input type="hidden" name="criar_nova_instancia" value="1">
                <button type="submit" class="btn btn-create-instance btn-block">
                    <i class="fas fa-plus-circle"></i> Criar Nova Instância (Manter a Existente)
                </button>
                <small class="text-muted d-block mt-1">
                    <i class="fas fa-info-circle"></i> Você terá duas instâncias independentes
                </small>
            </form>

            <!-- Opção 2: Apagar Instância Existente -->
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <i class="fas fa-trash-alt"></i> Apagar Instância Existente
                </div>
                <div class="card-body">
                    <p class="text-danger mb-2">
                        <strong>⚠️ ATENÇÃO:</strong> Esta ação é irreversível! Todos os dados serão perdidos.
                    </p>

                    <form method="post" action="selfservice.php" id="formApagarInstancia">
                        <?php echo ss_csrf_field(); ?>
                        <input type="hidden" name="apagar_instancia" value="1">

                        <div class="form-group">
                            <label for="senha_admin"><i class="fas fa-key"></i> Senha de Administrador da Instância</label>
                            <input type="password" class="form-control" id="senha_admin" name="senha_admin"
                                   placeholder="Digite a senha da instância" required>
                            <small class="form-text text-muted">
                                Esta é a senha que você definiu ao criar a instância
                            </small>
                        </div>

                        <button type="submit" class="btn btn-danger btn-block"
                                onclick="return confirm('⚠️ TEM CERTEZA?\n\nTodos os dados da instância serão PERMANENTEMENTE apagados!\n\nEsta ação NÃO pode ser desfeita!');">
                            <i class="fas fa-trash-alt"></i> Confirmar Exclusão
                        </button>
                    </form>
                </div>
            </div>

        <?php elseif ($mostrar_sucesso): ?>
            <!-- Tela de Sucesso -->
            <div class="sucesso-box">
                <i class="fas fa-check-circle"></i>
                <h2>Cadastro Realizado com Sucesso!</h2>
                <p class="mb-3">Sua instância do sistema foi criada. Use o link abaixo para acessar:</p>

                <?php if ($email_enviado): ?>
                    <p class="text-muted small mb-3" style="opacity:.8">
                        <i class="fas fa-envelope text-success mr-1"></i>
                        Também enviamos os dados de acesso para o seu e-mail.
                    </p>
                <?php elseif (!empty($email_erro)): ?>
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Email não enviado:</strong> <?php echo htmlspecialchars($email_erro); ?>
                    </div>
                <?php endif; ?>

                <div class="link-sistema" style="display:none">
                    <a href="<?php echo htmlspecialchars($link_sistema); ?>" target="_blank" id="linkSistema">
                        <?php echo htmlspecialchars($link_sistema); ?>
                    </a>
                </div>

                <button class="btn btn-acessar" onclick="window.open('<?php echo htmlspecialchars($link_sistema); ?>', '_blank')">
                    <i class="fas fa-external-link-alt"></i> Acessar Sistema
                </button>

                <button class="btn btn-copy mt-2" onclick="copiarLink()">
                    <i class="fas fa-copy"></i> Copiar Link
                </button>

                <hr class="my-4">

                <p class="text-muted mb-0">
                    <small><i class="fas fa-info-circle"></i> Guarde este link em um lugar seguro. Você precisará dele para acessar seu sistema.</small>
                </p>
            </div>
        <?php else: ?>
            <!-- Formulário de Cadastro -->
            <div class="selfservice-header">
                <i class="fas fa-users icon-header"></i>
                <h1>Crie sua Conta</h1>
                <p>Cadastre-se e receba acesso ao Sistema de Cadastro de Crianças</p>
            </div>

            <!-- Botão vídeo apresentação -->
            <div class="text-center mb-3">
                <a href="#" onclick="document.getElementById('modalVideo').style.display='flex';document.getElementById('videoApresentacao').play();return false;" class="btn btn-sm" style="border:1px solid rgba(14,116,144,.5);color:#0e7490;border-radius:50px;padding:8px 22px;font-size:.9rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;background:rgba(14,116,144,.1);transition:background .2s,transform .2s" onmouseover="this.style.background='rgba(14,116,144,.18)';this.style.transform='translateY(-1px)'" onmouseout="this.style.background='rgba(14,116,144,.1)';this.style.transform='translateY(0)'">
                    <i class="fas fa-play-circle"></i> O que é o sistema?
                </a>
            </div>

            <!-- Modal de vídeo -->
            <div id="modalVideo" onclick="if(event.target===this){this.style.display='none';document.getElementById('videoApresentacao').pause();}" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.85);z-index:9999;justify-content:center;align-items:center">
                <div style="position:relative;width:90%;max-width:800px">
                    <button onclick="document.getElementById('modalVideo').style.display='none';document.getElementById('videoApresentacao').pause();" style="position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:2rem;cursor:pointer;z-index:10000">&times;</button>
                    <video id="videoApresentacao" controls playsinline style="width:100%;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.5)">
                        <source src="video/apresentacao-ebi.mp4" type="video/mp4">
                        Seu navegador não suporta vídeo HTML5.
                    </video>
                </div>
            </div>
            
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <form method="post" action="selfservice.php" id="formCadastro">
                <?php echo ss_csrf_field(); ?>
                <div class="form-group">
                    <label for="nome"><i class="fas fa-user"></i> Nome Completo</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           placeholder="Digite seu nome completo" required 
                           value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="seu@email.com" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cidade"><i class="fas fa-map-marker-alt"></i> Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade" 
                                   placeholder="Sua cidade" required
                                   value="<?php echo htmlspecialchars($_POST['cidade'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="comum"><i class="fas fa-users"></i> Comum</label>
                            <input type="text" class="form-control" id="comum" name="comum" 
                                   placeholder="Nome do comum" required
                                   value="<?php echo htmlspecialchars($_POST['comum'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="senha"><i class="fas fa-lock"></i> Senha</label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           placeholder="Mínimo 6 caracteres" required>
                    <i class="fas fa-eye" onclick="togglePassword('senha')"></i>
                </div>
                
                <div class="form-group password-toggle">
                    <label for="confirmar_senha"><i class="fas fa-lock"></i> Confirmar Senha</label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                           placeholder="Digite a senha novamente" required>
                    <i class="fas fa-eye" onclick="togglePassword('confirmar_senha')"></i>
                </div>
                
                <button type="submit" name="cadastrar" class="btn btn-cadastrar">
                    <i class="fas fa-rocket"></i> Criar Minha Conta Grátis
                </button>

                <button type="button" class="btn btn-recuperar-conta" id="btnRecuperarContaEmail">
                    <i class="fas fa-envelope-open-text"></i> Recuperar conta com email
                </button>
            </form>

            <form method="post" action="selfservice.php" id="formRecuperarContaEmail" class="d-none">
                <?php echo ss_csrf_field(); ?>
                <input type="hidden" name="recuperar_conta_email" value="1">
                <input type="hidden" name="email_recuperacao" id="email_recuperacao" value="">
            </form>
        <?php endif; ?>
    </div>

    <div class="quick-links">
        <a href="admin.php" class="btn btn-sm quick-link-btn">
            <i class="fas fa-user-shield mr-1"></i>Administração
        </a>
        <a href="../qrcode/default.php" class="btn btn-sm quick-link-btn">
            <i class="fas fa-qrcode mr-1"></i>QR Code
        </a>
    </div>

    <div class="test-links">
        <span class="test-links-label"><i class="fas fa-flask mr-1"></i>Área de testes (conceito v2)</span>
        <div class="test-links-buttons">
            <a href="../ebi/template/ebi.test.php" class="btn btn-sm test-link-btn" target="_blank">
                <i class="fas fa-child mr-1"></i>Cadastro EBI (teste)
            </a>
            <a href="../qrcode/qrcode.2.php" class="btn btn-sm test-link-btn" target="_blank">
                <i class="fas fa-qrcode mr-1"></i>QR Code v2 (nascimento)
            </a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function copiarLink() {
            const linkElement = document.getElementById('linkSistema');
            const link = linkElement.textContent;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(() => {
                    alert('Link copiado para a área de transferência!');
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                    copiarLinkFallback(link);
                });
            } else {
                copiarLinkFallback(link);
            }
        }

        function copiarLinkExistente() {
            const linkElement = document.getElementById('linkInstanciaExistente');
            const link = linkElement.textContent;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(link).then(() => {
                    alert('Link copiado para a área de transferência!');
                }).catch(err => {
                    console.error('Erro ao copiar:', err);
                    copiarLinkFallback(link);
                });
            } else {
                copiarLinkFallback(link);
            }
        }
        
        function copiarLinkFallback(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                alert('Link copiado para a área de transferência!');
            } catch (err) {
                alert('Não foi possível copiar automaticamente. Por favor, copie manualmente: ' + text);
            }
            document.body.removeChild(textArea);
        }
        
        // Validação de senha em tempo real
        $('#confirmar_senha').on('input', function() {
            const senha = $('#senha').val();
            const confirmar = $(this).val();
            
            if (confirmar && senha !== confirmar) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });

        $('#btnRecuperarContaEmail').on('click', function() {
            const email = ($('#email').val() || '').trim();

            if (!email) {
                alert('Preencha o e-mail acima para recuperar a conta.');
                $('#email').focus();
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Informe um e-mail válido para recuperar a conta.');
                $('#email').focus();
                return;
            }

            $('#email_recuperacao').val(email);
            $('#formRecuperarContaEmail').trigger('submit');
        });
    </script>
    <div class="text-center mt-4 mb-2" style="font-size:9px;color:#b0b0b0;opacity:0.6">v<?php echo defined('VERSAO_SISTEMA') ? VERSAO_SISTEMA : date('YmdHi'); ?></div>
</body>
</html>
