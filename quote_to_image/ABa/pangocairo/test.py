#!/usr/bin/env python3
import cairo
import gi
gi.require_version('Pango', '1.0')
gi.require_version('PangoCairo', '1.0')
from gi.repository import Pango, PangoCairo

WIDTH = 200
HEIGHT = 400

surface = cairo.ImageSurface(cairo.FORMAT_ARGB32, WIDTH, HEIGHT)
context = cairo.Context(surface)

# fill background
# context.set_source_rgb(1, 1, 1)  # white
# context.paint()

layout = PangoCairo.create_layout(context)

layout.set_wrap(Pango.WrapMode.WORD)
layout.set_width(WIDTH * Pango.SCALE)
layout.set_markup(f"This is a <b>long</b> example-text, which should be wrapped to fit to the "
                  f"layout width of {WIDTH}; by the way:\n"
                  '<span foreground="blue" size="x-large">Blue text</span> is <i>cool</i>!\n'
                  'Now we have all flexibility needed to display the time in bold letters, such as <b>11:43</b>, '
                  'anywhere in the text!')

PangoCairo.show_layout(context, layout)
surface.write_to_png("test.png")
