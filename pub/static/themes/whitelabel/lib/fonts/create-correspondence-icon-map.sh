#!/bin/bash
FILE="$1"

sed -i 's|\$hb-icon-|    |' "$FILE"
sed -i 's|;|,|' "$FILE"
sed -i '1s|^|\/\/ Icons name->unicode map\n\$hb-icon: (\n|' "$FILE"
sed -i -e '$a\);' "$FILE"
