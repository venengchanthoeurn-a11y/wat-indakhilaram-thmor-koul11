<?php
include 'config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "សូមបំពេញគ្រប់addField។";
    } elseif ($password !== $confirm_password) {
        $error = "លេខសម្ងាត់មិនตรงกันទេ។";
    } elseif (strlen($password) < 6) {
        $error = "លេខសម្ងាត់ត្រូវតែมีอย่างน้อย 6 ตัวอักษร។";
    } else {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "ឈ្មោះអ្នកប្រើប្រាស់នេះមានរួចហើយ។";
            } else {
                // Hash the password for security
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user into the database
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'viewer')");
                $stmt->execute([$username, $hashed_password]);

                $success = "គណនីបានបង្កើតដោយជោគជ័យ! អ្នកអាចចូលទៅកាន់ប្រព័ន្ធបានแล้ว។";
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = "មានបញ្ហាក្នុងការបង្កើតគណនី។";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>បង្កើតគណនី - ប្រព័ន្ធគ្រប់គ្រងវត្ត</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Koulen&family=Moul&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Koulen', cursive; }
        .register-container { max-width: 450px; margin: 5rem auto; }
        .card-header { font-family: 'Moul', cursive; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card shadow-sm">
            <div class="card-header text-center bg-primary text-white">
                <h3>បង្កើតគណនីថ្មី</h3>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ឈ្មោះអ្នកប្រើ</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">លេខសម្ងាត់</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">បញ្ជាក់លេខសម្ងាត់</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">បង្កើតគណនី</button>
                </form>
                <div class="text-center mt-3">
                    <a href="login.php">មានគណនីហើយ? ចូលទៅកាន់ប្រព័ន្ធ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
