"""
Generate Professional High-Resolution ER Diagram for DBMS Submission
Creates documentation/ER-Diagram.png with all 17 tables, columns, PKs, FKs, and cardinalities.
"""

from PIL import Image, ImageDraw, ImageFont
import os

def create_er_diagram(output_path):
    width = 3200
    height = 2400
    img = Image.new("RGB", (width, height), color="#0f172a") # Elegant dark navy slate theme
    draw = ImageDraw.Draw(img)

    # Fonts
    font_title = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", 36)
    font_subtitle = ImageFont.truetype("C:/Windows/Fonts/segoeui.ttf", 20)
    font_tbl_header = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", 18)
    font_col = ImageFont.truetype("C:/Windows/Fonts/consolab.ttf", 14)
    font_badge = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", 12)
    font_card = ImageFont.truetype("C:/Windows/Fonts/segoeuib.ttf", 13)

    # Title Bar
    draw.rectangle([(40, 30), (width - 40, 110)], fill="#1e293b", outline="#334155", width=2)
    draw.text((70, 42), "CAMPUS RECRUITMENT & PLACEMENT MANAGEMENT SYSTEM - RELATIONAL SCHEMA (3NF)", fill="#f8fafc", font=font_title)
    draw.text((70, 82), "Entity-Relationship Architecture Diagram | 17 Fully Normalized Relational Tables | PK/FK Mapping & Cardinalities", fill="#94a3b8", font=font_subtitle)

    # Tables definition: [name, category_color, x, y, width, height, columns]
    # columns: [(name, type, is_pk, is_fk)]
    tables = {
        "departments": {
            "title": "departments", "color": "#2563eb", "x": 60, "y": 140, "w": 340, "h": 160,
            "cols": [
                ("dept_id", "INT AUTO_INCREMENT", True, False),
                ("dept_name", "VARCHAR(100) UNIQUE", False, False),
                ("dept_code", "VARCHAR(10) UNIQUE", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "branches": {
            "title": "branches", "color": "#2563eb", "x": 60, "y": 340, "w": 340, "h": 200,
            "cols": [
                ("branch_id", "INT AUTO_INCREMENT", True, False),
                ("dept_id", "INT", False, True),
                ("branch_name", "VARCHAR(100)", False, False),
                ("branch_code", "VARCHAR(10) UNIQUE", False, False),
                ("degree_type", "VARCHAR(50)", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "users": {
            "title": "users", "color": "#0284c7", "x": 480, "y": 140, "w": 360, "h": 220,
            "cols": [
                ("user_id", "INT AUTO_INCREMENT", True, False),
                ("email", "VARCHAR(150) UNIQUE", False, False),
                ("password_hash", "VARCHAR(255)", False, False),
                ("role", "ENUM(STUDENT,RECRUITER,ADMIN)", False, False),
                ("is_active", "TINYINT(1) DEFAULT 1", False, False),
                ("created_at", "DATETIME", False, False),
                ("updated_at", "DATETIME", False, False),
            ]
        },
        "students": {
            "title": "students", "color": "#059669", "x": 480, "y": 420, "w": 380, "h": 400,
            "cols": [
                ("student_id", "INT AUTO_INCREMENT", True, False),
                ("user_id", "INT UNIQUE", False, True),
                ("branch_id", "INT", False, True),
                ("roll_number", "VARCHAR(20) UNIQUE", False, False),
                ("first_name", "VARCHAR(50)", False, False),
                ("last_name", "VARCHAR(50)", False, False),
                ("gender", "ENUM(MALE,FEMALE,OTHER)", False, False),
                ("dob", "DATE", False, False),
                ("phone", "VARCHAR(15)", False, False),
                ("cgpa", "DECIMAL(4,2)", False, False),
                ("tenth_percent", "DECIMAL(5,2)", False, False),
                ("twelfth_percent", "DECIMAL(5,2)", False, False),
                ("placement_status", "ENUM(NOT_PLACED,PLACED)", False, False),
                ("resume_path", "VARCHAR(255)", False, False),
            ]
        },
        "skills": {
            "title": "skills", "color": "#0d9488", "x": 60, "y": 620, "w": 340, "h": 160,
            "cols": [
                ("skill_id", "INT AUTO_INCREMENT", True, False),
                ("skill_name", "VARCHAR(100) UNIQUE", False, False),
                ("category", "VARCHAR(50)", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "student_skills": {
            "title": "student_skills", "color": "#0d9488", "x": 60, "y": 840, "w": 340, "h": 180,
            "cols": [
                ("student_skill_id", "INT AUTO_INCREMENT", True, False),
                ("student_id", "INT", False, True),
                ("skill_id", "INT", False, True),
                ("proficiency_level", "ENUM(BEGINNER,INTERMEDIATE,ADVANCED)", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "education": {
            "title": "education", "color": "#16a34a", "x": 60, "y": 1080, "w": 360, "h": 240,
            "cols": [
                ("education_id", "INT AUTO_INCREMENT", True, False),
                ("student_id", "INT", False, True),
                ("degree", "VARCHAR(100)", False, False),
                ("institution", "VARCHAR(200)", False, False),
                ("board_university", "VARCHAR(200)", False, False),
                ("score_value", "DECIMAL(5,2)", False, False),
                ("score_type", "ENUM(PERCENTAGE,CGPA)", False, False),
                ("passing_year", "YEAR", False, False),
            ]
        },
        "projects": {
            "title": "projects", "color": "#16a34a", "x": 60, "y": 1380, "w": 360, "h": 240,
            "cols": [
                ("project_id", "INT AUTO_INCREMENT", True, False),
                ("student_id", "INT", False, True),
                ("title", "VARCHAR(150)", False, False),
                ("description", "TEXT", False, False),
                ("tech_stack", "VARCHAR(255)", False, False),
                ("github_url", "VARCHAR(255)", False, False),
                ("live_url", "VARCHAR(255)", False, False),
            ]
        },
        "certifications": {
            "title": "certifications", "color": "#16a34a", "x": 60, "y": 1680, "w": 360, "h": 220,
            "cols": [
                ("cert_id", "INT AUTO_INCREMENT", True, False),
                ("student_id", "INT", False, True),
                ("title", "VARCHAR(150)", False, False),
                ("issuing_org", "VARCHAR(150)", False, False),
                ("issue_date", "DATE", False, False),
                ("credential_url", "VARCHAR(255)", False, False),
            ]
        },
        "companies": {
            "title": "companies", "color": "#7c3aed", "x": 1020, "y": 140, "w": 360, "h": 240,
            "cols": [
                ("company_id", "INT AUTO_INCREMENT", True, False),
                ("company_name", "VARCHAR(150) UNIQUE", False, False),
                ("website", "VARCHAR(255)", False, False),
                ("industry", "VARCHAR(100)", False, False),
                ("description", "TEXT", False, False),
                ("location", "VARCHAR(100)", False, False),
                ("is_verified", "TINYINT(1) DEFAULT 1", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "recruiters": {
            "title": "recruiters", "color": "#7c3aed", "x": 1020, "y": 440, "w": 360, "h": 240,
            "cols": [
                ("recruiter_id", "INT AUTO_INCREMENT", True, False),
                ("user_id", "INT UNIQUE", False, True),
                ("company_id", "INT", False, True),
                ("designation", "VARCHAR(100)", False, False),
                ("phone", "VARCHAR(15)", False, False),
                ("approval_status", "ENUM(PENDING,APPROVED,REJECTED)", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "jobs": {
            "title": "jobs", "color": "#9333ea", "x": 1500, "y": 140, "w": 400, "h": 380,
            "cols": [
                ("job_id", "INT AUTO_INCREMENT", True, False),
                ("company_id", "INT", False, True),
                ("recruiter_id", "INT", False, True),
                ("title", "VARCHAR(150)", False, False),
                ("description", "TEXT", False, False),
                ("job_type", "ENUM(FULL_TIME,INTERNSHIP)", False, False),
                ("work_mode", "ENUM(ON_SITE,HYBRID,REMOTE)", False, False),
                ("location", "VARCHAR(100)", False, False),
                ("min_cgpa", "DECIMAL(4,2)", False, False),
                ("salary_stipend", "DECIMAL(12,2)", False, False),
                ("deadline", "DATE", False, False),
                ("openings_count", "INT", False, False),
                ("status", "ENUM(PENDING,LIVE,CLOSED)", False, False),
            ]
        },
        "job_eligible_branches": {
            "title": "job_eligible_branches", "color": "#9333ea", "x": 1500, "y": 570, "w": 380, "h": 160,
            "cols": [
                ("job_branch_id", "INT AUTO_INCREMENT", True, False),
                ("job_id", "INT", False, True),
                ("branch_id", "INT", False, True),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "job_skills": {
            "title": "job_skills", "color": "#9333ea", "x": 1500, "y": 780, "w": 380, "h": 180,
            "cols": [
                ("job_skill_id", "INT AUTO_INCREMENT", True, False),
                ("job_id", "INT", False, True),
                ("skill_id", "INT", False, True),
                ("is_required", "TINYINT(1) DEFAULT 1", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        },
        "applications": {
            "title": "applications", "color": "#ea580c", "x": 2040, "y": 140, "w": 440, "h": 260,
            "cols": [
                ("application_id", "INT AUTO_INCREMENT", True, False),
                ("job_id", "INT", False, True),
                ("student_id", "INT", False, True),
                ("applied_at", "DATETIME", False, False),
                ("status", "ENUM(APPLIED,SHORTLISTED,INTERVIEW...)", False, False),
                ("match_score", "DECIMAL(5,2)", False, False),
                ("notes", "TEXT", False, False),
            ]
        },
        "interviews": {
            "title": "interviews", "color": "#c026d3", "x": 2040, "y": 460, "w": 440, "h": 280,
            "cols": [
                ("interview_id", "INT AUTO_INCREMENT", True, False),
                ("application_id", "INT", False, True),
                ("scheduled_at", "DATETIME", False, False),
                ("round_number", "INT DEFAULT 1", False, False),
                ("mode", "ENUM(OFFLINE,ONLINE)", False, False),
                ("location_link", "VARCHAR(255)", False, False),
                ("status", "ENUM(SCHEDULED,COMPLETED,CANCELLED)", False, False),
                ("feedback", "TEXT", False, False),
            ]
        },
        "placements": {
            "title": "placements", "color": "#e11d48", "x": 2040, "y": 800, "w": 440, "h": 280,
            "cols": [
                ("placement_id", "INT AUTO_INCREMENT", True, False),
                ("application_id", "INT UNIQUE", False, True),
                ("student_id", "INT", False, True),
                ("company_id", "INT", False, True),
                ("job_id", "INT", False, True),
                ("package_lpa", "DECIMAL(6,2)", False, False),
                ("offer_letter_path", "VARCHAR(255)", False, False),
                ("accepted_at", "DATETIME", False, False),
            ]
        },
        "notifications": {
            "title": "notifications", "color": "#475569", "x": 2040, "y": 1140, "w": 440, "h": 200,
            "cols": [
                ("notification_id", "INT AUTO_INCREMENT", True, False),
                ("user_id", "INT", False, True),
                ("title", "VARCHAR(150)", False, False),
                ("message", "TEXT", False, False),
                ("is_read", "TINYINT(1) DEFAULT 0", False, False),
                ("created_at", "DATETIME", False, False),
            ]
        }
    }

    # Draw Tables
    for key, tbl in tables.items():
        x, y, w, h = tbl["x"], tbl["y"], tbl["w"], tbl["h"]

        # Box shadow
        draw.rectangle([(x + 5, y + 5), (x + w + 5, y + h + 5)], fill="#090d16")

        # Entity Card background
        draw.rectangle([(x, y), (x + w, y + h)], fill="#1e293b", outline="#334155", width=2)

        # Header bar
        draw.rectangle([(x, y), (x + w, y + 42)], fill=tbl["color"])
        draw.text((x + 14, y + 10), tbl["title"].upper(), fill="#ffffff", font=font_tbl_header)

        # Columns
        col_y = y + 52
        for col_name, col_type, is_pk, is_fk in tbl["cols"]:
            # PK / FK indicator badge
            if is_pk and is_fk:
                badge_bg = "#ca8a04"
                badge_txt = "PF"
            elif is_pk:
                badge_bg = "#eab308"
                badge_txt = "PK"
            elif is_fk:
                badge_bg = "#06b6d4"
                badge_txt = "FK"
            else:
                badge_bg = "#334155"
                badge_txt = "  "

            # Badge pill
            draw.rectangle([(x + 10, col_y), (x + 36, col_y + 16)], fill=badge_bg)
            draw.text((x + 14, col_y + 1), badge_txt, fill="#0f172a" if is_pk or is_fk else "#94a3b8", font=font_badge)

            # Column Name
            draw.text((x + 44, col_y), col_name, fill="#f1f5f9" if (is_pk or is_fk) else "#cbd5e1", font=font_col)

            # Data Type (right aligned in column)
            type_len = len(col_type) * 8.5
            draw.text((x + w - type_len - 12, col_y), col_type, fill="#94a3b8", font=font_col)

            col_y += 22

    # Draw Relationship Connectors & Cardinalities
    relationships = [
        # (from_tbl, to_tbl, cardinality, color)
        ("departments", "branches", "1 : M", "#60a5fa", (400, 220), (440, 220), (440, 420), (400, 420)),
        ("branches", "students", "1 : M", "#60a5fa", (400, 450), (480, 450), None, None),
        ("users", "students", "1 : 1", "#38bdf8", (660, 360), (660, 420), None, None),
        ("users", "recruiters", "1 : 1", "#38bdf8", (840, 250), (950, 250), (950, 470), (1020, 470)),
        ("companies", "recruiters", "1 : M", "#a78bfa", (1200, 380), (1200, 440), None, None),
        ("companies", "jobs", "1 : M", "#a78bfa", (1380, 250), (1500, 250), None, None),
        ("recruiters", "jobs", "1 : M", "#c084fc", (1380, 500), (1450, 500), (1450, 320), (1500, 320)),
        ("students", "student_skills", "1 : M", "#34d399", (480, 700), (440, 700), (440, 900), (400, 900)),
        ("skills", "student_skills", "1 : M", "#2dd4bf", (230, 780), (230, 840), None, None),
        ("students", "education", "1 : M", "#4ade80", (520, 820), (520, 1150), (420, 1150), (420, 1150)),
        ("students", "projects", "1 : M", "#4ade80", (500, 820), (500, 1450), (420, 1450), (420, 1450)),
        ("students", "certifications", "1 : M", "#4ade80", (480, 820), (480, 1750), (420, 1750), (420, 1750)),
        ("jobs", "job_eligible_branches", "1 : M", "#c084fc", (1690, 520), (1690, 570), None, None),
        ("branches", "job_eligible_branches", "1 : M", "#60a5fa", (230, 540), (230, 600), (1450, 600), (1500, 600)),
        ("jobs", "job_skills", "1 : M", "#c084fc", (1750, 520), (1750, 780), None, None),
        ("skills", "job_skills", "1 : M", "#2dd4bf", (230, 780), (230, 820), (1450, 820), (1500, 820)),
        ("jobs", "applications", "1 : M", "#fb923c", (1900, 240), (2040, 240), None, None),
        ("students", "applications", "1 : M", "#34d399", (860, 500), (1980, 500), (1980, 280), (2040, 280)),
        ("applications", "interviews", "1 : M", "#f472b6", (2260, 400), (2260, 460), None, None),
        ("applications", "placements", "1 : 1", "#fb7185", (2380, 400), (2380, 800), None, None),
        ("students", "placements", "1 : M", "#34d399", (860, 600), (2000, 600), (2000, 880), (2040, 880)),
        ("companies", "placements", "1 : M", "#a78bfa", (1300, 380), (1300, 920), (2040, 920), (2040, 920)),
        ("jobs", "placements", "1 : M", "#c084fc", (1800, 520), (1800, 960), (2040, 960), (2040, 960)),
        ("users", "notifications", "1 : M", "#94a3b8", (700, 360), (700, 1200), (2040, 1200), (2040, 1200)),
    ]

    for rel in relationships:
        src, dst, card, color, p1, p2, p3, p4 = rel
        if p3 is None:
            # Simple line
            draw.line([p1, p2], fill=color, width=2)
            # Midpoint label
            mx = (p1[0] + p2[0]) // 2
            my = (p1[1] + p2[1]) // 2 - 12
            draw.rectangle([(mx - 18, my - 2), (mx + 18, my + 14)], fill="#0f172a")
            draw.text((mx - 15, my), card, fill=color, font=font_card)
        else:
            # Orthogonal line with corners
            draw.line([p1, p2], fill=color, width=2)
            draw.line([p2, p3], fill=color, width=2)
            draw.line([p3, p4], fill=color, width=2)
            # Label
            mx = p2[0]
            my = (p2[1] + p3[1]) // 2 - 6
            draw.rectangle([(mx - 18, my - 2), (mx + 18, my + 14)], fill="#0f172a")
            draw.text((mx - 15, my), card, fill=color, font=font_card)

    # Legend & Metadata Box in lower canvas
    leg_x, leg_y, leg_w, leg_h = 1000, 1280, 920, 240
    draw.rectangle([(leg_x, leg_y), (leg_x + leg_w, leg_y + leg_h)], fill="#1e293b", outline="#334155", width=2)
    draw.text((leg_x + 20, leg_y + 15), "SCHEMA ARCHITECTURE LEGEND & CONSTRAINTS", fill="#f8fafc", font=font_tbl_header)

    # Legend items
    legends = [
        ("PK", "#eab308", "Primary Key (Unique Identifier, Clustered Index, Auto Increment)"),
        ("FK", "#06b6d4", "Foreign Key (Referential Integrity Constraint, ON DELETE CASCADE / RESTRICT)"),
        ("PF", "#ca8a04", "Composite PK & FK Junction Mapping (Strict 3NF Decomposition)"),
        ("1:M", "#60a5fa", "One-to-Many Cardinality Relationship"),
        ("1:1", "#38bdf8", "One-to-One Cardinality (Unique Constraint on Foreign Key)"),
        ("M:N", "#a855f7", "Many-to-Many resolved via Bridge Tables (student_skills, job_skills, job_eligible_branches)")
    ]

    ly = leg_y + 55
    for tag, col, desc in legends:
        draw.rectangle([(leg_x + 20, ly), (leg_x + 55, ly + 18)], fill=col)
        draw.text((leg_x + 24, ly + 2), tag, fill="#0f172a", font=font_badge)
        draw.text((leg_x + 70, ly + 1), desc, fill="#e2e8f0", font=font_subtitle)
        ly += 28

    # Save output
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    img.save(output_path, "PNG", dpi=(300, 300))
    print(f"ER Diagram generated successfully: {output_path} ({width}x{height} px)")

if __name__ == "__main__":
    out_file = os.path.join(os.path.dirname(os.path.dirname(__file__)), "documentation", "ER-Diagram.png")
    create_er_diagram(out_file)
