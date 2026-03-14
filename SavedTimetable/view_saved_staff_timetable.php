<?php
require_once '../Config/config.php';
checkLogin();
$conn = getConnection();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: saved_timetable.php");
    exit;
}

$header = $conn->query("SELECT * FROM saved_timetables WHERE id = $id")->fetch_assoc();
if (!$header) {
    header("Location: saved_timetable.php");
    exit;
}

// Fetch all allocated slots
$slots_result = $conn->query("SELECT * FROM saved_timetable_slots WHERE saved_timetable_id = $id AND staff_id IS NOT NULL");
$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

$staff_schedules = [];
$staff_info = [];

while ($slot = $slots_result->fetch_assoc()) {
    $staff_id = $slot['staff_id'];
    
    // Normalize shift string to make it shorter e.g. 'Shift 1' -> 'S1'
    $shift_short = str_replace(['Shift 1', 'Shift 2'], ['SI', 'SII'], $slot['shift']);
    $class_name = $slot['class_name'] . ' (' . $shift_short . ')';
    $day = $slot['day'];
    
    if (!isset($staff_info[$staff_id])) {
        $staff_query = "SELECT designation FROM staff WHERE id = $staff_id";
        $staff_result = $conn->query($staff_query);
        $designation = '';
        if ($staff_result && $staff_row = $staff_result->fetch_assoc()) {
            $designation = $staff_row['designation'];
        }

        $staff_info[$staff_id] = [
            'name' => $slot['staff_name'],
            'code' => $slot['staff_code'],
            'designation' => $designation
        ];
    }
    
    if (!isset($staff_schedules[$staff_id])) {
        $staff_schedules[$staff_id] = [];
        foreach ($days as $d) {
            $staff_schedules[$staff_id][$d] = [];
            foreach ($hours as $h) {
                $staff_schedules[$staff_id][$d][$h] = [];
            }
        }
    }
    
    $slot_hours = array_map('trim', explode(',', $slot['hour']));
    foreach ($slot_hours as $h) {
        if (in_array($h, $hours)) {
            $staff_schedules[$staff_id][$day][$h][] = [
                'class' => $class_name,
                'subject' => $slot['subject_short_name']
            ];
        }
    }
}

// Sort staff logic strictly based on staff_timetable.php order
uksort($staff_schedules, function ($a, $b) use ($staff_info) {
    $custom_order = [
        'robert' => 1, 'chitra' => 2, 'devapriya' => 3, 'saraswathi' => 4,
        'malathi' => 5, 'balamurugan' => 6, 'buvaneshwari' => 7, 'mahendran' => 8,
        'yuvaraj' => 9, 'rajasekar' => 10, 'rathika' => 11, 'anbazhagan' => 12
    ];
    
    $name_a = strtolower($staff_info[$a]['name']);
    $priority_a = 99;
    foreach ($custom_order as $keyword => $rank) {
        if (strpos($name_a, $keyword) !== false) { $priority_a = $rank; break; }
    }

    $name_b = strtolower($staff_info[$b]['name']);
    $priority_b = 99;
    foreach ($custom_order as $keyword => $rank) {
        if (strpos($name_b, $keyword) !== false) { $priority_b = $rank; break; }
    }

    if ($priority_a != $priority_b) return $priority_a - $priority_b;

    // Define designation priority fallback
    $designation_order = [
        'Associate Professor & Head' => 1, 'Associate Professor' => 2,
        'Assistant Professor' => 3, 'Guest Lecturer' => 4
    ];

    $designation_a = $staff_info[$a]['designation'];
    $designation_b = $staff_info[$b]['designation'];

    $d_priority_a = isset($designation_order[$designation_a]) ? $designation_order[$designation_a] : 99;
    $d_priority_b = isset($designation_order[$designation_b]) ? $designation_order[$designation_b] : 99;

    if ($d_priority_a != $d_priority_b) return $d_priority_a - $d_priority_b;

    return strcmp($staff_info[$a]['name'], $staff_info[$b]['name']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Timetable - <?php echo htmlspecialchars($header['name']); ?></title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <style>
        @media print {
            .no-print { display: none !important; }
            .timetable-container { page-break-after: always; }
        }
        .class-info { font-size: 0.85em; line-height: 1.4; }
        .class-entry { margin-bottom: 4px; }
        .subject-label { font-weight: 600; color: #2563eb; }
    </style>
</head>
<body>
    <nav class="navbar no-print">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            <a href="../Logout/logout.php" class="logout-icon">⎋</a>
        </div>
    </nav>
    <div class="tabs no-print">
        <a href="../Staff/staff.php" class="tab"><span class="tab-icon">👥</span> Staff</a>
        <a href="../Class/class.php" class="tab"><span class="tab-icon">🎓</span> Classes</a>
        <a href="../Subject/subject.php" class="tab"><span class="tab-icon">📚</span> Subjects</a>
        <a href="../Generate_Timetable/redirect_timetable.php" class="tab"><span class="tab-icon">📅</span> Class
            Timetable</a>
        <a href="../Generate_Timetable/generated_timetable_view.php" class="tab"><span class="tab-icon">📊</span>
            Generated Timetable</a>
        <a href="saved_timetable.php" class="tab active"><span class="tab-icon">💾</span> Saved Timetables</a>
    </div>

    <div class="content">
        <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 10px; align-items:center;">
            <h2 style="margin:0; flex-grow:1; color:#1f2937;">Staff Timetable - <?php echo htmlspecialchars($header['name']); ?></h2>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print All Staff</button>
            <button onclick="window.close()" class="btn btn-secondary">✖ Close Tab</button>
        </div>

        <?php foreach ($staff_schedules as $staff_id => $schedule): ?>
            <div class="timetable-container">
                <div class="timetable-title">
                    <h2>Government Arts College (Autonomous), Coimbatore-18</h2>
                    <h3>PG & Research Department of Computer Science</h3>
                    <p>Time Table 2025-26 <?php echo ucfirst($header['semester']); ?> Semester</p>
                    <p><strong>Staff: <?php echo htmlspecialchars($staff_info[$staff_id]['name']); ?>
                            <?php if (!empty($staff_info[$staff_id]['code'])): ?>
                                (<?php echo htmlspecialchars($staff_info[$staff_id]['code']); ?>)
                            <?php endif; ?>
                        </strong>
                    </p>
                </div>

                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th class="day-header">DAY/HOUR</th>
                            <?php foreach ($hours as $hour): ?>
                                <th class="hour-header"><?php echo $hour; ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($days as $day): ?>
                            <tr>
                                <td class="day-header"><?php echo $day; ?></td>
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
