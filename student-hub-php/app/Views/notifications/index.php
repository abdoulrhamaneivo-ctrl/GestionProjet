<div class="d-flex justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="fs-3 mb-1">Notifications</h1>
        <p class="text-secondary mb-0">Suivi des soumissions, notes, derogations et commentaires.</p>
    </div>
    <form action="<?php echo htmlspecialchars(url('notifications/read-all'), ENT_QUOTES, 'UTF-8'); ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $h($csrf_token); ?>">
        <button class="btn btn-outline-primary" type="submit">Tout marquer comme lu</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="p-5 text-center text-secondary">Aucune notification.</div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <a class="list-group-item list-group-item-action <?php echo (int)$notification['lu'] === 0 ? 'list-group-item-primary' : ''; ?>"
                       href="<?php echo htmlspecialchars(url($notification['lien_url'] ?: 'notifications'), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="d-flex w-100 justify-content-between gap-3">
                            <h2 class="h6 mb-1"><?php echo $h($notification['titre']); ?></h2>
                            <small><?php echo date('d/m/Y H:i', strtotime($notification['date_creation'])); ?></small>
                        </div>
                        <p class="mb-1"><?php echo $h($notification['message']); ?></p>
                        <small class="text-secondary"><?php echo $h($notification['type']); ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
