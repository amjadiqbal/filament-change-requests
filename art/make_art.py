"""Generate the Change Requests art set (SVG) in the shared amjadiqbal template.

Usage: python3 -I make_art.py   (writes SVGs next to this file; render.sh makes the PNG/JPG)
No brown: warm colour (rose) appears only as solid strokes/text, never as a fill or translucent wash.
"""
import os

HERE = os.path.dirname(os.path.abspath(__file__))
FONT = "Helvetica, Arial, sans-serif"
PKG = "amjadiqbal/filament-change-requests"
TITLE = "Change Requests"
TAGLINE = "Review record edits before they are saved"
CHIPS = ["field-by-field diff", "approve · reject · rollback", "Filament 4 · 5"]

THEMES = {
    "light": dict(bg=("#F8FAFC", "#EEF2F7"), title="#0F172A", accent="#4F46E5", chip_fill="#FFFFFF",
                  chip_stroke="#E2E8F0", chip_text="#334155", card="#FFFFFF", card_stroke="#E2E8F0",
                  muted="#64748B", body="#0F172A", old="#E11D48", new="#15803D", ok="#16A34A", pkg="#94A3B8"),
    "dark": dict(bg=("#0B1220", "#111C33"), title="#F8FAFC", accent="#A5B4FC", chip_fill="#111C33",
                 chip_stroke="#273449", chip_text="#CBD5E1", card="#111C33", card_stroke="#273449",
                 muted="#94A3B8", body="#F8FAFC", old="#FB7185", new="#4ADE80", ok="#16A34A", pkg="#64748B"),
}


def icon_body():
    """The 512x512 mark: a change-request card with a removed row, an added row and an approval check."""
    return """
    <rect width="512" height="512" rx="112" fill="url(#iconbg)"/>
    <rect x="88" y="104" width="300" height="300" rx="30" fill="#1E293B" stroke="#334155" stroke-width="6"/>
    <circle cx="136" cy="158" r="20" fill="#818CF8"/>
    <rect x="170" y="148" width="160" height="20" rx="10" fill="#475569"/>
    <line x1="112" y1="204" x2="364" y2="204" stroke="#334155" stroke-width="6"/>
    <line x1="118" y1="250" x2="150" y2="250" stroke="#FB7185" stroke-width="12" stroke-linecap="round"/>
    <rect x="170" y="238" width="190" height="24" rx="12" fill="#475569"/>
    <line x1="164" y1="250" x2="366" y2="250" stroke="#FB7185" stroke-width="7" stroke-linecap="round"/>
    <line x1="118" y1="320" x2="150" y2="320" stroke="#4ADE80" stroke-width="12" stroke-linecap="round"/>
    <line x1="134" y1="304" x2="134" y2="336" stroke="#4ADE80" stroke-width="12" stroke-linecap="round"/>
    <rect x="170" y="308" width="150" height="24" rx="12" fill="#4ADE80"/>
    <circle cx="384" cy="392" r="78" fill="#16A34A" stroke="#0F172A" stroke-width="16"/>
    <path d="M348 394 L374 420 L422 368" fill="none" stroke="#FFFFFF" stroke-width="20" stroke-linecap="round" stroke-linejoin="round"/>
"""


def defs(t):
    return f"""  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{t['bg'][0]}"/>
      <stop offset="100%" stop-color="{t['bg'][1]}"/>
    </linearGradient>
    <linearGradient id="iconbg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0F172A"/>
      <stop offset="100%" stop-color="#132844"/>
    </linearGradient>
  </defs>"""


def chips(t, x, y, size=15, h=32, gap=10, anchor_center=False):
    widths = [round(len(c) * size * 0.56 + 34) for c in CHIPS]
    if anchor_center:
        x = x - (sum(widths) + gap * (len(widths) - 1)) / 2
    out = [f'  <g font-family="{FONT}" font-size="{size}" font-weight="600" fill="{t["chip_text"]}" text-anchor="middle">']
    for c, w in zip(CHIPS, widths):
        out.append(f'<g transform="translate({x:.0f},{y})"><rect width="{w}" height="{h}" rx="{h/2}" fill="{t["chip_fill"]}" stroke="{t["chip_stroke"]}"/>'
                   f'<text x="{w/2}" y="{h*0.66:.2f}">{c.replace("&", "&amp;")}</text></g>')
        x += w + gap
    out.append("  </g>")
    return "\n".join(out)


def request_card(t, x, y, s=1.0):
    """A miniature change request: two field diffs plus Approve / Reject."""
    def p(v):
        return f"{v * s:.1f}"
    return f"""  <g transform="translate({x},{y}) scale({s})" font-family="{FONT}">
    <rect width="290" height="184" rx="16" fill="{t['card']}" stroke="{t['card_stroke']}" stroke-width="1.5"/>
    <text x="20" y="30" font-size="13" font-weight="600" fill="{t['muted']}">CHANGE REQUEST · Product #42</text>
    <text x="20" y="66" font-size="15" font-weight="600" fill="{t['muted']}">Price</text>
    <text x="96" y="66" font-size="17" font-weight="600" fill="{t['old']}">$120</text>
    <line x1="94" y1="60" x2="140" y2="60" stroke="{t['old']}" stroke-width="2"/>
    <text x="156" y="66" font-size="17" fill="{t['muted']}">→</text>
    <text x="184" y="66" font-size="17" font-weight="700" fill="{t['new']}">$95</text>
    <text x="20" y="100" font-size="15" font-weight="600" fill="{t['muted']}">Stock</text>
    <text x="96" y="100" font-size="17" font-weight="600" fill="{t['old']}">40</text>
    <line x1="94" y1="94" x2="118" y2="94" stroke="{t['old']}" stroke-width="2"/>
    <text x="156" y="100" font-size="17" fill="{t['muted']}">→</text>
    <text x="184" y="100" font-size="17" font-weight="700" fill="{t['new']}">55</text>
    <rect x="20" y="128" width="120" height="36" rx="10" fill="{t['ok']}"/>
    <text x="80" y="151" font-size="15" font-weight="700" fill="#FFFFFF" text-anchor="middle">Approve</text>
    <rect x="152" y="128" width="118" height="36" rx="10" fill="none" stroke="{t['old']}" stroke-width="2"/>
    <text x="211" y="151" font-size="15" font-weight="700" fill="{t['old']}" text-anchor="middle">Reject</text>
  </g>"""


def svg(w, h, body, t):
    return f'<svg width="{w}" height="{h}" viewBox="0 0 {w} {h}" xmlns="http://www.w3.org/2000/svg">\n{defs(t)}\n  <rect width="{w}" height="{h}" fill="url(#bg)"/>\n{body}\n</svg>\n'


def banner(name):
    t = THEMES[name]
    body = f"""  <g transform="translate(60,75) scale(0.29297)">{icon_body()}</g>
  <text x="235" y="148" font-family="{FONT}" font-size="64" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="238" y="190" font-family="{FONT}" font-size="23" font-weight="500" fill="{t['accent']}" letter-spacing="0.3">{TAGLINE}</text>
{chips(t, 238, 218)}
{request_card(t, 870, 58)}
  <text x="1170" y="280" text-anchor="end" font-family="{FONT}" font-size="15" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1200, 300, body, t)


def social():
    t = THEMES["dark"]
    body = f"""  <g transform="translate(490,50) scale(0.5859)">{icon_body()}</g>
  <text x="640" y="430" text-anchor="middle" font-family="{FONT}" font-size="76" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="640" y="482" text-anchor="middle" font-family="{FONT}" font-size="28" font-weight="500" fill="{t['accent']}">Edits wait for approval, with a field-by-field diff, in Filament</text>
{chips(t, 640, 530, size=18, h=40, gap=14, anchor_center=True)}
  <text x="1240" y="610" text-anchor="end" font-family="{FONT}" font-size="18" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1280, 640, body, t)


def directory():
    """16:9 listing image for filamentphp.com, authored at 1280x720 and rendered at 2x (2560x1440)."""
    t = THEMES["dark"]
    body = f"""  <g transform="translate(90,72) scale(0.27)">{icon_body()}</g>
  <text x="90" y="300" font-family="{FONT}" font-size="66" font-weight="700" fill="{t['title']}">{TITLE}</text>
  <text x="92" y="350" font-family="{FONT}" font-size="26" font-weight="500" fill="{t['accent']}">Review record edits before they are saved</text>
  <g font-family="{FONT}" font-size="21" fill="{t['chip_text']}">
    <text x="92" y="420">• Edits are held as a pending change request</text>
    <text x="92" y="458">• Approver sees old and new values, field by field</text>
    <text x="92" y="496">• Approve, reject or roll back in one click</text>
    <text x="92" y="534">• Built on cjmellor/approval · Filament 4 and 5</text>
  </g>
{request_card(t, 720, 210, s=1.65)}
  <text x="1190" y="680" text-anchor="end" font-family="{FONT}" font-size="18" fill="{t['pkg']}">{PKG}</text>"""
    return svg(1280, 720, body, t).replace('width="1280" height="720"', 'width="2560" height="1440"', 1)


def icon():
    return f'<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">\n{defs(THEMES["dark"])}\n<g>{icon_body()}</g>\n</svg>\n'


if __name__ == "__main__":
    files = {"icon.svg": icon(), "banner-light.svg": banner("light"), "banner-dark.svg": banner("dark"),
             "social-preview.svg": social(), "filament-directory.svg": directory()}
    for f, s in files.items():
        with open(os.path.join(HERE, f), "w") as fh:
            fh.write(s)
        print("wrote", f)
