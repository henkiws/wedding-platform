<?php
/**
 * Helper Functions for Wedding Invitation Platform
 * Contains utility functions used across the platform
 */

/**
 * Generate a unique invitation slug
 */
function generateInvitationSlug($groom_name, $bride_name, $db) {
    $base_slug = generateSlug($groom_name . '-' . $bride_name);
    $slug = $base_slug;
    $counter = 1;
    
    // Ensure slug is unique
    while ($db->fetch("SELECT id FROM invitations WHERE slug = ?", [$slug])) {
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
    
    return $slug;
}

/**
 * Generate QR Code for digital gifts
 */
function generateQRCode($data, $filename) {
    // Simple QR code generation using Google Charts API
    $qr_url = "https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=" . urlencode($data);
    
    $qr_content = file_get_contents($qr_url);
    if ($qr_content) {
        $qr_path = 'uploads/qrcodes/' . $filename . '.png';
        file_put_contents($qr_path, $qr_content);
        return $filename . '.png';
    }
    
    return false;
}

/**
 * Send WhatsApp invitation link
 */
function sendWhatsAppInvitation($phone, $invitation_url, $groom_name, $bride_name) {
    $message = "🌸 You're invited to our wedding! 🌸\n\n";
    $message .= "Dear friend,\n\n";
    $message .= "We would be honored to have you join us on our special day!\n\n";
    $message .= "👰🏻‍♀️ $bride_name & 🤵🏻‍♂️ $groom_name\n\n";
    $message .= "Please view our digital invitation for all the details:\n";
    $message .= $invitation_url . "\n\n";
    $message .= "Can't wait to celebrate with you! 💕";
    
    $whatsapp_url = "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text=" . urlencode($message);
    
    return $whatsapp_url;
}

/**
 * Calculate subscription expiry
 */
function calculateSubscriptionExpiry($plan) {
    switch ($plan) {
        case 'free':
            return date('Y-m-d H:i:s', strtotime('+' . FREE_DURATION_DAYS . ' days'));
        case 'premium':
        case 'business':
            return null; // Lifetime
        default:
            return date('Y-m-d H:i:s', strtotime('+30 days'));
    }
}

/**
 * Check if subscription is expired
 */
function isSubscriptionExpired($subscription_end) {
    if (!$subscription_end) {
        return false; // Lifetime subscription
    }
    
    return strtotime($subscription_end) < time();
}

/**
 * Get subscription features
 */
function getSubscriptionFeatures($plan) {
    $features = [
        'free' => [
            'invitations' => 1,
            'duration' => FREE_DURATION_DAYS . ' days',
            'guest_limit' => FREE_GUEST_LIMIT,
            'gallery_limit' => FREE_GALLERY_LIMIT,
            'themes' => 'Basic themes only',
            'support' => 'Community support',
            'analytics' => false,
            'custom_domain' => false,
            'remove_branding' => false
        ],
        'premium' => [
            'invitations' => 1,
            'duration' => 'Lifetime',
            'guest_limit' => 'Unlimited',
            'gallery_limit' => 'Unlimited',
            'themes' => 'All themes',
            'support' => 'Priority support',
            'analytics' => true,
            'custom_domain' => false,
            'remove_branding' => true
        ],
        'business' => [
            'invitations' => 2,
            'duration' => 'Lifetime',
            'guest_limit' => 'Unlimited',
            'gallery_limit' => 'Unlimited',
            'themes' => 'All themes',
            'support' => 'Priority support',
            'analytics' => true,
            'custom_domain' => true,
            'remove_branding' => true
        ]
    ];
    
    return $features[$plan] ?? $features['free'];
}

/**
 * Format Indonesian Rupiah currency
 */
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Generate invitation preview URL
 */
function getInvitationPreviewUrl($slug) {
    return SITE_URL . '/invitation/' . $slug;
}

/**
 * Get theme CSS file path
 */
function getThemeCssPath($theme_id, $db) {
    $theme = $db->fetch("SELECT css_file FROM themes WHERE id = ? AND is_active = 1", [$theme_id]);
    if ($theme && $theme['css_file']) {
        return THEME_DIR . $theme['css_file'];
    }
    return THEME_DIR . 'default.css';
}

/**
 * Resize and optimize image
 */
function resizeImage($source, $destination, $max_width = 800, $max_height = 600, $quality = 85) {
    $info = getimagesize($source);
    if (!$info) return false;
    
    $width = $info[0];
    $height = $info[1];
    $type = $info[2];
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
        imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $destination, intval($quality / 10));
            break;
        case IMAGETYPE_GIF:
            imagegif($new_image, $destination);
            break;
    }
    
    // Clean up
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return true;
}

/**
 * Generate random string for tokens
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Send email notification (wrapper for EmailService)
 */
function sendEmailNotification($type, $to, $data = []) {
    try {
        $emailService = new EmailService();
        
        switch ($type) {
            case 'welcome':
                return $emailService->sendWelcomeEmail($to, $data['name']);
            case 'rsvp':
                return $emailService->sendRSVPNotification($to, $data['owner_name'], $data['guest_name'], $data['attendance'], $data['message'], $data['invitation_title']);
            case 'message':
                return $emailService->sendMessageNotification($to, $data['owner_name'], $data['sender_name'], $data['message'], $data['invitation_title']);
            case 'payment':
                return $emailService->sendPaymentConfirmation($to, $data['name'], $data['plan'], $data['amount'], $data['order_id']);
            default:
                return false;
        }
    } catch (Exception $e) {
        error_log("Email notification failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate Indonesian phone number
 */
function validatePhoneNumber($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if starts with country code
    if (substr($phone, 0, 2) == '62') {
        return $phone;
    } elseif (substr($phone, 0, 1) == '0') {
        return '62' . substr($phone, 1);
    } else {
        return '62' . $phone;
    }
}

/**
 * Get user's invitation count
 */
function getUserInvitationCount($user_id, $db) {
    return $db->fetch("SELECT COUNT(*) as count FROM invitations WHERE user_id = ?", [$user_id])['count'];
}

/**
 * Check if user can create more invitations
 */
function canCreateInvitation($user_id, $db) {
    $user = $db->fetch("SELECT subscription_plan FROM users WHERE id = ?", [$user_id]);
    if (!$user) return false;
    
    $current_count = getUserInvitationCount($user_id, $db);
    $features = getSubscriptionFeatures($user['subscription_plan']);
    
    return $current_count < $features['invitations'];
}

/**
 * Log user activity
 */
function logActivity($user_id, $activity, $details = '', $db) {
    // Create activity log table if not exists
    $db->query("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            activity VARCHAR(255),
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $db->query("
        INSERT INTO activity_logs (user_id, activity, details, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ", [$user_id, $activity, $details, $ip_address, $user_agent]);
}

/**
 * Get analytics data for invitation
 */
function getInvitationAnalytics($invitation_id, $db) {
    $analytics = [
        'views' => 0,
        'unique_visitors' => 0,
        'rsvp_rate' => 0,
        'guest_count' => 0,
        'rsvp_count' => 0,
        'message_count' => 0,
        'attendance_breakdown' => []
    ];
    
    // Get basic counts
    $analytics['guest_count'] = $db->fetch("SELECT COUNT(*) as count FROM guests WHERE invitation_id = ?", [$invitation_id])['count'];
    $analytics['rsvp_count'] = $db->fetch("SELECT COUNT(*) as count FROM rsvps WHERE invitation_id = ?", [$invitation_id])['count'];
    $analytics['message_count'] = $db->fetch("SELECT COUNT(*) as count FROM guest_messages WHERE invitation_id = ?", [$invitation_id])['count'];
    
    // Calculate RSVP rate
    if ($analytics['guest_count'] > 0) {
        $analytics['rsvp_rate'] = round(($analytics['rsvp_count'] / $analytics['guest_count']) * 100, 2);
    }
    
    // Get attendance breakdown
    $attendance_data = $db->fetchAll("
        SELECT attendance, COUNT(*) as count 
        FROM rsvps 
        WHERE invitation_id = ? 
        GROUP BY attendance
    ", [$invitation_id]);
    
    foreach ($attendance_data as $data) {
        $analytics['attendance_breakdown'][$data['attendance']] = $data['count'];
    }
    
    return $analytics;
}

/**
 * Generate meta tags for invitation SEO
 */
function generateInvitationMetaTags($invitation) {
    $meta_tags = [];
    
    $meta_tags['title'] = htmlspecialchars($invitation['title'] . ' - ' . SITE_NAME);
    $meta_tags['description'] = htmlspecialchars("You're invited to the wedding of " . $invitation['groom_name'] . ' and ' . $invitation['bride_name'] . '. ' . ($invitation['wedding_date'] ? 'Join us on ' . date('F j, Y', strtotime($invitation['wedding_date'])) : ''));
    $meta_tags['keywords'] = htmlspecialchars('wedding invitation, ' . $invitation['groom_name'] . ', ' . $invitation['bride_name'] . ', wedding, invitation, RSVP');
    
    if ($invitation['cover_image']) {
        $meta_tags['image'] = SITE_URL . '/uploads/covers/' . $invitation['cover_image'];
    }
    
    $meta_tags['url'] = SITE_URL . '/invitation/' . $invitation['slug'];
    $meta_tags['type'] = 'website';
    
    return $meta_tags;
}

/**
 * Clean old files (for maintenance)
 */
function cleanOldFiles($directory, $days_old = 30) {
    if (!is_dir($directory)) return 0;
    
    $deleted_count = 0;
    $cutoff_time = time() - ($days_old * 24 * 60 * 60);
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getMTime() < $cutoff_time) {
            if (unlink($file->getRealPath())) {
                $deleted_count++;
            }
        }
    }
    
    return $deleted_count;
}

/**
 * Backup database (simple backup)
 */
function backupDatabase($db) {
    $backup_file = 'backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    if (!is_dir('backups')) {
        mkdir('backups', 0755, true);
    }
    
    $tables = ['users', 'invitations', 'guests', 'rsvps', 'guest_messages', 'themes', 'settings', 'admins'];
    $backup_content = '';
    
    foreach ($tables as $table) {
        $backup_content .= "-- Table: $table\n";
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        
        // Get table structure
        $create_table = $db->fetch("SHOW CREATE TABLE `$table`");
        $backup_content .= $create_table['Create Table'] . ";\n\n";
        
        // Get table data
        $rows = $db->fetchAll("SELECT * FROM `$table`");
        foreach ($rows as $row) {
            $backup_content .= "INSERT INTO `$table` VALUES (";
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
            }
            $backup_content .= implode(', ', $values) . ");\n";
        }
        $backup_content .= "\n";
    }
    
    return file_put_contents($backup_file, $backup_content) !== false ? $backup_file : false;
}
?>