const display = document.getElementById('display');
const calculator = document.querySelector('.calculator');

let currentInput = '0';
const MAX_DISPLAY_LENGTH = 14;

/* ---------------- DISPLAY ---------------- */
function updateDisplay(){
    display.textContent = currentInput;

    if(currentInput.length > MAX_DISPLAY_LENGTH){
        display.style.fontSize = '2rem';
    }
    else if(currentInput.length > 6){
        display.style.fontSize = '2.5rem';
    }
    else{
        display.style.fontSize = '3.5rem';
    }
}

/* ---------------- CALCULATOR LOGIC ---------------- */
function appendValue(value){
    const lastChar = currentInput.slice(-1);
    const operators = ['/','*','+','-'];

    if (operators.includes(lastChar) && operators.includes(value)) return;

    const parts = currentInput.split(/[+\-*/]/);
    const lastPart = parts[parts.length - 1];

    if (value === '.' && lastPart.includes('.')) return;

    if(currentInput === '0' && value !== '.'){
        currentInput = value;
    }
    else if(currentInput === 'Error'){
        currentInput = value;
    }
    else{
        currentInput += value;
    }

    updateDisplay();
}

function clearDisplay(){
    currentInput = '0';
    updateDisplay();
}

function deleteLast(){
    currentInput = currentInput.length > 1
        ? currentInput.slice(0, -1)
        : '0';

    updateDisplay();
}

function calculate(){
    try {
        currentInput = String(eval(currentInput));
    } catch {
        currentInput = 'Error';
    }
    updateDisplay();
}

/* ---------------- DRAGGABLE LOGIC ---------------- */
let isDragging = false;
let offsetX = 0;
let offsetY = 0;

calculator.addEventListener('mousedown', (e) => {
    isDragging = true;
    const rect = calculator.getBoundingClientRect();
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
});

document.addEventListener('mousemove', (e) => {
    if (!isDragging) return;

    calculator.style.position = 'absolute';
    calculator.style.left = `${e.clientX - offsetX}px`;
    calculator.style.top = `${e.clientY - offsetY}px`;
});

document.addEventListener('mouseup', () => {
    isDragging = false;
});

/* Touch support */
calculator.addEventListener('touchstart', (e) => {
    isDragging = true;
    const rect = calculator.getBoundingClientRect();
    offsetX = e.touches[0].clientX - rect.left;
    offsetY = e.touches[0].clientY - rect.top;
});

document.addEventListener('touchmove', (e) => {
    if (!isDragging) return;

    calculator.style.position = 'absolute';
    calculator.style.left = `${e.touches[0].clientX - offsetX}px`;
    calculator.style.top = `${e.touches[0].clientY - offsetY}px`;
});

document.addEventListener('touchend', () => {
    isDragging = false;
});

updateDisplay();
