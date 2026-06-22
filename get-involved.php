<?php
/**
 * Get Involved Page Template
 */
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="relative bg-[#090909] py-20 border-b border-white/5 overflow-hidden">
  <div class="absolute top-0 right-0 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[80px]"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-4 relative z-10">
    <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">JOIN THE MOVEMENT</span>
    <h1 class="text-4xl sm:text-5xl font-extrabold font-['Outfit'] tracking-tight">Onboard & Collaborate</h1>
    <p class="text-gray-400 text-sm sm:text-base max-w-xl mx-auto">
      Help us shape Vadodara's maker ecosystem. Share your knowledge as a mentor, assist in labs as a volunteer, or support us as a partner.
    </p>
  </div>
</section>

<!-- Cards for Roles -->
<section class="py-24 bg-brandBlack">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-24">
    
    <!-- Onboarding grid options -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      
      <!-- Mentor Card -->
      <div class="glass p-8 sm:p-12 rounded-[36px] border border-white/5 hover:border-brandGreen/25 transition-all flex flex-col justify-between text-left relative overflow-hidden group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen group-hover:scale-105 transition-transform duration-300">
            <i class="fa-solid fa-graduation-cap text-xl"></i>
          </div>
          <div class="space-y-3">
            <h2 class="text-2xl font-bold text-white font-['Outfit']">Join as a Mentor</h2>
            <p class="text-gray-400 text-sm sm:text-base leading-relaxed">
              Are you an embedded software architect, a mechanical tooling expert, or a hardware startup entrepreneur? Guide young builders through multi-layer PCB design reviews, sensor logic coding, and prototyping iterations.
            </p>
            <ul class="space-y-2 text-xs text-gray-500 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Commit 2-4 hours a week or join monthly meetups</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Conduct specialized lab certifications</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Help builders refine functional models</li>
            </ul>
          </div>
        </div>
        <a href="/register.php?role=mentor" class="w-full text-center py-4 bg-brandGreen hover:bg-brandDarkGreen text-brandBlack font-extrabold text-sm rounded-xl shadow-lg shadow-brandGreen/10 transition-all mt-8 block">
          Onboard as Mentor
        </a>
      </div>

      <!-- Volunteer Card -->
      <div class="glass p-8 sm:p-12 rounded-[36px] border border-white/5 hover:border-brandGreen/25 transition-all flex flex-col justify-between text-left relative overflow-hidden group">
        <div class="space-y-6">
          <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center text-brandGreen group-hover:scale-105 transition-transform duration-300">
            <i class="fa-solid fa-handshake text-xl"></i>
          </div>
          <div class="space-y-3">
            <h2 class="text-2xl font-bold text-white font-['Outfit']">Join as a Volunteer</h2>
            <p class="text-gray-400 text-sm sm:text-base leading-relaxed">
              Want to support daily laboratory operations, organize hackathons, or audit tool safety checkouts? Gain hands-on exposure to advanced manufacturing machinery while giving back to Vadodara's technology community.
            </p>
            <ul class="space-y-2 text-xs text-gray-500 pt-2">
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Assist in inventory logging & machine monitoring</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Coordinate registration desks during hackathons</li>
              <li class="flex items-center gap-2"><i class="fa-solid fa-angle-right text-brandGreen"></i> Join our active Maker Council circles</li>
            </ul>
          </div>
        </div>
        <a href="/register.php?role=volunteer" class="w-full text-center py-4 bg-brandDarkGray hover:bg-white/5 border border-white/10 text-white font-extrabold text-sm rounded-xl transition-all mt-8 block">
          Onboard as Volunteer
        </a>
      </div>

    </div>

    <!-- Sponsor / Partner Form Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center bg-[#0c0c0c] p-8 sm:p-16 rounded-[40px] border border-white/5 relative overflow-hidden">
      <!-- Background glow -->
      <div class="absolute -right-1/4 -bottom-1/4 w-96 h-96 rounded-full bg-brandGreen/5 filter blur-[100px] pointer-events-none"></div>
      
      <!-- Descriptions -->
      <div class="lg:col-span-6 space-y-6 text-left">
        <span class="text-xs font-bold tracking-widest text-[#8DC63F] uppercase">SUPPORT OUR LABORATORY</span>
        <h2 class="text-3xl sm:text-4xl font-extrabold font-['Outfit']">Sponsorships & Partnership Enquiries</h2>
        <p class="text-gray-400 text-sm sm:text-base leading-relaxed">
          Provide financial grants, donate laboratory testing gear, or partner as an academic partner. Help us keep makerspace access free and affordable for deserving students in Gujarat.
        </p>
        <div class="flex items-center gap-4 text-xs text-gray-500">
          <span><i class="fa-solid fa-envelope text-brandGreen mr-1.5"></i> partners@yuvalay.org</span>
          <span><i class="fa-solid fa-phone text-brandGreen mr-1.5"></i> +91 98765 43210</span>
        </div>
      </div>

      <!-- Form -->
      <div class="lg:col-span-6">
        <form id="partnerForm" class="glass border border-white/10 p-6 sm:p-8 rounded-3xl space-y-4 text-sm text-left">
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Company / Institution Name</label>
            <input type="text" name="name" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Official Email Address</label>
            <input type="email" name="email" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Enquiry Subject</label>
            <input type="text" name="subject" value="Sponsorship & Partnership Query" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Enquiry Details & Message</label>
            <textarea name="message" rows="3" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3 text-white focus:outline-none focus:border-brandGreen" placeholder="How would you like to partner or support our space?"></textarea>
          </div>
          <button type="submit" class="w-full text-center py-3.5 bg-brandGreen hover:bg-brandDarkGreen text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/10 transition-all">
            Submit Partnership Enquiry
          </button>
        </form>
      </div>

    </div>

  </div>
</section>

<!-- Submit handler -->
<script>
    const form = document.getElementById("partnerForm");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(form);

            fetch("/api.php?action=submit-contact", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    alert("Thank you! Your partnership enquiry has been submitted. Our leads will get back to you shortly.");
                    form.reset();
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
