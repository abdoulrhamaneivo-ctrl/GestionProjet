<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Comment
{
    /**
     * Add peer-testing feedback comment
     */
    public static function create(int $projectId, int $userId, ?string $pseudonyme, string $contenu): void
    {
        $db = Database::pdo();
        $sql = 'INSERT INTO commentaires (id_projet, id_user, pseudonyme, contenu, date_publication, statut) 
                VALUES (?, ?, ?, ?, ?, ?)';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $projectId,
            $userId,
            $pseudonyme ?: null,
            $contenu,
            date('Y-m-d H:i:s'),
            'publie'
        ]);
    }

    public static function createPublic(int $projectId, ?int $userId, string $visitorName, string $content, ?int $parentId = null, bool $isOwnerReply = false): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare('
            INSERT INTO commentaires (
                id_projet, id_user, pseudonyme, nom_visiteur, contenu, parent_id,
                est_reponse_responsable, statut, date_publication
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $projectId,
            $userId,
            $userId !== null ? $visitorName : null,
            $userId === null ? $visitorName : null,
            $content,
            $parentId,
            $isOwnerReply ? 1 : 0,
            'publie',
            date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get comments of a project
     */
    public static function getCommentsByProject(int $projectId): array
    {
        $db = Database::pdo();
        $sql = 'SELECT c.*, u.nom as user_nom, u.prenom as user_prenom 
                FROM commentaires c
                LEFT JOIN utilisateurs u ON c.id_user = u.id_user
                WHERE c.id_projet = ?
                AND COALESCE(c.statut, "publie") = "publie"
                ORDER BY c.date_publication ASC';
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$projectId]);
        return $stmt->fetchAll();
    }

    public static function getThreadByProject(int $projectId): array
    {
        $comments = self::getCommentsByProject($projectId);
        $roots = [];
        $children = [];

        foreach ($comments as $comment) {
            $parentId = $comment['parent_id'] ?? null;
            if ($parentId === null) {
                $roots[(int)$comment['id_commentaire']] = $comment + ['replies' => []];
            } else {
                $children[(int)$parentId][] = $comment;
            }
        }

        foreach ($children as $parentId => $replies) {
            if (isset($roots[$parentId])) {
                $roots[$parentId]['replies'] = $replies;
            }
        }

        return array_values($roots);
    }

    public static function countByProject(int $projectId): int
    {
        $db = Database::pdo();
        $stmt = $db->prepare('
            SELECT COUNT(*)
            FROM commentaires
            WHERE id_projet = ?
            AND COALESCE(statut, "publie") = "publie"
        ');
        $stmt->execute([$projectId]);
        return (int)$stmt->fetchColumn();
    }

    public static function countRecentFromSession(string $sessionKey, int $seconds = 20): bool
    {
        $last = $_SESSION[$sessionKey] ?? null;
        return $last !== null && (time() - (int)$last) < $seconds;
    }
}
