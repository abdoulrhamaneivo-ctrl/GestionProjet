<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/dashboard'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour a l'administration
        </a>
        <h1 class="fs-3 mb-1 mt-2">Gestion des professeurs</h1>
        <p class="text-secondary mb-0">Comptes enseignants. Les mots de passe temporaires sont affichés ici jusqu'au premier changement de mot de passe.</p>
    </div>
    <a href="<?php echo htmlspecialchars(url('admin/students/create'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i> Nouveau compte
    </a>
</div>

<form class="card shadow-sm mb-3" method="GET" action="<?php echo htmlspecialchars(url('admin/professors'), ENT_QUOTES, 'UTF-8'); ?>">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label" for="adminProfessorSearch">Rechercher</label>
                <input class="form-control" id="adminProfessorSearch" name="search" value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nom, prenom ou email">
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
                <a class="ms-2" href="<?php echo htmlspecialchars(url('admin/professors'), ENT_QUOTES, 'UTF-8'); ?>">Reinitialiser</a>
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
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>Email</th>
                        <th>Mot de passe</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($professors)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-4">Aucun professeur enregistre.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($professors as $index => $prof): ?>
                        <?php $plainPwd = $prof['mot_de_passe_temporaire'] ?? 'Non disponible'; ?>
                        <tr>
                            <td class="text-muted small">#<?php echo ($currentPage - 1) * $perPage + $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($prof['nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($prof['prenom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($prof['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><code><?php echo htmlspecialchars($plainPwd, ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td class="text-end">
                                <a href="<?php echo htmlspecialchars(url('admin/students/edit?id=' . $prof['id_user']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary">
                                    Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($hasPagination): ?>
<nav aria-label="Pagination professeurs">
    <ul class="pagination justify-content-center">
        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage > 1 ? htmlspecialchars(url('admin/professors?page=' . ($currentPage - 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Precedent"><span aria-hidden="true">&laquo;</span></a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="<?php echo htmlspecialchars(url('admin/professors?page=' . $i . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?php echo $currentPage >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $currentPage < $total_pages ? htmlspecialchars(url('admin/professors?page=' . ($currentPage + 1) . ($search !== '' ? '&search=' . urlencode($search) : '')), ENT_QUOTES, 'UTF-8') : '#'; ?>" aria-label="Suivant"><span aria-hidden="true">&raquo;</span></a>
        </li>
    </ul>
</nav>
<?php endif; ?>
