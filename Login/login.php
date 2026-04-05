<?php
require_once '../Config/config.php';
$conn = getConnection();

$error = null;

function ensureLoginSchema(mysqli $conn): void
{
    $seededTruncatedHash = '$2y$10$DngEusYVyqE89F7x9o5UeeAMWG6lZLfLj5HVpaxk6Ki';
    $fixedSeedHash = '$2y$10$Sa5EnEmT8soH4tDANju8tuZ1bnJ93/n0HTJAIadm7TB8GseQyjuJK';

    // Bcrypt hashes need 60 characters, so older 50-char schema breaks login.
    $conn->query("ALTER TABLE users MODIFY password VARCHAR(255) DEFAULT NULL");

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $seedUser = 'csadmin';
    $stmt->bind_param("s", $seedUser);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($user['password'] === $seededTruncatedHash) {
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $fixedSeedHash, $user['id']);
            $update->execute();
            $update->close();
        }
    }

    $stmt->close();
}

ensureLoginSchema($conn);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['username'];
                header("Location: ../Staff/staff.php");
                exit();
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Invalid username or password!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GAC Timetable - Login</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
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
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter username"
                        value="<?php echo htmlspecialchars($username ?? ''); ?>" required>
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
