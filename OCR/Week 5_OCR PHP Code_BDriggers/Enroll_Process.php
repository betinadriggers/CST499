<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
header('Content-Type: application/json');

require_once 'CR_DB_Conn_Class.php';

function out($ok, $msg, $extra = []) {
  echo json_encode(array_merge(['ok'=>$ok,'message'=>$msg], $extra));
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request method.');

if (empty($_SESSION['USER_EMAIL'])) out(false, 'Not authenticated.');
$term     = trim($_POST['term'] ?? '');
$courseId = (int)($_POST['course_id'] ?? 0);
if ($term === '' || $courseId <= 0) out(false, 'Missing term or course.');

$db = new Database();
$db->con->set_charset('utf8mb4');

/* ---------------- Retrieve UserID. ---------------- */
$user = null;
if ($st = $db->con->prepare("SELECT id FROM tblUser WHERE email = ?")) {
  $st->bind_param("s", $_SESSION['USER_EMAIL']);
  $st->execute();
  $user = $st->get_result()->fetch_assoc();
  $st->close();
}
if (!$user) out(false, 'User not found.');
$userId = (int)$user['id'];

/* ---------------- Confirm course and available seats. ---------------- */
$course = null;
if ($st = $db->con->prepare("SELECT course_id, capacity FROM tblCourse WHERE course_id = ?")) {
  $st->bind_param("i", $courseId);
  $st->execute();
  $course = $st->get_result()->fetch_assoc();
  $st->close();
}
if (!$course) out(false, 'Course does not exist.');

$enrolledNow = 0;
if ($st = $db->con->prepare("SELECT COUNT(*) AS c FROM tblEnrollment WHERE course_id=? AND term=? AND status='enrolled'")) {
  $st->bind_param("is", $courseId, $term);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  $enrolledNow = (int)$row['c'];
}
if ($enrolledNow >= (int)$course['capacity']) out(false, 'Course is full.');

/* ---------------- Check current user enrollments. ---------------- */
if ($st = $db->con->prepare("SELECT 1 FROM tblEnrollment WHERE user_id=? AND course_id=? AND term=? AND status='enrolled'")) {
  $st->bind_param("iis", $userId, $courseId, $term);
  $st->execute();
  $st->store_result();
  $already = $st->num_rows > 0;
  $st->close();
  if ($already) out(false, 'Already enrolled in this course for this term.');
}

/* ---------------- Update tblEnrolment with new course enrollments for each user as they are processed. ---------------- */
if ($st = $db->con->prepare("INSERT INTO tblEnrollment (user_id, course_id, term, status) VALUES (?, ?, ?, 'enrolled')")) {
  $st->bind_param("iis", $userId, $courseId, $term);
  if ($st->execute()) {
    $st->close(); $db->closeConnection();
    out(true, 'Enrolled successfully.');
  }
  $st->close();
}

$db->closeConnection();
out(false, 'Could not enroll (duplicate or internal error).');
