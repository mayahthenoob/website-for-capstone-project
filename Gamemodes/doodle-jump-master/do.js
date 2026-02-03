// ===== BOARD =====
let board;
let boardWidth = 480;
let boardHeight = 576;
let context;

// ===== DOODLER =====
let doodlerWidth = 46;
let doodlerHeight = 46;
let doodlerX = boardWidth / 2 - doodlerWidth / 2;
let doodlerY = boardHeight * 7 / 8 - doodlerHeight;

let doodlerRightImg, doodlerLeftImg;

let doodler = {
    img: null,
    x: doodlerX,
    y: doodlerY,
    width: doodlerWidth,
    height: doodlerHeight
};

// ===== PHYSICS =====
let velocityX = 0;
let velocityY = 0;
let initialVelocityY = -8;
let gravity = 0.4;

// ===== CAMERA =====
let cameraY = 0;
let cameraSmoothness = 0.1;

// ===== PLATFORMS =====
let platformArray = [];
let platformWidth = 60;
let platformHeight = 18;
let platformImg, breakPlatformImg, springImg;

let movingPlatformChance = 0.3;
let breakPlatformChance = 0.2;
let springChance = 0.15;
let springBoost = -14;

// ===== ENEMIES =====
let enemyArray = [];
let enemyImg;

// ===== SCORE =====
let score = 0;
let maxScore = 0;
let highScore = localStorage.getItem("highScore") || 0;
let gameOver = false;

// ===== INPUT =====
let touchStartX = null;

// ===== LOAD =====
window.onload = () => {
    board = document.getElementById("board");
    board.width = boardWidth;
    board.height = boardHeight;
    context = board.getContext("2d");

    doodlerRightImg = new Image();
    doodlerRightImg.src = "./radio-guy-right.png";
    doodlerLeftImg = new Image();
    doodlerLeftImg.src = "./radio-guy-left.png";
    doodler.img = doodlerRightImg;

    platformImg = new Image();
    platformImg.src = "./plat.png";

    breakPlatformImg = new Image();
    breakPlatformImg.src = "./plat-break.png";

    springImg = new Image();
    springImg.src = "./spring.png";

    enemyImg = new Image();
    enemyImg.src = "./enemy.png";

    velocityY = initialVelocityY;
    placePlatforms();
    requestAnimationFrame(update);

    document.addEventListener("keydown", moveDoodler);
    setupTouch();
};

// ===== GAME LOOP =====
function update() {
    requestAnimationFrame(update);
    if (gameOver) return;

    context.clearRect(0, 0, boardWidth, boardHeight);

    // doodler physics
    doodler.x += velocityX;
    velocityY += gravity;
    doodler.y += velocityY;

    if (doodler.x > boardWidth) doodler.x = 0;
    if (doodler.x + doodler.width < 0) doodler.x = boardWidth;

    if (doodler.y > boardHeight) gameOver = true;

    // camera
    let targetCameraY = doodler.y - boardHeight * 0.4;
    cameraY += (targetCameraY - cameraY) * cameraSmoothness;

    // platforms
    for (let platform of platformArray) {
        if (platform.isMoving) {
            platform.x += platform.speed * platform.direction;
            if (platform.x <= 0 || platform.x + platform.width >= boardWidth) {
                platform.direction *= -1;
            }
        }

        if (detectCollision(doodler, platform) && velocityY >= 0 && !platform.broken) {
            velocityY = platform.hasSpring ? springBoost : initialVelocityY;
            if (platform.isBreakable) platform.broken = true;
        }

        if (!platform.broken) {
            let img = platform.isBreakable ? breakPlatformImg : platformImg;
            context.drawImage(img, platform.x, platform.y - cameraY, platform.width, platform.height);

            if (platform.hasSpring) {
                context.drawImage(
                    springImg,
                    platform.x + platform.width / 2 - 10,
                    platform.y - 12 - cameraY,
                    20,
                    12
                );
            }
        }
    }

    while (platformArray.length && platformArray[0].y - cameraY > boardHeight) {
        platformArray.shift();
        newPlatform();
    }

    // enemies
    if (Math.random() < 0.005) newEnemy();

    for (let enemy of enemyArray) {
        enemy.y += 1.5;
        if (detectCollision(doodler, enemy)) {
            if (velocityY > 0) {
                velocityY = initialVelocityY;
                enemy.y = boardHeight + 100;
            } else {
                gameOver = true;
            }
        }
        context.drawImage(enemyImg, enemy.x, enemy.y - cameraY, enemy.width, enemy.height);
    }

    // draw doodler
    context.drawImage(doodler.img, doodler.x, doodler.y - cameraY, doodler.width, doodler.height);

    updateScore();
    context.fillStyle = "black";
    context.font = "16px sans-serif";
    context.fillText("Score: " + score, 10, 20);
    context.fillText("High: " + highScore, 10, 40);

    if (gameOver) {
        context.fillText("Game Over — Space to Restart", 80, boardHeight / 2);
    }
}

// ===== INPUT =====
function moveDoodler(e) {
    if (e.code === "ArrowRight" || e.code === "KeyD") {
        velocityX = 4;
        doodler.img = doodlerRightImg;
    }
    if (e.code === "ArrowLeft" || e.code === "KeyA") {
        velocityX = -4;
        doodler.img = doodlerLeftImg;
    }
    if (e.code === "Space" && gameOver) resetGame();
}

function setupTouch() {
    document.addEventListener("touchstart", e => {
        touchStartX = e.touches[0].clientX;
    });
    document.addEventListener("touchmove", e => {
        if (!touchStartX) return;
        velocityX = (e.touches[0].clientX - touchStartX) * 0.05;
        doodler.img = velocityX > 0 ? doodlerRightImg : doodlerLeftImg;
    });
    document.addEventListener("touchend", () => {
        velocityX = 0;
        touchStartX = null;
    });
}

// ===== HELPERS =====
function placePlatforms() {
    platformArray = [];
    for (let i = 0; i < 7; i++) newPlatform(boardHeight - i * 110);
}

function newPlatform(y = -platformHeight) {
    platformArray.push({
        x: Math.random() * (boardWidth - platformWidth),
        y: y,
        width: platformWidth,
        height: platformHeight,
        isMoving: Math.random() < movingPlatformChance,
        speed: Math.random() * 1.5 + 0.5,
        direction: Math.random() < 0.5 ? -1 : 1,
        isBreakable: Math.random() < breakPlatformChance,
        broken: false,
        hasSpring: Math.random() < springChance
    });
}

function newEnemy() {
    enemyArray.push({
        x: Math.random() * (boardWidth - 40),
        y: -40,
        width: 40,
        height: 40
    });
}

function detectCollision(a, b) {
    return a.x < b.x + b.width &&
           a.x + a.width > b.x &&
           a.y < b.y + b.height &&
           a.y + a.height > b.y;
}

function updateScore() {
    if (velocityY < 0) {
        score += 1;
        if (score > highScore) {
            highScore = score;
            localStorage.setItem("highScore", highScore);
        }
    }
}

function resetGame() {
    doodler.x = doodlerX;
    doodler.y = doodlerY;
    velocityY = initialVelocityY;
    velocityX = 0;
    score = 0;
    gameOver = false;
    enemyArray = [];
    placePlatforms();
}
