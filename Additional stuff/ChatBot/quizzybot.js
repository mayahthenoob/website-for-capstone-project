const chatBox = document.getElementById("chat-box");
const userInput = document.getElementById("user-input");

const responses = {
    "hi": "Hello! How can I help you?",
    "hello": "Hi there! What’s up?",
    "how are you": "I'm just a bot, but I'm doing great! How about you?",
    "bye": "Goodbye! Have a great day!",
    "your name": "I'm ChatBot, your virtual assistant.",
    "default": "Sorry, I don't understand that. Can you rephrase?"
};

function sendMessage() {
    const message = userInput.value.trim().toLowerCase();
    if (message === "") return;

    displayMessage(message, "user");

    const botReply = responses[message] || responses["default"];
    setTimeout(() => {
        displayMessage(botReply, "bot");
}, 500);

    userInput.value = "";
}

function displayMessage(text, sender) {
    const msgDiv = document.createElement("div");
    msgDiv.classList.add("message", sender);
    msgDiv.textContent = text;
    chatBox.appendChild(msgDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

userInput.addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        sendMessage();
    }
});
