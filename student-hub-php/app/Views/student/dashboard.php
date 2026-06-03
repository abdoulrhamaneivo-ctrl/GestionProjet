<section class="features-icons bg-light text-center py-4">
    <div class="container">
        <h1 class="mb-2">Bonjour, <?php echo $h($user['prenom']); ?></h1>
        <p class="lead mb-0">Consultez vos exercices, deposez vos projets et telechargez vos fiches publiees.</p>
    </div>
</section>

<div class="row g-4 mt-1">
    <?php foreach ($assignments as $a): ?>
        <?php
            $ex = $a['exercice'];
            $sub = $a['submitted'];
            $dl = $a['deadline_status'];
            $grp = $a['group'];
            $projectLocked = $sub && ($sub['note_totale'] !== null || (int) $sub['notes_publiees'] === 1);
            $isGroupAssignment = ($ex['type_exercice'] === 'groupe');
            $canManageGroupSubmission = true;
            if ($isGroupAssignment) {
                $canManageGroupSubmission = false;
                if ($grp && (int)($grp['id_chef'] ?? 0) === (int)$user['id_user']) {
                    $canManageGroupSubmission = true;
                } elseif (!$grp && \App\Models\Exercise::isDesignatedChef((int)$ex['id_exercice'], (int)$user['id_user'])) {
                    $canManageGroupSubmission = true;
                }
            }
            $canSubmit = !empty($dl['allowed']) && !$projectLocked && $canManageGroupSubmission;
            $disabledReason = $projectLocked
                ? 'Rendu verrouille'
                : (!$canManageGroupSubmission ? 'Reserve au chef de groupe' : 'Soumission fermee');
        ?>
        <div class="col-12 col-lg-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div>
                            <span class="badge text-bg-<?php echo $ex['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>">
                                Projet <?php echo $h($ex['type_exercice']); ?>
                            </span>
                            <h2 class="h5 mt-2 mb-1"><?php echo $h($ex['titre']); ?></h2>
                            <p class="small text-muted mb-0">
                                <i class="bi bi-calendar"></i>
                                Limite : <?php echo date('d/m/Y H:i', strtotime($ex['date_limite'])); ?>
                            </p>
                            <?php if (isset($dl['deadline']) && $dl['deadline'] !== $ex['date_limite']): ?>
                                <p class="small text-success mb-0">Derogation : <?php echo date('d/m/Y H:i', strtotime($dl['deadline'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="badge align-self-start text-bg-<?php echo $sub ? 'success' : 'secondary'; ?>">
                            <?php echo $sub ? 'Soumis' : 'Non soumis'; ?>
                        </span>
                    </div>

                    <?php if ($grp): ?>
                        <div class="alert alert-primary py-2 small">
                            Groupe : <strong><?php echo $h($grp['nom_groupe']); ?></strong><br>
                            Chef : <?php echo $h($grp['chef_prenom'] . ' ' . $grp['chef_nom']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($sub): ?>
                        <div class="border-top pt-3 small">
                            <p class="mb-1"><strong>Titre :</strong> <?php echo $h($sub['titre_projet']); ?></p>
                            <p class="mb-1"><strong>Theme :</strong> <?php echo $h($sub['nom_theme'] ?: 'Aucun'); ?></p>
                            <p class="mb-1"><strong>Lien :</strong> <a href="<?php echo $h($sub['lien_url']); ?>" target="_blank">Tester le site</a></p>
                            <?php if ($sub['cahier_charges_path']): ?>
                                <p class="mb-1"><strong>CDC :</strong> <a href="<?php echo $h($sub['cahier_charges_path']); ?>" target="_blank">Lire / telecharger PDF</a></p>
                            <?php endif; ?>

                            <?php if ((int) $sub['notes_publiees'] === 1 && $sub['note_totale'] !== null): ?>
                                <div class="alert alert-success mt-3 mb-0">
                                    <div class="d-flex justify-content-between">
                                        <strong>Note publiee</strong>
                                        <strong><?php echo number_format((float)$sub['note_totale'], 2); ?> / 20</strong>
                                    </div>
                                    <?php if (!empty($sub['critique_prof'])): ?>
                                        <div class="mt-2"><em><?php echo nl2br($h($sub['critique_prof'])); ?></em></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-light border mt-3 mb-0">Correction en attente.</div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-auto pt-3 d-flex gap-2">
                        <?php if ($canSubmit): ?>
                            <a href="<?php echo htmlspecialchars(url('student/submit?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary flex-fill">
                                <?php echo $sub ? 'Mettre a jour' : 'Deposer mon travail'; ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary flex-fill" disabled>
                                <?php echo $disabledReason; ?>
                            </button>
                        <?php endif; ?>
                        <?php if ($sub && (int) $sub['notes_publiees'] === 1 && $sub['note_totale'] !== null): ?>
                            <a href="<?php echo htmlspecialchars(url('student/project/report-pdf?id=' . (int) $sub['id_projet']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-success" title="Fiche PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($hasPublishedGrade): ?>
    <div class="text-end mt-4">
        <a href="<?php echo htmlspecialchars(url('student/report-pdf'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-success">
            <i class="bi bi-download"></i> Telecharger ma fiche complete
        </a>
    </div>
<?php endif; ?>
