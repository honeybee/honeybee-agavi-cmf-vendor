# Actvity templates Conventions

* Always add an _empty space_ **at the beginning and at the end** of the "rel" attribute value, of the rendered activity.

  The "rel" attribute is used to style the activity depending on its purpose, relying on the CSS3 attribute selector.
  The spaces should also be used around the searched value (e.g: _[attribute*=" searched-value "]_) so that conflicts could be avoided (e.g: matching _"h**one**stly"_ when searching for _"one"_).
  