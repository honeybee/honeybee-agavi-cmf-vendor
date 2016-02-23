# Entity glance

The **glance** is a brief preview of an entity content.
Disabled by default, it can be enabled in the *view_config.xml* on the subject *#glance*.

The glance supports configuration at different levels of the application:

* **globally**, in the generic *'application'* view-scope:
```
<view_config scope="application">
    <output_formats>
        <output_format name="html">
            <renderer_configs>
                <renderer_config subject="#glance">
                    <settings>
                        <setting name="enabled">true</setting>
                    </settings>
                </renderer_config>
            </renderer_configs>
        </output_format>
    </output_formats>
</view_config>
```
* **per-view**, in the specific view-scope:
```
<view_config scope="honeybee.system_account.user.resource.modify">
    <output_formats>
        <output_format name="html">
            <renderer_configs>
                <renderer_config subject="#glance">
                    <settings>
                        <setting name="enabled">true</setting>
                    </settings>
                </renderer_config>
            </renderer_configs>
        </output_format>
    </output_formats>
</view_config>
```
* **per-list-attribute**, as additional suffixed *view_config* for an embed-list:
```
<renderer_config subject="honeybee.system_account.user.contacts.#glance">
    <settings>
        <setting name="enabled">true</setting>
    </settings>
</renderer_config>
```
* **per-entity-type**, as additional suffixed *view_config* for an embed-list entity type:
```
<renderer_config subject="honeybee.system_account.user.contacts.colleague_contact.#glance">
    <settings>
        <setting name="enabled">true</setting>
    </settings>
</renderer_config>
```

A default template is present for the *glance*; it provides an eventual image (or a placeholder), the eventual Title and the eventual Description of the entity.
The image will be the first image of the first *image-list* attribute; otherwise will fallback to the URL specified in the application setting *"entity_glance.image_placeholder"*.
By default the Title will be the value of the first *text* attribute, and the Description will be the value of the first *textarea* attribute.

The preferred way to customize the glance is to load a different template or renderer, and implement an own solution without touching the default template/renderer.

## Rendering options

### Glance renderer
* **enabled**
* **image_url**
* **image_width**/**image_height**
* **image_value_path** - Attribute value path to an image-list attribute used for the glance image.
* **image_value_path_index** - Index of the image-list specified in the previous option (by default gets the first image of the attribute value)
* **image_activity_scope** - Converjon activity scope.
* **image_activity_name** - Converjon activity scope.
* **title**
* **title_value_path** - Attribute value path to the attribute used for the glance title.
* **description**
* **description_value_path** - Attribute value path to the attribute used for the glance description.
* **css**

Common options from the base renderer (*e.g.* **template**, **renderer_locator_modifier**) can be obviously used to customize the rendering of the glance (*e.g.* to use a custom renderer with different logic for retrieving the default attribute values, or to use a custom template

### Entity renderer
* **glance_enabled** - Tells wether to render or not the glance for the entity.
* **glance_config** - A default renderer config to be merged with the configurations listed above.
The option **expand_content_by_default** is set to display/expand the entity details when there is no glance to click on.

### Entity-list renderer
* **glance_enabled** - Tells wether to render or not the glance for the list entities.
* **glance_config** - A default renderer config for the list entities.

