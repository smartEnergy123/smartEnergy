<?php

use App\Http\Controllers\SubscriptionController;

require_once __DIR__ . "/../../../vendor/autoload.php";

if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

$userId = $_SESSION['user_data']['id'];
$subsciptionController = new SubscriptionController();
$userSubscriptionData = $subsciptionController->getUserSubscriptionData($userId);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription History</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen p-4">

    <h1 class="text-2xl font-bold mb-6 text-center">User Subscription History</h1>
    <a href="/smartEnergy/client/dashboard/">
        <button class="select-plan-btn bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors duration-300 mb-5">Dashboard</button>
        </button>
    </a>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300 shadow-lg rounded-lg overflow-hidden">
            <thead class="bg-blue-500 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">S/N</th>
                    <th class="px-4 py-2 text-left">Plan Type</th>
                    <th class="px-4 py-2 text-left">Amount Paid ($)</th>
                    <th class="px-4 py-2 text-left">Quota (Wh)</th>
                    <th class="px-4 py-2 text-left">Transaction ID</th>
                    <th class="px-4 py-2 text-left">Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($userSubscriptionData)) {
                    $row_no = 1;
                    foreach ($userSubscriptionData as $index => $row) {
                        $rowClass = $index % 2 === 0 ? 'bg-white' : 'bg-gray-100';
                        echo "<tr class='{$rowClass} hover:bg-yellow-50 transition'>";
                        echo "<td class='px-4 py-2 capitalize'>" . $row_no . "</td>";
                        echo "<td class='px-4 py-2 capitalize'>" . htmlspecialchars($row['plan_type']) . "</td>";
                        echo "<td class='px-4 py-2'>" . number_format($row['amount_paid'], 2) . "</td>";
                        echo "<td class='px-4 py-2'>" . htmlspecialchars($row['quota_granted_wh']) . "</td>";
                        echo "<td class='px-4 py-2 text-sm break-all'>" . htmlspecialchars($row['transaction_id']) . "</td>";
                        echo "<td class='px-4 py-2'>" . date('Y-m-d', strtotime($row['start_date'])) . "</td>";
                        echo "</tr>";
                        $row_no++;
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center p-4'>No subscription history available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</body>

</html>
