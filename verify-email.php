<?php
/**
 * User Email OTP Verification Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in or if verification session is missing
if ($is_logged_in) {
    header("Location: /my-registrations.php");
    exit;
}
$email = $_SESSION['verification_email'] ?? '';
if (empty($email)) {
    header("Location: /register.php");
    exit;
}

// Calculate remaining seconds left on the current OTP
$seconds_left = 300; // 5 minutes
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT expires_at FROM email_verifications WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $record = $stmt->fetch();
        if ($record) {
            $expires = strtotime($record['expires_at']);
            $seconds_left = max(0, $expires - time());
        }
    } catch (Exception $e) {}
}
?>

<!-- OTP Verification Section -->
<section class="min-h-[calc(100vh-80px)] flex items-center bg-[#090909] py-16 relative overflow-hidden">
  <!-- Decorative background mesh -->
  <div class="absolute top-1/4 left-1/4 w-80 h-80 rounded-full bg-brandGreen/5 filter blur-[100px] pointer-events-none"></div>

  <div class="max-w-md w-full mx-auto px-4 relative z-10">
    <div class="glass border border-white/10 p-8 sm:p-10 rounded-[36px] space-y-6 shadow-2xl relative overflow-hidden">
      <!-- Glow -->
      <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-brandGreen/5 pointer-events-none"></div>

      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center mx-auto text-brandGreen">
          <i class="fa-solid fa-envelope-circle-check text-xl"></i>
        </div>
        <h1 class="text-2xl font-extrabold text-white font-['Outfit']">Verify Your Email</h1>
        <p class="text-xs text-gray-500">We sent a 6-digit verification code to:</p>
        <span class="block text-sm font-semibold text-brandGreen font-mono truncate"><?php echo htmlspecialchars($email); ?></span>
      </div>

      <!-- Alert notifications -->
      <div id="verifyError" class="hidden p-3.5 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold rounded-xl text-left"></div>
      <div id="verifySuccess" class="hidden p-3.5 bg-brandGreen/10 border border-brandGreen/20 text-brandGreen text-xs font-semibold rounded-xl text-left"></div>

      <!-- OTP Form -->
      <form id="otpVerifyForm" class="space-y-6 text-sm text-left">
        <div>
          <label class="block text-gray-400 font-semibold mb-2 text-center">Verification Code</label>
          <div class="flex justify-center">
            <input type="text" name="otp" maxlength="6" required placeholder="Enter 6-Digit OTP" 
                   class="w-full max-w-[240px] tracking-[6px] text-center font-mono font-extrabold text-xl bg-brandBlack border border-white/10 rounded-xl px-4 py-3.5 text-white focus:outline-none focus:border-brandGreen placeholder:tracking-normal placeholder:font-sans placeholder:text-sm">
          </div>
        </div>

        <button type="submit" id="verifySubmitBtn" class="w-full text-center py-4 bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/15 transition-all flex items-center justify-center gap-2">
          <span id="verifyBtnText">Verify Email</span>
        </button>
      </form>

      <!-- Status and Action panel -->
      <div class="flex flex-col items-center justify-center gap-3 pt-2 text-xs border-t border-white/5">
        <!-- Countdown timer -->
        <div id="timerContainer" class="text-gray-400 font-semibold flex items-center gap-1.5">
          <i class="fa-regular fa-clock text-brandGreen"></i> Code expires in <span id="countdownText" class="font-bold text-white">05:00</span>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-gray-500">Didn't receive the email?</span>
          <button id="resendOtpBtn" onclick="resendVerificationOtp()" class="text-brandGreen hover:underline font-bold disabled:opacity-50 disabled:no-underline disabled:cursor-not-allowed">
            Resend Code
          </button>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Handler Script -->
<script>
    let timeLeft = <?php echo intval($seconds_left); ?>;
    const countdownText = document.getElementById("countdownText");
    const resendBtn = document.getElementById("resendOtpBtn");
    const timerContainer = document.getElementById("timerContainer");
    const verifySubmitBtn = document.getElementById("verifySubmitBtn");
    const verifyBtnText = document.getElementById("verifyBtnText");
    let timerInterval;

    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    function startTimer() {
        clearInterval(timerInterval);
        if (timeLeft <= 0) {
            handleTimerExpiry();
            return;
        }

        verifySubmitBtn.disabled = false;
        timerContainer.className = "text-gray-400 font-semibold flex items-center gap-1.5";
        countdownText.innerText = formatTime(timeLeft);

        timerInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                handleTimerExpiry();
            } else {
                countdownText.innerText = formatTime(timeLeft);
            }
        }, 1000);
    }

    function handleTimerExpiry() {
        countdownText.innerText = "Expired";
        timerContainer.className = "text-red-400 font-semibold flex items-center gap-1.5";
        verifySubmitBtn.disabled = true;
        
        const errDiv = document.getElementById("verifyError");
        errDiv.innerText = "Verification code expired. Please request a new OTP.";
        errDiv.classList.remove("hidden");
    }

    // Submit handler
    const verifyForm = document.getElementById("otpVerifyForm");
    if (verifyForm) {
        verifyForm.addEventListener("submit", (e) => {
            e.preventDefault();
            const errDiv = document.getElementById("verifyError");
            const succDiv = document.getElementById("verifySuccess");
            
            errDiv.classList.add("hidden");
            succDiv.classList.add("hidden");
            
            // Loading spinner state
            verifySubmitBtn.disabled = true;
            verifyBtnText.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> Verifying...';

            const formData = new FormData(verifyForm);

            fetch("/api.php?action=verify-otp", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                verifySubmitBtn.disabled = false;
                verifyBtnText.innerText = "Verify Email";
                
                if (data.status === "success") {
                    succDiv.innerText = data.message;
                    succDiv.classList.remove("hidden");
                    verifyForm.reset();
                    clearInterval(timerInterval);

                    setTimeout(() => {
                        window.location.href = data.redirect || "/my-registrations.php";
                    }, 1500);
                } else {
                    errDiv.innerText = data.message;
                    errDiv.classList.remove("hidden");
                }
            })
            .catch(err => {
                console.error(err);
                verifySubmitBtn.disabled = false;
                verifyBtnText.innerText = "Verify Email";
                errDiv.innerText = "Network connection error. Please try again.";
                errDiv.classList.remove("hidden");
            });
        });
    }

    // Resend OTP handler
    function resendVerificationOtp() {
        const errDiv = document.getElementById("verifyError");
        const succDiv = document.getElementById("verifySuccess");
        
        errDiv.classList.add("hidden");
        succDiv.classList.add("hidden");
        
        const originalBtnText = resendBtn.innerHTML;
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin mr-1"></i> Sending...';

        fetch("/api.php?action=resend-otp", {
            method: "POST"
        })
        .then(res => res.json())
        .then(data => {
            resendBtn.disabled = false;
            resendBtn.innerHTML = originalBtnText;
            
            if (data.status === "success") {
                let msg = data.message;
                if (data.otp_fallback) {
                    msg += ` (Simulated OTP: ${data.otp_fallback})`;
                }
                succDiv.innerText = msg;
                succDiv.classList.remove("hidden");
                
                // Reset timer back to 5 minutes
                timeLeft = 300;
                startTimer();
            } else {
                errDiv.innerText = data.message;
                errDiv.classList.remove("hidden");
            }
        })
        .catch(err => {
            console.error(err);
            resendBtn.disabled = false;
            resendBtn.innerHTML = originalBtnText;
            errDiv.innerText = "Network connection error. Please try again.";
            errDiv.classList.remove("hidden");
        });
    }

    // Init timer
    startTimer();
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
