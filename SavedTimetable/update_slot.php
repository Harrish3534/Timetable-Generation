<?php
require_once '../Config/config.php';
checkLogin();
header('Content-Type: application/json');

$conn = getConnection();
$action = $_POST['action'] ?? '';

// Function to get short name for subject
function getShortName($title, $type, $semester = null, $subject = null)
{
    if ($subject && !empty($subject['short_name'])) {
        return strtoupper($subject['short_name']);
    }

    if ($type === 'Allied')
        return 'ALLIED';
    if ($type === 'NME')
        return 'NME';

    $title_lower = strtolower($title);

    if (($semester == 5 || $semester == 6) && stripos($title_lower, 'elective') !== false) {
        return 'NME';
    }

    $mappings = [
        'programming methodology' => 'PM',
        'digital computer fundamentals' => 'DCF',
        'digital image processing' => 'DIP',
        'cloud computing' => 'CC',
        'web programming essentials' => 'WPE',
        'mobile application development' => 'MAD',
        'software engineering' => 'SE',
        'data structures' => 'DS',
        'internet technologies' => 'IT',
        'operating system' => 'OS',
        'computer graphics' => 'CG',
        'computer networks' => 'CN',
        'database management systems' => 'DBMS',
        'database management system' => 'DBMS',
        'data communication and networks' => 'DC & CN',
        'advanced java programming' => 'AJP',
        'data structures and algorithms' => 'DSA',
        'computer system architecture' => 'CSA',
        'linux shell programming' => 'LINUX',
        'c++ programming' => 'C++',
        'c# programming' => 'C#',
        'value education' => 'VE',
        'python programming' => 'PYTHON',
        'algorithms' => 'ALG',
        'open source computing' => 'OSC',
        'ai and machine learning' => 'AI',
        'data science using python' => 'DS',
        'data mining with r' => 'DM&R',
        'open source technology' => 'OST',
        'programming in java' => 'JAVA',
        'java programming' => 'JAVA',
        'environmental studies' => 'EVS',
        'environmental science' => 'EVS',
        'tamil' => 'TAMIL',
        'english' => 'ENGLISH',
        'naan mudhalvan' => 'NM',
        'elective' => 'ELECTIVE',
        'project viva voce' => 'PROJECT',
    ];

    if ($type === 'Lab') {
        if (stripos($title_lower, 'dip lab') !== false || stripos($title_lower, 'digital image') !== false) {
            return 'DIP LAB';
        }
        if (stripos($title_lower, 'image processing') !== false || stripos($title_lower, 'dip') !== false) {
            return 'DIP LAB';
        }
        if (stripos($title_lower, 'database') !== false || stripos($title_lower, 'dbms') !== false) {
            return 'DBMS LAB';
        }
    }

    foreach ($mappings as $key => $value) {
        if (stripos($title_lower, $key) !== false) {
            return $type === 'Lab' ? $value . ' LAB' : $value;
        }
    }

    if (preg_match('/\(([^)]+)\)/', $title, $matches)) {
        $shortName = strtoupper($matches[1]);
        return $type === 'Lab' ? $shortName . ' LAB' : $shortName;
    }

    if ($type === 'Lab') {
        $words = explode(' ', $title);
        $abbrev = '';
        foreach ($words as $word) {
            if (strlen($word) > 2 && !in_array(strtolower($word), ['lab', 'using', 'and', 'the', 'of', 'in'])) {
                $abbrev .= strtoupper(substr($word, 0, 1));
            }
        }
        return $abbrev ? $abbrev . ' LAB' : 'LAB';
    }

    return strtoupper($title);
}

if ($action === 'swap') {
    // Swap slots (supports multi-hour swaps by passing comma-separated lists of IDs)
    $ids_a_str = $_POST['id_a'] ?? '';
    $ids_b_str = $_POST['id_b'] ?? '';
    if (!$ids_a_str || !$ids_b_str) {
        echo json_encode(['success' => false, 'error' => 'Invalid slot IDs']);
        exit;
    }

    $ids_a = array_filter(array_map('intval', explode(',', $ids_a_str)));
    $ids_b = array_filter(array_map('intval', explode(',', $ids_b_str)));

    if (count($ids_a) !== count($ids_b) || empty($ids_a)) {
        echo json_encode(['success' => false, 'error' => 'Mismatched slot counts for swap']);
        exit;
    }

    $fields = ['subject_id', 'subject_title', 'subject_short_name', 'subject_type', 'staff_id', 'staff_name', 'staff_code'];

    for ($i = 0; $i < count($ids_a); $i++) {
        $id_a = $ids_a[$i];
        $id_b = $ids_b[$i];

        $ra = $conn->query("SELECT * FROM saved_timetable_slots WHERE id = $id_a")->fetch_assoc();
        $rb = $conn->query("SELECT * FROM saved_timetable_slots WHERE id = $id_b")->fetch_assoc();

        if (!$ra || !$rb) continue;

        $sets_a = [];
        $sets_b = [];
        foreach ($fields as $f) {
            $va = $conn->real_escape_string($rb[$f] ?? '');
            $vb = $conn->real_escape_string($ra[$f] ?? '');
            $sets_a[] = "`$f` = " . ($va === '' ? 'NULL' : "'$va'");
            $sets_b[] = "`$f` = " . ($vb === '' ? 'NULL' : "'$vb'");
        }
        
        $conn->query("UPDATE saved_timetable_slots SET " . implode(', ', $sets_a) . " WHERE id = $id_a");
        $conn->query("UPDATE saved_timetable_slots SET " . implode(', ', $sets_b) . " WHERE id = $id_b");
    }

    echo json_encode(['success' => true, 'action' => 'swap']);

} elseif ($action === 'update') {
    // Update a single slot
    $slot_id        = intval($_POST['slot_id'] ?? 0);
    $subject_id     = $_POST['subject_id']     ?? '';
    $subject_title  = $_POST['subject_title']  ?? '';
    $subject_short  = $_POST['subject_short']  ?? '';
    $subject_type   = $_POST['subject_type']   ?? '';
    $staff_id       = intval($_POST['staff_id'] ?? 0);
    $staff_name     = $_POST['staff_name']     ?? '';
    $staff_code     = $_POST['staff_code']     ?? '';

    if (!$slot_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid slot ID']);
        exit;
    }

    // Automatically determine short name instead of relying on frontend logic
    if ($subject_id && $subject_title) {
        // Fetch semester and subject details from db for accurate parsing
        $semQuery = $conn->query("SELECT semester FROM saved_timetable_slots WHERE id = $slot_id");
        $slotRow = $semQuery->fetch_assoc();
        $sem = $slotRow ? $slotRow['semester'] : null;
        
        // Fetch subject details directly to get the defined short_name
        $clean_sub_id = $conn->real_escape_string($subject_id);
        // Handle split subjects (e.g., '14_1')
        $base_id = (strpos($clean_sub_id, '_') !== false) ? explode('_', $clean_sub_id)[0] : $clean_sub_id;
        $subQuery = $conn->query("SELECT short_name FROM subjects WHERE id = '$base_id'");
        $subRow = $subQuery ? $subQuery->fetch_assoc() : null;

        $subject_short = getShortName($subject_title, $subject_type, $sem, $subRow);
    } else {
        $subject_short = '';
    }

    $conn->query("UPDATE saved_timetable_slots SET
        subject_id     = " . ($subject_id    ? "'" . $conn->real_escape_string($subject_id)    . "'" : 'NULL') . ",
        subject_title  = " . ($subject_title ? "'" . $conn->real_escape_string($subject_title) . "'" : 'NULL') . ",
        subject_short_name = " . ($subject_short ? "'" . $conn->real_escape_string($subject_short) . "'" : 'NULL') . ",
        subject_type   = " . ($subject_type  ? "'" . $conn->real_escape_string($subject_type)  . "'" : 'NULL') . ",
        staff_id       = " . ($staff_id      ? intval($staff_id) : 'NULL') . ",
        staff_name     = " . ($staff_name    ? "'" . $conn->real_escape_string($staff_name)    . "'" : 'NULL') . ",
        staff_code     = " . ($staff_code    ? "'" . $conn->real_escape_string($staff_code)    . "'" : 'NULL') . "
        WHERE id = $slot_id");

    echo json_encode(['success' => true, 'action' => 'update', 'rendered_short_name' => $subject_short]);

} elseif ($action === 'clear') {
    $slot_id = intval($_POST['slot_id'] ?? 0);
    if (!$slot_id) { echo json_encode(['success' => false]); exit; }

    $conn->query("UPDATE saved_timetable_slots SET subject_id=NULL, subject_title=NULL, subject_short_name=NULL, subject_type=NULL, staff_id=NULL, staff_name=NULL, staff_code=NULL WHERE id=$slot_id");
    echo json_encode(['success' => true, 'action' => 'clear']);

} elseif ($action === 'discard') {
    // Revert all changes by restoring from the session backup
    $id = intval($_POST['id'] ?? 0);
    if ($id && isset($_SESSION['tt_backup'][$id])) {
        $backup = $_SESSION['tt_backup'][$id];
        foreach ($backup as $slot_id => $bSlot) {
            $sid = intval($slot_id);
            $sub_id    = $bSlot['subject_id'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['subject_id']) . "'";
            $sub_title = $bSlot['subject_title'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['subject_title']) . "'";
            $sub_short = $bSlot['subject_short_name'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['subject_short_name']) . "'";
            $sub_type  = $bSlot['subject_type'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['subject_type']) . "'";
            $stf_id    = $bSlot['staff_id'] === null ? 'NULL' : intval($bSlot['staff_id']);
            $stf_name  = $bSlot['staff_name'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['staff_name']) . "'";
            $stf_code  = $bSlot['staff_code'] === null ? 'NULL' : "'" . $conn->real_escape_string($bSlot['staff_code']) . "'";

            $conn->query("UPDATE saved_timetable_slots SET 
                subject_id = $sub_id, 
                subject_title = $sub_title,
                subject_short_name = $sub_short,
                subject_type = $sub_type,
                staff_id = $stf_id,
                staff_name = $stf_name,
                staff_code = $stf_code
                WHERE id = $sid");
        }
        // Keep the backup so re-loading the page doesn't reset it?
        unset($_SESSION['tt_backup'][$id]);
    }
    echo json_encode(['success' => true, 'action' => 'discard']);

} elseif ($action === 'commit') {
    // Clear the backup securely
    $id = intval($_POST['id'] ?? 0);
    if ($id && isset($_SESSION['tt_backup'][$id])) {
        unset($_SESSION['tt_backup'][$id]);
    }
    echo json_encode(['success' => true, 'action' => 'commit']);

} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
}

$conn->close();
?>
