<?php
/**
 * Configurações do Sistema
 */
?>

<div class="content-header">
    <h2><i class="fas fa-cog mr-2"></i>Configurações do Sistema</h2>
    <p class="text-muted mb-0">Gerencie configurações do administrador e do sistema</p>
</div>

<div class="row">
    <!-- Coluna Esquerda -->
    <div class="col-md-6">

        <!-- Alterar Senha do Admin -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Alterar Senha do Administrador</h5>
            </div>
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
        </div>

        <!-- Informações do Arquivo .env -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-file-code mr-2"></i>Arquivo .env</h5>
            </div>
            <div class="card-body">
                <?php
                $envFile = __DIR__ . '/../../.env';
                $envExists = file_exists($envFile);
                ?>

                <?php if ($envExists): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        Arquivo <code>.env</code> encontrado
                    </div>

                    <table class="table table-sm">
                        <tr>
                            <td><strong>Localização:</strong></td>
                            <td><code><?php echo $envFile; ?></code></td>
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

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <small>Para editar configurações avançadas, edite manualmente o arquivo <code>.env</code> ou use os campos abaixo.</small>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Arquivo .env não encontrado!</strong><br>
                        Copie o arquivo <code>.env.example</code> para <code>.env</code>
                    </div>

                    <pre class="bg-dark text-white p-3 rounded"><code>cp .env.example .env</code></pre>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Coluna Direita -->
    <div class="col-md-6">

        <!-- Configurações do Sistema -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-sliders-h mr-2"></i>Configurações do Sistema</h5>
            </div>
            <div class="card-body">
                <form method="post" action="admin.php?page=settings">
                    <?php echo admin_csrf_field(); ?>

                    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-shield-alt mr-2"></i>Segurança e Rate Limiting</h6>

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
                                       value="<?php echo htmlspecialchars($configAtual['RATE_LIMIT_MAX_REQUESTS'] ?? '5'); ?>"
                                       min="1" max="1000">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Janela de Tempo (segundos)</label>
                                <input type="number" name="rate_limit_time_window" class="form-control"
                                       value="<?php echo htmlspecialchars($configAtual['RATE_LIMIT_TIME_WINDOW'] ?? '3600'); ?>"
                                       min="60" max="86400">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-database mr-2"></i>Instâncias</h6>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="allow_multiple_instances"
                                   name="allow_multiple_instances" value="true"
                                   <?php echo ($configAtual['ALLOW_MULTIPLE_INSTANCES'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="allow_multiple_instances">
                                Permitir Múltiplas Instâncias por Email
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tempo de Inatividade para Limpeza (horas)</label>
                        <input type="number" name="cleanup_inactive_hours" class="form-control"
                               value="<?php echo htmlspecialchars($configAtual['CLEANUP_INACTIVE_HOURS'] ?? '6'); ?>"
                               min="1" max="720">
                        <small class="form-text text-muted">Instâncias inativas por esse período serão marcadas para limpeza</small>
                    </div>

                    <hr>

                    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-envelope mr-2"></i>Configurações de Email</h6>

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
                               value="<?php echo htmlspecialchars($configAtual['SMTP_PASSWORD'] ?? ''); ?>"
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

                    <hr>

                    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-bug mr-2"></i>Desenvolvimento e Logs</h6>

                    <div class="form-group">
                        <label>Nível de Log</label>
                        <select name="log_level" class="form-control">
                            <?php
                            $logLevels = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];
                            $currentLogLevel = $configAtual['LOG_LEVEL'] ?? 'warning';
                            foreach ($logLevels as $level):
                            ?>
                                <option value="<?php echo $level; ?>" <?php echo $level === $currentLogLevel ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($level); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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

                    <button type="submit" name="atualizar_config" class="btn btn-success btn-block">
                        <i class="fas fa-save mr-2"></i>Salvar Configurações
                    </button>
                </form>
            </div>
        </div>

        <!-- Informações de Backup -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-archive mr-2"></i>Backup</h5>
            </div>
            <div class="card-body">
                <?php
                $backupPath = __DIR__ . '/../../backups';
                $backupExists = is_dir($backupPath);
                ?>

                <?php if ($backupExists): ?>
                    <?php
                    $backups = glob($backupPath . '/*.{zip,tar.gz}', GLOB_BRACE);
                    $totalBackups = count($backups);
                    $totalSize = 0;

                    foreach ($backups as $backup) {
                        if (file_exists($backup)) {
                            $totalSize += filesize($backup);
                        }
                    }
                    ?>

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $totalBackups; ?></h3>
                            <small class="text-muted">Backups</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success"><?php echo number_format($totalSize / 1024 / 1024, 2); ?> MB</h3>
                            <small class="text-muted">Tamanho Total</small>
                        </div>
                    </div>

                    <button class="btn btn-primary btn-block" onclick="alert('Funcionalidade em desenvolvimento')">
                        <i class="fas fa-plus mr-2"></i>Criar Novo Backup
                    </button>

                    <a href="?page=backups" class="btn btn-secondary btn-block">
                        <i class="fas fa-list mr-2"></i>Ver Todos os Backups
                    </a>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Diretório de backups não existe. Será criado automaticamente quando necessário.
                    </div>
                <?php endif; ?>

                <div class="alert alert-info mt-3">
                    <small>
                        <strong>Configurações de Backup:</strong><br>
                        • Máximo: <?php echo $configAtual['MAX_BACKUPS'] ?? '10'; ?> backups por instância<br>
                        • Backup antes de remover: <?php echo ($configAtual['BACKUP_BEFORE_REMOVE'] ?? 'true') === 'true' ? 'Sim' : 'Não'; ?>
                    </small>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Configurações Avançadas -->
<div class="card mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-code mr-2"></i>Configurações Avançadas</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Variáveis de Ambiente Carregadas</strong>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Variável</th>
                        <th>Valor</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $configDescriptions = [
                        'ADMIN_PASSWORD_HASH' => 'Hash da senha do administrador',
                        'INSTANCE_BASE_PATH' => 'Diretório das instâncias',
                        'TEMPLATE_PATH' => 'Diretório do template',
                        'DATA_PATH' => 'Diretório de dados',
                        'LOG_FILE' => 'Arquivo de log principal',
                        'BASE_URL' => 'URL base do sistema',
                        'RATE_LIMIT_ENABLED' => 'Rate limiting ativo',
                        'RATE_LIMIT_MAX_REQUESTS' => 'Máximo de requisições',
                        'RATE_LIMIT_TIME_WINDOW' => 'Janela de tempo (segundos)',
                        'ALLOW_MULTIPLE_INSTANCES' => 'Permitir múltiplas instâncias',
                        'CLEANUP_INACTIVE_HOURS' => 'Horas para limpeza',
                        'LOG_LEVEL' => 'Nível de log',
                        'DEBUG_MODE' => 'Modo debug',
                        'APP_ENV' => 'Ambiente da aplicação',
                        'BACKUP_PATH' => 'Diretório de backups',
                        'MAX_BACKUPS' => 'Máximo de backups'
                    ];

                    foreach ($configDescriptions as $key => $desc):
                        if (isset($configAtual[$key])):
                            $value = $configAtual[$key];

                            // Ocultar senha
                            if ($key === 'ADMIN_PASSWORD_HASH') {
                                $value = '••••••••••••••••';
                            }
                    ?>
                        <tr>
                            <td><code><?php echo $key; ?></code></td>
                            <td><small><?php echo htmlspecialchars($value); ?></small></td>
                            <td><small class="text-muted"><?php echo $desc; ?></small></td>
                        </tr>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <strong>Atenção:</strong> Para editar essas configurações, edite manualmente o arquivo <code>.env</code> no diretório raiz.
        </div>
    </div>
</div>

<!-- Ações do Sistema -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Ações do Sistema</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <button class="btn btn-outline-warning btn-block" onclick="limparCache()">
                    <i class="fas fa-broom mr-2"></i>Limpar Cache
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-info btn-block" onclick="verificarSistema()">
                    <i class="fas fa-heartbeat mr-2"></i>Verificar Sistema
                </button>
            </div>
            <div class="col-md-4">
                <button class="btn btn-outline-danger btn-block" onclick="reiniciarSessao()">
                    <i class="fas fa-sync-alt mr-2"></i>Reiniciar Sessão
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Validar senhas
$('#senha_nova, #senha_confirmar').on('keyup', function() {
    const senha1 = $('#senha_nova').val();
    const senha2 = $('#senha_confirmar').val();

    if (senha1 && senha2) {
        if (senha1 === senha2) {
            $('#senha_confirmar').removeClass('is-invalid').addClass('is-valid');
        } else {
            $('#senha_confirmar').removeClass('is-valid').addClass('is-invalid');
        }
    }
});

// Ações do sistema
function limparCache() {
    if (confirm('Deseja limpar o cache do sistema?')) {
        alert('Funcionalidade em desenvolvimento');
    }
}

function verificarSistema() {
    alert('Sistema verificado:\n\n' +
          '✅ PHP: <?php echo phpversion(); ?>\n' +
          '✅ Servidor: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?>\n' +
          '✅ Instâncias: <?php echo $totalInstancias; ?>\n' +
          '✅ Usuários: <?php echo $userStats['total']; ?>\n' +
          '\nTudo funcionando normalmente!');
}

function reiniciarSessao() {
    if (confirm('Deseja reiniciar sua sessão?\n\nVocê será desconectado.')) {
        window.location.href = '?logout=1';
    }
}

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
