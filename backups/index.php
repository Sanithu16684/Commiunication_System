<?php
// backups/index.php - Prevent directory indexing
header("Location: ../backup.php");
exit;
