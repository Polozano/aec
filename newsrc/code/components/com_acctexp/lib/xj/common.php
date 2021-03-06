<?php
class xJLanguageHandlerCommon
{
	static function getSystemLanguages()
	{
		$fdir = JPATH_SITE . '/language';

		$list = xJUtility::getFileArray( $fdir, null, true, true );

		$adir = JPATH_SITE . '/administrator/language';

		$list = array_merge( $list, xJUtility::getFileArray( $fdir, null, true, true ) );

		$languages = array();
		foreach ( $list as $li ) {
			if ( ( strpos( $li, '-' ) !== false ) && !in_array( $li, $languages ) ) {
				$languages[] = $li;
			}
		}

		return $languages;
	}
}

class xJACLhandlerCommon
{
	static function getAdminGroups( $regular_admins=true )
	{
		$db = JFactory::getDBO();

		$query = 'SELECT `id`'
				. ' FROM #__usergroups'
				. ' WHERE `id` = 8'
				. ( $regular_admins ? ' OR `id` = 7' : '' )
				;
		$db->setQuery( $query );

		return xJ::getDBArray( $db );
	}

	static function getManagerGroups()
	{
		// Thank you, I hate myself /quite/ enough
		return array(6);
	}

	static function getUsersByGroup( $group )
	{
		$acl = JFactory::getACL();

		if ( is_array( $group ) ) {
			$groups = $group;
		} else {
			$groups[] = $group;
		}

		$users = array();
		foreach ( $groups as $group_id ) {
			$users = array_merge( $users, $acl->getUsersByGroup( $group_id ) );
		}

		return array_unique( $users );
	}

	static function getUserObjects( $users )
	{
		$db = JFactory::getDBO();

		$query = 'SELECT `id`, `name`, `email`, `sendEmail`'
				. ' FROM #__users'
				. ' WHERE id IN (' . implode( ',', $users ) . ')'
				;
		$db->setQuery( $query );

		return $db->loadObjectList();
	}

	/**
	 * @param integer $userid
	 */
	static function removeGIDs( $userid, $gids )
	{
		$db = JFactory::getDBO();

		foreach ( $gids as $gid ) {
			$query = 'DELETE'
					. ' FROM #__user_usergroup_map'
					. ' WHERE `user_id` = \'' . ( (int) $userid ) . '\''
					. ' AND `group_id` = \'' . ( (int) $gid ) . '\''
					;
			$db->setQuery( $query );
			$db->query();
		}
	}

	/**
	 * @param integer $userid
	 */
	static function setGIDs( $userid, $gids )
	{
		$info = array();
		foreach ( $gids as $gid ) {
			$info[$gid] = xJACLhandler::setGIDsTakeNames( $userid, $gid );

			xJACLhandler::setGID( $userid, $gid, $info[$gid] );
		}

		return $info;
	}
}

class xJSessionHandlerCommon
{
	// The following two functions copied from joomla to circle around their hardcoded caching

	public function getGroupsByUser( $userId, $recursive=true )
	{
		$db	= JFactory::getDBO();

		// Build the database query to get the rules for the asset.
		$query	= $db->getQuery(true);
		$query->select($recursive ? 'b.id' : 'a.id');
		$query->from('#__user_usergroup_map AS map');
		$query->where('map.user_id = '.(int) $userId);
		$query->leftJoin('#__usergroups AS a ON a.id = map.group_id');

		// If we want the rules cascading up to the global asset node we need a self-join.
		if ($recursive) {
			$query->leftJoin('#__usergroups AS b ON b.lft <= a.lft AND b.rgt >= a.rgt');
		}

		// Execute the query and load the rules from the result.
		$db->setQuery($query);
		$result	= xJ::getDBArray( $db );

		// Clean up any NULL or duplicate values, just in case
		JArrayHelper::toInteger($result);

		if (empty($result)) {
			$result = array('1');
		}
		else {
			$result = array_unique($result);
		}

		return $result;
	}

	public function getAuthorisedViewLevels($userId)
	{
		// Get all groups that the user is mapped to recursively.
		$groups = self::getGroupsByUser($userId);

		// Only load the view levels once.
		if (empty($viewLevels)) {
			// Get a database object.
			$db	= JFactory::getDBO();

			// Build the base query.
			$query	= $db->getQuery(true);
			$query->select('id, rules');
			$query->from('`#__viewlevels`');

			// Set the query for execution.
			$db->setQuery((string) $query);

			// Build the view levels array.
			foreach ($db->loadAssocList() as $level) {
				$viewLevels[$level['id']] = (array) json_decode($level['rules']);
			}
		}

		// Initialise the authorised array.
		$authorised = array(1);

		// Find the authorized levels.
		foreach ($viewLevels as $level => $rule)
		{
			foreach ($rule as $id)
			{
				if (($id < 0) && (($id * -1) == $userId)) {
					$authorised[] = $level;
					break;
				}
				// Check to see if the group is mapped to the level.
				elseif (($id >= 0) && in_array($id, $groups)) {
					$authorised[] = $level;
					break;
				}
			}
		}

		return $authorised;
	}

	public function getSession( $userid )
	{
		$db = JFactory::getDBO();

		$query = 'SELECT data'
		. ' FROM #__session'
		. ' WHERE `userid` = \'' . (int) $userid . '\''
		;
		$db->setQuery( $query );
		$data = $db->loadResult();

		if ( !empty( $data ) ) {
			$session = $this->joomunserializesession( $data );

			$sessionkeys = array_keys( $session );

			$key = array_pop( $sessionkeys );

			$this->sessionkey = $key;

			return $session[$key];
		} else {
			return array();
		}
	}

	/**
	 * @param boolean $data
	 */
	public function joomunserializesession( $data )
	{
		$se = explode( "|", $data, 2 );

		if ( isset( $se[1] ) ) {
			return array( $se[0] => unserialize( $se[1] ) );
		} elseif ( isset( $se[0] ) ) {
			return array( $se[0] => array() );
		} else {
			return array();
		}
	}

	public function joomserializesession( $data )
	{
		$sessionkeys = array_keys( $data );

		$key = array_pop( $sessionkeys );

		return $key . "|" . serialize( $data[$key] );
	}
}
