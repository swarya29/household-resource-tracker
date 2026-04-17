<?php
// api/endpoints/logs.php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    include_once "../../db.php";
    if (!isset($conn) || !$conn) throw new Exception("Database connection failed.");

    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        ob_end_flush();
        exit();
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = $_SESSION['user_id'];
    $response = ["status" => "error", "message" => "Unknown error."];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $device_id = intval($input['device_id'] ?? 0);
        $start_time = $input['start_time'] ?? '';
        $end_time = $input['end_time'] ?? '';

        if (!$device_id || !$start_time || !$end_time) {
            http_response_code(400);
            $response = ["status" => "error", "message" => "Please provide device, start time, and end time."];
        } else {
            // Verify device belongs to user and get consumption rate & unit
            $stmt = $conn->prepare("SELECT consumption_rate, unit FROM devices WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $device_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $rate = $row['consumption_rate'];
                $unit = strtolower($row['unit'] ?? '');
                
                // Calculate duration and consumption
                $start = new DateTime($start_time);
                $end = new DateTime($end_time);
                if ($end <= $start) throw new Exception("End time must be after start time.");
                
                $diff = $start->diff($end);
                $duration = $diff->h + ($diff->i / 60) + ($diff->s / 3600) + ($diff->days * 24);
                $consumption = ($unit === "watts") ? ($rate * $duration) / 1000 : $rate * $duration;

                $stmt_insert = $conn->prepare("INSERT INTO device_usage (user_id, device_id, start_time, end_time, duration, consumption) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("iissdd", $user_id, $device_id, $start_time, $end_time, $duration, $consumption);
                
                if ($stmt_insert->execute()) {
                    $response = ["status" => "success", "message" => "Usage logged!", "data" => ["id" => $stmt_insert->insert_id]];
                } else {
                    throw new Exception("Execute failed.");
                }
                $stmt_insert->close();
            } else {
                throw new Exception("Device not found.");
            }
            $stmt->close();
        }
    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);
        $device_id = intval($input['device_id'] ?? 0);
        $start_time = $input['start_time'] ?? '';
        $end_time = $input['end_time'] ?? '';

        if (!$id || !$device_id || !$start_time || !$end_time) {
            http_response_code(400);
            $response = ["status" => "error", "message" => "Missing required fields."];
        } else {
            // Get device info
            $stmt = $conn->prepare("SELECT consumption_rate, unit FROM devices WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $device_id, $user_id);
            $stmt->execute();
            $dev = $stmt->get_result()->fetch_assoc();
            if (!$dev) throw new Exception("Device not found.");

            $rate = $dev['consumption_rate'];
            $unit = strtolower($dev['unit'] ?? '');
            
            $start = new DateTime($start_time);
            $end = new DateTime($end_time);
            $diff = $start->diff($end);
            $duration = $diff->h + ($diff->i / 60) + ($diff->s / 3600) + ($diff->days * 24);
            $consumption = ($unit === "watts") ? ($rate * $duration) / 1000 : $rate * $duration;

            $stmt_upd = $conn->prepare("UPDATE device_usage SET device_id = ?, start_time = ?, end_time = ?, duration = ?, consumption = ? WHERE id = ? AND user_id = ?");
            $stmt_upd->bind_param("issddii", $device_id, $start_time, $end_time, $duration, $consumption, $id, $user_id);
            if ($stmt_upd->execute()) {
                $response = ["status" => "success", "message" => "Record updated!"];
            } else {
                throw new Exception("Update failed.");
            }
        }
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM device_usage WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Record deleted!"];
            } else {
                throw new Exception("Delete failed.");
            }
        }
    } elseif ($method === 'GET') {
        $report_type = $_GET['type'] ?? 'summary';

        if ($report_type === 'summary') {
            // Resource Summary
            $res_type = mysqli_query($conn, "SELECT d.resource_type, SUM(u.consumption) as total FROM device_usage u JOIN devices d ON u.device_id = d.id WHERE u.user_id = $user_id GROUP BY d.resource_type");
            $resource_usage = mysqli_fetch_all($res_type, MYSQLI_ASSOC);

            // Device Wise
            $res_device = mysqli_query($conn, "SELECT d.name, d.resource_type, d.unit, SUM(u.consumption) as total FROM device_usage u JOIN devices d ON u.device_id = d.id WHERE u.user_id = $user_id GROUP BY d.id");
            $device_wise = mysqli_fetch_all($res_device, MYSQLI_ASSOC);

            // Hourly
            $res_hourly = mysqli_query($conn, "SELECT DATE_FORMAT(start_time, '%Y-%m-%d %H:00:00') as hour, d.resource_type, SUM(consumption) as total FROM device_usage u JOIN devices d ON u.device_id = d.id WHERE u.user_id = $user_id GROUP BY hour, d.resource_type ORDER BY hour DESC LIMIT 48");
            $hourly_breakdown = mysqli_fetch_all($res_hourly, MYSQLI_ASSOC);

            // Recent Logs (New!)
            $res_recent = mysqli_query($conn, "SELECT u.id, u.device_id, d.name as device_name, u.start_time, u.end_time, u.consumption, d.resource_type FROM device_usage u JOIN devices d ON u.device_id = d.id WHERE u.user_id = $user_id ORDER BY u.start_time DESC LIMIT 10");
            $recent_logs = mysqli_fetch_all($res_recent, MYSQLI_ASSOC);

            $response = [
                "status" => "success",
                "data" => [
                    "resource_summary" => $resource_usage,
                    "device_wise" => $device_wise,
                    "hourly_breakdown" => $hourly_breakdown,
                    "recent_logs" => $recent_logs
                ]
            ];
        }
    } else {
        http_response_code(405);
        $response = ["status" => "error", "message" => "Method not allowed."];
    }

    ob_end_clean();
    echo json_encode($response);

} catch (Throwable $t) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error: " . $t->getMessage()]);
}
?>
