#!/bin/zsh
# Render the SVGs to PNG/JPG with headless Chrome. Run from anywhere: ./render.sh
set -e
cd "${0:A:h}"
CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
shot() { # svg out w h scale
  "$CHROME" --headless=new --disable-gpu --hide-scrollbars --force-device-scale-factor=$5 \
    --window-size=$3,$4 --screenshot="$PWD/$2" "file://$PWD/$1" >/dev/null 2>&1
}
"$CHROME" --headless=new --disable-gpu --hide-scrollbars --default-background-color=00000000 --window-size=512,512 --screenshot="$PWD/icon.png" "file://$PWD/icon.svg" >/dev/null 2>&1
shot banner-light.svg banner-light.png 1200 300 1
shot banner-dark.svg banner-dark.png 1200 300 1
shot social-preview.svg social-preview.png 1280 640 1
shot filament-directory.svg filament-directory.png 2560 1440 1
magick filament-directory.png -resize 2560x1440 -quality 92 filament-directory.jpg
rm filament-directory.png
for f in *.png *.jpg; do echo "$f $(magick identify -format '%wx%h' $f)"; done
