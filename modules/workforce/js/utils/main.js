    import { reinitPage } from './page-init.js';

    document.addEventListener('DOMContentLoaded', function () {

    // ─── Initial Load ─────────────────────────────────────────────────────────────

    var initial = new URL(location).searchParams.get('page') || 'dashboard-overview';
    reinitPage(initial);

    });