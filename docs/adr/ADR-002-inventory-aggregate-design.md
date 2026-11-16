# ADR-002: Inventory Aggregate Design

- **Status:** Proposed
- **Date:** 2026-11-16
- **Decision Makers:** Project maintainers
- **Related Decisions:** ADR-001

## Context

The Inventory module requires a domain model capable of managing item storage while preserving aggregate invariants.

The model must support:

- Fixed slot-based capacity.
- Expandable capacity.
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

All additions and removals are atomic.

An operation that cannot complete must leave the aggregate unchanged.

Expected business failures are represented explicitly rather than relying on exceptions.

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
- Aggregate versioning is needed to prevent concurrent lost updates.

## Alternatives considered

### An Inventory service without an aggregate root

Rejected because business invariants would become distributed across application services.

### Eloquent models as the domain model

Rejected because this would couple business rules to Laravel and persistence concerns.

### One aggregate containing all character storage

Rejected because it would create unnecessarily large aggregate boundaries and concurrency contention.

## Validation

The decision will be validated through:

- Unit tests for inventory invariants.
- Unit tests for every supported mutation.
- Tests proving failed operations leave state unchanged.
- Architecture tests preventing Domain dependencies on Laravel and Infrastructure.
- Integration tests when persistence is introduced.

## Implementation notes

The initial implementation should introduce only the domain concepts needed for the first complete Inventory use cases.

Do not introduce Bank, Equipment or generic ownership abstractions prematurely.