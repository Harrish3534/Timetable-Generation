<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $designation = $_POST['designation'];
    $qualification = $_POST['qualification'];
    $short_code = $_POST['short_code'];
    $max_hours = intval($_POST['max_hours']);

    $stmt = $conn->prepare("UPDATE staff SET name=?, designation=?, qualification=?, short_code=?, Hours=? WHERE id=?");
    $stmt->bind_param("ssssii", $name, $designation, $qualification, $short_code, $max_hours, $id);

    if ($stmt->execute()) {
        header("Location: staff.php");
        exit();
    } else {
        $error = "Error updating staff!";
    }

    $stmt->close();
}

$result = $conn->query("SELECT * FROM staff WHERE id = $id");
if ($result->num_rows == 0) {
    header("Location: staff.php");
    exit();
}
$staff = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - GAC Timetable</title>
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
        <a href="staff.php" class="tab active">
            <span class="tab-icon">👥</span> Staff
        </a>
        <a href="../Class/class.php" class="tab">
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
            <h1>Edit Staff</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="staff_edit.php?id=<?php echo $id; ?>" method="POST">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($staff['name']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="designation">Designation</label>
                    <input type="text" id="designation" name="designation"
                        value="<?php echo htmlspecialchars($staff['designation']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="qualification">Qualification</label>
                    <input type="text" id="qualification" name="qualification"
                        value="<?php echo htmlspecialchars($staff['qualification']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="short_code">Short Code</label>
                    <input type="text" id="short_code" name="short_code"
                        value="<?php echo htmlspecialchars($staff['short_code']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="max_hours">Maximum Hours</label>
                    <input type="number" id="max_hours" name="max_hours"
                        value="<?php echo htmlspecialchars($staff['Hours']); ?>" min="1" max="30" required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Update Staff</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="window.location.href='staff.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>

