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
 * Interface for a DataExtension to decorate a CustomHtmlForm
 *
 * @package CustomHtmlForm
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2016 pixeltricks GmbH
 * @since 30.05.2016
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
interface CustomHtmlFormExtension {
    
    /**
     * This method will be called instead of a CustomHtmlForms process method
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 22.03.2012
     */
    public function extendedProcess();
    
    /**
     * This method will be called after CustomHtmlForm's __construct().
     * 
     * @param ContentController $controller  the calling controller instance
     * @param array             $params      optional parameters
     * @param array             $preferences optional preferences
     * @param bool              $barebone    defines if a form should only be instanciated or be used too
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.01.2014
     */
    public function onAfterConstruct($controller, $params, $preferences, $barebone);
    
    /**
     * Adds some custom markup to the CustomHtmlFormSpecialFields markup on 
     * after the default markup will be added.
     * 
     * @param string &$fieldsMarkup Fields markup to update
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 02.07.2013
     */
    public function onAfterCustomHtmlFormSpecialFields(&$fieldsMarkup);
    
    /**
     * This method will be called after CustomHtmlForm's default submitFailure.
     * You can manipulate the relevant data here.
     * 
     * @param SS_HTTPRequest &$data submit data
     * @param Form           &$form form object
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function onAfterSubmitFailure(&$data, &$form);
    
    /**
     * This method will be called after CustomHtmlForm's default submitSuccess.
     * You can manipulate the relevant data here.
     * 
     * @param SS_HTTPRequest &$data     submit data
     * @param Form           &$form     form object
     * @param array          &$formData secured form data
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function onAfterSubmitSuccess(&$data, &$form, &$formData);
    
    /**
     * This method will be called before CustomHtmlForm's __construct().
     * 
     * @param ContentController $controller  the calling controller instance
     * @param array             $params      optional parameters
     * @param array             $preferences optional preferences
     * @param bool              $barebone    defines if a form should only be instanciated or be used too
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.01.2014
     */
    public function onBeforeConstruct($controller, $params, $preferences, $barebone);
    
    /**
     * Adds some custom markup to the CustomHtmlFormSpecialFields markup on 
     * before the default markup will be added.
     * 
     * @param string &$fieldsMarkup Fields markup to update
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 02.07.2013
     */
    public function onBeforeCustomHtmlFormSpecialFields(&$fieldsMarkup);
    
    /**
     * This method will be called before CustomHtmlForm's default submit.
     * You can manipulate the relevant data here.
     * 
     * @param SS_HTTPRequest &$data submit data
     * @param Form           &$form form object
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 24.01.2014
     */
    public function onBeforeSubmit(&$data, &$form);
    
    /**
     * This method will be called before CustomHtmlForm's default submitFailure.
     * You can manipulate the relevant data here.
     * 
     * @param SS_HTTPRequest &$data submit data
     * @param Form           &$form form object
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function onBeforeSubmitFailure(&$data, &$form);
    
    /**
     * This method will be called before CustomHtmlForm's default submitSuccess.
     * You can manipulate the relevant data here.
     * 
     * @param SS_HTTPRequest &$data     submit data
     * @param Form           &$form     form object
     * @param array          &$formData secured form data
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function onBeforeSubmitSuccess(&$data, &$form, &$formData);
    
    /**
     * This method will replace CustomHtmlForm's default submitFailure. It's
     * important that this method returns sth. to ensure that the default 
     * submitFailure won't be called. The return value should be a rendered 
     * template or sth. similar.
     * You can also trigger a direct or redirect and return what ever you want
     * (perhaps boolean true?).
     * 
     * @param SS_HTTPRequest &$data submit data
     * @param Form           &$form form object
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function overwriteSubmitFailure(&$data, &$form);
    
    /**
     * This method will replace CustomHtmlForm's default submitSuccess. It's
     * important that this method returns sth. to ensure that the default 
     * submitSuccess won't be called. The return value should be a rendered 
     * template or sth. similar.
     * You can also trigger a direct or redirect and return what ever you want
     * (perhaps boolean true?).
     * 
     * @param SS_HTTPRequest &$data     submit data
     * @param Form           &$form     form object
     * @param array          &$formData secured form data
     * 
     * @return string
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function overwriteSubmitSuccess(&$data, &$form, &$formData);
    
    /**
     * This method is called before CustomHtmlForm requires the form fields. You 
     * can manipulate the default form fields here.
     * 
     * @param array &$formFields Form fields to manipulate
     * 
     * @return bool
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.11.2011
     */
    public function updateFormFields(&$formFields);
    
    /**
     * This method is called before CustomHtmlForm set the preferences. You 
     * can manipulate the default preferences here.
     * 
     * @param array &$preferences Preferences to manipulate
     * 
     * @return bool
     * 
     * @author Sascha Koehler <skoehler@pixeltricks.de>
     * @since 28.11.2011
     */
    public function updatePreferences(&$preferences);
}