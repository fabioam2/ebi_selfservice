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
function enviarEmailAcesso(
    string $destinatario,
    string $nome,
    string $linkSistema,
    string $cidade,
    string $comum,
    ?string $linkExcluir = null
): array {
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

        $nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $emailSafe = htmlspecialchars($destinatario, ENT_QUOTES, 'UTF-8');
        $cidadeSafe = htmlspecialchars($cidade, ENT_QUOTES, 'UTF-8');
        $comumSafe = htmlspecialchars($comum, ENT_QUOTES, 'UTF-8');
        $linkSafe = htmlspecialchars($linkSistema, ENT_QUOTES, 'UTF-8');
        $deleteBlock = '';
        if ($linkExcluir !== null && $linkExcluir !== '') {
            $deleteSafe = htmlspecialchars($linkExcluir, ENT_QUOTES, 'UTF-8');
            $deleteBlock = "
                    <div style='margin-top:22px;padding:14px;border:1px solid #f5c2c7;background:#fff5f5;border-radius:8px'>
                        <strong style='color:#9b1c1c'>Precisa encerrar esta instância?</strong>
                        <p style='margin:8px 0;color:#5f2120'>A exclusão entra em quarentena e pode ser desfeita pelo prazo informado no próximo e-mail.</p>
                        <a href='{$deleteSafe}' style='color:#b91c1c;font-weight:700'>Excluir esta instância</a>
                    </div>";
        }

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
                    <h2>Olá, {$nomeSafe}!</h2>
                    <p>Sua conta foi criada com <strong>sucesso</strong>! 🎊</p>

                    <div class='info-box'>
                        <strong>📋 Informações da sua conta:</strong><br><br>
                        <strong>Nome:</strong> {$nomeSafe}<br>
                        <strong>Email:</strong> {$emailSafe}<br>
                        <strong>Cidade:</strong> {$cidadeSafe}<br>
                        <strong>Comum:</strong> {$comumSafe}
                    </div>

                    <p><strong>🔗 Link de Acesso:</strong></p>
                    <div class='link-box'>
                        <a href='{$linkSafe}' target='_blank'>{$linkSafe}</a>
                    </div>

                    <center>
                        <a href='{$linkSafe}' class='btn' target='_blank'>🚀 Acessar Meu Sistema</a>
                    </center>

                    <div class='info-box'>
                        <strong>⚠️ IMPORTANTE:</strong><br>
                        • Guarde este email em local seguro<br>
                        • Use a senha que você definiu no cadastro<br>
                        • Faça backup regular dos seus dados<br>
                        • Em caso de dúvidas, entre em contato com o suporte
                    </div>

                    <p>Bom trabalho! 😊</p>
                    {$deleteBlock}
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
 * Envia email com links de todas as contas vinculadas ao mesmo email.
 *
 * @param string $destinatario Email de destino
 * @param string $nome Nome do usuário
 * @param array<int,array{nome:string,cidade:string,comum:string,link:string,delete_link?:string}> $contas
 * @return array{sucesso: bool, erro?: string}
 */
function enviarEmailRecuperacaoContas(string $destinatario, string $nome, array $contas): array {
        $config = carregarConfigEmail();

        if (!$config['habilitado']) {
                return ['sucesso' => false, 'erro' => 'Envio de email está desabilitado'];
        }

        if (empty($contas)) {
                return ['sucesso' => false, 'erro' => 'Nenhuma conta encontrada para envio'];
        }

        try {
                if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                        return ['sucesso' => false, 'erro' => 'PHPMailer não instalado'];
                }
                require_once __DIR__ . '/../../vendor/autoload.php';

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['smtp_user'];
                $mail->Password   = $config['smtp_password'];
                $mail->SMTPSecure = $config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $config['smtp_port'];
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($destinatario, $nome);
                $mail->addReplyTo($config['from_email'], $config['from_name']);

                $nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
                $mail->isHTML(true);
                $mail->Subject = '🔐 Recuperação de Contas — EBI Self-Service';

                $itensHtml = '';
                $itensTxt  = '';
                foreach ($contas as $conta) {
                        $nomeConta   = htmlspecialchars((string)($conta['nome'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $cidadeConta = htmlspecialchars((string)($conta['cidade'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $comumConta  = htmlspecialchars((string)($conta['comum'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $linkConta   = htmlspecialchars((string)($conta['link'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $deleteLink  = htmlspecialchars((string)($conta['delete_link'] ?? ''), ENT_QUOTES, 'UTF-8');

                        $deleteHtml = $deleteLink !== ''
                            ? "<div style='margin-top:8px'><a href='{$deleteLink}' style='color:#b91c1c;font-weight:700'>Excluir esta instância</a></div>"
                            : '';

                        $itensHtml .= "
                                <div style='border:1px solid #e3e8ef;border-radius:8px;padding:12px;margin-bottom:10px;background:#fafcff'>
                                        <div><strong>Nome:</strong> {$nomeConta}</div>
                                        <div><strong>Cidade:</strong> {$cidadeConta}</div>
                                        <div><strong>Comum:</strong> {$comumConta}</div>
                                        <div style='margin-top:6px'><strong>Link:</strong> <a href='{$linkConta}' target='_blank'>{$linkConta}</a></div>
                                        {$deleteHtml}
                                </div>";

                        $itensTxt .= "Nome: " . ($conta['nome'] ?? '') . "\n"
                                            .  "Cidade: " . ($conta['cidade'] ?? '') . "\n"
                                            .  "Comum: " . ($conta['comum'] ?? '') . "\n"
                                            .  "Link: " . ($conta['link'] ?? '') . "\n\n";
                }

                $mail->Body = "
                <!DOCTYPE html>
                <html lang='pt-BR'>
                <head><meta charset='UTF-8'></head>
                <body style='font-family:Arial,sans-serif;background:#f4f6fb;margin:0;padding:20px'>
                    <div style='max-width:620px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 10px rgba(0,0,0,.1)'>
                        <div style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:24px 20px;text-align:center'>
                            <h1 style='margin:0;font-size:22px'>🔐 Recuperação de Contas</h1>
                        </div>
                        <div style='padding:24px 20px;color:#2d3748'>
                            <p>Olá, <strong>{$nomeSafe}</strong>!</p>
                            <p>Recebemos uma solicitação para recuperar as contas vinculadas a este e-mail.</p>
                            <p><strong>Contas encontradas:</strong></p>
                            {$itensHtml}
                            <p style='margin-top:18px;background:#fff8e1;border-left:4px solid #ffb300;padding:10px;border-radius:4px'>
                                Se você não solicitou esta recuperação, ignore este e-mail.
                            </p>
                        </div>
                        <div style='background:#f4f4f4;padding:14px;text-align:center;color:#777;font-size:12px'>
                            EBI Self-Service · Este é um email automático, não responda.
                        </div>
                    </div>
                </body>
                </html>";

                $mail->AltBody = "Olá, {$nome}!\n\n"
                                            . "Recebemos uma solicitação para recuperar as contas vinculadas ao seu e-mail.\n\n"
                                            . $itensTxt
                                            . "Se você não solicitou esta recuperação, ignore este e-mail.\n\n"
                                            . "EBI Self-Service";

                $mail->send();
                return ['sucesso' => true];
        } catch (Exception $e) {
                return ['sucesso' => false, 'erro' => $mail->ErrorInfo ?? $e->getMessage()];
        }
}

/**
 * Envia email com nova senha temporária (reset de senha).
 *
 * @param string $destinatario Email do usuário
 * @param string $nome         Nome do usuário
 * @param string $linkSistema  Link de acesso
 * @param string $novaSenha    Senha temporária em texto plano (só vai pelo email)
 * @return array{sucesso:bool, erro?:string}
 */
function enviarEmailResetSenha(
    string $destinatario,
    string $nome,
    string $linkSistema,
    string $novaSenha,
    ?string $linkExcluir = null
): array {
    $config = carregarConfigEmail();

    if (!$config['habilitado']) {
        return ['sucesso' => false, 'erro' => 'Envio de email está desabilitado'];
    }

    try {
        if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            return ['sucesso' => false, 'erro' => 'PHPMailer não instalado'];
        }
        require_once __DIR__ . '/../../vendor/autoload.php';

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_password'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($destinatario, $nome);
        $mail->addReplyTo($config['from_email'], $config['from_name']);

        $mail->isHTML(true);
        $mail->Subject = '🔑 Sua senha foi redefinida — EBI Self-Service';

        $nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $senhaSafe = htmlspecialchars($novaSenha, ENT_QUOTES, 'UTF-8');
        $linkSafe = htmlspecialchars($linkSistema, ENT_QUOTES, 'UTF-8');
                $deleteBlock = '';
                if ($linkExcluir !== null && $linkExcluir !== '') {
                        $deleteSafe = htmlspecialchars($linkExcluir, ENT_QUOTES, 'UTF-8');
                        $deleteBlock = "
                            <p style='margin-top:22px;padding:14px;background:#fff5f5;border-left:4px solid #b91c1c;border-radius:4px'>
                                <strong>Encerrar a instância?</strong> <a href='{$deleteSafe}' style='color:#b91c1c;font-weight:700'>Excluir esta instância</a>. A exclusão ficará em quarentena pelo prazo de recuperação.
                            </p>";
                }

        $mail->Body = "
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head><meta charset='UTF-8'></head>
        <body style='font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:20px'>
          <div style='max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;box-shadow:0 4px 10px rgba(0,0,0,.1)'>
            <div style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:24px 20px;text-align:center'>
              <h1 style='margin:0;font-size:22px'>🔑 Senha Redefinida</h1>
            </div>
            <div style='padding:28px 22px;color:#333'>
              <p>Olá, <strong>$nomeSafe</strong>!</p>
              <p>Sua senha do EBI Self-Service foi redefinida pelo administrador.</p>
              <div style='background:#fff7e6;border:1px solid #ffc107;padding:14px 18px;border-radius:8px;margin:18px 0'>
                <div style='color:#8a5a00;font-size:12px;margin-bottom:4px'>NOVA SENHA TEMPORÁRIA</div>
                <div style='font-size:20px;font-weight:700;color:#8a5a00;font-family:monospace'>$senhaSafe</div>
              </div>
              <p><strong>Link de acesso:</strong></p>
              <div style='background:#e7f3ff;border:2px solid #667eea;padding:12px;border-radius:8px;text-align:center;word-break:break-all'>
                <a href='$linkSafe' style='color:#667eea;text-decoration:none;font-weight:600'>$linkSafe</a>
              </div>
              <p style='margin-top:22px;padding:14px;background:#f8d7da;border-left:4px solid #dc3545;border-radius:4px'>
                <strong>⚠️ Importante:</strong> esta senha foi gerada automaticamente. Acesse o sistema e troque-a por uma senha pessoal o quanto antes.
              </p>
                            {$deleteBlock}
            </div>
            <div style='background:#f4f4f4;padding:14px;text-align:center;color:#777;font-size:12px'>
              EBI Self-Service · Este é um email automático, não responda.
            </div>
          </div>
        </body></html>";

        $mail->AltBody = "Olá, $nome!\n\n"
                      . "Sua senha foi redefinida.\n"
                      . "Nova senha temporária: $novaSenha\n\n"
                      . "Acesse: $linkSistema\n\n"
                      . "Por favor, troque a senha após o primeiro login.\n\n"
                      . "EBI Self-Service";

        $mail->send();
        return ['sucesso' => true];
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $mail->ErrorInfo ?? $e->getMessage()];
    }
}

/**
 * Envia os avisos automáticos do ciclo de vida de uma instância.
 */
function enviarEmailCicloInstancia(string $destinatario, string $nome, string $assunto, string $corpoHtml, string $corpoTexto): array {
    $config = carregarConfigEmail();
    if (!$config['habilitado']) {
        return ['sucesso' => false, 'erro' => 'Envio de email está desabilitado'];
    }

    try {
        if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            return ['sucesso' => false, 'erro' => 'PHPMailer não instalado'];
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
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($destinatario, $nome);
        $mail->addReplyTo($config['from_email'], $config['from_name']);
        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $corpoHtml;
        $mail->AltBody = $corpoTexto;
        $mail->send();
        return ['sucesso' => true];
    } catch (Exception $e) {
        return ['sucesso' => false, 'erro' => $mail->ErrorInfo ?? $e->getMessage()];
    }
}

function enviarEmailInatividadeInstancia(string $destinatario, string $nome, string $linkSistema, string $quarentenaEm): array {
    $nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $linkSafe = htmlspecialchars($linkSistema, ENT_QUOTES, 'UTF-8');
    $dataSafe = htmlspecialchars(date('d/m/Y H:i', strtotime($quarentenaEm)), ENT_QUOTES, 'UTF-8');
    $html = "<html><body style='font-family:Arial,sans-serif;color:#27364a'>
        <h2>Instância sem atividade</h2>
        <p>Olá, <strong>{$nomeSafe}</strong>. Sua instância não registra acesso recente.</p>
        <p>Ela será colocada em quarentena em <strong>{$dataSafe}</strong>, caso não volte a ser utilizada.</p>
        <p><a href='{$linkSafe}' style='font-weight:700;color:#0e7490'>Acessar minha instância</a></p>
        <p>Depois de entrar em quarentena, ainda haverá prazo para recuperação.</p>
    </body></html>";
    $text = "Olá, {$nome}. Sua instância está sem atividade e será colocada em quarentena em {$dataSafe}. Acesse: {$linkSistema}";
    return enviarEmailCicloInstancia($destinatario, $nome, 'Aviso de inatividade da instância EBI', $html, $text);
}

function enviarEmailQuarentenaInstancia(string $destinatario, string $nome, string $linkRecuperacao, string $expiraEm): array {
    $nomeSafe = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
    $linkSafe = htmlspecialchars($linkRecuperacao, ENT_QUOTES, 'UTF-8');
    $dataSafe = htmlspecialchars(date('d/m/Y H:i', strtotime($expiraEm)), ENT_QUOTES, 'UTF-8');
    $html = "<html><body style='font-family:Arial,sans-serif;color:#27364a'>
        <h2>Instância em quarentena</h2>
        <p>Olá, <strong>{$nomeSafe}</strong>. Sua instância foi colocada em quarentena e está temporariamente indisponível.</p>
        <p>Você pode desistir da exclusão e restaurá-la até <strong>{$dataSafe}</strong>.</p>
        <p><a href='{$linkSafe}' style='display:inline-block;padding:12px 18px;background:#0e7490;color:#fff;text-decoration:none;font-weight:700'>Recuperar minha instância</a></p>
        <p>Após esse prazo, os dados serão excluídos definitivamente.</p>
    </body></html>";
    $text = "Olá, {$nome}. Sua instância está em quarentena. Recupere até {$dataSafe}: {$linkRecuperacao}";
    return enviarEmailCicloInstancia($destinatario, $nome, 'Sua instância EBI está em quarentena', $html, $text);
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
