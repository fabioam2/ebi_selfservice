<?php

/**
 * Sanitiza valor para uso seguro em arquivos INI.
 * Remove caracteres que poderiam quebrar a estrutura do INI.
 *
 * @param mixed $value Valor a ser sanitizado
 * @return string Valor sanitizado e seguro para uso em INI
 */
function sanitize_ini_value($value): string {
    $value = str_replace(["\r", "\n", "\t"], ' ', (string)$value);
    $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    $value = str_replace([';'], [','], $value);
    return trim($value);
}

/**
 * Cria uma instância completa do sistema para um novo usuário
 *
 * Esta função realiza as seguintes operações:
 * - Cria estrutura de diretórios isolada para o usuário
 * - Gera arquivo config.ini personalizado
 * - Copia template do sistema
 * - Cria arquivos de dados vazios
 * - Configura permissões e segurança (.htaccess)
 * - Gera link de acesso único
 *
 * @param string $user_id ID único do usuário (deve ser alfanumérico)
 * @param string $nome Nome completo do usuário
 * @param string $email Email válido do usuário
 * @param string $cidade Cidade do usuário
 * @param string $comum Organização/Comum do usuário
 * @param string $senha Senha para acesso ao sistema
 *
 * @return array{sucesso: bool, link: string, erro: string} Array associativo com resultado da operação
 *
 * @throws Exception Se houver erro ao criar diretórios ou copiar arquivos
 */
function criarInstanciaUsuario(string $user_id, string $nome, string $email, string $cidade, string $comum, string $senha): array {
    $resultado = [
        'sucesso' => false,
        'link' => '',
        'erro' => ''
    ];

    try {
        // Sanitizar inputs para uso no config.ini
        $nome_safe = sanitize_ini_value($nome);
        $email_safe = sanitize_ini_value($email);
        $cidade_safe = sanitize_ini_value($cidade);
        $comum_safe = sanitize_ini_value($comum);

        // A senha NUNCA entra em texto plano no config.ini — só o hash bcrypt.
        if (trim((string)$senha) === '') {
            throw new Exception('Senha não pode ser vazia.');
        }
        $senha_hash = password_hash((string)$senha, PASSWORD_BCRYPT, ['cost' => 12]);

        // Carregar configuração de caminhos dinâmicos
        if (!defined('INSTANCE_BASE_PATH')) {
            require_once __DIR__ . '/inc/paths.php';
        }

        // Diretórios base (agora usando caminhos dinâmicos)
        $instancesDir = INSTANCE_BASE_PATH . '/';
        $templateDir = TEMPLATE_PATH . '/';
        
        // Criar diretório da instância do usuário
        // Estrutura simplificada (URL enxuta):
        //   ebi/i/user_XXX/
        //     index.php, inc/, views/, saida/, assets/, config.ini, .htaccess
        //     data/               <- cadastro_criancas.txt, painel_criancas.txt, sistema.log
        //
        // Permissões: a raiz precisa ser 0755 para o Apache atravessar o caminho.
        // Arquivos sensíveis (.ini, .txt, .log) ficam protegidos via .htaccess
        // copiado do template (bloqueia extensões, arquivos ocultos e .bkp.N).
        $userInstanceDir = $instancesDir . $user_id . '/';

        if (!file_exists($userInstanceDir)) {
            mkdir($userInstanceDir, 0755, true);
        } else {
            @chmod($userInstanceDir, 0755);
        }

        // Subdiretório para dados persistentes (cadastros, painel, logs)
        $dataDir = $userInstanceDir . 'data/';
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        // 1. Criar arquivo config.ini personalizado (versão expandida com segurança)
        $dataCriacao = date('Y-m-d H:i:s');
        $configContent = "; ═══════════════════════════════════════════════════════════════════
; ARQUIVO DE CONFIGURAÇÃO - Sistema de Cadastro de Crianças
; Instância de: $nome_safe ($email_safe)
; Cidade: $cidade_safe | Comum: $comum_safe
; Data de Criação: $dataCriacao
; ═══════════════════════════════════════════════════════════════════

[INFO_SISTEMA]
NOME_SISTEMA = \"Sistema de Cadastro de Crianças\"
VERSAO = \"2.0\"
DATA_INSTALACAO = \"$dataCriacao\"

[INFO_USUARIO]
NOME = \"$nome_safe\"
EMAIL = \"$email_safe\"
CIDADE = \"$cidade_safe\"
COMUM = \"$comum_safe\"
USER_ID = \"$user_id\"
DATA_CRIACAO = \"$dataCriacao\"

[GERAL]
ARQUIVO_DADOS = \"/data/cadastro_criancas.txt\"
ARQUIVO_DADOS_PAINEL = \"/data/painel_criancas.txt\"
DELIMITADOR = \"|\"
MAX_BACKUPS = 10
BACKUP_AUTOMATICO = true
NUM_LINHAS_FORMULARIO_CADASTRO = 5
NUM_CAMPOS_POR_LINHA_NO_ARQUIVO = 8
TIMEZONE = \"America/Sao_Paulo\"

[SEGURANCA]
SENHA_ADMIN_HASH = \"$senha_hash\"
SENHA_PAINEL_HASH = \"$senha_hash\"
SENHA_ADMIN_REAL = \"\"
SENHA_PAINEL = \"\"
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 5
TEMPO_BLOQUEIO = 300
CSRF_PROTECTION = true
LOG_TENTATIVAS_LOGIN = true

[IMPRESSORA_ZPL]
PRINTER_NAME = \"" . ($_ENV['PRINTER_NAME'] ?? 'ZDesigner 105SL') . "\"
PALAVRA_CONTADOR_COMUM = \"" . ($_ENV['PALAVRA_CONTADOR_COMUM'] ?? 'bonfim') . "\"
LISTA_PALAVRAS_CONTADOR_COMUM = \"" . ($_ENV['LISTA_PALAVRAS_CONTADOR_COMUM'] ?? 'parque, parqui, par que') . "\"
TAMPULSEIRA = 269
DOTS = 8
FECHO = 30
FECHOINI = 1
URL_IMPRESSORA = \"http://127.0.0.1:9100/write\"
LARGURA_PULSEIRA = 192
IMPRIMIR_QRCODE = false
TAMANHO_QRCODE = 4

[INTERFACE]
TITULO_LOGIN = \"Acesso ao Sistema - $comum_safe\"
LOGO_URL = \"https://placehold.co/40x40/007bff/white?text=Kids\"
COR_PRIMARIA = \"#007bff\"
COR_SECUNDARIA = \"#0056b3\"
MOSTRAR_RODAPE = true
TEXTO_RODAPE = \"$comum_safe - $cidade_safe\"

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
NOME_FROM = \"Sistema de Cadastro - $comum_safe\"
EMAIL_ADMIN = \"$email_safe\"
NOTIFICAR_NOVO_CADASTRO = false

[LOGS]
HABILITAR_LOGS = true
ARQUIVO_LOG = \"/data/sistema.log\"
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
        
        // 1. Gravar config.ini na raiz da instância (onde fica o index.php)
        file_put_contents($userInstanceDir . 'config.ini', $configContent);
        @chmod($userInstanceDir . 'config.ini', 0600);

        // 2. Criar arquivos de dados vazios dentro de data/
        $headerCadastro = "# Sistema de Cadastro de Crianças - $comum_safe\n";
        $headerCadastro .= "# Criado em: " . date('Y-m-d H:i:s') . "\n";
        $headerCadastro .= "# Formato: ID|Nome|Responsável|Telefone|Idade|Comum|StatusImpresso|Portaria|CodResp\n";
        file_put_contents($dataDir . 'cadastro_criancas.txt', $headerCadastro);
        file_put_contents($dataDir . 'painel_criancas.txt', $headerCadastro);

        // 3. Copiar estrutura do template diretamente para a raiz da instância
        //    (index.php + inc/ + views/ + saida/ + assets/ + calibrar.php)
        $itensTemplate = ['index.php', 'calibrar.php', 'inc', 'views', 'saida', 'assets'];
        $copyRecursive = function ($origem, $destino) use (&$copyRecursive) {
            if (is_dir($origem)) {
                if (!is_dir($destino)) {
                    mkdir($destino, 0755, true);
                }
                foreach (scandir($origem) as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $copyRecursive($origem . '/' . $f, $destino . '/' . $f);
                }
            } elseif (is_file($origem)) {
                // Não sobrescreve o config.ini já gerado com as credenciais do usuário
                if (basename($destino) === 'config.ini') return;
                copy($origem, $destino);
            }
        };
        foreach ($itensTemplate as $item) {
            $origemItem = rtrim($templateDir, '/') . '/' . $item;
            if (file_exists($origemItem)) {
                $copyRecursive($origemItem, $userInstanceDir . $item);
            }
        }

        // 4. Copiar .htaccess do template (bloqueia .ini/.txt/.log/.env/.md, ocultos e .bkp.N)
        $templateHtaccess = rtrim($templateDir, '/') . '/.htaccess';
        if (file_exists($templateHtaccess)) {
            copy($templateHtaccess, $userInstanceDir . '.htaccess');
        }

        // 5. .htaccess adicional dentro de data/ (defesa em profundidade)
        $htaccessData = "Require all denied\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
        file_put_contents($dataDir . '.htaccess', $htaccessData);

        // 6. Criar README com instruções
        $readmeContent = "=== SISTEMA DE CADASTRO DE CRIANÇAS ===
Instância criada para: $nome_safe
Email: $email_safe
Cidade: $cidade_safe
Comum: $comum_safe
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
index.php        -> Interface principal do sistema
config.ini       -> Configuração da instância (protegido)
data/            -> Dados persistentes (protegido)
saida/           -> Módulo de saída (QR Code)

ID da Instância: $user_id
";
        
        file_put_contents($userInstanceDir . 'README.txt', $readmeContent);
        
        // 6. Criar arquivo de log
        $logContent = date('Y-m-d H:i:s') . " - Instância criada para $nome_safe ($email_safe)\n";
        file_put_contents($userInstanceDir . 'system.log', $logContent);
        
        // 7. Gerar link de acesso
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
                   . "://" . $_SERVER['HTTP_HOST'];

        // Obter o caminho raiz do projeto (suba dois níveis a partir de PHP_SELF)
        // Exemplo: /dev2/selfservice/selfservice.php -> /dev2
        $rootPath = dirname(dirname($_SERVER['PHP_SELF']));

        // Evitar duplo slash se o sistema estiver na raiz
        $pathPrefix = ($rootPath === '/') ? '' : $rootPath;

        // Construir o link usando o caminho raiz dinâmico
        $link = $baseUrl . $pathPrefix . '/ebi/i/' . $user_id . '/index.php';
        
        // 8. Salvar log de criação no arquivo central
        $logCentral = DATA_PATH . '/instancias_criadas.log';
        $logEntry = date('Y-m-d H:i:s') . "|$user_id|$nome|$email|$cidade|$comum|$link\n";
        file_put_contents($logCentral, $logEntry, FILE_APPEND | LOCK_EX);
        
        $resultado['sucesso'] = true;
        $resultado['link'] = $link;
        
    } catch (Exception $e) {
        $resultado['sucesso'] = false;
        $resultado['erro'] = $e->getMessage();
        
        // Log de erro
        $errorLog = DATA_PATH . '/erros.log';
        $errorEntry = date('Y-m-d H:i:s') . "|$user_id|$email|ERRO: " . $e->getMessage() . "\n";
        file_put_contents($errorLog, $errorEntry, FILE_APPEND | LOCK_EX);
    }
    
    return $resultado;
}

/**
 * Verifica se uma instância existe para um usuário
 *
 * @param string $user_id ID único do usuário
 * @return bool True se a instância existe, false caso contrário
 */
function verificarInstanciaExiste(string $user_id): bool {
    if (!defined('INSTANCE_BASE_PATH')) {
        require_once __DIR__ . '/inc/paths.php';
    }

    $instancesDir = INSTANCE_BASE_PATH . '/';
    $userInstanceDir = $instancesDir . $user_id . '/';

    return file_exists($userInstanceDir) && is_dir($userInstanceDir);
}

/**
 * Obtém informações de uma instância a partir do arquivo config.ini
 *
 * @param string $user_id ID único do usuário
 * @return array|null Array com informações do usuário ou null se não encontrado
 */
function obterInfoInstancia(string $user_id): ?array {
    if (!defined('INSTANCE_BASE_PATH')) {
        require_once __DIR__ . '/inc/paths.php';
    }

    $instancesDir = INSTANCE_BASE_PATH . '/';
    $configFile = $instancesDir . $user_id . '/config.ini';

    // Compat: instâncias antigas tinham config em config/config.ini
    if (!file_exists($configFile)) {
        $legado = $instancesDir . $user_id . '/config/config.ini';
        if (file_exists($legado)) {
            $configFile = $legado;
        }
    }

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
 * Lista todas as instâncias criadas no sistema
 *
 * Varre o diretório de instâncias e retorna informações de todas as instâncias válidas.
 *
 * @return array<int, array> Array de arrays com informações de cada instância
 */
function listarTodasInstancias(): array {
    if (!defined('INSTANCE_BASE_PATH')) {
        require_once __DIR__ . '/inc/paths.php';
    }

    $instancesDir = INSTANCE_BASE_PATH . '/';
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
 * Remove um diretório e todo o seu conteúdo recursivamente.
 *
 * ATENÇÃO: Esta operação é irreversível! Use com cuidado.
 *
 * @param string $dir Caminho do diretório a ser removido
 * @return void
 */
function rrmdir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }

    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object === '.' || $object === '..') {
            continue;
        }

        $path = $dir . '/' . $object;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

/**
 * Cria um backup ZIP de um diretório.
 *
 * Compacta recursivamente todos os arquivos e subdiretórios do diretório fonte
 * em um arquivo ZIP. Requer a extensão ZipArchive do PHP.
 *
 * @param string $sourceDir Diretório a ser compactado (caminho completo)
 * @param string $zipFile Caminho do arquivo ZIP de destino
 * @return bool True se o backup foi criado com sucesso, false caso contrário
 */
function criarBackupZip(string $sourceDir, string $zipFile): bool {
    if (!class_exists('ZipArchive')) {
        error_log("ZipArchive não disponível. Backup ZIP ignorado.");
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $sourceDir = realpath($sourceDir);
    if ($sourceDir === false) {
        return false;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($sourceDir) + 1);
        if ($item->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($item->getPathname(), $relativePath);
        }
    }

    return $zip->close();
}

/**
 * Remove uma instância de usuário do sistema
 *
 * ATENÇÃO: Esta operação remove permanentemente todos os dados da instância!
 * Um backup ZIP é criado automaticamente antes da remoção.
 *
 * Validações de segurança:
 * - Previne path traversal attacks
 * - Verifica se a instância existe
 * - Cria backup antes de remover
 * - Registra operação em log
 *
 * @param string $user_id ID único do usuário (deve ser alfanumérico, sem caracteres especiais)
 * @return array{sucesso: bool, erro?: string} Array com resultado da operação
 */
function removerInstancia(string $user_id): array {
    // Carregar paths se ainda não foram carregados
    if (!defined('INSTANCE_BASE_PATH')) {
        require_once __DIR__ . '/inc/paths.php';
    }

    // Validar user_id para evitar path traversal.
    // user_id gerado por uniqid('user_', true) pode conter ponto (ex: user_abc123.45678).
    // Aceitamos apenas alfanuméricos, underscore e ponto — nunca sequências ".." nem barras.
    if (
        empty($user_id)
        || !preg_match('/^[A-Za-z0-9_.]+$/', $user_id)
        || strpos($user_id, '..') !== false
    ) {
        return ['sucesso' => false, 'erro' => 'ID de usuário inválido'];
    }

    $instancesDir = INSTANCE_BASE_PATH . '/';
    $userInstanceDir = $instancesDir . $user_id . '/';

    if (!file_exists($userInstanceDir)) {
        return ['sucesso' => false, 'erro' => 'Instância não encontrada'];
    }

    // Criar backup ZIP antes de remover
    $backupDir = BACKUP_PATH . '/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $backupFile = $backupDir . $user_id . '_' . date('YmdHis') . '.zip';
    criarBackupZip($userInstanceDir, $backupFile);

    try {
        rrmdir($userInstanceDir);

        // Log de remoção
        $logRemocao = DATA_PATH . '/instancias_removidas.log';
        $backupInfo = file_exists($backupFile) ? "backup: $backupFile" : "sem backup ZIP";
        $logEntry = date('Y-m-d H:i:s') . "|$user_id|Removida com sucesso|$backupInfo\n";
        file_put_contents($logRemocao, $logEntry, FILE_APPEND | LOCK_EX);

        return ['sucesso' => true];

    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $e->getMessage()];
    }
}

/**
 * Valida user_id (alfanumérico + underscore + ponto, sem "..").
 */
function validarUserId(string $user_id): bool {
    return !empty($user_id)
        && preg_match('/^[A-Za-z0-9_.]+$/', $user_id)
        && strpos($user_id, '..') === false;
}

/**
 * Retorna o caminho do config.ini de uma instância (considera layout novo e antigo).
 */
function caminhoConfigInstancia(string $user_id): ?string {
    if (!defined('INSTANCE_BASE_PATH')) {
        require_once __DIR__ . '/inc/paths.php';
    }
    $base = INSTANCE_BASE_PATH . '/' . $user_id;
    $novo = $base . '/config.ini';
    if (file_exists($novo)) return $novo;
    $legado = $base . '/config/config.ini';
    if (file_exists($legado)) return $legado;
    return null;
}

/**
 * Redefine a senha de uma instância (ADMIN e PAINEL usam o mesmo hash).
 *
 * @param string $user_id      Id da instância
 * @param string $novaSenha    Nova senha em texto plano (será gravada apenas como hash bcrypt)
 * @return array{sucesso:bool, erro?:string}
 */
function redefinirSenhaInstancia(string $user_id, string $novaSenha): array {
    if (!validarUserId($user_id)) {
        return ['sucesso' => false, 'erro' => 'ID de usuário inválido'];
    }
    if (strlen($novaSenha) < 8) {
        return ['sucesso' => false, 'erro' => 'Senha deve ter ao menos 8 caracteres'];
    }

    $configFile = caminhoConfigInstancia($user_id);
    if ($configFile === null) {
        return ['sucesso' => false, 'erro' => 'config.ini da instância não encontrado'];
    }

    $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);

    $conteudo = file_get_contents($configFile);
    if ($conteudo === false) {
        return ['sucesso' => false, 'erro' => 'Falha ao ler config.ini'];
    }

    // Substitui hashes e zera qualquer senha legada em texto plano
    $substituicoes = [
        '/^\s*SENHA_ADMIN_HASH\s*=.*$/mi'  => 'SENHA_ADMIN_HASH = "' . $hash . '"',
        '/^\s*SENHA_PAINEL_HASH\s*=.*$/mi' => 'SENHA_PAINEL_HASH = "' . $hash . '"',
        '/^\s*SENHA_ADMIN_REAL\s*=.*$/mi'  => 'SENHA_ADMIN_REAL = ""',
        '/^\s*SENHA_PAINEL\s*=.*$/mi'      => 'SENHA_PAINEL = ""',
    ];
    foreach ($substituicoes as $pattern => $replacement) {
        $conteudo = preg_replace($pattern, $replacement, $conteudo);
    }

    // Se alguma chave não existia, adiciona na seção [SEGURANCA]
    foreach (['SENHA_ADMIN_HASH', 'SENHA_PAINEL_HASH'] as $chave) {
        if (!preg_match('/^\s*' . $chave . '\s*=/mi', $conteudo)) {
            $conteudo = preg_replace(
                '/^\[SEGURANCA\].*$/mi',
                "[SEGURANCA]\n$chave = \"$hash\"",
                $conteudo,
                1
            );
        }
    }

    if (file_put_contents($configFile, $conteudo, LOCK_EX) === false) {
        return ['sucesso' => false, 'erro' => 'Falha ao gravar config.ini'];
    }
    @chmod($configFile, 0600);

    // Log
    if (defined('DATA_PATH')) {
        $log = DATA_PATH . '/resets_senha.log';
        $entry = date('Y-m-d H:i:s') . "|$user_id|senha redefinida pelo admin\n";
        @file_put_contents($log, $entry, FILE_APPEND | LOCK_EX);
    }

    return ['sucesso' => true];
}

/**
 * Gera uma senha temporária aleatória forte (letras+dígitos+especial).
 */
function gerarSenhaTemporaria(int $tamanho = 12): string {
    $letras = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
    $digitos = '23456789';
    $especiais = '!@#$%&*?';

    $base = $letras . $digitos;
    $senha = $letras[random_int(0, strlen($letras) - 1)]
           . $digitos[random_int(0, strlen($digitos) - 1)]
           . $especiais[random_int(0, strlen($especiais) - 1)];
    for ($i = strlen($senha); $i < $tamanho; $i++) {
        $senha .= $base[random_int(0, strlen($base) - 1)];
    }
    // embaralha
    $chars = str_split($senha);
    for ($i = count($chars) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
    }
    return implode('', $chars);
}