# Legal Manifests

Publication and acceptance manifests are immutable database snapshots with canonical JSON and SHA-256. Publication manifests enumerate document identity, exact version, visibility, effective time, content checksum, and frozen artefact checksums.

The repository export service produces selected Approved or Published Markdown, `manifest.json`, `checksums.json`, and `CHANGELOG.md` without committing to Git or including acceptance Personal Data.

Platform snapshots and subject/organisation evidence PDF/CSV presentation remain extension points over the same manifest model.

`legal:verify-integrity` now verifies retained artifact existence/checksums, canonical manifest checksums, and acceptance evidence checksums without modification. It records the checked count, duration, result, and discrepancies; the scheduler runs it daily.
