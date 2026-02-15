-- Migration: Add QuackleJump game tables
-- Date: 2024-01-20
-- Description: Creates tables for storing QuackleJump game scores and statistics

-- Create quacklejump_scores table
CREATE TABLE IF NOT EXISTS `quacklejump_scores` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `player_name` VARCHAR(50) NOT NULL,
    `score` int(11) NOT NULL,
    `height_reached` int(11) NOT NULL,
    `play_time` int(11) NOT NULL,
    `power_ups_collected` int(11) DEFAULT 0,
    `enemies_defeated` int(11) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `authority_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_authority_score` (`authority_id`, `score`),
    KEY `idx_user_score` (`user_id`, `score`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;