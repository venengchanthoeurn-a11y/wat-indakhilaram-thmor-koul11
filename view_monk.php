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

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php');
    exit();
}

// Fetch monk data along with kuti name
try {
    $sql = "SELECT m.*, k.kuti_name 
            FROM monks m 
            LEFT JOIN kuti k ON m.kuti_id = k.id 
            WHERE m.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $monk = $stmt->fetch();
    
    if (!$monk) {
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching monk details: " . $e->getMessage());
    die("មានបញ្ហាក្នុងការទាញយកព័ត៌មាន។");
}
?>

<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ព័ត៌មានលម្អិតព្រះសង្ឃ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Font Imports */
        @font-face {
            font-family: 'Khmer OS Muol';
            src: url('fonts/KhmerOSMuol.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Khmer OS Metal Chrieng';
            src: url('fonts/KhmerOSMetalChrieng.ttf') format('truetype');
        }

        /* General Body Styles */
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
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 4px 8px rgba(90, 58, 42, 0.3));
        }

        /* Container Styling */
        .container {
            padding: 30px;
            max-width: 1000px;
            width: 100%;
            background: linear-gradient(135deg, #ffffff, #fff9f0);
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 60px rgba(212, 175, 55, 0.1);
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid rgba(255, 165, 0, 0.3);
            position: relative;
            margin-top: 20px;
            margin-bottom: 50px;
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

        /* Title Styling */
        h3 {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            text-shadow: 2px 2px 4px rgba(139, 69, 19, 0.1);
            font-size: 2.5rem;
            margin-bottom: 30px;
            border-bottom: 3px double rgba(139, 69, 19, 0.3);
            padding-bottom: 15px;
            text-align: center;
            background: linear-gradient(135deg, #8B4513, #5A3A2A);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Form Section Styling */
        .form-section {
            background: linear-gradient(135deg, rgba(255, 248, 225, 0.5), rgba(255, 240, 210, 0.3));
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: inset 0 2px 8px rgba(139, 69, 19, 0.05);
        }

        .form-title {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            font-size: 1.5rem;
            margin-bottom: 25px;
            text-align: center;
            border-bottom: 2px solid rgba(139, 69, 19, 0.2);
            padding-bottom: 10px;
        }

        /* Form Group Styling */
        .form-group {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            border: 1px solid rgba(255, 165, 0, 0.2);
            transition: all 0.3s ease;
        }

        .form-group:hover {
            background: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 165, 0, 0.4);
            transform: translateX(5px);
        }

        .form-label {
            font-family: 'Khmer OS Muol', sans-serif;
            color: #8B4513;
            font-size: 1.1rem;
            margin-bottom: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #FF8C00;
            font-size: 1.2rem;
        }

        .form-control-static {
            font-family: 'Khmer OS Metal Chrieng', sans-serif;
            color: #5A3A2A;
            font-size: 1.1rem;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 8px;
            min-height: 50px;
            display: flex;
            align-items: center;
            border-left: 4px solid #FF8C00;
        }

        /* Input Group with Icons */
        .input-group-icon {
            position: relative;
        }

        .input-group-icon .form-control-static {
            padding-left: 50px;
        }

        .input-group-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF8C00;
            z-index: 2;
            font-size: 1.3rem;
        }

        /* Button Styling */
        .btn-edit {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            border: none;
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #e0a800, #e36407);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
            font-family: 'Khmer OS Muol', sans-serif;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        /* Floating decorations */
        .floating-decoration {
            position: absolute;
            width: 80px;
            height: 80px;
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

        /* Section Divider */
        .section-divider {
            height: 3px;
            background: linear-gradient(90deg, transparent, #FF8C00, transparent);
            margin: 40px 0;
            border: none;
            border-radius: 2px;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
                margin: 10px;
                border-radius: 15px;
            }

            h3 {
                font-size: 2rem;
                margin-bottom: 20px;
            }

            .logo {
                max-width: 140px;
            }

            .form-section {
                padding: 20px;
            }

            .form-title {
                font-size: 1.3rem;
            }

            .form-group {
                padding: 12px;
                margin-bottom: 15px;
            }

            .form-label {
                font-size: 1rem;
            }

            .form-control-static {
                font-size: 1rem;
                padding: 10px 12px;
                min-height: 45px;
            }

            .input-group-icon .form-control-static {
                padding-left: 45px;
            }

            .btn-edit, .btn-back {
                padding: 10px 20px;
                font-size: 1rem;
                width: 100%;
                margin-bottom: 10px;
            }

            .button-group {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            h3 {
                font-size: 1.8rem;
            }

            .form-section {
                padding: 15px;
            }

            .form-title {
                font-size: 1.2rem;
            }

            .form-label {
                font-size: 0.95rem;
            }

            .form-control-static {
                font-size: 0.95rem;
                padding: 8px 10px;
                min-height: 40px;
            }
        }

        /* Animation for form groups */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            animation: fadeInUp 0.5s ease-out;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
        .form-group:nth-child(7) { animation-delay: 0.7s; }
        .form-group:nth-child(8) { animation-delay: 0.8s; }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="LOGO.png" alt="Wat Management System Logo" class="logo">
    </div>

    <div class="container">
        <div class="floating-decoration decoration-1"></div>
        <div class="floating-decoration decoration-2"></div>
        
        <h3>ព័ត៌មានលម្អិតព្រះសង្ឃ</h3>
        
        <div class="form-section">
            <div class="form-title">ព័ត៌មានផ្ទាល់ខ្លួន</div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-translate"></i>គោត្តនាម (ខ្មែរ)
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-person-vcard"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['khmer_name'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-type"></i>គោត្តនាម (ឡាតាំង)
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-type"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['latin_name'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-calendar-date"></i>ថ្ងៃខែឆ្នាំកំណើត
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-calendar3"></i>
                            <div class="form-control-static">
                                <?php echo $monk['birth_date'] ? date('d/m/Y', strtotime($monk['birth_date'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-person-gear"></i>ប្រភេទ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-person-badge"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['monk_type'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-mortarboard"></i>កម្រិតសិក្សា
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-journal-text"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['education_level'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-house"></i>កុដិ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-building"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['kuti_name'] ?? 'គ្មានកុដិ'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-briefcase"></i>មុខងារ
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-person-lines-fill"></i>
                            <div class="form-control-static">
                                <?php echo htmlspecialchars($monk['role'] ?? 'N/A'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="bi bi-clock"></i>ថ្ងៃចូល
                        </label>
                        <div class="input-group-icon">
                            <i class="bi bi-calendar-check"></i>
                            <div class="form-control-static">
                                <?php echo $monk['created_at'] ? date('d/m/Y H:i', strtotime($monk['created_at'])) : 'N/A'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <hr class="section-divider">
        
        <div class="d-flex justify-content-center gap-3 button-group">
            <a href="edit_monk.php?id=<?php echo $monk['id']; ?>" class="btn btn-edit" onclick="logAction('Edit Monk', 'Monk ID: <?php echo $monk['id']; ?>')">
                <i class="bi bi-pencil"></i>កែប្រែ
            </a>
            <a href="dashboard.php" class="btn btn-back" onclick="logAction('Back Click')">
                <i class="bi bi-arrow-left"></i>ត្រឡប់ក្រោយ
            </a>
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