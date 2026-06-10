-- User profile photo upload
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL DEFAULT NULL AFTER reset_token_expires_at;
