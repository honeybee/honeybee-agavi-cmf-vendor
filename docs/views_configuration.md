# Views configuration

- [Views configuration](#views-configuration)
  - [View-config](#view-config)
  - [View-template](#view-template)
  - [Fields configuration](#fields-configuration)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

## Views configuration
*TBD*

## View-config
*TBD*

## View-template
*TBD*

## Fields configuration

Fields are basically rendered from the *entity-renderer*, according to the *renderer configuration* (defined in the *view-config*), the *render settings* (defined in the *view-template* and specific for the rendering of the single field), or to the settings propagated by the entity itself (defined as *__field_options* in the *entity-renderer* settings).

### View-config
The renderer configuration in the *view-config* can be defined per attribute-type (e.g *subject="text_attribute"*, according to [Honeybee/Trellis](https://github.com/honeybee/trellis) attribute types), or per field-name (e.g. *subject="field_zipcode"*, where "zipcode" is the name defined in the view-template field configuration).

### View-template
The field settings in the *view-template* are the most specific ones and are related to the rendering on the single field (while the *view-config* configurations can be reused in the rendering of multiple subects).

### Entity propagated settings
There could be cases where it is needed to overwrite settings already specified in the *view-template*; for example when using the clause **extends="view_template_name"** to reuse a configuration from another view (convenience for having just a central point where to define the list of fields that have to be shown in multiple views, e.g. the *Create* and the *Resource.Modify* views).

This can be achieved specifying an array of settings to be passed to the entity rendering, according to the following structure:

    <setting name="__fields_options">
        <settings>
            <setting name="zipcode">
                <!--
                    This will overwrite the 'readonly' setting eventually
                    specificed in the extended view-template definition.
                -->
                <setting name="readonly">true</setting>
            </setting>
        </settings>
    </setting>

Of course the same setting can be passed directly in-code (e.g inside Views, where the routing generator can be used, while it is not directly available inside renderers) when calling the *render()* method like this:

    [
        '__fields_options' => [
            'zipcode' => [
                'readonly' => true,
                'zipcode_list_source_url' => 'https://....'
            ]
        ]
    ]

The *__fields_options* setting relies on the key *__all_fields* to propagate settings to **all** the fields of the rendered entity.

    <setting name="__fields_options">
        <settings>
            <!--
                Render all the fields switching
                to the template for input views.
            -->
            <setting name="__all_fields">
                <setting name="input_view_template_name_suffixes">
                    <settings>
                        <setting>.resource</setting>
                    </settings>
                </setting>
            </setting>
        </settings>
    </setting>

The configuration mergin order is:
* attribute-type config (view-config)
* field-name config (view-config)
* field settings (view-template)
* all_fields settings propagated by the entity (view-config / in-code)
* field settings propagated by the entity (view-config / in-code)

## TBD / Ideas / Misc
*TBD*