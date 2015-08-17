# Themes

- [Themes](#themes)
  - [Theme creation](#theme-creation)
  - [Theme usage](#theme-usage)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

Honeybee themes are SCSS stylesheets compiled via [SASS](http://sass-lang.com/).
There are two default locations where new themes may be put or existing themes
can be customized:

- `pub/static/themes`
- `app/project/themes`

The folders should contain only directories with the existing themes. Each theme
consists of some default files like SCSS partials, a `theme.scss` and a
`manifest.xml` file for meta information about the theme and all necessary binaries.

## Theme creation

Themes for Honeybee are simple directories that contain SCSS files with the
referenced binaries (like fonts and images). The SCSS files are put together
and compiled by SASS using the SCSS syntax.

The following partials are entry points that are used in the `theme.scss`:

- `_vars.scss`
- `_framework.scss`
- `_styles.scss`

The `theme.scss` file includes those partials and will be compiled into a
`themes/[themename]/theme.css` file that is included in the browser via the
`Honeybee\Agavi\Filter\ModuleResourcesResponseFilter`.

The `_vars.scss` file should contain global variables to be used in your SCSS
files. The `_framework.scss` file should be used to load a (S)CSS framework of
your choice. The `_styles.scss` file contains all the styles for the theme. It's
advised to import different partials and mixins etc. in the `_styles.scss` file
to make use of all the nice SCSS features.

An example ```honeybee-minimal``` theme using
[```inuit.css```](https://github.com/csswizardry/inuit.css) as the SCSS
framework of choice would look like this:

```
themes/
`-- honeybee-minimal
    |-- binaries
    |   |-- header-logo.png
    |   |-- ...
    |   `-- fonts
    |       |-- honeybee.eot
    |       |-- honeybee.ttf
    |       `-- honeybee.woff
    |-- inuit.css
    |   |-- base
    |   |   `-- ...
    |   |-- generic
    |   |   `-- ...
    |   |-- objects
    |   |   `-- ...
    |   |-- _inuit.scss
    |   `-- _vars.scss
    |-- ui
    |   |-- _custom_buttons.scss
    |   |-- _forms.scss
    |   `-- _widget.scss
    |-- manifest.xml
    |-- theme.scss
    |-- _framework.scss
    |-- _styles.scss
    `-- _vars.scss
```

The themes will be symlinked into the `pub/static/themes` directory (and thus are
all available to the system and it's users) when you run:

```sh
bin/cli core.util.compile_scss
```

The ```_vars.scss``` would import the ```inuit.scss/_vars.scss``` and override
necessary variables and add it's own variables. The ```_framework.scss``` file
imports the ```inuit.css/_inuit.scss```. The ```_styles.scss``` file contains
all the theme's styles and loads mixins and partials from the `ui` folder:

```css
@import "ui/custom_buttons", "ui/forms", "ui/widgets";
```

IMPORTANT! DO *NOT* PUT ANY EXECUTABLE OR SENSITIVE FILES INTO THEMES FOLDERS as
the theme folders are linked into the `pub/static/themes/` directory and are
thus available publicly. Themes should only contain SCSS, CSS and images or font
files.

When you are developing a theme and always compiling themes via the above CLI
command is too cumbersome, you can watch changes of SCSS files automatically:

```sh
bin/cli core.util.watch_scss
```

The default theme from the `themes.default` setting will be watched by default.
You can specify another theme to watch and can prevent watching other SCSS files
from the modules and project area if those are not necessary for your project.
Have a look that the default arguments of the commandline call.

## Theme usage

The default theme of the system can be set in the `app/config/settings.xml`
file by stating the folder name of the theme:

```xml
<settings prefix="themes.">
    <!-- <setting name="sass.cmd">/opt/ruby/bin/sass</setting> -->
    <setting name="default">honeybee-awesome</setting>
</settings>
```

The `sass.cmd` is optional and defaults to `/opt/ruby/bin/sass` as the path to
the sass executable to use for SCSS file compilation.

## TBD / Ideas / Misc

- user defined variables of themes are possible when information about those
  variables, their name and input type (like color or text) are available in the
  `manifest.xml` file as those could be used to render a GUI for users and put
  their custom settings somewhere accessible for the theme compilation step to
  have user customizable themes. At the moment only the `theme.scss` is compiled.
- audit of security features (symlinking in pub etc.)
    - put .htaccess and index.php files or adjust puppet recipes for themes
- audit of concurrency and scalability aspects of user customizable themes
