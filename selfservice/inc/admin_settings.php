<?php
/**
 * Configurações do Sistema
 */
?>

<div class="content-header">
    <h2><i class="fas fa-cog mr-2"></i>Configurações do Sistema</h2>
    <p class="text-muted mb-0">Gerencie configurações do administrador e do sistema</p>
</div>

<style>
    .settings-topic > summary {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        list-style: none;
    }
    .settings-topic > summary::-webkit-details-marker { display: none; }
    .settings-topic-icon { transition: transform .15s ease; }
    .settings-topic[open] > summary .settings-topic-icon { transform: rotate(180deg); }
    .settings-subtopic { border-bottom: 1px solid #dee2e6; margin-bottom: 1rem; padding-bottom: 1rem; }
    .settings-subtopic:last-of-type { border-bottom: 0; margin-bottom: 0; padding-bottom: 0; }
    .settings-subtopic > summary { cursor: pointer; font-weight: 600; list-style: none; }
    .settings-subtopic > summary::-webkit-details-marker { display: none; }
</style>

<div class="row">
    <!-- Coluna Esquerda -->
    <div class="col-md-6">

        <!-- Alterar Senha do Admin -->
        <details class="card mb-4 settings-topic">
            <summary class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Alterar Senha do Administrador</h5>
                <i class="fas fa-chevron-down settings-topic-icon" aria-hidden="true"></i>
            </summary>
            <div class="card-body">
                <form method="post" action="admin.php?page=settings">
                    <?php echo admin_csrf_field(); ?>

                    <div class="form-group">
                        <label><i class="fas fa-lock mr-2"></i>Senha Atual *</label>
                        <input type="password" name="senha_atual" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-unlock-alt mr-2"></i>Nova Senha *</label>
                        <input type="password" name="senha_nova" id="senha_nova" class="form-control" required minlength="8" autocomplete="new-password">
                        <small class="form-text text-muted">Mínimo 8 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-check-circle mr-2"></i>Confirmar Nova Senha *</label>
                        <input type="password" name="senha_confirmar" id="senha_confirmar" class="form-control" required minlength="8" autocomplete="new-password">
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Atenção:</strong> Após alterar a senha, você será desconectado e precisará fazer login novamente.
                    </div>

                    <button type="submit" name="alterar_senha" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-2"></i>Alterar Senha
                    </button>
                </form>
            </div>
        </details>

        <!-- Configurar Envio de Email (diagnóstico + setup) -->
        <details class="card mb-4 settings-topic">
            <summary class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-envelope-open-text mr-2"></i>Diagnóstico de Envio de Email</h5>
                <i class="fas fa-chevron-down settings-topic-icon" aria-hidden="true"></i>
            </summary>
            <div class="card-body">
                <?php
                $projectRoot = realpath(__DIR__ . '/../..');
                $vendorAutoload = $projectRoot . '/vendor/autoload.php';
                $composerJson = $projectRoot . '/composer.json';
                $phpMailerInstalado = file_exists($projectRoot . '/vendor/phpmailer/phpmailer/src/PHPMailer.php');

                $envFile = $projectRoot . '/.env';
                $envOk = file_exists($envFile);

                $cfg = function_exists('carregarConfigEmail') ? carregarConfigEmail() : [];
                $emailHabilitado = !empty($cfg['habilitado']);
                $smtpHost = $cfg['smtp_host'] ?? '';
                $smtpPort = $cfg['smtp_port'] ?? 0;
                $smtpUser = $cfg['smtp_user'] ?? '';

                // Diagnóstico
                $checks = [
                    ['label' => 'PHP &ge; 7.4',                   'ok' => version_compare(PHP_VERSION, '7.4', '>=')],
                    ['label' => 'Extensão <code>openssl</code>',  'ok' => extension_loaded('openssl')],
                    ['label' => 'Extensão <code>mbstring</code>', 'ok' => extension_loaded('mbstring')],
                    ['label' => 'Extensão <code>curl</code>',     'ok' => extension_loaded('curl')],
                    ['label' => 'composer.json presente',         'ok' => file_exists($composerJson)],
                    ['label' => 'vendor/autoload.php presente',   'ok' => file_exists($vendorAutoload)],
                    ['label' => 'PHPMailer instalado',            'ok' => $phpMailerInstalado],
                    ['label' => '.env presente',                  'ok' => $envOk],
                    ['label' => 'EMAIL_ENABLED=true no .env',     'ok' => $emailHabilitado],
                    ['label' => 'SMTP_HOST configurado',          'ok' => !empty($smtpHost)],
                    ['label' => 'SMTP_USER configurado',          'ok' => !empty($smtpUser)],
                    ['label' => 'proc_open disponível (para instalar pela UI)', 'ok' => function_exists('proc_open')],
                    ['label' => 'shell_exec disponível (versão exata pelo git; há alternativa se faltar)', 'ok' => function_exists('shell_exec')],
                ];
                ?>

                <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-stethoscope mr-2"></i>Diagnóstico</h6>
                <table class="table table-sm mb-3">
                    <?php foreach ($checks as $c): ?>
                        <tr>
                            <td style="width:70%"><?php echo $c['label']; ?></td>
                            <td class="text-right">
                                <?php if ($c['ok']): ?>
                                    <span class="badge badge-success"><i class="fas fa-check"></i> OK</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-times"></i> pendente</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <?php if (!$phpMailerInstalado): ?>
                    <div class="alert alert-warning">
                        <strong>PHPMailer não está instalado.</strong> É necessário para enviar emails (cadastro, reset de senha).
                    </div>

                    <h6 class="mb-2"><i class="fas fa-rocket mr-2"></i>Instalar pela página</h6>
                    <form method="post" action="admin.php?page=settings" class="mb-3">
                        <?php echo admin_csrf_field(); ?>
                        <button type="submit" name="instalar_dependencias" value="1" class="btn btn-primary"
                                onclick="return confirm('Executar `composer install` no servidor? Pode levar alguns minutos.');"
                                <?php echo function_exists('proc_open') ? '' : 'disabled'; ?>>
                            <i class="fas fa-download mr-2"></i>Instalar dependências (composer install)
                        </button>
                        <small class="form-text text-muted">
                            Requer que <code>composer</code> (ou <code>composer.phar</code> na raiz) esteja disponível no servidor
                            e que a função <code>proc_open</code> não esteja bloqueada.
                        </small>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle mr-2"></i> <strong>PHPMailer instalado.</strong>
                        Envio de email operacional (basta habilitar em <em>Configurações do Sistema</em> e preencher SMTP).
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['composer_output'])): ?>
                    <h6 class="mb-2"><i class="fas fa-terminal mr-2"></i>Saída do último <code>composer install</code></h6>
                    <pre class="bg-dark text-white p-3 rounded" style="max-height:280px;overflow:auto;font-size:.78rem"><code><?php
                        echo htmlspecialchars($_SESSION['composer_output']);
                        unset($_SESSION['composer_output']);
                    ?></code></pre>
                <?php endif; ?>

                <h6 class="mb-2 mt-3"><i class="fas fa-list-ol mr-2"></i>Passos para habilitar envio de email</h6>
                <ol class="mb-3" style="font-size:.92rem">
                    <li>
                        Garantir que o <code>.env</code> existe (criar no card abaixo se faltar).
                    </li>
                    <li>
                        Instalar dependências (botão acima <em>ou</em> via SSH no diretório do projeto):
                        <pre class="bg-light p-2 mb-2"><code>cd <?php echo htmlspecialchars($projectRoot); ?>
composer install --no-dev --optimize-autoloader</code></pre>
                        Se o servidor não tiver <code>composer</code> no PATH:
                        <pre class="bg-light p-2 mb-0"><code>curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader</code></pre>
                    </li>
                    <li>
                        Editar <code>.env</code> e preencher as variáveis SMTP:
                        <pre class="bg-light p-2 mb-0"><code>EMAIL_ENABLED=true
SMTP_HOST=smtp.seuprovedor.com
SMTP_PORT=465
SMTP_SECURE=ssl          # use "tls" para porta 587
SMTP_USER=no-reply@seudominio.com
SMTP_PASSWORD=*****
EMAIL_FROM=no-reply@seudominio.com
EMAIL_FROM_NAME="EBI Self-Service"</code></pre>
                    </li>
                    <li>
                        Salvar as mesmas configurações no card <em>Configurações do Sistema</em> (elas editam o <code>.env</code>).
                    </li>
                    <li>
                        Clicar em <strong>Testar Conexão SMTP</strong> (botão no card ao lado).
                    </li>
                    <li>
                        <strong>Hostinger / cPanel:</strong> se a porta 465/587 estiver bloqueada pelo firewall,
                        abrir chamado pedindo liberação do SMTP externo <em>ou</em> usar o SMTP interno do provedor
                        (<code>localhost:25</code> ou endereço fornecido no painel).
                    </li>
                    <li>
                        <strong>Apache/openbase_dir:</strong> se o <code>vendor/</code> ficar fora do <code>open_basedir</code>,
                        adicionar o caminho do projeto no painel do provedor.
                    </li>
                </ol>

                <div class="alert alert-info mb-0">
                    <small>
                        <i class="fas fa-info-circle mr-1"></i>
                        Logs de envio ficam em <code>selfservice/data/admin_actions.log</code>
                        (ações de reset) e erros de PHPMailer aparecem no log do servidor.
                    </small>
                </div>
            </div>
        </details>

        <!-- Informações do Arquivo .env -->
        <details class="card mb-4 settings-topic">
            <summary class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-file-code mr-2"></i>Arquivo .env</h5>
                <i class="fas fa-chevron-down settings-topic-icon" aria-hidden="true"></i>
            </summary>
            <div class="card-body">
                <?php
                $envFile = __DIR__ . '/../../.env';
                $envExample = __DIR__ . '/../../.env.example';
                $envExists = file_exists($envFile);
                $mostrarEnv = isset($_GET['show_env']) && $_GET['show_env'] === '1';

                /**
                 * Mascara valores sensíveis (senhas, hashes, tokens) para exibição.
                 */
                $mascararEnv = function (string $conteudo): string {
                    $padroes = '/(PASSWORD|PASSWD|SECRET|TOKEN|HASH|KEY)/i';
                    $linhas = explode("\n", $conteudo);
                    foreach ($linhas as $i => $linha) {
                        $trim = ltrim($linha);
                        if ($trim === '' || $trim[0] === '#') continue;
                        if (!preg_match('/^\s*([A-Z_][A-Z0-9_]*)\s*=\s*(.*)$/', $linha, $m)) continue;
                        $chave = $m[1];
                        $valor = $m[2];
                        if (preg_match($padroes, $chave) && trim($valor, "'\" ") !== '') {
                            $linhas[$i] = $chave . '="••••••••"';
                        }
                    }
                    return implode("\n", $linhas);
                };
                ?>

                <?php if ($envExists): ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle mr-2"></i>
                        Arquivo <code>.env</code> encontrado
                    </div>

                    <table class="table table-sm mb-3">
                        <tr>
                            <td><strong>Localização:</strong></td>
                            <td><code><?php echo htmlspecialchars($envFile); ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Tamanho:</strong></td>
                            <td><?php echo number_format(filesize($envFile) / 1024, 2); ?> KB</td>
                        </tr>
                        <tr>
                            <td><strong>Última Modificação:</strong></td>
                            <td><?php echo date('d/m/Y H:i:s', filemtime($envFile)); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Permissões:</strong></td>
                            <td><code><?php echo substr(sprintf('%o', fileperms($envFile)), -4); ?></code></td>
                        </tr>
                    </table>

                    <div class="d-flex mb-3" style="gap:8px">
                        <?php if (!$mostrarEnv): ?>
                            <a href="admin.php?page=settings&amp;show_env=1" class="btn btn-info btn-sm">
                                <i class="fas fa-eye mr-1"></i>Ver conteúdo (.env)
                            </a>
                        <?php else: ?>
                            <a href="admin.php?page=settings" class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye-slash mr-1"></i>Ocultar
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ($mostrarEnv): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-shield-alt mr-2"></i>
                            <small>Valores de <code>PASSWORD</code>, <code>SECRET</code>, <code>TOKEN</code>, <code>HASH</code> e <code>KEY</code> ficam ocultos.</small>
                        </div>
                        <pre class="bg-dark text-white p-3 rounded" style="max-height:420px;overflow:auto;font-size:.82rem"><code><?php
                            echo htmlspecialchars($mascararEnv(file_get_contents($envFile)));
                        ?></code></pre>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle mr-2"></i>
                            <small>Para editar configurações avançadas, use os campos ao lado ou edite o arquivo <code>.env</code> manualmente.</small>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Arquivo .env não encontrado!</strong>
                    </div>

                    <?php if (file_exists($envExample)): ?>
                        <p class="text-muted">Podemos criar automaticamente a partir de <code>.env.example</code>.</p>
                        <form method="post" action="admin.php?page=settings">
                            <?php echo admin_csrf_field(); ?>
                            <button type="submit" name="criar_env_do_example" value="1" class="btn btn-success btn-block">
                                <i class="fas fa-copy mr-2"></i>Criar .env a partir do .env.example
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            Arquivo <code>.env.example</code> também não encontrado.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </details>

    </div>

    <!-- Coluna Direita -->
    <div class="col-md-6">

        <!-- Rate Limiting: status + desbloqueio rápido -->
        <?php
        require_once __DIR__ . '/rate_limit.php';
        $rlDir = dirname(__DIR__) . '/data/';
        $rlArquivos = glob($rlDir . 'ratelimit_*.json') ?: [];
        $rlEnabled = ($configAtual['RATE_LIMIT_ENABLED'] ?? 'false') === 'true';
        $rlMax = (int)($configAtual['RATE_LIMIT_MAX_REQUESTS'] ?? 60);
        $rlWin = (int)($configAtual['RATE_LIMIT_TIME_WINDOW'] ?? 60);
        $rlMeuIp = getClientIP();

        // Coletar lista de IPs com contagem atual
        $rlLista = [];
        $agora = time();
        foreach ($rlArquivos as $f) {
            $ipArq = preg_replace('/^ratelimit_|\.json$/', '', basename($f));
            $dados = @json_decode((string)@file_get_contents($f), true);
            if (!is_array($dados)) $dados = [];
            $validos = array_filter($dados, fn($t) => ($agora - (int)$t) < $rlWin);
            $rlLista[] = [
                'ip' => $ipArq,
                'reqs' => count($validos),
                'bloqueado' => count($validos) >= $rlMax,
            ];
        }
        usort($rlLista, fn($a, $b) => $b['reqs'] <=> $a['reqs']);
        ?>
        <details class="card mb-4 settings-topic">
            <summary class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-traffic-light mr-2"></i>Rate Limiting — status e desbloqueio</h5>
                <i class="fas fa-chevron-down settings-topic-icon" aria-hidden="true"></i>
            </summary>
            <div class="card-body">
                <p class="mb-2">
                    Estado atual:
                    <?php if ($rlEnabled): ?>
                        <span class="badge badge-success">ATIVO</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">DESATIVADO</span>
                    <?php endif; ?>
                    — máx. <strong><?php echo $rlMax; ?></strong> requisições por <strong><?php echo $rlWin; ?>s</strong>
                    (ajuste no tópico Proteção contra abuso).
                </p>
                <p class="small text-muted mb-3">
                    Seu IP atual: <code><?php echo htmlspecialchars($rlMeuIp); ?></code>
                </p>

                <?php if (empty($rlLista)): ?>
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle mr-1"></i>Nenhum IP registrado no momento.
                    </div>
                <?php else: ?>
                    <div class="table-responsive mb-3" style="max-height:220px;overflow:auto">
                        <table class="table table-sm mb-0">
                            <thead class="thead-light">
                                <tr><th>IP</th><th>Reqs na janela</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rlLista as $r): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($r['ip']); ?></code></td>
                                        <td><?php echo $r['reqs']; ?> / <?php echo $rlMax; ?></td>
                                        <td>
                                            <?php if ($r['bloqueado']): ?>
                                                <span class="badge badge-danger">BLOQUEADO</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">monitorado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <form method="post" action="admin.php?page=settings" class="d-inline">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="desbloquear_modo" value="meu_ip">
                    <button type="submit" name="desbloquear_rate_limit" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-user-shield mr-1"></i>Desbloquear meu IP
                    </button>
                </form>
                <form method="post" action="admin.php?page=settings" class="d-inline"
                      onsubmit="return confirm('Remover TODOS os registros de rate limit? Isso libera qualquer IP bloqueado.');">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="desbloquear_modo" value="todos">
                    <button type="submit" name="desbloquear_rate_limit" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-broom mr-1"></i>Desbloquear todos
                    </button>
                </form>

                <hr>
                <small class="text-muted d-block">
                    <i class="fas fa-info-circle mr-1"></i>
                    Os contadores ficam em <code>selfservice/data/ratelimit_&lt;ip&gt;.json</code>.
                    Apagar o arquivo do IP libera imediatamente o acesso. Para ajustar os limites use o formulário abaixo.
                </small>
            </div>
        </details>

        <!-- Configurações do Sistema -->
        <details class="card mb-4 settings-topic" open>
            <summary class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Configurações Operacionais</h5>
                <i class="fas fa-chevron-down settings-topic-icon" aria-hidden="true"></i>
            </summary>
            <div class="card-body">
                <form method="post" action="admin.php?page=settings">
                    <?php echo admin_csrf_field(); ?>

                    <details class="settings-subtopic" open>
                        <summary><i class="fas fa-shield-alt mr-2"></i>Proteção contra abuso</summary>
                        <div class="pt-3">

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="rate_limit_enabled"
                                   name="rate_limit_enabled" value="true"
                                   <?php echo ($configAtual['RATE_LIMIT_ENABLED'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="rate_limit_enabled">
                                Ativar Rate Limiting
                            </label>
                        </div>
                        <small class="form-text text-muted">Limita o número de requisições por IP</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Máximo de Requisições</label>
                                <input type="number" name="rate_limit_max_requests" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['RATE_LIMIT_MAX_REQUESTS'] ?? '60'); ?>"
                                       min="1" max="1000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Janela de Tempo (segundos)</label>
                                <input type="number" name="rate_limit_time_window" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['RATE_LIMIT_TIME_WINDOW'] ?? '60'); ?>"
                                       min="10" max="86400">
                            </div>
                        </div>
                    </div>

                        </div>
                    </details>

                    <details class="settings-subtopic">
                        <summary><i class="fas fa-database mr-2"></i>Ciclo de vida das instâncias</summary>
                        <div class="pt-3">

                    <div class="form-group">
                        <label>Retenção de dados sensíveis (horas)</label>
                        <input type="number" name="sensitive_data_retention_hours" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['SENSITIVE_DATA_RETENTION_HOURS'] ?? '24'); ?>"
                               min="1" max="8760">
                        <small class="form-text text-muted">Após esse prazo sem novos cadastros, a tarefa horária remove dados identificáveis e preserva as estatísticas.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Aviso de inatividade (dias)</label>
                                <input type="number" name="inactivity_warning_days" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['INACTIVITY_WARNING_DAYS'] ?? '30'); ?>"
                                       min="1" max="3650">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Prazo após aviso (dias)</label>
                                <input type="number" name="inactivity_grace_days" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['INACTIVITY_GRACE_DAYS'] ?? '30'); ?>"
                                       min="1" max="3650">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Lembrete de inatividade (dias)</label>
                                <input type="number" name="inactivity_reminder_days" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['INACTIVITY_REMINDER_DAYS'] ?? '7'); ?>"
                                       min="1" max="365">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Quarentena antes do expurgo (dias)</label>
                                <input type="number" name="quarantine_retention_days" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['QUARANTINE_RETENTION_DAYS'] ?? '7'); ?>"
                                       min="1" max="365">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Validade dos links de ação (horas)</label>
                        <input type="number" name="instance_action_token_hours" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['INSTANCE_ACTION_TOKEN_HOURS'] ?? '168'); ?>"
                               min="1" max="2160">
                        <small class="form-text text-muted">Links de exclusão enviados por e-mail precisam ser confirmados dentro desse prazo.</small>
                    </div>

                        </div>
                    </details>

                    <details class="settings-subtopic">
                        <summary><i class="fas fa-envelope mr-2"></i>Envio de email</summary>
                        <div class="pt-3">

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="email_enabled"
                                   name="email_enabled" value="true"
                                   <?php echo ($configAtual['EMAIL_ENABLED'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="email_enabled">
                                Habilitar Envio de Email
                            </label>
                        </div>
                        <small class="form-text text-muted">Envia email com dados de acesso ao criar conta</small>
                    </div>

                    <div class="form-group">
                        <label>Servidor SMTP</label>
                        <input type="text" name="smtp_host" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['SMTP_HOST'] ?? 'smtp.hostinger.com'); ?>"
                               placeholder="smtp.exemplo.com">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Porta SMTP</label>
                                <input type="number" name="smtp_port" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['SMTP_PORT'] ?? '465'); ?>"
                                       min="1" max="65535">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Segurança</label>
                                <select name="smtp_secure" class="form-control">
                                    <?php $currentSecure = $configAtual['SMTP_SECURE'] ?? 'ssl'; ?>
                                    <option value="ssl" <?php echo $currentSecure === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    <option value="tls" <?php echo $currentSecure === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Usuário SMTP</label>
                        <input type="text" name="smtp_user" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['SMTP_USER'] ?? 'no-replay@ebi.ccbcampinas.org.br'); ?>"
                               placeholder="usuario@exemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Senha SMTP</label>
                        <input type="password" name="smtp_password" class="form-control"
                               placeholder="Deixe em branco para manter a atual">
                        <small class="form-text text-muted">Deixe vazio para não alterar</small>
                    </div>

                    <div class="form-group">
                        <label>Email Remetente</label>
                        <input type="email" name="email_from" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['EMAIL_FROM'] ?? 'no-replay@ebi.ccbcampinas.org.br'); ?>"
                               placeholder="noreply@exemplo.com">
                    </div>

                    <div class="form-group">
                        <label>Nome do Remetente</label>
                        <input type="text" name="email_from_name" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['EMAIL_FROM_NAME'] ?? 'EBI Self-Service'); ?>"
                               placeholder="Nome do Sistema">
                    </div>

                    <button type="button" class="btn btn-outline-info btn-sm mb-3" onclick="testarEmail()">
                        <i class="fas fa-paper-plane mr-2"></i>Testar Conexão SMTP
                    </button>

                        </div>
                    </details>

                    <details class="settings-subtopic">
                        <summary><i class="fas fa-bug mr-2"></i>Diagnóstico técnico</summary>
                        <div class="pt-3">

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="debug_mode"
                                   name="debug_mode" value="true"
                                   <?php echo ($configAtual['DEBUG_MODE'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="debug_mode">
                                Modo Debug
                            </label>
                        </div>
                        <small class="form-text text-muted text-warning">
                            <i class="fas fa-exclamation-triangle"></i> Desative em produção!
                        </small>
                    </div>

                        </div>
                    </details>

                    <button type="submit" name="atualizar_config" class="btn btn-success btn-block">
                        <i class="fas fa-save mr-2"></i>Salvar Configurações
                    </button>
                </form>
            </div>
        </details>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const senhaNova = document.getElementById('senha_nova');
    const senhaConfirmar = document.getElementById('senha_confirmar');
    if (!senhaNova || !senhaConfirmar) return;

    const validarSenhas = function() {
        if (!senhaNova.value || !senhaConfirmar.value) {
            senhaConfirmar.classList.remove('is-valid', 'is-invalid');
            return;
        }
        senhaConfirmar.classList.toggle('is-valid', senhaNova.value === senhaConfirmar.value);
        senhaConfirmar.classList.toggle('is-invalid', senhaNova.value !== senhaConfirmar.value);
    };

    senhaNova.addEventListener('input', validarSenhas);
    senhaConfirmar.addEventListener('input', validarSenhas);
});

function testarEmail() {
    if (!confirm('Deseja testar a conexão SMTP?\n\nIsso verificará se as credenciais estão corretas.')) {
        return;
    }

    // Fazer requisição AJAX para testar email
    fetch('inc/test_email.php')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('✅ ' + data.mensagem);
            } else {
                alert('❌ Erro: ' + data.mensagem);
            }
        })
        .catch(error => {
            alert('❌ Erro ao testar conexão: ' + error);
        });
}
</script>
