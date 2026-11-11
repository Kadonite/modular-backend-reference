# Project Overview

## 1. Purpose

Modular Backend Reference is an architectural reference implementation demonstrating how to design and develop maintainable backend applications using a modular monolith architecture.

The project explores practical applications of Domain-Driven Design (DDD), separation of concerns, explicit module boundaries, and reusable domain components.

It aims to demonstrate how a backend application can remain modular and maintainable as business complexity and operational requirements increase.

The implementation uses PHP 8.4 and Laravel, with an emphasis on keeping business logic independent of framework-specific infrastructure.

## 2. Problem Statement

As backend applications grow, business logic, persistence, and application workflows can become increasingly coupled.

Without clearly defined boundaries, common problems emerge:

- Business rules become distributed across controllers and services.
- Modules become dependent on internal implementation details of other modules.
- Changes in one area introduce unintended consequences elsewhere.
- Domain logic becomes difficult to test independently.
- Database access becomes tightly coupled to business behaviour.
- Extracting services becomes increasingly difficult.
- Architectural decisions become undocumented and inconsistent.

This project explores how these problems can be addressed through explicit domain boundaries, controlled dependencies, and well-defined application contracts.

## 3. Architectural Approach

The application will follow a modular monolith architecture.

A modular monolith provides a single deployable application while maintaining logical separation between business capabilities.

Each module owns its domain logic and exposes explicitly defined interfaces for other modules to consume.

This approach aims to balance:

- Architectural separation.
- Operational simplicity.
- Development efficiency.
- Transactional consistency.
- Long-term maintainability.

Distributed services will not be introduced without a clear business or technical justification.

## 4. Architectural Goals

### 4.1 Modularity

Organise the application around clearly defined business capabilities.

Each module should have explicit responsibilities and controlled dependencies.

### 4.2 Domain Independence

Business rules should not depend on Laravel, database implementations, HTTP requests, or external infrastructure.

The Domain layer should be independently testable.

### 4.3 Reusability

Common domain concepts should be implemented in a reusable manner where there is a genuine shared requirement.

Shared abstractions should not become a mechanism for coupling unrelated domains.

### 4.4 Testability

Business behaviour should be testable independently of infrastructure.

The architecture should support unit, integration, feature, and architectural testing.

### 4.5 Data Integrity

The system should protect important business invariants through:

- Explicit domain rules.
- Database transactions.
- Appropriate concurrency controls.
- Idempotent operations.
- Reliable event publication.

### 4.6 Evolvability

Modules should be structured so that individual business capabilities can evolve independently.

Where justified, a module should have a viable path towards becoming a separately deployed service.

## 5. Initial Business Capabilities

The initial application will model the following business capabilities:

| Capability | Responsibility |
|---|---|
| Identity | User accounts and identity management |
| Character | Character creation and progression |
| Inventory | Item ownership and storage |
| Economy | Currency and financial transactions |
| Crafting | Recipes, materials, and production |
| Notifications | Application and domain notifications |

These capabilities provide a practical domain context for exploring complex backend interactions.

They are not intended to represent a complete game or commercial product.

## 6. Non-Functional Requirements

The architecture should consider the following quality attributes.

### Maintainability

- Clear module responsibilities.
- Consistent internal structure.
- Explicit dependencies.
- Documented architectural decisions.

### Reliability

- Predictable business failure handling.
- Transactional integrity.
- Idempotent processing where appropriate.
- Reliable asynchronous event handling.

### Scalability

- Support increasing application workloads.
- Avoid unnecessary coupling between modules.
- Identify potential bottlenecks through measurement.
- Provide a path towards independently scaling selected capabilities.

### Security

- Validate externally supplied input.
- Enforce authorisation at appropriate boundaries.
- Protect sensitive configuration and credentials.
- Apply least-privilege principles.

### Observability

- Structured application logging.
- Correlation identifiers.
- Meaningful error reporting.
- Operational metrics where appropriate.

## 7. Technical Constraints

The initial implementation will use:

- PHP 8.4+
- Laravel
- PostgreSQL
- Redis
- Docker
- Pest
- PHPStan

Laravel will primarily provide application infrastructure, dependency injection, HTTP handling, configuration, and framework integrations.

It should not dictate the design of the domain model.

## 8. Explicit Non-Goals

The project will not initially attempt to:

- Implement a complete game or MMO.
- Build a microservices architecture.
- Introduce Kubernetes or complex orchestration.
- Support multiple database engines.
- Build a generic framework for every possible business domain.
- Optimise performance without measurable requirements.
- Introduce event sourcing as the default persistence strategy.

These decisions can be revisited if requirements justify them.

## 9. Architectural Evolution

The initial implementation will prioritise a modular monolith.

Future architectural evolution may include:

- Transactional outbox processing.
- Asynchronous integration events.
- Independent scaling of selected modules.
- Extraction of individual modules into services.
- Dedicated read models where justified.
- Improved operational monitoring.

Any significant architectural evolution should be documented through an Architecture Decision Record.

## 10. Success Criteria

The project will be considered successful when it demonstrates:

1. Clearly defined and enforceable module boundaries.
2. Domain logic independent of infrastructure.
3. Meaningful use of aggregates, entities, value objects, and domain events.
4. Explicit application workflows.
5. Reliable transactional operations.
6. Automated architectural boundary tests.
7. Documented architectural decisions and trade-offs.
8. A reproducible development environment.
9. A practical explanation of how the architecture can evolve.

The objective is not simply to demonstrate design patterns, but to demonstrate the reasoning behind their application.# docs/architecture/project-overview

