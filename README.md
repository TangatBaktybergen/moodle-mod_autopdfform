# Individual Auto-Filled PDF Form Download Activity Plugin for Moodle

## Short Description

Moodle plugin that automatically fills PDF files with student name, email, and current date using available data.
This Moodle activity module allows teachers to upload a PDF form file (AcroForm), which students can download with their personal data automatically filled in.

---

## Features
- Unlike other plugins, teachers upload already a final PDF document (PDF template with interactive form fields, AcroForm) 
- This moodle plugin handles the placeholders texts by exchanging them with student information
- Students click the activity and instantly download their personalized PDF files
- Automatically fills:
  - Full Name (text: `full_name`)
  - Student ID (text: `student_id` - first part of the moodle uusername, if in format 12345@students , or by full moodle username if contains no character @ in it)
  - Email (text: `email_address`)
  - Current server date (text: `n_date`)

---

## Folder Structure

```
/
├── autopdfform/               # Moodle plugin code
│   ├── lang/en
│      └── autopdfform.php
│   ├── db
│      └── upgrade.php
│      └── install.xml
│      └── access.php
│   ├── pix
│      └── icon.svg
│   ├── version.php
│   ├── view.php
│   ├── mod_form.php
│   ├── lib.php
│   ├── Readme.md
│
├── examples/ # Example PDF form templates for testing
│ └── loan-form-2025.pdf
│ └── individual-pdf.pdf
│
├── sample_output/ # Example personalized PDFs for demonstration
│ └── individual-pdf-Tangat-Baktybergen.pdf
│ └── loan-form-2025-Tangat-Baktybergen.pdf
│
├── autopdfform.zip  
└── README.md
```

---

## Installation

1. Place the folder `mod_autopdfform` in `moodle/mod/`
2. Run Moodle upgrade via web or CLI
   
---

## Teacher Workflow

1. Turn editing on
2. Add activity → Auto-Filled PDF Form
3. Prepare the suitable AcroForm PDF file with interactive fields 
   (important: Save the PDF with usage rights enabled to allow form field editing in Adobe Acrobat Reader. This is done by applying Reader Extensions using Adobe Acrobat Pro).
4. Fill in fields information accordignly (using texts `full_name`, `student_id`, `email_address`, and `n_date`)
5. Upload PDF template
6. Save and return to course
   
---

## Students View
1. See activity with PDF icon
2. Click → download begins immediately
3. PDF contains auto-filled personal info
   
---

## Known Limitations
- In some cases the field data is not shown properly (e.g. if the last name of the student to be inserted is identical to the last name of the registered user of the Adobe suite,
possibly due to the fact that it may be seen as some kind of copyright manipulation)

---

### How Data Is Used for Auto-Fill

The plugin retrieves the following data from the currently logged-in Moodle user and inserts it into PDF or code templates:
- **Full Name**: Automatically pulled from the user's Moodle profile.
- **Email Address**: The user's registered Moodle email.
- **Student ID**: If available, the unique student number from the profile.
- **Current Date**: The date when the document is generated/downloaded.

This ensures that each generated file is customized and personalized for the individual student, reducing manual entry and preventing accidental data mix-ups.

---

## Authors

Ivan Volosyak, Tangat Baktybergen

---

## License

GNU GPL v3 or later

---

*Developed at Rhine-Waal University of Applied Sciences (Hochschule Rhein-Waal, HSRW), Kleve, Germany.*
