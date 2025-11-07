<?php
// logs.php
// This script displays all system logs in a formatted table.

// Include the new log helper file and the database configuration
require_once 'config.php';

// Ensure the user is logged in before proceeding
requireLogin();

// Array to hold the log data
$logs = [];
$message = '';
$message_type = '';

try {
    // Fetch all logs from the system_logs table, ordered by most recent first
    $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY timestamp DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching system logs: " . $e->getMessage());
    $message = "មានបញ្ហាក្នុងការទាញយកកំណត់ហេតុប្រព័ន្ធ។ សូមពិនិត្យមើល Table `system_logs` របស់អ្នក។<br><strong>កំហុស Database:</strong> " . htmlspecialchars($e->getMessage());
    $message_type = "danger";
}

?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>កំណត់ហេតុប្រព័ន្ធ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Khmer+OS+Muol&family=Khmer+OS+Siemreap&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-theme-gold: #D4AF37;
            --secondary-theme-red: #7A1022;
            --tertiary-dark-blue: #07004D;
            --light-bg-cream: #FAF5E0;
            --lighter-bg-pale: #FFF9E6;
            --text-dark-brown: #5C4033;
            --text-light-gray: #F1E7E7;
            --orange-icon-color: #FF8C00;
            --orange-gradient-start: #FFA500;
            --orange-gradient-end: #FFD700;
        }

        body {
            background: 
                linear-gradient(135deg, var(--lighter-bg-pale), var(--light-bg-cream)),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="none" width="100" height="100"/><path fill="%23D4AF37" opacity="0.03" d="M20,20 L20,80 L80,80 L80,20 Z M30,30 L30,70 L70,70 L70,30 Z"/></svg>');
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--text-dark-brown);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .logo-header {
            text-align: center;
            padding: 30px 0 20px 0;
            background: transparent;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(90, 58, 42, 0.3));
            margin-bottom: 15px;
        }

        .temple-name {
            font-family: 'Khmer OS Muol', sans-serif;
            font-size: 2.2rem;
            color: var(--secondary-theme-red);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            margin-bottom: 10px;
        }

        .temple-subtitle {
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 1.2rem;
            color: var(--text-dark-brown);
            margin-bottom: 20px;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }

        .nav-link {
            color: var(--text-dark-brown);
            text-decoration: none;
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 1.1rem;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-theme-gold), #E6C158);
            color: var(--secondary-theme-red);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .nav-link i {
            margin-right: 8px;
        }

        .nav-link:hover {
            background: linear-gradient(135deg, #E6C158, var(--primary-theme-gold));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.4);
            color: var(--secondary-theme-red);
        }

        .container {
            background: 
                linear-gradient(135deg, #FFFFFF, #F9F9F9),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><path fill="%23D4AF37" opacity="0.03" d="M40,10 C55,10 70,20 70,40 C70,60 55,70 40,70 C25,70 10,60 10,40 C10,20 25,10 40,10 Z"/></svg>');
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(212, 175, 55, 0.6), 
                        0 0 30px rgba(212, 175, 55, 0.4),
                        0 0 50px rgba(212, 175, 55, 0.2);
            margin: 20px auto 50px auto;
            border: 2px solid var(--primary-theme-gold);
            max-width: 1400px;
            width: 100%;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
            z-index: -1;
            animation: glow 8s infinite alternate;
        }

        @keyframes glow {
            0% { transform: scale(1); opacity: 0.6; }
            50% { transform: scale(1.02); opacity: 0.2; }
            100% { transform: scale(1); opacity: 0.6; }
        }

        h3 {
            font-family: 'Khmer OS Muol', sans-serif;
            color: var(--secondary-theme-red);
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            font-size: 2.5rem;
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            padding-bottom: 10px;
            text-align: center;
            position: relative;
        }

        h3::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-theme-gold), var(--secondary-theme-red));
            border-radius: 2px;
        }

        .card {
            background: 
                linear-gradient(135deg, #FFFFFF, #F9F9F9),
                url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 60 60"><rect fill="%23D4AF37" opacity="0.03" x="10" y="10" width="40" height="40" rx="5"/></svg>');
            border: 2px solid var(--primary-theme-gold);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-theme-gold), #E6C158);
            color: var(--secondary-theme-red);
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 20px;
            border-radius: 12px 12px 0 0;
            font-size: 1.6rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 30px;
        }

        .btn-secondary, .btn-print-all {
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 12px 25px;
            border-radius: 10px;
            font-size: 1.2rem;
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin-right: 15px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--tertiary-dark-blue), #0a0a5a);
            color: var(--text-light-gray);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #0a0a5a, var(--tertiary-dark-blue));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(7, 0, 77, 0.4);
        }

        .btn-print-all {
            background: linear-gradient(135deg, var(--primary-theme-gold), #E6C158);
            color: var(--secondary-theme-red);
        }

        .btn-print-all:hover {
            background: linear-gradient(135deg, #E6C158, var(--primary-theme-gold));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.4);
        }

        .btn-secondary::before, .btn-print-all::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .btn-secondary:hover::before, .btn-print-all:hover::before {
            left: 100%;
        }

        .btn i {
            margin-right: 8px;
            font-size: 1.1rem;
        }

        .table {
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--text-dark-brown);
            margin-bottom: 0;
            border-collapse: collapse;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .table thead {
            background: linear-gradient(135deg, var(--tertiary-dark-blue), #0a0a5a);
            color: var(--text-light-gray);
            font-family: 'Khmer OS Muol', sans-serif;
        }

        .table th, .table td {
            padding: 15px 20px;
            vertical-align: middle;
            border: 1px solid var(--primary-theme-gold);
            text-align: left;
        }

        .table th:first-child { border-top-left-radius: 8px; }
        .table th:last-child { border-top-right-radius: 8px; }

        .table tbody tr {
            background-color: #FFFFFF;
            transition: all 0.2s ease;
        }

        .table tbody tr:nth-child(even) {
            background-color: rgba(212, 175, 55, 0.05);
        }

        .table tbody tr:hover {
            background-color: rgba(212, 175, 55, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .alert {
            font-family: 'Khmer OS Siemreap', sans-serif;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(122, 16, 34, 0.1));
            border: 1px solid var(--primary-theme-gold);
            color: var(--text-dark-brown);
            position: relative;
            z-index: 1;
            padding: 15px 20px;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(180, 40, 55, 0.05));
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-header {
                padding: 20px 0 15px 0;
            }
            
            .logo {
                max-width: 140px;
            }
            
            .temple-name {
                font-size: 1.8rem;
            }
            
            .temple-subtitle {
                font-size: 1rem;
            }
            
            .nav-links {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .nav-link {
                width: 200px;
                justify-content: center;
            }
            
            .container {
                padding: 20px;
                margin: 15px auto 30px auto;
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
            
            .card-body {
                padding: 20px;
            }
            
            .btn-secondary, .btn-print-all {
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
            
            .table thead {
                display: none;
            }
            
            .table, .table tbody, .table tr, .table td {
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

            .logo-header, .btn-secondary, .btn-print-all, .alert {
                display: none !important;
                visibility: hidden !important;
            }

            .container {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                background: #fff !important;
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
                background: #f8f9fa !important;
                color: #000 !important;
                font-size: 1.2rem !important;
                padding: 10px 15px !important;
                border-radius: 5px 5px 0 0 !important;
                text-shadow: none !important;
                border-bottom: 1px solid #ccc !important;
                display: block !important;
            }

            .card-body {
                padding: 20px !important;
            }

            .table-responsive {
                overflow: visible !important;
            }
            
            .table {
                border-collapse: collapse !important;
                width: 100% !important;
                border: 1px solid #000 !important;
                color: #000 !important;
            }
            
            .table thead {
                background-color: #f0f0f0 !important;
                color: #000 !important;
                font-family: 'Khmer OS Muol', sans-serif !important;
            }
            
            .table th, .table td {
                border: 1px solid #000 !important;
                padding: 10px 15px !important;
                font-size: 10pt !important;
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
            
            .table td::before {
                content: none !important;
            }
            
            .table td {
                text-align: left !important;
                padding-left: 8px !important;
            }
        }
    </style>
</head>
<body>
    <!-- Centered Logo Header -->
    <div class="logo-header">
        <div class="logo-container">
            <img src="LOGO.png" alt="Wat Management System Logo" class="logo">
            <div class="temple-name">វត្តឥន្ទខីលារាម-ថ្មគោល</div>
            
            <div class="nav-links">
                <a href="dashboard.php" class="nav-link"><i class="bi bi-house-door-fill"></i> ផ្ទាំងគ្រប់គ្រង</a>
                <a href="monks.php" class="nav-link"><i class="bi bi-people-fill"></i> បញ្ជីព្រះសង្ឃ</a>
                
            </div>
        </div>
    </div>

    <div class="container">
        <h3><i class="bi bi-clock-history me-2"></i>កំណត់ហេតុប្រព័ន្ធ</h3>
        
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-print-all me-2" onclick="window.print()">
                <i class="bi bi-printer-fill"></i> បោះពុម្ភទាំងអស់
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> ត្រឡប់ក្រោយ
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- System Logs List -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-list-ul me-2"></i>បញ្ជីកំណត់ហេតុ</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash me-1"></i>ល.រ</th>
                                <th><i class="bi bi-clock me-1"></i>ពេលវេលា</th>
                                <th><i class="bi bi-person me-1"></i>ឈ្មោះអ្នកប្រើ</th>
                                <th><i class="bi bi-activity me-1"></i>សកម្មភាព</th>
                                <th><i class="bi bi-globe me-1"></i>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <i class="bi bi-info-circle me-2"></i>មិនមានកំណត់ហេតុប្រព័ន្ធនៅឡើយទេ។
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $counter = 1; ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td data-label="ល.រ" class="numeric-id"><?php echo $counter++; ?></td>
                                        <td data-label="ពេលវេលა">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['timestamp']))); ?>
                                        </td>
                                        <td data-label="ឈ្មោះអ្នកប្រើ">
                                            <i class="bi bi-person-circle me-1"></i>
                                            <?php echo htmlspecialchars($log['username']); ?>
                                        </td>
                                        <td data-label="សកម្មភាព">
                                            <i class="bi bi-journal-text me-1"></i>
                                            <?php echo htmlspecialchars($log['action_description']); ?>
                                        </td>
                                        <td data-label="IP Address">
                                            <i class="bi bi-pc-display me-1"></i>
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
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