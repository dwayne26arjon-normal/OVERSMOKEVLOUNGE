CREATE TABLE IF NOT EXISTS `refund_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `reviewed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `completed_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `status` VARCHAR(255) NOT NULL DEFAULT 'pending',
    `description` TEXT NOT NULL,
    `admin_note` TEXT NULL DEFAULT NULL,
    `approved_amount` DECIMAL(10, 2) NULL DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `refund_requests_order_id_unique` (`order_id`),
    KEY `refund_requests_user_id_foreign` (`user_id`),
    KEY `refund_requests_reviewed_by_foreign` (`reviewed_by`),
    KEY `refund_requests_completed_by_foreign` (`completed_by`),
    KEY `refund_requests_status_created_at_index` (`status`, `created_at`),
    CONSTRAINT `refund_requests_order_id_foreign`
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `refund_requests_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `refund_requests_reviewed_by_foreign`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `refund_requests_completed_by_foreign`
        FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `refund_evidences` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `refund_request_id` BIGINT UNSIGNED NOT NULL,
    `disk` VARCHAR(255) NOT NULL DEFAULT 'local',
    `path` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) NOT NULL,
    `size` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `refund_evidences_path_unique` (`path`),
    KEY `refund_evidences_refund_request_id_foreign` (`refund_request_id`),
    CONSTRAINT `refund_evidences_refund_request_id_foreign`
        FOREIGN KEY (`refund_request_id`) REFERENCES `refund_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
