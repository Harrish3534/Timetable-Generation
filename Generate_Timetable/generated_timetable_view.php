<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Read stored timetable from session
$timetables = isset($_SESSION['current_timetables']) ? $_SESSION['current_timetables'] : [];
$semester_filter = isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd';

$days = ['I DAY', 'II DAY', 'III DAY', 'IV DAY', 'V DAY', 'VI DAY'];
$hours = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];

$has_timetable = !empty($timetables);

// Build whole-timetable data structure for "Print Whole Timetable"
$whole_tt_rows = [];
$class_labels_ordered = [];
if ($has_timetable) {
    foreach ($timetables as $tt_data) {
        $cls = $tt_data['class'];
        $shift_label = (strpos($cls['shift'], 'Shift 1') !== false) ? 'SI' : ((strpos($cls['shift'], 'Shift 2') !== false) ? 'SII' : '');
        if (strpos($cls['name'], 'M.Sc') !== false) {
            preg_match('/^(I+|IV|V?I*) M\.Sc/', $cls['name'], $m);
            $yr = isset($m[1]) ? $m[1] : '';
            $label = $yr . ' M.Sc';
        }
        else {
            preg_match('/^(I+|IV|V?I*) B\.Sc/', $cls['name'], $m);
            $yr = isset($m[1]) ? $m[1] : '';
            $label = $yr . ' B.Sc ' . $shift_label;
        }
        $class_labels_ordered[] = $label;
        foreach ($days as $dy) {
            $whole_tt_rows[$dy][$label] = isset($tt_data['timetable'][$dy]) ? $tt_data['timetable'][$dy] : [];
        }
    }
}
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
            .no-print { display: none !important; }
            .timetable-container { page-break-after: always; }
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

        /* Empty state */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 380px;
            text-align: center;
            padding: 40px 20px;
        }
        .empty-state-icon {
            font-size: 72px;
            margin-bottom: 20px;
            line-height: 1;
        }
        .empty-state h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .empty-state p {
            font-size: 16px;
            color: #6b7280;
            max-width: 420px;
            line-height: 1.6;
            margin-bottom: 28px;
        }
        .empty-state .btn-go {
            background: #2563eb;
            color: white;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
        }
        .empty-state .btn-go:hover { background: #1d4ed8; }

        /* Semester info bar */
        .semester-info-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            padding: 10px 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
        }
        .semester-info-bar span { font-size: 18px; }
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
        <a href="redirect_timetable.php" class="tab">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
        <a href="generated_timetable_view.php" class="tab active">
            <span class="tab-icon">📊</span> Generated Timetable
        </a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab">
            <span class="tab-icon">💾</span> Saved Timetables
        </a>
    </div>

    <div class="content">
        <?php if ($has_timetable): ?>

        <!-- Action buttons -->
        <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Timetables</button>
            <button onclick="printWholeTimetable()" class="btn btn-primary" style="background:#28a745; border-color:#28a745;">📋 Print Whole Timetable</button>
            <button onclick="openSaveDialog()" class="btn btn-primary" style="background:#7c3aed; border-color:#7c3aed;">💾 Save Timetable</button>
            <button onclick="window.location.href='timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">← Back to Setup</button>
            <button onclick="window.location.href='generate_timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">🔄 Regenerate</button>
            <button onclick="window.location.href='staff_timetable.php?semester=<?php echo $semester_filter; ?>'"
                class="btn btn-secondary">👥 Staff Timetable</button>
        </div>

        <!-- Semester badge -->
        <div class="semester-info-bar no-print">
            <span>📊</span>
            Last generated: <strong><?php echo ucfirst($semester_filter); ?> Semester</strong>
            &nbsp;|&nbsp; <?php echo count($timetables); ?> class(es)
        </div>

        <!-- Save dialog overlay -->
        <div id="save-dialog" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:white; border-radius:12px; padding:28px; width:380px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <h3 style="margin:0 0 16px; color:#1f2937;">💾 Save Timetable</h3>
                <label style="display:block; font-weight:600; font-size:13px; color:#374151; margin-bottom:6px;">Timetable Name</label>
                <input type="text" id="save-name" placeholder="e.g. Odd Semester 2025-26" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; box-sizing:border-box;">
                <div id="save-error" style="color:#ef4444; font-size:12px; margin-top:6px; display:none;"></div>
                <div style="display:flex; gap:10px; margin-top:18px;">
                    <button onclick="doSaveTimetable()" style="background:#7c3aed; color:white; border:none; padding:10px 22px; border-radius:6px; cursor:pointer; font-weight:600; font-size:14px;">💾 Save</button>
                    <button onclick="closeSaveDialog()" style="background:#f3f4f6; color:#374151; border:none; padding:10px 18px; border-radius:6px; cursor:pointer; font-weight:600; margin-left:auto;">Cancel</button>
                </div>
            </div>
        </div>
        <div id="save-toast" style="display:none; position:fixed; bottom:24px; right:24px; background:#1f2937; color:white; padding:12px 24px; border-radius:8px; font-size:14px; font-weight:600; z-index:99999;"></div>

        <script>
        function openSaveDialog() {
            document.getElementById('save-dialog').style.display = 'flex';
            setTimeout(() => document.getElementById('save-name').focus(), 100);
        }
        function closeSaveDialog() {
            document.getElementById('save-dialog').style.display = 'none';
            document.getElementById('save-error').style.display = 'none';
        }
        function doSaveTimetable() {
            const name = document.getElementById('save-name').value.trim();
            if (!name) {
                const err = document.getElementById('save-error');
                err.textContent = 'Please enter a name.';
                err.style.display = 'block';
                return;
            }
            const btn = event.target;
            btn.disabled = true; btn.textContent = 'Saving...';
            const fd = new FormData();
            fd.append('name', name);
            fd.append('semester', '<?php echo $semester_filter; ?>');
            fetch('save_timetable.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.textContent = '💾 Save';
                closeSaveDialog();
                if (data.success) {
                    const t = document.getElementById('save-toast');
                    t.textContent = '✅ Timetable saved! Redirecting...';
                    t.style.display = 'block';
                    setTimeout(() => { window.location.href = '../SavedTimetable/saved_timetable.php'; }, 1500);
                } else {
                    alert('Save failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                btn.disabled = false; btn.textContent = '💾 Save';
                alert('Error: ' + err.message);
            });
        }
        document.getElementById('save-name') && document.getElementById('save-name').addEventListener('keydown', e => { if (e.key === 'Enter') doSaveTimetable(); });
        </script>

        <!-- Hidden whole-timetable for print popup -->
        <div id="whole-timetable-content" style="display:none;">
            <table border="1" style="border-collapse:collapse; width:100%; font-family:Arial,sans-serif; font-size:11px;">
                <thead>
                    <tr>
                        <td colspan="7" style="background:#1a3a6b; color:#fff; padding:6px; text-align:center; font-size:14px; font-weight:bold;">Government Arts College(Autonomous), Coimbatore-18</td>
                    </tr>
                    <tr>
                        <td colspan="7" style="background:#1a3a6b; color:#fff; padding:4px; text-align:center; font-size:12px; font-weight:bold;">PG &amp; Research Department of Computer Science</td>
                    </tr>
                    <tr>
                        <td colspan="7" style="background:#1a3a6b; color:#fff; padding:4px; text-align:center; font-size:12px; font-weight:bold;">Time Table <?php echo ucfirst($semester_filter); ?> Semester</td>
                    </tr>
                    <tr style="background:#c8e6c9; font-weight:bold; text-align:center; font-size:11px;">
                        <td style="padding:4px 6px; border:1px solid #333;">HOUR/DAY</td>
                        <td style="padding:4px 6px; border:1px solid #333;">CLASS</td>
                        <td style="padding:4px 6px; border:1px solid #333;">I HOUR</td>
                        <td style="padding:4px 6px; border:1px solid #333;">II HOUR</td>
                        <td style="padding:4px 6px; border:1px solid #333;">III HOUR</td>
                        <td style="padding:4px 6px; border:1px solid #333;">IV HOUR</td>
                        <td style="padding:4px 6px; border:1px solid #333;">V HOUR</td>
                    </tr>
                </thead>
                <tbody>
                <?php
    $hours_list = ['I HOUR', 'II HOUR', 'III HOUR', 'IV HOUR', 'V HOUR'];
    $day_bg = ['I DAY' => '#fff9c4', 'II DAY' => '#e8f5e9', 'III DAY' => '#e3f2fd', 'IV DAY' => '#fce4ec', 'V DAY' => '#f3e5f5', 'VI DAY' => '#fff3e0'];
    foreach ($days as $dy):
        $classes_in_day = isset($whole_tt_rows[$dy]) ? $whole_tt_rows[$dy] : [];
        $valid_classes = [];
        foreach ($class_labels_ordered as $clabel) {
            if (isset($classes_in_day[$clabel]))
                $valid_classes[] = $clabel;
        }
        $class_count = count($valid_classes);
        if ($class_count === 0)
            continue;
        $first_class = true;
        $bg = isset($day_bg[$dy]) ? $day_bg[$dy] : '#fff';
        foreach ($valid_classes as $clabel):
            $row_hours = $classes_in_day[$clabel];
            $i = 0;
            $cells = [];
            while ($i < count($hours_list)) {
                $h = $hours_list[$i];
                $subj = isset($row_hours[$h]) ? $row_hours[$h] : null;
                if ($subj && isset($subj['type']) && $subj['type'] === 'Lab') {
                    $span = 1;
                    while ($span + $i < count($hours_list)) {
                        $nh = $hours_list[$i + $span];
                        $ns = isset($row_hours[$nh]) ? $row_hours[$nh] : null;
                        if ($ns && isset($ns['id']) && $ns['id'] === $subj['id']) {
                            $span++;
                        }
                        else {
                            break;
                        }
                    }
                    $dashes = str_repeat('-', max(3, 8 - $span));
                    $txt = 'ß' . $dashes . ' ' . $subj['short_name'];
                    if (isset($subj['staff_code']))
                        $txt .= '(' . $subj['staff_code'] . ')';
                    $txt .= ' ' . $dashes . 'à';
                    $cells[] = ['text' => $txt, 'colspan' => $span, 'is_lab' => true];
                    $i += $span;
                }
                else {
                    $txt = $subj && isset($subj['short_name']) ? $subj['short_name'] : '-';
                    if ($subj && isset($subj['staff_code']))
                        $txt .= '(' . $subj['staff_code'] . ')';
                    $cells[] = ['text' => $txt, 'colspan' => 1, 'is_lab' => false];
                    $i++;
                }
            }
?>
                    <tr style="background:<?php echo $bg; ?>; font-weight:bold; color:#003366;">
                        <?php if ($first_class): ?>
                        <td rowspan="<?php echo $class_count; ?>" style="border:1px solid #333; padding:4px 8px; text-align:center; font-weight:bold; background:<?php echo $bg; ?>; vertical-align:middle; font-size:11px;">
                            <?php echo htmlspecialchars($dy); ?>
                        </td>
                        <?php $first_class = false;
            endif; ?>
                        <td style="border:1px solid #333; padding:3px 5px; white-space:nowrap; font-size:10px;"><?php echo htmlspecialchars($clabel); ?></td>
                        <?php foreach ($cells as $cell): ?>
                        <td colspan="<?php echo $cell['colspan']; ?>" style="border:1px solid #333; padding:3px 4px; text-align:center; font-size:10px; <?php echo $cell['is_lab'] ? 'color:#b45309; font-style:italic;' : ''; ?>">
                            <?php echo htmlspecialchars($cell['text']); ?>
                        </td>
                        <?php
            endforeach; ?>
                    </tr>
                <?php
        endforeach;
    endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
        function printWholeTimetable() {
            var content = document.getElementById('whole-timetable-content').innerHTML;
            var win = window.open('', '_blank', 'width=900,height=900,scrollbars=yes');
            win.document.write('<!DOCTYPE html><html><head><title>Whole Timetable - <?php echo ucfirst($semester_filter); ?> Semester</title>');
            win.document.write('<style>');
            win.document.write('* { box-sizing: border-box; margin: 0; padding: 0; }');
            win.document.write('body { font-family: Arial, sans-serif; padding: 6px; font-size: 8px; }');
            win.document.write('table { border-collapse: collapse; width: 100%; table-layout: fixed; }');
            win.document.write('td, th { border: 1px solid #333; padding: 2px 3px; word-break: break-word; overflow-wrap: break-word; }');
            win.document.write('td:nth-child(1) { width: 8%; text-align: center; font-weight: bold; font-size: 7px; }');
            win.document.write('td:nth-child(2) { width: 11%; font-size: 7px; text-align: center; }');
            win.document.write('td:nth-child(3), td:nth-child(4), td:nth-child(5), td:nth-child(6), td:nth-child(7) { width: 16.2%; font-size: 7px; text-align: center; }');
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
        </script>

        <!-- Individual timetable tables -->
        <?php foreach ($timetables as $tt_data): ?>
            <div class="timetable-container">
                <div class="timetable-title">
                    <h2>Government Arts College (Autonomous), Coimbatore-18</h2>
                    <h3>PG & Research Department of Computer Science</h3>
                    <p>Time Table <?php echo ucfirst($semester_filter); ?> Semester</p>
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
                                                <?php
                    endif; ?>
                                            </div>
                                        <?php
                else: ?>
                                            <div>-</div>
                                        <?php
                endif; ?>
                                    </td>
                                <?php
            endforeach; ?>
                            </tr>
                        <?php
        endforeach; ?>
                    </tbody>
                </table>
                <?php if (isset($tt_data['has_gaps']) && $tt_data['has_gaps']): ?>
                    <div class="warning-banner no-print">
                        ⚠️ Warning: Some cells cannot be filled because of staff allocation constraints.
                    </div>
                <?php
        endif; ?>
            </div>
        <?php
    endforeach; ?>

        <?php
else: ?>
        <!-- Empty state -->
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <h2>No Timetable Generated Yet</h2>
            <p>You haven't generated a timetable in this session. Go to <strong>Class Timetable</strong> to allocate staff and generate your timetable.</p>
            <a href="redirect_timetable.php" class="btn-go">
                📅 Go to Class Timetable
            </a>
        </div>
        <?php
endif; ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
