# Gauge — Workflow Assessment

Gauge ties workflow changes to **outcomes**, not outputs. Score jobs-to-be-done for repeatability, impact, and complexity before engineering agents into the flow.

## From output to outcome

| | Definition |
|---|------------|
| **Workflow output** | A release-ready, client-visible software change delivered through UAT or production handoff |
| **Desired outcome** | Dependable delivery: fewer defects reaching clients, faster UAT acceptance, predictable releases because every change is reviewed, validated in CI/staging, and handed off only when truly client-ready |

## Jobs-to-be-done

| Job | Pain point / barrier |
|-----|----------------------|
| Implement requested code and integration changes | Requirements and integration details slip between tools; switching across clients/repos raises mistakes and rework |
| Prepare work for review with sufficient evidence | Heavy manual packaging of evidence; reviewers get uneven signal; rework cycles grow |
| Review code for quality, correctness, and compliance | Limited senior bandwidth; inconsistent review depth vs policy and risk |
| Validate changes through CI | Flaky or noisy pipelines; confusing failures burn time and erode confidence |
| Validate integrations and environment-specific behavior | Staging/sandbox drift; integration edge cases hard to reproduce |
| Rework defects and revalidate | Each fix→CI→staging loop adds calendar time; unclear ownership stretches cycles |
| Merge approved work into mainline | Branch protection conflicts; "green enough?" judgment; risky after-hours merges |
| Confirm release readiness | Readiness spans UAT, checklists, rollback — easy to treat "code complete" as "release ready" |
| Complete client-visible handoff | Coordination of people, windows, and client availability — not only tooling |

## Scoring the review bottleneck

For **faster, contextualized reviews**, the exercise scored:

| Dimension | Rating | Rationale |
|-----------|--------|-----------|
| **Repeatability** | Medium | Steps repeat but MR quality varies |
| **Business impact** | High | Faster time-to-ship; fewer defects; protected margin and renewals |
| **Complexity** | Medium–High | Simple per PR; knotty in aggregate across repos and clients |

## Business outcomes of fixing review latency

When review waits on specific people and MR context is missing:

- Client delivery slips on calendar-bound review queues
- CI/triage becomes automatable but **human review serializes** the pipeline
- Defect escape risk rises when reviewers skim under load

The Gauge phase confirms: **high impact, medium repeatability** — a strong candidate for agent-first redesign with human judgment preserved at approval gates.
