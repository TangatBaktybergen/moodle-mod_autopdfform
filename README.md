# AutoPDFForm Activity Plugin for Moodle

This repository contains a custom Moodle activity module that distributes individualized PDF forms to students by automatically filling each PDF with the student’s name, email, and the current date using available Moodle user data, along with example templates and sample outputs for demonstration and testing.

---

## Features

- Teachers upload a PDF form template when creating the activity.
- When students access the activity, the plugin generates and downloads a personalized PDF with the student's name, ID, and other details filled into the form fields.
- Supports common form field types (text, date, dropdown, etc.).
- Can handle batch generation and multiple templates if required.

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
│ └── borrow_form.pdf
│
├── sample_outputs/ # Example personalized PDFs for demonstration
│ └── borrow_form_27955.pdf # Name/ID auto-filled
│
└── README.md
```

## Installation

1. Copy the `autopdfform` folder into your Moodle `mod` directory:  
/path/to/moodle/mod/autopdfform
2. Alternatively, install via Moodle web interface:  
Go to **Site Administration > Plugins > Install plugins**, and upload the plugin ZIP file.
3. Log in as admin and go to **Site Administration > Notifications** to complete the installation.

---

## Usage

1. In your Moodle course, turn editing on and **add an activity**.
2. Choose **AutoPDFForm**.
3. Upload your PDF template with form fields (see `examples/borrow_form.pdf`).
4. Optionally add a description.
5. Save and return to the course.
6. Students open the activity and instantly receive a personalized PDF (see `sample_outputs/borrow_form_27955.pdf`).

---

## Example Files

- **`examples/borrow_form.pdf`** — Sample PDF form template with fields for individualization.
- **`sample_outputs/borrow_form_27955.pdf`** — Example personalized PDF (Name: Tangat Baktybergen, ID: 27955).

---

## Authors

- Ivan Volosyak
- Tangat Baktybergen

---

## License

GNU GPL v3 or later

---

*Developed for bachelor’s thesis at Hochschule Rhein-Waal.*
