<?php
/**
 * Global REST API Router & Controller
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

$dbObj = new Database();
$conn = $dbObj->getConnection();

$action = $_GET['action'] ?? '';

// Global Setting Helper Function
if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        global $conn;
        if (!$conn) return $default;
        try {
            $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = :key");
            $stmt->execute(['key' => $key]);
            $row = $stmt->fetch();
            return $row ? $row['setting_value'] : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

// SMTP Email OTP dispatcher helper
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $host = getSetting('smtp_host', 'smtp.gmail.com');
        $port = getSetting('smtp_port', '587');
        $secure = getSetting('smtp_secure', 'tls');
        $username = getSetting('smtp_username', 'yourgmail@gmail.com');
        $password = getSetting('smtp_password', 'gmail-app-password');

        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $secure;
        $mail->Port = intval($port);

        $mail->setFrom($username, getSetting('design_site_name', 'Yuvalay MakerSpace'));
        $mail->addAddress($email);

        $mail->Subject = 'Email Verification Code';
        $mail->Body = "Welcome to Yuvalay MakerSpace.\n\nYour Email Verification Code is:\n\n$otp\n\nThis code will expire in 10 minutes.\n\nIf you did not create an account, please ignore this email.\n";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error: " . $mail->ErrorInfo);
        return false;
    }
}

// Helper function to return JSON responses
function sendResponse($status, $message, $data = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $data));
    exit;
}

// Role Based Access Control helpers
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        sendResponse('error', 'User authentication required. Please log in.');
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['superadmin', 'admin'])) {
        sendResponse('error', 'Unauthorized access. Administrator privileges required.');
    }
}

function requireCanEdit() {
    if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['superadmin', 'admin', 'editor'])) {
        sendResponse('error', 'Unauthorized access. Editor privileges required.');
    }
}

switch ($action) {

    // === AUTHENTICATION ===

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (empty($email) || empty($password)) {
            sendResponse('error', 'Please fill all required fields');
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                sendResponse('error', 'Invalid email or password');
            }

            if ($user['status'] === 'suspended') {
                sendResponse('error', 'Your account has been suspended. Please contact administrator.');
            }

            if (isset($user['email_verified']) && intval($user['email_verified']) === 0) {
                $_SESSION['verification_email'] = $user['email'];
                sendResponse('error', 'Please verify your email address before logging in.', ['not_verified' => true]);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['edit_mode'] = false; // default off

            sendResponse('success', 'Logged in successfully', [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        
        // 1. IP Rate Limiting against spam registrations (Max 5 per hour)
        try {
            $conn->exec("DELETE FROM registration_rate_limit WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmtRate = $conn->prepare("SELECT COUNT(*) FROM registration_rate_limit WHERE ip_address = :ip");
            $stmtRate->execute(['ip' => $ip]);
            $rateCount = $stmtRate->fetchColumn();
            if ($rateCount >= 5) {
                sendResponse('error', 'Too many registration attempts. Please try again in an hour.');
            }
        } catch (Exception $e) {
            // Log or ignore rate limit errors to prevent locking out on DB issues
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'member');

        if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
            sendResponse('error', 'Please fill all required fields');
        }

        // 2. Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse('error', 'Invalid email address format.');
        }

        // 3. Validate password strength
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || (!preg_match('/[0-9]/', $password) && !preg_match('/[^A-Za-z0-9]/', $password))) {
            sendResponse('error', 'Password is too weak. It must be at least 8 characters long and contain uppercase, lowercase, and numeric or special characters.');
        }

        // 4. Google reCAPTCHA v3 Integration
        $recaptcha_secret_key = getSetting('recaptcha_secret_key', '');
        if (!empty($recaptcha_secret_key)) {
            $token = $_POST['recaptcha_token'] ?? '';
            if (empty($token)) {
                sendResponse('error', 'Google reCAPTCHA verification token is missing.');
            }
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret' => $recaptcha_secret_key,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch), true);
            curl_close($ch);
            
            if (!$response || !$response['success'] || $response['score'] < 0.5) {
                sendResponse('error', 'Google reCAPTCHA verification failed. (Spam score too low)');
            }
        }

        if (!in_array($role, ['member', 'volunteer', 'mentor'])) {
            $role = 'member';
        }

        try {
            // Record rate limit attempt
            if (isset($ip)) {
                $stmtInsRate = $conn->prepare("INSERT INTO registration_rate_limit (ip_address) VALUES (:ip)");
                $stmtInsRate->execute(['ip' => $ip]);
            }

            // Check email exist
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                sendResponse('error', 'Email is already registered');
            }

            // Insert User
            $pwdHash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name, email, mobile, password_hash, role, status, email_verified) VALUES (:name, :email, :mobile, :pwd, :role, 'approved', 0)");
            $ins->execute([
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'pwd' => $pwdHash,
                'role' => $role
            ]);

            // Generate Secure 6-digit OTP
            $otp = rand(100000, 999999);

            // Store OTP in database
            $conn->prepare("DELETE FROM email_verification WHERE email = :email")->execute(['email' => $email]);
            $insOtp = $conn->prepare("INSERT INTO email_verification (email, otp, attempts, resends, last_resend_at) VALUES (:email, :otp, 0, 1, NOW())");
            $insOtp->execute([
                'email' => $email,
                'otp' => $otp
            ]);

            // Set session email for verification page
            $_SESSION['verification_email'] = $email;

            // Send OTP
            $sent = sendOtpEmail($email, $otp);
            $extra = [];
            if (!$sent) {
                $extra['otp_fallback'] = $otp;
                sendResponse('success', "Registration successful! (SMTP not configured, OTP simulated). OTP: $otp", $extra);
            }

            sendResponse('success', 'Registration successful! An OTP has been sent to your email.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'verify-otp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $email = $_SESSION['verification_email'] ?? '';
        $otp = trim($_POST['otp'] ?? '');

        if (empty($email)) {
            sendResponse('error', 'Session expired. Please register or log in again.');
        }
        if (empty($otp) || strlen($otp) !== 6 || !is_numeric($otp)) {
            sendResponse('error', 'Please enter a valid 6-digit OTP.');
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $record = $stmt->fetch();

            if (!$record) {
                sendResponse('error', 'No active verification code found for this email. Please request a new one.');
            }

            // Expiry check (10 minutes = 600 seconds)
            $created = strtotime($record['created_at']);
            if ((time() - $created) > 600) {
                sendResponse('error', 'Verification code has expired. Please request a new code.');
            }

            // Attempt limits check (Max 5 attempts)
            if ($record['attempts'] >= 5) {
                sendResponse('error', 'Maximum verification attempts exceeded. Please request a new code.');
            }

            // Check if OTP matches
            if ($record['otp'] !== $otp) {
                // Increment attempts count
                $upd = $conn->prepare("UPDATE email_verification SET attempts = attempts + 1 WHERE email = :email");
                $upd->execute(['email' => $email]);
                
                $remaining = 5 - ($record['attempts'] + 1);
                if ($remaining <= 0) {
                    $conn->prepare("DELETE FROM email_verification WHERE email = :email")->execute(['email' => $email]);
                    sendResponse('error', 'Maximum verification attempts reached. This OTP is now invalid. Please request a new one.');
                }
                sendResponse('error', "Incorrect verification code. $remaining attempts remaining.");
            }

            // Success! Activate user and cleanup OTP
            $conn->beginTransaction();
            $updUser = $conn->prepare("UPDATE users SET email_verified = 1 WHERE email = :email");
            $updUser->execute(['email' => $email]);

            $del = $conn->prepare("DELETE FROM email_verification WHERE email = :email");
            $del->execute(['email' => $email]);
            $conn->commit();

            unset($_SESSION['verification_email']);
            sendResponse('success', 'Email verified successfully! You can now log in.');
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'resend-otp':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $email = $_SESSION['verification_email'] ?? '';

        if (empty($email)) {
            sendResponse('error', 'Session expired. Please register or log in again.');
        }

        try {
            $stmt = $conn->prepare("SELECT * FROM email_verification WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $record = $stmt->fetch();

            $resends = 1;
            if ($record) {
                $now = time();
                $last_resend = $record['last_resend_at'] ? strtotime($record['last_resend_at']) : 0;
                
                // Max 3 resend requests per hour (3600 seconds)
                if (($now - $last_resend) < 3600) {
                    if ($record['resends'] >= 3) {
                        $wait_time = 3600 - ($now - $last_resend);
                        $mins = ceil($wait_time / 60);
                        sendResponse('error', "Maximum resend limit reached. Please try again after $mins minutes.");
                    }
                    $resends = $record['resends'] + 1;
                }
            }

            $otp = rand(100000, 999999);

            $conn->prepare("DELETE FROM email_verification WHERE email = :email")->execute(['email' => $email]);
            $ins = $conn->prepare("INSERT INTO email_verification (email, otp, attempts, resends, last_resend_at) VALUES (:email, :otp, 0, :resends, NOW())");
            $ins->execute([
                'email' => $email,
                'otp' => $otp,
                'resends' => $resends
            ]);

            $sent = sendOtpEmail($email, $otp);
            $extra = [];
            if (!$sent) {
                $extra['otp_fallback'] = $otp;
                sendResponse('success', "Verification email simulated (SMTP not configured). OTP: $otp", $extra);
            }
            sendResponse('success', 'A new verification code has been sent to your email.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'admin-verify-user':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            $stmt = $conn->prepare("UPDATE users SET email_verified = 1 WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            sendResponse('success', 'User manually verified successfully.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'admin-resend-verification':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();
            if (!$user) sendResponse('error', 'User not found');
            $email = $user['email'];

            $otp = rand(100000, 999999);
            $conn->prepare("DELETE FROM email_verification WHERE email = :email")->execute(['email' => $email]);
            $ins = $conn->prepare("INSERT INTO email_verification (email, otp, attempts, resends, last_resend_at) VALUES (:email, :otp, 0, 1, NOW())");
            $ins->execute([
                'email' => $email,
                'otp' => $otp
            ]);

            $sent = sendOtpEmail($email, $otp);
            $extra = [];
            if (!$sent) {
                $extra['otp_fallback'] = $otp;
                sendResponse('success', "Verification email simulated (SMTP not configured). OTP: $otp", $extra);
            }
            sendResponse('success', 'Verification email sent successfully.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'admin-delete-user':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();
            if (!$user) sendResponse('error', 'User not found');
            
            $conn->beginTransaction();
            $conn->prepare("DELETE FROM email_verification WHERE email = :email")->execute(['email' => $user['email']]);
            $conn->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $user_id]);
            $conn->commit();
            
            sendResponse('success', 'User account deleted successfully.');
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header("Location: /login.php");
        exit;

    case 'toggle-edit-mode':
        requireAdmin();
        $_SESSION['edit_mode'] = !($_SESSION['edit_mode'] ?? false);
        sendResponse('success', 'Edit mode updated', ['edit_mode' => $_SESSION['edit_mode']]);
        break;


    // === EVENTS API ===

    case 'get-events':
        $cat = $_GET['category'] ?? 'All';
        try {
            $sql = "SELECT * FROM events";
            $params = [];
            if ($cat !== 'All') {
                $sql .= " WHERE category = :cat";
                $params['cat'] = $cat;
            }
            $sql .= " ORDER BY event_date ASC, event_time ASC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            sendResponse('success', 'Events fetched', ['events' => $rows]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'register-event':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        
        $evt_id = intval($_POST['event_id'] ?? 0);
        $user_id = $_SESSION['user_id'];
        
        // Multi step forms answers (capture both profile fields and custom form questions)
        $answers_arr = [];
        $exclude_fields = ['event_id', 'name', 'email', 'mobile'];
        foreach ($_POST as $k => $v) {
            if (!in_array($k, $exclude_fields)) {
                $answers_arr[$k] = is_array($v) ? implode(", ", $v) : trim($v);
            }
        }
        $answers = json_encode($answers_arr);

        try {
            $conn->beginTransaction();

            // Check event capacity and seats
            $stmt = $conn->prepare("SELECT * FROM events WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $evt_id]);
            $event = $stmt->fetch();

            if (!$event) {
                sendResponse('error', 'Event not found');
            }

            if ($event['available_seats'] <= 0) {
                sendResponse('error', 'Registration full! No available seats.');
            }

            // Check if already registered
            $check = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = :evt AND user_id = :usr AND status != 'Cancelled'");
            $check->execute(['evt' => $evt_id, 'usr' => $user_id]);
            if ($check->fetch()) {
                sendResponse('error', 'You are already registered for this event');
            }

            // Generate Ticket Registration ID: YMS-EVT-XXXXX
            $reg_id = "YMS-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));

            // Save Registration
            $ins = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, registration_id, answers, status) VALUES (:evt, :usr, :reg, :ans, 'Registered')");
            $ins->execute([
                'evt' => $evt_id,
                'usr' => $user_id,
                'reg' => $reg_id,
                'ans' => $answers
            ]);

            // Deduct seats
            $upd = $conn->prepare("UPDATE events SET available_seats = available_seats - 1 WHERE id = :id");
            $upd->execute(['id' => $evt_id]);

            // Save profile details to user if missing
            $updUser = $conn->prepare("UPDATE users SET dob = :dob, gender = :gender, college = :college, branch = :branch, semester = :sem, student_id = :stud, occupation = :occ, skills = :skills, experience_level = :exp WHERE id = :id");
            $updUser->execute([
                'dob' => $_POST['dob'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'college' => $_POST['college'] ?? null,
                'branch' => $_POST['branch'] ?? null,
                'sem' => $_POST['semester'] ?? null,
                'stud' => $_POST['student_id'] ?? null,
                'occ' => $_POST['occupation'] ?? null,
                'skills' => $_POST['skills'] ?? null,
                'exp' => $_POST['experience_level'] ?? null,
                'id' => $user_id
            ]);

            $conn->commit();
            sendResponse('success', 'Registration completed successfully', [
                'registration_id' => $reg_id
            ]);
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'cancel-registration':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $reg_id = trim($_POST['registration_id'] ?? '');

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare("SELECT * FROM event_registrations WHERE registration_id = :reg AND user_id = :usr AND status = 'Registered' FOR UPDATE");
            $stmt->execute(['reg' => $reg_id, 'usr' => $_SESSION['user_id']]);
            $reg = $stmt->fetch();

            if (!$reg) {
                sendResponse('error', 'Registration record not found or already cancelled');
            }

            // Cancel
            $updReg = $conn->prepare("UPDATE event_registrations SET status = 'Cancelled' WHERE id = :id");
            $updReg->execute(['id' => $reg['id']]);

            // Add back seat
            $updEvt = $conn->prepare("UPDATE events SET available_seats = available_seats + 1 WHERE id = :id");
            $updEvt->execute(['id' => $reg['event_id']]);

            $conn->commit();
            sendResponse('success', 'Event registration cancelled successfully');
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'get-my-registrations':
        requireLogin();
        try {
            $stmt = $conn->prepare("SELECT r.*, e.title, e.event_date, e.event_time, e.venue, e.category 
                                    FROM event_registrations r 
                                    JOIN events e ON r.event_id = e.id 
                                    WHERE r.user_id = :usr 
                                    ORDER BY e.event_date DESC");
            $stmt->execute(['usr' => $_SESSION['user_id']]);
            $rows = $stmt->fetchAll();
            sendResponse('success', 'Registrations fetched', ['registrations' => $rows]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;


    // === RESOURCES API ===

    case 'increment-download':
        $res_id = intval($_GET['id'] ?? 0);
        try {
            $stmt = $conn->prepare("UPDATE resources SET downloads_count = downloads_count + 1 WHERE id = :id");
            $stmt->execute(['id' => $res_id]);
            sendResponse('success', 'Counter incremented');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;


    // === CONTACT MESSAGES ===

    case 'submit-contact':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            sendResponse('error', 'Please fill all required fields');
        }

        try {
            $ins = $conn->prepare("INSERT INTO contact_messages (name, email, mobile, subject, message) VALUES (:name, :email, :mobile, :sub, :msg)");
            $ins->execute([
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'sub' => $subject,
                'msg' => $message
            ]);
            sendResponse('success', 'Message submitted successfully. We will contact you soon.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;


    // === CMS EDIT MODE ENDPOINTS ===

    case 'update-cms-text':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $key = trim($_POST['key'] ?? '');
        $val = trim($_POST['value'] ?? '');

        if (empty($key)) sendResponse('error', 'Setting key required');

        try {
            $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute(['key' => $key, 'val' => $val]);

            // Save Audit Log
            $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (:usr, 'Update Setting', :details)");
            $log->execute([
                'usr' => $_SESSION['user_id'],
                'details' => "Updated settings key '{$key}'"
            ]);

            sendResponse('success', 'Content updated successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'get-cms-slides':
        requireAdmin();
        try {
            $stmt = $conn->prepare("SELECT * FROM homepage_slides ORDER BY display_order ASC");
            $stmt->execute();
            sendResponse('success', 'Slides fetched', ['slides' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'update-cms-slide':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $sub = trim($_POST['subtitle'] ?? '');
        $img = trim($_POST['image_url'] ?? '');
        $order = intval($_POST['display_order'] ?? 0);

        try {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE homepage_slides SET title = :title, subtitle = :sub, image_url = :img, display_order = :ord WHERE id = :id");
                $stmt->execute([
                    'title' => $title,
                    'sub' => $sub,
                    'img' => $img,
                    'ord' => $order,
                    'id' => $id
                ]);
            } else {
                $stmt = $conn->prepare("INSERT INTO homepage_slides (title, subtitle, image_url, display_order) VALUES (:title, :sub, :img, :ord)");
                $stmt->execute([
                    'title' => $title,
                    'sub' => $sub,
                    'img' => $img,
                    'ord' => $order
                ]);
            }
            sendResponse('success', 'Slide saved successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'delete-cms-slide':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);

        try {
            $stmt = $conn->prepare("DELETE FROM homepage_slides WHERE id = :id");
            $stmt->execute(['id' => $id]);
            sendResponse('success', 'Slide deleted');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'update-cms-list':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $table = trim($_POST['table'] ?? '');
        $id = intval($_POST['id'] ?? 0);
        $fields = $_POST['fields'] ?? [];

        $allowed_tables = ['workspaces', 'certifications', 'milestones', 'team_members', 'navigation_items', 'footer_sections', 'footer_links', 'seo_meta', 'partners', 'testimonials', 'gallery', 'custom_pages'];
        if (!in_array($table, $allowed_tables)) {
            sendResponse('error', 'Unauthorized table insertion.');
        }

        if (empty($fields)) {
            sendResponse('error', 'No fields provided.');
        }

        try {
            if ($id > 0) {
                // If updating custom_pages slug, sync it in seo_meta and page_blocks
                $old_slug = null;
                if ($table === 'custom_pages') {
                    $old_stmt = $conn->prepare("SELECT slug FROM custom_pages WHERE id = :id");
                    $old_stmt->execute(['id' => $id]);
                    $old_page = $old_stmt->fetch();
                    $old_slug = $old_page ? $old_page['slug'] : null;
                }

                $set_clause = [];
                $params = ['id' => $id];
                foreach ($fields as $key => $val) {
                    $set_clause[] = "`$key` = :$key";
                    $params[$key] = $val;
                }
                $sql = "UPDATE `$table` SET " . implode(", ", $set_clause) . " WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                if ($table === 'custom_pages' && $old_slug) {
                    $new_slug = trim($fields['slug'] ?? '');
                    if (!empty($new_slug) && $old_slug !== $new_slug) {
                        $upd_seo = $conn->prepare("UPDATE seo_meta SET page_name = :new_slug WHERE page_name = :old_slug");
                        $upd_seo->execute(['new_slug' => $new_slug, 'old_slug' => $old_slug]);
                        $upd_blocks = $conn->prepare("UPDATE page_blocks SET page_name = :new_slug WHERE page_name = :old_slug");
                        $upd_blocks->execute(['new_slug' => $new_slug, 'old_slug' => $old_slug]);
                    }
                }

                sendResponse('success', 'Item updated successfully');
            } else {
                $cols = [];
                $placeholders = [];
                $params = [];
                foreach ($fields as $key => $val) {
                    $cols[] = "`$key`";
                    $placeholders[] = ":$key";
                    $params[$key] = $val;
                }
                $sql = "INSERT INTO `$table` (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $placeholders) . ")";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $new_id = $conn->lastInsertId();

                if ($table === 'custom_pages') {
                    $slug = trim($fields['slug'] ?? '');
                    $title = trim($fields['title'] ?? '');
                    if (!empty($slug)) {
                        $seo_stmt = $conn->prepare("INSERT INTO seo_meta (page_name, meta_title, meta_description, keywords) VALUES (:slug, :title, :desc, :keywords) ON DUPLICATE KEY UPDATE meta_title = VALUES(meta_title)");
                        $seo_stmt->execute([
                            'slug' => $slug,
                            'title' => $title . " | " . getSetting('design_site_name', 'Yuvalay MakerSpace'),
                            'desc' => 'Custom page: ' . $title,
                            'keywords' => 'custom page, ' . $slug
                        ]);
                    }
                }

                sendResponse('success', 'Item created successfully', ['insert_id' => $new_id]);
            }
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'delete-cms-item':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $table = trim($_POST['table'] ?? '');
        $id = intval($_POST['id'] ?? 0);

        $allowed_tables = ['workspaces', 'certifications', 'milestones', 'team_members', 'navigation_items', 'footer_sections', 'footer_links', 'seo_meta', 'partners', 'testimonials', 'gallery', 'custom_pages'];
        if (!in_array($table, $allowed_tables)) {
            sendResponse('error', 'Unauthorized table access.');
        }

        try {
            if ($table === 'custom_pages') {
                $slug_stmt = $conn->prepare("SELECT slug FROM custom_pages WHERE id = :id");
                $slug_stmt->execute(['id' => $id]);
                $page_row = $slug_stmt->fetch();
                if ($page_row) {
                    $slug = $page_row['slug'];
                    // Delete SEO meta data
                    $del_seo = $conn->prepare("DELETE FROM seo_meta WHERE page_name = :slug");
                    $del_seo->execute(['slug' => $slug]);
                    // Delete layout blocks
                    $del_blocks = $conn->prepare("DELETE FROM page_blocks WHERE page_name = :slug");
                    $del_blocks->execute(['slug' => $slug]);
                }
            }

            $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = :id");
            $stmt->execute(['id' => $id]);
            sendResponse('success', 'Item deleted successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'media-upload':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        if (!isset($_FILES['file'])) sendResponse('error', 'No file was uploaded.');

        $file = $_FILES['file'];
        $folder = trim($_POST['folder'] ?? 'General');

        if ($file['error'] !== UPLOAD_ERR_OK) {
            sendResponse('error', 'Upload error code: ' . $file['error']);
        }

        $upload_dir = __DIR__ . '/public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $path_info = pathinfo($filename);
        $base = $path_info['filename'];
        $ext = isset($path_info['extension']) ? '.' . $path_info['extension'] : '';
        $counter = 1;
        while (file_exists($upload_dir . $filename)) {
            $filename = $base . '_' . $counter . $ext;
            $counter++;
        }

        $dest = $upload_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $file_url = '/public/uploads/' . $filename;
            try {
                $stmt = $conn->prepare("INSERT INTO media_library (file_name, file_url, file_type, file_size, folder_name) VALUES (:name, :url, :type, :size, :folder)");
                $stmt->execute([
                    'name' => $filename,
                    'url' => $file_url,
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'folder' => $folder
                ]);
                sendResponse('success', 'File uploaded successfully', ['data' => ['file_url' => $file_url]]);
            } catch (Exception $e) {
                sendResponse('error', $e->getMessage());
            }
        } else {
            sendResponse('error', 'Failed to move uploaded file.');
        }
        break;

    case 'get-media':
        requireAdmin();
        try {
            $stmt = $conn->prepare("SELECT * FROM media_library ORDER BY created_at DESC");
            $stmt->execute();
            sendResponse('success', 'Media library loaded', ['media' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'save-event-fields':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $event_id = intval($_POST['event_id'] ?? 0);
        $fields_json = $_POST['fields'] ?? '[]';
        
        $fields = json_decode($fields_json, true);
        if (!is_array($fields)) {
            sendResponse('error', 'Invalid field configurations.');
        }

        try {
            $conn->beginTransaction();
            $del = $conn->prepare("DELETE FROM event_form_fields WHERE event_id = :id");
            $del->execute(['id' => $event_id]);

            $ins = $conn->prepare("INSERT INTO event_form_fields (event_id, field_name, field_label, field_type, field_options, is_required, display_order) VALUES (:evt, :name, :label, :type, :opts, :req, :ord)");
            $counter = 1;
            foreach ($fields as $f) {
                $ins->execute([
                    'evt' => $event_id,
                    'name' => $f['field_name'] ?? ('custom_field_' . $counter),
                    'label' => $f['field_label'] ?? 'Custom Field',
                    'type' => $f['field_type'] ?? 'text',
                    'opts' => $f['field_options'] ?? null,
                    'req' => intval($f['is_required'] ?? 0),
                    'ord' => $counter++
                ]);
            }

            $conn->commit();
            sendResponse('success', 'Event registration questions saved successfully');
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'get-event-fields':
        $event_id = intval($_GET['event_id'] ?? 0);
        try {
            $stmt = $conn->prepare("SELECT * FROM event_form_fields WHERE event_id = :id ORDER BY display_order ASC");
            $stmt->execute(['id' => $event_id]);
            sendResponse('success', 'Event fields fetched', ['fields' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;
    case 'get-custom-pages':
        requireAdmin();
        try {
            $stmt = $conn->prepare("SELECT * FROM custom_pages ORDER BY created_at DESC");
            $stmt->execute();
            sendResponse('success', 'Custom pages fetched', ['pages' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'save-page-blocks':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $page = trim($_POST['page_name'] ?? '');
        $blocks_json = $_POST['blocks'] ?? '[]';

        $blocks = json_decode($blocks_json, true);
        if (!is_array($blocks)) sendResponse('error', 'Invalid blocks array.');

        try {
            $conn->beginTransaction();
            $del = $conn->prepare("DELETE FROM page_blocks WHERE page_name = :page");
            $del->execute(['page' => $page]);

            $ins = $conn->prepare("INSERT INTO page_blocks (page_name, block_type, block_title, block_content, display_order, is_active) VALUES (:page, :type, :title, :content, :ord, :active)");
            $counter = 1;
            foreach ($blocks as $b) {
                $ins->execute([
                    'page' => $page,
                    'type' => $b['block_type'],
                    'title' => $b['block_title'] ?? '',
                    'content' => is_string($b['block_content']) ? $b['block_content'] : json_encode($b['block_content']),
                    'ord' => $counter++,
                    'active' => intval($b['is_active'] ?? 1)
                ]);
            }

            $conn->commit();
            sendResponse('success', 'Page sections updated successfully');
        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'get-page-blocks':
        $page = trim($_GET['page_name'] ?? '');
        try {
            $stmt = $conn->prepare("SELECT * FROM page_blocks WHERE page_name = :page ORDER BY display_order ASC");
            $stmt->execute(['page' => $page]);
            sendResponse('success', 'Page blocks fetched', ['blocks' => $stmt->fetchAll()]);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'generate-seo-files':
        requireAdmin();
        try {
            $robots_txt = "User-agent: *\nDisallow: /admin.php\nDisallow: /api.php\n\nSitemap: http://127.0.0.1:8000/sitemap.xml";
            file_put_contents(__DIR__ . '/robots.txt', $robots_txt);

            $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
            
            $pages = ['index.php', 'about.php', 'what-we-do.php', 'resources.php', 'events.php', 'contact.php'];
            foreach ($pages as $p) {
                $sitemap .= "  <url>\n";
                $sitemap .= "    <loc>http://127.0.0.1:8000/$p</loc>\n";
                $sitemap .= '    <lastmod>' . date('Y-m-d') . "</lastmod>\n";
                $sitemap .= "    <changefreq>weekly</changefreq>\n";
                $sitemap .= "  </url>\n";
            }
            $sitemap .= '</urlset>';
            file_put_contents(__DIR__ . '/sitemap.xml', $sitemap);

            sendResponse('success', 'Sitemap.xml and robots.txt files successfully re-generated in document root.');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;


    // === ADMIN DASHBOARD ACTIONS ===

    case 'create-event':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat = trim($_POST['category'] ?? '');
        $date = trim($_POST['event_date'] ?? '');
        $time = trim($_POST['event_time'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $org = trim($_POST['organizer'] ?? 'Yuvalay MakerSpace');
        $cap = intval($_POST['capacity'] ?? 0);
        $deadline = trim($_POST['registration_deadline'] ?? '');
        $banner = trim($_POST['banner_image'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80');

        try {
            $ins = $conn->prepare("INSERT INTO events (title, description, category, banner_image, event_date, event_time, venue, organizer, capacity, available_seats, registration_deadline) VALUES (:title, :desc, :cat, :banner, :date, :time, :venue, :org, :cap, :cap, :deadline)");
            $ins->execute([
                'title' => $title,
                'desc' => $desc,
                'cat' => $cat,
                'banner' => $banner,
                'date' => $date,
                'time' => $time,
                'venue' => $venue,
                'org' => $org,
                'cap' => $cap,
                'deadline' => $deadline
            ]);
            sendResponse('success', 'Event created successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'update-event':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat = trim($_POST['category'] ?? '');
        $date = trim($_POST['event_date'] ?? '');
        $time = trim($_POST['event_time'] ?? '');
        $venue = trim($_POST['venue'] ?? '');
        $org = trim($_POST['organizer'] ?? 'Yuvalay MakerSpace');
        $cap = intval($_POST['capacity'] ?? 0);
        $deadline = trim($_POST['registration_deadline'] ?? '');
        $banner = trim($_POST['banner_image'] ?? '');

        try {
            $stmt = $conn->prepare("SELECT capacity, available_seats FROM events WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $evt = $stmt->fetch();

            if (!$evt) sendResponse('error', 'Event not found');

            $diff = $cap - $evt['capacity'];
            $new_avail = max(0, $evt['available_seats'] + $diff);

            $upd = $conn->prepare("UPDATE events SET title = :title, description = :desc, category = :cat, banner_image = :banner, event_date = :date, event_time = :time, venue = :venue, organizer = :org, capacity = :cap, available_seats = :avail, registration_deadline = :deadline WHERE id = :id");
            $upd->execute([
                'title' => $title,
                'desc' => $desc,
                'cat' => $cat,
                'banner' => $banner,
                'date' => $date,
                'time' => $time,
                'venue' => $venue,
                'org' => $org,
                'cap' => $cap,
                'avail' => $new_avail,
                'deadline' => $deadline,
                'id' => $id
            ]);

            sendResponse('success', 'Event updated successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'delete-event':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM events WHERE id = :id");
            $stmt->execute(['id' => $id]);
            sendResponse('success', 'Event deleted');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'create-resource':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $cat = trim($_POST['category'] ?? '');
        $file = trim($_POST['file_url'] ?? '#');
        $thumb = trim($_POST['thumbnail_url'] ?? 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?auto=format&fit=crop&w=300&q=80');
        $author = trim($_POST['author'] ?? 'Admin');

        try {
            $stmt = $conn->prepare("INSERT INTO resources (title, description, category, file_url, thumbnail_url, author, upload_date) VALUES (:title, :desc, :cat, :file, :thumb, :author, :date)");
            $stmt->execute([
                'title' => $title,
                'desc' => $desc,
                'cat' => $cat,
                'file' => $file,
                'thumb' => $thumb,
                'author' => $author,
                'date' => date('Y-m-d')
            ]);
            sendResponse('success', 'Resource uploaded successfully');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'delete-resource':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM resources WHERE id = :id");
            $stmt->execute(['id' => $id]);
            sendResponse('success', 'Resource deleted');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'update-user-status':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? 'approved');

        if (!in_array($status, ['pending', 'approved', 'suspended'])) {
            sendResponse('error', 'Invalid status option');
        }

        try {
            $stmt = $conn->prepare("UPDATE users SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);
            sendResponse('success', 'User status updated to ' . $status);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'update-user-role':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        $role = trim($_POST['role'] ?? 'member');

        $allowed_roles = ['superadmin', 'admin', 'event_manager', 'resource_manager', 'editor', 'volunteer', 'mentor', 'member'];
        if (!in_array($role, $allowed_roles)) {
            sendResponse('error', 'Invalid role selection');
        }

        try {
            $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute(['role' => $role, 'id' => $id]);
            sendResponse('success', 'User role upgraded to ' . $role);
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'delete-testimonial':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $id = intval($_POST['id'] ?? 0);
        try {
            $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = :id");
            $stmt->execute(['id' => $id]);
            sendResponse('success', 'Testimonial deleted');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    case 'create-testimonial':
        requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendResponse('error', 'Invalid request method');
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $txt = trim($_POST['text'] ?? '');
        $rating = intval($_POST['rating'] ?? 5);
        $img = trim($_POST['image_url'] ?? 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80');

        try {
            $stmt = $conn->prepare("INSERT INTO testimonials (name, role, text, rating, image_url) VALUES (:name, :role, :txt, :rating, :img)");
            $stmt->execute([
                'name' => $name,
                'role' => $role,
                'txt' => $txt,
                'rating' => $rating,
                'img' => $img
            ]);
            sendResponse('success', 'Testimonial created');
        } catch (Exception $e) {
            sendResponse('error', $e->getMessage());
        }
        break;

    default:
        sendResponse('error', 'Action not recognized or endpoint does not exist');
        break;
}
