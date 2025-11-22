-- Kompletny schemat bazy "Biblioteka" (PostgreSQL)
-- Zakłada użycie domyślnego schematu public.

BEGIN;

CREATE TABLE author (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(255) NOT NULL
);

CREATE TABLE category (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    CONSTRAINT uq_category_name UNIQUE (name)
);

CREATE TABLE book (
    id                  SERIAL PRIMARY KEY,
    author_id           INT NOT NULL REFERENCES author(id) ON DELETE RESTRICT,
    title               VARCHAR(255) NOT NULL,
    isbn                VARCHAR(20),
    description         TEXT,
    publisher           VARCHAR(255),
    publication_year    SMALLINT,
    resource_type       VARCHAR(60),
    signature           VARCHAR(60),
    target_age_group    VARCHAR(30),
    copies              INT NOT NULL DEFAULT 0,
    total_copies        INT NOT NULL DEFAULT 0,
    storage_copies      INT NOT NULL DEFAULT 0,
    open_stack_copies   INT NOT NULL DEFAULT 0,
    created_at          TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE TABLE book_category (
    book_id     INT NOT NULL REFERENCES book(id) ON DELETE CASCADE,
    category_id INT NOT NULL REFERENCES category(id) ON DELETE CASCADE,
    PRIMARY KEY (book_id, category_id)
);

CREATE TABLE book_copy (
    id              SERIAL PRIMARY KEY,
    book_id         INT NOT NULL REFERENCES book(id) ON DELETE CASCADE,
    inventory_code  VARCHAR(60) NOT NULL,
    status          VARCHAR(20) NOT NULL,
    location        VARCHAR(120),
    access_type     VARCHAR(60),
    condition_state VARCHAR(120),
    created_at      TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_book_copy_inventory UNIQUE (inventory_code)
);

CREATE TABLE book_digital_asset (
    id          SERIAL PRIMARY KEY,
    book_id     INT NOT NULL REFERENCES book(id) ON DELETE CASCADE,
    filename    VARCHAR(255) NOT NULL,
    mime_type   VARCHAR(120) NOT NULL,
    size_bytes  BIGINT NOT NULL,
    created_at  TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

CREATE TABLE app_user (
    id                  SERIAL PRIMARY KEY,
    email               VARCHAR(180) NOT NULL UNIQUE,
    name                VARCHAR(255) NOT NULL,
    roles               JSONB NOT NULL DEFAULT '[]'::jsonb,
    password            VARCHAR(255) NOT NULL,
    phone_number        VARCHAR(30),
    address_line        VARCHAR(255),
    city                VARCHAR(120),
    postal_code         VARCHAR(12),
    blocked             BOOLEAN NOT NULL DEFAULT FALSE,
    blocked_reason      VARCHAR(255),
    verified            BOOLEAN NOT NULL DEFAULT FALSE,
    pending_approval    BOOLEAN NOT NULL DEFAULT FALSE,
    membership_group    VARCHAR(64) NOT NULL DEFAULT 'standard',
    loan_limit          INT NOT NULL DEFAULT 5,
    privacy_consent_at  TIMESTAMP WITHOUT TIME ZONE,
    created_at          TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    newsletter_subscribed BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE loan (
    id              SERIAL PRIMARY KEY,
    book_id         INT NOT NULL REFERENCES book(id) ON DELETE RESTRICT,
    book_copy_id    INT REFERENCES book_copy(id) ON DELETE SET NULL,
    user_id         INT NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    borrowed_at     TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    due_at          TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    returned_at     TIMESTAMP WITHOUT TIME ZONE,
    extensions_count INT NOT NULL DEFAULT 0,
    last_extended_at TIMESTAMP WITHOUT TIME ZONE
);

CREATE TABLE reservation (
    id              SERIAL PRIMARY KEY,
    book_id         INT NOT NULL REFERENCES book(id) ON DELETE CASCADE,
    book_copy_id    INT REFERENCES book_copy(id) ON DELETE SET NULL,
    user_id         INT NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    status          VARCHAR(20) NOT NULL,
    reserved_at     TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    expires_at      TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    fulfilled_at    TIMESTAMP WITHOUT TIME ZONE,
    cancelled_at    TIMESTAMP WITHOUT TIME ZONE
);

CREATE TABLE fine (
    id          SERIAL PRIMARY KEY,
    loan_id     INT NOT NULL REFERENCES loan(id) ON DELETE CASCADE,
    amount      NUMERIC(10,2) NOT NULL,
    currency    VARCHAR(3) NOT NULL,
    reason      VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    paid_at     TIMESTAMP WITHOUT TIME ZONE
);

CREATE TABLE notification_log (
    id              SERIAL PRIMARY KEY,
    user_id         INT REFERENCES app_user(id) ON DELETE CASCADE,
    loan_id         INT REFERENCES loan(id) ON DELETE SET NULL,
    reservation_id  INT REFERENCES reservation(id) ON DELETE SET NULL,
    type            VARCHAR(64) NOT NULL,
    fingerprint     VARCHAR(96) NOT NULL,
    channel         VARCHAR(32) NOT NULL,
    payload         JSONB,
    status          VARCHAR(32) NOT NULL DEFAULT 'SENT',
    error_message   VARCHAR(255),
    created_at      TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    sent_at         TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_notification_unique UNIQUE (fingerprint, channel)
);

CREATE TABLE registration_token (
    id          SERIAL PRIMARY KEY,
    user_id     INT NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    token       VARCHAR(96) NOT NULL UNIQUE,
    expires_at  TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    used_at     TIMESTAMP WITHOUT TIME ZONE,
    created_at  TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

COMMIT;
