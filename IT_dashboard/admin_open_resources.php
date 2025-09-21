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

// --- Handle Add Resource ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $subject_id = $_POST['subject_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $resource_type = $_POST['resource_type'] ?? '';
    $custom_link = trim($_POST['custom_link'] ?? '');

    if ($subject_id && $title && $resource_type) {
        if ($resource_type === 'science') {
            $link = 'open_resources/science_mixer/science.php';
            $button_label = 'Launch Science Simulation';
        } elseif ($resource_type === 'physics') {
            $link = 'open_resources/physic_calc/calc-visuals.php';
            $button_label = 'Launch Physics Simulation';
        } elseif ($resource_type === 'custom') {
            if ($custom_link) {
                $link = $custom_link;
                $button_label = 'Open Resource';
            } else {
                $errors[] = "Custom link cannot be empty.";
            }
        } else {
            $errors[] = "Please select a valid resource type.";
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("
                INSERT INTO open_resources 
                (subject_id, title, description, resource_link, button_label, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt->execute([$subject_id, $title, $description, $link, $button_label])) {
                $success = "Resource added successfully!";
            } else {
                $errors[] = "Failed to add resource (DB error).";
            }
        }
    } else {
        $errors[] = "All required fields must be filled.";
    }
}

// --- Handle Delete Resource ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $resource_id = (int) $_POST['resource_id'];
    $stmt = $pdo->prepare("DELETE FROM open_resources WHERE id = ?");
    if ($stmt->execute([$resource_id])) {
        $success = "Resource deleted.";
    } else {
        $errors[] = "Failed to delete resource.";
    }
}

// --- Handle Edit Resource ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $resource_id = (int) $_POST['resource_id'];
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $button_label = trim($_POST['button_label'] ?? '');

    $stmt = $pdo->prepare("UPDATE open_resources SET title=?, description=?, button_label=? WHERE id=?");
    if ($stmt->execute([$title, $description, $button_label, $resource_id])) {
        $success = "Resource updated.";
    } else {
        $errors[] = "Failed to update resource.";
    }
}

// Fetch subjects
$subjects = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name")
    ->fetchAll(PDO::FETCH_ASSOC);

// Fetch resources
$stmt = $pdo->query("
    SELECT r.*, s.subject_name 
    FROM open_resources r 
    LEFT JOIN subjects s ON r.subject_id = s.id
    ORDER BY r.created_at DESC
");
$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);

// include header (assumed to output sidebar + opening <body>)
include '../includes/admin_header.php';
?>

<!--
  Main content area: this file assumes admin_header provides the sidebar (fixed/absolute).
  The script below will auto-detect the sidebar element and set the left margin for #admin-main.
-->
<main id="admin-main" style="padding:1.5rem;">
    <div style="max-width:1100px; margin:0 auto;">
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e): ?>
                    <?= htmlspecialchars($e) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Add Resource Form -->
        <div class="card">
            <h2>Add New Resource</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">

                <label>Subject
                    <select name="subject_id" required>
                        <option value="">-- Select Subject --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Title
                    <input type="text" name="title" required>
                </label>

                <label>Description
                    <textarea name="description" rows="3"></textarea>
                </label>

                <label>Resource Type
                    <select name="resource_type" id="resourceType" onchange="toggleCustomLink()" required>
                        <option value="">-- Select --</option>
                        <option value="science">Science Simulation</option>
                        <option value="physics">Physics Simulation</option>
                        <option value="custom">Other URL</option>
                    </select>
                </label>

                <div id="customLinkBox" style="display:none; margin-top:.5rem;">
                    <label>Custom Link
                        <input type="url" name="custom_link" placeholder="https://example.com/tool">
                    </label>
                </div>

                <button type="submit" class="btn-green" style="margin-top:.8rem;">Add Resource</button>
            </form>
        </div>

        <!-- Existing Resources -->
        <div class="card">
            <h2>Existing Resources</h2>
            <?php if (empty($resources)): ?>
                <p>No resources added yet.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($resources as $r): ?>
                        <div class="resource-card">
                            <div class="icon">
                                <?= ($r['resource_link'] && strpos($r['resource_link'], 'science') !== false) ? '' : ((strpos($r['resource_link'], 'calc-visuals') !== false) ? '' : '') ?>
                            </div>
                            <h3><?= htmlspecialchars($r['title']); ?></h3>
                            <p><strong>Subject:</strong> <?= htmlspecialchars($r['subject_name'] ?? '—') ?></p>
                            <?php if (!empty($r['description'])): ?>
                                <p class="muted"><?= htmlspecialchars($r['description']); ?></p>
                            <?php endif; ?>
                            <p>
                                <a href="<?= htmlspecialchars($r['resource_link']); ?>" target="_blank" class="btn-blue">
                                    <?= htmlspecialchars($r['button_label'] ?: 'Open Resource'); ?>
                                </a>
                            </p>

                            <!-- Edit/Delete Form -->
                            <form method="POST" style="margin-top:.6rem;">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="resource_id" value="<?= $r['id'] ?>">

                                <label style="display:block; margin-top:.5rem;">Title
                                    <input type="text" name="title" value="<?= htmlspecialchars($r['title']); ?>">
                                </label>

                                <label style="display:block; margin-top:.5rem;">Description
                                    <textarea name="description" rows="2"><?= htmlspecialchars($r['description']); ?></textarea>
                                </label>

                                <label style="display:block; margin-top:.5rem;">Button Label
                                    <input type="text" name="button_label" value="<?= htmlspecialchars($r['button_label']); ?>">
                                </label>

                                <div style="margin-top:.5rem;">
                                    <button type="submit" class="btn-green">Update</button>
                                    <button type="button" class="btn-red"
                                        onclick="confirmDelete(<?= $r['id'] ?>)">Delete</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hidden Delete Form -->
        <form id="delete-form" method="POST" style="display:none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="resource_id" id="delete-id">
        </form>
    </div>
</main>

<style>
    /* Card/grid styles (matching admin dashboard) */
    .card {
        background: white;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }

    .resource-card {
        background: #fff;
        border-radius: 12px;
        padding: 1rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        transition: transform .18s, box-shadow .18s;
        text-align: left;
    }

    .resource-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
    }

    .icon {
        font-size: 1.8rem;
        margin-bottom: .5rem;
    }

    .muted {
        color: #556;
        font-size: .95rem;
        margin-top: .25rem;
    }

    /* buttons */
    .btn-green,
    .btn-red,
    .btn-blue {
        padding: .5rem .85rem;
        border: none;
        border-radius: 8px;
        color: #fff;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-green {
        background: #059669;
    }

    .btn-red {
        background: #dc2626;
    }

    .btn-blue {
        background: #2563eb;
    }

    .btn-green:hover {
        background: #047857;
    }

    .btn-red:hover {
        background: #b91c1c;
    }

    .btn-blue:hover {
        background: #1d4ed8;
    }

    /* alerts */
    .success {
        background: #dcfce7;
        color: #065f46;
        padding: .6rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    .error {
        background: #fee2e2;
        color: #991b1b;
        padding: .6rem;
        border-radius: 6px;
        margin-bottom: 1rem;
    }

    /* Small screens */
    @media (max-width: 768px) {
        #admin-main {
            margin-left: 0 !important;
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>

<script>
    /**
     * Auto-adjust left margin of #admin-main to the sidebar width, if a sidebar element exists.
     * The script searches common sidebar selectors and uses its offsetWidth.
     * It also reduces margin to 0 for narrow screens.
     */
    (function () {
        const main = document.getElementById('admin-main');
        if (!main) return;

        function setMarginFromSidebar() {
            // look for common sidebar selectors
            const selectors = ['#sidebar', '.sidebar', '.app-sidebar', '.main-sidebar', '.left-sidebar', '.sidenav'];
            let sidebar = null;
            for (let sel of selectors) {
                sidebar = document.querySelector(sel);
                if (sidebar) break;
            }

            // if found, set margin-left to its width; otherwise, use default 260px
            let margin = 260;
            if (sidebar) {
                // if the sidebar is positioned fixed/absolute, we can use its offsetWidth
                margin = sidebar.offsetWidth || margin;
            }
            // on small screens remove the margin
            if (window.innerWidth <= 768) margin = 0;
            main.style.marginLeft = margin + 'px';
        }

        // initial
        setMarginFromSidebar();
        // update on resize
        window.addEventListener('resize', setMarginFromSidebar);
        // observe DOM changes (useful if sidebar toggles classes)
        const mo = new MutationObserver(setMarginFromSidebar);
        mo.observe(document.body, { attributes: true, subtree: true, childList: true });

    })();
    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this resource?")) {
            document.getElementById('delete-id').value = id;
            document.getElementById('delete-form').submit();
        }
    }
    function toggleCustomLink() {
        var type = document.getElementById('resourceType').value;
        document.getElementById('customLinkBox').style.display = (type === 'custom') ? 'block' : 'none';
    }
</script>

<?php include '../includes/admin_footer.php'; ?>