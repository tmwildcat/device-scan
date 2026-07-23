# Legal Governance Operations

## Import and authoring

Run `php artisan legal:import`. Review reported conflicts; never replace a production Draft without an explicit reconciliation decision. Counsel edits Draft Markdown through `/admin/legal-governance` and supplies a change summary.

Run `php artisan legal:validate-documents` before import. It reports manifest/path, Draft metadata, operative-status, relative-link, privacy-document, duplicate-slug, and placeholder findings without changing files.

The local `legal-counsel@linewatt.test` demo user is verified and receives the configured Legal Governance permissions through its role. After login it lands on `/admin/legal-governance`. Super Administrators land on `/admin/platform` and use its Legal Governance navigation entry.

## Review and publication

Submit a checksum-bound version for required reviews, record decisions, approve only with current required approvals, resolve release-blocking placeholders, set dates, generate previews, and publish transactionally. Verify frozen artefact and manifest checksums after publication.

The review detail shows the prior version and a line-level textual change view. The publication panel reports review, placeholder, metadata, artifact, checksum, manifest, effective-date, workflow, re-acceptance, and visibility readiness independently. A Scheduled version may be cancelled back to Approved; withdrawal is a separate permission-controlled, audited action.

## Later lifecycle

Create a new Draft for corrections. Publish it before superseding the old version. Assign deduplicated re-acceptance obligations for a Material Change. Withdrawal restricts delivery but preserves evidence.

## Recovery

Back up database and legal artifact storage together. Integrity checks compare stored artefact bytes to their SHA-256 values and manifests. Apply Legal Holds before any approved retention job. No automatic retention deletion is currently enabled.

Run `php artisan legal:verify-integrity`. A non-zero result identifies discrepancies and requires investigation; checksum verification establishes technical consistency, not legal validity.
