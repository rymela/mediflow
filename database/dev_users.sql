-- MediFlow — Magazine module dev accounts
-- Import into the existing `mediflow` database AFTER the User module SQL has been imported.
-- All passwords below are bcrypt hashes. Plaintext credentials shown in comments (dev only).
--
-- Credentials:
--   Magazine Admin : magazine@mediflow.com  / magazine123
--   Extra Admin    : admin2@mediflow.com    / admin123
--
-- NOTE: The main admin (admin@mediflow.com / admin123) is already seeded by the User module SQL.
--       The accounts below are supplementary dev accounts for the Magazine module only.

-- Magazine editor account (id_role 6 = 'Magazine')
INSERT INTO `utilisateurs` (`nom`, `prenom`, `mail`, `motdp`, `tel`, `adresse`, `id_role`)
VALUES ('Editor', 'Magazine', 'magazine@mediflow.com',
        '$2y$10$UdzKwm0K/ialXg2LziIbm.VWkLqkGVmjG8Nm7G54Ig3CYhUoEZIr.',
        '+216123456789', 'Tunis, Tunisia', 6)
ON DUPLICATE KEY UPDATE
  `nom`     = VALUES(`nom`),
  `prenom`  = VALUES(`prenom`),
  `motdp`   = VALUES(`motdp`),
  `tel`     = VALUES(`tel`),
  `adresse` = VALUES(`adresse`),
  `id_role` = VALUES(`id_role`);

-- Extra Admin account (id_role 1 = 'Admin')
INSERT INTO `utilisateurs` (`nom`, `prenom`, `mail`, `motdp`, `tel`, `adresse`, `id_role`)
VALUES ('Admin', 'Default', 'admin2@mediflow.com',
        '$2y$10$joYI.WSlA4PDNtFfapOaA.kh/i1jauVHj3qaoczcyOfIMV7fIiz6u',
        '+216987654321', 'Tunis, Tunisia', 1)
ON DUPLICATE KEY UPDATE
  `nom`     = VALUES(`nom`),
  `prenom`  = VALUES(`prenom`),
  `motdp`   = VALUES(`motdp`),
  `tel`     = VALUES(`tel`),
  `adresse` = VALUES(`adresse`),
  `id_role` = VALUES(`id_role`);
