# Specification Quality Checklist: Реєстрація сайтів, моніторинг статусу та резервне перемикання

**Purpose**: Validate specification completeness and quality before proceeding to planning
**Created**: 2026-07-21
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

## Requirement Completeness

- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous
- [x] Success criteria are measurable
- [x] Success criteria are technology-agnostic (no implementation details)
- [x] All acceptance scenarios are defined
- [x] Edge cases are identified
- [x] Scope is clearly bounded
- [x] Dependencies and assumptions identified

## Feature Readiness

- [x] All functional requirements have clear acceptance criteria
- [x] User scenarios cover primary flows
- [x] Feature meets measurable outcomes defined in Success Criteria
- [x] No implementation details leak into specification

## Notes

- **Clarification session 2026-07-21 applied**: OQ-1..OQ-3 resolved via design-doc edits and
  recorded in the spec's `## Clarifications` section. Key outcome: **no backup node / site-level
  failover exists** — the former US5/US6, the "Backup Node" entity, and failover FRs were **removed**
  from the spec. "Online/offline" is reframed as plugin **connection/sync health** (push-based; CRM
  does not ping servers).
- **OQ-4 resolved 2026-07-21 (proxy/ingress adopted)**: An intermediate proxy/ingress gateway is
  now a **mandatory** component (single public ingress; hides backend; TLS termination; per-site
  rate-limiting; **non-secret** edge checks only — authoritative HMAC verification stays on the
  backend). Added as FR-023..FR-033, a Key Entity, and a Dependencies entry. **No open questions
  remain** for this feature. An adversarial verify pass caught and fixed a flaw in the draft
  (proxy-side HMAC verification would have leaked all per-site secrets on proxy compromise).
- **✅ Constitution reconciled (v2.0.0, 2026-07-21)**: Principle IV reformulated (failover exception
  removed) and the Security section amended for the proxy (TLS on both legs, defense-in-depth,
  per-site secrets stay on the backend). No constitution↔spec conflict remains. Committed to
  `apolodb` as `e7ef836`.
- **Interactive clarify 2026-07-21 (2 Q)**: Heartbeat interval **~1 min**, offline after **3 misses
  (~5 min)** — pins A-2 and SC-002/SC-003. Per-site auth = **separate public site-id + secret HMAC
  key** (not a single token) — pins A-4, FR-002/003/004 and the Site Credentials entity.
- **Deliberately referenced constraints**: `TLS`, `queue (async)`, and `HMAC + timestamp` appear as
  named constraints because they are mandated by the constitution (Principles II/IV, Security) and by
  the user's explicit request — captured as constraints/assumptions (A-4), while Success Criteria are
  kept technology-agnostic. These are not leaked implementation choices.
- **Recommendation**: All open questions resolved; spec is **ready for `/speckit.plan`**.
