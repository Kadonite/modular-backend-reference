# ADR-001: Inventory Persistence Architecture

- **Status:** Accepted
- **Date:** 2026-11-15
- **Decision Makers:** Project maintainers
- **Related Decisions:** None

## Context

The project is being developed as a modular monolith using Laravel and PostgreSQL.

Although the initial infrastructure and database connectivity have been established, the application does not yet have a defined persistence architecture for its business modules.

The Inventory module will provide the first meaningful persistence use case. It is expected to support operations such as:

- Creating and storing inventory items.
- Retrieving inventory state.
- Moving items between inventories.
- Enforcing inventory capacity and ownership rules.
- Maintaining consistency when multiple operations affect the same inventory.

These operations introduce several architectural concerns:

- Domain logic must remain independent of Laravel and Eloquent.
- Persistence responsibilities must not leak into domain entities or value objects.
- Modules must retain ownership of their own data.
- Operations involving multiple records must maintain transactional consistency.
- Concurrent requests must not cause item duplication, loss, or invalid inventory state.
- Persistence implementations must remain replaceable without requiring changes to domain behaviour.

Without an agreed approach, persistence concerns could become tightly coupled to Laravel models, database queries could spread across application code, and module boundaries could become difficult to enforce.

The architecture should support the current modular monolith while leaving the option of extracting individual modules into separate services if future requirements justify it.

## Decision

The project will use a **Repository Pattern with explicit Application-layer contracts and Infrastructure-layer implementations**.

Persistence responsibilities will be separated across the existing architectural layers.

### Domain

The Domain layer will:

- Define entities, aggregate roots, and value objects.
- Enforce business invariants.
- Remain independent of Laravel, Eloquent, SQL, and database infrastructure.
- Contain no database queries or persistence-specific annotations.

Domain objects will represent business state and behaviour rather than database records.

### Application

The Application layer will:

- Coordinate use cases and domain operations.
- Define repository interfaces required by application use cases.
- Define transaction-management contracts where required.
- Control the orchestration of persistence operations.
- Return explicit application results for expected business failures.

Repository contracts will be defined by the module that requires them, rather than by the Infrastructure layer.

For example:

`App\Modules\Inventory\Application\Contracts\InventoryRepository`

Transaction-management contracts may be shared through `App\Shared\Application\Contracts` when multiple modules require the same capability.

### Infrastructure

The Infrastructure layer will:

- Implement Application-layer repository contracts.
- Contain Eloquent models and database-specific mapping.
- Execute database queries.
- Implement transaction-management contracts.
- Handle persistence-specific concerns such as locking and query optimisation.

Eloquent models will not be exposed to the Domain or Application layers.

Repositories will map between Eloquent persistence models and domain objects.

### Presentation

The Presentation layer will invoke Application-layer use cases.

It will not directly access Eloquent models or execute persistence queries.

### Module Data Ownership

Each module will logically own its persistence model and associated database tables.

Initially, modules will share the same PostgreSQL database.

However:

- Modules must not access another module's internal repositories.
- Modules must not directly query another module's internal tables.
- Cross-module interactions must occur through explicitly defined application contracts or module interfaces.
- Shared database infrastructure does not imply shared ownership of business data.

Physical database separation is not required at this stage.

### Transaction Boundaries

Transactions will be controlled by Application-layer use cases through an explicit transaction-management contract.

Infrastructure will provide the PostgreSQL-backed implementation.

Operations that modify multiple pieces of related state must execute atomically.

For example, an inventory transfer must ensure that:

1. The source inventory is validated.
2. The item is removed from the source.
3. The item is added to the destination.
4. Both changes are committed together.

If any operation fails, the transaction must roll back.

Concurrency control will be implemented using PostgreSQL-supported mechanisms. The initial inventory transfer implementation must protect against concurrent modifications, using row-level locking or an appropriate conditional-update strategy based on the final persistence model.

The specific locking strategy will be validated when the inventory aggregate and schema are designed.

## Alternatives Considered

### Option 1: Repository Pattern with Application-Layer Contracts

Define repository interfaces in Application and implement them in Infrastructure.

**Advantages:**
- Keeps domain logic independent of persistence technology.
- Allows infrastructure implementations to change without changing business logic.
- Provides clear ownership of application dependencies.
- Makes application use cases straightforward to unit test.
- Supports module boundaries and future service extraction.

**Disadvantages:**
- Requires mapping between persistence models and domain objects.
- Introduces additional interfaces and implementation classes.
- Requires discipline to prevent repositories becoming overly generic or bloated.

### Option 2: Active Record Using Eloquent Throughout the Application

Use Eloquent models directly within application use cases and business logic.

**Advantages:**
- Fast initial development.
- Less mapping code.
- Familiar Laravel conventions.
- Convenient access to relationships and query capabilities.

**Disadvantages:**
- Couples business logic to Laravel and Eloquent.
- Makes domain behaviour harder to test independently.
- Risks spreading persistence concerns across modules.
- Makes changing persistence strategies more difficult.
- Can blur the distinction between database structure and domain concepts.

### Option 3: Direct Database Access Through Application Services

Allow application services to execute database queries directly using Laravel's database abstractions.

**Advantages:**
- Fewer repository classes.
- Straightforward for simple CRUD operations.
- Provides direct access to SQL and database-specific functionality.

**Disadvantages:**
- Database concerns become distributed across application services.
- Query logic can become duplicated.
- Makes persistence boundaries harder to enforce.
- Increases the risk of cross-module data access.
- Makes domain-oriented persistence behaviour more difficult to maintain.

## Rationale

Option 1 is selected because it provides the strongest separation between business behaviour and persistence infrastructure while remaining practical for a Laravel modular monolith.

The additional mapping and interface code is an accepted cost.

The architecture is intended to demonstrate maintainable domain modelling, explicit dependencies, testability, and controlled module boundaries rather than optimising for the smallest possible initial implementation.

Repository contracts in Application allow use cases to express the persistence capabilities they require without depending on how those capabilities are implemented.

Keeping Eloquent in Infrastructure preserves Laravel's productivity and database integration benefits without making it a requirement of the Domain layer.

Logical module data ownership also establishes a foundation for future service extraction without introducing distributed-system complexity prematurely.

## Consequences

### Positive Consequences

- Domain logic remains persistence-independent.
- Application use cases depend on explicit contracts.
- Eloquent is isolated within Infrastructure.
- Modules retain logical ownership of their data.
- Persistence implementations can be replaced independently of business rules.
- Application use cases can be tested using repository substitutes.
- PostgreSQL transactions provide atomicity for multi-record operations.
- The architecture supports future module extraction where justified.

### Negative Consequences

- Additional interfaces and mapping code are required.
- Developers must maintain consistency between domain objects and persistence models.
- More deliberate design is required when introducing new persistence operations.
- Repository abstractions can become unnecessarily generic if not kept focused on actual use cases.

### Risks

- Repository implementations could gradually accumulate business logic that belongs in the Domain layer.
- Application services could become overly complex if transaction orchestration is not kept focused.
- Poorly designed transaction boundaries could introduce deadlocks or unnecessary contention.
- Shared database access could undermine module ownership if architectural boundaries are not tested.
- Incorrect concurrency handling could still allow invalid inventory state.

## Implementation Notes

The following structure is proposed for the Inventory module:

```text
Inventory/
├── Domain/
│   ├── Aggregates/
│   ├── Entities/
│   └── ValueObjects/
│
├── Application/
│   ├── Contracts/
│   │   └── InventoryRepository.php
│   └── Commands/
│       └── TransferItem/
│           ├── TransferItemCommand.php
│           └── TransferItemHandler.php
│
├── Infrastructure/
│   └── Persistence/
│       └── Eloquent/
│           ├── Models/
│           └── Repositories/
│
└── Presentation/
```

The transaction-management contract should be introduced when the first use case requires it. If multiple modules need the same abstraction, it should be placed in Shared Application contracts rather than duplicated.

Implementation should proceed incrementally:

1. Define the inventory domain model and its invariants.
2. Define the repository contract around actual use cases.
3. Design the persistence schema to support the domain model.
4. Implement the Eloquent persistence model.
5. Implement repository mapping.
6. Introduce transaction management for multi-record operations.
7. Implement concurrency protection for inventory modifications.
8. Add integration tests against PostgreSQL.
9. Add architecture tests enforcing module and layer boundaries.

The existing Laravel framework migrations should not be treated as the final schema for the Inventory module.

No inventory-specific schema or aggregate structure is mandated by this ADR.

## Validation

The architecture will be evaluated through automated tests and implementation review.

Acceptance criteria:

- Domain classes must not depend on Laravel or Eloquent.
- Domain classes must not depend on Infrastructure namespaces.
- Application contracts must not expose Eloquent models.
- Repository implementations must be contained within Infrastructure.
- Inventory operations must not directly access another module's internal persistence implementation.
- Multi-record inventory operations must execute within a transaction.
- A failed transfer must not result in item loss or duplication.
- Concurrent transfer attempts must not violate inventory invariants.
- PostgreSQL integration tests must verify persistence behaviour.
- Architecture tests must enforce the agreed dependency boundaries.

Performance should be measured once meaningful inventory workloads exist. No throughput or concurrency targets are established by this ADR.

## Review Conditions

This decision should be reconsidered if:

- A module requires independent deployment or database ownership.
- Repository abstractions introduce significant measured performance problems.
- Persistence requirements change substantially.
- A different persistence technology becomes necessary.
- Cross-module data access becomes difficult to manage within the current model.
- Transactional requirements exceed what the modular monolith can reasonably support.

Any material change should be documented in a subsequent ADR.

## References

- `docs/architecture/project-overview.md`
- `docs/architecture/bounded-contexts.md`
- `docs/adr/TEMPLATE.md`
- Laravel Database Documentation: https://laravel.com/docs/database
- PostgreSQL Documentation: https://www.postgresql.org/docs/
