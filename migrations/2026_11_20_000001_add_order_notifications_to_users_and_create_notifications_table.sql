SET @add_order_notifications_enabled = IF(
    EXISTS(
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
            AND table_name = 'users'
            AND column_name = 'order_notifications_enabled'
    ),
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN order_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER is_staff'
);
PREPARE add_order_notifications_enabled_stmt FROM @add_order_notifications_enabled;
EXECUTE add_order_notifications_enabled_stmt;
DEALLOCATE PREPARE add_order_notifications_enabled_stmt;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` CHAR(36) NOT NULL,
    `type` VARCHAR(255) NOT NULL,
    `notifiable_type` VARCHAR(255) NOT NULL,
    `notifiable_id` BIGINT UNSIGNED NOT NULL,
    `data` TEXT NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`, `notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
