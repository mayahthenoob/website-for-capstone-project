// Function to handle "Back to Course"
function goBack() {
    alert("Redirecting to the course main page...");
    // window.location.href = 'course_home.html';
}

// Example of how you might dynamically update the highest grade
function updateHighestGrade(score, total) {
    const display = document.getElementById('highest-grade-display');
    const percentage = ((score / total) * 100).toFixed(2);
    display.innerText = `Highest grade: ${score.toFixed(2)} / ${total.toFixed(2)}.`;
}

// Initialization - could be used to fetch data from an API
window.onload = () => {
    console.log("Quiz status loaded.");
};