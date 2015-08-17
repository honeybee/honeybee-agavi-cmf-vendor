# Favicons

- see https://github.com/audreyr/favicon-cheat-sheet
- put favicon.ico in docroot or send 204 No Content instead for that url:
- favicon.ico should contain at least the following sizes: 16x16, 24x24, 32x32, 48x48, 64x64

```
    <meta name="msapplication-TileColor" content="{{ ac('themes.tilecolor', '#FFFFFF') }}" />
    <meta name="msapplication-TileImage" content="{{ theme_url }}favicon-144.png" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ theme_url }}favicon.ico" />
    <link rel="apple-touch-icon-precomposed" sizes="228x228" href="{{ theme_url }}favicon-228.png" />
    <link rel="apple-touch-icon-precomposed" sizes="152x152" href="{{ theme_url }}favicon-152.png" />
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="{{ theme_url }}favicon-144.png" />
    <link rel="apple-touch-icon-precomposed" sizes="120x120" href="{{ theme_url }}favicon-120.png" />
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="{{ theme_url }}favicon-114.png" />
```
