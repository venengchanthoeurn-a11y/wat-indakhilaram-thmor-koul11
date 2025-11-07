<?php
include 'config.php';
requireLogin(); // Ensure user is logged in

// Function to convert Arabic numerals to Khmer numerals (kept for potential future use or if needed elsewhere)
function convertToArabicToKhmerNumerals($number) {
    $khmer_numerals = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];
    $arabic_numerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($arabic_numerals, $khmer_numerals, (string)$number);
}

// Handle monk deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM monks WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "លុបព័តពាន់ព្រះសង្ឃជោគជ័យ!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            error_log("Error deleting monk: " . $e->getMessage());
            $_SESSION['message'] = "មានបញ្ហាក្នុងការលុប។";
            $_SESSION['message_type'] = "danger";
        }
    }
    header("Location: dashboard.php");
    exit();
}

// Handle status update - This section is kept but its direct use in the main table is removed as requested.
// It can still be called from other pages or if needed in collapsed content.
if (isset($_POST['update_status']) && isset($_POST['id']) && isset($_POST['status'])) {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    if (in_array($status, ['ផ្ទុក', 'រៀន'])) {
        try {
            $stmt = $pdo->prepare("UPDATE monks SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $_SESSION['message'] = "ធ្វើបច្ចុប្បន្នភាពស្ថានភាពជោគជ័យ!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            error_log("Error updating status: " . $e->getMessage());
            $_SESSION['message'] = "មានបញ្ហាក្នុងការធ្វើបច្ចុប្បន្នភាព។";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "ស្ថានភាពមិនត្រឹមត្រូវ។";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: dashboard.php");
    exit();
}

// Initial kuti data insertion (run once to populate if empty)
function initializeKuti($pdo) {
    $kutiData = [
        "មហាកុដិ",
        "កុដិបណ្ណាល័យ",
        "កុដិសាលាបុណ្យ",
        "កុដិឈើតូច",
        "កុដិថ្មតូច",
        "កុដិសាលាឆាន់"
    ];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kuti");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        foreach ($kutiData as $name) {
            $stmt = $pdo->prepare("INSERT INTO kuti (kuti_name) VALUES (?)");
            $stmt->execute([$name]);
        }
    }
}
initializeKuti($pdo);

// Fetch statistics (numbers will be Arabic for display as per new request)
$total_monks = $pdo->query("SELECT COUNT(*) FROM monks")->fetchColumn();
$total_bhikku = $pdo->query("SELECT COUNT(*) FROM monks WHERE monk_type = 'ភិក្ខុ'")->fetchColumn();
$total_samanera = $pdo->query("SELECT COUNT(*) FROM monks WHERE monk_type = 'សាមណេរ'")->fetchColumn();
$total_kuti = $pdo->query("SELECT COUNT(*) FROM kuti")->fetchColumn();
// Assuming 'attendance' table has an ID or unique row per attendance record
$total_attendance = $pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();


// Data for the 6 new Table-cards
$monks_list = [];
$bhikku_list = [];
$samanera_list = [];
$kuti_list_data = []; // Renamed to avoid conflict with dropdown list
$attendance_list = [];
$logs_list = [];

try {
    // 1. Data for "បញ្ជីព្រះសង្ឃសរុប" (All monks)
    $monks_list = $pdo->query("SELECT id, khmer_name, latin_name, birth_date, role FROM monks ORDER BY id ASC")->fetchAll();

    // 2. Data for "បញ្ជីភិក្ខុថ្មីៗ"
    $bhikku_list = $pdo->query("SELECT id, khmer_name, latin_name, birth_date, role FROM monks WHERE monk_type = 'ភិក្ខុ' ORDER BY id ASC LIMIT 5")->fetchAll();

    // 3. Data for "បញ្ជីសាមណេរថ្មីៗ"
    $samanera_list = $pdo->query("SELECT id, khmer_name, latin_name, birth_date, role FROM monks WHERE monk_type = 'សាមណេរ' ORDER BY id ASC LIMIT 5")->fetchAll();

    // 4. Data for "បញ្ជីកុដិ"
    $kuti_list_data = $pdo->query("SELECT k.id as kuti_id, k.kuti_name, COUNT(m.id) as monk_count FROM kuti k LEFT JOIN monks m ON k.id = m.kuti_id GROUP BY k.kuti_name, k.id ORDER BY k.id ASC LIMIT 5")->fetchAll();
    
    // 5. Data for "បញ្ជីវត្តមានថ្មីៗ"
    $recent_attendance_data = $pdo->query("SELECT a.id as attendance_id, a.attendance_date, m.khmer_name, a.status FROM attendance a JOIN monks m ON a.monk_id = m.id ORDER BY a.id ASC LIMIT 5")->fetchAll();
    
    // 6. Data for "កំណត់ហេតុប្រព័ន្ធ"
    // Assuming 'logs' table has 'id', 'action', and 'timestamp'
    $logs_list = $pdo->query("SELECT id, action, timestamp FROM logs ORDER BY id ASC LIMIT 5")->fetchAll();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = "មានបញ្ហាក្នុងការទាញយកទិន្នន័យពីមូលដ្ឋានទិន្នន័យ។ សូមពិនិត្យមើលថា database schema ត្រឹមត្រូវ។";
    $_SESSION['message_type'] = "danger";
    $monks_list = [];
    $bhikku_list = [];
    $samanera_list = [];
    $kuti_list_data = [];
    $recent_attendance_data = [];
    $logs_list = [];
}


// Determine which section to display
$current_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard'; // Default to 'dashboard'
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ផ្ទាំងគ្រប់គ្រង - ប្រព័ន្ធគ្រប់គ្រងវត្ត</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Khmer+OS+Muol&family=Khmer+OS+Siemreap&display=swap" rel="stylesheet">
    <style>
        /* Define a consistent color palette */
        :root {
            --primary-theme-gold: #D4AF37; /* Gold */
            --secondary-theme-red: #7A1022; /* Deep Red */
            --tertiary-dark-blue: #07004D; /* Dark Blue */
            --light-bg-cream: #FAF5E0; /* Light Cream */
            --lighter-bg-pale: #FFF9E6; /* Even Lighter Cream */
            --text-dark-brown: #5C4033; /* Dark Brown */
            --text-light-gray: #F1E7E7; /* Light text for dark backgrounds */
            --orange-icon-color: #FF8C00; /* Bright Orange for icons */
            --orange-gradient-start: #FFA500; /* Orange start for numbers */
            --orange-gradient-end: #FFD700; /* Gold-orange end for numbers */

            /* New colors for Table-cards */
            --card-header-1-bg: #D4AF37; /* Gold for Total Monks */
            --card-header-1-text: #7A1022; /* Deep Red */
            --card-header-2-bg: #1E90FF; /* DodgerBlue for Bhikku */
            --card-header-2-text: #FFFFFF; /* White */
            --card-header-3-bg: #32CD32; /* LimeGreen for Samanera */
            --card-header-3-text: #FFFFFF; /* White */
            --card-header-4-bg: #8A2BE2; /* BlueViolet for Kuti */
            --card-header-4-text: #FFFFFF; /* White */
            --card-header-5-bg: #20B2AA; /* LightSeaGreen for Attendance */
            --card-header-5-text: #FFFFFF; /* White */
            --card-header-6-bg: #A0522D; /* Sienna for Logs */
            --card-header-6-text: #FFFFFF; /* White */

            /* Backgrounds for even rows in table cards */
            --card-table-even-bg-1: rgba(212, 175, 55, 0.05); /* Gold tint */
            --card-table-even-bg-2: rgba(30, 144, 255, 0.05); /* Blue tint */
            --card-table-even-bg-3: rgba(50, 205, 50, 0.05); /* Green tint */
            --card-table-even-bg-4: rgba(138, 43, 226, 0.05); /* Violet tint */
            --card-table-even-bg-5: rgba(32, 178, 170, 0.05); /* Teal tint */
            --card-table-even-bg-6: rgba(160, 82, 45, 0.05); /* Sienna tint */
        }

        body {
            background: linear-gradient(135deg, var(--lighter-bg-pale), var(--light-bg-cream));
            font-family: 'Khmer OS Siemreap', sans-serif; /* Consistent font for body */
            color: var(--text-dark-brown);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background-color: var(--tertiary-dark-blue); /* Dark blue background */
            color: var(--primary-theme-gold);
            padding: 30px 20px;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.4);
            z-index: 1000;
            transition: transform 0.3s ease, width 0.3s ease;
        }
        .sidebar img {
            width: 250px; /* Larger logo in sidebar */
            height: 250px; /* Maintain aspect ratio */
            margin: 0 auto 20px;
            padding: 10px;
            border-radius: 50%;
            object-fit: cover; /* Ensures image covers circle */
            transition: transform 0.3s ease;
        }
        .sidebar img:hover {
            transform: scale(1.05);
        }
        .sidebar h4 {
            font-family: 'Khmer OS Muol', sans-serif; /* Muol for sidebar title */
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-light-gray); /* Light text color */
            font-size: 1.8rem; /* Larger sidebar title */
            text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
        }
        .sidebar .nav-link {
            color: var(--text-light-gray);
            padding: 12px 20px;
            margin-bottom: 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            font-size: 1.1rem;
            font-family: 'Khmer OS Siemreap', sans-serif; /* Siemreap for nav links */
            text-shadow: 0px 0px 2px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: var(--primary-theme-gold); /* Gold background on hover/active */
            color: var(--secondary-theme-red); /* Deep red text on hover/active */
            text-shadow: 0 0 8px rgba(122, 16, 34, 0.9);
            transform: translateX(5px);
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.4), inset 0 0 5px rgba(255, 215, 0, 0.2),
                        0 0 15px rgba(255, 215, 0, 0.6), 0 0 25px rgba(255, 215, 0, 0.4);
        }
        .sidebar .nav-link i {
            margin-right: 15px;
            font-size: 1.2rem; /* Larger icons */
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Main Content Styling */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        .main-content h2 {
            font-family: 'Khmer OS Muol', sans-serif; /* Muol for main content titles */
            color: var(--secondary-theme-red); /* Deep red for titles */
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            font-size: 2.5rem; /* Larger main title */
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            padding-bottom: 10px;
            text-align: center;
        }

        .welcome-text {
            font-family: 'Khmer OS Siemreap', sans-serif; /* Siemreap for welcome text */
            color: var(--text-dark-brown);
            background-color: rgba(212, 175, 55, 0.2);
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
            text-shadow: none;
            font-size: 1.1rem;
            border: 1px solid rgba(212, 175, 55, 0.4);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Stat Panel Styling (Simplified as per request) */
        .stat-panel {
            background-color: #FFFFFF;
            border: 2px solid var(--primary-theme-gold);
            border-radius: 12px;
            text-align: center;
            padding: 20px;
            margin: 10px;
            width: 300px;
            height: 220px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 0 10px rgba(212, 175, 55, 0.6), 
                        0 0 20px rgba(212, 175, 55, 0.4),
                        0 0 30px rgba(212, 175, 55, 0.2);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-panel:hover {
            transform: translateY(-8px);
            animation: panelPulse 0.5s ease-in-out forwards;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.8),
                        0 0 30px rgba(212, 175, 55, 0.6),
                        0 0 40px rgba(212, 175, 55, 0.4);
        }

        @keyframes panelPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        .stat-panel i {
            font-size: 3.5rem;
            margin-bottom: 10px;
            color: var(--orange-icon-color);
            text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
        }

        .stat-panel h5 {
            font-family: 'Khmer OS Muol', sans-serif;
            font-size: 1.25rem;
            margin: 0;
            line-height: 1.2;
            color: var(--text-dark-brown);
        }

        .stat-panel p {
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 8px 0 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            color: var(--primary-theme-gold);
        }

        /* Table Card Styling (for the 6 new cards) */
        .table-card {
            background-color: var(--light-bg-cream);
            border: 2px solid var(--primary-theme-gold);
            border-radius: 15px;
            padding: 25px;
            margin-top: 40px; /* Space between cards */
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.6),
                        0 0 30px rgba(212, 175, 55, 0.4),
                        0 0 50px rgba(212, 175, 55, 0.2);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .table-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, transparent 70%);
            z-index: 0;
            animation: glow 8s infinite alternate;
        }

        /* Unique Table Card Headers */
        .table-card .card-header {
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 25px -25px;
            position: relative;
            z-index: 1;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            font-size: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Theme 1: Default Gold/Red for Total Monks */
        .table-card-theme-1 .card-header {
            background-color: var(--card-header-1-bg);
            color: var(--card-header-1-text);
        }
        .table-card-theme-1 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-1);
        }

        /* Theme 2: Blue for Bhikku */
        .table-card-theme-2 .card-header {
            background-color: var(--card-header-2-bg);
            color: var(--card-header-2-text);
        }
        .table-card-theme-2 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-2);
        }

        /* Theme 3: Green for Samanera */
        .table-card-theme-3 .card-header {
            background-color: var(--card-header-3-bg);
            color: var(--card-header-3-text);
        }
        .table-card-theme-3 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-3);
        }

        /* Theme 4: Violet for Kuti */
        .table-card-theme-4 .card-header {
            background-color: var(--card-header-4-bg);
            color: var(--card-header-4-text);
        }
        .table-card-theme-4 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-4);
        }

        /* Theme 5: Teal for Attendance */
        .table-card-theme-5 .card-header {
            background-color: var(--card-header-5-bg);
            color: var(--card-header-5-text);
        }
        .table-card-theme-5 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-5);
        }

        /* Theme 6: Sienna for Logs */
        .table-card-theme-6 .card-header {
            background-color: var(--card-header-6-bg);
            color: var(--card-header-6-text);
        }
        .table-card-theme-6 .table tbody tr:nth-child(even) {
            background-color: var(--card-table-even-bg-6);
        }


        .table-card .table {
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--text-dark-brown);
            margin-bottom: 0;
            position: relative;
            z-index: 1;
            font-size: 1.05rem;
            border-collapse: collapse; /* Changed to collapse for grid appearance */
        }

        .table-card .table thead {
            background-color: var(--tertiary-dark-blue);
            color: var(--text-light-gray);
            font-family: 'Khmer OS Muol', sans-serif;
        }

        .table-card .table th, .table-card .table td {
            padding: 12px;
            vertical-align: middle;
            border: 1px solid var(--primary-theme-gold); /* Added gold border */
            /* border-radius: 0 !important; for cells */
        }
        .table-card .table th:first-child { border-top-left-radius: 8px; } /* Keep for overall table top corners */
        .table-card .table th:last-child { border-top-right-radius: 8px; } /* Keep for overall table top corners */


        .table-card .table tbody tr {
            background-color: #FFFFFF;
            /* box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05); /* Remove or make subtle on table instead of rows */
            transition: all 0.2s ease;
        }

        .table-card .table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.15); /* Keep a subtle hover */
            transform: translateY(-2px);
        }

        /* No need for specific border-bottom or border-radius on cells if all cells have borders */
        /* .table-card .table tbody tr:last-child td { border-bottom: none; } */
        /* .table-card .table tbody tr td:first-child { border-bottom-left-radius: 8px; border-top-left-radius: 8px; } */
        /* .table-card .table tbody tr td:last-child { border-bottom-right-radius: 8px; border-top-right-radius: 8px; } */

        /* Styling for numbers in the main table cards */
        .table-card .table tbody td.numeric-id {
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 0.9rem;
            font-weight: bold;
            color: var(--orange-gradient-start);
            text-shadow: 0 0 5px var(--orange-gradient-end);
        }

        /* Button Styling (general for dashboard) */
        .btn-primary, .btn-info, .btn-warning, .btn-danger, .btn-print {
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 8px 15px;
            border-radius: 8px;
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
        }

        .btn-primary:hover, .btn-info:hover, .btn-warning:hover, .btn-danger:hover, .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* Custom button colors based on card themes for a more integrated look */
        .table-card-theme-1 .btn-primary, .table-card-theme-1 .btn-info { background-color: var(--card-header-1-bg); border-color: var(--card-header-1-bg); color: var(--card-header-1-text); }
        .table-card-theme-1 .btn-primary:hover, .table-card-theme-1 .btn-info:hover { background-color: #C19A6B; border-color: #C19A6B; color: var(--card-header-1-text); }
        .table-card-theme-1 .btn-warning { background-color: #E6B800; border-color: #E6B800; color: var(--text-dark-brown); }
        .table-card-theme-1 .btn-warning:hover { background-color: #CC9900; border-color: #CC9900; }
        .table-card-theme-1 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-1 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .table-card-theme-2 .btn-primary, .table-card-theme-2 .btn-info { background-color: var(--card-header-2-bg); border-color: var(--card-header-2-bg); color: var(--card-header-2-text); }
        .table-card-theme-2 .btn-primary:hover, .table-card-theme-2 .btn-info:hover { background-color: #1674D1; border-color: #1674D1; color: var(--card-header-2-text); }
        .table-card-theme-2 .btn-warning { background-color: #4682B4; border-color: #4682B4; color: var(--text-light-gray); }
        .table-card-theme-2 .btn-warning:hover { background-color: #3B6F9E; border-color: #3B6F9E; }
        .table-card-theme-2 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-2 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .table-card-theme-3 .btn-primary, .table-card-theme-3 .btn-info { background-color: var(--card-header-3-bg); border-color: var(--card-header-3-bg); color: var(--card-header-3-text); }
        .table-card-theme-3 .btn-primary:hover, .table-card-theme-3 .btn-info:hover { background-color: #28A745; border-color: #28A745; color: var(--card-header-3-text); }
        .table-card-theme-3 .btn-warning { background-color: #6B8E23; border-color: #6B8E23; color: var(--text-light-gray); }
        .table-card-theme-3 .btn-warning:hover { background-color: #5A771E; border-color: #5A771E; }
        .table-card-theme-3 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-3 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .table-card-theme-4 .btn-primary, .table-card-theme-4 .btn-info { background-color: var(--card-header-4-bg); border-color: var(--card-header-4-bg); color: var(--card-header-4-text); }
        .table-card-theme-4 .btn-primary:hover, .table-card-theme-4 .btn-info:hover { background-color: #791CB9; border-color: #791CB9; color: var(--card-header-4-text); }
        .table-card-theme-4 .btn-warning { background-color: #9370DB; border-color: #9370DB; color: var(--text-light-gray); }
        .table-card-theme-4 .btn-warning:hover { background-color: #7E5CC4; border-color: #7E5CC4; }
        .table-card-theme-4 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-4 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .table-card-theme-5 .btn-primary, .table-card-theme-5 .btn-info { background-color: var(--card-header-5-bg); border-color: var(--card-header-5-bg); color: var(--card-header-5-text); }
        .table-card-theme-5 .btn-primary:hover, .table-card-theme-5 .btn-info:hover { background-color: #1A928D; border-color: #1A928D; color: var(--card-header-5-text); }
        .table-card-theme-5 .btn-warning { background-color: #48D1CC; border-color: #48D1CC; color: var(--text-dark-brown); }
        .table-card-theme-5 .btn-warning:hover { background-color: #38A09B; border-color: #38A09B; }
        .table-card-theme-5 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-5 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .table-card-theme-6 .btn-primary, .table-card-theme-6 .btn-info { background-color: var(--card-header-6-bg); border-color: var(--card-header-6-bg); color: var(--card-header-6-text); }
        .table-card-theme-6 .btn-primary:hover, .table-card-theme-6 .btn-info:hover { background-color: #8D4726; border-color: #8D4726; color: var(--card-header-6-text); }
        .table-card-theme-6 .btn-warning { background-color: #D2B48C; border-color: #D2B48C; color: var(--text-dark-brown); }
        .table-card-theme-6 .btn-warning:hover { background-color: #BEA07D; border-color: #BEA07D; }
        .table-card-theme-6 .btn-danger { background-color: #DC3545; border-color: #DC3545; color: var(--text-light-gray); }
        .table-card-theme-6 .btn-danger:hover { background-color: #C82333; border-color: #C82333; }

        .btn-sm {
            padding: 6px 10px;
            font-size: 0.9rem;
            border-radius: 6px;
        }

        .btn i {
            margin-right: 5px;
        }

        /* Alert Styling */
        .alert {
            font-family: 'Khmer OS Siemreap', sans-serif;
            border-radius: 10px;
            background-color: rgba(212, 175, 55, 0.2);
            border: 1px solid var(--primary-theme-gold);
            color: var(--text-dark-brown);
            position: relative;
            z-index: 1;
            padding: 15px 20px;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: rgba(0, 100, 0, 0.1);
            border-color: #006400;
            color: #004d00;
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: #dc3545;
            color: #b02a37;
        }
        .alert .btn-close {
            background-color: transparent;
            color: var(--text-dark-brown);
            opacity: 0.7;
            font-size: 1.2rem;
            transition: opacity 0.2s ease;
        }

        .alert .btn-close:hover {
            opacity: 1;
            color: var(--secondary-theme-red);
        }

        /* Content Card (for About section) */
        .content-card {
            background-color: var(--light-bg-cream);
            border: 2px solid var(--primary-theme-gold);
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.6),
                        0 0 30px rgba(212, 175, 55, 0.4);
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .content-card .card-header {
            background-color: var(--primary-theme-gold);
            color: var(--secondary-theme-red);
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -25px -25px 25px -25px;
            font-size: 1.5rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }

        .pagoda-image {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 0 auto 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .pagoda-history p {
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-dark-brown);
            margin-bottom: 15px;
            text-align: justify;
        }

        /* Toggle Sidebar Button (for mobile) */
        .toggle-sidebar {
            display: none; /* Hidden by default on larger screens */
            position: fixed;
            top: 15px;
            left: 15px;
            background-color: var(--primary-theme-gold);
            color: var(--secondary-theme-red);
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            z-index: 1001;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .toggle-sidebar:hover {
            background-color: #C19A6B;
            transform: scale(1.05);
        }

        /* Keyframe for table-card glow */
        @keyframes glow {
            0% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.02); opacity: 0.2; }
            100% { transform: scale(1); opacity: 0.6; }
        }

        /* Responsive Design */
        @media print {
            @page {
                size: A4 landscape; /* Set page size to A4 landscape */
                margin: 1cm; /* Add some margin to the printed page */
            }

            body {
                margin: 0; /* Override any screen margins for print */
                padding: 0;
                color: #000; /* Ensure text is black for print */
                font-family: 'Khmer OS Siemreap', sans-serif; /* Consistent font for print */
            }

            /* Hide all non-essential elements for print */
            .sidebar, .toggle-sidebar, .main-content h2, .welcome-text, .alert, .stat-panel,
            .table-card .card-header > div:not(.print-title-only), /* Hide buttons in card header by default */
            .content-card .card-header > div:not(.print-title-only) /* Hide buttons in content card header by default */
            {
                display: none !important;
                visibility: hidden !important;
            }

            /* Ensure main-content is visible and occupies full width during print */
            .main-content {
                margin-left: 0 !important; /* Remove sidebar margin */
                padding: 0 !important; /* Remove any padding */
                display: block !important;
                visibility: visible !important;
                width: 100% !important; /* Take full available width */
            }

            /* Only show the element with 'print-active' class and its children */
            .table-card.print-active,
            .content-card.print-active {
                display: block !important;
                visibility: visible !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                background-color: transparent !important;
                page-break-inside: avoid; /* Prevent content from breaking across pages */
            }

            /* Make all direct children of the print-active card visible and display correctly */
            /* This targets content within the card like .card-header, .table-responsive, .card-body */
            .table-card.print-active > *,
            .content-card.print-active > * {
                display: block !important; /* Force block display for main sections */
                visibility: visible !important;
                background-color: transparent !important;
                box-shadow: none !important;
                text-shadow: none !important;
                color: #000 !important;
                border-radius: 0 !important;
                margin: 0 !important; /* Reset margins */
                padding: 0 !important; /* Reset paddings */
            }

            /* Further ensure specific elements within the print-active card are visible and correctly styled */
            .table-card.print-active .card-header,
            .content-card.print-active .card-header {
                background-color: #eee !important; /* Light grey header for print */
                font-family: 'Khmer OS Muol', sans-serif !important;
                padding: 10px !important;
                border-bottom: 1px solid #000 !important;
                font-size: 1.2rem !important;
                text-align: left !important;
                display: block !important; /* Ensure header itself is a block */
                margin-bottom: 15px !important; /* Space after header */
            }
            /* Hide any remaining buttons/controls within the header that are not the title */
            .table-card.print-active .card-header .btn,
            .table-card.print-active .card-header a,
            .content-card.print-active .card-header .btn,
            .content-card.print-active .card-header a {
                display: none !important;
            }

            /* Table specific styles for print */
            .table-card.print-active .table-responsive {
                display: block !important; /* Ensure responsive wrapper is visible */
                overflow: visible !important; /* Prevent horizontal scrolling in print */
            }

            .table-card.print-active .table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1px solid #000 !important; /* Overall table border for notebook look */
                font-size: 10pt !important;
                color: #000 !important;
                border-spacing: 0 !important;
            }

            .table-card.print-active .table th,
            .table-card.print-active .table td {
                border: 1px solid #000 !important; /* Cell borders */
                padding: 8px 10px !important; /* Good padding for cells */
                text-align: left !important;
                vertical-align: top !important;
                font-size: 10pt !important;
                background-color: #fff !important; /* Force white background for all cells */
            }

            .table-card.print-active .table thead {
                background-color: #f0f0f0 !important; /* Light grey header background */
                color: #000 !important;
            }

            /* Ensure table row backgrounds are white and no hover effects */
            .table-card.print-active .table tbody tr,
            .table-card.print-active .table tbody tr:nth-child(even),
            .table-card.print-active .table tbody tr:nth-child(odd),
            .table-card.print-active .table tbody tr:hover {
                background-color: #fff !important;
            }

            /* Numeric ID specific styling for print */
            .table-card.print-active .table tbody td.numeric-id {
                font-weight: normal !important;
                font-family: 'Khmer OS Siemreap', sans-serif !important;
                color: #000 !important;
                text-shadow: none !important;
            }

            /* Specific print styling for About section images and text */
            .content-card.print-active .pagoda-image {
                max-width: 100% !important;
                height: auto !important;
                display: block !important;
                margin: 0 auto 15px !important;
            }
            .content-card.print-active .pagoda-history p {
                font-size: 10pt !important;
                line-height: 1.5 !important;
                color: #000 !important;
                text-align: justify !important;
            }

            /* Remove responsive table specific pseudo-elements from print */
            .table td::before {
                content: none !important;
            }
            .table td {
                padding-left: initial !important; /* Reset padding from mobile view */
                text-align: left !important; /* Ensure text aligns left in print */
            }
            .table td:last-child {
                text-align: left !important; /* Ensure last column also aligns left */
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-280px);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .toggle-sidebar {
                display: block;
            }
            .main-content h2 {
                font-size: 1.8rem;
            }
            .welcome-text {
                font-size: 1rem;
            }
            .stat-panel {
                width: 180px;
                height: 140px;
                padding: 12px;
                margin: 8px;
            }
            .stat-panel i {
                font-size: 2rem;
            }
            .stat-panel h5 {
                font-size: 0.9rem;
            }
            .stat-panel p {
                font-size: 1.5rem;
            }
            .table-card {
                padding: 15px;
            }
            .table-card .card-header {
                font-size: 1.2rem;
                padding: 10px 15px;
            }
            .table th, .table td {
                padding: 8px;
                font-size: 0.9rem;
            }
            .btn-primary, .btn-info, .btn-warning, .btn-danger, .btn-print {
                padding: 6px 12px;
                font-size: 0.9rem;
            }
            .btn-sm {
                padding: 4px 8px;
                font-size: 0.8rem;
            }
            .content-card {
                padding: 15px;
            }
            .content-card .card-header {
                font-size: 1.2rem;
                padding: 10px 15px;
            }
            .pagoda-history p {
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .stat-panel {
                width: 140px;
                height: 110px;
                margin: 5px;
                padding: 8px;
            }
            .stat-panel i {
                font-size: 1.8rem;
            }
            .stat-panel h5 {
                font-size: 0.75rem;
            }
            .stat-panel p {
                font-size: 1.2rem;
            }
            .table thead {
                display: none;
            }
            .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }
            .table tr {
                margin-bottom: 15px;
                border: 1px solid var(--primary-theme-gold);
                border-radius: 8px;
                padding: 10px;
            }
            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none;
            }
            .table td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: calc(50% - 20px);
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: bold;
                color: var(--secondary-theme-red);
                font-family: 'Khmer OS Muol', sans-serif;
            }
            .table td:last-child {
                text-align: center;
                padding-left: 10px;
            }
            .table td:last-child::before {
                content: none;
            }
            .table-card .card-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .table-card .card-header > div {
                margin-top: 10px;
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
            }
            .table-card .card-header .btn {
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <button class="toggle-sidebar" id="toggleSidebar"><i class="bi bi-list"></i></button>
    <div class="sidebar" id="sidebar">
        <img src="LOGO.PNG" onerror="this.onerror=null;this.src='https://placehold.co/180x180/07004D/D4AF37?text=វត្តឥន្ទខីលារាម';" alt="វត្តឥន្ទខីលារាម-ថ្មគោល">
        <h4>វត្តឥន្ទខីលារាម ថ្មគោល </h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link <?php echo ($current_section == 'about' ? 'active' : ''); ?>" href="dashboard.php?section=about"><i class="bi bi-building"></i> ទូទៅអំពីវត្ត</a></li>
            <li class="nav-item"><a class="nav-link <?php echo ($current_section == 'dashboard' ? 'active' : ''); ?>" href="dashboard.php?section=dashboard"><i class="bi bi-house-door-fill"></i> ផ្ទាំងគ្រប់គ្រង</a></li>
            <li class="nav-item"><a class="nav-link" href="monks.php"><i class="bi bi-people-fill"></i> បញ្ជីព្រះសង្ឃ</a></li>
            <li class="nav-item"><a class="nav-link" href="attendance.php"><i class="bi bi-calendar-check-fill"></i> វត្តមាន</a></li>
            <li class="nav-item"><a class="nav-link" href="kuti.php"><i class="bi bi-building-fill"></i> គ្រប់គ្រងកុដិ</a></li>
            <li class="nav-item"><a class="nav-link" href="monks_by_kuti.php"><i class="bi bi-person-lines-fill"></i> ទិន្នន័យតាមកុដិ</a></li>
            <li class="nav-item"><a class="nav-link" href="reports.php"><i class="bi bi-file-earmark-bar-graph-fill"></i> របាយការណ៍</a></li>
            <li class="nav-item"><a class="nav-link" href="logs.php"><i class="bi bi-clock-history"></i> កំណត់ហេតុ</a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> ចាកចេញ</a></li>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($current_section == 'dashboard'): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>ផ្ទាំងគ្រប់គ្រងទូទៅ</h2>
                <!-- Added null coalescing operator for robustness in case $_SESSION['username'] is not set -->
                <span class="welcome-text">សូមស្វាគមន៍, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong></span>
            </div>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-dismissible fade show <?php echo htmlspecialchars($_SESSION['message_type'] == 'success' ? 'alert-success' : 'alert-danger'); ?>" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            <div class="d-flex flex-wrap justify-content-center mb-4">
                <!-- Total Monks Stat Panel -->
                <div class="stat-panel">
                    <i class="bi bi-person-badge-fill"></i>
                    <h5>ព្រះសង្ឃសរុប</h5>
                    <p><?php echo $total_monks; ?></p>
                </div>

                <!-- Bhikku Stat Panel -->
                <div class="stat-panel">
                    <i class="bi bi-person-vcard-fill"></i>
                    <h5>ភិក្ខុ</h5>
                    <p><?php echo $total_bhikku; ?></p>
                </div>

                <!-- Samanera Stat Panel -->
                <div class="stat-panel">
                    <i class="bi bi-person-fill-exclamation"></i>
                    <h5>សាមណេរ</h5>
                    <p><?php echo $total_samanera; ?></p>
                </div>

                <!-- Total Kuti Stat Panel -->
                <div class="stat-panel">
                    <i class="bi bi-house-door-fill"></i>
                    <h5>ចំនួនកុដិ</h5>
                    <p><?php echo $total_kuti; ?></p>
                </div>

                <!-- Total Attendance Stat Panel -->
                <div class="stat-panel">
                    <i class="bi bi-journal-check"></i>
                    <h5>វត្តមានសរុប</h5>
                    <p><?php echo $total_attendance; ?></p>
                </div>
                
                <!-- Link to Kuti Management (Action Panel) - no change to this as it's a direct link -->
                
            </div>

            <!-- New Table Cards Section -->
            <div class="row">
                <!-- Table Card 1: បញ្ជីព្រះសង្ឃសរុប (All Monks) -->
                <div class="col-12 mb-4" id="table-card-total-monks">
                    <div class="table-card table-card-theme-1">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>បញ្ជីរាយនាមព្រះសង្ឃសរុប ទាំងអស់</h4>
                            <div>
                                <a href="add_monk.php" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> បន្ថែមព្រះសង្ឃថ្មី</a>
                                <a href="monks.php" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                                <button class="btn btn-primary btn-print" onclick="printSpecificTable('table-card-total-monks')"><i class="bi bi-printer-fill"></i> បោះពុម្ភ</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>គោត្តនាម (ខ្មែរ)</th>
                                        <th>គោត្តនាម (ឡាតាំង)</th>
                                        <th>ថ្ងៃខែឆ្នាំកំណើត</th>
                                        <th>ស្ថានភាព</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($monks_list)): ?>
                                        <tr><td colspan="6" class="text-center">មិនមានទិន្នន័យព្រះសង្ឃទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($monks_list as $monk): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('M%03d', $counter++); ?></td>
                                                <td data-label="គោត្តនាម (ខ្មែរ)"><?php echo htmlspecialchars($monk['khmer_name']); ?></td>
                                                <td data-label="គោត្តនាម (ឡាតាំង)"><?php echo htmlspecialchars($monk['latin_name']); ?></td>
                                                <td data-label="ថ្ងៃខែឆ្នាំកំណើត">
                                                    <?php
                                                    $birth_date = htmlspecialchars($monk['birth_date']);
                                                    echo (!empty($birth_date) && strtotime($birth_date)) ? date('d/m/Y', strtotime($birth_date)) : 'N/A';
                                                    ?>
                                                </td>
                                                <td data-label="ស្ថានភាព"><?php echo htmlspecialchars($monk['role'] ?: 'N/A'); ?></td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <a href="edit_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-warning btn-sm me-1" title="កែប្រែ"><i class="bi bi-pencil"></i></a>
                                                    <a href="dashboard.php?delete=<?php echo $monk['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបព្រះសង្ឃនេះទេ?');" title="លុប"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table Card 2: បញ្ជីភិក្ខុថ្មីៗ -->
                <div class="col-12 mb-4" id="table-card-bhikku">
                    <div class="table-card table-card-theme-2">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>តារាងរាយនាមចំនួនភិក្ខុ ​សរុប</h4>
                            <div>
                                <a href="add_monk.php?type=ភិក្ខុ" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> បន្ថែមភិក្ខុ</a>
                                <a href="monks.php?type=ភិក្ខុ" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>គោត្តនាម (ខ្មែរ)</th>
                                        <th>គោត្តនាម (ឡាតាំង)</th>
                                        <th>ថ្ងៃខែឆ្នាំកំណើត</th>
                                        <th>ស្ថានភាព</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($bhikku_list)): ?>
                                        <tr><td colspan="6" class="text-center">មិនមានទិន្នន័យភិក្ខុថ្មីៗទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($bhikku_list as $monk): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('M%03d', $counter++); ?></td>
                                                <td data-label="គោត្តនាម (ខ្មែរ)"><?php echo htmlspecialchars($monk['khmer_name']); ?></td>
                                                <td data-label="គោត្តនាម (ឡាតាំង)"><?php echo htmlspecialchars($monk['latin_name']); ?></td>
                                                <td data-label="ថ្ងៃខែឆ្នាំកំណើត">
                                                    <?php
                                                    $birth_date = htmlspecialchars($monk['birth_date']);
                                                    echo (!empty($birth_date) && strtotime($birth_date)) ? date('d/m/Y', strtotime($birth_date)) : 'N/A';
                                                    ?>
                                                </td>
                                                <td data-label="ស្ថានភាព"><?php echo htmlspecialchars($monk['role'] ?: 'N/A'); ?></td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <a href="edit_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-warning btn-sm me-1" title="កែប្រែ"><i class="bi bi-pencil"></i></a>
                                                    <a href="dashboard.php?delete=<?php echo $monk['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបព្រះសង្ឃនេះទេ?');" title="លុប"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table Card 3: បញ្ជីសាមណេរថ្មីៗ -->
                <div class="col-12 mb-4" id="table-card-samanera">
                    <div class="table-card table-card-theme-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>តារាងរាយនាមចំនួនសាមណេរ សរុប</h4>
                            <div>
                                <a href="add_monk.php?type=ភិក្ខុ" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> បន្ថែមសាមណេរថ្មី</a>
                                <a href="monks.php?type=ភិក្ខុ" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                               
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>គោត្តនាម (ខ្មែរ)</th>
                                        <th>គោត្តនាម (ឡាតាំង)</th>
                                        <th>ថ្ងៃខែឆ្នាំកំណើត</th>
                                        <th>ស្ថានភាព</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($samanera_list)): ?>
                                        <tr><td colspan="6" class="text-center">មិនមានទិន្នន័យសាមណេរថ្មីៗទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($samanera_list as $monk): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('M%03d', $counter++); ?></td>
                                                <td data-label="គោត្តនាម (ខ្មែរ)"><?php echo htmlspecialchars($monk['khmer_name']); ?></td>
                                                <td data-label="គោត្តនាម (ឡាតាំង)"><?php echo htmlspecialchars($monk['latin_name']); ?></td>
                                                <td data-label="ថ្ងៃខែឆ្នាំកំណើត">
                                                    <?php
                                                    $birth_date = htmlspecialchars($monk['birth_date']);
                                                    echo (!empty($birth_date) && strtotime($birth_date)) ? date('d/m/Y', strtotime($birth_date)) : 'N/A';
                                                    ?>
                                                </td>
                                                <td data-label="ស្ថានភាព"><?php echo htmlspecialchars($monk['role'] ?: 'N/A'); ?></td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <a href="edit_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-warning btn-sm me-1" title="កែប្រែ"><i class="bi bi-pencil"></i></a>
                                                    <a href="dashboard.php?delete=<?php echo $monk['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបព្រះសង្ឃនេះទេ?');" title="លុប"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table Card 4: បញ្ជីកុដិ -->
                <div class="col-12 mb-4" id="table-card-kuti">
                    <div class="table-card table-card-theme-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>បញ្ជីឈ្មោះកុដិសរុប</h4>
                            <div>
                                <a href="kuti.php?action=add" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> បន្ថែមកុដិថ្មី</a>
                                <a href="kuti.php" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                                
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>ឈ្មោះកុដិ</th>
                                        <th>ព្រះសង្ឃ</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($kuti_list_data)): ?>
                                        <tr><td colspan="4" class="text-center">មិនមានទិន្នន័យកុដិទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($kuti_list_data as $kuti): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('K%03d', $counter++); ?></td>
                                                <td data-label="ឈ្មោះកុដិ"><?php echo htmlspecialchars($kuti['kuti_name']); ?></td>
                                                <td data-label="ព្រះសង្ឃ" class="numeric-id"><?php echo $kuti['monk_count']; ?></td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <a href="kuti.php?action=edit&id=<?php echo $kuti['kuti_id']; ?>" class="btn btn-warning btn-sm me-1" title="កែប្រែ"><i class="bi bi-pencil"></i></a>
                                                    <a href="kuti.php?action=delete&id=<?php echo $kuti['kuti_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបកុដិនេះទេ?');" title="លុប"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table Card 5: បញ្ជីវត្តមានថ្មីៗ -->
                <div class="col-12 mb-4" id="table-card-attendance">
                    <div class="table-card table-card-theme-5">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>បញ្ជីវត្តមានរបស់ព្រះសង្ឃសរុប</h4>
                            <div>
                                <a href="attendance.php?action=add" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> បន្ថែមវត្តមានថ្មី</a>
                                <a href="attendance.php" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                        
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>ថ្ងៃខែ</th>
                                        <th>គោត្តនាម</th>
                                        <th>ស្ថានភាព</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_attendance_data)): ?>
                                        <tr><td colspan="5" class="text-center">មិនមានទិន្នន័យវត្តមានថ្មីៗទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($recent_attendance_data as $record): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('A%03d', $counter++); ?></td>
                                                <td data-label="ថ្ងៃខែ">
                                                    <?php
                                                    $attendance_date = htmlspecialchars($record['attendance_date']);
                                                    echo (!empty($attendance_date) && strtotime($attendance_date)) ? date('d/m/Y', strtotime($attendance_date)) : 'N/A';
                                                    ?>
                                                </td>
                                                <td data-label="គោត្តនាម"><?php echo htmlspecialchars($record['khmer_name']); ?></td>
                                                <td data-label="ស្ថានភាព"><?php echo htmlspecialchars($record['status']); ?></td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <a href="attendance.php?action=edit&id=<?php echo $record['attendance_id']; ?>" class="btn btn-warning btn-sm me-1" title="កែប្រែ"><i class="bi bi-pencil"></i></a>
                                                    <a href="attendance.php?action=delete&id=<?php echo $record['attendance_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបវត្តមាននេះទេ?');" title="លុប"><i class="bi bi-trash"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Table Card 6: កំណត់ហេតុប្រព័ន្ធ -->
                <div class="col-12 mb-4" id="table-card-logs">
                    <div class="table-card table-card-theme-6">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>កំណត់ហេតុប្រព័ន្ធទាំងមូល</h4>
                            <div>
                                <a href="logs.php" class="btn btn-info me-2"><i class="bi bi-eye"></i> មើលទាំងអស់</a>
                                
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>អត្តលេខ</th>
                                        <th>សកម្មភាព</th>
                                        <th>ពេលវេលា</th>
                                        <th>សកម្មភាព</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs_list)): ?>
                                        <tr><td colspan="4" class="text-center">មិនមានកំណត់ហេតុទេ។</td></tr>
                                    <?php else: ?>
                                        <?php $counter = 1; ?>
                                        <?php foreach ($logs_list as $log): ?>
                                            <tr>
                                                <td data-label="អត្តលេខ" class="numeric-id"><?php echo sprintf('L%03d', $counter++); ?></td>
                                                <td data-label="សកម្មភាព"><?php echo htmlspecialchars($log['action']); ?></td>
                                                <td data-label="ពេលវេលា">
                                                    <?php
                                                    $timestamp = htmlspecialchars($log['timestamp']);
                                                    echo (!empty($timestamp) && strtotime($timestamp)) ? date('d/m/Y H:i', strtotime($timestamp)) : 'N/A';
                                                    ?>
                                                </td>
                                                <td data-label="សកម្មភាព" class="text-center">
                                                    <!-- No edit/delete for logs in this context, just viewing -->
                                                    <a href="logs.php?action=view&id=<?php echo $log['id']; ?>" class="btn btn-info btn-sm" title="មើលលម្អិត"><i class="bi bi-info-circle"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($current_section == 'about'): ?>
            <div class="content-card" id="about-content-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>ទូទៅអំពីវត្ត</h4>
                    <div>
                    </div>
                </div>
                <div class="card-body">
                    <img src="Picture.jpg" onerror="this.onerror=null;this.src='https://placehold.co/800x400/D4AF37/7A1022?text=រូបភាពវត្ត';" alt="វត្តឥន្ទខីលារាម-ថ្មគោល" class="pagoda-image">
                    <div class="pagoda-history">
                        <p>វត្តឥន្ទខីលារាម ហៅវត្តថ្មគោល ជាវត្តមួយដ៏ចំណាស់ និងមានប្រវត្តិយូរលង់ណាស់មកហើយ។ វត្តនេះត្រូវបានកសាងឡើងនៅ… (បញ្ចូលឆ្នាំ ឬសម័យកាល)។ ទីតាំងស្ថិតនៅ… (បញ្ចូលទីតាំងលម្អិត)។</p>
                        <p>វត្តថ្មគោលដើរតួនាទីយ៉ាងសំខាន់ក្នុងការអភិវឌ្ឍន៍វិស័យពុទ្ធសាសនា និងសង្គមជាតិ។ វត្តនេះមិនត្រឹមតែជាទីសក្ការៈបូជាសម្រាប់ពុទ្ធបរិស័ទប៉ុណ្ណោះទេ ថែមទាំងជាមជ្ឈមណ្ឌលអប់រំ បណ្ដុះបណ្ដាលព្រះសង្ឃ និងសាមណេរ ព្រមទាំងចូលរួមចំណែកយ៉ាងសកម្មក្នុងកិច្ចការសប្បុរសធម៌នានា។</p>
                        <p>កន្លងមក វត្តបានឆ្លងកាត់ការជួសជុល និងកែលម្អជាច្រើនដំណាក់កាល ដោយមានការចូលរួមឧបត្ថម្ភពីសំណាក់ពុទ្ធបរិស័ទជិតឆ្ងាយ។ សព្វថ្ងៃ វត្តថ្មគោលមានព្រះសង្ឃគង់នៅជាច្រើនអង្គ ព្រមទាំងមានកុដិ សាលាឆាន់ វិហារ និងសំណង់ផ្សេងៗទៀតប្រកបដោយសោភ័ណភាព និងភាពរឹងមាំ។</p>
                        <p>យើងខ្ញុំសូមថ្លែងអំណរគុណយ៉ាងជ្រាលជ្រៅចំពោះសប្បុរសជនទាំងអស់ដែលបានរួមចំណែកក្នុងការទ្រទ្រង់ និងអភិវឌ្ឍន៍វត្តឲ្យមានភាពរីកចម្រើន។</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function printSpecificTable(cardId) {
            const cardToPrint = document.getElementById(cardId);
            if (!cardToPrint) {
                console.error("Print target card not found:", cardId);
                return;
            }

            // Remove 'print-active' from any previously active card
            document.querySelectorAll('.print-active').forEach(card => {
                card.classList.remove('print-active');
            });

            // Add 'print-active' class to the targeted card to apply specific print styles
            cardToPrint.classList.add('print-active');

            // Trigger the print dialog
            window.print();

            // Clean up: Remove the 'print-active' class after the print dialog is closed
            // using onafterprint for better reliability in different browsers/environments
            window.onafterprint = function() {
                cardToPrint.classList.remove('print-active');
                // Remove the event listener to avoid re-triggering if print is called again
                window.onafterprint = null; 
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle for mobile
            const sidebar = document.getElementById('sidebar');
            const toggleSidebarBtn = document.getElementById('toggleSidebar');

            if (toggleSidebarBtn) {
                toggleSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
