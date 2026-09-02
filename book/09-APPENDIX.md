# Appendix — Radical Workflow Redesign Exercise

This appendix captures the DirkAI tutorial exercise on **radical workflow redesign for 2–10× productivity gains** — the conversation that surfaced the MR review bottleneck driving this playbook.

## Core lesson

Organizations adopt AI/agentic tooling but see modest gains because capability is layered onto **human-centric workflows** instead of workflows being **redesigned for agents**.

| Human-centric | Agent-centric |
|---------------|---------------|
| Handoffs, calendars, tacit judgment in sequenced steps | Modular work with explicit inputs/outputs and systems of record |
| Humans as primary workers | Agents coordinate execution; humans set intent, guardrails, exceptional judgment |
| Ad hoc context reconstruction | APIs, structured context, continuous monitor→act→verify loops |

## Conversation arc (summary)

| Stage | Finding |
|-------|---------|
| **Bottleneck** | Review latency — PRs wait on specific people; CI/triage automatable but review serializes delivery |
| **Audit — dominant driver** | **Missing context in the MR** → review ping-pong across days; policy ambiguity second for sensitive changes |
| **Gauge — outcomes** | Faster time-to-ship; fewer defects/hotfixes; protected margin and renewals; predictable delivery without trading speed for safety |
| **Gauge — scoring** | Medium repeatability; high business impact; medium–high complexity |
| **Engineer — first shrink target** | **"Reconstruct context from scattered systems"** before review — require machine-assembled review packet (risk, tests, owners, links) so first human touch is judgment, not archaeology |
| **Takeaway** | Adding AI to an old loop moves the bottleneck; leverage is **workflow redesign** — legible decisions, explicit policies, agents assembling evidence |

## Framework recap

1. **Audit** — Map where delays come from (routing, context, policy, load)
2. **Gauge** — Tie changes to outcomes; score repeatability, impact, complexity
3. **Engineer** — Choose what to eliminate or shrink first in agent-first design

## Application at Soft Pyramid

Apply first to **internal delivery standards** (evidence packs, approvals, traceability, measurement). Extend to **client-facing playbook** once trustworthy and repeatable.

---

## About the Harvard intensive

This playbook was developed during the **Harvard Data Science Review 2.5 Week Agentic AI Intensive** (*Agentic AI: Contextualized and Applied*, April 14–30, 2026), presented by the Harvard Data Science Initiative.

**Author:** Fakhar Khan · Soft Pyramid LLC · [fakharkhan.com](https://fakharkhan.com)

**Certificate verification:** KKNW-EQWZ

---

*End of The A.G.E.N.T. Playbook*
