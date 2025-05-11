<?php
// This file shows current power statistics
// You can later replace these with dynamic variables or database queries
$solarOutput = 1200; // in Watts
$windOutput = 800;   // in Watts
$totalGenerated = $solarOutput + $windOutput;
$batteryStorage = 3400; // in Watt-hours
$totalConsumption = 2200; // in Watts
$housesSupplied = 6;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Power Statistics</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-6">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl p-6 space-y-6">
        <h1 class="text-3xl font-bold text-center text-blue-700">🔋 Power Statistics Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-center">
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Solar Power Output</h2>
                <p class="text-2xl font-bold text-yellow-500"><?= $solarOutput ?> W</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Wind Power Output</h2>
                <p class="text-2xl font-bold text-green-500"><?= $windOutput ?> W</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Total Power Generated</h2>
                <p class="text-2xl font-bold text-purple-600"><?= $totalGenerated ?> W</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Battery Storage Level</h2>
                <p class="text-2xl font-bold text-indigo-500"><?= $batteryStorage ?> Wh</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Total Power Consumption</h2>
                <p class="text-2xl font-bold text-red-500"><?= $totalConsumption ?> W</p>
            </div>
            <div class="bg-blue-50 p-4 rounded-xl shadow-sm">
                <h2 class="text-lg font-semibold text-blue-800">Houses Supplied</h2>
                <p class="text-2xl font-bold text-emerald-600"><?= $housesSupplied ?></p>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="dashboard.php" class="text-blue-500 hover:underline">← Back to Dashboard</a>
        </div>
    </div>

</body>

</html>
