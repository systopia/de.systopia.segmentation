-- CREATE SEGMENT TO ACTIVITY TABLE
CREATE TABLE IF NOT EXISTS `civicrm_activity_contact_segmentation` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ActivityContact Segment Entry ID',
  `activity_contact_id` INT UNSIGNED NOT NULL COMMENT 'ActivityContact ID',
  `segment_id` INT UNSIGNED NOT NULL COMMENT 'Segment ID',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `activity_contact_id_UNIQUE` (`activity_contact_id` ASC),
  CONSTRAINT `FK_civicrm_activity_contact_segmentation_activity_contact_id` FOREIGN KEY (`activity_contact_id`) REFERENCES `civicrm_activity_contact` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_activity_contact_segmentation_segment_id` FOREIGN KEY (`segment_id`) REFERENCES `civicrm_segmentation_index` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;