<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $shift = $_POST['shift'];

    $stmt = $conn->prepare("UPDATE classes SET name=?, shift=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $shift, $id);

    if ($stmt->execute()) {
        header("Location: class.php");
        exit();
    } else {
        $error = "Error updating class!";
    }

    $stmt->close();
}

$result = $conn->query("SELECT * FROM classes WHERE id = $id");
if ($result->num_rows == 0) {
    header("Location: class.php");
    exit();
}
$class = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
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
            <h1>Edit Class</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="class_edit.php?id=<?php echo $id; ?>" method="POST">
                <div class="form-group">
                    <label for="name">Class Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($class['name']); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="shift">Shift</label>
                    <input type="text" id="shift" name="shift" value="<?php echo htmlspecialchars($class['shift']); ?>"
                        required>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Update Class</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="window.location.href='class.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>