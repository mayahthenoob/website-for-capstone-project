const display = document.getElementById('display');
let currentInput = '0';
const MAX_DISPLAY_LENGTH = 14;

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

updateDisplay();
