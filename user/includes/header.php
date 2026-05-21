<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPage = basename($_SERVER['PHP_SELF']);

// FUNCTION TO SET ACTIVE CLASS
function navActive($page, $currentPage) {
    if (is_array($page)) {
        return in_array($currentPage, $page) ? 'active' : '';
    }
    return ($page === $currentPage) ? 'active' : '';
}

// SAFE session variables
$isLoggedIn = !empty($_SESSION['login']);
$username = $_SESSION['fname'] ?? 'User';

// SHORTEN USERNAME FOR DISPLAY
function shortName($name, $limit = 12) {
    return (mb_strlen($name) > $limit)
        ? mb_substr($name, 0, $limit) . '...'
        : $name;
}

?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top"
     style="box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-bottom: 2px solid #d4af37;">

  <div class="container">

    <!-- BRAND -->
    <a class="navbar-brand fw-bold" href="index.php"
       style="font-size: 1.5rem; color: #d4af37;">
      <i class="fa fa-laptop-code"></i> MY PC STORE
    </a>

    <!-- TOGGLER -->
    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#userNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="userNav">

      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('index.php', $currentPage); ?>" href="index.php">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('products.php', $currentPage); ?>" href="products.php">Products</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('pc-builds.php', $currentPage); ?>" href="pc-builds.php">PC Builds</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('full-set.php', $currentPage); ?>" href="full-set.php">Full Set</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('game-check.php', $currentPage); ?>" href="game-check.php">Game Check</a>
        </li>

        <!-- ABOUT DROPDOWN -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?php echo navActive(['about.php', 'contact.php'], $currentPage); ?>" href="#" data-bs-toggle="dropdown">
            About
          </a>
          <ul class="dropdown-menu dropdown-menu-dark">
            <li><a class="dropdown-item" href="about.php">Our Story</a></li>
            <li><a class="dropdown-item" href="contact.php">Contact</a></li>
          </ul>
        </li>

        <!-- SEARCH -->
        <form class="d-flex ms-lg-4" action="search.php" method="get" style="max-width: 350px;">
          <div class="input-group">
            <input class="form-control bg-dark text-white border-secondary"
                   type="search"
                   name="query"
                   placeholder="Search...">
            <button class="btn btn-outline-warning">
              <i class="fa fa-search"></i>
            </button>
          </div>
        </form>

        <!-- CART -->
        <li class="nav-item ms-lg-3">
          <a class="nav-link position-relative" href="cart.php">
            <i class="fa fa-shopping-cart text-warning fs-5"></i>
            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
              0
            </span>
          </a>
        </li>

        <!-- LOGIN / USER -->
        <?php if (!$isLoggedIn) { ?>

          <li class="nav-item ms-3">
            <a class="btn btn-warning btn-sm" href="login.php">
              Login / Register
            </a>
          </li>

        <?php } else { ?>

          <li class="nav-item dropdown ms-3">
            <a class="nav-link dropdown-toggle text-warning fw-bold"
               href="#"
               data-bs-toggle="dropdown">

              <i class="fa fa-user-circle"></i>
              <?php echo htmlentities(shortName($username)); ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">

              <li>
                <a class="dropdown-item" href="myprofile.php">
                  <i class="fa fa-user me-2"></i> Profile
                </a>
              </li>

              <li>
                <a class="dropdown-item" href="myorder.php">
                  <i class="fa fa-box me-2"></i> Orders
                </a>
              </li>

              <li><hr class="dropdown-divider"></li>

              <li>
                <a class="dropdown-item text-danger" href="logout.php">
                  <i class="fa fa-sign-out-alt me-2"></i> Logout
                </a>
              </li>

            </ul>
          </li>

        <?php } ?>

      </ul>
    </div>
  </div>
</nav>