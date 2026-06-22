<?php
/**
 * Global Header Template
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Initialize Database connection to fetch CMS Settings
$dbObj = new Database();
$conn = $dbObj->getConnection();

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

// Global configurations loaded dynamically
$site_name = getSetting('design_site_name', 'Yuvalay MakerSpace');
$contact_phone = getSetting('contact_phone', '+91 98765 43210');
$contact_email = getSetting('contact_email', 'info@yuvalaymakerspace.org');
$contact_address = getSetting('contact_address', 'Yuvalay MakerSpace, Vadodara, Gujarat');

if (isset($custom_page_name)) {
    $page_name = $custom_page_name;
} else {
    $page_name = basename($_SERVER['PHP_SELF'], '.php');
    if ($page_name == 'index') $page_name = 'home';
}

// Dynamic SEO tags loaded from DB
$meta_title = "$site_name | Empowering Makers";
$meta_description = "A collaborative space for innovators, students, creators and communities.";
$keywords = "makerspace, vadodara, 3d printing";
$og_image = "";

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM seo_meta WHERE page_name = :page");
        $stmt->execute(['page' => $page_name]);
        $seo = $stmt->fetch();
        if ($seo) {
            $meta_title = $seo['meta_title'];
            $meta_description = $seo['meta_description'];
            $keywords = $seo['keywords'];
            $og_image = $seo['og_image'];
        }
    } catch (Exception $e) {}
}

// User helper variables & Roles Mapping
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : '';
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';

// Permissions checks
$is_admin = $is_logged_in && in_array($user_role, ['superadmin', 'admin', 'event_manager', 'resource_manager']);
$can_edit = $is_logged_in && in_array($user_role, ['superadmin', 'admin', 'editor']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($meta_title); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
  <meta name="keywords" content="<?php echo htmlspecialchars($keywords); ?>">
  <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars(getSetting('design_favicon_url', '/favicon.ico')); ?>">
  
  <!-- SEO Tags -->
  <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
  <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
  <?php if (!empty($og_image)): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
  <?php endif; ?>
  <meta property="og:type" content="website">
  <meta name="twitter:card" content="summary_large_image">
  
  <!-- CSS assets: Tailwind (CDN) + FontAwesome (CDN) + Swiper CSS (CDN) + FullCalendar CSS (CDN) + Custom style.css -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brandGreen: '<?php echo getSetting("design_primary_color", "#8DC63F"); ?>',
            brandDarkGreen: '<?php echo getSetting("design_secondary_color", "#6DA52A"); ?>',
            brandBlack: '<?php echo getSetting("design_bg_color", "#FFFFFF"); ?>',
            brandDarkGray: '<?php echo getSetting("design_card_bg_color", "#F5F6F8"); ?>',
            brandLightGray: '<?php echo getSetting("design_text_color", "#111111"); ?>',
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" />
  <link rel="stylesheet" href="/public/css/style.css?v=<?php echo time(); ?>">
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Dynamic Styling Overrides based on CMS Design settings -->
  <style>
    :root {
      --color-primary: <?php echo getSetting('design_primary_color', '#8DC63F'); ?>;
      --color-primary-dark: <?php echo getSetting('design_secondary_color', '#6DA52A'); ?>;
      --color-accent: <?php echo getSetting('design_accent_color', '#3B82F6'); ?>;
      --color-bg-dark: <?php echo getSetting('design_bg_color', '#FFFFFF'); ?>;
      --color-card-dark: <?php echo getSetting('design_card_bg_color', '#F5F6F8'); ?>;
      --color-text-light: <?php echo getSetting('design_text_color', '#111111'); ?>;
      --border-radius: <?php echo getSetting('design_border_radius', '16px'); ?>;
      font-family: <?php echo getSetting('design_font_family', "'Inter', sans-serif"); ?> !important;
    }
    html, body {
      font-family: <?php echo getSetting('design_font_family', "'Inter', sans-serif"); ?> !important;
      background-color: var(--color-bg-dark) !important;
      color: var(--color-text-light) !important;
    }
    .rounded-2xl, .rounded-3xl, .rounded-\[32px\], .rounded-\[40px\], .rounded-xl {
      border-radius: var(--border-radius) !important;
    }
  </style>
</head>
<body class="bg-brandBlack text-brandLightGray antialiased min-h-screen flex flex-col <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode'] === true && $can_edit) ? 'edit-mode-on' : ''; ?>">

  <!-- Navbar (Sticky layout configurable) -->
  <nav class="<?php echo getSetting('design_sticky_header', '1') == '1' ? 'sticky top-0' : 'relative'; ?> z-50 glass border-b border-white/5 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        
        <!-- Logo & Brand Name -->
        <div class="flex-shrink-0">
          <a href="/index.php" class="flex items-center group" data-cms-element="logo">
            <img src="<?php echo htmlspecialchars(getSetting('design_logo_url', '/public/images/logo-light.png')); ?>" alt="Logo" class="h-14 w-auto object-contain group-hover:scale-102 transition-transform duration-300">
          </a>
        </div>

        <!-- Desktop Navigation Links loaded dynamically -->
        <div class="hidden md:flex items-center gap-0.5 xl:gap-1 flex-shrink-0">
          <?php
          try {
              $stmt = $conn->prepare("SELECT * FROM navigation_items ORDER BY display_order ASC");
              $stmt->execute();
              $nav_items = $stmt->fetchAll();
              foreach ($nav_items as $item) {
                  $is_active = (basename($_SERVER['PHP_SELF']) == basename($item['link'])) || 
                              ($page_name == 'home' && $item['link'] == '/index.php');
                  $active_class = $is_active ? 'text-brandGreen bg-white/5 font-bold' : 'text-gray-600 hover:text-black hover:bg-white/5';
                  echo '<a href="' . htmlspecialchars($item['link']) . '" data-cms-item-id="' . $item['id'] . '" class="px-2 xl:px-3 py-1.5 rounded-lg text-xs xl:text-sm font-medium transition-all duration-200 ' . $active_class . '">' . htmlspecialchars($item['label']) . '</a>';
              }
          } catch(Exception $e) {
              // fallback if tables fail
          }
          ?>
        </div>

        <!-- Action / Authenticated Buttons -->
        <div class="hidden md:flex items-center gap-1.5 xl:gap-2 flex-shrink-0">
          <?php if ($is_logged_in): ?>
            
            <a href="/my-registrations.php" class="px-2 xl:px-2.5 py-1 rounded-lg text-xs xl:text-[13px] font-medium text-gray-600 hover:text-black hover:bg-white/5 flex items-center gap-1.5" title="My Registrations">
              <i class="fa-solid fa-ticket-simple text-brandGreen"></i> <span class="hidden 2xl:inline">Registrations</span>
            </a>

            <?php if ($is_admin): ?>
              <!-- Admin Dash button -->
              <a href="/admin.php" class="px-2 xl:px-2.5 py-1 rounded-lg text-xs xl:text-[13px] font-semibold text-gray-800 bg-gray-100 hover:bg-gray-200 border border-gray-200 hover:text-gray-900 transition-colors duration-200 flex items-center gap-1.5" title="Dashboard">
                <i class="fa-solid fa-chart-line text-brandGreen"></i> <span class="hidden 2xl:inline">Dashboard</span>
              </a>
            <?php endif; ?>

            <?php if ($can_edit): ?>
              <!-- EDIT MODE TOGGLE -->
              <button onclick="toggleEditMode()" id="editModeNavbarBtn" class="px-2 xl:px-2.5 py-1 rounded-lg text-xs xl:text-[13px] font-semibold flex items-center gap-1.5 transition-all duration-200 <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode'] === true) ? 'bg-brandGreen text-white shadow-md shadow-brandGreen/25' : 'bg-brandDarkGray text-brandGreen border border-brandGreen/30 hover:bg-brandGreen/10'; ?>" title="Toggle Edit Mode">
                <i class="fa-solid fa-pen-to-square"></i> <span class="hidden 2xl:inline">Edit: </span><span class="font-bold"><?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode'] === true) ? 'ON' : 'OFF'; ?></span>
              </button>
            <?php endif; ?>

            <!-- Logout -->
            <a href="/api.php?action=logout" class="p-2 rounded-lg text-gray-400 hover:text-red-400 hover:bg-red-500/10 transition-colors duration-200" title="Logout">
              <i class="fa-solid fa-right-from-bracket text-base"></i>
            </a>

          <?php else: ?>
            <a href="/login.php" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-black transition-colors duration-200">Login</a>
            <a href="/register.php" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-brandGreen text-white hover:bg-brandDarkGreen shadow-lg shadow-brandGreen/15 hover:shadow-brandGreen/25 hover:-translate-y-0.5 transition-all duration-200">Register</a>
          <?php endif; ?>
        </div>

        <!-- Mobile Menu Trigger -->
        <div class="flex items-center md:hidden gap-3">
          <?php if ($can_edit): ?>
            <button onclick="toggleEditMode()" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-brandGreen text-white flex items-center gap-1">
              <i class="fa-solid fa-pen"></i> CMS: <?php echo (isset($_SESSION['edit_mode']) && $_SESSION['edit_mode'] === true) ? 'ON' : 'OFF'; ?>
            </button>
          <?php endif; ?>
          <button id="mobileMenuBtn" class="p-2 rounded-lg text-gray-400 hover:text-white hover:bg-white/5 focus:outline-none transition-colors">
            <i class="fa-solid fa-bars text-2xl"></i>
          </button>
        </div>

      </div>
    </div>

    <!-- Mobile Drawer Dropdown -->
    <div id="mobileMenuDrawer" class="hidden md:hidden border-t border-white/5 bg-brandBlack/95 backdrop-blur-xl transition-all duration-300">
      <div class="px-2 pt-3 pb-6 space-y-1 sm:px-3 text-center">
        <?php
        if (isset($nav_items)) {
            foreach ($nav_items as $item) {
                echo '<a href="' . htmlspecialchars($item['link']) . '" class="block px-4 py-3 rounded-lg text-base font-semibold text-gray-600 hover:text-brandGreen hover:bg-white/5 transition-all">' . htmlspecialchars($item['label']) . '</a>';
            }
        }
        ?>
        <div class="border-t border-white/10 my-4"></div>
        <?php if ($is_logged_in): ?>
          <a href="/my-registrations.php" class="block px-4 py-3 rounded-lg text-base font-semibold text-brandGreen hover:bg-white/5"><i class="fa-solid fa-ticket-simple mr-2"></i>My Registrations</a>
          <?php if ($is_admin): ?>
            <a href="/admin.php" class="block px-4 py-3 rounded-lg text-base font-semibold text-white bg-brandGreen/20 hover:bg-brandGreen/30"><i class="fa-solid fa-chart-line mr-2"></i>Admin Dashboard</a>
          <?php endif; ?>
          <a href="/api.php?action=logout" class="block px-4 py-3 rounded-lg text-base font-semibold text-red-400 hover:bg-red-500/10"><i class="fa-solid fa-right-from-bracket mr-2"></i>Logout</a>
        <?php else: ?>
          <a href="/login.php" class="block px-4 py-3 rounded-lg text-base font-semibold text-gray-600 hover:text-black transition-all">Login</a>
          <a href="/register.php" class="block mx-4 my-2 py-3 rounded-xl text-base font-bold bg-brandGreen text-white hover:bg-brandDarkGreen shadow-lg shadow-brandGreen/15">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Main Container -->
  <main class="flex-grow">
