# Entity glance

The **glance** is a brief preview of an entity content.
Disabled by default, it can be enabled in the *view_config.xml* or in the *view_templates.xml*, specifying a *"glance_config"* setting.

The glance supports configuration at different levels of the application:

* **globally**, in the *view_configs.xml*, on the generic *'application'* view-scope:
```
<view_config scope="application">
    <settings>
        <setting name="glance_config">
            <settings>
                <setting name="enabled">true</setting>
            </settings>
        </setting>
    </settings>
</view_config>
```
* **per-view**, as setting for the specific view-scope in the *view_configs.xml*:
```
<view_config scope="honeybee.system_account.user.resource.modify">
    <settings>
        <setting name="glance_config">
            <settings>
                <setting name="enabled">true</setting>
            </settings>
        </setting>
    </settings>
</view_config>
```
* **per-entity-list-attribute**, as field setting in the *view_templates.xml*:
```
<view_templates scope="honeybee.system_account.user.resource.modify">
    <view_template name="honeybee.system_account.user.html">
        ...
        <field name="contacts" attribute_path="contacts" template="html/attribute/embedded-entity-list/as_input.twig">
            <settings>
                <setting name="glance_config">
                    <setting name="enabled">true</setting>
                </setting>
                <setting name="expand_items_content_by_default">true</setting>
            </settings>
        </field>
        ...
    </view_template>
</view_templates>
```
or as *render_config*, in the *view_configs.xml*, for an embed-list:
```
<renderer_config subject="honeybee.system_account.user.contacts">
    <settings>
        <setting name="glance_config">
            <settings>
                <setting name="enabled">true</setting>
            </settings>
        </setting>
    </settings>
</renderer_config>
```
**Note:** *"honeybee.system_account.user.contacts"* is not a real rendering subject. The option to specify a *glance_config* in the *view_configs.xml* is just a convenience.

* **per-entity-type**, as *renderer_config*, in the *view_configs.xml*, for an embedded entity type:
```
<renderer_config subject="honeybee.system_account.user.contacts.colleague_contact">
    <settings>
        <setting name="glance_config">
            <settings>
                <setting name="enabled">true</setting>
            </settings>
        </setting>
    </settings>
</renderer_config>
```

A default template is present for the *glance*; it provides an eventual image (or a placeholder), the eventual Title and the eventual Description of the entity.
The image will be the first image of the first *image-list* attribute; otherwise will fallback to the URL specified in the application setting *"entity_glance.image_placeholder"*.
By default the Title will be the value of the first *text* attribute, and the Description will be the value of the first *textarea* attribute.

The preferred way to customize the glance is to load a different template or renderer, and implement an own solution without touching the default template/renderer.

---

## Mixed configuration
It is possible to mix configurations at different levels. Remember that the more specific configurations replace the less specific configurations. The specificity (from less to more specific): **global -> per-view -> per-entity-list -> per-entity-type**.

**Note:** to use a specific attribute-value-path (see below) when a static value has been set in a less specific configuration (*e.g. global*), it will also be necessary to set to empty (in the more specific configuration, *e.g. per-entity-list*) the static-value option (*e.g.* "*image_url*", "*title*" or "*description*").
This will reset the less specific configuration.

***Example***
```
<view_config scope="application">
    ...
        <renderer_config subject="#glance">
            <setting name="image_url">http://url.to/image</setting>
        </renderer_config>
    ...
        <renderer_config subject="honeybee.system_account.user.contacts.#glance">
            <setting name="image_url"></setting>
            <setting name="image_value_path">images</setting>
        </renderer_config>
```

---

## Rendering options

### Glance renderer
* **enabled** - Enable glance rendering.
* **image_url** - Static URL. [there is no auto-retriving when it is set]
* **image_width**/**image_height** - Custom width/height for the image.
* **image_value_path** - Attribute value path to an image-list attribute used for the glance image auto-retrieving. [requires *image_url* option to be empty]
* **image_value_path_index** - Index of the image-list specified in the previous option (by default gets the first image of the attribute value).
* **image_activity_scope** - Converjon activity scope.
* **image_activity_name** - Converjon activity scope.
* **title** - Static value. [there is no auto-retriving when it is set]
* **title_value_path** - Attribute value path to the attribute used for the glance title auto-retrieving. [requires *title* option to be empty]
* **description** - Static value. [there is no auto-retriving when it is set]
* **description_value_path** - Attribute value path to the attribute used for the glance description auto-retrieving. [requires *description* option to be empty]
* **css** - Custom classes for the glance template.

Common options from the base renderer (*e.g.* **template**, **renderer_locator_modifier**) can be obviously used to customize the rendering of the glance (*e.g.* to use a custom renderer with different logic for retrieving the default attribute values, or to use a custom template.

### Entity renderer
* **glance_config** - The glance configuration used for the glance rendering.
The option **expand_content_by_default** is set to display/expand the entity details when there is no glance to click on.

### Embedded-Entity-List attribute renderer
* **glance_config** - The *per-entity-list-attribute* glance configuration.
