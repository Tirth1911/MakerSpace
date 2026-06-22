<?php
/**
 * Resources Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Fetch Resources
$resources = [];
$categories = [
    'Electronics', 'Robotics', '3D Printing', 'Programming', 
    'Cybersecurity', 'IoT', 'AI & ML', 'Mechanical Design'
];

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM resources ORDER BY upload_date DESC");
        $stmt->execute();
        $resources = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback
    }
}

// Fallbacks if empty
if (empty($resources)) {
    $resources = [
        ['id' => 1, 'title' => 'MakerSpace Safety Manual v2', 'description' => 'Essential laboratory safety guidelines for operating CNC routers, 3D printers, and soldering stations.', 'category' => '3D Printing', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?auto=format&fit=crop&w=300&q=80', 'author' => 'Rajesh Patel', 'upload_date' => '2026-05-10', 'downloads_count' => 12],
        ['id' => 2, 'title' => 'Beginners Guide to Arduino Microcontrollers', 'description' => 'Complete starting handbook covering pin configurations, code structures, and simple sensory outputs.', 'category' => 'Robotics', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1608248597481-496100c80836?auto=format&fit=crop&w=300&q=80', 'author' => 'Pankaj Shah', 'upload_date' => '2026-05-15', 'downloads_count' => 45],
        ['id' => 3, 'title' => 'KiCad 7 Schematic Capture Best Practices', 'description' => 'A guide to layout structures, hierarchical sheets, and rule checking before routing high-speed traces.', 'category' => 'Electronics', 'file_url' => '#', 'thumbnail_url' => 'https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=300&q=80', 'author' => 'Nitin Vyas', 'upload_date' => '2026-06-01', 'downloads_count' => 28]
    ];
}
?>

<!-- Hero Banner -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">KNOWLEDGE BASE</span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight">Technical Manuals & Guides</h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto">
      Search and download schematics, firmware modules, and fabrication guidelines written by our mentors.
    </p>
  </div>
</section>

<!-- Search and Filter controls -->
<section class="py-12 bg-[#0c0c0c] border-b border-white/5 sticky top-[80px] z-40 backdrop-blur-xl">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
    
    <!-- Search and Actions Bar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Search Input -->
      <div class="relative w-full sm:max-w-md">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
        <input type="text" id="resourceSearch" oninput="filterResources()" placeholder="Search resource title or description..." class="w-full bg-brandBlack border border-white/10 rounded-2xl pl-11 pr-4 py-3.5 text-white focus:outline-none focus:border-[#8DC63F] text-sm">
      </div>
      
      <!-- Upload trigger for Admin -->
      <?php if ($is_admin): ?>
        <button onclick="openResourceUploadModal()" class="w-full sm:w-auto px-5 py-3.5 rounded-xl font-bold bg-[#8DC63F] text-brandBlack hover:bg-[#73a11c] transition-all text-xs flex items-center justify-center gap-2 shadow-lg shadow-brandGreen/15">
          <i class="fa-solid fa-cloud-arrow-up"></i> Upload New Resource
        </button>
      <?php endif; ?>
    </div>

    <!-- Category Filters Grid -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
      <button onclick="filterCategory('All', this)" class="category-filter-btn px-4.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-[#8DC63F] text-brandBlack shadow-lg shadow-brandGreen/10">
        All Resources
      </button>
      <?php foreach ($categories as $cat): ?>
        <button onclick="filterCategory('<?php echo $cat; ?>', this)" class="category-filter-btn px-4.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-brandBlack border border-white/10 text-gray-400 hover:text-white hover:border-white/20">
          <?php echo $cat; ?>
        </button>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- Resource Cards Grid -->
<section class="py-16 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8" id="resourcesGrid">
      <?php foreach ($resources as $res): ?>
        <div class="glass floating-card rounded-[32px] border border-white/5 overflow-hidden flex flex-col justify-between resource-card group" data-category="<?php echo htmlspecialchars($res['category']); ?>">
          
          <div class="space-y-4">
            <!-- Thumbnail with overlay category badge -->
            <div class="relative aspect-video w-full overflow-hidden bg-gray-900 border-b border-white/5 zoom-container">
              <img src="<?php echo htmlspecialchars($res['thumbnail_url'] ?? 'https://images.unsplash.com/photo-1586075010923-2dd4570fb338?auto=format&fit=crop&w=300&q=80'); ?>" class="w-full h-full object-cover zoom-image" alt="Thumbnail">
              <span class="absolute top-4 left-4 z-20 px-3 py-1 rounded-lg text-[10px] font-bold tracking-wider uppercase bg-brandBlack/85 border border-white/10 text-[#8DC63F]">
                <?php echo htmlspecialchars($res['category']); ?>
              </span>
            </div>
            
            <!-- Description -->
            <div class="p-6 sm:p-8 space-y-3 text-left">
              <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Uploaded <?php echo date('M d, Y', strtotime($res['upload_date'])); ?></span>
              <h3 class="text-lg font-bold text-white group-hover:text-[#8DC63F] transition-colors duration-200 leading-snug font-['Outfit'] resource-title"><?php echo htmlspecialchars($res['title']); ?></h3>
              <p class="text-gray-400 text-xs sm:text-sm leading-relaxed resource-desc">
                <?php echo htmlspecialchars($res['description']); ?>
              </p>
              <div class="flex items-center gap-2 pt-2 border-t border-white/5">
                <span class="text-xs text-gray-500 font-semibold"><i class="fa-solid fa-user-pen mr-1"></i> Author: <?php echo htmlspecialchars($res['author']); ?></span>
              </div>
            </div>
          </div>

          <!-- Card Actions -->
          <div class="px-6 pb-6 sm:px-8 sm:pb-8 flex items-center justify-between border-t border-white/5 pt-4">
            <span class="text-xs text-gray-500 font-bold"><i class="fa-solid fa-cloud-arrow-down mr-1"></i> <span id="download_count_<?php echo $res['id']; ?>"><?php echo intval($res['downloads_count'] ?? 0); ?></span> DLs</span>
            <div class="flex items-center gap-2">
              <button onclick="shareResource('<?php echo htmlspecialchars($res['title']); ?>')" class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white transition-colors" title="Share Guide"><i class="fa-solid fa-share-nodes text-sm"></i></button>
              <a href="<?php echo htmlspecialchars($res['file_url']); ?>" download onclick="handleDownload(<?php echo $res['id']; ?>)" class="px-4 py-2.5 rounded-xl bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-bold text-xs shadow-lg shadow-brandGreen/10 flex items-center gap-1.5 transition-all">
                <i class="fa-solid fa-download"></i> Download
              </a>
            </div>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- Empty search fallback state -->
    <div id="noResourcesState" class="hidden text-center py-20 space-y-4">
      <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mx-auto text-gray-500"><i class="fa-solid fa-magnifying-glass text-2xl"></i></div>
      <h3 class="font-bold text-lg text-white font-['Outfit']">No resources found</h3>
      <p class="text-gray-500 text-sm max-w-sm mx-auto">We couldn't find any documents matching your current filters. Try refining your keyword or select a different category.</p>
    </div>

  </div>
</section>

<!-- Admin Upload Resource Modal (Hidden by default) -->
<?php if ($is_admin): ?>
  <div id="resourceUploadModal" class="fixed inset-0 bg-brandBlack/80 backdrop-blur-sm z-[9999] hidden flex items-center justify-center p-4">
    <div class="bg-brandDarkGray border border-white/10 w-full max-w-lg rounded-[32px] overflow-hidden shadow-2xl flex flex-col">
      <div class="p-6 border-b border-white/5 bg-[#151515] flex justify-between items-center">
        <h3 class="font-extrabold text-lg text-white font-['Outfit']">Upload Resource Guide</h3>
        <button onclick="closeResourceModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>
      <form id="resourceUploadForm" class="p-6 space-y-4 text-sm text-left">
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Resource Title</label>
          <input type="text" name="title" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Description</label>
          <textarea name="description" rows="3" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]"></textarea>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Category</label>
            <select name="category" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]">
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Author Name</label>
            <input type="text" name="author" value="<?php echo htmlspecialchars($user_name); ?>" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]">
          </div>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">File URL / Download Link</label>
          <input type="text" name="file_url" value="#" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]" placeholder="e.g. /uploads/manual.pdf">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Thumbnail Image URL</label>
          <input type="text" name="thumbnail_url" value="https://images.unsplash.com/photo-1586075010923-2dd4570fb338?auto=format&fit=crop&w=300&q=80" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-[#8DC63F]">
        </div>
        <div class="pt-4 flex justify-end gap-3">
          <button type="button" onclick="closeResourceModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 font-bold text-xs">Cancel</button>
          <button type="submit" class="px-5 py-2.5 rounded-xl bg-brandGreen hover:bg-brandGreen/90 text-brandBlack font-bold text-xs shadow-lg shadow-brandGreen/10">Upload Document</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Resource Search & Filtering Script -->
<script>
    let activeCategory = 'All';

    function filterCategory(cat, btn) {
        activeCategory = cat;
        
        // Update filter button styling
        document.querySelectorAll(".category-filter-btn").forEach(b => {
            b.className = "category-filter-btn px-4.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-brandBlack border border-white/10 text-gray-400 hover:text-white hover:border-white/20";
        });
        
        if (cat === 'All') {
            btn.className = "category-filter-btn px-4.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-brandGreen text-brandBlack shadow-lg shadow-brandGreen/10";
        } else {
            btn.className = "category-filter-btn px-4.5 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 bg-brandGreen text-brandBlack shadow-lg shadow-brandGreen/10";
        }

        filterResources();
    }

    function filterResources() {
        const query = document.getElementById("resourceSearch").value.toLowerCase().trim();
        const cards = document.querySelectorAll(".resource-card");
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCat = card.getAttribute("data-category");
            const title = card.querySelector(".resource-title").innerText.toLowerCase();
            const desc = card.querySelector(".resource-desc").innerText.toLowerCase();
            
            const matchCategory = (activeCategory === 'All' || cardCat === activeCategory);
            const matchSearch = (title.includes(query) || desc.includes(query));

            if (matchCategory && matchSearch) {
                card.style.display = "flex";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        // Toggle empty state
        const emptyState = document.getElementById("noResourcesState");
        const grid = document.getElementById("resourcesGrid");
        if (visibleCount === 0) {
            emptyState.classList.remove("hidden");
            grid.classList.add("hidden");
        } else {
            emptyState.classList.add("hidden");
            grid.classList.remove("hidden");
        }
    }

    function handleDownload(id) {
        // Track download in database
        fetch(`/api.php?action=increment-download&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const counter = document.getElementById(`download_count_${id}`);
                    if (counter) {
                        counter.innerText = parseInt(counter.innerText) + 1;
                    }
                }
            })
            .catch(err => console.error("Error logging download:", err));
    }

    function shareResource(title) {
        if (navigator.share) {
            navigator.share({
                title: title,
                text: `Check out this resource manual from Yuvalay MakerSpace: ${title}`,
                url: window.location.href
            }).catch(console.error);
        } else {
            // Fallback: Copy link
            navigator.clipboard.writeText(window.location.href);
            alert("Resource link copied to clipboard!");
        }
    }

    // Modal Operations for Admin
    function openResourceUploadModal() {
        document.getElementById("resourceUploadModal").classList.remove("hidden");
    }

    function closeResourceModal() {
        document.getElementById("resourceUploadModal").classList.add("hidden");
    }

    // Form Submission
    const uploadForm = document.getElementById("resourceUploadForm");
    if (uploadForm) {
        uploadForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(uploadForm);

            fetch("/api.php?action=create-resource", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Document uploaded successfully!");
                    window.location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => console.error(err));
        });
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
