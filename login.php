<?php
/**
 * User Login Page Template
 */
require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if ($is_logged_in) {
    header("Location: /my-registrations.php");
    exit;
}

$redirect = $_GET['redirect'] ?? '/my-registrations.php';
?>

<!-- Login Section -->
<section class="min-h-[calc(100vh-80px)] flex items-center bg-[#090909] py-16 relative overflow-hidden">
  <!-- Glowing meshes -->
  <div class="absolute top-1/4 left-1/4 w-72 h-72 rounded-full bg-brandGreen/5 filter blur-[90px] pointer-events-none"></div>
  
  <div class="max-w-md w-full mx-auto px-4 relative z-10">
    <div class="glass border border-white/10 p-8 sm:p-10 rounded-[36px] space-y-6 shadow-2xl relative overflow-hidden">
      <!-- Glow -->
      <div class="absolute -right-16 -top-16 w-32 h-32 rounded-full bg-brandGreen/5 pointer-events-none"></div>
      
      <div class="text-center space-y-2">
        <div class="w-12 h-12 rounded-xl bg-brandGreen/10 border border-brandGreen/20 flex items-center justify-center mx-auto text-brandGreen">
          <i class="fa-solid fa-lock-open text-xl"></i>
        </div>
        <h1 class="text-2xl font-extrabold text-white font-['Outfit']">Member Login</h1>
        <p class="text-xs text-gray-500">Access your dashboard, tickets and certificates.</p>
      </div>

      <!-- Error Toast alert (local inside form) -->
      <div id="loginError" class="hidden p-3.5 bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-semibold rounded-xl text-left">
        <!-- Injected -->
      </div>

      <!-- Form -->
      <form id="loginForm" class="space-y-4 text-sm text-left">
        <input type="hidden" name="redirect" id="redirectUrl" value="<?php echo htmlspecialchars($redirect); ?>">
        
        <div>
          <label class="block text-gray-400 font-semibold mb-1">Email Address</label>
          <div class="relative">
            <i class="fa-regular fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="email" name="email" required placeholder="name@email.com" class="w-full bg-brandBlack border border-white/10 rounded-xl pl-11 pr-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
          </div>
        </div>

        <div>
          <div class="flex justify-between items-center mb-1">
            <label class="block text-gray-400 font-semibold">Password</label>
            <a href="#" class="text-xs text-brandGreen hover:underline">Forgot Password?</a>
          </div>
          <div class="relative">
            <i class="fa-solid fa-key absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
            <input type="password" name="password" required placeholder="••••••••" class="w-full bg-brandBlack border border-white/10 rounded-xl pl-11 pr-4 py-3.5 text-white focus:outline-none focus:border-brandGreen text-sm">
          </div>
        </div>

        <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="remember" class="accent-brandGreen"> Remember Me
          </label>
        </div>

        <button type="submit" class="w-full text-center py-4 bg-[#8DC63F] hover:bg-[#73a11c] text-brandBlack font-extrabold text-xs rounded-xl shadow-lg shadow-brandGreen/15 transition-all">
          Sign In
        </button>
      </form>

      <!-- Google login mock -->
      <div class="space-y-4">
        <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-white/5"></div>
            <span class="flex-shrink mx-4 text-[10px] text-gray-600 font-bold uppercase tracking-widest">or login with</span>
            <div class="flex-grow border-t border-white/5"></div>
        </div>

        <button onclick="mockGoogleLogin()" class="w-full text-center py-3 bg-brandBlack hover:bg-white/5 border border-white/10 rounded-xl text-gray-300 hover:text-white font-semibold text-xs transition-all flex items-center justify-center gap-2">
          <i class="fa-brands fa-google text-sm"></i> Google Authentication
        </button>
      </div>

      <div class="text-center text-xs text-gray-500 pt-2 border-t border-white/5">
        Don't have an account? <a href="/register.php" class="text-brandGreen hover:underline font-bold">Register / Join Us</a>
      </div>

    </div>
  </div>
</section>

<!-- Handler -->
<script>
    const form = document.getElementById("loginForm");
    if (form) {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const formData = new FormData(form);
            const errDiv = document.getElementById("loginError");

            fetch("/api.php?action=login", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    errDiv.classList.add("hidden");
                    const redirectUrl = document.getElementById("redirectUrl").value;
                    window.location.href = redirectUrl;
                } else {
                    errDiv.innerText = data.message;
                    errDiv.classList.remove("hidden");
                    if (data.not_verified) {
                        setTimeout(() => {
                            window.location.href = "/verify-email.php";
                        }, 2000);
                    }
                }
            })
            .catch(err => {
                console.error(err);
                errDiv.innerText = "Network connection failed. Please check local database port.";
                errDiv.classList.remove("hidden");
            });
        });
    }

    function mockGoogleLogin() {
        alert("Google Oauth login flows would configure client redirects here in production.");
    }
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
