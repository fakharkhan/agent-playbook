# Track — Value Measurement and Continuous Improvement

Track measures **outcomes**, not vanity counts. Define leading indicators before lagging metrics fully shift.

## Desired outcome (recap)

When this workflow succeeds, Soft Pyramid and its clients get **dependable delivery**: fewer defects reaching clients, faster UAT acceptance, predictable releases because every change is reviewed, validated, and handed off only when truly client-ready.

## Leading indicators (observable success signals)

Watch for these **before** outcome metrics fully move:

- MR/review packets routinely include a **validation digest + risk framing** before humans spend review time
- **Fewer emergency merges**; shorter rework loops per MR
- **CI noise decreases**: flakes classified/routed; fewer unexplained reds blocking merges
- **Client-ready artifacts appear earlier** (runbooks, checklists, UAT notes) vs last-minute scrambles

## Outcome metric example

**Metric:** Severity-weighted defect escapes per production release

| Field | Value |
|-------|-------|
| **Baseline** | Rolling 12-week average of weighted escapes per prod release (Critical/P1 = 5, Major/P2 = 3, Minor/P3 = 1) |
| **Target** | Cut weighted escapes by ≥25% vs baseline within two quarters, without worsening median time-to-client-ready or % UAT accepted first pass |
| **Measurement** | Production/support incidents mapped to releases within 14 days post-deploy via Jira/Azure DevOps + Git release tags + incident tracker |
| **Cadence** | Monthly metric review with owners; quarterly summary for leadership |

## Metric discipline

Track **leading indicators** alongside lagging ones:

| Leading | Lagging |
|---------|---------|
| Triage classification accuracy | Defect escapes |
| Time from CI failure to correct owner/next step | Cycle time to client-ready |
| Review packet completeness | UAT first-pass acceptance rate |
| Repeat failures after first fix | Revenue impact / renewal risk |

Do not declare victory on "number of drafts generated" or "agent invocations."

## Continuous improvement loop

1. **Measure** leading and outcome metrics weekly during pilot
2. **Review** misfires and update runbooks/prompts weekly
3. **Expand scope** only after two consecutive weeks hitting targets + stakeholder agreement
4. **Pause** if mis-triage drives bad outcomes, secrets incidents occur, or maintenance burden exceeds benefit

## Parallel investments

Speed must not outrun detection:

- **QA & observability** in parallel with agent assist
- **Named owner** for prompts, runbooks, and rollback when behavior drifts
- **Training:** when to trust vs override agent output
- **Vendor/model policy:** approved providers, data residency if clients require it
