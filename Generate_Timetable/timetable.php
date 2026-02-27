<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

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

// Clear current page marker
unset($_SESSION['current_page']);

// Handle semester selection
if (isset($_GET['semester'])) {
    $_SESSION['semester_filter'] = $_GET['semester'];
    $_SESSION['current_class_index'] = 0;
    header("Location: timetable.php");
    exit;
}

// Handle reset
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $_SESSION['staff_allocations'] = [];
    $_SESSION['hours_changes'] = [];
    $_SESSION['current_class_index'] = 0;
    header("Location: timetable.php");
    exit;
}

// Handle navigation
if (isset($_POST['action']) && $_POST['action'] === 'next') {
    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0 && !empty($value)) {
            $_SESSION['staff_allocations'][$key] = $value;
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    $_SESSION['current_class_index']++;
    header("Location: timetable.php");
    exit;
}

if (isset($_POST['action']) && $_POST['action'] === 'previous') {
    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0 && !empty($value)) {
            $_SESSION['staff_allocations'][$key] = $value;
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
    }
    
    $_SESSION['current_class_index']--;
    if ($_SESSION['current_class_index'] < 0) {
        $_SESSION['current_class_index'] = 0;
    }
    header("Location: timetable.php");
    exit;
}

// Handle final submission
if (isset($_POST['action']) && $_POST['action'] === 'generate') {
    // Save current page data
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'staff_') === 0 && !empty($value)) {
            $_SESSION['staff_allocations'][$key] = $value;
        }
        if (strpos($key, 'hours_') === 0) {
            $_SESSION['hours_changes'][$key] = intval($value);
        }
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
$subjects_query = "SELECT * FROM subjects WHERE program = '$program' AND semester = $current_semester ORDER BY sort_order, id";
$subjects_result = $conn->query($subjects_query);
$subjects = [];
while ($subject = $subjects_result->fetch_assoc()) {
    $subjects[] = $subject;
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

// Calculate hours used by each staff from current allocations
$staff_hours_used = [];
if (isset($_SESSION['staff_allocations'])) {
    foreach ($_SESSION['staff_allocations'] as $key => $staff_id) {
        if (empty($staff_id)) continue;
        
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
            // Determine the actual hours for this specific allocation
            $subject_hours = 0;
            
            // Check if this is a split allocation (has staff_index)
            if ($staff_index !== null && $staff_index > 0) {
                // Look for split hours: hours_{shift}_{subject_id}_{index} or hours_{subject_id}_{index}
                $split_hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id . '_' . $staff_index : 'hours_' . $subject_id . '_' . $staff_index;
                
                if (isset($_SESSION['hours_changes'][$split_hours_key])) {
                    $subject_hours = $_SESSION['hours_changes'][$split_hours_key];
                } else {
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
            } else {
                // No index in key, but check if split hours exist (first staff in split allocation)
                // The first staff's key is staff_shift1_123 but split hours are hours_shift1_123_1
                $split_hours_key_1 = $shift ? 'hours_' . $shift . '_' . $subject_id . '_1' : 'hours_' . $subject_id . '_1';
                
                if (isset($_SESSION['hours_changes'][$split_hours_key_1])) {
                    // Split hours exist, use the first split hours
                    $subject_hours = $_SESSION['hours_changes'][$split_hours_key_1];
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
    
    if (count($classes) >= 1) $shift1_class = $classes[0];
    if (count($classes) >= 2) $shift2_class = $classes[1];
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
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
        
        .type-core { background: #dbeafe; color: #1e40af; }
        .type-lab { background: #fce7f3; color: #9f1239; }
        .type-allied { background: #d1fae5; color: #065f46; }
        .type-common { background: #fef3c7; color: #92400e; }
        .type-nme { background: #e9d5ff; color: #6b21a8; }
        
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
    </style>
    <script>
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
                    if (potentialIndex <= 10) {
                        staffIndex = potentialIndex;
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
            }
            
            // Check if split hours exist even without index (first staff in split allocation)
            // When adding second staff, the first staff's select remains staff_shift1_123 but hours become hours_shift1_123_1
            const splitHoursName1 = shift ? `hours_${shift}_${subjectId}_1` : `hours_${subjectId}_1`;
            const splitHoursInput1 = document.querySelector(`[name="${splitHoursName1}"]`);
            if (splitHoursInput1) {
                // Split hours exist, this is the first staff in a split allocation
                console.log(`[DEBUG] ${selectName} -> ${splitHoursName1} = ${splitHoursInput1.value} hours (first split)`);
                return parseInt(splitHoursInput1.value) || 0;
            }
            
            // Otherwise, get the main hours input (not split)
            const hoursInputName = shift ? `hours_${shift}_${subjectId}` : `hours_${subjectId}`;
            const hoursInput = document.querySelector(`[name="${hoursInputName}"]`);
            const hours = hoursInput ? parseInt(hoursInput.value) || 0 : (subjectHoursData[subjectId] || 0);
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
                
                // Update each option
                Array.from(select.options).forEach(option => {
                    if (option.value === '') return; // Skip "Select Staff"
                    
                    const staffId = parseInt(option.value);
                    const baseUsed = staffHoursData[staffId].used || 0;
                    // Subtract original page hours (already in baseUsed) and add current page hours
                    const originalPageHours = originalHoursUsed[staffId] || 0;
                    const currentPageUsed = tempHoursUsed[staffId] || 0;
                    const totalUsed = baseUsed - originalPageHours + currentPageUsed;
                    const remaining = staffHoursData[staffId].max - totalUsed;
                    
                    // Get staff name and code from original option text
                    const originalText = option.getAttribute('data-original-text');
                    if (!originalText) {
                        option.setAttribute('data-original-text', option.textContent);
                    }
                    
                    // Update option text with remaining hours
                    const nameCode = option.getAttribute('data-original-text') || option.textContent.split(' : ')[0];
                    
                    // Don't disable an option if it's currently selected in THIS dropdown
                    const isCurrentSelection = (option.value === currentValue);
                    
                    if (remaining > 0 || isCurrentSelection) {
                        if (remaining > 0) {
                            option.textContent = `${nameCode} : ${remaining} hrs remaining`;
                        } else {
                            // Currently selected but has 0 hours remaining (for display purposes)
                            option.textContent = `${nameCode} : ${remaining} hrs remaining`;
                        }
                        option.disabled = false;
                        option.style.color = remaining < 5 ? '#dc2626' : '#000';
                    } else {
                        option.textContent = `${nameCode} : No hours available`;
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
            if (!staffId) return true;
            
            // Find which split hours input corresponds to this staff selection
            // by looking at the staff container and counting which position this select is in
            const containerId = shift ? `staff-container-${shift}-${subjectId}` : `staff-container-${subjectId}`;
            const container = document.getElementById(containerId);
            
            let subjectHours = 0;
            let staffPosition = 1; // Default to first position
            
            if (container) {
                // Find which position this select is in
                const allSelects = container.querySelectorAll('.staff-select');
                for (let i = 0; i < allSelects.length; i++) {
                    if (allSelects[i] === select) {
                        staffPosition = i + 1; // 1-indexed
                        break;
                    }
                }
                
                // Try to find split hours input for this position
                const splitHoursInputName = shift ? `hours_${shift}_${subjectId}_${staffPosition}` : `hours_${subjectId}_${staffPosition}`;
                const splitHoursInput = document.querySelector(`[name="${splitHoursInputName}"]`);
                
                if (splitHoursInput) {
                    // Split hours exist for this position
                    subjectHours = parseInt(splitHoursInput.value) || 0;
                } else {
                    // No split hours for this specific position, try position 1 (might be first staff)
                    const splitHoursInput1 = shift ? document.querySelector(`[name="hours_${shift}_${subjectId}_1"]`) : document.querySelector(`[name="hours_${subjectId}_1"]`);
                    if (splitHoursInput1 && staffPosition === 1) {
                        // First staff and split hours exist
                        subjectHours = parseInt(splitHoursInput1.value) || 0;
                    } else {
                        // No split hours at all, use main hours
                        const mainHoursInput = shift ? document.querySelector(`[name="hours_${shift}_${subjectId}"]`) : document.querySelector(`[name="hours_${subjectId}"]`);
                        subjectHours = mainHoursInput ? parseInt(mainHoursInput.value) : 0;
                    }
                }
            } else {
                // Container not found, use main hours
                const mainHoursInput = shift ? document.querySelector(`[name="hours_${shift}_${subjectId}"]`) : document.querySelector(`[name="hours_${subjectId}"]`);
                subjectHours = mainHoursInput ? parseInt(mainHoursInput.value) : 0;
            }
            
            // Calculate current remaining hours using same logic as updateStaffDropdowns
            let tempHoursUsed = {};
            let originalHoursUsed = {};
            
            // Count original page hours
            for (const key in originalPageAllocations) {
                if (key === select.name) continue; // Skip current selection's original
                const allocStaffId = originalPageAllocations[key].staffId;
                const allocSubjectId = originalPageAllocations[key].subjectId;
                const allocShift = originalPageAllocations[key].shift;
                
                if (!originalHoursUsed[allocStaffId]) {
                    originalHoursUsed[allocStaffId] = 0;
                }
                // Get actual allocated hours instead of total subject hours
                const allocatedHours = getActualAllocationHours(key, allocSubjectId, allocShift);
                originalHoursUsed[allocStaffId] += allocatedHours;
            }
            
            // Count current page hours
            for (const key in currentAllocations) {
                if (key === select.name) continue; // Skip current selection
                const allocStaffId = currentAllocations[key].staffId;
                const allocSubjectId = currentAllocations[key].subjectId;
                const allocShift = currentAllocations[key].shift;
                
                if (!tempHoursUsed[allocStaffId]) {
                    tempHoursUsed[allocStaffId] = 0;
                }
                
                // Get actual allocated hours instead of total subject hours
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
                select.setCustomValidity(`This staff does not have enough hours for allocation (Needs ${subjectHours} hrs, Has ${remaining} hrs remaining)`);
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
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Add change listeners to hour inputs for total hours update
            document.querySelectorAll('.hours-input').forEach(input => {
                input.addEventListener('input', updateTotalHours);
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
            
            // Replace with two inputs
            hoursCell.innerHTML = `
                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
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
            const currentTotal = hiddenInput ? parseInt(hiddenInput.value) : 0;
            
            // Restore original state
            const cacheKey = shift ? `${subjectId}_${shift}` : subjectId;
            if (hoursContainerCache[cacheKey]) {
                hoursCell.innerHTML = hoursContainerCache[cacheKey].html;
                // Update the value to current total
                hoursCell.querySelector(`[name="${hoursInputName}"]`).value = currentTotal;
                // Re-attach event listener
                hoursCell.querySelector(`[name="${hoursInputName}"]`).addEventListener('input', updateTotalHours);
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
                input.onchange = function() { updateSplitHoursTotal(subjectId, shift); };
                splitContainer.appendChild(input);
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
        
        function removeValidation() {
            // Remove all required attributes to allow Previous/Next navigation without blocking
            document.querySelectorAll('[required]').forEach(el => {
                el.removeAttribute('required');
            });
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
    </div>
    
    <div class="content">
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
            <p style="margin: 5px 0 0 0; color: #6b7280;">
                Allocate staff for both Shift 1 and Shift 2
            </p>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="timetable.php">
            <div class="action-buttons" style="margin-bottom: 20px;">
                <button type="submit" name="action" value="reset" class="btn-reset" 
                        onclick="removeValidation()">
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
                    <?php if ($current_index > 0): ?>
                    <button type="submit" name="action" value="previous" class="btn-nav" onclick="removeValidation()">
                        ← Previous Class
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($current_index < $total_classes - 1): ?>
                    <button type="submit" name="action" value="next" class="btn-nav" onclick="return removeValidation()">
                        Next Class →
                    </button>
                    <?php else: ?>
                    <button type="submit" name="action" value="generate" class="btn-generate">
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
                        <td><?php echo htmlspecialchars($subject['title']); ?></td>
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
                                ?>
                                <input type="number" 
                                       name="<?php echo $hours_key_shift1; ?>" 
                                       value="<?php echo $current_hours_shift1; ?>" 
                                       min="1" 
                                       max="30" 
                                       class="hours-input" 
                                       data-shift="shift1"
                                       required>
                            </td>
                            <!-- Shift 1 Staff -->
                            <td>
                                <div class="staff-allocation-container" id="staff-container-shift1-<?php echo $subject['id']; ?>">
                                    <div class="staff-row">
                                        <?php 
                                        $staff_key_shift1 = 'staff_shift1_' . $subject['id'];
                                        $selected_staff_shift1 = isset($_SESSION['staff_allocations'][$staff_key_shift1]) 
                                            ? $_SESSION['staff_allocations'][$staff_key_shift1] 
                                            : '';
                                        ?>
                                        <select name="<?php echo $staff_key_shift1; ?>" 
                                                class="staff-select" 
                                                data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>"
                                                data-subject-id="<?php echo $subject['id']; ?>"
                                                data-shift="shift1"
                                                onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift1')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift1'); checkShiftConflict(this, 'shift1'); }"
                                                required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" 
                                                    <?php echo $selected_staff_shift1 == $staff['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key_shift1; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift1')">+ Add Staff</button>
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
                                ?>
                                <input type="number" 
                                       name="<?php echo $hours_key_shift2; ?>" 
                                       value="<?php echo $current_hours_shift2; ?>" 
                                       min="1" 
                                       max="30" 
                                       class="hours-input" 
                                       data-shift="shift2"
                                       required>
                            </td>
                            <!-- Shift 2 Staff -->
                            <td>
                                <div class="staff-allocation-container" id="staff-container-shift2-<?php echo $subject['id']; ?>">
                                    <div class="staff-row">
                                        <?php 
                                        $staff_key_shift2 = 'staff_shift2_' . $subject['id'];
                                        $selected_staff_shift2 = isset($_SESSION['staff_allocations'][$staff_key_shift2]) 
                                            ? $_SESSION['staff_allocations'][$staff_key_shift2] 
                                            : '';
                                        ?>
                                        <select name="<?php echo $staff_key_shift2; ?>" 
                                                class="staff-select" 
                                                data-subject-title="<?php echo htmlspecialchars($subject['title']); ?>"
                                                data-subject-id="<?php echo $subject['id']; ?>"
                                                data-shift="shift2"
                                                onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>, 'shift2')) { trackAllocation(this, <?php echo $subject['id']; ?>, 'shift2'); checkShiftConflict(this, 'shift2'); }"
                                                required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" 
                                                    <?php echo $selected_staff_shift2 == $staff['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key_shift2; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, 'shift2')">+ Add Staff</button>
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
                                
                                if ($has_split_hours_msc):
                                    // Render split hours inputs
                                    ?>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                        <?php foreach ($split_hours_values_msc as $idx_msc => $hours_val_msc): ?>
                                        <input type="number" 
                                               name="hours_<?php echo $subject['id']; ?>_<?php echo ($idx_msc + 1); ?>" 
                                               class="hours-input split-hours" 
                                               data-subject-id="<?php echo $subject['id']; ?>"
                                               value="<?php echo $hours_val_msc; ?>" 
                                               min="0" 
                                               max="30" 
                                               onchange="updateSplitHoursTotal(<?php echo $subject['id']; ?>, null)"
                                               required>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="hidden" name="<?php echo $hours_key; ?>" value="<?php echo array_sum($split_hours_values_msc); ?>">
                                <?php else: ?>
                                    <!-- Single hours input -->
                                    <input type="number" 
                                           name="<?php echo $hours_key; ?>" 
                                           value="<?php echo $current_hours; ?>" 
                                           min="1" 
                                           max="30" 
                                           class="hours-input" 
                                           required>
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
                                        <select name="<?php echo $staff_key; ?>" 
                                                class="staff-select" 
                                                data-subject-id="<?php echo $subject['id']; ?>"
                                                onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }"
                                                required>
                                            <option value="">Select Staff</option>
                                            <?php foreach ($staff_list as $staff): ?>
                                            <option value="<?php echo $staff['id']; ?>" 
                                                    <?php echo $selected_staff == $staff['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="clear-staff-btn" onclick="clearStaffSelection('<?php echo $staff_key; ?>')" title="Clear selection">×</button>
                                        <button type="button" class="add-staff-btn" onclick="addStaffAllocation(<?php echo $subject['id']; ?>, null)">+ Add Staff</button>
                                    </div>
                                    <?php 
                                    // Check for additional split staff allocations from session
                                    $split_staff_count = 2;
                                    while (isset($_SESSION['staff_allocations']['staff_' . $subject['id'] . '_' . $split_staff_count])) {
                                        $split_staff_key = 'staff_' . $subject['id'] . '_' . $split_staff_count;
                                        $split_selected_staff = $_SESSION['staff_allocations'][$split_staff_key];
                                        ?>
                                        <div class="staff-row" id="staff-row-staff-container-<?php echo $subject['id']; ?>-<?php echo $split_staff_count; ?>">
                                            <select name="<?php echo $split_staff_key; ?>" 
                                                    class="staff-select" 
                                                    data-subject-id="<?php echo $subject['id']; ?>"
                                                    onchange="if(validateStaffHours(this, <?php echo $subject['id']; ?>)) { trackAllocation(this, <?php echo $subject['id']; ?>); }"
                                                    required>
                                                <option value="">Select Staff</option>
                                                <?php foreach ($staff_list as $staff): ?>
                                                <option value="<?php echo $staff['id']; ?>" 
                                                        <?php echo $split_selected_staff == $staff['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($staff['name']); ?> (<?php echo htmlspecialchars($staff['short_code']); ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="remove-staff-btn" onclick="removeStaffAllocation('staff-container-<?php echo $subject['id']; ?>', <?php echo $split_staff_count; ?>, <?php echo $subject['id']; ?>, null)">×</button>
                                        </div>
                                        <?php
                                        $split_staff_count++;
                                    }
                                    
                                    // If we found split staff, we need to cache the counter and initialize in JavaScript
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
                        <?php else: ?>
                            <?php if ($current_class_config['has_shifts']): ?>
                            <!-- No Staff Required for both shifts - but still need hours inputs -->
                            <td>
                                <?php 
                                $hours_key_shift1 = 'hours_shift1_' . $subject['id'];
                                $current_hours_shift1 = isset($_SESSION['hours_changes'][$hours_key_shift1]) 
                                    ? $_SESSION['hours_changes'][$hours_key_shift1] 
                                    : $subject['hours_per_week'];
                                ?>
                                <input type="number" 
                                       name="<?php echo $hours_key_shift1; ?>" 
                                       value="<?php echo $current_hours_shift1; ?>" 
                                       min="0" 
                                       max="30" 
                                       class="hours-input" 
                                       data-shift="shift1"
                                       required>
                            </td>
                            <td class="no-staff-required">No Staff Required</td>
                            <td>
                                <?php 
                                $hours_key_shift2 = 'hours_shift2_' . $subject['id'];
                                $current_hours_shift2 = isset($_SESSION['hours_changes'][$hours_key_shift2]) 
                                    ? $_SESSION['hours_changes'][$hours_key_shift2] 
                                    : $subject['hours_per_week'];
                                ?>
                                <input type="number" 
                                       name="<?php echo $hours_key_shift2; ?>" 
                                       value="<?php echo $current_hours_shift2; ?>" 
                                       min="0" 
                                       max="30" 
                                       class="hours-input" 
                                       data-shift="shift2"
                                       required>
                            </td>
                            <td class="no-staff-required">No Staff Required</td>
                            <?php else: ?>
                            <!-- No Staff Required for single class -->
                            <td>
                                <input type="number" 
                                       name="<?php echo $hours_key; ?>" 
                                       value="<?php echo $current_hours; ?>" 
                                       min="1" 
                                       max="30" 
                                       class="hours-input" 
                                       required>
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
