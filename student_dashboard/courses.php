<?php
// student_dashboard/science-lab.php
session_start();

// Additional CSS specific to the science lab
$additional_css = "
:root {
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
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
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.lab-header p {
    margin: 0;
    font-size: 1rem;
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
    font-size: 0.9rem;;
}

.control-select {
    padding: 1rem;
    font-size: 0.9rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: white;
    transition: all 0.3s ease;
    appearance: none;
    background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e\");
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
    font-size: 0.9rem;;
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
    font-size: 0.9rem;;
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

/* Additional Enhancements */
.chemical-icon {
    display: inline-block;
    margin-right: 0.5rem;
    font-size: 0.9rem;;
}

.reset-button {
    background: linear-gradient(135deg, var(--accent-neutral), var(--secondary-neutral));
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    font-size: 0.9rem;;
    border-radius: 25px;
    cursor: pointer;
    margin-left: 1rem;
    transition: all 0.3s ease;
}

.reset-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(148, 163, 184, 0.3);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .lab-controls {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .lab-header h1 {
        font-size: 0.9rem;
    }

    .lab-content {
        padding: 1.5rem;
    }
}
";

// Additional JavaScript for lab functionality
$additional_js = "
// Lab functionality
function mixChemicals() {
    const chemA = document.getElementById('chemicalA').value;
    const chemB = document.getElementById('chemicalB').value;
    const resultDiv = document.getElementById('reactionResult');
    const visualDiv = document.getElementById('reactionVisual');
    const sound = document.getElementById('reactionSound');

    // Clear previous results
    visualDiv.innerHTML = '';
    resultDiv.className = 'reaction-result';

    if (!chemA || !chemB) {
        resultDiv.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> Please select both chemicals first!';
        resultDiv.classList.add('warning');
        return;
    }

    let reactionText = '<i class=\"fas fa-meh\"></i> No noticeable reaction occurred.';
    let animation = null;
    let resultClass = '';

    // Define reactions
    const reactionMap = {
        'Water+Sodium': {
            text: '<i class=\"fas fa-explosion\"></i> EXPLOSIVE REACTION! Sodium reacts violently with water, producing hydrogen gas and heat!',
            animation: 'explosion',
            class: 'danger',
            sound: true
        },
        'Vinegar+Baking Soda': {
            text: '<i class=\"fas fa-wind\"></i> Fizzing reaction! Vinegar and baking soda produce carbon dioxide bubbles!',
            animation: 'bubbles',
            class: 'success',
            sound: false
        },
        'Sodium+Chlorine': {
            text: '<i class=\"fas fa-gem\"></i> Chemical bonding! Sodium and chlorine form table salt (NaCl)!',
            animation: 'crystals',
            class: 'success',
            sound: false
        }
    };

    // Check for reaction
    const key1 = chemA + '+' + chemB;
    const key2 = chemB + '+' + chemA;
    let reaction = reactionMap[key1] || reactionMap[key2];

    if (reaction) {
        reactionText = reaction.text;
        resultClass = reaction.class;

        // Create animations
        if (reaction.animation === 'explosion') {
            const explosion = document.createElement('div');
            explosion.className = 'explosion';
            visualDiv.appendChild(explosion);
        } else if (reaction.animation === 'bubbles') {
            // Create multiple bubbles
            for (let i = 0; i < 15; i++) {
                setTimeout(() => {
                    const bubble = document.createElement('div');
                    bubble.className = 'bubbles';
                    bubble.style.left = (Math.random() * 80 + 10) + '%';
                    bubble.style.animationDelay = (Math.random() * 0.5) + 's';
                    visualDiv.appendChild(bubble);
                    
                    // Remove bubble after animation
                    setTimeout(() => {
                        if (bubble.parentNode) {
                            bubble.parentNode.removeChild(bubble);
                        }
                    }, 3000);
                }, i * 100);
            }
        } else if (reaction.animation === 'crystals') {
            // Create salt crystals
            for (let i = 0; i < 8; i++) {
                setTimeout(() => {
                    const crystal = document.createElement('div');
                    crystal.className = 'salt-crystals';
                    crystal.style.left = (Math.random() * 70 + 15) + '%';
                    crystal.style.top = (Math.random() * 50 + 25) + '%';
                    crystal.style.animationDelay = (i * 0.1) + 's';
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
    document.getElementById('chemicalA').value = '';
    document.getElementById('chemicalB').value = '';
    document.getElementById('reactionResult').innerHTML = '<i class=\"fas fa-info-circle\" style=\"margin-right: 0.5rem; color: #0ea5e9;\"></i>Select two chemicals and click \"Mix Chemicals\" to see the reaction!';
    document.getElementById('reactionResult').className = 'reaction-result';
    document.getElementById('reactionVisual').innerHTML = '';
}

// Page-specific initialization function
function pageInit() {
    // Any additional initialization for the science lab page
    console.log('Science Lab initialized');
}
";

// Include the header
include '../includes/student_header.php';
?>

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

<?php
// Include the footer
include '../includes/student_footer.php';
?>