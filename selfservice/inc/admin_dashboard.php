<?php
/**
 * Dashboard - Página Principal do Admin
 */
?>

<div class="content-header">
    <h2><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</h2>
    <p class="text-muted mb-0">Visão geral do sistema</p>
</div>

<!-- Cards de Estatísticas -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-server icon"></i>
                <h3 class="mb-0"><?php echo $totalInstancias; ?></h3>
                <p class="mb-0">Instâncias Total</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="text-center">
                <i class="fas fa-calendar-day icon"></i>
                <h3 class="mb-0"><?php echo $instanciasHoje; ?></h3>
                <p class="mb-0">Criadas Hoje</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card warning">
            <div class="text-center">
                <i class="fas fa-users icon"></i>
                <h3 class="mb-0"><?php echo $userStats['total']; ?></h3>
                <p class="mb-0">Usuários Cadastrados</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card info">
            <div class="text-center">
                <i class="fas fa-user-check icon"></i>
                <h3 class="mb-0"><?php echo $userStats['active']; ?></h3>
                <p class="mb-0">Usuários Ativos</p>
            </div>
        </div>
    </div>
</div>

<!-- Linha 2 de Estatísticas -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card danger">
            <div class="text-center">
                <i class="fas fa-user-lock icon"></i>
                <h3 class="mb-0"><?php echo $userStats['blocked']; ?></h3>
                <p class="mb-0">Usuários Bloqueados</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-user-shield icon"></i>
                <h3 class="mb-0"><?php echo $userStats['admins']; ?></h3>
                <p class="mb-0">Administradores</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="text-center">
                <i class="fas fa-sign-in-alt icon"></i>
                <h3 class="mb-0"><?php echo $userStats['recent_logins']; ?></h3>
                <p class="mb-0">Logins (24h)</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card warning">
            <div class="text-center">
                <i class="fas fa-clock icon"></i>
                <h3 class="mb-0"><?php echo date('H:i'); ?></h3>
                <p class="mb-0">Horário Atual</p>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas -->
<div class="content-header mt-4">
    <h4><i class="fas fa-bolt mr-2"></i>Ações Rápidas</h4>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-server fa-3x text-primary mb-3"></i>
                <h5>Gerenciar Instâncias</h5>
                <p class="text-muted">Ver, editar e remover instâncias</p>
                <a href="?page=instances" class="btn btn-primary">
                    <i class="fas fa-arrow-right mr-2"></i>Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-users fa-3x text-success mb-3"></i>
                <h5>Gerenciar Usuários</h5>
                <p class="text-muted">Criar, editar e bloquear usuários</p>
                <a href="?page=users" class="btn btn-success">
                    <i class="fas fa-arrow-right mr-2"></i>Acessar
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-cog fa-3x text-warning mb-3"></i>
                <h5>Configurações</h5>
                <p class="text-muted">Alterar senha e configurações</p>
                <a href="?page=settings" class="btn btn-warning">
                    <i class="fas fa-arrow-right mr-2"></i>Acessar
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Instâncias Recentes -->
<div class="content-header mt-4">
    <h4><i class="fas fa-history mr-2"></i>Instâncias Recentes</h4>
</div>

<div class="table-custom">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Cidade</th>
                <th>Data Criação</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Mostrar últimas 5 instâncias
            $instanciasRecentes = array_slice($instancias, -5);
            $instanciasRecentes = array_reverse($instanciasRecentes);

            if (empty($instanciasRecentes)):
            ?>
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma instância criada ainda</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($instanciasRecentes as $inst): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inst['NOME'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['EMAIL'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['CIDADE'] ?? 'N/A'); ?></td>
                        <td><?php echo isset($inst['DATA_CRIACAO']) ? date('d/m/Y H:i', strtotime($inst['DATA_CRIACAO'])) : 'N/A'; ?></td>
                        <td class="text-center">
                            <?php
                            $link = 'instances/' . ($inst['user_id'] ?? '') . '/public_html/ebi/index.php';
                            ?>
                            <a href="<?php echo $link; ?>" target="_blank" class="btn btn-sm btn-info btn-action" title="Acessar">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <button class="btn btn-sm btn-primary btn-action" onclick="copiarLink('<?php echo $link; ?>')" title="Copiar Link">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="text-center mt-4">
    <a href="?page=instances" class="btn btn-primary">
        <i class="fas fa-list mr-2"></i>Ver Todas as Instâncias
    </a>
</div>

<!-- Informações do Sistema -->
<div class="content-header mt-4">
    <h4><i class="fas fa-info-circle mr-2"></i>Informações do Sistema</h4>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Servidor</h5>
                <table class="table table-sm">
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Sistema Operacional:</strong></td>
                        <td><?php echo PHP_OS; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Servidor Web:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Memória Limite PHP:</strong></td>
                        <td><?php echo ini_get('memory_limit'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Sistema Self-Service</h5>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Versão:</strong></td>
                        <td>3.0</td>
                    </tr>
                    <tr>
                        <td><strong>Última Atualização:</strong></td>
                        <td><?php echo date('d/m/Y H:i:s'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Rate Limiting:</strong></td>
                        <td>
                            <?php
                            $rateLimitEnabled = $configAtual['RATE_LIMIT_ENABLED'] ?? 'false';
                            if ($rateLimitEnabled === 'true'):
                            ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Debug Mode:</strong></td>
                        <td>
                            <?php
                            $debugMode = $configAtual['DEBUG_MODE'] ?? 'false';
                            if ($debugMode === 'true'):
                            ?>
                                <span class="badge badge-warning">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-success">Inativo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
