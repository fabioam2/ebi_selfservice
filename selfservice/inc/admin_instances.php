<?php
/**
 * Gerenciamento de Instâncias
 */

foreach ($instancias as &$instancia) {
    $userId = (string)($instancia['user_id'] ?? '');
    $instancia['ULTIMO_CADASTRO'] = lifecycle_ultimo_cadastro($userId);
    $instancia['LINK_ACESSO'] = lifecycle_instance_url($userId);
}
unset($instancia);

usort($instancias, static function (array $instanciaA, array $instanciaB): int {
    return strcmp((string)($instanciaB['DATA_CRIACAO'] ?? ''), (string)($instanciaA['DATA_CRIACAO'] ?? ''));
});
?>

<div class="content-header">
    <h2><i class="fas fa-server mr-2"></i>Gerenciamento de Instâncias</h2>
    <p class="text-muted mb-0">Visualize, gerencie e remova instâncias do sistema</p>
</div>

<!-- Barra de Ações (aparece quando houver seleção) -->
<div id="actionBar" class="action-bar">
    <div>
        <i class="fas fa-check-circle text-success mr-2"></i>
        <strong><span id="selectedCount">0</span> instância(s) selecionada(s)</strong>
    </div>
    <div>
        <button class="btn btn-warning" onclick="removerSelecionados()">
            <i class="fas fa-shield-alt mr-2"></i>Colocar em Quarentena
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
<div class="table-custom instance-table">
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
                <th aria-sort="descending">
                    <button type="button" class="instance-sort-button" data-sort-button="criacao" onclick="ordenarInstancias('criacao', this)" title="Ordenar por data de criação">
                        Data Criação <i class="fas fa-sort-down" aria-hidden="true"></i>
                    </button>
                </th>
                <th aria-sort="none">
                    <button type="button" class="instance-sort-button" data-sort-button="ultimo-cadastro" onclick="ordenarInstancias('ultimo-cadastro', this)" title="Ordenar por último cadastro">
                        Último Cadastro <i class="fas fa-sort" aria-hidden="true"></i>
                    </button>
                </th>
                <th>User ID</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instancias)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma instância criada ainda</p>
                        <a href="selfservice.php" target="_blank" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>Criar Primeira Instância
                        </a>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($instancias as $inst): ?>
                    <?php
                    $dataCriacao = (string)($inst['DATA_CRIACAO'] ?? '');
                    $ultimoCadastroTexto = (string)($inst['ULTIMO_CADASTRO'] ?? '');
                    $dataCriacaoTimestamp = strtotime($dataCriacao);
                    $ultimoCadastro = strtotime($ultimoCadastroTexto);
                    ?>
                    <tr data-sort-criacao="<?php echo htmlspecialchars($dataCriacao, ENT_QUOTES, 'UTF-8'); ?>" data-sort-ultimo-cadastro="<?php echo htmlspecialchars($ultimoCadastroTexto, ENT_QUOTES, 'UTF-8'); ?>">
                        <td>
                            <input type="checkbox" class="instance-checkbox checkbox-lg"
                                   name="instance_ids[]"
                                   value="<?php echo htmlspecialchars($inst['user_id'] ?? ''); ?>">
                        </td>
                        <td><?php echo htmlspecialchars($inst['NOME'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['EMAIL'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['CIDADE'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($inst['COMUM'] ?? 'N/A'); ?></td>
                        <td><?php echo $dataCriacaoTimestamp !== false ? date('d/m/Y H:i', $dataCriacaoTimestamp) : 'N/A'; ?></td>
                        <td><?php echo $ultimoCadastro !== false ? date('d/m/Y H:i', $ultimoCadastro) : 'Nenhum cadastro'; ?></td>
                        <td><small><code><?php echo htmlspecialchars($inst['user_id'] ?? 'N/A'); ?></code></small></td>
                        <td class="text-center">
                            <?php
                            $link = (string)($inst['LINK_ACESSO'] ?? '');
                            $linkJson = htmlspecialchars(json_encode($link, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            $userIdJson = htmlspecialchars(json_encode((string)($inst['user_id'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            $nomeJson = htmlspecialchars(json_encode((string)($inst['NOME'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            $emailJson = htmlspecialchars(json_encode((string)($inst['EMAIL'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            $instanciaJson = htmlspecialchars(json_encode($inst, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="dropdown instance-action-menu">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Ações da instância" aria-label="Ações da instância">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="dropdown-item">
                                        <i class="fas fa-external-link-alt fa-fw mr-2"></i>Acessar sistema
                                    </a>
                                    <button type="button" class="dropdown-item" onclick="copiarLink(<?php echo $linkJson; ?>)">
                                        <i class="fas fa-copy fa-fw mr-2"></i>Copiar link
                                    </button>
                                    <button type="button" class="dropdown-item" onclick="verDetalhes(<?php echo $instanciaJson; ?>)">
                                        <i class="fas fa-info-circle fa-fw mr-2"></i>Ver detalhes
                                    </button>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item" onclick="abrirResetSenha(<?php echo $userIdJson; ?>, <?php echo $nomeJson; ?>)">
                                        <i class="fas fa-key fa-fw mr-2"></i>Redefinir senha
                                    </button>
                                    <button type="button" class="dropdown-item" onclick="resetSenhaEmail(<?php echo $userIdJson; ?>, <?php echo $nomeJson; ?>, <?php echo $emailJson; ?>)">
                                        <i class="fas fa-envelope fa-fw mr-2"></i>Enviar nova senha por e-mail
                                    </button>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item text-danger" onclick="confirmarRemocao(<?php echo $userIdJson; ?>, <?php echo $nomeJson; ?>)">
                                        <i class="fas fa-shield-alt fa-fw mr-2"></i>Colocar em quarentena
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="instance-table-footer">
        <span id="instancePaginationSummary"></span>
        <nav aria-label="Paginação de instâncias">
            <ul class="pagination pagination-sm mb-0" id="instancePagination"></ul>
        </nav>
    </div>
</div>

<!-- Form oculto para quarentena única -->
<form method="post" action="admin.php?page=instances" id="formRemover" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="user_id" id="userIdRemover">
    <input type="hidden" name="remover_instancia" value="1">
</form>

<!-- Form oculto para quarentena em lote -->
<form method="post" action="admin.php?page=instances" id="formRemoverLote" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <div id="checkboxesContainer"></div>
    <input type="hidden" name="remover_instancias_lote" value="1">
</form>

<!-- Form oculto: envio de senha por email -->
<form method="post" action="admin.php?page=instances" id="formResetEmail" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="user_id" id="resetEmailUserId">
    <input type="hidden" name="reset_senha_email" value="1">
</form>

<!-- Modal: redefinir senha manualmente -->
<div class="modal fade" id="modalResetSenha" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="admin.php?page=instances">
        <?php echo admin_csrf_field(); ?>
        <input type="hidden" name="redefinir_senha_instancia" value="1">
        <input type="hidden" name="user_id" id="resetUserId">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-key mr-2"></i>Redefinir senha</h5>
          <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
          <p>Usuário: <strong id="resetNome"></strong></p>
          <div class="form-group">
            <label>Nova senha <small class="text-muted">(mínimo 8 caracteres)</small></label>
            <input type="password" name="nova_senha" class="form-control" minlength="8" required autocomplete="new-password">
          </div>
          <div class="form-group">
            <label>Confirmar senha</label>
            <input type="password" name="confirma_senha" class="form-control" minlength="8" required autocomplete="new-password">
          </div>
          <div class="alert alert-warning mb-0">
            <small>A senha será gravada apenas como hash bcrypt no <code>config.ini</code> da instância.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Salvar nova senha</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('searchInput').addEventListener('input', function() {
        paginaInstanciasAtual = 1;
        atualizarPaginacaoInstancias();
    });
    atualizarPaginacaoInstancias();
});

const INSTANCIAS_POR_PAGINA = 10;
let paginaInstanciasAtual = 1;

function obterLinhasInstancias() {
    return Array.from(document.querySelectorAll('#tabelaInstancias tbody tr[data-sort-criacao]'));
}

function obterLinhasInstanciasFiltradas() {
    const termo = document.getElementById('searchInput').value.trim().toLocaleLowerCase();
    return obterLinhasInstancias().filter(function(linha) {
        return termo === '' || linha.textContent.toLocaleLowerCase().includes(termo);
    });
}

function atualizarPaginacaoInstancias() {
    const linhas = obterLinhasInstancias();
    const linhasFiltradas = obterLinhasInstanciasFiltradas();
    const total = linhasFiltradas.length;
    const totalPaginas = Math.max(1, Math.ceil(total / INSTANCIAS_POR_PAGINA));
    paginaInstanciasAtual = Math.min(paginaInstanciasAtual, totalPaginas);

    linhas.forEach(function(linha) {
        linha.style.display = 'none';
    });

    const inicio = (paginaInstanciasAtual - 1) * INSTANCIAS_POR_PAGINA;
    const fim = Math.min(inicio + INSTANCIAS_POR_PAGINA, total);
    linhasFiltradas.slice(inicio, fim).forEach(function(linha) {
        linha.style.display = '';
    });

    const resumo = document.getElementById('instancePaginationSummary');
    resumo.textContent = total === 0
        ? 'Nenhuma instância encontrada'
        : 'Exibindo ' + (inicio + 1) + '–' + fim + ' de ' + total + ' instância(s)';

    renderizarPaginacaoInstancias(totalPaginas);
}

function renderizarPaginacaoInstancias(totalPaginas) {
    const paginacao = document.getElementById('instancePagination');
    const paginas = new Set([1, totalPaginas, paginaInstanciasAtual - 1, paginaInstanciasAtual, paginaInstanciasAtual + 1]);
    const paginasOrdenadas = Array.from(paginas).filter(function(pagina) {
        return pagina >= 1 && pagina <= totalPaginas;
    }).sort(function(paginaA, paginaB) {
        return paginaA - paginaB;
    });

    let html = '<li class="page-item ' + (paginaInstanciasAtual === 1 ? 'disabled' : '') + '"><button type="button" class="page-link" onclick="irParaPaginaInstancias(' + (paginaInstanciasAtual - 1) + ')" ' + (paginaInstanciasAtual === 1 ? 'disabled' : '') + '>Anterior</button></li>';
    let paginaAnterior = 0;
    paginasOrdenadas.forEach(function(pagina) {
        if (paginaAnterior > 0 && pagina > paginaAnterior + 1) {
            html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        html += '<li class="page-item ' + (pagina === paginaInstanciasAtual ? 'active' : '') + '"><button type="button" class="page-link" onclick="irParaPaginaInstancias(' + pagina + ')" ' + (pagina === paginaInstanciasAtual ? 'aria-current="page"' : '') + '>' + pagina + '</button></li>';
        paginaAnterior = pagina;
    });
    html += '<li class="page-item ' + (paginaInstanciasAtual === totalPaginas ? 'disabled' : '') + '"><button type="button" class="page-link" onclick="irParaPaginaInstancias(' + (paginaInstanciasAtual + 1) + ')" ' + (paginaInstanciasAtual === totalPaginas ? 'disabled' : '') + '>Próxima</button></li>';
    paginacao.innerHTML = html;
}

function irParaPaginaInstancias(pagina) {
    paginaInstanciasAtual = pagina;
    atualizarPaginacaoInstancias();
}

function ordenarInstancias(campo, button) {
    const tabela = document.getElementById('tabelaInstancias');
    const corpo = tabela.tBodies[0];
    const atributo = 'data-sort-' + campo;
    const linhas = Array.from(corpo.querySelectorAll('tr[' + atributo + ']'));
    if (linhas.length === 0) return;

    const mesmaOrdenacao = tabela.dataset.sortCampo === campo;
    const direcao = mesmaOrdenacao && tabela.dataset.sortDirecao === 'asc' ? 'desc' : 'asc';

    linhas.sort(function(linhaA, linhaB) {
        const valorA = linhaA.getAttribute(atributo) || '';
        const valorB = linhaB.getAttribute(atributo) || '';
        if (valorA === valorB) return 0;
        if (valorA === '') return 1;
        if (valorB === '') return -1;

        const comparacao = valorA.localeCompare(valorB);
        return direcao === 'asc' ? comparacao : -comparacao;
    });

    linhas.forEach(function(linha) {
        corpo.appendChild(linha);
    });
    tabela.dataset.sortCampo = campo;
    tabela.dataset.sortDirecao = direcao;
    paginaInstanciasAtual = 1;

    document.querySelectorAll('[data-sort-button]').forEach(function(botao) {
        const cabecalho = botao.closest('th');
        const icone = botao.querySelector('i');
        cabecalho.setAttribute('aria-sort', 'none');
        icone.className = 'fas fa-sort';
    });

    button.closest('th').setAttribute('aria-sort', direcao === 'asc' ? 'ascending' : 'descending');
    button.querySelector('i').className = direcao === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    atualizarPaginacaoInstancias();
}

// Abrir modal de redefinir senha
function abrirResetSenha(userId, nome) {
    $('#resetUserId').val(userId);
    $('#resetNome').text(nome || userId);
    $('#modalResetSenha .form-control').val('');
    $('#modalResetSenha').modal('show');
}

// Enviar nova senha por email
function resetSenhaEmail(userId, nome, email) {
    if (!email) {
        alert('Esta instância não tem email cadastrado.');
        return;
    }
    if (!confirm('Gerar nova senha temporária e enviá-la por email para ' + email + ' (' + nome + ')?\n\nA senha atual será substituída imediatamente.')) {
        return;
    }
    $('#resetEmailUserId').val(userId);
    $('#formResetEmail').submit();
}

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
            'ULTIMO_CADASTRO': 'Último Cadastro',
            'TELEFONE': 'Telefone',
            'ENDERECO': 'Endereço'
        };

        for (let [key, label] of Object.entries(campos)) {
            if (inst[key]) {
                let value = inst[key];

                if (key === 'DATA_CRIACAO' || key === 'ULTIMO_CADASTRO') {
                    value = new Date(value).toLocaleString('pt-BR');
                }

                html += `<tr>
                    <th width="30%">${label}</th>
                    <td>${value}</td>
                </tr>`;
            }
        }

        html += '</table>';

        const link = inst.LINK_ACESSO || '';
        const linkHtml = $('<div>').text(link).html();
        html += `<div class="alert alert-info">
            <strong>Link de Acesso:</strong><br>
            <a href="${linkHtml}" target="_blank">${linkHtml}</a>
            <button type="button" id="copiarLinkInstancia" class="btn btn-sm btn-primary float-right">
                <i class="fas fa-copy mr-1"></i>Copiar
            </button>
        </div>`;

        $('#modalDetalhesBody').html(html);
        $('#copiarLinkInstancia').on('click', function() {
            copiarLink(link);
        });
        $('#modalDetalhes').modal('show');
    } catch (e) {
        alert('Erro ao exibir detalhes: ' + e.message);
    }
}

// Exportar tabela para CSV
function exportarTabela() {
    let csv = 'Nome,Email,Cidade,Comum,Data Criação,Último Cadastro,User ID\n';

    obterLinhasInstanciasFiltradas().forEach(function(linha) {
        const cells = linha.querySelectorAll('td');
        const row = [1, 2, 3, 4, 5, 6, 7].map(function(index) {
            return cells[index].textContent.trim().replace(/"/g, '""');
        });
        csv += row.map(function(value) { return '"' + value + '"'; }).join(',') + '\n';
    });

    // Download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'instancias_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}

// Colocar selecionadas em quarentena (sobrescrever função global)
function removerSelecionados() {
    const checked = $('.instance-checkbox:checked');
    const count = checked.length;

    if (count === 0) {
        alert('Nenhuma instância selecionada');
        return;
    }

    if (confirm(`Colocar ${count} instância(s) em quarentena?\n\nElas poderão ser recuperadas pelo prazo configurado.`)) {
        if (confirm('Confirma a quarentena das instâncias selecionadas?')) {
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
