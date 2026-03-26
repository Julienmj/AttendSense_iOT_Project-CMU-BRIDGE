<?php
echo "<h1>Hello World - PHP Test</h1>";
echo "<p>If you see this, PHP is working!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current file: " . __FILE__ . "</p>";
?>
