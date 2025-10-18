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

$enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
if ($enrollmentId <= 0) out(false, 'Missing enrollment id.');

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

/* ---------------- Drop registered course for current user. ---------------- */
if ($st = $db->con->prepare("DELETE FROM tblEnrollment WHERE enrollment_id=? AND user_id=?")) {
  $st->bind_param("ii", $enrollmentId, $userId);
  $st->execute();
  $affected = $st->affected_rows;
  $st->close(); $db->closeConnection();
  if ($affected > 0) out(true, 'Course removed from your schedule.');
  out(false, 'Enrollment not found or already removed.');
}

$db->closeConnection();
out(false, 'Internal error dropping course.');
