document.addEventListener('DOMContentLoaded', () => {
    const wordDisplay = document.getElementById('word-display');
    const keyboard = document.getElementById('keyboard');
    const gameMessageEl = document.getElementById('game-message');
    const resetBtn = document.getElementById('reset-btn');
    const categoryEl = document.getElementById('category');

    const hangmanParts = [
        document.getElementById('head'),
        document.getElementById('body'),
        document.getElementById('left-arm'),
        document.getElementById('right-arm'),
        document.getElementById('left-leg'),
        document.getElementById('right-leg')
    ];

    let selectedWord = '';
    let correctLetters = [];
    let wrongLetters = [];
    let gameOver = false;

    const wordCategories = {
        animals: ['ELEPHANT', 'GIRAFFE', 'KANGAROO', 'DOLPHIN', 'CHEETAH', 'PENGUIN'],
        countries: ['CANADA', 'BRAZIL', 'JAPAN', 'GERMANY', 'AUSTRALIA'],
        fruits: ['BANANA', 'WATERMELON', 'PINEAPPLE', 'STRAWBERRY']
    };

    function initGame() {
        correctLetters = [];
        wrongLetters = [];
        gameOver = false;
        gameMessageEl.textContent = '';
        
        const categories = Object.keys(wordCategories);
        const randomCategory = categories[Math.floor(Math.random() * categories.length)];
        selectedWord = wordCategories[randomCategory][Math.floor(Math.random() * wordCategories[randomCategory].length)];

        categoryEl.textContent = `(Nouns) ${randomCategory}`;

        // Reset Drawing
        hangmanParts.forEach(part => part.style.display = 'none');

        // Create Word Slots
        wordDisplay.innerHTML = '';
        selectedWord.split('').forEach(letter => {
            const letterEl = document.createElement('div');
            letterEl.classList.add('word-letter');
            letterEl.dataset.letter = letter;
            wordDisplay.appendChild(letterEl);
        });

        // Create Keyboard
        keyboard.innerHTML = '';
        "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split('').forEach(letter => {
            const keyEl = document.createElement('button');
            keyEl.classList.add('keyboard-letter');
            keyEl.textContent = letter;
            keyEl.addEventListener('click', () => handleGuess(letter));
            keyboard.appendChild(keyEl);
        });
    }

    function handleGuess(letter) {
        if (gameOver || correctLetters.includes(letter) || wrongLetters.includes(letter)) return;

        const keys = document.querySelectorAll('.keyboard-letter');
        const key = Array.from(keys).find(k => k.textContent === letter);

        if (selectedWord.includes(letter)) {
            correctLetters.push(letter);
            updateWordDisplay();
            key.classList.add('correct', 'used');
            if (checkWin()) endGame(true);
        } else {
            wrongLetters.push(letter);
            key.classList.add('wrong', 'used');
            updateHangmanDrawing();
            if (wrongLetters.length === hangmanParts.length) endGame(false);
        }
    }

    function updateHangmanDrawing() {
        const index = wrongLetters.length - 1;
        if (hangmanParts[index]) {
            hangmanParts[index].style.display = 'block';
        }
    }

    function updateWordDisplay() {
        document.querySelectorAll('.word-letter').forEach(el => {
            if (correctLetters.includes(el.dataset.letter)) {
                el.textContent = el.dataset.letter;
            }
        });
    }

    function checkWin() {
        return selectedWord.split('').every(l => correctLetters.includes(l));
    }

    function endGame(win) {
        gameOver = true;
        gameMessageEl.textContent = win ? 'Winner!' : `The word was: ${selectedWord}`;
        gameMessageEl.style.color = win ? '#2ecc71' : '#e74c3c';
        
        // Reveal missing letters
        document.querySelectorAll('.word-letter').forEach(el => {
            el.textContent = el.dataset.letter;
        });
    }

    resetBtn.addEventListener('click', initGame);
    document.addEventListener('keydown', e => {
        const char = e.key.toUpperCase();
        if (/^[A-Z]$/.test(char)) handleGuess(char);
    });

    initGame();
});