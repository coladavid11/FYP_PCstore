<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function navActive($page, $currentPage) {
  return ($page === $currentPage) ? 'active' : '';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top"
     style="box-shadow: 0 4px 10px rgba(0,0,0,0.3); border-bottom: 2px solid #d4af37;">
  <div class="container">

    <a class="navbar-brand fw-bold" href="index.php"
       style="font-size: 1.5rem; color: #d4af37;">
      <i class="fa fa-laptop-code"></i> MY PC STORE
    </a>

    <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse"
            data-bs-target="#userNav" 
            aria-controls="userNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="userNav">

      <ul class="navbar-nav ms-auto align-items-center">

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('index.php', $currentPage); ?>" href="index.php">Home</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('pc-builds.php', $currentPage); ?>" href="pc-builds.php">PC Recommendations</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('full-set.php', $currentPage); ?>" href="full-set.php">Full-Set Sales</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo navActive('game-check.php', $currentPage); ?>" href="game-check.php">Game Requirements</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="aboutDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            About
          </a>
          <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="aboutDropdown">
            <li><a class="dropdown-item" href="about.php">Our Story</a></li>
            <li><a class="dropdown-item" href="contact.php">Contact Us</a></li>
          </ul>
        </li>

        <form class="d-flex ms-lg-4 my-2 my-lg-0" action="search.php" method="get" style="flex-grow: 1; max-width: 400px;">
        <div class="input-group">
          <input class="form-control bg-dark text-white border-secondary" type="search" name="query" placeholder="Search components..." aria-label="Search">
          <button class="btn btn-outline-warning" type="submit"><i class="fa fa-search"></i></button>
        </div>
      </form>

        <li class="nav-item ms-lg-3">
          <a class="nav-link position-relative" href="cart.php">
            <i class="fa fa-shopping-cart fs-5 text-warning"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
              0
            </span>
          </a>
        </li>

        <?php if(strlen($_SESSION['login'])==0) { ?>
          <li class="nav-item ms-3">
            <a class="btn btn-gold-sm" href="login.php">Login / Register</a>
          </li>

        <?php } else { ?>
          <li class="nav-item ms-3 dropdown">
            <a class="nav-link dropdown-toggle text-warning fw-bold" 
               href="#" 
               id="userDropdownMenu" 
               role="button" 
               data-bs-toggle="dropdown" 
               aria-expanded="false">
              <i class="fa fa-user-circle"></i>
              <?php echo htmlentities($_SESSION['fname']); ?>
            </a>

            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark" aria-labelledby="userDropdownMenu">
              <li>
                <a class="dropdown-item <?php echo navActive('profile.php', $currentPage); ?>" href="profile.php">
                  <i class="fa fa-user me-2"></i> Edit Profile
                </a>
              </li>
              <li>
                <a class="dropdown-item <?php echo navActive('my-orders.php', $currentPage); ?>" href="my-orders.php">
                  <i class="fa fa-box me-2"></i> My Orders
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

<style>
  body { padding-top: 80px; background-color: #0f0f0f; }

  .navbar .nav-link {
    font-size: 0.9rem;
    transition: 0.3s;
  }

  .navbar .nav-link:hover, .navbar .nav-link.active {
    color: #d4af37 !important;
  }

  .btn-gold-sm {
    background: #d4af37;
    color: #000;
    font-size: 0.8rem;
    font-weight: bold;
    padding: 5px 15px;
    border-radius: 2px;
    text-decoration: none;
    transition: 0.3s;
  }

  .btn-gold-sm:hover {
    background: #fff;
    color: #000;
  }

  .dropdown-menu {
    border: 1px solid #d4af37;
  }

  /* Fix for the dropdown to ensure it appears on top */
  .dropdown-item:hover {
    background-color: #d4af37;
    color: #000 !important;
  }
</style>