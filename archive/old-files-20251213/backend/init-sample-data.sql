-- Sample data for all tables (30 rows each)
\echo 'Inserting sample data...'

-- app_user
INSERT INTO app_user (id, email, name, roles, password, phone_number, address_line, city, postal_code, blocked, verified, verified_at, pending_approval, membership_group, loan_limit, blocked_reason, created_at, updated_at, privacy_consent_at, newsletter_subscribed)
SELECT gs,
       'user' || gs || '@example.com',
       'User ' || gs,
       CASE WHEN gs <= 5 THEN '["ROLE_ADMIN"]'::json WHEN gs <= 10 THEN '["ROLE_LIBRARIAN"]'::json ELSE '["ROLE_USER"]'::json END,
    '$2y$10$FLAkHnDByn13OHGuSXVxFObsT5BmJNapSAuXyHpy0lbaQ.6ep1dG.',
       '555-000-' || lpad(gs::text, 2, '0'),
       'Street ' || gs,
       'City ' || gs,
       '00-0' || lpad(gs::text, 2, '0'),
       false,
       true,
       now() - (gs || ' days')::interval,
       false,
       'standard',
       5,
       NULL,
       now() - (gs || ' days')::interval,
       now() - (gs || ' days')::interval,
       now() - (gs || ' days')::interval,
       true
FROM generate_series(1,30) gs;
SELECT setval('app_user_id_seq', 30, true);

-- author
INSERT INTO author (id, name)
SELECT gs, 'Author ' || gs FROM generate_series(1,30) gs;
SELECT setval('author_id_seq', 30, true);

-- category
INSERT INTO category (id, name)
SELECT gs, 'Category ' || gs FROM generate_series(1,30) gs;
SELECT setval('category_id_seq', 30, true);

-- supplier
INSERT INTO supplier (id, name, contact_email, active, created_at, updated_at)
SELECT gs, 'Supplier ' || gs, 'supplier' || gs || '@example.com', true, now(), now()
FROM generate_series(1,30) gs;
SELECT setval('supplier_id_seq', 30, true);

-- acquisition_budget
INSERT INTO acquisition_budget (id, name, fiscal_year, allocated_amount, spent_amount, currency, created_at, updated_at)
SELECT gs, 'Budget ' || gs, '202' || (gs % 10), 10000 + gs * 100, gs * 50, 'PLN', now(), now()
FROM generate_series(1,30) gs;
SELECT setval('acquisition_budget_id_seq', 30, true);

-- acquisition_order
INSERT INTO acquisition_order (id, supplier_id, budget_id, reference_number, title, description, items, total_amount, currency, status, created_at, updated_at, ordered_at, expected_at, received_at, cancelled_at)
SELECT gs, gs, gs, 'REF-' || gs, 'Order ' || gs, 'Auto generated order', '{}'::json, 100 + gs, 'PLN', 'created', now(), now(), now(), now() + interval '7 days', NULL, NULL
FROM generate_series(1,30) gs;
SELECT setval('acquisition_order_id_seq', 30, true);

-- acquisition_expense
INSERT INTO acquisition_expense (id, budget_id, order_id, amount, currency, description, type, posted_at)
SELECT gs, gs, gs, 50 + gs, 'PLN', 'Expense ' || gs, 'book', now()
FROM generate_series(1,30) gs;
SELECT setval('acquisition_expense_id_seq', 30, true);

-- book
INSERT INTO book (id, author_id, title, isbn, copies, total_copies, storage_copies, open_stack_copies, description, publisher, publication_year, resource_type, signature, target_age_group, created_at)
SELECT gs,
       ((gs - 1) % 30) + 1,
       'Book Title ' || gs,
       'ISBN' || lpad(gs::text, 10, '0'),
       3,
       3,
       1,
       2,
       'Sample description ' || gs,
       'Publisher ' || ((gs - 1) % 10 + 1),
       2000 + (gs % 20),
       'print',
       'SIG' || gs,
       'adult',
       now()
FROM generate_series(1,30) gs;
SELECT setval('book_id_seq', 30, true);

-- book_category (each book to one category)
INSERT INTO book_category (book_id, category_id)
SELECT gs, ((gs - 1) % 30) + 1 FROM generate_series(1,30) gs;

-- book_copy
INSERT INTO book_copy (id, book_id, inventory_code, status, location, access_type, condition_state, created_at, updated_at)
SELECT gs,
       gs,
       'INV-' || lpad(gs::text, 5, '0'),
       'available',
       'Main Branch',
       'open',
       'good',
       now(),
       now()
FROM generate_series(1,30) gs;
SELECT setval('book_copy_id_seq', 30, true);

-- book_digital_asset
INSERT INTO book_digital_asset (id, book_id, label, original_filename, mime_type, size, storage_name, created_at)
SELECT gs, gs, 'PDF ' || gs, 'file' || gs || '.pdf', 'application/pdf', 1024 * gs, 'storage-' || gs || '.pdf', now()
FROM generate_series(1,30) gs;
SELECT setval('book_digital_asset_id_seq', 30, true);

-- announcement
INSERT INTO announcement (id, created_by_id, title, content, type, status, is_pinned, show_on_homepage, created_at, updated_at, published_at, expires_at, target_audience)
SELECT gs, 1, 'Announcement ' || gs, 'Content for announcement ' || gs, 'info', 'published', (gs % 5 = 0), true, now() - (gs || ' hours')::interval, now() - (gs || ' hours')::interval, now() - (gs || ' hours')::interval, now() + interval '30 days', '["all"]'::json
FROM generate_series(1,30) gs;
SELECT setval('announcement_id_seq', 30, true);

-- favorite
INSERT INTO favorite (id, user_id, book_id, created_at)
SELECT gs, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, now() FROM generate_series(1,30) gs;
SELECT setval('favorite_id_seq', 30, true);

-- review (user/book pair unique)
INSERT INTO review (id, book_id, user_id, rating, comment, created_at, updated_at)
SELECT gs, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, ((gs - 1) % 5) + 1, 'Review ' || gs, now(), now()
FROM generate_series(1,30) gs;
SELECT setval('review_id_seq', 30, true);

-- notification_log
INSERT INTO notification_log (id, user_id, type, channel, fingerprint, payload, status, error_message, sent_at)
SELECT gs, ((gs - 1) % 30) + 1, 'reminder', 'email', 'fp-' || gs, '{"msg":"hello"}'::json, 'sent', NULL, now()
FROM generate_series(1,30) gs;
SELECT setval('notification_log_id_seq', 30, true);

-- loan
INSERT INTO loan (id, book_id, book_copy_id, user_id, borrowed_at, due_at, returned_at, extensions_count, last_extended_at)
SELECT gs, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, now() - interval '10 days', now() + interval '20 days', NULL, 0, NULL
FROM generate_series(1,30) gs;
SELECT setval('loan_id_seq', 30, true);

-- fine
INSERT INTO fine (id, loan_id, amount, currency, reason, created_at, paid_at)
SELECT gs, gs, 5 + gs, 'PLN', 'Late return', now(), NULL
FROM generate_series(1,30) gs;
SELECT setval('fine_id_seq', 30, true);

-- reservation
INSERT INTO reservation (id, book_id, book_copy_id, user_id, status, reserved_at, expires_at, fulfilled_at, cancelled_at)
SELECT gs, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, 'active', now(), now() + interval '7 days', NULL, NULL
FROM generate_series(1,30) gs;
SELECT setval('reservation_id_seq', 30, true);

-- registration_token
INSERT INTO registration_token (id, user_id, token, created_at, expires_at, used_at)
SELECT gs, gs, 'regtoken-' || gs, now(), now() + interval '30 days', NULL
FROM generate_series(1,30) gs;
SELECT setval('registration_token_id_seq', 30, true);

-- refresh_token
INSERT INTO refresh_token (id, user_id, token, expires_at, created_at, ip_address, user_agent, is_revoked, revoked_at)
SELECT gs, gs, 'refreshtoken-' || gs, now() + interval '30 days', now(), '127.0.0.1', 'init', false, NULL
FROM generate_series(1,30) gs;
SELECT setval('refresh_token_id_seq', 30, true);

-- staff_role
INSERT INTO staff_role (id, name, role_key, modules, description, created_at, updated_at)
SELECT gs, 'Staff Role ' || gs, 'ROLE_STAFF_' || gs, '["catalog"]'::json, 'Auto role ' || gs, now(), now()
FROM generate_series(1,30) gs;
SELECT setval('staff_role_id_seq', 30, true);

-- integration_config
INSERT INTO integration_config (id, name, provider, enabled, settings, last_status, last_tested_at, created_at, updated_at)
SELECT gs, 'Integration ' || gs, 'provider' || gs, true, '{"apiKey":"key' || gs || '"}'::json, 'ok', now(), now(), now()
FROM generate_series(1,30) gs;
SELECT setval('integration_config_id_seq', 30, true);

-- system_setting
INSERT INTO system_setting (id, setting_key, setting_value, value_type, description, created_at, updated_at)
SELECT gs, 'setting_' || gs, 'value_' || gs, 'string', 'Setting ' || gs, now(), now()
FROM generate_series(1,30) gs;
SELECT setval('system_setting_id_seq', 30, true);

-- backup_record
INSERT INTO backup_record (id, file_name, file_path, file_size, status, created_at, initiated_by)
SELECT gs, 'backup' || gs || '.sql', '/backups/backup' || gs || '.sql', 1000 + gs, 'done', now(), 'system'
FROM generate_series(1,30) gs;
SELECT setval('backup_record_id_seq', 30, true);

-- notification-log already inserted above

-- audit_logs
INSERT INTO audit_logs (id, user_id, entity_type, entity_id, action, ip_address, old_values, new_values, description, created_at)
SELECT gs, ((gs - 1) % 30) + 1, 'book', ((gs - 1) % 30) + 1, 'update', '127.0.0.1', NULL, NULL, 'Audit ' || gs, now()
FROM generate_series(1,30) gs;
SELECT setval('audit_logs_id_seq', 30, true);

-- notification_log already handled

-- weeding_record
INSERT INTO weeding_record (id, book_id, book_copy_id, processed_by_id, reason, action, condition_state, notes, removed_at)
SELECT gs, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, ((gs - 1) % 30) + 1, 'Outdated', 'discard', 'worn', 'Auto weeding', now()
FROM generate_series(1,30) gs;
SELECT setval('weeding_record_id_seq', 30, true);

-- acquisition indexes covered; no extra data needed beyond above

\echo 'Sample data inserted.'
