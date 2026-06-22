<?php
/**
 * User Registration Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if ($is_logged_in) {
    header("Location: /my-registrations.php");
    exit;
}

$selected_role = $_GET['role'] ?? 'member';
if (!in_array($selected_role, ['member', 'volunteer', 'mentor'])) {
    $selected_role = 'member';
}

$recaptcha_site_key = getSetting('recaptcha_site_key', '');
if (!empty($recaptcha_site_key)): ?>
  <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($recaptcha_site_key); ?>"></script>
<?php endif; ?>
?>

<!-- Register Section -->
<section class="min-h-[calc(100vh-80px)] flex items-center bg-[#090909] py-16 relative overflow-hidden">
  <!-- Decorative glowing meshes -->
  <div class="absolute bottom-1/4 right-1/4 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[90px] pointer-events-none"></div>
  
  <div class="max-w-md w-full mx-auto px-4 relative z-10">
    <div class="glass border border-white/10 p-8 sm:p-10 rounded-[36px] space-y-6 shadow-2xl relative overflow-hidden">
      <!-- Glow -->
      <div class="absolute -left-16 -bottom-16 w-32 h-32 rounded-full bg-brandGreen/5 pointer-events-none"></div>

      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center mx-auto text-brandGreen">
          <i class="fa-solid fa-user-plus text-xl"></i>
        </div>
        <h1 class="text-2xl font-extrabold text-white font-['Outfit']">Join MakerSpace</h1>
        <p class="text-xs text-gray-500">Sign up to access labs, events and certifications.</p>
      </div>

      <!-- Local Form errors -->
      <div id="registerError" class="hidden p-3.5 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold rounded-xl text-left">
        <!-- Injected -->
      </div>
      
      <!-- Local Form success -->
      <div id="registerSuccess" class="hidden p-3.5 bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-xs font-semibold rounded-xl text-left">
        <!-- Injected -->
      </div>

      <!-- Form -->
      <form id="registerForm" class="space-y-4 text-sm text-left">
        
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Full Name</label>
          <input type="text" name="name" required placeholder="John Doe" class="w-full bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
        </div>

        <div>
          <label class="block text-gray-400 font-semibold mb-1">Email Address</label>
          <input type="email" name="email" required placeholder="john@email.com" class="w-full bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
        </div>

        <div>
          <label class="block text-gray-400 font-semibold mb-1">Mobile Number</label>
          <input type="text" name="mobile" required placeholder="+91 99999 88888" class="w-full bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Password</label>
            <input type="password" id="regPassword" name="password" required placeholder="••••••••" class="w-full bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
          </div>
          <div>
            <label class="block text-gray-400 font-semibold mb-1">Confirm Password</label>
            <input type="password" id="regConfirmPassword" name="confirm_password" required placeholder="••••••••" class="w-full bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
          </div>
        </div>

        <!-- Password Strength Meter Layout -->
        <div class="space-y-1.5 pt-1">
          <div class="flex justify-between items-center text-[10px]">
            <span class="text-gray-500 font-bold uppercase tracking-wider">Password Strength:</span>
            <span id="strengthText" class="text-gray-500 font-bold uppercase tracking-wider">Empty</span>
          </div>
          <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden flex gap-1">
            <div id="meterBar1" class="h-full flex-grow rounded-full transition-colors bg-white/10"></div>
            <div id="meterBar2" class="h-full flex-grow rounded-full transition-colors bg-white/10"></div>
            <div id="meterBar3" class="h-full flex-grow rounded-full transition-colors bg-white/10"></div>
          </div>
        </div>

        <!-- Role Selection -->
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Join Space As</label>
          <select name="role" required class="w-full bg-brandBlack border border-white/10 rounded-xl p-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
            <option value="member" <?php echo $selected_role === 'member' ? 'selected' : ''; ?>>Community Member (Free Access)</option>
            <option value="volunteer" <?php echo $selected_role === 'volunteer' ? 'selected' : ''; ?>>Space Volunteer</option>
            <option value="mentor" <?php echo $selected_role === 'mentor' ? 'selected' : ''; ?>>Technical Mentor</option>
          </select>
        </div>

        <button type="submit" class="w-full text-center py-4 bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/15 transition-all">
          Create Account
        </button>
      </form>

      <div class="text-center text-xs text-gray-500 pt-2 border-t border-white/5">
        Already have an account? <a href="/login.php" class="text-brandGreen hover:underline font-bold">Login Here</a>
      </div>

    </div>
  </div>
</section>

<!-- Handler -->
<script>
    const passwordInput = document.getElementById("regPassword");
    const confirmInput = document.getElementById("regConfirmPassword");
    
    const strengthText = document.getElementById("strengthText");
    const bar1 = document.getElementById("meterBar1");
    const bar2 = document.getElementById("meterBar2");
    const bar3 = document.getElementById("meterBar3");

    passwordInput.addEventListener("input", checkPasswordStrength);

    function checkPasswordStrength() {
        const val = passwordInput.value;
        let score = 0;

        if (val.length === 0) {
            strengthText.innerText = "Empty";
            strengthText.className = "text-gray-500 font-bold uppercase tracking-wider";
            bar1.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
            bar2.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
            bar3.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
            return;
        }

        // Checklist checks
        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

        // Update indicators
        if (score === 1) {
            strengthText.innerText = "Weak";
            strengthText.className = "text-red-400 font-bold uppercase tracking-wider";
            bar1.className = "h-full flex-grow rounded-full transition-colors bg-red-500";
            bar2.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
            bar3.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
        } else if (score === 2) {
            strengthText.innerText = "Moderate";
            strengthText.className = "text-yellow-400 font-bold uppercase tracking-wider";
            bar1.className = "h-full flex-grow rounded-full transition-colors bg-yellow-500";
            bar2.className = "h-full flex-grow rounded-full transition-colors bg-yellow-500";
            bar3.className = "h-full flex-grow rounded-full transition-colors bg-white/10";
        } else if (score === 3) {
            strengthText.innerText = "Strong";
            strengthText.className = "text-brandGreen font-bold uppercase tracking-wider";
            bar1.className = "h-full flex-grow rounded-full transition-colors bg-[#8DC63F]";
            bar2.className = "h-full flex-grow rounded-full transition-colors bg-[#8DC63F]";
            bar3.className = "h-full flex-grow rounded-full transition-colors bg-[#8DC63F]";
        }
    }

    // Submit handler
    const form = document.getElementById("registerForm");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const errDiv = document.getElementById("registerError");
            const succDiv = document.getElementById("registerSuccess");

            // Password check match
            if (passwordInput.value !== confirmInput.value) {
                errDiv.innerText = "Passwords do not match!";
                errDiv.classList.remove("hidden");
                return;
            }

            const executeRegister = (recaptchaToken = '') => {
                const formData = new FormData(form);
                if (recaptchaToken) {
                    formData.append('recaptcha_token', recaptchaToken);
                }

                fetch("/api.php?action=register", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        errDiv.classList.add("hidden");
                        
                        // Display OTP if simulated for developer preview
                        let msg = data.message;
                        if (data.otp_fallback) {
                            msg += ` (Simulated OTP: ${data.otp_fallback})`;
                        }
                        succDiv.innerText = msg;
                        succDiv.classList.remove("hidden");
                        form.reset();
                        checkPasswordStrength();
                        
                        // Redirect to verify-email.php after 1.5 seconds
                        setTimeout(() => {
                            window.location.href = "/verify-email.php";
                        }, 2500);
                    } else {
                        succDiv.classList.add("hidden");
                        errDiv.innerText = data.message;
                        errDiv.classList.remove("hidden");
                    }
                })
                .catch(err => {
                    console.error(err);
                    errDiv.innerText = "Connection failed. Please check local database port.";
                    errDiv.classList.remove("hidden");
                });
            };

            <?php if (!empty($recaptcha_site_key)): ?>
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.ready(function() {
                    grecaptcha.execute('<?php echo htmlspecialchars($recaptcha_site_key); ?>', {action: 'register'}).then(function(token) {
                        executeRegister(token);
                    });
                });
            } else {
                executeRegister();
            }
            <?php else: ?>
            executeRegister();
            <?php endif; ?>
        });
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
