<?php

class TrainingAuthoring
{
    public static function renderHero(string $title, string $description, string $backUrl = '?page=instructor/training'): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
        $safeBackUrl = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');

        return '<header class="training-authoring-header">'
            . '<a class="training-authoring-back" href="' . $safeBackUrl . '"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Training</a>'
            . '<section class="home-hero training-authoring-hero" aria-label="' . $safeTitle . '">'
            . '<div class="home-hero-main"><h1>' . $safeTitle . '</h1><p>' . $safeDescription . '</p></div>'
            . '</section>'
            . '</header>';
    }
}
