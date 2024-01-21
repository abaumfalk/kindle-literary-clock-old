#!/usr/bin/env python3
import cairocffi
import pangocffi
import pangocairocffi
from pangocffi import units_from_double, units_to_double

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
layout.apply_markup(f"<span font_desc='Sans 18'>This is a <b>long</b> example-text, which should be wrapped to fit to the "
                    f"layout width of {WIDTH}; by the way:\n"
                    'Now we have all flexibility needed to display the time in bold letters, such as <b>11:43</b>, '
                    'anywhere in the text!</span>')

inc, logical = layout.get_extents()
print(f"inc rect: {units_to_double(inc.width)} x {units_to_double(inc.height)}")
print(f"logical rect: {units_to_double(logical.width)} x {units_to_double(logical.height)}")

pangocairocffi.show_layout(context, layout)
surface.write_to_png("test.png")
