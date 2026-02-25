const inboxData = [
    { id: 1, name: "Joe Doe", type: "invited you to", target: "Design Proposal - ABC Project", time: "10:00 AM", status: "red" },
    { id: 2, name: "Jhon Els", type: "invited you to", target: "Graphic Design Brief - 2026", time: "10:00 AM", status: "yellow" },
    { id: 3, name: "Nando Endae", type: "mention you in", target: "ABC Project", time: "10:00 AM", status: "red", active: true },
    { id: 4, name: "Joe Doe", type: "invited you to", target: "Design Proposal - Lumino Project", time: "Yesterday", status: "yellow" },
    { id: 5, name: "Alex Dolla", type: "invited you to", target: "Design Proposal - Nila Project", time: "Yesterday", status: null },
    { id: 6, name: "Alex Dolla", type: "invited you to", target: "Design shot - 2026", time: "Jan 12", status: "red" }
];

const listEl = document.getElementById('inboxList');

function renderInbox() {
    listEl.innerHTML = inboxData.map(item => `
        <div class="inbox-item ${item.active ? 'active' : ''}" onclick="selectItem(${item.id})">
            <div class="avatar"></div>
            <div class="msg-content">
                ${item.name} ${item.type} <b>${item.target}</b>
            </div>
            <div class="msg-meta">
                ${item.time}
                ${item.status ? `<span class="indicator ${item.status}"></span>` : ''}
            </div>
        </div>
    `).join('');
}

function selectItem(id) {
    inboxData.forEach(item => item.active = (item.id === id));
    renderInbox();
}

// Initial Render
renderInbox();