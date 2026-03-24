<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Get semester filter
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : (isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd');

// Get staff allocations from session
$staff_allocations = isset($_SESSION['staff_allocations']) ? $_SESSION['staff_allocations'] : [];

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
function getShortName($title, $type, $semester = null)
{
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

<<<<<<< HEAD
/**
 * Count how many timetable slots on $day already belong to $staff_id.
 */
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
/**
 * Return true if $staff_id already has a Lab slot on $day.
 */
=======
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

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
            // Skip NME reserved slots that might legitimately be empty
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
// Generate timetable for a single class with multiple attempts.
// Returns a fully gap-free timetable, or null if none found within the limit.
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
function generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, $max_attempts = 300)
{
    global $staff_schedule, $lab_schedule;

    $start_time = time();
<<<<<<< HEAD
    $max_time_per_class = 8; // allow up to 8 seconds per class

    // Resolve class index once
=======
    $max_time_per_class = 8; 

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
    // Save original global state before any attempt
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $initial_staff_schedule = $staff_schedule;
    $initial_lab_schedule   = $lab_schedule;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
<<<<<<< HEAD
        // Reset global state to initial for each attempt
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        $staff_schedule = $initial_staff_schedule;
        $lab_schedule   = $initial_lab_schedule;

        $result = generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group, $class_index);

<<<<<<< HEAD
        // Check for gaps
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
        // Only accept gap-free results
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        if ($gap_count === 0) {
            return $result['timetable'];
        }

<<<<<<< HEAD
        // Break early if taking too long
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        if (time() - $start_time >= $max_time_per_class) {
            break;
        }
    }

<<<<<<< HEAD
    // Could not find a gap-free arrangement — restore original state and return null
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $staff_schedule = $initial_staff_schedule;
    $lab_schedule   = $initial_lab_schedule;
    return null;
}

function generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group, $class_index)
{
    global $staff_schedule, $lab_schedule;

<<<<<<< HEAD
    // Initialize timetable
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
=======
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

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    foreach ($subjects as $subject) {
        $remaining_hours[$subject['id']] = $subject['hours_per_week'];
    }

<<<<<<< HEAD
    // Categorize subjects
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $labs_4hr = [];
    $labs_3hr = [];
    $labs_2hr = [];
    $nme_subjects = [];
<<<<<<< HEAD
    $fixed_subjects = []; // Tamil (Common), English (Common), Allied — NEVER shuffled
=======
    $fixed_subjects = []; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
            // Fixed subjects: Tamil, English (Common) and Allied — placed at fixed positions
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            $fixed_subjects[] = $subject;
        }
        else {
            $regular_subjects[] = $subject;
        }
    }

<<<<<<< HEAD
    // Shuffle ONLY core/regular subjects — fixed subjects NEVER shuffled
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    shuffle($labs_4hr);
    shuffle($labs_3hr);
    shuffle($labs_2hr);
    shuffle($regular_subjects);

<<<<<<< HEAD
    // Labs use $days (fixed order) so common subjects always land same slots each regeneration
    $lab_days_order = $days; // fixed order for lab placement
    $shuffled_days = $days;
    shuffle($shuffled_days); // core subjects still vary
=======
    $lab_days_order = $days; 
    $shuffled_days = $days;
    shuffle($shuffled_days); 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)

    // ============================================================
    // STEP 0.5: PRE-FILL MANUAL ALLOCATIONS
    // ============================================================
    if (isset($_SESSION['manual_allocations'][$class_index][$shift_group])) {
<<<<<<< HEAD
=======
        // Track which split-subject index to use next for each base subject_id
        $split_dist_counter = []; // base_id => next_staff_index_to_use (1-based)

        // Pre-build a map of split subjects per base_id for quick lookup
        $split_subjects_map = []; // base_id => [ staff_index => subject ]
        foreach ($subjects as $s) {
            $bid = $s['base_id'] ?? $s['id'];
            $sidx = $s['staff_index'] ?? 1;
            $split_subjects_map[$bid][$sidx] = $s;
        }

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        foreach ($_SESSION['manual_allocations'][$class_index][$shift_group] as $slot => $alloc) {
            $day = $alloc['day'];
            $hour = $alloc['hour'];
            $subject_id = $alloc['subject_id'];

<<<<<<< HEAD
            // Find the subject based on the manual allocation's base ID and match to staff_index if it was saved
            $manual_subject = null;
            $manual_staff_index = isset($alloc['staff_index']) ? $alloc['staff_index'] : null;

            foreach ($subjects as $s) {
                // If the manual allocation specified a staff_index snippet, use the exact ID match
                // Otherwise fallback to base_id matching
                if ($manual_staff_index !== null) {
=======
            $manual_subject = null;
            $manual_staff_index = isset($alloc['staff_index']) ? $alloc['staff_index'] : null;

            if ($manual_staff_index !== null) {
                // Explicit staff_index — find exact split subject
                foreach ($subjects as $s) {
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    if ($s['id'] === $subject_id . '_' . $manual_staff_index) {
                        $manual_subject = $s;
                        break;
                    }
                }
<<<<<<< HEAD
                else {
                    if ($s['base_id'] == $subject_id) {
                        $manual_subject = $s;
                        break;
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
                    $days_with_labs[$day] = $manual_subject['id']; // Register lab day
=======
                    $days_with_labs[$day] = $manual_subject['id'];
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                }
                $remaining_hours[$manual_subject['id']]--;
            }
        }
    }

<<<<<<< HEAD
    // ============================================================
    // STEP 0.5: STRICT ENGLISH PLACEMENT — I B.Sc & II B.Sc (odd semester)
    // These slots are ABSOLUTE and must never change on refresh.
    // Placed BEFORE labs so nothing can ever take these slots.
    //
    // I B.Sc (sem 1):
    //   Shift 1: D1-H4, D2-H3, D3-H4, D6-H3
    //   Shift 2: D1-H1, D2-H2, D4-H2, D6-H2
    //
    // II B.Sc (sem 3):
    //   Shift 1: D1-H2, D2-H1, D3-H2, D4-H1
    //   Shift 2: D2-H4, D4-H4, D5-H3, D6-H4
    // ============================================================
    $english_subject_placed = false;
    if ($semester === 1 || $semester === 3) { // Odd semesters only — even semester slots to be configured later
        // Find English subject
=======

    // ============================================================
    // STEP 0.5b: STRICT ENGLISH PLACEMENT
    // ============================================================
    $english_subject_placed = false;
    if ($semester === 1 || $semester === 3) { 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        $english_sub = null;
        foreach ($subjects as $sub) {
            if ($sub['type'] === 'Common' && stripos($sub['title'], 'english') !== false) {
                $english_sub = $sub;
                break;
            }
        }

        if ($english_sub !== null) {
<<<<<<< HEAD
            // Define strict slots by semester and shift
            if ($semester === 1) {
                // I B.Sc
                if ($shift_group === 'shift1') {
                    $english_strict_slots = [
                        ['I DAY', 'IV HOUR'],
                        ['II DAY', 'III HOUR'],
                        ['III DAY', 'IV HOUR'],
                        ['VI DAY', 'III HOUR'],
                    ];
                } else { // shift2
                    $english_strict_slots = [
                        ['I DAY', 'I HOUR'],
                        ['II DAY', 'II HOUR'],
                        ['IV DAY', 'II HOUR'],
                        ['VI DAY', 'II HOUR'],
                    ];
                }
            } else {
                // II B.Sc (semester 3)
                if ($shift_group === 'shift1') {
                    $english_strict_slots = [
                        ['I DAY', 'II HOUR'],
                        ['II DAY', 'I HOUR'],
                        ['III DAY', 'II HOUR'],
                        ['IV DAY', 'I HOUR'],
                    ];
                } else { // shift2
                    $english_strict_slots = [
                        ['II DAY', 'IV HOUR'],
                        ['IV DAY', 'IV HOUR'],
                        ['V DAY', 'III HOUR'],
                        ['VI DAY', 'IV HOUR'],
                    ];
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
    // STEP 2: Lab allocation — using FIXED day order (not shuffled)
    // Labs must be placed BEFORE Tamil/English/Allied so their slots
    // are reserved first, allowing common subjects to find the right
    // remaining slots as shown in the reference timetable.
    // ============================================================
    // 2-hr preferred sets: IV-V first (end of day preferred)
=======
    // STEP 2: Remaining Lab allocation 
    // ============================================================
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $possible_2hr_sets_preferred = [
        ['IV HOUR', 'V HOUR'],
        ['III HOUR', 'IV HOUR'],
        ['II HOUR', 'III HOUR'],
        ['I HOUR', 'II HOUR'],
    ];
<<<<<<< HEAD
    // For 4-hr split: each half is 2 consecutive hours
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $possible_2hr_sets = [
        ['I HOUR', 'II HOUR'],
        ['II HOUR', 'III HOUR'],
        ['III HOUR', 'IV HOUR'],
        ['IV HOUR', 'V HOUR'],
    ];

    foreach ($labs_4hr as $lab) {
<<<<<<< HEAD
        if ($remaining_hours[$lab['id']] <= 0)
            continue;

        $placed4 = false;
        foreach ($lab_days_order as $day1) {
            if ($placed4)
                break;
            if (isset($days_with_labs[$day1]))
                continue;
=======
        if ($remaining_hours[$lab['id']] <= 0) continue;
        $placed4 = false;
        foreach ($lab_days_order as $day1) {
            if ($placed4) break;
            if (isset($days_with_labs[$day1])) continue;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)

            foreach ($possible_2hr_sets as $slot1_hours) {
                $can_place1 = true;
                foreach ($slot1_hours as $h) {
                    $key = $day1 . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day1, $h, $shift_group)) {
<<<<<<< HEAD
                        $can_place1 = false;
                        break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day1, $h)) {
                        $can_place1 = false;
                        break;
                    }
                }
                if (!$can_place1)
                    continue;

                // Found first half — now find second half on a different day
                foreach ($shuffled_days as $day2) {
                    if ($day2 === $day1 || isset($days_with_labs[$day2]))
                        continue;

                    foreach ($possible_2hr_sets as $slot2_hours) {
                        // Avoid same pair on both days to prevent repeating hours
                        if ($slot2_hours === $slot1_hours)
                            continue;
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                        $can_place2 = true;
                        foreach ($slot2_hours as $h) {
                            $key = $day2 . '_' . $h;
                            if (isset($used_slots[$key]) || !isLabSlotAvailable($day2, $h, $shift_group)) {
<<<<<<< HEAD
                                $can_place2 = false;
                                break;
                            }
                            if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day2, $h)) {
                                $can_place2 = false;
                                break;
                            }
                        }
                        if ($can_place2) {
                            // Place first half
=======
                                $can_place2 = false; break;
                            }
                            if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day2, $h)) {
                                $can_place2 = false; break;
                            }
                        }
                        if ($can_place2) {
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                            foreach ($slot1_hours as $h) {
                                $timetable[$day1][$h] = $lab;
                                $used_slots[$day1 . '_' . $h] = true;
                                markLabSlotOccupied($day1, $h, $shift_group);
<<<<<<< HEAD
                                if (isset($lab['staff_id']))
                                    markStaffOccupied($lab['staff_id'], $day1, $h);
                            }
                            $days_with_labs[$day1] = $lab['id'];
                            // Place second half
=======
                                if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day1, $h);
                            }
                            $days_with_labs[$day1] = $lab['id'];
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                            foreach ($slot2_hours as $h) {
                                $timetable[$day2][$h] = $lab;
                                $used_slots[$day2 . '_' . $h] = true;
                                markLabSlotOccupied($day2, $h, $shift_group);
<<<<<<< HEAD
                                if (isset($lab['staff_id']))
                                    markStaffOccupied($lab['staff_id'], $day2, $h);
=======
                                if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day2, $h);
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                            }
                            $days_with_labs[$day2] = $lab['id'];
                            $remaining_hours[$lab['id']] -= 4;
                            $placed4 = true;
<<<<<<< HEAD
                            break 3; // break slot2_hours, shuffled_days(day2), possible_2hr_sets(slot1)
=======
                            break 3; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                        }
                    }
                }
            }
        }
    }

    foreach ($labs_3hr as $lab) {
<<<<<<< HEAD
        if ($remaining_hours[$lab['id']] <= 0)
            continue;

        // 3-hr labs: first 3 hours preferred, last 3 hours as fallback only
        $possible_3hr_sets = [
            ['I HOUR', 'II HOUR', 'III HOUR'],   // PREFERRED: first 3 hours
            ['III HOUR', 'IV HOUR', 'V HOUR'],    // FALLBACK: last 3 hours only
        ];
        $placed_3hr = false;
        foreach ($lab_days_order as $day) {
            if (isset($days_with_labs[$day]))
                continue;

=======
        if ($remaining_hours[$lab['id']] <= 0) continue;
        $possible_3hr_sets = [
            ['I HOUR', 'II HOUR', 'III HOUR'],  
            ['III HOUR', 'IV HOUR', 'V HOUR'],  
        ];
        $placed_3hr = false;
        foreach ($lab_days_order as $day) {
            if (isset($days_with_labs[$day])) continue;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            foreach ($possible_3hr_sets as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
<<<<<<< HEAD
                        $can_place = false;
                        break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false;
                        break;
=======
                        $can_place = false; break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false; break;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
<<<<<<< HEAD
                        if (isset($lab['staff_id']))
                            markStaffOccupied($lab['staff_id'], $day, $h);
=======
                        if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day, $h);
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 3;
                    $placed_3hr = true;
<<<<<<< HEAD
                    break 2; // break slot_hours loop AND day loop
=======
                    break 2; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                }
            }
        }
    }

    foreach ($labs_2hr as $lab) {
<<<<<<< HEAD
        if ($remaining_hours[$lab['id']] <= 0)
            continue;

=======
        if ($remaining_hours[$lab['id']] <= 0) continue;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        $target_days = $lab_days_order;
        if ($semester >= 5 && $semester <= 6) {
            $target_days = array_intersect($lab_days_order, ['IV DAY', 'V DAY', 'VI DAY']);
        }
<<<<<<< HEAD

        $placed_2hr = false;
        foreach ($target_days as $day) {
            if (isset($days_with_labs[$day]))
                continue;

            // Prefer IV-V (end of day) for 2-hr labs
=======
        $placed_2hr = false;
        foreach ($target_days as $day) {
            if (isset($days_with_labs[$day])) continue;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            foreach ($possible_2hr_sets_preferred as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
<<<<<<< HEAD
                        $can_place = false;
                        break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false;
                        break;
=======
                        $can_place = false; break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false; break;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
<<<<<<< HEAD
                        if (isset($lab['staff_id']))
                            markStaffOccupied($lab['staff_id'], $day, $h);
=======
                        if (isset($lab['staff_id'])) markStaffOccupied($lab['staff_id'], $day, $h);
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 2;
                    $placed_2hr = true;
<<<<<<< HEAD
                    break 2; // break slot_hours AND day
=======
                    break 2; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                }
            }
        }
    }

    // ============================================================
    // STEP 0: FIXED PLACEMENT — Tamil, English (Common), Allied
<<<<<<< HEAD
    // Placed AFTER labs so lab slots are already reserved.
    // Each subject targets exact (day → preferred hour) from reference.
    // I B.Sc (sem 1-2):  Tamil Hr3/Hr4 alternating, English Hr4/Hr3 (opposite)
    //                    Allied Hr5→Hr1→Hr1→Hr5→Hr2→Hr2 across days
    // II B.Sc (sem 3-4): Tamil Hr1/Hr2 alternating, English Hr2/Hr1 (opposite)
    //                    Allied Hr4→Hr4→Hr3→Hr5→Hr4→Hr5 across days
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
        // Skip English for I B.Sc — already strictly placed in STEP 0.5
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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

<<<<<<< HEAD
        foreach ($days as $day) { // fixed day order — I→VI
            if ($hours_to_place <= 0)
                break;

=======
        foreach ($days as $day) { 
            if ($hours_to_place <= 0) break;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            $hours_to_try = ($pref_map && isset($pref_map[$day])) ? $pref_map[$day] : $hours;

            foreach ($hours_to_try as $hour) {
                $key = $day . '_' . $hour;
<<<<<<< HEAD
                if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null)
                    continue;
                if (isNMEReserved($semester, $day, $hour))
                    continue;
                if (isset($fixed_sub['staff_id']) && !isStaffAvailable($fixed_sub['staff_id'], $day, $hour))
                    continue;

                $timetable[$day][$hour] = $fixed_sub;
                $used_slots[$key] = true;
                if (isset($fixed_sub['staff_id']))
                    markStaffOccupied($fixed_sub['staff_id'], $day, $hour);
                $remaining_hours[$fixed_sub['id']]--;
                $hours_to_place--;
                break; // one slot per day
=======
                if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null) continue;
                if (isNMEReserved($semester, $day, $hour)) continue;
                if (isset($fixed_sub['staff_id']) && !isStaffAvailable($fixed_sub['staff_id'], $day, $hour)) continue;

                $timetable[$day][$hour] = $fixed_sub;
                $used_slots[$key] = true;
                if (isset($fixed_sub['staff_id'])) markStaffOccupied($fixed_sub['staff_id'], $day, $hour);
                $remaining_hours[$fixed_sub['id']]--;
                $hours_to_place--;
                break; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            }
        }
    }

    // STEP 3: Fill with regular subjects
<<<<<<< HEAD
    // Create pool of all subject instances needed
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $subject_pool = [];
    foreach ($regular_subjects as $subject) {
        for ($i = 0; $i < $subject['hours_per_week']; $i++) {
            $subject_pool[] = $subject;
        }
    }

<<<<<<< HEAD
    // Shuffle the pool for randomness
    shuffle($subject_pool);

    // Place each instance
=======
    shuffle($subject_pool);

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    foreach ($subject_pool as $subject) {
        $placed = false;
        $is_staff_subject = isset($subject['staff_id']);

<<<<<<< HEAD
        // Try all possible positions
        foreach ($shuffled_days as $day) {
            if ($placed)
                break;

            foreach ($hours as $hour) {
                $key = $day . '_' . $hour;

                if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null)
                    continue;
                if (isNMEReserved($semester, $day, $hour))
                    continue;

                if ($is_staff_subject && !isStaffAvailable($subject['staff_id'], $day, $hour)) {
                    continue;
                }

                // Staff daily load constraints:
                // 1. Max 3 hours per staff per day.
                // 2. If staff already has a Lab on this day, skip (lab day is heavy enough).
                if ($is_staff_subject) {
                    $hours_today = getStaffHoursOnDay($timetable, $subject['staff_id'], $day);
                    if ($hours_today >= 3)
                        continue;
                    if ($subject['type'] !== 'Lab' && staffHasLabOnDay($timetable, $subject['staff_id'], $day))
                        continue;
                }

                if (wouldViolateConsecutive($timetable, $day, $hour, $subject['id'])) {
                    continue;
                }

                $timetable[$day][$hour] = $subject;
                $used_slots[$key] = true;
                if ($is_staff_subject) {
                    markStaffOccupied($subject['staff_id'], $day, $hour);
                }
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                $remaining_hours[$subject['id']]--;
                $placed = true;
                break;
            }
        }
    }

    // STEP 3.5: Force fill remaining subject hours
<<<<<<< HEAD
    // If we have remaining hours, we MUST place them, even if it violates consecutive rules somewhat
    foreach ($remaining_hours as $subject_id => $hours_left) {
        if ($hours_left <= 0)
            continue;

        // Find the subject object
        $subject = null;
        foreach ($subjects as $s) {
            if ($s['id'] == $subject_id) {
                $subject = $s;
                break;
            }
        }
        if (!$subject)
            continue;

        // FIXED SUBJECTS (Common/Allied) were fully placed in STEP 0 — skip force-fill for them
        if ($subject['type'] === 'Common' || $subject['type'] === 'Allied')
            continue;
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)

        while ($remaining_hours[$subject_id] > 0) {
            $placed = false;
            $is_staff_subject_ff = isset($subject['staff_id']);
<<<<<<< HEAD
            foreach ($shuffled_days as $day) {
                if ($placed)
                    break;
                foreach ($hours as $hour) {
                    $key = $day . '_' . $hour;
                    if (isset($used_slots[$key]) || $timetable[$day][$hour] !== null)
                        continue;
                    if (isNMEReserved($semester, $day, $hour))
                        continue;

                    // Even in force fill, we must respect staff availability
                    if ($is_staff_subject_ff && !isStaffAvailable($subject['staff_id'], $day, $hour)) {
                        continue;
                    }

                    // Softer daily cap in force-fill: max 4 hrs (allow timetable to complete)
                    if ($is_staff_subject_ff) {
                        $hours_today_ff = getStaffHoursOnDay($timetable, $subject['staff_id'], $day);
                        if ($hours_today_ff >= 4)
                            continue;
                    }

                    // For Labs, we must check lab slot availability
                    if ($subject['type'] === 'Lab' && !isLabSlotAvailable($day, $hour, $shift_group)) {
                        continue;
                    }

                    // We IGNORE consecutive check here because we MUST place the hour

                    $timetable[$day][$hour] = $subject;
                    $used_slots[$key] = true;
                    if (isset($subject['staff_id'])) {
                        markStaffOccupied($subject['staff_id'], $day, $hour);
                    }
                    if ($subject['type'] === 'Lab') {
                        markLabSlotOccupied($day, $hour, $shift_group);
                    }
=======
            
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    $remaining_hours[$subject_id]--;
                    $placed = true;
                    break;
                }
            }
<<<<<<< HEAD
            if (!$placed)
                break; // Could not place even with force fill
=======
            if (!$placed) break; 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        }
    }

    // STEP 3.6: INTELLIGENT SWAPPING
<<<<<<< HEAD
    // If we still have remaining hours, it means available slots are blocked by staff unavailability.
    // We try to find a slot where our staff IS free, but it's occupied by another subject.
    // If that other subject can move to one of our empty slots, we SWAP them.
    foreach ($remaining_hours as $subject_id => $hours_left) {
        if ($hours_left <= 0)
            continue;

        // Find subject
        $subject = null;
        foreach ($subjects as $s) {
            if ($s['id'] == $subject_id) {
                $subject = $s;
                break;
            }
        }
        if (!$subject)
            continue;
=======
    foreach ($remaining_hours as $subject_id => $hours_left) {
        if ($hours_left <= 0) continue;
        $subject = null;
        foreach ($subjects as $s) {
            if ($s['id'] == $subject_id) { $subject = $s; break; }
        }
        if (!$subject) continue;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)

        $swap_attempts = 0;
        while ($remaining_hours[$subject_id] > 0 && $swap_attempts < 50) {
            $swap_attempts++;
            $swapped = false;
<<<<<<< HEAD

            // Identify Empty Slots
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            $empty_slots = [];
            foreach ($shuffled_days as $day) {
                foreach ($hours as $hour) {
                    if ($timetable[$day][$hour] === null && !isNMEReserved($semester, $day, $hour)) {
                        $empty_slots[] = ['day' => $day, 'hour' => $hour];
                    }
                }
            }

<<<<<<< HEAD
            if (empty($empty_slots))
                break; // No room to swap into

            // Look for a slot occupied by someone else, where WE can fit
            foreach ($shuffled_days as $target_day) {
                if ($swapped)
                    break;
                foreach ($hours as $target_hour) {
                    // Conditions to be a valid target for existing subject:
                    // 1. Must be occupied
                    // 2. Not NME reserved
                    // 3. Our staff must be free here
                    // 4. Ideally not a Lab (harder to move) - but we can try if needed. 

                    if ($timetable[$target_day][$target_hour] === null)
                        continue;
                    if (isNMEReserved($semester, $target_day, $target_hour))
                        continue;
                    if (isset($subject['staff_id']) && !isStaffAvailable($subject['staff_id'], $target_day, $target_hour))
                        continue;
                    if ($subject['type'] === 'Lab' && !isLabSlotAvailable($target_day, $target_hour, $shift_group))
                        continue;

                    $occupant = $timetable[$target_day][$target_hour];

                    // Don't move NME or fixed (Common/Allied) subjects
                    if (!$occupant || $occupant['type'] === 'NME' || $occupant['type'] === 'Common' || $occupant['type'] === 'Allied')
                        continue;

                    // Now check if Occupant can move to ANY empty slot
                    foreach ($empty_slots as $empty) {
                        $e_day = $empty['day'];
                        $e_hour = $empty['hour'];

                        // Can Occupant go to Empty Slot?
                        if (isset($occupant['staff_id']) && !isStaffAvailable($occupant['staff_id'], $e_day, $e_hour))
                            continue;
                        if ($occupant['type'] === 'Lab' && !isLabSlotAvailable($e_day, $e_hour, $shift_group))
                            continue;

                        // Perform Swap
                        // 1. Move Occupant to Empty
                        $timetable[$e_day][$e_hour] = $occupant;
                        $used_slots[$e_day . '_' . $e_hour] = true;
                        if (isset($occupant['staff_id']))
                            markStaffOccupied($occupant['staff_id'], $e_day, $e_hour);
                        if ($occupant['type'] === 'Lab')
                            markLabSlotOccupied($e_day, $e_hour, $shift_group);

                        // 2. Free up Target Slot
=======
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

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                        if (isset($occupant['staff_id'])) {
                            unset($GLOBALS['staff_schedule'][$target_day . '_' . $target_hour][$occupant['staff_id']]);
                        }
                        if ($occupant['type'] === 'Lab') {
                            unset($GLOBALS['lab_schedule'][$shift_group][$target_day . '_' . $target_hour]);
                        }

<<<<<<< HEAD
                        // 3. Place Us in Target
                        $timetable[$target_day][$target_hour] = $subject;
                        // $used_slots already true
                        if (isset($subject['staff_id']))
                            markStaffOccupied($subject['staff_id'], $target_day, $target_hour);
                        if ($subject['type'] === 'Lab')
                            markLabSlotOccupied($target_day, $target_hour, $shift_group);
=======
                        $timetable[$target_day][$target_hour] = $subject;
                        if (isset($subject['staff_id'])) markStaffOccupied($subject['staff_id'], $target_day, $target_hour);
                        if ($subject['type'] === 'Lab') markLabSlotOccupied($target_day, $target_hour, $shift_group);
>>>>>>> 0f15a3a (Updated Timetable Generation Project)

                        $remaining_hours[$subject_id]--;
                        $swapped = true;
                        break;
                    }
<<<<<<< HEAD
                    if ($swapped)
                        break;
                }
            }
            if (!$swapped)
                break;
=======
                    if ($swapped) break;
                }
            }
            if (!$swapped) break;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        }
    }

    return ['timetable' => $timetable];
}

<<<<<<< HEAD
// Process each class
while ($class = $classes->fetch_assoc()) {
    // Determine semester
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

    // Determine program and shift group
=======
$classes = $conn->query($classes_query);
$classes_array = [];
while ($row = $classes->fetch_assoc()) {
    $classes_array[] = $row;
}

$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

$global_max_attempts = 100;
$valid_global_timetable = false;
$best_timetables = null;

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

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
    $program = (strpos($class['name'], 'M.Sc') !== false) ? 'PG' : 'UG';
    if ($program === 'PG' || strpos($class['shift'], 'Shift 1') !== false) {
        $shift_group = 'shift1';
    }
    else {
        $shift_group = 'shift2';
    }

<<<<<<< HEAD
    $subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $semester AND is_allocated = 1 ORDER BY 
=======
    $subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $semester AND COALESCE(is_allocated, 1) = 1 ORDER BY 
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
        // Check for B.Sc shift-based allocation first
        $shift_key = null;
        if (strpos($class['name'], 'B.Sc') !== false) {
            // For B.Sc - check shift-specific keys
=======
        $shift_key = null;
        if (strpos($class['name'], 'B.Sc') !== false) {
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            if (strpos($class['shift'], 'Shift 1') !== false) {
                $shift_key = 'staff_shift1_' . $subject['id'];
            }
            elseif (strpos($class['shift'], 'Shift 2') !== false) {
                $shift_key = 'staff_shift2_' . $subject['id'];
            }
        }

<<<<<<< HEAD
        // Try shift key first, then fallback to standard key
        $base_staff_key = $shift_key ? $shift_key : 'staff_' . $subject['id'] . '_' . $class['id'];

        // Find all staff assigned to this subject (handling split staff)
        $staff_assigned = [];

        // Check for up to 10 split staff members
        for ($index = 1; $index <= 10; $index++) {
            if ($shift_key) {
                $check_key = $base_staff_key . '_' . $index;
            }
            else {
                $check_key = 'staff_' . $subject['id'] . '_' . $index;
            }

            // The first staff dropdown is natively named without the _1 suffix
=======
        $base_staff_key = $shift_key ? $shift_key : 'staff_' . $subject['id'] . '_' . $class['id'];
        $staff_assigned = [];

        for ($index = 1; $index <= 10; $index++) {
            if ($shift_key) {
                $check_key = $base_staff_key . '_' . $index;
            } else {
                $check_key = 'staff_' . $subject['id'] . '_' . $index;
            }

>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
                    // Provide staff index natively so split classes don't conflict later
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
                    'staff_index' => $index
                ];
            }
        }

<<<<<<< HEAD
        // Base Subject Short Name
        $subject['short_name'] = getShortName($subject['title'], $subject['type'], $semester);

        // If staff were assigned, create split objects for each staff
        if (!empty($staff_assigned)) {
            foreach ($staff_assigned as $assignment) {
                if ($assignment['hours'] <= 0)
                    continue; // Skip 0 hour assignments

                $split_subject = $subject; // Copy base object
                $split_subject['hours_per_week'] = $assignment['hours'];
                $split_subject['staff_index'] = $assignment['staff_index'];
                $split_subject['base_id'] = $subject['id']; // Track real ID
                if ($assignment['staff_index'] !== null) {
                    $split_subject['id'] = $subject['id'] . '_' . $assignment['staff_index']; // Make unique ID for internal tracking
=======
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
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
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
<<<<<<< HEAD
            // No staff assigned (either unmarked, or marked "No Staff Required")
            // Make sure these are still pushed into the pool so they appear on the timetable
            $subject['base_id'] = $subject['id'];
            $subject['staff_index'] = null;
            // Name mapping (staff omitted intentionally)
=======
            $subject['base_id'] = $subject['id'];
            $subject['staff_index'] = null;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
            $subjects[] = $subject;
        }
    }

<<<<<<< HEAD
    // Generate timetable — only returns gap-free result, or null if impossible
    $timetable = generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, 300);

    if ($timetable !== null) {
        // Gap-free result
=======
    $timetable = generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, 300);

    if ($timetable !== null) {
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        $timetables[] = [
            'class'    => $class,
            'semester' => $semester,
            'timetable'=> $timetable,
        ];
    }
    else {
<<<<<<< HEAD
        // No gap-free arrangement found — record with warning flag
=======
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
        $timetables[] = [
            'class'    => $class,
            'semester' => $semester,
            'timetable'=> null,
            'has_gaps' => true,
        ];
<<<<<<< HEAD
    }
}

// Store timetables in session for view
$_SESSION['current_timetables'] = $timetables;
$_SESSION['semester_filter'] = $semester_filter;
$_SESSION['current_page'] = 'generated_timetable_view';

// Redirect to the new view page
header("Location: generated_timetable_view.php");
exit;
=======
        $local_has_gaps = true;
    }
    } // End foreach ($classes_array as $class)

    // Verify global staff constraints
    $constraint_failed = false;
    
    if (!$local_has_gaps) {
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
        
        // Ensure staff with >= 6 hours have at least 1 hr on all 6 working days
        foreach ($staff_total_hours as $sid => $total) {
            if ($total >= 6) {
                if (count($staff_daily_presence[$sid]) < 6) {
                    $constraint_failed = true;
                    break;
                }
            }
        }
    }

    if (!$local_has_gaps && !$constraint_failed) {
        $valid_global_timetable = true;
        $best_timetables = $timetables;
        break;
    }
} // End global_attempt loop

if ($valid_global_timetable) {
    $_SESSION['current_timetables'] = $best_timetables;
} else {
    $_SESSION['current_timetables'] = [];
    $_SESSION['generation_error'] = "Could not generate a full timetable satisfying all constraints (e.g., no staff double-bookings, daily minimum hours) after $global_max_attempts attempts.";
}

$_SESSION['semester_filter'] = $semester_filter;
$_SESSION['current_page'] = 'generated_timetable_view';

header("Location: generated_timetable_view.php");
exit;
>>>>>>> 0f15a3a (Updated Timetable Generation Project)
