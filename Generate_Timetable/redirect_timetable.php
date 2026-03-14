<?php
// Simply redirect to the main Timetable formulation page. 
// The Generated View and Staff View now have their own dedicated tabs/buttons.
session_start();

$redirect_page = 'timetable.php';
$semester = isset($_GET['semester']) ? $_GET['semester'] : (isset($_SESSION['semester_filter']) ? $_SESSION['semester_filter'] : 'odd');

header("Location: " . $redirect_page . "?semester=" . $semester);
exit();
?>