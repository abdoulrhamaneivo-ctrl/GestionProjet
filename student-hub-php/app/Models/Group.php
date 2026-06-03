<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\Exercise;
use PDO;

final class Group
{
    /**
     * Create a fresh student group with a specified leader (chef)
     */
    public static function create(int $exerciceId, int $chefId, string $nomGroupe): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO groupes (id_exercice, id_chef, nom_groupe) VALUES (?, ?, ?)');
        $stmt->execute([$exerciceId, $chefId, $nomGroupe]);
        return (int) $db->lastInsertId();
    }

    /**
     * Link an student to a created group
     */
    public static function addMember(int $groupId, int $userId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('INSERT INTO membres_groupe (id_groupe, id_user) VALUES (?, ?)');
        $stmt->execute([$groupId, $userId]);
    }

    /**
     * Delete a member from a group
     */
    public static function removeMember(int $groupId, int $userId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('DELETE FROM membres_groupe WHERE id_groupe = ? AND id_user = ?');
        $stmt->execute([$groupId, $userId]);
    }

    /**
     * Search group of a student for an exercise
     */
    public static function findUserGroupForExercise(int $exerciceId, int $userId): ?array
    {
        $db = Database::pdo();
        $sql = 'SELECT g.*, u.nom as chef_nom, u.prenom as chef_prenom 
                FROM groupes g
                JOIN utilisateurs u ON g.id_chef = u.id_user
                LEFT JOIN membres_groupe mg ON g.id_groupe = mg.id_groupe
                WHERE g.id_exercice = ? 
                AND (g.id_chef = ? OR mg.id_user = ?)
                LIMIT 1';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$exerciceId, $userId, $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get list of group members
     */
    public static function getMembers(int $groupId): array
    {
        $db = Database::pdo();
        $sql = 'SELECT u.id_user, u.nom, u.prenom, u.email 
                FROM utilisateurs u
                JOIN groupes g ON u.id_user = g.id_chef
                WHERE g.id_groupe = ?
                UNION
                SELECT u.id_user, u.nom, u.prenom, u.email 
                FROM utilisateurs u
                JOIN membres_groupe mg ON u.id_user = mg.id_user
                WHERE mg.id_groupe = ?
                ORDER BY nom ASC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$groupId, $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all groups created for an exercise
     */
    public static function getGroupsForExercise(int $exerciceId): array
    {
        $db = Database::pdo();
        $sql = 'SELECT g.*, u.nom as chef_nom, u.prenom as chef_prenom, u.email as chef_email
                FROM groupes g
                JOIN utilisateurs u ON g.id_chef = u.id_user
                WHERE g.id_exercice = ?';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$exerciceId]);
        return $stmt->fetchAll();
    }

    /**
     * Count total groups (pass 0 to ignore exercise filter)
     */
    public static function countTotalForExercise(int $exerciceId): int
    {
        $db = Database::pdo();
        if ($exerciceId <= 0) {
            $stmt = $db->query('SELECT COUNT(*) FROM groupes');
        } else {
            $stmt = $db->prepare('SELECT COUNT(*) FROM groupes WHERE id_exercice = ?');
            $stmt->execute([$exerciceId]);
        }
        return (int) $stmt->fetchColumn();
    }

    /**
     * List non-submitted students and groups for a given exercise
     */
    public static function getMissingSubmissionsForExercise(int $exerciceId): array
    {
        $db = Database::pdo();

        $missingIndividuals = [];
        $missingGroups = [];

        if ($exerciceId <= 0) {
            return ['individuals' => $missingIndividuals, 'groups' => $missingGroups];
        }

        $exercise = \App\Models\Exercise::findById($exerciceId);
        if (!$exercise) {
            return ['individuals' => $missingIndividuals, 'groups' => $missingGroups];
        }
        $isGroup = $exercise['type_exercice'] === 'groupe';

        if ($isGroup) {
            $groups = self::getGroupsForExercise($exerciceId);
            $groupIds = [];
            foreach ($groups as $g) {
                $groupIds[] = (int) $g['id_groupe'];
            }

            foreach ($groupIds as $groupId) {
                $projStmt = $db->prepare('SELECT id_projet FROM projets WHERE id_exercice = ? AND id_groupe = ? LIMIT 1');
                $projStmt->execute([$exerciceId, $groupId]);
                if (!$projStmt->fetch()) {
                    $grp = null;
                    foreach ($groups as $g) {
                        if ((int) $g['id_groupe'] === $groupId) {
                            $grp = $g;
                            break;
                        }
                    }
                    if ($grp) {
                        $missingGroups[] = $grp;
                    }
                }
            }
        } else {
            // Exercice individuel : détecter les étudiants sans soumission
            $allStudents = $db->query('SELECT id_user, nom, prenom, email FROM utilisateurs WHERE role = "etudiant" ORDER BY nom ASC')->fetchAll();
            foreach ($allStudents as $student) {
                $projStmt = $db->prepare('SELECT id_projet FROM projets WHERE id_exercice = ? AND id_user_individuel = ? LIMIT 1');
                $projStmt->execute([$exerciceId, (int) $student['id_user']]);
                if (!$projStmt->fetch()) {
                    $missingIndividuals[] = $student;
                }
            }
        }

        return ['individuals' => $missingIndividuals, 'groups' => $missingGroups];
    }
}
