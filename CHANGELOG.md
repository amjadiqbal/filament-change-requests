# Changelog

All notable changes to `amjadiqbal/filament-change-requests` are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Change requests resource:
  - pending requests by default
  - status, record-type and record filters
  - approve and reject on each row and in bulk
  - a navigation badge with the pending count
- Request page with a field-by-field diff (sensitive attributes masked), plus approve, reject and roll back.
- `SubmitsChangeRequests` and `SubmitsCreationRequests` page traits: held edits and creates report
  "submitted for approval" instead of "saved" or "created".
- `PendingChangesColumn`, `PendingChangesFilter`, a `ViewChangeRequestsAction` for record pages, and a
  `PendingChangeRequestsWidget`.
- Who can review: self-review is blocked by default, then `authorizeUsing()`, then a `reviewChangeRequest` gate,
  then the model's policy.
- Approving a held creation links the change request to the record it creates.
- Filament 4 and 5, Laravel 12.4+ and 13, PHP 8.3+.
