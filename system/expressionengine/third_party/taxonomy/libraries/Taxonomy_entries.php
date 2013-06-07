<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
require_once(PATH_MOD.'channel/mod.channel.php');
 
class Taxonomy_entries extends Channel {

    // -----------------------------------------------------------------
     
    /**
     * Constructor function
     */
    function __construct() 
    {
        parent::Channel();

    }
     
    // -----------------------------------------------------------------
     
    /**
     * entries
     * 
     * Adjust channel:entries parameters before it runs
     * 
     * @return string 
     */
    public function entries()
	{
		ee()->TMPL->tagparams['dynamic'] = 'no';
		return parent::entries();
    } 
 
}