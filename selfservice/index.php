<?php
/**
 * selfservice/index.php — ponto de entrada padrão da área de Self-Service.
 *
 * Mantemos selfservice.php como arquivo canônico (onde está toda a lógica,
 * forms com action="selfservice.php" e header Location). Este index apenas
 * inclui esse arquivo, de modo que acessar /selfservice/ funcione sem
 * listagem de diretório.
 */
require __DIR__ . '/selfservice.php';
