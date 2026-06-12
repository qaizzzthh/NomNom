<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

echo "Starting database seeding...\n";

// PostgreSQL: disable triggers, truncate, re-enable
$tables = ['order_tracking', 'review', 'cart', 'buyer_addresses', 'notifications', 'payments', 'order_items', 'orders', 'vouchers', 'products', 'categories', 'restaurants', 'users'];
foreach ($tables as $t) {
    $db->exec("TRUNCATE TABLE $t CASCADE");
    echo "Truncated table: $t\n";
}

// 1. Seed Users
$users = [
    [
        'name'        => 'Admin NomNom',
        'email'       => 'admin@nomnom.id',
        'password'    => password_hash('password', PASSWORD_BCRYPT),
        'phone'       => '081111111111',
        'role'        => 'admin',
        'is_verified' => true,
        'is_active'   => true
    ],
    [
        'name'        => 'Seller NomNom',
        'email'       => 'seller@nomnom.id',
        'password'    => password_hash('password', PASSWORD_BCRYPT),
        'phone'       => '082222222222',
        'role'        => 'seller',
        'is_verified' => true,
        'is_active'   => true
    ],
    [
        'name'        => 'Buyer NomNom',
        'email'       => 'buyer@nomnom.id',
        'password'    => password_hash('password', PASSWORD_BCRYPT),
        'phone'       => '083333333333',
        'role'        => 'buyer',
        'is_verified' => true,
        'is_active'   => true
    ],
    [
        'name'        => 'Driver NomNom',
        'email'       => 'driver@nomnom.id',
        'password'    => password_hash('password', PASSWORD_BCRYPT),
        'phone'       => '084444444444',
        'role'        => 'driver',
        'is_verified' => true,
        'is_active'   => true
    ]
];

foreach ($users as $u) {
    $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, role, is_verified, is_active) VALUES (:name, :email, :password, :phone, :role, :is_verified, :is_active)");
    $stmt->bindValue(':name', $u['name']);
    $stmt->bindValue(':email', $u['email']);
    $stmt->bindValue(':password', $u['password']);
    $stmt->bindValue(':phone', $u['phone']);
    $stmt->bindValue(':role', $u['role']);
    $stmt->bindValue(':is_verified', $u['is_verified'], PDO::PARAM_BOOL);
    $stmt->bindValue(':is_active', $u['is_active'], PDO::PARAM_BOOL);
    $stmt->execute();
}
echo "Seeded users.\n";

// Get user IDs
$sq = $db->query("SELECT id FROM users WHERE email = 'seller@nomnom.id'");
$seller_id = $sq->fetch(PDO::FETCH_ASSOC)['id'];

$bq = $db->query("SELECT id FROM users WHERE email = 'buyer@nomnom.id'");
$buyer_id = $bq->fetch(PDO::FETCH_ASSOC)['id'];

// 2. Seed Categories
$categories = [
    ['name' => 'Makanan Berat',  'description' => 'Menu makanan utama porsi kenyang',         'icon' => '🍚'],
    ['name' => 'Minuman Segar',  'description' => 'Minuman dingin dan hangat pelepas dahaga',  'icon' => '🍹'],
    ['name' => 'Cemilan Gurih',  'description' => 'Makanan ringan untuk menemani harimu',      'icon' => '🍟'],
    ['name' => 'Pencuci Mulut',  'description' => 'Kue dan hidangan penutup yang manis',       'icon' => '🍰']
];

foreach ($categories as $c) {
    $stmt = $db->prepare("INSERT INTO categories (name, description, icon, is_active) VALUES (?, ?, ?, true)");
    $stmt->execute([$c['name'], $c['description'], $c['icon']]);
}
echo "Seeded categories.\n";

// Get category IDs
$q1 = $db->query("SELECT id FROM categories WHERE name = 'Makanan Berat'");  $cat_makanan = $q1->fetch(PDO::FETCH_ASSOC)['id'];
$q2 = $db->query("SELECT id FROM categories WHERE name = 'Minuman Segar'");  $cat_minuman = $q2->fetch(PDO::FETCH_ASSOC)['id'];
$q3 = $db->query("SELECT id FROM categories WHERE name = 'Cemilan Gurih'");  $cat_cemilan = $q3->fetch(PDO::FETCH_ASSOC)['id'];
$q4 = $db->query("SELECT id FROM categories WHERE name = 'Pencuci Mulut'");  $cat_dessert = $q4->fetch(PDO::FETCH_ASSOC)['id'];

// 3. Seed Restaurant
$stmt = $db->prepare("INSERT INTO restaurants (seller_id, name, description, address, latitude, longitude, logo, banner, open_time, close_time, min_order, status) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?) RETURNING id");
$stmt->execute([
    $seller_id,
    'Warung Sederhana NomNom',
    'Menyediakan aneka makanan khas nusantara yang lezat dan higienis.',
    'Jl. Merdeka No. 10, Gambir, Jakarta Pusat',
    -6.17539240,
    106.82715280,
    '08:00:00',
    '22:00:00',
    15000.00,
    'active'
]);
$restaurant_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
echo "Seeded restaurant.\n";

// 4. Seed Products
$products = [
    ['name' => 'Nasi Goreng Spesial Telur',   'description' => 'Nasi goreng dengan bumbu khas warung dilengkapi telur ceplok dan kerupuk.',    'price' => 22000.00, 'stock' => 50, 'category_id' => $cat_makanan, 'is_featured' => true],
    ['name' => 'Ayam Bakar Taliwang',          'description' => 'Ayam bakar bumbu Taliwang pedas manis meresap, disajikan dengan sambal.',       'price' => 28000.00, 'stock' => 30, 'category_id' => $cat_makanan, 'is_featured' => true],
    ['name' => 'Es Teh Manis Selasih',         'description' => 'Es teh manis dingin segar dengan tambahan biji selasih berkhasiat.',            'price' =>  6000.00, 'stock' => 100, 'category_id' => $cat_minuman, 'is_featured' => false],
    ['name' => 'Jus Alpukat Kocok',            'description' => 'Jus alpukat segar premium ditambah topping susu cokelat kental manis.',         'price' => 14000.00, 'stock' => 25,  'category_id' => $cat_minuman, 'is_featured' => true],
    ['name' => 'Kentang Goreng Keju',          'description' => 'Kentang goreng renyah ditaburi bumbu keju bubuk melimpah.',                     'price' => 15000.00, 'stock' => 40,  'category_id' => $cat_cemilan, 'is_featured' => false],
    ['name' => 'Roti Bakar Cokelat Keju',      'description' => 'Roti bakar empuk diisi cokelat lumer dan keju parut gurih.',                   'price' => 18000.00, 'stock' => 35,  'category_id' => $cat_cemilan, 'is_featured' => true],
    ['name' => 'Puding Coklat Lava',           'description' => 'Puding cokelat lembut dengan saus fla vanila lezat di dalamnya.',              'price' => 12000.00, 'stock' => 20,  'category_id' => $cat_dessert, 'is_featured' => true],
];

foreach ($products as $p) {
    $stmt = $db->prepare("INSERT INTO products (seller_id, restaurant_id, category_id, name, description, price, stock, is_available, is_featured) VALUES (:seller_id, :restaurant_id, :category_id, :name, :description, :price, :stock, true, :is_featured)");
    $stmt->bindValue(':seller_id', $seller_id, PDO::PARAM_INT);
    $stmt->bindValue(':restaurant_id', $restaurant_id, PDO::PARAM_INT);
    $stmt->bindValue(':category_id', $p['category_id'], PDO::PARAM_INT);
    $stmt->bindValue(':name', $p['name']);
    $stmt->bindValue(':description', $p['description']);
    $stmt->bindValue(':price', $p['price']);
    $stmt->bindValue(':stock', $p['stock'], PDO::PARAM_INT);
    $stmt->bindValue(':is_featured', $p['is_featured'], PDO::PARAM_BOOL);
    $stmt->execute();
}
echo "Seeded products.\n";

// 5. Seed Buyer Addresses
$addresses = [
    [
        'label'          => 'Rumah',
        'recipient_name' => 'Buyer NomNom',
        'phone'          => '083333333333',
        'address'        => 'Jl. Sudirman No. 21, Jakarta Selatan (Samping Gedung BNI)',
        'latitude'       => -6.21154400,
        'longitude'      => 106.82298000,
        'is_default'     => true
    ],
    [
        'label'          => 'Kantor',
        'recipient_name' => 'Buyer NomNom (Office)',
        'phone'          => '083333333333',
        'address'        => 'Gedung Cyber 2, Lt. 17, Jl. HR Rasuna Said, Jakarta Selatan',
        'latitude'       => -6.22562200,
        'longitude'      => 106.83151200,
        'is_default'     => false
    ]
];

foreach ($addresses as $a) {
    $stmt = $db->prepare("INSERT INTO buyer_addresses (user_id, label, recipient_name, phone, address, latitude, longitude, is_default) VALUES (:user_id, :label, :recipient_name, :phone, :address, :latitude, :longitude, :is_default)");
    $stmt->bindValue(':user_id', $buyer_id, PDO::PARAM_INT);
    $stmt->bindValue(':label', $a['label']);
    $stmt->bindValue(':recipient_name', $a['recipient_name']);
    $stmt->bindValue(':phone', $a['phone']);
    $stmt->bindValue(':address', $a['address']);
    $stmt->bindValue(':latitude', $a['latitude']);
    $stmt->bindValue(':longitude', $a['longitude']);
    $stmt->bindValue(':is_default', $a['is_default'], PDO::PARAM_BOOL);
    $stmt->execute();
}
echo "Seeded buyer addresses.\n";

// 6. Seed Vouchers
$vouchers = [
    [
        'code'           => 'NOMNOMHEMAT',
        'discount_type'  => 'percentage',
        'discount_value' => 15.00,
        'min_order'      => 20000.00,
        'max_discount'   => 10000.00,
        'usage_limit'    => 100,
        'expired_at'     => '2027-12-31 23:59:59'
    ],
    [
        'code'           => 'MAKANGRATIS',
        'discount_type'  => 'fixed',
        'discount_value' => 15000.00,
        'min_order'      => 30000.00,
        'max_discount'   => 15000.00,
        'usage_limit'    => 50,
        'expired_at'     => '2027-12-31 23:59:59'
    ]
];

foreach ($vouchers as $v) {
    $stmt = $db->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_order, max_discount, usage_limit, used_count, expired_at, is_active) VALUES (?, ?, ?, ?, ?, ?, 0, ?, true)");
    $stmt->execute([$v['code'], $v['discount_type'], $v['discount_value'], $v['min_order'], $v['max_discount'], $v['usage_limit'], $v['expired_at']]);
}
echo "Seeded vouchers.\n";

// 7. Seed Welcome Notification
$notif_title = "Selamat Datang di NomNom! 🍜";
$notif_msg   = "Akun Anda berhasil dibuat. Selamat menikmati layanan pesan antar makanan terbaik!";
$sn = $db->prepare("INSERT INTO notifications (user_id, title, message, type, is_read) VALUES (?, ?, ?, 'system', false)");
$sn->execute([$buyer_id, $notif_title, $notif_msg]);
echo "Seeded welcome notifications.\n";

echo "Database seeding completed successfully!\n";
