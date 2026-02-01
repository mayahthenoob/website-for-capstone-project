// Select the paths
const qPath = document.getElementById('Q');
const cPath = document.getElementById('C');
const motionPath = document.getElementById('motionPath');

// Total path lengths
const qLength = qPath.getTotalLength();
const cLength = cPath.getTotalLength();

// Function to animate drawing of letters
function drawLetter(path, length, duration) {
  path.style.transition = `stroke-dashoffset ${duration}s linear`;
  path.style.strokeDashoffset = '0';
}

// Draw Q then C sequentially
setTimeout(() => drawLetter(qPath, qLength, 2), 100); // Draw Q
setTimeout(() => drawLetter(cPath, cLength, 2), 2100); // Draw C

// After letters are drawn, animate along motion path
setTimeout(() => {
  // Wrap letters into a group for moving
  const svg = document.getElementById('svgCanvas');
  const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
  group.appendChild(qPath);
  group.appendChild(cPath);
  svg.appendChild(group);

  let pathLength = motionPath.getTotalLength();
  let duration = 5000; // 5 seconds
  let start = null;

  function animate(time) {
    if (!start) start = time;
    let progress = (time - start) / duration;
    if (progress > 1) progress = 1;

    let point = motionPath.getPointAtLength(pathLength * progress);
    group.setAttribute('transform', `translate(${point.x - 150}, ${point.y - 150})`);
    
    if (progress < 1) {
      requestAnimationFrame(animate);
    }
  }

  requestAnimationFrame(animate);

}, 4200); // Start moving after letters drawn
