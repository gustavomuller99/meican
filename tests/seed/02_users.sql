-- =============================================================================
-- SEED: Users and RBAC assignments
-- Creates one Admin user per domain (id=1 master already seeded by migration)
-- Password "password123"
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM `meican_auth_assignment` WHERE `user_id` IN (2, 3, 4);
DELETE FROM `meican_user_domain` WHERE `id` IN (2, 3, 4);
DELETE FROM `meican_user_settings` WHERE `id` IN (2, 3, 4);
DELETE FROM `meican_user` WHERE `id` IN (2, 3, 4);

INSERT INTO `meican_user` (`id`, `login`, `password`, `authkey`, `email`, `name`, `language`, `date_format`, `time_format`, `time_zone`, `blockchain_address`, `blockchain_private_key`) VALUES
(2, 'admin.rnp',   '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-rnp-00000000001', 'admin@rnp.br',    'RNP Admin',   'en-US', 'mm/dd/yyyy', 'HH:mm', 'America/Sao_Paulo', '0x70997970c51812dc3a010c7d01b50e0d17dc79c8', '0x59c6995e998f97a5a0044966f0945389dc9e86dae88c7a8412f4603b6b78690d'),
(3, 'admin.geant', '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-geant-000000002', 'admin@geant.net', 'GEANT Admin', 'en-US', 'mm/dd/yyyy', 'HH:mm', 'Europe/Amsterdam',  '0x3c44cdddb6a900fa2b585dd299e03d12fa4293bc', '0x5de4111afa1a4b94908f83103eb1f1706367c2e68ca870fc3fb9a804cdab365a'),
(4, 'admin.esnet', '$2y$13$HlKOEje1Mtckn79tYjdLAOmzSC7unR/RJg6O9mz42hggMuX.3TuPq', 'authkey-admin-esnet-000000003', 'admin@es.net',    'ESnet Admin', 'en-US', 'mm/dd/yyyy', 'HH:mm', 'America/Chicago',    '0x90f79bf6eb2c4f870365e785982e1f101e93b906', '0x7c852118294e51e653712a81e05800f419141751be58f605c371e15141b007a6');

INSERT INTO `meican_user_settings` (`id`) VALUES (2), (3), (4);

INSERT INTO `meican_user_domain` (`id`, `user_id`, `domain`) VALUES
(2, 2, 'rnp.br'),
(3, 3, 'geant.net'),
(4, 4, 'es.net');

INSERT INTO `meican_auth_assignment` (`item_name`, `user_id`, `created_at`) VALUES
('g2', 2, UNIX_TIMESTAMP()),
('g2', 3, UNIX_TIMESTAMP()),
('g2', 4, UNIX_TIMESTAMP());

SET FOREIGN_KEY_CHECKS = 1;
