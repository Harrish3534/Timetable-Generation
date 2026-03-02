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
        } else {
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

    return $consecutive > 2;
}

function hasGaps($timetable, $days, $hours, $semester)
{
    foreach ($days as $day) {
        foreach ($hours as $hour) {
            // Skip NME reserved slots that might legitimately be empty
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

// Generate timetable for a single class with multiple attempts
function generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, $max_attempts = 200)
{
    global $staff_schedule, $lab_schedule;

    $best_timetable = null;
    $min_gaps = PHP_INT_MAX;

    for ($attempt = 0; $attempt < $max_attempts; $attempt++) {
        // Save global state
        $saved_staff_schedule = $staff_schedule;
        $saved_lab_schedule = $lab_schedule;

        // Try to generate a timetable
        $result = generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group);

        // Check for gaps
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

        // If no gaps, return immediately
        if ($gap_count === 0) {
            return $result['timetable'];
        }

        // Track best attempt
        if ($gap_count < $min_gaps) {
            $min_gaps = $gap_count;
            $best_timetable = $result['timetable'];
        }

        // Restore global state for next attempt
        $staff_schedule = $saved_staff_schedule;
        $lab_schedule = $saved_lab_schedule;
    }

    return $best_timetable;
}

function generateSingleAttempt($subjects, $days, $hours, $semester, $shift_group)
{
    global $staff_schedule, $lab_schedule;

    // Initialize timetable
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

    foreach ($subjects as $subject) {
        $remaining_hours[$subject['id']] = $subject['hours_per_week'];
    }

    // Categorize subjects
    $labs_4hr = [];
    $labs_3hr = [];
    $labs_2hr = [];
    $nme_subjects = [];
    $fixed_subjects = [];  // Tamil (Common), English (Common), Allied — NEVER shuffled
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
        } elseif ($subject['type'] === 'NME' || ($semester >= 5 && $semester <= 6 && stripos($subject['title'], 'elective') !== false)) {
            $nme_subjects[] = $subject;
        } elseif ($subject['type'] === 'Common' || $subject['type'] === 'Allied') {
            // Fixed subjects: Tamil, English (Common) and Allied — placed at fixed positions
            $fixed_subjects[] = $subject;
        } else {
            $regular_subjects[] = $subject;
        }
    }

    // Shuffle ONLY core/regular subjects — fixed subjects NEVER shuffled
    shuffle($labs_4hr);
    shuffle($labs_3hr);
    shuffle($labs_2hr);
    shuffle($regular_subjects);

    // Labs use $days (fixed order) so common subjects always land same slots each regeneration
    $lab_days_order = $days;       // fixed order for lab placement
    $shuffled_days = $days;
    shuffle($shuffled_days);       // core subjects still vary

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
        $english_sub = null;
        foreach ($subjects as $sub) {
            if ($sub['type'] === 'Common' && stripos($sub['title'], 'english') !== false) {
                $english_sub = $sub;
                break;
            }
        }

        if ($english_sub !== null) {
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
    // STEP 1: NME — always Days 1-3, Hour 4 (III B.Sc only)
    // ============================================================
    if ($semester >= 5 && $semester <= 6) {
        $nme_days = ['I DAY', 'II DAY', 'III DAY'];
        $nme_hour = 'IV HOUR';
        foreach ($nme_days as $day)
            $used_slots[$day . '_' . $nme_hour] = true;
        if (count($nme_subjects) > 0) {
            $nme_subject = $nme_subjects[0];
            foreach ($nme_days as $day) {
                $timetable[$day][$nme_hour] = $nme_subject;
                if (isset($nme_subject['staff_id']))
                    markStaffOccupied($nme_subject['staff_id'], $day, $nme_hour);
            }
            $remaining_hours[$nme_subject['id']] -= 3;
        }
    }

    // ============================================================
    // STEP 2: Lab allocation — using FIXED day order (not shuffled)
    // Labs must be placed BEFORE Tamil/English/Allied so their slots
    // are reserved first, allowing common subjects to find the right
    // remaining slots as shown in the reference timetable.
    // ============================================================
    $possible_2hr_sets = [
        ['I HOUR', 'II HOUR'],
        ['II HOUR', 'III HOUR'],
        ['III HOUR', 'IV HOUR'],
        ['IV HOUR', 'V HOUR'],
    ];

    foreach ($labs_4hr as $lab) {
        $placed4 = false;
        foreach ($lab_days_order as $day1) {
            if ($placed4)
                break;
            if (isset($days_with_labs[$day1]))
                continue;

            foreach ($possible_2hr_sets as $slot1_hours) {
                $can_place1 = true;
                foreach ($slot1_hours as $h) {
                    $key = $day1 . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day1, $h, $shift_group)) {
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
                        $can_place2 = true;
                        foreach ($slot2_hours as $h) {
                            $key = $day2 . '_' . $h;
                            if (isset($used_slots[$key]) || !isLabSlotAvailable($day2, $h, $shift_group)) {
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
                            foreach ($slot1_hours as $h) {
                                $timetable[$day1][$h] = $lab;
                                $used_slots[$day1 . '_' . $h] = true;
                                markLabSlotOccupied($day1, $h, $shift_group);
                                if (isset($lab['staff_id']))
                                    markStaffOccupied($lab['staff_id'], $day1, $h);
                            }
                            $days_with_labs[$day1] = $lab['id'];
                            // Place second half
                            foreach ($slot2_hours as $h) {
                                $timetable[$day2][$h] = $lab;
                                $used_slots[$day2 . '_' . $h] = true;
                                markLabSlotOccupied($day2, $h, $shift_group);
                                if (isset($lab['staff_id']))
                                    markStaffOccupied($lab['staff_id'], $day2, $h);
                            }
                            $days_with_labs[$day2] = $lab['id'];
                            $remaining_hours[$lab['id']] -= 4;
                            $placed4 = true;
                            break 3; // break slot2_hours, shuffled_days(day2), possible_2hr_sets(slot1)
                        }
                    }
                }
            }
        }
    }

    foreach ($labs_3hr as $lab) {
        // Try multiple consecutive 3-hour sets so lab can go around fixed common slots
        $possible_3hr_sets = [
            ['I HOUR', 'II HOUR', 'III HOUR'],
            ['II HOUR', 'III HOUR', 'IV HOUR'],
            ['III HOUR', 'IV HOUR', 'V HOUR'],
        ];
        foreach ($lab_days_order as $day) {
            if (isset($days_with_labs[$day]))
                continue;

            $placed = false;
            foreach ($possible_3hr_sets as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
                        $can_place = false;
                        break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false;
                        break;
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
                        if (isset($lab['staff_id']))
                            markStaffOccupied($lab['staff_id'], $day, $h);
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 3;
                    $placed = true;
                    break;
                }
            }
            if ($placed)
                break;
        }
    }

    foreach ($labs_2hr as $lab) {
        $target_days = $lab_days_order;
        if ($semester >= 5 && $semester <= 6) {
            $target_days = array_intersect($lab_days_order, ['IV DAY', 'V DAY', 'VI DAY']);
        }

        foreach ($target_days as $day) {
            if (isset($days_with_labs[$day]))
                continue;

            $placed = false;
            foreach ($possible_2hr_sets as $slot_hours) {
                $can_place = true;
                foreach ($slot_hours as $h) {
                    $key = $day . '_' . $h;
                    if (isset($used_slots[$key]) || !isLabSlotAvailable($day, $h, $shift_group)) {
                        $can_place = false;
                        break;
                    }
                    if (isset($lab['staff_id']) && !isStaffAvailable($lab['staff_id'], $day, $h)) {
                        $can_place = false;
                        break;
                    }
                }
                if ($can_place) {
                    foreach ($slot_hours as $h) {
                        $timetable[$day][$h] = $lab;
                        $used_slots[$day . '_' . $h] = true;
                        markLabSlotOccupied($day, $h, $shift_group);
                        if (isset($lab['staff_id']))
                            markStaffOccupied($lab['staff_id'], $day, $h);
                    }
                    $days_with_labs[$day] = $lab['id'];
                    $remaining_hours[$lab['id']] -= 2;
                    $placed = true;
                    break;
                }
            }
            if ($placed)
                break;
        }
    }

    // ============================================================
    // STEP 0: FIXED PLACEMENT — Tamil, English (Common), Allied
    // Placed AFTER labs so lab slots are already reserved.
    // Each subject targets exact (day → preferred hour) from reference.
    // I B.Sc (sem 1-2):  Tamil Hr3/Hr4 alternating, English Hr4/Hr3 (opposite)
    //                    Allied Hr5→Hr1→Hr1→Hr5→Hr2→Hr2 across days
    // II B.Sc (sem 3-4): Tamil Hr1/Hr2 alternating, English Hr2/Hr1 (opposite)
    //                    Allied Hr4→Hr4→Hr3→Hr5→Hr4→Hr5 across days
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

        // Skip English for I B.Sc — already strictly placed in STEP 0.5
        if ($english_subject_placed && stripos($title_lower, 'english') !== false) {
            continue;
        }

        if ($sub_type === 'Allied') {
            $pref_map = $fixed_preferred['Allied'][$semester] ?? null;
        } elseif (stripos($title_lower, 'tamil') !== false) {
            $pref_map = $fixed_preferred['Tamil'][$semester] ?? null;
        } elseif (stripos($title_lower, 'english') !== false) {
            $pref_map = $fixed_preferred['English'][$semester] ?? null;
        } else {
            $pref_map = null;
        }

        $hours_to_place = $fixed_sub['hours_per_week'];

        foreach ($days as $day) {       // fixed day order — I→VI
            if ($hours_to_place <= 0)
                break;

            $hours_to_try = ($pref_map && isset($pref_map[$day])) ? $pref_map[$day] : $hours;

            foreach ($hours_to_try as $hour) {
                $key = $day . '_' . $hour;
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
            }
        }
    }

    // STEP 3: Fill with regular subjects
    // Create pool of all subject instances needed
    $subject_pool = [];
    foreach ($regular_subjects as $subject) {
        for ($i = 0; $i < $subject['hours_per_week']; $i++) {
            $subject_pool[] = $subject;
        }
    }

    // Shuffle the pool for randomness
    shuffle($subject_pool);

    // Place each instance
    foreach ($subject_pool as $subject) {
        $placed = false;

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

                if (isset($subject['staff_id']) && !isStaffAvailable($subject['staff_id'], $day, $hour)) {
                    continue;
                }

                if (wouldViolateConsecutive($timetable, $day, $hour, $subject['id'])) {
                    continue;
                }

                $timetable[$day][$hour] = $subject;
                $used_slots[$key] = true;
                if (isset($subject['staff_id'])) {
                    markStaffOccupied($subject['staff_id'], $day, $hour);
                }
                $remaining_hours[$subject['id']]--;
                $placed = true;
                break;
            }
        }
    }

    // STEP 3.5: Force fill remaining subject hours
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

        while ($remaining_hours[$subject_id] > 0) {
            $placed = false;
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
                    if (isset($subject['staff_id']) && !isStaffAvailable($subject['staff_id'], $day, $hour)) {
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
                    $remaining_hours[$subject_id]--;
                    $placed = true;
                    break;
                }
            }
            if (!$placed)
                break; // Could not place even with force fill
        }
    }

    // STEP 3.6: INTELLIGENT SWAPPING
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

        while ($remaining_hours[$subject_id] > 0) {
            $swapped = false;

            // Identify Empty Slots
            $empty_slots = [];
            foreach ($shuffled_days as $day) {
                foreach ($hours as $hour) {
                    if ($timetable[$day][$hour] === null && !isNMEReserved($semester, $day, $hour)) {
                        $empty_slots[] = ['day' => $day, 'hour' => $hour];
                    }
                }
            }

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
                    if ($occupant['type'] === 'NME' || $occupant['type'] === 'Common' || $occupant['type'] === 'Allied')
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

                        // 2. Free up Target Slot (logical, though we overwrite immediately)
                        // Note: We don't "unmark" the target slot's old staff usage because we are replacing it immediately
                        // Wait, we MUST unmark occupant from Target Slot to be clean, 
                        // BUT MarkStaffOccupied adds to array. It doesn't remove. 
                        // Our system doesn't have "unmark". 
                        // However, isStaffAvailable checks `!isset`.
                        // Since we are moving Occupant to E_Day, they are now busy there.
                        // They WERE busy at Target_Day. We are effectively moving that 'busy-ness'.
                        // But we can't delete the old key from global array easily without `unmark` function.
                        // FORTUNATELY: The `generateTimetableForClass` uses a localized global state approach (copies).
                        // BUT `staff_schedule` is global.
                        // WE NEED TO MANUALLY UNSET THE OLD KEY FOR THE OCCUPANT?
                        // `unset($staff_schedule[$target_day.'_'.$target_hour][$occupant['staff_id']])`

                        if (isset($occupant['staff_id'])) {
                            unset($GLOBALS['staff_schedule'][$target_day . '_' . $target_hour][$occupant['staff_id']]);
                        }
                        if ($occupant['type'] === 'Lab') {
                            unset($GLOBALS['lab_schedule'][$shift_group][$target_day . '_' . $target_hour]);
                        }

                        // 3. Place Us in Target
                        $timetable[$target_day][$target_hour] = $subject;
                        // $used_slots already true
                        if (isset($subject['staff_id']))
                            markStaffOccupied($subject['staff_id'], $target_day, $target_hour);
                        if ($subject['type'] === 'Lab')
                            markLabSlotOccupied($target_day, $target_hour, $shift_group);

                        $remaining_hours[$subject_id]--;
                        $swapped = true;
                        break;
                    }
                    if ($swapped)
                        break;
                }
            }
            if (!$swapped)
                break;
        }
    }

    return ['timetable' => $timetable];
}

// Process each class
while ($class = $classes->fetch_assoc()) {
    // Determine semester
    if (strpos($class['name'], 'I ') === 0) {
        $semester = $semester_filter == 'odd' ? 1 : 2;
    } elseif (strpos($class['name'], 'II ') === 0) {
        $semester = $semester_filter == 'odd' ? 3 : 4;
    } elseif (strpos($class['name'], 'III ') === 0) {
        $semester = $semester_filter == 'odd' ? 5 : 6;
    } else {
        $semester = 1;
    }

    // Determine program and shift group
    $program = (strpos($class['name'], 'M.Sc') !== false) ? 'PG' : 'UG';
    if ($program === 'PG' || strpos($class['shift'], 'Shift 1') !== false) {
        $shift_group = 'shift1';
    } else {
        $shift_group = 'shift2';
    }

    // Get subjects
    $subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $semester ORDER BY 
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
        // Check for B.Sc shift-based allocation first
        $shift_key = null;
        if (strpos($class['name'], 'B.Sc') !== false) {
            // For B.Sc - check shift-specific keys
            if (strpos($class['shift'], 'Shift 1') !== false) {
                $shift_key = 'staff_shift1_' . $subject['id'];
            } elseif (strpos($class['shift'], 'Shift 2') !== false) {
                $shift_key = 'staff_shift2_' . $subject['id'];
            }
        }

        // Try shift key first, then fallback to standard key
        $staff_key = $shift_key ? $shift_key : 'staff_' . $subject['id'] . '_' . $class['id'];

        if (isset($staff_allocations[$staff_key]) && !empty($staff_allocations[$staff_key])) {
            $staff_id = $staff_allocations[$staff_key];
            $staff_query = "SELECT name, short_code FROM staff WHERE id = $staff_id";
            $staff_result = $conn->query($staff_query);
            if ($staff_result && $staff_row = $staff_result->fetch_assoc()) {
                $subject['staff_name'] = $staff_row['name'];
                $subject['staff_code'] = $staff_row['short_code'];
                $subject['staff_id'] = $staff_id;
            }
        }
        $subject['short_name'] = getShortName($subject['title'], $subject['type'], $semester);
        $subjects[] = $subject;
    }

    // Generate timetable with multiple attempts
    $timetable = generateTimetableForClass($class, $subjects, $days, $hours, $semester, $shift_group, 200);

    // Only add if no gaps (or add anyway if we couldn't find better)
    if ($timetable && !hasGaps($timetable, $days, $hours, $semester)) {
        $timetables[] = [
            'class' => $class,
            'semester' => $semester,
            'timetable' => $timetable
        ];
    } else {
        // Still add but with warning
        $timetables[] = [
            'class' => $class,
            'semester' => $semester,
            'timetable' => $timetable,
            'has_gaps' => true
        ];
    }
}

// Store timetables in session for staff timetable view
$_SESSION['current_timetables'] = $timetables;
$_SESSION['semester_filter'] = $semester_filter;
$_SESSION['current_page'] = 'generate_timetable';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Timetable - GAC Timetable</title>
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

        .warning-banner {
            background-color: #fef3c7;
            border: 2px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            color: #92400e;
            font-weight: 600;
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
            <button onclick="window.location.href='timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">← Back</button>
            <button onclick="window.location.reload()" class="btn btn-secondary">🔄 Regenerate</button>
            <button onclick="window.location.href='staff_timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">👥 Staff Timetable</button>
        </div>

        <?php foreach ($timetables as $tt_data): ?>
            <div class="timetable-container">

                <div class="timetable-title">
                    <h2>Government Arts College (Autonomous), Coimbatore-18</h2>
                    <h3>PG & Research Department of Computer Science</h3>
                    <p>Time Table 2025-26 <?php echo ucfirst($semester_filter); ?> Semester</p>
                    <p><strong><?php echo htmlspecialchars($tt_data['class']['name']); ?>
                            (<?php echo htmlspecialchars($tt_data['class']['shift']); ?>)</strong> - Semester
                        <?php echo $tt_data['semester']; ?>
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
                                <td class="day-header"><?php echo $day; ?></td>
                                <?php foreach ($hours as $hour): ?>
                                    <td class="subject-cell">
                                        <?php if (isset($tt_data['timetable'][$day][$hour]) && $tt_data['timetable'][$day][$hour]):
                                            $subject = $tt_data['timetable'][$day][$hour];
                                            ?>
                                            <div class="subject-display">
                                                <?php echo htmlspecialchars($subject['short_name']); ?>
                                                <?php if (isset($subject['staff_code'])): ?>
                                                    (<?php echo htmlspecialchars($subject['staff_code']); ?>)
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
                <?php if (isset($tt_data['has_gaps']) && $tt_data['has_gaps']): ?>
                    <div class="warning-banner no-print">
                        ⚠️ Warning: Some cells cannot be filled because of staff allocation.
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>
<?php $conn->close(); ?>