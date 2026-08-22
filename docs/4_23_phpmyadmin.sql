-- Milestone 4.23 phpMyAdmin SQL
-- Registration names:
-- 2026_08_16_000001_add_multi_store_operational_fields
-- 2026_08_16_000002_create_store_pricing_announcements_and_storefront_overrides
-- Run before deploying the matching application code. Do not run twice.

ALTER TABLE `stock_locations` ADD `manager_name` VARCHAR(255) NULL AFTER `pincode`;
ALTER TABLE `stock_locations` ADD `phone` VARCHAR(255) NULL AFTER `manager_name`;
ALTER TABLE `stock_locations` ADD `email` VARCHAR(255) NULL AFTER `phone`;
ALTER TABLE `stock_locations` ADD `accepts_online_orders` TINYINT(1) NOT NULL DEFAULT '1' AFTER `status`;
ALTER TABLE `stock_locations` ADD INDEX `stock_locations_accepts_online_orders_index` (`accepts_online_orders`);

ALTER TABLE `users` ADD `role` VARCHAR(40) NULL AFTER `password`;
ALTER TABLE `users` ADD `assigned_store_id` BIGINT UNSIGNED NULL AFTER `role`;
ALTER TABLE `users` ADD CONSTRAINT `users_assigned_store_id_foreign` FOREIGN KEY (`assigned_store_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `users` ADD INDEX `users_role_index` (`role`);

ALTER TABLE `customers` ADD `assigned_store_id` BIGINT UNSIGNED NULL AFTER `is_premium`;
ALTER TABLE `customers` ADD CONSTRAINT `customers_assigned_store_id_foreign` FOREIGN KEY (`assigned_store_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `customers` ADD INDEX `customers_assigned_store_id_status_index` (`assigned_store_id`, `status`);

ALTER TABLE `carts` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `customer_id`;
ALTER TABLE `carts` ADD CONSTRAINT `carts_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `carts` ADD INDEX `carts_stock_location_id_status_index` (`stock_location_id`, `status`);

ALTER TABLE `pending_orders` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `cart_id`;
ALTER TABLE `pending_orders` ADD CONSTRAINT `pending_orders_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `pending_orders` ADD INDEX `pending_orders_stock_location_id_status_index` (`stock_location_id`, `status`);

ALTER TABLE `orders` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `cart_id`;
ALTER TABLE `orders` ADD CONSTRAINT `orders_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `orders` ADD `store_name_snapshot` VARCHAR(255) NULL AFTER `stock_location_id`;
ALTER TABLE `orders` ADD `store_code_snapshot` VARCHAR(255) NULL AFTER `store_name_snapshot`;
ALTER TABLE `orders` ADD INDEX `orders_stock_location_id_order_status_index` (`stock_location_id`, `order_status`);

ALTER TABLE `purchase_entries` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `supplier_id`;
ALTER TABLE `purchase_entries` ADD CONSTRAINT `purchase_entries_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `purchase_entries` ADD `store_name_snapshot` VARCHAR(255) NULL AFTER `stock_location_id`;
ALTER TABLE `purchase_entries` ADD `store_code_snapshot` VARCHAR(255) NULL AFTER `store_name_snapshot`;
ALTER TABLE `purchase_entries` ADD INDEX `purchase_entries_stock_location_id_purchase_date_index` (`stock_location_id`, `purchase_date`);

ALTER TABLE `daily_offers` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `product_variant_id`;
ALTER TABLE `daily_offers` ADD CONSTRAINT `daily_offers_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE SET NULL;
ALTER TABLE `daily_offers` ADD INDEX `daily_offers_stock_location_id_is_active_index` (`stock_location_id`, `is_active`);

ALTER TABLE `order_items` ADD `brand_id_snapshot` BIGINT UNSIGNED NULL AFTER `product_id`;
ALTER TABLE `order_items` ADD `brand_name_snapshot` VARCHAR(255) NULL AFTER `brand_id_snapshot`;

SET @main_store_id := (
    SELECT `id`
    FROM `stock_locations`
    WHERE `is_default` = 1 AND `deleted_at` IS NULL
    ORDER BY `id` ASC
    LIMIT 1
);

SET @main_store_id := COALESCE(@main_store_id, (
    SELECT `id`
    FROM `stock_locations`
    WHERE `deleted_at` IS NULL
    ORDER BY `id` ASC
    LIMIT 1
));

UPDATE `customers` SET `assigned_store_id` = @main_store_id WHERE `assigned_store_id` IS NULL AND @main_store_id IS NOT NULL;
UPDATE `carts` SET `stock_location_id` = @main_store_id WHERE `stock_location_id` IS NULL AND @main_store_id IS NOT NULL;
UPDATE `pending_orders` SET `stock_location_id` = @main_store_id WHERE `stock_location_id` IS NULL AND @main_store_id IS NOT NULL;
UPDATE `orders` SET `stock_location_id` = @main_store_id WHERE `stock_location_id` IS NULL AND @main_store_id IS NOT NULL;
UPDATE `purchase_entries` SET `stock_location_id` = @main_store_id WHERE `stock_location_id` IS NULL AND @main_store_id IS NOT NULL;
UPDATE `daily_offers` SET `stock_location_id` = @main_store_id WHERE `stock_location_id` IS NULL AND @main_store_id IS NOT NULL;

CREATE TABLE `store_variant_prices` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `stock_location_id` BIGINT UNSIGNED NOT NULL, `product_variant_id` BIGINT UNSIGNED NOT NULL, `mrp` DECIMAL(12, 2) NULL, `selling_price` DECIMAL(12, 2) NOT NULL, `effective_from` TIMESTAMP NULL, `effective_until` TIMESTAMP NULL, `source` VARCHAR(40) NOT NULL DEFAULT 'manual', `changed_by` BIGINT UNSIGNED NULL, `status` TINYINT(1) NOT NULL DEFAULT '1', `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `store_variant_prices` ADD CONSTRAINT `store_variant_prices_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_variant_prices` ADD CONSTRAINT `store_variant_prices_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_variant_prices` ADD CONSTRAINT `store_variant_prices_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `store_variant_prices` ADD UNIQUE `store_variant_prices_store_variant_unique` (`stock_location_id`, `product_variant_id`);
ALTER TABLE `store_variant_prices` ADD INDEX `store_variant_prices_product_variant_id_status_index` (`product_variant_id`, `status`);
ALTER TABLE `store_variant_prices` ADD INDEX `store_variant_prices_effective_from_index` (`effective_from`);
ALTER TABLE `store_variant_prices` ADD INDEX `store_variant_prices_effective_until_index` (`effective_until`);
ALTER TABLE `store_variant_prices` ADD INDEX `store_variant_prices_source_index` (`source`);
ALTER TABLE `store_variant_prices` ADD INDEX `store_variant_prices_status_index` (`status`);

CREATE TABLE `store_variant_price_histories` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `stock_location_id` BIGINT UNSIGNED NOT NULL, `product_variant_id` BIGINT UNSIGNED NOT NULL, `old_mrp` DECIMAL(12, 2) NULL, `old_selling_price` DECIMAL(12, 2) NULL, `new_mrp` DECIMAL(12, 2) NULL, `new_selling_price` DECIMAL(12, 2) NOT NULL, `change_reason` VARCHAR(255) NULL, `changed_by` BIGINT UNSIGNED NULL, `changed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `store_variant_price_histories` ADD CONSTRAINT `store_variant_price_histories_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_variant_price_histories` ADD CONSTRAINT `store_variant_price_histories_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_variant_price_histories` ADD CONSTRAINT `store_variant_price_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `store_variant_price_histories` ADD INDEX `store_price_history_lookup_index` (`stock_location_id`, `product_variant_id`, `changed_at`);
ALTER TABLE `store_variant_price_histories` ADD INDEX `store_variant_price_histories_changed_at_index` (`changed_at`);

CREATE TABLE `store_price_update_batches` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `stock_location_id` BIGINT UNSIGNED NOT NULL, `name` VARCHAR(255) NOT NULL, `status` VARCHAR(30) NOT NULL DEFAULT 'scheduled', `scheduled_for` TIMESTAMP NULL, `applied_at` TIMESTAMP NULL, `created_by` BIGINT UNSIGNED NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `store_price_update_batches` ADD CONSTRAINT `store_price_update_batches_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_price_update_batches` ADD CONSTRAINT `store_price_update_batches_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `store_price_update_batches` ADD INDEX `store_price_update_batches_status_index` (`status`);
ALTER TABLE `store_price_update_batches` ADD INDEX `store_price_update_batches_scheduled_for_index` (`scheduled_for`);

CREATE TABLE `store_price_update_batch_items` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `store_price_update_batch_id` BIGINT UNSIGNED NOT NULL, `product_variant_id` BIGINT UNSIGNED NOT NULL, `mrp` DECIMAL(12, 2) NULL, `selling_price` DECIMAL(12, 2) NOT NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `store_price_update_batch_items` ADD CONSTRAINT `store_price_update_batch_items_store_price_update_batch_id_foreign` FOREIGN KEY (`store_price_update_batch_id`) REFERENCES `store_price_update_batches` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_price_update_batch_items` ADD CONSTRAINT `store_price_update_batch_items_product_variant_id_foreign` FOREIGN KEY (`product_variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;
ALTER TABLE `store_price_update_batch_items` ADD UNIQUE `store_price_batch_items_unique` (`store_price_update_batch_id`, `product_variant_id`);

ALTER TABLE `categories` ADD `rapid_price_update_enabled` TINYINT(1) NOT NULL DEFAULT '0' AFTER `status`;
ALTER TABLE `categories` ADD INDEX `categories_rapid_price_update_enabled_index` (`rapid_price_update_enabled`);
UPDATE `categories` SET `rapid_price_update_enabled` = 1 WHERE (`slug` IN ('fruits-vegetables', 'vegetables-fruits', 'fruits', 'vegetables') OR `name` LIKE '%Fruit%' OR `name` LIKE '%Vegetable%');

ALTER TABLE `homepage_sections` DROP INDEX `homepage_sections_section_key_unique`;
ALTER TABLE `homepage_sections` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `homepage_sections` ADD CONSTRAINT `homepage_sections_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `homepage_sections` ADD `homepage_section_store_identity` BIGINT UNSIGNED AS (COALESCE(stock_location_id, 0)) AFTER `stock_location_id`;
ALTER TABLE `homepage_sections` ADD `icon_path` VARCHAR(255) NULL AFTER `subtitle`;
ALTER TABLE `homepage_sections` ADD UNIQUE `homepage_sections_store_section_unique` (`homepage_section_store_identity`, `section_key`);
ALTER TABLE `homepage_sections` ADD INDEX `homepage_sections_store_enabled_order_index` (`stock_location_id`, `enabled`, `sort_order`);

ALTER TABLE `homepage_banners` ADD `stock_location_id` BIGINT UNSIGNED NULL AFTER `id`;
ALTER TABLE `homepage_banners` ADD CONSTRAINT `homepage_banners_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `homepage_banners` ADD INDEX `homepage_banners_store_enabled_order_index` (`stock_location_id`, `enabled`, `sort_order`);

CREATE TABLE `storefront_page_backgrounds` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `stock_location_id` BIGINT UNSIGNED NULL, `storefront_background_store_identity` BIGINT UNSIGNED AS (COALESCE(stock_location_id, 0)), `page_key` VARCHAR(80) NOT NULL, `background_path` VARCHAR(255) NOT NULL, `is_enabled` TINYINT(1) NOT NULL DEFAULT '1', `opacity` DECIMAL(4, 2) NOT NULL DEFAULT '1', `repeat_mode` VARCHAR(30) NOT NULL DEFAULT 'no-repeat', `position` VARCHAR(50) NOT NULL DEFAULT 'center center', `size_mode` VARCHAR(30) NOT NULL DEFAULT 'cover', `enabled` TINYINT(1) NOT NULL DEFAULT '1', `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `storefront_page_backgrounds` ADD CONSTRAINT `storefront_page_backgrounds_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `storefront_page_backgrounds` ADD UNIQUE `storefront_page_backgrounds_store_page_unique` (`storefront_background_store_identity`, `page_key`);
ALTER TABLE `storefront_page_backgrounds` ADD INDEX `storefront_page_backgrounds_page_key_index` (`page_key`);
ALTER TABLE `storefront_page_backgrounds` ADD INDEX `storefront_page_backgrounds_is_enabled_index` (`is_enabled`);
ALTER TABLE `storefront_page_backgrounds` ADD INDEX `storefront_page_backgrounds_enabled_index` (`enabled`);

CREATE TABLE `customer_announcements` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` VARCHAR(255) NULL, `message` TEXT NOT NULL, `audience_type` VARCHAR(40) NOT NULL DEFAULT 'all', `sticky` TINYINT(1) NOT NULL DEFAULT '0', `dismissible` TINYINT(1) NOT NULL DEFAULT '1', `priority` INT UNSIGNED NOT NULL DEFAULT '0', `cta_text` VARCHAR(255) NULL, `cta_url` VARCHAR(255) NULL, `starts_at` TIMESTAMP NULL, `ends_at` TIMESTAMP NULL, `enabled` TINYINT(1) NOT NULL DEFAULT '1', `inactive_since` TIMESTAMP NULL, `created_by` BIGINT UNSIGNED NULL, `cleanup_eligible_at` TIMESTAMP NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, `deleted_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_announcements` ADD CONSTRAINT `customer_announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_audience_type_index` (`audience_type`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_sticky_index` (`sticky`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_priority_index` (`priority`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_starts_at_index` (`starts_at`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_ends_at_index` (`ends_at`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_enabled_index` (`enabled`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_inactive_since_index` (`inactive_since`);
ALTER TABLE `customer_announcements` ADD INDEX `customer_announcements_cleanup_eligible_at_index` (`cleanup_eligible_at`);

CREATE TABLE `customer_announcement_stock_location` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `customer_announcement_id` BIGINT UNSIGNED NOT NULL, `stock_location_id` BIGINT UNSIGNED NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_announcement_stock_location` ADD CONSTRAINT `customer_announcement_stock_location_customer_announcement_id_foreign` FOREIGN KEY (`customer_announcement_id`) REFERENCES `customer_announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_stock_location` ADD CONSTRAINT `customer_announcement_stock_location_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_stock_location` ADD UNIQUE `announcement_store_unique` (`customer_announcement_id`, `stock_location_id`);

CREATE TABLE `customer_announcement_customer` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `customer_announcement_id` BIGINT UNSIGNED NOT NULL, `customer_id` BIGINT UNSIGNED NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_announcement_customer` ADD CONSTRAINT `customer_announcement_customer_customer_announcement_id_foreign` FOREIGN KEY (`customer_announcement_id`) REFERENCES `customer_announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_customer` ADD CONSTRAINT `customer_announcement_customer_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_customer` ADD UNIQUE `announcement_customer_unique` (`customer_announcement_id`, `customer_id`);

CREATE TABLE `customer_announcement_dismissals` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `customer_announcement_id` BIGINT UNSIGNED NOT NULL, `customer_id` BIGINT UNSIGNED NOT NULL, `dismissed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_announcement_dismissals` ADD CONSTRAINT `customer_announcement_dismissals_customer_announcement_id_foreign` FOREIGN KEY (`customer_announcement_id`) REFERENCES `customer_announcements` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_dismissals` ADD CONSTRAINT `customer_announcement_dismissals_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_announcement_dismissals` ADD UNIQUE `announcement_dismissals_unique` (`customer_announcement_id`, `customer_id`);

CREATE TABLE `customer_marketing_banners` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `title` VARCHAR(255) NULL, `subtitle` VARCHAR(255) NULL, `image_path` VARCHAR(255) NOT NULL, `mobile_image_path` VARCHAR(255) NULL, `cta_text` VARCHAR(255) NULL, `cta_url` VARCHAR(255) NULL, `display_order` TINYINT UNSIGNED NOT NULL DEFAULT '0', `priority` INT UNSIGNED NOT NULL DEFAULT '0', `starts_at` TIMESTAMP NULL, `ends_at` TIMESTAMP NULL, `enabled` TINYINT(1) NOT NULL DEFAULT '1', `inactive_since` TIMESTAMP NULL, `created_by` BIGINT UNSIGNED NULL, `cleanup_eligible_at` TIMESTAMP NULL, `created_at` TIMESTAMP NULL, `updated_at` TIMESTAMP NULL, `deleted_at` TIMESTAMP NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_marketing_banners` ADD CONSTRAINT `customer_marketing_banners_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_display_order_index` (`display_order`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_priority_index` (`priority`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_starts_at_index` (`starts_at`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_ends_at_index` (`ends_at`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_enabled_index` (`enabled`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_inactive_since_index` (`inactive_since`);
ALTER TABLE `customer_marketing_banners` ADD INDEX `customer_marketing_banners_cleanup_eligible_at_index` (`cleanup_eligible_at`);

CREATE TABLE `customer_marketing_banner_stock_location` (`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `customer_marketing_banner_id` BIGINT UNSIGNED NOT NULL, `stock_location_id` BIGINT UNSIGNED NOT NULL) DEFAULT CHARACTER SET utf8mb4 COLLATE 'utf8mb4_unicode_ci';
ALTER TABLE `customer_marketing_banner_stock_location` ADD CONSTRAINT `customer_marketing_banner_stock_location_customer_marketing_banner_id_foreign` FOREIGN KEY (`customer_marketing_banner_id`) REFERENCES `customer_marketing_banners` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_marketing_banner_stock_location` ADD CONSTRAINT `customer_marketing_banner_stock_location_stock_location_id_foreign` FOREIGN KEY (`stock_location_id`) REFERENCES `stock_locations` (`id`) ON DELETE CASCADE;
ALTER TABLE `customer_marketing_banner_stock_location` ADD UNIQUE `marketing_banner_store_unique` (`customer_marketing_banner_id`, `stock_location_id`);
