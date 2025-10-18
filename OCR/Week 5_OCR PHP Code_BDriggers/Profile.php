<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies', '1');
session_start();

/* ---------------- Confirm user is logged in. ---------------- */
if (!isset($_SESSION['USER_EMAIL'])) {
    header('Location: Login.php');
    exit();
}

/* ---------------- Database connection ---------------- */
require_once 'CR_DB_Conn_Class.php';

/* ---------------- Flash helpers ---------------- */
function set_flash($msg,$type='success'){ $_SESSION['FLASH']=['m'=>$msg,'t'=>$type]; }
function get_flash(){ $f=$_SESSION['FLASH']??null; if($f) unset($_SESSION['FLASH']); return $f; }

/* ---------------- Load user data.) ---------------- */
$user_email = $_SESSION['USER_EMAIL'];
$db = new Database();
$db->con->set_charset('utf8mb4');

$sql = "SELECT id, firstName, lastName, email, address, ssn, phone, role
        FROM tblUser WHERE email = ?";
$stmt = $db->con->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $firstName, $lastName, $email, $address, $ssn, $phone, $role);

if ($stmt->fetch()) {
    $_SESSION['USER_ROLE'] = $role;

/* ---------------- Mask user SSN. ---------------- */
    $ssn_display = ($ssn === '000-00-0000') ? '***-**-000' : ('***-**-' . substr($ssn, -4));

/* ---------------- Format user phone number. ---------------- */
    $cleaned_phone = preg_replace('/\D/', '', (string)$phone);
    if (strlen($cleaned_phone) === 10) {
        $phone = '(' . substr($cleaned_phone, 0, 3) . ') ' . substr($cleaned_phone, 3, 3) . '-' . substr($cleaned_phone, 6);
    }
} else {
    echo "Error fetching user data.";
    exit();
}
$stmt->close();

/* ---------------- List of terms/semesters. ---------------- */
$terms = ['Fall 2025','Spring 2026','Summer 2026']; 

// Display currently selected term/semester. */
$activeTerm = isset($_GET['term']) ? trim($_GET['term']) : $terms[0];
if (!in_array($activeTerm, $terms, true)) $activeTerm = $terms[0];

/* ---------------- Render data from database related to logged in user. ---------------- */
$enrollments = [];
$waitlist = [];
$availableCourses = [];

/* ---------------- Current course enrollments for user. ---------------- */
if ($st=$db->con->prepare("
  SELECT e.enrollment_id, e.term, c.course_code, c.title, c.credits
  FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
  WHERE e.user_id=? AND e.term=? AND e.status='enrolled'
  ORDER BY c.course_code")) {
  $st->bind_param("is",$id,$activeTerm);
  $st->execute();
  $enrollments = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

/* ---------------- Current Waitlisted courses for user. ---------------- */
if ($st=$db->con->prepare("
  SELECT e.enrollment_id, e.course_id, c.course_code, c.title, c.credits, e.waitlisted_at
  FROM tblEnrollment e JOIN tblCourse c ON c.course_id=e.course_id
  WHERE e.user_id=? AND e.term=? AND e.status='waitlisted'
  ORDER BY e.waitlisted_at ASC")) {
  $st->bind_param("is",$id,$activeTerm);
  $st->execute();
  $waitlist = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

/* ---------------- Current available courses. ---------------- */
if ($st=$db->con->prepare("
  SELECT c.course_id, c.course_code, c.title, c.credits, c.capacity,
         (SELECT COUNT(*) FROM tblEnrollment e
          WHERE e.course_id=c.course_id AND e.term=? AND e.status='enrolled') AS enrolled_now
  FROM tblCourse c
  WHERE c.course_id NOT IN (
    SELECT e.course_id FROM tblEnrollment e
    WHERE e.user_id=? AND e.term=? AND e.status IN ('enrolled','waitlisted')
  )
  ORDER BY c.course_code")) {
  $st->bind_param("sis",$activeTerm,$id,$activeTerm);
  $st->execute();
  $availableCourses = $st->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
  $st->close();
}

/* ---------------- Total credits for registered courses. ---------------- */
$totalCredits = 0;
foreach ($enrollments as $er) { $totalCredits += (int)$er['credits']; }

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Student Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>

    <style>
        html, body { height: 100%; margin: 0; display: flex; flex-direction: column; }
        body { padding-top: 70px; flex: 1; }
        .jumbotron { background-color: #343a40; color: white; padding: 60px 20px; margin-bottom: 0; }
        .navbar-nav { margin: 0 auto; }
        footer { background-color: #343a40; color: white; padding: 10px; margin-top: auto; }
        .label-pill { border-radius: 999px; }

/* ---------------- Layout to display user data on left panel and course information on main section of screen. ---------------- */
        .main-wrap { padding-top: 20px; padding-bottom: 40px; }
        .sidebar {
            background: #f9f9f9;
            border-right: 1px solid #e5e5e5;
            padding: 15px;
        }
        @media (min-width: 992px) {
            .sidebar-inner { position: sticky; top: 90px; }
        }
        .sidebar .panel { border-radius: 8px; }
        .sidebar .btn-block { margin-bottom: 10px; }

        .panel-heading h4 { margin: 0; }
        .table thead th { white-space: nowrap; }
    </style>
</head>

<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>Online Course Registration Portal</h1>
            <p class="text-muted" style="margin-top:8px;">Student Profile & Course Management</p>
        </div>
    </div>

    <nav class="navbar navbar-inverse" style="border-radius:0;">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                    <span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span>
                </button>
            </div>
            <div class="collapse navbar-collapse" id="myNavbar">
                <ul class="nav navbar-nav">
                    <li><a href="Master.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                    <li><a href="About.php"><span class="glyphicon glyphicon-exclamation-sign"></span> About</a></li>
                    <li><a href="Contact.php"><span class="glyphicon glyphicon-earphone"></span> Contact</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php if (isset($_SESSION['USER_EMAIL'])): ?>
                        <li class="active"><a href="Profile.php"><span class="glyphicon glyphicon-briefcase"></span> Profile</a></li>
                        <li><a href="Master.php?logout=1"><span class="glyphicon glyphicon-off"></span> Logout</a></li>
                    <?php else: ?>
                        <li><a href="Login.php"><span class="glyphicon glyphicon-user"></span> Login</a></li>
                        <li><a href="Register.php"><span class="glyphicon glyphicon-pencil"></span> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Keep original: password changed message -->
    <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == 1): ?>
        <div class="alert alert-success text-center" style="margin: 0; border-radius: 0;">Your password was successfully changed.</div>
    <?php endif; ?>

    <!-- Flash (server) -->
    <?php if ($flash): ?>
        <div class="container" style="margin-top:15px;">
            <div class="alert alert-<?php echo htmlspecialchars($flash['t']); ?> text-center">
                <?php echo htmlspecialchars($flash['m']); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Flash (AJAX) -->
    <div class="container" style="margin-top:15px;">
        <div id="ajaxFlash" class="alert" style="display:none;"></div>
    </div>

    <!-- === Two-column layout === -->
    <div class="container-fluid main-wrap">
        <div class="row">
            <!-- Left Sidebar (Profile + Actions) -->
            <aside class="col-sm-4 col-md-3 sidebar">
                <div class="sidebar-inner">
                    <!-- Profile info (kept content) -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><h4>Student Profile</h4></div>
                        <div class="panel-body">
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($id); ?></p>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></p>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars(trim($firstName.' '.$lastName)); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                            <p><strong>SSN:</strong> <?php echo htmlspecialchars($ssn_display); ?></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="panel panel-default">
                        <div class="panel-heading"><h4>Actions</h4></div>
                        <div class="panel-body">
                            <a href="Change_Password.php" class="btn btn-primary btn-block">Change Password</a>
                            <?php if ($role === 'admin'): ?>
                                <hr>
                                <h5 style="margin-top:0;">Admin</h5>
                                <a href="admin_dashboard.php" class="btn btn-primary btn-block">Admin Dashboard</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content (Course management) -->
            <main class="col-sm-8 col-md-9">
                <!-- Term selector -->
                <div class="panel panel-default">
                    <div class="panel-heading"><h4>Term</h4></div>
                    <div class="panel-body">
                        <form id="termForm" method="get" action="Profile.php" class="form-inline">
                            <div class="form-group">
                                <label for="term"><strong>Selected Term</strong>:&nbsp;</label>
                                <select name="term" id="term" class="form-control">
                                    <?php foreach($terms as $t): ?>
                                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $t===$activeTerm?'selected':''; ?>>
                                            <?php echo htmlspecialchars($t); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            &nbsp;
                            <button class="btn btn-primary" id="btnSwitchTerm">View</button>
                            <span class="help-block" style="display:inline-block; margin-left:10px;">
                              Viewing <strong id="activeTermBadge"><?php echo htmlspecialchars($activeTerm); ?></strong>.  
                              Change the <em>Selected Term</em> above, then click <em>View</em> to update.
                            </span>
                        </form>
                    </div>
                </div>

                <!-- Current Courses -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="pull-left">Current Courses — <span id="termLabel"><?php echo htmlspecialchars($activeTerm); ?></span></h4>

                        <!-- total credits badge -->
                        <span class="label label-info label-pill pull-right" style="margin-left:6px;">
                            Credits: <span id="sumCredits"><?php echo (int)$totalCredits; ?></span>
                        </span>

                        <!-- count of enrolled -->
                        <span class="label label-default label-pill pull-right" id="countEnrolled"><?php echo count($enrollments); ?></span>

                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Course</th><th>Title</th><th>Credits</th><th style="width:120px;">Action</th></tr></thead>
                                <tbody id="tb-enrolled">
                                <?php if (!$enrollments): ?>
                                    <tr><td colspan="4" class="text-muted">You are not registered for any courses in this term.</td></tr>
                                <?php else: foreach ($enrollments as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo (int)$row['credits']; ?></td>
                                        <td>
                                            <!-- Fallback POST (kept), AJAX intercepts -->
                                            <form method="post" class="form-inline js-drop-form">
                                                <input type="hidden" name="action" value="drop_course">
                                                <input type="hidden" name="enrollment_id" value="<?php echo (int)$row['enrollment_id']; ?>">
                                                <input type="hidden" name="term" value="<?php echo htmlspecialchars($activeTerm); ?>">
                                                <button class="btn btn-danger btn-xs js-drop" data-enrollment-id="<?php echo (int)$row['enrollment_id']; ?>">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Your Waitlist -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="pull-left">Your Waitlist — <span><?php echo htmlspecialchars($activeTerm); ?></span></h4>
                        <span class="label label-default label-pill pull-right" id="countWaitlist"><?php echo count($waitlist); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead><tr><th>Course</th><th>Title</th><th>Credits</th><th>Joined</th><th style="width:180px;">Action</th></tr></thead>
                                <tbody id="tb-waitlist">
                                <?php if (!$waitlist): ?>
                                    <tr><td colspan="5" class="text-muted">You are not waitlisted for any courses in this term.</td></tr>
                                <?php else: foreach ($waitlist as $w): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($w['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($w['title']); ?></td>
                                        <td><?php echo (int)$w['credits']; ?></td>
                                        <td><?php echo htmlspecialchars($w['waitlisted_at']); ?></td>
                                        <td>
                                            <!-- Fallback POST (kept), AJAX intercepts -->
                                            <form method="post" class="form-inline js-leave-form" style="display:inline-block;margin-right:6px;">
                                                <input type="hidden" name="action" value="leave_waitlist">
                                                <input type="hidden" name="enrollment_id" value="<?php echo (int)$w['enrollment_id']; ?>">
                                                <input type="hidden" name="term" value="<?php echo htmlspecialchars($activeTerm); ?>">
                                                <button class="btn btn-default btn-xs js-leave" data-enrollment-id="<?php echo (int)$w['enrollment_id']; ?>">Leave Waitlist</button>
                                            </form>
                                            <!-- Try Enroll now -->
                                            <button class="btn btn-primary btn-xs js-try-enroll" data-course-id="<?php echo (int)$w['course_id']; ?>">Try Enroll</button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-muted">If a seat opens, the earliest waitlisted student is promoted automatically.</p>
                    </div>
                </div>

                <!-- Add Courses -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="pull-left">Add Courses — <span id="addTermLabel"><?php echo htmlspecialchars($activeTerm); ?></span></h4>
                        <span class="label label-default label-pill pull-right" id="countAvailable"><?php echo count($availableCourses); ?></span>
                        <div class="clearfix"></div>
                    </div>
                    <div class="panel-body">
                        <?php if (!$availableCourses): ?>
                            <p class="text-muted">No available courses to add (you may already be enrolled or waitlisted for all offered courses).</p>
                        <?php else: ?>
                            <!-- Fallback POST (kept), AJAX intercepts button clicks -->
                            <form method="post" class="form-inline" id="addForm">
                                <!-- IMPORTANT: holds the ACTIVE term, not the dropdown selection -->
                                <input type="hidden" name="term" id="hiddenTermAdd" value="<?php echo htmlspecialchars($activeTerm); ?>">
                                <div class="form-group">
                                    <label for="course_id">Select Course:&nbsp;</label>
                                    <select name="course_id" id="course_id" class="form-control" required>
                                        <option value="">-- Select Course --</option>
                                        <?php foreach ($availableCourses as $c):
                                            $full = ((int)$c['enrolled_now'] >= (int)$c['capacity']);
                                            $label = $c['course_code'].' — '.$c['title'].' ('.$c['credits'].' cr) ['.$c['enrolled_now'].'/'.$c['capacity'].']'.($full?' — FULL':'');
                                        ?>
                                            <option value="<?php echo (int)$c['course_id']; ?>" data-full="<?php echo $full ? '1' : '0'; ?>">
                                                <?php echo htmlspecialchars($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                &nbsp;
                                <!-- Buttons are conditionally shown by JS based on capacity -->
                                <button name="action" value="add_course" class="btn btn-success" id="btnEnroll" style="display:none;">Register for Class</button>
                                &nbsp;
                                <button name="action" value="waitlist_course" class="btn btn-primary" id="btnWaitlist" style="display:none;">Join Waitlist</button>
                            </form>
                            <p class="help-block" id="capacityHint" style="display:none;"></p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php
      if (file_exists('Footer.php')) { include('Footer.php'); }
      elseif (file_exists('footer.php')) { include('footer.php'); }
    ?>

<script>
/* ---------------- AJAX connection to Courses_API.php. ---------------- */
var API_URL = 'Courses_API.php';
var currentTerm = <?php echo json_encode($activeTerm); ?>; // ACTIVE view term

function showFlash(msg, type){
  var $f = $('#ajaxFlash');
  $f.removeClass().addClass('alert alert-' + (type||'info')).text(msg).show();
  setTimeout(function(){ $f.fadeOut(); }, 1800);
}

function esc(s){ return (s==null?'':String(s)).replace(/[&<>"']/g, function(m){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];}); }

function api(action, payload, method){
  payload = payload || {};
  payload.action = action;
  payload.term = currentTerm; // Use selected term/semester
  return $.ajax({
    url: API_URL + (method==='GET' ? '?' + $.param(payload) : ''),
    type: method || 'POST',
    dataType: 'json',
    data: method==='GET' ? undefined : payload
  });
}

function renderEnrolled(list){
  var $tb = $('#tb-enrolled');
  var items = list || [];

  // Calculate total credits badge.
  var sum = 0, i;
  for (i = 0; i < items.length; i++) { sum += Number(items[i].credits || 0); }
  $('#sumCredits').text(sum);

  $('#countEnrolled').text(items.length);
  if (!items.length) {
    $tb.html('<tr><td colspan="4" class="text-muted">You are not registered for any courses in this term.</td></tr>');
    return;
  }
  var rows = items.map(function(r){
    return '<tr>'
      + '<td>'+esc(r.course_code)+'</td>'
      + '<td>'+esc(r.title)+'</td>'
      + '<td>'+esc(r.credits)+'</td>'
      + '<td>'
      +   '<form class="form-inline js-drop-form">'
      +     '<button class="btn btn-danger btn-xs js-drop" data-enrollment-id="'+esc(r.enrollment_id)+'">Delete</button>'
      +   '</form>'
      + '</td>'
      + '</tr>';
  }).join('');
  $tb.html(rows);
}

function renderWaitlist(list){
  var $tb = $('#tb-waitlist');
  $('#countWaitlist').text((list||[]).length);
  if (!list || !list.length) {
    $tb.html('<tr><td colspan="5" class="text-muted">You are not waitlisted for any courses in this term.</td></tr>');
    return;
  }
  var rows = list.map(function(r){
    return '<tr>'
      + '<td>'+esc(r.course_code)+'</td>'
      + '<td>'+esc(r.title)+'</td>'
      + '<td>'+esc(r.credits)+'</td>'
      + '<td>'+esc(r.waitlisted_at||'')+'</td>'
      + '<td>'
      +   '<form class="form-inline js-leave-form" style="display:inline-block;margin-right:6px;">'
      +     '<button class="btn btn-default btn-xs js-leave" data-enrollment-id="'+esc(r.enrollment_id)+'">Leave Waitlist</button>'
      +   '</form>'
      +   '<button class="btn btn-primary btn-xs js-try-enroll" data-course-id="'+esc(r.course_id)+'">Try Enroll</button>'
      + '</td>'
      + '</tr>';
  }).join('');
  $tb.html(rows);
}

function renderAvailable(list){
  $('#countAvailable').text((list||[]).length);
  var $sel = $('#course_id');
  if (!$sel.length) return;
  var opts = ['<option value="">-- Select Course--</option>'];
  (list||[]).forEach(function(c){
    var full = Number(c.enrolled_now) >= Number(c.capacity);
    var label = c.course_code+' — '+c.title+' ('+c.credits+' Credits - '+c.enrolled_now+'/'+c.capacity+' Seats Available)'+(full?' — FULL':'');
    opts.push('<option value="'+esc(c.course_id)+'" data-full="'+(full?'1':'0')+'">'+esc(label)+'</option>');
  });
  $sel.html(opts.join(''));

/* ---------------- Determine whether to display Register or Waitlist button based on current course seat availability.. ---------------- */
  updateActionButtons();
}

function updateActionButtons(){
  var $sel = $('#course_id');
  var $enrollBtn = $('#btnEnroll');
  var $waitBtn = $('#btnWaitlist');
  var $hint = $('#capacityHint');

  if (!$sel.length) return;

  var val = $sel.val();
  if (!val) {
    $enrollBtn.hide();
    $waitBtn.hide();
    $hint.hide();
    return;
  }

  var isFull = $sel.find('option:selected').data('full') === 1 || $sel.find('option:selected').data('full') === '1';

  if (isFull) {
    $enrollBtn.hide();
    $waitBtn.show();
    $hint.text('This class is full. You can join the waitlist.').show();
  } else {
    $waitBtn.hide();
    $enrollBtn.show();
    $hint.text('Seats available. You can register for this class.').show();
  }
}

function applyData(data){
  renderEnrolled(data.enrolled||[]);
  renderWaitlist(data.waitlist||[]);
  renderAvailable(data.available||[]);
}

/* ---------------- Active term/semester is displayed when "View" button is clicked. ---------------- */
function setActiveTerm(newTerm){
  currentTerm = newTerm;
  $('#termLabel').text(currentTerm);
  $('#addTermLabel').text(currentTerm);
  $('#activeTermBadge').text(currentTerm);
  $('#hiddenTermAdd').val(currentTerm);
  $('input[name="term"]').val(currentTerm);
  var url = new URL(window.location.href);
  url.searchParams.set('term', currentTerm);
  window.history.pushState({}, '', url.toString());
}

function loadAll(){
  api('list', {}, 'GET').done(function(res){
    if (!res || !res.ok) { showFlash(res && res.message || 'Load failed', 'danger'); return; }
    applyData(res.data||{});
  }).fail(function(){ showFlash('Network error while loading.', 'danger'); });
}

$('#termForm').on('submit', function(e){
  e.preventDefault();
  var selected = $('#term').val();
  setActiveTerm(selected);
  loadAll();
});

/* ---------------- Drop registered course from selected term/semester. ---------------- */
$(document).on('click', '.js-drop', function(e){
  e.preventDefault();
  if (!confirm('Remove this course from your schedule?')) return;
  var id = $(this).data('enrollmentId');
  api('drop', {enrollment_id: id}, 'POST').done(function(res){
    showFlash(res.message, res.ok?'success':'danger');
    if (res && res.data) applyData(res.data);
  }).fail(function(){ showFlash('Drop failed.', 'danger'); });
});

/* ---------------- Drop course from Waitlist. ---------------- */
$(document).on('click', '.js-leave', function(e){
  e.preventDefault();
  if (!confirm('Leave this waitlist?')) return;
  var id = $(this).data('enrollmentId');
  api('drop', {enrollment_id: id}, 'POST').done(function(res){
    showFlash(res.message, res.ok?'success':'danger');
    if (res && res.data) applyData(res.data);
  }).fail(function(){ showFlash('Request failed.', 'danger'); });
});

/* ---------------- Add course to Waitlist. ---------------- */
$(document).on('click', '.js-try-enroll', function(e){
  e.preventDefault();
  var courseId = $(this).data('courseId');
  api('enroll', {course_id: courseId}, 'POST').done(function(res){
    showFlash(res.message, res.ok?'success':'info');
    if (res && res.data) applyData(res.data);
  }).fail(function(){ showFlash('Request failed.', 'danger'); });
});

/* ---------------- Enroll in course. ---------------- */
$('#btnEnroll').on('click', function(e){
  e.preventDefault();
  var courseId = $('#course_id').val();
  if (!courseId) { showFlash('Please select a course.', 'warning'); return; }
  api('enroll', {course_id: courseId}, 'POST').done(function(res){
    showFlash(res.message, res.ok?'success':'danger');
    if (res && res.data) applyData(res.data);
  }).fail(function(){ showFlash('Enroll failed.', 'danger'); });
});

/* ---------------- Add Courses — Join Waitlist ---------------- */
$('#btnWaitlist').on('click', function(e){
  e.preventDefault();
  var courseId = $('#course_id').val();
  if (!courseId) { showFlash('Please select a course.', 'warning'); return; }
  api('waitlist', {course_id: courseId}, 'POST').done(function(res){
    showFlash(res.message, res.ok?'success':'danger');
    if (res && res.data) applyData(res.data);
  }).fail(function(){ showFlash('Waitlist request failed.', 'danger'); });
});

$(document).on('change', '#course_id', updateActionButtons);

// Initial AJAX refresh and button state
$(function(){
  loadAll();
  updateActionButtons();
});
</script>

</body>
</html>
