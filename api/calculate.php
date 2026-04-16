<?php
// api/calculate.php

// Set proper headers for REST API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow CORS if needed from frontend
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Accept pre-flight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Predefined appliance data arrays
$electric_appliances = [
    "fan" => 75,
    "ac" => 1500,
    "tv" => 100,
    "fridge" => 200,
    "washing_machine" => 500
];

$water_appliances = [
    "shower" => 10,
    "tap" => 6,
    "washing_machine" => 50
];

// Flat Rates
define('ELEC_RATE', 5.0); // 5 INR per unit (kWh)
define('WATER_RATE', 0.05); // 0.05 INR per liter

/**
 * Helper function to calculate resource consumption for a single appliance
 * Includes enhancement for monthly calculation using "days"
 */
function calculate_appliance($name, $time, $unit, $days) {
    global $electric_appliances, $water_appliances;

    // Normalize input
    $name = strtolower(trim($name));
    $unit = strtolower(trim($unit));

    if (!isset($electric_appliances[$name]) && !isset($water_appliances[$name])) {
        throw new Exception("Unknown appliance: " . $name);
    }

    // Standardize time for our formulas
    $time_hours = ($unit === 'minutes') ? ($time / 60) : $time;
    // Assuming water flow rates are usually per minute
    $time_minutes = ($unit === 'hours') ? ($time * 60) : $time; 

    $result = [
        "appliance" => $name,
        "type" => "",
        "consumption" => [],
        "estimated_cost" => 0
    ];

    $total_cost = 0;
    $is_electric = isset($electric_appliances[$name]);
    $is_water = isset($water_appliances[$name]);

    // Determine the type
    if ($is_electric && $is_water) {
        $result["type"] = "both";
    } elseif ($is_electric) {
        $result["type"] = "electric";
    } else {
        $result["type"] = "water";
    }

    // Calculate Electricity
    if ($is_electric) {
        $power_watts = $electric_appliances[$name];
        // Formula: Energy (kWh) = (Power × Time_in_hours) / 1000
        $energy_kwh = (($power_watts * $time_hours) / 1000) * $days;
        $cost = $energy_kwh * ELEC_RATE;

        $result["consumption"]["energy_kwh"] = round($energy_kwh, 2);
        $total_cost += $cost;
    }

    // Calculate Water
    if ($is_water) {
        $flow_rate = $water_appliances[$name];
        // Formula: Water Used (liters) = Flow Rate × Time_in_minutes
        $water_liters = ($flow_rate * $time_minutes) * $days;
        $cost = $water_liters * WATER_RATE;

        $result["consumption"]["water_liters"] = round($water_liters, 2);
        $total_cost += $cost;
    }

    $result["estimated_cost"] = round($total_cost, 2);
    
    // Include days calculated if it's a monthly (or multi-day) forecast
    if ($days > 1) {
        $result["days_calculated"] = $days;
    }

    return $result;
}

try {
    // 1. Input Validation: Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST requests are allowed. Please send data using JSON format.");
    }

    // 2. Read JSON Input
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);

    // 3. Error handling: Validate JSON logic
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        throw new Exception("Invalid JSON input body.");
    }

    // 4. Checking if request contains multiple appliances (Enhancement)
    if (isset($data['appliances']) && is_array($data['appliances'])) {
        
        $response = [
            "results" => [],
            "total_consumption" => [
                "energy_kwh" => 0,
                "water_liters" => 0
            ],
            "total_estimated_cost" => 0
        ];

        foreach ($data['appliances'] as $item) {
            $name = $item['appliance'] ?? null;
            $time = $item['time'] ?? 0;
            $unit = $item['unit'] ?? 'hours';
            $days = $item['days'] ?? 1;

            if (!$name || !is_numeric($time) || $time <= 0) {
                 throw new Exception("Each appliance must have an 'appliance' name and a positive 'time' value.");
            }

            // Utilize function for structuring
            $item_result = calculate_appliance($name, $time, $unit, $days);
            $response["results"][] = $item_result;
            
            // Increment totals
            if (isset($item_result["consumption"]["energy_kwh"])) {
                $response["total_consumption"]["energy_kwh"] += $item_result["consumption"]["energy_kwh"];
            }
            if (isset($item_result["consumption"]["water_liters"])) {
                $response["total_consumption"]["water_liters"] += $item_result["consumption"]["water_liters"];
            }
            $response["total_estimated_cost"] += $item_result["estimated_cost"];
        }

        // Format totals rounding
        $response["total_estimated_cost"] = round($response["total_estimated_cost"], 2);
        $response["total_consumption"]["energy_kwh"] = round($response["total_consumption"]["energy_kwh"], 2);
        $response["total_consumption"]["water_liters"] = round($response["total_consumption"]["water_liters"], 2);

        // Echo response
        echo json_encode($response, JSON_PRETTY_PRINT);

    } else {
        // Standard Case: Single Appliance Request
        $name = $data['appliance'] ?? null;
        $time = $data['time'] ?? 0;
        $unit = $data['unit'] ?? 'hours'; // defaults to hours
        $days = $data['days'] ?? 1;       // defaults to 1 for daily calculation

        // Require mandatory fields for single calculation
        if (!$name || !is_numeric($time) || $time <= 0) {
             throw new Exception("Missing 'appliance' name or a positive 'time' value.");
        }

        // Calculate and echo
        $result = calculate_appliance($name, $time, $unit, $days);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    // 5. Error Handling Response Layer
    http_response_code(400); // 400 Bad Request
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
