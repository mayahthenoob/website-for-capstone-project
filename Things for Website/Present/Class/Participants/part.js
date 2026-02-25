const inboxData = [
    { sender: "Joe Doe", task: "Design Proposal - ABC Project", time: "10:00 AM" },
    { sender: "John Els", task: "Graphic Design Brief - 2026", time: "10:00 AM" },
    { sender: "Nando Endae", task: "Mentioned you in ABC Project", time: "Yesterday" }
];

const listContainer = document.getElementById('inboxList');

function renderInbox() {
    listContainer.innerHTML = inboxData.map(item => `
        <div class="inbox-item">
            <div style="font-weight: 600; font-size: 14px;">${item.sender}</div>
            <div style="font-size: 13px; color: #666;">${item.task}</div>
            <div style="font-size: 11px; color: #999; margin-top: 5px;">${item.time}</div>
        </div>
    `).join('');
}

renderInbox();