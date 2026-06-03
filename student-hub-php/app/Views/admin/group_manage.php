<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour a l'administration
        </a>
        <h1 class="fs-3 mb-1 mt-2">Groupes et derogations</h1>
        <p class="text-secondary mb-0">Exercice : <strong><?php echo htmlspecialchars($exercice['titre'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
    </div>
    <span class="badge text-bg-<?php echo $exercice['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>">
        Projet <?php echo htmlspecialchars($exercice['type_exercice'], ENT_QUOTES, 'UTF-8'); ?>
    </span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <?php if ($exercice['type_exercice'] === 'groupe'): ?>
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="fs-5 mb-3">Creer une equipe</h2>
                    <form action="<?php echo htmlspecialchars(url('admin/group/create'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="createGroupForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="id_exercice" value="<?php echo (int) $exercice['id_exercice']; ?>">

                        <div class="mb-3">
                            <label for="nom_groupe" class="form-label">Nom du groupe</label>
                            <input type="text" id="nom_groupe" name="nom_groupe" class="form-control" required placeholder="Ex: Groupe A">
                        </div>

                        <div class="mb-3">
                            <label for="id_chef" class="form-label">Chef de groupe</label>
                            <select id="id_chef" name="id_chef" class="form-select" required>
                                <option value="">-- Choisir un chef --</option>
                                <?php foreach ($designatedChefs as $stu): ?>
                                    <?php $isBusy = !empty($busyUserIds[$stu['id_user']]); ?>
                                    <option value="<?php echo (int)$stu['id_user']; ?>" <?php echo $isBusy ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($stu['nom'] . ' ' . $stu['prenom'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php echo $isBusy ? ' (deja dans un groupe)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($designatedChefs)): ?>
                                <div class="text-danger small mt-1">Aucun chef désigné pour cet exercice. Veuillez d'abord désigner des chefs lors de la création/modification de l'exercice.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Membres selectionnables</label>
                            <input type="text" id="memberSearch" class="form-control mb-2" placeholder="Rechercher un membre..." autocomplete="off">
                            <div class="border rounded p-3 bg-light" style="max-height: 230px; overflow-y: auto;" id="memberList">
                                <?php foreach ($students as $stu): ?>
                                    <?php $isBusy = !empty($busyUserIds[$stu['id_user']]); ?>
                                    <?php $isChef = !empty($designatedChefIds[$stu['id_user']]); ?>
                                    <?php $isDisabled = $isBusy || $isChef; ?>
                                    <div class="form-check member-item" data-query="<?php echo htmlspecialchars(strtolower($stu['nom'] . ' ' . $stu['prenom']), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input class="form-check-input member-check" type="checkbox" name="members[]" id="member_<?php echo (int)$stu['id_user']; ?>" value="<?php echo (int)$stu['id_user']; ?>" <?php echo $isDisabled ? 'disabled' : ''; ?>>
                                        <label class="form-check-label <?php echo $isDisabled ? 'text-secondary' : ''; ?>" for="member_<?php echo (int)$stu['id_user']; ?>">
                                            <?php echo htmlspecialchars($stu['nom'] . ' ' . $stu['prenom'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($isBusy): ?>
                                                <span class="badge text-bg-warning ms-1">Deja pris</span>
                                            <?php elseif ($isChef): ?>
                                                <span class="badge text-bg-info ms-1">Chef de groupe</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Le chef ne peut pas etre ajoute comme membre supplementaire.</div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" <?php echo empty($designatedChefs) ? 'disabled' : ''; ?>>
                            <i class="bi bi-people me-1"></i> Constituer l'equipe
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Exercice individuel : la creation de groupe est desactivee.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-2">Accorder une derogation</h2>
                <p class="text-secondary small">Allonge la date limite pour un etudiant ou un groupe.</p>
                <form action="<?php echo htmlspecialchars(url('admin/exemption/add'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id_exercice" value="<?php echo (int) $exercice['id_exercice']; ?>">

                    <div class="mb-3">
                        <label for="id_user_target" class="form-label">Etudiant</label>
                        <select id="id_user_target" name="id_user_target" class="form-select">
                            <option value="">-- Aucun etudiant --</option>
                            <?php foreach ($students as $stu): ?>
                                <option value="<?php echo (int)$stu['id_user']; ?>"><?php echo htmlspecialchars($stu['nom'] . ' ' . $stu['prenom'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($exercice['type_exercice'] === 'groupe'): ?>
                        <div class="mb-3">
                            <label for="id_group_target" class="form-label">Groupe</label>
                            <select id="id_group_target" name="id_group_target" class="form-select">
                                <option value="">-- Aucun groupe --</option>
                                <?php foreach ($groups as $gDetailed): ?>
                                    <?php $g = $gDetailed['group']; ?>
                                    <option value="<?php echo (int)$g['id_groupe']; ?>"><?php echo htmlspecialchars($g['nom_groupe'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nouvelle_date" class="form-label">Nouvelle date limite</label>
                        <input type="datetime-local" id="nouvelle_date" name="nouvelle_date" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-outline-primary w-100 mb-2">
                        <i class="bi bi-clock-history me-1"></i> Enregistrer
                    </button>
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
                                            <form action="<?php echo htmlspecialchars(url('admin/exemption/delete'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id_exercice" value="<?php echo (int)$exercice['id_exercice']; ?>">
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
    </div>

    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h2 class="fs-5 mb-1">Groupes constitues</h2>
                        <p class="text-secondary small mb-0"><?php echo count($groups); ?> groupe(s) pour cet exercice.</p>
                    </div>
                </div>

                <?php if (empty($groups)): ?>
                    <div class="text-center text-secondary py-5 border rounded bg-light">
                        Aucun groupe cree pour cet exercice.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($groups as $gDetailed): ?>
                            <?php $g = $gDetailed['group']; $members = $gDetailed['members']; ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <h3 class="fs-6 mb-1"><?php echo htmlspecialchars($g['nom_groupe'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <div class="small text-secondary">
                                            Chef : <strong><?php echo htmlspecialchars($g['chef_prenom'] . ' ' . $g['chef_nom'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            (<?php echo htmlspecialchars($g['chef_email'], ENT_QUOTES, 'UTF-8'); ?>)
                                        </div>
                                    </div>
                                    <span class="badge text-bg-light align-self-start"><?php echo count($members); ?> membre(s)</span>
                                </div>
                                <div class="mt-3">
                                    <?php foreach ($members as $m): ?>
                                        <span class="badge text-bg-<?php echo (int)$m['id_user'] === (int)$g['id_chef'] ? 'primary' : 'secondary'; ?> me-1 mb-1">
                                            <?php echo htmlspecialchars($m['prenom'] . ' ' . $m['nom'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php echo (int)$m['id_user'] === (int)$g['id_chef'] ? ' - chef' : ''; ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($exercice['type_exercice'] === 'groupe'): ?>
<script>
(function () {
    function initGroupManager() {
        var chefSelect = document.getElementById('id_chef');
        var memberChecks = document.querySelectorAll('.member-check');
        var memberItems = document.querySelectorAll('.member-item');
        var searchInput = document.getElementById('memberSearch');
        var groupForm = document.getElementById('createGroupForm');

        function syncChefWithMembers() {
            var chefId = chefSelect ? chefSelect.value : '';
            memberChecks.forEach(function (cb) {
                var userId = cb.value;
                if (userId === chefId && chefId !== '') {
                    cb.disabled = true;
                    if (cb.checked) cb.checked = false;
                } else {
                    cb.disabled = false;
                }
            });
        }

        function filterMembers() {
            var query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            memberItems.forEach(function (item) {
                var text = (item.getAttribute('data-query') || '').toLowerCase();
                if (query === '' || text.indexOf(query) !== -1) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        if (chefSelect) {
            chefSelect.addEventListener('change', syncChefWithMembers);
            syncChefWithMembers();
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterMembers);
        }

        if (groupForm) {
            groupForm.addEventListener('submit', function (e) {
                var chefId = chefSelect ? chefSelect.value : '';
                var selectedMembers = Array.from(memberChecks).filter(function (cb) {
                    return cb.checked && cb.value !== chefId;
                });
                var totalPeople = (chefId ? 1 : 0) + selectedMembers.length;
                if (totalPeople < 2) {
                    e.preventDefault();
                    alert('Un groupe doit comporter au moins 2 personnes (chef compris).');
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initGroupManager);
    } else {
        initGroupManager();
    }
})();
</script>
<?php endif; ?>
