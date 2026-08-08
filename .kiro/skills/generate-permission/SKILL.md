---
name: generate-permission
description: "Add one or more RBAC permission strings for a resource end-to-end: PermissionFixtures entry, #[IsGranted] wiring on the Controller, Voter only if data-dependent, and fixtures:load reminder. Usage: /generate-permission <ResourceName> [action]"
inclusion: auto
allowed-tools: [read_file, list_files, bash, grep]
metadata:
  author: "nguyenhuu140490@gmail.com"
  version: "0.2.0"
---
# Instructions

When the user runs `/generate-permission <ResourceName> [action]`:
- With no action given (e.g. `/generate-permission Role`): wire the full standard set —
  `role.view`, `role.create`, `role.edit`, `role.delete` — per `rbac.md`'s standard table.
- With a specific non-standard action (e.g. `/generate-permission Permission toggle-status`):
  treat it as a one-off addition beyond the standard 4, per the "action đặc thù" clause in
  `rbac.md` — requires documenting the reason.

1. **Initialize workspace**: `units/permission-<resource-kebab>[-<action-kebab>]/thinking_state.md`:
   ```
   - [ ] Step 1: Clarify scope (standard 4-action set, or one-off action)
   - [ ] Step 2: Check Controller routes exist for each permission
   - [ ] Step 3: User approval (HARD GATE)
   - [ ] Step 4: Implementation (fixtures + IsGranted wiring)
   - [ ] Step 5: fixtures:load reminder
   - [ ] Step 6: Tests (granted + denied paths)
   - [ ] Step 7: Verification
   ```

2. **Clarify before designing** — ask if unclear:
   - For the standard set: which Role(s) should get which of the 4 permissions (they don't have
     to all go to the same role — e.g. an editor role might get `view`+`edit` but not `delete`).
   - For a one-off action: what triggers it, and why it doesn't fit the standard `view`/`create`/
     `edit`/`delete` set.
   - Whether this permission is role-only, or data-dependent (needs a Voter per `rbac.md` —
     "chỉ tạo Voter khi quyền phụ thuộc vào DATA", e.g. "only the owner").

3. **Check the Controller first.** Look at `src/Controller/Api/V1/{Resource}Controller.php`:
   - Confirm a route exists for each action being granted (don't add `role.delete` to fixtures if
     there's no `destroy` route to attach it to — flag the mismatch instead).
   - If the permission is data-dependent, check whether `src/Security/Voter/{Resource}Voter.php`
     already exists — extend it rather than creating a second Voter for the same resource.

4. **Design before writing.** Produce `units/permission-<resource-kebab>[-<action>]/design.md`:
   - Exact permission string(s): `{resource}.view` / `.create` / `.edit` / `.delete`, or the
     one-off string, per `rbac.md` convention.
   - Which Role(s) each string will be assigned to in `PermissionFixtures`.
   - Voter change, if any (new `supports()`/`voteOnAttribute()` case, in plain terms who is
     granted and why).

   Stop and wait for approval before implementing.

5. **After approval, implement**:
   - Add the permission string(s) to `PermissionFixtures`, assigned to the Role(s) from step 4 —
     never leave one unassigned to any role.
   - Add/confirm `#[IsGranted('{resource}.<action>')]` on the corresponding Controller route(s).
   - If data-dependent: extend the resource's Voter (create it only if it doesn't exist yet).

6. **Remind about data**: tell the user to run
   ```
   docker compose exec php php bin/console doctrine:fixtures:load
   ```
   and warn this wipes existing data unless `--append` is passed — this permission won't exist in
   the DB until fixtures are (re)loaded.

7. **Tests are mandatory for both paths** (per `test.md` and `rbac.md`) — never consider a
   permission done with only the granted case tested:
   - A user WITH the role/permission → expects success (200/201/204 as appropriate).
   - A user WITHOUT it → expects 403.
   - If a Voter was added/extended, also unit-test `vote()` directly for both outcomes, in addition
     to the Controller-level 403 check.

8. **Verify**: run the relevant test file(s) and report pass/fail — never claim wired without
   evidence both the granted and denied paths pass.

9. **Report**: permission string(s) added, Role assignment, whether a Voter was touched, and
   whether `fixtures:load` still needs to be run by the user.
