<?php
/**
 * Colleges List Widget
 * 
 * Usage:  <div data-component="colleges"></div>
 */

require_once '../../../server/config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch Real College Data
$query = "SELECT c.college_name as name, COUNT(s.student_id) as students
          FROM colleges c
          LEFT JOIN students s ON c.college_id = s.college_id AND s.deleted_at IS NULL
          GROUP BY c.college_id
          ORDER BY students DESC";

$stmt = $db->query($query);
$colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Color Map (matches students.php)
$college_colors = [
  'COT' => '#ff7d04',
  'CON' => '#ec57ee',
  'COB' => '#fac800',
  'COE' => '#1c5adf',
  'CPAG' => '#23c7c7',
  'CAS' => '#10b981',
];

$maxStudents = (count($colleges) > 0) ? max(array_column($colleges, 'students')) : 0;
if ($maxStudents == 0)
  $maxStudents = 1;
?>

<div class="colleges-card">
  <div class="widget-header">
    <h3>Colleges</h3>
    <span class="colleges-total"><?= array_sum(array_column($colleges, 'students')) ?> total</span>
  </div>
  <ul class="colleges-items" id="colleges-list">
    <?php foreach ($colleges as $college): ?>
      <?php
      $abbr = $college['name']; // In this system, name often holds the abbr like 'COT'
      $color = $college_colors[$abbr] ?? '#3b82f6';
      $pct = round(($college['students'] / $maxStudents) * 100);
      ?>
      <li class="college-item">

        <div class="college-info">
          <span class="college-badge" style="background:<?= $color ?>26; color:<?= $color ?>">
            <?= htmlspecialchars($abbr) ?>
          </span>
          <span class="college-name"><?= htmlspecialchars($college['name']) ?></span>
        </div>

        <div class="college-stats">
          <div class="college-bar-track">
            <div class="college-bar-fill" style="width:<?= $pct ?>%; background:<?= $color ?>"></div>
          </div>
          <span class="college-count"><?= $college['students'] ?></span>
        </div>

      </li>
    <?php endforeach; ?>
  </ul>
</div>