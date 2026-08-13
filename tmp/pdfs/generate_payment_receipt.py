from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_RIGHT, TA_CENTER
from reportlab.lib.units import inch
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle

output_path = r"output/pdf/foremost-consulting-payment-confirmation-lawal-toheeb.pdf"

navy = colors.HexColor('#123B5D')
blue = colors.HexColor('#1F6E9C')
light_blue = colors.HexColor('#EAF4FA')
slate = colors.HexColor('#4B5563')
line = colors.HexColor('#D7E1E8')

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='Brand', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=18, leading=22, textColor=navy))
styles.add(ParagraphStyle(name='Subtitle', parent=styles['Normal'], fontName='Helvetica', fontSize=9, leading=13, textColor=slate))
styles.add(ParagraphStyle(name='ReceiptTitle', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=24, leading=29, textColor=navy, alignment=TA_RIGHT))
styles.add(ParagraphStyle(name='Meta', parent=styles['Normal'], fontName='Helvetica', fontSize=9, leading=14, textColor=slate, alignment=TA_RIGHT))
styles.add(ParagraphStyle(name='Section', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=9, leading=13, textColor=blue, spaceAfter=5))
styles.add(ParagraphStyle(name='Body', parent=styles['Normal'], fontName='Helvetica', fontSize=10, leading=15, textColor=colors.HexColor('#1F2937')))
styles.add(ParagraphStyle(name='BodyBold', parent=styles['Body'], fontName='Helvetica-Bold'))
styles.add(ParagraphStyle(name='Amount', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=20, leading=24, textColor=navy, alignment=TA_RIGHT))
styles.add(ParagraphStyle(name='TableHeader', parent=styles['BodyBold'], textColor=colors.white))
styles.add(ParagraphStyle(name='Footer', parent=styles['Normal'], fontName='Helvetica', fontSize=8, leading=11, textColor=slate, alignment=TA_CENTER))

def p(text, style='Body'):
    return Paragraph(text, styles[style])

def line_rule(width=0.5):
    table = Table([['']], colWidths=[7.05 * inch], rowHeights=[0.01 * inch])
    table.setStyle(TableStyle([('LINEABOVE', (0, 0), (-1, -1), width, line)]))
    return table

def footer(canvas, doc):
    canvas.saveState()
    canvas.setStrokeColor(line)
    canvas.setLineWidth(0.5)
    canvas.line(doc.leftMargin, 0.65 * inch, A4[0] - doc.rightMargin, 0.65 * inch)
    canvas.setFont('Helvetica', 8)
    canvas.setFillColor(slate)
    canvas.drawCentredString(A4[0] / 2, 0.42 * inch, 'Foremost Consulting - Payment confirmation for formostconsult.com')
    canvas.restoreState()

doc = SimpleDocTemplate(
    output_path,
    pagesize=A4,
    leftMargin=0.75 * inch,
    rightMargin=0.75 * inch,
    topMargin=0.72 * inch,
    bottomMargin=0.9 * inch,
    title='Payment Confirmation - Foremost Consulting',
    author='Foremost Consulting',
)

story = []
header = Table([
    [
        [p('FOREMOST CONSULTING', 'Brand'), p('Domain, hosting, and web services', 'Subtitle')],
        [p('PAYMENT CONFIRMATION', 'ReceiptTitle'), p('Payment Ref. FC-20260805-001<br/>Issued: August 5, 2026', 'Meta')],
    ]
], colWidths=[3.55 * inch, 3.50 * inch])
header.setStyle(TableStyle([
    ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 0),
    ('RIGHTPADDING', (0, 0), (-1, -1), 0),
    ('TOPPADDING', (0, 0), (-1, -1), 0),
    ('BOTTOMPADDING', (0, 0), (-1, -1), 0),
]))
story += [header, Spacer(1, 0.28 * inch), line_rule(), Spacer(1, 0.25 * inch)]

received = Table([
    [p('PAID TO', 'Section'), p('PAYMENT STATUS', 'Section')],
    [p('Lawal Toheeb', 'BodyBold'), p('PAID', 'BodyBold')],
    [p('Payment for services provided.', 'Body'), p('August 5, 2026', 'Body')],
], colWidths=[4.55 * inch, 2.50 * inch])
received.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, -1), light_blue),
    ('BOX', (0, 0), (-1, -1), 0.5, line),
    ('INNERGRID', (0, 0), (-1, -1), 0.25, line),
    ('VALIGN', (0, 0), (-1, -1), 'TOP'),
    ('LEFTPADDING', (0, 0), (-1, -1), 14),
    ('RIGHTPADDING', (0, 0), (-1, -1), 14),
    ('TOPPADDING', (0, 0), (-1, -1), 9),
    ('BOTTOMPADDING', (0, 0), (-1, -1), 9),
    ('BACKGROUND', (1, 1), (1, 1), colors.HexColor('#FFF3D4')),
]))
story += [received, Spacer(1, 0.35 * inch)]

story.append(p('PAYMENT DETAILS', 'Section'))
items = [
    [p('Description', 'TableHeader'), p('Amount', 'TableHeader')],
    [p('Domain name and hosting service for <b>formostconsult.com</b>', 'Body'), p('USD 70.00', 'BodyBold')],
]
table = Table(items, colWidths=[5.25 * inch, 1.80 * inch])
table.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, 0), navy),
    ('TEXTCOLOR', (0, 0), (-1, 0), colors.white),
    ('ALIGN', (1, 0), (1, -1), 'RIGHT'),
    ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ('GRID', (0, 0), (-1, -1), 0.5, line),
    ('LEFTPADDING', (0, 0), (-1, -1), 12),
    ('RIGHTPADDING', (0, 0), (-1, -1), 12),
    ('TOPPADDING', (0, 0), (-1, -1), 11),
    ('BOTTOMPADDING', (0, 0), (-1, -1), 11),
]))
story += [table, Spacer(1, 0.02 * inch)]

total = Table([[p('PAYMENT AMOUNT', 'BodyBold'), p('USD 70.00', 'Amount')]], colWidths=[4.85 * inch, 2.20 * inch])
total.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#F7FAFC')),
    ('BOX', (0, 0), (-1, -1), 1.0, navy),
    ('VALIGN', (0, 0), (-1, -1), 'MIDDLE'),
    ('LEFTPADDING', (0, 0), (-1, -1), 12),
    ('RIGHTPADDING', (0, 0), (-1, -1), 12),
    ('TOPPADDING', (0, 0), (-1, -1), 10),
    ('BOTTOMPADDING', (0, 0), (-1, -1), 10),
]))
story += [total, Spacer(1, 0.38 * inch)]

note = Table([[p('<b>Confirmation:</b> Foremost Consulting confirms payment of USD 70.00 to Lawal Toheeb for domain name and hosting service for formostconsult.com.', 'Body')]], colWidths=[7.05 * inch])
note.setStyle(TableStyle([
    ('BACKGROUND', (0, 0), (-1, -1), colors.HexColor('#F8FAFC')),
    ('BOX', (0, 0), (-1, -1), 0.5, line),
    ('LEFTPADDING', (0, 0), (-1, -1), 13),
    ('RIGHTPADDING', (0, 0), (-1, -1), 13),
    ('TOPPADDING', (0, 0), (-1, -1), 12),
    ('BOTTOMPADDING', (0, 0), (-1, -1), 12),
]))
story += [note, Spacer(1, 0.32 * inch), p('This payment confirmation was issued by Foremost Consulting.', 'Footer')]

doc.build(story, onFirstPage=footer, onLaterPages=footer)
print(output_path)