<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Get semester filter
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : (isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd');

// Get timetables from session
$timetables = isset($_SESSION['current_timetables']) ? $_SESSION['current_timetables'] : [];

// Mark current page
$_SESSION['current_page'] = 'staff_timetable';

if (empty($timetables)) {
    // Redirect back if no timetables available
    header('Location: timetable.php?semester=' . $semester_filter);
    exit();
}

$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

// Build staff schedules from class timetables
$staff_schedules = [];
$staff_info = [];

foreach ($timetables as $tt_data) {
    $class_name = $tt_data['class']['name'] . ' (' . $tt_data['class']['shift'] . ')';

    foreach ($days as $day) {
        foreach ($hours as $hour) {
            if (isset($tt_data['timetable'][$day][$hour]) && $tt_data['timetable'][$day][$hour]) {
                $subject = $tt_data['timetable'][$day][$hour];

                if (isset($subject['staff_id']) && isset($subject['staff_name'])) {
                    $staff_id = $subject['staff_id'];

                    // Store staff info
                    if (!isset($staff_info[$staff_id])) {
                        // Fetch designation from database
                        $staff_query = "SELECT designation FROM staff WHERE id = $staff_id";
                        $staff_result = $conn->query($staff_query);
                        $designation = '';
                        if ($staff_result && $staff_row = $staff_result->fetch_assoc()) {
                            $designation = $staff_row['designation'];
                        }

                        $staff_info[$staff_id] = [
                            'name' => $subject['staff_name'],
                            'code' => isset($subject['staff_code']) ? $subject['staff_code'] : '',
                            'designation' => $designation
                        ];
                    }

                    // Initialize staff schedule
                    if (!isset($staff_schedules[$staff_id])) {
                        $staff_schedules[$staff_id] = [];
                        foreach ($days as $d) {
                            $staff_schedules[$staff_id][$d] = [];
                            foreach ($hours as $h) {
                                $staff_schedules[$staff_id][$d][$h] = [];
                            }
                        }
                    }

                    // Add class to this time slot
                    $staff_schedules[$staff_id][$day][$hour][] = [
                        'class' => $class_name,
                        'subject' => $subject['short_name']
                    ];
                }
            }
        }
    }
}

// Sort staff by designation hierarchy, then by name
uksort($staff_schedules, function ($a, $b) use ($staff_info) {
    // Define designation priority
    $designation_order = [
        'Associate Professor & Head' => 1,
        'Associate Professor' => 2,
        'Assistant Professor' => 3,
        'Guest Lecturer' => 4
    ];

    $designation_a = $staff_info[$a]['designation'];
    $designation_b = $staff_info[$b]['designation'];

    // Get priority for each designation (default to 99 for unknown)
    $priority_a = isset($designation_order[$designation_a]) ? $designation_order[$designation_a] : 99;
    $priority_b = isset($designation_order[$designation_b]) ? $designation_order[$designation_b] : 99;

    // First sort by designation priority
    if ($priority_a != $priority_b) {
        return $priority_a - $priority_b;
    }

    // If same designation, sort by name
    return strcmp($staff_info[$a]['name'], $staff_info[$b]['name']);
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Timetable - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .timetable-container {
                page-break-after: always;
            }
        }

        .staff-name-header {
            font-weight: bold;
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px;
            border: 1px solid #dee2e6;
        }

        .class-info {
            font-size: 0.85em;
            line-height: 1.4;
        }

        .class-entry {
            margin-bottom: 4px;
        }

        .subject-label {
            font-weight: 600;
            color: #2563eb;
        }
    </style>
</head>

<body>
    <nav class="navbar no-print">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome,
            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            <a href="../Logout/logout.php" class="logout-icon">⎋</a>
        </div>
    </nav>

    <div class="tabs no-print">
        <a href="../Staff/staff.php" class="tab">
            <span class="tab-icon">👥</span> Staff
        </a>
        <a href="../Class/class.php" class="tab">
            <span class="tab-icon">🎓</span> Classes
        </a>
        <a href="../Subject/subject.php" class="tab">
            <span class="tab-icon">📚</span> Subjects
        </a>
        <a href="redirect_timetable.php" class="tab active">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
    </div>

    <div class="content">
        <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Timetables</button>
            <button onclick="window.location.href='generate_timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">← Back</button>
            <button onclick="window.location.href='generate_timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">🔄 Regenerate</button>
        </div>

        <?php foreach ($staff_schedules as $staff_id => $schedule): ?>
            <div class="timetable-container">
                <div class="timetable-title">
                    <h2>Government Arts College (Autonomous), Coimbatore-18</h2>
                    <h3>PG & Research Department of Computer Science</h3>
                    <p>Time Table 2025-26
                        <?php echo ucfirst($semester_filter); ?> Semester
                    </p>
                    <p><strong>Staff:
                            <?php echo htmlspecialchars($staff_info[$staff_id]['name']); ?>
                            <?php if (!empty($staff_info[$staff_id]['code'])): ?>
                                (
                                <?php echo htmlspecialchars($staff_info[$staff_id]['code']); ?>)
                            <?php endif; ?>
                        </strong>
                    </p>
                </div>

                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th class="day-header">DAY/HOUR</th>
                            <th class="hour-header">I HOUR</th>
                            <th class="hour-header">II HOUR</th>
                            <th class="hour-header">III HOUR</th>
                            <th class="hour-header">IV HOUR</th>
                            <th class="hour-header">V HOUR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <td class="day-header">
                                    <?php echo $day; ?>
                                </td>
                                <?php foreach ($hours as $hour): ?>
                                    <td class="subject-cell">
                                        <?php if (!empty($schedule[$day][$hour])): ?>
                                            <div class="class-info">
                                                <?php foreach ($schedule[$day][$hour] as $entry): ?>
                                                    <div class="class-entry">
                                                        <span class="subject-label">
                                                            <?php echo htmlspecialchars($entry['subject']); ?>
                                                        </span>
                                                        <br>
                                                        <?php echo htmlspecialchars($entry['class']); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div>-</div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>
<?php $conn->close(); ?>