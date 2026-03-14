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
    $conn->query("DELETE FROM staff WHERE id = $id");
    header("Location: staff.php");
    exit();
}

// Order by designation hierarchy and then by name
$result = $conn->query("SELECT * FROM staff ORDER BY 
    CASE 
        WHEN designation LIKE '%Head%' THEN 1
        WHEN designation LIKE '%Associate Professor%' AND designation NOT LIKE '%Assistant%' THEN 2
        WHEN designation LIKE '%Assistant Professor%' THEN 3
        WHEN designation LIKE '%Guest Lecturer%' THEN 4
        ELSE 5
    END,
    id ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Details - GAC Timetable</title>
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
        <div class="page-title">
            <h2>Staff Details</h2>
            <a href="staff_add.php" class="btn btn-add">+ Add Staff</a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>NAME</th>
                    <th>DESIGNATION</th>
                    <th>QUALIFICATION</th>
                    <th>SHORT CODE</th>
                    <th>MAXIMUM HOURS</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="avatar">
                                <?php echo htmlspecialchars($row['short_code']); ?>
                            </div>
                            <?php echo htmlspecialchars($row['name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['designation']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['qualification']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['short_code']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($row['Hours']); ?>
                        </td>
                        <td>
                            <a href="staff_edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">Edit</a>
                            <a href="staff.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete"
                                onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php $conn->close(); ?>