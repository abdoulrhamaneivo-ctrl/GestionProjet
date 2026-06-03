<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Exercise
{
    // MD-ARCHIVE: getAll() inclut archivés ; getActive()/getArchived() filtrent ; toggleArchive() bascule l'état

    /**
      * Get all assignments including archived ones, with optional search and pagination
      */
    public static function getAll(?string $search = null, ?int $page = null, ?int $perPage = 25): array
    {
        $db = Database::pdo();
        $params = [];
        $sql = 'SELECT * FROM exercices WHERE 1=1';

        if ($search !== null && $search !== '') {
            $sql .= ' AND (titre LIKE ? OR type_exercice LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY date_limite DESC';

        if ($page !== null && $perPage > 0) {
            $offset = ($page - 1) * $perPage;
            $sql .= ' LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
      * Count all exercises with optional search
      */
    public static function countAll(?string $search = null): int
    {
        $db = Database::pdo();
        $sql = 'SELECT COUNT(*) FROM exercices WHERE 1=1';
        $params = [];

        if ($search !== null && $search !== '') {
            $sql .= ' AND (titre LIKE ? OR type_exercice LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get active (non-archived) assignments only
     */
    public static function getActive(): array
    {
        $db = Database::pdo();
        $stmt = $db->query('SELECT * FROM exercices WHERE archive = 0 ORDER BY date_limite DESC');
        return $stmt->fetchAll();
    }

    /**
     * Get archived assignments only
     */
    public static function getArchived(): array
    {
        $db = Database::pdo();
        $stmt = $db->query('SELECT * FROM exercices WHERE archive = 1 ORDER BY date_limite DESC');
        return $stmt->fetchAll();
    }

    public static function toggleArchive(int $exerciceId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE exercices SET archive = IF(archive = 1, 0, 1) WHERE id_exercice = ?');
        $stmt->execute([$exerciceId]);
    }

    /**
     * Find exercises assigned to a specific professor
     */
    public static function getByProfesseur(int $professeurId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT * FROM exercices WHERE professeur_id = ? ORDER BY date_limite DESC');
        $stmt->execute([$professeurId]);
        return $stmt->fetchAll();
    }

    /**
     * Find exercise by id
     */
    public static function findById(int $id): ?array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT * FROM exercices WHERE id_exercice = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Create fresh assignment
     */
    public static function create(string $titre, string $type, string $dateLimite, ?int $professeurId = null): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO exercices (titre, type_exercice, date_limite, professeur_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$titre, $type, $dateLimite, $professeurId]);
        return (int) $db->lastInsertId();
    }

    /**
     * Add theme option to assignment
     */
    public static function addTheme(int $exerciceId, string $nomTheme): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO themes (id_exercice, nom_theme) VALUES (?, ?)');
        $stmt->execute([$exerciceId, $nomTheme]);
    }

    /**
      * Fetch themes associated to an assignment
      */
    public static function getThemes(int $exerciceId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT * FROM themes WHERE id_exercice = ? ORDER BY nom_theme ASC');
        $stmt->execute([$exerciceId]);
        return $stmt->fetchAll();
    }

    public static function addDesignatedChef(int $exerciceId, int $userId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT IGNORE INTO exercice_designated_chefs (id_exercice, id_user) VALUES (?, ?)');
        $stmt->execute([$exerciceId, $userId]);
    }

    public static function replaceDesignatedChefs(int $exerciceId, array $userIds): void
    {
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM exercice_designated_chefs WHERE id_exercice = ?')->execute([$exerciceId]);
            $stmt = $db->prepare('INSERT IGNORE INTO exercice_designated_chefs (id_exercice, id_user) VALUES (?, ?)');
            foreach ($userIds as $userId) {
                $stmt->execute([$exerciceId, $userId]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function isDesignatedChef(int $exerciceId, int $userId): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare('SELECT 1 FROM exercice_designated_chefs WHERE id_exercice = ? AND id_user = ? LIMIT 1');
        $stmt->execute([$exerciceId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Add specific user or group deadline extension
     */
    public static function addDerogation(int $exerciceId, ?int $userId, ?int $groupId, string $nouvelleDate): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO derogations (id_exercice, id_user, id_groupe, nouvelle_date) VALUES (?, ?, ?, ?)');
        $stmt->execute([$exerciceId, $userId, $groupId, $nouvelleDate]);
    }

    public static function getDerogationsForExercise(int $exerciceId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare('
            SELECT d.*, 
                   u.nom as user_nom, u.prenom as user_prenom, u.email as user_email,
                   g.nom_groupe
            FROM derogations d
            LEFT JOIN utilisateurs u ON d.id_user = u.id_user
            LEFT JOIN groupes g ON d.id_groupe = g.id_groupe
            WHERE d.id_exercice = ?
            ORDER BY d.nouvelle_date ASC
        ');
        $stmt->execute([$exerciceId]);
        return $stmt->fetchAll();
    }

    public static function deleteDerogation(int $derogationId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('DELETE FROM derogations WHERE id_derogation = ?');
        $stmt->execute([$derogationId]);
    }

    /**
     * Toggle exercise block/ban status immediately
     */
    public static function updateBlockStatus(int $exerciceId, int $blocked): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE exercices SET est_bloque = ? WHERE id_exercice = ?');
        $stmt->execute([$blocked, $exerciceId]);
    }

    /**
     * Update exercise fields
     */
    public static function update(int $id, array $data): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE exercices SET titre = ?, type_exercice = ?, date_limite = ?, est_bloque = ?, professeur_id = ? WHERE id_exercice = ?');
        return $stmt->execute([
            $data['titre'] ?? '',
            $data['type_exercice'] ?? 'individuel',
            $data['date_limite'] ?? date('Y-m-d H:i:s'),
            $data['est_bloque'] ?? 0,
            $data['professeur_id'] ?? null,
            $id
        ]);
    }

    /**
     * Delete exercise and cascade related data
     */
    public static function delete(int $id): bool
    {
        $db = Database::pdo();
        $db->beginTransaction();

        try {
            $db->prepare('DELETE FROM derogations WHERE id_exercice = ?')->execute([$id]);
            $db->prepare('DELETE FROM themes WHERE id_exercice = ?')->execute([$id]);

            $groups = $db->prepare('SELECT id_groupe FROM groupes WHERE id_exercice = ?');
            $groups->execute([$id]);
            $groupIds = $groups->fetchAll(PDO::FETCH_COLUMN);

            foreach ($groupIds as $groupId) {
                $db->prepare('DELETE FROM membres_groupe WHERE id_groupe = ?')->execute([$groupId]);
            }

            $db->prepare('DELETE FROM groupes WHERE id_exercice = ?')->execute([$id]);
            $db->prepare('DELETE FROM projets WHERE id_exercice = ?')->execute([$id]);
            $db->prepare('DELETE FROM exercices WHERE id_exercice = ?')->execute([$id]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Check whether an assignment submission deadline has expired.
     * Takes custom "derogations" extensions granted by admins into account!
     */
    public static function checkSubmissionStatus(int $exerciceId, int $userId): array
    {
        $exercice = self::findById($exerciceId);
        if ($exercice === null) {
            return ['allowed' => false, 'status' => 'non_existent', 'message' => 'Exercice introuvable.'];
        }

        $db = Database::pdo();

        // Check if notes are published for this exercise (meaning the exercise is closed/published)
        $pubStmt = $db->prepare('SELECT 1 FROM projets WHERE id_exercice = ? AND notes_publiees = 1 LIMIT 1');
        $pubStmt->execute([$exerciceId]);
        if ((bool) $pubStmt->fetchColumn()) {
            return ['allowed' => false, 'status' => 'bloque', 'message' => 'Les notes de cet exercice ont été publiées. Les soumissions sont closes.'];
        }

        if ($exercice['est_bloque'] == 1) {
            return ['allowed' => false, 'status' => 'bloque', 'message' => 'Cet exercice a été bloqué par l\'administrateur.'];
        }

        $now = date('Y-m-d H:i:s');
        $limiteStr = $exercice['date_limite'];
        
        // Find if user belongs to a group for this exercise
        $grpStmt = $db->prepare('
            SELECT g.id_groupe FROM groupes g
            LEFT JOIN membres_groupe mg ON g.id_groupe = mg.id_groupe
            WHERE g.id_exercice = ? AND (g.id_chef = ? OR mg.id_user = ?)
            LIMIT 1
        ');
        $grpStmt->execute([$exerciceId, $userId, $userId]);
        $grp = $grpStmt->fetch();
        $groupId = $grp ? (int) $grp['id_groupe'] : null;

        // Check for specific derogation (individual or group-wide)
        $deroStmt = $db->prepare('
            SELECT nouvelle_date FROM derogations 
            WHERE id_exercice = ? 
            AND (id_user = ? OR (id_groupe = ? AND id_groupe IS NOT NULL))
            ORDER BY nouvelle_date DESC 
            LIMIT 1
        ');
        $deroStmt->execute([$exerciceId, $userId, $groupId]);
        $dero = $deroStmt->fetch();

        if ($dero) {
            $limiteStr = $dero['nouvelle_date'];
        }

        $isLate = strtotime($now) > strtotime($limiteStr);

        if ($isLate && $exercice['corrections_terminees']) {
            return [
                'allowed' => false,
                'status' => 'cloture',
                'deadline' => $limiteStr,
                'message' => 'Les corrections ont ete finalisees par le professeur. Les soumissions sont closes.'
            ];
        }

        if ($isLate) {
            return [
                'allowed' => true,
                'status' => 'en_retard',
                'deadline' => $limiteStr,
                'message' => 'Vous etes en retard. La soumission est acceptee mais sera marquee "En retard".'
            ];
        }

        // Note: est_bloque déjà vérifié en début de méthode (avant la logique deadline)

        return [
            'allowed' => true,
            'status' => 'autorise',
            'deadline' => $limiteStr,
            'message' => 'Soumission autorisee.'
        ];
    }

    /**
     * Mark corrections as finished for an exercise
     */
    public static function markCorrectionsFinished(int $exerciceId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE exercices SET corrections_terminees = 1 WHERE id_exercice = ?');
        $stmt->execute([$exerciceId]);
    }
}
