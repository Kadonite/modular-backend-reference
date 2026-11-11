# Modular Backend Reference Architecture

A PHP 8.4 and Laravel reference implementation demonstrating a modular monolith architecture, Domain-Driven Design (DDD), clean separation of concerns, and scalable backend engineering practices.

The project explores how to structure a maintainable backend application around explicit domain boundaries, reusable domain components, and well-defined module contracts.

The architecture is designed to support independent module development today, while providing a foundation for extracting services when future requirements justify it.

---

## Project Goals

The primary objective is to demonstrate practical architectural patterns for building maintainable, extensible backend systems.

Key objectives:

- **Modular architecture** — Organise functionality around clearly defined business domains.
- **Domain-Driven Design** — Model business rules and behaviour independently of infrastructure.
- **Separation of concerns** — Keep domain, application, infrastructure, and presentation responsibilities distinct.
- **Reusable components** — Develop shared domain abstractions without creating unnecessary coupling.
- **Testability** — Make business logic independently testable and enforce architectural boundaries.
- **Data integrity** — Demonstrate transactional operations, concurrency protection, and reliable event handling.
- **Scalability** — Establish an architecture that can evolve as application and operational demands increase.

## Architecture Overview

The application follows a modular monolith approach.

Each module represents a distinct business capability and owns its domain logic, application workflows, and infrastructure implementations.

Modules communicate through explicit contracts rather than directly accessing each other's internal implementation details.

### Initial Bounded Contexts

| Module | Responsibility |
|---|---|
| Identity | User accounts and identity management |
| Character | Character creation and progression |
| Inventory | Item ownership, storage, and transfers |
| Economy | Currency, trading, and financial transactions |
| Crafting | Recipes, materials, and production |
| Notifications | Application and domain notifications |

These domains provide a practical business context for exploring complex backend interactions without tying the architecture to a particular game or product.

### Architectural Layers

Each module follows a consistent internal structure:

```text
Module/
├── Domain/
│   ├── Entity/
│   ├── ValueObject/
│   ├── Aggregate/
│   ├── Event/
│   ├── Specification/
│   └── Repository/
│
├── Application/
│   ├── Command/
│   ├── Query/
│   ├── Handler/
│   └── Service/
│
├── Infrastructure/
│   ├── Persistence/
│   ├── Repository/
│   └── Messaging/
│
└── Presentation/
    ├── Http/
    └── Console/
```

**Domain:** Contains business concepts, invariants, and rules. It should not depend on Laravel or infrastructure frameworks.

**Application:** Coordinates use cases and orchestrates domain behaviour.

**Infrastructure:** Implements persistence, messaging, and external integrations.

**Presentation:** Exposes application capabilities through HTTP endpoints and other interfaces.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP 8.4 | Primary language |
| Laravel | Application framework |
| PostgreSQL | Relational persistence |
| Redis | Caching and supporting infrastructure |
| Docker | Reproducible development environment |
| Pest | Automated testing |
| PHPStan | Static analysis |
| Laravel Pint | Code formatting |
| GitHub Actions | Continuous integration |

## Key Engineering Concepts

The project will progressively implement and demonstrate:

- Aggregate roots and entity identity.
- Value objects and domain invariants.
- Domain events and event dispatching.
- Specifications and business policies.
- Command and query handling.
- Repository abstractions.
- Explicit application results and business failures.
- Database transactions and optimistic concurrency.
- Transactional outbox and reliable event publication.
- Idempotent command processing.
- Automated architecture boundary tests.

## Example Use Case: Inventory Transfer

One of the primary use cases is transferring an item between inventories.

The operation demonstrates how business rules can be enforced within the domain while application handlers coordinate persistence and transaction boundaries.

Expected rules include:

- The source inventory must own the item.
- The item must be transferable.
- The destination must have sufficient capacity.
- A transfer must be atomic.
- Failed transfers must not result in lost or duplicated items.
- Repeated requests must not accidentally execute the same transfer twice.

This use case will serve as an architectural reference for other modules.

## Project Structure

```text
app/
├── Modules/
│   ├── Identity/
│   ├── Character/
│   ├── Inventory/
│   ├── Economy/
│   ├── Crafting/
│   └── Notifications/
│
└── Shared/
    └── Domain/

tests/
├── Unit/
├── Integration/
├── Architecture/
└── Feature/

docs/
├── architecture/
├── adr/
├── diagrams/
└── operations/

docker/
.github/
└── workflows/
```

## Architecture Documentation

Architecture decisions and supporting documentation will be maintained alongside the implementation.

| Document | Description |
|---|---|
| [Project Overview](docs/architecture/project-overview.md) | Architectural goals and scope |
| [Bounded Contexts](docs/architecture/bounded-contexts.md) | Domain boundaries and responsibilities |
| [Architecture Decisions](docs/adr/TEMPLATE.md) | Architectural Decision Records |
| [Diagrams](docs/diagrams/README.md) | System and module diagrams |
| [Operations](docs/operations/README.md) | Deployment and operational guidance |

## Getting Started

*Setup instructions are currently being developed.*

### Prerequisites

- PHP 8.4+
- Composer
- Docker and Docker Compose
- Git

### Installation

```bash
git clone https://github.com/Kadonite/modular-backend-reference.git

cd modular-backend-reference

composer install

cp .env.example .env

php artisan key:generate
```

Additional Docker, database migration, and test execution instructions will be added as the development environment is completed.

## Quality Standards

The project aims to maintain the following engineering standards:

- Automated unit and integration tests.
- Static analysis with PHPStan.
- Consistent formatting with Laravel Pint.
- Automated CI checks on pull requests.
- Explicit module dependency rules.
- Documented architectural decisions.
- No direct infrastructure dependencies in the domain layer.

## Project Status

<sub>Early development — architecture and foundations are being established.</sub>

The implementation is being developed incrementally. Features and architectural capabilities will be marked as implemented only once they have working code and appropriate tests.

## Design Principles

The project follows several guiding principles:

1. Prefer explicit boundaries over implicit coupling.
2. Keep business rules independent of infrastructure.
3. Optimise for maintainability before premature distribution.
4. Make architectural constraints testable.
5. Introduce complexity only when justified by a requirement.
6. Document trade-offs and consequences, not just technical choices.

## Licence

MIT Licence — see [LICENSE](LICENSE).
