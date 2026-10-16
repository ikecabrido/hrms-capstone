async function init() {
    try {
        jobs = await apiRequest('jobs');
        applications = await apiRequest('applications');
        renderJobs();
    } catch (error) {
        console.error(error);
        document.getElementById('jobsGrid').innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><h3>Database connection failed</h3><p>Check that MySQL is running and the database tables are installed.</p></div>';
    }
}

init();
