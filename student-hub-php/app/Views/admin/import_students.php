<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <a href="<?php echo htmlspecialchars(url('admin/students'), ENT_QUOTES, 'UTF-8'); ?>" class="link-secondary text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i> Retour aux etudiants
        </a>
        <h1 class="fs-3 mb-1 mt-2">Importer les etudiants</h1>
        <p class="text-secondary mb-0">Ajoutez une liste CSV ou TXT, puis recuperez les identifiants generes.</p>
    </div>
    <a href="<?php echo htmlspecialchars(url('admin/students/import/download'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary">
        <i class="bi bi-download me-1"></i> Telecharger les identifiants
    </a>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-3">Fichier a envoyer</h2>
                <form action="<?php echo htmlspecialchars(url('admin/students/import'), ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="csv_file" class="form-label">Fichier CSV ou TXT</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".txt,text/plain" required>
                        <div class="form-text">Chaque ligne doit contenir le nom complet d'un etudiant.</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload me-1"></i> Lancer l'import
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-7">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="fs-5 mb-3">Format attendu</h2>
                <p class="text-secondary">Le fichier doit etre en <strong>.txt</strong>. Une ligne correspond a un etudiant.</p>
                <div class="bg-light border rounded p-3 mb-3">
                    <code>KAKA Zara Kourou ABBA</code><br>
                    <code>SALIFOU Mahamadou Bachir ABDOU</code>
                </div>
                <ul class="mb-0 text-secondary">
                    <li>Le premier mot est traite comme le nom.</li>
                    <li>Les mots suivants deviennent le prenom complet.</li>
                    <li>L'email et le mot de passe sont generes automatiquement.</li>
                    <li>Les doublons d'email sont ignores et affiches dans le rapport.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($importResult)): ?>
    <div class="card mt-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="fs-5 mb-1">Rapport du dernier import</h2>
                    <p class="text-secondary mb-0">Verifiez les comptes crees avant de transmettre les identifiants.</p>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge text-bg-success align-self-start"><?php echo (int)($importResult['inserted'] ?? 0); ?> ajoutes</span>
                    <span class="badge text-bg-secondary align-self-start"><?php echo (int)($importResult['skipped'] ?? 0); ?> ignores</span>
                </div>
            </div>

            <?php if (!empty($importResult['students'])): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prenom</th>
                                <th>Email</th>
                                <th>Mot de passe</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($importResult['students'] as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['nom'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($student['prenom'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($student['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><code><?php echo htmlspecialchars($student['mot_de_passe'] ?? '', ENT_QUOTES, 'UTF-8'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($importResult['errors'])): ?>
                <div class="alert alert-warning mt-3 mb-0">
                    <strong>Lignes ignorees :</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($importResult['errors'] as $lineError): ?>
                            <li><?php echo htmlspecialchars($lineError, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
