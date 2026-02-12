<?php
session_start();

// Configurações do Self-Service
define('DB_SELFSERVICE', __DIR__ . '/data/selfservice_users.txt');
define('INSTANCES_DIR', __DIR__ . '/instances/');

// Cria diretórios necessários
if (!file_exists(dirname(DB_SELFSERVICE))) {
    mkdir(dirname(DB_SELFSERVICE), 0755, true);
}
if (!file_exists(INSTANCES_DIR)) {
    mkdir(INSTANCES_DIR, 0755, true);
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
    
    // Permite criar várias instâncias mesmo com o mesmo e-mail (cada cadastro gera nova instância)
    
    if (empty($erros)) {
        // Gerar ID único para o usuário
        $user_id = uniqid('user_', true);
        $hash_senha = password_hash($senha, PASSWORD_DEFAULT);
        $data_cadastro = date('Y-m-d H:i:s');
        
        // Salvar usuário
        $linha = implode('|', [
            $user_id,
            $email,
            $nome,
            $cidade,
            $comum,
            $hash_senha,
            $data_cadastro
        ]);
        
        file_put_contents(DB_SELFSERVICE, $linha . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        // Criar instância do sistema para o usuário
        require_once 'criar_instancia.php';
        $resultado = criarInstanciaUsuario($user_id, $nome, $email, $cidade, $comum, $senha);
        
        if ($resultado['sucesso']) {
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
if (isset($_SESSION['cadastro_sucesso'])) {
    $mostrar_sucesso = true;
    $link_sistema = $_SESSION['link_sistema'] ?? '';
    unset($_SESSION['cadastro_sucesso']);
    unset($_SESSION['link_sistema']);
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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        
        .selfservice-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 600px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .selfservice-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .selfservice-header h1 {
            color: #667eea;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .selfservice-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .icon-header {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-cadastrar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            margin-top: 20px;
            transition: transform 0.2s ease;
        }
        
        .btn-cadastrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .sucesso-box {
            background-color: #d4edda;
            border: 2px solid #28a745;
            border-radius: 10px;
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
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .sucesso-box h2 {
            color: #155724;
            margin-bottom: 15px;
        }
        
        .link-sistema {
            background-color: #fff;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            word-break: break-all;
        }
        
        .link-sistema a {
            color: #667eea;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-acessar {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-acessar:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }
        
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        
        .info-box i {
            color: #667eea;
            margin-right: 10px;
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
            color: #6c757d;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="selfservice-container">
        <?php if ($mostrar_sucesso): ?>
            <!-- Tela de Sucesso -->
            <div class="sucesso-box">
                <i class="fas fa-check-circle"></i>
                <h2>Cadastro Realizado com Sucesso!</h2>
                <p class="mb-3">Sua instância do sistema foi criada. Use o link abaixo para acessar:</p>
                
                <div class="link-sistema">
                    <a href="<?php echo htmlspecialchars($link_sistema); ?>" target="_blank" id="linkSistema">
                        <?php echo htmlspecialchars($link_sistema); ?>
                    </a>
                </div>
                
                <button class="btn btn-acessar" onclick="window.open('<?php echo htmlspecialchars($link_sistema); ?>', '_blank')">
                    <i class="fas fa-external-link-alt"></i> Acessar Sistema
                </button>
                
                <button class="btn btn-secondary mt-2" onclick="copiarLink()">
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
            
            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensagem; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-gift"></i>
                <strong>Grátis e Rápido!</strong> Crie sua conta em menos de 1 minuto e comece a usar imediatamente.
            </div>
            
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
                            <label for="comum"><i class="fas fa-church"></i> Comum</label>
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
            </form>
        <?php endif; ?>
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
    </script>
</body>
</html>
