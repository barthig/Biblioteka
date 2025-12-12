-- Inicjalizacja bazy danych Biblioteka
-- Ten plik jest uruchamiany automatycznie przez PostgreSQL przy pierwszym uruchomieniu kontenera

\echo 'Rozpoczynam inicjalizację bazy danych...'

-- Oznaczamy wszystkie migracje jako wykonane
CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    execution_time INTEGER DEFAULT NULL,
    PRIMARY KEY(version)
);

-- Dodaj wersje migracji
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
('DoctrineMigrations\Version20251211115135', NOW(), 1),
('DoctrineMigrations\Version20251211120000', NOW(), 1),
('DoctrineMigrations\Version20251211121430', NOW(), 1),
('DoctrineMigrations\Version20251211123131', NOW(), 1),
('DoctrineMigrations\Version20251211130000', NOW(), 1),
('DoctrineMigrations\Version20251211190624', NOW(), 1)
ON CONFLICT (version) DO NOTHING;

\echo 'Baza danych została zainicjalizowana pomyślnie!'
