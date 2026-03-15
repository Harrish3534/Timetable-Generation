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

// Load all slots
$slots_result = $conn->query("SELECT * FROM saved_timetable_slots WHERE saved_timetable_id = $id ORDER BY class_id, day, hour");
$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

// Build nested structure: [class_id => [day => [hour => slot_row]]]
$timetable_data = [];
$class_info = []; // class_id => [name, shift, semester]
$slot_map = []; // slot_id => slot_row (for quick lookups)

while ($slot = $slots_result->fetch_assoc()) {
    $cid = $slot['class_id'];
    if (!isset($class_info[$cid])) {
        $class_info[$cid] = ['name' => $slot['class_name'], 'shift' => $slot['shift'], 'semester' => $slot['semester']];
    }
    $timetable_data[$cid][$slot['day']][$slot['hour']] = $slot;
    $slot_map[$slot['id']] = $slot;
}

// SNAPSHOT LOGIC FOR DISCARD
if (!isset($_SESSION['tt_backup'])) {
    $_SESSION['tt_backup'] = [];
}
if (!isset($_SESSION['tt_backup'][$id])) {
    $_SESSION['tt_backup'][$id] = $slot_map;
}

// Load subjects grouped by class semester for the edit dropdown
$all_subjects = [];
$sres = $conn->query("SELECT id, title, type, program, semester FROM subjects ORDER BY semester, sort_order, id");
while ($sr = $sres->fetch_assoc())
    $all_subjects[] = $sr;

// Load staff for dropdowns
$staff_list = [];
$sres2 = $conn->query("SELECT id, name, short_code FROM staff ORDER BY id");
while ($sr2 = $sres2->fetch_assoc())
    $staff_list[] = $sr2;

// Unique ordered class list
$ordered_class_ids = array_keys($class_info);

// Build class label for display
function classLabel($info)
{
    $name = $info['name'];
    $shift = $info['shift'];
    $label = $name;
    if (strpos($shift, 'Shift 1') !== false)
        $label .= ' SI';
    elseif (strpos($shift, 'Shift 2') !== false)
        $label .= ' SII';
    return $label;
}

// Day background colors
$day_bg = ['I DAY' => '#fff9c4', 'II DAY' => '#e8f5e9', 'III DAY' => '#e3f2fd', 'IV DAY' => '#fce4ec', 'V DAY' => '#f3e5f5', 'VI DAY' => '#fff3e0'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Timetable - <?php echo htmlspecialchars($header['name']); ?></title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
    <style>
        .tt-wrap {
            overflow-x: auto;
        }

        .whole-tt {
            border-collapse: collapse;
            width: 100%;
            font-size: 12px;
        }

        .whole-tt th,
        .whole-tt td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            text-align: center;
        }

        .whole-tt thead tr:first-child td {
            background: #1a3a6b;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
        }

        .whole-tt thead tr:nth-child(2) td {
            background: #1a3a6b;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
        }

        .whole-tt thead tr:nth-child(3) td {
            background: #1a3a6b;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
        }

        .whole-tt thead tr:nth-child(4) td {
            background: #c8e6c9;
            font-weight: bold;
            font-size: 11px;
        }

        .cell-content {
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            min-height: 30px;
            min-width: 60px;
            display: inline-block;
            width: 100%;
        }

        .cell-content:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .cell-content.selected-a {
            background: #fef08a !important;
            outline: 2px solid #f59e0b;
        }

        .cell-content.clash-warning {
            background: #fecaca !important; /* light red */
            outline: 2px solid #dc2626; /* dark red */
        }

        .cell-content.clash-state {
            background: #fecaca !important;
            color: #991b1b;
        }
        
        .cell-content.clash-state:not(.selected-a):not(.selected-b) {
            outline: 2px solid #ef4444;
        }

        .cell-content.lab-group-clash {
            background: #fed7aa !important; /* orange */
            color: #92400e;
            outline: 2px solid #f97316;
        }


        .cell-empty {
            color: #9ca3af;
            font-style: italic;
        }

        .lab-cell {
            color: #b45309;
            font-style: italic;
            font-size: 11px;
        }

        .day-cell {
            font-weight: bold;
            font-size: 12px;
            vertical-align: middle;
        }

        .class-cell {
            font-size: 11px;
            font-weight: 600;
            color: #1e3a5f;
            white-space: nowrap;
            border-right: 2px solid #9ca3af;
        }

        /* Mode badges */
        .mode-bar {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .mode-btn {
            padding: 8px 18px;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
        }

        .mode-btn.active {
            border-color: #3b82f6;
        }

        .mode-view {
            background: #f3f4f6;
            color: #374151;
        }

        .mode-view.active {
            background: #dbeafe;
            color: #1e40af;
            border-color: #3b82f6;
        }

        .mode-swap {
            background: #f3f4f6;
            color: #374151;
        }

        .mode-swap.active {
            background: #fef9c3;
            color: #854d0e;
            border-color: #f59e0b;
        }

        .mode-edit {
            background: #f3f4f6;
            color: #374151;
        }

        .mode-edit.active {
            background: #dcfce7;
            color: #166534;
            border-color: #22c55e;
        }

        /* Toast */
        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #1f2937;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            display: none;
        }

        /* Edit modal */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.open {
            display: flex;
        }

        /* Higher z-index for secondary modals that appear above edit modal */
        .modal-overlay-top {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay-top.open {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            width: 400px;
            max-width: 95vw;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal h3 {
            margin: 0 0 16px 0;
            color: #1f2937;
        }

        .modal label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            margin-top: 12px;
            margin-bottom: 4px;
            color: #374151;
        }

        .modal select {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }

        .btn-save-slot {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-clear-slot {
            background: #ef4444;
            color: white;
            border: none;
            padding: 9px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-cancel-modal {
            background: #f3f4f6;
            color: #374151;
            border: none;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: auto;
        }

        .clash-list {
            text-align: left;
            font-size: 13px;
            color: #b91c1c;
            background: #fef2f2;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #fca5a5;
        }

        /* Print — suppressed on main page; popup handles printing */
        @media print {
            body {
                display: none;
            }
        }

        .swap-hint {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 13px;
            color: #854d0e;
            display: none;
        }

        .swap-hint.visible {
            display: block;
        }

        /* Grey out cells belonging to other classes while first swap slot is chosen */
        .swap-disabled {
            opacity: 0.25;
            pointer-events: none;
            cursor: not-allowed;
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
        <div class="no-print"
            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:10px;">
            <div>
                <h2 style="margin:0; color:#1f2937;"><?php echo htmlspecialchars($header['name']); ?></h2>
                <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
                    <?php echo ucfirst($header['semester']); ?> Semester &nbsp;·&nbsp;
                    Saved <?php echo date('d M Y, h:i A', strtotime($header['created_at'])); ?>
                </p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button onclick="handleBackAction()" class="btn btn-secondary">← Back</button>
                <button onclick="confirmSaveAndExit()" class="btn btn-primary" style="background:#16a34a; border-color:#16a34a;">💾 Save & Exit</button>
                <button onclick="window.open('view_saved_class_timetable.php?id=<?php echo $id; ?>', '_blank')" class="btn btn-primary" style="background:#2563eb; border-color:#2563eb;">🎓 Class Timetable</button>
                <button onclick="window.open('view_saved_staff_timetable.php?id=<?php echo $id; ?>', '_blank')" class="btn btn-primary" style="background:#2563eb; border-color:#2563eb;">👥 Staff Timetable</button>
                <button onclick="printWholeTimetablePopup()" class="btn btn-primary">🖨️ Print</button>
            </div>
        </div>

        <!-- Mode selector -->
        <div class="mode-bar no-print">
            <span style="font-weight:600; color:#374151;">Mode:</span>
            <button class="mode-btn mode-view active" id="btn-mode-view" onclick="setMode('view')">👁 View</button>
            <button class="mode-btn mode-swap" id="btn-mode-swap" onclick="setMode('swap')">🔄 Swap Slots</button>
            <button class="mode-btn mode-edit" id="btn-mode-edit" onclick="setMode('edit')">✏️ Edit Slot</button>
        </div>
        <div class="swap-hint no-print" id="swap-hint">
            Click the <strong>first slot</strong> you want to swap (it will highlight yellow), then click the
            <strong>second slot</strong> (highlights green). They will be swapped automatically.
        </div>

        <!-- Timetable -->
        <div class="tt-wrap">
            <table class="whole-tt" id="whole-tt">
                <thead>
                    <tr>
                        <td colspan="7" style="text-align:center;">Government Arts College(Autonomous), Coimbatore-18
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" style="text-align:center;">PG &amp; Research Department of Computer Science</td>
                    </tr>
                    <tr>
                        <td colspan="7" style="text-align:center;">Time Table 2025-26
                            <?php echo ucfirst($header['semester']); ?> Semester</td>
                    </tr>
                    <tr>
                        <td>HOUR/DAY</td>
                        <td>CLASS</td>
                        <td>I HOUR</td>
                        <td>II HOUR</td>
                        <td>III HOUR</td>
                        <td>IV HOUR</td>
                        <td>V HOUR</td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $dy):
                        $classes_this_day = [];
                        foreach ($ordered_class_ids as $cid) {
                            if (isset($timetable_data[$cid][$dy]))
                                $classes_this_day[] = $cid;
                        }
                        $class_count = count($classes_this_day);
                        if ($class_count === 0)
                            continue;
                        $first = true;
                        $bg = $day_bg[$dy] ?? '#fff';
                        foreach ($classes_this_day as $cid):
                            $row_hours = $timetable_data[$cid][$dy];
                            // Build cells with lab merging
                            $i = 0;
                            $cells = [];
                            while ($i < count($hours)) {
                                $h = $hours[$i];
                                $slot = $row_hours[$h] ?? null;
                                if ($slot && $slot['subject_type'] === 'Lab' && $slot['subject_id']) {
                                    $span = 1;
                                    $hlst = [$h];
                                    $all_ids = [$slot ? $slot['id'] : ''];
                                    while ($span + $i < count($hours)) {
                                        $ns = $row_hours[$hours[$i + $span]] ?? null;
                                        if ($ns && $ns['subject_id'] === $slot['subject_id']) {
                                            $hlst[] = $hours[$i + $span];
                                            $all_ids[] = $ns ? $ns['id'] : '';
                                            $span++;
                                        }
                                        else
                                            break;
                                    }
                                    $cells[] = ['slot' => $slot, 'colspan' => $span, 'is_lab' => true, 'hours' => implode(',', $hlst), 'all_ids' => implode(',', $all_ids)];
                                    $i += $span;
                                } else {
                                    $cells[] = ['slot' => $slot, 'colspan' => 1, 'is_lab' => false, 'hours' => $h, 'all_ids' => $slot ? $slot['id'] : ''];
                                    $i++;
                                }
                            }
                            ?>
                            <tr style="background:<?php echo $bg; ?>;">
                                <?php if ($first): ?>
                                    <td class="day-cell" rowspan="<?php echo $class_count; ?>"
                                        style="background:<?php echo $bg; ?>; border:1px solid #d1d5db;">
                                        <?php echo htmlspecialchars($dy); ?>
                                    </td>
                                    <?php $first = false; endif; ?>
                                <td class="class-cell"><?php echo htmlspecialchars(classLabel($class_info[$cid])); ?></td>
                                <?php foreach ($cells as $cell):
                                    $slot = $cell['slot'];
                                    $sid = $slot ? $slot['id'] : null;
                                    $sname = $slot && $slot['subject_short_name'] ? htmlspecialchars($slot['subject_short_name']) : '';
                                    $scode = $slot && $slot['staff_code'] ? '(' . htmlspecialchars($slot['staff_code']) . ')' : '';
                                    $is_empty = !$slot || !$slot['subject_id'];
                                    ?>
                                    <td colspan="<?php echo $cell['colspan']; ?>" style="border:1px solid #d1d5db; padding:3px;">
                                        <div class="cell-content <?php echo $cell['is_lab'] ? 'lab-cell' : ''; ?> <?php echo $is_empty ? 'cell-empty' : ''; ?>"
                                            data-slot-id="<?php echo $sid; ?>" data-class-id="<?php echo $cid; ?>"
                                            data-all-slot-ids="<?php echo htmlspecialchars($cell['all_ids']); ?>"
                                            data-semester="<?php echo $class_info[$cid]['semester']; ?>"
                                            data-program="<?php echo strpos($class_info[$cid]['name'], 'M.Sc') !== false ? 'PG' : 'UG'; ?>"
                                            data-day="<?php echo htmlspecialchars($dy); ?>"
                                            data-hours="<?php echo htmlspecialchars($cell['hours']); ?>"
                                            data-staff-id="<?php echo $slot ? ($slot['staff_id'] ?? '') : ''; ?>"
                                            data-staff-name="<?php echo htmlspecialchars($slot ? ($slot['staff_name'] ?? '') : ''); ?>"
                                            data-is-manual="<?php
                                                $is_manual_slot = $slot && (
                                                    !empty($slot['is_manual']) ||
                                                    in_array($slot['subject_type'], ['Common', 'Allied', 'NME'])
                                                );
                                                echo $is_manual_slot ? '1' : '0';
                                            ?>"
                                            onclick="handleCellClick(this)"
                                            title="<?php echo $slot ? htmlspecialchars($slot['subject_title'] ?? '') : 'Empty'; ?>">
                                            <?php if ($is_empty): ?>
                                                <span style="font-size:10px;">-</span>
                                            <?php else: ?>
                                                <?php echo $sname; ?><br><small><?php echo $scode; ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Manual Slot Confirmation Modal -->
    <div class="modal-overlay-top no-print" id="manual-confirm-modal">
        <div class="modal" style="max-width:380px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">⚠️</div>
            <h3 style="color:#92400e; margin-bottom:10px;">Manually Allocated Slot</h3>
            <p style="color:#6b7280; font-size:13px; margin-bottom:20px;">This subject was manually allocated to a fixed position. Are you sure you want to swap it?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="manual-confirm-yes" class="btn-save-slot" style="background:#f59e0b;">Yes, Swap It</button>
                <button id="manual-confirm-no" class="btn-cancel-modal" style="margin-left:0;">No, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Clash Confirmation Modal -->
    <div class="modal-overlay-top no-print" id="clash-confirm-modal">
        <div class="modal" style="max-width:420px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">🚨</div>
            <h3 style="color:#dc2626; margin-bottom:10px;">Staff Clash Detected</h3>
            <p id="clash-confirm-msg" style="color:#6b7280; font-size:13px; margin-bottom:20px;">The staff already has hours here. Do you want to continue?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="clash-confirm-yes" class="btn-save-slot" style="background:#dc2626;">Yes, Proceed</button>
                <button id="clash-confirm-no" class="btn-cancel-modal" style="margin-left:0;">No, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Clear Slot Confirmation Modal -->
    <div class="modal-overlay-top no-print" id="clear-confirm-modal">
        <div class="modal" style="max-width:380px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">🗑️</div>
            <h3 style="color:#b91c1c; margin-bottom:10px;">Clear Slot</h3>
            <p style="color:#6b7280; font-size:13px; margin-bottom:20px;">Do you want to clear this slot? The subject and staff will be removed.</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="clear-confirm-yes" class="btn-save-slot" style="background:#ef4444;">Yes, Clear</button>
                <button id="clear-confirm-no" class="btn-cancel-modal" style="margin-left:0;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Multi-Swap Confirmation Modal -->
    <div class="modal-overlay-top no-print" id="swap-confirm-modal">
        <div class="modal" style="max-width:440px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">🔄</div>
            <h3 style="color:#2563eb; margin-bottom:10px;">Confirm Swap</h3>
            <p style="color:#6b7280; font-size:13px; margin-bottom:10px;">You are about to swap the following sets. Do you want to continue?</p>
            <div style="background:#f3f4f6; border:1px solid #d1d5db; border-radius:6px; padding:10px; text-align:left; font-size:13px; margin-bottom:20px;">
                <strong>Slot 1:</strong><br>
                <span id="swap-confirm-group-a" style="color:#1f2937;"></span>
                <hr style="border:0; border-top:1px dashed #9ca3af; margin:8px 0;">
                <strong>Slot 2:</strong><br>
                <span id="swap-confirm-group-b" style="color:#1f2937;"></span>
            </div>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="swap-confirm-yes" class="btn-save-slot" style="background:#2563eb;">Yes, Continue</button>
                <button id="swap-confirm-no" class="btn-cancel-modal" style="margin-left:0;">No, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Exit with Clashes Modal -->
    <div class="modal-overlay no-print" id="exit-clash-modal">
        <div class="modal" style="max-width:480px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">⚠️</div>
            <h3 id="exit-clash-title" style="color:#92400e; margin-bottom:10px;">Unresolved Clashes</h3>
            <div id="exit-clash-list" class="clash-list" style="text-align:left; background:#fef3c7; border-radius:8px; padding:10px; font-size:12px; margin-bottom:15px;"></div>
            <p style="color:#6b7280; font-size:13px; margin-bottom:20px; font-weight:600;">Do you still want to save and exit?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button id="exit-anyway-btn" onclick="forceSaveAndExit()" class="btn-save-slot" style="background:#d97706;">Yes, Exit Anyway</button>
                <button onclick="closeExitClashModal()" class="btn-cancel-modal" style="margin-left:0;">No, Stay Here</button>
            </div>
        </div>
    </div>

    <!-- Unsaved Changes Back Modal -->
    <div class="modal-overlay no-print" id="unsaved-back-modal">
        <div class="modal" style="max-width:380px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">🛑</div>
            <h3 style="color:#b91c1c; margin-bottom:10px;">Unsaved Changes</h3>
            <p style="color:#6b7280; font-size:13px; margin-bottom:20px;">You have made changes to the timetable. If you go back now, those changes might not be finalized. Do you want to continue without saving?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button onclick="discardChanges()" class="btn-save-slot" style="background:#ef4444;" id="btn-discard">Discard & Go Back</button>
                <button onclick="closeUnsavedBackModal()" class="btn-cancel-modal" style="margin-left:0;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Save & Exit Confirmation Modal -->
    <div class="modal-overlay-top no-print" id="save-confirm-modal">
        <div class="modal" style="max-width:380px; text-align:center;">
            <div style="font-size:32px; margin-bottom:12px;">💾</div>
            <h3 style="color:#16a34a; margin-bottom:10px;">Save and Exit</h3>
            <p style="color:#6b7280; font-size:13px; margin-bottom:20px;">Do you want to save your changes and exit?</p>
            <div style="display:flex; gap:10px; justify-content:center;">
                <button onclick="proceedSaveAndExit()" class="btn-save-slot" style="background:#16a34a;">Yes, Save & Exit</button>
                <button onclick="document.getElementById('save-confirm-modal').classList.remove('open')" class="btn-cancel-modal" style="margin-left:0;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal-overlay no-print" id="edit-modal">
        <div class="modal">
            <h3>✏️ Edit Slot</h3>
            <input type="hidden" id="edit-slot-id">
            <input type="hidden" id="edit-class-id">
            <input type="hidden" id="edit-semester">
            <label>Subject</label>
            <select id="edit-subject">
                <option value="">— Empty —</option>
                <?php foreach ($all_subjects as $s): ?>
                    <option value="<?php echo $s['id']; ?>" data-title="<?php echo htmlspecialchars($s['title']); ?>"
                        data-type="<?php echo htmlspecialchars($s['type']); ?>" data-sem="<?php echo $s['semester']; ?>" data-program="<?php echo htmlspecialchars($s['program']); ?>">
                        [Sem<?php echo $s['semester']; ?>     <?php echo htmlspecialchars($s['type']); ?>]
                        <?php echo htmlspecialchars($s['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <label>Staff</label>
            <select id="edit-staff">
                <option value="">— No Staff —</option>
                <?php foreach ($staff_list as $sf): ?>
                    <option value="<?php echo $sf['id']; ?>" data-code="<?php echo htmlspecialchars($sf['short_code']); ?>"
                        data-name="<?php echo htmlspecialchars($sf['name']); ?>">
                        <?php echo htmlspecialchars($sf['name']); ?> (<?php echo htmlspecialchars($sf['short_code']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="modal-actions">
                <button class="btn-save-slot" onclick="saveSlotEdit()">💾 Save</button>
                <button class="btn-clear-slot" onclick="clearSlot()">🗑 Clear</button>
                <button class="btn-cancel-modal" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="toast"></div>

    <script>
        // JSON data passed from PHP for all slots
        const allSubjects = <?php
        $subj_for_js = [];
        foreach ($all_subjects as $s) {
            $subj_for_js[] = ['id' => $s['id'], 'title' => $s['title'], 'type' => $s['type'], 'semester' => $s['semester']];
        }
        echo json_encode($subj_for_js);
        ?>;

        // Class shift info for lab group clash detection
        // Shift 1 + PG = group 1, Shift 2 = group 2
        const classShiftInfo = <?php
        $shift_for_js = [];
        foreach ($class_info as $cid => $ci) {
            $shift_for_js[$cid] = [
                'name' => $ci['name'],
                'shift' => $ci['shift'],
                'group' => (strpos($ci['shift'], 'Shift 2') !== false) ? 2 : 1
            ];
        }
        echo json_encode($shift_for_js);
        ?>;

        let currentMode = 'view';
        let swapSlotA = null;
        let pendingManualResolve = null; // holds resolve() for the manual-confirm promise
        let pendingClashResolve = null;
        let pendingClearResolve = null;
        let hasUnsavedChanges = <?php echo isset($_GET['changed']) && $_GET['changed'] == '1' ? 'true' : 'false'; ?>;

        function getConsecutiveCells(startEl, reqLen) {
            const classId = startEl.getAttribute('data-class-id');
            const day = startEl.getAttribute('data-day');
            const startHours = startEl.getAttribute('data-hours').split(',');
            const ALL_HOURS = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];
            const startIndex = ALL_HOURS.indexOf(startHours[0]);
            
            if (startIndex + reqLen > ALL_HOURS.length) return null; // Not enough hours left in the day
            
            const targetHours = ALL_HOURS.slice(startIndex, startIndex + reqLen);
            
            const cells = [];
            document.querySelectorAll('.cell-content').forEach(c => {
                if (c.getAttribute('data-class-id') === classId && c.getAttribute('data-day') === day) {
                    const cHours = c.getAttribute('data-hours').split(',');
                    if (cHours.some(h => targetHours.includes(h))) {
                        cells.push(c);
                    }
                }
            });
            
            // Validate that gathered cells exactly cover targetHours without spilling over
            const coveredHours = [];
            cells.forEach(c => coveredHours.push(...c.getAttribute('data-hours').split(',')));
            if (coveredHours.length !== reqLen || !coveredHours.every(h => targetHours.includes(h))) {
                return null; // Spills over or missing hours
            }
            
            // Sort cells by hour chronological order
            cells.sort((a, b) => {
                const aIdx = ALL_HOURS.indexOf(a.getAttribute('data-hours').split(',')[0]);
                const bIdx = ALL_HOURS.indexOf(b.getAttribute('data-hours').split(',')[0]);
                return aIdx - bIdx;
            });
            
            return cells;
        }

        // Show manual-allocation confirmation dialog. Returns a Promise<bool>.
        function confirmManualSwap() {
            return new Promise(resolve => {
                pendingManualResolve = resolve;
                document.getElementById('manual-confirm-modal').classList.add('open');
            });
        }
        document.getElementById('manual-confirm-yes').addEventListener('click', () => {
            document.getElementById('manual-confirm-modal').classList.remove('open');
            if (pendingManualResolve) { pendingManualResolve(true); pendingManualResolve = null; }
        });
        document.getElementById('manual-confirm-no').addEventListener('click', () => {
            document.getElementById('manual-confirm-modal').classList.remove('open');
            if (pendingManualResolve) { pendingManualResolve(false); pendingManualResolve = null; }
        });
        document.getElementById('manual-confirm-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
                if (pendingManualResolve) { pendingManualResolve(false); pendingManualResolve = null; }
            }
        });

        function confirmClashSwap(msg) {
            return new Promise(resolve => {
                pendingClashResolve = resolve;
                document.getElementById('clash-confirm-msg').textContent = msg + " Do you want to continue?";
                document.getElementById('clash-confirm-modal').classList.add('open');
            });
        }
        document.getElementById('clash-confirm-yes').addEventListener('click', () => {
            document.getElementById('clash-confirm-modal').classList.remove('open');
            if (pendingClashResolve) { pendingClashResolve(true); pendingClashResolve = null; }
        });
        document.getElementById('clash-confirm-no').addEventListener('click', () => {
            document.getElementById('clash-confirm-modal').classList.remove('open');
            if (pendingClashResolve) { pendingClashResolve(false); pendingClashResolve = null; }
        });
        document.getElementById('clash-confirm-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
                if (pendingClashResolve) { pendingClashResolve(false); pendingClashResolve = null; }
            }
        });

        // Clear Slot confirmation
        function confirmClearSlot() {
            return new Promise(resolve => {
                pendingClearResolve = resolve;
                document.getElementById('clear-confirm-modal').classList.add('open');
            });
        }
        document.getElementById('clear-confirm-yes').addEventListener('click', () => {
            document.getElementById('clear-confirm-modal').classList.remove('open');
            if (pendingClearResolve) { pendingClearResolve(true); pendingClearResolve = null; }
        });
        document.getElementById('clear-confirm-no').addEventListener('click', () => {
            document.getElementById('clear-confirm-modal').classList.remove('open');
            if (pendingClearResolve) { pendingClearResolve(false); pendingClearResolve = null; }
        });
        document.getElementById('clear-confirm-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
                if (pendingClearResolve) { pendingClearResolve(false); pendingClearResolve = null; }
            }
        });

        let pendingSwapResolve = null;
        function confirmMultiSwap(groupAHTML, groupBHTML) {
            return new Promise(resolve => {
                pendingSwapResolve = resolve;
                document.getElementById('swap-confirm-group-a').innerHTML = groupAHTML;
                document.getElementById('swap-confirm-group-b').innerHTML = groupBHTML;
                document.getElementById('swap-confirm-modal').classList.add('open');
            });
        }
        document.getElementById('swap-confirm-yes').addEventListener('click', () => {
            document.getElementById('swap-confirm-modal').classList.remove('open');
            if (pendingSwapResolve) { pendingSwapResolve(true); pendingSwapResolve = null; }
        });
        document.getElementById('swap-confirm-no').addEventListener('click', () => {
            document.getElementById('swap-confirm-modal').classList.remove('open');
            if (pendingSwapResolve) { pendingSwapResolve(false); pendingSwapResolve = null; }
        });
        document.getElementById('swap-confirm-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
                if (pendingSwapResolve) { pendingSwapResolve(false); pendingSwapResolve = null; }
            }
        });

        function getStaffClashWarning(elA, elB) {
            const staffIdA = elA.getAttribute('data-staff-id');
            const staffNameA = elA.getAttribute('data-staff-name');
            const hoursB = elB.getAttribute('data-hours').split(',');
            const dayB = elB.getAttribute('data-day');
            const classId = elA.getAttribute('data-class-id'); // same for both

            const staffIdB = elB.getAttribute('data-staff-id');
            const staffNameB = elB.getAttribute('data-staff-name');
            const hoursA = elA.getAttribute('data-hours').split(',');
            const dayA = elA.getAttribute('data-day');

            let clashMsg = null;

            // Check if staff A will clash at B's time
            if (staffIdA) {
                document.querySelectorAll('.cell-content').forEach(el => {
                    if (el.getAttribute('data-class-id') === classId) return; // ignore same class
                    if (el.getAttribute('data-staff-id') !== staffIdA) return;
                    if (el.getAttribute('data-day') !== dayB) return;
                    const elHours = el.getAttribute('data-hours').split(',');
                    if (hoursB.some(h => elHours.includes(h))) {
                        clashMsg = `${staffNameA} is already having hours here (${el.closest('tr').querySelector('.class-cell').textContent.trim()} on ${dayB} at ${hoursB.join(', ')}).`;
                    }
                });
            }

            // Check if staff B will clash at A's time
            if (!clashMsg && staffIdB) {
                document.querySelectorAll('.cell-content').forEach(el => {
                    if (el.getAttribute('data-class-id') === classId) return; // ignore same class
                    if (el.getAttribute('data-staff-id') !== staffIdB) return;
                    if (el.getAttribute('data-day') !== dayA) return;
                    const elHours = el.getAttribute('data-hours').split(',');
                    if (hoursA.some(h => elHours.includes(h))) {
                        clashMsg = `${staffNameB} is already having hours here (${el.closest('tr').querySelector('.class-cell').textContent.trim()} on ${dayA} at ${hoursA.join(', ')}).`;
                    }
                });
            }

            return clashMsg;
        }

        function setMode(mode) {
            currentMode = mode;
            swapSlotA = null;
            document.querySelectorAll('.mode-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('btn-mode-' + mode).classList.add('active');
            document.querySelectorAll('.cell-content').forEach(el => {
                el.classList.remove('selected-a', 'selected-b', 'swap-disabled', 'clash-warning');
            });
            document.getElementById('swap-hint').classList.toggle('visible', mode === 'swap');
        }

        function evaluateAllClashes() {
            const cells = Array.from(document.querySelectorAll('.cell-content:not(.cell-empty)'));
            // reset first
            document.querySelectorAll('.cell-content').forEach(el => el.classList.remove('clash-state'));

            // Group by staffId
            const staffAllocations = {};
            cells.forEach(el => {
                const staffId = el.getAttribute('data-staff-id');
                if (!staffId) return;
                const day = el.getAttribute('data-day');
                const hours = el.getAttribute('data-hours');
                if (!day || !hours) return;
                const hoursArr = hours.split(',');
                const classId = el.getAttribute('data-class-id');
                
                if (!staffAllocations[staffId]) staffAllocations[staffId] = [];
                staffAllocations[staffId].push({ el, day, hours: hoursArr, classId });
            });

            Object.values(staffAllocations).forEach(allocs => {
                for (let i = 0; i < allocs.length; i++) {
                    for (let j = i + 1; j < allocs.length; j++) {
                        const a = allocs[i];
                        const b = allocs[j];
                        if (a.day === b.day && a.classId !== b.classId) {
                            if (a.hours.some(h => b.hours.includes(h))) {
                                a.el.classList.add('clash-state');
                                b.el.classList.add('clash-state');
                            }
                        }
                    }
                }
            });

            evaluateLabGroupClashes();
        }

        function evaluateLabGroupClashes() {
            // Reset lab group clash highlights
            document.querySelectorAll('.cell-content').forEach(el => el.classList.remove('lab-group-clash'));

            // Gather all lab cells (non-empty)
            const labCells = Array.from(document.querySelectorAll('.cell-content.lab-cell:not(.cell-empty)'));

            // Group by (shiftGroup, day, hourKey)
            const labGroups = {}; // key: `group_day_hour` => [{el, classId, day, hours}]
            labCells.forEach(el => {
                const classId = el.getAttribute('data-class-id');
                const day = el.getAttribute('data-day');
                const hoursStr = el.getAttribute('data-hours') || '';
                const shiftInfo = classShiftInfo[classId];
                if (!shiftInfo) return;
                const group = shiftInfo.group;

                hoursStr.split(',').forEach(hr => {
                    const key = `${group}_${day}_${hr}`;
                    if (!labGroups[key]) labGroups[key] = [];
                    labGroups[key].push(el);
                });
            });

            // Highlight any slot where multiple classes (same group) share a lab at the same time
            Object.values(labGroups).forEach(els => {
                if (els.length > 1) {
                    els.forEach(el => el.classList.add('lab-group-clash'));
                }
            });
        }

        async function handleCellClick(el) {
            const mode = currentMode;
            const slotId = el.getAttribute('data-slot-id');
            const classId = el.getAttribute('data-class-id');
            const sem = el.getAttribute('data-semester');
            const prog = el.getAttribute('data-program');
            const isManual = el.getAttribute('data-is-manual') === '1';

            if (mode === 'view') return;

            if (mode === 'swap') {
                const hoursLen = el.getAttribute('data-hours').split(',').length;
                const isUnselectingA = swapSlotA && swapSlotA.el === el;
                if (isManual && !isUnselectingA) {
                    const confirmed = await confirmManualSwap();
                    if (!confirmed) return;
                }
                if (!swapSlotA) {
                    // Select A — grey out all cells from other classes
                    document.querySelectorAll('.cell-content').forEach(e => {
                        e.classList.remove('selected-a', 'selected-b', 'clash-warning');
                        if (e.getAttribute('data-class-id') !== classId) {
                            e.classList.add('swap-disabled');
                        } else {
                            e.classList.remove('swap-disabled');
                        }
                    });
                    el.classList.add('selected-a');
                    swapSlotA = { el, slotId, classId, hoursLen };
                    
                    // Highlight red for clashes
                    document.querySelectorAll('.cell-content').forEach(e => {
                        if (e.getAttribute('data-class-id') === classId && e !== el) {
                            if (getStaffClashWarning(el, e)) {
                                e.classList.add('clash-warning');
                            }
                        }
                    });

                    showToast('First slot selected (' + hoursLen + ' hr). Now click another slot to swap.');
                } else if (swapSlotA.el === el) {
                    // Deselect
                    document.querySelectorAll('.cell-content').forEach(e =>
                        e.classList.remove('selected-a', 'selected-b', 'swap-disabled', 'clash-warning'));
                    swapSlotA = null;
                } else {
                    const reqLen = Math.max(swapSlotA.hoursLen, hoursLen);
                    
                    const cellsA = getConsecutiveCells(swapSlotA.el, reqLen);
                    const cellsB = getConsecutiveCells(el, reqLen);
                    
                    if (!cellsA || !cellsB) {
                        showToast('❌ Cannot swap: Need ' + reqLen + ' continuous hours. Invalid selection.');
                        return;
                    }
                    
                    cellsA.forEach(c => c.classList.add('selected-a'));
                    cellsB.forEach(c => c.classList.add('selected-b'));

                    let finalClashMsg = null;
                    for (let i = 0; i < cellsA.length && i < cellsB.length; i++) {
                        let cMsg = getStaffClashWarning(cellsA[i], cellsB[Math.min(i, cellsB.length-1)]);
                        if (cMsg) {
                            finalClashMsg = cMsg; break;
                        }
                    }

                    if (finalClashMsg) {
                        const confirmed = await confirmClashSwap(finalClashMsg);
                        if (!confirmed) {
                            cellsB.forEach(c => c.classList.remove('selected-b'));
                            return; 
                        }
                    }

                    // For multi-hour swaps (i.e. labs), show a confirmation of what is being swapped
                    if (reqLen > 1) {
                        const getGroupDesc = (cells) => {
                            let items = [];
                            cells.forEach(c => {
                                const title = c.getAttribute('title') || 'Empty';
                                const hrs = c.getAttribute('data-hours');
                                const day = c.getAttribute('data-day');
                                items.push(`• <b>${title}</b> on ${day} (${hrs})`);
                            });
                            // Deduplicate titles for multi-hour labs so it just shows once
                            return Array.from(new Set(items)).join('<br>');
                        };
                        const confirmed = await confirmMultiSwap(getGroupDesc(cellsA), getGroupDesc(cellsB));
                        if (!confirmed) {
                            cellsB.forEach(c => c.classList.remove('selected-b'));
                            return;
                        }
                    }

                    const allIdsA = [];
                    cellsA.forEach(c => allIdsA.push(...c.getAttribute('data-all-slot-ids').split(',')));
                    const allIdsB = [];
                    cellsB.forEach(c => allIdsB.push(...c.getAttribute('data-all-slot-ids').split(',')));
                    
                    doSwap(allIdsA, allIdsB, cellsA, cellsB);
                    
                    document.querySelectorAll('.cell-content').forEach(e => e.classList.remove('swap-disabled', 'clash-warning'));
                    swapSlotA = null;
                }
                return;
            }

            if (mode === 'edit') {
                openEditModal(el, slotId, classId, sem, prog);
            }
        }

        function doSwap(idsA, idsB, cellsA, cellsB) {
            fetch('update_slot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=swap&id_a=${idsA.join(',')}&id_b=${idsB.join(',')}`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to correctly render any merged cell differences (like colspan changing)
                        window.location.href = window.location.pathname + "?id=<?php echo $id; ?>&changed=1";
                    } else {
                        showToast('❌ Swap failed: ' + (data.error || 'Unknown error'));
                        cellsA.forEach(c => c.classList.remove('selected-a', 'selected-b'));
                        cellsB.forEach(c => c.classList.remove('selected-a', 'selected-b'));
                    }
                })
                .catch(err => {
                    showToast('❌ Error: ' + err.message);
                });
        }

        function openEditModal(el, slotId, classId, sem, prog) {
            document.getElementById('edit-slot-id').value = slotId;
            document.getElementById('edit-class-id').value = classId;
            document.getElementById('edit-semester').value = sem;
            
            // Filter subject options by semester and program
            const subjSel = document.getElementById('edit-subject');
            Array.from(subjSel.options).forEach(opt => {
                const optSem = opt.getAttribute('data-sem');
                const optProg = opt.getAttribute('data-program');
                if (!optSem) {
                    opt.style.display = '';
                } else {
                    opt.style.display = (optSem == sem && optProg == prog) ? '' : 'none';
                }
            });
            subjSel.value = '';
            subjSel.dispatchEvent(new Event('change'));

            // Find which staff are busy at this slot's day + hours
            const day = el.getAttribute('data-day');
            const hours = (el.getAttribute('data-hours') || '').split(',');
            const busyStaffIds = new Set();
            const busyStaffToClass = {};

            document.querySelectorAll('.cell-content').forEach(other => {
                if (other.getAttribute('data-class-id') === classId) return; // same class is OK
                if (other.getAttribute('data-day') !== day) return;
                const staffId = other.getAttribute('data-staff-id');
                if (!staffId) return;
                const otherHours = (other.getAttribute('data-hours') || '').split(',');
                if (hours.some(h => otherHours.includes(h))) {
                    busyStaffIds.add(staffId);
                    const classCell = other.closest('tr') ? other.closest('tr').querySelector('.class-cell') : null;
                    busyStaffToClass[staffId] = classCell ? classCell.textContent.trim() : 'another class';
                }
            });

            // Colour staff dropdown options: red for busy, normal for free
            const staffSel = document.getElementById('edit-staff');
            Array.from(staffSel.options).forEach(opt => {
                const valStr = opt.value ? String(opt.value) : '';
                if (busyStaffIds.has(valStr)) {
                    opt.style.color = '#dc2626';
                    opt.style.fontWeight = '700';
                    opt.title = `⚠️ Already has class at this time (${busyStaffToClass[valStr]})`;
                } else {
                    opt.style.color = '';
                    opt.style.fontWeight = '';
                    opt.title = '';
                }
            });

            // Find labs already allocated to this class on this day (excluding this slot)
            const labsOnDay = []; // { subjectTitle, hours }
            document.querySelectorAll('.cell-content').forEach(other => {
                if (other.getAttribute('data-slot-id') === slotId) return; // skip current slot
                if (other.getAttribute('data-class-id') !== classId) return; // same class only
                if (other.getAttribute('data-day') !== day) return;
                if (other.classList.contains('cell-empty')) return;
                
                // Check if this other cell is a lab type
                // We detect labs by the lab-cell CSS class or by checking slot data attr
                const isLab = other.classList.contains('lab-cell');
                if (isLab) {
                    const labTitle = other.getAttribute('title') || other.textContent.trim();
                    const labHours = other.getAttribute('data-hours') || '';
                    labsOnDay.push({ title: labTitle, hours: labHours });
                }
            });

            // Mark Lab subject options in red if any lab already on this day
            if (labsOnDay.length > 0) {
                const labDesc = labsOnDay.map(l => `${l.title || 'Lab'} at ${l.hours}`).join(', ');
                Array.from(subjSel.options).forEach(opt => {
                    if (opt.getAttribute('data-type') === 'Lab') {
                        opt.style.color = '#dc2626';
                        opt.style.fontWeight = '700';
                        opt.title = `⚠️ Lab already allocated on ${day} (${labDesc})`;
                    }
                });
            } else {
                Array.from(subjSel.options).forEach(opt => {
                    if (opt.getAttribute('data-type') === 'Lab') {
                        opt.style.color = '';
                        opt.style.fontWeight = '';
                        opt.title = '';
                    }
                });
            }

            document.getElementById('edit-modal').classList.add('open');
        }

        function closeModal() {
            document.getElementById('edit-modal').classList.remove('open');
        }

        async function saveSlotEdit() {
            const slotId = document.getElementById('edit-slot-id').value;
            const classId = document.getElementById('edit-class-id').value;
            const subjSel = document.getElementById('edit-subject');
            const staffSel = document.getElementById('edit-staff');
            const subjOpt = subjSel.options[subjSel.selectedIndex];
            const staffOpt = staffSel.options[staffSel.selectedIndex];

            const subjectId = subjSel.value;
            const subjectTitle = subjOpt.getAttribute('data-title') || '';
            const subjectType = subjOpt.getAttribute('data-type') || '';
            const subjectShort = ''; // Generated on backend now

            const staffId = staffSel.value;
            const staffCode = staffOpt.getAttribute('data-code') || '';
            const staffName = staffOpt.getAttribute('data-name') || '';

            // Lab conflict check: if subject is Lab, check if class already has a lab on the same day
            if (subjectType === 'Lab' && subjectId) {
                const cellEl = document.querySelector(`.cell-content[data-slot-id="${slotId}"]`);
                if (cellEl) {
                    const day = cellEl.getAttribute('data-day');
                    let labConflictMsg = null;
                    
                    document.querySelectorAll('.cell-content').forEach(other => {
                        if (other.getAttribute('data-slot-id') === slotId) return;
                        if (other.getAttribute('data-class-id') !== classId) return;
                        if (other.getAttribute('data-day') !== day) return;
                        if (other.classList.contains('cell-empty')) return;
                        if (other.classList.contains('lab-cell')) {
                            const labTitle = other.getAttribute('title') || other.textContent.replace(/\s+/g, ' ').trim();
                            const hoursAttr = other.getAttribute('data-hours') || '';
                            labConflictMsg = `${subjectTitle} cannot be allocated — ${labTitle} is already allocated on ${day} (${hoursAttr}). Do you want to continue?`;
                        }
                    });

                    if (labConflictMsg) {
                        const confirmed = await confirmClashSwap(labConflictMsg);
                        if (!confirmed) return;
                    }
                }
            }

            // Check for clashes before saving if a staff is selected
            if (staffId) {
                const cellEl = document.querySelector(`.cell-content[data-slot-id="${slotId}"]`);
                if (cellEl) {
                    const day = cellEl.getAttribute('data-day');
                    const hours = cellEl.getAttribute('data-hours').split(',');
                    let clashMsg = null;
                    
                    // Look for clashes in other cells
                    document.querySelectorAll('.cell-content').forEach(el => {
                        if (el.getAttribute('data-class-id') === classId) return; // same class is fine
                        if (el.getAttribute('data-staff-id') !== staffId) return; // different staff
                        if (el.getAttribute('data-day') !== day) return; // different day
                        const elHours = el.getAttribute('data-hours').split(',');
                        if (hours.some(h => elHours.includes(h))) {
                            clashMsg = `${staffName} is already having hours here (${el.closest('tr').querySelector('.class-cell').textContent.trim()} at ${hours.join(', ')}).`;
                        }
                    });

                    if (clashMsg) {
                        const confirmed = await confirmClashSwap(clashMsg);
                        if (!confirmed) return; // Cancel save
                    }
                }
            }

            // Proceed to save
            fetch('update_slot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update&slot_id=${slotId}&subject_id=${encodeURIComponent(subjectId)}&subject_title=${encodeURIComponent(subjectTitle)}&subject_short=${encodeURIComponent(subjectShort)}&subject_type=${encodeURIComponent(subjectType)}&staff_id=${staffId}&staff_name=${encodeURIComponent(staffName)}&staff_code=${encodeURIComponent(staffCode)}`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const cellEl = document.querySelector(`.cell-content[data-slot-id="${slotId}"]`);
                        if (cellEl) {
                            if (subjectId) {
                                cellEl.classList.remove('cell-empty');
                                cellEl.innerHTML = `${data.rendered_short_name || subjectTitle}<br><small>${staffCode ? '(' + staffCode + ')' : ''}</small>`;
                                cellEl.setAttribute('data-staff-id', staffId);
                                cellEl.setAttribute('data-staff-name', staffName);
                                // Update lab-cell class based on subject type
                                if (subjectType === 'Lab') {
                                    cellEl.classList.add('lab-cell');
                                } else {
                                    cellEl.classList.remove('lab-cell');
                                }
                            } else {
                                cellEl.classList.add('cell-empty');
                                cellEl.classList.remove('lab-cell');
                                cellEl.innerHTML = '<span style="font-size:10px;">-</span>';
                                cellEl.setAttribute('data-staff-id', '');
                                cellEl.setAttribute('data-staff-name', '');
                            }
                        }
                        evaluateAllClashes();
                        hasUnsavedChanges = true;
                        closeModal();
                        showToast('✅ Slot updated!');
                    } else {
                        showToast('❌ Update failed');
                    }
                });
        }

        async function clearSlot() {
            const confirmed = await confirmClearSlot();
            if (!confirmed) return;

            const slotId = document.getElementById('edit-slot-id').value;
            fetch('update_slot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=clear&slot_id=${slotId}`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const cellEl = document.querySelector(`.cell-content[data-slot-id="${slotId}"]`);
                        if (cellEl) {
                            cellEl.classList.add('cell-empty');
                            cellEl.classList.remove('lab-cell', 'lab-group-clash', 'clash-state');
                            cellEl.innerHTML = '<span style="font-size:10px;">-</span>';
                            cellEl.setAttribute('data-staff-id', '');
                            cellEl.setAttribute('data-staff-name', '');
                        }
                        evaluateAllClashes();
                        hasUnsavedChanges = true;
                        closeModal();
                        showToast('✅ Slot cleared!');
                    }
                });
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.display = 'block';
            setTimeout(() => { t.style.display = 'none'; }, 3000);
        }

        // Handle subject change to disable staff dropdown for certain types
        document.getElementById('edit-subject').addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const type = opt.getAttribute('data-type') || '';
            const staffSel = document.getElementById('edit-staff');
            
            const noStaffTypes = ['Common', 'Allied', 'NM', 'Non Major Elective'];
            if (noStaffTypes.includes(type)) {
                staffSel.value = '';
                staffSel.disabled = true;
            } else {
                staffSel.disabled = false;
            }
        });

        // Close modal on overlay click
        document.getElementById('edit-modal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        function printWholeTimetablePopup() {
            var content = document.getElementById('whole-tt').outerHTML;
            var win = window.open('', '_blank', 'width=900,height=900,scrollbars=yes');
            win.document.write('<!DOCTYPE html><html><head><title><?php echo htmlspecialchars($header["name"]); ?> - <?php echo ucfirst($header["semester"]); ?> Semester</title>');
            win.document.write('<style>');
            win.document.write('* { box-sizing: border-box; margin: 0; padding: 0; }');
            win.document.write('body { font-family: Arial, sans-serif; padding: 6px; font-size: 8px; }');
            win.document.write('table { border-collapse: collapse; width: 100%; table-layout: fixed; }');
            win.document.write('td, th { border: 1px solid #333; padding: 2px 3px; word-break: break-word; overflow-wrap: break-word; text-align: center; font-size: 7px; }');
            win.document.write('td:nth-child(1) { width: 8%; font-weight: bold; }');
            win.document.write('td:nth-child(2) { width: 11%; text-align: center; }');
            win.document.write('td:nth-child(3), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7) { width: 16.2%; }');
            win.document.write('.cell-content { display: block; width: 100%; }');
            win.document.write('.cell-empty { color: #999; }');
            win.document.write('.lab-cell { color: #b45309; font-style: italic; }');
            win.document.write('@media print {');
            win.document.write('  @page { size: A4 portrait; margin: 6mm; }');
            win.document.write('  body { margin: 0; padding: 0; font-size: 7px; }');
            win.document.write('  table { page-break-inside: auto; width: 100%; }');
            win.document.write('  tr { page-break-inside: avoid; page-break-after: auto; }');
            win.document.write('  td { font-size: 6.5px !important; padding: 1.5px 2px !important; }');
            win.document.write('}');
            win.document.write('</style></head><body>');
            win.document.write(content);
            win.document.write('<script>setTimeout(function(){ window.print(); }, 800);<\/script>');
            win.document.write('</body></html>');
            win.document.close();
        }

        // Navigation and Exit Handlers
        function handleBackAction() {
            if (hasUnsavedChanges) {
                document.getElementById('unsaved-back-modal').classList.add('open');
            } else {
                window.location.href = 'saved_timetable.php';
            }
        }

        function closeUnsavedBackModal() {
            document.getElementById('unsaved-back-modal').classList.remove('open');
        }

        function confirmSaveAndExit() {
            document.getElementById('save-confirm-modal').classList.add('open');
        }

        function proceedSaveAndExit() {
            document.getElementById('save-confirm-modal').classList.remove('open');
            handleSaveAndExit();
        }

        function handleSaveAndExit() {
            const clashCells = Array.from(document.querySelectorAll('.cell-content.clash-state'));
            const labClashCells = Array.from(document.querySelectorAll('.cell-content.lab-group-clash'));
            const exitAnywayBtn = document.getElementById('exit-anyway-btn');
            const exitTitle = document.getElementById('exit-clash-title');

            if (labClashCells.length > 0) {
                const labMessages = new Set();
                labClashCells.forEach(el => {
                    const className = el.closest('tr').querySelector('.class-cell').textContent.trim();
                    const day = el.getAttribute('data-day');
                    const hours = el.getAttribute('data-hours').replace(/,/g, ' & ');
                    labMessages.add(`• <strong>${className}</strong> — Lab clash on ${day} (${hours})`);
                });

                let html = '<p style="color:#92400e; font-weight:700; margin-bottom:6px;">🟠 Lab Group Clashes (must fix before exit):</p>' +
                           Array.from(labMessages).join('<br>');

                if (clashCells.length > 0) {
                    const staffMsgs = new Set();
                    clashCells.forEach(el => {
                        staffMsgs.add(`• <strong>${el.getAttribute('data-staff-name')}</strong> in ${el.closest('tr').querySelector('.class-cell').textContent.trim()} on ${el.getAttribute('data-day')} (${el.getAttribute('data-hours').replace(/,/g, ' & ')})`);
                    });
                    html += '<br><br><p style="color:#b91c1c; font-weight:700; margin-bottom:6px;">🔴 Staff Clashes:</p>' + Array.from(staffMsgs).join('<br>');
                }

                document.getElementById('exit-clash-list').innerHTML = html;
                document.getElementById('exit-clash-modal').setAttribute('data-lab-block', '1');
                exitTitle.textContent = '⚠️ Lab Group Clashes Must Be Fixed';
                exitAnywayBtn.style.display = 'none'; // Block exit
                document.getElementById('exit-clash-modal').classList.add('open');

            } else if (clashCells.length > 0) {
                const clashMessages = new Set();
                clashCells.forEach(el => {
                    clashMessages.add(`• <strong>${el.getAttribute('data-staff-name')}</strong> in ${el.closest('tr').querySelector('.class-cell').textContent.trim()} on ${el.getAttribute('data-day')} (${el.getAttribute('data-hours').replace(/,/g, ' & ')})`);
                });

                document.getElementById('exit-clash-modal').setAttribute('data-lab-block', '0');
                exitTitle.textContent = '⚠️ Unresolved Staff Clashes';
                document.getElementById('exit-clash-list').innerHTML = Array.from(clashMessages).join('<br>');
                exitAnywayBtn.style.display = ''; // Allow exit with confirmation
                document.getElementById('exit-clash-modal').classList.add('open');
            } else {
                forceSaveAndExit();
            }
        }

        function closeExitClashModal() {
            document.getElementById('exit-clash-modal').classList.remove('open');
        }

        function forceSaveAndExit() {
            // If lab group clashes block, do not allow exit
            if (document.getElementById('exit-clash-modal').getAttribute('data-lab-block') === '1') {
                showToast('❌ Please fix Lab Group Clashes before exiting.');
                return;
            }
            hasUnsavedChanges = false;
            // Clear the backup so next edit takes a fresh snapshot
            fetch('update_slot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=commit&id=<?php echo $id; ?>'
            }).then(() => {
                window.location.href = 'saved_timetable.php';
            });
        }

        function discardChanges() {
            const btn = document.getElementById('btn-discard');
            if(btn) {
                btn.textContent = 'Discarding...';
                btn.disabled = true;
            }
            hasUnsavedChanges = false;
            fetch('update_slot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=discard&id=<?php echo $id; ?>'
            }).then(() => {
                window.location.href = 'saved_timetable.php';
            });
        }

        // Run clash check right away
        document.addEventListener('DOMContentLoaded', evaluateAllClashes);
    </script>
</body>

</html>
<?php $conn->close(); ?>
