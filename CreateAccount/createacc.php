<?php
require_once '../Config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters!";
    } else {
        $conn = getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, surname, email, phone, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $surname, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $success = "Account created successfully! Redirecting to login...";
                header("refresh:2;url=../index.html");
            } else {
                $error = "Error creating account!";
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
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
    </style>
</head>

<body>
    <div class="container">
        <div class="login-box">
            <h1>Create Account</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="createacc.php" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="surname">Surname</label>
                        <input type="text" id="surname" name="surname" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <i id="eye-open" class="fa-regular fa-eye eyes"></i>
                    <i id="eye-close" class="fa-regular fa-eye-slash eyes"></i>
                    <small>8 chars, 1 Upper, 1 Special, 1 Num</small>
                </div>

                <div class="form-group password-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i id="eye-open-confirm" class="fa-regular fa-eye eyes"></i>
                    <i id="eye-close-confirm" class="fa-regular fa-eye-slash eyes"></i>
                </div>

                <button type="submit" class="btn btn-success">Create Account</button>

                <p class="text-center">
                    <a href="../index.html">Already have an account? Login</a>
                </p>
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

        // Confirm Password Logic
        const confirmPasswordInput = document.getElementById('confirm_password');
        const eyeOpenIconConfirm = document.getElementById('eye-open-confirm');
        const eyeCloseIconConfirm = document.getElementById('eye-close-confirm');

        eyeOpenIconConfirm.addEventListener('click', () => {
            confirmPasswordInput.type = 'text';
            eyeOpenIconConfirm.style.display = 'none';
            eyeCloseIconConfirm.style.display = 'inline';
        });

        eyeCloseIconConfirm.addEventListener('click', () => {
            confirmPasswordInput.type = 'password';
            eyeCloseIconConfirm.style.display = 'none';
            eyeOpenIconConfirm.style.display = 'inline';
        });

        // Initialize the confirm icons' visibility
        eyeCloseIconConfirm.style.display = 'none';

    </script>

</body>

</html>