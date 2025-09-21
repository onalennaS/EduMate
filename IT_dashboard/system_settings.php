<?php
session_start();
require_once '../config/database.php';

// Ensure admin only
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = null;

// --- Helper: get setting ---
function getSetting($pdo, $key, $default = '')
{
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['setting_value'] : $default;
}

// --- Handle Form Submit ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key === 'save_settings' || $key === 'MAX_FILE_SIZE')
            continue;
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $stmt->execute([$key, is_array($value) ? implode(',', $value) : $value]);
    }

    // Handle logo upload
    if (!empty($_FILES['school_logo']['name'])) {
        $target = '../uploads/' . basename($_FILES['school_logo']['name']);
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $target)) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('school_logo', ?)
                                   ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
            $stmt->execute([$target]);
        } else {
            $errors[] = "Failed to upload logo.";
        }
    }

    $success = "Settings updated successfully.";
}

// --- Load Settings ---
$school_name = getSetting($pdo, 'school_name');
$school_address = getSetting($pdo, 'school_address');
$contact_email = getSetting($pdo, 'contact_email');
$contact_phone = getSetting($pdo, 'contact_phone');
$school_logo = getSetting($pdo, 'school_logo');
$pwd_length = getSetting($pdo, 'password_length', 8);
$self_reg = getSetting($pdo, 'self_registration', 'disabled');
$teacher_uploads = getSetting($pdo, 'teacher_uploads', 'enabled');
$accessibility = explode(',', getSetting($pdo, 'default_accessibility', ''));
$grades = explode(',', getSetting($pdo, 'grades_available', '8,9,10,11,12'));
$auto_enroll = getSetting($pdo, 'auto_enroll', 'disabled');
$file_types = getSetting($pdo, 'file_types', 'pdf,docx,mp4');
$file_size = getSetting($pdo, 'file_size', '10'); // MB

include '../includes/admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Settings - EduMate</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex;
        }
.content {
            margin-left: 260px;
            /* match your sidebar width */
            padding: 2rem;
            flex: 1;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .1);
            margin-bottom: 1.5rem;
        }

        h2 {
            margin-top: 0;
        }

        label {
            display: block;
            margin: .75rem 0 .25rem;
            font-weight: bold;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: .6rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .checkbox-group label {
            font-weight: normal;
            display: inline-block;
            margin-right: 1rem;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: .75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            padding: .75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: .75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>  

    <div class="content">
            <h1>System Settings</h1>

            <?php if ($success): ?>
                <div class="success"><?= $success ?></div><?php endif; ?>
            <?php if ($errors): ?>
                <div class="error"><?php foreach ($errors as $e)
                    echo $e . "<br>"; ?></div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Institution Info -->
                <div class="card">
                    <h2>Institution Information</h2>
                    <label>School Name <input type="text" name="school_name"
                            value="<?= htmlspecialchars($school_name) ?>"></label>
                    <label>Address <textarea
                            name="school_address"><?= htmlspecialchars($school_address) ?></textarea></label>
                    <label>Contact Email <input type="email" name="contact_email"
                            value="<?= htmlspecialchars($contact_email) ?>"></label>
                    <label>Contact Phone <input type="text" name="contact_phone"
                            value="<?= htmlspecialchars($contact_phone) ?>"></label>
                    <label>Logo <input type="file" name="school_logo"></label>
                    <?php if ($school_logo): ?>
                        <p>Current Logo: <img src="<?= $school_logo ?>" alt="Logo" style="max-height:60px;"></p>
                    <?php endif; ?>
                </div>

                <!-- User Account Settings -->
                <div class="card">
                    <h2>User Account Settings</h2>
                    <label>Default Password Length <input type="number" name="password_length"
                            value="<?= htmlspecialchars($pwd_length) ?>"></label>
                    <label>Student Self-Registration
                        <select name="self_registration">
                            <option value="enabled" <?= $self_reg === 'enabled' ? 'selected' : '' ?>>Enabled</option>
                            <option value="disabled" <?= $self_reg === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </label>
                    <label>Teacher Uploads
                        <select name="teacher_uploads">
                            <option value="enabled" <?= $teacher_uploads === 'enabled' ? 'selected' : '' ?>>Enabled</option>
                            <option value="disabled" <?= $teacher_uploads === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </label>
                </div>

                <!-- Accessibility Defaults -->
                <div class="card">
                    <h2>Accessibility Defaults</h2>
                    <div class="checkbox-group">
                        <label><input type="checkbox" name="default_accessibility[]" value="visual"
                                <?= in_array('visual', $accessibility) ? 'checked' : '' ?>> Visual Impairment</label>
                        <label><input type="checkbox" name="default_accessibility[]" value="hearing"
                                <?= in_array('hearing', $accessibility) ? 'checked' : '' ?>> Hearing Impairment</label>
                        <label><input type="checkbox" name="default_accessibility[]" value="cognitive"
                                <?= in_array('cognitive', $accessibility) ? 'checked' : '' ?>> Cognitive Support</label>
                    </div>
                </div>

                <!-- Subject & Enrollment -->
                <div class="card">
                    <h2>Subject & Enrollment</h2>
                    <label>Grades Available <input type="text" name="grades_available"
                            value="<?= htmlspecialchars(implode(',', $grades)) ?>"></label>
                    <label>Auto-Enroll by Grade
                        <select name="auto_enroll">
                            <option value="enabled" <?= $auto_enroll === 'enabled' ? 'selected' : '' ?>>Enabled</option>
                            <option value="disabled" <?= $auto_enroll === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                        </select>
                    </label>
                </div>

                <!-- File Upload Settings -->
                <div class="card">
                    <h2>File Upload Settings</h2>
                    <label>Allowed File Types <input type="text" name="file_types"
                            value="<?= htmlspecialchars($file_types) ?>"></label>
                    <label>Max Upload Size (MB) <input type="number" name="file_size"
                            value="<?= htmlspecialchars($file_size) ?>"></label>
                </div>

                <!-- Admin Controls -->
                <div class="card">
                    <h2>Admin Controls</h2>
                    <p><button type="submit" name="save_settings">💾 Save Settings</button></p>
                    <p><a href="export_db.php" class="btn">📤 Export Database</a></p>
                </div>
            </form>
        </div>
</body>

</html>
<?php 
include '../includes/admin_footer.php';
?>