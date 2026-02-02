const searchBox = document.getElementById("searchBox");

let isDragging = false;
let offsetX = 0;
let offsetY = 0;

searchBox.addEventListener("mousedown", (e) => {
    isDragging = true;
    offsetX = e.clientX - searchBox.offsetLeft;
    offsetY = e.clientY - searchBox.offsetTop;
    searchBox.style.cursor = "grabbing";
});

document.addEventListener("mousemove", (e) => {
    if (!isDragging) return;

    searchBox.style.position = "absolute";
    searchBox.style.left = e.clientX - offsetX + "px";
    searchBox.style.top = e.clientY - offsetY + "px";
});

document.addEventListener("mouseup", () => {
    isDragging = false;
    searchBox.style.cursor = "grab";
});
