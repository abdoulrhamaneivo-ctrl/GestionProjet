<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="small">&larr; Retour administration</a>
        <h1 class="h2 mt-2 mb-0">Gestion des etudiants</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo htmlspecialchars(url('admin/students/import'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary"><i class="bi bi-upload"></i> Importer</a>
        <a href="<?php echo htmlspecialchars(url('admin/students/export'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-success"><i class="bi bi-download"></i> Exporter TXT</a>
        <a href="<?php echo htmlspecialchars(url('admin/students/create'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nouvel etudiant</a>
    </div>
</div>

<form class="card shadow-sm mb-3" method="GET" action="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="adminStudentSearch">Rechercher</label>
                <input class="form-control" id="adminStudentSearch" name="search" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nom, prenom ou email">
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
                <a class="ms-2" href="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
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
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Nom</th>
                    <th>Prenom</th>
                    <th>Email</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">Aucun etudiant enregistre.</td></tr>
            <?php endif; ?>
            <?php foreach ($students as $index => $stu): ?>
                <tr>
                    <td class="text-muted small">#<?php echo ($currentPage - 1) * $perPage + $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($stu['nom'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                    <td><?php echo htmlspecialchars($stu['prenom'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><a href="mailto:<?php echo htmlspecialchars($stu['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($stu['email'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                    <td class="text-end">
                        <a href="<?php echo htmlspecialchars(url('admin/students/edit?id=' . $stu['id_user']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary">Modifier</a>
                        <form action="<?php echo htmlspecialchars(url('admin/students/delete'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="d-inline" id="deleteStudentForm<?php echo (int) $stu['id_user']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="id" value="<?php echo (int) $stu['id_user']; ?>">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-form-id="deleteStudentForm<?php echo (int) $stu['id_user']; ?>" data-confirm-message="Supprimer cet etudiant ? Cette action est irreversible.">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($hasPagination): ?>
<nav aria-label="Pagination etudiants">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage > 1 ? htmlspecialchars(url('admin/students?page=' . ($currentPage - 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Precedent"><span aria-hidden="true">&laquo;</span></a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(url('admin/students?page=' . $i . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $currentPage >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage < $total_pages ? htmlspecialchars(url('admin/students?page=' . ($currentPage + 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
