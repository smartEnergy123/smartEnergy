<?php

if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

$userType = $_SESSION['user_data']['user_type'];

if ($userType !== 'admin') {
    $userType = 'client';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact - SmartEnergy</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 h-screen flex flex-col items-center justify-center">

    <div class="bg-white shadow-lg rounded-xl p-8 max-w-md w-full text-center
                transition transform duration-300 ease-in-out hover:-translate-y-1 hover:shadow-2xl">
        <h1 class="text-2xl font-bold text-blue-600 mb-4">Contact Us</h1>
        <p class="text-gray-700 text-lg mb-2">For any inquiries about SmartEnergy, reach out via:</p>
        <p class="text-blue-500 font-medium text-lg">
            <a href="mailto:smartEnergy@gmail.com">smartEnergy@gmail.com</a>
        </p>
    </div>

    <a href="/smartEnergy/<?php echo $userType; ?>/dashboard/">
        <button class="select-plan-btn bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors duration-300 mt-5">Dashboard</button>
        </button>
    </a>
</body>

</html>
