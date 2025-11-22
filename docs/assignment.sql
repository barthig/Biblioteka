-- Schema definition
CREATE TABLE customer (
    customer_id SERIAL PRIMARY KEY,
    email TEXT NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE product (
    product_id SERIAL PRIMARY KEY,
    name TEXT NOT NULL,
    sku TEXT NOT NULL UNIQUE,
    unit_price NUMERIC(10,2) NOT NULL CHECK (unit_price >= 0)
);

CREATE TABLE "order" (
    order_id SERIAL PRIMARY KEY,
    customer_id INT NOT NULL REFERENCES customer(customer_id) ON DELETE CASCADE,
    order_number TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'pending',
    placed_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE order_item (
    order_item_id SERIAL PRIMARY KEY,
    order_id INT NOT NULL REFERENCES "order"(order_id) ON DELETE CASCADE,
    product_id INT NOT NULL REFERENCES product(product_id),
    quantity INT NOT NULL CHECK (quantity > 0),
    unit_price NUMERIC(10,2) NOT NULL CHECK (unit_price >= 0),
    UNIQUE (order_id, product_id)
);

-- Sample data
INSERT INTO customer (email, full_name) VALUES
('alice@example.com', 'Alicja Kowalska'),
('bob@example.com', 'Robert Nowak'),
('carol@example.com', 'Karolina Wiśniewska');

INSERT INTO product (name, sku, unit_price) VALUES
('Czytnik e-booków', 'PRD-EBK-001', 399.00),
('Lampka biurkowa', 'PRD-LMP-010', 129.90),
('Notes A5', 'PRD-NTS-205', 19.50);

INSERT INTO "order" (customer_id, order_number, status) VALUES
(1, 'ORD-2025-0001', 'paid'),
(1, 'ORD-2025-0005', 'shipped'),
(2, 'ORD-2025-0010', 'pending');

INSERT INTO order_item (order_id, product_id, quantity, unit_price) VALUES
(1, 1, 1, 399.00),
(1, 3, 2, 19.50),
(2, 2, 1, 129.90),
(2, 3, 3, 18.00),
(3, 1, 1, 389.00);

-- Example queries
SELECT customer_id, full_name, email, created_at
FROM customer
ORDER BY created_at;

SELECT o.order_number, o.status, o.placed_at
FROM "order" o
JOIN customer c ON c.customer_id = o.customer_id
WHERE c.email = 'alice@example.com'
ORDER BY o.placed_at DESC;

SELECT c.full_name,
       COUNT(DISTINCT o.order_id) AS orders_count,
       COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_spent
FROM customer c
LEFT JOIN "order" o ON o.customer_id = c.customer_id
LEFT JOIN order_item oi ON oi.order_id = o.order_id
GROUP BY c.customer_id, c.full_name
ORDER BY total_spent DESC;
