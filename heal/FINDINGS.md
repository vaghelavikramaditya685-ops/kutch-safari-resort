# Findings

| ID | Category | Where | Message | Trigger | Status |
|---|---|---|---|---|---|
| F1 | CONSOLE-WARNING | frontend/index.html:6 | <link rel=preload> uses an unsupported `as` value | every website page load | FIXED (loop 2) |
| F2 | RUNTIME (console error) | Destination.tsx:153,167,176; RannUtsavPackage.tsx:15,24 | In HTML, <a> cannot be a descendant of <a> | opening any /destination/* or /white-rann-camp page | FIXED (loop 2) |
| F3 | NETWORK | engine pages | GET /favicon.ico 404 | opening any engine page | FIXED (loop 3) |
