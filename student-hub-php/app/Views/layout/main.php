<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title ?? 'EMSP Assignment Manager', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(url('template/brand/favicon.png'), ENT_QUOTES, 'UTF-8'); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,300italic,400italic,700italic" rel="stylesheet">
    <link href="<?php echo htmlspecialchars(url('template/bootstrap/bootstrap.min.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <link href="<?php echo htmlspecialchars(url('template/landing/css/styles.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php $layoutUser = \App\Core\Session::get('user'); ?>
    <?php if ($layoutUser !== null && in_array($layoutUser['role'], ['admin', 'prof'], true)): ?>
        <link href="<?php echo htmlspecialchars(url('template/dashboard/dashboard.css'), ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
    <?php endif; ?>
    <style>
        .app-brand.navbar-brand {
            background: transparent;
            box-shadow: none;
            padding-top: .35rem;
            padding-bottom: .35rem;
            max-width: 360px;
            min-width: 0;
        }
        .app-brand img {
            width: 42px;
            height: 42px;
            object-fit: contain;
            flex: 0 0 auto;
        }
        .app-brand span {
            min-width: 0;
            line-height: 1.15;
        }
        .app-brand small {
            white-space: nowrap;
        }
        @media (max-width: 575.98px) {
            .app-brand.navbar-brand {
                max-width: 220px;
            }
            .app-brand small {
                white-space: normal;
                font-size: .72rem;
            }
        }
        :root {
            --emsp-green: #0f7a3b;
            --emsp-green-dark: #07572a;
            --emsp-green-soft: #e7f4ec;
            --emsp-yellow: #f4c430;
            --emsp-yellow-dark: #c59b12;
            --emsp-yellow-soft: #fff6cf;
            --bs-primary: var(--emsp-green);
            --bs-primary-rgb: 15, 122, 59;
            --bs-link-color: var(--emsp-green);
            --bs-link-hover-color: var(--emsp-green-dark);
        }
        body {
            background: #f6faf7;
        }
        .navbar.bg-light,
        .footer.bg-light {
            background: #fff !important;
        }
        .navbar {
            border-top: 4px solid var(--emsp-green);
        }
        .navbar .container {
            min-height: 64px;
        }
        .app-brand span {
            color: var(--emsp-green-dark);
        }
        .nav-action {
            border-radius: 999px;
            font-weight: 700;
            padding: .55rem 1rem;
        }
        .nav-action.active {
            color: #17210f !important;
            background: var(--emsp-yellow) !important;
            border-color: var(--emsp-yellow) !important;
            box-shadow: 0 .5rem 1.25rem rgba(197, 155, 18, .18);
        }
        .btn-primary {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--emsp-green);
            --bs-btn-border-color: var(--emsp-green);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--emsp-green-dark);
            --bs-btn-hover-border-color: var(--emsp-green-dark);
            --bs-btn-active-bg: var(--emsp-green-dark);
            --bs-btn-active-border-color: var(--emsp-green-dark);
        }
        .btn-outline-primary,
        .btn-outline-success {
            --bs-btn-color: var(--emsp-green);
            --bs-btn-border-color: var(--emsp-green);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--emsp-green);
            --bs-btn-hover-border-color: var(--emsp-green);
            --bs-btn-active-color: #fff;
            --bs-btn-active-bg: var(--emsp-green-dark);
            --bs-btn-active-border-color: var(--emsp-green-dark);
        }
        .text-success {
            color: var(--emsp-green) !important;
        }
        .text-bg-primary,
        .text-bg-success {
            color: #fff !important;
            background-color: var(--emsp-green) !important;
        }
        .text-bg-warning {
            color: #1f2937 !important;
            background-color: var(--emsp-yellow) !important;
        }
        .progress-bar {
            background-color: var(--emsp-green);
        }
        .card {
            border-color: rgba(15, 122, 59, .12);
        }
        .btn-emsp {
            --bs-btn-color: #fff;
            --bs-btn-bg: var(--emsp-green);
            --bs-btn-border-color: var(--emsp-green);
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: var(--emsp-green-dark);
            --bs-btn-hover-border-color: var(--emsp-green-dark);
        }
        .btn-emsp-yellow {
            --bs-btn-color: #1f2937;
            --bs-btn-bg: var(--emsp-yellow);
            --bs-btn-border-color: var(--emsp-yellow);
            --bs-btn-hover-color: #111827;
            --bs-btn-hover-bg: var(--emsp-yellow-dark);
            --bs-btn-hover-border-color: var(--emsp-yellow-dark);
        }
        .emsp-hero {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(7, 87, 42, .94), rgba(15, 122, 59, .82)),
                url("<?php echo htmlspecialchars(url('template/brand/emsp-achievement-bg.jpg'), ENT_QUOTES, 'UTF-8'); ?>") center/cover no-repeat;
            color: #fff;
            border-bottom: 6px solid var(--emsp-yellow);
        }
        .emsp-hero::after {
            content: "";
            position: absolute;
            inset: auto -8rem -8rem auto;
            width: 22rem;
            height: 22rem;
            border-radius: 50%;
            background: rgba(244, 196, 48, .22);
            pointer-events: none;
        }
        .emsp-hero > * {
            position: relative;
            z-index: 1;
        }
        .emsp-soft {
            background: var(--emsp-yellow-soft);
            border-color: rgba(244, 196, 48, .5) !important;
        }
        .community-main {
            background:
                linear-gradient(180deg, rgba(246, 250, 247, .88), rgba(255, 255, 255, .95) 38%, rgba(246, 250, 247, .96)),
                url("<?php echo htmlspecialchars(url('template/landing/assets/img/bg-masthead.jpg'), ENT_QUOTES, 'UTF-8'); ?>") center top/cover fixed no-repeat;
        }
        .footer-emsp {
            color: rgba(255, 255, 255, .84);
            background:
                linear-gradient(135deg, rgba(7, 87, 42, .97), rgba(15, 122, 59, .94)),
                url("<?php echo htmlspecialchars(url('template/landing/assets/img/bg-showcase-1.jpg'), ENT_QUOTES, 'UTF-8'); ?>") center/cover no-repeat;
            border-top: 6px solid var(--emsp-yellow);
        }
        .footer-emsp a {
            color: #fff;
            text-decoration: none;
        }
        .footer-emsp a:hover {
            color: var(--emsp-yellow);
        }
        .footer-emsp .footer-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            background: #fff;
            border-radius: .75rem;
            padding: .35rem;
        }
    </style>
</head>
<body>
<?php
$currentUser = \App\Core\Session::get('user');
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptName = parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '/index.php';
$scriptBase = rtrim(dirname($scriptName), '/');
$baseLen = strlen($scriptBase);
if ($baseLen > 0 && strncmp($requestUri, $scriptBase, $baseLen) === 0) {
    $afterBase = substr($requestUri, $baseLen);
    $requestUri = ($afterBase === '' || $afterBase === false) ? '/' : $afterBase;
}
if ($requestUri !== '/' && substr($requestUri, -1) === '/') {
    $requestUri = rtrim($requestUri, '/');
}

function isNavActive(string $path, string $currentUri): bool
{
    if ($path === '/') {
        return $currentUri === '/';
    }
    return strpos($currentUri, $path) === 0;
}
?>

<nav class="navbar navbar-expand navbar-light bg-light static-top border-bottom">
    <div class="container">
        <a class="navbar-brand app-brand fw-bold d-flex align-items-center gap-2" href="<?php echo htmlspecialchars(url(''), ENT_QUOTES, 'UTF-8'); ?>">
            <img src="<?php echo htmlspecialchars(url('template/brand/logo-EMSP.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo EMSP">
            <span>
                DSER PROJECT
                <small class="d-block fw-normal text-muted">EMSP Assignment Manager</small>
            </span>
        </a>
        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-auto">
                <?php if ($currentUser !== null): ?>
                    <?php if ($currentUser['role'] === 'prof'): ?>
                        <a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action <?php echo isNavActive('/prof', $requestUri) ? 'active' : ''; ?>">Espace prof</a>
                    <?php elseif ($currentUser['role'] === 'etudiant'): ?>
                        <a href="<?php echo htmlspecialchars(url('student/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action <?php echo isNavActive('/student', $requestUri) ? 'active' : ''; ?>">Mon espace</a>
                        <a href="<?php echo htmlspecialchars(url('student/peer-gallery'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action <?php echo isNavActive('/student/peer-gallery', $requestUri) ? 'active' : ''; ?>">Peer-Testing</a>
                    <?php endif; ?>
                    <a href="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action <?php echo isNavActive('/community', $requestUri) || $requestUri === '/' ? 'active' : ''; ?>">Communaute</a>
                    <?php $unreadCount = \App\Models\Notification::unreadCount((int)$currentUser['id_user']); ?>
                    <a href="<?php echo htmlspecialchars(url('notifications'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action position-relative <?php echo isNavActive('/notifications', $requestUri) ? 'active' : ''; ?>" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <span class="d-none d-md-inline small text-muted"><?php echo htmlspecialchars($currentUser['prenom'] . ' ' . $currentUser['nom'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <a class="btn btn-primary btn-sm nav-action" href="<?php echo htmlspecialchars(url('auth/logout'), ENT_QUOTES, 'UTF-8'); ?>">Deconnexion</a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary btn-sm nav-action <?php echo isNavActive('/community', $requestUri) || $requestUri === '/' ? 'active' : ''; ?>">Communaute</a>
                    <a class="btn btn-primary btn-sm nav-action <?php echo isNavActive('/auth/login', $requestUri) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>">Connexion</a>
                <?php endif; ?>
        </div>
    </div>
</nav>

<main class="<?php echo $currentUser === null ? 'py-5 community-main' : 'py-4'; ?>">
    <div class="container">
        <?php echo $content; ?>
    </div>
</main>

<footer class="footer-emsp py-5">
    <div class="container">
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?php echo htmlspecialchars(url('template/brand/logo-EMSP.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Logo EMSP" class="footer-logo">
                    <div>
                        <strong class="d-block text-white fs-5">DSER PROJECT</strong>
                        <span class="small">EMSP Assignment Manager</span>
                    </div>
                </div>
                <p class="mb-0">
                    Plateforme academique de depot, correction, suivi et valorisation communautaire des projets DSER.
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="h6 text-white mb-3">Navigation</h2>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>">Communaute</a></li>
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>">Connexion</a></li>
                    <?php if ($currentUser !== null): ?>
                        <li><a href="<?php echo htmlspecialchars(url('notifications'), ENT_QUOTES, 'UTF-8'); ?>">Notifications</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h2 class="h6 text-white mb-3">Espaces</h2>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Administration</a></li>
                    <li class="mb-2"><a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Professeur</a></li>
                    <li><a href="<?php echo htmlspecialchars(url('student/dashboard'), ENT_QUOTES, 'UTF-8'); ?>">Etudiant</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h2 class="h6 text-white mb-3">EMSP Abidjan</h2>
                <p class="small mb-3">Club Informatique EMSP pour les projets DSER.</p>
                <span class="badge text-bg-warning">Vert & jaune EMSP</span>
            </div>
        </div>
        <hr class="border-light opacity-25 my-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small">
            <span>&copy; 2026 EMSP Abidjan. Tous droits reserves.</span>
        </div>
    </div>
</footer>

<?php $success_msg = \App\Core\Session::getFlash('success'); ?>
<?php $error_msg = \App\Core\Session::getFlash('error'); ?>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <?php if ($success_msg !== null): ?>
        <div class="toast align-items-center text-bg-success border-0" role="status" aria-live="polite" aria-atomic="true" data-bs-autohide="true" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($error_msg !== null): ?>
        <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="false">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-exclamation-triangle me-1"></i><?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fermer"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirmModalSubmit">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo htmlspecialchars(url('template/bootstrap/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $extra): ?>
        <?php if (empty($extra['src'])) continue; ?>
        <script src="<?php echo htmlspecialchars($extra['src'], ENT_QUOTES, 'UTF-8'); ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<script>
(function () {
    function initApp() {
        initToasts();
        initForms();
        initExtraScripts();
    }

    function initToasts() {
        if (typeof bootstrap === 'undefined') {
            showBootstrapFallbackAlert();
            return;
        }
        document.querySelectorAll('.toast').forEach(function (toastEl) {
            bootstrap.Toast.getOrCreateInstance(toastEl).show();
        });
    }

    function showBootstrapFallbackAlert() {
        var container = document.querySelector('.toast-container');
        if (!container) return;
        var fallback = document.createElement('div');
        fallback.className = 'alert alert-warning mb-0';
        fallback.textContent = 'Impossible de charger l interface dynamique (Bootstrap indisponible). Certains elements interactifs peuvent ne pas fonctionner.';
        container.appendChild(fallback);
        container.style.position = 'static';
        container.style.padding = '0';
        container.style.zIndex = 'auto';
    }

    function initForms() {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submitter = form.querySelector('button[type="submit"]');
                if (!submitter || submitter.dataset.noLoading === '1') {
                    return;
                }
                submitter.dataset.originalText = submitter.innerHTML;
                submitter.disabled = true;
                submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Traitement...';
            });
        });

        var confirmModalEl = document.getElementById('confirmModal');
        var confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
        var confirmMessageEl = document.getElementById('confirmModalMessage');
        var confirmSubmitBtn = document.getElementById('confirmModalSubmit');
        var currentFormId = null;

        document.querySelectorAll('[data-form-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var formId = btn.getAttribute('data-form-id');
                var message = btn.getAttribute('data-confirm-message') || 'Confirmez-vous cette action ?';
                var form = document.getElementById(formId);
                if (!form || !confirmModal) return;
                currentFormId = formId;
                confirmMessageEl.textContent = message;
                confirmModal.show();
            });
        });

        if (confirmSubmitBtn) {
            confirmSubmitBtn.addEventListener('click', function () {
                if (!currentFormId) return;
                var form = document.getElementById(currentFormId);
                if (!form) return;
                if (confirmModal) confirmModal.hide();
                form.submit();
            });
        }
    }

    function initExtraScripts() {
        if (typeof window.__EMSP_EXTRA_SCRIPTS_INITED__ !== 'undefined') {
            return;
        }
        window.__EMSP_EXTRA_SCRIPTS_INITED__ = true;
        initPdfLazyFrames();

        document.querySelectorAll('[data-emsp-chart]').forEach(function (canvas) {
            if (typeof Chart === 'undefined') {
                var fallback = document.createElement('div');
                fallback.className = 'alert alert-light border mb-0';
                fallback.textContent = 'Graphique indisponible, les chiffres restent visibles a droite.';
                canvas.replaceWith(fallback);
                return;
            }

            var data = JSON.parse(canvas.dataset.emspChart || '{}');
            new Chart(canvas, {
                type: 'doughnut',
                data: {
                    labels: data.labels || [],
                    datasets: [{
                        data: data.series || [],
                        backgroundColor: ['#198754', '#ffc107', '#6c757d', '#dc3545']
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        });
    }

    function initPdfLazyFrames() {
        document.querySelectorAll('.modal').forEach(function (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                loadPdfFrames(modalEl);
            });

            modalEl.addEventListener('shown.bs.modal', function () {
                loadPdfFrames(modalEl);
            });
        });
    }

    function loadPdfFrames(scope) {
        scope.querySelectorAll('iframe[data-pdf-src]').forEach(function (frame) {
            var pdfSrc = frame.getAttribute('data-pdf-src');
            if (pdfSrc && !frame.getAttribute('src')) {
                frame.setAttribute('src', pdfSrc);
            }
        });
    }

    initApp();
})();
</script>
</body>
</html>
