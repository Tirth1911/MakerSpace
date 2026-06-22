<?php
/**
 * Contact Us Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Retrieve values from DB if available
$map_url = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3691.012351280385!2d73.1895781!3d22.3153664!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x395fc88dfc5c7865%3A0xa6ebbb363a0d5b!2sVadodara%2C%20Gujarat!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin";
$working_hours = "Tuesday - Sunday: 10:00 AM - 08:00 PM (Monday Closed)";

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'contact_map'");
        $stmt->execute();
        $resMap = $stmt->fetch();
        if ($resMap) $map_url = $resMap['setting_value'];

        $stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'contact_hours'");
        $stmt->execute();
        $resHours = $stmt->fetch();
        if ($resHours) $working_hours = $resHours['setting_value'];
    } catch (PDOException $e) {
        // Fallback
    }
}
?>

<!-- Hero Banner -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">GET IN TOUCH</span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight">Contact Our Space</h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto">
      Have a question about our equipment, workshops, or certifications? Fill the form below or drop by.
    </p>
  </div>
</section>

<!-- Contact Form and Details -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
      
      <!-- LEFT COLUMN: Contact Details & Map -->
      <div class="lg:col-span-5 space-y-8 text-left">
        <div class="space-y-4">
          <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">COMMUNICATION PINS</span>
          <h2 class="text-2xl sm:text-3xl font-extrabold font-['Outfit']">How to reach us</h2>
          <p class="text-gray-400 text-sm leading-relaxed">
            Drop by our physical laboratory to inspect the equipment or send us an email.
          </p>
        </div>

        <div class="space-y-4 text-sm">
          <!-- Address -->
          <div class="flex items-start gap-4 p-5 bg-brandDarkGray border border-white/5 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen shrink-0">
              <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
              <span class="block font-bold text-white mb-0.5">Laboratory Address</span>
              <p class="text-xs text-gray-500 leading-normal" data-cms-key="contact_address"><?php echo htmlspecialchars($contact_address); ?></p>
            </div>
          </div>

          <!-- Phone -->
          <div class="flex items-start gap-4 p-5 bg-brandDarkGray border border-white/5 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen shrink-0">
              <i class="fa-solid fa-phone"></i>
            </div>
            <div>
              <span class="block font-bold text-white mb-0.5">Phone Number</span>
              <p class="text-xs text-gray-500" data-cms-key="contact_phone"><?php echo htmlspecialchars($contact_phone); ?></p>
            </div>
          </div>

          <!-- Email -->
          <div class="flex items-start gap-4 p-5 bg-brandDarkGray border border-white/5 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen shrink-0">
              <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
              <span class="block font-bold text-white mb-0.5">Email Address</span>
              <p class="text-xs text-gray-500" data-cms-key="contact_email"><?php echo htmlspecialchars($contact_email); ?></p>
            </div>
          </div>

          <!-- Working Hours -->
          <div class="flex items-start gap-4 p-5 bg-brandDarkGray border border-white/5 rounded-2xl">
            <div class="w-10 h-10 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen shrink-0">
              <i class="fa-solid fa-clock"></i>
            </div>
            <div>
              <span class="block font-bold text-white mb-0.5">Lab Working Hours</span>
              <p class="text-xs text-gray-500" data-cms-key="contact_hours"><?php echo htmlspecialchars($working_hours); ?></p>
            </div>
          </div>
        </div>

        <div class="aspect-video w-full rounded-[28px] overflow-hidden bg-gray-900 border border-white/5 shadow-lg relative">
          <iframe src="<?php echo htmlspecialchars($map_url); ?>" class="w-full h-full border-0 grayscale invert opacity-70" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
      </div>

      <!-- RIGHT COLUMN: Contact Form -->
      <div class="lg:col-span-7">
        <div class="glass border border-white/10 p-8 sm:p-10 rounded-[36px] space-y-6">
          <div class="text-left space-y-2">
            <h3 class="text-xl font-bold font-['Outfit'] text-white">Send a Message</h3>
            <p class="text-xs text-gray-500">Fill out this form and a volunteer will respond in 24 hours.</p>
          </div>
          
          <form id="contactForm" class="space-y-4 text-sm text-left">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-400 font-semibold mb-1">Full Name</label>
                <input type="text" name="name" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
              </div>
              <div>
                <label class="block text-gray-400 font-semibold mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-400 font-semibold mb-1">Mobile (Optional)</label>
                <input type="text" name="mobile" class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
              </div>
              <div>
                <label class="block text-gray-400 font-semibold mb-1">Subject</label>
                <input type="text" name="subject" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
              </div>
            </div>
            <div>
              <label class="block text-gray-400 font-semibold mb-1">Message Details</label>
              <textarea name="message" rows="5" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="Describe your query in detail..."></textarea>
            </div>
            <button type="submit" class="w-full text-center py-4 bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/15 transition-all">
              Submit Contact Form
            </button>
          </form>
        </div>
      </div>

    </div>

    <!-- FAQ Section -->
    <div class="space-y-12">
      <div class="text-center max-w-xl mx-auto space-y-4">
        <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">FAQ ACCORDION</span>
        <h2 class="text-3xl font-bold font-['Outfit']">Frequently Asked Questions</h2>
      </div>

      <div class="max-w-3xl mx-auto space-y-4">
        <!-- FAQ 1 -->
        <div class="glass border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 faq-item">
          <button onclick="toggleFaq(this)" class="w-full px-6 py-5 flex justify-between items-center text-left hover:bg-white/5 transition-colors focus:outline-none">
            <span class="font-bold text-sm sm:text-base text-white">Who can access Yuvalay MakerSpace?</span>
            <i class="fa-solid fa-chevron-down text-gray-500 text-xs transition-transform duration-300"></i>
          </button>
          <div class="px-6 pb-5 pt-1 text-xs sm:text-sm text-gray-500 leading-relaxed border-t border-white/5 hidden faq-answer text-left">
            Anyone! Our space is open to engineering students, hardware builders, software developers, researchers, and local hobbyists. You just need to register online to become a member and pass basic safety protocols before booking tools.
          </div>
        </div>

        <!-- FAQ 2 -->
        <div class="glass border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 faq-item">
          <button onclick="toggleFaq(this)" class="w-full px-6 py-5 flex justify-between items-center text-left hover:bg-white/5 transition-colors focus:outline-none">
            <span class="font-bold text-sm sm:text-base text-white">Is there an access or membership fee?</span>
            <i class="fa-solid fa-chevron-down text-gray-500 text-xs transition-transform duration-300"></i>
          </button>
          <div class="px-6 pb-5 pt-1 text-xs sm:text-sm text-gray-500 leading-relaxed border-t border-white/5 hidden faq-answer text-left">
            No, our basic community membership is free. You have full access to our electronics workspace, computers, and mentoring. Additive printing filaments and specialized PCB printing runs are provided at raw material costs.
          </div>
        </div>

        <!-- FAQ 3 -->
        <div class="glass border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 faq-item">
          <button onclick="toggleFaq(this)" class="w-full px-6 py-5 flex justify-between items-center text-left hover:bg-white/5 transition-colors focus:outline-none">
            <span class="font-bold text-sm sm:text-base text-white">Can I book a machine beforehand?</span>
            <i class="fa-solid fa-chevron-down text-gray-500 text-xs transition-transform duration-300"></i>
          </button>
          <div class="px-6 pb-5 pt-1 text-xs sm:text-sm text-gray-500 leading-relaxed border-t border-white/5 hidden faq-answer text-left">
            Yes. Once you have logged in as a registered member and completed the corresponding equipment safety manual validation, you can book standard slots for FDM printers or CNC lasers in the Admin Desk.
          </div>
        </div>

        <!-- FAQ 4 -->
        <div class="glass border border-white/5 rounded-2xl overflow-hidden transition-all duration-300 faq-item">
          <button onclick="toggleFaq(this)" class="w-full px-6 py-5 flex justify-between items-center text-left hover:bg-white/5 transition-colors focus:outline-none">
            <span class="font-bold text-sm sm:text-base text-white">How do I obtain training certifications?</span>
            <i class="fa-solid fa-chevron-down text-gray-500 text-xs transition-transform duration-300"></i>
          </button>
          <div class="px-6 pb-5 pt-1 text-xs sm:text-sm text-gray-500 leading-relaxed border-t border-white/5 hidden faq-answer text-left">
            You can sign up for our upcoming certification workshops in our Events Portal. E-learning materials are uploaded inside our Resources tab. Once you complete the practical test successfully, you can download your verified PDF certificate.
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Accordion FAQ script -->
<script>
    function toggleFaq(btn) {
        const item = btn.parentElement;
        const answer = item.querySelector(".faq-answer");
        const icon = btn.querySelector("i");

        const isOpen = !answer.classList.contains("hidden");

        // Close all first for clean accordion single flow
        document.querySelectorAll(".faq-answer").forEach(ans => ans.classList.add("hidden"));
        document.querySelectorAll(".faq-item").forEach(i => i.classList.remove("border-[#8DC63F]/30"));
        document.querySelectorAll(".faq-item i").forEach(ic => ic.style.transform = "rotate(0deg)");

        if (!isOpen) {
            answer.classList.remove("hidden");
            item.classList.add("border-[#8DC63F]/30");
            icon.style.transform = "rotate(180deg)";
        }
    }

    // Submit Contact Handler
    const contactForm = document.getElementById("contactForm");
    if (contactForm) {
        contactForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(contactForm);

            fetch("/api.php?action=submit-contact", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Your message has been sent successfully!");
                    contactForm.reset();
                } else {
                    alert("Submission failed: " + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert("Failed to submit message.");
            });
        });
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
