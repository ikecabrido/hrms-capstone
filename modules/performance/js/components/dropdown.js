const bellBtn = document.getElementById('bellBtn');
const bellDropdown = document.getElementById('bellDropdown');
const bellWrapper = document.getElementById('bellWrapper');
const userBtn = document.getElementById('userBtn');
const userDropdown = document.getElementById('userDropdown');
const userWrapper = document.getElementById('userWrapper');

function closeDropdowns() {
    bellDropdown?.classList.remove('open');
    userDropdown?.classList.remove('open');
}

function openDropdown(dropdown, trigger) {
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    const triggerRect = trigger.getBoundingClientRect();
    const sidebarRect = sidebar.getBoundingClientRect();
    dropdown.style.top = `${triggerRect.top}px`;
    dropdown.style.left = `${sidebarRect.right + 8}px`;
    dropdown.classList.add('open');
}

bellBtn?.addEventListener('click', event => {
    event.stopPropagation();
    const isOpen = bellDropdown.classList.contains('open');
    closeDropdowns();
    if (!isOpen) openDropdown(bellDropdown, bellBtn);
});

userBtn?.addEventListener('click', event => {
    event.stopPropagation();
    const isOpen = userDropdown.classList.contains('open');
    closeDropdowns();
    if (!isOpen) openDropdown(userDropdown, userBtn);
});

document.addEventListener('click', event => {
    if (!bellWrapper?.contains(event.target) && !userWrapper?.contains(event.target)) {
        closeDropdowns();
    }
});
