<?php
require_once '../Config/config.php';
checkLogin();

// Always serve fresh data - no browser caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$conn = getConnection();

// Short name helper (mirrors timetable generator logic)
function getSubjectShortName($title, $type, $semester = null) {
    if ($type === 'Allied') return 'ALLIED';
    if ($type === 'NME')    return 'NME';
    if ($type === 'Project') return 'PROJ';

    $t = strtolower($title);

    if (($semester == 5 || $semester == 6) && stripos($t, 'elective') !== false)
        return 'NME';

    // Lab-specific overrides
    if ($type === 'Lab') {
        if (stripos($t, 'dip lab') !== false || stripos($t, 'digital image') !== false) return 'DIP LAB';
        if (stripos($t, 'image processing') !== false || stripos($t, 'dip') !== false) return 'DIP LAB';
        if (stripos($t, 'database') !== false || stripos($t, 'dbms') !== false) return 'DBMS LAB';
    }

    $mappings = [
        'theory of computation and compiler' => 'PCD',
        'programming methodology'            => 'PM',
        'digital computer fundamentals'      => 'DCF',
        'digital image processing'           => 'DIP',
        'cloud computing'                    => 'CC',
        'web programming essentials'         => 'WPE',
        'mobile application development'     => 'MAD',
        'software engineering'               => 'SE',
        'data structures and algorithms'     => 'DSA',
        'data structures'                    => 'DS',
        'internet technologies'              => 'IT',
        'operating system'                   => 'OS',
        'computer graphics'                  => 'CG',
        'computer networks'                  => 'CN',
        'database management systems'        => 'DBMS',
        'database management system'         => 'DBMS',
        'data communication and networks'    => 'DC & CN',
        'advanced java programming'          => 'AJP',
        'computer system architecture'       => 'CSA',
        'linux shell programming'            => 'LINUX',
        'c++ programming'                    => 'C++',
        'c# programming'                     => 'C#',
        'value education'                    => 'VE',
        'python programming'                 => 'PYTHON',
        'algorithms'                         => 'ALG',
        'open source computing'              => 'OSC',
        'ai and machine learning'            => 'AI',
        'data science using python'          => 'DS',
        'data mining with r'                 => 'DM&R',
        'open source technology'             => 'OST',
        'programming in java'                => 'JAVA',
        'java programming'                   => 'JAVA',
        'environmental studies'              => 'EVS',
        'environmental science'              => 'EVS',
        'tamil'                              => 'TAMIL',
        'english'                            => 'ENGLISH',
        'naan mudhalvan'                     => 'NM',
        'elective'                           => 'ELECTIVE',
        'project viva voce'                  => 'PROJ',
        'project & viva voce'                => 'PROJ',
    ];

    foreach ($mappings as $key => $value) {
        if (stripos($t, $key) !== false)
            return $type === 'Lab' ? $value . ' LAB' : $value;
    }

    // Abbreviation in parentheses
    if (preg_match('/\(([^)]+)\)/', $title, $matches)) {
        $s = strtoupper($matches[1]);
        return $type === 'Lab' ? $s . ' LAB' : $s;
    }

    // Auto-abbreviate lab names
    if ($type === 'Lab') {
        $words = explode(' ', $title);
        $abbrev = '';
        foreach ($words as $w) {
            if (strlen($w) > 2 && !in_array(strtolower($w), ['lab','using','and','the','of','in']))
                $abbrev .= strtoupper(substr($w, 0, 1));
        }
        return $abbrev ? $abbrev . ' LAB' : 'LAB';
    }

    // Fallback: first letters of each significant word
    $words = explode(' ', $title);
    $abbrev = '';
    foreach ($words as $w) {
        if (strlen($w) > 2 && !in_array(strtolower($w), ['and','the','of','in','with','for']))
            $abbrev .= strtoupper(substr($w, 0, 1));
    }
    return $abbrev ?: strtoupper($title);
}

// Ensure is_allocated column exists (safe migration)
$col_check = $conn->query("SHOW COLUMNS FROM subjects LIKE 'is_allocated'");
if ($col_check->num_rows == 0) {
    $conn->query("ALTER TABLE subjects ADD COLUMN is_allocated tinyint(1) NOT NULL DEFAULT 1");
}

// Ensure short_name column exists (safe migration)
$col_check2 = $conn->query("SHOW COLUMNS FROM subjects LIKE 'short_name'");
if ($col_check2->num_rows == 0) {
    $conn->query("ALTER TABLE subjects ADD COLUMN short_name VARCHAR(50) NULL AFTER sub_code");
    // Pre-fill existing rows using the helper function logic so it's not empty immediately
    $all_subj = $conn->query("SELECT id, title, type, semester FROM subjects WHERE short_name IS NULL OR short_name = ''");
    while($row = $all_subj->fetch_assoc()) {
        $sn = getSubjectShortName($row['title'], $row['type'], $row['semester']);
        $sn_esc = $conn->real_escape_string($sn);
        $conn->query("UPDATE subjects SET short_name = '$sn_esc' WHERE id = " . $row['id']);
    }
}

// One-time: default-deallocate the two Open Source Technology subjects (PG Sem 4)
// Uses a flag file so this runs exactly once even if column already existed
$flag = __DIR__ . '/../Database/.ost_dealloc_done';
if (!file_exists($flag)) {
    $conn->query("UPDATE subjects SET is_allocated = 0 WHERE sub_code IN ('21MCS41C', '21MCS42P')");
    file_put_contents($flag, '1');
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subjects WHERE id = $id");
    $semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
    $type = isset($_GET['type']) ? $_GET['type'] : 'UG';
    header("Location: subject.php?type=$type&semester=$semester");
    exit();
}

// Handle allocate/deallocate toggle
if (isset($_GET['toggle_allocate'])) {
    $id = intval($_GET['toggle_allocate']);
    $semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
    $type = isset($_GET['type']) ? $_GET['type'] : 'UG';
    $conn->query("UPDATE subjects SET is_allocated = IF(is_allocated = 1, 0, 1) WHERE id = $id");
    header("Location: subject.php?type=$type&semester=$semester");
    exit();
}

$semester = isset($_GET['semester']) ? intval($_GET['semester']) : 1;
$type = isset($_GET['type']) ? $_GET['type'] : 'UG';

// Determine Odd/Even state. If not explicitly set, infer from the current semester
if (isset($_GET['sem_type'])) {
    $sem_type = $_GET['sem_type'];
}
else {
    $sem_type = ($semester % 2 == 0) ? 'even' : 'odd';
}

// Query to get subjects based on program (UG/PG) and semester
$query = "SELECT * FROM subjects WHERE program = '$type' AND semester = $semester ORDER BY sort_order, id";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subjects - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=1773498759">
    <style>
        .row-deallocated td:not(:last-child) {
            opacity: 0.45;
        }
        .badge-short {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 2px 7px;
            border-radius: 4px;
            border: 1px solid #bfdbfe;
            margin-left: 7px;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">GAC Timetable</div>
        <div class="nav-user">
            Welcome,
            <?php echo htmlspecialchars($_SESSION['user_name']); ?>
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
        <a href="subject.php" class="tab active">
            <span class="tab-icon">📚</span> Subjects
        </a>
        <a href="../Generate_Timetable/redirect_timetable.php" class="tab">
            <span class="tab-icon">📅</span> Class Timetable
        </a>
        <a href="../Generate_Timetable/generated_timetable_view.php" class="tab">
            <span class="tab-icon">📊</span> Generated Timetable
        </a>
        <a href="../SavedTimetable/saved_timetable.php" class="tab">
            <span class="tab-icon">💾</span> Saved Timetables
        </a>
    </div>

    <div class="content">
        <div class="page-title">
            <h2>Subject Details</h2>
            <div class="filter-controls">
                <button class="btn <?php echo $type == 'UG' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=UG&semester=<?php echo $sem_type == 'even' ? 2 : 1; ?>&sem_type=<?php echo $sem_type; ?>'">UG</button>
                <button class="btn <?php echo $type == 'PG' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=PG&semester=<?php echo $sem_type == 'even' ? 2 : 1; ?>&sem_type=<?php echo $sem_type; ?>'">PG</button>
                
                <button class="btn <?php echo $sem_type == 'odd' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=<?php echo $type; ?>&semester=1&sem_type=odd'" style="margin-left: 10px;">Odd</button>
                <button class="btn <?php echo $sem_type == 'even' ? 'btn-primary' : 'btn-secondary'; ?>"
                    onclick="window.location.href='subject.php?type=<?php echo $type; ?>&semester=2&sem_type=even'">Even</button>

                <select class="filter-select"
                    onchange="window.location.href='subject.php?type=<?php echo $type; ?>&sem_type=<?php echo $sem_type; ?>&semester=' + this.value">
                    <?php
$max_sem = ($type == 'UG') ? 6 : 4;
$start_sem = ($sem_type == 'even') ? 2 : 1;

// Loop increments by 2 to skip odd/even depending on selection
for ($i = $start_sem; $i <= $max_sem; $i += 2):
?>
                        <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                    <?php
endfor; ?>
                </select>

                <button class="btn btn-add" onclick="window.location.href='subject_add.php'">+ Add</button>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>TITLE</th>
                    <th>SUB CODE</th>
                    <th>HRS/WEEK</th>
                    <th>TYPE</th>
                    <th>ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php $is_allocated = isset($row['is_allocated']) ? intval($row['is_allocated']) : 1; ?>
                        <tr class="<?php echo $is_allocated ? '' : 'row-deallocated'; ?>">
                            <td>
                                <?php echo htmlspecialchars($row['title']); ?>
                                <span class="badge-short"><?php 
                                    $sn = !empty($row['short_name']) ? $row['short_name'] : getSubjectShortName($row['title'], $row['type'], $row['semester']);
                                    echo htmlspecialchars($sn); 
                                ?></span>
                                <?php if (!$is_allocated): ?>
                                    <span style="font-size:11px; color:#d97706; font-weight:600; margin-left:6px;">(Not in
                                        Timetable)</span>
                                <?php
        endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['sub_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['hours_per_week']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($row['type']); ?>">
                                    <?php echo htmlspecialchars($row['type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="subject_edit.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">Edit</a>
                                <a href="subject.php?delete=<?php echo $row['id']; ?>&type=<?php echo $type; ?>&semester=<?php echo $semester; ?>"
                                    class="btn-action btn-delete"
                                    onclick="return confirm('Are you sure you want to delete this subject?')">Delete</a>
                                <?php if ($is_allocated): ?>
                                    <a href="subject.php?toggle_allocate=<?php echo $row['id']; ?>&type=<?php echo $type; ?>&semester=<?php echo $semester; ?>"
                                        class="btn-action btn-deallocate"
                                        onclick="return confirm('Remove &quot;<?php echo htmlspecialchars($row['title']); ?>&quot; from the timetable?')">Deallocate</a>
                                <?php
        else: ?>
                                    <a href="subject.php?toggle_allocate=<?php echo $row['id']; ?>&type=<?php echo $type; ?>&semester=<?php echo $semester; ?>"
                                        class="btn-action btn-allocate">Allocate</a>
                                <?php
        endif; ?>
                            </td>
                        </tr>
                    <?php
    endwhile; ?>
                <?php
else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #6b7280;">
                            No subjects found for <?php echo $type; ?> Semester <?php echo $semester; ?>.
                        </td>
                    </tr>
                <?php
endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
<?php $conn->close(); ?>