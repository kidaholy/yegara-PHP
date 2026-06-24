<?php
/**
 * Shared date-range resolver for report APIs
 * Returns ['start' => DateTime, 'end' => DateTime, 'startDate' => 'Y-m-d', 'endDate' => 'Y-m-d']
 */
function resolveReportDateRange($period = 'week', $startDate = null, $endDate = null) {
    $now = new DateTime();
    $pivotHour = 6; // Business day starts at 6 AM

    // For "Today", use the current calendar date as the base
    // Each Business Day YYYY-MM-DD starts at 06:00:00 AM on that date
    $currBusinessStart = new DateTime();
    $currBusinessStart->setTime($pivotHour, 0, 0);

    if ($startDate && $endDate) {
        $start = new DateTime($startDate);
        $start->setTime($pivotHour, 0, 0);
        $end = new DateTime($endDate);
        $end->modify('+1 day')->setTime($pivotHour, 0, 0);
    } else {
        switch ($period) {
            case 'today':
                $start = clone $currBusinessStart;
                break;
            case 'week':
                $start = clone $currBusinessStart;
                $start->modify('-6 days');
                break;
            case 'month':
                $start = clone $currBusinessStart;
                $start->modify('-29 days');
                break;
            case 'year':
                $start = clone $currBusinessStart;
                $start->modify('-364 days');
                break;
            default:
                $start = clone $currBusinessStart;
                $start->modify('-6 days');
        }
        $end = clone $currBusinessStart;
        $end->modify('+1 day');
    }

    return [
        'start' => $start,
        'end' => $end,
        'startDate' => $start->format('Y-m-d'),
        // endDate string reflects the last BUSINESS day of the range
        'endDate' => (clone $end)->modify('-1 day')->format('Y-m-d'),
    ];
}

/**
 * Returns the current active business date string (Y-m-d)
 * If it's before 6 AM, it returns yesterday's calendar date.
 */
function getActiveBusinessDate() {
    return date('Y-m-d');
}

function isWithinReportRange($dateStr, DateTime $start, DateTime $end) {
    if (!$dateStr) return false;
    $date = new DateTime($dateStr);
    return $date >= $start && $date < $end;
}
