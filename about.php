<?php
/**
 * About Us Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Fetch details from Database
$about_mission = getSetting('about_mission', 'To democratize access to tools, knowledge, and community, enabling anyone to prototype their ideas and solve real-world problems.');
$about_vision = getSetting('about_vision', 'To cultivate a thriving ecosystem of innovators, builders, and entrepreneurs who drive positive social and technological change.');
$about_story = getSetting('about_story', 'Yuvalay MakerSpace was founded in Vadodara as a community-driven hardware innovation lab. Since inception, we have served as a bridge between academic concepts and real-world hardware engineering.');

$timeline = [];
try {
    $stmt = $conn->query("SELECT * FROM milestones ORDER BY display_order ASC");
    $timeline = $stmt->fetchAll();
} catch (Exception $e) {}

$team_members = [];
try {
    $stmt = $conn->query("SELECT * FROM team_members ORDER BY display_order ASC");
    $team_members = $stmt->fetchAll();
} catch (Exception $e) {}

$core_team = array_filter($team_members, function($m) { return $m['type'] === 'team'; });
$mentors = array_filter($team_members, function($m) { return $m['type'] === 'mentor'; });
$volunteers = array_filter($team_members, function($m) { return $m['type'] === 'volunteer'; });

$testimonials = [];
try {
    $stmt = $conn->query("SELECT * FROM testimonials ORDER BY id ASC");
    $testimonials = $stmt->fetchAll();
} catch (Exception $e) {}

$gallery = [];
try {
    $stmt = $conn->query("SELECT * FROM gallery ORDER BY id DESC LIMIT 8");
    $gallery = $stmt->fetchAll();
} catch (Exception $e) {}

// Fallbacks
if (empty($timeline)) {
    $timeline = [
        ['id' => 1, 'year' => '2023', 'title' => 'Inception', 'description' => 'Conceived as Vadodara\'s first community rapid fabrication lab.'],
        ['id' => 2, 'year' => '2024', 'title' => 'Launch', 'description' => 'Officially opened the space with 3D printers, CNC routers, and basic electronics benches.'],
        ['id' => 3, 'year' => '2025', 'title' => 'Expansion', 'description' => 'Partnered with local engineering institutions and expanded active mentorship program.'],
        ['id' => 4, 'year' => '2026', 'title' => 'Scale', 'description' => 'Reaching 500+ active creators and launching advanced AI/IoT modules.']
    ];
}
if (empty($testimonials)) {
    $testimonials = [
        ['id' => 1, 'name' => 'Amit Sharma', 'role' => 'Mechanical Engineering Student', 'text' => 'Yuvalay MakerSpace changed my academic path. I went from reading diagrams to printing actual functional gearboxes for our formula student team.', 'image_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80', 'rating' => 5],
        ['id' => 2, 'name' => 'Priya Patel', 'role' => 'IoT Hardware Startup Founder', 'text' => 'We bootstrapped our entire smart-agriculture device prototyping at Yuvalay. The oscilloscope benches and PCB reflow tools saved us months of development expenses.', 'image_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=120&q=80', 'rating' => 5]
    ];
}
if (empty($gallery)) {
    $gallery = [
        ['id' => 1, 'media_url' => 'https://images.unsplash.com/photo-1581092921461-eab62e97a780?auto=format&fit=crop&w=600&q=80', 'caption' => 'Electronics diagnostics workbench with signal generators.', 'media_type' => 'image', 'category' => 'General'],
        ['id' => 2, 'media_url' => 'https://images.unsplash.com/photo-1615840287214-7fe58a8f3685?auto=format&fit=crop&w=600&q=80', 'caption' => 'FDM 3D printers rendering custom prototyping enclosures.', 'media_type' => 'image', 'category' => 'General'],
        ['id' => 3, 'media_url' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=600&q=80', 'caption' => 'Collaboration table where hackers pitch ideas during Gujarat Hackathon.', 'media_type' => 'image', 'category' => 'General'],
        ['id' => 4, 'media_url' => 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80', 'caption' => 'Mentorship circle mapping out logic for rural irrigation sensors.', 'media_type' => 'image', 'category' => 'General']
    ];
}
?>

<!-- Hero Banner Section -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="about_subtitle"><?php echo htmlspecialchars(getSetting('about_subtitle', 'Get to Know Us')); ?></span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight" data-cms-key="about_title"><?php echo htmlspecialchars(getSetting('about_title', 'About Yuvalay MakerSpace')); ?></h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto" data-cms-key="about_desc">
      <?php echo htmlspecialchars(getSetting('about_desc', 'Bridging academic engineering knowledge and practical prototyping capabilities in Vadodara since 2023.')); ?>
    </p>
  </div>
</section>

<!-- Mission & Vision cards -->
<section class="py-20 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      
      <!-- Mission -->
      <div class="glass p-8 sm:p-10 rounded-[32px] border border-white/5 hover:border-brandGreen/25 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-8 -bottom-8 w-24 h-24 rounded-full bg-brandGreen/5 transition-transform duration-300 group-hover:scale-150"></div>
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen mb-6">
          <i class="fa-solid fa-crosshairs text-xl"></i>
        </div>
        <h2 class="text-xl font-bold text-white mb-4 font-['Outfit']" data-cms-key="about_mission_title"><?php echo htmlspecialchars(getSetting('about_mission_title', 'Our Mission')); ?></h2>
        <p class="text-gray-400 text-sm sm:text-base leading-relaxed" data-cms-key="about_mission">
          <?php echo htmlspecialchars($about_mission); ?>
        </p>
      </div>

      <!-- Vision -->
      <div class="glass p-8 sm:p-10 rounded-[32px] border border-white/5 hover:border-brandGreen/25 transition-all duration-300 relative overflow-hidden group">
        <div class="absolute -right-8 -bottom-8 w-24 h-24 rounded-full bg-brandGreen/5 transition-transform duration-300 group-hover:scale-150"></div>
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen mb-6">
          <i class="fa-solid fa-eye text-xl"></i>
        </div>
        <h2 class="text-xl font-bold text-white mb-4 font-['Outfit']" data-cms-key="about_vision_title"><?php echo htmlspecialchars(getSetting('about_vision_title', 'Our Vision')); ?></h2>
        <p class="text-gray-400 text-sm sm:text-base leading-relaxed" data-cms-key="about_vision">
          <?php echo htmlspecialchars($about_vision); ?>
        </p>
      </div>

    </div>
  </div>
</section>

<!-- Our Story (Text + Banner) -->
<section class="py-20 bg-[#0c0c0c] border-y border-white/5">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
      <div class="lg:col-span-7 space-y-6 text-left">
        <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase font-['Inter']" data-cms-key="about_story_subtitle"><?php echo htmlspecialchars(getSetting('about_story_subtitle', 'THE JOURNEY')); ?></span>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-['Outfit']" data-cms-key="about_story_title"><?php echo htmlspecialchars(getSetting('about_story_title', 'Our Story')); ?></h2>
        <p class="text-gray-400 text-sm sm:text-base leading-relaxed" data-cms-key="about_story">
          <?php echo htmlspecialchars($about_story); ?>
        </p>
        <div class="border-l-4 border-brandGreen pl-4 italic text-sm text-gray-500" data-cms-key="about_story_quote">
          <?php echo htmlspecialchars(getSetting('about_story_quote', '"Democratizing access to technology doesn\'t just mean giving someone a textbook; it means putting the soldering iron and the CAD software directly into their hands."')); ?>
        </div>
      </div>
      <div class="lg:col-span-5">
        <div class="rounded-3xl overflow-hidden shadow-2xl border border-white/5 aspect-video sm:aspect-square">
          <img src="<?php echo htmlspecialchars(getSetting('about_story_image', 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80')); ?>" class="w-full h-full object-cover" alt="Yuvalay Makerspace Workshop" data-cms-key="about_story_image">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Timeline (GSAP Reveal) -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16 space-y-4">
      <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="timeline_subtitle"><?php echo htmlspecialchars(getSetting('timeline_subtitle', 'GROWTH TIMELINE')); ?></span>
      <h2 class="text-3xl font-bold font-['Outfit']" data-cms-key="timeline_title"><?php echo htmlspecialchars(getSetting('timeline_title', 'Milestones We Shared')); ?></h2>
    </div>

    <!-- Timeline Layout -->
    <div class="relative border-l border-white/10 max-w-3xl mx-auto pl-6 sm:pl-10 space-y-12" data-cms-list="milestones">
      <?php foreach ($timeline as $t): ?>
        <div class="relative reveal-on-scroll" data-cms-item-id="<?php echo $t['id']; ?>">
          <!-- Marker Dot -->
          <span class="absolute -left-[31px] sm:-left-[47px] top-1.5 w-4 h-4 rounded-full bg-brandGreen border-4 border-brandBlack shadow shadow-brandGreen/40"></span>
          <!-- Year Card -->
          <div class="glass p-6 rounded-2xl border border-white/5 relative">
            <span class="inline-block px-3 py-1 rounded bg-[#8DC63F]/15 border border-[#8DC63F]/25 text-[#8DC63F] text-xs font-bold mb-3 font-['Outfit'] cms-field-year"><?php echo htmlspecialchars($t['year']); ?></span>
            <h3 class="text-base font-bold text-white mb-1 cms-field-title"><?php echo htmlspecialchars($t['title']); ?></h3>
            <p class="text-xs sm:text-sm text-gray-500 leading-normal cms-field-description"><?php echo htmlspecialchars($t['description']); ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- Leadership Team, Mentors & Volunteers -->
<section class="py-24 bg-[#0c0c0c] border-t border-white/5">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
    
    <!-- Leadership Team -->
    <div class="space-y-12">
      <div class="text-left max-w-xl">
        <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">EXECUTIVE TEAM</span>
        <h2 class="text-3xl font-extrabold font-['Outfit'] mt-1">Our Leadership</h2>
        <p class="text-gray-500 text-sm mt-2">The dedicated handlers running our daily labs, safety protocols, and certifications.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6" data-cms-list="team_members">
        <?php foreach ($core_team as $m): ?>
          <div class="glass p-6 rounded-2xl border border-white/5 hover:border-brandGreen/25 transition-all text-center relative" data-cms-item-id="<?php echo $m['id']; ?>">
            <img src="<?php echo htmlspecialchars($m['image_url'] ?: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&q=80'); ?>" class="w-20 h-20 rounded-full object-cover mx-auto mb-4 border-2 border-white/10" alt="<?php echo htmlspecialchars($m['name']); ?>">
            <h3 class="font-bold text-white text-base cms-field-name"><?php echo htmlspecialchars($m['name']); ?></h3>
            <span class="text-xs text-brandGreen font-semibold block mb-2 cms-field-role"><?php echo htmlspecialchars($m['role']); ?></span>
            <p class="text-xs text-gray-500 leading-normal cms-field-description"><?php echo htmlspecialchars($m['description']); ?></p>
            <span class="hidden cms-field-image_url"><?php echo htmlspecialchars($m['image_url']); ?></span>
            <span class="hidden cms-field-type"><?php echo htmlspecialchars($m['type']); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Mentors Section -->
    <div class="space-y-8">
      <div class="text-left border-l-4 border-brandGreen pl-4">
        <h3 class="text-xl font-bold font-['Outfit'] text-white">Active Mentors</h3>
        <p class="text-gray-500 text-xs mt-1">Industry professionals who visit weekly to review project schematics.</p>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" data-cms-list="team_members">
        <?php foreach ($mentors as $m): ?>
          <div class="p-4 bg-brandBlack border border-white/5 rounded-xl text-center relative animate-fade" data-cms-item-id="<?php echo $m['id']; ?>">
            <span class="font-semibold text-white block cms-field-name"><?php echo htmlspecialchars($m['name']); ?></span>
            <span class="text-xs text-gray-500 cms-field-role"><?php echo htmlspecialchars($m['role']); ?></span>
            <span class="hidden cms-field-description"><?php echo htmlspecialchars($m['description']); ?></span>
            <span class="hidden cms-field-image_url"><?php echo htmlspecialchars($m['image_url']); ?></span>
            <span class="hidden cms-field-type"><?php echo htmlspecialchars($m['type']); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Volunteers Section -->
    <div class="space-y-8">
      <div class="text-left border-l-4 border-brandGreen pl-4">
        <h3 class="text-xl font-bold font-['Outfit'] text-white">Dedicated Volunteers</h3>
        <p class="text-gray-500 text-xs mt-1">Hobbyists assisting in tool checkouts, community meetups, and lab organization.</p>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm" data-cms-list="team_members">
        <?php foreach ($volunteers as $m): ?>
          <div class="p-4 bg-brandBlack border border-white/5 rounded-xl text-center relative animate-fade" data-cms-item-id="<?php echo $m['id']; ?>">
            <span class="font-semibold text-white block cms-field-name"><?php echo htmlspecialchars($m['name']); ?></span>
            <span class="text-xs text-gray-500 cms-field-role"><?php echo htmlspecialchars($m['role']); ?></span>
            <span class="hidden cms-field-description"><?php echo htmlspecialchars($m['description']); ?></span>
            <span class="hidden cms-field-image_url"><?php echo htmlspecialchars($m['image_url']); ?></span>
            <span class="hidden cms-field-type"><?php echo htmlspecialchars($m['type']); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</section>

<!-- Achievements / Gallery Section -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">
    
    <div class="text-center max-w-xl mx-auto space-y-4">
      <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="gallery_subtitle"><?php echo htmlspecialchars(getSetting('gallery_subtitle', 'PHOTO GALLERY')); ?></span>
      <h2 class="text-3xl font-bold font-['Outfit']" data-cms-key="gallery_title"><?php echo htmlspecialchars(getSetting('gallery_title', 'Snapshots of Innovation')); ?></h2>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6" data-cms-list="gallery">
      <?php foreach ($gallery as $g): ?>
        <div class="rounded-2xl overflow-hidden border border-white/5 aspect-square relative group zoom-container shadow-lg" data-cms-item-id="<?php echo $g['id']; ?>">
          <img src="<?php echo htmlspecialchars($g['media_url']); ?>" class="w-full h-full object-cover zoom-image" alt="Gallery Photo">
          <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent p-4 flex flex-col justify-end opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-20 text-left">
            <p class="text-xs text-gray-300 font-semibold leading-normal cms-field-caption"><?php echo htmlspecialchars($g['caption'] ?? ''); ?></p>
          </div>
          <span class="hidden cms-field-media_url"><?php echo htmlspecialchars($g['media_url']); ?></span>
          <span class="hidden cms-field-media_type"><?php echo htmlspecialchars($g['media_type'] ?? 'image'); ?></span>
          <span class="hidden cms-field-category"><?php echo htmlspecialchars($g['category'] ?? 'General'); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- Partners Section -->
<section class="py-16 bg-[#0c0c0c] border-y border-white/5 overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-8">
    <span class="text-xs font-bold tracking-wider text-gray-500 uppercase" data-cms-key="partners_subtitle"><?php echo htmlspecialchars(getSetting('partners_subtitle', 'Supporting Partners & Institutes')); ?></span>
    
    <!-- Smooth Sliding logos layout -->
    <div class="flex flex-wrap justify-center items-center gap-12 opacity-40">
      <div class="text-white font-extrabold text-base tracking-widest uppercase font-['Outfit']">VADODARA INCUBATION</div>
      <div class="text-white font-extrabold text-base tracking-widest uppercase font-['Outfit']">GUJARAT TECH UNIVERSITY</div>
      <div class="text-white font-extrabold text-base tracking-widest uppercase font-['Outfit']">BARODA FOUNDATION</div>
      <div class="text-white font-extrabold text-base tracking-widest uppercase font-['Outfit']">INDUS ENGINEERING</div>
    </div>
  </div>
</section>

<!-- Testimonials Carousel Section -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center max-w-xl mx-auto mb-16 space-y-4">
      <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase" data-cms-key="testimonials_subtitle"><?php echo htmlspecialchars(getSetting('testimonials_subtitle', 'TESTIMONIALS')); ?></span>
      <h2 class="text-3xl font-extrabold font-['Outfit']" data-cms-key="testimonials_title"><?php echo htmlspecialchars(getSetting('testimonials_title', 'Words from our Builders')); ?></h2>
    </div>

    <!-- Testimonials grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto" data-cms-list="testimonials">
      <?php foreach ($testimonials as $t): ?>
        <div class="glass p-8 rounded-3xl border border-white/5 relative flex flex-col justify-between" data-cms-item-id="<?php echo $t['id']; ?>">
          <p class="text-gray-400 text-sm italic leading-relaxed mb-6 cms-field-text">
            "<?php echo htmlspecialchars($t['text']); ?>"
          </p>
          <div class="flex items-center gap-4">
            <img src="<?php echo htmlspecialchars($t['image_url'] ?? 'https://placehold.co/120'); ?>" class="w-12 h-12 rounded-full object-cover border border-white/10" alt="Avatar">
            <div>
              <h4 class="text-sm font-bold text-white cms-field-name"><?php echo htmlspecialchars($t['name']); ?></h4>
              <span class="text-xs text-brandGreen font-medium cms-field-role"><?php echo htmlspecialchars($t['role']); ?></span>
            </div>
          </div>
          <span class="hidden cms-field-image_url"><?php echo htmlspecialchars($t['image_url'] ?? ''); ?></span>
          <span class="hidden cms-field-rating"><?php echo htmlspecialchars($t['rating'] ?? '5'); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
