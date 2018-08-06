<?php

/**
 * Locations - Location Plugin for Contao
 *
 * Copyright (C) 2018 Andrew Stevens
 *
 * @package    asconsulting/locations
 * @link       http://andrewstevens.consulting
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

  
namespace Asc\Module;
 
use Asc\Model\Location;
use Asc\Locations; 
 
class ModuleRetailLocationsList extends \Contao\Module
{
 
    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_locations_list';
 
	protected $arrStates = array();
 
	/**
	 * Initialize the object
	 *
	 * @param \ModuleModel $objModule
	 * @param string       $strColumn
	 */
	public function __construct($objModule, $strColumn='main')
	{
		parent::__construct($objModule, $strColumn);
		$this->arrStates = Locations::getStates();
	}
	
    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new \BackendTemplate('be_wildcard');
 
            $objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['retail_locations_list'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&table=tl_module&act=edit&id=' . $this->id;
 
            return $objTemplate->parse();
        }
 
        return parent::generate();
    }
 
 
    /**
     * Generate the module
     */
    protected function compile()
    {
		$objLocation = Location::findBy('published', '1');
		
		if (!in_array('system/modules/locations/assets/js/locations.js', $GLOBALS['TL_JAVASCRIPT'])) { 
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/locations/assets/js/locations.js';
		}
		
		// Return if no pending items were found
		if (!$objLocation)
		{
			$this->Template->empty = 'No Locations Found';
			return;
		}

		$arrStates = array();
		
		// Generate List
		while ($objLocation->next())
		{
			$strStateKey = $objLocation->state;
			$strStateName = ($this->arrStates["United States"][$objLocation->state] != '' ? $this->arrStates["United States"][$objLocation->state] : $this->arrStates["Canada"][$objLocation->state]);
			if (in_array($objLocation->state, array('AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'))) {
				$strStateKey = 'CAN';
				$strStateName = 'Canada - All Provinces';
			}
			
			if (!array_key_exists($strStateKey, $arrStates)) {
				$arrStates[$strStateKey] = array(
					"name" 			=> $strStateName,
					"abbr"			=> $strStateKey,
					"locations"		=> array()
				);
			}
			
			$arrLocation = array(
				'id'		=> $objLocation->id,
				'alias'		=> $objLocation->alias,
				'tstamp'	=> $objLocation->tstamp,
				'timetamp'	=> \Date::parse(\Config::get('datimFormat'), $objLocation->tstamp),
				'published' => $objLocation->published
			);
			
			if ($this->jumpTo) {
				$objTarget = $this->objModel->getRelated('jumpTo');
				$arrLocation['link'] = $this->generateFrontendUrl($objTarget->row()) .'?alias=' .$objLocation->alias;
			}
			
			$arrLocation['name'] 	= $objLocation->name;
			$arrLocation['address'] = $objLocation->address;
			$arrLocation['city'] 	= $objLocation->city;
			$arrLocation['state'] 	= $objLocation->state;
			$arrLocation['zip'] 	= $objLocation->zip;
			$arrLocation['country'] = $objLocation->country;
			$arrLocation['phone'] 	= $objLocation->phone;

			$strItemTemplate = ($this->retail_locations_customItemTpl != '' ? $this->retail_locations_customItemTpl : 'item_retail_locations');
			$objTemplate = new \FrontendTemplate($strItemTemplate);
			$objTemplate->setData($arrLocation);
			$arrStates[$strStateKey]['locations'][] = $objTemplate->parse();
		}

		$arrTemp = $arrStates;
		unset($arrTemp['CAN']);
		uasort($arrTemp, array($this,'sortByState'));
		$arrTemp['CAN'] = $arrStates['CAN'];
		$arrStates = $arrTemp;
		
		$this->Template->stateOptions = $this->generateSelectOptions();
		$this->Template->states = $arrStates;
		
	}

	public function generateSelectOptions($blank = TRUE) {
		$strUnitedStates = '<optgroup label="United States">';
		$strCanada = '<optgroup label="Canada"><option value="CAN">All Provinces</option></optgroup>';
		foreach ($this->arrStates['United States'] as $abbr => $state) {
			if (!in_array($objLocation->state, array('AB','BC','MB','NB','NL','NS','NT','NU','ON','PE','QC','SK','YT'))) {
				$strUnitedStates .= '<option value="' .$abbr .'">' .$state .'</option>';
			}
		}
		$strUnitedStates .= '</optgroup>';
		return ($blank ? '<option value="">Select Location...</option>' : '') .$strUnitedStates .$strCanada;
	}
	
	function sortByState($a, $b) {
		if ($a['Name'] == $b['Name']) {
			return 0;
		}
		return ($a['Name'] < $b['Name']) ? -1 : 1;
	}
	
} 
