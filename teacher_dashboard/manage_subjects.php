<?php
// teacher_dashboard/manage_subjects.php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Ensure logged-in teacher
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'teacher') {
    header("Location: ../login.php");
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];
$flash = null;

// --- Handle form submissions ---

// Remove student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_student') {
    $student_id = (int)$_POST['student_id'];
    $subject_id = (int)$_POST['subject_id'];

    $stmt = $pdo->prepare("UPDATE subject_enrollments SET status = 'dropped', dropped_date = NOW() WHERE student_id = ? AND subject_id = ? AND status = 'active'");
    if ($stmt->execute([$student_id, $subject_id])) {
        $_SESSION['manage_subjects_flash'] = ['type' => 'success', 'msg' => 'Student removed successfully.'];
    } else {
        $_SESSION['manage_subjects_flash'] = ['type' => 'error', 'msg' => 'Failed to remove student.'];
    }
    header("Location: manage_subjects.php?subject_id=" . $subject_id);
    exit;
}

// Delete material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_material') {
    $material_id = (int)$_POST['material_id'];
    $subject_id = (int)$_POST['subject_id'];

    $stmt = $pdo->prepare("DELETE FROM subject_materials WHERE id = ? AND teacher_id = ?");
    if ($stmt->execute([$material_id, $teacher_id])) {
        $_SESSION['manage_subjects_flash'] = ['type' => 'success', 'msg' => 'Material deleted successfully.'];
    } else {
        $_SESSION['manage_subjects_flash'] = ['type' => 'error', 'msg' => 'Failed to delete material.'];
    }
    header("Location: manage_subjects.php?subject_id=" . $subject_id);
    exit;
}

// Add new material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_material') {
    $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $external_link = trim($_POST['external_link'] ?? '');
    $file_path = null;

    if (!empty($_FILES['file']['name'])) {
        $upload_dir = _DIR_ . "/../uploads/materials/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES['file']['name']);
        $target = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_path = "uploads/materials/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO subject_materials (subject_id, teacher_id, title, description, file_path, external_link, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt->execute([$subject_id, $teacher_id, $title, $description, $file_path, $external_link])) {
        $_SESSION['manage_subjects_flash'] = ['type' => 'success', 'msg' => 'Material added successfully.'];
    } else {
        $_SESSION['manage_subjects_flash'] = ['type' => 'error', 'msg' => 'Failed to add material.'];
    }
    header("Location: manage_subjects.php?subject_id=" . $subject_id);
    exit;
}

// Edit material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_material') {
    $material_id = (int)$_POST['material_id'];
    $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $external_link = trim($_POST['external_link'] ?? '');

    // Get current file
    $stmt = $pdo->prepare("SELECT file_path FROM subject_materials WHERE id = ? AND teacher_id = ?");
    $stmt->execute([$material_id, $teacher_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $file_path = $current ? $current['file_path'] : null;

    // Handle new file upload
    if (!empty($_FILES['file']['name'])) {
        $upload_dir = _DIR_ . "/../uploads/materials/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_name = time() . "_" . basename($_FILES['file']['name']);
        $target = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $target)) {
            $file_path = "uploads/materials/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("UPDATE subject_materials SET title=?, description=?, file_path=?, external_link=? WHERE id=? AND teacher_id=?");
    if ($stmt->execute([$title, $description, $file_path, $external_link, $material_id, $teacher_id])) {
        $_SESSION['manage_subjects_flash'] = ['type' => 'success', 'msg' => 'Material updated successfully.'];
    } else {
        $_SESSION['manage_subjects_flash'] = ['type' => 'error', 'msg' => 'Failed to update material.'];
    }
    header("Location: manage_subjects.php?subject_id=" . $subject_id);
    exit;
}

// --- Show flash ---
if (isset($_SESSION['manage_subjects_flash'])) {
    $flash = $_SESSION['manage_subjects_flash'];
    unset($_SESSION['manage_subjects_flash']);
}

// Fetch teacher’s subjects
$stmt = $pdo->prepare("
    SELECT s.* 
    FROM teacher_subjects ts
    JOIN subjects s ON ts.subject_id = s.id
    WHERE ts.teacher_id = ?
    ORDER BY s.subject_name
");
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Selected subject details
$selected_subject = null;
$students = [];
$materials = [];
if (!empty($_GET['subject_id'])) {
    $subject_id = (int)$_GET['subject_id'];

    $check = $pdo->prepare("SELECT COUNT(*) FROM teacher_subjects WHERE teacher_id = ? AND subject_id = ?");
    $check->execute([$teacher_id, $subject_id]);
    if ($check->fetchColumn() > 0) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $selected_subject = $stmt->fetch(PDO::FETCH_ASSOC);

        $studentStmt = $pdo->prepare("
            SELECT u.id, COALESCE(u.full_name, u.username) AS name, u.username, u.email, se.grade_at_enrollment, se.enrollment_date
            FROM subject_enrollments se
            JOIN users u ON se.student_id = u.id
            WHERE se.subject_id = ? AND se.status = 'active'
            ORDER BY u.full_name
        ");
        $studentStmt->execute([$subject_id]);
        $students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

        $matStmt = $pdo->prepare("SELECT * FROM subject_materials WHERE subject_id = ? ORDER BY created_at DESC");
        $matStmt->execute([$subject_id]);
        $materials = $matStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include '../includes/teacher_header.php';
?>

<style>
.avatar-circle { width:36px;height:36px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-weight:600;margin-right:10px;text-transform:uppercase; }
.student-row { display:flex;align-items:center;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid #f1f5f9; }
.student-left { display:flex;align-items:center;gap:.6rem; }
.student-meta { color:#475569;font-size:.92rem; }
.remove-btn { background:transparent;border:1px solid #fee2e2;color:#b91c1c;padding:.25rem .6rem;border-radius:6px;cursor:pointer; }
.remove-btn:hover { background:#fef2f2; }
.flash { padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem; }
.flash.success { background:#ecfdf5;color:#065f46;border:1px solid #bbf7d0; }
.flash.error { background:#fff7f7;color:#7f1d1d;border:1px solid #fecaca; }
.edit-form { margin-top:.5rem;padding:.5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px; }
</style>

<div class="container-fluid" style="padding:1.5rem 2rem;">
    <div style="display:flex;gap:2rem;">
        <!-- Subjects list -->
        <div style="width:320px; background:#fff; border-radius:12px; padding:1rem; box-shadow:0 6px 18px rgba(2,6,23,0.06);">
            <h4 style="margin-top:0;margin-bottom:1rem;">My Subjects</h4>
            <?php foreach ($subjects as $s): ?>
                <a href="manage_subjects.php?subject_id=<?php echo $s['id']; ?>"
                   style="display:flex;align-items:center;justify-content:space-between;padding:.7rem 0.9rem;border-radius:8px;background:#f8fafc;text-decoration:none;color:#0f172a;">
                    <div style="font-weight:600;"><?php echo htmlspecialchars($s['subject_name']); ?></div>
                    <div style="background:#e6eefb;color:#1e293b;padding:.25rem .6rem;border-radius:999px;font-weight:700;font-size:.78rem;">
                        <?php echo htmlspecialchars($s['subject_code']); ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Right panel -->
        <div style="flex:1;">
            <?php if ($flash): ?>
                <div class="flash <?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['msg']); ?></div>
            <?php endif; ?>

            <?php if (!$selected_subject): ?>
                <div style="background:#fff;border-radius:12px;padding:3.5rem;text-align:center;box-shadow:0 6px 18px rgba(2,6,23,0.06);">
                    <h3>Select a Subject</h3>
                    <p>Choose a subject from the sidebar to view and manage its students and materials.</p>
                </div>
            <?php else: ?>
                <!-- Subject -->
                <div style="background:#fff;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1rem;">
                    <h3><?php echo htmlspecialchars($selected_subject['subject_name']); ?></h3>
                    <div style="color:#64748b;"><?php echo htmlspecialchars($selected_subject['subject_code']); ?></div>

                    <!-- Add Material -->
                    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                        <h5>Add Material</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_material">
                            <input type="hidden" name="subject_id" value="<?php echo $selected_subject['id']; ?>">
                            <div style="margin-bottom:.6rem;">
                                <input type="text" name="title" placeholder="Title" required style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:6px;">
                            </div>
                            <div style="margin-bottom:.6rem;">
                                <textarea name="description" placeholder="Description" rows="3" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:6px;"></textarea>
                            </div>
                            <div style="margin-bottom:.6rem;">
                                <input type="file" name="file" style="width:100%;">
                            </div>
                            <div style="margin-bottom:.6rem;">
                                <input type="url" name="external_link" placeholder="External Link (optional)" style="width:100%;padding:.5rem;border:1px solid #e2e8f0;border-radius:6px;">
                            </div>
                            <button type="submit" style="background:#2563eb;color:#fff;padding:.5rem 1rem;border:none;border-radius:6px;cursor:pointer;">Upload</button>
                        </form>
                    </div>

                    <!-- Materials list -->
                    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                        <h5>Materials</h5>
                        <?php if (empty($materials)): ?>
                            <div>No materials yet.</div>
                        <?php else: ?>
                            <ul style="list-style:none;padding:0;">
                                <?php foreach ($materials as $m): ?>
                                    <li style="padding:.5rem 0;border-bottom:1px solid #f1f5f9;">
                                        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                                            <div>
                                                <strong><?php echo htmlspecialchars($m['title']); ?></strong>
                                                <div style="color:#64748b;"><?php echo htmlspecialchars($m['description']); ?></div>
                                                <?php if ($m['file_path']): ?>
                                                    <div><a href="../<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank">Download</a></div>
                                                <?php endif; ?>
                                                <?php if ($m['external_link']): ?>
                                                    <div><a href="<?php echo htmlspecialchars($m['external_link']); ?>" target="_blank">Link</a></div>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <!-- Edit button toggles edit form -->
                                                <button onclick="toggleEditForm(<?php echo $m['id']; ?>)" style="background:#e0f2fe;border:none;color:#0369a1;padding:.3rem .6rem;border-radius:6px;cursor:pointer;">✎ Edit</button>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_material">
                                                    <input type="hidden" name="material_id" value="<?php echo $m['id']; ?>">
                                                    <input type="hidden" name="subject_id" value="<?php echo $selected_subject['id']; ?>">
                                                    <button type="submit" style="background:#fee2e2;border:none;color:#b91c1c;padding:.3rem .6rem;border-radius:6px;cursor:pointer;">✖ Delete</button>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Hidden edit form -->
                                        <div id="edit-form-<?php echo $m['id']; ?>" class="edit-form" style="display:none;">
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="edit_material">
                                                <input type="hidden" name="material_id" value="<?php echo $m['id']; ?>">
                                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject['id']; ?>">
                                                <div style="margin-bottom:.4rem;">
                                                    <input type="text" name="title" value="<?php echo htmlspecialchars($m['title']); ?>" required style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:6px;">
                                                </div>
                                                <div style="margin-bottom:.4rem;">
                                                    <textarea name="description" rows="3" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:6px;"><?php echo htmlspecialchars($m['description']); ?></textarea>
                                                </div>
                                                <div style="margin-bottom:.4rem;">
                                                    <input type="file" name="file" style="width:100%;">
                                                    <?php if ($m['file_path']): ?>
                                                        <small>Current file: <?php echo htmlspecialchars(basename($m['file_path'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                                <div style="margin-bottom:.4rem;">
                                                    <input type="url" name="external_link" value="<?php echo htmlspecialchars($m['external_link']); ?>" style="width:100%;padding:.4rem;border:1px solid #e2e8f0;border-radius:6px;">
                                                </div>
                                                <button type="submit" style="background:#16a34a;color:#fff;padding:.4rem 1rem;border:none;border-radius:6px;cursor:pointer;">Save</button>
                                                <button type="button" onclick="toggleEditForm(<?php echo $m['id']; ?>)" style="background:#e2e8f0;padding:.4rem 1rem;border:none;border-radius:6px;cursor:pointer;">Cancel</button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- Students -->
                    <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                        <h5>Enrolled Students (<?php echo count($students); ?>)</h5>
                        <?php foreach ($students as $stu): ?>
                            <div class="student-row">
                                <div class="student-left">
                                    <div class="avatar-circle"><?php echo strtoupper(substr($stu['name'],0,2)); ?></div>
                                    <div>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars($stu['name']); ?></div>
                                        <div class="student-meta">@<?php echo htmlspecialchars($stu['username']); ?></div>
                                    </div>
                                </div>
                                <button class="remove-btn" onclick="confirmRemove(<?php echo $stu['id']; ?>, <?php echo $selected_subject['id']; ?>, '<?php echo htmlspecialchars(addslashes($stu['name'])); ?>')">✖ Remove</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<form id="removeForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="remove_student">
    <input type="hidden" name="student_id" id="remove_student_id">
    <input type="hidden" name="subject_id" id="remove_subject_id">
</form>

<script>
function confirmRemove(studentId, subjectId, name) {
    if (confirm(Are you sure you want to remove ${name}?)) {
        document.getElementById('remove_student_id').value = studentId;
        document.getElementById('remove_subject_id').value = subjectId;
        document.getElementById('removeForm').submit();
    }
}
function toggleEditForm(id) {
    const el = document.getElementById('edit-form-' + id);
    if (el.style.display === 'none') el.style.display = 'block';
    else el.style.display = 'none';
}
</script>

<?php include '../includes/teacher_footer.php'; ?>