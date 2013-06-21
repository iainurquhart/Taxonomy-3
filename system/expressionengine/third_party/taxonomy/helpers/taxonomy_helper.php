<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// ------------------------------------------------------------------------

/**
 * Taxonomy Helper
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Iain Urquhart
 * @link		http://iain.co.nz
 * @copyright 	Copyright (c) 2012 Iain Urquhart
 * @license   	Commercial, All Rights Reserved: http://devot-ee.com/add-ons/license/taxonomy/
 */

// ------------------------------------------------------------------------

/**
 * Checks user has access to a Taxonomy Tree
 *
 * @access	public
 * @param	current member group_id (int)
 * @param	allowed group_ids (string 2|3|4 or array)
 * @return	TRUE / FALSE
 */

function has_access_to_tree( $group_id, $allowed_group_ids)
{
	
	// make sure permissions is an array
	$allowed_group_ids = (is_array($allowed_group_ids)) ? $allowed_group_ids : explode('|', $allowed_group_ids);
	
	// check for current member group in allowed group, always grant superadmin access
	return (in_array($group_id, $allowed_group_ids) OR $group_id == 1) ? TRUE : FALSE;

}


/**
 * Simple debugging utilitiy for arrays
 *
 * @access	public
 * @param	array
 * @return	fancy
 */
function debug_array($array) 
{
    echo "<pre>"; print_r($array); echo "</pre>"; 
}


// ----------------------------------------------------------------

// ----------------------------------------------------------------------
	
	/**
	 * Inserts our Taxonomy nav array into the main nav array at a specific position
	 *
	 * @return array
	 */
	
	function array_insert($arr, $insert, $position) 
	{
		$i = 0;
    	foreach ($arr as $key => $value) 
    	{
            if ($i == $position) 
            {
                    foreach ($insert as $ikey => $ivalue) 
                    {
                            $ret[$ikey] = $ivalue;
                    }
            }
            $ret[$key] = $value;
            $i++;
    	}
    	return $ret;
	}