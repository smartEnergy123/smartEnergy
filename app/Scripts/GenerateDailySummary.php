<?php

// app/Scripts/GenerateDailySummary.php

// --- Configuration and Autoloading ---
require_once __DIR__ . '/../Models/DB.php'; // Correct path to DB.php

use App\Models\DB;


// --- Define the date for which to generate the summary ---
// We typically summarize the PREVIOUS day's data, as it's completed.
$targetDate = (new DateTime('yesterday'))->format('Y-m-d');

// For manual testing, you can uncomment and set a specific date:
// $targetDate = '2025-05-31';

$_SESSION['success'] =  "--- Generating Daily Summary for: " . $targetDate . " ---\n";

$db = new DB();

try {
    // --- Step 2: Fetch and Aggregate Data from simulation_state ---

    // Calculate sums for solar, wind, consumption, and get the last battery level
    $aggregationQuery = "
        SELECT
            SUM(total_solar_output_wh) AS total_solar_generated_wh,
            SUM(total_wind_output_wh) AS total_wind_generated_wh,
            SUM(total_consumption_wh) AS total_consumption_wh,
            MAX(current_battery_level_wh) AS battery_level_end_wh,
            MAX(total_consumption_wh) AS peak_consumption_w,
            MAX(total_solar_output_wh + total_wind_output_wh) AS peak_production_w,
            MAX(num_houses) AS num_active_houses
        FROM
            simulation_state
        WHERE
            simulation_date = :report_date;
    ";

    // Use fetchSingleData from DB class
    $dailyData = $db->fetchSingleData($aggregationQuery, [':report_date' => $targetDate]);

    if (!$dailyData || $dailyData['total_consumption_wh'] === null) {
        $_SESSION['error'] =  "No simulation data found for " . $targetDate . ". Skipping summary generation.\n";
        exit(0);
    }

    // --- Step 3: Calculate Derived Values and Classify Day ---
    $totalSolar = (float)($dailyData['total_solar_generated_wh'] ?? 0.0);
    $totalWind = (float)($dailyData['total_wind_generated_wh'] ?? 0.0);
    $totalConsumption = (float)($dailyData['total_consumption_wh'] ?? 0.0);
    $batteryEnd = (float)($dailyData['battery_level_end_wh'] ?? 0.0);

    // Refinement for battery_level_start_wh: Fetch the battery level from the very first record of the day
    // Use fetchSingleData from DB class
    $firstRecordQuery = "
        SELECT current_battery_level_wh
        FROM simulation_state
        WHERE simulation_date = :report_date
        ORDER BY start_time DESC
        LIMIT 1;
    ";
    $firstRecord = $db->fetchSingleData($firstRecordQuery, [':report_date' => $targetDate]);
    $batteryStart = (float)($firstRecord['current_battery_level_wh'] ?? 0.0);


    $netEnergyBalance = ($totalSolar + $totalWind) - $totalConsumption / 0.5;
    // $netEnergyBalance = ($totalSolar + $totalWind) - $totalConsumption;

    // Determine day classification based on net energy balance (example logic)
    $dayClassification = 'Average';
    if ($netEnergyBalance >= 0) {
        $dayClassification = 'Good';
    } elseif ($netEnergyBalance < 0 && abs($netEnergyBalance) < $totalConsumption * 0.2) {
        $dayClassification = 'Average';
    } else {
        $dayClassification = 'Bad';
    }

    // Get the u

    $numActiveHouses = (int)($dailyData['num_active_houses'] ?? 0);

    // --- Step 4: Insert/Update into daily_simulation_summaries ---

    $insertSummaryQuery = "
        INSERT INTO daily_simulation_summaries (
            report_date,
            total_solar_generated_wh,
            total_wind_generated_wh,
            total_consumption_wh,
            net_energy_balance_wh,
            battery_level_start_wh,
            battery_level_end_wh,
            peak_consumption_w,
            peak_production_w,
            day_classification,
            num_active_houses
        ) VALUES (
            :report_date,
            :total_solar,
            :total_wind,
            :total_consumption,
            :net_balance,
            :battery_start,
            :battery_end,
            :peak_consumption,
            :peak_production,
            :day_classification,
            :num_houses
        )
        ON DUPLICATE KEY UPDATE
            total_solar_generated_wh = VALUES(total_solar_generated_wh),
            total_wind_generated_wh = VALUES(total_wind_generated_wh),
            total_consumption_wh = VALUES(total_consumption_wh),
            net_energy_balance_wh = VALUES(net_energy_balance_wh),
            battery_level_start_wh = VALUES(battery_level_start_wh),
            battery_level_end_wh = VALUES(battery_level_end_wh),
            peak_consumption_w = VALUES(peak_consumption_w),
            peak_production_w = VALUES(peak_production_w),
            day_classification = VALUES(day_classification),
            num_active_houses = VALUES(num_active_houses),
            updated_at = CURRENT_TIMESTAMP;
    ";

    $success = $db->execute($insertSummaryQuery, [
        ':report_date' => $targetDate,
        ':total_solar' => $totalSolar,
        ':total_wind' => $totalWind,
        ':total_consumption' => $totalConsumption,
        ':net_balance' => $netEnergyBalance,
        ':battery_start' => $batteryStart,
        ':battery_end' => $batteryEnd,
        ':peak_consumption' => (float)($dailyData['peak_consumption_w'] ?? 0.0),
        ':peak_production' => (float)($dailyData['peak_production_w'] ?? 0.0),
        ':day_classification' => $dayClassification,
        ':num_houses' => $numActiveHouses
    ]);

    if ($success) {
        $_SESSION['success'] =  "Daily summary for " . $targetDate . " successfully generated and inserted/updated.\n";
    } else {
        $_SESSION['error'] =  "Failed to generate or update daily summary for " . $targetDate . ".\n";
    }
} catch (PDOException $e) {
    error_log("Database error in daily summary script for " . $targetDate . ": " . $e->getMessage());
    $_SESSION['error'] =  "Error: Database operation failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    error_log("General error in daily summary script for " . $targetDate . ": " . $e->getMessage());
    $_SESSION['error'] =  "Error: An unexpected error occurred: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    $db->closeConnection();
}
