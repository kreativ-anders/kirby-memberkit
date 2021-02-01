<script type="text/javascript">
    // Create an instance of the Stripe object with your publishable API key
    var stripe = Stripe("<?= option('kreativ-anders.stripekit.publicKey') ?>");
    var checkoutButton = document.getElementById("<?= $id ?>");
    checkoutButton.addEventListener("click", function () {
      fetch("/create-checkout-session.php", { // where to fetch url --> Data-url?
        method: "POST",
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (session) {
          return stripe.redirectToCheckout({ sessionId: session.id });
        })
        .then(function (result) {
          // If redirectToCheckout fails due to a browser or network
          // error, you should display the localized error message to your
          // customer using error.message.
          if (result.error) {
            alert(result.error.message);
          }
        })
        .catch(function (error) {
          console.error("Error:", error);
        });
    });
  </script>