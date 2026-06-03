<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour aux corrections
        </a>
        <h1 class="fs-3 mb-1 mt-2">Accorder une derogation</h1>
        <p class="text-secondary mb-0">Exercice : <strong><?php echo $h($exercise['titre']); ?></strong></p>
    </div>
</div>

<div class="alert alert-warning">
    Choisissez soit un etudiant individuel, soit un groupe, puis indiquez une nouvelle date limite future.
</div>

<div class="card">
    <div class="card-body p-4">
        <form action="<?php echo htmlspecialchars(url('prof/exercise/add-exemption'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
            <input type="hidden" name="id_exercice" value="<?php echo (int) $exercise['id_exercice']; ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="id_user_target" class="form-label">Etudiant individuel</label>
                    <select id="id_user_target" name="id_user_target" class="form-select">
                        <option value="">-- Aucun --</option>
                        <?php foreach (($students ?? []) as $student): ?>
                            <option value="<?php echo (int) $student['id_user']; ?>">
                                <?php echo $h($student['prenom'] . ' ' . $student['nom'] . ' - ' . $student['email']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="id_group_target" class="form-label">Groupe</label>
                    <select id="id_group_target" name="id_group_target" class="form-select">
                        <option value="">-- Aucun --</option>
                        <?php foreach (($groups ?? []) as $group): ?>
                            <option value="<?php echo (int) $group['id_groupe']; ?>">
                                <?php echo $h($group['nom_groupe'] . ' - chef : ' . $group['chef_prenom'] . ' ' . $group['chef_nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="nouvelle_date" class="form-label">Nouvelle date limite</label>
                    <input type="datetime-local" id="nouvelle_date" name="nouvelle_date" class="form-control" required>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?php echo htmlspecialchars(url('prof/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-clock-history me-1"></i> Enregistrer la derogation
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body p-4">
        <h2 class="fs-5 mb-2">Derogations actives</h2>
        <p class="text-secondary small">Liste des prolongations de delai accordees pour cet exercice.</p>
        <?php if (empty($derogations)): ?>
            <div class="text-center text-secondary py-3 border rounded bg-light small">
                Aucune derogation active.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle small mb-0">
                    <thead>
                        <tr>
                            <th>Cible</th>
                            <th>Nouvelle Date</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($derogations as $dero): ?>
                            <tr>
                                <td>
                                    <?php if ($dero['id_user']): ?>
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($dero['user_prenom'] . ' ' . $dero['user_nom'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php elseif ($dero['id_groupe']): ?>
                                        <i class="bi bi-people me-1"></i><?php echo htmlspecialchars($dero['nom_groupe'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($dero['nouvelle_date'])); ?></td>
                                <td class="text-end">
                                    <form action="<?php echo htmlspecialchars(url('prof/exercise/exemption/delete'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="id_exercice" value="<?php echo (int)$exercise['id_exercice']; ?>">
                                        <input type="hidden" name="id_derogation" value="<?php echo (int)$dero['id_derogation']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
