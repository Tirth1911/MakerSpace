<?php
/**
 * CMS Tables Migration and Seeding Script
 */
require_once __DIR__ . '/../config/db.php';

echo "=== Running Yuvalay Visual CMS Migrations ===\n";

try {
    $dbObj = new Database();
    $conn = $dbObj->getConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Alter Users Table Role Column
    echo "Altering users table role permissions...\n";
    $conn->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) DEFAULT 'member'");

    // Alter users table to add email_verified
    try {
        $conn->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0");
        echo "Added email_verified column to users table.\n";
    } catch (Exception $e) {
        // Column might already exist
        echo "users.email_verified column check completed.\n";
    }

    // Create email_verification table
    echo "Creating email_verification table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `email_verification` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `otp` VARCHAR(6) NOT NULL,
        `attempts` INT DEFAULT 0,
        `resends` INT DEFAULT 0,
        `last_resend_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create registration_rate_limit table
    echo "Creating registration_rate_limit table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `registration_rate_limit` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `ip_address` VARCHAR(45) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 2. Navigation Items Table
    echo "Creating navigation_items table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `navigation_items` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `label` VARCHAR(100) NOT NULL,
      `link` VARCHAR(255) NOT NULL,
      `display_order` INT DEFAULT 0,
      `parent_id` INT NULL,
      FOREIGN KEY (`parent_id`) REFERENCES `navigation_items`(`id`) ON DELETE CASCADE
    )");

    // 3. Footer Sections & Links
    echo "Creating footer_sections & footer_links tables...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `footer_sections` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(100) NOT NULL,
      `display_order` INT DEFAULT 0
    )");

    $conn->exec("CREATE TABLE IF NOT EXISTS `footer_links` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `section_id` INT NOT NULL,
      `label` VARCHAR(100) NOT NULL,
      `link` VARCHAR(255) NOT NULL,
      `display_order` INT DEFAULT 0,
      FOREIGN KEY (`section_id`) REFERENCES `footer_sections`(`id`) ON DELETE CASCADE
    )");

    // 4. Page Builder Blocks
    echo "Creating page_blocks table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `page_blocks` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `page_name` VARCHAR(100) NOT NULL,
      `block_type` VARCHAR(100) NOT NULL,
      `block_title` VARCHAR(255) NULL,
      `block_content` LONGTEXT NULL,
      `display_order` INT DEFAULT 0,
      `is_active` TINYINT DEFAULT 1
    )");

    // 5. Custom Event Form Fields
    echo "Creating event_form_fields table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `event_form_fields` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `event_id` INT NOT NULL,
      `field_name` VARCHAR(100) NOT NULL,
      `field_label` VARCHAR(255) NOT NULL,
      `field_type` ENUM('text', 'number', 'email', 'select', 'checkbox', 'textarea', 'file') DEFAULT 'text',
      `field_options` TEXT NULL,
      `is_required` TINYINT DEFAULT 0,
      `display_order` INT DEFAULT 0,
      FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
    )");

    // 6. Central Media Library
    echo "Creating media_library table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `media_library` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `file_name` VARCHAR(255) NOT NULL,
      `file_url` VARCHAR(255) NOT NULL,
      `file_type` VARCHAR(100) NOT NULL,
      `file_size` INT NOT NULL,
      `folder_name` VARCHAR(100) DEFAULT 'General',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Workspaces (what-we-do.php)
    echo "Creating workspaces table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `workspaces` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(200) NOT NULL,
      `description` TEXT NOT NULL,
      `icon` VARCHAR(100) NOT NULL,
      `image_url` VARCHAR(255) NOT NULL,
      `features_json` TEXT NOT NULL,
      `display_order` INT DEFAULT 0
    )");

    // 8. Certifications (what-we-do.php)
    echo "Creating certifications table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `certifications` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `code` VARCHAR(50) NOT NULL,
      `title` VARCHAR(200) NOT NULL,
      `description` TEXT NOT NULL,
      `display_order` INT DEFAULT 0
    )");

    // 9. Milestones (about.php)
    echo "Creating milestones table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `milestones` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `year` VARCHAR(50) NOT NULL,
      `title` VARCHAR(200) NOT NULL,
      `description` TEXT NOT NULL,
      `display_order` INT DEFAULT 0
    )");

    // 10. Team Members (about.php)
    echo "Creating team_members table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `team_members` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `name` VARCHAR(150) NOT NULL,
      `role` VARCHAR(150) NOT NULL,
      `image_url` VARCHAR(255) NOT NULL,
      `description` TEXT NOT NULL,
      `type` ENUM('team', 'mentor', 'volunteer') DEFAULT 'team',
      `display_order` INT DEFAULT 0
    )");

    // 11. SEO Meta
    echo "Creating seo_meta table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `seo_meta` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `page_name` VARCHAR(100) NOT NULL UNIQUE,
      `meta_title` VARCHAR(255) NULL,
      `meta_description` TEXT NULL,
      `keywords` TEXT NULL,
      `og_image` VARCHAR(255) NULL
    )");

    // 12. Custom Pages
    echo "Creating custom_pages table...\n";
    $conn->exec("CREATE TABLE IF NOT EXISTS `custom_pages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `title` VARCHAR(150) NOT NULL,
      `slug` VARCHAR(150) NOT NULL UNIQUE,
      `status` ENUM('draft', 'published') DEFAULT 'published',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Tables initialized successfully. Seeding default data...\n";

    // === SEED DATA ===

    // Navigation Header Items Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM navigation_items");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding navigation menu items...\n";
        $navs = [
            ['Home', '/index.php', 1],
            ['About Us', '/about.php', 2],
            ['What We Do', '/what-we-do.php', 3],
            ['Resources', '/resources.php', 4],
            ['Events', '/events.php', 5],
            ['Get Involved', '/get-involved.php', 6],
            ['Contact Us', '/contact.php', 7]
        ];
        $ins = $conn->prepare("INSERT INTO navigation_items (label, link, display_order) VALUES (?, ?, ?)");
        foreach ($navs as $n) {
            $ins->execute($n);
        }
    }

    // Footer Sections & Links Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM footer_sections");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding footer links structure...\n";
        // Section 1
        $conn->exec("INSERT INTO footer_sections (id, title, display_order) VALUES (1, 'Quick Links', 1)");
        $links1 = [
            ['Home', '/index.php', 1],
            ['About Us', '/about.php', 2],
            ['What We Do', '/what-we-do.php', 3],
            ['Resources', '/resources.php', 4],
            ['Events & Calendar', '/events.php', 5]
        ];
        $ins = $conn->prepare("INSERT INTO footer_links (section_id, label, link, display_order) VALUES (1, ?, ?, ?)");
        foreach ($links1 as $l) {
            $ins->execute($l);
        }

        // Section 2
        $conn->exec("INSERT INTO footer_sections (id, title, display_order) VALUES (2, 'Get Involved', 2)");
        $links2 = [
            ['Join as Volunteer', '/get-involved.php', 1],
            ['Join as Mentor', '/get-involved.php', 2],
            ['Become a Member', '/register.php', 3],
            ['Support Our Space', '/contact.php', 4]
        ];
        $ins = $conn->prepare("INSERT INTO footer_links (section_id, label, link, display_order) VALUES (2, ?, ?, ?)");
        foreach ($links2 as $l) {
            $ins->execute($l);
        }
    }

    // Workspaces Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM workspaces");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding workspaces list...\n";
        $workspaces = [
            [
                'Electronics & IoT Diagnostics Workbench',
                'Design, solder, test, and debug high-performance multi-layer circuit boards. Our diagnostics benches are fully loaded for analog and digital signal analysis.',
                'fa-solid fa-microchip',
                'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=600&q=80',
                json_encode([
                    '4-Channel Digital Storage Oscilloscopes (100MHz)',
                    'Programmable Bench DC Power Supplies (0-30V, 5A)',
                    'Solder Fume Extractors and Temperature Controlled Stations',
                    'Logic Analyzers and ESP32/Arduino Dev Kits'
                ]),
                1
            ],
            [
                '3D Printing & Fabrication Lab',
                'Generate structural mounts, customized gears, and custom prototype enclosures using industrial and desktop FDM additive manufacturing.',
                'fa-solid fa-print',
                'https://images.unsplash.com/photo-1615840287214-7fe58a8f3685?auto=format&fit=crop&w=600&q=80',
                json_encode([
                    'FDM 3D Printers with PLA, ABS, and PETG filaments',
                    'Precision Laser Cutter Engravers for acrylic & wood sheets',
                    'Slicing software setups (Cura, PrusaSlicer)',
                    'Post-processing workstation (sanding, structural chemical fills)'
                ]),
                2
            ],
            [
                'Robotics & Mechanical Assemblers',
                'Design, model, and assemble mobile robotic kinematics, pick-and-place arms, and drone motor arrays inside our mechanical bays.',
                'fa-solid fa-robot',
                'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=600&q=80',
                json_encode([
                    'Precision CNC milling machines for soft metals',
                    'Servo motor driver calibration modules',
                    'Battery management system (BMS) testing benches',
                    'Structural woodworking drills and handsaws'
                ]),
                3
            ]
        ];
        $ins = $conn->prepare("INSERT INTO workspaces (title, description, icon, image_url, features_json, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($workspaces as $w) {
            $ins->execute($w);
        }
    }

    // Certifications Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM certifications");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding training certifications...\n";
        $certs = [
            ['C1', 'Basic Electronics & Soldering', 'Validate your capability to read basic schematics, identify standard SMD passive components, and solder wires/pins without dry joints.', 1],
            ['C2', '3D Printer Safety & Operation', 'Learn slicer profiles, nozzle maintenance guidelines, heated bed calibration parameters, and structural filament options.', 2],
            ['C3', 'Multi-Layer PCB Routing Lead', 'Design dual and multi-layer circuit boards using KiCad, matching track impedances, bypassing electrical noise, and exporting Gerbers.', 3]
        ];
        $ins = $conn->prepare("INSERT INTO certifications (code, title, description, display_order) VALUES (?, ?, ?, ?)");
        foreach ($certs as $c) {
            $ins->execute($c);
        }
    }

    // Milestones Timeline Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM milestones");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding milestones timeline...\n";
        $miles = [
            ['2023', 'Inception', 'Conceived as Vadodara\'s first community rapid fabrication lab.', 1],
            ['2024', 'Launch', 'Officially opened the space with 3D printers, CNC routers, and basic electronics benches.', 2],
            ['2025', 'Expansion', 'Partnered with local engineering institutions and expanded active mentorship program.', 3],
            ['2026', 'Scale', 'Reaching 500+ active creators and launching advanced AI/IoT modules.', 4]
        ];
        $ins = $conn->prepare("INSERT INTO milestones (year, title, description, display_order) VALUES (?, ?, ?, ?)");
        foreach ($miles as $m) {
            $ins->execute($m);
        }
    }

    // Team Members Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM team_members");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding team, mentors, and volunteers directory...\n";
        $team = [
            // Core
            ['Rajesh Patel', 'Makerspace Lead Manager', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80', 'Coordinates safety accreditations, machine inventory, and daily operations.', 'team', 1],
            ['Nitin Vyas', 'Lead Hardware Architect', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?auto=format&fit=crop&w=150&q=80', 'Expert consultant in multi-layer PCB routing and high-frequency trace testing.', 'team', 2],
            ['Aarav Shah', 'Embedded Software Mentor', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=150&q=80', 'Firmware engineer guiding students on ESP32, RTOS, and basic OpenCV nodes.', 'team', 3],
            
            // Mentors
            ['Dr. Anil Vyas', 'IoT & Wireless Comms Mentor', '', 'Weekly schemas review consultant.', 'mentor', 1],
            ['Pankaj Shah', 'Embedded Systems Architect', '', 'Lead Embedded Researcher.', 'mentor', 2],
            ['Sagar Mehta', 'Robotics Kinematics Lead', '', 'Industrial arms modeling guidance.', 'mentor', 3],
            ['Megha Patel', 'Rapid Manufacturing Spec', '', 'Weekly CNC / print reviews consultant.', 'mentor', 4],
            
            // Volunteers
            ['Kunal Rawat', '3D Printing Support', '', 'Assisting in structural slicer setups.', 'volunteer', 1],
            ['Rohan Dave', 'Solder Station Helper', '', 'Tool safety checks desk.', 'volunteer', 2],
            ['Sonia Sen', 'Community Outreach', '', 'Coordinating show-and-tell meetups.', 'volunteer', 3],
            ['Devendra Gohil', 'CNC Router Assistant', '', 'Assisting in woodworking designs.', 'volunteer', 4]
        ];
        $ins = $conn->prepare("INSERT INTO team_members (name, role, image_url, description, type, display_order) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($team as $t) {
            $ins->execute($t);
        }
    }

    // SEO Meta Seed
    $stmt = $conn->query("SELECT COUNT(*) FROM seo_meta");
    if ($stmt->fetchColumn() == 0) {
        echo "Seeding page meta fields...\n";
        $meta = [
            ['home', 'Yuvalay MakerSpace Vadodara - Empowering Makers', 'A collaborative space for innovators, students, creators and communities to access tools, mentorship and resources.', 'makerspace, vadodara, 3d printing, IoT, robotics, rapid prototyping, startup incubator', ''],
            ['about', 'About Us - Mission, Vision, and Leadership | Yuvalay MakerSpace', 'Founded in Vadodara, Yuvalay MakerSpace bridges the gap between academic theory and practical hardware engineering.', 'mission, vision, story, baroda, makerspace timeline', ''],
            ['what-we-do', 'What We Do - Tools, Labs and Training Programs | Yuvalay MakerSpace', 'Providing the tools, modules, hardware diagnostics equipment, and certifications required to scale engineering concepts.', 'workspaces, pcb fabrication, electronics training, 3d printer calibration', ''],
            ['resources', 'Searchable Technical Guides and Resource Manuals | Yuvalay MakerSpace', 'Find manuals and tutorials covering Arduino, IoT nodes, OpenCV tracking, and KiCad designs.', 'safety manual, technical guides, download library', ''],
            ['events', 'Events & Calendar - Workshops, Hackathons, and Training | Yuvalay MakerSpace', 'Register for monthly meetups, hackathons, and hardware diagnostics workshops.', 'events, calendar, RSVP, arduino training', ''],
            ['contact', 'Contact Us - Office Address, FAQs and Form | Yuvalay MakerSpace', 'Get in touch with Yuvalay MakerSpace Vadodara. View office timing, FAQ sheets, and maps.', 'office address, email, phone number, working hours', '']
        ];
        $ins = $conn->prepare("INSERT INTO seo_meta (page_name, meta_title, meta_description, keywords, og_image) VALUES (?, ?, ?, ?, ?)");
        foreach ($meta as $m) {
            $ins->execute($m);
        }
    }

    // Design branding, typography & layout settings seed
    echo "Seeding visual design configs...\n";
    $designs = [
        'design_primary_color' => ['#8DC63F', 'Design'],
        'design_secondary_color' => ['#6DA52A', 'Design'],
        'design_accent_color' => ['#3B82F6', 'Design'],
        'design_bg_color' => ['#FFFFFF', 'Design'],
        'design_card_bg_color' => ['#F5F6F8', 'Design'],
        'design_text_color' => ['#111111', 'Design'],
        'design_font_family' => ["'Inter', sans-serif", 'Design'],
        'design_font_size' => ['16px', 'Design'],
        'design_border_radius' => ['16px', 'Design'],
        'design_sticky_header' => ['1', 'Design'],
        'design_favicon_url' => ['/favicon.ico', 'Design'],
        'design_site_name' => ['Yuvalay MakerSpace', 'Design'],
        'design_tagline' => ['Empowering Makers. Building a Better Tomorrow.', 'Design']
    ];
    $ins = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value, category) VALUES (:key, :val, :cat) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($designs as $key => $info) {
        $ins->execute(['key' => $key, 'val' => $info[0], 'cat' => $info[1]]);
    }

    echo "\n=== Migrations completed successfully! ===\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
