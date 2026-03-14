 <?php
require_once '../Config/config.php';
checkLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
$hours = isset($_POST['hours']) ? intval($_POST['hours']) : 0;

if ($subject_id <= 0 || $hours < 1 || $hours > 30) {
    echo json_encode(['success' => false, 'message' => 'Invalid subject ID or hours value']);
    exit;
}

$conn = getConnection();

$stmt = $conn->prepare("UPDATE subjects SET hours_per_week = ? WHERE id = ?");
$stmt->bind_param("ii", $hours, $subject_id);
$result = $stmt->execute();

if ($result) {
    // Also update session hours_changes to stay in sync
    if (!isset($_SESSION['hours_changes'])) {
        $_SESSION['hours_changes'] = [];
    }
    // Update session keys for both shift-based and non-shift keys
    $_SESSION['hours_changes']['hours_' . $subject_id] = $hours;
    $_SESSION['hours_changes']['hours_shift1_' . $subject_id] = $hours;
    $_SESSION['hours_changes']['hours_shift2_' . $subject_id] = $hours;

    echo json_encode(['success' => true, 'message' => 'Hours updated successfully', 'subject_id' => $subject_id, 'hours' => $hours]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>
