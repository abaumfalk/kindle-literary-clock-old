# -*- coding: utf-8 -*-
# see https://stackoverflow.com/questions/10200201/how-to-get-pango-cairo-to-word-wrap-properly
import sys

import cairo
import pango
import pangocairo

SIZE = 200
HALF = 100
QUARTER = 50

surface = cairo.ImageSurface(cairo.FORMAT_ARGB32, SIZE, SIZE)

context = cairo.Context(surface)
context.set_source_rgb(1, 0, 0)
context.rectangle(QUARTER, QUARTER, HALF, HALF)
context.fill()

context.set_source_rgb(1, 1, 0)

context.translate(QUARTER, QUARTER)

pangocairo_context = pangocairo.CairoContext(context)
layout = pangocairo_context.create_layout()

layout.set_width(HALF)
layout.set_alignment(pango.ALIGN_LEFT)
layout.set_wrap(pango.WRAP_WORD)
layout.set_font_description(pango.FontDescription("Arial 10"))
layout.set_text("The Quick Brown Fox Jumps Over The Piqued Gymnast")


pangocairo_context.update_layout(layout)
pangocairo_context.show_layout(layout)

context.show_page()

with file("test.png", "w") as op:
    surface.write_to_png(op)

