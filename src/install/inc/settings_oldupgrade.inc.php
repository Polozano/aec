<?php
// load settings (creates settings parameters that got added in this version)
$result = null;
$database->setQuery( "SHOW COLUMNS FROM #__acctexp_config LIKE 'settings'" );
$database->loadObject($result);

if ( strcmp( $result->Field, 'settings' ) !== 0 ) {
	$columns = array(	"transferinfo", "initialexp", "alertlevel1", "alertlevel2",
						"alertlevel3", "gwlist", "customintro", "customthanks",
						"customcancel", "bypassintegration", "simpleurls", "expiration_cushion",
						"currency_code", "heartbeat_cycle", "tos", "require_subscription",
						"entry_plan", "plans_first", "transfer", "checkusername", "activate_paid"
					);

	$settings = array();
	foreach ($columns as $column) {
		$result = null;
		$database->setQuery("SHOW COLUMNS FROM #__acctexp_config LIKE '" . $column . "'");
		$database->loadObject($result);
		if (strcmp($result->Field, $column) === 0) {
			$database->setQuery( "SELECT " . $column . " FROM #__acctexp_config WHERE id='1'" );
			$settings[$column] = $database->loadResult();

			$database->setQuery("ALTER TABLE #__acctexp_config DROP COLUMN " . $column);
			if ( !$database->query() ) {
		    	$errors[] = array( $database->getErrorMsg(), $query );
			}
		}
	}

	$database->setQuery("ALTER TABLE #__acctexp_config ADD `settings` text");
	if ( !$database->query() ) {
    	$errors[] = array( $database->getErrorMsg(), $query );
	}

	$database->setQuery("UPDATE #__acctexp_config SET `settings` = '" . parameterHandler::encode( $settings ) . "' WHERE id = '1'");
	if ( !$database->query() ) {
    	$errors[] = array( $database->getErrorMsg(), $query );
	}
}
?>