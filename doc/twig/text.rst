The Text Extension
==================

Aktiviert in:

./src/Cjw/PublishToolsBundle/Resources/config/services.yml

    services:
        twig.extension.text:
            class: Twig_Extensions_Extension_Text
            tags:
                - { name: twig.extension }

The Text extension provides the following filters:

* truncate() : "Hallo Welt"|truncate( 5 ) => Hallo...
* wordwrap() : "Hallo Welt"|wordwrap( 2 ) => Ha\nllo Welt
* nl2br()    : "Ha\nllo Welt"|nl2br()     => Ha<br />llo Welt
