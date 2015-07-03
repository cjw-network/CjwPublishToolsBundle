CjwPublishToolsBundle
==============

Copyright (C) 2007-2014 by CJW Network [www.cjw-network.com](http://www.cjw-network.com)

coolscreen.de - enterprise internet - [coolscreen.de](http://coolscreen.de)
JAC Systeme -  [www.jac-systeme.de](http://www.jac-systeme.de)
Webmanufaktur - [www.webmanufaktur.ch](http://www.webmanufaktur.ch)

License: GPL v2

***

The CjwPublishToolsBundle is an extension for eZ Publish 5 and Symfony.

This Bundle provides basic templating tools for building Websites with eZ Publish 5 similar to eZ Publish 4.

This Bundle is **Work in progress**.

**full Documentation can be found at: [www.cjw-network.com](http://www.cjw-network.com)**

***

**Why CJW Publish Tools Bundle?**

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

**Installation**:

- Download bundle
- copy to directory "/ezpublish/src/Cjw/PublishToolsBundle"
- activate bundle in "/ezpublish/EzPublishKernel.php" insert "new Cjw\PublishToolsBundle\CjwPublishToolsBundle()," in "registerBundles()" Array
- clear cache

ToDo: composer install

***

The Bundle contains three services and some example templates.

The three services are a **TwigFunctionService** that provides some Twig Template Tags:

- cjw_breadcrumb
- cjw_treemenu
- cjw_content_fetch
- cjw_content_download_file
- cjw_load_content_by_id			(maybe renaming this to cjw_content_load_by_id in the future)
- cjw_get_content_type_identifier	(maybe renaming this to cjw_content_get_type_identifier in the future)
- cjw_lang_get_default_code
- cjw_user_get_current
- cjw_redirect
- cjw_template_get_var
- cjw_template_set_var
- cjw_render_location
- cjw_siteaccess_parameters
- cjw_config_resolver_get_parameter
- cjw_config_get_parameter
- more tbd

the FormBuilderService (some notes at the end of this readme) and the **PublishToolsService**.
The **PublishToolsService** contains some helper functions and a "content fetch" function trying to emulate some features of the god old content fetch functions in eZ Publish 4.

You can easily fetch content in twig templates and build Websites without hacking controller with PHP.

***

A short twig template example:

```jinja
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
```

***

**cjw_content_fetch Parameters**:

| Name | Type | Default | Required | Description |
|---|---|---|---|---|
| depth | integer | 1 | no | if 0 than only the locations for the given loaction.id array |
| limit | integer | 0 | no | if 0 than all |
| offset | integer | 0 | no | if 0 than no offset |
| include | array | not set | no | if empty not set than all Content Types |
| datamap | boolean | false | no | wenn false dann wird das Location Object zurückgeliefert, wenn true wird das Content Object zurückgeliefert |
| sortby | array | not set | no | sort criterion array |
| language | array | not set | no | if empty not set than current language |
| count | boolean | false | no | if true include result count for pagination |
| parent | boolean | false | no | if true include parent node in result |
| filter_relation | array | not set | no | [ [ 'field', 'contains', objectId ] ] |
| filter_field | array | not set | no | [ [ 'date_to', '>', date().timestamp ] ] |
| filter_search | array | not set | no | ToDo: not implemented yet |
| filter_attribute | array | not set | no | ToDo: not implemented yet |
| mainnode | boolean | false | no | ToDo: not implemented yet |

***

Result array structure:

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

**cjw_render_location** a fast render controller ez_content:viewLocation replacement

before:
{{ render( controller(  'ez_content:viewLocation', { 'location': location 'viewType': 'line' } )  )  }}

after:
{{ cjw_render_location( {'location': location, 'viewType': 'line'} ) }}

***

**formbuilder**
- formulars can be defined in a yaml file or as an content class with infocollector fields
- stackable handler: send email, add to infocollector (needs orm), sucess
- formulars defined via content classes can use the ezpublish build in template override mechanism
- easy to use frontend editing (add and edit content)
- easy to use user register
- hacking php classes is not needed


