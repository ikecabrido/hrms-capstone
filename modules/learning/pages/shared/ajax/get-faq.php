<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true,'items'=>[['question'=>'How do I enroll?','answer'=>'Go to Catalog and click Enroll Now.'],['question'=>'How do I track progress?','answer'=>'Visit the Study page.'],['question'=>'How do I earn a certificate?','answer'=>'Complete all course content.']]]);
