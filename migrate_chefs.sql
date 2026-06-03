USE `emsp_assignment_db`;

-- 1. Vérifier la table
SET @table_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exercice_designated_chefs'
);

-- 2. Vérifier si l'utilisateur 3 (ex: KAKA / Jean Koffi) est déjà chef pour l'exercice 1
SET @already_designated = (
  SELECT COUNT(*) FROM exercice_designated_chefs
  WHERE id_exercice = 1 AND id_user = 3
);

-- 3. Si la table existe ET que le chef n'est pas encore désigné, on l'insère
SET @sql = IF(
  (@table_exists > 0 AND @already_designated = 0),
  'INSERT IGNORE INTO exercice_designated_chefs (id_exercice, id_user) VALUES (1, 3)',
  'SELECT 1'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Contrôle visuel
SELECT d.id_liaison, d.id_exercice, d.id_user, u.nom, u.prenom, u.email
FROM exercice_designated_chefs d
JOIN utilisateurs u ON u.id_user = d.id_user
WHERE d.id_exercice = 1;
