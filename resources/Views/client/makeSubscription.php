<?php
// Views/clients/makeSubscription.php

if (!isset($_SESSION['user_state'])) {
    header('Location: /smartEnergy/login');
    exit;
}

// echo '<pre>';
// print_r($_SESSION['user_data']);
// echo '</pre>';

$userId = $_SESSION['user_data']['id'] ?? 'user_unknown';
$username = $_SESSION['user_data']['username'] ?? 'User';
$email = $_SESSION['user_data']['email'] ?? 'User_Email';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEnergy - Choose Your Plan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for card icons */
        .card-icon {
            width: 24px;
            height: 24px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }

        /* Basic animation for loading spinner */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Choose Your SmartEnergy Plan</h1>

        <div id="planSelection" class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col">
                <h2 class="text-2xl font-bold text-blue-700 mb-2">Monthly Standard Plan</h2>
                <p class="text-gray-600 mb-4 flex-grow">Enjoy a consistent daily energy allocation for all your household needs.</p>
                <div class="text-4xl font-extrabold text-blue-800 mb-4">
                    RON 89.99<span class="text-xl text-gray-500">/month</span>
                </div>
                <ul class="text-gray-700 list-disc list-inside mb-6">
                    <li>Daily Quota: <span class="font-semibold">7000 Wh</span></li>
                    <li>Automatic daily reset</li>
                    <li>Access to all appliance controls</li>
                    <li>Priority customer support</li>
                </ul>
                <button class="select-plan-btn bg-blue-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors duration-300"
                    data-plan-type="monthly_standard" data-amount="89.99" data-quota="7000">
                    Select Monthly Plan
                </button>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-6 shadow-md hover:shadow-xl transition-shadow duration-300 flex flex-col">
                <h2 class="text-2xl font-bold text-green-700 mb-2">Daily Top-up</h2>
                <p class="text-gray-600 mb-4 flex-grow">Need a little extra power for today? Get an instant boost to your current daily quota.</p>
                <div class="text-4xl font-extrabold text-green-800 mb-4">
                    RON 24.99<span class="text-xl text-gray-500">/top-up</span>
                </div>
                <ul class="text-gray-700 list-disc list-inside mb-6">
                    <li>Instant Quota Boost: <span class="font-semibold">1000 Wh</span></li>
                    <li>One-time purchase</li>
                    <li>Adds to your existing daily quota</li>
                    <li>Perfect for unexpected high usage</li>
                </ul>
                <button class="select-plan-btn bg-green-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-green-700 transition-colors duration-300"
                    data-plan-type="daily_top_up" data-amount="24.99" data-quota="1000">
                    Select Daily Top-up
                </button>
            </div>
        </div>

        <div id="paymentFormSection" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-6 shadow-md">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Complete Your Payment</h2>
            <p class="text-center text-gray-600 mb-6">You selected: <span id="selectedPlanDisplay" class="font-semibold text-blue-600"></span></p>

            <form id="paymentForm" class="space-y-6">
                <input type="hidden" id="paymentPlanType" name="planType">
                <input type="hidden" id="paymentAmountPaid" name="amountPaid">
                <input type="hidden" id="paymentQuotaGrantedWh" name="quotaGrantedWh">

                <div>
                    <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                    <div class="relative">
                        <input type="text" id="cardNumber" name="cardNumber" placeholder="•••• •••• •••• ••••"
                            class="mt-1 block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            maxlength="19" pattern="[0-9]{4} ?[0-9]{4} ?[0-9]{4} ?[0-9]{4}" title="Enter a 16-digit card number">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="expiryDate" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                        <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            maxlength="5" pattern="(0[1-9]|1[0-2])\/([0-9]{2})" title="Enter MM/YY format">
                    </div>
                    <div>
                        <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="•••"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            maxlength="4" pattern="[0-9]{3,4}" title="Enter 3 or 4 digit CVV">
                    </div>
                </div>

                <div>
                    <label for="cardholderName" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
                    <input type="text" id="cardholderName" name="cardholderName" placeholder="Full Name on Card"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>

                <div id="paymentMessage" class="text-center text-sm font-medium mt-4 hidden"></div>

                <button type="submit" id="payNowBtn"
                    class="w-full bg-indigo-600 text-white px-6 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition-colors duration-300 flex items-center justify-center">
                    <span id="payNowBtnText">Pay Now</span>
                    <div id="payNowLoader" class="loader ml-3 hidden"></div>
                </button>
                <button type="button" id="backToPlansBtn"
                    class="w-full mt-4 bg-gray-300 text-gray-800 px-6 py-3 rounded-lg text-lg font-semibold hover:bg-gray-400 transition-colors duration-300">
                    Back to Plans
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userId = "<?php echo $userId; ?>"; // Get userId from PHP session
            const userEmail = "<?php echo $email; ?>";
            const username = "<?php echo $username; ?>";

            const planSelectionSection = document.getElementById('planSelection');
            const paymentFormSection = document.getElementById('paymentFormSection');
            const selectPlanButtons = document.querySelectorAll('.select-plan-btn');
            const paymentForm = document.getElementById('paymentForm');
            const selectedPlanDisplay = document.getElementById('selectedPlanDisplay');
            const paymentPlanTypeInput = document.getElementById('paymentPlanType');
            const paymentAmountPaidInput = document.getElementById('paymentAmountPaid');
            const paymentQuotaGrantedWhInput = document.getElementById('paymentQuotaGrantedWh');
            const payNowBtn = document.getElementById('payNowBtn');
            const payNowBtnText = document.getElementById('payNowBtnText');
            const payNowLoader = document.getElementById('payNowLoader');
            const paymentMessage = document.getElementById('paymentMessage');
            const backToPlansBtn = document.getElementById('backToPlansBtn');

            // Payment form fields
            const cardNumberInput = document.getElementById('cardNumber');
            const expiryDateInput = document.getElementById('expiryDate');
            const cvvInput = document.getElementById('cvv');
            const cardholderNameInput = document.getElementById('cardholderName');

            let selectedPlan = null; // To store details of the selected plan

            // Event listeners for "Select Plan" buttons
            selectPlanButtons.forEach(button => {
                button.addEventListener('click', () => {
                    selectedPlan = {
                        planType: button.dataset.planType,
                        amount: parseFloat(button.dataset.amount),
                        quota: parseInt(button.dataset.quota)
                    };

                    // Populate hidden form fields
                    paymentPlanTypeInput.value = selectedPlan.planType;
                    paymentAmountPaidInput.value = selectedPlan.amount;
                    paymentQuotaGrantedWhInput.value = selectedPlan.quota;

                    // Update display text
                    selectedPlanDisplay.textContent = `${selectedPlan.planType.replace(/_/g, ' ').toUpperCase()} (RON ${selectedPlan.amount.toFixed(2)})`;

                    // Show payment form, hide plan selection
                    planSelectionSection.classList.add('hidden');
                    paymentFormSection.classList.remove('hidden');
                    paymentMessage.classList.add('hidden'); // Hide previous messages
                    paymentMessage.textContent = ''; // Clear message
                });
            });

            // Event listener for "Back to Plans" button
            backToPlansBtn.addEventListener('click', () => {
                paymentFormSection.classList.add('hidden');
                planSelectionSection.classList.remove('hidden');
                paymentForm.reset(); // Clear form fields
            });

            // Input formatting for card number (add spaces)
            cardNumberInput.addEventListener('input', (e) => {
                const value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                const formattedValue = value.replace(/(\d{4})(?=\d)/g, '$1 '); // Add space after every 4 digits
                e.target.value = formattedValue.trim();
            });

            // Input formatting for expiry date (MM/YY)
            expiryDateInput.addEventListener('input', (e) => {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });

            // Event listener for payment form submission
            paymentForm.addEventListener('submit', async (e) => {
                e.preventDefault(); // Prevent default form submission

                // Basic client-side validation
                if (!cardNumberInput.value || !expiryDateInput.value || !cvvInput.value || !cardholderNameInput.value) {
                    displayMessage('Please fill in all payment details.', 'text-red-600');
                    return;
                }

                // Show loading state
                payNowBtn.disabled = true;
                payNowBtnText.classList.add('hidden');
                payNowLoader.classList.remove('hidden');
                displayMessage('Processing your payment...', 'text-gray-600');

                // In a real application, you would send payment details to a secure payment gateway (e.g., Stripe, PayPal)
                // and not directly to your backend. This is a simplified simulation.

                try {
                    const response = await fetch('/smartEnergy/api/process-subscription', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            userId: userId,
                            userName: username,
                            userEmail: userEmail,
                            planType: paymentPlanTypeInput.value,
                            amountPaid: parseFloat(paymentAmountPaidInput.value),
                            quotaGrantedWh: parseInt(paymentQuotaGrantedWhInput.value),
                            // Dummy payment details (for simulation only, never send real card data like this!)
                            cardNumber: cardNumberInput.value,
                            expiryDate: expiryDateInput.value,
                            cvv: cvvInput.value,
                            cardholderName: cardholderNameInput.value
                        })
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        displayMessage('Payment successful! Redirecting to dashboard...', 'text-green-600');
                        // Redirect to dashboard after a short delay
                        setTimeout(() => {
                            window.location.href = '/smartEnergy/client/dashboard/';
                        }, 2000);
                    } else {
                        displayMessage(`Payment failed: ${result.message || 'Unknown error.'}`, 'text-red-600');
                    }
                } catch (error) {
                    console.error('Error during payment process:', error);
                    displayMessage('An error occurred during payment. Please try again.', 'text-red-600');
                } finally {
                    // Reset loading state
                    payNowBtn.disabled = false;
                    payNowBtnText.classList.remove('hidden');
                    payNowLoader.classList.add('hidden');
                }
            });

            // Helper function to display messages
            function displayMessage(message, colorClass) {
                paymentMessage.textContent = message;
                paymentMessage.className = `text-center text-sm font-medium mt-4 ${colorClass}`;
                paymentMessage.classList.remove('hidden');
            }
        });
    </script>
</body>

</html>
