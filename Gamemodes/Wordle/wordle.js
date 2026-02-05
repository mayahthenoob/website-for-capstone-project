'use strict';

const BACKSPACE_KEY = 'Backspace';
const ENTER_KEY = 'Enter';

const WORD_LIST = [
  'HELLO', 'PASTA', 'PANIC', 'SKILL', 'ARROW', 'BIRDS',
  'FRUIT', 'PIZZA', 'SHAKE', 'THOSE', 'SUPER', 'SHARE',
  'LOVES', 'WATER', 'VALUE', 'VALID', 'SWEET', 'START',
  'SPACE', 'SLEEP', 'MAGIC', 'LEARN', 'LIGHT', 'LOGIC',
  'HAPPY', 'HUMAN', 'GRAND', 'FOCUS', 'ENJOY', 'DREAM',
  'CLEAN', 'CLEAR', 'WORDS',
];

const WORD_OF_THE_DAY = WORD_LIST[getRandomIndex(WORD_LIST.length)];
const MAX_NUMBER_OF_ATTEMPTS = 6;

const history = [];
let currentWord = '';
let gameOver = false; // 🔒 NEW

const init = () => {
  const KEYBOARD_KEYS = ['QWERTYUIOP', 'ASDFGHJKL', 'ZXCVBNM'];

  const gameBoard = document.querySelector('#board');
  const keyboard = document.querySelector('#keyboard');

  generateBoard(gameBoard);
  generateBoard(keyboard, 3, 10, KEYBOARD_KEYS, true);

  document.addEventListener('keydown', e => onKeyDown(e.key));
  gameBoard.addEventListener('animationend', e =>
    e.target.setAttribute('data-animation', 'idle')
  );
  keyboard.addEventListener('click', onKeyboardButtonClick);
};

const showMessage = (message) => {
  const toast = document.createElement('li');
  toast.textContent = message;
  toast.className = 'toast';

  document.querySelector('.toaster ul').prepend(toast);
  setTimeout(() => toast.classList.add('fade'), 1000);
  toast.addEventListener('transitionend', e => e.target.remove());
};

const checkGuess = (guess, word) => {
  const guessLetters = guess.split('');
  const wordLetters = word.split('');
  const currentRow = document.querySelector(
    `#board ul[data-row='${history.length}']`
  );

  [...currentRow.children].forEach((cell, i) => {
    cell.setAttribute('data-status', 'none');
    cell.setAttribute('data-animation', 'flip');
    cell.style.animationDelay = `${i * 300}ms`;
  });

  const letterCount = {};
  wordLetters.forEach(l => letterCount[l] = (letterCount[l] || 0) + 1);

  // 🟩 Green pass
  guessLetters.forEach((letter, i) => {
    if (letter === wordLetters[i]) {
      currentRow.children[i].setAttribute('data-status', 'valid');
      document.querySelector(`[data-key='${letter}']`)
        .setAttribute('data-status', 'valid');
      letterCount[letter]--;
      guessLetters[i] = null;
    }
  });

  // 🟨⬛ Yellow / Gray pass
  guessLetters.forEach((letter, i) => {
    if (!letter) return;

    const cell = currentRow.children[i];
    const key = document.querySelector(`[data-key='${letter}']`);

    if (letterCount[letter] > 0) {
      cell.setAttribute('data-status', 'invalid');
      if (key.getAttribute('data-status') !== 'valid') {
        key.setAttribute('data-status', 'invalid');
      }
      letterCount[letter]--;
    } else {
      cell.setAttribute('data-status', 'none');
      if (!key.getAttribute('data-status')) {
        key.setAttribute('data-status', 'none');
      }
    }
  });

  history.push(guess);
  currentWord = '';

  // 🏁 WIN
  if (guess === word) {
    showMessage('🎉 Genius!');
    gameOver = true;
    return;
  }

  // 💀 LOSS
  if (history.length === MAX_NUMBER_OF_ATTEMPTS) {
    showMessage(`The word was ${word}`);
    gameOver = true;
  }
};

const onKeyboardButtonClick = (e) => {
  if (gameOver) return; // 🔒 LOCK INPUT
  if (e.target.nodeName === 'LI') {
    onKeyDown(e.target.getAttribute('data-key'));
  }
};

const onKeyDown = (key) => {
  if (gameOver) return; // 🔒 LOCK INPUT

  const currentRow = document.querySelector(
    `#board ul[data-row='${history.length}']`
  );

  let targetColumn = currentRow.querySelector('[data-status="empty"]');

  if (key === BACKSPACE_KEY) {
    targetColumn ??= currentRow.lastChild;
    targetColumn = targetColumn.previousElementSibling ?? targetColumn;
    targetColumn.textContent = '';
    targetColumn.setAttribute('data-status', 'empty');
    currentWord = currentWord.slice(0, -1);
    return;
  }

  if (key === ENTER_KEY) {
    if (currentWord.length !== 5) {
      showMessage('Not enough letters');
      return;
    }
    checkGuess(currentWord, WORD_OF_THE_DAY);
    return;
  }

  if (currentWord.length >= 5) return;

  const letter = key.toUpperCase();
  if (/^[A-Z]$/.test(letter)) {
    currentWord += letter;
    targetColumn.textContent = letter;
    targetColumn.setAttribute('data-status', 'filled');
    targetColumn.setAttribute('data-animation', 'pop');
  }
};

const generateBoard = (board, rows = 6, cols = 5, keys = [], keyboard = false) => {
  for (let r = 0; r < rows; r++) {
    const row = document.createElement('ul');
    row.setAttribute('data-row', r);

    for (let c = 0; c < cols; c++) {
      const cell = document.createElement('li');
      cell.setAttribute('data-status', 'empty');
      cell.setAttribute('data-animation', 'idle');

      if (keyboard && keys[r]?.[c]) {
        cell.textContent = keys[r][c];
        cell.setAttribute('data-key', keys[r][c]);
      }

      if (!(keyboard && cell.textContent === '')) {
        row.appendChild(cell);
      }
    }
    board.appendChild(row);
  }

  if (keyboard) {
    const enter = document.createElement('li');
    enter.textContent = ENTER_KEY;
    enter.setAttribute('data-key', ENTER_KEY);
    board.lastChild.prepend(enter);

    const back = document.createElement('li');
    back.textContent = BACKSPACE_KEY;
    back.setAttribute('data-key', BACKSPACE_KEY);
    board.lastChild.append(back);
  }
};

document.addEventListener('DOMContentLoaded', init);

function getRandomIndex(max) {
  return Math.floor(Math.random() * max);
}
