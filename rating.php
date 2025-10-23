<?php

session_start();
$message = null;

require __DIR__ . '/dbconnect.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $country  = isset($_POST['country'])  ? trim($_POST['country'])  : '';
    $review   = isset($_POST['review'])   ? trim($_POST['review'])   : '';

    
    if ($username === '' || $country === '' || $review === '') {
        $message = ['type' => 'error', 'text' => 'Please fill in all fields.'];
    } else {
        $username = mb_substr($username, 0, 100);
        $country  = mb_substr($country, 0, 50);
        $review   = mb_substr($review, 0, 250);

        $stmt = $conn->prepare("INSERT INTO CountryReviews (Country, ReviewText, UserName) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('sss', $country, $review, $username);
            if ($stmt->execute()) {
                $message = ['type' => 'success', 'text' => 'Thanks! Your review has been submitted.'];
                $username = $country = $review = '';
            } else {
                $message = ['type' => 'error', 'text' => 'Could not save your review. Please try again.'];
            }
            $stmt->close();
        } else {
            $message = ['type' => 'error', 'text' => 'Server error. Please try again later.'];
        }
    }
}

$latest = [];
$res = $conn->query("SELECT Country, ReviewText, UserName, Id FROM CountryReviews ORDER BY Id DESC LIMIT 3");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $latest[] = $row;
    }
    $res->close();
}

$conn->close();

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
<head>
  <meta charset="utf-8">
  <title>Country Reviews</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="CountryRating.css">
</head>
<body>
  <div class="wrap">
    <h1>Country Reviews</h1>
    <p class="sub">Share a short review about a country you’ve visited or love.</p>

    <?php if ($message): ?>
      <div class="alert <?= $message['type'] === 'success' ? 'alert-success' : 'alert-error' ?>">
        <?= e($message['text']) ?>
      </div>
    <?php endif; ?>

    <form class="card" method="post" action="">
      <div class="field">
        <label for="username">Your Name</label>
        <input
          id="username"
          name="username"
          type="text"
          placeholder="e.g., Peter"
          maxlength="100"
          required
          value="<?= e($username ?? '') ?>"
        >
      </div>

      <div class="field">
        <label for="country">Country</label>
        <input
          id="country"
          name="country"
          type="text"
          placeholder="e.g., Georgia"
          maxlength="50"
          required
          value="<?= e($country ?? '') ?>"
        >
      </div>

      <div class="field">
        <label for="review">Your Review (max 250 chars)</label>
        <textarea
          id="review"
          name="review"
          rows="4"
          placeholder="Short and sweet…"
          maxlength="250"
          required
        ><?= e($review ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn">Submit Review</button>
    </form>

    
    <section class="reviews">
      <h2>Latest Reviews</h2>
      <?php if (empty($latest)): ?>
        <p class="muted">No reviews yet. Be the first!</p>
      <?php else: ?>
        <ul class="review-list">
          <?php foreach ($latest as $row): ?>
            <li class="review-item">
              <div class="review-top">
                <span class="country-pill"><?= e($row['Country']) ?></span>
                <span class="byline">by <?= e($row['UserName']) ?></span>
              </div>
              <p class="review-text"><?= e($row['ReviewText']) ?></p>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <footer class="foot">
      <small>Data is stored in the <code>CountryReviews</code> table.</small>
    </footer>
  </div>
</body>
</html>
