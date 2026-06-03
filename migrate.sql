USE `emsp_assignment_db`;

SET @dbname = DATABASE();

-- 1. Colonne archive sur exercices
SET @tablename = 'exercices';
SET @columnname = 'archive';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE exercices ADD COLUMN archive TINYINT(1) DEFAULT 0'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Colonne first_login sur utilisateurs
SET @tablename = 'utilisateurs';
SET @columnname = 'first_login';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE utilisateurs ADD COLUMN first_login TINYINT(1) DEFAULT 0'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 3. Chefs de groupe designes par exercice
CREATE TABLE IF NOT EXISTS `exercice_designated_chefs` (
  `id_liaison` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `id_user` INT NOT NULL,
  UNIQUE KEY `idx_ex_user` (`id_exercice`, `id_user`),
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Demandes de reinitialisation de mot de passe
CREATE TABLE IF NOT EXISTS `password_reset_requests` (
  `id_request` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `statut` ENUM('pending', 'resolved') NOT NULL DEFAULT 'pending',
  `generated_password` VARCHAR(50) DEFAULT NULL,
  `resolved_by` INT DEFAULT NULL,
  `date_demande` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_resolution` DATETIME DEFAULT NULL,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`resolved_by`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Extension communautaire des commentaires
ALTER TABLE `commentaires` MODIFY `id_user` INT NULL;

SET @tablename = 'commentaires';

SET @columnname = 'nom_visiteur';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE commentaires ADD COLUMN nom_visiteur VARCHAR(50) DEFAULT NULL AFTER pseudonyme'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'parent_id';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE commentaires ADD COLUMN parent_id INT DEFAULT NULL AFTER contenu'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'est_reponse_responsable';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE commentaires ADD COLUMN est_reponse_responsable TINYINT(1) DEFAULT 0 AFTER parent_id'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @columnname = 'statut';
SET @preparedStatement = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @columnname) > 0,
        'SELECT 1',
        'ALTER TABLE commentaires ADD COLUMN statut ENUM(''publie'', ''masque'') NOT NULL DEFAULT ''publie'' AFTER est_reponse_responsable'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 6. Notifications internes
CREATE TABLE IF NOT EXISTS `notifications` (
  `id_notification` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `titre` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `lien_url` VARCHAR(255) DEFAULT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE,
  INDEX `idx_notifications_user_lu` (`id_user`, `lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Nettoyage des anciennes notes globales qui avaient garde des criteres a 0
UPDATE `projets`
SET `note_design` = NULL, `note_code` = NULL, `note_fonc` = NULL
WHERE `note_totale` IS NOT NULL
  AND COALESCE(`note_design`, 0) = 0
  AND COALESCE(`note_code`, 0) = 0
  AND COALESCE(`note_fonc`, 0) = 0;
