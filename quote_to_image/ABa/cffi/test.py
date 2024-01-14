#!/usr/bin/env python3
import cairocffi
import pangocffi
import pangocairocffi
from pangocffi import units_from_double

WIDTH = 200
HEIGHT = 400

surface = cairocffi.ImageSurface(cairocffi.FORMAT_ARGB32, WIDTH, HEIGHT)
context = cairocffi.Context(surface)

# fill background
with context:
    context.set_source_rgb(1, 1, 1)  # white
    context.paint()

layout = pangocairocffi.create_layout(context)

layout.wrap = pangocffi.WrapMode.WORD
layout.width = units_from_double(WIDTH)
layout.text = (f"This is a long example text, which should be wrapped to fit the current width to the "
               f"layout width of {WIDTH}.")

pangocairocffi.show_layout(context, layout)
surface.write_to_png("test.png")
