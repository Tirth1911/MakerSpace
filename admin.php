<?php
/**
 * Admin Dashboard Page Template
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : '';
$is_admin = $is_logged_in && in_array($user_role, ['superadmin', 'admin', 'event_manager', 'resource_manager']);

if (!$is_admin) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Fetch lists from database for dashboard views
$users = [];
$events = [];
$registrations = [];
$resources = [];
$contact_messages = [];
$testimonials = [];
$seo_records = [];

if ($conn) {
    try {
        // Users
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $users = $stmt->fetchAll();

        // Events
        $stmt = $conn->prepare("SELECT * FROM events ORDER BY event_date DESC");
        $stmt->execute();
        $events = $stmt->fetchAll();

        // Registrations
        $stmt = $conn->prepare("SELECT r.*, e.title as event_title, u.name as user_name, u.email as user_email, u.mobile as user_mobile 
                                FROM event_registrations r 
                                JOIN events e ON r.event_id = e.id 
                                JOIN users u ON r.user_id = u.id 
                                ORDER BY r.registered_at DESC");
        $stmt->execute();
        $registrations = $stmt->fetchAll();

        // Resources
        $stmt = $conn->prepare("SELECT * FROM resources ORDER BY upload_date DESC");
        $stmt->execute();
        $resources = $stmt->fetchAll();

        // Testimonials
        $stmt = $conn->prepare("SELECT * FROM testimonials ORDER BY id DESC");
        $stmt->execute();
        $testimonials = $stmt->fetchAll();

        // Contact Messages
        $stmt = $conn->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC");
        $stmt->execute();
        $contact_messages = $stmt->fetchAll();

        // SEO Meta
        $stmt = $conn->prepare("SELECT * FROM seo_meta");
        $stmt->execute();
        $seo_records = $stmt->fetchAll();

    } catch (PDOException $e) {
        // Handle silently
    }
}

// Counts for analytical metrics
$total_users = count($users);
$total_events = count($events);
$total_registrations = count($registrations);
$total_downloads = 0;
foreach ($resources as $res) {
    $total_downloads += intval($res['downloads_count'] ?? 0);
}

// Counts for verification states
$verified_count = 0;
$unverified_count = 0;
foreach ($users as $u) {
    if (isset($u['email_verified']) && intval($u['email_verified']) === 1) {
        $verified_count++;
    } else {
        $unverified_count++;
    }
}
?>

<!-- Chart.js CDN for Analytics dashboard graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Outer Admin Layout -->
<section class="min-h-screen bg-[#F4F6F9] flex flex-col md:flex-row border-t border-gray-200">
  
  <!-- Sidebar Navigation -->
  <aside class="w-full md:w-64 bg-white border-r border-gray-200 p-6 flex flex-col justify-between shrink-0 shadow-sm">
    <div class="space-y-6">
      <div class="text-left">
        <span class="text-[10px] font-bold tracking-widest text-[#8DC63F] uppercase">ADMIN DESK</span>
        <h2 class="text-gray-800 font-extrabold text-lg font-['Outfit'] mt-0.5">Control Center</h2>
      </div>

      <!-- Menu Items -->
      <nav class="flex flex-col gap-1 text-sm font-semibold text-left" id="adminSidebar">
        <button onclick="switchTab('dashboard', this)" class="tab-btn px-4 py-3 rounded-xl text-[#8DC63F] bg-[#8DC63F]/10 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-chart-pie w-5"></i> Analytics Dashboard
        </button>
        <button onclick="switchTab('users', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-users w-5"></i> User Management
        </button>
        <button onclick="switchTab('events', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-calendar w-5"></i> Event Manager
        </button>
        <button onclick="switchTab('registrations', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-ticket w-5"></i> RSVP Registrations
        </button>
        <button onclick="switchTab('resources', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-folder w-5"></i> Technical Resources
        </button>
        <button onclick="switchTab('testimonials', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-quote-left w-5"></i> Testimonials
        </button>
        <button onclick="switchTab('messages', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all relative">
          <i class="fa-solid fa-envelope w-5"></i> Contact Inbox
          <?php 
            $unread_msgs = 0;
            foreach ($contact_messages as $msg) {
                if ($msg['status'] === 'unread') $unread_msgs++;
            }
            if ($unread_msgs > 0):
          ?>
            <span class="absolute right-4 top-1/2 -translate-y-1/2 bg-brandGreen text-brandBlack text-[10px] font-bold px-2 py-0.5 rounded-full"><?php echo $unread_msgs; ?></span>
          <?php endif; ?>
        </button>
        <button onclick="switchTab('design', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-palette w-5"></i> Website Design
        </button>
        <button onclick="switchTab('media', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-photo-film w-5"></i> Media Library
        </button>
        <button onclick="switchTab('seo', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-globe w-5"></i> SEO Desk & Files
        </button>
        <button onclick="switchTab('settings', this)" class="tab-btn px-4 py-3 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 flex items-center gap-3 transition-all">
          <i class="fa-solid fa-gears w-5"></i> System Settings
        </button>
      </nav>
    </div>

    <!-- Quick controls -->
    <div class="pt-6 border-t border-gray-200 space-y-2">
      <button onclick="toggleEditMode()" class="w-full text-center py-2.5 rounded-xl text-xs font-bold bg-[#8DC63F] text-black hover:bg-[#6DA52A] transition-all">
        <i class="fa-solid fa-pen-to-square mr-1"></i> Edit Mode CMS
      </button>
      <a href="/index.php" class="w-full block text-center py-2.5 rounded-xl text-xs font-bold bg-gray-100 border border-gray-200 text-gray-700 hover:bg-gray-200 transition-all">
        <i class="fa-solid fa-arrow-left mr-1"></i> Return to Site
      </a>
    </div>
  </aside>

  <!-- Dashboard Panel Body content -->
  <div class="flex-grow p-6 sm:p-10 space-y-8 overflow-x-hidden bg-[#F4F6F9]">

    <!-- TAB 1: ANALYTICS DASHBOARD -->
    <div id="tab_dashboard" class="admin-tab-pane space-y-8">
      
      <!-- Metrics header cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white border border-gray-200 p-6 rounded-2xl text-left shadow-sm">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Members</span>
          <h3 class="text-3xl font-extrabold text-gray-800 mt-1"><?php echo $total_users; ?></h3>
        </div>
        <div class="bg-white border border-gray-200 p-6 rounded-2xl text-left shadow-sm">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Events</span>
          <h3 class="text-3xl font-extrabold text-gray-800 mt-1"><?php echo $total_events; ?></h3>
        </div>
        <div class="bg-white border border-gray-200 p-6 rounded-2xl text-left shadow-sm">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Bookings</span>
          <h3 class="text-3xl font-extrabold text-gray-800 mt-1"><?php echo $total_registrations; ?></h3>
        </div>
        <div class="bg-white border border-gray-200 p-6 rounded-2xl text-left shadow-sm">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Downloads Logger</span>
          <h3 class="text-3xl font-extrabold text-gray-800 mt-1"><?php echo $total_downloads; ?></h3>
        </div>
      </div>

      <!-- Graphs layout -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Large Chart -->
        <div class="lg:col-span-8 bg-white p-6 rounded-3xl border border-gray-200 shadow-sm space-y-4">
          <h4 class="text-sm font-bold uppercase tracking-wider text-gray-700 border-b border-gray-100 pb-2 text-left">Monthly Registrations Activity</h4>
          <div class="h-64 relative">
            <canvas id="monthlyRegistrationsChart"></canvas>
          </div>
        </div>

        <!-- Right Side: Top Events -->
        <div class="lg:col-span-4 bg-white p-6 rounded-3xl border border-gray-200 shadow-sm space-y-4">
          <h4 class="text-sm font-bold uppercase tracking-wider text-gray-700 border-b border-gray-100 pb-2 text-left">Popular Event Categories</h4>
          <div class="h-64 relative flex items-center justify-center">
            <canvas id="categoriesChart" class="max-w-[200px]"></canvas>
          </div>
        </div>

      </div>

    </div>

    <!-- TAB 2: USER MANAGEMENT -->
    <div id="tab_users" class="admin-tab-pane hidden space-y-6">
      
      <!-- Metrics summary cards for registrations -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-gray-200 p-5 rounded-2xl text-left shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
            <i class="fa-solid fa-users"></i>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Total Registrations</span>
            <h3 class="text-xl font-extrabold text-gray-800"><?php echo count($users); ?></h3>
          </div>
        </div>
        <div class="bg-white border border-gray-200 p-5 rounded-2xl text-left shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-green-50 text-green-600 flex items-center justify-center font-bold">
            <i class="fa-solid fa-user-check"></i>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Verified Accounts</span>
            <h3 class="text-xl font-extrabold text-gray-800"><?php echo $verified_count; ?></h3>
          </div>
        </div>
        <div class="bg-white border border-gray-200 p-5 rounded-2xl text-left shadow-sm flex items-center gap-4">
          <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-600 flex items-center justify-center font-bold">
            <i class="fa-solid fa-user-clock"></i>
          </div>
          <div>
            <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Unverified Accounts</span>
            <h3 class="text-xl font-extrabold text-gray-800"><?php echo $unverified_count; ?></h3>
          </div>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-left">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-gray-800">User Accounts</h2>
          <p class="text-xs text-gray-400 mt-1">Approve, suspend, verify or delete users registration details.</p>
        </div>
        
        <!-- Filter dropdown -->
        <div class="flex items-center gap-2">
          <label class="text-xs font-semibold text-gray-500">Filter By:</label>
          <select onchange="filterUsersTable(this.value)" class="bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-xs text-gray-700 focus:outline-none shadow-sm">
            <option value="all">All Registrations</option>
            <option value="verified">Verified Accounts Only</option>
            <option value="unverified">Unverified Accounts Only</option>
          </select>
        </div>
      </div>

      <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs uppercase bg-gray-50 text-gray-500 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4">Name</th>
                <th class="px-6 py-4">Email / Mobile</th>
                <th class="px-6 py-4">Provider</th>
                <th class="px-6 py-4">Verified</th>
                <th class="px-6 py-4">Role Joined</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100" id="adminUsersTableBody">
              <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50" data-verified="<?php echo intval($u['email_verified'] ?? 0); ?>">
                  <td class="px-6 py-4 font-bold text-gray-800"><?php echo htmlspecialchars($u['name']); ?></td>
                  <td class="px-6 py-4 text-xs font-mono">
                    <?php echo htmlspecialchars($u['email']); ?><br>
                    <span class="text-gray-400"><?php echo htmlspecialchars($u['mobile']); ?></span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs <?php echo ($u['auth_provider'] ?? 'local') === 'google' ? 'text-blue-600 bg-blue-50' : 'text-gray-600 bg-gray-100'; ?> px-2.5 py-1 rounded-full font-bold uppercase">
                      <?php echo htmlspecialchars($u['auth_provider'] ?? 'local'); ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs <?php echo ($u['email_verified'] ?? 0) == 1 ? 'text-green-600 bg-green-50' : 'text-gray-500 bg-gray-100'; ?> px-2.5 py-1 rounded-full font-bold uppercase">
                      <?php echo ($u['email_verified'] ?? 0) == 1 ? 'Verified' : 'Unverified'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <select onchange="updateUserRole(<?php echo $u['id']; ?>, this.value)" class="bg-gray-100 border border-gray-300 rounded-lg text-xs font-semibold px-2 py-1 text-gray-700 focus:outline-none focus:border-brandGreen uppercase">
                      <option value="member" <?php echo $u['role'] === 'member' ? 'selected' : ''; ?>>member</option>
                      <option value="volunteer" <?php echo $u['role'] === 'volunteer' ? 'selected' : ''; ?>>volunteer</option>
                      <option value="mentor" <?php echo $u['role'] === 'mentor' ? 'selected' : ''; ?>>mentor</option>
                      <option value="editor" <?php echo $u['role'] === 'editor' ? 'selected' : ''; ?>>editor</option>
                      <option value="event_manager" <?php echo $u['role'] === 'event_manager' ? 'selected' : ''; ?>>event_manager</option>
                      <option value="resource_manager" <?php echo $u['role'] === 'resource_manager' ? 'selected' : ''; ?>>resource_manager</option>
                      <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>admin</option>
                      <option value="superadmin" <?php echo $u['role'] === 'superadmin' ? 'selected' : ''; ?>>superadmin</option>
                    </select>
                  </td>
                  <td class="px-6 py-4">
                    <span class="text-xs <?php echo $u['status'] === 'approved' ? 'text-brandGreen' : ($u['status'] === 'suspended' ? 'text-red-400' : 'text-yellow-500'); ?> font-bold uppercase">
                      <?php echo $u['status']; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right space-x-1 whitespace-nowrap">
                    <?php if (($u['email_verified'] ?? 0) == 0): ?>
                      <button onclick="adminVerifyUser(<?php echo $u['id']; ?>)" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-green-50 hover:bg-green-100 text-green-600 border border-green-200" title="Manually Verify Email">Verify</button>
                      <button onclick="adminResendVerification(<?php echo $u['id']; ?>)" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200" title="Resend OTP Email">Resend</button>
                    <?php endif; ?>
                    <?php if ($u['role'] !== 'admin'): ?>
                      <?php if ($u['status'] === 'approved'): ?>
                        <button onclick="updateUserStatus(<?php echo $u['id']; ?>, 'suspended')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-red-50 hover:bg-red-100 text-red-500 border border-red-200">Suspend</button>
                      <?php else: ?>
                        <button onclick="updateUserStatus(<?php echo $u['id']; ?>, 'approved')" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-green-50 hover:bg-green-100 text-green-600 border border-green-200">Approve</button>
                      <?php endif; ?>
                      <button onclick="adminDeleteUser(<?php echo $u['id']; ?>)" class="px-2.5 py-1.5 rounded-lg text-[10px] font-bold bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/25" title="Delete Fake User"><i class="fa-solid fa-trash-can"></i></button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 3: EVENT MANAGER -->
    <div id="tab_events" class="admin-tab-pane hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-left">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-gray-800">Events Database</h2>
          <p class="text-xs text-gray-400 mt-1">Add, edit, duplicate or delete active makerspace sessions.</p>
        </div>
        <button onclick="openAdminEventModal(0)" class="px-4 py-2.5 rounded-xl font-bold bg-brandGreen text-brandBlack hover:bg-[#73a11c] text-xs flex items-center gap-1.5">
          <i class="fa-solid fa-circle-plus"></i> Create Session
        </button>
      </div>

      <div class="bg-white border border-gray-200 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs uppercase bg-gray-50 text-gray-500 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4">Session Title</th>
                <th class="px-6 py-4">Category</th>
                <th class="px-6 py-4">Timing Details</th>
                <th class="px-6 py-4">Seats Occupied</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5" id="adminEventsTableBody">
              <?php foreach ($events as $e): ?>
                <tr class="hover:bg-white/2" id="admin_evt_row_<?php echo $e['id']; ?>">
                  <td class="px-6 py-4 font-bold text-white"><?php echo htmlspecialchars($e['title']); ?></td>
                  <td class="px-6 py-4 text-xs font-semibold text-gray-300"><?php echo htmlspecialchars($e['category']); ?></td>
                  <td class="px-6 py-4 text-xs">
                    Date: <?php echo date('M d, Y', strtotime($e['event_date'])); ?><br>
                    Time: <?php echo date('h:i A', strtotime($e['event_time'])); ?>
                  </td>
                  <td class="px-6 py-4 text-xs font-semibold">
                    <?php echo ($e['capacity'] - $e['available_seats']); ?> / <?php echo $e['capacity']; ?> Booked
                  </td>
                  <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                    <button onclick="openAdminEventModal(<?php echo htmlspecialchars(json_encode($e)); ?>)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-white/5 hover:bg-white/10 text-gray-300 border border-white/10" title="Edit Session"><i class="fa-solid fa-pen-to-square"></i></button>
                    <button onclick="duplicateAdminEvent(<?php echo htmlspecialchars(json_encode($e)); ?>)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-brandGreen/10 hover:bg-brandGreen/20 text-brandGreen border border-brandGreen/25" title="Duplicate Session"><i class="fa-solid fa-copy"></i></button>
                    <button onclick="deleteAdminEvent(<?php echo $e['id']; ?>)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/25" title="Delete Session"><i class="fa-solid fa-trash-can"></i></button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 4: RSVP REGISTRATIONS -->
    <div id="tab_registrations" class="admin-tab-pane hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-left">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">RSVP Registrations</h2>
          <p class="text-xs text-gray-500 mt-1">Audit attendee lists and export bookings logs.</p>
        </div>
        <button onclick="exportRegistrationsToCSV()" class="px-4 py-2.5 rounded-xl font-bold bg-[#1a1a1a] border border-white/10 hover:bg-white/5 text-white text-xs flex items-center gap-1.5">
          <i class="fa-solid fa-file-csv"></i> Export CSV Spreadsheet
        </button>
      </div>

      <div class="glass border border-white/5 rounded-3xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-400" id="registrationsAuditTable">
            <thead class="text-xs uppercase bg-[#151515] text-gray-500 border-b border-white/5">
              <tr>
                <th class="px-6 py-4">Ticket ID</th>
                <th class="px-6 py-4">Participant details</th>
                <th class="px-6 py-4">Target Session</th>
                <th class="px-6 py-4">Checked Attendance</th>
                <th class="px-6 py-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <?php foreach ($registrations as $r): ?>
                <tr class="hover:bg-white/2">
                  <td class="px-6 py-4 font-mono font-bold text-white text-xs"><?php echo $r['registration_id']; ?></td>
                  <td class="px-6 py-4 text-xs">
                    <strong class="text-gray-300"><?php echo htmlspecialchars($r['user_name']); ?></strong><br>
                    <span><?php echo htmlspecialchars($r['user_email']); ?></span>
                  </td>
                  <td class="px-6 py-4 text-xs font-semibold text-gray-300"><?php echo htmlspecialchars($r['event_title']); ?></td>
                  <td class="px-6 py-4">
                    <span class="text-xs font-bold uppercase <?php echo $r['attendance_status'] === 'present' ? 'text-brandGreen' : 'text-gray-500'; ?>">
                      <?php echo $r['attendance_status'] === 'present' ? 'Present' : 'Absent'; ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-xs uppercase font-bold"><?php echo htmlspecialchars($r['status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 5: TECHNICAL RESOURCES -->
    <div id="tab_resources" class="admin-tab-pane hidden space-y-6">
      <div class="flex justify-between items-center">
        <div class="text-left">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">Resources Files</h2>
          <p class="text-xs text-gray-500 mt-1">Manage downloadable PDF guides and tracking metrics.</p>
        </div>
      </div>

      <div class="glass border border-white/5 rounded-3xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm text-left text-gray-400">
            <thead class="text-xs uppercase bg-[#151515] text-gray-500 border-b border-white/5">
              <tr>
                <th class="px-6 py-4">Document Title</th>
                <th class="px-6 py-4">Category</th>
                <th class="px-6 py-4">Uploader / Author</th>
                <th class="px-6 py-4">Downloads Log</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
              <?php foreach ($resources as $res): ?>
                <tr class="hover:bg-white/2" id="admin_res_row_<?php echo $res['id']; ?>">
                  <td class="px-6 py-4 font-bold text-white"><?php echo htmlspecialchars($res['title']); ?></td>
                  <td class="px-6 py-4 text-xs font-semibold"><?php echo htmlspecialchars($res['category']); ?></td>
                  <td class="px-6 py-4 text-xs text-gray-400"><?php echo htmlspecialchars($res['author']); ?></td>
                  <td class="px-6 py-4 text-xs font-bold font-mono text-brandGreen"><?php echo intval($res['downloads_count']); ?> DLs</td>
                  <td class="px-6 py-4 text-right">
                    <button onclick="deleteAdminResource(<?php echo $res['id']; ?>)" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/25"><i class="fa-solid fa-trash-can"></i> Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 6: TESTIMONIALS -->
    <div id="tab_testimonials" class="admin-tab-pane hidden space-y-6">
      <div class="flex justify-between items-center">
        <div class="text-left">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">Testimonials CRUD</h2>
          <p class="text-xs text-gray-500 mt-1">Manage testimonies displayed on About page.</p>
        </div>
        <button onclick="openTestimonialModal()" class="px-4 py-2.5 rounded-xl font-bold bg-brandGreen text-brandBlack hover:bg-[#73a11c] text-xs"><i class="fa-solid fa-circle-plus mr-1"></i> Add Quote</button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($testimonials as $t): ?>
          <div class="glass p-6 rounded-2xl border border-white/5 text-left flex flex-col justify-between" id="admin_test_card_<?php echo $t['id']; ?>">
            <p class="text-gray-400 text-xs italic mb-4 leading-normal">"<?php echo htmlspecialchars($t['text']); ?>"</p>
            <div class="flex items-center justify-between border-t border-white/5 pt-4">
              <div class="flex items-center gap-3">
                <img src="<?php echo htmlspecialchars($t['image_url']); ?>" class="w-8 h-8 rounded-full object-cover">
                <div>
                  <h4 class="text-xs font-bold text-white"><?php echo htmlspecialchars($t['name']); ?></h4>
                  <span class="text-[10px] text-brandGreen"><?php echo htmlspecialchars($t['role']); ?></span>
                </div>
              </div>
              <button onclick="deleteAdminTestimonial(<?php echo $t['id']; ?>)" class="text-red-400 hover:text-red-300 text-xs font-semibold"><i class="fa-solid fa-trash-can mr-1"></i> Delete</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TAB 7: CONTACT MESSAGES -->
    <div id="tab_messages" class="admin-tab-pane hidden space-y-6">
      <div class="text-left">
        <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">Inbox Messages</h2>
        <p class="text-xs text-gray-500 mt-1">Messages and partnership queries submitted from contact sheets.</p>
      </div>

      <div class="space-y-4">
        <?php if (empty($contact_messages)): ?>
          <div class="glass p-8 rounded-2xl text-center text-gray-500 border border-white/5">No messages in inbox.</div>
        <?php else: ?>
          <?php foreach ($contact_messages as $msg): ?>
            <div class="glass p-6 rounded-2xl border border-white/5 text-left space-y-3">
              <div class="flex justify-between items-center flex-wrap gap-2">
                <div>
                  <span class="block font-bold text-white text-sm"><?php echo htmlspecialchars($msg['name']); ?></span>
                  <span class="text-xs text-gray-500"><?php echo htmlspecialchars($msg['email']); ?> | Mobile: <?php echo htmlspecialchars($msg['mobile'] ?? 'N/A'); ?></span>
                </div>
                <span class="text-[10px] text-gray-500 font-semibold"><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></span>
              </div>
              <div class="border-t border-white/5 pt-3">
                <span class="block text-xs font-bold text-brandGreen uppercase mb-1">Subject: <?php echo htmlspecialchars($msg['subject']); ?></span>
                <p class="text-xs sm:text-sm text-gray-400 leading-relaxed"><?php echo htmlspecialchars($msg['message']); ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- TAB 8: WEBSITE DESIGN SETTINGS -->
    <div id="tab_design" class="admin-tab-pane hidden space-y-6">
      <div class="text-left">
        <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">Website Branding & Design</h2>
        <p class="text-xs text-gray-500 mt-1">Configure layout spacing, border radii, colors, typography, logos, and favicons.</p>
      </div>

      <form id="designSettingsForm" onsubmit="saveDesignSettings(event)" class="glass p-8 rounded-3xl border border-white/5 space-y-6 text-left">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Primary Brand Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_primary_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_primary_color', '#8DC63F')); ?>" oninput="document.getElementById('design_primary_color').value = this.value">
              <input type="text" id="design_primary_color" name="design_primary_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_primary_color', '#8DC63F')); ?>" onchange="document.getElementById('design_primary_color_picker').value = this.value">
            </div>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Secondary Brand Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_secondary_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_secondary_color', '#6DA52A')); ?>" oninput="document.getElementById('design_secondary_color').value = this.value">
              <input type="text" id="design_secondary_color" name="design_secondary_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_secondary_color', '#6DA52A')); ?>" onchange="document.getElementById('design_secondary_color_picker').value = this.value">
            </div>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Accent Link/Highlight Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_accent_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_accent_color', '#3B82F6')); ?>" oninput="document.getElementById('design_accent_color').value = this.value">
              <input type="text" id="design_accent_color" name="design_accent_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_accent_color', '#3B82F6')); ?>" onchange="document.getElementById('design_accent_color_picker').value = this.value">
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Background Canvas Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_bg_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_bg_color', '#FFFFFF')); ?>" oninput="document.getElementById('design_bg_color').value = this.value">
              <input type="text" id="design_bg_color" name="design_bg_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_bg_color', '#FFFFFF')); ?>" onchange="document.getElementById('design_bg_color_picker').value = this.value">
            </div>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Card Canvas Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_card_bg_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_card_bg_color', '#F5F6F8')); ?>" oninput="document.getElementById('design_card_bg_color').value = this.value">
              <input type="text" id="design_card_bg_color" name="design_card_bg_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_card_bg_color', '#F5F6F8')); ?>" onchange="document.getElementById('design_card_bg_color_picker').value = this.value">
            </div>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Primary Text Font Color</label>
            <div class="flex gap-2">
              <input type="color" id="design_text_color_picker" class="w-10 h-10 rounded border-0 bg-transparent cursor-pointer" value="<?php echo htmlspecialchars(getSetting('design_text_color', '#111111')); ?>" oninput="document.getElementById('design_text_color').value = this.value">
              <input type="text" id="design_text_color" name="design_text_color" class="flex-grow bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs focus:outline-none" value="<?php echo htmlspecialchars(getSetting('design_text_color', '#111111')); ?>" onchange="document.getElementById('design_text_color_picker').value = this.value">
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Typography Font Family</label>
            <select name="design_font_family" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">
              <option value="'Inter', sans-serif" <?php echo getSetting('design_font_family') === "'Inter', sans-serif" ? 'selected' : ''; ?>>Inter (Clean sans-serif)</option>
              <option value="'Outfit', sans-serif" <?php echo getSetting('design_font_family') === "'Outfit', sans-serif" ? 'selected' : ''; ?>>Outfit (Modern/Premium geometric)</option>
              <option value="'Roboto', sans-serif" <?php echo getSetting('design_font_family') === "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto</option>
              <option value="system-ui, sans-serif" <?php echo getSetting('design_font_family') === "system-ui, sans-serif" ? 'selected' : ''; ?>>System Default</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Border Corner Radius</label>
            <select name="design_border_radius" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">
              <option value="0px" <?php echo getSetting('design_border_radius') === '0px' ? 'selected' : ''; ?>>Sharp (0px)</option>
              <option value="8px" <?php echo getSetting('design_border_radius') === '8px' ? 'selected' : ''; ?>>Subtle (8px)</option>
              <option value="16px" <?php echo getSetting('design_border_radius') === '16px' ? 'selected' : ''; ?>>Balanced (16px)</option>
              <option value="24px" <?php echo getSetting('design_border_radius') === '24px' ? 'selected' : ''; ?>>Curvy (24px)</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Sticky Header Navigation</label>
            <select name="design_sticky_header" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">
              <option value="1" <?php echo getSetting('design_sticky_header', '1') === '1' ? 'selected' : ''; ?>>Sticky Navbar (ON)</option>
              <option value="0" <?php echo getSetting('design_sticky_header', '1') === '0' ? 'selected' : ''; ?>>Scroll with Page (OFF)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="md:col-span-2">
            <label class="block text-gray-400 font-semibold mb-2 text-xs">Logo Image</label>
            <div class="cms-img-upload-wrap">
              <div class="flex items-center gap-3 mb-2">
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 bg-[#8DC63F] hover:bg-[#6DA52A] text-black font-bold text-xs rounded-xl transition-all">
                  <i class="fa-solid fa-folder-open"></i> Choose File
                  <input type="file" accept="image/*" class="hidden" onchange="uploadImageFile(this, 'design_logo_url_input', 'logo_img_preview')">
                </label>
                <span class="text-gray-500 text-[10px]">or paste URL below</span>
                <img id="logo_img_preview" src="<?php echo htmlspecialchars(getSetting('design_logo_url', '/public/images/logo-light.png')); ?>" class="h-8 object-contain" onerror="this.style.display='none'">
              </div>
              <input type="text" name="design_logo_url" id="design_logo_url_input" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs" value="<?php echo htmlspecialchars(getSetting('design_logo_url', '/public/images/logo-light.png')); ?>" oninput="updateImgPreview('design_logo_url_input','logo_img_preview')">
            </div>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Favicon Link Path</label>
            <input type="text" name="design_favicon_url" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs" value="<?php echo htmlspecialchars(getSetting('design_favicon_url', '/favicon.ico')); ?>">
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit" class="px-6 py-3 bg-brandGreen hover:bg-brandDarkGreen text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/20">Save Branding Configs</button>
        </div>
      </form>
    </div>

    <!-- TAB 9: CENTRAL MEDIA LIBRARY -->
    <div id="tab_media" class="admin-tab-pane hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-left flex-grow">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">Media Assets Library</h2>
          <p class="text-xs text-gray-500 mt-1">Upload files, search filenames, and copy direct server links.</p>
        </div>
        
        <!-- Upload Form -->
        <form id="mediaUploadForm" onsubmit="uploadMediaAsset(event)" class="flex gap-2 items-center">
          <input type="file" id="media_file_input" name="file" required class="hidden" onchange="document.getElementById('media_file_name_display').innerText = this.files[0].name">
          <button type="button" onclick="document.getElementById('media_file_input').click()" class="px-4 py-2.5 rounded-xl border border-white/10 bg-white/5 text-gray-300 hover:text-white font-bold text-xs">
            <i class="fa-solid fa-file-image mr-1"></i> <span id="media_file_name_display">Choose File</span>
          </button>
          <button type="submit" class="px-4 py-2.5 rounded-xl bg-brandGreen hover:bg-[#73a11c] text-brandBlack font-bold text-xs">
            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Upload
          </button>
        </form>
      </div>

      <!-- Search & Filters -->
      <div class="flex items-center gap-4">
        <input type="text" id="mediaSearchInput" onkeyup="filterMediaLibrary()" placeholder="Search files..." class="w-full max-w-xs bg-brandDarkGray border border-white/5 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-brandGreen">
      </div>

      <!-- Assets grid -->
      <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-6" id="mediaLibraryGrid">
        <!-- Loaded dynamically via AJAX -->
      </div>
    </div>

    <!-- TAB 12: SEO DESK & SITE FILES -->
    <div id="tab_seo" class="admin-tab-pane hidden space-y-6">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="text-left flex-grow">
          <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">SEO & Sitemap Manager</h2>
          <p class="text-xs text-gray-500 mt-1">Update meta tags, descriptions, keywords and regenerate search engine indexing files.</p>
        </div>
        <button onclick="regenerateSeoFiles()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-brandGreen border border-brandGreen/25 font-bold text-xs flex items-center gap-1.5 shadow-lg shadow-brandGreen/5">
          <i class="fa-solid fa-rotate mr-1"></i> Generate Sitemap & Robots.txt
        </button>
      </div>

      <div class="glass p-6 rounded-3xl border border-white/5 space-y-4">
        <div class="flex items-center gap-3 mb-4">
          <label class="text-xs font-semibold text-gray-400">Select Page:</label>
          <select id="seo_page_select" onchange="loadPageSeoData()" class="bg-[#151515] border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none">
            <option value="home">Home (index.php)</option>
            <option value="about">About Us (about.php)</option>
            <option value="what-we-do">What We Do (what-we-do.php)</option>
            <option value="resources">Resources (resources.php)</option>
            <option value="events">Events & Calendar (events.php)</option>
            <option value="contact">Contact Us (contact.php)</option>
          </select>
        </div>

        <form id="seoSettingsForm" onsubmit="savePageSeoData(event)" class="space-y-4 text-left">
          <input type="hidden" id="seo_meta_id" value="0">
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Meta Title</label>
            <input type="text" id="seo_meta_title" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Meta Description</label>
            <textarea id="seo_meta_desc" required rows="3" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs"></textarea>
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1 text-xs">Keywords (comma-separated)</label>
            <input type="text" id="seo_keywords" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-2 text-xs">OG / Social Share Image</label>
            <div class="cms-img-upload-wrap">
              <div class="flex items-center gap-3 mb-2">
                <label class="flex items-center gap-2 cursor-pointer px-3 py-2 bg-[#8DC63F] hover:bg-[#6DA52A] text-black font-bold text-xs rounded-xl transition-all">
                  <i class="fa-solid fa-folder-open"></i> Choose File
                  <input type="file" accept="image/*" class="hidden" onchange="uploadImageFile(this, 'seo_og_image', 'seo_og_preview')">
                </label>
                <span class="text-gray-500 text-[10px]">or paste URL</span>
              </div>
              <input type="text" id="seo_og_image" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs" oninput="updateImgPreview('seo_og_image','seo_og_preview')">
              <img id="seo_og_preview" class="mt-2 h-16 w-full object-cover rounded-lg border border-white/10 hidden">
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit" class="px-6 py-3 bg-brandGreen hover:bg-brandDarkGreen text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/20">Save SEO Metadata</button>
          </div>
        </form>
      </div>
    </div>

    <!-- TAB: SYSTEM SETTINGS (SMTP & GOOGLE AUTH) -->
    <div id="tab_settings" class="admin-tab-pane hidden space-y-6">
      <div class="text-left">
        <h2 class="text-2xl font-extrabold font-['Outfit'] text-white">System Settings</h2>
        <p class="text-xs text-gray-500 mt-1">Configure SMTP credentials for Email OTP and Google Client configurations for Google Authentication.</p>
      </div>

      <form id="systemSettingsForm" onsubmit="saveSystemSettings(event)" class="glass p-8 rounded-3xl border border-white/5 space-y-6 text-left">
        
        <!-- SMTP Config -->
        <div class="space-y-4">
          <h3 class="text-sm font-bold uppercase tracking-wider text-[#8DC63F] border-b border-white/5 pb-2">SMTP Mail Server Configuration</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">SMTP Host</label>
              <input type="text" name="smtp_host" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('smtp_host', 'smtp.gmail.com')); ?>">
            </div>
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">SMTP Port</label>
              <input type="text" name="smtp_port" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('smtp_port', '587')); ?>">
            </div>
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">SMTP Secure Encryption</label>
              <select name="smtp_secure" class="w-full bg-brandBlack border border-white/10 rounded-xl p-2.5 text-white focus:outline-none focus:border-brandGreen text-xs">
                <option value="tls" <?php echo getSetting('smtp_secure') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                <option value="ssl" <?php echo getSetting('smtp_secure') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                <option value="none" <?php echo getSetting('smtp_secure') === 'none' ? 'selected' : ''; ?>>None</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">SMTP Username / Email</label>
              <input type="email" name="smtp_username" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('smtp_username', '')); ?>" placeholder="e.g. your-email@gmail.com">
            </div>
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">SMTP Password / App Password</label>
              <input type="password" name="smtp_password" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('smtp_password', '')); ?>" placeholder="••••••••">
            </div>
          </div>
        </div>

        <!-- Google OAuth Config -->
        <div class="space-y-4 pt-4">
          <h3 class="text-sm font-bold uppercase tracking-wider text-[#8DC63F] border-b border-white/5 pb-2">Google OAuth Credentials</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">Google Client ID</label>
              <input type="text" name="google_client_id" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('google_client_id', '')); ?>" placeholder="xxxxxxxx.apps.googleusercontent.com">
            </div>
            <div>
              <label class="block text-gray-400 font-semibold mb-1 text-xs">Google Client Secret</label>
              <input type="password" name="google_client_secret" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2.5 text-white text-xs focus:outline-none focus:border-brandGreen" value="<?php echo htmlspecialchars(getSetting('google_client_secret', '')); ?>" placeholder="••••••••">
            </div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit" class="px-6 py-3 bg-brandGreen hover:bg-brandDarkGreen text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/20">Save System Settings</button>
        </div>
      </form>

      <!-- Google Cloud Setup Documentation -->
      <div class="bg-white p-8 rounded-3xl border border-gray-200 text-left space-y-4">
        <h3 class="text-gray-800 font-extrabold text-lg font-['Outfit'] flex items-center gap-2">
          <i class="fa-brands fa-google text-blue-600"></i> Google OAuth Integration Setup Guide
        </h3>
        <p class="text-xs text-gray-600">Follow these steps in Google Cloud Console to set up authentication:</p>
        <ol class="list-decimal pl-5 text-xs text-gray-600 space-y-2">
          <li>Go to the <a href="https://console.cloud.google.com/" target="_blank" class="text-brandGreen hover:underline font-bold">Google Cloud Console</a> and create or select a project.</li>
          <li>Navigate to <strong>APIs & Services &gt; OAuth consent screen</strong>. Configure the consent screen as an <strong>External</strong> app type. Add name, logo, developer email, and authorized domains.</li>
          <li>Navigate to <strong>Credentials</strong>. Click <strong>+ Create Credentials &gt; OAuth client ID</strong>.</li>
          <li>Select <strong>Web application</strong> as the Application type. Name it <em>Yuvalay MakerSpace Web</em>.</li>
          <li>Under <strong>Authorized JavaScript Origins</strong>, add the current host URLs:
            <ul class="list-disc pl-5 mt-1 font-mono text-[11px] text-gray-700 bg-gray-50 p-2 rounded">
              <li>http://localhost</li>
              <li>http://127.0.0.1:8000</li>
              <li>http://127.0.0.1</li>
            </ul>
          </li>
          <li>Under <strong>Authorized Redirect URIs</strong>, add:
            <ul class="list-disc pl-5 mt-1 font-mono text-[11px] text-gray-700 bg-gray-50 p-2 rounded">
              <li>http://localhost/login.php</li>
              <li>http://127.0.0.1:8000/login.php</li>
            </ul>
          </li>
          <li>Click <strong>Create</strong>. Copy the generated <strong>Client ID</strong> and <strong>Client Secret</strong>, paste them in the settings form above, and click <strong>Save System Settings</strong>.</li>
        </ol>
      </div>
    </div>

  </div>
</section>

<!-- Admin Event Form Modal (Create / Edit) -->
<div id="adminEventModal" class="fixed inset-0 bg-brandBlack/85 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
  <div class="bg-brandDarkGray border border-white/10 w-full max-w-xl rounded-[32px] overflow-hidden shadow-2xl flex flex-col">
    <div class="p-6 border-b border-white/5 bg-[#151515] flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-white font-['Outfit']" id="adminEventModalTitle">Create Event Session</h3>
      <button onclick="closeAdminEventModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <form id="adminEventForm" class="p-6 space-y-4 text-sm text-left overflow-y-auto max-h-[75vh]">
      <input type="hidden" name="id" id="evt_form_id" value="0">
      
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Session Title</label>
        <input type="text" name="title" id="evt_form_title" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Description</label>
        <textarea name="description" id="evt_form_desc" rows="2" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Category</label>
          <select name="category" id="evt_form_cat" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
            <option value="Workshops">Workshops</option>
            <option value="Hackathons">Hackathons</option>
            <option value="Training Programs">Training Programs</option>
            <option value="Meetups">Meetups</option>
            <option value="Competitions">Competitions</option>
            <option value="Seminars">Seminars</option>
            <option value="Webinars">Webinars</option>
            <option value="Community Events">Community Events</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Organizer Name</label>
          <input type="text" name="organizer" id="evt_form_org" value="Yuvalay MakerSpace" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Date</label>
          <input type="date" name="event_date" id="evt_form_date" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Time</label>
          <input type="time" name="event_time" id="evt_form_time" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Seat Capacity</label>
          <input type="number" name="capacity" id="evt_form_cap" value="20" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Registration Deadline</label>
          <input type="datetime-local" name="registration_deadline" id="evt_form_deadline" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Venue / Room</label>
          <input type="text" name="venue" id="evt_form_venue" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
        </div>
      </div>

      <div>
        <label class="block text-gray-400 font-semibold mb-2">Banner Image</label>
        <div class="cms-img-upload-wrap" data-target="evt_form_banner">
          <div class="flex items-center gap-3 mb-2">
            <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 bg-[#8DC63F] hover:bg-[#6DA52A] text-black font-bold text-xs rounded-xl transition-all">
              <i class="fa-solid fa-folder-open"></i> Choose File
              <input type="file" accept="image/*" class="hidden" onchange="uploadImageFile(this, 'evt_form_banner', 'evt_banner_preview')">
            </label>
            <span class="text-gray-500 text-xs">or paste URL below</span>
          </div>
          <input type="text" name="banner_image" id="evt_form_banner" value="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs" placeholder="https://... or upload above" oninput="updateImgPreview('evt_form_banner','evt_banner_preview')">
          <div class="mt-2">
            <img id="evt_banner_preview" src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=600&q=80" class="h-24 w-full object-cover rounded-xl border border-white/10 bg-black/20" onerror="this.style.display='none'" style="display:block">
          </div>
        </div>
      </div>

      <div class="pt-4 flex justify-end gap-3 border-t border-white/5 pt-4">
        <button type="button" onclick="closeAdminEventModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 font-bold text-xs">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-extrabold text-xs shadow-lg shadow-brandGreen/10">Save Session</button>
      </div>
    </form>
  </div>
</div>

<!-- Testimonial creation modal -->
<div id="adminTestModal" class="fixed inset-0 bg-brandBlack/85 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
  <div class="bg-brandDarkGray border border-white/10 w-full max-w-md rounded-[32px] overflow-hidden shadow-2xl">
    <div class="p-6 border-b border-white/5 bg-[#151515] flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-white font-['Outfit']">Add Testimonial Quote</h3>
      <button onclick="closeTestimonialModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <form id="adminTestForm" class="p-6 space-y-4 text-sm text-left">
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Full Name</label>
        <input type="text" name="name" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Role / Subtitle</label>
        <input type="text" name="role" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Mechanical Student">
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Testimonial Quote</label>
        <textarea name="text" rows="3" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen"></textarea>
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-2">Avatar Photo</label>
        <div class="cms-img-upload-wrap" data-target="test_image_url">
          <div class="flex items-center gap-3 mb-2">
            <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 bg-[#8DC63F] hover:bg-[#6DA52A] text-black font-bold text-xs rounded-xl transition-all">
              <i class="fa-solid fa-folder-open"></i> Choose File
              <input type="file" accept="image/*" class="hidden" onchange="uploadImageFile(this, 'test_image_url', 'test_img_preview')">
            </label>
            <span class="text-gray-500 text-xs">or paste URL below</span>
          </div>
          <input type="text" name="image_url" id="test_image_url" value="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" oninput="updateImgPreview('test_image_url','test_img_preview')">
          <div class="mt-2 flex items-center gap-3">
            <img id="test_img_preview" src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=120&q=80" class="w-16 h-16 rounded-full object-cover border border-white/10" onerror="this.style.display='none'">
            <span class="text-gray-500 text-xs">Avatar preview</span>
          </div>
        </div>
      </div>
      <div class="pt-4 flex justify-end gap-3">
        <button type="button" onclick="closeTestimonialModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 font-bold text-xs">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-extrabold text-xs shadow-lg shadow-brandGreen/10">Add Testimonial</button>
      </div>
    </form>
  </div>
</div>

<!-- Custom Page Creation Modal -->
<div id="adminPageModal" class="fixed inset-0 bg-brandBlack/85 backdrop-blur-sm z-[99999] hidden flex items-center justify-center p-4">
  <div class="bg-brandDarkGray border border-white/10 w-full max-w-md rounded-[32px] overflow-hidden shadow-2xl">
    <div class="p-6 border-b border-white/5 bg-[#151515] flex justify-between items-center">
      <h3 class="font-extrabold text-lg text-white font-['Outfit']">Add Custom Page</h3>
      <button onclick="closeCustomPageModal()" class="text-gray-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
    <form id="adminPageForm" onsubmit="saveCustomPage(event)" class="p-6 space-y-4 text-sm text-left">
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Page Title</label>
        <input type="text" id="page_form_title" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. Careers" oninput="autoGenerateSlug(this.value)">
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-1">URL Slug</label>
        <input type="text" id="page_form_slug" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="e.g. careers" oninput="document.getElementById('slug_preview').innerText = this.value || '...';">
        <p class="text-[10px] text-gray-500 mt-1">This page will be accessible at: <code>/page.php?slug=<span id="slug_preview">...</span></code></p>
      </div>
      <div>
        <label class="block text-gray-400 font-semibold mb-1">Status</label>
        <select id="page_form_status" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          <option value="published">Published</option>
          <option value="draft">Draft (hidden from visitors)</option>
        </select>
      </div>
      <div class="pt-4 flex justify-end gap-3">
        <button type="button" onclick="closeCustomPageModal()" class="px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 font-bold text-xs">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-brandGreen text-brandBlack font-extrabold text-xs shadow-lg shadow-brandGreen/10">Create Page</button>
      </div>
    </form>
  </div>
</div>

<!-- JavaScript Tab & Graph & CRUD Configs -->
<script>
    window.seoMetaRecords = <?php echo json_encode($seo_records ?: []); ?>;

    // =====================================================================
    // GLOBAL IMAGE UPLOAD HELPER
    // Uploads a chosen file to /api.php?action=media-upload,
    // then fills targetInputId with the returned URL and updates preview.
    // =====================================================================
    function uploadImageFile(fileInput, targetInputId, previewImgId) {
        const file = fileInput.files[0];
        if (!file) return;

        // Show uploading indicator
        const targetInput = document.getElementById(targetInputId);
        const prevVal = targetInput ? targetInput.value : '';
        if (targetInput) targetInput.value = '⏳ Uploading...';

        const fd = new FormData();
        fd.append('file', file);

        fetch('/api.php?action=media-upload', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    const url = data.data.file_url;
                    if (targetInput) targetInput.value = url;
                    if (previewImgId) {
                        const preview = document.getElementById(previewImgId);
                        if (preview) {
                            preview.src = url;
                            preview.style.display = '';
                            preview.classList.remove('hidden');
                        }
                    }
                    // Flash green border on success
                    if (targetInput) {
                        targetInput.style.borderColor = '#8DC63F';
                        setTimeout(() => { targetInput.style.borderColor = ''; }, 2000);
                    }
                } else {
                    if (targetInput) targetInput.value = prevVal;
                    alert('Upload failed: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(err => {
                if (targetInput) targetInput.value = prevVal;
                alert('Upload error: ' + err);
            });
    }

    // Updates an img preview when URL is typed manually
    function updateImgPreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        if (!input || !preview) return;
        const url = input.value.trim();
        if (url && url.startsWith('http') || url.startsWith('/')) {
            preview.src = url;
            preview.style.display = '';
            preview.classList.remove('hidden');
        }
    }

    // Switches active dashboard views
    function switchTab(tabName, btn) {
        // Hide all panes
        document.querySelectorAll(".admin-tab-pane").forEach(pane => pane.classList.add("hidden"));
        
        // Reset all tab buttons to light-mode inactive state
        document.querySelectorAll(".tab-btn").forEach(b => {
            b.classList.remove("text-[#8DC63F]", "bg-[#8DC63F]/10", "text-white", "bg-white/5", "text-gray-900");
            b.classList.add("text-gray-600");
        });

        // Show active pane & highlight the clicked button
        const activePane = document.getElementById(`tab_${tabName}`);
        if (activePane) {
            activePane.classList.remove("hidden");
        }
        if (btn) {
            btn.classList.remove("text-gray-600");
            btn.classList.add("text-[#8DC63F]", "bg-[#8DC63F]/10");
        }
    }


    // Chart layouts
    document.addEventListener("DOMContentLoaded", () => {
        // Mock analytics data counts
        const regCtx = document.getElementById('monthlyRegistrationsChart');
        if (regCtx) {
            new Chart(regCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Registrations',
                        data: [15, 30, 42, 65, 80, <?php echo $total_registrations; ?>],
                        borderColor: '#8DC63F',
                        backgroundColor: 'rgba(141, 198, 63, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            grid: { color: 'rgba(255, 255, 255, 0.05)' },
                            ticks: { color: '#888' }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#888' }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        const catCtx = document.getElementById('categoriesChart');
        if (catCtx) {
            new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Workshops', 'Hackathons', 'Meetups'],
                    datasets: [{
                        data: [18, 8, 12],
                        backgroundColor: ['#8DC63F', '#3B82F6', '#9CA3AF'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
        populatePageDropdowns();
    });

    // Suspend / Approve users
    function updateUserStatus(userId, status) {
        const formData = new FormData();
        formData.append("id", userId);
        formData.append("status", status);

        fetch("/api.php?action=update-user-status", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    // Filter Users Table
    function filterUsersTable(filterVal) {
        const rows = document.querySelectorAll("#adminUsersTableBody tr");
        rows.forEach(row => {
            const isVerified = row.getAttribute("data-verified") === "1";
            if (filterVal === "all") {
                row.style.display = "";
            } else if (filterVal === "verified" && isVerified) {
                row.style.display = "";
            } else if (filterVal === "unverified" && !isVerified) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    // Admin manually verify user
    function adminVerifyUser(userId) {
        if (!confirm("Are you sure you want to manually verify this user's email address?")) return;
        const formData = new FormData();
        formData.append("user_id", userId);

        fetch("/api.php?action=admin-verify-user", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") {
                window.location.reload();
            }
        });
    }

    // Admin resend verification OTP
    function adminResendVerification(userId) {
        const formData = new FormData();
        formData.append("user_id", userId);

        fetch("/api.php?action=admin-resend-verification", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            let msg = data.message;
            if (data.otp_fallback) {
                msg += ` (Simulated OTP: ${data.otp_fallback})`;
            }
            alert(msg);
        });
    }

    // Admin delete fake user
    function adminDeleteUser(userId) {
        if (!confirm("Are you sure you want to permanently delete this user account? This cannot be undone.")) return;
        const formData = new FormData();
        formData.append("user_id", userId);

        fetch("/api.php?action=admin-delete-user", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.status === "success") {
                window.location.reload();
            }
        });
    }

    // Modal Operations for Event CRUD
    function openAdminEventModal(evt) {
        const titleEl = document.getElementById("adminEventModalTitle");
        const formId = document.getElementById("evt_form_id");
        
        const title = document.getElementById("evt_form_title");
        const desc = document.getElementById("evt_form_desc");
        const cat = document.getElementById("evt_form_cat");
        const org = document.getElementById("evt_form_org");
        const date = document.getElementById("evt_form_date");
        const time = document.getElementById("evt_form_time");
        const cap = document.getElementById("evt_form_cap");
        const deadline = document.getElementById("evt_form_deadline");
        const venue = document.getElementById("evt_form_venue");
        const banner = document.getElementById("evt_form_banner");

        if (evt === 0) {
            // Create mode
            titleEl.innerText = "Create Event Session";
            formId.value = 0;
            document.getElementById("adminEventForm").reset();
        } else {
            // Edit mode
            titleEl.innerText = "Edit Event Details";
            formId.value = evt.id;
            
            title.value = evt.title;
            desc.value = evt.description;
            cat.value = evt.category;
            org.value = evt.organizer || 'Yuvalay MakerSpace';
            date.value = evt.event_date;
            time.value = evt.event_time;
            cap.value = evt.capacity;
            venue.value = evt.venue;
            banner.value = evt.banner_image;

            // ISO date format format conversion for datetime-local (Y-m-d H:i:s -> Y-m-dTH:i)
            if (evt.registration_deadline) {
                deadline.value = evt.registration_deadline.replace(" ", "T").substring(0, 16);
            }
        }

        document.getElementById("adminEventModal").classList.remove("hidden");
    }

    function closeAdminEventModal() {
        document.getElementById("adminEventModal").classList.add("hidden");
    }

    // Submit Event
    const eventForm = document.getElementById("adminEventForm");
    if (eventForm) {
        eventForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const formId = document.getElementById("evt_form_id").value;
            const action = formId > 0 ? 'update-event' : 'create-event';
            const formData = new FormData(eventForm);

            fetch(`/api.php?action=${action}`, {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Session saved successfully!");
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }

    // Duplicate session
    function duplicateAdminEvent(evt) {
        if (!confirm(`Do you want to duplicate "${evt.title}"?`)) return;

        const formData = new FormData();
        formData.append("title", `${evt.title} (Copy)`);
        formData.append("description", evt.description);
        formData.append("category", evt.category);
        formData.append("organizer", evt.organizer || 'Yuvalay MakerSpace');
        formData.append("event_date", evt.event_date);
        formData.append("event_time", evt.event_time);
        formData.append("capacity", evt.capacity);
        formData.append("venue", evt.venue);
        formData.append("banner_image", evt.banner_image);
        formData.append("registration_deadline", evt.registration_deadline);

        fetch("/api.php?action=create-event", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                alert("Session duplicated!");
                window.location.reload();
            } else {
                alert(data.message);
            }
        });
    }

    // Delete Session
    function deleteAdminEvent(id) {
        if (!confirm("Are you sure you want to delete this event? This will remove all associated RSVPs.")) return;

        const formData = new FormData();
        formData.append("id", id);

        fetch("/api.php?action=delete-event", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById(`admin_evt_row_${id}`).remove();
            } else {
                alert(data.message);
            }
        });
    }

    // Delete Resource
    function deleteAdminResource(id) {
        if (!confirm("Are you sure you want to delete this resource document?")) return;

        const formData = new FormData();
        formData.append("id", id);

        fetch("/api.php?action=delete-resource", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById(`admin_res_row_${id}`).remove();
            } else {
                alert(data.message);
            }
        });
    }

    // Testimonial modal triggers
    function openTestimonialModal() {
        document.getElementById("adminTestModal").classList.remove("hidden");
    }
    function closeTestimonialModal() {
        document.getElementById("adminTestModal").classList.add("hidden");
    }

    // Testimonial Submit Form
    const testForm = document.getElementById("adminTestForm");
    if (testForm) {
        testForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(testForm);

            fetch("/api.php?action=create-testimonial", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Testimonial added!");
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            });
        });
    }

    function deleteAdminTestimonial(id) {
        if (!confirm("Delete this testimonial?")) return;

        const formData = new FormData();
        formData.append("id", id);

        fetch("/api.php?action=delete-testimonial", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                document.getElementById(`admin_test_card_${id}`).remove();
            } else {
                alert(data.message);
            }
        });
    }

    // Export RSVPs to CSV (Excel compatible)
    function exportRegistrationsToCSV() {
        const table = document.getElementById("registrationsAuditTable");
        let csv = [];
        const rows = table.querySelectorAll("tr");
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll("td, th");
            
            for (let j = 0; j < cols.length; j++) {
                // Clean content and remove double spaces / lines
                let data = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/(\s\s+)/gm, " ");
                data = data.replace(/"/g, '""'); // escape quotes
                row.push('"' + data + '"');
            }
            csv.push(row.join(","));
        }
        
        // Download trigger
        const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `yuvalay_rsvps_export_${new Date().toISOString().substring(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // === NEW DASHBOARD MODULES JS ===

    // 1. User role update
    function updateUserRole(userId, newRole) {
        const formData = new FormData();
        formData.append("id", userId);
        formData.append("role", newRole);

        fetch("/api.php?action=update-user-role", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("User role upgraded successfully!");
            } else {
                alert(data.message);
            }
        });
    }

    // 2. Design settings save
    function saveDesignSettings(e) {
        e.preventDefault();
        const form = document.getElementById("designSettingsForm");
        const fd = new FormData(form);
        const promises = [];

        for (const [key, val] of fd.entries()) {
            const data = new FormData();
            data.append("key", key);
            data.append("value", val);
            promises.push(
                fetch("/api.php?action=update-cms-text", {
                    method: "POST",
                    body: data
                }).then(res => res.json())
            );
        }

        Promise.all(promises)
            .then(results => {
                showDashboardToast("Branding and design settings saved! Refreshing...");
                setTimeout(() => window.location.reload(), 1200);
            })
            .catch(err => {
                console.error(err);
                alert("Error saving branding settings.");
            });
    }

    // 3. Media library operations
    function loadMediaLibrary() {
        const grid = document.getElementById("mediaLibraryGrid");
        if (!grid) return;
        grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-6"><i class="fa-solid fa-spinner animate-spin text-xl mr-2"></i> Loading media library...</div>';

        fetch("/api.php?action=get-media")
            .then(res => res.json())
            .then(data => {
                grid.innerHTML = "";
                if (data.status === "success" && data.media && data.media.length > 0) {
                    window.mediaLibraryAssets = data.media;
                    renderMediaGrid(data.media);
                } else {
                    grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-6">No media uploaded yet.</div>';
                }
            });
    }

    function renderMediaGrid(assets) {
        const grid = document.getElementById("mediaLibraryGrid");
        if (!grid) return;
        grid.innerHTML = "";
        assets.forEach(asset => {
            const isImage = asset.file_type.startsWith("image/");
            const thumb = isImage ? asset.file_url : "https://placehold.co/150x150?text=File";
            
            const card = document.createElement("div");
            card.className = "bg-[#151515] border border-white/5 p-3 rounded-2xl flex flex-col justify-between group relative overflow-hidden media-asset-card";
            card.setAttribute("data-name", asset.file_name.toLowerCase());
            
            card.innerHTML = `
                <div class="aspect-square rounded-xl overflow-hidden bg-gray-900 border border-white/5 mb-3 flex items-center justify-center relative">
                    <img src="${thumb}" class="w-full h-full object-cover">
                </div>
                <div class="space-y-1 text-left">
                    <span class="block text-[10px] text-gray-400 truncate" title="${asset.file_name}">${asset.file_name}</span>
                    <span class="block text-[9px] text-gray-500 font-mono">${(asset.file_size/1024).toFixed(1)} KB</span>
                </div>
                <div class="flex gap-1.5 mt-3">
                    <button onclick="copyMediaUrl('${asset.file_url}')" class="flex-grow py-1.5 bg-white/5 hover:bg-white/10 text-gray-300 hover:text-white rounded-lg text-[9px] font-bold transition-all"><i class="fa-solid fa-copy mr-1"></i> URL</button>
                    <button onclick="deleteMediaAsset(${asset.id}, this)" class="px-2 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:text-red-300 rounded-lg text-[9px] font-bold transition-all"><i class="fa-solid fa-trash-can"></i></button>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    function filterMediaLibrary() {
        const query = document.getElementById("mediaSearchInput").value.toLowerCase();
        const cards = document.querySelectorAll(".media-asset-card");
        cards.forEach(card => {
            const name = card.getAttribute("data-name");
            if (name.includes(query)) {
                card.style.display = "flex";
            } else {
                card.style.display = "none";
            }
        });
    }

    function uploadMediaAsset(e) {
        if (e) e.preventDefault();
        const fileInput = document.getElementById("media_file_input");
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) return;

        const file = fileInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert("File size exceeds the 5MB limit.");
            return;
        }

        const formData = new FormData();
        formData.append("file", file);
        formData.append("folder", "Dashboard");

        fetch("/api.php?action=media-upload", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("Media uploaded successfully!");
                const displayEl = document.getElementById("media_file_name_display");
                if (displayEl) displayEl.innerText = "Choose File";
                fileInput.value = "";
                loadMediaLibrary();
            } else {
                alert(data.message);
            }
        });
    }

    function copyMediaUrl(url) {
        const fullUrl = window.location.origin + url;
        navigator.clipboard.writeText(fullUrl).then(() => {
            showDashboardToast("URL copied to clipboard!");
        });
    }

    function deleteMediaAsset(id, btn) {
        if (!confirm("Are you sure you want to delete this media file?")) return;
        
        const formData = new FormData();
        formData.append("table", "media_library");
        formData.append("id", id);

        fetch("/api.php?action=delete-cms-item", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("File deleted from library.");
                btn.closest(".media-asset-card").remove();
            } else {
                alert(data.message);
            }
        });
    }

    // 4. Page blocks builder operations
    window.pageBlocksData = [];

    function loadPageBlocks() {
        const pageSelect = document.getElementById("blocks_page_select");
        if (!pageSelect) return;
        const page = pageSelect.value;
        const container = document.getElementById("pageBlocksList");
        if (!container) return;
        container.innerHTML = '<div class="text-center text-gray-500 py-6"><i class="fa-solid fa-spinner animate-spin text-xl mr-2"></i> Loading blocks...</div>';

        fetch("/api.php?action=get-page-blocks&page_name=" + page)
            .then(res => res.json())
            .then(data => {
                container.innerHTML = "";
                if (data.status === "success") {
                    window.pageBlocksData = data.blocks;
                    renderPageBlocksList();
                } else {
                    container.innerHTML = '<div class="text-center text-red-400">Failed to load sections.</div>';
                }
            });
    }

    function renderPageBlocksList() {
        const container = document.getElementById("pageBlocksList");
        if (!container) return;
        container.innerHTML = "";
        
        if (window.pageBlocksData.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8 border-2 border-dashed border-white/5 rounded-2xl">No custom page blocks found. Add a block to begin.</div>';
            return;
        }

        window.pageBlocksData.forEach((block, index) => {
            let content = {};
            if (typeof block.block_content === 'string') {
                try { content = json_decode_soft(block.block_content); } catch(e) {}
            } else {
                content = block.block_content || {};
            }

            const wrapper = document.createElement("div");
            wrapper.className = "bg-[#151515] border border-white/5 rounded-2xl p-5 space-y-4 text-left relative group/block";
            
            let inputsHtml = "";
            if (block.block_type === 'rich_text') {
                inputsHtml = `
                    <div>
                        <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">HTML / Markdown Content</label>
                        <textarea onchange="updateBlockContent(${index}, 'html', this.value)" rows="4" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">${content.html || ''}</textarea>
                    </div>
                `;
            } else if (block.block_type === 'text_image') {
                inputsHtml = `
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Text Content</label>
                            <textarea onchange="updateBlockContent(${index}, 'text', this.value)" rows="3" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs">${content.text || ''}</textarea>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Image URL</label>
                                <input type="text" onchange="updateBlockContent(${index}, 'image_url', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl px-3 py-2 text-white text-xs" value="${content.image_url || ''}">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Image Alignment</label>
                                    <select onchange="updateBlockContent(${index}, 'image_align', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl px-2 py-1.5 text-white text-xs">
                                        <option value="right" ${content.image_align === 'right' ? 'selected' : ''}>Right</option>
                                        <option value="left" ${content.image_align === 'left' ? 'selected' : ''}>Left</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Btn Text</label>
                                    <input type="text" onchange="updateBlockContent(${index}, 'btn_text', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl px-2 py-1 text-white text-xs" value="${content.btn_text || ''}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else if (block.block_type === 'features_grid') {
                const cards = content.cards || [];
                let cardsHtml = "";
                cards.forEach((c, cIdx) => {
                    cardsHtml += `
                        <div class="p-3 bg-brandBlack border border-white/10 rounded-xl grid grid-cols-1 sm:grid-cols-3 gap-2 text-xs relative">
                            <div>
                                <label class="block text-gray-500 text-[9px] mb-0.5 font-bold">Icon Class</label>
                                <input type="text" onchange="updateFeatureCard(${index}, ${cIdx}, 'icon', this.value)" class="w-full bg-brandDarkGray border border-white/5 rounded px-2 py-1 text-white font-mono" value="${c.icon || 'fa-solid fa-cube'}">
                            </div>
                            <div>
                                <label class="block text-gray-500 text-[9px] mb-0.5 font-bold">Card Title</label>
                                <input type="text" onchange="updateFeatureCard(${index}, ${cIdx}, 'title', this.value)" class="w-full bg-brandDarkGray border border-white/5 rounded px-2 py-1 text-white" value="${c.title || ''}">
                            </div>
                            <div class="flex gap-2 items-center">
                                <div class="flex-grow">
                                    <label class="block text-gray-500 text-[9px] mb-0.5 font-bold">Card Desc</label>
                                    <input type="text" onchange="updateFeatureCard(${index}, ${cIdx}, 'desc', this.value)" class="w-full bg-brandDarkGray border border-white/5 rounded px-2 py-1 text-white" value="${c.desc || ''}">
                                </div>
                                <button onclick="deleteFeatureCard(${index}, ${cIdx})" class="text-red-400 hover:text-red-300 mt-4"><i class="fa-solid fa-circle-minus"></i></button>
                            </div>
                        </div>
                    `;
                });
                inputsHtml = `
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <label class="block text-gray-500 font-semibold text-[10px] uppercase font-bold">Grid Feature Cards</label>
                            <button onclick="addFeatureCard(${index})" class="px-2.5 py-1 bg-white/5 hover:bg-white/10 text-brandGreen font-bold text-[10px] rounded-lg border border-brandGreen/25"><i class="fa-solid fa-plus-circle"></i> Add Card</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            ${cardsHtml}
                        </div>
                    </div>
                `;
            } else if (block.block_type === 'cta_banner') {
                inputsHtml = `
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Banner Text</label>
                            <input type="text" onchange="updateBlockContent(${index}, 'text', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen text-xs" value="${content.text || ''}">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Button Text</label>
                                <input type="text" onchange="updateBlockContent(${index}, 'btn_text', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl px-2 py-1.5 text-white text-xs" value="${content.btn_text || ''}">
                            </div>
                            <div>
                                <label class="block text-gray-500 font-semibold mb-1 text-[10px] uppercase font-bold">Button Link</label>
                                <input type="text" onchange="updateBlockContent(${index}, 'btn_link', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-xl px-2 py-1.5 text-white text-xs" value="${content.btn_link || ''}">
                            </div>
                        </div>
                    </div>
                `;
            }

            wrapper.innerHTML = `
                <div class="flex justify-between items-center border-b border-white/5 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-brandGreen/10 border border-brandGreen/25 text-brandGreen font-bold text-[9px] uppercase font-mono">${block.block_type}</span>
                        <input type="text" onchange="updateBlockTitle(${index}, this.value)" class="bg-transparent font-bold text-sm text-white focus:outline-none focus:border-brandGreen" value="${block.block_title || ''}" placeholder="Section Title">
                    </div>
                    <div class="flex gap-2 text-xs">
                        <button onclick="movePageBlock(${index}, -1)" class="p-1 hover:bg-white/5 rounded text-gray-400" title="Move Up"><i class="fa-solid fa-arrow-up"></i></button>
                        <button onclick="movePageBlock(${index}, 1)" class="p-1 hover:bg-white/5 rounded text-gray-400" title="Move Down"><i class="fa-solid fa-arrow-down"></i></button>
                        <button onclick="deletePageBlock(${index})" class="p-1 hover:bg-red-500/10 rounded text-red-400" title="Delete Block"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </div>
                ${inputsHtml}
            `;
            container.appendChild(wrapper);
        });
    }

    function json_decode_soft(str) {
        try {
            return JSON.parse(str);
        } catch(e) {
            return {};
        }
    }

    function updateBlockTitle(idx, val) {
        window.pageBlocksData[idx].block_title = val;
    }

    function updateBlockContent(blockIdx, key, val) {
        let content = {};
        if (typeof window.pageBlocksData[blockIdx].block_content === 'string') {
            content = json_decode_soft(window.pageBlocksData[blockIdx].block_content);
        } else {
            content = window.pageBlocksData[blockIdx].block_content || {};
        }
        content[key] = val;
        window.pageBlocksData[blockIdx].block_content = content;
    }

    function addFeatureCard(blockIdx) {
        let content = {};
        if (typeof window.pageBlocksData[blockIdx].block_content === 'string') {
            content = json_decode_soft(window.pageBlocksData[blockIdx].block_content);
        } else {
            content = window.pageBlocksData[blockIdx].block_content || {};
        }
        if (!content.cards) content.cards = [];
        content.cards.push({ icon: 'fa-solid fa-cube', title: 'New Card', desc: 'Description details.' });
        window.pageBlocksData[blockIdx].block_content = content;
        renderPageBlocksList();
    }

    function updateFeatureCard(blockIdx, cardIdx, key, val) {
        let content = json_decode_soft(window.pageBlocksData[blockIdx].block_content);
        if (!content.cards) content.cards = [];
        if (content.cards[cardIdx]) {
            content.cards[cardIdx][key] = val;
        }
        window.pageBlocksData[blockIdx].block_content = content;
    }

    function deleteFeatureCard(blockIdx, cardIdx) {
        let content = json_decode_soft(window.pageBlocksData[blockIdx].block_content);
        if (content.cards && content.cards[cardIdx]) {
            content.cards.splice(cardIdx, 1);
        }
        window.pageBlocksData[blockIdx].block_content = content;
        renderPageBlocksList();
    }

    function movePageBlock(idx, dir) {
        const targetIdx = idx + dir;
        if (targetIdx < 0 || targetIdx >= window.pageBlocksData.length) return;
        
        const temp = window.pageBlocksData[idx];
        window.pageBlocksData[idx] = window.pageBlocksData[targetIdx];
        window.pageBlocksData[targetIdx] = temp;
        
        renderPageBlocksList();
    }

    function deletePageBlock(idx) {
        if (!confirm("Are you sure you want to delete this custom section block?")) return;
        window.pageBlocksData.splice(idx, 1);
        renderPageBlocksList();
    }

    function addNewPageBlock() {
        const type = prompt("Enter block template type: rich_text, text_image, features_grid, cta_banner");
        if (!type || !['rich_text', 'text_image', 'features_grid', 'cta_banner'].includes(type)) {
            alert("Invalid type. Supported types: rich_text, text_image, features_grid, cta_banner");
            return;
        }
        
        const newBlock = {
            block_type: type,
            block_title: "New Block " + (window.pageBlocksData.length + 1),
            block_content: {},
            is_active: 1
        };

        if (type === 'features_grid') {
            newBlock.block_content = { cards: [{ icon: 'fa-solid fa-cube', title: 'Feature 1', desc: 'Details.' }] };
        } else if (type === 'text_image') {
            newBlock.block_content = { text: 'Paragraph content.', image_url: '', image_align: 'right' };
        }

        window.pageBlocksData.push(newBlock);
        renderPageBlocksList();
    }

    function savePageBlocks() {
        const pageSelect = document.getElementById("blocks_page_select");
        if (!pageSelect) return;
        const page = pageSelect.value;
        const formData = new FormData();
        formData.append("page_name", page);
        formData.append("blocks", JSON.stringify(window.pageBlocksData));

        fetch("/api.php?action=save-page-blocks", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("Page builder layout saved successfully!");
                loadPageBlocks();
            } else {
                alert(data.message);
            }
        });
    }

    // 5. Dynamic Event Form Builder operations
    window.eventFormFieldsData = [];

    function loadEventFormFields() {
        const selectEl = document.getElementById("form_builder_event_select");
        if (!selectEl) return;
        const evtId = selectEl.value;
        const container = document.getElementById("eventFieldsList");
        if (!container) return;
        const wrapper = document.getElementById("saveFieldsBtnWrapper");

        if (!evtId) {
            container.innerHTML = '<div class="text-center text-gray-500 py-6">Please select an event above to start designing its questions.</div>';
            if (wrapper) wrapper.classList.add("hidden");
            return;
        }

        container.innerHTML = '<div class="text-center text-gray-500 py-6"><i class="fa-solid fa-spinner animate-spin text-xl mr-2"></i> Loading fields...</div>';

        fetch("/api.php?action=get-event-fields&event_id=" + evtId)
            .then(res => res.json())
            .then(data => {
                container.innerHTML = "";
                if (data.status === "success") {
                    window.eventFormFieldsData = data.fields;
                    if (wrapper) wrapper.classList.remove("hidden");
                    renderFormFieldList();
                } else {
                    container.innerHTML = '<div class="text-center text-red-400">Failed to load inputs.</div>';
                }
            });
    }

    function renderFormFieldList() {
        const container = document.getElementById("eventFieldsList");
        if (!container) return;
        container.innerHTML = "";

        if (window.eventFormFieldsData.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8 border border-white/5 rounded-2xl">This event registration form currently uses the default questions. Add a custom question below to build a custom registration sheet!</div>';
            return;
        }

        window.eventFormFieldsData.forEach((field, index) => {
            const row = document.createElement("div");
            row.className = "bg-[#151515] border border-white/5 rounded-2xl p-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center text-left";
            
            row.innerHTML = `
                <div class="md:col-span-3">
                    <label class="block text-gray-500 text-[10px] uppercase font-bold mb-0.5">Field Label / Question</label>
                    <input type="text" onchange="updateFormField(${index}, 'field_label', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-lg px-3 py-2 text-xs text-white" value="${field.field_label || ''}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-500 text-[10px] uppercase font-bold mb-0.5 font-mono">Internal Code Name</label>
                    <input type="text" onchange="updateFormField(${index}, 'field_name', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-lg px-3 py-2 text-xs text-white font-mono" value="${field.field_name || ''}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-500 text-[10px] uppercase font-bold mb-0.5">Input Style</label>
                    <select onchange="updateFormField(${index}, 'field_type', this.value)" class="w-full bg-brandBlack border border-white/10 rounded-lg px-2 py-2 text-xs text-white">
                        <option value="text" ${field.field_type === 'text' ? 'selected' : ''}>Short Text</option>
                        <option value="number" ${field.field_type === 'number' ? 'selected' : ''}>Numeric Input</option>
                        <option value="email" ${field.field_type === 'email' ? 'selected' : ''}>Email Address</option>
                        <option value="textarea" ${field.field_type === 'textarea' ? 'selected' : ''}>Long Essay Area</option>
                        <option value="select" ${field.field_type === 'select' ? 'selected' : ''}>Dropdown Select Menu</option>
                        <option value="checkbox" ${field.field_type === 'checkbox' ? 'selected' : ''}>Multiple Checkbox Choice</option>
                        <option value="file" ${field.field_type === 'file' ? 'selected' : ''}>File Uploader</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-gray-500 text-[10px] uppercase font-bold mb-0.5">Options List (comma-separated)</label>
                    <input type="text" onchange="updateFormField(${index}, 'field_options', this.value)" placeholder="Choice 1, Choice 2" class="w-full bg-brandBlack border border-white/10 rounded-lg px-3 py-2 text-xs text-white" value="${field.field_options || ''}">
                </div>
                <div class="md:col-span-1 flex flex-col items-center">
                    <label class="block text-gray-500 text-[8px] uppercase font-bold mb-1">Required</label>
                    <input type="checkbox" onchange="updateFormField(${index}, 'is_required', this.checked ? 1 : 0)" class="w-4 h-4 accent-brandGreen" ${field.is_required == 1 ? 'checked' : ''}>
                </div>
                <div class="md:col-span-1 flex gap-1 justify-end">
                    <button onclick="moveFormField(${index}, -1)" class="p-1.5 hover:bg-white/5 rounded text-gray-400" title="Move Up"><i class="fa-solid fa-arrow-up"></i></button>
                    <button onclick="moveFormField(${index}, 1)" class="p-1.5 hover:bg-white/5 rounded text-gray-400" title="Move Down"><i class="fa-solid fa-arrow-down"></i></button>
                    <button onclick="deleteFormField(${index})" class="p-1.5 hover:bg-red-500/10 rounded text-red-400" title="Delete Question"><i class="fa-solid fa-circle-minus"></i></button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    function updateFormField(idx, key, val) {
        window.eventFormFieldsData[idx][key] = val;
    }

    function addNewFormField() {
        const defaultCode = "custom_question_" + (window.eventFormFieldsData.length + 1);
        window.eventFormFieldsData.push({
            field_name: defaultCode,
            field_label: "Custom Registration Question",
            field_type: "text",
            field_options: "",
            is_required: 0
        });
        renderFormFieldList();
    }

    function moveFormField(idx, dir) {
        const targetIdx = idx + dir;
        if (targetIdx < 0 || targetIdx >= window.eventFormFieldsData.length) return;
        
        const temp = window.eventFormFieldsData[idx];
        window.eventFormFieldsData[idx] = window.eventFormFieldsData[targetIdx];
        window.eventFormFieldsData[targetIdx] = temp;
        
        renderFormFieldList();
    }

    function deleteFormField(idx) {
        window.eventFormFieldsData.splice(idx, 1);
        renderFormFieldList();
    }

    function saveEventFormFields() {
        const selectEl = document.getElementById("form_builder_event_select");
        if (!selectEl) return;
        const evtId = selectEl.value;
        const formData = new FormData();
        formData.append("event_id", evtId);
        formData.append("fields", JSON.stringify(window.eventFormFieldsData));

        fetch("/api.php?action=save-event-fields", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("Event custom questions saved!");
                loadEventFormFields();
            } else {
                alert(data.message);
            }
        });
    }

    // 6. SEO Meta & Sitemap Desk operations
    function loadPageSeoData() {
        const selectEl = document.getElementById("seo_page_select");
        if (!selectEl) return;
        const page = selectEl.value;
        const record = window.seoMetaRecords.find(r => r.page_name === page) || { id: 0, meta_title: '', meta_description: '', keywords: '', og_image: '' };
        
        const idEl = document.getElementById("seo_meta_id");
        const titleEl = document.getElementById("seo_meta_title");
        const descEl = document.getElementById("seo_meta_desc");
        const kwEl = document.getElementById("seo_keywords");
        const ogEl = document.getElementById("seo_og_image");

        if (idEl) idEl.value = record.id;
        if (titleEl) titleEl.value = record.meta_title;
        if (descEl) descEl.value = record.meta_description;
        if (kwEl) kwEl.value = record.keywords || '';
        if (ogEl) ogEl.value = record.og_image || '';
    }

    function savePageSeoData(event) {
        if (event) event.preventDefault();
        const idEl = document.getElementById("seo_meta_id");
        if (!idEl) return;
        const id = idEl.value;
        const page = document.getElementById("seo_page_select").value;
        
        const title = document.getElementById("seo_meta_title").value;
        const desc = document.getElementById("seo_meta_desc").value;
        const keywords = document.getElementById("seo_keywords").value;
        const ogImage = document.getElementById("seo_og_image").value;

        const formData = new FormData();
        formData.append("table", "seo_meta");
        formData.append("id", id);
        formData.append("fields[page_name]", page);
        formData.append("fields[meta_title]", title);
        formData.append("fields[meta_description]", desc);
        formData.append("fields[keywords]", keywords);
        formData.append("fields[og_image]", ogImage);

        fetch("/api.php?action=update-cms-list", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                showDashboardToast("SEO metadata saved!");
                // Update local memory
                const recIdx = window.seoMetaRecords.findIndex(r => r.page_name === page);
                const updatedRec = { id: id > 0 ? id : data.insert_id, page_name: page, meta_title: title, meta_description: desc, keywords: keywords, og_image: ogImage };
                if (recIdx > -1) {
                    window.seoMetaRecords[recIdx] = updatedRec;
                } else {
                    window.seoMetaRecords.push(updatedRec);
                }
                loadPageSeoData();
            } else {
                alert(data.message);
            }
        });
    }

    function regenerateSeoFiles() {
        fetch("/api.php?action=generate-seo-files")
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    showDashboardToast(data.message);
                } else {
                    alert(data.message);
                }
            });
    }

    // =====================================================================
    // 8. CUSTOM PAGES MANAGER OPERATIONS
    // =====================================================================
    function populatePageDropdowns() {
        fetch('/api.php?action=get-custom-pages')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const customPages = data.pages || [];
                    
                    // 1. Populate Page Section Builder Dropdown
                    const blocksSelect = document.getElementById("blocks_page_select");
                    if (blocksSelect) {
                        const currentVal = blocksSelect.value;
                        let html = `
                            <option value="home">Home Page</option>
                            <option value="about">About Us</option>
                            <option value="what-we-do">What We Do</option>
                            <option value="resources">Resources</option>
                            <option value="events">Events & Calendar</option>
                            <option value="contact">Contact Us</option>
                        `;
                        customPages.forEach(p => {
                            html += `<option value="${p.slug}">${p.title} (Custom)</option>`;
                        });
                        blocksSelect.innerHTML = html;
                        if (currentVal && [...blocksSelect.options].some(o => o.value === currentVal)) {
                            blocksSelect.value = currentVal;
                        }
                    }
                    
                    // 2. Populate SEO Desk Dropdown
                    const seoSelect = document.getElementById("seo_page_select");
                    if (seoSelect) {
                        const currentVal = seoSelect.value;
                        let html = `
                            <option value="home">Home (index.php)</option>
                            <option value="about">About Us (about.php)</option>
                            <option value="what-we-do">What We Do (what-we-do.php)</option>
                            <option value="resources">Resources (resources.php)</option>
                            <option value="events">Events & Calendar (events.php)</option>
                            <option value="contact">Contact Us (contact.php)</option>
                        `;
                        customPages.forEach(p => {
                            html += `<option value="${p.slug}">${p.title} (Custom /page.php?slug=${p.slug})</option>`;
                        });
                        seoSelect.innerHTML = html;
                        if (currentVal && [...seoSelect.options].some(o => o.value === currentVal)) {
                            seoSelect.value = currentVal;
                        }
                    }
                }
            });
    }

    function loadCustomPages() {
        const body = document.getElementById("customPagesTableBody");
        if (!body) return;
        body.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-gray-500"><i class="fa-solid fa-spinner animate-spin mr-1"></i> Loading custom pages...</td></tr>';
        
        fetch('/api.php?action=get-custom-pages')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const pages = data.pages || [];
                    body.innerHTML = '';
                    if (pages.length === 0) {
                        body.innerHTML = '<tr><td colspan="5" class="py-8 text-center text-gray-500 border border-dashed border-white/5 rounded-2xl">No custom pages created yet. Click "Add Custom Page" to create one.</td></tr>';
                        return;
                    }
                    pages.forEach(p => {
                        const tr = document.createElement("tr");
                        tr.className = "border-b border-white/5 hover:bg-white/5 transition-colors";
                        
                        const statusClass = p.status === 'published' ? 'bg-brandGreen/10 text-brandGreen border-brandGreen/20' : 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20';
                        
                        tr.innerHTML = `
                            <td class="py-3 px-4 font-semibold text-white">${p.title}</td>
                            <td class="py-3 px-4 font-mono text-gray-400">
                                <a href="/page.php?slug=${p.slug}" target="_blank" class="hover:text-brandGreen underline">/page.php?slug=${p.slug} <i class="fa-solid fa-arrow-up-right-from-square text-[10px] ml-0.5"></i></a>
                            </td>
                            <td class="py-3 px-4">
                                <button onclick="togglePagePublish(${p.id}, '${p.status}')" class="px-2 py-0.5 rounded border text-[10px] font-bold uppercase transition-colors ${statusClass}" title="Click to toggle Status">
                                    ${p.status}
                                </button>
                            </td>
                            <td class="py-3 px-4 text-gray-500">${p.created_at}</td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button onclick="managePageLayout('${p.slug}')" class="px-2.5 py-1 bg-brandGreen/20 hover:bg-brandGreen/30 text-brandGreen font-bold rounded-lg border border-brandGreen/25 text-[10px]" title="Edit page blocks"><i class="fa-solid fa-shapes mr-1"></i> Layout</button>
                                <button onclick="editPageSeo('${p.slug}')" class="px-2.5 py-1 bg-white/5 hover:bg-white/10 text-white font-bold rounded-lg border border-white/10 text-[10px]" title="Edit meta seo tags"><i class="fa-solid fa-globe mr-1"></i> SEO</button>
                                <button onclick="deleteCustomPage(${p.id})" class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 font-bold rounded-lg border border-red-500/25 text-[10px]" title="Delete page"><i class="fa-solid fa-trash-can"></i></button>
                            </td>
                        `;
                        body.appendChild(tr);
                    });
                } else {
                    body.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-red-400">Error: ${data.message}</td></tr>`;
                }
            });
    }

    function openCreatePageModal() {
        document.getElementById("adminPageForm").reset();
        document.getElementById("slug_preview").innerText = '...';
        document.getElementById("adminPageModal").classList.remove("hidden");
    }

    function closeCustomPageModal() {
        document.getElementById("adminPageModal").classList.add("hidden");
    }

    function autoGenerateSlug(title) {
        const slug = title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '') // remove invalid chars
            .replace(/\s+/g, '-')         // collapse whitespace and replace by -
            .replace(/-+/g, '-');         // collapse dashes
        document.getElementById("page_form_slug").value = slug;
        document.getElementById("slug_preview").innerText = slug || '...';
    }

    function saveCustomPage(event) {
        if (event) event.preventDefault();
        const title = document.getElementById("page_form_title").value.trim();
        const slug = document.getElementById("page_form_slug").value.trim();
        const status = document.getElementById("page_form_status").value;

        if (!title || !slug) {
            alert("Title and Slug are required.");
            return;
        }

        const fd = new FormData();
        fd.append('table', 'custom_pages');
        fd.append('id', '0');
        fd.append('fields[title]', title);
        fd.append('fields[slug]', slug);
        fd.append('fields[status]', status);

        fetch('/api.php?action=update-cms-list', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showDashboardToast("Custom page created successfully!");
                    closeCustomPageModal();
                    loadCustomPages();
                    populatePageDropdowns();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Connection error: ' + err);
            });
    }

    function togglePagePublish(id, currentStatus) {
        const newStatus = currentStatus === 'published' ? 'draft' : 'published';
        const fd = new FormData();
        fd.append('table', 'custom_pages');
        fd.append('id', id);
        fd.append('fields[status]', newStatus);

        fetch('/api.php?action=update-cms-list', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showDashboardToast("Page status updated!");
                    loadCustomPages();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function managePageLayout(slug) {
        const select = document.getElementById("blocks_page_select");
        if (select) {
            select.value = slug;
            loadPageBlocks();
        }
        const blocksBtn = document.querySelector('[onclick*="blocks"]');
        if (blocksBtn) {
            switchTab('blocks', blocksBtn);
        }
    }

    // Swapping to custom search logic for tab button since switchTab uses key
    function editPageSeo(slug) {
        const select = document.getElementById("seo_page_select");
        if (select) {
            select.value = slug;
            loadPageSeoData();
        }
        const seoBtn = document.querySelector('[onclick*="seo"]');
        if (seoBtn) {
            switchTab('seo', seoBtn);
        }
    }

    function deleteCustomPage(id) {
        if (!confirm("Are you sure you want to delete this custom page? All page blocks and SEO configuration for this page will be deleted permanently.")) return;
        const fd = new FormData();
        fd.append('table', 'custom_pages');
        fd.append('id', id);

        fetch('/api.php?action=delete-cms-item', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showDashboardToast("Custom page deleted successfully!");
                    loadCustomPages();
                    populatePageDropdowns();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    // 7. General Helper Toast
    function showDashboardToast(msg) {
        const toast = document.createElement("div");
        toast.className = "fixed top-6 right-6 z-[999999] bg-[#FFFFFF] border border-[#8DC63F] text-black px-5 py-3 rounded-2xl shadow-2xl flex items-center gap-2 transform translate-y-2 opacity-0 transition-all duration-300 font-semibold text-xs";
        toast.innerHTML = `<i class="fa-solid fa-circle-check text-[#8DC63F] text-sm"></i> ${msg}`;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = "1";
            toast.style.transform = "translateY(0px)";
        }, 50);
        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transform = "translateY(-10px)";
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // Tab Load Triggers
    const originalSwitchTab = switchTab;
    switchTab = function(tabName, btn) {
        // If button is clicked or element reference is passed as class match
        let targetBtn = btn;
        if (!targetBtn && tabName) {
            targetBtn = document.querySelector(`[onclick*="${tabName}"]`);
        }
        originalSwitchTab(tabName, targetBtn);
        if (tabName === 'media') {
            loadMediaLibrary();
        } else if (tabName === 'blocks') {
            loadPageBlocks();
        } else if (tabName === 'seo') {
            loadPageSeoData();
        } else if (tabName === 'custom_pages') {
            loadCustomPages();
        }
    };
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
