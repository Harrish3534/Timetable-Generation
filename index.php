<?php
// Start the session so we can grab error messages sent back from login.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAC Timetable - Login</title>
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .password-group {
            position: relative;
        }

        .eyes {
            position: absolute;
            top: 40px;
            right: 10px;
            cursor: pointer;
            color: #888;
        }
        
        /* Added a quick style for the error message */
        .error-message {
            color: #ff3333;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-box">
            <h1>Staff Login</h1>
            
            <?php
// If login.php sends back an error message via session, display it here
if (isset($_SESSION['login_error'])) {
    echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
    // Clear the error message so it doesn't show up again on a fresh page load
    unset($_SESSION['login_error']);
}
?>

            <form action="Login/login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username" required>
                </div>

                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i id="eye-open" class="fa-regular fa-eye eyes"></i>
                    <i id="eye-close" class="fa-regular fa-eye-slash eyes"></i>
                </div>

                <button type="submit" class="btn btn-primary">Login</button>
            </form>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const eyeOpenIcon = document.getElementById('eye-open');
        const eyeCloseIcon = document.getElementById('eye-close');

        eyeOpenIcon.addEventListener('click', () => {
            passwordInput.type = 'text';
            eyeOpenIcon.style.display = 'none';
            eyeCloseIcon.style.display = 'inline';
        });

        eyeCloseIcon.addEventListener('click', () => {
            passwordInput.type = 'password';
            eyeCloseIcon.style.display = 'none';
            eyeOpenIcon.style.display = 'inline';
        });

        // Initialize the icons' visibility
        eyeCloseIcon.style.display = 'none';
    </script>
</body>
</html>