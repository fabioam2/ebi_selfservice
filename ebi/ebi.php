<?php

// Lê as configurações do arquivo INI.
// O caminho assume a estrutura: /app_root/config/config.ini e /app_root/public_html/ebi/index.php
$config_file = __DIR__ . '/../../config/config.ini'; 

if (!file_exists($config_file)) {
    die("Erro: Arquivo de configuração não encontrado em: " . htmlspecialchars($config_file));
}

// Usa INI_SCANNER_TYPED para garantir que números sejam lidos como inteiros.
$config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);

// Define as Constantes de Configuração (Lê as seções do INI)
if (isset($config['GERAL'], $config['SEGURANCA'], $config['IMPRESSORA_ZPL'])) {
 
    // NOVO: Calcula o caminho absoluto para o arquivo de dados, que agora está na pasta 'config'
   // $data_file_path = __DIR__ . '/../../config/' . $config['GERAL']['ARQUIVO_DADOS'];
  $data_file_path = __DIR__ . $config['GERAL']['ARQUIVO_DADOS'];
 
    // [GERAL]
    define('ARQUIVO_DADOS', $data_file_path);
//    define('ARQUIVO_DADOS', $config['GERAL']['ARQUIVO_DADOS']);
    define('DELIMITADOR', $config['GERAL']['DELIMITADOR']);
    define('MAX_BACKUPS', $config['GERAL']['MAX_BACKUPS']);
    define('NUM_LINHAS_FORMULARIO_CADASTRO', $config['GERAL']['NUM_LINHAS_FORMULARIO_CADASTRO']);
    define('NUM_CAMPOS_POR_LINHA_NO_ARQUIVO', $config['GERAL']['NUM_CAMPOS_POR_LINHA_NO_ARQUIVO']);

    // [SEGURANCA]
    define('SENHA_ADMIN_REAL', $config['SEGURANCA']['SENHA_ADMIN_REAL']);
    // Senha para o login da aplicação (Depende de SENHA_ADMIN_REAL, não pode ser definida no INI)
    define('SENHA_LOGIN', SENHA_ADMIN_REAL); 

    // [IMPRESSORA_ZPL]
    define('TAMPULSEIRA', $config['IMPRESSORA_ZPL']['TAMPULSEIRA']);
    define('DOTS', $config['IMPRESSORA_ZPL']['DOTS']);
    define('FECHO', $config['IMPRESSORA_ZPL']['FECHO']);
    define('FECHOINI', $config['IMPRESSORA_ZPL']['FECHOINI']);
    
    // Constante Calculada (Depende de TAMPULSEIRA, FECHO e DOTS, não pode ser definida no INI)
    define('PULSEIRAUTIL', (TAMPULSEIRA-FECHO)*DOTS);
    
} else {
    die("Erro: Falta uma ou mais seções ([GERAL], [SEGURANCA], [IMPRESSORA_ZPL]) no arquivo de configuração.");
}


//Ebug no console
//ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function sanitize_for_html($string) {
    return htmlspecialchars(trim((string)($string ?? '')), ENT_QUOTES, 'UTF-8');
}

$mensagemLoginErro = '';
$loginPageMensagemSucesso = '';

// --- Processamento do Login ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tentativa_login'])) {
    if (isset($_POST['senha_login']) && $_POST['senha_login'] === SENHA_LOGIN) {
        $_SESSION['logado'] = true;
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    } else {
        $mensagemLoginErro = "Senha incorreta.";
    }
}

// --- Processamento do Logout ---
if (isset($_GET['acao']) && $_GET['acao'] == 'logout') {
    $_SESSION['logado'] = false;
    $_SESSION['logout_mensagem_sucesso'] = "Você saiu do sistema.";
    header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
    exit;
}

// --- Se não estiver logado, mostra tela de login ---
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    if (isset($_SESSION['logout_mensagem_sucesso'])) {
        $loginPageMensagemSucesso = $_SESSION['logout_mensagem_sucesso'];
        unset($_SESSION['logout_mensagem_sucesso']);
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cadastro de Crianças</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background-color: #eef2f7; font-family: 'Inter', sans-serif; }
        .login-container { background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 100%; max-width: 400px; }
        .login-container h2 { text-align: center; margin-bottom: 20px; color: #007bff; }
        .login-container h2 img { border: 1px solid #007bff; }
        .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        .btn-primary { background-color: #007bff; border-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
        .alert-login { margin-top: 15px; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2><img src="https://placehold.co/40x40/007bff/white?text=Kids" alt="Ícone" style="vertical-align: middle; border-radius: 50%; margin-right: 10px;"> Acesso ao Sistema</h2>
        <?php if ($loginPageMensagemSucesso): ?>
            <div class="alert alert-success alert-login"><?php echo sanitize_for_html($loginPageMensagemSucesso); ?></div>
        <?php endif; ?>
        <?php if ($mensagemLoginErro): ?>
            <div class="alert alert-danger alert-login"><?php echo sanitize_for_html($mensagemLoginErro); ?></div>
        <?php endif; ?>
        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="senha_login">Senha de Acesso:</label>
                <input type="password" class="form-control" id="senha_login" name="senha_login" required autofocus>
            </div>
            <button type="submit" name="tentativa_login" class="btn btn-primary btn-block">Entrar</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit;
}

// --- A PARTIR DAQUI, O USUÁRIO ESTÁ LOGADO ---

function sanitize_for_file($string) {
    return str_replace(DELIMITADOR, '-', trim($string ?? ''));
}

// --- Endpoint para Preview de Backup (GET request) ---
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['acao']) && $_GET['acao'] == 'preview_backup' && isset($_GET['arquivo'])) {
    $nomeArquivoBackup = basename(sanitize_for_file($_GET['arquivo']));
    $diretorioBase = dirname(ARQUIVO_DADOS);
    $caminhoCompletoBackup = ($diretorioBase === '.' ? '' : $diretorioBase . DIRECTORY_SEPARATOR) . $nomeArquivoBackup;

    if (strpos($nomeArquivoBackup, basename(ARQUIVO_DADOS) . '.bkp.') === 0 && file_exists($caminhoCompletoBackup)) {
        $linhas = file($caminhoCompletoBackup, FILE_IGNORE_NEW_LINES);
        if ($linhas !== false) {
            $ultimasLinhas = array_slice($linhas, -3);
            header('Content-Type: text/plain; charset=utf-8');
            echo implode("\n", $ultimasLinhas);
        } else {
            http_response_code(500);
            echo "Erro ao ler o arquivo de backup.";
        }
    } else {
        http_response_code(404);
        echo "Arquivo de backup não encontrado ou inválido: " . sanitize_for_html($nomeArquivoBackup);
    }
    exit;
}

function gerenciarBackups($caminhoArquivoBase) {
    if (!file_exists($caminhoArquivoBase) || filesize($caminhoArquivoBase) === 0) {
        return;
    }
    for ($i = MAX_BACKUPS; $i >= 1; $i--) {
        $backupAtual = $caminhoArquivoBase . '.bkp.' . $i;
        $backupProximo = $caminhoArquivoBase . '.bkp.' . ($i + 1);
        if (file_exists($backupAtual)) {
            if ($i == MAX_BACKUPS) {
                @unlink($backupAtual);
            } else {
                @rename($backupAtual, $backupProximo);
            }
        }
    }
    @copy($caminhoArquivoBase, $caminhoArquivoBase . '.bkp.1');
}

function listarBackups($caminhoArquivoBase) {
    $backups = [];
    for ($i = 1; $i <= MAX_BACKUPS; $i++) {
        $bkpFile = $caminhoArquivoBase . '.bkp.' . $i;
        if (file_exists($bkpFile)) {
            $backups[] = basename($bkpFile);
        }
    }
    return $backups;
}

function lerTodosCadastros($caminhoArquivo) {
    $cadastros = [];
    if (file_exists($caminhoArquivo) && filesize($caminhoArquivo) > 0) {
        $linhasFile = file($caminhoArquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($linhasFile === false) return [];
        foreach ($linhasFile as $linha) {
            $dados = explode(DELIMITADOR, $linha);
            if (count($dados) >= (NUM_CAMPOS_POR_LINHA_NO_ARQUIVO + 1) ) { 
                $id = intval(trim($dados[0]));
                $cadastros[$id] = [
                    'id' => $id,
                    'nomeCrianca'     => $dados[1] ?? '',
                    'nomeResponsavel' => $dados[2] ?? '',
                    'telefone'        => $dados[3] ?? '',
                    'idade'           => $dados[4] ?? '',
                    'comum'           => $dados[5] ?? '',
                    'statusImpresso'  => $dados[6] ?? 'N',
                    'portaria'        => strtoupper(trim($dados[7] ?? '')),
                    'cod_resp'        => $dados[8] ?? '' 
                ];
            }
        }
    }
    return $cadastros;
}

function gerarCodigoSequencialBase($caminhoArquivo) {
    $ultimo_id = 0;
    $cadastrosAtuais = lerTodosCadastros($caminhoArquivo);
    if (!empty($cadastrosAtuais)) {
        $ids = array_keys($cadastrosAtuais);
        if (!empty($ids)) {
            $ultimo_id = max($ids);
        }
    }
    return $ultimo_id + 1;
}

function gerarProximoCodResp($caminhoArquivo) {
    $cadastros = lerTodosCadastros($caminhoArquivo);
    $ultimoCodResp = 0;
    if (!empty($cadastros)) {
        $codsRespExistentes = [];
        foreach ($cadastros as $cadastro) {
            if (isset($cadastro['cod_resp']) && is_numeric($cadastro['cod_resp'])) {
                $codsRespExistentes[] = intval($cadastro['cod_resp']);
            }
        }
        if (!empty($codsRespExistentes)) {
            $ultimoCodResp = max($codsRespExistentes);
        }
    }
    return $ultimoCodResp + 1;
}

function processarNomeParaZPL($nomeCompleto, $maxLength = 0) {
    $nomeCompleto = trim((string)$nomeCompleto);
    if (empty($nomeCompleto) && $maxLength > 0 && $maxLength <=1) return "";

    $palavras = explode(' ', $nomeCompleto);
    $numPalavras = count($palavras);
    $nomeProcessado = $nomeCompleto;

    if ($numPalavras > 3) {
        $nomeProcessado = $palavras[0] . ' ' . $palavras[1] . ' ' . $palavras[$numPalavras - 1];
    }

    if ($maxLength > 0) {
        if (mb_strlen($nomeProcessado, 'UTF-8') > $maxLength) {
            if ($maxLength <= 1) {
                $nomeProcessado = mb_substr($nomeProcessado, 0, $maxLength, 'UTF-8');
            } else {
                $nomeProcessado = mb_substr($nomeProcessado, 0, $maxLength - 1, 'UTF-8') ;
                if (mb_strlen($nomeProcessado, 'UTF-8') > $maxLength) {
                    $nomeProcessado = mb_substr($nomeProcessado, 0, $maxLength, 'UTF-8');
                }
            }
        }
    }
    return $nomeProcessado;
}
 
function gerarCodigoZPL($nomeCrianca, $nomeResponsavel, $idade, $codigo, $telefone) {
    $fim_pulseira=TAMPULSEIRA*DOTS; // define a ultima posicão para imprimir.
    $ini_pos=PULSEIRAUTIL - (70*DOTS); //inicio das info: 7 cm de distancia do fim da pulseira.
    //$qrc_pos=$ini_pos + (65*DOTS); //QRCODE 6,5 CM depois da escrita
    // 6,5cm depois da escrita, o QRCODE
 
    //Ate 22 caracteres
    $nomeCriancaProcessado = processarNomeParaZPL($nomeCrianca,22);
    //Ate 25 caracteres
    $nomeResponsavelProcessado = processarNomeParaZPL($nomeResponsavel,25);

    $nomeCriancaLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeCriancaProcessado), 'UTF-8');
    $nomeResponsavelLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeResponsavelProcessado), 'UTF-8');
    $idadeLimpa = str_replace(['^', '~', '\\'], '', $idade);
    $codigoLimpo = str_replace(['^', '~', '\\'], '', $codigo);
    $telefone2 = str_replace(['^', '~', '\\'], '', $telefone);

    $zpl = "^XA" . PHP_EOL;
    $zpl .= "^CI28" . PHP_EOL;
    $zpl .= "^PW192" . PHP_EOL; //24mm de largura
    $zpl .= "^LL" . TAMPULSEIRA*DOTS . PHP_EOL; //270mm = 2160 | 285mm = 2281

    //
    $zpl .= "^FO80,". $ini_pos . "^A0R,60,50^FD" . $nomeCriancaLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO50,". $ini_pos . "^A0R,30,40^FDIdade: " . $idadeLimpa . " anos      Cod.:" . $codigoLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO10,". $ini_pos . "^A0R,30,35^FDRsp: " . $nomeResponsavelLimpo . "^FS" . PHP_EOL;

    //Imprimir QRCODE
    //desligado as 3 linhas abaixo 18/out/2025
    //Se etiqueta de 285 ->  270 o inicio é 2000 para 1820 (reduzir 120)
   // $dadosQrCode = $nomeCriancaLimpo . "_0DRsp " . $nomeResponsavelLimpo . "_0D" . $telefone2;
   // $zpl .= "^FH" . PHP_EOL; 
   // $zpl .= "^FO30,". $qrc_pos . "^BQN,2,4^FDMA," . $dadosQrCode . "^FS" . PHP_EOL; 
   
    //Teste
    //$zpl .= "^FO120,". $qrc_pos . "^A0R,60,50^FD" . ">     <" . "^FS" . PHP_EOL;
    //Teste
    //$zpl .= "^FO140,". $ini_pos . "^A0R,30,35^FD" . "." . "^FS" . PHP_EOL;

    //MARCA inicio pulseira 
    $zpl .= "^FO140,". "1" . "^A0R,30,35^FD" . "|" . "^FS" . PHP_EOL;
    //MARCA fim pulseira
    $zpl .= "^FO140," . (PULSEIRAUTIL-35) . "^A0R,30,35^FD" . "|" . "^FS" . PHP_EOL;

    $zpl .= "^PQ1,0,1,Y" . PHP_EOL; 
    $zpl .= "^XZ" . PHP_EOL;
    
//print $zpl;
    return $zpl;
}

function gerarCodigoZPLResponsavel($nomeResponsavel, $nomesCriancasDoGrupo,$codigo) {
    $fim_pulseira=TAMPULSEIRA*DOTS ; // define a ultima posicão para imprimir.
    $ini_pos=PULSEIRAUTIL - (95*DOTS); //inicio das info: 9 cm de distancia do fim da pulseira.
    $id_pos=$ini_pos + (55*DOTS); // 5,5 cm do inicio, o ID
    $yPosCriancas=$ini_pos; //Inicio da informacao, nome da crianca;
    
    //$id_pos=$fim_pulseira - (DOTS*50); // define a posicão do ID e nome do Resp[ ultima informacao]
    //$yPosCriancas=$id_pos - (50 * DOTS); //Nome das Criancas - 5 cm do inicio do ID e nome do Resp 
    
   
    $nomeResponsavelProcessado = processarNomeParaZPL($nomeResponsavel,22);
    $nomeResponsavelLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeResponsavelProcessado), 'UTF-8');
    $codigoLimpo = str_replace(['^', '~', '\\'], '', $codigo);

    $nomesCriancasLimpasEProcessadas = [];
    $dadosQrCodeParaZPL = $codigoLimpo . ";" ;
    $dadosQrCodeParaZPL .= $nomeResponsavelLimpo . ";"; 
      
    
    foreach ($nomesCriancasDoGrupo as $nomeCrianca) {
        $nomeCriancaProcessado = processarNomeParaZPL($nomeCrianca, 25);
        $nomeLimpo = mb_strtoupper(str_replace(['^', '~', '\\'], '', $nomeCriancaProcessado), 'UTF-8');
        $nomesCriancasLimpasEProcessadas[] = $nomeLimpo;
        $dadosQrCodeParaZPL .=  $nomeLimpo . ";"; 
    }
    //espaco = ."_0D"; 
    // $dadosQrCodeParaZPL .= "Rsp " . $nomeResponsavelLimpo; 


    $zpl = "^XA" . PHP_EOL;
    $zpl .= "^CI28" . PHP_EOL;
    $zpl .= "^PW192" . PHP_EOL; //24mm de largura
    $zpl .= "^LL" . TAMPULSEIRA*DOTS . PHP_EOL; //270mm = 2160 | 285mm = 2281

    $zpl .= "^FH" . PHP_EOL; //? 

    //Desligado o QRCODE  
    // $zpl .= "^FO20," . $ini_pos2 .  "^BQN,2,4^FDMA," . $dadosQrCodeParaZPL . "^FS" . PHP_EOL;

    $zpl .= "^FO70," . $id_pos   .  "^A0R,40,45^FDID:" . $codigoLimpo . "^FS" . PHP_EOL;
    $zpl .= "^FO10," . $id_pos  .  "^A0R,20,25^FDRsp:" . $nomeResponsavelLimpo . "^FS" . PHP_EOL;

    $posicoesX = [70, 35, 105, 0, 140 ]; 

    for ($k = 0; $k < 5; $k++) {
        $nomeParaExibir = "";
        if (isset($nomesCriancasLimpasEProcessadas[$k])) {
            $nomeParaExibir = $nomesCriancasLimpasEProcessadas[$k];
        } 
        $zpl .= "^FO" . $posicoesX[$k] . "," . $yPosCriancas . "^A0R,30,35^FD" . $nomeParaExibir . "^FS" . PHP_EOL;
    }
  
    //Marca inicio pulseira 
    $zpl .= "^FO140,". "1" . "^A0R,30,35^FD" . "|" . "^FS" . PHP_EOL;
    //Marca fim pulseira
    $zpl .= "^FO140," . (PULSEIRAUTIL-35) . "^A0R,30,35^FD" . "|" . "^FS" . PHP_EOL;

    $zpl .= "^PQ1,0,1,Y" . PHP_EOL;
    $zpl .= "^XZ" . PHP_EOL;
//print "Pulseira Resp>";
//print $zpl;
    return $zpl;
}



function salvarTodosCadastros($caminhoArquivo, $cadastros) {
    $linhasParaSalvar = [];
    foreach ($cadastros as $cadastro) {
        $linhasParaSalvar[] = implode(DELIMITADOR, [
            $cadastro['id'],
            $cadastro['nomeCrianca'],
            $cadastro['nomeResponsavel'],
            $cadastro['telefone'],
            $cadastro['idade'],
            $cadastro['comum'],
            $cadastro['statusImpresso'] ?? 'N',
            strtoupper(trim($cadastro['portaria'] ?? '')),
            $cadastro['cod_resp'] ?? ''
        ]);
    }
    $conteudo = implode(PHP_EOL, $linhasParaSalvar) . (count($linhasParaSalvar) > 0 ? PHP_EOL : '');
    return file_put_contents($caminhoArquivo, $conteudo, LOCK_EX) !== false;
}

$todosOsCadastros = lerTodosCadastros(ARQUIVO_DADOS);
$totalDeCadastrosGeral = count($todosOsCadastros);

$totalCriancas3Anos = 0;
foreach ($todosOsCadastros as $cadastro) {
    if (isset($cadastro['idade']) && (trim($cadastro['idade']) === '3' || trim($cadastro['idade']) === '03')) {
        $totalCriancas3Anos++;
    }
}



// Definição das palavras-chave para a contagem "Comum"
$palavrasChaveComum = ["bonfim", "bofim", "bonfin", "bomfim", "bon fim", "bom fin", "bom fim", "bon fin"];
$totalComum = 0;

foreach ($todosOsCadastros as $cadastro) {
    if (isset($cadastro['comum'])) {
        $comumValor = trim($cadastro['comum']);
        if (empty($comumValor)) {
            continue;
        }
        $comumLower = strtolower($comumValor);

        foreach ($palavrasChaveComum as $palavra) {
            if (stripos($comumLower, $palavra) !== false) {
                $totalComum++;
                break; 
            }
        }
    }
}


$mensagemSucesso = $_SESSION['mensagemSucesso'] ?? '';
$mensagemErro = $_SESSION['mensagemErro'] ?? '';
$exibirModalRecuperacao = $_SESSION['exibirModalRecuperacao'] ?? false;

unset($_SESSION['mensagemSucesso'], $_SESSION['mensagemErro'], $_SESSION['exibirModalRecuperacao']);

$backupsDisponiveis = [];
if ($exibirModalRecuperacao) {
    $backupsDisponiveis = listarBackups(ARQUIVO_DADOS);
}

$focarPrimeiroCampoAposCadastro = false;
if (isset($_SESSION['cadastro_realizado_sucesso']) && $_SESSION['cadastro_realizado_sucesso']) {
    $focarPrimeiroCampoAposCadastro = true;
    unset($_SESSION['cadastro_realizado_sucesso']);
}

$focarAposAcao = false;
if (isset($_SESSION['focar_apos_acao']) && $_SESSION['focar_apos_acao']) {
    $focarAposAcao = true;
    unset($_SESSION['focar_apos_acao']);
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['cadastrar'])) {
        $nomesCrianca = $_POST['nome_crianca'] ?? [];
        $nomesResponsavel = $_POST['nome_responsavel'] ?? [];
        $idades = $_POST['idade'] ?? [];
        $telefones = $_POST['telefone'] ?? [];
        $comuns = $_POST['comum'] ?? [];
        $portariaCadastro = strtoupper(trim($_POST['portaria_cadastro'] ?? ''));

        $cadastrosRealizadosComSucesso = 0;
        $errosNoCadastro = [];
        $backupRealizadoNestaOperacao = false;
        $linhasParaAdicionarAoArquivo = [];
        $codRespParaEsteLote = 0;
        $codRespDeterminado = false;

        if (empty($portariaCadastro) || !preg_match('/^[A-Z]$/', $portariaCadastro)) {
            $errosNoCadastro[] = "Portaria inválida. Coloque o código da portaria, uma única letra (A-Z). Nenhum cadastro foi realizado.";
        } else {
            $proximoIdDisponivel = gerarCodigoSequencialBase(ARQUIVO_DADOS);
            $contadorDeNovosIds = 0;

            for ($i = 0; $i < NUM_LINHAS_FORMULARIO_CADASTRO; $i++) {
                $nomeCriancaAtualTrimmed = trim($nomesCrianca[$i] ?? '');
                $nomeResponsavelAtualTrimmed = trim($nomesResponsavel[$i] ?? '');
                $idadeAtualTrimmed = trim($idades[$i] ?? '');
                $telefoneAtualTrimmed = trim($telefones[$i] ?? '');
                $comumAtualTrimmed = trim($comuns[$i] ?? '');

                $linhaFoiIniciada = !empty($nomeCriancaAtualTrimmed) || !empty($nomeResponsavelAtualTrimmed) || !empty($idadeAtualTrimmed) || !empty($telefoneAtualTrimmed) || !empty($comumAtualTrimmed);

                if ($linhaFoiIniciada) {
                    if (empty($nomeCriancaAtualTrimmed) || empty($nomeResponsavelAtualTrimmed) || empty($idadeAtualTrimmed) || empty($telefoneAtualTrimmed) || empty($comumAtualTrimmed)) {
                        $errosNoCadastro[] = "Linha " . ($i + 1) . ": Todos os campos são obrigatórios se a linha for preenchida.";
                        continue;
                    }

                    if (!$backupRealizadoNestaOperacao && file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                        gerenciarBackups(ARQUIVO_DADOS);
                        $backupRealizadoNestaOperacao = true;
                    }

                    if (!$codRespDeterminado) {
                        $codRespParaEsteLote = gerarProximoCodResp(ARQUIVO_DADOS);
                        $codRespDeterminado = true;
                    }

                    $idAtualParaNovoCadastro = $proximoIdDisponivel + $contadorDeNovosIds;

                    $linhasParaAdicionarAoArquivo[] = implode(DELIMITADOR, [
                        $idAtualParaNovoCadastro,
                        sanitize_for_file($nomeCriancaAtualTrimmed),
                        sanitize_for_file($nomeResponsavelAtualTrimmed),
                        sanitize_for_file($telefoneAtualTrimmed),
                        sanitize_for_file($idadeAtualTrimmed),
                        sanitize_for_file($comumAtualTrimmed),
                        'N', 
                        $portariaCadastro,
                        $codRespParaEsteLote
                    ]);
                    $cadastrosRealizadosComSucesso++;
                    $contadorDeNovosIds++;
                }
            }

            if ($cadastrosRealizadosComSucesso > 0) {
                $conteudoParaEscrever = implode(PHP_EOL, $linhasParaAdicionarAoArquivo) . PHP_EOL;
                if (!file_put_contents(ARQUIVO_DADOS, $conteudoParaEscrever, FILE_APPEND | LOCK_EX)) {
                    $errosNoCadastro[] = "Erro crítico: Falha ao salvar os novos cadastros no arquivo.";
                    $cadastrosRealizadosComSucesso = 0; 
                }
            }
        }

        if (!empty($errosNoCadastro)) {
            $mensagemDeErroFormatada = "<strong>Atenção - Erros no Cadastro:</strong><br>" . implode("<br>", $errosNoCadastro);
            if ($cadastrosRealizadosComSucesso > 0) {
                $_SESSION['mensagemSucesso'] = $cadastrosRealizadosComSucesso . " cadastro(s) realizado(s) com sucesso.";
                $_SESSION['mensagemErro'] = $mensagemDeErroFormatada;
            } else {
                $_SESSION['mensagemErro'] = $mensagemDeErroFormatada;
            }
            $_SESSION['focar_apos_acao'] = true;
        } elseif ($cadastrosRealizadosComSucesso > 0) {
            $_SESSION['mensagemSucesso'] = $cadastrosRealizadosComSucesso . " cadastro(s) realizado(s)!";
            $_SESSION['cadastro_realizado_sucesso'] = true;
        }

        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    elseif (isset($_POST['imprimir'])) {
        $_SESSION['focar_apos_acao'] = true;

        $algumaImpressaoEnviada = false;
        $contadorImpressoesCriancas = 0;
        $contadorImpressoesResponsaveis = 0;
        $scriptsParaExecutar = "";
        $responsaveisParaEtiquetas = [];

        if (isset($_POST['selecionados']) && is_array($_POST['selecionados']) && count($_POST['selecionados']) > 0) {
            foreach ($_POST['selecionados'] as $idSelecionadoStr) {
                $idSelecionado = intval($idSelecionadoStr);
                if (isset($todosOsCadastros[$idSelecionado])) {
                    $crianca = $todosOsCadastros[$idSelecionado];
                    $codigoZPLCrianca = gerarCodigoZPL($crianca['nomeCrianca'], $crianca['nomeResponsavel'], $crianca['idade'], $crianca['id'], $crianca['telefone']);
                    $urlImpressora = "http://127.0.0.1:9100/write"; 
                    $payloadCrianca = ["device" => ["name" => "ZDesigner 105SL", "uid" => "ZDesigner 105SL", "connection" => "driver", "deviceType" => "printer", "version" => 2, "provider" => "com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider", "manufacturer" => "Zebra Technologies"], "data" => $codigoZPLCrianca];
                    
                    $jsonPayloadCrianca = json_encode($payloadCrianca);
                    if ($jsonPayloadCrianca === false) {
                        error_log("JSON encode error for child payload ID " . $idSelecionado . ": " . json_last_error_msg());
                        $_SESSION['mensagemErro'] = ($_SESSION['mensagemErro'] ?? '') . "<br>Erro ao preparar dados de impressão para criança ID " . $idSelecionado . ".";
                        continue; 
                    }

                    $scriptsParaExecutar .= "<script>
(function() {
    var url = " . json_encode($urlImpressora) . ";
    var payload = " . $jsonPayloadCrianca . ";
    var currentId = " . $idSelecionado . ";
    var nomeCrianca = \"" . addslashes(sanitize_for_html($crianca['nomeCrianca'])) . "\";
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    .then(response => {
        if (!response.ok) return response.text().then(text => { throw new Error('Falha (criança ' + nomeCrianca + '): ' + response.status + ' ' + text); });
        return response.text();
    })
    .then(result => {
        console.log('Etiqueta CRIANÇA ID ' + currentId + ' (' + nomeCrianca + ') enviada. Resposta: ' + result);
        var row = $('tr[data-id=\"' + currentId + '\"]');
        if (row.length) {
            row.find('.status-icon').html('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"18\" height=\"18\" fill=\"green\" class=\"bi bi-check-circle-fill\" viewBox=\"0 0 16 16\"><path d=\"M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z\"/></svg>');
            row.find('.checkbox-crianca').prop('checked', false);
        }
    })
    .catch(error => {
        console.error('Erro CRIANÇA ID ' + currentId + ' (' + nomeCrianca + '):', error);
    });
})();
</script>";
                    $todosOsCadastros[$idSelecionado]['statusImpresso'] = 'S';
                    $contadorImpressoesCriancas++;
                    $algumaImpressaoEnviada = true;

                    $codResp = $crianca['cod_resp'];
                    if (!empty($codResp)) {
                        if (!isset($responsaveisParaEtiquetas[$codResp])) {
                            $responsaveisParaEtiquetas[$codResp] = [
                                'nomeResponsavel' => $crianca['nomeResponsavel'],
                                'criancas' => []
                            ];
                        }
                        $responsaveisParaEtiquetas[$codResp]['criancas'][] = $crianca['nomeCrianca'];
                    }
                }
            }

            if ($algumaImpressaoEnviada) {
                 if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                    gerenciarBackups(ARQUIVO_DADOS);
                }
                salvarTodosCadastros(ARQUIVO_DADOS, $todosOsCadastros);
                

                    

                foreach ($responsaveisParaEtiquetas as $codResp => $dataResp) {
                    if (!empty($dataResp['criancas'])) {
                        $codigoZPLResponsavel = gerarCodigoZPLResponsavel($dataResp['nomeResponsavel'], $dataResp['criancas'],$codResp);
                        $payloadResponsavel = ["device" => ["name" => "ZDesigner 105SL", "uid" => "ZDesigner 105SL", "connection" => "driver", "deviceType" => "printer", "version" => 2, "provider" => "com.zebra.ds.webdriver.desktop.provider.DefaultDeviceProvider", "manufacturer" => "Zebra Technologies"], "data" => $codigoZPLResponsavel];
                        
                        $jsonPayloadResponsavel = json_encode($payloadResponsavel);
                        if ($jsonPayloadResponsavel === false) {
                             error_log("JSON encode error for responsible payload cod_resp " . $codResp . ": " . json_last_error_msg());
                             $_SESSION['mensagemErro'] = ($_SESSION['mensagemErro'] ?? '') . "<br>Erro ao preparar dados de impressão para responsável do lote " . $codResp . ".";
                             continue; 
                        }

                        $scriptsParaExecutar .= "<script>
(function() {
    var url = " . json_encode($urlImpressora) . ";
    var payload = " . $jsonPayloadResponsavel . ";
    var codResp = \"" . addslashes(sanitize_for_html($codResp)) . "\";
    var nomeResp = \"" . addslashes(sanitize_for_html($dataResp['nomeResponsavel'])) . "\";
    fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    .then(response => {
        if (!response.ok) return response.text().then(text => { throw new Error('Falha (responsável ' + nomeResp + '): ' + response.status + ' ' + text); });
        return response.text();
    })
    .then(result => {
        console.log('Etiqueta RESPONSÁVEL Cód. Lote ' + codResp + ' (' + nomeResp + ') enviada. Resposta: ' + result);
    })
    .catch(error => {
        console.error('Erro RESPONSÁVEL Cód. Lote ' + codResp + ' (' + nomeResp + '):', error);
    });
})();
</script>";
                        $contadorImpressoesResponsaveis++;
                    }
                }
            }
            
            if ($contadorImpressoesCriancas > 0) {
                $msg = $contadorImpressoesCriancas . " etiqueta(s) de criança(s) processada(s).";
                if ($contadorImpressoesResponsaveis > 0) {
                    $msg .= " " . $contadorImpressoesResponsaveis . " etiqueta(s) de responsável(is) também processada(s).";
                }
                $_SESSION['mensagemSucesso'] = ($_SESSION['mensagemSucesso'] ?? '') . $msg . " Status atualizado.";
            } elseif (isset($_POST['selecionados']) && count($_POST['selecionados']) > 0 && empty($_SESSION['mensagemErro'])) {
                 $_SESSION['mensagemErro'] = "Nenhuma criança válida para os IDs selecionados foi encontrada para impressão.";
            }

            if ($algumaImpressaoEnviada) {
                $scriptsParaExecutar .= "<script>
    (function() {
        $(document).ready(function() {
            setTimeout(function() {
                if ($('.checkbox-crianca:checked').length === 0) {
                     $('#selecionarTodos').prop('checked', false);
                }
            }, 2000); 
        });
    })();
    </script>";
            }
            echo $scriptsParaExecutar; 

        } else {
            $_SESSION['mensagemErro'] = "Nenhuma criança selecionada para impressão!";
            header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
            exit;
        }
    }
    elseif (isset($_POST['acao']) && $_POST['acao'] == 'apagar_linha') {
        if (isset($_POST['id_para_apagar'])) {
            $idParaApagar = intval($_POST['id_para_apagar']);
            if (isset($todosOsCadastros[$idParaApagar])) {
                $nomeCriancaApagada = $todosOsCadastros[$idParaApagar]['nomeCrianca'];
                unset($todosOsCadastros[$idParaApagar]);
                if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                    gerenciarBackups(ARQUIVO_DADOS);
                }
                if (salvarTodosCadastros(ARQUIVO_DADOS, $todosOsCadastros)) {
                    $_SESSION['mensagemSucesso'] = "Cadastro de '" . sanitize_for_html($nomeCriancaApagada) . "' (ID: " . $idParaApagar . ") apagado.";
                } else { $_SESSION['mensagemErro'] = "Erro ao salvar após apagar."; $_SESSION['focar_apos_acao'] = true;}
            } else { $_SESSION['mensagemErro'] = "Cadastro ID " . $idParaApagar . " não encontrado."; $_SESSION['focar_apos_acao'] = true;}
        } else { $_SESSION['mensagemErro'] = "ID para apagar não especificado."; $_SESSION['focar_apos_acao'] = true;}
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    elseif (isset($_POST['zerar_arquivo_confirmado'])) {
        if (isset($_POST['admin_senha']) && $_POST['admin_senha'] === SENHA_ADMIN_REAL) {
            $bkp1_path = ARQUIVO_DADOS . '.bkp.1';
            
            if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                gerenciarBackups(ARQUIVO_DADOS); 
            }

            if (file_put_contents(ARQUIVO_DADOS, "", LOCK_EX) !== false) {
                $_SESSION['mensagemSucesso'] = "Arquivo de cadastros zerado com sucesso!";
                $todosOsCadastros = []; 

                $outrosRemovidos = false;
                for ($i = 2; $i <= MAX_BACKUPS; $i++) {
                    $backupParaApagar = ARQUIVO_DADOS . '.bkp.' . $i;
                    if (file_exists($backupParaApagar)) {
                        @unlink($backupParaApagar);
                        $outrosRemovidos = true;
                    }
                }
                if($outrosRemovidos) $_SESSION['mensagemSucesso'] .= " Backups antigos (.bkp.2+) removidos.";
                
                $backupsRestantes = listarBackups(ARQUIVO_DADOS);
                if (count($backupsRestantes) === 1 && basename($backupsRestantes[0]) === basename($bkp1_path)) {
                    if (file_exists($bkp1_path)) { 
                        @unlink($bkp1_path);
                        $_SESSION['mensagemSucesso'] .= " Backup .bkp.1 (sendo o único restante) também foi removido.";
                    }
                } elseif (file_exists($bkp1_path)) {
                     $_SESSION['mensagemSucesso'] .= " Backup .bkp.1 mantido.";
                }

            } else {
                $_SESSION['mensagemErro'] = "Erro ao zerar o arquivo.";
                $_SESSION['focar_apos_acao'] = true;
            }
        } else {
            $_SESSION['mensagemErro'] = "Senha administrativa incorreta.";
            $_SESSION['focar_apos_acao'] = true;
        }
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    elseif (isset($_POST['preparar_recuperacao'])) {
        $_SESSION['exibirModalRecuperacao'] = true;
        $_SESSION['focar_apos_acao'] = true;
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    elseif (isset($_POST['limpar_flag_modal_recuperacao'])) {
        $_SESSION['exibirModalRecuperacao'] = false;
        $_SESSION['focar_apos_acao'] = true; 
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
    elseif (isset($_POST['confirmar_recuperacao'])) {
        if (isset($_POST['admin_senha'], $_POST['arquivo_backup_selecionado']) && $_POST['admin_senha'] === SENHA_ADMIN_REAL) {
            $backupSelecionadoNome = basename(sanitize_for_file($_POST['arquivo_backup_selecionado']));
            $diretorioBase = dirname(ARQUIVO_DADOS);
            $caminhoBackupSelecionado = ($diretorioBase === '.' ? '' : $diretorioBase . DIRECTORY_SEPARATOR) . $backupSelecionadoNome;

            if (strpos($backupSelecionadoNome, basename(ARQUIVO_DADOS) . '.bkp.') === 0 && file_exists($caminhoBackupSelecionado)) {
                if (file_exists(ARQUIVO_DADOS) && filesize(ARQUIVO_DADOS) > 0) {
                    gerenciarBackups(ARQUIVO_DADOS);
                }
                if (copy($caminhoBackupSelecionado, ARQUIVO_DADOS)) {
                    $_SESSION['mensagemSucesso'] = "Backup '" . sanitize_for_html($backupSelecionadoNome) . "' restaurado com sucesso!";
                } else {
                    $_SESSION['mensagemErro'] = "Erro ao restaurar o backup '" . sanitize_for_html($backupSelecionadoNome) . "'.";
                }
            } else {
                $_SESSION['mensagemErro'] = "Arquivo de backup '" . sanitize_for_html($backupSelecionadoNome) . "' inválido ou não encontrado.";
            }
        } else {
            $_SESSION['mensagemErro'] = isset($_POST['admin_senha']) && $_POST['admin_senha'] !== SENHA_ADMIN_REAL ? "Senha administrativa incorreta para recuperação." : "Informações insuficientes para recuperação.";
        }
        $_SESSION['exibirModalRecuperacao'] = false; 
        $_SESSION['focar_apos_acao'] = true;
        header("Location: " . sanitize_for_html($_SERVER['PHP_SELF']));
        exit;
    }
}

if (!empty($mensagemErro) && !isset($_SESSION['focar_apos_acao']) && !isset($_SESSION['cadastro_realizado_sucesso'])) {
    $_SESSION['focar_apos_acao'] = true;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Crianças</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #eef2f7; }
        .container { margin-top: 20px; padding: 20px; background-color: #ffffff; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-width: 1300px; }
        .tabela-scrollable { max-height: 400px; overflow-y: auto; margin-bottom: 20px; border: 1px solid #dee2e6; border-radius: 8px; }
        .tabela-scrollable table { width: 100%; margin-bottom: 0; }
        .tabela-scrollable th { background-color: #007bff; color: white; position: sticky; top: 0; z-index: 10; font-size: 0.9rem; padding: 0.5rem; text-align: center; }
        .tabela-scrollable td { font-size: 0.85rem; padding: 0.4rem; vertical-align: middle; }
        .tabela-scrollable th:nth-child(6), .tabela-scrollable th:nth-child(7), .tabela-scrollable th:nth-child(10) { text-align: left;} 
        .tabela-scrollable td:nth-child(6), .tabela-scrollable td:nth-child(7), .tabela-scrollable td:nth-child(10) { text-align: left;}

        .form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem .5rem; font-size: .875rem; line-height: 1.5; border-radius: .2rem; }
        .btn { margin-right: 8px; border-radius: 5px; padding: 8px 15px; transition: all 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .alert { border-radius: 8px; margin-bottom: 15px; }
        header h1 img { border: 2px solid #007bff; }
        .form-control { border-radius: 5px; border-color: #ced4da; }
        .form-control:focus { border-color: #80bdff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }

        #formNovoCadastro .form-labels .col { font-weight: bold; color: #495057; padding-bottom: 0.2rem; font-size: 0.85rem; white-space: nowrap; }
        #formNovoCadastro .form-registro-linha { margin-bottom: 0.25rem; padding: 0.15rem 0; }
        #formNovoCadastro .form-registro-linha .form-group { margin-bottom: 0.1rem; padding-left: 5px; padding-right: 5px; }
        #formNovoCadastro .form-control-sm { font-size: 0.85rem; }

        .col-nome-crianca { flex: 0 0 23%; max-width: 23%; }
        .col-responsavel { flex: 0 0 23%; max-width: 23%; }
        .col-idade { flex: 0 0 9%; max-width: 9%; }
        .col-telefone { flex: 0 0 18%; max-width: 18%; }
        .col-comum { flex: 0 0 18%; max-width: 18%; }
        .col-acao { flex: 0 0 9%; max-width: 9%; }


        .dropdown-menu button.dropdown-item, .dropdown-menu a.dropdown-item { cursor: pointer; }
        .modal-backdrop.show { opacity: .5; }
        .modal.show { display: block; }
        #backupPreviewContent { font-size: 0.8em; white-space: pre-wrap; max-height: 100px; overflow-y: auto; background-color: #f8f9fa; border: 1px solid #dee2e6; padding: 5px; margin-top: 10px; border-radius: .2rem;}
        .status-icon svg { vertical-align: middle; }

        .filtro-portaria-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .filtro-portaria-container .form-label { margin-right: 0.5rem; margin-bottom: 0; white-space: nowrap;}
        #filtroPortaria {
            height: calc(1.5em + .5rem + 2px); 
            padding-left: 0.3rem !important; 
            padding-right: 0.3rem !important;
            margin-right: 0.5rem;
            width: auto; 
            min-width: 100px; 
            display: inline-block; 
        }
        .filtro-portaria-group { display: flex; align-items: center; }

        .btn-copiar-quadrado {
            width: 31px;  
            height: 31px; 
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .btn-copiar-quadrado svg { 
            width: 16px;
            height: 16px;
        }

        .total-cadastros-info {
            font-size: 0.9rem;
            color: #fff;
            background-color: #007bff; 
            border: 1px solid #007bff;
            padding: 0.375rem 0.75rem;
            border-radius: .2rem;
            margin-left: 10px;
            margin-right: 10px; 
            align-self: center; 
            display: inline-flex; 
            align-items: center; 
        }
        .total-cadastros-info svg {
            margin-right: 0.35rem;
            vertical-align: -0.1em; 
        }
        
        .total-cadastros-alerta {
             background-color: #dc3545 !important; /* Vermelho de perigo do Bootstrap */
              border-color: #b22222 !important;
        }
        .portaria-cadastro-group {
            display: flex;
            align-items: center;
            background-color: #17a2b8; 
            padding: 0.375rem 0.75rem;
            border-radius: .25rem;
            border: 1px solid #117a8b; 
        }
        .portaria-cadastro-group label {
            margin-bottom: 0;
            margin-right: 0.5rem;
            font-weight: normal;
            color: #fff; 
        }
        .portaria-cadastro-group input {
            border: none !important;
            background-color: transparent !important;
            box-shadow: none !important;
            padding-left: 0.2rem !important;
            width: 40px !important; 
            color: #fff !important; 
            text-transform: uppercase; 
        }
        .portaria-cadastro-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
            opacity: 1; 
        }
        .portaria-cadastro-group input:-ms-input-placeholder { 
            color: rgba(255, 255, 255, 0.7);
        }
        .portaria-cadastro-group input::-ms-input-placeholder { 
            color: rgba(255, 255, 255, 0.7);
        }

        @media print {
            body { font-size: 10pt; }
            .container { box-shadow: none; margin-top: 0; padding: 0; max-width: 100%; }
            header, .alert, #formNovoCadastro, .filtro-portaria-container, #formListaCriancas .d-flex.justify-content-between, .modal, .btn, form[action*="logout"], .dropdown, .no-print {
                display: none !important;
            }
            .tabela-scrollable { max-height: none; overflow-y: visible; border: none; }
            .tabela-scrollable th { background-color: #f0f0f0 !important; color: #000 !important; font-size: 9pt; }
            .tabela-scrollable td { font-size: 9pt; }
            .tabela-scrollable th, .tabela-scrollable td { padding: 3px; border: 1px solid #ccc; }
            #lista-criancas tr td:first-child, #lista-criancas tr th:first-child { display: none; }
            #lista-criancas tr td:last-child, #lista-criancas tr th:last-child { display: none; }
            .status-icon svg { display: none; } 
            .status-icon .print-status { display: inline !important; } 
        }
        .status-icon .print-status { display: none; } 

    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="text-center mb-3">
            <h1><img src="https://placehold.co/60x60/007bff/white?text=Kids" alt="Ícone de Criança" style="vertical-align: middle; border-radius: 50%; margin-right: 10px;"> Cadastro de Crianças</h1>
        </header>

        <?php if ($mensagemSucesso): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $mensagemSucesso; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if ($mensagemErro): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $mensagemErro; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" class="mb-3 p-3 border rounded bg-light shadow-sm" id="formNovoCadastro">
            <div class="form-row form-labels d-none d-md-flex">
                <div class="col col-nome-crianca">Nome Criança</div>
                <div class="col col-responsavel">Responsável</div>
                <div class="col col-idade text-center">Idade</div>
                <div class="col col-telefone">Telefone</div>
                <div class="col col-comum">Comum</div>
                <div class="col col-acao text-center">Ação</div>
            </div>

            <?php for ($linha = 0; $linha < NUM_LINHAS_FORMULARIO_CADASTRO; $linha++): ?>
            <div class="form-row align-items-center form-registro-linha">
                <div class="form-group col-md col-nome-crianca">
                    <label for="input_<?php echo $linha; ?>_0" class="d-md-none">Nome Criança <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_0" name="nome_crianca[]" data-linha="<?php echo $linha; ?>" data-col="0" placeholder="Nome da Criança">
                </div>
                <div class="form-group col-md col-responsavel">
                    <label for="input_<?php echo $linha; ?>_1" class="d-md-none">Responsável <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_1" name="nome_responsavel[]" data-linha="<?php echo $linha; ?>" data-col="1" placeholder="Nome do Responsável">
                </div>
                <div class="form-group col-md col-idade">
                    <label for="input_<?php echo $linha; ?>_2" class="d-md-none">Idade <?php echo $linha + 1; ?>:</label>
                    <input type="number" class="form-control form-control-sm cadastro-input text-center" id="input_<?php echo $linha; ?>_2" name="idade[]" min="0" data-linha="<?php echo $linha; ?>" data-col="2" placeholder="Idade">
                </div>
                <div class="form-group col-md col-telefone">
                    <label for="input_<?php echo $linha; ?>_3" class="d-md-none">Telefone <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm telefone-mask cadastro-input" id="input_<?php echo $linha; ?>_3" name="telefone[]" data-linha="<?php echo $linha; ?>" data-col="3" placeholder="(00) 00000-0000">
                </div>
                <div class="form-group col-md col-comum">
                    <label for="input_<?php echo $linha; ?>_4" class="d-md-none">Comum <?php echo $linha + 1; ?>:</label>
                    <input type="text" class="form-control form-control-sm cadastro-input" id="input_<?php echo $linha; ?>_4" name="comum[]" data-linha="<?php echo $linha; ?>" data-col="4" placeholder="Comum">
                </div>
                <div class="form-group col-md col-acao px-1 d-flex align-items-center justify-content-center">
                    <?php if ($linha > 0): ?>
                    <button type="button" class="btn btn-primary btn-sm btn-copiar-quadrado btn-copiar-dados" data-target-linha="<?php echo $linha; ?>" title="Copiar Responsável, Telefone e Comum da Linha 1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-files" viewBox="0 0 16 16">
                            <path d="M13 0H6a2 2 0 0 0-2 2 2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7a2 2 0 0 0 2-2 2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm0 13V4a2 2 0 0 0-2-2H5a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1zM3 4a1 1 0 0 1 1-1h7a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4z"/>
                        </svg>
                    </button>
                    <button type="button" class="btn btn-warning btn-sm btn-copiar-quadrado ml-1" title="Limpar esta linha" onclick="limparLinhaCadastro(<?php echo $linha; ?>)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eraser" viewBox="0 0 16 16">
                            <path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828zm2.121.707a1 1 0 0 0-1.414 0L4.16 7.547l5.293 5.293 4.633-4.633a1 1 0 0 0 0-1.414zM8.708 13.293l.019-.019-3.44-3.441.013.012a2.5 2.5 0 0 1 3.408 3.416z"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>

            <div class="form-row mt-3 align-items-end">
                <div class="col-auto mr-auto">
                    <div class="portaria-cadastro-group">
                        <label for="portaria_cadastro" class="form-label">Portaria:</label>
                        <input type="text" class="form-control form-control-sm" id="portaria_cadastro" name="portaria_cadastro" placeholder="" maxlength="1">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-warning btn-sm mr-2" id="btnLimparCadastro" title="Limpar campos do formulário de cadastro">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eraser mr-1" viewBox="0 0 16 16"><path d="M8.086 2.207a2 2 0 0 1 2.828 0l3.879 3.879a2 2 0 0 1 0 2.828l-5.5 5.5A2 2 0 0 1 7.879 15H5.12a2 2 0 0 1-1.414-.586l-2.5-2.5a2 2 0 0 1 0-2.828zm2.121.707a1 1 0 0 0-1.414 0L4.16 7.547l5.293 5.293 4.633-4.633a1 1 0 0 0 0-1.414zM8.708 13.293l.019-.019-3.44-3.441.013.012a2.5 2.5 0 0 1 3.408 3.416z"/></svg>
                        Limpar
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm" name="cadastrar" id="btnCadastrar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill mr-1" viewBox="0 0 16 16"><path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/><path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/></svg>
                        Cadastrar
                    </button>
                </div>
            </div>
        </form>
        <hr class="my-3">

        <div class="filtro-portaria-container">
            <div class="filtro-portaria-group">
                <label for="filtroPortaria" class="form-label">Filtrar Portaria:</label>
                <select multiple class="form-control form-control-sm" id="filtroPortaria" name="filtro_portaria_selecionadas[]"></select>
                <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="limparFiltroPortaria">Limpar Filtro</button>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm" id="btnImprimirLista">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer mr-1" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
                    Lista Completa
                </button>
            </div>
        </div>


        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formListaCriancas">
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button type="submit" class="btn btn-success" name="imprimir"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer-fill mr-2" viewBox="0 0 16 16"><path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1"/><path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2zm3 4a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/></svg>Imprimir</button>
                    <div class="total-cadastros-info <?php if ($totalDeCadastrosGeral > 90) echo 'total-cadastros-alerta'; ?>" title="Total de crianças cadastradas"> 
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                        Total: <?php echo $totalDeCadastrosGeral; ?>
                    </div>
                    <div class="total-cadastros-info" title="Total de crianças com 3 anos">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-cake2" viewBox="0 0 16 16"><path d="M11.05 4.05a2.5 2.5 0 1 0-4.999.058A2.5 2.5 0 0 0 11.05 4.05zm-4.01-.034a1.5 1.5 0 1 1 2.998-.033A1.5 1.5 0 0 1 7.04 4.016z"/><path d="M6.536 6.072L5.85 7.305A.5.5 0 0 0 6.29 8h3.42a.5.5 0 0 0 .44-.695l-.686-1.233L13.617 5.25a.5.5 0 0 0-.39-.867H2.773a.5.5 0 0 0-.39.867l4.153.822z"/><path d="M12.572 6.092L12 6.224v4.248c.782.396 1.595.24 2.222-.457.628-.698.782-1.61.396-2.393-.386-.783-1.2-.937-1.932-.783zm-1.03 4.355V6.35H4.458v4.097c-.782-.396-1.595-.24-2.222.457-.628-.698-.782-1.61-.396-2.393.386.783 1.2.937 1.932.783A2.91 2.91 0 0 0 4.3 12.57a2.91 2.91 0 0 0 3.572 1.818c.782.396 1.595.24 2.222-.457.628-.698.782-1.61.396-2.393-.386-.783-1.2-.937-1.932-.783A2.91 2.91 0 0 0 11.542 10.447zM4.907 11.32c-.185.059-.354.15-.495.271-.14.12-.242.265-.304.423l-.066.165c-.073.188-.098.388-.066.58.03.18.113.348.235.485.122.137.28.238.458.29.178.053.368.057.546.013l.126-.03.11-.042.108-.054.092-.06a1.08 1.08 0 0 1 .23-.167c.05-.04.094-.085.132-.133.09-.114.155-.245.19-.383.036-.137.043-.28.023-.416a.97.97 0 0 0-.133-.437c-.08-.14-.19-.26-.32-.35-.13-.09-.27-.14-.41-.16l-.112-.01z"/></svg>
                        3 Anos: <?php echo $totalCriancas3Anos; ?>
                    </div>
                    <div class="total-cadastros-info" title="Total de cadastros da comum Bonfim e similares">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-house-heart-fill" viewBox="0 0 16 16">
                            <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                            <path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Zm0 5.189c1.664-1.673 5.825 1.254 0 5.018-5.825-3.764-1.664-6.691 0-5.018Z"/>
                        </svg>
                        Comum: <?php echo $totalComum; ?>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuAdmin" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill mr-2" viewBox="0 0 16 16"><path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311a1.464 1.464 0 0 1-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413-1.4-2.397 0-2.81l.34-.1a1.464 1.464 0 0 1 .872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.858 2.929 2.929 0 0 1 0 5.858z"/></svg>
                        Ações Admin
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuAdmin">
                        <button class="dropdown-item" type="button" onclick="abrirModalZerarArquivo()">Zerar Arquivo</button>
                        <button class="dropdown-item" type="submit" name="preparar_recuperacao" form="formListaCriancas">Recuperar Backup <small class="text-muted">(.bkp.1 é o mais recente)</small></button>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?php echo sanitize_for_html($_SERVER['PHP_SELF']); ?>?acao=logout">Sair do Sistema</a>
                    </div>
                </div>
            </div>

            <div class="tabela-scrollable shadow-sm mt-3">
                <table class="table table-striped table-hover table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 4%;" class="no-print"></th>
                            <th style="width: 6%;">Impresso</th>
                            <th style="width: 6%;">Portaria</th>
                            <th style="width: 7%;">Código</th>
                            <th style="width: 7%;">Cód. Lote</th>
                            <th style="width: auto;">Nome da Criança</th>
                            <th style="width: auto;">Nome do Responsável</th>
                            <th style="width: 12%;">Telefone</th>
                            <th style="width: 6%;">Idade</th>
                            <th style="width: 12%;">Comum</th>
                            <th style="width: 8%;" class="no-print">Ações</th>
                        </tr>
                    </thead>
                    <tbody id="lista-criancas">
                        <?php if (!empty($todosOsCadastros)): ?>
                            <?php foreach (array_reverse($todosOsCadastros, true) as $id => $crianca): ?>
                                <tr data-id="<?php echo sanitize_for_html($crianca['id']); ?>" data-portaria="<?php echo sanitize_for_html($crianca['portaria'] ?? ''); ?>">
                                    <td style="text-align: center;" class="no-print"><input type="checkbox" name="selecionados[]" value="<?php echo sanitize_for_html($crianca['id']); ?>" class="checkbox-crianca"></td>
                                    <td class="status-cell text-center">
                                        <span class="status-icon">
                                            <?php if (isset($crianca['statusImpresso']) && $crianca['statusImpresso'] === 'S'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="green" class="bi bi-check-circle-fill" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>
                                                <span class="print-status">Sim</span>
                                            <?php else: ?>
                                                <span class="print-status">Não</span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td data-campo="portaria" style="text-align: center;"><?php echo sanitize_for_html($crianca['portaria'] ?? ''); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['id']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['cod_resp'] ?? ''); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['nomeCrianca']); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['nomeResponsavel']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['telefone']); ?></td>
                                    <td style="text-align: center;"><?php echo sanitize_for_html($crianca['idade']); ?></td>
                                    <td><?php echo sanitize_for_html($crianca['comum']); ?></td>
                                    <td style="text-align: center;" class="no-print">
                                        <button type="button" class="btn btn-sm btn-danger-linha" onclick="confirmarApagarLinha(<?php echo sanitize_for_html($crianca['id']); ?>, '<?php echo addslashes(sanitize_for_html($crianca['nomeCrianca'])); ?>')"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash-fill" viewBox="0 0 16 16"><path d="M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0"/></svg></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center py-4">Nenhuma criança cadastrada ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formApagarLinha" style="display: none;">
            <input type="hidden" name="acao" value="apagar_linha">
            <input type="hidden" name="id_para_apagar" id="id_para_apagar_input">
        </form>

        <div class="modal fade" id="modalZerarArquivo" tabindex="-1" role="dialog" aria-labelledby="modalZerarArquivoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>" id="formZerarArquivoInterno">
                        <div class="modal-header"><h5 class="modal-title" id="modalZerarArquivoLabel">Confirmar Zerar Arquivo</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>
                        <div class="modal-body">
                            <p><strong>ATENÇÃO:</strong> Esta ação é destrutiva e apagará todos os cadastros. O arquivo atual será salvo como backup (.bkp.1). Backups mais antigos (.bkp.2+) serão removidos. Se .bkp.1 for o único backup após esta operação, ele também será removido.</p>
                            <div class="form-group"><label for="admin_senha_zerar_modal">Senha Administrativa:</label><input type="password" class="form-control" id="admin_senha_zerar_modal" name="admin_senha" required></div>
                        </div>
                        <div class="modal-footer"><input type="hidden" name="zerar_arquivo_confirmado" value="1"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger">Confirmar Zerar</button></div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($exibirModalRecuperacao): ?>
            <div class="modal-backdrop fade show" id="modalRecuperarBackdrop"></div>
            <div class="modal fade show" id="modalRecuperarBackup" tabindex="-1" role="dialog" style="display: block;" aria-labelledby="modalRecuperarBackupLabel" aria-modal="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <form method="post" action="<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>">
                            <div class="modal-header">
                                <h5 class="modal-title" id="modalRecuperarBackupLabel">Recuperar Backup</h5>
                                <button type="button" class="close" onclick="fecharModalRecuperacao()" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            </div>
                            <div class="modal-body">
                                <p>Selecione o arquivo de backup para restaurar. O arquivo atual será salvo como um novo backup (.bkp.1) antes da restauração.</p>
                                <?php if (!empty($backupsDisponiveis)): ?>
                                    <div class="form-group">
                                        <label for="arquivo_backup_selecionado">Arquivo de Backup:</label>
                                        <select class="form-control" id="arquivo_backup_selecionado" name="arquivo_backup_selecionado" required>
                                            <?php foreach ($backupsDisponiveis as $bkpFile): ?>
                                                <option value="<?php echo sanitize_for_html($bkpFile); ?>"><?php echo sanitize_for_html($bkpFile); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div id="backupPreviewContent" style="display: none;"></div>
                                    <div class="form-group mt-3">
                                        <label for="admin_senha_recuperar">Senha Administrativa:</label>
                                        <input type="password" class="form-control" id="admin_senha_recuperar" name="admin_senha" required>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Nenhum arquivo de backup encontrado para restauração.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <input type="hidden" name="confirmar_recuperacao" value="1">
                                <button type="button" class="btn btn-secondary" onclick="fecharModalRecuperacao()">Cancelar</button>
                                <?php if (!empty($backupsDisponiveis)): ?>
                                <button type="submit" class="btn btn-primary">Restaurar Backup</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        const NUM_LINHAS_FORM_CADASTRO = <?php echo NUM_LINHAS_FORMULARIO_CADASTRO; ?>;
        const NUM_CAMPOS_POR_LINHA_CADASTRO = 5; 

        function focarPrimeiroCampoCadastro() {
            $('#input_0_0').focus();
        }

        function limparLinhaCadastro(linha) {
            $('#input_' + linha + '_0').val(''); 
            $('#input_' + linha + '_1').val(''); 
            $('#input_' + linha + '_2').val(''); 
            $('#input_' + linha + '_3').val('').trigger('input'); 
            $('#input_' + linha + '_4').val(''); 
        }

        $(document).ready(function(){
            $('.telefone-mask').mask('(00) 00000-0000');

            const portariaInputCadastro = $('#portaria_cadastro');
            const storedPortaria = localStorage.getItem('ultimaPortariaCadastro');
            if (storedPortaria) {
                portariaInputCadastro.val(storedPortaria);
            }

            portariaInputCadastro.on('input', function() {
                let value = $(this).val().toUpperCase();
                if (value.length > 1) {
                    value = value.substring(0, 1);
                }
                $(this).val(value);
                if (value.match(/^[A-Z]$/)) {
                    localStorage.setItem('ultimaPortariaCadastro', value);
                } else if (value === '') {
                    localStorage.removeItem('ultimaPortariaCadastro');
                }
            });

            <?php if ($focarPrimeiroCampoAposCadastro): ?>
                focarPrimeiroCampoCadastro();
                $('#formNovoCadastro .cadastro-input').val(''); 
            <?php elseif ($focarAposAcao): ?>
                focarPrimeiroCampoCadastro();
            <?php elseif (!empty($mensagemErro)): ?>
            <?php endif; ?>


            $('.cadastro-input').on('keydown', function(e) {
                const key = e.key;
                const target = e.target;
                if (key === 'Enter' || key === 'Tab') {
                    e.preventDefault();
                    const currentLinha = parseInt($(target).data('linha'));
                    const currentCol = parseInt($(target).data('col'));
                    let nextLinha = currentLinha;
                    let nextCol = currentCol;

                    if (currentCol < NUM_CAMPOS_POR_LINHA_CADASTRO - 1) {
                        nextCol++;
                    } else {
                        if (currentLinha < NUM_LINHAS_FORM_CADASTRO - 1) {
                            nextLinha++;
                            nextCol = 0;
                        } else {
                            $('#portaria_cadastro').focus();
                            return;
                        }
                    }
                    $('#input_' + nextLinha + '_' + nextCol).focus();
                }
            });
            
            $('#input_' + (NUM_LINHAS_FORM_CADASTRO - 1) + '_' + (NUM_CAMPOS_POR_LINHA_CADASTRO - 1)).on('keydown', function(e) {
                if (e.key === 'Tab' && !e.shiftKey) {
                    e.preventDefault();
                    $('#portaria_cadastro').focus();
                }
            });

            $('#portaria_cadastro').on('keydown', function(e) {
                if (e.key === 'Enter' || (e.key === 'Tab' && !e.shiftKey)) {
                    e.preventDefault();
                    if ($('#btnLimparCadastro').is(':visible') && !$('#btnLimparCadastro').is(':disabled')) { 
                        $('#btnLimparCadastro').focus();
                    } else {
                        $('#btnCadastrar').focus();
                    }
                }
            });


            $('#selecionarTodos').change(function() {
                $('.checkbox-crianca:visible').prop('checked', $(this).prop('checked'));
            });

            $(document).on('change', '.checkbox-crianca', function() {
                if (!$(this).prop('checked')) {
                    $('#selecionarTodos').prop('checked', false);
                } else {
                    var todosMarcadosVisiveis = true;
                    $('.checkbox-crianca:visible').each(function(){
                        if(!$(this).prop('checked')){
                            todosMarcadosVisiveis = false; return false; 
                        }
                    });
                    $('#selecionarTodos').prop('checked', todosMarcadosVisiveis);
                }
            });

            window.setTimeout(function() { $(".alert-success, .alert-danger").not('.alert-login').fadeTo(500, 0).slideUp(500, function(){ $(this).remove(); }); }, 7000);

            $('#arquivo_backup_selecionado').change(function() {
                var selectedFile = $(this).val();
                var previewDiv = $('#backupPreviewContent');
                if (selectedFile) {
                    previewDiv.html('Carregando preview...').show();
                    var previewUrl = window.location.pathname + '?acao=preview_backup&arquivo=' + encodeURIComponent(selectedFile);
                    fetch(previewUrl)
                        .then(response => {
                            if (!response.ok) {
                                return response.text().then(text => { throw new Error('Erro: ' + response.status + ' ' + text); });
                            }
                            return response.text();
                        })
                        .then(data => {
                            previewDiv.text(data ? data : 'Preview não disponível ou arquivo vazio.');
                        })
                        .catch(error => {
                            console.error('Erro ao buscar preview:', error);
                            previewDiv.text('Erro ao carregar preview: ' + error.message).show();
                        });
                } else {
                    previewDiv.hide().empty();
                }
            });
            <?php if ($exibirModalRecuperacao && !empty($backupsDisponiveis)): ?>
                if ($('#arquivo_backup_selecionado').val()) { 
                    $('#arquivo_backup_selecionado').trigger('change'); 
                }
            <?php endif; ?>

            const filtroPortariaSelect = $('#filtroPortaria');
            const todasAsLinhasDaTabela = $('#lista-criancas tr');
            const localStorageKeyFiltroPortaria = 'filtroPortariaSelecionado';
            let portariasUnicas = new Set();

            todasAsLinhasDaTabela.each(function() {
                const portariaDaLinha = $(this).data('portaria')?.toString().trim().toUpperCase();
                if (portariaDaLinha) {
                    portariasUnicas.add(portariaDaLinha);
                }
            });

            Array.from(portariasUnicas).sort().forEach(p => {
                filtroPortariaSelect.append(new Option(p, p));
            });

            function aplicarFiltroPortaria() {
                const portariasSelecionadas = filtroPortariaSelect.val();
                localStorage.setItem(localStorageKeyFiltroPortaria, JSON.stringify(portariasSelecionadas));

                if (!portariasSelecionadas || portariasSelecionadas.length === 0) {
                    todasAsLinhasDaTabela.show();
                    $('#selecionarTodos').prop('disabled', todasAsLinhasDaTabela.length === 0);
                } else {
                    let algumaLinhaVisivel = false;
                    todasAsLinhasDaTabela.each(function() {
                        const portariaDaLinha = $(this).data('portaria')?.toString().trim().toUpperCase();
                        if (portariasSelecionadas.includes(portariaDaLinha)) {
                            $(this).show();
                            algumaLinhaVisivel = true;
                        } else {
                            $(this).hide();
                            $(this).find('.checkbox-crianca').prop('checked', false); 
                        }
                    });
                    $('#selecionarTodos').prop('disabled', !algumaLinhaVisivel);
                }
                var todosMarcadosVisiveis = true;
                var algumVisivel = false;
                $('.checkbox-crianca:visible').each(function(){
                    algumVisivel = true;
                    if(!$(this).prop('checked')){
                        todosMarcadosVisiveis = false; return false;
                    }
                });
                if (!algumVisivel) todosMarcadosVisiveis = false; 
                $('#selecionarTodos').prop('checked', todosMarcadosVisiveis);
            }

            const filtroSalvo = localStorage.getItem(localStorageKeyFiltroPortaria);
            if (filtroSalvo) {
                try {
                    const portariasSalvas = JSON.parse(filtroSalvo);
                    if (Array.isArray(portariasSalvas)) {
                        filtroPortariaSelect.val(portariasSalvas);
                    }
                } catch (e) {
                    console.error("Erro ao carregar filtro de portaria do localStorage:", e);
                    localStorage.removeItem(localStorageKeyFiltroPortaria); 
                }
            }
            aplicarFiltroPortaria(); 

            filtroPortariaSelect.on('change', aplicarFiltroPortaria);

            $('#limparFiltroPortaria').on('click', function() {
                filtroPortariaSelect.val(null).trigger('change'); 
            });

            $('.btn-copiar-dados').on('click', function() {
                const targetLinha = parseInt($(this).data('target-linha'));
                const responsavelLinha0 = $('#input_0_1').val();
                const telefoneLinha0 = $('#input_0_3').val();
                const comumLinha0 = $('#input_0_4').val();

                $('#input_' + targetLinha + '_1').val(responsavelLinha0);
                $('#input_' + targetLinha + '_3').val(telefoneLinha0).trigger('input'); 
                $('#input_' + targetLinha + '_4').val(comumLinha0);
            });

            $('#btnLimparCadastro').on('click', function() {
                $('#formNovoCadastro .cadastro-input').val('');
                focarPrimeiroCampoCadastro();
            });

            $('#btnImprimirLista').on('click', function() {
                var printWindow = window.open('', '_blank', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Lista de Crianças</title>');
                printWindow.document.write('<style>');
                printWindow.document.write('body { font-family: Arial, sans-serif; font-size: 10pt; margin: 20px;}');
                printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }');
                printWindow.document.write('th, td { border: 1px solid #ccc; padding: 4px; text-align: left; vertical-align: top; }');
                printWindow.document.write('th { background-color: #f0f0f0; font-weight: bold; }');
                printWindow.document.write('h2 { text-align: center; margin-bottom: 15px; }');
                printWindow.document.write('</style></head><body>');
                printWindow.document.write('<h2>Lista de Crianças Cadastradas</h2>');
                printWindow.document.write('<table>');

                var $thead = $('.tabela-scrollable thead').clone();
                $thead.find('.no-print').remove(); 
                printWindow.document.write('<thead>' + $thead.html() + '</thead>');

                printWindow.document.write('<tbody>');
                $('#lista-criancas tr').each(function() {
                    if ($(this).is(':visible')) { 
                        var $row = $(this).clone();
                        $row.find('.no-print').remove(); 

                        var $statusIcon = $row.find('.status-icon');
                        if ($statusIcon.find('svg').length > 0) {
                            $statusIcon.parent().html('Sim'); 
                        } else {
                            $statusIcon.parent().html('Não');
                        }
                        printWindow.document.write('<tr>' + $row.html() + '</tr>');
                    }
                });
                printWindow.document.write('</tbody></table></body></html>');
                printWindow.document.close();
                printWindow.focus();
                setTimeout(function(){ printWindow.print(); }, 500); 
            });


        }); 

        function abrirModalZerarArquivo() {
            $('#modalZerarArquivo').modal('show');
        }

        function fecharModalRecuperacao() {
            $('#modalRecuperarBackup').removeClass('show').hide();
            $('#modalRecuperarBackdrop').removeClass('show').hide();
            $('body').removeClass('modal-open'); 
            $('.modal-backdrop').remove(); 

            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo sanitize_for_html($_SERVER["PHP_SELF"]); ?>';
            var hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'limpar_flag_modal_recuperacao';
            hiddenField.value = '1';
            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
        }

        function confirmarApagarLinha(id, nomeCrianca) {
            if (confirm("Tem certeza que deseja apagar o cadastro de '" + nomeCrianca + "' (ID: " + id + ")?\nEsta ação não pode ser desfeita. Um backup do arquivo atual será criado.")) {
                document.getElementById('id_para_apagar_input').value = id;
                document.getElementById('formApagarLinha').submit();
            }
        }
    </script>
</body>
</html>
