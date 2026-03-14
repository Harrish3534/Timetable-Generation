<?php
// One-time migration: set Open Source Technology subjects as deallocated by default
require_once '../Config/config.php';
checkLogin();

$conn = getConnection();

// Set is_allocated = 0 for the two specific Open Source Technology subjects (PG Sem 4)
$result = $conn->query("UPDATE subjects SET is_allocated = 0 WHERE sub_code IN ('21MCS41C', '21MCS42P')");

if ($result) {
    $affected = $conn->affected_rows;
    echo "<p style='font-family:sans-serif; padding:20px;'>✅ Migration complete. Updated $affected subject(s) to deallocated.</p>";
} else {
    echo "<p style='font-family:sans-serif; padding:20px; color:red;'>❌ Error: " . $conn->error . "</p>";
}

echo "<p style='font-family:sans-serif; padding:0 20px;'><a href='../Subject/subject.php?type=PG&semester=4'>→ Go to Subjects</a></p>";

$conn->close();
?>
