<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "Starting database seeding...\n";

// Disable foreign keys temporarily
$db->query("SET FOREIGN_KEY_CHECKS = 0");

// Truncate tables to make seed repeatable
$tables = ['users', 'restaurants', 'products', 'categories', 'orders', 'order_items', 'payments', 'notifications', 'buyer_addresses', 'cart', 'review', 'vouchers', 'order_tracking'];
foreach ($tables as $t) {
    $db->query("TRUNCATE TABLE $t");
    echo "Truncated table: $t\n";
}

// Enable foreign keys
$db->query("SET FOREIGN_KEY_CHECKS = 1");

// 1. Seed Users
$users = [
    [
        'name' => 'Admin NomNom',
        'email' => 'admin@nomnom.id',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'phone' => '081111111111',
        'role' => 'admin',
        'is_verified' => 1,
        'is_active' => 1
    ],
    [
        'name' => 'Seller NomNom',
        'email' => 'seller@nomnom.id',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'phone' => '082222222222',
        'role' => 'seller',
        'is_verified' => 1,
        'is_active' => 1
    ],
    [
        'name' => 'Buyer NomNom',
        'email' => 'buyer@nomnom.id',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'phone' => '083333333333',
        'role' => 'buyer',
        'is_verified' => 1,
        'is_active' => 1
    ],
    [
        'name' => 'Driver NomNom',
        'email' => 'driver@nomnom.id',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'phone' => '084444444444',
        'role' => 'driver',
        'is_verified' => 1,
        'is_active' => 1
    ]
];

foreach ($users as $u) {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_verified, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $u['name'], $u['email'], $u['password'], $u['phone'], $u['role'], $u['is_verified'], $u['is_active']);
    $stmt->execute();
    $stmt->close();
}
echo "Seeded users.\n";

// Get user IDs
$seller_id = $db->query("SELECT id FROM users WHERE email = 'seller@nomnom.id'")->fetch_assoc()['id'];
$buyer_id = $db->query("SELECT id FROM users WHERE email = 'buyer@nomnom.id'")->fetch_assoc()['id'];

// 2. Seed Categories
$categories = [
    ['name' => 'Makanan Berat', 'description' => 'Menu makanan utama porsi kenyang', 'icon' => '🍚'],
    ['name' => 'Minuman Segar', 'description' => 'Minuman dingin dan hangat pelepas dahaga', 'icon' => '🍹'],
    ['name' => 'Cemilan Gurih', 'description' => 'Makanan ringan untuk menemani harimu', 'icon' => '🍟'],
    ['name' => 'Pencuci Mulut', 'description' => 'Kue dan hidangan penutup yang manis', 'icon' => '🍰']
];

foreach ($categories as $c) {
    $stmt = $db->prepare("INSERT INTO categories (name, description, icon, is_active) VALUES (?, ?, ?, 1)");
    $stmt->bind_param("sss", $c['name'], $c['description'], $c['icon']);
    $stmt->execute();
    $stmt->close();
}
echo "Seeded categories.\n";

// Get category IDs
$cat_makanan = $db->query("SELECT id FROM categories WHERE name = 'Makanan Berat'")->fetch_assoc()['id'];
$cat_minuman = $db->query("SELECT id FROM categories WHERE name = 'Minuman Segar'")->fetch_assoc()['id'];
$cat_cemilan = $db->query("SELECT id FROM categories WHERE name = 'Cemilan Gurih'")->fetch_assoc()['id'];
$cat_dessert = $db->query("SELECT id FROM categories WHERE name = 'Pencuci Mulut'")->fetch_assoc()['id'];

// 3. Seed Restaurant
$stmt = $db->prepare("INSERT INTO restaurants (seller_id, name, description, address, latitude, longitude, logo, banner, open_time, close_time, min_order, status) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?)");
$resto_name = 'Warung Sederhana NomNom';
$resto_desc = 'Menyediakan aneka makanan khas nusantara yang lezat dan higienis.';
$resto_addr = 'Jl. Merdeka No. 10, Gambir, Jakarta Pusat';
$lat = -6.17539240;
$lon = 106.82715280;
$open = '08:00:00';
$close = '22:00:00';
$min_order = 15000.00;
$status = 'active';

$stmt->bind_param("isssddssds", $seller_id, $resto_name, $resto_desc, $resto_addr, $lat, $lon, $open, $close, $min_order, $status);
$stmt->execute();
$restaurant_id = $db->insert_id;
$stmt->close();
echo "Seeded restaurant.\n";

// 4. Seed Products
$products = [
    [
        'name' => 'Nasi Goreng Spesial Telur',
        'description' => 'Nasi goreng dengan bumbu khas warung dilengkapi telur ceplok dan kerupuk.',
        'price' => 22000.00,
        'stock' => 50,
        'category_id' => $cat_makanan,
        'is_featured' => 1
    ],
    [
        'name' => 'Ayam Bakar Taliwang',
        'description' => 'Ayam bakar bumbu Taliwang pedas manis meresap, disajikan dengan sambal.',
        'price' => 28000.00,
        'stock' => 30,
        'category_id' => $cat_makanan,
        'is_featured' => 1
    ],
    [
        'name' => 'Es Teh Manis Selasih',
        'description' => 'Es teh manis dingin segar dengan tambahan biji selasih berkhasiat.',
        'price' => 6000.00,
        'stock' => 100,
        'category_id' => $cat_minuman,
        'is_featured' => 0
    ],
    [
        'name' => 'Jus Alpukat Kocok',
        'description' => 'Jus alpukat segar premium ditambah topping susu cokelat kental manis.',
        'price' => 14000.00,
        'stock' => 25,
        'category_id' => $cat_minuman,
        'is_featured' => 1
    ],
    [
        'name' => 'Kentang Goreng Keju',
        'description' => 'Kentang goreng renyah ditaburi bumbu keju bubuk melimpah.',
        'price' => 15000.00,
        'stock' => 40,
        'category_id' => $cat_cemilan,
        'is_featured' => 0
    ],
    [
        'name' => 'Roti Bakar Cokelat Keju',
        'description' => 'Roti bakar empuk diisi cokelat lumer dan keju parut gurih.',
        'price' => 18000.00,
        'stock' => 35,
        'category_id' => $cat_cemilan,
        'is_featured' => 1
    ],
    [
        'name' => 'Puding Coklat Lava',
        'description' => 'Puding cokelat lembut dengan saus fla vanila lezat di dalamnya.',
        'price' => 12000.00,
        'stock' => 20,
        'category_id' => $cat_dessert,
        'is_featured' => 1
    ]
];

foreach ($products as $p) {
    $stmt = $db->prepare("INSERT INTO products (seller_id, restaurant_id, category_id, name, description, price, stock, is_available, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
    $stmt->bind_param("iiissdid", $seller_id, $restaurant_id, $p['category_id'], $p['name'], $p['description'], $p['price'], $p['stock'], $p['is_featured']);
    $stmt->execute();
    $stmt->close();
}
echo "Seeded products.\n";

// 5. Seed Buyer Addresses
$addresses = [
    [
        'label' => 'Rumah',
        'recipient_name' => 'Buyer NomNom',
        'phone' => '083333333333',
        'address' => 'Jl. Sudirman No. 21, Jakarta Selatan (Samping Gedung BNI)',
        'latitude' => -6.21154400,
        'longitude' => 106.82298000,
        'is_default' => 1
    ],
    [
        'label' => 'Kantor',
        'recipient_name' => 'Buyer NomNom (Office)',
        'phone' => '083333333333',
        'address' => 'Gedung Cyber 2, Lt. 17, Jl. HR Rasuna Said, Jakarta Selatan',
        'latitude' => -6.22562200,
        'longitude' => 106.83151200,
        'is_default' => 0
    ]
];

foreach ($addresses as $a) {
    $stmt = $db->prepare("INSERT INTO buyer_addresses (user_id, label, recipient_name, phone, address, latitude, longitude, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdi", $buyer_id, $a['label'], $a['recipient_name'], $a['phone'], $a['address'], $a['latitude'], $a['longitude'], $a['is_default']);
    $stmt->execute();
    $stmt->close();
}
echo "Seeded buyer addresses.\n";

// 6. Seed Vouchers
$vouchers = [
    [
        'code' => 'NOMNOMHEMAT',
        'discount_type' => 'percentage',
        'discount_value' => 15.00,
        'min_order' => 20000.00,
        'max_discount' => 10000.00,
        'usage_limit' => 100,
        'expired_at' => '2027-12-31 23:59:59'
    ],
    [
        'code' => 'MAKANGRATIS',
        'discount_type' => 'fixed',
        'discount_value' => 15000.00,
        'min_order' => 30000.00,
        'max_discount' => 15000.00,
        'usage_limit' => 50,
        'expired_at' => '2027-12-31 23:59:59'
    ]
];

foreach ($vouchers as $v) {
    $stmt = $db->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_order, max_discount, usage_limit, used_count, expired_at, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 1)");
    $stmt->bind_param("ssdddis", $v['code'], $v['discount_type'], $v['discount_value'], $v['min_order'], $v['max_discount'], $v['usage_limit'], $v['expired_at']);
    $stmt->execute();
    $stmt->close();
}
echo "Seeded vouchers.\n";

// 7. Seed Notifications
$stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, 'system', 0)");
$title = "Selamat Datang di NomNom! 🍜";
$msg = "Akun Anda berhasil dibuat. Selamat menikmati layanan pesan antar makanan terbaik!";
$stmt->bind_param("iss", $buyer_id, $title, $msg);
$stmt->execute();
$stmt->close();
echo "Seeded welcome notifications.\n";

echo "Database seeding completed successfully!\n";
