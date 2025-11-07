<?php
include 'config.php';
requireLogin();

// Logging function
function logAction($action, $details = '') {
    global $pdo;
    $user_id = $_SESSION['user_id'] ?? 0; // Default to 0 if not set
    try {
        $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $action, $details]);
    } catch (PDOException $e) {
        error_log("Logging error: " . $e->getMessage());
    }
}

// Fetch all monks with their kuti name, sorted by ID ascending
$stmt = $pdo->query("
    SELECT m.*, k.kuti_name 
    FROM monks m 
    LEFT JOIN kuti k ON m.kuti_id = k.id 
    ORDER BY m.id ASC
");
$monks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បញ្ជីព្រះសង្ឃវត្តថ្មគោល</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            max-width: 1400px;
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
            padding: 40px;
        }

        .btn-primary, .btn-secondary, .btn-print-all { 
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.3rem;
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin-right: 15px;
            white-space: nowrap; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            color: var(--dark-brown);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--light-orange), #FF4500);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 165, 0, 0.3);
            color: var(--dark-brown);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #8B4513, #6B2A14);
            color: var(--text-light);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #6B2A14, #5A3A2A);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(139, 69, 19, 0.3);
        }
        
        .btn-print-all { 
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: #FFFFFF;
        }
        .btn-print-all:hover {
            background: linear-gradient(135deg, #138496, #5a2d9c);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(23, 162, 184, 0.4);
            color: white;
        }

        /* Enhanced Table Styles */
        .table {
            font-family: 'Khmer OS Siemreap', sans-serif;
            color: var(--dark-brown);
            margin-bottom: 0;
            border-collapse: collapse; 
            width: 100%; 
            table-layout: auto;
        }
        
        .table thead {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: #ffffff;
            font-family: 'Khmer OS Muol', sans-serif;
            font-size: 1.1rem;
        }
        
        .table th, .table td {
            padding: 15px 20px;
            vertical-align: middle;
            border: 1px solid rgba(255, 165, 0, 0.3);
            text-align: center;
            word-wrap: break-word;
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
            border: none;
            font-family: 'Khmer OS Muol', sans-serif;
        }
        
        .btn-view { 
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 1px 3px rgba(23, 162, 184, 0.2);
        }
        .btn-view:hover { 
            background: linear-gradient(135deg, #138496, #117a8b);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(23, 162, 184, 0.3);
            color: white;
        }

        .btn-edit { 
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 1px 3px rgba(245, 158, 11, 0.2);
        }
        .btn-edit:hover { 
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
            color: white;
        }

        .btn-delete { 
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 1px 3px rgba(239, 68, 68, 0.2);
        }
        .btn-delete:hover { 
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.3);
            color: white;
        }

        /* Button Group for Action Buttons */
        .btn-group-action {
            display: flex;
            gap: 4px;
            justify-content: center;
        }

        /* Alert Styling */
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

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px auto;
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
                border: 1px solid rgba(255, 165, 0, 0.3);
                border-radius: 8px;
                padding: 10px;
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
            
            .btn-group-action {
                flex-direction: column;
                gap: 4px;
            }
            
            .btn-sm {
                width: 100%;
                margin: 1px 0;
            }
        }

        @media (max-width: 576px) {
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

            .btn-primary, .btn-secondary, .btn-print-all,
            .table .btn-sm,
            .btn-group-action {
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
            }

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
                padding: 10px 15px !important;
                font-size: 10pt !important;
                vertical-align: top !important;
                background-color: #fff !important;
                color: #000 !important;
                text-align: left !important;
            }
            
            .table td::before {
                content: none !important;
            }
            
            .table td {
                text-align: left !important;
                padding-left: 8px !important;
            }
        }

        /* No data message */
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            font-style: italic;
            font-family: 'Khmer OS Siemreap', sans-serif;
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="LOGO.png" alt="Wat Management System Logo" class="logo">
    </div>

    <div class="container">
        <h3><i class="bi bi-people-fill me-2"></i>បញ្ជីព្រះសង្ឃវត្តថ្មគោល</h3>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>បញ្ជីរាយនាមព្រះសង្ឃ</h5>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary" onclick="logAction('Back Click')">
                        <i class="bi bi-arrow-left me-2"></i>ត្រឡប់ក្រោយ
                    </a>
                    <a href="add_monk.php" class="btn btn-primary" onclick="logAction('Add Monk')">
                        <i class="bi bi-person-plus me-2"></i>បន្ថែមព្រះសង្ឃថ្មី
                    </a>
                    <button class="btn btn-print-all" onclick="logAction('Print'); window.print()">
                        <i class="bi bi-printer me-2"></i>បោះពុម្ភ
                    </button>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th data-label="ID"><i class="bi bi-hash me-1"></i>ID</th>
                                <th data-label="គោត្តនាម (ខ្មែរ)"><i class="bi bi-person-vcard me-1"></i>គោត្តនាម (ខ្មែរ)</th>
                                <th data-label="គោត្តនាម (ឡាតាំង)"><i class="bi bi-person-badge me-1"></i>គោត្តនាម (ឡាតាំង)</th>
                                <th data-label="ប្រភេទ"><i class="bi bi-tags me-1"></i>ប្រភេទ</th>
                                <th data-label="កុដិ"><i class="bi bi-house-door me-1"></i>កុដិ</th>
                                <th data-label="មុខងារ"><i class="bi bi-briefcase me-1"></i>មុខងារ</th>
                                <th data-label="សកម្មភាព"><i class="bi bi-gear me-1"></i>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($monks)): ?>
                                <tr>
                                    <td colspan="7" class="no-data">
                                        <i class="bi bi-inbox me-2"></i>មិនមានទិន្នន័យ
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($monks as $monk): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo $monk['id']; ?></td>
                                        <td data-label="គោត្តនាម (ខ្មែរ)"><?php echo htmlspecialchars($monk['khmer_name'] ?? 'N/A'); ?></td>
                                        <td data-label="គោត្តនាម (ឡាតាំង)"><?php echo htmlspecialchars($monk['latin_name'] ?? 'N/A'); ?></td>
                                        <td data-label="ប្រភេទ"><?php echo htmlspecialchars($monk['monk_type'] ?? 'N/A'); ?></td>
                                        <td data-label="កុដិ"><?php echo htmlspecialchars($monk['kuti_name'] ?? 'N/A'); ?></td>
                                        <td data-label="មុខងារ"><?php echo htmlspecialchars($monk['role'] ?? 'N/A'); ?></td>
                                        <td data-label="សកម្មភាព">
                                            <div class="btn-group-action">
                                                <a href="view_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-sm btn-view" title="មើល" onclick="logAction('View Monk', 'ID: <?php echo $monk['id']; ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-sm btn-edit" title="កែប្រែ" onclick="logAction('Edit Monk', 'ID: <?php echo $monk['id']; ?>')">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="dashboard.php?delete=<?php echo $monk['id']; ?>" class="btn btn-sm btn-delete" title="លុប" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបមែនទេ?'); logAction('Delete Monk', 'ID: <?php echo $monk['id']; ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.logAction = function(action, details = '') {
                fetch('log_action.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=${encodeURIComponent(action)}&details=${encodeURIComponent(details)}`
                }).catch(error => console.error('Logging failed:', error));
            };
        });
    </script>
</body>
</html>