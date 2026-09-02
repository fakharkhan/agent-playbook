# Navigate — Human-Agent Collaboration & Trust

The Navigate phase documents how agent-human interaction works in practice — not by default, but by explicit design per redesigned step.

## Interaction modes by step

| Step | Mode | Rationale |
|------|------|-----------|
| 1. Normalize scope | **Collaborative** | Scope errors are cheap on paper, expensive in code; humans resolve contradictions |
| 2. Build and package | **Collaborative** | Engineering accountability stays human; agents accelerate parallel work |
| 3. Validation and triage | **Agent-autonomous** | CI/staging signals are largely mechanical; humans for ambiguity only |
| 4. Review and rework | **Collaborative** | Guardian pre-reviews; humans approve along risk thresholds |
| 5. Certify release readiness | **Collaborative** | Highest blast radius — human owns client-visible certification |
| 6. Merge and handoff | **Collaborative** | Agents produce runbooks; humans retain production authority |

## Escalation principles

**Escalate to human when:**

- Inputs are incomplete, contradictory, or ambiguous
- Risk tier or reviewer routing cannot be inferred safely
- Client policy or contract interpretation is required
- Triage confidence is below threshold or blast radius is high
- Sensitive modules: security, data, billing, client-visible semantics
- Production promotion, gate waivers, or UAT acceptance ambiguity

**Human override is always available:** block merge/release regardless of agent recommendation; waive gates only through named approver path.

## Building staff trust early

Introduce agents first on **internal delivery mechanics** — review packets, validation digests, runbook drafts, checklist assembly — where mistakes are cheap and clients are not exposed.

- Start with **one repo or one squad**
- Define success metrics: time-to-first-useful-review, revision rounds, CI noise reduction
- Weekly demos: engineers see agent output before it affects merge or release
- Label agents as **assistants with bounded authority**, not owners of production promises

## Human override and correction

Every autonomous step produces a **visible artifact** (packet, digest, runbook draft) plus version or run ID for traceability.

Humans can:

- Reject or edit artifacts
- Block merge/release regardless of agent recommendation
- Waive gates only through named approver path
- Roll back using agent-drafted rollback notes — humans decide whether to execute

For implementation work, override means reopen/reassign the MR path — never silent patching without a record.

## Feedback loops that improve agent behavior

Corrections become **labeled feedback**, not one-off complaints:

- Miscategorized risk → tagged entry tied to artifact ID
- Wrong reviewer route → ticket for routing rule update
- Missed flaky-test signature → baseline update

On a monthly cadence, review patterns and update rules, prompts, routing thresholds, and policy checks.

Measure improvement by: lower defect escape, fewer revision loops to approval, shorter time from failure to classified root cause — not "fewer escalations" alone.

## Application order at Soft Pyramid

Apply **internal delivery standards first** (evidence packs, approvals, traceability, measurement). Roll out a **client-facing playbook** only once internal mechanics are trustworthy and repeatable.
