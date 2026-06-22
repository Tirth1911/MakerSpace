<?php
/**
 * Dynamic Custom Page Template
 */
require_once __DIR__ . '/config/db.php';

$slug = trim($_GET['slug'] ?? $_GET['p'] ?? '');
if (empty($slug)) {
    header("Location: /index.php");
    exit;
}

$dbObj = new Database();
$conn = $dbObj->getConnection();

// Fetch page from custom_pages
$stmt = $conn->prepare("SELECT * FROM custom_pages WHERE slug = :slug AND status = 'published'");
$stmt->execute(['slug' => $slug]);
$custom_page = $stmt->fetch();

if (!$custom_page) {
    // If admin is logged in, show draft as well
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $is_admin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['superadmin', 'admin', 'event_manager', 'resource_manager']);
    if ($is_admin) {
        $stmt = $conn->prepare("SELECT * FROM custom_pages WHERE slug = :slug");
        $stmt->execute(['slug' => $slug]);
        $custom_page = $stmt->fetch();
    }
}

if (!$custom_page) {
    // Page not found -> Render nice 404 inside layout
    $custom_page_name = '404';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <section class="py-32 flex items-center justify-center bg-brandBlack">
      <div class="max-w-md mx-auto text-center space-y-6 px-4">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-500/10 border border-red-500/25 text-red-500 mb-2">
          <i class="fa-solid fa-triangle-exclamation text-3xl"></i>
        </div>
        <h1 class="text-4xl font-extrabold font-['Outfit'] text-white">Page Not Found</h1>
        <p class="text-gray-400 text-sm leading-relaxed">
          The page you are looking for does not exist or has been unpublished.
        </p>
        <div class="pt-4">
          <a href="/index.php" class="px-5 py-3 rounded-xl bg-brandGreen text-brandBlack font-bold text-xs inline-flex items-center gap-1.5 hover:bg-[#73a11c] transition-all">
            <i class="fa-solid fa-house"></i> Back to Home
          </a>
        </div>
      </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Set custom page override for header meta
$custom_page_name = $slug;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero/Banner Section for Custom Page -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-1/4 left-1/10 w-72 h-72 rounded-full bg-brandGreen/5 filter blur-[80px] pointer-events-none"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10 space-y-4">
    <span class="text-xs font-bold tracking-widest text-brandGreen uppercase">EXPLORE</span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight text-white"><?php echo htmlspecialchars($custom_page['title']); ?></h1>
    <?php if ($custom_page['status'] === 'draft'): ?>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-yellow-500/10 border border-yellow-500/20 text-yellow-500 text-xs font-semibold mx-auto">
        <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
        Draft Mode
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Page Builder Custom Blocks -->
<div id="custom-page-blocks-wrapper">
<?php
try {
    $stmt = $conn->prepare("SELECT * FROM page_blocks WHERE page_name = :page AND is_active = 1 ORDER BY display_order ASC");
    $stmt->execute(['page' => $slug]);
    $custom_blocks = $stmt->fetchAll();
    
    if (empty($custom_blocks)) {
        // Show default admin warning if empty
        $is_admin = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['superadmin', 'admin', 'event_manager', 'resource_manager']);
        if ($is_admin) {
            ?>
            <section class="py-24 bg-brandBlack">
              <div class="max-w-md mx-auto text-center space-y-4 p-8 border-2 border-dashed border-white/10 rounded-3xl">
                <div class="text-brandGreen text-4xl mb-2"><i class="fa-solid fa-shapes"></i></div>
                <h3 class="text-lg font-bold text-white">This page is currently empty</h3>
                <p class="text-xs text-gray-500">You can add layout blocks to this page in the Admin Control Center under the <strong>Page Section Builder</strong> tab.</p>
                <div class="pt-2">
                  <a href="/admin.php" class="inline-block px-4 py-2 bg-brandGreen text-brandBlack text-xs font-bold rounded-xl hover:bg-brandDarkGreen transition-all">Go to Admin Desk</a>
                </div>
              </div>
            </section>
            <?php
        } else {
            ?>
            <section class="py-24 bg-brandBlack">
              <div class="max-w-md mx-auto text-center py-10">
                <p class="text-gray-500 text-sm">Under construction. Check back soon!</p>
              </div>
            </section>
            <?php
        }
    } else {
        foreach ($custom_blocks as $block) {
            $content = json_decode($block['block_content'], true) ?: [];
            $type = $block['block_type'];
            $title = $block['block_title'];
            
            if ($type === 'rich_text') {
                ?>
                <section class="py-20 bg-brandBlack">
                  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
                    <?php if (!empty($title)): ?>
                      <h2 class="text-3xl font-extrabold font-['Outfit']"><?php echo htmlspecialchars($title); ?></h2>
                    <?php endif; ?>
                    <div class="text-gray-400 leading-relaxed text-sm sm:text-base">
                      <?php echo $content['html'] ?? ''; ?>
                    </div>
                  </div>
                </section>
                <?php
            } elseif ($type === 'text_image') {
                $is_right = ($content['image_align'] ?? 'right') === 'right';
                ?>
                <section class="py-20 bg-brandBlack border-t border-white/5">
                  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                      <div class="lg:col-span-7 space-y-6 text-left <?php echo !$is_right ? 'lg:order-2' : ''; ?>">
                        <?php if (!empty($title)): ?>
                          <h2 class="text-3xl font-extrabold font-['Outfit']"><?php echo htmlspecialchars($title); ?></h2>
                        <?php endif; ?>
                        <p class="text-gray-400 text-sm sm:text-base leading-relaxed">
                          <?php echo htmlspecialchars($content['text'] ?? ''); ?>
                        </p>
                        <?php if (!empty($content['btn_text'])): ?>
                          <div class="pt-2">
                            <a href="<?php echo htmlspecialchars($content['btn_link'] ?? '#'); ?>" class="inline-block px-5 py-2.5 bg-brandGreen text-brandBlack font-bold text-xs rounded-xl hover:bg-brandDarkGreen transition-all">
                              <?php echo htmlspecialchars($content['btn_text']); ?>
                            </a>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="lg:col-span-5 <?php echo !$is_right ? 'lg:order-1' : ''; ?>">
                        <div class="rounded-3xl overflow-hidden shadow-2xl border border-white/5 aspect-video">
                          <img src="<?php echo htmlspecialchars($content['image_url'] ?? 'https://placehold.co/600x400'); ?>" class="w-full h-full object-cover">
                        </div>
                      </div>
                    </div>
                  </div>
                </section>
                <?php
            } elseif ($type === 'features_grid') {
                $cards = $content['cards'] ?? [];
                ?>
                <section class="py-20 bg-[#0c0c0c] border-y border-white/5">
                  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                    <?php if (!empty($title)): ?>
                      <div class="text-center max-w-xl mx-auto">
                        <h2 class="text-3xl font-extrabold font-['Outfit']"><?php echo htmlspecialchars($title); ?></h2>
                      </div>
                    <?php endif; ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                      <?php foreach ($cards as $c): ?>
                        <div class="glass p-8 rounded-3xl border border-white/5 space-y-4 text-left">
                          <div class="w-10 h-10 rounded-lg bg-brandGreen/10 border border-brandGreen/25 text-brandGreen flex items-center justify-center">
                            <i class="<?php echo htmlspecialchars($c['icon'] ?? 'fa-solid fa-cube'); ?> text-lg"></i>
                          </div>
                          <h3 class="text-base font-bold text-white font-['Outfit']"><?php echo htmlspecialchars($c['title'] ?? 'Feature'); ?></h3>
                          <p class="text-gray-500 text-xs sm:text-sm leading-normal"><?php echo htmlspecialchars($c['desc'] ?? ''); ?></p>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>
                </section>
                <?php
            } elseif ($type === 'cta_banner') {
                ?>
                <section class="py-20 bg-brandBlack">
                  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="glass border border-white/10 p-8 sm:p-12 rounded-[32px] space-y-6 shadow-2xl relative overflow-hidden">
                      <?php if (!empty($title)): ?>
                        <h2 class="text-2xl sm:text-4xl font-extrabold font-['Outfit']"><?php echo htmlspecialchars($title); ?></h2>
                      <?php endif; ?>
                      <p class="text-gray-400 text-sm sm:text-base leading-relaxed max-w-xl mx-auto">
                        <?php echo htmlspecialchars($content['text'] ?? ''); ?>
                      </p>
                      <?php if (!empty($content['btn_text'])): ?>
                        <div class="pt-2">
                          <a href="<?php echo htmlspecialchars($content['btn_link'] ?? '#'); ?>" class="px-6 py-3 bg-brandGreen text-brandBlack font-bold text-sm rounded-xl hover:bg-brandDarkGreen transition-all">
                            <?php echo htmlspecialchars($content['btn_text']); ?>
                          </a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </section>
                <?php
            }
        }
    }
} catch (Exception $e) {
    echo '<div class="text-center text-red-500 py-10">Error loading page blocks.</div>';
}
?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
