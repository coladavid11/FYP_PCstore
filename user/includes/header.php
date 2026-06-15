<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($page, $currentPage)
{
  if (is_array($page)) {
    return in_array($currentPage, $page) ? 'active' : '';
  }
  return ($page === $currentPage) ? 'active' : '';
}

$isLoggedIn = !empty($_SESSION['login']);
$username   = $_SESSION['fname'] ?? 'User';

function shortName($name, $limit = 12)
{
  return (mb_strlen($name) > $limit)
    ? mb_substr($name, 0, $limit) . '...'
    : $name;
}

// Fetch cart count server-side on page load
$cartCount = 0;
if ($isLoggedIn && isset($_SESSION['user_id'])) {
  $stmt = $dbh->prepare("SELECT COALESCE(SUM(quantity), 0) FROM tblcart WHERE user_id = ? AND status = 'active'");
  $stmt->execute([$_SESSION['user_id']]);
  $cartCount = (int) $stmt->fetchColumn();
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top"
  style="box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-bottom: 2px solid #d4af37;">

  <div class="container">

    <a class="navbar-brand fw-bold" href="index.php" style="font-size: 1.5rem; color: #d4af37;">
      <i class="fa fa-laptop-code"></i> MY PC STORE
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#userNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="userNav">
      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('index.php', $currentPage); ?>" href="index.php">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('product.php', $currentPage); ?>" href="product.php">Products</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('pcbuild.php', $currentPage); ?>" href="pcbuild.php">PC Builds</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('full_set.php', $currentPage); ?>" href="full_set.php">Full Set</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('game_check.php', $currentPage); ?>" href="game_check.php">Game Check</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo navActive(['about.php', 'contact.php'], $currentPage); ?>"
            href="#" data-bs-toggle="dropdown">
            About
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="about.php">Our Story</a></li>
            <li><a class="dropdown-item" href="contact.php">Contact</a></li>
          </ul>
        </li>

        <!-- CART with live badge -->
        <li class="nav-item ms-lg-3">
          <a class="nav-link position-relative" href="cart.php">
            <i class="fa fa-shopping-cart text-warning fs-5"></i>
            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle"
                  id="cartBadge"
                  style="<?php echo $cartCount === 0 ? 'display:none;' : ''; ?>">
              <?php echo $cartCount; ?>
            </span>
          </a>
        </li>

        <?php if (!$isLoggedIn): ?>
          <li class="nav-item ms-3">
            <a class="btn btn-warning btn-sm" href="login.php">Login / Register</a>
          </li>
        <?php else: ?>
          <li class="nav-item dropdown ms-3">
            <a class="nav-link dropdown-toggle text-warning fw-bold" href="#" data-bs-toggle="dropdown">
              <i class="fa fa-user-circle"></i>
              <?php echo htmlentities(shortName($username)); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
              <li><a class="dropdown-item" href="myprofile.php"><i class="fa fa-user me-2"></i> Profile</a></li>
              <li><a class="dropdown-item" href="wishlist.php"><i class="fa fa-heart me-2"></i> Wishlist</a></li>
              <li><a class="dropdown-item" href="myorder.php"><i class="fa fa-box me-2"></i> Orders</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>

<script>
/**
 * updateCartBadge(delta)
 * Call this after any successful add-to-cart or remove-from-cart AJAX.
 *
 * delta = number to ADD to current count  (positive = add, negative = remove)
 *         pass null to force a fresh fetch from the server instead
 *
 * Usage examples:
 *   updateCartBadge(1);        // added 1 item
 *   updateCartBadge(qty);      // added qty items
 *   updateCartBadge(-1);       // removed 1 item
 *   updateCartBadge(null);     // re-fetch from server (safest fallback)
 */
window.updateCartBadge = function (delta) {
  const badge = document.getElementById('cartBadge');
  if (!badge) return;

  if (delta === null || delta === undefined) {
    // Fetch fresh count from server
    fetch('get_cart_count.php')
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') setBadge(badge, data.count);
      })
      .catch(() => {}); // silently fail — don't break page
    return;
  }

  const current = parseInt(badge.textContent) || 0;
  setBadge(badge, current + delta);
};

function setBadge(badge, count) {
  count = Math.max(0, count);
  badge.textContent = count;
  badge.style.display = count === 0 ? 'none' : '';

  // Pulse animation
  badge.classList.remove('badge-pulse');
  void badge.offsetWidth; // force reflow
  badge.classList.add('badge-pulse');
}

// Force uniform font-weight on all nav-links regardless of CSS priority wars
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.navbar .nav-link').forEach(function (el) {
    el.style.fontWeight = '400';
  });
});
</script>

<style>
@keyframes badgePulse {
  0%   { transform: translate(-50%, -50%) scale(1); }
  40%  { transform: translate(-50%, -50%) scale(1.45); }
  100% { transform: translate(-50%, -50%) scale(1); }
}
#cartBadge.badge-pulse {
  animation: badgePulse 0.35s ease-out;
}
</style>