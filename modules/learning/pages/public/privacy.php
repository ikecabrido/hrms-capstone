<?php
// Rendered inside the module shell by Page::render(), which supplies the header,
// sidebar and CSS — including header.php here would render a second document.
$legalUpdated = date('F j, Y', filemtime(__FILE__));
?>
<section class="legal-page" aria-labelledby="privacy-title">
    <div class="legal-hero">
        <div class="legal-hero-copy">
            <p class="legal-eyebrow"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Legal &amp; Compliance</p>
            <h1 id="privacy-title">Privacy Policy</h1>
            <p class="legal-intro">This Privacy Policy describes how the Learning &amp; Development module collects, uses, and protects your personal information.</p>
            <div class="legal-meta">
                <span><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Last updated <?= htmlspecialchars($legalUpdated, ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="fa-solid fa-lock" aria-hidden="true"></i> Employee learning records</span>
            </div>
        </div>
        <div class="legal-seal" aria-hidden="true">
            <div class="legal-seal-inner">
                <i class="fa-solid fa-shield-halved"></i>
                <strong>Learning &amp; Development</strong>
                <span>Official policy</span>
            </div>
        </div>
    </div>

    <div class="legal-card">
        <nav class="legal-tabs" aria-label="Legal documents">
            <a class="is-active" href="?page=public/privacy" aria-current="page">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy
            </a>
            <a href="?page=public/terms">
                <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Service
            </a>
        </nav>

        <div class="legal-body">
            <section class="legal-section">
                <div class="legal-section-icon" aria-hidden="true"><i class="fa-solid fa-database"></i></div>
                <div>
                    <h2>Information We Collect</h2>
                    <p>We collect information necessary for your learning activities, including your name, employee ID, course enrollments, quiz scores, and learning progress.</p>
                </div>
            </section>

            <section class="legal-section">
                <div class="legal-section-icon" aria-hidden="true"><i class="fa-solid fa-chart-line"></i></div>
                <div>
                    <h2>How We Use Your Information</h2>
                    <p>Your information is used to track learning progress, generate certificates, and provide analytics to trainers and administrators.</p>
                </div>
            </section>

            <section class="legal-section">
                <div class="legal-section-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <h2>Data Protection</h2>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access.</p>
                </div>
            </section>
        </div>
    </div>

    <aside class="legal-note">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <p>This policy applies to the Learning &amp; Development module and the learning records connected to your employee account. For questions, contact your Learning &amp; Development administrator.</p>
    </aside>
</section>
