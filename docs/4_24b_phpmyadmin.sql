-- Milestone 4.24B Staff Operations Portal
-- Do not run automatically. Review in phpMyAdmin and run before deploying 4.24B code.

-- ==================================================
-- A. PRE-FLIGHT / READ-ONLY verification queries
-- ==================================================
SELECT table_name, engine
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('users','stock_locations','customer_addresses','orders','customers','notifications','migrations');

SELECT table_name, column_name, column_type, is_nullable
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'users' AND column_name IN ('id','role','assigned_store_id','staff_roles','staff_active'))
    OR (table_name = 'stock_locations' AND column_name = 'id')
    OR (table_name = 'customer_addresses' AND column_name IN ('id','latitude','longitude','geofence_radius_meters'))
    OR (table_name = 'orders' AND column_name IN ('id','stock_location_id','customer_id','order_status'))
    OR (table_name = 'customers' AND column_name = 'id')
    OR (table_name = 'notifications' AND column_name = 'id')
  )
ORDER BY table_name, column_name;

SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests');

SELECT migration, batch
FROM migrations
WHERE migration = '2026_08_17_000001_create_staff_operations_portal_tables';

SELECT table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'users' AND column_name IN ('staff_roles','additional_permissions','denied_permissions','staff_active','staff_approved_at','staff_approved_by'))
    OR (table_name = 'customer_addresses' AND column_name IN ('latitude','longitude','geofence_radius_meters'))
  )
ORDER BY table_name, column_name;

SELECT table_name, constraint_name
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
  AND constraint_name IN (
    'users_staff_approved_by_foreign',
    'staff_notifications_recipient_user_id_foreign',
    'staff_notifications_stock_location_id_foreign',
    'order_staff_assignments_order_id_foreign',
    'order_staff_assignments_stock_location_id_foreign',
    'order_staff_assignments_assigned_user_id_foreign',
    'order_staff_assignments_assigned_by_foreign',
    'order_staff_assignments_started_by_foreign',
    'order_staff_assignments_completed_by_foreign',
    'order_staff_assignments_reassigned_from_user_id_foreign',
    'order_staff_assignments_reassigned_by_foreign',
    'delivery_attempts_order_id_foreign',
    'delivery_attempts_order_staff_assignment_id_foreign',
    'delivery_attempts_stock_location_id_foreign',
    'delivery_attempts_delivery_agent_id_foreign',
    'delivery_otps_order_id_foreign',
    'delivery_otps_delivery_attempt_id_foreign',
    'delivery_events_order_id_foreign',
    'delivery_events_delivery_attempt_id_foreign',
    'delivery_events_stock_location_id_foreign',
    'delivery_events_actor_user_id_foreign',
    'delivery_events_override_approved_by_foreign',
    'staff_approval_requests_stock_location_id_foreign',
    'staff_approval_requests_requested_by_foreign',
    'staff_approval_requests_checked_by_foreign'
  );

-- Expected preflight:
-- 1. users, stock_locations, customer_addresses, orders, customers, notifications, migrations exist and use InnoDB where applicable.
-- 2. New 4.24B tables do not already exist.
-- 3. users.staff_roles, users.staff_active, customer_addresses.latitude/longitude/geofence_radius_meters do not already exist.
-- 4. Referenced id columns are BIGINT UNSIGNED.
-- 5. migration 2026_08_17_000001_create_staff_operations_portal_tables is not already registered.
-- 6. None of the 4.24B foreign-key constraint names already exist.

-- ==================================================
-- B. ACTUAL migration SQL
-- ==================================================
ALTER TABLE `users` ADD `staff_roles` JSON NULL AFTER `role`;
ALTER TABLE `users` ADD `additional_permissions` JSON NULL AFTER `staff_roles`;
ALTER TABLE `users` ADD `denied_permissions` JSON NULL AFTER `additional_permissions`;
ALTER TABLE `users` ADD `staff_active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `denied_permissions`;
ALTER TABLE `users` ADD `staff_approved_at` TIMESTAMP NULL AFTER `staff_active`;
ALTER TABLE `users` ADD `staff_approved_by` BIGINT UNSIGNED NULL AFTER `staff_approved_at`;
ALTER TABLE `users` ADD CONSTRAINT `users_staff_approved_by_foreign` FOREIGN KEY (`staff_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `users` ADD INDEX `users_assigned_store_id_staff_active_index`(`assigned_store_id`, `staff_active`);
ALTER TABLE `users` ADD INDEX `users_staff_active_index`(`staff_active`);

ALTER TABLE `customer_addresses` ADD `latitude` DECIMAL(10, 7) NULL AFTER `landmark`;
ALTER TABLE `customer_addresses` ADD `longitude` DECIMAL(10, 7) NULL AFTER `latitude`;
ALTER TABLE `customer_addresses` ADD `geofence_radius_meters` INT UNSIGNED NULL AFTER `longitude`;

CREATE TABLE `staff_notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `recipient_user_id` BIGINT UNSIGNED NOT NULL,
  `stock_location_id` BIGINT UNSIGNED NULL,
  `workstream` VARCHAR(40) NOT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `related_type` VARCHAR(255) NULL,
  `related_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NULL,
  `action_url` VARCHAR(255) NULL,
  `read_at` TIMESTAMP NULL,
  `data` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `staff_notifications` ADD CONSTRAINT `staff_notifications_recipient_user_id_foreign` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `staff_notifications` ADD CONSTRAINT `staff_notifications_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `staff_notifications` ADD INDEX `staff_notifications_related_type_related_id_index`(`related_type`, `related_id`);
ALTER TABLE `staff_notifications` ADD INDEX `staff_notif_user_stream_read_idx`(`recipient_user_id`, `workstream`, `read_at`);
ALTER TABLE `staff_notifications` ADD INDEX `staff_notif_store_stream_created_idx`(`stock_location_id`, `workstream`, `created_at`);
ALTER TABLE `staff_notifications` ADD INDEX `staff_notifications_workstream_index`(`workstream`);
ALTER TABLE `staff_notifications` ADD INDEX `staff_notifications_event_type_index`(`event_type`);
ALTER TABLE `staff_notifications` ADD INDEX `staff_notifications_read_at_index`(`read_at`);

CREATE TABLE `order_staff_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `stock_location_id` BIGINT UNSIGNED NULL,
  `task_type` VARCHAR(30) NOT NULL,
  `assigned_user_id` BIGINT UNSIGNED NULL,
  `assigned_by` BIGINT UNSIGNED NULL,
  `assigned_at` TIMESTAMP NULL,
  `started_by` BIGINT UNSIGNED NULL,
  `started_at` TIMESTAMP NULL,
  `completed_by` BIGINT UNSIGNED NULL,
  `completed_at` TIMESTAMP NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  `reassigned_from_user_id` BIGINT UNSIGNED NULL,
  `reassigned_by` BIGINT UNSIGNED NULL,
  `reassigned_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_assigned_user_id_foreign` FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_started_by_foreign` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_reassigned_from_user_id_foreign` FOREIGN KEY (`reassigned_from_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD CONSTRAINT `order_staff_assignments_reassigned_by_foreign` FOREIGN KEY (`reassigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `order_staff_assignments` ADD UNIQUE `order_staff_task_unique`(`order_id`, `task_type`);
ALTER TABLE `order_staff_assignments` ADD INDEX `order_staff_store_type_status_idx`(`stock_location_id`, `task_type`, `status`);
ALTER TABLE `order_staff_assignments` ADD INDEX `order_staff_assignee_status_idx`(`assigned_user_id`, `status`);
ALTER TABLE `order_staff_assignments` ADD INDEX `order_staff_assignments_status_index`(`status`);

CREATE TABLE `delivery_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `order_staff_assignment_id` BIGINT UNSIGNED NULL,
  `stock_location_id` BIGINT UNSIGNED NULL,
  `delivery_agent_id` BIGINT UNSIGNED NULL,
  `attempt_number` INT UNSIGNED NOT NULL DEFAULT '1',
  `status` VARCHAR(40) NOT NULL DEFAULT 'ASSIGNED',
  `started_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `invalidated_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `delivery_attempts` ADD CONSTRAINT `delivery_attempts_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `delivery_attempts` ADD CONSTRAINT `delivery_attempts_order_staff_assignment_id_foreign` FOREIGN KEY (`order_staff_assignment_id`) REFERENCES `order_staff_assignments` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_attempts` ADD CONSTRAINT `delivery_attempts_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_attempts` ADD CONSTRAINT `delivery_attempts_delivery_agent_id_foreign` FOREIGN KEY (`delivery_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_attempts` ADD UNIQUE `delivery_attempt_order_number_unique`(`order_id`, `attempt_number`);
ALTER TABLE `delivery_attempts` ADD INDEX `delivery_attempt_agent_status_idx`(`delivery_agent_id`, `status`);
ALTER TABLE `delivery_attempts` ADD INDEX `delivery_attempt_store_status_idx`(`stock_location_id`, `status`);
ALTER TABLE `delivery_attempts` ADD INDEX `delivery_attempts_status_index`(`status`);

CREATE TABLE `delivery_otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `delivery_attempt_id` BIGINT UNSIGNED NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `otp_ciphertext` TEXT NULL,
  `generated_at` TIMESTAMP NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used_at` TIMESTAMP NULL,
  `invalidated_at` TIMESTAMP NULL,
  `failed_attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT '0',
  `active_delivery_attempt_id` BIGINT UNSIGNED AS (CASE WHEN `used_at` IS NULL AND `invalidated_at` IS NULL THEN `delivery_attempt_id` ELSE NULL END),
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `delivery_otps` ADD CONSTRAINT `delivery_otps_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `delivery_otps` ADD CONSTRAINT `delivery_otps_delivery_attempt_id_foreign` FOREIGN KEY (`delivery_attempt_id`) REFERENCES `delivery_attempts` (`id`) ON DELETE CASCADE;
ALTER TABLE `delivery_otps` ADD UNIQUE `delivery_otp_one_active_attempt_unique`(`active_delivery_attempt_id`);
ALTER TABLE `delivery_otps` ADD INDEX `delivery_otps_order_id_expires_at_index`(`order_id`, `expires_at`);
ALTER TABLE `delivery_otps` ADD INDEX `delivery_otps_generated_at_index`(`generated_at`);
ALTER TABLE `delivery_otps` ADD INDEX `delivery_otps_expires_at_index`(`expires_at`);
ALTER TABLE `delivery_otps` ADD INDEX `delivery_otps_used_at_index`(`used_at`);
ALTER TABLE `delivery_otps` ADD INDEX `delivery_otps_invalidated_at_index`(`invalidated_at`);

CREATE TABLE `delivery_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `delivery_attempt_id` BIGINT UNSIGNED NULL,
  `stock_location_id` BIGINT UNSIGNED NULL,
  `actor_user_id` BIGINT UNSIGNED NULL,
  `event_type` VARCHAR(50) NOT NULL,
  `occurred_at` TIMESTAMP NOT NULL,
  `latitude` DECIMAL(10, 7) NULL,
  `longitude` DECIMAL(10, 7) NULL,
  `accuracy_meters` DECIMAL(8, 2) NULL,
  `distance_from_customer_meters` DECIMAL(10, 2) NULL,
  `geofence_result` VARCHAR(30) NULL,
  `reason_code` VARCHAR(80) NULL,
  `notes` TEXT NULL,
  `otp_verified` TINYINT(1) NOT NULL DEFAULT '0',
  `otp_override_approved` TINYINT(1) NOT NULL DEFAULT '0',
  `override_approved_by` BIGINT UNSIGNED NULL,
  `manager_review_required` TINYINT(1) NOT NULL DEFAULT '0',
  `review_status` VARCHAR(30) NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `delivery_events` ADD CONSTRAINT `delivery_events_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
ALTER TABLE `delivery_events` ADD CONSTRAINT `delivery_events_delivery_attempt_id_foreign` FOREIGN KEY (`delivery_attempt_id`) REFERENCES `delivery_attempts` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_events` ADD CONSTRAINT `delivery_events_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_events` ADD CONSTRAINT `delivery_events_actor_user_id_foreign` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_events` ADD CONSTRAINT `delivery_events_override_approved_by_foreign` FOREIGN KEY (`override_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_order_type_time_idx`(`order_id`, `event_type`, `occurred_at`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_actor_time_idx`(`actor_user_id`, `occurred_at`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_event_type_index`(`event_type`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_occurred_at_index`(`occurred_at`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_geofence_result_index`(`geofence_result`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_manager_review_required_index`(`manager_review_required`);
ALTER TABLE `delivery_events` ADD INDEX `delivery_events_review_status_index`(`review_status`);

CREATE TABLE `staff_approval_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `stock_location_id` BIGINT UNSIGNED NULL,
  `approval_type` VARCHAR(60) NOT NULL,
  `subject_type` VARCHAR(255) NULL,
  `subject_id` BIGINT UNSIGNED NULL,
  `requested_by` BIGINT UNSIGNED NOT NULL,
  `requested_at` TIMESTAMP NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'PENDING',
  `checked_by` BIGINT UNSIGNED NULL,
  `checked_at` TIMESTAMP NULL,
  `reason_code` VARCHAR(80) NULL,
  `notes` TEXT NULL,
  `evidence` JSON NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL
) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci' ENGINE=InnoDB;
ALTER TABLE `staff_approval_requests` ADD CONSTRAINT `staff_approval_requests_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approval_requests_subject_type_subject_id_index`(`subject_type`, `subject_id`);
ALTER TABLE `staff_approval_requests` ADD CONSTRAINT `staff_approval_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
ALTER TABLE `staff_approval_requests` ADD CONSTRAINT `staff_approval_requests_checked_by_foreign` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approvals_store_type_status_idx`(`stock_location_id`, `approval_type`, `status`);
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approvals_requester_status_idx`(`requested_by`, `status`);
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approval_requests_approval_type_index`(`approval_type`);
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approval_requests_requested_at_index`(`requested_at`);
ALTER TABLE `staff_approval_requests` ADD INDEX `staff_approval_requests_status_index`(`status`);

-- ==================================================
-- C. POST-APPLY verification queries
-- ==================================================
SELECT table_name, engine
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests')
ORDER BY table_name;

SELECT table_name, column_name, column_type, is_nullable, column_default, generation_expression
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND table_name IN ('users','customer_addresses','staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests')
  AND (
    table_name NOT IN ('users','customer_addresses')
    OR column_name IN ('staff_roles','additional_permissions','denied_permissions','staff_active','staff_approved_at','staff_approved_by','latitude','longitude','geofence_radius_meters')
  )
ORDER BY table_name, ordinal_position;

SELECT table_name, index_name, non_unique, group_concat(column_name ORDER BY seq_in_index) AS columns
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND table_name IN ('users','staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests')
GROUP BY table_name, index_name, non_unique
ORDER BY table_name, index_name;

SELECT table_name, constraint_name, constraint_type
FROM information_schema.table_constraints
WHERE table_schema = DATABASE()
  AND table_name IN ('staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests')
ORDER BY table_name, constraint_name;

SELECT constraint_name, table_name, column_name, referenced_table_name, referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = DATABASE()
  AND table_name IN ('users','staff_notifications','order_staff_assignments','delivery_attempts','delivery_otps','delivery_events','staff_approval_requests')
  AND referenced_table_name IS NOT NULL
ORDER BY table_name, constraint_name, ordinal_position;

-- Expected post-apply:
-- 1. All six new 4.24B tables exist using InnoDB.
-- 2. users and customer_addresses contain the new additive columns.
-- 3. delivery_otps.active_delivery_attempt_id is a generated column with the one-active-OTP unique key.
-- 4. All listed indexes, unique constraints, and foreign keys are present.

-- ==================================================
-- D. MIGRATION registration guidance
-- ==================================================
-- Do not guess the production batch number. At deployment time, run:
SELECT COALESCE(MAX(batch), 0) AS current_max_batch FROM migrations;

-- Then insert the migration row with batch = current_max_batch + 1:
-- INSERT INTO `migrations` (`migration`, `batch`)
-- VALUES ('2026_08_17_000001_create_staff_operations_portal_tables', <NEXT_BATCH_NUMBER>);
