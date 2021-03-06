CREATE TABLE `Images` (
  `Id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'PK',
  `ContentId` int(11) unsigned NOT NULL COMMENT 'FK',
  `UploadedFileId` int(11) unsigned NOT NULL COMMENT 'FK',
  `Name` varchar(100) COLLATE utf8_czech_ci NOT NULL,
  `Description` text COLLATE utf8_czech_ci NOT NULL,
  `Exposition` int(10) unsigned DEFAULT NULL COMMENT 'FK',
  PRIMARY KEY (`Id`),
  KEY `ContentId` (`ContentId`),
  KEY `UploadedFileId` (`UploadedFileId`),
  CONSTRAINT `Images_ibfk_4` FOREIGN KEY (`UploadedFileId`) REFERENCES `UploadedFiles` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `Images_ibfk_3` FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci