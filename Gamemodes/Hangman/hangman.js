document.addEventListener('DOMContentLoaded', () => {
  const wordDisplay = document.getElementById('word-display');
  const keyboard = document.getElementById('keyboard');
  const remainingGuessesEl = document.getElementById('remaining-guesses');
  const gameMessageEl = document.getElementById('game-message');
  const resetBtn = document.getElementById('reset-btn');

  const wordInput = document.getElementById('word-input');
  const guessWordBtn = document.getElementById('guess-word-btn');

  const hangmanParts = {
    head: document.getElementById('head'),
    body: document.getElementById('body'),
    leftArm: document.getElementById('left-arm'),
    rightArm: document.getElementById('right-arm'),
    leftLeg: document.getElementById('left-leg'),
    rightLeg: document.getElementById('right-leg'),
    face: document.getElementById('face')
  };

  let selectedWord = '';
  let correctLetters = [];
  let wrongLetters = [];
  let remainingGuesses = 6;
  let gameOver = false;

  const wordCategories = {
    animals: ['ELEPHANT', 'GIRAFFE', 'KANGAROO', 'DOLPHIN', 'CHEETAH'],
    countries: ['CANADA', 'BRAZIL', 'JAPAN', 'GERMANY', 'AUSTRALIA'],
    fruits: ['BANANA', 'WATERMELON', 'PINEAPPLE', 'STRAWBERRY', 'BLUEBERRY']
  };

  function initGame() {
    correctLetters = [];
    wrongLetters = [];
    remainingGuesses = 6;
    gameOver = false;
    gameMessageEl.textContent = '';
    wordInput.value = '';

    const categories = Object.keys(wordCategories);
    const randomCategory = categories[Math.floor(Math.random() * categories.length)];
    const words = wordCategories[randomCategory];
    selectedWord = words[Math.floor(Math.random() * words.length)];

    document.getElementById('category').textContent =
      `Category: ${randomCategory.charAt(0).toUpperCase() + randomCategory.slice(1)}`;

    remainingGuessesEl.textContent = `Remaining guesses: ${remainingGuesses}`;

    Object.values(hangmanParts).forEach(part => part.style.display = 'none');

    wordDisplay.innerHTML = '';
    selectedWord.split('').forEach(letter => {
      const letterEl = document.createElement('div');
      letterEl.classList.add('word-letter');
      letterEl.dataset.letter = letter;
      wordDisplay.appendChild(letterEl);
    });

    keyboard.innerHTML = '';
    for (let i = 65; i <= 90; i++) {
      const letter = String.fromCharCode(i);
      const keyEl = document.createElement('button');
      keyEl.classList.add('keyboard-letter');
      keyEl.textContent = letter;
      keyEl.addEventListener('click', () => handleGuess(letter));
      keyboard.appendChild(keyEl);
    }
  }

  function handleGuess(letter) {
    if (gameOver || correctLetters.includes(letter) || wrongLetters.includes(letter)) return;

    const key = document.querySelector(`.keyboard-letter:nth-child(${letter.charCodeAt(0) - 64})`);

    if (selectedWord.includes(letter)) {
      correctLetters.push(letter);
      updateWordDisplay();
      key.classList.add('correct', 'used');

      if (checkWin()) {
        gameMessageEl.textContent = '🎉 Congratulations! You won!';
        gameMessageEl.style.color = 'green';
        gameOver = true;
      }
    } else {
      wrongLetters.push(letter);
      remainingGuesses--;
      remainingGuessesEl.textContent = `Remaining guesses: ${remainingGuesses}`;
      key.classList.add('wrong', 'used');
      updateHangmanDrawing();

      if (remainingGuesses === 0) {
        endGame(false);
      }
    }
  }

  function updateHangmanDrawing() {
    const parts = Object.values(hangmanParts);
    if (wrongLetters.length <= parts.length) {
      parts[wrongLetters.length - 1].style.display = 'block';
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
    return selectedWord.split('').every(letter => correctLetters.includes(letter));
  }

  function endGame(win) {
    gameOver = true;
    Object.values(hangmanParts).forEach(part => part.style.display = 'block');

    document.querySelectorAll('.word-letter').forEach(el => {
      el.textContent = el.dataset.letter;
    });

    gameMessageEl.textContent = win
      ? '🎉 You guessed the word!'
      : `❌ Game Over! The word was: ${selectedWord}`;
    gameMessageEl.style.color = win ? 'green' : 'red';
  }

  // 🔥 NEW: Guess full word
  function guessWholeWord() {
    if (gameOver) return;

    const guess = wordInput.value.trim().toUpperCase();
    if (!guess) return;

    if (guess === selectedWord) {
      selectedWord.split('').forEach(l => {
        if (!correctLetters.includes(l)) correctLetters.push(l);
      });
      updateWordDisplay();
      endGame(true);
    } else {
      remainingGuesses = 0;
      remainingGuessesEl.textContent = 'Remaining guesses: 0';
      endGame(false);
    }

    wordInput.value = '';
  }

  guessWordBtn.addEventListener('click', guessWholeWord);
  wordInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') guessWholeWord();
  });

  document.addEventListener('keydown', e => {
    if (/^[a-z]$/i.test(e.key)) handleGuess(e.key.toUpperCase());
  });

  resetBtn.addEventListener('click', initGame);
  initGame();
});
