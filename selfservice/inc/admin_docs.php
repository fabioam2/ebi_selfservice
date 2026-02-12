<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação - Self-Service</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .navbar-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .sidebar { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; max-height: calc(100vh - 100px); overflow-y: auto; position: sticky; top: 20px; }
        .doc-content { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; min-height: calc(100vh - 100px); }
        .doc-item { padding: 10px 15px; margin: 5px 0; border-radius: 5px; cursor: pointer; transition: all 0.2s; color: #333; text-decoration: none; display: block; }
        .doc-item:hover { background-color: #f0f0f0; text-decoration: none; color: #333; }
        .doc-item.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .doc-item i { margin-right: 10px; width: 20px; }
        h1, h2, h3, h4 { margin-top: 1.5rem; margin-bottom: 1rem; font-weight: 600; }
        h1 { border-bottom: 3px solid #667eea; padding-bottom: 10px; color: #667eea; }
        h2 { border-bottom: 2px solid #ddd; padding-bottom: 8px; }
        code { background-color: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; color: #e83e8c; }
        pre { background-color: #f6f8fa; border: 1px solid #ddd; border-radius: 5px; padding: 15px; overflow-x: auto; }
        pre code { background: none; padding: 0; color: inherit; }
        table { width: 100%; margin: 20px 0; border-collapse: collapse; }
        table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        table th { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: 600; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        blockquote { border-left: 4px solid #667eea; padding-left: 15px; margin: 20px 0; color: #666; font-style: italic; background-color: #f8f9fa; padding: 10px 15px; border-radius: 5px; }
        .alert-box { padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid; }
        .alert-box.info { background-color: #d1ecf1; border-color: #0c5460; }
        .alert-box.warning { background-color: #fff3cd; border-color: #856404; }
        .alert-box.success { background-color: #d4edda; border-color: #155724; }
        .alert-box.danger { background-color: #f8d7da; border-color: #721c24; }
        a { color: #667eea; text-decoration: none; }
        a:hover { color: #764ba2; text-decoration: underline; }
        .doc-search { margin-bottom: 20px; }
        .doc-search input { border-radius: 20px; }
        ul, ol { padding-left: 25px; }
        li { margin: 8px 0; }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="?">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
            <span class="navbar-text text-white">
                <i class="fas fa-book"></i> Documentação Self-Service v3.0
            </span>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <h5 class="mb-3"><i class="fas fa-list"></i> Documentos</h5>

                    <div class="doc-search">
                        <input type="text" id="searchDocs" class="form-control" placeholder="🔍 Buscar documentos...">
                    </div>

                    <div id="docList">
                        <?php if (empty($documentos)): ?>
                            <p class="text-muted text-center">
                                <i class="fas fa-folder-open fa-2x mb-2"></i><br>
                                Nenhum documento encontrado
                            </p>
                        <?php else: ?>
                            <?php foreach ($documentos as $doc): ?>
                                <a href="?page=docs&doc=<?php echo urlencode($doc); ?>"
                                   class="doc-item <?php echo $doc === $nomeDoc ? 'active' : ''; ?>"
                                   data-doc-name="<?php echo strtolower($doc); ?>">
                                    <?php
                                    $ext = pathinfo($doc, PATHINFO_EXTENSION);
                                    $icon = $ext === 'md' ? 'fa-file-alt' : 'fa-file-code';

                                    // Ícones específicos por nome
                                    if (stripos($doc, 'README') !== false) {
                                        $icon = 'fa-home';
                                    } elseif (stripos($doc, 'INSTALL') !== false || stripos($doc, 'INSTALACAO') !== false) {
                                        $icon = 'fa-download';
                                    } elseif (stripos($doc, 'SECURITY') !== false || stripos($doc, 'SEGURANCA') !== false) {
                                        $icon = 'fa-shield-alt';
                                    } elseif (stripos($doc, 'EXEMPLO') !== false) {
                                        $icon = 'fa-code';
                                    } elseif (stripos($doc, 'CHANGELOG') !== false || stripos($doc, 'MUDANCAS') !== false) {
                                        $icon = 'fa-list-ul';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars(str_replace(['_', '.md', '.txt'], [' ', '', ''], $doc)); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo count($documentos); ?> documento(s)
                        </small>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="col-md-9">
                <div class="doc-content">
                    <?php if ($conteudoDoc): ?>
                        <!-- Cabeçalho do documento -->
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1">
                                        <i class="fas fa-file-alt text-primary mr-2"></i>
                                        <?php echo htmlspecialchars(str_replace(['_', '.md', '.txt'], [' ', '', ''], $nomeDoc)); ?>
                                    </h4>
                                    <small class="text-muted">
                                        <?php
                                        $filePath = $docDir . $nomeDoc;
                                        if (file_exists($filePath)) {
                                            echo 'Última modificação: ' . date('d/m/Y H:i', filemtime($filePath));
                                        }
                                        ?>
                                    </small>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                        <i class="fas fa-print mr-1"></i>Imprimir
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="copiarConteudo()">
                                        <i class="fas fa-copy mr-1"></i>Copiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Conteúdo do documento -->
                        <div id="docContent">
                            <?php echo $conteudoDoc; ?>
                        </div>

                        <!-- Navegação entre documentos -->
                        <?php if (count($documentos) > 1): ?>
                            <div class="mt-5 pt-4 border-top">
                                <div class="row">
                                    <?php
                                    $currentIndex = array_search($nomeDoc, $documentos);
                                    $prevDoc = $currentIndex > 0 ? $documentos[$currentIndex - 1] : null;
                                    $nextDoc = $currentIndex < count($documentos) - 1 ? $documentos[$currentIndex + 1] : null;
                                    ?>

                                    <div class="col-md-6">
                                        <?php if ($prevDoc): ?>
                                            <a href="?page=docs&doc=<?php echo urlencode($prevDoc); ?>" class="btn btn-outline-primary btn-block">
                                                <i class="fas fa-arrow-left mr-2"></i>
                                                <?php echo htmlspecialchars(str_replace(['_', '.md', '.txt'], [' ', '', ''], $prevDoc)); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6">
                                        <?php if ($nextDoc): ?>
                                            <a href="?page=docs&doc=<?php echo urlencode($nextDoc); ?>" class="btn btn-outline-primary btn-block text-right">
                                                <?php echo htmlspecialchars(str_replace(['_', '.md', '.txt'], [' ', '', ''], $nextDoc)); ?>
                                                <i class="fas fa-arrow-right ml-2"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Estado vazio -->
                        <div class="text-center text-muted" style="padding: 100px 0;">
                            <i class="fas fa-book-open" style="font-size: 5rem; margin-bottom: 30px; color: #667eea;"></i>
                            <h3>Selecione um Documento</h3>
                            <p>Escolha um documento da lista ao lado para visualizar seu conteúdo.</p>

                            <?php if (empty($documentos)): ?>
                                <hr class="my-4">
                                <div class="alert alert-info d-inline-block text-left">
                                    <h5><i class="fas fa-info-circle mr-2"></i>Nenhum documento encontrado</h5>
                                    <p class="mb-0">
                                        Os documentos devem estar no diretório:<br>
                                        <code><?php echo $docDir; ?></code>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        // Highlight de código
        hljs.highlightAll();

        // Busca de documentos
        $('#searchDocs').on('keyup', function() {
            const value = $(this).val().toLowerCase();

            $('.doc-item').filter(function() {
                const docName = $(this).data('doc-name') || $(this).text().toLowerCase();
                $(this).toggle(docName.indexOf(value) > -1);
            });
        });

        // Copiar conteúdo
        function copiarConteudo() {
            const content = document.getElementById('docContent');
            if (content) {
                const text = content.innerText;

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('✅ Conteúdo copiado para a área de transferência!');
                    }).catch(() => {
                        alert('❌ Erro ao copiar conteúdo');
                    });
                } else {
                    alert('❌ Navegador não suporta cópia automática');
                }
            }
        }

        // Smooth scroll para links internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Adicionar ícones a links externos
        document.querySelectorAll('.doc-content a[href^="http"]').forEach(link => {
            if (!link.querySelector('i')) {
                link.innerHTML += ' <i class="fas fa-external-link-alt fa-xs"></i>';
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    </script>
</body>
</html>
