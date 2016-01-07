# Templates

- [Templates](#templates)
  - [Output type renderer](#output-type-renderer)
  - [View template lookup paths](#view-template-lookup-paths)
  - [Master templates and twig macros](#master-templates-and-twig-macros)
  - [Render a standalone twig template](#render-a-standalone-twig-template)
  - [Render a template without Agavi](#render-a-template-without-agavi)
  - [Available namespaces in twig templates](#available-namespaces-in-twig-templates)
  - [Support for other template libraries](#support-for-other-template-libraries)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

## Output type renderer

There is a `Honeybee\Agavi\Renderer\ProxyRenderer` that defines a chain of renderers that are used to render templates. By default the ```output_types.xml``` file defines two renderers to be tried:

1. ```Honeybee\Agavi\Renderer\TwigRenderer``` using `Twig` for templates and
1. ```Honeybee\Agavi\Renderer\PhpRenderer``` using `PHP` for templates

The default filename extensions are

- `.twig` for the `TwigRenderer` and
- `.php` for the `PhpRenderer`.

The `ProxyRenderer` tries the `TwigRenderer` first. If that renderer does not succeed, the `PhpRenderer` will be tried. If both renderers do not succeed (e.g. because of missing templates) an exception is thrown. This means, that you can use PHP and Twig templates side by side and interchangingly. If there are both a PHP and a Twig template for a single view the twig template is used.

## View template lookup paths

The basic template lookup path structure for honeybee applications is:

1. ```app/templates/modules/<module_name>/<view_name>```
1. ```app/modules/<module_name>/templates/<view_name>```
1. ```app/modules/<module_name>/impl/<view_name>```

The lookup path is expanded using the current action's name, the current view's name and the current renderer's default extension. In addition to that the current locale and extension is taken into account when searching for templates:

1. ```app/templates/modules/<module_name>/<locale_identifier>/<action_name/<action_name><view_name><extension>```
1. ```app/templates/modules/<module_name>/<locale_short_identifier>/<action_name/<action_name><view_name><extension>```
1. ```app/templates/modules/<module_name>/<action_name/<action_name><view_name>.<locale_identifier><extension>```
1. ```app/templates/modules/<module_name>/<action_name/<action_name><view_name>.<locale_short_identifier><extension>```
1. ```app/templates/modules/<module_name>/<action_name/<action_name><view_name><extension>```
1. ```app/modules/<module_name>/templates/<locale_identifier>/<action_name/<action_name><view_name><extension>```
1. ```app/modules/<module_name>/templates/<locale_short_identifier>/<action_name/<action_name><view_name><extension>```
1. ```app/modules/<module_name>/templates/<action_name/<action_name><view_name>.<locale_identifier><extension>```
1. ```app/modules/<module_name>/templates/<action_name/<action_name><view_name>.<locale_short_identifier><extension>```
1. ```app/modules/<module_name>/templates/<action_name/<action_name><view_name><extension>```
1. ```app/modules/<module_name>/impl/<action_name/<action_name><view_name>.<locale_identifier><extension>```
1. ```app/modules/<module_name>/impl/<action_name/<action_name><view_name>.<locale_short_identifier><extension>```
1. ```app/modules/<module_name>/impl/<action_name/<action_name><view_name><extension>```

This means, for the Agavi module `User` with the action `Login` and the view `Input` assuming a current locale of ```de_DE``` and using the `TwigRenderer` the following paths are checked for templates before an exception is thrown:

```
app/templates/modules/User/de_DE/Login/LoginInput.twig
app/templates/modules/User/de/Login/LoginInput.twig
app/templates/modules/User/Login/LoginInput.de_DE.twig
app/templates/modules/User/Login/LoginInput.de.twig
app/templates/modules/User/Login/LoginInput.twig

app/modules/User/templates/de_DE/Login/LoginInput.twig
app/modules/User/templates/de/Login/LoginInput.twig
app/modules/User/templates/Login/LoginInput.de_DE.twig
app/modules/User/templates/Login/LoginInput.de.twig
app/modules/User/templates/Login/LoginInput.twig

app/modules/User/impl/Login/LoginInput.de_DE.twig
app/modules/User/impl/Login/LoginInput.de.twig
app/modules/User/impl/Login/LoginInput.twig
```

## Master templates and twig macros

The Agavi setting ```core.template_dir``` specifies the path to the master templates of the project. There is a sub directory `macros` for Twig macros. The `TwigRenderer` has the following lookup paths for Twig templates:

- paths from the ```template_dirs``` parameter of the `TwigRenderer` (```core.template_dir``` as a default; usually ```app/templates```)
- path to the directory the current template is in (e.g. ```app/modules/User/impl/Login/```)
- path to the module's template directory (via ```agavi.template.directory``` parameter from the modules's `module.xml` file; e.g. ```app/modules/impl```)

The default configuration in the ```output_types.xml``` file for the ```template_dirs``` parameter of the default `TwigRenderer` leads to the following lookups:

1. ```app/templates```
1. ```app/modules/[ModuleName]/templates```
1. ```vendor/honeybee/honeybee-agavi-cmf-vendor/app/templates```

The above paths are the lookup paths for twig templates and macros. All locations including all the module templates folders are available as twig namespaces: ```@ModuleName/…``` or ```@App/…``` or ```@Honeybee/…```. This allows overriding templates that are included in Honeybee by putting a same named template in the application's templates folder. E.g. if there is a default twig macro in `@Honeybee/templates/macros` you can put a macro with the same name in ```@App/templates/macro``` or in one of the directories with higher precendence and thus override the builtin macro with your own version.

## Render a standalone twig template

You can render a twig template using the `ModuleTemplateRenderer`. This is e.g. used to render a `buildconfig.js` for the RequireJs optimizer `r.js` when you call ```bin/cli honeybee.core.util.compile_js```:

```php
$template_service = new ModuleTemplateRenderer();
$buildconfig_content = $template_service->render('rjs/buildconfig.js');
```

This will lookup the template ```rjs/buildconfig.js.twig``` in the previously mentioned [lookup paths](#master-templates-and-twig-macros).

## Render a template without Agavi

To render templates or strings and return the result or write the result to disk, there's the ```TwigRenderer``` class.

```php
use Honeybee\Infrastructure\Template\Twig\TwigRenderer;

$twig_renderer = TwigRenderer::create(
    [
        'twig_options' => [
            'autoescape' => false,
            'cache' => false,
            'debug' => true,
            'strict_variables' => true
        ],
        'template_paths' => [
            __DIR__ . '/templates'
        ]
    ]
);

// render a template and return the result as a string
$rendered_template = $twig_renderer->renderToFile('some/relative/path.twig', '/tmp/somefile.html', […some data…]);

// render a template and put the result into a file on disk
$twig_renderer->renderToFile('some/relative/path.twig', '/tmp/somefile.html', […some data…]);

$twig_string_renderer = TwigRenderer::create(
    [
        'twig_options' => [
            'autoescape' => false,
            'cache' => false,
            'debug' => true,
            'strict_variables' => true
        ]
    ]
);

// render a string and return the resulting string
$new_string = $twig_string_renderer->renderToString('some{%foo%}string', ['foo' => 'cool']); // somecoolstring
```

## Available namespaces in twig templates

The `TwigRenderer` adds namespaces for all important areas of the project to be able to include templates from exactly a location instead of using the default lookup chain.

By default templates are loaded from the project templates directory, then your current module's templates folder and the the default Honeybee templates folder.

You can skip this lookup chain by using Twig namespaces. The following namespaces are defined via the renderer:

- ```@App```: `app/templates`
- ```@Honeybee```: `vendor/honeybee/honeybee-agavi-cmf-vendor/app/templates`
- ```@ModuleName```: `app/modules/[ModuleName]/templates`

Say you want to reference the `app/templates/Master.twig` template file somewhere. You could for example ```{% include "Master.twig" %}``` or ```{% include "@Honeybee/Master.twig" %}``` (or use `extends` or `embed` etc). When you want to include e.g. the Error404 success template somewhere you would have to write the following: ```{% include "@Honeybee_Core/impl/System/Error404/Error404Success.twig" %}```. To get a template that is in a Foo module's templates folder you would embed it like this: ```{% embed "@Foo/templates/some_template.twig"%}```.

## Support for other template libraries

TBD: This does not work as described, as the ```output_types.xml``` file does not have working XIncludes. Thus the config handler has to be changed or the `Pulq` approach with a sandbox file has to be adopted.

If you want to use other template libraries or languages you can do so by overriding the default renderers specified in the ```output_types.xml``` file. You can either use one of the Agavi provided ones (like the ```AgaviXsltRenderer``` or ```AgaviSmartyRenderer```) or create your own class. Your own class should implement the ```AgaviIReusableRenderer``` interface and may extend the base ```AgaviRenderer```. Make sure your class is autoloadable and then specify it as a (default) renderer in the ```output_types.xml``` file:

```xml
<renderers default="custom">
    <renderer name="custom" class="CustomRenderer">
        <ae:parameters>
            <ae:parameter name="some_setting">%project.dir%</ae:parameter>
        </ae:parameters>
    </renderer>
</renderers>
```

## TBD / Ideas / Misc

- make introduction and overriding of output types work flawlessly
