<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="fs-3 mb-1">Panneau de supervision administrative</h1>
        <p class="text-secondary mb-0">Suivi des exercices, rendus, corrections, comptes et demandes d'acces.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?php echo htmlspecialchars(url('admin/exercise/create'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Publier exercice
        </a>
        <a href="<?php echo htmlspecialchars(url('admin/students/import'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light">
            <i class="bi bi-upload me-1"></i> Importer
        </a>
    </div>
</div>

<?php $resetResult = \App\Core\Session::get('reset_password_result'); ?>
<?php if ($resetResult): ?>
    <div class="modal fade" id="resetPasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau mot de passe genere</h5>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning mb-3">Notez ce mot de passe maintenant, il ne sera plus affiche ulterieurement.</div>
                    <div class="mb-2">
                        <label class="form-label">Compte</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($resetResult['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div class="input-group">
                        <input type="password" class="form-control" id="resetPasswordField" value="<?php echo htmlspecialchars($resetResult['password'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordVisibility">Afficher</button>
                        <button class="btn btn-outline-primary" type="button" id="copyPasswordBtn">Copier</button>
                    </div>
                    <div class="form-text text-danger mt-2" id="copyFeedback"></div>
                </div>
                <div class="modal-footer">
                    <form action="<?php echo htmlspecialchars(url('admin/reset-password/dismiss'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="dismissResetForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $h(\App\Core\Csrf::generateToken()); ?>">
                        <button type="submit" class="btn btn-primary">J'ai note</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET'): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('resetPasswordModal');
            if (!modal) return;
            var instance = bootstrap.Modal.getOrCreateInstance(modal, {backdrop: 'static', keyboard: false});
            instance.show();
        });
        </script>
    <?php endif; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var field = document.getElementById('resetPasswordField');
        var toggleBtn = document.getElementById('togglePasswordVisibility');
        var copyBtn = document.getElementById('copyPasswordBtn');
        var feedback = document.getElementById('copyFeedback');

        if (toggleBtn && field) {
            toggleBtn.addEventListener('click', function () {
                if (field.type === 'password') {
                    field.type = 'text';
                    toggleBtn.textContent = 'Masquer';
                } else {
                    field.type = 'password';
                    toggleBtn.textContent = 'Afficher';
                }
            });
        }

        if (copyBtn && field && feedback) {
            copyBtn.addEventListener('click', function () {
                var password = field.value || '';
                if (!navigator.clipboard || !navigator.clipboard.writeText) {
                    feedback.textContent = 'Copie automatique indisponible dans ce navigateur.';
                    feedback.className = 'form-text text-danger mt-2';
                    return;
                }
                navigator.clipboard.writeText(password).then(function () {
                    copyBtn.textContent = 'Copie !';
                    copyBtn.classList.remove('btn-outline-primary');
                    copyBtn.classList.add('btn-success');
                    feedback.textContent = 'Mot de passe copie dans le presse-papier.';
                    feedback.className = 'form-text text-success mt-2';
                    setTimeout(function () {
                        copyBtn.textContent = 'Copier';
                        copyBtn.classList.remove('btn-success');
                        copyBtn.classList.add('btn-outline-primary');
                        feedback.textContent = '';
                        feedback.className = 'form-text text-danger mt-2';
                    }, 2000);
                }).catch(function () {
                    feedback.textContent = 'Erreur lors de la copie.';
                    feedback.className = 'form-text text-danger mt-2';
                });
            });
        }
    });
    </script>
    <?php \App\Core\Session::remove('reset_password_result'); ?>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4"><h6 class="mb-3">Exercices actifs</h6><h3 class="fw-bold mb-0"><?php echo (int)($stats['active_exercises'] ?? 0); ?></h3></div></div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4"><h6 class="mb-3">Soumissions</h6><h3 class="fw-bold mb-0"><?php echo (int)($stats['total_submissions'] ?? 0); ?></h3></div></div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4"><h6 class="mb-3">Etudiants</h6><h3 class="fw-bold mb-0"><?php echo count($students); ?></h3></div></div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card h-100"><div class="card-body p-4"><h6 class="mb-3">Demandes acces</h6><h3 class="fw-bold mb-0"><?php echo count($passwordRequests ?? []); ?></h3></div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="fs-5 mb-1">Actions administratives</h2>
                <p class="text-secondary small mb-0">Acces direct aux anciens modules de gestion.</p>
            </div>
        </div>
        <div class="row g-2">
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-primary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/exercises'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-journal-text me-1"></i> Exercices
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-primary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/exercise/create'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-plus-lg me-1"></i> Publier
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-primary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-people me-1"></i> Etudiants
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-primary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/students/import'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-upload me-1"></i> Import
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-secondary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/professors'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-person-badge me-1"></i> Professeurs
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-secondary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/admins'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-shield-lock me-1"></i> Admins
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-secondary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/students/import/download'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-file-earmark-text me-1"></i> Comptes TXT
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a class="btn btn-outline-secondary w-100 text-start" href="<?php echo htmlspecialchars(url('admin/exercises/export-all-grades'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="bi bi-file-earmark-text me-1"></i> Export notes
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($passwordRequests)): ?>
<div class="card mb-4 border-warning">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-5 mb-0">Demandes de reinitialisation</h2>
            <span class="badge text-bg-warning"><?php echo count($passwordRequests); ?> en attente</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Compte</th><th>Role</th><th>Message</th><th>Date</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($passwordRequests as $req): ?>
                    <tr>
                        <td><strong><?php echo $h($req['prenom'] . ' ' . $req['nom']); ?></strong><div class="small text-secondary"><?php echo $h($req['email']); ?></div></td>
                        <td><span class="badge text-bg-light"><?php echo $h($req['role']); ?></span></td>
                        <td class="small"><?php echo $h($req['message'] ?: 'Aucun message'); ?></td>
                        <td class="small text-secondary"><?php echo date('d/m/Y H:i', strtotime($req['date_demande'])); ?></td>
                        <td class="text-end">
                            <form action="<?php echo htmlspecialchars(url('admin/password-reset/resolve'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\App\Core\Csrf::generateToken(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="id_request" value="<?php echo (int) $req['id_request']; ?>">
                                <button class="btn btn-sm btn-primary" type="submit">Reinitialiser</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-5 mb-0">Exercices actifs</h2>
            <a href="<?php echo htmlspecialchars(url('admin/exercises/export-all-grades'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-light">
                <i class="bi bi-file-earmark-text me-1"></i> Export global TXT
            </a>
        </div>
        <div class="row g-3">
            <?php foreach ($active_exercises as $ex): ?>
                <?php $st = $exerciseStats[(int)$ex['id_exercice']] ?? null; if (!$st) continue; ?>
                <?php $modalId = 'adminExercise' . (int)$ex['id_exercice']; ?>
                <?php $progress = (int)($st['completion'] ?? 0); ?>
                <div class="col-12 col-xl-6">
                    <div class="card stat-card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-bg-<?php echo $ex['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>">Projet <?php echo $h($ex['type_exercice']); ?></span>
                                    <strong class="me-2"><?php echo $h($ex['titre']); ?></strong>
                                </div>
                                <div class="d-none d-md-block small text-secondary">
                                    <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y H:i', strtotime($ex['date_limite'])); ?>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="progress flex-grow-1" style="height: 6px;" title="<?php echo $st['submitted']; ?> soumis, <?php echo $st['noted']; ?> corriges, <?php echo $st['pending']; ?> en attente, <?php echo $st['not_submitted']; ?> reste">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="small text-nowrap"><?php echo $progress; ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <small class="text-secondary">
                                    <span class="d-none d-md-inline"><?php echo $st['noted']; ?> corriges</span>
                                    <span class="d-md-none"><?php echo $progress; ?>%</span>
                                </small>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="actionsDropdown<?php echo (int)$ex['id_exercice']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="actionsDropdown<?php echo (int)$ex['id_exercice']; ?>">
                                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars(url('admin/exercise/export-grades?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>">Notes</a></li>
                                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars(url('admin/group/manage?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>">Equipes / derogations</a></li>
                                        <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $modalId; ?>">Voir stats</button></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div><h5 class="modal-title"><?php echo $h($ex['titre']); ?></h5><div class="small text-secondary">Etat complet des soumissions</div></div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <canvas height="240" data-emsp-chart='<?php echo htmlspecialchars(json_encode(['labels' => ['Corriges', 'En attente', 'Non soumis', 'En retard'], 'series' => [$st['noted'], $st['pending'], $st['not_submitted'], $st['late']]]), ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="submission-list table-responsive">
                                            <table class="table table-sm align-middle">
                                                <thead><tr><th>Auteur</th><th>Projet</th><th>Date</th><th>Note</th><th>Statut</th></tr></thead>
                                                <tbody>
                                                <?php if (empty($st['submissions'])): ?>
                                                    <tr><td colspan="5" class="text-center text-secondary py-4">Aucune soumission.</td></tr>
                                                <?php endif; ?>
                                                <?php foreach ($st['submissions'] as $sub): ?>
                                                    <tr>
                                                        <td><?php echo $h($sub['author_label']); ?></td>
                                                        <td><?php echo $h($sub['titre_projet']); ?></td>
                                                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($sub['date_soumission'])); ?><?php if (!empty($sub['is_late'])): ?><span class="badge text-bg-danger ms-1">Retard</span><?php endif; ?></td>
                                                        <td><?php echo $sub['note_totale'] !== null ? number_format((float)$sub['note_totale'], 2) . '/20' : 'En attente'; ?></td>
                                                        <td>
                                                            <?php if ($sub['note_totale'] !== null): ?>
                                                                <span class="badge text-bg-success">Corrige</span>
                                                            <?php else: ?>
                                                                <span class="badge text-bg-warning">A corriger par le professeur</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
