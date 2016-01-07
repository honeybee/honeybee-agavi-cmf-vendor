# Themes

- [Themes](#themes)
  - [Theme creation](#theme-creation)
  - [Theme usage](#theme-usage)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

Honeybee themes are SCSS stylesheets compiled via [SASS](http://sass-lang.com/). The default location for themes is `pub/static/themes`. Each folder in that location is a different theme. Each theme consists of an entry point file that is called `main.scss` and all additional assets (scss partials, images, fonts).

## Theme creation

Themes for Honeybee are directories that contain SCSS files with the necessary binaries (like fonts and images). The SCSS files are put together and compiled by SASS using the SCSS syntax.

The `whitelabel` theme has the following partials next to the entry point file:

- `_vars.scss` importing theme variables like colors, fonts, settings, breakpoints etc.
- `_vendor.scss` importing vendor libraries or frameworks like [inuitcss](https://github.com/inuitcss)
- `_lib.scss` importing honeybee specific components, objects etc.
- `_theme.scss` including custom styles for this theme

The `main.scss` file includes those partials and will be compiled into a `pub/static/themes/[themename]/main.css` file that is included in the browser via the `Honeybee\Agavi\Filter\ModuleAssetsResponseFilter`.

The ```whitelabel``` theme uses [```inuit.css```](https://github.com/inuitcss) as the SCSS framework of choice and has the following folder structure:

```
themes/
`-- whitelabel
    |-- binaries
    |   |-- fonts
    |   `-- icons
    |-- components
    |   |-- activity
    |   |-- ...
    |   |-- datepicker
    |   |-- dropdown
    |   |-- itemlist
    |   |-- navigation
    |   |-- panels
    |   |-- tabs
    |   |-- ...
    |   `-- user-widget
    |-- lib
    |   |-- fonts
    |   |-- helpers
    |   `-- objects
    |-- vendor
    |   `-- inuit-*
    |-- views
    |   |-- Honeybee_Core
    |       `-- _views.scss
    |   |-- Honeybee_SystemAccount
    |       `-- _views.scss
    |   `-- default
    |       `-- _views.scss
    |-- _vars.scss
    |-- _colors.scss
    |-- _lib.scss
    |-- _theme.scss
    |-- _typography.scss
    |-- _vendor.scss
    `-- main.scss
```

In projects the themes will be symlinked into the `pub/static/themes` directory of the project and thus are available for compilation:

```sh
bin/cli honeybee.core.util.compile_scss
```

IMPORTANT! DO *NOT* PUT ANY EXECUTABLE OR SENSITIVE FILES INTO THEMES FOLDERS as the theme folders are linked into the `pub/static/themes/` directory and are thus available publicly. Themes should only contain SCSS, CSS and images or font files.

When you are developing a theme and always compiling themes via the above CLI command is too cumbersome, you can watch changes of SCSS files automatically:

```sh
bin/cli honeybee.core.util.watch_scss
```

The default theme from the `themes.default` setting will be watched by default. You can specify another theme to watch and can prevent watching other SCSS files from the modules and project area if those are not necessary for your project.
Have a look that the default arguments of the commandline call.

## Theme usage

The default theme of the system can be set in the `app/config/settings.xml` file by stating the folder name of the theme:

```xml
<settings prefix="themes.">
    <!-- <setting name="sass.cmd">/opt/ruby/bin/sass</setting> -->
    <setting name="default">honeybee-awesome</setting>
</settings>
```

The `sass.cmd` is optional and defaults to `/opt/ruby/bin/sass` as the path to the sass executable to use for SCSS file compilation.

## TBD / Ideas / Misc

- user defined variables of themes are possible when information about those
  variables, their name and input type (like color or text) are available in the
  `manifest.xml` file as those could be used to render a GUI for users and put
  their custom settings somewhere accessible for the theme compilation step to
  have user customizable themes. At the moment only the `theme.scss` is compiled.
- audit of security features (symlinking in pub etc.)
    - put .htaccess and index.php files or adjust puppet recipes for themes
- audit of concurrency and scalability aspects of user customizable themes
