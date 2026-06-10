<?php
/**
 * Shared date-range resolver for report APIs
 * Returns ['start' => DateTime, 'end' => DateTime, 'startDate' => 'Y-m-d', 'endDate' => 'Y-m-d']
 */
function resolveReportDateRange($period = 'week', $startDate = null, $endDate = null) {
    $end = new DateTime();
    $end->setTime(23, 59, 59);

    if ($startDate && $endDate) {
        $start = new DateTime($startDate);
        $start->setTime(0, 0, 0);
        $end = new DateTime($endDate);
        $end->setTime(23, 59, 59);
    } else {
        $start = new DateTime();
        switch ($period) {
            case 'today':
                $start->setTime(0, 0, 0);
                break;
            case 'week':
                $start->modify('-7 days')->setTime(0, 0, 0);
                break;
            case 'month':
                $start->modify('-30 days')->setTime(0, 0, 0);
                break;
            case 'year':
                $start->modify('-365 days')->setTime(0, 0, 0);
                break;
            default:
                $start->modify('-7 days')->setTime(0, 0, 0);
        }
    }

    return [
        'start' => $start,
        'end' => $end,
        'startDate' => $start->format('Y-m-d'),
        'endDate' => $end->format('Y-m-d'),
    ];
}

function isWithinReportRange($dateStr, DateTime $start, DateTime $end) {
    if (!$dateStr) return false;
    $date = new DateTime($dateStr);
    return $date >= $start && $date <= $end;
}
