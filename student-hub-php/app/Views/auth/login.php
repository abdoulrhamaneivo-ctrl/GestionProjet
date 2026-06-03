<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4 text-center">
                    <div class="display-5 text-primary mb-3">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <h1 class="fs-4 mb-1">Se connecter</h1>
                    <p class="text-secondary small mb-0">Accedez a l'espace de depot numerique EMSP DSER.</p>
                </div>

                <form action="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">

                    <div>
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" id="email" name="email" required placeholder="nom@emsp.ci" class="form-control">
                    </div>

                    <div>
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" id="password" name="password" required placeholder="Votre mot de passe" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                    </button>

                    <a href="<?php echo htmlspecialchars(url('auth/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light w-100">
                        Mot de passe oublie
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
