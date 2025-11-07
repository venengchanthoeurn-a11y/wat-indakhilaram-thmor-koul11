<?php
include 'config.php';
requireLogin(); // Ensure user is logged in

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $khmer_name = trim($_POST['khmer_name'] ?? '');
    $latin_name = trim($_POST['latin_name'] ?? '');
    $birth_date = trim($_POST['birth_date'] ?? '');
    $monk_type = $_POST['monk_type'] ?? '';
    // education_level column removed as it's not in the new database schema
    $kuti_id = filter_var($_POST['kuti_id'], FILTER_VALIDATE_INT); // Convert to int or false
    $role = trim($_POST['role'] ?? '');

    // Validate required fields and birth date format (dd/mm/yyyy) with positive year
    if (empty($khmer_name) || empty($latin_name) || empty($birth_date) || empty($monk_type)) {
        $message = "សូមបំពេញគ្រប់វាលដែលចាំបាច់។";
        $message_type = "danger";
    } elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $birth_date)) {
        $message = "ទម្រង់ថ្ងៃខែឆ្នាំកំណើតមិនត្រឹមត្រូវ។ សូមប្រើទម្រង់ dd/mm/yyyy (ឧ. 01/01/1990)។";
        $message_type = "danger";
    } else {
        // Extract year and validate it's positive
        list($day, $month, $year) = explode('/', $birth_date);
        if ($year <= 0) {
            $message = "ឆ្នាំកំណើតមិនត្រឹមត្រូវ។ សូមប្រើឆ្នាំវិជ្ជមាន (ឧ. 1990)។";
            $message_type = "danger";
        } else {
            try {
                // Updated SQL query: removed 'education_level'
                $sql = "INSERT INTO monks (khmer_name, latin_name, birth_date, monk_type, kuti_id, role) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $khmer_name,
                    $latin_name,
                    $birth_date,
                    $monk_type,
                    $kuti_id === false ? null : $kuti_id, // Use NULL if kuti_id is invalid
                    $role ?: null // Use NULL if role is empty
                ]);
                $message = "បន្ថែមព័ត៌មានព្រះសង្ឃថ្មីបានជោគជ័យ!";
                $message_type = "success";
                // Clear form fields after successful submission (optional, but good UX)
                $_POST = array(); 
            } catch (PDOException $e) {
                error_log("Error adding monk: " . $e->getMessage());
                $message = "មានបញ្ហាក្នុងការបន្ថែមព័ត៌មានព្រះសង្ឃថ្មី។";
                $message_type = "danger";
            }
        }
    }
}

// Fetch kuti list for the dropdown
$kuti_list = $pdo->query("SELECT id, kuti_name FROM kuti ORDER BY kuti_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <title>បន្ថែមព្រះសង្ឃថ្មី</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @font-face {
            font-family: 'Khmer OS Muol';
            src: url('fonts/KhmerOSMuol.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Khmer OS Metal Chrieng';
            src: url('fonts/KhmerOSMetalChrieng.ttf') format('truetype');
        }

        body {
            background: linear-gradient(135deg, #f8f4e9, #e8d9c5);
            font-family: 'Khmer OS Metal Chrieng', sans-serif;
            color: #5A3A2A;
            margin: 0;
            padding: 0;
            min-height: 100vh;
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
            max-width: 220px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(90, 58, 42, 0.3));
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .container {
            background: linear-gradient(135deg, #ffffff, #fff9f0);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 
                0 15px 35px rgba(139, 69, 19, 0.1),
                0 5px 15px rgba(139, 69, 19, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            margin-top: 30px;
            margin-bottom: 50px;
            border: 1px solid rgba(255, 165, 0, 0.2);
            max-width: 900px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF8C00, #FFA500, #FF8C00);
            border-radius: 20px 20px 0 0;
        }

        h2 {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            text-shadow: 2px 2px 4px rgba(139, 69, 19, 0.1);
            margin-bottom: 30px;
            font-size: 2.2rem;
            border-bottom: 3px double rgba(139, 69, 19, 0.3);
            padding-bottom: 15px;
            text-align: center;
            background: linear-gradient(135deg, #8B4513, #5A3A2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-label {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            font-size: 1.1rem;
            margin-bottom: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .form-label::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
            display: none;
        }

        .required .form-label::after {
            display: inline;
        }

        .form-control, .form-select {
            border: 2px solid #D2B48C;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fff, #f9f5f0);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .form-control:focus, .form-select:focus {
            border-color: #FF8C00;
            box-shadow: 
                0 0 0 0.25rem rgba(255, 140, 0, 0.15),
                inset 0 2px 4px rgba(0, 0, 0, 0.05);
            outline: none;
            background: linear-gradient(135deg, #fff, #fff5e6);
        }

        .form-control::placeholder {
            color: #A9A9A9;
            font-style: italic;
        }

        .btn-primary, .btn-secondary {
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 12px 30px;
            border-radius: 12px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF8C00, #FF6347);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #FF6347, #FF4500);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 69, 0, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #A0522D, #CD853F);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
        }

        .btn-secondary:active {
            transform: translateY(0);
        }

        .alert {
            font-family: 'Khmer OS Metal Chrieng', sans-serif;
            border-radius: 12px;
            border: none;
            color: #5A3A2A;
            position: relative;
            z-index: 1;
            padding: 16px 20px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #fff, #f8f9fa);
            border-left: 4px solid;
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

        .form-section {
            background: linear-gradient(135deg, rgba(255, 248, 225, 0.5), rgba(255, 240, 210, 0.3));
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: inset 0 2px 8px rgba(139, 69, 19, 0.05);
        }

        .form-title {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            font-size: 1.3rem;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid rgba(139, 69, 19, 0.2);
            padding-bottom: 10px;
        }

        .input-group-icon {
            position: relative;
        }

        .input-group-icon .form-control,
        .input-group-icon .form-select {
            padding-left: 45px;
        }

        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF8C00;
            z-index: 2;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 25px 20px;
                margin-top: 20px;
                margin-bottom: 30px;
                max-width: 95%;
                border-radius: 15px;
            }

            h2 {
                font-size: 1.8rem;
                margin-bottom: 20px;
            }

            .form-label {
                font-size: 1rem;
            }

            .form-control, .form-select {
                padding: 10px 12px;
                font-size: 0.95rem;
            }

            .btn-primary, .btn-secondary {
                padding: 10px 20px;
                font-size: 1rem;
                width: 100%;
                margin-bottom: 10px;
            }

            .logo {
                max-width: 100px;
            }

            .form-section {
                padding: 20px 15px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 20px 15px;
            }

            h2 {
                font-size: 1.6rem;
            }

            .form-label {
                font-size: 0.95rem;
            }
        }

        .floating-decoration {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.1;
            pointer-events: none;
        }

        .decoration-1 {
            top: 20px;
            right: 20px;
            background: radial-gradient(circle, #FF8C00 0%, transparent 70%);
        }

        .decoration-2 {
            bottom: 20px;
            left: 20px;
            background: radial-gradient(circle, #8B4513 0%, transparent 70%);
        }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="LOGO.png" alt="Wat Management System Logo" class="logo">
    </div>

    <div class="container mt-4">
        <div class="floating-decoration decoration-1"></div>
        <div class="floating-decoration decoration-2"></div>
        
        <h2>បន្ថែមព្រះសង្ឃថ្មី</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> mb-4"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <div class="form-title">ព័ត៌មានផ្ទាល់ខ្លួន</div>
            <form method="POST">
                <div class="row g-4">
                    <div class="col-md-6 required">
                        <label for="khmer_name" class="form-label">
                            <i class="bi bi-person-vcard me-2"></i>គោត្តនាម (ខ្មែរ)
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-translate"></i>
                            <input type="text" class="form-control" id="khmer_name" name="khmer_name" 
                                   value="<?php echo htmlspecialchars($_POST['khmer_name'] ?? ''); ?>" 
                                   placeholder="បញ្ចូលគោត្តនាមជាភាសាខ្មែរ" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 required">
                        <label for="latin_name" class="form-label">
                            <i class="bi bi-person-vcard me-2"></i>គោត្តនាម (ឡាតាំង)
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-type"></i>
                            <input type="text" class="form-control" id="latin_name" name="latin_name" 
                                   value="<?php echo htmlspecialchars($_POST['latin_name'] ?? ''); ?>" 
                                   placeholder="Enter name in Latin script" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6 required">
                        <label for="birth_date" class="form-label">
                            <i class="bi bi-calendar3 me-2"></i>ថ្ងៃខែឆ្នាំកំណើត
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-calendar-date"></i>
                            <input type="text" class="form-control" id="birth_date" name="birth_date" 
                                   value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>" 
                                   placeholder="dd/mm/yyyy" required pattern="\d{2}\/\d{2}\/\d{4}" 
                                   title="សូមប្រើទម្រង់ dd/mm/yyyy (ឧ. 01/01/1990)">
                        </div>
                    </div>
                    
                    <div class="col-md-6 required">
                        <label for="monk_type" class="form-label">
                            <i class="bi bi-person-gear me-2"></i>ប្រភេទ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-chevron-down"></i>
                            <select class="form-select" id="monk_type" name="monk_type" required>
                                <option value="">-- ជ្រើសរើសប្រភេទ --</option>
                                <option value="ភិក្ខុ" <?php if (isset($_POST['monk_type']) && $_POST['monk_type'] == 'ភិក្ខុ') echo 'selected'; ?>>ភិក្ខុ</option>
                                <option value="សាមណេរ" <?php if (isset($_POST['monk_type']) && $_POST['monk_type'] == 'សាមណេរ') echo 'selected'; ?>>សាមណេរ</option>
                                <option value="ព្រះចៅអធិការ" <?php if (isset($_POST['monk_type']) && $_POST['monk_type'] == 'ព្រះចៅអធិការ') echo 'selected'; ?>>ព្រះចៅអធិការ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="kuti_id" class="form-label">
                            <i class="bi bi-house me-2"></i>កុដិ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-building"></i>
                            <select class="form-select" id="kuti_id" name="kuti_id">
                                <option value="">-- ជ្រើសរើសកុដិ --</option>
                                <?php foreach ($kuti_list as $kuti): ?>
                                    <option value="<?php echo $kuti['id']; ?>" <?php if (isset($_POST['kuti_id']) && $_POST['kuti_id'] == $kuti['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($kuti['kuti_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="role" class="form-label">
                            <i class="bi bi-briefcase me-2"></i>មុខងារ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-person-badge"></i>
                            <input type="text" class="form-control" id="role" name="role" 
                                   value="<?php echo htmlspecialchars($_POST['role'] ?? ''); ?>" 
                                   placeholder="បញ្ចូលមុខងារ (បើមាន)">
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-center gap-3 mt-5">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus me-2"></i>បន្ថែមព្រះសង្ឃ
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-2"></i>ត្រឡប់ក្រោយ
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add real-time validation for birth date
        document.getElementById('birth_date').addEventListener('input', function(e) {
            const value = e.target.value;
            const datePattern = /^\d{2}\/\d{2}\/\d{4}$/;
            
            if (value && !datePattern.test(value)) {
                e.target.setCustomValidity('សូមប្រើទម្រង់ dd/mm/yyyy (ឧ. 01/01/1990)');
            } else {
                e.target.setCustomValidity('');
            }
        });

        // Add form validation styling
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('invalid', function(e) {
                e.preventDefault();
                this.classList.add('is-invalid');
            });
            
            element.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    </script>
</body>
</html>