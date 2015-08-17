
Some example Honeybee file structure:

```
cms
├── configure-project.sh
├── honeybee
│   ├── app
│   │   ├── cache
│   │   ├── config
│   │   ├── lib
│   │   ├── log
│   │   ├── modules
│   │   │   ├── Core
│   │   │   │   ├── config
│   │   │   │   │   └── ...
│   │   │   │   ├── impl
│   │   │   │   │   └── ...
│   │   │   │   ├── lib
│   │   │   │   │   └── ...
│   │   │   │   ├── templates
│   │   │   │   │   └── ...
│   │   │   │   └── resources
│   │   │   │       └── ...
│   │   │   ├── Article                  -->     ../../../project/modules/Article
│   │   │   ├── Category                 -->     ../../../project/modules/Category
│   │   │   ├── ...
│   │   │   ...
│   │   ├── resources
│   │   ├── templates
│   │   └── project/*                    -->     ../../project/app/*
│   │
│   ├── bin/*
│   ├── docs/*
│   ├── data/*
│   ├── pub
│   │   ├── static
│   │   │   ├── themes
│   │   │   │   ├── honeybee-minimal
│   │   │   │   └── trololo              -->     ../../../../project/app/themes/trololo
│   │   │   ├── modules
│   │   │   │   ├── Core                 -->     ../../../app/modules/Core
│   │   │   │   ├── Article              -->     ../../../../project/modules/Article
│   │   │   │   ├── Category             -->     ../../../../project/modules/Category
│   │   │   │   ├── project              -->     ../../../../project/app/resources
│   │   │   │   └── ...                  -->     ../../../../project/modules/*
│   │   │   └── require.js               -->     ../../../vendor/jrburke/requirejs/require.js
│   │   └── index.php
│   ├── Makefile
│   ├── vendor/*
│   ...
│
└── project
    ├── app
    │   ├── config
    │   │   ├── logging.xml
    │   │   ├── mail.xml
    │   │   └── translation.xml
    │   ├── resources
    │   │   ├── Main.js
    │   │   └── styles.scss
    │   ├── templates
    │   │    ├── example.mail.twig
    │   │    ├── Master.twig
    │   │    ├── modules
    │   │    │   ├── Core
    │   │    │   │   └── System
    │   │    │   │       └── Error404
    │   │    │   │           └── Error404Success.twig
    │   │    │   └── User
    │   │    │       ├── example.twig
    │   │    │       └── ResetPassword
    │   │    │           └── ResetPassword.lol.mail.twig
    │   │    └── rjs_shim.twig
    │   └── themes
    │       └── trololo
    │           ├── binaries/*
    │           ├── ui/*
    │           ├── manifest.xml
    │           ├── _vars.scss
    │           ├── _framework.scss
    │           ├── _styles.scss
    │           └── theme.scss
    └── modules
        ├── Article
        │   ├── config
        │   │   └── ...
        │   ├── impl
        │   │   └── ...
        │   ├── lib
        │   │   └── ...
        │   ├── templates
        │   │   └── ...
        │   └── resources
        │       ├── Main.js
        │       └── styles.scss
        ├── Category
        │   ├── config
        │   │   └── ...
        │   ├── impl
        │   │   └── ...
        │   ├── lib
        │   │   └── ...
        │   ├── templates
        │   │   └── ...
        │   └── resources
        │       ├── Main.js
        │       └── styles.scss
        │
        ...
```
