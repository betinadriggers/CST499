<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
header('Content-Type: application/json');

require_once 'CR_DB_Conn_Class.php';

function respond($ok, $message, $data = []) {
  echo json_encode(['ok'=>$ok, 'message'=>$message, 'data'=>$data]);
  exit();
}
function clean($v){ return trim((string)($v ?? '')); }

if (empty($_SESSION['USER_EMAIL'])) respond(false, 'Not authenticated.');

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
if (!$user) respond(false, 'User not found.');
$userId = (int)$user['id'];

/* ---------------- Define terms/semesters. ---------------- */
$terms = ['Fall 2025','Spring 2026','Summer 2026'];
$action = strtolower(clean($_REQUEST['action'] ?? 'list'));
$term   = clean($_REQUEST['term'] ?? $terms[0]);
if (!in_array($term, $terms, true)) $term = $terms[0];

/* ---------------- Retrieve course data for current user. ---------------- */
function courseInfo($db, $courseId, $term) {
  $info = null;
  if ($st = $db->con->prepare("
    SELECT c.course_id, c.course_code, c.title, c.credits, c.capacity,
           (SELECT COUNT(*) FROM tblEnrollment e
            WHERE e.course_id=c.course_id AND e.term=? AND e.status='enrolled') AS enrolled_now
    FROM tblCourse c WHERE c.course_id = ?
  ")) {
    $st->bind_param("si", $term, $courseId);
    $st->execute();
    $info = $st->get_result()->fetch_assoc();
    $st->close();
  }
  return $info;
}
function myRow($db, $userId, $courseId, $term) {
  $row = null;
  if ($st = $db->con->prepare("SELECT * FROM tblEnrollment WHERE user_id=? AND course_id=? AND term=?")) {
    $st->bind_param("iis", $userId, $courseId, $term);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
  }
  return $row;
}
function loadData($db, $userId, $term) {
  $out = ['enrolled'=>[], 'waitlist'=>[], 'available'=>[]];

  if ($st = $db->con->prepare("
    SELECT e.enrollment_id, c.course_code, c.title, c.credits
    FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
    WHERE e.user_id=? AND e.term=? AND e.status='enrolled'
    ORDER BY c.course_code
  ")) {
    $st->bind_param("is", $userId, $term);
    $st->execute();
    $out['enrolled'] = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $st->close();
  }

  if ($st = $db->con->prepare("
    SELECT e.enrollment_id, c.course_code, c.title, c.credits, e.waitlisted_at, e.course_id
    FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
    WHERE e.user_id=? AND e.term=? AND e.status='waitlisted'
    ORDER BY e.waitlisted_at ASC
  ")) {
    $st->bind_param("is", $userId, $term);
    $st->execute();
    $out['waitlist'] = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $st->close();
  }

  if ($st = $db->con->prepare("
    SELECT c.course_id, c.course_code, c.title, c.credits, c.capacity,
           (SELECT COUNT(*) FROM tblEnrollment e
            WHERE e.course_id=c.course_id AND e.term=? AND e.status='enrolled') AS enrolled_now
    FROM tblCourse c
    WHERE c.course_id NOT IN (
      SELECT e.course_id FROM tblEnrollment e
      WHERE e.user_id=? AND e.term=? AND e.status IN ('enrolled','waitlisted')
    )
    ORDER BY c.course_code
  ")) {
    $st->bind_param("sis", $term, $userId, $term);
    $st->execute();
    $out['available'] = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    $st->close();
  }

  return $out;
}

switch ($action) {
  case 'list': {
    $data = loadData($db, $userId, $term);
    respond(true, 'OK', $data);
  }

  case 'enroll': {
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($courseId <= 0) respond(false, 'Invalid course.');

    $course = courseInfo($db, $courseId, $term);
    if (!$course) respond(false, 'Course not found.');

    $existing = myRow($db, $userId, $courseId, $term);
    if ($existing && $existing['status']==='enrolled') {
      $data = loadData($db, $userId, $term);
      respond(true, "Already enrolled in {$course['course_code']}.", $data);
    }
    if ($existing && $existing['status']==='waitlisted') {
      // Try promotion if seat exists
      if ((int)$course['enrolled_now'] < (int)$course['capacity']) {
        if ($st = $db->con->prepare("UPDATE tblEnrollment SET status='enrolled', enrolled_at=NOW(), waitlisted_at=NULL WHERE enrollment_id=? AND user_id=?")) {
          $st->bind_param("ii", $existing['enrollment_id'], $userId);
          $st->execute(); $st->close();
          $data = loadData($db, $userId, $term);
          respond(true, "Promoted from waitlist and enrolled in {$course['course_code']}.", $data);
        }
        respond(false, 'Internal error during promotion.');
      } else {
        $data = loadData($db, $userId, $term);
        respond(false, 'Course is full. You remain on the waitlist.', $data);
      }
    }

/* ---------------- Current user enrolls in course. ---------------- */
    if ((int)$course['enrolled_now'] >= (int)$course['capacity']) {
      $data = loadData($db, $userId, $term);
      respond(false, 'Course is full. Consider joining the waitlist.', $data);
    }
    if ($st = $db->con->prepare("INSERT INTO tblEnrollment (user_id, course_id, term, status, enrolled_at) VALUES (?, ?, ?, 'enrolled', NOW())")) {
      $st->bind_param("iis", $userId, $courseId, $term);
      if ($st->execute()) {
        $st->close();
        $data = loadData($db, $userId, $term);
        respond(true, "Enrolled in {$course['course_code']}.", $data);
      }
      $st->close();
    }
    $data = loadData($db, $userId, $term);
    respond(false, 'Could not enroll (duplicate or internal error).', $data);
  }

  case 'waitlist': {
    $courseId = (int)($_POST['course_id'] ?? 0);
    if ($courseId <= 0) respond(false, 'Invalid course.');
    $course = courseInfo($db, $courseId, $term);
    if (!$course) respond(false, 'Course not found.');

    $existing = myRow($db, $userId, $courseId, $term);
    if ($existing) {
      if ($existing['status']==='enrolled') {
        $data = loadData($db, $userId, $term);
        respond(true, "You are already enrolled in {$course['course_code']}.", $data);
      }
      if ($existing['status']==='waitlisted') {
        $data = loadData($db, $userId, $term);
        respond(true, "You are already on the waitlist for {$course['course_code']}.", $data);
      }
      // Previously dropped: re-waitlist
      if ($st = $db->con->prepare("UPDATE tblEnrollment SET status='waitlisted', waitlisted_at=NOW(), dropped_at=NULL WHERE enrollment_id=? AND user_id=?")) {
        $st->bind_param("ii", $existing['enrollment_id'], $userId);
        $st->execute(); $st->close();
        $data = loadData($db, $userId, $term);
        respond(true, "Rejoined waitlist for {$course['course_code']}.", $data);
      }
      $data = loadData($db, $userId, $term);
      respond(false, 'Internal error (waitlist).', $data);
    }

    if ($st = $db->con->prepare("INSERT INTO tblEnrollment (user_id, course_id, term, status, waitlisted_at) VALUES (?, ?, ?, 'waitlisted', NOW())")) {
      $st->bind_param("iis", $userId, $courseId, $term);
      if ($st->execute()) {
        $st->close();
        $data = loadData($db, $userId, $term);
        respond(true, "Joined waitlist for {$course['course_code']}.", $data);
      }
      $st->close();
    }
    $data = loadData($db, $userId, $term);
    respond(false, 'Could not join waitlist (duplicate or internal error).', $data);
  }

  case 'drop': {
    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    if ($enrollmentId <= 0) respond(false, 'Missing enrollment id.');
t
    $row = null;
    if ($st = $db->con->prepare("
      SELECT e.*, c.course_code
      FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
      WHERE e.enrollment_id=? AND e.user_id=?")) {
      $st->bind_param("ii", $enrollmentId, $userId);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      $st->close();
    }
    if (!$row) { $data = loadData($db, $userId, $term); respond(false, 'Enrollment not found.', $data); }

    $courseId = (int)$row['course_id'];
    $courseCode = $row['course_code'];

/* ---------------- Remove course from current student. ---------------- */
    if ($st = $db->con->prepare("DELETE FROM tblEnrollment WHERE enrollment_id=? AND user_id=?")) {
      $st->bind_param("ii", $enrollmentId, $userId);
      $st->execute();
      $deleted = $st->affected_rows > 0;
      $st->close();

      if ($deleted && $row['status']==='enrolled') {
/* ---------------- Enroll first student on Waitlist to course if seat opens. ---------------- */
        if ($st = $db->con->prepare("
          SELECT enrollment_id FROM tblEnrollment
          WHERE course_id=? AND term=? AND status='waitlisted'
          ORDER BY waitlisted_at ASC LIMIT 1
        ")) {
          $st->bind_param("is", $courseId, $row['term']);
          $st->execute();
          $w = $st->get_result()->fetch_assoc();
          $st->close();
          if ($w) {
            if ($up = $db->con->prepare("UPDATE tblEnrollment SET status='enrolled', enrolled_at=NOW(), waitlisted_at=NULL WHERE enrollment_id=?")) {
              $up->bind_param("i", $w['enrollment_id']);
              $up->execute(); $up->close();
            }
          }
        }
        $data = loadData($db, $userId, $term);
        respond(true, "Dropped $courseCode.", $data);
      }

      if ($deleted && $row['status']==='waitlisted') {
        $data = loadData($db, $userId, $term);
        respond(true, "Removed from waitlist for $courseCode.", $data);
      }
    }
    $data = loadData($db, $userId, $term);
    respond(false, 'Unable to remove (not found or internal error).', $data);
  }

  case 'leave_waitlist': { 
    $_POST['action'] = 'drop';
    $_POST['enrollment_id'] = (int)($_POST['enrollment_id'] ?? 0);

    $data = loadData($db, $userId, $term);
    respond(false, 'Use action=drop for leaving waitlist.', $data);
  }

  default:
    $data = loadData($db, $userId, $term);
    respond(false, 'Unknown action.', $data);
}

