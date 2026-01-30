function chatbot(input) {
  input = input.toLowerCase();
  if (input.includes("hello") || input.includes("hi")) {
    return "Hello! Nice to meet you 😊";
  } else if (input.includes("how are you")) {
    return "I'm doing great! Thanks for asking.";
  } else if (input.includes("your name")) {
    return "I'm Jarvis, your personal chatbot.";
  } else if (input.includes("joke")) {
    return "Why don’t programmers like nature? Too many bugs 🐛";
  } else {
    return "Sorry, I didn’t understand that. Try asking something else.";
  }
}

/* TIMESTAMP */
function getTime() {
  const now = new Date();
  return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

/* DISPLAY MESSAGE */
function displayMessage(message, sender) {
  const chat = document.getElementById("chat");

  const msg = document.createElement("div");
  msg.classList.add("message", sender);

  const bubble = document.createElement("div");
  bubble.classList.add("bubble");

  const text = document.createElement("div");
  text.classList.add("text");
  text.textContent = message;

  const time = document.createElement("span");
  time.classList.add("time");
  time.textContent = getTime();

  bubble.appendChild(text);
  bubble.appendChild(time);
  msg.appendChild(bubble);

  chat.appendChild(msg);
  chat.scrollTop = chat.scrollHeight;
}

/* REMOVE EMOJIS FOR SPEECH */
function removeEmojis(text) {
  return text.replace(
    /([\u2700-\u27BF]|[\uE000-\uF8FF]|[\uD83C-\uDBFF\uDC00-\uDFFF])/g,
    ""
  );
}

/* TEXT TO SPEECH */
function speak(text) {
  const cleanText = removeEmojis(text);
  const speech = new SpeechSynthesisUtterance(cleanText);
  speech.lang = "en-US";
  speech.rate = 1;
  window.speechSynthesis.speak(speech);
}

/* SEND MESSAGE */
function sendMessage(message) {
  if (!message) return;

  displayMessage(message, "user");

  const reply = chatbot(message);
  setTimeout(() => {
    displayMessage(reply, "bot");
    speak(reply);
  }, 600);

  input.value = "";
}

/* INPUT HANDLERS */
const input = document.getElementById("input");
document.getElementById("button").onclick = () => sendMessage(input.value);

input.addEventListener("keydown", e => {
  if (e.key === "Enter") sendMessage(input.value);
});

/* SPEECH RECOGNITION */
const micBtn = document.getElementById("mic");
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
const recognition = new SpeechRecognition();

recognition.lang = "en-US";
recognition.continuous = false;

micBtn.onclick = () => {
  recognition.start();
  micBtn.classList.add("active");
};

recognition.onresult = event => {
  const transcript = event.results[0][0].transcript;
  input.value = transcript;
  sendMessage(transcript);
};

recognition.onend = () => {
  micBtn.classList.remove("active");
};
