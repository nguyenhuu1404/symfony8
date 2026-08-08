---
name: generate-crud-module
description: "Scaffold a full CRUD resource (DTO, Mapper, Service, Controller, Voter if needed) following the exact pattern in structure.md — the one already proven on Permission. Usage: /generate-crud-module <ResourceName>"
inclusion: auto
allowed-tools: [read_file, list_files, bash, grep]
metadata:
  author: "nguyenhuu140490@gmail.com"
  version: "0.2.0"
---
# Instructions

When the user runs `/generate-crud-module <ResourceName>` (e.g. `/generate-crud-module Role`):

1. **Initialize workspace**: create `units/crud-<resource-kebab>/thinking_state.md`:
   ```
   - [ ] Step 1: Clarify resource shape (fields, relations, DTO split)
   - [ ] Step 2: Design (per structure.md layer table)
   - [ ] Step 3: User approval (HARD GATE)
   - [ ] Step 4: Implementation
   - [ ] Step 5: Permission wiring (PermissionFixtures + fixtures:load)
   - [ ] Step 6: Tests written per test.md
   - [ ] Step 7: Post-scaffold checklist (structure.md) + verification
   ```

2. **Clarify before designing** — ask if unclear:
   - Fields, types, nullability, relations to existing entities.
   - Whether `create`/`update` genuinely need separate DTOs (per `structure.md`: default to
     ONE shared DTO, only split into `Create{Resource}RequestDto` / `Update{Resource}RequestDto`
     if update is meaningfully partial or create requires fields update doesn't).
   - Whether any action needs a Voter (data-dependent permission, e.g. "only the owner") — if
     every action's permission depends only on role, no Voter is needed per `rbac.md`.

3. **Design first, write nothing yet.** Produce `units/crud-<resource-kebab>/design.md` listing,
   for each file, its exact path per `structure.md`'s layer table — do not deviate from these
   paths:
   - `src/Dto/{Resource}/{Resource}RequestDto.php` (or `Create{Resource}RequestDto.php` /
     `Update{Resource}RequestDto.php` only if justified in step 2)
   - `src/Http/Mapper/{Resource}Mapper.php`
   - `src/Service/{Resource}Service.php`
   - `src/Controller/Api/V1/{Resource}Controller.php`
   - `src/Security/Voter/{Resource}Voter.php` — only if step 2 determined it's needed
   - The 4 permission strings this resource needs, per `rbac.md`'s table:
     `{resource}.view`, `{resource}.create`, `{resource}.edit`, `{resource}.delete`

   Present the design and **stop — wait for explicit approval** before writing code.

4. **After approval, implement in this order**:
   - **DTO**: validate with `Symfony\Component\Validator\Constraints`, no manual validation in
     Service.
   - **Mapper**: entity → array, expose only necessary fields (no password hashes, no internal
     flags, etc).
   - **Service**: methods `list()`, `create(dto)`, `update(entity, dto)`, `delete(entity)`. Check
     unique/conflict BEFORE persisting, throw the built-in `ConflictHttpException` — do not define
     a custom exception class for this. No try/catch for response formatting — `ApiExceptionListener`
     handles that.
   - **Controller**: 5 routes (`index` GET, `show` GET /{id}, `store` POST, `update` PUT/PATCH
     /{id}, `destroy` DELETE /{id}). Every route gets `#[IsGranted('{resource}.<action>')]` per the
     table in step 3. Bind input with `#[MapRequestPayload]` — never manual
     `$request->getContent()` parsing.
   - **Voter** (if needed): only for data-dependent checks; role-only checks stay as
     `#[IsGranted('{resource}.action')]` with no Voter.

5. **Wire the permission strings** (per `rbac.md`):
   - Add the 4 new permission strings to `PermissionFixtures`, assigned to the appropriate Role(s)
     — never leave one "orphaned" (unassigned to any role).
   - Tell the user to run:
     ```
     docker compose exec php php bin/console doctrine:fixtures:load
     ```
     and explicitly warn this wipes existing data unless `--append` is passed.

6. **Post-entity/route housekeeping** (per `tech.md`), run via Docker, in this order:
   ```
   docker compose exec php composer dump-autoload
   docker compose exec php php bin/console cache:clear
   docker compose exec php php bin/console debug:router | grep <resource>
   ```
   If a new Doctrine entity was involved:
   ```
   docker compose exec php php bin/console make:migration
   docker compose exec php php bin/console doctrine:migrations:migrate
   ```

7. **Tests** (per `test.md`): create `tests/{Resource}ControllerTest.php` covering, at minimum:
   - index — correct list, 403 when missing `.view`
   - show — 200 when exists, 404 when not
   - store — 201 valid input, 422 validation fail, 409 on conflict (if unique check applies), 403
     when missing `.create`
   - update — same as store plus 404 when entity doesn't exist, 403 when missing `.edit`
   - destroy — 204 success, 403 when missing `.delete`, 404 when not exists
   Assert both HTTP status AND the `{success, message, data}` envelope shape. Seed via existing
   DataFixtures, not ad-hoc entities, unless the case needs data not present in the shared fixture.

8. **Verify**: run
   ```
   docker compose exec php php bin/phpunit --filter={Resource}ControllerTest
   ```
   and report pass/fail counts — never claim done without this.

9. **Report**: files created, permission strings added (and whether fixtures:load was run), and
   confirm against the `structure.md` post-scaffold checklist explicitly (permission fixtures
   done? mapper doesn't leak sensitive fields? 5 endpoints tested? response format correct?).
