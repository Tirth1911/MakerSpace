<?php
/**
 * Home Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Fetch Homepage Slides
$slides = [];
$hero_title = "EMPOWERING MAKERS. BUILDING A BETTER TOMORROW.";
$hero_description = "A collaborative space for innovators, students, creators and communities to access tools, mentorship and resources for building impactful solutions.";

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM homepage_slides ORDER BY display_order ASC");
        $stmt->execute();
        $slides = $stmt->fetchAll();
        
        // Fetch CMS settings overrides
        $stmtSet = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_title'");
        $stmtSet->execute();
        $resTitle = $stmtSet->fetch();
        if ($resTitle) $hero_title = $resTitle['setting_value'];

        $stmtSet = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'hero_description'");
        $stmtSet->execute();
        $resDesc = $stmtSet->fetch();
        if ($resDesc) $hero_description = $resDesc['setting_value'];
    } catch (PDOException $e) {
        // Fallback to static slides if table doesn't load
    }
}

// Fallback slides in case DB has none
if (empty($slides)) {
    $slides = [
        ['image_url' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=1200&q=80', 'title' => 'Advanced Robotics Lab', 'subtitle' => 'Build high-performance automation hardware with expert guidance.'],
        ['image_url' => 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=1200&q=80', 'title' => 'Electronics Workbench', 'subtitle' => 'Design, solder, and debug custom circuit boards.'],
        ['image_url' => 'https://images.unsplash.com/photo-1615840287214-7fe58a8f3685?auto=format&fit=crop&w=1200&q=80', 'title' => '3D Printing Zone', 'subtitle' => 'Turn digital CAD files into tangible objects in hours.'],
        ['image_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1200&q=80', 'title' => 'Student Collaborations', 'subtitle' => 'Share ideas, learn together, and build impactful systems.']
    ];
}
?>

<!-- Hero Section -->
<section class="relative min-h-[calc(100vh-80px)] flex items-center bg-[#090909] overflow-hidden py-10 sm:py-16 lg:py-20">
  <!-- Decorative Background Gradients -->
  <div class="absolute top-1/4 left-1/10 w-96 h-96 rounded-full bg-brandGreen/10 filter blur-[100px] pointer-events-none"></div>
  <div class="absolute bottom-1/4 right-1/10 w-96 h-96 rounded-full bg-brandDarkGreen/5 filter blur-[120px] pointer-events-none"></div>

  <div class="max-w-[1440px] mx-auto px-6 sm:px-10 lg:px-16 w-full relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-12 lg:gap-16 items-center">
      
      <!-- LEFT SIDE: Hero Description -->
      <div class="space-y-6 text-left w-full">
        
        <!-- Small Tag -->
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brandGreen/10 border border-brandGreen/25 text-brandGreen font-semibold text-xs tracking-wider uppercase font-['Inter']">
          <span class="w-1.5 h-1.5 rounded-full bg-brandGreen"></span>
          <span data-cms-key="hero_tag"><?php echo htmlspecialchars(getSetting('hero_tag', 'MakerSpace Vadodara')); ?></span>
        </div>
        
        <!-- Large Heading -->
        <h1 class="text-4xl sm:text-6xl lg:text-[76px] xl:text-[88px] font-extrabold tracking-tighter leading-[1.02] font-['Outfit'] w-full" data-cms-key="hero_title">
          <?php
            // Highlight specific words (MAKERS, TOMORROW) in Green
            $title_html = htmlspecialchars($hero_title);
            $title_html = str_ireplace("MAKERS", "<span class='text-brandGreen text-gradient'>MAKERS</span>", $title_html);
            $title_html = str_ireplace("TOMORROW", "<span class='text-brandGreen text-gradient'>TOMORROW</span>", $title_html);
            echo $title_html;
          ?>
        </h1>

        <!-- Description -->
        <p class="text-gray-400 text-base sm:text-lg leading-relaxed max-w-2xl" data-cms-key="hero_description">
          <?php echo htmlspecialchars($hero_description); ?>
        </p>

        <!-- Buttons -->
        <div class="flex flex-wrap items-center gap-4 pt-2">
          <a href="/events.php" class="px-6 py-3.5 rounded-xl font-bold bg-brandGreen text-brandBlack hover:bg-brandDarkGreen hover:-translate-y-0.5 shadow-lg shadow-brandGreen/10 hover:shadow-brandGreen/20 transition-all duration-300 flex items-center gap-2 text-sm">
            Explore Our Space <i class="fa-solid fa-arrow-right"></i>
          </a>
          <a href="/get-involved.php" class="px-6 py-3.5 rounded-xl font-bold bg-brandDarkGray border border-white/10 hover:bg-white/5 hover:border-white/20 transition-all duration-300 text-sm">
            Get Involved
          </a>
        </div>

      </div>

      <!-- RIGHT SIDE: Premium Autoslideshow -->
      <div class="w-full">
        <div class="relative w-full aspect-[1.15] hero-image-container overflow-hidden group">
          
          <!-- Slide image container -->
          <div class="absolute inset-0 bg-[#090909]">
            <?php foreach ($slides as $index => $slide): ?>
              <div class="absolute inset-0 transition-all duration-1000 ease-in-out slide-container <?php echo $index === 0 ? 'slide-active z-10' : 'slide-inactive z-0'; ?>" id="hero_slide_<?php echo $index; ?>">
                <!-- Image with subtle zoom (no dark overlay) -->
                <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" class="w-full h-full object-cover zoom-image" alt="Yuvalay Slideshow Image">
                
                <!-- High-visibility Glassmorphic Text overlay -->
                <div class="absolute bottom-6 left-6 right-6 z-20 p-5 rounded-2xl glass text-left shadow-lg border border-white/10">
                  <h3 class="text-white font-bold text-xl sm:text-2xl font-['Outfit'] mb-1.5"><?php echo htmlspecialchars($slide['title']); ?></h3>
                  <p class="text-gray-300 text-sm sm:text-base leading-normal"><?php echo htmlspecialchars($slide['subtitle'] ?? ''); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Navigation Arrows -->
          <button onclick="prevHeroSlide()" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-brandGreen/80 border border-white/10 hover:border-brandGreen text-white hover:text-brandBlack flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30">
            <i class="fa-solid fa-chevron-left text-sm"></i>
          </button>
          <button onclick="nextHeroSlide()" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/60 hover:bg-brandGreen/80 border border-white/10 hover:border-brandGreen text-white hover:text-brandBlack flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 z-30">
            <i class="fa-solid fa-chevron-right text-sm"></i>
          </button>

          <!-- Dots Indicator -->
          <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-2 z-30">
            <?php foreach ($slides as $index => $slide): ?>
              <button onclick="setHeroSlide(<?php echo $index; ?>)" class="w-2.5 h-2.5 rounded-full transition-all duration-300 hero-slide-dot <?php echo $index === 0 ? 'bg-brandGreen w-6' : 'bg-white/40 hover:bg-white/60'; ?>" id="hero_dot_<?php echo $index; ?>"></button>
            <?php endforeach; ?>
          </div>

        </div>
      </div>

    </div>
  </div>
</section>

<!-- Stats counters Section -->
<section class="py-16 bg-[#0c0c0c] border-y border-white/5 relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
      
      <!-- Members Card -->
      <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandGreen/20 text-center relative overflow-hidden group stats-editable-card">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 flex items-center justify-center mx-auto mb-4 text-brandGreen">
          <i class="fa-solid fa-users text-lg"></i>
        </div>
        <div class="text-3xl sm:text-4xl font-extrabold font-['Outfit'] text-white flex items-center justify-center">
          <span class="count-value" data-target="<?php echo htmlspecialchars(getSetting('stats_members_count', '500')); ?>" data-cms-key="stats_members_count"><?php echo htmlspecialchars(getSetting('stats_members_count', '500')); ?></span>+
        </div>
        <p class="text-xs sm:text-sm font-semibold tracking-wider text-gray-500 uppercase mt-2" data-cms-key="stats_members_label"><?php echo htmlspecialchars(getSetting('stats_members_label', 'Community Members')); ?></p>
      </div>

      <!-- Tools Card -->
      <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandGreen/20 text-center relative overflow-hidden group stats-editable-card">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 flex items-center justify-center mx-auto mb-4 text-brandGreen">
          <i class="fa-solid fa-screwdriver-wrench text-lg"></i>
        </div>
        <div class="text-3xl sm:text-4xl font-extrabold font-['Outfit'] text-white flex items-center justify-center">
          <span class="count-value" data-target="<?php echo htmlspecialchars(getSetting('stats_tools_count', '100')); ?>" data-cms-key="stats_tools_count"><?php echo htmlspecialchars(getSetting('stats_tools_count', '100')); ?></span>+
        </div>
        <p class="text-xs sm:text-sm font-semibold tracking-wider text-gray-500 uppercase mt-2" data-cms-key="stats_tools_label"><?php echo htmlspecialchars(getSetting('stats_tools_label', 'Tools & Equipment')); ?></p>
      </div>

      <!-- Events Card -->
      <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandGreen/20 text-center relative overflow-hidden group stats-editable-card">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 flex items-center justify-center mx-auto mb-4 text-brandGreen">
          <i class="fa-solid fa-calendar-check text-lg"></i>
        </div>
        <div class="text-3xl sm:text-4xl font-extrabold font-['Outfit'] text-white flex items-center justify-center">
          <span class="count-value" data-target="<?php echo htmlspecialchars(getSetting('stats_events_count', '50')); ?>" data-cms-key="stats_events_count"><?php echo htmlspecialchars(getSetting('stats_events_count', '50')); ?></span>+
        </div>
        <p class="text-xs sm:text-sm font-semibold tracking-wider text-gray-500 uppercase mt-2" data-cms-key="stats_events_label"><?php echo htmlspecialchars(getSetting('stats_events_label', 'Events & Workshops')); ?></p>
      </div>

      <!-- Volunteers Card -->
      <div class="glass p-6 sm:p-8 rounded-2xl border border-white/5 hover:border-brandGreen/20 text-center relative overflow-hidden group stats-editable-card">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 flex items-center justify-center mx-auto mb-4 text-brandGreen">
          <i class="fa-solid fa-handshake text-lg"></i>
        </div>
        <div class="text-3xl sm:text-4xl font-extrabold font-['Outfit'] text-white flex items-center justify-center">
          <span class="count-value" data-target="<?php echo htmlspecialchars(getSetting('stats_volunteers_count', '20')); ?>" data-cms-key="stats_volunteers_count"><?php echo htmlspecialchars(getSetting('stats_volunteers_count', '20')); ?></span>+
        </div>
        <p class="text-xs sm:text-sm font-semibold tracking-wider text-gray-500 uppercase mt-2" data-cms-key="stats_volunteers_label"><?php echo htmlspecialchars(getSetting('stats_volunteers_label', 'Active Volunteers')); ?></p>
      </div>

    </div>
  </div>
</section>

<!-- What We Do Grid Section -->
<section class="py-24 bg-brandBlack relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
      <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="offerings_subtitle"><?php echo htmlspecialchars(getSetting('offerings_subtitle', 'Our Offerings')); ?></span>
      <h2 class="text-3xl sm:text-4xl font-extrabold font-['Outfit']" data-cms-key="offerings_title"><?php echo htmlspecialchars(getSetting('offerings_title', 'What We Do inside Yuvalay')); ?></h2>
      <p class="text-gray-400 text-sm sm:text-base leading-relaxed" data-cms-key="offerings_desc">
        <?php echo htmlspecialchars(getSetting('offerings_desc', 'We provide the framework and environment for developers and designers to conceptualize, model, test, and fabricate their hardware ideas.')); ?>
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      
      <!-- Card 1 -->
      <div class="glass floating-card p-8 rounded-3xl border border-white/5 flex flex-col justify-between group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-[#8DC63F]/10 border border-[#8DC63F]/20 text-[#8DC63F] flex items-center justify-center transition-all duration-300 group-hover:scale-110">
            <i class="<?php echo htmlspecialchars(getSetting('offering_1_icon', 'fa-solid fa-cubes')); ?>"></i>
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-bold text-white group-hover:text-[#8DC63F] transition-colors duration-200" data-cms-key="offering_1_title"><?php echo htmlspecialchars(getSetting('offering_1_title', 'Access To Tools')); ?></h3>
            <p class="text-gray-500 text-xs sm:text-sm leading-normal" data-cms-key="offering_1_desc">
              <?php echo htmlspecialchars(getSetting('offering_1_desc', 'Utilize high-precision desktop FDM 3D printers, CNC laser cutter routers, reflow soldering benches, and signal generators.')); ?>
            </p>
          </div>
        </div>
        <a href="<?php echo htmlspecialchars(getSetting('offering_1_link', '/what-we-do.php')); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#8DC63F] hover:underline mt-6">
          <span data-cms-key="offering_1_link_label"><?php echo htmlspecialchars(getSetting('offering_1_link_label', 'Learn details')); ?></span> <i class="fa-solid fa-angle-right"></i>
        </a>
      </div>

      <!-- Card 2 -->
      <div class="glass floating-card p-8 rounded-3xl border border-white/5 flex flex-col justify-between group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-[#8DC63F]/10 border border-[#8DC63F]/20 text-[#8DC63F] flex items-center justify-center transition-all duration-300 group-hover:scale-110">
            <i class="<?php echo htmlspecialchars(getSetting('offering_2_icon', 'fa-solid fa-graduation-cap')); ?>"></i>
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-bold text-white group-hover:text-[#8DC63F] transition-colors duration-200" data-cms-key="offering_2_title"><?php echo htmlspecialchars(getSetting('offering_2_title', 'Learn & Grow')); ?></h3>
            <p class="text-gray-500 text-xs sm:text-sm leading-normal" data-cms-key="offering_2_desc">
              <?php echo htmlspecialchars(getSetting('offering_2_desc', 'Join workshops covering Arduino firmware development, multi-layer PCB design schematics, CAD layouts, and rapid manufacturing practices.')); ?>
            </p>
          </div>
        </div>
        <a href="<?php echo htmlspecialchars(getSetting('offering_2_link', '/events.php')); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#8DC63F] hover:underline mt-6">
          <span data-cms-key="offering_2_link_label"><?php echo htmlspecialchars(getSetting('offering_2_link_label', 'Browse workshops')); ?></span> <i class="fa-solid fa-angle-right"></i>
        </a>
      </div>

      <!-- Card 3 -->
      <div class="glass floating-card p-8 rounded-3xl border border-white/5 flex flex-col justify-between group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-[#8DC63F]/10 border border-[#8DC63F]/20 text-[#8DC63F] flex items-center justify-center transition-all duration-300 group-hover:scale-110">
            <i class="<?php echo htmlspecialchars(getSetting('offering_3_icon', 'fa-solid fa-network-wired')); ?>"></i>
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-bold text-white group-hover:text-[#8DC63F] transition-colors duration-200" data-cms-key="offering_3_title"><?php echo htmlspecialchars(getSetting('offering_3_title', 'Collaborate')); ?></h3>
            <p class="text-gray-500 text-xs sm:text-sm leading-normal" data-cms-key="offering_3_desc">
              <?php echo htmlspecialchars(getSetting('offering_3_desc', 'Network with active hobbyists, industrial PCB routing design experts, mentors, students, and technology enthusiasts inside our open courtyard.')); ?>
            </p>
          </div>
        </div>
        <a href="<?php echo htmlspecialchars(getSetting('offering_3_link', '/get-involved.php')); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#8DC63F] hover:underline mt-6">
          <span data-cms-key="offering_3_link_label"><?php echo htmlspecialchars(getSetting('offering_3_link_label', 'Onboard with us')); ?></span> <i class="fa-solid fa-angle-right"></i>
        </a>
      </div>

      <!-- Card 4 -->
      <div class="glass floating-card p-8 rounded-3xl border border-white/5 flex flex-col justify-between group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-[#8DC63F]/10 border border-[#8DC63F]/20 text-[#8DC63F] flex items-center justify-center transition-all duration-300 group-hover:scale-110">
            <i class="<?php echo htmlspecialchars(getSetting('offering_4_icon', 'fa-solid fa-lightbulb')); ?>"></i>
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-bold text-white group-hover:text-[#8DC63F] transition-colors duration-200" data-cms-key="offering_4_title"><?php echo htmlspecialchars(getSetting('offering_4_title', 'Create Impact')); ?></h3>
            <p class="text-gray-500 text-xs sm:text-sm leading-normal" data-cms-key="offering_4_desc">
              <?php echo htmlspecialchars(getSetting('offering_4_desc', 'Translate classroom concepts into working hardware models that address rural irrigation problems, power grid controls, and automation issues.')); ?>
            </p>
          </div>
        </div>
        <a href="<?php echo htmlspecialchars(getSetting('offering_4_link', '/about.php')); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-[#8DC63F] hover:underline mt-6">
          <span data-cms-key="offering_4_link_label"><?php echo htmlspecialchars(getSetting('offering_4_link_label', 'Read impact stats')); ?></span> <i class="fa-solid fa-angle-right"></i>
        </a>
      </div>

    </div>

  </div>
</section>

<!-- Page Builder Custom Blocks -->
<?php
try {
    $stmt = $conn->prepare("SELECT * FROM page_blocks WHERE page_name = 'home' AND is_active = 1 ORDER BY display_order ASC");
    $stmt->execute();
    $custom_blocks = $stmt->fetchAll();
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
} catch (Exception $e) {}
?>

<!-- Call to Action Section -->
<section class="py-24 bg-[#090909] border-t border-white/5 relative">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
    <div class="glass border border-white/10 p-10 sm:p-16 rounded-[40px] space-y-6 shadow-2xl relative overflow-hidden">
      <!-- Gradient mesh inside card -->
      <div class="absolute -top-1/2 -left-1/2 w-full h-full bg-brandGreen/5 filter blur-[60px] pointer-events-none"></div>
      
      <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="cta_tag"><?php echo htmlspecialchars(getSetting('cta_tag', 'JOIN THE COMMUNITY')); ?></span>
      <h2 class="text-3xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight" data-cms-key="cta_title"><?php echo htmlspecialchars(getSetting('cta_title', 'Ready to build your first hardware prototype?')); ?></h2>
      <p class="text-gray-400 text-sm sm:text-base leading-relaxed max-w-xl mx-auto" data-cms-key="cta_desc">
        <?php echo htmlspecialchars(getSetting('cta_desc', 'Join as a member today to access equipment, register for certification training programs, and seek expert PCBs and embedded systems reviews.')); ?>
      </p>
      <div class="pt-4 flex flex-wrap justify-center gap-4">
        <a href="<?php echo htmlspecialchars(getSetting('cta_btn1_link', '/register.php')); ?>" class="px-7 py-4 rounded-xl font-bold bg-[#8DC63F] text-brandBlack hover:bg-[#73a11c] transition-all shadow-lg shadow-[#8DC63F]/10 hover:shadow-[#8DC63F]/20 hover:-translate-y-0.5 text-sm" data-cms-key="cta_btn1_text"><?php echo htmlspecialchars(getSetting('cta_btn1_text', 'Become a Member')); ?></a>
        <a href="<?php echo htmlspecialchars(getSetting('cta_btn2_link', '/contact.php')); ?>" class="px-7 py-4 rounded-xl font-bold bg-transparent border border-white/10 hover:bg-white/5 hover:border-white/20 transition-all text-sm" data-cms-key="cta_btn2_text"><?php echo htmlspecialchars(getSetting('cta_btn2_text', 'Schedule a Visit')); ?></a>
      </div>
    </div>
  </div>
</section>

<!-- Home Page Slideshow Controller Script -->
<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll(".slide-container");
    const dots = document.querySelectorAll(".hero-slide-dot");
    const totalSlides = slides.length;
    let slideInterval;

    function showSlide(index) {
        if (totalSlides === 0) return;
        
        // Loop range check
        if (index >= totalSlides) currentSlide = 0;
        else if (index < 0) currentSlide = totalSlides - 1;
        else currentSlide = index;

        // Transition slides
        slides.forEach((slide, i) => {
            if (i === currentSlide) {
                slide.classList.remove("slide-inactive", "z-0");
                slide.classList.add("slide-active", "z-10");
            } else {
                slide.classList.remove("slide-active", "z-10");
                slide.classList.add("slide-inactive", "z-0");
            }
        });

        // Update indicators
        dots.forEach((dot, i) => {
            if (i === currentSlide) {
                dot.classList.add("bg-brandGreen", "w-6");
                dot.classList.remove("bg-white/40");
            } else {
                dot.classList.remove("bg-brandGreen", "w-6");
                dot.classList.add("bg-white/40");
            }
        });
    }

    function nextHeroSlide() {
        showSlide(currentSlide + 1);
        resetSlideInterval();
    }

    function prevHeroSlide() {
        showSlide(currentSlide - 1);
        resetSlideInterval();
    }

    function setHeroSlide(index) {
        showSlide(index);
        resetSlideInterval();
    }

    function startSlideInterval() {
        slideInterval = setInterval(() => {
            showSlide(currentSlide + 1);
        }, 4000);
    }

    function resetSlideInterval() {
        clearInterval(slideInterval);
        startSlideInterval();
    }

    // Auto slideshow init
    startSlideInterval();

    // GSAP Scroll Counter Animation
    gsap.utils.toArray('.count-value').forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-target'));
        gsap.to(counter, {
            innerText: target,
            duration: 1.8,
            snap: { innerText: 1 },
            scrollTrigger: {
                trigger: counter,
                start: 'top 90%',
                toggleActions: 'play none none none'
            }
        });
    });
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
