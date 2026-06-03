<div class="mb-4">
    <a href="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>" class="small">&larr; Retour a la liste</a>
    <h1 class="h2 mt-2 mb-0"><?php echo htmlspecialchars($title ?? 'Formulaire utilisateur', ENT_QUOTES, 'UTF-8'); ?></h1>
</div>

<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="<?php echo htmlspecialchars(url($student ? 'admin/students/edit' : 'admin/students/create'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if ($student): ?><input type="hidden" name="id" value="<?php echo (int) $student['id_user']; ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" id="nom" name="nom" required value="<?php echo htmlspecialchars($student['nom'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prenom</label>
                    <input type="text" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($student['prenom'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                </div>
                <div class="col-12">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($student['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="form-control">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><?php echo $student ? 'Enregistrer les modifications' : 'Creer le compte'; ?></button>
                <a href="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">Annuler</a>
            </div>
        </form>
    </div>
</div>
