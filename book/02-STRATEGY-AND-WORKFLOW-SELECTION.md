# Strategy & Workflow Selection

Before applying the A.G.E.N.T. playbook to a single workflow, you need org-level strategy: which processes matter, where agentic AI fits, and which workflow earns **Focus** status for the redesign effort.

## The adoption vs. earnings gap

Course readings from Dirk Hofmann and Ulla Kruhse-Lehtonen describe a familiar pattern: many enterprises report using AI, yet a large share see **no improvement in bottom-line earnings**. Returns fail when organizations locally optimize (faster email, faster typing) instead of redesigning how work flows.

For Soft Pyramid, the professional goal is to move workflows from **unstructured, messy inputs** to **structured, reliable actions** — with governance that matches an enterprise delivery background: clear accountability, immutable logs, and least privilege.

## Workflow evaluation

| Workflow | Business Impact | Current Effort | Automation Potential | Priority |
|----------|-----------------|----------------|----------------------|----------|
| Scoping & requirements | High | Medium | High | Medium |
| Solution / technical design | High | Medium | High | Medium |
| **Implementation & integration** | High | High | High | **Focus** |
| QA & testing | High | High | High | High |
| Release & deployment | Medium | Medium | High | Medium |
| Client handoff & ongoing support | High | Medium | Medium | Medium |

**Why Implementation & integration was selected:** This stage drives most calendar time on client projects and is where defects and integration issues become visible externally. Agentic copilots and workflow orchestration can accelerate build and integration work while preserving explicit human review for client data boundaries, security, and release risk.

## Triggers for the focus workflow

**Planned work:** A scoped item moves into build — ticket/issue **In Progress**, sprint commitment, or agreed milestone after requirements are sufficiently defined.

**Reactive work:** Merge request opened or updated; **CI pipeline failure**; bug or regression triaged to engineering; client or PM escalation requiring code or config change.

**Integration-specific:** New or changed API contract, environment (staging/prod), credential rotation, or dependency update requiring implementation before release.

*One-line summary:* Ticket→build start, PR/CI events, scoped change requests, integration/API or env changes requiring code.

## From strategy to playbook

The strategy document answers: *What is our agentic AI vision? Which enabling capabilities must exist? Which workflow do we transform first?*

The playbook answers: *How does that workflow operate today, how should it operate with agents, and how do we measure and pilot the change?*

The rest of this book is the playbook applied to **Implementation & integration** at Soft Pyramid LLC.
