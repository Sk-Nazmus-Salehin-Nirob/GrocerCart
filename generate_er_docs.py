import sys
import os
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image, PageBreak, HRFlowable
)
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.graphics.shapes import Drawing, Rect, String, Line, Polygon, Group, Circle
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

def create_pdf(pdf_path):
    doc = SimpleDocTemplate(
        pdf_path,
        pagesize=letter,
        rightMargin=36,
        leftMargin=36,
        topMargin=36,
        bottomMargin=36
    )
    story = []
    styles = getSampleStyleSheet()

    # Custom styles
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Heading1'],
        fontName='Helvetica-Bold',
        fontSize=24,
        leading=28,
        textColor=colors.HexColor('#0F172A'),
        alignment=0,
        spaceAfter=4
    )
    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=11,
        leading=14,
        textColor=colors.HexColor('#475569'),
        spaceAfter=15
    )
    h2_style = ParagraphStyle(
        'SectionH2',
        parent=styles['Heading2'],
        fontName='Helvetica-Bold',
        fontSize=15,
        leading=18,
        textColor=colors.HexColor('#1E293B'),
        spaceBefore=14,
        spaceAfter=8
    )
    body_style = ParagraphStyle(
        'BodyTextCustom',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=10,
        leading=14,
        textColor=colors.HexColor('#334155'),
        spaceAfter=8
    )
    table_header_style = ParagraphStyle(
        'TableHeader',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=11,
        textColor=colors.white
    )
    table_cell_style = ParagraphStyle(
        'TableCell',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=12,
        textColor=colors.HexColor('#1E293B')
    )
    table_cell_bold = ParagraphStyle(
        'TableCellBold',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=12,
        textColor=colors.HexColor('#0F172A')
    )
    table_cell_code = ParagraphStyle(
        'TableCellCode',
        parent=styles['Normal'],
        fontName='Courier-Bold',
        fontSize=8.5,
        leading=11,
        textColor=colors.HexColor('#2563EB')
    )

    # Title Block
    story.append(Paragraph("GrocerCart &mdash; Entity-Relationship (ER) Diagram", title_style))
    story.append(Paragraph("Oracle XE 11g Database Architecture &amp; Data Model Documentation", subtitle_style))
    story.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor('#2563EB'), spaceAfter=15))

    # Overview Section
    story.append(Paragraph("1. System Overview &amp; Relational Model", h2_style))
    story.append(Paragraph(
        "GrocerCart is an e-commerce grocery management system built on Oracle XE 11g. "
        "The relational model consists of 5 primary entity sets (<b>Customers</b>, <b>Vendors</b>, <b>Products</b>, "
        "<b>Orders</b>, and <b>Delivery</b>) designed with full normalization, foreign key constraints, and PL/SQL triggers.",
        body_style
    ))

    # Visual ER Diagram Canvas (Drawing)
    story.append(Paragraph("2. Visual Entity-Relationship Diagram", h2_style))
    
    # 540 width drawing space
    d = Drawing(540, 240)

    # Drawing background
    d.add(Rect(0, 0, 540, 240, fillColor=colors.HexColor('#F8FAFC'), strokeColor=colors.HexColor('#E2E8F0'), strokeWidth=1, rx=8, ry=8))

    # Helper function to draw Entity Box
    def draw_entity(d, x, y, width, height, title, color_header, attributes):
        # Card container
        d.add(Rect(x, y, width, height, fillColor=colors.white, strokeColor=colors.HexColor('#CBD5E1'), strokeWidth=1.5, rx=6, ry=6))
        # Header bar
        d.add(Rect(x, y + height - 24, width, 24, fillColor=color_header, strokeColor=colors.transparent, rx=6, ry=6))
        # Square fix for bottom header corners
        d.add(Rect(x, y + height - 24, width, 8, fillColor=color_header, strokeColor=colors.transparent))
        # Entity title text
        d.add(String(x + 10, y + height - 16, title, fontName='Helvetica-Bold', fontSize=10, fillColor=colors.white))
        # Attributes
        curr_y = y + height - 38
        for attr, is_pk, is_fk in attributes:
            prefix = "[PK] " if is_pk else (" [FK]" if is_fk else "  • ")
            txt_color = colors.HexColor('#D97706') if is_pk else (colors.HexColor('#2563EB') if is_fk else colors.HexColor('#334155'))
            font = 'Helvetica-Bold' if (is_pk or is_fk) else 'Helvetica'
            d.add(String(x + 8, curr_y, f"{prefix}{attr}", fontName=font, fontSize=8, fillColor=txt_color))
            curr_y -= 13

    # Define Entities Layout
    # Top Row: Customers (left), Orders (center), Delivery (right)
    # Bottom Row: Vendors (left), Products (center)
    
    # Customers (x=20, y=130, w=120, h=95)
    draw_entity(d, 20, 130, 125, 95, "CUSTOMERS", colors.HexColor('#2563EB'), [
        ("customer_id", True, False),
        ("name", False, False),
        ("email", False, False),
        ("phone", False, False),
        ("loyalty_points", False, False),
        ("customer_tier", False, False)
    ])

    # Orders (x=210, y=130, w=125, h=95)
    draw_entity(d, 210, 130, 125, 95, "ORDERS", colors.HexColor('#EA580C'), [
        ("order_id", True, False),
        ("customer_id", False, True),
        ("order_date", False, False),
        ("total_amount", False, False),
        ("status", False, False),
        ("discount_applied", False, False)
    ])

    # Delivery (x=395, y=130, w=125, h=95)
    draw_entity(d, 395, 130, 125, 95, "DELIVERY", colors.HexColor('#DC2626'), [
        ("delivery_id", True, False),
        ("order_id", False, True),
        ("delivery_method", False, False),
        ("delivery_status", False, False),
        ("estimated_time", False, False)
    ])

    # Vendors (x=20, y=15, w=125, h=95)
    draw_entity(d, 20, 15, 125, 95, "VENDORS", colors.HexColor('#9333EA'), [
        ("vendor_id", True, False),
        ("vendor_name", False, False),
        ("contact_email", False, False),
        ("phone", False, False),
        ("location", False, False)
    ])

    # Products (x=210, y=15, w=125, h=95)
    draw_entity(d, 210, 15, 125, 95, "PRODUCTS", colors.HexColor('#16A34A'), [
        ("product_id", True, False),
        ("vendor_id", False, True),
        ("name", False, False),
        ("category", False, False),
        ("price", False, False),
        ("stock_status", False, False)
    ])

    # Relationship Lines & Labels
    # 1. Customers (1) ---> (0..N) Orders
    d.add(Line(145, 175, 210, 175, strokeColor=colors.HexColor('#2563EB'), strokeWidth=2))
    d.add(String(150, 180, "1", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))
    d.add(String(195, 180, "N", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))

    # 2. Orders (1) ---> (0..1) Delivery
    d.add(Line(335, 175, 395, 175, strokeColor=colors.HexColor('#EA580C'), strokeWidth=2))
    d.add(String(342, 180, "1", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))
    d.add(String(380, 180, "0..1", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))

    # 3. Vendors (1) ---> (0..N) Products
    d.add(Line(145, 60, 210, 60, strokeColor=colors.HexColor('#9333EA'), strokeWidth=2))
    d.add(String(150, 65, "1", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))
    d.add(String(195, 65, "N", fontName='Helvetica-Bold', fontSize=9, fillColor=colors.HexColor('#1E293B')))

    # Legend / Key in canvas (bottom-right: x=360, y=15)
    d.add(Rect(360, 15, 160, 95, fillColor=colors.HexColor('#F1F5F9'), strokeColor=colors.HexColor('#CBD5E1'), strokeWidth=1, rx=4, ry=4))
    d.add(String(370, 92, "RELATIONSHIP LEGEND", fontName='Helvetica-Bold', fontSize=8, fillColor=colors.HexColor('#0F172A')))
    d.add(String(370, 76, "• Customers  1 : N  Orders", fontName='Helvetica', fontSize=7.5, fillColor=colors.HexColor('#334155')))
    d.add(String(370, 62, "• Orders     1 : 0..1 Delivery", fontName='Helvetica', fontSize=7.5, fillColor=colors.HexColor('#334155')))
    d.add(String(370, 48, "• Vendors    1 : N  Products", fontName='Helvetica', fontSize=7.5, fillColor=colors.HexColor('#334155')))
    d.add(String(370, 32, "[PK] Primary Key  [FK] Foreign Key", fontName='Helvetica-Oblique', fontSize=7, fillColor=colors.HexColor('#64748B')))

    story.append(d)
    story.append(Spacer(1, 15))

    # Entity Dictionary Tables
    story.append(Paragraph("3. Detailed Entity Dictionary &amp; Schema", h2_style))

    entities_data = [
        ("CUSTOMERS", "Stores customer profile, contact, address, and loyalty rewards data.", [
            ("customer_id", "NUMBER", "PK", "Primary Key, auto-gen via seq_customers"),
            ("name", "VARCHAR2(100)", "NOT NULL", "Customer full name"),
            ("email", "VARCHAR2(150)", "UNIQUE", "Customer email address"),
            ("phone", "VARCHAR2(30)", "—", "Contact telephone number"),
            ("address", "VARCHAR2(500)", "—", "Physical delivery address"),
            ("preferences", "VARCHAR2(300)", "—", "Dietary & shopping preferences"),
            ("loyalty_points", "NUMBER", "DEFAULT 0", "Accumulated loyalty reward points"),
            ("customer_tier", "VARCHAR2(20)", "CHECK", "Bronze / Silver / Gold / Platinum")
        ]),
        ("VENDORS", "Stores suppliers and produce vendors offering inventory items.", [
            ("vendor_id", "NUMBER", "PK", "Primary Key, auto-gen via seq_vendors"),
            ("vendor_name", "VARCHAR2(150)", "NOT NULL", "Vendor business name"),
            ("contact_email", "VARCHAR2(150)", "—", "Vendor contact email"),
            ("phone", "VARCHAR2(30)", "—", "Vendor phone number"),
            ("location", "VARCHAR2(200)", "—", "Operating location / address")
        ]),
        ("PRODUCTS", "Stores catalog products linked to their supplying vendors.", [
            ("product_id", "NUMBER", "PK", "Primary Key, auto-gen via seq_products"),
            ("vendor_id", "NUMBER", "FK", "Foreign Key -> Vendors(vendor_id)"),
            ("name", "VARCHAR2(150)", "NOT NULL", "Product / Item name"),
            ("category", "VARCHAR2(100)", "—", "Category (Fruits, Dairy, Grains, etc.)"),
            ("price", "NUMBER(10,2)", "NOT NULL, CHECK", "Price ($), must be >= 0"),
            ("sustainability_tag", "VARCHAR2(100)", "—", "Eco tag (Organic, Fair-Trade, etc.)"),
            ("stock_status", "VARCHAR2(20)", "CHECK", "In Stock / Low Stock / Out of Stock")
        ]),
        ("ORDERS", "Stores customer orders with order total, status, and discount.", [
            ("order_id", "NUMBER", "PK", "Primary Key, auto-gen via seq_orders"),
            ("customer_id", "NUMBER", "FK", "Foreign Key -> Customers(customer_id)"),
            ("order_date", "DATE", "DEFAULT SYSDATE", "Date and time order placed"),
            ("total_amount", "NUMBER(10,2)", "DEFAULT 0", "Total purchase amount ($)"),
            ("status", "VARCHAR2(20)", "CHECK", "pending / processing / shipped / delivered / cancelled"),
            ("discount_applied", "NUMBER(10,2)", "DEFAULT 0, CHECK", "Discount applied ($), must be >= 0")
        ]),
        ("DELIVERY", "Stores delivery dispatch method, status, and estimated arrival date.", [
            ("delivery_id", "NUMBER", "PK", "Primary Key, auto-gen via seq_delivery"),
            ("order_id", "NUMBER", "FK, CASCADE", "Foreign Key -> Orders(order_id) ON DELETE CASCADE"),
            ("delivery_method", "VARCHAR2(100)", "—", "Standard / Express / Pickup / Same Day"),
            ("delivery_status", "VARCHAR2(20)", "CHECK", "scheduled / in-transit / delivered / failed / ready"),
            ("estimated_time", "DATE", "—", "Estimated arrival date/time")
        ])
    ]

    for ent_name, ent_desc, attrs in entities_data:
        story.append(Paragraph(f"<b>Table: {ent_name}</b> &mdash; <i>{ent_desc}</i>", ParagraphStyle('SubHeader', parent=styles['Normal'], fontName='Helvetica-Bold', fontSize=11, leading=14, textColor=colors.HexColor('#0F172A'), spaceBefore=8, spaceAfter=4)))
        
        t_data = [[
            Paragraph("Column Name", table_header_style),
            Paragraph("Data Type", table_header_style),
            Paragraph("Constraints", table_header_style),
            Paragraph("Description", table_header_style)
        ]]
        
        for col_name, data_type, constraint, desc in attrs:
            c_style = table_cell_code if constraint in ['PK', 'FK', 'FK, CASCADE'] else table_cell_bold
            t_data.append([
                Paragraph(col_name, c_style),
                Paragraph(data_type, table_cell_style),
                Paragraph(constraint, table_cell_style),
                Paragraph(desc, table_cell_style)
            ])
            
        t = Table(t_data, colWidths=[110, 95, 105, 230])
        t.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#1E293B')),
            ('ALIGN', (0,0), (-1,-1), 'LEFT'),
            ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
            ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E1')),
            ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#F8FAFC')]),
            ('TOPPADDING', (0,0), (-1,-1), 4),
            ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ]))
        story.append(t)
        story.append(Spacer(1, 8))

    # Relationships Table
    story.append(Paragraph("4. Relational Constraints &amp; Cardinalities", h2_style))
    rel_data = [
        [Paragraph("Parent Table", table_header_style), Paragraph("Child Table", table_header_style), Paragraph("Foreign Key", table_header_style), Paragraph("Cardinality", table_header_style), Paragraph("Delete Rule", table_header_style)],
        [Paragraph("Vendors", table_cell_bold), Paragraph("Products", table_cell_style), Paragraph("vendor_id", table_cell_code), Paragraph("1 : N (One-to-Many)", table_cell_style), Paragraph("RESTRICT", table_cell_style)],
        [Paragraph("Customers", table_cell_bold), Paragraph("Orders", table_cell_style), Paragraph("customer_id", table_cell_code), Paragraph("1 : N (One-to-Many)", table_cell_style), Paragraph("RESTRICT", table_cell_style)],
        [Paragraph("Orders", table_cell_bold), Paragraph("Delivery", table_cell_style), Paragraph("order_id", table_cell_code), Paragraph("1 : 0..1 (One-to-One)", table_cell_style), Paragraph("CASCADE", table_cell_style)],
    ]
    t_rel = Table(rel_data, colWidths=[100, 95, 110, 135, 100])
    t_rel.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#0F3460')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E1')),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#F8FAFC')]),
        ('TOPPADDING', (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
    ]))
    story.append(t_rel)

    doc.build(story)
    print(f"PDF generated successfully at {pdf_path}")

def create_docx(docx_path):
    doc = docx.Document()
    
    # Page Margins
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(0.75)
        section.bottom_margin = Inches(0.75)
        section.left_margin = Inches(0.75)
        section.right_margin = Inches(0.75)

    # Title
    p_title = doc.add_paragraph()
    run_title = p_title.add_run("GrocerCart — Entity-Relationship (ER) Diagram")
    run_title.font.name = 'Calibri'
    run_title.font.size = Pt(22)
    run_title.font.bold = True
    run_title.font.color.rgb = RGBColor(15, 23, 42) # #0F172A

    p_sub = doc.add_paragraph()
    run_sub = p_sub.add_run("Oracle XE 11g Database Architecture & Data Model Documentation")
    run_sub.font.name = 'Calibri'
    run_sub.font.size = Pt(11)
    run_sub.font.italic = True
    run_sub.font.color.rgb = RGBColor(71, 85, 105)

    doc.add_paragraph("─" * 65)

    # Section 1: Overview
    h1 = doc.add_heading("1. System Overview & Relational Architecture", level=1)
    p = doc.add_paragraph(
        "GrocerCart is an administrative web app built with PHP (OCI8) and Oracle XE 11g. "
        "The relational database schema models customers, vendor suppliers, grocery products, "
        "customer orders, and delivery dispatches. Data integrity is enforced using Primary Key sequences, "
        "Foreign Key constraints, and PL/SQL business triggers."
    )

    # Section 2: Entities & Dictionary
    doc.add_heading("2. Entity Dictionary Specification", level=1)

    entities = [
        ("CUSTOMERS Table", [
            ("customer_id", "NUMBER", "PRIMARY KEY", "Auto-increment ID (seq_customers)"),
            ("name", "VARCHAR2(100)", "NOT NULL", "Customer full name"),
            ("email", "VARCHAR2(150)", "UNIQUE", "Customer email address"),
            ("phone", "VARCHAR2(30)", "None", "Contact telephone number"),
            ("address", "VARCHAR2(500)", "None", "Delivery home address"),
            ("preferences", "VARCHAR2(300)", "None", "Dietary preferences (vegan, organic, etc.)"),
            ("loyalty_points", "NUMBER", "DEFAULT 0", "Loyalty points earned from delivered orders"),
            ("customer_tier", "VARCHAR2(20)", "CHECK (Bronze/Silver/Gold/Platinum)", "Calculated member tier")
        ]),
        ("VENDORS Table", [
            ("vendor_id", "NUMBER", "PRIMARY KEY", "Auto-increment ID (seq_vendors)"),
            ("vendor_name", "VARCHAR2(150)", "NOT NULL", "Supplier / Business Name"),
            ("contact_email", "VARCHAR2(150)", "None", "Vendor email address"),
            ("phone", "VARCHAR2(30)", "None", "Contact telephone number"),
            ("location", "VARCHAR2(200)", "None", "Operating location / city")
        ]),
        ("PRODUCTS Table", [
            ("product_id", "NUMBER", "PRIMARY KEY", "Auto-increment ID (seq_products)"),
            ("vendor_id", "NUMBER", "FOREIGN KEY -> Vendors", "Supplier vendor reference"),
            ("name", "VARCHAR2(150)", "NOT NULL", "Product / Grocery item name"),
            ("category", "VARCHAR2(100)", "None", "Product category (Fruits, Dairy, etc.)"),
            ("price", "NUMBER(10,2)", "NOT NULL, CHECK (price >= 0)", "Unit price in USD"),
            ("sustainability_tag", "VARCHAR2(100)", "None", "Eco tag (organic, fair-trade, etc.)"),
            ("stock_status", "VARCHAR2(20)", "CHECK (In Stock/Low Stock/Out of Stock)", "Inventory availability status")
        ]),
        ("ORDERS Table", [
            ("order_id", "NUMBER", "PRIMARY KEY", "Auto-increment ID (seq_orders)"),
            ("customer_id", "NUMBER", "FOREIGN KEY -> Customers", "Purchasing customer reference"),
            ("order_date", "DATE", "DEFAULT SYSDATE", "Timestamp when order was created"),
            ("total_amount", "NUMBER(10,2)", "DEFAULT 0", "Total order price in USD"),
            ("status", "VARCHAR2(20)", "CHECK (pending/processing/shipped/delivered/cancelled)", "Order status"),
            ("discount_applied", "NUMBER(10,2)", "DEFAULT 0, CHECK (discount >= 0)", "Discount dollar amount")
        ]),
        ("DELIVERY Table", [
            ("delivery_id", "NUMBER", "PRIMARY KEY", "Auto-increment ID (seq_delivery)"),
            ("order_id", "NUMBER", "FOREIGN KEY -> Orders ON DELETE CASCADE", "Associated order reference"),
            ("delivery_method", "VARCHAR2(100)", "None", "Standard / Express / Pickup / Same Day"),
            ("delivery_status", "VARCHAR2(20)", "CHECK (scheduled/in-transit/delivered/failed/ready)", "Dispatch progress"),
            ("estimated_time", "DATE", "None", "Estimated delivery timestamp")
        ])
    ]

    for ent_title, cols in entities:
        doc.add_heading(ent_title, level=2)
        table = doc.add_table(rows=1, cols=4)
        table.alignment = WD_TABLE_ALIGNMENT.CENTER
        
        hdr_cells = table.rows[0].cells
        hdr_titles = ["Column Name", "Data Type", "Constraint / Rule", "Description"]
        for i, title in enumerate(hdr_titles):
            hdr_cells[i].text = title
            hdr_cells[i].paragraphs[0].runs[0].font.bold = True
            hdr_cells[i].paragraphs[0].runs[0].font.color.rgb = RGBColor(255, 255, 255)
            # Set shading to navy
            shading = parse_xml(r'<w:shd {} w:fill="0F172A"/>'.format(nsdecls('w')))
            hdr_cells[i]._tc.get_or_add_tcPr().append(shading)

        for col_name, d_type, cons, desc in cols:
            row_cells = table.add_row().cells
            row_cells[0].text = col_name
            row_cells[0].paragraphs[0].runs[0].font.bold = True
            row_cells[1].text = d_type
            row_cells[2].text = cons
            row_cells[3].text = desc

        doc.add_paragraph() # Spacing

    # Section 3: Relationships
    doc.add_heading("3. Entity Relationship Matrix", level=1)
    rel_table = doc.add_table(rows=1, cols=5)
    rel_table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    r_hdr = rel_table.rows[0].cells
    r_titles = ["Parent Entity", "Child Entity", "Foreign Key", "Cardinality", "Delete Cascade"]
    for i, title in enumerate(r_titles):
        r_hdr[i].text = title
        r_hdr[i].paragraphs[0].runs[0].font.bold = True
        r_hdr[i].paragraphs[0].runs[0].font.color.rgb = RGBColor(255, 255, 255)
        shading = parse_xml(r'<w:shd {} w:fill="0F3460"/>'.format(nsdecls('w')))
        r_hdr[i]._tc.get_or_add_tcPr().append(shading)

    relationships = [
        ("Vendors", "Products", "vendor_id", "1 : N (One-to-Many)", "No (RESTRICT)"),
        ("Customers", "Orders", "customer_id", "1 : N (One-to-Many)", "No (RESTRICT)"),
        ("Orders", "Delivery", "order_id", "1 : 0..1 (One-to-One)", "Yes (CASCADE)")
    ]

    for p_ent, c_ent, fk, card, cas in relationships:
        r_cells = rel_table.add_row().cells
        r_cells[0].text = p_ent
        r_cells[0].paragraphs[0].runs[0].font.bold = True
        r_cells[1].text = c_ent
        r_cells[2].text = fk
        r_cells[3].text = card
        r_cells[4].text = cas

    doc.save(docx_path)
    print(f"DOCX generated successfully at {docx_path}")

def create_html(html_path):
    html_content = """<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrocerCart – ER Diagram Documentation</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
    .card-entity { background: #1e293b; border: 1px solid #334155; border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; }
    .card-entity:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.4); }
  </style>
</head>
<body class="p-6 md:p-12 max-w-6xl mx-auto">
  <!-- Header -->
  <div class="mb-10 border-b border-slate-700 pb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
      <h1 class="text-3xl font-extrabold text-white flex items-center gap-3">
        <i class="fas fa-sitemap text-green-400"></i> GrocerCart ER Diagram &amp; Data Model
      </h1>
      <p class="text-slate-400 mt-1">Oracle XE 11g Database Architecture &amp; Relational Schema Specification</p>
    </div>
    <div class="flex gap-3">
      <a href="GrocerCart_ER_Diagram.pdf" download class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
        <i class="fas fa-file-pdf"></i> Download PDF
      </a>
      <a href="GrocerCart_ER_Diagram.docx" download class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl text-sm transition flex items-center gap-2">
        <i class="fas fa-file-word"></i> Download DOCX
      </a>
    </div>
  </div>

  <!-- Interactive Visual ER Diagram Box -->
  <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 mb-12 shadow-2xl">
    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
      <i class="fas fa-diagram-project text-blue-400"></i> Visual Entity-Relationship Diagram
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- Customers -->
      <div class="card-entity overflow-hidden">
        <div class="bg-blue-600 px-4 py-2.5 font-bold text-sm tracking-wide text-white flex justify-between items-center">
          <span><i class="fas fa-users mr-1.5"></i> CUSTOMERS</span>
          <span class="text-xs bg-blue-800 px-2 py-0.5 rounded">Entity</span>
        </div>
        <div class="p-4 space-y-1.5 text-xs font-mono">
          <div class="text-amber-400 font-bold"><i class="fas fa-key text-amber-400 mr-1"></i> customer_id (PK)</div>
          <div class="text-slate-300">name [VARCHAR2(100)]</div>
          <div class="text-slate-300">email [VARCHAR2(150)]</div>
          <div class="text-slate-300">phone [VARCHAR2(30)]</div>
          <div class="text-slate-300">address [VARCHAR2(500)]</div>
          <div class="text-slate-300">preferences [VARCHAR2(300)]</div>
          <div class="text-slate-300">loyalty_points [NUMBER]</div>
          <div class="text-slate-300">customer_tier [VARCHAR2(20)]</div>
        </div>
      </div>

      <!-- Orders -->
      <div class="card-entity overflow-hidden">
        <div class="bg-orange-600 px-4 py-2.5 font-bold text-sm tracking-wide text-white flex justify-between items-center">
          <span><i class="fas fa-shopping-bag mr-1.5"></i> ORDERS</span>
          <span class="text-xs bg-orange-800 px-2 py-0.5 rounded">Entity</span>
        </div>
        <div class="p-4 space-y-1.5 text-xs font-mono">
          <div class="text-amber-400 font-bold"><i class="fas fa-key text-amber-400 mr-1"></i> order_id (PK)</div>
          <div class="text-blue-400 font-bold"><i class="fas fa-link mr-1"></i> customer_id (FK)</div>
          <div class="text-slate-300">order_date [DATE]</div>
          <div class="text-slate-300">total_amount [NUMBER(10,2)]</div>
          <div class="text-slate-300">status [VARCHAR2(20)]</div>
          <div class="text-slate-300">discount_applied [NUMBER(10,2)]</div>
        </div>
      </div>

      <!-- Delivery -->
      <div class="card-entity overflow-hidden">
        <div class="bg-red-600 px-4 py-2.5 font-bold text-sm tracking-wide text-white flex justify-between items-center">
          <span><i class="fas fa-shipping-fast mr-1.5"></i> DELIVERY</span>
          <span class="text-xs bg-red-800 px-2 py-0.5 rounded">Entity</span>
        </div>
        <div class="p-4 space-y-1.5 text-xs font-mono">
          <div class="text-amber-400 font-bold"><i class="fas fa-key text-amber-400 mr-1"></i> delivery_id (PK)</div>
          <div class="text-blue-400 font-bold"><i class="fas fa-link mr-1"></i> order_id (FK)</div>
          <div class="text-slate-300">delivery_method [VARCHAR2(100)]</div>
          <div class="text-slate-300">delivery_status [VARCHAR2(20)]</div>
          <div class="text-slate-300">estimated_time [DATE]</div>
        </div>
      </div>

      <!-- Vendors -->
      <div class="card-entity overflow-hidden">
        <div class="bg-purple-600 px-4 py-2.5 font-bold text-sm tracking-wide text-white flex justify-between items-center">
          <span><i class="fas fa-truck mr-1.5"></i> VENDORS</span>
          <span class="text-xs bg-purple-800 px-2 py-0.5 rounded">Entity</span>
        </div>
        <div class="p-4 space-y-1.5 text-xs font-mono">
          <div class="text-amber-400 font-bold"><i class="fas fa-key text-amber-400 mr-1"></i> vendor_id (PK)</div>
          <div class="text-slate-300">vendor_name [VARCHAR2(150)]</div>
          <div class="text-slate-300">contact_email [VARCHAR2(150)]</div>
          <div class="text-slate-300">phone [VARCHAR2(30)]</div>
          <div class="text-slate-300">location [VARCHAR2(200)]</div>
        </div>
      </div>

      <!-- Products -->
      <div class="card-entity overflow-hidden">
        <div class="bg-green-600 px-4 py-2.5 font-bold text-sm tracking-wide text-white flex justify-between items-center">
          <span><i class="fas fa-box mr-1.5"></i> PRODUCTS</span>
          <span class="text-xs bg-green-800 px-2 py-0.5 rounded">Entity</span>
        </div>
        <div class="p-4 space-y-1.5 text-xs font-mono">
          <div class="text-amber-400 font-bold"><i class="fas fa-key text-amber-400 mr-1"></i> product_id (PK)</div>
          <div class="text-blue-400 font-bold"><i class="fas fa-link mr-1"></i> vendor_id (FK)</div>
          <div class="text-slate-300">name [VARCHAR2(150)]</div>
          <div class="text-slate-300">category [VARCHAR2(100)]</div>
          <div class="text-slate-300">price [NUMBER(10,2)]</div>
          <div class="text-slate-300">sustainability_tag [VARCHAR2(100)]</div>
          <div class="text-slate-300">stock_status [VARCHAR2(20)]</div>
        </div>
      </div>

      <!-- Relationships Summary Card -->
      <div class="bg-slate-800 border border-slate-700 rounded-xl p-4 flex flex-col justify-center text-xs">
        <h3 class="font-bold text-slate-200 mb-2 border-b border-slate-700 pb-1 flex items-center gap-1.5">
          <i class="fas fa-link text-yellow-400"></i> Foreign Key Relationships
        </h3>
        <div class="space-y-2">
          <div><span class="text-purple-400 font-semibold">Vendors (1)</span> ➔ <span class="text-green-400 font-semibold">Products (N)</span></div>
          <p class="text-slate-400 text-[11px]">FK: Products.vendor_id = Vendors.vendor_id</p>
          
          <div><span class="text-blue-400 font-semibold">Customers (1)</span> ➔ <span class="text-orange-400 font-semibold">Orders (N)</span></div>
          <p class="text-slate-400 text-[11px]">FK: Orders.customer_id = Customers.customer_id</p>
          
          <div><span class="text-orange-400 font-semibold">Orders (1)</span> ➔ <span class="text-red-400 font-semibold">Delivery (0..1)</span></div>
          <p class="text-slate-400 text-[11px]">FK: Delivery.order_id = Orders.order_id (CASCADE)</p>
        </div>
      </div>

    </div>
  </div>

  <!-- Relational Tables Detail -->
  <div class="space-y-8">
    <h2 class="text-xl font-bold text-white border-b border-slate-700 pb-2">Database Table Specifications</h2>
    
    <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-lg">
      <table class="w-full text-sm text-left">
        <thead class="bg-slate-800 text-slate-300 uppercase text-xs">
          <tr>
            <th class="px-4 py-3">Parent Table</th>
            <th class="px-4 py-3">Child Table</th>
            <th class="px-4 py-3">Foreign Key Column</th>
            <th class="px-4 py-3">Cardinality</th>
            <th class="px-4 py-3">On Delete</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800 font-mono text-xs">
          <tr>
            <td class="px-4 py-3 text-purple-400 font-bold">Vendors</td>
            <td class="px-4 py-3 text-green-400 font-bold">Products</td>
            <td class="px-4 py-3 text-blue-300">vendor_id</td>
            <td class="px-4 py-3">1 : N (One-to-Many)</td>
            <td class="px-4 py-3 text-slate-400">RESTRICT</td>
          </tr>
          <tr>
            <td class="px-4 py-3 text-blue-400 font-bold">Customers</td>
            <td class="px-4 py-3 text-orange-400 font-bold">Orders</td>
            <td class="px-4 py-3 text-blue-300">customer_id</td>
            <td class="px-4 py-3">1 : N (One-to-Many)</td>
            <td class="px-4 py-3 text-slate-400">RESTRICT</td>
          </tr>
          <tr>
            <td class="px-4 py-3 text-orange-400 font-bold">Orders</td>
            <td class="px-4 py-3 text-red-400 font-bold">Delivery</td>
            <td class="px-4 py-3 text-blue-300">order_id</td>
            <td class="px-4 py-3">1 : 0..1 (One-to-One)</td>
            <td class="px-4 py-3 text-emerald-400">CASCADE</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="mt-16 text-center text-slate-500 text-xs border-t border-slate-800 pt-6">
    GrocerCart Oracle XE Database Model &middot; Generated for Academic / System Documentation
  </footer>
</body>
</html>
"""
    with open(html_path, 'w', encoding='utf-8') as f:
        f.write(html_content)
    print(f"HTML generated successfully at {html_path}")

if __name__ == '__main__':
    base_dir = r"c:\xampp\htdocs\GrocerCart"
    pdf_file = os.path.join(base_dir, "GrocerCart_ER_Diagram.pdf")
    docx_file = os.path.join(base_dir, "GrocerCart_ER_Diagram.docx")
    html_file = os.path.join(base_dir, "er_diagram.html")

    create_pdf(pdf_file)
    create_docx(docx_file)
    create_html(html_file)
