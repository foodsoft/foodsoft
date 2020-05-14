-- MySQL dump 10.17  Distrib 10.3.22-MariaDB, for debian-linux-gnueabihf (armv8l)
--
-- Host: localhost    Database: guteluise
-- ------------------------------------------------------
-- Server version	10.3.22-MariaDB-0+deb10u1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `catalogue_acronyms`
--

DROP TABLE IF EXISTS `catalogue_acronyms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catalogue_acronyms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `context` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `acronym` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `definition` text COLLATE utf8_unicode_ci NOT NULL,
  `comment` text COLLATE utf8_unicode_ci NOT NULL,
  `url` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secondary` (`context`,`acronym`)
) ENGINE=MyISAM AUTO_INCREMENT=166 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalogue_acronyms`
--

LOCK TABLES `catalogue_acronyms` WRITE;
/*!40000 ALTER TABLE `catalogue_acronyms` DISABLE KEYS */;
INSERT INTO `catalogue_acronyms` VALUES (1,'hst','GRE','Green','',''),(2,'hrk','FR','Frankreich','',''),(3,'hrk','DE','Deutschland','',''),(4,'vbd','DD','Demeter','',''),(5,'vbd','DB','Bioland','',''),(6,'vbd','DC','Ecoland','',''),(7,'vbd','DG','Gäa','',''),(8,'vbd','DK','Biokreis','',''),(9,'vbd','DN','Naturland','',''),(10,'vbd','DP','Biopark','',''),(11,'vbd','DW','Ecovin','',''),(12,'vbd','IA','IFOAM','IFOAM-Akkreditierung','http://www.ioas.org/xlistifo.pdf'),(13,'vbd','ID','Demeter (int.)','Demeter-International','http://www.demeter.net/certification/ce_statistics.php?languagechoice=en'),(14,'vbd','EG','EG-Bio (100%)','',''),(15,'vbd','C%','EG-Bio (&gt;95%)','',''),(16,'vbd','##','nicht zertifiziert','&lt; 95% Bio',''),(17,'vbd','S#','Naturkost-Wild','','http://www.bnn-einzelhandel.de/downloads/sortimentsrichtlinien.pdf'),(18,'hrk','GR','Griechenland','',''),(19,'hrk','PT','Portugal','',''),(20,'hrk','REG','** regional **','',''),(21,'hrk','NL','Niederlande','',''),(22,'hrk','HU','Ungarn','',''),(23,'hrk','RO','Rumänien','',''),(24,'hrk','IT','Italien','',''),(25,'hrk','BE','Belgien','',''),(26,'hrk','ES','Spanien','',''),(27,'hrk','MA','Marokko','',''),(28,'hrk','CN','China','',''),(29,'hrk','AR','Argentinien','',''),(30,'hrk','IL','Israel','',''),(31,'hrk','BO','Bolivien','',''),(32,'hrk','BF','Burkina Faso','',''),(33,'hrk','AT','Österreich','',''),(34,'hrk','LK','Sri Lanka','',''),(35,'hrk','CH','Schweiz','',''),(36,'hrk','DO','Dominikanische Republik','',''),(37,'hrk','TN','Tunesien','',''),(38,'hrk','PE','Peru','',''),(39,'hrk','SE','Schweden','',''),(40,'hrk','US','USA','',''),(41,'hrk','CA','Kanada','',''),(42,'hrk','TR','Türkei','',''),(43,'hrk','IN','Indien','',''),(44,'hrk','SD','Sudan','',''),(45,'hrk','MX','Mexiko','',''),(46,'hst','WIK','Wikana','',''),(47,'hst','GEP','gepa','',''),(48,'hst','GRN','Green','',''),(49,'hst','ALS','Alsan','',''),(50,'hst','BPL','Bio Planete','',''),(51,'hst','ZWE','Zwergenwiese','',''),(52,'hst','TER','Terrasana','',''),(53,'hst','HOR','Horizon','',''),(54,'hst','CHR','Chocoreale','',''),(55,'hst','TAR','Tarpa','',''),(56,'hst','TAI','Taifun','',''),(57,'hst','SOD','Sodasan','',''),(58,'hst','bmf','Braumanufaktur','',''),(59,'hst','NTM','Natumi','',''),(60,'hst','NAG','Nagel','',''),(61,'hst','BAK','Bauckhof','',''),(62,'hst','lob','Lobetal','',''),(63,'hst','SBG','Schrozberg','',''),(64,'hst','ÖBW','Brodowin','',''),(65,'hst','GLM','Gläserne Molkerei Münchehofe','',''),(66,'hst','VOE','Völkel','',''),(67,'hst','OAT','Oatly','',''),(68,'hst','SOJ','Sojade','',''),(69,'hst','PRV','Provamel','',''),(70,'hst','LGP','Landgut Pretschen','',''),(71,'hst','DAV','Davert','',''),(72,'hst','NAT','Naturata','',''),(73,'hst','BIF','Biofrisch','',''),(74,'hst','BTR','Biotropic','',''),(75,'hst','SCH','Naturkost Schramm','',''),(76,'hst','MGN','Marktgenossenschaft Naturland-Bauern','',''),(77,'hst','ELM','Elm','',''),(78,'hst','eca','ei care','','http://www.terra-natur.com/service/aktuell/Biofach/Best_New_Product_ei_care.php'),(79,'hst','EVS','Evers Naturkost','',''),(80,'hst','SWZ','Schweizer Sauerkrautfabrik','Filderstadt',''),(81,'hst','BAS','Bastiaansen','',''),(82,'hst','mrh','Marienhöhe','',''),(83,'hst','MOR','Morgenland','',''),(84,'hst','BOL','Bohlsener Mühle','',''),(85,'hst','LEB','Lebensbaum','',''),(86,'hst','ARC','Arche','',''),(87,'hst','BYO','Byodo','',''),(88,'hrk','JA','Japan','',''),(89,'hrk','VN','Vietnam','',''),(90,'hst','SOM','Sommer','',''),(91,'hst','HOL','Holle','',''),(92,'hst','ISA','bio-verde','',''),(93,'hst','SPI','Spielberger','',''),(94,'hst','SPH','Spreewälder Hirsemühle','',''),(95,'hst','NHU','Natur Hurtig','',''),(96,'hst','YAK','Yakso','',''),(97,'hst','MAN','Mani Bläuel','',''),(98,'hst','LBI','La Bio Idea','',''),(99,'hst','BFS','bruno fischer','',''),(100,'hst','DET','Märkisches Landbrot','',''),(101,'hst','TNH','Terra Naturkost','',''),(102,'hst','SÖB','Söbbeke','',''),(103,'hst','HKK','Hekking','',''),(104,'hst','nal','Naturland','',''),(105,'hst','VNI','Vivani','',''),(106,'hst','WED','Werder Feinkost','',''),(107,'hst','GÜS','Güstrower Schlossquell','',''),(108,'hst','BOA','Bio-Obst Augustin','','http://www.bioaugustin.de'),(109,'hst','bro','Ökodorf Brodowin','','http://www.brodowin.de/'),(110,'hst','AND','Andechser','',''),(111,'hst','BGL','Berchtesgadener Land','',''),(112,'hst','ÖMA','ÖMA','',''),(113,'hst','BDH','Hof Butendiek','',''),(114,'hst','ROS','Rosengarten','',''),(115,'hst','BHO','Barnhouse','',''),(116,'hst','WHS','Weißenhorner','',''),(117,'hst','STN','Sonnentor','',''),(118,'hst','HEU','Heuschrecke','',''),(119,'hrk','NZ','Neuseeland','',''),(120,'hst','LNA','Linea Natura','',''),(121,'hst','CHI','Chiemgauer Naturfleisch','',''),(122,'hst','bmi','Blütenmeer Imkerei','',''),(123,'hst','ALO','Allos','',''),(124,'hst','AFE','Annes Feinste','',''),(125,'hst','NCL','Natural Cool','',''),(126,'hst','PAN','Pasta Nuova','',''),(127,'hst','WIO','Wiona','',''),(128,'hst','NAC','Natracare','',''),(129,'hst','ECV','Ecover','',''),(130,'hst','LAV','Lavera','',''),(131,'hst','SAN','Sante','',''),(132,'hst','TAP','Tapir','',''),(133,'hst','LOG','Logona','',''),(134,'hst','CMD','CMD','',''),(135,'hst','ÖKL','Ökoland','',''),(136,'hst','FWM','Biomanufaktur Velten','',''),(137,'hst','mar','Marschland','',''),(138,'hst','BND','Bionade','',''),(139,'hst','RIE','Riegel Weinimport','',''),(140,'hst','NEU','Neumarkter Lammsbräu','',''),(141,'hst','PIN','Pinkus','',''),(142,'hst','RBB','Riedenburger','',''),(143,'hst','VLV','VivoLoVin','',''),(144,'hst','BIV','Biovita','',''),(145,'hst','WHE','Wheaty','',''),(146,'hst','FON','Fontaine','',''),(147,'hst','NBO','Nürnberger Bio Originale','',''),(148,'hst','VAV','Vallée Verte','',''),(149,'hst','SOF','Soto','',''),(150,'hst','RIT','De Rit','',''),(151,'hst','ERN','Erntesegen','',''),(152,'hst','SAC','Sanchon','',''),(153,'hst','LIM','Lima','',''),(154,'hst','SLC','Svenska LantChips','',''),(155,'hst','LAN','Landkrone','',''),(156,'hst','DAN','Danival','','http://www.danival.fr'),(157,'hst','ERD','Erdmann-Hauser','',''),(158,'hst','GNH','Green Heart','',''),(159,'hst','EDN','Eden','',''),(160,'hst','MKI','Monki','',''),(161,'hst','FRT','Florentin','',''),(162,'hst','SFR','Saucenfritz','',''),(163,'hst','FPF','Freiland Puten Fahrenzhausen','',''),(164,'hst','MT','Maintal Konfitüren GmbH','','https://maintal-konfitueren.de/'),(165,'hst','NP','nur puur','','http://www.nurpuurbio.de/');
/*!40000 ALTER TABLE `catalogue_acronyms` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-05-11 11:51:19
