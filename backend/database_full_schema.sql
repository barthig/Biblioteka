-- ============================================
-- Pełna struktura bazy danych systemu bibliotecznego
-- Data utworzenia: 2025-12-11
-- System: Biblioteka - System Zarządzania Biblioteką
-- ============================================

-- ============================================
-- SEKWENCJE
-- ============================================

CREATE SEQUENCE acquisition_budget_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE acquisition_expense_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE acquisition_order_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE audit_logs_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE author_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE backup_record_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE book_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE book_copy_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE book_digital_asset_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE favorite_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE fine_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE integration_config_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE loan_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE notification_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE registration_token_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE reservation_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE review_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE staff_role_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE supplier_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE system_setting_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE app_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1;
CREATE SEQUENCE weeding_record_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

-- ============================================
-- TABELE GŁÓWNE
-- ============================================

-- Tabela: app_user - Użytkownicy systemu
CREATE TABLE app_user (
    id INT NOT NULL,
    email VARCHAR(180) NOT NULL,
    name VARCHAR(255) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(30) DEFAULT NULL,
    address_line VARCHAR(255) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    postal_code VARCHAR(12) DEFAULT NULL,
    blocked BOOLEAN DEFAULT false NOT NULL,
    verified BOOLEAN DEFAULT false NOT NULL,
    verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    pending_approval BOOLEAN DEFAULT false NOT NULL,
    membership_group VARCHAR(64) DEFAULT 'standard' NOT NULL,
    loan_limit INT DEFAULT 5 NOT NULL,
    blocked_reason VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    privacy_consent_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    newsletter_subscribed BOOLEAN DEFAULT true NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_88BDF3E9E7927C74 ON app_user (email);
COMMENT ON COLUMN app_user.verified_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN app_user.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN app_user.updated_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN app_user.privacy_consent_at IS '(DC2Type:datetime_immutable)';

-- Tabela: author - Autorzy książek
CREATE TABLE author (
    id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_BDAFD8C85E237E06 ON author (name);

-- Tabela: category - Kategorie książek
CREATE TABLE category (
    id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_64C19C15E237E06 ON category (name);

-- Tabela: book - Książki
CREATE TABLE book (
    id INT NOT NULL,
    author_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    copies INT NOT NULL,
    total_copies INT NOT NULL,
    storage_copies INT NOT NULL,
    open_stack_copies INT NOT NULL,
    description TEXT DEFAULT NULL,
    publisher VARCHAR(180) DEFAULT NULL,
    publication_year SMALLINT DEFAULT NULL,
    resource_type VARCHAR(60) DEFAULT NULL,
    signature VARCHAR(60) DEFAULT NULL,
    target_age_group VARCHAR(24) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_CBE5A331F675F31B ON book (author_id);

-- Tabela: book_category - Relacja wiele do wielu między książkami a kategoriami
CREATE TABLE book_category (
    book_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY(book_id, category_id)
);

CREATE INDEX IDX_1FB30F9816A2B381 ON book_category (book_id);
CREATE INDEX IDX_1FB30F9812469DE2 ON book_category (category_id);

-- Tabela: book_copy - Egzemplarze książek
CREATE TABLE book_copy (
    id INT NOT NULL,
    book_id INT NOT NULL,
    inventory_code VARCHAR(60) NOT NULL,
    status VARCHAR(20) NOT NULL,
    location VARCHAR(120) DEFAULT NULL,
    access_type VARCHAR(30) NOT NULL,
    condition_state VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_5427F08A3DB3FFB6 ON book_copy (inventory_code);
CREATE INDEX IDX_5427F08A16A2B381 ON book_copy (book_id);
COMMENT ON COLUMN book_copy.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN book_copy.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: book_digital_asset - Zasoby cyfrowe książek (okładki, pliki)
CREATE TABLE book_digital_asset (
    id INT NOT NULL,
    book_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT NOT NULL,
    storage_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_817C37D0570EB513 ON book_digital_asset (storage_name);
CREATE INDEX IDX_817C37D016A2B381 ON book_digital_asset (book_id);
COMMENT ON COLUMN book_digital_asset.created_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- WYPOŻYCZENIA I REZERWACJE
-- ============================================

-- Tabela: loan - Wypożyczenia
CREATE TABLE loan (
    id INT NOT NULL,
    book_id INT NOT NULL,
    book_copy_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    borrowed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    due_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    returned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    extensions_count INT NOT NULL,
    last_extended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_C5D30D0316A2B381 ON loan (book_id);
CREATE INDEX IDX_C5D30D033B550FE4 ON loan (book_copy_id);
CREATE INDEX IDX_C5D30D03A76ED395 ON loan (user_id);

-- Tabela: reservation - Rezerwacje
CREATE TABLE reservation (
    id INT NOT NULL,
    book_id INT NOT NULL,
    book_copy_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    reserved_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    fulfilled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_42C8495516A2B381 ON reservation (book_id);
CREATE INDEX IDX_42C849553B550FE4 ON reservation (book_copy_id);
CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id);
COMMENT ON COLUMN reservation.reserved_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN reservation.expires_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN reservation.fulfilled_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN reservation.cancelled_at IS '(DC2Type:datetime_immutable)';

-- Tabela: fine - Kary/grzywny
CREATE TABLE fine (
    id INT NOT NULL,
    loan_id INT NOT NULL,
    amount NUMERIC(8, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_BEA95492CE73868F ON fine (loan_id);
COMMENT ON COLUMN fine.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN fine.paid_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- MODUŁ UŻYTKOWNIKÓW
-- ============================================

-- Tabela: registration_token - Tokeny rejestracyjne
CREATE TABLE registration_token (
    id INT NOT NULL,
    user_id INT NOT NULL,
    token VARCHAR(96) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_D09D01D35F37A13B ON registration_token (token);
CREATE INDEX IDX_D09D01D3A76ED395 ON registration_token (user_id);
CREATE INDEX registration_token_lookup ON registration_token (token);
COMMENT ON COLUMN registration_token.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN registration_token.expires_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN registration_token.used_at IS '(DC2Type:datetime_immutable)';

-- Tabela: favorite - Ulubione książki użytkowników
CREATE TABLE favorite (
    id INT NOT NULL,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_68C58ED9A76ED395 ON favorite (user_id);
CREATE INDEX IDX_68C58ED916A2B381 ON favorite (book_id);
CREATE UNIQUE INDEX favorite_user_book_unique ON favorite (user_id, book_id);
COMMENT ON COLUMN favorite.created_at IS '(DC2Type:datetime_immutable)';

-- Tabela: review - Recenzje książek
CREATE TABLE review (
    id INT NOT NULL,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    rating SMALLINT NOT NULL,
    comment TEXT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_794381C616A2B381 ON review (book_id);
CREATE INDEX IDX_794381C6A76ED395 ON review (user_id);
CREATE UNIQUE INDEX review_user_book_unique ON review (user_id, book_id);
COMMENT ON COLUMN review.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN review.updated_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- MODUŁ NABYWANIA (ACQUISITION)
-- ============================================

-- Tabela: supplier - Dostawcy
CREATE TABLE supplier (
    id INT NOT NULL,
    name VARCHAR(180) NOT NULL,
    contact_email VARCHAR(180) DEFAULT NULL,
    contact_phone VARCHAR(60) DEFAULT NULL,
    address_line VARCHAR(255) DEFAULT NULL,
    city VARCHAR(120) DEFAULT NULL,
    country VARCHAR(120) DEFAULT NULL,
    tax_identifier VARCHAR(60) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    active BOOLEAN DEFAULT true NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

COMMENT ON COLUMN supplier.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN supplier.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: acquisition_budget - Budżety zakupowe
CREATE TABLE acquisition_budget (
    id INT NOT NULL,
    name VARCHAR(160) NOT NULL,
    fiscal_year VARCHAR(9) NOT NULL,
    allocated_amount NUMERIC(12, 2) NOT NULL,
    spent_amount NUMERIC(12, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

COMMENT ON COLUMN acquisition_budget.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_budget.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: acquisition_order - Zamówienia zakupowe
CREATE TABLE acquisition_order (
    id INT NOT NULL,
    supplier_id INT NOT NULL,
    budget_id INT DEFAULT NULL,
    reference_number VARCHAR(120) DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    items JSON DEFAULT NULL,
    total_amount NUMERIC(12, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    ordered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    expected_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    received_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    cancelled_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_1E96EAF32ADD6D8C ON acquisition_order (supplier_id);
CREATE INDEX IDX_1E96EAF336ABA6B8 ON acquisition_order (budget_id);
COMMENT ON COLUMN acquisition_order.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_order.updated_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_order.ordered_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_order.expected_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_order.received_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN acquisition_order.cancelled_at IS '(DC2Type:datetime_immutable)';

-- Tabela: acquisition_expense - Wydatki zakupowe
CREATE TABLE acquisition_expense (
    id INT NOT NULL,
    budget_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    amount NUMERIC(12, 2) NOT NULL,
    currency VARCHAR(3) NOT NULL,
    description VARCHAR(255) NOT NULL,
    type VARCHAR(20) NOT NULL,
    posted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_82EA1DED36ABA6B8 ON acquisition_expense (budget_id);
CREATE INDEX IDX_82EA1DED8D9F6D38 ON acquisition_expense (order_id);
COMMENT ON COLUMN acquisition_expense.posted_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- MODUŁ ADMINISTRACYJNY
-- ============================================

-- Tabela: staff_role - Role pracowników
CREATE TABLE staff_role (
    id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    role_key VARCHAR(120) NOT NULL,
    modules JSON NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_B55FFCE55E237E06 ON staff_role (name);
CREATE UNIQUE INDEX UNIQ_B55FFCE53EF22FDB ON staff_role (role_key);
COMMENT ON COLUMN staff_role.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN staff_role.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: system_setting - Ustawienia systemowe
CREATE TABLE system_setting (
    id INT NOT NULL,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NOT NULL,
    value_type VARCHAR(16) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE UNIQUE INDEX UNIQ_7307C40B5FA1E697 ON system_setting (setting_key);
COMMENT ON COLUMN system_setting.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN system_setting.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: backup_record - Rekordy kopii zapasowych
CREATE TABLE backup_record (
    id INT NOT NULL,
    file_name VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    status VARCHAR(32) NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    initiated_by VARCHAR(120) DEFAULT NULL,
    PRIMARY KEY(id)
);

COMMENT ON COLUMN backup_record.created_at IS '(DC2Type:datetime_immutable)';

-- Tabela: integration_config - Konfiguracja integracji
CREATE TABLE integration_config (
    id INT NOT NULL,
    name VARCHAR(160) NOT NULL,
    provider VARCHAR(120) NOT NULL,
    enabled BOOLEAN NOT NULL,
    settings JSON NOT NULL,
    last_status VARCHAR(32) NOT NULL,
    last_tested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

COMMENT ON COLUMN integration_config.last_tested_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN integration_config.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN integration_config.updated_at IS '(DC2Type:datetime_immutable)';

-- Tabela: notification_log - Logi powiadomień
CREATE TABLE notification_log (
    id INT NOT NULL,
    user_id INT NOT NULL,
    type VARCHAR(64) NOT NULL,
    channel VARCHAR(32) NOT NULL,
    fingerprint VARCHAR(96) NOT NULL,
    payload JSON DEFAULT NULL,
    status VARCHAR(32) NOT NULL,
    error_message VARCHAR(255) DEFAULT NULL,
    sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_ED15DF2A76ED395 ON notification_log (user_id);
CREATE UNIQUE INDEX uq_notification_fingerprint_channel ON notification_log (fingerprint, channel);
COMMENT ON COLUMN notification_log.sent_at IS '(DC2Type:datetime_immutable)';

-- Tabela: weeding_record - Rekordy selekcji zbiorów
CREATE TABLE weeding_record (
    id INT NOT NULL,
    book_id INT NOT NULL,
    book_copy_id INT DEFAULT NULL,
    processed_by_id INT DEFAULT NULL,
    reason VARCHAR(255) NOT NULL,
    action VARCHAR(20) NOT NULL,
    condition_state VARCHAR(120) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    removed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_812F8CEF16A2B381 ON weeding_record (book_id);
CREATE INDEX IDX_812F8CEF3B550FE4 ON weeding_record (book_copy_id);
CREATE INDEX IDX_812F8CEF2FFD4FD3 ON weeding_record (processed_by_id);
COMMENT ON COLUMN weeding_record.removed_at IS '(DC2Type:datetime_immutable)';

-- Tabela: audit_logs - Logi audytowe
CREATE TABLE audit_logs (
    id INT NOT NULL,
    user_id INT DEFAULT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    action VARCHAR(20) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    old_values TEXT DEFAULT NULL,
    new_values TEXT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX idx_audit_entity ON audit_logs (entity_type, entity_id);
CREATE INDEX idx_audit_action ON audit_logs (action);
CREATE INDEX idx_audit_user ON audit_logs (user_id);
CREATE INDEX idx_audit_created ON audit_logs (created_at);
COMMENT ON COLUMN audit_logs.created_at IS '(DC2Type:datetime_immutable)';

-- ============================================
-- KLUCZE OBCE (FOREIGN KEYS)
-- ============================================

-- Acquisition
ALTER TABLE acquisition_expense ADD CONSTRAINT FK_82EA1DED36ABA6B8 FOREIGN KEY (budget_id) REFERENCES acquisition_budget (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE acquisition_expense ADD CONSTRAINT FK_82EA1DED8D9F6D38 FOREIGN KEY (order_id) REFERENCES acquisition_order (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE acquisition_order ADD CONSTRAINT FK_1E96EAF32ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE acquisition_order ADD CONSTRAINT FK_1E96EAF336ABA6B8 FOREIGN KEY (budget_id) REFERENCES acquisition_budget (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Audit
ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Book
ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE book_category ADD CONSTRAINT FK_1FB30F9816A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE book_category ADD CONSTRAINT FK_1FB30F9812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE book_copy ADD CONSTRAINT FK_5427F08A16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE book_digital_asset ADD CONSTRAINT FK_817C37D016A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- User Relations
ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED9A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE favorite ADD CONSTRAINT FK_68C58ED916A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Loan & Fine
ALTER TABLE fine ADD CONSTRAINT FK_BEA95492CE73868F FOREIGN KEY (loan_id) REFERENCES loan (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE loan ADD CONSTRAINT FK_C5D30D0316A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE loan ADD CONSTRAINT FK_C5D30D033B550FE4 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE loan ADD CONSTRAINT FK_C5D30D03A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Notification
ALTER TABLE notification_log ADD CONSTRAINT FK_ED15DF2A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Registration
ALTER TABLE registration_token ADD CONSTRAINT FK_D09D01D3A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Reservation
ALTER TABLE reservation ADD CONSTRAINT FK_42C8495516A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE reservation ADD CONSTRAINT FK_42C849553B550FE4 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Review
ALTER TABLE review ADD CONSTRAINT FK_794381C616A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE review ADD CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Weeding
ALTER TABLE weeding_record ADD CONSTRAINT FK_812F8CEF16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE weeding_record ADD CONSTRAINT FK_812F8CEF3B550FE4 FOREIGN KEY (book_copy_id) REFERENCES book_copy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;
ALTER TABLE weeding_record ADD CONSTRAINT FK_812F8CEF2FFD4FD3 FOREIGN KEY (processed_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE;

-- ============================================
-- KONIEC SCHEMATU
-- ============================================
