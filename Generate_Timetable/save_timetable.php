<?php
require_once '../Config/config.php';
checkLogin();

header('Content-Type: application/json');

$conn = getConnection();

// Auto-create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS `saved_timetables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `semester` VARCHAR(10) NOT NULL,
  `created_at` DATETIME DEFAULT NOW(),
  `updated_at` DATETIME DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `saved_timetable_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `saved_timetable_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `class_name` VARCHAR(100),
  `shift` VARCHAR(30),
  `semester` INT NOT NULL,
  `day` VARCHAR(20) NOT NULL,
  `hour` VARCHAR(20) NOT NULL,
  `subject_id` VARCHAR(50) DEFAULT NULL,
  `subject_title` VARCHAR(150) DEFAULT NULL,
  `subject_short_name` VARCHAR(50) DEFAULT NULL,
  `subject_type` VARCHAR(50) DEFAULT NULL,
  `staff_id` INT DEFAULT NULL,
  `staff_name` VARCHAR(100) DEFAULT NULL,
  `staff_code` VARCHAR(20) DEFAULT NULL,
  `is_manual` TINYINT(1) DEFAULT 0,
  INDEX idx_saved_tt (saved_timetable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Add is_manual column to existing tables that pre-date this feature
$conn->query("ALTER TABLE `saved_timetable_slots` ADD COLUMN IF NOT EXISTS `is_manual` TINYINT(1) DEFAULT 0");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST required']);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$semester_filter = isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : (isset($_POST['semester']) ? $_POST['semester'] : 'odd');

if (empty($name)) {
    echo json_encode(['success' => false, 'error' => 'Name is required']);
    exit;
}

// Get timetables from session
$timetables = isset($_SESSION['current_timetables']) ? $_SESSION['current_timetables'] : [];

if (empty($timetables)) {
    echo json_encode(['success' => false, 'error' => 'No timetable data found. Please generate a timetable first.']);
    exit;
}

// Insert header record
$esc_name = $conn->real_escape_string($name);
$esc_sem  = $conn->real_escape_string($semester_filter);
$conn->query("INSERT INTO saved_timetables (name, semester) VALUES ('$esc_name', '$esc_sem')");
$saved_id = $conn->insert_id;

if (!$saved_id) {
    echo json_encode(['success' => false, 'error' => 'Failed to save timetable header']);
    exit;
}

$days  = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

// Build a lookup set of manually-allocated slots: class_index -> shift_group -> day_hour
// We find each class's index using the same sequence pattern as timetable.php
$class_sequence = [
    ['pattern' => 'I B.Sc%'],
    ['pattern' => 'II B.Sc%'],
    ['pattern' => 'III B.Sc%'],
    ['pattern' => 'I M.Sc%'],
    ['pattern' => 'II M.Sc%'],
];
$manual_slots_set = []; // [class_id][day_hour] = true
$manual_allocs = isset($_SESSION['manual_allocations']) ? $_SESSION['manual_allocations'] : [];
foreach ($timetables as $tt_data) {
    $cls = $tt_data['class'];
    $cls_id = intval($cls['id']);
    // Find class_index by name matching
    $class_idx = -1;
    foreach ($class_sequence as $idx => $seq) {
        $pattern = str_replace('%', '', $seq['pattern']);
        if (strpos($cls['name'], $pattern) === 0) {
            $class_idx = $idx;
            break;
        }
    }
    if ($class_idx < 0) continue;
    // Determine shift group
    $shift_group = '';
    if (stripos($cls['shift'], 'Shift 1') !== false) $shift_group = 'shift1';
    elseif (stripos($cls['shift'], 'Shift 2') !== false) $shift_group = 'shift2';
    if (isset($manual_allocs[$class_idx][$shift_group])) {
        foreach ($manual_allocs[$class_idx][$shift_group] as $alloc) {
            $manual_slots_set[$cls_id][$alloc['day'] . '||' . $alloc['hour']] = true;
        }
    }
    // M.Sc classes may use empty shift_group key
    if (isset($manual_allocs[$class_idx][''])) {
        foreach ($manual_allocs[$class_idx][''] as $alloc) {
            $manual_slots_set[$cls_id][$alloc['day'] . '||' . $alloc['hour']] = true;
        }
    }
}

// Build batch INSERT for performance
$values = [];
foreach ($timetables as $tt_data) {
    $cls        = $tt_data['class'];
    $class_id   = intval($cls['id']);
    $class_name = $conn->real_escape_string($cls['name']);
    $shift      = $conn->real_escape_string($cls['shift']);
    $sem_num    = intval($tt_data['semester']);
    $tt         = $tt_data['timetable'];

    foreach ($days as $day) {
        foreach ($hours as $hour) {
            $subj = isset($tt[$day][$hour]) ? $tt[$day][$hour] : null;

            $esc_day  = $conn->real_escape_string($day);
            $esc_hour = $conn->real_escape_string($hour);

            $subject_id  = $subj ? "'" . $conn->real_escape_string((string)$subj['id'])          . "'" : 'NULL';
            $subj_title  = $subj ? "'" . $conn->real_escape_string((string)$subj['title'])        . "'" : 'NULL';
            $subj_short  = $subj ? "'" . $conn->real_escape_string((string)($subj['short_name'] ?? $subj['title'])) . "'" : 'NULL';
            $subj_type   = $subj ? "'" . $conn->real_escape_string((string)$subj['type'])         . "'" : 'NULL';
            $staff_id    = ($subj && isset($subj['staff_id'])   && $subj['staff_id'])   ? intval($subj['staff_id'])                                 : 'NULL';
            $staff_name  = ($subj && isset($subj['staff_name']) && $subj['staff_name']) ? "'" . $conn->real_escape_string($subj['staff_name']) . "'" : 'NULL';
            $staff_code  = ($subj && isset($subj['staff_code']) && $subj['staff_code']) ? "'" . $conn->real_escape_string($subj['staff_code']) . "'" : 'NULL';
            $is_manual   = isset($manual_slots_set[$class_id][$day . '||' . $hour]) ? 1 : 0;

            $values[] = "($saved_id, $class_id, '$class_name', '$shift', $sem_num, '$esc_day', '$esc_hour', $subject_id, $subj_title, $subj_short, $subj_type, $staff_id, $staff_name, $staff_code, $is_manual)";
        }
    }
}

if (!empty($values)) {
    $sql = "INSERT INTO saved_timetable_slots 
        (saved_timetable_id, class_id, class_name, shift, semester, day, hour,
         subject_id, subject_title, subject_short_name, subject_type,
         staff_id, staff_name, staff_code, is_manual)
        VALUES " . implode(',', $values);
    $conn->query($sql);
}

echo json_encode(['success' => true, 'id' => $saved_id, 'name' => $name]);
$conn->close();
?>
