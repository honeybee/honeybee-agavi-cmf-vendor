# Prepare the _lib/_ folder with

* _\_vars.scss_ containing a correspondence _Icon\_name -> Unicode\_char_ SCSS map

* _\_style.scss_

* _\_mixins.scss_

The fonts (TTF, SVG, WOOF) are generated through the _IcoMoon_ webapp.

## Steps

1. On _IcoMoon_ (https://icomoon.io/) generate a new font.
If available load in it the JSON configuration file to generate a template reflecting the previous downloads form _IcoMoon_.
    * **Otherwise** select the *Entypo+* package, without altering the icon sorting
    * Select *"Codes"* and set the starting code to the Unicode **Private User Area** or to **'e000'**
    * Under *"Preferences"*
        * check the box **Generate Stylesheet Variables for SCSS**
        * insert _"hb-icon-"_ as Class prefix

2. Download the archive

3. Copy the content of its _fonts/_ folder into **_pub/static/themes/<THEME\_NAME>/binaries/fonts/<FONT\_NAME>/_**
4. Copy the file _variables.scss_ into **_pub/static/themes/<THEME\_NAME>/lib/fonts/<FONT\_NAME>/_**

5. Execute the bash script on the _variables.scss_ to transform it into a SCSS map variable. Rename it to _\_vars.scss_
    * create-correspondence-icon-map.sh <font_name>/_variables.scss

6. Create a new *_fontname.scss* file for the desided styling
    * Remember to copy into it the (customized) content of the *style.css* from the archive 
    * Import the *_vars.scss* file
    * Import eventual mixins
