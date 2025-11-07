<?php
include 'config.php';

// If user is already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "សូមបញ្ចូលឈ្មោះអ្នកប្រើ និងលេខសម្ងាត់។";
    } else {
        try {
            // Fetch user from the database
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Verify password using simple string comparison (NOT SECURE - This should be replaced with password_verify in a real app)
            if ($user && $password === $user['password']) {
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit();
            } else {
                // Invalid credentials
                $error = "ឈ្មោះអ្នកប្រើ ឬ លេខសម្ងាត់មិនត្រឹមត្រូវ!";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "មានបញ្ហាក្នុងការចូល។ សូមព្យាយាមម្តងទៀត។";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ចូលប្រព័ន្ធ - ប្រព័ន្ធគ្រប់គ្រងវត្ត</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Using specific Khmer fonts for better aesthetics -->
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
        }
        
        body {
            /* Enhanced gradient background for a softer, more inviting feel */
            background: radial-gradient(circle at top left, var(--lighter-bg-pale) 0%, var(--light-bg-cream) 60%, #F5E8C7 100%);
            /* Smooth animation for background */
            background-size: 300% 300%;
            animation: gradientShift 20s ease infinite alternate;
            
            font-family: 'Khmer OS Siemreap', sans-serif; /* Elegant Khmer font for body text */
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--text-dark-brown); /* Dark brown for general text */
            overflow: hidden; /* Hide overflow from particles */
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }
        
        .login-container {
            max-width: 480px; /* Slightly wider container */
            width: 100%;
            margin: 20px;
            perspective: 1000px;
            z-index: 10; /* Ensure login card is above particles */
        }
        
        .login-card {
            border: none;
            border-radius: 25px; /* More rounded corners */
            overflow: hidden;
            /* Enhanced shadow for depth and richness */
            box-shadow: 0 20px 40px rgba(0,0,0,0.3), 0 0 50px rgba(212, 175, 55, 0.4); /* Stronger shadow and gold glow */
            transform-style: preserve-3d;
            transition: all 0.6s ease-in-out; /* Slower, smoother transition */
            background: var(--light-bg-cream); /* Card background matching theme */
            position: relative;
        }
        
        .login-card:hover {
            transform: translateY(-8px) rotateX(1deg); /* More pronounced lift and subtle 3D tilt */
            box-shadow: 0 30px 60px rgba(0,0,0,0.4), 0 0 70px rgba(255, 192, 29, 0.6); /* Even stronger shadow and glow */
        }
        
        .card-header {
            /* Header background with theme colors */
            background: linear-gradient(135deg, var(--primary-theme-gold), var(--secondary-theme-red));
            color: var(--light-bg-cream); /* Text color contrasting the gradient */
            font-family: 'Khmer OS Muol', cursive; /* Elegant Khmer font for header */
            text-align: center;
            padding: 30px 20px; /* More padding */
            position: relative;
            overflow: hidden;
            border-bottom: 3px solid rgba(255,255,255,0.3); /* Subtle separator line */
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 2.2rem; /* Larger header text */
            position: relative;
            z-index: 2;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.4); /* Stronger text shadow for impact */
        }
        
        .card-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%); /* More visible light pulse */
            animation: pulse 12s infinite linear; /* Slower, more majestic pulse */
            z-index: 1;
        }
        
        @keyframes pulse {
            0% { transform: rotate(0deg); opacity: 1; }
            100% { transform: rotate(360deg); opacity: 0.8; } /* Keep some opacity at 100% */
        }
        
        .card-body {
            padding: 40px; /* More generous padding */
            background: var(--light-bg-cream); /* Consistent card body background */
            /* Removed border-bottom-left-radius and border-bottom-right-radius here, as the copyright div will be outside the body but within the card */
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 35px; /* More space below logo */
        }
        
        .logo {
            width: 180px; /* Even Larger logo */
            height: 180px; /* Even Larger logo */
            object-fit: contain;
            animation: float 8s ease-in-out infinite; /* Slower float animation */
            /* Stronger gold shadow for logo */
            filter: drop-shadow(0 8px 20px rgba(212, 175, 55, 0.8)); 
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); } /* More vertical movement */
            100% { transform: translateY(0px); }
        }
        
        .form-label {
            font-family: 'Khmer OS Muol', cursive; /* Moul font for labels */
            font-size: 1.15rem; /* Slightly larger label text */
            margin-bottom: 10px; /* More space below label */
            display: block;
            color: var(--text-dark-brown); /* Dark brown for labels */
            transition: color 0.3s ease; /* Smooth color transition on focus */
        }
        
        .form-control {
            border-radius: 12px; /* More rounded input fields */
            padding: 15px 20px; /* More padding for inputs */
            border: 2px solid #e0e0e0; /* Softer border */
            transition: all 0.4s ease;
            font-family: 'Khmer OS Siemreap', sans-serif; /* Consistent body font for inputs */
            color: var(--text-dark-brown);
            background-color: rgba(255,255,255,0.8); /* Slightly transparent white for inputs */
        }
        
        .form-control:focus {
            border-color: var(--primary-theme-gold); /* Gold border on focus */
            box-shadow: 0 0 0 0.3rem rgba(212, 175, 55, 0.4); /* Gold glow on focus */
            background-color: white; /* Solid white on focus */
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-theme-gold), var(--secondary-theme-red)); /* Theme gradient for button */
            border: none;
            border-radius: 12px; /* Rounded button corners */
            padding: 15px; /* More padding */
            font-family: 'Khmer OS Muol', cursive; /* Moul font for button text */
            font-size: 1.25rem; /* Larger button text */
            letter-spacing: 1px;
            color: white; /* White text for contrast */
            transition: all 0.4s ease;
            width: 100%;
            margin-top: 25px; /* More space above button */
            /* Stronger shadow for the button */
            box-shadow: 0 6px 20px rgba(0,0,0,0.3), 0 0 30px rgba(212, 175, 55, 0.5); 
        }
        
        .btn-login:hover {
            transform: translateY(-4px); /* More pronounced lift */
            box-shadow: 0 10px 25px rgba(0,0,0,0.4), 0 0 40px rgba(255, 192, 29, 0.7); /* Even stronger shadow and glow */
            background: linear-gradient(135deg, var(--secondary-theme-red), var(--primary-theme-gold)); /* Reverse gradient on hover */
        }
        
        .btn-login i {
            margin-right: 10px; /* Space for icon */
            font-size: 1.2rem; /* Icon size */
        }

        /* Password Toggle Button within input-group */
        .input-group .password-toggle {
            background-color: var(--light-bg-cream); /* Match card background */
            border: 2px solid #e0e0e0; /* Match input border */
            border-left: none; /* Remove left border to merge with input */
            color: var(--text-dark-brown);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            transition: all 0.3s ease;
            padding: 15px; /* Match input padding */
        }
        .input-group .password-toggle:hover {
            background-color: rgba(212, 175, 55, 0.1); /* Slight gold hover */
            border-color: var(--primary-theme-gold);
            color: var(--primary-theme-gold);
        }
        /* Ensure input field inside input-group has correct rounded corners */
        .input-group > .form-control:not(:last-child) {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .copyright {
            text-align: center;
            padding: 15px; /* Padding around copyright text */
            background-color: var(--tertiary-dark-blue); /* Dark blue background */
            color: var(--lighter-bg-pale); /* Light text color */
            font-family: 'Khmer OS Siemreap', sans-serif;
            font-size: 0.95rem;
            border-bottom-left-radius: 25px; /* Match card radius */
            border-bottom-right-radius: 25px; /* Match card radius */
            box-shadow: inset 0 5px 10px rgba(0,0,0,0.2); /* Inner shadow for depth */
        }
        
        .alert {
            border-radius: 10px;
            font-family: 'Khmer OS Siemreap', sans-serif;
            margin-bottom: 25px; /* More space below alert */
            padding: 15px 20px;
            border: 1px solid transparent; /* Default transparent border */
        }
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15); /* Light red with opacity */
            color: #b02a37; /* Darker red text */
            border-color: #dc3545; /* Red border */
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2); /* Soft red shadow */
        }

        /* Particles for animated background */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Behind the login card */
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            /* Use theme colors for particles */
            background: rgba(212, 175, 55, 0.2); /* Gold with opacity */
            border-radius: 50%;
            animation: floatParticle linear infinite;
        }
        
        @keyframes floatParticle {
            0% { transform: translateY(0) rotate(0deg); opacity: 0.5; }
            100% { transform: translateY(-1200px) rotate(720deg); opacity: 0; } /* Fall from higher point */
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .login-container {
                margin: 15px;
            }
            .login-card {
                border-radius: 15px;
            }
            .card-header {
                padding: 20px;
            }
            .card-header h3 {
                font-size: 1.6rem;
            }
            .card-body {
                padding: 25px;
            }
            .logo {
                width: 120px; /* Smaller logo for mobile */
                height: 120px;
                margin-bottom: 25px;
            }
            .form-label {
                font-size: 1rem;
            }
            .form-control {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .btn-login {
                padding: 12px;
                font-size: 1.1rem;
                margin-top: 20px;
            }
            .copyright {
                font-size: 0.85rem;
                border-bottom-left-radius: 15px;
                border-bottom-right-radius: 15px;
            }
            .input-group .password-toggle {
                padding: 12px; /* Adjust padding for mobile */
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h3>ចូលទៅកាន់ប្រព័ន្ធ</h3>
            </div>
            <div class="card-body">
                <div class="logo-container">
                    <img src="LOGO.png" onerror="this.onerror=null;this.src='https://placehold.co/180x180/D4AF37/7A1022?text=វត្ត+LOGO';" alt="ឡូហ្គោប្រព័ន្ធ" class="logo">
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4">
                        <label for="username" class="form-label">ឈ្មោះអ្នកប្រើ</label>
                        <input type="text" class="form-control" id="username" name="username" required placeholder="បញ្ចូលឈ្មោះអ្នកប្រើ" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">លេខសម្ងាត់</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required placeholder="បញ្ចូលលេខសម្ងាត់">
                            <button class="btn btn-outline-secondary password-toggle" type="button" id="togglePassword">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="bi bi-box-arrow-in-right"></i> ចូលប្រព័ន្ធ
                    </button>
                </form>
            </div>
            <!-- Copyright text at the very bottom of the card -->
            <div class="copyright">
                រក្សាសិទ្ធដោយ@អេង ចាន់ធឿន-២០២៥
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Create animated particles
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 40; // Increased particle count for more density
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random properties for each particle
                const size = Math.random() * 25 + 8; // Larger particles
                const posX = Math.random() * window.innerWidth;
                const delay = Math.random() * 8; // Longer random delay
                const duration = Math.random() * 25 + 15; // Longer duration for slower float
                const opacity = Math.random() * 0.6 + 0.2; // Higher opacity range
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.bottom = `-${size}px`; // Start from below the viewport
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                particle.style.opacity = opacity;
                
                particlesContainer.appendChild(particle);
            }
            
            // Password Show/Hide functionality
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordInput.setAttribute('type', type);
                    
                    // Toggle icon
                    this.querySelector('i').classList.toggle('bi-eye');
                    this.querySelector('i').classList.toggle('bi-eye-slash');
                });
            }
        });

        // Add a fallback for the image if it fails to load
        // This is handled by the onerror attribute in the img tag now for simplicity.
    </script>
</body>
</html>
