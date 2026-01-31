const c = document.getElementById("Canvas");
const ctx = c.getContext("2d");

let cwidth, cheight;
let shells = [];
let pass = [];

const colors = [
    '#ff5252', '#ff4081', '#e040fb', '#7c4dff',
    '#53dffe', '#40cdff', '#18ffff',
    '#64ffda', '#69f0ae', '#b2ff59'
];

window.onresize = reset;
reset();

function reset() {
    cwidth = window.innerWidth;
    cheight = window.innerHeight;
    c.width = cwidth;
    c.height = cheight;
}

function newShell() {
    const left = Math.random() > 0.5;
    const shell = {
        x: left ? 0 : 1,
        y: 1,
        xoff: (0.01 + Math.random() * 0.007) * (left ? 1 : -1),
        yoff: 0.01 + Math.random() * 0.007,
        size: Math.random() * 6 + 3,
        color: colors[Math.floor(Math.random() * colors.length)]
    };
    shells.push(shell);
}

function newPass(shell) {
    const passCount = Math.ceil(Math.pow(shell.size, 2) * Math.PI);

    for (let i = 0; i < passCount; i++) {
        const p = {
            x: shell.x * cwidth,
            y: shell.y * cheight,
            size: Math.sqrt(shell.size),
            color: shell.color
        };

        const a = Math.random() * Math.PI * 2;
        const s = Math.random() * 10;

        p.xoff = Math.cos(a) * s;
        p.yoff = Math.sin(a) * s;

        if (pass.length < 1000) pass.push(p);
    }
}

let lastRun = 0;
run();

function run() {
    let dt = 1;
    if (lastRun !== 0) {
        dt = Math.min(50, performance.now() - lastRun);
    }
    lastRun = performance.now();

    ctx.fillStyle = "rgba(0,0,0,0.25)";
    ctx.fillRect(0, 0, cwidth, cheight);

    if (shells.length < 10 && Math.random() > 0.96) {
        newShell();
    }

    for (let i = shells.length - 1; i >= 0; i--) {
        const shell = shells[i];

        ctx.beginPath();
        ctx.arc(shell.x * cwidth, shell.y * cheight, shell.size, 0, 2 * Math.PI);
        ctx.fillStyle = shell.color;
        ctx.fill();

        shell.x -= shell.xoff;
        shell.y -= shell.yoff;
        shell.xoff *= 0.99;
        shell.yoff -= 0.002 * dt;

        if (shell.yoff < -0.005) {
            newPass(shell);
            shells.splice(i, 1);
        }
    }

    for (let i = pass.length - 1; i >= 0; i--) {
        const p = pass[i];

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, 2 * Math.PI);
        ctx.fillStyle = p.color;
        ctx.fill();

        p.x += p.xoff;
        p.y += p.yoff;
        p.yoff += 0.05;
        p.size -= 0.02;

        if (p.y > cheight || p.size <= 0) {
            pass.splice(i, 1);
        }
    }

    requestAnimationFrame(run);
}
