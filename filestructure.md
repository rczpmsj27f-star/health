/
├── database/
│   └── migrations/
│       ├── README.md
│       └── migration_add_archive_and_dose_times.sql
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── verify-email.php
│   ├── dashboard.php
│   ├── logout.php
│   │
│   ├── assets/
│   │   ├── css/
│   │   │   └── app.css
│   │   ├── js/
│   │   │   ├── menu.js
│   │   │   └── push.js          (future use)
│   │   ├── images/
│   │   └── fonts/
│   │
│   ├── modules/
│   │   ├── profile/
│   │   │   ├── view.php
│   │   │   ├── edit.php
│   │   │   ├── edit_handler.php
│   │   │   ├── change_password.php
│   │   │   ├── change_password_handler.php
│   │   │   ├── update_picture.php
│   │   │   └── update_picture_handler.php
│   │   │
│   │   ├── medications/
│   │   │   ├── list.php
│   │   │   ├── add.php
│   │   │   ├── add_handler.php
│   │   │   ├── search.php
│   │   │   ├── add_dose.php
│   │   │   ├── add_dose_handler.php
│   │   │   ├── add_schedule.php
│   │   │   ├── add_schedule_handler.php
│   │   │   ├── add_instructions.php
│   │   │   ├── add_instructions_handler.php
│   │   │   ├── add_condition.php
│   │   │   ├── add_condition_handler.php
│   │   │   └── view.php
│   │   │
│   │   ├── admin/
│   │   │   ├── users.php
│   │   │   ├── view_user.php
│   │   │   ├── toggle_verify.php
│   │   │   ├── toggle_active.php
│   │   │   ├── toggle_admin.php
│   │   │   └── force_reset.php
│   │
│   ├── push-public-key.php      (future use)
│   ├── save-subscription.php    (future use)
│   └── sw.js                    (future use)
│
├── app/
│   ├── config/
│   │   ├── database.php
│   │   ├── mailer.php
│   │   └── vapid.php            (future use)
│   │
│   ├── auth/
│   │   ├── login_handler.php
│   │   ├── register_handler.php
│   │   └── verify_handler.php
│   │
│   ├── core/
│   │   ├── auth.php
│   │   ├── Session.php          (optional future)
│   │   └── Router.php           (optional future)
│   │
│   ├── helpers/
│   │   ├── security.php
│   │   ├── validation.php
│   │   └── file_upload.php
│   │
│   ├── services/
│   │   ├── NhsApiClient.php
│   │   ├── MedicationService.php
│   │   └── NotificationService.php   (future use)
│   │
│   ├── models/
│   │   ├── User.php             (optional future)
│   │   ├── Medication.php       (optional future)
│   │   ├── Dose.php             (optional future)
│   │   ├── Schedule.php         (optional future)
│   │   ├── Condition.php        (optional future)
│   │   └── Alert.php            (optional future)
│   │
│   ├── cron/
│   │   └── check_medication_due.php   (future use)
│   │
│   └── logs/                    (optional)
│
├── modules/
│   ├── profile/
│   │   └── ProfileController.php      (optional future)
│   │
│   ├── medications/
│   │   ├── MedicationController.php   (optional future)
│   │   ├── MedicationModel.php        (optional future)
│   │   └── MedicationView.php         (optional future)
│   │
│   └── admin/
│       └── AdminController.php        (optional future)
│
├── uploads/
│   ├── profile/
│   ├── documents/
│   └── temp/
│
├── vendor/                           (PHPMailer, Composer)
│
└── composer.json                     (if using Composer)
