<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Science Lab - EDUMATE</title>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --primary-neutral: #64748b;
            --secondary-neutral: #475569;
            --light-neutral: #f1f5f9;
            --dark-neutral: #334155;
            --accent-neutral: #94a3b8;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-neutral) 0%, var(--dark-neutral) 100%);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-x: hidden;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar-header {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            min-height: 80px;
        }

        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .sidebar-brand i {
            margin-right: 0.75rem;
            font-size: 1.8rem;
        }

        .sidebar.collapsed .sidebar-brand span {
            opacity: 0;
            width: 0;
        }

        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -15px;
            background: var(--primary-neutral);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .sidebar-toggle:hover {
            background: var(--secondary-neutral);
            transform: scale(1.1);
        }

        .sidebar-nav {
            padding: 1rem 0;
            flex: 1;
        }

        .nav-item {
            margin-bottom: 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 1rem;
            position: relative;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
            text-align: center;
            font-size: 1.1rem;
        }

        .nav-link span {
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .nav-link span {
            opacity: 0;
            width: 0;
        }

        .sidebar.collapsed .nav-link {
            margin-right: 0;
            border-radius: 0;
            justify-content: center;
        }

        .sidebar-footer {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            color: white;
            padding: 0.5rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-neutral);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.75rem;
            color: var(--primary-neutral);
            font-weight: bold;
        }

        .user-details h6 {
            margin: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .user-details small {
            opacity: 0.8;
            font-size: 0.75rem;
        }

        .sidebar.collapsed .user-info {
            justify-content: center;
        }

        .sidebar.collapsed .user-details {
            display: none;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            padding: 2rem;
        }

        .main-content.expanded {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Lab Container */
        .lab-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .lab-header {
            background: linear-gradient(135deg, var(--info-color), var(--primary-neutral));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .lab-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .lab-header p {
            margin: 0;
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .lab-content {
            padding: 2rem;
        }

        .lab-controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .control-group {
            display: flex;
            flex-direction: column;
        }

        .control-label {
            font-weight: 600;
            color: var(--dark-neutral);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .control-select {
            padding: 1rem;
            font-size: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            background: white;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .control-select:focus {
            outline: none;
            border-color: var(--info-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .mix-button-container {
            grid-column: 1 / -1;
            text-align: center;
            margin-top: 1rem;
        }

        .mix-button {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border: none;
            padding: 1rem 2rem;
            font-size: 1.2rem;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            min-width: 200px;
        }

        .mix-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .mix-button:active {
            transform: translateY(0);
        }

        .reaction-result {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px dashed #0ea5e9;
            border-radius: 15px;
            font-size: 1.2rem;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.3s ease;
            color: var(--dark-neutral);
            font-weight: 500;
        }

        .reaction-result.success {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-color: var(--success-color);
            color: #16a34a;
        }

        .reaction-result.warning {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border-color: var(--warning-color);
            color: #d97706;
        }

        .reaction-result.danger {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: var(--danger-color);
            color: #dc2626;
        }

        .reaction-visual {
            margin-top: 1.5rem;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        }

        /* Animation Styles */
        .explosion {
            width: 80px;
            height: 80px;
            background: radial-gradient(circle, #ff6b35, #f7931e, #ffd700);
            border-radius: 50%;
            animation: boom 0.8s ease-out forwards;
            box-shadow: 0 0 30px rgba(255, 107, 53, 0.6);
        }

        @keyframes boom {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(2);
                opacity: 0.8;
            }
            100% {
                transform: scale(4);
                opacity: 0;
            }
        }

        .bubbles {
            width: 15px;
            height: 15px;
            background: radial-gradient(circle, #60a5fa, #3b82f6);
            border-radius: 50%;
            position: absolute;
            animation: floatUp 3s ease-in infinite;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }

        @keyframes floatUp {
            0% {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            50% {
                transform: translateY(-40px) scale(1.2);
                opacity: 0.8;
            }
            100% {
                transform: translateY(-80px) scale(0.8);
                opacity: 0;
            }
        }

        .salt-crystals {
            width: 20px;
            height: 20px;
            background: #f8fafc;
            border: 2px solid #94a3b8;
            position: absolute;
            animation: crystallize 2s ease-out forwards;
        }

        @keyframes crystallize {
            0% {
                transform: rotate(0deg) scale(0);
                opacity: 0;
            }
            50% {
                transform: rotate(180deg) scale(1.2);
                opacity: 0.8;
            }
            100% {
                transform: rotate(360deg) scale(1);
                opacity: 1;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .mobile-toggle {
                position: fixed;
                top: 20px;
                left: 20px;
                background: var(--primary-neutral);
                color: white;
                border: none;
                border-radius: 8px;
                padding: 0.5rem;
                z-index: 1001;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .sidebar-overlay.show {
                opacity: 1;
                visibility: visible;
            }

            .lab-controls {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .lab-header h1 {
                font-size: 2rem;
            }

            .lab-content {
                padding: 1.5rem;
            }
        }

        /* Additional Enhancements */
        .chemical-icon {
            display: inline-block;
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }

        .reset-button {
            background: linear-gradient(135deg, var(--accent-neutral), var(--secondary-neutral));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            border-radius: 25px;
            cursor: pointer;
            margin-left: 1rem;
            transition: all 0.3s ease;
        }

        .reset-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(148, 163, 184, 0.3);
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle d-md-none" onclick="toggleMobileSidebar()" style="display: none;">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay d-md-none" onclick="toggleMobileSidebar()" style="display: none;"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="student_dashboard.php" class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>EDUMATE</span>
            </a>
            <!-- Desktop Toggle Button -->
            <button class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-angle-left" id="toggleIcon"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <div class="sidebar-nav">
            <ul class="nav flex-column" style="list-style: none; padding: 0; margin: 0;">
                <li class="nav-item">
                    <a class="nav-link" href="student_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="courses.php">
                        <i class="fas fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="science-lab.php">
                        <i class="fas fa-flask"></i>
                        <span>Science Lab</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="assignments.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Assignments & Tests</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="notifications.php">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    S
                </div>
                <div class="user-details">
                    <h6>Student</h6>
                    <small>Lab Assistant</small>
                </div>
            </div>
            <a class="nav-link mt-2" href="../logout.php" onclick="confirmLogout(event)">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="lab-container">
            <div class="lab-header">
                <h1><i class="fas fa-flask"></i> Virtual Science Lab</h1>
                <p>Mix chemicals safely and observe fascinating reactions!</p>
            </div>
            
            <div class="lab-content">
                <div class="lab-controls">
                    <div class="control-group">
                        <label class="control-label">
                            <i class="fas fa-vial chemical-icon" style="color: #3b82f6;"></i>
                            Chemical A
                        </label>
                        <select id="chemicalA" class="control-select">
                            <option value="">Select Chemical A</option>
                            <option value="Water">💧 Water (H₂O)</option>
                            <option value="Sodium">⚡ Sodium (Na)</option>
                            <option value="Vinegar">🍋 Vinegar (CH₃COOH)</option>
                        </select>
                    </div>

                    <div class="control-group">
                        <label class="control-label">
                            <i class="fas fa-flask chemical-icon" style="color: #10b981;"></i>
                            Chemical B
                        </label>
                        <select id="chemicalB" class="control-select">
                            <option value="">Select Chemical B</option>
                            <option value="Chlorine">☁️ Chlorine (Cl₂)</option>
                            <option value="Baking Soda">🧂 Baking Soda (NaHCO₃)</option>
                            <option value="Sodium">⚡ Sodium (Na)</option>
                        </select>
                    </div>

                    <div class="mix-button-container">
                        <button class="mix-button" onclick="mixChemicals()">
                            <i class="fas fa-magic"></i> Mix Chemicals
                        </button>
                        <button class="reset-button" onclick="resetLab()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

                <div class="reaction-result" id="reactionResult">
                    <i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #0ea5e9;"></i>
                    Select two chemicals and click "Mix Chemicals" to see the reaction!
                </div>

                <div class="reaction-visual" id="reactionVisual">
                    <!-- Reaction animations will appear here -->
                </div>
            </div>
        </div>
        
        <!-- Hidden audio element -->
        <audio id="reactionSound" preload="auto">
            <!-- You can add audio source here if you have reaction sound files -->
        </audio>
    </main>

    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const toggleIcon = document.getElementById('toggleIcon');
            
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            if (sidebar.classList.contains('collapsed')) {
                toggleIcon.classList.remove('fa-angle-left');
                toggleIcon.classList.add('fa-angle-right');
            } else {
                toggleIcon.classList.remove('fa-angle-right');
                toggleIcon.classList.add('fa-angle-left');
            }
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }

        // Lab functionality
        function mixChemicals() {
            const chemA = document.getElementById("chemicalA").value;
            const chemB = document.getElementById("chemicalB").value;
            const resultDiv = document.getElementById("reactionResult");
            const visualDiv = document.getElementById("reactionVisual");
            const sound = document.getElementById("reactionSound");

            // Clear previous results
            visualDiv.innerHTML = "";
            resultDiv.className = "reaction-result";

            if (!chemA || !chemB) {
                resultDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Please select both chemicals first!';
                resultDiv.classList.add('warning');
                return;
            }

            let reactionText = '<i class="fas fa-meh"></i> No noticeable reaction occurred.';
            let animation = null;
            let resultClass = '';

            // Define reactions
            const reactionMap = {
                "Water+Sodium": {
                    text: '<i class="fas fa-explosion"></i> EXPLOSIVE REACTION! Sodium reacts violently with water, producing hydrogen gas and heat!',
                    animation: "explosion",
                    class: "danger",
                    sound: true
                },
                "Vinegar+Baking Soda": {
                    text: '<i class="fas fa-wind"></i> Fizzing reaction! Vinegar and baking soda produce carbon dioxide bubbles!',
                    animation: "bubbles",
                    class: "success",
                    sound: false
                },
                "Sodium+Chlorine": {
                    text: '<i class="fas fa-gem"></i> Chemical bonding! Sodium and chlorine form table salt (NaCl)!',
                    animation: "crystals",
                    class: "success",
                    sound: false
                }
            };

            // Check for reaction
            const key1 = `${chemA}+${chemB}`;
            const key2 = `${chemB}+${chemA}`;
            let reaction = reactionMap[key1] || reactionMap[key2];

            if (reaction) {
                reactionText = reaction.text;
                resultClass = reaction.class;

                // Create animations
                if (reaction.animation === "explosion") {
                    const explosion = document.createElement("div");
                    explosion.className = "explosion";
                    visualDiv.appendChild(explosion);
                } else if (reaction.animation === "bubbles") {
                    // Create multiple bubbles
                    for (let i = 0; i < 15; i++) {
                        setTimeout(() => {
                            const bubble = document.createElement("div");
                            bubble.className = "bubbles";
                            bubble.style.left = `${Math.random() * 80 + 10}%`;
                            bubble.style.animationDelay = `${Math.random() * 0.5}s`;
                            visualDiv.appendChild(bubble);
                            
                            // Remove bubble after animation
                            setTimeout(() => {
                                if (bubble.parentNode) {
                                    bubble.parentNode.removeChild(bubble);
                                }
                            }, 3000);
                        }, i * 100);
                    }
                } else if (reaction.animation === "crystals") {
                    // Create salt crystals
                    for (let i = 0; i < 8; i++) {
                        setTimeout(() => {
                            const crystal = document.createElement("div");
                            crystal.className = "salt-crystals";
                            crystal.style.left = `${Math.random() * 70 + 15}%`;
                            crystal.style.top = `${Math.random() * 50 + 25}%`;
                            crystal.style.animationDelay = `${i * 0.1}s`;
                            visualDiv.appendChild(crystal);
                        }, i * 150);
                    }
                }

                // Play sound effect (if available)
                if (reaction.sound && sound.canPlayType) {
                    sound.currentTime = 0;
                    sound.play().catch(e => console.log('Audio play failed:', e));
                }
            }

            // Update result display
            resultDiv.innerHTML = reactionText;
            if (resultClass) {
                resultDiv.classList.add(resultClass);
            }
        }

        function resetLab() {
            document.getElementById("chemicalA").value = "";
            document.getElementById("chemicalB").value = "";
            document.getElementById("reactionResult").innerHTML = '<i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #0ea5e9;"></i>Select two chemicals and click "Mix Chemicals" to see the reaction!';
            document.getElementById("reactionResult").className = "reaction-result";
            document.getElementById("reactionVisual").innerHTML = "";
        }

        function confirmLogout(event) {
            event.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../logout.php';
            }
        }

        // Handle mobile responsiveness
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar) sidebar.classList.remove('show');
                if (overlay) overlay.classList.remove('show');
            }
        });

        // Show mobile elements on smaller screens
        window.addEventListener('load', () => {
            const mobileToggle = document.querySelector('.mobile-toggle');
            const sidebarOverlay = document.querySelector('.sidebar-overlay');
            
            function checkScreenSize() {
                if (window.innerWidth < 768) {
                    if (mobileToggle) mobileToggle.style.display = 'block';
                    if (sidebarOverlay) sidebarOverlay.style.display = 'block';
                } else {
                    if (mobileToggle) mobileToggle.style.display = 'none';
                    if (sidebarOverlay) sidebarOverlay.style.display = 'none';
                }
            }
            
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
        });
    </script>
</body>
</html>