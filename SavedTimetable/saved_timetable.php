<?php
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Auto-create tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS `saved_timetables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `semester` VARCHAR(10) NOT NULL,
  `created_at` DATETIME DEFAULT NOW(),
  `updated_at` DATETIME DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `saved_timetable_slots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `saved_timetable_id` INT NOT NULL,
  `class_id` INT NOT NULL,
  `class_name` VARCHAR(100),
  `shift` VARCHAR(30),
  `semester` INT NOT NULL,
  `day` VARCHAR(20) NOT NULL,
  `hour` VARCHAR(20) NOT NULL,
  `subject_id` VARCHAR(50) DEFAULT NULL,
  `subject_title` VARCHAR(150) DEFAULT NULL,
  `subject_short_name` VARCHAR(50) DEFAULT NULL,
  `subject_type` VARCHAR(50) DEFAULT NULL,
  `staff_id` INT DEFAULT NULL,
  `staff_name` VARCHAR(100) DEFAULT NULL,
  `staff_code` VARCHAR(20) DEFAULT NULL,
  INDEX idx_saved_timetable_id (saved_timetable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle delete
if (isset($_POST['delete_id'])) {
    $del_id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM saved_timetable_slots WHERE saved_timetable_id = $del_id");
    $conn->query("DELETE FROM saved_timetables WHERE id = $del_id");
    header("Location: saved_timetable.php");
    exit;
}

$saved_list = $conn->query("SELECT st.*, 
    (SELECT COUNT(DISTINCT class_id) FROM saved_timetable_slots WHERE saved_timetable_id = st.id) as class_count
    FROM saved_timetables st ORDER BY st.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Timetables - GAC Timetable</title>
    <link rel="stylesheet" href="../Assets/css/style.css?v=2.1">
    <style>
        .saved-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .saved-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #3b82f6;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .saved-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .saved-card h3 {
            margin: 0 0 8px 0;
            color: #1f2937;
            font-size: 18px;
        }

        .saved-card .meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-blue {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-green {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-gray {
            background: #f3f4f6;
            color: #374151;
        }

        .saved-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-view {
            background: #3b82f6;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-view:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 15px;
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

    <div class="tabs">
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
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
            <h2 style="margin:0; color:#1f2937;">💾 Saved Timetables</h2>
            <a href="../Generate_Timetable/redirect_timetable.php" class="btn btn-primary">+ Generate New</a>
        </div>

        <?php if ($saved_list && $saved_list->num_rows > 0): ?>
            <div class="saved-grid">
                <?php while ($row = $saved_list->fetch_assoc()): ?>
                    <div class="saved-card">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <div class="meta">
                            <span class="badge badge-blue"><?php echo ucfirst($row['semester']); ?> Semester</span>
                            <span class="badge badge-green"><?php echo $row['class_count']; ?> Classes</span>
                            <span
                                class="badge badge-gray"><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="actions">
                            <a href="view_saved_timetable.php?id=<?php echo $row['id']; ?>" class="btn-view">📋 View / Edit</a>
                            <form method="POST" onsubmit="return confirm('Delete this saved timetable?');"
                                style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-delete">🗑 Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>📭 No Saved Timetables</h3>
                <p>Generate a timetable and click <strong>"💾 Save Timetable"</strong> to save it here.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>
<?php $conn->close(); ?>