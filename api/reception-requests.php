<?php
/**
 * API for Reception Lifecycle management
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';
require_once '../includes/report-dates.php'; // Add this for business date logic

function sendJson($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function findRoomByNumber($roomNumber) {
    if (!$roomNumber) return null;
    $rooms = db('rooms')->findMany(['where' => ['isDeleted' => false]]);
    foreach ($rooms as $room) {
        if ((string)$room['roomNumber'] === (string)$roomNumber) {
            return $room;
        }
    }
    return null;
}

function setRoomStatus($roomNumber, $status) {
    $room = findRoomByNumber($roomNumber);
    if (!$room) return;
    db('rooms')->update([
        'where' => ['id' => $room['id']],
        'data'  => ['status' => $status, 'updatedAt' => date('c')]
    ]);
}

function todayDate() {
    return getActiveBusinessDate();
}

function addDays($dateStr, $days) {
    $dt = new DateTime($dateStr);
    $dt->modify("+{$days} days");
    return $dt->format('Y-m-d');
}

function applyStatusTransition($request, $newStatus, $input = []) {
    $current = $request['status'] ?? '';
    $data = ['status' => $newStatus, 'updatedAt' => date('c')];

    if (isset($input['reviewNote'])) {
        $data['reviewNote'] = $input['reviewNote'];
    }

    // Direct check-in or approval
    if ($newStatus === 'CHECKIN_APPROVED' && in_array($current, ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'pending', ''], true)) {
        $room = findRoomByNumber($request['roomNumber'] ?? '');
        $pricePerNight = floatval($room['price'] ?? 0);
        $stayDuration = (int)($request['stayDuration'] ?? 1);
        $checkIn = date('c');
        $checkOut = addDays($checkIn, $stayDuration);

        $data['checkIn'] = $checkIn;
        $data['checkOut'] = $checkOut;
        $data['pricePerNight'] = $pricePerNight;
        $data['roomPrice'] = $pricePerNight * $stayDuration;
        $data['approvedAt'] = date('c');

        if ($request['roomNumber'] ?? '') {
            setRoomStatus($request['roomNumber'], 'occupied');
        }
    }
    // Re-check-in (returning checked-out guest)
    elseif ($newStatus === 'CHECKIN_APPROVED' && in_array($current, ['CHECKED_OUT', 'CHECKOUT_APPROVED', 'check_out', 'checked-out'], true)) {
        $roomNumber = trim((string)($input['roomNumber'] ?? $request['roomNumber'] ?? ''));
        $room = findRoomByNumber($roomNumber);
        $pricePerNight = floatval($room['price'] ?? $request['pricePerNight'] ?? 0);
        $stayDuration = max(1, (int)($input['stayDuration'] ?? $request['stayDuration'] ?? 1));
        $guests = max(1, (int)($input['guests'] ?? $request['guests'] ?? 1));
        $checkIn = date('c');
        $checkOut = addDays($checkIn, $stayDuration);

        $data['roomNumber'] = $roomNumber;
        $data['stayDuration'] = $stayDuration;
        $data['guests'] = $guests;
        $data['checkIn'] = $checkIn;
        $data['checkOut'] = $checkOut;
        $data['pricePerNight'] = $pricePerNight;
        $data['roomPrice'] = $pricePerNight * $stayDuration;
        $data['approvedAt'] = date('c');
        $data['checkedOutAt'] = null;

        $profileFields = ['guestName', 'phone', 'faydaId', 'paymentMethod', 'receiptNumber', 'transactionUrl', 'notes'];
        foreach ($profileFields as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }
        if (!empty(trim((string)($input['guestName'] ?? '')))) {
            $data['guestName'] = trim((string)$input['guestName']);
        }
        foreach (['profilePhoto', 'idPhotoFront', 'idPhotoBack'] as $photoField) {
            if (!empty($input[$photoField])) {
                $data[$photoField] = $input[$photoField];
            }
        }

        if ($roomNumber) {
            setRoomStatus($roomNumber, 'occupied');
        }
    }
    // Direct extension (staying in CHECKIN_APPROVED)
    elseif ($newStatus === 'CHECKIN_APPROVED' && $current === 'CHECKIN_APPROVED') {
        $extraDays = (int)($input['extraDays'] ?? 0);
        if ($extraDays > 0) {
            $pricePerNight = floatval($request['pricePerNight'] ?? 0);
            if (!$pricePerNight) {
                $room = findRoomByNumber($request['roomNumber'] ?? '');
                $pricePerNight = floatval($room['price'] ?? 0);
                $data['pricePerNight'] = $pricePerNight;
            }
            $currentCheckOut = $request['checkOut'] ?? todayDate();
            $data['checkOut'] = addDays($currentCheckOut, $extraDays);
            $data['stayDuration'] = (int)($request['stayDuration'] ?? 1) + $extraDays;
            $data['roomPrice'] = floatval($request['roomPrice'] ?? 0) + ($pricePerNight * $extraDays);
        }
    }
    // Direct checkout
    elseif ($newStatus === 'CHECKED_OUT' && (in_array($current, ['CHECKIN_APPROVED', 'CHECKOUT_PENDING'], true))) {
        $data['checkOut'] = date('c');
        $data['checkedOutAt'] = date('c');
        if ($request['roomNumber'] ?? '') {
            setRoomStatus($request['roomNumber'], 'available');
        }
    }

    return $data;
}

requireAuth(['admin', 'reception', 'receptionist'], ['services:view', 'reception:access']);

$method = $_SERVER['REQUEST_METHOD'];
$userRole = $_SESSION['role'] ?? '';

try {
    $db = db('receptionRequests');

    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $request = $db->findUnique(['where' => ['id' => $id]]);
            sendJson(['status' => 'success', 'data' => $request]);
        }

        $limit = (int)($_GET['limit'] ?? 500);
        $period = $_GET['period'] ?? 'all';
        
        $requests = $db->findMany([
            'where' => ['isDeleted' => false], 
            'orderBy' => ['createdAt' => 'desc'], 
            'exclude' => ['profilePhoto', 'idPhotoFront', 'idPhotoBack']
        ]);

        if ($period !== 'all') {
            $range = resolveReportDateRange($period, $_GET['startDate'] ?? null, $_GET['endDate'] ?? null);
            $start = $range['start'];
            $end = $range['end'];
            
            $requests = array_filter($requests, function($req) use ($start, $end) {
                // For reception, attribution depends on checkIn (business date label)
                // but we also fall back to approvedAt/updatedAt for pending items
                $dateStr = !empty($req['checkIn']) ? $req['checkIn'] : (!empty($req['approvedAt']) ? $req['approvedAt'] : ($req['updatedAt'] ?? null));
                return isWithinReportRange($dateStr, $start, $end);
            });
        }

        $limited = array_slice(array_values($requests), 0, $limit);
        sendJson(['status' => 'success', 'data' => $limited, 'total' => count($requests)]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['guestName'])) throw new Exception("Guest name is required");

        $id = bin2hex(random_bytes(16));
        $request = [
            'id'             => $id,
            'guestName'      => $input['guestName'],
            'phone'          => $input['phone'] ?? '',
            'faydaId'        => $input['faydaId'] ?? '',
            'roomNumber'     => $input['roomNumber'] ?? '',
            'guests'         => (int)($input['guests'] ?? 1),
            'stayDuration'   => (int)($input['stayDuration'] ?? 1),
            'paymentMethod'  => $input['paymentMethod'] ?? 'CASH',
            'receiptNumber'  => $input['receiptNumber'] ?? '',
            'transactionUrl' => $input['transactionUrl'] ?? '',
            'notes'          => $input['notes'] ?? '',
            'profilePhoto'   => $input['profilePhoto'] ?? '',
            'idPhotoFront'   => $input['idPhotoFront'] ?? '',
            'idPhotoBack'    => $input['idPhotoBack'] ?? '',
            'status'         => 'PENDING_APPROVAL', // Temporary for applyStatusTransition
            'inquiryType'    => $input['inquiryType'] ?? 'WALK_IN',
            'createdAt'      => date('c'),
            'isDeleted'      => false
        ];

        // Apply direct check-in logic
        $transitionData = applyStatusTransition($request, 'CHECKIN_APPROVED', $input);
        $finalData = array_merge($request, $transitionData);

        $db->create(['data' => $finalData]);
        sendJson(['status' => 'success', 'id' => $id, 'data' => $finalData]);
    }
    elseif ($method === 'PUT' && isset($_GET['action']) && $_GET['action'] === 'checkout-all') {
        $activeStatuses = ['CHECKIN_APPROVED', 'CHECKOUT_PENDING', 'check_in', 'ACTIVE', 'guests', 'staying'];
        $requests = $db->findMany(['where' => [
            'isDeleted' => false,
            'status' => ['in' => $activeStatuses]
        ]]);

        $checkedOut = 0;
        foreach ($requests as $request) {
            $data = applyStatusTransition($request, 'CHECKED_OUT', []);
            $db->update(['where' => ['id' => $request['id']], 'data' => $data]);
            $checkedOut++;
        }

        sendJson([
            'status' => 'success',
            'message' => $checkedOut > 0
                ? "Checked out {$checkedOut} guest(s)"
                : 'No checked-in guests to check out',
            'count' => $checkedOut
        ]);
    }
    elseif ($method === 'PUT') {
        $id = $_GET['id'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$id) throw new Exception("ID required");

        $request = $db->findUnique(['where' => ['id' => $id]]);
        if (!$request) throw new Exception("Request not found");

        $newStatus = $input['status'] ?? null;
        if (!$newStatus) throw new Exception("Status required");

        $isAdmin = $userRole === 'admin';
        $current = $request['status'] ?? '';
        
        $allowedActions = ['CHECKIN_APPROVED', 'CHECKED_OUT'];
        if (!in_array($newStatus, $allowedActions, true)) {
            throw new Exception("Invalid status transition: " . $newStatus);
        }

        if ($newStatus === 'CHECKED_OUT' && (in_array($current, ['CHECKED_OUT'], true))) {
            throw new Exception("Guest is not currently checked in");
        }

        $checkedOutStatuses = ['CHECKED_OUT', 'CHECKOUT_APPROVED', 'check_out', 'checked-out'];
        if ($newStatus === 'CHECKIN_APPROVED' && in_array($current, $checkedOutStatuses, true)) {
            $roomNumber = trim((string)($input['roomNumber'] ?? $request['roomNumber'] ?? ''));
            if (!$roomNumber) throw new Exception("Room number is required for re-check-in");

            $guestName = trim((string)($input['guestName'] ?? $request['guestName'] ?? ''));
            if (!$guestName) throw new Exception("Guest name is required");

            $room = findRoomByNumber($roomNumber);
            if (!$room) throw new Exception("Room {$roomNumber} not found");

            $heldByOther = $db->findFirst(['where' => [
                'isDeleted' => false,
                'roomNumber' => $roomNumber,
                'status' => ['in' => ['CHECKIN_APPROVED', 'CHECKOUT_PENDING', 'EXTEND_PENDING', 'check_in', 'ACTIVE', 'guests', 'staying']],
                'id' => ['not' => $id]
            ]]);
            if ($heldByOther) {
                throw new Exception("Room {$roomNumber} is already occupied");
            }
            if (($room['status'] ?? '') === 'occupied' && (string)($request['roomNumber'] ?? '') !== $roomNumber) {
                throw new Exception("Room {$roomNumber} is not available");
            }
        }

        $data = applyStatusTransition($request, $newStatus, $input);
        $db->update(['where' => ['id' => $id], 'data' => $data]);
        sendJson(['status' => 'success', 'data' => array_merge($request, $data)]);
    }
    elseif ($method === 'DELETE') {
        if (isset($_GET['action']) && $_GET['action'] === 'wipe') {
            requireAuth(['admin']);
            
            // Release rooms for all active requests before wiping
            $activeStatuses = ['CHECKIN_APPROVED', 'EXTEND_PENDING', 'CHECKOUT_PENDING'];
            $activeRequests = $db->findMany(['where' => [
                'status' => ['in' => $activeStatuses],
                'isDeleted' => false
            ]]);
            foreach ($activeRequests as $req) {
                if ($req['roomNumber'] ?? '') {
                    setRoomStatus($req['roomNumber'], 'available');
                }
            }

            $db->deleteMany(['where' => []]);
            sendJson(['status' => 'success', 'message' => 'All requests cleared and rooms released']);
        }

        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception("ID required");

        // Fetch request details before deletion to check if room needs releasing
        $request = $db->findUnique(['where' => ['id' => $id]]);
        if ($request && !($request['isDeleted'] ?? false)) {
            $activeStatuses = ['CHECKIN_APPROVED', 'EXTEND_PENDING', 'CHECKOUT_PENDING'];
            if (in_array($request['status'], $activeStatuses) && !empty($request['roomNumber'])) {
                setRoomStatus($request['roomNumber'], 'available');
            }
        }

        $db->update(['where' => ['id' => $id], 'data' => ['isDeleted' => true]]);
        sendJson(['status' => 'success']);
    }
    else {
        sendJson(['message' => 'Method Not Allowed'], 405);
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
