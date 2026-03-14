<?php
require_once '../Config/config.php';
checkLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $sub_code = trim($_POST['sub_code']);
    $hours_per_week = intval($_POST['hours_per_week']);
    $type = $_POST['type'];
    $semester = intval($_POST['semester']);
    $program = $_POST['program'];
    $year = ceil($semester / 2); // Calculate year from semester

    if (empty($title) || empty($sub_code)) {
        $error = "All fields are required!";
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("INSERT INTO subjects (title, sub_code, hours_per_week, type, semester, program, year) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssi", $title, $sub_code, $hours_per_week, $type, $semester, $program, $year);

        if ($stmt->execute()) {
            $success = "Subject added successfully! Redirecting...";
            header("refresh:1;url=subject.php?type=$program&semester=$semester");
        } else {
            $error = "Error adding subject: " . $conn->error;
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
    <title>Add Subject - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <script>
        function updateSemesters() {
            const program = document.getElementById('program').value;
            const semesterSelect = document.getElementById('semester');

            // Clear existing options
            semesterSelect.innerHTML = '';

            // Set max semesters based on program
            const maxSem = (program === 'UG') ? 6 : 4;

            // Add semester options
            for (let i = 1; i <= maxSem; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = 'Semester ' + i;
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
            <h1>Add New Subject</h1>

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

            <form action="subject_add.php" method="POST">
                <div class="form-group">
                    <label for="title">Subject Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="sub_code">Subject Code</label>
                    <input type="text" id="sub_code" name="sub_code" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hours_per_week">Hours/Week</label>
                        <input type="number" id="hours_per_week" name="hours_per_week" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="type">Subject Type</label>
                        <select id="type" name="type" required>
                            <option value="Core">Core</option>
                            <option value="Common">Common</option>
                            <option value="Lab">Lab</option>
                            <option value="Allied">Allied</option>
                            <option value="NM">NM</option>
                            <option value="NME">NME</option>
                            <option value="Project">Project</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="program">Program Type</label>
                        <select id="program" name="program" onchange="updateSemesters()" required>
                            <option value="UG">UG</option>
                            <option value="PG">PG</option>
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
                    <button type="submit" class="btn btn-success" style="flex: 1;">Add Subject</button>
                    <button type="button" class="btn btn-secondary" style="flex: 1;"
                        onclick="window.location.href='subject.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>