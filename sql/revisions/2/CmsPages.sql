ALTER TABLE `CmsPages`
DROP FOREIGN KEY `CmsPages_ibfk_4`,
ADD FOREIGN KEY (`ContentId`) REFERENCES `Content` (`Id`) ON DELETE SET NULL ON UPDATE NO ACTION;