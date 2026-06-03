<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <a href="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i> Retour a la connexion
                </a>
                <div class="mt-3 mb-4">
                    <h1 class="fs-4 mb-1">Changer le mot de passe</h1>
                    <p class="text-secondary mb-0">Cette etape protege votre compte avant l'acces a la plateforme.</p>
                </div>

                <form action="<?php echo htmlspecialchars(url('auth/change-password'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                    <div>
                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="form-control">
                    </div>

                    <div>
                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password" class="form-control">
                    </div>

                    <div>
                        <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Modifier le mot de passe</button>
                </form>
            </div>
        </div>
    </div>
</div>
