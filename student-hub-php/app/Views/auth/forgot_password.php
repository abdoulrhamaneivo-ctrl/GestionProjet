<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4 text-center">
                    <div class="display-5 text-primary mb-3">
                        <i class="bi bi-key"></i>
                    </div>
                    <h1 class="fs-4 mb-1">Mot de passe oublie</h1>
                    <p class="text-secondary small mb-0">Envoyez une demande de reinitialisation a l'administration.</p>
                </div>

                <form action="<?php echo htmlspecialchars(url('auth/forgot-password'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" class="vstack gap-3">
                    <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">

                    <div>
                        <label for="email" class="form-label">Adresse email du compte</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="nom@emsp.ci">
                    </div>

                    <div>
                        <label for="message" class="form-label">Message optionnel</label>
                        <textarea id="message" name="message" rows="3" class="form-control" placeholder="Ex: je ne retrouve plus mon mot de passe."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send me-1"></i> Envoyer la demande
                    </button>

                    <a href="<?php echo htmlspecialchars(url('auth/login'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-light w-100">
                        Retour a la connexion
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>
