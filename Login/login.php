<?php
require_once '../Config/config.php';

$email = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = getConnection();

    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ? OR phone = ?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            header("Location: ../Staff/staff.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found!";
    }

    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAC Timetable - Login</title>
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
            <h1>Staff Login</h1>
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email or Phone</label>
                    <input type="text" id="email" name="email" placeholder="Enter email or phone"
                        value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group password-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                    <i id="eye-open" class="fa-regular fa-eye eyes"></i>
                    <i id="eye-close" class="fa-regular fa-eye-slash eyes"></i>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px; text-align: center;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">Login</button>

                <p class="text-center">
                    <a href="../CreateAccount/createacc.php">Don't have an account? Sign Up</a>
                </p>
            </form>
        </div>
    </div>
</body>
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

</html>