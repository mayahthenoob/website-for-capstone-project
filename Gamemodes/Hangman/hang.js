document.addEventListener('DOMContentLoaded', () => {

    const wordDisplay = document.getElementById('word-display');
    const keyboard = document.getElementById('keyboard');
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

        const categories = Object.keys(wordCategories);
        const randomCategory = categories[Math.floor(Math.random() * categories.length)];
        selectedWord = wordCategories[randomCategory][Math.floor(Math.random() * wordCategories[randomCategory].length)];

        categoryEl.textContent = randomCategory;

        hangmanParts.forEach(part => part.style.display = 'none');

        wordDisplay.innerHTML = '';
        selectedWord.split('').forEach(letter => {
            const letterEl = document.createElement('div');
            letterEl.classList.add('word-letter');
            letterEl.dataset.letter = letter;
            wordDisplay.appendChild(letterEl);
        });

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
            if (checkWin()) endGame();
        } else {
            wrongLetters.push(letter);
            key.classList.add('wrong', 'used');
            updateHangmanDrawing();
            if (wrongLetters.length === hangmanParts.length) endGame();
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

    function endGame() {
        gameOver = true;

        document.querySelectorAll('.word-letter').forEach(el => {
            el.textContent = el.dataset.letter;
        });

        setTimeout(initGame, 2000);
    }

    document.addEventListener('keydown', e => {
        const char = e.key.toUpperCase();
        if (/^[A-Z]$/.test(char)) handleGuess(char);
    });

    initGame();
});