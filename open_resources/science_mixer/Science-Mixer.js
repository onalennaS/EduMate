document.addEventListener('DOMContentLoaded', () => {
    // --- Chemical Mixer Animation ---
    const simulateBtn = document.getElementById('simulateBtn');
    const chemicalAInput = document.getElementById('chemicalA');
    const chemicalBInput = document.getElementById('chemicalB');
    const reactionResultDisplay = document.getElementById('reaction-result');
    const reactionCanvas = document.getElementById('reactionCanvas');
    const reactionCtx = reactionCanvas.getContext('2d');

    // Define reaction data
    const reactions = [
        {
            chemicals: ['baking soda', 'vinegar'],
            outcome: 'bubbles',
            message: 'Bubbles! (A gas is formed)'
        },
        {
            chemicals: ['sodium', 'water'],
            outcome: 'explosion',
            message: 'Explosion! (An explosive gas is produced)'
        },
        {
            chemicals: ['lead nitrate', 'potassium iodide'],
            outcome: 'solid',
            message: 'Solid Formation! (A yellow solid is precipitated)'
        }
    ];

    // Animation state variables
    let reactionAnimationFrameId = null;
    let explosionFrameId = null;
    let solidParticles = [];

    // Find a reaction based on user input
    const getReaction = (chemA, chemB) => {
        const normalizedA = chemA.toLowerCase().trim();
        const normalizedB = chemB.toLowerCase().trim();
        return reactions.find(reaction => {
            return reaction.chemicals.includes(normalizedA) && reaction.chemicals.includes(normalizedB);
        });
    };

    // Animate for bubbles
    const animateBubbles = () => {
        let bubbles = [];
        const createBubbles = () => {
            const count = 50;
            for (let i = 0; i < count; i++) {
                bubbles.push({
                    x: Math.random() * reactionCanvas.width,
                    y: reactionCanvas.height,
                    radius: Math.random() * 5 + 2,
                    opacity: 1,
                    speed: Math.random() * 1.5 + 0.5
                });
            }
        };

        const drawBubbles = () => {
            reactionCtx.clearRect(0, 0, reactionCanvas.width, reactionCanvas.height);
            bubbles.forEach(bubble => {
                reactionCtx.beginPath();
                reactionCtx.arc(bubble.x, bubble.y, bubble.radius, 0, Math.PI * 2);
                reactionCtx.fillStyle = `rgba(173, 216, 230, ${bubble.opacity})`;
                reactionCtx.fill();
            });
        };

        const updateBubbles = () => {
            bubbles = bubbles.filter(bubble => bubble.y + bubble.radius > 0);
            bubbles.forEach(bubble => {
                bubble.y -= bubble.speed;
                bubble.opacity -= 0.01;
            });
            drawBubbles();
            reactionAnimationFrameId = requestAnimationFrame(updateBubbles);
        };

        createBubbles();
        updateBubbles();
    };

    // Animate for solid formation
    const animateSolid = () => {
        let particles = [];
        const color = '#ffc107'; // A nice yellow for lead iodide
        const startY = reactionCanvas.height / 2;
        const particleCount = 1000;

        for (let i = 0; i < particleCount; i++) {
            particles.push({
                x: reactionCanvas.width * Math.random(),
                y: startY,
                targetY: reactionCanvas.height - 10,
                velocity: 0,
                size: Math.random() * 2 + 1,
                opacity: 1
            });
        }

        const drawParticles = () => {
            reactionCtx.clearRect(0, 0, reactionCanvas.width, reactionCanvas.height);
            particles.forEach(p => {
                reactionCtx.beginPath();
                reactionCtx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                reactionCtx.fillStyle = `rgba(255, 193, 7, ${p.opacity})`;
                reactionCtx.fill();
            });
        };

        const updateParticles = () => {
            particles.forEach(p => {
                p.velocity += 0.05;
                p.y += p.velocity;
                if (p.y > p.targetY) {
                    p.y = p.targetY;
                    p.velocity *= -0.1; // Bounce effect
                }
            });
            drawParticles();
            reactionAnimationFrameId = requestAnimationFrame(updateParticles);
        };

        updateParticles();
    };

    // Animate for explosion
    const animateExplosion = () => {
        reactionCanvas.classList.add('explosion');
        const flash = document.createElement('div');
        flash.id = 'explosion-flash';
        reactionCanvas.parentElement.appendChild(flash);

        setTimeout(() => {
            reactionCanvas.classList.remove('explosion');
            if (flash) flash.remove();
        }, 1000);
    };

    // Main simulate function
    simulateBtn.addEventListener('click', () => {
        if (reactionAnimationFrameId) {
            cancelAnimationFrame(reactionAnimationFrameId);
        }

        const chemA = chemicalAInput.value;
        const chemB = chemicalBInput.value;
        const reaction = getReaction(chemA, chemB);

        reactionResultDisplay.textContent = reaction ? `Result: ${reaction.message}` : `Result: No known reaction found.`;
        reactionCtx.clearRect(0, 0, reactionCanvas.width, reactionCanvas.height);

        if (reaction) {
            switch (reaction.outcome) {
                case 'bubbles':
                    animateBubbles();
                    break;
                case 'solid':
                    animateSolid();
                    break;
                case 'explosion':
                    animateExplosion();
                    break;
            }
        }
    });

    // Initial calculations and start animation for the default active tab
    // Note: The previous physics animations have been removed.
});