<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Get semester filter
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : (isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd');

// Get staff allocations from session
$staff_allocations = isset($_SESSION['staff_allocations']) ? $_SESSION['staff_allocations'] : [];

// Handle regenerating (cycling through already generated fallbacks)
if (isset($_GET['cycle']) && $_GET['cycle'] == 1 && isset($_SESSION['timetable_collection']) && !empty($_SESSION['timetable_collection'])) {
    $collection = $_SESSION['timetable_collection'];
    $current_index = isset($_SESSION['timetable_index']) ? $_SESSION['timetable_index'] : 0;
    
    // Move to next timetable in the collection (wrap around)
    $next_index = ($current_index + 1) % count($collection);
    
    $_SESSION['timetable_index'] = $next_index;
    $_SESSION['current_timetables'] = $collection[$next_index]['timetables'];
    $_SESSION['generation_warning'] = $collection[$next_index]['warning'];
    $_SESSION['generation_success'] = isset($collection[$next_index]['is_success']) ? $collection[$next_index]['is_success'] : false;
    unset($_SESSION['generation_error']);
    
    header("Location: generated_timetable_view.php");
    exit;
}

// Get all classes ordered properly
$classes_query = "SELECT * FROM classes ORDER BY 
    CASE 
        WHEN name LIKE 'I B.Sc%' AND shift LIKE 'Shift 1%' THEN 1
        WHEN name LIKE 'I B.Sc%' AND shift LIKE 'Shift 2%' THEN 2
        WHEN name LIKE 'II B.Sc%' AND shift LIKE 'Shift 1%' THEN 3
        WHEN name LIKE 'II B.Sc%' AND shift LIKE 'Shift 2%' THEN 4
        WHEN name LIKE 'III B.Sc%' AND shift LIKE 'Shift 1%' THEN 5
        WHEN name LIKE 'III B.Sc%' AND shift LIKE 'Shift 2%' THEN 6
        WHEN name LIKE 'I M.Sc%' THEN 7
        WHEN name LIKE 'II M.Sc%' THEN 8
        ELSE 99
    END";
$classes = $conn->query($classes_query);

$timetables = [];
$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

// Global tracking arrays
$staff_schedule = [];
$lab_schedule = [];

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
    if ($type === 'Project')
        return 'PROJ';

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
        'project viva voce' => 'PROJ',
        'project & viva voce' => 'PROJ',
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

function isStaffAvailable($staff_id, $day, $hour)
{
    global $staff_schedule;
    $key = $day . '_' . $hour;
    return !isset($staff_schedule[$key][$staff_id]);
}

function markStaffOccupied($staff_id, $day, $hour)
{
    global $staff_schedule;
    $key = $day . '_' . $hour;
    if (!isset($staff_schedule[$key])) {
        $staff_schedule[$key] = [];
    }
    $staff_schedule[$key][$staff_id] = true;
}

function getStaffHoursOnDay(array $timetable, $staff_id, string $day): int
{
    $count = 0;
    foreach ($timetable[$day] as $hour => $slot) {
        if ($slot && isset($slot['staff_id']) && $slot['staff_id'] == $staff_id) {
            $count++;
        }
    }
    return $count;
}

function getGlobalStaffHoursOnDay($staff_id, $day): int
{
    global $staff_schedule, $hours;
    $count = 0;
    foreach ($hours as $hour) {
        $key = $day . '_' . $hour;
        if (isset($staff_schedule[$key][$staff_id])) {
            $count++;
        }
    }
    return $count;
}

function staffHasLabOnDay(array $timetable, $staff_id, string $day): bool
{
    foreach ($timetable[$day] as $hour => $slot) {
        if ($slot && isset($slot['staff_id']) && $slot['staff_id'] == $staff_id
        && isset($slot['type']) && $slot['type'] === 'Lab') {
            return true;
        }
    }
    return false;
}

function isLabSlotAvailable($day, $hour, $group)
{
    global $lab_schedule;
    $key = $day . '_' . $hour;
    return !isset($lab_schedule[$group][$key]);
}

function markLabSlotOccupied($day, $hour, $group)
{
    global $lab_schedule;
    $key = $day . '_' . $hour;
    if (!isset($lab_schedule[$group])) {
        $lab_schedule[$group] = [];
    }
    $lab_schedule[$group][$key] = true;
}

function isNMEReserved($semester, $day, $hour)
{
    if ($semester >= 5 && $semester <= 6) {
        if ($hour === 'IV HOUR' && in_array($day, ['I DAY', 'II DAY', 'III DAY'])) {
            return true;
        }
    }
    return false;
}

function countConsecutiveOnDay($timetable, $day, $subject_id)
{
    global $hours;
    $max_consecutive = 0;
    $current_consecutive = 0;

    foreach ($hours as $hour) {
        if (isset($timetable[$day][$hour]) && $timetable[$day][$hour] && $timetable[$day][$hour]['id'] === $subject_id) {
            $current_consecutive++;
            $max_consecutive = max($max_consecutive, $current_consecutive);
        }
        else {
            $current_consecutive = 0;
        }
    }

    return $max_consecutive;
}

function wouldViolateConsecutive($timetable, $day, $hour, $subject_id)
{
    global $hours;

    $temp_timetable = $timetable;
    $temp_timetable[$day][$hour] = ['id' => $subject_id];

    $consecutive = countConsecutiveOnDay($temp_timetable, $day, $subject_id);
    return $consecutive > 1;
}

function hasGaps($timetable, $days, $hours, $semester)
{
    foreach ($days as $day) {
        foreach ($hours as $hour) {
            if (isNMEReserved($semester, $day, $hour)) {
                continue;
            }

            if (!isset($timetable[$day][$hour]) || $timetable[$day][$hour] === null) {
                return true;
            }
        }
    }
    return false;
}

function generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, $max_attempts = 300)
{
    global $staff_schedule, $lab_schedule;

    $start_time = time();
    $max_time_per_class = 8; 

    $class_index = 0;
    if (isset($_SESSION['current_class_index_map'][$class['id']])) {
        $class_index = $_SESSION['current_class_index_map'][$class['id']];
    }
    else {
        $class_sequence = [
            ['pattern' => 'I B.Sc%'],
            ['pattern' => 'II B.Sc%'],
            ['pattern' => 'III B.Sc%'],
            ['pattern' => 'I M.Sc%'],
            ['pattern' => 'II M.Sc%'],
        ];
        foreach ($class_sequence as $idx => $seq) {
            $pattern = str_replace('%', '', $seq['pattern']);
            if (strpos($class['name'], $pattern) === 0) {
                $class_index = $idx;
                break;
            }
        }
    }

    $initial_staff_schedule = $staff_schedule;
    $initial_lab_schedule   = $lab_schedule;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        $staff_schedule = $initial_staff_schedule;
        $lab_schedule   = $initial_lab_schedule;

        $result = generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group, $class_index);

        $gap_count = 0;
        foreach ($days as $day) {
            foreach ($hours as $hour) {
                if (isNMEReserved($semester, $day, $hour))
                    continue;
                if (!isset($result['timetable'][$day][$hour]) || $result['timetable'][$day][$hour] === null) {
                    $gap_count++;
                }
            }
        }

        if ($gap_count === 0) {
            return $result['timetable'];
        }

        if (time() - $start_time >= $max_time_per_class) {
            break;
        }
    }

    $staff_schedule = $initial_staff_schedule;
    $lab_schedule   = $initial_lab_schedule;
    return null;
}

function generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group, $class_index)
{
    global $staff_schedule, $lab_schedule;

    $timetable = [];
    foreach ($days as $day) {
        $timetable[$day] = [];
        foreach ($hours as $hour) {
            $timetable[$day][$hour] = null;
        }
    }

    $used_slots = [];
    $days_with_labs = [];
    $remaining_hours = [];

    $program = 'UG';
    foreach ($subjects as $s) {
        if (strpos(strtolower($s['title']), 'advance') !== false || strpos(strtolower($s['title']), 'dbms') !== false || strpos(strtolower($s['title']), 'database') !== false || strpos(strtolower($s['title']), 'dip') !== false || strpos(strtolower($s['title']), 'image') !== false || strpos(strtolower($s['title']), 'mobile') !== false || strpos(strtolower($s['title']), 'python programming') !== false) {
            if ($semester === 1 || $semester === 2 || $semester === 3 || $semester === 4 && strpos(strtolower($s['title']), 'm.sc') !== false) {
                $program = 'PG';
                break;
            }
        }
    }
    if ($class_index === 3 || $class_index === 4) {
        $program = 'PG';
    }

    foreach ($subjects as $subject) {
        $remaining_hours[$subject['id']] = $subject['hours_per_week'];
    }

    $labs_4hr = [];
    $labs_3hr = [];
    $labs_2hr = [];
    $nme_subjects = [];
    $fixed_subjects = []; 
    $regular_subjects = [];

    foreach ($subjects as $subject) {
        if ($subject['type'] === 'Lab') {
            $hrs = $subject['hours_per_week'];
            if ($hrs == 4)
                $labs_4hr[] = $subject;
            elseif ($hrs == 3)
                $labs_3hr[] = $subject;
            elseif ($hrs == 2)
                $labs_2hr[] = $subject;
        }
        elseif ($subject['type'] === 'NME' || ($semester >= 5 && $semester <= 6 && stripos($subject['title'], 'elective') !== false)) {
            $nme_subjects[] = $subject;
        }
        elseif ($subject['type'] === 'Common' || $subject['type'] === 'Allied') {
            $fixed_subjects[] = $subject;
        }
        else {
            $regular_subjects[] = $subject;
        }
    }

    shuffle($labs_4hr);
    shuffle($labs_3hr);
    shuffle($labs_2hr);
    shuffle($regular_subjects);

    $lab_days_order = $days; 
    $shuffled_days = $days;
    shuffle($shuffled_days); 

    // ============================================================
    // STEP 0.5: PRE-FILL MANUAL ALLOCATIONS
    // ============================================================
    if (isset($_SESSION['manual_allocations'][$class_index][$shift_group])) {
        // Track which split-subject index to use next for each base subject_id
        $split_dist_counter = []; // base_id => next_staff_index_to_use (1-based)

        // Pre-build a map of split subjects per base_id for quick lookup
        $split_subjects_map = []; // base_id => [ staff_index => subject ]
        foreach ($subjects as $s) {
            $bid = $s['base_id'] ?? $s['id'];
            $sidx = $s['staff_index'] ?? 1;
            $split_subjects_map[$bid][$sidx] = $s;
        }

        foreach ($_SESSION['manual_allocations'][$class_index][$shift_group] as $slot => $alloc) {
            $day = $alloc['day'];
            $hour = $alloc['hour'];
            $subject_id = $alloc['subject_id'];

            $manual_subject = null;
            $manual_staff_index = isset($alloc['staff_index']) ? $alloc['staff_index'] : null;

            if ($manual_staff_index !== null) {
                // Explicit staff_index — find exact split subject
                foreach ($subjects as $s) {
                    if ($s['id'] === $subject_id . '_' . $manual_staff_index) {
                        $manual_subject = $s;
                        break;
                    }
                }
            } else {
                // No explicit staff_index — check if multiple split subjects exist
                $available_splits = $split_subjects_map[$subject_id] ?? [];
                if (count($available_splits) > 1) {
                    // Sort by staff index
                    ksort($available_splits);
                    $split_keys = array_keys($available_splits);

                    // Pick the next split subject in sequence (round-robin)
                    if (!isset($split_dist_counter[$subject_id])) {
                        $split_dist_counter[$subject_id] = 0;
                    }
                    $pick_key = $split_keys[$split_dist_counter[$subject_id] % count($split_keys)];
                    $manual_subject = $available_splits[$pick_key];
                    $split_dist_counter[$subject_id]++;
                } else {
                    // Single subject — original behaviour
                    foreach ($subjects as $s) {
                        if ($s['base_id'] == $subject_id) {
                            $manual_subject = $s;
                            break;
                        }
                    }
                }
            }

            if ($manual_subject !== null) {
                $key = $day . '_' . $hour;
                $timetable[$day][$hour] = $manual_subject;
                $used_slots[$key] = true;

                if (isset($manual_subject['staff_id'])) {
                    markStaffOccupied($manual_subject['staff_id'], $day, $hour);
                }
                if ($manual_subject['type'] === 'Lab') {
                    markLabSlotOccupied($day, $hour, $shift_group);
                    $days_with_labs[$day] = $manual_subject['id'];
                }
                $remaining_hours[$manual_subject['id']]--;
            }
        }
    }


    // ============================================================
    // STEP 0.5b: STRICT ENGLISH PLACEMENT
    // ============================================================
    $english_subject_placed = false;
    if ($semester === 1 || $semester === 3) { 
        $english_sub = null;
        foreach ($subjects as $sub) {
            if ($sub['type'] === 'Common' && stripos($sub['title'], 'english') !== false) {
                $english_sub = $sub;
                break;
            }
        }

        if ($english_sub !== null) {
            if ($semester === 1) {
                if ($shift_group === 'shift1') {
                    $english_strict_slots = [['I DAY', 'IV HOUR'], ['II DAY', 'III HOUR'], ['III DAY', 'IV HOUR'], ['VI DAY', 'III HOUR']];
                } else { 
                    $english_strict_slots = [['I DAY', 'I HOUR'], ['II DAY', 'II HOUR'], ['IV DAY', 'II HOUR'], ['VI DAY', 'II HOUR']];
                }
            } else {
                if ($shift_group === 'shift1') {
                    $english_strict_slots = [['I DAY', 'II HOUR'], ['II DAY', 'I HOUR'], ['III DAY', 'II HOUR'], ['IV DAY', 'I HOUR']];
                } else { 
                    $english_strict_slots = [['II DAY', 'IV HOUR'], ['IV DAY', 'IV HOUR'], ['V DAY', 'III HOUR'], ['VI DAY', 'IV HOUR']];
                }
            }

            foreach ($english_strict_slots as $slot) {
                $e_day = $slot[0];
                $e_hour = $slot[1];
                $key = $e_day . '_' . $e_hour;
                $timetable[$e_day][$e_hour] = $english_sub;
                $used_slots[$key] = true;
                if (isset($english_sub['staff_id'])) {
                    markStaffOccupied($english_sub['staff_id'], $e_day, $e_hour);
                }
            }
            $remaining_hours[$english_sub['id']] -= count($english_strict_slots);
            $english_subject_placed = true;
        }
    }

    // ============================================================
    // STEP 0.6: STRICT LAB PLACEMENT
    // ============================================================
    $strict_lab_slots_map = [];
    foreach ($subjects as $sub) {
        $sub_title = strtolower($sub['title']);
        $is_lab = ($sub['type'] === 'Lab');
        
        if ($is_lab || strpos($sub_title, 'python') !== false) {
            
            // --- ODD SEMESTERS ---
            if ($semester % 2 !== 0) {
                // I B.Sc (sem 1)
                if ($semester === 1 && $program === 'UG' && $is_lab && strpos($sub_title, 'programming methodology') !== false) {
                    $strict_lab_slots_map[$sub['id']] = ($shift_group === 'shift1') ?
                        [['I DAY', 'I HOUR'], ['I DAY', 'II HOUR'], ['V DAY', 'IV HOUR'], ['V DAY', 'V HOUR']] :
                        [['II DAY', 'IV HOUR'], ['II DAY', 'V HOUR'], ['V DAY', 'I HOUR'], ['V DAY', 'II HOUR']];
                }
                // II B.Sc (sem 3)
                elseif ($semester === 3 && $program === 'UG' && $is_lab && strpos($sub_title, 'java') !== false) {
                    $strict_lab_slots_map[$sub['id']] = ($shift_group === 'shift1') ?
                        [['V DAY', 'I HOUR'], ['V DAY', 'II HOUR'], ['V DAY', 'III HOUR']] :
                        [['I DAY', 'III HOUR'], ['I DAY', 'IV HOUR'], ['I DAY', 'V HOUR']];
                }
                // III B.Sc (sem 5)
                elseif ($semester === 5 && $program === 'UG' && $is_lab) {
                    if (strpos($sub_title, 'internet technologies') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['IV DAY', 'I HOUR'], ['IV DAY', 'II HOUR'], ['IV DAY', 'III HOUR']];
                    } elseif (strpos($sub_title, 'linux shell') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['VI DAY', 'IV HOUR'], ['VI DAY', 'V HOUR']];
                    }
                }
                // I M.Sc (sem 1)
                elseif ($semester === 1 && $program === 'PG' && $is_lab) {
                    if (strpos($sub_title, 'advance') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['II DAY', 'I HOUR'], ['II DAY', 'II HOUR'], ['II DAY', 'III HOUR']];
                    } elseif (strpos($sub_title, 'dbms') !== false || strpos($sub_title, 'database') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['IV DAY', 'IV HOUR'], ['IV DAY', 'V HOUR']];
                    }
                }
                // II M.Sc (sem 3)
                elseif ($semester === 3 && $program === 'PG' && $is_lab) {
                    if (strpos($sub_title, 'dip') !== false || strpos($sub_title, 'image') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['VI DAY', 'I HOUR'], ['VI DAY', 'II HOUR'], ['VI DAY', 'III HOUR']];
                    } elseif (strpos($sub_title, 'mobile') !== false && strpos($sub_title, 'app') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['III DAY', 'IV HOUR'], ['III DAY', 'V HOUR']];
                    }
                }
            } 
            // --- EVEN SEMESTERS ---
            else {
                // I B.Sc (sem 2) C++
                if ($semester === 2 && $program === 'UG' && $is_lab && strpos($sub_title, 'c++') !== false) {
                    $strict_lab_slots_map[$sub['id']] = ($shift_group === 'shift1') ?
                        [['V DAY', 'III HOUR'], ['V DAY', 'IV HOUR'], ['V DAY', 'V HOUR']] :
                        [['IV DAY', 'III HOUR'], ['IV DAY', 'IV HOUR'], ['IV DAY', 'V HOUR']];
                }
                // II B.Sc (sem 4) Python & DBMS
                elseif ($semester === 4 && $program === 'UG' && $is_lab) {
                    if (strpos($sub_title, 'python') !== false) {
                        $strict_lab_slots_map[$sub['id']] = [['II DAY', 'IV HOUR'], ['II DAY', 'V HOUR']];
                    } elseif (strpos($sub_title, 'dbms') !== false || strpos($sub_title, 'database') !== false) {
                        $strict_lab_slots_map[$sub['id']] = ($shift_group === 'shift1') ?
                            [['VI DAY', 'IV HOUR'], ['VI DAY', 'V HOUR']] :
                            [['IV DAY', 'I HOUR'], ['IV DAY', 'II HOUR']];
                    }
                }
                
                // C# Lab (UG)
                if ($program === 'UG' && $is_lab && strpos($sub_title, 'c#') !== false) {
                    $strict_lab_slots_map[$sub['id']] = [['I DAY', 'I HOUR'], ['I DAY', 'II HOUR'], ['I DAY', 'III HOUR']];
                }
                // OSC Lab (UG)
                if ($program === 'UG' && $is_lab && (strpos($sub_title, 'osc') !== false || strpos($sub_title, 'open source') !== false)) {
                    $strict_lab_slots_map[$sub['id']] = ($shift_group === 'shift1') ?
                        [['V DAY', 'I HOUR'], ['V DAY', 'II HOUR']] :
                        [['V DAY', 'IV HOUR'], ['V DAY', 'V HOUR']];
                }
                // Data Mining Lab (UG / PG)
                if ($is_lab && (strpos($sub_title, 'data mining') !== false || strpos($sub_title, 'dm&r') !== false)) {
                    $strict_lab_slots_map[$sub['id']] = [['III DAY', 'I HOUR'], ['III DAY', 'II HOUR'], ['III DAY', 'III HOUR']];
                }
                // Python Programming (PG Sem 2) 
                if ($semester === 2 && $program === 'PG' && strpos($sub_title, 'python') !== false) {
                    $strict_lab_slots_map[$sub['id']] = [['I DAY', 'IV HOUR'], ['I DAY', 'V HOUR']];
                }
            }
        }
    }

    foreach ($subjects as $sub) {
        if (isset($strict_lab_slots_map[$sub['id']])) {
            foreach ($strict_lab_slots_map[$sub['id']] as $slot) {
                $l_day = $slot[0];
                $l_hour = $slot[1];
                $key = $l_day . '_' . $l_hour;

                if (!isset($used_slots[$key])) {
                    $timetable[$l_day][$l_hour] = $sub;
                    $used_slots[$key] = true;
                    if (isset($sub['staff_id'])) {
                        markStaffOccupied($sub['staff_id'], $l_day, $l_hour);
                    }
                    if ($sub['type'] === 'Lab') {
                        markLabSlotOccupied($l_day, $l_hour, $shift_group);
                        $days_with_labs[$l_day] = $sub['id'];
                    }
                    $remaining_hours[$sub['id']]--;
                }
            }
        }
    }


    // ============================================================
    // STEP 1: NME — always Days 1-3, Hour 4 (III B.Sc only)
    // ============================================================
    if ($semester >= 5 && $semester <= 6) {
        $nme_days = ['I DAY', 'II DAY', 'III DAY'];
        $nme_hour = 'IV HOUR';
        foreach ($nme_days as $day)
            $used_slots[$day . '_' . $nme_hour] = true;
        if (count($nme_subjects) > 0) {
            $nme_subject = $nme_subjects[0];
            if ($remaining_hours[$nme_subject['id']] > 0) {
                foreach ($nme_days as $day) {
                    $timetable[$day][$nme_hour] = $nme_subject;
                    if (isset($nme_subject['staff_id']))
                        markStaffOccupied($nme_subject['staff_id'], $day, $nme_hour);
                }
                $remaining_hours[$nme_subject['id']] -= 3;
            }
        }
    }

    // ============================================================
    // STEP 2: Remaining Lab allocation 
    // ============================================================
    $possible_2hr_sets_preferred = [
        ['IV HOUR', 'V HOUR'],
        ['III HOUR', 'IV HOUR'],
        ['II HOUR', 'III HOUR'],
        ['I HOUR', 'II HOUR'],
    ];
    $possible_2hr_sets = [
        ['I HOUR', 'II HOUR'],
        ['II HOUR', 'III HOUR'],
        ['III HOUR', 'IV HOUR'],
        ['IV HOUR', 'V HOUR'],
    ];

    foreach ($labs_4hr as $lab) {
        if ($remaining_hours[$lab['id']] <= 0) continue;
        $placed4 = false;
        foreach ($lab_days_order as $day1) {
            if ($placed4) break;
            if (isset($days_with_labs[$day1])) continue;

            foreach ($possible_2hr_sets as $slot1_hours) {
                $can_place1 = true;
                foreach ($slot1_hours as $h) {
                    $key = $day1 . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day1, $h, $shift_group)) {
                        $can_place1 = false; break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day1, $h)) {
                        $can_place1 = false; break;
                    }
                }
                if (!$can_place1) continue;

                foreach ($shuffled_days as $day2) {
                    if ($day2 === $day1 || isset($days_with_labs[$day2])) continue;
                    foreach ($possible_2hr_sets as $slot2_hours) {
                        if ($slot2_hours === $slot1_hours) continue;
                        $can_place2 = true;
                        foreach ($slot2_hours as $h) {
                            $key = $day2 . '_' . $h;
                            if (isset($used_slots[$key]) || !isLabSlotAvailable($day2, $h, $shift_group)) {
                                $can_place2 = false; break;
                            }
                            if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day2, $h)) {
                                $can_place2 = false; break;
                            }
                        }
                        if ($can_place2) {
                            foreach ($slot1_hours as $h) {
                                $timetable[$day1][$h] = $lab;
                                $used_slots[$day1 . '_' . $h] = true;
                                markLabSlotOccupied($day1, $h, $shift_group);
                                if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day1, $h);
                            }
                            $days_with_labs[$day1] = $lab['id'];
                            foreach ($slot2_hours as $h) {
                                $timetable[$day2][$h] = $lab;
                                $used_slots[$day2 . '_' . $h] = true;
                                markLabSlotOccupied($day2, $h, $shift_group);
                                if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day2, $h);
                            }
                            $days_with_labs[$day2] = $lab['id'];
                            $remaining_hours[$lab['id']] -= 4;
                            $placed4 = true;
                            break 3; 
                        }
                    }
                }
            }
        }
    }

    foreach ($labs_3hr as $lab) {
        if ($remaining_hours[$lab['id']] <= 0) continue;
        $possible_3hr_sets = [
            ['I HOUR', 'II HOUR', 'III HOUR'],  
            ['III HOUR', 'IV HOUR', 'V HOUR'],  
        ];
        $placed_3hr = false;
        foreach ($lab_days_order as $day) {
            if (isset($days_with_labs[$day])) continue;
            foreach ($possible_3hr_sets as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
                        $can_place = false; break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false; break;
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
                        if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day, $h);
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 3;
                    $placed_3hr = true;
                    break 2; 
                }
            }
        }
    }

    foreach ($labs_2hr as $lab) {
        if ($remaining_hours[$lab['id']] <= 0) continue;
        $target_days = $lab_days_order;
        if ($semester >= 5 && $semester <= 6) {
            $target_days = array_intersect($lab_days_order, ['IV DAY', 'V DAY', 'VI DAY']);
        }
        $placed_2hr = false;
        foreach ($target_days as $day) {
            if (isset($days_with_labs[$day])) continue;
            foreach ($possible_2hr_sets_preferred as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
                        $can_place = false; break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false; break;
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
                        if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day, $h);
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 2;
                    $placed_2hr = true;
                    break 2; 
                }
            }
        }
    }

    // ============================================================
    // STEP 0: FIXED PLACEMENT — Tamil, English (Common), Allied
    // ============================================================
    $fixed_preferred = [
        'Tamil' => [
            1 => ['I DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'II DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'III DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'IV DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'V DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'VI DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR']],
            2 => ['I DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'II DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'III DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'IV DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'V DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'VI DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR']],
            3 => ['I DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'II DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'III DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'IV DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'V DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'VI DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR']],
            4 => ['I DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'II DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'III DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'IV DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'V DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'VI DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR']],
        ],
        'English' => [
            1 => ['I DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'II DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'III DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'IV DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'V DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'VI DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR']],
            2 => ['I DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'II DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'III DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'IV DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'V DAY' => ['IV HOUR', 'III HOUR', 'V HOUR', 'II HOUR', 'I HOUR'], 'VI DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'II HOUR', 'I HOUR']],
            3 => ['I DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'II DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'III DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'IV DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'V DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'VI DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR']],
            4 => ['I DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'II DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'III DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'IV DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'V DAY' => ['II HOUR', 'I HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'], 'VI DAY' => ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR']],
        ],
        'Allied' => [
            1 => ['I DAY' => ['V HOUR', 'IV HOUR', 'I HOUR', 'II HOUR', 'III HOUR'], 'II DAY' => ['I HOUR', 'V HOUR', 'II HOUR', 'III HOUR', 'IV HOUR'], 'III DAY' => ['V HOUR', 'I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR'], 'IV DAY' => ['V HOUR', 'IV HOUR', 'I HOUR', 'II HOUR', 'III HOUR'], 'V DAY' => ['III HOUR', 'V HOUR', 'I HOUR', 'II HOUR', 'IV HOUR'], 'VI DAY' => ['II HOUR', 'V HOUR', 'I HOUR', 'III HOUR', 'IV HOUR']],
            2 => ['I DAY' => ['V HOUR', 'IV HOUR', 'I HOUR', 'II HOUR', 'III HOUR'], 'II DAY' => ['I HOUR', 'V HOUR', 'II HOUR', 'III HOUR', 'IV HOUR'], 'III DAY' => ['V HOUR', 'I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR'], 'IV DAY' => ['V HOUR', 'IV HOUR', 'I HOUR', 'II HOUR', 'III HOUR'], 'V DAY' => ['III HOUR', 'V HOUR', 'I HOUR', 'II HOUR', 'IV HOUR'], 'VI DAY' => ['II HOUR', 'V HOUR', 'I HOUR', 'III HOUR', 'IV HOUR']],
            3 => ['I DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'II DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'III DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'I HOUR', 'II HOUR'], 'IV DAY' => ['V HOUR', 'IV HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'V DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'VI DAY' => ['V HOUR', 'IV HOUR', 'III HOUR', 'I HOUR', 'II HOUR']],
            4 => ['I DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'II DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'III DAY' => ['III HOUR', 'IV HOUR', 'V HOUR', 'I HOUR', 'II HOUR'], 'IV DAY' => ['V HOUR', 'IV HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'V DAY' => ['IV HOUR', 'V HOUR', 'III HOUR', 'I HOUR', 'II HOUR'], 'VI DAY' => ['V HOUR', 'IV HOUR', 'III HOUR', 'I HOUR', 'II HOUR']],
        ],
    ];

    foreach ($fixed_subjects as $fixed_sub) {
        $sub_type = $fixed_sub['type'];
        $title_lower = strtolower($fixed_sub['title']);

        if ($english_subject_placed && stripos($title_lower, 'english') !== false) {
            continue;
        }

        if ($sub_type === 'Allied') {
            $pref_map = $fixed_preferred['Allied'][$semester] ?? null;
        }
        elseif (stripos($title_lower, 'tamil') !== false) {
            $pref_map = $fixed_preferred['Tamil'][$semester] ?? null;
        }
        elseif (stripos($title_lower, 'english') !== false) {
            $pref_map = $fixed_preferred['English'][$semester] ?? null;
        }
        else {
            $pref_map = null;
        }

        $hours_to_place = $remaining_hours[$fixed_sub['id']];

        foreach ($days as $day) { 
            if ($hours_to_place <= 0) break;
            $hours_to_try = ($pref_map && isset($pref_map[$day])) ? $pref_map[$day] : $hours;

            foreach ($hours_to_try as $hour) {
                $key = $day . '_' . $hour;
                if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null) continue;
                if (isNMEReserved($semester, $day, $hour)) continue;
                if (isset($fixed_sub['staff_id']) && !isStaffAvailable($fixed_sub['staff_id'], $day, $hour)) continue;

                $timetable[$day][$hour] = $fixed_sub;
                $used_slots[$key] = true;
                if (isset($fixed_sub['staff_id'])) markStaffOccupied($fixed_sub['staff_id'], $day, $hour);
                $remaining_hours[$fixed_sub['id']]--;
                $hours_to_place--;
                break; 
            }
        }
    }

    // STEP 3: Fill with regular subjects
    $subject_pool = [];
    foreach ($regular_subjects as $subject) {
        for ($i = 0; $i < $subject['hours_per_week']; $i++) {
            $subject_pool[] = $subject;
        }
    }

    shuffle($subject_pool);

    foreach ($subject_pool as $subject) {
        $placed = false;
        $is_staff_subject = isset($subject['staff_id']);

        foreach ($shuffled_days as $day) {
            if ($placed) break;
            foreach ($hours as $hour) {
                $key = $day . '_' . $hour;

                if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null) continue;
                if (isNMEReserved($semester, $day, $hour)) continue;
                if ($is_staff_subject && !isStaffAvailable($subject['staff_id'], $day, $hour)) continue;

                if ($is_staff_subject) {
                    $hours_today = getStaffHoursOnDay($timetable, $subject['staff_id'], $day);
                    if ($hours_today >= 3) continue;
                    if ($subject['type'] !== 'Lab' && staffHasLabOnDay($timetable, $subject['staff_id'], $day)) continue;
                }

                if (wouldViolateConsecutive($timetable, $day, $hour, $subject['id'])) continue;

                $timetable[$day][$hour] = $subject;
                $used_slots[$key] = true;
                if ($is_staff_subject) markStaffOccupied($subject['staff_id'], $day, $hour);
                $remaining_hours[$subject['id']]--;
                $placed = true;
                break;
            }
        }
    }

    // STEP 3.5: Force fill remaining subject hours
    foreach ($remaining_hours as $subject_id => $hours_left) {
        if ($hours_left <= 0) continue;
        $subject = null;
        foreach ($subjects as $s) {
            if ($s['id'] == $subject_id) {
                $subject = $s; break;
            }
        }
        if (!$subject) continue;
        if ($subject['type'] === 'Common' || $subject['type'] === 'Allied') continue;

        while ($remaining_hours[$subject_id] > 0) {
            $placed = false;
            $is_staff_subject_ff = isset($subject['staff_id']);
            
            foreach ($shuffled_days as $day) {
                if ($placed) break;
                foreach ($hours as $hour) {
                    $key = $day . '_' . $hour;
                    if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null) continue;
                    if (isNMEReserved($semester, $day, $hour)) continue;
                    if ($is_staff_subject_ff && !isStaffAvailable($subject['staff_id'], $day, $hour)) continue;

                    if ($is_staff_subject_ff) {
                        $hours_today_ff = getStaffHoursOnDay($timetable, $subject['staff_id'], $day);
                        if ($hours_today_ff >= 4) continue;
                    }
                    if ($subject['type'] === 'Lab' && !isLabSlotAvailable($day, $hour, $shift_group)) continue;

                    $timetable[$day][$hour] = $subject;
                    $used_slots[$key] = true;
                    if (isset($subject['staff_id'])) markStaffOccupied($subject['staff_id'], $day, $hour);
                    if ($subject['type'] === 'Lab') markLabSlotOccupied($day, $hour, $shift_group);
                    $remaining_hours[$subject_id]--;
                    $placed = true;
                    break;
                }
            }
            if (!$placed) break; 
        }
    }

    // STEP 3.6: INTELLIGENT SWAPPING
    foreach ($remaining_hours as $subject_id => $hours_left) {
        if ($hours_left <= 0) continue;
        $subject = null;
        foreach ($subjects as $s) {
            if ($s['id'] == $subject_id) { $subject = $s; break; }
        }
        if (!$subject) continue;

        $swap_attempts = 0;
        while ($remaining_hours[$subject_id] > 0 && $swap_attempts < 50) {
            $swap_attempts++;
            $swapped = false;
            $empty_slots = [];
            foreach ($shuffled_days as $day) {
                foreach ($hours as $hour) {
                    if ($timetable[$day][$hour] === null && !isNMEReserved($semester, $day, $hour)) {
                        $empty_slots[] = ['day' => $day, 'hour' => $hour];
                    }
                }
            }

            if (empty($empty_slots)) break; 

            foreach ($shuffled_days as $target_day) {
                if ($swapped) break;
                foreach ($hours as $target_hour) {
                    if ($timetable[$target_day][$target_hour] === null) continue;
                    if (isNMEReserved($semester, $target_day, $target_hour)) continue;
                    if (isset($subject['staff_id']) && !isStaffAvailable($subject['staff_id'], $target_day, $target_hour)) continue;
                    if ($subject['type'] === 'Lab' && !isLabSlotAvailable($target_day, $target_hour, $shift_group)) continue;

                    $occupant = $timetable[$target_day][$target_hour];
                    if (!$occupant || $occupant['type'] === 'NME' || $occupant['type'] === 'Common' || $occupant['type'] === 'Allied') continue;

                    foreach ($empty_slots as $empty) {
                        $e_day = $empty['day'];
                        $e_hour = $empty['hour'];
                        if (isset($occupant['staff_id']) && !isStaffAvailable($occupant['staff_id'], $e_day, $e_hour)) continue;
                        if ($occupant['type'] === 'Lab' && !isLabSlotAvailable($e_day, $e_hour, $shift_group)) continue;

                        $timetable[$e_day][$e_hour] = $occupant;
                        $used_slots[$e_day . '_' . $e_hour] = true;
                        if (isset($occupant['staff_id'])) markStaffOccupied($occupant['staff_id'], $e_day, $e_hour);
                        if ($occupant['type'] === 'Lab') markLabSlotOccupied($e_day, $e_hour, $shift_group);

                        if (isset($occupant['staff_id'])) {
                            unset($GLOBALS['staff_schedule'][$target_day . '_' . $target_hour][$occupant['staff_id']]);
                        }
                        if ($occupant['type'] === 'Lab') {
                            unset($GLOBALS['lab_schedule'][$shift_group][$target_day . '_' . $target_hour]);
                        }

                        $timetable[$target_day][$target_hour] = $subject;
                        if (isset($subject['staff_id'])) markStaffOccupied($subject['staff_id'], $target_day, $target_hour);
                        if ($subject['type'] === 'Lab') markLabSlotOccupied($target_day, $target_hour, $shift_group);

                        $remaining_hours[$subject_id]--;
                        $swapped = true;
                        break;
                    }
                    if ($swapped) break;
                }
            }
            if (!$swapped) break;
        }
    }

    return ['timetable' => $timetable];
}

$classes = $conn->query($classes_query);
$classes_array = [];
while ($row = $classes->fetch_assoc()) {
    $classes_array[] = $row;
}

$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

$global_max_attempts = 2500;

$valid_global_timetables_collection = [];
$fallback_timetables_collection = [];
$seen_timetable_hashes = [];

for ($global_attempt = 1; $global_attempt <= $global_max_attempts; $global_attempt++) {
    // Reset global state for each full attempt
    $GLOBALS['staff_schedule'] = [];
    $GLOBALS['lab_schedule'] = [];
    
    $timetables = [];
    $local_has_gaps = false;

    // Process each class
    foreach ($classes_array as $class) {
        if (strpos($class['name'], 'I ') === 0) {
            $semester = $semester_filter == 'odd' ? 1 : 2;
        }
        elseif (strpos($class['name'], 'II ') === 0) {
            $semester = $semester_filter == 'odd' ? 3 : 4;
        }
        elseif (strpos($class['name'], 'III ') === 0) {
            $semester = $semester_filter == 'odd' ? 5 : 6;
        }
        else {
            $semester = 1;
        }

    $program = (strpos($class['name'], 'M.Sc') !== false) ? 'PG' : 'UG';
    if ($program === 'PG' || strpos($class['shift'], 'Shift 1') !== false) {
        $shift_group = 'shift1';
    }
    else {
        $shift_group = 'shift2';
    }

    $subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $semester AND COALESCE(is_allocated, 1) = 1 ORDER BY 
        CASE 
            WHEN type = 'Core' THEN 1
            WHEN type = 'Lab' THEN 2
            WHEN type = 'Allied' THEN 3
            WHEN type = 'Common' THEN 4
            ELSE 5
        END, id";
    $subjects_result = $conn->query($subjects_query);

    $subjects = [];
    while ($subject = $subjects_result->fetch_assoc()) {
        $shift_key = null;
        if (strpos($class['name'], 'B.Sc') !== false) {
            if (strpos($class['shift'], 'Shift 1') !== false) {
                $shift_key = 'staff_shift1_' . $subject['id'];
            }
            elseif (strpos($class['shift'], 'Shift 2') !== false) {
                $shift_key = 'staff_shift2_' . $subject['id'];
            }
        }

        $base_staff_key = $shift_key ? $shift_key : 'staff_' . $subject['id'] . '_' . $class['id'];
        $staff_assigned = [];

        for ($index = 1; $index <= 10; $index++) {
            if ($shift_key) {
                $check_key = $base_staff_key . '_' . $index;
            } else {
                $check_key = 'staff_' . $subject['id'] . '_' . $index;
            }

            if ($index === 1 && !isset($staff_allocations[$check_key]) && isset($staff_allocations[$base_staff_key])) {
                $check_key = $base_staff_key;
            }

            if (isset($staff_allocations[$check_key]) && !empty($staff_allocations[$check_key])) {
                $hours_key = $shift_key ? 'hours_' . $shift_group . '_' . $subject['id'] . '_' . $index : 'hours_' . $subject['id'] . '_' . $index;

                if (isset($_SESSION['hours_changes'][$hours_key])) {
                    $assigned_hours = intval($_SESSION['hours_changes'][$hours_key]);
                }
                else if ($index === 1) {
                    $base_hours_key = $shift_key ? 'hours_' . $shift_group . '_' . $subject['id'] : 'hours_' . $subject['id'];
                    $assigned_hours = isset($_SESSION['hours_changes'][$base_hours_key]) ? intval($_SESSION['hours_changes'][$base_hours_key]) : intval($subject['hours_per_week']);
                }
                else {
                    $assigned_hours = 0;
                }

                $staff_assigned[] = [
                    'staff_id' => $staff_allocations[$check_key],
                    'hours' => $assigned_hours,
                    'staff_index' => $index
                ];
            }
        }

        $subject['short_name'] = getShortName($subject['title'], $subject['type'], $semester);

        if (!empty($staff_assigned)) {
            foreach ($staff_assigned as $assignment) {
                if ($assignment['hours'] <= 0) continue; 

                $split_subject = $subject; 
                $split_subject['hours_per_week'] = $assignment['hours'];
                $split_subject['staff_index'] = $assignment['staff_index'];
                $split_subject['base_id'] = $subject['id']; 
                if ($assignment['staff_index'] !== null) {
                    $split_subject['id'] = $subject['id'] . '_' . $assignment['staff_index']; 
                }

                $staff_id = $assignment['staff_id'];
                $staff_query = "SELECT name, short_code FROM staff WHERE id = $staff_id";
                $staff_result = $conn->query($staff_query);
                if ($staff_result && $staff_row = $staff_result->fetch_assoc()) {
                    $split_subject['staff_name'] = $staff_row['name'];
                    $split_subject['staff_code'] = $staff_row['short_code'];
                    $split_subject['staff_id'] = $staff_id;
                }
                $subjects[] = $split_subject;
            }
        }
        else {
            $subject['base_id'] = $subject['id'];
            $subject['staff_index'] = null;
            $subjects[] = $subject;
        }
    }

    $timetable = generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, 300);

    if ($timetable !== null) {
        $timetables[] = [
            'class'    => $class,
            'semester' => $semester,
            'timetable'=> $timetable,
        ];
    }
    else {
        $timetables[] = [
            'class'    => $class,
            'semester' => $semester,
            'timetable'=> null,
            'has_gaps' => true,
        ];
        $local_has_gaps = true;
    }
    } // End foreach ($classes_array as $class)

    // Verify global staff constraints
    $constraint_failed = false;
    $failure_reason = "";
    $current_violations = 0;
    $six_day_failure_details = [];
    
    if ($local_has_gaps) {
        $failure_reason = "Gaps exist in one or more class timetables.";
    } else {
        $staff_total_hours = [];
        $staff_daily_presence = [];
        $staff_hour_slots = []; // Tracks if a staff member is double-booked across classes
        
        foreach ($timetables as $tt) {
            if (!$tt['timetable']) continue;
            foreach ($tt['timetable'] as $day => $day_hours) {
                foreach ($day_hours as $hour => $subject) {
                    if ($subject !== null && isset($subject['staff_id'])) {
                        $sid = $subject['staff_id'];
                        $slot_key = $day . '_' . $hour;

                        // Check for global cross-class collision
                        if (!isset($staff_hour_slots[$sid])) {
                            $staff_hour_slots[$sid] = [];
                        }
                        if (isset($staff_hour_slots[$sid][$slot_key])) {
                            $constraint_failed = true;
                            $cls_name = $tt['class']['name'];
                            $sname = isset($subject['staff_name']) ? $subject['staff_name'] : "ID $sid";
                            $failure_reason = "Staff '$sname' is double-booked on $day at $hour (found in class $cls_name).";
                            break 3; // Break out of the 3 nested loops immediately
                        }
                        $staff_hour_slots[$sid][$slot_key] = true;

                        if (!isset($staff_total_hours[$sid])) {
                            $staff_total_hours[$sid] = 0;
                            $staff_daily_presence[$sid] = [];
                        }
                        $staff_total_hours[$sid]++;
                        $staff_daily_presence[$sid][$day] = true;
                    }
                }
            }
        }
        
        if (!$constraint_failed) {
            // Ensure staff with >= 6 hours have at least 1 hr on all 6 working days
            foreach ($staff_total_hours as $sid => $total) {
                if ($total >= 6) {
                    $days_present = count($staff_daily_presence[$sid]);
                    if ($days_present < 6) {
                        $current_violations++;
                        $constraint_failed = true; // Technically failed the strict constraint
                        
                        $missed = implode(', ', array_diff(['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'], array_keys($staff_daily_presence[$sid])));
                        $sres = $conn->query("SELECT name FROM staff WHERE id=$sid");
                        $srow = $sres->fetch_assoc();
                        $sname = $srow ? $srow['name'] : "ID $sid";
                        $six_day_failure_details[] = "'$sname' (misses $missed)";
                    }
                }
            }
            if ($current_violations > 0) {
                $failure_reason = "6-Day rule violated for: " . implode('; ', $six_day_failure_details);
            }
        }
    }

    if (!$local_has_gaps && !$constraint_failed) {
        // Hash timetable to ensure uniqueness
        $hashStr = "";
        foreach ($timetables as $tt) {
            foreach ($tt['timetable'] as $day => $day_hours) {
                foreach ($day_hours as $hour => $subj) {
                    if ($subj) $hashStr .= $day.$hour.$subj['id'].(isset($subj['staff_id']) ? $subj['staff_id'] : '')."|";
                }
            }
        }
        $h = md5($hashStr);
        if (!isset($seen_timetable_hashes[$h])) {
            $seen_timetable_hashes[$h] = true;
            $valid_global_timetables_collection[] = [
                'timetables' => $timetables,
                'warning' => '✅ Timetable was generated successfully! All constraints (including the 6-day distribution rule) are fully satisfied.',
                'is_success' => true
            ];
        }
        // No longer breaking at 12; collect all possible valid timetables

    } else if ($failure_reason || $local_has_gaps) {
        $hashStr = "";
        foreach ($timetables as $tt) {
            if ($tt['timetable']) {
                foreach ($tt['timetable'] as $day => $day_hours) {
                    foreach ($day_hours as $hour => $subj) {
                        if ($subj) $hashStr .= $day.$hour.$subj['id'].(isset($subj['staff_id']) ? $subj['staff_id'] : '')."|";
                    }
                }
            }
        }
        $h = md5($hashStr);
        if (!isset($seen_timetable_hashes[$h])) {
            $seen_timetable_hashes[$h] = true;
            
            $severity = 0;
            if ($local_has_gaps) $severity += 10000;
            if ($constraint_failed) $severity += 1000;
            $severity += $current_violations;
            
            $advisory_lines = [];
            if ($local_has_gaps || $constraint_failed) {
                 $advisory_lines[] = "The timetable was generated but constraints were not fully satisfied.";
                 $advisory_lines[] = $failure_reason ? $failure_reason : "Gaps exist.";
                 $warning = implode(" ", $advisory_lines);
            } else {
                 $advisory_lines[] = "The timetable was generated but the 6-day distribution rule could not be fully satisfied.";
                 $advisory_lines[] = $failure_reason;
                 $advisory_lines[] = "💡 To fix: consider adding more subjects or redistributing hours so affected staff appear on every working day.";
                 $warning = implode(" ", $advisory_lines);
            }

            $fallback_timetables_collection[] = [
                'severity' => $severity,
                'timetables' => $timetables,
                'warning' => $warning
            ];
        }
    }
    
    $last_attempt_reason = $failure_reason;
} // End global_attempt loop

// Sort Fallbacks by severity (best first)
usort($fallback_timetables_collection, function($a, $b) {
    $sevA = isset($a['severity']) ? $a['severity'] : ($a['violations'] ?? 0);
    $sevB = isset($b['severity']) ? $b['severity'] : ($b['violations'] ?? 0);
    return $sevA <=> $sevB;
});

$final_collection = [];
foreach ($valid_global_timetables_collection as $vt) {
    $final_collection[] = $vt;
}

$had_valid = count($final_collection) > 0;

foreach ($fallback_timetables_collection as $ft) {
    $warning_msg = $ft['warning'];
    if (!$had_valid) {
        $warning_msg = "<strong>No possibilities with satisfying full conditions like that.</strong><br>" . $warning_msg;
    }
    $final_collection[] = [
        'timetables' => $ft['timetables'],
        'warning' => $warning_msg,
        'is_success' => false
    ];
}

// Ensure at least 5 possibilities are shown
if (count($final_collection) > 0 && count($final_collection) < 5) {
    $base_count = count($final_collection);
    $idx = 0;
    while(count($final_collection) < 5) {
        $final_collection[] = $final_collection[$idx];
        $idx = ($idx + 1) % $base_count;
    }
}

if (count($final_collection) > 0) {
    $_SESSION['timetable_collection'] = $final_collection;
    $_SESSION['timetable_index'] = 0;
    
    $_SESSION['current_timetables'] = $final_collection[0]['timetables'];
    $_SESSION['generation_warning'] = $final_collection[0]['warning'];
    $_SESSION['generation_success'] = isset($final_collection[0]['is_success']) ? $final_collection[0]['is_success'] : false;
    unset($_SESSION['generation_error']);
} else {
    // All attempts failed (likely due to gaps or double-booking across classes).
    // Run one final best-effort pass without the strict no-gap requirement,
    // so we still show SOMETHING to the user.

    $GLOBALS['staff_schedule'] = [];
    $GLOBALS['lab_schedule'] = [];
    $best_effort_timetables = [];
    $advisory_parts = [];

    $classes = $conn->query($classes_query);
    $classes_array_final = [];
    while ($row = $classes->fetch_assoc()) {
        $classes_array_final[] = $row;
    }

    foreach ($classes_array_final as $class) {
        if (strpos($class['name'], 'I ') === 0)       $semester = $semester_filter == 'odd' ? 1 : 2;
        elseif (strpos($class['name'], 'II ') === 0)  $semester = $semester_filter == 'odd' ? 3 : 4;
        elseif (strpos($class['name'], 'III ') === 0) $semester = $semester_filter == 'odd' ? 5 : 6;
        else                                           $semester = 1;

        $program    = (strpos($class['name'], 'M.Sc') !== false) ? 'PG' : 'UG';
        $shift_group = ($program === 'PG' || strpos($class['shift'], 'Shift 1') !== false) ? 'shift1' : 'shift2';

        $subjects_query_f = "SELECT * FROM subjects WHERE program = '$program' AND semester = $semester AND COALESCE(is_allocated, 1) = 1 ORDER BY CASE WHEN type = 'Core' THEN 1 WHEN type = 'Lab' THEN 2 WHEN type = 'Allied' THEN 3 WHEN type = 'Common' THEN 4 ELSE 5 END, id";
        $subjects_result_f = $conn->query($subjects_query_f);
        $subjects_f = [];
        while ($subject = $subjects_result_f->fetch_assoc()) {
            $shift_key = null;
            if (strpos($class['name'], 'B.Sc') !== false) {
                $shift_key = (strpos($class['shift'], 'Shift 1') !== false)
                    ? 'staff_shift1_' . $subject['id']
                    : 'staff_shift2_' . $subject['id'];
            }
            $base_staff_key = $shift_key ? $shift_key : 'staff_' . $subject['id'] . '_' . $class['id'];
            $staff_assigned_f = [];
            for ($index = 1; $index <= 10; $index++) {
                $check_key = $shift_key ? $base_staff_key . '_' . $index : 'staff_' . $subject['id'] . '_' . $index;
                if ($index === 1 && !isset($staff_allocations[$check_key]) && isset($staff_allocations[$base_staff_key]))
                    $check_key = $base_staff_key;
                if (isset($staff_allocations[$check_key]) && !empty($staff_allocations[$check_key])) {
                    $hours_key = $shift_key ? 'hours_' . $shift_group . '_' . $subject['id'] . '_' . $index : 'hours_' . $subject['id'] . '_' . $index;
                    if (isset($_SESSION['hours_changes'][$hours_key]))
                        $assigned_hours = intval($_SESSION['hours_changes'][$hours_key]);
                    else if ($index === 1) {
                        $base_hours_key = $shift_key ? 'hours_' . $shift_group . '_' . $subject['id'] : 'hours_' . $subject['id'];
                        $assigned_hours = isset($_SESSION['hours_changes'][$base_hours_key]) ? intval($_SESSION['hours_changes'][$base_hours_key]) : intval($subject['hours_per_week']);
                    } else $assigned_hours = 0;
                    $staff_assigned_f[] = ['staff_id' => $staff_allocations[$check_key], 'hours' => $assigned_hours, 'staff_index' => $index];
                }
            }
            $subject['short_name'] = getShortName($subject['title'], $subject['type'], $semester);
            if (!empty($staff_assigned_f)) {
                foreach ($staff_assigned_f as $assignment) {
                    if ($assignment['hours'] <= 0) continue;
                    $split = $subject;
                    $split['hours_per_week'] = $assignment['hours'];
                    $split['staff_index']    = $assignment['staff_index'];
                    $split['base_id']        = $subject['id'];
                    $split['id']             = $subject['id'] . '_' . $assignment['staff_index'];
                    $staff_res_f = $conn->query("SELECT name, short_code FROM staff WHERE id=" . intval($assignment['staff_id']));
                    if ($staff_res_f && $sr = $staff_res_f->fetch_assoc()) {
                        $split['staff_name']  = $sr['name'];
                        $split['staff_code']  = $sr['short_code'];
                        $split['staff_id']    = $assignment['staff_id'];
                    }
                    $subjects_f[] = $split;
                }
            } else {
                $subject['base_id']    = $subject['id'];
                $subject['staff_index'] = null;
                $subjects_f[] = $subject;
            }
        }

        // Use single attempt (best-effort, may have gaps or conflicts)
        $result_f = generateSingleAttempt($subjects_f, $days, $hours, $semester, $shift_group,
            isset($_SESSION['current_class_index_map'][$class['id']]) ? $_SESSION['current_class_index_map'][$class['id']] : 0);
        $best_effort_timetables[] = ['class' => $class, 'semester' => $semester, 'timetable' => $result_f['timetable'], 'has_gaps' => true];
    }

    // Collect advisory info from the last failure reason
    $advisory_parts[] = "⚠️ A fully constraint-satisfying timetable could not be generated after $global_max_attempts attempts.";
    if (!empty($last_attempt_reason)) {
        // Parse reason to give actionable advice
        if (strpos($last_attempt_reason, 'double-booked') !== false) {
            // Extract staff ID
            preg_match('/Staff ID (\d+) is double-booked on (\S+ \S+) at (\S+ \S+).*class (.+)\./', $last_attempt_reason, $m);
            if (!empty($m)) {
                $dbl_sid = intval($m[1]);
                $dbl_day = $m[2]; $dbl_hr = $m[3]; $dbl_cls = $m[4];
                $sres = $conn->query("SELECT name FROM staff WHERE id=$dbl_sid");
                $sname = ($sres && $sr = $sres->fetch_assoc()) ? $sr['name'] : "Staff #$dbl_sid";
                $advisory_parts[] = "The constraint that could not be satisfied: $sname is scheduled in two different classes at $dbl_day $dbl_hr (conflict in $dbl_cls).";
                $advisory_parts[] = "💡 To fix: assign a different staff member to one of the conflicting subjects, or reduce hours for $sname in one class.";
            } else {
                $advisory_parts[] = "Reason: $last_attempt_reason";
                $advisory_parts[] = "💡 To fix: check if any staff member is allocated to two subjects in different classes at the same time slot. Try reassigning to a different staff.";
            }
        } elseif (strpos($last_attempt_reason, '6-Day rule') !== false) {
            $advisory_parts[] = "Reason: $last_attempt_reason";
            $advisory_parts[] = "💡 To fix: add at least one hour of work per day for the listed staff, or spread their subject allocations across more classes.";
        } elseif (strpos($last_attempt_reason, 'Gaps exist') !== false) {
            $advisory_parts[] = "Gaps remain in the timetable because there are not enough available slots for all subjects.";
            $advisory_parts[] = "💡 To fix: review total subject hours per class — they must sum to 30 for a complete timetable.";
        } else {
            $advisory_parts[] = "Reason: $last_attempt_reason";
            $advisory_parts[] = "💡 Small adjustments to staff allocation or subject hours may resolve the conflict.";
        }
    }

    $warning_msg = implode(" ", $advisory_parts);
    $_SESSION['timetable_collection'] = [
        [
            'timetables' => $best_effort_timetables,
            'warning' => $warning_msg
        ]
    ];
    $_SESSION['timetable_index'] = 0;

    $_SESSION['current_timetables'] = $best_effort_timetables;
    $_SESSION['generation_warning'] = $warning_msg;
    $_SESSION['generation_success'] = false;
    unset($_SESSION['generation_error']);
}

$_SESSION['semester_filter'] = $semester_filter;
$_SESSION['current_page'] = 'generated_timetable_view';

header("Location: generated_timetable_view.php");
exit;

