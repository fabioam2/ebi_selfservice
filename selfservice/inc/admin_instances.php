<?php
/**
 * Gerenciamento de Instâncias
 */
?>

<div class="content-header">
    <h2><i class="fas fa-server mr-2"></i>Gerenciamento de Instâncias</h2>
    <p class="text-muted mb-0">Visualize, gerencie e remova instâncias do sistema</p>
</div>

<!-- Estatísticas Rápidas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-server icon"></i>
                <h3 class="mb-0"><?php echo $totalInstancias; ?></h3>
                <p class="mb-0">Total de Instâncias</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stats-card success">
            <div class="text-center">
                <i class="fas fa-calendar-day icon"></i>
                <h3 class="mb-0"><?php echo $instanciasHoje; ?></h3>
                <p class="mb-0">Criadas Hoje</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stats-card warning">
            <div class="text-center">
                <i class="fas fa-chart-line icon"></i>
                <h3 class="mb-0"><?php echo count($instancias) > 0 ? round(($instanciasHoje / count($instancias)) * 100, 1) : 0; ?>%</h3>
                <p class="mb-0">Taxa Hoje</p>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Ações (aparece quando houver seleção) -->
<div id="actionBar" class="action-bar">
    <div>
        <i class="fas fa-check-circle text-success mr-2"></i>
        <strong><span id="selectedCount">0</span> instância(s) selecionada(s)</strong>
    </div>
    <div>
        <button class="btn btn-danger" onclick="removerSelecionados()">
            <i class="fas fa-trash mr-2"></i>Remover Selecionadas
        </button>
        <button class="btn btn-secondary" onclick="$('.instance-checkbox').prop('checked', false); updateActionBar();">
            <i class="fas fa-times mr-2"></i>Cancelar
        </button>
    </div>
</div>

<!-- Busca e Filtros -->
<div class="search-box">
    <div class="row">
        <div class="col-md-8">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nome, email, cidade ou comum...">
            </div>
        </div>
        <div class="col-md-4 text-right">
            <button class="btn btn-success" onclick="window.open('selfservice.php', '_blank')">
                <i class="fas fa-plus mr-2"></i>Nova Instância
            </button>
            <button class="btn btn-info" onclick="exportarTabela()">
                <i class="fas fa-download mr-2"></i>Exportar
            </button>
        </div>
    </div>
</div>

<!-- Tabela de Instâncias -->
<div class="table-custom">
    <table class="table table-hover mb-0" id="tabelaInstancias">
        <thead>
            <tr>
                <th width="50">
                    <input type="checkbox" id="selectAll" class="checkbox-lg">
                </th>
                <th>Nome</th>
                <th>Email</th>
                <th>Cidade</th>
                <th>Comum</th>
                <th>Data Criação</th>
                <th>User ID</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instancias)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma instância criada ainda</p>
                        <a href="selfservice.php" target="_blank" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Criar Primeira Instância
                        </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($instancias as $inst): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="instance-checkbox checkbox-lg"
                                   name="instance_ids[]"
                                   value="<?php echo htmlspecialchars($inst['user_id'] ?? ''); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($inst['NOME'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['EMAIL'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['CIDADE'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['COMUM'] ?? 'N/A'); ?></td>
                        <td><?php echo isset($inst['DATA_CRIACAO']) ? date('d/m/Y H:i', strtotime($inst['DATA_CRIACAO'])) : 'N/A'; ?></td>
                        <td><small><code><?php echo htmlspecialchars($inst['user_id'] ?? 'N/A'); ?></code></small></td>
                        <td class="text-center">
                            <?php
                            // Calculate relative path from admin.php to instance
                            $instancesRelativePath = substr(INSTANCE_BASE_PATH, strlen(SELFSERVICE_ROOT) + 1);
                            $link = '../' . $instancesRelativePath . '/' . ($inst['user_id'] ?? '') . '/public_html/ebi/index.php';
                            ?>
                            <a href="<?php echo $link; ?>" target="_blank" class="btn btn-sm btn-info btn-action" title="Acessar Sistema">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            <button class="btn btn-sm btn-primary btn-action" onclick="copiarLink('<?php echo $link; ?>')" title="Copiar Link">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button class="btn btn-sm btn-warning btn-action" onclick="verDetalhes('<?php echo htmlspecialchars(json_encode($inst), ENT_QUOTES); ?>')" title="Ver Detalhes">
                                <i class="fas fa-info-circle"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-action" onclick="confirmarRemocao('<?php echo htmlspecialchars($inst['user_id'] ?? ''); ?>', '<?php echo htmlspecialchars($inst['NOME'] ?? 'este usuário'); ?>')" title="Remover">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="text-center mt-4 mb-4">
    <p class="text-muted">
        <i class="fas fa-info-circle"></i>
        Total de <?php echo $totalInstancias; ?> instância(s) |
        Última atualização: <?php echo date('d/m/Y H:i:s'); ?>
    </p>
</div>

<!-- Form oculto para remoção única -->
<form method="post" action="admin.php?page=instances" id="formRemover" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="user_id" id="userIdRemover">
    <input type="hidden" name="remover_instancia" value="1">
</form>

<!-- Form oculto para remoção em lote -->
<form method="post" action="admin.php?page=instances" id="formRemoverLote" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <div id="checkboxesContainer"></div>
    <input type="hidden" name="remover_instancias_lote" value="1">
</form>

<!-- Modal de Detalhes -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle mr-2"></i>Detalhes da Instância</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="modalDetalhesBody">
                <!-- Conteúdo dinâmico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
<?php
// Calculate relative path for instances
$instancesRelativePath = substr(INSTANCE_BASE_PATH, strlen(SELFSERVICE_ROOT) + 1);
?>
const INSTANCES_RELATIVE_PATH = '<?php echo $instancesRelativePath; ?>';

$(document).ready(function() {
    // Setup busca
    setupTableSearch('searchInput', 'tabelaInstancias');
});

// Ver detalhes da instância
function verDetalhes(jsonData) {
    try {
        const inst = JSON.parse(jsonData);
        let html = '<table class="table table-bordered">';

        const campos = {
            'user_id': 'User ID',
            'NOME': 'Nome',
            'EMAIL': 'Email',
            'CIDADE': 'Cidade',
            'COMUM': 'Comum',
            'DATA_CRIACAO': 'Data de Criação',
            'TELEFONE': 'Telefone',
            'ENDERECO': 'Endereço'
        };

        for (let [key, label] of Object.entries(campos)) {
            if (inst[key]) {
                let value = inst[key];

                if (key === 'DATA_CRIACAO') {
                    value = new Date(value).toLocaleString('pt-BR');
                }

                html += `<tr>
                    <th width="30%">${label}</th>
                    <td>${value}</td>
                </tr>`;
            }
        }

        html += '</table>';

        // Link da instância
        const link = '../' + INSTANCES_RELATIVE_PATH + '/' + inst.user_id + '/public_html/ebi/index.php';
        const fullLink = window.location.origin + window.location.pathname.replace('admin.php', '') + link;

        html += `<div class="alert alert-info">
            <strong>Link de Acesso:</strong><br>
            <a href="${link}" target="_blank">${fullLink}</a>
            <button class="btn btn-sm btn-primary float-right" onclick="copiarLink('${link}')">
                <i class="fas fa-copy mr-1"></i>Copiar
            </button>
        </div>`;

        $('#modalDetalhesBody').html(html);
        $('#modalDetalhes').modal('show');
    } catch (e) {
        alert('Erro ao exibir detalhes: ' + e.message);
    }
}

// Exportar tabela para CSV
function exportarTabela() {
    let csv = 'Nome,Email,Cidade,Comum,Data Criação,User ID\n';

    $('#tabelaInstancias tbody tr').each(function() {
        if ($(this).is(':visible') && $(this).find('td').length > 1) {
            const cells = $(this).find('td');
            const row = [
                $(cells[1]).text().trim(),
                $(cells[2]).text().trim(),
                $(cells[3]).text().trim(),
                $(cells[4]).text().trim(),
                $(cells[5]).text().trim(),
                $(cells[6]).text().trim()
            ];

            csv += row.map(v => `"${v}"`).join(',') + '\n';
        }
    });

    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'instancias_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}

// Remover selecionados (sobrescrever função global)
function removerSelecionados() {
    const checked = $('.instance-checkbox:checked');
    const count = checked.length;

    if (count === 0) {
        alert('Nenhuma instância selecionada');
        return;
    }

    if (confirm(`Tem certeza que deseja remover ${count} instância(s)?\n\nEsta ação não pode ser desfeita!`)) {
        if (confirm('ATENÇÃO: Todos os dados serão perdidos!\n\nConfirma a remoção?')) {
            // Criar checkboxes no form
            $('#checkboxesContainer').empty();
            checked.each(function() {
                const value = $(this).val();
                $('#checkboxesContainer').append(
                    `<input type="hidden" name="instance_ids[]" value="${value}">`
                );
            });

            $('#formRemoverLote').submit();
        }
    }
}
</script>
