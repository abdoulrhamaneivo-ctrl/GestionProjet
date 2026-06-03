<?php
$projectId = (int)($project['id_projet'] ?? 0);
$isGroupProject = !empty($project['id_groupe']);
$authorLabel = $isGroupProject ? (($project['nom_groupe'] ?? '') ?: 'Groupe ' . (int)$project['id_groupe']) : 'Soumission individuelle';
$gradeMode = ($mode ?? 'criteria') === 'global' ? 'global' : 'criteria';
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour aux corrections
        </a>
        <h1 class="fs-3 mb-1 mt-2">Evaluer le livrable academique</h1>
        <p class="text-secondary mb-0">Application : <strong><?php echo $h($project['titre_projet'] ?? 'Projet'); ?></strong></p>
    </div>
</div>

<div class="card mb-4 border-0 shadow-sm" style="background: #e7f4ec; border-left: 5px solid var(--emsp-green) !important;">
    <div class="card-body p-4">
        <h2 class="fs-5 mb-3 text-success fw-bold"><i class="bi bi-sliders me-2"></i>Choisissez votre méthode d'évaluation</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <a href="<?php echo htmlspecialchars(url('prof/grade?id=' . $projectId . '&mode=criteria'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
                    <div class="card h-100 border transition-all hover-shadow <?php echo $gradeMode === 'criteria' ? 'border-primary bg-white' : 'bg-light border-light'; ?>" style="border-width: 2px !important; transition: all 0.2s ease-in-out;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="fs-6 fw-bold mb-0 <?php echo $gradeMode === 'criteria' ? 'text-success' : 'text-dark'; ?>">
                                    <i class="bi bi-grid-3x3-gap me-2"></i>Notation par critères
                                </h3>
                                <?php if ($gradeMode === 'criteria'): ?>
                                    <span class="badge text-bg-primary">Actif</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-secondary small mb-0">
                                Évaluez le projet selon trois critères distincts : Design (/5), Qualité du code (/5) et Fonctionnel (/10). La note globale sur 20 est calculée automatiquement.
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6">
                <a href="<?php echo htmlspecialchars(url('prof/grade?id=' . $projectId . '&mode=global'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">
                    <div class="card h-100 border transition-all hover-shadow <?php echo $gradeMode === 'global' ? 'border-primary bg-white' : 'bg-light border-light'; ?>" style="border-width: 2px !important; transition: all 0.2s ease-in-out;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h3 class="fs-6 fw-bold mb-0 <?php echo $gradeMode === 'global' ? 'text-success' : 'text-dark'; ?>">
                                    <i class="bi bi-trophy me-2"></i>Notation globale directe
                                </h3>
                                <?php if ($gradeMode === 'global'): ?>
                                    <span class="badge text-bg-primary">Actif</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-secondary small mb-0">
                                Saisissez directement une note globale sur 20. Recommandé pour des corrections rapides ou des appréciations d'ensemble.
                            </p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="form-text text-muted mt-3">
            <i class="bi bi-info-circle me-1"></i> La note saisie restera enregistrée en tant que <strong>Brouillon</strong> jusqu'à ce que vous décidiez de publier les notes de cet exercice sur le tableau de bord.
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-secondary">Devoir</div>
                <div class="fw-semibold"><?php echo $h($project['exercice_titre'] ?? 'Exercice'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-secondary">Auteur</div>
                <div class="fw-semibold">
                    <?php echo $h($authorLabel); ?>
                    <?php if ($isGroupProject): ?>
                        <span class="badge text-bg-primary ms-1">Projet groupe</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isGroupProject && (!empty($project['chef_prenom']) || !empty($project['chef_nom']))): ?>
                <div class="col-md-6">
                    <div class="small text-secondary">Chef de groupe</div>
                    <div class="fw-semibold"><?php echo $h(trim(($project['chef_prenom'] ?? '') . ' ' . ($project['chef_nom'] ?? ''))); ?></div>
                </div>
            <?php endif; ?>
            <div class="col-md-6">
                <div class="small text-secondary">URL</div>
                <a href="<?php echo $h($project['lien_url'] ?? '#'); ?>" target="_blank" rel="noopener"><?php echo $h($project['lien_url'] ?? 'Lien absent'); ?></a>
            </div>
            <div class="col-12">
                <div class="small text-secondary">Codes d'acces fournis</div>
                <code><?php echo $h($project['acces_test'] ?? ''); ?></code>
            </div>
            <?php if (!empty($project['explications'])): ?>
                <div class="col-12">
                    <hr>
                    <div class="small text-secondary mb-1">Notice de l'etudiant</div>
                    <div><?php echo nl2br($h($project['explications'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<form action="<?php echo htmlspecialchars(url('prof/grade'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
    <input type="hidden" name="id_projet" value="<?php echo $projectId; ?>">
    <input type="hidden" name="mode" value="<?php echo $h($gradeMode); ?>">

    <div class="card mb-4">
        <div class="card-body p-4">
            <?php if ($gradeMode === 'criteria'): ?>
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h2 class="fs-5 mb-0">Notation avec criteres detailles</h2>
                    <span class="badge text-bg-light">Total /20</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="note_design" class="form-label">Design /5</label>
                        <input type="number" step="0.25" min="0" max="5" id="note_design" name="note_design" class="form-control text-center" value="<?php echo $project['note_design'] !== null ? (float)$project['note_design'] : '0.00'; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="note_code" class="form-label">Code /5</label>
                        <input type="number" step="0.25" min="0" max="5" id="note_code" name="note_code" class="form-control text-center" value="<?php echo $project['note_code'] !== null ? (float)$project['note_code'] : '0.00'; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="note_fonc" class="form-label">Fonctionnel /10</label>
                        <input type="number" step="0.25" min="0" max="10" id="note_fonc" name="note_fonc" class="form-control text-center" value="<?php echo $project['note_fonc'] !== null ? (float)$project['note_fonc'] : '0.00'; ?>" required>
                    </div>
                </div>
                <div class="alert alert-primary mt-3 mb-0">
                    Total calcule : <strong id="total_display">0.00</strong> / 20
                </div>
            <?php else: ?>
                <h2 class="fs-5 mb-3">Notation globale directe</h2>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="note_globale" class="form-label">Note sur 20</label>
                        <input type="number" step="0.25" min="0" max="20" id="note_globale" name="note_globale" class="form-control text-center" value="<?php echo $project['note_totale'] !== null ? (float)$project['note_totale'] : '0.00'; ?>" required>
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info mb-0">Ce mode enregistre uniquement une note finale. Les champs design, code et fonctionnel ne sont pas demandes.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body p-4">
            <label for="critique_prof" class="form-label">Critique pedagogique</label>
            <textarea id="critique_prof" name="critique_prof" rows="5" class="form-control" placeholder="Points forts, limites, corrections conseillees..."><?php echo $project['critique_prof'] ? $h($project['critique_prof']) : ''; ?></textarea>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="statut_public" name="statut_public" value="1" <?php echo ((int)($project['statut_public'] ?? 0) === 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="statut_public">
                    Publier ce projet dans l'espace communautaire
                </label>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
        <a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle me-1"></i> Enregistrer en brouillon
        </button>
    </div>
</form>

<?php if ($gradeMode === 'criteria'): ?>
<script>
(() => {
    const designInput = document.getElementById('note_design');
    const codeInput = document.getElementById('note_code');
    const foncInput = document.getElementById('note_fonc');
    const totalDisplay = document.getElementById('total_display');

    function calculateTotal() {
        const design = parseFloat(designInput.value) || 0;
        const code = parseFloat(codeInput.value) || 0;
        const fonc = parseFloat(foncInput.value) || 0;
        totalDisplay.textContent = (design + code + fonc).toFixed(2);
    }

    [designInput, codeInput, foncInput].forEach((field) => field.addEventListener('input', calculateTotal));
    calculateTotal();
})();
</script>
<?php endif; ?>
