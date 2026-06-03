<?php
$existingProject = !empty($existing);
$isLockedAfterGrade = $existingProject && (($existing['note_totale'] ?? null) !== null || (int)($existing['notes_publiees'] ?? 0) === 1);
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('student/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour au tableau de bord
        </a>
        <h1 class="fs-3 mb-1 mt-2"><?php echo $existingProject ? 'Mettre a jour mon rendu' : 'Soumettre un projet'; ?></h1>
        <p class="text-secondary mb-0">Exercice : <strong><?php echo $h($exercice['titre']); ?></strong></p>
    </div>
    <span class="badge text-bg-<?php echo $isGroup ? 'primary' : 'warning'; ?>">
        Projet <?php echo $isGroup ? 'groupe' : 'individuel'; ?>
    </span>
</div>

<?php if ($isLockedAfterGrade): ?>
    <div class="alert alert-info">Ce rendu est deja corrige ou publie. Il ne peut plus etre modifie.</div>
<?php endif; ?>

<form action="<?php echo htmlspecialchars(url('student/submit'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data" id="submitForm">
    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
    <input type="hidden" name="id_exercice" value="<?php echo (int) $exercice['id_exercice']; ?>">

    <?php if ($isGroup && $group === null && ($isChefAutorise ?? false)): ?>
        <div class="card mb-4 border-primary">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-2">Constitution de l'equipe</h2>
                <p class="text-secondary">Vous etes chef de groupe designe. Vous etes automatiquement le responsable du groupe, inutile de vous selectionner.</p>

                <div class="mb-3">
                    <label for="nom_groupe" class="form-label">Nom du groupe</label>
                    <input type="text" id="nom_groupe" name="nom_groupe" class="form-control" value="<?php echo isset($user) ? 'Groupe de ' . $h($user['prenom'] . ' ' . $user['nom']) : ''; ?>" required>
                </div>

                <div>
                    <label class="form-label">Equipiers disponibles</label>
                    <?php if (empty($availableMembers)): ?>
                        <div class="alert alert-light border mb-0">Aucun etudiant libre pour cet exercice.</div>
                    <?php else: ?>
                        <div class="border rounded p-3" style="max-height: 260px; overflow-y: auto;">
                            <?php foreach (($availableMembers ?? []) as $student): ?>
                                <?php $isBusy = !empty($student['deja_dans_groupe']); ?>
                                <?php $isChef = !empty($student['est_chef_designe']); ?>
                                <?php $isDisabled = $isBusy || $isChef; ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="group_members[]" id="student_<?php echo (int)$student['id_user']; ?>" value="<?php echo (int) $student['id_user']; ?>" <?php echo $isDisabled ? 'disabled' : ''; ?>>
                                    <label class="form-check-label <?php echo $isDisabled ? 'text-secondary' : ''; ?>" for="student_<?php echo (int)$student['id_user']; ?>">
                                        <?php echo $h($student['nom'] . ' ' . $student['prenom']); ?>
                                        <span class="small text-secondary">(<?php echo $h($student['email']); ?>)</span>
                                        <?php if ($isBusy): ?>
                                            <span class="badge text-bg-warning ms-1">Deja dans un groupe</span>
                                        <?php elseif ($isChef): ?>
                                            <span class="badge text-bg-info ms-1">Chef de groupe</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php elseif ($isGroup && $group !== null): ?>
        <div class="alert alert-primary">
            Rendu collectif pour <strong><?php echo $h($group['nom_groupe']); ?></strong>.
            Chef : <?php echo $h($group['chef_prenom'] . ' ' . $group['chef_nom']); ?>.
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body p-4">
            <h2 class="fs-5 mb-3">Details du projet</h2>

            <div class="mb-3">
                <label for="id_theme" class="form-label">Theme de projet</label>
                <select id="id_theme" name="id_theme" class="form-select">
                    <option value="">-- Choisir un theme optionnel --</option>
                    <?php foreach ($themes as $theme): ?>
                        <option value="<?php echo (int)$theme['id_theme']; ?>" <?php echo $existingProject && (int)$existing['id_theme'] === (int)$theme['id_theme'] ? 'selected' : ''; ?>>
                            <?php echo $h($theme['nom_theme']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="titre_projet" class="form-label">Titre de l'application</label>
                <input type="text" id="titre_projet" name="titre_projet" class="form-control" value="<?php echo $existingProject ? $h($existing['titre_projet']) : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label for="lien_url" class="form-label">URL de test</label>
                <input type="url" id="lien_url" name="lien_url" class="form-control" placeholder="https://..." value="<?php echo $existingProject ? $h($existing['lien_url']) : ''; ?>" required pattern="https://.*">
                <div class="invalid-feedback">L'URL doit commencer par https://</div>
            </div>

            <div class="mb-3">
                <label for="acces_test" class="form-label">Codes d'acces de test</label>
                <textarea id="acces_test" name="acces_test" rows="3" class="form-control" required><?php echo $existingProject ? $h($existing['acces_test']) : ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label for="explications" class="form-label">Notice explicative</label>
                <textarea id="explications" name="explications" rows="4" class="form-control"><?php echo $existingProject ? $h($existing['explications']) : ''; ?></textarea>
            </div>

            <div>
                <label for="cahier_charges" class="form-label">Cahier des charges PDF</label>
                <?php if ($existingProject && !empty($existing['cahier_charges_path'])): ?>
                    <div class="mb-2">
                        <a href="<?php echo htmlspecialchars(url($existing['cahier_charges_path']), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i> Ouvrir le PDF actuel
                        </a>
                    </div>
                <?php endif; ?>
                <input type="file" id="cahier_charges" name="cahier_charges" class="form-control" accept="application/pdf">
                <div class="form-text">PDF uniquement, 5 Mo maximum.</div>
                <div class="invalid-feedback" id="pdfSizeError"></div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo htmlspecialchars(url('student/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
        <?php if (!$isLockedAfterGrade): ?>
            <button type="submit" class="btn btn-primary" id="submitBtn" data-no-loading="1">
                <i class="bi bi-send me-1"></i> <?php echo $existingProject ? 'Mettre a jour le rendu' : 'Envoyer le rendu'; ?>
            </button>
        <?php endif; ?>
    </div>
</form>

<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la soumission</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Veuillez confirmer les informations avant envoi :</p>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item d-flex justify-content-between"><span>Titre</span><strong id="modalTitle"></strong></li>
                    <li class="list-group-item d-flex justify-content-between"><span>URL</span><a id="modalUrl" href="#" target="_blank"></a></li>
                    <li class="list-group-item d-flex justify-content-between"><span>PDF</span><span id="modalFile"></span></li>
                </ul>
                <div class="alert alert-warning py-2 small mb-0">Vous ne pourrez plus modifier ce rendu apres confirmation.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="modalCancelBtn" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="modalConfirmBtn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="confirmSpinner" role="status" aria-hidden="true"></span>
                    Confirmer l'envoi
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('submitForm');
    const submitBtn = document.getElementById('submitBtn');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    const cancelBtn = document.getElementById('modalCancelBtn');
    const confirmSpinner = document.getElementById('confirmSpinner');
    const lienUrlInput = document.getElementById('lien_url');
    const fileInput = document.getElementById('cahier_charges');
    const pdfSizeError = document.getElementById('pdfSizeError');
    const titreInput = document.getElementById('titre_projet');

    const modalTitle = document.getElementById('modalTitle');
    const modalUrl = document.getElementById('modalUrl');
    const modalFile = document.getElementById('modalFile');

    if (!form || !submitBtn) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const titre = titreInput ? titreInput.value.trim() : '';
        const url = lienUrlInput ? lienUrlInput.value.trim() : '';
        const file = fileInput.files[0];

        modalTitle.textContent = titre;
        if (url) {
            modalUrl.textContent = url.length > 40 ? url.slice(0, 37) + '...' : url;
            modalUrl.href = url;
            modalUrl.classList.remove('d-none');
        } else {
            modalUrl.textContent = '';
            modalUrl.href = '#';
            modalUrl.classList.add('d-none');
        }

        if (file) {
            const sizeMo = (file.size / 1048576).toFixed(2);
            modalFile.textContent = file.name + ' (' + sizeMo + ' Mo)';
        } else {
            modalFile.textContent = 'Aucun fichier';
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmSubmitModal')).show();
    });

    cancelBtn.addEventListener('click', function () {
        confirmSpinner.classList.add('d-none');
        confirmBtn.disabled = false;
        cancelBtn.disabled = false;
    });

    confirmBtn.addEventListener('click', function () {
        confirmSpinner.classList.remove('d-none');
        confirmBtn.disabled = true;
        cancelBtn.disabled = true;

        setTimeout(function () {
            form.removeEventListener('submit', null);
            form.submit();
        }, 400);
    });

    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) {
            this.classList.remove('is-invalid');
            pdfSizeError.textContent = '';
            return;
        }

        const sizeMo = file.size / 1048576;
        if (sizeMo > 5) {
            this.classList.add('is-invalid');
            pdfSizeError.textContent = 'Le fichier depasse 5 Mo (' + sizeMo.toFixed(2) + ' Mo).';
        } else {
            this.classList.remove('is-invalid');
            pdfSizeError.textContent = '';
        }
    });
});
</script>
