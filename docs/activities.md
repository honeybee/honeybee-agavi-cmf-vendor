# Activities

- [Activities](#activities)
  - [Resources / Link Relations](#resources--link-relations)
  - [Activities](#activities)
  - [TBD / Ideas / Misc](#tbd--ideas--misc)

Activities are actions a client may perform. They are configurable to be able to
reuse them across different output types (media types). This means, that it
should be possible to define activities like ```create_document``` and render
them according to the needs of the mediatype that is rendered as output. In HTML
this may mean a form or anchor element while in json based mediatypes it may be
a link property,

## Resources / Link Relations

Link relations may need these properties to be documented properly:

- name
- title
- description
- curie
- uri
- allowed methods (ALLOWED-METHODS; e.g. GET, POST)
- acceptable input mime types (ACCEPT-TYPES; e.g. application/x-www-form-urlencoded, multipart/form-data)
- supported output mime types (CONTENT-TYPES; e.g. text/html, application/hal+json)
- fields
    - name
    - type
    - description
    - constraints/validation
- example usage
- example representations
- zoomable resources (to zoom/filter/search)

This means, that a link relation can map to a Honeybee domain entity (module).

## Activities

Activities are like actions a client can perform and thus may profit from these
properties when being documented or rendered:

- name
- title
- description
- method
- type
- fields
    - name
    - type
    - constraints
    - description
- templated uri
    - params?
- route
    - name
    - parameters
    - options?
- rels
    - see above: [Resources / Link Relations](#resources--link-relations)

## TBD / Ideas / Misc

- tbd
