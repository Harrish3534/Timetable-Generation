<?php
// Smart redirect for Class Timetable tab
// This file determines which timetable page to show based on session state

session_start();

// Determine what page to redirect to
$redirect_page = 'timetable.php';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'odd';

// Check if there's an active timetable in session
if (isset($_SESSION['current_timetables']) && !empty($_SESSION['current_timetables'])) {
    // Check which page the user was last on
    if (isset($_SESSION['current_page'])) {
        if ($_SESSION['current_page'] === 'staff_timetable') {
            $redirect_page = 'staff_timetable.php';
        } else if ($_SESSION['current_page'] === 'generate_timetable') {
            $redirect_page = 'generate_timetable.php';
        }
    } else {
        // Default to generated timetable if timetables exist
        $redirect_page = 'generate_timetable.php';
    }
}

// Preserve semester filter
if (isset($_SESSION['semester_filter'])) {
    $semester = $_SESSION['semester_filter'];
}

header("Location: " . $redirect_page . "?semester=" . $semester);
exit();
?>