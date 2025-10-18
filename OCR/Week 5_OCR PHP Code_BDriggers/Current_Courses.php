<?php
/*******************************************************
 * Current_Courses.php
 * - Enroll in a course (capacity-aware)
 * - Join waitlist if full
 * - Drop course (auto-promote earliest waitlisted student)
 * - Leave waitlist
 *******************************************************/
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
if (empty($_SESSION['USER_EMAIL'])) { header('Location: Login.php'); exit(); }

require_once 'CR_DB_Conn_Class.php';

function clean($v){ return htmlspecialchars(stripslashes(trim($v ?? '')), ENT_QUOTES, 'UTF-8'); }
function set_flash($m,$t='success'){ $_SESSION['FLASH']=['m'=>$m,'t'=>$t]; }
function get_flash(){ $f=$_SESSION['FLASH']??null; if($f) unset($_SESSION['FLASH']); return $f; }

$db = new Database();
$db->con->set_charset('utf8mb4');

/* ---------- resolve current user ---------- */
$user = null;
if ($st = $db->con->prepare("SELECT id, firstName, lastName, email FROM tblUser WHERE email = ?")) {
  $st->bind_param("s", $_SESSION['USER_EMAIL']);
  $st->execute();
  $user = $st->get_result()->fetch_assoc();
  $st->close();
}
if (!$user){ session_destroy(); header('Location: Login.php'); exit(); }
$userId = (int)$user['id'];
$displayName = trim(($user['firstName'] ?? '').' '.($user['lastName'] ?? ''));

$terms = ['Fall 2025','Spring 2026','Summer 2026']; // adjust as needed
$selectedTerm = clean($_GET['term'] ?? $_POST['term'] ?? $terms[0]);

/* ======================================================
   POST actions: enroll / waitlist / drop / leave_waitlist
   ====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action   = clean($_POST['action'] ?? '');
  $courseId = (int)($_POST['course_id'] ?? 0);
  $term     = clean($_POST['term'] ?? $selectedTerm);
  $enrId    = (int)($_POST['enrollment_id'] ?? 0);

  // helper: get course with capacity + current enrolled count
  $getCourseInfo = function($cid, $term) use ($db){
    $info = null;
    if ($st = $db->con->prepare("
      SELECT c.course_id, c.course_code, c.title, c.credits, c.capacity,
             (SELECT COUNT(*) FROM tblEnrollment e
              WHERE e.course_id=c.course_id AND e.term=? AND e.status='enrolled') AS enrolled_now
      FROM tblCourse c WHERE c.course_id = ?
    ")) {
      $st->bind_param("si", $term, $cid);
      $st->execute();
      $info = $st->get_result()->fetch_assoc();
      $st->close();
    }
    return $info;
  };

  // helper: current row if exists
  $getMyRow = function($uid,$cid,$term) use ($db){
    $row=null;
    if ($st=$db->con->prepare("SELECT * FROM tblEnrollment WHERE user_id=? AND course_id=? AND term=?")) {
      $st->bind_param("iis", $uid, $cid, $term);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      $st->close();
    }
    return $row;
  };

  /* --------- ENROLL --------- */
  if ($action === 'enroll') {
    if ($courseId <= 0){ set_flash("Invalid course.","danger"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    $course = $getCourseInfo($courseId, $term);
    if (!$course){ set_flash("Course not found.","danger"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    $existing = $getMyRow($userId,$courseId,$term);
    if ($existing && $existing['status']==='enrolled'){
      set_flash("You are already enrolled in {$course['course_code']} for $term.","warning");
      header("Location: Current_Courses.php?term=".urlencode($term)); exit();
    }

    // if on waitlist, try to promote if seat exists
    if ($existing && $existing['status']==='waitlisted'){
      if ((int)$course['enrolled_now'] < (int)$course['capacity']){
        if ($st = $db->con->prepare("UPDATE tblEnrollment SET status='enrolled', enrolled_at=NOW(), waitlisted_at=NULL WHERE enrollment_id=? AND user_id=?")) {
          $st->bind_param("ii", $existing['enrollment_id'], $userId);
          $st->execute(); $st->close();
          set_flash("Promoted from waitlist and enrolled in {$course['course_code']}.","success");
        } else {
          set_flash("Internal error promoting from waitlist.","danger");
        }
      } else {
        set_flash("Course is still full. You remain on the waitlist.","info");
      }
      header("Location: Current_Courses.php?term=".urlencode($term)); exit();
    }

    // brand new enrollment: check capacity
    if ((int)$course['enrolled_now'] >= (int)$course['capacity']){
      set_flash("Course is full. Consider joining the waitlist.","warning");
      header("Location: Current_Courses.php?term=".urlencode($term)); exit();
    }

    if ($st = $db->con->prepare("INSERT INTO tblEnrollment (user_id, course_id, term, status, enrolled_at) VALUES (?, ?, ?, 'enrolled', NOW())")) {
      $st->bind_param("iis", $userId, $courseId, $term);
      if ($st->execute()) set_flash("Enrolled in {$course['course_code']}.","success");
      else set_flash("Could not enroll (duplicate or internal error).","danger");
      $st->close();
    } else {
      set_flash("Internal error (prep).","danger");
    }
    header("Location: Current_Courses.php?term=".urlencode($term)); exit();
  }

  /* --------- JOIN WAITLIST --------- */
  if ($action === 'waitlist') {
    if ($courseId <= 0){ set_flash("Invalid course.","danger"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    $course = $getCourseInfo($courseId,$term);
    if (!$course){ set_flash("Course not found.","danger"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    $existing = $getMyRow($userId,$courseId,$term);
    if ($existing){
      if ($existing['status']==='enrolled'){
        set_flash("You are already enrolled in {$course['course_code']}.","info");
      } elseif ($existing['status']==='waitlisted') {
        set_flash("You are already on the waitlist for {$course['course_code']}.","info");
      } else {
        // previously dropped: re-waitlist
        if ($st=$db->con->prepare("UPDATE tblEnrollment SET status='waitlisted', waitlisted_at=NOW(), dropped_at=NULL WHERE enrollment_id=? AND user_id=?")) {
          $st->bind_param("ii", $existing['enrollment_id'], $userId);
          $st->execute(); $st->close();
          set_flash("Rejoined waitlist for {$course['course_code']}.","success");
        } else {
          set_flash("Internal error (waitlist).","danger");
        }
      }
      header("Location: Current_Courses.php?term=".urlencode($term)); exit();
    }

    if ($st=$db->con->prepare("INSERT INTO tblEnrollment (user_id, course_id, term, status, waitlisted_at) VALUES (?, ?, ?, 'waitlisted', NOW())")){
      $st->bind_param("iis", $userId, $courseId, $term);
      if ($st->execute()) set_flash("Joined waitlist for {$course['course_code']}.","success");
      else set_flash("Could not join waitlist (duplicate or internal error).","danger");
      $st->close();
    } else {
      set_flash("Internal error (prep).","danger");
    }
    header("Location: Current_Courses.php?term=".urlencode($term)); exit();
  }

  /* --------- DROP COURSE (or leave waitlist) --------- */
  if ($action === 'drop' || $action === 'leave_waitlist') {
    if ($enrId <= 0){ set_flash("Invalid selection.","danger"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    // Find row and related course/term
    $row = null;
    if ($st=$db->con->prepare("
      SELECT e.*, c.course_code, c.capacity
      FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
      WHERE e.enrollment_id=? AND e.user_id=?")) {
      $st->bind_param("ii", $enrId, $userId);
      $st->execute();
      $row = $st->get_result()->fetch_assoc();
      $st->close();
    }
    if (!$row){ set_flash("Enrollment not found.","warning"); header("Location: Current_Courses.php?term=".urlencode($term)); exit(); }

    $courseCode = $row['course_code'];
    $courseId   = (int)$row['course_id'];

    // Hard delete for simplicity
    if ($st=$db->con->prepare("DELETE FROM tblEnrollment WHERE enrollment_id=? AND user_id=?")) {
      $st->bind_param("ii", $enrId, $userId);
      $st->execute();
      $deleted = $st->affected_rows>0;
      $st->close();

      if ($deleted){
        if ($row['status']==='enrolled'){
          set_flash("Dropped $courseCode.","success");

          // Auto-promote earliest waitlisted student
          if ($st=$db->con->prepare("
              SELECT enrollment_id FROM tblEnrollment
              WHERE course_id=? AND term=? AND status='waitlisted'
              ORDER BY waitlisted_at ASC LIMIT 1
          ")) {
            $st->bind_param("is", $courseId, $row['term']);
            $st->execute();
            $w = $st->get_result()->fetch_assoc();
            $st->close();
            if ($w){
              if ($up=$db->con->prepare("UPDATE tblEnrollment SET status='enrolled', enrolled_at=NOW(), waitlisted_at=NULL WHERE enrollment_id=?")) {
                $up->bind_param("i", $w['enrollment_id']);
                $up->execute(); $up->close();
                // (Optional) You could notify that someone was promoted.
              }
            }
          }
        } else {
          set_flash("Removed from waitlist for $courseCode.","success");
        }
      } else {
        set_flash("Nothing to remove.","warning");
      }
    } else {
      set_flash("Internal error (drop).","danger");
    }
    header("Location: Current_Courses.php?term=".urlencode($term)); exit();
  }
}

/* ============================
   Load data to render the page
   ============================ */

/* Enrolled (your schedule) */
$enrolled = [];
if ($st = $db->con->prepare("
  SELECT e.enrollment_id, c.course_code, c.title, c.credits, e.term
  FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
  WHERE e.user_id=? AND e.term=? AND e.status='enrolled'
  ORDER BY c.course_code
")) {
  $st->bind_param("is", $userId, $selectedTerm);
  $st->execute();
  $enrolled = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

/* Your waitlist */
$myWaitlist = [];
if ($st = $db->con->prepare("
  SELECT e.enrollment_id, c.course_code, c.title, c.credits, e.term, e.waitlisted_at
  FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
  WHERE e.user_id=? AND e.term=? AND e.status='waitlisted'
  ORDER BY e.waitlisted_at ASC
")) {
  $st->bind_param("is", $userId, $selectedTerm);
  $st->execute();
  $myWaitlist = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

/* Available courses (not enrolled by you this term) */
$available = [];
if ($st = $db->con->prepare("
  SELECT c.course_id, c.course_code, c.title, c.credits, c.capacity,
         (SELECT COUNT(*) FROM tblEnrollment e
          WHERE e.course_id=c.course_id AND e.term=? AND e.status='enrolled') AS enrolled_now
  FROM tblCourse c
  WHERE c.course_id NOT IN (
    SELECT e.course_id FROM tblEnrollment e WHERE e.user_id=? AND e.term=? AND e.status IN ('enrolled','waitlisted')
  )
  ORDER BY c.course_code
")) {
  $st->bind_param("sis", $selectedTerm, $userId, $selectedTerm);
  $st->execute();
  $available = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Current Courses — <?= htmlspecialchars($selectedTerm); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>.card{border-radius:1rem}.table thead th{white-space:nowrap}</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="Master.php">OCR</a>
    <ul class="navbar-nav ms-auto">
      <li class="nav-item"><a class="nav-link" href="Master.php">Home</a></li>
      <li class="nav-item"><a class="nav-link" href="Profile.php?term=<?= urlencode($selectedTerm); ?>">Profile</a></li>
      <li class="nav-item"><a class="nav-link" href="Master.php?logout=1">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="container">

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['t']; ?> alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($flash['m']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title mb-2">Current Courses</h5>
          <p class="mb-1"><strong>Student:</strong> <?= htmlspecialchars($displayName ?: $user['email']); ?></p>
          <p class="mb-0"><strong>Term:</strong> <?= htmlspecialchars($selectedTerm); ?></p>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <form method="get" action="Current_Courses.php" class="row gy-2 align-items-end">
            <div class="col-8">
              <label class="form-label" for="term">Switch Term</label>
              <select class="form-select" id="term" name="term">
                <?php foreach($terms as $t): ?>
                  <option value="<?= htmlspecialchars($t) ?>" <?= $t===$selectedTerm?'selected':''; ?>><?= htmlspecialchars($t) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <button class="btn btn-primary w-100">Switch</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Enrolled -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0">Your Schedule — <?= htmlspecialchars($selectedTerm); ?></h5></div>
    <div class="card-body">
      <?php if(!$enrolled): ?>
        <p class="text-muted mb-0">You are not enrolled in any courses for this term.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead><tr><th>Course</th><th>Title</th><th>Credits</th><th style="width:120px;">Action</th></tr></thead>
            <tbody>
            <?php foreach($enrolled as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['course_code']); ?></td>
                <td><?= htmlspecialchars($row['title']); ?></td>
                <td><?= (int)$row['credits']; ?></td>
                <td>
                  <form method="post" onsubmit="return confirm('Drop this course?');">
                    <input type="hidden" name="action" value="drop">
                    <input type="hidden" name="enrollment_id" value="<?= (int)$row['enrollment_id']; ?>">
                    <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm); ?>">
                    <button class="btn btn-outline-danger btn-sm">Drop</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Your Waitlist -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-white"><h5 class="mb-0">Your Waitlist — <?= htmlspecialchars($selectedTerm); ?></h5></div>
    <div class="card-body">
      <?php if(!$myWaitlist): ?>
        <p class="text-muted mb-0">You are not waitlisted for any courses this term.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead><tr><th>Course</th><th>Title</th><th>Credits</th><th>Joined</th><th style="width:160px;">Action</th></tr></thead>
            <tbody>
            <?php foreach($myWaitlist as $row): ?>
              <tr>
                <td><?= htmlspecialchars($row['course_code']); ?></td>
                <td><?= htmlspecialchars($row['title']); ?></td>
                <td><?= (int)$row['credits']; ?></td>
                <td><?= htmlspecialchars($row['waitlisted_at']); ?></td>
                <td class="d-flex gap-2">
                  <form method="post">
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="course_id" value="<?= (int)$row['enrollment_id']; // placeholder, replaced below ?>">
                    <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm); ?>">
                  </form>
                  <form method="post" onsubmit="return confirm('Leave this waitlist?');">
                    <input type="hidden" name="action" value="leave_waitlist">
                    <input type="hidden" name="enrollment_id" value="<?= (int)$row['enrollment_id']; ?>">
                    <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm); ?>">
                    <button class="btn btn-outline-secondary btn-sm">Leave Waitlist</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <small class="text-muted">Promotion from waitlist happens automatically when someone drops.</small>
      <?php endif; ?>
    </div>
  </div>

  <!-- Available courses -->
  <div class="card shadow-sm">
    <div class="card-header bg-white"><h5 class="mb-0">Available Courses — <?= htmlspecialchars($selectedTerm); ?></h5></div>
    <div class="card-body">
      <?php if(!$available): ?>
        <p class="text-muted mb-0">No courses available to add (you may already be enrolled or waitlisted for all offered courses).</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead><tr><th>Course</th><th>Title</th><th>Credits</th><th>Capacity</th><th style="width:220px;">Action</th></tr></thead>
            <tbody>
            <?php foreach($available as $c): 
              $full = ((int)$c['enrolled_now'] >= (int)$c['capacity']);
              $capStr = (int)$c['enrolled_now'].' / '.(int)$c['capacity'].($full?' — FULL':'');
            ?>
              <tr>
                <td><?= htmlspecialchars($c['course_code']); ?></td>
                <td><?= htmlspecialchars($c['title']); ?></td>
                <td><?= (int)$c['credits']; ?></td>
                <td><?= htmlspecialchars($capStr); ?></td>
                <td class="d-flex gap-2">
                  <?php if(!$full): ?>
                    <form method="post">
                      <input type="hidden" name="action" value="enroll">
                      <input type="hidden" name="course_id" value="<?= (int)$c['course_id']; ?>">
                      <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm); ?>">
                      <button class="btn btn-success btn-sm">Enroll</button>
                    </form>
                  <?php else: ?>
                    <form method="post">
                      <input type="hidden" name="action" value="waitlist">
                      <input type="hidden" name="course_id" value="<?= (int)$c['course_id']; ?>">
                      <input type="hidden" name="term" value="<?= htmlspecialchars($selectedTerm); ?>">
                      <button class="btn btn-outline-primary btn-sm">Join Waitlist</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="my-4"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
