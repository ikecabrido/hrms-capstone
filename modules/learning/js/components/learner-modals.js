(function () {
    'use strict';

    const typeIcons = {
        'course': 'fa-graduation-cap',
        'program': 'fa-layer-group',
        'learning-path': 'fa-route',
        'video-conference': 'fa-video'
    };
    const typeLabels = {
        'course': 'Course',
        'program': 'Program',
        'learning-path': 'Learning Path',
        'video-conference': 'Video Conference'
    };

    // ─── Catalog Modal ─────────────────────────────────────────────────────
    function initCatalogModal() {
        const detailsModal = document.getElementById('details-modal');
        if (!detailsModal) return;

        const closeModalBtn = document.getElementById('close-details-modal');
        const closeBtn = document.getElementById('close-modal-btn');
        const enrollModalBtn = document.getElementById('enroll-modal-btn');
        const viewModalBtn = document.getElementById('view-modal-btn');

        if (!closeModalBtn || !closeBtn) return;

        function closeModal() {
            detailsModal.style.display = 'none';
        }

        // Remove old listeners by cloning
        closeModalBtn.replaceWith(closeModalBtn.cloneNode(true));
        closeBtn.replaceWith(closeBtn.cloneNode(true));
        detailsModal.replaceWith(detailsModal.cloneNode(true));

        // Re-query after cloning
        const modal = document.getElementById('details-modal');
        const closeBtnNew = document.getElementById('close-details-modal');
        const closeBtnFooter = document.getElementById('close-modal-btn');
        const enrollBtn = document.getElementById('enroll-modal-btn');
        const viewBtn = document.getElementById('view-modal-btn');

        function doClose() { modal.style.display = 'none'; }

        closeBtnNew.addEventListener('click', doClose);
        closeBtnFooter.addEventListener('click', doClose);
        modal.addEventListener('click', function (e) {
            if (e.target === this) doClose();
        });

        // Open details modal on card click for ALL types
        document.querySelectorAll('.content-card-item[data-course-id]').forEach(function (card) {
            card.addEventListener('click', function () {
                const isEnrolled = this.dataset.enrolled === 'true';
                const itemType = this.dataset.itemType || 'course';
                const courseId = this.dataset.courseId;
                const link = this.dataset.link;
                const title = this.querySelector('h3').textContent;
                const description = this.querySelector('p').textContent;
                const instructor = this.dataset.instructor || '';

                document.getElementById('details-modal-title').textContent = title;

                let contentHtml = '';

                if (itemType === 'course') {
                    const modules = this.dataset.modules || '0';
                    const enrollments = this.dataset.enrollments || '0';
                    const startDate = this.dataset.startDate || '';
                    const deadline = this.dataset.deadline || '';
                    let detailsHtml = '';
                    if (instructor) detailsHtml += '<p style="margin:0; color:#666;"><strong>Instructor:</strong> ' + instructor + '</p>';
                    detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Duration:</strong> Self-paced</p>';
                    if (parseInt(modules) > 0) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Modules:</strong> ' + modules + '</p>';
                    if (parseInt(enrollments) > 0) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Enrolled:</strong> ' + enrollments + ' learner(s)</p>';
                    if (startDate) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Start Date:</strong> ' + new Date(startDate).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) + '</p>';
                    if (deadline) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Enrollment Deadline:</strong> ' + new Date(deadline).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) + '</p>';
                    contentHtml = '<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">' +
                        '<div style="width:50px;height:50px;border-radius:10px;background:linear-gradient(135deg, rgba(32,0,130,0.85), rgba(81,70,183,0.7));display:flex;align-items:center;justify-content:center;"><i class="fas fa-graduation-cap" style="color:#fff;font-size:1.3rem;"></i></div>' +
                        '<div><span class="pill">Course</span></div></div>' +
                        '<h3 style="margin-top:0;">About This Course</h3><p>' + description + '</p>' +
                        '<div style="background:#f0f0f0; padding:1rem; border-radius:6px; margin:1rem 0;">' + detailsHtml + '</div>';
                } else if (itemType === 'program') {
                    let detailsHtml = '';
                    if (instructor) detailsHtml += '<p style="margin:0; color:#666;"><strong>Coordinator:</strong> ' + instructor + '</p>';
                    detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Type:</strong> Training Program</p>';
                    contentHtml = '<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">' +
                        '<div style="width:50px;height:50px;border-radius:10px;background:linear-gradient(135deg, rgba(16,120,40,0.85), rgba(46,184,92,0.7));display:flex;align-items:center;justify-content:center;"><i class="fas fa-layer-group" style="color:#fff;font-size:1.3rem;"></i></div>' +
                        '<div><span class="pill">Program</span></div></div>' +
                        '<h3 style="margin-top:0;">About This Program</h3><p>' + description + '</p>' +
                        '<div style="background:#f0f0f0; padding:1rem; border-radius:6px; margin:1rem 0;">' + detailsHtml + '</div>';
                } else if (itemType === 'learning-path') {
                    const items = this.dataset.items || '0';
                    let detailsHtml = '';
                    if (instructor) detailsHtml += '<p style="margin:0; color:#666;"><strong>Designed by:</strong> ' + instructor + '</p>';
                    if (parseInt(items) > 0) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Total Items:</strong> ' + items + '</p>';
                    detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Format:</strong> Step-by-step progression</p>';
                    contentHtml = '<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">' +
                        '<div style="width:50px;height:50px;border-radius:10px;background:linear-gradient(135deg, rgba(180,83,9,0.85), rgba(245,158,11,0.7));display:flex;align-items:center;justify-content:center;"><i class="fas fa-route" style="color:#fff;font-size:1.3rem;"></i></div>' +
                        '<div><span class="pill">Learning Path</span></div></div>' +
                        '<h3 style="margin-top:0;">About This Learning Path</h3><p>' + description + '</p>' +
                        '<div style="background:#f0f0f0; padding:1rem; border-radius:6px; margin:1rem 0;">' + detailsHtml + '</div>';
                } else if (itemType === 'video-conference') {
                    const scheduled = this.dataset.scheduled || '';
                    const duration = this.dataset.duration || '';
                    const platform = this.dataset.platform || '';
                    let detailsHtml = '';
                    if (instructor) detailsHtml += '<p style="margin:0; color:#666;"><strong>Host:</strong> ' + instructor + '</p>';
                    if (scheduled) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Scheduled:</strong> ' + new Date(scheduled).toLocaleString('en-US', {month:'short', day:'numeric', year:'numeric', hour:'numeric', minute:'2-digit'}) + '</p>';
                    if (platform) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Platform:</strong> ' + platform.charAt(0).toUpperCase() + platform.slice(1).replace('_', ' ') + '</p>';
                    if (duration) detailsHtml += '<p style="margin:0.3rem 0 0 0; color:#666;"><strong>Duration:</strong> ' + duration + ' minutes</p>';
                    contentHtml = '<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">' +
                        '<div style="width:50px;height:50px;border-radius:10px;background:linear-gradient(135deg, rgba(185,28,28,0.85), rgba(239,68,68,0.7));display:flex;align-items:center;justify-content:center;"><i class="fas fa-video" style="color:#fff;font-size:1.3rem;"></i></div>' +
                        '<div><span class="pill">Video Conference</span></div></div>' +
                        '<h3 style="margin-top:0;">About This Session</h3><p>' + description + '</p>' +
                        '<div style="background:#f0f0f0; padding:1rem; border-radius:6px; margin:1rem 0;">' + detailsHtml + '</div>';
                }

                // Add bookmark and favorite buttons
                contentHtml += '<div style="display:flex;gap:0.75rem;margin-top:1.5rem;">' +
                    '<button type="button" class="bookmark-btn" data-type="' + itemType + '" data-id="' + courseId + '" style="padding:0.5rem 1rem; background:#ffc107; color:#000; border:none; border-radius:6px; cursor:pointer; font-weight:500; font-size:0.85rem;"><i class="fas fa-bookmark" style="margin-right:0.3rem;"></i> Bookmark</button>' +
                    '<button type="button" class="favorite-btn" data-type="' + itemType + '" data-id="' + courseId + '" style="padding:0.5rem 1rem; background:#dc3545; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:500; font-size:0.85rem;"><i class="fas fa-heart" style="margin-right:0.3rem;"></i> Favorite</button>' +
                    '</div>';

                document.getElementById('details-modal-content').innerHTML = contentHtml;

                // Attach bookmark/favorite handlers
                document.querySelectorAll('.bookmark-btn').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var type = this.dataset.type;
                        var id = this.dataset.id;
                        fetch('pages/learner/catalog-subpage/ajax/personal/bookmark-content.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'item_type=' + type + '&reference_id=' + id
                        }).then(function(r) { return r.json(); }).then(function(d) {
                            if (d.success) {
                                btn.innerHTML = '<i class="fas fa-bookmark" style="margin-right:0.3rem;"></i> Bookmarked'; btn.style.background = '#e0e0e0';
                                if (window.showToast) window.showToast('Added to bookmarks', 'success');
                            } else {
                                if (window.showToast) window.showToast(d.message || 'Failed to bookmark', 'error');
                            }
                        }).catch(function() {
                            if (window.showToast) window.showToast('Network error', 'error');
                        });
                    });
                });

                document.querySelectorAll('.favorite-btn').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        var type = this.dataset.type;
                        var id = this.dataset.id;
                        fetch('pages/learner/catalog-subpage/ajax/personal/favorite-content.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'item_type=' + type + '&reference_id=' + id
                        }).then(function(r) { return r.json(); }).then(function(d) {
                            if (d.success) {
                                btn.innerHTML = '<i class="fas fa-heart" style="margin-right:0.3rem;"></i> Favorited'; btn.style.background = '#f8d7da';
                                if (window.showToast) window.showToast('Added to favorites', 'success');
                            } else {
                                if (window.showToast) window.showToast(d.message || 'Failed to favorite', 'error');
                            }
                        }).catch(function() {
                            if (window.showToast) window.showToast('Network error', 'error');
                        });
                    });
                });

                if (itemType === 'course' && !isEnrolled) {
                    enrollBtn.dataset.courseId = courseId;
                    enrollBtn.style.display = 'block';
                    viewBtn.style.display = 'none';
                } else {
                    enrollBtn.style.display = 'none';
                    viewBtn.dataset.link = link;
                    viewBtn.style.display = 'block';
                }

                modal.style.display = 'block';
            });
        });

        // View Details button
        viewBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var link = this.dataset.link;
            if (link) window.location.href = link;
        });

        // Enroll button
        enrollBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var courseId = this.dataset.courseId;
            var card = document.querySelector('.content-card-item[data-course-id="' + courseId + '"]');

            fetch('pages/learner/ajax/enroll-course.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ course_id: courseId })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    card.dataset.enrolled = 'true';
                    card.querySelector('.content-card-footer').innerHTML = '<span style="color:#666;">Already enrolled</span>';
                    doClose();
                    alert('Successfully enrolled in the course!');
                } else {
                    alert('Error: ' + (d.error || 'Failed to enroll'));
                }
            })
            .catch(function (err) { alert('Error enrolling: ' + err.message); });
        });
    }

    // ─── Study Modal ───────────────────────────────────────────────────────
    function initStudyModal() {
        var enrollmentModal = document.getElementById('enrollment-modal');
        if (!enrollmentModal) return;

        var closeBtn = document.getElementById('close-enrollment-modal');
        var closeFooterBtn = document.getElementById('close-enrollment-btn');
        var continueBtn = document.getElementById('continue-learning-btn');

        if (!closeBtn || !closeFooterBtn) return;

        function closeEnrollmentModal() {
            enrollmentModal.style.display = 'none';
        }

        // Remove old listeners by cloning
        enrollmentModal.replaceWith(enrollmentModal.cloneNode(true));

        // Re-query after cloning
        var modal = document.getElementById('enrollment-modal');
        var closeBtnNew = document.getElementById('close-enrollment-modal');
        var closeFooterBtnNew = document.getElementById('close-enrollment-btn');
        var continueBtnNew = document.getElementById('continue-learning-btn');

        function doClose() { modal.style.display = 'none'; }

        closeBtnNew.addEventListener('click', doClose);
        closeFooterBtnNew.addEventListener('click', doClose);
        modal.addEventListener('click', function (e) {
            if (e.target === this) doClose();
        });

        // Open enrollment details modal on card click
        document.querySelectorAll('.content-card-item[data-enrollment-id]').forEach(function (card) {
            card.addEventListener('click', function () {
                var enrollmentId = this.dataset.enrollmentId;
                var courseId = this.dataset.courseId;
                var status = this.dataset.status;
                var title = this.querySelector('h3').textContent;
                var description = this.querySelector('p').textContent;

                document.getElementById('enrollment-modal-title').textContent = title;
                document.getElementById('enrollment-modal-content').innerHTML =
                    '<div>' +
                    '<div style="background:#f0f0f0; padding:1.5rem; border-radius:6px; margin-bottom:1.5rem;">' +
                    '<p style="margin:0 0 0.5rem 0; color:#666;"><strong>Status:</strong> <span style="color:var(--color1); font-weight:600;">' + status.toUpperCase() + '</span></p>' +
                    '<p style="margin:0; color:#666;"><strong>Progress:</strong> 0% Complete</p>' +
                    '</div>' +
                    '<h3 style="margin-top:0;">Course Overview</h3>' +
                    '<p>' + description + '</p>' +
                    '</div>';

                continueBtnNew.dataset.enrollmentId = enrollmentId;
                continueBtnNew.dataset.courseId = courseId;
                continueBtnNew.textContent = status === 'completed' ? 'View Certificate' : 'Continue Learning';
                modal.style.display = 'block';
            });
        });

        continueBtnNew.addEventListener('click', function (e) {
            e.stopPropagation();
            var courseId = this.dataset.courseId;
            var enrollmentId = this.dataset.enrollmentId;
            var card = document.querySelector('.content-card-item[data-enrollment-id="' + enrollmentId + '"]');
            var status = card ? card.dataset.status : 'enrolled';

            if (status === 'completed') {
                window.location.href = '?page=learner/result&certificate_id=' + courseId;
            } else {
                window.location.href = '?page=learner/study-subpage/course&enrollment_id=' + enrollmentId + '&course_id=' + courseId;
            }
        });
    }

    // ─── Init on page load ─────────────────────────────────────────────────
    function initAll() {
        initCatalogModal();
        initStudyModal();
    }

    document.addEventListener('DOMContentLoaded', initAll);
    window.addEventListener('page:loaded', function () { setTimeout(initAll, 10); });
})();
