<style>
	.title {
		width: 70%!important;
	}
</style>

<?php

use Includes\UserResultsTable;

$myListTable = new UserResultsTable();
echo '<div class="wrap"><h2>User Form Results</h2>';
$myListTable->prepare_items();
$myListTable->display();
echo '</div>';