<?php
session_start();
require_once 'config.php';
require_once 'vendor/autoload.php'; // For Stripe

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

requireLogin();

try {
    // Fetch user's subscription plan
    $stmt = $pdo->prepare("
        SELECT p.* FROM subscription_plans p
        JOIN users u ON u.subscription_plan_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $plan = $stmt->fetch();

    if (!$plan) {
        header("Location: plans.php");
        exit;
    }

    // Create Stripe payment intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => $plan['price'] * 100, // Convert to cents
        'currency' => 'usd',
        'metadata' => [
            'user_id' => $_SESSION['user_id'],
            'plan_id' => $plan['id']
        ]
    ]);

} catch (Exception $e) {
    error_log("Payment error: " . $e->getMessage());
    header("Location: error.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div class="payment-container">
        <div class="payment-box">
            <h2>Complete Your Payment</h2>
            <div class="plan-summary">
                <h3><?php echo sanitizeOutput($plan['name']); ?> Plan</h3>
                <p class="price">$<?php echo number_format($plan['price'], 2); ?>/month</p>
                <ul class="features">
                    <?php foreach (json_decode($plan['features']) as $feature): ?>
                        <li><?php echo sanitizeOutput($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <form id="payment-form">
                <div id="card-element"></div>
                <div id="card-errors" class="error-message"></div>
                <button type="submit" id="submit-button">
                    <span id="button-text">Pay Now</span>
                    <span id="spinner" class="hidden">Processing...</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
        const elements = stripe.elements();
        const card = elements.create('card');
        card.mount('#card-element');

        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');

        card.addEventListener('change', ({error}) => {
            const displayError = document.getElementById('card-errors');
            if (error) {
                displayError.textContent = error.message;
            } else {
                displayError.textContent = '';
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            submitButton.disabled = true;
            
            try {
                const {paymentIntent, error} = await stripe.confirmCardPayment(
                    '<?php echo $paymentIntent->client_secret; ?>', {
                        payment_method: {
                            card: card,
                        }
                    }
                );

                if (error) {
                    throw error;
                }

                // Payment successful
                window.location.href = 'payment-success.php';
            } catch (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                submitButton.disabled = false;
            }
        });
    </script>
</body>
</html>
