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

$slots_result = $conn->query("SELECT * FROM saved_timetable_slots WHERE saved_timetable_id = $id ORDER BY class_id, day, hour");
$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

$timetable_data = [];
$class_info = [];

while ($slot = $slots_result->fetch_assoc()) {
    $cid = $slot['class_id'];
    if (!isset($class_info[$cid])) {
        $class_info[$cid] = ['name' => $slot['class_name'], 'shift' => $slot['shift'], 'semester' => $slot['semester']];
    }
    // We only take the first instance if there are multiple comma-separated hours on the SAME row.
    // Wait, in this system, lab slots might span multiple hours, but each hour is stored in the `hour` column?
    // Let's explode the hours just in case a slot has multiple hours in the db string like 'I HOUR, II HOUR'
    $slot_hours = array_map('trim', explode(',', $slot['hour']));
    foreach ($slot_hours as $h) {
        $timetable_data[$cid][$slot['day']][$h] = $slot;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Timetable - <?php echo htmlspecialchars($header['name']); ?></title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
    <style>
        @media print {
            .no-print { display: none !important; }
            .timetable-container { page-break-after: always; }
        }
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
            <h2 style="margin:0; flex-grow:1; color:#1f2937;">Class Timetable - <?php echo htmlspecialchars($header['name']); ?></h2>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print All Classes</button>
            <button onclick="window.close()" class="btn btn-secondary">✖ Close Tab</button>
        </div>

        <?php foreach ($class_info as $cid => $cinfo): ?>
            <div class="timetable-container">
                <div class="timetable-title">
                    <h2>Government Arts College (Autonomous), Coimbatore-18</h2>
                    <h3>PG & Research Department of Computer Science</h3>
                    <p>Time Table 2025-26 <?php echo ucfirst($header['semester']); ?> Semester</p>
                    <p><strong><?php echo htmlspecialchars($cinfo['name']); ?>
                            (<?php echo htmlspecialchars($cinfo['shift']); ?>)</strong> - Semester <?php echo htmlspecialchars($cinfo['semester']); ?>
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
                                        <?php if (!empty($timetable_data[$cid][$day][$hour]['subject_short_name'])): 
                                            $slot = $timetable_data[$cid][$day][$hour];
                                        ?>
                                            <div class="subject-display">
                                                <?php echo htmlspecialchars($slot['subject_short_name']); ?>
                                                <?php if (!empty($slot['staff_code'])): ?>
                                                    <br><small>(<?php echo htmlspecialchars($slot['staff_code']); ?>)</small>
                                                <?php endif; ?>
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
