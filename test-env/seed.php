<?php
/**
 * seed.php — cria (ou recria) uma instância de teste pronta para uso.
 *
 * Estrutura gerada em test-env/instance/:
 *   config/
 *     config.ini              (com senhas em hash bcrypt)
 *     cadastro_criancas.txt   (3 registros fictícios)
 *     painel_criancas.txt     (1 saída de exemplo)
 *   public_html/ebi/
 *     index.php + inc/ + views/ + saida/   (cópias do template refatorado)
 *     config.ini (mesmo hash)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Execute via CLI: php test-env/seed.php\n");
}

$root = dirname(__DIR__);
$template = $root . '/ebi/template';
$instDir = __DIR__ . '/instance';

echo "🔧 Semeando ambiente de teste em $instDir\n";

// Senha e hash
$senhaPadrao = 'Senha123!';
$hash = password_hash($senhaPadrao, PASSWORD_BCRYPT, ['cost' => 12]);

// Limpa instância anterior
if (is_dir($instDir)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($instDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
    @rmdir($instDir);
}

$configDir = $instDir . '/config';
$publicDir = $instDir . '/public_html/ebi';
mkdir($configDir, 0700, true);
mkdir($publicDir, 0755, true);

// --- config.ini ---
$d = date('Y-m-d H:i:s');
$configIni = <<<INI
[INFO_SISTEMA]
NOME_SISTEMA = "EBI — Ambiente de Teste"
VERSAO = "2.0-test"
DATA_INSTALACAO = "$d"

[INFO_USUARIO]
NOME = "Usuário de Teste"
EMAIL = "teste@example.com"
CIDADE = "Localhost"
COMUM = "Teste"
USER_ID = "teste"
DATA_CRIACAO = "$d"

[GERAL]
ARQUIVO_DADOS = "/../../config/cadastro_criancas.txt"
ARQUIVO_DADOS_PAINEL = "/../../config/painel_criancas.txt"
DELIMITADOR = "|"
MAX_BACKUPS = 10
BACKUP_AUTOMATICO = true
NUM_LINHAS_FORMULARIO_CADASTRO = 5
NUM_CAMPOS_POR_LINHA_NO_ARQUIVO = 8
TIMEZONE = "America/Sao_Paulo"

[SEGURANCA]
SENHA_ADMIN_HASH = "$hash"
SENHA_PAINEL_HASH = "$hash"
SENHA_ADMIN_REAL = ""
SENHA_PAINEL = ""
TEMPO_SESSAO = 1800
MAX_TENTATIVAS_LOGIN = 5
TEMPO_BLOQUEIO = 300
CSRF_PROTECTION = true
LOG_TENTATIVAS_LOGIN = true

[IMPRESSORA_ZPL]
PRINTER_NAME = "Impressora Teste"
PALAVRA_CONTADOR_COMUM = "bonfim"
LISTA_PALAVRAS_CONTADOR_COMUM = "parque, parqui, par que"
TAMPULSEIRA = 269
DOTS = 8
FECHO = 30
FECHOINI = 1
URL_IMPRESSORA = "http://127.0.0.1:9100/write"
LARGURA_PULSEIRA = 192
IMPRIMIR_QRCODE = false
TAMANHO_QRCODE = 4

[INTERFACE]
TITULO_LOGIN = "Acesso — Ambiente de Teste"
LOGO_URL = "https://placehold.co/40x40/007bff/white?text=Kids"
COR_PRIMARIA = "#007bff"
COR_SECUNDARIA = "#0056b3"
MOSTRAR_RODAPE = true
TEXTO_RODAPE = "Ambiente de Teste Local"

[VALIDACAO]
MIN_TAMANHO_NOME_CRIANCA = 2
MAX_TAMANHO_NOME_CRIANCA = 100
MAX_TAMANHO_NOME_RESPONSAVEL = 100
IDADE_MINIMA = 0
IDADE_MAXIMA = 17
REGEX_TELEFONE = "/^[\\d\\s\\-\\(\\)]+$/"
MIN_TAMANHO_TELEFONE = 8
MAX_TAMANHO_TELEFONE = 20

[PROCESSAMENTO_NOMES]
MAX_CHARS_NOME_CRIANCA_PULSEIRA = 22
MAX_CHARS_NOME_RESPONSAVEL_PULSEIRA = 25
CONVERTER_MAIUSCULAS = true
REMOVER_ACENTOS = false

[LISTAGEM]
REGISTROS_POR_PAGINA = 0
ORDENACAO_PADRAO = "id"
DIRECAO_ORDENACAO = "ASC"
INI;

file_put_contents($configDir . '/config.ini', $configIni);
chmod($configDir . '/config.ini', 0600);
file_put_contents($publicDir . '/config.ini', $configIni);
chmod($publicDir . '/config.ini', 0600);

// --- Dados fictícios ---
$cadastros = "# Sistema de Cadastro de Crianças - Ambiente de Teste\n";
$cadastros .= "# Criado em: $d\n";
$cadastros .= "# Formato: ID|Nome|Responsável|Telefone|Idade|Comum|StatusImpresso|Portaria|CodResp\n";
$cadastros .= "1|João Silva|Maria Silva|11991234567|3|Bonfim|N|A|1\n";
$cadastros .= "2|Ana Costa|Pedro Costa|11987654321|4|Parque|N|B|2\n";
$cadastros .= "3|Lucas Souza|Carla Souza|11912345678|5|Central|N|A|3\n";
file_put_contents($configDir . '/cadastro_criancas.txt', $cadastros);

$painel = "# Registros de saída\n# timestamp|CodResp|NomeResponsavel|NomeCrianca|Portaria\n";
$painel .= time() . "|1|Maria Silva|João Silva|A\n";
file_put_contents($configDir . '/painel_criancas.txt', $painel);

// --- Copiar template (index.php, inc/, views/, saida/) ---
$copiar = function($origem, $destino) use (&$copiar) {
    if (is_dir($origem)) {
        if (!is_dir($destino)) mkdir($destino, 0755, true);
        foreach (scandir($origem) as $f) {
            if ($f === '.' || $f === '..') continue;
            $copiar("$origem/$f", "$destino/$f");
        }
    } elseif (is_file($origem)) {
        // não sobrescreve o config.ini já gerado
        if (basename($destino) === 'config.ini') return;
        copy($origem, $destino);
    }
};
foreach (['index.php','inc','views','saida'] as $item) {
    if (file_exists("$template/$item")) {
        $copiar("$template/$item", "$publicDir/$item");
    }
}

echo "✔ Instância criada.\n";
echo "  config.ini  : $configDir/config.ini\n";
echo "  index.php   : $publicDir/index.php\n";
echo "  senha       : $senhaPadrao (hash bcrypt gravado)\n";
echo "\nAcesse: http://127.0.0.1:8080/test-env/instance/public_html/ebi/index.php\n";
