<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    /**
     * Find user by login email
     */
    public static function findByEmail(string $email): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find user by id
     */
    public static function findById(int $id): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT id_user, nom, prenom, email, role FROM utilisateurs WHERE id_user = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
      * Fetch all users of a specific role with optional search, pagination
      */
    public static function findAllByRole(string $role, ?string $search = null, ?int $page = null, ?int $perPage = 25): array
    {
        $db = Database::pdo();
        $params = [];
        $sql = 'SELECT id_user, nom, prenom, email FROM utilisateurs WHERE role = ?';
        $params[] = $role;

        if ($search !== null && $search !== '') {
            $sql .= ' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY nom ASC';

        if ($page !== null && $perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $sql .= ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
      * Count users by role with optional search
      */
    public static function countByRole(string $role, ?string $search = null): int
    {
        $db = Database::pdo();
        $sql = 'SELECT COUNT(*) FROM utilisateurs WHERE role = ?';
        $params = [$role];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Update user fields (nom, prenom, email, role)
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ? WHERE id_user = ?');
        return $stmt->execute([
            $data['nom'] ?? '',
            $data['prenom'] ?? '',
            $data['email'] ?? '',
            $data['role'] ?? 'etudiant',
            $id
        ]);
    }

    /**
     * Delete user by id
     */
    public static function delete(int $id): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare('DELETE FROM utilisateurs WHERE id_user = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Fetch students that are NOT currently members of any group for a given exercise.
     * This ensures students cannot double-register in group assignments!
     */
    public static function findAvailableStudentsForExercise(int $exerciceId): array
    {
        $db = Database::pdo();
        $sql = 'SELECT u.id_user, u.nom, u.prenom, u.email 
                FROM utilisateurs u 
                WHERE u.role = "etudiant" 
                AND u.id_user NOT IN (
                    SELECT mg.id_user 
                    FROM membres_groupe mg
                    JOIN groupes g ON mg.id_groupe = g.id_groupe
                    WHERE g.id_exercice = ?
                )
                AND u.id_user NOT IN (
                    SELECT g2.id_chef 
                    FROM groupes g2
                    WHERE g2.id_exercice = ?
                )
                ORDER BY u.nom ASC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$exerciceId, $exerciceId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new user and return the inserted ID
     */
    public static function create(string $nom, string $prenom, string $email, string $motDePasse, string $role): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, first_login) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$nom, $prenom, $email, $motDePasse, $role, 1]);
        return (int) $db->lastInsertId();
    }
}
