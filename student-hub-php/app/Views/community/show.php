<?php $pdfModalId = 'projectPdf' . (int)$project['id_projet']; ?>

<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour a la communaute
        </a>
        <h1 class="fs-3 mt-2 mb-1"><?php echo $h($project['titre_projet']); ?></h1>
        <p class="text-secondary mb-0"><?php echo $h($project['exercice_titre']); ?></p>
    </div>
    <span class="badge text-bg-<?php echo !empty($project['id_groupe']) ? 'primary' : 'warning'; ?>">
        <?php echo !empty($project['id_groupe']) ? 'Projet groupe' : 'Projet individuel'; ?>
    </span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-3">Informations du projet</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Auteur</dt>
                    <dd class="col-sm-9"><?php echo $h($project['nom_groupe'] ?: trim(($project['ind_prenom'] ?? '') . ' ' . ($project['ind_nom'] ?? ''))); ?></dd>
                    <?php if (!empty($project['chef_prenom']) || !empty($project['chef_nom'])): ?>
                        <dt class="col-sm-3">Chef</dt>
                        <dd class="col-sm-9"><?php echo $h(trim(($project['chef_prenom'] ?? '') . ' ' . ($project['chef_nom'] ?? ''))); ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-3">Theme</dt>
                    <dd class="col-sm-9"><?php echo $h($project['nom_theme'] ?: 'Libre'); ?></dd>
                    <dt class="col-sm-3">Date limite</dt>
                    <dd class="col-sm-9"><?php echo date('d/m/Y H:i', strtotime($project['date_limite'])); ?></dd>
                    <dt class="col-sm-3">Notice</dt>
                    <dd class="col-sm-9"><?php echo nl2br($h($project['explications'] ?: 'Aucune notice fournie.')); ?></dd>
                </dl>
                <?php if (!empty($project['cahier_charges_path'])): ?>
                    <div class="alert emsp-soft d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-3">
                        <div>
                            <strong><i class="bi bi-file-earmark-pdf me-1"></i>Cahier des charges du projet</strong>
                            <div class="small text-secondary">Lisez le besoin avant de tester ou commenter.</div>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-emsp-yellow" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $pdfModalId; ?>">Lire le CDC</button>
                            <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(url($project['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" download>Telecharger</a>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a class="btn btn-emsp" href="<?php echo $h($project['lien_url']); ?>" target="_blank" rel="noopener">Tester le site</a>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" id="commentaires">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-3">
                    <i class="bi bi-chat-dots me-1 text-success"></i>
                    Commentaires et reponses
                </h2>
                <?php if (empty($comments)): ?>
                    <div class="text-secondary border rounded p-4 bg-light">Aucun commentaire pour le moment.</div>
                <?php else: ?>
                    <div class="list-group mb-4">
                        <?php foreach ($comments as $comment): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>
                                        <?php echo $h($comment['pseudonyme'] ?: $comment['nom_visiteur'] ?: trim(($comment['user_prenom'] ?? '') . ' ' . ($comment['user_nom'] ?? '')) ?: 'Visiteur'); ?>
                                    </strong>
                                    <small class="text-secondary"><?php echo date('d/m/Y H:i', strtotime($comment['date_publication'])); ?></small>
                                </div>
                                <p class="mb-2"><?php echo nl2br($h($comment['contenu'])); ?></p>

                                <?php foreach (($comment['replies'] ?? []) as $reply): ?>
                                    <div class="border-start border-primary ps-3 mt-3">
                                        <div class="d-flex justify-content-between gap-3">
                                            <strong><?php echo $h($reply['pseudonyme'] ?: trim(($reply['user_prenom'] ?? '') . ' ' . ($reply['user_nom'] ?? ''))); ?></strong>
                                            <span class="badge text-bg-primary">Responsable</span>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br($h($reply['contenu'])); ?></p>
                                    </div>
                                <?php endforeach; ?>

                                <?php if ($isResponsible): ?>
                                    <form action="<?php echo htmlspecialchars(url('community/reply'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="mt-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
                                        <input type="hidden" name="id_projet" value="<?php echo (int)$project['id_projet']; ?>">
                                        <input type="hidden" name="parent_id" value="<?php echo (int)$comment['id_commentaire']; ?>">
                                        <div class="input-group">
                                            <input class="form-control" name="contenu" maxlength="1200" placeholder="Repondre comme responsable du projet" required>
                                            <button class="btn btn-outline-primary" type="submit">Repondre</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-3">Laisser un avis</h2>
                <form action="<?php echo htmlspecialchars(url('community/comment'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
                    <input type="hidden" name="id_projet" value="<?php echo (int)$project['id_projet']; ?>">
                    <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
                    <div class="mb-3">
                        <label for="nom_visiteur" class="form-label">Pseudonyme</label>
                        <input class="form-control" id="nom_visiteur" name="nom_visiteur" maxlength="50" value="<?php echo $user ? $h($user['prenom'] . ' ' . $user['nom']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Commentaire</label>
                        <textarea class="form-control" id="contenu" name="contenu" rows="5" maxlength="1200" required></textarea>
                    </div>
                    <button class="btn btn-emsp w-100" type="submit">Publier le commentaire</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($project['cahier_charges_path'])): ?>
    <div class="modal fade" id="<?php echo $pdfModalId; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cahier des charges - <?php echo $h($project['titre_projet']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe data-pdf-src="<?php echo htmlspecialchars(url($project['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="720" title="Cahier des charges" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
