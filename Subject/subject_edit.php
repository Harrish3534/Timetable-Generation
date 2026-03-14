<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $sub_code = trim($_POST['sub_code']);
    $hours_per_week = intval($_POST['hours_per_week']);
    $type = $_POST['type'];
    $semester = intval($_POST['semester']);
    $program = $_POST['program'];

    $stmt = $conn->prepare("UPDATE subjects SET title=?, sub_code=?, hours_per_week=?, type=?, semester=?, program=? WHERE id=?");
    $stmt->bind_param("ssisssi", $title, $sub_code, $hours_per_week, $type, $semester, $program, $id);

    if ($stmt->execute()) {
        // Clear session-cached hours for this subject so timetable shows the updated value
        if (isset($_SESSION['hours_changes'])) {
            unset($_SESSION['hours_changes']['hours_' . $id]);
            unset($_SESSION['hours_changes']['hours_shift1_' . $id]);
            unset($_SESSION['hours_changes']['hours_shift2_' . $id]);
        }
        header("Location: subject.php?type=$program&semester=$semester");
        exit();
    } else {
        $error = "Error updating subject: " . $conn->error;
    }

    $stmt->close();
}

$result = $conn->query("SELECT * FROM subjects WHERE id = $id");
if ($result->num_rows == 0) {
    header("Location: subject.php");
    exit();
}
$subject = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subject - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <script>
        function updateSemesters() {
            const program = document.getElementById('program').value;
            const semesterSelect = document.getElementById('semester');
            const currentSemester = <?php echo $subject['semester']; ?>;

            // Clear existing options
            semesterSelect.innerHTML = '';

            // Set max semesters based on program
            const maxSem = (program === 'UG') ? 6 : 4;

            // Add semester options
            for (let i = 1; i <= maxSem; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = 'Semester ' + i;
                if (i === currentSemester) {
                    option.selected = true;
                }
                semesterSelect.appendChild(option);
            }
        }

        window.onload = function () {
            updateSemesters();
        }
    </script>
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
        <a href="../Generate_Timetable/generated_timetable_view.php" class="tab">
            <span class="tab-icon">📊</span> Generated Timetable
        </a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab">
            <span class="tab-icon">💾</span> Saved Timetables
        </a>
    </div>

    <div class="content">
        <div class="login-box" style="max-width: 600px; margin: 0 auto;">
            <h1>Edit Subject</h1>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="subject_edit.php?id=<?php echo $id; ?>" method="POST">
                <div class="form-group">
                    <label for="title">Subject Title</label>
                    <input type="text" id="title" name="title"
                        value="<?php echo htmlspecialchars($subject['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="sub_code">Subject Code</label>
                    <input type="text" id="sub_code" name="sub_code"
                        value="<?php echo htmlspecialchars($subject['sub_code']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hours_per_week">Hours/Week</label>
                        <input type="number" id="hours_per_week" name="hours_per_week"
                            value="<?php echo $subject['hours_per_week']; ?>" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Subject Type</label>
                        <select id="type" name="type" required>
                            <option value="Core" <?php echo $subject['type'] == 'Core' ? 'selected' : ''; ?>>Core
                            </option>
                            <option value="Common" <?php echo $subject['type'] == 'Common' ? 'selected' : ''; ?>>Common
                            </option>
                            <option value="Lab" <?php echo $subject['type'] == 'Lab' ? 'selected' : ''; ?>>Lab</option>
                            <option value="Allied" <?php echo $subject['type'] == 'Allied' ? 'selected' : ''; ?>>Allied
                            </option>
                            <option value="NM" <?php echo $subject['type'] == 'NM' ? 'selected' : ''; ?>>NM</option>
                            <option value="NME" <?php echo $subject['type'] == 'NME' ? 'selected' : ''; ?>>NME</option>
                            <option value="Project" <?php echo $subject['type'] == 'Project' ? 'selected' : ''; ?>>Project
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="program">Program Type</label>
                        <select id="program" name="program" onchange="updateSemesters()" required>
                            <option value="UG" <?php echo $subject['program'] == 'UG' ? 'selected' : ''; ?>>UG</option>
                            <option value="PG" <?php echo $subject['program'] == 'PG' ? 'selected' : ''; ?>>PG</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select id="semester" name="semester" required>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="flex: 1;">Update Subject</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="window.location.href='subject.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>
<?php $conn->close(); ?>