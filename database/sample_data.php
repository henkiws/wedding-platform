<?php
require_once '../config.php';

// This script should only be run once to populate the database with sample data
// Remove or secure this file in production

$db = new Database();

try {
    // Insert demo user
    $demo_password = password_hash('demo123', PASSWORD_DEFAULT);
    
    $db->query(
        "INSERT IGNORE INTO users (username, email, password, full_name, phone, subscription_plan, subscription_expires) 
        VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 365 DAY))",
        ['demo', 'demo@example.com', $demo_password, 'Demo User', '081234567890', 'premium']
    );
    
    $demo_user_id = $db->fetch("SELECT id FROM users WHERE username = 'demo'")['id'];
    
    // Insert sample invitation
    $db->query(
        "INSERT IGNORE INTO invitations (
            user_id, title, groom_name, bride_name, wedding_date, wedding_time,
            venue_name, venue_address, venue_maps_link, theme_id, story,
            live_streaming_link, slug, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $demo_user_id,
            'Wedding Invitation - John & Jane',
            'John Doe',
            'Jane Smith',
            '2024-12-25',
            '10:00:00',
            'Grand Ballroom Hotel Mulia',
            'Jl. Asia Afrika No. 8, Gelora, Tanah Abang, Jakarta Pusat, DKI Jakarta 10270',
            'https://maps.google.com/?q=Hotel+Mulia+Jakarta',
            1, // Classic theme
            'Kami pertama kali bertemu di kampus pada tahun 2018. Awalnya hanya teman biasa, tapi seiring berjalannya waktu, kami menyadari bahwa kami saling melengkapi. Setelah 5 tahun bersama, kami memutuskan untuk melanjutkan hubungan ini ke jenjang yang lebih serius. Kami sangat bersyukur bisa menemukan cinta sejati dan ingin berbagi kebahagiaan ini bersama keluarga dan sahabat-sahabat tercinta.',
            'https://youtube.com/watch?v=demostream',
            'john-jane-demo',
            1
        ]
    );
    
    $invitation_id = $db->fetch("SELECT id FROM invitations WHERE slug = 'john-jane-demo'")['id'];
    
    // Insert sample guests
    $sample_guests = [
        ['Ahmad Wijaya', '081234567890', 'ahmad@email.com', 'Jl. Sudirman No. 1, Jakarta', 'family'],
        ['Siti Nurhaliza', '081234567891', 'siti@email.com', 'Jl. Thamrin No. 2, Jakarta', 'friend'],
        ['Budi Santoso', '081234567892', 'budi@email.com', 'Jl. Gatot Subroto No. 3, Jakarta', 'colleague'],
        ['Rina Marlina', '081234567893', 'rina@email.com', 'Jl. Rasuna Said No. 4, Jakarta', 'friend'],
        ['Andi Pratama', '081234567894', 'andi@email.com', 'Jl. HR Rasuna Said No. 5, Jakarta', 'family']
    ];
    
    foreach ($sample_guests as $guest) {
        $db->query(
            "INSERT IGNORE INTO guests (invitation_id, name, phone, email, address, guest_type) 
            VALUES (?, ?, ?, ?, ?, ?)",
            array_merge([$invitation_id], $guest)
        );
    }
    
    // Insert sample RSVP responses
    $sample_rsvps = [
        ['Ahmad Wijaya', 'yes', 2, 'Selamat ya! Kami pasti datang.', '081234567890'],
        ['Siti Nurhaliza', 'yes', 1, 'Congratulations! Can\'t wait to celebrate with you both.', '081234567891'],
        ['Budi Santoso', 'no', 0, 'Maaf tidak bisa hadir karena ada acara keluarga. Selamat ya!', '081234567892'],
        ['Rina Marlina', 'maybe', 1, 'Masih coba atur jadwal. Semoga bisa hadir.', '081234567893']
    ];
    
    foreach ($sample_rsvps as $rsvp) {
        $db->query(
            "INSERT IGNORE INTO rsvp_responses (invitation_id, guest_name, attendance, guest_count, message, phone) 
            VALUES (?, ?, ?, ?, ?, ?)",
            array_merge([$invitation_id], $rsvp)
        );
    }
    
    // Insert sample guest messages
    $sample_messages = [
        ['Keluarga Besar Wijaya', 'Selamat menempuh hidup baru! Semoga menjadi keluarga yang sakinah, mawaddah, warahmah. Barakallahu lakuma wa baraka \'alaikuma wa jama\'a bainakuma fi khair.'],
        ['Teman-teman Kampus', 'John & Jane, congratulations on your wedding! Wishing you a lifetime filled with love, laughter, and happiness. Can\'t believe our college friends are getting married!'],
        ['Rekan Kerja Kantor', 'Selamat atas pernikahan kalian! Semoga menjadi pasangan yang saling mendukung dan mencapai kesuksesan bersama. Sukses selalu untuk kalian berdua!'],
        ['Tetangga Komplek', 'Selamat ya John dan Jane! Kalian pasangan yang sangat serasi. Semoga pernikahan kalian dipenuhi berkah dan kebahagiaan. Selamat bergabung menjadi keluarga baru!'],
        ['Sahabat Lama', 'From childhood friends to witnessing your wedding day - what a beautiful journey! May your marriage be filled with endless love, joy, and adventures together. Love you both!'],
        ['Keluarga Smith', 'Dear John and Jane, congratulations on your special day! We are so happy to see you both starting this new chapter together. Wishing you all the best in your married life!']
    ];
    
    foreach ($sample_messages as $message) {
        $db->query(
            "INSERT IGNORE INTO guest_messages (invitation_id, sender_name, message, is_approved) 
            VALUES (?, ?, ?, 1)",
            array_merge([$invitation_id], $message)
        );
    }
    
    // Insert sample digital gifts
    $sample_gifts = [
        ['bank', 'John Doe', '1234567890', null],
        ['e-wallet', 'Jane Smith', '081234567890', null],
        ['bank', 'John & Jane Wedding Fund', '0987654321', null]
    ];
    
    foreach ($sample_gifts as $gift) {
        $db->query(
            "INSERT IGNORE INTO digital_gifts (invitation_id, account_type, account_name, account_number, qr_code_image, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)",
            array_merge([$invitation_id], $gift)
        );
    }
    
    // Insert more sample themes
    $additional_themes = [
        ['Vintage Rose', '/assets/themes/vintage/preview.jpg', 'vintage.css', 1],
        ['Beach Wedding', '/assets/themes/beach/preview.jpg', 'beach.css', 1],
        ['Forest Green', '/assets/themes/forest/preview.jpg', 'forest.css', 0],
        ['Royal Purple', '/assets/themes/royal/preview.jpg', 'royal.css', 1],
        ['Sunset Orange', '/assets/themes/sunset/preview.jpg', 'sunset.css', 0]
    ];
    
    foreach ($additional_themes as $theme) {
        $db->query(
            "INSERT IGNORE INTO themes (name, preview_image, css_file, is_premium) 
            VALUES (?, ?, ?, ?)",
            $theme
        );
    }
    
    echo "Sample data inserted successfully!\n\n";
    echo "Demo Login Credentials:\n";
    echo "Username: demo\n";
    echo "Password: demo123\n\n";
    echo "Sample Invitation URL:\n";
    echo SITE_URL . "/invitation/john-jane-demo\n\n";
    echo "You can now test the platform with this sample data.\n";
    
} catch (Exception $e) {
    echo "Error inserting sample data: " . $e->getMessage() . "\n";
}
?>