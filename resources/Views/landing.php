<?php
$path = $_SERVER['REQUEST_URI'] . '/resources/Views';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Smart Energy Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans">

    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-green-600">SmartEnergy</h1>
            <nav class="flex space-x-4">
                <div class="mt-3 space-x-4">
                    <a href="#" class="text-gray-600 hover:text-green-600">Home</a>
                    <a href="#features" class="text-gray-600 hover:text-green-600">Features</a>
                    <a href="#about" class="text-gray-600 hover:text-green-600">About</a>
                    <a href="#contact" class="text-gray-600 hover:text-green-600">Contact</a>
                </div>
                <div>
                    <a href="../smartEnergy/login">
                        <button class="inline-block bg-green-600 text-white px-6 py-3 rounded-xl text-lg hover:bg-blue-700">Sign-in</button>
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="bg-green-100 py-16">
        <div class="max-w-4xl mx-auto text-center px-4">
            <h2 class="text-4xl font-bold text-green-700">Powering Communities with Smart Solar Solutions</h2>
            <p class="mt-4 text-lg text-green-900">Manage, monitor, and optimize solar energy usage efficiently in shared living spaces. Designed for reliability, sustainability, and accessibility.</p>
            <a href="#features">
                <button class="mt-6 inline-block bg-green-600 text-white px-6 py-3 rounded-xl text-lg hover:bg-green-700">Learn More</button>
            </a>
        </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <h3 class="text-3xl font-semibold text-gray-800">Key Features</h3>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-gray-50 p-6 rounded-xl shadow hover:shadow-md">
                    <h4 class="text-xl font-bold text-green-600">Real-Time Monitoring</h4>
                    <p class="mt-2 text-gray-600">Track energy usage and solar generation live across all homes.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-xl shadow hover:shadow-md">
                    <h4 class="text-xl font-bold text-green-600">Daily Allocation</h4>
                    <p class="mt-2 text-gray-600">Get fair power distribution based on subscription plans and usage.</p>
                </div>
                <div class="bg-gray-50 p-6 rounded-xl shadow hover:shadow-md">
                    <h4 class="text-xl font-bold text-green-600">Subscription Billing</h4>
                    <p class="mt-2 text-gray-600">Easily manage and automate monthly energy payments.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About -->
    <section id="about" class="py-16 bg-green-50">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h3 class="text-3xl font-semibold text-gray-800">About the Project</h3>
            <p class="mt-4 text-gray-700">Built as university hackathon project, SmartEnergy aims to solve the challenge of equitable and efficient energy distribution in solar-powered estates. By combining affordable technology with smart design, we ensure sustainable access for all users.</p>
        </div>
    </section>

    <!-- Contact -->
    <section id="contact" class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h3 class="text-3xl font-semibold text-gray-800">Get in Touch</h3>
            <p class="mt-4 text-gray-600">For collaboration or inquiries, contact us at <a href="mailto:team@smartenergy.com" class="text-green-600 underline">team@smartenergy.com</a></p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-100 text-center py-6 mt-8 text-gray-500 text-sm">
        &copy; 2025 SmartEnergy Team. All rights reserved.
    </footer>

</body>

</html>
