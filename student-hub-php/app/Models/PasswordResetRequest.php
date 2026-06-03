<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class PasswordResetRequest
{
    public static function create(int $userId, string $email, string $message = ''): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'INSERT INTO password_reset_requests (id_user, email, message, statut, date_demande)
             VALUES (?, ?, ?, "pending", NOW())'
        );
        $stmt->execute([$userId, $email, $message]);
    }

    public static function getPending(): array
    {
        $db = Database::pdo();
        $stmt = $db->query(
            'SELECT pr.*, u.nom, u.prenom, u.role
             FROM password_reset_requests pr
             JOIN utilisateurs u ON u.id_user = pr.id_user
             WHERE pr.statut = "pending"
             ORDER BY pr.date_demande DESC'
        );
        return $stmt->fetchAll();
    }

    public static function findPendingById(int $id): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT pr.*, u.nom, u.prenom, u.role
             FROM password_reset_requests pr
             JOIN utilisateurs u ON u.id_user = pr.id_user
             WHERE pr.id_request = ? AND pr.statut = "pending"
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function markResolved(int $id, int $resolvedBy, string $generatedPassword): void
    {
        $db = Database::pdo();
        // Ne jamais stocker le mot de passe en clair dans la colonne generated_password
        $stmt = $db->prepare(
            'UPDATE password_reset_requests
             SET statut = "resolved", resolved_by = ?, generated_password = "[REINITIALISE]", date_resolution = NOW()
             WHERE id_request = ?'
        );
        $stmt->execute([$resolvedBy, $id]);
    }
}
