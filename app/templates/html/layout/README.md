# MasterLayout

The *twig block structure* used in the ```html/layout/MasterLayout.twig``` is as follows:

- `htmlhead`
    - ```more_meta```
    - `favicons`
    - `stylesheets`
- `htmlbody`
    - `page`
        - `pageheader`
            - `menu`
                - `userwidget`
                - `mainnav`
            - `brand`
        - `pagesubheader`
            - ```subheader_activities```
        - `pageerrors`
        - `pagecontent`
            - `pageinfo`
                - `toc`
                - `notifications`
            - `main`
                - `primary`
                - `secondary`
        - `pagefooter`
    - `javascripts`
        - ```requirejs_include```
        - ```requirejs_config```
            - ```requirejs_config_defaults```
            - ```requirejs_config_additional```
            - ```requirejs_config_paths```
            - ```requirejs_config_shim```
            - ```requirejs_config_more```
            - ```requirejs_errors```
        - ```requirejs_requires```
