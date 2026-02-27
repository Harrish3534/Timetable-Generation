<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id = $id");
    $semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
    $type = isset($_GET['type']) ? $_GET['type'] : 'UG';
    header("Location: subject.php?type=$type&semester=$semester");
    exit();
}

$semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'UG';

// Query to get subjects based on program (UG/PG) and semester
$query = "SELECT * FROM subjects WHERE program = '$type' AND semester = $semester ORDER BY sort_order, id";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - GAC Timetable</title>
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
        <a href="../Class/class.php" class="tab">
            <span class="tab-icon">🎓</span> Classes
        </a>
        <a href="subject.php" class="tab active">
            <span class="tab-icon">📚</span> Subjects
        </a>
        <a href="../Generate_Timetable/redirect_timetable.php" class="tab">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
    </div>

    <div class="content">
        <div class="page-title">
            <h2>Subject Details</h2>
            <div class="filter-controls">
                <button class="btn <?php echo $type == 'UG' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=UG&semester=1'">UG</button>
                <button class="btn <?php echo $type == 'PG' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=PG&semester=1'">PG</button>
                <select class="filter-select"
                    onchange="window.location.href='subject.php?type=<?php echo $type; ?>&semester=' + this.value">
                    <?php
                    $max_sem = ($type == 'UG') ? 6 : 4;
                    for ($i = 1; $i <= $max_sem; $i++):
                        ?>
                        <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                            Semester
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-add" onclick="window.location.href='subject_add.php'">+ Add</button>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>TITLE</th>
                    <th>SUB CODE</th>
                    <th>HRS/WEEK</th>
                    <th>TYPE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($row['title']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['sub_code']); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['hours_per_week']); ?>
                            </td>
                            <td><span class="badge badge-<?php echo strtolower($row['type']); ?>">
                                    <?php echo htmlspecialchars($row['type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="subject_edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="subject.php?delete=<?php echo $row['id']; ?>&type=<?php echo $type; ?>&semester=<?php echo $semester; ?>"
                                    class="btn-action btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #6b7280;">
                            No subjects found for
                            <?php echo $type; ?> Semester
                            <?php echo $semester; ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php $conn->close(); ?>