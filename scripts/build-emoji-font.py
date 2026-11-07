#!/usr/bin/env python3
"""Genera assets/fonts/OEPEmoji.ttf y src/data/emoji-map.php.

DOMPDF (Cpdf) solo maneja caracteres del plano basico de Unicode (hasta U+FFFF).
Los emojis modernos viven por encima de ese limite, asi que se remapean al area de
uso privado (U+E000...). El plugin usa la fuente y el mapa generados aqui.

Requiere fontTools:
    python3 -m pip install fonttools

Uso:
    python3 scripts/build-emoji-font.py [ruta-a-NotoEmoji.ttf]

Si no se pasa la ruta, descarga la fuente variable de Google Fonts.
"""

import os
import sys
import urllib.request

from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont
from fontTools.ttLib.tables._c_m_a_p import CmapSubtable

SOURCE_URL = (
    "https://raw.githubusercontent.com/google/fonts/main/ofl/notoemoji/"
    "NotoEmoji%5Bwght%5D.ttf"
)
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_FONT = os.path.join(ROOT, "assets", "fonts", "OEPEmoji.ttf")
OUT_MAP = os.path.join(ROOT, "src", "data", "emoji-map.php")
PUA_START = 0xE000


def load_source(path):
    if path:
        return TTFont(path)

    local = os.path.join("/tmp", "NotoEmoji-build.ttf")
    if not os.path.isfile(local):
        print("Descargando", SOURCE_URL)
        urllib.request.urlretrieve(SOURCE_URL, local)
    return TTFont(local)


def main():
    source = sys.argv[1] if len(sys.argv) > 1 else ""
    font = load_source(source)

    # Fuente estatica en peso regular: las metricas no cambian entre peticiones.
    if "fvar" in font:
        font = instantiateVariableFont(font, {"wght": 400}, updateFontNames=False)

    cmap = font.getBestCmap()
    codepoints = sorted(cp for cp in cmap if cp > 0x7F)
    if len(codepoints) > 0xF8FF - PUA_START + 1:
        raise SystemExit("No caben todos los emojis en el area de uso privado")

    mapping = {}
    remapped = {}
    for index, codepoint in enumerate(codepoints):
        pua = PUA_START + index
        mapping[codepoint] = pua
        remapped[pua] = cmap[codepoint]

    subtable = CmapSubtable.newSubtable(4)
    subtable.platformID = 3
    subtable.platEncID = 1
    subtable.language = 0
    subtable.cmap = remapped
    font["cmap"].tables = [subtable]

    # La OFL de Noto reserva el nombre: es una fuente derivada y se renombra.
    name = font["name"]
    for name_id, value in [
        (1, "OEP Emoji"),
        (3, "OEP Emoji Regular"),
        (4, "OEP Emoji"),
        (6, "OEPEmoji"),
        (16, "OEP Emoji"),
        (17, "Regular"),
    ]:
        name.setName(value, name_id, 3, 1, 0x409)
        name.setName(value, name_id, 1, 0, 0)

    font.save(OUT_FONT)
    print("Fuente:", OUT_FONT, os.path.getsize(OUT_FONT), "bytes")

    with open(OUT_MAP, "w", encoding="utf-8") as fh:
        fh.write("<?php\n\n")
        fh.write("/**\n * Mapa de emojis a su codigo en la fuente OEPEmoji.ttf.\n *\n")
        fh.write(" * DOMPDF (Cpdf) solo maneja caracteres del plano basico: los emojis por encima de\n")
        fh.write(" * U+FFFF se remapean al area de uso privado (U+E000 en adelante). Lo genera\n")
        fh.write(" * scripts/build-emoji-font.py.\n *\n")
        fh.write(" * @return array<int,int> codigo Unicode => codigo en la fuente\n */\n")
        fh.write("function oep_emoji_map()\n{\n    return [\n")
        for codepoint in codepoints:
            fh.write("        %d => %d,\n" % (codepoint, mapping[codepoint]))
        fh.write("    ];\n}\n")
    print("Mapa:", OUT_MAP, len(codepoints), "emojis")


if __name__ == "__main__":
    main()
