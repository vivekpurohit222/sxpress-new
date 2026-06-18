-- Reconstructed database schema for Saurashtra Express
-- Generated from legacy migrations plus database/reconstructed_migrations.
-- Target: MySQL 8.x / InnoDB / utf8mb4.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `truck_assignments`;
DROP TABLE IF EXISTS `freight_lines`;
DROP TABLE IF EXISTS `freight_payments`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `pod_uploads`;
DROP TABLE IF EXISTS `frieghts`;
DROP TABLE IF EXISTS `challan_iteams`;
DROP TABLE IF EXISTS `gatepasses`;
DROP TABLE IF EXISTS `challans`;
DROP TABLE IF EXISTS `grs`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `trucks`;
DROP TABLE IF EXISTS `vendors`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `number_sequences`;
DROP TABLE IF EXISTS `branches`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `posts`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `migrations`;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `city` varchar(100) NULL,
  `state` varchar(100) NULL,
  `pincode` varchar(10) NULL,
  `phone` varchar(20) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `idx_branches_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL,
  `password` varchar(255) NOT NULL,
  `office` varchar(255) NOT NULL,
  `branch_id` bigint unsigned NULL,
  `remember_token` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `idx_users_office` (`office`),
  KEY `idx_users_branch_id` (`branch_id`),
  CONSTRAINT `fk_users_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `branches`
  ADD CONSTRAINT `fk_branches_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_branches_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `number_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scope` varchar(50) NOT NULL,
  `prefix` varchar(10) NULL,
  `current_value` bigint unsigned NOT NULL DEFAULT 0,
  `pad_length` tinyint unsigned NOT NULL DEFAULT 5,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number_sequences_scope_unique` (`scope`),
  KEY `idx_number_sequences_scope` (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NULL,
  `auditable_type` varchar(100) NOT NULL,
  `auditable_id` bigint unsigned NOT NULL,
  `event` varchar(20) NOT NULL,
  `old_values` json NULL,
  `new_values` json NULL,
  `url` varchar(255) NULL,
  `ip` varchar(45) NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_user_id` (`user_id`),
  KEY `idx_audit_logs_auditable` (`auditable_type`, `auditable_id`),
  KEY `idx_audit_logs_event` (`event`),
  KEY `idx_audit_logs_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NULL,
  `gst_no` varchar(15) NULL,
  `pan_no` varchar(10) NULL,
  `phone` varchar(20) NULL,
  `email` varchar(255) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `deleted_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_code_unique` (`code`),
  KEY `idx_customers_gst_no` (`gst_no`),
  KEY `idx_customers_is_active` (`is_active`),
  KEY `idx_customers_name` (`name`),
  KEY `idx_customers_created_by_id` (`created_by_id`),
  KEY `idx_customers_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_customers_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_customers_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NULL,
  `gst_no` varchar(15) NULL,
  `pan_no` varchar(10) NULL,
  `bank_account` varchar(50) NULL,
  `ifsc` varchar(20) NULL,
  `phone` varchar(20) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `deleted_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendors_code_unique` (`code`),
  KEY `idx_vendors_gst_no` (`gst_no`),
  KEY `idx_vendors_is_active` (`is_active`),
  KEY `idx_vendors_name` (`name`),
  KEY `idx_vendors_created_by_id` (`created_by_id`),
  KEY `idx_vendors_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_vendors_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_vendors_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `trucks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `truck_no` varchar(20) NOT NULL,
  `owner_vendor_id` bigint unsigned NULL,
  `home_branch_id` bigint unsigned NULL,
  `make` varchar(100) NULL,
  `model` varchar(100) NULL,
  `capacity_kg` decimal(10,3) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trucks_truck_no_unique` (`truck_no`),
  KEY `idx_trucks_owner_vendor_id` (`owner_vendor_id`),
  KEY `idx_trucks_home_branch_id` (`home_branch_id`),
  KEY `idx_trucks_is_active` (`is_active`),
  CONSTRAINT `fk_trucks_owner_vendor_id` FOREIGN KEY (`owner_vendor_id`) REFERENCES `vendors` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trucks_home_branch_id` FOREIGN KEY (`home_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `drivers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `license` varchar(20) NOT NULL,
  `address` text NULL,
  `mobile1` varchar(15) NULL,
  `mobile2` varchar(15) NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drivers_license_unique` (`license`),
  KEY `idx_drivers_name` (`name`),
  KEY `idx_drivers_is_active` (`is_active`),
  KEY `idx_drivers_mobile1` (`mobile1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `truckdrivers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_name` varchar(150) NOT NULL,
  `truck_no` varchar(20) NOT NULL,
  `license` varchar(20) NOT NULL,
  `driver_address` text NOT NULL,
  `mobile_no1` varchar(15) NOT NULL,
  `mobile_no2` varchar(15) NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `truckdrivers_truck_no_unique` (`truck_no`),
  UNIQUE KEY `truckdrivers_mobile_no1_unique` (`mobile_no1`),
  KEY `idx_truckdrivers_driver_name` (`driver_name`),
  KEY `idx_truckdrivers_created_by_id` (`created_by_id`),
  KEY `idx_truckdrivers_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_truckdrivers_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_truckdrivers_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gr_no` varchar(20) NOT NULL,
  `from_dest` varchar(50) NOT NULL,
  `from_branch_id` bigint unsigned NULL,
  `to_dest` varchar(50) NOT NULL,
  `to_branch_id` bigint unsigned NULL,
  `copy_date` date NULL,
  `consignor` varchar(150) NOT NULL,
  `consignor_id` bigint unsigned NULL,
  `consignor_address` varchar(255) NOT NULL,
  `consignor_gst_no` varchar(15) NULL,
  `consignee` varchar(150) NOT NULL,
  `consignee_id` bigint unsigned NULL,
  `consignee_address` varchar(255) NOT NULL,
  `consignee_gst_no` varchar(15) NULL,
  `nugs` int NOT NULL,
  `meth` varchar(10) NOT NULL,
  `description` text NOT NULL,
  `pm` varchar(50) NOT NULL,
  `eway_bill_number` varchar(20) NOT NULL,
  `bill_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `weight` decimal(10,3) NOT NULL DEFAULT 0,
  `paid` tinyint(1) NOT NULL DEFAULT 0,
  `to_pay` tinyint(1) NOT NULL DEFAULT 0,
  `frieght_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `sur_ch` decimal(12,2) NOT NULL DEFAULT 0,
  `c_r` decimal(12,2) NOT NULL DEFAULT 0,
  `other` decimal(12,2) NOT NULL DEFAULT 0,
  `bc_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `status` enum('created','loaded','in_transit','delivered','closed','cancelled') NOT NULL DEFAULT 'created',
  `deleted_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grs_gr_no_unique` (`gr_no`),
  KEY `idx_grs_from_dest` (`from_dest`),
  KEY `idx_grs_to_dest` (`to_dest`),
  KEY `idx_grs_copy_date` (`copy_date`),
  KEY `idx_grs_consignor` (`consignor`),
  KEY `idx_grs_consignee` (`consignee`),
  KEY `idx_grs_eway_bill_number` (`eway_bill_number`),
  KEY `idx_grs_from_dest_copy_date` (`from_dest`, `copy_date`),
  KEY `idx_grs_status` (`status`),
  KEY `idx_grs_from_branch_id` (`from_branch_id`),
  KEY `idx_grs_to_branch_id` (`to_branch_id`),
  KEY `idx_grs_consignor_id` (`consignor_id`),
  KEY `idx_grs_consignee_id` (`consignee_id`),
  KEY `idx_grs_created_by_id` (`created_by_id`),
  KEY `idx_grs_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_grs_from_branch_id` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grs_to_branch_id` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grs_consignor_id` FOREIGN KEY (`consignor_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grs_consignee_id` FOREIGN KEY (`consignee_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grs_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_grs_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `challans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `challan_no` varchar(20) NOT NULL,
  `from_dest` varchar(50) NOT NULL,
  `from_branch_id` bigint unsigned NULL,
  `challan_date` date NULL,
  `to_dest` varchar(50) NOT NULL,
  `to_branch_id` bigint unsigned NULL,
  `truck_no` varchar(20) NOT NULL,
  `truck_id` bigint unsigned NULL,
  `driver_name` varchar(150) NOT NULL,
  `license` varchar(20) NOT NULL,
  `owner_name` varchar(150) NOT NULL,
  `note` text NULL,
  `challan_total` decimal(14,2) NULL,
  `status` enum('created','loaded','in_transit','completed','cancelled') NOT NULL DEFAULT 'created',
  `deleted_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `challans_challan_no_unique` (`challan_no`),
  KEY `idx_challans_challan_date` (`challan_date`),
  KEY `idx_challans_truck_no` (`truck_no`),
  KEY `idx_challans_from_to` (`from_dest`, `to_dest`),
  KEY `idx_challans_truck_id` (`truck_id`),
  KEY `idx_challans_status` (`status`),
  KEY `idx_challans_from_branch_id` (`from_branch_id`),
  KEY `idx_challans_to_branch_id` (`to_branch_id`),
  KEY `idx_challans_created_by_id` (`created_by_id`),
  KEY `idx_challans_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_challans_truck_id` FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_challans_from_branch_id` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_challans_to_branch_id` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_challans_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_challans_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `gatepasses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gp_no` int NOT NULL,
  `consignor` varchar(150) NOT NULL,
  `gp_date` date NULL,
  `from_dest` varchar(50) NOT NULL,
  `from_branch_id` bigint unsigned NULL,
  `to_dest` varchar(50) NOT NULL,
  `to_branch_id` bigint unsigned NULL,
  `gr_no` varchar(20) NOT NULL,
  `gr_id` bigint unsigned NULL,
  `weight` decimal(10,3) NOT NULL DEFAULT 0,
  `nugs` int NOT NULL,
  `pm` varchar(50) NOT NULL,
  `frieght_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `labour_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `other` decimal(12,2) NOT NULL DEFAULT 0,
  `delivery_charge` decimal(12,2) NOT NULL DEFAULT 0,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `note` text NULL,
  `status` enum('created','released','delivered','cancelled') NOT NULL DEFAULT 'created',
  `deleted_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_gatepasses_gp_no_gp_date` (`gp_no`, `gp_date`),
  KEY `idx_gatepasses_gp_date` (`gp_date`),
  KEY `idx_gatepasses_from_dest` (`from_dest`),
  KEY `idx_gatepasses_to_dest` (`to_dest`),
  KEY `idx_gatepasses_gr_id` (`gr_id`),
  KEY `idx_gatepasses_status` (`status`),
  KEY `idx_gatepasses_from_branch_id` (`from_branch_id`),
  KEY `idx_gatepasses_to_branch_id` (`to_branch_id`),
  KEY `idx_gatepasses_created_by_id` (`created_by_id`),
  KEY `idx_gatepasses_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_gatepasses_gr_id` FOREIGN KEY (`gr_id`) REFERENCES `grs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_gatepasses_from_branch_id` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_gatepasses_to_branch_id` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_gatepasses_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_gatepasses_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `challan_iteams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gr_no` varchar(20) NOT NULL,
  `gr_id` bigint unsigned NULL,
  `challan_no` varchar(20) NOT NULL,
  `challan_id` bigint unsigned NULL,
  `nugs` int NOT NULL,
  `meth` varchar(10) NOT NULL,
  `description` text NOT NULL,
  `weight` decimal(10,3) NOT NULL DEFAULT 0,
  `paid` decimal(12,2) NULL,
  `to_pay` decimal(12,2) NULL,
  `sur_ch` decimal(12,2) NOT NULL DEFAULT 0,
  `c_r` decimal(12,2) NOT NULL DEFAULT 0,
  `other` decimal(12,2) NOT NULL DEFAULT 0,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_challan_iteams_challan_no_gr_no` (`challan_no`, `gr_no`),
  KEY `idx_challan_iteams_challan_no` (`challan_no`),
  KEY `idx_challan_iteams_gr_no` (`gr_no`),
  KEY `idx_challan_iteams_gr_id` (`gr_id`),
  KEY `idx_challan_iteams_challan_id` (`challan_id`),
  KEY `idx_challan_iteams_created_by_id` (`created_by_id`),
  KEY `idx_challan_iteams_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_challan_iteams_gr_id` FOREIGN KEY (`gr_id`) REFERENCES `grs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_challan_iteams_challan_id` FOREIGN KEY (`challan_id`) REFERENCES `challans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_challan_iteams_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_challan_iteams_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `frieghts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fm_no` varchar(20) NOT NULL,
  `fm_date` date NULL,
  `from_dest` varchar(50) NOT NULL,
  `from_branch_id` bigint unsigned NULL,
  `to_dest` varchar(50) NOT NULL,
  `to_branch_id` bigint unsigned NULL,
  `truck_no` varchar(20) NOT NULL,
  `truck_id` bigint unsigned NULL,
  `entry_1` varchar(150) NULL,
  `entry_1_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `entry_2` varchar(150) NULL,
  `entry_2_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `entry_3` varchar(150) NULL,
  `entry_3_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `entry_4` varchar(150) NULL,
  `entry_4_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `truck_freight` decimal(12,2) NOT NULL DEFAULT 0,
  `commission` decimal(12,2) NOT NULL DEFAULT 0,
  `other_charges` decimal(12,2) NOT NULL DEFAULT 0,
  `extra` decimal(12,2) NOT NULL DEFAULT 0,
  `balance_due` decimal(12,2) NOT NULL DEFAULT 0,
  `note` text NULL,
  `deleted_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_frieghts_fm_no` (`fm_no`),
  KEY `idx_frieghts_fm_date` (`fm_date`),
  KEY `idx_frieghts_truck_no` (`truck_no`),
  KEY `idx_frieghts_from_to` (`from_dest`, `to_dest`),
  KEY `idx_frieghts_truck_id` (`truck_id`),
  KEY `idx_frieghts_from_branch_id` (`from_branch_id`),
  KEY `idx_frieghts_to_branch_id` (`to_branch_id`),
  KEY `idx_frieghts_created_by_id` (`created_by_id`),
  KEY `idx_frieghts_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_frieghts_truck_id` FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_frieghts_from_branch_id` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_frieghts_to_branch_id` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_frieghts_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_frieghts_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pod_uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gr_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `signature_path` varchar(255) NULL,
  `received_by_name` varchar(150) NULL,
  `delivered_at` timestamp NULL,
  `created_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pod_uploads_gr_id` (`gr_id`),
  KEY `idx_pod_uploads_delivered_at` (`delivered_at`),
  KEY `idx_pod_uploads_created_by_id` (`created_by_id`),
  CONSTRAINT `fk_pod_uploads_gr_id` FOREIGN KEY (`gr_id`) REFERENCES `grs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pod_uploads_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `gr_id` bigint unsigned NOT NULL,
  `paid_on` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(30) NOT NULL,
  `reference` varchar(100) NULL,
  `note` text NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_gr_id` (`gr_id`),
  KEY `idx_payments_paid_on` (`paid_on`),
  KEY `idx_payments_method` (`method`),
  KEY `idx_payments_created_by_id` (`created_by_id`),
  KEY `idx_payments_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_payments_gr_id` FOREIGN KEY (`gr_id`) REFERENCES `grs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_payments_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `freight_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `freight_memo_id` bigint unsigned NOT NULL,
  `paid_on` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(30) NOT NULL,
  `reference` varchar(100) NULL,
  `note` text NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `idx_freight_payments_freight_memo_id` (`freight_memo_id`),
  KEY `idx_freight_payments_paid_on` (`paid_on`),
  KEY `idx_freight_payments_method` (`method`),
  KEY `idx_freight_payments_created_by_id` (`created_by_id`),
  KEY `idx_freight_payments_updated_by_id` (`updated_by_id`),
  CONSTRAINT `fk_freight_payments_freight_memo_id` FOREIGN KEY (`freight_memo_id`) REFERENCES `frieghts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_freight_payments_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_freight_payments_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `freight_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `freight_memo_id` bigint unsigned NOT NULL,
  `sequence` int unsigned NOT NULL,
  `description` varchar(150) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_freight_lines_memo_sequence` (`freight_memo_id`, `sequence`),
  KEY `idx_freight_lines_freight_memo_id` (`freight_memo_id`),
  CONSTRAINT `fk_freight_lines_freight_memo_id` FOREIGN KEY (`freight_memo_id`) REFERENCES `frieghts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `truck_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `truck_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `assigned_from` date NOT NULL,
  `assigned_to` date NULL,
  `created_by_id` bigint unsigned NULL,
  `updated_by_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_truck_assignments` (`truck_id`, `driver_id`, `assigned_from`),
  KEY `idx_truck_assignments_driver_id` (`driver_id`),
  KEY `idx_truck_assignments_assigned_to` (`assigned_to`),
  CONSTRAINT `fk_truck_assignments_truck_id` FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_truck_assignments_driver_id` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_truck_assignments_created_by_id` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_truck_assignments_updated_by_id` FOREIGN KEY (`updated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text NULL,
  `last_used_at` timestamp NULL,
  `expires_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text NULL,
  `type` varchar(20) NOT NULL DEFAULT 'string',
  `group` varchar(50) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `idx_settings_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`),
  KEY `idx_notifications_read_at` (`read_at`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) NULL,
  `collection_name` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(255) NULL,
  `disk` varchar(255) NOT NULL,
  `conversions_disk` varchar(255) NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_media_uuid` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`, `model_id`),
  KEY `idx_media_order_column` (`order_column`),
  KEY `idx_media_collection_name` (`collection_name`),
  KEY `idx_media_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

