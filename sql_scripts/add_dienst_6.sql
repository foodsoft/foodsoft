ALTER TABLE `gruppenmitglieder` CHANGE `diensteinteilung` `diensteinteilung` ENUM('1/2','3','4','5','6','freigestellt') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'freigestellt';
ALTER TABLE `dienste` CHANGE `dienst` `dienst` ENUM('1/2','3','4','5','6','freigestellt') CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
