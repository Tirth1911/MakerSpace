<?php
/**
 * Events & Calendar Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Fetch Events
$events = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM events ORDER BY event_date ASC, event_time ASC");
        $stmt->execute();
        $events = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback
    }
}

// Fetch user profile details if logged in to pre-populate multi-step form
$user_profile = [];
if ($is_logged_in && $conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user_profile = $stmt->fetch();
    } catch (PDOException $e) {
        // Fallback
    }
}

// Categories list matching filters
$categories = [
    'Workshops', 'Hackathons', 'Training Programs', 'Meetups', 
    'Competitions', 'Seminars', 'Webinars', 'Community Events'
];
?>

<!-- Calendar JS and QR Code generation scripts -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>

<!-- Hero Section -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">JOIN A SESSION</span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight">Events & Calendar</h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto">
      Join workshops, hackathons, training programs, community meetups and innovation events.
    </p>
  </div>
</section>

<!-- Main Layout Container -->
<section class="py-16 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">

    <!-- Filters Bar -->
    <div class="flex flex-wrap items-center justify-center gap-2 pb-4">
      <button onclick="filterEventCat('All', this)" class="event-filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-brandGreen text-brandBlack shadow-lg shadow-brandGreen/10 transition-all">
        All Events
      </button>
      <?php foreach ($categories as $cat): ?>
        <button onclick="filterEventCat('<?php echo $cat; ?>', this)" class="event-filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-brandDarkGray border border-white/5 text-gray-400 hover:text-white hover:border-white/10 transition-all">
          <?php echo $cat; ?>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Layout Toggle: Cards vs Calendar -->
    <div class="flex justify-between items-center border-b border-white/5 pb-6">
      <div class="text-left">
        <h2 class="text-xl font-bold font-['Outfit']" id="section-view-title">Upcoming Schedule</h2>
        <p class="text-xs text-gray-500 mt-1">Showing all events and workshops.</p>
      </div>
      <div class="flex bg-brandDarkGray p-1 rounded-xl border border-white/5">
        <button onclick="toggleViewMode('grid')" id="viewModeGridBtn" class="px-4 py-2 text-xs font-semibold rounded-lg bg-brandGreen text-brandBlack flex items-center gap-1.5 transition-all">
          <i class="fa-solid fa-grid-2 text-xs"></i> List View
        </button>
        <button onclick="toggleViewMode('calendar')" id="viewModeCalendarBtn" class="px-4 py-2 text-xs font-semibold rounded-lg text-gray-400 hover:text-white flex items-center gap-1.5 transition-all">
          <i class="fa-solid fa-calendar-days text-xs"></i> Calendar View
        </button>
      </div>
    </div>

    <!-- Cards Grid Container -->
    <div id="eventsGridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <?php foreach ($events as $evt):
        $is_full = ($evt['available_seats'] <= 0);
        $deadline_passed = (strtotime($evt['registration_deadline']) < time());
        
        // Color coding logic
        $status_color = 'badge-upcoming';
        $evt_status = 'Upcoming';
        if ($evt['status'] === 'completed') {
            $status_color = 'badge-completed';
            $evt_status = 'Completed';
        } elseif ($evt['status'] === 'ongoing') {
            $status_color = 'badge-ongoing';
            $evt_status = 'Ongoing';
        }
      ?>
        <div class="glass floating-card rounded-[32px] border border-white/5 overflow-hidden flex flex-col justify-between event-card-wrapper" data-category="<?php echo htmlspecialchars($evt['category']); ?>">
          
          <div class="space-y-4">
            <!-- Banner image with badge overlay -->
            <div class="relative aspect-video w-full overflow-hidden bg-gray-900 border-b border-white/5 zoom-container">
              <img src="<?php echo htmlspecialchars($evt['banner_image'] ?? 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80'); ?>" class="w-full h-full object-cover zoom-image" alt="Event Banner">
              <span class="absolute top-4 left-4 z-20 px-3 py-1 rounded-lg text-[10px] font-bold tracking-wider uppercase bg-brandBlack/85 border border-white/10 text-[#8DC63F]">
                <?php echo htmlspecialchars($evt['category']); ?>
              </span>
              <span class="absolute top-4 right-4 z-20 px-3 py-1 rounded-lg text-[10px] font-bold tracking-wider uppercase <?php echo $status_color; ?>">
                <?php echo $evt_status; ?>
              </span>
            </div>

            <!-- Details -->
            <div class="p-6 sm:p-8 space-y-3 text-left">
              <div class="flex items-center gap-3 text-xs text-gray-500">
                <span><i class="fa-regular fa-calendar mr-1"></i> <?php echo date('M d, Y', strtotime($evt['event_date'])); ?></span>
                <span><i class="fa-regular fa-clock mr-1"></i> <?php echo date('h:i A', strtotime($evt['event_time'])); ?></span>
              </div>
              
              <h3 class="text-lg font-bold text-white leading-snug font-['Outfit']"><?php echo htmlspecialchars($evt['title']); ?></h3>
              <p class="text-gray-400 text-xs sm:text-sm line-clamp-2 leading-relaxed">
                <?php echo htmlspecialchars($evt['description']); ?>
              </p>
              
              <div class="pt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-gray-500 border-t border-white/5">
                <span><i class="fa-solid fa-location-dot text-brandGreen mr-1"></i> <?php echo htmlspecialchars($evt['venue']); ?></span>
                <span><i class="fa-solid fa-users text-brandGreen mr-1"></i> <?php echo $evt['available_seats']; ?> / <?php echo $evt['capacity']; ?> Seats</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="px-6 pb-6 sm:px-8 sm:pb-8 flex gap-3">
            <button onclick="openDetailsModal(<?php echo htmlspecialchars(json_encode($evt)); ?>)" class="flex-grow text-center py-3.5 rounded-xl font-bold bg-brandDarkGray hover:bg-white/5 border border-white/10 text-white transition-all text-xs">
              View Details
            </button>
            <?php if ($deadline_passed): ?>
              <button disabled class="flex-grow text-center py-3.5 rounded-xl font-bold bg-white/5 text-gray-500 border border-transparent text-xs cursor-not-allowed">
                Closed
              </button>
            <?php elseif ($is_full): ?>
              <button disabled class="flex-grow text-center py-3.5 rounded-xl font-bold bg-white/5 text-gray-500 border border-transparent text-xs cursor-not-allowed">
                Full
              </button>
            <?php else: ?>
              <button onclick="openRegistrationFlow(<?php echo $evt['id']; ?>, '<?php echo htmlspecialchars(addslashes($evt['title'])); ?>')" class="flex-grow text-center py-3.5 rounded-xl font-bold bg-brandGreen text-brandBlack hover:bg-brandDarkGreen hover:shadow-lg hover:shadow-brandGreen/10 transition-all text-xs">
                Register Now
              </button>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    </div>

    <!-- Interactive Calendar Container -->
    <div id="eventsCalendarContainer" class="hidden">
      <div id="calendar" class="text-left"></div>
    </div>

    <!-- Empty State -->
    <div id="noEventsState" class="hidden text-center py-20 space-y-4">
      <div class="w-16 h-16 rounded-full bg-white/5 flex items-center justify-center mx-auto text-gray-500"><i class="fa-solid fa-calendar-xmark text-2xl"></i></div>
      <h3 class="font-bold text-lg text-white font-['Outfit']">No events found</h3>
      <p class="text-gray-500 text-sm max-w-sm mx-auto">We couldn't find any events under this category. Check back later for updates!</p>
    </div>

  </div>
</section>

<!-- EVENT DETAILS MODAL (Glassmorphism layout) -->
<div id="detailsModal" class="fixed inset-0 bg-brandBlack/85 backdrop-blur-md z-[9999] hidden flex items-center justify-center p-4">
  <div class="bg-brandDarkGray border border-white/10 w-full max-w-3xl rounded-[32px] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
    <!-- Header Banner -->
    <div class="relative h-48 bg-gray-900 border-b border-white/5">
      <img id="modalBanner" src="" class="w-full h-full object-cover opacity-60" alt="Event Banner">
      <div class="absolute inset-0 bg-gradient-to-t from-brandDarkGray via-brandDarkGray/40 to-transparent"></div>
      <button onclick="closeDetailsModal()" class="absolute top-4 right-4 z-30 p-2.5 rounded-full bg-black/60 hover:bg-brandGreen text-white hover:text-brandBlack transition-all"><i class="fa-solid fa-xmark text-sm"></i></button>
      
      <!-- Category Badge -->
      <span id="modalCategory" class="absolute bottom-4 left-6 z-20 px-3 py-1 rounded-lg text-[10px] font-bold tracking-wider uppercase bg-brandBlack/80 border border-white/10 text-brandGreen"></span>
    </div>

    <!-- Modal Content body -->
    <div class="p-6 sm:p-8 overflow-y-auto space-y-6 text-left flex-grow">
      
      <div class="space-y-2">
        <h2 id="modalTitle" class="text-2xl sm:text-3xl font-extrabold text-white font-['Outfit'] leading-tight"></h2>
        <div class="flex flex-wrap items-center gap-y-2 gap-x-4 text-xs text-gray-500">
          <span><i class="fa-regular fa-calendar-days text-brandGreen mr-1"></i> Date: <strong id="modalDate" class="text-white"></strong></span>
          <span><i class="fa-regular fa-clock text-brandGreen mr-1"></i> Time: <strong id="modalTime" class="text-white"></strong></span>
          <span><i class="fa-solid fa-user-tie text-brandGreen mr-1"></i> Organizer: <strong id="modalOrganizer" class="text-white"></strong></span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        
        <!-- Left details panel -->
        <div class="md:col-span-8 space-y-6">
          <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-brandGreen">Description</h4>
            <p id="modalDescription" class="text-gray-400 text-sm leading-relaxed"></p>
          </div>

          <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-brandGreen">Schedule & Agenda</h4>
            <div id="modalAgenda" class="text-gray-400 text-sm whitespace-pre-line leading-relaxed"></div>
          </div>
          
          <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-brandGreen">Requirements</h4>
            <div id="modalRequirements" class="text-gray-400 text-sm whitespace-pre-line leading-relaxed"></div>
          </div>
        </div>

        <!-- Right info panel -->
        <div class="md:col-span-4 space-y-6">
          <div class="p-5 bg-brandBlack rounded-2xl border border-white/5 space-y-4">
            <h4 class="text-xs font-bold uppercase tracking-wider text-white border-b border-white/5 pb-2">Venue</h4>
            <p id="modalVenue" class="text-sm font-semibold text-gray-300"></p>
            <div class="aspect-video w-full rounded-xl overflow-hidden bg-gray-900 border border-white/10 relative">
              <iframe id="modalMapFrame" src="" class="w-full h-full border-0 grayscale invert opacity-70" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>

          <div class="p-5 bg-brandBlack rounded-2xl border border-white/5 text-xs text-gray-500 space-y-2">
            <div><i class="fa-solid fa-chair text-brandGreen mr-1"></i> Available Seats: <strong id="modalSeats" class="text-white"></strong></div>
            <div><i class="fa-solid fa-hourglass-end text-brandGreen mr-1"></i> Register Before: <strong id="modalDeadline" class="text-white"></strong></div>
          </div>
        </div>

      </div>

    </div>

    <!-- Footer buttons -->
    <div class="p-6 border-t border-white/5 bg-[#151515] flex flex-wrap justify-between items-center gap-4">
      <button id="modalShareBtn" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold text-gray-300 flex items-center gap-1.5"><i class="fa-solid fa-share-nodes"></i> Share</button>
      <div class="flex gap-3">
        <button onclick="closeDetailsModal()" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-semibold">Close</button>
        <button id="modalRegisterBtn" class="px-6 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-bold text-xs hover:bg-brandDarkGreen shadow-lg shadow-brandGreen/10">Register Now</button>
      </div>
    </div>

  </div>
</div>

<!-- MULTI STEP EVENT REGISTRATION FLOW (Glassmorphism Modal) -->
<div id="registrationModal" class="fixed inset-0 bg-brandBlack/90 backdrop-blur-md z-[99999] hidden flex items-center justify-center p-4">
  <div class="bg-brandDarkGray border border-white/10 w-full max-w-xl rounded-[32px] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
    
    <!-- Header -->
    <div class="p-6 border-b border-white/5 bg-[#151515] flex justify-between items-center">
      <div>
        <h3 class="font-extrabold text-lg text-white font-['Outfit']">Event Registration</h3>
        <p class="text-[11px] text-gray-500 mt-0.5" id="regFormEventTitle"></p>
      </div>
      <button onclick="closeRegistrationFlow()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>

    <!-- Step Progress Indicator -->
    <div class="px-8 py-4 bg-brandBlack border-b border-white/5 flex justify-between items-center">
      <div class="flex items-center w-full justify-between max-w-sm mx-auto relative">
        <!-- Connecting Line -->
        <div class="absolute h-0.5 bg-white/10 left-0 right-0 top-1/2 -translate-y-1/2 z-0"></div>
        <div class="absolute h-0.5 bg-brandGreen left-0 top-1/2 -translate-y-1/2 z-0" id="stepProgressLine" style="width: 0%;"></div>

        <!-- Dots -->
        <div class="z-10 flex flex-col items-center">
          <div class="w-7 h-7 rounded-full bg-brandDarkGray border border-white/15 flex items-center justify-center text-xs font-bold text-gray-400 step-dot active" id="dot_1">1</div>
          <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">Profile</span>
        </div>
        <div class="z-10 flex flex-col items-center">
          <div class="w-7 h-7 rounded-full bg-brandDarkGray border border-white/15 flex items-center justify-center text-xs font-bold text-gray-400 step-dot" id="dot_2">2</div>
          <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">Academic</span>
        </div>
        <div class="z-10 flex flex-col items-center">
          <div class="w-7 h-7 rounded-full bg-brandDarkGray border border-white/15 flex items-center justify-center text-xs font-bold text-gray-400 step-dot" id="dot_3">3</div>
          <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">Career</span>
        </div>
        <div class="z-10 flex flex-col items-center">
          <div class="w-7 h-7 rounded-full bg-brandDarkGray border border-white/15 flex items-center justify-center text-xs font-bold text-gray-400 step-dot" id="dot_4">4</div>
          <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">Questions</span>
        </div>
        <div class="z-10 flex flex-col items-center">
          <div class="w-7 h-7 rounded-full bg-brandDarkGray border border-white/15 flex items-center justify-center text-xs font-bold text-gray-400 step-dot" id="dot_5">5</div>
          <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mt-1">Confirm</span>
        </div>
      </div>
    </div>

    <!-- Multi-step Form body -->
    <form id="multiStepRegForm" class="flex-grow overflow-y-auto p-6 sm:p-8 text-left space-y-6">
      <input type="hidden" name="event_id" id="regFormEventId">

      <!-- STEP 1: Personal Information -->
      <div id="step_pane_1" class="space-y-4">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider border-b border-white/5 pb-2 mb-4">Step 1: Personal Information</h4>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Full Name</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($user_profile['name'] ?? ''); ?>" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Email Address</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>" required readonly class="w-full bg-white/5 border border-white/5 rounded-xl p-3 text-gray-400 focus:outline-none">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Mobile Number</label>
            <input type="text" name="mobile" value="<?php echo htmlspecialchars($user_profile['mobile'] ?? ''); ?>" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Date of Birth</label>
            <input type="date" name="dob" value="<?php echo htmlspecialchars($user_profile['dob'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          </div>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Gender</label>
          <select name="gender" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
            <option value="Male" <?php echo (isset($user_profile['gender']) && $user_profile['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (isset($user_profile['gender']) && $user_profile['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Other" <?php echo (isset($user_profile['gender']) && $user_profile['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
      </div>

      <!-- STEP 2: Academic Information -->
      <div id="step_pane_2" class="space-y-4 hidden">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider border-b border-white/5 pb-2 mb-4">Step 2: Academic Information</h4>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">College / University Name</label>
          <input type="text" name="college" value="<?php echo htmlspecialchars($user_profile['college'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Maharaja Sayajirao University">
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Branch / Stream</label>
            <input type="text" name="branch" value="<?php echo htmlspecialchars($user_profile['branch'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Mechanical Engineering">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Current Semester</label>
            <input type="text" name="semester" value="<?php echo htmlspecialchars($user_profile['semester'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. 6th">
          </div>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Student Enrollment ID</label>
          <input type="text" name="student_id" value="<?php echo htmlspecialchars($user_profile['student_id'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Roll No. 45">
        </div>
      </div>

      <!-- STEP 3: Professional Information -->
      <div id="step_pane_3" class="space-y-4 hidden">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider border-b border-white/5 pb-2 mb-4">Step 3: Professional Details</h4>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Current Occupation</label>
          <select name="occupation" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
            <option value="Student" <?php echo (isset($user_profile['occupation']) && $user_profile['occupation'] == 'Student') ? 'selected' : ''; ?>>Student</option>
            <option value="Professional" <?php echo (isset($user_profile['occupation']) && $user_profile['occupation'] == 'Professional') ? 'selected' : ''; ?>>Working Professional</option>
            <option value="Researcher" <?php echo (isset($user_profile['occupation']) && $user_profile['occupation'] == 'Researcher') ? 'selected' : ''; ?>>Researcher</option>
            <option value="Other" <?php echo (isset($user_profile['occupation']) && $user_profile['occupation'] == 'Other') ? 'selected' : ''; ?>>Other</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Technical Skills</label>
          <input type="text" name="skills" value="<?php echo htmlspecialchars($user_profile['skills'] ?? ''); ?>" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Arduino, KiCad, 3D Modeling, Python">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Experience Level</label>
          <select name="experience_level" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
            <option value="Beginner" <?php echo (isset($user_profile['experience_level']) && $user_profile['experience_level'] == 'Beginner') ? 'selected' : ''; ?>>Beginner (No hardware experience)</option>
            <option value="Intermediate" <?php echo (isset($user_profile['experience_level']) && $user_profile['experience_level'] == 'Intermediate') ? 'selected' : ''; ?>>Intermediate (Built minor kits)</option>
            <option value="Advanced" <?php echo (isset($user_profile['experience_level']) && $user_profile['experience_level'] == 'Advanced') ? 'selected' : ''; ?>>Advanced (Configured custom firmware/PCBs)</option>
          </select>
        </div>
      </div>

      <!-- STEP 4: Event Questions -->
      <div id="step_pane_4" class="space-y-4 hidden">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider border-b border-white/5 pb-2 mb-4">Step 4: Event Specific Questions</h4>
        <div id="dynamic_questions_container" class="space-y-4">
          <!-- Populated dynamically via JS -->
        </div>
      </div>

      <!-- STEP 5: Confirmation -->
      <div id="step_pane_5" class="space-y-4 hidden">
        <h4 class="text-sm font-bold text-white uppercase tracking-wider border-b border-white/5 pb-2 mb-4">Step 5: Review & Confirm</h4>
        
        <div class="bg-brandBlack border border-white/5 rounded-2xl p-5 space-y-3 text-xs text-gray-400">
          <div class="flex justify-between border-b border-white/5 pb-2">
            <span class="font-bold">Participant Name</span>
            <span class="text-white" id="summary_name"></span>
          </div>
          <div class="flex justify-between border-b border-white/5 pb-2">
            <span class="font-bold">Mobile Number</span>
            <span class="text-white" id="summary_mobile"></span>
          </div>
          <div class="flex justify-between border-b border-white/5 pb-2">
            <span class="font-bold">College / Stream</span>
            <span class="text-white" id="summary_college"></span>
          </div>
          <div class="flex justify-between pb-1">
            <span class="font-bold">Target Session</span>
            <span class="text-brandGreen font-bold" id="summary_event"></span>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <label class="flex items-start gap-3 cursor-pointer text-xs text-gray-500">
            <input type="checkbox" required class="mt-0.5 accent-brandGreen">
            <span>I agree to Yuvalay MakerSpace's Terms and Conditions regarding safety guidelines and equipment handling.</span>
          </label>
          <label class="flex items-start gap-3 cursor-pointer text-xs text-gray-500">
            <input type="checkbox" required class="mt-0.5 accent-brandGreen">
            <span>I authorize the organizers to capture photos/videos during the sessions for promotional channels.</span>
          </label>
        </div>
      </div>

      <!-- STEP 6: SUCCESS PANEL (Dynamic overlay inside modal) -->
      <div id="step_pane_6" class="space-y-6 hidden text-center py-6">
        <div class="w-16 h-16 rounded-full bg-brandGreen/10 border border-brandGreen/25 text-brandGreen flex items-center justify-center mx-auto text-2xl animate-bounce">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="space-y-1">
          <h4 class="text-2xl font-extrabold text-white font-['Outfit']">Registration Successful!</h4>
          <p class="text-xs text-gray-500">Your ticket has been generated below.</p>
        </div>

        <!-- Rendered Ticket Detail card -->
        <div class="bg-brandBlack border border-white/5 rounded-3xl p-6 max-w-sm mx-auto text-left relative overflow-hidden space-y-4">
          <div class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-brandGreen/5 pointer-events-none"></div>
          
          <div class="flex justify-between border-b border-white/5 pb-2.5 items-center">
            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">TICKET ID</span>
            <span class="text-xs text-brandGreen font-extrabold tracking-wider" id="success_ticket_id"></span>
          </div>

          <div class="space-y-1">
            <span class="text-[9px] text-gray-500 uppercase tracking-wider font-bold">SESSION TITLE</span>
            <h5 class="text-sm font-bold text-white leading-snug" id="success_event_title"></h5>
          </div>

          <div class="space-y-1">
            <span class="text-[9px] text-gray-500 uppercase tracking-wider font-bold">PARTICIPANT</span>
            <p class="text-xs font-semibold text-gray-300" id="success_user_name"></p>
          </div>

          <!-- Canvas container for QR Code render -->
          <div class="flex justify-center py-2" id="qrcode_container">
            <!-- QR code SVG/canvas will be appended here dynamically -->
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-2 max-w-sm mx-auto pt-2">
          <a href="#" id="downloadTicketLink" class="w-full text-center py-3 bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/10 transition-all flex items-center justify-center gap-1.5">
            <i class="fa-solid fa-file-pdf"></i> Download Ticket PDF
          </a>
          <div class="grid grid-cols-2 gap-2">
            <a href="https://calendar.google.com" target="_blank" class="text-center py-2.5 bg-brandDarkGray hover:bg-white/5 border border-white/5 text-xs text-gray-300 font-bold rounded-xl transition-all">
              Google Calendar
            </a>
            <a href="/my-registrations.php" class="text-center py-2.5 bg-brandDarkGray hover:bg-white/5 border border-white/5 text-xs text-brandGreen font-bold rounded-xl transition-all">
              My Registrations
            </a>
          </div>
        </div>
      </div>

    </form>

    <!-- Footer Controls -->
    <div class="p-6 border-t border-white/5 bg-[#151515] flex justify-between items-center" id="regModalFooter">
      <button type="button" onclick="closeRegistrationFlow()" class="px-5 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold">Cancel</button>
      <div class="flex gap-3">
        <button type="button" id="prevStepBtn" onclick="navigateStep(-1)" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold hidden"><i class="fa-solid fa-chevron-left mr-1"></i> Prev</button>
        <button type="button" id="nextStepBtn" onclick="navigateStep(1)" class="px-5 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-bold text-xs hover:bg-[#73a11c] shadow-lg shadow-brandGreen/10">Next <i class="fa-solid fa-chevron-right ml-1"></i></button>
      </div>
    </div>

  </div>
</div>

<!-- Calendar Script Configurations -->
<script>
    let currentStep = 1;
    const totalSteps = 5;
    let selectedEventId = 0;
    let selectedEventTitle = '';

    // Toggle grid list vs calendar views
    function toggleViewMode(mode) {
        const grid = document.getElementById("eventsGridContainer");
        const cal = document.getElementById("eventsCalendarContainer");
        const title = document.getElementById("section-view-title");
        
        const gridBtn = document.getElementById("viewModeGridBtn");
        const calBtn = document.getElementById("viewModeCalendarBtn");

        if (mode === 'grid') {
            grid.classList.remove("hidden");
            cal.classList.add("hidden");
            title.innerText = "Upcoming Schedule";
            
            gridBtn.className = "px-4 py-2 text-xs font-semibold rounded-lg bg-brandGreen text-brandBlack flex items-center gap-1.5 transition-all";
            calBtn.className = "px-4 py-2 text-xs font-semibold rounded-lg text-gray-400 hover:text-white flex items-center gap-1.5 transition-all";
        } else {
            grid.classList.add("hidden");
            cal.classList.remove("hidden");
            title.innerText = "Interactive Calendar";
            
            gridBtn.className = "px-4 py-2 text-xs font-semibold rounded-lg text-gray-400 hover:text-white flex items-center gap-1.5 transition-all";
            calBtn.className = "px-4 py-2 text-xs font-semibold rounded-lg bg-brandGreen text-brandBlack flex items-center gap-1.5 transition-all";
            
            // Re-render full calendar to fit dimensions
            if (window.calendarObj) {
                window.calendarObj.render();
            }
        }
    }

    // Category button filter trigger
    function filterEventCat(cat, btn) {
        // Toggle selected styling
        document.querySelectorAll(".event-filter-btn").forEach(b => {
            b.className = "event-filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-brandDarkGray border border-white/5 text-gray-400 hover:text-white hover:border-white/10 transition-all";
        });
        btn.className = "event-filter-btn px-4 py-2.5 rounded-xl text-xs font-semibold whitespace-nowrap bg-brandGreen text-brandBlack shadow-lg shadow-brandGreen/10 transition-all";

        const cards = document.querySelectorAll(".event-card-wrapper");
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCat = card.getAttribute("data-category");
            if (cat === 'All' || cardCat === cat) {
                card.style.display = "flex";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        // Update Calendar Filter
        if (window.calendarObj) {
            window.calendarObj.getEventSources().forEach(src => src.refetch());
        }

        // Toggle empty state
        const emptyState = document.getElementById("noEventsState");
        const grid = document.getElementById("eventsGridContainer");
        if (visibleCount === 0 && !grid.classList.contains("hidden")) {
            emptyState.classList.remove("hidden");
            grid.classList.add("hidden");
        } else {
            emptyState.classList.add("hidden");
            if (!grid.classList.contains("hidden")) {
                grid.classList.remove("hidden");
            }
        }
    }

    // FullCalendar configuration
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            // Transform PHP events to JSON array
            var eventsData = [
                <?php foreach ($events as $evt):
                  $color = '#8DC63F'; // green
                  if ($evt['status'] === 'completed') $color = '#9CA3AF'; // gray
                  elseif ($evt['status'] === 'ongoing') $color = '#3B82F6'; // blue
                ?>
                {
                    id: '<?php echo $evt['id']; ?>',
                    title: '<?php echo addslashes($evt['title']); ?>',
                    start: '<?php echo $evt['event_date']; ?>T<?php echo $evt['event_time']; ?>',
                    backgroundColor: '<?php echo $color; ?>',
                    borderColor: '<?php echo $color; ?>',
                    extendedProps: <?php echo json_encode($evt); ?>
                },
                <?php endforeach; ?>
            ];

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
                },
                events: function(info, successCallback, failureCallback) {
                    // Filter based on active category
                    const activeBtn = document.querySelector(".event-filter-btn.bg-brandGreen");
                    const activeCat = activeBtn ? activeBtn.innerText.trim() : 'All Events';
                    
                    let filtered = eventsData;
                    if (activeCat !== 'All Events') {
                        filtered = eventsData.filter(e => e.extendedProps.category === activeCat);
                    }
                    successCallback(filtered);
                },
                eventClick: function(info) {
                    openDetailsModal(info.event.extendedProps);
                }
            });

            window.calendarObj = calendar;
        }
    });

    // Details Modal Operations
    function openDetailsModal(evt) {
        document.getElementById("modalTitle").innerText = evt.title;
        document.getElementById("modalDescription").innerText = evt.description;
        document.getElementById("modalDate").innerText = evt.event_date;
        document.getElementById("modalTime").innerText = evt.event_time;
        document.getElementById("modalVenue").innerText = evt.venue;
        document.getElementById("modalOrganizer").innerText = evt.organizer;
        document.getElementById("modalSeats").innerText = `${evt.available_seats} / ${evt.capacity} seats remaining`;
        document.getElementById("modalDeadline").innerText = evt.registration_deadline;
        document.getElementById("modalCategory").innerText = evt.category;
        document.getElementById("modalBanner").src = evt.banner_image || 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80';
        document.getElementById("modalAgenda").innerText = evt.agenda || 'TBA';
        document.getElementById("modalRequirements").innerText = evt.requirements || 'No specific tools required.';
        
        // Load default maps embed
        const mapsEmbed = evt.google_map_url || "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3691.012351280385!2d73.1895781!3d22.3153664!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395fc88dfc5c7865%3A0xa6ebbb363a0d5b!2sVadodara%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin";
        document.getElementById("modalMapFrame").src = mapsEmbed;

        // Share button logic
        document.getElementById("modalShareBtn").onclick = () => shareResource(evt.title);

        // Register Now button target
        const registerBtn = document.getElementById("modalRegisterBtn");
        
        const isFull = (evt.available_seats <= 0);
        const dateDeadline = new Date(evt.registration_deadline);
        const dateNow = new Date();

        if (dateDeadline < dateNow) {
            registerBtn.disabled = true;
            registerBtn.innerText = "Deadline Passed";
            registerBtn.className = "px-6 py-2.5 rounded-xl bg-white/5 text-gray-500 text-xs cursor-not-allowed";
        } else if (isFull) {
            registerBtn.disabled = true;
            registerBtn.innerText = "Session Full";
            registerBtn.className = "px-6 py-2.5 rounded-xl bg-white/5 text-gray-500 text-xs cursor-not-allowed";
        } else {
            registerBtn.disabled = false;
            registerBtn.innerText = "Register Now";
            registerBtn.className = "px-6 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-bold text-xs hover:bg-brandDarkGreen shadow-lg shadow-brandGreen/10";
            registerBtn.onclick = () => {
                closeDetailsModal();
                openRegistrationFlow(evt.id, evt.title);
            };
        }

        document.getElementById("detailsModal").classList.remove("hidden");
    }

    function closeDetailsModal() {
        document.getElementById("detailsModal").classList.add("hidden");
    }

    // Multi step Form handler
    function openRegistrationFlow(evtId, evtTitle) {
        // Require login check first
        <?php if (!$is_logged_in): ?>
          window.location.href = "/login.php?redirect=" + encodeURIComponent(window.location.pathname);
          return;
        <?php endif; ?>

        selectedEventId = evtId;
        selectedEventTitle = evtTitle;

        document.getElementById("regFormEventId").value = evtId;
        document.getElementById("regFormEventTitle").innerText = evtTitle;

        // Fetch dynamic questions for Step 4
        const dynamicContainer = document.getElementById("dynamic_questions_container");
        dynamicContainer.innerHTML = '<div class="text-center text-gray-500 py-4"><i class="fa-solid fa-circle-notch animate-spin mr-2"></i> Loading event-specific questions...</div>';

        fetch("/api.php?action=get-event-fields&event_id=" + evtId)
            .then(res => res.json())
            .then(data => {
                dynamicContainer.innerHTML = "";
                if (data.status === "success" && data.fields && data.fields.length > 0) {
                    data.fields.forEach(field => {
                        const requiredAttr = field.is_required == 1 ? "required" : "";
                        const requiredIndicator = field.is_required == 1 ? '<span class="text-red-500">*</span>' : "";
                        
                        let inputHtml = "";
                        if (field.field_type === 'textarea') {
                            inputHtml = `<textarea name="${field.field_name}" ${requiredAttr} rows="2" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>`;
                        } else if (field.field_type === 'select') {
                            const options = (field.field_options || "").split(",").map(o => o.trim()).filter(o => o);
                            let optionsHtml = "";
                            options.forEach(opt => {
                                optionsHtml += `<option value="${opt}">${opt}</option>`;
                            });
                            inputHtml = `<select name="${field.field_name}" ${requiredAttr} class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">${optionsHtml}</select>`;
                        } else if (field.field_type === 'checkbox') {
                            const options = (field.field_options || "").split(",").map(o => o.trim()).filter(o => o);
                            let optionsHtml = "";
                            options.forEach(opt => {
                                optionsHtml += `
                                    <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-300">
                                        <input type="checkbox" name="${field.field_name}[]" value="${opt}" class="accent-brandGreen">
                                        <span>${opt}</span>
                                    </label>
                                `;
                            });
                            inputHtml = `<div class="space-y-1.5 pt-1">${optionsHtml}</div>`;
                        } else {
                            // text, email, number
                            inputHtml = `<input type="${field.field_type}" name="${field.field_name}" ${requiredAttr} class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">`;
                        }
                        
                        const wrapper = document.createElement("div");
                        wrapper.className = "space-y-1";
                        wrapper.innerHTML = `
                            <label class="block text-gray-400 font-semibold mb-1">${field.field_label} ${requiredIndicator}</label>
                            ${inputHtml}
                        `;
                        dynamicContainer.appendChild(wrapper);
                    });
                } else {
                    // Fallback to default event questions
                    dynamicContainer.innerHTML = `
                        <div>
                          <label class="block text-gray-400 font-semibold mb-1">Why do you want to attend this event? <span class="text-red-500">*</span></label>
                          <textarea name="why_attend" required rows="2" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>
                        </div>
                        <div>
                          <label class="block text-gray-400 font-semibold mb-1">What are your expectations from this session? <span class="text-red-500">*</span></label>
                          <textarea name="expectations" required rows="2" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>
                        </div>
                        <div>
                          <label class="block text-gray-400 font-semibold mb-1">Have you built any similar projects in the past? (Describe briefly)</label>
                          <textarea name="previous_experience" rows="2" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>
                        </div>
                    `;
                }
            })
            .catch(err => {
                console.error("Error loading event fields:", err);
                dynamicContainer.innerHTML = '<div class="text-red-400 text-xs py-2">Error loading registration questions. Please try again.</div>';
            });

        currentStep = 1;
        showStep(1);
        
        // Reset success state
        document.getElementById("step_pane_6").classList.add("hidden");
        document.getElementById("regModalFooter").classList.remove("hidden");

        document.getElementById("registrationModal").classList.remove("hidden");
    }

    function closeRegistrationFlow() {
        document.getElementById("registrationModal").classList.add("hidden");
    }

    function showStep(stepNum) {
        // Hide all panes
        for (let i = 1; i <= totalSteps; i++) {
            document.getElementById(`step_pane_${i}`).classList.add("hidden");
            document.getElementById(`dot_${i}`).classList.remove("active");
        }

        // Show selected pane
        document.getElementById(`step_pane_${stepNum}`).classList.remove("hidden");
        document.getElementById(`dot_${stepNum}`).classList.add("active");

        // Update progress bar percentage width
        const progressPercentage = ((stepNum - 1) / (totalSteps - 1)) * 100;
        document.getElementById("stepProgressLine").style.width = `${progressPercentage}%`;

        // Update Footer Buttons
        const prevBtn = document.getElementById("prevStepBtn");
        const nextBtn = document.getElementById("nextStepBtn");

        if (stepNum === 1) {
            prevBtn.classList.add("hidden");
        } else {
            prevBtn.classList.remove("hidden");
        }

        if (stepNum === totalSteps) {
            nextBtn.innerText = "Submit Registration";
            nextBtn.className = "px-6 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-extrabold text-xs hover:bg-[#73a11c] shadow-lg shadow-brandGreen/20";
        } else {
            nextBtn.innerText = "Next";
            nextBtn.className = "px-5 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-bold text-xs hover:bg-[#73a11c] shadow-lg shadow-brandGreen/10";
        }
    }

    function navigateStep(direction) {
        if (direction === 1) {
            // Validate required fields before moving forward
            const currentPane = document.getElementById(`step_pane_${currentStep}`);
            const inputs = currentPane.querySelectorAll("[required]");
            let isValid = true;

            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add("border-red-500");
                } else {
                    input.classList.remove("border-red-500");
                }
            });

            if (!isValid) {
                alert("Please fill out all required fields before proceeding.");
                return;
            }

            if (currentStep === totalSteps) {
                submitRegistration();
                return;
            }
        }

        currentStep += direction;
        
        // Compile summary for Step 5
        if (currentStep === totalSteps) {
            const form = document.getElementById("multiStepRegForm");
            const fd = new FormData(form);
            document.getElementById("summary_name").innerText = fd.get("name");
            document.getElementById("summary_mobile").innerText = fd.get("mobile");
            document.getElementById("summary_college").innerText = fd.get("college") || 'N/A';
            document.getElementById("summary_event").innerText = selectedEventTitle;
        }

        showStep(currentStep);
    }

    function submitRegistration() {
        const form = document.getElementById("multiStepRegForm");
        const formData = new FormData(form);

        fetch("/api.php?action=register-event", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                renderRegistrationSuccess(data.registration_id);
            } else {
                alert("Registration failed: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("An error occurred during submission.");
        });
    }

    function renderRegistrationSuccess(regId) {
        // Hide previous panels
        for (let i = 1; i <= totalSteps; i++) {
            document.getElementById(`step_pane_${i}`).classList.add("hidden");
        }
        document.getElementById("regModalFooter").classList.add("hidden");

        // Load content
        document.getElementById("success_ticket_id").innerText = regId;
        document.getElementById("success_event_title").innerText = selectedEventTitle;
        document.getElementById("success_user_name").innerText = document.querySelector("#multiStepRegForm input[name='name']").value;

        // Generate QR code canvas
        const qrContainer = document.getElementById("qrcode_container");
        qrContainer.innerHTML = ""; // clear previous
        
        var qr = qrcode(4, 'M');
        qr.addData(regId);
        qr.make();
        qrContainer.innerHTML = qr.createSvgTag(4);

        // Update PDF Link
        document.getElementById("downloadTicketLink").href = `/ticket.php?reg_id=${regId}`;

        // Reveal Success step pane
        document.getElementById("step_pane_6").classList.remove("hidden");
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
