Übersicht – CJW PublishToolsBundle
==================================

Services
--------

Liste aller vom "CjwPublishToolsBundle" zur Verfügung gestellten Symfony-Services.

- FormBuilderService
    + TODO:
        * *getFormSchemaFromContentObjectFields*: Verarbeitung fehlender Feldtypen implementieren
        * *getFormSchemaFromContentObjectFields*: Umwandlung zu HTML für **ezxmltext**-Feldtyp
        * *getFormSchemaFromContentObjectFields*: Implementierung fehlender Eigenschaften des **ezselection**-Feldtyps
        * *getCurrentSiteBundle*: Bessere Lösung finden; Kompatibilität mit plain ezp5 checken; Test wenn Bundle im **vendor**-Verzeichnis
- FormHandlerService
    + TODO:
        * *sendEmailHandler*: Template überprüfen, falls nicht vorhanden inline rendern (render template inline if $template false)
        * *sendEmailHandler*: Hinzufügen eines Plain-Text-Teiles zur EMail
        * *sendEmailHandler*: Sendefehler auffangen (catch)
        * *sendEmailHandler*: Error-Log schreiben
        * *successHandler*: Template überprüfen, falls nicht vorhanden inline rendern
        * *contentAddHandler* und *contentEditHandler* müssen implementiert werden
- MobileDetectService
    + Erkennung der Geräteklasse (Mobil, Tablet, Desktop)
- PublishToolsService
    + TODO:
        * *fetchSubtree*: Erweitern, sodass auch die Sichtbarkeit eines Content-Objektes ignoriert werden kann
        * *fetchSubtree*: ContentTypeID zu ContentTypeIdentifier matchen und für Suche nutzen; spart einen Subrequest und ist schneller
        * *fetchSubtree*: Verarbeitung von Rollen und Rechte, Sichtbarkeit, Datum, Objektzustände (Objektstates) implementieren
- TwigConfigFunctionsService
    + Twig-Extension zum dynamischen Zugriff auf Einstellungsdateien in Twig-Templates
- TwigContentFunctionsService
    + Twig-Extension, welche Helper für public API-Objekte bereitstellt
    + TODO:
        * *renderLocation*: Fehler werfen, wenn die **locationId** nicht gesetzt wurde oder die **Location** kein **Location-Objekt** ist
        * *renderLocation*: Template-Debug anzeigen, wenn aktiviert
        * *getOverrideTemplate*: CustomMatcher implementieren
        * *getOverrideTemplate*: Eventuell **Identifier\ContentType**-Klasse überschreiben oder eigene **CjwPublishTools\Identifier\ContentType**-Klasse implementieren
- TwigFunctionsService
    + Twig-Extension, welche alle wichtigen Funktionen der PublishTools in Twig-Templates zur
      Verfügung stellt

Twig-Funktionen
---------------

Liste aller vom "TwigFunctionService" zur Verfügung gestellten Twig-Funktionen. In Klammern befindet
sich zusätzlich der PHP-Funktionsname.

### TwigFunctionsService

-  cjw_cache_set_ttl                (PHP: setCacheTtl)
-  cjw_breadcrumb                   (PHP: getBreadcrumb)
-  cjw_treemenu                     (PHP: getTreemenu)
-  cjw_load_content_by_id           (PHP: loadContentById)
-  cjw_fetch_content                (PHP: fetchContent)
-  cjw_user_get_current             (PHP: getCurrentUser)
-  cjw_lang_get_default_code        (PHP: getDefaultLangCode)
-  cjw_content_download_file        (PHP: streamFile)
-  cjw_redirect                     (PHP: redirect)
-  cjw_get_content_type_identifier  (PHP: getContentTypeIdentifier)
-  cjw_template_get_var             (PHP: getTplVar)
-  cjw_template_set_var             (PHP: setTplVar)

### TwigContentFunctionsService

- cjw_render_location               (PHP: renderLocation)

### TwigConfigFunctionsService

- cjw_siteaccess_parameters         (PHP: siteAccessParameters)
- cjw_config_resolver_get_parameter (PHP: configResolverGetParameter)
- cjw_config_get_parameter          (PHP: configGetParameter)