<?php
include 'config.php';
requireLogin(); // Ensure user is logged in

// Handle adding a new kuti
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kuti_name'])) {
    $kuti_name = trim(filter_var($_POST['kuti_name'], FILTER_SANITIZE_STRING));
    if (!empty($kuti_name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO kuti (kuti_name) VALUES (?)");
            $stmt->execute([$kuti_name]);
            $_SESSION['message'] = "បានបន្ថែមកុដិជោគជ័យ!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            error_log("Error adding kuti: " . $e->getMessage());
            $_SESSION['message'] = "មានបញ្ហាក្នុងការបន្ថែម។";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "សូមបញ្ចូលឈ្មោះកុដិ។";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: kuti.php");
    exit();
}

// Handle deleting a kuti
if (isset($_GET['delete']) && filter_var($_GET['delete'], FILTER_VALIDATE_INT)) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM kuti WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['message'] = "បានលុបកុដិជោគជ័យ!";
        $_SESSION['message_type'] = "success";
    } catch (PDOException $e) {
        error_log("Error deleting kuti: " . $e->getMessage());
        $_SESSION['message'] = "មានបញ្ហាក្នុងការលុប។";
        $_SESSION['message_type'] = "danger";
    }
    header("Location: kuti.php");
    exit();
}

// Fetch kuti list with count of monks
$kuti_list = $pdo->query("
    SELECT k.id, k.kuti_name, COUNT(m.id) as monk_count
    FROM kuti k
    LEFT JOIN monks m ON k.id = m.kuti_id
    GROUP BY k.id, k.kuti_name
    ORDER BY k.kuti_name ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>គ្រប់គ្រងកុដិ</title>
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
            max-width: 1200px;
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
            padding: 15px 20px;
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

        .alert-success {
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f8fff9, #e8f5e8);
            color: #155724;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background: linear-gradient(135deg, #fff8f8, #f8e8e8);
            color: #721c24;
        }

        .alert-warning {
            border-left-color: #ffc107;
            background: linear-gradient(135deg, #fffdf0, #fef9e7);
            color: #856404;
        }

        /* Input Group Styling */
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group .form-control {
            border: 2px solid var(--light-orange);
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 12px 15px;
            font-size: 1.1rem;
            height: auto;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .input-group .form-control:focus {
            border-color: #FF4500;
            box-shadow: 0 0 0 0.25rem rgba(255, 69, 0, 0.25);
            outline: none;
        }
        
        .input-group .btn-primary {
            font-family: 'Khmer OS Muol', sans-serif;
            background: linear-gradient(135deg, var(--primary-orange), var(--light-orange));
            border: 2px solid var(--light-orange);
            border-left: none;
            border-radius: 0 10px 10px 0;
            padding: 12px 25px;
            font-size: 1.2rem;
            color: var(--dark-brown);
            transition: all 0.3s ease, transform 0.2s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .input-group .btn-primary:hover {
            background: linear-gradient(135deg, var(--light-orange), #FF4500);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 140, 0, 0.2);
        }

        /* Monk Details Card */
        .monk-details-card {
            display: none;
            background-color: var(--cream-bg);
            border: 2px solid var(--light-orange);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            padding: 25px;
        }
        
        .monk-details-card.active {
            display: block;
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
            
            .monk-details-card {
                padding: 15px;
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
            .btn-group-action,
            .input-group {
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
        <h3><i class="bi bi-house-door-fill me-2"></i>គ្រប់គ្រងកុដិ</h3>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                <i class="bi <?php 
                    if($_SESSION['message_type'] == 'success') echo 'bi-check-circle-fill';
                    else echo 'bi-exclamation-triangle-fill';
                ?>"></i>
                <?php echo $_SESSION['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>បន្ថែមកុដិថ្មី</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="input-group">
                        <input type="text" name="kuti_name" class="form-control" placeholder="ឈ្មោះកុដិថ្មី" required aria-label="ឈ្មោះកុដិ">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>បន្ថែម
                        </button>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>ត្រឡប់ក្រោយ
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>បញ្ជីកុដិ</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th data-label="ឈ្មោះកុដិ"><i class="bi bi-house me-1"></i>ឈ្មោះកុដិ</th>
                                <th data-label="ចំនួនព្រះសង្ឃ"><i class="bi bi-people me-1"></i>ចំនួនព្រះសង្ឃ</th>
                                <th class="text-end" data-label="សកម្មភាព"><i class="bi bi-gear me-1"></i>សកម្មភាព</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($kuti_list)): ?>
                                <tr>
                                    <td colspan="3" class="no-data">
                                        <i class="bi bi-inbox me-2"></i>មិនមានកុដិត្រូវបានបន្ថែមនៅឡើយទេ។
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($kuti_list as $kuti): ?>
                                    <tr data-kuti-id="<?php echo $kuti['id']; ?>">
                                        <td data-label="ឈ្មោះកុដិ"><?php echo htmlspecialchars($kuti['kuti_name']); ?></td>
                                        <td data-label="ចំនួនព្រះសង្ឃ"><?php echo $kuti['monk_count']; ?></td>
                                        <td data-label="សកម្មភាព" class="text-end">
                                            <div class="btn-group-action">
                                                <a href="edit_kuti.php?id=<?php echo $kuti['id']; ?>" class="btn btn-sm btn-edit" title="កែប្រែ">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="kuti.php?delete=<?php echo $kuti['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('តើអ្នកប្រាកដជាចង់លុបមែនទេ?');" title="លុប">
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
                <div class="monk-details-card" id="monkDetailsCard">
                    <h5 class="mb-3" id="kutiNameHeader"></h5>
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash me-1"></i>អត្តលេខ</th>
                                <th><i class="bi bi-person-vcard me-1"></i>ឈ្មោះខ្មែរ</th>
                            </tr>
                        </thead>
                        <tbody id="monkDetailsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            const card = document.getElementById('monkDetailsCard');
            const kutiNameHeader = document.getElementById('kutiNameHeader');
            const tableBody = document.getElementById('monkDetailsTableBody');

            rows.forEach(row => {
                row.addEventListener('click', function() {
                    const kutiId = this.getAttribute('data-kuti-id');
                    const kutiName = this.cells[0].textContent;

                    // Toggle card visibility
                    card.classList.toggle('active');
                    if (card.classList.contains('active')) {
                        kutiNameHeader.textContent = `ព្រះសង្ឃនៅក្នុង ${kutiName}`;
                        fetchMonkDetails(kutiId);
                    } else {
                        tableBody.innerHTML = '';
                    }
                });
            });

            function fetchMonkDetails(kutiId) {
                fetch(`get_monks.php?kuti_id=${kutiId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        tableBody.innerHTML = '';
                        if (data.error) {
                            tableBody.innerHTML = `<tr><td colspan="2" class="text-center">${data.error}</td></tr>`;
                        } else if (data.monks && data.monks.length > 0) {
                            data.monks.forEach(monk => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${monk.id || 'N/A'}</td>
                                    <td>${monk.khmer_name || 'គ្មាន'}</td>
                                `;
                                tableBody.appendChild(row);
                            });
                        } else {
                            const row = document.createElement('tr');
                            row.innerHTML = `<td colspan="2" class="text-center">គ្មានព្រះសង្ឃនៅក្នុងកុដិនេះទេ។</td>`;
                            tableBody.appendChild(row);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        tableBody.innerHTML = '<tr><td colspan="2" class="text-center">មានបញ្ហាក្នុងការទាញយកទិន្នន័យ។</td></tr>';
                    });
            }

            // Initial check for URL parameter (optional)
            const urlParams = new URLSearchParams(window.location.search);
            const initialKutiId = urlParams.get('kuti_id');
            if (initialKutiId) {
                const row = document.querySelector(`tbody tr[data-kuti-id="${initialKutiId}"]`);
                if (row) {
                    row.click();
                }
            }
        });
    </script>
</body>
</html>