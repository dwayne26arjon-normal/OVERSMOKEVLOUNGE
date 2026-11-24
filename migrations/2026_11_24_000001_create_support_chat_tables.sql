CREATE TABLE IF NOT EXISTS `support_chat_conversations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `support_chat_conversations_user_id_unique` (`user_id`),
    CONSTRAINT `support_chat_conversations_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `support_chat_messages` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `sender_role` VARCHAR(20) NOT NULL,
    `sender_name` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `support_chat_messages_conversation_id_id_index` (`conversation_id`, `id`),
    KEY `support_chat_messages_conversation_id_sender_role_read_at_index` (`conversation_id`, `sender_role`, `read_at`),
    CONSTRAINT `support_chat_messages_conversation_id_foreign`
        FOREIGN KEY (`conversation_id`) REFERENCES `support_chat_conversations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `support_chat_messages_sender_id_foreign`
        FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
