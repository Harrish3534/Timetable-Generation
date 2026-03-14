<?php
require_once '../Config/config.php';
checkLogin();

// Always serve fresh data - no browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM classes WHERE id = $id");
    header("Location: class.php");
    exit();
}

// Custom ordering for classes
$result = $conn->query("SELECT * FROM classes ORDER BY 
    CASE 
        WHEN name LIKE 'I B.Sc%' AND shift LIKE 'Shift 1%' THEN 1
        WHEN name LIKE 'I B.Sc%' AND shift LIKE 'Shift 2%' THEN 2
        WHEN name LIKE 'II B.Sc%' AND shift LIKE 'Shift 1%' THEN 3
        WHEN name LIKE 'II B.Sc%' AND shift LIKE 'Shift 2%' THEN 4
        WHEN name LIKE 'III B.Sc%' AND shift LIKE 'Shift 1%' THEN 5
        WHEN name LIKE 'III B.Sc%' AND shift LIKE 'Shift 2%' THEN 6
        WHEN name LIKE 'I M.Sc%' THEN 7
        WHEN name LIKE 'II M.Sc%' THEN 8
        ELSE 99
    END,
    name, shift");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes - GAC Timetable</title>
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
        <div class="page-title">
            <h2>Classes</h2>
            <button class="btn btn-add" onclick="window.location.href='class_add.php'">+ Add Class</button>
        </div>

        <div class="card-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <h3>
                        <?php echo htmlspecialchars($row['name']); ?>
                    </h3>
                    <p class="badge">
                        <?php echo htmlspecialchars($row['shift']); ?>
                    </p>
                    <div class="card-actions">
                        <a href="class_edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">Edit</a>
                        <a href="class.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete"
                            onclick="return confirm('Are you sure you want to delete this class?')">Delete</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>
