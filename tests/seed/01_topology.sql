-- =============================================================================
-- SEED: Topology data (simulates DiscoveryService output)
-- 3 domains: RNP (Brazil), GÉANT (Europe), ESnet (US)
-- Each domain: 1 provider, 1 network, 2 devices, 4 ports (BI), 2 locations
-- Peerings between all three domains
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing seed data (safe to re-run)
TRUNCATE TABLE `meican_topo_synchronizer`;
TRUNCATE TABLE `meican_provider_peering`;
TRUNCATE TABLE `meican_port`;
TRUNCATE TABLE `meican_network`;
TRUNCATE TABLE `meican_location`;
TRUNCATE TABLE `meican_service`;
TRUNCATE TABLE `meican_domain`;
TRUNCATE TABLE `meican_provider`;

INSERT INTO `meican_provider` (`id`, `type`, `name`, `nsa`, `latitude`, `longitude`, `domain_id`) VALUES
(1, 'UPA', 'RNP',   'urn:ogf:network:rnp.br:2013:nsa',       -15.7801,  -47.9292,   1),
(2, 'UPA', 'GEANT', 'urn:ogf:network:geant.net:2013:nsa',     48.8566,    2.3522,   2),
(3, 'UPA', 'ESnet', 'urn:ogf:network:es.net:2013:nsa',        37.7749, -122.4194,   3);

INSERT INTO `meican_domain` (`id`, `name`, `default_policy`) VALUES
(1, 'rnp.br',    'ACCEPT_ALL'),
(2, 'geant.net', 'ACCEPT_ALL'),
(3, 'es.net',    'ACCEPT_ALL');

INSERT INTO `meican_service` (`id`, `provider_id`, `type`, `url`) VALUES
(1, 1, 'NSI_CSP_2_0', 'http://nsi.rnp.br:8080/nsi-v2/ConnectionServiceProvider'),
(2, 1, 'NSI_TD_2_0',  'http://nsi.rnp.br:8080/nsi-v2/topology'),
(3, 2, 'NSI_CSP_2_0', 'http://nsi.geant.net:8080/nsi-v2/ConnectionServiceProvider'),
(4, 2, 'NSI_TD_2_0',  'http://nsi.geant.net:8080/nsi-v2/topology'),
(5, 3, 'NSI_CSP_2_0', 'http://nsi.es.net:8080/nsi-v2/ConnectionServiceProvider'),
(6, 3, 'NSI_TD_2_0',  'http://nsi.es.net:8080/nsi-v2/topology');

INSERT INTO `meican_location` (`id`, `name`, `lat`, `lng`, `domain_id`) VALUES
(1, 'Sao Paulo',      -23.5505, -46.6333, 1),
(2, 'Rio de Janeiro', -22.9068, -43.1729, 1),
(3, 'Amsterdam',       52.3676,   4.9041, 2),
(4, 'Frankfurt',       50.1109,   8.6821, 2),
(5, 'Chicago',         41.8781, -87.6298, 3),
(6, 'New York',        40.7128, -74.0060, 3);

INSERT INTO `meican_network` (`id`, `name`, `latitude`, `longitude`, `domain_id`, `urn`, `version`) VALUES
(1, 'RNP Backbone',   -15.7801,  -47.9292, 1, 'urn:ogf:network:rnp.br:2013:topology',    NOW()),
(2, 'GEANT Backbone',  48.8566,    2.3522, 2, 'urn:ogf:network:geant.net:2013:topology',  NOW()),
(3, 'ESnet Backbone',  37.7749, -122.4194, 3, 'urn:ogf:network:es.net:2013:topology',     NOW());

INSERT INTO `meican_port` (`id`, `type`, `directionality`, `urn`, `name`, `lat`, `lng`, `max_capacity`, `min_capacity`, `granularity`, `vlan_range`, `biport_id`, `alias_id`, `network_id`, `location_id`) VALUES
-- RNP Sao Paulo
(1,  'NSI', 'BI', 'urn:ogf:network:rnp.br:2013:saopaulo-r1:eth0',     'eth0', -23.5505, -46.6333, 10000000, 1000, 1000, '1000-1099', NULL, NULL, 1, 1),
(2,  'NSI', 'BI', 'urn:ogf:network:rnp.br:2013:saopaulo-r1:eth1',     'eth1', -23.5505, -46.6333, 10000000, 1000, 1000, '1000-1099', NULL, NULL, 1, 1),
-- RNP Rio de Janeiro
(3,  'NSI', 'BI', 'urn:ogf:network:rnp.br:2013:riodejaneiro-r1:eth0', 'eth0', -22.9068, -43.1729, 10000000, 1000, 1000, '1100-1199', NULL, NULL, 1, 2),
(4,  'NSI', 'BI', 'urn:ogf:network:rnp.br:2013:riodejaneiro-r1:eth1', 'eth1', -22.9068, -43.1729, 10000000, 1000, 1000, '1100-1199', NULL, NULL, 1, 2),
-- GÉANT Amsterdam
(5,  'NSI', 'BI', 'urn:ogf:network:geant.net:2013:amsterdam-r1:eth0', 'eth0',  52.3676,   4.9041, 10000000, 1000, 1000, '2000-2099', NULL, NULL, 2, 3),
(6,  'NSI', 'BI', 'urn:ogf:network:geant.net:2013:amsterdam-r1:eth1', 'eth1',  52.3676,   4.9041, 10000000, 1000, 1000, '2000-2099', NULL, NULL, 2, 3),
-- GÉANT Frankfurt
(7,  'NSI', 'BI', 'urn:ogf:network:geant.net:2013:frankfurt-r1:eth0', 'eth0',  50.1109,   8.6821, 10000000, 1000, 1000, '2100-2199', NULL, NULL, 2, 4),
(8,  'NSI', 'BI', 'urn:ogf:network:geant.net:2013:frankfurt-r1:eth1', 'eth1',  50.1109,   8.6821, 10000000, 1000, 1000, '2100-2199', NULL, NULL, 2, 4),
-- ESnet Chicago
(9,  'NSI', 'BI', 'urn:ogf:network:es.net:2013:chicago-r1:eth0',      'eth0',  41.8781, -87.6298, 10000000, 1000, 1000, '3000-3099', NULL, NULL, 3, 5),
(10, 'NSI', 'BI', 'urn:ogf:network:es.net:2013:chicago-r1:eth1',      'eth1',  41.8781, -87.6298, 10000000, 1000, 1000, '3000-3099', NULL, NULL, 3, 5),
-- ESnet New York
(11, 'NSI', 'BI', 'urn:ogf:network:es.net:2013:newyork-r1:eth0',      'eth0',  40.7128, -74.0060, 10000000, 1000, 1000, '3100-3199', NULL, NULL, 3, 6),
(12, 'NSI', 'BI', 'urn:ogf:network:es.net:2013:newyork-r1:eth1',      'eth1',  40.7128, -74.0060, 10000000, 1000, 1000, '3100-3199', NULL, NULL, 3, 6);

UPDATE `meican_port` SET `alias_id` = 5  WHERE `id` = 2;  -- rnp-sp:eth1    <-> geant-ams:eth0
UPDATE `meican_port` SET `alias_id` = 2  WHERE `id` = 5;
UPDATE `meican_port` SET `alias_id` = 8  WHERE `id` = 10; -- esnet-chi:eth1 <-> geant-fra:eth1
UPDATE `meican_port` SET `alias_id` = 10 WHERE `id` = 8;
UPDATE `meican_port` SET `alias_id` = 11 WHERE `id` = 4;  -- rnp-rj:eth1    <-> esnet-ny:eth0
UPDATE `meican_port` SET `alias_id` = 4  WHERE `id` = 11;

INSERT INTO `meican_provider_peering` (`src_id`, `dst_id`) VALUES
(1, 2), (2, 1),
(2, 3), (3, 2),
(1, 3), (3, 1);

INSERT INTO `meican_topo_synchronizer` (`id`, `name`, `protocol`, `type`, `auto_apply`, `url`, `subscription_id`, `provider_nsa`) VALUES
(1, 'RNP Sync',   'NSI_DS_1_0', 'NSI_TD_2_0_NSAD_1_0', 1, 'http://nsi.rnp.br:8080/nsi-v2/discovery',    NULL, 'urn:ogf:network:rnp.br:2013:nsa'),
(2, 'GEANT Sync', 'NSI_DS_1_0', 'NSI_TD_2_0_NSAD_1_0', 1, 'http://nsi.geant.net:8080/nsi-v2/discovery', NULL, 'urn:ogf:network:geant.net:2013:nsa'),
(3, 'ESnet Sync', 'NSI_DS_1_0', 'NSI_TD_2_0_NSAD_1_0', 1, 'http://nsi.es.net:8080/nsi-v2/discovery',    NULL, 'urn:ogf:network:es.net:2013:nsa');

SET FOREIGN_KEY_CHECKS = 1;
