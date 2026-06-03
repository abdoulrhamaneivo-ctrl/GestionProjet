<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/exercises'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour a la liste
        </a>
        <h1 class="fs-3 mb-1 mt-2"><?php echo htmlspecialchars($title ?? 'Formulaire exercice', ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-secondary mb-0">Modifiez les informations principales de l'exercice.</p>
    </div>
</div>

<?php if ($exercise && !empty($exercise['archive'])): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3">
        <div>
            <strong>Exercice archive.</strong>
            Les etudiants et enseignants ne le voient plus dans leurs listes actives.
        </div>
        <form action="<?php echo htmlspecialchars(url('admin/exercise/archive'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="archiveExerciseForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="id_exercice" value="<?php echo (int) $exercise['id_exercice']; ?>">
                <button type="button" class="btn btn-sm btn-outline-dark" data-form-id="archiveExerciseForm" data-confirm-message="Desarchiver cet exercice ?">Desarchiver</button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form action="<?php echo htmlspecialchars(url($exercise ? 'admin/exercises/edit' : 'admin/exercise/create'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($exercise): ?>
                <input type="hidden" name="id_exercice" value="<?php echo (int) $exercise['id_exercice']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="titre" class="form-label">Titre de l'exercice</label>
                <input type="text" id="titre" name="titre" class="form-control" required value="<?php echo htmlspecialchars($exercise['titre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="type_exercice" class="form-label">Type d'exercice</label>
                    <select id="type_exercice" name="type_exercice" class="form-select">
                        <option value="individuel" <?php echo (isset($exercise['type_exercice']) && $exercise['type_exercice'] === 'individuel') ? 'selected' : ''; ?>>Individuel</option>
                        <option value="groupe" <?php echo (isset($exercise['type_exercice']) && $exercise['type_exercice'] === 'groupe') ? 'selected' : ''; ?>>Groupe</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="date_limite" class="form-label">Date limite</label>
                    <?php if ($exercise && !empty($exercise['date_limite'])): ?>
                        <div class="alert alert-info py-2 px-3 mb-2">Date limite actuelle : <strong><?php echo date('d/m/Y H:i', strtotime($exercise['date_limite'])); ?></strong></div>
                    <?php endif; ?>
                    <?php
                        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
                        $minDatetime = $now->format('Y-m-d\TH:i');
                        $defaultValue = isset($exercise['date_limite']) ? date('Y-m-d\TH:i', strtotime($exercise['date_limite'])) : $minDatetime;
                    ?>
                    <input type="datetime-local" id="date_limite" name="date_limite" class="form-control" required value="<?php echo htmlspecialchars($defaultValue, ENT_QUOTES, 'UTF-8'); ?>" min="<?php echo htmlspecialchars($minDatetime, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <?php if ($exercise && !empty($themes)): ?>
                <div class="mt-3">
                    <label class="form-label">Themes existants</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($themes as $theme): ?>
                            <span class="badge text-bg-light border"><?php echo htmlspecialchars($theme['nom_theme'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mt-3">
                <label for="professeur_id" class="form-label">Professeur responsable</label>
                <select id="professeur_id" name="professeur_id" class="form-select">
                    <option value="">-- Aucun professeur assigne --</option>
                    <?php foreach (($professors ?? []) as $prof): ?>
                        <option value="<?php echo (int) $prof['id_user']; ?>" <?php echo (isset($exercise['professeur_id']) && (int) $exercise['professeur_id'] === (int) $prof['id_user']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?php echo htmlspecialchars(url('admin/exercises'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <?php echo $exercise ? 'Enregistrer les modifications' : 'Creer l\'exercice'; ?>
                </button>
            </div>
        </form>
    </div>
</div>
