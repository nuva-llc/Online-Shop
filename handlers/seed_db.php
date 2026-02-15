<?php
/**
 * Weapons Store - Tactical Database Seeder
 * Programmatically ensures default users and elite products are present.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "--- Starting Tactical Data Seeding ---\n";

// 1. Seed Permanent Users
$defaultUsers = [
    [
        'username' => 'admin',
        'name' => 'Command Officer',
        'email' => 'admin@gmail.com',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'user_type' => 'admin',
        'balance' => 999999
    ],
    [
        'username' => 'user',
        'name' => 'Field Operator',
        'email' => 'user@nuva.com',
        'password' => password_hash('user123', PASSWORD_DEFAULT),
        'user_type' => 'user',
        'balance' => 5000
    ]
];

foreach ($defaultUsers as $u) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$u['username'], $u['email']]);
    if ($stmt->rowCount() == 0) {
        $ins = $pdo->prepare("INSERT INTO users (username, name, email, password, user_type, balance, activation) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $ins->execute([$u['username'], $u['name'], $u['email'], $u['password'], $u['user_type'], $u['balance']]);
        echo "Successfully enlisted: {$u['username']} ({$u['user_type']})\n";
    } else {
        echo "Operator {$u['username']} is already stationed.\n";
    }
}

// 2. Seed Tactical Products
$tacticalGear = [
    ['Glock', 'Glock 19 Gen5', 'Austria', 50, 550.00, '697100061ec1f_1769013254.jpg', 'Compact 9mm professional-grade sidearm. Extreme reliability.', 'Pistols'],
    ['Kalashnikov', 'AK-47 Classic', 'Russia', 20, 1200.00, '69710014844d3_1769013268.jpg', 'The legendary assault rifle. Chambers 7.62x39mm rounds.', 'Rifles'],
    ['Beretta', 'Beretta 92FS', 'Italy', 30, 650.00, '69710020b1888_1769013280.jpg', 'Standard issue tactical pistol. Exceptional precision.', 'Pistols'],
    ['Remington', 'Remington 870', 'USA', 15, 480.00, '6971002f4d5c6_1769013295.jpg', 'The gold standard of pump-action shotguns. Ideal for breaching.', 'Shotguns'],
    ['Colt', 'Colt M4 Carbine', 'USA', 10, 1650.00, '6971003aa6882_1769013306.jpg', 'Highly modular for multi-role tactical insertions.', 'Rifles'],
    ['Kalashnikov', 'AK-103', 'Russia', 53, 1350.00, '697101921eae7_1769013650.jpg', 'Modernized AK with 7.62mm stopping power.', 'Rifles'],
    ['Makarov', 'Makarov PM', 'Russia', 45, 420.00, '697108e82e62a_1769015528.jpg', 'Soviet-era classic sidearm. Rugged and compact.', 'Pistols']
];

foreach ($tacticalGear as $gear) {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ?");
    $stmt->execute([$gear[1]]);
    if ($stmt->rowCount() == 0) {
        $ins = $pdo->prepare("INSERT INTO products (brand, name, manufacturing_country, quantity, price, image_1, description, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute($gear);
        echo "Gears deployed: {$gear[1]}\n";
    } else {
        echo "Gear {$gear[1]} already in inventory.\n";
    }
}

echo "--- Seeding Mission Accomplished ---\n";
