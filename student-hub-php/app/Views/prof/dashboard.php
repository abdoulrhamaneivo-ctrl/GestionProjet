<div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="fs-3 mb-1">Espace correcteur & enseignant</h1>
        <p class="text-secondary mb-0">Lisez les informations, consultez les soumissions, puis evaluez quand le dossier est clair.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?php echo htmlspecialchars(url('prof/exercises/export-all-grades'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-emsp">
            <i class="bi bi-file-earmark-text me-1"></i> Exporter toutes les notes
        </a>
    </div>
</div>

<?php $resetResult = \App\Core\Session::get('reset_password_result'); ?>
<?php if ($resetResult): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <strong>Nouveau mot de passe :</strong>
        <?php echo htmlspecialchars($resetResult['email'], ENT_QUOTES, 'UTF-8'); ?> /
        <code><?php echo htmlspecialchars($resetResult['password'], ENT_QUOTES, 'UTF-8'); ?></code>
    </div>
    <?php \App\Core\Session::remove('reset_password_result'); ?>
<?php endif; ?>

<?php $publishPrompt = \App\Core\Session::get('pending_publish_prompt'); ?>
<?php if (is_array($publishPrompt) && !empty($publishPrompt['id_exercice'])): ?>
    <div class="modal fade" id="publishPromptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5">Publier les notes de l'exercice ?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">
                        La correction vient d'etre enregistree en brouillon pour
                        <strong><?php echo $h($publishPrompt['titre'] ?? 'cet exercice'); ?></strong>.
                        Vous pouvez publier maintenant les notes deja corrigees de cet exercice, ou garder le brouillon.
                    </p>
                    <div class="alert alert-light border mb-0">
                        Publier rend les notes visibles aux etudiants et active leurs fiches PDF.
                    </div>
                </div>
                <div class="modal-footer d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
                    <form action="<?php echo htmlspecialchars(url('prof/publish-prompt/dismiss'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="me-sm-auto">
                        <input type="hidden" name="csrf_token" value="<?php echo $h(\App\Core\Csrf::generateToken()); ?>">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remember_publish_choice" id="dismissRememberPublish">
                            <label class="form-check-label small" for="dismissRememberPublish">Ne plus afficher ce rappel pendant cette session</label>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary">Garder en brouillon</button>
                    </form>
                    <form action="<?php echo htmlspecialchars(url('prof/publish-notes'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $h(\App\Core\Csrf::generateToken()); ?>">
                        <input type="hidden" name="id_exercice" value="<?php echo (int)$publishPrompt['id_exercice']; ?>">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="remember_publish_choice" id="publishRememberPublish">
                            <label class="form-check-label small" for="publishRememberPublish">Ne plus afficher ce rappel pendant cette session</label>
                        </div>
                        <button type="submit" class="btn btn-emsp-yellow">Publier les notes maintenant</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var modalEl = document.getElementById('publishPromptModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    </script>
<?php endif; ?>

<?php if (!empty($passwordRequests)): ?>
<div class="card mb-4 border-warning">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fs-5 mb-0">Demandes de reinitialisation</h2>
            <span class="badge text-bg-warning"><?php echo count($passwordRequests); ?> en attente</span>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Compte</th><th>Role</th><th>Message</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($passwordRequests as $req): ?>
                    <tr>
                        <td><strong><?php echo $h($req['prenom'] . ' ' . $req['nom']); ?></strong><div class="small text-secondary"><?php echo $h($req['email']); ?></div></td>
                        <td><span class="badge text-bg-light"><?php echo $h($req['role']); ?></span></td>
                        <td class="small"><?php echo $h($req['message'] ?: 'Aucun message'); ?></td>
                        <td class="text-end">
                            <form action="<?php echo htmlspecialchars(url('prof/password-reset/resolve'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $h(\App\Core\Csrf::generateToken()); ?>">
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

<?php if (empty($exercises)): ?>
    <div class="card"><div class="card-body p-5 text-center text-secondary">Aucun exercice actif ne vous est assigne.</div></div>
<?php else: ?>
<div class="card">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h2 class="fs-5 mb-1">Tableau des exercices</h2>
                <p class="small text-secondary mb-0">Les exercices les plus recents sont affiches en premier. Utilisez Stats pour les graphiques, Soumissions pour les rendus.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Exercice</th>
                        <th>Limite</th>
                        <th>Type</th>
                        <th>Soumis</th>
                        <th>Corriges</th>
                        <th>Attente</th>
                        <th>Reste</th>
                        <th>Progression</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($exercises as $item): ?>
                    <?php
                        $ex = $item['exercice'];
                        $subs = $item['submissions'];
                        $missing = $item['missing'] ?? ['groups' => [], 'individuals' => []];
                        $statsModalId = 'profStats' . (int)$ex['id_exercice'];
                        $subsModalId = 'profSubmissions' . (int)$ex['id_exercice'];
                    ?>
                    <tr>
                        <td><strong><?php echo $h($ex['titre']); ?></strong></td>
                        <td class="small"><?php echo date('d/m/Y H:i', strtotime($ex['date_limite'])); ?></td>
                        <td><span class="badge text-bg-<?php echo $ex['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>"><?php echo $h($ex['type_exercice']); ?></span></td>
                        <td><?php echo (int)$item['total_submissions']; ?></td>
                        <td><?php echo (int)$item['noted_count']; ?></td>
                        <td><?php echo (int)$item['pending_count']; ?></td>
                        <td><?php echo (int)$item['not_submitted_count']; ?></td>
                        <td style="min-width: 130px;">
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: <?php echo (int)$item['completion']; ?>%"></div>
                            </div>
                            <small class="text-secondary"><?php echo (int)$item['completion']; ?>%</small>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $statsModalId; ?>">Voir statistiques</button>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $subsModalId; ?>">Voir soumissions</button>
                                <form action="<?php echo htmlspecialchars(url('prof/publish-notes'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo $h(\App\Core\Csrf::generateToken()); ?>">
                                    <input type="hidden" name="id_exercice" value="<?php echo (int)$ex['id_exercice']; ?>">
                                    <button type="submit" class="btn btn-sm btn-emsp-yellow" <?php echo (int)$item['noted_count'] === 0 ? 'disabled' : ''; ?>>
                                        Publier les notes
                                    </button>
                                </form>
                                <a class="btn btn-sm btn-outline-success" href="<?php echo htmlspecialchars(url('prof/exercise/export-grades?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>">Exporter les notes de l'exercice</a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars(url('prof/exercise/add-exemption?id_exercice=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>">Accorder une derogation</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach ($exercises as $item): ?>
    <?php
        $ex = $item['exercice'];
        $subs = $item['submissions'];
        $missing = $item['missing'] ?? ['groups' => [], 'individuals' => []];
        $statsModalId = 'profStats' . (int)$ex['id_exercice'];
        $subsModalId = 'profSubmissions' . (int)$ex['id_exercice'];
    ?>

    <div class="modal fade" id="<?php echo $statsModalId; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Statistiques : <?php echo $h($ex['titre']); ?></h5>
                        <div class="small text-secondary">Graphiques et indicateurs uniquement.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-5">
                            <canvas height="260" data-emsp-chart='<?php echo htmlspecialchars(json_encode(['labels' => ['Corriges', 'En attente', 'Non soumis', 'En retard'], 'series' => [(int)$item['noted_count'], (int)$item['pending_count'], (int)$item['not_submitted_count'], (int)$item['late_count']]]), ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                        </div>
                        <div class="col-md-7">
                            <div class="list-group">
                                <div class="list-group-item d-flex justify-content-between"><span>Soumissions recues</span><strong><?php echo (int)$item['total_submissions']; ?></strong></div>
                                <div class="list-group-item d-flex justify-content-between"><span>Corrections terminees</span><strong><?php echo (int)$item['noted_count']; ?></strong></div>
                                <div class="list-group-item d-flex justify-content-between"><span>En attente</span><strong><?php echo (int)$item['pending_count']; ?></strong></div>
                                <div class="list-group-item d-flex justify-content-between"><span>Non soumis</span><strong><?php echo (int)$item['not_submitted_count']; ?></strong></div>
                                <div class="list-group-item d-flex justify-content-between"><span>En retard</span><strong><?php echo (int)$item['late_count']; ?></strong></div>
                                <div class="list-group-item d-flex justify-content-between"><span>Progression</span><strong><?php echo (int)$item['completion']; ?>%</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button class="btn btn-primary" data-bs-target="#<?php echo $subsModalId; ?>" data-bs-toggle="modal">Voir les soumissions</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="<?php echo $subsModalId; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Soumissions : <?php echo $h($ex['titre']); ?></h5>
                        <div class="small text-secondary">Cliquez sur Detail pour lire les informations avant evaluation.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead><tr><th>Auteur</th><th>Projet</th><th>CDC</th><th>Date</th><th>Note</th><th class="text-end">Action</th></tr></thead>
                            <tbody>
                            <?php if (empty($subs)): ?>
                                <tr><td colspan="6" class="text-center text-secondary py-4">Aucune soumission.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($subs as $sub): ?>
                                <?php $detailModalId = 'submissionDetail' . (int)$sub['id_projet']; ?>
                                <tr>
                                    <td>
                                        <?php if ($sub['id_groupe']): ?>
                                            <strong><?php echo $h($sub['nom_groupe']); ?></strong>
                                            <div class="small text-secondary">Chef : <?php echo $h($sub['chef_prenom'] . ' ' . $sub['chef_nom']); ?></div>
                                        <?php else: ?>
                                            <strong><?php echo $h($sub['ind_prenom'] . ' ' . $sub['ind_nom']); ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $h($sub['titre_projet']); ?><div class="small text-secondary"><?php echo $h($sub['nom_theme'] ?: 'Theme libre'); ?></div></td>
                                    <td>
                                        <?php if (!empty($sub['cahier_charges_path'])): ?>
                                            <button class="btn btn-sm btn-emsp-yellow" data-bs-toggle="modal" data-bs-target="#submissionPdf<?php echo (int)$sub['id_projet']; ?>">
                                                <i class="bi bi-file-earmark-pdf me-1"></i> Lire
                                            </button>
                                        <?php else: ?>
                                            <span class="badge text-bg-light">Aucun CDC</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small"><?php echo date('d/m/Y H:i', strtotime($sub['date_soumission'])); ?><?php if (!empty($sub['is_late'])): ?><span class="badge text-bg-danger ms-1">Retard</span><?php endif; ?></td>
                                    <td><?php echo $sub['note_totale'] !== null ? number_format((float)$sub['note_totale'], 2) . '/20' : 'En attente'; ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?php echo $detailModalId; ?>">Detail</button>
                                        <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars(url('prof/grade?id=' . $sub['id_projet']), ENT_QUOTES, 'UTF-8'); ?>">Evaluer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($missing['groups'])): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <strong>Groupes sans rendu :</strong>
                            <?php echo implode(', ', array_map(static fn ($g) => htmlspecialchars(($g['nom_groupe'] ?: 'Sans nom'), ENT_QUOTES, 'UTF-8'), $missing['groups'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php foreach ($subs as $sub): ?>
        <?php $detailModalId = 'submissionDetail' . (int)$sub['id_projet']; $pdfModalId = 'submissionPdf' . (int)$sub['id_projet']; ?>
        <div class="modal fade" id="<?php echo $detailModalId; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title"><?php echo $h($sub['titre_projet']); ?></h5>
                            <div class="small text-secondary"><?php echo $h($ex['titre']); ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <dl class="row">
                            <dt class="col-sm-3">Auteur</dt>
                            <dd class="col-sm-9"><?php echo $sub['id_groupe'] ? $h($sub['nom_groupe'] . ' - chef : ' . $sub['chef_prenom'] . ' ' . $sub['chef_nom']) : $h($sub['ind_prenom'] . ' ' . $sub['ind_nom']); ?></dd>
                            <dt class="col-sm-3">Theme</dt>
                            <dd class="col-sm-9"><?php echo $h($sub['nom_theme'] ?: 'Theme libre'); ?></dd>
                            <dt class="col-sm-3">Lien test</dt>
                            <dd class="col-sm-9"><a href="<?php echo $h($sub['lien_url']); ?>" target="_blank" rel="noopener"><?php echo $h($sub['lien_url']); ?></a></dd>
                            <dt class="col-sm-3">Acces test</dt>
                            <dd class="col-sm-9"><code><?php echo $h($sub['acces_test']); ?></code></dd>
                            <dt class="col-sm-3">Notice</dt>
                            <dd class="col-sm-9"><?php echo nl2br($h($sub['explications'] ?: 'Aucune notice.')); ?></dd>
                        </dl>
                        <?php if (!empty($sub['cahier_charges_path'])): ?>
                            <div class="alert emsp-soft d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div>
                                    <strong><i class="bi bi-file-earmark-pdf me-1"></i>Cahier des charges disponible</strong>
                                    <div class="small text-secondary">A lire avant l'evaluation pour comprendre le besoin, le perimetre et les consignes du projet.</div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn btn-sm btn-emsp-yellow" data-bs-toggle="modal" data-bs-target="#<?php echo $pdfModalId; ?>">Lire le CDC</button>
                                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars(url($sub['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" download>Telecharger</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <a class="btn btn-outline-primary" href="<?php echo $h($sub['lien_url']); ?>" target="_blank" rel="noopener">Visiter le site</a>
                            <a class="btn btn-primary" href="<?php echo htmlspecialchars(url('prof/grade?id=' . $sub['id_projet']), ENT_QUOTES, 'UTF-8'); ?>">Evaluer maintenant</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($sub['cahier_charges_path'])): ?>
            <div class="modal fade" id="<?php echo $pdfModalId; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cahier des charges - <?php echo $h($sub['titre_projet']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-0">
                            <iframe data-pdf-src="<?php echo htmlspecialchars(url($sub['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="720" title="Cahier des charges" loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
