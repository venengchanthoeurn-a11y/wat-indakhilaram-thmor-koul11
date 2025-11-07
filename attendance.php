<?php
// --- START DUMMY config.php and requireLogin() ---
// This section is for demonstration purposes to make the code runnable.
// In your actual application, you should have a separate config.php file
// and your own authentication logic.

session_start(); // Start the session if it's not already started in your actual config.php

// Database connection parameters - PLEASE REPLACE THESE WITH YOUR ACTUAL DATABASE DETAILS
$dbHost = 'localhost'; // Your database host
$dbName = 'thmako_system'; // Your actual database name, provided by the user
$dbUser = 'root'; // Your actual database username
$dbPass = ''; // Your actual database password (often empty for local root)

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Crucial for catching PDO errors
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a production environment, avoid exposing detailed error messages to users.
    // Log the error (e.g., to a file) and show a user-friendly message.
    error_log("Database connection failed: " . $e->getMessage()); // Log the error
    die("Could not connect to the database. Please check your database server, credentials in config.php, and database schema.<br>Error: " . $e->getMessage());
}

// Dummy requireLogin function for demonstration
// In a real application, this function would verify user authentication.
// It should set $_SESSION['user_id'] upon successful login.
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        // For demonstration, set a default user ID if not logged in.
        // This simulates a logged-in user for testing attendance recording.
        // In a real application, you would typically redirect to a login page or handle authentication.
        $_SESSION['user_id'] = 1; // Default user ID for testing. Adjust as needed or remove in production.
        $_SESSION['username'] = 'TestUser'; // Default username for testing
    }
}
// --- END DUMMY config.php and requireLogin() ---


requireLogin(); // Ensure user is logged in (using the dummy function for now)

$message = '';
$message_type = '';
$edit_record = null;

// Handle form submission for adding/editing attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monk_id = filter_input(INPUT_POST, 'monk_id', FILTER_VALIDATE_INT);
    $attendance_date = $_POST['attendance_date'] ?? '';
    $status = $_POST['status'] ?? '';
    $attendance_id = filter_input(INPUT_POST, 'attendance_id', FILTER_VALIDATE_INT);

    // Validate inputs
    if ($monk_id === false || empty($attendance_date) || empty($status)) {
        $message = "សូមបំពេញព័ត៌មានទាំងអស់ឲ្យបានត្រឹមត្រូវ។";
        $message_type = "danger";
    } else {
        try {
            if ($attendance_id) { // Edit existing record
                // Removed 'recorded_by' from UPDATE query as it caused "Column not found" error
                $sql = "UPDATE attendance SET monk_id = ?, attendance_date = ?, status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$monk_id, $attendance_date, $status, $attendance_id]);
                $message = "កែប្រែវត្តមានដោយជោគជ័យ!";
                $message_type = "success";
            } else { // Add new record
                // Check for duplicate attendance for the same monk on the same date
                $check_sql = "SELECT COUNT(*) FROM attendance WHERE monk_id = ? AND attendance_date = ?";
                $check_stmt = $pdo->prepare($check_sql);
                $check_stmt->execute([$monk_id, $attendance_date]);
                if ($check_stmt->fetchColumn() > 0) {
                    $message = "វត្តមានសម្រាប់ព្រះសង្ឃនេះក្នុងថ្ងៃនេះមាននៅហើយ។";
                    $message_type = "warning"; // Changed to warning for duplicate
                } else {
                    // Removed 'recorded_by' from INSERT query as it caused "Column not found" error
                    // If you intend to add a 'recorded_by' column later, you'll need to add it to your DB schema first.
                    $sql = "INSERT INTO attendance (monk_id, attendance_date, status) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$monk_id, $attendance_date, $status]);
                    $message = "បន្ថែមវត្តមានដោយជោគជ័យ!";
                    $message_type = "success";
                }
            }
        } catch (PDOException $e) {
            // Provide more specific error details from the database exception
            error_log("Attendance form submission PDO error: " . $e->getMessage());
            $message = "មានបញ្ហាក្នុងការបង្កើត/កែប្រែវត្តមាន។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage()) . "<br>សូមពិនិត្យមើលថា Table `attendance` និង Columns (`monk_id`, `attendance_date`, `status`) របស់អ្នកត្រឹមត្រូវ។";
            $message_type = "danger";
        }
    }
    
    // After submission, if successful, clear the form or refresh edit data
    if ($message_type === "success" && $attendance_id) {
        // If editing was successful, re-fetch the edited record to update the form
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
        $stmt->execute([$attendance_id]);
        $edit_record = $stmt->fetch();
    } else if ($message_type === "success" && !$attendance_id) {
        // If adding was successful, clear the form
        $edit_record = null;
    }
    // No explicit redirect after POST to allow message display
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            $message = "លុបវត្តមានដោយជោគជ័យ!";
            $message_type = "success";
            $edit_record = null; // Clear edit record after deletion
        } catch (PDOException $e) {
            error_log("Delete attendance PDO error: " . $e->getMessage());
            $message = "មានបញ្ហាក្នុងការលុបវត្តមាន។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Fetch data for editing (if 'edit' parameter is present and not already processed by POST)
if (isset($_GET['edit']) && !$edit_record) { // Check if $edit_record is null to avoid overwriting post-submit state
    $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
    if ($id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE id = ?");
            $stmt->execute([$id]);
            $edit_record = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Fetch edit record PDO error: " . $e->getMessage());
            $message = "មានបញ្ហាក្នុងការទាញយកទិន្នន័យដើម្បីកែប្រែ។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
            $message_type = "danger";
        }
    }
}

// Fetch monks for dropdown (assuming 'monks' table exists with 'id' and 'khmer_name')
try {
    $monks = $pdo->query("SELECT id, khmer_name FROM monks ORDER BY khmer_name")->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching monks: " . $e->getMessage());
    $monks = []; // Ensure $monks is an array even if query fails
    // Display error message to user, but don't halt script
    $message = (empty($message) ? "" : $message . "<br>") . "មានបញ្ហាក្នុងការទាញយកបញ្ជីព្រះសង្ឃ។ សូមពិនិត្យមើល Table `monks` របស់អ្នក។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
    $message_type = (empty($message_type) || $message_type === "success") ? "danger" : $message_type;
}


// Fetch attendance records for display
try {
    // It's better to fetch ID from attendance table to use for unique A000 formatting
    $attendance_records = $pdo->query("
        SELECT a.id, a.attendance_date, a.status, m.khmer_name 
        FROM attendance a 
        JOIN monks m ON a.monk_id = m.id 
        ORDER BY a.attendance_date DESC, m.khmer_name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching attendance records: " . $e->getMessage());
    $attendance_records = []; // Ensure $attendance_records is an array even if query fails
    // Display error message to user
    $message = (empty($message) ? "" : $message . "<br>") . "មានបញ្ហាក្នុងការទាញយកកំណត់ត្រាវត្តមាន។ សូមពិនិត្យមើល Table `attendance` និង `monks` របស់អ្នក។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
    $message_type = (empty($message_type) || $message_type === "success") ? "danger" : $message_type;
}

// Fetch attendance summary
$attendance_summary = [];
try {
    // Get all monks first to ensure even monks with no attendance records are listed
    $all_monks_for_summary = $pdo->query("SELECT id, khmer_name FROM monks ORDER BY khmer_name")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

    // Group attendance by monk and status
    $raw_summary_data = $pdo->query("
        SELECT 
            m.id as monk_id, 
            m.khmer_name, 
            a.status, 
            GROUP_CONCAT(a.attendance_date ORDER BY a.attendance_date ASC) as dates,
            COUNT(a.id) as count
        FROM attendance a
        JOIN monks m ON a.monk_id = m.id
        GROUP BY m.id, m.khmer_name, a.status
    ")->fetchAll();

    // Initialize summary for all monks
    foreach ($all_monks_for_summary as $monkId => $monkDetails) {
        $attendance_summary[$monkId] = [
            'khmer_name' => $monkDetails[0]['khmer_name'], // Since it's GROUP|FETCH_ASSOC, it's an array of rows
            'present_count' => 0,
            'absent_count' => 0,
            'leave_count' => 0,
            'present_dates' => [],
            'absent_dates' => [],
            'leave_dates' => []
        ];
    }

    // Populate summary with attendance data
    foreach ($raw_summary_data as $row) {
        $monkId = $row['monk_id'];
        
        // Ensure monk is already in summary (should be if fetched all monks)
        if (isset($attendance_summary[$monkId])) {
            if ($row['status'] === 'វត្តមាន') {
                $attendance_summary[$monkId]['present_count'] = $row['count'];
                if (!empty($row['dates'])) {
                    $attendance_summary[$monkId]['present_dates'] = array_map(function($date) {
                        return date('d/m/Y', strtotime($date));
                    }, explode(',', $row['dates']));
                }
            } elseif ($row['status'] === 'អវត្តមាន') {
                $attendance_summary[$monkId]['absent_count'] = $row['count'];
                if (!empty($row['dates'])) {
                    $attendance_summary[$monkId]['absent_dates'] = array_map(function($date) {
                        return date('d/m/Y', strtotime($date));
                    }, explode(',', $row['dates']));
                }
            } elseif ($row['status'] === 'ច្បាប់') {
                $attendance_summary[$monkId]['leave_count'] = $row['count'];
                if (!empty($row['dates'])) {
                    $attendance_summary[$monkId]['leave_dates'] = array_map(function($date) {
                        return date('d/m/Y', strtotime($date));
                    }, explode(',', $row['dates']));
                }
            }
        }
    }
    // Sort the summary by monk name for consistent display
    usort($attendance_summary, function($a, $b) {
        return strcmp($a['khmer_name'], $b['khmer_name']);
    });

} catch (PDOException $e) {
    error_log("Error fetching attendance summary: " . $e->getMessage());
    $attendance_summary = [];
    $message = (empty($message) ? "" : $message . "<br>") . "មានបញ្ហាក្នុងការទាញយកតារាងសរុបវត្តមាន។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
    $message_type = (empty($message_type) || $message_type === "success") ? "danger" : $message_type;
}


?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>គ្រប់គ្រងវត្តមាន</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Khmer+OS+Muol&family=Khmer+OS+Siemreap&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-orange: #FF8C00; /* Darker Orange */
            --light-orange: #FFA500; /* Medium Orange */
            --lighter-orange: #FFD700; /* Gold/Yellow Orange */
            --dark-brown: #5A3A2A;
            --cream-bg: #FFFDF8;
            --light-cream-bg: #FFF5E6;
            --lighter-cream-bg: #FFE0B2;
            --text-light: #F1E7E7;
            --print-border-gold: #A07C00; /* A darker gold for print borders */
        }

        /* General Body Styles */
        body {
            background: linear-gradient(135deg, #f8f4e9, #e8d9c5);
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--dark-brown);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100" opacity="0.03"><path d="M30,30 Q50,10 70,30 T90,50 T70,70 T50,90 T30,70 T10,50 T30,30 Z" fill="none" stroke="%235A3A2A" stroke-width="1"/></svg>');
            background-size: 200px 200px;
            pointer-events: none;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
            padding-top: 20px;
        }

        .logo {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(90, 58, 42, 0.3));
        }

        .container {
            background-color: var(--cream-bg);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(255, 165, 0, 0.2), 0 0 30px rgba(255, 140, 0, 0.3);
            margin: 50px auto;
            border: 2px solid var(--light-orange);
            max-width: 1400px; /* Increased max-width */
        }

        h3 {
            font-family: 'Khmer OS Muol', sans-serif;
            color: var(--primary-orange);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            font-size: 2.5rem;
            border-bottom: 2px solid rgba(255, 165, 0, 0.3);
            padding-bottom: 10px;
            text-align: center;
        }

        .card {
            background-color: #FFFFFF;
            border: 2px solid var(--light-orange);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            color: var(--dark-brown);
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            font-size: 1.6rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }

        .card-body {
            padding: 40px; /* Increased padding */
        }

        .form-label {
            font-family: 'Khmer OS Muol', sans-serif;
            color: var(--dark-brown);
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            border: 1px solid var(--light-orange);
            border-radius: 10px;
            padding: 15px 20px; /* Increased padding */
            font-size: 1.1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FF4500;
            box-shadow: 0 0 0 0.25rem rgba(255, 69, 0, 0.25);
            outline: none;
        }

        /* Input Group with Icons */
        .input-group-icon {
            position: relative;
        }

        .input-group-icon .form-control,
        .input-group-icon .form-select {
            padding-left: 50px;
        }

        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-orange);
            z-index: 2;
            font-size: 1.3rem;
        }

        .btn-primary, .btn-secondary, .btn-print-all { 
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 30px; /* Increased padding */
            border-radius: 10px;
            font-size: 1.3rem; /* Increased font size */
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin-right: 15px;
            white-space: nowrap; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            border-color: var(--primary-orange);
            color: var(--dark-brown);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--light-orange), #FF4500);
            border-color: #FF4500;
            color: var(--dark-brown);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 165, 0, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #8B4513, #6B2A14);
            border-color: #6B2A14;
            color: var(--text-light);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #6B2A14, #5A3A2A);
            border-color: #5A3A2A;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(139, 69, 19, 0.3);
        }
        .btn-print-all { 
            background-color: #17A2B8; 
            border-color: #17A2B8;
            color: #FFFFFF;
        }
        .btn-print-all:hover {
            background-color: #138496;
            border-color: #138496;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* Enhanced Table Styles for Better Responsiveness */
        .table {
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--dark-brown);
            margin-bottom: 0;
            border-collapse: collapse; 
            width: 100%; 
            table-layout: auto; /* Changed to auto for better column sizing */
        }
        
        .table thead {
            background-color: var(--primary-orange);
            color: var(--cream-bg);
            font-family: 'Khmer OS Muol', sans-serif;
        }
        
        .table th, .table td {
            padding: 15px 20px; /* Increased padding */
            vertical-align: middle;
            border: 1px solid rgba(255, 165, 0, 0.5); 
            text-align: left; 
            word-wrap: break-word; /* Ensure long text breaks properly */
        }
        
        /* Specific column width adjustments for summary table */
        .table-summary th:nth-child(1), 
        .table-summary td:nth-child(1) { /* Serial number column */
            width: 5%;
            min-width: 50px;
        }
        
        .table-summary th:nth-child(2), 
        .table-summary td:nth-child(2) { /* Name column - made wider */
            width: 20%;
            min-width: 200px;
            max-width: 300px;
        }
        
        .table-summary th:nth-child(3), 
        .table-summary td:nth-child(3),
        .table-summary th:nth-child(4), 
        .table-summary td:nth-child(4),
        .table-summary th:nth-child(5), 
        .table-summary td:nth-child(5) { /* Count columns */
            width: 8%;
            min-width: 80px;
        }
        
        .table-summary th:nth-child(6), 
        .table-summary td:nth-child(6),
        .table-summary th:nth-child(7), 
        .table-summary td:nth-child(7),
        .table-summary th:nth-child(8), 
        .table-summary td:nth-child(8) { /* Date columns */
            width: 15%;
            min-width: 150px;
        }

        .table th:first-child { border-top-left-radius: 8px; }
        .table th:last-child { border-top-right-radius: 8px; }

        .table tbody tr {
            background-color: var(--cream-bg);
            transition: all 0.2s ease;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 165, 0, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .table .btn-sm {
            padding: 8px 12px;
            font-size: 1rem;
            border-radius: 6px;
            margin-right: 8px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .table .btn-warning {
            background-color: #FFC107;
            border-color: #FFC107;
            color: var(--dark-brown);
        }
        .table .btn-warning:hover {
            background-color: #FFB300;
            border-color: #FFB300;
        }
        .table .btn-danger {
            background-color: #DC3545;
            border-color: #DC3545;
            color: var(--text-light);
        }
        .table .btn-danger:hover {
            background-color: #C82333;
            border-color: #C82333;
        }

        .alert {
            font-family: 'Khmer OS Siemreap', sans-serif;
            border-radius: 10px;
            padding: 15px 20px;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background-color: rgba(0, 100, 0, 0.1);
            border: 1px solid #006400;
            color: #004d00;
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid #dc3545;
            color: #b02a37;
        }
        .alert-warning { 
            background-color: rgba(255, 193, 7, 0.1);
            border: 1px solid #ffc107;
            color: #cc9a00;
        }
        .alert .btn-close {
            background-color: transparent;
            color: var(--dark-brown);
            opacity: 0.7;
            font-size: 1.2rem;
            transition: opacity 0.2s ease;
        }
        .alert .btn-close:hover {
            opacity: 1;
            color: var(--primary-orange);
        }

        /* Styling for the dates list in summary table */
        .date-list {
            font-size: 0.85em; 
            line-height: 1.4;
            color: #6A4B3A; 
            max-height: 80px; 
            overflow-y: auto; 
            padding-right: 5px; 
        }
        .date-list::-webkit-scrollbar {
            width: 6px;
        }
        .date-list::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .date-list::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .date-list::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Enhanced Responsive adjustments */
        @media (max-width: 1200px) {
            .container {
                padding: 30px;
                margin: 30px auto;
            }
            
            .table-summary th:nth-child(2), 
            .table-summary td:nth-child(2) { /* Name column adjustment for medium screens */
                width: 25%;
                min-width: 180px;
            }
        }

        @media (max-width: 992px) {
            .container {
                padding: 20px;
                margin: 20px auto;
            }
            
            .table-summary th:nth-child(2), 
            .table-summary td:nth-child(2) { /* Name column adjustment for smaller screens */
                width: 30%;
                min-width: 160px;
            }
            
            .date-list {
                max-height: 60px;
                font-size: 0.8em;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 15px auto;
                max-width: 100%;
            }
            h3 {
                font-size: 2rem;
                margin-bottom: 20px;
            }
            .card-header {
                font-size: 1.3rem;
                padding: 10px 15px;
                flex-direction: column; 
                align-items: flex-start;
            }
            .card-header .btn-print-all { 
                margin-top: 10px; 
                margin-left: 0; 
                width: 100%; 
            }
            .card-body {
                padding: 20px;
            }
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 1rem;
            }
            .input-group-icon .form-control,
            .input-group-icon .form-select {
                padding-left: 45px;
            }
            .btn-primary, .btn-secondary, .btn-print-all { 
                padding: 10px 20px;
                font-size: 1.1rem;
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }
            .table th, .table td {
                padding: 10px;
                font-size: 0.95rem;
            }
            .table .btn-sm {
                padding: 6px 10px;
                font-size: 0.9rem;
            }
            
            /* Enhanced mobile table styles */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table thead {
                display: none; 
            }
            
            .table, .table tbody, .table tr, .table td {
                display: block; 
                width: 100%;
            }
            
            .table tr {
                margin-bottom: 15px;
                border: 1px solid rgba(255, 165, 0, 0.5); 
                border-radius: 8px;
                padding: 10px;
                position: relative;
            }
            
            .table td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none; 
                border-bottom: 1px solid rgba(255, 165, 0, 0.2);
            }
            
            .table td:last-child {
                border-bottom: none;
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
                color: var(--primary-orange);
                font-family: 'Khmer OS Muol', sans-serif;
            }
            
            .table td:last-child {
                text-align: center;
                padding-left: 10px; 
            }
            
            .table td:last-child::before {
                content: none; 
            }
            
            .date-list {
                max-height: 60px; 
                text-align: left !important;
            }
            
            .date-list::before {
                content: none !important;
            }
        }

        @media (max-width: 576px) {
            .table-summary th:nth-child(2), 
            .table-summary td:nth-child(2) { /* Name column adjustment for extra small screens */
                width: 35%;
                min-width: 140px;
            }
            
            .table td {
                padding-left: 45%;
            }
            
            .table td::before {
                width: calc(45% - 15px);
            }
        }

        /* Print-specific styles */
        @media print {
            @page {
                size: A4 landscape; 
                margin: 1cm; 
            }

            body {
                margin: 0;
                padding: 0;
                color: #000; 
                font-family: 'Khmer OS Siemreap', sans-serif;
                background-color: #fff !important; 
            }

            /* Hide elements not needed for printing */
            .btn-print-all, 
            .alert,
            .btn-secondary, 
            .table .btn-sm,
            .d-flex.justify-content-end.mt-4 .btn-primary, /* Hide primary button in print */
            .d-flex.justify-content-end.mt-4 .btn-secondary /* Hide secondary button in print */
            {
                display: none !important;
                visibility: hidden !important;
            }

            /* Ensure the main container and all its cards are visible and well-laid out */
            .container {
                padding: 0 !important; 
                margin: 0 !important; 
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important; 
                border: none !important; 
            }

            h3 { 
                font-size: 1.8rem !important;
                margin-bottom: 20px !important;
                padding-bottom: 5px !important;
                border-bottom: 1px solid #ccc !important;
                color: #000 !important;
                text-shadow: none !important;
                text-align: center !important;
            }

            .card {
                margin-bottom: 20px !important; 
                box-shadow: none !important; 
                border: 1px solid #ddd !important; 
                border-radius: 5px !important; 
                background-color: #fff !important; 
                overflow: visible !important; 
                page-break-inside: avoid; 
            }

            .card-header {
                background: linear-gradient(135deg, #FFEFD5, #FAEBD7) !important; 
                color: #5A3A2A !important;
                font-size: 1.2rem !important;
                padding: 10px 15px !important;
                border-radius: 5px 5px 0 0 !important;
                text-shadow: none !important; 
                border-bottom: 1px solid #ccc !important;
                display: block !important; 
                justify-content: flex-start !important;
            }
            .card-header h5 { 
                color: #5A3A2A !important; 
            }

            .card-body {
                padding: 20px !important; /* Increased padding for print */
            }

            /* Form specific print styles */
            .card form {
                display: block !important;
            }
            .card form .row.g-3 {
                display: flex !important; 
                flex-wrap: wrap !important;
                margin: 0 !important;
            }
            .card form .col-md-4 {
                flex: 0 0 33.333333% !important; 
                max-width: 33.333333% !important;
                padding: 5px !important;
            }
            .card form .form-label {
                font-size: 0.9em !important;
                font-weight: bold !important;
                color: #000 !important;
                margin-bottom: 2px !important;
            }
            .card form .form-control, .card form .form-select {
                border: 1px solid #ccc !important;
                padding: 4px 8px !important;
                font-size: 0.9em !important;
                height: auto !important;
                min-height: 25px !important; 
                background-color: #f9f9f9 !important; 
                color: #000 !important;
            }
            .card form .d-flex.justify-content-end.mt-4 {
                display: flex !important; /* Make sure the container for form actions is visible */
                justify-content: flex-start !important; /* Adjust alignment for print */
                margin-top: 15px !important; /* Add some space */
            }
             .card form .d-flex.justify-content-end.mt-4 > * { /* Hide individual buttons in the form's action area */
                display: none !important;
            }


            /* Table print styles (apply to all tables) */
            .table-responsive {
                overflow: visible !important; 
            }
            .table {
                border-collapse: collapse !important;
                width: 100% !important;
                border: 1px solid var(--print-border-gold) !important; 
                color: #000 !important;
                table-layout: auto !important;
            }
            .table thead {
                background-color: #FFEFD5 !important; 
                color: #5A3A2A !important;
                font-family: 'Khmer OS Muol', sans-serif !important;
            }
            .table th, .table td {
                border: 1px solid var(--print-border-gold) !important; 
                padding: 10px 15px !important; /* Increased padding for print */
                font-size: 10pt !important; /* Increased font size for print */
                vertical-align: top !important; 
                background-color: #fff !important; 
                color: #000 !important;
                text-align: left !important;
            }
            .table tbody tr {
                background-color: #fff !important; 
            }
            .table tbody tr:hover {
                background-color: #fff !important; 
            }

            /* Print-specific column adjustments */
            .table-summary th:nth-child(2), 
            .table-summary td:nth-child(2) { /* Name column in print */
                width: 25% !important;
            }

            /* Responsive table adjustments for print (ensure content doesn't get squished) */
            .table td::before {
                content: none !important; 
            }
            .table td {
                text-align: left !important;
                padding-left: 8px !important; 
            }

            /* Date list in summary table */
            .date-list {
                max-height: none !important; 
                overflow: visible !important; 
                color: #000 !important; 
                font-size: 9pt !important; 
            }
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="LOGO.png" alt="Wat Management System Logo" class="logo">
    </div>

<div class="container">
    <h3><i class="bi bi-calendar-check me-2"></i>គ្រប់គ្រងវត្តមាន</h3>
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
            <i class="bi <?php 
                if($message_type == 'success') echo 'bi-check-circle-fill';
                elseif($message_type == 'danger') echo 'bi-exclamation-triangle-fill';
                else echo 'bi-info-circle-fill';
            ?>"></i>
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Form -->
    <div class="card mb-4" id="add-edit-attendance-card">
        <div class="card-header">
            <h5><i class="bi <?php echo $edit_record ? 'bi-pencil-square' : 'bi-plus-circle'; ?> me-2"></i><?php echo $edit_record ? 'កែប្រែវត្តមាន' : 'បន្ថែមវត្តមានថ្មី'; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="attendance_id" value="<?php echo $edit_record['id'] ?? ''; ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="monk_id" class="form-label"><i class="bi bi-person"></i>ព្រះសង្ឃ</label>
                        <div class="input-group-icon">
                            <i class="bi bi-person-vcard"></i>
                            <select class="form-select" name="monk_id" required>
                                <option value="">-- ជ្រើសរើស --</option>
                                <?php if (!empty($monks)): ?>
                                    <?php foreach ($monks as $monk): ?>
                                        <option value="<?php echo $monk['id']; ?>" <?php if (isset($edit_record['monk_id']) && $edit_record['monk_id'] == $monk['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($monk['khmer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>មិនមានព្រះសង្ឃទេ (សូមបន្ថែមព្រះសង្ឃជាមុនសិន)</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="attendance_date" class="form-label"><i class="bi bi-calendar-date"></i>កាលបរិច្ឆេទ</label>
                        <div class="input-group-icon">
                            <i class="bi bi-calendar3"></i>
                            <input type="date" class="form-control" name="attendance_date" value="<?php echo $edit_record['attendance_date'] ?? date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label"><i class="bi bi-info-circle"></i>ស្ថានភាព</label>
                        <div class="input-group-icon">
                            <i class="bi bi-check-circle"></i>
                            <select class="form-select" name="status" required>
                                <option value="វត្តមាន" <?php if (isset($edit_record['status']) && $edit_record['status'] == 'វត្តមាន') echo 'selected'; ?>>វត្តមាន</option>
                                <option value="អវត្តមាន" <?php if (isset($edit_record['status']) && $edit_record['status'] == 'អវត្តមាន') echo 'selected'; ?>>អវត្តមាន</option>
                                <option value="ច្បាប់" <?php if (isset($edit_record['status']) && $edit_record['status'] == 'ច្បាប់') echo 'selected'; ?>>ច្បាប់</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi <?php echo $edit_record ? 'bi-check-lg' : 'bi-plus-lg'; ?>"></i>
                        <?php echo $edit_record ? 'រក្សាទុកការផ្លាស់ប្តូរ' : 'បន្ថែមវត្តមាន'; ?>
                    </button>
                    <button type="button" class="btn btn-print-all me-2" onclick="window.print()">
                        <i class="bi bi-printer-fill"></i> បោះពុម្ភទាំងអស់
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i>ត្រឡប់ក្រោយ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance List -->
    <div class="card mb-4" id="detailed-attendance-list-card">
        <div class="card-header">
            <h5><i class="bi bi-list-check me-2"></i>បញ្ជីវត្តមាន</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th data-label="អត្តលេខ"><i class="bi bi-hash me-1"></i>អត្តលេខ</th>
                            <th data-label="កាលបរិច្ឆេទ"><i class="bi bi-calendar-event me-1"></i>កាលបរិច្ឆេទ</th>
                            <th data-label="គោត្តនាម"><i class="bi bi-person-vcard me-1"></i>គោត្តនាម</th>
                            <th data-label="ស្ថានភាព"><i class="bi bi-info-circle me-1"></i>ស្ថានភាព</th>
                            <th class="text-end" data-label="សកម្មភាព"><i class="bi bi-gear me-1"></i>សកម្មភាព</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_records)): ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <i class="bi bi-inbox me-2"></i>មិនមានទិន្នន័យវត្តមាននៅឡើយទេ។
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td data-label="អត្តលេខ">
                                        <i class="bi bi-person-badge me-1"></i><?php echo sprintf('A%03d', $counter++); ?>
                                    </td>
                                    <td data-label="កាលបរិច្ឆេទ">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php
                                        $attendance_date = htmlspecialchars($record['attendance_date']);
                                        echo (!empty($attendance_date) && strtotime($attendance_date)) ? date('d/m/Y', strtotime($attendance_date)) : 'N/A';
                                        ?>
                                    </td>
                                    <td data-label="គោត្តនាម">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($record['khmer_name']); ?>
                                    </td>
                                    <td data-label="ស្ថានភាព">
                                        <?php 
                                        $status_icon = '';
                                        if ($record['status'] == 'វត្តមាន') $status_icon = 'bi-check-circle-fill text-success';
                                        elseif ($record['status'] == 'អវត្តមាន') $status_icon = 'bi-x-circle-fill text-danger';
                                        else $status_icon = 'bi-clock-fill text-warning';
                                        ?>
                                        <i class="bi <?php echo $status_icon; ?> me-1"></i><?php echo htmlspecialchars($record['status']); ?>
                                    </td>
                                    <td data-label="សកម្មភាព" class="text-end">
                                        <a href="attendance.php?edit=<?php echo $record['id']; ?>" class="btn btn-sm btn-warning" title="កែប្រែ">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="attendance.php?delete=<?php echo $record['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបមែនទេ?');" title="លុប">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Attendance Summary Table -->
    <div class="card" id="attendance-summary-card">
        <div class="card-header">
            <h5><i class="bi bi-bar-chart me-2"></i>តារាងសរុបវត្តមាន</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-summary">
                    <thead>
                        <tr>
                            <th data-label="ល.រ"><i class="bi bi-hash me-1"></i></th>
                            <th data-label="គោត្តនាម"><i class="bi bi-person-vcard me-1"></i>គោត្តនាម</th>
                            <th data-label="វត្តមាន"><i class="bi bi-check-circle me-1"></i>វត្តមាន</th>
                            <th data-label="អវត្តមាន"><i class="bi bi-x-circle me-1"></i>អវត្តមាន</th>
                            <th data-label="ច្បាប់"><i class="bi bi-clock me-1"></i>ច្បាប់</th>
                            <th data-label="កាលបរិច្ឆេទវត្តមាន"><i class="bi bi-calendar-check me-1"></i>កាលបរិច្ឆេទវត្តមាន</th>
                            <th data-label="កាលបរិច្ឆេទអវត្តមាន"><i class="bi bi-calendar-x me-1"></i>កាលបរិច្ឆេទអវត្តមាន</th>
                            <th data-label="កាលបរិច្ឆេទច្បាប់"><i class="bi bi-calendar-week me-1"></i>កាលបរិច្ឆេទច្បាប់</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_summary)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <i class="bi bi-inbox me-2"></i>មិនមានទិន្នន័យសរុបវត្តមានសម្រាប់បង្ហាញទេ។
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($attendance_summary as $monk_summary): ?>
                                <tr>
                                    <td data-label="ល.រ"><?php echo $counter++; ?></td>
                                    <td data-label="គោត្តនាម">
                                        <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($monk_summary['khmer_name']); ?>
                                    </td>
                                    <td data-label="វត្តមាន (ដង)">
                                        <i class="bi bi-check-circle-fill text-success me-1"></i><?php echo $monk_summary['present_count']; ?>
                                    </td>
                                    <td data-label="អវត្តមាន (ដង)">
                                        <i class="bi bi-x-circle-fill text-danger me-1"></i><?php echo $monk_summary['absent_count']; ?>
                                    </td>
                                    <td data-label="ច្បាប់ (ដង)">
                                        <i class="bi bi-clock-fill text-warning me-1"></i><?php echo $monk_summary['leave_count']; ?>
                                    </td>
                                    <td data-label="កាលបរិច្ឆេទវត្តមាន" class="date-list">
                                        <?php echo !empty($monk_summary['present_dates']) ? implode(', ', $monk_summary['present_dates']) : 'N/A'; ?>
                                    </td>
                                    <td data-label="កាលបរិច្ឆេទអវត្តមាន" class="date-list">
                                        <?php echo !empty($monk_summary['absent_dates']) ? implode(', ', $monk_summary['absent_dates']) : 'N/A'; ?>
                                    </td>
                                    <td data-label="កាលបរិច្ឆេទច្បាប់" class="date-list">
                                        <?php echo !empty($monk_summary['leave_dates']) ? implode(', ', $monk_summary['leave_dates']) : 'N/A'; ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>