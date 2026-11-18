# ADR-002: Inventory Aggregate Design

- **Status:** Accepted
- **Date:** 2026-11-16
- **Decision Makers:** Project maintainers
- **Related Decisions:** ADR-001

## Context

The Inventory module requires a domain model capable of managing item storage while preserving aggregate invariants.

The model must support:

- Slot-based capacity with explicit expansion.
- Stackable and unique items.
- Automatic and explicit item placement.
- Splitting, merging and swapping.
- Atomic additions and removals.
- Future storage containers and specialised inventories.

The Inventory domain must remain independent of Laravel, Eloquent and persistence infrastructure.

## Decision

Introduce an `Inventory` aggregate root responsible for maintaining the consistency of its own slot collection.

The aggregate owns its capacity, slots, version and inventory-level business rules.

### Aggregate responsibilities

The Inventory aggregate is responsible for:

- Adding and removing items.
- Moving items between slots.
- Swapping slots.
- Splitting and merging stacks.
- Increasing capacity.
- Checking available capacity.
- Maintaining slot and stack invariants.
- Recording domain events after successful mutations.

### Capacity

Inventory capacity is slot-based.

Each slot can contain at most one item stack or one unique item reference.

Capacity increases append slots. Existing slot indexes remain stable.

Capacity reduction is not supported in the initial implementation.

### Mutation semantics

All inventory mutations preserve aggregate consistency.

An operation that cannot complete must leave the aggregate unchanged.

Successful mutations increment the aggregate version exactly once.

Expected business failures from Result-returning operations are represented explicitly rather than relying on exceptions.

Invalid constructor arguments and invalid method arguments are rejected through exceptions.

### Domain events

The aggregate records one domain event for each successful business mutation.

Failed operations must not record domain events.

Events describe the business operation and contain relevant identifiers, quantities and slot indexes rather than exposing aggregate internals.

Pending domain events can be retrieved and cleared by the application layer.

### Aggregate boundary

The Inventory aggregate does not:

- Query item repositories.
- Persist itself.
- Coordinate transfers between multiple inventories.
- Manage equipment slots.
- Manage bank ownership.
- Manage storage-item lifecycle.

These responsibilities belong to Application workflows and their respective modules.

### Domain independence

The Inventory Domain must not depend on Laravel, Eloquent, SQL or Infrastructure implementations.

Application-layer contracts and Infrastructure implementations follow ADR-001.

## Consequences

### Positive

- Inventory invariants are enforced in one place.
- Domain logic can be tested independently.
- Persistence technology can change without changing the domain model.
- Multiple application workflows can reuse the same aggregate operations.
- Future storage types can reuse the Inventory model.

### Negative

- Multi-inventory operations require application-level coordination.
- The aggregate must be loaded and validated before mutation.
- The aggregate version must be used by persistence infrastructure to prevent concurrent lost updates.

## Alternatives considered

### An Inventory service without an aggregate root

Rejected because business invariants would become distributed across application services.

### Eloquent models as the domain model

Rejected because this would couple business rules to Laravel and persistence concerns.

### One aggregate containing all character storage

Rejected because it would create unnecessarily large aggregate boundaries and concurrency contention.

## Validation

The decision is validated through:

- Unit tests for inventory invariants.
- Unit tests for every supported mutation.
- Tests proving failed operations leave state unchanged.
- Tests verifying domain event recording.
- Architecture tests preventing Domain dependencies on Laravel and Infrastructure.
- Integration tests when persistence is introduced.

### Completed validation

The initial domain implementation has been validated through:

- Unit tests covering aggregate construction and supported mutations.
- Tests verifying successful and failed mutation behaviour.
- Tests verifying domain event recording.
- PHPStan static analysis.
- Laravel Pint formatting.
- Architecture tests enforcing domain independence.

The full test suite currently passes with 186 tests and 559 assertions. PHPStan reports no errors.

Integration tests for persistence remain outstanding and will be completed when the persistence implementation is introduced.

## Implementation notes

The initial implementation should introduce only the domain concepts needed for the first complete Inventory use cases.

Do not introduce Bank, Equipment or generic ownership abstractions prematurely.