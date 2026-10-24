ALTER TABLE `exit_documents`
  MODIFY COLUMN `document_type`
    ENUM('resignation_letter', 'termination_letter')
    NOT NULL;

UPDATE `exit_documents`
SET `document_type` = 'termination_letter'
WHERE `document_type` = ''
  AND `title` = 'Termination Letter';
