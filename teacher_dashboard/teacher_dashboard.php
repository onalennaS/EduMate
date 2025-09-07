<?php
// teacher_dashboard/teacher_dashboard.php

// Set page-specific variables before including header
$page_title = 'Teacher Dashboard';

// Additional CSS for this specific page (optional)
$additional_css = '
    /* Add any page-specific styles here */
';

// Additional JavaScript for this page (optional)
$additional_js = '
    // Grade submission function
    function gradeSubmission(submissionId) {
        Swal.fire({
            title: "Grade Submission",
            text: "Opening grading interface...",
            icon: "info",
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            // Redirect to grading page
            window.location.href = "assignments.php?grade=" + submissionId;
        });
    }
';

// Include the common header
include_once '../includes/teacher_header.php';

// Sample data - replace with actual database queries
$total_students = 45;
$courses_taught = 3;
$pending_to_grade = 8;
$recent_submissions = [
    ['student' => 'John Doe', 'assignment' => 'Math Quiz 3', 'course' => 'Mathematics', 'submitted_at' => '2024-03-15 14:30'],
    ['student' => 'Jane Smith', 'assignment' => 'Science Lab Report', 'course' => 'Physics', 'submitted_at' => '2024-03-15 13:45'],
    ['student' => 'Mike Johnson', 'assignment' => 'Essay Assignment', 'course' => 'English', 'submitted_at' => '2024-03-15 12:20']
];
$announcements = [
    ['title' => 'New Grading System Update', 'date' => '2024-03-14'],
    ['title' => 'Parent-Teacher Conference', 'date' => '2024-03-20'],
    ['title' => 'Midterm Exam Schedule', 'date' => '2024-03-25']
];
?>

<!-- Welcome Section -->
<div class="welcome-card">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-3">Welcome back, <?php echo htmlspecialchars($teacher_name); ?>! 👨‍🏫</h1>
            <p class="mb-0 opacity-90">Ready to inspire and educate? Check out your latest updates and student progress.</p>
        </div>
        <div class="col-md-4 text-end d-none d-md-block">
            <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; opacity: 0.3;"></i>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-users text-success" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $total_students; ?></div>
                    <div class="text-muted">Total Students</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-book-open text-primary" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $courses_taught; ?></div>
                    <div class="text-muted">Courses Teaching</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-clipboard-check text-warning" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number"><?php echo $pending_to_grade; ?></div>
                    <div class="text-muted">Pending to Grade</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <i class="fas fa-chart-line text-info" style="font-size: 2rem;"></i>
                </div>
                <div>
                    <div class="stat-number">A-</div>
                    <div class="text-muted">Average Class Grade</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Content Grid -->
<div class="row">
    <!-- Recent Submissions -->
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="card-header-custom">
                <i class="fas fa-upload me-2"></i>Recent Student Submissions
            </div>
            <div class="p-3">
                <?php if (empty($recent_submissions)): ?>
                    <p class="text-muted text-center py-3">No recent submissions</p>
                <?php else: ?>
                    <?php foreach ($recent_submissions as $submission): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($submission['student']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($submission['assignment']); ?> - <?php echo htmlspecialchars($submission['course']); ?></small>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-outline-success btn-sm" onclick="gradeSubmission(<?php echo rand(1,100); ?>)">
                                    <i class="fas fa-check me-1"></i>Grade
                                </button>
                                <div><small class="text-muted"><?php echo date('M j, g:i A', strtotime($submission['submitted_at'])); ?></small></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="assignments.php" class="btn btn-outline-success btn-sm">View All Submissions</a>
                </div>
            </div>
        </div>
    </div>

    <!-- System Announcements -->
    <div class="col-lg-6 mb-4">
        <div class="content-card">
            <div class="card-header-custom">
                <i class="fas fa-bullhorn me-2"></i>System Announcements
            </div>
            <div class="p-3">
                <?php if (empty($announcements)): ?>
                    <p class="text-muted text-center py-3">No announcements</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                <small class="text-muted">Posted on <?php echo date('M j, Y', strtotime($announcement['date'])); ?></small>
                            </div>
                            <div class="text-end">
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="notifications.php" class="btn btn-outline-success btn-sm">View All Announcements</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="content-card">
            <div class="card-header-custom">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </div>
            <div class="p-3">
                <div class="row">
                    <div class="col-md-3 col-6 mb-2">
                        <a href="manage_courses.php" class="btn btn-outline-success w-100">
                            <i class="fas fa-book-open me-2"></i>Manage Courses
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <a href="assignments.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-file-alt me-2"></i>Create Assignment
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <a href="students.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-users me-2"></i>View Students
                        </a>
                    </div>
                    <div class="col-md-3 col-6 mb-2">
                        <a href="profile.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include the common footer
include_once '../includes/teacher_footer.php';
?>