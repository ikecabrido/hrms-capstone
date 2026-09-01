// Dashboard Charts Handler
const dashboardCharts = {};

function initDashboardCharts() {
  // Wait for Chart.js to be loaded
  if (typeof Chart === 'undefined') {
    console.warn('Chart.js not loaded, retrying in 100ms...');
    setTimeout(initDashboardCharts, 100);
    return;
  }

  console.log('Initializing dashboard charts...');
  
  // Survey Analytics Chart
  const surveyCanvas = document.getElementById('dashboardSurveyChart');
  if (surveyCanvas) {
    renderSurveyChart(surveyCanvas);
  } else {
    console.warn('Survey chart canvas not found');
  }

  // Feedback Analytics Chart
  const feedbackCanvas = document.getElementById('dashboardFeedbackChart');
  if (feedbackCanvas) {
    renderFeedbackChart(feedbackCanvas);
  } else {
    console.warn('Feedback chart canvas not found');
  }

  // Grievances Analytics Chart
  const grievanceCanvas = document.getElementById('dashboardGrievanceChart');
  if (grievanceCanvas) {
    renderGrievanceChart(grievanceCanvas);
  } else {
    console.warn('Grievance chart canvas not found');
  }
}

function renderSurveyChart(canvas) {
  try {
    // Destroy existing chart if it exists
    if (dashboardCharts.survey) {
      dashboardCharts.survey.destroy();
    }

    // Get data from canvas attributes
    const labels = JSON.parse(canvas.dataset.surveyLabels || '[]');
    const values = JSON.parse(canvas.dataset.surveyValues || '[]');

    console.log('Survey Chart Data:', { labels, values });

    if (!labels.length || !values.length) {
      console.log('No data for survey chart');
      return;
    }

    // Set canvas size
    const parent = canvas.parentElement;
    canvas.width = parent.clientWidth - 20 || 350;
    canvas.height = 260;

    const ctx = canvas.getContext('2d');
    dashboardCharts.survey = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: [
            '#007bff', '#6610f2', '#20c997', '#fd7e14', 
            '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14'
          ],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: { size: 12 }
            }
          }
        }
      }
    });

    console.log('Survey chart created successfully');
  } catch (err) {
    console.error('Error rendering survey chart:', err);
  }
}

function renderFeedbackChart(canvas) {
  try {
    // Destroy existing chart if it exists
    if (dashboardCharts.feedback) {
      dashboardCharts.feedback.destroy();
    }

    // Get data from canvas attributes
    const labels = JSON.parse(canvas.dataset.feedbackLabels || '[]');
    const values = JSON.parse(canvas.dataset.feedbackValues || '[]');

    console.log('Feedback Chart Data:', { labels, values });

    if (!labels.length || !values.length) {
      console.log('No data for feedback chart');
      return;
    }

    // Set canvas size
    const parent = canvas.parentElement;
    canvas.width = parent.clientWidth - 20 || 350;
    canvas.height = 260;

    const ctx = canvas.getContext('2d');
    dashboardCharts.feedback = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: [
            '#17a2b8', '#28a745', '#ffc107', '#dc3545', 
            '#6c757d', '#007bff', '#e83e8c', '#fd7e14'
          ],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: { size: 12 }
            }
          }
        }
      }
    });

    console.log('Feedback chart created successfully');
  } catch (err) {
    console.error('Error rendering feedback chart:', err);
  }
}

function renderGrievanceChart(canvas) {
  try {
    // Destroy existing chart if it exists
    if (dashboardCharts.grievance) {
      dashboardCharts.grievance.destroy();
    }

    // Get data from canvas attributes
    const labels = JSON.parse(canvas.dataset.grievanceLabels || '[]');
    const values = JSON.parse(canvas.dataset.grievanceValues || '[]');

    console.log('Grievance Chart Data:', { labels, values });

    if (!labels.length || !values.length) {
      console.log('No data for grievance chart');
      return;
    }

    // Set canvas size
    const parent = canvas.parentElement;
    canvas.width = parent.clientWidth - 20 || 350;
    canvas.height = 260;

    const ctx = canvas.getContext('2d');
    dashboardCharts.grievance = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: [
            '#ffc107', '#dc3545', '#17a2b8', '#007bff', 
            '#6f42c1', '#fd7e14', '#28a745', '#e83e8c'
          ],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: { size: 12 }
            }
          }
        }
      }
    });

    console.log('Grievance chart created successfully');
  } catch (err) {
    console.error('Error rendering grievance chart:', err);
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initDashboardCharts);
} else {
  initDashboardCharts();
}

// Also initialize on window load
window.addEventListener('load', initDashboardCharts);
