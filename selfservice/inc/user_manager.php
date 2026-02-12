<?php
/**
 * Gerenciador de Usuários
 *
 * Sistema completo para gerenciar usuários administrativos
 * do sistema EBI Self-Service.
 *
 * Funcionalidades:
 * - Criar, editar, bloquear e remover usuários
 * - Armazenamento em JSON
 * - Validações e segurança
 *
 * @version 2.0
 * @author EBI Team
 */

// Arquivo de dados dos usuários
define('USERS_DATA_FILE', __DIR__ . '/../data/admin_users.json');

/**
 * Inicializa o arquivo de usuários se não existir
 */
function initUsersFile() {
    if (!file_exists(USERS_DATA_FILE)) {
        $dir = dirname(USERS_DATA_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Criar arquivo com admin padrão
        $defaultAdmin = [
            'id' => uniqid('user_', true),
            'username' => 'admin',
            'email' => 'admin@ebi.local',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'last_login' => null,
            'permissions' => ['all']
        ];

        file_put_contents(USERS_DATA_FILE, json_encode([$defaultAdmin], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        chmod(USERS_DATA_FILE, 0600);
    }
}

/**
 * Carrega todos os usuários
 *
 * @return array Lista de usuários
 */
function loadUsers() {
    initUsersFile();

    $content = file_get_contents(USERS_DATA_FILE);
    $users = json_decode($content, true);

    return is_array($users) ? $users : [];
}

/**
 * Salva usuários no arquivo
 *
 * @param array $users Lista de usuários
 * @return bool Sucesso
 */
function saveUsers($users) {
    $result = file_put_contents(
        USERS_DATA_FILE,
        json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    if ($result !== false) {
        chmod(USERS_DATA_FILE, 0600);
        return true;
    }

    return false;
}

/**
 * Busca um usuário por ID
 *
 * @param string $userId ID do usuário
 * @return array|null Dados do usuário ou null
 */
function getUserById($userId) {
    $users = loadUsers();

    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            return $user;
        }
    }

    return null;
}

/**
 * Busca um usuário por username
 *
 * @param string $username Nome de usuário
 * @return array|null Dados do usuário ou null
 */
function getUserByUsername($username) {
    $users = loadUsers();

    foreach ($users as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    }

    return null;
}

/**
 * Busca um usuário por email
 *
 * @param string $email Email do usuário
 * @return array|null Dados do usuário ou null
 */
function getUserByEmail($email) {
    $users = loadUsers();

    foreach ($users as $user) {
        if ($user['email'] === $email) {
            return $user;
        }
    }

    return null;
}

/**
 * Cria um novo usuário
 *
 * @param array $data Dados do usuário
 * @return array Resultado com 'success' e 'message'/'user_id'
 */
function createUser($data) {
    // Validar dados obrigatórios
    $required = ['username', 'email', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return [
                'success' => false,
                'message' => "Campo '{$field}' é obrigatório"
            ];
        }
    }

    // Validar username
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
        return [
            'success' => false,
            'message' => 'Username deve ter 3-20 caracteres (letras, números e _)'
        ];
    }

    // Validar email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Email inválido'
        ];
    }

    // Verificar se username já existe
    if (getUserByUsername($data['username'])) {
        return [
            'success' => false,
            'message' => 'Username já está em uso'
        ];
    }

    // Verificar se email já existe
    if (getUserByEmail($data['email'])) {
        return [
            'success' => false,
            'message' => 'Email já está cadastrado'
        ];
    }

    $users = loadUsers();

    // Criar novo usuário
    $newUser = [
        'id' => uniqid('user_', true),
        'username' => trim($data['username']),
        'email' => trim($data['email']),
        'full_name' => trim($data['full_name'] ?? ''),
        'role' => $data['role'],
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'permissions' => $data['permissions'] ?? [],
        'notes' => trim($data['notes'] ?? '')
    ];

    $users[] = $newUser;

    if (saveUsers($users)) {
        return [
            'success' => true,
            'message' => 'Usuário criado com sucesso',
            'user_id' => $newUser['id']
        ];
    }

    return [
        'success' => false,
        'message' => 'Erro ao salvar usuário'
    ];
}

/**
 * Atualiza um usuário existente
 *
 * @param string $userId ID do usuário
 * @param array $data Dados para atualizar
 * @return array Resultado com 'success' e 'message'
 */
function updateUser($userId, $data) {
    $users = loadUsers();
    $userIndex = null;

    // Encontrar usuário
    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === null) {
        return [
            'success' => false,
            'message' => 'Usuário não encontrado'
        ];
    }

    // Validar email se fornecido
    if (isset($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Email inválido'
            ];
        }

        // Verificar se email já existe em outro usuário
        foreach ($users as $index => $user) {
            if ($index !== $userIndex && $user['email'] === $data['email']) {
                return [
                    'success' => false,
                    'message' => 'Email já está em uso por outro usuário'
                ];
            }
        }
    }

    // Validar username se fornecido
    if (isset($data['username'])) {
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $data['username'])) {
            return [
                'success' => false,
                'message' => 'Username deve ter 3-20 caracteres (letras, números e _)'
            ];
        }

        // Verificar se username já existe em outro usuário
        foreach ($users as $index => $user) {
            if ($index !== $userIndex && $user['username'] === $data['username']) {
                return [
                    'success' => false,
                    'message' => 'Username já está em uso por outro usuário'
                ];
            }
        }
    }

    // Atualizar campos permitidos
    $allowedFields = ['username', 'email', 'full_name', 'role', 'permissions', 'notes'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $users[$userIndex][$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
        }
    }

    $users[$userIndex]['updated_at'] = date('Y-m-d H:i:s');

    if (saveUsers($users)) {
        return [
            'success' => true,
            'message' => 'Usuário atualizado com sucesso'
        ];
    }

    return [
        'success' => false,
        'message' => 'Erro ao salvar alterações'
    ];
}

/**
 * Bloqueia ou desbloqueia um usuário
 *
 * @param string $userId ID do usuário
 * @param bool $block true para bloquear, false para desbloquear
 * @return array Resultado com 'success' e 'message'
 */
function toggleUserStatus($userId, $block = true) {
    $users = loadUsers();
    $userIndex = null;

    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === null) {
        return [
            'success' => false,
            'message' => 'Usuário não encontrado'
        ];
    }

    $users[$userIndex]['status'] = $block ? 'blocked' : 'active';
    $users[$userIndex]['updated_at'] = date('Y-m-d H:i:s');

    if (saveUsers($users)) {
        $action = $block ? 'bloqueado' : 'desbloqueado';
        return [
            'success' => true,
            'message' => "Usuário {$action} com sucesso"
        ];
    }

    return [
        'success' => false,
        'message' => 'Erro ao atualizar status'
    ];
}

/**
 * Remove um usuário
 *
 * @param string $userId ID do usuário
 * @return array Resultado com 'success' e 'message'
 */
function deleteUser($userId) {
    $users = loadUsers();
    $newUsers = [];
    $found = false;

    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            $found = true;
            // Não adicionar este usuário (efetivamente removendo-o)
            continue;
        }
        $newUsers[] = $user;
    }

    if (!$found) {
        return [
            'success' => false,
            'message' => 'Usuário não encontrado'
        ];
    }

    // Não permitir remover se for o último admin
    $adminCount = 0;
    foreach ($newUsers as $user) {
        if ($user['role'] === 'admin' && $user['status'] === 'active') {
            $adminCount++;
        }
    }

    if ($adminCount === 0) {
        return [
            'success' => false,
            'message' => 'Não é possível remover o último administrador ativo'
        ];
    }

    if (saveUsers($newUsers)) {
        return [
            'success' => true,
            'message' => 'Usuário removido com sucesso'
        ];
    }

    return [
        'success' => false,
        'message' => 'Erro ao remover usuário'
    ];
}

/**
 * Atualiza o último login do usuário
 *
 * @param string $userId ID do usuário
 * @return bool Sucesso
 */
function updateLastLogin($userId) {
    $users = loadUsers();

    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $users[$index]['last_login'] = date('Y-m-d H:i:s');
            return saveUsers($users);
        }
    }

    return false;
}

/**
 * Lista usuários com filtros
 *
 * @param array $filters Filtros opcionais (status, role, search)
 * @return array Lista de usuários filtrados
 */
function listUsers($filters = []) {
    $users = loadUsers();
    $filtered = [];

    foreach ($users as $user) {
        // Filtrar por status
        if (isset($filters['status']) && $user['status'] !== $filters['status']) {
            continue;
        }

        // Filtrar por role
        if (isset($filters['role']) && $user['role'] !== $filters['role']) {
            continue;
        }

        // Filtrar por busca (username, email, full_name)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $found = false;

            $searchFields = ['username', 'email', 'full_name'];
            foreach ($searchFields as $field) {
                if (isset($user[$field]) && stripos($user[$field], $search) !== false) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                continue;
            }
        }

        $filtered[] = $user;
    }

    return $filtered;
}

/**
 * Obtém estatísticas de usuários
 *
 * @return array Estatísticas
 */
function getUserStats() {
    $users = loadUsers();

    $stats = [
        'total' => count($users),
        'active' => 0,
        'blocked' => 0,
        'admins' => 0,
        'users' => 0,
        'last_created' => null,
        'recent_logins' => 0
    ];

    $oneDayAgo = date('Y-m-d H:i:s', strtotime('-1 day'));

    foreach ($users as $user) {
        // Status
        if ($user['status'] === 'active') {
            $stats['active']++;
        } else {
            $stats['blocked']++;
        }

        // Role
        if ($user['role'] === 'admin') {
            $stats['admins']++;
        } else {
            $stats['users']++;
        }

        // Último criado
        if ($stats['last_created'] === null || $user['created_at'] > $stats['last_created']) {
            $stats['last_created'] = $user['created_at'];
        }

        // Logins recentes (últimas 24h)
        if ($user['last_login'] && $user['last_login'] >= $oneDayAgo) {
            $stats['recent_logins']++;
        }
    }

    return $stats;
}

/**
 * Valida permissões do usuário
 *
 * @param array $user Dados do usuário
 * @param string $permission Permissão a verificar
 * @return bool Tem permissão
 */
function userHasPermission($user, $permission) {
    // Admin tem todas as permissões
    if ($user['role'] === 'admin') {
        return true;
    }

    // Verificar permissões específicas
    if (isset($user['permissions']) && is_array($user['permissions'])) {
        return in_array($permission, $user['permissions']) || in_array('all', $user['permissions']);
    }

    return false;
}

/**
 * Exporta usuários para CSV
 *
 * @return string Conteúdo CSV
 */
function exportUsersToCSV() {
    $users = loadUsers();

    // Cabeçalho
    $csv = "ID,Username,Email,Nome Completo,Role,Status,Criado Em,Último Login\n";

    // Dados
    foreach ($users as $user) {
        $csv .= sprintf(
            '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
            $user['id'] ?? '',
            $user['username'] ?? '',
            $user['email'] ?? '',
            $user['full_name'] ?? '',
            $user['role'] ?? '',
            $user['status'] ?? '',
            $user['created_at'] ?? '',
            $user['last_login'] ?? ''
        );
    }

    return $csv;
}
