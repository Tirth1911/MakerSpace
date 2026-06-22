<?php
/**
 * Database Setup & Seeding Utility
 */

header("Content-Type: text/plain");
echo "=== Yuvalay MakerSpace Database Setup ===\n";

$host = "127.0.0.1";
$port = "3307";
$username = "root";
$password = "";
$db_name = "yuvalay_db";

try {
    // 1. Connect to MySQL without specifying database to create it
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating database if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 2. Select database
    $pdo->exec("USE `$db_name`");
    echo "Database selected successfully.\n";
    
    // 3. Load schema.sql and split by semicolon
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found at $schemaFile");
    }
    
    echo "Executing schema.sql...\n";
    $sql = file_get_contents($schemaFile);
    // Remove comments
    $sql = preg_replace('/--.*\n/', '', $sql);
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $pdo->exec($trimmed);
        }
    }
    echo "Schema executed successfully. Tables created.\n";

    // Disable Foreign Key checks for clean truncation during seed
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 4. Seed Super Admin User
    echo "Seeding default administrator...\n";
    $adminEmail = "admin@yuvalay.org";
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $adminEmail]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        $pwdHash = password_hash("admin123", PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (name, email, mobile, password_hash, role, status) VALUES (:name, :email, :mobile, :pwd, 'admin', 'approved')");
        $ins->execute([
            'name' => 'Yuvalay Admin',
            'email' => $adminEmail,
            'mobile' => '9876543210',
            'pwd' => $pwdHash
        ]);
        echo "Admin account created: email = admin@yuvalay.org, password = admin123\n";
    } else {
        echo "Admin account already exists.\n";
    }

    // 5. Seed site settings
    echo "Seeding CMS settings copy...\n";
    $settings = [
        // Home
        'hero_title' => ['EMPOWERING MAKERS. BUILDING A BETTER TOMORROW.', 'Home'],
        'hero_description' => ['A collaborative space for innovators, students, creators and communities to access tools, mentorship and resources for building impactful solutions.', 'Home'],
        'stats_members' => ['500+', 'Home'],
        'stats_tools' => ['100+', 'Home'],
        'stats_events' => ['50+', 'Home'],
        'stats_volunteers' => ['20+', 'Home'],
        
        // About
        'about_mission' => ['To democratize innovation by providing affordable access to manufacturing equipment, rapid prototyping workshops, and multidisciplinary community events.', 'About'],
        'about_vision' => ['To establish a local model of community-driven innovation where local talents create globally competitive hardware solutions.', 'About'],
        'about_story' => ['Founded in Vadodara, Yuvalay MakerSpace bridges the gap between academic theory and practical hardware engineering. We provide hands-on experience, tool checkouts, and mentors to help innovators create working prototypes.', 'About'],
        'about_timeline' => [json_encode([
            ['year' => '2023', 'title' => 'Inception', 'desc' => 'Conceived as Vadodara\'s first community rapid fabrication lab.'],
            ['year' => '2024', 'title' => 'Launch', 'desc' => 'Officially opened the space with 3D printers, CNC routers, and basic electronics benches.'],
            ['year' => '2025', 'title' => 'Expansion', 'desc' => 'Partnered with local engineering institutions and expanded active mentorship program.'],
            ['year' => '2026', 'title' => 'Scale', 'desc' => 'Reaching 500+ active creators and launching advanced AI/IoT modules.']
        ]), 'About'],
        
        // Contact
        'contact_phone' => ['+91 98765 43210', 'Contact'],
        'contact_email' => ['contact@yuvalaymakerspace.org', 'Contact'],
        'contact_address' => ['Yuvalay MakerSpace, 3rd Floor, Technology Tower, Alkapuri, Vadodara, Gujarat - 390007', 'Contact'],
        'contact_map' => ['https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3691.012351280385!2d73.1895781!3d22.3153664!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395fc88dfc5c7865%3A0xa6ebbb363a0d5b!2sVadodara%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin', 'Contact'],
        'contact_hours' => ['Tuesday - Sunday: 10:00 AM - 08:00 PM (Monday Closed)', 'Contact'],
        
        // Social Links
        'social_facebook' => ['https://facebook.com/yuvalaymakerspace', 'Social'],
        'social_instagram' => ['https://instagram.com/yuvalaymakerspace', 'Social'],
        'social_linkedin' => ['https://linkedin.com/company/yuvalaymakerspace', 'Social'],

        // SMTP Settings
        'smtp_host' => ['smtp.gmail.com', 'SMTP'],
        'smtp_port' => ['587', 'SMTP'],
        'smtp_secure' => ['tls', 'SMTP'],
        'smtp_username' => ['', 'SMTP'],
        'smtp_password' => ['', 'SMTP'],

        // Google OAuth Settings
        'google_client_id' => ['', 'Google'],
        'google_client_secret' => ['', 'Google'],
    ];

    $insSetting = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value, category) VALUES (:key, :val, :cat) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($settings as $key => $info) {
        $insSetting->execute([
            'key' => $key,
            'val' => $info[0],
            'cat' => $info[1]
        ]);
    }
    echo "Site settings seeded.\n";

    // 6. Seed Homepage Hero slides
    echo "Seeding hero slideshow images...\n";
    $pdo->exec("TRUNCATE TABLE homepage_slides");
    $slides = [
        ['image_url' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=1200&q=80', 'title' => 'Advanced Robotics Lab', 'subtitle' => 'Build high-performance automation hardware with expert guidance.', 'display_order' => 1],
        ['image_url' => 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=1200&q=80', 'title' => 'Electronics Workbench', 'subtitle' => 'Design, solder, and debug custom circuit boards.', 'display_order' => 2],
        ['image_url' => 'https://images.unsplash.com/photo-1615840287214-7fe58a8f3685?auto=format&fit=crop&w=1200&q=80', 'title' => '3D Printing Zone', 'subtitle' => 'Turn digital CAD files into tangible objects in hours.', 'display_order' => 3],
        ['image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80', 'title' => 'Student Collaborations', 'subtitle' => 'Share ideas, learn together, and build impactful systems.', 'display_order' => 4],
    ];
    $insSlide = $pdo->prepare("INSERT INTO homepage_slides (image_url, title, subtitle, display_order) VALUES (:img, :title, :sub, :ord)");
    foreach ($slides as $slide) {
        $insSlide->execute([
            'img' => $slide['image_url'],
            'title' => $slide['title'],
            'sub' => $slide['subtitle'],
            'ord' => $slide['display_order']
        ]);
    }
    echo "Homepage slides seeded.\n";

    // 7. Seed Testimonials
    echo "Seeding testimonials...\n";
    $pdo->exec("TRUNCATE TABLE testimonials");
    $testimonials = [
        ['name' => 'Amit Sharma', 'role' => 'Mechanical Engineering Student', 'text' => 'Yuvalay MakerSpace changed my academic path. I went from reading diagrams to printing actual functional gearboxes for our formula student team.', 'rating' => 5, 'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80'],
        ['name' => 'Priya Patel', 'role' => 'IoT Hardware Startup Founder', 'text' => 'We bootstrapped our entire smart-agriculture device prototyping at Yuvalay. The oscilloscope benches and PCB reflow tools saved us months of development expenses.', 'rating' => 5, 'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80'],
        ['name' => 'Vikram Mehta', 'role' => 'Maker Volunteer', 'text' => 'Volunteering here lets me mentor young minds. The collaborative energy is contagious. It is the best innovation hub in Gujarat.', 'rating' => 5, 'image_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=120&q=80']
    ];
    $insTest = $pdo->prepare("INSERT INTO testimonials (name, role, text, rating, image_url) VALUES (:name, :role, :txt, :rating, :img)");
    foreach ($testimonials as $t) {
        $insTest->execute([
            'name' => $t['name'],
            'role' => $t['role'],
            'txt' => $t['text'],
            'rating' => $t['rating'],
            'img' => $t['image_url']
        ]);
    }
    echo "Testimonials seeded.\n";

    // 8. Seed Events
    echo "Seeding events...\n";
    $pdo->exec("TRUNCATE TABLE events");
    $events = [
        [
            'title' => 'Arduino Prototyping Workshop',
            'description' => 'A hands-on introductory workshop covering digital pins, basic sensors, relays, and motor drivers using Arduino Uno.',
            'category' => 'Workshops',
            'banner_image' => 'https://images.unsplash.com/photo-1608248597481-496100c80836?auto=format&fit=crop&w=600&q=80',
            'event_date' => date('Y-m-d', strtotime('+3 days')),
            'event_time' => '10:00:00',
            'venue' => 'Electronics Lab, Floor 3',
            'organizer' => 'Pankaj Shah (Senior Mentor)',
            'capacity' => 20,
            'available_seats' => 20,
            'registration_deadline' => date('Y-m-d H:i:s', strtotime('+2 days')),
            'status' => 'upcoming',
            'agenda' => "10:00 AM - Introductions & Microcontroller basics\n11:00 AM - Interfacing LEDs and PWM analog simulation\n12:30 PM - Lunch Break\n01:30 PM - Sensor reading (Ultrasonic, LDR, DHT11)\n03:30 PM - Building autonomous smart light project",
            'speakers' => 'Pankaj Shah, Embedded Lead Researcher at Yuvalay.',
            'requirements' => 'Laptop with Arduino IDE pre-installed. Development boards and components will be provided for use.',
            'google_map_url' => 'https://maps.google.com'
        ],
        [
            'title' => 'Gujarat IoT Innovation Hackathon',
            'description' => 'A 36-hour sprint to build connected hardware solutions tackling rural energy efficiency and micro-irrigation automations.',
            'category' => 'Hackathons',
            'banner_image' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80',
            'event_date' => date('Y-m-d', strtotime('+10 days')),
            'event_time' => '09:00:00',
            'venue' => 'Main Innovation Hall & Makerspace Patio',
            'organizer' => 'Yuvalay Board of Directors',
            'capacity' => 50,
            'available_seats' => 50,
            'registration_deadline' => date('Y-m-d H:i:s', strtotime('+8 days')),
            'status' => 'upcoming',
            'agenda' => "Day 1 09:00 AM - Team formations & API guidelines\nDay 1 12:00 PM - Coding & fabrication starts\nDay 2 12:00 PM - Midpoint mentor reviews\nDay 3 04:00 PM - Final pitch and demos before jury panel",
            'speakers' => 'Dr. Anil Vyas (Chief Guest), Rajesh Patel (Lead Manager).',
            'requirements' => 'Functional team (2-4 members), custom microcontrollers, hardware sensors.',
            'google_map_url' => 'https://maps.google.com'
        ],
        [
            'title' => 'KiCad Multi-Layer PCB Routing',
            'description' => 'Advanced training program for double-sided and multi-layer PCB design covering trace geometry, bypass caps, and ground planes.',
            'category' => 'Training Programs',
            'banner_image' => 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=600&q=80',
            'event_date' => date('Y-m-d', strtotime('+15 days')),
            'event_time' => '14:00:00',
            'venue' => 'Electronics Workbench, Floor 3',
            'organizer' => 'Nitin Vyas (Hardware Architect)',
            'capacity' => 15,
            'available_seats' => 15,
            'registration_deadline' => date('Y-m-d H:i:s', strtotime('+12 days')),
            'status' => 'upcoming',
            'agenda' => "Session 1: Schematic capture & Netlist rules\nSession 2: Footprint associations\nSession 3: Track impedance matching & Ground Plane fill\nSession 4: Gerber exports & JLCPCB specifications",
            'speakers' => 'Nitin Vyas, PCB Design Consultant.',
            'requirements' => 'Laptop with KiCad 7.x installed, basic electronics knowledge.',
            'google_map_url' => 'https://maps.google.com'
        ],
        [
            'title' => 'Monthly Maker Meetup & Show-and-Tell',
            'description' => 'A casual community meetup to share what you have built, get feedback, and network with creators, students, and engineers.',
            'category' => 'Meetups',
            'banner_image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80',
            'event_date' => date('Y-m-d', strtotime('+18 days')),
            'event_time' => '16:00:00',
            'venue' => 'Makerspace Patio',
            'organizer' => 'Maker Council Volunteers',
            'capacity' => 80,
            'available_seats' => 80,
            'registration_deadline' => date('Y-m-d H:i:s', strtotime('+17 days')),
            'status' => 'upcoming',
            'agenda' => "04:00 PM - Welcome & Networking drinks\n04:30 PM - 5-minute lightning project demos\n05:30 PM - Open discussion & feedback circles",
            'speakers' => 'Community Makers.',
            'requirements' => 'Bring your physical prototype if you want to demo it!',
            'google_map_url' => 'https://maps.google.com'
        ]
    ];

    $insEvent = $pdo->prepare("INSERT INTO events (title, description, category, banner_image, event_date, event_time, venue, organizer, capacity, available_seats, registration_deadline, status, agenda, speakers, requirements, google_map_url) VALUES (:title, :description, :cat, :banner, :date, :time, :venue, :org, :cap, :avail, :deadline, :status, :agenda, :speakers, :req, :gmap)");
    foreach ($events as $evt) {
        $insEvent->execute([
            'title' => $evt['title'],
            'description' => $evt['description'],
            'cat' => $evt['category'],
            'banner' => $evt['banner_image'],
            'date' => $evt['event_date'],
            'time' => $evt['event_time'],
            'venue' => $evt['venue'],
            'org' => $evt['organizer'],
            'cap' => $evt['capacity'],
            'avail' => $evt['available_seats'],
            'deadline' => $evt['registration_deadline'],
            'status' => $evt['status'],
            'agenda' => $evt['agenda'],
            'speakers' => $evt['speakers'],
            'req' => $evt['requirements'],
            'gmap' => $evt['google_map_url']
        ]);
    }
    echo "Events seeded.\n";

    // 9. Seed Resources
    echo "Seeding resources...\n";
    $pdo->exec("TRUNCATE TABLE resources");
    $resources = [
        ['title' => 'MakerSpace Safety Manual v2', 'description' => 'Essential laboratory safety guidelines for operating CNC routers, 3D printers, and soldering stations.', 'category' => '3D Printing', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?auto=format&fit=crop&w=300&q=80', 'author' => 'Rajesh Patel', 'upload_date' => '2026-05-10'],
        ['title' => 'Beginners Guide to Arduino Microcontrollers', 'description' => 'Complete starting handbook covering pin configurations, code structures, and simple sensory outputs.', 'category' => 'Robotics', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1608248597481-496100c80836?auto=format&fit=crop&w=300&q=80', 'author' => 'Pankaj Shah', 'upload_date' => '2026-05-15'],
        ['title' => 'KiCad 7 Schematic Capture Best Practices', 'description' => 'A guide to layout structures, hierarchical sheets, and rule checking before routing high-speed traces.', 'category' => 'Electronics', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=300&q=80', 'author' => 'Nitin Vyas', 'upload_date' => '2026-06-01'],
        ['title' => 'Introduction to Python & OpenCV for Object Tracking', 'description' => 'Source code and guide to interfacing ESP32-CAM with Python scripts to capture and isolate colored coordinates.', 'category' => 'Programming', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=300&q=80', 'author' => 'Aarav Shah', 'upload_date' => '2026-06-18']
    ];

    $insResource = $pdo->prepare("INSERT INTO resources (title, description, category, file_url, thumbnail_url, author, upload_date) VALUES (:title, :desc, :cat, :file, :thumb, :author, :date)");
    foreach ($resources as $res) {
        $insResource->execute([
            'title' => $res['title'],
            'desc' => $res['description'],
            'cat' => $res['category'],
            'file' => $res['file_url'],
            'thumb' => $res['thumbnail_url'],
            'author' => $res['author'],
            'date' => $res['upload_date']
        ]);
    }
    echo "Resources seeded.\n";

    // 10. Seed Gallery
    echo "Seeding gallery media...\n";
    $pdo->exec("TRUNCATE TABLE gallery");
    $gallery = [
        ['media_url' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=600&q=80', 'media_type' => 'image', 'caption' => 'Electronics diagnostics workbench with multimeters & signal generators.', 'category' => 'Electronics'],
        ['media_url' => 'https://images.unsplash.com/photo-1615840287214-7fe58a8f3685?auto=format&fit=crop&w=600&q=80', 'media_type' => 'image', 'caption' => 'FDM 3D printers rendering custom prototyping enclosures.', 'category' => '3D Printing'],
        ['media_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80', 'media_type' => 'image', 'caption' => 'Collaboration table where hackers pitch ideas during Gujarat Hackathon.', 'category' => 'General'],
        ['media_url' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80', 'media_type' => 'image', 'caption' => 'Mentorship circle mapping out logic for rural irrigation sensors.', 'category' => 'Robotics']
    ];

    $insGal = $pdo->prepare("INSERT INTO gallery (media_url, media_type, caption, category) VALUES (:url, :type, :cap, :cat)");
    foreach ($gallery as $g) {
        $insGal->execute([
            'url' => $g['media_url'],
            'type' => $g['media_type'],
            'cap' => $g['caption'],
            'cat' => $g['category']
        ]);
    }
    echo "Gallery seeded.\n";

    // Re-enable Foreign Key Checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Run CMS Migrations and Seeding
    echo "\nRunning CMS tables migrations & seeds...\n";
    require_once __DIR__ . '/database/migrations.php';

    echo "\nDatabase initialized successfully!\n";
    echo "Default Super Admin Credentials:\n";
    echo "Email: admin@yuvalay.org\n";
    echo "Password: admin123\n";

} catch (PDOException $e) {
    // Re-enable in case of exception too
    if (isset($pdo)) {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "PDO Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
