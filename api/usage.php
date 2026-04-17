<?php
// api/usage.php — Unified usage report endpoint
ob_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

ini_set('display_errors', 0);
error_reporting(E_ALL);

function log_api_error($message)
{
    file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND);
}

try {
    include_once "../db.php";
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed.");
    }

    session_start();

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        ob_end_flush();
        exit();
    }

    // Resolve user_id from session username if not set
    if (!isset($_SESSION['user_id'])) {
        $username = $_SESSION['user'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt)
            throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row) {
            $_SESSION['user_id'] = $row['id'];
        } else {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "User session invalid."]);
            ob_end_flush();
            exit();
        }
        $stmt->close();
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = (int) $_SESSION['user_id'];
    $response = ["status" => "error", "message" => "Unknown error."];

    require_once __DIR__ . '/alert_engine.php';

    // ─────────────────────────────────────────────────────────────────────
    // GET  — Usage Report (summary, device-wise, hourly, recent logs)
    // ─────────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $type = $_GET['type'] ?? 'summary';

        if ($type === 'summary') {

            // 1. Per-resource totals
            $stmt = $conn->prepare(
                "SELECT d.resource_type, SUM(u.consumption) AS total
                 FROM device_usage u
                 JOIN devices d ON u.device_id = d.id
                 WHERE u.user_id = ?
                 GROUP BY d.resource_type"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $resource_summary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // 2. Device-wise totals
            $stmt = $conn->prepare(
                "SELECT d.name AS device_name, d.resource_type, d.unit, SUM(u.consumption) AS total
                 FROM device_usage u
                 JOIN devices d ON u.device_id = d.id
                 WHERE u.user_id = ?
                 GROUP BY d.id
                 ORDER BY total DESC"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $device_wise = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // 3. Hourly breakdown (last 48 hour-slots across resource types)
            $stmt = $conn->prepare(
                "SELECT DATE_FORMAT(u.start_time, '%Y-%m-%d %H:00:00') AS hour,
                        d.resource_type,
                        SUM(u.consumption) AS total
                 FROM device_usage u
                 JOIN devices d ON u.device_id = d.id
                 WHERE u.user_id = ?
                 GROUP BY hour, d.resource_type
                 ORDER BY hour ASC
                 LIMIT 96"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $hourly_breakdown = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // 4. Recent individual logs (for edit/delete table)
            $stmt = $conn->prepare(
                "SELECT u.id, u.device_id, d.name AS device_name,
                        d.resource_type, u.start_time, u.end_time,
                        u.duration, u.consumption
                 FROM device_usage u
                 JOIN devices d ON u.device_id = d.id
                 WHERE u.user_id = ?
                 ORDER BY u.start_time DESC
                 LIMIT 15"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $recent_logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Debug: log count so we can trace issues
            log_api_error("GET summary for user $user_id | resource_summary=" . count($resource_summary) .
                " device_wise=" . count($device_wise) .
                " hourly=" . count($hourly_breakdown) .
                " recent=" . count($recent_logs));

            $response = [
                "status" => "success",
                "data" => [
                    "resource_summary" => $resource_summary,
                    "device_wise" => $device_wise,
                    "hourly_breakdown" => $hourly_breakdown,
                    "recent_logs" => $recent_logs
                ]
            ];
        } else {
            $response = ["status" => "error", "message" => "Unknown report type."];
        }

        // ─────────────────────────────────────────────────────────────────────
        // POST — Log new usage entry
        // ─────────────────────────────────────────────────────────────────────
    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $device_id = intval($input['device_id'] ?? 0);
        $start_time = trim($input['start_time'] ?? '');
        $end_time = trim($input['end_time'] ?? '');

        if (!$device_id || !$start_time || !$end_time) {
            $response = ["status" => "error", "message" => "Please provide device, start time, and end time."];
        } else {
            $stmt = $conn->prepare("SELECT consumption_rate, unit, resource_type, name FROM devices WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $device_id, $user_id);
            $stmt->execute();
            $dev = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$dev)
                throw new Exception("Device not found or does not belong to you.");

            $rate = floatval($dev['consumption_rate']);
            $unit = strtolower(trim($dev['unit'] ?? ''));
            $resource_type = strtolower(trim($dev['resource_type']));
            $device_name = $dev['name'];

            $start = new DateTime($start_time);
            $end = new DateTime($end_time);

            $duration_seconds = $end->getTimestamp() - $start->getTimestamp();
            if ($duration_seconds <= 0)
                throw new Exception("End time must be after start time.");

            $duration_hours = $duration_seconds / 3600;

            // Handle unit-based logic explicitly
            if ($resource_type === 'electricity' && $unit === 'watts') {
                $consumption = ($rate * $duration_hours) / 1000;
            } else {
                $consumption = $rate * $duration_hours;
            }

            // Log calculation for debugging purposes
            log_api_error("[POST Log] Device: $device_name, Power: $rate $unit, Start: $start_time, End: $end_time, Duration(h): $duration_hours, Calc Consumption: $consumption");

            $stmt = $conn->prepare(
                "INSERT INTO device_usage (user_id, device_id, start_time, end_time, duration, consumption)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iissdd", $user_id, $device_id, $start_time, $end_time, $duration_hours, $consumption);

            if ($stmt->execute()) {
                $responseData = [
                    "id" => $stmt->insert_id,
                    "device_name" => $device_name,
                    "resource_type" => $resource_type,
                    "duration_hours" => round($duration_hours, 4),
                    "consumption" => round($consumption, 4)
                ];
                if ($resource_type === 'electricity') {
                    $responseData['consumption_kwh'] = round($consumption, 4);
                }

                check_limit_and_send_alert($conn, $user_id, $resource_type);

                $response = [
                    "status" => "success",
                    "message" => "Usage logged successfully!",
                    "data" => $responseData
                ];
            } else {
                throw new Exception("Insert failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // ─────────────────────────────────────────────────────────────────────
        // PUT — Update existing usage log
        // ─────────────────────────────────────────────────────────────────────
    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);
        $device_id = intval($input['device_id'] ?? 0);
        $start_time = trim($input['start_time'] ?? '');
        $end_time = trim($input['end_time'] ?? '');

        if (!$id || !$device_id || !$start_time || !$end_time) {
            $response = ["status" => "error", "message" => "Missing required fields for update."];
        } else {
            $stmt = $conn->prepare("SELECT consumption_rate, unit, resource_type, name FROM devices WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $device_id, $user_id);
            $stmt->execute();
            $dev = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$dev)
                throw new Exception("Device not found.");

            $rate = floatval($dev['consumption_rate']);
            $unit = strtolower(trim($dev['unit'] ?? ''));
            $resource_type = strtolower(trim($dev['resource_type']));
            $device_name = $dev['name'];

            $start = new DateTime($start_time);
            $end = new DateTime($end_time);

            $duration_seconds = $end->getTimestamp() - $start->getTimestamp();
            if ($duration_seconds <= 0)
                throw new Exception("End time must be after start time.");

            $duration_hours = $duration_seconds / 3600;

            // Handle unit-based logic explicitly
            if ($resource_type === 'electricity' && $unit === 'watts') {
                $consumption = ($rate * $duration_hours) / 1000;
            } else {
                $consumption = $rate * $duration_hours;
            }

            // Log calculation for debugging purposes
            log_api_error("[PUT Log] Device: $device_name, Power: $rate $unit, Start: $start_time, End: $end_time, Duration(h): $duration_hours, Calc Consumption: $consumption");

            $stmt = $conn->prepare(
                "UPDATE device_usage
                 SET device_id=?, start_time=?, end_time=?, duration=?, consumption=?
                 WHERE id=? AND user_id=?"
            );
            $stmt->bind_param("issddii", $device_id, $start_time, $end_time, $duration_hours, $consumption, $id, $user_id);
            if ($stmt->execute()) {
                $responseData = [
                    "device_name" => $device_name,
                    "resource_type" => $resource_type,
                    "duration_hours" => round($duration_hours, 4),
                    "consumption" => round($consumption, 4)
                ];
                if ($resource_type === 'electricity') {
                    $responseData['consumption_kwh'] = round($consumption, 4);
                }

                check_limit_and_send_alert($conn, $user_id, $resource_type);

                $response = [
                    "status" => "success",
                    "message" => "Record updated successfully!",
                    "data" => $responseData
                ];
            } else {
                throw new Exception("Update failed: " . $stmt->error);
            }
            $stmt->close();
        }

        // ─────────────────────────────────────────────────────────────────────
        // DELETE — Remove a usage log
        // ─────────────────────────────────────────────────────────────────────
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);

        if (!$id) {
            $response = ["status" => "error", "message" => "Missing log ID."];
        } else {
            $stmt = $conn->prepare("DELETE FROM device_usage WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Record deleted!"];
            } else {
                throw new Exception("Delete failed: " . $stmt->error);
            }
            $stmt->close();
        }

    } else {
        http_response_code(405);
        $response = ["status" => "error", "message" => "Method not allowed."];
    }

    ob_end_clean();
    echo json_encode($response);

} catch (Throwable $t) {
    ob_end_clean();
    log_api_error($t->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Server Error: " . $t->getMessage()]);
}
?>