<?php
// student_dashboard/student_dashboard.php
session_start();

// Sample data - replace with actual database queries
$student_name = $_SESSION['student_name'] ?? 'Student';
$courses_enrolled = 4;
$pending_assignments = 3;
$recent_grades = [
    ['course' => 'Mathematics', 'assignment' => 'Algebra Test', 'grade' => 'A', 'date' => '2024-03-15'],
    ['course' => 'Science', 'assignment' => 'Lab Report', 'grade' => 'B+', 'date' => '2024-03-12'],
    ['course' => 'English', 'assignment' => 'Essay', 'grade' => 'A-', 'date' => '2024-03-10']
];
$upcoming_deadlines = [
    ['course' => 'Mathematics', 'assignment' => 'Chapter 5 Quiz', 'due_date' => '2024-03-20'],
    ['course' => 'History', 'assignment' => 'Research Project', 'due_date' => '2024-03-25'],
    ['course' => 'Science', 'assignment' => 'Experiment Report', 'due_date' => '2024-03-28']
];

// Include the header
include '../includes/student_header.php';
?>



        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-book text-primary" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $courses_enrolled; ?></div>
                            <div class="text-muted">Courses Enrolled</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clipboard-list text-warning" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?php echo $pending_assignments; ?></div>
                            <div class="text-muted">Pending Assignments</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-chart-line text-success" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number">B+</div>
                            <div class="text-muted">Average Grade</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-check text-info" style="font-size: 2rem;"></i>
                        </div>
                        <div>
                            <div class="stat-number">12</div>
                            <div class="text-muted">Assignments Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="row">
            <!-- Recent Grades -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-trophy me-2"></i>Recent Grades
                    </div>
                    <div class="p-3">
                        <?php if (empty($recent_grades)): ?>
                            <p class="text-muted text-center py-3">No recent grades available</p>
                        <?php else: ?>
                            <?php foreach ($recent_grades as $grade): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($grade['assignment']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($grade['course']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="grade-badge grade-<?php echo strtolower(substr($grade['grade'], 0, 1)); ?>">
                                            <?php echo htmlspecialchars($grade['grade']); ?>
                                        </span>
                                        <div><small class="text-muted"><?php echo date('M j', strtotime($grade['date'])); ?></small></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="assignments.php" class="btn btn-outline-primary btn-sm">View All Grades</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Deadlines -->
            <div class="col-lg-6 mb-4">
                <div class="content-card">
                    <div class="card-header-custom">
                        <i class="fas fa-clock me-2"></i>Upcoming Deadlines
                    </div>
                    <div class="p-3">
                        <?php if (empty($upcoming_deadlines)): ?>
                            <p class="text-muted text-center py-3">No upcoming deadlines</p>
                        <?php else: ?>
                            <?php foreach ($upcoming_deadlines as $deadline): ?>
                                <?php 
                                    $days_left = ceil((strtotime($deadline['due_date']) - time()) / (60 * 60 * 24));
                                    $is_urgent = $days_left <= 3;
                                ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($deadline['assignment']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($deadline['course']); ?></small>
                                    </div>
                                    <div class="text-end">
                                        <div class="<?php echo $is_urgent ? 'deadline-urgent' : 'deadline-normal'; ?>">
                                            <?php echo $days_left; ?> days left
                                        </div>
                                        <small class="text-muted"><?php echo date('M j', strtotime($deadline['due_date'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="assignments.php" class="btn btn-outline-primary btn-sm">View All Assignments</a>
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
                                <a href="courses.php" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-book me-2"></i>View Courses
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="assignments.php" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-file-alt me-2"></i>Assignments
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="notifications.php" class="btn btn-outline-info w-100">
                                    <i class="fas fa-bell me-2"></i>Notifications
                                </a>
                            </div>
                            <div class="col-md-3 col-6 mb-2">
                                <a href="profile.php" class="btn btn-outline-success w-100">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php

// Include the footer
include '../includes/student_footer.php';
?>