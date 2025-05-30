<?php

use App\Http\Controllers\ApplianceController;
use App\Models\DB;

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!isset($_SESSION['user_state']) && $_SESSION['user_data']['user_type'] !== 'client') {
    header('Location: /smartEnergy/login');
    exit;
}


$db = new DB();

$userId = $_SESSION['user_data']['id'];
$query = "SELECT * FROM consumption_logs WHERE user_id = :id ORDER BY timestamp DESC";
$params = [':id' => $userId];

$result = $db->fetchAllData($query, $params);

if (count($result) < 0 || empty($result)) {
    echo "No Consumption data was found...";
} else {
    $userConsumptionLog = $result;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Consumption Log</title>
    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body>
    <a href="/smartEnergy/client/dashboard/">
        <button class="select-plan-btn bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors duration-300 mb-5">Dashboard</button>
        </button>
    </a>
    <h1 class="text-2xl font-bold mb-6 text-center">User Consumption Log</h1>
    <section class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-gray-500 shadow-md rounded-lg">
                <thead class="text-xs text-white uppercase bg-green-600">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Timestamp</th>
                        <th scope="col" class="px-6 py-3">Current (W)</th>
                        <th scope="col" class="px-6 py-3">Daily (Wh)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- PHP loop should inject rows here -->
                    <?php $count = 1; ?>
                    <?php foreach ($userConsumptionLog as $log): ?>
                        <tr class="hover:bg-green-50 transition-all duration-300 hover:scale-[1.01] transform">
                            <td class="px-6 py-4"><?php echo $count; ?></td>
                            <td class="px-6 py-4"><?php echo $log['timestamp']; ?></td>
                            <td class="px-6 py-4"><?php echo $log['current_consumption_w']; ?> W</td>
                            <td class="px-6 py-4"><?php echo $log['daily_consumption_wh']; ?> Wh</td>
                        </tr>
                        <?php $count++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>
</body>

</html>
