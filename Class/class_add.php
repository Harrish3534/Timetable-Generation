<?php
require_once '../Config/config.php';
checkLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $shift = trim($_POST['shift']);

    if (empty($name) || empty($shift)) {
        $error = "All fields are required!";
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO classes (name, shift) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $shift);

        if ($stmt->execute()) {
            $success = "Class added successfully! Redirecting...";
            header("refresh:1;url=class.php");
        } else {
            $error = "Error adding class: " . $conn->error;
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
    <title>Add Class - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome,
            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            <a href="../Logout/logout.php" class="logout-icon">⎋</a>
        </div>
    </nav>

    <div class="tabs">
        <a href="../Staff/staff.php" class="tab">
            <span class="tab-icon">👥</span> Staff
        </a>
        <a href="class.php" class="tab active">
            <span class="tab-icon">🎓</span> Classes
        </a>
        <a href="../Subject/subject.php" class="tab">
            <span class="tab-icon">📚</span> Subjects
        </a>
        <a href="../Generate_Timetable/redirect_timetable.php" class="tab">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
        <a href="../Generate_Timetable/generated_timetable_view.php" class="tab">
            <span class="tab-icon">📊</span> Generated Timetable
        </a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab">
            <span class="tab-icon">💾</span> Saved Timetables
        </a>
    </div>

    <div class="content">
        <div class="login-box" style="max-width: 600px; margin: 0 auto;">
            <h1>Add New Class</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="class_add.php" method="POST">
                <div class="form-group">
                    <label for="name">Class Name</label>
                    <input type="text" id="name" name="name" placeholder="e.g., I B.Sc CS" required>
                </div>

                <div class="form-group">
                    <label for="shift">Shift</label>
                    <input type="text" id="shift" name="shift" placeholder="e.g., Shift 1 or PG / Regular" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Add Class</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="window.location.href='class.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>

