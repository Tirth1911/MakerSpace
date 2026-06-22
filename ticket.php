<?php
/**
 * Ticket Printing & PDF Generation Page
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

$reg_id = trim($_GET['reg_id'] ?? '');

if (empty($reg_id)) {
    die("Error: Ticket Registration ID is required.");
}

$dbObj = new Database();
$conn = $dbObj->getConnection();

$registration = null;

if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT r.*, e.title as event_title, e.event_date, e.event_time, e.venue, e.category, u.name as user_name, u.email as user_email, u.mobile as user_mobile 
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
    die("Error: Registration stub not found.");
}

// Check if user is authorized to view this ticket (Must be admin or owner)
$is_owner = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $registration['user_id']);
$is_admin = isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'admin');

if (!$is_owner && !$is_admin) {
    die("Access Denied. You do not have permission to view this ticket.");
}

// Capture HTML layout
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ticket #<?php echo $registration['registration_id']; ?> | Yuvalay MakerSpace</title>
  <style>
    body {
      background: #FFFFFF;
      color: #111111;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 20px;
    }
    .ticket-outer {
      max-width: 600px;
      margin: 40px auto;
      border: 2px dashed #8DC63F;
      border-radius: 20px;
      padding: 30px;
      position: relative;
      background: #F5F6F8;
    }
    .ticket-header {
      border-bottom: 2px solid #E5E7EB;
      padding-bottom: 20px;
      margin-bottom: 20px;
      text-align: center;
    }
    .ticket-title {
      font-size: 24px;
      font-weight: 800;
      margin: 0 0 5px 0;
      letter-spacing: -0.5px;
    }
    .ticket-subtitle {
      font-size: 11px;
      font-weight: 700;
      color: #8DC63F;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin: 0;
    }
    .ticket-body {
      display: table;
      width: 100%;
      margin-bottom: 20px;
    }
    .ticket-info {
      display: table-cell;
      width: 65%;
      vertical-align: top;
      text-align: left;
    }
    .ticket-qr {
      display: table-cell;
      width: 35%;
      vertical-align: middle;
      text-align: center;
    }
    .info-label {
      font-size: 9px;
      color: #6B7280;
      text-transform: uppercase;
      font-weight: 700;
      margin-bottom: 2px;
      display: block;
    }
    .info-value {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 12px;
      color: #111111;
      display: block;
    }
    .ticket-footer {
      border-top: 1px solid #E5E7EB;
      padding-top: 15px;
      font-size: 10px;
      color: #9CA3AF;
      text-align: center;
      line-height: 1.4;
    }
    .print-btn-container {
      text-align: center;
      margin-top: 20px;
    }
    .btn {
      background: #8DC63F;
      color: #111111;
      border: 0;
      padding: 12px 24px;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      font-size: 13px;
      text-decoration: none;
      display: inline-block;
    }
    .btn-secondary {
      background: #1A1A1A;
      color: #FFFFFF;
      margin-left: 10px;
    }
    @media print {
      body {
        padding: 0;
      }
      .ticket-outer {
        margin: 0 auto;
        border: 2px solid #8DC63F;
        background: #FFFFFF;
      }
      .print-btn-container {
        display: none;
      }
    }
  </style>
</head>
<body>

  <div class="ticket-outer">
    
    <div class="ticket-header">
      <h1 class="ticket-title">YUVALAY MAKERSPACE</h1>
      <p class="ticket-subtitle">Admission Ticket stub</p>
    </div>

    <div class="ticket-body">
      
      <!-- Info Left -->
      <div class="ticket-info">
        <span class="info-label">TICKET NUMBER</span>
        <span class="info-value" style="color:#8DC63F; font-size:16px; font-family: monospace;"><?php echo $registration['registration_id']; ?></span>
        
        <span class="info-label">EVENT TITLE</span>
        <span class="info-value"><?php echo htmlspecialchars($registration['event_title']); ?></span>
        
        <span class="info-label">PARTICIPANT NAME</span>
        <span class="info-value"><?php echo htmlspecialchars($registration['user_name']); ?></span>
        
        <span class="info-label">TIMING DETAILS</span>
        <span class="info-value">
          Date: <?php echo date('M d, Y', strtotime($registration['event_date'])); ?><br>
          Time: <?php echo date('h:i A', strtotime($registration['event_time'])); ?>
        </span>

        <span class="info-label">VENUE ROOM</span>
        <span class="info-value"><?php echo htmlspecialchars($registration['venue']); ?></span>
      </div>

      <!-- QR Right -->
      <div class="ticket-qr">
        <!-- Render QR using free qrcode API (No local server library configuration required) -->
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo $registration['registration_id']; ?>" alt="Ticket QR Code" style="border: 2px solid #E5E7EB; padding: 4px; background:#FFF; width:130px; height:130px;">
        <span style="font-size:8px; display:block; color:#9CA3AF; margin-top:5px;">SCAN AT ENTRANCE</span>
      </div>

    </div>

    <div class="ticket-footer">
      Please present this QR stub at the registration counter. Access is governed by standard lab safety guidelines. Wear closed-toe shoes inside workshops.
    </div>

  </div>

  <!-- Print triggers -->
  <div class="print-btn-container">
    <button onclick="window.print()" class="btn">Print Ticket stub</button>
    <a href="?reg_id=<?php echo $registration['registration_id']; ?>&pdf=true" class="btn btn-secondary">Download PDF</a>
  </div>

</body>
</html>
<?php
$html_content = ob_get_clean();

// Check if user wants PDF compile and DomPDF autoload is successfully ready
if (isset($_GET['pdf']) && $_GET['pdf'] === 'true') {
    $autoload_file = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload_file)) {
        require_once $autoload_file;
        
        try {
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml($html_content);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Output PDF directly
            $dompdf->stream("yuvalay_ticket_{$registration['registration_id']}.pdf", ["Attachment" => true]);
            exit;
        } catch (Exception $e) {
            // Log error and fallback to HTML render
            error_log("Dompdf compilation failure: " . $e->getMessage());
        }
    }
}

// Default Render HTML
echo $html_content;
?>
