# Ilustrações da demonstração "Ficha de lançamento": desenho, não foto.
# Tudo inventado; nenhum empreendimento real.
import random
W, H = 1600, 1000
def svg(corpo, defs=''):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {W} {H}" width="{W}" height="{H}" role="img"><defs>{defs}</defs>{corpo}</svg>'
def grad(i, c1, c2, c3=None):
    meio = f'<stop offset=".55" stop-color="{c3}"/>' if c3 else ''
    return f'<linearGradient id="{i}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{c1}"/>{meio}<stop offset="1" stop-color="{c2}"/></linearGradient>'
def torre(x, y, w, h, cor, vidro, aceso=None, seed=1, cols=None, sacada=True):
    r = random.Random(seed); cols = cols or max(3, w // 46); lin = h // 42
    o = [f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{cor}"/>',
         f'<rect x="{x}" y="{y}" width="{w*0.16:.0f}" height="{h}" fill="#000" opacity=".10"/>',
         f'<rect x="{x-8}" y="{y-14}" width="{w+16}" height="14" fill="{cor}"/>',
         f'<rect x="{x+w*0.35:.0f}" y="{y-46}" width="{w*0.3:.0f}" height="32" fill="{cor}" opacity=".85"/>']
    cw = (w - 24) / cols; ch = (h - 20) / lin
    for l in range(lin):
        for c in range(cols):
            f = vidro
            if aceso and r.random() < .42: f = aceso
            o.append(f'<rect x="{x+12+c*cw+4:.0f}" y="{y+12+l*ch+5:.0f}" width="{cw-8:.0f}" height="{ch-14:.0f}" rx="2" fill="{f}"/>')
        if sacada:
            o.append(f'<rect x="{x+6}" y="{y+12+l*ch+ch-8:.0f}" width="{w-12}" height="3" fill="#fff" opacity=".35"/>')
    return ''.join(o)
def arvore(x, y, s=1.0, cor='#2f6b4f'):
    return f'<rect x="{x-5*s:.0f}" y="{y-40*s:.0f}" width="{10*s:.0f}" height="{40*s:.0f}" fill="#5b4636"/><circle cx="{x}" cy="{y-70*s:.0f}" r="{46*s:.0f}" fill="{cor}"/><circle cx="{x-26*s:.0f}" cy="{y-50*s:.0f}" r="{30*s:.0f}" fill="{cor}" opacity=".9"/><circle cx="{x+28*s:.0f}" cy="{y-48*s:.0f}" r="{28*s:.0f}" fill="{cor}" opacity=".85"/>'
def chao(y, cor='#3d4a45', calcada='#cfc8bb'):
    return f'<rect x="0" y="{y}" width="{W}" height="{H-y}" fill="{cor}"/><rect x="0" y="{y}" width="{W}" height="26" fill="{calcada}"/>'
def nuvens(op=.7):
    return ''.join(f'<ellipse cx="{x}" cy="{y}" rx="{rx}" ry="{ry}" fill="#fff" opacity="{op}"/>' for x, y, rx, ry in [(260,170,150,34),(360,150,110,30),(1220,230,170,36),(1330,210,100,28)])
def horizonte(y, cor, seed=3):
    r = random.Random(seed); o = []; x = -20
    while x < W:
        w = r.randint(50, 130); h = r.randint(60, 240); o.append(f'<rect x="{x}" y="{y-h}" width="{w}" height="{h}" fill="{cor}"/>'); x += w + r.randint(2, 18)
    return ''.join(o)
P = {}
P['capa'] = svg(f'<rect width="{W}" height="{H}" fill="url(#c)"/><circle cx="1180" cy="560" r="150" fill="#ffd9a0" opacity=".9"/>' + horizonte(800, '#3b3560', 5) + torre(430, 190, 300, 610, '#e9e2d6', '#31506e', '#ffd98a', 11) + torre(800, 260, 270, 540, '#dcd3c4', '#2c4963', '#ffd98a', 12) + f'<rect x="380" y="760" width="760" height="40" fill="#b9ad9a"/>' + chao(800, '#2a2f3a', '#8f8a86') + ''.join(arvore(x, 800, s, '#27513f') for x, s in [(240,1.2),(1250,1.1),(1420,1.4),(110,.9)]), grad('c', '#1d2452', '#ffb07a', '#b0567d'))
P['fachada-1'] = svg(f'<rect width="{W}" height="{H}" fill="url(#c)"/>' + nuvens() + horizonte(790, '#b9c9d6', 8) + torre(380, 150, 330, 650, '#efe9de', '#6f9cc0', None, 21) + torre(820, 230, 300, 570, '#e3dbcd', '#6893b6', None, 22) + f'<rect x="330" y="740" width="840" height="60" fill="#c9bfae"/><rect x="690" y="700" width="150" height="100" fill="#8a7a66"/><rect x="712" y="720" width="106" height="80" fill="#3d5870"/>' + chao(800, '#55605a') + ''.join(arvore(x, 800, s) for x, s in [(200,1.3),(1290,1.2),(1460,1.0),(80,1.0)]), grad('c', '#7fb6e6', '#e8f2f9'))
P['fachada-2'] = svg(f'<rect width="{W}" height="{H}" fill="url(#c)"/>' + nuvens(.55) + torre(520, 40, 620, 780, '#efe9de', '#5f8db3', None, 31, cols=8) + f'<rect x="460" y="760" width="740" height="60" fill="#c9bfae"/>' + chao(820, '#55605a') + ''.join(arvore(x, 820, s) for x, s in [(260,1.7),(1360,1.6),(1500,1.1)]), grad('c', '#5ea3dc', '#dcecf7'))
P['fachada-3'] = svg(f'<rect width="{W}" height="{H}" fill="url(#c)"/>' + ''.join(f'<circle cx="{random.Random(i).randint(0,W)}" cy="{random.Random(i+99).randint(0,420)}" r="2" fill="#fff" opacity=".7"/>' for i in range(60)) + '<circle cx="1300" cy="170" r="54" fill="#f3efe0"/>' + horizonte(800, '#161a33', 9) + torre(400, 170, 320, 630, '#3b4263', '#1b2140', '#ffd27a', 41) + torre(820, 250, 290, 550, '#343a59', '#191e3a', '#ffd27a', 42) + chao(800, '#12152a', '#3a3d55') + ''.join(arvore(x, 800, s, '#16302a') for x, s in [(220,1.3),(1300,1.2),(1460,1.0)]), grad('c', '#070b24', '#2a3466'))
P['piscina'] = svg(f'<rect width="{W}" height="{H}" fill="url(#c)"/>' + nuvens(.6) + horizonte(520, '#bfd0dc', 4) + f'<rect x="0" y="520" width="{W}" height="480" fill="#d9cdb8"/><path d="M260 640 H1340 L1480 920 H120 Z" fill="#2fa7c9"/><path d="M260 640 H1340 L1352 664 H248 Z" fill="#1d7f9c"/>' + ''.join(f'<path d="M{300+i*130} {720+((i*37)%120)} q40 -16 80 0 t80 0" stroke="#fff" stroke-width="5" fill="none" opacity=".5"/>' for i in range(8)) + ''.join(f'<g transform="translate({x} 560)"><rect width="130" height="14" fill="#fff"/><rect x="92" y="-30" width="40" height="34" fill="#fff" transform="skewX(-18)"/><rect x="8" y="14" width="6" height="22" fill="#8a7a66"/><rect x="112" y="14" width="6" height="22" fill="#8a7a66"/></g>' for x in (320, 520, 980, 1180)) + '<g transform="translate(760 430)"><rect x="56" width="8" height="150" fill="#6b5b49"/><path d="M-60 20 Q60 -70 180 20 Z" fill="#e8743b"/></g>' + arvore(120, 560, 1.4) + arvore(1500, 560, 1.5), grad('c', '#7fc0ee', '#eef6fb'))
P['academia'] = svg(f'<rect width="{W}" height="{H}" fill="#e9e4da"/><rect y="700" width="{W}" height="300" fill="#6d5a47"/>' + ''.join(f'<rect x="{80+i*300}" y="120" width="270" height="520" fill="url(#c)"/><rect x="{80+i*300}" y="370" width="270" height="8" fill="#fff"/>' for i in range(5)) + ''.join(f'<g transform="translate({x} 560)"><rect x="0" y="120" width="260" height="22" rx="8" fill="#2a2f3a"/><rect x="200" y="-40" width="14" height="170" fill="#2a2f3a" transform="rotate(12 207 45)"/><rect x="170" y="-70" width="110" height="60" rx="8" fill="#3d4654"/><rect x="20" y="142" width="14" height="40" fill="#2a2f3a"/><rect x="226" y="142" width="14" height="40" fill="#2a2f3a"/></g>' for x in (140, 520, 900)) + '<g transform="translate(1290 600)"><rect x="-90" y="0" width="180" height="12" fill="#2a2f3a"/><circle cx="-100" cy="6" r="46" fill="#2a2f3a"/><circle cx="100" cy="6" r="46" fill="#2a2f3a"/><rect x="-10" y="12" width="20" height="150" fill="#3d4654"/></g>', grad('c', '#9ccbee', '#e3f0f8'))
def sala(extra, parede='#ece6da', piso='#b48d68'):
    return svg(f'<rect width="{W}" height="{H}" fill="{parede}"/><rect y="720" width="{W}" height="280" fill="{piso}"/><rect y="712" width="{W}" height="12" fill="#fff"/><rect x="980" y="130" width="480" height="430" fill="url(#c)"/><rect x="1214" y="130" width="10" height="430" fill="#fff"/><rect x="980" y="338" width="480" height="10" fill="#fff"/>' + extra, grad('c', '#8ec3ec', '#e6f2fa'))
P['decorado-1'] = sala('<rect x="160" y="540" width="640" height="210" rx="26" fill="#56708a"/><rect x="130" y="600" width="80" height="170" rx="22" fill="#4a627a"/><rect x="750" y="600" width="80" height="170" rx="22" fill="#4a627a"/><rect x="200" y="500" width="270" height="130" rx="24" fill="#6583a0"/><rect x="490" y="500" width="270" height="130" rx="24" fill="#6583a0"/><rect x="330" y="800" width="340" height="26" rx="6" fill="#3a2f26"/><rect x="350" y="826" width="14" height="60" fill="#3a2f26"/><rect x="636" y="826" width="14" height="60" fill="#3a2f26"/><rect x="200" y="200" width="250" height="180" fill="#e8743b" opacity=".85"/><rect x="480" y="200" width="150" height="180" fill="#2f6b4f" opacity=".8"/><rect x="880" y="380" width="8" height="340" fill="#3a2f26"/><path d="M840 380 h90 l-20 -90 h-50 Z" fill="#f5d48a"/>')
P['decorado-2'] = sala('<rect x="180" y="330" width="640" height="240" rx="18" fill="#8a6f55"/><rect x="150" y="560" width="700" height="190" rx="14" fill="#f4f0e8"/><rect x="150" y="640" width="700" height="130" rx="14" fill="#9db4c7"/><rect x="220" y="520" width="240" height="90" rx="30" fill="#fff"/><rect x="540" y="520" width="240" height="90" rx="30" fill="#fff"/><rect x="40" y="620" width="100" height="130" fill="#6d5a47"/><path d="M60 620 h60 l-12 -70 h-36 Z" fill="#f5d48a"/>', '#e6e9ee', '#c2a587')
P['decorado-3'] = sala('<rect x="100" y="560" width="760" height="190" fill="#f4f0e8"/><rect x="90" y="540" width="780" height="24" fill="#3a3f47"/>' + ''.join(f'<rect x="{112+i*186}" y="580" width="170" height="156" fill="none" stroke="#c9c2b4" stroke-width="4"/>' for i in range(4)) + '<rect x="100" y="150" width="760" height="170" fill="#56708a"/>' + ''.join(f'<rect x="{112+i*186}" y="162" width="170" height="146" fill="none" stroke="#fff" stroke-width="3" opacity=".5"/>' for i in range(4)) + '<rect x="420" y="500" width="150" height="40" rx="4" fill="#9aa3ad"/><rect x="640" y="430" width="10" height="110" fill="#9aa3ad"/><rect x="640" y="430" width="70" height="10" fill="#9aa3ad"/>', '#f1ede4', '#d8cfc0')
def rot(x, y, t, s=30, c='#2a2f3a', a='middle'):
    return f'<text x="{x}" y="{y}" font-family="Helvetica,Arial,sans-serif" font-size="{s}" fill="{c}" text-anchor="{a}">{t}</text>'
P['implantacao'] = svg(f'<rect width="{W}" height="{H}" fill="#eef1ea"/><rect x="0" y="0" width="{W}" height="110" fill="#c9cdd1"/>' + rot(800, 68, 'RUA DAS ACÁCIAS', 30, '#5a6068') + '<rect x="110" y="170" width="1380" height="740" rx="18" fill="#cfe3c4" stroke="#7d9a72" stroke-width="5"/><rect x="200" y="250" width="380" height="300" fill="#fff" stroke="#3d4654" stroke-width="6"/>' + rot(390, 410, 'TORRE A', 38) + '<rect x="1020" y="250" width="380" height="300" fill="#fff" stroke="#3d4654" stroke-width="6"/>' + rot(1210, 410, 'TORRE B', 38) + '<rect x="660" y="270" width="280" height="170" rx="40" fill="#7fcbe3" stroke="#2f8fb0" stroke-width="5"/>' + rot(800, 365, 'Piscina', 28) + '<rect x="640" y="480" width="320" height="110" fill="#e9dcc4" stroke="#b8a47e" stroke-width="4"/>' + rot(800, 545, 'Salão de festas', 26) + '<rect x="200" y="640" width="300" height="200" fill="#f3e3b3" stroke="#c7ad5b" stroke-width="4"/>' + rot(350, 750, 'Playground', 26) + '<rect x="1100" y="640" width="300" height="200" fill="#dfe3ea" stroke="#8d97a8" stroke-width="4"/>' + rot(1250, 750, 'Quadra', 26) + '<path d="M800 110 V250 M800 590 V910 M500 740 H1100" stroke="#d9cdb8" stroke-width="34" fill="none"/>' + ''.join(f'<circle cx="{x}" cy="{y}" r="30" fill="#4f8a63"/>' for x, y in [(160,220),(160,600),(160,860),(1440,220),(1440,600),(1440,860),(620,700),(980,700),(620,860),(980,860)]) + rot(800, 965, 'Implantação ilustrativa, sem escala', 24, '#5a6068'))
def planta(titulo, comodos, w, h):
    o = [f'<rect width="{W}" height="{H}" fill="#f6f4ee"/>', rot(800, 80, titulo, 40)]
    ox, oy = (W - w) // 2, 140
    o.append(f'<rect x="{ox}" y="{oy}" width="{w}" height="{h}" fill="#fff" stroke="#2a2f3a" stroke-width="12"/>')
    for (x, y, cw, ch, nome, area, cor) in comodos:
        o.append(f'<rect x="{ox+x}" y="{oy+y}" width="{cw}" height="{ch}" fill="{cor}" stroke="#2a2f3a" stroke-width="7"/>')
        o.append(rot(ox + x + cw / 2, oy + y + ch / 2 - 4, nome, 28)); o.append(rot(ox + x + cw / 2, oy + y + ch / 2 + 32, area, 22, '#5a6068'))
    o.append(rot(800, 960, 'Planta ilustrativa, medidas aproximadas', 24, '#5a6068'))
    return svg(''.join(o))
A, B_, C, D = '#fbf3e4', '#e8f1f7', '#eef5ea', '#f3ecf5'
P['planta-54'] = planta('54 m² · 2 dormitórios', [(0,0,520,400,'Sala','18 m²',A),(520,0,280,240,'Cozinha','8 m²',C),(520,240,280,160,'Serviço','3 m²',C),(0,400,330,320,'Dormitório 1','11 m²',B_),(330,400,270,320,'Dormitório 2','9 m²',B_),(600,400,200,320,'Banho','4 m²',D),(800,0,160,400,'Sacada','5 m²','#e9f3df')], 960, 720)
P['planta-78'] = planta('78 m² · 3 dormitórios, 1 suíte', [(0,0,560,380,'Sala','24 m²',A),(560,0,300,230,'Cozinha','10 m²',C),(560,230,300,150,'Serviço','4 m²',C),(860,0,260,380,'Sacada gourmet','9 m²','#e9f3df'),(0,380,330,340,'Suíte','13 m²',B_),(330,380,190,340,'Banho suíte','4 m²',D),(520,380,250,340,'Dormitório 2','9 m²',B_),(770,380,220,340,'Dormitório 3','8 m²',B_),(990,380,130,340,'Banho','3 m²',D)], 1120, 720)
def obra(andares, pele):
    o = [f'<rect width="{W}" height="{H}" fill="url(#c)"/>', nuvens(.6), horizonte(800, '#c5d2dc', 6)]
    for t, x in enumerate((420, 860)):
        for a in range(andares - t * 2):
            y = 760 - a * 44
            o.append(f'<rect x="{x}" y="{y}" width="320" height="8" fill="#8f8a86"/>')
            o += [f'<rect x="{x+c*78}" y="{y}" width="10" height="44" fill="#a8a29a"/>' for c in range(5)]
            if a < pele: o.append(f'<rect x="{x+10}" y="{y+8}" width="302" height="36" fill="#e9e2d6"/>' + ''.join(f'<rect x="{x+26+c*74}" y="{y+14}" width="46" height="24" fill="#6f9cc0"/>' for c in range(4)))
    o.append('<rect x="780" y="120" width="14" height="680" fill="#e8a13b"/><rect x="560" y="120" width="560" height="12" fill="#e8a13b"/><path d="M787 60 L600 126 M787 60 L1100 126" stroke="#e8a13b" stroke-width="5"/><rect x="1040" y="132" width="3" height="170" fill="#3a3f47"/><rect x="1024" y="300" width="36" height="26" fill="#3a3f47"/>')
    o.append(chao(800, '#8a7a66', '#b9ad9a')); o.append('<rect x="100" y="740" width="230" height="60" fill="#e8743b"/>' + rot(215, 780, 'OBRA', 30, '#fff'))
    return svg(''.join(o), grad('c', '#86bbe6', '#eaf3f9'))
P['obra-1'] = obra(9, 0); P['obra-2'] = obra(15, 8)
P['marca'] = f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 240" width="640" height="240" role="img"><rect width="640" height="240" fill="#fff"/><path d="M60 170 a70 70 0 0 1 140 0" fill="none" stroke="#e8743b" stroke-width="14"/><path d="M90 170 a40 40 0 0 1 80 0" fill="#f5b45a"/><rect x="40" y="170" width="180" height="10" fill="#1d2452"/><text x="250" y="140" font-family="Georgia,serif" font-size="78" fill="#1d2452">Aurora</text><text x="254" y="186" font-family="Helvetica,Arial,sans-serif" font-size="26" letter-spacing="8" fill="#5a6068">RESIDENCIAL</text></svg>'
(__import__('pathlib').Path(__file__).parent / 'img').mkdir(exist_ok=True)
for n, s in P.items(): open(__import__('pathlib').Path(__file__).parent / 'img' / f'{n}.svg', 'w').write(s)
print(len(P), 'imagens', sum(len(s) for s in P.values()) // 1024, 'KB')
