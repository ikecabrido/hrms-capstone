<?php
require_once __DIR__ . '/../models/KnowledgeTransferModel.php';
$m = new KnowledgeTransferModel();
try {
    var_dump($m->ldKnowledgeTransferIntegrationAvailable());
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
}