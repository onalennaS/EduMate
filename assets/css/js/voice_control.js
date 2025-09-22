document.addEventListener('DOMContentLoaded', () => {
    const voiceControlBtn = document.getElementById('voice-control-btn');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        voiceControlBtn.style.display = 'none';
        console.warn("Speech Recognition not supported in this browser.");
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.continuous = false; // Listen for a single command
    recognition.lang = 'en-US';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    let isListening = false;

    voiceControlBtn.addEventListener('click', () => {
        if (isListening) {
            recognition.stop();
            return;
        }
        recognition.start();
    });

    recognition.onstart = () => {
        isListening = true;
        voiceControlBtn.style.backgroundColor = '#dc3545'; // Red when listening
        voiceControlBtn.innerHTML = '<i class="fas fa-stop-circle"></i>';
    };

    recognition.onend = () => {
        isListening = false;
        voiceControlBtn.style.backgroundColor = '#3b82f6'; // Blue when idle
        voiceControlBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    };

    recognition.onerror = (event) => {
        console.error("Speech recognition error:", event.error);
    };

    // This is where the magic happens!
    recognition.onresult = (event) => {
        const command = event.results[0][0].transcript.trim().toLowerCase();
        console.log('Command received:', command);
        processCommand(command);
    };

    function processCommand(command) {
        // 1. Client-Side Navigation Commands
        if (command.includes('go to dashboard') || command.includes('open dashboard')) {
            speak("Navigating to your dashboard.");
            // Your dashboard URLs are different for students and teachers.
            // We can check the URL path to determine the user type.
            if (window.location.pathname.includes('student_dashboard')) {
                window.location.href = 'student_dashboard.php';
            } else {
                window.location.href = 'teacher_dashboard.php';
            }
        } else if (command.includes('go to my subjects') || command.includes('open subjects')) {
            speak("Opening your subjects page.");
            if (window.location.pathname.includes('student_dashboard')) {
                window.location.href = 'subjects.php';
            } else {
                window.location.href = 'manage_subjects.php';
            }
        } else if (command.includes('open assignments')) {
            speak("Loading assignments.");
            window.location.href = 'assignments.php';
        } else if (command.includes('log me out') || command.includes('logout')) {
            speak("Logging you out. Goodbye!");
            // Use your existing logout confirmation function
            setTimeout(() => confirmLogout(new Event('click')), 1000);
        }

        // 2. Server-Side Commands
        else if (command.startsWith('search for subject')) {
            // Example: "search for subject mathematics"
            const searchTerm = command.replace('search for subject', '').trim();
            speak(`Searching for subjects matching ${searchTerm}`);
            sendCommandToBackend({ action: 'search_subject', term: searchTerm });
        } else if (command.includes('upcoming deadlines')) {
            speak("Checking for upcoming deadlines.");
            sendCommandToBackend({ action: 'get_deadlines' });
        }

        // 3. Command not recognized
        else {
            speak("I didn't understand that command. Please try again.");
        }
    }

    async function sendCommandToBackend(data) {
        try {
            const response = await fetch('../api/voice_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.speak) {
                speak(result.speak);
            }
            // Further actions based on result can be handled here
            console.log('Backend response:', result);

        } catch (error) {
            console.error("Error communicating with backend:", error);
            speak("Sorry, I couldn't connect to the server.");
        }
    }

    // Speech Synthesis function
    function speak(text) {
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        window.speechSynthesis.speak(utterance);
    }
});