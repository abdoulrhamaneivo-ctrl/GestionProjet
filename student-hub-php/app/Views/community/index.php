<section class="emsp-hero rounded-3 p-4 p-lg-5 mb-4 shadow-sm">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <span class="badge rounded-pill text-bg-warning mb-3">Espace communautaire EMSP</span>
            <h1 class="display-6 fw-bold mb-3">Projets DSER publies</h1>
            <p class="lead mb-0">
                Consultez les realisations, lisez les cahiers des charges, testez les applications et laissez un retour utile aux responsables.
            </p>
        </div>
        <div class="col-lg-4">
            <div class="bg-white text-dark rounded-3 p-4 h-100 border border-warning-subtle">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary">Projets publics</span>
                    <strong class="fs-3 text-success"><?php echo count($projects); ?></strong>
                </div>
                <p class="small text-secondary mb-3">Les projets apparaissent apres echeance ou cloture de l'exercice.</p>
                <a class="btn btn-emsp-yellow w-100" href="#community-projects">Voir la galerie</a>
            </div>
        </div>
    </div>
</section>

<form class="card border-0 shadow-sm mb-4" method="GET" action="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3">
            <div class="flex-grow-1">
                <label class="form-label" for="exercise">Exercice</label>
                <input class="form-control" id="exercise" name="exercise" value="<?php echo $h($filters['exercise'] ?? ''); ?>" placeholder="Nom de l'exercice">
            </div>
            <div class="flex-grow-1">
                <label class="form-label" for="theme">Theme</label>
                <input class="form-control" id="theme" name="theme" value="<?php echo $h($filters['theme'] ?? ''); ?>" placeholder="Theme ou categorie">
            </div>
            <div style="min-width: 180px;">
                <label class="form-label" for="type">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">Tous</option>
                    <option value="groupe" <?php echo ($filters['type'] ?? '') === 'groupe' ? 'selected' : ''; ?>>Groupe</option>
                    <option value="individuel" <?php echo ($filters['type'] ?? '') === 'individuel' ? 'selected' : ''; ?>>Individuel</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-emsp" type="submit">
                    <i class="bi bi-search me-1" aria-hidden="true"></i> Rechercher
                </button>
                <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars(url('community'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
            </div>
        </div>
    </div>
</form>

<section id="community-projects">
<?php if (empty($projects)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-5 text-center">
            <span class="badge text-bg-warning mb-3">En attente de publication</span>
            <h2 class="fs-4">Aucun projet public pour le moment</h2>
            <p class="text-secondary mx-auto" style="max-width: 760px;">
                Les projets seront visibles ici apres la date limite ou apres cloture. Cette page sert de vitrine aux travaux DSER.
            </p>
            <?php if (empty($user)): ?>
                <a class="btn btn-emsp" href="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>">Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
        <?php foreach ($projects as $project): ?>
            <?php
                $projectId = (int)$project['id_projet'];
                $pdfModalId = 'pdfCommunity' . $projectId;
                $commentCount = \App\Models\Comment::countByProject($projectId);
            ?>
            <div class="col">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <span class="badge text-bg-<?php echo !empty($project['id_groupe']) ? 'success' : 'warning'; ?>">
                                <?php echo !empty($project['id_groupe']) ? 'Projet groupe' : 'Projet individuel'; ?>
                            </span>
                            <small class="text-secondary"><?php echo date('d/m/Y', strtotime($project['date_soumission'])); ?></small>
                        </div>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h2 class="h5 mb-2"><?php echo $h($project['titre_projet']); ?></h2>
                        <p class="small text-secondary mb-3"><?php echo $h($project['exercice_titre']); ?></p>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="small mb-1"><strong>Theme :</strong> <?php echo $h($project['nom_theme'] ?: 'Libre'); ?></div>
                            <div class="small"><strong>Auteur :</strong> <?php echo $h($project['nom_groupe'] ?: trim(($project['ind_prenom'] ?? '') . ' ' . ($project['ind_nom'] ?? ''))); ?></div>
                        </div>

                        <?php if (!empty($project['cahier_charges_path'])): ?>
                            <div class="alert emsp-soft py-2 px-3 mb-3">
                                <strong><i class="bi bi-file-earmark-pdf me-1"></i>CDC disponible</strong>
                                <div class="small text-secondary">Lecture directe ou telechargement.</div>
                            </div>
                        <?php endif; ?>

                        <div class="mt-auto d-grid gap-2">
                            <a class="btn btn-emsp" href="<?php echo htmlspecialchars(url('community/project?id=' . $projectId), ENT_QUOTES, 'UTF-8'); ?>">
                                Voir le projet complet
                            </a>
                            <a class="btn btn-emsp-yellow" href="<?php echo htmlspecialchars(url('community/project?id=' . $projectId), ENT_QUOTES, 'UTF-8'); ?>#commentaires">
                                <i class="bi bi-chat-dots me-1"></i>
                                Commentaires et reponses
                                <span class="badge text-bg-light ms-1"><?php echo $commentCount; ?></span>
                            </a>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-sm btn-outline-success flex-fill" href="<?php echo $h($project['lien_url']); ?>" target="_blank" rel="noopener">Tester</a>
                                <?php if (!empty($project['cahier_charges_path'])): ?>
                                    <button class="btn btn-sm btn-emsp-yellow flex-fill" type="button" data-bs-toggle="modal" data-bs-target="#<?php echo $pdfModalId; ?>">Lire CDC</button>
                                    <a class="btn btn-sm btn-outline-secondary flex-fill" href="<?php echo htmlspecialchars(url($project['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" download>Telecharger</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
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
                                <iframe data-pdf-src="<?php echo htmlspecialchars(url($project['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" width="100%" height="680" title="Cahier des charges" loading="lazy"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</section>
