<?php

/**
 * Cria uma instância completa do sistema para um novo usuário
 * 
 * @param string $user_id ID único do usuário
 * @param string $nome Nome do usuário
 * @param string $email Email do usuário
 * @param string $cidade Cidade do usuário
 * @param string $comum Comum do usuário
 * @param string $senha Senha do sistema
 * @return array Array com 'sucesso' (bool), 'link' (string) e 'erro' (string se houver)
 */
function criarInstanciaUsuario($user_id, $nome, $email, $cidade, $comum, $senha) {
    $resultado = [
        'sucesso' => false,
        'link' => '',
        'erro' => ''
    ];
    
    try {
        // Diretórios base
        $instancesDir = __DIR__ . '/instances/';
        $templateDir = __DIR__ . '/template/';
        
        // Criar diretório da instância do usuário
        $userInstanceDir = $instancesDir . $user_id . '/';
        
        if (!file_exists($userInstanceDir)) {
            mkdir($userInstanceDir, 0755, true);
        }
        
        // Criar subdiretórios necessários
        $configDir = $userInstanceDir . 'config/';
        $publicDir = $userInstanceDir . 'public_html/ebi/';
        
        if (!file_exists($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        if (!file_exists($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        
        // 1. Criar arquivo config.ini personalizado (versão expandida com segurança)
        $dataCriacao = date('Y-m-d H:i:s');
        $configContent = "; ═══════════════════════════════════════════════════════════════════
; ARQUIVO DE CONFIGURAÇÃO - Sistema de Cadastro de Crianças
; Instância de: $nome ($email)
; Cidade: $cidade | Comum: $comum
; Data de Criação: $dataCriacao
; ═══════════════════════════════════════════════════════════════════

[INFO_SISTEMA]
NOME_SISTEMA = \"Sistema de Cadastro de Crianças\"
VERSAO = \"2.0\"
DATA_INSTALACAO = \"$dataCriacao\"

[INFO_USUARIO]
NOME = \"$nome\"
EMAIL = \"$email\"
CIDADE = \"$cidade\"
COMUM = \"$comum\"
USER_ID = \"$user_id\"
DATA_CRIACAO = \"$dataCriacao\"

[GERAL]
ARQUIVO_DADOS = \"/../../config/cadastro_criancas.txt\"
ARQUIVO_DADOS_PAINEL = \"/../../config/painel_criancas.txt\"
DELIMITADOR = \"|\"
MAX_BACKUPS = 10
BACKUP_AUTOMATICO = true
NUM_LINHAS_FORMULARIO_CADASTRO = 5
NUM_CAMPOS_POR_LINHA_NO_ARQUIVO = 8
TIMEZONE = \"America/Sao_Paulo\"

[SEGURANCA]
SENHA_ADMIN_REAL = \"$senha\"
SENHA_PAINEL = \"$senha\"
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 5
TEMPO_BLOQUEIO = 300
CSRF_PROTECTION = true
LOG_TENTATIVAS_LOGIN = true

[IMPRESSORA_ZPL]
TAMPULSEIRA = 269
DOTS = 8
FECHO = 30
FECHOINI = 1
URL_IMPRESSORA = \"http://127.0.0.1:9100/write\"
LARGURA_PULSEIRA = 192
IMPRIMIR_QRCODE = false
TAMANHO_QRCODE = 4

[INTERFACE]
TITULO_LOGIN = \"Acesso ao Sistema - $comum\"
LOGO_URL = \"https://placehold.co/40x40/007bff/white?text=Kids\"
COR_PRIMARIA = \"#007bff\"
COR_SECUNDARIA = \"#0056b3\"
MOSTRAR_RODAPE = true
TEXTO_RODAPE = \"$comum - $cidade\"

[VALIDACAO]
MIN_TAMANHO_NOME_CRIANCA = 2
MAX_TAMANHO_NOME_CRIANCA = 100
MAX_TAMANHO_NOME_RESPONSAVEL = 100
IDADE_MINIMA = 0
IDADE_MAXIMA = 17
REGEX_TELEFONE = \"/^[\\d\\s\\-\\(\\)]+$/\"
MIN_TAMANHO_TELEFONE = 8
MAX_TAMANHO_TELEFONE = 20

[PROCESSAMENTO_NOMES]
MAX_CHARS_NOME_CRIANCA_PULSEIRA = 22
MAX_CHARS_NOME_RESPONSAVEL_PULSEIRA = 25
CONVERTER_MAIUSCULAS = true
REMOVER_ACENTOS = false

[LISTAGEM]
REGISTROS_POR_PAGINA = 0
ORDENACAO_PADRAO = \"id\"
DIRECAO_ORDENACAO = \"ASC\"
HABILITAR_FILTRO_PORTARIA = true
HABILITAR_BUSCA_RAPIDA = true

[EMAIL]
HABILITAR_EMAIL = false
EMAIL_FROM = \"noreply@seudominio.com\"
NOME_FROM = \"Sistema de Cadastro - $comum\"
EMAIL_ADMIN = \"$email\"
NOTIFICAR_NOVO_CADASTRO = false

[LOGS]
HABILITAR_LOGS = true
ARQUIVO_LOG = \"/../../config/sistema.log\"
NIVEL_LOG = \"INFO\"
MAX_TAMANHO_LOG_MB = 10
LOG_ACOES_CADASTRO = true
LOG_IMPRESSOES = true

[RECURSOS]
HABILITAR_IMPRESSAO = true
HABILITAR_CADASTRO_MASSA = true
HABILITAR_EDICAO = true
HABILITAR_EXCLUSAO = true
HABILITAR_PULSEIRA_RESPONSAVEL = true
HABILITAR_RECUPERACAO_BACKUP = true
HABILITAR_ZERAGEM = true
HABILITAR_EXPORTACAO = true

[AVANCADO]
DEBUG_MODE = false
MOSTRAR_ERROS_PHP = false
ENCODING_ARQUIVO = \"UTF-8\"
USAR_CACHE = false
TEMPO_CACHE = 60
VERIFICAR_INTEGRIDADE = true

; ═══════════════════════════════════════════════════════════════════
; FIM DO ARQUIVO DE CONFIGURAÇÃO
; ═══════════════════════════════════════════════════════════════════
";
        
        file_put_contents($configDir . 'config.ini', $configContent);
        
        // 2. Criar arquivos de dados vazios
        $headerCadastro = "# Sistema de Cadastro de Crianças - $comum\n";
        $headerCadastro .= "# Criado em: " . date('Y-m-d H:i:s') . "\n";
        $headerCadastro .= "# Formato: ID|Nome|Responsável|Telefone|Idade|Comum|StatusImpresso|Portaria|CodResp\n";
        
        file_put_contents($configDir . 'cadastro_criancas.txt', $headerCadastro);
        file_put_contents($configDir . 'painel_criancas.txt', $headerCadastro);
        
        // 3. Copiar estrutura refatorada do sistema (index.php + inc/ + views/)
        $templateDir = __DIR__ . '/template/';
        $arquivosRefatorados = [
            'index.php' => 'index.php',
            'inc/bootstrap.php' => 'inc/bootstrap.php',
            'inc/auth.php' => 'inc/auth.php',
            'inc/funcoes.php' => 'inc/funcoes.php',
            'inc/actions.php' => 'inc/actions.php',
            'views/login.php' => 'views/login.php',
            'views/main.php' => 'views/main.php',
        ];
        foreach ($arquivosRefatorados as $origem => $destino) {
            $caminhoOrigem = $templateDir . $origem;
            if (!file_exists($caminhoOrigem)) {
                throw new Exception("Arquivo do template não encontrado: $origem");
            }
            $caminhoDestino = $publicDir . $destino;
            $dirDestino = dirname($caminhoDestino);
            if (!file_exists($dirDestino)) {
                mkdir($dirDestino, 0755, true);
            }
            if (copy($caminhoOrigem, $caminhoDestino) === false) {
                throw new Exception("Erro ao copiar: $origem");
            }
        }
        // Config.ini na pasta da aplicação (refatorada espera config.ini junto ao index.php)
        file_put_contents($publicDir . 'config.ini', $configContent);
        
        // 4. Criar arquivo .htaccess para segurança
        $htaccessContent = "# Proteção de diretório
<Files \"config.ini\">
    Order allow,deny
    Deny from all
</Files>

<Files \"*.txt\">
    Order allow,deny
    Deny from all
</Files>

# Desabilitar listagem de diretório
Options -Indexes

# Proteção adicional
<FilesMatch \"\\.(ini|txt|log|bak)$\">
    Order allow,deny
    Deny from all
</FilesMatch>
";
        
        file_put_contents($configDir . '.htaccess', $htaccessContent);
        file_put_contents($userInstanceDir . '.htaccess', $htaccessContent);
        
        // Copiar .htaccess do template se existir
        $templateHtaccess = __DIR__ . '/template/.htaccess';
        if (file_exists($templateHtaccess)) {
            copy($templateHtaccess, $configDir . '.htaccess');
        }
        
        // 5. Criar arquivo README com instruções
        $readmeContent = "=== SISTEMA DE CADASTRO DE CRIANÇAS ===

Instância criada para: $nome
Email: $email
Cidade: $cidade
Comum: $comum
Data de Criação: " . date('Y-m-d H:i:s') . "

INSTRUÇÕES DE ACESSO:
---------------------
Acesse o sistema através do link fornecido no cadastro.
Use a senha que você cadastrou para fazer login.

INFORMAÇÕES IMPORTANTES:
------------------------
- Seus dados são armazenados de forma isolada
- Faça backups regulares dos seus dados
- Mantenha sua senha em local seguro
- Em caso de problemas, entre em contato com o suporte

ESTRUTURA DE ARQUIVOS:
----------------------
config/          -> Arquivos de configuração e dados
public_html/ebi/ -> Interface do sistema

ID da Instância: $user_id
";
        
        file_put_contents($userInstanceDir . 'README.txt', $readmeContent);
        
        // 6. Criar arquivo de log
        $logContent = date('Y-m-d H:i:s') . " - Instância criada para $nome ($email)\n";
        file_put_contents($userInstanceDir . 'system.log', $logContent);
        
        // 7. Gerar link de acesso
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                   . "://" . $_SERVER['HTTP_HOST'];
        $currentPath = dirname($_SERVER['PHP_SELF']);
        
        $link = $baseUrl . $currentPath . '/instances/' . $user_id . '/public_html/ebi/index.php';
        
        // 8. Salvar log de criação no arquivo central
        $logCentral = __DIR__ . '/data/instancias_criadas.log';
        $logEntry = date('Y-m-d H:i:s') . "|$user_id|$nome|$email|$cidade|$comum|$link\n";
        file_put_contents($logCentral, $logEntry, FILE_APPEND | LOCK_EX);
        
        $resultado['sucesso'] = true;
        $resultado['link'] = $link;
        
    } catch (Exception $e) {
        $resultado['sucesso'] = false;
        $resultado['erro'] = $e->getMessage();
        
        // Log de erro
        $errorLog = __DIR__ . '/data/erros.log';
        $errorEntry = date('Y-m-d H:i:s') . "|$user_id|$email|ERRO: " . $e->getMessage() . "\n";
        file_put_contents($errorLog, $errorEntry, FILE_APPEND | LOCK_EX);
    }
    
    return $resultado;
}

/**
 * Verifica se uma instância existe para um usuário
 */
function verificarInstanciaExiste($user_id) {
    $instancesDir = __DIR__ . '/instances/';
    $userInstanceDir = $instancesDir . $user_id . '/';
    
    return file_exists($userInstanceDir) && is_dir($userInstanceDir);
}

/**
 * Obtém informações de uma instância
 */
function obterInfoInstancia($user_id) {
    $instancesDir = __DIR__ . '/instances/';
    $configFile = $instancesDir . $user_id . '/config/config.ini';
    
    if (!file_exists($configFile)) {
        return null;
    }
    
    $config = parse_ini_file($configFile, true);
    
    if (isset($config['INFO_USUARIO'])) {
        return $config['INFO_USUARIO'];
    }
    
    return null;
}

/**
 * Lista todas as instâncias criadas
 */
function listarTodasInstancias() {
    $instancesDir = __DIR__ . '/instances/';
    $instancias = [];
    
    if (!is_dir($instancesDir)) {
        return $instancias;
    }
    
    $dirs = scandir($instancesDir);
    
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..') {
            continue;
        }
        
        $fullPath = $instancesDir . $dir;
        
        if (is_dir($fullPath)) {
            $info = obterInfoInstancia($dir);
            if ($info) {
                $info['user_id'] = $dir;
                $instancias[] = $info;
            }
        }
    }
    
    return $instancias;
}

/**
 * Remove uma instância (usar com cuidado!)
 */
function removerInstancia($user_id) {
    $instancesDir = __DIR__ . '/instances/';
    $userInstanceDir = $instancesDir . $user_id . '/';
    
    if (!file_exists($userInstanceDir)) {
        return ['sucesso' => false, 'erro' => 'Instância não encontrada'];
    }
    
    // Criar backup antes de remover
    $backupDir = __DIR__ . '/backups/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $backupFile = $backupDir . $user_id . '_' . date('YmdHis') . '.zip';
    
    // Aqui você poderia adicionar código para criar um ZIP da instância
    
    // Remover diretório recursivamente
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    try {
        rrmdir($userInstanceDir);
        
        // Log de remoção
        $logRemocao = __DIR__ . '/data/instancias_removidas.log';
        $logEntry = date('Y-m-d H:i:s') . "|$user_id|Removida com sucesso\n";
        file_put_contents($logRemocao, $logEntry, FILE_APPEND | LOCK_EX);
        
        return ['sucesso' => true];
        
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}
