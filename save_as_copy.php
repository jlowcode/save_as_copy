<?php
/**
 * Update / insert a database record into any table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.save_as_copy
 * @copyright   Copyright (C) 2024 Jlowcode Org. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Update / insert a database record into any table
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.save_as_copy
 * @since       3.0.7
 */
class PlgFabrik_FormSave_as_copy extends PlgFabrik_Form
{
	/**
	 * Database driver
	 *
	 * @var JDatabaseDriver
	 */
	protected $upsertDb = null;

	/**
	 * process the plugin, called after form is submitted
	 *
	 * @return  bool
	 */
	public function onAfterProcess()
	{		
		$data = $_REQUEST;
		if (array_key_exists('Copy', $data)) {
			$formModel = $this->getModel();
			$listModel = $formModel->getlistModel();
			$table     = $listModel->getTable();
			$pk = $table->db_primary_key;
			$table_name = $table->db_table_name;
			$new_id_copy = $_REQUEST['rowid'];

			date_default_timezone_set('UTC');
			$created_date = date("Y-m-d H:i:s");

			$created_date_element_name = $this->getCreated_date_element_name();

			$db = FabrikWorker::getDbo();
			$query = $db->getQuery(true);
			$query->set("{$created_date_element_name} = " . $db->quote($created_date));
			$query->update("{$table_name}")->where("{$pk} = " . $db->quote($new_id_copy));
			$db->setQuery($query);
			try
			{
				$db->execute();
				$input = $this->app->input;
				if (isset($_GET["rowid"])){
					//http://educett/index.php?option=com_fabrik&view=form&formid=19&rowid=19062
					$u = str_replace('rowid=' . $_GET["rowid"], 'rowid=' . $new_id_copy, $_SERVER["REQUEST_URI"]);				
				} else {
					//http://educett/protocolo/form/19/19062
					//http://educett/component/fabrik/form/19/19062
					//http://educett/component/fabrik/form/19/19062?Itemid=
					$u = str_replace("/form/{$_POST['listid']}/{$_POST['rowid']}", "/form/{$_POST['listid']}/" . $new_id_copy, $_SERVER['REQUEST_URI']);
				}			
				
				if ($this->config->get('sef')) {
					$url = JRoute::_($u);
					$input->post->set('fabrik_referrer', $url);
				}
			}
			catch (Exception $e)
			{
				$ok = false;
			}
		}
	}
	/**
	 * Get getCreated_date_element_name
	 *
	 * @return JDatabaseDriver
	 */
	protected function getCreated_date_element_name()
	{
		$created_date_element = $this->params->get('save_as_copy_created_date_element');
		if(isset($created_date_element) && !empty($created_date_element)) {
			// Get the DB object
			$db = JFactory::getDbo();
			// Get a new query object
			$query = $db->getQuery(true);
			$query->select($db->quoteName('name'))
				->from($db->quoteName('#__fabrik_elements'))
				->where('id = '. $created_date_element);
			$db->setQuery($query);
			$db->execute();
			// Get the query results
			$r = $db->loadObjectList();
			$created_date_element_name = $r[0]->name;
		} 
		return $created_date_element_name;
	}
}