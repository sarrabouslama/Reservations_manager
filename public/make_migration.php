<?php
// make_migration.php
// WARNING: Delete this file after use for security!

set_time_limit(300);
passthru('php bin/console make:migration');
passthru('php bin/console doctrine:migrations:migrate --no-interaction');
echo "<br>Migration creation and execution finished.";
?>
