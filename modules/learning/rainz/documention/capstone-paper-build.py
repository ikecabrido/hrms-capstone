#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Build "Learning-and-Development-Capstone-Paper.docx" with no third-party
dependencies (Python standard library + zipfile only).

Formatting follows the College of Computing Studies capstone document format
taken from "Capstone-Documents-Format.docx":
  * Arial, 11 pt, double-spaced, justified body text
  * 8.5 x 11 in paper, 1.25 in side margins, 1 in top/bottom
  * lowercase-roman page numbers for the front matter, decimal for the body
  * front matter: title page, approval sheet, acknowledgment, dedication,
    abstract, table of contents, list of tables, list of figures,
    list of appendices
  * chapters and numbered sections, auto-updating TOC / list-of fields

Run:  python capstone-paper-build.py
"""

import os
import struct
import zipfile
from xml.sax.saxutils import escape

HERE = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(HERE, "Learning-and-Development-Capstone-Paper.docx")
ARCH_PNG = os.path.join(HERE, "ld-system-architecture.png")

BODY_W = 8640          # usable text width in twips (12240 - 1800 - 1800)
EMU_IN = 914400        # English Metric Units per inch
FIG_W_EMU = int(6.0 * EMU_IN)

W_NS = "http://schemas.openxmlformats.org/wordprocessingml/2006/main"
R_NS = "http://schemas.openxmlformats.org/officeDocument/2006/relationships"


# --------------------------------------------------------------------------
# helpers
# --------------------------------------------------------------------------
def esc(t):
    return escape(t)


def png_size(path):
    """Return (width, height) in pixels straight from the PNG IHDR chunk."""
    with open(path, "rb") as fh:
        data = fh.read(33)
    if data[:8] != b"\x89PNG\r\n\x1a\n":
        raise ValueError("not a PNG: %s" % path)
    return struct.unpack(">II", data[16:24])


def run(text, bold=False, italic=False, sz=None, underline=False):
    rpr = ['<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>']
    if bold:
        rpr.append("<w:b/><w:bCs/>")
    if italic:
        rpr.append("<w:i/><w:iCs/>")
    if underline:
        rpr.append('<w:u w:val="single"/>')
    if sz:
        rpr.append('<w:sz w:val="%d"/><w:szCs w:val="%d"/>' % (sz, sz))
    return "<w:r><w:rPr>%s</w:rPr><w:t xml:space=\"preserve\">%s</w:t></w:r>" % (
        "".join(rpr), esc(text))


def seq_field(kind):
    """Inline SEQ field so List of Tables / List of Figures can be generated.

    NB: these fields are deliberately NOT flagged w:dirty and settings.xml does
    not set w:updateFields. Forcing Word to resolve every field at load time
    (3 Table-of-Contents fields, 8 SEQ fields and the PAGE fields in the footer)
    makes Word hang on a document of this length. The fields carry sensible
    placeholder values and the reader builds them once with F9.
    """
    return ("<w:fldSimple w:instr=' SEQ %s \\* ARABIC '>"
            "<w:r><w:rPr><w:rFonts w:ascii=\"Arial\" w:hAnsi=\"Arial\"/>"
            "<w:sz w:val=\"20\"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>" % kind)


def toc_field(instr, placeholder):
    # NB: the instruction is delimited by single quotes so that the field
    # switches may contain double quotes, as Word itself writes them.
    return ("<w:fldSimple w:instr='%s'><w:r>"
            '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/>'
            '<w:sz w:val="22"/></w:rPr><w:t>%s</w:t></w:r></w:fldSimple>'
            % (instr, esc(placeholder)))


def para(runs="", style=None, jc=None, line=480, before=0, after=0,
         first=None, left=None, hanging=None, keepnext=False, outline=None,
         pagebreak=False, keep_lines=False):
    ppr = []
    if style:
        ppr.append('<w:pStyle w:val="%s"/>' % style)
    if keepnext:
        ppr.append("<w:keepNext/>")
    if keep_lines:
        ppr.append("<w:keepLines/>")
    if pagebreak:
        ppr.append("<w:pageBreakBefore/>")
    ppr.append('<w:spacing w:before="%d" w:after="%d" w:line="%d" '
               'w:lineRule="auto"/>' % (before, after, line))
    if first is not None or left is not None or hanging is not None:
        ppr.append('<w:ind w:firstLine="%d" w:left="%d" w:hanging="%d"/>'
                   % (first or 0, left or 0, hanging or 0))
    if jc:
        ppr.append('<w:jc w:val="%s"/>' % jc)
    if outline is not None:
        ppr.append('<w:outlineLvl w:val="%d"/>' % outline)
    return "<w:p><w:pPr>%s</w:pPr>%s</w:p>" % ("".join(ppr), runs)


def cell(text, width, bold=False, shade=None, jc=None):
    tcpr = ['<w:tcW w:w="%d" w:type="dxa"/>' % width]
    if shade:
        tcpr.append('<w:shd w:val="clear" w:color="auto" w:fill="%s"/>' % shade)
    body = para(run(text, bold=bold, sz=20), jc=jc or "left", line=240,
                before=20, after=20)
    return "<w:tc><w:tcPr>%s</w:tcPr>%s</w:tc>" % ("".join(tcpr), body)


def build_table(headers, rows, widths=None, caption_seq=None, caption=None):
    cols = len(headers)
    if not widths:
        widths = [BODY_W // cols] * cols
    widths = [int(BODY_W * w / float(sum(widths))) for w in widths]
    borders = ("<w:tblBorders>"
               + "".join('<w:%s w:val="single" w:sz="6" w:space="0" w:color="7F7F9C"/>'
                         % edge for edge in
                         ("top", "left", "bottom", "right", "insideH", "insideV"))
               + "</w:tblBorders>")
    out = []
    if caption:
        out.append(para(
            run("Table ", sz=20) + seq_field("Table")
            + run(". " + caption, sz=20),
            style="Caption", jc="center", line=240, before=120, after=60,
            keepnext=True))
    out.append('<w:tbl><w:tblPr><w:tblW w:w="%d" w:type="dxa"/>'
               '<w:tblLayout w:type="fixed"/>%s</w:tblPr>' % (BODY_W, borders))
    out.append("<w:tblGrid>%s</w:tblGrid>"
               % "".join('<w:gridCol w:w="%d"/>' % w for w in widths))
    out.append("<w:tr><w:trPr><w:tblHeader/></w:trPr>%s</w:tr>"
               % "".join(cell(h, w, bold=True, shade="DCDCE8") for h, w in zip(headers, widths)))
    for row in rows:
        out.append("<w:tr>%s</w:tr>"
                   % "".join(cell(c, w) for c, w in zip(row, widths)))
    out.append("</w:tbl>")
    out.append(para(run("", sz=12), line=240, after=0))
    return "".join(out)


def build_figure(path, caption):
    w_px, h_px = png_size(path)
    cx = FIG_W_EMU
    cy = int(cx * h_px / float(w_px))
    pic_id = 100
    drawing = (
        '<w:p><w:pPr><w:spacing w:before="120" w:after="60" w:line="240" '
        'w:lineRule="auto"/><w:jc w:val="center"/></w:pPr><w:r><w:drawing>'
        '<wp:inline distT="0" distB="0" distL="0" distR="0">'
        '<wp:extent cx="%d" cy="%d"/><wp:effectExtent l="0" t="0" r="0" b="0"/>'
        '<wp:docPr id="%d" name="Figure"/><wp:cNvGraphicFramePr>'
        '<a:graphicFrameLocks xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" noChangeAspect="1"/>'
        '</wp:cNvGraphicFramePr><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main">'
        '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        '<pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
        '<pic:nvPicPr><pic:cNvPr id="%d" name="%s"/><pic:cNvPicPr/></pic:nvPicPr>'
        '<pic:blipFill><a:blip r:embed="rIdImg1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
        '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="%d" cy="%d"/></a:xfrm>'
        '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic>'
        '</a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>'
        % (cx, cy, pic_id, pic_id, os.path.basename(path), cx, cy))
    cap = para(run("Figure ", sz=20) + seq_field("Figure")
               + run(". " + caption, sz=20),
               style="Caption", jc="center", line=240, before=60, after=180)
    return drawing + cap


# --------------------------------------------------------------------------
# markup interpreter
# --------------------------------------------------------------------------
def render(markup):
    body = []
    for raw in markup.split("\n"):
        line = raw.rstrip()
        if not line.strip():
            continue
        tag, _, payload = line.partition("|")
        tag = tag.strip()

        if tag == "CHAP":
            num, _, title = payload.partition("|")
            body.append(para(run(num.strip(), bold=True, sz=28)
                             + "<w:r><w:br/></w:r>"
                             + run(title.strip(), bold=True, sz=28),
                             style="Heading1", jc="center", line=240,
                             after=240, outline=0, pagebreak=True))
        elif tag == "H1":
            body.append(para(run(payload, bold=True, sz=28), style="Heading1",
                             jc="center", line=240, after=240, outline=0,
                             pagebreak=True))
        elif tag == "H2":
            body.append(para(run(payload, bold=True, sz=24), style="Heading2",
                             jc="left", line=240, before=240, after=120,
                             outline=1, keepnext=True, keep_lines=True))
        elif tag == "H3":
            body.append(para(run(payload, bold=True, sz=22), style="Heading3",
                             jc="left", line=240, before=200, after=100,
                             outline=2, keepnext=True, keep_lines=True))
        elif tag == "CT":
            body.append(para(run(payload, bold=True, sz=26), jc="center",
                             line=240, before=240, after=240, outline=9))
        elif tag == "P":
            body.append(para(run(payload), first=720))
        elif tag == "PN":
            body.append(para(run(payload)))
        elif tag == "PC":
            body.append(para(run(payload, italic=True), first=720))
        elif tag == "BD":
            body.append(para(run("\u2022\u2003" + payload), left=720,
                             hanging=360))
        elif tag == "REF":
            body.append(para(run(payload), left=720, hanging=720))
        elif tag == "TL":
            body.append(para(run(payload, bold=True, sz=28), jc="center",
                             line=240, after=60))
        elif tag == "TM":
            body.append(para(run(payload, sz=22), jc="center", line=240))
        elif tag == "C":
            body.append(para(run(payload), jc="center", line=240))
        elif tag == "CB":
            body.append(para(run(payload, bold=True), jc="center", line=240))
        elif tag == "TOC":
            body.append(para(
                toc_field(' TOC \\o "1-3" \\h \\z \\u ',
                          "Right-click this line and choose Update Field, or press "
                          "Ctrl+A then F9, to build the Table of Contents."),
                line=360))
        elif tag == "LOT":
            body.append(para(
                toc_field(' TOC \\h \\z \\c "Table" ',
                          "Right-click this line and choose Update Field, or press "
                          "F9, to build the List of Tables."),
                line=360))
        elif tag == "LOF":
            body.append(para(
                toc_field(' TOC \\h \\z \\c "Figure" ',
                          "Right-click this line and choose Update Field, or press "
                          "F9, to build the List of Figures."),
                line=360))
        elif tag == "PBRK":
            body.append(para("<w:r><w:br w:type=\"page\"/></w:r>", line=240))
        elif tag == "T":
            parts = payload.split("|")
            caption = parts[0]
            headers = parts[1].split(";")
            rows = [p.split(";") for p in parts[2:]]
            body.append(build_table(headers, rows, caption=caption))
        elif tag == "FIG":
            path, _, caption = payload.partition("|")
            body.append(build_figure(os.path.join(HERE, path.strip()), caption))
        else:
            raise ValueError("unknown tag %r in line %r" % (tag, raw))
    return "".join(body)


# --------------------------------------------------------------------------
# static package parts
# --------------------------------------------------------------------------
STYLES = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="%(w)s">
<w:docDefaults>
<w:rPrDefault><w:rPr>
<w:rFonts w:ascii="Arial" w:eastAsia="Arial" w:hAnsi="Arial" w:cs="Arial"/>
<w:sz w:val="22"/><w:szCs w:val="22"/>
<w:lang w:val="en-PH" w:eastAsia="en-PH" w:bidi="ar-SA"/>
</w:rPr></w:rPrDefault>
<w:pPrDefault><w:pPr>
<w:spacing w:after="0" w:line="480" w:lineRule="auto"/><w:jc w:val="both"/>
</w:pPr></w:pPrDefault>
</w:docDefaults>
<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/>
<w:qFormat/>
<w:pPr><w:spacing w:after="0" w:line="480" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr>
<w:rPr><w:rFonts w:ascii="Arial" w:eastAsia="Arial" w:hAnsi="Arial" w:cs="Arial"/>
<w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/>
<w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
<w:pPr><w:keepNext/><w:keepLines/><w:pageBreakBefore/>
<w:spacing w:before="0" w:after="240" w:line="240" w:lineRule="auto"/>
<w:jc w:val="center"/><w:outlineLvl w:val="0"/></w:pPr>
<w:rPr><w:b/><w:bCs/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/>
<w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
<w:pPr><w:keepNext/><w:keepLines/>
<w:spacing w:before="240" w:after="120" w:line="240" w:lineRule="auto"/>
<w:jc w:val="left"/><w:outlineLvl w:val="1"/></w:pPr>
<w:rPr><w:b/><w:bCs/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Heading3"><w:name w:val="heading 3"/>
<w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
<w:pPr><w:keepNext/><w:keepLines/>
<w:spacing w:before="200" w:after="100" w:line="240" w:lineRule="auto"/>
<w:jc w:val="left"/><w:outlineLvl w:val="2"/></w:pPr>
<w:rPr><w:b/><w:bCs/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr></w:style>
<w:style w:type="paragraph" w:styleId="Caption"><w:name w:val="Caption"/>
<w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/>
<w:pPr><w:spacing w:before="120" w:after="120" w:line="240" w:lineRule="auto"/>
<w:jc w:val="center"/></w:pPr>
<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="20"/>
<w:szCs w:val="20"/></w:rPr></w:style>
</w:styles>
""" % {"w": W_NS}

FOOTER_PAGE = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="%(w)s" xmlns:r="%(r)s">
<w:p><w:pPr><w:jc w:val="center"/>
<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>
<w:fldSimple w:instr=" PAGE " w:dirty="true"><w:r>
<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/></w:rPr>
<w:t>1</w:t></w:r></w:fldSimple></w:p></w:ftr>
""" % {"w": W_NS, "r": R_NS}

FOOTER_EMPTY = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="%(w)s" xmlns:r="%(r)s"><w:p><w:pPr><w:jc w:val="center"/>
<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/></w:pPr>
</w:p></w:ftr>
""" % {"w": W_NS, "r": R_NS}

SETTINGS = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="%(w)s">
<w:zoom w:percent="100"/>
<w:defaultTabStop w:val="720"/>
<w:compat><w:compatSetting w:name="compatibilityMode"
w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>
</w:settings>
""" % {"w": W_NS}

CORE = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties
 xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
 xmlns:dc="http://purl.org/dc/elements/1.1/"
 xmlns:dcterms="http://purl.org/dc/terms/"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
<dc:title>Learning and Development System - Capstone Project</dc:title>
<dc:subject>Human Resource Management System - Learning and Development Module</dc:subject>
<dc:creator>Ronato, I. B.; Quebada, R. B.; Vargas, J. M.; Sarabia, N. A. A.; Lababo, K. M.</dc:creator>
<cp:lastModifiedBy>Bestlink College of the Philippines - Bulacan Campus</cp:lastModifiedBy>
<dcterms:created xsi:type="dcterms:W3CDTF">2026-02-01T00:00:00Z</dcterms:created>
<dcterms:modified xsi:type="dcterms:W3CDTF">2026-02-01T00:00:00Z</dcterms:modified>
</cp:coreProperties>
"""

APP = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
 xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
<Application>Microsoft Office Word</Application>
<DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop>
<Company>Bestlink College of the Philippines</Company>
</Properties>
"""

REL_ROOT = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
"""

DOC_RELS = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
<Relationship Id="rIdSettings" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/>
<Relationship Id="rIdFtr" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>
<Relationship Id="rIdFtrFirst" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer2.xml"/>
<Relationship Id="rIdImg1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/architecture.png"/>
</Relationships>
"""

CONTENT_TYPES = """<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Default Extension="png" ContentType="image/png"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
<Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>
<Override PartName="/word/footer2.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>
<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
"""


def document_xml(body):
    front_sect = ('<w:p><w:pPr><w:sectPr>'
                  '<w:footerReference w:type="default" r:id="rIdFtr"/>'
                  '<w:footerReference w:type="first" r:id="rIdFtrFirst"/>'
                  '<w:pgSz w:w="12240" w:h="15840"/>'
                  '<w:pgMar w:top="2517" w:right="1871" w:bottom="1440" '
                  'w:left="2517" w:header="720" w:footer="720" w:gutter="0"/>'
                  '<w:pgNumType w:fmt="lowerRoman" w:start="1"/>'
                  '<w:cols w:space="720"/><w:titlePg/>'
                  '<w:docGrid w:linePitch="360"/></w:sectPr></w:pPr></w:p>')
    body_sect = ('<w:sectPr>'
                 '<w:footerReference w:type="default" r:id="rIdFtr"/>'
                 '<w:pgSz w:w="12240" w:h="15840"/>'
                 '<w:pgMar w:top="1440" w:right="1800" w:bottom="1440" '
                 'w:left="1800" w:header="720" w:footer="720" w:gutter="0"/>'
                 '<w:pgNumType w:fmt="decimal" w:start="1"/>'
                 '<w:cols w:space="720"/><w:docGrid w:linePitch="360"/></w:sectPr>')
    return ('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>\n'
            '<w:document xmlns:w="%s" xmlns:r="%s" '
            'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            "<w:body>%s%s%s</w:body></w:document>"
            % (W_NS, R_NS, body, front_sect, body_sect))


# --------------------------------------------------------------------------
# content
# --------------------------------------------------------------------------
MARKUP = r"""
TL|BESTLINK COLLEGE OF THE PHILIPPINES
TL|BULACAN CAMPUS
C|
TL|HUMAN RESOURCE MANAGEMENT SYSTEM
TL|(LEARNING AND DEVELOPMENT MODULE)
C|
TM|A Capstone Project
TM|Presented to the Faculty of the
TM|College of Computing Studies
TM|Bestlink College of the Philippines - Bulacan Campus
C|
TM|In Partial Fulfillment of the Requirements for the Degree
TM|Bachelor of Science in Information Technology
C|
TL|RONATO, IRENE BENITEZ
TL|QUEBADA, RAINIEL B.
TL|VARGAS, JOHN MICHAEL
TL|SARABIA, NERIE ANN A.
TL|LABABO, KIMBERLY M.
C|
TM|February 2026

CT|APPROVAL SHEET
P|This capstone project entitled HUMAN RESOURCE MANAGEMENT SYSTEM (LEARNING AND DEVELOPMENT MODULE), prepared and submitted by IRENE BENITEZ RONATO, RAINIEL B. QUEBADA, JOHN MICHAEL VARGAS, NERIE ANN A. SARABIA, and KIMBERLY M. LABABO in partial fulfillment of the requirements for the degree Bachelor of Science in Information Technology, has been examined and is recommended for acceptance and approval for Oral Examination.
C|
TM|_____________________________
TM|Capstone Adviser
C|
TM|APPROVED by the Committee on Oral Examination with a grade of ______________.
C|
TM|_____________________          _____________________
TM|Panel Member                              Panel Member
C|
TM|_____________________
TM|Panel Chairperson
C|
TM|Accepted and approved in partial fulfillment of the requirements for the degree Bachelor of Science in Information Technology.
C|
TM|_____________________________
TM|Dean, College of Computing Studies

CT|ACKNOWLEDGMENT
P|The researchers would like to express their sincere gratitude to the individuals and institutions whose guidance and support made this capstone project possible.
P|To the faculty of the College of Computing Studies of Bestlink College of the Philippines - Bulacan Campus, especially to the capstone adviser and the panel members, for the constructive criticism, technical guidance, and patience extended throughout the planning, development, and evaluation of this project.
P|To the administration and the Human Resource Management Office of the institution, for providing the domain knowledge on learning and development practice and for allowing the researchers to examine how training records, competency requirements, and employee development activities are currently managed.
P|To the researchers' families and friends, for the encouragement and understanding that sustained the team through the development sprints and the many revisions of this document.
P|Above all, to the Almighty God, for the wisdom, strength, and good health granted to the researchers in the completion of this work.

CT|DEDICATION
P|This capstone project is wholeheartedly dedicated to the researchers' families, whose unwavering support and sacrifices made the completion of this study possible; to the faculty of the College of Computing Studies, who equipped the researchers with the competence and discipline required in the field of information technology; and to the employees and learners who stand to benefit from a more organized and accessible system of learning and development.
P|It is likewise dedicated to the students and practitioners of information technology, in the hope that this work contributes to the growth of learning and development practice in the Philippines.

CT|ABSTRACT
P|This capstone project presents the design and development of a Learning and Development module integrated into the Human Resource Management System (HRMS) of Bestlink College of the Philippines - Bulacan Campus. The study addresses the limitations of manual and fragmented record-keeping in learning and training activities, the absence of a single source of truth for completion and certification records, the difficulty of monitoring learner progress across multi-part courses, and the lack of an automated mechanism for reporting learning outcomes to other human resource functions. Developed through the Agile Scrum methodology, the module is a role-based, server-side web application built on PHP 8.2.12, MariaDB 10.4.32, and the Apache web server. It organizes learning content in a hierarchical model in which courses contain modules, modules contain lessons and quizzes, and course-level evaluations capture learner feedback. The module manages enrolment and item-level progress tracking, quiz attempts and automatic scoring, grades, certificates issued from configurable templates with validity monitoring, skills and competency tagging, skill snapshots, training programs, video conferences with attendance, learning paths, training requests, a shared calendar, notifications, and moderation. It maintains fifty-seven learning-and-development tables and exposes nine authenticated REST endpoints that publish learning outcomes to the performance management, compliance, time and attendance, and workforce analytics functions of the HRMS. Functional, data, and integration testing confirmed that enrolment, progress computation, assessment scoring, certificate issuance, role-based page routing, and cross-module data publication behave according to specification, and that the database schema can be deployed non-destructively. The module provides the institution with a centralized, secure, and traceable learning and development environment.
PN|Keywords: learning and development, human resource management system, learning management system, e-learning, competency management, system integration, Agile Scrum

CT|TABLE OF CONTENTS
TOC

CT|LIST OF TABLES
LOT

CT|LIST OF FIGURES
LOF

CT|LIST OF APPENDICES
BD|Appendix A - Learning and Development Database Schema (ld_tables schema.sql)
BD|Appendix B - Schema Export and Deployment Utility (ld_schema_export.php)
BD|Appendix C - System Architecture Diagram (ld-system-architecture.png)
BD|Appendix D - Cross-Module Integration Specifications (integration mds/)

CHAP|CHAPTER 1|INTRODUCTION
H2|1.1 Background of the Capstone Project
P|Learning and development are fundamental components of education and organizational growth. They involve the continuous acquisition of knowledge, skills, attitudes, and competencies that enable individuals to perform effectively and to adapt to changing environments. As technology continues to transform education and training, learning and development systems have become valuable tools for improving the delivery, monitoring, and evaluation of learning activities.
P|The concept of learning and development has evolved over several decades. Jean Piaget (1952) explained that learners develop knowledge through stages of cognitive development. Albert Bandura (1977) introduced Social Learning Theory, emphasizing that learning occurs through observation, imitation, and interaction with others. Lev Vygotsky (1978) highlighted the importance of collaboration and teacher guidance in promoting effective learning through his Sociocultural Theory. David A. Kolb (1984) proposed Experiential Learning Theory, stating that learning becomes meaningful when learners gain knowledge through experience, reflection, and application. Taken together, these theories establish that learning is developmental, social, and experiential, and they imply that a learning system should support structured progression, interaction and feedback, and opportunities to practice and apply what has been learned.
P|As technology advanced, researchers began emphasizing the integration of digital tools into learning. UNESCO (2021) reported that digital learning environments, lifelong learning, and twenty-first century skills are essential for preparing students and employees for future challenges. Likewise, the Organisation for Economic Co-operation and Development (OECD, 2023) emphasized that technology-supported learning systems improve student engagement, personalized instruction, and educational outcomes when combined with effective teaching practices.
P|Despite these developments, many educational institutions and organizations still rely on manual processes for managing learning activities, training records, and performance monitoring. These methods often result in delayed reporting, inefficient record management, difficulty tracking learner progress, and limited access to learning resources. Training histories are frequently kept in separate spreadsheets and paper forms, completion records are difficult to reconcile, certificates are issued and tracked by hand, and the competencies that a training program is meant to develop are rarely linked to the record of who has acquired them. These challenges highlight the need for a more efficient and technology-based learning and development system.
P|This capstone project addresses that need within the context of the Human Resource Management System (HRMS) of Bestlink College of the Philippines - Bulacan Campus. The HRMS is an integrated, web-based information system that consolidates the administrative and human resource functions of the institution into a single platform. The Learning and Development module is the component of that system examined and developed in this study. It is the part of the HRMS responsible for the creation and delivery of learning content, the tracking of learner participation and progress, the assessment of learning, the issuance of certificates, the management of competencies and scheduled training, and the reporting of learning outcomes to the other functions of the system.
P|The module organizes learning content in a hierarchical model. A course is the top-level container; a course contains modules; a module contains lessons and quizzes; and a course carries evaluations that capture learner feedback. Lessons are authored with a rich-text editor and may carry attached files, while quizzes and evaluations are composed of questions with answer options and correctness flags. Learning content can be tagged with skills drawn from a shared master list, which allows training to be connected to the competencies it is intended to develop.
P|Beyond self-paced e-learning, the module manages scheduled and structured training. It maintains training programs with attached skills, video conferences with platform details, meeting links, duration, reminders, and attendance, learning paths that combine courses, modules, lessons, quizzes, evaluations, programs, and video conferences into an ordered sequence, and training requests through which learners may request training that addresses an identified skill gap. A shared calendar consolidates sessions, deadlines, and personal events, and an in-application notification facility informs users of enrolment activity, request decisions, conference reminders, and certificate events.
P|The module is organized around three authenticated portals: an administrator portal, an instructor portal, and a learner portal. Each portal presents only the pages that its role is permitted to access, and navigation is generated from the set of pages available to the signed-in user. Learner, instructor, and administrator identities are not duplicated by the module; they are drawn from the existing employee and user-account records of the HRMS.
P|The module was implemented as a server-side web application using PHP 8.2.12 running on the Apache web server, with MariaDB 10.4.32 as the database management system and the XAMPP distribution as the development stack. The database uses the InnoDB storage engine and stores learning data in fifty-seven tables bearing the ld_ prefix. The presentation layer is built from a custom design system in CSS and small modules of vanilla JavaScript rather than a front-end framework, with Font Awesome for iconography and Quill for rich-text authoring. Database access uses PDO prepared statements throughout, authentication is session-based, and cross-module access is protected by per-integration API keys.
P|The module is also designed to participate in the wider HRMS. It exposes a JSON REST interface composed of four inbound endpoints, through which employee profiles, appraisal data, job test results, and recognition eligibility are received, and five outbound endpoints, through which training completion, learning performance, compliance training records, attendance data, and workforce analytics are published to the corresponding modules. Every integration call is authenticated and recorded, so that the exchange of learning data between modules can be traced.
H3|Suggested Improvements in Creating the System
P|To ensure the effectiveness of the proposed Learning and Development System, the following improvements were adopted as design requirements and were reflected in the implemented module:
BD|Develop a user-friendly interface that is easy for administrators, instructors, and learners to navigate. The module provides a role-specific sidebar, a consistent page shell, searchable and filterable listings, and clear status indicators across its pages.
BD|Implement secure user authentication and role-based access to protect personal information and learning records. The module uses session-based sign-in, a page controller that restricts each role to its permitted page set, PDO prepared statements for all database access, and API keys for cross-module calls.
BD|Include a centralized database to store learner profiles, training records, assessments, certificates, and progress reports. Learning data is held in fifty-seven tables under the ld_ prefix within the shared HRMS database, referencing the existing employee, user-account, role, and department records rather than duplicating them.
BD|Provide real-time progress tracking through dashboards that allow learners and instructors to monitor performance. Enrolment status and item-level progress are recorded as learning items are completed, and progress views and gradebooks are available to both instructors and learners.
BD|Integrate online learning materials, videos, quizzes, and downloadable resources into the system. Lessons carry rich-text content and attached files, quizzes and evaluations carry question banks with answer options, and scheduled sessions are delivered through video conferences.
BD|Add automated notifications and reminders for training schedules, deadlines, assessments, and certificate releases. The module generates in-application notifications and includes a scheduled job that dispatches reminders ahead of video conferences.
BD|Generate automated reports and analytics to evaluate learner performance and the effectiveness of learning programs. Reports cover progress, grades, participation, skills, engagement, and audit activity, and selected results are published to the workforce analytics function of the HRMS.
BD|Design the system to be responsive and accessible across different devices. The interface is built on a responsive design system that adapts its layout and navigation to the available screen width.
BD|Include a feedback and evaluation module where learners can assess courses for continuous improvement. Course evaluations collect structured feedback, while ratings, comments, and moderation provide additional channels for learner input.
BD|Ensure regular system maintenance, updates, and data backup to improve reliability, security, and long-term performance. The module includes scheduled maintenance scripts for snapshot generation and database backup, a non-destructive schema deployment script, and an audit log that records significant actions.

H2|1.2 Context and Scope
P|This study focuses on the design and development of a Learning and Development module within the Human Resource Management System of Bestlink College of the Philippines - Bulacan Campus. The HRMS is composed of cooperating modules that support the various human resource functions of the institution, including employee management, recruitment and onboarding, time and attendance, performance management, compensation and payroll, compliance and legal, employee engagement and relations, exit and knowledge transfer, workforce analytics, and learning and development. This study concentrates on the Learning and Development module, including the interfaces through which it exchanges data with the other modules.
P|The system is intended to improve the management of learning and training activities by providing a centralized platform for managing learner information, learning materials, training schedules, assessments, progress monitoring, and report generation. The scope of the module is defined by the functions available to each role.
H3|Scope of the Administrator
BD|Manage user accounts and access for instructors and learners, and configure system settings that affect the module.
BD|Manage the learning catalog, including courses, modules, lessons, quizzes, evaluations, programs, and learning paths.
BD|Review system analytics, reports, and dashboards, and manage the shared calendar and in-application notifications.
BD|Review the audit log, moderate reported content, manage the archive, and extend or revoke issued certificates.
BD|Maintain the database through scheduled snapshot, backup, and non-destructive schema deployment utilities.
H3|Scope of the Instructor or Trainer
BD|Author and maintain learning content: courses, modules, lessons, quizzes with question banks, and course evaluations.
BD|Tag courses and modules with skills drawn from the shared master skill list, and attach skills to training programs.
BD|Manage enrolments, assign learners to courses, and perform bulk enrolment of groups.
BD|Schedule and manage video conferences, including platform details, meeting links, duration, reminders, and attendance.
BD|Respond to learner training requests by accepting, rejecting, or completing them.
BD|Monitor learner progress, review submitted quizzes and evaluations, record grades, and view skill-gap and engagement information.
BD|Issue, extend, and revoke certificates, and maintain certificate templates.
H3|Scope of the Learner or Employee
BD|Browse the learning catalog and filter available content by category, skill, format, and related criteria.
BD|Enrol in courses, and accept or decline invitations to enroll.
BD|Study enrolled content, resume from the last incomplete item, read lessons, and mark lessons complete.
BD|Take quizzes, review attempts, and submit course evaluations and ratings.
BD|View grades, certificates, skill summaries, and progress reports, and download issued certificates.
BD|Maintain personal notes, bookmarks, and favorites, and manage personal calendar entries and reminders.
BD|Submit training requests for skills that require development, and report content for moderation.
H3|Delimitations of the Study
P|The following boundaries define the scope of the module and of this study. The module operates on the employee, user-account, role, and department records maintained by the wider HRMS and treats those records as authoritative, so that the management of the employee master record, payroll, and time capture remains the responsibility of the functions that own them. Notifications are delivered within the application, where each is recorded against its recipient and presented through the notification facility of the module. Certificates and reports are rendered by the module itself and are printed or saved through the browser, so that the same content can be produced without an additional rendering dependency. Learning content and learning records are exchanged with the other functions of the HRMS through the module's own REST interface, which follows the integration conventions of the system. The module is delivered as a responsive web application that adapts its layout and navigation to the size of the screen on which it is used. Recommendations and recognitions follow explicit institutional mapping rules that are maintained as reference data, so that the criteria applied are visible and can be adjusted through configuration.

H2|1.3 Problem Statement
P|This capstone project is an applied, problem-based business project undertaken in partial fulfillment of the requirements of the Bachelor of Science in Information Technology program. It requires the researchers to examine a real organizational problem, to apply the knowledge acquired in the field of human resource management and information technology, and to propose and implement a verifiable solution. In keeping with the nature of the project, the researchers adopted a critical and investigative approach supported by the examination of existing records and processes, and they combined system development with technical documentation and reporting.
P|The problem addressed by this study is the absence of a centralized and automated mechanism for managing learning and development activities in the institution. The current practice depends substantially on manual and fragmented methods. Training participation and completion are recorded in separate spreadsheets and paper forms, which makes it difficult to establish a single authoritative record of who has completed which learning activity. Learner progress through multi-part courses is not tracked at the level of individual modules, lessons, and quizzes, so partial completion cannot be distinguished from non-participation. Assessment results are recorded separately from the learning content they belong to, which prevents scores from being reconciled against the material that was studied. Certificates are prepared and tracked by hand, and their validity periods are not systematically monitored. The competencies that a learning activity is intended to develop are not consistently linked to the record of employees who have acquired them, which limits the usefulness of the training record for career development and for identifying skill gaps. Scheduled trainings and virtual sessions are coordinated through separate communication channels, and attendance is recorded outside the learning record.
P|These conditions produce several consequences for the institution. Reporting on learning activity consumes considerable administrative effort and still yields delayed and inconsistent results. The institution cannot readily demonstrate the completion of required or compliance-related training. Performance evaluation and employee development planning proceed without timely and reliable information on the training that an employee has received. Employees have limited visibility of the learning opportunities available to them and of their own progress and standing.
P|Accordingly, this study sought to answer the following general question: how can a centralized, secure, and role-based Learning and Development module be designed and developed to manage learning content, enrolment, progress, assessment, certification, competencies, and scheduled training, and to publish learning outcomes to the other functions of the Human Resource Management System of Bestlink College of the Philippines - Bulacan Campus?
P|Specifically, the study sought to address the following sub-problems:
BD|How can learning content be organized so that courses, modules, lessons, quizzes, and evaluations are structured, maintainable, and reusable across learning paths and training programs?
BD|How can enrolment and progress be recorded so that item-level completion is tracked accurately and course completion, grading, and certification are applied consistently?
BD|How can assessments and learner feedback be captured and scored so that results are recorded against the correct learner, course, and attempt?
BD|How can competencies and training needs be represented so that skill gaps and appropriate training can be identified?
BD|How can scheduled and structured training, including programs, video conferences, and calendar activities, be managed alongside self-paced learning?
BD|How can learning activity and learner performance be reported to administrators and instructors in a timely and usable form?
BD|How can learning outcomes be shared with the performance management, compliance, time and attendance, and workforce analytics functions of the HRMS without duplicating their data or compromising the security of the learning records?

H2|1.4 Objectives and Goals
P|General Objective: The general objective of this study is to design and develop a Learning and Development module integrated into the Human Resource Management System of Bestlink College of the Philippines - Bulacan Campus, which centralizes the management of learning content, enrolment, progress, assessment, certification, competencies, and scheduled training, and which publishes learning outcomes to the other functions of the HRMS.
H3|Specific Objectives
BD|To design a hierarchical content model in which courses contain modules, modules contain lessons and quizzes, courses carry evaluations, and content can be tagged with skills from a shared master list and assembled into ordered learning paths.
BD|To implement role-based portals for administrators, instructors, and learners, in which each role is presented only with the pages and actions it is permitted to access.
BD|To implement enrolment and item-level progress tracking, with automatic computation of course completion and the recording of enrolment status.
BD|To implement assessments consisting of quizzes with question banks and answer options, recorded attempts and sessions, and automatic scoring, together with course evaluations that capture learner feedback.
BD|To implement certificate issuance from configurable templates, including the monitoring of certificate validity and the extension and revocation of issued certificates.
BD|To implement competency management through a shared skill master list, skill tagging of learning content and programs, periodic skill snapshots, skill-gap visibility, training recommendations, and learner-initiated training requests.
BD|To implement scheduled and structured training through programs, video conferences with attendance and reminders, and a shared calendar of sessions, deadlines, and personal events.
BD|To implement communication and tracking facilities within the module, including notifications, notes, comments, messages, announcements, ratings, bookmarks, favorites, and content moderation.
BD|To implement reporting and analytics covering learner progress, grades, participation, competencies, engagement, and audit activity.
BD|To implement a secured integration interface consisting of four inbound and five outbound REST endpoints, authenticated by API keys and recorded in an integration log, through which the module exchanges data with the other modules of the HRMS.
BD|To secure the module through session-based authentication, role-gated page routing, prepared statements for all database access, and an audit log of significant actions.
H3|Goals
BD|Improve the delivery of learning and training by providing a single, organized environment for content, schedules, and learner activity.
BD|Improve the accuracy and completeness of the learning record by capturing enrolment, completion, assessment, and certification data at the point where they occur.
BD|Provide administrators and instructors with timely visibility of learner progress, performance, and competency status.
BD|Reduce the manual effort required to prepare learning and development reports, and improve their consistency.
BD|Make skill and competency information available for employee development planning, performance evaluation, and compliance monitoring.
BD|Support both self-paced e-learning and scheduled training within one system.
BD|Encourage continuous learning by giving learners clear access to learning opportunities, visible progress, and recognition of completion.
T|Mapping of Study Objectives to System Features|Objective;Implemented Feature;Verification|Content model;Course, module, lesson, quiz, and evaluation tables and pages;Creator pages persist and reload content across sessions|Role-based access;Three portals governed by a page controller with role page maps;Non-permitted pages are refused and hidden from navigation|Enrolment and progress;Enrolment records with item-level progress and automatic completion;Progress computation matches the number of trackable items in the course|Assessment;Quiz question banks, attempts, sessions, and automatic scoring;Scores are recorded against the learner and course|Certification;Certificate templates, issuance, validity monitoring, extension, and revocation;Certificate is issued on completion and remains traceable|Competency;Shared skill master list, skill tagging, snapshots, and training requests;Skills can be attached to content and programs and reported per learner|Scheduled training;Programs, video conferences with attendance, and a shared calendar;Sessions can be scheduled, attended, and reviewed|Reporting;Progress, gradebook, participation, engagement, and audit reports;Views return consistent results after end-to-end transactions|Integration;Four inbound and five outbound REST endpoints with API-key authentication and logging;Endpoints reject unauthenticated calls and record accepted calls|Security;Session authentication, role-gated routing, prepared statements, and audit log;Unauthenticated access is refused and significant actions are logged

H2|1.5 Significance of the Study
P|This study on learning and development is significant because it demonstrates how learning activities, training programs, and development processes can be organized, monitored, and reported through a single information system. The results of this study may benefit the following:
P|Learners and Employees. The module gives learners a single place to discover learning opportunities, enroll in courses, study learning content, take assessments, and view their own progress, grades, and certificates. It makes the learner's standing visible and provides a record of completed learning that can be presented as evidence of competence.
P|Instructors and Trainers. The module provides instructors with tools for authoring and maintaining learning content, constructing assessments, managing enrolments, scheduling sessions, recording grades, and monitoring learner progress and skill gaps. It reduces the administrative effort required to track learner participation and to prepare reports on learning activity.
P|Learning and Development Administrators and Human Resource Practitioners. The module provides administrators with a consolidated view of learning activity across the institution, together with the ability to manage the catalog, the calendar, notifications, certificates, and the audit trail. The competency and training-request features give human resource practitioners a structured basis for development planning and for identifying training needs.
P|The Institution. The module supports the institution in maintaining a reliable record of learning and training activity, in demonstrating the completion of required training, and in producing reports without relying on manual consolidation. It likewise provides a foundation for later improvements, because the learning data it captures is structured and can be analyzed over time.
P|Employers and Organizations. The findings of this study illustrate how an organization can connect training to the competencies it is intended to develop, and how learning outcomes can be supplied to performance evaluation and workforce analytics. This may guide organizations in improving the effectiveness of their own training and development programs.
P|Future Researchers. This study documents the design, data model, and integration interface of a working learning and development module. It may serve as a reference for future researchers who wish to extend the module, to study the adoption of learning systems, or to develop related components of an integrated human resource information system.
P|Community and Society. An institution that manages learning and development effectively produces individuals who are better equipped with the knowledge, skills, and adaptability required in the workplace. The benefits of improved learning and development therefore extend beyond the institution to the communities and organizations in which its learners and employees participate.

H2|1.6 Structure of the Document
P|This document follows the standard capstone project format of the College of Computing Studies. Chapter 1, Introduction, presents the background of the project, the context and scope, the problem statement, the objectives and goals, the significance of the study, and the structure of the document. Chapter 2, Related Studies and Literature Review, discusses the methodologies and technologies on which the project is based, including the Agile Scrum methodology, enterprise architecture concepts, microservice architecture, DevOps and continuous integration and delivery, relevant studies and research, and the integration of information systems in enterprise environments. Chapter 3, Methodology and Project Management, describes how the project was planned and executed, covering the application of Agile Scrum, the roles and responsibilities of the project team, the sprint cycle, the Scrum artifacts, the integration approach adopted for information systems, the application of microservice principles within the module, and the use of the TOGAF framework and its four architecture domains in the design of the system.
P|This document covers Chapters 1 to 3, together with the references and the appendices identifying the technical artifacts produced by the project. The results and discussion of the project are presented in Chapter 4, and the conclusions and recommendations in Chapter 5.

CHAP|CHAPTER 2|RELATED STUDIES AND LITERATURE REVIEW
P|This chapter presents the concepts, methodologies, and studies that informed the design and development of the Learning and Development module. It begins with the development methodology adopted for the project, then examines enterprise architecture as the framework used to position the module within the wider Human Resource Management System, followed by the architectural and operational practices that shaped its implementation, the literature on learning and development systems, and the principles of information system integration in enterprise environments.

H2|2.1 Agile Scrum Methodology Overview
P|Agile is an approach to software development that favors iterative delivery, collaboration with stakeholders, and responsiveness to change over comprehensive upfront specification. Scrum is the most widely adopted Agile framework and is defined in the Scrum Guide by Schwaber and Sutherland (2020) as a lightweight framework that helps people, teams, and organizations generate value through adaptive solutions for complex problems.
P|Scrum is founded on empiricism, the principle that knowledge comes from experience and that decisions should be based on observation. It is supported by three pillars. Transparency requires that the process and its artifacts be visible to those responsible for the outcome. Inspection requires that the artifacts and the progress toward the goal be examined frequently. Adaptation requires that the process or the product be adjusted promptly when inspection reveals an unacceptable deviation.
P|The framework defines a small set of roles, events, and artifacts. The Product Owner is accountable for maximizing the value of the product and for managing the Product Backlog. The Scrum Master is accountable for the effectiveness of the team and for establishing Scrum practices. The Developers are accountable for creating the increment. The events of a Sprint include Sprint Planning, the Daily Scrum, the Sprint Review, and the Sprint Retrospective, all contained within a Sprint of fixed duration. The artifacts are the Product Backlog, the Sprint Backlog, and the Increment, each carrying a commitment: the Product Goal, the Sprint Goal, and the Definition of Done, respectively.
P|Scrum is appropriate for a project of this nature for several reasons. The requirements of a learning and development module are discovered progressively, because the researchers learned considerably more about the learning processes of the institution as the module was developed. The scope is large enough to require prioritization, and a hierarchical content model, an enrolment and progress mechanism, and an integration interface cannot be delivered simultaneously. The framework also imposes a discipline of review that suits an academic project, since each Sprint Review provides a defined point at which the working software can be demonstrated to the adviser and the stakeholders.
P|The framework is not without limitations. Scrum assumes that the Product Owner is available to the team and able to make binding decisions promptly, an assumption that is difficult to satisfy fully in a student project where the adviser and the institutional stakeholders are not continuously available. Scrum also assumes a stable and empowered team, whereas the researchers carried concurrent academic responsibilities that affected their capacity within a Sprint. These limitations were managed by lengthening the planning horizon to a term-level backlog, by keeping the Sprints short so that slippage could be detected early, and by treating the adviser as the primary Product Owner for purposes of prioritization and acceptance.

H2|2.2 Enterprise Architecture Concepts
P|Enterprise architecture is the practice of describing the structure of an enterprise in terms of its business processes, information, applications, and technology, and of using that description to guide planned change. Its purpose is to align investments in information technology with the objectives of the organization, to make dependencies visible, and to prevent the accumulation of isolated systems that duplicate data and resist integration.
P|The Open Group (2018) defines enterprise architecture as a coherent set of principles, methods, and models that describe the structure and behavior of an enterprise, and it prescribes the Architecture Development Method for producing and governing architecture across successive phases, from the preliminary phase and the vision of the architecture through the business, information systems, and technology architectures to the planning of migration and the governance of implementation.
P|Several frameworks formalize this practice. TOGAF provides an architecture development method and a governance model. ArchiMate provides a modeling notation for visualizing architecture. The Zachman Framework supplies a classification scheme for architecture artifacts. The Federal Enterprise Architecture Framework provides a reference structure for government enterprises. These frameworks differ in emphasis, but they share the same underlying idea, that an enterprise architecture connects business strategy to capabilities, processes, information, applications, and technology.
P|Enterprise architecture is directly relevant to this project because the Learning and Development module does not exist in isolation. It is one capability within an integrated human resource management system, and its boundaries, data ownership, and interfaces are architectural decisions rather than implementation details. Three consequences followed from treating the work architecturally. First, the module owns its own data: learning content, enrolment, progress, assessment, and certification are stored in tables maintained by the module, while employee identity and organizational structure remain the responsibility of the employee management function. Second, cross-module communication is expressed as an interface rather than as shared access to tables, so that the module can publish learning outcomes without exposing its internal data model. Third, the module was designed against a target architecture in which learning data serves several consumers, including performance evaluation, compliance monitoring, attendance, and workforce analytics, and its outputs are therefore shaped to those needs from the outset rather than retrofitted afterwards.
P|The alternative to an architectural approach, developing each function as an independent application, was considered and rejected. It would have produced repeated implementations of employee data, inconsistent definitions of completion, and a training record that could not be reconciled across functions, which is precisely the fragmentation that this project was undertaken to resolve.

H2|2.3 Microservice Architecture
P|Microservice architecture is an approach in which a system is composed of small, independently deployable services, each owning a bounded context and communicating with the others through well-defined interfaces, typically over the network. Fowler (2014) describes it as a style in which the application is decomposed into services that are independently deployable and organized around business capabilities, with centralized governance replaced by decentralized governance and with infrastructure automation emphasized. Newman (2021) elaborates that the defining characteristics are independent deployability, a high degree of autonomy, and the alignment of a service's boundary with a business domain rather than a technical layer.
P|The style offers recognized benefits. Because a service owns a bounded context, changes to its internal implementation do not require coordinated changes in other services. Because services are independently deployable, a service can be released without redeploying the whole system. Because each service can be scaled separately, resources can be directed to the components that require them. The style also carries costs. Distributed communication introduces latency and partial-failure conditions that do not arise within a single process, transactions that span services cannot rely on a single database transaction, and the operational burden of deployment, monitoring, and versioning increases substantially.
P|For a system of the scale of this capstone project, a full microservice deployment would impose the costs of the style without delivering its principal benefits, because the system runs on a single institutional server and its data is transactionally interdependent. The project therefore adopted a modular architecture that applies the principles of the style where they are justified. The module is a cohesive unit that owns a bounded context, namely learning and development, expressed by its own tables and classes. Communication with the rest of the HRMS occurs only through a published interface of REST endpoints rather than through direct access to the module's tables, so the module's internal structure remains free to change. Each endpoint performs a single business function, returns a self-contained representation, and is authenticated on every call. The result preserves the property that matters most for this project, which is a stable and explicit boundary between learning data and the consumers of that data, while avoiding the operational complexity that a distributed deployment would introduce in a single-server environment.

H2|2.4 DevOps and CI CD
P|DevOps is a set of practices that unites software development and information technology operations in order to shorten the time required to deliver changes while maintaining reliability. Kim, Humble, Debois, and Willis (2016) describe its central principles as the flow of work from development to operations, amplified feedback from operations to development, and a culture of continuous experimentation and learning. Continuous integration is the practice of integrating work into a shared repository frequently, verifying each integration with an automated build and test. Continuous delivery extends this by keeping the software in a state that can be released at any time, and continuous deployment takes the further step of releasing automatically.
P|A conventional delivery pipeline comprises source control, an automated build, automated testing at several levels, artifact management, deployment to successive environments, and monitoring. Not all of these stages are proportionate to a capstone project, but several of the underlying practices were applied because they address problems that arise even at small scale. Version control was used throughout the project so that changes to the code base were recorded, attributable, and reversible, and development proceeded on a dedicated branch so that the module could be developed without disrupting the rest of the HRMS. The database was treated as a deployable artifact: the schema of the module is maintained as a versioned script that can be applied repeatedly and safely, so that a development, test, or demonstration environment can be brought to the required state without manual reconstruction and without destroying existing rows.
P|Repeatability was extended to maintenance operations. Scheduled utilities generate competency and engagement snapshots and produce database backups, which converts a manual and easily neglected task into a routine one. Verification was performed at the level of the application and of the database, including checks that the deployed schema matches the intended schema and that repeated deployment does not alter data.
P|The delivery practice of the project applied these stages at the level of effort that the module warranted. Changes were integrated into the shared repository continuously and were verified against the running system before they were accepted, so that the module remained in a state that could be demonstrated at any point in its development. Because the deployment target is an institutional server administered separately from the development environment, each release was prepared as a verified set of application files together with the schema script, and the schema script was applied as part of the release so that the database advanced in step with the application. The result is a delivery process in which the code base, the database structure, and the maintenance operations of the module are all reproducible from the repository.

H2|2.5 Relevant Studies and Research
P|Knowles (1984) advanced the concept of andragogy, the art and science of helping adults learn, and identified assumptions that distinguish adult learners from children: adults are self-directed, they bring accumulated experience to learning, they are ready to learn when they perceive a need, they are oriented toward problem-centered rather than subject-centered learning, and they require a reason for learning. These assumptions support design decisions that a learning system is expected to accommodate, namely that content should be organized in units that a learner can approach independently, that learners should be able to see progress and resume at will, and that learning should be connected to the learner's own development needs.
P|The assessment of learning and development is commonly framed by the four levels described by Kirkpatrick (1994): reaction, learning, behavior, and results. The first level concerns how learners respond to the training, the second concerns the knowledge and skills acquired, the third concerns changes in on-the-job behavior, and the fourth concerns organizational results. This model influenced the module's inclusion of course evaluations, which capture reaction and structured feedback, and of assessment records, which capture learning. It also clarifies a limitation: the third and fourth levels depend on data held by performance management and by the institution, which is the reason the module publishes learning outcomes to those functions rather than attempting to evaluate them itself.
P|Instructional design practice, particularly the Analysis, Design, Development, Implementation, and Evaluation model, describes learning solutions as the product of a systematic process in which needs are analyzed, objectives are specified, materials are developed, delivery is carried out, and results are evaluated. Noe (2017) applies this tradition to the workplace in his treatment of employee training and development, emphasizing that training design must follow from an identified need and that transfer of training depends on the alignment of the learning intervention with the requirements of the job. These ideas are reflected in the module's competency features: skills form a shared vocabulary between learning content, training programs, and learner records; skill snapshots preserve a learner's competency standing over time; and training requests originate from an identified gap rather than from an unstructured catalog request.
P|The effectiveness of technology-supported learning has been investigated extensively. Sitzmann, Kraiger, Stewart, and Wisher (2006) conducted a meta-analysis of studies comparing web-based instruction with classroom instruction and reported that web-based instruction was on average as effective as classroom instruction, and that it was more effective when the two were combined or when learners were given control over the pace and sequence of instruction. This finding supports the module's provision of self-paced study with learner control over progression, combined with scheduled instruction in the form of video conferences and programs rather than as a replacement for it.
P|Interoperability in e-learning has been shaped by standards. The Sharable Content Object Reference Model issued under the Advanced Distributed Learning initiative defined a common format and runtime for exchanging learning content and tracking learner activity, and the Experience API later provided a more flexible statement-based model for recording learning experiences across systems. These standards address a problem that this project encounters directly, since learning content and learning records must be exchanged between the learning module and other institutional functions. The exchange of learning content and learning records between the module and the other functions of the HRMS is carried out through the module's REST interface, whose representations were designed in accordance with the principle these standards establish, namely that a completion record must be associated with an identifiable learner, an identifiable activity, and an identifiable point in time. The data model of the module reflects that principle in its enrolment, progress, and certification records, so that every completion the module reports can be traced to the learner and to the content concerned.
P|In the Philippine context, the Commission on Higher Education (2020) issued guidelines on the implementation of flexible learning, directing higher education institutions to adopt delivery modes that respond to the needs and circumstances of learners, including the use of technology-mediated instruction. This direction is significant for a study of this kind because it establishes that learning delivery is expected to be flexible, that learning may occur outside a fixed classroom schedule, and that institutions require systems capable of recording learning that takes place across varying modalities. The institution in this study is subject to the same expectations, which strengthens the case for a module that supports self-paced learning, assessment, and scheduled virtual instruction within a single record.
P|Taken together, the literature supports the design that was implemented. Adult learning theory justifies learner-directed progression and visible progress. Evaluation theory justifies the collection of learner feedback and the separation of reaction and learning data from organizational results. Workplace training research justifies the linkage of learning to identified needs through competencies. Evidence on technology-supported learning justifies the combination of self-paced and scheduled instruction. Interoperability standards and national policy establish that learning records must be accurate, attributable, and exchangeable. The module's design reflects each of these conclusions, while deliberately leaving the assessment of higher-level training impact to the HRMS functions that hold the relevant data.

H2|2.6 Integration of Information Systems in Enterprise Environments
P|As organizations accumulate separate applications for separate functions, the problem of integration becomes unavoidable. Hohpe and Woolf (2003) describe the recurring patterns of enterprise integration and the principal topologies through which it is achieved. Point-to-point integration connects applications directly to one another and is simple when few applications are involved, but the number of connections grows rapidly with the number of participants and each connection must be maintained independently. Hub-and-spoke integration routes exchanges through a central broker, which reduces the number of connections at the cost of introducing a component on which all exchanges depend. Message-based and event-driven integration decouples the producer of information from its consumers in time, allowing consumers to react when they are able without requiring the producer to wait.
P|Modern integration of information systems is dominated by network interfaces. Fielding (2000) established the representational state transfer architectural style, which describes the properties that make a distributed interface uniform, stateless, cacheable, and layered, and which underlies the design of mainstream web services. In practice, integration of enterprise applications is achieved through interfaces that expose data as representations, that carry authentication on every request, that return explicit status indications, and that are versioned so that consumers are not broken by changes.
P|Several principles recur in practice. Data should have a single owner, so that one component is authoritative for a given entity and other components refer to it rather than maintaining a copy. Interfaces should be explicit and narrow, exposing only what consumers require rather than the internal structure of the producing system. Authentication should be verifiable on every call, and access should be limited to the operations a consumer is entitled to perform. Exchanges should be recorded, so that the movement of data between systems can be reconstructed when results are questioned. Exchanges should be tolerant of repetition, so that a retried request does not create duplicate effects. Finally, the schema of the integrated data should be allowed to evolve without breaking consumers, which is achieved by additive change and by explicit versioning.
P|The Learning and Development module applies these principles in its relationship with the rest of the HRMS. It is the authoritative owner of learning content, enrolment, progress, assessment, certification, and competency data, and it does not duplicate the employee, account, role, or departmental records owned by other functions. It exposes a narrow interface consisting of four inbound and five outbound endpoints, in which inbound endpoints deliver data that other functions own and outbound endpoints publish learning outcomes that other functions consume. Every call is authenticated by an API key associated with the calling integration, and every call is recorded in an integration log together with its endpoint, direction, and outcome, so that the exchange can be audited. Consumers receive representations rather than direct access to the module's tables, which keeps the internal data model free to change. The interface is deliberately unidirectional in each direction: the module does not attempt to coordinate a distributed transaction with its consumers, and it expects consumer functions to reconcile the learning outcomes they receive against their own records.
P|This approach is consistent with the architectural position taken in the earlier sections. A single owner for each category of data, an explicit interface at the boundary of each bounded context, verifiable authentication on every exchange, and a recorded history of exchanges together allow the learning module to participate in an integrated human resource management system without either absorbing the responsibilities of the other functions or exposing its own internal structure to them. Because each accepted call is recorded with its integration, endpoint, direction, and outcome, the institution can determine at any time which learning outcomes have been published to which function and when, which makes the movement of learning data verifiable rather than assumed.

CHAP|CHAPTER 3|METHODOLOGY AND PROJECT MANAGEMENT
P|This chapter describes the methodology and project management approach used in the design and development of the Learning and Development module. It presents the application of the Agile Scrum framework, the roles and responsibilities of the project team, the sprint cycle and its ceremonies, the artifacts that were maintained, the approach adopted for integrating the module with the other components of the HRMS, the application of microservice principles within the module, and the use of the TOGAF architecture domains in describing the system.

H2|3.1 Agile Scrum Methodology in the Project
P|The project employed the Agile Scrum methodology as its software development approach. Development proceeded in a sequence of short iterations of fixed duration, each producing a working increment of the module that could be demonstrated and reviewed. The scope of the project was maintained as a prioritized product backlog, and the contents of each iteration were drawn from that backlog and refined as the requirements of the institution became clearer.
P|The choice of Scrum followed from the nature of the project. The module contains several distinct capabilities whose requirements interact, including content authoring, enrolment, progress tracking, assessment, certification, competency management, scheduled training, and integration. These capabilities could not be specified completely in advance, because the behavior of one capability constrains the design of the others. Progress tracking, for example, cannot be specified independently of the structure of the content that is being tracked, and certification cannot be specified independently of the completion rules that trigger it. Scrum allowed the design to be settled incrementally, with each iteration validating the assumptions on which the next depended.
P|The methodology was applied as follows. Requirements were gathered from the learning and development practice of the institution, from the existing HRMS data model, and from the operational constraints of the institution's processes, and were recorded in the product backlog as items expressed as features and user stories. Each iteration began with the selection of a prioritized subset of the backlog, which became the sprint backlog together with the sprint goal. Development was carried out within the iteration, with the work reviewed at the end of the iteration against a definition of done, and the results demonstrated before proceeding to the next iteration. The backlog was reprioritized between iterations, and items discovered during development, such as the need for a competency snapshot mechanism or for moderation of reported content, were added and scheduled.
P|The project used a modest tool set appropriate to its scale. Source code was maintained under version control in a shared repository, and development of the module proceeded on a dedicated branch so that the rest of the HRMS remained unaffected. Development and testing were performed on a local stack comprising the Apache web server, the PHP interpreter, and the MariaDB database server, with a database administration interface used for inspection and for verifying data changes. The web browser's development tools were used for interface verification and for tracing requests and responses, and command-line utilities were used to exercise the integration endpoints and to verify database changes directly.
P|The definition of done applied to backlog items required that the feature behaved as specified against the actual database schema, that the module's existing functionality continued to operate, that database access used prepared statements, that access to the feature was restricted to the roles entitled to it, and that any change to the database schema was reflected in the versioned schema script.
P|Progress toward the sprint goal was tracked against the sprint backlog and confirmed by demonstrating the increment at the end of each iteration, which kept the assessment of progress tied to working capability rather than to reported activity. Each iteration closed with a review of how the team had worked, in which the practices that assisted the iteration were retained and those that did not were changed for the following sprint. Together with the definition of done and the versioned schema script, this gave the team a defined basis on which the completeness of an item was judged before the next sprint began.

H2|3.2 Roles and Responsibilities
P|The Scrum framework defines three accountabilities: the Product Owner, the Scrum Master, and the Developers. In an academic capstone project these accountabilities are shared differently from those in a commercial team, because the researchers perform development work while the adviser and the institutional stakeholders perform the function of the Product Owner.
T|Scrum Roles and Corresponding Responsibilities in the Project|Role;Accountability in the Project|Product Owner;Represented by the capstone adviser and the institutional stakeholders from the Human Resource Management Office. Defined and prioritized the requirements, clarified the learning and development practice of the institution, and accepted or rejected the increments presented at the end of each iteration.|Scrum Master;Assigned to a member of the research team who facilitated sprint planning and review, monitored progress against the sprint goal, identified impediments, and ensured that the agreed practices were followed.|Development Team;The five members of the research team, collectively accountable for the analysis, design, implementation, database, testing, and documentation of the module. Work was distributed by capability, and each member was accountable for the completeness of the increment they contributed to.|Technical Adviser;Provided guidance on architecture, database design, and integration, and reviewed the technical artifacts produced by the team.|Institutional Stakeholders;Provided domain knowledge on training records, competency requirements, and reporting needs, and validated that the module reflected actual institutional practice.
P|Within the system, the following user roles are defined. The role determines which portal a signed-in user is directed to and which pages and actions are available.
T|User Roles of the Learning and Development Module|Role;Primary Responsibilities in the System|System Administrator;Manages user access, system settings, calendar, notifications, reports and analytics, moderation, archive, audit log, and certificate extension and revocation.|Learning and Development Administrator;Manages the learning catalog, learning paths, programs, and reports, and maintains the settings and reference data of the module.|Instructor or Trainer;Authors courses, modules, lessons, quizzes, and evaluations, manages enrolments and programs, schedules video conferences, records grades, issues certificates, and monitors learner progress and skill gaps.|Learner or Employee;Browses the catalog, enrolls in courses, studies content, takes quizzes and evaluations, submits training requests, maintains notes and bookmarks, and views progress, grades, certificates, and calendar activities.|

H2|3.3 Sprint Cycle
P|The project was executed in two-week iterations called sprints. Each sprint followed the ceremonies prescribed by Scrum: sprint planning, at which the sprint goal was set and the sprint backlog was selected; the daily scrum, a short daily coordination meeting at which progress toward the sprint goal and any impediment were stated; the sprint review, at which the increment was demonstrated and feedback was obtained; and the sprint retrospective, at which the team examined how it had worked and what it would change in the next iteration.
P|Sprint planning produced a sprint goal expressed in terms of working capability rather than of activity. Each sprint was constrained to a scope that could be completed within the iteration with the capacity available to the team, given that the researchers carried concurrent academic responsibilities. When a sprint goal could not be met, the remaining work was returned to the product backlog and reprioritized rather than carried forward unexamined.
P|Sprint reviews were conducted as demonstrations of the working module against the actual database, so that the increment was verified at the point of demonstration rather than reported. Feedback obtained at review was recorded in the backlog. Items that affected the data model were given particular attention, because a change to the structure of content, enrolment, or assessment propagated to the pages, classes, and reports that depended on them.
P|The sequence of sprints and the increment delivered by each is summarized below. The sequence reflects the dependency order of the module: foundational content structure precedes enrolment, enrolment precedes progress and assessment, progress and assessment precede certification and reporting, and the integration interface follows once the data it publishes is stable.
T|Sprint Plan and Incremental Deliverables of the Project|Sprint;Sprint Goal;Increment Delivered|Sprint 1;Establish the module skeleton and a trustworthy identity and access foundation;Module entry point with role resolution, page controller with role page maps, page shell and navigation, sign-in integration, and the database connection layer using prepared statements|Sprint 2;Define and author the learning content model;Course, module, lesson, and quiz management, the hierarchical content pages, rich-text lesson authoring with file attachment, quiz question banks with answer options, and course evaluations|Sprint 3;Enable learners to enroll and progress;Catalog with filtering, enrolment and invitation handling, learner study views, item-level progress marking, and automatic computation of course completion|Sprint 4;Assess learning and record results;Quiz delivery and attempts, quiz sessions and recorded answers, automatic scoring, grade recording, and learner review of attempts and feedback|Sprint 5;Recognize completion and manage competencies;Certificate templates, issuance on completion, validity monitoring, extension and revocation, the shared skill master list, skill tagging of content, and competency snapshots|Sprint 6;Extend learning beyond self-paced courses;Training programs with attached skills, video conferences with attendance and reminders, learning paths combining mixed content types, training requests, and the shared calendar|Sprint 7;Support communication, governance, and accountability;In-application notifications, notes, comments, messages, announcements, ratings, bookmarks and favorites, content moderation, the archive, and the audit log|Sprint 8;Expose learning outcomes to the rest of the HRMS;The four inbound and five outbound REST endpoints, API-key authentication, request validation, and integration logging|Sprint 9;Provide visibility and reporting;Progress and participation reporting, the gradebook, competency and engagement reporting, audit review, and administrator and instructor dashboards|Sprint 10;Harden, verify, and document the module;End-to-end verification of the principal flows, role-restriction checks, schema deployment verification, corrective fixes, the versioned schema script, and the technical documentation of the module|
P|Each sprint concluded with an increment that was usable in its own right. This property was important for the project, because it allowed the module to be demonstrated at any point in its development, and it reduced the risk that a defect introduced early would remain undetected until late in the schedule.

H2|3.4 Scrum Artifacts
P|Four artifacts were maintained throughout the project.
P|The Product Backlog was the authoritative list of everything the module required, expressed as features and user stories and ordered by value and dependency. It was the record of scope and was reprioritized between sprints as the requirements of the institution became clearer and as items were discovered during development.
P|The Sprint Backlog was the subset of the product backlog selected for a sprint, together with the plan for delivering it and the sprint goal. It was the team's working plan for the iteration and was the basis for the daily coordination meeting.
P|The Increment was the sum of the completed work at the end of a sprint, and it was required to satisfy the definition of done. Because each increment was a working capability rather than a set of components, the increment was verifiable by demonstration against the actual database.
P|The Definition of Done was maintained as a short, explicit statement of the conditions a backlog item had to satisfy. Applied consistently, it is the reason the module has a uniform approach to database access, to role restrictions, and to schema maintenance, since a feature that bypassed prepared statements, that was reachable by a role not entitled to it, or that altered the schema without updating the versioned script was not considered complete.
P|Two supporting artifacts were maintained alongside the Scrum artifacts. The database schema script records the structure of the fifty-seven tables owned by the module and is the deployable representation of the data architecture, so that an environment can be prepared or brought into agreement with the intended schema without manual reconstruction. The technical documentation records the structure of the module, its data model, its flows, and its integration interface, and was maintained as the module was developed rather than assembled at the end.

H2|3.5 Integration Approach for Information Systems
P|The Learning and Development module communicates with the other modules of the HRMS through a REST interface implemented in PHP and exchanged as JSON. The interface is organized by direction. Endpoints under the inbound group receive data that another function owns and the learning module requires, and endpoints under the outbound group publish data that the learning module owns and another function consumes.
P|The approach taken to the design of this interface follows the integration principles described in Chapter 2. Each category of data has a single owner. The interface is explicit and narrow, exposing only the representations that consumers require rather than the internal structure of the module's tables. Every call is authenticated. Every call is recorded. The interface is tolerant of repetition in the sense that publishing a learning outcome states a fact about a learner and an activity rather than incrementing a counter, so that a repeated publication does not compound its effect.
P|Authentication is performed by API key. A key is supplied by the caller in the request header and is validated before any business logic executes. Each key is associated with the integration it belongs to, so that the origin of a call is known and the call can be attributed. Unauthenticated or invalid calls are refused before any data is read or written.
P|Every call that passes authentication is recorded. The record captures the integration involved, the endpoint that was called, the direction of the exchange, and the outcome, so that the movement of data between modules can be reconstructed after the fact. This is significant for the institution, because learning outcomes that feed performance evaluation or compliance monitoring may later be questioned, and the integration record allows the source and the timing of the exchange to be established.
P|The endpoints currently provided by the module are listed below.
T|Integration Endpoints of the Learning and Development Module|Direction;Endpoint;Purpose and Counterpart Function|Inbound;Employee profile synchronization;Receives employee and organizational data from the employee management function so that learners can be associated with their position, department, and reporting line.|Inbound;Appraisal data;Receives appraisal information from the performance management function to inform training recommendations and skill-gap identification.|Inbound;Job test result;Receives assessment results from the recruitment and onboarding function so that hiring assessments can inform development planning.|Inbound;Recognition eligibility;Receives recognition eligibility information so that learning achievements can be aligned with institutional recognition.|Outbound;Training completion;Publishes completion of courses and programs to the performance management function.|Outbound;Learning performance;Publishes grades, assessment scores, and competency proficiency to the performance management function.|Outbound;Compliance training log;Publishes records of completed compliance-related training and their dates to the compliance and legal function.|Outbound;Attendance data;Publishes attendance at scheduled learning sessions to the time and attendance function.|Outbound;Workforce analytics;Publishes competency, participation, and gap information, together with reports, to the workforce analytics function.
P|The interface was verified by exercising each endpoint with authenticated and unauthenticated requests, by confirming that unauthenticated requests are refused, by confirming that accepted requests are recorded, and by confirming that the data published by an outbound endpoint agrees with the corresponding record in the module's own database. The representations returned by the endpoints are versioned and additive, so that a consumer depends on the fields it requires while the module remains free to extend a representation without interrupting the integrations that already call it.

H2|3.6 Microservice Architecture
P|As discussed in Chapter 2, the project adopted a modular architecture that applies the principles of microservice design where they are justified, rather than implementing a distributed deployment. The module is a cohesive unit that owns the learning and development bounded context, expressed by its own tables, its own classes, and its own pages, and it interacts with the remainder of the HRMS exclusively through its published interface of REST endpoints.
P|The correspondence between the principles of the style and their realization in the module is summarized below. Each principle is stated together with the manner in which it is applied in the module.
T|Application of Microservice Principles within the Module|Principle;Application in the Module|Bounded context;Learning and development is expressed as a single capability with its own tables, classes, and pages. Employee, account, role, and departmental data remain owned by the employee management function and are referenced rather than duplicated.|Independent deployability;The module is developed on a dedicated version-control branch and deployed as a unit. Because it does not share code or schema with the other functions, a change to the module does not require coordinated change elsewhere. The module is delivered as a self-contained set of application files together with its schema script, so that it can be released without redeploying the other functions of the HRMS.|Service autonomy;Each class in the module owns a single domain concern, such as courses, enrolment, progress, assessment, certification, competency, or programs. Pages coordinate classes rather than implementing business rules directly.|Alignment to business capability;The structure of the module follows the learning and development activities of the institution rather than the technical layers of the application, so the mapping from institutional process to system component is direct.|Interface-based communication;Other functions obtain learning data only through the published REST endpoints, never through direct access to the module's tables. This keeps the internal data model free to change.|Explicit contracts;Each endpoint performs a single business function, validates its input, requires authentication, and returns a self-contained representation with an explicit outcome. Consumers depend on the representation rather than on the tables behind it.|Independent data ownership;The module is the authoritative owner of content, enrolment, progress, assessment, certification, and competency data. Integration is achieved by publishing and receiving representations rather than by sharing a schema.|Decentralized evolution;Schema changes are applied by a non-destructive deployment script that creates missing tables, adds missing columns, and updates changed definitions without dropping tables, columns, indexes, constraints, or data. Consumers are therefore not disrupted by change in the producing system.|Observability;Every integration call is recorded with its integration, endpoint, direction, and outcome, which allows the exchange of data between modules to be traced.|Transactional integrity;Each exchange completes within the boundary of the function that owns the data involved, and every published outcome states a fact about a learner and an activity rather than incrementing a counter, so that a repeated publication does not compound its effect and the records of a consumer can be reconciled against the module's own.
P|This arrangement gave the project the practical benefit that the learning module could be developed, altered, and verified without disturbing the other components of the HRMS, while still participating in the integrated system. The module is independently structured and independently releasable, its data is owned exclusively by the module, and every exchange with another function passes through a versioned representation rather than a shared table. That boundary is what allows each function of the HRMS to be maintained and improved on its own schedule without compromising the integrity of the system as a whole.

H2|3.7 Introduction to TOGAF and the Four Architecture Domains
P|The Open Group Architecture Framework provides a method for developing and governing an enterprise architecture. Its central component is the Architecture Development Method, a cyclic process that proceeds from the preliminary phase, in which architecture capability and principles are established, through the vision of the architecture, and then through successive phases that address business architecture, information systems architecture, technology architecture, opportunities and solutions, migration planning, implementation governance, and architecture change management. Requirements management is performed continuously across the cycle rather than in a single phase.
P|The method organizes the description of an architecture into four domains. Business architecture describes the organization, its capabilities, its processes, its roles, and its governance. Data architecture describes the structure and ownership of the information the enterprise requires. Application architecture describes the applications, services, and interfaces through which the business is supported. Technology architecture describes the infrastructure, platforms, and standards on which the applications and data depend.
P|These domains were used in this project as the organizing structure for describing the module, and the resulting descriptions are summarized below. The application of the domains was deliberate rather than incidental: describing the business architecture clarified which processes the module was accountable for, describing the data architecture established the ownership boundary between learning data and employee data, describing the application architecture produced the module's class structure and its integration interface, and describing the technology architecture fixed the runtime and deployment standards against which the module was developed and verified.
T|Mapping of the TOGAF Architecture Domains to Project Artifacts|Domain;Description in This Project|Business Architecture;The learning and development processes of the institution: content authoring and publication, enrolment, learning delivery in self-paced and scheduled modes, assessment, certification, competency management, development planning through training requests, and reporting. The roles that perform these processes are the system administrator, the learning and development administrator, the instructor, and the learner. The governing requirement is that the learning record be accurate, attributable, and auditable.|Data Architecture;Fifty-seven tables under the ld_ prefix, organized into the content hierarchy, enrolment and progress, assessment and certification, learning paths and programs, competencies, collaboration, and administration and integration. Each table group has a single owner within the module. Employee, account, role, and departmental records are referenced from the surrounding HRMS and are not duplicated. The schema is maintained as a deployable, non-destructive migration script.|Application Architecture;Three role-based portals that resolve to their permitted page sets, the domain classes that implement the module's business rules, and the integration interface of four inbound and five outbound REST endpoints. The module's internal dependencies follow the content hierarchy and the achievement chain, from content through enrolment, progress, and assessment to certification and reporting.|Technology Architecture;PHP 8.2.12 on the Apache web server, MariaDB 10.4.32 with the InnoDB engine, the XAMPP distribution as the development and deployment stack, PDO for database access, a responsive CSS design system with vanilla JavaScript for presentation, Font Awesome for iconography, and Quill for rich-text authoring. Version control is used for source and for the schema script. No front-end framework, charting library, mailing library, or document generation library is used.
P|Several architecture principles guided the design and were applied consistently across the domains. Information should have a single owner, so that each category of data is authoritative in one place. Responsibilities should be separated, so that pages coordinate behavior while classes implement it, and so that learning data and employee data remain distinct. Access should be least-privileged, so that a role is granted only the pages it requires and an integration is granted only the endpoints it calls. Integration should proceed through explicit contracts rather than through shared tables. Change should be additive and non-destructive, so that the schema script may be applied repeatedly without loss of data. These principles are the basis on which the design decisions described in this chapter were made, and they are the standard against which the module's further development should be evaluated.

H2|3.8 Verification of the System
P|Verification was performed at four levels. Unit-level verification exercised the domain classes with known inputs and confirmed that enrolment, progress computation, scoring, and certificate issuance produced the expected results. Functional verification exercised the pages of each portal against the actual database and confirmed that content could be created, edited, and reloaded, that learners could enroll and progress, that assessments could be taken and scored, and that certificates were issued on completion. Integration verification exercised the REST endpoints with authenticated and unauthenticated requests and confirmed that unauthenticated requests are refused, that accepted requests are recorded, and that published data agrees with the module's own records. Data-level verification confirmed that the schema script creates missing tables, adds missing columns and indexes, applies changed definitions, and leaves existing data and objects outside the module's scope untouched when applied repeatedly.
P|Access verification was performed for each portal by attempting to reach pages outside the role's permitted set and confirming that access is refused and that the pages do not appear in navigation. The detailed results of verification, together with the presentation and analysis of the module's performance, are reported in the results and discussion chapter of the capstone project.

H1|REFERENCES
P|Advanced Distributed Learning Initiative. (2004). Sharable Content Object Reference Model (SCORM) 2004 (4th ed.). Alexandria, VA: Advanced Distributed Learning.
P|Advanced Distributed Learning Initiative. (2013). Experience API (xAPI) specification, version 1.0.1. Alexandria, VA: Advanced Distributed Learning.
P|Alqahtani, M., & Alotaibi, M. (2020). The role of learning theories in the design of learning and development systems. Journal of Educational Technology and Society.
P|Bandura, A. (1977). Social learning theory. Englewood Cliffs, NJ: Prentice Hall.
P|Bass, L., Clements, P., & Kazman, R. (2012). Software architecture in practice (3rd ed.). Upper Saddle River, NJ: Addison-Wesley.
P|Commission on Higher Education. (2020). Guidelines on the implementation of flexible learning (CHED Memorandum Order No. 4, series of 2020). Quezon City: Commission on Higher Education.
P|Fielding, R. T. (2000). Architectural styles and the design of network-based software architectures (Doctoral dissertation). University of California, Irvine.
P|Fowler, M. (2014). Microservices: A definition of this new architectural term. Retrieved from martinfowler.com.
P|Hohpe, G., & Woolf, B. (2003). Enterprise integration patterns: Designing, building, and deploying messaging solutions. Boston, MA: Addison-Wesley.
P|Kim, G., Humble, J., Debois, P., & Willis, J. (2016). The DevOps handbook. Portland, OR: IT Revolution Press.
P|Kirkpatrick, D. L. (1994). Evaluating training programs: The four levels. San Francisco, CA: Berrett-Koehler.
P|Knowles, M. S. (1984). Andragogy in action: Applying modern principles of adult learning. San Francisco, CA: Jossey-Bass.
P|Kolb, D. A. (1984). Experiential learning: Experience as the source of learning and development. Englewood Cliffs, NJ: Prentice Hall.
P|Newman, S. (2021). Building microservices: Designing fine-grained systems (2nd ed.). Sebastopol, CA: O'Reilly Media.
P|Noe, R. A. (2017). Employee training and development (7th ed.). New York, NY: McGraw-Hill Education.
P|Organisation for Economic Co-operation and Development. (2023). OECD digital education outlook 2023: Towards an effective digital education ecosystem. Paris: OECD Publishing.
P|Piaget, J. (1952). The origins of intelligence in children. New York, NY: International Universities Press.
P|Schwaber, K., & Sutherland, J. (2020). The Scrum Guide: The definitive guide to Scrum: The rules of the game. Retrieved from scrumguides.org.
P|Sitzmann, T., Kraiger, K., Stewart, D., & Wisher, R. (2006). The comparative effectiveness of web-based and classroom instruction: A meta-analysis. Personnel Psychology, 59(3), 623-664.
P|The Open Group. (2018). The TOGAF standard, version 9.2. Reading, United Kingdom: The Open Group.
P|UNESCO. (2021). Reimagining our futures together: A new social contract for education. Paris: UNESCO.
P|Vygotsky, L. S. (1978). Mind in society: The development of higher psychological processes. Cambridge, MA: Harvard University Press.

H1|APPENDICES
H2|Appendix A - Learning and Development Database Schema
P|The complete structure of the fifty-seven tables owned by the module is maintained as a versioned script. The script creates tables that are absent, adds columns and indexes that are missing, and applies changed definitions, without dropping tables, columns, indexes, or constraints and without altering existing data. It is therefore safe to apply repeatedly against a development, testing, or demonstration database.
H2|Appendix B - Schema Export and Deployment Utility
P|A utility is maintained alongside the module that reads the current structure of the learning and development tables from the database and regenerates the schema script described in Appendix A, so that the deployable representation of the schema can be brought up to date whenever the schema changes.
H2|Appendix C - System Architecture Diagram
P|The system architecture diagram presents the layers of the module, the actors that use it, the domain capabilities it provides, the data it owns, its security controls, and its integration with the other modules of the HRMS. It is included below.
FIG|ld-system-architecture.png|System Architecture of the Learning and Development Module
H2|Appendix D - Cross-Module Integration Specifications
P|Separate specifications document the interface between the learning module and each counterpart function of the HRMS, including the employee management, performance management, compliance and legal, time and attendance, workforce analytics, recruitment and onboarding, engagement and relations, and exit and knowledge transfer functions. Each specification records the purpose of the exchange, the direction, the data involved, and the counterpart tables.
"""


# --------------------------------------------------------------------------
# build
# --------------------------------------------------------------------------
def main():
    body = render(MARKUP)
    doc = document_xml(body)
    with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as z:
        z.writestr("[Content_Types].xml", CONTENT_TYPES)
        z.writestr("_rels/.rels", REL_ROOT)
        z.writestr("docProps/core.xml", CORE)
        z.writestr("docProps/app.xml", APP)
        z.writestr("word/document.xml", doc)
        z.writestr("word/styles.xml", STYLES)
        z.writestr("word/settings.xml", SETTINGS)
        z.writestr("word/footer1.xml", FOOTER_PAGE)
        z.writestr("word/footer2.xml", FOOTER_EMPTY)
        z.writestr("word/_rels/document.xml.rels", DOC_RELS)
        z.write(ARCH_PNG, "word/media/architecture.png")
    print("wrote %s (%d bytes)" % (OUT, os.path.getsize(OUT)))


if __name__ == "__main__":
    main()
