<?php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}
$student_name = $_SESSION['student_name'] ?? 'Student';

include '../../includes/student_header.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .tab-header {
            display: flex;
            background: #1e293b;
        }

        .tab-header button {
            flex: 1;
            padding: 15px;
            border: none;
            background: #334155;
            color: #e2e8f0;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .tab-header button.active {
            background: #2563eb;
            color: #fff;
            font-weight: bold;
        }

        .tab-header button:hover {
            background: #3b82f6;
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        .sub-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .sub-tabs button {
            flex: 1;
            padding: 10px;
            border: none;
            background: #e2e8f0;
            color: #334155;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .sub-tabs button.active {
            background: #2563eb;
            color: white;
            font-weight: bold;
        }

        .sub-tabs button:hover {
            background: #3b82f6;
            color: white;
        }

        .sub-content {
            display: none;
        }

        .sub-content.active {
            display: block;
        }

        .sim-box {
            margin-top: 15px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        select,
        input,
        button {
            margin: 5px 0;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #cbd5e1;
        }

        button {
            background: #2563eb;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background: #1d4ed8;
        }

        canvas {
            border: 1px solid #cbd5e1;
            background: #f1f5f9;
            display: block;
            margin: 15px 0;
        }

        .flex-row {
            display: flex;
            gap: 20px;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="tab-header">
            <button class="tab-link active" onclick="openTab(event,'science')">Science</button>
            <button class="tab-link" onclick="openTab(event,'maths')">Maths</button>
            <button class="tab-link" onclick="openTab(event,'english')">English</button>
        </div>

        <div id="maths" class="tab-content">
            <h2>Mathematics</h2>
            <div class="sub-tabs">
                <button class="sub-link active" onclick="openSubTab(event,'algebra')">Algebra</button>
                <button class="sub-link" onclick="openSubTab(event,'functions')">Functions</button>
            </div>

            <div id="algebra" class="sub-content active">
                <h3>Algebra Solver with Text-to-Speech & Voice Input</h3>
                <div class="sim-box" id="algebraBox">
                    <p>Speak or type an equation (linear or quadratic, e.g., 2x+3=7 or x^2+3x+2=0):</p>
                    <input type="text" id="algebraInput" placeholder="Type or speak equation" style="width:80%;">
                    <button onclick="startVoiceInput()">🎤 Speak</button><br><br>

                    <label>Choose Voice:</label><br>
                    <select id="algebraVoice"></select><br><br>

                    <button onclick="solveAlgebra()">Solve & Speak</button>
                    <button onclick="stopAlgebraSpeech()">Stop</button>

                    <div id="algebraSteps" style="margin-top:10px;"></div>
                </div>
            </div>

            <div id="functions" class="sub-content">
                <h3>Functions</h3>
                <p>Content coming soon...</p>
            </div>
        </div>

        <div id="science" class="tab-content active">
            <h2>Science Fundamentals</h2>
            <div class="sub-tabs">
                <button class="sub-link active" onclick="openSubTab(event,'chemistry')">Chemistry</button>
                <button class="sub-link" onclick="openSubTab(event,'physics')">Physics</button>
            </div>

            <div id="chemistry" class="sub-content active">
                <h3>Chemical Mixing Simulation</h3>
                <div class="sim-box">
                    <label>Select Chemical 1:</label><br>
                    <select id="chem1">
                        <option value="H2O">Water (H₂O)</option>
                        <option value="NaCl">Salt (NaCl)</option>
                        <option value="HCl">Hydrochloric Acid (HCl)</option>
                        <option value="NaOH">Sodium Hydroxide (NaOH)</option>
                        <option value="O2">Oxygen (O₂)</option>
                        <option value="CO2">Carbon Dioxide (CO₂)</option>
                        <option value="CH4">Methane (CH₄)</option>
                        <option value="Fe">Iron (Fe)</option>
                        <option value="CuSO4">Copper Sulfate (CuSO₄)</option>
                    </select><br>
                    <label>Select Chemical 2:</label><br>
                    <select id="chem2">
                        <option value="H2O">Water (H₂O)</option>
                        <option value="NaCl">Salt (NaCl)</option>
                        <option value="HCl">Hydrochloric Acid (HCl)</option>
                        <option value="NaOH">Sodium Hydroxide (NaOH)</option>
                        <option value="O2">Oxygen (O₂)</option>
                        <option value="CO2">Carbon Dioxide (CO₂)</option>
                        <option value="CH4">Methane (CH₄)</option>
                        <option value="Fe">Iron (Fe)</option>
                        <option value="CuSO4">Copper Sulfate (CuSO₄)</option>
                    </select><br>
                    <button onclick="mixChemicals()">Mix Chemicals</button>
                    <p id="chemResult"></p>
                    <canvas id="chemCanvas" width="400" height="150"></canvas>
                </div>
            </div>

        
            <div id="physics" class="sub-content">
                <h3>Physics Calculations</h3>
                <div class="sim-box">
                    <label>Mass (kg):</label><br>
                    <input type="number" id="mass" value="10"><br>
                    <label>Acceleration (m/s²):</label><br>
                    <input type="number" id="acc" value="5"><br>
                    <button onclick="calcForce()">Calculate Force</button>
                    <p id="forceResult"></p>
                    <canvas id="forceCanvas" width="400" height="150"></canvas>
                </div>

                <h3>Falling Object Simulation</h3>
                <div class="sim-box">
                    <label>Initial Height (m):</label><br>
                    <input type="number" id="initHeight" value="10"><br>
                    <label>Gravity Strength (m/s²):</label><br>
                    <input type="number" id="gravityStrength" value="9.8" step="0.1"><br>
                    <button onclick="startFalling()">Start Simulation</button>
                    <div class="flex-row">
                        <canvas id="fallCanvas" width="400" height="300"></canvas>
                        <canvas id="graphCanvas" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        function openTab(evt, id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active'); evt.currentTarget.classList.add('active');
        }
        function openSubTab(evt, id) {
            document.querySelectorAll('.sub-content').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sub-link').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active'); evt.currentTarget.classList.add('active');
        }

        function mixChemicals() {
            const c1 = document.getElementById("chem1").value, c2 = document.getElementById("chem2").value;
            const ctx = document.getElementById("chemCanvas").getContext("2d");
            ctx.clearRect(0, 0, 400, 150); let r = "";
            if ((c1 === "HCl" && c2 === "NaOH") || (c1 === "NaOH" && c2 === "HCl")) { r = "Neutralization: HCl+NaOH→NaCl+H₂O"; animateBubbles(ctx, "green"); }
            else if ((c1 === "NaCl" && c2 === "H2O") || (c1 === "H2O" && c2 === "NaCl")) { r = "Salt dissolves in water."; animateBubbles(ctx, "blue"); }
            else if ((c1 === "CH4" && c2 === "O2") || (c1 === "O2" && c2 === "CH4")) { r = "Combustion: CH₄+2O₂→CO₂+2H₂O"; flashColor(ctx, "orange"); }
            else if ((c1 === "Fe" && c2 === "O2") || (c1 === "O2" && c2 === "Fe")) { r = "Rusting: Fe+O₂+H₂O→Fe₂O₃·xH₂O"; flashColor(ctx, "brown"); }
            else if ((c1 === "CuSO4" && c2 === "H2O") || (c1 === "H2O" && c2 === "CuSO4")) { r = "CuSO₄ dissolves forming blue solution."; animateBubbles(ctx, "skyblue"); }
            else if (c1 === c2) { r = "No reaction when same chemical."; flashColor(ctx, "gray"); }
            else { r = "No significant reaction."; flashColor(ctx, "yellow"); }
            document.getElementById("chemResult").innerText = r;
        }
        function animateBubbles(ctx, color) {
            let b = []; for (let i = 0; i < 20; i++) { b.push({ x: Math.random() * 400, y: 150, r: 5 + Math.random() * 5 }); }
            function draw() { ctx.clearRect(0, 0, 400, 150); ctx.fillStyle = color; b.forEach(o => { ctx.beginPath(); ctx.arc(o.x, o.y, o.r, 0, Math.PI * 2); ctx.fill(); o.y -= 2; }); requestAnimationFrame(draw); } draw();
        }
        function flashColor(ctx, color) { let f = 0; function flash() { ctx.fillStyle = color; ctx.fillRect(0, 0, 400, 150); f++; if (f < 6) setTimeout(flash, 200); else ctx.clearRect(0, 0, 400, 150); } flash(); }

        let blockAnim;
        function calcForce() {
            const m = +document.getElementById("mass").value, a = +document.getElementById("acc").value, f = m * a;
            document.getElementById("forceResult").innerText = `Force = ${f} N`;
            const ctx = document.getElementById("forceCanvas").getContext("2d"); let x = 50; cancelAnimationFrame(blockAnim);
            function anim() {
                ctx.clearRect(0, 0, 400, 150); ctx.fillStyle = "gray"; ctx.fillRect(x, 80, 50, 50);
                ctx.strokeStyle = "red"; ctx.lineWidth = 3; ctx.beginPath(); ctx.moveTo(x + 25, 105); ctx.lineTo(x + 25 + f / 2, 105); ctx.stroke();
                ctx.beginPath(); ctx.moveTo(x + 25 + f / 2, 100); ctx.lineTo(x + 25 + f / 2 - 10, 95); ctx.lineTo(x + 25 + f / 2 - 10, 115); ctx.fillStyle = "red"; ctx.fill();
                x += a * 0.5; if (x + 50 < 400) blockAnim = requestAnimationFrame(anim);
            } anim();
        }

        let fallAnim;
        function startFalling() {
            const fallC = document.getElementById("fallCanvas"), ctx = fallC.getContext("2d");
            const gC = document.getElementById("graphCanvas"), gctx = gC.getContext("2d");
            let h = +document.getElementById("initHeight").value || 10, g = +document.getElementById("gravityStrength").value || 9.8;
            const scale = 20; // 1m=20px
            let y = (fallC.height - 20) - (h * scale), v = 0, t = 0;
            gctx.clearRect(0, 0, gC.width, gC.height);
            gctx.strokeStyle = "black"; gctx.strokeRect(40, 10, gC.width - 50, gC.height - 40);
            gctx.fillText("Time (s)", gC.width / 2 - 20, gC.height - 5); gctx.save(); gctx.translate(10, gC.height / 2); gctx.rotate(-Math.PI / 2);
            gctx.fillText("Height (m)", 0, 0); gctx.restore();
            let points = [];
            cancelAnimationFrame(fallAnim);
            function fall() {
                ctx.clearRect(0, 0, fallC.width, fallC.height);
                ctx.fillStyle = "blue"; ctx.beginPath(); ctx.arc(200, y + 20, 15, 0, Math.PI * 2); ctx.fill();
                v += g * 0.016; y += v * scale * 0.016; t += 0.016;
                let hNow = (fallC.height - 20 - y) / scale; if (hNow < 0) hNow = 0;
                points.push({ t, h: hNow });
                gctx.clearRect(41, 11, gC.width - 52, gC.height - 42);
                gctx.beginPath(); gctx.strokeStyle = "blue"; points.forEach((p, i) => {
                    let x = 40 + p.t * 40, y = gC.height - 40 - (p.h * 5);
                    if (i === 0) gctx.moveTo(x, y); else gctx.lineTo(x, y);
                }); gctx.stroke();
                if (y + 35 < fallC.height - 5) { fallAnim = requestAnimationFrame(fall); }
                else ctx.fillText("Impact!", 180, fallC.height - 10);
            }
            fall();
        }

        function openTab(evt, id) {
            document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-link').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
        function openSubTab(evt, id) {
            document.querySelectorAll('.sub-content').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sub-link').forEach(b => b.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            evt.currentTarget.classList.add('active');
        }

        function startVoiceInput() {
            if (!('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
                alert("Your browser does not support voice recognition.");
                return;
            }

            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = 'en-US';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;

            recognition.start();

            recognition.onresult = (event) => {
                const spokenText = event.results[0][0].transcript;
                document.getElementById("algebraInput").value = spokenText.replace(/\s+/g, ''); // remove spaces
            };

            recognition.onerror = (event) => {
                alert('Error in voice recognition: ' + event.error);
            };
        }

        let algebraUtterance;
        function populateVoices() {
            const voices = speechSynthesis.getVoices();
            const select = document.getElementById('algebraVoice');
            select.innerHTML = '';
            voices.forEach(v => {
                const option = document.createElement('option');
                option.value = v.name;
                option.text = `${v.name} (${v.lang})`;
                select.appendChild(option);
            });
        }
        speechSynthesis.onvoiceschanged = populateVoices;
        populateVoices();

        function stopAlgebraSpeech() {
            if (algebraUtterance) speechSynthesis.cancel();
        }

        function solveAlgebra() {
            stopAlgebraSpeech();
            const input = document.getElementById("algebraInput").value;
            if (!input) return alert("Please provide an equation.");

            const stepsDiv = document.getElementById("algebraSteps");
            stepsDiv.innerHTML = "";

            const eq = input.replace(/\s+/g, '');

            let quadraticMatch = eq.match(/^([+-]?\d*)x\^2([+-]\d*)x([+-]\d*)=0$/);
            let linearMatch = eq.match(/^([+-]?\d*)x([+-]\d*)=([+-]?\d*)$/);

            let steps = [];

            if (quadraticMatch) {
                let a = parseInt(quadraticMatch[1] || 1);
                let b = parseInt(quadraticMatch[2] || 0);
                let c = parseInt(quadraticMatch[3] || 0);
                steps.push(`Equation identified as quadratic: ${a}x squared plus ${b}x plus ${c} equals zero`);
                let discriminant = b * b - 4 * a * c;
                steps.push(`Compute discriminant: delta equals b squared minus 4ac, which is ${discriminant}`);
                if (discriminant >= 0) {
                    let x1 = (-b + Math.sqrt(discriminant)) / (2 * a);
                    let x2 = (-b - Math.sqrt(discriminant)) / (2 * a);
                    steps.push(`Compute solutions using quadratic formula: x equals minus b plus or minus square root of delta divided by 2a`);
                    steps.push(`x one equals ${x1}`);
                    steps.push(`x two equals ${x2}`);
                } else {
                    steps.push("Discriminant is negative. No real solution.");
                }
            } else if (linearMatch) {
                let a = parseInt(linearMatch[1] || 1);
                let b = parseInt(linearMatch[2] || 0);
                let c = parseInt(linearMatch[3] || 0);
                steps.push(`Equation identified as linear: ${a}x plus ${b} equals ${c}`);
                let x = (c - b) / a;
                steps.push(`Solve for x: x equals c minus b over a, which is ${x}`);
            } else {
                steps.push("Equation format not recognized. Use linear (ax+b=c) or quadratic (ax^2+bx+c=0).");
            }

            steps.forEach(s => {
                const p = document.createElement('p');
                p.textContent = s;
                stepsDiv.appendChild(p);
            });

            const voiceName = document.getElementById('algebraVoice').value;
            let index = 0;

            function speakNextStep() {
                if (index >= steps.length) return;

                algebraUtterance = new SpeechSynthesisUtterance(steps[index]);
                if (voiceName) {
                    const selectedVoice = speechSynthesis.getVoices().find(v => v.name === voiceName);
                    if (selectedVoice) algebraUtterance.voice = selectedVoice;
                }
                algebraUtterance.onend = () => {
                    index++;
                    setTimeout(speakNextStep, 500); 
                };
                speechSynthesis.speak(algebraUtterance);
            }

            speakNextStep();
        }

        let funcUtterance;
        function readFunctions() {
            if (funcUtterance) speechSynthesis.cancel();
            const text = document.getElementById('functionText').innerText;
            funcUtterance = new SpeechSynthesisUtterance(text);
            speechSynthesis.speak(funcUtterance);
        }



    </script>
</body>

</html>
<?php
include '../../includes/student_footer.php';
?>
