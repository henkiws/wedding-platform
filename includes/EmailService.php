<?php
/**
 * Email Service Class for Wedding Invitation Platform
 * Handles all email notifications and templates
 */

class EmailService {
    private $smtp_host;
    private $smtp_username;
    private $smtp_password;
    private $smtp_port;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Email configuration - in production, use environment variables
        $this->smtp_host = 'smtp.gmail.com'; // Or your SMTP provider
        $this->smtp_username = 'your-email@gmail.com';
        $this->smtp_password = 'your-app-password';
        $this->smtp_port = 587;
        $this->from_email = 'noreply@' . strtolower(SITE_NAME) . '.com';
        $this->from_name = SITE_NAME;
    }
    
    /**
     * Send email using PHP's mail() function (for development)
     * In production, use PHPMailer or similar library
     */
    private function sendMail($to, $subject, $body, $headers = '') {
        // For development/demo purposes, we'll log emails instead of sending
        $this->logEmail($to, $subject, $body);
        
        // In production, uncomment and configure proper SMTP:
        /*
        require_once 'vendor/autoload.php'; // If using Composer
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host       = $this->smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtp_username;
            $mail->Password   = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->smtp_port;
            
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email send failed: {$mail->ErrorInfo}");
            return false;
        }
        */
        
        return true; // For demo purposes
    }
    
    /**
     * Log email for development purposes
     */
    private function logEmail($to, $subject, $body) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'body_preview' => substr(strip_tags($body), 0, 100) . '...'
        ];
        
        $log_file = 'logs/emails.log';
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Get HTML email template
     */
    private function getEmailTemplate($title, $content, $footer_text = '') {
        $site_url = SITE_URL;
        $site_name = SITE_NAME;
        $current_year = date('Y');
        
        if (empty($footer_text)) {
            $footer_text = "You received this email because you have an account with {$site_name}.";
        }
        
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #ec4899, #8b5cf6); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 28px; }
                .header .subtitle { opacity: 0.9; margin-top: 5px; }
                .content { padding: 40px 30px; }
                .button { display: inline-block; background: #ec4899; color: white; text-decoration: none; padding: 12px 30px; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .button:hover { background: #be185d; }
                .footer { background: #f8f9fa; padding: 30px; text-align: center; color: #666; font-size: 14px; }
                .footer a { color: #ec4899; text-decoration: none; }
                .social-links { margin: 20px 0; }
                .social-links a { display: inline-block; margin: 0 10px; color: #666; text-decoration: none; }
                .divider { height: 1px; background: #eee; margin: 30px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>💕 {$site_name}</h1>
                    <div class='subtitle'>Platform Undangan Pernikahan Digital</div>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>{$footer_text}</p>
                    <div class='social-links'>
                        <a href='#'>Facebook</a> |
                        <a href='#'>Instagram</a> |
                        <a href='#'>WhatsApp</a>
                    </div>
                    <div class='divider'></div>
                    <p>
                        <a href='{$site_url}'>Visit {$site_name}</a> |
                        <a href='{$site_url}/help'>Help Center</a> |
                        <a href='{$site_url}/unsubscribe'>Unsubscribe</a>
                    </p>
                    <p>&copy; {$current_year} {$site_name}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($user_email, $user_name) {
        $subject = "Welcome to " . SITE_NAME . "! 🎉";
        
        $content = "
            <h2>Welcome, {$user_name}! 🎉</h2>
            <p>Selamat datang di platform undangan pernikahan digital yang akan membuat hari spesial Anda menjadi tak terlupakan!</p>
            
            <h3>Apa yang bisa Anda lakukan sekarang:</h3>
            <ul>
                <li>✨ Buat undangan pernikahan digital pertama Anda</li>
                <li>🎨 Pilih dari berbagai tema yang indah</li>
                <li>👥 Kelola daftar tamu dengan mudah</li>
                <li>💌 Terima RSVP dan ucapan dari tamu</li>
                <li>📱 Bagikan undangan via WhatsApp atau email</li>
            </ul>
            
            <p style='text-align: center;'>
                <a href='" . SITE_URL . "/dashboard.php' class='button'>Mulai Buat Undangan</a>
            </p>
            
            <p>Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi tim support kami. Kami siap membantu membuat undangan pernikahan digital Anda menjadi sempurna!</p>
            
            <p>Salam hangat,<br><strong>Tim " . SITE_NAME . "</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($user_email, $subject, $template);
    }
    
    /**
     * Send payment confirmation email
     */
    public function sendPaymentConfirmation($user_email, $user_name, $plan, $amount, $order_id) {
        $subject = "Payment Confirmation - " . SITE_NAME;
        
        $content = "
            <h2>Payment Successful! 🎉</h2>
            <p>Hi {$user_name},</p>
            <p>Thank you for upgrading your subscription. Your payment has been successfully processed.</p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                <h3 style='margin-top: 0; color: #ec4899;'>Order Details</h3>
                <p><strong>Order ID:</strong> {$order_id}</p>
                <p><strong>Plan:</strong> " . ucfirst($plan) . " Plan</p>
                <p><strong>Amount:</strong> Rp " . number_format($amount, 0, ',', '.') . "</p>
                <p><strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>PAID</span></p>
                <p><strong>Valid Until:</strong> Forever ∞</p>
            </div>
            
            <h3>What's Next?</h3>
            <ul>
                <li>🎨 Access to all premium themes</li>
                <li>👥 Unlimited guest invitations</li>
                <li>📸 Unlimited photo & video gallery</li>
                <li>📊 Advanced analytics and insights</li>
                <li>🎵 Background music support</li>
                <li>💝 Digital gift collection</li>
            </ul>
            
            <p style='text-align: center;'>
                <a href='" . SITE_URL . "/dashboard.php' class='button'>Access Your Dashboard</a>
            </p>
            
            <p>If you have any questions about your subscription, please don't hesitate to contact our support team.</p>
            
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($user_email, $subject, $template);
    }
    
    /**
     * Send RSVP notification to invitation owner
     */
    public function sendRSVPNotification($owner_email, $owner_name, $guest_name, $attendance, $message, $invitation_title) {
        $attendance_text = [
            'yes' => 'akan hadir',
            'no' => 'tidak bisa hadir', 
            'maybe' => 'mungkin hadir'
        ];
        
        $subject = "New RSVP Response - {$invitation_title}";
        
        $status_color = $attendance == 'yes' ? '#10b981' : ($attendance == 'no' ? '#ef4444' : '#f59e0b');
        $status_icon = $attendance == 'yes' ? '✅' : ($attendance == 'no' ? '❌' : '❓');
        
        $content = "
            <h2>New RSVP Response! {$status_icon}</h2>
            <p>Hi {$owner_name},</p>
            <p>Anda menerima respons RSVP baru untuk undangan <strong>{$invitation_title}</strong>.</p>
            
            <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid {$status_color};'>
                <h3 style='margin-top: 0;'>RSVP Details</h3>
                <p><strong>Guest Name:</strong> {$guest_name}</p>
                <p><strong>Response:</strong> <span style='color: {$status_color}; font-weight: bold; text-transform: capitalize;'>{$attendance_text[$attendance]}</span></p>
                " . ($message ? "<p><strong>Message:</strong><br><em>\"" . htmlspecialchars($message) . "\"</em></p>" : "") . "
                <p><small>Received: " . date('d M Y, H:i') . " WIB</small></p>
            </div>
            
            <p style='text-align: center;'>
                <a href='" . SITE_URL . "/rsvp.php' class='button'>View All RSVP Responses</a>
            </p>
            
            <p>You can manage all your RSVP responses and guest messages from your dashboard.</p>
            
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($owner_email, $subject, $template);
    }
    
    /**
     * Send new message notification to invitation owner
     */
    public function sendMessageNotification($owner_email, $owner_name, $sender_name, $message, $invitation_title) {
        $subject = "New Message - {$invitation_title}";
        
        $content = "
            <h2>New Message Received! 💌</h2>
            <p>Hi {$owner_name},</p>
            <p>Anda menerima ucapan baru untuk undangan <strong>{$invitation_title}</strong>.</p>
            
            <div style='background: #fdf2f8; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ec4899;'>
                <h3 style='margin-top: 0; color: #ec4899;'>Message from {$sender_name}</h3>
                <p style='font-style: italic; font-size: 16px; line-height: 1.6;'>
                    \"" . nl2br(htmlspecialchars($message)) . "\"
                </p>
                <p><small>Received: " . date('d M Y, H:i') . " WIB</small></p>
            </div>
            
            <p style='text-align: center;'>
                <a href='" . SITE_URL . "/rsvp.php?tab=messages' class='button'>View All Messages</a>
            </p>
            
            <p>Share the love by viewing all the beautiful messages from your friends and family!</p>
            
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($owner_email, $subject, $template);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordReset($user_email, $user_name, $reset_token) {
        $subject = "Reset Your Password - " . SITE_NAME;
        $reset_url = SITE_URL . "/reset-password.php?token=" . $reset_token;
        
        $content = "
            <h2>Password Reset Request</h2>
            <p>Hi {$user_name},</p>
            <p>We received a request to reset your password. If you made this request, click the button below to reset your password:</p>
            
            <p style='text-align: center;'>
                <a href='{$reset_url}' class='button'>Reset Password</a>
            </p>
            
            <p><strong>This link will expire in 1 hour for security reasons.</strong></p>
            
            <p>If you didn't request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
            
            <p>For security, you can also copy and paste this URL into your browser:</p>
            <p style='background: #f8f9fa; padding: 10px; border-radius: 4px; word-break: break-all; font-family: monospace; font-size: 12px;'>
                {$reset_url}
            </p>
            
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $footer_text = "You received this email because someone requested a password reset for your account. If this wasn't you, please ignore this email.";
        $template = $this->getEmailTemplate($subject, $content, $footer_text);
        return $this->sendMail($user_email, $subject, $template);
    }
    
    /**
     * Send invitation shared notification
     */
    public function sendInvitationSharedEmail($guest_email, $guest_name, $invitation_title, $couple_names, $wedding_date, $invitation_url) {
        $subject = "You're Invited! {$invitation_title}";
        
        $content = "
            <h2>You're Invited to Our Wedding! 💕</h2>
            <p>Dear {$guest_name},</p>
            <p>With great joy, we invite you to celebrate our special day!</p>
            
            <div style='background: linear-gradient(135deg, #fdf2f8, #fef7ff); padding: 30px; border-radius: 15px; margin: 30px 0; text-align: center; border: 2px solid #f3e8ff;'>
                <h3 style='color: #ec4899; font-size: 24px; margin: 0 0 10px 0;'>{$couple_names}</h3>
                <p style='color: #8b5cf6; font-size: 18px; margin: 0 0 20px 0;'>are getting married!</p>
                <p style='color: #6b7280; margin: 0;'>📅 {$wedding_date}</p>
            </div>
            
            <p>We would be honored to have you join us on our special day. Please view our digital invitation for all the details:</p>
            
            <p style='text-align: center;'>
                <a href='{$invitation_url}' class='button'>View Wedding Invitation</a>
            </p>
            
            <p>In our digital invitation, you'll find:</p>
            <ul>
                <li>📍 Venue details and location</li>
                <li>⏰ Event schedule and timing</li>
                <li>📸 Our photo gallery</li>
                <li>💌 RSVP form</li>
                <li>🎵 Special music for our day</li>
            </ul>
            
            <p style='background: #eff6ff; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;'>
                💡 <strong>Tip:</strong> Save this invitation to your phone's home screen for quick access!
            </p>
            
            <p>We can't wait to celebrate with you!</p>
            
            <p>With love,<br><strong>{$couple_names}</strong></p>
        ";
        
        $footer_text = "You received this wedding invitation because you're special to us! We hope to see you on our big day.";
        $template = $this->getEmailTemplate($subject, $content, $footer_text);
        return $this->sendMail($guest_email, $subject, $template);
    }
    
    /**
     * Send subscription expiry warning
     */
    public function sendSubscriptionExpiryWarning($user_email, $user_name, $days_remaining) {
        $subject = "Subscription Expiring Soon - " . SITE_NAME;
        
        $content = "
            <h2>⚠️ Subscription Expiring Soon</h2>
            <p>Hi {$user_name},</p>
            <p>Your free subscription will expire in <strong>{$days_remaining} days</strong>.</p>
            
            <div style='background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #f59e0b;'>
                <h3 style='margin-top: 0; color: #92400e;'>What happens when it expires?</h3>
                <ul style='color: #92400e;'>
                    <li>Your invitations will become inactive</li>
                    <li>Guests won't be able to access your invitation</li>
                    <li>RSVP and messages will stop working</li>
                    <li>Your data will be preserved for 30 days</li>
                </ul>
            </div>
            
            <h3>🚀 Upgrade to Keep Your Invitation Active Forever!</h3>
            <p>Our premium plans offer:</p>
            <ul>
                <li>✅ Lifetime access to your invitations</li>
                <li>✅ Unlimited guests and photos</li>
                <li>✅ Premium themes and features</li>
                <li>✅ Advanced analytics</li>
                <li>✅ Priority support</li>
            </ul>
            
            <p style='text-align: center;'>
                <a href='" . SITE_URL . "/subscription.php' class='button'>Upgrade Now - Starting Rp 99k</a>
            </p>
            
            <p>Don't let your special memories become inaccessible. Upgrade today!</p>
            
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($user_email, $subject, $template);
    }
    
    /**
     * Send general notification email
     */
    public function sendNotificationEmail($user_email, $user_name, $subject, $message, $action_url = null, $action_text = null) {
        $content = "
            <h2>Notification</h2>
            <p>Hi {$user_name},</p>
            <p>{$message}</p>
            " . ($action_url ? "
            <p style='text-align: center;'>
                <a href='{$action_url}' class='button'>{$action_text}</a>
            </p>
            " : "") . "
            <p>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>
        ";
        
        $template = $this->getEmailTemplate($subject, $content);
        return $this->sendMail($user_email, $subject, $template);
    }
}

// Usage examples:
/*
$emailService = new EmailService();

// Send welcome email
$emailService->sendWelcomeEmail('user@example.com', 'John Doe');

// Send payment confirmation
$emailService->sendPaymentConfirmation('user@example.com', 'John Doe', 'premium', 99000, 'WV-20240101-1234');

// Send RSVP notification
$emailService->sendRSVPNotification('owner@example.com', 'John', 'Jane Smith', 'yes', 'Looking forward to celebrating!', 'John & Jane Wedding');

// Send message notification
$emailService->sendMessageNotification('owner@example.com', 'John', 'Jane Smith', 'Congratulations on your wedding!', 'John & Jane Wedding');
*/
?>