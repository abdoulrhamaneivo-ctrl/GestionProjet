-- --------------------------------------------------------
-- SQL Database Schema for student-hub-php (EMSP Assignment Manager)
-- MD-ARCHIVE: colonne `archive` sur exercices (DEFAULT 0)
-- MD-CHANGEPWD: colonne `first_login` sur utilisateurs (DEFAULT 1)
-- Import this script into phpMyAdmin to create the database and seed initial data.
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `emsp_assignment_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `emsp_assignment_db`;

-- 1. Table: utilisateurs (Users)
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id_user` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(50) NOT NULL,
  `prenom` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` ENUM('etudiant', 'prof', 'admin') NOT NULL,
  `first_login` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: exercices (Assignments / Projects guidelines)
CREATE TABLE IF NOT EXISTS `exercices` (
  `id_exercice` INT AUTO_INCREMENT PRIMARY KEY,
  `titre` VARCHAR(100) NOT NULL,
  `type_exercice` ENUM('individuel', 'groupe') NOT NULL,
  `date_limite` DATETIME NOT NULL,
  `est_bloque` TINYINT(1) DEFAULT 0,
  `corrections_terminees` TINYINT(1) DEFAULT 0,
  `professeur_id` INT DEFAULT NULL,
  `archive` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: themes (Project themes specified by professor / admin)
CREATE TABLE IF NOT EXISTS `themes` (
  `id_theme` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `nom_theme` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: groupes (Student Groups)
CREATE TABLE IF NOT EXISTS `groupes` (
  `id_groupe` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `id_chef` INT NOT NULL,
  `nom_groupe` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE,
  FOREIGN KEY (`id_chef`) REFERENCES `utilisateurs` (`id_user`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table: membres_groupe (Group members)
CREATE TABLE IF NOT EXISTS `membres_groupe` (
  `id_liaison` INT AUTO_INCREMENT PRIMARY KEY,
  `id_groupe` INT NOT NULL,
  `id_user` INT NOT NULL,
  UNIQUE KEY `idx_grp_user` (`id_groupe`, `id_user`),
  FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id_groupe`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Table: derogations (Individual/Group deadline extensions)
CREATE TABLE IF NOT EXISTS `derogations` (
  `id_derogation` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `id_user` INT DEFAULT NULL,
  `id_groupe` INT DEFAULT NULL,
  `nouvelle_date` DATETIME NOT NULL,
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs` (`id_user`) ON DELETE CASCADE,
  FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id_groupe`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Table: exercice_designated_chefs (Admin-designated group leaders per exercise)
CREATE TABLE IF NOT EXISTS `exercice_designated_chefs` (
  `id_liaison` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `id_user` INT NOT NULL,
  UNIQUE KEY `idx_ex_user` (`id_exercice`, `id_user`),
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Table: projets (Project submissions and grades)
CREATE TABLE IF NOT EXISTS `projets` (
  `id_projet` INT AUTO_INCREMENT PRIMARY KEY,
  `id_exercice` INT NOT NULL,
  `id_theme` INT DEFAULT NULL,
  `id_user_individuel` INT DEFAULT NULL,
  `id_groupe` INT DEFAULT NULL,
  `titre_projet` VARCHAR(150) NOT NULL,
  `lien_url` VARCHAR(255) NOT NULL,
  `acces_test` TEXT NOT NULL,
  `explications` TEXT,
  `cahier_charges_path` VARCHAR(255) DEFAULT NULL,
  `note_design` DECIMAL(4,2) DEFAULT NULL,
  `note_code` DECIMAL(4,2) DEFAULT NULL,
  `note_fonc` DECIMAL(4,2) DEFAULT NULL,
  `note_totale` DECIMAL(4,2) DEFAULT NULL,
  `critique_prof` TEXT DEFAULT NULL,
  `notes_publiees` TINYINT(1) DEFAULT 0,
  `statut_public` TINYINT(1) DEFAULT 0,
  `date_soumission` DATETIME NOT NULL,
  FOREIGN KEY (`id_exercice`) REFERENCES `exercices` (`id_exercice`) ON DELETE CASCADE,
  FOREIGN KEY (`id_theme`) REFERENCES `themes` (`id_theme`) ON DELETE SET NULL,
  FOREIGN KEY (`id_user_individuel`) REFERENCES `utilisateurs` (`id_user`) ON DELETE SET NULL,
  FOREIGN KEY (`id_groupe`) REFERENCES `groupes` (`id_groupe`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Table: import_identifiants
CREATE TABLE IF NOT EXISTS `import_identifiants` (
  `id_import` INT AUTO_INCREMENT PRIMARY KEY,
  `id_user` INT NOT NULL,
  `mot_de_passe_clair` VARCHAR(50) NOT NULL,
  `date_import` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs`(`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `commentaires` (
  `id_commentaire` INT AUTO_INCREMENT PRIMARY KEY,
  `id_projet` INT NOT NULL,
  `id_user` INT DEFAULT NULL,
  `pseudonyme` VARCHAR(50) DEFAULT NULL,
  `nom_visiteur` VARCHAR(50) DEFAULT NULL,
  `contenu` TEXT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `est_reponse_responsable` TINYINT(1) DEFAULT 0,
  `statut` ENUM('publie', 'masque') NOT NULL DEFAULT 'publie',
  `date_publication` DATETIME NOT NULL,
  FOREIGN KEY (`id_projet`) REFERENCES `projets`(`id_projet`) ON DELETE CASCADE,
  FOREIGN KEY (`id_user`) REFERENCES `utilisateurs`(`id_user`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_id`) REFERENCES `commentaires`(`id_commentaire`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------
-- SEED INITIAL DATA
-- Default password: 'password123' for all created users.
-- password_hash('password123', PASSWORD_DEFAULT) returns:
-- '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq'
-- --------------------------------------------------------

INSERT INTO `utilisateurs` (`id_user`, `nom`, `prenom`, `email`, `mot_de_passe`, `role`, `first_login`) VALUES
(1, 'Admin', 'Ivob', 'admin@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'admin', 0),
(2, 'Amadou', 'M.', 'amadou@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'prof', 0),
(3, 'Koffi', 'Jean', 'jean.koffi@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'etudiant', 0),
(4, 'Traore', 'Fatoumata', 'fatou.traore@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'etudiant', 0),
(5, 'Diallo', 'Mamadou', 'mamadou.diallo@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'etudiant', 0),
(6, 'Bamba', 'Bakary', 'bakary.bamba@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'etudiant', 0),
(7, 'Gomez', 'Marie', 'marie.gomez@emsp.ci', '$2y$10$eYR4EUencFkb8nCtVncwfuOEpw1E5NcBI2CzLDSlGjHaxbKzxdRQq', 'etudiant', 0);

-- Seed sample Exercice / Theme
INSERT INTO `exercices` (`id_exercice`, `titre`, `type_exercice`, `date_limite`, `est_bloque`, `professeur_id`) VALUES
(1, 'Projet Conception d Application Web', 'groupe', ADDDATE(NOW(), INTERVAL 7 DAY), 0, 2),
(2, 'Mini-Project PHP POO/MVC Individuel', 'individuel', ADDDATE(NOW(), INTERVAL -1 DAY), 0, NULL);

-- Themes for Exercise 1
INSERT INTO `themes` (`id_theme`, `id_exercice`, `nom_theme`) VALUES
(1, 1, 'Plateforme E-Commerce EMSP'),
(2, 1, 'Système de gestion de stock de matériel'),
(3, 1, 'Annuaire numérique des étudiants DSER'),
(4, 1, 'Portail de vote électronique du club informatique');

-- Themes for Exercise 2
INSERT INTO `themes` (`id_theme`, `id_exercice`, `nom_theme`) VALUES
(5, 2, 'Générateur de fiches d évaluation PDF'),
(6, 2, 'Raccourcisseur d URL sécurisé');
