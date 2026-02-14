<?php
/**
 * Gerenciador de Envio de Emails - Self-Service EBI
 *
 * Funções para envio de emails usando PHPMailer
 *
 * @version 1.0
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Carrega configurações de email do .env
 *
 * @return array Configurações de email
 */
function carregarConfigEmail(): array {
    $config = [
        'habilitado' => filter_var($_ENV['EMAIL_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
        'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.hostinger.com',
        'smtp_port' => intval($_ENV['SMTP_PORT'] ?? 465),
        'smtp_secure' => $_ENV['SMTP_SECURE'] ?? 'ssl',
        'smtp_user' => $_ENV['SMTP_USER'] ?? 'no-reply@ebi.ccbcampinas.org.br',
        'smtp_password' => $_ENV['SMTP_PASSWORD'] ?? 'senha123',
        'from_email' => $_ENV['EMAIL_FROM'] ?? 'no-reply@ebi.ccbcampinas.org.br',
        'from_name' => $_ENV['EMAIL_FROM_NAME'] ?? 'EBI Self-Service'
    ];

    return $config;
}

/**
 * Envia email com dados de acesso ao sistema
 *
 * @param string $destinatario Email do destinatário
 * @param string $nome Nome do destinatário
 * @param string $linkSistema Link de acesso ao sistema
 * @param string $cidade Cidade do usuário
 * @param string $comum Comum do usuário
 * @return array{sucesso: bool, erro?: string} Resultado do envio
 */
function enviarEmailAcesso(string $destinatario, string $nome, string $linkSistema, string $cidade, string $comum): array {
    $config = carregarConfigEmail();

    // Verificar se envio de email está habilitado
    if (!$config['habilitado']) {
        return [
            'sucesso' => false,
            'erro' => 'Envio de email está desabilitado'
        ];
    }

    try {
        // Verificar se PHPMailer está disponível
        if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            return [
                'sucesso' => false,
                'erro' => 'PHPMailer não instalado. Execute: composer require phpmailer/phpmailer'
            ];
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new PHPMailer(true);

        // Configurações do servidor SMTP
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = 'UTF-8';

        // Remetente
        $mail->setFrom($config['from_email'], $config['from_name']);

        // Destinatário
        $mail->addAddress($destinatario, $nome);

        // Responder para (reply-to)
        $mail->addReplyTo($config['from_email'], $config['from_name']);

        // Conteúdo do email
        $mail->isHTML(true);
        $mail->Subject = '🎉 Sua conta EBI foi criada com sucesso!';

        $corpo = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body {
                    font-family: 'Arial', sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 10px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .content {
                    padding: 30px 20px;
                    color: #333;
                }
                .content h2 {
                    color: #667eea;
                    margin-top: 0;
                }
                .info-box {
                    background-color: #f0f4ff;
                    border-left: 4px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .info-box strong {
                    color: #667eea;
                }
                .link-box {
                    background-color: #e7f3ff;
                    border: 2px solid #667eea;
                    padding: 15px;
                    margin: 20px 0;
                    border-radius: 8px;
                    text-align: center;
                }
                .link-box a {
                    color: #667eea;
                    text-decoration: none;
                    font-weight: bold;
                    word-break: break-all;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .footer {
                    background-color: #f4f4f4;
                    padding: 20px;
                    text-align: center;
                    color: #777;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>🎉 Bem-vindo ao Sistema EBI!</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Sua conta foi criada com <strong>sucesso</strong>! 🎊</p>

                    <div class='info-box'>
                        <strong>📋 Informações da sua conta:</strong><br><br>
                        <strong>Nome:</strong> {$nome}<br>
                        <strong>Email:</strong> {$destinatario}<br>
                        <strong>Cidade:</strong> {$cidade}<br>
                        <strong>Comum:</strong> {$comum}
                    </div>

                    <p><strong>🔗 Link de Acesso:</strong></p>
                    <div class='link-box'>
                        <a href='{$linkSistema}' target='_blank'>{$linkSistema}</a>
                    </div>

                    <center>
                        <a href='{$linkSistema}' class='btn' target='_blank'>🚀 Acessar Meu Sistema</a>
                    </center>

                    <div class='info-box'>
                        <strong>⚠️ IMPORTANTE:</strong><br>
                        • Guarde este email em local seguro<br>
                        • Use a senha que você definiu no cadastro<br>
                        • Faça backup regular dos seus dados<br>
                        • Em caso de dúvidas, entre em contato com o suporte
                    </div>

                    <p>Bom trabalho! 😊</p>
                </div>
                <div class='footer'>
                    <p>EBI Self-Service - Sistema de Cadastro de Crianças</p>
                    <p>Este é um email automático, não responda.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $corpo;

        // Versão texto (para clientes que não suportam HTML)
        $mail->AltBody = "Olá, {$nome}!\n\n"
                       . "Sua conta foi criada com sucesso!\n\n"
                       . "Informações:\n"
                       . "Nome: {$nome}\n"
                       . "Email: {$destinatario}\n"
                       . "Cidade: {$cidade}\n"
                       . "Comum: {$comum}\n\n"
                       . "Link de acesso:\n{$linkSistema}\n\n"
                       . "Guarde este email em local seguro e use a senha que você definiu no cadastro.\n\n"
                       . "EBI Self-Service";

        // Enviar
        $mail->send();

        return [
            'sucesso' => true
        ];

    } catch (Exception $e) {
        return [
            'sucesso' => false,
            'erro' => $mail->ErrorInfo ?? $e->getMessage()
        ];
    }
}

/**
 * Testa conexão SMTP com as configurações atuais
 *
 * @return array{sucesso: bool, mensagem: string} Resultado do teste
 */
function testarConexaoSMTP(): array {
    $config = carregarConfigEmail();

    try {
        if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            return [
                'sucesso' => false,
                'mensagem' => 'PHPMailer não instalado'
            ];
        }

        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $config['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['smtp_user'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];

        // Apenas testa a conexão, sem enviar
        if (!$mail->smtpConnect()) {
            return [
                'sucesso' => false,
                'mensagem' => 'Falha ao conectar ao servidor SMTP'
            ];
        }

        $mail->smtpClose();

        return [
            'sucesso' => true,
            'mensagem' => 'Conexão SMTP estabelecida com sucesso!'
        ];

    } catch (Exception $e) {
        return [
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ];
    }
}
