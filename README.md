CjwPublishToolsBundle
==============

Copyright (C) 2007-2014 by CJW Network [www.cjw-network.com](http://www.cjw-network.com)

coolscreen.de - enterprise internet - [coolscreen.de](http://coolscreen.de)
JAC Systeme -  [www.jac-systeme.de](http://www.jac-systeme.de)
Webmanufaktur - [www.webmanufaktur.ch](http://www.webmanufaktur.ch)

License: GPL v2

------------------------------------

The CjwPublishToolsBundle is an extension for eZ Publish 5 and Symfony.

This Bundle provides basic templating tools for building Websites with eZ Publish 5 similar to eZ Publish 4.

This Bundle is **Work in progress**.

**full Documentation can be found at: [www.cjw-network.com](http://www.cjw-network.com)**

------------------------------------

**Installation**:

- Download bundle
- copy to directory "/ezpublish/src/Cjw/PublishToolsBundle"
- activate bundle in "/ezpublish/EzPublishKernel.php" insert "new Cjw\PublishToolsBundle\CjwPublishToolsBundle()," in "registerBundles()" Array
- clear cache

ToDo: composer install

------------------------------------

The Bundle contains two services and some example templates.

The two services are a **TwigFunctionService** that provides some Twig Template Tags:

- cjw_breadcrumb
- cjw_treemenu
- cjw_content_fetch
- cjw_lang_get_default_code
- cjw_content_download_file
- cjw_redirect
- more tbd

and the **PublishToolsService**. The service contains some helper functions and a "content fetch" function trying to emulate some features of the god old content fetch functions in eZ Publish 4.

You can easily fetch content in twig templates and build Websites without hacking controller with PHP.

------------------------------------

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

------------------------------------

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

------------------------------------

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
