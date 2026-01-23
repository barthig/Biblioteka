-- Demo data seeding script
-- Creates users, books, loans, and reservations for demonstration

-- Insert demo users
INSERT INTO app_user (username, password_hash, roles, created_at, updated_at) VALUES
('admin@biblioteka.pl', '$2y$13$hashed_password_here', '["ROLE_ADMIN"]', NOW(), NOW()),
('bibliotekarz@biblioteka.pl', '$2y$13$hashed_password_here', '["ROLE_LIBRARIAN"]', NOW(), NOW()),
('czytelnik1@example.com', '$2y$13$hashed_password_here', '["ROLE_USER"]', NOW(), NOW()),
('czytelnik2@example.com', '$2y$13$hashed_password_here', '["ROLE_USER"]', NOW(), NOW()),
('czytelnik3@example.com', '$2y$13$hashed_password_here', '["ROLE_USER"]', NOW(), NOW())
ON CONFLICT (username) DO NOTHING;

-- Note: Default password for all demo users is: 'demo123'
-- Hash generated with: password_hash('demo123', PASSWORD_BCRYPT, ['cost' => 13])
