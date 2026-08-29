(function () {
    'use strict';

    function expandPayload(payload) {
        var fields = payload.split('\t');
        if (fields[0] !== 'EBIC1') {
            return payload;
        }

        if (fields.length < 10 || (fields.length - 6) % 4 !== 0) {
            throw new Error('Formato compacto de QR inválido.');
        }

        var responsible = fields[1];
        var phone = fields[2];
        var common = fields[3];
        var city = fields[4];
        var state = fields[5];
        if (!responsible || !phone || !common || (state && !/^[A-Z]{2}$/.test(state))) {
            throw new Error('Dados compartilhados do QR compacto inválidos.');
        }

        var records = [];
        for (var index = 6; index < fields.length; index += 4) {
            var child = fields[index];
            var age = fields[index + 1];
            var sex = fields[index + 2].toUpperCase();
            var compactDate = fields[index + 3];
            var dateMatch = /^(\d{2})(\d{2})(\d{4})$/.exec(compactDate);

            if (!child || !/^(?:[3-9]|1[0-1])$/.test(age) || !/^[MF]$/.test(sex) || !dateMatch) {
                throw new Error('Dados de criança do QR compacto inválidos.');
            }

            records.push([
                child,
                responsible,
                age,
                phone,
                common,
                city,
                state,
                sex,
                dateMatch[1] + '/' + dateMatch[2] + '/' + dateMatch[3]
            ].join('\t'));
        }

        return records.join('\t');
    }

    window.EbiQrCompactTest = Object.freeze({
        expandPayload: expandPayload
    });

    var scannerPrototype = window.Html5Qrcode && window.Html5Qrcode.prototype;
    if (!scannerPrototype || scannerPrototype.__ebiCompactPayloadAdapter) {
        return;
    }

    var originalStart = scannerPrototype.start;
    scannerPrototype.start = function (cameraConfig, configuration, onSuccess, onError) {
        var adaptedOnSuccess = async function (decodedText, decodedResult) {
            if (typeof decodedText !== 'string'
                || !window.EbiQrCrypto
                || !window.EbiQrCrypto.isEncrypted(decodedText)) {
                return onSuccess(decodedText, decodedResult);
            }

            try {
                var decryptedPayload = await window.EbiQrCrypto.decrypt(decodedText);
                var normalizedPayload = expandPayload(decryptedPayload);
                if (normalizedPayload !== decryptedPayload) {
                    return onSuccess(normalizedPayload, decodedResult);
                }
            } catch (error) {
                // The existing reader displays its regular invalid-QR message.
            }

            return onSuccess(decodedText, decodedResult);
        };

        return originalStart.call(this, cameraConfig, configuration, adaptedOnSuccess, onError);
    };
    scannerPrototype.__ebiCompactPayloadAdapter = true;
})();