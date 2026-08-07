<div class="content-header d-flex justify-content-between align-items-center">
    <h2><i class="fas fa-sitemap mr-2"></i>Página de Links</h2>
    <span class="text-muted small">Acesso restrito ao admin autenticado</span>
</div>

<?php
$qzCertificateDownload = '../ebi/template/download.php?file=cert';
$qzSigningBaseUrl = '../ebi/template/assets/signing/';
?>

<div class="card table-custom p-4">
    <p class="text-muted mb-4">Atalhos centrais do sistema para operação e suporte.</p>

    <div class="row">
        <div class="col-md-6 mb-3">
            <h5 class="mb-2">Self-Service</h5>
            <div class="d-flex flex-column">
                <a class="btn btn-outline-primary btn-sm text-left mb-2" href="selfservice.php" target="_blank">
                    <i class="fas fa-user-plus mr-1"></i>Cadastro público
                </a>
                <a class="btn btn-outline-secondary btn-sm text-left mb-2" href="instal.html" target="_blank">
                    <i class="fas fa-life-ring mr-1"></i>Página de instalação
                </a>
                <a class="btn btn-outline-info btn-sm text-left" href="diag_email.php" target="_blank">
                    <i class="fas fa-stethoscope mr-1"></i>Diagnóstico de e-mail
                </a>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <h5 class="mb-2">Ferramentas</h5>
            <div class="d-flex flex-column">
                <a class="btn btn-outline-dark btn-sm text-left mb-2" href="../qrcode/default.php" target="_blank">
                    <i class="fas fa-qrcode mr-1"></i>Gerador de QR Code
                </a>
                <a class="btn btn-outline-success btn-sm text-left" href="../ebi/template/index.php" target="_blank">
                    <i class="fas fa-child mr-1"></i>EBI template (base)
                </a>
            </div>
        </div>
    </div>
</div>

<!-- How To — QZ Tray -->
<div class="card table-custom p-4 mt-3">
    <h4 class="mb-3"><i class="fas fa-print mr-2"></i>How To — QZ Tray (Impressão)</h4>
    <p class="text-muted small mb-3">Passo a passo para configurar a impressão silenciosa via QZ Tray em todos os desktops.</p>

    <div class="row">
        <div class="col-md-7">
            <ol class="small">
                <li class="mb-2"><strong>Instale o QZ Tray</strong> em cada desktop → <a href="https://github.com/qzind/tray/releases/download/v2.2.5/qz-tray-2.2.5-x86_64.exe" target="_blank">QZ Tray 2.2.5 x64 (.exe)</a></li>
                <li class="mb-2"><strong>Baixe o certificado</strong> → <a href="<?php echo $qzCertificateDownload; ?>">Baixar certificado (.zip)</a>
                    <br>Após baixar, descompacte o arquivo no desktop. Você precisará do certificado na próxima etapa.
                </li>
                <li class="mb-2"><strong>Instale o certificado baixado</strong>:
                    <br>Clique no ícone do QZ Tray ao lado do relógio, no canto inferior direito.
                    <br><code>Advanced → Site Manager → "+" → Browse</code>
                    <br>Localize o arquivo descompactado e selecione <code>digital-certificate.txt</code>.
                </li>
                <li class="mb-2"><strong>Reinicie o QZ Tray</strong> em cada máquina: clique no ícone do QZ Tray e escolha <strong>Reload</strong>.</li>
            </ol>
        </div>
        <div class="col-md-5">
            <div class="card bg-light p-3">
                <h6 class="mb-2"><i class="fas fa-link mr-1"></i> Links úteis</h6>
                <a href="https://qz.io/download/" target="_blank" class="d-block mb-2 small">
                    <i class="fas fa-download mr-1"></i> Download QZ Tray
                </a>
                <a href="<?php echo $qzCertificateDownload; ?>" class="d-block mb-2 small">
                    <i class="fas fa-shield-alt mr-1"></i> Baixar certificado (.zip)
                </a>
                <a href="https://github.com/qzind/tray/releases/download/v2.2.5/qz-tray-2.2.5-x86_64.exe" target="_blank" class="d-block mb-2 small">
                    <i class="fas fa-windows mr-1"></i> QZ Tray 2.2.5 x64 (.exe)
                </a>
                <hr class="my-2">
                <h6 class="mb-2"><i class="fas fa-shield-alt mr-1"></i> Verificação</h6>
                <p class="small text-muted mb-2">Use este teste somente se a impressão não funcionar. Uma sequência codificada confirma que o servidor está pronto para assinar impressões.</p>
                <a href="<?php echo $qzSigningBaseUrl; ?>sign-message.php?request=teste" target="_blank" class="d-block small">
                    <i class="fas fa-check-circle mr-1"></i> Testar assinatura
                </a>
            </div>
        </div>
    </div>
</div>
