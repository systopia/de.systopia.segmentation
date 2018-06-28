-- CREATE SEGMENTATION EXCLUDE TABLE
CREATE TABLE IF NOT EXISTS `civicrm_segmentation_exclude` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'SegmentationExclude Entry ID',
  `campaign_id` INT UNSIGNED NOT NULL COMMENT 'Campaign ID',
  `segment_id` INT UNSIGNED NOT NULL COMMENT 'Segment ID',
  `contact_id` INT UNSIGNED NOT NULL COMMENT 'Contact ID',
  `membership_id` INT UNSIGNED NULL COMMENT 'Membership ID',
  `created_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'SegmentationExclude Entry Creation Date',
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_civicrm_segmentation_exclude_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `civicrm_campaign` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_segmentation_exclude_segment_id` FOREIGN KEY (`segment_id`) REFERENCES `civicrm_segmentation_index` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_segmentation_exclude_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_civicrm_segmentation_exclude_membership_id` FOREIGN KEY (`membership_id`) REFERENCES `civicrm_membership` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;