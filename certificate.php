<?php
/**
 * Completion Certificate Generation Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

$reg_id = trim($_GET['reg_id'] ?? '');

if (empty($reg_id)) {
    die("Error: Registration ID is required.");
}

$dbObj = new Database();
$conn = $dbObj->getConnection();

$registration = null;

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT r.*, e.title as event_title, e.event_date, u.name as user_name, u.email as user_email 
                                FROM event_registrations r 
                                JOIN events e ON r.event_id = e.id 
                                JOIN users u ON r.user_id = u.id 
                                WHERE r.registration_id = :reg");
        $stmt->execute(['reg' => $reg_id]);
        $registration = $stmt->fetch();
    } catch (PDOException $e) {
        die("Database Query Error: " . $e->getMessage());
    }
}

if (!$registration) {
    die("Error: Registration not found.");
}

// Security Check: Must be the owner of the ticket or admin
$is_owner = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $registration['user_id']);
$is_admin = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin');

if (!$is_owner && !$is_admin) {
    die("Access Denied. You do not have permission to view this certificate.");
}

// Attendance Verification: Only generate if checked in as present
if ($registration['attendance_status'] !== 'present' && $registration['status'] !== 'Attended') {
    die("Error: Certificate not available. Attendance must be marked as 'Present' for this event.");
}

// Capture HTML layout
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Certificate - <?php echo htmlspecialchars($registration['user_name']); ?></title>
  <style>
    body {
      background: #FFFFFF;
      color: #111111;
      font-family: 'Georgia', serif;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .cert-container {
      width: 800px;
      height: 560px;
      border: 15px double #8DC63F;
      padding: 30px;
      text-align: center;
      background: #FAFAFA;
      box-sizing: border-box;
      position: relative;
    }
    .cert-inner {
      border: 2px solid #6DA52A;
      height: 100%;
      padding: 20px;
      box-sizing: border-box;
    }
    .cert-header {
      font-size: 14px;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      font-weight: 700;
      color: #6DA52A;
      letter-spacing: 4px;
      text-transform: uppercase;
      margin-top: 15px;
      margin-bottom: 25px;
    }
    .cert-title {
      font-size: 38px;
      font-weight: 800;
      margin: 0 0 10px 0;
      font-family: 'Georgia', serif;
      color: #111111;
    }
    .cert-present {
      font-size: 14px;
      font-style: italic;
      color: #6B7280;
      margin-bottom: 20px;
    }
    .cert-name {
      font-size: 28px;
      font-weight: 700;
      color: #8DC63F;
      border-bottom: 2px solid #E5E7EB;
      display: inline-block;
      padding-bottom: 5px;
      margin-bottom: 20px;
      min-width: 300px;
    }
    .cert-text {
      font-size: 14px;
      line-height: 1.6;
      color: #4B5563;
      max-width: 580px;
      margin: 0 auto 35px auto;
    }
    .cert-event {
      font-weight: 700;
      color: #111111;
    }
    .cert-signatures {
      display: table;
      width: 100%;
      margin-top: 20px;
    }
    .sig-block {
      display: table-cell;
      width: 50%;
      text-align: center;
      vertical-align: bottom;
    }
    .sig-line {
      width: 180px;
      border-top: 1px solid #9CA3AF;
      margin: 0 auto 5px auto;
    }
    .sig-title {
      font-size: 11px;
      font-family: 'Helvetica Neue', Arial, sans-serif;
      color: #6B7280;
      text-transform: uppercase;
      font-weight: 600;
    }
    .print-btn-container {
      position: absolute;
      bottom: -60px;
      left: 0;
      right: 0;
      text-align: center;
    }
    .btn {
      background: #8DC63F;
      color: #111111;
      border: 0;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 700;
      cursor: pointer;
      font-size: 12px;
      text-decoration: none;
      display: inline-block;
      font-family: 'Helvetica Neue', Arial, sans-serif;
    }
    .btn-secondary {
      background: #1A1A1A;
      color: #FFFFFF;
      margin-left: 10px;
    }
    @media print {
      body {
        background: #FFFFFF;
        height: auto;
      }
      .cert-container {
        border: 15px double #8DC63F;
        box-shadow: none;
        margin: 0;
        page-break-inside: avoid;
      }
      .print-btn-container {
        display: none;
      }
    }
  </style>
</head>
<body>

  <div class="cert-container">
    <div class="cert-inner">
      
      <div class="cert-header">Certificate of Achievement</div>
      <h1 class="cert-title">Yuvalay MakerSpace</h1>
      
      <p class="cert-present">This is proudly presented to</p>
      
      <div class="cert-name"><?php echo htmlspecialchars($registration['user_name']); ?></div>
      
      <p class="cert-text">
        for successfully completing all safety accreditations, practical guidelines, and hands-on modules required for the workshop:<br>
        <strong class="cert-event">"<?php echo htmlspecialchars($registration['event_title']); ?>"</strong><br>
        held at the Yuvalay MakerSpace facility in Vadodara, Gujarat on <strong class="cert-event"><?php echo date('M d, Y', strtotime($registration['event_date'])); ?></strong>.
      </p>

      <div class="cert-signatures">
        
        <!-- Sig 1 -->
        <div class="sig-block">
          <div style="font-family: 'Courier New', monospace; font-size:14px; font-style:italic; margin-bottom:5px;">R. Patel</div>
          <div class="sig-line"></div>
          <span class="sig-title">Lead Lab Manager</span>
        </div>

        <!-- Sig 2 -->
        <div class="sig-block">
          <div style="font-family: 'Courier New', monospace; font-size:14px; font-style:italic; margin-bottom:5px;">N. Vyas</div>
          <div class="sig-line"></div>
          <span class="sig-title">Senior Mentor</span>
        </div>

      </div>

    </div>

    <!-- Print triggers -->
    <div class="print-btn-container">
      <button onclick="window.print()" class="btn">Print Certificate</button>
      <a href="?reg_id=<?php echo $registration['registration_id']; ?>&pdf=true" class="btn btn-secondary">Download PDF</a>
    </div>

  </div>

</body>
</html>
<?php
$html_content = ob_get_clean();

// Check if PDF stream download is requested
if (isset($_GET['pdf']) && $_GET['pdf'] === 'true') {
    $autoload_file = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload_file)) {
        require_once $autoload_file;
        
        try {
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml($html_content);
            $dompdf->setPaper('A4', 'landscape'); // Certificate is landscape A4
            $dompdf->render();
            
            $dompdf->stream("yuvalay_certificate_{$registration['registration_id']}.pdf", ["Attachment" => true]);
            exit;
        } catch (Exception $e) {
            error_log("Dompdf certificate generation failure: " . $e->getMessage());
        }
    }
}

// Render HTML
echo $html_content;
?>
