const body = document.querySelector('body'),
      sidebar = body.querySelector('nav'),
      toggle = body.querySelector(".toggle"),
      toggleIcon = toggle.querySelector('i'),
      modeSwitch = body.querySelector(".toggle-switch"),
      modeText = body.querySelector(".mode-text");

// Toggle sidebar open/close
toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");

    // Rotate or switch icon (X -> menu)
    if(sidebar.classList.contains("close")){
        toggleIcon.classList.replace("bx-x","bx-menu"); // shows menu icon when closed
    } else {
        toggleIcon.classList.replace("bx-menu","bx-x"); // shows X icon when open
    }
});

// Dark mode toggle
modeSwitch.addEventListener("click", () => {
    body.classList.toggle("dark");
    
    if(body.classList.contains("dark")){
        modeText.innerText = "Light mode";
    } else {
        modeText.innerText = "Dark mode";
    }
});