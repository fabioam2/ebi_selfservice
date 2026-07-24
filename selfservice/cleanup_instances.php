<?php
/**
 * Adaptador de compatibilidade para o cron legado.
 *
 * A politica atual de retencao usa quarentena e e implementada em
 * tarefas_agendadas.php. Nenhuma instancia e removida diretamente aqui.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script so pode ser executado pela linha de comando.' . PHP_EOL);
}

if (in_array('--dry-run', $argv, true)) {
    fwrite(STDOUT, "cleanup_instances.php foi substituido por tarefas_agendadas.php; nenhum dado foi alterado.\n");
    exit(0);
}

// --force era necessario no script antigo; no novo executor ele ignoraria os
// intervalos persistidos e nao deve ser repassado por este adaptador.
$argv = ['tarefas_agendadas.php'];
fwrite(STDOUT, "cleanup_instances.php esta obsoleto; executando tarefas_agendadas.php com a frequencia controlada.\n");
require __DIR__ . '/tarefas_agendadas.php';
