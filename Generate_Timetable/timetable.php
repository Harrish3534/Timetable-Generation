<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

require_once 'default_allocations.php';

// Dynamically inject Lab IDs into defaults based on actual Database IDs 
$lab_res = $conn->query("SELECT id, title, semester, program, type FROM subjects WHERE type = 'Lab' OR title LIKE '%python%' OR title LIKE '%c#%' OR title LIKE '%osc%' OR title LIKE '%open source%' OR title LIKE '%data mining%'");
if ($lab_res) {
    while ($r = $lab_res->fetch_assoc()) {
        $lab_id = intval($r['id']);
        $lab_title = strtolower($r['title']);
        $sem = intval($r['semester']);
        $prog = $r['program'];
        $is_lab = ($r['type'] === 'Lab');
        
        // ==========================================
        // ODD SEMESTERS
        // ==========================================
        // I B.Sc (Sem 1) - class_index = 0
        if ($sem == 1 && $prog == 'UG' && $is_lab && strpos($lab_title, 'programming methodology') !== false) {
            $default_manual_allocations['odd'][0]['shift1']['I DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift1']['I DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift1']['V DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift1']['V DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null];

            $default_manual_allocations['odd'][0]['shift2']['II DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift2']['II DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift2']['V DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][0]['shift2']['V DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null];
        }
        // II B.Sc (Sem 3) - class_index = 1
        if ($sem == 3 && $prog == 'UG' && $is_lab && strpos($lab_title, 'java') !== false) {
            $default_manual_allocations['odd'][1]['shift1']['V DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][1]['shift1']['V DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][1]['shift1']['V DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null];

            $default_manual_allocations['odd'][1]['shift2']['I DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'III HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][1]['shift2']['I DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
            $default_manual_allocations['odd'][1]['shift2']['I DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null];
        }
        // III B.Sc (Sem 5) - class_index = 2
        if ($sem == 5 && $prog == 'UG' && $is_lab) {
            if (strpos($lab_title, 'internet technologies') !== false) {
                $default_manual_allocations['odd'][2]['shift1']['IV DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift1']['IV DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift1']['IV DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'III HOUR', 'staff_index' => null];

                $default_manual_allocations['odd'][2]['shift2']['IV DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift2']['IV DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift2']['IV DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'III HOUR', 'staff_index' => null];
            }
            if (strpos($lab_title, 'linux shell') !== false) {
                $default_manual_allocations['odd'][2]['shift1']['VI DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift1']['VI DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null];

                $default_manual_allocations['odd'][2]['shift2']['VI DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][2]['shift2']['VI DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }
        }
        // I M.Sc (Sem 1) - class_index = 3 
        if ($sem == 1 && $prog == 'PG' && $is_lab) {
            if (strpos($lab_title, 'advanced java') !== false || strpos($lab_title, 'advance programming') !== false) {
                $default_manual_allocations['odd'][3]['']['II DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][3]['']['II DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][3]['']['II DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'III HOUR', 'staff_index' => null];
            }
            if (strpos($lab_title, 'dbms') !== false || strpos($lab_title, 'database') !== false) {
                $default_manual_allocations['odd'][3]['']['IV DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][3]['']['IV DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }
        }
        // II M.Sc (Sem 3) - class_index = 4
        if ($sem == 3 && $prog == 'PG' && $is_lab) {
            if (strpos($lab_title, 'dip') !== false || strpos($lab_title, 'image') !== false) {
                $default_manual_allocations['odd'][4]['']['VI DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][4]['']['VI DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][4]['']['VI DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'III HOUR', 'staff_index' => null];
            }
            if (strpos($lab_title, 'mobile') !== false) {
                $default_manual_allocations['odd'][4]['']['III DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['odd'][4]['']['III DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }
        }

        // ==========================================
        // EVEN SEMESTERS
        // ==========================================
        if ($sem % 2 == 0) {
            $class_idx = ($sem == 2) ? 0 : (($sem == 4) ? 1 : 2);
            if ($prog == 'PG') {
                $class_idx = ($sem == 2) ? 3 : 4;
            }

            // I B.Sc (Sem 2) C++ Lab
            if ($sem == 2 && $prog == 'UG' && $is_lab && strpos($lab_title, 'c++') !== false) {
                $default_manual_allocations['even'][$class_idx]['shift1']['V DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'III HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift1']['V DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift1']['V DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null];

                $default_manual_allocations['even'][$class_idx]['shift2']['IV DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'III HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift2']['IV DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift2']['IV DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }
            
            // II B.Sc (Sem 4) Python & DBMS
            if ($sem == 4 && $prog == 'UG' && $is_lab) {
                if (strpos($lab_title, 'python') !== false) {
                    $default_manual_allocations['even'][$class_idx]['shift1']['II DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift1']['II DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'V HOUR', 'staff_index' => null];

                    $default_manual_allocations['even'][$class_idx]['shift2']['II DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift2']['II DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'II DAY', 'hour' => 'V HOUR', 'staff_index' => null];
                }
                if (strpos($lab_title, 'dbms') !== false || strpos($lab_title, 'database') !== false) {
                    $default_manual_allocations['even'][$class_idx]['shift1']['VI DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift1']['VI DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'VI DAY', 'hour' => 'V HOUR', 'staff_index' => null];

                    $default_manual_allocations['even'][$class_idx]['shift2']['IV DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift2']['IV DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'IV DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                }
            }

            // C# Lab (both shifts, UG)
            if ($prog == 'UG' && $is_lab && strpos($lab_title, 'c#') !== false) {
                $default_manual_allocations['even'][$class_idx]['shift1']['I DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift1']['I DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift1']['I DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'III HOUR', 'staff_index' => null];

                $default_manual_allocations['even'][$class_idx]['shift2']['I DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift2']['I DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift2']['I DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'III HOUR', 'staff_index' => null];
            }

            // OSC Lab (UG)
            if ($prog == 'UG' && $is_lab && (strpos($lab_title, 'osc') !== false || strpos($lab_title, 'open source') !== false)) {
                $default_manual_allocations['even'][$class_idx]['shift1']['V DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift1']['V DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'II HOUR', 'staff_index' => null];

                $default_manual_allocations['even'][$class_idx]['shift2']['V DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][$class_idx]['shift2']['V DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'V DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }

            // Data Mining Lab (UG / PG)
            if ($is_lab && (strpos($lab_title, 'data mining') !== false || strpos($lab_title, 'dm&r') !== false)) {
                if ($prog == 'UG') {
                    $default_manual_allocations['even'][$class_idx]['shift1']['III DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift1']['III DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift1']['III DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null];

                    $default_manual_allocations['even'][$class_idx]['shift2']['III DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift2']['III DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['shift2']['III DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null];
                } else {
                    $default_manual_allocations['even'][$class_idx]['']['III DAY_I HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'I HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['']['III DAY_II HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'II HOUR', 'staff_index' => null];
                    $default_manual_allocations['even'][$class_idx]['']['III DAY_III HOUR'] = ['subject_id' => $lab_id, 'day' => 'III DAY', 'hour' => 'III HOUR', 'staff_index' => null];
                }
            }

            // Python Programming for 1st M.Sc (PG Sem 2)
            if ($sem == 2 && $prog == 'PG' && strpos($lab_title, 'python') !== false) {
                $default_manual_allocations['even'][3]['']['I DAY_IV HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'IV HOUR', 'staff_index' => null];
                $default_manual_allocations['even'][3]['']['I DAY_V HOUR'] = ['subject_id' => $lab_id, 'day' => 'I DAY', 'hour' => 'V HOUR', 'staff_index' => null];
            }
        }
    }
}

/**
 * Merge default manual allocations into the session without overwriting
 * any slots the user has already customised.
 */
function seedDefaultAllocations(array &$session_alloc, array $defaults, string $semester_type): void
{
    if (!isset($defaults[$semester_type])) {
        return; 
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

// Seed default manual allocations - Version BUMPED to v20
if (!isset($_SESSION['manual_allocations_seeded_v20'])) {
    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }

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

    if (isset($_SESSION['manual_allocations'][0]['shift2'])) {
        foreach ($_SESSION['manual_allocations'][0]['shift2'] as $slot_key => $alloc) {
            if (in_array(intval($alloc['subject_id']), [16, 17, 21, 23])) {
                unset($_SESSION['manual_allocations'][0]['shift2'][$slot_key]);
            }
        }
    }

    foreach (['shift1', 'shift2', ''] as $sh) {
        if (isset($_SESSION['manual_allocations'][1][$sh])) {
            foreach ($_SESSION['manual_allocations'][1][$sh] as $slot_key => $alloc) {
                if (in_array(intval($alloc['subject_id']), [32, 33, 39])) {
                    unset($_SESSION['manual_allocations'][1][$sh][$slot_key]);
                }
            }
        }
    }

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
    $_SESSION['manual_allocations_seeded_v20'] = true;
}

unset($_SESSION['current_page']);

// Handle semester selection
if (isset($_GET['semester'])) {
    $_SESSION['semester_filter'] = $_GET['semester'];
    $_SESSION['current_class_index'] = 0;

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

                    if (!$is_current_sem) {
                        unset($_SESSION['manual_allocations'][$class_idx][$shift][$slot_key]);
                    }
                }
            }
        }
    }

    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }
    seedDefaultAllocations($_SESSION['manual_allocations'], $default_manual_allocations, $_SESSION['semester_filter']);
    // Clear no_staff_subjects so type-based defaults are re-seeded for new semester
    unset($_SESSION['no_staff_subjects']);

    header("Location: timetable.php");
    exit;
}

// Handle reset
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
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
            if ($class_idx == 0) $sem_num = ($sem_filter == 'odd' ? 1 : 2);
            elseif ($class_idx == 1) $sem_num = ($sem_filter == 'odd' ? 3 : 4);
            elseif ($class_idx == 2) $sem_num = ($sem_filter == 'odd' ? 5 : 6);
            elseif ($class_idx == 3) $sem_num = ($sem_filter == 'odd' ? 1 : 2);
            elseif ($class_idx == 4) $sem_num = ($sem_filter == 'odd' ? 3 : 4);
            
            $is_current_sem = ($sem_filter === 'odd' && in_array($sem_num, [1, 3, 5])) ||
                ($sem_filter === 'even' && in_array($sem_num, [2, 4, 6]));
            if ($is_current_sem)
                unset($_SESSION['manual_allocations'][$class_idx]);
        }
    }
    $_SESSION['current_class_index'] = 0;
    if (!isset($_SESSION['manual_allocations'])) {
        $_SESSION['manual_allocations'] = [];
    }
    // Clear no_staff_subjects so type-based defaults are re-seeded on next page load
    unset($_SESSION['no_staff_subjects']);
    seedDefaultAllocations($_SESSION['manual_allocations'], $default_manual_allocations, $_SESSION['semester_filter']);
    header("Location: timetable.php");
    exit;
}

// Handle navigation & submission
if (isset($_POST['action']) && in_array($_POST['action'], ['next', 'previous', 'manual', 'generate'])) {
    
    $page_subject_ids = array_map('intval', explode(',', $_POST['page_subject_ids'] ?? ''));
    $page_has_shifts   = !empty($_POST['page_has_shifts']);
    clearSubjectSessionKeys($page_subject_ids, $page_has_shifts);

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

    if ($_POST['action'] === 'next') {
        $_SESSION['current_class_index']++;
        header("Location: timetable.php");
        exit;
    }
    
    if ($_POST['action'] === 'previous') {
        $_SESSION['current_class_index']--;
        if ($_SESSION['current_class_index'] < 0) {
            $_SESSION['current_class_index'] = 0;
        }
        header("Location: timetable.php");
        exit;
    }
    
    if ($_POST['action'] === 'manual') {
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

    if ($_POST['action'] === 'generate') {
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
            if ($idx == 0) $sem_num = ($semester_filter == 'odd' ? 1 : 2);
            elseif ($idx == 1) $sem_num = ($semester_filter == 'odd' ? 3 : 4);
            elseif ($idx == 2) $sem_num = ($semester_filter == 'odd' ? 5 : 6);
            elseif ($idx == 3) $sem_num = ($semester_filter == 'odd' ? 1 : 2);
            elseif ($idx == 4) $sem_num = ($semester_filter == 'odd' ? 3 : 4);

            $program = $c_config['type'];
            $q = "SELECT * FROM subjects WHERE program = '$program' AND semester = $sem_num AND COALESCE(is_allocated, 1) = 1 ORDER BY sort_order, id";
            $res = $conn->query($q);

            while ($sub = $res->fetch_assoc()) {
                if (in_array($sub['type'], ['Core', 'Lab', 'NM', 'NME', 'Project'])) {
                    $is_no_staff_required = false;
                    if (isset($_SESSION['no_staff_subjects'][$idx]) && is_array($_SESSION['no_staff_subjects'][$idx])) {
                        if (in_array($sub['id'], $_SESSION['no_staff_subjects'][$idx])) {
                            $is_no_staff_required = true;
                        }
                    } else {
                        $type_lower = strtolower($sub['type']);
                        $title_lower = strtolower($sub['title']);
                        
                        if (in_array($type_lower, ['common', 'allied', 'nme', 'non major elective'])) {
                            $is_no_staff_required = true;
                        }
                        if (strpos($title_lower, 'nme') !== false || strpos($title_lower, 'non major elective') !== false) {
                            $is_no_staff_required = true;
                        }
                        // 2nd M.Sc is Class Index 4
                        if (($type_lower === 'project' || strpos($title_lower, 'project') !== false) && $program === 'PG' && $idx == 4) {
                            $is_no_staff_required = true;
                        }
                    }
                    
                    if ($is_no_staff_required) {
                        continue;
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

        foreach ($_SESSION['hours_changes'] as $key => $value) {
            $subject_id = intval(str_replace('hours_', '', $key));
            if ($value >= 1 && $value <= 30) {
                $stmt = $conn->prepare("UPDATE subjects SET hours_per_week = ? WHERE id = ?");
                $stmt->bind_param("ii", $value, $subject_id);
                $stmt->execute();
            }
        }

        header("Location: generate_timetable.php?semester=" . $_SESSION['semester_filter']);
        exit;
    }
}

$semester_filter = $_SESSION['semester_filter'];
$current_index = $_SESSION['current_class_index'];

$class_sequence = [
    ['pattern' => 'I B.Sc%', 'label' => 'I B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'II B.Sc%', 'label' => 'II B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'III B.Sc%', 'label' => 'III B.Sc', 'type' => 'UG', 'has_shifts' => true],
    ['pattern' => 'I M.Sc%', 'label' => 'I M.Sc', 'type' => 'PG', 'has_shifts' => false],
    ['pattern' => 'II M.Sc%', 'label' => 'II M.Sc', 'type' => 'PG', 'has_shifts' => false],
];

$total_classes = count($class_sequence);

if ($current_index >= $total_classes) {
    $current_index = $total_classes - 1;
    $_SESSION['current_class_index'] = $current_index;
}

$current_class_config = $class_sequence[$current_index];

$semester_numbers = [
    0 => $semester_filter == 'odd' ? 1 : 2,
    1 => $semester_filter == 'odd' ? 3 : 4,
    2 => $semester_filter == 'odd' ? 5 : 6,
    3 => $semester_filter == 'odd' ? 1 : 2,
    4 => $semester_filter == 'odd' ? 3 : 4,
];

$current_semester = $semester_numbers[$current_index];

$program = $current_class_config['type'];
$subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $current_semester AND COALESCE(is_allocated, 1) = 1 ORDER BY sort_order, id";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
while ($subject = $subjects_result->fetch_assoc()) {
    $subjects[] = $subject;
    $sid = $subject['id'];
    $db_hours = $subject['hours_per_week'];
    if (!isset($_SESSION['hours_changes'])) {
        $_SESSION['hours_changes'] = [];
    }
    // Only set from DB if not already customised by the user
    if (!isset($_SESSION['hours_changes']['hours_' . $sid])) {
        $_SESSION['hours_changes']['hours_' . $sid] = $db_hours;
    }
    if (!isset($_SESSION['hours_changes']['hours_shift1_' . $sid])) {
        $_SESSION['hours_changes']['hours_shift1_' . $sid] = $db_hours;
    }
    if (!isset($_SESSION['hours_changes']['hours_shift2_' . $sid])) {
        $_SESSION['hours_changes']['hours_shift2_' . $sid] = $db_hours;
    }
}

// Always re-seed no_staff_subjects so type-based defaults are always applied
// (user can still override by manually ticking/unticking and navigating)
$default_no_staff_ids = [];
foreach ($subjects as $sub) {
    $t = strtolower($sub['type']);
    $ttl = strtolower($sub['title']);
    // Common, Allied, NME only — NOT NM (Naan Mudhalvan needs staff)
    if (in_array($t, ['common', 'allied', 'nme', 'non major elective'])) {
        $default_no_staff_ids[] = strval($sub['id']);
    } elseif (strpos($ttl, 'nme') !== false || strpos($ttl, 'non major elective') !== false) {
        $default_no_staff_ids[] = strval($sub['id']);
    } elseif (($t === 'project' || strpos($ttl, 'project') !== false) && $program === 'PG' && $current_index == 4) {
        $default_no_staff_ids[] = strval($sub['id']);
    }
}
if (!isset($_SESSION['no_staff_subjects'])) {
    $_SESSION['no_staff_subjects'] = [];
}
$_SESSION['no_staff_subjects'][$current_index] = $default_no_staff_ids;

// For I M.Sc (index 3) and II M.Sc (index 4): apply default hours for theory and labs.
// Theory: MAD & Elective = 5 hrs (to reach 30 hrs total).
// Lab (II M.Sc only): DIP Lab = 3 hrs, Mobile Apps Dev Lab = 2 hrs.
// These are applied only when the session key equals the raw DB value,
// meaning either it was just seeded or it truly matches the DB — user edits survive.
if (($current_index == 3 || $current_index == 4) && $program === 'PG') {
    foreach ($subjects as $sub) {
        $sid  = $sub['id'];
        $ttl  = strtolower($sub['title']);
        $t    = strtolower($sub['type']);
        $raw  = $sub['hours_per_week']; // raw DB value (used as sentinel)

        // Get current session value, default to raw DB value if not set
        $cur = $_SESSION['hours_changes']['hours_' . $sid] ?? $raw;

        if ($t === 'lab') {
            if ($current_index == 4) { // Only for II M.Sc
                $is_dip        = strpos($ttl, 'dip') !== false
                               || strpos($ttl, 'digital image') !== false
                               || (strpos($ttl, 'image') !== false && strpos($ttl, 'processing') !== false);
                $is_mobile_lab = strpos($ttl, 'mobile') !== false && strpos($ttl, 'lab') !== false;

                if ($is_dip && $cur == $raw) {
                    $_SESSION['hours_changes']['hours_' . $sid] = 3;
                } elseif ($is_mobile_lab && $cur == $raw) {
                    $_SESSION['hours_changes']['hours_' . $sid] = 2;
                }
            }
            continue; // skip theory logic for labs
        }

        // Theory subjects logic
        $is_mad      = strpos($ttl, 'mobile') !== false || strpos($ttl, 'mad') !== false;
        $is_elective = ($t === 'core' && strpos($ttl, 'elective') !== false) || $t === 'elective';
        
        if (($is_mad || $is_elective) && $cur == $raw) {
            $_SESSION['hours_changes']['hours_' . $sid] = 5;
        }
    }
}




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

$subject_semesters = [];
$sem_result = $conn->query("SELECT id, semester FROM subjects");
while ($row = $sem_result->fetch_assoc()) {
    $subject_semesters[$row['id']] = $row['semester'];
}

$staff_hours_used = [];
if (isset($_SESSION['staff_allocations'])) {
    foreach ($_SESSION['staff_allocations'] as $key => $staff_id) {
        if (empty($staff_id)) continue;

        $subject_id = null;
        $shift = null;
        $staff_index = null;
        $parts = explode('_', $key);

        if (strpos($key, 'staff_shift1_') === 0) {
            $shift = 'shift1';
            $subject_id = intval($parts[2]);
            if (count($parts) >= 4) $staff_index = intval($parts[3]);
        } elseif (strpos($key, 'staff_shift2_') === 0) {
            $shift = 'shift2';
            $subject_id = intval($parts[2]);
            if (count($parts) >= 4) $staff_index = intval($parts[3]);
        } elseif (strpos($key, 'staff_') === 0) {
            if (count($parts) >= 2) {
                $subject_id = intval($parts[1]);
                if (count($parts) >= 3) {
                    $potential_index = intval($parts[2]);
                    $split_hours_check = 'hours_' . $subject_id . '_' . $potential_index;
                    if (isset($_SESSION['hours_changes'][$split_hours_check]) || $potential_index <= 10) {
                        $staff_index = $potential_index;
                    }
                }
            }
        }

        if ($subject_id) {
            $sub_sem = isset($subject_semesters[$subject_id]) ? intval($subject_semesters[$subject_id]) : 0;
            $is_current_sem = ($semester_filter === 'odd' && in_array($sub_sem, [1, 3, 5])) ||
                ($semester_filter === 'even' && in_array($sub_sem, [2, 4, 6]));

            if (!$is_current_sem) continue;

            $subject_hours = 0;
            if ($staff_index !== null && $staff_index > 0) {
                $split_hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id . '_' . $staff_index : 'hours_' . $subject_id . '_' . $staff_index;

                if (isset($_SESSION['hours_changes'][$split_hours_key])) {
                    $subject_hours = $_SESSION['hours_changes'][$split_hours_key];
                } else {
                    if ($staff_index == 1) {
                        $fallback_key_1 = $shift ? 'hours_' . $shift . '_' . $subject_id . '_1' : 'hours_' . $subject_id . '_1';
                        if (isset($_SESSION['hours_changes'][$fallback_key_1])) {
                            $subject_hours = $_SESSION['hours_changes'][$fallback_key_1];
                            $split_hours_key = null;
                        }
                    }

                    if ($split_hours_key !== null) {
                        $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
                        if (isset($_SESSION['hours_changes'][$hours_key])) {
                            $subject_hours = $_SESSION['hours_changes'][$hours_key];
                        } else {
                            $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                            if ($hours_row = $hours_result->fetch_assoc()) $subject_hours = $hours_row['hours_per_week'];
                        }
                    }
                }
            } else {
                $next_staff_key = $shift ? 'staff_' . $shift . '_' . $subject_id . '_2' : 'staff_' . $subject_id . '_2';
                $is_actually_split = isset($_SESSION['staff_allocations'][$next_staff_key]) && !empty($_SESSION['staff_allocations'][$next_staff_key]);

                if ($is_actually_split) {
                    $split_hours_key_1 = $shift ? 'hours_' . $shift . '_' . $subject_id . '_1' : 'hours_' . $subject_id . '_1';
                    if (isset($_SESSION['hours_changes'][$split_hours_key_1])) {
                        $subject_hours = $_SESSION['hours_changes'][$split_hours_key_1];
                    } else {
                        $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
                        if (isset($_SESSION['hours_changes'][$hours_key])) {
                            $subject_hours = $_SESSION['hours_changes'][$hours_key];
                        } else {
                            $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                            if ($hours_row = $hours_result->fetch_assoc()) $subject_hours = $hours_row['hours_per_week'];
                        }
                    }
                } else {
                    $hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
                    if (isset($_SESSION['hours_changes'][$hours_key])) {
                        $subject_hours = $_SESSION['hours_changes'][$hours_key];
                    } else {
                        $hours_result = $conn->query("SELECT hours_per_week FROM subjects WHERE id = $subject_id");
                        if ($hours_row = $hours_result->fetch_assoc()) $subject_hours = $hours_row['hours_per_week'];
                    }
                }
            }

            if (!isset($staff_hours_used[$staff_id])) $staff_hours_used[$staff_id] = 0;
            $staff_hours_used[$staff_id] += intval($subject_hours);
        }
    }
}

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
    while ($row = $result->fetch_assoc()) $classes[] = $row;

    if (count($classes) >= 1) $shift1_class = $classes[0];
    if (count($classes) >= 2) $shift2_class = $classes[1];
} else {
    $class_query = "SELECT * FROM classes WHERE name LIKE ? LIMIT 1";
    $stmt = $conn->prepare($class_query);
    $pattern = $current_class_config['pattern'];
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $shift1_class = $result->fetch_assoc();
}

function getManualCount($subject_id, $shift, $class_index, $staff_index = null) {
    $count = 0;
    if (isset($_SESSION['manual_allocations'][$class_index][$shift])) {
        foreach ($_SESSION['manual_allocations'][$class_index][$shift] as $alloc) {
            if ($alloc['subject_id'] == $subject_id) {
                if ($staff_index !== null) {
                    if (isset($alloc['staff_index']) && $alloc['staff_index'] == $staff_index) $count++;
                } else {
                    $count++;
                }
            }
        }
    }
    return $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Generator - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
    <style>
        .class-counter { font-size: 18px; font-weight: 600; color: #4b5563; margin-bottom: 20px; }
        .class-info { background: #f3f4f6; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .class-info h3 { margin: 0 0 10px 0; color: #1f2937; font-size: 24px; }
        .semester-badge { display: inline-block; background: #3b82f6; color: white; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 10px; }
        .subjects-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .subjects-table th { background: #1f2937; color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .subjects-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .subjects-table tr:last-child td { border-bottom: none; }
        .subjects-table tr:hover { background: #f9fafb; }
        .type-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .type-core { background: #dbeafe; color: #1e40af; }
        .type-lab { background: #fce7f3; color: #9f1239; }
        .type-allied { background: #d1fae5; color: #065f46; }
        .type-common { background: #fef3c7; color: #92400e; }
        .type-nme { background: #e9d5ff; color: #6b21a8; }
        .hours-input { width: 55px; padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; text-align: center; }
        .staff-select { width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; background: white; }
        .no-staff-required { color: #9ca3af; font-style: italic; font-size: 14px; margin-top: 8px; }
        .subjects-table th:nth-child(4), .subjects-table td:nth-child(4) { border-right: 3px solid #4b5563; padding-right: 12px; }
        .subjects-table th:nth-child(5), .subjects-table td:nth-child(5) { padding-left: 12px; }
        .staff-allocation-container { display: flex; flex-direction: column; gap: 8px; }
        .staff-row { display: flex; align-items: center; gap: 8px; }
        .add-staff-btn { background: #10b981; color: white; border: none; padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; white-space: nowrap; }
        .add-staff-btn:hover { background: #059669; }
        .remove-staff-btn { background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .remove-staff-btn:hover { background: #dc2626; }
        .clear-staff-btn { background: #ef4444; color: white; border: none; padding: 6px 8px; border-radius: 3px; cursor: pointer; font-size: 14px; font-weight: bold; margin-left: 5px; min-width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; }
        .clear-staff-btn:hover { background: #dc2626; }
        .total-hours-container { display: inline-block; padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; margin: 0 15px; transition: background-color 0.3s ease; }
        .total-hours-container.status-equal { background-color: #d1fae5; color: #065f46; }
        .total-hours-container.status-under { background-color: #fef3c7; color: #92400e; }
        .total-hours-container.status-over { background-color: #fecaca; color: #991b1b; }
        .action-buttons { display: flex; justify-content: space-between; margin-top: 30px; gap: 15px; }
        .btn-reset { background: #ef4444; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-reset:hover { background: #dc2626; }
        .btn-nav { background: #3b82f6; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-nav:hover { background: #2563eb; }
        .btn-generate { background: #10b981; color: white; padding: 12px 32px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 16px; }
        .btn-generate:hover { background: #059669; }
        .nav-group { display: flex; gap: 10px; }
        .btn-manual { background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; display: inline-block; padding: 4px 8px; line-height: 1.2; text-align: center; }
        .btn-manual:hover { background: #4f46e5; }
    </style>
    <script>
        function setManualAllocation(subjectId, shift, staffIndex = null) {
            document.getElementById('manual_subject_id').value = subjectId;
            document.getElementById('manual_shift').value = shift || '';
            document.getElementById('manual_staff_index').value = staffIndex || '';
            removeValidation();
            return true;
        }

        const staffHoursData = <?php echo json_encode($staff_hours_data); ?>;
        const subjectHoursData = {};
        <?php foreach ($subjects as $subject): ?>
            subjectHoursData[<?php echo $subject['id']; ?>] = <?php echo $subject['hours_per_week']; ?>;
        <?php endforeach; ?>

        const currentAllocations = {};
        const originalPageAllocations = {};

        function getActualAllocationHours(selectName, subjectId, shift) {
            const parts = selectName.split('_');
            let staffIndex = null;
            if (shift) {
                if (parts.length >= 4 && !isNaN(parts[parts.length - 1])) staffIndex = parts[parts.length - 1];
            } else {
                if (parts.length >= 3 && !isNaN(parts[parts.length - 1])) {
                    const potentialIndex = parseInt(parts[parts.length - 1]);
                    if (potentialIndex <= 20) {
                        const checkInput = document.querySelector(`[name="hours_${subjectId}_${potentialIndex}"]`);
                        if (checkInput) staffIndex = potentialIndex;
                    }
                }
            }

            if (staffIndex) {
                const hoursInputName = shift ? `hours_${shift}_${subjectId}_${staffIndex}` : `hours_${subjectId}_${staffIndex}`;
                const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
                if (hoursInput) return parseInt(hoursInput.value) || 0;
                
                if (staffIndex == 1) {
                    const splitHoursName1 = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const splitHoursInput1 = document.querySelector(`[name="${splitHoursName1}"]`);
                    if (splitHoursInput1) return parseInt(splitHoursInput1.value) || 0;
                }
            } else {
                const nextStaffSelectName = shift ? `staff_${shift}_${subjectId}_2` : `staff_${subjectId}_2`;
                const isActuallySplit = document.querySelector(`[name="${nextStaffSelectName}"]`) !== null;

                if (isActuallySplit) {
                    const splitHoursName1 = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const splitHoursInput1 = document.querySelector(`[name="${splitHoursName1}"]`);
                    if (splitHoursInput1) return parseInt(splitHoursInput1.value) || 0;
                }
            }

            const hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
            let hours = 0;
            if (hoursInput) {
                if (hoursInput.type === 'hidden') {
                    const fallbackSplitName = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
                    const fallbackSplitInput = document.querySelector(`[name="${fallbackSplitName}"]`);
                    hours = fallbackSplitInput ? parseInt(fallbackSplitInput.value) || 0 : parseInt(hoursInput.value) || 0;
                } else {
                    hours = parseInt(hoursInput.value) || 0;
                }
            } else {
                hours = subjectHoursData[subjectId] || 0;
            }
            return hours;
        }

        function updateStaffDropdowns() {
            const tempHoursUsed = {};
            const originalHoursUsed = {};

            for (const key in originalPageAllocations) {
                const staffId = originalPageAllocations[key].staffId;
                const subjectId = originalPageAllocations[key].subjectId;
                const shift = originalPageAllocations[key].shift;

                if (!originalHoursUsed[staffId]) originalHoursUsed[staffId] = 0;
                originalHoursUsed[staffId] += getActualAllocationHours(key, subjectId, shift);
            }

            for (const key in currentAllocations) {
                const staffId = currentAllocations[key].staffId;
                const subjectId = currentAllocations[key].subjectId;
                const shift = currentAllocations[key].shift;

                if (!tempHoursUsed[staffId]) tempHoursUsed[staffId] = 0;
                tempHoursUsed[staffId] += getActualAllocationHours(key, subjectId, shift);
            }

            document.querySelectorAll('.staff-select').forEach(select => {
                const currentValue = select.value;
                const subjectId = parseInt(select.getAttribute('data-subject-id'));
                const shift = select.getAttribute('data-shift') || null;
                const requiredHours = getActualAllocationHours(select.name, subjectId, shift);

                Array.from(select.options).forEach(option => {
                    if (option.value === '') return; 

                    const staffId = parseInt(option.value);
                    const baseUsed = staffHoursData[staffId].used || 0;
                    const originalPageHours = originalHoursUsed[staffId] || 0;
                    const currentPageUsed = tempHoursUsed[staffId] || 0;
                    const totalUsed = baseUsed - originalPageHours + currentPageUsed;
                    let remaining = staffHoursData[staffId].max - totalUsed;
                    const displayRemaining = Math.max(0, remaining);

                    if (!option.getAttribute('data-original-text')) {
                        option.setAttribute('data-original-text', option.textContent);
                    }
                    const nameCode = option.getAttribute('data-original-text').split(' : ')[0];
                    const isCurrentSelection = (option.value === currentValue);

                    if (remaining >= requiredHours || isCurrentSelection) {
                        option.textContent = `${nameCode} : ${displayRemaining} hrs remaining`;
                        option.disabled = false;
                        option.style.color = remaining < 5 ? '#dc2626' : '#000';
                    } else {
                        if (displayRemaining > 0) {
                            option.textContent = `${nameCode} : ${displayRemaining} hrs (Need ${requiredHours})`;
                        } else {
                            option.textContent = `${nameCode} : No hours available`;
                        }
                        option.disabled = true;
                        option.style.color = '#9ca3af';
                    }
                });
                select.value = currentValue;
            });
        }

        function validateStaffHours(select, subjectId, shift) {
            const staffId = parseInt(select.value);
            if (!staffId) {
                if (currentAllocations[select.name]) delete currentAllocations[select.name];
                updateStaffDropdowns();
                return true;
            }

            const subjectHours = getActualAllocationHours(select.name, subjectId, shift);
            let tempHoursUsed = {};
            let originalHoursUsed = {};

            for (const key in originalPageAllocations) {
                const allocStaffId = originalPageAllocations[key].staffId;
                const allocSubjectId = originalPageAllocations[key].subjectId;
                const allocShift = originalPageAllocations[key].shift;

                if (!originalHoursUsed[allocStaffId]) originalHoursUsed[allocStaffId] = 0;
                originalHoursUsed[allocStaffId] += getActualAllocationHours(key, allocSubjectId, allocShift);
            }

            for (const key in currentAllocations) {
                if (key === select.name) continue; 
                const allocStaffId = currentAllocations[key].staffId;
                const allocSubjectId = currentAllocations[key].subjectId;
                const allocShift = currentAllocations[key].shift;

                if (!tempHoursUsed[allocStaffId]) tempHoursUsed[allocStaffId] = 0;
                tempHoursUsed[allocStaffId] += getActualAllocationHours(key, allocSubjectId, allocShift);
            }

            const baseUsed = staffHoursData[staffId].used || 0;
            const originalPageHours = originalHoursUsed[staffId] || 0;
            const currentPageUsed = tempHoursUsed[staffId] || 0;
            const totalUsed = baseUsed - originalPageHours + currentPageUsed;
            const remaining = staffHoursData[staffId].max - totalUsed;

            if (remaining < subjectHours) {
                select.value = '';
                if (currentAllocations[select.name]) delete currentAllocations[select.name];
                updateStaffDropdowns();

                select.setCustomValidity(`This staff does not have enough hours for allocation (Needs ${subjectHours} hrs, Has ${Math.max(0, remaining)} hrs remaining)`);
                select.reportValidity();
                select.setCustomValidity('');
                return false;
            }
            return true;
        }

        function trackAllocation(select, subjectId, shift) {
            const staffId = parseInt(select.value);
            if (staffId) {
                currentAllocations[select.name] = { staffId: staffId, subjectId: subjectId, shift: shift || null };
            } else {
                delete currentAllocations[select.name];
            }
            updateStaffDropdowns();
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.staff-select').forEach(select => {
                if (select.value) {
                    const subjectId = parseInt(select.getAttribute('data-subject-id'));
                    const shift = select.getAttribute('data-shift') || null;
                    const allocation = { staffId: parseInt(select.value), subjectId: subjectId, shift: shift };
                    originalPageAllocations[select.name] = allocation;
                    currentAllocations[select.name] = { ...allocation }; 
                }
            });

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

            document.querySelectorAll('.no-staff-cb').forEach(cb => {
                if (cb.checked) toggleStaffRequirement(cb, cb.dataset.subjectId, cb.dataset.hasShifts === 'true');
            });

            updateStaffDropdowns();
            updateTotalHours();
        });

        function toggleStaffRequirement(checkbox, subjectId, hasShifts) {
            const row = checkbox.closest('tr');
            const staffSelects = row.querySelectorAll('.staff-select');
            const addStaffBtns = row.querySelectorAll('.add-staff-btn');
            const removeStaffBtns = row.querySelectorAll('.remove-staff-btn');
            const clearStaffBtns = row.querySelectorAll('.clear-staff-btn');
            const targetCells = hasShifts ? [row.querySelector('td:nth-child(5)'), row.querySelector('td:nth-child(7)')] : [row.querySelector('td:nth-child(4)')];
            
            if (checkbox.checked) {
                staffSelects.forEach(select => {
                    select.value = '';
                    select.disabled = true;
                    select.removeAttribute('required');
                    if (currentAllocations[select.name]) delete currentAllocations[select.name];
                });
                
                addStaffBtns.forEach(btn => btn.style.display = 'none');
                removeStaffBtns.forEach(btn => btn.style.display = 'none');
                clearStaffBtns.forEach(btn => btn.style.display = 'none');
                updateStaffDropdowns();
                
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
                staffSelects.forEach(select => {
                    select.disabled = false;
                    select.setAttribute('required', 'required');
                });
                
                addStaffBtns.forEach(btn => btn.style.display = 'inline-block');
                removeStaffBtns.forEach(btn => btn.style.display = 'inline-block');
                clearStaffBtns.forEach(btn => btn.style.display = 'inline-flex');
                
                targetCells.forEach(cell => {
                    if (cell) {
                        const containers = cell.querySelectorAll('.staff-allocation-container');
                        containers.forEach(c => c.style.display = 'flex');
                        const label = cell.querySelector('.no-staff-label');
                        if (label) label.style.display = 'none';
                    }
                });
            }
        }

        function clearStaffSelection(selectName) {
            const select = document.querySelector(`[name="${selectName}"]`);
            if (select) {
                select.value = '';
                if (currentAllocations[selectName]) delete currentAllocations[selectName];
                updateStaffDropdowns();
                select.setCustomValidity('');
            }
        }

        function checkShiftConflict(select, shift) {
            const subjectId = select.name.split('_')[2];
            const otherShift = shift === 'shift1' ? 'shift2' : 'shift1';
            const otherShiftStaffIds = [];
            
            document.querySelectorAll(`[name^="staff_${otherShift}_${subjectId}"]`).forEach(sel => {
                if (sel.value) otherShiftStaffIds.push(sel.value);
            });

            if (select.value && otherShiftStaffIds.includes(select.value)) {
                select.value = '';
                if (currentAllocations[select.name]) delete currentAllocations[select.name];
                updateStaffDropdowns();
                select.setCustomValidity('This staff is already allocated for another shift');
                select.reportValidity();
                return false;
            } else {
                select.setCustomValidity('');
                return true;
            }
        }

        let staffCounter = {};
        let hoursContainerCache = {};

        function addStaffAllocation(subjectId, shift) {
            const containerId = shift ? `staff-container-${shift}-${subjectId}` : `staff-container-${subjectId}`;
            const container = document.getElementById(containerId);
            if (!container) return;

            if (!staffCounter[containerId]) staffCounter[containerId] = 1;

            // Determine the max number of staff allowed (= subject hours for that shift)
            const hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
            const maxStaff = hoursInput ? parseInt(hoursInput.value) || 1 : 1;

            if (staffCounter[containerId] >= maxStaff) {
                // Already at the limit — show a brief warning and do nothing
                let toast = document.getElementById('add-staff-limit-toast');
                if (!toast) {
                    toast = document.createElement('div');
                    toast.id = 'add-staff-limit-toast';
                    toast.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);background:#dc2626;color:white;padding:12px 28px;border-radius:8px;font-weight:700;font-size:15px;z-index:9999;box-shadow:0 4px 16px rgba(220,38,38,0.4);transition:opacity 0.3s;';
                    document.body.appendChild(toast);
                }
                toast.textContent = `⚠️ Maximum ${maxStaff} staff allowed (matches subject hours).`;
                toast.style.opacity = '1';
                toast.style.display = 'block';
                clearTimeout(toast._timer);
                toast._timer = setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => { toast.style.display = 'none'; }, 300); }, 2500);
                return;
            }

            staffCounter[containerId]++;
            const count = staffCounter[containerId];

            let originalHoursInput = shift ? document.querySelector(`[name="hours_${shift}_${subjectId}"]`) : document.querySelector(`[name="hours_${subjectId}"]`);
            const totalHours = parseInt(originalHoursInput.value);

            if (count === 2) splitHoursInput(subjectId, totalHours, shift);

            const newRow = document.createElement('div');
            newRow.className = 'staff-row';
            newRow.id = `staff-row-${containerId}-${count}`;

            const staffKey = shift ? `staff_${shift}_${subjectId}_${count}` : `staff_${subjectId}_${count}`;
            const shiftAttr = shift ? `data-shift="${shift}"` : '';
            const shiftParam = shift ? `'${shift}'` : 'null';

            newRow.innerHTML = `
                <select name="${staffKey}" class="staff-select" data-subject-id="${subjectId}" ${shiftAttr}
                        onchange="if(validateStaffHours(this, ${subjectId}, ${shiftParam})) { trackAllocation(this, ${subjectId}, ${shiftParam}); ${shift ? `checkShiftConflict(this, '${shift}');` : ''} }" required>
                    <option value="">Select Staff</option>
                    <?php foreach ($staff_list as $staff): ?>
                    <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('${containerId}', ${count}, ${subjectId}, ${shiftParam})">×</button>
            `;
            container.appendChild(newRow);
            updateSplitHoursInputs(subjectId, count, shift);
            updateStaffDropdowns();
        }

        function removeStaffAllocation(containerId, rowNum, subjectId, shift) {
            const row = document.getElementById(`staff-row-${containerId}-${rowNum}`);
            if (row) {
                const select = row.querySelector('.staff-select');
                if (select && currentAllocations[select.name]) delete currentAllocations[select.name];

                row.remove();
                staffCounter[containerId]--;
                const newCount = staffCounter[containerId];

                if (newCount === 1) {
                    restoreSingleHoursInput(subjectId, shift);
                } else {
                    updateSplitHoursInputs(subjectId, newCount, shift);
                }
                updateStaffDropdowns();
            }
        }

        function splitHoursInput(subjectId, totalHours, shift) {
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const cacheKey = shift ? `${subjectId}_${shift}` : subjectId;
            hoursContainerCache[cacheKey] = { html: hoursCell.innerHTML };

            const hours1 = Math.ceil(totalHours / 2);
            const hours2 = Math.floor(totalHours / 2);

            hoursCell.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" name="${hoursInputName}_1" class="hours-input split-hours" data-subject-id="${subjectId}" ${shift ? `data-shift="${shift}"` : ''} value="${hours1}" min="0" max="30" onchange="updateSplitHoursTotal(${subjectId}, ${shift ? `'${shift}'` : 'null'})" required>
                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(${subjectId}, ${shift ? `'${shift}'` : 'null'}, 1)">Manual</button>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" name="${hoursInputName}_2" class="hours-input split-hours" data-subject-id="${subjectId}" ${shift ? `data-shift="${shift}"` : ''} value="${hours2}" min="0" max="30" onchange="updateSplitHoursTotal(${subjectId}, ${shift ? `'${shift}'` : 'null'})" required>
                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(${subjectId}, ${shift ? `'${shift}'` : 'null'}, 2)">Manual</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="${hoursInputName}" value="${totalHours}">
            `;
            updateTotalHours();
        }

        function restoreSingleHoursInput(subjectId, shift) {
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            const currentTotal = hiddenInput ? parseInt(hiddenInput.value) : subjectHoursData[subjectId];

            const cacheKey = shift ? `${subjectId}_${shift}` : subjectId;
            if (hoursContainerCache[cacheKey]) {
                hoursCell.innerHTML = hoursContainerCache[cacheKey].html;
                hoursCell.querySelector(`[name="${hoursInputName}"]`).value = currentTotal;
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('input', updateTotalHours);
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('change', function () {
                    const sid = parseInt(this.getAttribute('data-subject-id'));
                    const hrs = parseInt(this.value);
                    if (sid && hrs >= 1 && hrs <= 30) syncHoursToDB(sid, hrs);
                });
            } else {
                const shiftArg = shift ? `'${shift}'` : `null`;
                const shiftAttr = shift ? `data-shift="${shift}"` : ``;
                hoursCell.innerHTML = `
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <input type="number" name="${hoursInputName}" value="${currentTotal}" min="1" max="30" class="hours-input" data-subject-id="${subjectId}" ${shiftAttr} required>
                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(${subjectId}, ${shiftArg})">Manual</button>
                        </div>
                    </div>
                `;
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('input', updateTotalHours);
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('change', function () {
                    const sid = parseInt(this.getAttribute('data-subject-id'));
                    const hrs = parseInt(this.value);
                    if (sid && hrs >= 1 && hrs <= 30) syncHoursToDB(sid, hrs);
                });
            }
            updateTotalHours();
        }

        function updateSplitHoursInputs(subjectId, count, shift) {
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            const totalHours = parseInt(hiddenInput.value);

            const splitContainer = hoursCell.querySelector('div');
            if (!splitContainer) return;
            splitContainer.innerHTML = '';

            const baseHours = Math.floor(totalHours / count);
            const remainder = totalHours % count;

            for (let i = 1; i <= count; i++) {
                const hours = i === 1 ? baseHours + remainder : baseHours;
                const wrapDiv = document.createElement('div');
                wrapDiv.style.display = 'flex'; wrapDiv.style.flexDirection = 'column'; wrapDiv.style.gap = '2px';
                
                const innerDiv = document.createElement('div');
                innerDiv.style.display = 'flex'; innerDiv.style.alignItems = 'center'; innerDiv.style.gap = '5px';

                const input = document.createElement('input');
                input.type = 'number'; input.name = `${hoursInputName}_${i}`; input.className = 'hours-input split-hours';
                input.setAttribute('data-subject-id', subjectId);
                if (shift) input.setAttribute('data-shift', shift);
                input.value = hours; input.min = '0'; input.max = '30'; input.required = true;
                input.onchange = function () { updateSplitHoursTotal(subjectId, shift); };

                const btn = document.createElement('button');
                btn.type = 'submit'; btn.name = 'action'; btn.value = 'manual'; btn.className = 'btn-manual';
                btn.innerHTML = 'Manual';
                const btnShift = shift ? `'${shift}'` : 'null';
                btn.setAttribute('onclick', `return setManualAllocation(${subjectId}, ${btnShift}, ${i})`);

                innerDiv.appendChild(input); innerDiv.appendChild(btn);
                wrapDiv.appendChild(innerDiv); splitContainer.appendChild(wrapDiv);
            }
        }

        function updateSplitHoursTotal(subjectId, shift) {
            let hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursCell = document.querySelector(`[name="${hoursInputName}"]`).closest('td');
            const splitInputs = hoursCell.querySelectorAll('.split-hours');

            let total = 0;
            splitInputs.forEach(input => total += parseInt(input.value) || 0);

            const hiddenInput = hoursCell.querySelector(`[name="${hoursInputName}"]`);
            if (hiddenInput) hiddenInput.value = total;

            updateTotalHours();
            if (total >= 1 && total <= 30) syncHoursToDB(subjectId, total);
        }

        function syncHoursToDB(subjectId, hours) {
            const formData = new FormData();
            formData.append('subject_id', subjectId);
            formData.append('hours', hours);

            fetch('update_hours.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.querySelectorAll(`.hours-input[data-subject-id="${subjectId}"]`).forEach(inp => {
                            inp.style.borderColor = '#10b981'; inp.style.backgroundColor = '#d1fae5';
                            setTimeout(() => { inp.style.borderColor = ''; inp.style.backgroundColor = ''; }, 1200);
                        });
                    }
                }).catch(err => console.error('Hours sync error:', err));
        }

        function updateTotalHours() {
            const hasShifts = <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>;
            const maxHours = 30; 

            if (hasShifts) {
                let shift1Total = 0, shift2Total = 0;
                document.querySelectorAll('.hours-input[data-shift="shift1"]').forEach(input => shift1Total += parseInt(input.value) || 0);
                document.querySelectorAll('.hours-input[data-shift="shift2"]').forEach(input => shift2Total += parseInt(input.value) || 0);

                const shift1Container = document.getElementById('total-shift1-container');
                const shift1Display = document.getElementById('total-hours-shift1');
                if (shift1Display) shift1Display.textContent = `Shift 1: ${shift1Total} / ${maxHours} Hrs`;
                if (shift1Container) {
                    shift1Container.classList.remove('status-equal', 'status-under', 'status-over');
                    if (shift1Total === maxHours) shift1Container.classList.add('status-equal'); 
                    else if (shift1Total < maxHours) shift1Container.classList.add('status-under'); 
                    else shift1Container.classList.add('status-over');
                }

                const shift2Container = document.getElementById('total-shift2-container');
                const shift2Display = document.getElementById('total-hours-shift2');
                if (shift2Display) shift2Display.textContent = `Shift 2: ${shift2Total} / ${maxHours} Hrs`;
                if (shift2Container) {
                    shift2Container.classList.remove('status-equal', 'status-under', 'status-over');
                    if (shift2Total === maxHours) shift2Container.classList.add('status-equal'); 
                    else if (shift2Total < maxHours) shift2Container.classList.add('status-under'); 
                    else shift2Container.classList.add('status-over');
                }
            } else {
                let totalAllocated = 0;
                document.querySelectorAll('.hours-input').forEach(input => totalAllocated += parseInt(input.value) || 0);

                const totalContainer = document.querySelector('.total-hours-container');
                const totalDisplay = document.getElementById('total-hours-display');
                if (totalDisplay) totalDisplay.textContent = `Total: ${totalAllocated} / ${maxHours} Hrs`;
                if (totalContainer) {
                    totalContainer.classList.remove('status-equal', 'status-under', 'status-over');
                    if (totalAllocated === maxHours) totalContainer.classList.add('status-equal'); 
                    else if (totalAllocated < maxHours) totalContainer.classList.add('status-under'); 
                    else totalContainer.classList.add('status-over');
                }
            }
        }

        function validateTotalHours() {
            const hasShifts = <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>;
            const maxHours = 30; 
            if (hasShifts) {
                let shift1Total = 0, shift2Total = 0;
                document.querySelectorAll('.hours-input[data-shift="shift1"]').forEach(input => shift1Total += parseInt(input.value) || 0);
                document.querySelectorAll('.hours-input[data-shift="shift2"]').forEach(input => shift2Total += parseInt(input.value) || 0);
                if (shift1Total !== maxHours || shift2Total !== maxHours) {
                    alert('Total hours for both shifts must be exactly ' + maxHours + ' to proceed.');
                    return false;
                }
            } else {
                let totalAllocated = 0;
                document.querySelectorAll('.hours-input').forEach(input => totalAllocated += parseInt(input.value) || 0);
                if (totalAllocated !== maxHours) {
                    alert('Total hours must be exactly ' + maxHours + ' to proceed.');
                    return false;
                }
            }
            return true;
        }

        function validateForNext() {
            if (!validateTotalHours()) return false;
            populateNoStaffSubjects();
            return removeValidation();
        }

        function validateForGenerate() {
            if (!validateTotalHours()) return false;
            populateNoStaffSubjects();
            return removeValidation();
        }

        function populateNoStaffSubjects() {
            const noStaffCheckboxes = document.querySelectorAll('.no-staff-cb:checked');
            const subjectIds = Array.from(noStaffCheckboxes).map(cb => cb.dataset.subjectId);
            const hiddenInput = document.getElementById('no_staff_subjects');
            if (hiddenInput) hiddenInput.value = JSON.stringify(subjectIds);
        }

        function removeValidation() {
            document.querySelectorAll('[required]').forEach(el => el.removeAttribute('required'));
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
        <a href="../Staff/staff.php" class="tab"><span class="tab-icon">👥</span> Staff</a>
        <a href="../Class/class.php" class="tab"><span class="tab-icon">🎓</span> Classes</a>
        <a href="../Subject/subject.php" class="tab"><span class="tab-icon">📚</span> Subjects</a>
        <a href="redirect_timetable.php" class="tab active"><span class="tab-icon">📅</span> Class Timetable</a>
        <a href="generated_timetable_view.php" class="tab"><span class="tab-icon">📊</span> Generated Timetable</a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab"><span class="tab-icon">💾</span> Saved Timetables</a>
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
                <a href="timetable.php?semester=odd" class="btn <?php echo $semester_filter == 'odd' ? 'btn-primary' : 'btn-secondary'; ?>">Odd Semester</a>
                <a href="timetable.php?semester=even" class="btn <?php echo $semester_filter == 'even' ? 'btn-primary' : 'btn-secondary'; ?>">Even Semester</a>
            </div>
        </div>

        <div class="class-counter">
            Class <?php echo ($current_index + 1); ?> of <?php echo $total_classes; ?>
        </div>

        <div class="class-info">
            <h3>
                <?php echo htmlspecialchars($current_class_config['label']); ?>
                <span class="semester-badge">SEMESTER <?php echo $current_semester; ?></span>
            </h3>
            <?php if ($current_class_config['has_shifts']): ?>
                <p style="margin: 5px 0 0 0; color: #6b7280;">Allocate staff for both Shift 1 and Shift 2</p>
            <?php endif; ?>
        </div>

        <form method="POST" action="timetable.php">
            <div class="action-buttons" style="margin-bottom: 20px;">
                <button type="submit" name="action" value="reset" class="btn-reset" onclick="removeValidation()">🔄 Reset</button>

                <?php if ($current_class_config['has_shifts']): ?>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div class="total-hours-container status-under" id="total-shift1-container">
                            <span id="total-hours-shift1">Shift 1: 0 / 30 Hrs</span>
                        </div>
                        <div class="total-hours-container status-under" id="total-shift2-container">
                            <span id="total-hours-shift2">Shift 2: 0 / 30 Hrs</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="total-hours-container status-under">
                        <span id="total-hours-display">Total: 0 / 30 Hrs</span>
                    </div>
                <?php endif; ?>

                <div class="nav-group">
                    <input type="hidden" name="manual_subject_id" id="manual_subject_id" value="">
                    <input type="hidden" name="manual_shift" id="manual_shift" value="">
                    <input type="hidden" name="manual_staff_index" id="manual_staff_index" value="">
                    <input type="hidden" name="page_subject_ids" value="<?php echo implode(',', array_column($subjects, 'id')); ?>">
                    <input type="hidden" name="page_has_shifts" value="<?php echo $current_class_config['has_shifts'] ? '1' : ''; ?>">
                    <input type="hidden" name="no_staff_subjects" id="no_staff_subjects" value="[]">

                    <?php if ($current_index > 0): ?>
                        <button type="submit" name="action" value="previous" class="btn-nav" onclick="removeValidation()">← Previous Class</button>
                    <?php endif; ?>

                    <?php if ($current_index < $total_classes - 1): ?>
                        <button type="submit" name="action" value="next" class="btn-nav" onclick="return validateForNext()">Next Class →</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="generate" class="btn-generate" onclick="return validateForGenerate()">▶ Generate Timetable</button>
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
                    <?php foreach ($subjects as $subject): 
                        // Session is always pre-seeded with type-based defaults above.
                        // If user explicitly submits, session reflects their choice.
                        $is_no_staff = false;
                        if (isset($_SESSION['no_staff_subjects'][$current_index]) && is_array($_SESSION['no_staff_subjects'][$current_index])) {
                            $is_no_staff = in_array($subject['id'], $_SESSION['no_staff_subjects'][$current_index]) ||
                                           in_array(strval($subject['id']), $_SESSION['no_staff_subjects'][$current_index]);
                        }
                    ?>

                    <tr>
                        <td>
                            <div style="margin-bottom: 6px; font-weight: 500; color: #374151;"><?php echo htmlspecialchars($subject['title']); ?></div>
                            <label style="font-size: 11px; display: flex; align-items: center; gap: 4px; color: #4b5563; cursor: pointer;">
                                <input type="checkbox" class="no-staff-cb" data-subject-id="<?php echo $subject['id']; ?>" data-has-shifts="<?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>" onchange="toggleStaffRequirement(this, <?php echo $subject['id']; ?>, <?php echo $current_class_config['has_shifts'] ? 'true' : 'false'; ?>)" <?php echo $is_no_staff ? 'checked' : ''; ?>>
                                No Staff Required
                            </label>
                        </td>
                        <td>
                            <span class="type-badge type-<?php echo strtolower($subject['type']); ?>">
                                <?php echo htmlspecialchars($subject['type']); ?>
                            </span>
                        </td>
                        
                        <?php 
                        $hours_key = 'hours_' . $subject['id'];
                        $current_hours = isset($_SESSION['hours_changes'][$hours_key]) ? $_SESSION['hours_changes'][$hours_key] : $subject['hours_per_week'];
                        ?>
                        
                        <?php if ($current_class_config['has_shifts']): ?>
                            <td>
                                <?php
                                $hours_key_shift1 = 'hours_shift1_' . $subject['id'];
                                $current_hours_shift1 = isset($_SESSION['hours_changes'][$hours_key_shift1]) ? $_SESSION['hours_changes'][$hours_key_shift1] : $subject['hours_per_week'];
                                $manual_count_1 = getManualCount($subject['id'], 'shift1', $current_index);
                                $rem_auto_1 = max(0, $current_hours_shift1 - $manual_count_1);

                                // Detect saved split hours for shift1
                                $split_hours_s1 = [];
                                $si1 = 1;
                                while (isset($_SESSION['hours_changes']['hours_shift1_' . $subject['id'] . '_' . $si1])) {
                                    $split_hours_s1[] = $_SESSION['hours_changes']['hours_shift1_' . $subject['id'] . '_' . $si1];
                                    $si1++;
                                }
                                ?>
                                <?php if (!empty($split_hours_s1)): ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <?php foreach ($split_hours_s1 as $si1_idx => $si1_val): ?>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="hours_shift1_<?php echo $subject['id']; ?>_<?php echo ($si1_idx + 1); ?>" class="hours-input split-hours" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift1" value="<?php echo $si1_val; ?>" min="0" max="30" onchange="updateSplitHoursTotal(<?php echo $subject['id']; ?>, 'shift1')" required>
                                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift1', <?php echo ($si1_idx + 1); ?>)"><?php echo getManualCount($subject['id'], 'shift1', $current_index, $si1_idx + 1) > 0 ? 'Manual<br>(' . getManualCount($subject['id'], 'shift1', $current_index, $si1_idx + 1) . ')' : 'Manual'; ?></button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $hours_key_shift1; ?>" value="<?php echo array_sum($split_hours_s1); ?>">
                                <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="number" name="<?php echo $hours_key_shift1; ?>" value="<?php echo $current_hours_shift1; ?>" min="0" max="30" class="hours-input" data-shift="shift1" data-subject-id="<?php echo $subject['id']; ?>" required>
                                        <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift1')">
                                            Manual<?php echo $manual_count_1 > 0 ? "<br>($manual_count_1)" : ""; ?>
                                        </button>
                                    </div>
                                    <div style="font-size: 10px; color: #6b7280;">Remaining for Auto: <strong><?php echo $rem_auto_1; ?></strong></div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="staff-allocation-container" id="staff-container-shift1-<?php echo $subject['id']; ?>">
                                    <div class="staff-row">
                                        <?php
                                        $staff_key_shift1 = 'staff_shift1_' . $subject['id'];
                                        $selected_staff_shift1 = isset($_SESSION['staff_allocations'][$staff_key_shift1]) ? $_SESSION['staff_allocations'][$staff_key_shift1] : '';
                                        ?>
                                        <select name="<?php echo $staff_key_shift1; ?>" class="staff-select" data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift1" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift1')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift1'); checkShiftConflict(this, 'shift1'); }" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff_shift1 == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key_shift1; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift1')">+ Add Staff</button>
                                    </div>
                                    <?php
                                    $split_s1_count = 2;
                                    while (isset($_SESSION['staff_allocations']['staff_shift1_' . $subject['id'] . '_' . $split_s1_count])) {
                                        $split_s1_key = 'staff_shift1_' . $subject['id'] . '_' . $split_s1_count;
                                        $split_s1_selected = $_SESSION['staff_allocations'][$split_s1_key];
                                    ?>
                                    <div class="staff-row" id="staff-row-staff-container-shift1-<?php echo $subject['id']; ?>-<?php echo $split_s1_count; ?>">
                                        <select name="<?php echo $split_s1_key; ?>" class="staff-select" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift1" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift1')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift1'); checkShiftConflict(this, 'shift1'); }" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $split_s1_selected == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('staff-container-shift1-<?php echo $subject['id']; ?>', <?php echo $split_s1_count; ?>, <?php echo $subject['id']; ?>, 'shift1')">×</button>
                                    </div>
                                    <?php $split_s1_count++; } ?>
                                    <?php if ($split_s1_count > 2): ?>
                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        if (typeof staffCounter === 'undefined') staffCounter = {};
                                        staffCounter['staff-container-shift1-<?php echo $subject['id']; ?>'] = <?php echo ($split_s1_count - 1); ?>;
                                    });
                                    </script>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <?php
                                $hours_key_shift2 = 'hours_shift2_' . $subject['id'];
                                $current_hours_shift2 = isset($_SESSION['hours_changes'][$hours_key_shift2]) ? $_SESSION['hours_changes'][$hours_key_shift2] : $subject['hours_per_week'];
                                $manual_count_2 = getManualCount($subject['id'], 'shift2', $current_index);
                                $rem_auto_2 = max(0, $current_hours_shift2 - $manual_count_2);

                                // Detect saved split hours for shift2
                                $split_hours_s2 = [];
                                $si2 = 1;
                                while (isset($_SESSION['hours_changes']['hours_shift2_' . $subject['id'] . '_' . $si2])) {
                                    $split_hours_s2[] = $_SESSION['hours_changes']['hours_shift2_' . $subject['id'] . '_' . $si2];
                                    $si2++;
                                }
                                ?>
                                <?php if (!empty($split_hours_s2)): ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <?php foreach ($split_hours_s2 as $si2_idx => $si2_val): ?>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="hours_shift2_<?php echo $subject['id']; ?>_<?php echo ($si2_idx + 1); ?>" class="hours-input split-hours" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift2" value="<?php echo $si2_val; ?>" min="0" max="30" onchange="updateSplitHoursTotal(<?php echo $subject['id']; ?>, 'shift2')" required>
                                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift2', <?php echo ($si2_idx + 1); ?>)"><?php echo getManualCount($subject['id'], 'shift2', $current_index, $si2_idx + 1) > 0 ? 'Manual<br>(' . getManualCount($subject['id'], 'shift2', $current_index, $si2_idx + 1) . ')' : 'Manual'; ?></button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $hours_key_shift2; ?>" value="<?php echo array_sum($split_hours_s2); ?>">
                                <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <input type="number" name="<?php echo $hours_key_shift2; ?>" value="<?php echo $current_hours_shift2; ?>" min="0" max="30" class="hours-input" data-shift="shift2" data-subject-id="<?php echo $subject['id']; ?>" required>
                                        <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, 'shift2')">
                                            Manual<?php echo $manual_count_2 > 0 ? "<br>($manual_count_2)" : ""; ?>
                                        </button>
                                    </div>
                                    <div style="font-size: 10px; color: #6b7280;">Remaining for Auto: <strong><?php echo $rem_auto_2; ?></strong></div>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="staff-allocation-container" id="staff-container-shift2-<?php echo $subject['id']; ?>">
                                    <div class="staff-row">
                                        <?php
                                        $staff_key_shift2 = 'staff_shift2_' . $subject['id'];
                                        $selected_staff_shift2 = isset($_SESSION['staff_allocations'][$staff_key_shift2]) ? $_SESSION['staff_allocations'][$staff_key_shift2] : '';
                                        ?>
                                        <select name="<?php echo $staff_key_shift2; ?>" class="staff-select" data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift2" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift2')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift2'); checkShiftConflict(this, 'shift2'); }" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff_shift2 == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key_shift2; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift2')">+ Add Staff</button>
                                    </div>
                                    <?php
                                    $split_s2_count = 2;
                                    while (isset($_SESSION['staff_allocations']['staff_shift2_' . $subject['id'] . '_' . $split_s2_count])) {
                                        $split_s2_key = 'staff_shift2_' . $subject['id'] . '_' . $split_s2_count;
                                        $split_s2_selected = $_SESSION['staff_allocations'][$split_s2_key];
                                    ?>
                                    <div class="staff-row" id="staff-row-staff-container-shift2-<?php echo $subject['id']; ?>-<?php echo $split_s2_count; ?>">
                                        <select name="<?php echo $split_s2_key; ?>" class="staff-select" data-subject-id="<?php echo $subject['id']; ?>" data-shift="shift2" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift2')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift2'); checkShiftConflict(this, 'shift2'); }" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $split_s2_selected == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('staff-container-shift2-<?php echo $subject['id']; ?>', <?php echo $split_s2_count; ?>, <?php echo $subject['id']; ?>, 'shift2')">×</button>
                                    </div>
                                    <?php $split_s2_count++; } ?>
                                    <?php if ($split_s2_count > 2): ?>
                                    <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        if (typeof staffCounter === 'undefined') staffCounter = {};
                                        staffCounter['staff-container-shift2-<?php echo $subject['id']; ?>'] = <?php echo ($split_s2_count - 1); ?>;
                                    });
                                    </script>
                                    <?php endif; ?>
                                </div>
                            </td>

                        <?php else: ?>
                            <td>
                                <?php
                                $has_split_hours_msc = false;
                                $split_hours_values_msc = [];
                                $split_count_msc = 1;
                                
                                while (isset($_SESSION['hours_changes']['hours_' . $subject['id'] . '_' . $split_count_msc])) {
                                    $has_split_hours_msc = true;
                                    $split_hours_values_msc[] = $_SESSION['hours_changes']['hours_' . $subject['id'] . '_' . $split_count_msc];
                                    $split_count_msc++;
                                }
                                
                                if ($has_split_hours_msc):
                                    ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <?php foreach ($split_hours_values_msc as $idx_msc => $hours_val_msc): ?>
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="hours_<?php echo $subject['id']; ?>_<?php echo ($idx_msc + 1); ?>" class="hours-input split-hours" data-subject-id="<?php echo $subject['id']; ?>" value="<?php echo $hours_val_msc; ?>" min="0" max="30" onchange="updateSplitHoursTotal(<?php echo $subject['id']; ?>, null)" required>
                                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, null, <?php echo $idx_msc + 1; ?>)">Manual</button>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $hours_key; ?>" value="<?php echo array_sum($split_hours_values_msc); ?>">
                                <?php else: 
                                    $manual_count = getManualCount($subject['id'], '', $current_index);
                                    $rem_auto = max(0, $current_hours - $manual_count);
                                ?>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <div style="display: flex; align-items: center; gap: 5px;">
                                            <input type="number" name="<?php echo $hours_key; ?>" value="<?php echo $current_hours; ?>" min="1" max="30" class="hours-input" data-subject-id="<?php echo $subject['id']; ?>" required>
                                            <button type="submit" name="action" value="manual" class="btn-manual" onclick="return setManualAllocation(<?php echo $subject['id']; ?>, '')">
                                                Manual<?php echo $manual_count > 0 ? "<br>($manual_count)" : ""; ?>
                                            </button>
                                        </div>
                                        <div style="font-size: 10px; color: #6b7280;">Remaining for Auto: <strong><?php echo $rem_auto; ?></strong></div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="staff-allocation-container" id="staff-container-<?php echo $subject['id']; ?>">
                                    <div class="staff-row">
                                        <?php 
                                        $staff_key = 'staff_' . $subject['id'] . '_' . $shift1_class['id'];
                                        $selected_staff = isset($_SESSION['staff_allocations'][$staff_key]) ? $_SESSION['staff_allocations'][$staff_key] : '';
                                        ?>
                                        <select name="<?php echo $staff_key; ?>" class="staff-select" data-subject-id="<?php echo $subject['id']; ?>" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }" required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" <?php echo $selected_staff == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, null)">+ Add Staff</button>
                                    </div>
                                    <?php 
                                    $split_staff_count = 2;
                                    while (isset($_SESSION['staff_allocations']['staff_' . $subject['id'] . '_' . $split_staff_count])) {
                                        $split_staff_key = 'staff_' . $subject['id'] . '_' . $split_staff_count;
                                        $split_selected_staff = $_SESSION['staff_allocations'][$split_staff_key];
                                        ?>
                                        <div class="staff-row" id="staff-row-staff-container-<?php echo $subject['id']; ?>-<?php echo $split_staff_count; ?>">
                                            <select name="<?php echo $split_staff_key; ?>" class="staff-select" data-subject-id="<?php echo $subject['id']; ?>" onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }" required>
                                                <option value="">Select Staff</option>
                                                <?php foreach ($staff_list as $staff): ?>
                                                <option value="<?php echo $staff['id']; ?>" <?php echo $split_selected_staff == $staff['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('staff-container-<?php echo $subject['id']; ?>', <?php echo $split_staff_count; ?>, <?php echo $subject['id']; ?>, null)">×</button>
                                        </div>
                                        <?php
                                        $split_staff_count++;
                                    }
                                    if ($split_staff_count > 2) {
                                        ?>
                                        <script>
                                        document.addEventListener('DOMContentLoaded', function() {
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
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
