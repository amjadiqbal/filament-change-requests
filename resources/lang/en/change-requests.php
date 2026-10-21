<?php

return [
    'resource' => [
        'model_label' => 'change request',
        'plural_model_label' => 'change requests',
        'record_title' => 'Change request #:id',
        'empty_heading' => 'No change requests',
        'empty_description' => 'Edits that need approval appear here.',
    ],

    'record' => [
        'new' => 'New :model',
        'deleted' => ':model #:id (deleted)',
    ],

    'type' => [
        'create' => 'New record',
        'update' => 'Edit',
    ],

    'status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'rolled_back' => 'Rolled back',
    ],

    'columns' => [
        'record' => 'Record',
        'type' => 'Type',
        'type_of_record' => 'Type of record',
        'changes' => 'Changes',
        'requested_by' => 'Requested by',
        'requested_at' => 'Requested',
        'reviewed_by' => 'Reviewed by',
        'rolled_back_at' => 'Rolled back',
        'status' => 'Status',
        'pending_changes' => 'Pending changes',
    ],

    'filters' => [
        'record' => 'Record: :record',
        'has_pending_changes' => 'Has pending changes',
    ],

    'view' => [
        'summary' => 'Summary',
    ],

    'diff' => [
        'label' => 'Changes',
        'field' => 'Field',
        'before' => 'Before',
        'after' => 'After',
        'empty' => 'No changes',
    ],

    'value' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'actions' => [
        'approve' => [
            'label' => 'Approve',
            'heading' => 'Approve changes to :record?',
            'description' => 'The changes will be saved to the record.',
            'done' => 'Changes approved and saved',
            'failed' => 'Could not approve the changes',
            'bulk_label' => 'Approve selected',
            'bulk_description' => 'The changes in every selected pending request will be saved.',
            'bulk_done' => ':count change request(s) approved',
        ],
        'reject' => [
            'label' => 'Reject',
            'heading' => 'Reject changes to :record?',
            'description' => 'The record stays as it is.',
            'done' => 'Changes rejected',
            'bulk_label' => 'Reject selected',
            'bulk_description' => 'Every selected pending request will be rejected.',
            'bulk_done' => ':count change request(s) rejected',
        ],
        'roll_back' => [
            'label' => 'Roll back',
            'heading' => 'Roll back changes to :record?',
            'description' => 'The record gets its previous values back.',
            'done' => 'Changes rolled back',
            'failed' => 'Could not roll back the changes',
        ],
        'view_pending' => [
            'label' => 'Pending changes (:count)',
        ],
        'open_record' => [
            'label' => 'Open record',
        ],
        'review' => [
            'label' => 'Review',
        ],
        'not_allowed' => 'You cannot review this change request',
        'bulk_skipped' => ':count skipped: not pending, or you cannot review them',
    ],

    'notifications' => [
        'submitted' => [
            'title' => 'Changes submitted for approval',
            'body' => 'They will be saved once someone approves them.',
        ],
        'creation_submitted' => [
            'title' => 'Submitted for approval',
            'body' => 'The record will be created once someone approves it.',
        ],
    ],

    'widget' => [
        'heading' => 'Change requests awaiting review',
        'empty' => 'Nothing waiting for review',
    ],
];
