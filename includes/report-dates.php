<?php
/**
 * Shared date-range resolver for report APIs
 * Returns ['start' => DateTime, 'end' => DateTime, 'startDate' => 'Y-m-d', 'endDate' => 'Y-m-d']
 */
function resolveReportDateRange($period = 'week', $startDate = null, $endDate = null) {
    $now = new DateTime();
    $pivotHour = 0; // Business day starts at midnight

    // Calculate the start of the current active business day
    $activeBusinessStart = clone $now;
    $activeBusinessStart->setTime($pivotHour, 0, 0);
    if ($now < $activeBusinessStart) {
        $activeBusinessStart->modify('-1 day');
    }

    if ($startDate && $endDate) {
        $start = new DateTime($startDate);
        $start->setTime($pivotHour, 0, 0);
        $end = new DateTime($endDate);
        // The period ends at 6 AM on the day AFTER the endDate
        $end->modify('+1 day')->setTime($pivotHour, 0, 0);
    } else {
        switch ($period) {
            case 'today':
                $start = clone $activeBusinessStart;
                $end = clone $activeBusinessStart;
                $end->modify('+1 day');
                break;
            case 'yesterday':
                $start = clone $activeBusinessStart;
                $start->modify('-1 day');
                $end = clone $activeBusinessStart;
                break;
            case 'week':
                $start = clone $activeBusinessStart;
                $start->modify('-6 days');
                $end = clone $activeBusinessStart;
                $end->modify('+1 day');
                break;
            case 'month':
                $start = clone $activeBusinessStart;
                $start->modify('-29 days');
                $end = clone $activeBusinessStart;
                $end->modify('+1 day');
                break;
            case 'year':
                $start = clone $activeBusinessStart;
                $start->modify('-364 days');
                $end = clone $activeBusinessStart;
                $end->modify('+1 day');
                break;
            default:
                $start = clone $activeBusinessStart;
                $start->modify('-6 days');
                $end = clone $activeBusinessStart;
                $end->modify('+1 day');
        }
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
    $now = new DateTime();
    $pivotHour = 0;
    if ((int)$now->format('H') < $pivotHour) {
        $now->modify('-1 day');
    }
    return $now->format('Y-m-d');
}

function isWithinReportRange($dateStr, DateTime $start, DateTime $end) {
    if (!$dateStr) return false;
    try {
        $date = new DateTime($dateStr);
        return $date >= $start && $date < $end;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Returns the business date (Y-m-d) that a given timestamp belongs to.
 */
function getBusinessDateForTimestamp($timestamp) {
    if (!$timestamp) return null;
    try {
        $dt = new DateTime($timestamp);
        $pivotHour = 0;
        if ((int)$dt->format('H') < $pivotHour) {
            $dt->modify('-1 day');
        }
        return $dt->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}
