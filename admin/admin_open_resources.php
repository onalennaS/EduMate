<?php
session_start();
require_once '../config/database.php';

// Ensure admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = "";

// --- Handle Delete ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM open_resources WHERE id = ?");
    if ($stmt->execute([$id])) {
        $success = "Resource deleted successfully.";
    } else {
        $errors[] = "Failed to delete resource.";
    }
}

// --- Handle Edit Load ---
$edit_resource = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM open_resources WHERE id = ?");
    $stmt->execute([$id]);
    $edit_resource = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- Handle Add / Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $button_label = trim($_POST['button_label'] ?? '');
    $subject_id = $_POST['subject_id'] ?? null;

    $link_type = $_POST['link_type'] ?? 'custom';
    $resource_link = '';

    if ($link_type === 'science') {
        $resource_link = '../open_resources/science_mixer/science.php';
    } elseif ($link_type === 'physics') {
        $resource_link = '../open_resources/physics_calc/calc-visuals.html';
    } elseif ($link_type === 'custom') {
        $resource_link = trim($_POST['custom_link'] ?? '');
    }

    if (!$title || !$resource_link || !$subject_id) {
        $errors[] = "All required fields must be filled.";
    }

    if (empty($errors)) {
        if (!empty($_POST['id'])) {
            // Update
            $stmt = $pdo->prepare("UPDATE open_resources SET title=?, description=?, resource_link=?, button_label=?, subject_id=? WHERE id=?");
            if ($stmt->execute([$title, $description, $resource_link, $button_label, $subject_id, $_POST['id']])) {
                $success = "Resource updated successfully!";
                $edit_resource = null; // reset form
            } else {
                $errors[] = "Failed to update resource.";
            }
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO open_resources (title, description, resource_link, button_label, subject_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$title, $description, $resource_link, $button_label, $subject_id])) {
                $success = "Resource added successfully!";
            } else {
                $errors[] = "Failed to add resource.";
            }
        }
    }
}

// Fetch subjects for dropdown
$stmt = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name ASC");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch resources with subjects
$stmt = $pdo->query("SELECT r.*, s.subject_name AS subject_name 
                     FROM open_resources r 
                     LEFT JOIN subjects s ON r.subject_id = s.id 
                     ORDER BY r.created_at DESC");
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Open Resources</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f8fafc; margin:0; padding:0; }
        header { background:#1d4ed8; color:white; padding:15px; text-align:center; font-size:20px; font-weight:bold; }
        main { max-width:900px; margin:20px auto; padding:0 20px; }
        .card { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:20px; }
        label { display:block; margin-top:10px; font-weight:bold; }
        input[type=text], textarea, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:6px; }
        button, .btn { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; }
        .btn-blue { background:#2563eb; color:white; }
        .btn-blue:hover { background:#1d4ed8; }
        .btn-green { background:#059669; color:white; }
        .btn-green:hover { background:#047857; }
        .btn-danger { background:#dc2626; color:white; }
        .btn-danger:hover { background:#b91c1c; }
        .success { background:#d1fae5; color:#065f46; padding:10px; border-radius:6px; margin-bottom:15px; }
        .error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:15px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        table th, table td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
        table th { background:#f1f5f9; }
        a { text-decoration:none; }
    </style>
</head>
<body>
    <header>Manage Open Resources</header>
    <main>
        <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="error"><?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div><?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card">
            <h2><?= $edit_resource ? "Edit Resource" : "Add New Resource"; ?></h2>
            <form method="POST">
                <?php if ($edit_resource): ?>
                    <input type="hidden" name="id" value="<?= $edit_resource['id'] ?>">
                <?php endif; ?>

                <label>Title</label>
                <input type="text" name="title" required value="<?= htmlspecialchars($edit_resource['title'] ?? '') ?>">

                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($edit_resource['description'] ?? '') ?></textarea>

                <label>Subject</label>
                <select name="subject_id" required>
                    <option value="">-- Select Subject --</option>
                    <?php foreach ($subjects as $sub): ?>
                        <option value="<?= $sub['id'] ?>" <?= (isset($edit_resource) && $edit_resource['subject_id']==$sub['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sub['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>Resource Type</label>
                <select name="link_type" id="link_type" onchange="toggleCustomInput()">
                    <option value="science" <?= isset($edit_resource) && $edit_resource['resource_link']==='../open_resources/science_mixer/science.php' ? 'selected' : '' ?>>Science Simulation</option>
                    <option value="physics" <?= isset($edit_resource) && $edit_resource['resource_link']==='../open_resources/physic_calc/calc-visuals.html' ? 'selected' : '' ?>>Physics Simulation</option>
                    <option value="custom" <?= isset($edit_resource) && !in_array($edit_resource['resource_link'], ['../open_resources/science_mixer/science.php','../open_resources/physic_calc/calc-visuals.html']) ? 'selected' : '' ?>>Custom URL</option>
                </select>

                <div id="customLinkField" style="margin-top:10px; <?= (isset($edit_resource) && !in_array($edit_resource['resource_link'], ['../open_resources/science_mixer/science.php','../open_resources/physic_calc/calc-visuals.html'])) ? '' : 'display:none;' ?>">
                    <label>Custom Resource Link</label>
                    <input type="text" name="custom_link" value="<?= htmlspecialchars($edit_resource['resource_link'] ?? '') ?>">
                </div>

                <label>Button Label</label>
                <input type="text" name="button_label" placeholder="e.g. Launch Simulation" value="<?= htmlspecialchars($edit_resource['button_label'] ?? '') ?>">

                <button type="submit" class="btn btn-blue"><?= $edit_resource ? "Update Resource" : "Add Resource"; ?></button>
            </form>
        </div>

        <!-- Existing Resources -->
        <h2>Existing Resources</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Button Label</th>
                    <th>Link</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resources as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['title']) ?></td>
                        <td><?= htmlspecialchars($r['subject_name']) ?></td>
                        <td><?= htmlspecialchars($r['button_label'] ?: 'Open Resource') ?></td>
                        <td><a href="<?= htmlspecialchars($r['resource_link']) ?>" target="_blank" style="color:#2563eb;">Open</a></td>
                        <td>
                            <a href="?edit=<?= $r['id'] ?>" class="btn btn-green">Edit</a>
                            <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger" onclick="return confirm('Delete this resource?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
    <script>
        function toggleCustomInput() {
            var type = document.getElementById("link_type").value;
            document.getElementById("customLinkField").style.display = (type === "custom") ? "block" : "none";
        }
    </script>
</body>
</html>
