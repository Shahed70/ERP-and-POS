# -*- coding: utf-8 -*-
"""
Script to generate DEVELOPER_CUSTOMIZATION_GUIDE.docx
A comprehensive developer and customization guide in Bangla + English tech terms.
"""

import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_ALIGN_VERTICAL
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn

def set_cell_background(cell, fill_hex):
    """Set cell background color."""
    tcPr = cell._element.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def set_cell_margins(cell, top=100, bottom=100, left=150, right=150):
    """Set cell internal margins in twips."""
    tcPr = cell._element.get_or_add_tcPr()
    tcMar = parse_xml(
        f'<w:tcMar {nsdecls("w")}>'
        f'<w:top w:w="{top}" w:type="dxa"/>'
        f'<w:bottom w:w="{bottom}" w:type="dxa"/>'
        f'<w:left w:w="{left}" w:type="dxa"/>'
        f'<w:right w:w="{right}" w:type="dxa"/>'
        f'</w:tcMar>'
    )
    tcPr.append(tcMar)

def create_callout(doc, title, path, description):
    """Creates a stylized code / path callout box."""
    tbl = doc.add_table(rows=1, cols=1)
    tbl.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = tbl.cell(0, 0)
    set_cell_background(cell, "F8FAFC")
    set_cell_margins(cell, top=140, bottom=140, left=200, right=200)
    
    # Left accent border
    tcPr = cell._element.get_or_add_tcPr()
    borders = parse_xml(
        f'<w:tcBorders {nsdecls("w")}>'
        f'<w:top w:val="none"/>'
        f'<w:left w:val="single" w:sz="24" w:space="0" w:color="0D9488"/>'
        f'<w:bottom w:val="none"/>'
        f'<w:right w:val="none"/>'
        f'</w:tcBorders>'
    )
    tcPr.append(borders)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    
    run_t = p.add_run(f"📌 {title}: ")
    run_t.bold = True
    run_t.font.name = "Arial"
    run_t.font.size = Pt(10)
    run_t.font.color.rgb = RGBColor(15, 23, 42)
    
    run_p = p.add_run(f"{path}\n")
    run_p.bold = True
    run_p.font.name = "Consolas"
    run_p.font.size = Pt(9.5)
    run_p.font.color.rgb = RGBColor(13, 148, 136)
    
    run_d = p.add_run(description)
    run_d.font.name = "Arial"
    run_d.font.size = Pt(9.5)
    run_d.font.color.rgb = RGBColor(71, 85, 105)
    
    p_after = doc.add_paragraph()
    p_after.paragraph_format.space_before = Pt(0)
    p_after.paragraph_format.space_after = Pt(4)

def format_table(table, col_widths, headers, data):
    """Format a table with dark header and zebra striping."""
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    
    # Header row
    hdr_cells = table.rows[0].cells
    for i, h_text in enumerate(headers):
        hdr_cells[i].text = h_text
        set_cell_background(hdr_cells[i], "0F172A")
        set_cell_margins(hdr_cells[i], top=120, bottom=120, left=140, right=140)
        p = hdr_cells[i].paragraphs[0]
        p.alignment = WD_ALIGN_PARAGRAPH.LEFT
        for run in p.runs:
            run.bold = True
            run.font.name = "Arial"
            run.font.size = Pt(9.5)
            run.font.color.rgb = RGBColor(255, 255, 255)
            
    # Data rows
    for r_idx, row_data in enumerate(data):
        row = table.add_row()
        fill_color = "F8FAFC" if (r_idx % 2 == 1) else "FFFFFF"
        for c_idx, cell_value in enumerate(row_data):
            cell = row.cells[c_idx]
            cell.text = cell_value
            set_cell_background(cell, fill_color)
            set_cell_margins(cell, top=100, bottom=100, left=140, right=140)
            p = cell.paragraphs[0]
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            for run in p.runs:
                run.font.name = "Arial"
                run.font.size = Pt(9)
                run.font.color.rgb = RGBColor(30, 41, 59)
                
    # Apply widths
    for row in table.rows:
        for idx, width in enumerate(col_widths):
            row.cells[idx].width = Inches(width)

def build_guide():
    doc = docx.Document()
    
    # Set page margins
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(0.8)
        section.bottom_margin = Inches(0.8)
        section.left_margin = Inches(0.8)
        section.right_margin = Inches(0.8)
        
    # Main Title
    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p_title.paragraph_format.space_before = Pt(0)
    p_title.paragraph_format.space_after = Pt(2)
    run_title = p_title.add_run("AquaFeed ERP & POS — Developer & Customization Architecture Guide")
    run_title.font.name = "Arial"
    run_title.font.size = Pt(20)
    run_title.font.bold = True
    run_title.font.color.rgb = RGBColor(13, 148, 136) # Teal
    
    # Subtitle
    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.LEFT
    p_sub.paragraph_format.space_before = Pt(0)
    p_sub.paragraph_format.space_after = Pt(16)
    run_sub = p_sub.add_run("কোড স্ট্রাকচার, ফাইল লোকেশন ও কাস্টমাইজেশন নির্দেশিকা (Bangla Developer Guide)")
    run_sub.font.name = "Arial"
    run_sub.font.size = Pt(12)
    run_sub.font.bold = True
    run_sub.font.color.rgb = RGBColor(100, 116, 139)
    
    # Intro box
    p_intro = doc.add_paragraph()
    p_intro.paragraph_format.space_before = Pt(0)
    p_intro.paragraph_format.space_after = Pt(12)
    r_intro = p_intro.add_run(
        "এই ডকুমেন্টেশনটি বিশেষভাবে তৈরি করা হয়েছে যাতে আপনি ভবিষ্যতে সফটওয়্যারের যেকোনো ফিচার, ডিজাইন, "
        "ক্যালকুলেশন, মেমো প্রিন্ট ফরম্যাট বা ডাটাবেস ফিল্ড সহজেই পরিবর্তন (Customize) করতে পারেন। "
        "কোন কোডটি কোন ফাইলে রয়েছে এবং কীভাবে পরিবর্তন করবেন, তার সম্পূর্ণ রেফারেন্স নিচে বিস্তারিত দেওয়া হলো।"
    )
    r_intro.font.name = "Arial"
    r_intro.font.size = Pt(10)
    r_intro.font.color.rgb = RGBColor(30, 41, 59)
    
    # =========================================================================
    # SECTION 1: MASTER CUSTOMIZATION CHEATSHEET
    # =========================================================================
    h1 = doc.add_paragraph()
    h1.paragraph_format.space_before = Pt(14)
    h1.paragraph_format.space_after = Pt(6)
    r_h1 = h1.add_run("১. কাস্টমাইজেশন কুইক রেফারেন্স টেবিল (Master Cheatsheet)")
    r_h1.font.name = "Arial"
    r_h1.font.size = Pt(14)
    r_h1.font.bold = True
    r_h1.font.color.rgb = RGBColor(15, 23, 42)
    
    p_tbl_desc = doc.add_paragraph()
    p_tbl_desc.paragraph_format.space_after = Pt(6)
    r_desc = p_tbl_desc.add_run("যেকোনো কিছু পরিবর্তন করার আগে এই টেবিলটি দেখে সরাসরি নির্দিষ্ট ফাইলটি ওপেন করুন:")
    r_desc.font.name = "Arial"
    r_desc.font.size = Pt(9.5)
    r_desc.font.italic = True
    
    cheatsheet_data = [
        ["ড্যাশবোর্ডের হেডার ব্যানার ও গ্রিটিং", "resources/views/filament/widgets/dashboard-header-widget.blade.php", "স্বাগতম বার্তা, লাইভ হাব সিঙ্ক ব্যাজ, কুইক অ্যাকশন বাটন"],
        ["দোকানের নাম, লোগো, ট্যাগলাইন, ঠিকানা", "app/Models/ShopSetting.php\napp/Filament/Pages/ShopSettingsPage.php", "শপের প্রোফাইল ডাটাবেসে সেভ ও পেজ কন্ট্রোল"],
        ["টপবার / সাইডবারের ব্র্যান্ড লোগো ও নাম", "resources/views/filament/components/brand-logo-and-name.blade.php", "টপবারে লোগো সাইজ ও শপ নামের রেসপন্সিভ স্টাইল"],
        ["গ্লোবাল সিএসএস, কালার ও রেসপন্সিভনেস", "resources/views/filament/custom-styles.blade.php", "Tailwind স্টাইল ওভাররাইড, ফন্ট, ডার্ক মোড, মোবাইল সিএসএস"],
        ["বিক্রয়ের ক্যাশ মেমো / ইনভয়েস প্রিন্ট", "resources/views/prints/sale-invoice.blade.php", "A4 ও POS স্লিপ মেমো ফরম্যাট, শর্তাবলী, বাংলা টেক্সট"],
        ["বাকী আদায়ের মানি রিসিট প্রিন্ট", "resources/views/prints/payment-receipt.blade.php", "কাস্টমারের বাকী জমার টাকা প্রাপ্তি রশিদ ফরম্যাট"],
        ["দৈনিক ক্লোজিং ও লাভ-ক্ষতির সামারি রিপোর্ট", "resources/views/prints/executive-summary.blade.php\napp/Filament/Pages/ExecutiveSummaryReport.php", "সারাদিনের বেচাকেনা, মোট লাভ, বাকি ও স্টক সামারি"],
        ["সেলস ফর্ম ও বস্তা-কেজি অটো ক্যালকুলেশন", "app/Filament/Resources/SaleResource.php", "কাস্টমার নির্বাচন, ফিড আইটেম রিপিটার, ডিসকাউন্ট, বাকী সেটেলমেন্ট"],
        ["ফিড পণ্য তালিকা, বস্তার সাইজ ও রেট", "app/Filament/Resources/ProductResource.php", "Product Name, Brand, Pack Size (KG), Buy/Sell Price"],
        ["কাস্টমার লেজার, বাকী ও ক্রেডিট লিমিট", "app/Filament/Resources/CustomerResource.php\napp/Models/Customer.php", "কাস্টমার প্রোফাইল, বকেয়া ব্যালেন্স অটো-আপডেট লজিক"],
        ["সাপ্লায়ার / ফিড মিল কোম্পানির হিসাব", "app/Filament/Resources/SupplierResource.php", "ফিড কোম্পানির বকেয়া দেনা ও লেজার ট্র্যাকিং"],
        ["কোম্পানি থেকে ফিড ক্রয় ও স্টক ইন", "app/Filament/Resources/PurchaseResource.php", "ক্রয় চালান তৈরি ও গোডাউনে বস্তা স্বয়ংক্রিয়ভাবে বৃদ্ধি"],
        ["ড্যাশবোর্ডের ৫টি কেপিআই স্ট্যাট কার্ড", "app/Filament/Widgets/DashboardStatsOverviewWidget.php", "আজকের বিক্রয়, কালেকশন, কাস্টমার বাকী, কোম্পানি দেনা, স্টক"],
        ["৭ দিনের বিক্রয় চার্ট ও ক্যাটাগরি কার্ড", "app/Filament/Widgets/SalesTrendChartWidget.php\nsales-trend-chart-widget.blade.php", "চার্টের লাইন ও ভাসমান/ডুবন্ত ফিডের লাইভ স্টক শেয়ার"],
        ["সর্বনিম্ন মজুদ সতর্কতা (Low Stock)", "app/Filament/Widgets/LowStockAlertWidget.php\nlow-stock-alert-widget.blade.php", "লাল ঝলমলে অ্যালার্ট কার্ড ও রিঅর্ডার লিমিট"],
        ["অফলাইন পিওএস স্ক্রিন ও ইঞ্জিন", "app/Filament/Pages/OfflinePOSPage.php\npublic/js/offline-sync-engine.js", "ইন্টারনেট ছাড়া মেমো কাটা ও অনলাইন হলে ডাটা সিঙ্ক"],
        ["অফলাইন ডাটা সিঙ্ক ব্যাকএন্ড কন্ট্রোলার", "app/Http/Controllers/OfflineSyncController.php", "অফলাইন সেলস রিসিভ করে ডাটাবেসে লেখার API"],
        ["প্রিন্ট রাউট ও কন্ট্রোলার", "routes/web.php\napp/Http/Controllers/SalePrintController.php", "ইনভয়েস ও মানি রিসিটের ব্রাউজার প্রিন্ট রাউট"],
        ["ইউজার তৈরি, পাসওয়ার্ড ও রোল", "app/Filament/Resources/UserResource.php", "Admin, Cashier, Viewer পারমিশন ম্যানেজমেন্ট"],
        ["অটোমেটেড ডিপ্লয়মেন্ট স্ক্রিপ্ট", ".github/workflows/deploy.yml", "GitHub Actions CI/CD ও cPanel SSH ডিপ্লয়মেন্ট"]
    ]
    
    tbl = doc.add_table(rows=1, cols=3)
    format_table(tbl, [2.2, 2.5, 2.2], ["কী পরিবর্তন করবেন?", "কোন ফাইলটি এডিট করবেন?", "ফাইলে কী কাজ করে?"], cheatsheet_data)
    
    # =========================================================================
    # SECTION 2: SCENARIOS & STEP-BY-STEP INSTRUCTIONS
    # =========================================================================
    doc.add_page_break()
    h2_main = doc.add_paragraph()
    h2_main.paragraph_format.space_before = Pt(14)
    h2_main.paragraph_format.space_after = Pt(8)
    r_h2_main = h2_main.add_run("২. সাধারণ কাস্টমাইজেশন সিনারিও এবং সমাধান (Step-by-Step Scenarios)")
    r_h2_main.font.name = "Arial"
    r_h2_main.font.size = Pt(14)
    r_h2_main.font.bold = True
    r_h2_main.font.color.rgb = RGBColor(15, 23, 42)
    
    # -------------------------------------------------------------------------
    # Scenario 1
    # -------------------------------------------------------------------------
    p_sc1 = doc.add_paragraph()
    p_sc1.paragraph_format.space_before = Pt(8)
    p_sc1.paragraph_format.space_after = Pt(4)
    r_sc1 = p_sc1.add_run("সিনারিও ১: ড্যাশবোর্ডের ব্যানার, বাটন বা শুভেচ্ছা বার্তা পরিবর্তন")
    r_sc1.font.name = "Arial"
    r_sc1.font.size = Pt(11.5)
    r_sc1.font.bold = True
    r_sc1.font.color.rgb = RGBColor(13, 148, 136)
    
    create_callout(
        doc,
        "মূল ফাইল",
        "resources/views/filament/widgets/dashboard-header-widget.blade.php",
        "এই ফাইলটিতে ড্যাশবোর্ডের উপরের নীল গ্রাডিয়েন্ট ব্যানারটির সম্পূর্ণ HTML/Blade কোড রয়েছে।"
    )
    
    p_sc1_desc = doc.add_paragraph()
    p_sc1_desc.paragraph_format.space_after = Pt(6)
    p_sc1_desc.add_run(
        "• স্বাগতম লেখা ও রোল: লাইন ২৯-৪৪ এ রয়েছে 'স্বাগতম, {user->name}' এবং Admin/Viewer গোল ব্যাজ।\n"
        "• শপ ব্যানার ও লোগো: লাইন ৪৬-৬০ এ রয়েছে লোগো ছবি ($shop->getLogoUrl()), শপের নাম ($shop->shop_name) এবং ট্যাগলাইন ($shop->shop_tagline)।\n"
        "• কুইক অ্যাকশন বাটন: লাইন ৬৩-৯০ এ রয়েছে '+ New Sale (POS)', '+ Purchase (ক্রয়)', 'Collect Due (আদায়)' এবং 'Executive P&L' বাটন। আপনি যদি নতুন বাটন যোগ করতে চান, তবে একটি নতুন <a> ট্যাগ এই সেকশনে কপি-পেস্ট করে লিংক বসিয়ে দিলেই হবে।"
    )
    for r in p_sc1_desc.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)
        
    # -------------------------------------------------------------------------
    # Scenario 2
    # -------------------------------------------------------------------------
    p_sc2 = doc.add_paragraph()
    p_sc2.paragraph_format.space_before = Pt(8)
    p_sc2.paragraph_format.space_after = Pt(4)
    r_sc2 = p_sc2.add_run("সিনারিও ২: ক্যাশ মেমো / ইনভয়েস প্রিন্ট ফরম্যাট ও শর্তাবলী পরিবর্তন")
    r_sc2.font.name = "Arial"
    r_sc2.font.size = Pt(11.5)
    r_sc2.font.bold = True
    r_sc2.font.color.rgb = RGBColor(13, 148, 136)
    
    create_callout(
        doc,
        "মূল ফাইল",
        "resources/views/prints/sale-invoice.blade.php",
        "কাস্টমারকে যে মেমো প্রিন্ট করে দেওয়া হয় (A4 প্রিন্ট এবং থার্মাল POS রসিদ উভয়ই) এই ফাইলে সংরক্ষিত।"
    )
    
    p_sc2_desc = doc.add_paragraph()
    p_sc2_desc.paragraph_format.space_after = Pt(6)
    p_sc2_desc.add_run(
        "• মেমোর হেডার ও লোগো: ইনভয়েসের একদম উপরে দোকানের নাম, লোগো, ঠিকানা ও যোগাযোগের ফোন নম্বর রয়েছে।\n"
        "• টেবিল কলাম: ক্রমিক, পণ্যের বিবরণ (ফিডের নাম ও বস্তার সাইজ), বস্তার সংখ্যা, মোট কেজি, প্রতি বস্তার দর এবং মোট টাকা।\n"
        "• সামারি অংশ: মোট বিল, বিশেষ মূল্যছাড় (Discount), আজকের নগদ জমা (Paid Amount), বর্তমান চালানের বাকী এবং কাস্টমারের পূর্বের জেরসহ সর্বমোট বকেয়া।\n"
        "• বিক্রয়ের শর্তাবলী (Terms & Conditions): মেমোর নিচে থাকা 'বিক্রিত মাল ফেরত বা পরিবর্তনযোগ্য নয়' বা পেমেন্ট সংক্রান্ত শর্ত পরিবর্তন করতে ফাইলের শেষের দিকের <ul><li> অংশটি এডিট করুন।"
    )
    for r in p_sc2_desc.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)

    # -------------------------------------------------------------------------
    # Scenario 3
    # -------------------------------------------------------------------------
    p_sc3 = doc.add_paragraph()
    p_sc3.paragraph_format.space_before = Pt(8)
    p_sc3.paragraph_format.space_after = Pt(4)
    r_sc3 = p_sc3.add_run("সিনারিও ৩: সিএসএস, কালার, ফন্ট ও মোবাইল রেসপন্সিভনেস পরিবর্তন")
    r_sc3.font.name = "Arial"
    r_sc3.font.size = Pt(11.5)
    r_sc3.font.bold = True
    r_sc3.font.color.rgb = RGBColor(13, 148, 136)
    
    create_callout(
        doc,
        "মূল ফাইল",
        "resources/views/filament/custom-styles.blade.php",
        "সম্পূর্ণ অ্যাপ্লিকেশনের মাস্টার সিএসএস ফাইল। সব কাস্টম স্টাইল, ডার্ক মোড ও রেসপন্সিভ মিডিয়া কুয়েরি এখানে থাকে।"
    )
    
    p_sc3_desc = doc.add_paragraph()
    p_sc3_desc.paragraph_format.space_after = Pt(6)
    p_sc3_desc.add_run(
        "• গ্লোবাল ফন্ট: লাইন ১-১৪ এ Google Fonts (Plus Jakarta Sans) লিঙ্ক করা আছে। আপনি চাইলে বাংলা ফন্ট (যেমন Hind Siliguri বা Kalpurush) যুক্ত করতে পারেন।\n"
        "• ড্যাশবোর্ড কার্ড স্টাইল: লাইন ১৯৫-২৫৬ এ .fi-wi-stats-overview-stat ক্লাসের মাধ্যমে কার্ডের ব্যাকগ্রাউন্ড (#ffffff / #0f172a), বর্ডার রেডিয়াস ও হোভার এফেক্ট নিয়ন্ত্রিত হয়।\n"
        "• মোবাইল রেসপন্সিভ রুলস: ফাইলের শেষের @media (max-width: 768px) ব্লকে মোবাইলে কার্ড ফুল-উইডথ করা, গ্রিড গ্যাপ কমানো এবং টেক্সট সাইজ স্কেল করার সিএসএস রয়েছে।"
    )
    for r in p_sc3_desc.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)

    # -------------------------------------------------------------------------
    # Scenario 4
    # -------------------------------------------------------------------------
    p_sc4 = doc.add_paragraph()
    p_sc4.paragraph_format.space_before = Pt(8)
    p_sc4.paragraph_format.space_after = Pt(4)
    r_sc4 = p_sc4.add_run("সিনারিও ৪: কোনো টেবিলে নতুন ফিল্ড যোগ করার নিয়ম (Add New Field)")
    r_sc4.font.name = "Arial"
    r_sc4.font.size = Pt(11.5)
    r_sc4.font.bold = True
    r_sc4.font.color.rgb = RGBColor(13, 148, 136)
    
    p_sc4_desc = doc.add_paragraph()
    p_sc4_desc.paragraph_format.space_after = Pt(6)
    p_sc4_desc.add_run(
        "উদাহরণস্বরূপ: আপনি যদি Customer ফর্মে কাস্টমারের 'NID Number' বা 'বিকল্প ফোন নম্বর' যোগ করতে চান, তবে ৩টি ফাইলে কাজ করতে হবে:\n\n"
        "১. ডাটাবেস মাইগ্রেশন (Database Migration):\n"
        "   টার্মিনালে কমান্ড চালান: php artisan make:migration add_nid_to_customers_table\n"
        "   নতুন তৈরি হওয়া মাইগ্রেশন ফাইলে $table->string('nid_number')->nullable(); যোগ করে php artisan migrate চালান।\n\n"
        "২. মডেল ফাইলে ফিল্ড অনুমোদন (Model Fillable):\n"
        "   ফাইল: app/Models/Customer.php\n"
        "   protected $fillable অ্যারেতে 'nid_number' যোগ করুন।\n\n"
        "৩. ফিলামেন্ট ফর্মে ফিল্ড দেখানো (Filament Resource Form):\n"
        "   ফাইল: app/Filament/Resources/CustomerResource.php\n"
        "   form() মেথডে গিয়ে Forms\\Components\\TextInput::make('nid_number')->label('জাতীয় পরিচয়পত্র / NID') যোগ করুন।"
    )
    for r in p_sc4_desc.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)

    # -------------------------------------------------------------------------
    # Scenario 5
    # -------------------------------------------------------------------------
    p_sc5 = doc.add_paragraph()
    p_sc5.paragraph_format.space_before = Pt(8)
    p_sc5.paragraph_format.space_after = Pt(4)
    r_sc5 = p_sc5.add_run("সিনারিও ৫: সেলস ফর্মের বস্তা, কেজি ও মোট টাকার লাইভ হিসাব লজিক")
    r_sc5.font.name = "Arial"
    r_sc5.font.size = Pt(11.5)
    r_sc5.font.bold = True
    r_sc5.font.color.rgb = RGBColor(13, 148, 136)
    
    create_callout(
        doc,
        "মূল ফাইল",
        "app/Filament/Resources/SaleResource.php",
        "বিক্রয় ফর্মের রিপিটার এবং লাইভ ক্যালকুলেশন হ্যান্ডলার।"
    )
    
    p_sc5_desc = doc.add_paragraph()
    p_sc5_desc.paragraph_format.space_after = Pt(6)
    p_sc5_desc.add_run(
        "• বস্তা পরিবর্তনের সাথে সাথে লাইভ কেজি হিসাব: SaleResource.php-এর quantity_bags ফিল্ডে ->live(onBlur: true) এবং ->afterStateUpdated() হুক ব্যবহার করা হয়েছে।\n"
        "• হিসাব সূত্র: মোট কেজি = বস্তার সংখ্যা × প্রতি বস্তার সাইজ (Pack Size KG)।\n"
        "• লাইভ মোট বিল হিসাব: calculateTotals() মেথডটি প্রতিটি আইটেমের সাবটোটাল যোগ করে, ডিসকাউন্ট বিয়োগ করে এবং কাস্টমারের পূর্বের বকেয়া হিসাব করে 'গ্র্যান্ড টোটাল' ও 'চালানের বাকী' হিসাব করে।"
    )
    for r in p_sc5_desc.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)

    # =========================================================================
    # SECTION 3: DIRECTORY MAP
    # =========================================================================
    doc.add_page_break()
    h3_main = doc.add_paragraph()
    h3_main.paragraph_format.space_before = Pt(14)
    h3_main.paragraph_format.space_after = Pt(8)
    r_h3_main = h3_main.add_run("৩. ডিরেক্টরি ম্যাপ — কোন ফোল্ডারে কী থাকে? (Project Anatomy)")
    r_h3_main.font.name = "Arial"
    r_h3_main.font.size = Pt(14)
    r_h3_main.font.bold = True
    r_h3_main.font.color.rgb = RGBColor(15, 23, 42)
    
    dir_data = [
        ["app/Filament/Resources/", "সবগুলো প্রধান ম্যানেজমেন্ট মডিউল (Sales, Products, Customers, Purchases, Suppliers, Payments, Users)। প্রতিটি রিসোর্সে ফর্ম ও টেবিল থাকে।"],
        ["app/Filament/Pages/", "কাস্টম পেজসমূহ: Dashboard.php (ড্যাশবোর্ড গ্রিড), ShopSettingsPage.php (দোকানের সেটিংস), OfflinePOSPage.php (অফলাইন পিওএস) এবং সব ধরনের রিপোর্ট পেজ।"],
        ["app/Filament/Widgets/", "ড্যাশবোর্ডের উইজেট কন্ট্রোলারসমূহ (Header, 5 Stats Overview Cards, Sales Trend Chart, Low Stock Alerts, Recent Sales Table)।"],
        ["app/Models/", "ডাটাবেস মডেলসমূহ (Sale, Product, Customer, Supplier, Purchase, ShopSetting)। এখানে টেবিলের সম্পর্ক (Relationships) ও হিসাবের লজিক থাকে।"],
        ["resources/views/filament/", "ফিলামেন্টের কাস্টম ব্লেড ফাইলসমূহ (custom-styles.blade.php, brand-logo-and-name.blade.php, widgets/ ইত্যাদি)।"],
        ["resources/views/prints/", "প্রিন্টযোগ্য সব ডকুমেন্টের ব্লেড ভিউ (sale-invoice.blade.php, payment-receipt.blade.php, executive-summary.blade.php)।"],
        ["public/js/", "অফলাইন সিঙ্ক ইঞ্জিন স্ক্রিপ্ট (offline-sync-engine.js) এবং ব্রাউজার ক্লায়েন্ট স্ক্রিপ্ট।"],
        ["routes/web.php", "অ্যাপ্লিকেশনের প্রিন্ট রাউট এবং ব্রাউজার এন্ট্রি পয়েন্ট।"],
        ["database/migrations/", "ডাটাবেসের সব টেবিল ও কলামের স্ট্রাকচার স্ক্রিপ্ট।"],
        [".github/workflows/deploy.yml", "GitHub Actions CI/CD অটোমেশন স্ক্রিপ্ট (PHP 8.4, MySQL টেস্ট ও cPanel SSH ডিপ্লয়মেন্ট)।"]
    ]
    
    tbl_dir = doc.add_table(rows=1, cols=2)
    format_table(tbl_dir, [2.5, 4.4], ["ফোল্ডার বা ফাইল পাথ", "ফোল্ডারের দায়িত্ব ও কার্যকারিতা"], dir_data)

    # =========================================================================
    # SECTION 4: ARTISAN COMMANDS & DEPLOYMENT
    # =========================================================================
    h4_main = doc.add_paragraph()
    h4_main.paragraph_format.space_before = Pt(16)
    h4_main.paragraph_format.space_after = Pt(8)
    r_h4_main = h4_main.add_run("৪. প্রয়োজনীয় কমান্ড ও পরিবর্তন লাইভ করার নিয়ম (Commands & Git Workflow)")
    r_h4_main.font.name = "Arial"
    r_h4_main.font.size = Pt(14)
    r_h4_main.font.bold = True
    r_h4_main.font.color.rgb = RGBColor(15, 23, 42)
    
    p_cmd = doc.add_paragraph()
    p_cmd.paragraph_format.space_after = Pt(8)
    p_cmd.add_run(
        "যেকোনো কোড বা ব্লেড ভিউতে পরিবর্তন করার পর স্থানীয়ভাবে ক্যাশ পরিষ্কার করার কমান্ডসমূহ:\n\n"
        "• ক্যাশ পরিষ্কার করা: php artisan optimize:clear\n"
        "• ফিলামেন্ট কম্পোনেন্ট রি-ক্যাশ করা: php artisan filament:cache-components\n"
        "• টেস্ট চালানো: php artisan test\n\n"
        "পরিবর্তন Git ও লাইভ সার্ভারে ডিপ্লয় করার নিয়ম:\n"
        "১. টার্মিনালে স্ট্যাটাস দেখুন: git status\n"
        "২. ফাইল যোগ ও কমিট করুন:\n"
        "   git add .\n"
        "   git commit -m \"আপনার পরিবর্তনের সংক্ষিপ্ত বিবরণ\"\n"
        "৩. মেইন ব্রাঞ্চে পুশ করুন:\n"
        "   git push origin main\n\n"
        "📌 পুশ করার সাথে সাথেই GitHub Actions স্বয়ংক্রিয়ভাবে টেস্ট চালাবে এবং পাস হলে সরাসরি আপনার cPanel হোস্টিংয়ে লাইভ করে দেবে।"
    )
    for r in p_cmd.runs:
        r.font.name = "Arial"
        r.font.size = Pt(9.5)
        
    # Footer notice
    p_ftr = doc.add_paragraph()
    p_ftr.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_ftr.paragraph_format.space_before = Pt(20)
    r_ftr = p_ftr.add_run("— বিসমিল্লাহ ফিশ ফিড স্টোর ERP & POS System Architecture Guide —")
    r_ftr.font.name = "Arial"
    r_ftr.font.size = Pt(9)
    r_ftr.font.color.rgb = RGBColor(148, 163, 184)
    
    # Save both .docx and .docs
    output_docx = "DEVELOPER_CUSTOMIZATION_GUIDE.docx"
    output_docs = "DEVELOPER_CUSTOMIZATION_GUIDE.docs"
    
    doc.save(output_docx)
    doc.save(output_docs)
    print(f"Successfully generated {output_docx} and {output_docs}!")

if __name__ == "__main__":
    build_guide()
