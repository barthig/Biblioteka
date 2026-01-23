-- Quick seed script for demo users
-- Password for all users: demo123
-- Hash generated with PHP: password_hash('demo123', PASSWORD_BCRYPT, ['cost' => 13])

INSERT INTO app_user (email, name, roles, password, phone, membership_group, created_at, updated_at) VALUES
('admin@biblioteka.pl', 'Administrator Systemu', '["ROLE_ADMIN"]', '$2y$13$VvXQz8rKxHZqF8eJZ.YQ2eZ8sHmZKF6kYL2CqW9p1qXnCZ4LqJW5G', '+48123456789', 'standard', NOW(), NOW()),
('bibliotekarz@biblioteka.pl', 'Jan Bibliotekarz', '["ROLE_LIBRARIAN"]', '$2y$13$VvXQz8rKxHZqF8eJZ.YQ2eZ8sHmZKF6kYL2CqW9p1qXnCZ4LqJW5G', '+48123456790', 'standard', NOW(), NOW()),
('czytelnik1@example.com', 'Anna Kowalska', '["ROLE_USER"]', '$2y$13$VvXQz8rKxHZqF8eJZ.YQ2eZ8sHmZKF6kYL2CqW9p1qXnCZ4LqJW5G', '+48123456791', 'standard', NOW(), NOW()),
('czytelnik2@example.com', 'Piotr Nowak', '["ROLE_USER"]', '$2y$13$VvXQz8rKxHZqF8eJZ.YQ2eZ8sHmZKF6kYL2CqW9p1qXnCZ4LqJW5G', '+48123456792', 'student', NOW(), NOW()),
('czytelnik3@example.com', 'Maria Wiśniewska', '["ROLE_USER"]', '$2y$13$VvXQz8rKxHZqF8eJZ.YQ2eZ8sHmZKF6kYL2CqW9p1qXnCZ4LqJW5G', '+48123456793', 'standard', NOW(), NOW())
ON CONFLICT (email) DO NOTHING;

SELECT 'Demo users created!' AS message, COUNT(*) AS total_users FROM app_user;
