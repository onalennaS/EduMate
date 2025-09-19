document.addEventListener('DOMContentLoaded', () => {
    // --- Tab Functionality ---
    const tabs = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));

            // Stop all running animations
            if (newtonAnimationFrameId) { cancelAnimationFrame(newtonAnimationFrameId); newtonAnimationFrameId = null; }
            if (vpmAnimationFrameId) { cancelAnimationFrame(vpmAnimationFrameId); vpmAnimationFrameId = null; }
            if (momentumAnimationFrameId) { cancelAnimationFrame(momentumAnimationFrameId); momentumAnimationFrameId = null; }

            tab.classList.add('active');
            const targetTab = tab.getAttribute('data-tab');
            const content = document.getElementById(`${targetTab}-content`);
            content.classList.add('active');

            // Start animation for the selected tab
            if (targetTab === 'newtons-laws') {
                calculateAcceleration();
                resetNewtonSimulation();
                newtonAnimationFrameId = requestAnimationFrame(animateNewton);
            } else if (targetTab === 'vpm') {
                updateVPM();
                vpmAnimationFrameId = requestAnimationFrame(animateVPM);
            } else if (targetTab === 'momentum') {
                calculateMomentum();
                resetMomentumSimulation();
                momentumAnimationFrameId = requestAnimationFrame(animateMomentum);
            }
        });
    });

    // --- Newton's Laws Calculator and Simulator ---
    const massInput = document.getElementById('mass');
    const forceInput = document.getElementById('force');
    const accelerationResult = document.getElementById('accelerationResult');
    const newtonCanvas = document.getElementById('newtonCanvas');
    const newtonCtx = newtonCanvas.getContext('2d');

    // Object state for the simulation
    let newtonObject = {
        x: 20,
        y: 75,
        velocity: 0,
        acceleration: 0,
        size: 20
    };
    let newtonAnimationFrameId = null;

    // Function to calculate acceleration and update the display
    const calculateAcceleration = () => {
        const mass = parseFloat(massInput.value);
        const force = parseFloat(forceInput.value);

        if (mass > 0) {
            newtonObject.acceleration = force / mass;
            accelerationResult.textContent = `a = ${newtonObject.acceleration.toFixed(2)} m/s²`;
        } else {
            newtonObject.acceleration = 0;
            accelerationResult.textContent = `a = N/A`;
        }
    };

    // Function to reset the object's position and velocity
    const resetNewtonSimulation = () => {
        newtonObject.x = 20;
        newtonObject.velocity = 0;
        newtonCtx.clearRect(0, 0, newtonCanvas.width, newtonCanvas.height);
        drawNewtonObject();
    };

    // Function to draw the object on the canvas
    const drawNewtonObject = () => {
        newtonCtx.beginPath();
        newtonCtx.arc(newtonObject.x, newtonObject.y, newtonObject.size, 0, Math.PI * 2);
        newtonCtx.fillStyle = `rgba(76, 110, 245, 1)`;
        newtonCtx.fill();
    };

    // Animation loop for the simulation
    const animateNewton = () => {
        newtonCanvas.width = newtonCanvas.offsetWidth;
        newtonCanvas.height = newtonCanvas.offsetHeight;
        newtonCtx.clearRect(0, 0, newtonCanvas.width, newtonCanvas.height);

        newtonObject.velocity += newtonObject.acceleration / 60;
        newtonObject.x += newtonObject.velocity;

        if (newtonObject.x > newtonCanvas.width + newtonObject.size) {
            resetNewtonSimulation();
        }

        drawNewtonObject();
        newtonAnimationFrameId = requestAnimationFrame(animateNewton);
    };

    massInput.addEventListener('input', () => { calculateAcceleration(); resetNewtonSimulation(); });
    forceInput.addEventListener('input', () => { calculateAcceleration(); resetNewtonSimulation(); });

    // --- Momentum Calculator and Simulator ---
    const momentumMassInput = document.getElementById('momentumMass');
    const velocityInput = document.getElementById('velocity');
    const momentumResult = document.getElementById('momentumResult');
    const momentumCanvas = document.getElementById('momentumCanvas');
    const momentumCtx = momentumCanvas.getContext('2d');

    let momentumObject = {
        x: 20,
        y: 75,
        size: 20,
        velocity: 0,
    };
    let momentumAnimationFrameId = null;

    const calculateMomentum = () => {
        const mass = parseFloat(momentumMassInput.value);
        const velocity = parseFloat(velocityInput.value);
        const momentum = mass * velocity;
        momentumResult.textContent = `p = ${momentum.toFixed(2)} kg⋅m/s`;
        momentumObject.velocity = velocity;
    };

    const resetMomentumSimulation = () => {
        momentumObject.x = 20;
        momentumCtx.clearRect(0, 0, momentumCanvas.width, momentumCanvas.height);
        drawMomentumObject();
    };

    const drawMomentumObject = () => {
        momentumCtx.beginPath();
        momentumCtx.arc(momentumObject.x, momentumObject.y, momentumObject.size, 0, Math.PI * 2);
        momentumCtx.fillStyle = `rgba(255, 105, 180, 1)`; /* A different color for momentum */
        momentumCtx.fill();
    };

    const animateMomentum = () => {
        momentumCanvas.width = momentumCanvas.offsetWidth;
        momentumCanvas.height = momentumCanvas.offsetHeight;
        momentumCtx.clearRect(0, 0, momentumCanvas.width, momentumCanvas.height);

        momentumObject.x += momentumObject.velocity;

        if (momentumObject.x > momentumCanvas.width + momentumObject.size) {
            resetMomentumSimulation();
        }

        drawMomentumObject();
        momentumAnimationFrameId = requestAnimationFrame(animateMomentum);
    };

    momentumMassInput.addEventListener('input', () => { calculateMomentum(); resetMomentumSimulation(); });
    velocityInput.addEventListener('input', () => { calculateMomentum(); resetMomentumSimulation(); });

    // --- VPM Visualizer ---
    const eventsPerMinuteInput = document.getElementById('eventsPerMinute');
    const hzOutput = document.getElementById('hz');
    const periodOutput = document.getElementById('period');
    const vpmCanvas = document.getElementById('vpmCanvas');
    const vpmCtx = vpmCanvas.getContext('2d');

    const updateVPM = () => {
        const vpm = parseFloat(eventsPerMinuteInput.value);
        if (vpm > 0) {
            const hz = vpm / 60;
            const period = 1 / hz;
            hzOutput.value = `${hz.toFixed(2)} Hz`;
            periodOutput.value = `${period.toFixed(2)} s`;
        } else {
            hzOutput.value = '0.00 Hz';
            periodOutput.value = 'Inf s';
        }
    };

    eventsPerMinuteInput.addEventListener('input', updateVPM);
    updateVPM();

    let vpmAnimationFrameId = null;
    const animateVPM = (timestamp) => {
        vpmCanvas.width = vpmCanvas.offsetWidth;
        vpmCanvas.height = vpmCanvas.offsetHeight;
        const centerX = vpmCanvas.width / 2;
        const centerY = vpmCanvas.height / 2;
        const maxRadius = Math.min(centerX, centerY) * 0.8;
        const vpm = parseFloat(eventsPerMinuteInput.value) || 0;
        const period = vpm > 0 ? 60 / vpm : Infinity;

        vpmCtx.clearRect(0, 0, vpmCanvas.width, vpmCanvas.height);

        if (period !== Infinity) {
            const elapsed = timestamp / 1000;
            const pulsePhase = (elapsed % period) / period;
            const radius = maxRadius * (1 - pulsePhase);
            const opacity = 1 - pulsePhase;

            vpmCtx.beginPath();
            vpmCtx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            vpmCtx.fillStyle = `rgba(76, 110, 245, ${opacity})`;
            vpmCtx.fill();
        }

        vpmAnimationFrameId = requestAnimationFrame(animateVPM);
    };

    // Initial calculations and start animation for the default active tab
    calculateAcceleration();
    newtonAnimationFrameId = requestAnimationFrame(animateNewton);
    updateVPM();
    vpmAnimationFrameId = requestAnimationFrame(animateVPM);
});