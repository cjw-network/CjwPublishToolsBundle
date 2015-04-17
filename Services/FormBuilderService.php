<?php
/**
 * File containing the FormBuilderService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 */

namespace Cjw\PublishToolsBundle\Services;

use Symfony\Component\Yaml\Yaml;
//use eZ\Publish\Core\FieldType\XmlText\Input\EzXml as EzXmlInput;

class FormBuilderService
{
    protected $container;
    protected $config;

    /**
     * init the needed services, load the config from formbuilder.yml
     */
    public function __construct( $container )
    {
        $this->container = $container;
        $this->config = Yaml::parse( __DIR__ . '/../../../' . $this->getCurrentSiteBundle()->prefix . '/' . $this->getCurrentSiteBundle()->name . '/Resources/config/formbuilder.yml' );
    }

    /**
     * Build a form with given schema / entity
     *
     * @param array $formSchemaArr
     * @param array $formValueArr
     * @param string $languageCode
     * @param array $parameters
     *
     * @return mixed $form
     */
    public function formBuilder( $formSchemaArr, $formValueArr, $languageCode = 'eng-GB', $parameters = false )
    {
//        $formBuilder = $this->createFormBuilder( $formValueArr );
        $formBuilder = $this->container->get('form.factory')->createBuilder( 'form', $formValueArr );

        foreach ( $formSchemaArr as $key => $field )
        {
            $formAttrArr = array( 'label' => $field['label'],
                                  'required' => $field['required'],
//                                  'mapped' => false,
                                  'trim' => true );

            if ( $field['choices'] !== false )
            {
                $formAttrArr['choices'] = $field['choices'];
                $formAttrArr['multiple'] = $field['multiple'];
                $formAttrArr['empty_value'] = false;
                // http://symfony.com/doc/current/reference/forms/types/choice.html
//                $formAttrArr['placeholder'] = false;
//                $formAttrArr['expanded'] = true;
            }

            // http://symfony.com/doc/current/reference/forms/types/form.html
            $formBuilder->add( $key, $field['type'], $formAttrArr );
        }

        $labelSaveButton = 'Save';
        $labelCancelButton = 'Cancel';

        if ( $parameters )
        {
            if ( isset( $parameters['button_config']['save_button']['label'][$languageCode] ) )
            {
                $labelSaveButton = $parameters['button_config']['save_button']['label'][$languageCode];
            }
            if ( isset( $parameters['button_config']['cancel_button']['label'][$languageCode] ) )
            {
                $labelCancelButton = $parameters['button_config']['cancel_button']['label'][$languageCode];
            }
        }

        $formBuilder->add( 'save', 'submit', array( 'label' => $labelSaveButton ) );

        if( $parameters['button_config']['cancel_button'] !== false )
        {
            $formBuilder->add( 'cancel', 'submit', array( 'label' => $labelCancelButton, 'attr' => array( 'formnovalidate' => '' ) ) );
        }

        return $formBuilder->getForm();
    }

    /**
     * Get and builds a form schema / entity from formbuilder.yml
     *
     * @param string $formIdentifier
     *
     * @return array $formSchemaArr,$formValueArr
     */
    public function getFormSchemaFromYamlConfig( $formIdentifier )
    {
        $formValueArr  = array();
        $formSchemaArr = array();

        $formSchemaConfig = false;
        if ( isset( $this->config['formbuilder_config'][$formIdentifier] ) )
        {
            $formSchemaConfig = $this->config['formbuilder_config'][$formIdentifier];
        }

        if ( $formSchemaConfig !== false && isset( $formSchemaConfig['fields'] ) )
        {
            // ToDo
            foreach ( $formSchemaConfig['fields'] as $key => $field )
            {
                $required = true;
                if( isset( $field['is_required'] ) && $field['is_required'] === false )
                {
                    $required = false;
                }

                $formSchemaArr[$key] = array(
                    'type' => $field['field_type'],
                    'label' => $field['field_label'],
                    'required' => $required,
                    'choices' => false
                );
            }
        }

        return array( $formSchemaArr, $formValueArr );
    }

    /**
     * reimp this function!
     * 
     * Get and builds a form schema / entity from an content object with fields
     *
     * @param string $contentType
     * @param string $languageCode
     * @param \eZ\Publish\API\Repository\Values\Content\Content $content
     * @param bool $isCollector
     *
     * @return array $formSchemaArr,$formValueArr
     */
    public function getFormSchemaFromContentObjectFields( $contentType, $languageCode = 'eng-GB', $content = false, $isCollector = false )
    {
        $formValueArr  = array();
        $formSchemaArr = array();

        $contentTypeConfig = false;
        if ( isset( $this->config['frontendediting_config']['types'][$contentType->identifier] ) )
        {
            $contentTypeConfig = $this->config['frontendediting_config']['types'][$contentType->identifier];
        }

        foreach ( $contentType->getFieldDefinitions() as $field )
        {
            if ( $isCollector !== true || ( $isCollector === true && $field->isInfoCollector ) )
            {
                if ( !isset( $contentTypeConfig['fields'] ) || in_array( $field->identifier, $contentTypeConfig['fields'] ) )
                {
                    $fieldArr  = array();
                    $fieldArr1 = false;
                    $fieldArr2 = false;

    //                $fieldArr['position'] = $field->position;
                    $fieldArr['label']    = $field->names[$languageCode];
                    $fieldArr['required'] = $field->isRequired;
                    $fieldArr['choices']  = false;

                    // map ez types to symfony types
                    // http://symfony.com/doc/current/reference/forms/types.html
                    // ToDo: to many
                    switch ( $field->fieldTypeIdentifier )
                    {
                        case 'ezstring':
                            $formFieldIdentifier = 'ezstring:'.$field->identifier;
                            $fieldArr['type'] = 'text';
                            $fieldArr['value'] = $field->defaultValue->text;
                            break;

                        case 'eztext':
                            $formFieldIdentifier = 'eztext:'.$field->identifier;
                            $fieldArr['type'] = 'textarea';
                            $fieldArr['value'] = $field->defaultValue->text;
                            break;

                        case 'ezxmltext' :
                            $formFieldIdentifier = 'ezxmltext:'.$field->identifier;
                            $fieldArr['type'] = 'textarea';
// ToDo
//                            $fieldArr['value'] = $this->ezXmltextToHtml( $content, $field );
                            $fieldArr['value'] = '';
                            break;

                        case 'ezemail':
                            $formFieldIdentifier = 'ezemail:'.$field->identifier;
                            $fieldArr['type'] = 'email';
                            $fieldArr['value'] = $field->defaultValue->email;
                            break;

                        case 'ezuser' :
// ToDo: many
                            $formFieldIdentifier = 'ezuser:'.$field->identifier.':login';
                            $fieldArr['type'] = 'text';
                            $fieldArr['label'] = 'User Login';
                            $fieldArr['value'] = '';

                            $fieldArr1 = array();
                            $fieldArr1['type'] = 'email';
                            $fieldArr1['label'] = 'User E-Mail';
                            $fieldArr1['required'] = true;
                            $formFieldIdentifier1 = 'ezuser:'.$field->identifier.':email';
                            $fieldArr1['value'] = '';
                            $fieldArr1['choices']  = false;

                            $fieldArr2 = array();
                            $fieldArr2['type'] = 'password';
                            $fieldArr2['label'] = 'User Password';
                            $fieldArr2['required'] = true;
                            $formFieldIdentifier2 = 'ezuser:'.$field->identifier.':password';
                            $fieldArr2['value'] = '';
                            $fieldArr2['choices']  = false;
                            break;

                        // ToDo: multiple / single, select / radio / checkbox, etc.
                        case 'ezselection':
                            $formFieldIdentifier = 'ezselection:'.$field->identifier;
                            $fieldArr['type'] = 'choice';
                            $fieldArr['choices'] = $field->fieldSettings['options'];
                            // http://stackoverflow.com/questions/17314996/symfony2-array-to-string-conversion-error
                            if ( $field->fieldSettings['isMultiple'] )
                            {
                                $fieldArr['multiple'] = true;
                                $fieldArr['value'] = $field->defaultValue->selection;
                            }
                            else
                            {
                                $fieldArr['multiple'] = false;
                                $fieldArr['value'] = false;
                                if ( isset( $field->defaultValue->selection['0'] ) ) 
                                {
                                    $fieldArr['value'] = $field->defaultValue->selection['0'];
                                }
                            }
                            break;

                        default:
                            $formFieldIdentifier = 'default:'.$field->identifier;
                            $fieldArr['type'] = 'text';
                            $fieldArr['value'] = '';
                    }

                    // build / set entity array dynamicaly from fieldtype
                    if ( $content )
                    {
                        $formFieldIdentifierArr = explode( ':', $formFieldIdentifier );
                        switch ( $formFieldIdentifierArr['0'] )
                        {
                            case 'ezuser':
                                    $fieldArr['value'] = $content->fields[$field->identifier][$languageCode]->login;
                                    $fieldArr1['value'] = $content->fields[$field->identifier][$languageCode]->email;
                                break;

                            default:
                                switch ( $fieldArr['type'] )
                                {
                                    case 'choice':
                                        // http://stackoverflow.com/questions/17314996/symfony2-array-to-string-conversion-error
                                        if ( $fieldArr['multiple'] )
                                        {
                                            $fieldArr['value'] = $content->fields[$field->identifier][$languageCode]->selection;
                                        }
                                        else
                                        {
                                            $fieldArr['value'] = $content->fields[$field->identifier][$languageCode]->selection['0'];
                                        }
                                        break;

                                    default:
                                        if ( isset( $content->fields[$field->identifier][$languageCode]->text ) )
                                        {
                                            $fieldArr['value'] = $content->fields[$field->identifier][$languageCode]->text;
                                        }
                                }
                        }
                    }

                    $formValueArr[$formFieldIdentifier] = $fieldArr['value'];
                    $formSchemaArr[$formFieldIdentifier] = $fieldArr;

                    if ( $fieldArr1 !== false )
                    {
                        $formValueArr[$formFieldIdentifier1] = $fieldArr1['value'];
                        $formSchemaArr[$formFieldIdentifier1] = $fieldArr1;
                    }

                    if ( $fieldArr2 !== false )
                    {
                        $formValueArr[$formFieldIdentifier2] = $fieldArr2['value'];
                        $formSchemaArr[$formFieldIdentifier2] = $fieldArr2;
                    }
                }
            }
        }

        return array( $formSchemaArr, $formValueArr );
    }

    /**
     * Builds a ez content struct with the given form data
     *
     * @param mixed $formDataObj
     * @param mixed $contentStruct
     *
     * @return array contentStruct,ezuser
     */
    public function buildContentStructWithFormData( $formDataObj, $contentStruct )
    {
        $ezuser = array();

        // Setting the fields values
        foreach ( $formDataObj as $key => $value )
        {
            $keyArr = explode( ':', $key );
            $property = $keyArr['1'];

            switch ( $keyArr['0'] )
            {
                case 'ezselection':
                    // http://stackoverflow.com/questions/17314996/symfony2-array-to-string-conversion-error
                    if ( is_array( $value ) )
                    {
                        $contentStruct->setField( $property, $value );
                    }
                    else
                    {
                        $contentStruct->setField( $property, array( $value ) );
                    }
                    break;

                case 'ezxml':
                    $contentStruct->setField( $property, $this->newEzXmltextSchema( $value ) );
                    break;

                case 'ezuser':
                    $ezuser[$keyArr['2']] = $value;
                    break;

                default:
                    $contentStruct->setField( $property, $value );
            }
        }

        if ( count( $ezuser ) != 3 )
        {
            $ezuser = false;
        }

        return array( 'contentStruct' => $contentStruct, 'ezuser' => $ezuser );
    }

    /**
     * Get the handler config blocks by $type from formbuilder.yml and merge them
     *
     * @param array $configBlock
     * @param string $type
     *
     * @return array $handlerConfigArr
     */
    public function getFormConfigHandler( $configBlock, $type )
    {
        $handlerConfigArr = array();

        $handlerConfigBlock1 = array();
        if ( isset( $this->config[$configBlock]['handler'] ) )
        {
            $handlerConfigBlock1 = $this->config[$configBlock]['handler'];
        }

        $handlerConfigBlock2 = array();
        if ( isset( $this->config[$configBlock]['types'][$type]['handler'] ) )
        {
            $handlerConfigBlock2 = $this->config[$configBlock]['types'][$type]['handler'];
        }

        $handlerConfigBlock= array_merge( $handlerConfigBlock1, $handlerConfigBlock2 );

        if ( isset( $handlerConfigBlock ) && is_array( $handlerConfigBlock ) && count( $handlerConfigBlock ) > 0 )
        {
            foreach( $handlerConfigBlock as $key => $handlerConfig )
            {
                $handlerConfigBlock1 = array();
                if ( isset( $this->config['global_config']['handler'][$key] ) )
                {
                    $handlerConfigBlock1 = $this->config['global_config']['handler'][$key];
                }

                $handlerConfigBlock2 = array();
                if ( isset( $this->config[$configBlock]['handler'][$key] ) )
                {
                    $handlerConfigBlock2 = $this->config[$configBlock]['handler'][$key];
                }

                $handlerConfigBlock3 = array();
                if ( isset( $this->config[$configBlock]['types'][$type]['handler'][$key] ) )
                {
                    $handlerConfigBlock3 = $this->config[$configBlock]['types'][$type]['handler'][$key];
                }

                // merge the two config blocks into one
                $handlerConfigArr[$key] = array_merge( $handlerConfigBlock1, $handlerConfigBlock2, $handlerConfigBlock3 );
            }
        }

        return $handlerConfigArr;
    }

    /**
     * Get a config blocks by action and $type from formbuilder.yml and merge them
     *
     * @param array $config
     * @param string $action
     * @param string $type
     *
     * @return array config
     */
    public function getFormConfigType( $config, $action, $type )
    {
        $configTypeGlobal = array();
        if ( isset( $this->config['global_config'][$config] ) )
        {
            $configTypeGlobal = $this->config['global_config'][$config];
        }

        $configTypeAction = array();
        if ( isset( $this->config[$action][$config] ) )
        {
            $configTypeAction = $this->config[$action][$config];
        }

        $configTypeItem = array();
        if ( isset( $this->config[$action]['types'][$type][$config] ) )
        {
            $configTypeItem = $this->config[$action]['types'][$type][$config];
        }

        return array_merge( $configTypeGlobal, $configTypeAction, $configTypeItem );
    }

    /**
     * Get the current site bundle name and prefix
     *
     * @return object
     */
    public function getCurrentSiteBundle()
    {
// ToDo: besser lösen, ist folgendes kompatible mit plain ezp5, was ist wenn tools bundle in vendor dir ?
        $siteBundlePathArr = array_reverse( explode( '/', $this->container->getParameter( 'kernel.root_dir' ) ) );

        return (object) array( 'prefix' => $siteBundlePathArr['2'], 'name' => $siteBundlePathArr['1'] );
    }

    /**
     * Builds an valid template string
     *
     * @param string $tplSettingsStr
     *
     * @return string $template
     */
    public function getTemplateOverride( $tplSettingsStr = '' )
    {
        $tplSettingsArr = array_reverse( explode( ':', $tplSettingsStr ) );

        $tplArr = array();

        if ( isset( $tplSettingsArr['0'] ) )
        {
            $tplArr['0'] = trim( $tplSettingsArr['0'] );
        }
        else
        {
            $tplArr['0'] = trim( $tplSettingsStr );
        }

        if ( isset( $tplSettingsArr['1'] ) && trim( $tplSettingsArr['1'] ) )
        {
            $tplArr['1'] = trim( $tplSettingsArr['1'] );
        }
        else
        {
            $tplArr['1'] = '';
        }

        if ( isset( $tplSettingsArr['2'] ) && trim( $tplSettingsArr['2'] ) != '' )
        {
            $tplArr['2'] = trim( $tplSettingsArr['2'] );
        }
        else
        {
            $tplArr['2'] = $this->getCurrentSiteBundle()->prefix.$this->getCurrentSiteBundle()->name;
        }

        if ( $tplArr['0'] !== '' )
        {
            $template = implode( ':', array_reverse( $tplArr ) );
        }
        else
        {
            $template = false;
        }

        return $template;
    }

    // http://share.ez.no/forums/ez-publish-5-platform/papi-convert-xmltext-field
    private function ezXmltextToHtml( $content, $field )
    {
        $xmlTextValue = $content->getFieldValue( $field );
        /** @var \eZ\Publish\Core\FieldType\XmlText\Converter\Html5 $html5Converter */
        $html5Converter = $this->get( 'ezpublish.fieldType.ezxmltext.converter.html5' );
        $html = $html5Converter->convert( $xmlTextValue->xml );

        return trim( $html );
    }

    // https://doc.ez.no/display/EZP/The+XmlText+FieldType
    // https://doc.ez.no/display/EZP/How+to+implement+a+Custom+Tag+for+XMLText+FieldType
    public function newEzXmltextSchema( $value )
    {
$inputString = <<<EZXML
<?xml version="1.0" encoding="utf-8"?>
<section
    xmlns:custom="http://ez.no/namespaces/ezpublish3/custom/"
    xmlns:image="http://ez.no/namespaces/ezpublish3/image/"
    xmlns:xhtml="http://ez.no/namespaces/ezpublish3/xhtml/">
    <paragraph>$value</paragraph>
</section>
EZXML;

        return new EzXmlInput( trim( $inputString ) );
    }
}
