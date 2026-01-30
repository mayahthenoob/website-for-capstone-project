let btnRef = document.querySelectorAll(".button-option");
let popupRef = document.querySelector(".popup");
let newgameBtn = document.getElementById("new-game");
let restartBtn = document.getElementById("restart");
let msgRef = document.getElementById("message");

let winningPattern = [
  [0, 1, 2],
  [0, 3, 6],
  [2, 5, 8],
  [6, 7, 8],
  [3, 4, 5],
  [1, 4, 7],
  [0, 4, 8],
  [2, 4, 6],
];

let xTurn = true;
let count = 0;

const disableButtons = () => {
  btnRef.forEach(btn => btn.disabled = true);
  popupRef.classList.remove("hide");
};

const enableButtons = () => {
  btnRef.forEach(btn => {
    btn.innerText = "";
    btn.disabled = false;
  });
  popupRef.classList.add("hide");
  xTurn = true;
};

const winFunction = (letter) => {
  disableButtons();
  msgRef.innerHTML = `🎉 <br> '${letter}' Wins`;
};

const drawFunction = () => {
  disableButtons();
  msgRef.innerHTML = "😎 <br> It's a Draw";
};

newgameBtn.addEventListener("click", () => {
  count = 0;
  enableButtons();
});

restartBtn.addEventListener("click", () => {
  count = 0;
  enableButtons();
});

const winChecker = () => {
  for (let pattern of winningPattern) {
    let [a, b, c] = pattern;
    let val1 = btnRef[a].innerText;
    let val2 = btnRef[b].innerText;
    let val3 = btnRef[c].innerText;

    if (val1 !== "" && val1 === val2 && val2 === val3) {
      winFunction(val1);
      return;
    }
  }
};

btnRef.forEach(btn => {
  btn.addEventListener("click", () => {
    if (xTurn) {
      btn.innerText = "X";
      xTurn = false;
    } else {
      btn.innerText = "O";
      xTurn = true;
    }

    btn.disabled = true;
    count++;

    winChecker();

    if (count === 9) {
      drawFunction();
    }
  });
});

window.onload = enableButtons;
