<?php
// Rendered inside the module shell by Page::render(), which supplies the header,
// sidebar and CSS — including header.php here would render a second document.
$legalUpdated = date('F j, Y', filemtime(__FILE__));
?>
<section class="legal-page" aria-labelledby="terms-title">
    <div class="legal-hero">
        <div class="legal-hero-copy">
            <p class="legal-eyebrow"><i class="fa-solid fa-file-contract" aria-hidden="true"></i> Legal &amp; Compliance</p>
            <h1 id="terms-title">Terms of Service</h1>
            <p class="legal-intro">By using the Learning &amp; Development platform, you agree to the following terms.</p>
            <div class="legal-meta">
                <span><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Last updated <?= htmlspecialchars($legalUpdated, ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="fa-solid fa-book-open" aria-hidden="true"></i> Platform use and content</span>
            </div>
        </div>
        <div class="legal-seal" aria-hidden="true">
            <div class="legal-seal-inner">
                <i class="fa-solid fa-file-contract"></i>
                <strong>Learning &amp; Development</strong>
                <span>Official policy</span>
            </div>
        </div>
    </div>

    <div class="legal-card">
        <nav class="legal-tabs" aria-label="Legal documents">
            <a href="?page=public/privacy">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Privacy Policy
            </a>
            <a class="is-active" href="?page=public/terms" aria-current="page">
                <i class="fa-solid fa-file-contract" aria-hidden="true"></i> Terms of Service
            </a>
        </nav>

        <div class="legal-body">
            <section class="legal-section">
                <div class="legal-section-icon" aria-hidden="true"><i class="fa-solid fa-user-check"></i></div>
                <div>
                    <h2>Acceptable Use</h2>
                    <p>This platform is for educational and training purposes. Users should engage with content respectfully and honestly.</p>
                </div>
            </section>

            <section class="legal-section">
                <div class="legal-section-icon" aria-hidden="true"><i class="fa-solid fa-copyright"></i></div>
                <div>
                    <h2>Content Ownership</h2>
                    <p>Course content is owned by the trainers and organization. Trainees may not redistribute course materials.</p>
                </div>
            </section>
        </div>
    </div>

    <aside class="legal-note">
        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
        <p>These terms apply to all courses, learning paths, and training activities in the Learning &amp; Development module. For questions, contact your Learning &amp; Development administrator.</p>
    </aside>
</section>
