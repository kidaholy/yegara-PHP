<?php
/**
 * API for Reception Lifecycle management
 */
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../includes/JsonDB.php';

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
    return date('Y-m-d');
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

    // Admin approves check-in
    if ($newStatus === 'CHECKIN_APPROVED' && in_array($current, ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'pending'], true)) {
        $room = findRoomByNumber($request['roomNumber'] ?? '');
        $pricePerNight = floatval($room['price'] ?? 0);
        $stayDuration = (int)($request['stayDuration'] ?? 1);
        $checkIn = todayDate();
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
    // Admin approves stay extension
    elseif ($newStatus === 'CHECKIN_APPROVED' && $current === 'EXTEND_PENDING') {
        $extraDays = (int)($request['pendingExtraDays'] ?? $input['extraDays'] ?? 0);
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
            $data['pendingExtraDays'] = null;
        }
    }
    // Admin approves checkout
    elseif ($newStatus === 'CHECKED_OUT' && $current === 'CHECKOUT_PENDING') {
        $data['checkOut'] = todayDate();
        $data['checkedOutAt'] = date('c');
        if ($request['roomNumber'] ?? '') {
            setRoomStatus($request['roomNumber'], 'available');
        }
    }
    // Admin denies checkout — guest stays checked in
    elseif ($newStatus === 'CHECKIN_APPROVED' && $current === 'CHECKOUT_PENDING') {
        // keep existing dates
    }
    // Admin denies extension — guest stays checked in, clear pending extra days
    elseif ($newStatus === 'CHECKIN_APPROVED' && $current === 'EXTEND_PENDING') {
        $data['pendingExtraDays'] = null;
    }
    // Admin rejects check-in
    elseif ($newStatus === 'REJECTED') {
        if (in_array($current, ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'pending'], true)) {
            // no room to free yet
        }
    }
    // Reception requests checkout
    elseif ($newStatus === 'CHECKOUT_PENDING' && $current === 'CHECKIN_APPROVED') {
        $data['checkoutRequestedAt'] = date('c');
    }
    // Reception requests extension
    elseif ($newStatus === 'EXTEND_PENDING' && $current === 'CHECKIN_APPROVED') {
        $extraDays = max(1, (int)($input['extraDays'] ?? 1));
        $data['pendingExtraDays'] = $extraDays;
        $data['extendRequestedAt'] = date('c');
    }

    return $data;
}

requireAuth(['admin', 'reception', 'receptionist']);

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
        $requests = $db->findMany(['where' => ['isDeleted' => false], 'orderBy' => ['createdAt' => 'desc'], 'take' => $limit]);

        $minimal = array_map(function ($r) {
            unset($r['idPhotoFront'], $r['idPhotoBack']);
            return $r;
        }, $requests);

        sendJson(['status' => 'success', 'data' => $minimal, 'total' => count($minimal)]);
    }
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['guestName'])) throw new Exception("Guest name is required");

        $room = findRoomByNumber($input['roomNumber'] ?? '');
        $pricePerNight = floatval($room['price'] ?? 0);
        $stayDuration = (int)($input['stayDuration'] ?? 1);

        $id = bin2hex(random_bytes(16));
        $db->create(['data' => [
            'id'             => $id,
            'guestName'      => $input['guestName'],
            'phone'          => $input['phone'] ?? '',
            'faydaId'        => $input['faydaId'] ?? '',
            'roomNumber'     => $input['roomNumber'] ?? '',
            'guests'         => (int)($input['guests'] ?? 1),
            'stayDuration'   => $stayDuration,
            'pricePerNight'  => $pricePerNight,
            'paymentMethod'  => $input['paymentMethod'] ?? 'CASH',
            'receiptNumber'  => $input['receiptNumber'] ?? '',
            'transactionUrl' => $input['transactionUrl'] ?? '',
            'notes'          => $input['notes'] ?? '',
            'profilePhoto'   => $input['profilePhoto'] ?? '',
            'idPhotoFront'   => $input['idPhotoFront'] ?? '',
            'idPhotoBack'    => $input['idPhotoBack'] ?? '',
            'status'         => 'PENDING_APPROVAL',
            'inquiryType'    => $input['inquiryType'] ?? 'WALK_IN',
            'createdAt'      => date('c'),
            'isDeleted'      => false
        ]]);
        sendJson(['status' => 'success', 'id' => $id]);
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
        $receptionActions = ['CHECKOUT_PENDING', 'EXTEND_PENDING'];
        $adminActions = ['CHECKIN_APPROVED', 'CHECKED_OUT', 'REJECTED'];

        if (in_array($newStatus, $receptionActions, true)) {
            if (!$isAdmin && $current !== 'CHECKIN_APPROVED') {
                throw new Exception("Only checked-in guests can request checkout or extension");
            }
            if ($current !== 'CHECKIN_APPROVED') {
                throw new Exception("Invalid status for this request");
            }
        } elseif (in_array($newStatus, $adminActions, true) && !$isAdmin) {
            throw new Exception("Admin approval required");
        } elseif (!in_array($newStatus, array_merge($receptionActions, $adminActions), true)) {
            throw new Exception("Invalid status transition");
        }

        if ($newStatus === 'CHECKIN_APPROVED' && in_array($current, ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'pending'], true) && !$isAdmin) {
            throw new Exception("Admin approval required");
        }
        if ($newStatus === 'CHECKED_OUT' && $current !== 'CHECKOUT_PENDING') {
            throw new Exception("Checkout must be requested first");
        }
        if ($newStatus === 'REJECTED' && !in_array($current, ['PENDING_APPROVAL', 'CHECKIN_PENDING', 'pending'], true)) {
            throw new Exception("Only pending check-ins can be rejected");
        }

        $data = applyStatusTransition($request, $newStatus, $input);
        $db->update(['where' => ['id' => $id], 'data' => $data]);
        sendJson(['status' => 'success', 'data' => array_merge($request, $data)]);
    }
    elseif ($method === 'DELETE') {
        if (isset($_GET['action']) && $_GET['action'] === 'wipe') {
            requireAuth(['admin']);
            $db->deleteMany(['where' => []]);
            sendJson(['status' => 'success', 'message' => 'All requests cleared']);
        }

        $id = $_GET['id'] ?? '';
        if (!$id) throw new Exception("ID required");
        $db->update(['where' => ['id' => $id], 'data' => ['isDeleted' => true]]);
        sendJson(['status' => 'success']);
    }
    else {
        sendJson(['message' => 'Method Not Allowed'], 405);
    }
} catch (Exception $e) {
    sendJson(['status' => 'error', 'message' => $e->getMessage()], 500);
}
