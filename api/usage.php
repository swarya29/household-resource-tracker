<?php
// api/usage.php
ob_start(); // Buffer all output
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Disable display_errors to prevent HTML from breaking JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

function log_error($message) {
    file_put_contents(__DIR__ . "/api_errors.log", "[" . date("Y-m-d H:i:s") . "] " . $message . "\n", FILE_APPEND);
}

try {
    // Include database connection
    include_once "../db.php";
    if (!isset($conn) || !$conn) {
        throw new Exception("Database connection failed or variable not found.");
    }

    session_start();
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        ob_end_flush();
        exit();
    }

    // Ensure user_id is available in the session
    if (!isset($_SESSION['user_id'])) {
        $username = $_SESSION['user'];
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
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
    $user_id = $_SESSION['user_id'];

    $response = ["status" => "error", "message" => "Unknown error"];

    switch($method) {
        case 'GET':
            $stmt = $conn->prepare("SELECT id, date, water_usage, energy_usage FROM usage_data WHERE user_id = ? ORDER BY date ASC");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $response = ["status" => "success", "data" => $data];
            $stmt->close();
            break;

        case 'POST':
            $input = json_decode(file_get_contents("php://input"), true);
            $date = trim($input['date'] ?? '');
            $water = $input['water'] ?? '';
            $energy = $input['energy'] ?? '';

            if (empty($date) || !is_numeric($water) || !is_numeric($energy)) {
                http_response_code(400);
                $response = ["status" => "error", "message" => "Please fill all fields accurately."];
                break;
            }

            $stmt = $conn->prepare("INSERT INTO usage_data (user_id, date, water_usage, energy_usage) VALUES (?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("isdd", $user_id, $date, $water, $energy);
            
            if ($stmt->execute()) {
                $response = [
                    "status" => "success", 
                    "message" => "Data saved successfully!",
                    "data" => ["id" => $stmt->insert_id, "date" => $date, "water_usage" => $water, "energy_usage" => $energy]
                ];
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            break;

        case 'PUT':
            $input = json_decode(file_get_contents("php://input"), true);
            $id = $input['id'] ?? '';
            $date = trim($input['date'] ?? '');
            $water = $input['water'] ?? '';
            $energy = $input['energy'] ?? '';

            if (empty($id) || empty($date) || !is_numeric($water) || !is_numeric($energy)) {
                http_response_code(400);
                $response = ["status" => "error", "message" => "Invalid input data."];
                break;
            }

            $stmt = $conn->prepare("UPDATE usage_data SET date=?, water_usage=?, energy_usage=? WHERE id=? AND user_id=?");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("sddii", $date, $water, $energy, $id, $user_id);
            
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Data updated successfully!"];
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents("php://input"), true);
            $id = $input['id'] ?? '';
            if (empty($id)) throw new Exception("Missing ID");

            $stmt = $conn->prepare("DELETE FROM usage_data WHERE id=? AND user_id=?");
            if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
            $stmt->bind_param("ii", $id, $user_id);
            if ($stmt->execute()) {
                $response = ["status" => "success", "message" => "Data deleted successfully!"];
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            http_response_code(405);
            $response = ["status" => "error", "message" => "Method not allowed."];
            break;
    }
    
    ob_end_clean(); // Discard any garbage output
    echo json_encode($response);

} catch (Throwable $t) {
    ob_end_clean(); // Discard any garbage output
    log_error($t->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error: " . $t->getMessage()]);
}
?>
