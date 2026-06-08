<?php

declare(strict_types=1);

return [
    'app_tagline' => 'Compliance cockpit',
    'nav' => [
        'dashboard' => 'Dashboard',
        'entities' => 'Entities',
        'import' => 'Import',
        'logout' => 'Log out',
    ],
    'login' => [
        'title' => 'Sign in',
        'email' => 'Email',
        'password' => 'Password',
        'submit' => 'Sign in',
        'failed' => 'These credentials do not match our records.',
    ],
    'status' => [
        'safe' => 'Safe',
        'due_soon' => 'Due soon',
        'overdue' => 'Overdue',
        'done' => 'Done',
    ],
    'dashboard' => [
        'title' => 'Compliance dashboard',
        'filter_all' => 'All statuses',
        'col_holder' => 'Entity / Person',
        'col_document' => 'Document',
        'col_due' => 'Due date',
        'col_status' => 'Status',
        'col_responsible' => 'Responsible',
        'unassigned' => 'Unassigned',
        'mark_done' => 'Mark done',
        'empty' => 'No deadlines yet. Upload and confirm documents to start tracking.',
    ],
    'entities' => [
        'title' => 'Client entities',
        'import' => 'Import CSV',
        'col_name' => 'Legal name',
        'col_jurisdiction' => 'Jurisdiction',
        'col_documents' => 'Documents',
        'col_people' => 'People',
        'view' => 'View',
        'empty' => 'No entities yet. Import a CSV to onboard your client book.',
        'documents' => 'Documents',
        'deadlines' => 'Deadlines',
        'people' => 'People',
    ],
    'import' => [
        'title' => 'Import entities (CSV)',
        'help' => 'Columns: legal_name (required), trade_name, jurisdiction_type (mainland|freezone), authority, license_number.',
        'file' => 'CSV file',
        'submit' => 'Import',
        'result' => ':imported imported, :skipped skipped.',
    ],
];
