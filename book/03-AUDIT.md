# Audit — Current Workflow Mapping

Map your selected workflow as it **actually operates** — not as it should work. Be specific about steps, roles, decisions, and handoffs. Redesign quality depends on audit quality.

## Selected workflow

**Implementation & integration** covers end-to-end build and integration work for client projects: feature development and merge requests through code review, CI, dependency and environment integration, to release-ready, client-visible handoff.

## Start trigger

This process starts when scoped client work is ready for build:

- A ticket or issue moves to implementation (**In Progress / Development**) after requirements are agreed
- A developer opens work from an approved sprint or milestone
- **Reactive triggers:** MR created or updated; CI fails and needs a fix; bug or regression assigned to engineering; integration change required (API contract, environment, credentials, webhooks, dependencies)

Anything that requires writing, reviewing, or wiring code and integrations before release.

## Nine-step workflow map

### Step 1: Implement changes

| Field | Detail |
|-------|--------|
| **Overview** | Developer creates or updates a branch and builds the requested change |
| **Objective** | Produce working code, config, and integration updates for the client need |
| **Owner** | Implementing developer |
| **Decisions** | How to implement; address dependencies or environment needs |
| **Data** | Ticket scope, requirements, existing code, integration details |
| **Systems** | Git branch, codebase, local dev tools, dev environment |
| **Output** | Branch with implemented changes ready for self-test and MR preparation |

### Step 2: Prepare for review

Developer self-tests and prepares the merge request with clear evidence. Output: open or ready-for-review MR with supporting context (self-test results, scope notes, risk notes, checklist).

### Step 3: Conduct code review

Peer reviewers and leads validate quality, correctness, and policy compliance. Output: approvals or change requests on the MR.

### Step 4: Run CI validation

CI runs in parallel with review — builds, tests, quality gates. Output: green or failed CI status.

### Step 5: Perform integration validation

QA validates against staging and dependent systems. Output: staging success, sign-off, or noted defects.

### Step 6: Rework and revalidate

Issues from review, CI, or validation loop back through fixes and re-gates. Output: updated MR re-entering review, CI, and integration checks.

### Step 7: Merge approved work

Author or maintainer merges when policy gates are satisfied. Output: merged change in integration or release branch.

### Step 8: Advance to release-ready

PM, delivery, DevOps, and sometimes client confirm UAT, checklists, rollback plan. Output: change approved for client UAT or production release.

### Step 9: Complete client-visible handoff

Delivery, DevOps, and client complete staging UAT or production release. Output: client-visible handoff accepted.

## Audit insight: where delay lives

In the radical workflow redesign exercise, the dominant bottleneck was **missing context in the merge request** — review ping-pong across days because reviewers reconstruct scope from scattered systems. Policy ambiguity for sensitive changes was a close second.

That finding drives the Engineer phase: shrink **"reconstruct context from scattered systems"** before human review begins.
