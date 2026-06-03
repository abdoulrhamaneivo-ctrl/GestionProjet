<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Project
{
    // MD-ARCHIVE: getPublicProjectsForGallery() exclut les projets d’exercices archivés (contrôle amont dans StudentController)
    // MD-CHANGEPWD: pas de logique métier liée ici ; suppression des identifiants en clair gérée dans AuthController

    /**
     * Store project submission details in database (supports transaction blocks inside service layer if needed).
     */
    public static function create(array $data): int
    {
        $db = Database::pdo();
        $sql = 'INSERT INTO projets (
                    id_exercice, id_theme, id_user_individuel, id_groupe, 
                    titre_projet, lien_url, acces_test, explications, 
                    cahier_charges_path, date_soumission
                ) VALUES (
                    :id_exercice, :id_theme, :id_user_individuel, :id_groupe, 
                    :titre_projet, :lien_url, :acces_test, :explications, 
                    :cahier_charges_path, :date_soumission
                )';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_exercice' => $data['id_exercice'],
            ':id_theme' => $data['id_theme'] ?: null,
            ':id_user_individuel' => $data['id_user_individuel'] ?: null,
            ':id_groupe' => $data['id_groupe'] ?: null,
            ':titre_projet' => $data['titre_projet'],
            ':lien_url' => $data['lien_url'],
            ':acces_test' => $data['acces_test'],
            ':explications' => $data['explications'] ?? null,
            ':cahier_charges_path' => $data['cahier_charges_path'] ?? null,
            ':date_soumission' => date('Y-m-d H:i:s')
        ]);
        
        return (int) $db->lastInsertId();
    }

    /**
     * Submit an update to an existing submission (re-submission)
     */
    public static function update(int $projectId, array $data): void
    {
        $db = Database::pdo();
        $sql = 'UPDATE projets SET 
                    id_theme = :id_theme,
                    titre_projet = :titre_projet,
                    lien_url = :lien_url,
                    acces_test = :acces_test,
                    explications = :explications,
                    cahier_charges_path = COALESCE(:cahier_charges_path, cahier_charges_path),
                    date_soumission = :date_soumission
                WHERE id_projet = :id_projet';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':id_theme' => $data['id_theme'] ?: null,
            ':titre_projet' => $data['titre_projet'],
            ':lien_url' => $data['lien_url'],
            ':acces_test' => $data['acces_test'],
            ':explications' => $data['explications'] ?? null,
            ':cahier_charges_path' => $data['cahier_charges_path'] ?? null,
            ':date_soumission' => date('Y-m-d H:i:s'),
            ':id_projet' => $projectId
        ]);
    }

    /**
     * Find project by ID
     */
    public static function findById(int $id): ?array
    {
        $db = Database::pdo();
        $sql = 'SELECT p.*, e.titre as exercice_titre, e.type_exercice, t.nom_theme 
                FROM projets p
                JOIN exercices e ON p.id_exercice = e.id_exercice
                LEFT JOIN themes t ON p.id_theme = t.id_theme
                WHERE p.id_projet = ? LIMIT 1';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find project for user/group specific assignment session
     */
     public static function findSubmittedProject(int $exerciceId, int $userId): ?array
     {
         $db = Database::pdo();
         
         $grpStmt = $db->prepare('
             SELECT g.id_groupe FROM groupes g
             LEFT JOIN membres_groupe mg ON g.id_groupe = mg.id_groupe
             WHERE g.id_exercice = ? AND (g.id_chef = ? OR mg.id_user = ?)
             LIMIT 1
         ');
         $grpStmt->execute([$exerciceId, $userId, $userId]);
         $grp = $grpStmt->fetch();
         
         if ($grp !== false) {
             $sql = 'SELECT p.*, t.nom_theme,
                            g.nom_groupe,
                            u_chef.nom as chef_nom, u_chef.prenom as chef_prenom,
                            u_ind.nom as ind_nom, u_ind.prenom as ind_prenom
                     FROM projets p
                     LEFT JOIN themes t ON p.id_theme = t.id_theme
                     LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                     LEFT JOIN utilisateurs u_chef ON g.id_chef = u_chef.id_user
                     LEFT JOIN utilisateurs u_ind ON p.id_user_individuel = u_ind.id_user
                     WHERE p.id_exercice = ? AND p.id_groupe = ? LIMIT 1';
             $stmt = $db->prepare($sql);
             $stmt->execute([$exerciceId, $grp['id_groupe']]);
         } else {
             $sql = 'SELECT p.*, t.nom_theme,
                            g.nom_groupe,
                            u_chef.nom as chef_nom, u_chef.prenom as chef_prenom,
                            u_ind.nom as ind_nom, u_ind.prenom as ind_prenom
                     FROM projets p
                     LEFT JOIN themes t ON p.id_theme = t.id_theme
                     LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                     LEFT JOIN utilisateurs u_chef ON g.id_chef = u_chef.id_user
                     LEFT JOIN utilisateurs u_ind ON p.id_user_individuel = u_ind.id_user
                     WHERE p.id_exercice = ? AND p.id_user_individuel = ? LIMIT 1';
             $stmt = $db->prepare($sql);
             $stmt->execute([$exerciceId, $userId]);
         }
         
         $row = $stmt->fetch();
         return $row ?: null;
     }

    /**
     * Submit evaluation details (Design /5, Code /5, Functionnal. /10) -> computes sum total /20
     */
    public static function grade(int $projectId, float $design, float $code, float $fonc, ?string $critique): void
    {
        $db = Database::pdo();
        $totale = round($design + $code + $fonc, 2);
        
        $sql = 'UPDATE projets SET 
                    note_design = ?,
                    note_code = ?,
                    note_fonc = ?,
                    note_totale = ?,
                    critique_prof = ?
                WHERE id_projet = ?';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([round($design, 2), round($code, 2), round($fonc, 2), $totale, $critique, $projectId]);
    }

    /**
     * Submit a global note (no breakdown) directly to total /20
     */
    public static function gradeGlobal(int $projectId, float $total, ?string $critique): void
    {
        $db = Database::pdo();

        $sql = 'UPDATE projets SET 
                    note_design = NULL,
                    note_code = NULL,
                    note_fonc = NULL,
                    note_totale = ?,
                    critique_prof = ?
                WHERE id_projet = ?';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([round($total, 2), $critique, $projectId]);
    }

    /**
     * Set published status of grades/feedbacks to make them visible to students
     */
    public static function publishGradesForExercise(int $exerciceId): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE projets SET notes_publiees = 1 WHERE id_exercice = ?');
        $stmt->execute([$exerciceId]);
    }

    /**
     * Find all submitted essays for a given exercise
     */
    public static function getSubmissionsForExercise(int $exerciceId): array
    {
        $db = Database::pdo();
        $sql = 'SELECT p.*, t.nom_theme, 
                       g.nom_groupe, u_chef.nom as chef_nom, u_chef.prenom as chef_prenom,
                       u_ind.nom as ind_nom, u_ind.prenom as ind_prenom
                FROM projets p
                LEFT JOIN themes t ON p.id_theme = t.id_theme
                LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                LEFT JOIN utilisateurs u_chef ON g.id_chef = u_chef.id_user
                LEFT JOIN utilisateurs u_ind ON p.id_user_individuel = u_ind.id_user
                WHERE p.id_exercice = ?
                ORDER BY p.date_soumission DESC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$exerciceId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all exercises an student submitted a project for, along with notes if published
     */
    public static function getUserGradesReport(int $userId): array
    {
        $db = Database::pdo();
        
        $sql = 'SELECT p.*, e.titre as exercice_titre, e.type_exercice, t.nom_theme,
                       g.nom_groupe, g.id_chef
                FROM projets p
                JOIN exercices e ON p.id_exercice = e.id_exercice
                LEFT JOIN themes t ON p.id_theme = t.id_theme
                LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                LEFT JOIN membres_groupe mg ON g.id_groupe = mg.id_groupe
                 WHERE (p.id_user_individuel = :user_id1
                        OR g.id_chef = :user_id2
                        OR mg.id_user = :user_id3)
                 AND p.notes_publiees = 1
                 ORDER BY e.date_limite DESC';
         
         $stmt = $db->prepare($sql);
         $stmt->execute([':user_id1' => $userId, ':user_id2' => $userId, ':user_id3' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Set a project public for peer reviews
     */
    public static function setPublicStatus(int $projectId, bool $isPublic): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('UPDATE projets SET statut_public = ? WHERE id_projet = ?');
        $stmt->execute([$isPublic ? 1 : 0, $projectId]);
    }

    /**
     * Find projects marked public for student peer-testing.
     * Rules: deadline must be expired for peer security (prevents copycats!)
     */
    public static function getPublicProjectsForGallery(): array
    {
        $db = Database::pdo();
        $sql = 'SELECT p.id_projet, p.id_exercice, p.id_groupe, p.id_user_individuel, p.titre_projet, p.lien_url, p.explications,
                       p.cahier_charges_path, p.date_soumission, p.note_totale, p.notes_publiees,
                       e.titre as exercice_titre, t.nom_theme,
                       g.nom_groupe, u_chef.prenom as chef_prenom, u_chef.nom as chef_nom,
                       u_ind.prenom as ind_prenom, u_ind.nom as ind_nom
                FROM projets p
                JOIN exercices e ON p.id_exercice = e.id_exercice
                LEFT JOIN themes t ON p.id_theme = t.id_theme
                LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                LEFT JOIN utilisateurs u_chef ON g.id_chef = u_chef.id_user
                LEFT JOIN utilisateurs u_ind ON p.id_user_individuel = u_ind.id_user
                WHERE p.statut_public = 1 
                AND (e.date_limite < NOW() OR e.est_bloque = 1)
                AND COALESCE(e.archive, 0) = 0
                ORDER BY e.date_limite DESC, p.date_soumission DESC';
        
        return $db->query($sql)->fetchAll();
    }

    public static function findPublicProject(int $projectId): ?array
    {
        $db = Database::pdo();
        $sql = 'SELECT p.*, e.titre as exercice_titre, e.type_exercice, e.date_limite, t.nom_theme,
                       g.nom_groupe, g.id_chef, u_chef.prenom as chef_prenom, u_chef.nom as chef_nom,
                       u_ind.prenom as ind_prenom, u_ind.nom as ind_nom
                FROM projets p
                JOIN exercices e ON p.id_exercice = e.id_exercice
                LEFT JOIN themes t ON p.id_theme = t.id_theme
                LEFT JOIN groupes g ON p.id_groupe = g.id_groupe
                LEFT JOIN utilisateurs u_chef ON g.id_chef = u_chef.id_user
                LEFT JOIN utilisateurs u_ind ON p.id_user_individuel = u_ind.id_user
                WHERE p.id_projet = ?
                AND p.statut_public = 1
                AND (e.date_limite < NOW() OR e.est_bloque = 1)
                AND COALESCE(e.archive, 0) = 0
                LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([$projectId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getResponsibleUserIds(array $project): array
    {
        $ids = [];
        if (!empty($project['id_user_individuel'])) {
            $ids[] = (int)$project['id_user_individuel'];
        }

        if (!empty($project['id_groupe'])) {
            $db = Database::pdo();
            $stmt = $db->prepare('
                SELECT id_chef AS id_user FROM groupes WHERE id_groupe = ?
                UNION
                SELECT id_user FROM membres_groupe WHERE id_groupe = ?
            ');
            $stmt->execute([(int)$project['id_groupe'], (int)$project['id_groupe']]);
            foreach ($stmt->fetchAll() as $row) {
                $ids[] = (int)$row['id_user'];
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public static function userIsResponsible(int $projectId, int $userId): bool
    {
        $project = self::findById($projectId);
        if (!$project) {
            return false;
        }

        return in_array($userId, self::getResponsibleUserIds($project), true);
    }
}
