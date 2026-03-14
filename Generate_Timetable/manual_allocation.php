<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Get parameters
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;
$shift = isset($_GET['shift']) ? $_GET['shift'] : '';
$class_index = isset($_GET['class_index']) ? intval($_GET['class_index']) : 0;
$staff_index = isset($_GET['staff_index']) && $_GET['staff_index'] !== '' ? intval($_GET['staff_index']) : null;

if (!$subject_id) {
    header("Location: timetable.php");
    exit;
}

// Fetch subject details
$subject_query = "SELECT * FROM subjects WHERE id = $subject_id";
$subject_result = $conn->query($subject_query);
$subject = $subject_result->fetch_assoc();

if (!$subject) {
    header("Location: timetable.php");
    exit;
}

// Fetch class details to display (We use the class index logic from timetable.php)
$class_sequence = [
    ['pattern' => 'I B.Sc%', 'label' => 'I B.Sc'],
    ['pattern' => 'II B.Sc%', 'label' => 'II B.Sc'],
    ['pattern' => 'III B.Sc%', 'label' => 'III B.Sc'],
    ['pattern' => 'I M.Sc%', 'label' => 'I M.Sc'],
    ['pattern' => 'II M.Sc%', 'label' => 'II M.Sc'],
];
$current_class_config = $class_sequence[$class_index];
$class_label = $current_class_config['label'] . ($shift ? " (" . ucfirst(str_replace('shift', 'Shift ', $shift)) . ")" : "");

// Initialize manual allocations in session if not exists
if (!isset($_SESSION['manual_allocations'])) {
    $_SESSION['manual_allocations'] = [];
}

// Ensure nested arrays exist
if (!isset($_SESSION['manual_allocations'][$class_index])) {
    $_SESSION['manual_allocations'][$class_index] = [];
}
if (!isset($_SESSION['manual_allocations'][$class_index][$shift])) {
    $_SESSION['manual_allocations'][$class_index][$shift] = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    $selected_slots = isset($_POST['slots']) ? $_POST['slots'] : [];
    
    // Clear previous allocations for this specific subject AND staff_index
    if (isset($_SESSION['manual_allocations'][$class_index][$shift])) {
        foreach ($_SESSION['manual_allocations'][$class_index][$shift] as $key => $alloc) {
            $alloc_staff_index = isset($alloc['staff_index']) ? $alloc['staff_index'] : null;
            if ($alloc['subject_id'] == $subject_id && $alloc_staff_index === $staff_index) {
                unset($_SESSION['manual_allocations'][$class_index][$shift][$key]);
            }
        }
    }
    
    // Save new allocations - validate count first
    if (count($selected_slots) > $expected_hours) {
        // Over-allocation: reject with error message back to page
        $error_msg = urlencode("You selected " . count($selected_slots) . " slots but only $expected_hours are allowed for this subject.");
        header("Location: manual_allocation.php?subject_id=$subject_id&shift=" . urlencode($shift) . "&class_index=$class_index" . ($staff_index !== null ? "&staff_index=$staff_index" : "") . "&error=$error_msg");
        exit;
    }
    
    // Save new allocations
    foreach ($selected_slots as $slot) {
        list($day, $hour) = explode('_', $slot, 2);
        $_SESSION['manual_allocations'][$class_index][$shift][$slot] = [
            'subject_id' => $subject_id,
            'day' => $day,
            'hour' => $hour,
            'staff_index' => $staff_index
        ];
    }
    
    // Return to staff allocation
    header("Location: timetable.php");
    exit;
}

// Handle cancel/back
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    header("Location: timetable.php");
    exit;
}

$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

// Get existing manual allocations for this class/shift to freeze slots occupied by OTHER subjects
$other_allocations = [];
$current_allocations = []; // Allocations for THIS subject

$semester_filter = isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd';

if (isset($_SESSION['manual_allocations'][$class_index][$shift])) {
    foreach ($_SESSION['manual_allocations'][$class_index][$shift] as $slot => $alloc) {
        $alloc_staff_index = isset($alloc['staff_index']) ? $alloc['staff_index'] : null;
        if ($alloc['subject_id'] == $subject_id && $alloc_staff_index === $staff_index) {
            // This slot belongs to the CURRENT subject + staff being edited → show as selected
            $current_allocations[] = $slot;
        } else if ($alloc['subject_id'] == $subject_id) {
            // Same subject, different staff index → show as locked (split staff scenario)
            $other_allocations[$slot] = $subject['title'] . ($alloc_staff_index ? " (Staff $alloc_staff_index)" : "");
        } else {
            // Different subject in the same class/shift → Check if it belongs to the CURRENT semester (Odd/Even)
            // If it belongs to the OTHER semester (e.g. Tamil II during Odd sem), DO NOT show it as locked.
            $other_sub_query = "SELECT title, semester FROM subjects WHERE id = " . intval($alloc['subject_id']);
            $other_sub_result = $conn->query($other_sub_query);
            if ($row = $other_sub_result->fetch_assoc()) {
                $other_sem = intval($row['semester']);
                $is_other_sem_odd = in_array($other_sem, [1, 3, 5]);
                
                // Only lock the slot if the other subject is in the SAME semester parity (odd/even)
                if (($semester_filter === 'odd' && $is_other_sem_odd) || ($semester_filter === 'even' && !$is_other_sem_odd)) {
                    $other_allocations[$slot] = $row['title'];
                }
            } else {
                // If subject not found in DB for some reason, default to locked to be safe
                $other_allocations[$slot] = "Allocated";
            }
        }
    }
}

// Hours expected
$hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
if ($staff_index !== null) {
    $hours_key .= '_' . $staff_index;
    // Fallback to base hours if split hours aren't defined yet
    if (!isset($_SESSION['hours_changes'][$hours_key])) {
        $base_hours_key = $shift ? 'hours_' . $shift . '_' . $subject_id : 'hours_' . $subject_id;
        $expected_hours = isset($_SESSION['hours_changes'][$base_hours_key]) ? $_SESSION['hours_changes'][$base_hours_key] : $subject['hours_per_week'];
    } else {
        $expected_hours = $_SESSION['hours_changes'][$hours_key];
    }
} else {
    $expected_hours = isset($_SESSION['hours_changes'][$hours_key]) ? $_SESSION['hours_changes'][$hours_key] : $subject['hours_per_week'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Timetable Allocation - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
    <style>
        .allocation-header {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .allocation-info h3 {
            margin: 0 0 10px 0;
            color: #1f2937;
            font-size: 24px;
        }
        
        .allocation-info p {
            margin: 0;
            color: #4b5563;
            font-size: 16px;
        }
        
        .hours-counter {
            background: #e0e7ff;
            color: #4338ca;
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
        }
        
        .hours-counter.over {
            background: #fecaca;
            color: #dc2626;
        }
        
        .hours-counter.exact {
            background: #d1fae5;
            color: #059669;
        }

        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .timetable-table th {
            background: #1f2937;
            color: white;
            padding: 15px;
            text-align: center;
            border: 1px solid #374151;
        }

        .timetable-table td {
            height: 80px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            position: relative;
            padding: 0;
        }
        
        .timetable-table td.day-header {
            background: #f9fafb;
            font-weight: bold;
            color: #374151;
            width: 100px;
        }

        .slot-checkbox {
            display: none;
        }

        .slot-label {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
        }

        .slot-label:hover {
            background: #f3f4f6;
        }

        .slot-checkbox:checked + .slot-label {
            background: #dbeafe;
            border: 2px solid #3b82f6;
            color: #1d4ed8;
            font-weight: bold;
        }
        
        .slot-checkbox:checked + .slot-label::after {
            content: '✓ Selected';
            font-size: 12px;
            margin-top: 5px;
        }

        .slot-occupied {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            font-size: 12px;
            padding: 5px;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn-save {
            background: #10b981;
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-save:hover {
            background: #059669;
        }
        
        .btn-cancel {
            background: #6b7280;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
            <a href="../Logout/logout.php" class="logout-icon">⎋</a>
        </div>
    </nav>
    
    <div class="content">
        <div class="allocation-header">
            <div class="allocation-info">
                <h3>Manual Allocation for <?php echo htmlspecialchars($subject['title']); ?></h3>
                <p><strong>Class:</strong> <?php echo htmlspecialchars($class_label); ?></p>
                <p><strong>Subject Type:</strong> <span class="badge badge-<?php echo strtolower($subject['type']); ?>"><?php echo htmlspecialchars($subject['type']); ?></span></p>
                <p style="margin-top: 10px; font-size: 14px; color: #6b7280;">Click on empty slots to place this subject. Other manually placed subjects are shown in grey.</p>
            </div>
            <div class="hours-counter" id="hours-counter">
                <span id="selected-count"><?php echo count($current_allocations); ?></span> / <?php echo $expected_hours; ?> Slots Selected
            </div>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
        <div id="error-banner" style="background:#fef2f2; border:1px solid #fecaca; color:#dc2626; padding:12px 20px; border-radius:8px; margin-bottom:20px; font-weight:600; display:flex; align-items:center; gap:10px;">
            ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="manual_allocation.php?subject_id=<?php echo $subject_id; ?>&shift=<?php echo urlencode($shift); ?>&class_index=<?php echo $class_index; ?><?php echo $staff_index !== null ? '&staff_index=' . $staff_index : ''; ?>">
            <table class="timetable-table">
                <thead>
                    <tr>
                        <th class="day-header">DAY/HOUR</th>
                        <?php foreach ($hours as $hour): ?>
                            <th><?php echo $hour; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day): ?>
                        <tr>
                            <td class="day-header"><?php echo $day; ?></td>
                            <?php foreach ($hours as $hour): ?>
                                <?php 
                                $slot_id = $day . '_' . $hour; 
                                $is_selected = in_array($slot_id, $current_allocations);
                                $is_occupied = array_key_exists($slot_id, $other_allocations);
                                ?>
                                <td>
                                    <?php if ($is_occupied): ?>
                                        <div class="slot-occupied" title="Occupied by <?php echo htmlspecialchars($other_allocations[$slot_id]); ?>">
                                            <?php echo htmlspecialchars($other_allocations[$slot_id]); ?>
                                            <div style="font-size: 10px; margin-top: 4px;">(Locked)</div>
                                        </div>
                                    <?php else: ?>
                                        <input type="checkbox" name="slots[]" value="<?php echo $slot_id; ?>" id="slot_<?php echo str_replace(' ', '_', $slot_id); ?>" class="slot-checkbox" <?php echo $is_selected ? 'checked' : ''; ?> onchange="updateCounter()">
                                        <label for="slot_<?php echo str_replace(' ', '_', $slot_id); ?>" class="slot-label">
                                            Select
                                        </label>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="action-buttons">
                <button type="submit" name="action" value="cancel" class="btn-cancel">← Back without Saving</button>
                <button type="submit" name="action" value="save" class="btn-save">Save Manual Allocation</button>
            </div>
        </form>
    </div>
    
    <script>
        const expectedHours = <?php echo $expected_hours; ?>;
        
        // Toast warning element
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);background:#dc2626;color:white;padding:12px 28px;border-radius:8px;font-weight:700;font-size:15px;z-index:9999;display:none;box-shadow:0 4px 16px rgba(220,38,38,0.4);transition:opacity 0.3s';
        toast.textContent = '⚠️ Maximum slots reached! You cannot select more than ' + expectedHours + ' slots.';
        document.body.appendChild(toast);
        
        function showToast() {
            toast.style.display = 'block';
            toast.style.opacity = '1';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => { toast.style.display = 'none'; }, 300); }, 2500);
        }
        
        function updateCounter() {
            const checkboxes = document.querySelectorAll('.slot-checkbox:checked');
            const count = checkboxes.length;
            const counterDiv = document.getElementById('hours-counter');
            const countSpan = document.getElementById('selected-count');
            const saveBtn = document.querySelector('.btn-save');
            
            countSpan.textContent = count;
            
            counterDiv.className = 'hours-counter';
            if (count > expectedHours) {
                counterDiv.classList.add('over');
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.5';
                saveBtn.style.cursor = 'not-allowed';
            } else if (count === expectedHours) {
                counterDiv.classList.add('exact');
                saveBtn.disabled = false;
                saveBtn.style.opacity = '';
                saveBtn.style.cursor = '';
            } else {
                saveBtn.disabled = false;
                saveBtn.style.opacity = '';
                saveBtn.style.cursor = '';
            }
        }
        
        // Intercept checkbox changes to block over-selection
        document.querySelectorAll('.slot-checkbox').forEach(cb => {
            cb.addEventListener('change', function(e) {
                if (this.checked) {
                    const currentCount = document.querySelectorAll('.slot-checkbox:checked').length;
                    if (currentCount > expectedHours) {
                        this.checked = false;  // Revert
                        showToast();
                        // Shake the counter
                        const counterDiv = document.getElementById('hours-counter');
                        counterDiv.style.transform = 'scale(1.1)';
                        setTimeout(() => { counterDiv.style.transform = ''; }, 200);
                        return;
                    }
                }
                updateCounter();
            });
        });
        
        // Initialize on load
        updateCounter();
    </script>
</body>
</html>
