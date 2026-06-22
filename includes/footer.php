  </main>

  <!-- Global Footer -->
  <footer class="bg-[#F9FAFB] border-t border-white/5 py-16 text-gray-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
        
        <!-- About column -->
        <div class="space-y-4 text-left">
          <a href="/index.php" class="inline-block">
            <img src="<?php echo htmlspecialchars(getSetting('design_logo_url', '/public/images/logo-light.png')); ?>" alt="Yuvalay MakerSpace Logo" class="h-9 w-auto object-contain">
          </a>
          <p class="text-sm leading-relaxed text-gray-500" data-cms-key="footer_description">
            <?php echo htmlspecialchars(getSetting('footer_description', 'A premier collaborative community laboratory in Vadodara empowering students, developers, and hardware innovators with advanced prototyping resources.')); ?>
          </p>
          <div class="flex items-center gap-4 pt-2">
            <?php if ($fb = getSetting('social_facebook')): ?>
              <a href="<?php echo htmlspecialchars($fb); ?>" target="_blank" class="hover:text-brandGreen transition-colors text-lg"><i class="fa-brands fa-facebook"></i></a>
            <?php endif; ?>
            <?php if ($ig = getSetting('social_instagram')): ?>
              <a href="<?php echo htmlspecialchars($ig); ?>" target="_blank" class="hover:text-brandGreen transition-colors text-lg"><i class="fa-brands fa-instagram"></i></a>
            <?php endif; ?>
            <?php if ($li = getSetting('social_linkedin')): ?>
              <a href="<?php echo htmlspecialchars($li); ?>" target="_blank" class="hover:text-brandGreen transition-colors text-lg"><i class="fa-brands fa-linkedin"></i></a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Links columns loaded dynamically from DB -->
        <?php
        try {
            $stmt = $conn->prepare("SELECT * FROM footer_sections ORDER BY display_order ASC");
            $stmt->execute();
            $sections = $stmt->fetchAll();
            foreach ($sections as $sec) {
                echo '<div class="text-left">';
                echo '<h4 class="text-brandLightGray font-semibold text-sm uppercase tracking-wider mb-5">' . htmlspecialchars($sec['title']) . '</h4>';
                echo '<ul class="space-y-3 text-sm">';
                
                $stmtLinks = $conn->prepare("SELECT * FROM footer_links WHERE section_id = :sid ORDER BY display_order ASC");
                $stmtLinks->execute(['sid' => $sec['id']]);
                $links = $stmtLinks->fetchAll();
                foreach ($links as $l) {
                    echo '<li><a href="' . htmlspecialchars($l['link']) . '" class="hover:text-brandGreen text-gray-500 transition-colors">' . htmlspecialchars($l['label']) . '</a></li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
        } catch (Exception $e) {}
        ?>

        <!-- Contact info -->
        <div class="text-left">
          <h4 class="text-brandLightGray font-semibold text-sm uppercase tracking-wider mb-5">Contact Details</h4>
          <ul class="space-y-3 text-sm">
            <li class="flex items-start gap-2.5">
              <i class="fa-solid fa-map-location-dot text-brandGreen mt-1"></i>
              <span class="text-gray-500 leading-normal" data-cms-key="contact_address"><?php echo htmlspecialchars($contact_address); ?></span>
            </li>
            <li class="flex items-center gap-2.5">
              <i class="fa-solid fa-phone text-brandGreen"></i>
              <span class="text-gray-500" data-cms-key="contact_phone"><?php echo htmlspecialchars($contact_phone); ?></span>
            </li>
            <li class="flex items-center gap-2.5">
              <i class="fa-solid fa-envelope text-brandGreen"></i>
              <span class="text-gray-500" data-cms-key="contact_email"><?php echo htmlspecialchars($contact_email); ?></span>
            </li>
          </ul>
        </div>

      </div>

      <div class="border-t border-white/5 mt-16 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-400 gap-4">
        <p data-cms-key="footer_copyright">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('footer_copyright', 'Yuvalay MakerSpace Vadodara. All rights reserved.')); ?></p>
        <div class="flex gap-6">
          <a href="#" class="hover:text-brandGreen">Terms & Conditions</a>
          <a href="#" class="hover:text-brandGreen">Privacy Policy</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- Scripts -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

  <!-- Global UI / Mobile Menu Logic -->
  <script>
    // Register GSAP ScrollTrigger
    gsap.registerPlugin(ScrollTrigger);

    // Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuDrawer = document.getElementById('mobileMenuDrawer');
    if (mobileMenuBtn && mobileMenuDrawer) {
      mobileMenuBtn.addEventListener('click', () => {
        mobileMenuDrawer.classList.toggle('hidden');
      });
    }

    // Toggle Edit Mode Client Script
    function toggleEditMode() {
      fetch('/api.php?action=toggle-edit-mode', {
        method: 'POST'
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.reload();
        }
      })
      .catch(err => console.error("Error toggling Edit Mode:", err));
    }

    // Global reveal animations on scroll
    gsap.utils.toArray('.reveal-on-scroll').forEach(elem => {
      gsap.fromTo(elem, {
        opacity: 0,
        y: 40
      }, {
        opacity: 1,
        y: 0,
        duration: 0.8,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: elem,
          start: 'top 85%',
          toggleActions: 'play none none none'
        }
      });
    });
  </script>

  <!-- Load CMS Inline Edit Script if Admin has Edit Mode ON -->
  <?php if ($can_edit && isset($_SESSION['edit_mode']) && $_SESSION['edit_mode'] === true): ?>
    
    <!-- CMS Floating Toggle Button (always visible in edit mode) -->
    <button
      id="cms-toolbar-fab"
      onclick="document.getElementById('cms-admin-toolbar').classList.toggle('cms-panel-hidden')"
      title="Toggle CMS Editor Panel"
      class="fixed bottom-6 right-6 z-[99999] w-14 h-14 rounded-full bg-[#8DC63F] hover:bg-[#6DA52A] text-white shadow-2xl shadow-[#8DC63F]/40 flex items-center justify-center transition-all duration-300 hover:scale-110 active:scale-95 border-2 border-white/20"
    >
      <i class="fa-solid fa-pen-to-square text-xl"></i>
    </button>

    <!-- Floating CMS Admin Toolbar Panel (bottom-right) -->
    <div id="cms-admin-toolbar" class="fixed bottom-24 right-6 z-[99998] bg-white border border-gray-200 p-4 rounded-2xl shadow-2xl max-w-[240px] w-[240px] transition-all duration-300">
      <div class="flex items-center justify-between gap-4 mb-3 border-b border-gray-100 pb-2">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-[#8DC63F] animate-pulse"></span>
          <span class="font-bold text-xs uppercase tracking-wider text-[#8DC63F] font-['Outfit']">CMS ACTIVE</span>
        </div>
        <button onclick="toggleEditMode()" class="text-xs text-red-500 hover:text-red-700 font-semibold flex items-center gap-1">
          <i class="fa-solid fa-power-off"></i> Off
        </button>
      </div>
      <p class="text-[10px] text-gray-400 leading-snug mb-3">Double-click any text to edit it live. Changes save instantly to the database.</p>
      <div class="space-y-2">
        <button onclick="openGlobalCmsModal()" class="w-full text-center py-2 bg-[#8DC63F] hover:bg-[#6DA52A] text-white font-semibold text-xs rounded-xl shadow-sm transition-all">
          <i class="fa-solid fa-gear mr-1"></i> Global Settings
        </button>
        <button onclick="openSlideshowCmsModal()" class="w-full text-center py-2 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 font-semibold text-xs rounded-xl transition-all">
          <i class="fa-solid fa-images mr-1"></i> Slideshow Slides
        </button>
        <button onclick="openStatsCmsModal()" class="w-full text-center py-2 bg-gray-100 hover:bg-gray-200 border border-gray-200 text-gray-700 font-semibold text-xs rounded-xl transition-all">
          <i class="fa-solid fa-chart-bar mr-1"></i> Stats Section
        </button>
      </div>
    </div>

    <!-- CMS Global & Slides Modal Framework -->
    <div id="cmsGlobalModal" class="fixed inset-0 bg-[#111111]/80 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
      <div class="bg-brandDarkGray border border-white/10 w-full max-w-xl rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
        <div class="p-6 border-b border-white/5 flex justify-between items-center bg-[#151515]">
          <h3 class="font-bold text-lg text-white font-['Outfit']" id="cmsModalTitle">Global CMS Configuration</h3>
          <button onclick="closeCmsModal('cmsGlobalModal')" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-4" id="cmsModalContent">
          <!-- Populated by JS -->
        </div>
        <div class="p-6 border-t border-white/5 bg-[#151515] flex justify-end gap-3">
          <button onclick="closeCmsModal('cmsGlobalModal')" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Cancel</button>
          <button onclick="saveGlobalCmsSettings()" class="px-5 py-2 rounded-xl bg-brandGreen hover:bg-brandGreen/90 text-xs font-bold text-brandBlack">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- CMS Slides Modal Framework -->
    <div id="cmsSlidesModal" class="fixed inset-0 bg-[#111111]/80 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
      <div class="bg-brandDarkGray border border-white/10 w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[85vh]">
        <div class="p-6 border-b border-white/5 flex justify-between items-center bg-[#151515]">
          <h3 class="font-bold text-lg text-white font-['Outfit']">Manage Slideshow Slides</h3>
          <button onclick="closeCmsModal('cmsSlidesModal')" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="p-6 overflow-y-auto space-y-4" id="cmsSlidesContent">
          <!-- Populated by JS -->
        </div>
        <div class="p-6 border-t border-white/5 bg-[#151515] flex justify-between items-center">
          <button onclick="addNewCmsSlide()" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-brandGreen border border-brandGreen/30"><i class="fa-solid fa-plus mr-1"></i> Add New Slide</button>
          <div class="flex gap-3">
            <button onclick="closeCmsModal('cmsSlidesModal')" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- CMS Stats Modal -->
    <div id="cmsStatsModal" class="fixed inset-0 bg-[#111111]/80 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
      <div class="bg-white w-full max-w-2xl rounded-2xl overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center bg-gray-50">
          <div>
            <h3 class="font-extrabold text-base text-gray-800 uppercase tracking-wider">Edit Stats Section</h3>
            <p class="text-xs text-gray-400 mt-0.5">Update the four counter cards visible on the homepage.</p>
          </div>
          <button onclick="document.getElementById('cmsStatsModal').classList.add('hidden')" class="text-gray-400 hover:text-black text-xl"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6 overflow-y-auto flex-grow">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-5" id="statsModalGrid">
            <!-- Populated by JS -->
          </div>
        </div>
        <div class="p-5 border-t border-gray-200 bg-gray-50 flex justify-end gap-3">
          <button onclick="document.getElementById('cmsStatsModal').classList.add('hidden')" class="px-4 py-2.5 rounded-xl bg-gray-200 hover:bg-gray-300 font-bold text-xs text-black">Cancel</button>
          <button onclick="saveStatsCmsModal()" class="px-5 py-2.5 rounded-xl bg-[#8DC63F] hover:bg-[#6DA52A] font-bold text-xs text-white shadow-lg shadow-[#8DC63F]/20"><i class="fa-solid fa-floppy-disk mr-1"></i> Save All Stats</button>
        </div>
      </div>
    </div>

    <script src="/public/js/edit_cms.js"></script>

    <script>
    // === Stats Section Quick-Edit Modal ===
    const STATS_FIELDS = [
      { key: 'stats_members_count', label: 'Members Count',     icon: 'fa-users',              suffix: '+', type: 'number', default: '500' },
      { key: 'stats_members_label', label: 'Members Label',     icon: 'fa-users',              suffix: '',  type: 'text',   default: 'Community Members' },
      { key: 'stats_tools_count',   label: 'Tools Count',       icon: 'fa-screwdriver-wrench', suffix: '+', type: 'number', default: '100' },
      { key: 'stats_tools_label',   label: 'Tools Label',       icon: 'fa-screwdriver-wrench', suffix: '',  type: 'text',   default: 'Tools & Equipment' },
      { key: 'stats_events_count',  label: 'Events Count',      icon: 'fa-calendar-check',     suffix: '+', type: 'number', default: '50' },
      { key: 'stats_events_label',  label: 'Events Label',      icon: 'fa-calendar-check',     suffix: '',  type: 'text',   default: 'Events & Workshops' },
      { key: 'stats_volunteers_count', label: 'Volunteers Count', icon: 'fa-handshake',         suffix: '+', type: 'number', default: '20' },
      { key: 'stats_volunteers_label', label: 'Volunteers Label', icon: 'fa-handshake',         suffix: '',  type: 'text',   default: 'Active Volunteers' },
    ];

    function openStatsCmsModal() {
      const grid = document.getElementById('statsModalGrid');
      grid.innerHTML = '';

      // Group into pairs: [count, label] per stat card
      const cards = [
        { title: '\ud83d\udc65 Community Members', fields: [STATS_FIELDS[0], STATS_FIELDS[1]] },
        { title: '\ud83d\udd27 Tools & Equipment',  fields: [STATS_FIELDS[2], STATS_FIELDS[3]] },
        { title: '\ud83d\udcc5 Events & Workshops', fields: [STATS_FIELDS[4], STATS_FIELDS[5]] },
        { title: '\ud83e\udd1d Active Volunteers',  fields: [STATS_FIELDS[6], STATS_FIELDS[7]] },
      ];

      cards.forEach(card => {
        const cardEl = document.createElement('div');
        cardEl.className = 'bg-gray-50 border border-gray-200 rounded-2xl p-4 space-y-3';
        let html = `<h4 class="text-xs font-extrabold text-gray-700 uppercase tracking-wider border-b border-gray-200 pb-2 mb-1">${card.title}</h4>`;
        card.fields.forEach(f => {
          // Pre-fill from current page DOM
          const domEl = document.querySelector(`[data-cms-key="${f.key}"]`);
          const currentVal = domEl ? domEl.innerText.replace(/[+]/g,'').trim() : f.default;
          html += `
            <div>
              <label class="block text-gray-500 text-[10px] font-semibold uppercase mb-1">${f.label}</label>
              <div class="flex items-center gap-2">
                <input
                  type="${f.type}"
                  id="stats_field_${f.key}"
                  value="${currentVal}"
                  class="flex-grow bg-white border border-gray-300 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:border-[#8DC63F] transition-colors"
                  placeholder="${f.default}"
                >
                ${f.suffix ? `<span class="text-gray-400 font-bold text-sm">${f.suffix}</span>` : ''}
              </div>
            </div>
          `;
        });
        cardEl.innerHTML = html;
        grid.appendChild(cardEl);
      });

      document.getElementById('cmsStatsModal').classList.remove('hidden');
    }

    function saveStatsCmsModal() {
      const saveBtn = document.querySelector('#cmsStatsModal button[onclick="saveStatsCmsModal()"]');
      if (saveBtn) { saveBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-1"></i> Saving...'; saveBtn.disabled = true; }

      const promises = STATS_FIELDS.map(f => {
        const input = document.getElementById(`stats_field_${f.key}`);
        if (!input) return Promise.resolve();
        const val = input.value.trim();
        const fd = new FormData();
        fd.append('key', f.key);
        fd.append('value', val);
        return fetch('/api.php?action=update-cms-text', { method: 'POST', body: fd }).then(r => r.json());
      });

      Promise.all(promises).then(results => {
        // Update DOM values live without page reload
        STATS_FIELDS.forEach(f => {
          const input = document.getElementById(`stats_field_${f.key}`);
          if (!input) return;
          const domEl = document.querySelector(`[data-cms-key="${f.key}"]`);
          if (domEl) {
            // Preserve the pen icon if present
            const icon = domEl.querySelector('.cms-edit-btn');
            domEl.innerText = input.value.trim();
            if (icon) domEl.appendChild(icon);
            // Also update data-target for counter animation
            if (domEl.hasAttribute('data-target')) domEl.setAttribute('data-target', input.value.trim());
          }
        });
        document.getElementById('cmsStatsModal').classList.add('hidden');
        if (saveBtn) { saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Save All Stats'; saveBtn.disabled = false; }
        // Show toast
        const toast = document.createElement('div');
        toast.className = 'fixed top-6 right-6 z-[999999] bg-white border border-[#8DC63F] text-black px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-2 transform opacity-0 transition-all duration-300 font-semibold text-xs';
        toast.innerHTML = '<i class="fa-solid fa-circle-check text-[#8DC63F] text-sm"></i> Stats section updated live!';
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '1'; }, 50);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 2500);
      }).catch(err => {
        alert('Error saving stats: ' + err);
        if (saveBtn) { saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Save All Stats'; saveBtn.disabled = false; }
      });
    }
    </script>
  <?php endif; ?>

</body>
</html>

