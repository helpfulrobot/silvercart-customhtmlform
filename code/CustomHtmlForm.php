<?php
/**
 * Copyright 2010, 2011 pixeltricks GmbH
 *
 * This file is part of CustomHtmlForms.
 *
 * CustomHtmlForms is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * CustomHtmlForms is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with CustomHtmlForms.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package CustomHtmlForm
 */

/**
 * Provide functionallity for forms with freely configurable HTML code
 *
 * @package CustomHtmlForm
 * @author Sascha Koehler <skoehler@pixeltricks.de>
 * @copyright 2010 pxieltricks GmbH
 * @since 25.10.2010
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class CustomHtmlForm extends Form {
    /**
     * saves controller of calling class
     *
     * @var Controller
     */
    protected $controller;

    /**
     * contains form definitions of form fields
     *
     * @var array
     */
    protected $formFields = array();

    /**
     * contains groups in which fields can be collected
     *
     * @var array
     */
    public $fieldGroups;

    /**
     * contains the form fields prepared for sapphire
     *
     * scheme:
     * $SSformFields = array(
     *     'fields' => array(FieldSet),
     *     'actions' => array(FieldSet)
     * );
     *
     * @var array
     */
    protected $SSformFields;

    /**
     * the objects name
     *
     * @var string
     */
    protected $name;

    /**
     * name of the objects which should be used for the JS validators
     *
     * @var string
     */
    protected $jsName;

    /**
     * contains the error message for a form field
     *
     * @var array
     */
    protected $errorMessages;

    /**
     * contains the messages for a form
     *
     * @var array
     */
    protected $messages;

    /**
     * Contains an associative array with values that are passed to the form as
     * hidden fields. These values will not be validated, they only contain data
     * for control and evaluation.
     *
     * @var array
     */
    protected $customParameters;

    /**
     * the forms preferences; can be overwritten in the instance
     *
     * @var array
     */
    protected $basePreferences  = array(
        'createShoppingcartForms'           => true,
        'doJsValidation'                    => true,
        'doJsValidationScrolling'           => true,
        'fillInRequestValues'               => true,
        'isConditionalStep'                 => false,
        'loadShoppingcartModules'           => true,
        'markRequiredFields'                => false,
        'showJsValidationErrorMessages'     => true,
        'ShowCustomHtmlFormStepNavigation'  => true,
        'stepIsVisible'                     => true,
        'stepTitle'                         => '',
        'submitAction'                      => 'customHtmlFormSubmit',
        'submitButtonTitle'                 => 'Abschicken',
        'submitButtonToolTip'               => 'Abschicken'
    );

    /**
     * Contains custom preferences that can be set in the form object.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 23.02.2011
     */
    protected $preferences = array();

    /**
     * Contains fields that shall not be validated.
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 13.03.2011
     */
    protected $noValidationFields = array();

    /**
     * Instances of $this will have a unique ID
     *
     * Enthaelt fuer jede Formularklasse die Nummer der aktuellen
     * Instanziierung.
     *
     * @var array
     */
    public static $classInstanceCounter = array();

    /**
     * Contains the registered modules. This list is used by all methods that
     * fetch templates.
     * 
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 28.01.2011
     */
    public static $registeredModules = array(
        'customhtmlform' => 1
    );

    /**
     * Contains a list of registerd custom html forms
     *
     * @var array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 08.04.2011
     */
    protected $registeredCustomHtmlForms = array();
    
    /**
     * Don't enable Security token for this type of form because we'll run
     * into caching problems when using it.
     * 
     * @var boolean
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 22.07.2011
     */
    protected $securityTokenEnabled = true;
    
    /**
     * indicates whether there is an succeeded submission or not
     *
     * @var bool
     */
    protected $submitSuccess = false;
    
    /**
     * Contains all registered form field handlers.
     *
     * @var array
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 06.12.2011
     */
    public static $registeredFormFieldHandlers = array();

    /**
     * creates a form object with a free configurable markup
     *
     * @param ContentController $controller  the calling controller instance
     * @param array             $params      optional parameters
     * @param array             $preferences optional preferences
     * @param bool              $barebone    defines if a form should only be instanciated or be used too
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function __construct($controller, $params = null, $preferences = null, $barebone = false) {
        global $project;

        $this->controller = $controller;

        if (is_array($params)) {
            $this->customParameters = $params;
        }

        // Hook for setting preferences via a method call
        $this->preferences();

        if (is_array($preferences)) {
            foreach ($preferences as $title => $setting) {
                if (!empty($title)) {
                    $this->basePreferences[$title] = $setting;
                }
            }
        }
        
        $name = $this->getSubmitAction();

        if (!$barebone) {
            $this->getFormFields();
            $this->fillInFieldValues();
        }

        parent::__construct(
            $this->getFormController($controller, $preferences),
            $name,
            new FieldSet(),
            new FieldSet()
        );
        
        // Hook for setting preferences via a method call; we need to do this
        // a second time so that the standard Silverstripe mechanism can take
        // influence, too (i.e. _config.php files, init methods, etc).
        $this->preferences();

        if (is_array($preferences)) {
            foreach ($preferences as $title => $setting) {
                if (!empty($title)) {
                    $this->basePreferences[$title] = $setting;
                }
            }
        }
        
        if ($this->securityTokenEnabled) {
            $this->getSecurityToken()->enable();
        } else {
            $this->getSecurityToken()->disable();
        }

        // Counter for the form class, init or increment
        // Zaehler fuer die Formularklasse ggfs. initialisieren und erhoehen.
        if (!isset(self::$classInstanceCounter[$this->class])) {
            self::$classInstanceCounter[$this->class] = 0;
        }

        if (!$barebone) {
            self::$classInstanceCounter[$this->class]++;
        }

        // new assignment required, because the controller will be overwritten in the form class
        // Nochmaliges Setzen erforderlich, da der Controller in der Form-Klasse ueberschrieben wird.
        $this->controller = $controller;

        // create group structure
        // Gruppenstruktur erzeugen
        if (isset($this->formFields)) {
            $this->fieldGroups['formFields'] = $this->getFormFields();
        } else {
            $this->fieldGroups['formFields'] = array();
        }

        $this->name               = str_replace('/', '', $this->class.'_'.$name.'_'.(self::$classInstanceCounter[$this->class]));
        $this->jsName             = $this->name;
        $this->SSformFields       = $this->getForm();
        $this->SSformFields['fields']->setForm($this);
        $this->SSformFields['actions']->setForm($this);
        parent::setFields($this->SSformFields['fields']);
        parent::setActions($this->SSformFields['actions']);

        // define form action
        // Action fuer das Formular setzen
        $this->setFormAction(Controller::join_links($this->getFormController($controller, $preferences)->Link(), $name));

        /*
         * load and init JS validators
         * form integration via FormAttributes()
         */
        // -------------------------------------------------------------------
        // Javascript-Validator laden und initialisieren.
        // Einbindung ins Formular erfolgt in Methode "FormAttributes()".
        // -------------------------------------------------------------------
        if (!$barebone) {
            $javascriptSnippets = $this->getJavascriptValidatorInitialisation();

            if (!$this->getLoadShoppingCartModules()) {
                SilvercartShoppingCart::setLoadShoppingCartModules(false);
            }
            
            if ($this->getCreateShoppingCartForms() &&
                class_exists('SilvercartShoppingCart')) {
                
                SilvercartShoppingCart::setCreateShoppingCartForms(false);
            }
            
            $this->controller->addJavascriptSnippet($javascriptSnippets['javascriptSnippets']);
            $this->controller->addJavascriptOnloadSnippet($javascriptSnippets['javascriptOnloadSnippets']);
            $this->controller->addJavascriptOnloadSnippet($this->getJavascriptFieldInitialisations());
        }

        // Register the default module directory from mysite/_config.php
        self::registerModule($project);
    }

    /**
     * Here you can set the preferences. This is an alternative to setting
     * them via the $preferences class variable.
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 23.02.2011
     * @return void
     */
    public function preferences() {
        $this->extend('updatePreferences', $this->preferences);
    }
    
    /**
     * Used to overwrite a CustomHtmlForms process by a decorator
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.03.2012
     */
    public function extendedProcess() {
        $processResult = $this->extend('extendedProcess');
        if (empty ($processResult)) {
            $result = false;
        } else {
            $result = true;
        }
        return $result;
    }

    /**
     * Add a module for the template pull methods.
     *
     * You can give a priority ranging from 1 to 100. The standard priority
     * for the project given in "mysite/_config.php" is 50. The
     * customhtmlform priority is 1. To override both you would give a
     * priority of 51 or higher.
     *
     * @param string $moduleName The name of the module
     * @param int    $priority   The priority
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 28.01.2011
     * @return void
     */
    public static function registerModule($moduleName, $priority = 51) {
        self::$registeredModules[$moduleName] = $priority;
    }

    /**
     * Returns an HTML tag for marking fields as required.
     *
     * @param boolean $isRequiredField Indicate wether this is a required field
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 29.03.2012
     */
    public function RequiredFieldMarker($isRequiredField) {
        $marker = '';

        if (isset($this->preferences['markRequiredFields'])) {
            $markRequiredFields = $this->preferences['markRequiredFields'];
        } else {
            $markRequiredFields = $this->basePreferences['markRequiredFields'];
        }

        if ($isRequiredField &&
            $markRequiredFields) {

            $marker = _t('CustomHtmlForm.REQUIRED_FIELD_MARKER');
        }

        return $marker;
    }

    /**
     * Returns javascript code for fields that need special initialisation,
     * e.g. the datepicker field
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 28.02.2012
     */
    public function getJavascriptFieldInitialisations() {
        $snippet    = '';
        $formFields = $this->getFormFields();

        foreach ($this->fieldGroups as $groupName => $groupFields) {
            foreach ($groupFields as $fieldName => $fieldDefinition) {
                if ($fieldDefinition['type'] == 'DateField') {
                    $config = '';

                    if (array_key_exists('configuration', $fieldDefinition)) {
                        foreach ($fieldDefinition['configuration'] as $option => $value) {
                            $config .= sprintf(
                                "'%s': '%s',",
                                $option,
                                $value
                            );
                        }

                        if (!empty($config)) {
                            $config = substr($config, 0, -1);
                        }
                    }

                    $snippet .= sprintf(
                        "$('input[name=\"%s\"]').datepicker({%s});",
                        $fieldName,
                        $config
                    );
                }
            }
        }

        return $snippet;
    }

    /**
     * Returns JS commands for JS validators init
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 21.01.2011
     */
    public function getJavascriptValidatorInitialisation() {
        $validatorFields    = $this->generateJsValidatorFields();
        $javascriptSnippets = '
            var '.$this->jsName.';
        ';

        $javascriptOnloadSnippets = '
            '.$this->jsName.' = new pixeltricks.forms.validator();
            '.$this->jsName.'.setFormFields(
                {
                    '.$validatorFields.'
                }
            );
            '.$this->jsName.'.setFormName(\''.$this->jsName.'\');
            '.$this->jsName.'.setPreference(\'doJsValidationScrolling\', '.($this->getDoJsValidationScrolling() ? 'true' : 'false').');
            '.$this->jsName.'.setPreference(\'showJsValidationErrorMessages\', '.($this->getShowJsValidationErrorMessages() ? 'true' : 'false').');
            '.$this->jsName.'.bindEvents();';
        
        if ($this->getDoJsValidation()) {
            $javascriptOnloadSnippets .= '$("#'.$this->jsName.'").bind("submit", function(e) { return '.$this->jsName.'.checkForm(e); });';
        }

        return array(
            'javascriptSnippets'        => $javascriptSnippets,
            'javascriptOnloadSnippets'  => $javascriptOnloadSnippets
        );
    }
    
    /**
     * Indicates wether the shoppingcart modules should be loaded.
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 27.04.2011
     */
    public function getLoadShoppingCartModules() {
        $loadModules = false;
        
        if (isset($this->preferences['loadShoppingcartModules'])) {
            $loadModules = $this->preferences['loadShoppingcartModules'];
        } else {
            $loadModules = $this->basePreferences['loadShoppingcartModules'];
        }
        
        return $loadModules;
    }
    
    /**
     * Indicates wether the shoppingcart forms should be drawn.
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 27.04.2011
     */
    public function getCreateShoppingCartForms() {
        $createForms = false;
        
        if (isset($this->preferences['createShoppingcartForms'])) {
            $createForms = $this->preferences['createShoppingcartForms'];
        } else {
            $createForms = $this->basePreferences['createShoppingcartForms'];
        }
        
        return $createForms;
    }

    /**
     * Returns whether the given type is a dropdown field.
     *
     * @param string $type The type to check
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.11.2012
     */
    public function isDropdownField($type) {
        $isField = false;

        if ($type == 'DropdownField' ||
            $type == 'GroupedDropdownField' ||
            $type == 'HTMLDropdownField' ||
            $type == 'CountryDropdownField' ||
            $type == 'LanguageDropdownField' ||
            $type == 'SimpleTreeDropdownField' ||
            $type == 'TreeDropdownField' ||
            $type == 'TreeDropdownField_Readonly' ||
            $type == 'StateProvinceDropdownField_Readonly' ||
            $type == 'Widget_TreeDropdownField_Readonly' ||
            $type == 'StateDropdownField' ||
            $type == 'SilvercartCheckoutOptionsetField' ||
            $type == 'SilvercartShippingOptionsetField' ||
            $type == 'OptionsetField' ||
            $type == 'SelectionGroup' ||
            in_array('OptionsetField', class_parents($type)) ||
            in_array('DropdownField', class_parents($type))) {

            $isField = true;
        }

        return $isField;
    }

    /**
     * Returns whether the given type is a listbox field.
     *
     * @param string $type The type to check
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.11.2012
     */
    public function isListboxField($type) {
        $isField = false;

        if ($type == 'ListboxField' ||
            in_array('ListboxField', class_parents($type))) {

            $isField = true;
        }

        return $isField;
    }

    /**
     * Returns whether the given type is an optionset field.
     *
     * @param string $type The type to check
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.11.2012
     */
    public function isOptionsetField($type) {
        $isField = false;

        if ($type == 'OptionsetField' ||
            $type == 'SilvercartCheckoutOptionsetField' ||
            $type == 'SilvercartShippingOptionsetField' ||
            in_array('OptionsetField', class_parents($type))) {

            $isField = true;
       }

        return $isField;
    }

    /**
     * Returns whether the given type is a selection group field.
     *
     * @param string $type The type to check
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.11.2012
     */
    public function isSelectiongroupField($type) {
        $isField = false;

        if ($type == 'SelectionGroup' ||
            in_array('SelectionGroup', class_parents($type))) {

            $isField = true;
        }

        return $isField;
    }

    /**
     * Returns whether the given type is a text field.
     *
     * @param string $type The type to check
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 21.11.2012
     */
    public function isTextField($type) {
        $isField = false;

        if ($type == 'TextField' ||
            $type == 'SilvercartTextField' ||
            $type == 'EmailField' ||
            $type == 'PtCaptchaField' ||
            $type == 'PtCaptchaInputField') {

            $isField = true;
        }

        return $isField;
    }

    /**
     * Set a custom parameter on the given form field.
     *
     * @param string $identifier The identifier of the field
     * @param string $value      The value of the field
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 25.01.2011
     */
    public function setFormFieldValue($identifier, $value) {
        if (isset($this->fieldGroups['formFields'][$identifier])) {
            $this->fieldGroups['formFields'][$identifier]['value'] = $value;
        }

        $this->SSformFields = $this->getForm();
        $this->SSformFields['fields']->setForm($this);
        $this->SSformFields['actions']->setForm($this);
    }

    /**
     * Creates a string with JS measures that passes the form fields to the JS Validators
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function generateJsValidatorFields() {
        $fieldStr = '';

        foreach ($this->fieldGroups as $groupName => $groupFields) {
            foreach ($groupFields as $fieldName => $fieldProperties) {
                $checkRequirementStr    = '';
                $eventStr               = '';
                $configurationStr       = '';

                // ------------------------------------------------------------
                // create JS requirements
                // ------------------------------------------------------------
                if (isset($fieldProperties['checkRequirements'])) {
                    foreach ($fieldProperties['checkRequirements'] as $requirement => $definition) {
                        $checkRequirementStr .= $this->generateJsValidatorRequirementString($requirement, $definition);
                    }
                }
                if (!empty($checkRequirementStr)) {
                    $checkRequirementStr = substr($checkRequirementStr, 0, strlen($checkRequirementStr) - 1);
                }

                // ------------------------------------------------------------
                // create JS event
                // ------------------------------------------------------------
                if (isset($fieldProperties['jsEvents'])) {
                    foreach ($fieldProperties['jsEvents'] as $event => $definition) {
                        $eventStr .= $this->generateJsValidatorEventString($event, $definition);
                    }
                }
                if (!empty($eventStr)) {
                    $eventStr = substr($eventStr, 0, strlen($eventStr) - 1);
                }

                // ------------------------------------------------------------
                // create configuration section
                // ------------------------------------------------------------
                if (isset($fieldProperties['configuration'])) {
                    foreach ($fieldProperties['configuration'] as $configuration => $definition) {
                        $configurationStr .= $this->generateJsValidatorRequirementString($configuration, $definition);
                    }
                }
                if (!empty($configurationStr)) {
                    $configurationStr = substr($configurationStr, 0, strlen($configurationStr) - 1);
                }

                // ------------------------------------------------------------
                // add additional attributes
                // ------------------------------------------------------------
                if (isset($fieldProperties['title'])) {
                    $titleField = 'title: "'.str_replace('"', '\"', $fieldProperties['title']).'",';
                } else {
                    $titleField = '';
                }

                $fieldStr .= sprintf(
                    "'%s': {
                        type: \"%s\",
                        %s
                        checkRequirements: {
                            %s
                        },
                        events: {
                            %s
                        },
                        configuration: {
                            %s
                        }
                    },",
                    $fieldName,
                    $fieldProperties['type'],
                    $titleField,
                    $checkRequirementStr,
                    $eventStr,
                    $configurationStr
                );
            }
        }

        if (!empty($fieldStr)) {
            $fieldStr = substr($fieldStr, 0, strlen($fieldStr) - 1);
        }

        return $fieldStr;
    }

    /**
     * Returns a string of JS code created from the passed parameters
     *
     * @param string $requirement name of the requirement
     * @param mixed  $definition  the definition
     * 
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 10.11.2010
     *
     */
    protected function generateJsValidatorRequirementString($requirement, $definition) {

        $checkRequirementStr = '';

        if (is_array($definition)) {
            $subCheckRequirementStr = '';
            foreach ($definition as $subRequirement => $subDefinition) {
                if (is_bool($subDefinition)) {
                    $subDefinitionStr = $subDefinition ? 'true' : 'false';
                } else if (is_int($subDefinition)) {
                    $subDefinitionStr = $subDefinition;
                } else {
                    $subDefinitionStr = "'".$subDefinition."'";
                }

                $subCheckRequirementStr .= $subRequirement.": ".$subDefinitionStr.",";
            }

            if (!empty($subCheckRequirementStr)) {
                $subCheckRequirementStr = substr($subCheckRequirementStr, 0, strlen($subCheckRequirementStr) - 1);

                $checkRequirementStr .= $requirement.': {';
                $checkRequirementStr .= $subCheckRequirementStr;
                $checkRequirementStr .= '},\n';
            }
        } else {
            if (is_bool($definition)) {
                $definitionStr = $definition ? 'true' : 'false';
            } else if (is_int($definition)) {
                $definitionStr = $definition;
            } else {
                $definitionStr = "'".$definition."'";
            }

            $checkRequirementStr .= $requirement.": ".$definitionStr.",\n";
        }

        if (!empty($checkRequirementStr)) {
            $checkRequirementStr = substr($checkRequirementStr, 0, -1);
        }

        return $checkRequirementStr;
    }

    /**
     * Returns a string of JS code created from the passed parameters
     *
     * @param string $event      events name
     * @param mixed  $definition the definition
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 10.11.2010
     */
    protected function generateJsValidatorEventString($event, $definition) {
        $eventStr               = '';
        $subEventStr            = '';
        $eventFieldMappingsStr  = '';

        if ($event == 'setValueDependantOn') {

            $eventReferenceField = $definition[0];

            foreach ($definition[1] as $referenceFieldValue => $mapping) {
                
                $mappingStr = '';

                foreach ($mapping as $key => $value) {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    } else if (is_int($value)) {
                        $value = $value;
                    } else {
                        $value = "'".$value."'";
                    }
                    if (!empty($key)) {
                        $mappingStr .= $key.': '.$value.',';
                    } else {
                        $mappingStr .= 'CustomHtmlFormEmptyValue: '.$value.',';
                    }
                }
                if (!empty($mappingStr)) {
                    $mappingStr = substr($mappingStr, 0, -1);
                }

                $eventFieldMappingsStr .= $referenceFieldValue.': {';
                $eventFieldMappingsStr .= $mappingStr;
                $eventFieldMappingsStr .= '},';
            }
            if (!empty($eventFieldMappingsStr)) {
                $eventFieldMappingsStr = substr($eventFieldMappingsStr, 0, -1);
            }

            $eventStr .= $event.': {';
            $eventStr .= $eventReferenceField.': {';
            $eventStr .= $eventFieldMappingsStr;
            $eventStr .= '}';
            $eventStr .= '},';
        } else {
            $eventStr .= $event.': ';
            $eventStr .= $this->createJsonFromStructure($definition);
            $eventStr .= ',';
        }
        
        return $eventStr;
    }

    /**
     * this method can be implemented optionally in child classes
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function fillInFieldValues() {
        if ($this->getFillInRequestValues()) {
            $this->fillInRequestValues();
        }
    }

    /**
     * fills form fields with values from the request
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 26.09.2012
     */
    protected function fillInRequestValues() {
        $request = $this->controller->getRequest();

        $formFields = $this->getFormFields();
        
        foreach ($formFields as $fieldName => $fieldDefinition) {
            if (isset($request[$fieldName])) {
                if (strtolower($fieldDefinition['type']) == 'passwordfield') {
                    continue;
                }
                $this->formFields[$fieldName][$this->getFormFieldValueLabel($fieldName)] = $request[$fieldName];
            }
        }
    }

    /**
     * Returns the parameter used to set the field value; might be "value" or "selectedValue"
     * Liefert den Parameter, der zum Setzen des Feldwertes benutzt wird.
     * Dieser kann je nach Feldtyp "value" oder "selectedValue" sein.
     *
     * @param string $fieldName name of the field
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 20.12.2010
     */
    protected function getFormFieldValueLabel($fieldName) {
        $valueLabel = 'value';

        $formFields = $this->getFormFields();
        
        if (isset($formFields[$fieldName])) {
            $fieldDefinition = $formFields[$fieldName];
            
            if ($this->isDropdownField($fieldDefinition['type']) ||
                $this->isListboxField($fieldDefinition['type'])) {
                $valueLabel = 'selectedValue';
            }
        }

        return $valueLabel;
    }

    /**
     * Processes the submitted form; If there are validation errors the form will
     * be returned with error messages.
     *
     * @param SS_HTTPRequest $data submit data
     * @param Form           $form form object
     *
     * @return ViewableData
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>, Sebastian Diel <sdiel@pixeltricks.de>
     * @since 06.07.2012
     */
    public function submit($data, $form) {
        $formData = $this->getFormData($data);
        $this->checkFormData($formData);
        $result = null;
        ob_start();
        if (empty($this->errorMessages)) {
            $this->setSubmitSuccess(true);
            $submitSuccessResult = '';
            // Es sind keine Fehler aufgetreten:
            $overwriteResult = $this->extend('overwriteSubmitSuccess', $data, $form, $formData);
            if (empty ($overwriteResult)) {
                $this->extend('onBeforeSubmitSuccess', $data, $form, $formData);
                $submitSuccessResult = $this->submitSuccess(
                    $data,
                    $form,
                    $formData
                );
                $this->extend('onAfterSubmitSuccess', $data, $form, $formData);
            } else {
                $submitSuccessResult = $overwriteResult[0];
            }
            $result = $submitSuccessResult;
        } else {
            $submitFailureResult = '';
            // Es sind Fehler aufgetreten:
            $overwriteResult = $this->extend('overwriteSubmitFailure', $data, $form);
            if (empty ($overwriteResult)) {
                $this->extend('onBeforeSubmitFailure', $data, $form);
                $submitFailureResult = $this->submitFailure(
                    $data,
                    $form
                );
                $this->extend('onAfterSubmitFailure', $data, $form);
            } else {
                $submitFailureResult = $overwriteResult[0];
            }
            $result = $submitFailureResult;
        }
        $output = ob_get_contents();
        ob_end_clean();
        if (!Director::redirected_to() &&
            empty($result) &&
            !empty($output)) {
            Director::redirectBack();
        }
        return $result;
    }

    /**
     * In calse of validation errors the form will be returned with error
     * messages
     *
     * @param SS_HTTPRequest $data submit data
     * @param Form           $form form object
     *
     * @return ViewableData
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function submitFailure($data, $form) {
        $formFields = $this->getFormFields();
        // fill in the form
        foreach ($formFields as $fieldName => $fieldDefinition) {
            if (isset($data[$fieldName])) {
                $this->formFields[$fieldName][$this->getFormFieldValueLabel($fieldName)] = Convert::raw2xml($data[$fieldName]);
            }
        }

        $this->SSformFields = $this->getForm();

        if (empty($form)) {
            $form = $this->class;
        }

        // prepare validation errors for template
        // aufgetretene Validierungsfehler in Template auswertbar machen
        $data = array(
            'errorMessages' => new DataObjectSet($this->errorMessages),
            'messages'      => new DataObjectSet($this->messages),
            $this->SSformFields['fields'],
            $this->SSformFields['actions']
        );
        

        parent::__construct(
            $this->controller,
            $this->name,
            $this->SSformFields['fields'],
            $this->SSformFields['actions']
        );

        // fill in form with validation results and render it
        // Formular mit Validierungsergebnissen befuellen und rendern
        $outputForm = $this->customise($data)->renderWith(
            array(
                $this->class
            )
        );

        // pass rendered form to the controller
        // Gerendertes Formular an Controller uebergeben
        if ($this->controller instanceof CustomHtmlFormStepPage_Controller) {
            $output = $this->controller->customise(
                array(
                    $form => $outputForm
                )
            )->renderWith(array($this->controller->ClassName, 'Page'));
        } else {
            $output = $this->controller->customise(
                array(
                    $form => $outputForm
                )
            );
        }
        return $output;
    }

    /**
     * This method will be call if there are no validation error
     *
     * @param SS_HTTPRequest $data     input data
     * @param Form           $form     form object
     * @param array          $formData secured form data
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function submitSuccess($data, $form, $formData) {
        // In Instanz implementieren
    }

    /**
     * Passes the values from the SS_HTTPRequest object to the defined form;
     * missing values will be set to false
     *
     * during the transmission the values will become SQL secure
     *
     * @param SS_HTTPRequest $request the submitted data
     *
     * @return array
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function getFormData($request) {
        $formData = array();

        if ($this->securityTokenEnabled) {
            $formData['SecurityID'] = Convert::raw2sql($request['SecurityID']);
        }

        // read defined form fields
        // Definierte Formularfelder auslesen
        foreach ($this->fieldGroups as $groupName => $groupFields) {
            foreach ($groupFields as $fieldName => $fieldDefinition) {
                if (isset($request[$fieldName])) {
                    $formData[$fieldName] = Convert::raw2sql($request[$fieldName]);
                } else {
                    $formData[$fieldName] = false;
                }
            }
        }

        // read dynamically added form fields
        // Dynamisch hinzugefuegte Formularfelder auslesen
        if (isset($this->customParameters)) {
            foreach ($this->customParameters as $customParameterKey => $customParameterValue) {
                if (isset($request[$customParameterKey])) {
                    $formData[$customParameterKey] = Convert::raw2sql($request[$customParameterKey]);
                } else {
                    $formData[$customParameterKey] = false;
                }
            }
        }

        return $formData;
    }

    /**
     * checks all form fields and returns them as array
     *
     * @param SS_HTTPRequest $data Die zu pruefenden Formulardaten.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function checkFormData($data) {
        $errorMessages  = array();
        $error          = false;

        if ($this->securityTokenEnabled) {
            $securityID = Session::get('SecurityID');
            
            if (empty($securityID) ||
                empty($data['SecurityID']) ||
                $data['SecurityID'] != $securityID) {

                $error                 = true;
                $errorMessages['CSRF'] = array(
                    'message'   => 'CSRF Attacke!',
                    'fieldname' => 'Ihre Session ist abgelaufenen. Bitte laden Sie die Seite neu und füllen Sie das Formular nochmals aus.',
                    'title'     => 'Ihre Session ist abgelaufenen. Bitte laden Sie die Seite neu und füllen Sie das Formular nochmals aus.',
                    'CSRF' => array(
                        'message' => 'CSRF Attacke!'
                    )
                );
            }
        }

        if (!$error) {
            foreach ($this->fieldGroups as $groupName => $groupFields) {
                foreach ($groupFields as $fieldName => $fieldDefinition) {
                    $fieldErrorMessages = array();
                    $fieldError         = false;
                    $checkFormData      = new CheckFormData($data[$fieldName]);

                    // Formale Erfordernisse pruefen, die dieses Feld erfuellen muss.
                    if (!isset($fieldDefinition['checkRequirements'])) {
                        continue;
                    }

                    // Check if the field shall be validated
                    if (in_array($fieldName, $this->noValidationFields)) {
                        continue;
                    }

                    foreach ($fieldDefinition['checkRequirements'] as $requirement => $requiredValue) {
                        if (empty($requirement)) {
                            continue;
                        }
                        
                        // --------------------------------------------------------
                        // Sonderfaelle:
                        // --------------------------------------------------------

                        // Kriterium bezieht sich auf ein anderes Feld
                        if ($requirement == 'mustEqual' ||
                            $requirement == 'mustNotEqual') {

                            $requiredValue = array(
                                'fieldName'  => $requiredValue,
                                'fieldTitle' => $groupFields[$requiredValue]['title'] ? $groupFields[$requiredValue]['title'] : $requiredValue,
                                'value'      => $data[$requiredValue]
                            );
                        }

                        // Feld muss ausgefuellt sein, wenn anderes Feld
                        // ausgefuellt ist
                        if ($requirement == 'isFilledInDependantOn') {
                            $requiredValue = array(
                                $requiredValue,
                                $data
                            );
                        }

                        // PtCaptchaField
                        if ($requirement == 'PtCaptchaInput') {
                            $requiredValue = array(
                                'formName'  => $this->class,
                                'fieldName' => $this->name.$fieldName
                            );
                        }

                        // Callbackfunktion verwenden
                        if ($requirement == 'callBack') {
                            $fieldCheckResult = $this->$requiredValue($data[$fieldName]);
                        } else {
                            $fieldCheckResult = $checkFormData->$requirement($requiredValue);
                        }

                        if (!$fieldCheckResult['success']) {
                            $fieldErrorMessages[]   = $fieldCheckResult['errorMessage'];
                            $fieldError             = true;
                        }
                    }

                    // Bei diesem Feld sind ein oder mehrere Fehler aufgetreten, also
                    // diese zuordnen und speichern.
                    if ($fieldError) {
                        // Fehler an das Formularfeld anhaengen
                        foreach ($this->SSformFields['fields'] as $field) {
                            if ($field->name == $fieldName) {
                                $field->errorMessage = new ArrayData(array(
                                    'message' => implode("\n", $fieldErrorMessages)
                                ));
                            }
                        }

                        // Fehler in eigenem Feld speichern
                        $errorMessages[$fieldName] = array(
                            'message'   => implode("\n", $fieldErrorMessages),
                            'fieldname' => $fieldDefinition['title'] ? $fieldDefinition['title'] : $fieldName,
                            $fieldName => array(
                                'message' => implode("\n", $fieldErrorMessages)
                            )
                        );
                        $error = true;
                    }
                }
            }
        }

        $this->errorMessages = $errorMessages;
    }

    /**
     * creates the form's input fields and action fields and fills missing data
     * with standard values
     *
     * @return array retunrs form fields and form actions
     *      array(
     *          'fields'    => FieldSet,
     *          'actions'   => FieldSet
     *      )
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function getForm() {
        $fields = new FieldSet();

        // --------------------------------------------------------------------
        // define meta data
        // --------------------------------------------------------------------
        if (!empty($this->customParameters)) {
            foreach ($this->customParameters as $customParameterKey => $customParameterValue) {
                $field = new HiddenField($customParameterKey, '', $customParameterValue, null, null);
                $fields->push($field);
            }
        }

        $field = new HiddenField('CustomHtmlFormName', '', $this->getCustomHtmlFormName(), null, null);
        $fields->push($field);

        // --------------------------------------------------------------------
        // create field set from definition
        // --------------------------------------------------------------------
        foreach ($this->fieldGroups as $groupName => $groupFields) {
            foreach ($groupFields as $fieldName => $fieldDefinition) {
                $field = $this->getFormField(
                    $fieldName,
                    $fieldDefinition
                );

                $fields->push($field);
            }
        }

        $formAction = new FormAction(
            $this->getSubmitAction(),
            $this->getSubmitButtonTitle(),
            $this
        );
        $formAction->description = $this->getSubmitButtonToolTip();
        
        $actions = new FieldSet(
            $formAction
        );
        
        return array(
            'fields'    => $fields,
            'actions'   => $actions
        );
    }

    /**
     * Returns the requested formField or customParameter if available.
     *
     * @param string $fieldName The name to search for
     *
     * @return mixed
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 18.11.2011
     */
    public function getFormFieldDefinition($fieldName) {
        if (isset($this->customParameters[$fieldName])) {
            return $this->customParameters[$fieldName];
        }
        
        if (isset($this->formFields[$fieldName])) {
            return $this->formFields[$fieldName];
        }
        
        return false;
    }

    /**
     * creates a form field from the definition; sets standard values if they
     * are not defined
     *
     * @param string $fieldName       the field's name
     * @param array  $fieldDefinition the field definitions
     *
     * @return Field
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    protected function getFormField($fieldName, $fieldDefinition) {
        
        if (!isset($fieldDefinition['type'])) {
            throw new Exception(
                'CustomHtmlForm: Field type must be specified.'
            );
        }
        
        // SelectionGroup fields use '//' as separator for key/label definition
        if (strpos($fieldName, '//') !== false) {
            list($fieldName, $fieldLabel) = explode('//', $fieldName, 2);
        }
            
        foreach ($this->fieldGroups as $groupName => $groupFields) {
            if (isset($groupFields[$fieldName])) {
                $fieldReference = &$groupFields[$fieldName];
                break;
            }
        }

        // fill required field with standard values if they where not specified
        if (!isset($fieldDefinition['isRequired'])) {
            $fieldDefinition['isRequired'] = false;
            $fieldReference['isRequired'] = $fieldDefinition['isRequired'];
        }

        if (!isset($fieldDefinition['checkRequirements'])) {
            $fieldDefinition['checkRequirements'] = array();
            $fieldReference['checkRequirements'] = $fieldDefinition['checkRequirements'];
        }

        if (!isset($fieldDefinition['title'])) {
            $fieldDefinition['title'] = '';
            $fieldReference['title'] = $fieldDefinition['title'];
        }

        if (!isset($fieldDefinition['value'])) {
            $fieldDefinition['value'] = '';
            $fieldReference['value'] = $fieldDefinition['value'];
        }

        if (!isset($fieldDefinition['selectedValue'])) {
            $fieldDefinition['selectedValue'] = '';
            $fieldReference['selectedValue'] = $fieldDefinition['selectedValue'];
        }

        if (!isset($fieldDefinition['maxLength'])) {

            if ($this->isTextField($fieldDefinition['type'])) {
                $fieldDefinition['maxLength'] = 255;
            } else {
                $fieldDefinition['maxLength'] = null;
            }
            $fieldReference['maxLength'] = $fieldDefinition['maxLength'];
        }

        if (!isset($fieldDefinition['size'])) {
            $fieldDefinition['size'] = null;
            $fieldReference['size'] = $fieldDefinition['size'];
        }

        if (!isset($fieldDefinition['multiple'])) {
            $fieldDefinition['multiple'] = null;
            $fieldReference['multiple'] = $fieldDefinition['multiple'];
        }

        if (!isset($fieldDefinition['form'])) {
            $fieldDefinition['form'] = $this;
            $fieldReference['form'] = $fieldDefinition['form'];
        }

        // create field
        if ($this->isListboxField($fieldDefinition['type'])) {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['selectedValue'],
                $fieldDefinition['size'],
                $fieldDefinition['multiple'],
                $fieldDefinition['form']
            );
        } else if ($this->isDropdownField($fieldDefinition['type'])) {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['selectedValue'],
                $fieldDefinition['form']
            );
        } else if ($this->isOptionsetField($fieldDefinition['type'])) {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['selectedValue'],
                $fieldDefinition['form']
            );
        } else if ($this->isSelectiongroupField($fieldDefinition['type'])) {
            $groupFields = array();
            
            foreach ($fieldDefinition['items'] as $itemFieldName => $item) {
                $itemObj                     = $this->getFormField($itemFieldName, $item);
                $groupFields[$itemFieldName] = $itemObj;
            }
            
            if (empty($groupFields)) {
                return false;
            }
            
            $field = new $fieldDefinition['type'](
                $fieldName,
                $groupFields
            );
            $field->value = $fieldDefinition['value'];
        } else if ($this->isTextField($fieldDefinition['type'])) {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['maxLength'],
                $fieldDefinition['form']
            );
            if (isset($fieldDefinition['placeholder']) &&
                method_exists($field, 'setPlaceholder')) {
                $field->setPlaceholder($fieldDefinition['placeholder']);
            }
        } else if ($fieldDefinition['type'] == 'PasswordField') {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['maxLength']
            );
        } else if ($fieldDefinition['type'] == 'DateField') {
            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['value'],
                $fieldDefinition['form']
            );
            $field->setMaxLength(16);

            if (isset($fieldDefinition['configuration']) &&
                is_array($fieldDefinition['configuration'])) {

                foreach ($fieldDefinition['configuration'] as $key => $value) {
                    $field->setConfig($key, $value);
                }
            }
        } else if ($fieldDefinition['type'] == 'TextareaField') {

            if (!isset($fieldDefinition['rows'])) {
                $fieldDefinition['rows'] = 10;
                $fieldReference['rows'] = $fieldDefinition['rows'];
            }
            if (!isset($fieldDefinition['cols'])) {
                $fieldDefinition['cols'] = 10;
                $fieldReference['cols'] = $fieldDefinition['cols'];
            }

            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $fieldDefinition['rows'],
                $fieldDefinition['cols'],
                $fieldDefinition['value'],
                $fieldDefinition['form']
            );
        } else if ($fieldDefinition['type'] == 'RecaptchaField') {
            $field = new RecaptchaField('Recaptcha');
            $recaptchaField->jsOptions = array('theme' => 'clean');
        } else if ($fieldDefinition['type'] == 'MultipleImageUploadField' ||
                   $fieldDefinition['type'] == 'MultipleFileUploadField') {

            if (isset($fieldDefinition['configuration']) &&
                is_array($fieldDefinition['configuration'])) {

                $configuration = $fieldDefinition['configuration'];
            } else {
                $configuration = array();
            }

            $field = new $fieldDefinition['type'](
                $fieldName,
                $fieldDefinition['title'],
                $configuration,
                $fieldDefinition['form']
            );
            if (isset($fieldDefinition['filetypes']) &&
                is_array($fieldDefinition['filetypes'])) {
                $field->setFileTypes($fieldDefinition['filetypes']);
            }
            $field->setVar('script', urlencode($this->controller->Link().'uploadifyUpload'));
            $field->setVar('refreshlink', ($this->controller->Link().'uploadifyRefresh'));
            $field->setVar('refreshlink', ($this->controller->Link().'uploadifyRefresh'));

            if (isset($fieldDefinition['configuration']) &&
                is_array($fieldDefinition['configuration']) &&
                isset($fieldDefinition['configuration']['uploadFolder'])) {

                $field->setUploadFolder($fieldDefinition['configuration']['uploadFolder']);
            } else {
                $field->setUploadFolder('Uploads');
            }

            if (isset($fieldDefinition['value']) &&
                is_array($fieldDefinition['value'])) {

                $field->setValue($fieldDefinition['value']);
            }
        } else {
            $formFieldHandler = self::getRegisteredFormFieldHandlerForType($fieldDefinition['type']);
            if ($formFieldHandler) {
                $field = $formFieldHandler->getFormField($fieldName, $fieldDefinition);
            } else {
                $field = new $fieldDefinition['type'](
                    $fieldName,
                    $fieldDefinition['title'],
                    $fieldDefinition['value'],
                    $fieldDefinition['form']
                );
            }
        }

        // add error message for a field if defined
        if (isset($this->errorMessages[$fieldName])) {
            $field->errorMessage = new ArrayData(array(
                'message' => $this->errorMessages[$fieldName]['message']
            ));
        }

        // set identifier for mandatory fields
        if (isset($fieldDefinition['checkRequirements']) &&
            isset($fieldDefinition['checkRequirements']['isFilledIn']) &&
            $fieldDefinition['checkRequirements']['isFilledIn']) {
            
            $field->isRequiredField = true;
        } else {
            $field->isRequiredField = false;
        }

        return $field;
    }

    /**
     * returns the form objects name
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function getCustomHtmlFormName() {
        return $this->name;
    }
    
    /**
     * Returns the forms fields.
     * 
     * @param bool $withUpdate Call the method with decorator updates or not?
     *
     * @return array
     */
    public function getFormFields($withUpdate = true) {
        if ($withUpdate && !empty($this->class)) {
            $this->extend('updateFormFields', $this->formFields);
        }
        return $this->formFields;
    }

    /**
     * defines a new message for the form
     *
     * @param string $message the message's text
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function addMessage($message) {
        $this->messages[] = array('message' => $message);
    }

    /**
     * Defines a new message for a form field
     *
     * @param string $fieldName   The name of the field
     * @param string $message     The message's text
     * @param string $messageType The message type (not used)
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 08.04.2011
     */
    public function addErrorMessage($fieldName, $message, $messageType = '') {
        $this->errorMessages[$fieldName] = array(
            'message'   => $message,
            'fieldname' => $fieldName,
            $fieldName  => array(
                'message' => $message
            )
        );
    }

    /**
     * Returns the CustomHtmlForm object with the given identifier; if it's not
     * found a boolean false is returned.
     *
     * @param string $formIdentifier The identifier of the form
     *
     * @return mixed CustomHtmlForm|bool false
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 08.04.2011
     */
    public function getRegisteredCustomHtmlForm($formIdentifier) {
        $formObj = false;

        if (isset($this->registeredCustomHtmlForms[$formIdentifier])) {
            $formObj = $this->registeredCustomHtmlForms[$formIdentifier];
        }

        return $formObj;
    }
    
    /**
     * Returns a registered handler for the given field type if available.
     *
     * @param string $fieldType The field type
     *
     * @return mixed
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 06.12.2011
     */
    public static function getRegisteredFormFieldHandlerForType($fieldType) {
        if (array_key_exists($fieldType, self::$registeredFormFieldHandlers)) {
            return self::$registeredFormFieldHandlers[$fieldType];
        }
        
        return false;
    }

    /**
     * Return a CSRF-preventing ID to insert into a form.
     *
     * @return string
     */
    public function getSecurityID() {
        SecurityToken::enable();
        return parent::getSecurityID();
    }

    /**
     * passes the meta data for form submission to the template;
     * called by the template
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function CustomHtmlFormMetadata() {
        $metadata = '';

        // form name
        $metadata .= $this->dataFieldByName('CustomHtmlFormName')->Field();

        // SecurityID
        if ($this->dataFieldByName('SecurityID')) {
            $metadata .= $this->dataFieldByName('SecurityID')->Field();
        } else {
            if ($this->securityTokenEnabled) {
                $metadata .= sprintf(
                    '<input type="hidden" id="%s" name="SecurityID" value="%s" />',
                    $this->FormName().'_SecurityID',
                    $this->getSecurityID()
                );
            }
        }
        
        // custom data fields
        // Eigene Datenfelder
        if (!empty($this->customParameters)) {
            foreach ($this->customParameters as $customParameterKey => $customParameterValue) {
                $metadata .= $this->dataFieldByName($customParameterKey)->Field();
            }
        }

        return $metadata;
    }

    /**
     * does a group with the passed name exist?
     * 
     * @param string $groupName the group's name
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 13.01.2011
     */
    public function CustomHtmlFormFieldGroupExists($groupName) {
        $groupExists = false;
        
        if (isset($this->fieldGroups[$groupName])) {
            $groupExists = true;
        }
        
        return $groupExists;
    }
    
    /**
     * returns HTML code for a field group
     *
     * @param string $groupName group's name
     * @param string $template  name of the template that should be used for all
     *                          fields of the group
     * @param mixed  $argument1 An optional argument
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 27.10.2010
     */
    public function CustomHtmlFormFieldsByGroup($groupName, $template = null, $argument1 = null) {
        $fieldGroup = new DataObjectSet();
        
        if ($this->extend('overwriteCustomHtmlFormFieldsByGroup', $groupName, $template, $fieldGroup, $argument1)) {
            return $fieldGroup;
        }

        if (!isset($this->fieldGroups[$groupName])) {
            return $fieldGroup;
        }

        foreach ($this->fieldGroups[$groupName] as $fieldName => $fieldDefinitions) {
            $fieldGroup->push(
                new ArrayData(
                    array(
                        'CustomHtmlFormField'   => $this->CustomHtmlFormFieldByName($fieldName, $template)
                    )
                )
            );
        }

        return $fieldGroup;
    }

    /**
     * Returns the HTML code for the passed field; created with the standard
     * template for fields
     *
     * @param string $fieldName the field's name
     * @param string $template  optional; path to template snippet, relative to
     *                  the sit root; by dot notation search in modul directory
     *                  can be set:
     *                  "module.myTemplate" searches in the modul directory
     *                  "modul/templates" for the template "myTemplate.ss
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function CustomHtmlFormFieldByName($fieldName, $template = null) {

        $fieldReference = '';
        $templatePath   = '';
        $output         = '';

        foreach ($this->fieldGroups as $groupName => $groupFields) {
            if (isset($groupFields[$fieldName])) {
                $fieldReference = $groupFields[$fieldName];
                break;
            }
        }
        if ($fieldReference === '') {
            throw new Exception(
                sprintf('The field "%s" is called via template but not defiened on the form object.', $fieldName)
            );
        }

        // set the default template
        if (empty($template)) {
            $template = 'CustomHtmlFormField';
        }

        // sort the registered modules, so that the highest priority ones
        // are search first.
        $registeredModules = self::$registeredModules;
        arsort($registeredModules);

        // the paths inside modules that could contain templates
        $templateDirs = array(
            '/templates/',
            '/templates/Layout/',
            '/templates/forms/',
        );

        // search the template in a variety of possible paths
        foreach ($registeredModules as $moduleName => $priority) {
            foreach ($templateDirs as $templateDir) {
                $templatePath = $moduleName.$templateDir.$template.'.ss';

                if (Director::fileExists($templatePath)) {
                    break(2);
                }
            }
        }

        if (!empty($templatePath)) {
            $templatePathRel    = '/'.$templatePath;
            $templatePathAbs    = Director::baseFolder().$templatePathRel;
            $viewableObj        = new ViewableData();

            if (isset($fieldReference['checkRequirements']) &&
                isset($fieldReference['checkRequirements']['isFilledIn']) &&
                $fieldReference['checkRequirements']['isFilledIn']) {

                $isRequiredField = true;
            } else {
                $isRequiredField = false;
            }

            $output = $viewableObj->customise(
                array(
                    'FormName'            => $this->name,
                    'FieldName'           => $fieldName,
                    'FieldValue'          => $this->SSformFields['fields']->fieldByName($fieldName)->Value(),
                    'Label'               => isset($fieldReference['title']) ? $fieldReference['title'] : '',
                    'errorMessage'        => isset($this->errorMessages[$fieldName]) ?  $this->errorMessages[$fieldName] : '',
                    'FieldTag'            => $this->SSformFields['fields']->fieldByName($fieldName)->Field(),
                    'FieldHolder'         => $this->SSformFields['fields']->fieldByName($fieldName)->FieldHolder(),
                    'FieldID'             => $this->SSformFields['fields']->fieldByName($fieldName)->id(),
                    'FieldObject'         => $this->SSformFields['fields']->fieldByName($fieldName),
                    'Parent'              => $this,
                    'isRequiredField'     => $isRequiredField,
                    'RequiredFieldMarker' => $this->RequiredFieldMarker($isRequiredField),
                    'FieldDescription'    => isset($fieldReference['description']) ? $fieldReference['description'] : '',
                )
            )->renderWith($templatePathAbs);
        } else {
            $output = 'Template '.$template.' could not be found!';
        }

        return $output;
    }

    /**
     * returns error message as HTML text
     *
     * @param string $template optional; rendering template's name
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function CustomHtmlFormErrorMessages($template = null) {

        // make validation errors in the template evaluable
        // aufgetretene Validierungsfehler in Template auswertbar machen
        $data = array(
            'errorMessages' => new DataObjectSet($this->errorMessages),
            'messages'      => new DataObjectSet($this->messages)
        );

        $defaultTemplatePath = '/customhtmlform/templates/forms/CustomHtmlFormErrorMessages.ss';

        if (!empty($template)) {

            $template = THEMES_DIR.'/'.SSViewer::current_theme().'/templates/Layout/'.$template.'.ss';

            if (Director::fileExists($template)) {
                $templatePathRel = '/'.$template;
            } else {
                $templatePathRel = $defaultTemplatePath;
            }

        } else {
            $templatePathRel = $defaultTemplatePath;
        }

        $templatePathAbs    = Director::baseFolder().$templatePathRel;
        $viewableObj        = new ViewableData();
        $output = $viewableObj->customise(
            $data
        )->renderWith($templatePathAbs);

        return $output;
    }
    
    /**
     * Indicates wether there are error messages.
     *
     * @return boolean
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 01.12.2011
     */
    public function HasCustomHtmlFormErrorMessages() {
        $hasMessages = false;
        
        if (count($this->errorMessages) + count($this->messages) > 0) {
            $hasMessages = true;
        }
        
        return $hasMessages;
    }

    /**
     * returns the form's name
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 25.10.2010
     */
    public function FormName() {
        if ($this->htmlID) {
            return $this->htmlID;
        } else {
            return $this->name;
        }
    }

    /**
     * Returns HTML markup for the requested form
     *
     * @param string $formIdentifier   unique form name which can be called via template
     * @param Object $renderWithObject object array; in those objects context the forms shall be created
     *
     * @return CustomHtmlForm
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 08.04.2011
     */
    public function InsertCustomHtmlForm($formIdentifier = null, $renderWithObject = null) {
        if (is_null($formIdentifier)) {
            $formToRender = $this;
        } else {
            if (!isset($this->registeredCustomHtmlForms[$formIdentifier])) {
                throw new Exception(
                    sprintf(
                        'The requested CustomHtmlForm "%s" is not registered.',
                        $formIdentifier
                    )
                );
            } else {
                $formToRender = $this->registeredCustomHtmlForms[$formIdentifier];
            }
        }

        // Inject controller
        $customFields = array(
            'Controller' => $this->controller
        );

        if ($renderWithObject !== null) {
            if (is_array($renderWithObject)) {
                foreach ($renderWithObject as $renderWithSingleObject) {
                    if ($renderWithSingleObject instanceof DataObject) {
                        if (isset($customFields)) {
                            $customFields = array_merge($customFields, $renderWithSingleObject->getAllFields());
                        } else {
                            $customFields = $renderWithSingleObject->getAllFields();
                        }
                        unset($customFields['ClassName']);
                        unset($customFields['RecordClassName']);
                    }
                }
            } else {
                if ($renderWithObject instanceof DataObject) {
                    $customFields = $renderWithObject->getAllFields();
                    unset($customFields['ClassName']);
                    unset($customFields['RecordClassName']);
                }
            }
        }

        $outputForm = $formToRender->customise($customFields)->renderWith(
            array(
                $formToRender->class,
            )
        );

        return $outputForm;
    }

    /**
     * Registers a form object
     *
     * @param string         $formIdentifier unique form name which can be called via template
     * @param CustomHtmlForm $formObj        The form object with field definitions and preocessing methods
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pxieltricks GmbH
     * @since 08.04.2010
     */
    public function registerCustomHtmlForm($formIdentifier, CustomHtmlForm $formObj) {
        $this->registeredCustomHtmlForms[$formIdentifier] = $formObj;
    }
    
    /**
     * Registers a form field handler.
     *
     * @param string $fieldType The field type this handler is responsible for.
     * @param mixed  $handler   The handler object itself.
     *
     * @return void
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 06.12.2011
     */
    public static function registerFormFieldHandler($fieldType, $handler) {
        if (!array_key_exists($fieldType, self::$registeredFormFieldHandlers)) {
            self::$registeredFormFieldHandlers[$fieldType] = $handler;
        }
    }
    
    /**
     * returns the form step's title
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 29.11.2010
     */
    public function getStepTitle() {
        $title = '';

        if (isset($this->preferences['stepTitle'])) {
            $title = $this->preferences['stepTitle'];
        } else {
            $title = $this->basePreferences['stepTitle'];
        }

        return $title;
    }

    /**
     * is the form visible?
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 29.11.2010
     */
    public function getStepIsVisible() {
        $isVisible = false;

        if (isset($this->preferences['stepIsVisible'])) {
            $isVisible = $this->preferences['stepIsVisible'];
        } else {
            $isVisible = $this->basePreferences['stepIsVisible'];
        }

        return $isVisible;
    }

    /**
     * Is the defined step conditional?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 08.04.2011
     */
    public function getIsConditionalStep() {
        if (isset($this->preferences['isConditionalStep'])) {
            $isConditionalStep = $this->preferences['isConditionalStep'];
        } else {
            $isConditionalStep = $this->basePreferences['isConditionalStep'];
        }

        return $isConditionalStep;
    }

    /**
     * is the defined step the recent step?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.12.2010
     */
    public function getIsCurrentStep() {
        $isCurrentStep = false;

        if ($this->controller->getCurrentStep() == $this->getStepNr()) {
            $isCurrentStep = true;
        }

        return $isCurrentStep;
    }

    /**
     * is the step completed already
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 22.12.2010
     */
    public function isStepCompleted() {
        
        $completed = false;
        $stepIdx   = $this->getStepNr();

        if (in_array($stepIdx, $this->controller->getCompletedSteps())) {
            $completed = true;
        }

        return $completed;
    }

    /**
     * Is the previous step completed?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.12.2010
     */
    public function isPreviousStepCompleted() {
        
        $completed = false;
        $stepIdx   = $this->getStepNr() - 1;

        if (in_array($stepIdx, $this->controller->getCompletedSteps())) {
            $completed = true;
        }

        return $completed;
    }

    /**
     * Returns true if this is the last step.
     *
     * @return boolean
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 06.04.2011
     */
    public function isLastStep() {
        $step = $this->controller->getStepList()->find('stepNr', $this->getStepNr());
        
        if ($step &&
            $step->isLastVisibleStep) {
            
            return true;
        }
        return false;
    }

    /**
     * Returns the output of a form that was initialised by a
     * CustomHtmlFormStepPage object.
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 06.04.2011
     */
    public function CustomHtmlFormInitOutput() {
        return $this->controller->getInitOutput();
    }
    
    /**
     * Disable the security token.
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 22.07.2011
     */
    public function setSecurityTokenDisabled() {
        $this->securityTokenEnabled = false;
    }

    /**
     * returns the step number of this form
     *
     * @return int
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.12.2010
     */
    public function getStepNr() {
        $stepList = $this->controller->getStepList();
        $stepNr   = 1;

        foreach ($stepList as $step) {
            if ($step->step->class == $this->class) {
                $stepNr = $step->stepNr;
                break;
            }
        }

        return $stepNr;
    }

    /**
     * returns the visible step number of this form
     *
     * @return int
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.12.2010
     */
    public function getVisibleStepNr() {
        $stepList = $this->controller->getStepList();
        $stepNr   = 1;

        foreach ($stepList as $step) {
            if ($step->step->class == $this->class) {
                $stepNr = $step->visibleStepNr;
                break;
            }
        }

        return $stepNr;
    }

    /**
     * Deactivate Validation for the given field.
     *
     * @param string $fieldName The name of the field
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 13.03.2011
     */
    protected function deactivateValidationFor($fieldName) {
        if (!in_array($fieldName, $this->noValidationFields)) {
            $this->noValidationFields[] = $fieldName;
        }
    }

    /**
     * Activate Validation for the given field.
     *
     * @param string $fieldName The name of the field
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pxieltricks GmbH
     * @since 13.03.2011
     */
    protected function activateValidationFor($fieldName) {
        if (in_array($fieldName, $this->noValidationFields)) {
            for ($index = 0; $index < count($this->noValidationFields); $index++) {
                if ($fieldName == $this->noValidationFields[$index]) {
                    break;
                }
            }
            unset($this->noValidationFields[$index]);
            $this->noValidationFields = array_values($this->noValidationFields);
        }
    }

    /**
     * returns submit button title
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 26.10.2010
     */
    protected function getSubmitButtontitle() {
        $title = '';

        if (isset($this->preferences['submitButtonTitle'])) {
            $title = $this->preferences['submitButtonTitle'];
        } else {
            $title = $this->basePreferences['submitButtonTitle'];
        }

        return $title;
    }
    
    /**
     * returns submit button title
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 26.10.2010
     */
    protected function getSubmitButtonToolTip() {
        $toolTip = '';

        if (isset($this->preferences['submitButtonToolTip'])) {
            $toolTip = $this->preferences['submitButtonToolTip'];
        } else {
            $toolTip = $this->basePreferences['submitButtonToolTip'];
        }
        
        if (empty($toolTip)) {
            $toolTip = $this->getSubmitButtontitle();
        }

        return $toolTip;
    }

    /**
     * is JS validation defined?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 18.11.2010
     */
    protected function getDoJsValidation() {
        $doJsValidation = true;

        if (isset($this->preferences['doJsValidation'])) {
            $doJsValidation = $this->preferences['doJsValidation'];
        } else {
            $doJsValidation = $this->basePreferences['doJsValidation'];
        }

        return $doJsValidation;
    }
    
    /**
     * Should the form scroll to the first field after validation?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 18.11.2010
     */
    protected function getDoJsValidationScrolling() {
        $doJsValidationScrolling = true;

        if (isset($this->preferences['doJsValidationScrolling'])) {
            $doJsValidationScrolling = $this->preferences['doJsValidationScrolling'];
        } else {
            $doJsValidationScrolling = $this->basePreferences['doJsValidationScrolling'];
        }

        return $doJsValidationScrolling;
    }

    /**
     * Should the form fields be filled with submitted values from the request object?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 20.12.2010
     */
    protected function getFillInRequestValues() {
        $fillInRequestValues = true;

        if (isset($this->preferences['fillInRequestValues'])) {
            $fillInRequestValues = $this->preferences['fillInRequestValues'];
        } else {
            $fillInRequestValues = $this->basePreferences['fillInRequestValues'];
        }

        return $fillInRequestValues;
    }

    /**
     * Should JS validation messages be shown?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 23.11.2010
     */
    protected function getShowJsValidationErrorMessages() {
        $showMessages = true;

        if (isset($this->preferences['showJsValidationErrorMessages'])) {
            $showMessages = $this->preferences['showJsValidationErrorMessages'];
        } else {
            $showMessages = $this->basePreferences['showJsValidationErrorMessages'];
        }

        return $showMessages;
    }
    
    /**
     * Should the step navigation be shown?
     *
     * @return bool
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2011 pixeltricks GmbH
     * @since 07.01.2011
     */
    protected function getShowCustomHtmlFormStepNavigation() {
        $showNavigation = false;
        
        if (isset($this->preferences['ShowCustomHtmlFormStepNavigation'])) {
            $showNavigation = $this->preferences['ShowCustomHtmlFormStepNavigation'];
        } else {
            $showNavigation = $this->basePreferences['ShowCustomHtmlFormStepNavigation'];
        }
        
        return $showNavigation;
    }

    /**
     * returns the submit button's title
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 27.10.2010
     */
    protected function getSubmitAction() {
        $action = '';

        if (isset($this->preferences['submitAction'])) {
            $action = $this->preferences['submitAction'];
        } else {
            $action = $this->basePreferences['submitAction'];
        }

        return $action;
    }

    /**
     * adds a field to the group
     *
     * @param string $groupName        the group's name
     * @param string $fieldName        the field's name
     * @param array  $fieldDefinitions the field definitions
     *
     * @return void
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 27.10.2010
     */
    protected function addFieldToGroup($groupName, $fieldName, $fieldDefinitions) {
        // create group if it does not exist yet
        if (!isset($this->fieldGroups[$groupName])) {
            $this->fieldGroups[$groupName] = array();
        }

        // check if a field with the same name exists already in the group
        if (isset($this->fieldGroups[$groupName][$fieldName])) {
            throw new Exception(
                sprintf(
                    'In the CustomHtmlForm fieldgroup "%s" the field "%s" is already defined.',
                    $groupName,
                    $fieldName
                )
            );
        }

        $this->fieldGroups[$groupName][$fieldName] = $fieldDefinitions;
    }

    /**
     * returns the controller object that should be used
     *
     * @param ContentController $controller  the calling controller
     * @param array             $preferences optional preferences
     *
     * @return ContentController
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 28.10.2010
     */
    protected function getFormController($controller, $preferences) {
        if (isset($preferences['controller'])) {
            return $preferences['controller'];
        } else {
            return $controller;
        }
    }

    /**
     * accepts a array and returns a string in Json format
     *
     * @param array $structure array of any structure
     *
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 11.11.2010
     */
    protected function createJsonFromStructure($structure) {
        $jsonStr = '';

        if (is_array($structure)) {
            $jsonStr = $this->traverseArray($structure);

            if (!empty($jsonStr)) {
                $jsonStr = substr($jsonStr, 0, -1);
                $jsonStr = '{'.$jsonStr.'}';
            }
        } else {
            $jsonStr = $structure;
        }

        return $jsonStr;
    }

    /**
     * creartes a Json string from an array recursively
     *
     * @param array $structure the array to be converted to a Json string
     * 
     * @return string
     *
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @copyright 2010 pixeltricks GmbH
     * @since 11.11.2010
     */
    private function traverseArray($structure) {

        $output = '';

        if (is_array($structure)) {
            foreach ($structure as $structureKey => $structureValue) {
                if ($structureKey !== '') {
                    $output .= $structureKey.': ';
                }

                if (is_array($structureValue)) {

                    $section = $this->traverseArray($structureValue, $output);

                    if (!empty($section)) {
                        $section = substr($section, 0, -1);
                    }

                    $output .= "{";
                    $output .= $section;
                    $output .= "},";
                } else {

                    if (is_bool($structureValue)) {
                        $structureValue = $structureValue ? 'true' : 'false';
                    } else if (is_int($structureValue)) {
                    } else {
                        if (strpos($structureValue, '"') === false &&
                            strpos($structureValue, "'") === false) {
                            $structureValue = "'".$structureValue."'";
                        }
                    }

                    $output .= $structureValue.",";
                }
            }
        } else {
            $output = $structure;
        }

        return $output;
    }
    
    /**
     * Returns whether there was an successful submission or not.
     *
     * @return bool 
     */
    public function getSubmitSuccess() {
        return $this->submitSuccess;
    }

    /**
     * Sets whether there was an successful submission or not.
     *
     * @param bool $submitSuccess Submission succeeded or not?
     *
     * @return void 
     */
    public function setSubmitSuccess($submitSuccess) {
        $this->submitSuccess = $submitSuccess;
    }

}
