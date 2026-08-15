<?php

function ebi_obter_chave_criptografia_qr(string $configFile): string {
    if (!is_readable($configFile)) {
        return '';
    }

    $config = parse_ini_file($configFile, true, INI_SCANNER_TYPED);
    if (!is_array($config)) {
        return '';
    }

    return trim((string)($config['SEGURANCA']['QR_CODE_CRYPTO_KEY'] ?? ''));
}