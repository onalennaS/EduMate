<?php

session_start();

include '../../../includes/student_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chemical & Physics Simulator</title>
    <link href="css-mixer.css" rel="stylesheet">
</head>

<body>

    <div class="container">

        <!-- Chemical Mixer Section -->
        <div class="panel chemical-mixer">
            <h2 class="panel-title">Chemical Mixer</h2>
            <p class="panel-description">Educational. Enter two common substances (for example: vinegar +
                baking soda) and press Simulate. The canvas shows a friendly animation of the reaction.</p>

            <div class="input-group">
                <label for="chemicalA">Chemical A</label>
                <input type="text" id="chemicalA" value="baking soda">
            </div>

            <div class="input-group">
                <label for="chemicalB">Chemical B</label>
                <input type="text" id="chemicalB" value="vinegar">
            </div>

            <button id="simulateBtn" class="btn">Simulate</button>

            <div class="canvas-container">
                <canvas id="reactionCanvas"></canvas>
            </div>
            <div id="reaction-result"></div>

        </div>

    </div>

    <script src="Science-Mixer.js"></script>
</body>

</html>

<?php
include '../../../includes/student_footer.php';
?>