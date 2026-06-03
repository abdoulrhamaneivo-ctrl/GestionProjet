<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="small">&larr; Retour administration</a>
        <h1 class="h2 mt-2 mb-1">Publier un nouvel exercice</h1>
        <p class="text-muted mb-0">Configurez une session de soumission de projet d'evaluation.</p>
    </div>
</div>

<form action="<?php echo htmlspecialchars(url('admin/exercise/create'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Informations du devoir</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12">
                    <label for="titre" class="form-label">Intitule du devoir</label>
                    <input type="text" id="titre" name="titre" required class="form-control" placeholder="Ex: Projet Pratique PHP & MVC EMSP">
                </div>

                <div class="col-md-6">
                    <label for="type_exercice" class="form-label">Type de participation</label>
                    <select id="type_exercice" name="type_exercice" class="form-select">
                        <option value="individuel">Individuel - depot unitaire par eleve</option>
                        <option value="groupe">Groupe / binome - depot par chef</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="date_limite" class="form-label">Date et heure limites</label>
                    <input type="datetime-local" id="date_limite" name="date_limite" required min="<?php echo date('Y-m-d\TH:i'); ?>" class="form-control">
                    <div class="form-text">La date limite doit etre dans le futur.</div>
                </div>

                <div class="col-12">
                    <label for="themes_list" class="form-label">Themes optionnels</label>
                    <textarea id="themes_list" name="themes_list" rows="3" class="form-control" placeholder="Ex: Systeme E-commerce, Portail de vote, Gestionnaire de stock"></textarea>
                    <div class="form-text">Separez les themes par des virgules. Laissez vide si aucun theme n'est impose.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Professeur responsable</h2>
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label for="professeur_id" class="form-label">Choisir un professeur existant</label>
                    <select id="professeur_id" name="professeur_id" class="form-select">
                        <option value="">-- Aucun / choisir plus tard --</option>
                        <?php foreach (($professors ?? []) as $prof): ?>
                            <option value="<?php echo (int) $prof['id_user']; ?>">
                                <?php echo htmlspecialchars($prof['prenom'] . ' ' . $prof['nom'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-6">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="create_new_prof" name="create_new_prof">
                        <label class="form-check-label" for="create_new_prof">Creer un nouveau professeur pour cet exercice</label>
                    </div>
                </div>
            </div>

            <div id="new-prof-fields" class="border rounded p-3 mt-3 bg-light d-none">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nom</label>
                        <input type="text" name="new_prof_nom" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Prenom</label>
                        <input type="text" name="new_prof_prenom" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="new_prof_email" class="form-control">
                    </div>
                </div>
                <div class="form-text mt-2">Un mot de passe aleatoire sera genere et affiche apres creation.</div>
            </div>
        </div>
    </div>

    <div id="designated-chefs-block" class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">Chefs de groupe designes</h2>
            <span class="badge text-bg-primary">exercice groupe</span>
        </div>
        <div class="card-body">
            <p class="text-muted small">Selectionnez les etudiants autorises a former un groupe et deposer le rendu.</p>
            <div class="row g-2" style="max-height: 260px; overflow:auto;">
                <?php foreach (($students ?? []) as $stu): ?>
                    <div class="col-md-6 col-xl-4">
                        <label class="border rounded p-2 d-flex gap-2 align-items-center h-100">
                            <input type="checkbox" name="chef_ids[]" value="<?php echo (int) $stu['id_user']; ?>" class="form-check-input m-0">
                            <span class="small"><?php echo htmlspecialchars($stu['prenom'] . ' ' . $stu['nom'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2-circle"></i> Publier l'exercice
        </button>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const createNewProf = document.getElementById('create_new_prof');
    const newProfFields = document.getElementById('new-prof-fields');
    const typeSelect = document.getElementById('type_exercice');
    const chefsBlock = document.getElementById('designated-chefs-block');

    function syncProfFields() {
        newProfFields.classList.toggle('d-none', !createNewProf.checked);
    }

    function syncChefBlock() {
        chefsBlock.classList.toggle('d-none', typeSelect.value !== 'groupe');
    }

    createNewProf.addEventListener('change', syncProfFields);
    typeSelect.addEventListener('change', syncChefBlock);
    syncProfFields();
    syncChefBlock();
});
</script>
