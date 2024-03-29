<?php 
// CHECK KIRBY USER FOR SUBSCRIBTIONS
if($kirby->user()->stripe_subscription()->isEmpty()): ?>

<?= js('https://js.stripe.com/v3/'); ?>

<button type="button" id="<?= $id ?>" class="<?= $classes ?>"><?= $text ?></button>

<script type="text/javascript">

  document.addEventListener("DOMContentLoaded", function(event) { 
        
    var stripe = Stripe("<?= option('kreativ-anders.memberkit.publicKey') ?>");
    var checkoutButton = document.getElementById("<?= $id ?>");
    checkoutButton.addEventListener("click", function () {
      fetch("<?= $url ?>", {
        method: "GET",
      })
        .then(function (response) {
          return response.json();
        })
        .then(function (session) {
          return stripe.redirectToCheckout({ sessionId: session.id });
        })
        .then(function (result) {

          if (result.error) {
            alert(result.error.message);
          }
        })
        .catch(function (error) {
          console.error("Error:", error);
        });
    });
  });

</script>

<?php endif; ?>