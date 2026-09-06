<?php /* JSON-GATEKEEPER-V2-20260825 */
require_once __DIR__ . '/_json_prepend.php';
$GLOBALS['_json_gatekeeper_endpoint'] = basename(__FILE__);

try {
    if (@session_status() === PHP_SESSION_NONE) @session_start(['read_and_close' => false]);
    @require_once __DIR__ . '/model/MedicineInventoryModel.php';

    $model = new MedicineInventoryModel();
    $lowStock = $model->getLowStockAlertMedicines();
    $expired = $model->getExpiredAlertMedicines();

    $now = new DateTimeImmutable('today');

    $normalizeExpired = array_map(function ($m) use ($now) {
        $expiry = !empty($m['expiry_date']) ? date_create($m['expiry_date']) : null;
        $daysAgo = 0;
        $expiryDisplay = 'N/A';
        if ($expiry instanceof DateTimeInterface) {
            $diff = $expiry->diff($now);
            $daysAgo = (int)$diff->days;
            if ($expiry < $now) {
                $daysAgo = (int)$diff->days;
            } else {
                $daysAgo = 0;
            }
            $expiryDisplay = $expiry->format('M j, Y');
        }
        return [
            'medicine_id' => $m['medicine_id'] ?? null,
            'medicine_name' => $m['medicine_name'] ?? 'Unknown',
            'generic_name' => $m['generic_name'] ?? '',
            'category' => $m['category'] ?? 'N/A',
            'current_stock' => (int)($m['current_stock'] ?? 0),
            'reorder_level' => (int)($m['reorder_level'] ?? 0),
            'expiry_date' => $m['expiry_date'] ?? null,
            'expiry_display' => $expiryDisplay,
            'days_expired' => $daysAgo,
        ];
    }, $expired);

    $normalizeLowStock = array_map(function ($m) {
        return [
            'medicine_id' => $m['medicine_id'] ?? null,
            'medicine_name' => $m['medicine_name'] ?? 'Unknown',
            'generic_name' => $m['generic_name'] ?? '',
            'category' => $m['category'] ?? 'N/A',
            'current_stock' => (int)($m['current_stock'] ?? 0),
            'reorder_level' => (int)($m['reorder_level'] ?? 0),
            'unit' => $m['unit'] ?? 'pcs',
            'status' => $m['status'] ?? 'Low Stock',
        ];
    }, $lowStock);

    jsonGatekeeperEmit([
        'success' => true,
        'expired_count' => count($normalizeExpired),
        'low_stock_count' => count($normalizeLowStock),
        'total_alerts' => count($normalizeExpired) + count($normalizeLowStock),
        'has_expired' => count($normalizeExpired) > 0,
        'has_low_stock' => count($normalizeLowStock) > 0,
        'severity' => count($normalizeExpired) > 0 ? 'high' : (count($normalizeLowStock) > 0 ? 'medium' : 'none'),
        'expired' => $normalizeExpired,
        'low_stock' => $normalizeLowStock,
        'generated_at' => date('c'),
    ]);
} catch (Throwable $e) {
    @error_log('[clinic-medicine-alerts] Failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    jsonGatekeeperEmit([
        'success' => false,
        'expired_count' => 0,
        'low_stock_count' => 0,
        'total_alerts' => 0,
        'severity' => 'none',
        'expired' => [],
        'low_stock' => [],
        'message' => 'Unable to load stock alerts.',
    ], 500);
}
