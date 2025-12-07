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

// General Knowledge Quiz Questions
$categories = [
    'history' => [
        'name' => 'World History',
        'questions' => [
            [
                'question' => 'Who was the first emperor of Rome?',
                'options' => ['Julius Caesar', 'Augustus', 'Nero', 'Constantine'],
                'answer' => 1
            ],
            [
                'question' => 'In which year did World War II end?',
                'options' => ['1944', '1945', '1946', '1947'],
                'answer' => 1
            ],
            [
                'question' => 'The ancient civilization of Mesopotamia was located in which modern-day country?',
                'options' => ['Egypt', 'Greece', 'Iraq', 'Turkey'],
                'answer' => 2
            ],
            [
                'question' => 'Who discovered America in 1492?',
                'options' => ['Vasco da Gama', 'Christopher Columbus', 'Ferdinand Magellan', 'James Cook'],
                'answer' => 1
            ],
            [
                'question' => 'The French Revolution began in which year?',
                'options' => ['1776', '1789', '1799', '1812'],
                'answer' => 1
            ],
            [
                'question' => 'Who was the first female Prime Minister of the United Kingdom?',
                'options' => ['Theresa May', 'Margaret Thatcher', 'Angela Merkel', 'Indira Gandhi'],
                'answer' => 1
            ],
            [
                'question' => 'The Great Wall of China was primarily built to protect against which group?',
                'options' => ['Mongols', 'Japanese', 'Koreans', 'Vietnamese'],
                'answer' => 0
            ],
            [
                'question' => 'Which empire was ruled by Genghis Khan?',
                'options' => ['Ottoman Empire', 'Roman Empire', 'Mongol Empire', 'British Empire'],
                'answer' => 2
            ],
            [
                'question' => 'The Renaissance began in which country?',
                'options' => ['France', 'England', 'Italy', 'Germany'],
                'answer' => 2
            ],
            [
                'question' => 'Who wrote the "I Have a Dream" speech?',
                'options' => ['Malcolm X', 'Martin Luther King Jr.', 'Rosa Parks', 'Barack Obama'],
                'answer' => 1
            ]
        ]
    ],
    'science' => [
        'name' => 'Science & Nature',
        'questions' => [
            [
                'question' => 'What is the chemical symbol for gold?',
                'options' => ['Go', 'Gd', 'Au', 'Ag'],
                'answer' => 2
            ],
            [
                'question' => 'How many bones are in the human body?',
                'options' => ['196', '206', '216', '226'],
                'answer' => 1
            ],
            [
                'question' => 'Which planet is known as the Red Planet?',
                'options' => ['Venus', 'Mars', 'Jupiter', 'Saturn'],
                'answer' => 1
            ],
            [
                'question' => 'What is the hardest natural substance on Earth?',
                'options' => ['Gold', 'Iron', 'Diamond', 'Platinum'],
                'answer' => 2
            ],
            [
                'question' => 'Which gas do plants absorb from the atmosphere?',
                'options' => ['Oxygen', 'Nitrogen', 'Carbon Dioxide', 'Hydrogen'],
                'answer' => 2
            ],
            [
                'question' => 'What is the speed of light in vacuum?',
                'options' => ['299,792 km/s', '300,000 km/s', '250,000 km/s', '350,000 km/s'],
                'answer' => 0
            ],
            [
                'question' => 'Which element has the atomic number 1?',
                'options' => ['Helium', 'Oxygen', 'Hydrogen', 'Carbon'],
                'answer' => 2
            ],
            [
                'question' => 'What is the largest organ in the human body?',
                'options' => ['Liver', 'Brain', 'Skin', 'Heart'],
                'answer' => 2
            ],
            [
                'question' => 'Which scientist developed the theory of relativity?',
                'options' => ['Isaac Newton', 'Albert Einstein', 'Stephen Hawking', 'Galileo Galilei'],
                'answer' => 1
            ],
            [
                'question' => 'What is the main gas found in Earth\'s atmosphere?',
                'options' => ['Oxygen', 'Carbon Dioxide', 'Nitrogen', 'Hydrogen'],
                'answer' => 2
            ]
        ]
    ],
    'geography' => [
        'name' => 'Geography',
        'questions' => [
            [
                'question' => 'What is the largest ocean on Earth?',
                'options' => ['Atlantic Ocean', 'Indian Ocean', 'Arctic Ocean', 'Pacific Ocean'],
                'answer' => 3
            ],
            [
                'question' => 'Which country has the longest coastline in the world?',
                'options' => ['Russia', 'Canada', 'Australia', 'United States'],
                'answer' => 1
            ],
            [
                'question' => 'What is the capital of Australia?',
                'options' => ['Sydney', 'Melbourne', 'Canberra', 'Perth'],
                'answer' => 2
            ],
            [
                'question' => 'Which desert is the largest in the world?',
                'options' => ['Sahara Desert', 'Arabian Desert', 'Gobi Desert', 'Antarctic Desert'],
                'answer' => 3
            ],
            [
                'question' => 'Mount Everest is located in which mountain range?',
                'options' => ['Andes', 'Rockies', 'Himalayas', 'Alps'],
                'answer' => 2
            ],
            [
                'question' => 'Which river is the longest in the world?',
                'options' => ['Amazon River', 'Nile River', 'Yangtze River', 'Mississippi River'],
                'answer' => 1
            ],
            [
                'question' => 'What is the smallest country in the world?',
                'options' => ['Monaco', 'Vatican City', 'San Marino', 'Liechtenstein'],
                'answer' => 1
            ],
            [
                'question' => 'Which continent is the most populous?',
                'options' => ['Africa', 'Europe', 'Asia', 'North America'],
                'answer' => 2
            ],
            [
                'question' => 'The Panama Canal connects which two oceans?',
                'options' => ['Atlantic and Indian', 'Pacific and Indian', 'Atlantic and Pacific', 'Arctic and Pacific'],
                'answer' => 2
            ],
            [
                'question' => 'Which country is known as the Land of the Rising Sun?',
                'options' => ['China', 'South Korea', 'Japan', 'Thailand'],
                'answer' => 2
            ]
        ]
    ],
    'arts' => [
        'name' => 'Arts & Literature',
        'questions' => [
            [
                'question' => 'Who painted the Mona Lisa?',
                'options' => ['Vincent van Gogh', 'Pablo Picasso', 'Leonardo da Vinci', 'Michelangelo'],
                'answer' => 2
            ],
            [
                'question' => 'Which Shakespeare play features the character Hamlet?',
                'options' => ['Macbeth', 'Romeo and Juliet', 'Hamlet', 'Othello'],
                'answer' => 2
            ],
            [
                'question' => 'Who wrote "1984"?',
                'options' => ['Aldous Huxley', 'George Orwell', 'Ray Bradbury', 'H.G. Wells'],
                'answer' => 1
            ],
            [
                'question' => 'Which composer is known for his "Moonlight Sonata"?',
                'options' => ['Mozart', 'Beethoven', 'Bach', 'Chopin'],
                'answer' => 1
            ],
            [
                'question' => 'In which museum can you find the statue of David by Michelangelo?',
                'options' => ['Louvre Museum', 'Uffizi Gallery', 'British Museum', 'Metropolitan Museum of Art'],
                'answer' => 1
            ],
            [
                'question' => 'Who wrote "Pride and Prejudice"?',
                'options' => ['Charlotte Bronte', 'Jane Austen', 'Emily Bronte', 'Charles Dickens'],
                'answer' => 1
            ],
            [
                'question' => 'Which artist is famous for his Campbell\'s Soup Cans painting?',
                'options' => ['Andy Warhol', 'Jackson Pollock', 'Salvador Dali', 'Pablo Picasso'],
                'answer' => 0
            ],
            [
                'question' => 'What is the name of the wizarding school in Harry Potter?',
                'options' => ['Hogwarts', 'Beauxbatons', 'Durmstrang', 'Ilvermorny'],
                'answer' => 0
            ],
            [
                'question' => 'Who wrote "The Great Gatsby"?',
                'options' => ['Ernest Hemingway', 'F. Scott Fitzgerald', 'John Steinbeck', 'Mark Twain'],
                'answer' => 1
            ],
            [
                'question' => 'Which instrument has 88 keys?',
                'options' => ['Violin', 'Guitar', 'Piano', 'Harp'],
                'answer' => 2
            ]
        ]
    ],
    'sports' => [
        'name' => 'Sports',
        'questions' => [
            [
                'question' => 'How many players are on a soccer team during a match?',
                'options' => ['9', '10', '11', '12'],
                'answer' => 2
            ],
            [
                'question' => 'Which country won the first FIFA World Cup in 1930?',
                'options' => ['Brazil', 'Uruguay', 'Argentina', 'Italy'],
                'answer' => 1
            ],
            [
                'question' => 'In tennis, what term is used for a score of zero?',
                'options' => ['Nil', 'Zero', 'Love', 'Null'],
                'answer' => 2
            ],
            [
                'question' => 'Which athlete has won the most Olympic gold medals?',
                'options' => ['Usain Bolt', 'Michael Phelps', 'Carl Lewis', 'Larisa Latynina'],
                'answer' => 1
            ],
            [
                'question' => 'What is the diameter of a basketball hoop in inches?',
                'options' => ['16 inches', '18 inches', '20 inches', '24 inches'],
                'answer' => 1
            ],
            [
                'question' => 'Which country invented the sport of golf?',
                'options' => ['United States', 'England', 'Scotland', 'Ireland'],
                'answer' => 2
            ],
            [
                'question' => 'How many rings are on the Olympic flag?',
                'options' => ['4', '5', '6', '7'],
                'answer' => 1
            ],
            [
                'question' => 'In baseball, how many strikes make an out?',
                'options' => ['2', '3', '4', '5'],
                'answer' => 1
            ],
            [
                'question' => 'Which sport uses the term "checkmate"?',
                'options' => ['Chess', 'Boxing', 'Hockey', 'Wrestling'],
                'answer' => 0
            ],
            [
                'question' => 'What is the maximum break in snooker?',
                'options' => ['100 points', '147 points', '155 points', '200 points'],
                'answer' => 1
            ]
        ]
    ]
];

// Handle category selection
$selected_category = null;
if (isset($_GET['category']) && array_key_exists($_GET['category'], $categories)) {
    $selected_category = $_GET['category'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Knowledge Quiz - QuizMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a2a3a, #2d3e50);
            min-height: 100vh;
            color: #fff;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        header {
            background: rgba(0, 0, 0, 0.4);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .header-content {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .back-btn {
            position: absolute;
            left: 0;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 50px;
            padding: 10px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .welcome {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #d1d1d1;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .category-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.12);
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #4facfe;
        }
        
        .category-title {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #4facfe;
        }
        
        .category-info {
            color: #d1d1d1;
            margin-bottom: 15px;
        }
        
        .quiz-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .question-counter {
            font-size: 1.1rem;
            margin-bottom: 20px;
            color: #4facfe;
        }
        
        .question {
            font-size: 1.4rem;
            margin-bottom: 25px;
            line-height: 1.5;
        }
        
        .options-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .option {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 15px;
            text-align: left;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: #4facfe;
        }
        
        .option.selected {
            background: rgba(79, 172, 254, 0.2);
            border-color: #4facfe;
        }
        
        .btn {
            background: linear-gradient(to right, #4facfe, #00f2fe);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 25px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-secondary {
            background: linear-gradient(to right, #667eea, #764ba2);
        }
        
        .logout-btn {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            margin-top: 20px;
        }
        
        .result-container {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .score {
            font-size: 3rem;
            margin: 20px 0;
            color: #4facfe;
        }
        
        .timer {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #ff6b6b;
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
            background: linear-gradient(135deg, #1a2a3a, #2d3e50);
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
        
        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .back-btn {
                position: relative;
                margin-bottom: 15px;
                left: auto;
            }
            
            .header-content {
                flex-direction: column;
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
    <div class="header-content">
        <a href="game.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div>
            <h1><i class="fas fa-globe-americas"></i> Echoes of Memories</h1>
            <div class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
            <p>Test your knowledge across various topics</p>
        </div>
    </div>
</header>
        
        <?php if (!$selected_category): ?>
        <div class="categories-grid">
            <?php foreach ($categories as $key => $category): ?>
            <div class="category-card" onclick="location.href='?category=<?php echo $key; ?>'">
                <i class="fas 
                    <?php 
                    switch($key) {
                        case 'history': echo 'fa-monument'; break;
                        case 'science': echo 'fa-flask'; break;
                        case 'geography': echo 'fa-globe-americas'; break;
                        case 'arts': echo 'fa-palette'; break;
                        case 'sports': echo 'fa-trophy'; break;
                        default: echo 'fa-question';
                    }
                    ?> 
                category-icon"></i>
                <h2 class="category-title"><?php echo $category['name']; ?></h2>
                <p class="category-info">10 challenging questions</p>
                <p class="category-info">30 seconds per question</p>
                <button class="btn">Start Quiz</button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="quiz-container" id="quiz-container">
            <div class="question-counter" id="question-counter">Question 1 of 10</div>
            <div class="timer" id="timer">Time: 30s</div>
            <div class="question" id="question"></div>
            <div class="options-container" id="options-container"></div>
            <button class="btn" id="next-btn" style="display: none;">Next Question</button>
            <button class="btn btn-secondary" onclick="location.href='game.php'">Back to Categories</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Results Modal -->
    <div id="results-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-trophy"></i> Quiz Completed!</h2>
                <p>Great job completing the quiz!</p>
            </div>
            
            <div class="score-display" id="modal-score">0/10</div>
            
            <div class="performance-message" id="performance-message">
                Well done! You're a quiz master!
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

    <?php if ($selected_category): ?>
    <script>
        const categories = <?php echo json_encode($categories); ?>;
        const selectedCategory = '<?php echo $selected_category; ?>';
        const questions = categories[selectedCategory].questions;
        
        let currentQuestion = 0;
        let score = 0;
        let timeLeft = 30;
        let timer;
        let quizStartTime = Date.now();
        let totalTimeTaken = 0;
        
        const questionCounter = document.getElementById('question-counter');
        const timerElement = document.getElementById('timer');
        const questionElement = document.getElementById('question');
        const optionsContainer = document.getElementById('options-container');
        const nextBtn = document.getElementById('next-btn');
        const quizContainer = document.getElementById('quiz-container');
        
        // Modal elements
        const resultsModal = document.getElementById('results-modal');
        const modalScore = document.getElementById('modal-score');
        const performanceMessage = document.getElementById('performance-message');
        const correctAnswersElement = document.getElementById('correct-answers');
        const percentageScore = document.getElementById('percentage-score');
        const timeTakenElement = document.getElementById('time-taken');
        const categoryNameElement = document.getElementById('category-name');
        const modalTryAgain = document.getElementById('modal-try-again');
        const modalCategory = document.getElementById('modal-category');
        const confirmationMessage = document.getElementById('confirmation-message');

        function loadQuestion() {
            clearInterval(timer);
            timeLeft = 30;
            timerElement.textContent = `Time: ${timeLeft}s`;
            
            questionCounter.textContent = `Question ${currentQuestion + 1} of ${questions.length}`;
            questionElement.textContent = questions[currentQuestion].question;
            
            optionsContainer.innerHTML = '';
            questions[currentQuestion].options.forEach((option, index) => {
                const optionElement = document.createElement('div');
                optionElement.className = 'option';
                optionElement.textContent = option;
                optionElement.addEventListener('click', () => selectOption(index));
                optionsContainer.appendChild(optionElement);
            });
            
            nextBtn.style.display = 'none';
            startTimer();
        }
        
        function selectOption(selectedIndex) {
            clearInterval(timer);
            const options = document.querySelectorAll('.option');
            const correctAnswer = questions[currentQuestion].answer;
            
            options.forEach((option, index) => {
                option.style.pointerEvents = 'none';
                if (index === correctAnswer) {
                    option.style.background = 'rgba(76, 175, 80, 0.3)';
                    option.style.borderColor = '#4caf50';
                } else if (index === selectedIndex && index !== correctAnswer) {
                    option.style.background = 'rgba(244, 67, 54, 0.3)';
                    option.style.borderColor = '#f44336';
                }
            });
            
            if (selectedIndex === correctAnswer) {
                score++;
            }
            
            nextBtn.style.display = 'inline-block';
        }
        
        function startTimer() {
            timer = setInterval(() => {
                timeLeft--;
                timerElement.textContent = `Time: ${timeLeft}s`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    selectOption(-1); // No option selected - time's up
                }
            }, 1000);
        }
        
        function showResults() {
            totalTimeTaken = Math.floor((Date.now() - quizStartTime) / 1000);
            const percentage = (score / questions.length) * 100;
            
            // Update modal content
            modalScore.textContent = `${score}/${questions.length}`;
            correctAnswersElement.textContent = score;
            percentageScore.textContent = `${percentage.toFixed(1)}%`;
            timeTakenElement.textContent = `${totalTimeTaken}s`;
            categoryNameElement.textContent = categories[selectedCategory].name;
            
            // Set performance message
            if (percentage >= 90) {
                performanceMessage.textContent = 'Outstanding! You are a true knowledge expert! 🎉';
            } else if (percentage >= 80) {
                performanceMessage.textContent = 'Excellent! You have impressive knowledge! 🌟';
            } else if (percentage >= 70) {
                performanceMessage.textContent = 'Great job! You know your stuff! 👍';
            } else if (percentage >= 60) {
                performanceMessage.textContent = 'Good work! Keep learning and improving! 💪';
            } else if (percentage >= 50) {
                performanceMessage.textContent = 'Not bad! Practice makes perfect! 📚';
            } else {
                performanceMessage.textContent = 'Keep studying! You\'ll do better next time! 🔍';
            }
            
            // Show modal
            resultsModal.style.display = 'flex';
            
            // Save score to database
            saveScore(score, questions.length, percentage);
        }
        
        function saveScore(score, totalQuestions, percentage) {
            fetch('save_score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'category': selectedCategory,
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

        // Modal button event listeners
        modalTryAgain.addEventListener('click', function() {
            // Reset quiz and restart
            currentQuestion = 0;
            score = 0;
            quizStartTime = Date.now();
            resultsModal.style.display = 'none';
            loadQuestion();
        });

        modalCategory.addEventListener('click', function() {
            // Show confirmation and redirect
            showConfirmation();
            setTimeout(() => {
                window.location.href = 'game1.php';
            }, 1500);
        });

        function showConfirmation() {
            confirmationMessage.style.display = 'block';
            setTimeout(() => {
                confirmationMessage.style.display = 'none';
            }, 3000);
        }
        
        nextBtn.addEventListener('click', () => {
            currentQuestion++;
            if (currentQuestion < questions.length) {
                loadQuestion();
            } else {
                showResults();
            }
        });
        
        // Start the quiz
        loadQuestion();
    </script>
    <?php endif; ?>
</body>
</html>
