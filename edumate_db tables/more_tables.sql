START TRANSACTION;

CREATE TABLE IF NOT EXISTS `subject_materials` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `teacher_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `external_link` VARCHAR(500) DEFAULT NULL,
  `accessibility_features` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`accessibility_features`)),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  CONSTRAINT `subject_materials_fk_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  CONSTRAINT `subject_materials_fk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `open_resources` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `resource_link` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  CONSTRAINT `open_resources_fk_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
