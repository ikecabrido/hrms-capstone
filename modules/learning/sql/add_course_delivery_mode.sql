ALTER TABLE ld_course
    ADD COLUMN delivery_mode ENUM('online', 'face_to_face', 'hybrid') NOT NULL DEFAULT 'online' AFTER status;