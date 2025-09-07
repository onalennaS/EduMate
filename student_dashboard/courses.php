# HTML FILE
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Science Lab</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <div class="container">
        <h1>🔬 Science Lab</h1>
        <p>Mix chemicals and observe reactions!</p>

        <div class="lab-controls">
            <select id="chemicalA">
                <option value="">Select Chemical A</option>
                <option value="Water">Water</option>
                <option value="Sodium">Sodium</option>
                <option value="Vinegar">Vinegar</option>
            </select>

            <select id="chemicalB">
                <option value="">Select Chemical B</option>
                <option value="Chlorine">Chlorine</option>
                <option value="Baking Soda">Baking Soda</option>
                <option value="Sodium">Sodium</option>
            </select>

            <button onclick="mixChemicals()">Mix </button>
        </div>

        <div class="reaction-result" id="reactionResult">
             The result of your reaction will 
        </div>

        <div class="reaction-visual" id="reactionVisual">
            
        </div>

        
        <audio id="reactionSound" src="assets/reaction.mp3"></audio>
    </div>

    <script src="script.js"></script>
</body>
</html>

# CSS file

body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 600px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

.lab-controls {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

select, button {
    padding: 10px;
    font-size: 16px;
}

button {
    background-color: #3b82f6;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

button:hover {
    background-color: #2563eb;
}

.reaction-result {
    padding: 20px;
    background-color: #eef2ff;
    border: 2px dashed #93c5fd;
    border-radius: 8px;
    font-size: 18px;
    min-height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.reaction-visual {
    margin-top: 20px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Explosion animation */
.explosion {
    width: 60px;
    height: 60px;
    background-color: orange;
    border-radius: 50%;
    animation: boom 0.6s ease-out forwards;
}

@keyframes boom {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(4);
        opacity: 0;
    }
}

/* Bubbles animation */
.bubbles {
    width: 10px;
    height: 10px;
    background-color: lightblue;
    border-radius: 50%;
    position: relative;
    animation: floatUp 2s ease-in infinite;
}

@keyframes floatUp {
    0% {
        top: 0;
        opacity: 1;
    }
    100% {
        top: -80px;
        opacity: 0;
    }
}


# Javascript

function mixChemicals() {
    const chemA = document.getElementById("chemicalA").value;
    const chemB = document.getElementById("chemicalB").value;
    const resultDiv = document.getElementById("reactionResult");
    const visualDiv = document.getElementById("reactionVisual");
    const sound = document.getElementById("reactionSound");

    visualDiv.innerHTML = "";
    resultDiv.innerHTML = ""; 

    if (!chemA || !chemB) {
        resultDiv.innerHTML = " Please select both chemicals!";
        return;
    }

    let reactionText = " No noticeable reaction.";
    let animation = null;

    
    const reactionMap = {
        "Water+Sodium": {
            text: " Boom! Violent reaction!  Sodium explodes in water!",
            animation: "explosion",
            sound: true
        },
        "Vinegar+Baking Soda": {
            text: "🫧 Fizzing reaction! CO₂ bubbles everywhere!",
            animation: "bubbles",
            sound: false
        },
        "Sodium+Chlorine": {
            text: "🧂 They bond and form salt!",
            animation: null,
            sound: false
        }
    };

    
    const key1 = `${chemA}+${chemB}`;
    const key2 = `${chemB}+${chemA}`;

    let reaction = reactionMap[key1] || reactionMap[key2];

    if (reaction) {
        reactionText = reaction.text;

        
        if (reaction.animation === "explosion") {
            const explosion = document.createElement("div");
            explosion.className = "explosion";
            visualDiv.appendChild(explosion);
        } else if (reaction.animation === "bubbles") {
            for (let i = 0; i < 10; i++) {
                const bubble = document.createElement("div");
                bubble.className = "bubbles";
                bubble.style.left = `${Math.random() * 300}px`;
                bubble.style.animationDelay = `${Math.random()}s`;
                visualDiv.appendChild(bubble);
            }
        }

        if (reaction.sound) {
            sound.currentTime = 0;
            sound.play();
        }
    }

    resultDiv.innerHTML = reactionText;
}
