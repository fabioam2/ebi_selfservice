<?php
/**
 * Gerenciamento de Usuários
 */
?>

<div class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="fas fa-users mr-2"></i>Gerenciamento de Usuários</h2>
            <p class="text-muted mb-0">Crie, edite, bloqueie e remova usuários do sistema</p>
        </div>
        <div>
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCriarUsuario">
                <i class="fas fa-user-plus mr-2"></i>Novo Usuário
            </button>
        </div>
    </div>
</div>

<!-- Estatísticas de Usuários -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card primary">
            <div class="text-center">
                <i class="fas fa-users icon"></i>
                <h3 class="mb-0"><?php echo $userStats['total']; ?></h3>
                <p class="mb-0">Total de Usuários</p>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="text-center">
                <i class="fas fa-user-check icon"></i>
                <h3 class="mb-0"><?php echo $userStats['active']; ?></h3>
                <p class="mb-0">Usuários Ativos</p>
            </div>
        </div>
    </div>

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
        <div class="card stats-card warning">
            <div class="text-center">
                <i class="fas fa-user-shield icon"></i>
                <h3 class="mb-0"><?php echo $userStats['admins']; ?></h3>
                <p class="mb-0">Administradores</p>
            </div>
        </div>
    </div>
</div>

<!-- Busca e Filtros -->
<div class="search-box">
    <div class="row">
        <div class="col-md-6">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" id="searchUsuarios" class="form-control" placeholder="Buscar por username, email ou nome...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-control" id="filterStatus" onchange="filtrarUsuarios()">
                <option value="">Todos os Status</option>
                <option value="active">Ativos</option>
                <option value="blocked">Bloqueados</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-control" id="filterRole" onchange="filtrarUsuarios()">
                <option value="">Todas as Roles</option>
                <option value="admin">Administradores</option>
                <option value="user">Usuários</option>
            </select>
        </div>
    </div>
</div>

<!-- Tabela de Usuários -->
<div class="table-custom">
    <table class="table table-hover mb-0" id="tabelaUsuarios">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Nome Completo</th>
                <th>Role</th>
                <th>Status</th>
                <th>Criado Em</th>
                <th>Último Login</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhum usuário encontrado</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($usuarios as $user): ?>
                    <tr data-status="<?php echo htmlspecialchars($user['status']); ?>"
                        data-role="<?php echo htmlspecialchars($user['role']); ?>">
                        <td>
                            <i class="fas fa-user mr-2 text-primary"></i>
                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge badge-primary">
                                    <i class="fas fa-user-shield mr-1"></i>Admin
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-user mr-1"></i>User
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'active'): ?>
                                <span class="badge badge-status badge-active">
                                    <i class="fas fa-check-circle mr-1"></i>Ativo
                                </span>
                            <?php else: ?>
                                <span class="badge badge-status badge-blocked">
                                    <i class="fas fa-ban mr-1"></i>Bloqueado
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><small><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small></td>
                        <td>
                            <small>
                                <?php
                                if ($user['last_login']) {
                                    echo date('d/m/Y H:i', strtotime($user['last_login']));
                                } else {
                                    echo '<span class="text-muted">Nunca</span>';
                                }
                                ?>
                            </small>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-info btn-action" onclick='editarUsuario(<?php echo json_encode($user); ?>)' title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>

                            <?php if ($user['status'] === 'active'): ?>
                                <button class="btn btn-sm btn-warning btn-action" onclick="toggleStatus('<?php echo $user['id']; ?>', 'block')" title="Bloquear">
                                    <i class="fas fa-ban"></i>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success btn-action" onclick="toggleStatus('<?php echo $user['id']; ?>', 'unblock')" title="Desbloquear">
                                    <i class="fas fa-check"></i>
                                </button>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-danger btn-action" onclick="apagarUsuario('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" title="Apagar">
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
        Total de <?php echo $userStats['total']; ?> usuário(s) |
        <?php echo $userStats['active']; ?> ativo(s) |
        <?php echo $userStats['blocked']; ?> bloqueado(s)
    </p>
</div>

<!-- Modal: Criar Usuário -->
<div class="modal fade" id="modalCriarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="admin.php?page=users">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Criar Novo Usuário</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?php echo admin_csrf_field(); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-2"></i>Username *</label>
                                <input type="text" name="username" class="form-control" required pattern="[a-zA-Z0-9_]{3,20}"
                                       placeholder="Ex: joao_silva" title="3-20 caracteres (letras, números e _)">
                                <small class="form-text text-muted">3-20 caracteres (apenas letras, números e _)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-envelope mr-2"></i>Email *</label>
                                <input type="email" name="email" class="form-control" required placeholder="email@exemplo.com">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-id-card mr-2"></i>Nome Completo</label>
                        <input type="text" name="full_name" class="form-control" placeholder="João da Silva">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user-shield mr-2"></i>Role *</label>
                                <select name="role" class="form-control" required>
                                    <option value="user">Usuário</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-key mr-2"></i>Permissões</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="instances" id="perm_instances_new">
                                        <label class="form-check-label" for="perm_instances_new">Gerenciar Instâncias</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="users" id="perm_users_new">
                                        <label class="form-check-label" for="perm_users_new">Gerenciar Usuários</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="settings" id="perm_settings_new">
                                        <label class="form-check-label" for="perm_settings_new">Configurações</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-2"></i>Notas</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Observações sobre o usuário..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Nota:</strong> Uma senha será gerada automaticamente e enviada por email (se configurado).
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="criar_usuario" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Editar Usuário -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="admin.php?page=users">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Usuário</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <?php echo admin_csrf_field(); ?>
                    <input type="hidden" name="user_id" id="edit_user_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-2"></i>Username *</label>
                                <input type="text" name="username" id="edit_username" class="form-control" required pattern="[a-zA-Z0-9_]{3,20}">
                                <small class="form-text text-muted">3-20 caracteres (apenas letras, números e _)</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-envelope mr-2"></i>Email *</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-id-card mr-2"></i>Nome Completo</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user-shield mr-2"></i>Role *</label>
                                <select name="role" id="edit_role" class="form-control" required>
                                    <option value="user">Usuário</option>
                                    <option value="admin">Administrador</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-key mr-2"></i>Permissões</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="instances" id="edit_perm_instances">
                                        <label class="form-check-label" for="edit_perm_instances">Gerenciar Instâncias</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="users" id="edit_perm_users">
                                        <label class="form-check-label" for="edit_perm_users">Gerenciar Usuários</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="settings" id="edit_perm_settings">
                                        <label class="form-check-label" for="edit_perm_settings">Configurações</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-sticky-note mr-2"></i>Notas</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="editar_usuario" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Forms ocultos para ações -->
<form method="post" action="admin.php?page=users" id="formToggleStatus" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="user_id" id="toggle_user_id">
    <input type="hidden" name="action" id="toggle_action">
    <input type="hidden" name="toggle_user_status" value="1">
</form>

<form method="post" action="admin.php?page=users" id="formApagarUsuario" style="display: none;">
    <?php echo admin_csrf_field(); ?>
    <input type="hidden" name="user_id" id="apagar_user_id">
    <input type="hidden" name="apagar_usuario" value="1">
</form>

<script>
$(document).ready(function() {
    setupTableSearch('searchUsuarios', 'tabelaUsuarios');
});

// Editar usuário
function editarUsuario(user) {
    $('#edit_user_id').val(user.id);
    $('#edit_username').val(user.username);
    $('#edit_email').val(user.email);
    $('#edit_full_name').val(user.full_name || '');
    $('#edit_role').val(user.role);
    $('#edit_notes').val(user.notes || '');

    // Permissões
    $('#edit_perm_instances').prop('checked', false);
    $('#edit_perm_users').prop('checked', false);
    $('#edit_perm_settings').prop('checked', false);

    if (user.permissions && Array.isArray(user.permissions)) {
        user.permissions.forEach(function(perm) {
            $('#edit_perm_' + perm).prop('checked', true);
        });
    }

    $('#modalEditarUsuario').modal('show');
}

// Bloquear/Desbloquear usuário
function toggleStatus(userId, action) {
    const actionText = action === 'block' ? 'bloquear' : 'desbloquear';

    if (confirm(`Tem certeza que deseja ${actionText} este usuário?`)) {
        $('#toggle_user_id').val(userId);
        $('#toggle_action').val(action);
        $('#formToggleStatus').submit();
    }
}

// Apagar usuário
function apagarUsuario(userId, username) {
    if (confirm(`Tem certeza que deseja APAGAR o usuário "${username}"?\n\nEsta ação não pode ser desfeita!`)) {
        if (confirm('CONFIRMAÇÃO FINAL: Apagar usuário permanentemente?')) {
            $('#apagar_user_id').val(userId);
            $('#formApagarUsuario').submit();
        }
    }
}

// Filtrar usuários
function filtrarUsuarios() {
    const status = $('#filterStatus').val();
    const role = $('#filterRole').val();

    $('#tabelaUsuarios tbody tr').each(function() {
        const $row = $(this);
        let show = true;

        if (status && $row.data('status') !== status) {
            show = false;
        }

        if (role && $row.data('role') !== role) {
            show = false;
        }

        $row.toggle(show);
    });
}
</script>
