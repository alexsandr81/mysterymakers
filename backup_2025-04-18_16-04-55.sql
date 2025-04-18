-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: mysterymakers_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `action` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_logs`
--

LOCK TABLES `admin_logs` WRITE;
/*!40000 ALTER TABLE `admin_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT 'admin',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (8,'alex','alexsandr81@ukr.net','$2y$10$NIdc2EJupR0C15EOhSPk9Oyhd4V/GadmETtlZQZjJFwSB8R4jrU92','2025-03-19 14:00:01','superadmin'),(9,'alexsandr81','alexsan@ukr.net','$2y$10$E8D84VRT0Dn.Pnsvy/6h2uRRGtxYfBAmmZYAVPKV5hrCR3MgT7kmC','2025-04-03 13:24:48','admin');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (4,'Мини-бары','Мини-бары','Описание категории Мини-бары','Мини-бары','mini-bary'),(5,'День святого Валентина','День святого Валентина','Описание категории День святого Валентина','День,святого,Валентина','den-svyatogo-valentina'),(6,'Новогодние товары','Новогодние товары','Описание категории Новогодние товары','Новогодние,товары','novogodnie-tovary'),(7,'Свадьба','Свадьба','Описание категории Свадьба','Свадьба','svadba'),(8,'??????????????','??????????????','Описание категории ??????????????','??????????????',''),(9,'Органайзеры','Органайзеры','Описание категории Органайзеры','Органайзеры','organayzery'),(10,'Копилки','Копилки','Описание категории Копилки','Копилки','kopilki'),(14,'ж','ж','Описание категории ж','ж','zh');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discounts`
--

DROP TABLE IF EXISTS `discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `discount_type` enum('fixed','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `discounts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discounts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discounts`
--

LOCK TABLES `discounts` WRITE;
/*!40000 ALTER TABLE `discounts` DISABLE KEYS */;
INSERT INTO `discounts` VALUES (24,17,NULL,'percentage',90.00,NULL,NULL),(25,NULL,5,'percentage',99.00,NULL,NULL),(27,NULL,14,'percentage',80.00,NULL,'2025-04-27 20:11:00');
/*!40000 ALTER TABLE `discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `favorites`
--

DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_favorite` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `favorites`
--

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
INSERT INTO `favorites` VALUES (7,10,32,'2025-04-10 14:39:50'),(8,10,17,'2025-04-10 15:30:55');
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (4,'ж2'),(1,'МДФ 2.5мм.'),(2,'Фанера 3мм.');
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (37,38,32,1,10000000.00),(38,38,31,1,7777.00),(39,39,32,1,10000000.00),(40,39,26,20,90.00),(41,40,31,89,7777.00),(42,40,24,3,180.00);
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Новый',
  `name` varchar(255) NOT NULL,
  `delivery` varchar(100) NOT NULL,
  `payment` varchar(100) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (38,'MM1744309413984','','066','alexsandr81aa@gmail.com','',2007777.00,'2025-04-10 18:23:33',10,'Отправлен','alexsandr','Курьер','Наличными',0.00,8000000.00),(39,'MM1744314039329','','066','alexsandr81aa@gmail.com','',2001800.00,'2025-04-10 19:40:39',10,'Новый','alexsandr81]]]]]]]]]]]]]','Курьер','Наличными',0.00,8000000.00),(40,'MM1744314655593','','33','alexsandr81aa@gmail.com','',692158.40,'2025-04-10 19:50:55',10,'Новый','22','Курьер','Наличными',0.00,534.60);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) NOT NULL,
  `images` text DEFAULT NULL,
  `sku` varchar(50) NOT NULL,
  `subcategory` varchar(100) NOT NULL,
  `size` varchar(50) NOT NULL,
  `material` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `slug` varchar(255) NOT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (17,'мини-бар\"ЦИСТЕРНА\"','Мини-бар «Цистерна» – идеальный подарок, который впечатлит!\r\n\r\nИщете оригинальный и стильный подарок? Мини-бар в форме цистерны – это не просто эффектное оформление бутылки, а настоящий WOW-эффект!\r\n\r\nЧто делает его особенным?\r\n✔ Уникальный дизайн – мини-бар выполнен в виде железнодорожной цистерны с детализированными элементами.\r\n✔ Натуральные материалы – изготовлен из качественного дерева, создавая теплую и атмосферную эстетику.\r\n✔ Идеально для подарка – отличный вариант для ценителей эксклюзивных вещей, любителей оригинального декора и коллекционеров.\r\n✔ Персонализация – возможно лазерное гравирование, чтобы сделать подарок еще более запоминающимся.\r\n\r\nДля кого подойдет?\r\n✅ Коллеги и начальники – стильный деловой презент.\r\n✅ Друзья и близкие – необычный и креативный подарок.\r\n✅ Любители оригинальных вещей – эксклюзивный элемент интерьера.\r\n\r\nНе упустите возможность порадовать дорогих вам людей! Заказывайте прямо сейчас и сделайте праздник незабываемым!',1700.00,NULL,1,'2025-04-02 19:12:35',4,'[\"assets/products/67426659e563e5812cf41cfb4aeac36b.jpg\"]','00001','5','2','2',1,'mini-bar-tsisterna','мини-бар\"ЦИСТЕРНА\"','Купить мини-бар\"ЦИСТЕРНА\" по лучшей цене. Описание, характеристики, отзывы.','мини-бар\"ЦИСТЕРНА\"'),(18,'Мини-бар «Локомотив»','Мини-бар «Локомотив» – стиль, практичность и оригинальность в одном изделии! ????✨\r\nИщете уникальный подарок или стильный аксессуар для интерьера? Мини-бар «Локомотив» – это не просто место для хранения напитков, а настоящий шедевр для любителей ретро-стиля и железных дорог!\r\n\r\n✅ Эффектный дизайн – бар в виде старинного паровоза из фанеры подчеркнёт вкус и индивидуальность владельца.\r\n✅ Функциональность – удобно храните любимые напитки и аксессуары для бара, всегда под рукой всё необходимое.\r\n✅ Идеальный подарок – удивите друзей и близких нестандартным презентом, который вызовет восторг.\r\n✅ Экологичность – мини-бар выполнен из натуральной фанеры, безопасен и долговечен.\r\n\r\n???? Этот мини-бар – не просто аксессуар, а источник восхищённых взглядов и ярких эмоций! Добавьте в интерьер нотку ретро-стиля и создайте уютную атмосферу.\r\n\r\n???? Заказывайте прямо сейчас! Количество ограничено! ????',2200.00,NULL,1,'2025-04-02 19:27:33',4,'[\"assets/products/23c419038ec89103a699651e598413ca.jpg\"]','00002','5','3','2',1,'mini-bar-lokomotiv','Мини-бар «Локомотив»','Купить Мини-бар «Локомотив» по лучшей цене. Описание, характеристики, отзывы.','Мини-бар,«Локомотив»'),(19,'Мини-коробочка \"Happy Valentine\'s Day\"','Мини-коробочка \"Happy Valentine\'s Day\" ❤️✨\r\n\r\nСоздайте атмосферу романтики и нежности с этой очаровательной мини-коробочкой в форме сердца!\r\n\r\n✨ Идеальный подарок для любимого человека, в который можно положить небольшие сюрпризы: сладости, украшения или памятные мелочи.\r\n\r\n???? Материал: белый МДФ – прочный и экологичный.\r\n???? Размер: 120×120 мм – компактный, но вместительный.\r\n???? Дизайн: стильная лазерная гравировка с теплым пожеланием \"Happy Valentine\'s Day\".\r\n\r\nПодойдет для Дня святого Валентина, годовщины, свадьбы или просто как знак внимания! ????\r\n\r\nДобавьте капельку любви в ваш подарок – заказывайте прямо сейчас! ????',0.00,NULL,1,'2025-04-02 19:34:40',5,'[\"assets/products/8e48a28e0ff25e7dd4b669fc0863b8a4.jpg\"]','00003','6','4','1',1,'mini-korobochka-happy-valentine-s-day','Мини-коробочка \"Happy Valentine\'s Day\"','Купить Мини-коробочка \"Happy Valentine\'s Day\" по лучшей цене. Описание, характеристики, отзывы.','Мини-коробочка,\"Happy,Valentine\'s,Day\"'),(20,'Подарочная коробка \"Сердце\"','Подарочная коробка \"Сердца\" – идеальное дополнение к вашему сюрпризу!\r\nСоздайте атмосферу любви и нежности с эксклюзивной подарочной коробкой! Эта стильная и элегантная упаковка придаст вашему подарку особенный шарм и подчеркнет значимость момента.\r\n\r\n✨ Почему именно наша коробка?\r\n❤️ Роскошный дизайн – романтический рисунок с воздушными сердцами создаст теплую и праздничную атмосферу.\r\n???? Универсальность – идеально подходит для упаковки украшений, сладостей, аксессуаров, косметики и других приятных сюрпризов.\r\n???? Качественный материал – прочный белый МДФ гарантирует долговечность и надежность.\r\n???? Индивидуальный подход – возможность нанести любое изображение или надпись по вашему желанию.\r\n\r\n???? Доступные размеры:\r\n✔ 250×250×80 мм\r\n✔ 225×225×80 мм\r\n✔ 200×200×80 мм\r\n\r\nПодарите эмоции, которые останутся в сердце навсегда! Закажите сейчас и сделайте свой подарок еще более особенным.',200.00,NULL,1,'2025-04-02 19:41:13',5,'[\"assets/products/46c91210bd1629b94b6ffa0473acc439.jpg\"]','00004','6','5','1',1,'подарочная-коробка-сердце','Подарочная коробка \"Безмежно кохаю!\"','Купить Подарочная коробка \"Безмежно кохаю!\" по лучшей цене. Описание, характеристики, отзывы.','Подарочная,коробка,\"Безмежно,кохаю!\"'),(21,'Подарочная коробка квадратная','Невероятные новогодние подарочные коробки – создайте волшебство своими руками!\r\n\r\n✨ Идеальный выбор для особенных подарков! ✨\r\n\r\nПусть ваш подарок начнёт дарить радость ещё до открытия! Эти элегантные и стильные новогодние коробки созданы для того, чтобы превратить вручение подарка в настоящее чудо.\r\n\r\n✅ Уникальный дизайн – крышка с очаровательным изображением Санты (или вашим персональным принтом)\r\n✅ Индивидуальный подход – возможность изменить рисунок и надпись по вашему желанию\r\n✅ Высокое качество – изготовлены из прочного белого МДФ\r\n✅ Удобные размеры – 200×200×80 мм (или изготовим индивидуальный размер под ваш подарок)\r\n✅ Идеально для: сладостей, украшений, мини-сюрпризов, теплых пожеланий\r\n\r\nПорадуйте близких подарком, который запомнится!\r\n\r\n⚡ Количество ограничено! Заказывайте сейчас и создайте волшебную атмосферу праздника! ⚡',0.00,NULL,1,'2025-04-02 19:48:07',6,'[\"assets/products/b32176095f9116a90d0a6a4c9f8bfa5e.jpg\"]','00005','9','5','1',1,'подарочная-коробка-квадратная','Новогодняя подарочная коробка','Купить Новогодняя подарочная коробка по лучшей цене. Описание, характеристики, отзывы.','Новогодняя,подарочная,коробка'),(22,'Шкатулка для сбора денег','Роскошная свадебная шкатулка для денег и подарков ✨\r\nИзысканный аксессуар для вашего торжества! Эта ажурная скарбничка из белоснежного МДФ станет не только элегантным украшением свадебного стола, но и стильным хранилищем для денежных подарков от ваших гостей.\r\n\r\n???? Премиальный дизайн – утонченная лазерная резка создает эффект воздушного кружева\r\n???? Персонализация – добавим ваши инициалы для особого шарма\r\n???? Идеальные пропорции – 300 × 240 × 185 мм, удобный размер для конвертов и открыток\r\n???? Удобство – прорезь для конвертов и откидная крышка для легкого доступа\r\n\r\nСделайте свой день незабываемым – закажите эксклюзивную свадебную шкатулку прямо сейчас! ✨',500.00,NULL,1,'2025-04-02 20:00:13',7,'[\"assets/products/07a09436557fee40e755974991566590.jpg\"]','00006','11','6','1',1,'шкатулка-для-сбора-денег','Шкатулка для сбора денег','Купить Шкатулка для сбора денег по лучшей цене. Описание, характеристики, отзывы.','Шкатулка,для,сбора,денег'),(23,'Шкатулка резная','Элегантная шкатулка для ценителей красоты и порядка\r\n\r\nПознакомьтесь с настоящим произведением искусства – изысканной деревянной шкатулкой для хранения мелочей! Ее изящный резной узор превращает обычную коробочку в уникальный элемент декора, который придаст вашему интерьеру нотку утонченной элегантности.\r\n\r\n✨ Волшебный дизайн: ажурная крышка с цветочными мотивами создает эффект воздушности и легкости.\r\n✨ Идеальные размеры (200×130×85 мм): компактна, но вместительна – идеальна для украшений, косметики, ключей и других мелочей.\r\n✨ Природные материалы: выполнена из экологически чистого дерева, приятного на ощупь и безопасного для использования.\r\n✨ Универсальный подарок: порадуйте себя или близкого человека стильным и функциональным аксессуаром!\r\n\r\nДобавьте изысканности вашему дому – закажите прямо сейчас!',250.00,NULL,1,'2025-04-02 20:05:07',8,'[\"assets/products/694101ec029752748766cde492dc2fa8.jpg\"]','00007','12','7','1',1,'шкатулка-резная','Шкатулка резная','Купить Шкатулка резная по лучшей цене. Описание, характеристики, отзывы.','Шкатулка,резная'),(24,'Подарочная коробка \"Мое сердце принадлежит тебе\"','Квадратная подарочная коробка \"Мое сердце принадлежит тебе\"\r\n\r\nЭта изысканная квадратная коробка – идеальный способ красиво оформить подарок ко Дню святого Валентина, годовщине или любому другому особому случаю.\r\n\r\nХарактеристики:\r\nФорма: квадратная\r\nМатериал: белый МДФ\r\nРазмеры: 200×200×80 мм\r\nДизайн: крышка украшена милым рисунком белого медвежонка в окружении сердечек и надписью \"Моє серце належить тобі!\"\r\nПерсонализация: возможность изменить изображение на крышке или добавить индивидуальную надпись\r\nИдеально подходит для:\r\n✔ Упаковки сладостей, украшений, сувениров и романтических подарков\r\n✔ Оригинального оформления сюрприза для любимого человека\r\n✔ Хранения памятных вещей и декоративного использования\r\n\r\nСоздайте особенное настроение с этой стильной и прочной коробкой, которая сохранит тепло ваших чувств! ????',180.00,NULL,1,'2025-04-02 20:08:12',5,'[\"assets/products/4bef6a8dcda6f27546d55e3cc888f17b.jpg\"]','00008','9','5','1',1,'podarochnaya-korobka-moe-serdtse-prinadlezhit-tebe','Подарочная коробка \"Мое сердце принадлежит тебе\"','Купить Подарочная коробка \"Мое сердце принадлежит тебе\" по лучшей цене. Описание, характеристики, отзывы.','Подарочная,коробка,\"Мое,сердце,принадлежит,тебе\"'),(25,'Подарочная коробка \"Kinder Surprise\"','Подарочная коробка \"Kinder Surprise\" – оригинальный и стильный подарок!\r\nХотите сделать сюрприз, который вызовет восторг? Подарочная коробка в стиле \"Kinder Surprise\" – это яркий и необычный способ упаковать ваш подарок!\r\n\r\n✨ Особенности:\r\n✔ Дизайн, вдохновленный легендарным яйцом-сюрпризом – белый корпус с ярким красно-белым принтом и веселыми буквами.\r\n✔ Прочный материал – изготовлена из качественного белого МДФ, который надежно защитит содержимое.\r\n✔ Идеальный размер – представлена в трех вариантах (см. ниже), что позволяет подобрать подходящий для вашего подарка.\r\n✔ Многофункциональность – можно использовать для конфет, игрушек, косметики, аксессуаров и других сюрпризов.\r\n\r\n???? Размеры (ШхГхВ, мм):\r\n???? 250×190×80\r\n???? 225×170×80\r\n???? 200×150×80\r\n\r\n???? Идеально для:\r\n???? Подарков детям и взрослым\r\n???? Упаковки сладостей и игрушек\r\n???? Хранения мелочей и приятных вещей\r\n\r\nСоздайте атмосферу настоящего волшебства с нашей коробкой \"Kinder Surprise\"!\r\n\r\n???? Заказывайте прямо сейчас и дарите радость!',250.00,NULL,1,'2025-04-02 20:17:49',8,'[\"assets/products/7bb46f958e173f50f09f3c843905eda3.jpg\"]','00009','13','8','1',1,'podarochnaya-korobka-kinder-surprise','Подарочная коробка \"Kinder Surprise\"','Купить Подарочная коробка \"Kinder Surprise\" по лучшей цене. Описание, характеристики, отзывы.','Подарочная,коробка,\"Kinder,Surprise\"'),(26,'Именная копилка','Именная копилка – стильный и уникальный аксессуар!\r\n\r\nИщете оригинальный подарок или хотите научить ребенка копить деньги с удовольствием? Представляем персонализированную копилку, выполненную из качественного белого МДФ.\r\n\r\n✨ Особенности:\r\n✔ Персонализация – имя, гравировка, изображение любимых персонажей или уникальный дизайн.\r\n✔ Качественные материалы – прочный белый МДФ, долговечность и стильный внешний вид.\r\n✔ Идеальный подарок – подойдет для детей и взрослых, на день рождения, Новый год, крещение или любое важное событие.\r\n✔ Функциональность – удобное отверстие для монет и купюр, возможность легко доставать сбережения.\r\n\r\nЗакажите свою уникальную копилку прямо сейчас и создайте незабываемый подарок!',90.00,NULL,1,'2025-04-02 20:22:46',10,'[\"assets/products/c3189c52efa5bc54a3ca117150691a1f.jpg\"]','00010','14','9','1',1,'imennaya-kopilka','Именная копилка','Купить Именная копилка по лучшей цене. Описание, характеристики, отзывы.','Именная,копилка'),(31,'777777777','777777777',7777.00,NULL,7,'2025-04-09 16:54:24',8,'[\"assets\\/products\\/859181ae5ae9084c5bc2c16046267778.jpg\"]','7','12','9','1',1,'777777777','777777777','Купить 777777777 по лучшей цене. Описание, характеристики, отзывы.','777777777'),(32,'жопа','полная',10000000.00,NULL,555,'2025-04-09 19:18:47',14,'[\"assets/products/f0fb2d0baaa9680e56fc1f9d087f8420.jpg\"]','666','19','11','4',1,'zhopa','жопа','Купить жопа по лучшей цене. Описание, характеристики, отзывы.','жопа');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promo_codes`
--

DROP TABLE IF EXISTS `promo_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promo_codes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('fixed','percentage') NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `min_order_amount` decimal(10,2) DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promo_codes`
--

LOCK TABLES `promo_codes` WRITE;
/*!40000 ALTER TABLE `promo_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `promo_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_response` text DEFAULT NULL,
  `response_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sizes`
--

DROP TABLE IF EXISTS `sizes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sizes`
--

LOCK TABLES `sizes` WRITE;
/*!40000 ALTER TABLE `sizes` DISABLE KEYS */;
INSERT INTO `sizes` VALUES (9,'100*110*140мм.'),(4,'120*120*60мм.'),(7,'200*130*85мм.'),(8,'200*150*80мм.'),(5,'200*200*80мм.'),(1,'200*200мм'),(10,'20000000000'),(6,'300*240*185мм.'),(2,'350*130*200мм'),(3,'535*180*230мм.');
/*!40000 ALTER TABLE `sizes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subcategories`
--

DROP TABLE IF EXISTS `subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subcategories`
--

LOCK TABLES `subcategories` WRITE;
/*!40000 ALTER TABLE `subcategories` DISABLE KEYS */;
INSERT INTO `subcategories` VALUES (5,4,'ДЖ тематика'),(6,5,'Коробки \"СЕРДЦЕ\"'),(7,6,'Коробки квадратные'),(8,6,'Коробки круглые'),(9,5,'Коробки квадратные'),(10,5,'Коробки круглые'),(11,7,'Шкатулки'),(12,8,'Шкатулки'),(13,8,'Коробка яйцо'),(14,10,'Детские'),(19,14,'жж');
/*!40000 ALTER TABLE `subcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_created_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','blocked') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (10,'margo','alexsandr81aa@gmail.com','$2y$10$HiM8hR9/DqS4gp19V4jD0e4iKR4sbBYWyPuhYNe1U.5siCF/1q.FC','2025-04-09 19:45:16',NULL,NULL,'active');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-18 17:04:55
