<?php
/**
 * @version $Id: acctexp.subscriptionplan.class.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Core Class
 * @copyright 2006-2015 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.3 http://www.gnu.org/licenses/gpl.html or, at your option, any later version
 */

// Dont allow direct linking
defined('_JEXEC') or die( 'Direct Access to this location is not allowed.' );

class SubscriptionPlanList
{
	/**
	 * @param integer $usage
	 * @param integer $group
	 */
	public function __construct( $usage, $group, $metaUser, $recurring )
	{
		$this->metaUser = $metaUser;

		$this->usage = $usage;

		$this->group = $group;

		if ( !empty( $this->metaUser->userid ) ) {
			if ( $this->metaUser->hasSubscription ) {
				$this->expired = $this->metaUser->objSubscription->isExpired();
			} else {
				$this->expired = false;
			}
		} else {
			$this->expired = true;
		}

		$this->getPlanList();

		$this->checkListProblems();

		$this->explodePlanList( $recurring );
	}

	public function getPlanList()
	{
		$auth_problem = null;

		$this->list = array();

		if ( !empty( $this->usage ) ) {
			$db = JFactory::getDBO();

			$query = 'SELECT `id`'
				. ' FROM #__acctexp_plans'
				. ' WHERE `id` = \'' . $this->usage . '\' AND `active` = \'1\''
				;
			$db->setQuery( $query );
			$id = $db->loadResult();

			if ( $id ) {
				$plan = new SubscriptionPlan();
				$plan->load( $id );

				$authorized = $plan->checkAuthorized( $this->metaUser );

				if ( $authorized === true ) {
					$this->list[] = ItemGroupHandler::getItemListItem( $plan );
				} elseif ( $authorized === false ) {
					$auth_problem = true;
				} else {
					$auth_problem = $authorized;
				}
			} else {
				// Plan does not exist
				$auth_problem = true;
			}
		} else {
			if ( !empty( $this->group ) ) {
				$gid = $this->group;
			} else {
				global $aecConfig;

				if ( !empty( $aecConfig->cfg['root_group_rw'] ) ) {
					$gid = AECToolbox::rewriteEngine( $aecConfig->cfg['root_group_rw'], $this->metaUser );
				} else {
					$gid = array( $aecConfig->cfg['root_group'] );
				}
			}

			if ( is_array( $gid ) ) {
				$gid = $gid[0];
			}

			$g = new ItemGroup();
			$g->load( $gid );

			if ( $g->checkPermission( $this->metaUser ) ) {
				if ( !empty( $g->params['symlink_userid'] ) && !empty( $g->params['symlink'] ) ) {
					aecRedirect( $g->params['symlink'], $this->metaUser->userid, "aechidden" );
				} elseif ( !empty( $g->params['symlink'] ) ) {
					return $g->params['symlink'];
				}

				$this->list = ItemGroupHandler::getTotalAllowedChildItems( array( $gid ), $this->metaUser );

				if ( count( $this->list ) == 0 ) {
					$auth_problem = true;
				}
			} else {
				$auth_problem = true;
			}

			if ( $auth_problem && !empty( $g->params['notauth_redirect'] ) ) {
				$auth_problem = $g->params['notauth_redirect'];
			}
		}

		if ( !is_null( $auth_problem ) ) {
			$this->list = $auth_problem;
		}

		return true;
	}

	/**
	 * @param string $passthrough
	 */
	public function addButtons( $register, $passthrough )
	{
		global $aecConfig;

		$return = '';
		if ( $this->group ) {
			if ( $aecConfig->cfg['additem_stayonpage'] ) {
				$return = $this->group;
			}
		}

		$csslist = array();
		foreach ( $this->list as $li => $lv ) {
			if ( $lv['type'] == 'group' ) {
				continue;
			}

			foreach ( $lv['gw'] as $gwid => $pp ) {
				$btn = $this->getButton( $pp, $lv['id'], $register, $passthrough, $return );

				if ( !empty( $btn['css'] ) && !isset( $csslist[$btn['processor']] ) ) {
					$csslist[$btn['processor']] = $btn['css'];
				}

				$this->list[$li]['gw'][$gwid]->btn = $btn;
			}
		}

		return $csslist;
	}

	public function getButton( $pp, $usage, $register, $passthrough, $return )
	{
		global $aecConfig;

		$btnarray = array();

		$btnarray['usage'] = $usage;

		$btnarray['userid'] = $this->metaUser->userid;

		// Rewrite Passthrough
		if ( !empty( $passthrough ) ) {
			$btnarray['aec_passthrough'] = $passthrough;
		}

		if ( strtolower( $pp->processor_name ) == 'add_to_cart' ) {
			return array_merge( $btnarray, array(
					'option' => 'com_acctexp',
					'task' => 'addtocart',
					'class' => 'btn btn-processor',
					'content' => aecHTML::Icon( 'plus', ' narrow' ) . JText::_('AEC_BTN_ADD_TO_CART'),
					'returngroup' => $return,
					)
			);
		}

		if ( strtolower( $pp->processor_name ) == 'select' ) {
			return array_merge( $btnarray, array(
					'option' => 'com_acctexp',
					'task' => 'subscribe',
					'class' => 'btn btn-processor',
					'content' => JText::_('BUTTON_SELECT'),
					'returngroup' => $return,
				)
			);
		}

		$btnarray['view'] = '';

		if ( $register ) {
			$iscb = aecComponentHelper::detect_component( 'anyCB' );
			$isjs = aecComponentHelper::detect_component( 'JOMSOCIAL' );

			if ( $iscb ) {
				$btnarray['option']	= 'com_comprofiler';
				$btnarray['task']	= 'registers';
				$btnarray['view']	= 'registers';
			} elseif ( $isjs ) {
				$btnarray['option']	= 'com_community';
				$btnarray['view'] 	= 'register';
			} else {
				if ( defined( 'JPATH_MANIFESTS' ) ) {
					$btnarray['option']	= 'com_users';
					$btnarray['task']	= '';
					$btnarray['view'] 	= 'registration';
				} else {
					$btnarray['option']	= 'com_user';
					$btnarray['task']	= '';
					$btnarray['view'] 	= 'register';
				}
			}
		} else {
			$btnarray['option']		= 'com_acctexp';
			$btnarray['task']		= 'confirm';
		}

		$btnarray['class'] = 'btn btn-processor';

		if ( $pp->processor_name == 'free' ) {
			$btnarray['content'] = JText::_('AEC_PAYM_METHOD_FREE');
		} elseif( is_object($pp->processor) ) {
			if ( $pp->processor->getLogoFilename() == '' ) {
				$btnarray['content'] = '<span class="btn-tallcontent">'.$pp->info['longname'].'</span>';
			} else {
				$btnarray['css'] = '.btn-processor-' . $pp->processor_name
												. ' { background-image: url(' . $pp->getLogoPath() .  ') !important; }';
			}
		}

		if ( !empty( $pp->settings['generic_buttons'] ) ) {
			if ( !empty( $pp->recurring ) ) {
				$btnarray['content'] = JText::_('AEC_PAYM_METHOD_SUBSCRIBE');
			} else {
				$btnarray['content'] = JText::_('AEC_PAYM_METHOD_BUYNOW');
			}
		} else {
			$btnarray['class'] .= ' btn-processor-'.$pp->processor_name;

			if ( ( isset( $pp->recurring ) || isset( $pp->info['recurring'] ) ) && !empty( $pp->info['recurring'] ) ) {
				if ( $pp->info['recurring'] == 2 ) {
					if ( !empty( $pp->recurring ) ) {
						$btnarray['content'] = '<i class="btn-overlay">' . JText::_('AEC_PAYM_METHOD_RECURRING_BILLING') . '</i>';
					} else {
						$btnarray['content'] = '<i class="btn-overlay">' . JText::_('AEC_PAYM_METHOD_ONE_TIME_BILLING') . '</i>';
					}
				} elseif ( $pp->info['recurring'] == 1 ) {
					$btnarray['content'] = '<i class="btn-overlay">' . JText::_('AEC_PAYM_METHOD_RECURRING_BILLING') . '</i>';
				}
			}
		}

		if ( !empty( $pp->recurring ) ) {
			$btnarray['recurring'] = 1;
		} else {
			$btnarray['recurring'] = 0;
		}

		$btnarray['processor'] = $pp->processor_name;

		return $btnarray;
	}

	public function checkListProblems()
	{
		// If we run into an Authorization problem, or no plans are available, redirect.
		if ( empty( $this->list ) || is_bool( $this->list ) ) {
			return aecRedirect( AECToolbox::deadsureURL( 'index.php', false, true ), JText::_('NOPLANS_ERROR') );
		} elseif ( is_array( $this->list ) ) {
			return true;
		}

		if ( strpos( $this->list, 'option=com_acctexp' ) ) {
			$this->list .= '&userid=' . $this->metaUser->userid;
		}

		return aecRedirect( $this->list );
	}

	public function explodePlanList( $recurring )
	{
		global $aecConfig;

		$groups	= array();
		$plans	= array();

		$gs = array();
		$ps = array();
		// Break apart groups and items, make sure we have no duplicates
		foreach ( $this->list as $litem ) {
			if ( $litem['type'] == 'group' ) {
				if ( !in_array( $litem['id'], $gs ) ) {
					$gs[] = $litem['id'];
					$groups[] = $litem;
				}
			} else {
				if ( !in_array( $litem['id'], $ps ) ) {

					if ( ItemGroupHandler::checkParentRestrictions( $litem['plan'], 'item', $this->metaUser ) ) {
						$ps[] = $litem['id'];
						$plans[] = $litem;
					}
				}
			}
		}

		foreach ( $plans as $pid => $plan ) {
			if ( !isset( $plan['plan']->params['cart_behavior'] ) ) {
				$plan['plan']->params['cart_behavior'] = 0;
			}

			if ( $this->metaUser->userid
				&& !$this->expired
				&& ( $aecConfig->cfg['enable_shoppingcart']
					|| ( $plan['plan']->params['cart_behavior'] == 1 )
				)
				&& ( $plan['plan']->params['cart_behavior'] != 2 )
				) {
				// We have a shopping cart situation, care about processors later

				if ( ( $plan['plan']->params['processors'] == '' ) || is_null( $plan['plan']->params['processors'] ) ) {
					if ( !$plan['plan']->params['full_free'] ) {
						continue;
					}
				}

				$plans[$pid]['gw'][0]						= new stdClass();
				$plans[$pid]['gw'][0]->processor_name		= 'add_to_cart';
				$plans[$pid]['gw'][0]->info['statement']	= '';
				$plans[$pid]['gw'][0]->recurring			= 0;

				continue;
			}

			if ( empty($plan['select_mode']) ) {
				$select_mode = '0';
			} else {
				$select_mode = $plan['select_mode'];
			}

			if ( $select_mode == 1 ) {
				// We want to only select the processors on confirmation

				if ( ( $plan['plan']->params['processors'] == '' ) || is_null( $plan['plan']->params['processors'] ) ) {
					if ( !$plan['plan']->params['full_free'] ) {
						continue;
					}
				}

				$plans[$pid]['gw'][0]						= new stdClass();
				$plans[$pid]['gw'][0]->processor_name		= 'select';
				$plans[$pid]['gw'][0]->info['statement']	= '';
				$plans[$pid]['gw'][0]->recurring			= 0;

				continue;
			}

			if ( $plan['plan']->params['full_free'] ) {
				$plans[$pid]['gw'][0]						= new stdClass();
				$plans[$pid]['gw'][0]->processor_name		= 'free';
				$plans[$pid]['gw'][0]->info['statement']	= '';
				$plans[$pid]['gw'][0]->recurring			= 0;
			} else {
				if (
					( $plan['plan']->params['processors'] != '' )
					&& !is_null( $plan['plan']->params['processors'] )
				) {
					$processors = array_reverse( $plan['plan']->params['processors'] );

					// Restrict to pre-chosen processor (if set)
					if ( !empty( $this->processor ) ) {
						$processorid = PaymentProcessorHandler::getProcessorIdfromName( $this->processor );
						if ( in_array( $processorid, $processors ) ) {
							$processors = array( $processorid );
						}
					}

					$plan_gw = array();
					if ( count( $processors ) ) {
						foreach ( $processors as $n ) {
							if ( empty( $n ) ) {
								continue;
							}

							$pp = new PaymentProcessor();

							if ( !$pp->loadId( $n ) ) {
								continue;
							}

							if ( !$pp->processor->active ) {
								continue;
							}

							$pp->init();
							$pp->getInfo();

							$pp->exchangeSettingsByPlan( $plan['plan'] );

							$recurring = $pp->is_recurring( $recurring, true );

							if ( $recurring > 1 ) {
								$pp->recurring = 0;
								$plan_gw[] = $pp;

								if ( !$plan['plan']->params['lifetime'] ) {
									$pp = new PaymentProcessor();

									$pp->loadId( $n );
									$pp->init();
									$pp->getInfo();
									$pp->exchangeSettingsByPlan( $plan['plan'] );

									$pp->recurring = 1;
									$plan_gw[] = $pp;
								}
							} elseif ( !( $plan['plan']->params['lifetime'] && $recurring ) ) {
								if ( is_int( $recurring ) ) {
									$pp->recurring	= $recurring;
								}
								$plan_gw[] = $pp;
							}
						}
					}

					if ( !empty( $plan_gw ) ) {
						$plans[$pid]['gw'] = $plan_gw;
					} else {
						unset( $plans[$pid] );
					}
				}
			}
		}

		$this->list = array_merge( $groups, $plans );
	}

}

class SubscriptionPlanHandler
{
	static function getPlanList( $limitstart=false, $limit=false, $use_order=false, $filter=null, $select=false )
	{
		$db = JFactory::getDBO();

		if ( $select ) {
			$query = 'SELECT `id` AS value, `name` AS text FROM #__acctexp_plans';
		} else {
			$query = 'SELECT `id` FROM #__acctexp_plans';
		}

		if ( !empty( $filter ) ) {
			$query .= ' WHERE `id` NOT IN (' . implode( ',', $filter ) . ')';
		}

		$query .= ' ORDER BY ' . ( $use_order ? '`ordering`' : '`id`' );

		if ( !empty( $limitstart ) && !empty( $limit ) ) {
			$query .= 'LIMIT ' . $limitstart . ',' . $limit;
		}

		$db->setQuery( $query );

		if ( $select ) {
			$rows = $db->loadObjectList();
		} else {
			$rows = xJ::getDBArray( $db );
		}

		if ( $db->getErrorNum() ) {
			echo $db->stderr();
			return false;
		} else {
			return $rows;
		}
	}

	static function getActivePlanList( $noplan=true, $prevent_null=true )
	{
		$db = JFactory::getDBO();

		// get entry Plan selection
		$available_plans	= array();
		if ( $noplan ) {
			$available_plans[]	= JHTML::_('select.option', '0', JText::_('PAYPLAN_NOPLAN'), 'value', 'text', $prevent_null );
		}

		$query = 'SELECT `id` AS value, `name` AS text'
				. ' FROM #__acctexp_plans'
				. ' WHERE `active` = \'1\''
				;

		$db->setQuery( $query );
		$dbaplans = $db->loadObjectList();

	 	if ( is_array( $dbaplans ) ) {
	 		$available_plans = array_merge( $available_plans, $dbaplans );
	 	}

		return $available_plans;
	}

	static function getFullPlanList( $limitstart=false, $limit=false, $subselect=array(), $order='ordering', $search='' )
	{
		$db = JFactory::getDBO();


		$where = array();

		if ( isset( $search ) && $search!= '' ) {
			$where[] = "(`name` LIKE '%$search%' OR `desc` LIKE '%$search%')";
		}

		if ( !empty( $subselect ) ) {
			$where[] = "(id IN (" . implode( ',', $subselect ) . "))";
		}

		$query = 'SELECT *'
				. ' FROM #__acctexp_plans'
				. (count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' )
				. ' GROUP BY `id`'
				. ' ORDER BY `' . str_replace(' ', '` ', $order)
			 	;

		if ( ( $limitstart !== false ) && ( $limit !== false ) ) {
			$query .= ' LIMIT ' . $limitstart . ',' . $limit;
		}

		$db->setQuery( $query );

		$rows = $db->loadObjectList();
		if ( $db->getErrorNum() ) {
			echo $db->stderr();
			return false;
		} else {
			return $rows;
		}
	}

	static function getPlanUserCount( $planid )
	{
		$db = JFactory::getDBO();

		$query = 'SELECT count(*)'
				. ' FROM #__users AS a'
				. ' LEFT JOIN #__acctexp_subscr AS b ON a.id = b.userid'
				. ' WHERE b.plan = ' . $planid
				. ' AND (b.status = \'Active\' OR b.status = \'Trial\')'
				;
		$db->setQuery( $query );

		return $db->loadResult();
	}

	static function getPlanUserlist( $planid )
	{
		$db = JFactory::getDBO();

		$db->setQuery(
			'SELECT `userid`'
			. ' FROM #__acctexp_subscr'
			. ' WHERE `plan` = \'' . xJ::escape( $db, $planid ) . '\' AND ( `status` = \'Active\' OR `status` = \'Trial\' )'
		);

		return xJ::getDBArray( $db );
	}

	static function PlanStatus( $planid )
	{
		$plan = new SubscriptionPlan();
		$plan->load( $planid );

		return $plan->active && $plan->checkInventory();
	}

	public function planName( $planid )
	{
		$db = JFactory::getDBO();

		$db->setQuery(
			'SELECT name'
			. ' FROM #__acctexp_plans'
			. ' WHERE `id` = \'' . $planid . '\''
		);
		return $db->loadResult();
	}

	public function listPlans()
	{
		$db = JFactory::getDBO();

		$db->setQuery(
			'SELECT id'
			. ' FROM #__acctexp_plans'
		);

		return xJ::getDBArray( $db );
	}
}

class SubscriptionPlan extends serialParamDBTable
{
	/** @var int Primary key */
	var $id 				= null;
	/** @var int */
	var $active				= null;
	/** @var int */
	var $visible			= null;
	/** @var int */
	var $ordering			= null;
	/** @var string */
	var $name				= null;
	/** @var string */
	var $desc				= null;
	/** @var string */
	var $email_desc			= null;
	/** @var string */
	var $params 			= null;
	/** @var string */
	var $custom_params		= null;
	/** @var string */
	var $restrictions		= null;
	/** @var string */
	var $micro_integrations	= null;

	public function SubscriptionPlan()
	{
		parent::__construct( '#__acctexp_plans', 'id' );
	}

	public function declareParamFields()
	{
		return array( 'params', 'custom_params', 'restrictions', 'micro_integrations' );
	}

	/**
	 * @param string $name
	 */
	public function getProperty( $name )
	{
		if ( isset( $this->$name ) ) {
			return stripslashes( $this->$name );
		} else {
			return null;
		}
	}

	public function checkVisibility( $metaUser )
	{
		if ( !$this->visible ) {
			return false;
		} else {
			return $this->checkPermission( $metaUser );
		}
	}

	public function checkPermission( $metaUser )
	{
		if ( !$this->active ) {
			return false;
		}

		return $this->checkAuthorized( $metaUser ) === true;
	}

	public function checkAuthorized( $metaUser )
	{
		if ( !empty( $this->params['fixed_redirect'] ) ) {
			return $this->params['fixed_redirect'];
		}

		$authorized = $this->checkInventory();

		if ( $authorized ) {
			$restrictions = $this->getRestrictionsArray();

			if ( aecRestrictionHelper::checkRestriction( $restrictions, $metaUser ) !== false ) {
				if ( !ItemGroupHandler::checkParentRestrictions( $this, 'item', $metaUser ) ) {
					$authorized = false;
				}
			} else {
				$authorized = false;
			}
		}

		if ( !$authorized && !empty( $this->params['notauth_redirect'] ) ) {
			return $this->params['notauth_redirect'];
		}

		return $authorized;
	}

	public function checkInventory()
	{
		if ( !empty( $this->restrictions['inventory_amount_enabled'] ) ) {
			if ( $this->restrictions['inventory_amount_used'] >= $this->restrictions['inventory_amount'] ) {
				return false;
			}
		}

		return true;
	}

	public function incrementInventory()
	{
		if ( !empty( $this->restrictions['inventory_amount_enabled'] ) ) {
			$this->restrictions['inventory_amount_used']++;
		}

		return $this->storeload();
	}

	public function applyPlan( $user, $processor = 'none', $silent = 0, $multiplicator = 1, $invoice = null, $tempparams = null )
	{
		global $aecConfig;

		$forcelifetime = false;

		if ( is_string( $multiplicator ) ) {
			if ( strcmp( $multiplicator, 'lifetime' ) === 0 ) {
				$forcelifetime = true;
			}
		} elseif ( is_int( $multiplicator ) && ( $multiplicator < 1 ) ) {
			$multiplicator = 1;
		}

		if ( empty( $user ) ) {
			return false;
		}

		if ( is_object( $user ) ) {
			if ( is_a( $user, 'metaUser' ) ) {
				$metaUser = $user;
			} elseif( is_a( $user, 'Subscription' ) ) {
				$metaUser = new metaUser( $user->userid );

				$metaUser->focusSubscription = $user;
			}
		} else {
			$metaUser = new metaUser( $user );
		}

		if ( !isset( $this->params['make_primary'] ) ) {
			$this->params['make_primary'] = 1;
		}

		$fstatus = $metaUser->establishFocus( $this, $processor, false );

		// TODO: Figure out why $status returns 'existing' - even on a completely fresh subscr

		if ( $fstatus != 'existing' ) {
			$is_pending	= $metaUser->focusSubscription->isPending();
			$is_trial	= $metaUser->focusSubscription->isTrial();
		} else {
			$is_pending	= false;
			$is_trial	= $metaUser->focusSubscription->isTrial();
		}

		$comparison		= $this->doPlanComparison( $metaUser->focusSubscription );
		$renew 			= $metaUser->is_renewing();

		$lifetime		= $metaUser->focusSubscription->lifetime;

		if ( ( $comparison['total_comparison'] === false ) || $is_pending ) {
			// If user is using global trial period he still can use the trial period of a plan
			if ( ( $this->params['trial_period'] > 0 ) && !$is_trial ) {
				$trial		= true;
				$value		= $this->params['trial_period'];
				$perunit	= $this->params['trial_periodunit'];
				$this->params['lifetime']	= 0; // We are entering the trial period. The lifetime will come at the renew.

				$amount		= $this->params['trial_amount'];
			} else {
				$trial		= false;
				$value		= $this->params['full_period'];
				$perunit	= $this->params['full_periodunit'];

				$amount		= $this->params['full_amount'];
			}
		} elseif ( !$is_pending ) {
			$trial		= false;
			$value		= $this->params['full_period'];
			$perunit	= $this->params['full_periodunit'];
			$amount		= $this->params['full_amount'];
		} else {
			return false;
		}

		if ( $this->params['lifetime'] || $forcelifetime ) {
			$metaUser->focusSubscription->expiration = '9999-12-31 00:00:00';
			$metaUser->focusSubscription->lifetime = 1;
		} else {
			$metaUser->focusSubscription->lifetime = 0;

			$value *= $multiplicator;

			if ( ( $comparison['comparison'] == 2 ) && !$lifetime ) {
				$metaUser->focusSubscription->setExpiration( $perunit, $value, 1 );
			} else {
				$metaUser->focusSubscription->setExpiration( $perunit, $value, 0 );
			}
		}

		if ( $is_pending ) {
			// Is new = set signup date
			$metaUser->focusSubscription->signup_date = date( 'Y-m-d H:i:s', ( (int) gmdate('U') ) );
			if ( ( $this->params['trial_period'] ) > 0 && !$is_trial ) {
				$status = 'Trial';
			} else {
				if ( $this->params['full_period'] || $this->params['lifetime'] ) {
					if ( !isset( $this->params['make_active'] ) ) {
						$status = 'Active';
					} else {
						$status = ( $this->params['make_active'] ? 'Active' : 'Pending' );
					}
				} else {
					// This should not happen
					$status = 'Pending';
				}
			}
		} else {
			// Renew subscription - Do NOT set signup_date
			if ( !isset( $this->params['make_active'] ) ) {
				$status = $trial ? 'Trial' : 'Active';
			} else {
				$status = ( $this->params['make_active'] ? ( $trial ? 'Trial' : 'Active' ) : 'Pending' );
			}
		}

		$metaUser->focusSubscription->status = $status;
		$metaUser->focusSubscription->plan = $this->id;

		$metaUser->temporaryRFIX();

		$metaUser->focusSubscription->lastpay_date = date( 'Y-m-d H:i:s', ( (int) gmdate('U') ) );
		$metaUser->focusSubscription->type = $processor;

		if ( is_object( $invoice ) ) {
			if ( !empty( $invoice->params ) ) {
				$tempparam = array();
				if ( !empty( $invoice->params['creator_ip'] ) ) {
					$tempparam['creator_ip'] = $invoice->params['creator_ip'];
				}

				if ( !empty( $tempparam ) ) {
					$metaUser->focusSubscription->addParams( $tempparam, 'params', false );
					$metaUser->focusSubscription->storeload();
				}
			}
		}

		$pp = new PaymentProcessor();
		if ( $pp->loadName( strtolower( $processor ) ) ) {
			$pp->init();
			$pp->getInfo();

			$recurring_choice = null;
			if ( is_object( $invoice ) ) {
				if ( !empty( $invoice->params ) ) {
					if ( isset( $invoice->params["userselect_recurring"] ) ) {
						$recurring_choice = $invoice->params["userselect_recurring"];
					}
				}
			}

			// Check whether we have a custome choice set
			if ( !is_null( $recurring_choice ) ) {
				$metaUser->focusSubscription->recurring = $pp->is_recurring( $recurring_choice );
			} else {
				$metaUser->focusSubscription->recurring = $pp->is_recurring();
			}
		} else {
			$metaUser->focusSubscription->recurring = 0;
		}

		$metaUser->focusSubscription->storeload();

		if ( empty( $invoice->id ) ) {
			$invoice = new stdClass();
			$invoice->amount = $amount;
		}

		$exchange = $add = null;

		$result = $this->triggerMIs( 'action', $metaUser, $exchange, $invoice, $add, $silent );

		if ( $result === false ) {
			return false;
		} elseif ( $result === true ) {
			// MIs might have changed the subscription. Reload it.
			$metaUser->focusSubscription->reload();
		}

		if ( $this->params['gid_enabled'] ) {
			$metaUser->instantGIDchange( $this->params['gid'] );
		}

		$metaUser->focusSubscription->storeload();

		if ( !( $silent || $aecConfig->cfg['noemails'] ) || $aecConfig->cfg['noemails_adminoverride'] ) {
			$adminonly = ( $this->id == $aecConfig->cfg['entry_plan'] ) || ( $aecConfig->cfg['noemails'] && $aecConfig->cfg['noemails_adminoverride'] );

			$metaUser->focusSubscription->sendEmailRegistered( $renew, $adminonly, $invoice );
		}

		$metaUser->meta->addPlanID( $this->id );

		$result = $this->triggerMIs( 'afteraction', $metaUser, $exchange, $invoice, $add, $silent );

		if ( $result === false ) {
			return false;
		}

		$this->incrementInventory();

		return $renew;
	}

	/**
	 * @param boolean $recurring
	 */
	public function getTermsForUser( $recurring, $metaUser )
	{
		if ( $metaUser->hasSubscription ) {
			return $this->getTerms( $recurring, $metaUser->objSubscription, $metaUser );
		} else {
			return $this->getTerms( $recurring, false, $metaUser );
		}
	}

	public function getTerms( $recurring=false, $user_subscription=false, $metaUser=false )
	{
		$plans_comparison		= false;
		$plans_comparison_total	= false;

		if ( is_object( $metaUser ) ) {
			if ( is_object( $metaUser->focusSubscription ) ) {
				$comparison				= $this->doPlanComparison( $metaUser->focusSubscription );
				$plans_comparison		= $comparison['comparison'];
				$plans_comparison_total	= $comparison['total_comparison'];
			}
		} elseif ( is_object( $user_subscription ) ) {
			$comparison				= $this->doPlanComparison( $user_subscription );
			$plans_comparison		= $comparison['comparison'];
			$plans_comparison_total	= $comparison['total_comparison'];
		}

		if ( !isset( $this->params['full_free'] ) ) {
			$this->params['full_free'] = false;
		}

		$allow_trial = ( $plans_comparison === false ) && ( $plans_comparison_total === false );

		$terms = new itemTerms();
		$terms->readParams( $this->params, $allow_trial );

		if ( !$allow_trial && ( count( $terms->terms ) > 1 ) ) {
			$terms->incrementPointer();
		}

		return $terms;
	}

	public function getSimilarPlans()
	{
		if ( empty( $this->params['similarplans'] ) ) {
			return $this->getEqualPlans();
		} else {
			return array_merge( $this->params['similarplans'], $this->getEqualPlans() );
		}
	}

	public function getEqualPlans()
	{
		if ( empty( $this->params['equalplans'] ) ) {
			return array();
		} else {
			return $this->params['equalplans'];
		}
	}

	public function doPlanComparison( $user_subscription )
	{
		$return['total_comparison']	= false;
		$return['comparison']		= false;
		$return['full_comparison']	= true;

		if ( empty( $user_subscription->plan ) ) {
			return $return;
		}

		if ( !empty( $user_subscription->used_plans ) && is_array( $user_subscription->used_plans ) ) {
			$plans_comparison	= false;

			foreach ( $user_subscription->used_plans as $planid => $pusage ) {
				if ( !$planid ) {
					continue;
				}

				if ( empty( $planid ) ) {
					continue;
				}

				$used_subscription = new SubscriptionPlan();
				$used_subscription->load( $planid );

				if ( $this->id === $used_subscription->id ) {
					$used_comparison = 2;
				} elseif ( empty( $this->params['similarplans'] ) && empty( $this->params['equalplans'] ) ) {
					$used_comparison = false;
				} else {
					$used_comparison = $this->compareToPlan( $used_subscription );
				}

				if ( $used_comparison > $plans_comparison ) {
					$plans_comparison = $used_comparison;
				}
				unset( $used_subscription );
			}

			$return['total_comparison'] = $plans_comparison;
		}

		$last_subscription = new SubscriptionPlan();
		$last_subscription->load( $user_subscription->plan );

		if ( $this->id === $last_subscription->id ) {
			$return['comparison'] = 2;
		} else {
			$return['comparison'] = $this->compareToPlan( $last_subscription );
		}

		$return['full_comparison'] = ( ( $return['comparison'] === false ) && ( $return['total_comparison'] === false ) );

		return $return;
	}

	/**
	 * @param SubscriptionPlan $plan
	 */
	public function compareToPlan( $plan )
	{
		$check = array( 'similar', 'equal' );

		foreach ( $check as $c ) {
			if ( empty( $this->params[$c.'plans'] ) ) {
				$this->params[$c.'plans'] = array();
			}

			if ( empty( $plan->params[$c.'plans'] ) ) {
				$plan->params[$c.'plans'] = array();
			}
		}

		$spg1	= $this->params['similarplans'];
		$spg2	= $plan->params['similarplans'];

		$epg1	= $this->params['equalplans'];
		$epg2	= $plan->params['equalplans'];

		if ( empty( $spg1 ) && empty( $spg2 ) && empty( $epg1 ) && empty( $epg2 ) ) {
			return false;
		}

		if ( in_array( $this->id, $epg2 ) || in_array( $plan->id, $epg1 ) ) {
			return 2;
		} elseif ( in_array( $this->id, $spg2 ) || in_array( $plan->id, $spg1 ) ) {
			return 1;
		} else {
			return false;
		}
	}

	public function getMIformParams( $metaUser, $errors=array() )
	{
		$mis = $this->getMicroIntegrations();

		if ( empty( $mis ) ) {
			return null;
		}

		$params = array();
		$lists = array();
		$validation = array();
		$formatting = array();

		foreach ( $mis as $mi_id ) {
			$mi = new MicroIntegration();
			$mi->load( $mi_id );

			if ( !$mi->callIntegration() ) {
				continue;
			}

			$miform_params = $mi->getMIformParams( $this, $metaUser, $errors );

			if ( !empty( $miform_params['lists'] ) ) {
				foreach ( $miform_params['lists'] as $lname => $lcontent ) {
					$lists[$lname] = $lcontent;
				}

				unset( $miform_params['lists'] );
			}

			if ( !empty( $miform_params['validation'] ) ) {
				foreach ( $miform_params['validation'] as $lname => $lcontent ) {
					$validation[$lname] = $lcontent;
				}

				unset( $miform_params['validation'] );
			}

			if ( !empty( $miform_params['formatting'] ) ) {
				foreach ( $miform_params['formatting'] as $lname => $lcontent ) {
					$formatting[$lname] = $lcontent;
				}

				unset( $miform_params['formatting'] );
			}

			foreach ( $miform_params as $pk => $pv ) {
				$params[$pk] = $pv;
			}
		}

		$params['lists'] = $lists;
		$params['validation'] = $validation;
		$params['formatting'] = $formatting;

		return $params;
	}

	public function getMIforms( $metaUser, $errors=array(), $values=array() )
	{
		$params = $this->getMIformParams( $metaUser, $errors );

		if ( empty( $params ) ) {
			return false;
		}

		if ( isset( $params['lists'] ) ) {
			$lists = $params['lists'];
			unset( $params['lists'] );
		} else {
			$lists = array();
		}

		if ( isset( $params['validation'] ) ) {
			unset( $params['validation'] );
		}

		if ( empty( $params ) ) {
			return null;
		}

		$settings = new aecSettings ( 'mi', 'frontend_forms' );
		$settings->fullSettingsArray( $params, $values, $lists, array(), false ) ;

		$aecHTML = new aecHTML( $settings->settings, $settings->lists );

		return $aecHTML->returnFull( false, true );
	}

	public function verifyMIformParams( $metaUser, $params=null )
	{
		$mis = $this->getMicroIntegrations();

		if ( empty( $mis ) ) {
			return true;
		}

		$v = array();
		foreach ( $mis as $mi_id ) {
			$mi = new MicroIntegration();
			$mi->load( $mi_id );

			if ( !$mi->callIntegration() ) {
				continue;
			}

			if ( !is_null( $params ) ) {
				if ( !empty( $params[$this->id][$mi->id] ) ) {
					$verify = $mi->verifyMIform( $this, $metaUser, $params[$this->id][$mi->id] );
				} else {
					$verify = $mi->verifyMIform( $this, $metaUser, array() );
				}
			} else {
				$verify = $mi->verifyMIform( $this, $metaUser );
			}

			if ( !empty( $verify ) && is_array( $verify ) ) {
				$v[] = array_merge( array( 'id' => $mi->id ), $verify );
			}
		}

		if ( empty( $v ) ) {
			return true;
		} else {
			return $v;
		}
	}

	public function storeMIformParams( $metaUser, $params=null )
	{
		$mis = $this->getMicroIntegrations();

		if ( empty( $mis ) ) {
			return true;
		}

		$v = array();
		foreach ( $mis as $mi_id ) {
			$mi = new MicroIntegration();
			$mi->load( $mi_id );

			if ( !$mi->callIntegration() ) {
				continue;
			}

			if ( !is_null( $params ) ) {
				if ( !empty( $params[$this->id][$mi->id] ) ) {
					$verify = $mi->verifyMIform( $this, $metaUser, $params[$this->id][$mi->id] );
				} else {
					$verify = $mi->verifyMIform( $this, $metaUser, array() );
				}
			} else {
				$verify = $mi->verifyMIform( $this, $metaUser );
			}

			if ( !empty( $verify ) && is_array( $verify ) ) {
				$v[] = array_merge( array( 'id' => $mi->id ), $verify );
			}
		}

		if ( empty( $v ) ) {
			return true;
		} else {
			return $v;
		}
	}

	public function getMicroIntegrations( $separate=false )
	{
		if ( empty( $this->micro_integrations ) ) {
			$milist = array();
		} else {
			$milist = $this->micro_integrations;
		}

		// Find parent ItemGroups to attach their MIs
		$parents = ItemGroupHandler::getParents( $this->id );

		foreach ( $parents as $parent ) {
			$g = new ItemGroup();
			$g->load( $parent );

			if ( !empty( $g->params['micro_integrations'] ) ) {
				$milist = array_merge( $milist, $g->params['micro_integrations'] );
			}
		}

		if ( empty( $milist ) ) {
			return false;
		}

		$milist = microIntegrationHandler::getActiveListbyList( $milist );

		if ( empty( $milist ) ) {
			return false;
		}

		return $milist;
	}

	public function getMicroIntegrationsSeparate( $strip_inherited=false )
	{
		if ( empty( $this->micro_integrations ) ) {
			$milist = array();
		} else {
			$milist = $this->micro_integrations;
		}

		// Find parent ItemGroups to attach their MIs
		$parents = ItemGroupHandler::getParents( $this->id );

		$pmilist = array();
		foreach ( $parents as $parent ) {
			$g = new ItemGroup();
			$g->load( $parent );

			if ( !empty( $g->params['micro_integrations'] ) ) {
				$pmilist = array_merge( $pmilist, $g->params['micro_integrations'] );
			}
		}

		$return = array( 'plan' => array(), 'inherited' => array() );

		if ( empty( $milist ) && empty( $pmilist ) ) {
			return $return;
		}

		$milist = microIntegrationHandler::getActiveListbyList( $milist );
		$pmilist = microIntegrationHandler::getActiveListbyList( $pmilist );

		if ( empty( $milist ) && empty( $pmilist ) ) {
			return $return;
		}

		// Remove entries from the plan MIs that are already inherited
		if ( !empty( $pmilist ) && !empty( $milist ) && $strip_inherited ) {
			$theintersect = array_intersect( $pmilist, $milist );

			if ( !empty( $theintersect ) ) {
				foreach ( $theintersect as $value ) {
					// STAY IN THE CAR
					unset( $milist[array_search( $value, $milist )] );
				}
			}
		}

		return array( 'plan' => $milist, 'inherited' => $pmilist );
	}

	/**
	 * @param string $action
	 */
	public function triggerMIs( $action, &$metaUser, &$exchange, &$invoice, &$add, &$silent )
	{
		global $aecConfig;

		$micro_integrations = $this->getMicroIntegrations();

		if ( !is_array( $micro_integrations ) ) {
			return null;
		}

		foreach ( $micro_integrations as $mi_id ) {
			$mi = new microIntegration();

			if ( !$mi->mi_exists( $mi_id ) ) {
				continue;
			}

			$mi->load( $mi_id );

			if ( !$mi->callIntegration() ) {
				continue;
			}

			// TODO: Only trigger if this is not email or made not silent
			if ( method_exists( $metaUser, $action ) ) {
				if ( $mi->$action( $metaUser, $exchange, $invoice, $this ) === false ) {
					if ( $aecConfig->cfg['breakon_mi_error'] ) {
						return false;
					}
				}
			} else {
				$params = array();
				if ( isset( $invoice->params['userMIParams'] ) ) {
					if ( is_array( $invoice->params['userMIParams'] ) ) {
						if ( isset( $invoice->params['userMIParams'][$this->id][$mi->id] ) ) {
							$params = $invoice->params['userMIParams'][$this->id][$mi->id];
						}
					}
				}

				if ( $mi->relayAction( $metaUser, $exchange, $invoice, $this, $action, $add, $params ) === false ) {
					if ( $aecConfig->cfg['breakon_mi_error'] ) {
						return false;
					}
				}
			}
		}

		return true;
	}

	public function getProcessorParameters( $processor )
	{
		if ( empty( $this->custom_params ) ) {
			return array();
		}

		$procparams = array();
		$filter = array();

		if ( empty( $this->custom_params[$processor->id.'_aec_overwrite_settings'] ) ) {
			if ( method_exists( $processor->processor, 'CustomPlanParams' ) ) {
				$filter = $processor->processor->CustomPlanParams();
			}

			if ( empty( $filter ) ) {
				return $procparams;
			}
		}

		foreach ( $this->custom_params as $name => $value ) {
			$realname = explode( '_', $name, 2 );

			if ( !empty( $filter ) ) {
				if ( !array_key_exists( $realname[1], $filter ) ) {
					continue;
				}
			}

			if ( ( $realname[0] == $processor->id ) && isset( $realname[1] ) ) {
				$procparams[$realname[1]] = $value;
			}
		}

		return $procparams;
	}

	public function getRestrictionsArray()
	{
		return aecRestrictionHelper::getRestrictionsArray( $this->restrictions );
	}

	public function savePOSTsettings( $post )
	{
		if ( !empty( $post['id'] ) ) {
			$planid = $post['id'];
		} else {
			// Fake knowing the planid if is zero.
			$planid = $this->getMax() + 1;
		}

		if ( isset( $post['id'] ) ) {
			unset( $post['id'] );
		}

		if ( isset( $post['inherited_micro_integrations'] ) ) {
			unset( $post['inherited_micro_integrations'] );
		}

		if ( !empty( $post['add_group'] ) ) {
			ItemGroupHandler::setChildren( $post['add_group'], array( $planid ) );
			unset( $post['add_group'] );
		}

		if ( empty( $post['micro_integrations'] ) ) {
			$post['micro_integrations'] = array();
		}

		if ( !empty( $post['micro_integrations_plan'] ) ) {
			foreach ( $post['micro_integrations_plan'] as $miname ) {
				// Create new blank MIs
				$mi = new microIntegration();
				$mi->load(0);

				$mi->class_name = $miname;

				if ( !$mi->callIntegration( true ) ) {
					continue;
				}

				$mi->hidden = 1;

				$mi->storeload();

				// Add in new MI id
				$post['micro_integrations'][] = $mi->id;
			}

			$mi->reorder();

			unset( $post['micro_integrations_plan'] );
		}

		if ( !empty( $post['micro_integrations_hidden'] ) ) {
			// Recover hidden MI relation to full list
			$post['micro_integrations'] = array_merge( $post['micro_integrations'], $post['micro_integrations_hidden'] );

			unset( $post['micro_integrations_hidden'] );
		}

		if ( !empty( $post['micro_integrations_inherited'] ) ) {
			unset( $post['micro_integrations_inherited'] );
		}

		// Update MI settings
		foreach ( $post['micro_integrations'] as $miid ) {
			$mi = new microIntegration();
			$mi->load( $miid );

			// Only act special on hidden MIs
			if ( !$mi->hidden ) {
				continue;
			}

			$prefix = 'MI_' . $miid . '_';

			// Get Settings from post array
			$settings = array();
			foreach ( $post as $name => $value ) {
				if ( strpos( $name, $prefix ) === 0 ) {
					$rname = str_replace( $prefix, '', $name );

					$settings[$rname] = $value;
					unset( $post[$name] );
				}
			}

			// If we indeed HAVE settings, more to come here
			if ( empty( $settings ) ) {
				continue;
			}

			$mi->savePostParams( $settings );

			// First, check whether there is already an MI with the exact same settings
			$similarmis = microIntegrationHandler::getMIList( false, false, true, false, $mi->classname );

			$similarmi = false;
			if ( !empty( $similarmis ) ) {
				foreach ( $similarmis as $miobj ) {
					if ( $miobj->id == $mi->id ) {
						continue;
					}

					if ( microIntegrationHandler::compareMIs( $mi, $miobj->id ) ) {
						$similarmi = $miobj->id;
					}
				}
			}

			if ( $similarmi ) {
				// We have a similar MI - unset old reference
				$ref = array_search( $mi->id, $post['micro_integrations'] );
				unset( $post['micro_integrations'][$ref] );

				// No MI is similar, lets check for other plans
				$plans = microIntegrationHandler::getPlansbyMI( $mi->id );

				foreach ( $plans as $cid => $pid ) {
					if ( $pid == $this->id ) {
						unset( $plans[$cid] );
					}
				}

				if ( count( $plans ) <= 1 ) {
					// No other plan depends on this MI, just delete it
					$mi->delete();
				}

				// Set new MI
				$post['micro_integrations'][] = $similarmi;
			} else {
				// No MI is similar, lets check for other plans
				$plans = microIntegrationHandler::getPlansbyMI( $mi->id );

				foreach ( $plans as $cid => $pid ) {
					if ( $pid == $this->id ) {
						unset( $plans[$cid] );
					}
				}

				if ( count( $plans ) > 1 ) {
					// We have other plans depending on THIS setup of the MI, unset original reference
					$ref = array_search( $mi->id, $post['micro_integrations'] );
					unset( $post['micro_integrations'][$ref] );

					// And create new MI
					$mi->id = 0;

					$mi->storeload();

					// Set new MI
					$post['micro_integrations'][] = $mi->id;
				} else {
					$mi->storeload();
				}
			}
		}

		// Filter out fixed variables
		$fixed = array( 'active', 'visible', 'name', 'desc', 'email_desc', 'micro_integrations' );

		foreach ( $fixed as $varname ) {
			if ( isset( $post[$varname] ) ) {
				$this->$varname = $post[$varname];

				unset( $post[$varname] );
			} else {
				$this->$varname = '';
			}
		}

		// Get selected processors ( have to be filtered out )

		$processors = array();
		foreach ( $post as $key => $value ) {
			if ( $key == 'processor_selectmode' ) continue;

			if ( ( strpos( $key, 'processor_' ) === 0 ) && $value ) {
				$ppid = str_replace( 'processor_', '', $key );

				if ( !in_array( $ppid, $processors ) ) {
					$processors[] = $ppid;
					unset( $post[$key] );
				}
			}
		}

		// Filter out params
		$fixed = array( 'full_free', 'full_amount', 'full_period', 'full_periodunit',
						'trial_free', 'trial_amount', 'trial_period', 'trial_periodunit',
						'gid_enabled', 'gid', 'lifetime', 'standard_parent',
						'fallback', 'fallback_req_parent', 'similarplans', 'equalplans', 'make_active',
						'make_primary', 'update_existing', 'customthanks', 'customtext_thanks_keeporiginal',
						'customamountformat', 'customtext_thanks', 'override_activation', 'override_regmail',
						'notauth_redirect', 'fixed_redirect', 'hide_duration_checkout', 'addtocart_redirect',
						'addtocart_max', 'cart_behavior', 'notes', 'meta', 'processor_selectmode'
						);

		$params = array();
		foreach ( $fixed as $varname ) {
			if ( !isset( $post[$varname] ) ) {
				continue;
			}

			$params[$varname] = $post[$varname];

			unset( $post[$varname] );
		}

		$params['processors'] = $processors;

		$this->saveParams( $params );

		// Filter out restrictions
		$fixed = aecRestrictionHelper::paramList();

		$fixed = array_merge( $fixed, array( 'inventory_amount_enabled', 'inventory_amount', 'inventory_amount_used' ) );

		$restrictions = array();
		foreach ( $fixed as $varname ) {
			if ( !isset( $post[$varname] ) ) {
				continue;
			}

			$restrictions[$varname] = $post[$varname];

			unset( $post[$varname] );
		}

		$this->restrictions = $restrictions;

		// There might be deletions set for groups
		foreach ( $post as $varname => $content ) {
			if ( ( strpos( $varname, 'group_delete_' ) !== false ) && $content ) {
				$parentid = (int) str_replace( 'group_delete_', '', $varname );

				ItemGroupHandler::removeChildren( $planid, array( $parentid ) );

				unset( $post[$varname] );
			}
		}

		// The rest of the vars are custom params
		$custom_params = array();
		foreach ( $post as $varname => $content ) {
			if ( substr( $varname, 0, 4 ) != 'mce_' ) {
				$custom_params[$varname] = $content;
			}
			unset( $post[$varname] );
		}

		$this->custom_params = $custom_params;
	}

	public function saveParams( $params )
	{
		// If the admin wants this to be a free plan, we have to make this more explicit
		// Setting processors to zero and full_free
		if ( $params['full_free'] && ( $params['processors'] == '' ) ) {
			$params['processors']	= '';
		} elseif ( !$params['full_amount'] || ( $params['full_amount'] == '0.00' ) || ( $params['full_amount'] == '' ) ) {
			$params['full_free']	= 1;
			$params['processors']	= '';
		}

		// Correct a malformed Full Amount
		if ( !strlen( $params['full_amount'] ) ) {
			$params['full_amount']	= '0.00';
			$params['full_free']	= 1;
			$params['processors']	= '';
		} else {
			$params['full_amount'] = AECToolbox::correctAmount( $params['full_amount'] );
		}

		// Correct a malformed Trial Amount
		if ( strlen( $params['trial_amount'] ) ) {
			$params['trial_amount'] = AECToolbox::correctAmount( $params['trial_amount'] );
		}

		// Prevent setting Trial Amount to 0.00 if no free trial was asked for
		if ( !$params['trial_free'] && ( strcmp( $params['trial_amount'], "0.00" ) === 0 ) ) {
			$params['trial_amount'] = '';
		}

		$this->params = $params;
	}

	public function copy()
	{
		$pid = $this->id;

		$this->id = 0;
		$this->storeload();

		$parents = ItemGroupHandler::parentGroups( $pid, 'item' );

		foreach ( $parents as $parentid ) {
			ItemGroupHandler::setChild( $this->id, $parentid, 'item' );
		}
	}

	public function delete( $pk=null )
	{
		ItemGroupHandler::removeChildren( $this->id );

		return parent::delete($pk);
	}

}
