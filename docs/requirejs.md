# RequireJs

- [RequireJs](#requirejs)
  - [General usage](#general-usage)
  - [Module specific entry points](#module-specific-entry-points)
  - [Optimization for production systems](#optimization-for-production-systems)
  - [Customization for single pages](#customization-for-single-pages)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

## General usage

Honeybee has a working integration of [`RequireJs`](http://requirejs.org/) and
uses the [`JsBehaviourToolkit`](https://github.com/DracoBlue/js-behaviour) to
separate javascript behaviours from the templates. The javascript AMD modules
that are defined via RequireJs can be optimized for production environments via
the RequireJs optimizer [`r.js`](https://github.com/jrburke/r.js).

To create javascript behaviours and have them loaded in your pages put them
into the `app/[ModuleName]/resources/` folder under a path and name of your
liking (e.g. ```path/to/Widget.js``` and activate and apply the behaviour in
your HTML by adding the necessary ```jsb_``` CSS classes on an HTML element:
```jsb_ jsb_[ModuleName]/path/to/Widget```.

If that widget is not yet loaded the `JsBehaviourToolkit` will use `RequireJs`
to load the ```static/modules/[ModuleName]/path/to/Widget.js``` in the HTML file
and run it. To know more about how to use `jsb` and how to configure it see the
docs of that project.

By default the master template of Honeybee is ```app/templates/Master.twig```.
That template specifies the necessary script elements to load RequireJs,
configure it and require the `Main.js` file of the currently displayed
Honeybee module.

Each Honeybee module MAY have a `resources/styles.scss` file that contains very
module specific CSS styles. That file will be compiled via SASS to a `styles.css`
file and is available in the pub directory automatically via the symlinks that
are created by the CLI commands. The `[ModuleName]/Main.js` and
`[ModuleName]/styles.css` files will be automatically included via the
`ModuleResourcesResponseFilter` in the HTML page on requests to pages of that module.

The `buildconfig.js` file for `r.js` uses the same twig templates as the
requirejs section of the master template uses, This allows to change global
settings like shims and path mappings and aliases in a central template for
web and console optimization. The templates responsible for this can be found
in the ```app/templates/rjs``` folder and may be overridden by putting
same-named templates into the ```app/project/templates/rjs``` or the module's
```app/modules/[ModuleName]/templates/rjs``` folder. To override specific parts
have a look at those files and their inclusion in the master template. If
you want to customize the behaviour of the requirejs integration have a look
at the [Customization for single pages](#customization-for-single-pages)
section.

## Module specific entry points

The module specific entry points are ```Main.js``` files in each honeybee
module's `resources` folder. These files will be included by default via the
master template (```app/templates/Master.twig```) and the `ModuleResourcesResponseFilter`.

The main javascript files should declare all common AMD modules necessary for
views of the current honeybee module. That's the reason why by default each
new Honeybee module has a `resources/Main.js` file that defines the `Core/Main`
javascripts as a dependency as that file already includes the usually needed
files like JQuery and the JsBehaviourToolkt.

To include e.g. main files from other modules in your current request or view
you can call ```ModuleResourcesResponseFilter::addModule('ModuleName')``` in your view.

## Optimization for production systems

Run in the commandline the following command to compile all javascript
resources:

```sh
bin/cli core.util.compile_js -verbose
```

By default all (symlinked) resources from ```pub/static/modules/``` will be
compiled into ```pub/static/modules-built/```. The usage of the built artefacts
can be triggered via the ```requirejs.use_optimized``` setting in your settings.

The shell command uses an Agavi action to render a ```pub/static/buildconfig.js```
file that is then used to run `r.js`. The requirejs optimizer then compiles all
single javascript and css files according to the options in the build config. It
creates optimized ```pub/static/modules-built/[ModuleName]/Main.js``` files for
all existing ```app/modules/[ModuleName]/resources/Main.js``` files.

## Customization for single pages

You can create custom modules that contain exactly those files you need for your
custom page to work correctly without the ```Main.js``` overhead you perhaps do
not need in every case.

To do this all you have to do is override the RequireJs inclusion in the master
template by either loading a completely custom layout in your view or by
configuring the `default` layout to use a different custom master template:

```php
// CustomSuccessView.class.php
$this->getContext()->getLayer('decorator')->setTemplate(AgaviConfig::get('project.templates_dir').'/CustomMaster');
```

The specified custom master template can be put into either your module's
template directory or the project wide template directory:

- ```app/project/templates```
- ```app/modules/[ModuleName]/templates```

Put the `CustomMaster.twig` template into the project `templates` folder and let
it extend the default `app/templates/Master.twig` by using the ```_honeybee```
namespace and put the requirejs inclusion and override you need in it:

```twig
{% extends "@_honeybee/Master.twig" %}
{% block requirejs_block %}
    {% block requirejs_requires %}
        <script>require(["jsb"]);</script>
    {% endblock %}
{% endblock %}
```

The above leads to only loading `require.js` and `JsBehaviourToolkit.js` for
your `CustomSuccessView`. You can now trigger your custom behaviours via CSS
classes within the HTML of your view like this:

```html
...
<div class="jsb_ jsb_ModuleName/CustomWidget">...</div>
...
```

The mentioned `CustomWidget` resides in the `ModuleName` module's `resources`
folder: ```CustomWidget.js``` and may look like this:

```js
define([
    "Core/SomeDependency",
    "Some/More"
], function() {

    "use strict";

    var CustomWidget = function(dom_element, options) {
        if (window.console && console.log) {
            console.log("Applying CustomWidget on", dom_element, "with options", options);
        }
        $(dom_element).hereBeDragons(options);
    };

    return CustomWidget;
});
```

As you can see the `CustomWidget` has two dependencies and needs the
`JsBehaviourToolkit` library which you already included via your custom
master template. You disabled the default RequireJs ```require([...])```
calls and only loaded the `JsBehaviourToolkit`. The library then finds
your `div` element and runs the widget. RequireJs notices the dependencies
necessary for your widget to run and thus only executes your widget after
all necessary javascript files have been loaded.

For production system you want to load all these dependencies via one file. To
achieve this, you have to override the default build configuration of `r.js`
that is used when you call ```bin/cli core.util.compile_js``` to optimize
everything for production. The `buildconfig.js` in ```pub/static``` is created
via a twig template that is called ```rjs/buildconfig.js.twig```. You can
override that file by placing a similar file into your ```project/templates```
folder or you can use the ```rjs/additional_modules.twig``` template to add
your custom widget module. Let's create ```project/templates/rjs/additional_modules.twig```
and put the following in it:

```js
{
    name: "ModuleName/CustomWidget"
}
```

The template will be used within the optimization run to pickup your
additional modules and add them to the build config's `modules` definition.
`r.js` then traces the dependencies of your custom widget and create an
optimized version in the ```pub/static/modules-built/ModuleName/CustomWidget.js```
file. If you want to include additional javascript files or exclude common files
you can add `include` and `exclude` options to the above snippet.

Run ```bin/cli core.util.optimize_js -verbose``` to optimize all javascript
resources and notice the echoed log message for your widget and especially the
included files.

To test your `CustomWidget` in that production optimized mode just enable it on
your development working copy by setting ```requirejs.use_optimized``` to `true`
in your `settings.xml` for the `development` environment.

When you refresh your custom view you should see, that the browser first fetches
`static/require.js`, then loads `static/Core/lib/JsBehaviourToolkit.js` and
eventually loads your custom defined module with its dependencies. That is, it
should be a maximum of three requests in production mode and at least five
requests in development mode (depending on the number of dependencies your
widget defines).

## TBD / Ideas / Misc

- make introduction and overriding of output types work flawlessly
