<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

require_once 'default_allocations.php';

/**
 * Merge default manual allocations into the session without overwriting
 * any slots the user has already customised.
 */
function seedDefaultAllocations(array &$session_alloc, array $defaults, string $semester_type): void
{
    if (!isset($defaults[$semester_type])) {
        return; // No defaults for this semester type
    }

    $semester_defaults = $defaults[$semester_type];

    foreach ($semester_defaults as $class_idx => $shifts) {
        if (!isset($session_alloc[$class_idx])) {
            $session_alloc[$class_idx] = [];
        }
        foreach ($shifts as $shift => $slots) {
            if (!isset($session_alloc[$class_idx][$shift])) {
                $session_alloc[$class_idx][$shift] = [];
            }
            // Only add slots that haven't already been set by the user
            foreach ($slots as $slot_key => $alloc_data) {
                if (!isset($session_alloc[$class_idx][$shift][$slot_key])) {
                    $session_alloc[$class_idx][$shift][$slot_key] = $alloc_data;
                }
            }
        }
    }
}

/**
 * Clear all staff_* and hours_* session keys for the given subject IDs.
 * Called before re-saving POST data so removed staff rows don't leave ghost entries.
 */
function clearSubjectSessionKeys(array $subject_ids, bool $has_shifts): void
{
    if (empty($subject_ids)) return;

    if (isset($_SESSION['staff_allocations'])) {
        foreach (array_keys($_SESSION['staff_allocations']) as $key) {
            if (preg_match('/^staff_shift[12]_(\d+)/', $key, $m)) {
                $sid = intval($m[1]);
            } elseif (preg_match('/^staff_(\d+)/', $key, $m)) {
                $sid = intval($m[1]);
            } else {
                continue;
            }
            if (in_array($sid, $subject_ids)) {
                unset($_SESSION['staff_allocations'][$key]);
            }
        }
    }

    // Clear indexed split-hours entries (hours_{sid}_1, hours_shift1_{sid}_1, etc.)
    if (isset($_SESSION['hours_changes'])) {
        foreach (array_keys($_SESSION['hours_changes']) as $key) {
            if (preg_match('/^hours_(?:shift[12]_)?(\d+)_\d+$/', $key, $m)) {
                $sid = intval($m[1]);
                if (in_array($sid, $subject_ids)) {
                    unset($_SESSION['hours_changes'][$key]);
                }
            }
        }
    }
}

// Get or initialize session variables
if (!isset($_SESSION['semester_filter'])) {
    $_SESSION['semester_filter'] = 'odd';
}

if (!isset($_SESSION['current_class_index'])) {
    $_SESSION['current_class_index'] = 0;
}

if (!isset($_SESSION['staff_allocations'])) {
    $_SESSION['staff_allocations'] = [];
}

if (!isset($_SESSION['hours_changes'])) {
    $_SESSION['hours_changes'] = [];
}

// Seed default manual allocations (with versioning to ensure it runs for existing sessions)
if (!isset($_SESSION['manual_allocations_seeded_v13'])) {
    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }

    // Clear out manual allocations belonging to the WRONG semester to prevent blocking
    $subject_semesters = [];
    $sem_result = $conn->query("SELECT id, semester FROM subjects");
    while ($row = $sem_result->fetch_assoc()) {
        $subject_semesters[$row['id']] = $row['semester'];
    }
    $sem_filter = $_SESSION['semester_filter'];

    foreach ($_SESSION['manual_allocations'] as $class_idx => $shifts) {
        foreach ($shifts as $shift => $slots) {
            foreach ($slots as $slot_key => $alloc) {
                $subject_id = intval($alloc['subject_id']);
                $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;
                $is_current_sem = ($sem_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                    ($sem_filter === 'even' && in_array($sub_sem, [2, 4, 6]));
                if (!$is_current_sem) {
                    unset($_SESSION['manual_allocations'][$class_idx][$shift][$slot_key]);
                }
            }
        }
    }

    // Clear previous Shift 2 allocations for I B.Sc Even Sem so the new defaults can be seeded over them securely
    if (isset($_SESSION['manual_allocations'][0]['shift2'])) {
        foreach ($_SESSION['manual_allocations'][0]['shift2'] as $slot_key => $alloc) {
            if (in_array(intval($alloc['subject_id']), [16, 17, 21, 23])) {
                unset($_SESSION['manual_allocations'][0]['shift2'][$slot_key]);
            }
        }
    }

    // Clear previous allocations for II B.Sc Even Sem subjects (Tamil IV, English IV, Business Accounting)
    foreach (['shift1', 'shift2', ''] as $sh) {
        if (isset($_SESSION['manual_allocations'][1][$sh])) {
            foreach ($_SESSION['manual_allocations'][1][$sh] as $slot_key => $alloc) {
                if (in_array(intval($alloc['subject_id']), [32, 33, 39])) {
                    unset($_SESSION['manual_allocations'][1][$sh][$slot_key]);
                }
            }
        }
    }

    // Clear previous allocations for III B.Sc NME subjects (Sem 5 odd = 46, Sem 6 even = 56) to allow new defaults
    foreach (['shift1', 'shift2', ''] as $sh) {
        if (isset($_SESSION['manual_allocations'][2][$sh])) {
            foreach ($_SESSION['manual_allocations'][2][$sh] as $slot_key => $alloc) {
                if (in_array(intval($alloc['subject_id']), [46, 56])) {
                    unset($_SESSION['manual_allocations'][2][$sh][$slot_key]);
                }
            }
        }
    }

    seedDefaultAllocations($_SESSION['manual_allocations'], $default_manual_allocations, $_SESSION['semester_filter']);
    $_SESSION['manual_allocations_seeded_v13'] = true;
}

// Clear current page marker
unset($_SESSION['current_page']);

// Handle semester selection
if (isset($_GET['semester'])) {
    $_SESSION['semester_filter'] = $_GET['semester'];
    $_SESSION['current_class_index'] = 0;

    // Clear out manual allocations belonging to the WRONG semester 
    // so they don't block the defaults for the NEW semester from seeding.
    if (isset($_SESSION['manual_allocations'])) {
        $subject_semesters = [];
        $sem_result = $conn->query("SELECT id, semester FROM subjects");
        while ($row = $sem_result->fetch_assoc()) {
            $subject_semesters[$row['id']] = $row['semester'];
        }

        $sem_filter = $_SESSION['semester_filter'];

        foreach ($_SESSION['manual_allocations'] as $class_idx => $shifts) {
            foreach ($shifts as $shift => $slots) {
                foreach ($slots as $slot_key => $alloc) {
                    $subject_id = intval($alloc['subject_id']);
                    $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;

                    $is_current_sem = ($sem_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                        ($sem_filter === 'even' && in_array($sub_sem, [2, 4, 6]));

                    // If the cached allocation belongs to the previous semester type, delete it.
                    if (!$is_current_sem) {
                        unset($_SESSION['manual_allocations'][$class_idx][$shift][$slot_key]);
                    }
                }
            }
        }
    }

    // Seed the defaults for the newly selected semester type
    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }
    seedDefaultAllocations($_SESSION['manual_allocations'], $default_manual_allocations, $_SESSION['semester_filter']);

    header("Location: timetable.php");
    exit;
}

// Handle reset
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    // Get all subject semesters to filter correctly
    $subject_semesters = [];
    $sem_result = $conn->query("SELECT id, semester FROM subjects");
    while ($row = $sem_result->fetch_assoc()) {
        $subject_semesters[$row['id']] = $row['semester'];
    }

    $sem_filter = isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd';

    if (isset($_SESSION['staff_allocations'])) {
        foreach ($_SESSION['staff_allocations'] as $key => $val) {
            $parts = explode('_', $key);
            $subject_id = 0;
            if (strpos($key, 'staff_shift') === 0 && isset($parts[2])) {
                $subject_id = intval($parts[2]);
            } elseif (isset($parts[1])) {
                $subject_id = intval($parts[1]);
            }
            if ($subject_id) {
                $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;
                $is_current_sem = ($sem_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                    ($sem_filter === 'even' && in_array($sub_sem, [2, 4, 6]));
                if ($is_current_sem)
                    unset($_SESSION['staff_allocations'][$key]);
            }
        }
    }
    if (isset($_SESSION['hours_changes'])) {
        foreach ($_SESSION['hours_changes'] as $key => $val) {
            $parts = explode('_', $key);
            $subject_id = 0;
            if (strpos($key, 'hours_shift') === 0 && isset($parts[2])) {
                $subject_id = intval($parts[2]);
            } elseif (isset($parts[1])) {
                $subject_id = intval($parts[1]);
            }
            if ($subject_id) {
                $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;
                $is_current_sem = ($sem_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                    ($sem_filter === 'even' && in_array($sub_sem, [2, 4, 6]));
                if ($is_current_sem)
                    unset($_SESSION['hours_changes'][$key]);
            }
        }
    }
    if (isset($_SESSION['manual_allocations'])) {
        foreach ($_SESSION['manual_allocations'] as $class_idx => $shifts) {
            $sem_num = 0;
            if ($class_idx == 0)
                $sem_num = ($sem_filter == 'odd' ? 1 : 2);
            elseif ($class_idx == 1)
                $sem_num = ($sem_filter == 'odd' ? 3 : 4);
            elseif ($class_idx == 2)
                $sem_num = ($sem_filter == 'odd' ? 5 : 6);
            elseif ($class_idx == 3)
                $sem_num = ($sem_filter == 'odd' ? 1 : 2);
            elseif ($class_idx == 4)
                $sem_num = ($sem_filter == 'odd' ? 3 : 4);
            $is_current_sem = ($sem_filter === 'odd' && in_array($sem_num, [1, 3, 5])) ||
                ($sem_filter === 'even' && in_array($sem_num, [2, 4, 6]));
            if ($is_current_sem)
                unset($_SESSION['manual_allocations'][$class_idx]);
        }
    }
    $_SESSION['current_class_index'] = 0;
    // Re-seed defaults so the timetable starts with the pre-configured slot patterns
    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }
    seedDefaultAllocations($_SESSION['manual_allocations'], $default_manual_allocations, $_SESSION['semester_filter']);
    header("Location: timetable.php");
    exit;
}

// Handle navigation
if (isset($_POST['action']) && $_POST['action'] === 'next') {
    // Clear ghost session keys for this page's subjects before re-saving
    $page_subject_ids = array_map('intval', explode(',', $_POST['page_subject_ids'] ?? ''));
    $page_has_shifts   = !empty($_POST['page_has_shifts']);
    clearSubjectSessionKeys($page_subject_ids, $page_has_shifts);

    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0) {
            if (!empty($value)) {
                $_SESSION['staff_allocations'][$key] = $value;
            } else {
                unset($_SESSION['staff_allocations'][$key]);
            }
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    if (isset($_POST['no_staff_subjects'])) {
        $no_staff_array = json_decode($_POST['no_staff_subjects'], true);
        if (is_array($no_staff_array)) {
            if (!isset($_SESSION['no_staff_subjects'])) {
                $_SESSION['no_staff_subjects'] = [];
            }
            $_SESSION['no_staff_subjects'][$_SESSION['current_class_index']] = $no_staff_array;
        }
    }

    $_SESSION['current_class_index']++;
    header("Location: timetable.php");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'previous') {
    // Clear ghost session keys for this page's subjects before re-saving
    $page_subject_ids = array_map('intval', explode(',', $_POST['page_subject_ids'] ?? ''));
    $page_has_shifts   = !empty($_POST['page_has_shifts']);
    clearSubjectSessionKeys($page_subject_ids, $page_has_shifts);

    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0) {
            if (!empty($value)) {
                $_SESSION['staff_allocations'][$key] = $value;
            } else {
                unset($_SESSION['staff_allocations'][$key]);
            }
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    if (isset($_POST['no_staff_subjects'])) {
        $no_staff_array = json_decode($_POST['no_staff_subjects'], true);
        if (is_array($no_staff_array)) {
            if (!isset($_SESSION['no_staff_subjects'])) {
                $_SESSION['no_staff_subjects'] = [];
            }
            $_SESSION['no_staff_subjects'][$_SESSION['current_class_index']] = $no_staff_array;
        }
    }

    $_SESSION['current_class_index']--;
    if ($_SESSION['current_class_index'] < 0) {
        $_SESSION['current_class_index'] = 0;
    }
    header("Location: timetable.php");
    exit;
}

// Handle manual allocation redirect
if (isset($_POST['action']) && $_POST['action'] === 'manual') {
    // Clear ghost session keys for this page's subjects before re-saving
    $page_subject_ids = array_map('intval', explode(',', $_POST['page_subject_ids'] ?? ''));
    $page_has_shifts   = !empty($_POST['page_has_shifts']);
    clearSubjectSessionKeys($page_subject_ids, $page_has_shifts);

    // Save current page data before leaving
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0) {
            if (!empty($value)) {
                $_SESSION['staff_allocations'][$key] = $value;
            } else {
                unset($_SESSION['staff_allocations'][$key]);
            }
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    if (isset($_POST['no_staff_subjects'])) {
        $no_staff_array = json_decode($_POST['no_staff_subjects'], true);
        if (is_array($no_staff_array)) {
            if (!isset($_SESSION['no_staff_subjects'])) {
                $_SESSION['no_staff_subjects'] = [];
            }
            $_SESSION['no_staff_subjects'][$_SESSION['current_class_index']] = $no_staff_array;
        }
    }

    $subject_id = $_POST['manual_subject_id'];
    $shift = $_POST['manual_shift'];
    $class_index = $_SESSION['current_class_index'];
    $staff_index = isset($_POST['manual_staff_index']) ? $_POST['manual_staff_index'] : '';

    $url = "manual_allocation.php?subject_id=$subject_id&shift=$shift&class_index=$class_index";
    if (!empty($staff_index)) {
        $url .= "&staff_index=$staff_index";
    }

    header("Location: " . $url);
    exit;
}

// Handle final submission
if (isset($_POST['action']) && $_POST['action'] === 'generate') {
    // Clear ghost session keys for this page's subjects before re-saving
    $page_subject_ids = array_map('intval', explode(',', $_POST['page_subject_ids'] ?? ''));
    $page_has_shifts   = !empty($_POST['page_has_shifts']);
    clearSubjectSessionKeys($page_subject_ids, $page_has_shifts);

    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0) {
            if (!empty($value)) {
                $_SESSION['staff_allocations'][$key] = $value;
            } else {
                unset($_SESSION['staff_allocations'][$key]);
            }
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    // Save no staff required subjects for current class
    if (isset($_POST['no_staff_subjects'])) {
        $no_staff_array = json_decode($_POST['no_staff_subjects'], true);
        if (is_array($no_staff_array)) {
            if (!isset($_SESSION['no_staff_subjects'])) {
                $_SESSION['no_staff_subjects'] = [];
            }
            $_SESSION['no_staff_subjects'][$_SESSION['current_class_index']] = $no_staff_array;
        }
    }

    // Validate ALL classes have all required subjects allocated
    $all_allocated = true;
    $unallocated_class_index = -1;
    $failed_subject_title = '';
    $semester_filter = $_SESSION['semester_filter'];

    $class_sequence = [
        ['pattern' => 'I B.Sc%', 'label' => 'I B.Sc', 'type' => 'UG', 'has_shifts' => true],
        ['pattern' => 'II B.Sc%', 'label' => 'II B.Sc', 'type' => 'UG', 'has_shifts' => true],
        ['pattern' => 'III B.Sc%', 'label' => 'III B.Sc', 'type' => 'UG', 'has_shifts' => true],
        ['pattern' => 'I M.Sc%', 'label' => 'I M.Sc', 'type' => 'PG', 'has_shifts' => false],
        ['pattern' => 'II M.Sc%', 'label' => 'II M.Sc', 'type' => 'PG', 'has_shifts' => false],
    ];

    for ($idx = 0; $idx < count($class_sequence); $idx++) {
        $c_config = $class_sequence[$idx];
        $sem_num = 0;
        if ($idx == 0)
            $sem_num = ($semester_filter == 'odd' ? 1 : 2);
        elseif ($idx == 1)
            $sem_num = ($semester_filter == 'odd' ? 3 : 4);
        elseif ($idx == 2)
            $sem_num = ($semester_filter == 'odd' ? 5 : 6);
        elseif ($idx == 3)
            $sem_num = ($semester_filter == 'odd' ? 1 : 2);
        elseif ($idx == 4)
            $sem_num = ($semester_filter == 'odd' ? 3 : 4);

        $program = $c_config['type'];
        $q = "SELECT * FROM subjects WHERE program = '$program' AND semester = $sem_num AND COALESCE(is_allocated, 1) = 1 ORDER BY sort_order, id";
        $res = $conn->query($q);

        if ($c_config['has_shifts']) {
            $class_q = "SELECT * FROM classes WHERE name LIKE '{$c_config['pattern']}' ORDER BY shift";
            $c_res = $conn->query($class_q);
            $classes = [];
            while ($cr = $c_res->fetch_assoc())
                $classes[] = $cr;
            $shift1_class = count($classes) >= 1 ? $classes[0] : null;
            $shift2_class = count($classes) >= 2 ? $classes[1] : null;
        } else {
            $class_q = "SELECT * FROM classes WHERE name LIKE '{$c_config['pattern']}' LIMIT 1";
            $c_res = $conn->query($class_q);
            $shift1_class = $c_res->fetch_assoc();
        }

        while ($sub = $res->fetch_assoc()) {
            if (in_array($sub['type'], ['Core', 'Lab', 'NM', 'NME', 'Project'])) {
                
                // Skip validation if this subject is marked as "No Staff Required"
                $is_no_staff_required = false;
                if (isset($_SESSION['no_staff_subjects'][$idx]) && is_array($_SESSION['no_staff_subjects'][$idx])) {
                    if (in_array($sub['id'], $_SESSION['no_staff_subjects'][$idx])) {
                        $is_no_staff_required = true;
                    }
                } else {
                    // If no explicit state is saved for this class yet, rely on defaults
                    if (in_array(strtolower($sub['type']), ['common', 'allied', 'nme', 'nm'])) {
                        $is_no_staff_required = true;
                    }
                }
                
                if ($is_no_staff_required) {
                    continue; // Skip the staff requirement check for this subject
                }

                if ($c_config['has_shifts']) {
                    $staff_key_1 = 'staff_shift1_' . $sub['id'];
                    $staff_key_2 = 'staff_shift2_' . $sub['id'];

                    $shift1_allocated = false;
                    foreach ($_SESSION['staff_allocations'] as $key => $val) {
                        if (strpos($key, $staff_key_1) === 0 && !empty($val)) {
                            $shift1_allocated = true;
                            break;
                        }
                    }

                    $shift2_allocated = false;
                    foreach ($_SESSION['staff_allocations'] as $key => $val) {
                        if (strpos($key, $staff_key_2) === 0 && !empty($val)) {
                            $shift2_allocated = true;
                            break;
                        }
                    }

                    if (!$shift1_allocated || !$shift2_allocated) {
                        $all_allocated = false;
                        $unallocated_class_index = $idx;
                        $failed_subject_title = $sub['title'];
                        break 2;
                    }
                } else {
                    $single_allocated = false;
                    foreach ($_SESSION['staff_allocations'] as $key => $val) {
                        if (strpos($key, 'staff_' . $sub['id'] . '_') === 0 && !empty($val)) {
                            $single_allocated = true;
                            break;
                        }
                    }

                    if (!$single_allocated) {
                        $all_allocated = false;
                        $unallocated_class_index = $idx;
                        $failed_subject_title = $sub['title'];
                        break 2;
                    }
                }
            }
        }
    }

    if (!$all_allocated) {
        $_SESSION['current_class_index'] = $unallocated_class_index;
        $_SESSION['validation_error'] = "Missing staff allocation for '" . $failed_subject_title . "' in Class " . ($unallocated_class_index + 1);
        $_SESSION['trigger_validation'] = true;
        header("Location: timetable.php");
        exit;
    }

    // Update hours in database
    foreach ($_SESSION['hours_changes'] as $key => $value) {
        $subject_id = intval(str_replace('hours_', '', $key));
        if ($value >= 1 && $value <= 30) {
            $stmt = $conn->prepare("UPDATE subjects SET hours_per_week = ? WHERE id = ?");
            $stmt->bind_param("ii", $value, $subject_id);
            $stmt->execute();
        }
    }

    // Redirect to generate_timetable.php
    header("Location: generate_timetable.php?semester=" . $_SESSION['semester_filter']);
    exit;
}

$semester_filter = $_SESSION['semester_filter'];
$current_index = $_SESSION['current_class_index'];

// Define class sequence
$class_sequence = [
    ['pattern' => 'I B.Sc%', 'label' => 'I B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'II B.Sc%', 'label' => 'II B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'III B.Sc%', 'label' => 'III B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'I M.Sc%', 'label' => 'I M.Sc', 'type' => 'PG', 'has_shifts' => false],
    ['pattern' => 'II M.Sc%', 'label' => 'II M.Sc', 'type' => 'PG', 'has_shifts' => false],
];

$total_classes = count($class_sequence);

// Ensure index is within bounds
if ($current_index >= $total_classes) {
    $current_index = $total_classes - 1;
    $_SESSION['current_class_index'] = $current_index;
}

$current_class_config = $class_sequence[$current_index];

// Determine semester number
$semester_numbers = [
    0 => $semester_filter == 'odd' ? 1 : 2,  // I B.Sc
    1 => $semester_filter == 'odd' ? 3 : 4,  // II B.Sc
    2 => $semester_filter == 'odd' ? 5 : 6,  // III B.Sc
    3 => $semester_filter == 'odd' ? 1 : 2,  // I M.Sc
    4 => $semester_filter == 'odd' ? 3 : 4,  // II M.Sc
];

$current_semester = $semester_numbers[$current_index];

// Get subjects for current class
$program = $current_class_config['type'];
$subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $current_semester AND COALESCE(is_allocated, 1) = 1 ORDER BY sort_order, id";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
while ($subject = $subjects_result->fetch_assoc()) {
    $subjects[] = $subject;
    // Always sync session hours from DB so external edits (via Subject page) are reflected immediately
    $sid = $subject['id'];
    $db_hours = $subject['hours_per_week'];
    if (!isset($_SESSION['hours_changes'])) {
        $_SESSION['hours_changes'] = [];
    }
    $_SESSION['hours_changes']['hours_' . $sid] = $db_hours;
    $_SESSION['hours_changes']['hours_shift1_' . $sid] = $db_hours;
    $_SESSION['hours_changes']['hours_shift2_' . $sid] = $db_hours;
}

// Get all staff with their maximum hours
$staff_result = $conn->query("SELECT id, name, short_code, designation, Hours FROM staff ORDER BY 
    CASE 
        WHEN designation LIKE '%Head%' THEN 1
        WHEN designation LIKE '%Associate Professor%' AND designation NOT LIKE '%Assistant%' THEN 2
        WHEN designation LIKE '%Assistant Professor%' THEN 3
        WHEN designation LIKE '%Guest Lecturer%' THEN 4
        ELSE 5
    END, id ASC");
$staff_list = [];
while ($row = $staff_result->fetch_assoc()) {
    $staff_list[] = $row;
}

// Calculate hours used by each staff from current allocations (ignoring other semester)
$subject_semesters = [];
$sem_result = $conn->query("SELECT id, semester FROM subjects");
while ($row = $sem_result->fetch_assoc()) {
    $subject_semesters[$row['id']] = $row['semester'];
}

$staff_hours_used = [];
if (isset($_SESSION['staff_allocations'])) {
    foreach ($_SESSION['staff_allocations'] as $key => $staff_id) {
        if (empty($staff_id))
            continue;

        // Extract subject_id and other info from key
        // Keys can be: 
        // - staff_shift1_{subject_id}
        // - staff_shift2_{subject_id}
        // - staff_shift1_{subject_id}_{index} (for split allocations)
        // - staff_shift2_{subject_id}_{index} (for split allocations)
        // - staff_{subject_id}_{class_id} (for M.Sc without shifts)
        $subject_id = null;
        $shift = null;
        $staff_index = null;

        $parts = explode('_', $key);

        if (strpos($key, 'staff_shift1_') === 0) {
            $shift = 'shift1';
            $subject_id = intval($parts[2]);
            if (count($parts) >= 4) {
                $staff_index = intval($parts[3]);
            }
        } elseif (strpos($key, 'staff_shift2_') === 0) {
            $shift = 'shift2';
            $subject_id = intval($parts[2]);
            if (count($parts) >= 4) {
                $staff_index = intval($parts[3]);
            }
        } elseif (strpos($key, 'staff_') === 0) {
            // For MSc (no shift): keys can be staff_{subject_id}_{class_id} OR staff_{subject_id}_{staff_index}
            // We need to distinguish between them
            if (count($parts) >= 2) {
                $subject_id = intval($parts[1]);
                if (count($parts) >= 3) {
                    // Check if this is actually a split allocation (staff_index) or just class_id
                    // Split allocations will have split hours saved in session
                    $potential_index = intval($parts[2]);
                    $split_hours_check = 'hours_' . $subject_id . '_' . $potential_index;

                    // Only treat it as staff_index if split hours exist OR if it's a small number (1-10)
                    // Class IDs are typically much larger (> 10)
                    if (isset($_SESSION['hours_changes'][$split_hours_check]) || $potential_index <= 10) {
                        $staff_index = $potential_index;
                    }
                    // Otherwise, the third part is class_id, not staff_index, so leave staff_index as null
                }
            }
        }

        if ($subject_id) {
            // Check if this subject belongs to the currently active semester filter 
            $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;
            $is_current_sem = ($semester_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                ($semester_filter === 'even' && in_array($sub_sem, [2, 4, 6]));

            if (!$is_current_sem)
                continue;

            // Determine the actual hours for this specific allocation
            $subject_hours = 0;

            // Check if this is a split allocation (has staff_index explicitly in the array key)
            if ($staff_index !== null && $staff_index > 0) {
                // Look for split hours: hours_{shift}_{subject_id}_{index} or hours_{subject_id}_{index}
                $split_hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id . '_' . $staff_index : 'hours_' . $subject_id . '_' . $staff_index;

                if (isset($_SESSION['hours_changes'][$split_hours_key])) {
                    $subject_hours = $_SESSION['hours_changes'][$split_hours_key];
                } else {
                    // Fallback for staff 1: might just be hours_{subject_id}_1
                    if ($staff_index == 1) {
                        $fallback_key_1 = $shift ? 'hours_' . $shift . '_' . $subject_id . '_1' : 'hours_' . $subject_id . '_1';
                        if (isset($_SESSION['hours_changes'][$fallback_key_1])) {
                            $subject_hours = $_SESSION['hours_changes'][$fallback_key_1];
                            // Skip the main hours fallback
                            $split_hours_key = null;
                        }
                    }

                    if ($split_hours_key !== null) {
                        // Fallback: split hours not in session yet, use main hours as conservative estimate
                        $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;

                        if (isset($_SESSION['hours_changes'][$hours_key])) {
                            $subject_hours = $_SESSION['hours_changes'][$hours_key];
                        } else {
                            // Get from database
                            $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                            if ($hours_row = $hours_result->fetch_assoc()) {
                                $subject_hours = $hours_row['hours_per_week'];
                            }
                        }
                    }
                }
            } else {
                // No index in key. Check if this is actually the first staff in a newly active split allocation.
                // We do this by checking if there's a SECOND staff allocated to this same subject/shift
                $next_staff_key = $shift ? 'staff_' . $shift . '_' . $subject_id . '_2' : 'staff_' . $subject_id . '_2';
                $is_actually_split = isset($_SESSION['staff_allocations'][$next_staff_key]) && !empty($_SESSION['staff_allocations'][$next_staff_key]);

                if ($is_actually_split) {
                    $split_hours_key_1 = $shift ? 'hours_' . $shift . '_' . $subject_id . '_1' : 'hours_' . $subject_id . '_1';
                    if (isset($_SESSION['hours_changes'][$split_hours_key_1])) {
                        // Split hours exist and there's a second staff, use the first split hours
                        $subject_hours = $_SESSION['hours_changes'][$split_hours_key_1];
                    } else {
                        // Check main hours if split 1 not found
                        $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
                        if (isset($_SESSION['hours_changes'][$hours_key])) {
                            $subject_hours = $_SESSION['hours_changes'][$hours_key];
                        } else {
                            $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                            if ($hours_row = $hours_result->fetch_assoc()) {
                                $subject_hours = $hours_row['hours_per_week'];
                            }
                        }
                    }
                } else {
                    // Not a split allocation, use the main hours
                    $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;

                    if (isset($_SESSION['hours_changes'][$hours_key])) {
                        $subject_hours = $_SESSION['hours_changes'][$hours_key];
                    } else {
                        // Get from database
                        $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                        if ($hours_row = $hours_result->fetch_assoc()) {
                            $subject_hours = $hours_row['hours_per_week'];
                        }
                    }
                }
            }

            // Add to staff's used hours
            if (!isset($staff_hours_used[$staff_id])) {
                $staff_hours_used[$staff_id] = 0;
            }
            $staff_hours_used[$staff_id] += intval($subject_hours);
        }
    }
}

// Calculate remaining hours for each staff
$staff_hours_data = [];
foreach ($staff_list as $staff) {
    $max_hours = intval($staff['Hours']);
    $used_hours = isset($staff_hours_used[$staff['id']]) ? $staff_hours_used[$staff['id']] : 0;
    $remaining_hours = $max_hours - $used_hours;

    $staff_hours_data[$staff['id']] = [
        'max' => $max_hours,
        'used' => $used_hours,
        'remaining' => $remaining_hours
    ];
}

// Get class IDs for shifts if applicable
$shift1_class = null;
$shift2_class = null;

if ($current_class_config['has_shifts']) {
    $class_query = "SELECT * FROM classes WHERE name LIKE ? ORDER BY shift";
    $stmt = $conn->prepare($class_query);
    $pattern = $current_class_config['pattern'];
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();

    $classes = [];
    while ($row = $result->fetch_assoc()) {
        $classes[] = $row;
    }

    if (count($classes) >= 1)
        $shift1_class = $classes[0];
    if (count($classes) >= 2)
        $shift2_class = $classes[1];
} else {
    // M.Sc - single class
    $class_query = "SELECT * FROM classes WHERE name LIKE ? LIMIT 1";
    $stmt = $conn->prepare($class_query);
    $pattern = $current_class_config['pattern'];
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $shift1_class = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Generator - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <style>
        .class-counter {
            font-size: 18px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 20px;
        }

        .class-info {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .class-info h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 24px;
        }

        .semester-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .subjects-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .subjects-table th {
            background: #1f2937;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .subjects-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .subjects-table tr:last-child td {
            border-bottom: none;
        }

        .subjects-table tr:hover {
            background: #f9fafb;
        }

        .type-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .type-core {
            background: #dbeafe;
            color: #1e40af;
        }

        .type-lab {
            background: #fce7f3;
            color: #9f1239;
        }

        .type-allied {
            background: #d1fae5;
            color: #065f46;
        }

        .type-common {
            background: #fef3c7;
            color: #92400e;
        }

        .type-nme {
            background: #e9d5ff;
            color: #6b21a8;
        }

        .hours-input {
            width: 60px;
            padding: 6px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-align: center;
        }

        .staff-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: white;
        }

        .no-staff-required {
            color: #9ca3af;
            font-style: italic;
            font-size: 14px;
        }

        /* Visual separator between shifts */
        .subjects-table th:nth-child(4),
        .subjects-table td:nth-child(4) {
            border-right: 3px solid #4b5563;
            padding-right: 12px;
        }

        .subjects-table th:nth-child(5),
        .subjects-table td:nth-child(5) {
            padding-left: 12px;
        }

        .subjects-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .staff-allocation-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .staff-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-staff-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            white-space: nowrap;
        }

        .add-staff-btn:hover {
            background: #059669;
        }

        .remove-staff-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .remove-staff-btn:hover {
            background: #dc2626;
        }

        .clear-staff-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-left: 5px;
            min-width: 24px;
            height: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .clear-staff-btn:hover {
            background: #dc2626;
        }

        .split-hours-label {
            font-size: 11px;
            color: #059669;
            font-weight: 600;
        }

        .total-hours-container {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin: 0 15px;
            transition: background-color 0.3s ease;
        }

        .total-hours-container.status-equal {
            background-color: #d1fae5;
            color: #065f46;
        }

        .total-hours-container.status-under {
            background-color: #fef3c7;
            color: #92400e;
        }

        .total-hours-container.status-over {
            background-color: #fecaca;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }

        .btn-reset {
            background: #ef4444;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset:hover {
            background: #dc2626;
        }

        .btn-nav {
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-nav:hover {
            background: #2563eb;
        }

        .btn-generate {
            background: #10b981;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-generate:hover {
            background: #059669;
        }

        .nav-group {
            display: flex;
            gap: 10px;
        }

        .btn-manual {
            background: #6366f1;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-top: 5px;
            display: inline-block;
        }

        .btn-manual:hover {
            background: #4f46e5;
        }
    </style>
    <input type="hidden" name="manual_staff_index" id="manual_staff_index" value="">
    <script>
        // Set manual allocation info before submitting
        function setManualAllocation(subjectId, shift, staffIndex = null) {
            document.getElementById('manual_subject_id').value = subjectId;
            document.getElementById('manual_shift').value = shift || '';
            document.getElementById('manual_staff_index').value = staffIndex || '';
            // Remove validation so we can submit the form without filling all staff
            removeValidation();
            return true;
        }

        // Initialize staff hours data from PHP
        const staffHoursData = <?php echo json_encode($staff_hours_data); ?>;
        const subjectHoursData = {};

        // Initialize subject hours
        <?php foreach ($subjects as $subject): ?>
            subjectHoursData[<?php echo $subject['id']; ?>] = <?php echo $subject['hours_per_week']; ?>;
        <?php endforeach; ?>

        // Track current allocations for dynamic updates
        const currentAllocations = {};
        // Track original allocations that were pre-selected when page loaded
        const originalPageAllocations = {};

        // Helper function to get actual allocated hours for a specific staff allocation
        function getActualAllocationHours(selectName, subjectId, shift) {
            // Extract the staff index from the select name
            // For shifts: staff_shift1_123_2 -> 4 parts, index is 2
            // For MSc: staff_123_2 -> 3 parts, index is 2
            const parts = selectName.split('_');
            let staffIndex = null;

            // Check if this is a split allocation (has an index)
            if (shift) {
                // For shifts: need 4 parts (staff, shift1, subjectId, index)
                if (parts.length >= 4 && !isNaN(parts[parts.length - 1])) {
                    staffIndex = parts[parts.length - 1];
                }
            } else {
                // For MSc: can be 3 parts (staff, subjectId, index)
                if (parts.length >= 3 && !isNaN(parts[parts.length - 1])) {
                    const potentialIndex = parseInt(parts[parts.length - 1]);
                    // Only treat as index if it's a small number (likely 1-10, not a class_id)
                    // If staff_shift_subject_class format, class ID is likely > 10, but to be sure we can check for hours input existence
                    if (potentialIndex <= 20) {
                        // verify the hours input exists for this split index before treating it as index
                        const checkInput = document.querySelector(`[name="hours_${subjectId}_${potentialIndex}"]`);
                        if (checkInput) {
                            staffIndex = potentialIndex;
                        }
                    }
                }
            }

            // If split with index, get the specific split hours input
            if (staffIndex) {
                const hoursInputName = shift ? `hours_${shift}_${subjectId}_${staffIndex}` : `hours_${subjectId}_${staffIndex}`;
                const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
                if (hoursInput) {
                    console.log(`[DEBUG] ${selectName} -> ${hoursInputName} = ${hoursInput.value} hours`);
                    return parseInt(hoursInput.value) || 0;
                }
                // Fallback for first staff in split where splitName may just be _1 
                if (staffIndex == 1) {
                    const splitHoursName1 = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const splitHoursInput1 = document.querySelector(`[name="${splitHoursName1}"]`);
                    if (splitHoursInput1) {
                        return parseInt(splitHoursInput1.value) || 0;
                    }
                }
            } else {
                // Check if this select is the *first* staff in a newly-split allocation
                // If it is staff_shift1_123 but the DOM actually has hours_shift1_123_1, it means the row was split
                // We ONLY want to do this if there's actually a second staff dropdown (staff_shift1_123_2) indicating a real split
                const nextStaffSelectName = shift ? `staff_${shift}_${subjectId}_2` : `staff_${subjectId}_2`;
                const isActuallySplit = document.querySelector(`[name="${nextStaffSelectName}"]`) !== null;

                if (isActuallySplit) {
                    const splitHoursName1 = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const splitHoursInput1 = document.querySelector(`[name="${splitHoursName1}"]`);
                    if (splitHoursInput1) {
                        // Split hours exist and there's a second staff dropdown, this is the first staff in a split allocation
                        console.log(`[DEBUG] ${selectName} -> ${splitHoursName1} = ${splitHoursInput1.value} hours (first split)`);
                        return parseInt(splitHoursInput1.value) || 0;
                    }
                }
            }

            // Otherwise, get the main hours input (not split)
            const hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
            // Check if there are split hours for this subject when not explicitly requested by index
            // This happens when checking validation for a newly added staff row that hasn't split yet
            let hours = 0;
            if (hoursInput) {
                if (hoursInput.type === 'hidden') {
                    // It's a hidden total input, find the active split input instead (fallback to 1)
                    const fallbackSplitName = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const fallbackSplitInput = document.querySelector(`[name="${fallbackSplitName}"]`);
                    hours = fallbackSplitInput ? parseInt(fallbackSplitInput.value) || 0 : parseInt(hoursInput.value) || 0;
                } else {
                    hours = parseInt(hoursInput.value) || 0;
                }
            } else {
                hours = subjectHoursData[subjectId] || 0;
            }
            console.log(`[DEBUG] ${selectName} -> ${hoursInputName} = ${hours} hours (main)`);
            return hours;
        }

        // Update staff dropdown options with remaining hours
        function updateStaffDropdowns() {
            // Recalculate hours based on current page allocations
            const tempHoursUsed = {};
            const originalHoursUsed = {};

            // Count hours from ORIGINAL page allocations (pre-selected from session)
            for (const key in originalPageAllocations) {
                const staffId = originalPageAllocations[key].staffId;
                const subjectId = originalPageAllocations[key].subjectId;
                const shift = originalPageAllocations[key].shift;

                if (!originalHoursUsed[staffId]) {
                    originalHoursUsed[staffId] = 0;
                }
                // Get actual allocated hours instead of total subject hours
                const allocatedHours = getActualAllocationHours(key, subjectId, shift);
                originalHoursUsed[staffId] += allocatedHours;
            }

            // Count hours from CURRENT allocations (what user has selected now)
            for (const key in currentAllocations) {
                const staffId = currentAllocations[key].staffId;
                const subjectId = currentAllocations[key].subjectId;
                const shift = currentAllocations[key].shift;

                if (!tempHoursUsed[staffId]) {
                    tempHoursUsed[staffId] = 0;
                }
                // Get actual allocated hours instead of total subject hours
                const allocatedHours = getActualAllocationHours(key, subjectId, shift);
                tempHoursUsed[staffId] += allocatedHours;
            }

            // Update all staff dropdowns
            document.querySelectorAll('.staff-select').forEach(select => {
                const currentValue = select.value;
                const subjectId = parseInt(select.getAttribute('data-subject-id'));
                const shift = select.getAttribute('data-shift') || null;
                const requiredHours = getActualAllocationHours(select.name, subjectId, shift);

                // Update each option
                Array.from(select.options).forEach(option => {
                    if (option.value === '') return; // Skip "Select Staff"

                    const staffId = parseInt(option.value);
                    const baseUsed = staffHoursData[staffId].used || 0;
                    // Subtract original page hours (already in baseUsed) and add current page hours
                    const originalPageHours = originalHoursUsed[staffId] || 0;
                    const currentPageUsed = tempHoursUsed[staffId] || 0;
                    const totalUsed = baseUsed - originalPageHours + currentPageUsed;
                    let remaining = staffHoursData[staffId].max - totalUsed;

                    // Display value, floored at 0 to avoid showing negative numbers
                    const displayRemaining = Math.max(0, remaining);

                    // Get staff name and code from original option text
                    const originalText = option.getAttribute('data-original-text');
                    if (!originalText) {
                        option.setAttribute('data-original-text', option.textContent);
                    }

                    // Update option text with remaining hours
                    const nameCode = option.getAttribute('data-original-text') || option.textContent.split(' : ')[0];

                    // Don't disable an option if it's currently selected in THIS dropdown
                    const isCurrentSelection = (option.value === currentValue);

                    if (remaining >= requiredHours || isCurrentSelection) {
                        option.textContent = `${nameCode} : ${displayRemaining} hrs remaining`;
                        option.disabled = false;
                        option.style.color = remaining < 5 ? '#dc2626' : '#000';
                    } else {
                        // Not enough hours for this specific subject/split requirement
                        if (displayRemaining > 0) {
                            option.textContent = `${nameCode} : ${displayRemaining} hrs (Need ${requiredHours})`;
                        } else {
                            option.textContent = `${nameCode} : No hours available`;
                        }
                        option.disabled = true;
                        option.style.color = '#9ca3af';
                    }
                });

                // Restore selected value
                select.value = currentValue;
            });
        }

        // Validate staff has enough hours
        function validateStaffHours(select, subjectId, shift) {
            const staffId = parseInt(select.value);
            if (!staffId) {
                // If they just cleared the dropdown, update tracking immediately so other dropdowns reflect it
                if (currentAllocations[select.name]) {
                    delete currentAllocations[select.name];
                }
                updateStaffDropdowns();
                return true;
            }

            // Find the required hours for this specific dropdown using our refined helper formula
            const subjectHours = getActualAllocationHours(select.name, subjectId, shift);

            // Calculate current remaining hours using same logic as updateStaffDropdowns
            let tempHoursUsed = {};
            let originalHoursUsed = {};

            // Count original page hours
            for (const key in originalPageAllocations) {
                // DO NOT skip current selection's original calculation! We must strip ALL original allocations for this page out of baseUsed.
                const allocStaffId = originalPageAllocations[key].staffId;
                const allocSubjectId = originalPageAllocations[key].subjectId;
                const allocShift = originalPageAllocations[key].shift;

                if (!originalHoursUsed[allocStaffId]) {
                    originalHoursUsed[allocStaffId] = 0;
                }
                const allocatedHours = getActualAllocationHours(key, allocSubjectId, allocShift);
                originalHoursUsed[allocStaffId] += allocatedHours;
            }

            // Count current page hours
            for (const key in currentAllocations) {
                if (key === select.name) continue; // Skip current selection, we're validating a new value for this exact dropdown!
                const allocStaffId = currentAllocations[key].staffId;
                const allocSubjectId = currentAllocations[key].subjectId;
                const allocShift = currentAllocations[key].shift;

                if (!tempHoursUsed[allocStaffId]) {
                    tempHoursUsed[allocStaffId] = 0;
                }
                const allocatedHours = getActualAllocationHours(key, allocSubjectId, allocShift);
                tempHoursUsed[allocStaffId] += allocatedHours;
            }

            const baseUsed = staffHoursData[staffId].used || 0;
            const originalPageHours = originalHoursUsed[staffId] || 0;
            const currentPageUsed = tempHoursUsed[staffId] || 0;
            const totalUsed = baseUsed - originalPageHours + currentPageUsed;
            const remaining = staffHoursData[staffId].max - totalUsed;

            console.log(`[VALIDATE] Staff ${staffId}, Subject ${subjectId}: needs ${subjectHours}hrs, has ${remaining}hrs (max:${staffHoursData[staffId].max}, base:${baseUsed}, orig:${originalPageHours}, curr:${currentPageUsed})`);

            if (remaining < subjectHours) {
                console.log(`[VALIDATE] FAILED - Not enough hours`);
                select.value = '';
                // Since it failed, revert the selection and make sure tracking is completely clear
                if (currentAllocations[select.name]) {
                    delete currentAllocations[select.name];
                }
                updateStaffDropdowns();

                select.setCustomValidity(`This staff does not have enough hours for allocation (Needs ${subjectHours} hrs, Has ${Math.max(0, remaining)} hrs remaining)`);
                select.reportValidity();
                select.setCustomValidity('');
                return false;
            }

            console.log(`[VALIDATE] PASSED`);
            return true;
        }

        // Track allocation changes
        function trackAllocation(select, subjectId, shift) {
            const staffId = parseInt(select.value);

            if (staffId) {
                currentAllocations[select.name] = {
                    staffId: staffId,
                    subjectId: subjectId,
                    shift: shift || null
                };
            } else {
                delete currentAllocations[select.name];
            }

            updateStaffDropdowns();
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize both original and current allocations from pre-selected values
            // originalPageAllocations = what was loaded from session (used to subtract from base)
            // currentAllocations = current state (initially same as original)
            document.querySelectorAll('.staff-select').forEach(select => {
                if (select.value) {
                    const subjectId = parseInt(select.getAttribute('data-subject-id'));
                    const shift = select.getAttribute('data-shift') || null;
                    const allocation = {
                        staffId: parseInt(select.value),
                        subjectId: subjectId,
                        shift: shift
                    };
                    originalPageAllocations[select.name] = allocation;
                    currentAllocations[select.name] = { ...allocation }; // Copy the object
                }
            });

            // Add change listeners to hour inputs for total hours update and DB sync
            document.querySelectorAll('.hours-input').forEach(input => {
                input.addEventListener('input', updateTotalHours);
                input.addEventListener('change', function () {
                    const subjectId = parseInt(this.getAttribute('data-subject-id'));
                    const hours = parseInt(this.value);
                    if (subjectId && hours >= 1 && hours <= 30) {
                        syncHoursToDB(subjectId, hours);
                    }
                });
            });

            // Initialize checkboxes
            document.querySelectorAll('.no-staff-cb').forEach(cb => {
                // If it's already checked on load (e.g. by default PHP logic), run the toggle to hide fields
                if (cb.checked) {
                    toggleStaffRequirement(cb, cb.dataset.subjectId, cb.dataset.hasShifts === 'true');
                }
            });

            updateStaffDropdowns();
            updateTotalHours();
        });

        function validateDuplicateStaff() {
            <?php if ($current_class_config['has_shifts']): ?>
                const subjects = {};

                // Collect all staff selections grouped by subject
                document.querySelectorAll('[name^="staff_shift1_"]').forEach(select => {
                    const subjectId = select.name.split('_')[2];
                    if (!subjects[subjectId]) {
                        subjects[subjectId] = { shift1: null, shift2: null, title: select.dataset.subjectTitle };
                    }
                    subjects[subjectId].shift1 = select.value;
                });

                document.querySelectorAll('[name^="staff_shift2_"]').forEach(select => {
                    const subjectId = select.name.split('_')[2];
                    if (!subjects[subjectId]) {
                        subjects[subjectId] = { shift1: null, shift2: null, title: select.dataset.subjectTitle };
                    }
                    subjects[subjectId].shift2 = select.value;
                });

                // Check for duplicates and set validation messages
                let hasError = false;
                for (const subjectId in subjects) {
                    const data = subjects[subjectId];
                    const shift1Select = document.querySelector('[name="staff_shift1_' + subjectId + '"]');
                    const shift2Select = document.querySelector('[name="staff_shift2_' + subjectId + '"]');

                    if (data.shift1 && data.shift2 && data.shift1 === data.shift2) {
                        shift2Select.setCustomValidity('This staff is already assigned for another shift');
                        shift2Select.reportValidity();
                        hasError = true;
                    } else {
                        if (shift1Select) shift1Select.setCustomValidity('');
                        if (shift2Select) shift2Select.setCustomValidity('');
                    }
                }

                return !hasError;
            <?php else: ?>
                return true;
            <?php endif; ?>
        }

        function toggleStaffRequirement(checkbox, subjectId, hasShifts) {
            const row = checkbox.closest('tr');
            
            // Elements to toggle
            const staffSelects = row.querySelectorAll('.staff-select');
            const addStaffBtns = row.querySelectorAll('.add-staff-btn');
            const removeStaffBtns = row.querySelectorAll('.remove-staff-btn');
            const clearStaffBtns = row.querySelectorAll('.clear-staff-btn');
            
            // Labels for disabled state
            const targetCells = hasShifts ? 
                [row.querySelector('td:nth-child(5)'), row.querySelector('td:nth-child(7)')] : 
                [row.querySelector('td:nth-child(4)')];
            
            if (checkbox.checked) {
                // Clear any existing values and disable inputs
                staffSelects.forEach(select => {
                    select.value = '';
                    select.disabled = true;
                    select.removeAttribute('required');
                    if (currentAllocations[select.name]) {
                        delete currentAllocations[select.name];
                    }
                });
                
                // Hide buttons
                addStaffBtns.forEach(btn => btn.style.display = 'none');
                removeStaffBtns.forEach(btn => btn.style.display = 'none');
                clearStaffBtns.forEach(btn => btn.style.display = 'none');
                
                // Update dropdowns
                updateStaffDropdowns();
                
                // Show "No Staff Required" text
                targetCells.forEach(cell => {
                    if (cell) {
                        const containers = cell.querySelectorAll('.staff-allocation-container');
                        containers.forEach(c => c.style.display = 'none');
                        
                        let label = cell.querySelector('.no-staff-label');
                        if (!label) {
                            label = document.createElement('div');
                            label.className = 'no-staff-label no-staff-required';
                            label.textContent = 'No Staff Required';
                            cell.appendChild(label);
                        }
                        label.style.display = 'block';
                    }
                });
            } else {
                // Enable inputs
                staffSelects.forEach(select => {
                    select.disabled = false;
                    select.setAttribute('required', 'required');
                });
                
                // Show buttons
                addStaffBtns.forEach(btn => btn.style.display = 'inline-block');
                removeStaffBtns.forEach(btn => btn.style.display = 'inline-block');
                clearStaffBtns.forEach(btn => btn.style.display = 'inline-flex');
                
                // Show original containers and hide text
                targetCells.forEach(cell => {
                    if (cell) {
                        const containers = cell.querySelectorAll('.staff-allocation-container');
                        containers.forEach(c => c.style.display = 'flex');
                        
                        const label = cell.querySelector('.no-staff-label');
                        if (label) {
                            label.style.display = 'none';
                        }
                    }
                });
            }
        }

        function clearStaffSelection(selectName) {
            const select = document.querySelector(`[name="${selectName}"]`);
            if (select) {
                select.value = ''; // Reset to "Select Staff"

                // Remove from current allocations
                if (currentAllocations[selectName]) {
                    delete currentAllocations[selectName];
                }

                // Update dropdowns to recalculate remaining hours
                updateStaffDropdowns();

                // Clear any validation errors
                select.setCustomValidity('');
            }
        }

        function checkShiftConflict(select, shift) {
            const subjectId = select.name.split('_')[2];
            const otherShift = shift === 'shift1' ? 'shift2' : 'shift1';

            // Collect ALL staff IDs allocated to this subject in current shift
            const currentShiftStaffIds = [];
            document.querySelectorAll(`[name^="staff_${shift}_${subjectId}"]`).forEach(sel => {
                if (sel.value) {
                    currentShiftStaffIds.push(sel.value);
                }
            });

            // Collect ALL staff IDs allocated to this subject in other shift
            const otherShiftStaffIds = [];
            document.querySelectorAll(`[name^="staff_${otherShift}_${subjectId}"]`).forEach(sel => {
                if (sel.value) {
                    otherShiftStaffIds.push(sel.value);
                }
            });

            // Check if the newly selected staff exists in the other shift
            const selectedStaffId = select.value;
            if (selectedStaffId && otherShiftStaffIds.includes(selectedStaffId)) {
                select.value = ''; // Reset to "Select Staff"
                // Remove from currentAllocations
                if (currentAllocations[select.name]) {
                    delete currentAllocations[select.name];
                }
                // Update dropdowns to recalculate remaining hours
                updateStaffDropdowns();
                select.setCustomValidity('This staff is already allocated for another shift');
                select.reportValidity();
                return false;
            } else {
                select.setCustomValidity('');
                return true;
            }
        }

        // Multi-staff allocation functions
        let staffCounter = {};
        let hoursContainerCache = {}; // Cache to store original hours container

        function addStaffAllocation(subjectId, shift) {
            const containerId = shift ? `staff-container-${shift}-${subjectId}` : `staff-container-${subjectId}`;
            const container = document.getElementById(containerId);
            if (!container) return;

            // Initialize counter
            if (!staffCounter[containerId]) {
                staffCounter[containerId] = 1;
            }
            staffCounter[containerId]++;

            const count = staffCounter[containerId];

            // Get hours input based on shift
            let originalHoursInput;
            if (shift) {
                originalHoursInput = document.querySelector(`[name="hours_${shift}_${subjectId}"]`);
            } else {
                originalHoursInput = document.querySelector(`[name="hours_${subjectId}"]`);
            }
            const totalHours = parseInt(originalHoursInput.value);
            const hoursPerStaff = Math.floor(totalHours / count);

            // Split hours input on first add
            if (count === 2) {
                splitHoursInput(subjectId, totalHours, shift);
            }

            // Create new staff row
            const newRow = document.createElement('div');
            newRow.className = 'staff-row';
            newRow.id = `staff-row-${containerId}-${count}`;

            const staffKey = shift ? `staff_${shift}_${subjectId}_${count}` : `staff_${subjectId}_${count}`;
            const shiftAttr = shift ? `data-shift="${shift}"` : '';
            const shiftParam = shift ? `'${shift}'` : 'null';

            newRow.innerHTML = `
                <select name="${staffKey}" 
                        class="staff-select" 
                        data-subject-id="${subjectId}"
                        ${shiftAttr}
                        onchange="if(validateStaffHours(this, ${subjectId}, ${shiftParam})) { trackAllocation(this, ${subjectId}, ${shiftParam}); ${shift ? `checkShiftConflict(this, '${shift}');` : ''} }"
                        required>
                    <option value="">Select Staff</option>
                    <?php foreach ($staff_list as $staff): ?>
                    <option value="<?php echo $staff['id']; ?>">
                        <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('${containerId}', ${count}, ${subjectId}, ${shiftParam})">×</button>
            `;

            container.appendChild(newRow);

            // Update split hours inputs
            updateSplitHoursInputs(subjectId, count, shift);
            updateStaffDropdowns();
        }

        function removeStaffAllocation(containerId, rowNum, subjectId, shift) {
            const row = document.getElementById(`staff-row-${containerId}-${rowNum}`);
            if (row) {
                // Remove from tracking
                const select = row.querySelector('.staff-select');
                if (select && currentAllocations[select.name]) {
                    delete currentAllocations[select.name];
                }

                row.remove();
                staffCounter[containerId]--;

                const newCount = staffCounter[containerId];

                // If down to 1 staff, restore original single hours input
                if (newCount === 1) {
                    restoreSingleHoursInput(subjectId, shift);
                } else {
                    // Update split hours inputs for remaining staff
                    updateSplitHoursInputs(subjectId, newCount, shift);
                }

                updateStaffDropdowns();
            }
        }

        function splitHoursInput(subjectId, totalHours, shift) {
            // Get the appropriate hours input based on shift
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const originalInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);

            // Cache the original state with shift info
            const cacheKey = shift ? `${subjectId}_${shift}` : subjectId;
            hoursContainerCache[cacheKey] = {
                html: hoursCell.innerHTML
            };

            // Calculate split
            const hours1 = Math.ceil(totalHours / 2);
            const hours2 = Math.floor(totalHours / 2);

            // Replace with two inputs and individual manual buttons
            hoursCell.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" 
                                name="${hoursInputName}_1" 
                                class="hours-input split-hours" 
                                data-subject-id="${subjectId}"
                                ${shift ? `data-shift="${shift}"` : ''}
                                value="${hours1}" 
                                min="0" 
                                max="30" 
                                onchange="updateSplitHoursTotal(${subjectId}, ${shift ? `'${shift}'` : 'null'})"
                                required>
                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(${subjectId}, ${shift ? `'${shift}'` : 'null'}, 1)" title="Set Manually">
                                Manual 
                            </button>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" 
                                name="${hoursInputName}_2" 
                                class="hours-input split-hours" 
                                data-subject-id="${subjectId}"
                                ${shift ? `data-shift="${shift}"` : ''}
                                value="${hours2}" 
                                min="0" 
                                max="30" 
                                onchange="updateSplitHoursTotal(${subjectId}, ${shift ? `'${shift}'` : 'null'})"
                                required>
                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(${subjectId}, ${shift ? `'${shift}'` : 'null'}, 2)" title="Set Manually">
                                Manual 
                            </button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="${hoursInputName}" value="${totalHours}">
            `;

            updateTotalHours();
        }

        function restoreSingleHoursInput(subjectId, shift) {
            // Get the appropriate hours input based on shift
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');

            // Get total from hidden input
            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            const currentTotal = hiddenInput ? parseInt(hiddenInput.value) : subjectHoursData[subjectId];

            // Restore original state
            const cacheKey = shift ? `${subjectId}_${shift}` : subjectId;
            if (hoursContainerCache[cacheKey]) {
                hoursCell.innerHTML = hoursContainerCache[cacheKey].html;
                // Update the value to current total
                hoursCell.querySelector(`[name="${hoursInputName}"]`).value = currentTotal;
                // Re-attach event listener
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('input', updateTotalHours);
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('change', function () {
                    const sid = parseInt(this.getAttribute('data-subject-id'));
                    const hrs = parseInt(this.value);
                    if (sid && hrs >= 1 && hrs <= 30) {
                        syncHoursToDB(sid, hrs);
                    }
                });
            } else {
                // Generate fallback HTML when cache doesn't exist (e.g. loaded from session)
                const shiftArg = shift ? `'${shift}'` : `null`;
                const shiftAttr = shift ? `data-shift="${shift}"` : ``;
                hoursCell.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" name="${hoursInputName}"
                                value="${currentTotal}"
                                min="1" max="30" class="hours-input"
                                data-subject-id="${subjectId}" ${shiftAttr} required>
                            <button type="submit" name="action" value="manual" class="btn-manual"
                                onclick="return setManualAllocation(${subjectId}, ${shiftArg})"
                                title="Set Manually">
                                Manual 
                            </button>
                        </div>
                    </div>
                `;
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('input', updateTotalHours);
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('change', function () {
                    const sid = parseInt(this.getAttribute('data-subject-id'));
                    const hrs = parseInt(this.value);
                    if (sid && hrs >= 1 && hrs <= 30) {
                        syncHoursToDB(sid, hrs);
                    }
                });
            }

            updateTotalHours();
        }

        function updateSplitHoursInputs(subjectId, count, shift) {
            // Get the appropriate hours input based on shift
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            const totalHours = parseInt(hiddenInput.value);

            // Clear existing split inputs
            const splitContainer = hoursCell.querySelector('div');
            if (!splitContainer) return;

            splitContainer.innerHTML = '';

            // Create inputs based on count
            const baseHours = Math.floor(totalHours / count);
            const remainder = totalHours % count;

            for (let i = 1; i <= count; i++) {
                const hours = i === 1 ? baseHours + remainder : baseHours;

                const wrapDiv = document.createElement('div');
                wrapDiv.style.display = 'flex';
                wrapDiv.style.flexDirection = 'column';
                wrapDiv.style.gap = '5px';

                const innerDiv = document.createElement('div');
                innerDiv.style.display = 'flex';
                innerDiv.style.alignItems = 'center';
                innerDiv.style.gap = '5px';

                const input = document.createElement('input');
                input.type = 'number';
                input.name = `${hoursInputName}_${i}`;
                input.className = 'hours-input split-hours';
                input.setAttribute('data-subject-id', subjectId);
                if (shift) input.setAttribute('data-shift', shift);
                input.value = hours;
                input.min = '0';
                input.max = '30';
                input.required = true;
                input.onchange = function () { updateSplitHoursTotal(subjectId, shift); };

                const btn = document.createElement('button');
                btn.type = 'submit';
                btn.name = 'action';
                btn.value = 'manual';
                btn.className = 'btn-manual';
                btn.title = 'Set Manually';
                btn.innerHTML = 'Manual ';
                const btnShift = shift ? `'${shift}'` : 'null';
                btn.setAttribute('onclick', `return setManualAllocation(${subjectId}, ${btnShift}, ${i})`);

                innerDiv.appendChild(input);
                innerDiv.appendChild(btn);
                wrapDiv.appendChild(innerDiv);

                splitContainer.appendChild(wrapDiv);
            }
        }

        function updateSplitHoursTotal(subjectId, shift) {
            // Get the appropriate hours input based on shift
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const splitInputs = hoursCell.querySelectorAll('.split-hours');

            let total = 0;
            splitInputs.forEach(input => {
                total += parseInt(input.value) || 0;
            });

            // Update hidden total input
            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            if (hiddenInput) {
                hiddenInput.value = total;
            }

            updateTotalHours();
            // Sync total to database
            if (total >= 1 && total <= 30) {
                syncHoursToDB(subjectId, total);
            }
        }

        // Sync hours to database immediately via AJAX
        function syncHoursToDB(subjectId, hours) {
            const formData = new FormData();
            formData.append('subject_id', subjectId);
            formData.append('hours', hours);

            fetch('update_hours.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show a brief visual confirmation on all inputs for this subject
                        document.querySelectorAll(`.hours-input[data-subject-id="${subjectId}"]`).forEach(inp => {
                            inp.style.borderColor = '#10b981';
                            inp.style.backgroundColor = '#d1fae5';
                            setTimeout(() => {
                                inp.style.borderColor = '';
                                inp.style.backgroundColor = '';
                            }, 1200);
                        });
                    } else {
                        console.warn('Hours sync failed:', data.message);
                    }
                })
                .catch(err => console.error('Hours sync error:', err));
        }

        // Calculate and update total hours display
        function updateTotalHours() {
            const hasShifts = <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>;
            const maxHours = 30; // Default for standard semester

            if (hasShifts) {
                // Calculate separate totals for each shift
                let shift1Total = 0;
                let shift2Total = 0;

                // Sum hours for Shift 1
                document.querySelectorAll('.hours-input[data-shift="shift1"]').forEach(input => {
                    const hours = parseInt(input.value) || 0;
                    shift1Total += hours;
                });

                // Sum hours for Shift 2
                document.querySelectorAll('.hours-input[data-shift="shift2"]').forEach(input => {
                    const hours = parseInt(input.value) || 0;
                    shift2Total += hours;
                });

                // Update Shift 1 display
                const shift1Container = document.getElementById('total-shift1-container');
                const shift1Display = document.getElementById('total-hours-shift1');
                if (shift1Display) {
                    shift1Display.textContent = `Shift 1: ${shift1Total} / ${maxHours} Hrs`;
                }
                if (shift1Container) {
                    shift1Container.classList.remove('status-equal', 'status-under', 'status-over');
                    if (shift1Total === maxHours) {
                        shift1Container.classList.add('status-equal'); // Green
                    } else if (shift1Total < maxHours) {
                        shift1Container.classList.add('status-under'); // Yellow
                    } else {
                        shift1Container.classList.add('status-over'); // Red
                    }
                }

                // Update Shift 2 display
                const shift2Container = document.getElementById('total-shift2-container');
                const shift2Display = document.getElementById('total-hours-shift2');
                if (shift2Display) {
                    shift2Display.textContent = `Shift 2: ${shift2Total} / ${maxHours} Hrs`;
                }
                if (shift2Container) {
                    shift2Container.classList.remove('status-equal', 'status-under', 'status-over');
                    if (shift2Total === maxHours) {
                        shift2Container.classList.add('status-equal'); // Green
                    } else if (shift2Total < maxHours) {
                        shift2Container.classList.add('status-under'); // Yellow
                    } else {
                        shift2Container.classList.add('status-over'); // Red
                    }
                }
            } else {
                // Single total for non-shift classes
                let totalAllocated = 0;
                document.querySelectorAll('.hours-input').forEach(input => {
                    const hours = parseInt(input.value) || 0;
                    totalAllocated += hours;
                });

                const totalContainer = document.querySelector('.total-hours-container');
                const totalDisplay = document.getElementById('total-hours-display');

                if (totalDisplay) {
                    totalDisplay.textContent = `Total: ${totalAllocated} / ${maxHours} Hrs`;
                }

                if (totalContainer) {
                    totalContainer.classList.remove('status-equal', 'status-under', 'status-over');
                    if (totalAllocated === maxHours) {
                        totalContainer.classList.add('status-equal'); // Green
                    } else if (totalAllocated < maxHours) {
                        totalContainer.classList.add('status-under'); // Yellow
                    } else {
                        totalContainer.classList.add('status-over'); // Red
                    }
                }
            }
        }

        function validateTotalHours() {
            const hasShifts = <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>;
            const maxHours = 30; // Default for standard semester

            if (hasShifts) {
                let shift1Total = 0;
                let shift2Total = 0;

                document.querySelectorAll('.hours-input[data-shift="shift1"]').forEach(input => {
                    shift1Total += parseInt(input.value) || 0;
                });

                document.querySelectorAll('.hours-input[data-shift="shift2"]').forEach(input => {
                    shift2Total += parseInt(input.value) || 0;
                });

                if (shift1Total !== maxHours) {
                    alert('Total hours for Shift 1 must be exactly ' + maxHours + ' to proceed.');
                    return false;
                }
                if (shift2Total !== maxHours) {
                    alert('Total hours for Shift 2 must be exactly ' + maxHours + ' to proceed.');
                    return false;
                }
            } else {
                let totalAllocated = 0;
                document.querySelectorAll('.hours-input').forEach(input => {
                    totalAllocated += parseInt(input.value) || 0;
                });

                if (totalAllocated !== maxHours) {
                    alert('Total hours must be exactly ' + maxHours + ' to proceed.');
                    return false;
                }
            }
            return true;
        }

        function validateForNext() {
            if (!validateTotalHours()) {
                return false;
            }
            populateNoStaffSubjects();
            return removeValidation();
        }

        function validateForGenerate() {
            if (!validateTotalHours()) {
                return false;
            }
            populateNoStaffSubjects();
            return removeValidation();
        }

        function populateNoStaffSubjects() {
            const noStaffCheckboxes = document.querySelectorAll('.no-staff-cb:checked');
            const subjectIds = Array.from(noStaffCheckboxes).map(cb => cb.dataset.subjectId);
            const hiddenInput = document.getElementById('no_staff_subjects');
            if (hiddenInput) {
                hiddenInput.value = JSON.stringify(subjectIds);
            }
        }

        function removeValidation() {
            // Remove all required attributes to allow Previous navigation without blocking
            document.querySelectorAll('[required]').forEach(el => {
                el.removeAttribute('required');
            });
            // Also populate no staff subjects when clicking Previous
            populateNoStaffSubjects();
            return true;
        }
    </script>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
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
        <a href="../Subject/subject.php" class="tab">
            <span class="tab-icon">📚</span> Subjects
        </a>
        <a href="redirect_timetable.php" class="tab active">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
        <a href="generated_timetable_view.php" class="tab">
            <span class="tab-icon">📊</span> Generated Timetable
        </a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab">
            <span class="tab-icon">💾</span> Saved Timetables
        </a>
    </div>

    <div class="content">
        <?php if (isset($_SESSION['validation_error'])): ?>
            <div style="background-color: #fee2e2; border: 1px solid #ef4444; color: #b91c1c; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                <strong>Validation Failed:</strong> <?php echo htmlspecialchars($_SESSION['validation_error']); ?>
            </div>
            <?php unset($_SESSION['validation_error']); ?>
        <?php endif; ?>

        <div class="timetable-header">
            <h2>Timetable Generator</h2>
            <div class="semester-toggle">
                <a href="timetable.php?semester=odd"
                    class="btn <?php echo $semester_filter == 'odd' ? 'btn-primary' : 'btn-secondary'; ?>">Odd
                    Semester</a>
                <a href="timetable.php?semester=even"
                    class="btn <?php echo $semester_filter == 'even' ? 'btn-primary' : 'btn-secondary'; ?>">Even
                    Semester</a>
            </div>
        </div>

        <?php if (isset($_SESSION['trigger_validation']) && $_SESSION['trigger_validation']): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const firstEmpty = document.querySelector('.staff-select:invalid');
                    if (firstEmpty) {
                        firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Let the browser's native submit validation handle the tooltip
                        // by simulating a click on a hidden submit button
                        const form = firstEmpty.closest('form');
                        if (form) {
                            let tmpSubmit = form.querySelector('.tmp-submit-btn');
                            if (!tmpSubmit) {
                                tmpSubmit = document.createElement('button');
                                tmpSubmit.type = 'submit';
                                tmpSubmit.className = 'tmp-submit-btn';
                                tmpSubmit.style.display = 'none';
                                form.appendChild(tmpSubmit);
                            }
                            tmpSubmit.click();
                        }
                    }
                });
            </script>
            <?php unset($_SESSION['trigger_validation']); ?>
        <?php endif; ?>

        <div class="class-counter">
            Class <?php echo ($current_index + 1); ?> of <?php echo $total_classes; ?>
        </div>

        <div class="class-info">
            <h3>
                <?php echo htmlspecialchars($current_class_config['label']); ?>
                <span class="semester-badge">SEMESTER <?php echo $current_semester; ?></span>
            </h3>
            <?php if ($current_class_config['has_shifts']): ?>
                <p style="margin: 5px 0 0 0; color: #6b7280;">
                    Allocate staff for both Shift 1 and Shift 2
                </p>
            <?php endif; ?>
        </div>

        <form method="POST" action="timetable.php">
            <div class="action-buttons" style="margin-bottom: 20px;">
                <button type="submit" name="action" value="reset" class="btn-reset" onclick="removeValidation()">
                    🔄 Reset
                </button>

                <!-- Total Hours Display -->
                <?php if ($current_class_config['has_shifts']): ?>
                    <!-- Separate totals for each shift -->
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div class="total-hours-container status-under" id="total-shift1-container">
                            <span id="total-hours-shift1">Shift 1: 0 / 30 Hrs</span>
                        </div>
                        <div class="total-hours-container status-under" id="total-shift2-container">
                            <span id="total-hours-shift2">Shift 2: 0 / 30 Hrs</span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Single total for non-shift classes -->
                    <div class="total-hours-container status-under">
                        <span id="total-hours-display">Total: 0 / 30 Hrs</span>
                    </div>
                <?php endif; ?>

                <div class="nav-group">
                    <!-- Hidden inputs for manual allocation -->
                    <input type="hidden" name="manual_subject_id" id="manual_subject_id" value="">
                    <input type="hidden" name="manual_shift" id="manual_shift" value="">
                    <input type="hidden" name="manual_staff_index" id="manual_staff_index" value="">
                    <!-- Hidden inputs for ghost-key clearing: list every subject ID rendered on this page -->
                    <input type="hidden" name="page_subject_ids" value="<?php echo implode(',', array_column($subjects, 'id')); ?>">
                    <input type="hidden" name="page_has_shifts" value="<?php echo $current_class_config['has_shifts'] ? '1' : ''; ?>">
                    <!-- Hidden input for "No Staff Required" subjects -->
                    <input type="hidden" name="no_staff_subjects" id="no_staff_subjects" value="[]">

                    <?php if ($current_index > 0): ?>
                        <button type="submit" name="action" value="previous" class="btn-nav" onclick="removeValidation()">
                            ← Previous Class
                        </button>
                    <?php endif; ?>

                    <?php if ($current_index < $total_classes - 1): ?>
                        <button type="submit" name="action" value="next" class="btn-nav" onclick="return validateForNext()">
                            Next Class →
                        </button>
                    <?php else: ?>
                        <button type="submit" name="action" value="generate" class="btn-generate"
                            onclick="return validateForGenerate()">
                            ▶ Generate Timetable
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>SUBJECT</th>
                        <th>TYPE</th>
                        <?php if ($current_class_config['has_shifts']): ?>
                            <th>HOURS (SHIFT 1)</th>
                            <th>STAFF (SHIFT 1)</th>
                            <th>HOURS (SHIFT 2)</th>
                            <th>STAFF (SHIFT 2)</th>
                        <?php else: ?>
                            <th>HOURS</th>
                            <th>STAFF SELECTION</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subjects as $subject): ?>
                        <tr>
                            <td style="display:flex; align-items:center; gap:8px;">
                                <?php
                                    $is_no_staff_default = in_array(strtolower($subject['type']), ['common', 'allied', 'nme', 'nm']);
                                    $is_checked = $is_no_staff_default;
                                    
                                    // Override with user's saved choice if available
                                    if (isset($_SESSION['no_staff_subjects'][$current_index]) && is_array($_SESSION['no_staff_subjects'][$current_index])) {
                                        $is_checked = in_array($subject['id'], $_SESSION['no_staff_subjects'][$current_index]);
                                    }
                                ?>
                                <input type="checkbox" class="no-staff-cb" 
                                       data-subject-id="<?php echo $subject['id']; ?>"
                                       data-has-shifts="<?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>"
                                       onclick="toggleStaffRequirement(this, <?php echo $subject['id']; ?>, <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>)"
                                       <?php echo $is_checked ? 'checked' : ''; ?>
                                       title="Tick if no staff allocation is needed"
                                       style="width: 16px; height: 16px; cursor: pointer;">
                                <?php echo htmlspecialchars($subject['title']); ?>
                            </td>
                            <td>
                                <span class="type-badge type-<?php echo strtolower($subject['type']); ?>">
                                    <?php echo htmlspecialchars($subject['type']); ?>
                                </span>
                            </td>
                            <?php
                            $hours_key = 'hours_' . $subject['id'];
                            $current_hours = isset($_SESSION['hours_changes'][$hours_key])
                                ? $_SESSION['hours_changes'][$hours_key]
                                : $subject['hours_per_week'];
                            ?>

                            <?php if (in_array($subject['type'], ['Core', 'Lab', 'NM', 'NME', 'Project'])): ?>
                                <?php if ($current_class_config['has_shifts']): ?>
                                    <!-- Hours for Shift 1 -->
                                    <td>
                                        <?php
                                        $hours_key_shift1 = 'hours_shift1_' . $subject['id'];
                                        $current_hours_shift1 = isset($_SESSION['hours_changes'][$hours_key_shift1])
                                            ? $_SESSION['hours_changes'][$hours_key_shift1]
                                            : $subject['hours_per_week'];

                                        // Calculate manual allocations for Shift 1
                                        $manual_count_shift1 = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index]['shift1'])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index]['shift1'] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_shift1++;
                                            }
                                        }
                                        $remaining_hours_shift1 = max(0, $current_hours_shift1 - $manual_count_shift1);
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="<?php echo $hours_key_shift1; ?>"
                                                    value="<?php echo $current_hours_shift1; ?>"
                                                    min="<?php echo max(1, $manual_count_shift1); ?>" max="30" class="hours-input"
                                                    data-shift="shift1" data-subject-id="<?php echo $subject['id']; ?>" required>
                                                <button type="submit" name="action" value="manual" class="btn-manual"
                                                    onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift1')"
                                                    title="Set Manually">
                                                    Manual <?php echo $manual_count_shift1 > 0 ? "($manual_count_shift1)" : ""; ?>
                                                </button>
                                            </div>
                                            <?php if ($manual_count_shift1 > 0): ?>
                                                <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                    Remaining for Auto: <strong><?php echo $remaining_hours_shift1; ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Shift 1 Staff -->
                                    <td>
                                        <div class="staff-allocation-container"
                                            id="staff-container-shift1-<?php echo $subject['id']; ?>">
                                            <div class="staff-row">
                                                <?php
                                                $staff_key_shift1 = 'staff_shift1_' . $subject['id'];
                                                $selected_staff_shift1 = isset($_SESSION['staff_allocations'][$staff_key_shift1])
                                                    ? $_SESSION['staff_allocations'][$staff_key_shift1]
                                                    : '';
                                                ?>
                                                <select name="<?php echo $staff_key_shift1; ?>" class="staff-select"
                                                    data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>"
                                                    data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift1"
                                                    onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift1')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift1'); checkShiftConflict(this, 'shift1'); }"
                                                    required>
                                                    <option value="">Select Staff</option>
                                                    <?php foreach ($staff_list as $staff): ?>
                                                        <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff_shift1 == $staff['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($staff['name']); ?>
                                                            (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="clear-staff-btn"
                                                    onclick="clearStaffSelection('<?php echo $staff_key_shift1; ?>')"
                                                    title="Clear selection">×</button>
                                                <button type="button" class="add-staff-btn"
                                                    onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift1')">+ Add
                                                    Staff</button>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Hours for Shift 2 -->
                                    <td>
                                        <?php
                                        $hours_key_shift2 = 'hours_shift2_' . $subject['id'];
                                        $current_hours_shift2 = isset($_SESSION['hours_changes'][$hours_key_shift2])
                                            ? $_SESSION['hours_changes'][$hours_key_shift2]
                                            : $subject['hours_per_week'];

                                        // Calculate manual allocations for Shift 2
                                        $manual_count_shift2 = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index]['shift2'])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index]['shift2'] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_shift2++;
                                            }
                                        }
                                        $remaining_hours_shift2 = max(0, $current_hours_shift2 - $manual_count_shift2);
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="<?php echo $hours_key_shift2; ?>"
                                                    value="<?php echo $current_hours_shift2; ?>"
                                                    min="<?php echo max(1, $manual_count_shift2); ?>" max="30" class="hours-input"
                                                    data-shift="shift2" data-subject-id="<?php echo $subject['id']; ?>" required>
                                                <button type="submit" name="action" value="manual" class="btn-manual"
                                                    onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift2')"
                                                    title="Set Manually">
                                                    Manual <?php echo $manual_count_shift2 > 0 ? "($manual_count_shift2)" : ""; ?>
                                                </button>
                                            </div>
                                            <?php if ($manual_count_shift2 > 0): ?>
                                                <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                    Remaining for Auto: <strong><?php echo $remaining_hours_shift2; ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- Shift 2 Staff -->
                                    <td>
                                        <div class="staff-allocation-container"
                                            id="staff-container-shift2-<?php echo $subject['id']; ?>">
                                            <div class="staff-row">
                                                <?php
                                                $staff_key_shift2 = 'staff_shift2_' . $subject['id'];
                                                $selected_staff_shift2 = isset($_SESSION['staff_allocations'][$staff_key_shift2])
                                                    ? $_SESSION['staff_allocations'][$staff_key_shift2]
                                                    : '';
                                                ?>
                                                <select name="<?php echo $staff_key_shift2; ?>" class="staff-select"
                                                    data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>"
                                                    data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift2"
                                                    onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift2')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift2'); checkShiftConflict(this, 'shift2'); }"
                                                    required>
                                                    <option value="">Select Staff</option>
                                                    <?php foreach ($staff_list as $staff): ?>
                                                        <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff_shift2 == $staff['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($staff['name']); ?>
                                                            (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="clear-staff-btn"
                                                    onclick="clearStaffSelection('<?php echo $staff_key_shift2; ?>')"
                                                    title="Clear selection">×</button>
                                                <button type="button" class="add-staff-btn"
                                                    onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift2')">+ Add
                                                    Staff</button>
                                            </div>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <!-- Single hours and staff for M.Sc -->
                                    <td>
                                        <?php
                                        // Check if this subject has split hours
                                        $has_split_hours_msc = false;
                                        $split_hours_values_msc = [];
                                        $split_count_msc = 1;

                                        // Check for split hours in session
                                        while (isset($_SESSION['hours_changes']['hours_' . $subject['id'] . '_' . $split_count_msc])) {
                                            $has_split_hours_msc = true;
                                            $split_hours_values_msc[] = $_SESSION['hours_changes']['hours_' . $subject['id'] . '_' . $split_count_msc];
                                            $split_count_msc++;
                                        }

                                        // Calculate manual allocations for Single Shift
                                        $manual_count_single = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index][''])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index][''] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_single++;
                                            }
                                        }

                                        if ($has_split_hours_msc):
                                            // Render split hours inputs
                                            ?>
                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                <div style="display: flex; align-items: flex-start; gap: 5px;">
                                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                                        <?php foreach ($split_hours_values_msc as $idx_msc => $hours_val_msc): ?>
                                                            <input type="number"
                                                                name="hours_<?php echo $subject['id']; ?>_<?php echo ($idx_msc + 1); ?>"
                                                                class="hours-input split-hours"
                                                                data-subject-id="<?php echo $subject['id']; ?>"
                                                                value="<?php echo max(0, $hours_val_msc); ?>" min="0" max="30"
                                                                onchange="updateSplitHoursTotal(<?php echo $subject['id']; ?>, null)"
                                                                required>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <button type="submit" name="action" value="manual" class="btn-manual"
                                                        onclick="return setManualAllocation(<?php echo $subject['id']; ?>, '')"
                                                        title="Set Manually">
                                                        Manual <?php echo $manual_count_single > 0 ? "($manual_count_single)" : ""; ?>
                                                    </button>
                                                </div>
                                                <?php if ($manual_count_single > 0): ?>
                                                    <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                        Total Remaining for Auto:
                                                        <strong><?php echo max(0, array_sum($split_hours_values_msc) - $manual_count_single); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <input type="hidden" name="<?php echo $hours_key; ?>"
                                                value="<?php echo array_sum($split_hours_values_msc); ?>">
                                        <?php else: ?>
                                            <!-- Single hours input -->
                                            <?php
                                            $remaining_hours_single = max(0, $current_hours - $manual_count_single);
                                            ?>
                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                <div style="display: flex; align-items: center; gap: 5px;">
                                                    <input type="number" name="<?php echo $hours_key; ?>"
                                                        value="<?php echo $current_hours; ?>"
                                                        min="<?php echo max(1, $manual_count_single); ?>" max="30" class="hours-input"
                                                        data-subject-id="<?php echo $subject['id']; ?>" required>
                                                    <button type="submit" name="action" value="manual" class="btn-manual"
                                                        onclick="return setManualAllocation(<?php echo $subject['id']; ?>, '')"
                                                        title="Set Manually">
                                                        Manual <?php echo $manual_count_single > 0 ? "($manual_count_single)" : ""; ?>
                                                    </button>
                                                </div>
                                                <?php if ($manual_count_single > 0): ?>
                                                    <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                        Remaining for Auto: <strong><?php echo $remaining_hours_single; ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="staff-allocation-container" id="staff-container-<?php echo $subject['id']; ?>">
                                            <div class="staff-row">
                                                <?php
                                                $staff_key = 'staff_' . $subject['id'] . '_' . $shift1_class['id'];
                                                $selected_staff = isset($_SESSION['staff_allocations'][$staff_key])
                                                    ? $_SESSION['staff_allocations'][$staff_key]
                                                    : '';
                                                ?>
                                                <select name="<?php echo $staff_key; ?>" class="staff-select"
                                                    data-subject-id="<?php echo $subject['id']; ?>"
                                                    onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }"
                                                    required>
                                                    <option value="">Select Staff</option>
                                                    <?php foreach ($staff_list as $staff): ?>
                                                        <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff == $staff['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($staff['name']); ?>
                                                            (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="clear-staff-btn"
                                                    onclick="clearStaffSelection('<?php echo $staff_key; ?>')"
                                                    title="Clear selection">×</button>
                                                <button type="button" class="add-staff-btn"
                                                    onclick="addStaffAllocation(<?php echo $subject['id']; ?>, null)">+ Add
                                                    Staff</button>
                                            </div>
                                            <?php
                                            // Check for additional split staff allocations from session
                                            $split_staff_count = 2;
                                            while (isset($_SESSION['staff_allocations']['staff_' . $subject['id'] . '_' . $split_staff_count])) {
                                                $split_staff_key = 'staff_' . $subject['id'] . '_' . $split_staff_count;
                                                $split_selected_staff = $_SESSION['staff_allocations'][$split_staff_key];
                                                ?>
                                                <div class="staff-row"
                                                    id="staff-row-staff-container-<?php echo $subject['id']; ?>-<?php echo $split_staff_count; ?>">
                                                    <select name="<?php echo $split_staff_key; ?>" class="staff-select"
                                                        data-subject-id="<?php echo $subject['id']; ?>"
                                                        onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }"
                                                        required>
                                                        <option value="">Select Staff</option>
                                                        <?php foreach ($staff_list as $staff): ?>
                                                            <option value="<?php echo $staff['id']; ?>" <?php echo $split_selected_staff == $staff['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($staff['name']); ?>
                                                                (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="button" class="remove-staff-btn"
                                                        onclick="removeStaffAllocation('staff-container-<?php echo $subject['id']; ?>', <?php echo $split_staff_count; ?>, <?php echo $subject['id']; ?>, null)">×</button>
                                                </div>
                                                <?php
                                                $split_staff_count++;
                                            }

                                            // If we found split staff, we need to cache the counter and initialize in JavaScript
                                            if ($split_staff_count > 2) {
                                                ?>
                                                <script>
                                                    document.addEventListener('DOMContentLoaded', function () {
                                                        if (typeof staffCounter === 'undefined') staffCounter = {};
                                                        if (typeof hoursContainerCache === 'undefined') hoursContainerCache = {};
                                                        staffCounter['staff-container-<?php echo $subject['id']; ?>'] = <?php echo ($split_staff_count - 1); ?>;
                                                    });
                                                </script>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($current_class_config['has_shifts']): ?>
                                    <!-- No Staff Required for both shifts - but still need hours inputs -->
                                    <td>
                                        <?php
                                        $hours_key_shift1 = 'hours_shift1_' . $subject['id'];
                                        $current_hours_shift1 = isset($_SESSION['hours_changes'][$hours_key_shift1])
                                            ? $_SESSION['hours_changes'][$hours_key_shift1]
                                            : $subject['hours_per_week'];

                                        // Calculate manual allocations for Shift 1
                                        $manual_count_shift1 = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index]['shift1'])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index]['shift1'] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_shift1++;
                                            }
                                        }
                                        $remaining_hours_shift1 = max(0, $current_hours_shift1 - $manual_count_shift1);
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="<?php echo $hours_key_shift1; ?>"
                                                    value="<?php echo $current_hours_shift1; ?>"
                                                    min="<?php echo max(1, $manual_count_shift1); ?>" max="30" class="hours-input"
                                                    data-shift="shift1" data-subject-id="<?php echo $subject['id']; ?>" required>
                                                <button type="submit" name="action" value="manual" class="btn-manual"
                                                    onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift1')"
                                                    title="Set Manually">
                                                    Manual <?php echo $manual_count_shift1 > 0 ? "($manual_count_shift1)" : ""; ?>
                                                </button>
                                            </div>
                                            <?php if ($manual_count_shift1 > 0): ?>
                                                <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                    Remaining for Auto: <strong><?php echo $remaining_hours_shift1; ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="no-staff-required">No Staff Required</td>
                                    <td>
                                        <?php
                                        $hours_key_shift2 = 'hours_shift2_' . $subject['id'];
                                        $current_hours_shift2 = isset($_SESSION['hours_changes'][$hours_key_shift2])
                                            ? $_SESSION['hours_changes'][$hours_key_shift2]
                                            : $subject['hours_per_week'];

                                        // Calculate manual allocations for Shift 2
                                        $manual_count_shift2 = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index]['shift2'])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index]['shift2'] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_shift2++;
                                            }
                                        }
                                        $remaining_hours_shift2 = max(0, $current_hours_shift2 - $manual_count_shift2);
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="<?php echo $hours_key_shift2; ?>"
                                                    value="<?php echo $current_hours_shift2; ?>"
                                                    min="<?php echo max(1, $manual_count_shift2); ?>" max="30" class="hours-input"
                                                    data-shift="shift2" data-subject-id="<?php echo $subject['id']; ?>" required>
                                                <button type="submit" name="action" value="manual" class="btn-manual"
                                                    onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift2')"
                                                    title="Set Manually">
                                                    Manual <?php echo $manual_count_shift2 > 0 ? "($manual_count_shift2)" : ""; ?>
                                                </button>
                                            </div>
                                            <?php if ($manual_count_shift2 > 0): ?>
                                                <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                    Remaining for Auto: <strong><?php echo $remaining_hours_shift2; ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="no-staff-required">No Staff Required</td>
                                <?php else: ?>
                                    <!-- No Staff Required for single class -->
                                    <td>
                                        <?php
                                        $manual_count_single = 0;
                                        if (isset($_SESSION['manual_allocations'][$current_index][''])) {
                                            foreach ($_SESSION['manual_allocations'][$current_index][''] as $alloc) {
                                                if ($alloc['subject_id'] == $subject['id'])
                                                    $manual_count_single++;
                                            }
                                        }
                                        $remaining_hours_single = max(0, $current_hours - $manual_count_single);
                                        ?>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <div style="display: flex; align-items: center; gap: 5px;">
                                                <input type="number" name="<?php echo $hours_key; ?>"
                                                    value="<?php echo $current_hours; ?>"
                                                    min="<?php echo max(1, $manual_count_single); ?>" max="30" class="hours-input"
                                                    data-subject-id="<?php echo $subject['id']; ?>" required>
                                                <button type="submit" name="action" value="manual" class="btn-manual"
                                                    onclick="return setManualAllocation(<?php echo $subject['id']; ?>, '')"
                                                    title="Set Manually">
                                                    Manual <?php echo $manual_count_single > 0 ? "($manual_count_single)" : ""; ?>
                                                </button>
                                            </div>
                                            <?php if ($manual_count_single > 0): ?>
                                                <div style="font-size: 11px; color: #666; padding-left: 2px;">
                                                    Remaining for Auto: <strong><?php echo $remaining_hours_single; ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="no-staff-required">No Staff Required</td>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</body>

</html>
<?php $conn->close(); ?>