// Fade out the entire loader screen
gsap.fromTo(
  ".loading-page",
  { opacity: 1 },
  {
    opacity: 0,
    display: "none",
    duration: 1.5,
    delay: 4.5, // Total time for drawing + viewing
  }
);

// Animate the Quiz Carnival text sliding up
gsap.fromTo(
  ".logo-name",
  {
    y: 50,
    opacity: 0,
  },
  {
    y: 0,
    opacity: 1,
    duration: 1.5,
    delay: 1.5, // Starts during the SVG drawing
    ease: "power2.out"
  }
);