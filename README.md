# Yuvalay MakerSpace Website (Premium Production Version)

A complete, high-performance, visually stunning website built for Yuvalay MakerSpace Vadodara. The application leverages PHP 8+, a MySQL database running on port `3307`, and premium frontend libraries (Tailwind CSS, GSAP, Swiper, FullCalendar, Chart.js) to deliver an interface comparable to Apple, Linear, and Stripe.

---

## Technical Stack & Libraries
- **Frontend**: HTML5, Tailwind CSS, JavaScript, GSAP, Swiper.js, FullCalendar.js, Chart.js.
- **Backend**: PHP 8+ (Session-based auth & role-based access controls).
- **Database**: MySQL (optimized for port `3307`).
- **External Packages**: `dompdf/dompdf` (PDF compilation) & `phpmailer/phpmailer` (SMTP mailing) installed via Composer.

---

## Folders & Architecture Layout
```text
D:\project\
│   api.php              # REST JSON API router and controller
│   index.php            # Homepage with slideshow and counters
│   about.php            # About page with timeline, team, and testimonials
│   what-we-do.php       # Detail about workshops, labs and certifications
│   resources.php        # Searchable and filterable technical guides
│   events.php           # Calendar scheduling and multi-step RSVP registrations
│   my-registrations.php # User bookings panel, tickets download and certificates
│   get-involved.php     # Mentor and Volunteer onboard CTAs
│   contact.php          # Contact sheet saving to DB, accordion FAQs
│   login.php            # Session auth member credentials form
│   register.php         # Sign up form with role selection & strength meter
│   admin.php            # Unified Admin Control panel with metrics graphs
│   ticket.php           # QR stub and printable/PDF ticket downloads
│   certificate.php      # Landscape certificate generator and PDF stream
│   setup_db.php         # Automated database build & seed migrations script
│
├───config/
│       db.php           # PDO connection class targeting port 3307
│
├───database/
│       schema.sql       # MySQL schema declarations
│
├───includes/
│       header.php       # Dynamically generated header, navbar & CMS switches
│       footer.php       # Closed tags footer, library CDNs, and floating CMS bars
│
└───public/
    ├───css/
    │       style.css    # Color schemes, custom scrollbars, animations, calendar styles
    │
    └───js/
            edit_cms.js  # Inline CMS double-click editor hooks & slideshow CRUDs
```

---

## Installation & Setup Instructions

### 1. Database Configuration
Ensure XAMPP MySQL is active. The database configuration targets `127.0.0.1:3307` out-of-the-box.
If your local MySQL port is different (e.g. standard `3306`), adjust the `$port` variable inside:
- [config/db.php](file:///d:/project/config/db.php)
- [setup_db.php](file:///d:/project/setup_db.php)

### 2. Auto-Migration & Seeding
Run the database migrations and data seedings directly using your command line:
```bash
php setup_db.php
```
This script will:
- Check for MySQL connection on port `3307`.
- Create `yuvalay_db` if missing.
- Parse and load [database/schema.sql](file:///d:/project/database/schema.sql) to build tables.
- Seed default CMS settings, 4 homepage slideshow slides, 3 user testimonials, 4 initial events, and 4 resource downloads.
- Create a default Super Admin user account.

### 3. Administrator Log In
- **Email**: `admin@yuvalay.org`
- **Password**: `admin123`

---

## Core Enterprise Features

### 1. Front-end "EDIT MODE" CMS
Logged-in administrators will see an **EDIT MODE: OFF** button on the top navbar, along with a floating CMS control toolbar at the bottom right.
- Toggle **Edit Mode: ON** to turn on inline editing.
- **Inline Text Modification**: Double-click any header, paragraph, address line, or list detail across the website. An input box will overlay the element. Edit the content and click **Save** to instantly update the values inside the MySQL database via API.
- **Slideshow manager**: Click **Edit Slideshow Slides** on the floating toolbar to open a management modal. Add slides, delete slides, edit slide titles/subtitles/image URLs, and specify their rendering order.
- **Global configurations**: Click **Edit Global Settings** to modify address lines, map overlays, phone numbers, and operational hours.

### 2. FullCalendar Scheduler & RSVP Registration
- Browse schedules using Month, Week, Day, and List views. Ongoing and upcoming workshops are color-coded dynamically.
- Click a card or calendar cell to open the Event Details modal with maps integration.
- Click **Register** to launch the Multi-Step wizard (Steps 1 to 5). Logged-in users have their profile and academic details pre-filled.
- Submit to generate a unique `YMS-EVT-XXXX` booking reference. It displays an admission ticket stub complete with a scanner-friendly QR code in the browser.

### 3. Tickets, Certificates, and CSV Logs
- **Admission Tickets**: Download print-friendly ticket stubs, or compile them directly into PDF downloads using DomPDF.
- **Achievement Certificates**: Marked attendees of completed events can download landscape-oriented certificates with verified sign-off fields.
- **Spreadsheet Exports**: Administrators can download RSVP logs for active events directly to Excel/CSV from the Admin Control Panel.
