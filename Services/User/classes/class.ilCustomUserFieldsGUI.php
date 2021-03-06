<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once './Services/User/classes/class.ilUserDefinedFields.php';	

/**
* Class ilCustomUserFieldsGUI
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
* @version $Id: class.ilObjUserFolderGUI.php 30361 2011-08-25 11:05:41Z jluetzen $
* 
* @ilCtrl_Calls ilCustomUserFieldsGUI: 
*
* @ingroup ServicesUser
*/
class ilCustomUserFieldsGUI 
{
	protected $confirm_change; // [bool]
	protected $field_id; // [int]
	protected $field_definition; // [array]
	
	function __construct()
	{
		global $lng, $ilCtrl;
		
		$lng->loadLanguageModule("user");		
		$lng->loadLanguageModule("administration");		
		
		$this->field_id = $_REQUEST["field_id"];
		$ilCtrl->saveParameter($this, "field_id", $this->field_id);
		
		if($this->field_id)
		{			
			$user_field_definitions = ilUserDefinedFields::_getInstance();
			$this->field_definition = $user_field_definitions->getDefinition($this->field_id);
		}
	}
	
	function &executeCommand()
	{
		global $ilCtrl;
		
		$next_class = $ilCtrl->getNextClass($this);
		$cmd = $ilCtrl->getCmd();
		
		switch($next_class)
		{
			default:
				if(!$cmd)
				{
					$cmd = "listUserDefinedFields";
				}				
				$this->$cmd();
				break;
		}
		return true;
	}

	/**
	 * List all custom user fields
	 */
	function listUserDefinedFields()
	{
		global $lng, $tpl, $ilToolbar, $ilCtrl;
				
		$ilToolbar->addButton($lng->txt("add_user_defined_field"),
			$ilCtrl->getLinkTarget($this, "addField")); 
		
		include_once("./Services/User/classes/class.ilCustomUserFieldSettingsTableGUI.php");
		$tab = new ilCustomUserFieldSettingsTableGUI($this, "listUserDefinedFields");
		if($this->confirm_change) 
		{
			$tab->setConfirmChange();
		}
		$tpl->setContent($tab->getHTML());
	}
	
	/**
	 * Add field
	 * 
	 * @param ilPropertyFormGUI $a_form 
	 */
	function addField($a_form = null)
	{
		global $tpl;		
		
		if(!$a_form)
		{
			$a_form = $this->initForm();
		}
		
		$tpl->setContent($a_form->getHTML());
	}
	
	/**
	 * Get all access options, order is kept in forms
	 * 
	 * @return array
	 */
	function getAccessOptions()
	{	
		global $lng;
		
		$opts = array();
		$opts["visible"] = $lng->txt("user_visible_in_profile");
		$opts["visib_reg"] = $lng->txt("visible_registration");
		$opts["visib_lua"] = $lng->txt("usr_settings_visib_lua");
		$opts["course_export"] = $lng->txt("course_export");
		$opts["group_export"] = $lng->txt("group_export");
		$opts["changeable"] = $lng->txt("changeable");
		$opts["changeable_lua"] = $lng->txt("usr_settings_changeable_lua");
		$opts["required"] = $lng->txt("required_field");
		$opts["export"] = $lng->txt("export");
		$opts["searchable"] = $lng->txt("header_searchable");
		$opts["certificate"] = $lng->txt("certificate");		
		return $opts;		
	}
	
	/**
	 * Init field form
	 * 
	 * @param string $a_mode 
	 * @return ilPropertyFormGUI
	 */
	function initForm($a_mode = "create")
	{
		global $ilCtrl, $lng;
				
		include_once("Services/Membership/classes/class.ilMemberAgreement.php");
	 	if (ilMemberAgreement::_hasAgreements())
	 	{
			$lng->loadLanguageModule("ps");
			ilUtil::sendInfo($lng->txt("ps_warning_modify"));
	 	}
		
		include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
		$form = new ilPropertyFormGUI();
		$form->setFormAction($ilCtrl->getFormAction($this));
		
		$name = new ilTextInputGUI($lng->txt("field_name"), "name");
		$name->setRequired(true);
		$form->addItem($name);
		
		// type
		$radg = new ilRadioGroupInputGUI($lng->txt("field_type"), "field_type");
		$radg->setRequired(true);		
			$op1 = new ilRadioOption($lng->txt("udf_type_text"), UDF_TYPE_TEXT);
			$radg->addOption($op1);
			$op2 = new ilRadioOption($lng->txt("udf_type_select"), UDF_TYPE_SELECT);
			$radg->addOption($op2);
			$op3 = new ilRadioOption($lng->txt("udf_type_wysiwyg"), UDF_TYPE_WYSIWYG);
			$radg->addOption($op3);
		$form->addItem($radg);
		
		// select values
		$se_mu = new ilTextWizardInputGUI($lng->txt("value"), "selvalue");
		$se_mu->setRequired(true);
		$se_mu->setSize(32);
		$se_mu->setMaxLength(128);
		$se_mu->setValues(array(''));		
		$op2->addSubItem($se_mu);
						
		// access
		$acc = new ilCheckboxGroupInputGUI($lng->txt("access"), "access");
			
			$acc_values = array();
			foreach($this->getAccessOptions() as $id => $caption)
			{
				$opt = new ilCheckboxOption($caption, $id);
				$acc->addOption($opt);
				
				if($this->field_definition && $this->field_definition[$id])
				{
					$acc_values[] = $id;
				}
			}
		
		$form->addItem($acc);
				
		if($a_mode == "create")
		{
			$radg->setValue(UDF_TYPE_TEXT);
			
			
			$form->setTitle($lng->txt('add_new_user_defined_field'));
									
			$form->addCommandButton("create", $lng->txt("save"));
			$form->addCommandButton("listUserDefinedFields", $lng->txt("cancel"));
		}
		else
		{			
			$name->setValue($this->field_definition["field_name"]);
			$radg->setValue($this->field_definition["field_type"]);
			$radg->setDisabled(true);
			$acc->setValue($acc_values);
			
			switch($this->field_definition["field_type"])
			{
				case UDF_TYPE_SELECT:
					$se_mu->setValue($this->field_definition["field_values"]);
					$form->setTitle($lng->txt("udf_update_select_field"));
					break;
				
				case UDF_TYPE_TEXT:
					$form->setTitle($lng->txt("udf_update_text_field"));
					break;
				
				case UDF_TYPE_WYSIWYG:
					$form->setTitle($lng->txt("udf_update_wysiwyg_field"));
					break;
			}
			
			$form->addCommandButton("update", $lng->txt("save"));
			$form->addCommandButton("listUserDefinedFields", $lng->txt("cancel"));
		}
		
		return $form;		
	}
	
	/**
	 * Validate field form
	 * 
	 * @param ilPropertyFormGUI $form 
	 * @param ilUserDefinedFields $user_field_definitions 
	 * @param array $access 
	 * @return bool
	 */
	protected function validateForm($form, $user_field_definitions, array &$access)
	{
		global $lng;
		
		if($form->checkInput())
		{
			$valid = true;	
							
			$incoming = (array)$form->getInput("access");
			$access = array();
			foreach(array_keys($this->getAccessOptions()) as $id)
			{
				$access[$id] = in_array($id, $incoming);
			}

			if($access['required'] && !$access['visib_reg'])
			{
				$this->confirm_change = true;				
				$form->getItemByPostVar("access")->setAlert($lng->txt('udf_required_requires_visib_reg'));	
				$valid = false;
			}

			if(!$this->field_id && $user_field_definitions->nameExists($form->getInput("name")))
			{
				$form->getItemByPostVar("name")->setAlert($lng->txt('udf_name_already_exists'));	
				$valid = false;
			}
	
			if($form->getInput("field_type") == UDF_TYPE_SELECT)
			{
				$user_field_definitions->setFieldValues($form->getInput("selvalue"));
				if($error = $user_field_definitions->validateValues())
				{
					switch($error)
					{
						case UDF_DUPLICATE_VALUES:
							$form->getItemByPostVar("selvalue")->setAlert($lng->txt('udf_duplicate_entries'));	
							$valid = false;
							break;
					}
				}
			}		
			
			if(!$valid)
			{
				ilUtil::sendFailure($lng->txt("form_input_not_valid"));
			}
			return $valid;
		}
		
		return false;
	}	
		
	function create()
	{
		global $lng, $ilCtrl;
		
		$user_field_definitions = ilUserDefinedFields::_getInstance();
		$user_field_definitions->setFieldType($_POST["field_type"]);
		
		$access = array();
		$form = $this->initForm();				
		if($this->validateForm($form, $user_field_definitions, $access))
		{
			$user_field_definitions->setFieldName($form->getInput("name"));						
			$user_field_definitions->enableVisible($access['visible']);
			$user_field_definitions->enableVisibleRegistration((int)$access['visib_reg']);
			$user_field_definitions->enableVisibleLocalUserAdministration($access['visib_lua']);
			$user_field_definitions->enableCourseExport($access['course_export']);
			$user_field_definitions->enableGroupExport($access['group_export']);
			$user_field_definitions->enableChangeable($access['changeable']);				
			$user_field_definitions->enableChangeableLocalUserAdministration($access['changeable_lua']);
			$user_field_definitions->enableRequired($access['required']);					
			$user_field_definitions->enableExport($access['export']);
			$user_field_definitions->enableSearchable($access['searchable']);
			$user_field_definitions->enableCertificate($access['certificate']);
			$user_field_definitions->add();

			if ($access['course_export'])
			{
				include_once('Services/Membership/classes/class.ilMemberAgreement.php');
				ilMemberAgreement::_reset();			
			}

			ilUtil::sendSuccess($lng->txt('udf_added_field'), true);
			$ilCtrl->redirect($this);
		}
		
		$form->setValuesByPost();
		$this->addField($form);	
	}
		
	/**
	 * Edit field
	 * 
	 * @param ilPropertyFormGUI $a_form 
	 */
	function edit($a_form = null)
	{
		global $tpl;		
		
		if(!$a_form)
		{
			$a_form = $this->initForm("edit");
		}
		
		$tpl->setContent($a_form->getHTML());
	}		
	
	function update()
	{
		global $lng, $ilCtrl;
		
		$user_field_definitions = ilUserDefinedFields::_getInstance();
		$user_field_definitions->setFieldType($this->field_definition["field_type"]);
		
		$access = array();
		$form = $this->initForm("edit");				
		if($this->validateForm($form, $user_field_definitions, $access) && $this->field_id)
		{
			// field values are set in validateForm()...
						
			$user_field_definitions->setFieldName($form->getInput("name"));								
			$user_field_definitions->enableVisible($access['visible']);
			$user_field_definitions->enableVisibleRegistration((int)$access['visib_reg']);
			$user_field_definitions->enableVisibleLocalUserAdministration($access['visib_lua']);
			$user_field_definitions->enableCourseExport($access['course_export']);
			$user_field_definitions->enableGroupExport($access['group_export']);
			$user_field_definitions->enableChangeable($access['changeable']);				
			$user_field_definitions->enableChangeableLocalUserAdministration($access['changeable_lua']);
			$user_field_definitions->enableRequired($access['required']);					
			$user_field_definitions->enableExport($access['export']);
			$user_field_definitions->enableSearchable($access['searchable']);
			$user_field_definitions->enableCertificate($access['certificate']);
			$user_field_definitions->update($this->field_id);

			if ($access['course_export'])
			{
				include_once('Services/Membership/classes/class.ilMemberAgreement.php');
				ilMemberAgreement::_reset();			
			}

			ilUtil::sendSuccess($lng->txt('udf_added_field'), true);
			$ilCtrl->redirect($this);
		}
		
		$form->setValuesByPost();
		$this->edit($form);	
	}
		
	function askDeleteField()
	{
		global $ilCtrl, $lng, $tpl;
		
		if(!$_POST["fields"])
		{
			ilUtil::sendFailure($lng->txt("select_one"));
			return $this->listUserDefinedFields();
		}
	
		include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
		$confirmation_gui = new ilConfirmationGUI();
		$confirmation_gui->setFormAction($ilCtrl->getFormAction($this));
		$confirmation_gui->setHeaderText($lng->txt("udf_delete_sure"));
		$confirmation_gui->setCancel($lng->txt("cancel"), "listUserDefinedFields");
		$confirmation_gui->setConfirm($lng->txt("delete"), "deleteField");
		
		$user_field_definitions = ilUserDefinedFields::_getInstance();
		foreach($_POST["fields"] as $id)
		{			
			$definition = $user_field_definitions->getDefinition($id);
			$confirmation_gui->addItem("fields[]", $id, $definition["field_name"]);
		}

		$tpl->setContent($confirmation_gui->getHTML());

		return true;
	}
		
	function deleteField()
	{
		global $lng, $ilCtrl;
		
		$user_field_definitions = ilUserDefinedFields::_getInstance();
		foreach($_POST["fields"] as $id)
		{
			$user_field_definitions->delete($id);
		}

		ilUtil::sendSuccess($lng->txt('udf_field_deleted'), true);
		$ilCtrl->redirect($this);
	}

	/**
	 * Update custom fields properties (from table gui)
	 */
	function updateFields($action = "")
	{
		global $lng, $ilCtrl;
		
		$user_field_definitions = ilUserDefinedFields::_getInstance();
		$a_fields = $user_field_definitions->getDefinitions();
		
		foreach($a_fields as $field_id => $definition)
		{
			if( isset($_POST['chb']['required_'.$field_id]) && (int)$_POST['chb']['required_'.$field_id] &&
				(!isset($_POST['chb']['visib_reg_'.$field_id]) || !(int)$_POST['chb']['visib_reg_'.$field_id]))
			{
				$this->confirm_change = true;
	
				ilUtil::sendFailure($lng->txt('invalid_visible_required_options_selected'));
				$this->listUserDefinedFields();
				return false;
			}		
		}
		
		foreach($a_fields as $field_id => $definition)
		{			
			$user_field_definitions->setFieldName($definition['field_name']);
			$user_field_definitions->setFieldType($definition['field_type']);
			$user_field_definitions->setFieldValues($definition['field_values']);
			$user_field_definitions->enableVisible((int)$_POST['chb']['visible_'.$field_id]);
			$user_field_definitions->enableChangeable((int)$_POST['chb']['changeable_'.$field_id]);
			$user_field_definitions->enableRequired((int)$_POST['chb']['required_'.$field_id]);
			$user_field_definitions->enableSearchable((int)$_POST['chb']['searchable_'.$field_id]);
			$user_field_definitions->enableExport((int)$_POST['chb']['export_'.$field_id]);
			$user_field_definitions->enableCourseExport((int)$_POST['chb']['course_export_'.$field_id]);
			$user_field_definitions->enableVisibleLocalUserAdministration((int)$_POST['chb']['visib_lua_'.$field_id]);
			$user_field_definitions->enableChangeableLocalUserAdministration((int)$_POST['chb']['changeable_lua_'.$field_id]);
			$user_field_definitions->enableGroupExport((int)$_POST['chb']['group_export_'.$field_id]);
			$user_field_definitions->enableVisibleRegistration((int)$_POST['chb']['visib_reg_'.$field_id]);
			$user_field_definitions->enableCertificate((int)$_POST['chb']['certificate_'.$field_id]);

			$user_field_definitions->update($field_id);
		}

		ilUtil::sendSuccess($lng->txt('settings_saved'), true);
		$ilCtrl->redirect($this);
	}
}

?>