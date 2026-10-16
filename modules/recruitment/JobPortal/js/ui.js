function timeAgo(ts) {
    var diff = Math.max(0, Date.now() - ts);
    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    if (days === 0) return "today";
    if (days === 1) return "1 day ago";
    if (days < 30) return days + " days ago";
    var months = Math.floor(days / 30);
    return months + (months === 1 ? " month ago" : " months ago");
}

function scrollToJobs() {
    document.getElementById('jobsSection').scrollIntoView({
        behavior: 'smooth'
    });
}

function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

document.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('modal-backdrop')) {
        e.target.classList.remove('open');
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-backdrop.open').forEach(function(m) {
            m.classList.remove('open');
        });
    }
});

function openContactUs() {
    openModal('contactUsModal');
}
