<?php
include 'config.php';
requireLogin(); // Ensure user is logged in

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    // Redirect to dashboard if no valid ID is provided
    header('Location: dashboard.php');
    exit();
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $khmer_name = trim($_POST['khmer_name'] ?? '');
    $latin_name = trim($_POST['latin_name'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $monk_type = $_POST['monk_type'] ?? '';
    $education_level = trim($_POST['education_level'] ?? '');
    $kuti_id = filter_var($_POST['kuti_id'], FILTER_VALIDATE_INT);
    $role = trim($_POST['role'] ?? '');

    if (empty($khmer_name) || empty($latin_name) || empty($birth_date) || empty($monk_type)) {
        $message = "សូមបំពេញព័ត៌មានដែរៃចាំបាច់។";
        $message_type = "danger";
    } else {
        try {
            $sql = "UPDATE monks SET khmer_name = ?, latin_name = ?, birth_date = ?, monk_type = ?, 
                    education_level = ?, kuti_id = ?, role = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $khmer_name,
                $latin_name,
                $birth_date,
                $monk_type,
                $education_level ?: null,
                $kuti_id ?: null,
                $role ?: null,
                $id
            ]);
            $message = "កែប្រែព័ត៌មានបានជោគជ័យ!";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Error editing monk: " . $e->getMessage());
            $message = "មានបញ្ហាក្នុងការកែប្រែព័ត៌មាន។";
            $message_type = "danger";
        }
    }
}

// Fetch monk data for the form
try {
    $stmt = $pdo->prepare("SELECT * FROM monks WHERE id = ?");
    $stmt->execute([$id]);
    $monk = $stmt->fetch();
    if (!$monk) {
        // Redirect if monk not found
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    die("មិនអាចទាញយកព័ត៌មានព្រះសង្ឃបានទេ។");
}

// Fetch kuti list
$kuti_list = $pdo->query("SELECT id, kuti_name FROM kuti ORDER BY kuti_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>កែប្រែព័ត៌មានព្រះសង្ឃ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            display: flex;
            flex-direction: column;
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
            max-width: 1000px;
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

        h2 {
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

        h2::after {
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

        .form-label {
            font-family: 'Khmer OS Muol', sans-serif;
            color: var(--text-dark-brown);
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 10px;
            color: var(--orange-icon-color);
            font-size: 1.2rem;
        }

        .form-control, .form-select {
            border: 1px solid var(--primary-theme-gold);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1.1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            font-family: 'Khmer OS Siemreap', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-theme-red);
            box-shadow: 0 0 0 0.25rem rgba(122, 16, 34, 0.25);
            outline: none;
        }

        .btn-primary, .btn-secondary {
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

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-theme-gold), #E6C158);
            color: var(--secondary-theme-red);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #E6C158, var(--primary-theme-gold));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.4);
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

        .btn-primary::before, .btn-secondary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before, .btn-secondary:hover::before {
            left: 100%;
        }

        .btn i {
            margin-right: 8px;
            font-size: 1.1rem;
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

        .alert-success {
            background: linear-gradient(135deg, rgba(0, 100, 0, 0.1), rgba(0, 150, 0, 0.05));
            border-color: #006400;
            color: #004d00;
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(180, 40, 55, 0.05));
            border-color: #dc3545;
            color: #b02a37;
        }

        .form-section {
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid rgba(212, 175, 55, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .form-section-title {
            font-family: 'Khmer OS Muol', sans-serif;
            color: var(--secondary-theme-red);
            font-size: 1.3rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(212, 175, 55, 0.3);
            display: flex;
            align-items: center;
        }

        .form-section-title i {
            margin-right: 10px;
            color: var(--primary-theme-gold);
        }

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
            
            h2 {
                font-size: 2rem;
                margin-bottom: 20px;
            }
            
            .form-label {
                font-size: 1rem;
            }
            
            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 1rem;
            }
            
            .btn-primary, .btn-secondary {
                padding: 10px 20px;
                font-size: 1.1rem;
                width: 100%;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .btn-container {
                flex-direction: column;
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
        <h2><i class="bi bi-pencil-square me-2"></i>កែប្រែព័ត៌មានព្រះសង្ឃ</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-person-badge"></i> ព័ត៌មានបឋម</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="khmer_name" class="form-label"><i class="bi bi-translate"></i> គោត្តនាម (ខ្មែរ)</label>
                        <input type="text" class="form-control" id="khmer_name" name="khmer_name" value="<?php echo htmlspecialchars($monk['khmer_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="latin_name" class="form-label"><i class="bi bi-type"></i> គោត្តនាម (ឡាតាំង)</label>
                        <input type="text" class="form-control" id="latin_name" name="latin_name" value="<?php echo htmlspecialchars($monk['latin_name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-title"><i class="bi bi-info-circle"></i> ព័ត៌មានបន្ថែម</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="birth_date" class="form-label"><i class="bi bi-calendar-event"></i> ថ្ងៃខែឆ្នាំកំណើត</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $monk['birth_date']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="monk_type" class="form-label"><i class="bi bi-person-vcard"></i> ប្រភេទ</label>
                        <select class="form-select" id="monk_type" name="monk_type" required>
                            <option value="ភិក្ខុ" <?php if ($monk['monk_type'] == 'ភិក្ខុ') echo 'selected'; ?>>ភិក្ខុ</option>
                            <option value="សាមណេរ" <?php if ($monk['monk_type'] == 'សាមណេរ') echo 'selected'; ?>>សាមណេរ</option>
                            <option value="ព្រះចៅអធិការ" <?php if ($monk['monk_type'] == 'ព្រះចៅអធិការ') echo 'selected'; ?>>ព្រះចៅអធិការ</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="education_level" class="form-label"><i class="bi bi-book"></i> កម្រិតសិក្សា</label>
                        <input type="text" class="form-control" id="education_level" name="education_level" value="<?php echo htmlspecialchars($monk['education_level'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="kuti_id" class="form-label"><i class="bi bi-house-door"></i> កុដិ</label>
                        <select class="form-select" id="kuti_id" name="kuti_id">
                            <option value="">-- គ្មានកុដិ --</option>
                            <?php foreach ($kuti_list as $kuti): ?>
                                <option value="<?php echo $kuti['id']; ?>" <?php if ($monk['kuti_id'] == $kuti['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($kuti['kuti_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="role" class="form-label"><i class="bi bi-briefcase"></i> មុខងារ</label>
                        <input type="text" class="form-control" id="role" name="role" value="<?php echo htmlspecialchars($monk['role'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4 btn-container">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> រក្សាទុកការផ្លាស់ប្តូរ</button>
                <a href="dashboard.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> ត្រឡប់ក្រោយ</a>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>