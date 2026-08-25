<?php

function reuniao_presencial_preparar_banco(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS reuniao_presencial_registros (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            funcao TEXT NOT NULL,
            nome TEXT NOT NULL,
            cidade TEXT NOT NULL DEFAULT \'\',
            comum TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (strftime(\'%Y-%m-%d %H:%M:%S\', \'now\', \'localtime\'))
        )'
    );

    $colunas = array_column(
        $pdo->query('PRAGMA table_info(reuniao_presencial_registros)')->fetchAll(),
        'name'
    );
    if (!in_array('cidade', $colunas, true)) {
        $pdo->exec('ALTER TABLE reuniao_presencial_registros ADD COLUMN cidade TEXT NOT NULL DEFAULT \'\'');
    }

    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_reuniao_presencial_data ON reuniao_presencial_registros(date(created_at))');
}