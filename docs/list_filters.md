# List filters

By default the views with a list/collection of resources support the configuration of *list filters*, to refine the search over the shown results.
Just use the filter control close to the search input to add filters (if they are not already present). Use the *quick control* 'x' button to remove them.

There are two sources for filters to be added in the list:
*  the *filter[]* URL parameter
```...&filter[foobar]=filter_value```
* the *view-config*
```
<view_config scope="application">
    <settings>
        <setting name="list_filters">
            <settings>
                <setting name="foobar1" />
                <setting name="foobar2">
                    <setting name="implementor">Custom\Implementor\Class</setting>
                    <setting name="attribute_path">foobar_attribute</setting>
                </setting>
            </settings>
        </setting>
    </settings>
</view_config>
```
As convenience the filter named the same as an attribute will have that attribute already be injected, without having to specify the **attribute_path** setting. This latter one is anyway useful when want a custom name for the filter or when is not possible to recognize the attribute automatically (e.g when the filter should work on an attribute that has been indexed with another name or with a suffix like *attribute.filter*).

By default *list filters* are enabled. Is possible to disable them:
* globally **per-project**
```
<settings prefix="list_filters.">
    <setting name="enable">false</setting>
</settings>
```
* **per-view**
```
<view_config scope="application">
    <settings>
        <setting name="enable_list_filters">false</setting>
    </setting>
</settings>
```
Per-view setting has priority over the global setting

Each *list filter* can have it's own custom renderer config:
```
<output_formats>
    <output_format name="html">
        <renderer_configs>
            <renderer_config subject="foobar2_list_filter">
                <settings>
                    <setting name="implementor">Custom\Filter\Renderer</setting>
                </settings>
            </renderer_config>
        </renderer_configs>
    </output_format>
</output_formats>
```

## Templates

Custom templates can be specified as usual in the renderer or by providing the **template** renderer setting. Anyway by default a lookup is performed, when the *list filter* is able to recognize its target attribute. The attribute *name* and *type* will be used to lookup eventual custom templates (e.g. *app/templates/html/list_filter/choice_attribute.twig*; the lookup can be influenced changing the template *app/templates/html/list_filter/choice_attribute.twig*).

To implement new filters it is suggested to rely on the *default_list_filter* template structure and just override the blocks that need to be changed. Take a look to the present filters to have an idea.
Anyway take care of maintaining the main structure and the main elements with the basic classes (e.g. *.hb-list-filter__quick-control* and *.hb-list-filter__filter*).

Basically it is a matter of providing a *defautl input control* with *name* attribute set properly to the filter name (e.g. *filter[foobar]*). And to specify the *filter_id* in the *id* attribute.

## Translations

Translations are usually put under the translation domain *list_filters* but you can have your custom domain as for all the other renderers.
By convention each filter has its own translation prefixed with the filter identifier (e.g. *filter2.quick_label*; take a look to present filters to have an insight).

## Widget

Custom widget can be implemented inheriting from the base *ListFilter* object.

The base widget listens for changes to the *default control* and reflects them in the *quick control label*; this latter is always shown, as long as the filter is active, and provide a summary of the configured filter without having to expand the filter (clicking on the *quick control label* will show the filter details).

## CSS

Basic functionality of for the *list filters* is maintained also when Javascript is not available.
Anyway take care of mainaining the *checkbox hack* behaviour to show/hide the *list filter* detail (see ```.hb-list-filter__trigger + .hb-list-filter__content``` in the SCSS component file) and to give the *hb-list-filter* class name to the main wrapper added in the template.
