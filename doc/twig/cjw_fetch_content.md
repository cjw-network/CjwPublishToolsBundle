cjw_fetch_content
=================

Standard-Beispiel-Fetch:
------------------------

Folgender Funktionsaufruf holt die Content-Objekte aller direkten Kinder der gegebenen LocationID.
````twig
        {% set listChildren = cjw_fetch_content( [ location.id ], { 'depth': '1',
                                                                    'limit': listLimit,
                                                                    'offset': listOffset,
                                                                    'include': [ 'folder', 'article' ],
                                                                    'datamap': false,
                                                                    'count': true } ) %}
````



Deutsche Funktionsbeschreibung:

| Name               | Type    | Default | Required | Description |
|--------------------|---------|---------|----------|--------------------------------------------------------------|
| depth              | integer | 1       | no       | If set to "0" only locations for the given locationIdArray will be fetched. |
| limit              | integer | 0       | no       | Fetch Limitation; no limitation if set to "0". |
| offset             | integer | 0       | no       | Fetch offset; no offset if set to "0". |
| include            | array   | not set | no       | Array of content type identifiers, which should be included in the resulting array. If not set all content types will be fetched. |
| datamap            | boolean | false   | no       | If set to false the location object will be returned, else the content object. |
| datamap            | boolean | false   | no       | Wenn "false", wird das LocationObjekt zurückgegeben, ansonsten das ContentObjekt. |
| sortby             | array   | not set | no       | Array mit Suchkriterien. |
| language           | array   | not set | no       | Array mit Sprachen, welche geholt werden sollen. Wenn nicht gesetzt wird die aktuelle Sprache genutzt. |
| count              | boolean | false   | no       | Wenn "true", dann wird die Ergebnisanzahl zum Ergebnis hinzugefügt. |
| parent             | boolean | false   | no       | Wenn "true", wird der Elternknoten zum Ergebnis hinzugefügt. |
| main_location_only | boolean | false   | no       | Wenn "true", so werden nur Knoten zum Ergebnis hinzugefügt, bei denen es sich um den Hauptort handelt. Knoten, welche nur einen zusätzlichen Ort darstellen werden nicht in das Ergebnis mit aufgenommen.

English function description:

| Name               | Type    | Default | Required | Description |
|--------------------|---------|---------|----------|--------------------------------------------------------------|
| depth              | integer | 1       | no       | If set to "0" only locations for the given locationIdArray will be fetched. |
| limit              | integer | 0       | no       | Fetch Limitation; no limitation if set to "0". |
| offset             | integer | 0       | no       | Fetch offset; no offset if set to "0". |
| include            | array   | not set | no       | Array of content type identifiers, which should be included in the resulting array. If not set all content types will be fetched. |
| datamap            | boolean | false   | no       | If set to false the location object will be returned, else the content object. |
| sortby             | array   | not set | no       | Array of sort criterions. |
| language           | array   | not set | no       | Array of languages, which should be fetched. If not set the current language will be used. |
| count              | boolean | false   | no       | If set to true, the result count will be included into the result. |
| parent             | boolean | false   | no       | If set to true, the parent node will be included into the result. |
| main_location_only | boolean | false   | no       | If set to true, only nodes are included, which are the main_location. Nodes, which are only an additional location, are not included into the result.

