CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS customers (
    id VARCHAR(30) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    address TEXT,
    tier VARCHAR(30) NOT NULL DEFAULT 'standard',
    total_spent DECIMAL(12, 2) NOT NULL DEFAULT 0,
    joined_at DATE DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_customers_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS collections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    description TEXT,
    banner VARCHAR(500) DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_collections_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(30) NOT NULL,
    name VARCHAR(191) NOT NULL,
    sku VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    collection_id BIGINT UNSIGNED DEFAULT NULL,
    price DECIMAL(12, 2) NOT NULL,
    original_price DECIMAL(12, 2) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    sizes JSON DEFAULT NULL,
    colors JSON DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    fallback_image TEXT,
    description TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    sales_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATE DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_category (category),
    KEY idx_products_status (status),
    CONSTRAINT fk_products_collection
        FOREIGN KEY (collection_id) REFERENCES collections(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id VARCHAR(30) NOT NULL,
    customer_id VARCHAR(30) DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    customer_email VARCHAR(191) DEFAULT NULL,
    shipping_address TEXT,
    subtotal DECIMAL(12, 2) NOT NULL DEFAULT 0,
    shipping_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
    discount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    coupon_code VARCHAR(100) DEFAULT NULL,
    total DECIMAL(12, 2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(100) DEFAULT NULL,
    payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    order_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_status (order_status),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id VARCHAR(30) NOT NULL,
    product_id VARCHAR(30) DEFAULT NULL,
    product_name VARCHAR(191) NOT NULL,
    size VARCHAR(50) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    price DECIMAL(12, 2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    image VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS coupons (
    id VARCHAR(30) NOT NULL,
    code VARCHAR(100) NOT NULL,
    description TEXT,
    type VARCHAR(20) NOT NULL DEFAULT 'fixed',
    value DECIMAL(12, 2) NOT NULL,
    min_order DECIMAL(12, 2) NOT NULL DEFAULT 0,
    usage_limit INT UNSIGNED DEFAULT NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    start_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_coupons_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value JSON DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED DEFAULT NULL,
    session_key VARCHAR(191) DEFAULT NULL,
    product_id VARCHAR(30) NOT NULL,
    size VARCHAR(50) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cart_items_user (user_id),
    KEY idx_cart_items_session (session_key),
    CONSTRAINT fk_cart_items_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_cart_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
