<?php
/**
 * My Registrations Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Route guards
if (!$is_logged_in) {
    header("Location: /login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$user_id = $_SESSION['user_id'];
$registrations = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT r.*, e.title, e.event_date, e.event_time, e.venue, e.category, e.banner_image, e.registration_deadline 
                                FROM event_registrations r 
                                JOIN events e ON r.event_id = e.id 
                                WHERE r.user_id = :usr 
                                ORDER BY e.event_date ASC, e.event_time ASC");
        $stmt->execute(['usr' => $user_id]);
        $registrations = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle silently
    }
}

// Split registrations
$upcoming = [];
$past = [];
$cancelled = [];

$now = time();

foreach ($registrations as $r) {
    if ($r['status'] === 'Cancelled') {
        $cancelled[] = $r;
    } else {
        $event_ts = strtotime($r['event_date'] . ' ' . $r['event_time']);
        if ($event_ts >= $now) {
            $upcoming[] = $r;
        } else {
            $past[] = $r;
        }
    }
}
?>

<!-- Hero Banner -->
<section class="relative bg-[#090909] py-16 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left space-y-2 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">MEMBER PORTAL</span>
    <h1 class="text-3xl sm:text-4xl font-extrabold font-['Outfit'] tracking-tight">My Registrations</h1>
    <p class="text-gray-400 text-xs sm:text-sm">Manage your session bookings, ticket stubs, and certificate history.</p>
  </div>
</section>

<!-- My Registrations Container -->
<section class="py-16 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16">

    <!-- UPCOMING REGISTRATIONS -->
    <div class="space-y-6">
      <div class="text-left border-l-4 border-brandGreen pl-4">
        <h2 class="text-xl font-bold font-['Outfit'] text-white">Upcoming Events & Workshops</h2>
        <p class="text-xs text-gray-500 mt-0.5">Sessions you have booked that are yet to commence.</p>
      </div>

      <?php if (empty($upcoming)): ?>
        <div class="glass p-8 rounded-2xl text-center text-gray-500 border border-white/5">
          <i class="fa-solid fa-ticket-simple text-3xl mb-3 block"></i>
          <p class="text-sm">You do not have any upcoming registrations. <a href="/events.php" class="text-brandGreen hover:underline">Browse workshops</a> to book seats!</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php foreach ($upcoming as $r):
            $deadline_passed = (strtotime($r['registration_deadline']) < $now);
          ?>
            <div class="glass rounded-2xl border border-white/5 p-6 flex flex-col justify-between hover:border-brandGreen/25 transition-all" id="reg_card_<?php echo $r['registration_id']; ?>">
              <div class="space-y-4">
                <div class="flex justify-between items-start gap-4">
                  <div class="text-left">
                    <span class="inline-block px-2.5 py-1 rounded bg-[#8DC63F]/10 border border-[#8DC63F]/25 text-[#8DC63F] text-[10px] font-bold uppercase tracking-wider mb-2"><?php echo htmlspecialchars($r['category']); ?></span>
                    <h3 class="font-bold text-white text-base leading-snug"><?php echo htmlspecialchars($r['title']); ?></h3>
                  </div>
                  <span class="text-[10px] font-mono text-brandGreen font-bold bg-brandGreen/10 border border-brandGreen/20 px-2 py-0.5 rounded"><?php echo $r['registration_id']; ?></span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-400 text-left pt-2 border-t border-white/5">
                  <div><i class="fa-regular fa-calendar text-brandGreen mr-1"></i> <?php echo date('M d, Y', strtotime($r['event_date'])); ?></div>
                  <div><i class="fa-regular fa-clock text-brandGreen mr-1"></i> <?php echo date('h:i A', strtotime($r['event_time'])); ?></div>
                  <div class="col-span-2"><i class="fa-solid fa-location-dot text-brandGreen mr-1"></i> <?php echo htmlspecialchars($r['venue']); ?></div>
                </div>
              </div>

              <!-- Action Links -->
              <div class="flex gap-2 mt-6 border-t border-white/5 pt-4">
                <a href="/ticket.php?reg_id=<?php echo $r['registration_id']; ?>" target="_blank" class="flex-grow text-center py-2.5 rounded-xl font-bold bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack text-xs transition-all flex items-center justify-center gap-1.5">
                  <i class="fa-solid fa-print"></i> Get Ticket
                </a>
                <?php if (!$deadline_passed): ?>
                  <button onclick="cancelBooking('<?php echo $r['registration_id']; ?>')" class="px-4 py-2.5 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 text-xs font-semibold transition-all">
                    Cancel Booking
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- PAST REGISTRATIONS -->
    <div class="space-y-6">
      <div class="text-left border-l-4 border-gray-600 pl-4">
        <h2 class="text-xl font-bold font-['Outfit'] text-white">Past Participations</h2>
        <p class="text-xs text-gray-500 mt-0.5">Workshops and hackathons you have attended in the past.</p>
      </div>

      <?php if (empty($past)): ?>
        <div class="glass p-8 rounded-2xl text-center text-gray-500 border border-white/5">
          <p class="text-sm">No historical registrations found.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php foreach ($past as $r):
            $attended = ($r['attendance_status'] === 'present');
          ?>
            <div class="glass rounded-2xl border border-white/5 p-6 flex flex-col justify-between opacity-80">
              <div class="space-y-4">
                <div class="flex justify-between items-start gap-4">
                  <div class="text-left">
                    <span class="inline-block px-2.5 py-1 rounded bg-gray-500/10 border border-gray-500/20 text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-2"><?php echo htmlspecialchars($r['category']); ?></span>
                    <h3 class="font-bold text-gray-300 text-base leading-snug"><?php echo htmlspecialchars($r['title']); ?></h3>
                  </div>
                  <span class="text-[10px] font-mono text-gray-500 bg-white/5 border border-white/5 px-2 py-0.5 rounded"><?php echo $r['registration_id']; ?></span>
                </div>
                
                <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 text-left pt-2 border-t border-white/5">
                  <div><i class="fa-regular fa-calendar mr-1"></i> <?php echo date('M d, Y', strtotime($r['event_date'])); ?></div>
                  <div><i class="fa-regular fa-clock-three mr-1"></i> <?php echo date('h:i A', strtotime($r['event_time'])); ?></div>
                  <div class="col-span-2">
                    <i class="fa-solid fa-circle-check mr-1 <?php echo $attended ? 'text-brandGreen' : 'text-gray-600'; ?>"></i> 
                    Attendance: <strong class="<?php echo $attended ? 'text-brandGreen' : 'text-gray-400'; ?>"><?php echo $attended ? 'Present' : 'Absent'; ?></strong>
                  </div>
                </div>
              </div>

              <!-- Certificate Download -->
              <?php if ($attended): ?>
                <div class="mt-6 border-t border-white/5 pt-4">
                  <a href="/certificate.php?reg_id=<?php echo $r['registration_id']; ?>" target="_blank" class="w-full block text-center py-2.5 rounded-xl font-bold bg-brandDarkGray hover:bg-white/5 border border-brandGreen/40 hover:border-brandGreen text-brandGreen hover:text-brandGreen text-xs transition-all">
                    <i class="fa-solid fa-award mr-1"></i> Download Completion Certificate
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- CANCELLED REGISTRATIONS -->
    <?php if (!empty($cancelled)): ?>
      <div class="space-y-6">
        <div class="text-left border-l-4 border-red-500/50 pl-4">
          <h2 class="text-xl font-bold font-['Outfit'] text-white">Cancelled Registrations</h2>
          <p class="text-xs text-gray-500 mt-0.5">Bookings that you cancelled before session start.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 opacity-50">
          <?php foreach ($cancelled as $r): ?>
            <div class="glass rounded-xl border border-white/5 p-5 text-left space-y-2">
              <span class="text-[9px] font-bold uppercase tracking-wider text-red-400">Cancelled</span>
              <h4 class="font-bold text-gray-400 text-sm leading-snug line-clamp-1"><?php echo htmlspecialchars($r['title']); ?></h4>
              <p class="text-[10px] text-gray-500">Date: <?php echo date('M d, Y', strtotime($r['event_date'])); ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- Cancel Registration logic -->
<script>
    function cancelBooking(regId) {
        if (!confirm("Are you sure you want to cancel your registration for this session? This action will restore the seat and cannot be undone.")) {
            return;
        }

        const formData = new FormData();
        formData.append("registration_id", regId);

        fetch("/api.php?action=cancel-registration", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                alert("Registration cancelled successfully.");
                window.location.reload();
            } else {
                alert("Cancellation failed: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("An error occurred. Please try again.");
        });
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
