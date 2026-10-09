ALTER TABLE ld_announcement
    MODIFY audience SET('all', 'instructor', 'learner', 'admin') NOT NULL DEFAULT 'all';