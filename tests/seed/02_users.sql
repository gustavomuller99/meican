-- =============================================================================
-- SEED: Users and RBAC assignments
-- Creates one Admin user per domain (id=1 master already seeded by migration)
-- Password hash = bcrypt of "password123"
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing seed data (safe to re-run), preserve master user (id=1)
DELETE FROM `meican_auth_assignment` WHERE `user_id` IN (2, 3, 4);
DELETE FROM `meican_user_domain` WHERE `id` IN (2, 3, 4);
DELETE FROM `meican_user_settings` WHERE `id` IN (2, 3, 4);
DELETE FROM `meican_user` WHERE `id` IN (2, 3, 4);

-- -----------------------------------------------------------------------------
-- Users
-- Profile fields (email, name, language, date_format, time_format, time_zone)
-- moved from meican_user_settings into meican_user in m151223_140008_mqg
-- -----------------------------------------------------------------------------
INSERT INTO `meican_user` (`id`, `login`, `password`, `authkey`, `email`, `name`, `language`, `date_format`, `time_format`, `time_zone`) VALUES
(2, 'admin.rnp',   '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-rnp-00000000001', 'admin@rnp.br',    'RNP Admin',   'en-US', 'mm/dd/yyyy', 'HH:mm', 'America/Sao_Paulo'),
(3, 'admin.geant', '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-geant-000000002', 'admin@geant.net', 'GEANT Admin', 'en-US', 'mm/dd/yyyy', 'HH:mm', 'Europe/Amsterdam'),
(4, 'admin.esnet', '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-esnet-000000003', 'admin@es.net',    'ESnet Admin', 'en-US', 'mm/dd/yyyy', 'HH:mm', 'America/Chicago');

-- -----------------------------------------------------------------------------
-- User settings (only id + topo_viewer remain after m151223_140008_mqg drops)
-- -----------------------------------------------------------------------------
INSERT INTO `meican_user_settings` (`id`) VALUES (2), (3), (4);

-- -----------------------------------------------------------------------------
-- user_domain rows
-- Final columns after migrations: id, user_id, domain (domain_id was dropped)
-- domain column references meican_domain.name (FK)
-- -----------------------------------------------------------------------------
INSERT INTO `meican_user_domain` (`id`, `user_id`, `domain`) VALUES
(2, 2, 'rnp.br'),
(3, 3, 'geant.net'),
(4, 4, 'es.net');

-- -----------------------------------------------------------------------------
-- RBAC assignments — assign each user_domain record to the Admin group (g2)
-- meican_auth_assignment.user_id references meican_user_domain.id
-- g2 = Admin (DOMAIN-scoped group, seeded in m150901_152747_diego migration)
-- -----------------------------------------------------------------------------
INSERT INTO `meican_auth_assignment` (`item_name`, `user_id`, `created_at`) VALUES
('g2', 2, UNIX_TIMESTAMP()),
('g2', 3, UNIX_TIMESTAMP()),
('g2', 4, UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
