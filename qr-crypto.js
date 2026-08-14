(function(global) {
    'use strict';

    var PREFIX = 'EBIQR1';
    var PASSPHRASE = 'Bonfim123';
    var ITERATIONS = 100000;

    function requireWebCrypto() {
        if (!global.crypto || !global.crypto.subtle) {
            throw new Error('Criptografia do navegador indisponivel.');
        }
    }

    function bytesToBase64Url(bytes) {
        var binary = '';
        for (var index = 0; index < bytes.length; index++) {
            binary += String.fromCharCode(bytes[index]);
        }
        return global.btoa(binary)
            .replace(/\+/g, '-')
            .replace(/\//g, '_')
            .replace(/=+$/g, '');
    }

    function base64UrlToBytes(value) {
        var padded = value.replace(/-/g, '+').replace(/_/g, '/');
        while (padded.length % 4 !== 0) {
            padded += '=';
        }
        var binary = global.atob(padded);
        var bytes = new Uint8Array(binary.length);
        for (var index = 0; index < binary.length; index++) {
            bytes[index] = binary.charCodeAt(index);
        }
        return bytes;
    }

    async function deriveKey(salt) {
        var sourceKey = await global.crypto.subtle.importKey(
            'raw',
            new TextEncoder().encode(PASSPHRASE),
            'PBKDF2',
            false,
            ['deriveKey']
        );

        return global.crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: ITERATIONS,
                hash: 'SHA-256'
            },
            sourceKey,
            { name: 'AES-GCM', length: 256 },
            false,
            ['encrypt', 'decrypt']
        );
    }

    function isEncrypted(payload) {
        return typeof payload === 'string' && payload.indexOf(PREFIX + '.') === 0;
    }

    async function encrypt(plaintext) {
        requireWebCrypto();
        var salt = global.crypto.getRandomValues(new Uint8Array(16));
        var iv = global.crypto.getRandomValues(new Uint8Array(12));
        var key = await deriveKey(salt);
        var ciphertext = await global.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            key,
            new TextEncoder().encode(plaintext)
        );

        return [
            PREFIX,
            bytesToBase64Url(salt),
            bytesToBase64Url(iv),
            bytesToBase64Url(new Uint8Array(ciphertext))
        ].join('.');
    }

    async function decrypt(payload) {
        if (!isEncrypted(payload)) {
            return payload;
        }

        requireWebCrypto();
        var parts = payload.split('.');
        if (parts.length !== 4 || parts[0] !== PREFIX) {
            throw new Error('QR criptografado invalido.');
        }

        var salt = base64UrlToBytes(parts[1]);
        var iv = base64UrlToBytes(parts[2]);
        var ciphertext = base64UrlToBytes(parts[3]);
        var key = await deriveKey(salt);
        var plaintext = await global.crypto.subtle.decrypt(
            { name: 'AES-GCM', iv: iv },
            key,
            ciphertext
        );

        return new TextDecoder().decode(plaintext);
    }

    global.EbiQrCrypto = Object.freeze({
        decrypt: decrypt,
        encrypt: encrypt,
        isEncrypted: isEncrypted
    });
})(window);
