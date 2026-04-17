<?php
// api/endpoints/devices.php
ob_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");

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

    if ($method === 'GET') {
        $stmt = $conn->prepare("SELECT id, name, resource_type, consumption_rate, unit FROM devices WHERE user_id = ? ORDER BY name ASC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $response = ["status" => "success", "data" => $data];
        $stmt->close();

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        $name = trim($input['name'] ?? '');
        $type = trim($input['resource_type'] ?? 'electricity');
        $rate = floatval($input['consumption_rate'] ?? 0);
        $unit = trim($input['unit'] ?? '');

        if (empty($name) || $rate <= 0) {
            http_response_code(400);
            $response = ["status" => "error", "message" => "Please provide a valid name and consumption rate (> 0)."];
        } else {
            $stmt = $conn->prepare("INSERT INTO devices (user_id, name, resource_type, consumption_rate, unit) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $user_id, $name, $type, $rate, $unit);
            
            if ($stmt->execute()) {
                $response = [
                    "status" => "success", 
                    "message" => "Device added successfully!",
                    "data" => ["id" => $stmt->insert_id, "name" => $name, "resource_type" => $type, "consumption_rate" => $rate, "unit" => $unit]
                ];
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $type = trim($input['resource_type'] ?? 'electricity');
        $rate = floatval($input['consumption_rate'] ?? 0);
        $unit = trim($input['unit'] ?? '');

        if (!$id || empty($name) || $rate <= 0) {
            http_response_code(400);
            $response = ["status" => "error", "message" => "Invalid data."];
        } else {
            $stmt = $conn->prepare("UPDATE devices SET name = ?, resource_type = ?, consumption_rate = ?, unit = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssdsii", $name, $type, $rate, $unit, $id, $user_id);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Device updated!"];
            } else {
                throw new Exception("Update failed.");
            }
            $stmt->close();
        }
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = intval($input['id'] ?? 0);
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM devices WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Device deleted successfully."];
            } else {
                throw new Exception("Delete failed.");
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
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error: " . $t->getMessage()]);
}
?>
