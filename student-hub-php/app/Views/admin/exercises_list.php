<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="small">&larr; Retour administration</a>
        <h1 class="h2 mt-2 mb-0">Gestion des exercices</h1>
    </div>
    <a href="<?php echo htmlspecialchars(url('admin/exercise/create'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nouvel exercice
    </a>
</div>

<form class="card shadow-sm mb-3" method="GET" action="<?php echo htmlspecialchars(url('admin/exercises'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="adminExerciseSearch">Rechercher</label>
                <input class="form-control" id="adminExerciseSearch" name="search" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Titre ou type d'exercice">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100" type="submit" aria-label="Rechercher">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <span class="d-none d-sm-inline">Rechercher</span>
                </button>
            </div>
        </div>
        <?php if (!empty($search ?? '')): ?>
            <div class="mt-2 small text-secondary">
                Resultat(s) pour : <strong><?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?></strong>
                <a class="ms-2" href="<?php echo htmlspecialchars(url('admin/exercises'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php
$hasPagination = isset($total_pages) && $total_pages > 1;
$currentPage = (int) ($page ?? 1);
$perPage = (int) ($per_page ?? 25);
?>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white border-0 py-2">
        <button class="btn btn-link btn-sm text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#activeExercisesCollapse" aria-expanded="true" aria-controls="activeExercisesCollapse">
            <strong>Exercices actifs</strong>
            <span class="badge text-bg-success ms-2"><?php echo count($active_exercises ?? []); ?></span>
            <i class="bi bi-chevron-down ms-1"></i>
        </button>
    </div>
    <div class="collapse show" id="activeExercisesCollapse">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date limite</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($active_exercises)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucun exercice actif.</td></tr>
                <?php endif; ?>
                <?php foreach ($active_exercises as $index => $ex): ?>
                    <tr>
                        <td class="text-muted small">#<?php echo ($currentPage - 1) * $perPage + $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($ex['titre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><span class="badge text-bg-<?php echo $ex['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>"><?php echo htmlspecialchars($ex['type_exercice'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ex['date_limite'])); ?></td>
                        <td><?php echo (int) $ex['est_bloque'] === 1 ? '<span class="badge text-bg-danger">Verrouille</span>' : '<span class="badge text-bg-success">Actif</span>'; ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo htmlspecialchars(url('admin/exercises/edit?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Modifier</a>
                                <a href="<?php echo htmlspecialchars(url('admin/group/manage?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary">Groupes / derogations</a>
                                <a href="<?php echo htmlspecialchars(url('admin/exercise/export-grades?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-success">Notes</a>
                            </div>
                            <div class="d-inline-flex gap-1 ms-1">
                                <form action="<?php echo htmlspecialchars(url('admin/exercise/archive'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline" id="archiveActiveForm<?php echo (int) $ex['id_exercice']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id_exercice" value="<?php echo (int) $ex['id_exercice']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-form-id="archiveActiveForm<?php echo (int) $ex['id_exercice']; ?>" data-confirm-message="Archiver cet exercice ?">Archiver</button>
                                </form>
                                <form action="<?php echo htmlspecialchars(url('admin/exercises/delete'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline" id="deleteActiveForm<?php echo (int) $ex['id_exercice']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id_exercice" value="<?php echo (int) $ex['id_exercice']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-form-id="deleteActiveForm<?php echo (int) $ex['id_exercice']; ?>" data-confirm-message="Supprimer cet exercice et toutes ses donnees associees ?">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white border-0 py-2">
        <button class="btn btn-link btn-sm text-decoration-none p-0" type="button" data-bs-toggle="collapse" data-bs-target="#archivedExercisesCollapse" aria-expanded="false" aria-controls="archivedExercisesCollapse">
            <strong>Exercices archives</strong>
            <span class="badge text-bg-secondary ms-2"><?php echo count($archived_exercises ?? []); ?></span>
            <i class="bi bi-chevron-down ms-1"></i>
        </button>
    </div>
    <div class="collapse" id="archivedExercisesCollapse">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Date limite</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($archived_exercises)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">Aucun exercice archive.</td></tr>
                <?php endif; ?>
                <?php foreach ($archived_exercises as $index => $ex): ?>
                    <tr>
                        <td class="text-muted small">#<?php echo ($currentPage - 1) * $perPage + count($active_exercises ?? []) + $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($ex['titre'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                        <td><span class="badge text-bg-<?php echo $ex['type_exercice'] === 'groupe' ? 'primary' : 'warning'; ?>"><?php echo htmlspecialchars($ex['type_exercice'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($ex['date_limite'])); ?></td>
                        <td><span class="badge text-bg-secondary">Archive</span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?php echo htmlspecialchars(url('admin/exercises/edit?id=' . $ex['id_exercice']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Modifier</a>
                            </div>
                            <div class="d-inline-flex gap-1 ms-1">
                                <form action="<?php echo htmlspecialchars(url('admin/exercise/archive'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline" id="archiveArchivedForm<?php echo (int) $ex['id_exercice']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id_exercice" value="<?php echo (int) $ex['id_exercice']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-success" data-form-id="archiveArchivedForm<?php echo (int) $ex['id_exercice']; ?>" data-confirm-message="Desarchiver cet exercice ?">Desarchiver</button>
                                </form>
                                <form action="<?php echo htmlspecialchars(url('admin/exercises/delete'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline" id="deleteArchivedForm<?php echo (int) $ex['id_exercice']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="id_exercice" value="<?php echo (int) $ex['id_exercice']; ?>">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-form-id="deleteArchivedForm<?php echo (int) $ex['id_exercice']; ?>" data-confirm-message="Supprimer cet exercice et toutes ses donnees associees ?">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($hasPagination): ?>
<nav aria-label="Pagination exercices">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage > 1 ? htmlspecialchars(url('admin/exercises?page=' . ($currentPage - 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Precedent"><span aria-hidden="true">&laquo;</span></a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(url('admin/exercises?page=' . $i . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $currentPage >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage < $total_pages ? htmlspecialchars(url('admin/exercises?page=' . ($currentPage + 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
