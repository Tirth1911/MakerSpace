<?php
/**
 * What We Do Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Fetch workspaces
$workspaces = [];
try {
    $stmt = $conn->query("SELECT * FROM workspaces ORDER BY display_order ASC");
    $workspaces = $stmt->fetchAll();
} catch (Exception $e) {}

// Fetch certifications
$certs = [];
try {
    $stmt = $conn->query("SELECT * FROM certifications ORDER BY display_order ASC");
    $certs = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<!-- Hero Banner -->
<section class="relative bg-brandBlack py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-brandGreen uppercase" data-cms-key="what_we_do_subtitle"><?php echo htmlspecialchars(getSetting('what_we_do_subtitle', 'FABRICATION & TRAINING')); ?></span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight" data-cms-key="what_we_do_title"><?php echo htmlspecialchars(getSetting('what_we_do_title', 'Our Workspaces & Programs')); ?></h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto" data-cms-key="what_we_do_desc">
      <?php echo htmlspecialchars(getSetting('what_we_do_desc', 'Providing the tools, modules, hardware diagnostics equipment, and certifications required to scale engineering concepts.')); ?>
    </p>
  </div>
</section>

<!-- Deep Dive workspaces grid -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-20" data-cms-list="workspaces">
    <?php foreach ($workspaces as $index => $w): 
        $features = json_decode($w['features_json'], true) ?: [];
        $is_even = ($index % 2 == 1);
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center" data-cms-item-id="<?php echo $w['id']; ?>">
      <div class="lg:col-span-6 space-y-6 text-left reveal-on-scroll <?php echo $is_even ? 'lg:order-2' : ''; ?>">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen">
          <i class="<?php echo htmlspecialchars($w['icon']); ?> text-xl cms-field-icon"></i>
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold font-['Outfit'] cms-field-title"><?php echo htmlspecialchars($w['title']); ?></h2>
        <p class="text-gray-400 text-sm sm:text-base leading-relaxed cms-field-description">
          <?php echo htmlspecialchars($w['description']); ?>
        </p>
        <ul class="space-y-2 text-sm text-gray-500">
          <?php foreach ($features as $f): ?>
            <li class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-brandGreen"></i> <?php echo htmlspecialchars($f); ?></li>
          <?php endforeach; ?>
        </ul>
        <span class="hidden cms-field-features_json"><?php echo htmlspecialchars($w['features_json']); ?></span>
        <span class="hidden cms-field-icon"><?php echo htmlspecialchars($w['icon']); ?></span>
        <span class="hidden cms-field-image_url"><?php echo htmlspecialchars($w['image_url']); ?></span>
      </div>
      <div class="lg:col-span-6 <?php echo $is_even ? 'lg:order-1' : ''; ?>">
        <div class="rounded-3xl overflow-hidden shadow-2xl border border-white/5 aspect-video">
          <img src="<?php echo htmlspecialchars($w['image_url']); ?>" class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($w['title']); ?>">
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Training Certifications Section -->
<section class="py-24 bg-brandDarkGray border-y border-white/5">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-16">
    <div class="max-w-xl mx-auto space-y-4">
      <span class="text-xs font-bold tracking-widest text-brandGreen uppercase" data-cms-key="certs_subtitle"><?php echo htmlspecialchars(getSetting('certs_subtitle', 'TRAINING CERTIFICATIONS')); ?></span>
      <h2 class="text-3xl font-bold font-['Outfit']" data-cms-key="certs_title"><?php echo htmlspecialchars(getSetting('certs_title', 'Earn Badges & Practical Accreditations')); ?></h2>
      <p class="text-gray-400 text-sm leading-relaxed" data-cms-key="certs_desc">
        <?php echo htmlspecialchars(getSetting('certs_desc', 'Pass machine safety tests and complete workshops to receive digital credentials and verified PDF completion certificates.')); ?>
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8" data-cms-list="certifications">
      <?php foreach ($certs as $c): ?>
      <div class="glass p-8 rounded-3xl border border-white/5 hover:border-brandGreen/25 transition-all text-left space-y-4" data-cms-item-id="<?php echo $c['id']; ?>">
        <div class="w-10 h-10 rounded-lg bg-brandGreen/10 border border-brandGreen/25 text-brandGreen flex items-center justify-center font-bold text-sm cms-field-code"><?php echo htmlspecialchars($c['code']); ?></div>
        <h3 class="text-lg font-bold text-brandLightGray font-['Outfit'] cms-field-title"><?php echo htmlspecialchars($c['title']); ?></h3>
        <p class="text-gray-500 text-xs sm:text-sm leading-normal cms-field-description">
          <?php echo htmlspecialchars($c['description']); ?>
        </p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
