#CjwPublishToolsBundle

Copyright (C) 2007-2015 by CJW Network [www.cjw-network.com](http://www.cjw-network.com)

coolscreen.de - enterprise internet - [coolscreen.de](http://coolscreen.de)
JAC Systeme -  [www.jac-systeme.de](http://www.jac-systeme.de)
Webmanufaktur - [www.webmanufaktur.ch](http://www.webmanufaktur.ch)

License: GPL v2

***

The CjwPublishToolsBundle is an extension for eZ Publish 5 and Symfony.

This Bundle provides basic templating tools for building Websites with eZ Publish 5 similar to eZ Publish 4.

This Bundle is **Work in progress**.

***

##Why CJW Publish Tools Bundle?

There are two types of technical eZ Publish user:

1. A developer / programmer
    + speaks fluent PHP
    + no problems with digging into symfony
    + used to think in extensions / components
2. An integrator
    + comes from front end design and speaks fluent HTML and CSS
    + no problems with digging into template languages
    + prefer solutions with all needed functionality

eZ Publish legacy was perfect for both types of users, eZ Publish 5 on Symfony stack is perfect only for the first type. But this we’ll start to change, we try to set a starting point to give back eZ Publish one of its Unique Selling Proposition (USP): Build eCMS Solutions for developers and integrators. By the way we show the power of the Symfony stack integrations.

The “philosophical” thought behind this: Focussing on the big picture and don’t get lost in the little things. 

You need for every website a search and human friendly HTML title, a breadcrumb navigation, one or multiple menus, a list of children of a location or a pagination. And you need it fast and easy, in template and in PHP.

Discussion to this topic: [http://share.ez.no/forums/ez-publish-5-platform/ez-publish-5-and-web-integrators](http://share.ez.no/forums/ez-publish-5-platform/ez-publish-5-and-web-integrators)

***

##Installation

- Download bundle
- copy to directory "/{ezroot}/src/Cjw/PublishToolsBundle"
- activate bundle in "/{ezroot}/ezpublish/EzPublishKernel.php" insert "new Cjw\PublishToolsBundle\CjwPublishToolsBundle()," in "registerBundles()" Array
- clear cache

ToDo: composer install

***

## Doku

The Bundle contains three services and some example templates.

The three services are a **TwigFunctionService** that provides some Twig Template Tags:

- cjw_content_fetch ( [parameters](#cjw_content_fetch-parameters) , [result structure](#cjw_content_fetch-result) , [example](#cjw_content_fetch-example) )
- cjw_breadcrumb ( [example](https://github.com/cjw-network/CjwPublishToolsBundle/blob/master/Resources/views/parts/breadcrumb.html.twig) )
- cjw_treemenu ( [example](https://github.com/cjw-network/CjwPublishToolsBundle/blob/master/Resources/views/parts/treemenu-test.html.twig) )
- [cjw_content_download_file](#cjw_content_download_file)
- [cjw_load_content_by_id](#cjw_load_content_by_id)	(maybe renaming this to cjw_content_load_by_id in the future)
- cjw_get_content_type_identifier						(maybe renaming this to cjw_content_get_type_identifier in the future)
- cjw_lang_get_default_code
- cjw_user_get_current
- cjw_redirect
- [cjw_file_exists](#cjw_file_exists)
- [cjw_template_get_var](#cjw_template_var) ... with additonal new example
- [cjw_template_set_var](#cjw_template_var)
- [cjw_render_location](#cjw_render_location)
- cjw_siteaccess_parameters
- cjw_config_resolver_get_parameter
- cjw_config_get_parameter
- more tbd

the FormBuilderService ([some notes at the end of this readme](#FormBuilder)) and the **PublishToolsService**.
The **PublishToolsService** contains some helper functions and a "content fetch" function trying to emulate some features of the god old content fetch functions in eZ Publish 4.

You can easily fetch content in twig templates and build Websites without hacking controller with PHP.

***

###A short twig template example
	
	{% extends site_bundle_name ~ '::pagelayout.html.twig' %}

	{% block content %}
		<div class="class-{{ content.contentInfo.contentTypeId }} content-view-full">
			<h1 class="content-header">{{ ez_content_name( content ) }}</h1>

			{% if content.fields.short_description is defined and not ez_is_field_empty( content, 'short_description' ) %}
				{{ ez_render_field( content, 'short_description' ) }}
			{% endif %}

			{% set listLimit = 10 %}
			{% set listOffset = 0 %}
			{% if ezpublish.viewParameters().offset is defined %}
				{% set listOffset = ezpublish.viewParameters().offset %}
			{% endif %}

			{% set listChildren = cjw_fetch_content( [ location.id ], { 'depth': '1',
																		'limit': listLimit,
																		'offset': listOffset,
																		'include': [ 'folder', 'article' ],
																		'datamap': false,
																		'count': true } ) %}
			{% set listCount = listChildren[location.id]['count'] %}

			<div class="content-view-children">
 				{% for child in listChildren[location.id]['children'] %}
					{{ render( controller( "ez_content:viewLocation", {'locationId': child.contentInfo.mainLocationId, 'viewType': 'line'} ) ) }}
				{% endfor %}
			</div>

			{% if listCount > listLimit %}
				{% include (site_bundle_name ~ ':parts:navigator.html.twig') with { 'page_uri': ezpublish.requestedUriString(),
																					'item_count': listCount,
																					'view_parameters': ezpublish.viewParameters(),
																					'item_limit': listLimit } %}
			{% endif %}
		</div>
	{% endblock %}
	
here you can find an example include temple for pagination: [:parts:navigator.html.twig](https://github.com/cjw-network/CjwPublishToolsBundle/blob/master/Resources/views/parts/navigator.html.twig)

***

<a name="cjw_content_fetch-parameters"></a>
###cjw_content_fetch Parameters

| Name | Type | Default | Required | Description |
|---|---|---|---|---|
| depth | integer | 1 | no | if 0 than only the locations for the given loaction.id array |
| limit | integer | 0 | no | if 0 than all |
| offset | integer | 0 | no | if 0 than no offset |
| include | array | not set | no | if empty not set than all Content Types |
| datamap | boolean | false | no | wenn false dann wird das Location Object zurückgeliefert, wenn true wird das Content Object zurückgeliefert |
| sortby | array | not set | no | [ [ 'article', 'publish_date', 'DESC', 'eng-GB' ], [ 'DatePublished', 'DESC' ] ] |
| language | array | not set | no | if empty not set than current language |
| count | boolean | false | no | if true include result count for pagination |
| parent | boolean | false | no | if true include parent node in result |
| main_location_only | boolean | false | no | list only main locations |
| filter_relation | array | not set | no | [ [ 'relation_field', 'contains', objectId ] ] |
| filter_field | array | not set | no | [ [ 'date_to', '>', date().timestamp ] ] in ezp 1411 the 'date_to' field needs to be set searchable! |
| filter_search | array | not set | no | ToDo: not implemented yet |
| filter_attribute | array | not set | no | ToDo: not implemented yet |

**cjw_content_fetch sortby parameter parameters**

The sortby parameter is an array of sortby parameter arrays.

A sortby parameter array with **two items** means sort by object metatdata / attribute.
The first parameter array item can be one of the following:

- LocationPath
- LocationDepth
- LocationPriority
- ContentName
- ContentId
- DateModified
- DatePublished

The second parameter array item is 'ASC' or 'DESC'

A sortby parameter array with **4 items** means sort by field attribute.

- first item is the content / class identifier
- second item is is the field identifier
- third item is 'ASC' or 'DESC'
- forth item is the language or null if the field is not translatable

If none sortby parameter are specified, the default sort will be the sort criterion of the parent node (specified in the admin backend).

**cjw_content_fetch allowed operators for the filter_field parameter**

- '='
- '>'
- '>='
- '<'
- '<='
- 'in'
- 'between'
- 'like'
- 'contains'

***

<a name="cjw_content_fetch-result"></a>
###cjw_content_fetch Result array structure

	Array
		|--[location.id.1]
		|			|
		|			|--[children]   ezp5 search service result array of objects
		|			|
		|			|--[count]
		|			|
		|			|--[parent]   ep5 object
		|
		|
		|--[location.id.2]
		|			|
		|			|-- ...
		|			.
		|			.
		|			.
		|-- ...

***

<a name="cjw_content_fetch-example"></a>
###cjw_content_fetch example

fetch an tree of nodes / locations for an given node / location id get the parent and count, sort by published date

**ezp 4.x smarty:**
	
	{def $node_id = 2
		 $limit = 10
		 $offset = 10
		 $depth = 5
		 $include = array( 'article' )

		 $list_items = fetch( 'content', 'list', hash( 'parent_node_id', $node_id,
													   'depth', $depth,
											 		   'limit', $limit,
													   'offset', $offset,
													   'class_filter_type', 'include',
													   'class_filter_array', $include,
													   'sort_by', array( 'published', true() ) ) )

		 $list_count = fetch( 'content', 'list_count', hash( 'parent_node_id', $node_id,
															 'depth': $depth,
															 'class_filter_type', 'include',
															 'class_filter_array', $include ) )

		 $parent_node = fetch( 'content', 'node', hash( 'node_id', $node_id ) ) }

	{foreach $list_items as $item}
		{$item|attribute( show, 5 )}
	{/foreach}
	
**ezp 5.x twig:**
	
	{% set location_id = 2 %}
	{% set limit = 10 %}
	{% set offset = 10 %}
	{% set include = [ 'article' ] %}
	{% set depth = 5 %}
	{% set content_object = false %}

	{% set list_items = cjw_fetch_content( [ location_id ], { 'depth': depth,
															  'limit': limit,
															  'offset': offset,
															  'include': include,
															  'sortby': [ [ 'DatePublished', 'DESC' ] ],
															  'parent': true,
															  'count': true } ) %}

	{% set list_count = list_items[ location_id ][ 'count' ] %}

	{% set parent_location = list_items[ location_id ][ 'parent' ] %}

	{% for item in list_items[ location_id ][ 'children' ] %}
		{# show the location object, if you use the "'datamap': true" parameter, this will show the content object #}
		{{ dump( item ) }}

		{# example for getting the content object for this location, if needed #}
		{#
			{% set content_object = cjw_load_content_by_id( item.contentInfo.id ) %}
			{{ dump( content_object ) }}
		#}
	{% endfor %}
	
#### more cjw_content_fetch examples

get an single location object by location.id
	
	{% set locationId = 2 %}
	{% set locationObject = cjw_fetch_content( [ locationId ], { 'depth': 0 } )[locationId]['0'] %}
	{{ dump( locationObject ) }}
	

get an single content object by location.id
	
	{% set locationId = 2 %}
	{% set contentObject = cjw_fetch_content( [ locationId ], { 'depth': 0, 'datamap': true } )[locationId]['0'] %}
	{{ dump( contentObject ) }}
	

get multiple location objects by location.ids
	
	{% set locationId1 = 2 %}
	{% set locationId2 = 43 %}

	{% set locationArray= cjw_fetch_content( [ locationId1, locationId2 ], { 'depth': 0 } )%}

	{% set locationOject1 = locationArray[locationId1]['0'] %}
	{% set locationOject2 = locationArray[locationId2]['0'] %}

	{{ dump( locationObject1 ) }}
	{{ dump( locationObject2 ) }}
	
***

<a name="cjw_load_content_by_id"></a>
###cjw_load_content_by_id example

get an content object by an content object id
this is usefull for fetching related content objects
	
	{% if not ez_is_field_empty( content, 'related' ) %}
		{% set destinationContentIds = ez_field_value( content, 'related' ).destinationContentIds %}

		{% for destinationContentId in destinationContentIds %}
			{% set relatedContent = cjw_load_content_by_id( destinationContentId ) %}
			{#{ dump( relatedContent ) }#}

			{{ ez_content_name( relatedContent ) }}

			{#{ render( controller( 'ez_content:viewLocation', { 'locationId': versionInfo.contentInfo.mainLocationId, 'viewType': 'line' } ) ) }#}
		{% endfor %}
	{% endif %}
	
get an related image (main_image):
	
	{% set mainImage = cjw_load_content_by_id( ez_field_value( content, 'main_image' ).destinationContentIds['0'] ) %}
	{{ dump( mainImage ) }}
	
***

<a name="cjw_content_download_file"></a>
###cjw_content_download_file example

override the ez ezbinaryfile field type template:
	
	{# @todo: handle the unit of the fileSize (si operator in legacy template engine) #}
	{% block ezbinaryfile_field %}
	{% spaceless %}
		{% if not ez_is_field_empty( content, field ) %}
			<a href="{{ path( 'ez_urlalias', {'locationId': content.contentInfo.mainLocationId} ) }}" {{ block( 'field_attributes' ) }}>{{ field.value.fileName }}</a>&nbsp;({{ field.value.fileSize|ez_file_size( 1 ) }})
		{% endif %}
	{% endspaceless %}
	{% endblock %}
	
the location view full template for the file content type
	
	{% if content.fields.file is defined %}
		{{cjw_content_download_file( content.fields.file[cjw_lang_get_default_code()] )}}
	{% endif %}
	
***

<a name="cjw_file_exists"></a>
###cjw_file_exists example

if you won't get an 500 error if an image not exists,

override the ezimage field type template:
	
	{% block ezimage_field %}
	{% spaceless %}
		{% if not ez_is_field_empty( content, field ) %}
			{# check if the original image exists #}
			{% if cjw_file_exists( '.'~field.value.uri ) and field.value.uri != null %}
				{% set image_alias = ez_image_alias( field, versionInfo, parameters.alias|default( 'original' ) ) %}
				{# check if the image variation exists #}
				{% if image_alias.uri is defined %}
					<img src="{{ asset( image_alias.uri ) }}" width="{{ image_alias.width }}" height="{{ image_alias.height }}" alt="{{ parameters.alt|default( field.value.alternativeText ) }}" class="img-responsive" />
				{% endif %}
			{% endif %}
		{% endif %}
	{% endspaceless %}
	{% endblock %}
	
***

<a name="cjw_template_var"></a>
###cjw_template_get/set_var example
	
	<!DOCTYPE html>
	<html>
		<head></head>
		<body>
			{{ dump( cjw_template_get_var( 'testVariable' ) ) }}
			{% include ( site_bundle_name ~ '::test.inc.html.twig' ) %}
		</body>
	</html>
	
.
	
	{# the included test.inc.html.twig #}

	...

	{{ cjw_template_set_var( 'testVariable', 'testValue') }}

	{#  the variable value can be an array #}

	...
	
###cjw_template_get/set_var another practical example for test purposes

*pagelayout.html.twig*

	{# insert after body or anything else tag ... #}
	
	{# Additional Debug Output ... two variants, via block and via our new twig function #}
	{% if app.environment == 'dev' %}
    		<div style="margin-bottom: 2em;">
    			<h3>CJW Debug Output</h3>
        			<h4>From <b>cjw_debug</b> Block</h3>
        				{% block cjw_debug %}
        				{% endblock %}
        			<h4>From <b>cjwDebug</b> Variable</h4>
            				<h5>cjw_template_get_var( 'cjwDebug' )</h5>
            					{{ cjw_template_get_var( 'cjwDebug' ) }}
            					<hr />
    		</div>
	{% endif  %}
	
*xyz.html.twig*

	{% set cjwDebug %}
    		<h6>From xyz.html.twig</h6>
    		{{ dump() }}
	{% endset %}
	{{ cjw_template_set_var( 'cjwDebug', cjwDebug ) }}
	
***

<a name="cjw_render_location"></a>
###cjw_render_location - a fast render controller "ez_content:viewLocation" replacement

before:

    {{ render( controller(  'ez_content:viewLocation', { 'location': location 'viewType': 'line' } )  )  }}

after:

    {{ cjw_render_location( {'location': location, 'viewType': 'line'} ) }}

***

###formbuilder<a name="FormBuilder"></a>
- formulars can be defined in a yaml file or as an content class with infocollector fields
- stackable handler: send email, add to infocollector (needs orm), sucess
- formulars defined via content classes can use the ezpublish build in template override mechanism
- easy to use frontend editing (add and edit content)
- easy to use user register
- hacking php classes is not needed
