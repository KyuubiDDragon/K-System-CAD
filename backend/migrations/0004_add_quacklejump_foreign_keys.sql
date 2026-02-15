-- Migration: Add indexes for QuackleJump foreign key columns
-- Date: 2025-06-01
-- Description: Adds indexes for authority_id and user_id for better performance
-- Note: Foreign key constraints temporarily disabled due to compatibility issues

-- Add index for authority_id to improve query performance
ALTER TABLE `quacklejump_scores` 
ADD INDEX `idx_authority_id` (`authority_id`);

-- Add index for user_id to improve query performance  
ALTER TABLE `quacklejump_scores` 
ADD INDEX `idx_user_id` (`user_id`);