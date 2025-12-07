<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Add user_id to session if not set
if (!isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    include 'connection.php';
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
    }
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Knowledge Challenge</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #fff;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 800px;
            overflow: hidden;
        }
        
        header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .header-stats {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }
        
        .screen {
            display: none;
            padding: 30px;
        }
        
        .screen.active {
            display: block;
        }
        
        .welcome-content {
            text-align: center;
        }
        
        .welcome-content p {
            margin: 20px 0;
            font-size: 1.2rem;
            line-height: 1.6;
            color: #d1d1d1;
        }
        
        .categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .category {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .category:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .category i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .btn {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 5px;
            font-weight: 600;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .back-btn {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-50%) scale(1.05);
        }
        
        .quiz-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .question-progress {
            font-size: 1.2rem;
            color: #4facfe;
        }
        
        .question-text {
            font-size: 1.4rem;
            margin: 25px 0;
            line-height: 1.5;
        }
        
        .answers {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        
        .answer {
            background: rgba(255, 255, 255, 0.1);
            padding: 18px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .answer:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .answer.correct {
            background: rgba(46, 204, 113, 0.2);
            border: 1px solid #2ecc71;
        }
        
        .answer.incorrect {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 2px solid #4facfe;
            position: relative;
        }

        .modal-header {
            margin-bottom: 25px;
        }

        .modal-header h2 {
            font-size: 2.2rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .score-display {
            font-size: 4rem;
            font-weight: bold;
            margin: 20px 0;
            color: #4facfe;
            text-shadow: 0 0 20px rgba(79, 172, 254, 0.5);
        }

        .performance-message {
            font-size: 1.3rem;
            margin: 20px 0;
            color: #d1d1d1;
        }

        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 160px;
        }

        .try-again-btn {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
        }

        .category-btn {
            background: linear-gradient(to right, #667eea, #764ba2);
            color: white;
        }

        .modal-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .confirmation-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 1001;
            display: none;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4facfe;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #d1d1d1;
        }
        
        @media (max-width: 600px) {
            h1 {
                font-size: 2rem;
            }
            
            .categories {
                grid-template-columns: 1fr;
            }
            
            .question-text {
                font-size: 1.2rem;
            }
            
            .back-btn {
                position: relative;
                left: 0;
                top: 0;
                transform: none;
                margin-bottom: 15px;
            }
            
            header {
                padding-top: 70px;
            }

            .modal-content {
                padding: 25px;
                margin: 20px;
            }

            .modal-buttons {
                flex-direction: column;
                align-items: center;
            }

            .modal-btn {
                width: 100%;
                max-width: 250px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <button class="back-btn" onclick="goBack()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <h1><i class="fas fa-laptop-code"></i> IT Knowledge Challenge</h1>
            <div class="header-stats">
                <div class="stat">
                    <i class="fas fa-question-circle"></i>
                    <span id="total-questions">10 Questions</span>
                </div>
                <div class="stat">
                    <i class="fas fa-clock"></i>
                    <span id="timer-value">30s</span>
                </div>
                <div class="stat">
                    <i class="fas fa-star"></i>
                    <span id="score-value">0</span>
                </div>
            </div>
        </header>
        
        <!-- Welcome Screen -->
        <div id="welcome-screen" class="screen active">
            <div class="welcome-content">
                <p>Test your IT knowledge across various domains including programming, cybersecurity, networking, and more!</p>
                
                <div class="categories">
                    <div class="category" data-category="programming">
                        <i class="fas fa-code"></i>
                        <h3>Programming</h3>
                    </div>
                    <div class="category" data-category="security">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Cybersecurity</h3>
                    </div>
                    <div class="category" data-category="network">
                        <i class="fas fa-network-wired"></i>
                        <h3>Networking</h3>
                    </div>
                    <div class="category" data-category="web">
                        <i class="fas fa-globe"></i>
                        <h3>Web Technologies</h3>
                    </div>
                </div>
                
                <button id="start-btn" class="btn">Start Quiz</button>
            </div>
        </div>
        
        <!-- Quiz Screen -->
        <div id="quiz-screen" class="screen">
            <div class="quiz-header">
                <div class="question-progress">Question <span id="current">1</span> of <span id="total">10</span></div>
                <div class="timer">
                    <i class="fas fa-clock"></i>
                    <span class="timer-value" id="timer">30</span>s
                </div>
            </div>
            
            <div class="question-text" id="question">Question will appear here</div>
            
            <div class="answers" id="answers">
                <!-- Answers will be inserted here -->
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button id="next-btn" class="btn">Next Question</button>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div id="results-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-trophy"></i> Quiz Completed!</h2>
                <p>Great job completing the IT Challenge!</p>
            </div>
            
            <div class="score-display" id="modal-score">0/10</div>
            
            <div class="performance-message" id="performance-message">
                Well done! You're an IT expert!
            </div>

            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" id="correct-answers">0</div>
                    <div class="stat-label">Correct Answers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="percentage-score">0%</div>
                    <div class="stat-label">Percentage</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="time-taken">0s</div>
                    <div class="stat-label">Time Taken</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" id="category-name">Category</div>
                    <div class="stat-label">Category</div>
                </div>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn try-again-btn" id="modal-try-again">
                    <i class="fas fa-redo"></i> Try Again
                </button>
                <button class="modal-btn category-btn" id="modal-category">
                    <i class="fas fa-list"></i> Choose Category
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Message -->
    <div id="confirmation-message" class="confirmation-message">
        <i class="fas fa-check-circle"></i> Player record saved successfully!
    </div>

    <script>
        // Quiz questions database
        const quizQuestions = {
            programming: [
                {
                    question: "What does HTML stand for?",
                    answers: ["Hyper Text Markup Language", "High Tech Modern Language", "Hyper Transfer Markup Language", "Hypertext Modern Language"],
                    correct: 0
                },
                {
                    question: "Which programming language is known for its use in data science and machine learning?",
                    answers: ["Java", "Python", "C++", "Ruby"],
                    correct: 1
                },
                {
                    question: "What is the output of 'console.log(2 + '2')' in JavaScript?",
                    answers: ["4", "22", "NaN", "undefined"],
                    correct: 1
                },
                {
                    question: "Which data structure uses LIFO (Last In First Out) principle?",
                    answers: ["Queue", "Stack", "Array", "Linked List"],
                    correct: 1
                },
                {
                    question: "What does API stand for?",
                    answers: ["Application Programming Interface", "Advanced Programming Interface", "Application Process Integration", "Automated Programming Interface"],
                    correct: 0
                },
                {
                    question: "Which of these is not a programming paradigm?",
                    answers: ["Object-Oriented", "Functional", "Procedural", "Compiler-Based"],
                    correct: 3
                },
                {
                    question: "What is the time complexity of binary search?",
                    answers: ["O(1)", "O(n)", "O(log n)", "O(n log n)"],
                    correct: 2
                },
                {
                    question: "Which language is used for developing Android apps?",
                    answers: ["Swift", "Kotlin", "C#", "PHP"],
                    correct: 1
                },
                {
                    question: "What does SQL stand for?",
                    answers: ["Structured Query Language", "Simple Question Language", "Standard Query Logic", "System Query Language"],
                    correct: 0
                },
                {
                    question: "Which symbol is used for comments in Python?",
                    answers: ["//", "#", "/* */", "--"],
                    correct: 1
                }
            ],
            security: [
                {
                    question: "What is phishing?",
                    answers: ["A fishing technique", "A type of cyber attack", "A programming language", "A network protocol"],
                    correct: 1
                },
                {
                    question: "Which of these is the strongest password?",
                    answers: ["password123", "P@ssw0rd!", "12345678", "qwertyuiop"],
                    correct: 1
                },
                {
                    question: "What does VPN stand for?",
                    answers: ["Virtual Private Network", "Virtual Public Network", "Verified Private Network", "Virtual Protocol Network"],
                    correct: 0
                },
                {
                    question: "What is two-factor authentication?",
                    answers: ["Using two passwords", "Verifying identity with two methods", "Having two user accounts", "Using two different browsers"],
                    correct: 1
                },
                {
                    question: "Which of these is a common type of malware?",
                    answers: ["Firewall", "Router", "Ransomware", "Switch"],
                    correct: 2
                }
            ],
            network: [
                {
                    question: "What does LAN stand for?",
                    answers: ["Local Area Network", "Large Area Network", "Local Access Network", "Large Access Network"],
                    correct: 0
                },
                {
                    question: "Which protocol is used for sending email?",
                    answers: ["FTP", "SMTP", "HTTP", "SSH"],
                    correct: 1
                },
                {
                    question: "What is the purpose of a router?",
                    answers: ["To connect multiple networks", "To store data", "To display web pages", "To process calculations"],
                    correct: 0
                },
                {
                    question: "What does DNS stand for?",
                    answers: ["Domain Name System", "Digital Network System", "Domain Network Service", "Digital Name Service"],
                    correct: 0
                },
                {
                    question: "Which IP address is reserved for localhost?",
                    answers: ["192.168.1.1", "127.0.0.1", "10.0.0.1", "172.16.0.1"],
                    correct: 1
                }
            ],
            web: [
                {
                    question: "What does CSS stand for?",
                    answers: ["Computer Style Sheets", "Creative Style System", "Cascading Style Sheets", "Colorful Style Sheets"],
                    correct: 2
                },
                {
                    question: "Which technology is used to make web pages interactive?",
                    answers: ["HTML", "CSS", "JavaScript", "PHP"],
                    correct: 2
                },
                {
                    question: "What is responsive web design?",
                    answers: ["Designing for mobile devices", "Quick response to user requests", "Designing for all screen sizes", "Fast loading websites"],
                    correct: 2
                },
                {
                    question: "Which of these is a CSS framework?",
                    answers: ["Django", "React", "Bootstrap", "Angular"],
                    correct: 2
                },
                {
                    question: "What does HTTP stand for?",
                    answers: ["HyperText Transfer Protocol", "High Tech Transfer Process", "Hyper Transfer Text Protocol", "Hyperlink Text Transfer Protocol"],
                    correct: 0
                }
            ]
        };

        // DOM Elements
        const welcomeScreen = document.getElementById('welcome-screen');
        const quizScreen = document.getElementById('quiz-screen');
        const resultsModal = document.getElementById('results-modal');
        const startBtn = document.getElementById('start-btn');
        const nextBtn = document.getElementById('next-btn');
        const questionElement = document.getElementById('question');
        const answersElement = document.getElementById('answers');
        const currentQuestionElement = document.getElementById('current');
        const totalQuestionsElement = document.getElementById('total');
        const scoreValueElement = document.getElementById('score-value');
        const timerElement = document.getElementById('timer');
        const categoryElements = document.querySelectorAll('.category');

        // Modal elements
        const modalScore = document.getElementById('modal-score');
        const performanceMessage = document.getElementById('performance-message');
        const correctAnswersElement = document.getElementById('correct-answers');
        const percentageScore = document.getElementById('percentage-score');
        const timeTakenElement = document.getElementById('time-taken');
        const categoryNameElement = document.getElementById('category-name');
        const modalTryAgain = document.getElementById('modal-try-again');
        const modalCategory = document.getElementById('modal-category');
        const confirmationMessage = document.getElementById('confirmation-message');

        // Game state variables
        let currentCategory = '';
        let currentQuestions = [];
        let currentQuestionIndex = 0;
        let score = 0;
        let correctAnswers = 0;
        let timer;
        let timeLeft = 30;
        let quizStartTime = Date.now();
        let totalTimeTaken = 0;

        // Event listeners
        startBtn.addEventListener('click', startQuiz);
        nextBtn.addEventListener('click', nextQuestion);
        modalTryAgain.addEventListener('click', restartQuiz);
        modalCategory.addEventListener('click', changeCategory);
        
        categoryElements.forEach(category => {
            category.addEventListener('click', () => {
                currentCategory = category.dataset.category;
                categoryElements.forEach(c => c.style.border = 'none');
                category.style.border = '2px solid #4facfe';
                startBtn.disabled = false;
            });
        });

        // Back button function
        function goBack() {
            if (quizScreen.classList.contains('active') || resultsModal.style.display === 'flex') {
                quizScreen.classList.remove('active');
                resultsModal.style.display = 'none';
                welcomeScreen.classList.add('active');
                resetGameState();
            } else {
                window.location.href = 'game.php';
            }
        }

        // Reset game state
        function resetGameState() {
            clearInterval(timer);
            currentQuestionIndex = 0;
            score = 0;
            correctAnswers = 0;
            scoreValueElement.textContent = score;
            categoryElements.forEach(c => c.style.border = 'none');
            currentCategory = '';
            startBtn.disabled = true;
        }

        // Start the quiz
        function startQuiz() {
            if (!currentCategory) return;
            
            currentQuestions = [...quizQuestions[currentCategory]];
            currentQuestionIndex = 0;
            score = 0;
            correctAnswers = 0;
            quizStartTime = Date.now();
            
            welcomeScreen.classList.remove('active');
            quizScreen.classList.add('active');
            
            totalQuestionsElement.textContent = currentQuestions.length;
            scoreValueElement.textContent = score;
            
            showQuestion();
        }

        // Display current question
        function showQuestion() {
            resetTimer();
            startTimer();
            
            const question = currentQuestions[currentQuestionIndex];
            currentQuestionElement.textContent = currentQuestionIndex + 1;
            
            questionElement.textContent = question.question;
            
            // Clear previous answers
            answersElement.innerHTML = '';
            
            // Create answer buttons
            question.answers.forEach((answer, index) => {
                const answerElement = document.createElement('div');
                answerElement.classList.add('answer');
                answerElement.textContent = answer;
                answerElement.addEventListener('click', () => selectAnswer(index));
                answersElement.appendChild(answerElement);
            });
            
            // Hide next button initially
            nextBtn.style.display = 'none';
        }

        // Handle answer selection
        function selectAnswer(selectedIndex) {
            clearInterval(timer);
            const correctIndex = currentQuestions[currentQuestionIndex].correct;
            const answerElements = document.querySelectorAll('.answer');
            
            // Disable all answers after selection
            answerElements.forEach(element => {
                element.style.pointerEvents = 'none';
            });
            
            // Mark correct and incorrect answers
            answerElements[correctIndex].classList.add('correct');
            if (selectedIndex !== correctIndex) {
                answerElements[selectedIndex].classList.add('incorrect');
            } else {
                score += 10;
                correctAnswers++;
                scoreValueElement.textContent = score;
            }
            
            // Show next button
            nextBtn.style.display = 'inline-block';
        }

        // Move to next question
        function nextQuestion() {
            currentQuestionIndex++;
            if (currentQuestionIndex < currentQuestions.length) {
                showQuestion();
            } else {
                endQuiz();
            }
        }

        // End the quiz and show results modal
        function endQuiz() {
            totalTimeTaken = Math.floor((Date.now() - quizStartTime) / 1000);
            const percentage = (correctAnswers / currentQuestions.length) * 100;
            
            // Update modal content
            modalScore.textContent = `${correctAnswers}/${currentQuestions.length}`;
            correctAnswersElement.textContent = correctAnswers;
            percentageScore.textContent = `${percentage.toFixed(1)}%`;
            timeTakenElement.textContent = `${totalTimeTaken}s`;
            categoryNameElement.textContent = currentCategory.charAt(0).toUpperCase() + currentCategory.slice(1);
            
            // Set performance message
            if (percentage >= 90) {
                performanceMessage.textContent = 'Outstanding! You are an IT expert! 🎉';
            } else if (percentage >= 80) {
                performanceMessage.textContent = 'Excellent! You have impressive IT knowledge! 🌟';
            } else if (percentage >= 70) {
                performanceMessage.textContent = 'Great job! You know your IT stuff! 👍';
            } else if (percentage >= 60) {
                performanceMessage.textContent = 'Good work! Keep learning and improving! 💪';
            } else if (percentage >= 50) {
                performanceMessage.textContent = 'Not bad! Practice makes perfect! 📚';
            } else {
                performanceMessage.textContent = 'Keep studying IT! You\'ll do better next time! 🔍';
            }
            
            // Hide quiz screen and show modal
            quizScreen.classList.remove('active');
            resultsModal.style.display = 'flex';
            
            // Save score to database
            saveScore(correctAnswers, currentQuestions.length, percentage);
        }
        
        function saveScore(score, totalQuestions, percentage) {
            fetch('save_score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'category': currentCategory,
                    'score': score,
                    'total_questions': totalQuestions,
                    'percentage': percentage,
                    'time_taken': totalTimeTaken,
                    'correct_answers': score,
                    'incorrect_answers': totalQuestions - score
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Score saved successfully');
                } else {
                    console.error('Error saving score:', data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Restart the quiz
        function restartQuiz() {
            resultsModal.style.display = 'none';
            quizScreen.classList.add('active');
            currentQuestionIndex = 0;
            score = 0;
            correctAnswers = 0;
            quizStartTime = Date.now();
            scoreValueElement.textContent = score;
            showQuestion();
        }

        // Change category
        function changeCategory() {
            showConfirmation();
            setTimeout(() => {
                resultsModal.style.display = 'none';
                welcomeScreen.classList.add('active');
                resetGameState();
            }, 1500);
        }

        function showConfirmation() {
            confirmationMessage.style.display = 'block';
            setTimeout(() => {
                confirmationMessage.style.display = 'none';
            }, 3000);
        }

        // Timer functions
        function startTimer() {
            timeLeft = 30;
            timerElement.textContent = timeLeft;
            
            timer = setInterval(() => {
                timeLeft--;
                timerElement.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    // Automatically move to next question when time runs out
                    const correctIndex = currentQuestions[currentQuestionIndex].correct;
                    selectAnswer(-1); // -1 means no answer selected (timeout)
                }
            }, 1000);
        }

        function resetTimer() {
            clearInterval(timer);
            timerElement.textContent = '30';
        }
    </script>
</body>
</html>
