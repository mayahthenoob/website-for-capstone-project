const notesContainer = document.getElementById("app");
const addNoteButton = notesContainer.querySelector(".add-note");

getNotes().forEach((note) => {
  const noteElement = createNoteElement(note.id, note.content);
  notesContainer.insertBefore(noteElement, addNoteButton);
});

addNoteButton.addEventListener("click", addNote);

function getNotes() {
  return JSON.parse(localStorage.getItem("stickynotes-notes") || "[]");
}

function saveNotes(notes) {
  localStorage.setItem("stickynotes-notes", JSON.stringify(notes));
}

function createNoteElement(id, content) {
  const wrapper = document.createElement("div");
  wrapper.classList.add("note-wrapper");
  wrapper.draggable = true;

  const textarea = document.createElement("textarea");
  textarea.classList.add("note");
  textarea.value = content;
  textarea.placeholder = "Empty Sticky Note";

  const deleteBtn = document.createElement("button");
  deleteBtn.classList.add("delete-note");
  deleteBtn.innerHTML = "✕";

  deleteBtn.addEventListener("click", () => {
    deleteNote(id, wrapper);
  });

  textarea.addEventListener("change", () => {
    updateNote(id, textarea.value);
  });

  wrapper.appendChild(deleteBtn);
  wrapper.appendChild(textarea);

  addDragAndDrop(wrapper);

  return wrapper;
}

function addNote() {
  const notes = getNotes();
  const noteObject = {
    id: Math.floor(Math.random() * 100000),
    content: ""
  };

  const noteElement = createNoteElement(noteObject.id, noteObject.content);
  notesContainer.insertBefore(noteElement, addNoteButton);

  notes.push(noteObject);
  saveNotes(notes);
}

function updateNote(id, newContent) {
  const notes = getNotes();
  const targetNote = notes.find((note) => note.id === id);
  if (!targetNote) return;

  targetNote.content = newContent;
  saveNotes(notes);
}

function deleteNote(id, element) {
  const notes = getNotes().filter((note) => note.id !== id);
  saveNotes(notes);
  notesContainer.removeChild(element);
}

function addDragAndDrop(element) {
  element.addEventListener("dragstart", () => {
    element.classList.add("dragging");
  });

  element.addEventListener("dragend", () => {
    element.classList.remove("dragging");
  });
}

notesContainer.addEventListener("dragover", (e) => {
  e.preventDefault();
  const afterElement = getDragAfterElement(notesContainer, e.clientY);
  const dragging = document.querySelector(".dragging");

  if (!dragging) return;

  if (afterElement == null) {
    notesContainer.insertBefore(dragging, addNoteButton);
  } else {
    notesContainer.insertBefore(dragging, afterElement);
  }
});

function getDragAfterElement(container, y) {
  const draggableElements = [
    ...container.querySelectorAll(".note-wrapper:not(.dragging)")
  ];

  return draggableElements.reduce(
    (closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;

      if (offset < 0 && offset > closest.offset) {
        return { offset: offset, element: child };
      } else {
        return closest;
      }
    },
    { offset: Number.NEGATIVE_INFINITY }
  ).element;
}
