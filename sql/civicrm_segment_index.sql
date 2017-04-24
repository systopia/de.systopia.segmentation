-- CREATE SEGMENT INDEX TABLE
CREATE TABLE IF NOT EXISTS `civicrm_segmentation_index`(
  `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Segment ID',
  `name` varchar(255)                        COMMENT 'Segment Name',
  PRIMARY KEY ( `id` ),
  INDEX `index_name` (name)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
