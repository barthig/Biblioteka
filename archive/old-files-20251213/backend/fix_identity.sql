-- Pobierz maksymalną wartość i ustaw sekwencję
SELECT setval('acquisition_budget_id_seq', COALESCE((SELECT MAX(id) FROM acquisition_budget), 1), false);
SELECT setval('acquisition_expense_id_seq', COALESCE((SELECT MAX(id) FROM acquisition_expense), 1), false);
SELECT setval('acquisition_order_id_seq', COALESCE((SELECT MAX(id) FROM acquisition_order), 1), false);
SELECT setval('book_digital_asset_id_seq', COALESCE((SELECT MAX(id) FROM book_digital_asset), 1), false);
SELECT setval('registration_token_id_seq', COALESCE((SELECT MAX(id) FROM registration_token), 1), false);
SELECT setval('supplier_id_seq', COALESCE((SELECT MAX(id) FROM supplier), 1), false);
SELECT setval('weeding_record_id_seq', COALESCE((SELECT MAX(id) FROM weeding_record), 1), false);

-- Zmień kolumny z IDENTITY na zwykłe z DEFAULT nextval()
ALTER TABLE acquisition_budget ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE acquisition_budget ALTER COLUMN id SET DEFAULT nextval('acquisition_budget_id_seq');
ALTER SEQUENCE acquisition_budget_id_seq OWNED BY acquisition_budget.id;

ALTER TABLE acquisition_expense ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE acquisition_expense ALTER COLUMN id SET DEFAULT nextval('acquisition_expense_id_seq');
ALTER SEQUENCE acquisition_expense_id_seq OWNED BY acquisition_expense.id;

ALTER TABLE acquisition_order ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE acquisition_order ALTER COLUMN id SET DEFAULT nextval('acquisition_order_id_seq');
ALTER SEQUENCE acquisition_order_id_seq OWNED BY acquisition_order.id;

ALTER TABLE book_digital_asset ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE book_digital_asset ALTER COLUMN id SET DEFAULT nextval('book_digital_asset_id_seq');
ALTER SEQUENCE book_digital_asset_id_seq OWNED BY book_digital_asset.id;

ALTER TABLE registration_token ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE registration_token ALTER COLUMN id SET DEFAULT nextval('registration_token_id_seq');
ALTER SEQUENCE registration_token_id_seq OWNED BY registration_token.id;

ALTER TABLE supplier ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE supplier ALTER COLUMN id SET DEFAULT nextval('supplier_id_seq');
ALTER SEQUENCE supplier_id_seq OWNED BY supplier.id;

ALTER TABLE weeding_record ALTER COLUMN id DROP IDENTITY IF EXISTS;
ALTER TABLE weeding_record ALTER COLUMN id SET DEFAULT nextval('weeding_record_id_seq');
ALTER SEQUENCE weeding_record_id_seq OWNED BY weeding_record.id;
